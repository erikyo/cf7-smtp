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


namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;

/**
 *
 */
class Widget extends Base {

	/**
	 * Initialize the class.
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}
		\add_action( 'wp_dashboard_setup', array( $this, 'cf7_smtp_dashboard_widget' ) );
	}


	/**
	 * The function adds a dashboard widget for displaying statistics related to CF7 SMTP.
	 */
	public function cf7_smtp_dashboard_widget() {
		\wp_add_dashboard_widget( 'dashboard_widget', __( 'Stats for CF7 SMTP', 'cf7-smtp' ), array( $this, 'cf7_smtp_display' ) );
	}

	/**
	 * The function `cf7_smtp_display` displays a chart showing the number of emails sent and failed over
	 * time, using data stored in the `cf7-smtp-report` option.
	 */
	public function cf7_smtp_display() {
		$cf7_smtp_report = get_option( 'cf7-smtp-report', false );

		echo '<div class="smtp-style-chart">';
		if ( ! empty( $cf7_smtp_report ) ) {
			echo '<h4>' . esc_html__( 'Mail vs Time', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
			echo '<canvas id="smtp-line-chart"></canvas>';
			echo '<hr>';
			echo '<h4>' . esc_html__( 'Mail sent vs Mail failed', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
			echo '<div id="smtp-pie-container">';
			echo '<canvas id="smtp-pie-chart"></canvas>';
			echo '</div>';
			echo '<script id="smtpReport">var smtpReportData =' . wp_json_encode( $cf7_smtp_report ) . '</script>';
		} else {
			echo '<span class="chart-icon">📊</span>';
			echo '<h4 class="no-chart-title">' . esc_html__( 'No email sent (yet)', CF7_SMTP_TEXTDOMAIN ) . '</h4>';
		}
		echo '</div>';
	}
}
