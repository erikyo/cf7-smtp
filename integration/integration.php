<?php



include_once path_join(
	CF7_SMTP_PLUGIN_ROOT,
	'integration/service.php'
);

add_action( 'wpcf7_init', 'cf7_smtp_register_service', 1, 0 );

function cf7_smtp_register_service() {


	$integration = WPCF7_Integration::get_instance();

	
	$integration->add_service( 'cf7-smtp',
        WPCF7_SMTP::get_instance()
	);
	$integration->add_category('email_services', 'Email Services');
}
