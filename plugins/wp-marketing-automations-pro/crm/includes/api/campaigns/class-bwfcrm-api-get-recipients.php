<?php

class BWFCRM_API_Get_Recipients extends BWFCRM_API_Base {
	public static $ins;

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
		$this->route              = '/broadcasts/(?P<campaign_id>[\\d]+)/recipients/';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = array(
			'campaign_id' => array(
				'description' => __( 'Campaign ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'offset'      => array(
				'description' => __( 'Contacts list Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'       => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);
	}

	public function process_api_call() {
		$campaign_id = $this->get_sanitized_arg( 'campaign_id', 'text_field' );
		$recipients  = BWFAN_Model_Engagement_Tracking::get_recipents_by_type( $campaign_id, BWFAN_Email_Conversations::$TYPE_CAMPAIGN, $this->pagination->offset, $this->pagination->limit );
		if ( empty( $recipients['conversations'] ) ) {
			$this->response_code = 404;
			$response            = __( "No recipients found", "autonami-automations-pro" );

			return $this->success_response( [], $response );
		}
		$this->total_count = $recipients['total'];

		return $this->success_response( $recipients['conversations'], __( 'Got All Recipients', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Recipients' );
