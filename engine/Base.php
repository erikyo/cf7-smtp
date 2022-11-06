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

namespace cf7_smtp\Engine;

/**
 * Base skeleton of the plugin
 */
class Base {

	/**
	 * @var array The settings of the plugin.
	 */
	public $options = array();

	/**
	 * Initialize the class and get the plugin settings
	 *
	 * @return bool
	 */
	public function initialize() {
		$this->options = \c_get_settings();

		return true;
	}

}
