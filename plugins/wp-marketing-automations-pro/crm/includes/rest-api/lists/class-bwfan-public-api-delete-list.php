<?php

/**
 * Class BWFAN_Rest_API_Delete_List
 */

namespace BWFAN\Rest_API;
class Delete_List extends \BWFAN_Rest_API_Base {

	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::DELETABLE;
		$this->route    = '/list/(?P<id>[\\d]+)';
		$this->required = [ 'id' ];
		$this->validate = [ 'list_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param \BWFCRM_Lists $list
	 *
	 * @return mixed
	 */
	public function process_api_call( $list = '' ) {
		$list_id = $this->get_sanitized_arg( 'id', 'text_field' );

		// storing list details before deleting to pass to api response
		$deleted_list = [ 'ID' => $list_id, 'name' => $list->get_name() ];

		$delete_list = \BWFCRM_Lists::delete_list( absint( $list_id ) );

		if ( false === $delete_list ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		return $this->success_response( [ 'lists' => $deleted_list ], __( 'List deleted successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Delete_List' );
