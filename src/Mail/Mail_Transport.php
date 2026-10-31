<?php
/**
 * Transport that uses wp_mail().
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends through wp_mail() with the site's default From address.
 *
 * @since 1.0.0
 */
final class Mail_Transport implements Transport_Interface {

	/**
	 * Send.
	 *
	 * @since 1.0.0
	 *
	 * @param Message $message Message.
	 * @return bool
	 */
	public function send( Message $message ): bool {
		return (bool) wp_mail( $message->to, $message->subject, $message->body, $message->headers, $message->attachments );
	}
}
