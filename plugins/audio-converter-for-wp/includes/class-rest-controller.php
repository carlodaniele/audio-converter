<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_REST_Controller {
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

		if ( ! is_array( $payload ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'invalid_input', 'Request body must be a JSON object.' );
		}

		$validation = Audio_Converter_Ability_Contract::validate_input( $payload );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$external_run_id = (string) $payload['external_run_id'];
		$run_id          = Audio_Converter_Job_Store::find_or_create_run( $external_run_id );
		$status          = Audio_Converter_Job_Store::get_status( $run_id );

		if ( 'completed' === $status ) {
			return Audio_Converter_Job_Store::get_completed_response( $run_id );
		}

		if ( 'processing' === $status ) {
			return Audio_Converter_Job_Store::get_processing_response( $run_id );
		}

		if ( 'failed' === $status ) {
			return Audio_Converter_Job_Store::get_failed_response( $run_id );
		}

		$lock_ok = Audio_Converter_Idempotency_Lock::acquire( $external_run_id );
		if ( ! $lock_ok ) {
			return Audio_Converter_Ability_Contract::error_response( 'duplicate_run', 'A run with the same external_run_id is already processing.' );
		}

		Audio_Converter_Job_Store::mark_processing( $run_id );
		Audio_Converter_Observability::log_event( 'run_processing_started', array( 'run_id' => $run_id, 'external_run_id' => $external_run_id ) );

		try {
			$structured = Audio_Converter_AI_Processor::transcribe_and_structure( $payload );
			if ( is_wp_error( $structured ) ) {
				return Audio_Converter_Job_Store::mark_failed( $run_id, (string) $structured->get_error_code(), $structured->get_error_message() );
			}

			$normalized = Audio_Converter_Normalizer::normalize_structured_post( $structured );
			if ( '' === $normalized['title'] && empty( $normalized['sections'] ) ) {
				return Audio_Converter_Job_Store::mark_failed( $run_id, 'ai_provider_unavailable', 'AI returned empty structured content.' );
			}

			$blocks     = Audio_Converter_Block_Mapper::sections_to_blocks( $normalized['sections'] );
			$quality_flags = self::evaluate_quality_flags( $payload, $normalized );

			$publish_options = isset( $payload['publish_options'] ) && is_array( $payload['publish_options'] ) ? $payload['publish_options'] : array();
			$published       = Audio_Converter_Publisher::create_draft_from_blocks( $normalized['title'], $blocks, $publish_options );
			if ( is_wp_error( $published ) ) {
				return Audio_Converter_Job_Store::mark_failed( $run_id, (string) $published->get_error_code(), $published->get_error_message() );
			}

			Audio_Converter_Observability::log_event(
				'run_completed',
				array(
					'run_id'          => $run_id,
					'external_run_id' => $external_run_id,
					'post_id'         => (int) $published['post_id'],
				)
			);

			return Audio_Converter_Job_Store::mark_completed(
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
		} catch ( Throwable $e ) {
			Audio_Converter_Observability::log_event(
				'run_failed_exception',
				array(
					'run_id'          => $run_id,
					'external_run_id' => $external_run_id,
					'error'           => $e->getMessage(),
				)
			);

			return Audio_Converter_Job_Store::mark_failed( $run_id, 'internal_error', 'Unexpected runtime error.' );
		} finally {
			Audio_Converter_Idempotency_Lock::release( $external_run_id );
		}
	}
}
