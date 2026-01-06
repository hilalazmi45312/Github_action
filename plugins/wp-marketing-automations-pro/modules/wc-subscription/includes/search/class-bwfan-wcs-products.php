<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BWFAN_WCS_Products {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wcs_products';
	}

	public function get_options( $search = '' ) {
		/*** Filter hook to modify product type */
		$product_types         = apply_filters( 'bwfan_subscription_product_types', array( 'subscription', 'variable-subscription' ) );
		$subscription_products = get_posts( array(
			'post_type'      => 'product',
			'posts_per_page' => 10,
			's'              => $search,
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => $product_types,
				),
			),
		) );

		$product_array = array();

		foreach ( $subscription_products as $product_post ) {
			$product                            = wc_get_product( $product_post->ID );
			$product_array[ $product_post->ID ] = strip_tags( $product->get_formatted_name() );
			if ( in_array( $product->get_type(), array( 'variable', 'variable-subscription' ), true ) ) {
				$variations = $product->get_children();
				foreach ( $variations as $variation_id ) {
					$variation = wc_get_product( $variation_id );

					$product_array[ $variation->get_id() ] = strip_tags( $variation->get_formatted_name() );
				}
			}
		}

		return $product_array;
	}
}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WCS_Products' );
}
