<?php

final class BWFAN_WP_Update_User_Meta extends BWFAN_Action {

	private static $ins = null;
	public $required_fields = array( 'custom_fields' );

	protected function __construct() {
		$this->action_name = __( 'Update User Meta', 'wp-marketing-automations-pro' );
		$this->action_desc = __( 'This action updates the WordPress user\'s meta field(s)', 'wp-marketing-automations-pro' );

		$this->action_priority = 10;
		$this->excluded_events = array();
		$this->support_v2      = true;
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
        <script type="text/html" id="tmpl-action-repeater-ui-<?php echo esc_html__( $unique_slug ); ?>">
            <div class="bwfan-input-form clearfix gs-repeater-fields">
                <div class="bwfan-col-sm-5 bwfan-pl-0">
                    <input required type="text" placeholder="Meta key" class="bwfan-input-wrapper" value="" name="bwfan[{{data.action_id}}][data][custom_fields][field][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-6 bwfan-p-0">
                    <input required type="text" placeholder="Meta value" class="bwfan-input-wrapper bwfan-input-merge-tags" value="" name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{data.index}}]"/>
                </div>
                <div class="bwfan-col-sm-1 bwfan-pr-0">
                    <span class="bwfan-remove-repeater-field" data-groupid="{{data.action_id}}">&#10006;</span>
                </div>
            </div>
        </script>

        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <div class="bwfan-repeater-wrap">
                <label for="" class="bwfan-label-title">
					<?php echo esc_html__( 'Data', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                </label>
                <div class="clearfix bwfan-input-repeater bwfan-mb10">
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
                            <input required type="text" placeholder="Meta key" class="bwfan-input-wrapper" value="{{repeaterArr.field[key]}}" name="bwfan[{{data.action_id}}][data][custom_fields][field][{{h}}]"/>
                        </div>
                        <div class="bwfan-col-sm-6 bwfan-p-0">
                            <input required type="text" placeholder="Meta value" class="bwfan-input-wrapper" value="{{repeaterArr.field_value[key]}}" name="bwfan[{{data.action_id}}][data][custom_fields][field_value][{{h}}]"/>
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
                <div class="bwfan-col-sm-12 bwfan-pl-0 bwfan-mb-15">
                    <a href="#" class="bwfan-add-repeater-data bwfan-repeater-ui" data-repeater-slug="<?php echo esc_html__( $unique_slug ); ?>" data-groupid="{{data.action_id}}" data-count="{{repeaterCount}}"><i class="dashicons dashicons-plus-alt"></i></a>
                </div>
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
		$data_to_set  = array();
		$fields       = $task_meta['data']['custom_fields']['field'];
		$order_id     = isset( $task_meta['global']['order_id'] ) ? $task_meta['global']['order_id'] : '';
		$email        = isset( $task_meta['global']['email'] ) ? $task_meta['global']['email'] : '';
		$affiliate_id = isset( $task_meta['global']['affiliate_id'] ) ? $task_meta['global']['affiliate_id'] : '';
		$user_id      = isset( $task_meta['global']['user_id'] ) ? $task_meta['global']['user_id'] : '';
		$user_empty   = empty( $user_id ) ? true : false;

		// get user id by order ID
		if ( true === $user_empty && bwfan_is_woocommerce_active() ) {
			$order_object = ! empty( $order_id ) ? wc_get_order( $order_id ) : '';
			if ( $order_object instanceof WC_Order ) {
				$user_id = $order_object->get_user_id();
			}
			$user_empty = ! empty( $user_id ) ? false : true;
		}

		// get user id by email if still user id is blank
		if ( true === $user_empty ) {
			$user_object = ! empty( $email ) ? get_user_by( 'email', $email ) : '';
			if ( $user_object instanceof WP_User ) {
				$user_id = $user_object->ID;
			}
			$user_empty = ! empty( $user_id ) ? false : true;

		}

		// get user id by affiliate
		if ( true === $user_empty && bwfan_is_affiliatewp_active() ) {
			$user_id = ! empty( $affiliate_id ) ? affwp_get_affiliate_user_id( $affiliate_id ) : '';
		}

		$fields_value = $task_meta['data']['custom_fields']['field_value'];

		$custom_fields = array();
		foreach ( $fields as $key1 => $field_id ) {
			$custom_fields[ $field_id ] = BWFAN_Common::decode_merge_tags( $fields_value[ $key1 ] );
		}

		$data_to_set['custom_fields'] = $custom_fields;
		$data_to_set['user_id']       = $user_id;

		return $data_to_set;
	}


	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set  = array();
		$fields       = $step_data['custom_fields'];
		$order_id     = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : '';
		$email        = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$affiliate_id = isset( $automation_data['global']['affiliate_id'] ) ? $automation_data['global']['affiliate_id'] : '';
		$user_id      = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : '';
		$user_empty   = empty( $user_id ) ? true : false;

		// get user id by order ID
		if ( true === $user_empty && bwfan_is_woocommerce_active() ) {
			$order_object = ! empty( $order_id ) ? wc_get_order( $order_id ) : '';
			if ( $order_object instanceof WC_Order ) {
				$user_id = $order_object->get_user_id();
			}
			$user_empty = ! empty( $user_id ) ? false : true;
		}

		// get user id by email if still user id is blank
		if ( true === $user_empty ) {
			$user_object = ! empty( $email ) ? get_user_by( 'email', $email ) : '';
			if ( $user_object instanceof WP_User ) {
				$user_id = $user_object->ID;
			}
			$user_empty = ! empty( $user_id ) ? false : true;

		}

		// get user id by affiliate
		if ( true === $user_empty && bwfan_is_affiliatewp_active() ) {
			$user_id = ! empty( $affiliate_id ) ? affwp_get_affiliate_user_id( $affiliate_id ) : '';
		}

		$custom_fields = [];
		foreach ( $fields as $field ) {
			$key                   = BWFAN_Common::decode_merge_tags( $field['field'] );
			$custom_fields[ $key ] = BWFAN_Common::decode_merge_tags( $field['field_value'] );
		}

		$data_to_set['custom_fields'] = $custom_fields;
		$data_to_set['user_id']       = $user_id;

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

		return $this->process();
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$params_data = $this->data['custom_fields'];
		$user_id     = $this->data['user_id'];
		$result      = array();

		if ( empty( $user_id ) ) {
			$result['status'] = 4;
			$result['body']   = 'No user found';

			return $result;
		}

		if ( empty( $params_data ) ) {
			$result['status'] = 4;
			$result['body']   = 'No data available to update';

			return $result;
		}

		foreach ( $params_data as $key => $data ) {
			if ( empty( $key ) ) {
				continue;
			}
			update_user_meta( $user_id, $key, $data );
		}

		return array(
			'status' => 3,
		);
	}

	public function process_v2() {
		$params_data = $this->data['custom_fields'];
		$user_id     = $this->data['user_id'];

		if ( empty( $user_id ) ) {
			return $this->skipped_response( __( 'No user found', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $params_data ) ) {
			return $this->skipped_response( __( 'No data available to update', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $params_data as $key => $data ) {
			if ( empty( $key ) ) {
				continue;
			}
			update_user_meta( $user_id, $key, $data );
		}

		return $this->success_message( __( 'Data updated in meta.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'     => 'custom_fields',
				'type'   => 'repeater',
				'label'  => __( 'Data', 'wp-marketing-automations-pro' ),
				"fields" => [
					[
						'id'          => 'field',
						'label'       => "",
						'type'        => 'text',
						'placeholder' => __( "Meta key", 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						'tip'         => "",
						"description" => "",
						"required"    => false,
					],
					[
						"id"          => 'field_value',
						"label"       => "",
						"type"        => 'text',
						'placeholder' => __( "Meta value", 'wp-marketing-automations-pro' ),
						"class"       => 'bwfan-input-wrapper',
						"description" => "",
						"required"    => false,
					]
				]
			],
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
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WP_Update_User_Meta';
