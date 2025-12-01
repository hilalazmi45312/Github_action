<?php

class BWFAN_Rule_Contact_Field extends BWFAN_Rule_Base {

	public $slug = null;
	public $type = null;

	public function __construct( $slug = '', $type = '' ) {
		$this->v1   = false;
		$this->v2   = true;
		$this->slug = $slug;
		$this->type = $type;
		parent::__construct( $slug );
	}

	/**
	 * @param $search
	 *
	 * @return array|string[]
	 */
	public function get_options( $search = '' ) {

		$allowed_types = [ 4, 5 ];
		$field_id      = str_replace( 'bwf_contact_field_f', '', $this->slug );
		$field         = BWFAN_Model_Fields::get_field_by_id( $field_id );
		/** If no option found */
		if ( empty( $field ) || ! isset( $field['meta'] ) || ! is_object( $field['meta'] ) || ! property_exists( $field['meta'], 'options' ) ) {
			return [];
		}
		if ( in_array( absint( $this->type ), $allowed_types ) ) {
			/** For radio and dropdown field */
			return $this->get_formatted_options( $field['meta']->options );
		} elseif ( 6 === absint( $this->type ) ) {
			/** For checkbox field */
			$options = BWFAN_PRO_Common::search_srting_from_data( $field['meta']->options, $search );

			return $this->get_formatted_options( $options );
		}

		return [];
	}

	public function get_formatted_options( $options ) {
		if ( empty( $options ) || ! is_array( $options ) ) {
			return [];
		}
		$formatted = [ '' => 'Select' ];
		foreach ( $options as $option ) {
			$formatted[ $option ] = $option;
		}

		return $formatted;
	}

