<?php
/**
 * Resolves the branding object.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Precedence (lowest to highest): Mammoth/neutral defaults, single-vendor branding,
 * site-owner white-label constant, `mmwp_support_branding` filter.
 *
 * @since 1.0.0
 */
final class Branding_Resolver {

	/**
	 * Neutral defaults.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public static function neutral(): array {
		return array(
			'name'              => __( 'Support', 'mammothwp-support' ),
			'menu_title'        => __( 'Support', 'mammothwp-support' ),
			'page_title'        => __( 'Support', 'mammothwp-support' ),
			'icon'              => 'dashicons-sos',
			'logo_url'          => '',
			'subject_prefix'    => '[Support]',
			'ref_prefix'        => 'SUP',
			'show_powered_by'   => false,
			'show_vendor_names' => true,
		);
	}

	/**
	 * Mammoth WP defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param string $icon Data-URI icon.
	 * @return array<string,mixed>
	 */
	public static function mammoth( string $icon ): array {
		return array(
			'name'              => Config::MAMMOTH_VENDOR,
			'menu_title'        => Config::MAMMOTH_VENDOR,
			'page_title'        => __( 'Mammoth WP Support', 'mammothwp-support' ),
			'icon'              => $icon,
			'logo_url'          => '',
			'subject_prefix'    => '[Mammoth WP]',
			'ref_prefix'        => 'MMWP',
			'show_powered_by'   => true,
			'show_vendor_names' => true,
		);
	}

	/**
	 * Resolve.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Definition[]           $plugins       Registered plugins.
	 * @param bool|array<string,mixed>|null $whitelabel    MMWP_SUPPORT_WHITELABEL (true, array or null).
	 * @param array<string,mixed>|null      $filtered      Result of the `mmwp_support_branding` filter (null = no override).
	 * @param string[]                      $allowed_hosts Hosts allowed for logo_url.
	 * @param string                        $mammoth_icon  Mammoth WP menu icon (data URI).
	 * @return Branding
	 */
	public static function resolve( array $plugins, $whitelabel, ?array $filtered, array $allowed_hosts, string $mammoth_icon ): Branding {
		$has_mammoth = array() !== array_filter( $plugins, static fn( Plugin_Definition $p ): bool => $p->is_mammoth() );
		$mode        = $has_mammoth ? 'mammoth' : 'neutral';
		$values      = $has_mammoth ? self::mammoth( $mammoth_icon ) : self::neutral();

		$vendor = self::vendor_branding( $plugins );
		if ( array() !== $vendor ) {
			$values = array_merge( $values, $vendor );
			$mode   = 'vendor';
		}

		if ( null !== $whitelabel ) {
			$values = array_merge( self::neutral(), self::neutral_whitelabel(), is_array( $whitelabel ) ? $whitelabel : array() );
			$mode   = 'whitelabel';
		}

		if ( null !== $filtered ) {
			$values = array_merge( $values, $filtered );
			$mode   = 'whitelabel' === $mode ? $mode : 'filter';
		}

		return new Branding( self::sanitize( $values, $allowed_hosts ), $mode );
	}

	/**
	 * Extra neutralisation for white-label mode (vendor names hidden, no powered-by).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	private static function neutral_whitelabel(): array {
		return array(
			'show_powered_by'   => false,
			'show_vendor_names' => false,
		);
	}

	/**
	 * Merge the plugins' branding arrays, only when every plugin has the same vendor.
	 * The first plugin that sets a key wins.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Definition[] $plugins Plugins.
	 * @return array<string,mixed>
	 */
	private static function vendor_branding( array $plugins ): array {
		$vendors = array_unique( array_map( static fn( Plugin_Definition $p ): string => strtolower( trim( $p->vendor ) ), $plugins ) );
		if ( array() === $plugins || 1 !== count( $vendors ) ) {
			return array();
		}

		$merged = array();
		foreach ( $plugins as $plugin ) {
			$merged += $plugin->branding;
		}
		return $merged;
	}

	/**
	 * Keep only known keys with safe types; reject remote logos and invalid icons.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $values        Candidate values.
	 * @param string[]            $allowed_hosts Hosts allowed for logo_url.
	 * @return array<string,mixed>
	 */
	private static function sanitize( array $values, array $allowed_hosts ): array {
		$defaults = self::neutral();
		$clean    = array();
		foreach ( Branding::KEYS as $key ) {
			$value = $values[ $key ] ?? $defaults[ $key ];
			if ( is_bool( $defaults[ $key ] ) ) {
				$clean[ $key ] = (bool) $value;
				continue;
			}
			$clean[ $key ] = is_scalar( $value ) ? trim( wp_strip_all_tags( (string) $value ) ) : (string) $defaults[ $key ];
		}

		if ( ! Branding_Guard::is_valid_icon( $clean['icon'] ) ) {
			$clean['icon'] = $defaults['icon'];
		}
		if ( '' !== $clean['logo_url'] && ! Branding_Guard::is_local_url( $clean['logo_url'], $allowed_hosts ) ) {
			$clean['logo_url'] = '';
		}
		$clean['ref_prefix'] = strtoupper( (string) preg_replace( '/[^A-Za-z0-9]/', '', $clean['ref_prefix'] ) );
		if ( '' === $clean['ref_prefix'] ) {
			$clean['ref_prefix'] = $defaults['ref_prefix'];
		}
		return $clean;
	}
}
