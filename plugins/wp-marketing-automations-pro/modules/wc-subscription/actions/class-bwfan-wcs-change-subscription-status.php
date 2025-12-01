<?php

final class BWFAN_WCS_Change_Subscription_Status extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Subscription Status', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes the WooCommerce Subscription status', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'subscription_id', 'subs_status' );
		$this->action_priority = 1;

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
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'subscription_status_options', $data );
		}
	}

	public function get_view_data() {
		return wcs_get_subscription_statuses();
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_subscription_status = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'subscription_status')) ? data.actionSavedData.data.subscription_status : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Status', 'woocommerce' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][subscription_status]">
                    <option value=""><?php echo esc_html__( 'Choose Status', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'subscription_status_options') && _.isObject(data.actionFieldsOptions.subscription_status_options) ) {
                    _.each( data.actionFieldsOptions.subscription_status_options, function( value, key ){
                    selected = (key == selected_subscription_status) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
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
		$data_to_set                    = array();
		$data_to_set['subs_status']     = $task_meta['data']['subscription_status'];
		$data_to_set['subscription_id'] = $task_meta['global']['wc_subscription_id'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                    = array();
		$data_to_set['subs_status']     = $step_data['subscription_status'];
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

		return array(
			'status'  => $result['status'],
			'message' => $result['msg'],
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

		return $this->change_status();
	}

	/**
	 * Change subscription status.
	 *
	 * subscription_id, status are required.
	 *
	 * @return array|bool
	 */
	public function change_status() {
		$result = [];

		$subscription = wcs_get_subscription( $this->data['subscription_id'] );
		if ( ! $subscription ) {
			$result['msg']    = __( 'Subscription does not exists', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		try {
			$subscription->update_status( $this->data['subs_status'], sprintf( __( 'Subscription status changed by FunnelKit automation #%s.', 'wp-marketing-automations-pro' ), $this->data['automation_id'] ) );
			$result = true;
		} catch ( Exception $error ) {
			$result['msg']    = $error->getMessage();
			$result['status'] = 4;
		}

		return $result;
	}

	public function process_v2() {
		$subscription  = wc_get_order( $this->data['subscription_id'] );
		$subscriptions = [];
		$order_id      = $this->data['order_id'];

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

		try {
			$subscription->update_status( $this->data['subs_status'], sprintf( __( 'Subscription status changed by FunnelKit automation #%s.', 'wp-marketing-automations-pro' ), $this->data['automation_id'] ) );

			return $this->success_message( __( 'Subscription status changed.', 'wp-marketing-automations-pro' ) );
		} catch ( Exception $error ) {
			return $this->error_response( $error->getMessage() );
		}
	}

	public function get_fields_schema() {
		$options = array_replace( [ '' => 'Select' ], $this->get_view_data() );
		$options = BWFAN_PRO_Common::prepared_field_options( $options );

		return [
			[
				'id'          => 'subscription_status',
				'label'       => __( "Status", 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $options,
				'placeholder' => __( 'Choose Status', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				'tip'         => "",
				"description" => "",
				"required"    => false,
			],
		];
	}

	public function get_desc_text( $data ) {
		$data   = json_decode( wp_json_encode( $data ), true );
		$status = $this->get_view_data();
		if ( ! is_array( $data ) || ! isset( $data['subscription_status'] ) ) {
			return reset( $status );
		}

		return $status[ $data['subscription_status'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Change_Subscription_Status';
