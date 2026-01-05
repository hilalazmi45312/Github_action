<?php

class BWFAN_WC_Order_Again_Url extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'order_again_url';
		$this->tag_description = __( 'Order Again URL', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_order_again_url', array( $this, 'parse_shortcode' ) );
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

		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		$order_id = absint( $order_id ) > 0 ? $order_id : BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' );

		if ( ! absint( $order_id ) > 0 ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order || ! $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed', 'processing' ) ) ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$order_again_url = add_query_arg( 'bwfan-order-again', $order->get_order_key(), wc_get_cart_url() );

		return $this->parse_shortcode_output( $order_again_url, $attr );
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
if ( bwfan_is_woocommerce_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_WC_Order_Again_Url', null, __( 'Order', 'wp-marketing-automations-pro' ) );
}