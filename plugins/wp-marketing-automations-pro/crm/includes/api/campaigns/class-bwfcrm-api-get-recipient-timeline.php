<?php

class BWFCRM_API_Get_Recipient_Timeline extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $recipients_timeline = [];

	public function __construct() {
		parent::__construct();
		$this->method             = WP_REST_Server::READABLE;
		$this->route              = '/broadcasts/(?P<campaign_id>[\\d]+)/recipients/(?P<conversation_id>[\\d]+)/timeline';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 10;
		$this->request_args       = array(
			'campaign_id'     => array(
				'description' => __( 'Broadcast ID to retrieve campaign', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'conversation_id' => array(
				'description' => __( 'Conversation ID to retrieve recipient timeline', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			)
		);

	}

	public function process_api_call() {
		$campaign_id         = $this->get_sanitized_arg( 'campaign_id', 'text_field' );
		$conversation_id     = $this->get_sanitized_arg( 'conversation_id', 'text_field' );
		$recipients_timeline = BWFAN_Model_Engagement_Tracking::get_engagement_recipient_timeline( $conversation_id );
		if ( empty( $recipients_timeline ) ) {
			return $this->success_response( [], __( 'No engagement found', 'wp-marketing-automations-pro' ) );
		}
		$this->recipients_timeline = $recipients_timeline;

		return $this->success_response( $recipients_timeline, __( 'Got All timeline', 'wp-marketing-automations-pro' ) );
	}

	public function get_result_total_count() {
		return count( $this->recipients_timeline );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Recipient_Timeline' );
