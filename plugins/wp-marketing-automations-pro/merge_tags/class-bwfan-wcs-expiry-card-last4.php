<?php

class BWFAN_WCS_Expire_Card_Last_4_Digit extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'expire_card_last4_digit';
		$this->tag_description = __( 'Expire credit card last 4 digit', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_expire_card_last4_digit', array( $this, 'parse_shortcode' ) );
		$this->priority = 50;
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

		$data = BWFAN_Merge_Tag_Loader::get_data();
		if ( ! is_array( $data ) || empty( $data ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$digits = isset( $data['credit_card_last4'] ) ? $data['credit_card_last4'] : '';

		return $this->parse_shortcode_output( $digits, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 121;
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wcs_card_expiry', 'BWFAN_WCS_Expire_Card_Last_4_Digit' );
}
