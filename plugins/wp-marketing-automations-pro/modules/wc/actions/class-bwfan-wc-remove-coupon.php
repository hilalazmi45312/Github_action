<?php

final class BWFAN_WC_Remove_Coupon extends BWFAN_Action {

	private static $ins = null;

	protected function __construct() {
		$this->action_name     = __( 'Delete Coupon', 'wp-marketing-automations-pro' );
		$this->action_desc     = __( 'This action deletes a WooCommerce coupon', 'wp-marketing-automations-pro' );
		$this->required_fields = array( 'coupon_name' );
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
		?>
        <script type="text/html" id="tmpl-action-<?php echo esc_html__( $this->get_slug() ); ?>">
            <#
            entered_coupon_name = (_.has(data.actionSavedData, 'data') && _.has(data.actionSavedData.data, 'coupon_name')) ? data.actionSavedData.data.coupon_name : '';
            #>
            <div class="bwfan-input-form clearfix">
                <label for="" class="bwfan-label-title">
					<?php echo esc_html__( 'Coupon Name', 'wp-marketing-automations-pro' ); ?>
					<?php echo $this->inline_merge_tag_invoke(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                </label>
                <input required type="text" class="bwfan-input-wrapper" name="bwfan[{{data.action_id}}][data][coupon_name]" value="{{entered_coupon_name}}"/>
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
		$data_to_set                = array();
		$data_to_set['email']       = $task_meta['global']['email'];
		$data_to_set['coupon_name'] = BWFAN_Common::decode_merge_tags( $task_meta['data']['coupon_name'] );

		return $data_to_set;
	}

	public function make_v2_data( $automation_data, $step_data ) {
		$data_to_set = array();

		$type        = isset( $step_data['coupon_type'] ) ? $step_data['coupon_type'] : 'static';
		$coupon_name = '';
		switch ( $type ) {
			case 'static':
				if ( isset( $step_data['coupon_name'] ) && ! empty( $step_data['coupon_name'] ) ) {
					$coupon_name = $step_data['coupon_name'];
				}
				break;
			case 'dynamic':
				if ( isset( $step_data['dynamic_coupon_id'] ) && ! empty( $step_data['dynamic_coupon_id'] ) ) {
					$coupon_name = '{{wc_dynamic_coupon id="' . $step_data['dynamic_coupon_id'] . '"}}';
				}
		}

		$data_to_set['coupon_name'] = BWFAN_Common::decode_merge_tags( $coupon_name );

		$data_to_set['order_id'] = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
		$data_to_set['email']    = ( isset( $automation_data['global']['email'] ) && is_email( $automation_data['global']['email'] ) ) ? $automation_data['global']['email'] : '';

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

		if ( $this->fields_missing ) {
			return array(
				'status'  => 4,
				'message' => $result['body'][0],
			);
		}

		if ( is_array( $result ) && count( $result ) > 0 ) { // Error in coupon deletion, coupon does not exists.
			$status = array(
				'status'  => 3,
				'message' => $result['err_msg'],
			);

			return $status;
		}

		return array(
			'status'  => 3,
			'message' => "Coupon {$this->data['coupon_name']} deleted.",
		);
	}

	/**
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
	public function process() {
		$is_required_fields_present = $this->check_fields( $this->data, $this->required_fields );
		if ( false === $is_required_fields_present ) {
			$this->fields_missing = true;

			return $this->show_fields_error();
		}

		$data        = $this->data;
		$coupon_name = $data['coupon_name'];
		$coupon      = new WC_Coupon( $coupon_name );
		$coupon_id   = $coupon->get_id();

		if ( 0 === $coupon_id ) { // Coupon does not exists
			return [
				'err_msg' => __( 'Coupon does not exists', 'wp-marketing-automations-pro' )
			];
		}

		$coupons_email = $coupon->get_email_restrictions();

		if ( is_array( $coupons_email ) && count( $coupons_email ) > 0 ) {
			$index = array_search( $data['email'], $coupons_email, true );
			if ( false !== $index ) {
				unset( $coupons_email[ $index ] );
			}
		}

		if ( is_array( $coupons_email ) && count( $coupons_email ) > 0 ) {
			$coupon->set_email_restrictions( $coupons_email );
			$coupon->save();
		} else {
			wp_delete_post( $coupon_id, true );
		}

		return $coupon_id;
	}

	public function process_v2() {
		$data        = $this->data;
		$coupon_name = $data['coupon_name'];
		$coupon      = new WC_Coupon( $coupon_name );
		$coupon_id   = $coupon->get_id();

		if ( 0 === $coupon_id ) { // Coupon does not exists
			return $this->skipped_response( __( 'Coupon does not exist.', 'wp-marketing-automations-pro' ) );
		}

		$coupons_email = $coupon->get_email_restrictions();

		if ( is_array( $coupons_email ) && count( $coupons_email ) > 0 ) {
			$index = array_search( $data['email'], $coupons_email, true );
			if ( false !== $index ) {
				unset( $coupons_email[ $index ] );
			}
		}

		if ( is_array( $coupons_email ) && count( $coupons_email ) > 0 ) {
			$coupon->set_email_restrictions( $coupons_email );
			$coupon->save();
		} else {
			wp_delete_post( $coupon_id, true );
		}

		return $this->success_message( __( 'Coupon deleted.', 'wp-marketing-automations-pro' ) );
	}

	public function get_fields_schema() {
		return [
			[
				'id'          => 'coupon_type',
				'label'       => __( 'Coupon Type', 'wp-marketing-automations-pro' ),
				'type'        => 'radio',
				'options'     => [
					[
						'label' => __( "Static Coupon", 'wp-marketing-automations-pro' ),
						'value' => 'static',
					],
					[
						'label' => __( "Dynamic Coupon", 'wp-marketing-automations-pro' ),
						'value' => 'dynamic',
					]
				],
				'class'       => 'inline-radio-field',
				'required'    => false,
				'wrap_before' => '',
			],
			[
				"id"       => 'coupon_name',
				"label"    => __( 'Coupon Name', 'wp-marketing-automations-pro' ),
				"type"     => 'text',
				"class"    => 'bwfan-input-wrapper',
				"required" => true,
				"hint"     => __( 'Copy the value of coupon code. You can add dynamic coupon code merge tag or static coupon code. ( Only 1 allowed )', 'wp-marketing-automations-pro' ),
				"toggler"  => [
					'fields'   => [
						[
							'id'    => 'coupon_type',
							'value' => 'static'
						]
					],
					'relation' => 'AND',
				]
			],
			[
				'id'          => 'dynamic_coupon_id',
				'type'        => 'ajax',
				'label'       => __( 'Step ID', 'wp-marketing-automations-pro' ),
				"class"       => 'bwfan-input-wrapper',
				"required"    => true,
				'placeholder' => __( 'Select', 'wp-marketing-automations-pro' ),
				"description" => "",
				"ajax_cb"     => 'bwfan_get_automation_wc_dynamic_coupon',
				"toggler"     => [
					'fields'   => [
						[
							'id'    => 'coupon_type',
							'value' => 'dynamic'
						]
					],
					'relation' => 'AND',
				],
			],
		];
	}

	public function get_default_values() {
		return [
			'coupon_type' => 'static',
		];
	}

	public function get_desc_text( $data ) {
		$data = json_decode( wp_json_encode( $data ), true );

		$type = isset( $data['coupon_type'] ) ? $data['coupon_type'] : 'static';

		switch ( $type ) {
			case 'static':
				if ( ! isset( $data['coupon_name'] ) || empty( $data['coupon_name'] ) ) {
					return '';
				}

				return $data['coupon_name'];
			case 'dynamic':
				if ( ! isset( $data['dynamic_coupon_id'] ) || empty( $data['dynamic_coupon_id'] ) ) {
					return '';
				}

				return '{{wc_dynamic_coupon id="' . $data['dynamic_coupon_id'] . '"}}';
			default:
				return '';
		}
	}
}

/**
 * Register this action. Registering the action will make it eligible to see it on single automation screen in select actions dropdown.
 */
return 'BWFAN_WC_Remove_Coupon';
