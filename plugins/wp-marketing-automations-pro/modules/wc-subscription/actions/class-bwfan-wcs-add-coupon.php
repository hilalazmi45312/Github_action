<?php

final class BWFAN_WCS_Add_Coupon extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Add Coupon', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'Assign a coupon to discount future payments for a subscriptions', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'subscription_id', 'subscription_coupon_id' );
		$this->action_priority = 1;

		// Excluded events which this action does not support.
		$this->included_events = array(
			'wcs_before_end',
			'wcs_note_added',
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
			'wc_product_stock_reduced',
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'subscription_add_coupon', $data );
		}
	}

	public function get_view_data( $search = '' ) {
		$get_coupon_list = BWFAN_Common::get_coupon( $search );
		if ( empty( $get_coupon_list['results'] ) ) {
			return array();
		}

		$coupon_list = array();
		foreach ( $get_coupon_list['results'] as $index => $code ) {
			$discount_type = get_post_meta( $code['id'], 'discount_type', true );
			if ( in_array( $discount_type, array( 'recurring_fee', 'recurring_percent' ), true ) ) {
				$coupon_list[ $code['id'] ] = $code['text'];
			}
		}

		return $coupon_list;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_subscription_add_coupon = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'selected_subscription_add_coupon')) ? data.actionSavedData.data.selected_subscription_add_coupon : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Coupon', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][selected_subscription_add_coupon]">
                    <option value=""><?php echo esc_html__( 'Choose Coupon', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'subscription_add_coupon') && _.isObject(data.actionFieldsOptions.subscription_add_coupon) ) {
                    _.each( data.actionFieldsOptions.subscription_add_coupon, function( value, key ){
                    selected = (key == selected_subscription_add_coupon) ? 'selected' : '';
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
		$data_to_set                           = array();
		$data_to_set['subscription_coupon_id'] = $task_meta['data']['selected_subscription_add_coupon'];
		$data_to_set['subscription_id']        = $task_meta['global']['wc_subscription_id'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                           = array();
		$data_to_set['subscription_coupon_id'] = isset( $step_data['selected_subscription_add_coupon'][0]['id'] ) ? $step_data['selected_subscription_add_coupon'][0]['id'] : 0;
		$data_to_set['subscription_id']        = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
		$data_to_set['order_id']               = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

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

		if ( true === $result ) {
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

		return $this->add_coupon();
	}

	/**
	 * add coupon to subscription.
	 *
	 * subscription_id, subscription_coupon_id are required.
	 *
	 * @return array|bool
	 */
	public function add_coupon() {
		$result = [];

		$subscription     = wcs_get_subscription( $this->data['subscription_id'] );
		$automation_data  = BWFAN_Core()->automations->get_automation_data_meta( $this->automation_id );
		$automation_title = isset( $automation_data['title'] ) ? $automation_data['title'] : '';
		$coupon           = new WC_Coupon( $this->data['subscription_coupon_id'] );
		if ( ! $subscription || ! $coupon ) {
			$result['msg']    = __( 'Subscription or Coupon does not exists', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		$response = $subscription->apply_coupon( $coupon );
		if ( is_wp_error( $response ) ) {

			$result['msg']    = $response->get_error_message();
			$result['status'] = 4;

			return $result;
		}

		$subscription->add_order_note( sprintf( __( '%1$s automation run: added coupon %2$s to subscription. (Automation ID: %3$d)', 'wp-marketing-automations-pro' ), $automation_title, $coupon->get_code(), $this->automation_id ), false, false );

		return true;
	}

	public function process_v2() {
		$subscription = wcs_get_subscription( $this->data['subscription_id'] );

		$automation_data  = BWFAN_Core()->automations->get_automation_data_meta( $this->automation_id );
		$automation_title = isset( $automation_data['title'] ) ? $automation_data['title'] : '';

		$coupon   = new WC_Coupon( $this->data['subscription_coupon_id'] );
		$order_id = $this->data['order_id'];

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

		if ( ! $subscription instanceof WC_Subscription || ! $coupon ) {
			return $this->skipped_response( __( 'Subscription or Coupon does not exists.', 'wp-marketing-automations-pro' ) );
		}

		$response = $subscription->apply_coupon( $coupon );
		if ( is_wp_error( $response ) ) {
			return $this->error_response( $response->get_error_message() );
		}

		$subscription->add_order_note( sprintf( __( '%1$s automation run: added coupon %2$s to subscription. (Automation ID: %3$d)', 'wp-marketing-automations-pro' ), $automation_title, $coupon->get_code(), $this->automation_id ), false, false );

		return $this->success_message( __( 'Coupon added.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {

		return [
			[
				"id"                  => 'selected_subscription_add_coupon',
				"label"               => __( 'Coupon', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcs_coupons',
					'slug'      => 'wcs_coupons',
					'labelText' => 'Coupon'
				],
				"allowFreeTextSearch" => false,
				"required"            => false,
				"errorMsg"            => "",
				"multiple"            => false
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['selected_subscription_add_coupon'] ) || empty( $data['selected_subscription_add_coupon'] ) ) {
			return '';
		}
		$coupons = [];
		foreach ( $data['selected_subscription_add_coupon'] as $coupon ) {
			if ( ! isset( $coupon['name'] ) || empty( $coupon['name'] ) ) {
				continue;
			}
			$coupons[] = $coupon['name'];
		}

		return $coupons;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Add_Coupon';
