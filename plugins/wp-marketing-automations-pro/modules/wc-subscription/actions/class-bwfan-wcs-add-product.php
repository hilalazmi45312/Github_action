<?php

final class BWFAN_WCS_Add_Product extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Add Product In Subscription', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This adds a product in the subscription', 'wp-marketing-automations-pro' );
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
		$data_to_set['subscription_id']      = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['product_qty']          = isset( $step_data['product_qty'] ) ? $step_data['product_qty'] : 1;
		$data_to_set['custom_product_price'] = isset( $step_data['custom_product_price'] ) ? $step_data['custom_product_price'] : '';
		$data_to_set['custom_product_name']  = isset( $step_data['custom_product_name'] ) ? $step_data['custom_product_name'] : '';
		$data_to_set['products']             = isset( $step_data['products'][0] ) ? $step_data['products'][0] : [];
		$data_to_set['order_id']             = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

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

		if ( in_array( intval( $product_id ), $product_ids, true ) ) {
			return $this->skipped_response( __( 'Product already exists in the subscription.', 'wp-marketing-automations-pro' ) );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return $this->skipped_response( __( 'Product not found.', 'wp-marketing-automations-pro' ) );
		}

		$add_product_args         = array();
		$add_product_args['name'] = isset( $this->data['custom_product_name'] ) && ! empty( $this->data['custom_product_name'] ) ? $this->data['custom_product_name'] : $product->get_title();

		$quantity = isset( $this->data['product_qty'] ) ? $this->data['product_qty'] : 1;

		$custom_product_price = isset( $this->data['custom_product_price'] ) && $this->data['custom_product_price'] !== '' ? $this->data['custom_product_price'] : $product->get_price();
		$custom_product_price = empty( $custom_product_price ) ? 0 : $custom_product_price;

		$add_product_args['subtotal'] = 0;
		$add_product_args['total']    = 0;
		if ( ! empty( $custom_product_price ) ) {
			$add_product_args['subtotal'] = wc_get_price_excluding_tax( $product, array(
				'price' => $custom_product_price,
				'qty'   => $quantity,
			) );
			$add_product_args['total']    = $add_product_args['subtotal'];
		}

		$item_id = $subscription->add_product( $product, $quantity, $add_product_args );
		$item    = $subscription->get_item( $item_id );
		if ( $item instanceof WC_Order_Item ) {
			$item->add_meta_data( '_fk_automation', 'yes' );
			$item->save();
		}

		$subscription->calculate_totals();
		$subscription->save();

		if ( $item_id ) {
			return $this->success_message( __( 'Product added to the subscription.', 'wp-marketing-automations-pro' ) );
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
				'hint'                => __( 'Action will be skipped if product already exists in the subscription', 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'product_qty',
				'label'       => __( 'Product Quantity', 'wp-marketing-automations-pro' ),
				"type"        => 'number',
				'min'         => '0',
				'placeholder' => __( 'Enter Quantity', 'wp-marketing-automations-pro' ),
				'required'    => true,
			],
			[
				'id'          => 'custom_product_price',
				'label'       => __( 'Product Price', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				'placeholder' => __( 'Enter Price', 'wp-marketing-automations-pro' ),
				'hint'        => __( 'Leave empty to keep the default product price', 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'custom_product_name',
				'label'       => __( 'Product Name', 'wp-marketing-automations-pro' ),
				"type"        => 'text',
				'placeholder' => __( 'Enter Name', 'wp-marketing-automations-pro' ),
				'hint'        => __( 'Leave empty to keep the default product name', 'wp-marketing-automations-pro' ),
			],
		];
	}

	/**
	 * Set default values
	 *
	 * @return string[]
	 */
	public function get_default_values() {
		return [
			'product_qty' => 1,
		];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Add_Product';
