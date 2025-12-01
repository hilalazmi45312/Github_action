<?php

class BWFAN_WCS_Expire_Card_Type extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'expire_card_type';
		$this->tag_description = __( 'Expire credit card type Ex (visa,master card)', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_expire_card_type', array( $this, 'parse_shortcode' ) );
		$this->priority = 55;
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
		$type = isset( $data['credit_card_type'] ) ? $data['credit_card_type'] : '';

		return $this->parse_shortcode_output( $type, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview() {
		return 'visa';
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wcs_card_expiry', 'BWFAN_WCS_Expire_Card_Type', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
