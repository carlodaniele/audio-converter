<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_AI_Processor {
	private const AI_MAX_RETRY_ATTEMPTS = 4;
	private const AI_RETRY_BASE_DELAY_MICROSECONDS = 750000;
	private const SIGNED_URL_FETCH_TIMEOUT_SECONDS = 45;
	private const FREE_DEFAULT_TEMPERATURE = 0.3;
	private const MIN_TEMPERATURE          = 0.0;
	private const MAX_TEMPERATURE          = 1.0;

	private static function ai_error( string $code, string $message ) {
		return Audio_Converter_Ability_Contract::error_response( $code, $message );
	}

	private static function is_timeout_error( WP_Error $error ): bool {
		$message = strtolower( $error->get_error_message() );

		if ( false !== strpos( $message, 'curl error 28' ) ) {
			return true;
		}

		return false !== strpos( $message, 'timed out' );
	}

	private static function generate_text_with_retry( $builder, string $stage ) {
		$last_error = null;

		for ( $attempt = 1; $attempt <= self::AI_MAX_RETRY_ATTEMPTS; $attempt++ ) {
			$result = $builder->generate_text();

			if ( ! is_wp_error( $result ) ) {
				return $result;
			}

			$last_error = $result;

			if ( ! self::is_timeout_error( $result ) || $attempt >= self::AI_MAX_RETRY_ATTEMPTS ) {
				break;
			}

			Audio_Converter_Observability::log_event(
				'ai_timeout_retry',
				array(
					'stage'   => $stage,
					'attempt' => $attempt,
					'error'   => $result->get_error_message(),
				)
			);

			usleep( self::AI_RETRY_BASE_DELAY_MICROSECONDS * $attempt );
		}

		if ( $last_error instanceof WP_Error && self::is_timeout_error( $last_error ) ) {
			return self::ai_error(
				'ai_provider_unavailable',
				'AI provider timeout while ' . $stage . '. Please retry in a few seconds.'
			);
		}

		if ( $last_error instanceof WP_Error ) {
			return self::ai_error( 'ai_provider_unavailable', $last_error->get_error_message() );
		}

		return self::ai_error( 'ai_provider_unavailable', 'AI provider failed without a detailed error.' );
	}

	private static function resolve_from_media_id( int $media_id ) {
		$mime_type = (string) get_post_mime_type( $media_id );
		if ( '' === $mime_type || 0 !== strpos( $mime_type, 'audio/' ) ) {
			return self::ai_error( 'invalid_input', 'media_id must reference an audio attachment.' );
		}

		$path = get_attached_file( $media_id );
		if ( ! is_string( $path ) || '' === $path || ! file_exists( $path ) ) {
			return self::ai_error( 'invalid_input', 'Audio attachment file not found on server.' );
		}

		$bytes = file_get_contents( $path );
		if ( false === $bytes ) {
			return self::ai_error( 'ai_provider_unavailable', 'Unable to read audio attachment bytes.' );
		}

		return array(
			'base64'    => base64_encode( $bytes ),
			'mime_type' => $mime_type,
		);
	}

	private static function resolve_from_signed_url( string $signed_url ) {
		$response = wp_remote_get(
			$signed_url,
			array(
				'timeout' => self::SIGNED_URL_FETCH_TIMEOUT_SECONDS,
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::ai_error( 'ai_provider_unavailable', 'Unable to fetch signed_url audio payload.' );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return self::ai_error( 'ai_provider_unavailable', 'signed_url returned non-success HTTP status.' );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( ! is_string( $body ) || '' === $body ) {
			return self::ai_error( 'ai_provider_unavailable', 'signed_url response body is empty.' );
		}

		$mime_type = (string) wp_remote_retrieve_header( $response, 'content-type' );
		if ( '' === $mime_type || 0 !== strpos( $mime_type, 'audio/' ) ) {
			$mime_type = 'audio/mpeg';
		}

		return array(
			'base64'    => base64_encode( $body ),
			'mime_type' => $mime_type,
		);
	}

	private static function resolve_from_base64( array $audio ) {
		$base64    = isset( $audio['base64'] ) ? (string) $audio['base64'] : '';
		$mime_type = isset( $audio['mime_type'] ) ? (string) $audio['mime_type'] : '';

		if ( '' === $base64 || '' === $mime_type ) {
			return self::ai_error( 'invalid_input', 'base64 audio mode requires base64 and mime_type.' );
		}

		if ( 0 !== strpos( $mime_type, 'audio/' ) ) {
			return self::ai_error( 'invalid_input', 'mime_type must start with audio/.' );
		}

		$decoded = base64_decode( $base64, true );
		if ( false === $decoded || '' === $decoded ) {
			return self::ai_error( 'invalid_input', 'Invalid base64 audio payload.' );
		}

		return array(
			'base64'    => $base64,
			'mime_type' => $mime_type,
		);
	}

	private static function resolve_audio_payload( array $payload ) {
		$audio = $payload['audio'];

		if ( isset( $audio['media_id'] ) ) {
			return self::resolve_from_media_id( (int) $audio['media_id'] );
		}

		if ( isset( $audio['signed_url'] ) ) {
			return self::resolve_from_signed_url( (string) $audio['signed_url'] );
		}

		return self::resolve_from_base64( $audio );
	}

	private static function output_language( array $payload ): string {
		if ( isset( $payload['editorial_options']['language'] ) && is_string( $payload['editorial_options']['language'] ) ) {
			return $payload['editorial_options']['language'];
		}

		if ( function_exists( 'get_locale' ) ) {
			return (string) get_locale();
		}

		return 'en-US';
	}

	private static function audio_context_hint( array $payload ): string {
		if ( isset( $payload['audio']['media_id'] ) ) {
			return 'Audio source: WordPress media library attachment.';
		}

		if ( isset( $payload['audio']['signed_url'] ) ) {
			return 'Audio source: signed URL attachment.';
		}

		return 'Audio source: base64 payload.';
	}

	private static function target_length_range( array $payload ): array {
		$target = isset( $payload['editorial_options']['target_length'] ) ? (string) $payload['editorial_options']['target_length'] : 'medium';

		if ( 'short' === $target ) {
			return array(
				'mode' => 'short',
				'min'  => 250,
				'max'  => 450,
			);
		}

		if ( 'long' === $target ) {
			return array(
				'mode' => 'long',
				'min'  => 900,
				'max'  => 1400,
			);
		}

		return array(
			'mode' => 'medium',
			'min'  => 500,
			'max'  => 850,
		);
	}

	private static function structured_word_count( array $structured ): int {
		$parts = array();

		if ( isset( $structured['title'] ) && is_string( $structured['title'] ) ) {
			$parts[] = $structured['title'];
		}

		if ( isset( $structured['sections'] ) && is_array( $structured['sections'] ) ) {
			foreach ( $structured['sections'] as $section ) {
				if ( ! is_array( $section ) ) {
					continue;
				}

				if ( isset( $section['heading'] ) && is_string( $section['heading'] ) ) {
					$parts[] = $section['heading'];
				}

				if ( isset( $section['paragraphs'] ) && is_array( $section['paragraphs'] ) ) {
					foreach ( $section['paragraphs'] as $paragraph ) {
						if ( is_string( $paragraph ) ) {
							$parts[] = $paragraph;
						}
					}
				}

				if ( isset( $section['bullet_points'] ) && is_array( $section['bullet_points'] ) ) {
					foreach ( $section['bullet_points'] as $bullet ) {
						if ( is_string( $bullet ) ) {
							$parts[] = $bullet;
						}
					}
				}
			}
		}

		$text = trim( preg_replace( '/\s+/', ' ', implode( ' ', $parts ) ) );
		if ( '' === $text ) {
			return 0;
		}

		return count( preg_split( '/\s+/', $text ) );
	}

	private static function decode_structured_json( $structured_json ) {
		$structured = json_decode( (string) $structured_json, true );
		if ( ! is_array( $structured ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'ai_provider_unavailable', 'Could not parse AI structured output.' );
		}

		return $structured;
	}

	private static function is_pro_temperature_enabled(): bool {
		return (bool) apply_filters( 'audio_converter_pro_temperature_enabled', false );
	}

	private static function clamp_temperature( float $value ): float {
		if ( $value < self::MIN_TEMPERATURE ) {
			return self::MIN_TEMPERATURE;
		}

		if ( $value > self::MAX_TEMPERATURE ) {
			return self::MAX_TEMPERATURE;
		}

		return $value;
	}

	private static function generation_temperature( array $payload ): float {
		$default = (float) self::FREE_DEFAULT_TEMPERATURE;

		if ( ! self::is_pro_temperature_enabled() ) {
			return $default;
		}

		$requested = $default;
		if ( isset( $payload['editorial_options']['temperature'] ) && is_numeric( $payload['editorial_options']['temperature'] ) ) {
			$requested = (float) $payload['editorial_options']['temperature'];
		}

		return self::clamp_temperature( $requested );
	}

	private static function temperature_observability_context( array $payload, float $effective ): array {
		$pro_enabled   = self::is_pro_temperature_enabled();
		$has_requested = isset( $payload['editorial_options']['temperature'] ) && is_numeric( $payload['editorial_options']['temperature'] );
		$requested     = $has_requested ? (float) $payload['editorial_options']['temperature'] : (float) self::FREE_DEFAULT_TEMPERATURE;

		return array(
			'mode'              => $pro_enabled ? 'extended' : 'standard',
			'pro_enabled'       => $pro_enabled,
			'has_requested'     => $has_requested,
			'requested'         => $requested,
			'effective'         => $effective,
			'external_run_id'   => isset( $payload['external_run_id'] ) ? (string) $payload['external_run_id'] : '',
		);
	}

	public static function transcribe_and_structure( array $payload ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return Audio_Converter_Ability_Contract::error_response( 'ai_provider_unavailable', 'WordPress AI Client is not available.' );
		}

		$audio_payload = self::resolve_audio_payload( $payload );
		if ( is_wp_error( $audio_payload ) ) {
			return $audio_payload;
		}

		$language = self::output_language( $payload );
		$hint     = self::audio_context_hint( $payload );
		$length   = self::target_length_range( $payload );
		$generation_temperature = self::generation_temperature( $payload );

		Audio_Converter_Observability::log_event(
			'ai_generation_temperature_resolved',
			self::temperature_observability_context( $payload, $generation_temperature )
		);

		$transcription_builder = wp_ai_client_prompt( "Transcribe this audio note accurately in {$language}. Return plain text only." )
			->with_file( $audio_payload['base64'], $audio_payload['mime_type'] )
			->using_temperature( 0.1 );

		if ( method_exists( $transcription_builder, 'is_supported_for_text_generation' ) && ! $transcription_builder->is_supported_for_text_generation() ) {
			return self::ai_error( 'ai_provider_unavailable', 'No configured AI model supports this audio transcription request.' );
		}

		$transcript = self::generate_text_with_retry( $transcription_builder, 'transcribing audio' );
		if ( is_wp_error( $transcript ) ) {
			return $transcript;
		}

		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'title'    => array( 'type' => 'string' ),
				'sections' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'heading'       => array( 'type' => 'string' ),
							'level'         => array( 'type' => 'integer' ),
							'paragraphs'    => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
							'bullet_points' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						),
						'required'   => array( 'heading', 'level', 'paragraphs' ),
					),
				),
			),
			'required'   => array( 'title', 'sections' ),
		);

		$prompt =
			"Create a concise blog-ready draft in {$language}.\n" .
			"Target length mode: {$length['mode']}.\n" .
			"Required word count range: {$length['min']} to {$length['max']} words.\n" .
			"Hard requirement: produce at least {$length['min']} words.\n" .
			"Return JSON with title and sections.\n" .
			"Each section must include heading, level (2 or 3), paragraphs, optional bullet_points.\n" .
			"{$hint}\n" .
			"Use factual and clear writing.\n" .
			"Do not add fabricated facts.\n\n" .
			"Transcript:\n" . (string) $transcript;

		$structured_builder = wp_ai_client_prompt( $prompt )
			->using_temperature( $generation_temperature )
			->as_json_response( $schema );

		$structured_json = self::generate_text_with_retry( $structured_builder, 'generating structured draft' );

		if ( is_wp_error( $structured_json ) ) {
			return $structured_json;
		}

		$structured = self::decode_structured_json( $structured_json );
		if ( is_wp_error( $structured ) ) {
			return $structured;
		}

		$current_word_count = self::structured_word_count( $structured );
		if ( $current_word_count < $length['min'] ) {
			$expansion_prompt =
				"Expand and rewrite the following draft in {$language}.\n" .
				"Required word count range: {$length['min']} to {$length['max']} words.\n" .
				"Current draft is about {$current_word_count} words, so it is too short.\n" .
				"Keep facts consistent with the transcript and avoid fabricated details.\n" .
				"Return JSON with the same schema: title and sections (heading, level, paragraphs, optional bullet_points).\n\n" .
				"Transcript:\n" . (string) $transcript . "\n\n" .
				"Current draft JSON:\n" . wp_json_encode( $structured );

			$expanded_builder = wp_ai_client_prompt( $expansion_prompt )
				->using_temperature( $generation_temperature )
				->as_json_response( $schema );

			$expanded_json = self::generate_text_with_retry( $expanded_builder, 'expanding structured draft to target length' );
			if ( ! is_wp_error( $expanded_json ) ) {
				$expanded_structured = self::decode_structured_json( $expanded_json );
				if ( ! is_wp_error( $expanded_structured ) ) {
					$structured = $expanded_structured;
				}
			}
		}

		return $structured;
	}
}
