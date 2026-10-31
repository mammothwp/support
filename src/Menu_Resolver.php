<?php
/**
 * Resolves the canonical menu location.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure resolver: filter > constant > default.
 *
 * @since 1.0.0
 */
final class Menu_Resolver {

	/**
	 * Resolve the location.
	 *
	 * @since 1.0.0
	 *
	 * @param bool        $has_mammoth Whether a Mammoth WP plugin is registered.
	 * @param string|null $constant    MMWP_SUPPORT_MENU value.
	 * @param string|null $filtered    Value returned by the `mmwp_support_menu` filter (null = no override).
	 * @return Menu_Location
	 */
	public static function resolve( bool $has_mammoth, ?string $constant, ?string $filtered ): Menu_Location {
		$from_filter = self::parse( $filtered, 'filter' );
		if ( null !== $from_filter ) {
			return $from_filter;
		}

		$from_constant = self::parse( $constant, 'constant' );
		if ( null !== $from_constant ) {
			return $from_constant;
		}

		return new Menu_Location( $has_mammoth ? 'toplevel' : 'tools', '', 'default' );
	}

	/**
	 * Parse `toplevel|tools|settings|hidden|parent:<slug>`.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $value  Raw value.
	 * @param string      $source Source label.
	 * @return Menu_Location|null Null when empty or invalid.
	 */
	public static function parse( ?string $value, string $source ): ?Menu_Location {
		$value = null === $value ? '' : trim( $value );
		if ( in_array( $value, Config::LOCATIONS, true ) ) {
			return new Menu_Location( $value, '', $source );
		}
		if ( 0 === strpos( $value, 'parent:' ) && strlen( $value ) > 7 ) {
			return new Menu_Location( 'parent', substr( $value, 7 ), $source );
		}
		return null;
	}
}
