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
		$this->options = get_option( 'cf7-smtp-options' );

		$this->cf7_smtp_host_presets = apply_filters(
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
		register_setting(
			'cf7-smtp-settings',
			'cf7-smtp-options',
			array( $this, 'cf7_smtp_sanitize_options' )
		);

		/* Section Bot Fingerprint */
		add_settings_section(
			'smtp_data',
			__( 'Smtp Server Setup', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_main_subtitle' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'enabled',
			__( 'Enable', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_enable_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth_method */
		add_settings_field(
			'auth_method',
			__( 'Mail Service', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_auth_method_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp preset */
		add_settings_field(
			'preset',
			__( 'SMTP configuration preset', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_preset_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth */
		add_settings_field(
			'auth',
			__( 'Encryption', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_auth_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp smtp_mode */
		add_settings_field(
			'smtp_mode',
			__( 'SMTP Mode', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_smtp_mode_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp host */
		add_settings_field(
			'host',
			__( 'Host', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_host_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp port */
		add_settings_field(
			'port',
			__( 'Port', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_port_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_name */
		add_settings_field(
			'user_name',
			__( 'SMTP User Name', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_user_name_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_pass */
		add_settings_field(
			'user_pass',
			__( 'SMTP Password', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_user_pass_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* OAuth2 Section */
		add_settings_section(
			'smtp_oauth2',
			__( 'OAuth2 Authentication', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_section_callback' ),
			'smtp-settings'
		);

		/* OAuth2 Provider */
		add_settings_field(
			'oauth2_provider',
			__( 'OAuth2 Provider', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_provider_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Redirect URI (Read Only) */
		add_settings_field(
			'oauth2_redirect_uri',
			__( 'Authorized Redirect URI', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_redirect_uri_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Client ID */
		add_settings_field(
			'oauth2_client_id',
			__( 'Client ID', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_client_id_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Client Secret */
		add_settings_field(
			'oauth2_client_secret',
			__( 'Client Secret', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_client_secret_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* OAuth2 Connect Button */
		add_settings_field(
			'oauth2_connect',
			__( 'OAuth2 Connection', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_oauth2_connect_callback' ),
			'smtp-settings',
			'smtp_oauth2',
			array( 'class' => 'cf7-smtp-oauth-row' )
		);

		/* smtp_advanced */
		add_settings_section(
			'smtp_advanced',
			__( 'Advanced Options', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_advanced_callback' ),
			'smtp-settings-advanced'
		);

		/* allow insecure options */
		add_settings_field(
			'insecure',
			__( 'Allow insecure options', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_insecure_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Reply to */
		add_settings_field(
			'replyTo',
			__( 'Add Reply To', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_reply_to_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_mail',
			__( 'From mail', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_from_mail_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_name',
			__( 'From name', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_from_name_callback' ),
			'smtp-settings-advanced',
			'smtp_advanced'
		);

		/* Section style */
		add_settings_section(
			'smtp_style',
			__( 'Style', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_style_subtitle' ),
			'smtp-style'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'custom_template',
			__( 'Custom Template', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_custom_template_callback' ),
			'smtp-style',
			'smtp_style'
		);

		/* Section cron */
		add_settings_section(
			'smtp_cron',
			__( 'Report', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_section_cron_subtitle' ),
			'smtp-cron'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'report_every',
			__( 'Schedule report every', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_every_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp log retain days */
		add_settings_field(
			'log_retain_days',
			__( 'Log retention days', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_log_retain_days_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Flush logs */
		add_settings_field(
			'flush_logs',
			__( 'Flush logs', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_flush_logs_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'report_to',
			__( 'Email report to', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_to_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'report_now',
			__( 'Send report now', 'cf7-smtp' ),
			array( $this, 'cf7_smtp_print_report_now_callback' ),
			'smtp-cron',
			'smtp_cron'
		);
	}

	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_main_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Welcome! Remember that you can activate and deactivate the smtp service simply by ticking the checkbox below', 'cf7-smtp' )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_style_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Add an html template that wraps the email. (ps. You can enable a user-defined template, see the documentation for more information)', 'cf7-smtp' )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_cron_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Do you want to know if the mails are running smoothly? Let me occasionally e-mail a summary to verify the functionality.', 'cf7-smtp' )
		);
	}

	/**
	 * It returns nothing
	 *
	 * @return void Nothing.
	 */
	public function cf7_smtp_print_advanced_callback() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Leave empty to NOT override the WordPress defaults (the one used can be different from the one you see below, if left blank the one set in Contact Form 7 will be used)', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 section description.
	 */
	public function cf7_smtp_print_oauth2_section_callback() {
		printf(
			'<div id="cf7_smtp_oauth2_section_desc"><p>%s</p></div>',
			esc_html__( 'Use OAuth2 for secure authentication without storing passwords. Connect with your email provider using OAuth2.', 'cf7-smtp' )
		);
	}



	/**
	 * Prints the OAuth2 provider select field.
	 */
	public function cf7_smtp_print_oauth2_provider_callback() {
		$oauth2_handler = new \cf7_smtp\Core\OAuth2_Handler();
		$providers      = $oauth2_handler->get_providers();
		$current        = ! empty( $this->options['oauth2_provider'] ) ? $this->options['oauth2_provider'] : '';

		$options_html = '<option value="">' . esc_html__( 'Select a provider', 'cf7-smtp' ) . '</option>';
		foreach ( $providers as $key => $name ) {
			$options_html .= sprintf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				$current === $key ? 'selected' : '',
				esc_html( $name )
			);
		}

		printf(
			'<select id="cf7_smtp_oauth2_provider" name="cf7-smtp-options[oauth2_provider]" class="cf7-smtp-oauth2-field">%s</select>',
			$options_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above.
		);
	}

	/**
	 * Prints the OAuth2 Redirect URI (read-only) for the user to copy.
	 */
	public function cf7_smtp_print_oauth2_redirect_uri_callback() {
		$redirect_uri = \admin_url( 'admin.php' );
		// Ensure the redirect URI matches exactly what the OAuth2 provider expects
		$redirect_uri = \add_query_arg(
			array(
				'page'            => 'cf7-smtp',
				'oauth2_callback' => 1,
			),
			$redirect_uri
		);

		\printf(
			'<code style="user-select: all; background: #f0f0f1; padding: 5px 10px; display: block; margin-bottom: 5px;">%s</code>
			<p class="description">%s</p>',
			\esc_html( $redirect_uri ),
			\esc_html__( 'Copy this URL and add it to your "Authorized Redirect URIs" in your Google Cloud Console or OAuth2 Provider settings.', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 Client ID field.
	 */
	public function cf7_smtp_print_oauth2_client_id_callback() {
		$client_id = $this->cf7_smtp_find_setting( 'oauth2_client_id' );
		printf(
			'<input type="text" id="cf7_smtp_oauth2_client_id" name="cf7-smtp-options[oauth2_client_id]" value="%s" class="regular-text cf7-smtp-oauth2-field" %s />
			<p class="description">%s</p>',
			esc_attr( $client_id['value'] ?? '' ),
			esc_html( empty( $client_id['defined'] ) ? '' : 'disabled' ),
			esc_html__( 'Enter the Client ID from your OAuth2 provider (e.g., Google Cloud Console).', 'cf7-smtp' )
		);
	}

	/**
	 * Prints the OAuth2 Client Secret field.
	 */
	public function cf7_smtp_print_oauth2_client_secret_callback() {
		$client_secret = $this->cf7_smtp_find_setting( 'oauth2_client_secret' );
		$has_value     = ! empty( $client_secret['value'] );
		printf(
			'<input type="password" id="cf7_smtp_oauth2_client_secret" name="cf7-smtp-options[oauth2_client_secret]" class="regular-text cf7-smtp-oauth2-field" %s placeholder="%s" />
			<p class="description">%s</p>',
			esc_html( empty( $client_secret['defined'] ) ? '' : 'disabled' ),
			$has_value ? esc_attr__( '••••••••', 'cf7-smtp' ) : '',
			esc_html__( 'Enter the Client Secret from your OAuth2 provider. This will be encrypted before storing.', 'cf7-smtp' )
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
			$connected_email = $status['user_email'] ?? __( 'Connected', 'cf7-smtp' );
			printf(
				'<div class="cf7-smtp-oauth2-status cf7-smtp-oauth2-status--connected">
					<span class="dashicons dashicons-yes-alt"></span>
					<span class="cf7-smtp-oauth2-status-text">%s: <strong>%s</strong></span>
				</div>
				<button type="button" id="cf7_smtp_oauth2_disconnect" class="button button-secondary cf7-smtp-oauth2-field">%s</button>',
				esc_html__( 'Connected as', 'cf7-smtp' ),
				esc_html( $connected_email ),
				esc_html__( 'Disconnect', 'cf7-smtp' )
			);
		} else {
			printf(
				'<div class="cf7-smtp-oauth2-status cf7-smtp-oauth2-status--disconnected">
					<span class="dashicons dashicons-warning"></span>
					<span class="cf7-smtp-oauth2-status-text">%s</span>
				</div>
				<button type="button" id="cf7_smtp_oauth2_connect" class="button button-primary cf7-smtp-oauth2-field">%s</button>
				<p class="description">%s</p>',
				esc_html__( 'Not connected', 'cf7-smtp' ),
				esc_html__( 'Connect with OAuth2', 'cf7-smtp' ),
				esc_html__( 'Save your Client ID and Client Secret first, then click Connect.', 'cf7-smtp' )
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
				'label' => __( 'WordPress', 'cf7-smtp' ),
				'icon'  => 'dashicons-wordpress',
			),
			'smtp'    => array(
				'label' => __( 'Other SMTP', 'cf7-smtp' ),
				'icon'  => 'dashicons-email-alt',
			),
			'gmail'   => array(
				'label' => __( 'Gmail', 'cf7-smtp' ),
				// Placeholder for SVG or use dashicon for now
				'icon'  => 'dashicons-google',
			),
			'outlook' => array(
				'label' => __( 'Outlook', 'cf7-smtp' ),
				'icon'  => 'dashicons-email-alt2',
			),
		);

		echo '<div class="cf7-smtp-auth-grid">';
		foreach ( $methods as $key => $props ) {
			printf(
				'<label class="cf7-smtp-auth-card %s">
					<input type="radio" name="cf7-smtp-options[auth_method]" value="%s" %s %s />
					<div class="cf7-smtp-auth-card-content">
						<span class="dashicons %s"></span>
						<span class="cf7-smtp-auth-label">%s</span>
					</div>
				</label>',
				$current === $key ? 'selected' : '',
				esc_attr( $key ),
				$current === $key ? 'checked' : '',
				esc_attr( $disabled ),
				esc_attr( $props['icon'] ),
				esc_html( $props['label'] )
			);
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Choose your preferred mail delivery method.', 'cf7-smtp' ) . '</p>';

		// Warning box for invalid/WP selection
		printf(
			'<div id="cf7-smtp-wp-warning" class="notice notice-warning inline" style="display:none; margin-top: 15px;">
				<p>%s</p>
			</div>',
			esc_html__( 'Warning: You are using the default WordPress mailer. Your emails might end up in spam.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a checkbox with the id of `cf7_smtp_enabled` and the name of `cf7-smtp-options[enabled]` and if the `enabled`
	 * option is set, it will be checked
	 */
	public function cf7_smtp_print_enable_callback() {
		printf(
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
				$option_data .= ! empty( $value ) ? sprintf( ' data-%s="%s"', sanitize_key( $prop ), sanitize_text_field( $value ) ) : '';
			}

			$select_opt .= sprintf(
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
			$html .= sprintf(
				'<option value="%s" %s>%s</option>',
				sanitize_key( $key ),
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

		printf(
			'<select type="checkbox" id="cf7_smtp_preset" name="cf7-smtp-options[preset]">%s</select>',
			wp_kses(
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
			$auth_name = 'tls' === $auth || 'ssl' === $auth ? $auth : esc_html__( 'none', 'cf7-smtp' );
			$html     .= sprintf(
				'<label><input type="radio" name="cf7-smtp-options[auth]" class="auth-%s" value="%s"%s%s/>%s</label>',
				esc_attr( $auth_name ),
				esc_attr( $auth_name ),
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
		printf(
			'<span class="smtp-options-wrapper checkbox-wrapper flex">
				<input type="checkbox" id="cf7_smtp_insecure" name="cf7-smtp-options[insecure]" %s %s />
				<p class="description">%s</p>
			</span>',
			empty( $insecure['value'] ) ? '' : 'checked="true"',
			esc_html( empty( $insecure['defined'] ) ? '' : 'disabled' ),
			esc_html__( 'Enable ONLY if your SMTP server uses self-signed certificates or outdated TLS versions. Warning: This reduces the security of your mail transmission.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a checkbox with the id of cf7_smtp_replyTo and the name of cf7-smtp-options[replyTo] and if the option is
	 * set, it will be checked
	 */
	public function cf7_smtp_print_reply_to_callback() {
		$reply_to = $this->cf7_smtp_find_setting( 'replyTo' );
		printf(
			'<span class="smtp-options-wrapper checkbox-wrapper flex">
				<input type="checkbox" id="cf7_smtp_replyTo" name="cf7-smtp-options[replyTo]" %s %s />
				<p class="description">%s</p>
			</span>',
			empty( $reply_to['value'] ) ? '' : 'checked="true"',
			esc_html( empty( $reply_to['defined'] ) ? '' : 'disabled' ),
			esc_html__( 'Check this if you want the "Reply-To" header to be set automatically. This allows users to reply to a different address than the one used to send the email.', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a radio button group with the value of the 'auth' key in the $this->options array
	 */
	public function cf7_smtp_print_auth_callback() {
		$auth = $this->cf7_smtp_find_setting( 'auth' );
		printf(
			'<div id="cf7-smtp-auth">%s</div>',
			wp_kses(
				self::cf7_smtp_form_generate_radio( esc_html( $auth['value'] ), $auth['defined'] ),
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
		printf(
			'<input type="text" id="cf7_smtp_host" name="cf7-smtp-options[host]" value="%s" %s placeholder="localhost" />',
			esc_attr( empty( $host['value'] ) ? '' : esc_html( $host['value'] ) ),
			esc_html( empty( $host['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints an input field with the id of cf7_smtp_port and the name of cf7-smtp-options[port] and the value of the port
	 * option
	 */
	public function cf7_smtp_print_port_callback() {
		$port = $this->cf7_smtp_find_setting( 'port' );
		printf(
			'<input type="number" id="cf7_smtp_port" name="cf7-smtp-options[port]" value="%s" %s min="0" max="65353" step="1" />',
			esc_attr( empty( $port['value'] ) ? '' : intval( $port['value'] ) ),
			esc_html( empty( $port['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_user_name, the name of cf7-smtp-options[user_name], and the value
	 * of the user_name key in the options array
	 */
	public function cf7_smtp_print_user_name_callback() {
		$user_name = $this->cf7_smtp_find_setting( 'user_name' );
		printf(
			'<input type="text" id="cf7_smtp_user_name" name="cf7-smtp-options[user_name]" value="%s" %s />',
			esc_attr( empty( $user_name['value'] ) ? '' : esc_html( $user_name['value'] ) ),
			esc_html( empty( $user_name['defined'] ) ? '' : 'disabled' )
		);
	}

	/**
	 * It prints a radio button group for the SMTP mode (all emails vs cf7 only)
	 */
	public function cf7_smtp_print_smtp_mode_callback() {
		$smtp_mode = $this->cf7_smtp_find_setting( 'smtp_mode' );
		$current   = ! empty( $smtp_mode['value'] ) ? $smtp_mode['value'] : 'all';
		$disabled  = ! empty( $smtp_mode['defined'] ) ? 'disabled' : '';

		printf(
			'<div id="cf7-smtp-mode">
				<label><input type="radio" name="cf7-smtp-options[smtp_mode]" value="all" %s %s /> %s</label><br>
				<label><input type="radio" name="cf7-smtp-options[smtp_mode]" value="cf7_only" %s %s /> %s</label>
			</div>
			<p class="description">%s</p>',
			'all' === $current ? 'checked' : '',
			esc_attr( $disabled ),
			esc_html__( 'Send all emails via SMTP', 'cf7-smtp' ),
			'cf7_only' === $current ? 'checked' : '',
			esc_attr( $disabled ),
			esc_html__( 'Send only Contact Form 7 emails via SMTP', 'cf7-smtp' ),
			esc_html__( 'Choose "Send Only CF7 Emails" to use the default WordPress mailer for other emails.Choose whether to route all WordPress outgoing mail through SMTP or restrict it only to Contact Form 7. Global routing is recommended for better deliverability of password resets and notifications.', 'cf7-smtp' )
		);
	}


	/**
	 * It prints a text input field with the id of cf7_smtp_user_pass, the name of cf7-smtp-options[user_pass], and a class of
	 * either safe or unsafe
	 */
	public function cf7_smtp_print_user_pass_callback() {
		$user_pass     = $this->cf7_smtp_find_setting( 'user_pass', true );
		$user_pass_val = esc_html( $user_pass['value'] );
		if ( $user_pass['defined'] ) {
			cf7_smtp_update_settings( array( 'user_pass' => '' ) );
		}
		printf(
			'<input type="text" id="cf7_smtp_user_pass" name="cf7-smtp-options[user_pass]" class="%s" autocomplete="off" %s %s />',
			empty( $user_pass['defined'] ) ? 'unsafe' : 'safe',
			wp_kses( empty( $user_pass_val ) ? '' : 'placeholder=' . cf7_smtp_print_pass_placeholders( $user_pass_val ) . '', array( 'placeholder' => array() ) ),
			esc_html( 'defined' === $user_pass_val ? 'disabled' : '' )
		);
	}

	/**
	 * It prints an input field with the id of cf7_smtp_from_mail and the name of cf7-smtp-options[from_mail] and the value of
	 * the from_mail key in the options array
	 */
	public function cf7_smtp_print_from_mail_callback() {
		$from_mail = $this->cf7_smtp_find_setting( 'from_mail' );
		printf(
			'<span class="smtp-options-wrapper flex">
				<input type="email" id="cf7_smtp_from_mail" name="cf7-smtp-options[from_mail]" value="%s" %s placeholder="wordpress@example.com" />
				<button type="button" id="cf7_smtp_check_dns" class="button button-secondary">%s</button>
			</span>
			<p class="description">%s</p>
			<div id="cf7_smtp_dns_result"></div>',
			esc_attr( empty( $from_mail['value'] ) ? '' : esc_html( $from_mail['value'] ) ),
			esc_html( empty( $from_mail['defined'] ) ? '' : 'disabled' ),
			esc_html__( 'Check DNS now', 'cf7-smtp' ),
			esc_html__( 'Crucial for deliverability. This address should match the domain of your SMTP account to satisfy SPF and DKIM checks. If left empty, the plugin will use the WordPress default or the one set in CF7, which might cause "Spoofing" alerts.', 'cf7-smtp' )
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
		printf(
			'<input type="text" id="cf7_smtp_from_name" name="cf7-smtp-options[from_name]" value="%s" %s placeholder="WordPress" />
			<p class="description">%s</p>',
			esc_attr( isset( $from_name['value'] ) ? esc_html( $from_name['value'] ) : '' ),
			esc_html( $from_name['defined'] ? 'disabled' : '' ),
			esc_html__( 'The display name shown in the recipient\'s inbox (e.g., "Your Company Support").', 'cf7-smtp' )
		);
	}

	/**
	 * It generates a select box with the options 'disabled', '60sec', '5min', 'hourly', 'twicedaily', 'daily', 'weekly' and the default value is 'disabled'
	 */
	public function cf7_smtp_print_report_every_callback() {
		/* the list of available schedules */
		$schedules = wp_get_schedules();
		$schedules = array_merge( array( '' => array( 'display' => 'disabled' ) ), $schedules );
		printf(
			'<select id="report_every" name="cf7-smtp-options[report_every]">%s</select>',
			wp_kses(
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
		printf(
			'<input type="number" id="cf7_smtp_log_retain_days" name="cf7-smtp-options[log_retain_days]" value="%s" min="0" max="365" step="1" />',
			esc_attr( ! empty( $this->options['log_retain_days'] ) ? intval( $this->options['log_retain_days'] ) : 30 )
		);
	}

	/**
	 * Print the flush logs button callback
	 *
	 * @return void
	 */
	public function cf7_smtp_print_flush_logs_callback() {
		printf(
			'<button id="cf7_smtp_flush_logs" class="button" />%s</button>',
			esc_html__( 'Flush Logs', 'cf7-smtp' )
		);
	}

	/**
	 * It prints a checkbox with the id of cf7_smtp_custom_template and the name of cf7-smtp-options[custom_template] and if
	 * the custom_template option is not empty, it adds the checked="true" attribute to the checkbox
	 */
	public function cf7_smtp_print_custom_template_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_custom_template" name="cf7-smtp-options[custom_template]" %s />',
			empty( $this->options['custom_template'] ) ? '' : 'checked="true"'
		);
	}

	/**
	 * It prints a text input field with the id of `cf7_smtp_report_to` and the name of `cf7-smtp-options[report_to]` and the
	 * value of the `report_to` key in the `$this->options` array
	 */
	public function cf7_smtp_print_report_to_callback() {
		printf(
			'<input type="text" id="cf7_smtp_report_to" name="cf7-smtp-options[report_to]" value="%s" />',
			empty( $this->options['report_to'] ) ? esc_html( wp_get_current_user()->user_email ) : esc_html( $this->options['report_to'] )
		);
	}

	/**
	 * Print the report now button callback
	 *
	 * @return void
	 */
	public function cf7_smtp_print_report_now_callback() {
		printf(
			'<button id="cf7_smtp_report_now" class="button" />%s</button>',
			esc_html__( 'Send Report Now', 'cf7-smtp' )
		);
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys.
	 * @return array $options sanitized
	 */
	public function cf7_smtp_sanitize_options( $input ) {

		$opts = new ActDeact();

		/* get the existing options */
		$new_input = array_merge( $opts::default_options(), $this->options );

		/* SMTP auth method */
		$new_input['auth_method'] = ! empty( $input['auth_method'] ) ? sanitize_text_field( $input['auth_method'] ) : 'wp';

		/* SMTP enabled - Sync with auth_method */
		if ( 'wp' === $new_input['auth_method'] ) {
			$new_input['enabled'] = false;
		} else {
			$new_input['enabled'] = true;
		}

		/* SMTP preset */
		if ( ! empty( $input['preset'] ) ) {
			$selected_preset = $this->cf7_smtp_host_presets[ sanitize_text_field( $input['preset'] ) ] ?? 'custom';
			if ( isset( $input['auth'] ) && isset( $input['port'] ) && isset( $input['host'] ) && $input['auth'] === $selected_preset['auth'] && $input['host'] === $selected_preset['host'] && intval( $input['port'] ) === intval( $selected_preset['port'] ) ) {
				$new_input['preset'] = sanitize_text_field( $input['preset'] );
			} else {
				$new_input['preset'] = 'custom';
			}
		}

		/* SMTP preset */
		if ( isset( $input['auth'] ) ) {
			$auth = sanitize_text_field( $input['auth'] );
			if ( in_array( $auth, array( 'ssl', 'tls' ), true ) ) {
				$new_input['auth'] = $auth;
			} else {
				$new_input['auth'] = '';
			}
		}

		/* SMTP host */
		$new_input['host'] = ! empty( $input['host'] ) ? sanitize_text_field( $input['host'] ) : $new_input['host'];

		/* SMTP port */
		$new_input['port'] = ! empty( $input['port'] ) ? intval( $input['port'] ) : $new_input['port'];

		/* SMTP UserName */
		$new_input['user_name'] = isset( $input['user_name'] ) ? sanitize_text_field( $input['user_name'] ) : $new_input['user_name'];

		/* SMTP Password */
		$new_input['user_pass'] = ! empty( $input['user_pass'] ) ? cf7_smtp_crypt( sanitize_text_field( $input['user_pass'] ) ) : $new_input['user_pass'];

		/* Reply to */
		$new_input['insecure'] = ! empty( $input['insecure'] );

		/* Reply to */
		$new_input['replyTo'] = ! empty( $input['replyTo'] );

		/* SMTP from Mail */
		$new_input['from_mail'] = isset( $input['from_mail'] ) ? sanitize_email( $input['from_mail'] ) : $new_input['from_mail'];

		/* SMTP from UserName */
		$new_input['from_name'] = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : $new_input['from_name'];

		/* SMTP custom_template */
		$new_input['custom_template'] = ! empty( $input['custom_template'] );

		/* SMTP report cron schedule */
		$schedule = wp_get_schedules();
		if ( isset( $input['report_every'] ) && in_array( $input['report_every'], array_keys( $schedule ), true ) ) {
			if ( $this->options['report_every'] !== $input['report_every'] ) {
				$new_input['report_every'] = $input['report_every'];

				/* delete previous scheduled events */
				$timestamp = wp_next_scheduled( 'cf7_smtp_report' );
				if ( $timestamp ) {
					wp_clear_scheduled_hook( 'cf7_smtp_report' );
				}

				/* add the new scheduled event */
				wp_schedule_event( time() + $schedule[ $new_input['report_every'] ]['interval'], $new_input['report_every'], 'cf7_smtp_report' );
			}
		} else {
			$new_input['report_every'] = '';
			/* Get the timestamp for the next event. */
			$timestamp = wp_next_scheduled( 'cf7_smtp_report' );
			if ( $timestamp ) {
				wp_clear_scheduled_hook( 'cf7_smtp_report' );
			}
		}//end if

		/* SMTP log retain days */
		$new_input['log_retain_days'] = ! empty( $input['log_retain_days'] ) ? intval( $input['log_retain_days'] ) : $new_input['log_retain_days'];

		/* SMTP send report to */
		$new_input['report_to'] = empty( $input['report_to'] ) ? $new_input['report_to'] : sanitize_text_field( $input['report_to'] );

		/* OAuth2 Authentication Type */
		if ( isset( $input['auth_type'] ) ) {
			$auth_type = sanitize_text_field( $input['auth_type'] );
			if ( in_array( $auth_type, array( 'basic', 'oauth2' ), true ) ) {
				$new_input['auth_type'] = $auth_type;
			}
		}

		/* OAuth2 Provider */
		if ( isset( $input['oauth2_provider'] ) ) {
			$valid_providers = array( '', 'gmail', 'office365' );
			if ( in_array( $input['oauth2_provider'], $valid_providers, true ) ) {
				$new_input['oauth2_provider'] = sanitize_text_field( $input['oauth2_provider'] );
			}
		}

		/* OAuth2 Client ID */
		if ( isset( $input['oauth2_client_id'] ) ) {
			$new_input['oauth2_client_id'] = sanitize_text_field( $input['oauth2_client_id'] );
		}

		/* OAuth2 Client Secret - encrypt before storing */
		if ( ! empty( $input['oauth2_client_secret'] ) ) {
			// Only encrypt if the value is different from what's already stored.
			// This prevents double-encryption when update_option() triggers the
			// sanitize callback with the already-encrypted value.
			$existing = $this->options['oauth2_client_secret'] ?? '';
			if ( $input['oauth2_client_secret'] !== $existing ) {
				$new_input['oauth2_client_secret'] = cf7_smtp_crypt( sanitize_text_field( $input['oauth2_client_secret'] ) );
			}
		}

		/* Sync auth_type and oauth2_provider based on auth_method - Force override at the end */
		if ( 'gmail' === $new_input['auth_method'] ) {
			$new_input['auth_type']       = 'oauth2';
			$new_input['oauth2_provider'] = 'gmail';
		} elseif ( 'outlook' === $new_input['auth_method'] ) {
			$new_input['auth_type']       = 'oauth2';
			$new_input['oauth2_provider'] = 'office365';
		}
		// For 'smtp' auth_method, ensure auth_type is not oauth2 if basic is intended,
		// or let it be if user chose oauth2 for custom smtp

		return $new_input;
	}

	/**
	 * It handles the actions that are triggered by the user
	 */
	public function cf7_smtp_handle_actions() {

		if ( ! isset( $_REQUEST ) || empty( $_REQUEST['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ) ) ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : false;
		$url    = esc_url( menu_page_url( 'cf7-smtp', false ) );

		if ( 'dismiss-banner' === $action ) {
			if ( get_user_meta( get_current_user_id(), 'cf7_smtp_hide_welcome_panel_on', true ) ) {
				update_user_meta( get_current_user_id(), 'cf7_smtp_hide_welcome_panel_on', true );
			} else {
				add_user_meta( get_current_user_id(), 'cf7_smtp_hide_welcome_panel_on', true, true );
			}

			wp_safe_redirect( $url );
			exit();
		}
	}
}
