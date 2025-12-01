<?php

namespace BWFCRM\Actions\Autonami;

use BWFAN_Model_Fields;
use BWFCRM\Actions\Base;
use BWFCRM\Calls\Autonami as Calls;

/**
 * Update Custom field action class
 */
class Update_Fields extends Base {

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->slug        = 'update_fields';
		$this->nice_name   = __( 'Update field', 'wp-marketing-automations-pro' );
		$this->group       = 'autonami';
		$this->group_label = __( 'FunnelKit Automations', 'wp-marketing-automations-pro' );
		$this->priority    = 10;
		$this->support     = [ 1, 2 ];
	}

	/**
	 * Returns action field schema
	 *
	 * @return array
	 */
	public function get_action_schema() {
		return [
			'type' => 'bwfupdatefield',
			'meta' => [
				'fields' => []
			]
		];
	}

	/**
	 * process action
	 *
	 * @param $contact
	 * @param $fields
	 *
	 * @return array
	 */
	public function handle_action( $contact, $fields ) {
		$field_data = [];
		/** Trim field keys before processing */
		$fields = $this->trim_filter_data( $fields );

		foreach ( $fields as $key => $value ) {
			/** If status is 3 then contact unsubscribe */
			if ( 'status' === trim( $key ) && 3 === absint( $value ) ) {
				$contact->unsubscribe();
				continue;
			}

			if ( isset( $value ) ) {
				$field_data[ trim( $key ) ] = $value;
			}
		}

		/**
		 * Check if call exists
		 */
		if ( ! class_exists( 'BWFCRM\Calls\Autonami\Update_Fields' ) ) {
			return array(
				'status'  => self::$RESPONSE_FAILED,
				'message' => __( 'Update Fields (Error): Update Fields call not found', 'wp-marketing-automations-pro' ),
			);
		}

		$call_obj = new Calls\Update_Fields();
		$result   = $call_obj->process_call( $contact, $field_data );
		$fields   = $this->get_affected_fields( $fields );

		if ( true === $result ) {
			return array(
				'status'  => self::$RESPONSE_SUCCESS,
				'message' => __( 'Field(s) Updated: ', 'wp-marketing-automations-pro' ) . implode( ', ', $fields ),
			);
		}

		return array(
			'status'  => self::$RESPONSE_FAILED,
			'message' => __( 'Field(s) Update Failed: ', 'wp-marketing-automations-pro' ) . implode( ', ', $fields ),
		);
	}

	/**
	 * Trim field key data
	 *
	 * @param $data
	 *
	 * @return array
	 */
	public function trim_filter_data( $data ) {
		if ( empty( $data ) ) {
			return [];
		}

		$result = [];
		foreach ( $data as $key => $value ) {
			$result[ trim( $key ) ] = $value;
		}

		return $result;
	}

	public function get_affected_fields( $fields ) {
		$custom_fields  = array();
		$default_fields = array();

		foreach ( array_keys( $fields ) as $id ) {
			$id = trim( $id );
			if ( is_numeric( $id ) ) {
				$custom_fields[] = absint( $id );
				continue;
			}

			$default_fields[] = $id;
		}

		/** If custom fields not found */
		if ( empty( $custom_fields ) ) {
			return $default_fields;
		}

		$custom_fields = BWFAN_Model_Fields::get_multiple_fields( $custom_fields );
		$custom_fields = array_map( function ( $field ) {
			return $field['name'];
		}, $custom_fields );

		return array_values( array_merge( $default_fields, $custom_fields ) );
	}
}

/**
 * Register action
 */
BWFCRM_Core()->actions->register_action( 'update_fields', 'BWFCRM\Actions\Autonami\Update_Fields', __( 'Update Fields', 'wp-marketing-automations-pro' ), 'autonami', __( 'FunnelKit Automations', 'wp-marketing-automations-pro' ) );
