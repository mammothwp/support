<?php
/**
 * Per-user send rate limit.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;

/**
 * Sliding window kept in a transient per user.
 *
 * @since 1.0.0
 */
final class Rate_Limiter {

	/**
	 * Transient key.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @return string
	 */
	private static function key( int $user_id ): string {
		return 'mmwp_support_rl_' . $user_id;
	}

	/**
	 * Timestamps of recent sends inside the window.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @param int $window  Window in seconds.
	 * @param int $now     Current time.
	 * @return int[]
	 */
	private function recent( int $user_id, int $window, int $now ): array {
		$hits = get_transient( self::key( $user_id ) );
		$hits = is_array( $hits ) ? array_map( 'intval', $hits ) : array();
		return array_values( array_filter( $hits, static fn( int $at ): bool => $at > $now - $window ) );
	}

	/**
	 * Whether the user may send now.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @param int $now     Current time.
	 * @return bool
	 */
	public function allowed( int $user_id, int $now ): bool {
		$limit = Config::rate_limit( $user_id );
		return count( $this->recent( $user_id, $limit['window'], $now ) ) < $limit['max'];
	}

	/**
	 * Record a send.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User id.
	 * @param int $now     Current time.
	 * @return void
	 */
	public function record( int $user_id, int $now ): void {
		$limit  = Config::rate_limit( $user_id );
		$hits   = $this->recent( $user_id, $limit['window'], $now );
		$hits[] = $now;
		set_transient( self::key( $user_id ), $hits, $limit['window'] );
	}
}
