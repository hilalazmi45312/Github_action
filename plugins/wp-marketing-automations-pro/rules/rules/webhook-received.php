<?php

class BWFAN_Rule_Webhook_Received extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'webhook_received' );
	}

	public function get_possible_rule_operators() {
		$operators                 = array_merge( $this->operator_matches(), $this->get_number_operators() );
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		$meta = $this->event_automation_meta;
		if ( ! isset( $meta['webhook_data'] ) ) {
			return array();
		}

		$webhook_fields = is_array( $meta['webhook_data'] ) ? $meta['webhook_data'] : [];

		return $this->get_webhook_fields( $webhook_fields );
	}

	public function get_rule_type() {
		return 'key-value';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$type        = $rule_data['rule'];
		$data        = $rule_data['data'];
		$key         = isset( $data[0] ) ? $data[0] : '';
		$saved_value = isset( $data[1] ) ? $data[1] : '';
		$value       = $this->get_field_value( $key, $automation_data );
		if ( in_array( $type, array_keys( $this->get_number_operators() ), true ) ) {
			if ( ! is_numeric( $value ) || ! is_numeric( $saved_value ) ) {
				return $this->return_is_match( false, $rule_data );
			}
			$result = $this->is_match_number( $value, $type, $saved_value );

			return $this->return_is_match( $result, $rule_data );
		}

		/** If type is is_blank or is_not_blank */
		if ( 'is_blank' === $type || 'is_not_blank' === $type ) {
			return ( 'is_blank' === $type ) ? empty( $value ) : ! empty( $value );
		}

		$value = BWFAN_Pro_Rules::make_value_as_array( $value );

		$value           = array_map( 'strtolower', $value );
		$condition_value = strtolower( trim( $saved_value ) );

		/** checking if condition value contains comma */
		if ( strpos( $condition_value, ',' ) !== false ) {
			$condition_value = explode( ',', $condition_value );
			$condition_value = array_map( 'trim', $condition_value );
		}
		$result = false;
		switch ( $type ) {
			case 'is':
				if ( is_array( $condition_value ) && is_array( $value ) ) {
					$result = count( array_intersect( $condition_value, $value ) ) > 0;
				} else {
					$result = in_array( $condition_value, $value );
				}
				break;
			case 'is_not':
				if ( is_array( $condition_value ) && is_array( $value ) ) {
					$result = count( array_intersect( $condition_value, $value ) ) === 0;
				} else {
					$result = ! in_array( $condition_value, $value );
				}
				break;
			case 'contains':
				$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
				$result = strpos( $value, $condition_value ) !== false;
				break;
			case 'not_contains':
				$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
				$result = strpos( $value, $condition_value ) === false;
				break;
			case 'starts_with':
				$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
				$length = strlen( $condition_value );
				$result = substr( $value, 0, $length ) === $condition_value;
				break;
			case 'ends_with':
				$value  = isset( $value[0] ) && ! empty( $value[0] ) ? $value[0] : '';
				$length = strlen( $condition_value );

				if ( 0 === $length ) {
					$result = true;
				} else {
					$result = substr( $value, - $length ) === $condition_value;
				}
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function is_match_number( $value, $operator, $saved_value ) {
		$value       = (float) $value;
		$saved_value = (float) $saved_value;
		$result      = false;
		switch ( $operator ) {
			case '==':
				$result = $value === $saved_value;
				break;
			case '!=':
				$result = $value !== $saved_value;
				break;
			case '>':
				$result = $value > $saved_value;
				break;
			case '<':
				$result = $value < $saved_value;
				break;
			case '>=':
				$result = $value >= $saved_value;
				break;
			case '<=':
				$result = $value <= $saved_value;
				break;
		}

		return $result;
	}

	/**
	 * @param $field_key
	 * @param $automation_data
	 *
	 * @return mixed|string
	 */
	public function get_field_value( $field_key, $automation_data ) {
		$keys = explode( '.', $field_key );
		$data = isset( $automation_data['global']['webhook_data'] ) ? $automation_data['global']['webhook_data'] : [];
		foreach ( $keys as $val ) {
			if ( isset( $data[ $val ] ) ) {
				$data = $data[ $val ];
			}
		}

		return ! is_array( $data ) ? $data : '';
	}

	/**
	 * Get webhook fields
	 *
	 * @param $webhook_fields
	 *
	 * @return array
	 */
	public function get_webhook_fields( $webhook_fields ) {
		$field_keys = [];
		foreach ( $webhook_fields as $key => $value ) {
			if ( is_array( $value ) ) {
				$keys = $this->get_webhook_fields( $value );
				foreach ( $keys as $k ) {
					$field_keys[ $key . '.' . $k ] = $key . '.' . $k;
				}
				continue;
			}
			$field_keys[ $key ] = $key;
		}

		return ! empty( $field_keys ) ? $field_keys : [];
	}

	public function get_number_operators() {
		return array(
			'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
			'>=' => __( 'at least', 'wp-marketing-automations-pro' ),
			'<=' => __( 'at most', 'wp-marketing-automations-pro' ),
		);
	}
}
