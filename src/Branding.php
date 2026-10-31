<?php
/**
 * Value object: resolved branding.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Branding used by the header, menu, titles, email subject and powered-by line.
 *
 * @since 1.0.0
 */
final class Branding {

	public const KEYS = array( 'name', 'menu_title', 'page_title', 'icon', 'logo_url', 'subject_prefix', 'ref_prefix', 'show_powered_by', 'show_vendor_names' );

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $values     Resolved values keyed by self::KEYS.
	 * @param string              $mode       mammoth, neutral, vendor, whitelabel or filter.
	 */
	public function __construct(
		private array $values,
		public readonly string $mode
	) {
	}

	/**
	 * Get one value.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key Key from self::KEYS.
	 * @return mixed
	 */
	public function get( string $key ) {
		return $this->values[ $key ] ?? '';
	}

	/**
	 * Whether the package presents itself as Mammoth WP.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_mammoth(): bool {
		return 'mammoth' === $this->mode;
	}

	/**
	 * All values.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->values;
	}
}
