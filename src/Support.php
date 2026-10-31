<?php
/**
 * Public entry point: the only class plugin authors call.
 *
 * It is deliberately a thin, stable shim. The first copy of the package that
 * loads defines this class for the whole request, even when a newer copy wins
 * the election, so it must never gain logic or change a signature.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public API.
 *
 * @since 1.0.0
 */
final class Support {

	/**
	 * Menu slug of the Mammoth WP top-level menu (and of the canonical Support screen).
	 *
	 * Mammoth WP plugins may add their own pages with
	 * `add_submenu_page( Support::MENU_PARENT, ... )`.
	 *
	 * @since 1.0.0
	 */
	public const MENU_PARENT = 'mmwp-support';

	/**
	 * Register a plugin with the shared Support screen.
	 *
	 * Safe to call before the package boots (queued, then replayed).
	 *
	 * @since 1.0.0
	 *
	 * @param string              $file Main plugin file (usually __FILE__).
	 * @param array<string,mixed> $args Configuration; see docs/CONFIGURATION.md. Only `email` is required.
	 * @return void
	 */
	public static function register_plugin( string $file, array $args = array() ): void {
		\mmwp_support_queue_plugin( $file, $args );
	}
}
