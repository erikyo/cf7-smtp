<?php
/**
 * CF7_SMTP OAuth2 Handler
 *
 * Handles OAuth2 authentication flow for SMTP email sending.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

use cf7_smtp\Engine\Base;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Grant\RefreshToken;

/**
 * OAuth2 Handler class for managing OAuth2 authentication.
 */
class OAuth2_Handler extends Base {

	/**
	 * Supported OAuth2 providers configuration.
	 *
	 * @var array
	 */
	private array $providers;

	/**
	 * Initialize the OAuth2 handler.
	 */
	public function __construct() {
		parent::initialize();

		$this->providers = array(
			'gmail'     => array(
				'name'         => 'Gmail',
				'class'        => Google::class,
				'host'         => 'smtp.gmail.com',
				'port'         => 587,
				'encryption'   => 'tls',
				'scopes'       => array( 'https://mail.google.com/' ),
				'auth_url'     => 'https://accounts.google.com/o/oauth2/v2/auth',
				'token_url'    => 'https://oauth2.googleapis.com/token',
				'redirect_uri' => admin_url( 'admin.php?page=cf7-smtp&oauth2_callback=1' ),
			),
			'office365' => array(
				'name'                         => 'Office 365',
				'class'                        => null,
				// Uses generic provider
										'host' => 'smtp.office365.com',
				'port'                         => 587,
				'encryption'                   => 'tls',
				'scopes'                       => array( 'https://outlook.office.com/SMTP.Send', 'offline_access' ),
				'auth_url'                     => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
				'token_url'                    => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
				'redirect_uri'                 => admin_url( 'admin.php?page=cf7-smtp&oauth2_callback=1' ),
			),
		);
	}

	/**
	 * Get available OAuth2 providers.
	 *
	 * @return array List of providers with name and key.
	 */
	public function get_providers(): array {
		$list = array();
		foreach ( $this->providers as $key => $config ) {
			$list[ $key ] = $config['name'];
		}
		return $list;
	}

	/**
	 * Get provider configuration.
	 *
	 * @param string $provider_key The provider key (gmail, office365).
	 *
	 * @return array|null Provider configuration or null if not found.
	 */
	public function get_provider_config( string $provider_key ): ?array {
		return $this->providers[ $provider_key ] ?? null;
	}

	/**
	 * Create OAuth2 provider instance.
	 *
	 * @param string $provider_key The provider key.
	 *
	 * @return \League\OAuth2\Client\Provider\AbstractProvider|null
	 */
	private function create_provider( string $provider_key ) {
		$config = $this->get_provider_config( $provider_key );
		if ( ! $config ) {
			return null;
		}

		$oauth2_data   = $this->get_oauth2_data();
		$client_id     = $oauth2_data['client_id'] ?? '';
		$client_secret = cf7_smtp_decrypt( $oauth2_data['client_secret'] ?? '' );

		if ( empty( $client_id ) || empty( $client_secret ) ) {
			return null;
		}

		if ( 'gmail' === $provider_key ) {
			return new Google(
				array(
					'clientId'     => $client_id,
					'clientSecret' => $client_secret,
					'redirectUri'  => $config['redirect_uri'],
					'accessType'   => 'offline',
					'prompt'       => 'consent',
				)
			);
		}

		// For Office 365 and other providers, use GenericProvider
		return new \League\OAuth2\Client\Provider\GenericProvider(
			array(
				'clientId'                => $client_id,
				'clientSecret'            => $client_secret,
				'redirectUri'             => $config['redirect_uri'],
				'urlAuthorize'            => $config['auth_url'],
				'urlAccessToken'          => $config['token_url'],
				'urlResourceOwnerDetails' => '',
				'scopes'                  => $config['scopes'],
			)
		);
	}

	/**
	 * Get stored OAuth2 data.
	 *
	 * @return array OAuth2 data.
	 */
	public function get_oauth2_data(): array {
		$data = array();

		// Map main options to OAuth2 data structure.
		$keys = array( 'provider', 'access_token', 'refresh_token', 'expires', 'user_email', 'client_id', 'client_secret', 'connected_at' );

		foreach ( $keys as $key ) {
			if ( isset( $this->options[ 'oauth2_' . $key ] ) ) {
				$data[ $key ] = $this->options[ 'oauth2_' . $key ];
			}
		}

		return $data;
	}

	/**
	 * Save OAuth2 data.
	 *
	 * @param array $data OAuth2 data to save.
	 *
	 * @return bool Whether the data was saved successfully.
	 */
	public function save_oauth2_data( array $data ): bool {
		$options = get_option( 'cf7-smtp-options', array() );

		foreach ( $data as $key => $value ) {
			$options[ 'oauth2_' . $key ] = $value;
		}

		return update_option( 'cf7-smtp-options', $options );
	}

