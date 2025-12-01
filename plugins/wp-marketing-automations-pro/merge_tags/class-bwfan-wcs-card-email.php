<?php

class BWFAN_WCS_Card_Email extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'card_user_email';
		$this->tag_description = __( 'Expire credit card user email', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_card_user_email', array( $this, 'parse_shortcode' ) );
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
		$email = isset( $data['email'] ) ? $data['email'] : '';

		return $this->parse_shortcode_output( $email, $attr );
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
	BWFAN_Merge_Tag_Loader::register( 'wcs_card_expiry', 'BWFAN_WCS_Card_Email', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
