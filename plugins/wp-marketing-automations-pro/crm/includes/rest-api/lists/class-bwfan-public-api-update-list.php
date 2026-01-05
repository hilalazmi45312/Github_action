<?php

/**
 * Class BWFAN_Rest_API_Update_List
 */

namespace BWFAN\Rest_API;
class Update_List extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/list/update/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'list' ];
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
		$list_id   = $this->get_sanitized_arg( 'id', 'key' );
		$list_name = $this->get_sanitized_arg( 'list', 'text_field' );

		$current_name = $list->get_name();
		if ( $list_name === $current_name ) {
			$updated_list = [ 'ID' => $list_id, 'name' => $list_name ];

			return $this->success_response( [ 'lists' => $updated_list ], __( 'List updated successfully', 'wp-marketing-automations-pro' ) );
		}

		/** Check if list is already exists */
		$already_exists = \BWFCRM_Lists::get_terms( \BWFCRM_Term_Type::$LIST, [], $list_name, 0, 0, ARRAY_A, 'exact' );
		if ( ! empty( $already_exists ) ) {
			$response = __( "List already exists with name: " . $list_name, 'wp-marketing-automations-pro' );

			$this->error_response( $response, 422, 'unknown_error' );
		}

		// setting and update lists
		$list->set_name( $list_name );
		$updated = $list->save();

		if ( empty( $updated ) || is_wp_error( $updated ) ) {
			$this->error_response( null, 422, 'unknown_error' );
		}

		$updated_list = [ 'ID' => $list_id, 'name' => $list->get_name() ];

		return $this->success_response( [ 'lists' => $updated_list ], __( 'List updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Update_List' );
