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

/**
 * Handles Statistics for this plugin.
 *
 * This class manages email sending statistics including success/failure counts,
 * storage of detailed email logs, and report generation for the CF7 SMTP plugin.
 * It provides functionality to track, store, and report on email sending activities.
 *
 * @since   1.0.0
 * @package cf7_smtp
 * @author  Erik Golinelli <erik@codekraft.it>
 */
class Stats extends Base {


	/**
	 * The report data structure containing email statistics and storage.
	 *
	 * @var array<string, mixed> Report data with keys: 'success', 'failed', 'storage', 'last_report_time'
	 */
	private $report = null;

	/**
	 * Initialize the Stats class.
	 *
	 * Loads the existing report from WordPress options and ensures the report
	 * structure contains all required keys with proper default values.
	 *
	 * @since 1.0.0
	 * @return void
	 */
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
		if ( ! isset( $this->report['last_report_time'] ) ) {
			$this->report['last_report_time'] = 0;
		}
	}

	/**
	 * Check if there is a report stored with email entries.
	 *
	 * Determines whether the current report contains any stored email data
	 * in the storage array.
	 *
	 * @since 1.0.0
	 * @return bool True if report exists and has storage entries, false otherwise.
	 */
	public function has_report(): bool {
		return isset( $this->report ) && ! empty( $this->report['storage'] );
	}

	/**
	 * Returns the complete stored report data.
	 *
	 * Retrieves the full report array including success counts, failure counts,
	 * storage data, and metadata.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed> The complete report data structure.
	 */
	public function get_report(): array {
		return $this->report;
	}

	/**
	 * Reset the report statistics and optionally clean up old storage entries.
	 *
	 * Resets success and failure counters to zero, updates the last report time,
	 * and optionally removes storage entries older than the specified retention period.
	 *
	 * @since 1.0.0
	 * @param int $retain_days Number of days to retain storage entries. Default 30.
	 * @return void
	 */
	public function reset_report( $retain_days = 30 ) {
		// Reset the report success and failed to 0
		$this->report = array_merge(
			$this->report,
			array(
				'success'          => 0,
				'failed'           => 0,
				'last_report_time' => time(),
			)
		);

		// Clean up the storage if needed
		self::cleanup_storage( $retain_days );

		// update the report
		self::store();
	}

	/**
	 * Remove storage entries older than the specified number of days.
	 *
	 * Cleans up the email storage array by removing entries that are older
	 * than the specified retention period to prevent database bloat.
	 *
	 * @since 1.0.0
	 * @param int $days_to_keep_logs Number of days to keep logs. Entries older than this will be removed.
	 * @return bool True if cleanup was successful and data was stored, false otherwise.
	 */
	public function cleanup_storage( int $days_to_keep_logs ): bool {
		// check if the time is a valid day timestamp
		if ( ! is_numeric( $days_to_keep_logs ) ) {
			return false;
		}

		// check if the timestamp is valid
		$timestamp = time() - ( $days_to_keep_logs * 24 * 60 * 60 );

		// remove all the entries from the report that are older than the specified time
		$this->report['storage'] = array_filter(
			$this->report['storage'],
			function ( $key ) use ( $timestamp ) {
				return $key > $timestamp;
			},
			ARRAY_FILTER_USE_KEY
		);

		return self::store();
	}

	/**
	 * Store the current report data in WordPress options.
	 *
	 * Persists the complete report structure to the WordPress database
	 * using the update_option function.
	 *
	 * @since 1.0.0
	 * @return bool True if the report was successfully stored, false otherwise.
	 */
	public function store(): bool {
		return update_option( 'cf7-smtp-report', $this->report );
	}

	/**
	 * Get the total number of successful email sends.
	 *
	 * Retrieves the success counter from the current report data.
	 *
	 * @since 1.0.0
	 * @return int The number of successful email sends.
	 */
	public function get_success() {
		return $this->report['success'];
	}

	/**
	 * Get the total number of failed email sends.
	 *
	 * Retrieves the failure counter from the current report data.
	 *
	 * @since 1.0.0
	 * @return int The number of failed email sends.
	 */
	public function get_failed(): int {
		return $this->report['failed'];
	}

	/**
	 * Get the detailed email storage data.
	 *
	 * Retrieves the storage array which contains detailed information
	 * about each email sent including timestamps, status, and metadata.
	 *
	 * @since 1.0.0
	 * @return array<string, mixed> The storage array containing detailed email logs.
	 */
	public function get_storage(): array {
		return $this->report['storage'];
	}

	/**
	 * Add an entry to the email storage with detailed information.
	 *
	 * Stores detailed information about a specific email send operation
	 * in the storage array using the timestamp as the key.
	 *
	 * @since 1.0.0
	 * @param int   $time  Unix timestamp when the email was sent.
	 * @param array $value Array containing email details including status, form ID, title, etc.
	 * @return void
	 */
	public function add_field_to_storage( $time, $value ) {
		$this->report['storage'][ $time ] = $value;
	}

	/**
	 * Increment the failed email counter.
	 *
	 * Increases the failed counter by one to track unsuccessful email sends.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_failed() {
		$this->report['failed'] = ++$this->report['failed'];
	}

	/**
	 * Increment the successful email counter.
	 *
	 * Increases the success counter by one to track successful email sends.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_success() {
		$this->report['success'] = ++$this->report['success'];
	}

	/**
	 * Format report data into a human-readable HTML email.
	 *
	 * Takes the raw report data and formats it into a professional HTML email template with
	 * email statistics, individual email entries with timestamps and status,
	 * and summary information. The email uses a card-based design with a light
	 * gray background for better readability and email client compatibility.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $report      The report data array containing storage and statistics.
	 * @param bool                 $last_report Optional. Unix timestamp of the last report. Default current time.
	 * @return string Formatted HTML string containing the styled email report.
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

			foreach ( $report['storage'] as $date => $row ) {
				if ( $last_report > $date ) {
					++$mail_list['old'];
					continue;
				} else {
					++$mail_list['recent'][ $row['mail_sent'] ];
					++$mail_list['count'];
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
	 * Send an email report of statistics to the configured recipient.
	 *
	 * Generates and sends a comprehensive email report containing success/failure
	 * statistics and recent email activity. The report is sent based on the
	 * configured schedule settings.
	 *
	 * @param bool $force Optional. Whether to force sending the report regardless of schedule. Default false.
	 *
	 * @return bool True if the report was sent successfully, false otherwise.
	 * @since 1.0.0
	 */
	public function send_report( bool $force = false ): bool {
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
		$retain_days = intval( $options['log_retain_days'] );
		if ( $response ) {
			$this->reset_report( $retain_days );
		}

		return $response;
	}
}
