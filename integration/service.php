<?php
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

use WPCF7_Service as GlobalWPCF7_Service;

Class WPCF7_SMTP extends GlobalWPCF7_Service {

    private static $instance;

    public $options;

    public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

    public function __construct() {
	    $this->options = get_option( 'cf7-smtp-options' );

        $integration = 'cf7-smtp';
        add_action('load-' . $integration, array($this, 'wpcf7_load_integration_page'), 10, 0);

    }

	public function get_title() {
		return __( 'SMTP', 'Simple Mail Transfer Protocol' );
	}

    public function is_active() {
        return true;
	}

	public function get_categories()
	{
		return array('email_services');
	}

    public function icon() {
		echo '<div class="integration-icon">' . file_get_contents(CF7_SMTP_PLUGIN_ROOT . 'public/icon.svg') . '</div>';
	}


	public function link() {
		return wpcf7_link(
			'https://wordpress.org/plugins/cf7-smtp/',
			'cf7-smtp'
		);
	}

	public function admin_notice( $message = '' ) {
	}

    protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'cf7-smtp' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}


    public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'cf7-smtp-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$redirect_to = $this->menu_page_url( 'action=setup' );
			}
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}



    public function display( $action = '') {
		echo sprintf(
			'<p>%s</p>',
			esc_html( __( "SMTP stands for ‘Simple Mail Transfer Protocol’."
            . "It is a connection-oriented, text-based network protocol, "
            . "the purpose of this plugin is to send e-mails from a sender to a recipient through the use of a form", 'contact-form-7' ) )
		);

		echo sprintf(
			'<p><strong>%s</strong></p>',
			wpcf7_link(
				__( 'https://wordpress.org/plugins/cf7-smtp/', 'contact-form-7' ),
				__( 'SMTP (v0.0.1)', 'contact-form-7' )
			)
		);

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "SMTP is active on this site.", 'contact-form-7' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} else {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup Integration', 'contact-form-7' ) )
			);
		}
	}

    private function display_setup( ) {

		include_once( CF7_SMTP_PLUGIN_ROOT . 'backend/views/admin.php' );
		include_once( CF7_SMTP_PLUGIN_ROOT . 'backend/views/send_mail.php' );

	}
}
