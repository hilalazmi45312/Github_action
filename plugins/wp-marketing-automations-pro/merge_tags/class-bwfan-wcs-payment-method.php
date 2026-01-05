<?php

class BWFAN_WCS_Payment_Method extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'subscription_payment_method';
		$this->tag_description = __( 'Subscription Payment Method', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_payment_method', array( $this, 'parse_shortcode' ) );
		$this->priority = 15;
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

		$data = BWFAN_Merge_Tag_Loader::get_data();
		if ( ! is_array( $data ) || empty( $data ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$subscription_id = isset( $data['wc_subscription_id'] ) ? $data['wc_subscription_id'] : '';

		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}
		$subscription = wcs_get_subscription( $subscription_id );

		if ( ! $subscription instanceof WC_Subscription ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$payment_method = $subscription->get_payment_method_to_display();

		return $this->parse_shortcode_output( $payment_method, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return __( 'Manual Renewal', 'wp-marketing-automations-pro' );
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_Payment_Method', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
