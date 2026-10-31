<?php
/**
 * The canonical Support screen.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Branding;
use MammothWP\Support\Branding_Resolver;
use MammothWP\Support\Config;
use MammothWP\Support\Kit\Kit_Integration;
use MammothWP\Support\Mail\Send_Log;
use MammothWP\Support\Plugin_Registry;
use MammothWP\Support\Report\Debug_Log_Reader;
use MammothWP\Support\Report\Report_Service;

/**
 * Builds the view model and renders the screen. Output is escaped in the views.
 *
 * @since 1.0.0
 */
final class Screen {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Plugin_Registry $registry Registry.
	 * @param callable        $branding Branding provider: fn(): Branding.
	 * @param Report_Service  $reports  Report service.
	 * @param Send_Log        $log      Send log.
	 * @param string          $asset_base Base URL of the package's assets/ directory ('' = none).
	 * @param string          $version  Package version (cache-busting).
	 * @param string          $kit_state  Loaded wp-kit version.
	 */
	public function __construct(
		private Plugin_Registry $registry,
		private $branding,
		private Report_Service $reports,
		private Send_Log $log,
		private string $asset_base,
		private string $version,
		private string $kit_state
	) {
	}

	/**
	 * Enqueue the CSS/JS on the Support screen only.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( '' === $this->asset_base ) {
			return;
		}
		wp_enqueue_style( Config::ASSET_HANDLE, $this->asset_base . 'mmwp-support.css', array(), $this->version );
		wp_enqueue_script( Config::ASSET_HANDLE, $this->asset_base . 'mmwp-support.js', array(), $this->version, true );
		wp_localize_script( Config::ASSET_HANDLE, 'mmwpSupport', $this->script_data() );
	}

	/**
	 * Data for the script: REST endpoint, nonce and strings.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	private function script_data(): array {
		/* translators: %s: recipient email address (replaced by script). */
		$send_to = __( 'This message will be sent to %s.', 'mammothwp-support' );
		return array(
			'sendUrl' => rest_url( Config::REST_NAMESPACE . '/support/send' ),
			'nonce'   => wp_create_nonce( Config::REST_NONCE_ACTION ),
			'i18n'    => array(
				'sending'  => __( 'Sending…', 'mammothwp-support' ),
				'sent'     => __( 'Your message was sent. Reference:', 'mammothwp-support' ),
				'failed'   => __( 'The request failed.', 'mammothwp-support' ),
				'mailto'   => __( 'Send it from your email app instead', 'mammothwp-support' ),
				'copied'   => __( 'Copied.', 'mammothwp-support' ),
				'sendTo'   => $send_to,
				'noEmail'  => __( 'This plugin has no valid support email address configured, so sending is disabled.', 'mammothwp-support' ),
				'copyFail' => __( 'Could not copy. Select the text and copy it manually.', 'mammothwp-support' ),
			),
		);
	}

	/**
	 * Render the screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( Config::capability( is_network_admin() ) ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mammothwp-support' ), 403 );
		}
		View::render( 'screen', $this->view_model() );
	}

	/**
	 * Build the view model.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	public function view_model(): array {
		$branding = call_user_func( $this->branding );
		if ( ! $branding instanceof Branding ) {
			$branding = new Branding( Branding_Resolver::neutral(), 'neutral' );
		}
		$notice = Notice_Store::take( get_current_user_id() );
		$form   = is_array( $notice['form'] ?? null ) ? $notice['form'] : array();
		$report = $this->reports->build( false );
		$user   = wp_get_current_user();

		return array(
			'branding'    => $branding,
			'plugins'     => $this->registry->all(),
			'selected'    => $this->selected_slug( (string) ( $form['plugin'] ?? '' ) ),
			'form'        => array(
				'subject'        => (string) ( $form['subject'] ?? '' ),
				'message'        => (string) ( $form['message'] ?? '' ),
				'reply_to'       => (string) ( $form['reply_to'] ?? $user->user_email ),
				'name'           => (string) ( $form['name'] ?? $user->display_name ),
				'include_report' => ! empty( $form['include_report'] ),
				'include_log'    => ! empty( $form['include_log'] ),
			),
			'notice'      => $notice,
			'report_text' => $report['text'],
			'log_lines'   => Debug_Log_Reader::available() ? $this->reports->build( true )['report']['debug_log']['data']['lines'] ?? array() : array(),
			'log_ready'   => Debug_Log_Reader::available(),
			'send_log'    => $this->log->all(),
			'kit_notice'  => ! Kit_Integration::is_supported( $this->kit_state ),
			'kit_loaded'  => $this->kit_state,
			'post_url'    => admin_url( 'admin-post.php' ),
			'action'      => Config::ADMIN_POST_ACTION,
			'nonce_name'  => Config::NONCE_ACTION,
		);
	}

	/**
	 * Slug to preselect: a stored form value, the `plugin` query arg, or the only plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $stored Slug from a previous submission.
	 * @return string
	 */
	private function selected_slug( string $stored ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only preselection, no state change.
		$requested = isset( $_GET['plugin'] ) ? sanitize_title( wp_unslash( (string) $_GET['plugin'] ) ) : '';
		foreach ( array( $stored, $requested ) as $slug ) {
			if ( '' !== $slug && null !== $this->registry->get( $slug ) ) {
				return $slug;
			}
		}
		$all = $this->registry->all();
		return 1 === count( $all ) ? $all[0]->slug : '';
	}
}
