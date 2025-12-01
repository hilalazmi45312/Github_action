<?php

class BWFAN_WCS_Start_Date extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'subscription_start_date';
		$this->tag_description = __( 'Subscription Start Date', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_start_date', array( $this, 'parse_shortcode' ) );
		$this->support_date = true;
		$this->priority     = 20;
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
		$parameters           = [];
		$parameters['format'] = isset( $attr['format'] ) ? $attr['format'] : 'F j';
		if ( isset( $attr['modify'] ) ) {
			$parameters['modify'] = $attr['modify'];
		}
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview( $parameters );
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

		$date = $this->format_datetime( $subscription->get_date_created(), $parameters );

		return $this->parse_shortcode_output( $date, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @param $parameters
	 *
	 * @return string
	 */
	public function get_dummy_preview( $parameters ) {
		return $this->format_datetime( '2018-12-07 13:25:39', $parameters );
	}

	/**
	 * Return mergetag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$formats      = BWFAN_PRO_Common::get_date_formats();
		$date_formats = array_map( function ( $data ) {
			return [
				'value' => $data['format'],
				'label' => date( $data['format'] ),
			];
		}, $formats );

		return [
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Select Date Format', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
				"required"    => false,
				"description" => ""
			],
			[
				'id'          => 'modify',
				'label'       => __( 'Modify (Optional)', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => '',
				'placeholder' => __( 'e.g. +2 months, -1 day, +6 hours', 'wp-marketing-automations-pro' ),
				'required'    => false,
				'toggler'     => array(),
			],
		];
	}

}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_Start_Date', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
