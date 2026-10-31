<?php
/**
 * Service behind the three operations.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Kit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Mail\Ticket_Request;
use MammothWP\Support\Mail\Ticket_Service;
use MammothWP\Support\Plugin_Registry;
use MammothWP\Support\Report\Report_Service;

/**
 * Method names and parameter names match the wp-kit field names, so wp-kit can bind them.
 * No logic here: everything delegates to the services the admin screen uses.
 *
 * @since 1.0.0
 */
final class Operations {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Report_Service  $reports  Report service.
	 * @param Plugin_Registry $registry Registry.
	 * @param Ticket_Service  $tickets  Ticket service.
	 * @param callable        $show_vendor Callable returning whether vendor names are shown.
	 */
	public function __construct(
		private Report_Service $reports,
		private Plugin_Registry $registry,
		private Ticket_Service $tickets,
		private $show_vendor
	) {
	}

	/**
	 * The site report.
	 *
	 * @since 1.0.0
	 *
	 * @param string $format text or json.
	 * @return string|array<string,mixed>
	 */
	public function support_report( string $format = 'text' ) {
		$built = $this->reports->build( false );
		if ( 'json' === $format ) {
			return array_map( static fn( array $section ): array => $section['data'], $built['report'] );
		}
		return $built['text'];
	}

	/**
	 * Registered plugins.
	 *
	 * @since 1.0.0
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function support_plugins(): array {
		$show = (bool) call_user_func( $this->show_vendor );
		return array_map( static fn( $plugin ): array => $plugin->to_array( $show ), $this->registry->all() );
	}

	/**
	 * Send (or dry-run) a support request.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin         Plugin slug.
	 * @param string $subject        Subject.
	 * @param string $message        Message.
	 * @param string $reply_to       Reply-To.
	 * @param string $name           Sender name.
	 * @param bool   $include_report Include the report.
	 * @param bool   $include_log    Include debug.log tail.
	 * @param bool   $dry_run        Print instead of sending.
	 * @return array<string,mixed>|\WP_Error
	 */
	public function support_send( string $plugin = '', string $subject = '', string $message = '', string $reply_to = '', string $name = '', bool $include_report = false, bool $include_log = false, bool $dry_run = false ) {
		$request = Ticket_Request::from_array(
			compact( 'plugin', 'subject', 'message', 'reply_to', 'name', 'include_report', 'include_log' ),
			get_current_user_id()
		);
		return $this->tickets->send( $request, $dry_run );
	}
}