	/**
	 * Get the authorization URL for the OAuth2 flow.
	 *
	 * @param string $provider_key The provider key.
	 *
	 * @return string|null The authorization URL or null on error.
	 */
	public function get_authorization_url( string $provider_key ): ?string {
		$provider = $this->create_provider( $provider_key );
		if ( ! $provider ) {
			return null;
		}

		$config = $this->get_provider_config( $provider_key );

		$options = array(
			'scope' => $config['scopes'],
		);

		if ( 'gmail' === $provider_key ) {
			$options['access_type'] = 'offline';
			$options['prompt']      = 'consent';
		}

		$auth_url = $provider->getAuthorizationUrl( $options );
		$state    = $provider->getState();

		// Store state for verification.
		set_transient( 'cf7_smtp_oauth2_state', $state, 10 * MINUTE_IN_SECONDS );
		set_transient( 'cf7_smtp_oauth2_provider', $provider_key, 10 * MINUTE_IN_SECONDS );

		return $auth_url;
	}

	/**
	 * Handle the OAuth2 callback and exchange the code for tokens.
	 *
	 * @param string $code  The authorization code.
	 * @param string $state The state parameter for verification.
	 *
	 * @return array{success: bool, message: string, email?: string}
	 */
	public function handle_callback( string $code, string $state ): array {
		// Verify state.
		$stored_state = get_transient( 'cf7_smtp_oauth2_state' );
		if ( empty( $stored_state ) || $state !== $stored_state ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid state parameter. Please try again.', 'cf7-smtp' ),
			);
		}

		$provider_key = get_transient( 'cf7_smtp_oauth2_provider' );
		if ( empty( $provider_key ) ) {
			return array(
				'success' => false,
				'message' => __( 'Provider not found. Please try again.', 'cf7-smtp' ),
			);
		}

		// Clean up transients.
		delete_transient( 'cf7_smtp_oauth2_state' );
		delete_transient( 'cf7_smtp_oauth2_provider' );

		$provider = $this->create_provider( $provider_key );
		if ( ! $provider ) {
			return array(
				'success' => false,
				'message' => __( 'Could not create OAuth2 provider.', 'cf7-smtp' ),
			);
		}

