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
	wp_nonce_field( 'cf7-smtp-setup' ); 
	?>

	<div class="cf7-smtp-options">
		<h3><?php esc_html__( 'Options', CF7_SMTP_TEXTDOMAIN ); ?></h3>
		<form method="post" action="options.php" id="cf7-smtp-settings" class="form-table">
			<?php
			/* This prints out all hidden setting fields */
			settings_fields( CF7_SMTP_TEXTDOMAIN . '-settings' );

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

			/* EDIT: Commented the undefined constant */
			/* This Prints the CRON job for mail reports */
			if ( /*!empty(CF7ANTISPAM_DEBUG) &&*/ wp_next_scheduled( 'cf7_smtp_report' ) ) {
				echo '<div class="tip schedule"><h1>⏰</h1>';
				printf(
					'<small class="monospace"><b>%s</b> %s <br/><b>%s</b> %s</small>',
					esc_html__( 'Next report:', CF7_SMTP_TEXTDOMAIN ),
					esc_html( wp_date( 'Y-m-d H:i:s', wp_next_scheduled( 'cf7_smtp_report' ) ) ),
					esc_html__( 'Server time:', CF7_SMTP_TEXTDOMAIN ),
					esc_html( wp_date( 'Y-m-d H:i:s', time() ) )
				);
				echo '</div>';
			}


			submit_button();
			echo '</div>';


			$cf7_smtp_report = get_option( 'cf7-smtp-report', false );

			echo '<div class="card smtp-style-chart">';
			echo '<h2>' . esc_html__( 'Stats', CF7_SMTP_TEXTDOMAIN ) . '</h2>';
			if ( ! empty( $cf7_smtp_report ) ) {
				echo '<h4>' . esc_html__( 'Mail vs Time', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
				echo '<canvas id="line-chart" width="480" height="250"></canvas>';
				echo '<hr>';
				echo '<h4>' . esc_html__( 'Mail sent vs Mail failed', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
				echo '<canvas id="pie-chart" width="200" height="250"></canvas>';

				echo '<script id="smtpReport">var smtpReportData =' . wp_json_encode( $cf7_smtp_report ) . '</script>';
			} else {
				echo '<span class="chart-icon">📊</span>';
				echo '<h4 class="no-chart-title">' . esc_html__( 'No email sent (yet)', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
			}
			echo '</div>';
			?>
		</form>
	</div>
</div>
