<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

use Exception;
use WC_COG_Product;

/**
 * Integration for:
 * https://woocommerce.com/products/woocommerce-cost-of-goods/
 */
class WoocommerceCostOfGoods {

	/**
	 * Run the integration.
	 */
	public function run(): void {
		add_filter( 'woocommerce_gpf_custom_field_list', [ $this, 'register_field' ] );
	}

	/**
	 * Register the field so that it can be chosen as a prepopulate option.
	 *
	 * @param $field_list
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function register_field( $field_list ) {
		$field_list['method:WoocommerceCostOfGoods::getCostPrice'] = __( 'Cost price from Cost of Goods extension', 'woocommerce_gpf' );

		return $field_list;
	}

	/**
	 * Generate the cost price value for a product.
	 *
	 * @param $wc_product
	 *
	 * @return string
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
	 */
	public static function getCostPrice( $wc_product ) {
		$cost_price = WC_COG_Product::get_cost( $wc_product );
		if ( '' === $cost_price ) {
			return '';
		}
		$price_string = number_format( (float) $cost_price, 2, '.', '' );

		return $price_string . ' ' . get_woocommerce_currency();
	}
	// phpcs:enable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
}
