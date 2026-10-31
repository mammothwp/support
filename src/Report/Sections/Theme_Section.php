<?php
/**
 * Report section: theme.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support\Report\Sections;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Report\Field_Guard;
use MammothWP\Support\Report\Section_Interface;

/**
 * Active theme, parent theme, block theme flag.
 *
 * @since 1.0.0
 */
final class Theme_Section implements Section_Interface {

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'theme';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Theme', 'mammothwp-support' );
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		$data = Field_Guard::fields(
			array(
				'active'      => static fn() => self::describe( wp_get_theme() ),
				'block_theme' => static fn() => Site_Section::yes_no( wp_is_block_theme() ),
				'parent'      => static function () {
					$parent = wp_get_theme()->parent();
					return $parent ? self::describe( $parent ) : null;
				},
			),
			'theme'
		);
		return array_filter( $data, static fn( $value ): bool => null !== $value );
	}

	/**
	 * "Name Version" of a theme.
	 *
	 * @since 1.0.1
	 *
	 * @param \WP_Theme $theme Theme.
	 * @return string
	 */
	private static function describe( \WP_Theme $theme ): string {
		return trim( (string) $theme->get( 'Name' ) . ' ' . (string) $theme->get( 'Version' ) );
	}
}
