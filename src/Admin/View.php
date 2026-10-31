<?php
/**
 * View renderer.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Includes a view file with a `$view` array in scope.
 *
 * @since 1.0.0
 */
final class View {

	/**
	 * Render a view from the package's views/ directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $name Name without extension.
	 * @param array<string,mixed> $view Data for the view.
	 * @return void
	 */
	public static function render( string $name, array $view ): void {
		$file = dirname( __DIR__, 2 ) . '/views/' . $name . '.php';
		if ( ! is_file( $file ) ) {
			return;
		}
		include $file; // The file reads $view.
		unset( $view );
	}
}
