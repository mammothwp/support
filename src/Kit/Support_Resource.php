<?php
/**
 * Resource for wp-kit: one definition, three surfaces.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Kit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Config;
use MammothWP\WpKit\Context;
use MammothWP\WpKit\Field;
use MammothWP\WpKit\Operation;
use MammothWP\WpKit\Resource;

/**
 * REST `mmwp/v1/support/*`, WP-CLI `wp mmwp support *`, Abilities `mmwp/support-*`.
 *
 * @since 1.0.0
 */
final class Support_Resource extends Resource {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Operations $operations Shared service.
	 */
	public function __construct( private Operations $operations ) {
	}

	/**
	 * Resource name.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function name() {
		return 'support';
	}

	/**
	 * Service object.
	 *
	 * @since 1.0.0
	 * @return object
	 */
	public function service() {
		return $this->operations;
	}

	/**
	 * REST namespace.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function rest_namespace() {
		return Config::REST_NAMESPACE;
	}

	/**
	 * WP-CLI base command.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function cli_base() {
		return 'mmwp support';
	}

	/**
	 * Ability category (abilities are named `<category>/<slug>`).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function ability_category() {
		return 'mmwp';
	}

	/**
	 * Ability category labels (neutral: white-label safe).
	 *
	 * @since 1.0.0
	 * @return array<string,string>
	 */
	public function ability_category_args() {
		return array(
			'label'       => __( 'Support', 'mammothwp-support' ),
			'description' => __( 'Site report and support requests.', 'mammothwp-support' ),
		);
	}

	/**
	 * Default capability (filterable).
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function capability() {
		return Config::capability();
	}

	/**
	 * Operations.
	 *
	 * @since 1.0.0
	 * @return array<string,Operation>
	 */
	public function operations() {
		return array(
			'report'  => Operation::read( 'support_report' )
				->label( __( 'Site report', 'mammothwp-support' ) )
				->description( __( 'Returns the redacted site report. Nothing is sent anywhere.', 'mammothwp-support' ) )
				->input( array( Field::enum( 'format', array( 'text', 'json' ) )->default_value( 'text' )->describe( 'Output format.' ) ) )
				->rest( 'GET', '/report' )
				->cli( 'report' )
				->ability( 'support-report' ),
			'plugins' => Operation::read( 'support_plugins' )
				->label( __( 'Registered plugins', 'mammothwp-support' ) )
				->description( __( 'Plugins registered with the Support screen.', 'mammothwp-support' ) )
				->rest( 'GET', '/plugins' )
				->cli( 'plugins', array( 'columns' => array( 'slug', 'name', 'version', 'vendor', 'email_configured', 'recipient', 'email_source' ) ) )
				->ability( 'support-plugins' ),
			'send'    => Operation::write( 'support_send' )
				->label( __( 'Send a support request', 'mammothwp-support' ) )
				->description( __( 'Sends one email to the plugin\'s support address. The site report is included only with include_report.', 'mammothwp-support' ) )
				->input(
					array(
						Field::string( 'plugin' )->required()->describe( 'Slug of a registered plugin.' ),
						Field::string( 'subject' )->describe( 'Subject.' ),
						Field::string( 'message' )->required()->describe( 'Message.' ),
						Field::string( 'reply_to' )->describe( 'Reply-To address (default: current user).' ),
						Field::string( 'name' )->describe( 'Sender name.' ),
						Field::bool( 'include_report' )->default_value( false )->describe( 'Include the site report (default false).' ),
						Field::bool( 'include_log' )->default_value( false )->describe( 'Include the last debug.log lines (default false).' ),
						Field::bool( 'dry_run' )->default_value( false )->describe( 'Print the email instead of sending it.' ),
					)
				)
				->confirm( array( Context::ABILITY ) )
				->rest( 'POST', '/send' )
				->cli( 'send' )
				->ability( 'support-send' ),
		);
	}
}
