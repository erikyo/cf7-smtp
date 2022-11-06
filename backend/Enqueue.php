<?php

/**
 * cf7_smtp
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;

/**
 * This class contain the Enqueue stuff for the backend
 */
class Enqueue extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {

		if ( !parent::initialize() ) {
			return;
		}

		\add_action('admin_enqueue_scripts' , [ $this, 'enqueue_admin_styles' ] );
		\add_action('admin_enqueue_scripts' , [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 0.0.1
	 * @return array
	 */
	public function enqueue_admin_styles() {
		$admin_page = \get_current_screen();
		$styles     = array();

		if ( !\is_null( $admin_page ) && false !== strpos($admin_page->id, 'cf7-smtp') ) {
			$asset = include C_PLUGIN_ROOT . 'build/smtp-settings.asset.php';
			\wp_enqueue_style( C_TEXTDOMAIN . '-settings-style', C_PLUGIN_URL . 'build/smtp-settings.css', [], $asset['version'] );
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 0.0.1
	 * @return array
	 */
	public function enqueue_admin_scripts() {
		$admin_page = \get_current_screen();
		$scripts    = array();

		if ( !\is_null( $admin_page ) && false !== strpos($admin_page->id, 'cf7-smtp') ) {

			$asset = include C_PLUGIN_ROOT . 'build/smtp-settings.asset.php';
			\wp_enqueue_script( C_TEXTDOMAIN . '-settings-script', C_PLUGIN_URL .'build/smtp-settings.js', $asset['dependencies'], $asset['version'], true );

			\wp_localize_script(
				C_TEXTDOMAIN . '-settings-script',
				'smtp_settings',
				array(
					'nonce' => wp_create_nonce( 'cf7-smtp' )
				)
			);
		}
	}

}
