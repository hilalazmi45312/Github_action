<?php

final class BWFAN_CRM_Change_Contact_Status extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Change Status', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action changes the Contact status', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'email', 'status' );
		$this->action_priority = 50;
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
		if ( false === BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			return;
		}
		$data = $this->get_view_data();
		BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'status_options', $data );
	}

	public function get_view_data() {
		if ( method_exists( 'BWFAN_Common', 'get_contact_status_array_list' ) ) {
			$status = BWFAN_Common::get_contact_status_array_list();

			$arr = [];
			foreach ( $status as $key => $value ) {
				$arr[ $key ] = $value['name'];
			}

			return $arr;
		}

		return array(
			'0' => 'Unverified',
			'1' => 'Subscribed',
			'3' => 'Unsubscribed',
			'2' => 'Bounced',
		);
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
            <div data-element-type="bwfan-select" class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?> bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Status', 'woocommerce' ); ?></label>
                <select data-element-type="bwfan-select" required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][status]">
                    <option value=""><?php echo esc_html__( 'Select', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'status_options') &&
                    _.isObject(data.actionFieldsOptions.status_options) ) {
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
		$data['status']     = isset( $task_meta['data']['status'] ) ? $task_meta['data']['status'] : 0;
		$data['contact_id'] = isset( $task_meta['global']['contact_id'] ) ? $task_meta['global']['contact_id'] : 0;
		$data['user_id']    = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : 0;
		$data['email']      = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data['email'] ) && ! empty( $data['user_id'] ) ) {
			$user_data     = get_userdata( $data['user_id'] );
			$data['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		return $data;
	}

	public function make_v2_data( $automation_data, $step_data ) {

		$data['status']     = isset( $step_data['status'] ) ? $step_data['status'] : 0;
		$data['contact_id'] = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$data['user_id']    = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$data['email']      = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data['email'] ) && ! empty( $data['user_id'] ) ) {
			$user_data     = get_userdata( $data['user_id'] );
			$data['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		return $data;
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

		return $this->process();
	}

	/**
	 * Change Contact status.
	 *
	 * @return array|string|string[]
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$id_or_email = isset( $this->data['contact_id'] ) && absint( $this->data['contact_id'] ) > 0 ? $this->data['contact_id'] : $this->data['email'];
		$status      = absint( $this->data['status'] );
		$contact     = new BWFCRM_Contact( $id_or_email );

		/** check contact exists */
		if ( ! $contact->is_contact_exists() ) {
			return array(
				'status'  => 4,
				'message' => __( 'Contact does not exists with ' . $this->data['email'], 'wp-marketing-automations-pro' ),
			);
		}

		$old_status = $contact->contact->get_status();

		/** @var checking $unsubscribe_status of the contact */
		$unsubscribe_status = $contact->check_contact_unsubscribed();
		if ( ! empty( $unsubscribe_status['ID'] ) && absint( $unsubscribe_status['ID'] ) > 0 ) {
			$old_status = 3;
		}

		/**  check if old status already set */
		if ( absint( $status ) === absint( $old_status ) ) {
			$statuses    = $this->get_view_data();
			$status_name = isset( $statuses[ strval( $old_status ) ] ) ? $statuses[ strval( $old_status ) ] : $old_status;

			return array(
				'status'  => 3,
				'message' => __( 'Contact already has status: ' . $status_name . '.', 'wp-marketing-automations-pro' ),
			);
		}

		if ( 3 === $status ) {
			$contact->unsubscribe();
		} else {
			$contact->remove_unsubscribe_status();

			$contact->contact->set_status( $status );
			$contact->save();
		}

		return array(
			'status'  => 3,
			'message' => __( 'Contact status changed.', 'wp-marketing-automations-pro' ),
		);
	}

	public function process_v2() {
		$id_or_email = isset( $this->data['contact_id'] ) && absint( $this->data['contact_id'] ) > 0 ? $this->data['contact_id'] : $this->data['email'];
		$status      = intval( $this->data['status'] );
		$contact     = new BWFCRM_Contact( $id_or_email );

		/** check contact exists */
		if ( ! $contact->is_contact_exists() ) {
			return $this->error_response( __( 'Contact does not exists with ' . $this->data['email'], 'wp-marketing-automations-pro' ) );
		}

		$old_status = $contact->contact->get_status();
		/**  check if old status already set */
		if ( $status === intval( $old_status ) ) {
			$statuses    = $this->get_view_data();
			$status_name = isset( $statuses[ strval( $old_status ) ] ) ? $statuses[ strval( $old_status ) ] : $old_status;

			$this->success_message( __( 'Contact already has status: ' . $status_name . '.', 'wp-marketing-automations-pro' ) );
		}

		$contact->update_status( $status );

		return $this->success_message( __( 'Contact status changed.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		$status = $this->get_view_data();
		$status = BWFAN_PRO_Common::prepared_field_options( $status );

		return [
			[
				'id'          => 'status',
				'type'        => 'wp_select',
				'label'       => __( 'Status', 'woocommerce' ),
				'options'     => $status,
				'placeholder' => __( 'Choose Status', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			]
		];
	}

	/** set default values */
	public function get_default_values() {
		return [
			'status' => 1,
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );

		$nice_name = $this->get_view_data();
		if ( ! isset( $data['status'] ) || empty( $data['status'] ) ) {
			return $nice_name[0];
		}

		return $nice_name[ $data['status'] ];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_CRM_Change_Contact_Status';
