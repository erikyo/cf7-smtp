<?php
/**
 * PHPMailer OAuthTokenProvider Interface Definition
 *
 * @package   cf7_smtp
 */

namespace PHPMailer\PHPMailer;

/**
 * OAuthTokenProvider - OAuth token provider interface for PHPMailer.
 *
 * WordPress's bundled PHPMailer references this interface in setOAuth()
 * but does not include this file. This definition allows plugins to use
 * XOAUTH2 authentication with PHPMailer.
 */
interface OAuthTokenProvider {
	/**
	 * Generate a base64-encoded OAuth token string for SMTP XOAUTH2.
	 *
	 * @return string The base64-encoded OAuth token
	 */
	public function getOauth64(): string;
}
