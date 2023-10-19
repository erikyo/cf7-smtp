<?php

/**
 * CF7_SMTP Cron
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

use cf7_smtp\Engine\Base;

/**
 * The various Cron of this plugin
 */
class Cron extends Base {


	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		add_action( 'cf7_smtp_report', array( $this, 'cf7_smtp_send_report' ) );

		add_filter( 'cron_schedules', array( $this, 'cf7_smtp_add_cron_steps' ) );
	}

	/**
	 * It adds two new cron schedules to WordPress
	 *
	 * @param array $schedules This is the name of the hook that we're adding a schedule to.
	 */
	public function cf7_smtp_add_cron_steps( $schedules ): array {
		if ( ! isset( $schedules['2weeks'] ) ) {
			$schedules['2weeks'] = array(
				'interval' => WEEK_IN_SECONDS * 2,
				'display'  => __( 'Every 2 weeks', CF7_SMTP_TEXTDOMAIN ),
			);
		}
		if ( ! isset( $schedules['month'] ) ) {
			$schedules['month'] = array(
				'interval' => MONTH_IN_SECONDS,
				'display'  => __( 'Every month', CF7_SMTP_TEXTDOMAIN ),
			);
		}
		return $schedules;
	}

	/**
	 * It takes the report data and formats it into a human-readable HTML string
	 *
	 * @param array $report The array of emails.
	 * @param bool  $last_report the time of last report (unix timestamp).
	 *
	 * @return string
	 */
	public function cf7_smtp_format_report( $report, $last_report = false ) {

		if ( ! $last_report ) {
			$last_report = time();
		}

		$mail_list = array(
			'result' => array(
				'success' => 0,
				'failed'  => 0,
			),
			'old'    => 0,
			'count'  => 0,
		);

		$html = '';

		if ( ! empty( $report['storage'] ) ) {

			$html .= sprintf(
				'<h3>%s</h3>',
				esc_html__( 'Mail sent since last update', CF7_SMTP_TEXTDOMAIN )
			);

			foreach ( $report['storage'] as $date => $row ) {
				if ( $last_report > $date ) {
					$mail_list['old']++;
					continue;
				} else {
					$mail_list['recent'][ $row['mail_sent'] ]++;
					$mail_list['count']++;
				}

				$html .= sprintf(
					'<p>%s - %s %s (id: %s)</p>',
					wp_date( 'r', $date ),
					empty( $row['mail_sent'] ) ? '⛔' : '✅',
					empty( $row['title'] ) ? '' : intval( $row['title'] ),
					empty( $row['form_id'] ) ? '' : intval( $row['form_id'] )
				);
			}
		}

		/* Checking if the report has valid or failed emails. Note: in order to move the report after the list of mail the previous html will be concatenated at the end of this string */
		if ( ! empty( $report['valid'] ) || ! empty( $report['failed'] ) ) {
			$html = sprintf(
				'<h3>%s</h3><p><b>%s</b>%s - <b>%s</b> %s</p>',
				esc_html__( 'Email statistics', CF7_SMTP_TEXTDOMAIN ),
				esc_html__( 'Sent with success', CF7_SMTP_TEXTDOMAIN ),
				intval( $mail_list['recent']['success'] ),
				esc_html__( 'Failed', CF7_SMTP_TEXTDOMAIN ),
				intval( $mail_list['recent']['failed'] )
			) . $html;
		} else {
			$html = sprintf(
				'<h3>%s</h3>',
				esc_html__( 'No recent e-mails to show!', CF7_SMTP_TEXTDOMAIN )
			);
		}

		$html .= ! empty( $report['storage'] )
			? sprintf(
				/* translators: %1$s the section title - the inside %2$s (number) is the total count of emails sent and %3$s (number) is the number of mail since the last report */
				"\r\n<h3>%s: </h3><p>%s overall sent mails, %s since last report</p>",
				esc_html__( 'Email statistics', CF7_SMTP_TEXTDOMAIN ),
				count( $report['storage'] ),
				$mail_list['count']
			)
			: esc_html__( 'No Mail in storage', CF7_SMTP_TEXTDOMAIN );

		/* Add filter for 3rd party access, format your html as h3 or p tags */	
		if( has_filter( 'cf7_smtp_report_mailbody' ) ){
			$html = apply_filters( 'cf7_smtp_report_mailbody', $html, $last_report );
		}

		return $html;
	}



	/**
	 * It sends a report of the number of successful and failed emails sent by Contact Form 7 to the email address specified
	 * in the plugin settings
	 */
	public function cf7_smtp_send_report() {
		/* if report is disabled then return */
		if ( ! get_option( 'cf7-smtp-report', false ) ) {
			return;
		}

		$options = cf7_smtp_get_settings();

		/* init the mail */
		$smtp_mailer = new Mailer();
		$mail        = array();

		/* the subject */
		$schedules   = wp_get_schedules();
		$last_report = time() - intval( $schedules[ $options['report_every'] ]['interval'] );

		/* build the report */
		$report_formatted = $this->cf7_smtp_format_report( get_option( 'cf7-smtp-report' ), $last_report );

		$mail['subject'] = esc_html(
			sprintf(
				/* translators: %s scheduled time of recurrence (e.g. monthly report, weekly report, daily report) or "website" (e.g. website mail report) in case it fail to get the recurrence */
				__(
					'%s Mail report',
					CF7_SMTP_TEXTDOMAIN
				),
				$schedules[ $options['report_every'] ]['display']
			)
		);

		/* the mail message */
		$mail['body'] = $smtp_mailer->cf7_smtp_form_template(
			array(
				'body'    => $report_formatted,
				'title'   => get_bloginfo( 'name' ),
				'subject' => $mail['subject'],
			),
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			file_get_contents( CF7_SMTP_PLUGIN_ROOT . 'templates/report.html' )
		);

		/* mail headers (if available) */
		$headers = '';
		if ( ! empty( $options['from_mail'] ) ) {
			$headers = sprintf( "Content-Type: text/html; charset=utf-8\r\nFrom: %s <%s>\r\n", $options['from_name'], $options['from_mail'] );
		}

		try {
			// phpcs:disable WordPressVIPMinimum.Functions.RestrictedFunctions.wp_mail_wp_mail
			if ( wp_mail(
				$options['report_to'],
				$mail['subject'],
				$mail['body'],
				$headers
			) ) {
				$report['failed']  = 0;
				$report['success'] = 0;
				update_option( 'cf7-smtp-report', $report );
			}
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			cf7_smtp_log( 'Something went wrong while sending the report! 😓' );
			cf7_smtp_log( $e );
		}
	}
}
