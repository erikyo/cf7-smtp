<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php
	do_action( 'cf7_smtp_dashboard' );
	?>

	<div class="card main-options">
		<h3><?php esc_html__( 'Options', C_TEXTDOMAIN ); ?></h3>
		<form method="post" action="options.php" id="cf7-smtp-settings" class="form-table">
			<?php
			/* This prints out all hidden setting fields */
			settings_fields( C_TEXTDOMAIN . '-settings' );
			do_settings_sections( 'smtp-settings' );
			submit_button();
			?>
		</form>
	</div>

</div>
