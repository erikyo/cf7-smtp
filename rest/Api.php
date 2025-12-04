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
class Api extends Base
{


	/**
	 * Initialize the class and get the plugin settings
	 */
	public function initialize()
	{
		parent::initialize();

		\add_action('rest_api_init', array($this, 'add_sendmail_api'));
	}

	/**
	 * Examples
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function add_sendmail_api()
	{
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
	public function add_smtp_mail_route()
	{
		\register_rest_route(
			'cf7-smtp/v1',
			'/sendmail/',
			array(
				'methods' => 'POST',
				'permission_callback' => function () {
					return current_user_can('manage_options');
				},
				'callback' => array($this, 'smtp_sendmail'),
				'args' => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
		\register_rest_route(
			'cf7-smtp/v1',
			'/get_log/',
			array(
				'methods' => 'POST',
				'permission_callback' => function () {
					return current_user_can('manage_options');
				},
				'callback' => array($this, 'smtp_sendmail_get_log'),
				'args' => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
		\register_rest_route(
			'cf7-smtp/v1',
			'/report/',
			array(
				'methods' => 'POST',
				'permission_callback' => function () {
					return current_user_can('manage_options');
				},
				'callback' => array($this, 'smtp_report'),
				'args' => array(
					'nonce' => array(
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * It returns an array of the values of the keys 'email', 'subject', 'body' of the array
	 * $mail_data, or if the key is not set, the value of the corresponding key in the array $this->options
	 *
	 * @param array $mail_data This is an array of the data that was sent to the function.
	 *
	 * @return array an array of the email, subject, body
	 */
	private function cf7_smtp_testmailer_fill_data(array $mail_data)
	{
		$mailer = new Mailer();
		return array(
			'email' => !empty($mail_data['email']) ? sanitize_email($mail_data['email']) : $mailer->get_setting_by_key('email', $this->options),
			'subject' => !empty($mail_data['subject']) ? sanitize_text_field($mail_data['subject']) : esc_html__('no subject provided', 'cf7-smtp'),
			'body' => !empty($mail_data['body']) ? wp_kses_post($mail_data['body']) : esc_html__('Empty mail body', 'cf7-smtp'),
			'from_mail' => $mailer->get_setting_by_key('from_mail', $this->options),
			'from_name' => $mailer->get_setting_by_key('from_name', $this->options),
			'headers' => '',
		);
	}


	/**
	 * It sends a test email to the email address provided by the user
	 *
	 * @param array $mail_data an array containing the following keys.
	 *
	 * @return string The log of wp_mail
	 */
	private function cf7_smtp_testmailer(array $mail_data)
	{

		$mail = $this->cf7_smtp_testmailer_fill_data($mail_data);

		/* the destination mail is mandatory */
		if (empty($mail['email'])) {
			cf7_smtp_log('you need to fill the "email" field in order to decide where the mail has to be received');
			return "⚠️ The recipient mail is missing!\r\n";
		}
		/* allows to change the "from" if the user has chosen to override WordPress data */
		/* Setting the "from" (email and name). */
		if (!empty($mail['from_mail'])) {
			$mail['headers'] = sprintf("From: %s <%s>\r\n", $mail['from_name'] ?? 'WordPress', $mail['from_mail']);
		}

		$smtp_mailer = new Mailer();
		$res = $smtp_mailer->send_email($mail);

		// Get the log from the mailer instance and set transient
		$log = $smtp_mailer->get_log();
		if (!empty($log)) {
			set_transient('cf7_smtp_testing_log', $log, MINUTE_IN_SECONDS);
		}

		if ($res) {
			return esc_html(gmdate('Y-m-d h:i:s') . ' ' . esc_html__('Mail Processed with success ✅', 'cf7-smtp')) . PHP_EOL;
		} else {
			return esc_html(gmdate('Y-m-d h:i:s') . ' ' . esc_html__('Mail processed with errors', 'cf7-smtp')) . PHP_EOL;
		}
	}

	/**
	 * It returns the error message from the transient if it exists, otherwise it returns an error message saying that it
	 * cannot find any log
	 *
	 * @param array $request The http request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response The response is being returned.
	 */
	public function smtp_sendmail_get_log($request)
	{

		if (!\wp_verify_nonce(\strval($request['nonce']), 'cf7-smtp')) {
			$response = \rest_ensure_response('Wrong nonce');

			if (\is_wp_error($response)) {
				return $response;
			}

			$response->set_status(500);

			return $response;
		}

		$err_msg = get_transient('cf7_smtp_testing_error');
		if (!empty($err_msg)) {

			$response = \rest_ensure_response(
				array(
					'status' => 'error',
					'message' => $err_msg,
				)
			);
			$response->set_status(200);
			delete_transient('cf7_smtp_testing_error');

			return $response;
		}

		$log = get_transient('cf7_smtp_testing_log');
		if (!empty($log)) {
			$response = \rest_ensure_response(
				array(
					'status' => 'log',
					'message' => esc_html($log),
				)
			);
			$response->set_status(200);
			delete_transient('cf7_smtp_testing_log');
		} else {
			$response = \rest_ensure_response(
				array(
					'status' => 'wait',
					'message' => esc_html__('Still no Server response', 'cf7-smtp') . PHP_EOL . $log,
				)
			);
			$response->set_status(200);
		}

		return $response;
	}

	private function smtp_report()
	{

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
	public function smtp_sendmail(WP_REST_Request $request)
	{

		$json_params = $request->get_json_params();

		$options = cf7_smtp_get_settings();

		if (!\wp_verify_nonce(\strval($request['nonce']), 'cf7-smtp')) {
			$response = \rest_ensure_response('Wrong nonce');

			if (\is_wp_error($response)) {
				return $response;
			}

			$response->set_status(500);

			return $response;
		}

		if (!empty($json_params['email'])) {

			set_transient('cf7_smtp_testing', true, MINUTE_IN_SECONDS);

			$phpmailer_resp = self::cf7_smtp_testmailer(
				array(
					'email' => $json_params['email'],
					'subject' => !empty($json_params['subject']) ? $json_params['subject'] : 'Test message works! 🎉',
					'body' => !empty($json_params['body']) ? $json_params['body'] : '',
				)
			);

			if (!empty($phpmailer_resp)) {

				$response = \rest_ensure_response(
					array(
						'status' => 'success',
						'protocol' => !empty($options['enabled']) ? 'SMTP' : 'PHPMAILER',
						'message' => wp_unslash($phpmailer_resp),
						'nonce' => wp_create_nonce('cf7-smtp'),
					)
				);
			} else {

				$response = \rest_ensure_response(
					array(
						'status' => 'log',
						'protocol' => !empty($options['enabled']) ? 'SMTP' : 'PHPMAILER',
						'message' => 'success',
						'mail_data' => $json_params,
						'nonce' => wp_create_nonce('cf7-smtp'),
					)
				);
			}

			$response->set_status(200);
		} else {

			$response = \rest_ensure_response(
				array(
					'status' => 'error',
					'protocol' => !empty($options['enabled']) ? 'SMTP' : 'PHPMAILER',
					'message' => 'Destination Email missing',
					'nonce' => wp_create_nonce('cf7-smtp'),
				)
			);

			$response->set_status(500);
		}

		return $response;
	}
}
