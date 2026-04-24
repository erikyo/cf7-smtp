<?php
/**
 * PHPMailer OAuthTokenProvider Interface
 *
 * WordPress's bundled PHPMailer references this interface in its setOAuth()
 * method but does not ship the interface file. This file provides it so
 * the cf7-smtp plugin can use XOAUTH2 authentication.
 *
 * The interface MUST be in the PHPMailer\PHPMailer namespace because
 * PHPMailer's setOAuth() method type-hints against
 * PHPMailer\PHPMailer\OAuthTokenProvider.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace PHPMailer\PHPMailer;

if ( ! interface_exists( 'PHPMailer\\PHPMailer\\OAuthTokenProvider' ) ) {
	interface OAuthTokenProvider {
		/**
		 * Generate a base64-encoded OAuth token string for SMTP XOAUTH2.
		 *
		 * @return string The base64-encoded OAuth token
		 */
		public function getOauth64(): string;
	}
}
