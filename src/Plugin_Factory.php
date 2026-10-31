<?php
/**
 * Builds Plugin_Definition objects from a file and the registration array.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies header fallbacks, the JSON file and the email precedence:
 * filter > constant > array > json.
 *
 * @since 1.0.0
 */
final class Plugin_Factory {

	/**
	 * Site-owner settings.
	 *
	 * @var Site_Settings
	 */
	private Site_Settings $settings;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param Site_Settings $settings Site-owner settings.
	 */
	public function __construct( Site_Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Create a definition.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $file Main plugin file.
	 * @param array<string,mixed> $args Registration arguments.
	 * @return Plugin_Definition
	 */
	public function create( string $file, array $args ): Plugin_Definition {
		$json = Json_Config::load( dirname( $file ) );
		foreach ( $json['errors'] as $error ) {
			_doing_it_wrong( 'MammothWP\Support\Support::register_plugin', esc_html( $error ), '1.0.0' );
		}

		$merged  = array_merge( $json['data'], $args );
		$headers = $this->headers( $file );
		$slug    = sanitize_title( $this->text( $merged, 'slug', basename( dirname( $file ) ) ) );
		$email   = $this->email( $slug, $merged, array_key_exists( 'email', $args ) ? 'array' : 'json' );

		if ( '' === $email['value'] ) {
			_doing_it_wrong(
				'MammothWP\Support\Support::register_plugin',
				esc_html( sprintf( 'Plugin "%s" has no valid support email; sending is disabled.', $slug ) ),
				'1.0.0'
			);
		}

		return new Plugin_Definition(
			$file,
			$slug,
			$this->text( $merged, 'name', '' !== $headers['Name'] ? $headers['Name'] : $slug ),
			$this->text( $merged, 'version', $headers['Version'] ),
			$this->text( $merged, 'vendor', $headers['Author'] ),
			$email['value'],
			$email['source'],
			esc_url_raw( $this->text( $merged, 'docs_url', '' ) ),
			isset( $merged['menu'] ) && is_array( $merged['menu'] ) ? $merged['menu'] : array(),
			isset( $merged['branding'] ) && is_array( $merged['branding'] ) ? $merged['branding'] : array()
		);
	}

	/**
	 * Resolve the recipient: filter > constant > array > json.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $slug         Plugin slug.
	 * @param array<string,mixed> $merged       Merged config.
	 * @param string              $base_source  Source label for the config value (array or json).
	 * @return array{value:string,source:string}
	 */
	public function email( string $slug, array $merged, string $base_source ): array {
		$value  = isset( $merged['email'] ) && is_string( $merged['email'] ) ? trim( $merged['email'] ) : '';
		$source = '' === $value ? 'none' : $base_source;

		$constant = $this->settings->email_for( $slug );
		if ( null !== $constant ) {
			$value  = trim( $constant );
			$source = 'constant';
		}

		/**
		 * Filters the support recipient for a plugin. Wins over the constant, the array and the JSON file.
		 *
		 * @since 1.0.0
		 *
		 * @param string $email Resolved address so far.
		 * @param string $slug  Plugin slug.
		 */
		$filtered = apply_filters( 'mmwp_support_email', $value, $slug );
		if ( is_string( $filtered ) && trim( $filtered ) !== $value ) {
			$value  = trim( $filtered );
			$source = 'filter';
		}

		if ( ! is_email( $value ) ) {
			return array(
				'value'  => '',
				'source' => 'none',
			);
		}
		return array(
			'value'  => $value,
			'source' => $source,
		);
	}

	/**
	 * Read Name, Version and Author from the plugin header.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file Main plugin file.
	 * @return array{Name:string,Version:string,Author:string}
	 */
	private function headers( string $file ): array {
		$data = is_readable( $file )
			? get_file_data(
				$file,
				array(
					'Name'    => 'Plugin Name',
					'Version' => 'Version',
					'Author'  => 'Author',
				)
			)
			: array();
		return array(
			'Name'    => trim( wp_strip_all_tags( (string) ( $data['Name'] ?? '' ) ) ),
			'Version' => trim( wp_strip_all_tags( (string) ( $data['Version'] ?? '' ) ) ),
			'Author'  => trim( wp_strip_all_tags( (string) ( $data['Author'] ?? '' ) ) ),
		);
	}

	/**
	 * Read a string value with a fallback.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string,mixed> $config   Config.
	 * @param string              $key      Key.
	 * @param string              $fallback Fallback.
	 * @return string
	 */
	private function text( array $config, string $key, string $fallback ): string {
		$value = isset( $config[ $key ] ) && is_string( $config[ $key ] ) ? trim( wp_strip_all_tags( $config[ $key ] ) ) : '';
		return '' !== $value ? $value : $fallback;
	}
}
