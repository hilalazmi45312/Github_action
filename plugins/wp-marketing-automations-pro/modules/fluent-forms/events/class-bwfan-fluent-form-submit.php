<?php

#[AllowDynamicProperties]
final class BWFAN_Fluent_Form_Submit extends BWFAN_Event {
	private static $instance = null;
	public $form_id = 0;
	public $form_title = '';
	public $fields = [];
	public $email = '';
	public $insert_id = '';
	public $first_name = '';
	public $last_name = '';
	public $contact_phone = '';
	public $mark_subscribe = false;

	private function __construct() {
		$this->event_merge_tag_groups = array( 'fluentforms', 'bwf_contact' );
		$this->event_name             = esc_html__( 'Form Submits', 'wp-marketing-automations-pro' );
		$this->event_desc             = esc_html__( 'This event runs after a form is submitted', 'wp-marketing-automations-pro' );
		$this->event_rule_groups      = array(
			'fluentforms',
			'bwf_contact_segments',
			'bwf_contact',
			'bwf_contact_fields',
			'bwf_contact_user',
			'bwf_contact_wc',
			'bwf_contact_geo',
			'bwf_engagement',
			'bwf_broadcast'
		);
		$this->optgroup_label         = esc_html__( 'Fluent Form', 'wp-marketing-automations-pro' );
		$this->priority               = 10;
		$this->customer_email_tag     = '';
		$this->v2                     = true;
		$this->force_async            = true;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function load_hooks() {
		add_action( 'wp_ajax_bwfan_get_fluent_form_fields', array( $this, 'bwfan_get_fluent_form_fields' ) );
		add_action( 'fluentform_submission_inserted', array( $this, 'process' ), 10, 3 );
		add_filter( 'bwfan_all_event_js_data', array( $this, 'add_form_data' ), 10, 2 );
	}

	/**
	 * Localize data for html fields for the current event.
	 */
	public function admin_enqueue_assets() {
		if ( BWFAN_Common::is_load_admin_assets( 'automation' ) ) {
			$data = $this->get_view_data();

			BWFAN_Core()->admin->set_events_js_data( $this->get_slug(), 'form_options', $data );
		}
	}

	public function get_view_data() {

		$options      = array();
		$fluent_forms = wpFluent()->table( 'fluentform_forms' )->select( [ 'id', 'title' ] )->orderBy( 'id', 'DESC' )->get();

		if ( ! empty( $fluent_forms ) ) {
			foreach ( $fluent_forms as $fluent_form ) {
				$options[ $fluent_form->id ] = $fluent_form->title;
			}
		}

		return $options;
	}

	/**
	 * Show the html fields for the current event.
	 */
	public function get_view( $db_eventmeta_saved_value ) {

		?>
        <script type="text/html" id="tmpl-event-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            selected_form_id = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'form_id')) ? data.eventSavedData.form_id : '';
            selected_field_map = (_.has(data, 'eventSavedData') &&_.has(data.eventSavedData, 'email_map')) ? data.eventSavedData.email_map : '';
            #>
            <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-mt-15 bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Select Form', 'wp-marketing-automations-pro' ); ?></label>
                <select id="bwfan-fluent_form_submit_form_id" class="bwfan-input-wrapper" name="event_meta[form_id]">
                    <option value=""><?php esc_html_e( 'Choose Form', 'wp-marketing-automations-pro' ); ?></option>
                    <#
                    if(_.has(data.eventFieldsOptions, 'form_options') && _.isObject(data.eventFieldsOptions.form_options) ) {
                    _.each( data.eventFieldsOptions.form_options, function( value, key ){
                    selected =(key == selected_form_id)?'selected':'';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    } #>
                </select>
            </div>

            <#
            show_mapping = !_.isEmpty(selected_form_id)?'block':'none';
            #>
            <div class="bwfan-fluent-forms-map bwfan-col-sm-12 bwfan-p-0 bwfan-mt-5">
                <div class="bwfan_spinner bwfan_hide"></div>
                <div class="bwfan-col-sm-12 bwfan-p-0 bwfan-fluent-field-map" style="display:{{show_mapping}}">
                    <label for="" class="bwfan-label-title">
						<?php esc_html_e( 'Select Email Field', 'wp-marketing-automations-pro' ); ?>
                        <div class="bwfan_tooltip" data-size="2xl">
                            <span class="bwfan_tooltip_text" data-position="top"><?php esc_html_e( 'Map the email field to be used by appropriate Rules and Actions.', 'wp-marketing-automations-pro' ); ?></span>
                        </div>
                    </label>
                    <select id="bwfan-fluent_email_field_map" class="bwfan-input-wrapper" name="event_meta[email_map]">
                        <option value=""><?php esc_html_e( 'none', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        _.each( bwfan_events_js_data['fluent_form_submit']['selected_form_fields'], function( value, key ){
                        selected =(key == selected_field_map)?'selected':'';
                        #>
                        <option value="{{key}}" {{selected}}>{{value}}</option>
                        <# })
                        #>
                    </select>
                </div>
            </div>
        </script>
        <script>
            jQuery(document).on('change', '#bwfan-fluent_form_submit_form_id', function () {
                var selected_id = jQuery(this).val();
                bwfan_events_js_data['fluent_form_submit']['selected_id'] = selected_id;
                if (_.isEmpty(selected_id)) {
                    jQuery(".bwfan-fluent-field-map").hide();
                    return false;
                }
                jQuery(".bwfan-fluent-forms-map .bwfan_spinner").removeClass('bwfan_hide');
                jQuery(".bwfan-fluent-field-map").hide();
                jQuery.ajax({
                    method: 'post',
                    url: "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>",
                    datatype: "JSON",
                    data: {
                        action: 'bwfan_get_fluent_form_fields',
                        id: selected_id,
                    },
                    success: function (response) {
                        jQuery(".bwfan-fluent-forms-map .bwfan_spinner").addClass('bwfan_hide');
                        jQuery(".bwfan-fluent-field-map").show();
                        update_fluent_email_field_map(response.fields);
                        bwfan_events_js_data['fluent_form_submit']['selected_form_fields'] = response.fields;
                    }
                });
            });

            function update_fluent_email_field_map(fields) {
                jQuery("#bwfan-fluent_email_field_map").html('');
                var option = '<option value="">none</option>';
                if (_.size(fields) > 0 && _.isObject(fields)) {
                    _.each(fields, function (v, e) {
                        option += '<option value="' + e + '">' + v + '</option>';
                    });
                }
                jQuery("#bwfan-fluent_email_field_map").html(option);
            }

            jQuery('body').on('bwfan-change-rule', function (e, v) {
                if ('fluent_form_field' !== v.value) {
                    return;
                }

                var options = '';

                _.each(bwfan_events_js_data['fluent_form_submit']['selected_form_fields'], function (value, key) {
                    options += '<option value="' + key + '">' + value + '</option>';
                });

                v.scope.find('.bwfan_fluent_form_fields').html(options);
            });

            jQuery('body').on('bwfan-selected-merge-tag', function (e, v) {
                if ('fluent_form_field' !== v.tag) {
                    return;
                }

                var options = '';
                var i = 1;
                var selected = '';

                _.each(bwfan_events_js_data['fluent_form_submit']['selected_form_fields'], function (value, key) {
                    selected = (i == 1) ? 'selected' : '';
                    options += '<option value="' + key + '" ' + selected + '>' + value + '</option>';
                    i++;
                });

                jQuery('.bwfan_fluent_form_fields').html(options);
                jQuery('.bwfan_tag_select').trigger('change');
            });
        </script>
		<?php
	}

	public function bwfan_get_fluent_form_fields() {
		BWFAN_PRO_Common::nocache_headers();
		$form_id = absint( sanitize_text_field( $_POST['id'] ) ); // WordPress.CSRF.NonceVerification.NoNonceVerification
		$fields  = [];
		if ( ! empty( $form_id ) ) {
			$fields = $this->get_form_fields( $form_id );
		}

		if ( isset( $_POST['fromApp'] ) && $_POST['fromApp'] ) {
			$final_arr = [];
			foreach ( $fields as $key => $value ) {
				$final_arr[] = [
					'key'   => $key,
					'value' => $value
				];
			}

			wp_send_json( array(
				'results' => $final_arr
			) );
			exit;
		}

		wp_send_json( array(
			'fields' => $fields,
		) );
	}

	/** get fluent form fields
	 *
	 * @param $form_id
	 *
	 * @return array
	 */
	public function get_form_fields( $form_id ) {
		if ( empty( $form_id ) ) {
			return array();
		}

		$form        = wpFluent()->table( 'fluentform_forms' )->find( $form_id );
		$form_fields = json_decode( $form->form_fields );
		$fields_data = array();
		if ( empty( $form_fields ) ) {
			return $fields_data;
		}

		$column_field_data = array();
		foreach ( $form_fields as $fields ) {
			if ( isset( $fields->attributes ) && isset( $fields->attributes->type ) && ( ( 'submit' === $fields->attributes->type ) ) ) {
				continue;
			}

			if ( ! is_array( $fields ) || 0 === count( $fields ) ) {
				continue;
			}
			foreach ( $fields as $field ) {
				/** in case fields are in column */
				if ( isset( $field->columns ) && ! empty( $field->columns ) ) {
					$column_field_data[] = $this->get_fluent_col_fields( $field->columns );
					continue;
				}

				/** Getting child fields */
				if ( isset( $field->fields ) && ! empty( $field->fields ) ) {
					foreach ( $field->fields as $child_field ) {
						if ( false === $child_field->settings->visible ) {
							continue;
						}
						$label = ! empty( $child_field->settings->label ) ? $child_field->settings->label : '';
						$label = empty( $label ) && ! empty( $child_field->attributes->placeholder ) ? $child_field->attributes->placeholder : $label;
						$label = empty( $label ) && ! empty( $child_field->attributes->name ) ? $child_field->attributes->name : $label;

						$fields_data[ $field->attributes->name . ':' . $child_field->attributes->name ] = $label;
					}
					continue;
				}

				$field_label = ! empty( $field->settings->label ) ? $field->settings->label : '';
				$field_label = empty( $field_label ) && ! empty( $field->settings->admin_field_label ) ? $field->settings->admin_field_label : $field_label;
				$field_label = empty( $field_label ) && ! empty( $field->attributes->name ) ? $field->attributes->name : $field_label;

				if ( isset( $field->attributes ) && isset( $field->attributes->name ) ) {
					$fields_data[ $field->attributes->name ] = $field_label;
				}
			}
		}

		if ( empty( $column_field_data ) ) {
			return array_filter( $fields_data );
		}

		foreach ( $column_field_data as $column_field ) {
			$fields_data = array_merge( $fields_data, $column_field );
		}

		return array_filter( $fields_data );
	}

	/**
	 * Get column fields
	 *
	 * @param $col_fields
	 *
	 * @return array
	 */
	public function get_fluent_col_fields( $col_fields ) {
		$fields_data = array();

		foreach ( $col_fields as $col_field ) {
			if ( empty( $col_field->fields ) ) {
				continue;
			}

			foreach ( $col_field->fields as $field ) {
				if ( isset( $field->attributes ) && isset( $field->attributes->name ) ) {
					$field_label = ! empty( $field->settings->label ) ? $field->settings->label : '';
					$field_label = empty( $field_label ) && ! empty( $field->settings->admin_field_label ) ? $field->settings->admin_field_label : $field_label;

					$fields_data[ $field->attributes->name ] = empty( $field_label ) && ! empty( $field->attributes->name ) ? $field->attributes->name : $field_label;
				}

				/** Checking for child fields */
				if ( empty( $field->fields ) ) {
					continue;
				}
				foreach ( $field->fields as $child_field ) {
					if ( false === $child_field->settings->visible ) {
						continue;
					}
					$label = ! empty( $child_field->settings->label ) ? $child_field->settings->label : '';
					$label = empty( $label ) && ! empty( $child_field->attributes->placeholder ) ? $child_field->attributes->placeholder : $label;
					$label = empty( $label ) && ! empty( $child_field->attributes->name ) ? $child_field->attributes->name : $label;

					$fields_data[ $field->attributes->name . ':' . $child_field->attributes->name ] = $label;

					unset( $fields_data[ $field->attributes->name ] );
				}

			}
		}

		return $fields_data;
	}

	public function process( $insertid, $formData, $form ) {
		$data               = $this->get_default_data();
		$data['insert_id']  = $insertid;
		$data['fields']     = $formData;
		$data['form_id']    = $form->id;
		$data['form_title'] = $form->title;

		$this->send_async_call( $data );
	}

	public function add_form_data( $event_js_data, $automation_meta ) {
		if ( ! isset( $automation_meta['event_meta'] ) || ! isset( $event_js_data['fluent_form_submit'] ) || ! isset( $automation_meta['event_meta']['form_id'] ) ) {
			return $event_js_data;
		}

		if ( isset( $automation_meta['event'] ) && ! empty( $automation_meta['event'] ) && 'fluent_form_submit' !== $automation_meta['event'] ) {
			return $event_js_data;
		}

		$event_js_data['fluent_form_submit']['selected_id'] = $automation_meta['event_meta']['form_id'];
		$fields                                             = $this->get_form_fields( $automation_meta['event_meta']['form_id'] );

		$event_js_data['fluent_form_submit']['selected_form_fields'] = $fields;

		return $event_js_data;
	}

	/**
	 * Set up rules data
	 *
	 * @param $automation_data
	 */
	public function pre_executable_actions( $automation_data ) {
		$email_map   = $automation_data['event_meta']['email_map'];
		$this->email = ( ! empty( $email_map ) && isset( $this->fields[ $email_map ] ) && is_email( $this->fields[ $email_map ] ) ) ? $this->fields[ $email_map ] : '';
		BWFAN_Core()->rules->setRulesData( $this->form_id, 'form_id' );
		BWFAN_Core()->rules->setRulesData( $this->form_title, 'form_title' );
		BWFAN_Core()->rules->setRulesData( $this->fields, 'fields' );
		BWFAN_Core()->rules->setRulesData( $this->email, 'email' );
		BWFAN_Core()->rules->setRulesData( $this->insert_id, 'insert_id' );
		BWFAN_Core()->rules->setRulesData( BWFAN_Common::get_bwf_customer( $this->email, $this->get_user_id_event() ), 'bwf_customer' );
	}

	public function get_user_id_event() {
		if ( is_email( $this->email ) ) {
			$user = get_user_by( 'email', $this->email );

			return ( $user instanceof WP_User ) ? $user->ID : false;
		}

		return false;
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
		$data_to_send['global']['form_id']    = $this->form_id;
		$data_to_send['global']['form_title'] = $this->form_title;
		$data_to_send['global']['fields']     = $this->fields;
		$data_to_send['global']['email']      = $this->email;
		$data_to_send['global']['insert_id']  = $this->insert_id;

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
		?>
        <li>
            <strong><?php echo esc_html__( 'Form ID:', 'wp-marketing-automations-pro' ); ?> </strong>
            <span><?php echo esc_html__( $global_data['form_id'] ); ?></span>
        </li>
        <li>
            <strong><?php echo esc_html__( 'Form Title:', 'wp-marketing-automations-pro' ); ?> </strong>
			<?php echo esc_html__( $global_data['form_title'] ); ?>
        </li>
		<?php
		if ( isset( $global_data['fields'] ) && is_array( $global_data['fields'] ) && count( $global_data['fields'] ) > 0 ) {
			$h = 0;
			foreach ( $global_data['fields'] as $key => $value ) {
				if ( '_' === substr( $key, 0, 1 ) ) {
					continue;
				}
				if ( ! empty( $value ) ) {
					?>
                    <li>
                        <strong><?php echo esc_html__( 'Field ', 'wp-marketing-automations-pro' ) . '(' . esc_html( $key ); ?>): </strong>
						<?php echo is_array( $value ) ? implode( ', ', esc_html( $value ) ) : esc_html( $value ); ?>
                    </li>
					<?php
					$h ++;
				}
				if ( 2 <= $h ) {
					break;
				}
			}
		}

		return ob_get_clean();
	}

	/**
	 * Set global data for all the merge tags which are supported by this event.
	 *
	 * @param $task_meta
	 */
	public function set_merge_tags_data( $task_meta ) {
		$get_data = BWFAN_Merge_Tag_Loader::get_data( 'form_id' );
		if ( ( empty( $get_data ) || intval( $get_data ) !== intval( $task_meta['global']['form_id'] ) ) ) {
			$set_data = array(
				'form_id'    => intval( $task_meta['global']['form_id'] ),
				'form_title' => $task_meta['global']['form_title'],
				'fields'     => $task_meta['global']['fields'],
				'email'      => $task_meta['global']['email'],
				'insert_id'  => $task_meta['global']['insert_id'],
			);
			BWFAN_Merge_Tag_Loader::set_data( $set_data );
		}
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_async_data() {
		$this->form_id    = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title = BWFAN_Common::$events_async_data['form_title'];
		$this->fields     = BWFAN_Common::$events_async_data['fields'];
		$this->insert_id  = BWFAN_Common::$events_async_data['insert_id'];

		return $this->run_automations();
	}

	public function get_email_event() {
		return is_email( $this->email ) ? $this->email : false;
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
			$match_form_id = isset( $automation_data['event_meta']['form_id'] ) ? $automation_data['event_meta']['form_id'] : 0;
			if ( absint( $this->form_id ) !== absint( $match_form_id ) ) {
				unset( $automations_arr_temp[ $automation_id ] );
			}
		}

		return $automations_arr_temp;
	}

	/**
	 * v2 Method: Validate event settings
	 *
	 * @param $automation_data
	 *
	 * @return bool
	 */

	public function validate_v2_event_settings( $automation_data ) {
		if ( absint( $automation_data['form_id'] ) !== absint( $automation_data['event_meta']['bwfan-fluent_form_submit_form_id'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Capture the async data for the current event.
	 * @return array|bool
	 */
	public function capture_v2_data( $automation_data ) {
		$map_fields     = isset( $automation_data['event_meta']['bwfan-form-field-map'] ) ? $automation_data['event_meta']['bwfan-form-field-map'] : [];
		$email_map      = isset( $map_fields['bwfan_email_field_map'] ) ? $map_fields['bwfan_email_field_map'] : '';
		$first_name_map = isset( $map_fields['bwfan_first_name_field_map'] ) ? $map_fields['bwfan_first_name_field_map'] : '';
		$last_name_map  = isset( $map_fields['bwfan_last_name_field_map'] ) ? $map_fields['bwfan_last_name_field_map'] : '';
		$phone_map      = isset( $map_fields['bwfan_phone_field_map'] ) ? $map_fields['bwfan_phone_field_map'] : '';

		$this->form_id        = BWFAN_Common::$events_async_data['form_id'];
		$this->form_title     = BWFAN_Common::$events_async_data['form_title'];
		$this->fields         = BWFAN_Common::$events_async_data['fields'];
		$this->insert_id      = BWFAN_Common::$events_async_data['insert_id'];
		$this->email          = ( ! empty( $email_map ) && isset( $this->fields[ $email_map ] ) && is_email( $this->fields[ $email_map ] ) ) ? $this->fields[ $email_map ] : '';
		$this->first_name     = ! empty( $first_name_map ) ? $this->get_field_data( $first_name_map ) : '';
		$this->last_name      = ! empty( $last_name_map ) ? $this->get_field_data( $last_name_map ) : '';
		$this->contact_phone  = ! empty( $phone_map ) ? $this->get_field_data( $phone_map ) : '';
		$this->mark_subscribe = isset( $automation_data['event_meta']['bwfan-mark-contact-subscribed'] ) ? $automation_data['event_meta']['bwfan-mark-contact-subscribed'] : 0;

		$automation_data['form_id']       = $this->form_id;
		$automation_data['form_title']    = $this->form_title;
		$automation_data['fields']        = $this->fields;
		$automation_data['email']         = $this->email;
		$automation_data['insert_id']     = $this->insert_id;
		$automation_data['first_name']    = $this->first_name;
		$automation_data['last_name']     = $this->last_name;
		$automation_data['contact_phone'] = $this->contact_phone;

		$automation_data['mark_contact_subscribed'] = $this->mark_subscribe;

		BWFAN_PRO_Common::maybe_create_update_contact( $automation_data );

		return $automation_data;
	}

	/** get field value */
	public function get_field_data( $field ) {
		$field_value = '';
		if ( ! empty( $field ) && isset( $this->fields[ $field ] ) ) {
			$field_value = $this->fields[ $field ];
		} else if ( strpos( $field, ':' ) !== false ) {
			$fieldsgroup      = explode( ':', $field );
			$field_name       = $fieldsgroup[1];
			$field_group_data = isset( $this->fields[ $fieldsgroup[0] ] ) ? $this->fields[ $fieldsgroup[0] ] : array();
			$field_value      = isset( $field_group_data[ $field_name ] ) ? $field_group_data[ $field_name ] : '';
		}

		return $field_value;
	}

	/**
	 * v2 Method: Get fields schema
	 * @return array[][]
	 */
	public function get_fields_schema() {
		$forms = $this->get_view_data();
		$forms = empty( $forms ) ? [ '' => 'No forms to select' ] : array_replace( [ '' => 'Select' ], $forms );
		$forms = BWFAN_PRO_Common::prepared_field_options( $forms );

		return [
			[
				'id'          => 'bwfan-fluent_form_submit_form_id',
				'type'        => 'select',
				'options'     => $forms,
				'label'       => __( 'Select Form', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"placeholder" => __( 'Select', 'wp-marketing-automations-pro' ),
				"required"    => true,
				"errorMsg"    => __( "Form is required.", 'wp-marketing-automations-pro' ),
				"description" => ""
			],
			[
				'id'          => 'bwfan-form-field-map',
				'type'        => 'bwf_form_submit',
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_fluent_form_fields',
				"ajax_field"  => [
					'id' => 'bwfan-fluent_form_submit_form_id'
				],
				"fieldChange" => 'bwfan-fluent_form_submit_form_id',
				"toggler"     => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-fluent_form_submit_form_id',
							'value' => '',
						)
					),
					'relation' => 'AND',
				]
			],
			[
				'id'            => 'bwfan-mark-contact-subscribed',
				'type'          => 'checkbox',
				'checkboxlabel' => __( 'Mark Contact as Subscribed', 'wp-marketing-automations-pro' ),
				'description'   => '',
				"toggler"       => [
					'fields'   => array(
						array(
							'id'    => 'bwfan-fluent_form_submit_form_id',
							'value' => '',
						),
					),
					'relation' => 'AND',
				]
			]
		];
	}
}

/**
 * Register this event to a source.
 * This will show the current event in dropdown in single automation screen.
 */
if ( bwfan_is_fluent_forms_active() ) {
	return 'BWFAN_Fluent_Form_Submit';
}
