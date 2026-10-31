<?php
/**
 * Value object: one registered plugin.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Immutable description of a registered plugin.
 *
 * @since 1.0.0
 */
final class Plugin_Definition {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string              $file         Main plugin file.
	 * @param string              $slug         Slug.
	 * @param string              $name         Display name.
	 * @param string              $version      Version.
	 * @param string              $vendor       Vendor.
	 * @param string              $email        Resolved recipient ('' when none/invalid).
	 * @param string              $email_source Where the email came from: filter, constant, array, json or none.
	 * @param string              $docs_url     Documentation URL.
	 * @param array<string,mixed> $menu         Menu shortcut config.
	 * @param array<string,mixed> $branding     Vendor branding config.
	 */
	public function __construct(
		public readonly string $file,
		public readonly string $slug,
		public readonly string $name,
		public readonly string $version,
		public readonly string $vendor,
		public readonly string $email,
		public readonly string $email_source,
		public readonly string $docs_url,
		public readonly array $menu,
		public readonly array $branding
	) {
	}

	/**
	 * Whether a valid recipient is configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function has_email(): bool {
		return '' !== $this->email;
	}

	/**
	 * Whether the vendor is Mammoth WP.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_mammoth(): bool {
		return strtolower( trim( $this->vendor ) ) === strtolower( Config::MAMMOTH_VENDOR );
	}

	/**
	 * Array form for the `plugins` operation.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $show_vendor Whether to include the vendor name.
	 * @return array<string,mixed>
	 */
	public function to_array( bool $show_vendor = true ): array {
		return array(
			'slug'             => $this->slug,
			'name'             => $this->name,
			'version'          => $this->version,
			'vendor'           => $show_vendor ? $this->vendor : '',
			'docs_url'         => $this->docs_url,
			'email_configured' => $this->has_email(),
			'recipient'        => $this->email,
			'email_source'     => $this->email_source,
		);
	}
}
