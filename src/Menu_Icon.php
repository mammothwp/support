<?php
/**
 * Admin menu icon.
 *
 * @package MammothWP\Support
 * @since   1.0.1
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the menu icon data URI from the source SVG, so no base64 is ever pasted by hand.
 *
 * @since 1.0.1
 */
final class Menu_Icon {

	/**
	 * Fallback when the SVG is missing or unusable.
	 */
	public const FALLBACK = 'dashicons-sos';

	/**
	 * Encode SVG markup as a base64 data URI (WP recolours `fill="black"` to the admin scheme).
	 *
	 * @since 1.0.1
	 *
	 * @param string $svg SVG markup.
	 * @return string Data URI, or the dashicon fallback when the markup is not an SVG.
	 */
	public static function from_svg( string $svg ): string {
		if ( false === stripos( $svg, '<svg' ) ) {
			return self::FALLBACK;
		}
		return 'data:image/svg+xml;base64,' . base64_encode( trim( $svg ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- SVG data URI for the menu icon.
	}

	/**
	 * Data URI for an SVG file.
	 *
	 * @since 1.0.1
	 *
	 * @param string $file Absolute path of the SVG.
	 * @return string
	 */
	public static function from_file( string $file ): string {
		if ( ! is_readable( $file ) ) {
			return self::FALLBACK;
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file inside the package.
		return self::from_svg( (string) file_get_contents( $file ) );
	}
}
