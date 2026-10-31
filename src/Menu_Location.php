<?php
/**
 * Value object: resolved canonical menu location.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Where the canonical Support screen lives and why.
 *
 * @since 1.0.0
 */
final class Menu_Location {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type   toplevel, tools, settings, parent or hidden.
	 * @param string $parent_menu Parent menu slug when type is "parent".
	 * @param string $source default, constant or filter.
	 */
	public function __construct(
		public readonly string $type,
		public readonly string $parent_menu,
		public readonly string $source
	) {
	}

	/**
	 * Parent slug to pass to add_submenu_page() (empty for toplevel).
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network Whether for the network admin.
	 * @return string
	 */
	public function parent_slug( bool $network = false ): string {
		return match ( $this->type ) {
			'tools'    => $network ? 'settings.php' : 'tools.php',
			'settings' => $network ? 'settings.php' : 'options-general.php',
			'parent'   => $this->parent_menu,
			'hidden'   => 'options.php',
			default    => Support::MENU_PARENT,
		};
	}

	/**
	 * Admin path (relative to wp-admin/) of the canonical screen.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_slug Optional plugin to preselect.
	 * @return string
	 */
	public function admin_path( string $plugin_slug = '' ): string {
		$query = 'page=' . Config::PAGE_SLUG . ( '' !== $plugin_slug ? '&plugin=' . rawurlencode( $plugin_slug ) : '' );
		$base  = match ( $this->type ) {
			'tools'    => 'tools.php',
			'settings' => 'options-general.php',
			'parent'   => str_contains( $this->parent_menu, '.php' ) ? $this->parent_menu : 'admin.php',
			default    => 'admin.php',
		};
		return $base . ( str_contains( $base, '?' ) ? '&' : '?' ) . $query;
	}

	/**
	 * Array form for reports and the CLI.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'location' => 'parent' === $this->type ? 'parent:' . $this->parent_menu : $this->type,
			'source'   => $this->source,
		);
	}
}
