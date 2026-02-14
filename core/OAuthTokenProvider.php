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

if ( ! interface_exists( 'PHPMailer\\PHPMailer\\OAuthTokenProvider' ) ) {
	require_once __DIR__ . '/PHPMailerOAuthTokenProvider.php';
}
