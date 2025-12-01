<?php

class BWFCRM_Api_Create_Fields extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function default_args_values() {
		$args = array(
			'group_id' => '',
			'field'    => [],
		);

		return $args;
	}

	public function __construct() {
		parent::__construct();
		$this->method        = WP_REST_Server::CREATABLE;
		$this->route         = '/fields';
		$this->response_code = 200;
	}

	public function process_api_call() {

		/**
		 *  getting post data
		 */
		$field    = $this->get_sanitized_arg( '', 'text_field', $this->args['field'] );
		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$group_id = ! empty( $group_id ) && is_numeric( $group_id ) ? $group_id : 0;
		if ( empty( $field['name'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field name is mandatory', 'wp-marketing-automations-pro' ) );
		}
		/** Checking field slug is reserved key or not */
		if ( in_array( sanitize_title( $field['name'] ), BWFCRM_Fields::$reserved_keys, true ) ) {
			$this->response_code = 400;

			return $this->error_response( __( sanitize_title( $field['name'] ) . ' is a reserved key', 'wp-marketing-automations-pro' ) );
		}

		/**
		 *  Check group exist.
		 */
		$group = BWFCRM_Group::get_groupby_id( $group_id );
		if ( $group_id > 0 && empty( $group ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field Group ID ' . $group_id . ' is mandatory', 'wp-marketing-automations-pro' ) );
		}

		if ( empty( $field['type'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Field type is mandatory', ' autonami-automations-pro' ) );
		}
		$field_name = $field['name'];
		$type       = $field['type'];
		$mode       = isset( $field['mode'] ) ? absint( $field['mode'] ) : 1;
		$vmode      = isset( $field['vmode'] ) ? absint( $field['vmode'] ) : 1;
		$search     = isset( $field['search'] ) ? absint( $field['search'] ) : 1;

		if ( ! empty( $this->args['field']['options'] ) ) {
			$options = $this->get_sanitized_arg( '', 'text_field', $this->args['field']['options'] );
		}
		$options = ! empty( $options ) && is_array( $options ) ? $options : [];

		$placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

		$field = BWFCRM_Fields::add_field( $field_name, $type, $options, $placeholder, $mode, $vmode, $search, $group_id );
		if ( is_wp_error( $field ) ) {
			return $this->error_response( '', $field, $field->get_error_code() );
		}

		if ( isset( $field['err_msg'] ) ) {
			$this->response_code = 400;

			return $this->error_response( __( 'Cannot create a field. Error: ' . $field['err_msg'] . '. Contact Funnelkit support.', ' autonami-automations-pro' ) );
		}

		$field['merge_tag'] = BWFCRM_Core()->merge_tags->get_field_tag( $field['slug'] );

		return $this->success_response( $field, __( 'Field created', 'wp-marketing-automations-pro' ) );
	}
}


BWFCRM_API_Loader::register( 'BWFCRM_Api_Create_Fields' );