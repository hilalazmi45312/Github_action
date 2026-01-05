<?php

class BWFCRM_Api_Delete_Group extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::DELETABLE;
		$this->route         = '/groupfields/(?P<group_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'group_id' =>'',
			'move_to_group'=>''
		);

		return $args;
	}

	public function process_api_call() {

		$group_id = $this->get_sanitized_arg('group_id','text_field');
		$move_group_id = $this->get_sanitized_arg('move_to_group','text_field');
		$move_group_id = !empty( $move_group_id )? $move_group_id : 0;
		
		if (  empty($group_id) ) {
			$this->response_code = 400;
			$response            = __( "Group Id is missing", 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		BWFCRM_Fields::field_move_to_group( $group_id, $move_group_id );

		$delete_group = BWFAN_Model_Field_Groups::delete( $group_id );

		
		if ( 0 === $delete_group ) {

			$this->response_code = 400;

			return $this->error_response( __('Unable to delete group with group id ' . $group_id,'wp-marketing-automations-pro') );
		}

		return $this->success_response( __('Field group deleted','wp-marketing-automations-pro') );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Delete_Group' );