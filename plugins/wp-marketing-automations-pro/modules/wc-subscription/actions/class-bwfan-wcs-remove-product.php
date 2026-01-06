<?php

final class BWFAN_WCS_Remove_Product extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Remove Product From Subscription', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This removes a product from the subscription', 'wp-marketing-automations-pro' );
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
			'wcs_note_added',
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
		$data_to_set['subscription_id'] = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['products']        = isset( $step_data['products'][0] ) ? $step_data['products'][0] : [];
		$data_to_set['order_id']        = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

		return $data_to_set;
	}

	public function process_v2() {
		if ( ! isset( $this->data['subscription_id'] ) && ! isset( $this->data['order_id'] ) ) {
			return $this->skipped_response( __( 'Subscription not found.', 'wp-marketing-automations-pro' ) );
		}

		$sub_id = $this->data['subscription_id'];
		if ( empty( $sub_id ) && ! empty( $this->data['order_id'] ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $this->data['order_id'], array( 'order_type' => 'any' ) );
			if ( ! empty( $subscriptions ) ) {
				$subscription_keys = array_keys( $subscriptions );
				$sub_id            = reset( $subscription_keys );
			}
		}

		if ( empty( $sub_id ) ) {
			return $this->skipped_response( __( 'Subscription not found.', 'wp-marketing-automations-pro' ) );
		}

		$subscription = wcs_get_subscription( $sub_id );
		if ( ! $subscription instanceof WC_Subscription ) {
			return $this->skipped_response( __( 'Subscription not found.', 'wp-marketing-automations-pro' ) );
		}

		$product_id  = $this->data['products']['id'];
		$product_ids = array_map( function ( $item ) {
			/** @var $item WC_Order_Item_Product */
			return $item->get_product_id();
		}, $subscription->get_items() );

		if ( ! in_array( intval( $product_id ), $product_ids, true ) ) {
			return $this->skipped_response( __( 'Product does not exist in the subscription.', 'wp-marketing-automations-pro' ) );
		}

		$item_key = array_search( $product_id, $product_ids );

		if ( false !== $item_key ) {
			$subscription->remove_item( $item_key );
			$subscription->calculate_totals();
			$subscription->save();

			return $this->success_message( __( 'Product removed from the subscription.', 'wp-marketing-automations-pro' ) );
		}

		return $this->error_response( __( 'Some error occurred.', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * v2 Method: Get field Schema
	 *
	 * @return array[]
	 */
	public function get_fields_schema() {
		return [
			[
				'id'                  => 'products',
				'label'               => __( 'Select Product', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcs_products',
					'slug'      => 'wcs_products',
					'labelText' => 'WooCommerce Subscription products '
				],
				'class'               => '',
				'placeholder'         => '',
				'required'            => true,
				'multiple'            => false,
			]
		];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Remove_Product';
