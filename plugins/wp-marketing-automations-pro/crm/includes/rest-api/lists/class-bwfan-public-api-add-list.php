<?php

/**
 * Class BWFAN_Rest_API_Add_List
 */

namespace BWFAN\Rest_API;
class Add_List extends \BWFAN_Rest_API_Base {

	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::CREATABLE;
		$this->route    = '/list/add';
		$this->required = [ 'lists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		// list names can be an array when creating more than one list or
		// can be single text when creating only one list
		$lists = isset( $this->args['lists'] ) ? $this->args['lists'] : array();

		// adding id to list names
		// array(array(id,value),array(id,value),...)
		$list_data = array();
		if ( is_array( $lists ) ) {
			$list_data = array_map( function ( $list ) {
				return array( 'id' => 0, 'value' => $list );
			}, $lists );
		} else {
			$list_data['id']    = 0;
			$list_data['value'] = $lists;
			$list_data          = array( $list_data );
		}

		$lists = \BWFCRM_Term::get_or_create_terms( $list_data, \BWFCRM_Term_Type::$LIST, true, true );
		if ( is_wp_error( $lists ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		// return if lists not created or not existing
		if ( ! isset( $lists['existing'] ) || ! isset( $lists['created'] ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		$created_lists = \BWFCRM_Term::get_collection_array( $lists['created'] );

		// if not created, then provided list already exists
		if ( empty( $created_lists ) ) {
			$this->error_response( null, 422, 'already_exists' );
		}

		return $this->success_response( [ 'lists' => $created_lists ], __( 'New list created successfully.', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Add_List' );
