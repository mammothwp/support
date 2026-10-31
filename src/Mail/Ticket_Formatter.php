<?php
/**
 * Subject, reference and mailto formatting.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;

/**
 * Pure formatting helpers.
 *
 * @since 1.0.0
 */
final class Ticket_Formatter {

	/**
	 * Reference: `<PREFIX>-<yyyymmdd>-<6 chars>`.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix    Prefix from branding (MMWP, SUP, ...).
	 * @param int    $timestamp Unix time (UTC).
	 * @param string $suffix    Six random characters.
	 * @return string
	 */
	public static function ref( string $prefix, int $timestamp, string $suffix ): string {
		return sprintf( '%s-%s-%s', $prefix, gmdate( 'Ymd', $timestamp ), strtoupper( substr( $suffix, 0, 6 ) ) );
	}

	/**
	 * Six random hex characters.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function random_suffix(): string {
		return strtoupper( substr( bin2hex( random_bytes( 3 ) ), 0, 6 ) );
	}

	/**
	 * Subject: `<prefix>[<plugin-slug>] <subject> (<ref>)`, filterable, single line.
	 *
	 * @since 1.0.0
	 *
	 * @param string $prefix  Subject prefix from branding.
	 * @param string $slug    Plugin slug.
	 * @param string $subject User subject.
	 * @param string $ref     Reference.
	 * @return string
	 */
	public static function subject( string $prefix, string $slug, string $subject, string $ref ): string {
		$text = sprintf( Config::SUBJECT_FORMAT, $prefix, $slug, $subject, $ref );

		/**
		 * Filters the email subject.
		 *
		 * @since 1.0.0
		 *
		 * @param string $text    Formatted subject.
		 * @param string $prefix  Subject prefix from branding.
		 * @param string $slug    Plugin slug.
		 * @param string $subject User-entered subject.
		 * @param string $ref     Reference.
		 */
		$text = apply_filters( 'mmwp_support_subject', $text, $prefix, $slug, $subject, $ref );
		return trim( (string) preg_replace( '/[\r\n]+/', ' ', is_string( $text ) ? $text : '' ) );
	}

	/**
	 * Plain-text body: message, sender block, optional report.
	 *
	 * @since 1.0.0
	 *
	 * @param string               $message     Message.
	 * @param array<string,string> $facts  Label => value lines (name, plugin, ref).
	 * @param string|null          $report_text Report text when included.
	 * @return string
	 */
	public static function body( string $message, array $facts, ?string $report_text ): string {
		$lines = array( $message, '', '--' );
		foreach ( $facts as $label => $value ) {
			$lines[] = $label . ': ' . $value;
		}
		if ( null !== $report_text ) {
			$lines[] = '';
			$lines[] = $report_text;
		}
		return implode( "\n", $lines );
	}

	/**
	 * A mailto: URL for the fallback when wp_mail() fails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to      Recipient.
	 * @param string $subject Subject.
	 * @param string $message User message (truncated).
	 * @return string
	 */
	public static function mailto( string $to, string $subject, string $message ): string {
		$body = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, Config::MAILTO_MAX_CHARS ) : substr( $message, 0, Config::MAILTO_MAX_CHARS );
		return 'mailto:' . rawurlencode( $to ) . '?subject=' . rawurlencode( $subject ) . '&body=' . rawurlencode( $body );
	}
}
