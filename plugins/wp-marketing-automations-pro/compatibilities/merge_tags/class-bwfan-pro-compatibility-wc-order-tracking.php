<?php

class BWFAN_WC_Order_Tracking extends BWFAN_Merge_Tag {
	private static $instance = null;

	public function __construct() {
		$this->tag_name        = 'wc_order_tracking';
		$this->tag_description = __( 'WooCommerce order Tracking details', 'wp-marketing-automations-pro' );
		add_shortcode( 'bwfan_wc_order_tracking', array( $this, 'parse_shortcode' ) );

		$this->support_v1 = false;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_view_data() {
		return array(
			'Items_name'               => __( 'Tracking Item Name', 'wp-marketing-automations-pro' ),
			'carrier_name'             => __( 'Carrier Name', 'wp-marketing-automations-pro' ),
			'tracking_number'          => __( 'Tracking Number', 'wp-marketing-automations-pro' ),
			'tracking_url_show'        => __( 'Tracking Link', 'wp-marketing-automations-pro' ),
			'order_tracking_formatted' => __( 'Formatted', 'wp-marketing-automations-pro' ),
		);
	}

	public function parse_shortcode( $attr ) {
		if ( true === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ) {
			return $this->get_dummy_preview( $attr );
		}

		$order_id = absint( BWFAN_Merge_Tag_Loader::get_data( 'wc_order_id' ) );
		$order_id = empty( $order_id ) && ! empty( BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) ) ? BWFAN_Merge_Tag_Loader::get_data( 'order_id' ) : $order_id;

		if ( empty( $order_id ) ) {
			return '';
		}

		$tracking_data = self::get_tracking_data( $order_id );
		if ( empty( $tracking_data ) ) {
			return '';
		}

		$item_key = ( isset( $attr['data'] ) && ! empty( $attr['data'] ) ) ? $attr['data'] : 'tracking_number';
		if ( 'order_tracking_formatted' === $item_key ) {
			$return_value = $this->get_formatted_order_tracking( $tracking_data );

			return $this->parse_shortcode_output( $return_value, $attr );
		}

		$return_value = 'Items_name' === $item_key ? implode( ', ', array_map( function ( $item ) {
			return $item['item_name'];
		}, $tracking_data ) ) : implode( ', ', array_column( $tracking_data, $item_key ) );

		return $this->parse_shortcode_output( $return_value, $attr );
	}

	public function get_formatted_order_tracking( $tracking_items ) {
		$formatted_order_tracking = array_map( function ( $item ) {
			return "{$item['item_name']}\n{$item['carrier_name']} ({$item['tracking_number']})\n{$item['tracking_url_show']}";
		}, $tracking_items );

		return implode( ", \n", $formatted_order_tracking );
	}

	public function get_dummy_preview( $attr ) {
		switch ( $attr['data'] ) {
			case 'Items_name':
				return 'Album, Belt';
			case 'carrier_name':
				return 'Carrier Name, Carrier Name 2';
			case 'tracking_url_show':
				return 'https://example.com, https://example2.com';
			case 'order_tracking_formatted':
				return "Item Name,\nCarrier Name (Tracking Code)\nhttps://example.com";
			default:
				return 'ABCD123, ABCDE1234';
		}
	}

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
				'label'       => __( 'Select order Tracking Field', 'wp-marketing-automations-pro' ),
				'class'       => 'bwfan-input-wrapper',
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				'required'    => false,
				'description' => ''
			],
		];
	}

	public static function get_tracking_data( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || empty( $order->get_items() ) ) {
			return [];
		}

		if ( class_exists( 'VI_WOOCOMMERCE_ORDERS_TRACKING_DATA' ) ) {
			return VI_WOOCOMMERCE_ORDERS_TRACKING_DATA::get_tracking_numbers( $order_id );
		}

		$tracking_data = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			$item_tracking_data = wc_get_order_item_meta( $item_id, '_vi_wot_order_item_tracking_data', true );
			if ( empty( $item_tracking_data ) ) {
				continue;
			}
			$item_tracking_data = json_decode( $item_tracking_data, true );
			if ( empty( $item_tracking_data ) || ! is_array( $item_tracking_data ) ) {
				continue;
			}
			foreach ( $item_tracking_data as $tracking ) {
				// Check if tracking number already exists to prevent duplicates
				if ( ! in_array( $tracking['tracking_number'], array_column( $tracking_data, 'tracking_number' ) ) ) {
					$tracking_data[] = [
						'tracking_number'   => $tracking['tracking_number'] ?? '',
						'carrier_name'      => $tracking['carrier_name'] ?? '',
						'item_name'         => $item->get_name(),
						'tracking_url_show' => str_replace( '{tracking_number}', $tracking['tracking_number'] ?? '', $tracking['carrier_url'] ?? '' ),
					];
				}
			}
		}

		return $tracking_data;
	}
}

BWFAN_Merge_Tag_Loader::register( 'wc_order', 'BWFAN_WC_Order_Tracking' );
