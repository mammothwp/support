<?php
/**
 * Redaction pass applied to everything that leaves the site in a report.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes secrets and personal data from report text.
 *
 * Rules: absolute paths (ABSPATH, content dir, home dirs), emails, IP addresses,
 * URL credentials, `Bearer` tokens, `key=value` pairs whose key looks secret, JWTs
 * and long token-like strings.
 *
 * @since 1.0.0
 */
final class Redactor {

	public const SECRET_KEYS = 'pass(?:word|wd)?|secret|token|api[_-]?key|apikey|auth(?:orization)?|salt|nonce|private[_-]?key|access[_-]?key|credential|cookie|session|license[_-]?key|bearer';

	/**
	 * Absolute paths to replace, longest first: path => placeholder.
	 *
	 * @var array<string,string>
	 */
	private array $paths;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,string> $paths Absolute path => placeholder (e.g. ABSPATH => "[ABSPATH]/").
	 */
	public function __construct( array $paths = array() ) {
		uksort( $paths, static fn( string $a, string $b ): int => strlen( $b ) <=> strlen( $a ) );
		$this->paths = array_filter( $paths, static fn( string $placeholder, string $path ): bool => '' !== trim( $path, '/\\' ), ARRAY_FILTER_USE_BOTH );
	}

	/**
	 * Redact a string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public function redact( string $text ): string {
		foreach ( $this->paths as $path => $placeholder ) {
			$text = str_replace( array( $path, rtrim( $path, '/\\' ) ), array( $placeholder, rtrim( $placeholder, '/' ) ), $text );
		}

		$rules = array(
			'#(?i)\b([a-z][a-z0-9+.-]*://)[^\s/@:]+(?::[^\s/@]*)?@#' => '$1[credentials]@',
			'#(?i)\bbearer\s+[A-Za-z0-9._~+/=-]+#' => 'Bearer [token]',
			'#\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]*#' => '[token]',
			'#(?i)(["\']?[\w.-]*(?:' . self::SECRET_KEYS . ')[\w.-]*["\']?\s*[:=]>?\s*)(["\']?)[^\s,"\';&]+\2#' => '$1[redacted]',
			'#(?i)(?:/home|/Users)/[^/\s]+#'       => '/[home]',
			'#(?i)[A-Z]:\\\\Users\\\\[^\\\\\s]+#'  => '[home]',
			'#[A-Za-z0-9._%+-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,}#' => '[email]',
			'#\b(?:(?:25[0-5]|2[0-4]\d|1?\d?\d)\.){3}(?:25[0-5]|2[0-4]\d|1?\d?\d)\b#' => '[ip]',
			'#(?<![\w:])(?:(?:[0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4}|(?:[0-9A-Fa-f]{1,4}(?::[0-9A-Fa-f]{1,4}){0,6})?::(?:[0-9A-Fa-f]{1,4}(?::[0-9A-Fa-f]{1,4}){0,6})?)(?![\w:])#' => '[ip]',
		);
		foreach ( $rules as $pattern => $replacement ) {
			$text = (string) preg_replace( $pattern, $replacement, $text );
		}

		return (string) preg_replace_callback( '#[A-Za-z0-9_+/=-]{32,}#', array( $this, 'redact_token' ), $text );
	}

	/**
	 * Replace long strings that mix letters and digits (tokens, hashes); keep plain words/paths.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $matches Regex matches.
	 * @return string
	 */
	private function redact_token( array $matches ): string {
		$candidate = $matches[0];
		$has_digit = 1 === preg_match( '/\d/', $candidate );
		$has_alpha = 1 === preg_match( '/[A-Za-z]/', $candidate );
		return $has_digit && $has_alpha ? '[token]' : $candidate;
	}

	/**
	 * Redact every string value in a structure (keys are kept).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Scalar or nested array.
	 * @return mixed
	 */
	public function redact_deep( $data ) {
		if ( is_string( $data ) ) {
			return $this->redact( $data );
		}
		if ( is_array( $data ) ) {
			return array_map( array( $this, 'redact_deep' ), $data );
		}
		return $data;
	}
}
