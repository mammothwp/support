<?php
/**
 * PHPUnit bootstrap: Composer autoload first (ABSPATH undefined, so the package and wp-kit
 * bootstraps are no-ops), then a fake ABSPATH, wp-kit's loader and a minimal WP_Error.
 *
 * @package MammothWP\Support
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', sys_get_temp_dir() . '/mmwp-abspath/' );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'MammothWP\\WpKit\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = dirname( __DIR__ ) . '/vendor/mammothwp/wp-kit/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

if ( ! class_exists( 'WP_Error' ) ) {
	// Minimal stand-in for WP_Error.
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

// Defines the bootstrap functions and registers the real copy in the shared state.
require dirname( __DIR__ ) . '/bootstrap.php';
