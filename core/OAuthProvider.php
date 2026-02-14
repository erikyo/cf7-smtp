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

require_once __DIR__ . '/OAuthTokenProvider.php';

/**
 * Custom OAuth Token Provider for PHPMailer.
 * Implements PHPMailer\PHPMailer\OAuthTokenProvider using League OAuth2 Client.
 */
class CF7_SMTP_OAuthProvider implements \PHPMailer\PHPMailer\OAuthTokenProvider {
	/**
	 * The OAuth provider instance (League OAuth2 Client).
	 *
	 * @var object
	 */
	private object $provider;

	/**
	 * The client ID.
	 *
	 * @var string
	 */
	private string $client_id;

	/**
	 * The client secret.
	 *
	 * @var string
	 */
	private string $client_secret;

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
	 * Constructor
	 *
	 * @param object $provider      The OAuth provider instance.
	 * @param string $client_id     The client ID.
	 * @param string $client_secret The client secret.
	 * @param string $refresh_token The refresh token.
	 * @param string $email         The user email address.
	 */
	public function __construct( object $provider, string $client_id, string $client_secret, string $refresh_token, string $email ) {
		$this->provider      = $provider;
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->refresh_token = $refresh_token;
		$this->email         = $email;
	}

	/**
	 * Get the base64-encoded OAuth token string for PHPMailer XOAUTH2.
	 *
	 * This method is called by PHPMailer during SMTP authentication when
	 * AuthType is set to 'XOAUTH2'.
	 *
	 * @return string The base64-encoded OAuth token
	 * @throws \PHPMailer\PHPMailer\Exception If the OAuth provider or refresh token is not configured.
	 */
	public function get_oauth64(): string {
		if ( ! $this->provider || ! $this->refresh_token ) {
			throw new \PHPMailer\PHPMailer\Exception( 'OAuth provider or refresh token not configured.' );
		}

		try {
			$new_access_token = $this->provider->getAccessToken(
				'refresh_token',
				array(
					'refresh_token' => $this->refresh_token,
				)
			);

			// Build the XOAUTH2 authentication string per RFC 7628
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			return base64_encode(
				'user=' . $this->email
								. "\001auth=Bearer " . $new_access_token->getToken()
								. "\001\001"
			);
		} catch ( \Exception $e ) {
			throw new \PHPMailer\PHPMailer\Exception( 'OAuth token retrieval failed: ' . esc_html( $e->getMessage() ) );
		}
	}
}
