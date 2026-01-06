<?php

class BWFAN_Upsell_Failed_Order_Link extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'upsell_failed_order_link';
		$this->tag_description = __( 'Offer Failed Order Link', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_upsell_failed_order_link', array( $this, 'parse_shortcode' ) );
		add_shortcode( 'bwfan_upstroke_failed_order_link', array( $this, 'parse_shortcode' ) );
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

		$failed_order_payment_link = BWFAN_Merge_Tag_Loader::get_data( 'failed_order_payment_link' );

		return $this->parse_shortcode_output( $failed_order_payment_link, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return home_url();
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_failed_order', 'BWFAN_Upsell_Failed_Order_Link', null, __( 'Order', 'wp-marketing-automations-pro' ) );
}
