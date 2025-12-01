<?php

class BWFCRM_Api_Update_List extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::EDITABLE;
		$this->route  = '/lists/(?P<list_id>[\\d]+)';
	}

	public function default_args_values() {
		$args = array(
			'list_id'   => '',
			'list_name' => '',
		);

		return $args;
	}

	public function process_api_call() {
		$list_id = $this->get_sanitized_arg( 'list_id', 'text_field' );
		if ( empty( $list_id ) ) {
			$this->response_code = 400;
			$response            = __( 'List ID is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$list_name = $this->get_sanitized_arg( 'list_name', 'text_field' );
		if ( empty( $list_name ) ) {
			$this->response_code = 404;
			$response            = __( 'List name is mandatory', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$list = new BWFCRM_Lists( intval( $list_id ) );
		if ( ! $list->is_exists() ) {
			$this->response_code = 404;
			$response            = __( 'List doesn\'t exists', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$current_name = $list->get_name();
		if ( $list_name !== $current_name ) {
			$already_exists = BWFCRM_Lists::get_terms( BWFCRM_Term_Type::$LIST, [], $list_name, 0, 0, ARRAY_A, 'exact' );
			if ( ! empty( $already_exists ) ) {
				return $this->error_response( __( "List already exists with name: " . $list_name, 'wp-marketing-automations-pro' ), null, 404 );
			}
			$list->set_name( $list_name );
		}

		$description = $this->get_sanitized_arg( 'description', 'text_field' );
		$list->set_description( $description );

		if ( empty( $list->save() ) ) {
			return $this->error_response( __( 'Unable to update the list', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( __( 'List updated', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_List' );
