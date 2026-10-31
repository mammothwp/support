<?php
/**
 * Site-owner settings read from constants (injectable for tests).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads the site-owner constants MMWP_SUPPORT_MENU, MMWP_SUPPORT_WHITELABEL and
 * MMWP_SUPPORT_EMAIL_<SLUG>.
 *
 * @since 1.0.0
 */
final class Site_Settings {

	/**
	 * Constant lookup: name => value, or null when undefined.
	 *
	 * @var callable
	 */
	private $lookup;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param callable|null $lookup Optional lookup `fn( string $name ): mixed` returning null when undefined.
	 */
	public function __construct( ?callable $lookup = null ) {
		$this->lookup = $lookup ?? static function ( string $name ) {
			return defined( $name ) ? constant( $name ) : null;
		};
	}

	/**
	 * The MMWP_SUPPORT_MENU constant.
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function menu(): ?string {
		$value = ( $this->lookup )( 'MMWP_SUPPORT_MENU' );
		return is_string( $value ) && '' !== $value ? $value : null;
	}

	/**
	 * The MMWP_SUPPORT_WHITELABEL constant.
	 *
	 * @since 1.0.0
	 *
	 * @return bool|array<string,mixed>|null True, an array of overrides, or null when unset/false.
	 */
	public function whitelabel() {
		$value = ( $this->lookup )( 'MMWP_SUPPORT_WHITELABEL' );
		if ( true === $value || is_array( $value ) ) {
			return $value;
		}
		return null;
	}

	/**
	 * The email constant for a plugin slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Plugin slug.
	 * @return string|null
	 */
	public function email_for( string $slug ): ?string {
		$value = ( $this->lookup )( self::email_constant( $slug ) );
		return is_string( $value ) && '' !== $value ? $value : null;
	}

	/**
	 * Name of the email constant for a slug: MMWP_SUPPORT_EMAIL_<SLUG_UPPER_UNDERSCORED>.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Plugin slug.
	 * @return string
	 */
	public static function email_constant( string $slug ): string {
		return 'MMWP_SUPPORT_EMAIL_' . strtoupper( (string) preg_replace( '/[^A-Za-z0-9]+/', '_', $slug ) );
	}
}
