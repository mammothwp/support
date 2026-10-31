<?php
/**
 * Report sections, builder, formatter and consent plumbing.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MammothWP\Support\Redactor;
use MammothWP\Support\Report\Debug_Log_Reader;
use MammothWP\Support\Report\Report_Builder;
use MammothWP\Support\Report\Report_Formatter;
use MammothWP\Support\Report\Section_Interface;
use MammothWP\Support\Report\Sections\Plugins_Section;
use MammothWP\Support\Report\Sections\Server_Section;
use MammothWP\Support\Report\Sections\Site_Section;

final class ReportTest extends TestCase {

	/**
	 * A fake section.
	 *
	 * @param string              $key  Key.
	 * @param array<string,mixed> $data Data.
	 * @return Section_Interface
	 */
	private function section( string $key, array $data ): Section_Interface {
		return new class( $key, $data ) implements Section_Interface {
			public function __construct( private string $key, private array $data ) {}
			public function key(): string {
				return $this->key;
			}
			public function label(): string {
				return strtoupper( $this->key );
			}
			public function collect(): array {
				return $this->data;
			}
		};
	}

	public function test_site_section_reduces_urls_and_reports_flags_without_paths(): void {
		Functions\when( 'home_url' )->justReturn( 'https://user:pw@shop.example.org:8443/blog?x=1' );
		Functions\when( 'site_url' )->justReturn( 'https://shop.example.org/wp' );
		Functions\when( 'get_bloginfo' )->justReturn( '6.8.1' );
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_blog_count' )->justReturn( 4 );
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
		Functions\when( 'wp_timezone_string' )->justReturn( 'Europe/Athens' );
		Functions\when( 'get_option' )->justReturn( '/%postname%/' );
		Functions\when( 'is_ssl' )->justReturn( true );
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
		Functions\when( 'wp_using_ext_object_cache' )->justReturn( false );

		$data = ( new Site_Section() )->collect();
		$this->assertSame( 'https://shop.example.org', $data['home_url'] );
		$this->assertSame( 'https://shop.example.org', $data['site_url'] );
		$this->assertSame( array( '6.8.1', 'yes', '4', 'pretty', 'yes', 'no' ), array( $data['wp_version'], $data['multisite'], $data['multisite_sites'], $data['permalinks'], $data['https'], $data['object_cache'] ) );
		$this->assertArrayHasKey( 'DISABLE_WP_CRON', $data );
		$this->assertArrayHasKey( 'WP_MEMORY_LIMIT', $data );
		$this->assertStringNotContainsString( ABSPATH, (string) json_encode( $data ) );
	}

	public function test_server_section_lists_extensions_and_limits(): void {
		$data = ( new Server_Section() )->collect();
		$this->assertSame( PHP_VERSION, $data['php_version'] );
		$this->assertSame( array( 'curl', 'mbstring', 'intl', 'imagick', 'gd', 'zip', 'openssl', 'sodium' ), array_keys( $data['extensions'] ) );
		foreach ( array( 'memory_limit', 'max_execution_time', 'upload_max_filesize', 'post_max_size', 'database', 'server_software', 'os_family' ) as $key ) {
			$this->assertArrayHasKey( $key, $data );
		}
		$this->assertSame( 'unknown', $data['database'], 'No $wpdb here, and never a host or credentials.' );
	}

	public function test_plugins_section_helpers_sort_and_describe(): void {
		$all = array(
			'b/b.php' => array(
				'Name'    => 'Beta',
				'Version' => '2',
			),
			'a/a.php' => array(
				'Name'    => 'Alpha',
				'Version' => '1.1',
			),
		);
		$this->assertSame( array( 'Alpha 1.1', 'Beta 2' ), Plugins_Section::describe( $all, array( 'b/b.php', 'a/a.php', 'gone/gone.php' ) ) );
		$this->assertSame(
			array( 'advanced-cache.php', 'Zed' ),
			Plugins_Section::names(
				array(
					'z.php'              => array( 'Name' => 'Zed' ),
					'advanced-cache.php' => array(),
				)
			)
		);
	}

	public function test_builder_is_deterministic_and_redacts_everything_including_plugin_sections(): void {
		$redactor = new Redactor( array( '/srv/www/' => '[ABSPATH]/' ) );
		$core     = array(
			$this->section(
				'site',
				array(
					'path' => '/srv/www/wp-content',
					'mail' => 'me@example.org',
				)
			),
		);
		$custom   = $this->section( 'acme', array( 'license' => 'api_key=SECRETVALUE1' ) );
		Filters\expectApplied( 'mmwp_support_report_sections' )->andReturnUsing(
			static function ( $sections ) use ( $custom ) {
				$sections[] = $custom;
				$sections[] = 'not a section';
				return $sections;
			}
		);

		$builder = new Report_Builder( $core, $redactor );
		$report  = $builder->build();
		$this->assertSame( array( 'site', 'acme' ), array_keys( $report ) );
		$text = Report_Formatter::to_text( $report );
		$json = Report_Formatter::to_json( $report );
		foreach ( array( $text, $json ) as $output ) {
			$this->assertStringNotContainsString( '/srv/www', $output );
			$this->assertStringNotContainsString( 'me@example.org', $output );
			$this->assertStringNotContainsString( 'SECRETVALUE1', $output );
		}
		$this->assertSame( $text, Report_Formatter::to_text( $builder->build() ), 'Same input, same output.' );
	}

	public function test_formatter_writes_key_value_lines_in_sections(): void {
		$report = array(
			'site' => array(
				'label' => 'Site',
				'data'  => array(
					'wp'   => '6.8',
					'flag' => true,
					'ext'  => array( 'curl' => 'yes' ),
					'list' => array( 'A 1', 'B 2' ),
					'none' => array(),
				),
			),
		);
		$this->assertSame( "== Site ==\nwp: 6.8\nflag: yes\next:\n  curl: yes\nlist:\n  - A 1\n  - B 2\nnone: -\n", Report_Formatter::to_text( $report ) );
		$this->assertSame( '6.8', json_decode( Report_Formatter::to_json( $report ), true )['site']['wp'] );
	}

	public function test_debug_log_tail_returns_last_lines_and_is_empty_for_missing_files(): void {
		$file = $this->temp_dir() . '/debug.log';
		file_put_contents( $file, implode( "\n", array_map( static fn( $i ) => "line $i", range( 1, 120 ) ) ) . "\n" );
		$tail = Debug_Log_Reader::tail( $file, 50 );
		$this->assertCount( 50, $tail );
		$this->assertSame( 'line 120', end( $tail ) );
		$this->assertSame( 'line 71', $tail[0] );
		$this->assertSame( array(), Debug_Log_Reader::tail( $file . '.missing', 50 ) );
	}

	/**
	 * Stub every WP function Site_Section::collect() calls.
	 *
	 * @param array<string,mixed> $overrides Function name => return value.
	 */
	private function stub_site( array $overrides = array() ): void {
		$defaults = array(
			'home_url'                  => 'https://shop.example.org',
			'site_url'                  => 'https://shop.example.org',
			'get_bloginfo'              => '7.1',
			'is_multisite'              => false,
			'get_locale'                => 'en_US',
			'wp_timezone_string'        => 'UTC',
			'get_option'                => '',
			'is_ssl'                    => true,
			'wp_get_environment_type'   => 'local',
			'wp_using_ext_object_cache' => false,
		);
		foreach ( array_merge( $defaults, $overrides ) as $name => $value ) {
			Functions\when( $name )->justReturn( $value );
		}
	}

	public function test_site_section_survives_null_object_cache_flag(): void {
		// wp_using_ext_object_cache() returns null before the cache drop-in decides (Site_Section line 61 fatal).
		$this->stub_site( array( 'wp_using_ext_object_cache' => null ) );
		$data = ( new Site_Section() )->collect();
		$this->assertSame( 'no', $data['object_cache'] );
	}

	public function test_site_section_marks_a_throwing_field_unavailable(): void {
		$this->stub_site();
		Functions\when( 'wp_timezone_string' )->alias(
			static function () {
				throw new \RuntimeException( 'boom' );
			}
		);
		$data = ( new Site_Section() )->collect();
		$this->assertSame( 'unavailable', $data['timezone'] );
		$this->assertSame( 'en_US', $data['locale'] );
	}

	/**
	 * @dataProvider yes_no_provider
	 *
	 * @param mixed  $value    Input.
	 * @param string $expected Expected output.
	 */
	public function test_yes_no_normalises_any_value( $value, string $expected ): void {
		$this->assertSame( $expected, Site_Section::yes_no( $value ) );
	}

	/**
	 * Inputs for yes_no.
	 *
	 * @return array<string,array{0:mixed,1:string}>
	 */
	public function yes_no_provider(): array {
		return array(
			'null'         => array( null, 'no' ),
			'false'        => array( false, 'no' ),
			'string 0'     => array( '0', 'no' ),
			'empty string' => array( '', 'no' ),
			'string 1'     => array( '1', 'yes' ),
			'true'         => array( true, 'yes' ),
			'int 1'        => array( 1, 'yes' ),
			'garbage'      => array( 'maybe', 'no' ),
		);
	}

	public function test_builder_survives_a_throwing_section_and_marks_it_unavailable(): void {
		$throwing = new class() implements Section_Interface {
			public function key(): string {
				return 'bad';
			}
			public function label(): string {
				return 'Bad';
			}
			public function collect(): array {
				throw new \TypeError( 'strict types blew up' );
			}
		};
		$builder  = new Report_Builder( array( $this->section( 'a', array( 'x' => '1' ) ), $throwing, $this->section( 'c', array( 'y' => '2' ) ) ), new Redactor( array() ) );
		Filters\expectApplied( 'mmwp_support_report_sections' )->andReturnFirstArg();
		$report = $builder->build();
		$this->assertSame( array( 'a', 'bad', 'c' ), array_keys( $report ) );
		$this->assertSame( array( 'status' => 'unavailable' ), $report['bad']['data'] );
		$this->assertSame( array( 'y' => '2' ), $report['c']['data'] );
	}
}
