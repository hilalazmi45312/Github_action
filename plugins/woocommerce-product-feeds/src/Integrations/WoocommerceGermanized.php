<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use WC_Product;
use WooCommerce_Germanized;
use function wc_get_product;

/**
 * Integration for:
 * https://wordpress.org/plugins/woocommerce-germanized/
 */
class WoocommerceGermanized {

	/**
	 * Mapping from germanized's list of UOMs to Google's accepted UOMs.
	 *
	 * @var array
	 */
	const UOM_MAP = [
		'lbs' => 'lb',
	];

	/**
	 * Check versions and run the integration if suitable.
	 */
	public function run(): void {
		// Check version is 3.7.0 or higher.
		$instance = WooCommerce_Germanized::instance();
		if ( empty( $instance->version ) || version_compare( '3.7.0', $instance->version, '>=' ) ) {
			return;
		}
		add_filter( 'woocommerce_gpf_custom_field_list', [ $this, 'register_fields' ] );
	}

	/**
	 * @param array $fields
	 */
	public function register_fields( array $fields ): array {
		$fields['disabled:wcgermanized'] = __(
			'-- Fields from "WooCommerce Germanized" --',
			'woocommerce_gpf'
		);

		$fields['meta:_ts_gtin'] = _x(
			'GTIN field',
			'Name of field from WooCommerce Germanized extension',
			'woocommerce_gpf'
		);

		$fields['meta:_ts_mpn'] = _x(
			'MPN field',
			'Name of field from WooCommerce Germanized extension',
			'woocommerce_gpf'
		);

		// Note see class_alias() in bootstrap file.
		$fields['method:WoocommerceProductFeedsWoocommerceGermanized::get_product_units'] = _x(
			'Product units',
			'Name of field from WooCommerce Germanized extension',
			'woocommerce_gpf'
		);

		// Note see class_alias() in bootstrap file.
		$fields['method:WoocommerceProductFeedsWoocommerceGermanized::get_unit_price_units'] = _x(
			'Unit price units',
			'Name of field from WooCommerce Germanized extension',
			'woocommerce_gpf'
		);

		return $fields;
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public static function get_product_units( WC_Product $wc_product ): string {
		$uom          = self::get_uom_for_product( $wc_product );
		$unit_product = $wc_product->get_meta( '_unit_product', true );
		if ( empty( $unit_product ) || empty( $uom ) ) {
			return '';
		}

		return $unit_product . $uom;
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public static function get_unit_price_units( WC_Product $wc_product ) {
		$uom              = self::get_uom_for_product( $wc_product );
		$unit_price_units = $wc_product->get_meta( '_unit_base', true );
		if ( empty( $unit_price_units ) || empty( $uom ) ) {
			return '';
		}

		return $unit_price_units . $uom;
	}

	/**
	 * @param WC_Product $wc_product
	 *
	 * @return string
	 */
	public static function get_uom_for_product( WC_Product $wc_product ) {
		$specific_uom = (string) $wc_product->get_meta( '_unit', true );
		if ( ! empty( $specific_uom ) ) {
			return self::UOM_MAP[ $specific_uom ] ?? $specific_uom;
		}
		$parent_id = $wc_product->get_parent_id();
		if ( empty( $parent_id ) ) {
			return '';
		}
		$parent_product = wc_get_product( $parent_id );
		$parent_uom     = (string) $parent_product->get_meta( '_unit', true );

		return self::UOM_MAP[ $parent_uom ] ?? $parent_uom;
	}
}
