<?php
/**
 * Builds the site report.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Redactor;

/**
 * Collects sections into a structured, redacted, deterministic report.
 *
 * Structure: `[ key => [ 'label' => string, 'data' => array ] ]`.
 *
 * @since 1.0.0
 */
final class Report_Builder {

	/**
	 * Sections.
	 *
	 * @var Section_Interface[]
	 */
	private array $sections;

	/**
	 * Redactor.
	 *
	 * @var Redactor
	 */
	private Redactor $redactor;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Section_Interface[] $sections Core sections.
	 * @param Redactor            $redactor Redactor.
	 */
	public function __construct( array $sections, Redactor $redactor ) {
		$this->sections = $sections;
		$this->redactor = $redactor;
	}

	/**
	 * Build the report.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $log_lines Debug-log lines to include (empty = none).
	 * @return array<string,array<string,mixed>>
	 */
	public function build( array $log_lines = array() ): array {
		$report = array();
		foreach ( $this->all_sections() as $section ) {
			$report[ $section->key() ] = $this->collect_section( $section );
		}

		if ( array() !== $log_lines ) {
			$report['debug_log'] = array(
				'label' => __( 'Debug log (last lines)', 'mammothwp-support' ),
				'data'  => array( 'lines' => array_values( $log_lines ) ),
			);
		}

		$redacted = $this->redactor->redact_deep( $report );
		return is_array( $redacted ) ? $redacted : $report;
	}

	/**
	 * Collect one section; a throwing section renders as unavailable instead of fataling.
	 *
	 * @since 1.0.1
	 *
	 * @param Section_Interface $section Section.
	 * @return array{label:string,data:array<string,mixed>}
	 */
	private function collect_section( Section_Interface $section ): array {
		$key   = Field_Guard::run( array( $section, 'key' ), 'section', 'unknown' );
		$label = Field_Guard::run( array( $section, 'label' ), 'section.' . $key, (string) $key );
		$data  = Field_Guard::run( array( $section, 'collect' ), 'section.' . $key, null );
		return array(
			'label' => (string) $label,
			'data'  => is_array( $data ) ? $data : array( 'status' => Field_Guard::UNAVAILABLE ),
		);
	}

	/**
	 * Core sections plus those added through `mmwp_support_report_sections`.
	 *
	 * @since 1.0.0
	 *
	 * @return Section_Interface[]
	 */
	private function all_sections(): array {
		/**
		 * Filters the report sections. Add a `Section_Interface` object. Contract: no secrets;
		 * redaction runs over everything, but do not rely on it.
		 *
		 * @since 1.0.0
		 *
		 * @param Section_Interface[] $sections Sections in output order.
		 */
		$sections = apply_filters( 'mmwp_support_report_sections', $this->sections );
		return array_values(
			array_filter(
				is_array( $sections ) ? $sections : $this->sections,
				static fn( $section ): bool => $section instanceof Section_Interface
			)
		);
	}
}
