<?php

class BWFCRM_API_Get_Automation_Recipients extends BWFCRM_API_Base {
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
		$this->route              = '/automation/(?P<automation_id>[\\d]+)/recipients/';
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

		$recipients               = BWFAN_Model_Engagement_Tracking::get_automation_recipents( $automation_id, $this->pagination->offset, $this->pagination->limit );
		$recipients['recipients'] = $recipients['conversations'];
		unset( $recipients['conversations'] );
		$recipients['automation_data'] = $automation_obj->automation_data;

		if ( isset( $recipients['automation_data']['v'] ) && 1 === absint( $recipients['automation_data']['v'] ) ) {
			$meta                                   = BWFAN_Model_Automationmeta::get_automation_meta( $automation_id );
			$recipients['automation_data']['title'] = isset( $meta['title'] ) ? $meta['title'] : '';
		}

		if ( empty( $recipients['total'] ) ) {
			$this->response_code = 404;
			$response            = __( "No recipients found", "autonami-automations-pro" );

			return $this->success_response( $recipients, $response );
		}

		$this->total_count = $recipients['total'];

		return $this->success_response( $recipients, __( 'Got All Recipients', 'wp-marketing-automations-pro' ) );
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

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Automation_Recipients' );
