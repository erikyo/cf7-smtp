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

namespace cf7_smtp\Core;

use cf7_smtp\Engine\Base;
use PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

/**
 * Enqueue stuff on the frontend and backend
 */
class Enqueue extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		parent::initialize();

		if ( ! empty( $this->options['enabled'] ) ) {
			\add_action( 'phpmailer_init', [ $this, 'cf7_smtp_overrides' ] );
		}

		\add_action( 'wp_mail_failed', function ( $error ) {
			error_log( "cf7 smtp errors" );
			error_log( print_r( $error, true ) );
		} );

		\add_filter(
			'wpcf7_mail_components',
			[ $this, 'cf7_smtp_template' ],
			99, 3
		);
	}

	/**
	 * Creates Stripe's Payment Intent.
	 */
	public function cf7_smtp_template( $components, $contact_form, $mail ) {

		if ( ! empty( $components['body'] ) ) {
			$template = file_get_contents( C_PLUGIN_ROOT . 'templates/template.html' );

			$components['body'] = nl2br(wp_kses( $components['body'], [
				'i' => [],
				'b' => [],
				'a' => [ 'href' => [], 'target' => [] ],
				'strong' => [],
				'h1' => [],
				'h2' => [],
				'h3' => [],
				'h4' => [],
				'h5' => [],
				'h6' => []
			] ) );

			$components['body'] = str_replace( "{{message}}", $components['body'], $template );
			$components['body'] = str_replace( "{{subject}}", esc_html($components['subject']), $components['body'] );
			$components['body'] = str_replace( "{{language}}", get_bloginfo('language'), $components['body'] );
			$components['body'] = str_replace( "{{site_logo}}", apply_filters( "cf7_smtp_mail_logo", wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ) , 'full' ) ), $components['body'] );
			$components['body'] = str_replace( "{{site_url}}", apply_filters( "cf7_smtp_mail_logo_url", get_site_url() ), $components['body'] );
		}

		return $components;
	}

	public function cf7_smtp_valid_mail_configuration( PHPMailer\PHPMailer $phpmailer, $verbose = false ) {
		// TODO: Validate email data before send
	}

	/**
	 * @throws Exception
	 */
	public function cf7_smtp_overrides( PHPMailer\PHPMailer $phpmailer ) {

		$phpmailer->isSMTP();

		/* SSL or TLS, if necessary for your server */
		if (!empty($this->options['auth'])) {
			$phpmailer->SMTPAuth   = true;
			$phpmailer->SMTPSecure = $this->options['auth'];
		}

		/*Host*/
		if (!empty($this->options['host'])) $phpmailer->Host = sanitize_text_field( $this->options['host'] );

		/*Port*/
		if ($this->options['port']) $phpmailer->Port = intval($this->options['port']);

		/* Force it to use Username and Password to authenticate */
		if ($this->options['user_name']) $phpmailer->Username = sanitize_text_field($this->options['user_name']);
		if ($this->options['user_pass']) $phpmailer->Password = cf7_smtp_decrypt($this->options['user_pass']);

		/* Enable verbose debug output */
		$verbose = get_transient('cf7_smtp_testing');
		if ($verbose) {
			delete_transient('cf7_smtp_testing');
			$phpmailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
		}

		/* Force html if the user has choosen a custom template */
		if ( ! empty( $this->options['custom_template'] ) ) {
			$phpmailer->isHTML();
		}

		/* Setting the "from" (email and name). */
		if (!empty($this->options['advanced'])) {
			if (!empty($this->options['from_mail']) && !empty($this->options['from_name'])){
				$phpmailer->setFrom( $this->options['from_mail'], $this->options['from_name'] );
			}
		}
	}
}
