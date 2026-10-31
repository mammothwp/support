<?php
/**
 * Branding resolution and logo / icon guards.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use MammothWP\Support\Branding_Guard;
use MammothWP\Support\Branding_Resolver;

final class BrandingTest extends TestCase {

	private const ICON  = 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=';
	private const HOSTS = array( 'site.test' );

	/**
	 * Resolve with defaults.
	 *
	 * @param array<int,\MammothWP\Support\Plugin_Definition> $plugins     Plugins.
	 * @param bool|array<string,mixed>|null                   $whitelabel  White-label constant.
	 * @param array<string,mixed>|null                        $filtered    Filter result.
	 * @return \MammothWP\Support\Branding
	 */
	private function resolve( array $plugins, $whitelabel = null, ?array $filtered = null ) {
		return Branding_Resolver::resolve( $plugins, $whitelabel, $filtered, self::HOSTS, self::ICON );
	}

	public function test_mammoth_defaults_when_a_mammoth_plugin_is_registered(): void {
		$branding = $this->resolve( array( $this->definition( array( 'vendor' => 'Mammoth WP' ) ), $this->definition( array( 'slug' => 'b' ) ) ) );
		$this->assertSame( 'mammoth', $branding->mode );
		$this->assertSame( 'Mammoth WP', $branding->get( 'menu_title' ) );
		$this->assertSame( self::ICON, $branding->get( 'icon' ) );
		$this->assertSame( '[Mammoth WP]', $branding->get( 'subject_prefix' ) );
		$this->assertSame( 'MMWP', $branding->get( 'ref_prefix' ) );
		$this->assertTrue( $branding->get( 'show_powered_by' ) );
	}

	public function test_neutral_defaults_for_third_party_only(): void {
		$branding = $this->resolve( array( $this->definition() ) );
		$this->assertSame( 'neutral', $branding->mode );
		$this->assertSame( array( 'Support', 'dashicons-sos', '', '[Support]', false ), array( $branding->get( 'menu_title' ), $branding->get( 'icon' ), $branding->get( 'logo_url' ), $branding->get( 'subject_prefix' ), $branding->get( 'show_powered_by' ) ) );
	}

	public function test_single_vendor_branding_is_honoured(): void {
		$own      = array(
			'name'           => 'Acme Help',
			'subject_prefix' => '[Acme]',
			'ref_prefix'     => 'acme',
		);
		$branding = $this->resolve(
			array(
				$this->definition( array( 'branding' => $own ) ),
				$this->definition(
					array(
						'slug'   => 'two',
						'vendor' => 'acme ltd',
					)
				),
			)
		);
		$this->assertSame( 'vendor', $branding->mode );
		$this->assertSame( array( 'Acme Help', '[Acme]', 'ACME' ), array( $branding->get( 'name' ), $branding->get( 'subject_prefix' ), $branding->get( 'ref_prefix' ) ) );
	}

	public function test_branding_of_mixed_vendors_is_ignored(): void {
		$branding = $this->resolve(
			array(
				$this->definition( array( 'branding' => array( 'name' => 'Acme Help' ) ) ),
				$this->definition(
					array(
						'slug'   => 'other',
						'vendor' => 'Other Inc',
					)
				),
			)
		);
		$this->assertSame( 'neutral', $branding->mode );
		$this->assertSame( 'Support', $branding->get( 'name' ) );
	}

	public function test_whitelabel_true_is_fully_neutral_even_with_mammoth_plugins_and_vendor_branding(): void {
		$plugin   = $this->definition(
			array(
				'vendor'   => 'Mammoth WP',
				'branding' => array( 'name' => 'Mammoth Pro' ),
			)
		);
		$branding = $this->resolve( array( $plugin ), true );
		$this->assertSame( 'whitelabel', $branding->mode );
		$this->assertSame( array( 'Support', 'dashicons-sos', '[Support]', 'SUP', false, false ), array( $branding->get( 'name' ), $branding->get( 'icon' ), $branding->get( 'subject_prefix' ), $branding->get( 'ref_prefix' ), $branding->get( 'show_powered_by' ), $branding->get( 'show_vendor_names' ) ) );
		$this->assertStringNotContainsString( 'Mammoth', (string) wp_json_encode( $branding->to_array() ) );
	}

	public function test_whitelabel_array_overrides_win_over_vendor_branding(): void {
		$plugin   = $this->definition( array( 'branding' => array( 'name' => 'Vendor Name' ) ) );
		$branding = $this->resolve(
			array( $plugin ),
			array(
				'name'       => 'Agency Help',
				'menu_title' => 'Help Desk',
				'ref_prefix' => 'HD',
			)
		);
		$this->assertSame( array( 'Agency Help', 'Help Desk', 'HD' ), array( $branding->get( 'name' ), $branding->get( 'menu_title' ), $branding->get( 'ref_prefix' ) ) );
	}

	public function test_filter_result_wins_over_everything(): void {
		$branding = $this->resolve( array( $this->definition() ), true, array( 'name' => 'Filtered' ) );
		$this->assertSame( 'Filtered', $branding->get( 'name' ) );
		$this->assertSame( 'whitelabel', $branding->mode );
		$this->assertSame( 'filter', $this->resolve( array( $this->definition() ), null, array( 'name' => 'F' ) )->mode );
	}

	public function test_remote_logo_is_rejected_and_local_logo_kept(): void {
		$remote = $this->resolve( array( $this->definition() ), array( 'logo_url' => 'https://evil.test/logo.png' ) );
		$this->assertSame( '', $remote->get( 'logo_url' ) );

		$local = $this->resolve( array( $this->definition() ), array( 'logo_url' => 'https://site.test/wp-content/logo.png' ) );
		$this->assertSame( 'https://site.test/wp-content/logo.png', $local->get( 'logo_url' ) );

		$relative = $this->resolve( array( $this->definition() ), array( 'logo_url' => '/wp-content/logo.png' ) );
		$this->assertSame( '/wp-content/logo.png', $relative->get( 'logo_url' ) );
	}

	public function test_logo_guard_edge_cases(): void {
		foreach ( array( '//evil.test/x.png', 'javascript:alert(1)', 'data:image/png;base64,AAAA', 'https://user@site.test/x.png', 'ftp://site.test/x', "https://site.test/x\n.png", 'https://site.test.evil.test/x.png', '' ) as $url ) {
			$this->assertFalse( Branding_Guard::is_local_url( $url, self::HOSTS ), $url );
		}
		$this->assertTrue( Branding_Guard::is_local_url( 'HTTP://SITE.test/x.png', self::HOSTS ) );
	}

	public function test_invalid_icon_falls_back_to_the_dashicon(): void {
		$branding = $this->resolve( array( $this->definition() ), array( 'icon' => 'https://evil.test/i.svg' ) );
		$this->assertSame( 'dashicons-sos', $branding->get( 'icon' ) );
		$this->assertSame( 'dashicons-admin-tools', $this->resolve( array( $this->definition() ), array( 'icon' => 'dashicons-admin-tools' ) )->get( 'icon' ) );
	}
}
