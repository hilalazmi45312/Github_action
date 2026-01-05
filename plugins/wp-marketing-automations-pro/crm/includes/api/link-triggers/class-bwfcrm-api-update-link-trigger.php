<?php

class BWFCRM_Api_Update_Link_Trigger extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/link-trigger/(?P<link_id>[\\d]+)';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'link_id'            => '',
			'title'              => '',
			'desc'               => '',
			'redirect_url'       => '',
			'action_run'         => '',
			'redirect_url_title' => '',
			'actions'            => array(),
			'status'             => 1,
			'add_contact_note'   => false,
			'enable_auto_login'  => false,
			'auto_login'         => array(
				'unit' => 'days',
				'text' => '7',
			),
		);

		return $args;
	}

	public function process_api_call() {
		$title              = $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] );
		$desc               = $this->get_sanitized_arg( 'desc', 'text_field', $this->args['desc'] );
		$redirect_url       = $this->get_sanitized_url( 'redirect_url' );
		$redirect_url_title = $this->get_sanitized_arg( 'redirect_url_title', 'text_field', $this->args['redirect_url_title'] );
		$action_run         = isset( $this->args['action_run'] ) ? $this->get_sanitized_arg( 'action_run', 'text_field', $this->args['action_run'] ) : 'once';
		$status             = isset( $this->args['status'] ) ? $this->get_sanitized_arg( 'status', 'text_field', $this->args['status'] ) : false;
		$add_contact_note   = isset( $this->args['add_contact_note'] ) ? $this->get_sanitized_arg( 'add_contact_note', 'bool', $this->args['add_contact_note'] ) : false;
		$enable_auto_login  = isset( $this->args['enable_auto_login'] ) ? $this->get_sanitized_arg( 'enable_auto_login', 'bool' ) : false;
		$auto_login         = isset( $this->args['auto_login'] ) ? $this->args['auto_login'] : array();
		$actions            = $this->args['actions'];
		$link_id            = $this->get_sanitized_arg( 'link_id', 'key' );
		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Oops Title not entered, enter title to save template', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$already_exists = BWFAN_Model_Link_Triggers::check_link_exists_with( 'ID', $link_id );

		if ( ! $already_exists ) {
			$this->response_code = 400;
			$response            = __( "Links trigger doesn't exists with id : ", 'wp-marketing-automations-pro' ) . $link_id;

			return $this->error_response( $response );
		}

		$create_time = current_time( 'mysql', 1 );
		/** Create Tags if not exists */
		$add_tags = isset( $actions['add_tags'] ) ? $actions['add_tags'] : [];
		if ( is_array( $add_tags ) && ! empty( $add_tags ) && method_exists( 'BWFAN_PRO_Common', 'get_or_create_terms' ) ) {
			$tags                = BWFAN_PRO_Common::get_or_create_terms( $add_tags, BWFCRM_Term_Type::$TAG );
			$actions['add_tags'] = $tags;
		}

		/** Create Lists if not exists */
		$add_to_lists = isset( $actions['add_to_lists'] ) ? $actions['add_to_lists'] : [];
		if ( is_array( $add_to_lists ) && ! empty( $add_to_lists ) && method_exists( 'BWFAN_PRO_Common', 'get_or_create_terms' ) ) {
			$lists                   = BWFAN_PRO_Common::get_or_create_terms( $add_to_lists, BWFCRM_Term_Type::$LIST );
			$actions['add_to_lists'] = $lists;
		}

		$data = array(
			'redirect_url'       => ! empty( $redirect_url ) ? $redirect_url : '',
			'actions'            => $actions,
			'redirect_url_title' => $redirect_url_title,
			'action_run'         => $action_run === 'once' ? $action_run : 'multiple',
			'desc'               => ! empty( $desc ) ? $desc : '',
			'add_contact_note'   => $add_contact_note,
			'enable_auto_login'  => $enable_auto_login,
			'auto_login'         => $auto_login,
		);

		$link_data = array(
			'title'      => $title,
			'status'     => $status,
			'updated_at' => $create_time,
			'data'       => wp_json_encode( $data ),
		);

		$result = BWFAN_Model_Link_Triggers::update_link_trigger_data( $link_id, $link_data );

		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to create link trigger', 'wp-marketing-automations-pro' ) );
		}

		$link_data = BWFAN_Model_Link_Triggers::bwfan_get_link_trigger( $link_id );

		if ( empty( $link_data ) ) {
			return $this->error_response( __( 'Link trigger not found with provided ID', 'wp-marketing-automations-pro' ), null, 400 );
		} else {
			$temp = ! empty( $link_data['data'] ) ? (array) $link_data['data'] : array();
			unset( $link_data['data'] );
			$link_data = array_merge( $link_data, $temp );
		}

		return $this->success_response( $link_data, __( 'Link trigger saved', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Update_Link_Trigger' );
