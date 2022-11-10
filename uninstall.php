<?php

/**
 * CF7_SMTP Uninstall
 *
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once site wide.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Loop for uninstall
 *
 * @return void
 */
function cf7_smtp_uninstall_multisite() {
	if ( is_multisite() ) {
		/** @var array<\WP_Site> $blogs */
		$blogs = get_sites();

		if ( ! empty( $blogs ) ) {
			foreach ( $blogs as $blog ) {
				switch_to_blog( (int) $blog->blog_id );
				cf7_smtp_uninstall();
				restore_current_blog();
			}

			return;
		}
	}

	cf7_smtp_uninstall();
}

/**
 * What happens on uninstall?
 * https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/
 *
 * @global WP_Roles $wp_roles
 * @return void
 */
function cf7_smtp_uninstall() { // phpcs:ignore

	\delete_option( CF7_SMTP_TEXTDOMAIN . '-options' );

	// for site options in Multisite
	\delete_site_option( CF7_SMTP_TEXTDOMAIN . '-options' );
}

cf7_smtp_uninstall_multisite();
