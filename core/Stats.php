<?php
/**
 * CF7_SMTP Stats
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
 * Handles Statistics for this plugin
 */
class Stats extends Base
{

	/**
	 * The report
	 *
	 * @var array
	 */
	private $report = null;

	public function __construct()
	{
		$this->report = get_option('cf7-smtp-report');

		// Initialize with default structure if option doesn't exist or is invalid
		if (!is_array($this->report)) {
			$this->report = array(
				'success' => 0,
				'failed' => 0,
				'storage' => array(),
			);
			update_option('cf7-smtp-report', $this->report);
		}

		// Ensure all required keys exist
		if (!isset($this->report['success'])) {
			$this->report['success'] = 0;
		}
		if (!isset($this->report['failed'])) {
			$this->report['failed'] = 0;
		}
		if (!isset($this->report['storage'])) {
			$this->report['storage'] = array();
		}
	}

	public function has_report()
	{
		return !empty($this->report);
	}

	public function get_report()
	{
		return $this->report;
	}

	public function reset_report()
	{
		$this->report = array(
			'success' => 0,
			'failed' => 0,
			'storage' => $this->report['storage']
		);
		update_option('cf7-smtp-report', $this->report);
	}

	public function cleanup_storage($time)
	{
		$this->report['storage'] = array_filter($this->report['storage'], function ($value) use ($time) {
			return $value['time'] > $time;
		});
	}

	public function store()
	{
		update_option('cf7-smtp-report', $this->report);
	}

	public function get_success()
	{
		return $this->report['success'];
	}

	public function get_failed()
	{
		return $this->report['failed'];
	}

	public function get_storage()
	{
		return $this->report['storage'];
	}

	public function add_field_to_storage($time, $value)
	{
		$this->report['storage'][$time] = $value;
	}

	public function add_failed()
	{
		$this->report['failed'] = ++$this->report['failed'];
	}

	public function add_success()
	{
		$this->report['success'] = ++$this->report['success'];
	}
}
