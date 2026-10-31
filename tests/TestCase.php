<?php
/**
 * Base test case: Brain Monkey plus stubs for the WordPress helpers the package uses.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

abstract class TestCase extends PolyfillTestCase {

	/**
	 * Set up Brain Monkey and common stubs.
	 */
	protected function set_up() {
		parent::set_up();
		Monkey\setUp();

		$passthrough = array( '__', 'esc_html__', 'esc_html', 'esc_attr', 'esc_url_raw', 'wp_unslash', 'sanitize_email' );
		foreach ( $passthrough as $name ) {
			Functions\when( $name )->returnArg();
		}
		Functions\when( 'wp_strip_all_tags' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		Functions\when( 'sanitize_text_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( $s ) => trim( strip_tags( (string) $s ) ) );
		Functions\when( 'sanitize_title' )->alias( static fn( $s ) => strtolower( trim( (string) preg_replace( '/[^A-Za-z0-9-]+/', '-', (string) $s ), '-' ) ) );
		Functions\when( 'is_email' )->alias( static fn( $s ) => false !== filter_var( $s, FILTER_VALIDATE_EMAIL ) ? $s : false );
		Functions\when( 'wp_parse_url' )->alias( static fn( $url, $component = -1 ) => parse_url( (string) $url, $component ) );
		Functions\when( 'wp_json_encode' )->alias( static fn( $data, $flags = 0 ) => json_encode( $data, $flags ) );
		Functions\when( 'wp_get_current_user' )->justReturn(
			(object) array(
				'user_email'   => 'owner@example.org',
				'display_name' => 'Owner',
			)
		);
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tear_down() {
		Monkey\tearDown();
		parent::tear_down();
	}

	/**
	 * Create a temp directory.
	 *
	 * @return string
	 */
	protected function temp_dir(): string {
		$dir = sys_get_temp_dir() . '/mmwp-test-' . bin2hex( random_bytes( 4 ) );
		mkdir( $dir, 0777, true );
		return $dir;
	}

	/**
	 * Create a plugin main file with headers.
	 *
	 * @param string               $name    Plugin Name header.
	 * @param string               $version Version header.
	 * @param string               $author  Author header.
	 * @param array<string,string> $extra   Extra files (name => contents) in the plugin dir.
	 * @return string Path to the main file.
	 */
	protected function plugin_file( string $name = 'Acme Widgets', string $version = '2.3.4', string $author = 'Acme Ltd', array $extra = array() ): string {
		$dir = $this->temp_dir() . '/acme-widgets';
		mkdir( $dir, 0777, true );
		file_put_contents( $dir . '/acme-widgets.php', "<?php\n/*\nPlugin Name: $name\nVersion: $version\nAuthor: $author\n*/\n" );
		foreach ( $extra as $file => $contents ) {
			file_put_contents( $dir . '/' . $file, $contents );
		}
		Functions\when( 'get_file_data' )->alias(
			static function ( $file ) {
				$text = (string) file_get_contents( $file );
				$out  = array();
				foreach ( array(
					'Name'    => 'Plugin Name',
					'Version' => 'Version',
					'Author'  => 'Author',
				) as $key => $header ) {
					$out[ $key ] = preg_match( '/^' . $header . ':\s*(.*)$/m', $text, $m ) ? trim( $m[1] ) : '';
				}
				return $out;
			}
		);
		return $dir . '/acme-widgets.php';
	}

	/**
	 * Build a Plugin_Definition quickly.
	 *
	 * @param array<string,mixed> $values Overrides.
	 * @return \MammothWP\Support\Plugin_Definition
	 */
	protected function definition( array $values = array() ): \MammothWP\Support\Plugin_Definition {
		$v = array_merge(
			array(
				'file'     => '/p/acme/acme.php',
				'slug'     => 'acme',
				'name'     => 'Acme',
				'version'  => '1.0.0',
				'vendor'   => 'Acme Ltd',
				'email'    => 'help@acme.test',
				'source'   => 'array',
				'docs_url' => '',
				'menu'     => array(),
				'branding' => array(),
			),
			$values
		);
		return new \MammothWP\Support\Plugin_Definition( $v['file'], $v['slug'], $v['name'], $v['version'], $v['vendor'], $v['email'], $v['source'], $v['docs_url'], $v['menu'], $v['branding'] );
	}
}
