<?php

/**
 * Create Link Trigger File
 */
class BWFCRM_Api_Create_Link_Trigger extends BWFCRM_API_Base {

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
		$this->route         = '/link-trigger';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'title'              => '',
			'desc'               => '',
			'redirect_url'       => '',
			'redirect_url_title' => '',
			'add_contact_note'   => false,
			'actions'            => [],
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
		$redirect_url       = $this->get_sanitized_arg( 'redirect_url', 'text_field', $this->args['redirect_url'] );
		$redirect_url_title = $this->get_sanitized_arg( 'redirect_url_title', 'text_field', $this->args['redirect_url_title'] );
		$add_contact_note   = isset( $this->args['add_contact_note'] ) ? $this->get_sanitized_arg( 'add_contact_note', 'text_field', $this->args['add_contact_note'] ) : false;
		$actions            = $this->args['actions'];
		$only_title         = isset( $this->args['only_title'] ) ? $this->get_sanitized_arg( 'only_title', 'text_field', $this->args['only_title'] ) : false;
		$status             = 2;
		$enable_auto_login  = isset( $this->args['enable_auto_login'] ) ? $this->get_sanitized_arg( 'enable_auto_login', 'bool' ) : false;
		$auto_login         = isset( $this->args['auto_login'] ) ? $this->args['auto_login'] : array();
		if ( $only_title ) {
			$status = 0;
		}

		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Oops Title not entered, enter title to create smart link', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$already_exists = BWFAN_Model_Link_Triggers::check_link_exists_with( 'title', $title );

		if ( $already_exists ) {
			$this->response_code = 400;
			$response            = __( 'Links trigger already exists with title : ', 'wp-marketing-automations-pro' ) . $title;

			return $this->error_response( $response );
		}
		$create_time = current_time( 'mysql', 1 );

		if ( isset( $actions['add_tags'] ) ) {
			/** Create Tags if not exists */
			$actions['add_tags'] = $this->process_terms( $actions['add_tags'] ?? [], BWFCRM_Term_Type::$TAG );
			if ( empty( $actions['add_tags'] ) ) {
				unset( $actions['add_tags'] );
			}
		}

		if ( isset( $actions['add_to_lists'] ) ) {
			/** Create Lists if not exists */
			$actions['add_to_lists'] = $this->process_terms( $actions['add_to_lists'] ?? [], BWFCRM_Term_Type::$LIST );
			if ( empty( $actions['add_to_lists'] ) ) {
				unset( $actions['add_to_lists'] );
			}
		}

		$data      = [
			'redirect_url'       => ! empty( $redirect_url ) ? $redirect_url : '',
			'actions'            => ! empty( $actions ) ? $actions : [],
			'desc'               => ! empty( $desc ) ? $desc : '',
			'redirect_url_title' => $redirect_url_title,
			'add_contact_note'   => $add_contact_note,
			'enable_auto_login'  => $enable_auto_login,
			'auto_login'         => $auto_login,
		];
		$link_data = [
			'title'      => $title,
			'status'     => $status,
			'created_at' => $create_time,
			'updated_at' => $create_time,
			'data'       => wp_json_encode( $data ),
			'created_by' => get_current_user_id()
		];

		$result = BWFAN_Model_Link_Triggers::bwfan_create_new_link_trigger( $link_data );
		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to create link trigger', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [ 'id' => $result ], __( 'Link trigger Created', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Process term and return term ids
	 *
	 * @param array $item Term data.
	 * @param string $type Term type.
	 *
	 * @return array
	 */
	public function process_terms( $item = [], $type = '' ) {
		if ( is_array( $item ) && ! empty( $item ) ) {
			return BWFAN_PRO_Common::get_or_create_terms( $item, $type );
		}

		return [];
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Link_Trigger' );