<?php
/**
 * Report section: server.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Report\Field_Guard;
use MammothWP\Support\Report\Section_Interface;

/**
 * PHP, extensions, limits, database and web server.
 *
 * @since 1.0.0
 */
final class Server_Section implements Section_Interface {

	public const EXTENSIONS = array( 'curl', 'mbstring', 'intl', 'imagick', 'gd', 'zip', 'openssl', 'sodium' );

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'server';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Server', 'mammothwp-support' );
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		return Field_Guard::fields(
			array(
				'php_version'         => static fn() => PHP_VERSION,
				'php_sapi'            => static fn() => PHP_SAPI,
				'extensions'          => static fn() => self::extensions(),
				'memory_limit'        => static fn() => (string) ini_get( 'memory_limit' ),
				'max_execution_time'  => static fn() => (string) ini_get( 'max_execution_time' ),
				'upload_max_filesize' => static fn() => (string) ini_get( 'upload_max_filesize' ),
				'post_max_size'       => static fn() => (string) ini_get( 'post_max_size' ),
				'database'            => static fn() => self::database(),
				'server_software'     => static fn() => self::server_software(),
				'os_family'           => static fn() => PHP_OS_FAMILY,
			),
			'server'
		);
	}

	/**
	 * Loaded state of the extensions that matter for support.
	 *
	 * @since 1.0.1
	 *
	 * @return array<string,string>
	 */
	public static function extensions(): array {
		$extensions = array();
		foreach ( self::EXTENSIONS as $extension ) {
			$extensions[ $extension ] = Site_Section::yes_no( extension_loaded( $extension ) );
		}
		return $extensions;
	}

	/**
	 * Database type and version (never host, name or credentials).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function database(): string {
		global $wpdb;
		if ( ! $wpdb instanceof \wpdb ) {
			return 'unknown';
		}
		$info = (string) $wpdb->db_server_info();
		$type = false !== stripos( $info, 'mariadb' ) ? 'MariaDB' : 'MySQL';
		return $type . ' ' . (string) $wpdb->db_version();
	}

	/**
	 * Web server software string, sanitised.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function server_software(): string {
		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		return '' === $software ? 'unknown' : $software;
	}
}
