<?php
/**
 * No-JS submission handler (admin-post.php).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;
use MammothWP\Support\Mail\Ticket_Request;
use MammothWP\Support\Mail\Ticket_Service;
use WP_Error;

/**
 * Progressive enhancement: the form works without JavaScript through the same service.
 *
 * @since 1.0.0
 */
final class Post_Handler {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Ticket_Service $tickets Ticket service.
	 * @param callable       $fallback_url Provider of the screen URL used when there is no referer.
	 */
	public function __construct(
		private Ticket_Service $tickets,
		private $fallback_url
	) {
	}

	/**
	 * Handle `admin_post_mmwp_support_send`.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle(): void {
		check_admin_referer( Config::NONCE_ACTION );
		if ( ! current_user_can( Config::capability( is_network_admin() ) ) && ! current_user_can( Config::capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'mammothwp-support' ), 403 );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified above; sanitised field by field in Ticket_Request.
		$input   = wp_unslash( $_POST );
		$request = Ticket_Request::from_array( is_array( $input ) ? $input : array(), get_current_user_id() );
		$result  = $this->tickets->send( $request );

		Notice_Store::put( $request->user_id, $this->notice( $result, $request ) );
		wp_safe_redirect( $this->target() );
		exit;
	}

	/**
	 * Shape the notice stored for the next page load.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed>|WP_Error $result  Service result.
	 * @param Ticket_Request               $request Request (refills the form on error).
	 * @return array<string,mixed>
	 */
	public function notice( $result, Ticket_Request $request ): array {
		if ( ! $result instanceof WP_Error ) {
			return array(
				'ok'  => true,
				'ref' => (string) $result['ref'],
				'to'  => (string) $result['to'],
			);
		}

		$data = $result->get_error_data();
		$data = is_array( $data ) ? $data : array();
		return array(
			'ok'      => false,
			'message' => $result->get_error_message(),
			'ref'     => (string) ( $data['ref'] ?? '' ),
			'mailto'  => (string) ( $data['mailto'] ?? '' ),
			'form'    => array(
				'plugin'         => $request->plugin,
				'subject'        => $request->subject,
				'message'        => $request->message,
				'reply_to'       => $request->reply_to,
				'name'           => $request->name,
				'include_report' => $request->include_report,
				'include_log'    => $request->include_log,
			),
		);
	}

	/**
	 * Where to go back to.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function target(): string {
		$referer = wp_get_referer();
		return false === $referer ? (string) call_user_func( $this->fallback_url ) : $referer;
	}
}
