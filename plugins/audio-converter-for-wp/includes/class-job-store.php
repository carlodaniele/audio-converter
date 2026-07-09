<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Job_Store {
	private static function status_key( string $run_id ): string {
		return 'aicb_run_status_' . $run_id;
	}

	private static function response_key( string $run_id ): string {
		return 'aicb_run_response_' . $run_id;
	}

	public static function find_or_create_run( string $external_run_id ): string {
		$existing = get_transient( 'aicb_run_map_' . md5( $external_run_id ) );
		if ( is_string( $existing ) && '' !== $existing ) {
			return $existing;
		}

		$run_id = 'run_' . wp_generate_password( 12, false, false );
		set_transient( 'aicb_run_map_' . md5( $external_run_id ), $run_id, HOUR_IN_SECONDS );
		set_transient( self::status_key( $run_id ), 'pending', HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), Audio_Converter_Ability_Contract::base_response( $run_id, 'pending' ), HOUR_IN_SECONDS );

		return $run_id;
	}

	public static function get_status( string $run_id ): string {
		$status = get_transient( self::status_key( $run_id ) );
		if ( ! is_string( $status ) || '' === $status ) {
			return 'pending';
		}

		return $status;
	}

	public static function mark_processing( string $run_id ): void {
		$response = Audio_Converter_Ability_Contract::base_response( $run_id, 'processing' );
		set_transient( self::status_key( $run_id ), 'processing', HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), $response, HOUR_IN_SECONDS );
	}

	public static function mark_completed( string $run_id, int $post_id, string $post_url, array $quality_flags = array(), array $result_payload = array() ): array {
		$response                 = Audio_Converter_Ability_Contract::base_response( $run_id, 'completed' );
		$response['post_id']      = $post_id;
		$response['post_url']     = $post_url;
		$response['quality_flags'] = $quality_flags;

		if ( isset( $result_payload['title'] ) && is_string( $result_payload['title'] ) ) {
			$response['title'] = $result_payload['title'];
		}

		if ( isset( $result_payload['blocks'] ) && is_array( $result_payload['blocks'] ) ) {
			$response['blocks'] = $result_payload['blocks'];
		}

		$response['updated_existing_post'] = ! empty( $result_payload['updated_existing_post'] );

		set_transient( self::status_key( $run_id ), 'completed', HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), $response, HOUR_IN_SECONDS );

		return $response;
	}

	public static function mark_failed( string $run_id, string $code, string $message ): array {
		$base     = Audio_Converter_Ability_Contract::base_response( $run_id, 'failed' );
		$response = Audio_Converter_Ability_Contract::with_error( $base, $code, $message );

		set_transient( self::status_key( $run_id ), 'failed', HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), $response, HOUR_IN_SECONDS );

		return $response;
	}

	private static function get_stored_response( string $run_id, string $fallback_status ): array {
		$response = get_transient( self::response_key( $run_id ) );
		if ( ! is_array( $response ) ) {
			return Audio_Converter_Ability_Contract::base_response( $run_id, $fallback_status );
		}

		return $response;
	}

	public static function get_processing_response( string $run_id ): array {
		return self::get_stored_response( $run_id, 'processing' );
	}

	public static function get_completed_response( string $run_id ): array {
		return self::get_stored_response( $run_id, 'completed' );
	}

	public static function get_failed_response( string $run_id ): array {
		return self::get_stored_response( $run_id, 'failed' );
	}
}
