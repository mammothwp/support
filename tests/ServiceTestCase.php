<?php
/**
 * Base for tests that need a wired Ticket_Service with in-memory fakes.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Functions;
use MammothWP\Support\Branding;
use MammothWP\Support\Branding_Resolver;
use MammothWP\Support\Mail\Attachment_Writer;
use MammothWP\Support\Mail\Message;
use MammothWP\Support\Mail\Rate_Limiter;
use MammothWP\Support\Mail\Send_Log;
use MammothWP\Support\Mail\Ticket_Request;
use MammothWP\Support\Mail\Ticket_Service;
use MammothWP\Support\Mail\Transport_Interface;
use MammothWP\Support\Plugin_Registry;

abstract class ServiceTestCase extends TestCase {

	protected const NOW = 1714953600; // 2024-05-06 00:00:00 UTC.

	/** @var array<string,mixed> */
	protected array $store = array();

	/** @var Message[] */
	protected array $sent = array();

	/** @var array<int,array{name:string,exists:bool,contents:string}> */
	protected array $attachments_seen = array();

	protected int $report_calls = 0;

	protected bool $transport_result = true;

	protected Plugin_Registry $registry;

	protected Branding $branding;

	protected int $now = self::NOW;

	protected function set_up() {
		parent::set_up();
		$this->store            = array();
		$this->sent             = array();
		$this->attachments_seen = array();
		$this->report_calls     = 0;
		$this->transport_result = true;
		$this->now              = self::NOW;
		$this->registry         = new Plugin_Registry();
		$this->registry->add( $this->definition() );
		$this->branding = new Branding( Branding_Resolver::neutral(), 'neutral' );

		Functions\when( 'get_transient' )->alias( fn( $key ) => $this->store[ 't_' . $key ] ?? false );
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value ) {
				$this->store[ 't_' . $key ] = $value;
				return true;
			}
		);
		Functions\when( 'get_option' )->alias( fn( $key, $default = false ) => $this->store[ 'o_' . $key ] ?? $default );
		Functions\when( 'update_option' )->alias(
			function ( $key, $value ) {
				$this->store[ 'o_' . $key ] = $value;
				return true;
			}
		);
		Functions\when( 'home_url' )->justReturn( 'https://Shop.Example.org/blog' );
		Functions\when( 'get_temp_dir' )->justReturn( sys_get_temp_dir() . '/' );
		Functions\when( 'trailingslashit' )->alias( fn( $s ) => rtrim( $s, '/' ) . '/' );
		Functions\when( 'wp_mkdir_p' )->alias( fn( $d ) => is_dir( $d ) || mkdir( $d, 0777, true ) );
		Functions\when( 'wp_generate_password' )->alias( fn() => bin2hex( random_bytes( 6 ) ) );
		Functions\when( 'wp_delete_file' )->alias( fn( $f ) => @unlink( $f ) ); // phpcs:ignore
	}

	/**
	 * Transport that records messages.
	 *
	 * @return Transport_Interface
	 */
	protected function transport(): Transport_Interface {
		$test = $this;
		return new class( $test ) implements Transport_Interface {
			public function __construct( private ServiceTestCase $test ) {}
			public function send( Message $message ): bool {
				return $this->test->record( $message );
			}
		};
	}

	/**
	 * Record a message (called by the fake transport).
	 *
	 * @param Message $message Message.
	 * @return bool Configured transport result.
	 */
	public function record( Message $message ): bool {
		$this->sent[] = $message;
		foreach ( $message->attachments as $path ) {
			$this->attachments_seen[] = array(
				'name'     => basename( $path ),
				'exists'   => is_file( $path ),
				'contents' => (string) @file_get_contents( $path ), // phpcs:ignore
				'path'     => $path,
			);
		}
		return $this->transport_result;
	}

	/**
	 * Build the service.
	 *
	 * @return Ticket_Service
	 */
	protected function service(): Ticket_Service {
		return new Ticket_Service(
			$this->registry,
			fn() => $this->branding,
			fn() => $this->transport(),
			function ( bool $include_log ): array {
				++$this->report_calls;
				return array(
					'text' => "== Site ==\nwp_version: 6.8\n",
					'json' => '{"site":{"wp_version":"6.8"}}',
				);
			},
			new Rate_Limiter(),
			new Send_Log(),
			new Attachment_Writer(),
			fn() => $this->now
		);
	}

	/**
	 * A valid request.
	 *
	 * @param array<string,mixed> $overrides Overrides.
	 * @return Ticket_Request
	 */
	protected function request( array $overrides = array() ): Ticket_Request {
		return Ticket_Request::from_array(
			array_merge(
				array(
					'plugin'   => 'acme',
					'subject'  => 'Checkout fails',
					'message'  => 'It does not work.',
					'reply_to' => 'owner@example.org',
					'name'     => 'Owner',
				),
				$overrides
			),
			7
		);
	}
}
