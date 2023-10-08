<?php

/**
 * CF7_SMTP context class.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

require_once path_join(
	CF7_SMTP_PLUGIN_ROOT,
	'integration/service.php'
);

/**
 * call the integration action to mount our plugin as a component
 * into the intefration page
 */

add_action( 'wpcf7_init', 'cf7_smtp_register_service', 1, 0 );

function cf7_smtp_register_service() {
	$integration = WPCF7_Integration::get_instance();
	$integration->add_service(
		'cf7-smtp',
		WPCF7_SMTP::get_instance()
	);
	$integration->add_category( 'email_services', 'Email Services' );
}
