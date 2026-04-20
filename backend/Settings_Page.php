<?php
/**
 * CF7_SMTP the settings page
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;

/**
 * Create the settings page in the backend
 */
class Settings_Page extends Base {

	/**
	 * The settings form
	 *
	 * @var Settings_Form
	 */
	private $form;

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}

		$this->form = new Settings_Form();

		// Add the options page and menu item.
		\add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		$realpath        = (string) \realpath( __DIR__ );
		$plugin_basename = \plugin_basename( \plugin_dir_path( $realpath ) . 'cf7-smtp.php' );

		\add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

		// Register hooks early
		\add_action( 'admin_init', array( $this, 'check_oauth2_callback' ) );
		\add_action( 'admin_notices', array( $this, 'display_oauth2_notices' ) );
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function add_plugin_admin_menu() {
		\add_submenu_page(
			'wpcf7',
			CF7_SMTP_NAME,
			\__( 'SMTP', 'cf7-smtp' ),
			'manage_options',
			'cf7-smtp',
			array( $this, 'display_plugin_admin_page' )
		);

		\add_action( 'admin_init', array( $this->form, 'cf7_smtp_options_init' ) );
	}


	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function display_plugin_admin_page() {
		include_once CF7_SMTP_PLUGIN_ROOT . 'backend/views/admin.php';
		include_once CF7_SMTP_PLUGIN_ROOT . 'backend/views/send_mail.php';
	}

	/**
	 * Add a settings action link to the plugin page.
	 *
	 * @since {{plugin_version}}
	 * @param array $links Array of links.
	 * @return array
	 */
	public function add_action_links( array $links ) {
		$plugin_option   = \get_option( 'cf7-smtp-options' );
		$service_enabled = $plugin_option['service_enabled'] ?? false;
		$url             = $service_enabled ? 'admin.php?page=cf7-smtp' : 'admin.php?page=wpcf7-integration&service=cf7-smtp&action=setup';
		$label           = $service_enabled ? \__( 'Setup SMTP', 'cf7-smtp' ) : \__( 'Settings', 'cf7-smtp' );
		return \array_merge(
			array(
				'settings' => sprintf(
					'<a href="%s">%s</a>',
					\admin_url( $url ),
					$label
				),
			),
			$links
		);
	}

	/**
	 * Check for OAuth2 callback and handle token exchange.
	 */
	public function check_oauth2_callback() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth2 state param used for verification
		if ( ! isset( $_GET['page'] ) || 'cf7-smtp' !== $_GET['page'] || ! isset( $_GET['oauth2_callback'] ) ) {
			return;
		}

		$handler = new \cf7_smtp\Core\OAuth2_Handler();
		$state   = ( isset( $_GET['state'] ) && \is_scalar( $_GET['state'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['state'] ) ) : '';

		// Verify state for OAuth2 callback using transient-based validation.
		if ( empty( $state ) || ! $handler->validate_state( $state, false ) ) {
			cf7_smtp_log( 'OAuth2 callback: Invalid state parameter or missing state' );
			\set_transient( 'cf7_smtp_oauth2_error', \__( 'Invalid or expired OAuth2 state. Please try again.', 'cf7-smtp' ), 60 );
			\wp_safe_redirect( \admin_url( 'admin.php?page=cf7-smtp' ) );
			exit;
		}

		// Handle OAuth2 error responses (e.g. user cancelled the Microsoft consent screen).
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- state/transient already verified above
		if ( isset( $_GET['error'] ) || isset( $_GET['error_description'] ) ) {
			$error_code = ( isset( $_GET['error'] ) && \is_scalar( $_GET['error'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['error'] ) ) : 'unknown_error';
			$error_desc = ( isset( $_GET['error_description'] ) && \is_scalar( $_GET['error_description'] ) )
				? \sanitize_text_field( \wp_unslash( $_GET['error_description'] ) )
				: '';

			$message = \sprintf(
				/* translators: 1: OAuth2 error code  2: human-readable error description */
				__( 'OAuth2 authorization failed: %1$s. %2$s', 'cf7-smtp' ),
				$error_code,
				$error_desc
			);
			cf7_smtp_log( 'OAuth2 callback error: ' . $error_code . ' - ' . $error_desc );
			\set_transient( 'cf7_smtp_oauth2_error', $message, 60 );

			\wp_safe_redirect( \admin_url( 'admin.php?page=cf7-smtp' ) );
			exit;
		}

		$code = ( isset( $_GET['code'] ) && \is_scalar( $_GET['code'] ) ) ? \sanitize_text_field( \wp_unslash( $_GET['code'] ) ) : '';
		if ( empty( $code ) ) {
			cf7_smtp_log( 'OAuth2 callback: Missing or invalid authorization code' );
			\set_transient( 'cf7_smtp_oauth2_error', \__( 'Missing or invalid authorization code. Please try again.', 'cf7-smtp' ), 60 );
			\wp_safe_redirect( \admin_url( 'admin.php?page=cf7-smtp' ) );
			exit;
		}

		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$result = $handler->handle_callback( $code, $state );

			if ( $result['success'] ) {
				\set_transient( 'cf7_smtp_oauth2_notice', $result['message'], 60 );
			} else {
				\set_transient( 'cf7_smtp_oauth2_error', $result['message'], 60 );
			}
		} catch ( \Throwable $e ) {
			cf7_smtp_log( 'OAuth2 callback exception: ' . $e->getMessage() );
			\set_transient( 'cf7_smtp_oauth2_error', $e->getMessage(), 60 );
		}

		\wp_safe_redirect( \admin_url( 'admin.php?page=cf7-smtp' ) );
		exit;
	}

	/**
	 * Display OAuth2 notices.
	 */
	public function display_oauth2_notices() {
		$notice = \get_transient( 'cf7_smtp_oauth2_notice' );
		if ( $notice ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php echo \esc_html( $notice ); ?></p>
			</div>
			<?php
			\delete_transient( 'cf7_smtp_oauth2_notice' );
		}

		$error = \get_transient( 'cf7_smtp_oauth2_error' );
		if ( $error ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo \esc_html( $error ); ?></p>
			</div>
			<?php
			\delete_transient( 'cf7_smtp_oauth2_error' );
		}
	}
}
