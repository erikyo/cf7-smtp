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
use WPCF7_ContactForm;
use WPCF7_Mail;

/**
 * Enqueue stuff on the frontend and backend
 */
class Mailer extends Base {
	/**
	 * @var array
	 */
	private $mail_allowed_tags;

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {

		parent::initialize();

		$this->mail_allowed_tags = apply_filters(
			'cf7_smtp_mail_allowed_tags',
			array(
				'i'      => array(),
				'b'      => array(),
				'a'      => array(
					'href'   => array(),
					'target' => array(),
				),
				'strong' => array(),
				'h1'     => array(),
				'h2'     => array(),
				'h3'     => array(),
				'h4'     => array(),
				'h5'     => array(),
				'h6'     => array(),
			)
		);

		if ( ! empty( $this->options['enabled'] ) ) {
			\add_action( 'phpmailer_init', array( $this, 'cf7_smtp_overrides' ) );
		}

		\add_action(
			'wp_mail_failed',
			function ( $error ) {
				error_log( 'cf7 smtp errors' );
				error_log( print_r( $error, true ) );
			}
		);

		\add_filter(
			'wpcf7_mail_components',
			array( $this, 'cf7_smtp_email_style' ),
			99,
			3
		);
	}


	/**
	 * It replaces the {{message}} placeholder in the template with the actual message, and then replaces the {{subject}}
	 * placeholder with the actual subject
	 *
	 * @param array $mail_data The components of the email.
	 *       //    $components = array(
	 *       //      'subject'  => string,
	 *       //      'body'     => string,
	 *       //      'language' => language,
	 *       //  );
	 * @param string $template_name the name of the html file
	 *
	 * @return string The body of the email.
	 */
	public function cf7_smtp_form_template( array $mail_data, string $template_name ): string {

		/* get the mail template */
		$template = $this->cf7_smtp_get_email_style( $template_name );

		if ( !empty($template) ) {
			/* htmlize the mail content */
			$mail_body = nl2br( wp_kses( $mail_data['body'], $this->mail_allowed_tags ) );
		} else {
			$mail_body = $mail_data['body'];
		}

		/* replace the message in body */
		$mail_body = str_replace( '{{message}}', $mail_body, $template );

		/* set the default mail replacement */
		$template_replacements = apply_filters(
			'cf7_smtp_mail_template_replacements',
			array(
				'subject'   => esc_html( $mail_data['subject'] ),
				'language'  => sanitize_text_field( $mail_data['language'] ) ?? get_bloginfo( 'language' ),
				'site_logo' => apply_filters( 'cf7_smtp_mail_logo', wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) ),
				'site_url'  => apply_filters( 'cf7_smtp_mail_logo_url', get_site_url() ),
			)
		);

		/* It's a simple way to replace the placeholders in the template with the actual values. */
		foreach ( $template_replacements as $replacement => $value ) {
			$mail_body = str_replace( '{{' . $replacement . '}}', $value, $mail_body );
		}

		return $mail_body;
	}

	/**
	 * It gets the contents of a file and returns it
	 *
	 * @param string $template_name The name of the template file.
	 *
	 * @return false|string The contents of the file.
	 */
	public function cf7_smtp_get_email_style( string $template_name ) {

		// TODO FILTER user defined template
		// $template = file_get_contents( get_stylesheet_directory_uri() . 'templates/mail/default.html' );

		return file_get_contents( C_PLUGIN_ROOT . 'templates/' . $template_name );
	}


	/**
	 * It takes the body of the email, and replaces it with the output of the function cf7_smtp_form_template
	 *
	 * @param array             $components The components of the email message.
	 * @param WPCF7_ContactForm $contact_form The current contact form object.
	 * @param WPCF7_Mail        $mail The mail object that contains all the information about the email.
	 *
	 * @return array $components The $components array is being returned.
	 */
	public function cf7_smtp_email_style( $components, $contact_form, $mail ) {

		if ( empty( $this->options['custom_template'] ) ) return $components;

		$email_data = array(
			'body'     => $components['body'],
			'subject'  => $components['subject'],
			'language' => $contact_form->locale(),
		);

		if ( ! empty( $components['body'] ) ) {
			$components['body'] = self::cf7_smtp_form_template(
				apply_filters( 'cf7_smtp_mail_components', $email_data, $contact_form, $mail ),
				apply_filters( 'cf7_smtp_mail_template', 'default.html', $contact_form->id(), $contact_form->locale() )
			);
		}

		return $components;
	}

	/**
	 * It overrides the default WordPress mailer with the SMTP mailer.
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer object.
	 *
	 * @throws Exception
	 */
	public function cf7_smtp_overrides( PHPMailer\PHPMailer $phpmailer ) {

		$phpmailer->isSMTP();

		/* SSL or TLS, if necessary for your server */
		if ( ! empty( $this->options['auth'] ) ) {
			$phpmailer->SMTPAuth   = true;
			$phpmailer->SMTPSecure = $this->options['auth'];
		}

		/*Host*/
		if ( ! empty( $this->options['host'] ) ) {
			$phpmailer->Host = sanitize_text_field( $this->options['host'] );
		}

		/*Port*/
		if ( $this->options['port'] ) {
			$phpmailer->Port = intval( $this->options['port'] );
		}

		/* Force it to use Username and Password to authenticate */
		if ( $this->options['user_name'] ) {
			$phpmailer->Username = sanitize_text_field( $this->options['user_name'] );
		}
		if ( $this->options['user_pass'] ) {
			$phpmailer->Password = cf7_smtp_decrypt( $this->options['user_pass'] );
		}

		/* Enable verbose debug output */
		$verbose = get_transient( 'cf7_smtp_testing' );
		if ( $verbose ) {
			delete_transient( 'cf7_smtp_testing' );
			$phpmailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
		}

		/* Force html if the user has choosen a custom template */
		if ( ! empty( $this->options['custom_template'] ) ) {
			$phpmailer->isHTML();
		}

		/* Setting the "from" (email and name). */
		if ( ! empty( $this->options['advanced'] ) ) {
			if ( ! empty( $this->options['from_mail'] ) && ! empty( $this->options['from_name'] ) ) {
				$phpmailer->setFrom( $this->options['from_mail'], $this->options['from_name'] );
			}
		}
	}
}
