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
		$this->report['success']          = 0;
		$this->report['failed']           = 0;
		$this->report['last_report_time'] = time();

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
	public function format_report( array $report, int $last_report = 0 ): string {

		if ( ! $last_report ) {
			$last_report = time();
		}

		$mail_list = array(
			'recent' => array(
				'success' => 0,
				'failed'  => 0,
			),
			'old'    => 0,
			'count'  => 0,
		);

		$content_body = '';

		// Build the mail list section
		if ( ! empty( $report['storage'] ) ) {

			$content_body .= sprintf(
				'<h2 style="color: #333; font-size: 18px; font-weight: 600; margin: 0 0 16px 0; padding: 0;">%s</h2>',
				esc_html__( 'Mail sent since last update', 'cf7-smtp' )
				sprintf(
					/* translators: %s: date */
					esc_html__( 'Mail sent since %s', 'cf7-smtp' ),
					wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_report )
				)
			);

			$mail_items = '';
			foreach ( $report['storage'] as $date => $row ) {
				if ( $last_report > $date ) {
					++$mail_list['old'];
					continue;
				} else {
					if ( ! empty( $row['mail_sent'] ) ) {
						++$mail_list['recent']['success'];
					} else {
						++$mail_list['recent']['failed'];
					}
					++$mail_list['count'];
				}

				$status_icon  = empty( $row['mail_sent'] ) ? '⛔' : '✅';
				$status_color = empty( $row['mail_sent'] ) ? '#dc3545' : '#28a745';

				$mail_items .= sprintf(
					'<div style="padding: 10px; margin-bottom: 8px; background: #f8f9fa; border-left: 3px solid %s; border-radius: 4px;">
						<span style="color: #666; font-size: 13px;">%s</span>
						<span style="font-size: 16px; margin: 0 8px;">%s</span>
						<span style="color: #333; font-weight: 500;">%s</span>
						<span style="color: #999; font-size: 13px;">(ID: %s)</span>
					</div>',
					$status_color,
					wp_date( 'M j, Y - H:i', $date ),
					$status_icon,
					empty( $row['title'] ) ? esc_html__( 'No title', 'cf7-smtp' ) : esc_html( $row['title'] ),
					empty( $row['form_id'] ) ? 'N/A' : intval( $row['form_id'] )
				);
			}//end foreach

			$content_body .= $mail_items;
		}//end if

		// Build the statistics section
		$statistics_html = '';
		if ( ! empty( $mail_list['recent']['success'] ) || ! empty( $mail_list['recent']['failed'] ) ) {
			$statistics_html = sprintf(
				'<div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 24px;">
					<h2 style="color: #333; font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">%s</h2>
					<div style="display: inline-block; margin-right: 24px;">
						<span style="font-size: 24px; color: #28a745; font-weight: 700;">%d</span>
						<span style="color: #666; font-size: 14px; margin-left: 8px;">%s</span>
					</div>
					<div style="display: inline-block;">
						<span style="font-size: 24px; color: #dc3545; font-weight: 700;">%d</span>
						<span style="color: #666; font-size: 14px; margin-left: 8px;">%s</span>
					</div>
				</div>',
				esc_html__( 'Email Statistics', 'cf7-smtp' ),
				intval( $mail_list['recent']['success'] ),
				esc_html__( 'Sent Successfully', 'cf7-smtp' ),
				intval( $mail_list['recent']['failed'] ),
				esc_html__( 'Failed', 'cf7-smtp' )
			);
		} else {
			$statistics_html = sprintf(
				'<div style="background: #fff3cd; padding: 20px; border-radius: 6px; margin-bottom: 24px; border-left: 4px solid #ffc107;">
					<h2 style="color: #856404; font-size: 18px; font-weight: 600; margin: 0;">%s</h2>
				</div>',
				esc_html__( 'No recent e-mails to show!', 'cf7-smtp' )
			);
		}//end if

		// Build the overall statistics
		$overall_stats = '';
		if ( ! empty( $report['storage'] ) ) {
			$overall_stats = sprintf(
				'<div style="margin-top: 24px; padding-top: 20px; border-top: 2px solid #e9ecef;">
					<h2 style="color: #333; font-size: 18px; font-weight: 600; margin: 0 0 12px 0;">%s</h2>
					<p style="color: #666; font-size: 14px; margin: 0; line-height: 1.6;">
						<strong style="color: #333;">%d</strong> %s<br>
						<strong style="color: #333;">%d</strong> %s
					</p>
				</div>',
				esc_html__( 'Overall Statistics', 'cf7-smtp' ),
				count( $report['storage'] ),
				esc_html__( 'total emails sent', 'cf7-smtp' ),
				$mail_list['count'],
				esc_html__( 'emails since last report', 'cf7-smtp' )
			);
		} else {
			$overall_stats = sprintf(
				'<div style="margin-top: 24px; padding: 16px; background: #e9ecef; border-radius: 6px;">
					<p style="color: #666; font-size: 14px; margin: 0;">%s</p>
				</div>',
				esc_html__( 'No Mail in storage', 'cf7-smtp' )
			);
		}//end if

		// Combine all content
		$main_content = $statistics_html . $content_body . $overall_stats;

		// Allow 3rd party plugins to add content via filter
		// Only basic HTML tags are allowed: h2, h3, p, div, span, strong, b, em, i, br
		if ( has_filter( 'cf7_smtp_report_mailbody' ) ) {
			$filtered_content = apply_filters( 'cf7_smtp_report_mailbody', '', $last_report );

			// Sanitize the filtered content to allow only safe HTML tags
			$allowed_tags = array(
				'h2'     => array( 'style' => array() ),
				'h3'     => array( 'style' => array() ),
				'p'      => array( 'style' => array() ),
				'div'    => array( 'style' => array() ),
				'span'   => array( 'style' => array() ),
				'strong' => array(),
				'b'      => array(),
				'em'     => array(),
				'i'      => array(),
				'br'     => array(),
			);

			$filtered_content = wp_kses( $filtered_content, $allowed_tags );

			if ( ! empty( $filtered_content ) ) {
				$main_content .= sprintf(
					'<div style="margin-top: 24px; padding-top: 20px; border-top: 2px solid #e9ecef;">%s</div>',
					$filtered_content
				);
			}
		}//end if

		// Build the complete HTML email template
		$html = sprintf(
			'<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>%s</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f5f5f5; line-height: 1.6;">
	<table width="100%%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f5f5f5; padding: 40px 20px;">
		<tr>
			<td align="center">
				<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width: 600px; width: 100%%; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
					<tr>
						<td style="padding: 40px 40px 32px 40px; border-bottom: 3px solid #007bff;">
							<h1 style="margin: 0; padding: 0; color: #333; font-size: 24px; font-weight: 700;">%s</h1>
							<p style="margin: 8px 0 0 0; color: #666; font-size: 14px;">%s</p>
						</td>
					</tr>
					<tr>
						<td style="padding: 32px 40px;">
							%s
						</td>
					</tr>
					<tr>
						<td style="padding: 24px 40px; background-color: #f8f9fa; border-top: 1px solid #e9ecef; border-radius: 0 0 8px 8px;">
							<p style="margin: 0; color: #999; font-size: 12px; text-align: center;">
								%s
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>',
			esc_html__( 'CF7 SMTP Email Report', 'cf7-smtp' ),
			esc_html__( 'CF7 SMTP Email Report', 'cf7-smtp' ),
			esc_html( wp_date( 'F j, Y', time() ) ),
			$main_content,
			sprintf(
			/* translators: %s: plugin name */
				esc_html__( 'This report was generated by %s', 'cf7-smtp' ),
				'CF7 SMTP'
			)
		);

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
		if ( ! $force && empty( $options['report_every'] ) ) {
			return false;
		}

		// get the schedules
		$schedules = wp_get_schedules();

		/* the subject */
		if ( ! empty( $this->report['last_report_time'] ) ) {
			$last_report = $this->report['last_report_time'];
		} else {
			$interval    = ! empty( $options['report_every'] ) && isset( $schedules[ $options['report_every'] ] ) ? intval( $schedules[ $options['report_every'] ]['interval'] ) : \WEEK_IN_SECONDS;
			$last_report = time() - $interval;
		}

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
