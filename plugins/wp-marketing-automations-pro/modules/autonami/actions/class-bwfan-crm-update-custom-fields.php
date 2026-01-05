<?php

final class BWFAN_CRM_Update_CustomFields extends BWFAN_Action {

	private static $instance = null;

	private function __construct() {
		$this->action_name     = __( 'Update Fields', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action adds/ updates the custom fields of the contact', 'wp-marketing-automations-pro' );
		$this->action_priority = 60;
		$this->support_v2      = true;
		$this->required_fields = array( 'email', 'custom_fields' );
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
			$data = $this->get_view_data();
			BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'custom_fields_options', $data );
		}
	}

	public function get_view_data() {
		$custom_fields = BWFCRM_Fields::get_fields( null, 1 );

		/** removed array_merge and used array_replace instead, array_merge reindexes the numeric index */
		$custom_fields = is_array( $custom_fields ) ? array_replace( [ 'email' => 'Email' ], $custom_fields ) : [];

		$fields = [
			[
				'value' => '',
				'label' => __( 'Select', 'wp-marketing-automations-pro' )
			]
		];

		foreach ( $custom_fields as $field_id => $field ) {
			if ( 'status' === $field_id ) {
				continue;
			}

			$hint = '';

			/** Get options if field types are select(4), radio(5) and checkbox(6) */
			$field_types = [ 4, 5, 6 ];
			if ( isset( $field['type'] ) && in_array( intval( $field['type'] ), $field_types, true ) ) {
				$options = isset( $field['meta']['options'] ) && is_array( $field['meta']['options'] ) ? implode( ', ', $field['meta']['options'] ) : '';
				if ( intval( $field['type'] ) === 6 ) {
					$hint = "Enter any of these options: $options. Use comma seperated values for multiple options.";
				} else {
					$hint = "Enter one of these options: $options";
				}
			}

			/** Set hint for country field */
			if ( 'country' === $field_id ) {
				$hint = "Enter two digit ISO Code";
			}

			/** Set hint for date field type */
			if ( isset( $field['type'] ) && 7 === intval( $field['type'] ) ) {
				$hint = "Enter date in Y-m-d format";
			}

			$type_datetime = property_exists( 'BWFCRM_Fields', 'TYPE_DATETIME' ) && BWFCRM_Fields::$TYPE_DATETIME ? BWFCRM_Fields::$TYPE_DATETIME : 8;
			$type_time     = property_exists( 'BWFCRM_Fields', 'TYPE_TIME' ) && BWFCRM_Fields::$TYPE_TIME ? BWFCRM_Fields::$TYPE_TIME : 9;
			/** Set hint for datetime field type */
			if ( isset( $field['type'] ) && $type_datetime === intval( $field['type'] ) ) {
				$hint = "Enter date in Y-m-d H:i format";
			}

			/** Set hint for time field type */
			if ( isset( $field['type'] ) && $type_time === intval( $field['type'] ) ) {
				$hint = "Enter time in H:i format";
			}

			/** Set hint for number field type */
			if ( isset( $field['type'] ) && 2 === intval( $field['type'] ) ) {
				$hint = "Enter value in number";
			}

			$fields[] = [
				'value' => $field_id,
				'label' => isset( $field['name'] ) ? $field['name'] : $field,
				'hint'  => $hint,
				'type'  => isset( $field['type'] ) ? $field['type'] : 1,
			];
		}

		return $fields;
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug = $this->get_slug();
		?>
        <script type="text/html" id="tmpl-repeater-ui-<?php echo esc_attr__( $unique_slug ); ?>">
            <div class="bwfan-input-form clearfix gs-repeater-fields">
                <div class="bwfan-col-sm-5 bwfan-p-0">
                    <select data-parent-slug="<?php echo esc_attr__( $unique_slug ); ?>" required
                            class="bwfan-input-wrapper wfacp_ac_custom_field"
                            name="bwfan[{{data.action_id}}][data][custom_fields][field][{{data.index}}]">
                        <option value=""><?php echo esc_html__( 'Choose Field', 'wp-marketing-automations-pro' ); ?></option>
                        <#
                        if(_.has(data.actionFieldsOptions, 'custom_fields_options') &&
                        _.isObject(data.actionFieldsOptions.custom_fields_options) ) {
                        _.each( data.actionFieldsOptions.custom_fields_options, function( value, key ){
                        #>
                        <option value="{{value.value}}">{{value.label}}</option>
                        <# })
                        }
                        #>
                    </select>
                </div>
                <div class="bwfan-col-sm-6 bwfan-pr-0">
                    <input required type="text" class="bwfan-input-wrapper bwfan-input-merge-tags" value=""
                           name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-1 bwfan-pr-0">
                    <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <div class="bwfan-repeater-wrap">
                <label for="" class="bwfan-label-title">
					<?php
					echo esc_html__( 'Select Custom Fields', 'wp-marketing-automations-pro' );
					$message = __( 'Select available fields to update and if unable to locate then sync the connector.', 'wp-marketing-automations-pro' );
					echo $this->add_description( $message, '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
					echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <div class="clearfix bwfan-input-repeater bwfan_mb10">
                    <#
                    repeaterArr = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data,
                    'custom_fields')) ? data.actionSavedData.data.custom_fields : {};
                    repeaterCount = _.size(repeaterArr.field);
                    validate_field = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data,
                    'validate_fields')) ? data.actionSavedData.data.validate_fields : 0;

                    validate_field = 1=== parseInt(validate_field)?'checked':0;
                    if(repeaterCount == 0) {
                    repeaterArr = {field:{0:''}, field_value:{0:''}};
                    }

                    if(repeaterCount >= 0) {
                    h=0;
                    _.each( repeaterArr.field, function( value, key ){
                    #>
                    <div class="bwfan-input-form clearfix gs-repeater-fields">
                        <div class="bwfan-col-sm-5 bwfan-p-0">
                            <select required class="bwfan-input-wrapper wfacp_ac_custom_field"
                                    name="bwfan[{{data.action_id}}][data][custom_fields][field][{{h}}]">
                                <option value=""><?php echo esc_html__( 'Choose Field', 'wp-marketing-automations-pro' ); ?></option>
                                <#
                                if(_.has(data.actionFieldsOptions, 'custom_fields_options') &&
                                _.isObject(data.actionFieldsOptions.custom_fields_options) ) {
                                _.each( data.actionFieldsOptions.custom_fields_options, function( column_option_value,
                                column_option_key ){
                                selected = (column_option_value.value == value) ? 'selected' : '';
                                #>
                                <option value="{{column_option_value.value}}" {{selected}}>
                                    {{column_option_value.label}}
                                </option>
                                <# })
                                }
                                #>
                            </select>
                        </div>
                        <div class="bwfan-col-sm-6 bwfan-pr-0">
                            <input required type="text" class="bwfan-input-wrapper"
                                   value="{{repeaterArr.field_value[key]}}"
                                   name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-1 bwfan-pr-0">
                            <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                        </div>
                    </div>
                    <# h++;
                    });
                    }
                    repeaterCount = repeaterCount + 1;
                    #>
                </div>
                <div class="bwfan-col-sm-12 bwfan-pl-0">
                    <a href="#"
                       class="bwfan-add-repeater-data bwfan-repeater-ui-<?php echo esc_attr__( $unique_slug ); ?>"
                       data-groupid="{{data.action_id}}"
                       data-count="{{repeaterCount}}"><?php esc_html_e( 'Add More', 'wp-marketing-automations-pro' ); ?></a>
                </div>
            </div>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan_mt15 bwfan-mb-15">
                <label for="bwfan-validate_fields" class="bwfan-label-title">Advanced</label>
                <input type="checkbox" name="bwfan[{{data.action_id}}][data][validate_fields]"
                       id="bwfan-ac_validate_fields" value="1" class="validate_fields_1" {{validate_field}}>
                <label for="bwfan-ac_validate_fields" class="bwfan-checkbox-label">Do not update custom field(s) when
                    passed value is blank</label>
            </div>
        </script>

        <script>
            jQuery(document).ready(function ($) {
                /** Generate repeater UI by calling script template */
                $('body').on('click', '.bwfan-repeater-ui-<?php echo esc_attr__( $unique_slug ); ?>', function (event) {
                    event.preventDefault();
                    var $this = $(this);
                    var index = Number($this.attr('data-count'));
                    var action_id = $this.attr('data-groupid');
                    var template = wp.template('repeater-ui-<?php echo esc_attr__( $unique_slug ); ?>');

                    var actionFieldsOptions = {
                        custom_fields_options: bwfan_set_actions_js_data['<?php echo esc_attr__( $this->get_class_slug() ); ?>']['custom_fields_options']
                    };

                    $this.parents('.bwfan-repeater-wrap').find('.bwfan-input-repeater').append(template({
                        action_id: action_id,
                        index: index,
                        actionFieldsOptions: actionFieldsOptions
                    }));
                    index = index + 1;
                    $this.attr('data-count', index);
                });
            });
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

		$data_to_set['email']   = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';
		$data_to_set['user_id'] = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : 0;
		$data_to_set['phone']   = isset( $task_meta['global']['phone'] ) ? $task_meta['global']['phone'] : '';
		$fields                 = $task_meta['data']['custom_fields']['field'];
		$fields_value           = $task_meta['data']['custom_fields']['field_value'];
		$custom_fields          = array();
		$is_validate            = isset( $task_meta['data']['validate_fields'] ) ? $task_meta['data']['validate_fields'] : '';

		/** check if email not exists in global then get from user is if available */
		if ( empty( $data_to_set['email'] ) && ! empty( $data_to_set['user_id'] ) ) {
			$user_data            = get_userdata( $data_to_set['user_id'] );
			$data_to_set['email'] = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		add_filter( 'bwfan_order_date_format', [ $this, 'get_ymd' ] );
		foreach ( $fields as $key1 => $field_id ) {
			if ( isset( $this->default_fields[ $field_id ] ) ) {
				$data_to_set['have_default_field'] = true;
				$data_to_set[ $field_id ]          = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
				continue;
			}
			$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
		}
		remove_filter( 'bwfan_order_date_format', [ $this, 'get_ymd' ] );

		//filter custom fields to remove blank
		if ( 1 === intval( $is_validate ) ) {
			foreach ( $custom_fields as $key => $fields ) {
				if ( empty( $fields ) ) {
					unset( $custom_fields[ $key ] );
				}
			}
		}

		$data_to_set['custom_fields'] = $custom_fields;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set            = array();
		$data_to_set['email']   = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$data_to_set['user_id'] = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$data_to_set['phone']   = isset( $automation_data['global']['phone'] ) ? $automation_data['global']['phone'] : '';
		$fields                 = $step_data['custom_fields'];
		$is_validate            = isset( $step_data['validate_fields'] ) ? $step_data['validate_fields'] : '';

		if ( empty( $data_to_set['email'] ) ) {
			$user = ! empty( $automation_data['global']['user_id'] ) ? get_user_by( 'ID', $automation_data['global']['user_id'] ) : false;

			$data_to_set['email'] = $user instanceof WP_User ? $user->user_email : '';
		}

		if ( empty( $fields ) ) {
			return $data_to_set;
		}

		$custom_fields = [];
		foreach ( $fields as $field ) {
			$field_value = '';

			/** Checking if operator is set in field value */
			if ( isset( $field['field_value']['operator'] ) ) {
				$field_value = BWFAN_Common::decode_merge_tags( $field['field_value']['value'] );

				$cid = $automation_data['global']['cid'];
				/**Get field value from contact*/
				$contact             = new BWFCRM_Contact( $cid );
				$contact_field_value = isset( $contact->fields[ $field['field'] ] ) ? $contact->fields[ $field['field'] ] : 0;

				/**Set field value according to operator*/
				switch ( $field['field_value']['operator'] ) {
					case '+':
						$field_value = (float) $contact_field_value + (float) $field_value;
						break;
					case '-':
						$field_value = (float) $contact_field_value - (float) $field_value;
						break;
				}
			}

			$field_value = ! isset( $field['field_value']['value'] ) ? BWFAN_Common::decode_merge_tags( $field['field_value'] ) : $field_value;

			$contact_columns = array_merge( BWFCRM_Fields::$contact_columns, BWFCRM_Fields::$contact_address_columns );

			/** Checking if the field is exists in bwf_contact table's column then avoid further processing */
			if ( isset( $contact_columns[ $field['field'] ] ) || 'email' === $field['field'] ) {
				$custom_fields[ $field['field'] ] = $field_value;
				continue;
			}

			/** check if the field is date, checking and formatting should be after decoding */
			$field_type = BWFAN_Model_Fields::get_field_type( $field['field'] );
			if ( BWFCRM_Fields::$TYPE_DATE === intval( $field_type ) ) {
				$field_value = BWFCRM_Contact::get_date_value( $field_value );
				if ( ! $this->validate_date( $field_value ) ) {
					continue;
				}
			}

			$type_datetime = property_exists( 'BWFCRM_Fields', 'TYPE_DATETIME' ) && BWFCRM_Fields::$TYPE_DATETIME ? BWFCRM_Fields::$TYPE_DATETIME : 8;
			$type_time     = property_exists( 'BWFCRM_Fields', 'TYPE_TIME' ) && BWFCRM_Fields::$TYPE_TIME ? BWFCRM_Fields::$TYPE_TIME : 9;
			/** Check if the field is datetime, checking and formatting should be after decoding */
			if ( $type_datetime === intval( $field_type ) ) {
				$field_value = BWFCRM_Contact::get_date_value( $field_value, 'Y-m-d H:i' );
				if ( ! $this->validate_date( $field_value, 'Y-m-d H:i' ) ) {
					continue;
				}
			}

			/** Check if the field is time, checking and formatting should be after decoding */
			if ( $type_time === intval( $field_type ) ) {
				$field_value = BWFCRM_Contact::get_date_value( $field_value, 'H:i' );
			}

			$custom_fields[ $field['field'] ] = $field_value;
		}

		/** Filter custom fields to remove blank in case validate empty enabled */
		if ( 1 === intval( $is_validate ) ) {
			foreach ( $custom_fields as $key => $value ) {
				if ( empty( trim( $value ) ) ) {
					unset( $custom_fields[ $key ] );
				}
			}
		}
		$data_to_set['custom_fields'] = $custom_fields;

		return $data_to_set;
	}

	public function get_ymd() {
		return 'Y-m-d';
	}

	public function execute_action( $action_data ) {
		$this->set_data( $action_data['processed_data'] );

		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			return $this->show_fields_error();
		}

		$contact = new BWFCRM_Contact( $this->data['email'], true );

		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {
				return array(
					'status'  => 4,
					'message' => __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ),
				);
			}
		}

		$custom_field_updated = $contact->update_custom_fields( $this->data['custom_fields'] );

		if ( false === $custom_field_updated ) {
			return array(
				'status'  => 4,
				'message' => __( 'Unable to Update Custom Fields', 'autonami-automation-connector' ),
			);
		}

		return array(
			'status'  => 3,
			'message' => __( 'Contact fields updated ', 'autonami-automation-connector' ),
		);
	}

	public function process_v2() {
		$contact = new BWFCRM_Contact( $this->data['email'], true );
		if ( ! $contact->is_contact_exists() && ! empty( $this->data['phone'] ) ) {
			/** get contact id using the phone number */
			$contact_id = BWFAN_Pro_Common::get_contact_id_by_phone( $this->data['phone'] );
			$contact    = new BWFCRM_Contact( $contact_id, false );
			if ( ! $contact->is_contact_exists() ) {

				return $this->error_response( __( 'Unable to fetch contact', 'wp-marketing-automations-pro' ) );
			}
		}

		$message = '';
		if ( isset( $this->data['custom_fields']['email'] ) && $contact->contact->get_email() !== $this->data['custom_fields']['email'] ) {
			if ( ! is_email( $this->data['custom_fields']['email'] ) ) {
				return $this->error_response( __( 'New email is not valid.', 'wp-marketing-automations-pro' ) );
			}

			$check_contact = new BWFCRM_Contact( $this->data['custom_fields']['email'] );
			/** If email is already exists with other contacts*/
			if ( $check_contact->is_contact_exists() ) {
				$message = __( 'given email is already associated with another contact.', 'wp-marketing-automations-pro' );

				/** If Only email field to be updated then return error response */
				if ( 1 === count( $this->data['custom_fields'] ) ) {
					return $this->error_response( $message );
				}

				/** If other fields also available for update then unset the email */
				unset( $this->data['custom_fields']['email'] );
			}
		}

		/** If no data to update contact field then return with success message */
		if ( empty( $this->data['custom_fields'] ) ) {
			return $this->success_message( __( 'Contact fields updated.', 'wp-marketing-automations-pro' ) );
		}

		$custom_field_updated = $contact->update_custom_fields( $this->data['custom_fields'] );
		if ( false === $custom_field_updated ) {
			return $this->error_response( __( 'Unable to Update Custom Fields', 'wp-marketing-automations-pro' ) );
		}
		$default_msg = __( 'Contact fields updated.', 'wp-marketing-automations-pro' );
		$message     = ! empty( $message ) ? 'Contact fields updated but ' . $message : $default_msg;

		return $this->success_message( $message );
	}

	public function get_fields_schema() {
		$field_options = $this->get_view_data();

		return [
			[
				'id'       => 'custom_fields',
				'type'     => 'repeater',
				'label'    => __( 'Select Custom Fields', 'wp-marketing-automations-pro' ),
				"fields"   => [
					[
						'id'          => 'field',
						'type'        => 'wp_select',
						'options'     => $field_options,
						'placeholder' => __( 'Select field', 'wp-marketing-automations-pro' ),
						'label'       => "",
						'tip'         => "",
						"description" => "",
						"required"    => false,
					],
					[
						"id"                    => 'field_value',
						"label"                 => "",
						"type"                  => 'text_with_button',
						"text_with_symbol"      => true,
						"class"                 => 'bwfan-input-wrapper',
						"description"           => "",
						"required"              => false,
						"automation_merge_tags" => true
					]
				],
				'tip'      => __( "Select available fields to update and if unable to locate then sync the connector.", 'wp-marketing-automations-pro' ),
				"required" => false,
			],
			[
				'id'            => 'validate_fields',
				'type'          => 'checkbox',
				'label'         => __( 'Advanced', 'wp-marketing-automations-pro' ),
				'checkboxlabel' => __( 'Do not update field(s) when passed value is blank', 'wp-marketing-automations-pro' ),
				"description"   => ""
			]
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['custom_fields'] ) || empty( $data['custom_fields'] ) ) {
			return '';
		}

		$count = count( $data['custom_fields'] );

		return ( $count > 1 ) ? $count . ' fields' : $count . ' field';
	}

	/**
	 * Validate date
	 *
	 * @param $value
	 * @param $format
	 *
	 * @return bool
	 */
	public function validate_date( $value, $format = 'Y-m-d' ) {
		try {
			$date      = $value !== null ? DateTime::createFromFormat( $format, $value ) : '';
			$validated = $date && ( $date->format( $format ) === $value );
		} catch ( Error $e ) {
			return false;
		} catch ( Exception $e ) {
			return false;
		}

		return $validated;
	}
}

return 'BWFAN_CRM_Update_CustomFields';
