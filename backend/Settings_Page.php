<?php

/**
 * CF7_SMTP the settings page
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
 * Create the settings page in the backend
 */
class Settings_Page extends Base {

	/**
	 * The settings form
	 *
	 * @var Settings_Form
	 */
	private $form;

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}

		$this->form = new Settings_Form();

		// Add the options page and menu item.
		\add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		$realpath        = (string) \realpath( __DIR__ );
		$plugin_basename = \plugin_basename( \plugin_dir_path( $realpath ) . 'cf7-smtp' . '.php' );

		\add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		\add_submenu_page(
			'wpcf7',
			CF7_SMTP_NAME,
			__( 'SMTP', 'cf7-smtp' ),
			'manage_options',
			'cf7-smtp',
			array( $this, 'display_plugin_admin_page' )
		);

		\add_action( 'admin_init', array( $this->form, 'cf7_smtp_options_init' ) );
		\add_action( 'admin_init', array( $this->form, 'cf7_smtp_handle_actions' ), 1 );
	}


	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function display_plugin_admin_page() {
		include_once CF7_SMTP_PLUGIN_ROOT . 'backend/views/admin.php';
		include_once CF7_SMTP_PLUGIN_ROOT . 'backend/views/send_mail.php';
	}

	/**
	 * Add a settings action link to the plugin page.
	 *
	 * @since {{plugin_version}}
	 * @param array $links Array of links.
	 * @return array
	 */
	public function add_action_links( array $links ) {
		$plugin_option   = get_option( 'cf7-smtp' . '-options' );
		$service_enabled = $plugin_option['service_enabled'] ?? false;
		$url             = $service_enabled ? 'admin.php?page=cf7-smtp' : 'admin.php?page=wpcf7-integration&service=cf7-smtp&action=setup';
		$label           = $service_enabled ? __( 'Setup SMTP', 'cf7-smtp' ) : __( 'Settings', 'cf7-smtp' );
		return \array_merge(
			array(
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					\admin_url( $url ),
					$label
				),
			),
			$links
		);
	}
}
