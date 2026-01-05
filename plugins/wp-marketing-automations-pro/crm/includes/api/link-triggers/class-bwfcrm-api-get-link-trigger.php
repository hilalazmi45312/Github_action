<?php

class BWFCRM_Api_Get_Link_Trigger extends BWFCRM_API_Base {

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
		$this->route        = '/link-trigger/(?P<link_id>[\\d]+)';
		$this->request_args = array(
			'link_id' => array(
				'description' => __( 'Link ID to retrieve', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);

	}

	public function process_api_call() {
		$link_id = $this->get_sanitized_arg( 'link_id' );
		if ( empty( $link_id ) ) {
			return $this->error_response( __( 'Invalid / Empty link ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$linkdata = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( $link_id );

		if ( empty( $linkdata ) ) {
			return $this->error_response( __( 'Links trigger not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		} else {
			$temp = ! empty( $linkdata['data'] ) ? (array) $linkdata['data'] : [];
			unset( $linkdata['data'] );
			$linkdata = array_merge( $linkdata, $temp );
		}

		/** Fetch updated list */
		if ( isset( $linkdata['actions']['add_to_lists'] ) ) {
			$linkdata['actions']['add_to_lists'] = BWFAN_Common::get_updated_tags_and_list( $linkdata['actions']['add_to_lists'] );
		}

		/** Fetch updated Tags */
		if ( isset( $linkdata['actions']['add_tags'] ) ) {
			$linkdata['actions']['add_tags'] = BWFAN_Common::get_updated_tags_and_list( $linkdata['actions']['add_tags'] );
		}

		$response = [
			'link_data'     => $linkdata,
			'actions'       => $this->format_action_array( BWFCRM_Core()->actions->get_all_action_list( 1 ) ),
			'action_schema' => BWFCRM_Core()->actions->get_all_actions_schema_data(),
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

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Link_Trigger' );