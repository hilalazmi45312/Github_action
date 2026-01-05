<?php

final class BWFAN_CRM_Add_Contact_Note extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Add Contact Note', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds note to the Contact', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'title', 'body' );
		$this->support_v2      = true;
		$this->action_priority = 10;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            body = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'body')) ? data.actionSavedData.data.body : '';
            note_type = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'note_type')) ? data.actionSavedData.data.note_type : '';
            selected_title = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'title')) ? data.actionSavedData.data.title : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php esc_html_e( 'Title', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                    <input required type="text"
                           class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>"
                           name="bwfan[{{data.action_id}}][data][title]" placeholder="Title"
                           value="{{selected_title}}"/>
                </div>
            </div>
            <div class="bwfan-input-form clearfix">
                <label for=""
                       class="bwfan-label-title"><?php echo esc_html__( 'Note', 'wp-marketing-automations-pro' ); ?><?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?></label>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0">
                    <textarea required class="bwfan-input-wrapper" rows="4"
                              placeholder="<?php echo esc_html__( 'Contact Note', 'wp-marketing-automations-pro' ); ?>"
                              name="bwfan[{{data.action_id}}][data][body]">{{body}}</textarea>
                </div>
            </div>
            <div class="bwfan-input-form clearfix">
                <label for=""
                       class="bwfan-label-title"><?php echo esc_html__( 'Note Type', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper bwfan-single-select"
                        name="bwfan[{{data.action_id}}][data][note_type]">
                    <option {{ note_type===
                    'billing' ? 'selected' : '' }}
                    value="billing"><?php echo esc_html__( 'Billing', 'woocommerce' ); ?></option>
                    <option {{ note_type===
                    'shipping' ? 'selected' : '' }}
                    value="shipping"><?php echo esc_html__( 'Shipping', 'woocommerce' ); ?></option>
                    <option {{ note_type===
                    'refund' ? 'selected' : '' }}
                    value="refund"><?php echo esc_html__( 'Refund', 'woocommerce' ); ?></option>
                    <option {{ note_type===
                    'subscription' ? 'selected' : '' }}
                    value="subscription"><?php echo esc_html__( 'Subscription', 'wp-marketing-automations-pro' ); ?></option>
                    <option {{ note_type===
                    'feedback' ? 'selected' : '' }}
                    value="feedback"><?php echo esc_html__( 'Feedback', 'wp-marketing-automations-pro' ); ?></option>
                    <option {{ note_type===
                    'others' ? 'selected' : '' }}
                    value="others"><?php echo esc_html__( 'Others', 'wp-marketing-automations-pro' ); ?></option>
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
		$this->set_data_for_merge_tags( $task_meta );
		$title                = $task_meta['data']['title'];
		$body                 = $task_meta['data']['body'];
		$note_type            = $task_meta['data']['note_type'];
		$title                = BWFAN_Common::decode_merge_tags( $title );
		$body                 = BWFAN_Common::decode_merge_tags( $body );
		$data_to_set          = array();
		$data_to_set['body']  = $body;
		$data_to_set['type']  = $note_type;
		$data_to_set['title'] = $title;

		foreach ( $task_meta['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
		}

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$title                = $step_data['title'];
		$body                 = $step_data['body'];
		$note_type            = $step_data['note_type'];
		$title                = BWFAN_Common::decode_merge_tags( $title );
		$body                 = BWFAN_Common::decode_merge_tags( $body );
		$data_to_set          = array();
		$data_to_set['body']  = $body;
		$data_to_set['type']  = $note_type;
		$data_to_set['title'] = $title;
		$data_to_set['email'] = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';

		foreach ( $automation_data['global'] as $key1 => $value1 ) {
			$data_to_set[ $key1 ] = $value1;
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

		if ( false === $result ) {
			return array(
				'status'  => 4,
				'message' => '',
			);
		}

		return array(
			'status'  => 3,
			'message' => __( 'Contact note added', 'wp-marketing-automations-pro' ),
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
		$email = BWFAN_Merge_Tag_Loader::get_data( 'email' );

		$contact = new BWFCRM_Contact( $email, true );

		if ( ! $contact->is_contact_exists() ) {
			return array(
				'status'  => 4,
				'message' => __( 'Contact not exist', 'wp-marketing-automations-pro' ),
			);
		}

		return $contact->add_note_to_contact( $this->data );
	}

	public function process_v2() {
		if ( ! is_email( $this->data['email'] ) ) {
			return $this->skipped_response( __( 'Required field email is missing.', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() ) {
			return $this->error_response( __( 'Contact not exist.', 'wp-marketing-automations-pro' ) );
		}

		$contact->add_note_to_contact( $this->data );

		return $this->success_message( 'Contact note added.', 'wp-marketing-automations-pro' );
	}

	public function get_fields_schema() {
		$note_types = BWFAN_Common::get_contact_notes_type();
		$def        = array(
			array(
				'value' => '',
				'label' => __( 'Select', 'wp-marketing-automations-pro' ),
			)
		);
		$note_types = array_merge( $def, $note_types );

		return [
			[
				'id'          => 'title',
				'type'        => 'text',
				'label'       => __( 'Title', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Title', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'body',
				'type'        => 'textarea',
				'label'       => __( 'Note', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Note', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'note_type',
				'type'        => 'wp_select',
				'label'       => __( 'Note Type', 'wp-marketing-automations-pro' ),
				'options'     => $note_types,
				'placeholder' => __( 'Choose Note Type', 'wp-marketing-automations-pro' ),
				'tip'         => "",
				"description" => "",
				"required"    => true,
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['body'] ) || empty( $data['body'] ) ) {
			return '';
		}

		return $data['body'];
	}
}

return 'BWFAN_CRM_Add_Contact_Note';
