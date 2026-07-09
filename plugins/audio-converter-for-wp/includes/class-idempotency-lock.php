<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Idempotency_Lock {
	public static function acquire( string $external_run_id ): bool {
		$key = 'aicb_lock_' . md5( $external_run_id );
		if ( get_transient( $key ) ) {
			return false;
		}

		set_transient( $key, 1, 120 );
		return true;
	}

	public static function release( string $external_run_id ): void {
		$key = 'aicb_lock_' . md5( $external_run_id );
		delete_transient( $key );
	}
}
