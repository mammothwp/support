<?php
/**
 * One-shot notice storage between the admin-post redirect and the screen.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;

/**
 * Keeps the outcome of a no-JS send for the next page load (per user, short-lived).
 *
 * @since 1.0.0
 */
final class Notice_Store {

	/**
	 * Store.
	 *
	 * @since 1.0.0
	 *
	 * @param int                 $user_id User id.
	 * @param array<string,mixed> $notice  Notice data.
	 * @return void
	 */
	public static function put( int $user_id, array $notice ): void {
		set_transient( 'mmwp_support_notice_' . $user_id, $notice, Config::NOTICE_TTL );
	}

	/**
	 * Fetch and delete.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return array<string,mixed>
	 */
	public static function take( int $user_id ): array {
		$notice = get_transient( 'mmwp_support_notice_' . $user_id );
		if ( false === $notice ) {
			return array();
		}
		delete_transient( 'mmwp_support_notice_' . $user_id );
		return is_array( $notice ) ? $notice : array();
	}
}
