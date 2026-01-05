<?php

class BWFAN_Contact_Products_purchased extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'contact_products_purchased';
		$this->tag_description = __( 'Product Purchased', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_contact_products_purchased', array( $this, 'parse_shortcode' ) );
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

		$products           = $customer->get_purchased_products();
		$product_list_array = array();

		if ( empty( $products ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		foreach ( $products as $product ) {
			$product_title = get_the_title( $product );

			if ( ! $product_title ) {
				continue;
			}

			$product_list_array[] = $product_title;
		}

		if ( empty( $product_list_array ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$product_list_array = array_unique( $product_list_array );

		$product_lists = implode( ', ', $product_list_array );

		return $this->parse_shortcode_output( $product_lists, $attr );

	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 *
	 */
	public function get_dummy_preview() {
		return 'beanie with logo, belt';
	}

}

/**
 * Register this merge tag to a group.
 */

if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'bwf_contact', 'BWFAN_Contact_Products_purchased', null, __( 'Contact', 'wp-marketing-automations-pro' ) );
}