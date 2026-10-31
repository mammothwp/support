<?php
/**
 * Menu icon data URI.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use MammothWP\Support\Branding_Guard;
use MammothWP\Support\Menu_Icon;

final class MenuIconTest extends TestCase {

	public function test_shipped_icon_is_a_valid_single_path_20px_svg(): void {
		$file = dirname( __DIR__ ) . '/assets/mmwp-icon.svg';
		$uri  = Menu_Icon::from_file( $file );
		$this->assertTrue( Branding_Guard::is_valid_icon( $uri ) );
		$this->assertStringStartsWith( 'data:image/svg+xml;base64,', $uri );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- decoding the icon under test.
		$svg = (string) base64_decode( substr( $uri, strlen( 'data:image/svg+xml;base64,' ) ), true );
		$this->assertStringContainsString( 'viewBox="0 0 20 20"', $svg );
		$this->assertSame( 1, substr_count( $svg, '<path' ) );
		$this->assertStringContainsString( 'fill="black"', $svg );
		$this->assertDoesNotMatchRegularExpression( '/stroke|gradient|<image|width=|height=/i', $svg );
	}

	public function test_missing_file_or_non_svg_falls_back_to_a_dashicon(): void {
		$this->assertSame( 'dashicons-sos', Menu_Icon::from_file( '/nonexistent/icon.svg' ) );
		$this->assertSame( 'dashicons-sos', Menu_Icon::from_svg( 'not an svg' ) );
	}
}
