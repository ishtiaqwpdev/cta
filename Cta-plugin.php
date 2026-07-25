<?php
/**
 * Plugin Name: CTA Academy LMS
 * Plugin URI: https://clinicaltrainingacademy.com
 * Description: Complete LMS platform for Clinical Training and Supervision Academy.
 * Version: 1.0.61
 * Author: David James
 * Author URI: https://clinicaltrainingacademy.com
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cta-lms
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'CTA Academy LMS requires PHP 7.4 or higher.', 'cta-lms' );
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Deactivate known legacy CTA LMS installs without blocking boot.
 */
function cta_academy_deactivate_legacy_plugins() {
	if ( ! function_exists( 'deactivate_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$self = plugin_basename( __FILE__ );

	$legacy = array(
		'cta-lms/Cta-plugin.php',
		'cta-lms/cta-lms.php',
		'cta-lms/cta-plugin.php',
		'cta-design/Cta-plugin.php',
		'cta-design/cta-lms.php',
		'cta-design/cta-plugin.php',
		'cta-lms-plugin/Cta-plugin.php',
		'cta-lms-plugin/cta-lms.php',
	);

	$to_deactivate = array();

	foreach ( $legacy as $path ) {
		if ( $path === $self ) {
			continue;
		}
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $path ) ) {
			$to_deactivate[] = $path;
		}
	}

	if ( empty( $to_deactivate ) ) {
		return;
	}

	try {
		deactivate_plugins( $to_deactivate, true );
	} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		// Never block site boot if deactivation fails.
	}
}

// Prefer deferred deactivation so plugin load cannot fatal the request.
if ( function_exists( 'add_action' ) ) {
	add_action( 'plugins_loaded', 'cta_academy_deactivate_legacy_plugins', 0 );
	add_action( 'admin_init', 'cta_academy_deactivate_legacy_plugins', 1 );
}

if ( defined( 'CTA_LMS_LOADED' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'Another CTA LMS copy is already loaded. Keep only CTA Academy LMS active and delete the old plugin folder.', 'cta-lms' );
			echo '</p></div>';
		}
	);
	return;
}

define( 'CTA_LMS_LOADED', true );
define( 'CTA_PLUGIN_FILE', __FILE__ );

$cta_bootstrap = __DIR__ . '/cta-lms.php';

if ( ! file_exists( $cta_bootstrap ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'CTA Academy LMS bootstrap file (cta-lms.php) is missing. Reinstall the plugin zip.', 'cta-lms' );
			echo '</p></div>';
		}
	);
	return;
}

require_once $cta_bootstrap;

if ( class_exists( 'CTA_Activator' ) ) {
	register_activation_hook( __FILE__, array( 'CTA_Activator', 'activate' ) );
}

if ( class_exists( 'CTA_Deactivator' ) ) {
	register_deactivation_hook( __FILE__, array( 'CTA_Deactivator', 'deactivate' ) );
}
