<?php

final class BWFAN_WCS_Send_Subscription_Invoice extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Send Subscription Invoice', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action sends the subscription invoice email to the user', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'subscription_id' );
		$this->action_priority = 2;

		// Excluded events which this action does not support.
		$this->included_events = array(
			'wcs_before_end',
			'wcs_before_renewal',
			'wcs_note_added',
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
		$this->support_v2      = true;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $integration_object
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set                    = array();
		$data_to_set['subscription_id'] = $task_meta['global']['wc_subscription_id'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                    = array();
		$data_to_set['subscription_id'] = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['order_id']        = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

		return $data_to_set;
	}

	/**
	 * Execute the current action.
	 * Return 3 for successful execution , 4 for permanent failure.
	 *
	 * @param $action_data
	 *
	 * @return array
	 */
	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();

		if ( $result ) {
			return array(
				'status' => 3,
			);
		}
		$status = array(
			'status'  => $result['status'],
			'message' => $result['msg'],
		);

		return $status;
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		return $this->send_invoice();
	}

	/**
	 * Change subscription status.
	 *
	 * subscription_id, status are required.
	 *
	 * @return array|bool
	 */
	public function send_invoice() {
		$result       = [];
		$subscription = wcs_get_subscription( $this->data['subscription_id'] );

		if ( ! $subscription ) {
			$result['msg']    = __( 'Subscription does not exists', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		do_action( 'woocommerce_before_resend_order_emails', $subscription, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $subscription );

		do_action( 'woocommerce_after_resend_order_email', $subscription, 'customer_invoice' );

		return true;
	}

	public function process_v2() {
		$subscription = wcs_get_subscription( $this->data['subscription_id'] );
		$order_id     = $this->data['order_id'];

		/** then will get it using the order id */
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

		do_action( 'woocommerce_before_resend_order_emails', $subscription, 'customer_invoice' );

		WC()->payment_gateways();
		WC()->shipping();
		WC()->mailer()->customer_invoice( $subscription );

		do_action( 'woocommerce_after_resend_order_email', $subscription, 'customer_invoice' );

		return $this->success_message( __( 'Subscription invoice sent to the customer.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Send_Subscription_Invoice';
