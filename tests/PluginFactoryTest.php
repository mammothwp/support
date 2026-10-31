<?php
/**
 * Registration: minimal config, header fallbacks, JSON config, email precedence.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MammothWP\Support\Json_Config;
use MammothWP\Support\Plugin_Factory;
use MammothWP\Support\Site_Settings;

final class PluginFactoryTest extends TestCase {

	/**
	 * Factory with fake constants.
	 *
	 * @param array<string,mixed> $constants Constants.
	 * @return Plugin_Factory
	 */
	private function factory( array $constants = array() ): Plugin_Factory {
		return new Plugin_Factory( new Site_Settings( static fn( string $name ) => $constants[ $name ] ?? null ) );
	}

	public function test_minimal_config_uses_header_and_folder_fallbacks(): void {
		$plugin = $this->factory()->create( $this->plugin_file(), array( 'email' => 'help@acme.test' ) );
		$this->assertSame( 'acme-widgets', $plugin->slug );
		$this->assertSame( 'Acme Widgets', $plugin->name );
		$this->assertSame( '2.3.4', $plugin->version );
		$this->assertSame( 'Acme Ltd', $plugin->vendor );
		$this->assertSame( 'help@acme.test', $plugin->email );
		$this->assertTrue( $plugin->has_email() );
		$this->assertFalse( $plugin->is_mammoth() );
	}

	public function test_array_values_override_headers(): void {
		$plugin = $this->factory()->create(
			$this->plugin_file(),
			array(
				'email'   => 'help@acme.test',
				'name'    => 'Custom',
				'slug'    => 'custom-slug',
				'version' => '9.0',
				'vendor'  => 'Mammoth WP',
			)
		);
		$this->assertSame( array( 'custom-slug', 'Custom', '9.0' ), array( $plugin->slug, $plugin->name, $plugin->version ) );
		$this->assertTrue( $plugin->is_mammoth() );
	}

	public function test_json_config_is_used_and_array_keys_override_it(): void {
		$json = '{"email":"json@acme.test","docs_url":"https://acme.test/docs","vendor":"From JSON"}';
		$file = $this->plugin_file( 'Acme Widgets', '2.3.4', 'Acme Ltd', array( 'mammothwp-support.json' => $json ) );

		$plugin = $this->factory()->create( $file, array() );
		$this->assertSame( 'json@acme.test', $plugin->email );
		$this->assertSame( 'json', $plugin->email_source );
		$this->assertSame( 'From JSON', $plugin->vendor );
		$this->assertSame( 'https://acme.test/docs', $plugin->docs_url );

		$plugin = $this->factory()->create( $file, array( 'email' => 'array@acme.test' ) );
		$this->assertSame( 'array@acme.test', $plugin->email );
		$this->assertSame( 'array', $plugin->email_source );
	}

	public function test_json_validation_reports_bad_types_and_unknown_keys(): void {
		$result = Json_Config::parse( '{"email":5,"menu":"x","nope":1,"name":"Ok"}' );
		$this->assertSame( array( 'name' => 'Ok' ), $result['data'] );
		$this->assertCount( 3, $result['errors'] );
		$this->assertNotEmpty( Json_Config::parse( '[1,2]' )['errors'] );
		$this->assertNotEmpty( Json_Config::parse( 'not json' )['errors'] );
	}

	public function test_email_precedence_filter_over_constant_over_array_over_json(): void {
		$file      = $this->plugin_file( 'Acme Widgets', '1', 'A', array( 'mammothwp-support.json' => '{"email":"json@acme.test"}' ) );
		$constants = array( 'MMWP_SUPPORT_EMAIL_ACME_WIDGETS' => 'const@acme.test' );

		$plugin = $this->factory()->create( $file, array( 'email' => 'array@acme.test' ) );
		$this->assertSame( 'array@acme.test', $plugin->email );

		$plugin = $this->factory( $constants )->create( $file, array( 'email' => 'array@acme.test' ) );
		$this->assertSame( array( 'const@acme.test', 'constant' ), array( $plugin->email, $plugin->email_source ) );

		Filters\expectApplied( 'mmwp_support_email' )->andReturn( 'filter@acme.test' );
		$plugin = $this->factory( $constants )->create( $file, array( 'email' => 'array@acme.test' ) );
		$this->assertSame( array( 'filter@acme.test', 'filter' ), array( $plugin->email, $plugin->email_source ) );
	}

	public function test_email_constant_name_is_upper_underscored_slug(): void {
		$this->assertSame( 'MMWP_SUPPORT_EMAIL_MY_PLUGIN_2', Site_Settings::email_constant( 'my-plugin.2' ) );
	}

	public function test_invalid_or_missing_email_disables_sending_and_warns(): void {
		Functions\expect( '_doing_it_wrong' )->twice();
		$file = $this->plugin_file();

		$missing = $this->factory()->create( $file, array() );
		$this->assertFalse( $missing->has_email() );
		$this->assertSame( 'none', $missing->email_source );

		$invalid = $this->factory()->create( $file, array( 'email' => 'not-an-email' ) );
		$this->assertFalse( $invalid->has_email(), 'No fallback to any default address.' );
		$this->assertSame( '', $invalid->email );
	}

	public function test_no_mammoth_address_exists_in_the_code(): void {
		$hits = array();
		$it   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__ ) . '/src' ) );
		foreach ( $it as $file ) {
			if ( $file->isFile() && false !== strpos( (string) file_get_contents( $file->getPathname() ), 'support@mammothwp.com' ) ) {
				$hits[] = $file->getPathname();
			}
		}
		$this->assertSame( array(), $hits );
	}
}
