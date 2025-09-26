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
	private $cf7_smtp_log;

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->cf7_smtp_log = '';

		parent::initialize();

		if ( ! empty( $this->options['enabled'] || ! empty( get_transient( 'cf7_smtp_testing' ) ) ) ) {
			\add_action( 'phpmailer_init', array( $this, 'cf7_smtp_overrides' ), 11 );
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
	 * Fired when the mail has succeeded
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @TODO: with the contact form instance we could build better stats in the future.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_succeeded( $contact_form ) {
		$report                      = get_option( 'cf7-smtp-report' );
		$report['storage'][ time() ] = array(
			'mail_sent' => true,
			'form_id'   => $contact_form->id(),
			'title'     => $contact_form->title(),
		);
		$report['success']           = ++$report['success'];
		update_option( 'cf7-smtp-report', $report );
	}

	/**
	 * Fired when the mail has failed
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_failed( $contact_form ) {
		$report                      = get_option( 'cf7-smtp-report' );
		$report['storage'][ time() ] = array(
			'mail_sent' => false,
			'id'        => $contact_form->id(),
			'title'     => $contact_form->title(),
		);
		$report['failed']            = ++$report['failed'];
		update_option( 'cf7-smtp-report', $report );
	}

	/**
	 * If there's an error, save it to a transient.
	 *
	 * @param \WP_Error $error - The error message that was returned by wp_mail().
	 */
	public function cf7_smtp_wp_mail_catch_errors( $error ) {
		cf7_smtp_log( $error->get_all_error_data() );
		set_transient( 'cf7_smtp_testing_error', $error->get_error_messages(), MINUTE_IN_SECONDS );
	}

	/**
	 * It sets a transient for the log file.
	 */
	public function cf7_smtp_wp_mail_log() {
		set_transient( 'cf7_smtp_testing_log', $this->cf7_smtp_log, MINUTE_IN_SECONDS );
	}


	/**
	 * It replaces the {{message}} placeholder in the template with the actual message, and then replaces the {{subject}}
	 * placeholder with the actual subject
	 *
	 * @param array  $mail_data The components of the email.
	 *        //  $components = array(
	 *        //      'subject'  => string,
	 *        //      'body'     => string,
	 *        //      'language' => language,
	 *        //  );.
	 * @param string $template The content of the template as literal.
	 *
	 * @return string The body of the email.
	 */
	public function cf7_smtp_form_template( array $mail_data, string $template ): string {

		if ( empty( $template ) ) {
			return $mail_data['body'];
		}

		/* htmlize the mail content */
		$mail_body = ! empty( $mail_data['body'] ) ? wp_kses_post( $mail_data['body'] ) : '';

		/* if the mail body is available replace the message in body */
		$mail_body = $mail_data['body'] ? str_replace( '{{message}}', $mail_body, $template ) : $template;

		/**
		 * Set the mail replacements tags
		 *
		 * @param array $mail_tags an array where the key is the needle and the value is what has to be replaced in content
		 * @param string $template_name the name of the template without ".html"
		 */
		$template_replacements = apply_filters(
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
			sanitize_file_name( $template . '.html' )
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
	 * @ref WPBP/template https://github.com/WPBP/template/blob/master/template.php
	 *
	 * @param string $template_name The name of the template file.
	 * @param int    $id The template that refers to contact form id.
	 * @param string $lang The template language.
	 *
	 * @return false|string The contents of the file.
	 */
	public function cf7_smtp_get_email_style( string $template_name, int $id, string $lang ) {

		$theme_custom_dir    = 'cf7-smtp/';
		$plugin_template_dir = CF7_SMTP_PLUGIN_ROOT . 'templates/';

		/* Look in yourtheme/cf7-smtp/template-name-id.php and yourtheme/cf7-smtp/template-name.php */
		if ( $id ) {
			$template = locate_template( array( "{$template_name}-{$id}.html", $theme_custom_dir . "{$template_name}-{$id}.html" ) );
		} else {
			$template = locate_template( array( "{$template_name}.html", $theme_custom_dir . "{$template_name}.html" ) );
		}

		/* Get default template_name-id.php */
		if ( ! $template && empty( $id ) ) {
			$template = $plugin_template_dir . "{$template_name}-{$id}.html";
		}

		/* Get default template_name.php */
		if ( ! $template && file_exists( $plugin_template_dir . "{$template_name}.html" ) ) {
			$template = $plugin_template_dir . "{$template_name}.html";
		}

		/**
		 * Allows user and 3rd party plugin to filter the template file
		 *
		 * @since 0.0.1
		 *
		 * @param string $template the filename of the template
		 * @param string $template_name the name of the template
		 * @param string $id the contact form id
		 * @param string $lang the contact form language
		 */
		$template = apply_filters( 'cf7_smtp_mail_template', $template, $template_name, $id, $lang, 'cf7-smtp' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return ! empty( $template ) ? file_get_contents( $template ) : '';
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
	public function cf7_smtp_email_style( array $components, WPCF7_ContactForm $contact_form, WPCF7_Mail $mail ): array {

		if ( empty( $this->options['custom_template'] ) ) {
			return $components;
		}

		$email_data = array(
			'body'     => nl2br( $components['body'] ),
			'subject'  => $components['subject'],
			'language' => $contact_form->locale(),
		);

		if ( ! empty( $components['body'] ) ) {

			$components['body'] = self::cf7_smtp_form_template(
				/**
				 * Allows user and 3rd party plugin to filter the template file
				 *
				 * @since 0.0.1
				 *
				 * @param string $template the filename of the template
				 * @param string $template_name the name of the template
				 * @param string $id the contact form id
				 * @param string $lang the contact form language
				 */
				apply_filters( 'cf7_smtp_mail_components', $email_data, $contact_form, $mail ),
				self::cf7_smtp_get_email_style( 'default', $contact_form->id(), $contact_form->locale() )
			);
		}

		return $components;
	}

	/**
	 * It returns the value of the key in the CF7_SMTP_SETTINGS array if it exists, otherwise it returns the value of the key
	 * in the $options array if it exists, otherwise it returns an empty string
	 *
	 * @param string      $key The key of the setting you want to retrieve.
	 * @param array|false $options The options array.
	 *
	 * @return string The value of the key in the array.
	 */
	public function cf7_smtp_get_setting_by_key( $key, $options = false ) {
		$options = ! empty( $options ) ? $options : $this->options;
		if ( ! empty( CF7_SMTP_SETTINGS ) && isset( CF7_SMTP_SETTINGS[ $key ] ) && CF7_SMTP_SETTINGS[ $key ] ) {
			return CF7_SMTP_SETTINGS[ $key ];
		} elseif ( isset( $options[ $key ] ) ) {
			return $options[ $key ];
		}
		return '';
	}

	/**
	 * It overrides the default WordPress mailer with the SMTP mailer.
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer object.
	 *
	 * @throws Exception May fail and throw an exception.
	 */
	public function cf7_smtp_overrides( PHPMailer\PHPMailer $phpmailer ) {
		try {
			// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Check if SMTP is enabled
			if ( empty( $this->options['enabled'] ) ) {
				return; // Exit early if SMTP is not enabled
			}

			$phpmailer->isSMTP();

			// Get and validate settings
			$auth = $this->cf7_smtp_get_setting_by_key( 'auth' );
			$username = sanitize_text_field( $this->cf7_smtp_get_setting_by_key( 'user_name' ) );

			// Get password with proper fallback
			$password = '';
			if ( ! empty( CF7_SMTP_SETTINGS ) && isset( CF7_SMTP_SETTINGS['user_pass'] ) ) {
				$password = CF7_SMTP_SETTINGS['user_pass'];
			} elseif ( ! empty( $this->options['user_pass'] ) ) {
				$password = cf7_smtp_decrypt( $this->options['user_pass'] );
			}

			$host = sanitize_text_field( $this->cf7_smtp_get_setting_by_key( 'host' ) );
			$port = intval( $this->cf7_smtp_get_setting_by_key( 'port' ) );
			$insecure = intval( $this->cf7_smtp_get_setting_by_key( 'insecure' ) );
			$from_mail = sanitize_email( $this->cf7_smtp_get_setting_by_key( 'from_mail' ) );
			$from_name = sanitize_text_field( $this->cf7_smtp_get_setting_by_key( 'from_name' ) );
			$reply_to = intval( $this->cf7_smtp_get_setting_by_key( 'replyTo' ) );

			// Validate required settings
			if ( empty( $host ) ) {
				throw new Exception( 'SMTP Host is required but not configured.' );
			}

			// Set host
			$phpmailer->Host = $host;

			// Set port with defaults based on an encryption type
			if ( ! empty( $port ) && $port > 0 && $port <= 65535 ) {
				$phpmailer->Port = $port;
			} else {
				// Set default ports based on encryption
				if ( $auth === 'ssl' ) {
					$phpmailer->Port = 465;
				} elseif ( $auth === 'tls' ) {
					$phpmailer->Port = 587;
				} else {
					$phpmailer->Port = 25;
				}
			}

			// Handle encryption (your original $auth variable seems to contain an encryption type)
			if ( ! empty( $auth ) ) {
				if ( 'tls' === $auth || 'ssl' === $auth ) {
					$phpmailer->SMTPSecure = $auth;
				}
			}

			// Set authentication - Enable if username and password are provided
			if ( ! empty( $username ) && ! empty( $password ) ) {
				$phpmailer->SMTPAuth = true;
				$phpmailer->Username = $username;
				$phpmailer->Password = $password;
			}

			// Handle insecure connections
			if ( ! empty( $insecure ) ) {
				$phpmailer->SMTPAutoTLS = false;
				$phpmailer->SMTPOptions = array(
					'ssl' => array(
						'verify_peer'       => false,
						'verify_peer_name'  => false,
						'allow_self_signed' => true,
					),
				);
			}

			// Set timeout (important for some providers)
			$phpmailer->Timeout = 30;

			// Enable verbose debug output if testing
			$verbose = get_transient( 'cf7_smtp_testing' );
			if ( $verbose ) {
				$phpmailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
				$phpmailer->Debugoutput = function ( $str, $level ) {
					$this->cf7_smtp_log .= "$level: $str\n";
				};
			}

			/**
			 * Set From address with validation (email and name).
			 *
			 * The third parameter has to be set too to false in order to not override the Sender header
			 * https://developer.wordpress.org/reference/hooks/phpmailer_init/#comment-2878
			 */
			if ( ! empty( $from_mail ) && is_email( $from_mail ) ) {
				$phpmailer->setFrom( $from_mail, $from_name, false );
			} else {
				// Use WordPress default if from_mail is invalid
				$default_from = get_option( 'admin_email' );
				if ( is_email( $default_from ) ) {
					$phpmailer->setFrom( $default_from, get_bloginfo( 'name' ), false );
				}
			}

			// Set Reply-To
			if ( ! empty( $reply_to ) ) {
				$reply_to_mail = ! empty( $from_mail ) && is_email( $from_mail ) ? $from_mail : $phpmailer->From;
				$reply_to_name = ! empty( $from_name ) ? $from_name : $phpmailer->FromName;

				if ( is_email( $reply_to_mail ) ) {
					$phpmailer->addReplyTo( $reply_to_mail, $reply_to_name );
				}
			}

			// Set additional headers that some providers require
			$phpmailer->XMailer = 'WordPress/' . get_bloginfo( 'version' ); // phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		} catch ( Exception $e ) {
			error_log( 'CF7 SMTP Configuration Error: ' . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * If the user has chosen a custom template, force the email to be sent as HTML if the email body contains the string
	 * <html
	 *
	 * @param PHPMailer\PHPMailer $phpmailer The PHPMailer object that is being used to send the email.
	 */
	public function cf7_smtp_apply_template( PHPMailer\PHPMailer $phpmailer ) {
		/* Force html if the user has chosen a custom template */
		if ( ! empty( $this->options['custom_template'] ) ) {
			if ( preg_match( '/<html /mi', $phpmailer->Body ) ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$phpmailer->isHTML();
			}
		}
	}
}
