<?php

final class BWFAN_WP_HTTP_Post extends BWFAN_Action {

	private static $ins = null;
	public $required_fields = array( 'url', 'custom_fields' );

	protected function __construct() {
		$this->action_name = __( 'Send Data To Any Source (HTTP Request)', 'wp-marketing-automations-pro' );
		$this->action_desc = __( 'This action sends a HTTP request with key value pairs data to the entered URL', 'wp-marketing-automations-pro' );

		$this->action_priority = 10;
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

		$method_data = $this->get_method_data();
		BWFAN_Core()->admin->set_actions_js_data( $this->get_class_slug(), 'method_data', $method_data );
	}

	/**
	 * @return string[]
	 */
	public function get_method_data() {
		return array(
			1 => 'GET',
			2 => 'POST',
			4 => 'PUT',
		);
	}

	/**
	 * Show the html fields for the current action.
	 */
	public function get_view() {
		$unique_slug  = $this->get_slug();
		$unique_slug2 = $unique_slug . '_2';
		?>
        <script type="text/html" id="tmpl-action-repeater-ui-<?php echo esc_html__( $unique_slug ); ?>">
            <div class="bwfan-input-form clearfix gs-repeater-fields gs-repeater-custom-fields">
                <div class="bwfan-col-sm-5 bwfan-pl-0">
                    <input required type="text" placeholder="Key" class="bwfan-input-wrapper" value="" name="bwfan[{{data.action_id}}][data][custom_fields][field][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-6 bwfan-p-0">
                    <input required type="text" placeholder="Value" class="bwfan-input-wrapper bwfan-input-merge-tags" value="" name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-1 bwfan-pr-0">
                    <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-action-repeater-ui-<?php echo esc_html__( $unique_slug2 ); ?>">
            <div class="bwfan-input-form clearfix gs-repeater-fields">
                <div class="bwfan-col-sm-5 bwfan-pl-0">
                    <input required type="text" placeholder="Key" class="bwfan-input-wrapper" value="" name="bwfan[{{data.action_id}}][data][headers][field][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-6 bwfan-p-0">
                    <input required type="text" placeholder="Value" class="bwfan-input-wrapper bwfan-input-merge-tags" value="" name="bwfan[{{data.action_id}}][data][headers][field_value][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-1 bwfan-pr-0">
                    <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            entered_url = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'url')) ? data.actionSavedData.data.url : '';
            selected_method = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'http_method')) ? data.actionSavedData.data.http_method : 2;
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php
					echo esc_html__( 'Enter URL', 'wp-marketing-automations-pro' );
					$message = __( 'Enter a URL where data will be sent.', 'wp-marketing-automations-pro' );
					echo $this->add_description( $message, '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <textarea required="" class="bwfan-input-wrapper" rows="3" placeholder="Webhook URL" name="bwfan[{{data.action_id}}][data][url]" spellcheck="false">{{entered_url}}</textarea>
            </div>

            <label for="" class="bwfan-label-title">
				<?php esc_html_e( 'Method', 'wp-marketing-automations-pro' ); ?>
				<?php
				$message = __( 'Method to pass this data on webhook', 'wp-marketing-automations-pro' );
				echo $this->add_description( esc_html__( $message ), '2xl', 'right' ); //phpcs:ignore WordPress.Security.EscapeOutput
				?>
            </label>
            <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-pr-0 bwfan-mb-15">
                <select required id="bwfan_http_method" class="bwfan-input-wrapper bwfan-field-<?php esc_html_e( $unique_slug ); ?>" name="bwfan[{{data.action_id}}][data][http_method]">
                    <option value="">Choose method</option>
                    <#
                    if(_.has(data.actionFieldsOptions, 'method_data') && _.isObject(data.actionFieldsOptions.method_data) ) {
                    _.each( data.actionFieldsOptions.method_data, function( value, key ){
                    selected = (key == selected_method) ? 'selected' : '';
                    #>
                    <option value="{{key}}" {{selected}}>{{value}}</option>
                    <# })
                    }
                    #>
                </select>
            </div>
            <div class="clearfix bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Add Contact Details', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan_field_desc bwfan-mb10"><?php esc_html_e( 'This will add the Contact ID, First Name, Last Name, Email and Phone automatically to the data', 'wp-marketing-automations-pro' ); ?></div>
                <input type="button" id="bwfan_append_contact_data" class="button" value="<?php esc_html_e( 'Add Now', 'wp-marketing-automations-pro' ); ?>">
            </div>
            <div class="clearfix bwfan-repeater-wrap bwfan-mb10">
                <label for="" class="bwfan-label-title">
					<?php echo esc_html__( 'Data', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                </label>

                <div class="clearfix bwfan-input-repeater bwfan-custom-fields bwfan-mb10">
                    <#
                    repeaterArr = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'custom_fields')) ? data.actionSavedData.data.custom_fields : {};
                    repeaterCount = _.size(repeaterArr.field);
                    if(repeaterCount == 0) {
                    repeaterArr = {field:{0:''}, field_value:{0:''}};
                    }

                    if(repeaterCount >= 0) {
                    h=0;
                    _.each( repeaterArr.field, function( value, key ){
                    #>
                    <div class="bwfan-input-form clearfix gs-repeater-fields gs-repeater-custom-fields">
                        <div class="bwfan-col-sm-5 bwfan-pl-0">
                            <input required type="text" placeholder="Key" class="bwfan-input-wrapper" value="{{repeaterArr.field[key]}}" name="bwfan[{{data.action_id}}][data][custom_fields][field][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-6 bwfan-p-0">
                            <input required type="text" placeholder="Value" class="bwfan-input-wrapper" value="{{repeaterArr.field_value[key]}}" name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-1 bwfan-pr-0">
                            <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                        </div>
                    </div>
                    <# h++;
                    });
                    }
                    #>
                </div>
                <div class="bwfan-col-sm-12 bwfan-custom-fields-repeater-data bwfan-pl-0 bwfan-mb10">
                    <a href="#" class="bwfan-add-repeater-data bwfan-repeater-ui" data-repeater-slug="<?php echo esc_html__( $unique_slug ); ?>" data-groupid="{{data.action_id}}" data-count="{{repeaterCount}}"><i class="dashicons dashicons-plus-alt"></i></a>
                </div>
            </div>
            <div class="clearfix bwfan-repeater-wrap bwfan-mb10">
                <label for="" class="bwfan-label-title">
					<?php echo esc_html__( 'Headers', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                </label>
                <div class="clearfix bwfan-input-repeater bwfan-mb10">
                    <#
                    repeaterArr2 = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'headers')) ? data.actionSavedData.data.headers : {};
                    repeaterCount2 = _.size(repeaterArr2.field);
                    if(repeaterCount2 == 0) {
                    repeaterArr2 = {field:{0:''}, field_value:{0:''}};
                    }

                    if(repeaterCount2 >= 0) {
                    h=0;
                    _.each( repeaterArr2.field, function( value, key ){
                    #>
                    <div class="bwfan-input-form clearfix gs-repeater-fields">
                        <div class="bwfan-col-sm-5 bwfan-pl-0">
                            <input type="text" placeholder="Key" class="bwfan-input-wrapper" value="{{repeaterArr2.field[key]}}" name="bwfan[{{data.action_id}}][data][headers][field][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-6 bwfan-p-0">
                            <input type="text" placeholder="Value" class="bwfan-input-wrapper" value="{{repeaterArr2.field_value[key]}}" name="bwfan[{{data.action_id}}][data][headers][field_value][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-1 bwfan-pr-0">
                            <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                        </div>
                    </div>
                    <# h++;
                    });
                    }
                    #>
                </div>
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-mb-15">
                    <a href="#" class="bwfan-add-repeater-data bwfan-repeater-ui" data-repeater-slug="<?php echo esc_html__( $unique_slug2 ); ?>" data-groupid="{{data.action_id}}" data-count="{{repeaterCount2}}"><i class="dashicons dashicons-plus-alt"></i></a>
                </div>
            </div>

            <div class="clearfix bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Send test data via HTTP Post', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan_field_desc bwfan-mb10"><?php esc_html_e( 'This will POST the key value pairs with dummy data to the specified URL', 'wp-marketing-automations-pro' ); ?></div>
                <input type="button" id="bwfan_test_http_post_btn" class="button" value="<?php esc_html_e( 'Send Now', 'wp-marketing-automations-pro' ); ?>">
            </div>
        </script>

        <script>
            jQuery(document).ready(function ($) {

                const contact_data = {
                    'contact_id': '{{contact_id}}',
                    'contact_email': '{{contact_email}}',
                    'contact_first_name': '{{contact_first_name}}',
                    'contact_last_name': '{{contact_last_name}}',
                    'contact_phone': '{{contact_phone}}'
                }
                /* Send test data to zap */
                $(document).on('click', '#bwfan_test_http_post_btn', function () {
                    var el = $(this);
                    var form_data = $('#bwfan-actions-form-container').bwfan_serializeAndEncode();
                    form_data = bwfan_deserialize_obj(form_data);
                    var group_id = $('.bwfan-selected-action').attr('data-group-id');
                    var data_to_send = form_data.bwfan[group_id];

                    data_to_send.source = BWFAN_Auto.uiDataDetail.trigger.source;
                    data_to_send.event = BWFAN_Auto.uiDataDetail.trigger.event;
                    data_to_send._wpnonce = bwfanParams.ajax_nonce;
                    data_to_send.automation_id = bwfan_automation_data.automation_id;

                    el.prop('disabled', true);

                    var ajax = new bwf_ajax();
                    ajax.ajax('send_test_http_post', data_to_send);

                    ajax.success = function (resp) {
                        el.prop('disabled', false);

                        if (resp.status == true) {
                            let $iziWrap = $("#modal_automation_success");
                            if ($iziWrap.length > 0) {
                                $iziWrap.iziModal('setTitle', resp.msg);
                                $iziWrap.iziModal('open');
                            }

                        } else {
                            swal({
                                type: 'error',
                                title: resp.msg,
                            });
                        }
                    };
                });

                $(document).on('click', '#bwfan_append_contact_data', function () {
                    const elem = $(".bwfan-custom-fields-repeater-data a");
                    let new_row_index = elem.attr('data-count');
                    const action_id = $('.bwfan-remove-repeater-field').data('groupid');

                    /** Clean empty rows */
                    $(".gs-repeater-custom-fields").each(function () {
                        let elem = $(this);
                        if ('' === elem.find("input.bwfan-input-wrapper").val()) {
                            elem.remove();
                        }
                    });

                    let html = "";
                    _.each(contact_data, function (value, key) {
                        html += '<div class="bwfan-input-form clearfix gs-repeater-fields gs-repeater-custom-fields">';
                        html += '<div class="bwfan-col-sm-5 bwfan-pl-0">';
                        html += '<input required type="text" placeholder="Key" class="bwfan-input-wrapper" value="' + key + '" name="bwfan[' + action_id + '][data][custom_fields][field][' + new_row_index + ']"/>'
                        html += '</div>';
                        html += '<div class="bwfan-col-sm-6 bwfan-p-0">';
                        html += '<input required type="text" placeholder="Value" class="bwfan-input-wrapper" value="' + value + '" name="bwfan[' + action_id + '][data][custom_fields][field_value][' + new_row_index + ']"/>'
                        html += '</div>';
                        html += '<div class="bwfan-col-sm-1 bwfan-pr-0">';
                        html += '<span class="bwfan-remove-repeater-field" data-groupid="' + action_id + '">&#10006;</span>';
                        html += '</div></div>';
                        new_row_index++;
                    });
                    elem.attr('data-count', new_row_index);
                    $(".bwfan-custom-fields").append(html);
                });
            });
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
		$data_to_set          = array();
		$data_to_set['email'] = $task_meta['global']['email'];
		$data_to_set['url']   = BWFAN_Common::decode_merge_tags( $task_meta['data']['url'] );
		$fields               = $task_meta['data']['custom_fields']['field'];
		$fields_value         = $task_meta['data']['custom_fields']['field_value'];
		$http_method          = $task_meta['data']['http_method'];
		$custom_fields        = array();

		foreach ( $fields as $key1 => $field_id ) {
			$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
		}
		$data_to_set['custom_fields'] = $custom_fields;

		$header_fields       = $task_meta['data']['headers']['field'];
		$header_fields_value = $task_meta['data']['headers']['field_value'];
		$header_fields_final = array();

		foreach ( $header_fields as $key1 => $field_id ) {
			$header_fields_final[ $field_id ] = BWFAN_Common::decode_merge_tags( $header_fields_value[ $key1 ] );
		}
		$data_to_set['headers'] = $header_fields_final;
		$data_to_set['method']  = $http_method;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set          = array();
		$data_to_set['email'] = $automation_data['global']['email'];
		$data_to_set['url']   = BWFAN_Common::decode_merge_tags( $step_data['url'] );
		$fields               = isset( $step_data['custom_fields'] ) ? $step_data['custom_fields'] : [];
		$http_method          = $step_data['http_method'];

		$custom_fields = array();
		foreach ( $fields as $field ) {
			$key                   = BWFAN_Common::decode_merge_tags( $field['field'] );
			$custom_fields[ $key ] = BWFAN_Common::decode_merge_tags( $field['field_value'] );
		}

		$data_to_set['custom_fields'] = $custom_fields;

		$header_fields       = isset( $step_data['headers'] ) ? $step_data['headers'] : [];
		$header_fields_final = array();

		foreach ( $header_fields as $field ) {
			$header_fields_final[ $field['field'] ] = BWFAN_Common::decode_merge_tags( $field['field_value'] );
		}
		$data_to_set['headers'] = $header_fields_final;
		$data_to_set['method']  = $http_method;

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

		if ( isset( $result['response'] ) && 200 === $result['response'] ) {
			return array(
				'status' => 3
			);
		}

		$error_message = 'Response Status Code: ' . $result['response'];
		if ( is_array( $result['body'] ) ) {
			$error_message .= isset( $result['body']['message'] ) ? ' Error: ' . $result['body']['message'] : ( isset( $result['body']['msg'] ) ? ' Error: ' . $result['body']['msg'] : '' );
		}

		return array(
			'status'  => 1,
			'message' => $error_message
		);
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$endpoint_url = $this->data['url'];
		$params_data  = $this->data['custom_fields'];
		$headers      = [];
		if ( isset( $this->data['headers'] ) ) {
			foreach ( $this->data['headers'] as $header_key => $header_value ) {
				if ( empty( $header_key ) || empty( $header_value ) ) {
					continue;
				}
				$headers[ $header_key ] = $header_value;
			}
		}
		/** Check if nested data */
		$params_data = $this->check_for_nested_data( $params_data );
		/** if content type is application/json then send parameters in json format */
		if ( 1 !== intval( $this->data['method'] ) && isset( $headers['Content-Type'] ) && 'application/json' === strtolower( $headers['Content-Type'] ) ) {
			$params_data = wp_json_encode( $params_data );
		}

		return $this->make_wp_requests( $endpoint_url, $params_data, $headers, $this->data['method'] );
	}

	public function process_v2() {
		$endpoint_url = $this->data['url'];
		$params_data  = $this->data['custom_fields'];
		$headers      = [];
		if ( isset( $this->data['headers'] ) ) {
			foreach ( $this->data['headers'] as $header_key => $header_value ) {
				if ( empty( $header_key ) || empty( $header_value ) ) {
					continue;
				}
				$headers[ $header_key ] = $header_value;
			}
		}

		/** Checking if nested data exists */
		$params_data = $this->check_for_nested_data( $params_data );

		/** if content type is application/json then send parameters in json format */
		if ( 1 !== intval( $this->data['method'] ) && isset( $headers['Content-Type'] ) && 'application/json' === strtolower( $headers['Content-Type'] ) ) {
			$params_data = wp_json_encode( $params_data );
		}
		$this->make_wp_requests( $endpoint_url, $params_data, $headers, $this->data['method'] );

		return $this->success_message( __( 'Data sent successfully.', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Get nested data
	 *
	 * @param $data
	 *
	 * @return array|mixed
	 */
	public function check_for_nested_data( $data ) {
		if ( empty( $data ) ) {
			return [];
		}

		$prepared_data = $data;
		foreach ( $data as $key => $value ) {
			if ( ! strpos( $key, '.' ) ) {
				continue;
			}
			unset( $prepared_data[ $key ] );
			$nested_keys   = $this->get_nested_keys( $key, $value, $prepared_data );
			$prepared_data = array_replace( $prepared_data, $nested_keys );
		}

		return $prepared_data;
	}

	/**
	 * Get nested data
	 *
	 * @param $total_keys
	 * @param $value
	 *
	 * @return array
	 */
	private function get_nested_keys( $total_keys, $value, $final_data ) {
		$total_keys = explode( '.', $total_keys );

		return $this->add_keys_dynamic( $final_data, $total_keys, $value );
	}

	public function add_keys_dynamic( $main_array, $keys, $value ) {
		$tmp_array = &$main_array;
		while ( count( $keys ) > 0 ) {
			$k = array_shift( $keys );
			if ( ! is_array( $tmp_array ) ) {
				$tmp_array = [];
			}
			$tmp_array = &$tmp_array[ $k ];
		}
		$tmp_array = $value;

		return $main_array;
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'url',
				'type'        => 'textarea',
				'label'       => __( 'Enter URL', 'wp-marketing-automations-pro' ),
				'placeholder' => __( 'Webhook URL', 'wp-marketing-automations-pro' ),
				'tip'         => __( "Enter a URL where data will be sent.", 'wp-marketing-automations-pro' ),
				"description" => "",
				"required"    => true,
			],
			[
				'id'          => 'http_method',
				'type'        => 'select',
				'label'       => __( 'Method', 'wp-marketing-automations-pro' ),
				'options'     => [
					[
						'label' => __( 'GET', 'wp-marketing-automations-pro' ),
						'value' => 1
					],
					[
						'label' => __( 'POST', 'wp-marketing-automations-pro' ),
						'value' => 2
					],
					[
						'label' => __( 'PUT', 'wp-marketing-automations-pro' ),
						'value' => 4
					],
				],
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				'tip'         => "Method to pass this data on webhook",
				"description" => "",
				"required"    => true,
			],
			[
				'id'     => 'custom_fields',
				'type'   => 'repeater',
				'label'  => __( 'Data', 'wp-marketing-automations-pro' ),
				"fields" => [
					[
						'id'          => 'field',
						'label'       => "",
						'type'        => 'text',
						'placeholder' => __( 'Key', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						'tip'         => "",
						"description" => "",
						"required"    => false,
					],
					[
						"id"          => 'field_value',
						"label"       => "",
						"type"        => 'text',
						'placeholder' => __( 'Value', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						"description" => "",
						"required"    => false,
					]
				]
			],
			[
				'id'     => 'headers',
				'type'   => 'repeater',
				'label'  => __( 'Headers', 'wp-marketing-automations-pro' ),
				"fields" => [
					[
						'id'          => 'field',
						'label'       => "",
						'type'        => 'text',
						'placeholder' => __( 'Key', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						'tip'         => "",
						"description" => "",
						"required"    => false,
					],
					[
						"id"          => 'field_value',
						"label"       => "",
						"type"        => 'text',
						'placeholder' => __( 'Value', 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						"description" => "",
						"required"    => false,
					]
				]
			],
			[
				'id'          => 'send_test_data',
				'type'        => 'send_data',
				'label'       => __( 'Send Test Data via HTTP Request', 'wp-marketing-automations-pro' ),
				'send_action' => 'bwf_send_test_http_post',
				'send_field'  => [
					'url'           => 'url',
					'http_method'   => 'http_method',
					'headers'       => 'headers',
					'custom_fields' => 'custom_fields',
				],
				"hint"        => __( "Send sample key-value pair data with headers to the specified URL using the selected method.", 'wp-marketing-automations-pro' )
			],
		];
	}

	public function get_default_values() {
		return [
			'http_method' => 2,
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );
		if ( ! isset( $data['url'] ) || empty( $data['url'] ) ) {
			return '';
		}

		return $data['url'];
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WP_HTTP_Post';
