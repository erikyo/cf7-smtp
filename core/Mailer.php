<?php

/**
 * CF7_SMTP MAILER
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
	 * The mail log.
	 *
	 * @var string
	 */
	private string $cf7_smtp_log = '';

	/**
	 * The mail header.
	 *
	 * @var array
	 */
	private array $default_headers;

	public function __construct() {
		parent::initialize();

		// Get from_mail with fallback to admin_email
		$from_mail = ! empty( $this->options['from_mail'] ) && is_email( $this->options['from_mail'] )
			? $this->options['from_mail']
			: get_option( 'admin_email' );

		$from_name = ! empty( $this->options['from_name'] )
			? $this->options['from_name']
			: get_bloginfo( 'name' );

		$this->default_headers = array(
			'From'         => "{$from_name} <{$from_mail}>",
			'Content-Type' => 'text/html',
			'Reply-To'     => get_option( 'admin_email' ),
		);
	}

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize() {
		if ( ! empty( $this->options['enabled'] || ! empty( get_transient( 'cf7_smtp_testing' ) ) ) ) {
			\add_action( 'phpmailer_init', array( $this, 'smtp_overrides' ), 11 );
		}

		if ( ! empty( $this->options['custom_template'] ) ) {
			\add_action( 'phpmailer_init', array( $this, 'cf7_smtp_apply_template' ), 10 );
		}

		\add_action( 'wpcf7_mail_sent', array( $this, 'cf7_smtp_wp_mail_succeeded' ) );
		\add_action( 'wpcf7_mail_failed', array( $this, 'cf7_smtp_wp_mail_failed' ) );
		\add_action( 'wp_mail_succeeded', array( $this, 'cf7_smtp_wp_mail_log' ) );
		\add_action( 'wp_mail_failed', array( $this, 'cf7_smtp_wp_mail_catch_errors' ) );
		\add_filter( 'wpcf7_mail_components', array( $this, 'cf7_smtp_email_style' ), 99, 3 );
	}

	/**
	 * Log contact form mail statistics
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form instance.
	 * @param bool              $success      Whether the mail was sent successfully.
	 *
	 * @return void
	 */
	private function log_contact_form_stats( WPCF7_ContactForm $contact_form, bool $success ) {
		$stats = new Stats();

		if ( $success ) {
			$stats->add_success();
		} else {
			$stats->add_failed();
		}

		$stats->add_field_to_storage(
			time(),
			array(
				'mail_sent' => $success,
				'form_id'   => $contact_form->id(),
				'title'     => $contact_form->title(),
			)
		);

		$stats->store();
	}

	/**
	 * Fired when the mail has succeeded
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_succeeded( $contact_form ) {
		$this->log_contact_form_stats( $contact_form, true );
	}

	/**
	 * Fired when the mail has failed
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_failed( $contact_form ) {
		$this->log_contact_form_stats( $contact_form, false );
	}

	/**
	 * If there's an error, save it to a transient.
	 *
	 * @param \WP_Error $error - The error message that was returned by wp_mail().
	 */
	public function cf7_smtp_wp_mail_catch_errors( $error ) {
		$error_msgs = $error->get_error_messages();
		foreach ( $error_msgs as $msg ) {
			error_log( 'CF7 SMTP Error: ' . $msg );
		}

		cf7_smtp_log( 'WP Mail Failed! Error Messages:' );
		cf7_smtp_log( $error_msgs );
		cf7_smtp_log( 'Error Data:' );
		cf7_smtp_log( $error->get_all_error_data() );
		set_transient( 'cf7_smtp_testing_error', $error_msgs, MINUTE_IN_SECONDS );
	}

	/**
	 * It sets a transient for the log file.
	 */
	public function cf7_smtp_wp_mail_log() {
		set_transient( 'cf7_smtp_testing_log', $this->cf7_smtp_log, MINUTE_IN_SECONDS );
	}

	/**
	 * Get the SMTP debug log
	 *
	 * @return string The debug log.
	 */
	public function get_log(): string {
		return $this->cf7_smtp_log;
	}

	/**
	 * Get template replacements for email
	 *
	 * @param array  $mail_data The email data.
	 * @param string $template_name The template name.
	 *
	 * @return array Template replacements.
	 */
	private function get_template_replacements( array $mail_data, string $template_name ): array {
		return apply_filters(
			'cf7_smtp_mail_template_replacements',
			array(
				'title'     => ! empty( $mail_data['title'] ) ? esc_html( $mail_data['title'] ) : '',
				'subject'   => ! empty( $mail_data['subject'] ) ? esc_html( $mail_data['subject'] ) : '',
				'language'  => ! empty( $mail_data['language'] ) ? sanitize_text_field( $mail_data['language'] ) : get_bloginfo( 'language' ),

				/**
				 * Set the mail logo image shown at the top of the email
				 *
				 * @since 0.0.1
				 *
				 * @param string $mail_logo the url of the image
				 */
				'site_logo' => apply_filters( 'cf7_smtp_mail_logo', wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) ) ?? '',
				'site_name' => apply_filters( 'cf7_smtp_mail_logo_alt', esc_html( get_bloginfo( 'name' ) ) ),

				/**
				 * Set the mail logo link url (what happens when you click on the image at the top of the email)
				 *
				 * @since 0.0.1
				 *
				 * @param string $mail_url the url of the image
				 */
				'site_url'  => apply_filters( 'cf7_smtp_mail_logo_url', get_site_url() ) ?? '',
			),
			sanitize_file_name( $template_name . '.html' )
		);
	}

	/**
	 * Replace template placeholders with actual values
	 *
	 * @param array  $mail_data The components of the email.
	 * @param string $template The content of the template as literal.
	 *
	 * @return string The body of the email.
	 */
	public function cf7_smtp_form_template( array $mail_data, string $template ): string {
		if ( empty( $template ) ) {
			return $mail_data['body'];
		}

		$mail_body = ! empty( $mail_data['body'] ) ? wp_kses_post( $mail_data['body'] ) : '';
		$mail_body = $mail_data['body'] ? str_replace( '{{message}}', $mail_body, $template ) : $template;

		$replacements = $this->get_template_replacements( $mail_data, basename( $template, '.html' ) );

		foreach ( $replacements as $key => $value ) {
			$mail_body = str_replace( '{{' . $key . '}}', $value, $mail_body );
		}

		return $mail_body;
	}

	/**
	 * Get email template file path
	 *
	 * @param string $template_name The name of the template file.
	 * @param int    $id The template that refers to contact form id.
	 * @param string $lang The template language.
	 *
	 * @return string The template file path.
	 */
	private function get_template_path( string $template_name, int $id, string $lang ): string {
		$theme_custom_dir    = 'cf7-smtp/';
		$plugin_template_dir = CF7_SMTP_PLUGIN_ROOT . 'templates/';

		// Look in theme directories
		if ( $id ) {
			$template = locate_template(
				array(
					"{$template_name}-{$id}.html",
					$theme_custom_dir . "{$template_name}-{$id}.html",
				)
			);
		} else {
			$template = locate_template(
				array(
					"{$template_name}.html",
					$theme_custom_dir . "{$template_name}.html",
				)
			);
		}

		// Fallback to plugin templates
		if ( ! empty( $template ) && $id ) {
			$template = $plugin_template_dir . "{$template_name}-{$id}.html";
		}

		/* Get default template_name.php */
		if ( ! empty( $template ) && file_exists( $plugin_template_dir . "{$template_name}.html" ) ) {
			$template = $plugin_template_dir . "{$template_name}.html";
		}

		return apply_filters( 'cf7_smtp_mail_template', $template, $template_name, $id, $lang, 'cf7-smtp' );
	}

	/**
	 * Get email template content
	 *
	 * @param string $template_name The name of the template file.
	 * @param int    $id The template that refers to contact form id.
	 * @param string $lang The template language.
	 *
	 * @return string The contents of the file.
	 */
	public function cf7_smtp_get_email_style( string $template_name, int $id, string $lang ): string {
		$template = $this->get_template_path( $template_name, $id, $lang );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return ! empty( $template ) ? file_get_contents( $template ) : '';
	}

	/**
	 * Apply custom template to email components
	 *
	 * @param array             $components The components of the email message.
	 * @param WPCF7_ContactForm $contact_form The current contact form object.
	 * @param WPCF7_Mail        $mail The mail object that contains all the information about the email.
	 *
	 * @return array The modified components.
	 */
	public function cf7_smtp_email_style( array $components, WPCF7_ContactForm $contact_form, WPCF7_Mail $mail ): array {
		if ( empty( $this->options['custom_template'] ) || empty( $components['body'] ) ) {
			return $components;
		}

		// Apply the nl2br to convert newlines to HTML line breaks
		$email_data = array(
			'body'     => nl2br( $components['body'] ),
			'subject'  => $components['subject'],
			'language' => $contact_form->locale(),
		);

		$email_data = apply_filters( 'cf7_smtp_mail_components', $email_data, $contact_form, $mail );
		$template   = $this->cf7_smtp_get_email_style( 'default', $contact_form->id(), $contact_form->locale() );

		$components['body'] = $this->cf7_smtp_form_template( $email_data, $template );

		// Forces header Content-Type to be HTML if using custom template
		if ( strpos( $components['headers'], 'Content-Type:' ) === false ) {
			$components['headers'] .= "\nContent-Type: text/html; charset=\"" . get_option( 'blog_charset' ) . "\"\n";
		} else {
			$components['headers'] = preg_replace(
				'/Content-Type: text\/plain/i',
				'Content-Type: text/html',
				$components['headers']
			);
		}

		return $components;
	}

	/**
	 * Force HTML content type when using custom template
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer object.
	 */
	public function cf7_smtp_apply_template( PHPMailer\PHPMailer $phpmailer ) {
		// If it contains HTML (like <br>, <div>, <html>), force PHPMailer to use HTML
		if ( preg_match( '/<(br|div|html|body|table|p)/mi', $phpmailer->Body ) ) {
			$phpmailer->isHTML( true );
		}
	}

	/**
	 * Get setting by key with proper fallback
	 *
	 * @param string      $key The key of the setting.
	 * @param array|false $options The option array.
	 *
	 * @return string The value of the key.
	 */
	public function get_setting_by_key( string $key, $options = false ): string {
		$options = ! empty( $options ) ? $options : $this->options;

		if ( defined( 'CF7_SMTP_SETTINGS' ) && isset( CF7_SMTP_SETTINGS[ $key ] ) ) {
			return CF7_SMTP_SETTINGS[ $key ];
		}

		return $options[ $key ] ?? '';
	}

	/**
	 * Get formatted email headers
	 *
	 * @return string The headers for the email.
	 */
	private function get_headers(): string {
		$header_lines = array_map(
			function ( $key, $value ) {
				return "{$key}: {$value}";
			},
			array_keys( $this->default_headers ),
			$this->default_headers
		);

		return implode( "\n", $header_lines );
	}

	/**
	 * Send email using wp_mail
	 *
	 * @param string $to The recipient of the email.
	 * @param string $subject The subject of the email.
	 * @param string $body The body of the email.
	 * @param string $headers The headers of the email.
	 * @param array  $attachments The attachments of the email.
	 *
	 * @return bool Whether the email was sent successfully.
	 */
	private function send( $to, $subject, $body, $headers = '', $attachments = array() ) {
		try {
			if ( ! wp_mail( $to, $subject, $body, $headers, $attachments ) ) {
				cf7_smtp_log( 'Unable to send email ' . $to . ' ' . $subject . ' ' . $body . ' ' . $headers );
				return false;
			}
			return true;
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			cf7_smtp_log( 'Email sending failed: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Prepare email data with template
	 *
	 * @param array  $data Email data (body, subject, title).
	 * @param string $template_file Template file name.
	 *
	 * @return array Prepared email data.
	 */
	private function prepare_email_with_template( array $data, string $template_file ): array {
		if ( empty( $this->options['template'] ) && empty( $this->options['custom_template'] ) ) {
			return $data;
		}

		$template_path = CF7_SMTP_PLUGIN_ROOT . 'templates/' . $template_file;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$template = file_exists( $template_path ) ? file_get_contents( $template_path ) : '';

		if ( $template ) {
			$data['body'] = $this->cf7_smtp_form_template( $data, $template );
		}

		return $data;
	}

	/**
	 * Send report email
	 *
	 * @param string $report The report content.
	 *
	 * @return bool Whether the report was sent successfully.
	 */
	public function send_report( $report ) {
		$subject = esc_html(
			sprintf(
				/* translators: %s site name */
				__( '%s Mail report', 'cf7-smtp' ),
				get_bloginfo( 'name' )
			)
		);

		$mail_data = $this->prepare_email_with_template(
			array(
				'body'    => $report,
				'title'   => get_bloginfo( 'name' ),
				'subject' => $subject,
			),
			'report.html'
		);

		// Fallback to plain text if no template
		if ( empty( $this->options['template'] ) ) {
			$mail_data['body'] = sprintf( "%s %s\r\n\r\n%s", $subject, get_bloginfo( 'name' ), $report );
		}

		return $this->send(
			$this->options['report_to'],
			$subject,
			$mail_data['body'],
			$this->get_headers()
		);
	}

	/**
	 * Send test email
	 *
	 * @param array $mail Email data.
	 *
	 * @return bool|string Mail result or log.
	 */
	public function send_email( $mail ) {
		// Use headers from $mail array if provided, otherwise empty
		$headers = ! empty( $mail['headers'] ) ? $mail['headers'] : '';

		// Apply custom template if enabled
		if ( ! empty( $this->options['custom_template'] ) ) {
			// Append template headers if not already set
			if ( empty( $headers ) ) {
				$headers = $this->get_headers();
			}

			$mail_data = $this->prepare_email_with_template(
				array(
					'body'    => $mail['body'],
					'subject' => $mail['subject'],
				),
				'test.html'
			);

			$mail['body'] = $mail_data['body'];
		}

		// Capture output for debugging
		ob_start();

		$mail_sent = wp_mail(
			$mail['email'],
			$mail['subject'],
			$mail['body'],
			$headers
		);

		$mail_log = ob_get_clean();

		return ! empty( $mail_log ) ? $mail_log : $mail_sent;
	}

	/**
	 * Get decrypted password
	 *
	 * @return string The decrypted password.
	 */
	private function get_smtp_password(): string {
		if ( ! empty( CF7_SMTP_SETTINGS ) && isset( CF7_SMTP_SETTINGS['user_pass'] ) ) {
			return CF7_SMTP_SETTINGS['user_pass'];
		}

		if ( ! empty( $this->options['user_pass'] ) ) {
			return cf7_smtp_decrypt( $this->options['user_pass'] );
		}

		return '';
	}

	/**
	 * Configure PHPMailer authentication
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 * @param string              $username SMTP username.
	 * @param string              $password SMTP password.
	 */
	private function configure_smtp_auth( PHPMailer\PHPMailer $phpmailer, string $username, string $password ) {
		if ( ! empty( $username ) && ! empty( $password ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->SMTPAuth = true;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Username = $username;
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Password = $password;
		}
	}

	/**
	 * Configure PHPMailer port
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 * @param int                 $port User-defined port.
	 * @param string              $auth Encryption type.
	 */
	private function configure_smtp_port( PHPMailer\PHPMailer $phpmailer, int $port, string $auth ) {
		if ( ! empty( $port ) && $port > 0 && $port <= 65535 ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Port = $port;
			return;
		}

		// Set default ports based on encryption
		$default_ports = array(
			'ssl' => 465,
			'tls' => 587,
		);

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Port = $default_ports[ $auth ] ?? 25;
	}

	/**
	 * Configure PHPMailer for insecure connections
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 */
	private function configure_insecure_connection( PHPMailer\PHPMailer $phpmailer ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->SMTPAutoTLS = false;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);
	}

	/**
	 * Configure PHPMailer debug output
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 */
	private function configure_smtp_debug( PHPMailer\PHPMailer $phpmailer ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Debugoutput = function ( $str, $level ) {
			$this->cf7_smtp_log .= "$level: $str\n";
		};
	}

	/**
	 * Configure PHPMailer From address
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 * @param string              $from_mail From email address.
	 * @param string              $from_name From name.
	 */
	private function configure_from_address( PHPMailer\PHPMailer $phpmailer, string $from_mail, string $from_name ) {
		try {
			$phpmailer->setFrom( $from_mail, $from_name, false );
			$phpmailer->Sender = $from_mail;
			return;
		} catch ( \Exception $e ) {
			cf7_smtp_log( 'Failed to set From and Sender: ' . $e->getMessage() );
		}

		// Use WordPress default if from_mail is invalid
		$default_from = get_option( 'admin_email' );
		cf7_smtp_log( "From mail empty/invalid. Fallback to admin_email: $default_from" );

		try {
			if ( is_email( $default_from ) ) {
				$phpmailer->setFrom( $default_from, get_bloginfo( 'name' ), false );
				$phpmailer->Sender = $default_from;
			}
		} catch ( \Exception $e ) {
			cf7_smtp_log( 'Failed to set From and Sender: ' . $e->getMessage() );
		}
	}

	/**
	 * Configure PHPMailer Reply-To address
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
	 * @param string              $from_mail From email address.
	 * @param string              $from_name From name.
	 */
	private function configure_reply_to( PHPMailer\PHPMailer $phpmailer, string $from_mail, string $from_name ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$reply_to_mail = ! empty( $from_mail ) && is_email( $from_mail ) ? $from_mail : $phpmailer->From;
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$reply_to_name = ! empty( $from_name ) ? $from_name : $phpmailer->FromName;

		try {
			if ( is_email( $reply_to_mail ) ) {
				$phpmailer->addReplyTo( $reply_to_mail, $reply_to_name );
			}
		} catch ( \Exception $e ) {
			cf7_smtp_log( 'Failed to set Reply-To: ' . $e->getMessage() );
		}
	}

	/**
	 * Override default WordPress mailer with SMTP settings
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer object.
```
	 *
	 * @throws Exception May fail and throw an exception.
	 */
	public function smtp_overrides( PHPMailer\PHPMailer $phpmailer ) {
		try {
			// Check if SMTP is enabled
			if ( empty( $this->options['enabled'] ) ) {
				return;
			}

			$phpmailer->isSMTP();

			// Get settings
			$auth          = $this->get_setting_by_key( 'auth' );
			$username      = sanitize_text_field( $this->get_setting_by_key( 'user_name' ) );
			$password      = $this->get_smtp_password();
			$host          = sanitize_text_field( $this->get_setting_by_key( 'host' ) );
			$port          = intval( $this->get_setting_by_key( 'port' ) );
			$insecure      = intval( $this->get_setting_by_key( 'insecure' ) );
			$raw_from_mail = $this->get_setting_by_key( 'from_mail' );
			$from_mail     = is_email( $raw_from_mail ) ? sanitize_email( $raw_from_mail ) : '';
			$from_name     = sanitize_text_field( $this->get_setting_by_key( 'from_name' ) );
			$reply_to      = intval( $this->get_setting_by_key( 'replyTo' ) );

			// Validate required settings
			if ( empty( $host ) ) {
				throw new Exception( 'SMTP Host is required but not configured.' );
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Host = $host;

			// Configure port
			$this->configure_smtp_port( $phpmailer, $port, $auth );

			// Configure encryption
			if ( ! empty( $auth ) && in_array( $auth, array( 'tls', 'ssl' ), true ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->SMTPSecure = $auth;
			}

			// Configure authentication
			$this->configure_smtp_auth( $phpmailer, $username, $password );

			// Handle insecure connections
			if ( ! empty( $insecure ) ) {
				$this->configure_insecure_connection( $phpmailer );
			}

			// Set timeout
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->Timeout = 30;

			// Enable debug output if testing
			if ( get_transient( 'cf7_smtp_testing' ) ) {
				$this->configure_smtp_debug( $phpmailer );
			}

			// Configure From address
			$this->configure_from_address( $phpmailer, $from_mail, $from_name );

			// Configure Reply-To
			if ( ! empty( $reply_to ) ) {
				$this->configure_reply_to( $phpmailer, $from_mail, $from_name );
			}

			// Set XMailer header
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$phpmailer->XMailer = 'WordPress/' . get_bloginfo( 'version' );

		} catch ( Exception $e ) {
			cf7_smtp_log( 'Failed to configure SMTP: ' . $e->getMessage() );
		}
	}
}
