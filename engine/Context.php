<?php

/**
 * CF7_SMTP context class.
 *
 * @package   cf7_smtp
 * @author    Erik Golinelli <erik@codekraft.it>
 * @copyright 2022 Erik
 * @license   GPL 2.0+
 * @link      https://modul-r.codekraft.it/
 */

namespace cf7_smtp\Engine;

use Inpsyde\WpContext;
use WP_User;

/**
 * CF7-SMTP Context Methods.
 */
class Context {


	/**
	 * WpContext Class
	 *
	 * @var object
	 */
	public $context = null;

	/**
	 * What type of request is this?
	 *
	 * @since 0.0.1
	 * @param  string $type admin, ajax, cron, cli, amp or frontend.
	 * @return bool
	 * @SuppressWarnings("StaticAccess")
	 */
	public function request( string $type ): bool {
		$this->context = WpContext::determine();

		switch ( $type ) {
			case 'backend':
				return $this->context->isBackoffice();

			case 'ajax':
				return $this->context->isAjax();

			case 'installing_wp':
				return $this->context->isInstalling();

			case 'rest':
				return $this->context->isRest();

			case 'cron':
				return $this->context->isCron();

			case 'frontend':
				return $this->context->isFrontoffice();

			case 'core':
				return $this->context->isCore();

			case 'cli':
				return $this->context->isWpCli();

			case 'amp':
				return $this->is_amp();

			default:
				\_doing_it_wrong( __METHOD__, \esc_html( \sprintf( 'Unknown request type: %s', $type ) ), '1.0.0' );

				return false;
		}
	}

	/**
	 * Is AMP
	 *
	 * @return bool
	 */
	public function is_amp(): bool {
		return \function_exists( 'is_amp_endpoint' ) && \is_amp_endpoint();
	}

	/**
	 * Whether given user is an administrator.
	 *
	 * @param WP_User|null $user The given user.
	 * @return bool
	 */
	public static function is_user_admin( WP_User $user = null ): bool
	{ // phpcs:ignore
		if ( \is_null( $user ) ) {
			$user = \wp_get_current_user();
		}

		if ( ! $user instanceof WP_User ) {
			\_doing_it_wrong( __METHOD__, 'To check if the user is admin is required a WP_User object.', '0.0.1' );
		}

		return \is_multisite() ? \user_can($user, 'manage_network') : \user_can($user, 'manage_options'); // phpcs:ignore
	}
}
