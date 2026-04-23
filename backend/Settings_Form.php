<?php
/**
 * CF7_SMTP plugin settings
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Backend;

/**
 * Create the settings page in the backend
 */
class Settings_Form {
	/**
	 * The plugin options
	 *
	 * @var false|mixed|null
	 */
	private $options;

	/**
	 * The array of smtp hosts presets.
	 *
	 * @var array[]
	 */
	private $cf7_smtp_host_presets;

	/**
	 * Possible auth value (none, ssl, tls).
	 *
	 * @var string[]
	 */
	private $auth_values;

	/**
	 * Initialize the class
	 *
	 * @return void|bool
	 */
	public function __construct() {
		$this->options = \get_option( 'cf7-smtp-options' );

		$this->cf7_smtp_host_presets = \apply_filters(
			'cf7_smtp_servers',
			array(
				'custom'      => array(
					'host' => 'localhost',
					'auth' => 'none',
					'port' => 25,
				),
				'aruba'       => array(
					'host' => 'smtps.aruba.it',
					'auth' => 'ssl',
					'port' => 465,
				),
				'gmail (ssl)' => array(
					'host' => 'smtp.gmail.com',
					'auth' => 'ssl',
					'port' => 465,
				),
				'gmail (tls)' => array(
					'host' => 'smtp.gmail.com',
					'auth' => 'tls',
					'port' => 587,
				),
				'yahoo (ssl)' => array(
					'host' => 'pop.mail.yahoo.com',
					'auth' => 'ssl',
					'port' => 465,
				),
				'yahoo (tls)' => array(
					'host' => 'pop.mail.yahoo.com',
					'auth' => 'tls',
					'port' => 587,
				),
				'outlook.com' => array(
					'host' => 'smtp-mail.outlook.com',
					'auth' => 'tls',
					'port' => 587,
				),
				'office365'   => array(
					'host' => 'smtp.office365.com',
					'auth' => 'tls',
					'port' => 587,
				),
			)
		);

		$this->auth_values = array( 'none', 'ssl', 'tls' );
	}

