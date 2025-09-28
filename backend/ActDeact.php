<?php

/**
 * CF7_SMTP actiovation / deactivation class
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Backend;

use cf7_smtp\Engine\Base;
use WP_Site;

/**
 * Activate and deactive method of the plugin and relates.
 */
class ActDeact extends Base {


	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}

		/* Activate plugin when new blog is added */
		\add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param int $blog_id ID of the new blog.
	 * @since 0.0.1
	 * @return void
	 */
	public function activate_new_site( int $blog_id ) {
		if ( 1 !== \did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		\switch_to_blog( $blog_id );
		self::single_activate();
		\restore_current_blog();
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param bool|null $network_wide True if active in a multisite, false if classic site.
	 * @since 0.0.1
	 * @return void
	 */
	public static function activate( bool $network_wide ) {
		if ( \function_exists( 'is_multisite' ) && \is_multisite() ) {
			if ( $network_wide ) {
				/**
				 * Get all blog ids
				 *
				 * @var array<WP_Site> $blogs - the array of blog id
				 */
				$blogs = \get_sites();

				foreach ( $blogs as $blog ) {
					\switch_to_blog( (int) $blog->blog_id );
					self::single_activate();
					\restore_current_blog();
				}

				return;
			}
		}

		self::update_options();

		self::single_activate();
	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param bool $network_wide True if WPMU superadmin uses
	 * "Network Deactivate" action, false if
	 * WPMU is disabled or plugin is
	 * deactivated on an individual blog.
	 * @since 0.0.1
	 * @return void
	 */
	public static function deactivate( bool $network_wide ) {
		if ( \function_exists( 'is_multisite' ) && \is_multisite() ) {
			if ( $network_wide ) {
				/**
				 * Get all blog ids.
				 *
				 * @var array<WP_Site> $blogs - Blog ids.
				 */
				$blogs = \get_sites();

				foreach ( $blogs as $blog ) {
					\switch_to_blog( (int) $blog->blog_id );
					self::single_deactivate();
					\restore_current_blog();
				}

				return;
			}
		}

		self::single_deactivate();
	}


	/**
	 * It sets the default options for the plugin.
	 */
	public static function default_options(): array {
		$current_website = wp_parse_url( implode( '.', array_slice( explode( ',', get_bloginfo( 'url' ) ), -2, 2, true ) ), PHP_URL_HOST );

		return array(
			'version'         => 1,
			'enabled'         => true,
			'custom_template' => false,
			'report_every'    => false,
			'report_to'       => wp_get_current_user()->user_email ?? '',
			'preset'          => 'custom',
			'host'            => $current_website,
			'port'            => '25',
			'auth'            => '',
			'replyTo'         => false,
			'insecure'        => false,
			'user_name'       => '',
			'user_pass'       => '',
			'from_mail'       => '',
			'from_name'       => '',
		);
	}

	/**
	 *  Create or Update the CF7 Antispam options
	 *
	 * @param bool $reset_options - whatever to force the reset.
	 */
	public static function update_options( bool $reset_options = false ) {

		$default_cf7_smtp_options = self::default_options();

		$options = get_option( 'cf7-smtp' . '-options' );

		if ( empty( $options ) || $reset_options ) {
			/* if the plugin options are missing Init the plugin with the default option + the default settings */
			add_option( 'cf7-smtp' . '-options', $default_cf7_smtp_options );
		} else {
			/* update the plugin options but add the new options automatically */
			if ( isset( $options['cf7_smtp_version'] ) ) {
				unset( $options['cf7_smtp_version'] );
			}

			/* merge previous options with the updated copy keeping the already selected option as default */
			$new_options = array_merge( $default_cf7_smtp_options, $options );

			update_option( 'cf7-smtp' . '-options', $new_options );
		}
	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function single_activate() {
		/**
		 * Clear the permalinks
		 */
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		\flush_rewrite_rules();
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	private static function single_deactivate() {
		/**
		 * Clear the permalinks
		 */
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		\flush_rewrite_rules();
	}
}
