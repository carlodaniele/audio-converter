<?php
/**
 * Uninstall cleanup for Audio Converter.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin settings and transient rows for the current site.
 */
function audio_converter_cleanup_current_site_data(): void {
	global $wpdb;

	delete_option( 'aicb_settings' );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- uninstall intentionally removes plugin-owned table.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aicb_runs" );

	$transient_patterns = array(
		'_transient_aicb_%',
		'_transient_timeout_aicb_%',
	);

	foreach ( $transient_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall intentionally removes plugin-owned transient rows.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
	}
}

/**
 * Remove network-level transient rows (defensive cleanup for multisite).
 */
function audio_converter_cleanup_network_transients(): void {
	global $wpdb;

	if ( ! isset( $wpdb->sitemeta ) ) {
		return;
	}

	$site_transient_patterns = array(
		'_site_transient_aicb_%',
		'_site_transient_timeout_aicb_%',
	);

	foreach ( $site_transient_patterns as $pattern ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall intentionally removes plugin-owned network transient rows.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
				$pattern
			)
		);
	}
}

if ( is_multisite() ) {
	$audio_converter_site_ids = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	$audio_converter_current_blog_id = get_current_blog_id();

	foreach ( $audio_converter_site_ids as $audio_converter_site_id ) {
		switch_to_blog( (int) $audio_converter_site_id );
		audio_converter_cleanup_current_site_data();
		restore_current_blog();
	}

	switch_to_blog( (int) $audio_converter_current_blog_id );
	audio_converter_cleanup_network_transients();
	restore_current_blog();
} else {
	audio_converter_cleanup_current_site_data();
}
