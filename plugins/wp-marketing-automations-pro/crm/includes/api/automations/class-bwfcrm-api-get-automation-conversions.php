<?php

class BWFCRM_API_Get_Automation_Conversions extends BWFCRM_API_Base {
	public static $ins;
	private $aid = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/automation/(?P<automation_id>[\\d]+)/conversions/';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = array(
			'automation_id' => array(
				'description' => __( 'Automation ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'offset'        => array(
				'description' => __( 'Contacts list Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'         => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);
	}

	public function process_api_call() {
		$automation_id  = $this->get_sanitized_arg( 'automation_id', 'text_field' );
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}
		$this->aid = $automation_id;

		$conversions                    = BWFCRM_Automations::get_conversions( $automation_id, $this->pagination->offset, $this->pagination->limit );
		$conversions['automation_data'] = $automation_obj->automation_data;

		if ( isset( $conversions['automation_data']['v'] ) && 1 === absint( $conversions['automation_data']['v'] ) ) {
			$meta                                    = BWFAN_Model_Automationmeta::get_automation_meta( $automation_id );
			$conversions['automation_data']['title'] = isset( $meta['title'] ) ? $meta['title'] : '';
		}

		if ( empty( $conversions['total'] ) ) {
			$this->response_code = 404;
			$response            = __( "No orders found", "autonami-automations-pro" );

			return $this->success_response( $conversions, $response );
		}
		$this->total_count = $conversions['total'];

		return $this->success_response( $conversions, __( 'Got All Orders', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}

	/**
	 * @return array
	 */
	public function get_result_count_data() {
		return [
			'failed' => BWFAN_Model_Automation_Contact::get_active_count( $this->aid, 2 )
		];
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Automation_Conversions' );
