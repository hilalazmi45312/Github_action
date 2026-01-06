<?php

/**
 * Class BWFAN_Rest_API_Unassign_List
 */

namespace BWFAN\Rest_API;
class Unassign_List extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/list-unassign/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'listId' ];
		$this->validate = [ 'contact_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/**
	 * @param \BWFCRM_Contact $contact
	 *
	 * @return mixed
	 */
	public function process_api_call( $contact = '' ) {
		$lists = isset( $this->args['listId'] ) ? $this->args['listId'] : array();

		if ( ! is_array( $lists ) ) {
			$this->error_response( '', 422, 'unprocessable_entity' );
		}

		$unassign_lists = $contact->remove_lists( $lists );
		$contact->save();
		if ( empty( $unassign_lists ) ) {
			$this->error_response( 'No list to unassign', 422, 'unprocessable_entity' );
		}

		if ( is_wp_error( $unassign_lists ) ) {
			$this->error_response( $unassign_lists->get_error_messages(), 422, 'unknown_error' );
		}
		$unassign_lists = \BWFCRM_Lists::get_lists( $unassign_lists );


		return $this->success_response( [ 'lists' => $unassign_lists ], __( 'List(s) unassigned successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Unassign_List' );
