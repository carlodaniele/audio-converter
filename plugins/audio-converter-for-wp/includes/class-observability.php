<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Observability {
	public static function log_event( string $event, array $context = array() ): void {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
			return;
		}

		error_log(
			wp_json_encode(
				array(
					'component' => 'audio-converter-for-wp',
					'event'     => $event,
					'context'   => $context,
					'time'      => gmdate( 'c' ),
				)
			)
		);
	}
}
