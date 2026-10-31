<?php
/**
 * Sends a support request.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Mail;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Branding;
use MammothWP\Support\Config;
use MammothWP\Support\Plugin_Definition;
use MammothWP\Support\Plugin_Registry;
use WP_Error;

/**
 * The one service behind the admin screen, the REST endpoint, the CLI command and the Ability.
 *
 * @since 1.0.0
 */
final class Ticket_Service {

	/**
	 * Registry.
	 *
	 * @var Plugin_Registry
	 */
	private Plugin_Registry $registry;

	/**
	 * Branding provider.
	 *
	 * @var callable(): Branding
	 */
	private $branding;

	/**
	 * Transport provider.
	 *
	 * @var callable(): Transport_Interface
	 */
	private $transport;

	/**
	 * Report provider: fn( bool $include_log ): array{text:string,json:string}.
	 *
	 * @var callable
	 */
	private $report;

	/**
	 * Rate limiter.
	 *
	 * @var Rate_Limiter
	 */
	private Rate_Limiter $limiter;

	/**
	 * Log.
	 *
	 * @var Send_Log
	 */
	private Send_Log $log;

	/**
	 * Attachment writer.
	 *
	 * @var Attachment_Writer
	 */
	private Attachment_Writer $attachments;

	/**
	 * Clock.
	 *
	 * @var callable(): int
	 */
	private $clock;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Registry   $registry    Registry.
	 * @param callable          $branding    Branding provider.
	 * @param callable          $transport   Transport provider.
	 * @param callable          $report      Report provider.
	 * @param Rate_Limiter      $limiter     Rate limiter.
	 * @param Send_Log          $log         Send log.
	 * @param Attachment_Writer $attachments Attachment writer.
	 * @param callable|null     $clock       Optional clock.
	 */
	public function __construct(
		Plugin_Registry $registry,
		callable $branding,
		callable $transport,
		callable $report,
		Rate_Limiter $limiter,
		Send_Log $log,
		Attachment_Writer $attachments,
		?callable $clock = null
	) {
		$this->registry    = $registry;
		$this->branding    = $branding;
		$this->transport   = $transport;
		$this->report      = $report;
		$this->limiter     = $limiter;
		$this->log         = $log;
		$this->attachments = $attachments;
		$this->clock       = $clock ?? 'time';
	}

	/**
	 * Validate the request and return the plugin, or an error.
	 *
	 * @since 1.0.0
	 *
	 * @param Ticket_Request $request Request.
	 * @return Plugin_Definition|WP_Error
	 */
	public function validate( Ticket_Request $request ) {
		$plugin = $this->registry->get( $request->plugin );
		if ( null === $plugin ) {
			return new WP_Error( 'mmwp_support_unknown_plugin', __( 'Choose one of the listed plugins.', 'mammothwp-support' ), array( 'status' => 400 ) );
		}
		if ( ! $plugin->has_email() ) {
			return new WP_Error( 'mmwp_support_no_email', __( 'This plugin has no valid support email address configured, so it cannot send requests.', 'mammothwp-support' ), array( 'status' => 400 ) );
		}
		if ( '' === $request->message ) {
			return new WP_Error( 'mmwp_support_empty_message', __( 'Please write a message.', 'mammothwp-support' ), array( 'status' => 400 ) );
		}
		if ( ! is_email( $request->reply_to ) ) {
			return new WP_Error( 'mmwp_support_invalid_reply_to', __( 'Please enter a valid reply-to email address.', 'mammothwp-support' ), array( 'status' => 400 ) );
		}
		return $plugin;
	}

