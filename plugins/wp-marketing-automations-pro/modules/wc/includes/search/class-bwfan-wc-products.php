<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class BWFAN_WC_New_Order_Product {

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function get_slug() {
		return 'wc_new_order_product';
	}

	public function get_options( $search ) {
		$ids    = BWFAN_Common::search_products( $search, true );

		$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_editable' );
		$products        = array();
		foreach ( $product_objects as $product_object ) {
			if ( ! $product_object instanceof WC_Product ) {
				continue;
			}

			if ( 'pending' === $product_object->get_status() ) {
				continue;
			}

			$products[ $product_object->get_id() ] = strip_tags( rawurldecode( BWFAN_Common::get_formatted_product_name( $product_object ) ) );
		}

		return $products;

	}


}

if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
	BWFAN_Load_Custom_Search::register( 'BWFAN_WC_New_Order_Product' );
}

