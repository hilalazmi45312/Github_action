<?php

class BWFCRM_API_Create_Form_Feed extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function default_args_values() {
		$args = array(
			'name' => '',
			'form' => '',
		);

		return $args;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/form-feeds';
		$this->response_code = 200;
	}

	public function process_api_call() {
		$name = $this->get_sanitized_arg( 'name', 'text_field' );
		$form = $this->get_sanitized_arg( 'form', 'text_field' );
		if ( empty( $name ) ) {
			return $this->error_response( __( 'Invalid data / Required fields missing', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$nice_names = BWFCRM_Core()->forms->get_forms_nice_names();
		if ( ! empty( $form ) && ! isset( $nice_names[ $form ] ) ) {
			return $this->error_response( __( 'Form type is invalid', 'wp-marketing-automations-pro' ), null, 500 );
		}

		$feed = new BWFCRM_Form_Feed();
		$feed->set_title( $name );
		! empty( $form ) && $feed->set_source( $form );
		$feed_id = $feed->save();
		if ( ! absint( $feed_id ) > 0 ) {
			return $this->error_response( __( 'Unable to create form', 'wp-marketing-automations-pro' ), null, 500 );
		}

		return $this->success_response( $feed->get_array(), __( 'Form created', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_API_Create_Form_Feed' );