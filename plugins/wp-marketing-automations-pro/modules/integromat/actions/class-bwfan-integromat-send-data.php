<?php

final class BWFAN_Integromat_Send_Data extends BWFAN_Action {

	private static $ins = null;
	public $required_fields = array( 'url', 'custom_fields' );

	private function __construct() {
		$this->action_name = __( 'Send Data To Make (Integromat)', 'wp-marketing-automations-pro' );
		$this->action_desc = __( 'This action sends key/ value pair data to the Integromat', 'wp-marketing-automations-pro' );
		$this->support_v2  = true;
		$this->support_v1  = false;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * Make all the data which is required by the current action.
	 * This data will be used while executing the task of this action.
	 *
	 * @param $automation_data
	 * @param $step_data
	 *
	 * @return array
	 */
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
	 * Process and do the actual processing for the current action.
	 * This function is present in every action class.
	 */
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
				'label'       => __( 'Send test data via HTTP Post', 'wp-marketing-automations-pro' ),
				'send_action' => 'bwf_send_test_http_post',
				'send_field'  => [
					'url'           => 'url',
					'http_method'   => 'http_method',
					'headers'       => 'headers',
					'custom_fields' => 'custom_fields',
				],
				"hint"        => __( "This will POST the key value pairs with dummy data to Integromat Webhook URL", 'wp-marketing-automations-pro' )
			],
		];
	}
}

return 'BWFAN_Integromat_Send_Data';