		try {
			$token = $provider->getAccessToken( 'authorization_code', array( 'code' => $code ) );

			$access_token  = $token->getToken();
			$refresh_token = $token->getRefreshToken();
			$expires       = $token->getExpires();

			// Get user email for Gmail.
			$user_email = '';
			if ( 'gmail' === $provider_key ) {
				try {
					$resource_owner = $provider->getResourceOwner( $token );
					$user_email     = $resource_owner->getEmail();
				} catch ( \Exception $e ) {
					cf7_smtp_log( 'Could not get user email: ' . $e->getMessage() );
				}
			}

			// Store tokens.
			$this->save_oauth2_data(
				array(
					'provider'      => $provider_key,
					'access_token'  => cf7_smtp_crypt( $access_token ),
					'refresh_token' => cf7_smtp_crypt( $refresh_token ),
					'expires'       => $expires,
					'user_email'    => $user_email,
					'connected_at'  => time(),
				)
			);

			return array(
				'success' => true,
				'message' => __( 'Successfully connected!', 'cf7-smtp' ),
				'email'   => $user_email,
			);

		} catch ( \Exception $e ) {
			cf7_smtp_log( 'OAuth2 callback error: ' . $e->getMessage() );
			return array(
				'success' => false,
				'message' => sprintf(
					/* translators: %s: error message */
					__( 'OAuth2 error: %s', 'cf7-smtp' ),
					$e->getMessage()
				),
			);
		}//end try
	}

	/**
	 * Refresh the access token if expired.
	 *
	 * @return string|null The new access token or null on failure.
	 */
	public function refresh_token(): ?string {
		$oauth2_data = $this->get_oauth2_data();

		if ( empty( $oauth2_data['refresh_token'] ) || empty( $oauth2_data['provider'] ) ) {
			return null;
		}

		$provider = $this->create_provider( $oauth2_data['provider'] );
		if ( ! $provider ) {
			return null;
		}

		try {
			$refresh_token = cf7_smtp_decrypt( $oauth2_data['refresh_token'] );
			$grant         = new RefreshToken();

			$token = $provider->getAccessToken( $grant, array( 'refresh_token' => $refresh_token ) );

			$new_access_token  = $token->getToken();
			$new_refresh_token = $token->getRefreshToken() ?: $refresh_token;
			$expires           = $token->getExpires();

			// Update stored tokens.
			$this->save_oauth2_data(
				array(
					'access_token'  => cf7_smtp_crypt( $new_access_token ),
					'refresh_token' => cf7_smtp_crypt( $new_refresh_token ),
					'expires'       => $expires,
				)
			);

			return $new_access_token;

		} catch ( \Exception $e ) {
			cf7_smtp_log( 'Token refresh error: ' . $e->getMessage() );
			return null;
		}//end try
	}

	/**
	 * Get the current access token, refreshing if necessary.
	 *
	 * @return string|null The access token or null if not available.
	 */
	public function get_access_token(): ?string {
		$oauth2_data = $this->get_oauth2_data();

		if ( empty( $oauth2_data['access_token'] ) ) {
			return null;
		}

		$access_token = cf7_smtp_decrypt( $oauth2_data['access_token'] );
		$expires      = $oauth2_data['expires'] ?? 0;

		// Check if token is expired (with 5-minute buffer).
		if ( $expires && $expires < ( time() + 300 ) ) {
			$access_token = $this->refresh_token();
		}

		return $access_token;
	}

	/**
	 * Check if OAuth2 is connected and valid.
	 *
	 * @return bool True if connected with valid tokens.
	 */
	public function is_connected(): bool {
		$oauth2_data = $this->get_oauth2_data();

		if ( empty( $oauth2_data['access_token'] ) || empty( $oauth2_data['refresh_token'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the connected email address.
	 *
	 * @return string|null The connected email or null.
	 */
	public function get_connected_email(): ?string {
		$oauth2_data = $this->get_oauth2_data();
		return $oauth2_data['user_email'] ?? null;
	}

	/**
	 * Get the current provider key.
	 *
	 * @return string|null The provider key or null.
	 */
	public function get_current_provider(): ?string {
		$oauth2_data = $this->get_oauth2_data();
		return $oauth2_data['provider'] ?? null;
	}

	/**
	 * Disconnect OAuth2 and clear all stored data.
	 *
	 * @return bool Whether the disconnect was successful.
	 */
	public function disconnect(): bool {
		$options = get_option( 'cf7-smtp-options', array() );
		$keys    = array( 'provider', 'access_token', 'refresh_token', 'expires', 'user_email', 'client_id', 'client_secret', 'connected_at' );

		foreach ( $keys as $key ) {
			if ( isset( $options[ 'oauth2_' . $key ] ) ) {
				unset( $options[ 'oauth2_' . $key ] );
			}
		}

		return update_option( 'cf7-smtp-options', $options );
	}

	/**
	 * Get the OAuth2 provider instance for use with PHPMailer.
	 *
	 * @return \League\OAuth2\Client\Provider\AbstractProvider|null
	 */
	public function get_provider_instance() {
		$provider_key = $this->get_current_provider();
		if ( ! $provider_key ) {
			return null;
		}
		return $this->create_provider( $provider_key );
	}

	/**
	 * Get OAuth2 status information.
	 *
	 * @return array Status information.
	 */
	public function get_status(): array {
		$oauth2_data = $this->get_oauth2_data();
		$connected   = $this->is_connected();

		return array(
			'connected'     => $connected,
			'provider'      => $oauth2_data['provider'] ?? null,
			'user_email'    => $oauth2_data['user_email'] ?? null,
			'connected_at'  => $oauth2_data['connected_at'] ?? null,
			'expires'       => $oauth2_data['expires'] ?? null,
			'refresh_token' => $oauth2_data['refresh_token'] ?? null,
		);
	}

	/**
	 * Get PHPMailer OAuth configuration for use with XOAUTH2.
	 *
	 * @return array|null OAuth configuration or null if not available.
	 */
	public function get_phpmailer_oauth_config(): ?array {
		if ( ! $this->is_connected() ) {
			return null;
		}

		$oauth2_data  = $this->get_oauth2_data();
		$provider_key = $oauth2_data['provider'] ?? '';
		$config       = $this->get_provider_config( $provider_key );

		if ( ! $config ) {
			return null;
		}

		return array(
			'provider'      => $provider_key,
			'client_id'     => $oauth2_data['client_id'] ?? '',
			'client_secret' => cf7_smtp_decrypt( $oauth2_data['client_secret'] ?? '' ),
			'refresh_token' => cf7_smtp_decrypt( $oauth2_data['refresh_token'] ?? '' ),
			'user_email'    => $oauth2_data['user_email'] ?? '',
			'host'          => $config['host'],
			'port'          => $config['port'],
			'encryption'    => $config['encryption'],
		);
	}
}
