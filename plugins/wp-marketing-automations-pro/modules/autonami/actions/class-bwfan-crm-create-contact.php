<?php

final class BWFAN_CRM_Create_Contact extends BWFAN_Action {

	private static $instance = null;

	private function __construct() {
		$this->action_name     = __( 'Create Contact', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action creates a contact', 'wp-marketing-automations-pro' );
		$this->action_priority = 5;
		$this->support_v2      = true;
		$this->required_fields = array( 'email' );
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Localize data for html fields for the current action.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$contact_status = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'contact_status', $contact_status );
		}
	}

	public function get_view_data() {
		if ( method_exists( 'BWFAN_Common', 'get_contact_status_array_list' ) ) {
			$status = BWFAN_Common::get_contact_status_array_list();

			$arr = [ '1' => 'Subscribed' ];
			foreach ( $status as $key => $value ) {
				$arr[ $key ] = $value['name'];
			}

			return $arr;
		}

		return array(
			'1' => __( 'Subscribed', 'wp-marketing-automations-pro' ),
			'0' => __( 'Unverified', 'wp-marketing-automations-pro' ),
			'3' => __( 'Unsubscribed', 'wp-marketing-automations-pro' ),
			'2' => __( 'Bounced', 'wp-marketing-automations-pro' ),
		);
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            selected_first_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'first_name')) ? data.actionSavedData.data.first_name : '';
            selected_last_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'last_name')) ? data.actionSavedData.data.last_name : '';
            selected_email = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'email')) ? data.actionSavedData.data.email : '';
            selected_phone = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'phone')) ? data.actionSavedData.data.phone : '';
            selected_status = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'status')) ? data.actionSavedData.data.status : 1;
            #>
            <label for="" class="bwfan-label-title">
				<?php esc_html_e( 'Email', 'wp-marketing-automations-pro' ); ?>
				<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            </label>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <input required type="text" class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>" name="bwfan[{{data.action_id}}][data][email]" placeholder="Email" value="{{selected_email}}"/>
            </div>
            <label for="" class="bwfan-label-title">
				<?php esc_html_e( 'First Name (optional)', 'wp-marketing-automations-pro' ); ?>
				<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            </label>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <input required type="text" class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>" name="bwfan[{{data.action_id}}][data][first_name]" placeholder="First Name" value="{{selected_first_name}}"/>
            </div>
            <label for="" class="bwfan-label-title">
				<?php esc_html_e( 'Last Name (optional)', 'wp-marketing-automations-pro' ); ?>
				<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            </label>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <input required type="text" class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>" name="bwfan[{{data.action_id}}][data][last_name]" placeholder="Last Name" value="{{selected_last_name}}"/>
            </div>
            <label for="" class="bwfan-label-title">
				<?php esc_html_e( 'Phone (optional)', 'wp-marketing-automations-pro' ); ?>
				<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            </label>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <input required type="text" class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>" name="bwfan[{{data.action_id}}][data][phone]" placeholder="Phone" value="{{selected_phone}}"/>
            </div>
            <div data-element-type="bwfan-select" class="bwfan-<?php echo esc_html__( $this->get_slug() ); ?> bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php echo esc_html__( 'Status', 'woocommerce' ); ?></label>
                <select data-element-type="bwfan-select" required id="" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][status]">
                    <#
                    if(_.has(data.actionFieldsOptions, 'contact_status') && _.isObject(data.actionFieldsOptions.contact_status) ) {
                    _.each( data.actionFieldsOptions.contact_status, function( value, key ){
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
	 * @param $integration_object BWFAN_Integration
	 * @param $task_meta
	 *
	 * @return array|void
	 */
	public function make_data( $integration_object, $task_meta ) {
		$data_to_set = array();

		$fname = isset( $task_meta['data']['first_name'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['first_name'] ) : '';
		if ( ! empty( $fname ) ) {
			$data_to_set['f_name'] = $fname;
		}
		$lname = isset( $task_meta['data']['last_name'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['last_name'] ) : '';
		if ( ! empty( $lname ) ) {
			$data_to_set['l_name'] = $lname;
		}
		$contact_no = isset( $task_meta['data']['phone'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['phone'] ) : '';
		if ( ! empty( $contact_no ) ) {
			$data_to_set['contact_no'] = $contact_no;
		}
		$email = isset( $task_meta['data']['email'] ) ? BWFAN_Common::decode_merge_tags( $task_meta['data']['email'] ) : '';
		if ( ! empty( $email ) ) {
			$data_to_set['email'] = $email;
		}
		$data_to_set['status'] = isset( $task_meta['data']['status'] ) ? $task_meta['data']['status'] : 0;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array();

		$fname = isset( $step_data['first_name'] ) ? BWFAN_Common::decode_merge_tags( $step_data['first_name'] ) : '';
		if ( ! empty( $fname ) ) {
			$data_to_set['f_name'] = $fname;
		}
		$lname = isset( $step_data['last_name'] ) ? BWFAN_Common::decode_merge_tags( $step_data['last_name'] ) : '';
		if ( ! empty( $lname ) ) {
			$data_to_set['l_name'] = $lname;
		}
		$email = isset( $step_data['email'] ) ? BWFAN_Common::decode_merge_tags( $step_data['email'] ) : '';
		if ( ! empty( $email ) ) {
			$data_to_set['email'] = $email;
		}
		$contact_no = isset( $step_data['contact_no'] ) ? BWFAN_Common::decode_merge_tags( $step_data['contact_no'] ) : '';
		if ( ! empty( $contact_no ) ) {
			$data_to_set['contact_no'] = $contact_no;
		}
		$data_to_set['status'] = isset( $step_data['status'] ) ? $step_data['status'] : 0;

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

		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );

		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		if ( ! is_email( $this->data['email'] ) ) {
			return array(
				'status'  => 4,
				'message' => __( 'Invalid email to create contact.', 'wp-marketing-automations-pro' ),
			);
		}

		$status = ( isset( $this->data['status'] ) && ! empty( $this->data['status'] ) ) ? absint( $this->data['status'] ) : 0;

		/** If status is unsubscribed then unset status from data */
		if ( 3 === $status ) {
			/** Keep older one in order to change back the status from unsubscribe */
			unset( $this->data['status'] );
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true, $this->data );

		/** Older contact */
		if ( true === $contact->already_exists ) {
			/** Update contact fields */
			$contact->update( $this->data );

			/** Update contact status */
			$contact->update_status( $status );

			return array(
				'status'  => 3,
				'message' => __( 'Contact updated', 'wp-marketing-automations-pro' ),
			);
		}

		if ( ! $contact->is_contact_exists() ) {
			return array(
				'status'  => 4,
				'message' => __( 'Unable to create contact', 'wp-marketing-automations-pro' ),
			);
		}

		/** If new status is unsubscribe for new contact */
		if ( 3 === $status ) {
			$contact->unsubscribe();
		}

		return array(
			'status'  => 3,
			'message' => __( 'Contact created', 'wp-marketing-automations-pro' ),
		);

	}

	public function process_v2() {
		if ( ! is_email( $this->data['email'] ) ) {
			return $this->skipped_response( __( 'Invalid email to create contact.', 'wp-marketing-automations-pro' ) );
		}

		$status = ( isset( $this->data['status'] ) && ! empty( $this->data['status'] ) ) ? intval( $this->data['status'] ) : 0;

		/** in case status is unsubscribed then unset status from data */
		if ( 3 === $status ) {
			unset( $this->data['status'] );
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true, $this->data );
		if ( $contact->already_exists ) {
			/** Update contact fields */
			$contact->update( $this->data );

			/** Update contact status */
			$contact->update_status( $status );

			return $this->success_message( __( 'Contact updated', 'wp-marketing-automations-pro' ) );
		}

		if ( ! $contact->is_contact_exists() ) {
			return $this->error_response( __( 'Unable to create contact', 'wp-marketing-automations-pro' ) );
		}

		/** in case unsubscribe than make entry in unsubscribe table */
		if ( 3 === $status ) {
			$contact->unsubscribe();
		}

		return $this->success_message( __( 'Contact created', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		$options          = $this->get_view_data();
		$prepared_options = BWFAN_PRO_Common::prepared_field_options( $options );

		return array(
			array(
				'id'                    => 'email',
				'label'                 => __( 'Email', 'wp-marketing-automations-pro' ),
				'type'                  => 'text_with_button',
				'placeholder'           => __( 'Email', 'wp-marketing-automations-pro' ),
				'class'                 => 'bwfan-input-wrapper bwfan-field-crm_create_contact',
				'description'           => '',
				'required'              => true,
				'automation_merge_tags' => true
			),
			array(
				'id'                    => 'first_name',
				'label'                 => __( 'First Name', 'wp-marketing-automations-pro' ),
				'type'                  => 'text_with_button',
				'placeholder'           => __( 'First Name', 'wp-marketing-automations-pro' ),
				'class'                 => 'bwfan-input-wrapper bwfan-field-crm_create_contact',
				'description'           => '',
				'required'              => false,
				'automation_merge_tags' => true
			),
			array(
				'id'                    => 'last_name',
				'label'                 => __( 'Last Name', 'wp-marketing-automations-pro' ),
				'type'                  => 'text_with_button',
				'placeholder'           => __( 'Last Name', 'wp-marketing-automations-pro' ),
				'class'                 => 'bwfan-input-wrapper bwfan-field-crm_create_contact',
				'description'           => '',
				'required'              => false,
				'automation_merge_tags' => true
			),
			array(
				'id'                    => 'contact_no',
				'label'                 => __( 'Phone', 'wp-marketing-automations-pro' ),
				'type'                  => 'text_with_button',
				'placeholder'           => __( 'Phone', 'wp-marketing-automations-pro' ),
				'class'                 => 'bwfan-input-wrapper bwfan-field-crm_create_contact',
				'description'           => '',
				'required'              => false,
				'automation_merge_tags' => true
			),
			array(
				'id'          => 'status',
				'label'       => __( 'Status', 'woocommerce' ),
				'type'        => 'radio',
				'placeholder' => __( 'Choose status', 'wp-marketing-automations-pro' ),
				'options'     => $prepared_options,
				'class'       => 'bwfan-input-wrapper bwfan-field-crm_create_status',
				'description' => '',
				'required'    => true,
			),
		);
	}

	public function get_default_values() {
		return [
			'status' => '1',
			'email'  => '{{contact_email}}'
		];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_CRM_Create_Contact';
