<?php
/**
 * CF7_SMTP engine class
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Engine;

/**
 * Base skeleton of the plugin
 */
class Base {

	/**
	 * The settings of the plugin.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Initialize the class and get the plugin settings
	 *
	 * @return void|boolean
	 */
	public function initialize() {
		$this->options = \cf7_smtp_get_settings();

		return true;
	}

}
