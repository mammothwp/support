<?php
/**
 * Validation of branding inputs (local logo URL, icon).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps remote assets out of the admin UI.
 *
 * @since 1.0.0
 */
final class Branding_Guard {

	/**
	 * Whether a logo URL is local: relative to this site, or on one of the site's own hosts.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $url           Candidate URL.
	 * @param string[] $allowed_hosts Hosts of home/site/content URLs.
	 * @return bool
	 */
	public static function is_local_url( string $url, array $allowed_hosts ): bool {
		$url = trim( $url );
		if ( '' === $url || preg_match( '/[\x00-\x1f\s\\\\]/', $url ) ) {
			return false;
		}
		if ( '/' === $url[0] ) {
			return '/' !== ( $url[1] ?? '' );
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || ! in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true ) || isset( $parts['user'] ) ) {
			return false;
		}
		$host = strtolower( (string) ( $parts['host'] ?? '' ) );
		return '' !== $host && in_array( $host, array_map( 'strtolower', $allowed_hosts ), true );
	}

	/**
	 * Whether an icon is a dashicon name or an SVG data URI.
	 *
	 * @since 1.0.0
	 *
	 * @param string $icon Candidate icon.
	 * @return bool
	 */
	public static function is_valid_icon( string $icon ): bool {
		return 1 === preg_match( '/^dashicons-[a-z0-9-]+$/', $icon )
			|| 1 === preg_match( '#^data:image/svg\+xml;base64,[A-Za-z0-9+/=]+$#', $icon );
	}
}
