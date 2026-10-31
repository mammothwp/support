<?php
/**
 * Reader/validator for mammothwp-support.json.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses the optional `mammothwp-support.json` file (same keys as the PHP array).
 *
 * @since 1.0.0
 */
final class Json_Config {

	public const STRING_KEYS = array( 'email', 'name', 'slug', 'version', 'vendor', 'docs_url' );
	public const ARRAY_KEYS  = array( 'menu', 'branding' );

	/**
	 * Load the file from a plugin directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $dir Plugin root directory.
	 * @return array{data:array<string,mixed>,errors:string[]}
	 */
	public static function load( string $dir ): array {
		$file = rtrim( $dir, '/\\' ) . '/' . Config::JSON_FILENAME;
		if ( ! is_readable( $file ) ) {
			return array(
				'data'   => array(),
				'errors' => array(),
			);
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file inside the plugin.
		$contents = file_get_contents( $file );
		return self::parse( false === $contents ? '' : $contents );
	}

	/**
	 * Parse and validate JSON text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $json JSON document.
	 * @return array{data:array<string,mixed>,errors:string[]}
	 */
	public static function parse( string $json ): array {
		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) || array() !== array_filter( array_keys( $decoded ), 'is_int' ) ) {
			return array(
				'data'   => array(),
				'errors' => array( Config::JSON_FILENAME . ' must contain a JSON object.' ),
			);
		}

		$data   = array();
		$errors = array();
		foreach ( $decoded as $key => $value ) {
			$error = self::validate_key( (string) $key, $value );
			if ( null !== $error ) {
				$errors[] = $error;
				continue;
			}
			$data[ $key ] = $value;
		}
		return array(
			'data'   => $data,
			'errors' => $errors,
		);
	}

	/**
	 * Validate one key.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key   Key.
	 * @param mixed  $value Value.
	 * @return string|null Error message, null when valid.
	 */
	private static function validate_key( string $key, $value ): ?string {
		if ( in_array( $key, self::STRING_KEYS, true ) ) {
			return is_string( $value ) ? null : sprintf( 'Key "%s" must be a string.', $key );
		}
		if ( in_array( $key, self::ARRAY_KEYS, true ) ) {
			return is_array( $value ) ? null : sprintf( 'Key "%s" must be an object.', $key );
		}
		return sprintf( 'Unknown key "%s" ignored.', $key );
	}
}
