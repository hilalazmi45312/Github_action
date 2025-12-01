<?php

final class BWFAN_AFFWP_Change_Affiliate_Rate extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Affiliate Rate', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes the affiliate\'s referral rate', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'affiliate_id', 'rate_type', 'rate' );

		$this->support_v2 = true;
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'rate_types_options', $data );
		}
	}

	public function get_view_data() {
		return affwp_get_affiliate_rate_types();
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_rate_type = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'rate_type')) ? data.actionSavedData.data.rate_type : '';
            rate = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'rate')) ? data.actionSavedData.data.rate : '';
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Rate Type', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][rate_type]">
                    <option value=""><?php echo esc_html__( 'Choose Type', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'rate_types_options') && _.isObject(data.actionFieldsOptions.rate_types_options) ) {
                    _.each( data.actionFieldsOptions.rate_types_options, function( value, key ){
                    selected = (key == selected_rate_type) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Rate', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0">
                    <input type="text" required class="bwfan-input-wrapper" placeholder="<?php echo esc_html__( 'Affiliate new rate', 'wp-marketing-automations-pro' ); ?>" name="bwfan[{{data.action_id}}][data][rate]" value="{{rate}}">
                </div>
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
		$data_to_set = array();

		$data_to_set['rate_type']    = $task_meta['data']['rate_type'];
		$data_to_set['rate']         = $task_meta['data']['rate'];
		$data_to_set['affiliate_id'] = $task_meta['global']['affiliate_id'];
		$data_to_set['user_id']      = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : 0;
		$data_to_set['email']        = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';
		if ( empty( $data_to_set['affiliate_id'] ) && intval( $data_to_set['user_id'] ) > 0 ) {
			$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $data_to_set['user_id'] );
		}
		if ( empty( $data_to_set['affiliate_id'] ) && ! empty( $data_to_set['email'] ) ) {
			/** @var WP_User $user */
			$user = get_user_by( 'email', $data_to_set['email'] );
			if ( $user instanceof WP_User ) {
				$data_to_set['user_id']      = $user->ID;
				$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $user->ID );
			}
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array();

		$data_to_set['rate_type']    = $step_data['rate_type'];
		$data_to_set['rate']         = $step_data['rate'];
		$data_to_set['affiliate_id'] = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : 0;
		$data_to_set['user_id']      = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$data_to_set['email']        = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		if ( empty( $data_to_set['affiliate_id'] ) && intval( $data_to_set['user_id'] ) > 0 ) {
			$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $data_to_set['user_id'] );
		}
		if ( empty( $data_to_set['affiliate_id'] ) && ! empty( $data_to_set['email'] ) ) {
			/** @var WP_User $user */
			$user = get_user_by( 'email', $data_to_set['email'] );
			if ( $user instanceof WP_User ) {
				$data_to_set['user_id']      = $user->ID;
				$data_to_set['affiliate_id'] = affwp_get_affiliate_id( $user->ID );
			}
		}

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
			'message' => __( 'Affiliate rate could not be changed', 'wp-marketing-automations-pro' ),
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
		if ( empty( $this->data['affiliate_id'] ) ) {
			return false;
		}
		$affiliate = affwp_get_affiliate( $this->data['affiliate_id'] );
		if ( empty( $affiliate ) ) {
			return false;
		}

		affiliate_wp()->affiliates->update( $affiliate->ID, array(
			'rate_type' => $this->data['rate_type'],
			'rate'      => $this->data['rate'],
		), '', 'affiliate' );

		return true;
	}

	public function process_v2() {
		/** Perform Promotional checking */
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->skipped_response( __( 'Required fields missing.' ) );
		}

		if ( empty( $this->data['affiliate_id'] ) ) {
			return $this->skipped_response( __( 'Affiliate not found.' ) );
		}
		$affiliate = affwp_get_affiliate( $this->data['affiliate_id'] );
		if ( empty( $affiliate ) ) {
			return $this->skipped_response( __( 'Affiliate not found.' ) );
		}

		affiliate_wp()->affiliates->update( $affiliate->ID, array(
			'rate_type' => $this->data['rate_type'],
			'rate'      => $this->data['rate'],
		), '', 'affiliate' );

		return $this->success_message( __( 'Affiliate rate changed.' ) );
	}

	public function get_fields_schema() {
		$rate_types = array_replace( [ '' => 'Select' ], $this->get_view_data() );
		$rate_types = BWFAN_PRO_Common::prepared_field_options( $rate_types );

		return [
			[
				'id'          => 'rate_type',
				'type'        => 'wp_select',
				'label'       => __( 'Rate Type', 'wp-marketing-automations-pro' ),
				'options'     => $rate_types,
				'placeholder' => __( 'Select Rate Type', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
				"errorMsg"    => __( "Rate type is required", 'wp-marketing-automations-pro' ),
			],
			[
				'id'          => 'rate',
				'type'        => 'text',
				'label'       => __( 'Rate', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Affiliate new rate', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
				"errorMsg"    => __( "Rate is required", 'wp-marketing-automations-pro' ),
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['rate'] ) || empty( $data['rate'] ) ) {
			return '';
		}
		$rates = $this->get_view_data();

		return $data['rate'] . ' ' . $rates[ $data['rate_type'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select action dropdown.
 */
return 'BWFAN_AFFWP_Change_Affiliate_Rate';
