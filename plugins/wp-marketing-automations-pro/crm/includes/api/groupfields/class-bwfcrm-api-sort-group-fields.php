<?php

class BWFCRM_Api_Sort_Group_Fields extends BWFCRM_API_Base {

	public static $ins;
	public $fields = [];

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/groupfields/sort';
		$this->response_code = 200;
		$this->fields        = array();
	}

	public function default_args_values() {
		$args = array(
			'field_sort' => []
		);

		return $args;
	}

	public function process_api_call() {

		if ( empty( $this->args['field_sort'] ) ) {
			$this->response_code = 404;
			$response            = __( "Required parameter is missing.", "autonami-automations-pro" );

			return $this->error_response( $response );
		}
		update_option( 'bwf_crm_field_sort', $this->args['field_sort'] );

		return $this->success_response( $this->args['field_sort'], __( 'Fields order updated' ) );
	}


	public function get_result_total_count() {
		return count( $this->fields );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Sort_Group_Fields' );