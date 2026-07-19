<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_REST_Controller {
	private static function debug_reference_id_from_response( array $response ): string {
		if ( isset( $response['debug_reference_id'] ) && is_string( $response['debug_reference_id'] ) ) {
			return $response['debug_reference_id'];
		}

		return '';
	}

	private static function mark_failed_with_observability( string $run_id, string $external_run_id, string $code, string $message ): array {
		$response = Audio_Converter_Job_Store::mark_failed( $run_id, $code, $message );

		$retryable = isset( $response['error']['retryable'] ) ? (bool) $response['error']['retryable'] : false;
		Audio_Converter_Observability::log_lifecycle(
			'run_failed',
			$run_id,
			$external_run_id,
			array(
				'status'             => 'failed',
				'error_code'         => $code,
				'error_message'      => $message,
				'error_retryable'    => $retryable,
				'debug_reference_id' => self::debug_reference_id_from_response( $response ),
			)
		);

		return $response;
	}

	private static function count_words( string $text ): int {
		$normalized = trim( preg_replace( '/\s+/', ' ', $text ) );
		if ( '' === $normalized ) {
			return 0;
		}

		return count( preg_split( '/\s+/', $normalized ) );
	}

	private static function collect_content_text( array $normalized ): string {
		$chunks = array();

		if ( isset( $normalized['title'] ) && is_string( $normalized['title'] ) ) {
			$chunks[] = $normalized['title'];
		}

		if ( isset( $normalized['sections'] ) && is_array( $normalized['sections'] ) ) {
			foreach ( $normalized['sections'] as $section ) {
				if ( ! is_array( $section ) ) {
					continue;
				}

				if ( isset( $section['heading'] ) && is_string( $section['heading'] ) ) {
					$chunks[] = $section['heading'];
				}

				if ( isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ) {
					foreach ( $section['paragraphs'] as $paragraph ) {
						if ( is_string( $paragraph ) ) {
							$chunks[] = $paragraph;
						}
					}
				}
			}
		}

		return implode( "\n", $chunks );
	}

	private static function evaluate_quality_flags( array $payload, array $normalized ): array {
		$flags        = array();
		$content_text = self::collect_content_text( $normalized );
		$word_count   = self::count_words( $content_text );

		if ( '' === trim( $content_text ) ) {
			$flags[] = 'empty_content';
		}

		// Heuristic only: unusually short content can indicate low transcription quality or unintelligible audio.
		if ( $word_count > 0 && $word_count < 80 ) {
			$flags[] = 'audio_possibly_unintelligible';
		}

		if ( ! empty( $payload['proper_noun_hints'] ) && is_array( $payload['proper_noun_hints'] ) ) {
			$missing_count = 0;
			foreach ( $payload['proper_noun_hints'] as $hint ) {
				if ( ! is_string( $hint ) || '' === trim( $hint ) ) {
					continue;
				}

				if ( false === stripos( $content_text, $hint ) ) {
					$missing_count++;
				}
			}

			if ( $missing_count > 0 ) {
				$flags[] = 'proper_noun_hints_missing';
			}
		}

		if ( empty( $flags ) ) {
			$flags[] = 'quality_checks_passed';
		}

		return array_values( array_unique( $flags ) );
	}

	public static function execute_ability( array $payload ) {
		if ( isset( $payload['input'] ) && is_array( $payload['input'] ) && ! isset( $payload['contract_version'] ) ) {
			$payload = $payload['input'];
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			Audio_Converter_Observability::log_event( 'run_rejected_unauthorized' );
			return Audio_Converter_Ability_Contract::error_response( 'unauthorized', 'You are not allowed to execute this ability.' );
		}

		if ( ! is_array( $payload ) ) {
			Audio_Converter_Observability::log_event( 'run_rejected_invalid_payload', array( 'reason' => 'payload_not_object' ) );
			return Audio_Converter_Ability_Contract::error_response( 'invalid_input', 'Request body must be a JSON object.' );
		}

		$validation = Audio_Converter_Ability_Contract::validate_input( $payload );
		if ( is_wp_error( $validation ) ) {
			Audio_Converter_Observability::log_event(
				'run_rejected_invalid_input',
				array(
					'error_code'    => (string) $validation->get_error_code(),
					'error_message' => $validation->get_error_message(),
				)
			);
			return $validation;
		}

		$external_run_id = (string) $payload['external_run_id'];
		$run_id          = Audio_Converter_Job_Store::find_or_create_run( $external_run_id );
		$status          = Audio_Converter_Job_Store::get_status( $run_id );

		Audio_Converter_Observability::log_lifecycle(
			'run_received',
			$run_id,
			$external_run_id,
			array(
				'status' => $status,
			)
		);

		if ( 'completed' === $status ) {
			Audio_Converter_Observability::log_lifecycle( 'run_replayed_completed', $run_id, $external_run_id, array( 'status' => 'completed' ) );
			return Audio_Converter_Job_Store::get_completed_response( $run_id );
		}

		if ( 'processing' === $status ) {
			Audio_Converter_Observability::log_lifecycle( 'run_replayed_processing', $run_id, $external_run_id, array( 'status' => 'processing' ) );
			return Audio_Converter_Job_Store::get_processing_response( $run_id );
		}

		if ( 'failed' === $status ) {
			Audio_Converter_Observability::log_lifecycle( 'run_replayed_failed', $run_id, $external_run_id, array( 'status' => 'failed' ) );
			return Audio_Converter_Job_Store::get_failed_response( $run_id );
		}

		$lock_ok = Audio_Converter_Idempotency_Lock::acquire( $external_run_id );
		if ( ! $lock_ok ) {
			Audio_Converter_Observability::log_lifecycle( 'run_duplicate_lock', $run_id, $external_run_id, array( 'status' => 'processing' ) );
			return Audio_Converter_Ability_Contract::error_response( 'duplicate_run', 'A run with the same external_run_id is already processing.' );
		}

		Audio_Converter_Job_Store::mark_processing( $run_id );
		Audio_Converter_Observability::log_lifecycle( 'run_processing_started', $run_id, $external_run_id, array( 'status' => 'processing' ) );

		try {
			$structured = Audio_Converter_AI_Processor::transcribe_and_structure( $payload );
			if ( is_wp_error( $structured ) ) {
				return self::mark_failed_with_observability( $run_id, $external_run_id, (string) $structured->get_error_code(), $structured->get_error_message() );
			}

			$normalized = Audio_Converter_Normalizer::normalize_structured_post( $structured );
			if ( '' === $normalized['title'] && empty( $normalized['sections'] ) ) {
				return self::mark_failed_with_observability( $run_id, $external_run_id, 'ai_provider_unavailable', 'AI returned empty structured content.' );
			}

			$blocks        = Audio_Converter_Block_Mapper::sections_to_blocks( $normalized['sections'] );
			$quality_flags = self::evaluate_quality_flags( $payload, $normalized );

			$publish_options = isset( $payload['publish_options'] ) && is_array( $payload['publish_options'] ) ? $payload['publish_options'] : array();
			$published       = Audio_Converter_Publisher::create_draft_from_blocks( $normalized['title'], $blocks, $publish_options );
			if ( is_wp_error( $published ) ) {
				return self::mark_failed_with_observability( $run_id, $external_run_id, (string) $published->get_error_code(), $published->get_error_message() );
			}

			$completed_response = Audio_Converter_Job_Store::mark_completed(
				$run_id,
				(int) $published['post_id'],
				(string) $published['post_url'],
				$quality_flags,
				array(
					'title'                 => $normalized['title'],
					'blocks'                => $blocks,
					'updated_existing_post' => ! empty( $published['updated_existing_post'] ),
				)
			);

			Audio_Converter_Observability::log_lifecycle(
				'run_completed',
				$run_id,
				$external_run_id,
				array(
					'status'             => 'completed',
					'post_id'            => (int) $published['post_id'],
					'updated_existing_post' => ! empty( $published['updated_existing_post'] ),
					'debug_reference_id' => self::debug_reference_id_from_response( $completed_response ),
				)
			);

			return $completed_response;
		} catch ( Throwable $e ) {
			Audio_Converter_Observability::log_lifecycle(
				'run_failed_exception',
				$run_id,
				$external_run_id,
				array(
					'status'        => 'failed',
					'error_code'    => 'internal_error',
					'error_message' => $e->getMessage(),
				)
			);

			return self::mark_failed_with_observability( $run_id, $external_run_id, 'internal_error', 'Unexpected runtime error.' );
		} finally {
			Audio_Converter_Idempotency_Lock::release( $external_run_id );
			Audio_Converter_Observability::log_lifecycle( 'run_lock_released', $run_id, $external_run_id );
		}
	}
}
