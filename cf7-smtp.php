<?php
/**
 * SMTP for Contact From 7
 * A trustworthy SMTP plugin for Contact Form 7. Simple and useful.
 *
 * Plugin Name:     SMTP for Contact From 7
 * Plugin URI:      https://wordpress.org/plugins/cf7-smtp
 * Description:     A trustworthy SMTP plugin for Contact Form 7. Simple and useful.
 * Version:         1.0.0
 * Author:          codekraft
 * Contributors:    gardenboi
 * Author URI:      https://modul-r.codekraft.it/
 * Text Domain:     cf7-smtp
 * License:         GPL 2.0+
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.txt
 * Domain Path:     /languages
 * Requires PHP:    7.1
 * WordPress-Plugin-Boilerplate-Powered: v3.3.0
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'CF7_SMTP_NAME', 'Contact Form 7 - SMTP' );
define( 'CF7_SMTP_TEXTDOMAIN', 'cf7-smtp' );
define( 'CF7_SMTP_MIN_PHP_VERSION', '7.1' );
define( 'CF7_SMTP_VERSION', '1.0.0' );

define( 'CF7_SMTP_PLUGIN_ROOT', plugin_dir_path( __FILE__ ) );
define( 'CF7_SMTP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );


if ( ! defined( 'CF7_SMTP_HOST' ) ) {
	define( 'CF7_SMTP_HOST', null );
}
if ( ! defined( 'CF7_SMTP_PORT' ) ) {
	define( 'CF7_SMTP_PORT', null );
}
if ( ! defined( 'CF7_SMTP_AUTH' ) ) {
	define( 'CF7_SMTP_AUTH', null );
}
if ( ! defined( 'CF7_SMTP_USER_NAME' ) ) {
	define( 'CF7_SMTP_USER_NAME', null );
}
if ( ! defined( 'CF7_SMTP_USER_PASS' ) ) {
	define( 'CF7_SMTP_USER_PASS', null );
}
if ( ! defined( 'CF7_SMTP_FROM_MAIL' ) ) {
	define( 'CF7_SMTP_FROM_MAIL', null );
}
if ( ! defined( 'CF7_SMTP_FROM_NAME' ) ) {
	define( 'CF7_SMTP_FROM_NAME', null );
}

if ( ! defined( 'CF7_SMTP_SETTINGS' ) ) {
	define(
		'CF7_SMTP_SETTINGS',
		array(
			'host'      => CF7_SMTP_HOST,
			'port'      => CF7_SMTP_PORT,
			'auth'      => CF7_SMTP_AUTH,
			'user_name' => CF7_SMTP_USER_NAME,
			'user_pass' => CF7_SMTP_USER_PASS,
			'from_mail' => CF7_SMTP_FROM_MAIL,
			'from_name' => CF7_SMTP_FROM_NAME,
		)
	);
}

if ( version_compare( PHP_VERSION, CF7_SMTP_MIN_PHP_VERSION, '<=' ) ) {
	add_action(
		'admin_init',
		static function() {
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}
	);
	add_action(
		'admin_notices',
		static function() {
			echo wp_kses_post(
				sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					esc_html__( 'SMTP for Contact Form 7 requires PHP 7.1 or newer.', CF7_SMTP_TEXTDOMAIN )
				)
			);
		}
	);

	// Return early to prevent loading the plugin.
	return;
}

$cf7_smtp_libraries = require CF7_SMTP_PLUGIN_ROOT . 'vendor/autoload.php'; //phpcs:ignore

require_once CF7_SMTP_PLUGIN_ROOT . 'functions/functions.php';

/**
 * If the plugin is not being installed then
 * register the activation and deactivation hooks
 * and load the plugin
 */
if ( ! wp_installing() ) {

	/* It's a hook that is called when the plugin is activated. */
	register_activation_hook( CF7_SMTP_TEXTDOMAIN . '/' . CF7_SMTP_TEXTDOMAIN . '.php', array( new \cf7_smtp\Backend\ActDeact(), 'activate' ) );

	/* It's a hook that is called when the plugin is deactivated. */
	register_deactivation_hook( CF7_SMTP_TEXTDOMAIN . '/' . CF7_SMTP_TEXTDOMAIN . '.php', array( new \cf7_smtp\Backend\ActDeact(), 'deactivate' ) );

	/* It's a hook that is called when all plugins are loaded. */
	add_action(
		'plugins_loaded',
		static function () use ( $cf7_smtp_libraries ) {
			$cf7_smtp_libraries = require CF7_SMTP_PLUGIN_ROOT . 'vendor/autoload.php';
			try {
				new \cf7_smtp\Engine\Initialize( $cf7_smtp_libraries );
			} catch ( Exception $e ) {
				return;
			}

			if ( ! class_exists( 'WPCF7_Service' ) ) {
				return;
			}
		}
	);

	add_action(
		'init',
		static function() {
			$file = path_join( CF7_SMTP_PLUGIN_ROOT, 'integration/integration.php' );

			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	);
}

/**
 * call the integration action to mount our plugin as a component
 * into the intefration page
 */
add_action( 'wpcf7_init', 'cf7_smtp_register_service', 1, 0 );
function cf7_smtp_register_service() {
	$integration = WPCF7_Integration::get_instance();
	$integration->add_category( 'email_services', 'Email Services' );
	$integration->add_service(
		'cf7-smtp',
		cf7_smtp\Integration\Service::get_instance()
	);
}
