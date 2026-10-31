<?php
/**
 * Report section: WooCommerce.
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
 * WooCommerce basics: version, HPOS, currency, base country, enabled gateway and shipping method ids.
 * No orders, no customers.
 *
 * @since 1.0.0
 */
final class Woo_Section implements Section_Interface {

	/**
	 * Whether WooCommerce is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
	}

	/**
	 * Key.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function key(): string {
		return 'woocommerce';
	}

	/**
	 * Heading.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function label(): string {
		return 'WooCommerce';
	}

	/**
	 * Collect.
	 *
	 * @since 1.0.0
	 * @return array<string,mixed>
	 */
	public function collect(): array {
		return Field_Guard::fields(
			array(
				'version'          => static fn() => defined( 'WC_VERSION' ) ? (string) constant( 'WC_VERSION' ) : 'unknown',
				'hpos'             => static fn() => Site_Section::yes_no( self::hpos_enabled() ),
				'currency'         => static fn() => (string) get_option( 'woocommerce_currency', '' ),
				'base_country'     => static fn() => (string) strtok( (string) get_option( 'woocommerce_default_country', '' ), ':' ),
				'payment_gateways' => static fn() => self::enabled_gateways(),
				'shipping_methods' => static fn() => self::enabled_shipping_methods(),
			),
			'woocommerce'
		);
	}

	/**
	 * Whether High-Performance Order Storage is on.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private static function hpos_enabled(): bool {
		$util = '\Automattic\WooCommerce\Utilities\OrderUtil';
		return class_exists( $util ) && (bool) call_user_func( array( $util, 'custom_orders_table_usage_is_enabled' ) );
	}

	/**
	 * Ids of enabled payment gateways.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	private static function enabled_gateways(): array {
		$factory = 'WC';
		$woo     = is_callable( $factory ) ? $factory() : null; // @phpstan-ignore-line -- WC() has no stub here.
		if ( ! is_object( $woo ) || ! method_exists( $woo, 'payment_gateways' ) ) {
			return array();
		}
		$ids = array();
		foreach ( (array) $woo->payment_gateways()->payment_gateways() as $id => $gateway ) {
			if ( is_object( $gateway ) && 'yes' === ( $gateway->enabled ?? '' ) ) {
				$ids[] = (string) $id;
			}
		}
		sort( $ids );
		return $ids;
	}

	/**
	 * Ids of enabled shipping methods across zones.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]
	 */
	private static function enabled_shipping_methods(): array {
		$zones_class = 'WC_Shipping_Zones';
		if ( ! class_exists( $zones_class ) ) {
			return array();
		}
		$ids   = array();
		$zones = (array) call_user_func( array( $zones_class, 'get_zones' ) );
		foreach ( $zones as $zone ) {
			foreach ( (array) ( $zone['shipping_methods'] ?? array() ) as $method ) {
				if ( is_object( $method ) && 'yes' === ( $method->enabled ?? '' ) ) {
					$ids[] = (string) $method->id;
				}
			}
		}
		$ids = array_values( array_unique( $ids ) );
		sort( $ids );
		return $ids;
	}
}
