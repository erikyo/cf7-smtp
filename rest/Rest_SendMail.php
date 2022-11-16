<?php
/**
 * CF7_SMTP Rest api endpoints
 * provides cf7-smtp/v1/sendmail/ and cf7-smtp/v1/get_errors/
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Rest;

use cf7_smtp\Core\Mailer;
use cf7_smtp\Engine\Base;

use WP_Error;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Example class for REST
 */
class Rest_SendMail extends Base {

	/**
	 * Initialize the class and get the plugin settings
	 */
	public function initialize() {
		parent::initialize();

		\add_action( 'rest_api_init', array( $this, 'add_sendmail_api' ) );

	}

	/**
	 * Examples
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_sendmail_api() {
		$this->add_smtp_mail_route();
	}

	/**
	 * Examples
	 *
	 * @return void
	 * @since 0.0.1
	 *
	 *  Make an instance of this class somewhere, then
	 *  call this method and test on the command line with
	 * `curl http://example.com/wp-json/wp/v2/calc?first=1&second=2`
	 */
	public function add_smtp_mail_route() {
		\register_rest_route(
			'cf7-smtp/v1',
			'/sendmail/',
			array(
				'methods'             => 'POST',
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'callback'            => array( $this, 'smtp_sendmail' ),
				'args'                => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
		\register_rest_route(
			'cf7-smtp/v1',
			'/get_errors/',
			array(
				'methods'             => 'POST',
				'permission_callback' => function() {
					return current_user_can( 'manage_options' );
				},
				'callback'            => array( $this, 'smtp_sendmail_get_errors' ),
				'args'                => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * It returns an array of the values of the keys 'email', 'subject', 'body', 'from_name', and 'from_mail' of the array
	 * $mail_data, or if the key is not set, the value of the corresponding key in the array $this->options
	 *
	 * @param array $mail_data This is an array of the data that was sent to the function.
	 * @param array $mail_headers The mail headers that we want to append.
	 *
	 * @return array an array of the email, subject, body, from_name, and from_mail.
	 */
	private function cf7_smtp_testmailer_fill_data( array $mail_data, array $mail_headers ) {
		$mailer = new Mailer();
		return array(
			'email'     => ! empty( $mail_data['email'] ) ? sanitize_email( $mail_data['email'] ) : $mailer->cf7_smtp_get_setting_by_key( 'email', $this->options ),
			'subject'   => ! empty( $mail_data['subject'] ) ? sanitize_text_field( $mail_data['subject'] ) : esc_html__( 'no subject provided', CF7_SMTP_TEXTDOMAIN ),
			'body'      => ! empty( $mail_data['body'] ) ? wp_kses_post( $mail_data['body'] ) : esc_html__( 'mail body not provided', CF7_SMTP_TEXTDOMAIN ),
			'from_mail' => ! empty( $mail_headers['from_mail'] ) ? sanitize_email( $mail_headers['from_mail'] ) : false,
			'from_name' => ! empty( $mail_headers['from_name'] ) ? sanitize_text_field( $mail_headers['from_name'] ) : false,
		);
	}


	/**
	 * It sends a test email to the email address provided by the user
	 *
	 * @param array $mail_data an array containing the following keys.
	 * @param array $mail_headers an array of headers to be used in the email.
	 *
	 * @return string The log of wp_mail
	 */
	private function cf7_smtp_testmailer( array $mail_data, array $mail_headers ) {

		$mail = $this->cf7_smtp_testmailer_fill_data( $mail_data, $mail_headers );

		/* the destination mail is mandatory */
		if ( empty( $mail['email'] ) ) {
			cf7_smtp_log( 'you need to fill the "email" field in order to decide where the mail has to be received' );
			return "⚠️ The recipient mail is missing!\r\n";
		}

		/* allows to change the "from" if the user has chosen to override WordPress data */
		$headers = '';
		/* Setting the "from" (email and name). */
		if ( ! empty( $mail['from_mail'] ) ) {
			$headers .= sprintf( "From: %s <%s>\r\n", $mail['from_name'], $mail['from_mail'] );
		}

		/* adds the mail template (if enabled) */
		if ( ! empty( $this->options['custom_template'] ) ) {

			/* The custom template the content type needs to be set as html */
			$headers .= 'Content Type: text/html';

			/* apply the custom test template */
			$smtp_mailer  = new Mailer();
			$mail['body'] = $smtp_mailer->cf7_smtp_form_template(
				array(
					'body'    => $mail['body'],
					'subject' => $mail['subject'],
				),
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				file_get_contents( CF7_SMTP_PLUGIN_ROOT . 'templates/test.html' )
			);
		}

		/* if needed catch the error of wp_mail and return it to the user. */
		$mail_log = '';
		ob_start();
		try {
			// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
			$mail_sent = wp_mail(
				$mail['email'],
				$mail['subject'],
				$mail['body'],
				$headers
			);
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			echo "🆘 Something went wrong\r\n";
		}
		$mail_log .= ob_get_contents();
		ob_end_clean();

		if ( ! empty( $mail_sent ) && empty( $mail_log ) ) {
			/** As wp_mail docbloc says:
			 * A true return value does not automatically mean that the user received the
			 * email successfully. It just only means that the method used was able to
			 * process the request without any errors.
			 */
			$mail_log = esc_html( gmdate( 'Y-m-d h:i:s' ) . ' ' . esc_html__( 'Mail processed without errors', CF7_SMTP_TEXTDOMAIN ) );
		}

		return $mail_log;
	}

	/**
	 * It returns the error message from the transient if it exists, otherwise it returns an error message saying that it
	 * cannot find any log
	 *
	 * @param array $request The http request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response The response is being returned.
	 */
	public function smtp_sendmail_get_errors( $request ) {

		if ( ! \wp_verify_nonce( \strval( $request['nonce'] ), CF7_SMTP_TEXTDOMAIN ) ) {
			$response = \rest_ensure_response( 'Wrong nonce' );

			if ( \is_wp_error( $response ) ) {
				return $response;
			}

			$response->set_status( 500 );

			return $response;

		}

		$err_msg = get_transient( 'cf7_smtp_testing_error' );
		if ( ! empty( $err_msg ) ) {
			$response = \rest_ensure_response(
				array(
					'status'  => 'success',
					'message' => $err_msg,
				)
			);
			delete_transient( 'cf7_smtp_testing_error' );
		} else {
			$response = \rest_ensure_response(
				array(
					'status'  => 'wait',
					'message' => __( 'Still no Server response', CF7_SMTP_TEXTDOMAIN ),
				)
			);
		}

		$response->set_status( 200 );

		return $response;
	}


	/**
	 * The rest endpoint that send the email
	 *
	 * @param WP_REST_Request $request Values.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response - the rest request to send a mail
	 *
	 * @since 0.0.1
	 */
	public function smtp_sendmail( WP_REST_Request $request ) {

		$json_params = $request->get_json_params();

		$options = cf7_smtp_get_settings();

		if ( ! \wp_verify_nonce( \strval( $request['nonce'] ), CF7_SMTP_TEXTDOMAIN ) ) {
			$response = \rest_ensure_response( 'Wrong nonce' );

			if ( \is_wp_error( $response ) ) {
				return $response;
			}

			$response->set_status( 500 );

			return $response;

		}

		if ( ! empty( $json_params['email'] ) ) {

			$smtp_mailer = new Mailer();

			set_transient( 'cf7_smtp_testing', true, MINUTE_IN_SECONDS );

			$r = self::cf7_smtp_testmailer(
				array(
					'email'   => $json_params['email'],
					'subject' => ! empty( $json_params['subject'] ) ? $json_params['subject'] : 'Test message works! 🎉',
					'body'    => ! empty( $json_params['body'] ) ? $json_params['body'] : '',
				),
				array(
					'from_name' => ! empty( $json_params['name-from'] ) ? $json_params['name-from'] : $smtp_mailer->cf7_smtp_get_setting_by_key( 'from_name' ),
					'from_mail' => ! empty( $json_params['email-from'] ) ? $json_params['email-from'] : $smtp_mailer->cf7_smtp_get_setting_by_key( 'from_mail' ),
				)
			);

			if ( ! empty( $r ) ) {

				$response = \rest_ensure_response(
					array(
						'status'   => 'success',
						'protocol' => ! empty( $options['enabled'] ) ? 'SMTP' : 'PHPMAILER',
						'message'  => $r,
					)
				);

				$response->set_status( 200 );

			} else {

				$response = \rest_ensure_response(
					array(
						'status'    => 'error',
						'protocol'  => ! empty( $options['enabled'] ) ? 'SMTP' : 'PHPMAILER',
						'message'   => 'Empty response',
						'mail_data' => $json_params,
						'nonce'     => wp_create_nonce( CF7_SMTP_TEXTDOMAIN ),
					)
				);

				$response->set_status( 200 );

			}

			delete_transient( 'cf7_smtp_testing' );

		} else {

			$response = \rest_ensure_response(
				array(
					'status'   => 'error',
					'protocol' => ! empty( $options['enabled'] ) ? 'SMTP' : 'PHPMAILER',
					'message'  => 'Destination Email missing',
					'nonce'    => wp_create_nonce( CF7_SMTP_TEXTDOMAIN ),
				)
			);

			$response->set_status( 500 );

		}

		return $response;

	}

}
