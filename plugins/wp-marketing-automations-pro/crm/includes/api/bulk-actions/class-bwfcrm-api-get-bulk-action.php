<?php

/**
 * Get Bulk Action API
 */
class BWFCRM_Api_Get_Bulk_Action extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = '/bulk-action/(?P<bulk_action_id>[\\d]+)';
		$this->request_args = array(
			'bulk_action_id' => array(
				'description' => __( 'Bulk Action ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);

	}

	public function process_api_call() {
		$bulk_action_id = $this->get_sanitized_arg( 'bulk_action_id' );
		if ( empty( $bulk_action_id ) ) {
			return $this->error_response( __( 'Invalid / Empty bulk action ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data = BWFAN_Model_Bulk_Action::bwfan_get_bulk_action( $bulk_action_id );

		if ( empty( $data ) ) {
			return $this->error_response( __( 'Bulk Action not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Fetch updated list */
		if ( isset( $data['actions']['add_to_lists'] ) ) {
			$data['actions']['add_to_lists'] = BWFAN_Common::get_updated_tags_and_list( $data['actions']['add_to_lists'] );
		}

		/** Fetch updated Tags */
		if ( isset( $data['actions']['add_tags'] ) ) {
			$data['actions']['add_tags'] = BWFAN_Common::get_updated_tags_and_list( $data['actions']['add_tags'] );
		}


		$response = [
			'data'          => $data,
			'actions'       => $this->format_action_array( BWFCRM_Core()->actions->get_all_action_list( 2 ) ),
			'action_schema' => BWFCRM_Core()->actions->get_all_actions_schema_data(),
			'group_data'    => BWFCRM_Core()->actions->get_group_list(),
		];

		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * Format the action data
	 *
	 * @param $actions
	 *
	 * @return array|array[]
	 */
	public function format_action_array( $actions ) {
		if ( empty( $actions ) ) {
			return [];
		}

		return array_map( function ( $slug, $nice_name ) {
			return [
				'value' => $slug,
				'label' => $nice_name,
			];
		}, array_keys( $actions ), $actions );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Bulk_Action' );
