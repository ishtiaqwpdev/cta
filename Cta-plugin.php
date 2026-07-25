<?php
/**
 * Plugin Name: CTA Academy LMS
 * Plugin URI: https://clinicaltrainingacademy.com
 * Description: Complete LMS platform for Clinical Training and Supervision Academy.
 * Version: 1.0.63
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

// Must be first: block duplicate copies from fatalling the site.
if ( defined( 'CTA_LMS_LOADED' ) ) {
	return;
}
define( 'CTA_LMS_LOADED', true );

/**
 * EMERGENCY SAFE MODE
 *
 * If this file exists, the plugin loads NOTHING (no DB upgrades, no shortcodes).
 * That restores a white-screen / timeout site caused by a stuck CTA boot path.
 *
 * After the site is healthy: delete DISABLE_CTA_LMS_BOOT from this plugin folder
 * (or deploy the next release that removes it) to turn the LMS back on.
 */
if ( file_exists( __DIR__ . '/DISABLE_CTA_LMS_BOOT' ) && ! ( defined( 'CTA_LMS_FULL_BOOT' ) && CTA_LMS_FULL_BOOT ) ) {
	add_action(
		'plugins_loaded',
		static function () {
			// Best-effort cleanup of stuck upgrade locks (ignore failures).
			if ( function_exists( 'delete_transient' ) ) {
				delete_transient( 'cta_lms_upgrading' );
			}
			if ( function_exists( 'delete_option' ) ) {
				delete_option( 'cta_lms_pending_upgrade_from' );
			}
		},
		0
	);

	add_action(
		'admin_notices',
		static function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-warning"><p><strong>CTA LMS safe mode is ON.</strong> ';
			echo esc_html__( 'The plugin is intentionally idle so the website can load. Delete the file DISABLE_CTA_LMS_BOOT inside the plugin folder (or add define( \'CTA_LMS_FULL_BOOT\', true ); to wp-config.php) to re-enable the LMS.', 'cta-lms' );
			echo '</p></div>';
		}
	);

	return;
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

if ( ! function_exists( 'cta_academy_deactivate_legacy_plugins' ) ) {
	/**
	 * Deactivate known duplicate CTA LMS installs (admin only).
	 */
	function cta_academy_deactivate_legacy_plugins() {
		if ( ! is_admin() || ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$self = defined( 'CTA_PLUGIN_BASENAME' ) ? CTA_PLUGIN_BASENAME : plugin_basename( __FILE__ );

		$legacy = array(
			'cta-lms/Cta-plugin.php',
			'cta-lms/cta-lms.php',
			'cta-lms/cta-plugin.php',
			'cta-design/Cta-plugin.php',
			'cta-design/cta-lms.php',
			'cta-design/cta-plugin.php',
			'cta-lms-plugin/Cta-plugin.php',
			'cta-lms-plugin/cta-lms.php',
			'cta-academy-lms/Cta-plugin.php',
			'cta/Cta-plugin.php',
			'cta/cta-lms.php',
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
			// Never block admin.
		}
	}
}

add_action( 'admin_init', 'cta_academy_deactivate_legacy_plugins', 1 );

define( 'CTA_PLUGIN_FILE', __FILE__ );

$cta_bootstrap = __DIR__ . '/cta-lms.php';

if ( ! file_exists( $cta_bootstrap ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>';
			echo esc_html__( 'CTA Academy LMS bootstrap file (cta-lms.php) is missing. Reinstall the plugin.', 'cta-lms' );
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
