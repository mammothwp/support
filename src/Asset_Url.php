<?php
/**
 * Maps a filesystem path to a URL.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local path to URL for files under the plugin, mu-plugin or content directories.
 *
 * @since 1.0.0
 */
final class Asset_Url {

	/**
	 * Convert.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Absolute path.
	 * @return string URL, '' when the path is outside every known base.
	 */
	public static function for_path( string $path ): string {
		$path  = wp_normalize_path( $path );
		$pairs = array(
			array( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '', defined( 'WP_PLUGIN_URL' ) ? WP_PLUGIN_URL : '' ),
			array( defined( 'WPMU_PLUGIN_DIR' ) ? WPMU_PLUGIN_DIR : '', defined( 'WPMU_PLUGIN_URL' ) ? WPMU_PLUGIN_URL : '' ),
			array( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '', content_url() ),
		);
		foreach ( $pairs as $pair ) {
			$base = trailingslashit( wp_normalize_path( $pair[0] ) );
			if ( '/' !== $base && 0 === strpos( $path, $base ) ) {
				return trailingslashit( $pair[1] ) . substr( $path, strlen( $base ) );
			}
		}
		return '';
	}
}
