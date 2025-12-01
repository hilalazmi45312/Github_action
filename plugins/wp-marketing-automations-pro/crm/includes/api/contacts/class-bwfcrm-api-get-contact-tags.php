<?php

class BWFCRM_API_Get_Contact_Tags extends BWFCRM_API_Base {
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
		$this->route              = '/contacts/(?P<contact_id>[\\d]+)/tags';
		$this->pagination->offset = 0;
		$this->pagination->limit  = 30;
		$this->request_args       = array(
			'search' => array(
				'description' => __( 'Search from tag name', 'wp-marketing-automations-pro' ),
				'type'        => 'string',
			),
			'offset' => array(
				'description' => __( 'Tags list Offset', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'limit'  => array(
				'description' => __( 'Per page limit', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function default_args_values() {
		return array(
			'contact_id' => '',
		);
	}

	public function process_api_call() {
		/** checking if search present in params **/
		$contact_id = $this->get_sanitized_arg( 'contact_id', 'text_field' );
		$search     = $this->get_sanitized_arg( 'search', 'text_field' );
		$offset     = $this->get_sanitized_arg( 'offset', 'text_field' );
		$limit      = $this->get_sanitized_arg( 'limit', 'text_field' );

		$offset = empty( $offset ) ? $this->pagination->offset : $offset;
		$limit  = empty( $limit ) ? $this->pagination->limit : $limit;

		if ( empty( $contact_id ) ) {
			return $this->error_response( __( 'Contact ID is mandatory', 'wp-marketing-automations-pro' ) );
		}

		$contact = new BWFCRM_Contact( $contact_id );
		if ( ! $contact->is_contact_exists() ) {
			$this->response_code = 404;

			return $this->error_response( __( 'No contact found with given id #' . $contact_id, 'wp-marketing-automations-pro' ) );
		}

//		$contact_terms       = BWFCRM_Model_Contact_Terms::get_contact_terms( $contact->get_id(), $offset, $limit, $search, 0 );
		$contact_terms = $contact->get_all_tags();
		$this->response_code = 200;

		return $this->success_response( $contact_terms );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Contact_Tags' );
