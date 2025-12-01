<?php

class BWFCRM_Api_Create_Layout extends BWFCRM_API_Base {

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
		$this->route         = '/layout';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'title'    => '',
			'content' => '',
		);

		return $args;
	}

	public function process_api_call() {
		$title      = isset( $this->args['title'] ) ? $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] ) : '';
		$desc       = isset( $this->args['desc'] ) ? $this->get_sanitized_arg( 'desc', 'text_field', $this->args['desc'] ) : '';
		$content    = isset( $this->args['content'] ) ? $this->args['content'] : '';
		$categories = isset( $this->args['categories'] ) ? $this->args['categories'] : [];

		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Name is required', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $content ) ) {
			$this->response_code = 400;
			$response            = __( 'Layout body is required', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$create_time = current_time( 'mysql', 1 );

		$data = [
			'desc' => $desc,
			'categories' => $categories
		];

		$template_data = [
			'title'      => $title,
			'template'   => $content,
			'type'       => 1,
			'mode'       => 6,
			'canned'     => 0,
			'created_at' => $create_time,
			'updated_at' => $create_time,
			'data'       => json_encode( $data ),
		];

		$result = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );
		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to create layout', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [ 'id' => $result ], __( 'Layout Created Successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Layout' );