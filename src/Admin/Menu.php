<?php
/**
 * Registers the canonical menu item, shortcuts and Plugins-screen links.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Branding;
use MammothWP\Support\Branding_Resolver;
use MammothWP\Support\Config;
use MammothWP\Support\Menu_Location;
use MammothWP\Support\Plugin_Definition;
use MammothWP\Support\Plugin_Registry;

/**
 * WordPress glue for the menu. Placement logic lives in Menu_Resolver / Menu_Location.
 *
 * @since 1.0.0
 */
final class Menu {

	/**
	 * Page hook suffixes of the registered screens (site and network).
	 *
	 * @var string[]
	 */
	private array $hooks = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Registry $registry Registry.
	 * @param callable        $location Provider: fn(): Menu_Location.
	 * @param callable        $branding Provider: fn(): Branding.
	 * @param Screen          $screen   Screen.
	 */
	public function __construct(
		private Plugin_Registry $registry,
		private $location,
		private $branding,
		private Screen $screen
	) {
	}

	/**
	 * Register `admin_menu`, `network_admin_menu` and asset hooks.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_site' ) );
		add_action( 'network_admin_menu', array( $this, 'register_network' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue' ) );
	}

	/**
	 * Site admin menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_site(): void {
		if ( ! $this->registry->has_any() ) {
			return;
		}
		$this->register_screen( false );
		$this->register_shortcuts();
		$this->register_action_links();
	}

	/**
	 * Network admin menu: only when at least one registered plugin is network-active.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_network(): void {
		if ( ! is_multisite() || ! $this->has_network_plugin() ) {
			return;
		}
		$this->register_screen( true );
		$this->register_action_links();
	}

	/**
	 * Resolved location from the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return Menu_Location
	 */
	private function current_location(): Menu_Location {
		$location = call_user_func( $this->location );
		return $location instanceof Menu_Location ? $location : new Menu_Location( 'tools', '', 'default' );
	}

	/**
	 * Resolved branding from the provider.
	 *
	 * @since 1.0.0
	 *
	 * @return Branding
	 */
	private function current_branding(): Branding {
		$branding = call_user_func( $this->branding );
		return $branding instanceof Branding ? $branding : new Branding( Branding_Resolver::neutral(), 'neutral' );
	}

	/**
	 * Whether any registered plugin is network-activated.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function has_network_plugin(): bool {
		foreach ( $this->registry->all() as $plugin ) {
			if ( is_plugin_active_for_network( plugin_basename( $plugin->file ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Register the canonical page.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $network Network admin.
	 * @return void
	 */
	private function register_screen( bool $network ): void {
		$location = $this->current_location();
		$branding = $this->current_branding();
		$cap      = Config::capability( $network );
		$page     = (string) $branding->get( 'page_title' );
		$label    = $branding->is_mammoth() && 'toplevel' !== $location->type ? __( 'Support', 'mammothwp-support' ) : (string) $branding->get( 'menu_title' );

		if ( 'toplevel' === $location->type ) {
			$this->hooks[] = (string) add_menu_page( $page, (string) $branding->get( 'menu_title' ), $cap, Config::PAGE_SLUG, array( $this->screen, 'render' ), (string) $branding->get( 'icon' ), Config::menu_position() );
			$label         = __( 'Support', 'mammothwp-support' );
		}

		$hook = add_submenu_page( $location->parent_slug( $network ), $page, $label, $cap, Config::PAGE_SLUG, array( $this->screen, 'render' ) );
		if ( false !== $hook ) {
			$this->hooks[] = $hook;
		}
	}

	/**
	 * Per-plugin submenu shortcuts: `'menu' => [ 'parent' => '...', 'title' => 'Support' ]`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_shortcuts(): void {
		$location = $this->current_location();
		foreach ( $this->registry->all() as $plugin ) {
			$parent = isset( $plugin->menu['parent'] ) && is_string( $plugin->menu['parent'] ) ? $plugin->menu['parent'] : '';
			if ( '' === $parent || $parent === $location->parent_slug() ) {
				continue;
			}
			$title = isset( $plugin->menu['title'] ) && is_string( $plugin->menu['title'] ) && '' !== $plugin->menu['title'] ? $plugin->menu['title'] : __( 'Support', 'mammothwp-support' );
			add_submenu_page( $parent, $title, $title, Config::capability(), $this->screen_url( $plugin ) );
		}
	}

	/**
	 * Add "Support" to each registered plugin's row on the Plugins screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function register_action_links(): void {
		foreach ( $this->registry->all() as $plugin ) {
			if ( ! Config::action_link_enabled( $plugin->slug ) ) {
				continue;
			}
			$basename = plugin_basename( $plugin->file );
			$callback = fn( array $links ): array => $this->with_action_link( $links, $plugin );
			add_filter( 'plugin_action_links_' . $basename, $callback );
			add_filter( 'network_admin_plugin_action_links_' . $basename, $callback );
		}
	}

	/**
	 * Append the Support link.
	 *
	 * @since 1.0.0
	 *
	 * @param string[]          $links  Existing links.
	 * @param Plugin_Definition $plugin Plugin.
	 * @return string[]
	 */
	public function with_action_link( array $links, Plugin_Definition $plugin ): array {
		$links['mmwp-support'] = sprintf( '<a href="%s">%s</a>', esc_url( $this->screen_url( $plugin ) ), esc_html__( 'Support', 'mammothwp-support' ) );
		return $links;
	}

	/**
	 * Canonical URL with a plugin preselected.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Definition $plugin Plugin.
	 * @return string
	 */
	public function screen_url( Plugin_Definition $plugin ): string {
		$location = $this->current_location();
		$path     = $location->admin_path( $plugin->slug );
		return is_network_admin() ? network_admin_url( $path ) : admin_url( $path );
	}

	/**
	 * Enqueue assets on our screens only.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function maybe_enqueue( string $hook_suffix ): void {
		if ( in_array( $hook_suffix, $this->hooks, true ) ) {
			$this->screen->enqueue();
		}
	}
}
