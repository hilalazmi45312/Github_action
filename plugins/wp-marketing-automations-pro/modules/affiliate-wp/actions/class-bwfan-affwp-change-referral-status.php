<?php

final class BWFAN_AFFWP_Change_Referral_Status extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Referral Status', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes affiliate\'s status', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'referral_id', 'referral_status' );

		// Excluded events which this action does not supports.
		$this->included_events = array(
			'affwp_makes_sale',
			'affwp_referral_rejected',
			'affwp_report',
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'referral_status_options', $data );
		}
	}

	public function get_view_data() {
		return array(
			'paid'     => __( 'Paid', 'affiliate-wp' ),
			'unpaid'   => __( 'Unpaid', 'affiliate-wp' ),
			'rejected' => __( 'Rejected', 'affiliate-wp' ),
			'pending'  => __( 'Pending', 'affiliate-wp' ),
		);
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_referral_status = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'referral_status')) ? data.actionSavedData.data.referral_status : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Status', 'woocommerce' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][referral_status]">
                    <option value=""><?php echo esc_html__( 'Choose Status', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'referral_status_options') && _.isObject(data.actionFieldsOptions.referral_status_options) ) {
                    _.each( data.actionFieldsOptions.referral_status_options, function( value, key ){
                    selected = (key == selected_referral_status) ? 'selected' : '';
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
		$data_to_set['referral_status'] = $task_meta['data']['referral_status'];
		$data_to_set['referral_id']     = $task_meta['global']['referral_id'];

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set                    = array();
		$data_to_set['referral_status'] = $step_data['referral_status'];
		$data_to_set['referral_id']     = $automation_data['global']['referral_id'];

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
			'status'  => 4,
			'message' => __( 'Referral status could not be changed', 'wp-marketing-automations-pro' ),
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

		return affwp_set_referral_status( $this->data['referral_id'], $this->data['referral_status'] );
	}

	public function process_v2() {
		$result = affwp_set_referral_status( $this->data['referral_id'], $this->data['referral_status'] );
		if ( ! empty( $result ) ) {
			return $this->success_message( __( 'Affiliate status changed.' ) );
		}
	}

	public function get_fields_schema() {
		$rate_types = BWFAN_PRO_Common::prepared_field_options( $this->get_view_data() );

		return [
			[
				'id'          => 'referral_status',
				'type'        => 'wp_select',
				'label'       => __( 'Referral Status', 'wp-marketing-automations-pro' ),
				'options'     => $rate_types,
				'placeholder' => __( 'Select Status', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => '',
				"required"    => true,
			],
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'referral_status' => 'paid',
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['referral_status'] ) || empty( $data['referral_status'] ) ) {
			return '';
		}
		$status = $this->get_view_data();

		return $status[ $data['referral_status'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_AFFWP_Change_Referral_Status';
