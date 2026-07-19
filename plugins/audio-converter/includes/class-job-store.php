<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Audio_Converter_Job_Store {
	const TABLE_SUFFIX = 'aicb_runs';

	private static $table_ready = null;

	private static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			run_id varchar(64) NOT NULL,
			external_run_id varchar(191) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			response_json longtext NULL,
			last_error_code varchar(100) NULL,
			last_error_message text NULL,
			started_at datetime NULL,
			completed_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY run_id (run_id),
			UNIQUE KEY external_run_id (external_run_id),
			KEY status (status)
		) {$charset_collate};";

		dbDelta( $sql );

		self::$table_ready = self::table_exists();
	}

	private static function table_exists(): bool {
		global $wpdb;

		$table_name = self::table_name();
		$found      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		return is_string( $found ) && $table_name === $found;
	}

	private static function ensure_table(): bool {
		if ( true === self::$table_ready ) {
			return true;
		}

		if ( false === self::$table_ready ) {
			return false;
		}

		if ( self::table_exists() ) {
			self::$table_ready = true;

			return true;
		}

		self::install();

		return true === self::$table_ready;
	}

	private static function now_mysql_utc(): string {
		return current_time( 'mysql', true );
	}

	private static function map_key( string $external_run_id ): string {
		return 'aicb_run_map_' . md5( $external_run_id );
	}

	private static function status_key( string $run_id ): string {
		return 'aicb_run_status_' . $run_id;
	}

	private static function response_key( string $run_id ): string {
		return 'aicb_run_response_' . $run_id;
	}

	private static function cache_run( string $external_run_id, string $run_id, string $status, array $response ): void {
		set_transient( self::map_key( $external_run_id ), $run_id, HOUR_IN_SECONDS );
		set_transient( self::status_key( $run_id ), $status, HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), $response, HOUR_IN_SECONDS );
	}

	private static function update_cache_without_external( string $run_id, string $status, array $response ): void {
		set_transient( self::status_key( $run_id ), $status, HOUR_IN_SECONDS );
		set_transient( self::response_key( $run_id ), $response, HOUR_IN_SECONDS );
	}

	private static function find_or_create_run_transient( string $external_run_id ): string {
		$existing = get_transient( self::map_key( $external_run_id ) );
		if ( is_string( $existing ) && '' !== $existing ) {
			return $existing;
		}

		$run_id   = 'run_' . wp_generate_password( 12, false, false );
		$response = Audio_Converter_Ability_Contract::base_response( $run_id, 'pending' );

		self::cache_run( $external_run_id, $run_id, 'pending', $response );

		return $run_id;
	}

	public static function find_or_create_run( string $external_run_id ): string {
		if ( ! self::ensure_table() ) {
			return self::find_or_create_run_transient( $external_run_id );
		}

		global $wpdb;

		$table_name = self::table_name();
		$existing   = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT run_id FROM {$table_name} WHERE external_run_id = %s LIMIT 1",
				$external_run_id
			)
		);

		if ( is_string( $existing ) && '' !== $existing ) {
			$status   = self::get_status( $existing );
			$response = self::get_stored_response( $existing, $status );
			self::cache_run( $external_run_id, $existing, $status, $response );

			return $existing;
		}

		$run_id = 'run_' . wp_generate_password( 12, false, false );
		$now    = self::now_mysql_utc();
		$base   = Audio_Converter_Ability_Contract::base_response( $run_id, 'pending' );

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'run_id'          => $run_id,
				'external_run_id' => $external_run_id,
				'status'          => 'pending',
				'response_json'   => wp_json_encode( $base ),
				'started_at'      => $now,
				'completed_at'    => null,
				'created_at'      => $now,
				'updated_at'      => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			$existing_after_race = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT run_id FROM {$table_name} WHERE external_run_id = %s LIMIT 1",
					$external_run_id
				)
			);

			if ( is_string( $existing_after_race ) && '' !== $existing_after_race ) {
				$status   = self::get_status( $existing_after_race );
				$response = self::get_stored_response( $existing_after_race, $status );
				self::cache_run( $external_run_id, $existing_after_race, $status, $response );

				return $existing_after_race;
			}

			return self::find_or_create_run_transient( $external_run_id );
		}

		self::cache_run( $external_run_id, $run_id, 'pending', $base );

		return $run_id;
	}

	public static function get_status( string $run_id ): string {
		if ( self::ensure_table() ) {
			global $wpdb;

			$table_name = self::table_name();
			$status     = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT status FROM {$table_name} WHERE run_id = %s LIMIT 1",
					$run_id
				)
			);

			if ( is_string( $status ) && '' !== $status ) {
				return $status;
			}
		}

		$status = get_transient( self::status_key( $run_id ) );
		if ( ! is_string( $status ) || '' === $status ) {
			return 'pending';
		}

		return $status;
	}

	public static function mark_processing( string $run_id ): void {
		$response = Audio_Converter_Ability_Contract::base_response( $run_id, 'processing' );
		if ( self::ensure_table() ) {
			global $wpdb;

			$table_name = self::table_name();
			$wpdb->update(
				$table_name,
				array(
					'status'        => 'processing',
					'response_json' => wp_json_encode( $response ),
					'updated_at'    => self::now_mysql_utc(),
				),
				array( 'run_id' => $run_id ),
				array( '%s', '%s', '%s' ),
				array( '%s' )
			);
		}

		self::update_cache_without_external( $run_id, 'processing', $response );
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

		if ( self::ensure_table() ) {
			global $wpdb;

			$table_name = self::table_name();
			$now        = self::now_mysql_utc();

			$wpdb->update(
				$table_name,
				array(
					'status'          => 'completed',
					'response_json'   => wp_json_encode( $response ),
					'last_error_code' => null,
					'last_error_message' => null,
					'completed_at'    => $now,
					'updated_at'      => $now,
				),
				array( 'run_id' => $run_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
		}

		self::update_cache_without_external( $run_id, 'completed', $response );

		return $response;
	}

	public static function mark_failed( string $run_id, string $code, string $message ): array {
		$base     = Audio_Converter_Ability_Contract::base_response( $run_id, 'failed' );
		$response = Audio_Converter_Ability_Contract::with_error( $base, $code, $message );
		$now      = self::now_mysql_utc();

		if ( self::ensure_table() ) {
			global $wpdb;

			$table_name = self::table_name();
			$wpdb->update(
				$table_name,
				array(
					'status'             => 'failed',
					'response_json'      => wp_json_encode( $response ),
					'last_error_code'    => $code,
					'last_error_message' => $message,
					'completed_at'       => $now,
					'updated_at'         => $now,
				),
				array( 'run_id' => $run_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%s' )
			);
		}

		self::update_cache_without_external( $run_id, 'failed', $response );

		return $response;
	}

	private static function get_stored_response( string $run_id, string $fallback_status ): array {
		if ( self::ensure_table() ) {
			global $wpdb;

			$table_name     = self::table_name();
			$response_json  = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT response_json FROM {$table_name} WHERE run_id = %s LIMIT 1",
					$run_id
				)
			);

			if ( is_string( $response_json ) && '' !== $response_json ) {
				$decoded = json_decode( $response_json, true );
				if ( is_array( $decoded ) ) {
					return $decoded;
				}
			}
		}

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
