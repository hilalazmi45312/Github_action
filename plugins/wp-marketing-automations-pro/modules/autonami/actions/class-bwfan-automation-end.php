<?php

final class BWFAN_Automation_End extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'End Automation', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'End a FunnelKit Automation', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'email' );
		$this->support_v2      = true;
		$this->action_priority = 20;
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
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'automations_options', $data );
		}
	}

	public function get_view_data() {
		$automations = BWFAN_Core()->automations->get_active_v1_automation_names();
		if ( empty( $automations ) ) {
			return array();
		}

		$automations_to_return = array();
		foreach ( $automations as $automation ) {
			$automations_to_return[ $automation['ID'] ] = ( ! empty( $automation['meta_value'] ) ? $automation['meta_value'] : '' );
		}

		return $automations_to_return;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            selected_automation = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'selected_automation')) ? data.actionSavedData.data.selected_automation : '';
            #>
            <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Select Automation', 'wp-marketing-automations-pro' ); ?></label>
            <select required id="" class="bwfan-input-wrapper bwfan-single-select" name="bwfan[{{data.action_id}}][data][selected_automation]">
                <#
                if(_.has(data.actionFieldsOptions, 'automations_options') && _.isObject(data.actionFieldsOptions.automations_options) ) {
                _.each( data.actionFieldsOptions.automations_options, function( value, key ){
                selected = (key == selected_automation) ? 'selected' : '';
                #>
                <option value="{{key}}" {{selected}}>{{value}} (#{{key}})</option>
                <# })
                }
                #>
            </select>
            <div class="clearfix bwfan_field_desc bwfan-mb10">
                Any scheduled tasks for the selected automation will be removed for the user.
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
		$data_to_set                   = array();
		$data_to_set['end_automation'] = $task_meta['data']['selected_automation'];

		if ( isset( $task_meta['global']['phone'] ) && ! empty( $task_meta['global']['phone'] ) ) {
			$data_to_set['phone'] = $task_meta['global']['phone'];
		}

		$data_to_set['email'] = $task_meta['global']['email'];
		if ( ! is_email( $data_to_set['email'] ) && isset( $task_meta['global']['user_id'] ) ) {
			$data_to_set['email'] = ( get_user_by( 'ID', $task_meta['global']['user_id'] ) )->user_email;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {

		$data_to_set                   = array();
		$data_to_set['end_automation'] = isset( $step_data['selected_automation'] ) && ! empty( $step_data['selected_automation'] ) ? array_column( $step_data['selected_automation'], 'id' ) : [];
		$data_to_set['cid']            = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
		$data_to_set['sid']            = isset( $automation_data['step_id'] ) ? $automation_data['step_id'] : 0;
		if ( isset( $automation_data['global']['phone'] ) && ! empty( $automation_data['global']['phone'] ) ) {
			$automation_data['phone'] = $automation_data['global']['phone'];
		}

		$data_to_set['email'] = $automation_data['global']['email'];
		if ( ! is_email( $data_to_set['email'] ) && isset( $automation_data['global']['user_id'] ) ) {
			$data_to_set['email'] = ( get_user_by( 'ID', $automation_data['global']['user_id'] ) )->user_email;
		}

		if ( empty( $data_to_set['cid'] ) && is_email( $data_to_set['email'] ) ) {
			$contact            = bwf_get_contact( '', $data_to_set['email'] );
			$data_to_set['cid'] = $contact->get_id();
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
		$action_data['processed_data']['task_id'] = absint( $action_data['task_id'] );
		$this->set_data( $action_data['processed_data'] );
		$result = $this->process();

		if ( ! is_array( $result ) ) {
			$email   = is_email( $this->data['email'] ) ? ' Email: ' . $this->data['email'] : '';
			$phone   = isset( $this->data['phone'] ) && ! empty( $this->data['phone'] ) ? ' Phone: ' . $this->data['phone'] : '';
			$message = __( $result . ' tasks having were deleted successfully, for' . $email . $phone, 'autonami-automation-pro' );

			return array(
				'status'  => 3,
				'message' => $message
			);
		}

		return array(
			'status'  => 4,
			'message' => __( is_array( $result ) && isset( $result['message'] ) ? $result['message'] : 'Something went wrong while ending automation: ' . $this->data['automation_id'], 'wp-marketing-automations-pro' ),
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

		$automation_id   = absint( $this->data['end_automation'] );
		$automation_data = BWFAN_Model_Automations::get( $automation_id );

		/** if automation data not found */
		if ( empty( $automation_data ) ) {
			return array( 'message' => 'No Automation found with ID: ' . $automation_id );
		}

		/** checking automation status before deleting tasks */

		if ( isset( $automation_data['status'] ) && 2 === absint( $automation_data['status'] ) ) {
			return array( 'message' => 'Automation with ID ' . $automation_id . ' is inactive.' );
		}

		$tasks = array();
		if ( isset( $this->data['email'] ) && is_email( $this->data['email'] ) ) {
			$email_tasks = BWFAN_Common::get_schedule_task_by_email( array( $automation_id ), $this->data['email'] );
			$tasks       = isset( $email_tasks[ $automation_id ] ) && ! empty( $email_tasks[ $automation_id ] ) ? $email_tasks[ $automation_id ] : $tasks;
		}

		if ( isset( $this->data['phone'] ) && ! empty( $this->data['phone'] ) ) {
			$phone_tasks = BWFAN_Common::get_schedule_task_by_phone( array( $this->data['end_automation'] ), $this->data['phone'] );
			$tasks       = isset( $phone_tasks[ $automation_id ] ) && ! empty( $phone_tasks[ $automation_id ] ) ? array_replace( $tasks, $phone_tasks[ $automation_id ] ) : $tasks;
		}

		if ( ! is_array( $tasks ) || 0 === count( $tasks ) ) {
			return array( 'message' => 'No tasks found' );
		}

		$delete_tasks = array();
		foreach ( $tasks as $task ) {
			$delete_tasks[] = absint( $task['ID'] );
		}

		/** Unset Current task */
		if ( absint( $this->data['end_automation'] ) === absint( $this->data['automation_id'] ) && false !== ( $key = array_search( $this->data['task_id'], $delete_tasks, true ) ) ) {
			unset( $delete_tasks[ $key ] );
		}

		if ( 0 === count( $delete_tasks ) ) {
			return array( 'message' => 'No tasks found' );
		}

		BWFAN_Core()->tasks->delete_tasks( $delete_tasks );

		return count( $delete_tasks );
	}

	public function process_v2() {
		$end_automations = ! is_array( $this->data['end_automation'] ) ? array( $this->data['end_automation'] ) : $this->data['end_automation'];
		$contact_id      = absint( $this->data['cid'] );

		if ( empty( $end_automations ) ) {
			return $this->success_message( __( 'No End Automation selected in action', 'wp-marketing-automations-pro' ) );
		}

		$end_automations = array_map( 'intval', $end_automations );

		foreach ( $end_automations as $automation_id ) {
			/** Get automation contact data */
			$automation_contact = BWFAN_PRO_Common::get_automation_contact_rows( $automation_id, $contact_id );
			if ( ! is_array( $automation_contact ) || 0 === count( $automation_contact ) ) {
				continue;
			}

			foreach ( $automation_contact as $a_contact ) {
				if ( absint( $a_contact['aid'] ) === absint( $a_contact ) ) {
					/** using special property to end the current automation process for a contact */
					BWFAN_Common::$end_v2_current_contact_automation = true;
				}
				$reason    = [
					'type' => BWFAN_Automation_Controller::$ACTION_END,
					'data' => [
						'sid' => $this->data['step_id'],
						'aid' => $this->automation_id
					]
				];
				$a_contact = BWFAN_PRO_Common::set_automation_ended_reason( $reason, $a_contact );

				/** set data for insert complete contact automation */
				$data = [
					'cid'    => $a_contact['cid'],
					'aid'    => $a_contact['aid'],
					'event'  => $a_contact['event'],
					's_date' => $a_contact['c_date'],
					'c_date' => current_time( 'mysql', 1 ),
					'data'   => $a_contact['data'],
					'trail'  => $a_contact['trail'],
				];

				$format = [
					'%d',// cid
					'%d',// aid
					'%s',// event
					'%s',// s_date
					'%s',// c_date
					'%s',// data
					'%s',// trail
				];

				if ( method_exists( 'BWFAN_Model_Automation_Complete_Contact', 'insert_ignore' ) ) {
					BWFAN_Model_Automation_Complete_Contact::insert_ignore( $data, $format );
				} else {
					BWFCRM_Common::insert_ignore( 'bwfan_automation_complete_contact', $data, $format );
				}

				BWFAN_Model_Automation_Contact::delete( $a_contact['ID'] );

				/** Update status as success for any step trail where status was waiting */
				BWFAN_Model_Automation_Contact_Trail::update_all_step_trail_status_complete( $a_contact['trail'] );

				BWFAN_Common::update_automation_contact_fields( $a_contact['cid'], $a_contact['aid'] );
			}
		}

		return $this->success_message( __( 'Automation ended', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'                  => 'selected_automation',
				'type'                => 'search',
				'label'               => __( 'Select Automation', 'wp-marketing-automations-pro' ),
				'placeholder'         => __( 'Select', 'wp-marketing-automations-pro' ),
				"tip"                 => __( "Any scheduled tasks for the selected automation will be removed for the user.", 'wp-marketing-automations-pro' ),
				"required"            => true,
				"allowFreeTextSearch" => false,
				"multiple"            => true,
				'autocompleter'       => 'automations',
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['selected_automation'] ) || empty( $data['selected_automation'] ) ) {
			return '';
		}
		$automations = [];
		foreach ( $data['selected_automation'] as $automation ) {
			if ( ! isset( $automation['name'] ) || empty( $automation['name'] ) ) {
				continue;
			}
			$automations[] = $automation['name'];
		}

		return $automations;
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_Automation_End';
