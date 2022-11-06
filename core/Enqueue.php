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

		\add_action( 'phpmailer_init', array( $this, 'cf7_phpmailer') );

		\add_action( 'wp_mail_failed', function ( $error ) {
			error_log( "cf7 smtp errors" );
			error_log( print_r( $error, true ) );
		} );
	}

	/**
	 * @throws Exception
	 */
	public function cf7_phpmailer( PHPMailer\PHPMailer $phpmailer ) {

		if ( ! empty( $this->options['enabled'] ) ) {
			$phpmailer->isSMTP();
			$phpmailer->Host = $this->options['host'];

			// Choose SSL or TLS, if necessary for your server
			$phpmailer->SMTPAuth = true;
			$phpmailer->SMTPSecure = $this->options['auth'];
			$phpmailer->Port = $this->options['port'];
			// Force it to use Username and Password to authenticate
			$phpmailer->Username = $this->options['user_name'];
			$phpmailer->Password = cf7_smtp_decrypt($this->options['user_pass']);

			//Enable verbose debug output
			$phpmailer->SMTPDebug = SMTP::DEBUG_LOWLEVEL;
		}


		if (!empty($this->options['from_mail']) && !empty($this->options['from_name'])){
			$phpmailer->setFrom( $this->options['from_mail'], $this->options['from_name'] );
		}
	}
}
