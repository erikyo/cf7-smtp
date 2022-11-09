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

namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;
use Yoast_I18n_WordPressOrg_v3;

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

		/*
		 * Alert after few days to suggest to contribute to the localization if it is incomplete
		 * on translate.wordpress.org, the filter enables to remove globally.
		 */
		if ( \apply_filters( 'cf7_smtp_alert_localization', true ) ) {
			new Yoast_I18n_WordPressOrg_v3(
				array(
					'textdomain' => CF7_SMTP_TEXTDOMAIN,
					'cf7_smtp'   => CF7_SMTP_NAME,
					'hook'       => 'admin_notices',
				),
				true
			);
		}

	}

}
