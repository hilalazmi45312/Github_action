<?php

class BWFCRM_Api_Create_Template extends BWFCRM_API_Base {

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
		$this->route         = '/template';
		$this->response_code = 200;
	}

	public function default_args_values() {
		$args = array(
			'title'    => '',
			'subject'  => '',
			'template' => '',
			'type'     => '',
			'mode'     => '',
			'data'     => [],
		);

		return $args;
	}

	public function process_api_call() {
		if ( isset( $this->args['content'] ) ) {
			$content    = BWFAN_Common::is_json( $this->args['content'] ) ? json_decode( $this->args['content'], true ) : [];
			$this->args = wp_parse_args( $content, $this->args );
		}
		$title       = $this->get_sanitized_arg( 'title', 'text_field', $this->args['title'] );
		$subject     = $this->get_sanitized_arg( 'subject', 'text_field', $this->args['subject'] );
		$template    = $this->args['template'];
		$type        = $this->get_sanitized_arg( 'type', 'text_field', $this->args['type'] );
		$mode        = $this->get_sanitized_arg( 'mode', 'text_field', $this->args['mode'] );
		$escape_body = isset( $this->args['escape_body'] ) ? $this->get_sanitized_arg( 'escape_body', 'text_field', $this->args['escape_body'] ) : false;
		$data        = $this->args['data'];

		if ( empty( $title ) ) {
			$this->response_code = 400;
			$response            = __( 'Name is required', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $subject ) && ! $escape_body ) {
			$this->response_code = 400;
			$response            = __( 'Subject is required', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		if ( empty( $template ) && ! $escape_body ) {
			$this->response_code = 400;
			$response            = __( 'Email body is required', 'wp-marketing-automations-pro' );

			return $this->error_response( $response );
		}

		$already_exists = BWFAN_Model_Templates::bwfan_check_template_exists( 'title', $title );
		if ( absint( $already_exists ) > 0 ) {
			$title = "$title - $already_exists";
		}

		$create_time = current_time( 'mysql', 1 );

		$template_data = [
			'title'      => $title,
			'template'   => $template,
			'subject'    => $subject,
			'type'       => intval( $type ) > 0 ? intval( $type ) : 1,
			'mode'       => intval( $mode ) > 0 ? intval( $mode ) : 5,
			'canned'     => 1,
			'created_at' => $create_time,
			'updated_at' => $create_time,
			'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
		];

		$result = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );
		if ( empty( $result ) ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to create template', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [ 'id' => $result ], __( 'Template Saved Successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Template' );