<?php
/**
 * CF7_SMTP OAuth Token Provider Interface
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

use PHPMailer\PHPMailer\Exception;

/**
 * OAuth Token Provider Interface for PHPMailer
 */
interface CF7_SMTP_OAuthTokenProvider {
	/**
	 * Get the OAuth64 string for PHPMailer
	 *
	 * @return string The OAuth64 string
	 * @throws Exception If OAuth token retrieval fails.
	 */
	public function get_oauth64(): string;
}
