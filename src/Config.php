<?php
/**
 * Central configuration: default values (constants) and filterable knobs.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defaults live here as constants; the accessors apply the documented filters.
 *
 * @since 1.0.0
 */
final class Config {

	public const TEXT_DOMAIN        = 'mammothwp-support';
	public const PAGE_SLUG          = 'mmwp-support';
	public const ASSET_HANDLE       = 'mmwp-support';
	public const CAPABILITY         = 'manage_options';
	public const NETWORK_CAPABILITY = 'manage_network_options';
	public const MENU_POSITION      = 80;
	public const RATE_LIMIT_MAX     = 3;
	public const RATE_LIMIT_WINDOW  = 600;
	public const LOG_OPTION         = 'mmwp_support_log';
	public const LOG_CAP            = 20;
	public const DEBUG_LOG_LINES    = 50;
	public const SUBJECT_FORMAT     = '%1$s[%2$s] %3$s (%4$s)';
	public const JSON_FILENAME      = 'mammothwp-support.json';
	public const WPKIT_MIN_VERSION  = '1.0.0';
	public const REST_NAMESPACE     = 'mmwp/v1';
	public const ADMIN_POST_ACTION  = 'mmwp_support_send';
	public const NONCE_ACTION       = 'mmwp_support_send';
	public const REST_NONCE_ACTION  = 'wp_rest';
	public const NOTICE_TTL         = 300;
	public const MAILTO_MAX_CHARS   = 1500;
	public const MAMMOTH_VENDOR     = 'Mammoth WP';
	public const MAMMOTH_URL        = 'https://mammothwp.com';
	public const LOCATIONS          = array( 'toplevel', 'tools', 'settings', 'hidden' );

	/**
	 * Capability required to use the Support screen and the operations.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network Whether the network admin screen is meant.
	 * @return string
	 */
	public static function capability( bool $network = false ): string {
		$default = $network ? self::NETWORK_CAPABILITY : self::CAPABILITY;

		/**
		 * Filters the capability required for the Support screen and operations.
		 *
		 * @since 1.0.0
		 *
		 * @param string $capability Capability, default `manage_options`.
		 * @param bool   $network    True for the network admin screen.
		 */
		$capability = apply_filters( 'mmwp_support_capability', $default, $network );
		return is_string( $capability ) && '' !== $capability ? $capability : $default;
	}

	/**
	 * Rate limit: maximum sends per window, and window length in seconds.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id Current user.
	 * @return array{max:int,window:int}
	 */
	public static function rate_limit( int $user_id ): array {
		$default = array(
			'max'    => self::RATE_LIMIT_MAX,
			'window' => self::RATE_LIMIT_WINDOW,
		);

		/**
		 * Filters the send rate limit (per user).
		 *
		 * @since 1.0.0
		 *
		 * @param array{max:int,window:int} $limit   Max sends and window in seconds.
		 * @param int                       $user_id User id.
		 */
		$limit = apply_filters( 'mmwp_support_rate_limit', $default, $user_id );
		if ( ! is_array( $limit ) ) {
			return $default;
		}
		return array(
			'max'    => max( 0, (int) ( $limit['max'] ?? $default['max'] ) ),
			'window' => max( 1, (int) ( $limit['window'] ?? $default['window'] ) ),
		);
	}

	/**
	 * Whether the JSON report is attached when the report is included.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public static function attach_report( string $slug ): bool {
		/**
		 * Filters whether `site-report-<host>-<date>.json` is attached to the email.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $attach Default true.
		 * @param string $slug   Plugin slug.
		 */
		return (bool) apply_filters( 'mmwp_support_attach_report', true, $slug );
	}

	/**
	 * Menu position for the top-level menu.
	 *
	 * @since 1.0.0
	 *
	 * @return float|int
	 */
	public static function menu_position() {
		/**
		 * Filters the position of the top-level menu.
		 *
		 * @since 1.0.0
		 *
		 * @param int $position Default 80.
		 */
		$position = apply_filters( 'mmwp_support_menu_position', self::MENU_POSITION );
		return is_numeric( $position ) ? $position + 0 : self::MENU_POSITION;
	}

	/**
	 * Whether a plugin gets a "Support" action link on the Plugins screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Plugin slug.
	 * @return bool
	 */
	public static function action_link_enabled( string $slug ): bool {
		/**
		 * Filters whether a plugin gets the "Support" action link.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $enabled Default true.
		 * @param string $slug    Plugin slug.
		 */
		return (bool) apply_filters( 'mmwp_support_action_link', true, $slug );
	}
}
