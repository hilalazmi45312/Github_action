<?php

class BWFAN_Contact_Purchased_Product_category extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'contact_purchased_product_category';
		$this->tag_description = __( 'Purchased Product Category', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_contact_purchased_product_category', array( $this, 'parse_shortcode' ) );
		$this->priority = 32;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Parse the merge tag and return its value.
	 *
	 * @param $attr
	 *
	 * @return mixed|string|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->parse_shortcode_output( $this->get_dummy_preview(), $attr );
		}

		$email = BWFAN_Merge_Tag_Loader::get_data( 'email' );

		if ( ! is_email( $email ) ) {
			return $this->parse_shortcode_output( false, $attr );
		}

		$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

		if ( ! $customer instanceof WooFunnels_Customer ) {
			return $this->parse_shortcode_output( false, $attr );
		}

		$product_cats = $customer->get_purchased_products_cats();

		$product_cat_lists       = '';
		$product_cat_lists_array = [];

		if ( empty( $product_cats ) ) {
			return $this->parse_shortcode_output( $product_cat_lists, $attr );
		}

		$cat_title = '';
		foreach ( $product_cats as $product_cat ) {

			if ( $term = get_term_by( 'id', $product_cat, 'product_cat' ) ) {

				if ( ! isset( $term->name ) ) {
					continue;
				}

				$cat_title = $term->name;
			}

			$product_cat_lists_array[] = $cat_title;
		}

		if ( empty( $product_cat_lists_array ) ) {
			return $this->parse_shortcode_output( $product_cat_lists, $attr );
		}

		$product_cat_lists = implode( ',', $product_cat_lists_array );

		return $this->parse_shortcode_output( $product_cat_lists, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return 'clothing,accessories';
	}


}

/**
 * Register this merge tag to a group.
 */

if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Contact_Purchased_Product_category', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
}