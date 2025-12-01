<?php

class BWFAN_Upsell_Offer_Accepted_Product_Price extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'upsell_offer_accepted_product_price';
		$this->tag_description = __( 'Offer Accepted Product Price', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_upsell_offer_accepted_product_price', array( $this, 'parse_shortcode' ) );
		$this->priority = 22;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @param $attr
	 *
	 * @return mixed|string|null
	 */
	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview( $attr );
		}

		$order_id = BWFAN_Merge_Tag_Loader::get_data( 'order_id' );
		$order    = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$accepted_product_price = BWFAN_Merge_Tag_Loader::get_data( 'accepted_product_price' );
		if ( empty( $accepted_product_price ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$formatting             = BWFAN_Common::get_formatting_for_wc_price( $attr, $order );
		$accepted_product_price = BWFAN_Common::get_formatted_price_wc( $accepted_product_price, $formatting['raw'], $formatting['currency'] );
		$accepted_product_price = apply_filters( 'bwfan_accepted_product_price_format', $accepted_product_price, $order );

		return $this->parse_shortcode_output( $accepted_product_price, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview( $attr ) {
		$formatting = BWFAN_Common::get_formatting_for_wc_price( $attr, '' );

		return BWFAN_Common::get_formatted_price_wc( 255, $formatting['raw'], $formatting['currency'] );
	}

	/**
	 * Return merge-tag schema
	 *
	 * @return array[]
	 */
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
				'label'       => __( 'Display', 'wp-marketing-automations' ),
				'class'       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Raw', 'wp-marketing-automations' ),
				"required"    => false,
				"description" => ""
			],
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_offer_product', 'BWFAN_Upsell_Offer_Accepted_Product_Price', null, __( 'Upsell', 'wp-marketing-automations-pro' ) );
}
