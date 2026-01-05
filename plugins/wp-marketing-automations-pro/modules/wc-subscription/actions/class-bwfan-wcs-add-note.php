<?php

final class BWFAN_WCS_Add_Note extends BWFAN_Action {

	private static $ins = null;
	private $wcs_note_types = [];

	protected function __construct() {
		$this->action_name     = __( 'Add Note', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( "Added note to the subscription", 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'subscription_id', 'note_type', 'notes' );
		$this->action_priority = 1;
		$this->wcs_note_types  = [
			'private' => __( 'Private', 'wp-marketing-automations-pro' ),
			'public'  => __( 'Customer', 'wp-marketing-automations-pro' ),
		];

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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'wcs_note_types', $this->wcs_note_types );
		}
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            wcs_note_type = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'wcs_note_type')) ? data.actionSavedData.data.wcs_note_type : '';
            wcs_notes = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'wcs_notes')) ? data.actionSavedData.data.wcs_notes : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Note Type', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][wcs_note_type]">
                    <option value=""><?php echo esc_html__( 'Select', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'wcs_note_types') && _.isObject(data.actionFieldsOptions.wcs_note_types) ) {
                    _.each( data.actionFieldsOptions.wcs_note_types, function( value, key ){
                    selected = (key == wcs_note_type) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>

            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Notes', 'wp-marketing-automations-pro' ); ?></label>
                <textarea required="" class="bwfan-input-wrapper" rows="3" placeholder="Subscription Notes" name="bwfan[{{data.action_id}}][data][wcs_notes]" spellcheck="false">{{wcs_notes}}</textarea>
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
		$data_to_set['note_type']       = $task_meta['data']['wcs_note_type'];
		$data_to_set['subscription_id'] = $task_meta['global']['wc_subscription_id'];
		$data_to_set['notes']           = $task_meta['data']['wcs_notes'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                    = array();
		$data_to_set['note_types']      = $step_data['wcs_note_type'];
		$data_to_set['notes']           = BWFAN_Common::decode_merge_tags( $step_data['wcs_notes'] );
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

		return $this->add_notes();
	}

	/**
	 * add notes to subscription.
	 *
	 * subscription_id, subscription_coupon_id are required.
	 *
	 * @return array|bool
	 */
	public function add_notes() {
		$result = [];

		$subscription     = wcs_get_subscription( $this->data['subscription_id'] );
		$is_customer_note = $this->data['note_types'] === 'public' ? true : false;
		$note             = $this->data['notes'];

		if ( ! $subscription ) {
			$result['msg']    = __( 'Subscription does not exists', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		if ( empty( $this->data['notes'] ) ) {
			$result['msg']    = __( 'Notes is required', 'wp-marketing-automations-pro' );
			$result['status'] = 4;

			return $result;
		}

		$subscription->add_order_note( $note, $is_customer_note, false );

		return true;
	}

	public function process_v2() {
		$subscription     = wcs_get_subscription( $this->data['subscription_id'] );
		$is_customer_note = $this->data['note_types'] === 'public' ? true : false;
		$note             = $this->data['notes'];
		$order_id         = $this->data['order_id'];

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

		if ( empty( $this->data['notes'] ) ) {
			return $this->skipped_response( __( 'Note is required, missing.', 'wp-marketing-automations-pro' ) );
		}

		$subscription->add_order_note( $note, $is_customer_note, false );

		return $this->success_message( __( 'Note added.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		$options = BWFAN_PRO_Common::prepared_field_options( $this->wcs_note_types );

		return [
			[
				'id'          => 'wcs_note_type',
				'label'       => __( "Note Type", 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $options,
				'placeholder' => __( 'Choose note type', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				'tip'         => "",
				"description" => "",
				"required"    => false,
			],
			[
				'id'          => 'wcs_notes',
				'type'        => 'textarea',
				'label'       => __( 'Note', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				'placeholder' => __( "Note", 'wp-marketing-automations-pro' ),
				"description" => "",
				"required"    => false,
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'wcs_note_type' => 'private',
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['wcs_notes'] ) || empty( $data['wcs_notes'] ) ) {
			return '';
		}

		return $data['wcs_notes'];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCS_Add_Note';
