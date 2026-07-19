<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Observability {
	private static function clean_context( array $context ): array {
		foreach ( $context as $key => $value ) {
			if ( null === $value ) {
				unset( $context[ $key ] );
			}
		}

		return $context;
	}

	public static function run_context( string $run_id, string $external_run_id, array $extra = array() ): array {
		$base = array(
			'run_id'            => $run_id,
			'external_run_id'   => $external_run_id,
			'status'            => null,
			'error_code'        => null,
			'retryable_reason'  => null,
			'attempt'           => null,
			'max_attempts'      => null,
			'debug_reference_id' => null,
		);

		return self::clean_context( array_merge( $base, $extra ) );
	}

	public static function log_lifecycle( string $event, string $run_id, string $external_run_id, array $extra = array() ): void {
		self::log_event( $event, self::run_context( $run_id, $external_run_id, $extra ) );
	}

	public static function log_event( string $event, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- runtime observability is intentionally written only when WP_DEBUG_LOG is enabled.
		error_log(
			wp_json_encode(
				array(
					'component' => 'audio-converter',
					'event'     => $event,
					'context'   => $context,
					'time'      => gmdate( 'c' ),
				)
			)
		);
	}
}
