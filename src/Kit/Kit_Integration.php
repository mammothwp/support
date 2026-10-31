<?php
/**
 * Availability check for wp-kit.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Kit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;

/**
 * Decides whether REST / WP-CLI / Abilities can be registered.
 *
 * Note that wp-kit has no single-instance election across plugins, so another plugin may have loaded an
 * older copy first. When the loaded version is missing or too old, the operations are skipped and
 * the screen keeps working through admin-post.php.
 *
 * @since 1.0.0
 */
final class Kit_Integration {

	/**
	 * Version of the booted wp-kit, '' when it is missing or not booted.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function loaded_version(): string {
		$kit = '\MammothWP\WpKit\Kit';
		if ( ! class_exists( $kit, false ) || ! (bool) call_user_func( array( $kit, 'is_booted' ) ) ) {
			return '';
		}
		return (string) call_user_func( array( $kit, 'version' ) );
	}

	/**
	 * Whether a loaded version satisfies the requirement.
	 *
	 * @since 1.0.0
	 *
	 * @param string $loaded   Loaded version ('' = missing).
	 * @param string $required Minimum version.
	 * @return bool
	 */
	public static function is_supported( string $loaded, string $required = Config::WPKIT_MIN_VERSION ): bool {
		return '' !== $loaded && version_compare( $loaded, $required, '>=' );
	}

	/**
	 * Run the registration callback only when wp-kit is usable.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $loaded   Loaded wp-kit version.
	 * @param callable $register Registration callback.
	 * @return bool Whether the operations were registered.
	 */
	public static function register_if_supported( string $loaded, callable $register ): bool {
		if ( ! self::is_supported( $loaded ) ) {
			return false;
		}
		call_user_func( $register );
		return true;
	}

	/**
	 * Register the Support resource on REST, WP-CLI and Abilities.
	 *
	 * @since 1.0.0
	 *
	 * @param Operations $operations Shared operations service.
	 * @return void
	 */
	public static function register_adapters( Operations $operations ): void {
		$registry = ( new \MammothWP\WpKit\Registry() )->add( new Support_Resource( $operations ) )->ready();
		foreach ( $registry->all() as $resource ) {
			( new \MammothWP\WpKit\Adapters\Rest_Adapter( $resource ) )->register();
			( new \MammothWP\WpKit\Adapters\Cli_Adapter( $resource ) )->register();
			( new \MammothWP\WpKit\Adapters\Ability_Adapter( $resource ) )->register();
		}
	}
}
