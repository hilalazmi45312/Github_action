<?php

final class BWFAN_ZA_Send_Data extends BWFAN_Action {

	private static $ins = null;
	public $required_fields = array( 'url', 'custom_fields' );

	private function __construct() {
		$this->action_name = __( 'Send Data To Zapier', 'wp-marketing-automations-pro' );
		$this->action_desc = __( 'This action sends key/ value pair data to the Zapier', 'wp-marketing-automations-pro' );
		$this->support_v2  = true;
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
        <script type="text/html" id="tmpl-action-repeater-ui-<?php echo esc_attr__( $unique_slug ); ?>">
            <div class="bwfan-input-form clearfix gs-repeater-fields">
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

        <script type="text/html" id="tmpl-action-<?php echo esc_attr__( $unique_slug ); ?>">
            <#
            entered_url = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'url')) ? data.actionSavedData.data.url : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php
					echo esc_html__( 'Enter Webhook URL', 'wp-marketing-automations-pro' );
					$message = __( 'Zapier Webhook URL where data will be sent.', 'wp-marketing-automations-pro' );
					echo $this->add_description( $message, 'l' ); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
                </label>
                <textarea rows="2" required type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][url]">{{entered_url}}</textarea>
            </div>
            <div class="clearfix bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Add Contact Details', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan_field_desc bwfan-mb10"><?php esc_html_e( 'This will add the Contact ID, First Name, Last Name, Email and Phone automatically to the data', 'wp-marketing-automations-pro' ); ?></div>
                <input type="button" id="bwfan_za_append_contact_data" class="button" value="<?php esc_html_e( 'Add Now', 'wp-marketing-automations-pro' ); ?>">
            </div>
            <label for="" class="bwfan-label-title">
				<?php echo esc_html__( 'Data', 'wp-marketing-automations-pro' ); ?>
				<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            </label>
            <div class="clearfix bwfan-repeater-wrap bwfan-mb10 ">
                <div class="clearfix bwfan-input-repeater bwfan-mb10 bwfan-za-custom-fields">
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
                    <div class="bwfan-input-form clearfix gs-repeater-fields">
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
                <div class="clearfix">
                    <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-mb-15 bwfan-za-custom-fields-repeater-data">
                        <a href="#" class="bwfan-add-repeater-data bwfan-repeater-ui" data-repeater-slug="<?php echo esc_html__( $unique_slug ); ?>" data-groupid="{{data.action_id}}" data-count="{{repeaterCount}}"><i class="dashicons dashicons-plus-alt"></i></a>
                    </div>
                </div>
            </div>
            <div class="clearfix bwfan-mb-15">
                <label for="" class="bwfan-label-title"><?php esc_html_e( 'Send test data to Zapier Webhook', 'wp-marketing-automations-pro' ); ?></label>
                <div class="bwfan_field_desc bwfan-mb10"><?php esc_html_e( 'This will POST the key value pairs with dummy data to Zapier Webhook URL', 'wp-marketing-automations-pro' ); ?></div>
                <input type="button" id="bwfan_test_zap_btn" class="button" value="<?php esc_html_e( 'Send Now', 'wp-marketing-automations-pro' ); ?>">
            </div>

        </script>

        <script>
            jQuery(document).ready(function ($) {
                /** contact data **/
                const contact_data = {
                    'contact_id': '{{contact_id}}',
                    'contact_email': '{{contact_email}}',
                    'contact_first_name': '{{contact_first_name}}',
                    'contact_last_name': '{{contact_last_name}}',
                    'contact_phone': '{{contact_phone}}'
                }

                /* Send test data to zap */
                $(document).on('click', '#bwfan_test_zap_btn', function () {
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
                    ajax.ajax('test_zap', data_to_send);

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
                                title: window.bwfan.texts.sync_oops_title,
                                text: resp.msg,
                            });
                        }
                    };
                });
                $(document).on('click', '#bwfan_za_append_contact_data', function () {
                    const elem = $(".bwfan-za-custom-fields-repeater-data a");
                    let new_row_index = elem.attr('data-count');
                    const action_id = $('.bwfan-remove-repeater-field').data('groupid');

                    /** Clean empty rows */
                    $(".gs-repeater-fields").each(function () {
                        let elem = $(this);
                        if ('' === elem.find("input.bwfan-input-wrapper").val()) {
                            elem.remove();
                        }
                    });

                    let html = "";
                    _.each(contact_data, function (value, key) {
                        html += '<div class="bwfan-input-form clearfix gs-repeater-fields">';
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
                    $(".bwfan-za-custom-fields").append(html);
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

		$custom_fields = array();
		foreach ( $fields as $key1 => $field_id ) {
			$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
		}

		$data_to_set['custom_fields'] = $custom_fields;

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set          = array();
		$data_to_set['email'] = $automation_data['global']['email'];
		$data_to_set['url']   = BWFAN_Common::decode_merge_tags( $step_data['url'] );
		$fields               = $step_data['custom_fields'];

		$custom_fields = array();
		foreach ( $fields as $field ) {
			$custom_fields[ $field['field'] ] = BWFAN_Common::decode_merge_tags( $field['field_value'] );
		}

		$data_to_set['custom_fields'] = $custom_fields;

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
		$result  = $this->process();
		$int_obj = BWFAN_Core()->integration->get_integration( 'zapier' );
		$result  = $int_obj->handle_response( $result, $this->connector, $this->call );

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

		$endpoint_url = $this->data['url'];
		$params_data  = $this->data['custom_fields'];
		$result       = $this->make_wp_requests( $endpoint_url, $params_data, array(), BWF_CO::$POST );

		return $result;
	}

	public function process_v2() {
		$endpoint_url = $this->data['url'];
		$params_data  = $this->data['custom_fields'];
		$this->make_wp_requests( $endpoint_url, $params_data, array(), BWF_CO::$POST );

		return $this->success_message( __( 'Data sent successfully.', 'wp-marketing-automations-pro' ) );
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
				'id'          => 'send_test_data',
				'type'        => 'send_data',
				'label'       => __( 'Send test data via Zapier', 'wp-marketing-automations-pro' ),
				'send_action' => 'bwf_test_zap',
				'send_field'  => [
					'url'           => 'url',
					'http_method'   => 'http_method',
					'headers'       => 'headers',
					'custom_fields' => 'custom_fields',
				],
				"hint"        => __( "This will POST the key value pairs with dummy data to Zapier Webhook URL", 'wp-marketing-automations-pro' )
			],
		];
	}
}

return 'BWFAN_ZA_Send_Data';
