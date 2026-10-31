<?php
/**
 * Operations (wp-kit): surfaces, permissions, CLI dry-run, and the too-old degrade path.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use MammothWP\Support\Kit\Kit_Integration;
use MammothWP\Support\Kit\Operations;
use MammothWP\Support\Kit\Support_Resource;
use MammothWP\Support\Redactor;
use MammothWP\Support\Report\Report_Service;
use MammothWP\WpKit\Context;

final class KitTest extends ServiceTestCase {

	/**
	 * Resource under test.
	 *
	 * @return Support_Resource
	 */
	private function resource(): Support_Resource {
		$reports    = new Report_Service(
			static fn( array $lines ): array => array(
				'site' => array(
					'label' => 'Site',
					'data'  => array( 'wp' => '6.8' ),
				),
			),
			new Redactor()
		);
		$operations = new Operations( $reports, $this->registry, $this->service(), static fn() => true );
		return new Support_Resource( $operations );
	}

	public function test_version_guard(): void {
		$this->assertFalse( Kit_Integration::is_supported( '' ) );
		$this->assertFalse( Kit_Integration::is_supported( '0.4.0' ) );
		$this->assertTrue( Kit_Integration::is_supported( '1.0.0' ) );
		$this->assertTrue( Kit_Integration::is_supported( '1.3.2' ) );
	}

	public function test_too_old_or_missing_wp_kit_skips_registration_and_the_screen_still_sends(): void {
		$registered = 0;
		$callback   = static function () use ( &$registered ): void {
			++$registered;
		};
		$this->assertFalse( Kit_Integration::register_if_supported( '', $callback ), 'Missing.' );
		$this->assertFalse( Kit_Integration::register_if_supported( '0.4.0', $callback ), 'Too old.' );
		$this->assertSame( 0, $registered );
		$this->assertTrue( Kit_Integration::register_if_supported( '1.0.0', $callback ) );
		$this->assertSame( 1, $registered );

		// The admin-post path uses the very same service and keeps working without wp-kit.
		$this->assertTrue( $this->service()->send( $this->request() )['sent'] );
	}

	public function test_loaded_version_is_empty_when_wp_kit_is_not_booted(): void {
		$this->assertSame( '', Kit_Integration::loaded_version() );
	}

	public function test_operations_are_exposed_on_rest_cli_and_abilities_with_the_documented_names(): void {
		$resource = $this->resource();
		$this->assertSame( 'mmwp/v1', $resource->rest_namespace() );
		$this->assertSame( '/support', $resource->rest_base() );
		$this->assertSame( 'mmwp support', $resource->cli_base() );
		$this->assertSame( 'mmwp', $resource->ability_category() );

		$ops = $resource->ops();
		$this->assertSame( array( 'report', 'plugins', 'send' ), array_keys( $ops ) );
		$this->assertSame( array( 'support-report', 'support-plugins', 'support-send' ), array_map( static fn( $op ) => $op->ability_slug(), array_values( $ops ) ) );
		$this->assertSame( array( 'report', 'plugins', 'send' ), array_map( static fn( $op ) => $op->cli_binding()['name'], array_values( $ops ) ) );
		$methods = array_map( static fn( $op ) => $op->rest_bindings()[0]['method'], array_values( $ops ) );
		$this->assertSame( array( 'GET', 'GET', 'POST' ), $methods );
		$this->assertSame( array( 'plugin', 'subject', 'message', 'reply_to', 'name', 'include_report', 'include_log', 'dry_run' ), array_map( static fn( $f ) => $f->name(), $ops['send']->fields() ) );
		$this->assertSame( array( 'format' ), array_map( static fn( $f ) => $f->name(), $ops['report']->fields() ) );
	}

	public function test_every_operation_requires_manage_options_for_authenticated_surfaces(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$denied = Functions\when( 'current_user_can' )->alias( static fn( $cap ) => false );
		foreach ( $this->resource()->ops() as $name => $op ) {
			foreach ( array( Context::ability( array() ), Context::make( Context::REST ) ) as $context ) {
				$result = $op->authorize( array(), $context );
				$this->assertInstanceOf( \WP_Error::class, $result, $name );
				$this->assertSame( 'mmwp_forbidden', $result->get_error_code() );
			}
		}
		$asked = array();
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap ) use ( &$asked ) {
				$asked[] = $cap;
				return true;
			}
		);
		foreach ( $this->resource()->ops() as $op ) {
			$this->assertTrue( $op->authorize( array(), Context::ability( array() ) ) );
		}
		$this->assertSame( array( 'manage_options' ), array_values( array_unique( $asked ) ) );
		unset( $denied );
	}

	public function test_anonymous_requests_are_rejected_with_401(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'current_user_can' )->justReturn( false );
		$result = $this->resource()->ops()['send']->authorize( array(), Context::make( Context::REST ) );
		$this->assertSame( 401, $result->get_error_data()['status'] );
	}

	public function test_capability_is_filterable(): void {
		Filters\expectApplied( 'mmwp_support_capability' )->andReturn( 'edit_pages' );
		$this->assertSame( 'edit_pages', $this->resource()->capability() );
	}

	public function test_ability_send_requires_confirm(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		$send   = $this->resource()->ops()['send'];
		$input  = array(
			'plugin'   => 'acme',
			'message'  => 'hi',
			'reply_to' => 'o@example.org',
		);
		$result = $send->run( $input, Context::ability( $input ) );
		$this->assertSame( 'mmwp_confirm_required', $result->get_error_code() );
		$this->assertSame( array(), $this->sent );

		$input['confirm'] = true;
		$result           = $send->run( $input, Context::ability( $input ) );
		$this->assertTrue( $result['sent'] );
		$this->assertCount( 1, $this->sent );
	}

	public function test_cli_dry_run_prints_the_email_instead_of_sending_it(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		$send   = $this->resource()->ops()['send'];
		$assoc  = array(
			'plugin'         => 'acme',
			'message'        => 'hello',
			'reply_to'       => 'o@example.org',
			'include_report' => true,
			'dry_run'        => true,
		);
		$result = $send->run( $assoc, Context::cli( array(), $assoc ) );
		$this->assertIsArray( $result );
		$this->assertTrue( $result['dry_run'] );
		$this->assertSame( 'help@acme.test', $result['to'] );
		$this->assertStringContainsString( '== Site ==', $result['body'] );
		$this->assertSame( array(), $this->sent, 'Nothing is sent on --dry-run.' );
	}

	public function test_cli_flags_are_the_dashed_field_names(): void {
		$fields = $this->resource()->ops()['send']->fields();
		$input  = \MammothWP\WpKit\Field_Map::from_cli(
			$fields,
			array(),
			array(
				'plugin'         => 'acme',
				'dry-run'        => true,
				'include-report' => true,
				'reply-to'       => 'a@b.co',
			)
		);
		$this->assertSame(
			array(
				'plugin'         => 'acme',
				'reply_to'       => 'a@b.co',
				'include_report' => true,
				'dry_run'        => true,
			),
			$input
		);
	}

	public function test_send_defaults_never_include_the_report_or_the_log(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		$send   = $this->resource()->ops()['send'];
		$assoc  = array(
			'plugin'   => 'acme',
			'message'  => 'hello',
			'reply_to' => 'o@example.org',
			'dry_run'  => true,
		);
		$result = $send->run( $assoc, Context::cli( array(), $assoc ) );
		$this->assertSame( 0, $this->report_calls );
		$this->assertStringNotContainsString( '== Site ==', $result['body'] );
	}

	public function test_plugins_operation_lists_plugins_and_hides_vendors_when_asked(): void {
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		$reports    = new Report_Service( static fn(): array => array(), new Redactor() );
		$operations = new Operations( $reports, $this->registry, $this->service(), static fn() => false );
		$rows       = $operations->support_plugins();
		$this->assertSame( 'acme', $rows[0]['slug'] );
		$this->assertTrue( $rows[0]['email_configured'] );
		$this->assertSame( '', $rows[0]['vendor'] );
	}

	public function test_report_operation_formats(): void {
		$operations = $this->resource()->service();
		$this->assertStringContainsString( 'wp: 6.8', $operations->support_report( 'text' ) );
		$this->assertSame( array( 'site' => array( 'wp' => '6.8' ) ), $operations->support_report( 'json' ) );
	}
}
