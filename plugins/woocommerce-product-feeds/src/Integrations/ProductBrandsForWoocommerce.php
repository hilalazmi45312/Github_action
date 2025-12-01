<?php

namespace Ademti\WoocommerceProductFeeds\Integrations;

/**
 * Integration for:
 * https://woocommerce.com/products/product-brands-for-woocommerce/
 */
class ProductBrandsForWoocommerce {

	/**
	 * Add filters.
	 */
	public function run(): void {
		add_filter( 'woocommerce_gpf_prepopulate_options', [ $this, 'register_taxonomy' ], 10, 2 );
	}

	/**
	 * Register the field with a descriptive name so it can be chosen as a prepopulate option.
	 *
	 * @param $field_list
	 * @param $key
	 *
	 * @return mixed
	 */
	public function register_taxonomy( $field_list, $key ) {
		if ( 'description' === $key || ! isset( $field_list['tax:product-brand'] ) ) {
			return $field_list;
		}
		$field_list['tax:product-brand'] = __( 'Brand (from "Product Brands for WooCommerce")', 'woocommerce_gpf' );

		return $field_list;
	}
}
