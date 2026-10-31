<?php
/**
 * Contract for a report section.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A report section. Contract: never return secrets; the redaction pass runs anyway.
 *
 * @since 1.0.0
 */
interface Section_Interface {

	/**
	 * Machine key (array key / JSON key).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function key(): string;

	/**
	 * Human heading.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function label(): string;

	/**
	 * Collect data: scalar values, lists, or one level of associative arrays.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function collect(): array;
}
