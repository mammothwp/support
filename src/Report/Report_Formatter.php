<?php
/**
 * Formats a report as text or JSON.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compact plain text (`key: value` lines in sections) and JSON.
 *
 * @since 1.0.0
 */
final class Report_Formatter {

	/**
	 * Plain-text form.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $report Report.
	 * @return string
	 */
	public static function to_text( array $report ): string {
		$blocks = array();
		foreach ( $report as $section ) {
			$lines = array( '== ' . (string) $section['label'] . ' ==' );
			foreach ( (array) $section['data'] as $key => $value ) {
				$lines = array_merge( $lines, self::lines( (string) $key, $value, 0 ) );
			}
			$blocks[] = implode( "\n", $lines );
		}
		return implode( "\n\n", $blocks ) . "\n";
	}

	/**
	 * JSON form, keyed by section key.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,array<string,mixed>> $report Report.
	 * @return string
	 */
	public static function to_json( array $report ): string {
		$data = array();
		foreach ( $report as $key => $section ) {
			$data[ $key ] = (array) $section['data'];
		}
		$json = wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return false === $json ? '{}' : $json;
	}

	/**
	 * Lines for one key/value pair.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key    Key.
	 * @param mixed  $value  Value.
	 * @param int    $indent Indent level.
	 * @return string[]
	 */
	private static function lines( string $key, $value, int $indent ): array {
		$pad = str_repeat( '  ', $indent );
		if ( ! is_array( $value ) ) {
			return array( $pad . $key . ': ' . self::scalar( $value ) );
		}
		if ( array() === $value ) {
			return array( $pad . $key . ': -' );
		}

		$lines = array( $pad . $key . ':' );
		foreach ( $value as $sub_key => $sub_value ) {
			$lines = array_merge( $lines, array_is_list( $value ) && ! is_array( $sub_value ) ? array( $pad . '  - ' . self::scalar( $sub_value ) ) : self::lines( (string) $sub_key, $sub_value, $indent + 1 ) );
		}
		return $lines;
	}

	/**
	 * Scalar to single-line text.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private static function scalar( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}
		return trim( (string) preg_replace( '/\s+/', ' ', is_scalar( $value ) ? (string) $value : '' ) );
	}
}
