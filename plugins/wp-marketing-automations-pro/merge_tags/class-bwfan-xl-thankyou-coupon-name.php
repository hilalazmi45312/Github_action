<?php

class BWFAN_XL_NextMove_ThankYou extends BWFAN_Merge_Tag {

	private static $instance = null;
	protected $support_v2 = true;
	protected $support_v1 = false;

	public function __construct() {
		$this->tag_name        = 'xl_nextmove_coupon_name';
		$this->tag_description = __( 'NextMove Coupon Name ', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_xl_nextmove_coupon_name', array( $this, 'parse_shortcode' ) );
		$this->support_fallback = false;
		$this->priority         = 2;
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

		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$coupon_id = $order->get_meta( '_xlwcty_coupon' );
		if ( empty( $coupon_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$coupon_name = get_the_title( $coupon_id );
		if ( empty( $coupon_name ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		return $this->parse_shortcode_output( $coupon_name, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 'Coupon Text';
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_xl_nextmove_thankyou_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_XL_NextMove_ThankYou', null, __( 'Order', 'wp-marketing-automations-pro' ) );
}
