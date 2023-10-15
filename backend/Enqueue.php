<?php

/**
 * CF7_SMTP Enqueue style and scripts
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
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}

		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_admin_styles() {
		$admin_page = \get_current_screen();
		$styles     = array();

		if (
			! \is_null( $admin_page ) &&
			( false !== strpos( $admin_page->id, 'contact_page_wpcf7-integration' ) ||
				false !== strpos( $admin_page->id, 'dashboard' )
			)
		) {
			$asset = include CF7_SMTP_PLUGIN_ROOT . '/build/smtp-settings.asset.php';
			\wp_enqueue_style( CF7_SMTP_TEXTDOMAIN . '-settings-style', CF7_SMTP_PLUGIN_URL . 'build/smtp-settings.css', array(), $asset['version'] );
		}
	}

	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @since 0.0.1
	 */
	public function enqueue_admin_scripts() {
		$admin_page = \get_current_screen();
		$scripts    = array();

		if (
			! \is_null( $admin_page ) &&
			( false !== strpos( $admin_page->id, 'contact_page_wpcf7-integration' ) ||
				false !== strpos( $admin_page->id, 'dashboard' )
			)
		) {

			$asset = include CF7_SMTP_PLUGIN_ROOT . '/build/smtp-settings.asset.php';
			\wp_enqueue_script( CF7_SMTP_TEXTDOMAIN . '-settings-script', CF7_SMTP_PLUGIN_URL . 'build/smtp-settings.js', $asset['dependencies'], $asset['version'], true );

			\wp_localize_script(
				CF7_SMTP_TEXTDOMAIN . '-settings-script',
				'smtp_settings',
				array(
					'nonce' => wp_create_nonce( CF7_SMTP_TEXTDOMAIN ),
				)
			);
		}
	}
}
