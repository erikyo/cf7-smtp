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

$c_debug = new WPBP_Debug( __( 'cf7-smtp', C_TEXTDOMAIN ) );

/**
 * Log text inside the debugging plugins.
 *
 * @param string $text The text.
 * @return void
 */
function c_log( string $text ) {
	global $c_debug;
	$c_debug->log( $text );
}
