<?php
/**
 * Report section: plugins.
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
 * Active plugins (name + version), network-active, mu-plugins, drop-ins, inactive count.
 *
 * @since 1.0.0
 */
final class Plugins_Section implements Section_Interface {

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'plugins';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Plugins', 'mammothwp-support' );
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all     = (array) get_plugins();
		$active  = array_map( 'strval', (array) get_option( 'active_plugins', array() ) );
		$network = is_multisite() ? array_map( 'strval', array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) ) : array();

		return Field_Guard::fields(
			array(
				'active'         => static fn() => self::describe( $all, $active ),
				'network_active' => static fn() => self::describe( $all, $network ),
				'mu_plugins'     => static fn() => self::names( (array) get_mu_plugins() ),
				'drop_ins'       => static fn() => self::names( (array) get_dropins() ),
				'inactive_count' => static fn() => (string) max( 0, count( $all ) - count( array_unique( array_merge( $active, $network ) ) ) ),
			),
			'plugins'
		);
	}

	/**
	 * "Name version" list for the given plugin files, sorted by name.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $all   All installed plugins.
	 * @param string[]                          $files Plugin basenames.
	 * @return string[]
	 */
	public static function describe( array $all, array $files ): array {
		$lines = array();
		foreach ( $files as $file ) {
			if ( ! isset( $all[ $file ] ) ) {
				continue;
			}
			$lines[] = trim( (string) ( $all[ $file ]['Name'] ?? $file ) . ' ' . (string) ( $all[ $file ]['Version'] ?? '' ) );
		}
		sort( $lines, SORT_FLAG_CASE | SORT_STRING );
		return $lines;
	}

	/**
	 * Names of mu-plugins / drop-ins, sorted.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $items Plugin data keyed by file.
	 * @return string[]
	 */
	public static function names( array $items ): array {
		$names = array();
		foreach ( $items as $file => $data ) {
			$names[] = (string) ( '' !== (string) ( $data['Name'] ?? '' ) ? $data['Name'] : $file );
		}
		sort( $names, SORT_FLAG_CASE | SORT_STRING );
		return $names;
	}
}
