<?php
/**
 * Builds the redacted report for the screen, the email and the operations.
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
 * Facade over Report_Builder + Report_Formatter + Debug_Log_Reader.
 *
 * @since 1.0.0
 */
final class Report_Service {

	/**
	 * Builder factory: fn( array $log_lines ): array.
	 *
	 * @var callable
	 */
	private $builder;

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
	 * @param callable $builder  Callable `fn( string[] $log_lines ): array` returning the structured report.
	 * @param Redactor $redactor Redactor used for the debug log lines.
	 */
	public function __construct( callable $builder, Redactor $redactor ) {
		$this->builder  = $builder;
		$this->redactor = $redactor;
	}

	/**
	 * Build text, JSON and structure. The debug-log tail is added only on request.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $include_log Include the last lines of debug.log.
	 * @return array{report:array<string,mixed>,text:string,json:string}
	 */
	public function build( bool $include_log = false ): array {
		$lines = array();
		if ( $include_log && Debug_Log_Reader::available() ) {
			$lines = array_map( array( $this->redactor, 'redact' ), Debug_Log_Reader::tail( Debug_Log_Reader::path(), \MammothWP\Support\Config::DEBUG_LOG_LINES ) );
		}

		$report = (array) call_user_func( $this->builder, $lines );
		return array(
			'report' => $report,
			'text'   => Report_Formatter::to_text( $report ),
			'json'   => Report_Formatter::to_json( $report ),
		);
	}
}
