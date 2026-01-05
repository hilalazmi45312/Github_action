<?php

class BWFAN_WCS_Data extends BWFAN_Merge_Tag {

	private static $instance = null;


	public function __construct() {
		$this->tag_name        = 'subscription_data';
		$this->tag_description = __( 'Subscription Data', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_subscription_data', array( $this, 'parse_shortcode' ) );
		$this->priority          = 46;
		$this->support_date      = true;
		$this->support_date      = true;
		$this->is_delay_variable = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Show the html in popup for the merge tag.
	 */
	public function get_view() {
		$this->get_back_button();
		$this->data_key();
		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
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
			return $this->parse_shortcode_output( $this->get_dummy_preview( $attr ), $attr );
		}

		$item_key = $attr['key'];

		$wc_subscription = BWFAN_Merge_Tag_Loader::get_data( 'wc_subscription' );
		if ( ! empty( $wc_subscription ) && $wc_subscription instanceof WC_Subscription ) {
			$meta = $wc_subscription->get_meta( $item_key );

			/** if value is date then checking format in attribute */
			$meta = $this->get_date_value( $meta, $attr );

			return $this->parse_shortcode_output( $meta, $attr );
		}

		$wcs_id = BWFAN_Merge_Tag_Loader::get_data( 'wc_subscription_id' );
		if ( empty( $wcs_id ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$subscription = wcs_get_subscription( $wcs_id );

		if ( ! $subscription instanceof WC_Subscription ) {
			return $this->parse_shortcode_output( '', $attr );
		}

		$meta = $subscription->get_meta( $item_key );
		$meta = $this->get_date_value( $meta, $attr );

		return $this->parse_shortcode_output( $meta, $attr );
	}

	/**
	 * Get formatted date value
	 *
	 * @param $value
	 *
	 * @return false|string
	 */
	public function get_date_value( $value, $attr ) {
		if ( empty( $value ) ) {
			return $value;
		}

		if ( ! is_array( $attr ) || ! isset( $attr['type'] ) || 'date' !== $attr['type'] || ! isset( $attr['format'] ) || empty( $attr['format'] ) ) {
			return $value;
		}

		return $this->format_datetime( $value, $attr );
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview( $attr ) {
		if ( ! is_array( $attr ) || ! isset( $attr['type'] ) || 'date' !== $attr['type'] || ! isset( $attr['format'] ) || empty( $attr['format'] ) ) {
			return __( 'Key value', 'wp-marketing-automations-pro' );
		}

		return $this->format_datetime( date( 'Y-m-d H:i:s' ), $attr );
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
				'id'          => 'key',
				'label'       => __( 'Meta Key', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => '',
				'placeholder' => '',
				'hint'        => __( 'Input the correct meta key of subscription post to get the data', 'wp-marketing-automations-pro' ),
				'required'    => true,
				'toggler'     => array(),
			],
			[
				'id'          => 'type',
				'type'        => 'select',
				'options'     => [
					[
						'value' => '',
						'label' => __( 'Text', 'wp-marketing-automations-pro' ),
					],
					[
						'value' => 'date',
						'label' => __( 'Date', 'woocommerce' ),
					]
				],
				'label'       => __( 'Select Type', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => false,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				'hint'        => __( 'Select value type', 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Select Date Format', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => "",
				'toggler'     => array(
					'fields'   => array(
						array(
							'id'    => 'type',
							'value' => 'date',
						),
					),
					'relation' => 'AND',
				)
			],
		];
	}

	/**
	 * Return merge tag delay step schema
	 *
	 * @return array[]
	 */
	public function get_delay_setting_schema() {
		$formats      = BWFAN_PRO_Common::get_date_formats();
		$date_formats = array_map( function ( $data ) {
			return [
				'value' => $data['format'],
				'label' => date( $data['format'] ),
			];
		}, $formats );

		return [
			[
				'id'          => 'key',
				'label'       => __( 'Meta Key', 'wp-marketing-automations-pro' ),
				'type'        => 'text',
				'class'       => '',
				'placeholder' => '',
				'hint'        => __( 'Input the correct meta key in order to get the data', 'wp-marketing-automations-pro' ),
				'required'    => true,
				'toggler'     => array(),
			],
			[
				'id'          => 'format',
				'type'        => 'select',
				'options'     => $date_formats,
				'label'       => __( 'Date Format', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => 'Select',
				'hint'        => __( 'Select the date format in which date value is saved on the meta key', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => "",
				'toggler'     => array()
			],
		];
	}
}

/**
 * Register this merge tag to a group.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Merge_Tag_Loader::register( 'wc_subscription', 'BWFAN_WCS_Data', null, __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' ) );
}
