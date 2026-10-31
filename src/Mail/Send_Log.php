<?php
/**
 * Capped send log.
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
 * Keeps the last sends (time, plugin, ref, result; never contents) in an option.
 *
 * @since 1.0.0
 */
final class Send_Log {

	/**
	 * Add an entry, newest first, capped.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $time   Unix time.
	 * @param string $plugin Plugin slug.
	 * @param string $ref    Reference.
	 * @param string $result sent or failed.
	 * @return void
	 */
	public function add( int $time, string $plugin, string $ref, string $result ): void {
		$entries = $this->all();
		array_unshift(
			$entries,
			array(
				'time'   => $time,
				'plugin' => $plugin,
				'ref'    => $ref,
				'result' => $result,
			)
		);
		update_option( Config::LOG_OPTION, array_slice( $entries, 0, Config::LOG_CAP ), false );
	}

	/**
	 * All entries, newest first.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int,array{time:int,plugin:string,ref:string,result:string}>
	 */
	public function all(): array {
		$stored = get_option( Config::LOG_OPTION, array() );
		return is_array( $stored ) ? array_values( $stored ) : array();
	}
}
