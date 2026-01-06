<?php

final class BWFAN_WC_Add_Custom_Field extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Add Custom Fields', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds/ updates the custom fields', 'wp-marketing-automations-pro' );
		$this->action_priority = 20;
		$this->included_events = array(
			'wc_new_order',
			'wc_order_note_added',
			'wc_order_status_change',
			'wc_product_purchased',
			'wc_product_refunded',
			'wc_product_stock_reduced',
			'wc_order_status_pending',
		);
		$this->support_v2      = true;
		$this->support_v1      = false;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set         = array();
		$custom_fields       = array();
		$custom_fields_value = array();

		$data_to_set['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

		$custom_field_array = isset( $step_data['custom_fields'] ) ? $step_data['custom_fields'] : [];
		if ( ! empty( $custom_field_array ) && is_array( $custom_field_array ) ) {
			$custom_fields       = array_column( $custom_field_array, 'field' );
			$custom_fields_value = array_column( $custom_field_array, 'field_value' );
		}

		$meta_fields = array();
		if ( is_array( $custom_fields ) && ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $key => $field_alias ) {
				if ( empty( $field_alias ) ) {
					continue;
				}
				$meta_fields[ $field_alias ] = BWFAN_Common::decode_merge_tags( $custom_fields_value[ $key ] );
			}
		}

		$data_to_set['order_metas'] = $meta_fields;

		return $data_to_set;
	}

	public function process_v2() {
		if ( 0 === intval( $this->data['order_id'] ) ) {
			return $this->skipped_response( __( 'Order missing.', 'wp-marketing-automations-pro' ) );
		}

		$order = wc_get_order( intval( $this->data['order_id'] ) );
		if ( ! $order instanceof WC_Order ) {
			return $this->skipped_response( __( 'Not a WC order', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $this->data['order_metas'] ) ) {
			return $this->skipped_response( __( 'Custom field(s) are required.', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $this->data['order_metas'] as $meta_key => $meta_value ) {
			$order->update_meta_data( $meta_key, $meta_value );
		}
		$order->save();

		return $this->success_message( __( 'Custom Field added/updated.', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'          => 'custom_fields',
				'type'        => 'repeater',
				'label'       => __( 'Enter Custom Field Key/ values', 'wp-marketing-automations-pro' ),
				"fields"      => [
					[
						'id'          => 'field',
						'type'        => 'text',
						'label'       => "",
						'tip'         => "",
						"description" => "",
						"required"    => false,
						"placeholder" => __( 'Enter Field Key', 'wp-marketing-automations-pro' ),

					],
					[
						"id"          => 'field_value',
						"label"       => "",
						"type"        => 'text',
						"class"       => 'bwfan-input-wrapper',
						"description" => "",
						"required"    => false,
						"placeholder" => __( 'Enter Field value', 'wp-marketing-automations-pro' ),
					]
				],
				'tip'         => "",
				"description" => ""
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );

		if ( ! isset( $data['custom_fields'] ) || empty( $data['custom_fields'] ) ) {
			return '';
		}

		$count = count( $data['custom_fields'] );

		return ( $count > 1 ) ? $count . ' fields' : $count . ' field';
	}

}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WC_Add_Custom_Field';
