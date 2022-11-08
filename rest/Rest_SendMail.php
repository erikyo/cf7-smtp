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

namespace cf7_smtp\Rest;

use cf7_smtp\Core\Mailer;
use cf7_smtp\Engine\Base;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Example class for REST
 */
class Rest_SendMail extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
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
			[
				'methods'             => 'POST',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'callback'            => [ $this, 'smtp_sendmail' ],
				'args'                => [
					'nonce' => [
						'required' => true,
					],
				],
			]
		);
	}

	private function cf7_smtp_testmailer( $mail_data, $mail_headers ) {

		/* the destination mail is mandatory */
		if ( empty( $mail_data['email'] ) ) {
			error_log(print_r("email missing ", true));
			return false;
		}

		/* allows to change the "from" */
		$headers = '';
		if ( ! empty( $mail_headers ) ) {
			$headers = "From: {$mail_headers['from_user']} <{$mail_headers['from_mail']}>" . "\r\n";
		}

		/* store the testing flag temporally */
		set_transient( 'cf7_smtp_testing', true, MINUTE_IN_SECONDS );

		/* adds the mail template (if enabled) */
		if ( ! empty( $this->options['custom_template'] ) ) {
			$smtp_mailer       = new Mailer();
			$mail_data['body'] = $smtp_mailer->cf7_smtp_form_template(
				[
					'body'     => $mail_data['body'] ?? 'mail body missing',
					'subject'  => $mail_data['subject'] ?? false,
					'language' => 'en',
				],
				'test.html'
			);
		}

		ob_start();
		wp_mail(
			$mail_data['email'],
			$mail_data['subject'],
			$mail_data['body'],
			$headers
		);
		$mail_result = ob_get_contents();
		ob_end_clean();

		return $mail_result;
	}


	/**
	 * smtp_sendmail
	 *
	 * @param WP_REST_Request $request Values.
	 *
	 * @return WP_REST_Response|WP_REST_Request - the rest request to send a mail
	 *
	 * @since 0.0.1
	 */
	public function smtp_sendmail( WP_REST_Request $request ) {

		$json_params = $request->get_json_params();

		// array('name-from' => $json_params['name-from'],'email-from' => $json_params['email-from'])
		// $from_email = apply_filters( 'wp_mail_from', $from_email );
		// $from_name = apply_filters( 'wp_mail_from_name', $from_name );

		if ( ! empty( $json_params['email'] ) ) {

			$r = self::cf7_smtp_testmailer(
				[
					'email'   => $json_params['email'],
					'subject' => ! empty( $json_params['subject'] ) ? $json_params['subject'] : 'Test message delivered! 🎉',
					'body'    => ! empty( $json_params["body'"] ) ? $json_params["body'"] : '',
				],
				[
					'from_user' => ! empty( $json_params['name-from'] ) ? $json_params['name-from'] : $this->options['from_name'],
					'from_mail' => ! empty( $json_params['email-from'] ) ? $json_params['email-from'] : $this->options['from_email'],
				]
			);

			if ( ! empty( $r ) ) {
				return \rest_ensure_response(
					[ 'message' => $r, ]
				);
			}
		}

		$response = \rest_ensure_response(
			[ 'message' => 'error', ]
		);

		$response->set_status( 500 );

		return $response;

	}

}
