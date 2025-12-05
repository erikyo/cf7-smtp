<?php

/**
 * CF7_SMTP Cron
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

use cf7_smtp\Engine\Base;

/**
 * The various Cron of this plugin
 */
class Cron extends Base
{


	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize()
	{
		add_action('cf7_smtp_report', array($this, 'cf7_smtp_send_report'));

		add_filter('cron_schedules', array($this, 'cf7_smtp_add_cron_steps'));
	}

	/**
	 * It adds two new cron schedules to WordPress
	 *
	 * @param array $schedules This is the name of the hook that we're adding a schedule to.
	 */
	public function cf7_smtp_add_cron_steps($schedules): array
	{
		if (!isset($schedules['2weeks'])) {
			$schedules['2weeks'] = array(
				'interval' => WEEK_IN_SECONDS * 2,
				'display' => __('Every 2 weeks', 'cf7-smtp'),
			);
		}
		if (!isset($schedules['month'])) {
			$schedules['month'] = array(
				'interval' => MONTH_IN_SECONDS,
				'display' => __('Every month', 'cf7-smtp'),
			);
		}
		return $schedules;
	}

	/**
	 * It sends a report of the number of successful and failed emails sent by Contact Form 7 to the email address specified
	 * in the plugin settings
	 */
	public function cf7_smtp_send_report()
	{
		$stats = new Stats();
		$stats->send_report();
	}
}
