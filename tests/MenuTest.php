<?php
/**
 * Menu placement, site overrides, shortcuts, action links.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MammothWP\Support\Admin\Menu;
use MammothWP\Support\Admin\Screen;
use MammothWP\Support\Branding;
use MammothWP\Support\Branding_Resolver;
use MammothWP\Support\Menu_Location;
use MammothWP\Support\Menu_Resolver;
use MammothWP\Support\Plugin_Registry;

final class MenuTest extends TestCase {

	public function test_default_is_toplevel_when_a_mammoth_plugin_is_registered(): void {
		$location = Menu_Resolver::resolve( true, null, null );
		$this->assertSame( array( 'toplevel', 'default' ), array( $location->type, $location->source ) );
	}

	public function test_default_is_tools_for_third_party_only_sites(): void {
		$location = Menu_Resolver::resolve( false, null, null );
		$this->assertSame( array( 'tools', 'default' ), array( $location->type, $location->source ) );
		$this->assertSame( 'tools.php', $location->parent_slug() );
		$this->assertSame( 'tools.php?page=mmwp-support&plugin=acme', $location->admin_path( 'acme' ) );
	}

	public function test_constant_then_filter_override_with_filter_winning(): void {
		$by_constant = Menu_Resolver::resolve( true, 'settings', null );
		$this->assertSame( array( 'settings', 'constant' ), array( $by_constant->type, $by_constant->source ) );
		$this->assertSame( 'options-general.php', $by_constant->parent_slug() );

		$by_filter = Menu_Resolver::resolve( true, 'settings', 'parent:woocommerce' );
		$this->assertSame( array( 'parent', 'filter', 'woocommerce' ), array( $by_filter->type, $by_filter->source, $by_filter->parent_slug() ) );
		$this->assertSame( 'parent:woocommerce', $by_filter->to_array()['location'] );
	}

	public function test_invalid_values_fall_back_to_the_default(): void {
		$this->assertSame( 'default', Menu_Resolver::resolve( false, 'bogus', 'parent:' )->source );
	}

	public function test_hidden_mode_has_no_menu_parent_and_admin_php_url(): void {
		$location = Menu_Resolver::resolve( false, 'hidden', null );
		$this->assertSame( 'options.php', $location->parent_slug() );
		$this->assertSame( 'admin.php?page=mmwp-support', $location->admin_path() );
	}

	public function test_parent_php_menu_gets_page_as_query_argument(): void {
		$location = new Menu_Location( 'parent', 'edit.php?post_type=product', 'filter' );
		$this->assertSame( 'edit.php?post_type=product&page=mmwp-support&plugin=x', $location->admin_path( 'x' ) );
	}

	/**
	 * Menu under test.
	 *
	 * @param Plugin_Registry $registry Registry.
	 * @param Menu_Location   $location Location.
	 * @return Menu
	 */
	private function menu( Plugin_Registry $registry, Menu_Location $location ): Menu {
		$branding = new Branding( Branding_Resolver::neutral(), 'neutral' );
		$screen   = ( new \ReflectionClass( Screen::class ) )->newInstanceWithoutConstructor();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'is_network_admin' )->justReturn( false );
		Functions\when( 'admin_url' )->alias( static fn( $path = '' ) => 'https://site.test/wp-admin/' . $path );
		Functions\when( 'plugin_basename' )->alias( static fn( $file ) => basename( dirname( $file ) ) . '/' . basename( $file ) );
		return new Menu( $registry, static fn() => $location, static fn() => $branding, $screen );
	}

	public function test_per_plugin_shortcut_links_to_the_canonical_screen_with_the_plugin_preselected(): void {
		$registry = new Plugin_Registry();
		$registry->add(
			$this->definition(
				array(
					'menu' => array(
						'parent' => 'acme-settings',
						'title'  => 'Help',
					),
				)
			)
		);
		$registry->add( $this->definition( array( 'slug' => 'other' ) ) );

		Functions\expect( 'add_submenu_page' )->with( 'tools.php', 'Support', 'Support', 'manage_options', 'mmwp-support', \Mockery::any() )->once()->andReturn( 'tools_page_mmwp-support' );
		Functions\expect( 'add_submenu_page' )->with( 'acme-settings', 'Help', 'Help', 'manage_options', 'https://site.test/wp-admin/tools.php?page=mmwp-support&plugin=acme' )->once()->andReturn( 'x' );

		$this->menu( $registry, new Menu_Location( 'tools', '', 'default' ) )->register_site();
		$this->assertNotFalse( has_filter( 'plugin_action_links_acme/acme.php' ) );
	}

	public function test_toplevel_registers_the_menu_page_with_branding_and_a_support_submenu(): void {
		$registry = new Plugin_Registry();
		$registry->add( $this->definition() );

		Functions\expect( 'add_menu_page' )->once()->with( 'Support', 'Support', 'manage_options', 'mmwp-support', \Mockery::any(), 'dashicons-sos', 80 )->andReturn( 'toplevel_page_mmwp-support' );
		Functions\expect( 'add_submenu_page' )->once()->with( 'mmwp-support', 'Support', 'Support', 'manage_options', 'mmwp-support', \Mockery::any() )->andReturn( 'toplevel_page_mmwp-support' );
		$this->menu( $registry, new Menu_Location( 'toplevel', '', 'default' ) )->register_site();
		$this->addToAssertionCount( 1 );
	}

	public function test_hidden_mode_registers_under_options_php(): void {
		$registry = new Plugin_Registry();
		$registry->add( $this->definition() );
		Functions\expect( 'add_submenu_page' )->once()->with( 'options.php', \Mockery::any(), \Mockery::any(), 'manage_options', 'mmwp-support', \Mockery::any() )->andReturn( 'admin_page_mmwp-support' );
		$this->menu( $registry, new Menu_Location( 'hidden', '', 'constant' ) )->register_site();
		$this->addToAssertionCount( 1 );
	}

	public function test_nothing_is_registered_without_plugins(): void {
		Functions\expect( 'add_menu_page' )->never();
		Functions\expect( 'add_submenu_page' )->never();
		$this->menu( new Plugin_Registry(), new Menu_Location( 'tools', '', 'default' ) )->register_site();
		$this->addToAssertionCount( 1 );
	}

	public function test_action_link_points_at_the_screen_with_the_plugin_and_can_be_filtered_off(): void {
		$registry = new Plugin_Registry();
		$plugin   = $this->definition();
		$registry->add( $plugin );
		$menu  = $this->menu( $registry, new Menu_Location( 'tools', '', 'default' ) );
		$links = $menu->with_action_link( array(), $plugin );
		$this->assertStringContainsString( 'tools.php?page=mmwp-support&plugin=acme', $links['mmwp-support'] );

		Functions\when( 'add_submenu_page' )->justReturn( 'x' );
		Filters\expectApplied( 'mmwp_support_action_link' )->andReturn( false );
		$menu->register_site();
		$this->assertFalse( has_filter( 'plugin_action_links_acme/acme.php' ) );
	}
}