	public function get_rule_type() {
		$allowed_types = [ 4, 5 ];
		if ( in_array( absint( $this->type ), $allowed_types, false ) ) {
			return 'Select';
		} elseif ( 6 === absint( $this->type ) ) {
			return 'Search';
		} elseif ( 7 === absint( $this->type ) ) {
			return 'Date';
		} elseif ( 8 === absint( $this->type ) ) {
			return 'datetime';
		} elseif ( 9 === absint( $this->type ) ) {
			return 'time';
		}

		return 'Text';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$key               = '';
		$field             = [];
		$is_date_field     = false;
		$is_datetime_field = false;
		$is_time_field     = false;
		$filter            = isset( $rule_data['filter'] ) ? $rule_data['filter'] : '';
		$field_id          = ! empty( $filter ) ? str_replace( 'bwf_contact_field_f', '', $filter ) : 0;

		if ( $field_id > 0 ) {
			$field             = BWFAN_Model_Fields::get_field_by_id( $field_id );
			$key               = isset( $field['slug'] ) ? $field['slug'] : '';
			$is_date_field     = ( BWFCRM_Fields::$TYPE_DATE === intval( $field['type'] ) );
			$is_datetime_field = ( property_exists( 'BWFCRM_Fields', 'TYPE_DATETIME' ) && BWFCRM_Fields::$TYPE_DATETIME === intval( $field['type'] ) );
			$is_time_field     = ( property_exists( 'BWFCRM_Fields', 'TYPE_TIME' ) && BWFCRM_Fields::$TYPE_TIME === intval( $field['type'] ) );
		}

		$operator = $rule_data['rule'];
		$value    = $this->get_possible_v2_value( $key, $field, $automation_data );

		/** If operator is is_blank or is_not_blank then return after value check */
		if ( 'is_blank' === $operator || 'is_not_blank' === $operator ) {
			return $this->return_is_match(
				( 'is_blank' === $operator && empty( $value ) )
				|| ( 'is_not_blank' === $operator && ! empty( $value ) ),
				$rule_data
			);
		}

		/** If field type is checkbox */
		if ( ! empty( $field ) && isset( $field['type'] ) && BWFCRM_Fields::$TYPE_CHECKBOX === intval( $field['type'] ) && is_array( $rule_data['data'] ) ) {
			$rule_values = array_column( $rule_data['data'], 'key' );

			return $this->return_is_match( $this->validate_set( $rule_values, $value, $operator ), $rule_data );
		}

		/** Check if the field type is text or textarea and don't explode them */
		$value = ! empty( $field ) && isset( $field['type'] ) && in_array( intval( $field['type'] ), array(
			BWFCRM_Fields::$TYPE_TEXT,
			BWFCRM_Fields::$TYPE_TEXTAREA
		), true ) ? array( $value ) : BWFAN_Pro_Rules::make_value_as_array( $value );

		$value = array_map( 'strtolower', $value );

		/** Between case */
		if ( 'between' === $operator && is_array( $rule_data['data'] ) ) {
			$value = isset( $value[0] ) && ! empty( $value[0] ) ? strtotime( $value[0] ) : '';

			$from   = strtotime( $rule_data['data']['from'] );
			$to     = strtotime( $rule_data['data']['to'] );
			$result = ( ( $value >= $from ) && ( $value <= $to ) );

			return $this->return_is_match( $result, $rule_data );
		}

		$condition_value = is_array( $rule_data['data'] ) ? $rule_data['data'] : strtolower( trim( $rule_data['data'] ) );
		if ( in_array( $operator, [ 'is', 'isnot', 'is_not' ] ) ) {
			switch ( $operator ) {
				case 'is':
					if ( true === $is_date_field ) {
						$value           = ! empty( $value[0] ) ? strtotime( $value[0] ) : '';
						$condition_value = DateTime::createFromFormat( 'm/d/Y', trim( $rule_data['data'] ) )->format( 'Y-m-d' );
						$result          = ( $value === strtotime( $condition_value ) );
						break;
					}
					$result = in_array( $condition_value, $value );
					break;
				case 'isnot':
				case 'is_not':
					$result = ! in_array( $condition_value, $value );
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** If the field type is datetime or time */
		if ( true === $is_datetime_field || true === $is_time_field ) {
			$format = strpos( $rule_data['data'], 'T' ) ? 'Y-m-d\TH:i:s' : 'Y-m-d H:i:s';
			$format = true === $is_time_field ? 'H:i' : $format;

			// Convert the contact field time into the correct format for comparison
			$contact_time = new DateTime( $value[0] );
			$contact_time = $contact_time->format( $format );

			// Use common method to check if they match based on the operator
			$result = BWFAN_DateTime_Rule_Common::is_match( $operator, $contact_time, $rule_data['data'] );

			return $this->return_is_match( $result, $rule_data );
		}

		$value  = is_array( $value ) ? $value[0] : '';
		$result = false;

		switch ( $operator ) {
			case 'contains':
				$result = strpos( $value, $condition_value ) !== false;
				break;
			case 'not_contains':
				$result = strpos( $value, $condition_value ) === false;
				break;
			case 'starts_with':
				$length = strlen( $condition_value );
				$result = substr( $value, 0, $length ) === $condition_value;
				break;
			case 'ends_with':
				$length = strlen( $condition_value );
				$result = ( 0 === $length ) || ( substr( $value, - $length ) === $condition_value );
				break;
			case 'lessthan':
				$result = ( $value < $condition_value );
				break;
			case 'morethan':
				$result = ( $value > $condition_value );
				break;
			case 'before':
				$value  = ! empty( $value ) ? strtotime( $value ) : '';
				$result = ( $value < strtotime( $condition_value ) );
				break;
			case 'after':
				$value  = ! empty( $value ) ? strtotime( $value ) : '';
				$result = ( $value > strtotime( $condition_value ) );
				break;
			case 'is_blank':
				$result = empty( $value );
				break;
			case 'is_not_blank':
				$result = ! empty( $value );
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function validate_set( $options, $found_options, $operator ) {
		if ( ! is_array( $found_options ) ) {
			return false;
		}
		switch ( $operator ) {
			case 'any':
				$result = count( array_intersect( $options, $found_options ) ) > 0;
				break;
			case 'has':
				$result = count( array_intersect( $options, $found_options ) ) === count( $options );
				break;
			case 'hasnot':
				$result = count( array_intersect( $options, $found_options ) ) === 0;
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

	public function get_possible_v2_value( $key, $field, $automation_data = [] ) {
		$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		if ( 'email' === $key && is_email( $email ) ) {
			return $email;
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		$contact        = '';

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				$order    = wc_get_order( $order_id );
				$email    = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** check with cart abandoned email */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}
			$contact = new WooFunnels_Contact( '', $email );
			if ( 0 === intval( $contact->get_id() ) ) {
				$contact = new WooFunnels_Contact( $user_id );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return '';
		}

		if ( method_exists( $contact, 'get_' . $key ) ) {
			$value = $contact->{'get_' . $key}();
		} else {
			$contact = new BWFCRM_Contact( $contact );
			$value   = $contact->get_field_by_slug( $key );
		}

		/** If field type is not checkbox then no need to decode */
		if ( isset( $field['type'] ) && BWFCRM_Fields::$TYPE_CHECKBOX !== intval( $field['type'] ) ) {
			return $value;
		}

		/** Check if the string is JSON */
		$json_decoded_value = json_decode( $value, true );
		if ( ! empty( $json_decoded_value ) ) {
			$value = $json_decoded_value;
		}

		return $value;
	}


	public function get_possible_rule_operators() {
		$operators = [];

		switch ( true ) {
			case ( BWFCRM_Fields::$TYPE_TEXT === absint( $this->type ) || BWFCRM_Fields::$TYPE_TEXTAREA === absint( $this->type ) ):
				$operators = $this->get_possible_text_operators();
				break;
			case ( BWFCRM_Fields::$TYPE_SELECT === absint( $this->type ) || BWFCRM_Fields::$TYPE_RADIO === absint( $this->type ) ):
				$operators = $this->get_possible_radio_operators();
				break;
			case ( BWFCRM_Fields::$TYPE_NUMBER === absint( $this->type ) ) :
				$operators = $this->get_possible_number_operators();
				break;
			case ( BWFCRM_Fields::$TYPE_CHECKBOX === absint( $this->type ) ) :
				$operators = $this->get_possible_checkbox_operators();
				break;
			case ( BWFCRM_Fields::$TYPE_DATE === absint( $this->type ) ):
				$operators = $this->get_possible_date_operators();
				break;
			case ( property_exists( 'BWFCRM_Fields', 'TYPE_DATETIME' ) && in_array( intval( $this->type ), [ BWFCRM_Fields::$TYPE_TIME, BWFCRM_Fields::$TYPE_DATETIME ], true ) ) :
				$operators = $this->get_possible_time_operators();
				break;
		}

		return $operators;
	}

	public function get_possible_text_operators() {
		return array(
			'is'           => __( 'is', 'wp-marketing-automations-pro' ),
			'is_not'       => __( 'is not', 'wp-marketing-automations-pro' ),
			'contains'     => __( 'contains', 'wp-marketing-automations-pro' ),
			'not_contains' => __( 'does not contains', 'wp-marketing-automations-pro' ),
			'starts_with'  => __( 'start with', 'wp-marketing-automations-pro' ),
			'ends_with'    => __( 'end with', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_radio_operators() {
		return array(
			'is'           => __( 'is', 'wp-marketing-automations-pro' ),
			'isnot'        => __( 'is not', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_date_operators() {
		return array(
			'before'       => __( 'Before', 'wp-marketing-automations-pro' ),
			'after'        => __( 'After', 'wp-marketing-automations-pro' ),
			'is'           => __( 'On', 'wp-marketing-automations-pro' ),
			'between'      => __( 'Between', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_time_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_number_operators() {
		return array(
			'is'           => __( 'is', 'wp-marketing-automations-pro' ),
			'isnot'        => __( 'is not', 'wp-marketing-automations-pro' ),
			'lessthan'     => __( 'Less Than', 'wp-marketing-automations-pro' ),
			'morethan'     => __( 'More Than', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_checkbox_operators() {
		return array(
			'any'          => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'hasnot'       => __( 'matches none of', 'wp-marketing-automations-pro' ),
			'has'          => __( 'matches all of', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}


}