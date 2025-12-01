<?php

class BWFAN_WCS_Total extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'subscription_total';
		$this->tag_description = __( 'Subscription Total', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_total', array( $this, 'parse_shortcode' ) );
		$this->priority = 30;
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
			return $this->get_dummy_preview( $attr );
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

		$formatting = BWFAN_Common::get_formatting_for_wc_price( $attr, $subscription );
		$total      = BWFAN_Common::get_formatted_price_wc( $subscription->get_total(), $formatting['raw'], $formatting['currency'] );

		return $this->parse_shortcode_output( $total, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return integer
	 */
	public function get_dummy_preview( $attr ) {
		$formatting = BWFAN_Common::get_formatting_for_wc_price( $attr, '' );

		return BWFAN_Common::get_formatted_price_wc( 255, $formatting['raw'], $formatting['currency'] );
	}

	public function get_setting_schema() {
		$options = [
			[
				'value' => 'raw',
				'label' => __( 'Raw', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'formatted',
				'label' => __( 'Formatted', 'wp-marketing-automations-pro' ),
			],
			[
				'value' => 'formatted-currency',
				'label' => __( 'Formatted with currency', 'wp-marketing-automations-pro' ),
			],
		];

		return [
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Display', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Raw', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => ""
			],
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_Total', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
