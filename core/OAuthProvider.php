<?php
/**
 * CF7_SMTP OAuth Provider
 *
 * Custom OAuth Token Provider for PHPMailer that uses League OAuth2 Client
 * to obtain fresh access tokens via refresh token grant.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

// Load the OAuthTokenProvider interface (PHPMailer\PHPMailer namespace, not autoloaded).
require_once __DIR__ . '/OAuthTokenProvider.php';

/**
 * Custom OAuth Token Provider for PHPMailer.
 * Implements PHPMailer\PHPMailer\OAuthTokenProvider using a pre-fetched access token
 * or falling back to refreshing via the League OAuth2 Client.
 */
class OAuthProvider implements \PHPMailer\PHPMailer\OAuthTokenProvider {
	/**
	 * The OAuth provider instance (League OAuth2 Client).
	 *
	 * @var object|null
	 */
	private ?object $provider;

	/**
	 * The refresh token used to obtain new access tokens.
	 *
	 * @var string
	 */
	private string $refresh_token;

	/**
	 * The user email address.
	 *
	 * @var string
	 */
	private string $email;

	/**
	 * A pre-fetched access token (already validated/refreshed by OAuth2_Handler).
	 *
	 * @var string
	 */
	private string $access_token;

	/**
	 * Constructor
	 *
	 * @param string      $email         The user email address.
	 * @param string      $access_token  A valid, pre-fetched access token.
	 * @param object|null $provider      The OAuth provider instance (for fallback refresh).
	 * @param string      $refresh_token The refresh token (for fallback refresh).
	 */
	public function __construct( string $email, string $access_token, ?object $provider = null, string $refresh_token = '' ) {
		$this->email         = $email;
		$this->access_token  = $access_token;
		$this->provider      = $provider;
		$this->refresh_token = $refresh_token;
	}

	/**
	 * Get the base64-encoded OAuth token string for PHPMailer XOAUTH2.
	 *
	 * This method is called by PHPMailer during SMTP authentication when
	 * AuthType is set to 'XOAUTH2'.
	 *
	 * @return string The base64-encoded OAuth token
	 * @throws \PHPMailer\PHPMailer\Exception If no valid access token is available.
	 */
	public function get_oauth64(): string {
		$token = $this->access_token;

		// If we don't have a pre-fetched token, try to refresh via the provider.
		if ( empty( $token ) && $this->provider && $this->refresh_token ) {
			try {
				$new_token = $this->provider->getAccessToken(
					'refresh_token',
					array(
						'refresh_token' => $this->refresh_token,
					)
				);
				$token     = $new_token->getToken();
			} catch ( \Exception $e ) {
				throw new \PHPMailer\PHPMailer\Exception( 'OAuth token retrieval failed: ' . esc_textarea( $e->getMessage() ) );
			}
		}

		if ( empty( $token ) ) {
			throw new \PHPMailer\PHPMailer\Exception( 'No valid OAuth access token available.' );
		}

		// Build the XOAUTH2 authentication string per RFC 7628
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode(
			'user=' . $this->email
							. "\001auth=Bearer " . $token
							. "\001\001"
		);
	}
}