	/**
	 * Send (or, with $dry_run, only build) the email.
	 *
	 * Nothing is sent unless this method is called for an explicit user action; the report is
	 * included only when `include_report` is true.
	 *
	 * @since 1.0.0
	 *
	 * @param Ticket_Request $request Request.
	 * @param bool           $dry_run Build the email without sending or counting against the rate limit.
	 * @return array<string,mixed>|WP_Error
	 */
	public function send( Ticket_Request $request, bool $dry_run = false ) {
		$plugin = $this->validate( $request );
		if ( $plugin instanceof WP_Error ) {
			return $plugin;
		}

		$now = (int) call_user_func( $this->clock );
		if ( ! $dry_run && ! $this->limiter->allowed( $request->user_id, $now ) ) {
			return new WP_Error( 'mmwp_support_rate_limited', __( 'Too many requests. Please wait a few minutes before sending another.', 'mammothwp-support' ), array( 'status' => 429 ) );
		}

		$branding = call_user_func( $this->branding );
		$ref      = Ticket_Formatter::ref( (string) $branding->get( 'ref_prefix' ), $now, Ticket_Formatter::random_suffix() );
		$report   = $request->include_report ? (array) call_user_func( $this->report, $request->include_log ) : null;
		$subject  = Ticket_Formatter::subject( (string) $branding->get( 'subject_prefix' ), $plugin->slug, '' !== $request->subject ? $request->subject : __( 'Support request', 'mammothwp-support' ), $ref );
		$body     = Ticket_Formatter::body( $request->message, $this->facts( $request, $plugin, $ref ), null === $report ? null : (string) $report['text'] );
		$filename = null === $report || ! Config::attach_report( $plugin->slug ) ? '' : $this->attachment_name( $now );
		$headers  = array( 'Reply-To: ' . $this->reply_to_header( $request ) );

		if ( $dry_run ) {
			return array(
				'dry_run'    => true,
				'sent'       => false,
				'ref'        => $ref,
				'to'         => $plugin->email,
				'subject'    => $subject,
				'headers'    => $headers,
				'body'       => $body,
				'attachment' => $filename,
			);
		}

		return $this->deliver( $plugin, $request, new Message( $plugin->email, $subject, $body, $headers ), $ref, $filename, $report, $now );
	}

	/**
	 * Hand the message to the transport, log, and shape the result.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Definition        $plugin   Plugin.
	 * @param Ticket_Request           $request  Request.
	 * @param Message                  $message  Message without attachment.
	 * @param string                   $ref      Reference.
	 * @param string                   $filename Attachment file name ('' for none).
	 * @param array<string,mixed>|null $report   Report text/json.
	 * @param int                      $now      Current time.
	 * @return array<string,mixed>|WP_Error
	 */
	private function deliver( Plugin_Definition $plugin, Ticket_Request $request, Message $message, string $ref, string $filename, ?array $report, int $now ) {
		$path = '';
		if ( '' !== $filename && null !== $report ) {
			$path = $this->attachments->write( $filename, (string) $report['json'] );
		}
		if ( '' !== $path ) {
			$message = new Message( $message->to, $message->subject, $message->body, $message->headers, array( $path ) );
		}

		$transport = call_user_func( $this->transport );
		$sent      = $transport->send( $message );
		$this->attachments->remove( $path );

		$this->limiter->record( $request->user_id, $now );
		$this->log->add( $now, $plugin->slug, $ref, $sent ? 'sent' : 'failed' );

		if ( ! $sent ) {
			return new WP_Error(
				'mmwp_support_mail_failed',
				__( 'The email could not be sent. Check the mail configuration of this site, or use the email link below.', 'mammothwp-support' ),
				array(
					'status' => 502,
					'ref'    => $ref,
					'to'     => $plugin->email,
					'mailto' => Ticket_Formatter::mailto( $plugin->email, $message->subject, $request->message . "\n\n" . $ref ),
				)
			);
		}

		return array(
			'dry_run' => false,
			'sent'    => true,
			'ref'     => $ref,
			'to'      => $plugin->email,
		);
	}

	/**
	 * Facts block under the message.
	 *
	 * @since 1.0.0
	 *
	 * @param Ticket_Request    $request Request.
	 * @param Plugin_Definition $plugin  Plugin.
	 * @param string            $ref     Reference.
	 * @return array<string,string>
	 */
	private function facts( Ticket_Request $request, Plugin_Definition $plugin, string $ref ): array {
		return array(
			'Name'      => $request->name,
			'Reply-To'  => $request->reply_to,
			'Plugin'    => trim( $plugin->name . ' ' . $plugin->version ),
			'Reference' => $ref,
			'Report'    => $request->include_report ? 'included' : 'not included',
		);
	}

	/**
	 * Reply-To header value.
	 *
	 * @since 1.0.0
	 *
	 * @param Ticket_Request $request Request.
	 * @return string
	 */
	private function reply_to_header( Ticket_Request $request ): string {
		$name = trim( (string) preg_replace( '/[^\p{L}\p{N} .\'-]+/u', '', $request->name ) );
		return '' === $name ? $request->reply_to : sprintf( '%s <%s>', $name, $request->reply_to );
	}

	/**
	 * Attachment name for the current site and day.
	 *
	 * @since 1.0.0
	 *
	 * @param int $now Current time.
	 * @return string
	 */
	private function attachment_name( int $now ): string {
		return Attachment_Writer::filename( (string) wp_parse_url( home_url(), PHP_URL_HOST ), gmdate( 'Y-m-d', $now ) );
	}
}
