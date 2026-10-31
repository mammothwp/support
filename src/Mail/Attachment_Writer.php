<?php
/**
 * Writes the JSON report attachment.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes `site-report-<host>-<date>.json` into a throw-away temp directory.
 *
 * @since 1.0.0
 */
final class Attachment_Writer {

	/**
	 * File name for a host and date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $host      Site host.
	 * @param string $date      Date, Y-m-d.
	 * @return string
	 */
	public static function filename( string $host, string $date ): string {
		$host = trim( (string) preg_replace( '/[^a-z0-9.-]+/', '-', strtolower( $host ) ), '-.' );
		return sprintf( 'site-report-%s-%s.json', '' === $host ? 'site' : $host, $date );
	}

	/**
	 * Write the file.
	 *
	 * @since 1.0.0
	 *
	 * @param string $filename File name.
	 * @param string $json     Contents.
	 * @return string Absolute path, '' on failure.
	 */
	public function write( string $filename, string $json ): string {
		$dir = trailingslashit( get_temp_dir() ) . 'mmwp-' . wp_generate_password( 12, false );
		if ( ! wp_mkdir_p( $dir ) ) {
			return '';
		}
		$path = $dir . '/' . $filename;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- short-lived file in the system temp dir.
		return false === file_put_contents( $path, $json ) ? '' : $path;
	}

	/**
	 * Remove the file and its directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Path returned by write().
	 * @return void
	 */
	public function remove( string $path ): void {
		if ( '' === $path ) {
			return;
		}
		wp_delete_file( $path );
		$dir = dirname( $path );
		if ( is_dir( $dir ) && array() === array_diff( (array) scandir( $dir ), array( '.', '..' ) ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the empty temp dir created by write().
			rmdir( $dir );
		}
	}
}