	/**
	 * It creates the settings page
	 */
	public function cf7_smtp_options_init() {

		/* Group */
		\register_setting(
			'cf7-smtp-settings',
			'cf7-smtp-options',
			array( $this, 'cf7_smtp_sanitize_options' )
		);

		/* Section Bot Fingerprint */
		\add_settings_section(
			'smtp_data',
			\__( 'Smtp Server Setup', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_main_subtitle' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		\add_settings_field(
			'enabled',
			\__( 'Enable', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_enable_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth_method */
		\add_settings_field(
			'auth_method',
			\__( 'Mail Service', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_auth_method_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp preset */
		\add_settings_field(
			'preset',
			\__( 'SMTP configuration preset', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_preset_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth */
		\add_settings_field(
			'auth',
			\__( 'Encryption', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_auth_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp smtp_mode */
		\add_settings_field(
			'smtp_mode',
			\__( 'SMTP Mode', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_smtp_mode_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp host */
		\add_settings_field(
			'host',
			\__( 'Host', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_host_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp port */
		\add_settings_field(
			'port',
			\__( 'Port', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_port_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_name */
		\add_settings_field(
			'user_name',
			\__( 'SMTP User Name', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_user_name_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_pass */
		\add_settings_field(
			'user_pass',
			\__( 'SMTP Password', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_user_pass_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* OAuth2 Section */
		\add_settings_section(
			'smtp_oauth2',
			\__( 'OAuth2 Authentication', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_section_callback' ),
			'smtp-settings'
		);

		/* OAuth2 Provider */
		\add_settings_field(
			'oauth2_provider',
			\__( 'OAuth2 Provider', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_provider_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Redirect URI (Read Only) */
		\add_settings_field(
			'oauth2_redirect_uri',
			\__( 'Authorized Redirect URI', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_redirect_uri_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Client ID */
		\add_settings_field(
			'oauth2_client_id',
			\__( 'Client ID', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_client_id_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Client Secret */
		\add_settings_field(
			'oauth2_client_secret',
			\__( 'Client Secret', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_client_secret_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Connect Button */
		\add_settings_field(
			'oauth2_connect',
			\__( 'OAuth2 Connection', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_connect_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* smtp_advanced */
		\add_settings_section(
			'smtp_advanced',
			\__( 'Advanced Options', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_advanced_callback' ),
			'smtp-settings-advanced'
		);

		/* allow insecure options */
		\add_settings_field(
			'insecure',
			\__( 'Allow insecure options', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_insecure_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Reply to */
		\add_settings_field(
			'replyTo',
			\__( 'Add Reply To', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_reply_to_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		\add_settings_field(
			'from_mail',
			\__( 'From mail', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_from_mail_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		\add_settings_field(
			'from_name',
			\__( 'From name', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_from_name_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Section style */
		\add_settings_section(
			'smtp_style',
			\__( 'Style', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_style_subtitle' ),
			'smtp-style'
		);

		/* Settings cf7_smtp enabled */
		\add_settings_field(
			'custom_template',
			\__( 'Form Email Templates', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_custom_template_callback' ),
			'smtp-style',
			'smtp_style'
		);

		/* Section cron */
		\add_settings_section(
			'smtp_cron',
			\__( 'Report', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_cron_subtitle' ),
			'smtp-cron'
		);

		/* Settings cf7_smtp enabled */
		\add_settings_field(
			'report_every',
			\__( 'Schedule report every', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_every_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp log retain days */
		\add_settings_field(
			'log_retain_days',
			\__( 'Log retention days', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_log_retain_days_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Flush logs */
		\add_settings_field(
			'flush_logs',
			\__( 'Flush logs', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_flush_logs_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp enabled */
		\add_settings_field(
			'report_to',
			\__( 'Email report to', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_to_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp enabled */
		\add_settings_field(
			'report_now',
			\__( 'Send report now', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_now_callback' ),
			'smtp-cron',
			'smtp_cron'
		);
	}

	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_main_subtitle() {
		\printf(
			'<p>%s</p>',
			\esc_html__( 'Welcome! Remember that you can activate and deactivate the smtp service simply by ticking the checkbox below', 'cf7-smtp' )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_style_subtitle() {
		\printf(
			'<p>%s</p>',
			\esc_html__( 'Add an html template that wraps the email. (ps. You can enable a user-defined template, see the documentation for more information)', 'cf7-smtp' )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_cron_subtitle() {
		\printf(
			'<p>%s</p>',
			\esc_html__( 'Do you want to know if the mails are running smoothly? Let me occasionally e-mail a summary to verify the functionality.', 'cf7-smtp' )
		);
	}

	/**
	 * It returns nothing
	 *
	 * @return void Nothing.
	 */
	public function cf7_smtp_print_advanced_callback() {
		\printf(
			'<p>%s</p>',
			\esc_html__( 'Leave empty to NOT override the WordPress defaults (the one used can be different from the one you see below, if left blank the one set in Contact Form 7 will be used)', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 section description.
	 */
	public function cf7_smtp_print_oauth2_section_callback() {
		\printf(
			'<div id="cf7_smtp_oauth2_section_desc"><p>%s</p></div>',
			\esc_html__( 'Use OAuth2 for secure authentication without storing passwords. Connect with your email provider using OAuth2.', 'cf7-smtp' )
		);
	}



	/**
	 * Prints the OAuth2 provider select field.
	 */
	public function cf7_smtp_print_oauth2_provider_callback() {
		$oauth2_handler = new \cf7_smtp\Core\OAuth2_Handler();
		$providers      = $oauth2_handler->get_providers();
		$current        = ! empty( $this->options['oauth2_provider'] ) ? $this->options['oauth2_provider'] : '';

		$options_html = '<option value="">' . \esc_html__( 'Select a provider', 'cf7-smtp' ) . '</option>';
		foreach ( $providers as $key => $name ) {
			$options_html .= \sprintf(
				'<option value="%s" %s>%s</option>',
				\esc_attr( $key ),
				$current === $key ? 'selected' : '',
				\esc_html( $name )
			);
		}

		\printf(
			'<select id="cf7_smtp_oauth2_provider" name="cf7-smtp-options[oauth2_provider]" class="cf7-smtp-oauth2-field" style="display:none;">%s</select>',
			$options_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above.
		);
	}

	/**
	 * Prints the OAuth2 Redirect URI (read-only) for the user to copy.
	 */
	public function cf7_smtp_print_oauth2_redirect_uri_callback() {
		$admin_callback = \add_query_arg(
			array(
				'page'            => 'cf7-smtp',
				'oauth2_callback' => 1,
			),
			\admin_url( 'admin.php' )
		);
		$rest_callback  = \rest_url( 'cf7-smtp/v1/oauth2/callback' );

		$current_provider = ! empty( $this->options['oauth2_provider'] ) ? $this->options['oauth2_provider'] : 'gmail';
		$initial_callback = 'office365' === $current_provider ? $rest_callback : $admin_callback;

		\printf(
			'<code id="cf7_smtp_oauth2_redirect_uri"
				   data-gmail-redirect-uri="%1$s"
				   data-office365-redirect-uri="%2$s"
				   style="user-select: all; background: #f0f0f1; padding: 5px 10px; display: block; margin-bottom: 5px;">%4$s</code>
			<p class="description">%3$s</p>',
			\esc_attr( $admin_callback ),
			\esc_attr( $rest_callback ),
			\esc_html__( 'Copy this URL and add it to your "Authorized Redirect URIs" in your Google Cloud Console or OAuth2 Provider settings.', 'cf7-smtp' ),
			\esc_html( $initial_callback )
		);
	}

	/**
	 * Prints the OAuth2 Client ID field.
	 */
	public function cf7_smtp_print_oauth2_client_id_callback() {
		$client_id = $this->cf7_smtp_find_setting( 'oauth2_client_id' );
		\printf(
			'<input type="text" id="cf7_smtp_oauth2_client_id" name="cf7-smtp-options[oauth2_client_id]" value="%s" class="regular-text cf7-smtp-oauth2-field" %s />
			<p class="description">%s</p>',
			\esc_attr( $client_id['value'] ?? '' ),
			\esc_html( empty( $client_id['defined'] ) ? '' : 'disabled' ),
			\esc_html__( 'Enter the Client ID from your OAuth2 provider (e.g., Google Cloud Console).', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 Client Secret field.
	 */
	public function cf7_smtp_print_oauth2_client_secret_callback() {
		$client_secret = $this->cf7_smtp_find_setting( 'oauth2_client_secret' );
		$has_value     = ! empty( $client_secret['value'] );
		\printf(
			'<div class="cf7-smtp-password-wrap">
				<input type="password" id="cf7_smtp_oauth2_client_secret" name="cf7-smtp-options[oauth2_client_secret]" class="regular-text cf7-smtp-oauth2-field" %s placeholder="%s" />
				%s
			</div>
			<p class="description">%s</p>',
			\esc_html( empty( $client_secret['defined'] ) ? '' : 'disabled' ),
			$has_value ? \esc_attr__( '••••••••', 'cf7-smtp' ) : '',
			$has_value && empty( $client_secret['defined'] ) ? \sprintf( '<label><input type="checkbox" name="cf7-smtp-options[remove_oauth2_client_secret]" value="1"> %s</label>', \esc_html__( 'Remove secret', 'cf7-smtp' ) ) : '',
			\esc_html__( 'Enter the Client Secret from your OAuth2 provider. This will be encrypted before storing.', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 connect/disconnect button and status.
	 */
	public function cf7_smtp_print_oauth2_connect_callback() {
		$oauth2_handler = new \cf7_smtp\Core\OAuth2_Handler();
		$is_connected   = $oauth2_handler->is_connected();
		$status         = $oauth2_handler->get_status();

		if ( $is_connected ) {
			$connected_email = $status['user_email'] ?? \__( 'Connected', 'cf7-smtp' );
			\printf(
				'<div class="cf7-smtp-oauth2-status cf7-smtp-oauth2-status--connected">
					<span class="dashicons dashicons-yes-alt"></span>
					<span class="cf7-smtp-oauth2-status-text">%s: <strong>%s</strong></span>
				</div>
				<button type="button" id="cf7_smtp_oauth2_disconnect" class="button button-secondary cf7-smtp-oauth2-field">%s</button>',
				\esc_html__( 'Connected as', 'cf7-smtp' ),
				\esc_html( $connected_email ),
				\esc_html__( 'Disconnect', 'cf7-smtp' )
			);
		} else {
			\printf(
				'<div class="cf7-smtp-oauth2-status cf7-smtp-oauth2-status--disconnected">
					<span class="dashicons dashicons-warning"></span>
					<span class="cf7-smtp-oauth2-status-text">%s</span>
				</div>
				<button type="button" id="cf7_smtp_oauth2_connect" class="button button-primary cf7-smtp-oauth2-field">%s</button>
				<p class="description">%s</p>',
				\esc_html__( 'Not connected', 'cf7-smtp' ),
				\esc_html__( 'Connect with OAuth2', 'cf7-smtp' ),
				\esc_html__( 'Save your Client ID and Client Secret first, then click Connect.', 'cf7-smtp' )
			);
		}//end if
	}

	/**
	 * Prints the authentication method selection with visual buttons.
	 */
	public function cf7_smtp_print_auth_method_callback() {
		$auth_method = $this->cf7_smtp_find_setting( 'auth_method' );
		$current     = ! empty( $auth_method['value'] ) ? $auth_method['value'] : '';

		// Backward compatibility: if auth_method is empty, check enabled.
		if ( empty( $current ) ) {
			$enabled = $this->cf7_smtp_find_setting( 'enabled' );
			$current = ! empty( $enabled['value'] ) ? 'smtp' : 'wp';
		}

		$disabled = ! empty( $auth_method['defined'] ) ? 'disabled' : '';

		$methods = array(
			'wp'      => array(
				'label' => \__( 'WordPress', 'cf7-smtp' ),
				'icon'  => 'dashicons-wordpress',
			),
			'smtp'    => array(
				'label' => \__( 'Other SMTP', 'cf7-smtp' ),
				'icon'  => 'dashicons-email-alt',
			),
			'gmail'   => array(
				'label' => \__( 'Gmail', 'cf7-smtp' ),
				// Placeholder for SVG or use dashicon for now
				'icon'  => 'dashicons-google',
			),
			'outlook' => array(
				'label' => \__( 'Outlook', 'cf7-smtp' ),
				'icon'  => 'dashicons-email-alt2',
			),
		);

		echo '<div class="cf7-smtp-auth-grid">';
		foreach ( $methods as $key => $props ) {
			\printf(
				'<label class="cf7-smtp-auth-card %s">
					<input type="radio" name="cf7-smtp-options[auth_method]" value="%s" %s %s />
					<div class="cf7-smtp-auth-card-content">
						<span class="dashicons %s"></span>
						<span class="cf7-smtp-auth-label">%s</span>
					</div>
				</label>',
				$current === $key ? 'selected' : '',
				\esc_attr( $key ),
				$current === $key ? 'checked' : '',
				\esc_attr( $disabled ),
				\esc_attr( $props['icon'] ),
				\esc_html( $props['label'] )
			);
		}
		echo '</div>';
		echo '<p class="description">' . \esc_html__( 'Choose your preferred mail delivery method.', 'cf7-smtp' ) . '</p>';

		// Warning box for invalid/WP selection
		\printf(
			'<div id="cf7-smtp-wp-warning" class="notice notice-warning inline" style="display:none; margin-top: 15px;">
				<p>%s</p>
			</div>',
			\esc_html__( 'Warning: You are using the default WordPress mailer. Your emails might end up in spam.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a checkbox with the id of `cf7_smtp_enabled` and the name of `cf7-smtp-options[enabled]` and if the `enabled`
	 * option is set, it will be checked
	 */
	public function cf7_smtp_print_enable_callback() {
		\printf(
			'<input type="checkbox" id="cf7_smtp_enabled" name="cf7-smtp-options[enabled]" %s />',
			! empty( $this->options['enabled'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It takes an array of arrays, and returns a string of HTML options
	 *
	 * @param array  $options The array of options to be converted to HTML.
	 * @param string $selected The selected item.
	 *
	 * @return string
	 */
	public function cf7_smtp_form_array_to_options( array $options, $selected = '' ) {
		$select_opt = '';

		// foreach option, add it to the select as an option
		foreach ( $options as $option => $options_data ) {
			$option_data = '';
			foreach ( $options_data as $prop => $value ) {
				$option_data .= ! empty( $value ) ? \sprintf( ' data-%s="%s"', \sanitize_key( $prop ), \sanitize_text_field( $value ) ) : '';
			}

			$select_opt .= \sprintf(
				"<option value='%s'%s%s>%s</option>",
				$option,
				$selected === $option ? ' selected' : ' ',
				$option_data,
				ucfirst( $option )
			);
		}

		return $select_opt;
	}

	/**
	 * Utility that generates the options for a select input given an array of values
	 *
	 * @param array  $values - the array of selection options.
	 * @param string $selected - the name of the selected one (if any).
	 *
	 * @return string - the html needed inside the select
	 */
	private function cf7_smtp_generate_options( $values, $selected = '' ) {
		$html = '';
		foreach ( $values as $key => $value ) {
			$html .= \sprintf(
				'<option value="%s" %s>%s</option>',
				\sanitize_key( $key ),
				$key === $selected ? 'selected' : '',
				$value['display']
			);
		}
		return $html;
	}

	/**
	 * It creates a dropdown menu of preset SMTP hosts
	 */
	public function cf7_smtp_print_preset_callback() {

		\printf(
			'<select type="checkbox" id="cf7_smtp_preset" name="cf7-smtp-options[preset]">%s</select>',
			\wp_kses(
				self::cf7_smtp_form_array_to_options(
					$this->cf7_smtp_host_presets,
					$this->options['preset']
				),
				array(
					'option' => array(
						'value'     => array(),
						'data-host' => array(),
						'data-auth' => array(),
						'data-port' => array(),
						'selected'  => array(),
					),
				)
			)
		);
	}

	/**
	 * It generates a radio button for the authentication method.
	 *
	 * @param string $selected The value of the selected radio button.
	 * @param bool   $defined The value has been defined with a constant.
	 *
	 * @return string a string of HTML.
	 */
	public function cf7_smtp_form_generate_radio( string $selected = '', bool $defined = false ): string {
		$html = '';
		foreach ( $this->auth_values as $auth ) {
			$auth_name = 'tls' === $auth || 'ssl' === $auth ? $auth : \esc_html__( 'none', 'cf7-smtp' );
			$html     .= \sprintf(
				'<label><input type="radio" name="cf7-smtp-options[auth]" class="auth-%s" value="%s"%s%s/>%s</label>',
				\esc_attr( $auth_name ),
				\esc_attr( $auth_name ),
				( $auth === $selected || ( '' === $selected && 'none' === $auth ) ) ? ' checked' : ' ',
				! empty( $defined ) ? ' disabled ' : ' ',
				$auth_name
			);
		}
		return $html;
	}

	/**
	 * If the CF7_SMTP_SETTINGS constant is defined and the key is set, return the value of the key. Otherwise, if the key is
	 * set in the options array, return the value of the key. Otherwise, return 'unset'
	 *
	 * @param string $key The key of the option you want to retrieve.
	 * @param bool   $return_value If true, the value of the option will be returned. If false, the option's status will be returned.
	 *
	 * @return array $option The value of the key in the CF7_SMTP_SETTINGS array, or the value of the key in the $this->options array, or
	 * 'unset' if the key is not found in either array.
	 */
	public function cf7_smtp_find_setting( string $key, bool $return_value = true ): array {
		$option = array(
			'value'   => false,
			'defined' => false,
		);
		if ( ! empty( CF7_SMTP_SETTINGS ) && isset( CF7_SMTP_SETTINGS[ $key ] ) ) {
			$option['value']   = $return_value ? CF7_SMTP_SETTINGS[ $key ] : 'defined';
			$option['defined'] = true;
		} elseif ( ! empty( $this->options[ $key ] ) ) {
			$option['value'] = $return_value ? $this->options[ $key ] : 'stored';
		}
		return $option;
	}

	/**
	 * It prints a checkbox
	 */
	public function cf7_smtp_print_insecure_callback() {
		$insecure = $this->cf7_smtp_find_setting( 'insecure' );
		\printf(
			'<span class="smtp-options-wrapper checkbox-wrapper flex">
				<input type="checkbox" id="cf7_smtp_insecure" name="cf7-smtp-options[insecure]" %s %s />
				<p class="description">%s</p>
			</span>',
			empty( $insecure['value'] ) ? '' : 'checked="true"',
			\esc_html( empty( $insecure['defined'] ) ? '' : 'disabled' ),
			\esc_html__( 'Enable ONLY if your SMTP server uses self-signed certificates or outdated TLS versions. Warning: This reduces the security of your mail transmission.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a checkbox with the id of cf7_smtp_replyTo and the name of cf7-smtp-options[replyTo] and if the option is
	 * set, it will be checked
	 */
	public function cf7_smtp_print_reply_to_callback() {
		$reply_to = $this->cf7_smtp_find_setting( 'replyTo' );
		$reply_to_email = $this->cf7_smtp_find_setting( 'reply_to_email' );
		\printf(
			'<span class="smtp-options-wrapper checkbox-wrapper flex">
				<input type="checkbox" id="cf7_smtp_replyTo" name="cf7-smtp-options[replyTo]" %s %s />
				<p class="description">%s</p>
			</span>
			<div class="smtp-options-wrapper flex" style="margin-top: 10px;">
				<input type="email" id="cf7_smtp_reply_to_email" name="cf7-smtp-options[reply_to_email]" value="%s" %s placeholder="reply@example.com" class="regular-text" />
				<p class="description">%s</p>
			</div>',
			empty( $reply_to['value'] ) ? '' : 'checked="true"',
			\esc_html( empty( $reply_to['defined'] ) ? '' : 'disabled' ),
			\esc_html__( 'Check this if you want the "Reply-To" header to be set automatically. This allows users to reply to a different address than the one used to send the email.', 'cf7-smtp' ),
			\esc_attr( empty( $reply_to_email['value'] ) ? '' : \esc_html( $reply_to_email['value'] ) ),
			\esc_html( empty( $reply_to_email['defined'] ) ? '' : 'disabled' ),
			\esc_html__( 'The email address to be used in the Reply-To header. If empty, the From email will be used.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a radio button group with the value of the 'auth' key in the $this->options array
	 */
	public function cf7_smtp_print_auth_callback() {
		$auth = $this->cf7_smtp_find_setting( 'auth' );
		\printf(
			'<div id="cf7-smtp-auth">%s</div>',
			\wp_kses(
				self::cf7_smtp_form_generate_radio( \esc_html( $auth['value'] ), $auth['defined'] ),
				array(
					'label' => array(),
					'input' => array(
						'type'     => array(),
						'name'     => array(),
						'class'    => array(),
						'value'    => array(),
						'checked'  => array(),
						'disabled' => array(),
					),
				)
			)
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_host, the name of cf7-smtp-options[host], the value of the host
	 * option, and a placeholder of localhost
	 */
	public function cf7_smtp_print_host_callback() {
		$host = $this->cf7_smtp_find_setting( 'host' );
		\printf(
			'<input type="text" id="cf7_smtp_host" name="cf7-smtp-options[host]" value="%s" %s placeholder="localhost" />',
			\esc_attr( empty( $host['value'] ) ? '' : \esc_html( $host['value'] ) ),
			\esc_html( empty( $host['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints an input field with the id of cf7_smtp_port and the name of cf7-smtp-options[port] and the value of the port
	 * option
	 */
	public function cf7_smtp_print_port_callback() {
		$port = $this->cf7_smtp_find_setting( 'port' );
		\printf(
			'<input type="number" id="cf7_smtp_port" name="cf7-smtp-options[port]" value="%s" %s min="0" max="65353" step="1" />',
			\esc_attr( empty( $port['value'] ) ? '' : \intval( $port['value'] ) ),
			\esc_html( empty( $port['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_user_name, the name of cf7-smtp-options[user_name], and the value
	 * of the user_name key in the options array
	 */
	public function cf7_smtp_print_user_name_callback() {
		$user_name = $this->cf7_smtp_find_setting( 'user_name' );
		\printf(
			'<input type="text" id="cf7_smtp_user_name" name="cf7-smtp-options[user_name]" value="%s" class="regular-text" %s />',
			\esc_attr( $user_name['value'] ?? '' ),
			\esc_html( empty( $user_name['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_user_name, the name of cf7-smtp-options[user_name], and the value
	 * of the user_name key in the options array
	 */
	public function cf7_smtp_print_user_pass_callback() {
		$user_pass = $this->cf7_smtp_find_setting( 'user_pass' );
		$has_value = ! empty( $user_pass['value'] );

		\printf(
			'<div class="cf7-smtp-password-wrap">
				<input type="password" id="cf7_smtp_user_pass" name="cf7-smtp-options[user_pass]" class="regular-text"%s placeholder="%s" />
				%s
			</div>
			<p class="description">%s</p>',
			\esc_html( empty( $user_pass['defined'] ) ? '' : ' disabled' ),
			$has_value ? \esc_attr__( '••••••••', 'cf7-smtp' ) : '',
			$has_value && empty( $user_pass['defined'] ) ? \sprintf( '<label><input type="checkbox" name="cf7-smtp-options[remove_user_pass]" value="1"> %s</label>', \esc_html__( 'Remove password', 'cf7-smtp' ) ) : '',
			\esc_html__( 'If using Gmail or Outlook (Exchange), consider using OAuth2 above instead of saving your password directly. Using OAuth2 removes the need to allow "less secure apps" or generate "app passwords" in your email account.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a radio button group for the SMTP mode (all emails vs cf7 only)
	 */
	public function cf7_smtp_print_smtp_mode_callback() {
		$smtp_mode     = $this->cf7_smtp_find_setting( 'smtp_mode' );
		$select_values = array(
			'cf7'      => array( 'display' => 'Contact Form 7 Settings' ),
			'override' => array( 'display' => 'Override all WordPress emails' ),
		);

		\printf(
			'<select id="cf7_smtp_smtp_mode" name="cf7-smtp-options[smtp_mode]" class="regular-text" %s>%s</select>',
			\esc_attr( empty( $smtp_mode['defined'] ) ? '' : 'disabled' ),
			\wp_kses(
				$this->cf7_smtp_generate_options( $select_values, \esc_html( $smtp_mode['value'] ) ),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
		\printf(
			'<p class="description">%s<strong>%s</strong> %s</p>',
			\esc_html__( 'Using', 'cf7-smtp' ),
			\esc_html__( '\'Contact Form 7 Settings\'', 'cf7-smtp' ),
			\esc_html__( 'this plugin will override ONLY the emails generated by Contact Form 7 Forms submit. Other options will use this Smtp to deliver all WordPress generated emails.', 'cf7-smtp' )
		);
	}


	/**
	 * It prints an input field with the id of cf7_smtp_from_mail and the name of cf7-smtp-options[from_mail] and the value of
	 * the from_mail key in the options array
	 */
	public function cf7_smtp_print_from_mail_callback() {
		$from_mail = $this->cf7_smtp_find_setting( 'from_mail' );
		\printf(
			'<span class="smtp-options-wrapper flex">
				<input type="email" id="cf7_smtp_from_mail" name="cf7-smtp-options[from_mail]" value="%s" %s placeholder="wordpress@example.com" />
				<button type="button" id="cf7_smtp_check_dns" class="button button-secondary">%s</button>
			</span>
			<p class="description">%s</p>
			<div id="cf7_smtp_dns_result"></div>',
			\esc_attr( empty( $from_mail['value'] ) ? '' : \esc_html( $from_mail['value'] ) ),
			\esc_html( empty( $from_mail['defined'] ) ? '' : 'disabled' ),
			\esc_html__( 'Check DNS now', 'cf7-smtp' ),
			\esc_html__( 'Crucial for deliverability. This address should match the domain of your SMTP account to satisfy SPF and DKIM checks. If left empty, the plugin will use the WordPress default or the one set in CF7, which might cause "Spoofing" alerts.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_from_name and the name of cf7-smtp-options[from_name] and the
	 * value of the from_name option
	 *
	 * This one could be empty.
	 */
	public function cf7_smtp_print_from_name_callback() {
		$from_name = $this->cf7_smtp_find_setting( 'from_name' );
		\printf(
			'<input type="text" id="cf7_smtp_from_name" name="cf7-smtp-options[from_name]" value="%s" %s placeholder="WordPress" />
			<p class="description">%s</p>',
			\esc_attr( isset( $from_name['value'] ) ? \esc_html( $from_name['value'] ) : '' ),
			\esc_html( $from_name['defined'] ? 'disabled' : '' ),
			\esc_html__( 'The display name shown in the recipient\'s inbox (e.g., "Your Company Support").', 'cf7-smtp' )
		);
	}

	/**
	 * It generates a select box with the options 'disabled', '60sec', '5min', 'hourly', 'twicedaily', 'daily', 'weekly' and the default value is 'disabled'
	 */
	public function cf7_smtp_print_report_every_callback() {
		/* the list of available schedules */
		$schedules = \wp_get_schedules();
		$schedules = \array_merge( array( '' => array( 'display' => 'disabled' ) ), $schedules );
		\printf(
			'<select id="report_every" name="cf7-smtp-options[report_every]">%s</select>',
			\wp_kses(
				$this->cf7_smtp_generate_options(
					$schedules,
					isset( $this->options['report_every'] ) ? $this->options['report_every'] : ''
				),
				array(
					'option' => array(
						'value'    => array(),
						'selected' => array(),
					),
				)
			)
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_log_retain_days and the name of cf7-smtp-options[log_retain_days] and the value of the log_retain_days option
	 *
	 * @return void
	 */
	public function cf7_smtp_print_log_retain_days_callback() {
		$log_retain_days = $this->cf7_smtp_find_setting( 'log_retain_days', true );

		if ( empty( $log_retain_days['value'] ) ) {
			$log_retain_days['value'] = 30;
		}

		\printf(
			'<input type="number" step="1" id="cf7_smtp_log_retain_days" name="cf7-smtp-options[log_retain_days]" value="%s" class="regular-text" %s />',
			\esc_attr( $log_retain_days['value'] ),
			\esc_attr( empty( $log_retain_days['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * Print the flush logs button callback
	 *
	 * @return void
	 */
	public function cf7_smtp_print_flush_logs_callback() {

		\printf(
			'<button type="button" class="button button-secondary cf7_smtp_flush_logs">%s</button>
			<div class="message"></div>',
			\esc_html__( 'Empty table', 'cf7-smtp' )
		);
	}

	/**
	 * Get available custom templates from theme folder
	 * Looks for templates in theme/cf7-smtp/ directory
	 *
	 * @return array Array of template files with name as key and path as value
	 */
	private function cf7_smtp_get_custom_templates() {
		$custom_templates = array();

		// Get template directory paths to check
		$template_dir   = get_template_directory();
		$stylesheet_dir = get_stylesheet_directory();

		// Check multiple possible locations for custom templates
		$directories_to_check = array();

		// Check child theme first (if different)
		if ( $stylesheet_dir !== $template_dir ) {
			$directories_to_check[] = $stylesheet_dir . '/cf7-smtp';
			$directories_to_check[] = $stylesheet_dir . '/templates/cf7-smtp';
		}

		// Check parent theme
		$directories_to_check[] = $template_dir . '/cf7-smtp';
		$directories_to_check[] = $template_dir . '/templates/cf7-smtp';

		foreach ( $directories_to_check as $dir ) {
			if ( is_dir( $dir ) ) {
				// Get all PHP files in the directory
				$files = glob( $dir . '/*.php' );

				if ( ! empty( $files ) ) {
					foreach ( $files as $file ) {
						// Get filename without extension
						$template_name = basename( $file, '.php' );

						// Skip if template name starts with underscore (private template)
						if ( strpos( $template_name, '_' ) === 0 ) {
							continue;
						}

						// Avoid duplicates - prefer first found (child theme over parent theme)
						if ( ! isset( $custom_templates[ $template_name ] ) ) {
							$custom_templates[ $template_name ] = $file;
						}
					}
				}
			}//end if
		}//end foreach

		// Allow developers to filter custom templates
		return apply_filters( 'cf7_smtp_custom_templates', $custom_templates );
	}

	/**
	 * Displays Contact Form 7 forms with template selection options
	 * Shows dropdown for each form to choose between: no template, default template, or custom templates
	 */
	public function cf7_smtp_print_custom_template_callback() {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			echo '<p>' . esc_html__( 'Contact Form 7 is not installed or activated.', 'cf7-smtp' ) . '</p>';
			return;
		}

		$forms = \WPCF7_ContactForm::find(
			array(
				'posts_per_page' => -1,
			)
		);

		if ( empty( $forms ) ) {
			echo '<p>' . esc_html__( 'No Contact Form 7 forms found. Please create a form first.', 'cf7-smtp' ) . '</p>';
			return;
		}

		// Get available custom templates from theme folder
		$custom_templates = $this->cf7_smtp_get_custom_templates();

		// Get current template preferences (default to empty array if not set)
		$template_preferences = isset( $this->options['form_templates'] ) ? $this->options['form_templates'] : array();

		echo '<div class="cf7-smtp-form-templates">';
		echo '<h4>' . esc_html__( 'Form Template Selection', 'cf7-smtp' ) . '</h4>';
		echo '<p>' . esc_html__( 'Choose the email template for each Contact Form 7 form:', 'cf7-smtp' ) . '</p>';

		echo '<table class="widefat striped" style="margin-top: 10px;">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="width: 5%;">' . esc_html__( 'ID', 'cf7-smtp' ) . '</th>';
		echo '<th style="width: 35%;">' . esc_html__( 'Form Title', 'cf7-smtp' ) . '</th>';
		echo '<th style="width: 60%;">' . esc_html__( 'Email Template', 'cf7-smtp' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		foreach ( $forms as $form ) {
			$form_id          = $form->id();
			$form_title       = $form->title();
			$current_template = isset( $template_preferences[ $form_id ] ) ? $template_preferences[ $form_id ] : 'default';

			echo '<tr>';
			echo '<td>' . esc_html( $form_id ) . '</td>';
			echo '<td><a href="' . esc_url( admin_url( 'admin.php?page=wpcf7&post=' . $form_id . '&action=edit' ) ) . '" target="_blank">' . esc_html( $form_title ) . '</a></td>';
			echo '<td>';

			// Template selection dropdown
			echo '<select name="cf7-smtp-options[form_templates][' . esc_attr( $form_id ) . ']" class="cf7-smtp-template-select">';
			echo '<option value="none"' . selected( $current_template, 'none', false ) . '>' . esc_html__( 'No Template (Keep Contact Form 7 settings)', 'cf7-smtp' ) . '</option>';
			echo '<option value="default"' . selected( $current_template, 'default', false ) . '>' . esc_html__( 'Default Template', 'cf7-smtp' ) . '</option>';

			// Add custom templates if available
			if ( ! empty( $custom_templates ) ) {
				echo '<optgroup label="' . esc_attr__( 'Custom Templates', 'cf7-smtp' ) . '">';
				foreach ( $custom_templates as $template_name => $template_path ) {
					echo '<option value="' . esc_attr( $template_name ) . '"' . selected( $current_template, $template_name, false ) . '>' . esc_html( $template_name ) . '</option>';
				}
				echo '</optgroup>';
			}

			echo '</select>';
			echo '</td>';
			echo '</tr>';
		}//end foreach

		echo '</tbody>';
		echo '</table>';

		// Show info about custom templates
		if ( empty( $custom_templates ) ) {
			echo '<p style="margin-top: 15px;"><em>' . sprintf(
				// Translators: %1$s and %2$s are code snippets
				esc_html__( 'No custom templates found. To add custom templates, create template files in your theme folder: %1$s or %2$s', 'cf7-smtp' ),
				'<code>your-theme/cf7-smtp/</code>',
				'<code>your-theme/templates/cf7-smtp/</code>'
			) . '</em></p>';
		} else {
			echo '<p style="margin-top: 15px;"><em>' . sprintf(
				// Translators: %d is the number of custom templates
				esc_html__( 'Found %d custom template(s) in your theme folder.', 'cf7-smtp' ),
				count( $custom_templates )
			) . '</em></p>';
		}

		echo '</div>';
	}

	/**
	 * It prints a text input field with the id of `cf7_smtp_report_to` and the name of `cf7-smtp-options[report_to]` and the
	 * value of the `report_to` key in the `$this->options` array
	 */
	public function cf7_smtp_print_report_to_callback() {
		$report_to = $this->cf7_smtp_find_setting( 'report_to' );

		if ( empty( $report_to['value'] ) ) {
			$report_to['value'] = \wp_get_current_user()->user_email;
		}

		\printf(
			'<input type="email" id="cf7_smtp_report_to" name="cf7-smtp-options[report_to]" value="%s" class="regular-text" %s />',
			\esc_attr( $report_to['value'] ),
			\esc_attr( empty( $report_to['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * Print the report now button callback
	 *
	 * @return void
	 */
	public function cf7_smtp_print_report_now_callback() {

		$admin_email                 = \get_bloginfo( 'admin_email' );
		$options                     = \get_option( 'cf7-smtp-options' );
		$options['report_to']        = ! empty( $options['report_to'] ) ? $options['report_to'] : $admin_email;
		$options['email_subject']    = ! empty( $options['email_subject'] ) ? $options['email_subject'] : 'Send report now';
		$options['email_from_name']  = ! empty( $options['email_from_name'] ) ? $options['email_from_name'] : 'CF7 SMTP Report';
		$options['email_from_email'] = ! empty( $options['email_from_email'] ) ? $options['email_from_email'] : $admin_email;

		\printf(
			'<button type="button" class="button button-secondary cf7_smtp_send_report_now">%s</button>
			<div class="message"></div>',
			\esc_html__( 'Send me a report about sent emails', 'cf7-smtp' )
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array $options sanitized
	 */
	public function cf7_smtp_sanitize_options( $input ) {
		$new_input = array();

		/* Enabled Support*/
		if ( isset( $input['enabled'] ) && ! empty( $input['enabled'] ) ) {
			$new_input['enabled'] = 1;

			// If backward-compatibility sets enabled to true and auth_method is missing, set to smtp
			if ( empty( $input['auth_method'] ) ) {
				$new_input['auth_method'] = 'smtp';
			}
		}

		/* Auth Method Override */
		if ( isset( $input['auth_method'] ) ) {
			$new_input['auth_method'] = \sanitize_text_field( $input['auth_method'] );
		}

		/* Preset string */
		if ( isset( $input['preset'] ) ) {
			$new_input['preset'] = \sanitize_text_field( $input['preset'] );
		}

		/* Delete Passwords & Secrets explicit flags */
		if ( isset( $input['remove_user_pass'] ) && '1' === $input['remove_user_pass'] ) {
			$new_input['user_pass'] = '';
		} elseif ( isset( $input['user_pass'] ) && ! empty( $input['user_pass'] ) ) {
			$placeholder = \esc_attr__( '••••••••', 'cf7-smtp' );
			if ( $input['user_pass'] === $placeholder ) {
				// User submitted the placeholder, don't double-encrypt, keep existing db value
				$existing               = $this->options['user_pass'] ?? '';
				$new_input['user_pass'] = $existing;
			} else {
				$new_input['user_pass'] = \cf7_smtp_crypt( \sanitize_text_field( $input['user_pass'] ) );
				// It encrypts the user pass.
			}
		} else {
			$new_input['user_pass'] = $this->options['user_pass'] ?? '';
		}

		if ( isset( $input['remove_oauth2_client_secret'] ) && '1' === $input['remove_oauth2_client_secret'] ) {
			$new_input['oauth2_client_secret'] = '';
		} elseif ( isset( $input['oauth2_client_secret'] ) && ! empty( $input['oauth2_client_secret'] ) ) {
			$placeholder = \esc_attr__( '••••••••', 'cf7-smtp' );
			if ( $input['oauth2_client_secret'] === $placeholder ) {
				// Don't re-encrypt the placeholder
				$existing                          = $this->options['oauth2_client_secret'] ?? '';
				$new_input['oauth2_client_secret'] = $existing;
			} else {
				$new_input['oauth2_client_secret'] = \cf7_smtp_crypt( \sanitize_text_field( $input['oauth2_client_secret'] ) );
			}
		} else {
			$new_input['oauth2_client_secret'] = $this->options['oauth2_client_secret'] ?? '';
		}

		/* get the existing options */
		$opts      = new ActDeact();
		$new_input = \array_merge( $opts::default_options(), $this->options, $new_input );

		/* SMTP auth method */
		$new_input['auth_method'] = ! empty( $input['auth_method'] ) ? \sanitize_text_field( $input['auth_method'] ) : 'wp';

		/* SMTP enabled - Sync with auth_method */
		if ( 'wp' === $new_input['auth_method'] ) {
			$new_input['enabled'] = false;
		} else {
			$new_input['enabled'] = true;
		}

		/* SMTP preset */
		if ( ! empty( $input['preset'] ) ) {
			$selected_preset = $this->cf7_smtp_host_presets[ \sanitize_text_field( $input['preset'] ) ] ?? 'custom';
			if ( isset( $input['auth'] ) && isset( $input['port'] ) && isset( $input['host'] ) && $input['auth'] === $selected_preset['auth'] && $input['host'] === $selected_preset['host'] && \intval( $input['port'] ) === \intval( $selected_preset['port'] ) ) {
				$new_input['preset'] = \sanitize_text_field( $input['preset'] );
			} else {
				$new_input['preset'] = 'custom';
			}
		}

		/* Auth string */
		if ( isset( $input['auth'] ) ) {
			$auth = \sanitize_text_field( $input['auth'] );
			if ( \in_array( $auth, array( 'ssl', 'tls' ), true ) ) {
				$new_input['auth'] = $auth;
			} else {
				$new_input['auth'] = '';
			}
		}

		/* SMTP host */
		$new_input['host'] = ! empty( $input['host'] ) ? \sanitize_text_field( $input['host'] ) : $new_input['host'];

		/* SMTP port */
		$new_input['port'] = ! empty( $input['port'] ) ? \intval( $input['port'] ) : $new_input['port'];

		/* User name string */
		if ( isset( $input['user_name'] ) ) {
			$new_input['user_name'] = \sanitize_text_field( $input['user_name'] );
		}

		/* SMTP Password */
		if ( ! empty( $input['remove_user_pass'] ) ) {
			$new_input['user_pass'] = '';
		} elseif ( ! empty( $input['user_pass'] ) ) {
			$existing_pass = $this->options['user_pass'] ?? '';
			// Prevent double encryption if the submitted form just passes the encrypted string back
			if ( $input['user_pass'] !== $existing_pass ) {
				$new_input['user_pass'] = \cf7_smtp_crypt( \sanitize_text_field( $input['user_pass'] ) );
			}
		}

		/* Reply to */
		$new_input['insecure'] = ! empty( $input['insecure'] );

		/* Reply to */
		$new_input['replyTo'] = ! empty( $input['replyTo'] );

		/* From email string */
		if ( isset( $input['from_mail'] ) ) {
			$new_input['from_mail'] = \sanitize_email( $input['from_mail'] );
		}
		if ( isset( $input['from_name'] ) ) {
			$new_input['from_name'] = \sanitize_text_field( $input['from_name'] );
		}

		/* SMTP custom_template */
		$new_input['custom_template'] = ! empty( $input['custom_template'] );

		/* Report cron string */
		if ( isset( $input['report_every'] ) ) {
			if ( \array_key_exists( $input['report_every'], \wp_get_schedules() ) ) {
				$new_input['report_every'] = $input['report_every'];

				// schedule cron
				if (
					! $this->options ||
					! \wp_next_scheduled( 'cf7_smtp_send_report' ) ||
					( isset( $this->options['report_every'] ) && $this->options['report_every'] !== $input['report_every'] )
				) {
					\wp_clear_scheduled_hook( 'cf7_smtp_send_report' );
					if ( '' !== $input['report_every'] ) {
						// we add 2h waiting gap to let enough time to complete all the setup.
						\wp_schedule_event( \time() + 7200, $input['report_every'], 'cf7_smtp_send_report' );
					}
				}
			} elseif ( empty( $input['report_every'] ) ) {
				// unschedule cron
				if ( \wp_next_scheduled( 'cf7_smtp_send_report' ) ) {
					$new_input['report_every'] = '';
					\wp_clear_scheduled_hook( 'cf7_smtp_send_report' );
				}
			}//end if
		}//end if

		/* SMTP log retain days */
		$new_input['log_retain_days'] = ! empty( $input['log_retain_days'] ) ? \intval( $input['log_retain_days'] ) : $new_input['log_retain_days'];

		/* SMTP send report to */
		$new_input['report_to'] = empty( $input['report_to'] ) ? $new_input['report_to'] : \sanitize_text_field( $input['report_to'] );

		/* OAuth2 Authentication Type */
		if ( isset( $input['auth_type'] ) ) {
			$auth_type = \sanitize_text_field( $input['auth_type'] );
			if ( \in_array( $auth_type, array( 'basic', 'oauth2' ), true ) ) {
				$new_input['auth_type'] = $auth_type;
			}
		}

		// OAuth2 Provider - Redundant input, logic overwritten below anyway, but kept for JS hidden input sync if we need it, though normally hidden by JS anyway.
		if ( isset( $input['oauth2_provider'] ) ) {
			$valid_providers = array( '', 'gmail', 'office365' );
			if ( \in_array( $input['oauth2_provider'], $valid_providers, true ) ) {
				$new_input['oauth2_provider'] = \sanitize_text_field( $input['oauth2_provider'] );
			}
		}

		/* OAuth2 Client ID */
		if ( isset( $input['oauth2_client_id'] ) ) {
			$new_input['oauth2_client_id'] = \sanitize_text_field( $input['oauth2_client_id'] );
		}

		/* OAuth2 Client Secret - encrypt before storing */
		if ( ! empty( $input['remove_oauth2_client_secret'] ) ) {
			$new_input['oauth2_client_secret'] = '';
		} elseif ( ! empty( $input['oauth2_client_secret'] ) ) {
			// Only encrypt if the value is different from what's already stored.
			// This prevents double-encryption when update_option() triggers the
			// sanitize callback with the already-encrypted value.
			$existing = $this->options['oauth2_client_secret'] ?? '';
			if ( $input['oauth2_client_secret'] !== $existing ) {
				$new_input['oauth2_client_secret'] = \cf7_smtp_crypt( \sanitize_text_field( $input['oauth2_client_secret'] ) );
			}
		}

		/* Sync auth_type and oauth2_provider based on auth_method - Force override at the end */
		if ( 'gmail' === $new_input['auth_method'] ) {
			$new_input['auth_type']       = 'oauth2';
			$new_input['oauth2_provider'] = 'gmail';

			// Remove orphaned Settings
			$new_input['user_name'] = '';
			$new_input['user_pass'] = '';
			if ( ( $this->options['auth_method'] ?? '' ) !== 'gmail' ) {
				$new_input['oauth2_client_id']     = '';
				$new_input['oauth2_client_secret'] = '';
			}
		} elseif ( 'outlook' === $new_input['auth_method'] ) {
			$new_input['auth_type']       = 'oauth2';
			$new_input['oauth2_provider'] = 'office365';

			// Remove orphaned Settings
			$new_input['user_name'] = '';
			$new_input['user_pass'] = '';
			if ( ( $this->options['auth_method'] ?? '' ) !== 'outlook' ) {
				$new_input['oauth2_client_id']     = '';
				$new_input['oauth2_client_secret'] = '';
			}
		} elseif ( 'smtp' === $new_input['auth_method'] ) {
			// Regular SMTP uses basic authentication
			$new_input['auth_type'] = 'basic';
			// Remove orphaned OAuth settings if switching to regular SMTP
			$new_input['oauth2_client_id']     = '';
			$new_input['oauth2_client_secret'] = '';
			$new_input['oauth2_provider']      = '';
			$new_input['oauth2_access_token']  = '';
			$new_input['oauth2_refresh_token'] = '';
			$new_input['oauth2_expires']       = '';
			$new_input['oauth2_user_email']    = '';
			$new_input['oauth2_connected_at']  = '';
		} elseif ( 'wp' === $new_input['auth_method'] ) {
			// Remove everything if switching to WP Mail
			$new_input['host']                 = '';
			$new_input['port']                 = '';
			$new_input['user_name']            = '';
			$new_input['user_pass']            = '';
			$new_input['oauth2_client_id']     = '';
			$new_input['oauth2_client_secret'] = '';
			$new_input['oauth2_provider']      = '';
			$new_input['oauth2_access_token']  = '';
			$new_input['oauth2_refresh_token'] = '';
			$new_input['oauth2_expires']       = '';
			$new_input['oauth2_user_email']    = '';
			$new_input['oauth2_connected_at']  = '';
		}//end if
		// For 'smtp' auth_method, ensure auth_type is not oauth2 if basic is intended,
		// or let it be if user chose oauth2 for custom smtp

		return $new_input;
	}
}
