<?php
/**
 * Transport seam.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivers a message. Only MailTransport (wp_mail) ships. Any future transport that
 * sends data off-site must obtain the same explicit user action first.
 *
 * @since 1.0.0
 */
interface Transport_Interface {

	/**
	 * Send.
	 *
	 * @since 1.0.0
	 *
	 * @param Message $message Message.
	 * @return bool True on success.
	 */
	public function send( Message $message ): bool;
}
