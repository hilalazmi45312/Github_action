<?php

#[AllowDynamicProperties]
final class BWFAN_CRM_Field_Updated extends BWFAN_Event {
	private static $instance = null;
	public $field_id = null;
	public $contact_id = null;
	public $old_value = null;
	public $new_value = null;
	public $email = null;
	public $user_id = null;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'bwfan_crm_fields', 'bwf_contact' );
		$this->optgroup_label         = esc_html__( 'Contact', 'wp-marketing-automations-pro' );
		$this->event_name             = esc_html__( 'Field Updated', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after an contact field updated.', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->priority               = 25;
		$this->customer_email_tag     = '{{contact_email}}';
		$this->v2                     = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'bwfcrm_contact_field_updated', array( $this, 'process' ), 10, 4 );
		add_action( 'bwfcrm_contact_custom_field_updated', array( $this, 'process' ), 10, 4 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( false === BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			return;
		}
		$fields = $this->get_view_data();

		BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'fields', $fields );
	}

	public function get_view_data() {
		$all_fields = BWFCRM_Fields::get_fields();

		if ( empty( $all_fields ) ) {
			return array();
		}

		$field_data = array();
		foreach ( $all_fields as $field_key => $field ) {
			$field_data[ $field_key ] = ! empty( $field['name'] ) ? $field['name'] : $field;
		}

		return $field_data;
	}

	/**
	 * Make the required data for the current event and send it asynchronously.
	 *
	 * @param $field_id
	 * @param $new_value
	 * @param $old_value
	 * @param $contact_id
	 *
	 * @return void
	 */
	public function process( $field_id, $new_value, $old_value, $contact_id ) {
		if ( empty( $field_id ) ) {
			return;
		}

		$data               = $this->get_default_data();
		$data['field_id']   = $field_id;
		$data['new_value']  = $new_value;
		$data['old_value']  = $old_value;
		$data['contact_id'] = $contact_id;

		/** class exists than get the bwfcrm contact object */
		$contact_object = new BWFCRM_Contact( $contact_id );
		$data['email']  = ( $contact_object instanceof BWFCRM_Contact ) ? $contact_object->contact->get_email() : '';

		$this->send_async_call( $data );
	}

	/**
	 * Registers the tasks for current event.
	 *
	 * @param $automation_id
	 * @param $integration_data
	 * @param $event_data
	 */
	public function register_tasks( $automation_id, $integration_data, $event_data ) {
		if ( ! is_array( $integration_data ) ) {
			return;
		}

		$data_to_send = $this->get_event_data();

		$this->create_tasks( $automation_id, $integration_data, $event_data, $data_to_send );
	}

	public function get_event_data() {
		$data_to_send                         = [ 'global' => [] ];
		$data_to_send['global']['field_id']   = $this->field_id;
		$data_to_send['global']['contact_id'] = $this->contact_id;
		$data_to_send['global']['new_value']  = $this->new_value;
		$data_to_send['global']['old_value']  = $this->old_value;
		$data_to_send['global']['email']      = $this->email;

		return $data_to_send;
	}

	/**
	 * Make the view data for the current event which will be shown in task listing screen.
	 *
	 * @param $global_data
	 *
	 * @return false|string
	 */
	public function get_task_view( $global_data ) {
		ob_start();

		$field_id = isset( $global_data['field_id'] ) ? $global_data['field_id'] : 0;
		?>
        <li>
            <strong><?php echo esc_html__( 'Field Id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <a target="_blank" href=""><?php echo esc_html__( $field_id ); ?></a>
        </li>
		<?php
		if ( ! empty( $global_data['old_value'] ) ) {
			?>
            <li>
                <strong><?php echo esc_html__( 'Old Value:', 'wp-marketing-automations-pro' ); ?> </strong>
                <span><?php echo esc_html__( $global_data['old_value'] ); ?></span>
            </li>
			<?php
		}
		?>
        <li>
            <strong><?php echo esc_html__( 'New Value:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['new_value'] ); ?></span>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Contact_id:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['contact_id'] ); ?></span>
        </li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->field_id   = BWFAN_Common::$events_async_data['field_id'];
		$this->contact_id = BWFAN_Common::$events_async_data['contact_id'];
		$this->old_value  = BWFAN_Common::$events_async_data['old_value'];
		$this->new_value  = BWFAN_Common::$events_async_data['new_value'];
		$this->email      = BWFAN_Common::$events_async_data['email'];

		return $this->run_automations();
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {
		?>
        <script type="text/html" id="tmpl-event-<?php esc_attr_e( $this->get_slug() ); ?>">
            <#
            selected_field = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'field')) ? data.eventSavedData.field : '';
            selected_runs = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'runs')) ? data.eventSavedData.runs : '';
            selected_from = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'bwfan_field_from')) ? data.eventSavedData.bwfan_field_from : 'any';
            selected_to = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'bwfan_field_to')) ? data.eventSavedData.bwfan_field_to : 'any';
            selected_from_specific = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'bwfan_from_specific_value')) ? data.eventSavedData.bwfan_from_specific_value : '';
            selected_to_specific = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'bwfan_to_specific_value')) ? data.eventSavedData.bwfan_to_specific_value : '';

            selected_field_from_any = selected_from==='any' || _.isEmpty(selected_from)?'checked':'';
            selected_field_from_specific = selected_from==='specific'?'checked':'';

            selected_field_to_any = selected_to==='any' || _.isEmpty(selected_to)?'checked':'';
            selected_field_to_specific = selected_to==='specific'?'checked':'';

            hide_from_specific_value = selected_from =='any'?'bwfan-display-none':'';
            hide_to_specific_value = selected_to =='any'?'bwfan-display-none':'';
            #>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-15 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Field', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[field]">
                    <#
                    if(_.has(data.eventFieldsOptions, 'fields') && _.isObject(data.eventFieldsOptions.fields) ) {
                    _.each( data.eventFieldsOptions.fields, function( value, key ){
                    selected = (key == selected_field) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-15 bwfan-pl-0">
                <div class="bwfan_label">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'FROM', 'wp-marketing-automations-pro' ); ?></label>
                </div>
                <div class="bwfan_label_val">
                    <div class="radio-list">
                        <input type="radio" id="bwfan_field_from" name="event_meta[bwfan_field_from]" value="any" {{selected_field_from_any}}><?php esc_html_e( 'Any Value', 'wp-marketing-automations-pro' ); ?>
                        <input type="radio" id="bwfan_field_from" name="event_meta[bwfan_field_from]" value="specific" {{selected_field_from_specific}}><?php esc_html_e( 'A Specific Value', 'wp-marketing-automations-pro' ); ?>
                    </div>
                </div>
            </div>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan_mt15 bwfan-field-from-specific {{hide_from_specific_value}}">
                <input type="text" name="event_meta[bwfan_from_specific_value]" id="bwfan_from_specific_value" value="{{selected_from_specific}}"/>
            </div>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-15 bwfan-pl-0">
                <div class="bwfan_label">
                    <label for="" class="bwfan-label-title"><?php esc_html_e( 'TO', 'wp-marketing-automations-pro' ); ?></label>
                </div>
                <div class="bwfan_label_val">
                    <div class="radio-list">
                        <input type="radio" id="bwfan_field_to" name="event_meta[bwfan_field_to]" value="any" {{selected_field_to_any}}><?php esc_html_e( 'Any Value', 'wp-marketing-automations-pro' ); ?>
                        <input type="radio" id="bwfan_field_to" name="event_meta[bwfan_field_to]" value="specific" {{selected_field_to_specific}}><?php esc_html_e( 'A Specific Value', 'wp-marketing-automations-pro' ); ?>
                    </div>
                </div>
            </div>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan_mt15 bwfan-field-to-specific {{hide_to_specific_value}} ">
                <input type="text" name="event_meta[bwfan_to_specific_value]" id="bwfan_to_specific_value" value="{{selected_to_specific}}"/>
            </div>
            <div class="bwfan_mt15"></div>
            <div class="bwfan-col-sm-15 bwfan-pl-0">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Runs', 'wp-marketing-automations-pro' ); ?></label>
                <select required id="" class="bwfan-input-wrapper" name="event_meta[runs]">
                    <option value="first_time" {{
                    'first_time' == selected_runs?'selected':'' }}><?php esc_html_e( 'the first time', 'wp-marketing-automations-pro' ); ?></option>
                    <option value="every_time" {{
                    'every_time' == selected_runs?'selected':'' }}><?php esc_html_e( 'every time', 'wp-marketing-automations-pro' ); ?></option>
                </select>
            </div>
        </script>

        <script>
            jQuery(document).on('change', '#bwfan_field_to', function () {
                var field_to = jQuery(this).val();
                if ("specific" === field_to) {
                    jQuery(".bwfan-field-to-specific").removeClass("bwfan-display-none");
                } else {
                    jQuery(".bwfan-field-to-specific").addClass("bwfan-display-none");
                    jQuery("#bwfan_to_specific_value").val("");
                }
            });

            jQuery(document).on('change', '#bwfan_field_from', function () {
                var field_from = jQuery(this).val();
                if ("specific" === field_from) {
                    jQuery(".bwfan-field-from-specific").removeClass("bwfan-display-none");
                } else {
                    jQuery(".bwfan-field-from-specific").addClass("bwfan-display-none");
                    jQuery("#bwfan_from_specific_value").val("");
                }
            });
        </script>
		<?php
	}

	public function get_user_id_event() {
		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', absint( $this->email ) );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return ! empty( absint( $this->user_id ) ) ? absint( $this->user_id ) : false;
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'field_id' );
		if ( ( empty( $get_data ) || $get_data !== $task_meta['global']['field_id'] ) ) {
			$set_data = array(
				'field_id'   => $task_meta['global']['field_id'],
				'email'      => $task_meta['global']['email'],
				'contact_id' => intval( $task_meta['global']['contact_id'] ),
				'new_value'  => $task_meta['global']['new_value'],
				'old_value'  => $task_meta['global']['old_value'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Set up rules data
	 *
	 * @param $value
	 */
	public function pre_executable_actions( $value ) {
		BWFAN_Core()->rules->setRulesData( $this->field_id, 'field_id' );
		BWFAN_Core()->rules->setRulesData( $this->contact_id, 'contact_id' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->new_value, 'new_value' );
		BWFAN_Core()->rules->setRulesData( $this->old_value, 'old_value' );
	}

	/**
	 * Validating form id after submission with the selected form id in the event
	 *
	 * @param $automations_arr
	 *
	 * @return mixed
	 */
	public function validate_event_data_before_creating_task( $automations_arr ) {
		$automations_arr_temp = $automations_arr;
		foreach ( $automations_arr as $automation_id => $automation_data ) {

			$field            = $automation_data['event_meta']['field'];
			$runs             = $automation_data['event_meta']['runs'];
			$field_from       = $automation_data['event_meta']['bwfan_field_from'];
			$field_to         = $automation_data['event_meta']['bwfan_field_to'];
			$field_from_value = $automation_data['event_meta']['bwfan_from_specific_value'];
			$field_to_value   = $automation_data['event_meta']['bwfan_to_specific_value'];
			$run_count        = 0;
			if ( 'once' === $runs ) {
				$run_count = BWFAN_Model_Automations::get_contact_automation_run_count( $automation_id, $this->contact_id );
			}

			/** checking condition for every time */
			if ( $this->field_id === $field && 'every_time' === $runs && 'any' === $field_from && 'any' === $field_to ) {
				continue;
			}

			if ( $this->field_id === $field && 'every_time' === $runs && 'specific' === $field_from && $field_from_value === $this->old_value && 'specific' === $field_to && $field_to_value === $this->new_value ) {
				continue;
			}

			if ( $this->field_id === $field && 'every_time' === $runs && 'specific' === $field_from && $field_from_value === $this->old_value && 'any' === $field_to ) {
				continue;
			}

			if ( $this->field_id === $field && 'every_time' === $runs && 'any' === $field_from && 'specific' === $field_to && $field_to_value === $this->new_value ) {
				continue;
			}

			/** checking condition for first time */
			if ( $this->field_id === $field && 'first_time' === $runs && 'any' === $field_from && 'any' === $field_to && 0 === $run_count ) {
				continue;
			}

			if ( $this->field_id === $field && 'first_time' === $runs && 'specific' === $field_from && $field_from_value === $this->old_value && 'specific' === $field_to && $field_to_value === $this->new_value && 0 === $run_count ) {
				continue;
			}

			if ( $this->field_id === $field && 'first_time' === $runs && 'specific' === $field_from && $field_from_value === $this->old_value && 'any' === $field_to && 0 === $run_count ) {
				continue;
			}

			if ( $this->field_id === $field && 'first_time' === $runs && 'any' === $field_from && 'specific' === $field_to && $field_to_value === $this->new_value && 0 === $run_count ) {
				continue;
			}

			unset( $automations_arr_temp[ $automation_id ] );
		}

		return $automations_arr_temp;
	}

	public function validate_event_data_before_executing_task( $data ) {
		return $this->validate_field( $data );
	}

	public function validate_field( $data ) {
		if ( ! isset( $data['field_id'] ) || ! isset( $data['contact_id'] ) ) {
			return false;
		}

		if ( ! BWFCRM_Fields::is_field_exists( $data['field_id'] ) ) {
			$this->message_validate_event = __( 'Updated field not exist.', 'wp-marketing-automations-pro' );

			return false;
		}

		$contact = new BWFCRM_Contact( $data['contact_id'] );

		if ( ! $contact->is_contact_exists() ) {
			$this->message_validate_event = __( 'Contact not exist.', 'wp-marketing-automations-pro' );

			return false;
		}

		return true;
	}

	/**
	 * Capture v2 event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$this->field_id   = BWFAN_Common::$events_async_data['field_id'];
		$this->contact_id = BWFAN_Common::$events_async_data['contact_id'];
		$this->old_value  = BWFAN_Common::$events_async_data['old_value'];
		$this->new_value  = BWFAN_Common::$events_async_data['new_value'];
		$this->email      = BWFAN_Common::$events_async_data['email'];

		$automation_data['field_id']   = $this->field_id;
		$automation_data['contact_id'] = $this->contact_id;
		$automation_data['email']      = $this->email;
		$automation_data['old_value']  = $this->old_value;
		$automation_data['new_value']  = $this->new_value;

		return $automation_data;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */
	public function validate_v2_event_settings( $automation_data ) {
		return $this->validate_field( $automation_data );
	}

	/**
	 * v2 Method: Get field schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$field_options = $this->get_view_data();

		return [
			[
				'id'          => 'bwfan-crm_select_field',
				'type'        => 'wp_select',
				'options'     => $field_options,
				'label'       => __( 'Field', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				"description" => ""
			],
			[
				'id'          => 'bwfan_from_crm_field_value',
				'label'       => __( "FROM", 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( "Any Value", 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label'   => __( " A Specific Value", 'wp-marketing-automations-pro' ),
						'value'   => 'specific',
						'tooltip' => __( "This will attach the image of highest price product of a cart/ order in the message.", 'wp-marketing-automations-pro' )
					]
				],
				'description' => ""
			],
			[
				'id'          => 'bwfan_to_crm_field_value',
				'label'       => __( "TO", 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( "Any Value", 'wp-marketing-automations-pro' ),
						'value' => 'any'
					],
					[
						'label'   => __( " A Specific Value", 'wp-marketing-automations-pro' ),
						'value'   => 'specific',
						'tooltip' => __( "This will attach the image of highest price product of a cart/ order in the message.", 'wp-marketing-automations-pro' )
					]
				],
				'description' => ""
			],
		];
	}

}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( class_exists( 'BWFCRM_Fields' ) ) {
	//return 'BWFAN_CRM_Field_Updated';
}
