<?php
/**
 * Reads the tail of debug.log.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the last lines of the WordPress debug log when WP_DEBUG_LOG points to a readable file.
 *
 * @since 1.0.0
 */
final class Debug_Log_Reader {

	public const MAX_BYTES = 262144;

	/**
	 * Resolved log path, or '' when logging to a file is not configured or the file is unreadable.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function path(): string {
		if ( ! defined( 'WP_DEBUG_LOG' ) || ! constant( 'WP_DEBUG_LOG' ) ) {
			return '';
		}
		$value = constant( 'WP_DEBUG_LOG' );
		$path  = is_string( $value ) ? $value : ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/debug.log' : '' );
		return '' !== $path && is_file( $path ) && is_readable( $path ) ? $path : '';
	}

	/**
	 * Whether the log can be offered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function available(): bool {
		return '' !== self::path();
	}

	/**
	 * Last lines of a file (reads at most MAX_BYTES from the end).
	 *
	 * @since 1.0.0
	 *
	 * @param string $path  File path.
	 * @param int    $lines Number of lines.
	 * @return string[]
	 */
	public static function tail( string $path, int $lines ): array {
		if ( '' === $path || ! is_readable( $path ) ) {
			return array();
		}
		$size = (int) filesize( $path );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- bounded read of a local log.
		$handle = fopen( $path, 'rb' );
		if ( false === $handle ) {
			return array();
		}
		fseek( $handle, -min( $size, self::MAX_BYTES ), SEEK_END );
		$chunk = (string) stream_get_contents( $handle );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- paired with fopen above.
		fclose( $handle );

		$all = preg_split( '/\R/', rtrim( $chunk ) );
		return array_slice( false === $all ? array() : $all, -max( 1, $lines ) );
	}
}
