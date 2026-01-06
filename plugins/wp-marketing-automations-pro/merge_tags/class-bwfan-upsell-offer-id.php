<?php

class BWFAN_Upsell_Offer_ID extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'upsell_offer_id';
		$this->tag_description = __( 'Offer ID', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_upsell_offer_id', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_upstroke_offer_id', array( $this, 'parse_shortcode' ) );
		$this->priority = 10;
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
	 * @return int|mixed|void
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview();
		}

		$offer_id = BWFAN_Merge_Tag_Loader::get_data( 'offer_id' );

		return $this->parse_shortcode_output( $offer_id, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 256;
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_offer', 'BWFAN_Upsell_Offer_ID', null, __( 'Upsell', 'wp-marketing-automations-pro' ) );
}
