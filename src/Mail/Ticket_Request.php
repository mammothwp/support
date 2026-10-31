<?php
/**
 * Sanitised send request.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Input of a send, sanitised once, shared by the admin-post path and the operation.
 *
 * @since 1.0.0
 */
final class Ticket_Request {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id        Sender user id.
	 * @param string $plugin         Plugin slug.
	 * @param string $subject        Subject.
	 * @param string $message        Message.
	 * @param string $reply_to       Reply-To address.
	 * @param string $name           Sender name.
	 * @param bool   $include_report Include the site report.
	 * @param bool   $include_log    Include the debug.log tail.
	 */
	public function __construct(
		public readonly int $user_id,
		public readonly string $plugin,
		public readonly string $subject,
		public readonly string $message,
		public readonly string $reply_to,
		public readonly string $name,
		public readonly bool $include_report,
		public readonly bool $include_log
	) {
	}

	/**
	 * Build from raw, unslashed input.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $input   Raw input.
	 * @param int                 $user_id User id.
	 * @return Ticket_Request
	 */
	public static function from_array( array $input, int $user_id ): self {
		$user     = wp_get_current_user();
		$reply_to = sanitize_email( (string) ( $input['reply_to'] ?? '' ) );
		$name     = sanitize_text_field( (string) ( $input['name'] ?? '' ) );

		return new self(
			$user_id,
			sanitize_title( (string) ( $input['plugin'] ?? '' ) ),
			sanitize_text_field( (string) ( $input['subject'] ?? '' ) ),
			trim( sanitize_textarea_field( (string) ( $input['message'] ?? '' ) ) ),
			'' !== $reply_to ? $reply_to : sanitize_email( (string) $user->user_email ),
			'' !== $name ? $name : sanitize_text_field( (string) $user->display_name ),
			self::truthy( $input['include_report'] ?? false ),
			self::truthy( $input['include_log'] ?? false )
		);
	}

	/**
	 * Strict-ish boolean coercion: only true, 1, "1", "true", "yes", "on" are true.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	public static function truthy( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( is_string( $value ) ? strtolower( $value ) : $value, array( 1, '1', 'true', 'yes', 'on' ), true );
	}
}
