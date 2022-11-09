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
					'host' => '',
					'auth' => '',
					'port' => false,
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
			)
		);

		$this->auth_values = array( '', 'ssl', 'tls' );
	}

	/**
	 * It creates the settings page
	 */
	public function cf7_smtp_options_init() {

		/* Group */
		register_setting(
			CF7_SMTP_TEXTDOMAIN . '-settings',
			CF7_SMTP_TEXTDOMAIN . '-options',
			array( $this, 'cf7_smtp_sanitize_options' )
		);

		/* Section Bot Fingerprint */
		add_settings_section(
			'smtp_data',
			__( 'Smtp Server Setup', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_section_main_subtitle' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'enabled',
			__( 'Enable', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_enable_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'preset',
			__( 'SMTP configuration preset', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_preset_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp auth */
		add_settings_field(
			'auth',
			__( 'Auth', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_auth_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp host */
		add_settings_field(
			'host',
			__( 'Host', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_host_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp port */
		add_settings_field(
			'port',
			__( 'Port', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_port_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_name */
		add_settings_field(
			'user_name',
			__( 'SMTP User Name', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_user_name_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* Settings cf7_smtp user_pass */
		add_settings_field(
			'user_pass',
			__( 'SMTP Password', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_user_pass_callback' ),
			'smtp-settings',
			'smtp_data'
		);

		/* smtp_advanced */
		add_settings_section(
			'smtp_advanced',
			__( 'Advanced Options', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_empty_callback' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'custom_template',
			__( 'Custom Template', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_custom_template_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'advanced',
			__( 'Enable Overrides', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_advanced_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_mail',
			__( 'Override User Mail', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_from_mail_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_name',
			__( 'Override User Name', CF7_SMTP_TEXTDOMAIN ),
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
			esc_html__( 'The settings for this plugin', CF7_SMTP_TEXTDOMAIN )
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

		/* if is anna array add for array element an option */
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
	 *
	 * @return string a string of HTML.
	 */
	public function cf7_smtp_form_generate_radio( $selected = '' ) {
		// TODO user extendable?

		$html = '';
		foreach ( $this->auth_values as $auth ) {
			$auth_name = ! empty( $auth ) ? $auth : esc_html__( 'none', CF7_SMTP_TEXTDOMAIN );
			$html     .= sprintf(
				'<label><input type="radio" name="cf7-smtp-options[auth]" class="auth-%s" value="%s"%s/>%s</label>',
				$auth_name,
				$auth_name,
				$selected === $auth ? ' checked ' : ' ',
				$auth_name
			);
		}
		return $html;
	}

	/**
	 * It returns nothing
	 *
	 * @return void Nothing.
	 */
	public function cf7_smtp_print_empty_callback() {}

	/**
	 * It prints a radio button group with the value of the 'auth' key in the $this->options array
	 */
	public function cf7_smtp_print_auth_callback() {
		$auth_value = ! empty( $this->options['auth'] ) ? sanitize_key( $this->options['auth'] ) : '';
		printf(
			'<div id="cf7-smtp-auth">%s</div>',
			wp_kses(
				self::cf7_smtp_form_generate_radio( $auth_value ),
				array(
					'label' => array(),
					'input' => array(
						'type'    => array(),
						'name'    => array(),
						'class'   => array(),
						'value'   => array(),
						'checked' => array(),
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
		printf(
			'<input type="text" id="cf7_smtp_host" name="cf7-smtp-options[host]" value="%s" placeholder="localhost" />',
			! empty( $this->options['host'] ) ? esc_html( $this->options['host'] ) : ''
		);
	}

	/**
	 * It prints an input field with the id of cf7_smtp_port and the name of cf7-smtp-options[port] and the value of the port
	 * option
	 */
	public function cf7_smtp_print_port_callback() {
		printf(
			'<input type="number" id="cf7_smtp_port" name="cf7-smtp-options[port]" value="%s" />',
			! empty( $this->options['port'] ) ? intval( $this->options['port'] ) : ''
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_user_name, the name of cf7-smtp-options[user_name], and the value
	 * of the user_name key in the options array
	 */
	public function cf7_smtp_print_user_name_callback() {
		printf(
			'<input type="text" id="cf7_smtp_user_name" name="cf7-smtp-options[user_name]" value="%s" />',
			! empty( $this->options['user_name'] ) ? esc_html( $this->options['user_name'] ) : ''
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_user_pass, the name of cf7-smtp-options[user_pass], and the class
	 * of either safe or unsafe.
	 *
	 * The class of safe or unsafe is determined by whether or not the CF7_SMTP_PASSWORD constant is defined. If it is
	 * defined, the class is safe. If it is not defined, the class is unsafe.
	 *
	 * The placeholder attribute is set to *** if the CF7_SMTP_PASSWORD constant is defined. If it is not defined, the
	 * placeholder attribute is not set.
	 *
	 * The disabled attribute is set if the CF7_SMTP_PASSWORD constant is defined. If it is not defined, the disabled
	 * attribute is not set.
	 *
	 * The value of the text input field is set to the value of the user_pass key in the $this->options array.
	 */
	public function cf7_smtp_print_user_pass_callback() {
		printf(
			'<input type="text" id="cf7_smtp_user_pass" name="cf7-smtp-options[user_pass]" class="%s" autocomplete="off" %s %s />',
			empty( CF7_SMTP_PASSWORD ) ? 'unsafe' : 'safe',
			empty( $this->options['user_pass'] ) && empty( CF7_SMTP_PASSWORD ) ? '' : 'placeholder="***"',
			empty( CF7_SMTP_PASSWORD ) ? '' : 'disabled'
		);
	}

	/**
	 * It prints a checkbox with the id of cf7_smtp_custom_template and the name of cf7-smtp-options[custom_template] and if
	 * the custom_template option is not empty, it adds the checked="true" attribute to the checkbox
	 */
	public function cf7_smtp_print_custom_template_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_custom_template" name="cf7-smtp-options[custom_template]" %s />',
			! empty( $this->options['custom_template'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It prints a checkbox with the id of cf7_smtp_advanced and the name of cf7-smtp-options[advanced] and if the advanced
	 * option is not empty, it checks the box
	 */
	public function cf7_smtp_print_advanced_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_advanced" name="cf7-smtp-options[advanced]" %s />',
			! empty( $this->options['advanced'] ) ? 'checked="true"' : ''
		);
	}

	/**
	 * It prints an input field with the id of cf7_smtp_from_mail and the name of cf7-smtp-options[from_mail] and the value of
	 * the from_mail key in the options array
	 */
	public function cf7_smtp_print_from_mail_callback() {
		printf(
			'<input type="email" id="cf7_smtp_from_mail" name="cf7-smtp-options[from_mail]" value="%s" />',
			! empty( $this->options['from_mail'] ) ? sanitize_text_field( $this->options['from_mail'] ) : ''
		);
	}

	/**
	 * It prints a text input field with the id of cf7_smtp_from_name and the name of cf7-smtp-options[from_name] and the
	 * value of the from_name option
	 */
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
		if ( ! empty( $input['preset'] ) ) {
			$selected_preset = $this->cf7_smtp_host_presets[ sanitize_text_field( $input['preset'] ) ] ?? 'custom';
			if ( $input['auth'] === $selected_preset['auth'] && $input['host'] === $selected_preset['host'] && intval( $input['port'] ) === intval( $selected_preset['port'] ) ) {
				$new_input['preset'] = sanitize_text_field( $input['preset'] );
			}
		}

		/* SMTP preset */
		if ( ! empty( $input['auth'] ) ) {
			if ( in_array( $input['auth'], array( 'ssl', 'tls' ), true ) ) {
				$new_input['auth'] = sanitize_text_field( $input['auth'] );
			}
		}

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
