<?php

final class BWFAN_WCS_Add_Custom_Field extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Add Custom Fields', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds/ updates the custom fields', 'wp-marketing-automations-pro' );
		$this->action_priority = 20;
		$this->support_v2      = true;
		$this->support_v1      = false;

		$this->included_events = array(
			'wcs_before_end',
			'wcs_before_renewal',
			'wcs_card_expiry',
			'wcs_created',
			'wcs_renewal_payment_complete',
			'wcs_renewal_payment_failed',
			'wcs_status_changed',
			'wcs_trial_end',
			'wc_new_order',
			'wc_order_note_added',
			'wc_order_status_change',
			'wc_product_purchased',
			'wc_product_refunded',
			'wc_product_stock_reduced'
		);
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

		$data_to_set['subscription_id'] = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['order_id']        = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

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

		$data_to_set['subscription_metas'] = $meta_fields;

		return $data_to_set;
	}

	public function process_v2() {
		$subscription = wcs_get_subscription( intval( $this->data['subscription_id'] ) );
		$order_id     = intval( $this->data['order_id'] );

		/** if not subscription then get it using the order id */
		if ( ! $subscription ) {
			$subscriptions = BWFAN_PRO_Common::order_contains_subscriptions( $order_id );

			/** if still no subscriptions exists with order then skipped */
			if ( false === $subscriptions ) {
				return $this->skipped_response( __( 'No subscription associated with order.', 'wp-marketing-automations-pro' ) );
			}

			$subscriptions = array_values( $subscriptions );
			$subscription  = $subscriptions[0];
		}

		if ( ! $subscription ) {
			return $this->skipped_response( __( 'Subscription does not exists', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $this->data['subscription_metas'] ) ) {
			return $this->skipped_response( __( 'Custom field(s) are required.', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $this->data['subscription_metas'] as $meta_key => $meta_value ) {
			$subscription->update_meta_data( $meta_key, $meta_value );
		}
		$subscription->save();

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
					],
					[
						"id"          => 'field_value',
						"label"       => "",
						"type"        => 'text',
						"class"       => 'bwfan-input-wrapper',
						"description" => "",
						"required"    => false,
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
return 'BWFAN_WCS_Add_Custom_Field';
