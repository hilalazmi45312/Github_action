<?php

/**
 * Class BWFAN_Rest_API_Add_Field
 */

namespace BWFAN\Rest_API;
class Add_Field extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::CREATABLE;
		$this->route    = '/field/add';
		$this->required = [ 'field_name', 'type' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		// collecting data
		$field_name = $this->get_sanitized_arg( 'field_name', 'text_field' );

		// types : 1 - text | 2 - number | 3 - textarea | 4 - select | 5 - radio | 6 - checkbox | 7 - date
		$type     = $this->get_sanitized_arg( 'type', 'text_field' );
		$group_id = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$mode     = isset( $this->args['mode'] ) ? absint( $this->args['mode'] ) : 1;
		$vmode    = isset( $this->args['vmode'] ) ? absint( $this->args['vmode'] ) : 1;
		$search   = isset( $this->args['search'] ) ? absint( $this->args['search'] ) : 1;

		// checking for group existence
		$group_id = ! empty( $group_id ) && is_numeric( $group_id ) ? $group_id : 0;
		$group    = \BWFCRM_Group::get_groupby_id( $group_id );
		if ( $group_id > 0 && empty( $group ) ) {
			$this->error_response( null, 422, 'unprocessable_entity' );
		}

		$options     = isset( $this->args['options'] ) && ! empty( $options ) && is_array( $options ) ? $options : [];
		$placeholder = isset( $this->args['placeholder'] ) ? $this->args['placeholder'] : '';

		$field = \BWFCRM_Fields::add_field( $field_name, $type, $options, $placeholder, $mode, $vmode, $search, $group_id );

		if ( is_wp_error( $field ) ) {
			$this->error_response( '', 422, 'already_exists' );
		}

		if ( empty( $field ) ) {
			$this->error_response( '', 422, 'unknown_error' );
		}

		return $this->success_response( array( 'fields' => $field ), __( 'Field created successfully', 'wp-marketing-automations-pro' ) );
	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Add_Field' );
