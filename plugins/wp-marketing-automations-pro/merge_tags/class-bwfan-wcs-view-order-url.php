<?php

class BWFAN_WCS_View_Url extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'subscription_view_url';
		$this->tag_description = __( 'Subscription View Url', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_view_url', array( $this, 'parse_shortcode' ) );
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

		$url = $subscription->get_view_order_url();

		return $this->parse_shortcode_output( $url, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return wc_get_endpoint_url( 'view-subscription', 1, wc_get_page_permalink( 'myaccount' ) );
	}


}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_View_Url', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
