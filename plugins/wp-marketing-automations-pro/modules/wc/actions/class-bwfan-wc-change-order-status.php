<?php

final class BWFAN_WC_Change_order_status extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Order Status', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes the WooCommerce order status', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'order_id', 'status' );
		$this->included_events = array(
			'wc_new_order',
			'wc_order_note_added',
			'wc_order_status_change',
			'wc_product_purchased',
			'wc_product_refunded',
			'wc_product_stock_reduced',
			'wc_order_status_pending',
		);

		$this->action_priority = 15;
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'status_options', $data );
		}
	}

	public function get_view_data() {
		return wc_get_order_statuses();
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_status = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'status')) ? data.actionSavedData.data.status : '';
            #>
            <div data-element-type="bwfan-select" class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Change Order Status To', 'wp-marketing-automations-pro' ); ?></label>
                <select data-element-type="bwfan-select" required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][status]">
                    <option value=""><?php echo esc_html__( 'Choose Order Status', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'status_options') && _.isObject(data.actionFieldsOptions.status_options) ) {
                    _.each( data.actionFieldsOptions.status_options, function( value, key ){
                    selected = (key == selected_status) ? 'selected' : '';
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
		return array(
			'status'   => $task_meta['data']['status'],
			'order_id' => $task_meta['global']['order_id'],
		);
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set           = array();
		$data_to_set['status'] = $step_data['status'];

		$email                   = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
		$data_to_set['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
		$user                    = is_email( $email ) ? get_user_by( 'email', $email ) : '';
		$data_to_set['user_id']  = $user instanceof WP_User ? $user->ID : 0;
		$data_to_set['email']    = $email;

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
			'status'  => 4,
			'message' => __( 'Something went wrong', 'wp-marketing-automations-pro' ),
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
	 * Change order status.
	 *
	 * order_id, status are required.
	 *
	 * @return array|bool
	 * @throws Exception
	 */
	public function change_status() {
		$order = new WC_Order( $this->data['order_id'] );

		add_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );
		$res = $order->update_status( $this->data['status'] );
		remove_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );

		return $res;
	}

	public function process_v2() {
		$order_id = absint( $this->data['order_id'] );
		$order    = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return $this->skipped_response( __( 'Not a WooCommerce Order Object', 'wp-marketing-automations-pro' ) );
		}

		$order->update_status( $this->data['status'] );

		remove_filter( 'woocommerce_new_order_note_data', array( $this, 'add_autonami_prefix_in_notes' ), 9999, 2 );

		return $this->success_message( __( 'Order status changed.', 'wp-marketing-automations-pro' ) );
	}

	public function add_autonami_prefix_in_notes( $note, $data ) {
		if ( isset( $this->data['order_id'] ) && intval( $this->data['order_id'] ) === intval( $data['order_id'] ) ) {
			$note['comment_content'] = 'FunnelKit Automations: ' . $note['comment_content'];
		}

		return $note;
	}

	public function get_fields_schema() {
		$status  = array_replace( [ '' => 'Select' ], $this->get_view_data() );
		$options = BWFAN_PRO_Common::prepared_field_options( $status );

		return [
			[
				'id'          => 'status',
				'label'       => __( "Change Order Status To", 'wp-marketing-automations-pro' ),
				'type'        => 'wp_select',
				'options'     => $options,
				'placeholder' => __( 'Choose Order Status', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['status'] ) || empty( $data['status'] ) ) {
			return '';
		}
		$status = $this->get_view_data();

		return $status[ $data['status'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WC_Change_order_status';
