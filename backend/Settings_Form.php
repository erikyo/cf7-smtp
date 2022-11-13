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
			array( $this, 'cf7_smtp_print_advanced_callback' ),
			'smtp-settings'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_mail',
			__( 'From mail', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_from_mail_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Settings cf7_smtp from_mail */
		add_settings_field(
			'from_name',
			__( 'From name', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_from_name_callback' ),
			'smtp-settings',
			'smtp_advanced'
		);

		/* Section style */
		add_settings_section(
			'smtp_style',
			__( 'Style', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_section_style_subtitle' ),
			'smtp-style'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'custom_template',
			__( 'Custom Template', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_custom_template_callback' ),
			'smtp-style',
			'smtp_style'
		);

		/* Section cron */
		add_settings_section(
			'smtp_cron',
			__( 'Cron', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_section_cron_subtitle' ),
			'smtp-cron'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'report_every',
			__( 'Schedule report every', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_report_every_callback' ),
			'smtp-cron',
			'smtp_cron'
		);

		/* Settings cf7_smtp enabled */
		add_settings_field(
			'report_to',
			__( 'Email report to', CF7_SMTP_TEXTDOMAIN ),
			array( $this, 'cf7_smtp_print_report_to_callback' ),
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
			esc_html__( 'Welcome! Remember that you can activate and deactivate the smtp service simply by ticking the checkbox below', CF7_SMTP_TEXTDOMAIN )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_style_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Add an html template that wraps the email. (ps. You can enable a user-defined template, see the documentation for more information)', CF7_SMTP_TEXTDOMAIN )
		);
	}
	/**
	 * It prints The main setting text below the title
	 */
	public function cf7_smtp_print_section_cron_subtitle() {
		printf(
			'<p>%s</p>',
			esc_html__( 'Do you want to know if the mails are running smoothly? Let me occasionally e-mail a summary to verify the functionality.', CF7_SMTP_TEXTDOMAIN )
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
			esc_html__( 'Leave empty to NOT override the WordPress defaults (the one used can be different from the one you see below, if left blank the one set in Contact Form 7 will be used)', CF7_SMTP_TEXTDOMAIN )
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
	 *
	 * @return string a string of HTML.
	 */
	public function cf7_smtp_form_generate_radio( $selected = '' ) {
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
	 * If the CF7_SMTP_SETTINGS constant is defined and the key is set, return the value of the key. Otherwise, if the key is
	 * set in the options array, return the value of the key. Otherwise, return 'unset'
	 *
	 * @param string $key The key of the option you want to retrieve.
	 * @param bool   $return If true, the value of the option will be returned. If false, the option's status will be returned.
	 *
	 * @return array $option The value of the key in the CF7_SMTP_SETTINGS array, or the value of the key in the $this->options array, or
	 * 'unset' if the key is not found in either array.
	 */
	public function cf7_smtp_find_setting( string $key, bool $return = true ): array {
		$option = array(
			'value'   => '',
			'defined' => false,
		);
		if ( ! empty( CF7_SMTP_SETTINGS ) && ! empty( CF7_SMTP_SETTINGS[ $key ] ) ) {
			$option['value']   = $return ? CF7_SMTP_SETTINGS[ $key ] : 'defined';
			$option['defined'] = true;
		} elseif ( ! empty( $this->options[ $key ] ) ) {
			$option['value'] = $return ? $this->options[ $key ] : 'stored';
		}
		return $option;
	}

	/**
	 * It prints a checkbox with the id of `cf7_smtp_enabled` and the name of `cf7-smtp-options[enabled]` and if the `enabled`
	 * option is set, it will be checked
	 */
	public function cf7_smtp_print_enable_callback() {
		printf(
			'<input type="checkbox" id="cf7_smtp_enabled" name="cf7-smtp-options[enabled]" %s />',
			! empty( $this->options['enabled'] ) || ! empty( CF7_SMTP_SETTINGS ) ? 'checked="true"' : ''
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
				self::cf7_smtp_form_generate_radio( $auth['value'] ),
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
		$host = $this->cf7_smtp_find_setting( 'host' );
		printf(
			'<input type="text" id="cf7_smtp_host" name="cf7-smtp-options[host]" value="%s" placeholder="localhost" value="%s" />',
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
	 * It prints a text input field with the id of cf7_smtp_user_pass, the name of cf7-smtp-options[user_pass], and a class of
	 * either safe or unsafe
	 */
	public function cf7_smtp_print_user_pass_callback() {
		$user_pass     = $this->cf7_smtp_find_setting( 'user_pass', false );
		$user_pass_val = esc_html( $user_pass['value'] );
		printf(
			'<input type="text" id="cf7_smtp_user_pass" name="cf7-smtp-options[user_pass]" class="%s" autocomplete="off" %s %s />',
			empty( $user_pass['defined'] ) ? 'unsafe' : 'safe',
			esc_html( '' === $user_pass_val ? '' : 'placeholder="***"' ),
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
			'<input type="email" id="cf7_smtp_from_mail" name="cf7-smtp-options[from_mail]" value="%s" %s />',
			esc_attr( empty( $from_mail['value'] ) ? '' : esc_html( $from_mail['value'] ) ),
			esc_html( empty( $from_mail['defined'] ) ? '' : 'disabled' )
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
			'<input type="text" id="cf7_smtp_from_name" name="cf7-smtp-options[from_name]" value="%s" %s />',
			esc_attr( isset( $from_name['value'] ) ? esc_html( $from_name['value'] ) : '' ),
			esc_html( $from_name['defined'] ? 'disabled' : '' )
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
			} else {
				$new_input['preset'] = 'custom';
			}
		}

		/* SMTP preset */
		if ( isset( $input['auth'] ) ) {
			if ( in_array( $input['auth'], array( 'ssl', 'tls' ), true ) ) {
				$new_input['auth'] = sanitize_text_field( $input['auth'] );
			} else {
				$new_input['auth'] = '';
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
		}

		/* SMTP send report to */
		$new_input['report_to'] = empty( $input['report_to'] ) ? $new_input['report_to'] : sanitize_text_field( $input['report_to'] );

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
