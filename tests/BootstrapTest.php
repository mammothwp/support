<?php
/**
 * Version election, loader and registration before/after boot.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Functions;
use MammothWP\Support\Kernel;
use MammothWP\Support\Plugin_Factory;
use MammothWP\Support\Plugin_Registry;
use MammothWP\Support\Site_Settings;

final class BootstrapTest extends TestCase {

	/**
	 * Create a fake package copy whose src/ defines a probe class.
	 *
	 * @param string $version Copy version.
	 * @param string $class   Probe class short name.
	 * @return string Directory.
	 */
	private function fake_copy( string $version, string $class ): string {
		$dir = $this->temp_dir() . '/copy-' . $version;
		mkdir( $dir . '/src', 0777, true );
		file_put_contents( $dir . '/src/' . $class . '.php', "<?php\nnamespace MammothWP\\Support;\nfinal class $class { public const FROM = '$version'; }\n" );
		return $dir;
	}

	public function test_pick_winner_prefers_highest_version_of_three_copies(): void {
		$copies = array(
			'/a' => '1.2.0',
			'/b' => '2.0.0',
			'/c' => '1.9.9',
		);
		$this->assertSame( '/b', \mmwp_support_pick_winner( $copies ) );
		$this->assertNull( \mmwp_support_pick_winner( array() ) );
		$this->assertSame(
			'/y',
			\mmwp_support_pick_winner(
				array(
					'/x' => '1.10.0',
					'/y' => '1.10.1',
				)
			),
			'Compares versions, not strings.'
		);
	}

	public function test_election_loads_classes_from_the_winning_copy_only(): void {
		$GLOBALS['mmwp_support_state'] = array(
			'copies' => array(),
			'queue'  => array(),
			'winner' => null,
			'hooked' => true,
		);
		$old                           = $this->fake_copy( '1.2.0', 'Election_Probe' );
		$new                           = $this->fake_copy( '2.0.0', 'Election_Probe_B' );
		$mid                           = $this->fake_copy( '1.9.9', 'Election_Probe_C' );
		file_put_contents( $old . '/src/Election_Probe_B.php', "<?php\nnamespace MammothWP\\Support;\nfinal class Election_Probe_B { public const FROM = '1.2.0'; }\n" );
		file_put_contents( $mid . '/src/Election_Probe_B.php', "<?php\nnamespace MammothWP\\Support;\nfinal class Election_Probe_B { public const FROM = '1.9.9'; }\n" );

		\mmwp_support_register_copy( '1.2.0', $old );
		\mmwp_support_register_copy( '2.0.0', $new );
		\mmwp_support_register_copy( '1.9.9', $mid );

		$this->assertSame( $new, \mmwp_support_elect() );
		$this->assertSame( '2.0.0', \MammothWP\Support\Election_Probe_B::FROM );
		$this->assertSame( $new, \mmwp_support_state()['winner'] );

		\mmwp_support_register_copy( '9.9.9', $this->fake_copy( '9.9.9', 'Election_Probe_D' ) );
		$this->assertCount( 3, \mmwp_support_state()['copies'], 'A copy registered after the election is ignored.' );
	}

	public function test_registration_before_boot_is_queued_then_replayed(): void {
		$GLOBALS['mmwp_support_state'] = array(
			'copies' => array(),
			'queue'  => array(),
			'winner' => null,
			'hooked' => true,
		);
		$file                          = $this->plugin_file();
		\MammothWP\Support\Support::register_plugin( $file, array( 'email' => 'help@acme.test' ) );
		$this->assertCount( 1, \mmwp_support_state()['queue'] );

		$registry = $this->boot_fake_kernel();
		Kernel::flush_queue();
		$this->assertSame( array( 'acme-widgets' ), array_map( static fn( $p ) => $p->slug, $registry->all() ) );
		$this->assertCount( 0, \mmwp_support_state()['queue'] );
	}

	public function test_registration_after_boot_is_applied_at_once(): void {
		$GLOBALS['mmwp_support_state'] = array(
			'copies' => array(),
			'queue'  => array(),
			'winner' => '/some/copy',
			'hooked' => true,
		);
		$registry                      = $this->boot_fake_kernel();
		\MammothWP\Support\Support::register_plugin( $this->plugin_file(), array( 'email' => 'help@acme.test' ) );
		$this->assertCount( 1, $registry->all() );
	}

	/**
	 * Install a Kernel instance without running start().
	 *
	 * @return Plugin_Registry
	 */
	private function boot_fake_kernel(): Plugin_Registry {
		$registry = new Plugin_Registry();
		$kernel   = ( new \ReflectionClass( Kernel::class ) )->newInstanceWithoutConstructor();
		foreach ( array(
			'registry' => $registry,
			'factory'  => new Plugin_Factory( new Site_Settings( static fn() => null ) ),
		) as $name => $value ) {
			$property = new \ReflectionProperty( Kernel::class, $name );
			$property->setAccessible( true );
			$property->setValue( $kernel, $value );
		}
		$instance = new \ReflectionProperty( Kernel::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, $kernel );
		Functions\when( 'is_multisite' )->justReturn( false );
		return $registry;
	}

	protected function tear_down() {
		$instance = new \ReflectionProperty( Kernel::class, 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
		parent::tear_down();
	}
}
