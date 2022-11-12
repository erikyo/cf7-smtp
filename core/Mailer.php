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
	 * the tags allowed for the mail content.
	 *
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

		/**
		 * Filter the mail content to allow only certain tags
		 *
		 * @since 0.0.1
		 *
		 * @param array $mail_allowed_tags a wp_kses formatted array of tags and properties
		 */
		$this->mail_allowed_tags = apply_filters(
			'cf7_smtp_mail_allowed_tags',
			array(
				'table'  => array(),
				'tr'     => array(),
				'td'     => array(),
				'div'    => array(),
				'span'   => array(),
				'br'     => array(),
				'hr'     => array(),
				'b'      => array(),
				'p'      => array(),
				'a'      => array(
					'href'   => array(),
					'target' => array( '_blank', '_top' ),
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

		\add_action( 'wpcf7_mail_sent', array( $this, 'cf7_smtp_wp_mail_succeeded' ) );
		\add_action( 'wpcf7_mail_failed', array( $this, 'cf7_smtp_wp_mail_failed' ) );

		\add_action(
			'wp_mail_failed',
			function ( $error ) {
				set_transient( 'cf7_smtp_testing_error', $error, MINUTE_IN_SECONDS );
				cf7_smtp_log( $error );
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
	 * Fired when the mail has succeeded
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @TODO: with the contact form instance we could build better stats in the future.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_succeeded( $contact_form ) {
		$report                      = get_option( 'cf7_smtp_report' );
		$report['storage'][ time() ] = array(
			'mail_sent' => true,
			'form_id'   => $contact_form->id(),
		);
		$report['sent']              = ++$report['sent'];
		update_option( 'cf7_smtp_report', $report );
	}

	/**
	 * Fired when the mail has failed
	 *
	 * @param WPCF7_ContactForm $contact_form The contact form that has sent the email.
	 *
	 * @return void
	 */
	public function cf7_smtp_wp_mail_failed( $contact_form ) {
		$report                      = get_option( 'cf7_smtp_report' );
		$report['storage'][ time() ] = array(
			'mail_sent' => false,
			'id'        => $contact_form->id(),
		);
		$report['failed']            = ++$report['failed'];
		update_option( 'cf7_smtp_report', $report );
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
		$mail_body = ! empty( $mail_data['body'] ) ? nl2br( wp_kses_post( $mail_data['body'] ) ) : '';

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

		$theme_custom_dir    = CF7_SMTP_TEXTDOMAIN . '/';
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
		 * @param string $CF7_SMTP_TEXTDOMAIN cf7-smtp slug
		 */
		$template = apply_filters( 'cf7_smtp_mail_template', $template, $template_name, $id, $lang, CF7_SMTP_TEXTDOMAIN );

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
			'body'     => $components['body'],
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
				 * @param string $CF7_SMTP_TEXTDOMAIN cf7-smtp slug
				 */
				apply_filters( 'cf7_smtp_mail_components', $email_data, $contact_form, $mail ),
				self::cf7_smtp_get_email_style( 'default', $contact_form->id(), $contact_form->locale() )
			);
		}

		return $components;
	}

	public function cf7_smtp_get_setting_by_key( $key, $options = false ) {
		$options = ! empty( $options ) ? $options : $this->options;
		if ( ! empty( CF7_SMTP_SETTINGS ) && ! empty( CF7_SMTP_SETTINGS[ $key ] ) ) {
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

		$phpmailer->isSMTP();

		/* SSL or TLS, if necessary for your server */
		if ( ! empty( $this->options['auth'] ) ) {
			$phpmailer->SMTPAuth   = true;
			$phpmailer->SMTPSecure = $this->cf7_smtp_get_setting_by_key( 'auth' );
		}

		/*Host*/
		if ( ! empty( $this->options['host'] ) ) {
			$phpmailer->Host = sanitize_text_field( $this->cf7_smtp_get_setting_by_key( 'host' ) );
		}

		/*Port*/
		if ( $this->options['port'] ) {
			$phpmailer->Port = intval( $this->cf7_smtp_get_setting_by_key( 'port' ) );
		}

		/* Force it to use Username and Password to authenticate */
		if ( $this->options['user_name'] ) {
			$phpmailer->Username = sanitize_text_field( $this->cf7_smtp_get_setting_by_key( 'user_name' ) );
		}
		if ( $this->options['user_pass'] ) {
			if ( ! empty( CF7_SMTP_SETTINGS ) && ! empty( CF7_SMTP_SETTINGS['user_pass'] ) ) {
				$phpmailer->Password = CF7_SMTP_SETTINGS['user_pass'];
			} elseif ( ! empty( $this->options['user_pass'] ) ) {
				$phpmailer->Password = cf7_smtp_decrypt( $this->options['user_pass'] );
			} else {
				$phpmailer->Password = '';
			}
		}

		/* Enable verbose debug output */
		$verbose = get_transient( 'cf7_smtp_testing' );
		if ( $verbose ) {
			delete_transient( 'cf7_smtp_testing' );
			/* in very rare case this could be more useful but for the moment level 3 is sufficient - $phpmailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL; */
			$phpmailer->SMTPDebug = SMTP::DEBUG_CONNECTION;
		}

		/* Force html if the user has choosen a custom template */
		if ( ! empty( $this->options['custom_template'] ) ) {
			$phpmailer->isHTML();
		}

		/* Setting the "from" (email and name). */
		$from_mail = $this->cf7_smtp_get_setting_by_key( 'from_mail' );
		if ( ! empty( $from_mail ) ) {
			$from_name = $this->cf7_smtp_get_setting_by_key( 'from_name' );
			$phpmailer->setFrom( $from_mail, $from_name );
		}
	}
}
