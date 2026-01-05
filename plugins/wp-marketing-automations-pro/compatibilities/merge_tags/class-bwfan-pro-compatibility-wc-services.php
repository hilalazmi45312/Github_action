<?php

class BWFAN_WC_Jetpack_Shipment extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wc_jetpack_shipment';
		$this->tag_description = __( 'WooCommerce Services - Jetpack', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wc_jetpack_shipment', array( $this, 'parse_shortcode' ) );
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
		$tracking_fields = $this->get_view_data();
		$this->get_back_button();

		?>
        <div class="bwfan_mtag_wrap">
            <div class="bwfan_label">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Tracking Field', 'wp-marketing-automations-pro' ); ?></label>
            </div>
            <div class="bwfan_label_val">
                <select id="" class="bwfan-input-wrapper bwfan_tag_select" name="data">
					<?php
					foreach ( $tracking_fields as $slug => $name ) {
						echo '<option value="' . esc_attr__( $slug ) . '">' . esc_attr__( $name ) . '</option>';
					}
					?>
                </select>
            </div>
        </div>
		<?php

		if ( $this->support_fallback ) {
			$this->get_fallback();
		}

		$this->get_preview();
		$this->get_copy_button();
	}

	public function get_view_data() {
		return array(
			'carrier_name_short' => __( 'Carrier Name - Short', 'wp-marketing-automations-pro' ),
			'carrier_name_full'  => __( 'Carrier Name - Full', 'wp-marketing-automations-pro' ),
			'package_name'       => __( 'Package Name', 'wp-marketing-automations-pro' ),
			'tracking_number'    => __( 'Tracking Number', 'wp-marketing-automations-pro' ),
			'tracking_link'      => __( 'Tracking Link', 'wp-marketing-automations-pro' ),
		);
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

		$order_id = absint( BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' ) );
		$order_id = empty( $order_id ) && ! empty( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) ? BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) : $order_id;
		if ( empty( $order_id ) ) {
			return '';
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return '';
		}
		$tracking_items = $order->get_meta( 'wc_connect_labels' );
		if ( ! is_array( $tracking_items ) || empty( $tracking_items ) ) {
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items' );
		}
		if ( ! is_array( $tracking_items ) || empty( $tracking_items ) ) {
			return '';
		}
		$item         = $tracking_items[0];
		$return_value = '';
		$item_key     = isset( $attr['data'] ) && ! empty( $attr['data'] ) ? $attr['data'] : 'tracking_number';

		switch ( $item_key ) {
			case 'carrier_name_short':
				$return_value = isset( $item['tracking_provider'] ) ? strtoupper( $item['tracking_provider'] ) : ( isset( $item['custom_tracking_provider'] ) ? strtoupper( $item['custom_tracking_provider'] ) : '' );

				break;
			case 'carrier_name_full':
				$return_value = isset( $item['custom_tracking_provider'] ) ? $item['custom_tracking_provider'] : '';
				break;
			case 'package_name':
				$return_value = isset( $item['package_name'] ) ? $item['package_name'] : '';
				break;
			case 'tracking_number':
				$return_value = isset( $item['tracking_number'] ) ? $item['tracking_number'] : '';
				break;
			case 'tracking_link':
				$return_value = isset( $item['custom_tracking_link'] ) ? $item['custom_tracking_link'] : '';
				break;
		}

		return $this->parse_shortcode_output( $return_value, $attr );
	}


	public function get_tracking_url( $carrier, $tracking_number ) {
		$tracking_url = '';
		switch ( $carrier ) {
			case 'fedex':
				$tracking_url = 'https://www.fedex.com/apps/fedextrack/?action=track&tracknumbers=' . $tracking_number;
				break;
			case 'usps':
				$tracking_url = 'https://tools.usps.com/go/TrackConfirmAction.action?tLabels=' . $tracking_number;
				break;
		}

		return $tracking_url;
	}

	/**
	 * Show dummy value of the current merge tag.
	 *
	 * @return string
	 */
	public function get_dummy_preview() {
		return '123456789';
	}

	/**
	 * Return merge tag schema
	 *
	 * @return array[]
	 */
	public function get_setting_schema() {
		$tracking_data = $this->get_view_data();
		$options       = [];
		foreach ( $tracking_data as $track_key => $data ) {
			$options[] = [
				'value' => $track_key,
				'label' => $data,
			];
		}

		return [
			[
				'id'          => 'data',
				'type'        => 'select',
				'options'     => $options,
				'label'       => __( 'Select Shipment Tracking Field', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				"required"    => false,
				"description" => ""
			],
		];
	}

}

BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_WC_Jetpack_Shipment' );
