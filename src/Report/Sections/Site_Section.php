<?php
/**
 * Report section: site.
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
 * Site facts: URLs (scheme + host only), WordPress, debug flags, memory limits.
 *
 * @since 1.0.0
 */
final class Site_Section implements Section_Interface {

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'site';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Site', 'mammothwp-support' );
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		$data = Field_Guard::fields(
			array(
				'home_url'         => static fn() => self::origin( home_url() ),
				'site_url'         => static fn() => self::origin( site_url() ),
				'wp_version'       => static fn() => get_bloginfo( 'version' ),
				'multisite'        => static fn() => self::yes_no( is_multisite() ),
				'locale'           => static fn() => get_locale(),
				'timezone'         => static fn() => wp_timezone_string(),
				'permalinks'       => static fn() => '' === (string) get_option( 'permalink_structure' ) ? 'plain' : 'pretty',
				'https'            => static fn() => self::yes_no( is_ssl() ),
				'environment_type' => static fn() => wp_get_environment_type(),
				'object_cache'     => static fn() => self::yes_no( wp_using_ext_object_cache() ),
				'advanced_cache'   => static fn() => self::yes_no( defined( 'WP_CONTENT_DIR' ) && file_exists( WP_CONTENT_DIR . '/advanced-cache.php' ) ),
				'DISABLE_WP_CRON'  => static fn() => self::constant_state( 'DISABLE_WP_CRON' ),
				'WP_DEBUG'         => static fn() => self::constant_state( 'WP_DEBUG' ),
				'WP_DEBUG_LOG'     => static fn() => self::constant_state( 'WP_DEBUG_LOG' ),
				'SCRIPT_DEBUG'     => static fn() => self::constant_state( 'SCRIPT_DEBUG' ),
				'WP_MEMORY_LIMIT'  => static fn() => self::constant_value( 'WP_MEMORY_LIMIT' ),
				'WP_MAX_MEMORY'    => static fn() => self::constant_value( 'WP_MAX_MEMORY_LIMIT' ),
			),
			'site'
		);

		if ( Field_Guard::run( static fn() => is_multisite(), 'site.multisite_check', false ) ) {
			$data['multisite_sites'] = Field_Guard::run( static fn() => (string) get_blog_count(), 'site.multisite_sites' );
		}
		return $data;
	}

	/**
	 * Reduce a URL to scheme + host (no credentials, port, path or query).
	 *
	 * @since 1.0.0
	 *
	 * @param string $url URL.
	 * @return string
	 */
	public static function origin( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return '';
		}
		return ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'];
	}

	/**
	 * Any boolean-ish value as "yes"/"no" (null, '', '0', 'no', 'false', 0 and false are "no").
	 *
	 * WordPress helpers such as wp_using_ext_object_cache() can return null, so this never
	 * relies on a strict bool.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public static function yes_no( $value ): string {
		return true === filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ? 'yes' : 'no';
	}

	/**
	 * State of a boolean-ish constant: "on", "off" or "not set". Never exposes a path.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Constant name.
	 * @return string
	 */
	public static function constant_state( string $name ): string {
		if ( ! defined( $name ) ) {
			return 'not set';
		}
		return constant( $name ) ? 'on' : 'off';
	}

	/**
	 * Value of a scalar constant ("not set" when undefined).
	 *
	 * @since 1.0.0
	 *
	 * @param string $name Constant name.
	 * @return string
	 */
	public static function constant_value( string $name ): string {
		return defined( $name ) && is_scalar( constant( $name ) ) ? (string) constant( $name ) : 'not set';
	}
}
