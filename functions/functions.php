<?php
/**
 * CF7_SMTP common functions
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

/**
 * Get the settings of the plugin in a filterable way
 *
 * @since 0.0.1
 *
 * @return array
 */
function cf7_smtp_get_settings(): array {
	return apply_filters( 'cf7_smtp_get_settings', get_option( CF7_SMTP_TEXTDOMAIN . '-options', array() ) );
}

/**
 * It updates the plugin's settings
 *
 * @param array $options An array of options to update.
 *
 * @return bool success or not
 */
function cf7_smtp_update_settings( array $options ): bool {
	$new_options = array_merge( cf7_smtp_get_settings(), $options );
	return update_option( CF7_SMTP_TEXTDOMAIN . '-options', $new_options );
}

/**
 * It encrypts a string using the WordPress salt as the key
 *
 * @param string|int $value The value to encrypt.
 * @param string     $cipher The cipher method to use.
 *
 * @return string The encrypted value.
 */
function cf7_smtp_crypt( $value, string $cipher = 'aes-256-cbc' ) {
	if ( extension_loaded( 'openssl' ) ) {
		return openssl_encrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
	}
	return $value;
}

/**
 * It decrypts the data.
 *
 * @param string $value The value to be encrypted.
 * @param string $cipher The cipher method to use.
 *
 * @return string The decrypted value.
 */
function cf7_smtp_decrypt( string $value, string $cipher = 'aes-256-cbc' ): string {
	if ( extension_loaded( 'openssl' ) ) {
		return openssl_decrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
	}
	return $value;
}


/**
 * A function to log a string / array to the "wp-content/debug.log".
 *
 * @param string|array $log_data - The string/array to log.
 *
 * @return void
 */
function cf7_smtp_log( $log_data ) {
	if ( ! empty( $log_data && WP_DEBUG ) ) {
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			is_string( $log_data )
				? 'cf7_smtp: ' . $log_data
				// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
				: 'cf7_smtp: ' . print_r( $log_data, true )
		);
	}
}

/**
 * It prints the password placeholders, the number of * is equal to the length of the password
 *
 * @param string $pass The password to print.
 *
 * @return string The password with * placeholders.
 */
function cf7_smtp_print_pass_placeholders( string $pass ) {
	return '"' . str_repeat( '*', strlen( $pass ) ) . '"';
}

