<?php
/**
 * PHPMailer OAuthTokenProvider Interface
 *
 * WordPress's bundled PHPMailer references this interface in its setOAuth()
 * method but does not ship the interface file. This file provides it so
 * the cf7-smtp plugin can use XOAUTH2 authentication.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

if ( ! interface_exists( 'cf7_smtp\\Core\\OAuthTokenProvider' ) ) {
	interface OAuthTokenProvider {
		/**
		 * Generate a base64-encoded OAuth token string for SMTP XOAUTH2.
		 *
		 * @return string The base64-encoded OAuth token
		 */
		public function get_oauth64(): string;
	}
}
