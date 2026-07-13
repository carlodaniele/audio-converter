<?php
/**
 * Uninstall cleanup for Audio Converter for WP.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin settings and transient rows for the current site.
 */
function aicb_cleanup_current_site_data(): void {
	global $wpdb;

	delete_option( 'aicb_settings' );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}aicb_runs" );

	$transient_patterns = array(
		'_transient_aicb_%',
		'_transient_timeout_aicb_%',
	);

	foreach ( $transient_patterns as $pattern ) {
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
function aicb_cleanup_network_transients(): void {
	global $wpdb;

	if ( ! isset( $wpdb->sitemeta ) ) {
		return;
	}

	$site_transient_patterns = array(
		'_site_transient_aicb_%',
		'_site_transient_timeout_aicb_%',
	);

	foreach ( $site_transient_patterns as $pattern ) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
				$pattern
			)
		);
	}
}

if ( is_multisite() ) {
	$site_ids = get_sites(
		array(
			'fields' => 'ids',
		)
	);

	$current_blog_id = get_current_blog_id();

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( (int) $site_id );
		aicb_cleanup_current_site_data();
		restore_current_blog();
	}

	switch_to_blog( (int) $current_blog_id );
	aicb_cleanup_network_transients();
	restore_current_blog();
} else {
	aicb_cleanup_current_site_data();
}
