<?php

final class BWFAN_WCS_Cancel_Order_Subscriptions extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Cancel Order Associated Subscriptions', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action cancels the WooCommerce Subscription associated with the order.', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'order_id' );
		$this->action_priority = 20;

		// Excluded events which this action does not support.
		$this->included_events = array(
			'wc_order_status_change',
			'wc_new_order',
			'wc_order_note_added',
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

	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <div class="clearfix bwfan_field_desc bwfan-pt-5 bwfan-mb10">
                Note: This action will cancel the active associated subscriptions with the order.
            </div>
        </script>
		<?php
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
		return [];
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set             = array();
		$data_to_set['order_id'] = $automation_data['global']['order_id'];

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

		/** Required fields missing */
		if ( isset( $result['bwfan_response'] ) ) {
			return array(
				'status'  => 4,
				'message' => $result['bwfan_response'],
			);
		}

		return array(
			'status'  => $result['status'],
			'message' => isset( $result['msg'] ) ? $result['msg'] : __( 'Unknown Error Occurred', 'wp-marketing-automations-pro' ),
		);

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

		return $this->cancel_order_associated_subscriptions();
	}

	/**
	 * cancelled subscription.
	 *
	 * order_id is required.
	 *
	 * @return array|bool
	 */
	public function cancel_order_associated_subscriptions() {
		$result = [];

		$order = wc_get_order( $this->data['order_id'] );

		/** check order instance */
		if ( ! $order instanceof WC_Order ) {
			$result['msg']    = __( 'Order does not exists.', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		if ( false === wcs_order_contains_subscription( $order, array( 'parent', 'renewal' ) ) ) {
			$result['msg']    = __( 'Order does not contains any subscription.', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		$subscriptions = wcs_get_subscriptions_for_order( wcs_get_objects_property( $order, 'id' ), array( 'order_type' => array( 'parent', 'renewal' ) ) );
		foreach ( $subscriptions as $subscription ) {

			if ( ! $subscription->has_status( 'active' ) ) {
				$result['msg']    = __( 'Subscription is not active.', 'wp-marketing-automations-pro' );
				$result['status'] = 4;

				return $result;
			}

			try {
				$subscription->update_status( 'cancelled', sprintf( __( 'Subscription status cancelled by FunnelKit automation #%s.', 'wp-marketing-automations-pro' ), $this->data['automation_id'] ) );
				$result['msg']    = sprintf( __( 'Subscription #%s is cancelled.', 'wp-marketing-automations-pro' ), $subscription->get_id() );
				$result['status'] = 3;
			} catch ( Exception $error ) {
				$result['msg']    = $error->getMessage();
				$result['status'] = 4;
			}

		}

		return $result;
	}

	public function process_v2() {
		$order = wc_get_order( $this->data['order_id'] );

		/** check order instance */
		if ( ! $order instanceof WC_Order ) {
			return $this->skipped_response( __( 'Order does not exists', 'wp-marketing-automations-pro' ) );
		}

		if ( false === wcs_order_contains_subscription( $order, array( 'parent', 'renewal' ) ) ) {
			return $this->skipped_response( __( 'Order does not contains any subscription.', 'wp-marketing-automations-pro' ) );
		}

		$subscriptions = wcs_get_subscriptions_for_order( wcs_get_objects_property( $order, 'id' ), array( 'order_type' => array( 'parent', 'renewal' ) ) );
		$message       = '';
		foreach ( $subscriptions as $subscription ) {
			if ( ! $subscription->has_status( 'active' ) ) {
				return $this->skipped_response( __( 'Subscription is not active.', 'wp-marketing-automations-pro' ) );
			}
			try {
				$subscription->update_status( 'cancelled', sprintf( __( 'Subscription status cancelled by FunnelKit automation #%s.', 'wp-marketing-automations-pro' ), $this->data['automation_id'] ) );
				$message = sprintf( __( 'Subscription #%s is cancelled.', 'wp-marketing-automations-pro' ), $subscription->get_id() );
			} catch ( Exception $error ) {
				$message = $error->getMessage();
			}
		}

		return $this->success_message( $message );
	}

	public function get_fields_schema() {
		return [];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Cancel_Order_Subscriptions';
