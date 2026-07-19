<?php
/**
 * Plugin Name: Audio Converter
 * Description: Ability-first audio-to-post pipeline skeleton for WordPress.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Carlo Daniele
 * License: GPL-2.0-or-later
 * Text Domain: audio-converter
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-ability-contract.php';
require_once __DIR__ . '/includes/class-job-store.php';
require_once __DIR__ . '/includes/class-idempotency-lock.php';
require_once __DIR__ . '/includes/class-ai-processor.php';
require_once __DIR__ . '/includes/class-normalizer.php';
require_once __DIR__ . '/includes/class-block-mapper.php';
require_once __DIR__ . '/includes/class-publisher.php';
require_once __DIR__ . '/includes/class-observability.php';
require_once __DIR__ . '/includes/class-rest-controller.php';
require_once __DIR__ . '/includes/class-audio-converter-plugin.php';

register_activation_hook( __FILE__, array( 'Audio_Converter_Job_Store', 'install' ) );

Audio_Converter_Plugin::init();
