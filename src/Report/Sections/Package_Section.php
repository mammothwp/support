<?php
/**
 * Report section: this package.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Report\Section_Interface;

/**
 * Registered plugins and versions, booted package version, copies found, menu location,
 * branding mode and loaded wp-kit version. The heading is always neutral ("Support package").
 *
 * @since 1.0.0
 */
final class Package_Section implements Section_Interface {

	/**
	 * Data prepared by the kernel: pure values, no objects.
	 *
	 * @var array<string,mixed>
	 */
	private array $facts;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $facts Facts: plugins (list of "slug version"), package_version, copies (versions), menu, branding_mode, wp_kit.
	 */
	public function __construct( array $facts ) {
		$this->facts = $facts;
	}

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'support_package';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Support package', 'mammothwp-support' );
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		return $this->facts;
	}
}
