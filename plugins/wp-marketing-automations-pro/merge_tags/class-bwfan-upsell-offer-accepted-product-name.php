<?php

class BWFAN_Upsell_Offer_Accepted_Product_Name extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'upsell_offer_accepted_product_name';
		$this->tag_description = __( 'Offer Accepted Product Name', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_upsell_offer_accepted_product_name', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_upstroke_offer_accepted_product_name', array( $this, 'parse_shortcode' ) );
		$this->priority = 22;
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
			return $this->get_dummy_preview();
		}

		$accepted_product_name = BWFAN_Merge_Tag_Loader::get_data( 'accepted_product_name' );

		return $this->parse_shortcode_output( $accepted_product_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return __( 'Product name', 'wp-marketing-automations-pro' );
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_offer_product', 'BWFAN_Upsell_Offer_Accepted_Product_Name', null, __( 'Upsell', 'wp-marketing-automations-pro' ) );
}
