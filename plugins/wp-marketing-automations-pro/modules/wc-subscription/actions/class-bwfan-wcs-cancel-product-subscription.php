<?php

final class BWFAN_WCS_Cancel_Product_Subscription extends BWFAN_Action {

	private static $ins = null;

	private function __construct() {
		$this->action_name     = __( 'Cancel Product Subscriptions', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action cancels the user\'s subscription contains the given product', 'wp-marketing-automations-pro' );
		$this->action_priority = 20;
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
		$data_to_set['subscription_id']       = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['products']              = isset( $step_data['products'] ) ? $step_data['products'] : [];
		$data_to_set['subscription-contains'] = isset( $step_data['subscription-contains'] ) ? $step_data['subscription-contains'] : 'wcs_latest';

		$data_to_set['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
		$data_to_set['email']    = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';

		return $data_to_set;
	}

	public function process_v2() {
		if ( empty( $this->data['products'] ) ) {
			return $this->skipped_response( __( 'No product selected.', 'wp-marketing-automations-pro' ) );
		}

		$email = $this->get_user_email();
		if ( empty( $email ) ) {
			return $this->skipped_response( __( 'Contact not found.', 'wp-marketing-automations-pro' ) );
		}

		$product_ids      = array_column( $this->data['products'], 'id' );
		$subscription_ids = $this->get_subscriptions( $email, $product_ids );
		if ( empty( $subscription_ids ) ) {
			return $this->skipped_response( __( 'Subscription not found.', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $subscription_ids as $subscription_id ) {
			$this->cancel_subscription_by_id( $subscription_id );
		}

		return $this->success_message( __( 'Subscription(s) canceled.', 'wp-marketing-automations-pro' ) );
	}

	protected function get_user_email() {
		/** Subscription */
		$id = $this->data['subscription_id'];
		if ( ! empty( $id ) ) {
			$subscription = wcs_get_subscription( $id );
			if ( $subscription instanceof WC_Subscription ) {
				return $subscription->get_billing_email();
			}
		}

		/** Order */
		$id = $this->data['order_id'];
		if ( ! empty( $id ) ) {
			$order = wc_get_order( $id );
			if ( $order instanceof WC_Order ) {
				return $order->get_billing_email();
			}
		}

		/** cid */
		$id = $this->data['cid'];
		if ( ! empty( $id ) ) {
			$contact = bwf_get_contact( '', $id );
			if ( $contact instanceof WooFunnels_Contact ) {
				return $contact->get_email();
			}
		}

		return $this->data['email'];
	}

	protected function get_subscriptions( $email, $product_ids ) {
		global $wpdb;
		$product_ids_placeholder = implode( ', ', array_fill( 0, count( $product_ids ), '%s' ) );
		if ( BWF_WC_Compatibility::is_hpos_enabled() ) {
			$query = $wpdb->prepare( "SELECT DISTINCT orders.id FROM {$wpdb->prefix}wc_orders AS orders
            INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON orders.id = woi.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id
            WHERE orders.billing_email = %s AND orders.status IN ('wc-active', 'wc-on-hold') AND woi.order_item_type = 'line_item' AND orders.type = 'shop_subscription'
            AND orders.status != 'trash' AND woim.meta_key = '_product_id' AND woim.meta_value IN ($product_ids_placeholder)
            ORDER BY `id` DESC", array_merge( [ $email ], $product_ids ) );
		} else {
			$query = $wpdb->prepare( "SELECT DISTINCT p.ID AS id FROM {$wpdb->prefix}posts AS p INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
         	INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON p.ID = woi.order_id INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS woim ON woi.order_item_id = woim.order_item_id
        	 WHERE pm.meta_key = '_billing_email' AND pm.meta_value = %s AND p.post_type = 'shop_subscription' AND p.post_status IN ('wc-active', 'wc-on-hold') AND woi.order_item_type = 'line_item'
         	AND woim.meta_key = '_product_id' AND woim.meta_value IN ($product_ids_placeholder)
         	ORDER BY `ID` DESC", array_merge( [ $email ], $product_ids ) );
		}
		if ( 'wcs_latest' === $this->data['subscription-contains'] ) {
			$query .= " LIMIT 0,1";
		}

		return $wpdb->get_col( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Cancel subscription by ID
	 *
	 * @param $subscription_id
	 *
	 * @return void
	 */
	public function cancel_subscription_by_id( $subscription_id ) {
		$subscription = wcs_get_subscription( $subscription_id );
		if ( ! $subscription instanceof WC_Subscription ) {
			return;
		}

		try {
			$subscription->update_status( 'cancelled', sprintf( __( 'Subscription cancelled by FunnelKit Automation #%s.', 'wp-marketing-automations-pro' ), $this->data['automation_id'] ) );
		} catch ( Exception $error ) {
		}
	}

	public function get_fields_schema() {
		return [
			[
				'id'                  => 'products',
				'label'               => __( 'Select Products', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcs_products',
					'slug'      => 'wcs_products',
					'labelText' => __( 'WooCommerce Subscription products', 'wp-marketing-automations-pro' ),
				],
				'class'               => '',
				'placeholder'         => '',
				'required'            => true,
				'multiple'            => true,
				"hint"                => __( "Subscriptions with Active or On-hold status will be fetched.", 'wp-marketing-automations-pro' ),
			],
			[
				'id'       => 'subscription-contains',
				'label'    => __( 'Cancel Subscription(s)', 'wp-marketing-automations-pro' ),
				'type'     => 'radio',
				'options'  => [
					[
						'label' => __( 'Recent', 'wp-marketing-automations-pro' ),
						'value' => 'wcs_latest',
					],
					[
						'label' => __( 'All', 'wp-marketing-automations-pro' ),
						'value' => 'wcs_all',
					],
				],
				"class"    => 'bwfan-input-wrapper',
				"tip"      => "",
				"required" => false,
				"hint"     => __( "<b>Recent</b> option will cancel the latest active subscription.</br><b>All</b> option will cancel all the active subscriptions.", 'wp-marketing-automations-pro' ),
			],
		];
	}

	public function get_default_values() {
		return [
			'subscription-contains' => 'wcs_latest',
		];
	}
}

return 'BWFAN_WCS_Cancel_Product_Subscription';
