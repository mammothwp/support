<?php
/**
 * Single-instance bootstrap for mammothwp/support.
 *
 * Loaded through Composer's "files" autoload by every plugin that bundles this
 * package. Each include registers its copy (version + path) in a shared
 * registry; on `plugins_loaded` exactly one copy boots: the highest version.
 * The winner prepends a class loader for `MammothWP\Support\` pointing at its
 * own src/ directory, so its classes are the ones used.
 *
 * Everything in this file (and the tiny `Support` shim) is declared with
 * function_exists() guards because the FIRST copy to load owns these symbols
 * for the whole request. Keep them small and backwards compatible forever.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

if ( ! function_exists( 'mmwp_support_register_copy' ) ) {

	/**
	 * Shared state accessor (copies, queue, winner).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	function mmwp_support_state(): array {
		if ( ! isset( $GLOBALS['mmwp_support_state'] ) || ! is_array( $GLOBALS['mmwp_support_state'] ) ) {
			$GLOBALS['mmwp_support_state'] = array(
				'copies' => array(),
				'queue'  => array(),
				'winner' => null,
				'hooked' => false,
			);
		}
		return $GLOBALS['mmwp_support_state'];
	}

	/**
	 * Register this copy and make sure the boot hook exists.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Semantic version of the copy.
	 * @param string $path    Absolute directory containing bootstrap.php.
	 * @return void
	 */
	function mmwp_support_register_copy( string $version, string $path ): void {
		$state = mmwp_support_state();
		$path  = rtrim( $path, '/\\' );
		if ( '' === $path || null !== $state['winner'] ) {
			return;
		}

		$state['copies'][ $path ] = $version;
		if ( $state['hooked'] ) {
			$GLOBALS['mmwp_support_state'] = $state;
			return;
		}

		$state['hooked']               = true;
		$GLOBALS['mmwp_support_state'] = $state;

		if ( function_exists( 'did_action' ) && did_action( 'plugins_loaded' ) ) {
			mmwp_support_boot();
			return;
		}

		if ( function_exists( 'add_action' ) ) {
			add_action( 'plugins_loaded', 'mmwp_support_boot', -50 );
			return;
		}

		// Loaded from wp-config.php (Bedrock, WP-CLI) before the Plugin API exists: pre-seed the hook.
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- no Plugin API yet.
		$GLOBALS['wp_filter']['plugins_loaded'][-50][] = array(
			'function'      => 'mmwp_support_boot',
			'accepted_args' => 1,
		);
	}

	/**
	 * Pick the copy with the highest version.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $copies Path => version.
	 * @return string|null Winning path, null when there are no copies.
	 */
	function mmwp_support_pick_winner( array $copies ): ?string {
		$best_path    = null;
		$best_version = null;
		foreach ( $copies as $path => $version ) {
			if ( null === $best_version || version_compare( (string) $version, (string) $best_version, '>' ) ) {
				$best_path    = (string) $path;
				$best_version = (string) $version;
			}
		}
		return $best_path;
	}

	/**
	 * Prepend a class loader for `MammothWP\Support\` rooted at a copy's src/.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path Directory of the winning copy.
	 * @return void
	 */
	function mmwp_support_install_loader( string $path ): void {
		$prefix = 'MammothWP\\Support\\';
		$root   = $path . '/src/';
		spl_autoload_register(
			static function ( string $class_name ) use ( $prefix, $root ): void {
				if ( 0 !== strpos( $class_name, $prefix ) ) {
					return;
				}
				$file = $root . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
				if ( is_file( $file ) ) {
					require_once $file;
				}
			},
			true,
			true
		);
	}

	/**
	 * Elect the winner and install its loader (no WordPress side effects).
	 *
	 * @since 1.0.0
	 *
	 * @return string|null Winning path.
	 */
	function mmwp_support_elect(): ?string {
		$state = mmwp_support_state();
		if ( null !== $state['winner'] ) {
			return $state['winner'];
		}

		$winner = mmwp_support_pick_winner( $state['copies'] );
		if ( null === $winner ) {
			return null;
		}

		$state['winner']               = $winner;
		$GLOBALS['mmwp_support_state'] = $state;
		mmwp_support_install_loader( $winner );
		return $winner;
	}

	/**
	 * `plugins_loaded` callback: elect, then boot the winner's kernel.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	function mmwp_support_boot(): void {
		$winner = mmwp_support_elect();
		if ( null === $winner ) {
			return;
		}

		$version = (string) mmwp_support_state()['copies'][ $winner ];
		if ( class_exists( '\MammothWP\Support\Kernel' ) ) {
			\MammothWP\Support\Kernel::boot( $version, $winner );
		}
	}

	/**
	 * Queue a plugin registration (replayed at boot, applied at once afterwards).
	 *
	 * @since 1.0.0
	 *
	 * @param string              $file Main plugin file.
	 * @param array<string,mixed> $args Registration arguments.
	 * @return void
	 */
	function mmwp_support_queue_plugin( string $file, array $args ): void {
		$state            = mmwp_support_state();
		$state['queue'][] = array( $file, $args );

		$GLOBALS['mmwp_support_state'] = $state;
		if ( null !== $state['winner'] && class_exists( '\MammothWP\Support\Kernel' ) ) {
			\MammothWP\Support\Kernel::flush_queue();
		}
	}
}

mmwp_support_register_copy( (string) require __DIR__ . '/version.php', __DIR__ );
