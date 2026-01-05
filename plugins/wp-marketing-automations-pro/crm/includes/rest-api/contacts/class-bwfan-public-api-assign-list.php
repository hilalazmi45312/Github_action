<?php

/**
 * Class BWFAN_Rest_API_Assign_List
 */

namespace BWFAN\Rest_API;
class Assign_List extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/contact/list-assign/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'lists' ];
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
		$lists           = isset( $this->args['lists'] ) ? $this->args['lists'] : array();
		$stop_automation = isset( $this->args['stop_automation'] ) ? (bool) $this->args['stop_automation'] : false;

		if ( ! is_array( $lists ) ) {
			$this->error_response( null, 422, 'unprocessable_entity' );
		}

		$list_data = \BWFCRM_Lists::get_lists( $lists );
		if ( empty( $list_data ) ) {
			$this->error_response( 'List(s) not found', 422, 'unprocessable_entity' );
		}
		$old_lists    = $contact->get_lists();
		$list_ids     = array_column( $list_data, 'ID' );
		$assign_lists = $contact->set_lists_v2( $list_ids, $stop_automation );
		$contact->save();

		if ( ! is_array( $assign_lists ) ) {
			$this->error_response( 'No list(s) to update', 422, 'unprocessable_entity' );
		}

		if ( is_wp_error( $assign_lists ) ) {
			$this->error_response( $assign_lists->get_error_messages(), 422, 'unknown_error' );
		}
		$newly_assigned_lists = array_diff( $assign_lists, $old_lists );
		$list_results         = \BWFCRM_Lists::get_lists( $newly_assigned_lists );

		return $this->success_response( [ 'lists' => $list_results ], __( 'List(s) assigned successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Assign_List' );
