<?php

final class BWFAN_WCM_Delete_Membership extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Remove From Membership Plan', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action removes the membership plan from the user', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'user_id', 'plan_id' );
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'membership_plans', $data );
		}
	}

	public function get_view_data() {
		$plans       = wc_memberships_get_membership_plans();
		$plans_array = [];
		foreach ( $plans as $plan ) {
			$plans_array[ $plan->get_id() ] = $plan->get_name();
		}

		return $plans_array;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_plan = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'plan')) ? data.actionSavedData.data.plan : '';
            #>
            <div class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?>">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Membership Plan', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][plan]">
                    <option value=""><?php echo esc_html__( 'Choose New Plan', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'membership_plans') && _.isObject(data.actionFieldsOptions.membership_plans) ) {
                    _.each( data.actionFieldsOptions.membership_plans, function( value, key ){
                    selected = (key == selected_plan) ? 'selected' : '';
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
		$data_to_set            = array();
		$data_to_set['plan_id'] = $task_meta['data']['plan'];

		$user_id = BWFAN_PRO_Common::maybe_get_user_id_from_task_meta( $task_meta['global'] );
		if ( ! empty( $user_id ) ) {
			$data_to_set['user_id'] = $user_id;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set            = array();
		$data_to_set['plan_id'] = isset( $step_data['plan'][0]['id'] ) ? $step_data['plan'][0]['id'] : 0;

		$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		if ( 0 === absint( $user_id ) ) {
			$email   = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';
			$user    = is_email( $email ) ? get_user_by( 'email', $email ) : '';
			$user_id = $user instanceof WP_User ? $user->ID : 0;
		}
		$data_to_set['user_id'] = $user_id;

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
				'status'  => 3,
				'message' => 'User Membership deleted',
			);
		}

		return $result;
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

		$plan_id = absint( $this->data['plan_id'] );

		$user = get_userdata( $this->data['user_id'] );
		if ( false === $user ) {
			return array(
				'status'  => 4,
				'message' => __( 'User does not exists', 'wp-marketing-automations-pro' ),
			);
		}

		$membership = $plan_id ? wc_memberships_get_user_membership( $user->ID, $plan_id ) : false;
		//To execute this action user's current membership must exists
		if ( ! $membership ) {
			return array(
				'status'  => 4,
				'message' => __( 'Membership does not exists with the selected plan', 'wp-marketing-automations-pro' ),
			);
		}

		$membership_id = $membership->get_id();

		$success = wp_delete_post( $membership_id, true );
		if ( $success ) {
			return true;
		}

		return array(
			'status'  => 4,
			'message' => __( 'Unable to delete user membership', 'wp-marketing-automations-pro' ),
		);
	}

	public function process_v2() {
		$plan_id = absint( $this->data['plan_id'] );

		$user = get_userdata( $this->data['user_id'] );
		if ( false === $user ) {
			return $this->skipped_response( __( 'User does not exists', 'wp-marketing-automations-pro' ) );
		}

		$membership = $plan_id ? wc_memberships_get_user_membership( $user->ID, $plan_id ) : false;
		// To execute this action user's current membership must exists
		if ( ! $membership ) {
			return $this->skipped_response( __( 'Membership does not exists with the selected plan', 'wp-marketing-automations-pro' ) );
		}

		$membership_id = $membership->get_id();

		$success = wp_delete_post( $membership_id, true );
		if ( $success ) {
			return $this->success_message( __( 'Membership plan deleted.', 'wp-marketing-automations-pro' ) );
		}

		return $this->skipped_response( __( 'Unable to delete user membership', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				"id"                  => 'plan',
				"label"               => __( 'Membership Plan', 'wp-marketing-automations-pro' ),
				"type"                => 'custom_search',
				'autocompleterOption' => [
					'path'      => 'wcm_plans',
					'slug'      => 'wcm_plans',
					'labelText' => 'plan'
				],
				"allowFreeTextSearch" => false,
				"required"            => true,
				"errorMsg"            => __( "Membership plan is required", 'wp-marketing-automations-pro' ),
				"multiple"            => false
			],
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['plan'] ) || empty( $data['plan'] ) ) {
			return '';
		}
		$plans = [];
		foreach ( $data['plan'] as $plan ) {
			if ( ! isset( $plan['name'] ) || empty( $plan['name'] ) ) {
				continue;
			}
			$plans[] = $plan['name'];
		}

		return $plans;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WCM_Delete_Membership';
