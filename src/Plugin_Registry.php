<?php
/**
 * Collection of registered plugins.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holds the registered plugins and answers questions about them.
 *
 * @since 1.0.0
 */
final class Plugin_Registry {

	/**
	 * Registered plugins by slug.
	 *
	 * @var array<string,Plugin_Definition>
	 */
	private array $plugins = array();

	/**
	 * Add a plugin; a duplicate slug is ignored (first registration wins).
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Definition $plugin Plugin.
	 * @return void
	 */
	public function add( Plugin_Definition $plugin ): void {
		if ( isset( $this->plugins[ $plugin->slug ] ) ) {
			return;
		}
		$this->plugins[ $plugin->slug ] = $plugin;
	}

	/**
	 * All plugins, in registration order.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin_Definition[]
	 */
	public function all(): array {
		return array_values( $this->plugins );
	}

	/**
	 * Find by slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug Slug.
	 * @return Plugin_Definition|null
	 */
	public function get( string $slug ): ?Plugin_Definition {
		return $this->plugins[ $slug ] ?? null;
	}

	/**
	 * Whether at least one plugin is registered.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_any(): bool {
		return array() !== $this->plugins;
	}

	/**
	 * Whether at least one registered plugin is by Mammoth WP.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_mammoth(): bool {
		foreach ( $this->plugins as $plugin ) {
			if ( $plugin->is_mammoth() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether every registered plugin has the same (case-insensitive) vendor.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function single_vendor(): bool {
		$vendors = array_unique( array_map( static fn( Plugin_Definition $p ): string => strtolower( trim( $p->vendor ) ), $this->plugins ) );
		return 1 === count( $vendors );
	}
}
