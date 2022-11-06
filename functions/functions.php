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

/**
 * Get the settings of the plugin in a filterable way
 *
 * @since 0.0.1
 * @return array
 */
function c_get_settings() {
	return apply_filters( 'c_get_settings', get_option( C_TEXTDOMAIN . '-options' ) );
}

/**
 * It encrypts a string using the WordPress salt as the key
 *
 * @param string|int $value The value to encrypt.
 * @param string     $cipher The cipher method to use.
 *
 * @return string The encrypted value.
 */
function cf7_smtp_crypt( $value, $cipher = 'aes-256-cbc' ) {
	if ( ! extension_loaded( 'openssl' ) ) {
		return $value;
	}
	return openssl_encrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
}

/**
 * It decrypts the data.
 *
 * @param string $value The value to be encrypted.
 * @param string $cipher The cipher method to use.
 *
 * @return string The decrypted value.
 */
function cf7_smtp_decrypt( $value, $cipher = 'aes-256-cbc' ) {
	if ( ! extension_loaded( 'openssl' ) ) {
		return $value;
	}
	return openssl_decrypt( $value, $cipher, wp_salt( 'nonce' ), $options = 0, substr( wp_salt( 'nonce' ), 0, 16 ) );
}




