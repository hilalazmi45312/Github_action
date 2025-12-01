<?php

class BWFAN_WC_Shipment_Tracking extends BWFAN_Merge_Tag {

	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wc_shipment_tracking';
		$this->tag_description = __( 'WooCommerce Shipment Tracking details', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wc_shipment_tracking', array( $this, 'parse_shortcode' ) );
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
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Shipment Tracking Field', 'wp-marketing-automations-pro' ); ?></label>
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
			'tracking_number'             => __( 'Tracking Number', 'wp-marketing-automations-pro' ),
			'formatted_tracking_provider' => __( 'Tracking Provider', 'wp-marketing-automations-pro' ),
			'formatted_tracking_link'     => __( 'Tracking Link', 'wp-marketing-automations-pro' ),
			'date_shipped'                => __( 'Date Shipped', 'wp-marketing-automations-pro' ),
			'shipping_formatted'          => __( 'Formatted', 'wp-marketing-automations-pro' ),
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

		$tracking_items = wc_shipment_tracking()->actions->get_tracking_items( $order_id, true );
		if ( count( $tracking_items ) < 1 ) {
			return '';
		}

		$date_format = get_option( 'date_format', 'Y-m-d' );
		$item_key    = ( isset( $attr['data'] ) && ! empty( $attr['data'] ) ) ? $attr['data'] : 'tracking_number';
		if ( 'shipping_formatted' === $item_key ) {
			$return_value = $this->get_formatted_shipping( $tracking_items, $date_format );

			return $this->parse_shortcode_output( $return_value, $attr );
		}

		$return_value = 'date_shipped' === $item_key ? implode( ', ', array_map( function ( $item ) use ( $date_format ) {
			return date( $date_format, $item['date_shipped'] );
		}, $tracking_items ) ) : implode( ', ', array_column( $tracking_items, $item_key ) );

		return $this->parse_shortcode_output( $return_value, $attr );
	}

	/**
	 * Format shipping information.
	 *
	 * @param array $tracking_items Tracking items array.
	 *
	 * @return string Formatted shipping information.
	 */
	public function get_formatted_shipping( $tracking_items, $date_format ) {
		$formatted_shipping = array_map( function ( $item ) use ( $date_format ) {
			$output = "{$item['formatted_tracking_provider']} ({$item['tracking_number']})";
			$output .= "\n{$item['formatted_tracking_link']}";
			$output .= "\n" . date( $date_format, $item['date_shipped'] );

			return $output;
		}, $tracking_items );

		return implode( "\n\n", $formatted_shipping );
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

BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_WC_Shipment_Tracking' );
