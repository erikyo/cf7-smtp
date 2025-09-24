<?php

namespace cf7_smtp\Integration;

/**
 * CF7_SMTP context class.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */


if ( ! class_exists( 'WPCF7_Service' ) ) {
	return;
}

/**
 * Integration class from Contact Form 7
 */

use WPCF7_Service as GlobalWPCF7_Service;

/**
 * This Extention represents the skeleton of the integration API
 */

class Service extends GlobalWPCF7_Service {

	private static $instance;

	public $options;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		/**
		 * Call the options otherwise the plugin will break in integration
		 */
		$this->options = get_option( 'cf7-smtp-options' );

		if ( isset( $_POST['cf7_smtp_submit'] ) && check_admin_referer( 'cf7a_toggle', 'cf7a_nonce' ) ) {
			$this->options['enabled'] = $_POST['cf7_smtp_submit'] === 'Enable';
			update_option( 'cf7-smtp-options', $this->options );
			// add a notice that the settings have been saved
			add_action(
				'admin_notices',
				function () {
					echo '<p>' . __( 'Settings saved.', 'cf7-smtp' ) . '</p>'; }
			);
		}

		$integration = 'cf7-smtp';
		add_action( 'load-' . $integration, array( $this, 'wpcf7_load_integration_page' ), 10, 0 );
	}

	/**
	 * The function returns the title "SMTP" with the description "Simple Mail Transfer Protocol" in the
	 * specified language.
	 *
	 * @return string "SMTP" with the translation "Simple Mail Transfer Protocol".
	 */
	public function get_title() {
		return __( 'CF7 SMTP', 'cf7-smtp' );
	}

	/**
	 * The function checks if a certain option called "enabled" is set to true.
	 *
	 * @return bool value of the 'enabled' key in the  array.
	 */
	public function is_active() {
		return $this->options['enabled'];
	}

	/**
	 * The function "get_categories" returns an array containing the category "email_services".
	 *
	 * @return array containing the string 'email_services' is being returned.
	 */
	public function get_categories() {
		return array( 'email_services' );
	}

	/**
	 * The function "icon" echoes an SVG icon wrapped in a div with the class "integration-icon".
	 */
	public function icon() {
		$allowed_html = array(
			'svg'    => array(
				'xmlns'   => true,
				'id'      => true,
				'viewbox' => true,
				'width'   => true,
				'height'  => true,
			),
			'defs'   => array(),
			'style'  => array(),
			'g'      => array(
				'id' => true,
			),
			'circle' => array(
				'cx'        => true,
				'cy'        => true,
				'r'         => true,
				'class'     => true,
				'transform' => true,
			),
			'path'   => array(
				'd'     => true,
				'class' => true,
				'fill'  => true,
			),
			'rect'   => array(
				'width'  => true,
				'height' => true,
				'x'      => true,
				'y'      => true,
				'class'  => true,
				'rx'     => true,
				'ry'     => true,
			),
		);
		$style        = '<style>#cf7-smtp input { margin: 0 5px 0 0; } #cf7-smtp .integration-icon { display: inline-block; padding-block: inherit; margin: 0 0 0 0.7em; width: 30px; }</style>';
		echo '<div class="integration-icon">' . wp_kses( file_get_contents( CF7_SMTP_PLUGIN_ROOT . 'public/icon.svg' ), $allowed_html ) . $style . '</div>';
	}

	/**
	 * The function returns a link to the WordPress plugin "cf7-smtp" on the WordPress.org website.
	 *
	 * @return a link to the WordPress plugin "cf7-smtp" on the WordPress.org website.
	 */
	public function link() {
		return wpcf7_link(
			'https://wordpress.org/plugins/cf7-smtp/',
			'cf7-smtp'
		);
	}

	public function admin_notice( $message = '' ) {
	}

	/**
	 * The function `menu_page_url` generates a URL for a specific menu page with additional query
	 * parameters.
	 *
	 * @param array $args The `` parameter is an optional array that allows you to add additional query
	 * parameters to the URL. These query parameters can be used to pass data or settings to the page that
	 * the URL points to.
	 *
	 * @return string URL with query parameters.
	 */
	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'cf7-smtp' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


	/**
	 * The function checks if the action is "setup" and the request method is "POST", and if so, it
	 * performs some actions and redirects the user.
	 *
	 * @param string $action The "action" parameter is used to determine the specific action that needs to be
	 * performed. In this code snippet, if the value of the "action" parameter is "setup", it will execute
	 * the code inside the if statement.
	 */
	public function load( $action = '' ) {
		if ( ! empty( $_SERVER['REQUEST_METHOD'] ) ) {
			if ( 'setup' == $action && 'POST' == $_SERVER['REQUEST_METHOD'] ) {
				check_admin_referer( 'cf7-smtp-setup' );

				if ( ! empty( $_POST['reset'] ) ) {
					$redirect_to = $this->menu_page_url( 'action=setup' );
					wp_safe_redirect( $redirect_to );
				}
				exit();
			}
		}
	}



	/**
	 * The `display` function is used to display information about the SMTP plugin and provide options for
	 * setup integration.
	 *
	 * @param string The "action" parameter is used to determine the specific action to be performed in the
	 * "display" function. It is a string that can have two possible values:
	 */
	public function display( $action = '' ) {
		echo sprintf(
			'<p>%s</p>',
			esc_html(
				__(
					'SMTP stands for ‘Simple Mail Transfer Protocol’.'
					. 'It is a connection-oriented, text-based network protocol, '
					. 'the purpose of this plugin is to send e-mails from a sender to a recipient through the use of a form',
					'cf7-smtp'
				)
			)
		);

		echo sprintf(
			'<p><strong>%s</strong></p>',
			// phpcs:ignore
			wpcf7_link(
				esc_html__( 'https://wordpress.org/plugins/cf7-smtp/', 'cf7-smtp' ),
				'CF7 SMTP (' . CF7_SMTP_VERSION . ")"
			)
		);

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( 'CF7 SMTP is active on this site.', 'cf7-smtp' ) )
			);
		}

		// Get the current checkbox status from the options
		echo '<div class="wrap">';
		echo '<form method="post" action="">';
		wp_nonce_field( 'cf7a_toggle', 'cf7a_nonce' );
		printf(
			'<input type="submit" name="cf7_smtp_submit" class="button button-primary" value="%s">',
			$this->is_active() ? esc_html__( 'Disable', 'cf7-smtp' ) : esc_html__( 'Enable', 'cf7-smtp' )
		);
		if ( $this->is_active() ) {
			printf( '<a class="button" href="%s">Settings Page</a>', esc_url_raw( admin_url( 'admin.php?page=cf7-smtp' ) ) );
		}
		echo '</form>';
		echo '</div>';
	}
}
