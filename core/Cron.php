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
		add_action( 'cf7_smtp_report', array( $this, 'cf7_smtp_report' ) );

		add_filter( 'cron_schedules', array( $this, 'cf7_smtp_add_cron_steps' ) );
	}

	/**
	 * It adds two new cron schedules to WordPress
	 *
	 * @param array $schedules This is the name of the hook that we're adding a schedule to.
	 */
	function cf7_smtp_add_cron_steps( $schedules ) {
		if ( ! empty( $schedules ) ) {
			return array_merge(
				$schedules,
				array(
					'2weeks' => array(
						'interval' => WEEK_IN_SECONDS * 2,
						'display'  => __( 'Every 2 weeks', CF7_SMTP_TEXTDOMAIN ),
					),
					'month'  => array(
						'interval' => MONTH_IN_SECONDS,
						'display'  => __( 'Every month', CF7_SMTP_TEXTDOMAIN ),
					),
				)
			);
		}
	}

	/**
	 * It takes the report data and formats it into a human-readable HTML string
	 *
	 * @param array $report the array of emails
	 * @param bool  $last_report
	 *
	 * @return string
	 */
	public function cf7_smtp_format_report( $report, $last_report = false ) {
		$html       = '';
		$mail_count = 0;

		if ( ! $last_report ) {
			$last_report = time();
		}

		cf7_smtp_log( $report['storage'] );

		foreach ( $report['storage'] as $date => $row ) {

			if ( $last_report > $date ) {
				cf7_smtp_log( "skip $date" );
				continue;
			}

			$mail_count++;

			$html .= sprintf(
				'<p>%s - %s</p><br/>',
				gmdate( 'r', $date ),
				! empty( $row['mail_sent'] ) ? '✅' : '⛔'
			);
		}

		if ( ! empty( $report['valid'] ) || ! empty( $report['failed'] ) ) {
			$html .= sprintf(
				"\r\n<p><b>%s</b>%s - <b>%s</b> %s</p>",
				esc_html__( 'Sent with success', CF7_SMTP_TEXTDOMAIN ),
				intval( $report['success'] ),
				esc_html__( 'Failed', CF7_SMTP_TEXTDOMAIN ),
				intval( $report['failed'] )
			);
		}

		$html .= ! empty( $report['storage'] )
			? sprintf(
				'<br/><h4>%s</h4><p>%s</p>',
				esc_html(
					sprintf(
						__( 'Email statistics', CF7_SMTP_TEXTDOMAIN ),
						/* translators: %1$s (number) is the total count of emails sent and %2$s (number) is the number of mail since the last report */
						__( '%1$s overall sent mails, %2$s since last report', CF7_SMTP_TEXTDOMAIN ),
						count( $report['storage'] ),
						$mail_count
					)
				)
			)
			: esc_html__( 'No Mail in storage', CF7_SMTP_TEXTDOMAIN );

		return $html;
	}



	/**
	 * It sends a report of the number of successful and failed emails sent by Contact Form 7 to the email address specified
	 * in the plugin settings
	 */
	public function cf7_smtp_report() {

		/* get the stored report */
		$options = cf7_smtp_get_settings();
		$report  = get_option( 'cf7_smtp_report' );

		/* init the mail */
		$smtp_mailer = new Mailer();
		$mail        = array();

		/* the subject */
		$schedules   = wp_get_schedules();
		$last_report = time() - intval( $schedules[ $options['report_every'] ]['interval'] );

		/* build the report */
		$report_formatted = $this->cf7_smtp_format_report( $report, 0654 );
		cf7_smtp_log( $report_formatted );

		/* translators: %s scheduled time of recurrence (e.g. monthly report, weekly report, daily report) or "website" (e.g. website mail report) in case it fail to get the recurrence */
		$mail['subject'] = esc_html__( sprintf( '%s Mail report', esc_html( $schedules[ $options['report_every'] ]['display'] ) ), CF7_SMTP_TEXTDOMAIN );

		/* the mail message */
		$mail['body'] = $smtp_mailer->cf7_smtp_form_template(
			array(
				'body'    => $report_formatted,
				'title'   => get_bloginfo( 'name' ),
				'subject' => $mail['subject'],
			),
			file_get_contents( CF7_SMTP_PLUGIN_ROOT . 'templates/report.html' )
		);

		/* mail headers (if available) */
		$headers = '';
		if ( ! empty( $options['from_mail'] ) ) {
			$headers = sprintf( "From: %s <%s>\r\n", $options['from_name'], $options['from_mail'] );
		}

		/* A try catch block. */
		try {
			if ( wp_mail(
				$options['report_to'],
				$mail['subject'],
				$mail['body'],
				$headers
			) ) {
				$report['failed']  = 0;
				$report['success'] = 0;
				update_option( 'cf7_smtp_report', $report );
			}
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			cf7_smtp_log( 'Something went wrong while sending the report! 😓' );
			cf7_smtp_log( $e );
		}

	}

}
