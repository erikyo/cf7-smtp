<?php

/**
 * CF7_SMTP notifications
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;

/**
 * Everything that involves notification on the WordPress dashboard
 */
class Notices extends Base {


	/**
	 * Initialize the class
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}
	}

	/**
	 * It displays a message on the admin page
	 *
	 * @param string $message The message you want to display.
	 * @param string $type The type of notification between: error, warning, success, info. Default error.
	 */
	public function cf7_smtp_admin_notice( $message, $type = 'error' ) {
		$class = "notice notice-$type";

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}
}
