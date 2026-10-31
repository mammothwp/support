<?php
/**
 * Email message value object.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A fully built email, ready for a transport.
 *
 * @since 1.0.0
 */
final class Message {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $to          Recipient.
	 * @param string   $subject     Subject.
	 * @param string   $body        Plain text body.
	 * @param string[] $headers     Extra headers (Reply-To). No From: the site default is used.
	 * @param string[] $attachments Absolute file paths.
	 */
	public function __construct(
		public readonly string $to,
		public readonly string $subject,
		public readonly string $body,
		public readonly array $headers = array(),
		public readonly array $attachments = array()
	) {
	}
}
