<?php
/**
 * CF7_SMTP Stats
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Core;

use cf7_smtp\Engine\Base;
use cf7_smtp\Core\Mailer;

/**
 * Handles Statistics for this plugin
 */
class Stats extends Base {


	/**
	 * The report
	 *
	 * @var array
	 */
	private $report = null;

	public function __construct() {
		$this->report = get_option( 'cf7-smtp-report' );

		// Initialize with default structure if option doesn't exist or is invalid
		if ( ! is_array( $this->report ) ) {
			$this->report = array(
				'success' => 0,
				'failed'  => 0,
				'storage' => array(),
			);
			update_option( 'cf7-smtp-report', $this->report );
		}

		// Ensure all required keys exist
		if ( ! isset( $this->report['success'] ) ) {
			$this->report['success'] = 0;
		}
		if ( ! isset( $this->report['failed'] ) ) {
			$this->report['failed'] = 0;
		}
		if ( ! isset( $this->report['storage'] ) ) {
			$this->report['storage'] = array();
		}
	}

	public function has_report() {
		return ! empty( $this->report );
	}

	public function get_report() {
		return $this->report;
	}

	/**
	 * Reset the report
	 *
	 * @return void
	 */
	public function reset_report() {
		// Reset the report success and failed to 0
		$this->report = array_merge($this->report, array(
			'success' => 0,
			'failed' => 0
		));

		// Clean up the storage if needed
		$options     = cf7_smtp_get_settings();
		$retain_days = intval( $options['log_retain_days'] );
		if ( $retain_days ) {
			$this->cleanup_storage( time() - $retain_days * 24 * 60 * 60 );
		}

		// update the report
		update_option( 'cf7-smtp-report', $this->report );
	}

	public function cleanup_storage( $time ) {
		$this->report['storage'] = array_filter(
			$this->report['storage'],
			function ( $value ) use ( $time ) {
				return $value['time'] > $time;
			}
		);
	}

	public function store() {
		update_option( 'cf7-smtp-report', $this->report );
	}

	public function get_success() {
		return $this->report['success'];
	}

	public function get_failed() {
		return $this->report['failed'];
	}

	public function get_storage() {
		return $this->report['storage'];
	}

	public function add_field_to_storage( $time, $value ) {
		$this->report['storage'][ $time ] = $value;
	}

	public function add_failed() {
		$this->report['failed'] = ++$this->report['failed'];
	}

	public function add_success() {
		$this->report['success'] = ++$this->report['success'];
	}

	/**
	 * It takes the report data and formats it into a human-readable HTML string
	 *
	 * @param array $report The array of emails.
	 * @param bool  $last_report the time of last report (unix timestamp).
	 *
	 * @return string
	 */
	public function format_report( array $report, bool $last_report = false ) {

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
				esc_html__( 'Mail sent since last update', 'cf7-smtp' )
			);

			foreach ($report['storage'] as $date => $row) {
				if ($last_report > $date) {
					$mail_list['old']++;
					continue;
				} else {
					$mail_list['recent'][$row['mail_sent']]++;
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
				esc_html__( 'Email statistics', 'cf7-smtp' ),
				esc_html__( 'Sent with success', 'cf7-smtp' ),
				intval( $mail_list['recent']['success'] ),
				esc_html__( 'Failed', 'cf7-smtp' ),
				intval( $mail_list['recent']['failed'] )
			) . $html;
		} else {
			$html = sprintf(
				'<h3>%s</h3>',
				esc_html__( 'No recent e-mails to show!', 'cf7-smtp' )
			);
		}

		$html .= ! empty( $report['storage'] )
			? sprintf(
				/* translators: %1$s the section title - the inside %2$s (number) is the total count of emails sent and %3$s (number) is the number of mail since the last report */
				"\r\n<h3>%s: </h3><p>%s overall sent mails, %s since last report</p>",
				esc_html__( 'Email statistics', 'cf7-smtp' ),
				count( $report['storage'] ),
				$mail_list['count']
			)
			: esc_html__( 'No Mail in storage', 'cf7-smtp' );

		/* Add filter for 3rd party access, format your html as h3 or p tags */
		if ( has_filter( 'cf7_smtp_report_mailbody' ) ) {
			$html = apply_filters( 'cf7_smtp_report_mailbody', $html, $last_report );
		}

		return $html;
	}

	/**
	 * It sends a report of the number of successful and failed emails sent by Contact Form 7 to the email address specified
	 * in the plugin settings
	 *
	 * @param bool $force Whether to force the report to be sent.
	 *
	 * @return bool Whether the report was sent successfully.
	 */
	public function send_report( $force = false ) {
		// get the options
		$options = cf7_smtp_get_settings();

		/* if the report is not forced or is disabled, then return */
		if ( $force || empty( $options['report_every'] ) ) {
			return false;
		}

		// get the schedules
		$schedules = wp_get_schedules();

		/* the subject */
		$last_report = time() - intval( $schedules[ $options['report_every'] ]['interval'] );

		/* build the report */
		$report_formatted = $this->format_report( $this->get_report(), $last_report );

		/* init the mail */
		$smtp_mailer = new Mailer();
		$smtp_mailer->initialize();

		/* send the report */
		$response = $smtp_mailer->send_report( $report_formatted );

		/* if the report is sent, reset the report */
		if ( $response ) {
			$this->reset_report();
		}

		return $response;
	}
}
