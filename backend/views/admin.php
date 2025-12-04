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

use cf7_smtp\Core\Stats;


?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<?php
	do_action( 'cf7_smtp_dashboard' );
	wp_nonce_field( 'cf7-smtp-setup' );
	?>

	<div class="cf7-smtp-options">
		<h3><?php esc_html_e( 'Options', 'cf7-smtp' ); ?></h3>
		<form method="post" action="options.php" id="cf7-smtp-settings" class="form-table">
			<?php

			/* This prints out all hidden setting fields */
			settings_fields( 'cf7-smtp-settings' );

			/* This prints out the smtp settings */
			echo '<div class="card smtp-settings-options">';
			do_settings_sections( 'smtp-settings' );
			submit_button();
			echo '</div>';

			/* This prints the style options (template) */
			echo '<div class="card smtp-style-options">';
			do_settings_sections( 'smtp-style' );
			submit_button();
			echo '</div>';

			/* This prints the cron options (mail report) */
			echo '<div class="card main-options">';
			do_settings_sections( 'smtp-cron' );


			if ( wp_next_scheduled( 'cf7_smtp_report' ) ) {
				echo '<div class="tip schedule"><h1>⏰</h1>';
				printf(
					'<small class="monospace"><b>%s</b> %s <br/><b>%s</b> %s</small>',
					esc_html__( 'Next report:', 'cf7-smtp' ),
					esc_html( wp_date( 'Y-m-d H:i:s', wp_next_scheduled( 'cf7_smtp_report' ) ) ),
					esc_html__( 'Server time:', 'cf7-smtp' ),
					esc_html( wp_date( 'Y-m-d H:i:s', time() ) )
				);
				echo '</div>';
			}

			submit_button();
			echo '</div>';

			/* This prints the stats */
			echo '<div class="card smtp-style-chart">';
			echo '<h2>' . esc_html__( 'Stats', 'cf7-smtp' ) . '</h2>';
			$widget = new \cf7_smtp\Backend\Widget();
			$widget->display_charts();
			echo '</div>';
			?>
		</form>
	</div>
</div>
