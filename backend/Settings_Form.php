<?php
/**
 * cf7_smtp
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
	 * @var false|mixed|null
	 */
	private $options;

	/**
	 * Initialize the class
	 *
	 * @return void|bool
	 */
	public function __construct() {
		$this->options = get_option( 'cf7-smtp-options' );
	}

	/**
	 * It creates the settings page
	 */
	public function cf7_smtp_options_init() {

		/* Group */
		register_setting(
			C_TEXTDOMAIN . '-settings',
			C_TEXTDOMAIN . '-options',
			array( $this, 'cf7_smtp_sanitize_options' )
		);

		/* Section Bot Fingerprint */
		add_settings_section(
			'smtp_data',
			__( 'Smtp Server Setup', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_section_main_subtitle' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'enabled',
			__( 'Enable', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_enable_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'preset',
			__( 'SMTP configuration preset', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_preset_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth */
		add_settings_field(
			'auth',
			__( 'Auth', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_auth_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp host */
		add_settings_field(
			'host',
			__( 'Host', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_host_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp port */
		add_settings_field(
			'port',
			__( 'Port', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_port_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_name */
		add_settings_field(
			'user_name',
			__( 'SMTP User Name', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_user_name_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_pass */
		add_settings_field(
			'user_pass',
			__( 'SMTP Password', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_user_pass_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* smtp_advanced */
		add_settings_section(
			'smtp_advanced',
			__( 'Advanced Options', C_TEXTDOMAIN ),
			null,
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'custom_template',
			__( 'Custom Template', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_custom_template_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'advanced',
			__( 'Enable Overrides', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_advanced_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_mail',
			__( 'Override User Mail', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_from_mail_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_name',
			__( 'Override User Name', C_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_from_name_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);
	}

	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_main_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'The settings for this plugin', C_TEXTDOMAIN )
		);
	}

	public function cf7_smtp_print_enable_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_enabled" name="cf7-smtp-options[enabled]" %s />',
			! empty( $this->options['enabled'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7_smtp_form_array_to_options($options) {
		$select_opt = "";

		/* if is anna array add for array element an option */
		if (is_array($options)) foreach ($options as $option => $options_data) {
			$option_data = '';
			foreach ($options_data as $prop => $value) {
				$option_data .= sprintf( ' data-%s="%s"', sanitize_key( $prop ), sanitize_text_field( $value ) );
			}
			$select_opt .= "<option value='{$option}'{$option_data}>{$option}</option>";
		}

		return $select_opt;
	}

	public function cf7_smtp_print_preset_callback() {

		printf(
			'<select type="checkbox" id="cf7_smtp_preset" name="cf7-smtp-options[preset]">%s</select>',
			self::cf7_smtp_form_array_to_options([
				'custom'      => ['host' => '', 'auth' => '', 'port' => false ],
				'Aruba'       => ['host' => 'smtps.aruba.it', 'auth' => 'ssl', 'port' => 465 ],
				'Gmail (ssl)' => ['host' => 'smtp.gmail.com', 'auth' => 'ssl', 'port' => 465 ],
				'Gmail (tls)' => ['host' => 'smtp.gmail.com', 'auth' => 'tls', 'port' => 587 ],
				'Yahoo (ssl)' => ['host' => 'pop.mail.yahoo.com', 'auth' => 'ssl', 'port' => 465 ],
				'Yahoo (tls)' => ['host' => 'pop.mail.yahoo.com', 'auth' => 'tls', 'port' => 587 ],
				'Outlook.com' => ['host' => 'smtp-mail.outlook.com', 'auth' => 'tls', 'port' => 587 ]
			])
		);
	}

	public function cf7_smtp_form_generate_radio($selected = '') {
		//TODO user extendable?
		$auth_values = array( "", "ssl", "tls" );
		$html = '';
		foreach ($auth_values as $auth) {
			$auth_name = ! empty( $auth ) ? $auth : esc_html__( 'none' );
			$html .= sprintf( '<label><input type="radio" name="cf7-smtp-options[auth]" class="auth-%s" value="%s"%s/>%s</label>',
				$auth_name,
				$auth_name,
				$selected === $auth ? ' checked ' : ' ',
				$auth_name
			);
		}
		return $html;
	}

	public function cf7_smtp_print_auth_callback() {
		$auth_value = ! empty( $this->options['auth'] ) ? sanitize_key( $this->options['auth'] ) : '';
		printf( '<div id="cf7-smtp-auth">%s</div>',
			self::cf7_smtp_form_generate_radio($auth_value)
		);
	}

	public function cf7_smtp_print_host_callback() {
		printf(
			'<input type="text" id="cf7_smtp_host" name="cf7-smtp-options[host]" value="%s" placeholder="localhost" />',
			! empty( $this->options['host'] ) ? sanitize_text_field( $this->options['host'] ) : ''
		);
	}

	public function cf7_smtp_print_port_callback() {
		printf(
			'<input type="number" id="cf7_smtp_port" name="cf7-smtp-options[port]" value="%s" />',
			! empty( $this->options['port'] ) ? intval( $this->options['port'] ) : ''
		);
	}

	public function cf7_smtp_print_user_name_callback() {
		printf(
			'<input type="text" id="cf7_smtp_user_name" name="cf7-smtp-options[user_name]" value="%s" />',
			! empty( $this->options['user_name'] ) ? sanitize_text_field( $this->options['user_name'] ) : ''
		);
	}

	public function cf7_smtp_print_user_pass_callback() {
		printf(
			'<input type="text" id="cf7_smtp_user_pass" name="cf7-smtp-options[user_pass]" class="%s" %s %s />',
			empty( CF7_SMTP_PASSWORD ) ? 'unsafe' : 'safe',
			empty( $this->options['user_pass'] ) && empty( CF7_SMTP_PASSWORD ) ? '' : 'placeholder="***"',
			empty( CF7_SMTP_PASSWORD ) ? '' : 'disabled'
		);
	}

	public function cf7_smtp_print_custom_template_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_custom_template" name="cf7-smtp-options[custom_template]" %s />',
			! empty( $this->options['custom_template'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7_smtp_print_advanced_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_advanced" name="cf7-smtp-options[advanced]" %s />',
			! empty( $this->options['advanced'] ) ? 'checked="true"' : ''
		);
	}

	public function cf7_smtp_print_from_mail_callback() {
		printf(
			'<input type="email" id="cf7_smtp_from_mail" name="cf7-smtp-options[from_mail]" value="%s" />',
			! empty( $this->options['from_mail'] ) ? sanitize_text_field( $this->options['from_mail'] ) : ''
		);
	}

	public function cf7_smtp_print_from_name_callback() {
		printf(
			'<input type="text" id="cf7_smtp_from_name" name="cf7-smtp-options[from_name]" value="%s" />',
			! empty( $this->options['from_name'] ) ? sanitize_text_field( $this->options['from_name'] ) : ''
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

		/* SMTP enabled */
		$new_input['enabled'] = ! empty( $input['enabled'] );

		/* SMTP preset */
		$new_input['preset'] = ! empty( $input['preset'] ) ? sanitize_text_field( $input['preset'] ) : $new_input['preset'];

		/* SMTP preset */
		$new_input['auth'] = ! empty( $input['auth'] ) ? sanitize_text_field( $input['auth'] ) : $new_input['auth'];

		/* SMTP host */
		$new_input['host'] = ! empty( $input['host'] ) ? sanitize_text_field( $input['host'] ) : $new_input['host'];

		/* SMTP port */
		$new_input['port'] = ! empty( $input['port'] ) ? intval( $input['port'] ) : $new_input['port'];

		/* SMTP UserName */
		$new_input['user_name'] = ! empty( $input['user_name'] ) ? sanitize_text_field( $input['user_name'] ) : $new_input['user_name'];

		/* SMTP Password */
		$new_input['user_pass'] = ! empty( $input['user_pass'] ) ? cf7_smtp_crypt( sanitize_text_field( $input['user_pass'] ) ) : $new_input['user_pass'];

		/* SMTP custom_template */
		$new_input['custom_template'] = ! empty( $input['custom_template'] );

		/* SMTP advanced */
		$new_input['advanced'] = ! empty( $input['advanced'] );

		/* SMTP from Mail */
		$new_input['from_mail'] = ! empty( $input['from_mail'] ) ? sanitize_email( $input['from_mail'] ) : $new_input['from_mail'];

		/* SMTP from UserName */
		$new_input['from_name'] = ! empty( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : $new_input['from_name'];

		return $new_input;
	}

	/**
	 * It handles the actions that are triggered by the user
	 */
	public function cf7_smtp_handle_actions() {

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
