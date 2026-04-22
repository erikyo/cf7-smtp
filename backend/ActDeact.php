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
			'version'                          => defined( 'CF7_SMTP_VERSION' ) ? CF7_SMTP_VERSION : '1.0.0',
			'enabled'                          => true,
			'custom_template'                  => false,
			'report_every'                     => false,
			'report_to'                        => wp_get_current_user()->user_email ?? '',
			'preset'                           => 'custom',
			'host'                             => $current_website,
			'port'                             => '25',
			'auth'                             => '',
			'replyTo'                          => false,
			'insecure'                         => false,
			'user_name'                        => '',
			'user_pass'                        => '',
			'from_mail'                        => '',
			'from_name'                        => '',
			'log_retain_days'                  => 30,
			// OAuth2 settings.
			'auth_type'                        => 'basic',
			'auth_method'                      => 'wp',
			// basic or oauth2.
							'oauth2_provider'  => '',
			// gmail, office365.
							'oauth2_client_id' => '',
			'oauth2_client_secret'             => '',
			'oauth2_access_token'              => '',
			'oauth2_refresh_token'             => '',
			'oauth2_expires'                   => '',
			'oauth2_user_email'                => '',
			'oauth2_connected_at'              => '',
		);
	}

	/**
	 *  Create or Update the CF7 Antispam options
	 *
	 * @param bool $reset_options - whatever to force the reset.
	 */
	public static function update_options( bool $reset_options = false ) {

		$default_cf7_smtp_options = self::default_options();

		$options = get_option( 'cf7-smtp-options' );

		if ( empty( $options ) || $reset_options ) {
			/* if the plugin options are missing Init the plugin with the default option + the default settings */
			add_option( 'cf7-smtp-options', $default_cf7_smtp_options );
		} else {
			/* update the plugin options but add the new options automatically */
			if ( isset( $options['cf7_smtp_version'] ) ) {
				unset( $options['cf7_smtp_version'] );
			}

			/* merge previous options with the updated copy keeping the already selected option as default */
			$new_options = array_merge( $default_cf7_smtp_options, $options );

			/**
			 * Legacy v1.0.0 users:
			 * v1.0.0 did not have 'auth_method'. If it's missing, it means the user
			 * was previously using the standard SMTP functionality. We force
			 * 'smtp' as the method to maintain their existing configuration,
			 * instead of letting it fall back to the new 'wp' (WordPress default).
			 */
			if ( ! isset( $options['auth_method'] ) || ( isset( $options['enabled'] ) && true === $options['enabled'] ) ) {
				$new_options['auth_method'] = 'smtp';
			}

			/* Always stamp the current plugin version so maybe_upgrade() knows the migration is done */
			$new_options['version'] = defined( 'CF7_SMTP_VERSION' ) ? CF7_SMTP_VERSION : $default_cf7_smtp_options['version'];

			update_option( 'cf7-smtp-options', $new_options );
		}//end if
	}

	/**
	 * Runs after every page load (hooked to plugins_loaded) and migrates stored
	 * options to the current schema whenever the plugin has been updated without
	 * a manual deactivate / activate cycle (e.g. auto-updates via WP dashboard).
	 *
	 * The version stored in the option row is compared against CF7_SMTP_VERSION;
	 * if they differ, update_options() merges the current defaults into the stored
	 * options and stamps the new version so the migration only runs once.
	 *
	 * @since 1.1.0
	 * @return void
	 */
	public static function maybe_upgrade() {
		$options         = get_option( 'cf7-smtp-options', array() );
		$stored_version  = $options['version'] ?? 0;
		$current_version = defined( 'CF7_SMTP_VERSION' ) ? CF7_SMTP_VERSION : '0';

		if ( version_compare( (string) $stored_version, $current_version, '<' ) ) {
			self::update_options();
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
