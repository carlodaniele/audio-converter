<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Ability_Contract {
	const CONTRACT_VERSION = '1.0.0';

	public static function validate_input( array $payload ) {
		if ( empty( $payload['contract_version'] ) || 0 !== strpos( (string) $payload['contract_version'], '1.' ) ) {
			return self::error_response( 'invalid_input', 'contract_version must be 1.x.' );
		}

		if ( empty( $payload['external_run_id'] ) || ! is_string( $payload['external_run_id'] ) ) {
			return self::error_response( 'invalid_input', 'external_run_id is required.' );
		}

		if ( empty( $payload['source'] ) || ! is_string( $payload['source'] ) ) {
			return self::error_response( 'invalid_input', 'source is required.' );
		}

		if ( empty( $payload['audio'] ) || ! is_array( $payload['audio'] ) ) {
			return self::error_response( 'invalid_input', 'audio object is required.' );
		}

		$audio       = $payload['audio'];
		$audio_modes = 0;
		$audio_modes += isset( $audio['media_id'] ) ? 1 : 0;
		$audio_modes += isset( $audio['signed_url'] ) ? 1 : 0;
		$audio_modes += isset( $audio['base64'] ) ? 1 : 0;

		if ( 1 !== $audio_modes ) {
			return self::error_response( 'invalid_input', 'audio must include exactly one input mode.' );
		}

		return true;
	}

	public static function error_response( string $code, string $message ): WP_Error {
		$status = 500;
		if ( 'invalid_input' === $code ) {
			$status = 400;
		} elseif ( 'unauthorized' === $code ) {
			$status = 403;
		} elseif ( 'duplicate_run' === $code ) {
			$status = 409;
		}

		return new WP_Error(
			$code,
			$message,
			array(
				'status' => $status,
				'error'  => array(
					'code'      => $code,
					'message'   => $message,
					'retryable' => in_array( $code, array( 'ai_provider_unavailable' ), true ),
				),
			)
		);
	}

	public static function base_response( string $run_id, string $status ): array {
		$now = gmdate( 'c' );

		return array(
			'contract_version'      => self::CONTRACT_VERSION,
			'run_id'                => $run_id,
			'status'                => $status,
			'quality_flags'         => array(),
			'processing_timestamps' => array(
				'started_at'   => $now,
				'completed_at' => ( 'completed' === $status || 'failed' === $status ) ? $now : null,
			),
			'debug_reference_id'    => 'dbg_' . wp_generate_password( 12, false, false ),
		);
	}

	public static function with_error( array $response, string $code, string $message ): array {
		$response['status'] = 'failed';
		$response['error']  = array(
			'code'      => $code,
			'message'   => $message,
			'retryable' => in_array( $code, array( 'ai_provider_unavailable' ), true ),
		);

		if ( isset( $response['processing_timestamps'] ) && is_array( $response['processing_timestamps'] ) ) {
			$response['processing_timestamps']['completed_at'] = gmdate( 'c' );
		}

		return $response;
	}
}
