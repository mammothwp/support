<?php
/**
 * Failure isolation for report collection.
 *
 * @package MammothWP\Support
 * @since   1.0.1
 */

namespace MammothWP\Support\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs collectors so that one failing field or section never fatals wp-admin.
 *
 * @since 1.0.1
 */
final class Field_Guard {

	/**
	 * Placeholder for a value that could not be collected.
	 */
	public const UNAVAILABLE = 'unavailable';

	/**
	 * Run a callable; on any Throwable log it and return the placeholder.
	 *
	 * @since 1.0.1
	 *
	 * @param callable $collector Zero-argument collector.
	 * @param string   $context   Where it ran (for the log line).
	 * @param mixed    $fallback  Value returned on failure.
	 * @return mixed
	 */
	public static function run( callable $collector, string $context, $fallback = self::UNAVAILABLE ) {
		try {
			return $collector();
		} catch ( \Throwable $e ) {
			self::log( $context, $e );
			return $fallback;
		}
	}

	/**
	 * Run a map of key => collector, isolating each field.
	 *
	 * @since 1.0.1
	 *
	 * @param array<string,callable> $collectors Field collectors.
	 * @param string                 $context    Section name for the log.
	 * @return array<string,mixed>
	 */
	public static function fields( array $collectors, string $context ): array {
		$data = array();
		foreach ( $collectors as $key => $collector ) {
			$data[ $key ] = self::run( $collector, $context . '.' . $key );
		}
		return $data;
	}

	/**
	 * Log a failure to the PHP error log, only when WP_DEBUG is on.
	 *
	 * @since 1.0.1
	 *
	 * @param string     $context Where it failed.
	 * @param \Throwable $e       The failure.
	 * @return void
	 */
	public static function log( string $context, \Throwable $e ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! constant( 'WP_DEBUG' ) ) {
			return;
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated by WP_DEBUG.
		error_log( sprintf( '[mammothwp-support] report %s unavailable: %s: %s', $context, get_class( $e ), $e->getMessage() ) );
	}
}
