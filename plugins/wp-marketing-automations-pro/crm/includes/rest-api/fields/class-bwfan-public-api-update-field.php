<?php

/**
 * Class BWFAN_Rest_API_Update_Field
 */

namespace BWFAN\Rest_API;
class Update_Field extends \BWFAN_Rest_API_Base {
	public static $ins;

	public function __construct() {
		parent::__construct();
		$this->method   = \WP_REST_Server::EDITABLE;
		$this->route    = '/field/update/(?P<id>[\\d]+)';
		$this->required = [ 'id', 'slug' ];
		$this->validate = [ 'field_not_exists' ];
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function process_api_call() {
		// collecting the data
		$field_id    = $this->get_sanitized_arg( 'id', 'text_field' );
		$field_name  = $this->get_sanitized_arg( 'field_name', 'text_field' );
		$type        = $this->get_sanitized_arg( 'type', 'text_field' );
		$slug        = $this->get_sanitized_arg( 'slug', 'text_field' );
		$options     = isset( $this->args['options'] ) ? $this->args['options'] : array();
		$placeholder = $this->get_sanitized_arg( 'placeholder', 'text_field' );
		$mode        = $this->get_sanitized_arg( 'mode', 'text_field' );
		$vmode       = $this->get_sanitized_arg( 'vmode', 'text_field' );
		$search      = $this->get_sanitized_arg( 'search', 'text_field' );
		$group_id    = $this->get_sanitized_arg( 'group_id', 'text_field' );
		$group_id    = ! empty( $group_id ) && is_numeric( $group_id ) ? $group_id : false;

		// checking for group existence if group id provided
		$group = \BWFCRM_Group::get_groupby_id( $group_id );
		if ( false !== $group_id && $group_id > 0 && empty( $group ) ) {
			$this->error_response( null, 422, 'unprocessable_entity' );
		}

		$update_field = \BWFCRM_Fields::update_field( $field_id, $group_id, $field_name, $type, $options, $placeholder, $slug, $mode, $vmode, $search );

		if ( $update_field['status'] === 404 ) {
			$this->error_response( null, 422, 'already_exists' );
		}

		// fetching field detail before deleting to send it in api response
		$updated_field = \BWFAN_Model_Fields::get_field_by_id( $field_id );
		$field         = [
			"ID"   => $field_id,
			"name" => $updated_field['name']
		];

		return $this->success_response( [ 'fields' => $field ], __( 'Field updated successfully', 'wp-marketing-automations-pro' ) );

	}
}

\BWFAN_Rest_API_Loader::register( 'BWFAN\Rest_API\Update_Field' );
