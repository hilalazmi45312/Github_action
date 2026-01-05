<?php

class BWFAN_Rule_Customer_Is_WP_User extends BWFAN_Rule_Base {
	public $supports = array( 'order' );

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'customer_is_wp_user' );
	}

	public function get_possible_rule_values() {
		return array(
			'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
			'no'  => __( 'No', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_default_rule_value() {
		return 'yes';
	}

	public function get_condition_input_type() {
		return 'Select';
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_possible_rule_values();
	}

	public function get_rule_type() {
		return 'Select';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$value = false;

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];

		$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** checking with user id if available */
		if ( ! empty( $user_id ) ) {
			$user_data = get_userdata( $user_id );
			if ( $user_data instanceof WP_User ) {
				$value = true;

				return $this->return_is_match( ( 'yes' === $rule_data['data'] ) ? $value : ! $value, $rule_data );
			}
		}

		$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';

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

		/** get email from abandoned data */
		if ( ! is_email( $email ) ) {
			$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
		}

		if ( ! is_email( $email ) ) {
			return false;
		}

		$user_by_email = get_user_by( 'email', $email );

		/** check wp user if email available */
		if ( $user_by_email instanceof WP_User ) {
			$value = true;

			return $this->return_is_match( ( 'yes' === $rule_data['data'] ) ? $value : ! $value, $rule_data );
		}

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$contact = BWFAN_PRO_Common::get_bwf_contact_by_email( $email );
		}

		if ( $contact instanceof WooFunnels_Contact && $contact->get_wpid() > 0 ) {
			$value = true;
		}

		return $this->return_is_match( ( 'yes' === $rule_data['data'] ) ? $value : ! $value, $rule_data );
	}

	/** v2 Methods: END */

	public function is_match( $rule_data ) {
		$value = false;
		/**
		 * @var Woofunnels_Customer $customer
		 */
		$customer = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
		$contact  = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

		$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
		$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

		/** checking with user id if available */
		if ( ! empty( $user_id ) ) {
			$user_data = get_userdata( $user_id );
			if ( $user_data instanceof WP_User ) {
				$value = true;

				return $this->return_is_match( ( 'yes' === $rule_data['condition'] ) ? $value : ! $value, $rule_data );
			}
		}

		$email = BWFAN_Core()->rules->getRulesData( 'email' );
		$email = is_array( $email ) ? '' : $email;

		if ( ( empty( $email ) || ! is_email( $email ) ) && class_exists( 'WooCommerce' ) ) {
			$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
			$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
		}

		/** Get email from abandoned data */
		if ( empty( $email ) || ! is_email( $email ) ) {
			$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
		}

		/** If no email, then return */
		if ( empty( $email ) || ! is_email( $email ) ) {
			$value = false;

			return $this->return_is_match( ( 'yes' === $rule_data['condition'] ) ? $value : ! $value, $rule_data );
		}

		/** Check wp user if email available */
		$user_by_email = get_user_by( 'email', $email );
		if ( $user_by_email instanceof WP_User ) {
			$value = true;

			return $this->return_is_match( ( 'yes' === $rule_data['condition'] ) ? $value : ! $value, $rule_data );
		}

		/** Get Contact if contact ID is available */
		$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );

		/** Check for contact object by ID or email */
		if ( ! $contact instanceof WooFunnels_Contact && ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}
		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$contact = BWFAN_PRO_Common::get_bwf_contact_by_email( $email );
		}
		if ( $contact instanceof WooFunnels_Contact && $contact->get_wpid() > 0 ) {
			$value = true;
		}

		return $this->return_is_match( ( 'yes' === $rule_data['condition'] ) ? $value : ! $value, $rule_data );
	}

	public function ui_view() {
		?>
        <% if (condition == "yes") { %>A<% } %>
        <% if (condition == "no") { %>Not a<% } %>
		<?php
		esc_html_e( ' WordPress User', 'wp-marketing-automations-pro' );
	}

	public function get_possible_rule_operators() {
		return null;
	}

}

class BWFAN_Rule_Customer_Custom_Field extends BWFAN_Rule_Custom_Field {

	public function __construct() {
		$this->v1 = true;
		$this->v2 = false;
		parent::__construct( 'customer_custom_field' );
	}

	public function get_possible_value( $key ) {

		$email = BWFAN_Core()->rules->getRulesData( 'email' );
		if ( 'email' === $key && is_email( $email ) ) {
			return $email;
		}

		/**
		 * @var Woofunnels_Customer $customer
		 */
		$customer = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
		$contact  = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

		$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
		$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

		/** Get Contact if contact ID is available */
		$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
		if ( ! $contact instanceof WooFunnels_Contact && ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return '';
		}

		$value = '';
		if ( method_exists( $contact, 'get_' . $key ) ) {
			$value = $contact->{'get_' . $key}();
		} else {
			$contact = new BWFCRM_Contact( $contact );
			$value   = $contact->get_field_by_slug( $key );
		}

		/** Check if the string is JSON */
		$json_decoded_value = json_decode( $value, true );
		if ( ! empty( $json_decoded_value ) ) {
			$value = $json_decoded_value;
		}

		return $value;
	}

	public function is_match( $rule_data ) {
		$type  = $rule_data['operator'];
		$value = $this->get_possible_value( $rule_data['condition']['key'] );

		$value = BWFAN_Pro_Rules::make_value_as_array( $value );

		$value           = array_map( 'strtolower', $value );
		$condition_value = strtolower( trim( $rule_data['condition']['value'] ) );

		switch ( $type ) {
			case 'is':
				$result = in_array( $condition_value, $value );
				break;
			case 'isnot':
			case 'is_not':
				$result = ! in_array( $condition_value, $value );
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
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function ui_view() {
		esc_html_e( 'Contact Field', 'wp-marketing-automations-pro' );
		?>
        '<%= condition['key'] %>' <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
        <%= ops[operator] %> '<%= condition['value'] %>'
		<?php
	}

	public function get_possible_rule_operators() {
		return $this->operator_matches();
	}

	public function conditions_view() {
		$condition_input_type = 'Select';
		$values               = $this->get_custom_fields();
		$value_args           = array(
			'input'       => $condition_input_type,
			'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition][key]',
			'choices'     => $values,
			'class'       => 'bwfan_field_one_half',
			'placeholder' => __( 'Key', 'wp-marketing-automations-pro' ),
		);

		bwfan_Input_Builder::create_input_field( $value_args );
		$condition_input_type = $this->get_condition_input_type();
		$values               = $this->get_possible_rule_values();
		$value_args           = array(
			'input'       => $condition_input_type,
			'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition][value]',
			'choices'     => $values,
			'class'       => 'bwfan_field_one_half',
			'placeholder' => __( 'Value', 'wp-marketing-automations-pro' ),
		);

		bwfan_Input_Builder::create_input_field( $value_args );
	}

	/** get custom field array
	 * @return array
	 */
	public function get_custom_fields() {
		$fields = BWFCRM_Fields::get_fields( null, 1 );

		if ( empty( $fields ) ) {
			return array();
		}

		$field_data  = array();
		$field_data1 = array();
		foreach ( $fields as $field_id => $field ) {
			if ( isset( $field['name'] ) && isset( $field['slug'] ) ) {
				$field_data[ $field['slug'] ] = $field['name'];
			} else {
				$field_data1[ $field_id ] = $field;
			}
		}
		$field_data['email'] = 'Email';
		$field_data          = array_merge( $field_data1, $field_data );

		return $field_data;
	}

}

class BWFAN_Rule_Customer_Marketing_Status extends BWFAN_Rule_Base {
	public $supports = array( 'order' );

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'customer_marketing_status' );
	}

	public function get_possible_rule_values() {
		if ( method_exists( 'BWFAN_Common', 'get_contact_status_array_list' ) ) {
			$status = BWFAN_Common::get_contact_status_array_list();

			$arr = [ '1' => __( 'Subscribed', 'wp-marketing-automations-pro' ) ];
			foreach ( $status as $key => $value ) {
				$arr[ $key ] = $value['name'];
			}

			return $arr;
		}

		return array(
			'1' => __( 'Subscribed', 'wp-marketing-automations-pro' ),
			'0' => __( 'Unverified', 'wp-marketing-automations-pro' ),
			'3' => __( 'Unsubscribed', 'wp-marketing-automations-pro' ),
			'2' => __( 'Bounced', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_default_rule_value() {
		return '1';
	}

	public function get_condition_input_type() {
		return 'Select';
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_possible_rule_values();
	}

	public function get_rule_type() {
		return 'Select';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$value = false;

		$email    = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
		$order    = bwfan_is_woocommerce_active() ? wc_get_order( $order_id ) : '';
		if ( ! is_email( $email ) ) {
			$email = $order instanceof WC_Order ? $order->get_billing_email() : $email;
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		if ( ! is_email( $email ) ) {
			$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
		}

		/** Get User ID */
		$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact ID */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;

		/** get email from user id if still not available */
		if ( ! is_email( $email ) && ! empty( $user_id ) ) {
			$user_data = get_userdata( $user_id );
			$email     = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		/** Get Contact Display Status */
		$status = $this->get_contact_display_status( $contact_id, $email, $user_id );

		/** Check status from order, if contact has no status */
		if ( false === $status && bwfan_is_woocommerce_active() && $order instanceof WC_Order ) {
			$marketing_status = BWFAN_Woocommerce_Compatibility::get_order_data( $order, 'marketing_status' );

			if ( ! empty( $marketing_status ) ) {
				if ( 1 === absint( $marketing_status ) ) {
					$value = true;
				}

				return $this->return_is_match( ( '1' === $rule_data['data'] ) ? $value : ! $value, $rule_data );
			}
		}

		/** Check for Status */
		$selected = absint( $rule_data['data'] );
		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_UNVERIFIED && 0 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_SUBSCRIBED && 1 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_UNSUBSCRIBED && 3 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_BOUNCED && 2 === $selected ) {
			$value = true;
		}

		return $this->return_is_match( $value, $rule_data );
	}

	/** v2 Methods: END */

	public function is_match( $rule_data ) {
		$value = false;

		/** Get Email from any source */
		$email = BWFAN_Core()->rules->getRulesData( 'email' );

		/** @var WC_Order $order */
		$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
		if ( bwfan_is_woocommerce_active() && ! is_email( $email ) && $order instanceof WC_Order ) {
			$email = $order->get_billing_email();
		}

		$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
		if ( ! is_email( $email ) ) {
			$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
		}

		/** Get User ID */
		$user_id = BWFAN_Core()->rules->getRulesData( 'user_id' );

		/** Get Contact ID */
		$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );

		/** get email from user id if still not available */
		if ( ! is_email( $email ) && ! empty( $user_id ) ) {
			$user_data = get_userdata( $user_id );
			$email     = $user_data instanceof WP_User ? $user_data->user_email : '';
		}

		/** Get Contact Display Status */
		$status = $this->get_contact_display_status( $contact_id, $email, $user_id );

		/** Check status from order, if contact has no status */
		if ( false === $status && bwfan_is_woocommerce_active() && $order instanceof WC_Order ) {
			$marketing_status = BWFAN_Woocommerce_Compatibility::get_order_data( $order, 'marketing_status' );

			if ( ! empty( $marketing_status ) ) {
				if ( 1 === absint( $marketing_status ) ) {
					$value = true;
				}

				return $this->return_is_match( ( '1' === $rule_data['condition'] ) ? $value : ! $value, $rule_data );
			}
		}

		/** Check for Status */
		$selected = absint( $rule_data['condition'] );
		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_UNVERIFIED && 0 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_SUBSCRIBED && 1 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_UNSUBSCRIBED && 3 === $selected ) {
			$value = true;
		}

		if ( $status === BWFCRM_Contact::$DISPLAY_STATUS_BOUNCED && 2 === $selected ) {
			$value = true;
		}

		return $this->return_is_match( $value, $rule_data );
	}

	public function get_contact_display_status( $contact_id = '', $email = '', $user_id = '' ) {
		$contact = new WooFunnels_Contact( $user_id, $email, '', $contact_id );
		if ( 0 === absint( $contact->get_id() ) ) {
			return false;
		}

		$crm_contact = new BWFCRM_Contact( $contact );
		$status      = $crm_contact->get_display_status();

		return $status instanceof WP_Error ? false : $status;
	}

	public function ui_view() {

		esc_html_e( ' Contact Status is ', 'wp-marketing-automations-pro' );
		?>
        <% if (condition == "0") { %>Unverified<% } %>
        <% if (condition == "1") { %>Subscribed<% } %>
        <% if (condition == "2") { %>Bounced<% } %>
        <% if (condition == "3") { %>Unsubscribed<% } %>
		<?php
	}

	public function get_possible_rule_operators() {
		return null;
	}

}

class BWFAN_Rule_Contact_Tags extends BWFAN_Dynamic_Option_Base {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'contact_tags' );
	}

	public function get_condition_input_type() {
		return 'Chosen_Select';
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_search_results( $term, true );
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$contact = false;

		$email    = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $contact_id );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = wc_get_order( $order_id );
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email from abandoned cart */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$contact = bwf_get_contact( null, $email );
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return false;
		}

		$contact_tags = $contact->get_tags();

		$data = $rule_data['data'];
		if ( ! is_array( $data ) && empty( $data ) ) {
			return false;
		}

		$saved_tags = array_map( function ( $tag ) {
			return absint( $tag['key'] );
		}, $data );
		$result     = $this->validate_set( $saved_tags, $contact_tags, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_values() {
		$result = array();
		$tags   = BWFCRM_Tag::get_tags();
		if ( empty( $tags ) ) {
			return $result;
		}

		foreach ( $tags as $tag ) {
			$result[ $tag['ID'] ] = $tag['name'];
		}

		return $result;
	}

	public function conditions_view() {
		$condition_input_type = $this->get_condition_input_type();
		$values               = $this->get_possible_rule_values();
		$value_args           = array(
			'input'       => $condition_input_type,
			'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition]',
			'choices'     => $values,
			'ajax'        => true,
			'search_type' => $this->get_search_type_name(),
			'rule_type'   => $this->rule_type,
		);
		bwfan_Input_Builder::create_input_field( $value_args );
	}

	public function get_search_type_name() {
		return 'crm_tags';
	}

	public function get_search_results( $term, $v2 = false ) {
		$array    = array();
		$get_tags = BWFCRM_Tag::get_tags( array(), $term );

		if ( empty( $get_tags ) ) {
			if ( $v2 ) {
				return array();
			}

			wp_send_json( array(
				'results' => $array,
			) );
		}

		if ( $v2 ) {
			foreach ( $get_tags as $tag ) {
				$array[ $tag['ID'] ] = $tag['name'];
			}

			return $array;
		}

		foreach ( $get_tags as $tag ) {
			array_push( $array, array(
				'id'   => $tag['ID'],
				'text' => $tag['name'],
			) );
		}
		wp_send_json( array(
			'results' => $array,
		) );
	}

	public function get_possible_rule_operators() {
		return array(
			'any'    => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'hasnot' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			'has'    => __( 'matches all of', 'wp-marketing-automations-pro' ),
		);
	}

	public function is_match( $rule_data ) {
		/**
		 * @var Woofunnels_Customer $customer
		 */
		$rules_data = BWFAN_Core()->rules->getRulesData();
		$customer   = isset( $rules_data['bwf_customer'] ) ? $rules_data['bwf_customer'] : '';
		$contact    = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

		$contact_id = isset( $rules_data['contact_id'] ) ? $rules_data['contact_id'] : '';
		if ( ! empty( $contact_id ) && ! $contact instanceof WooFunnels_Contact ) {
			$contact = new WooFunnels_Contact( '', '', '', $contact_id );
		}

		$abandoned_data = isset( $rules_data['abandoned_data'] ) ? $rules_data['abandoned_data'] : '';
		$user_id        = isset( $rules_data['user_id'] ) ? $rules_data['user_id'] : 0;
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$email = $rules_data['email'];
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = $rules_data['wc_order'];
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email from abandoned cart */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$contact = bwf_get_contact( null, $email );
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return false;
		}

		$contact_tags = $contact->get_tags();
		$rule_tags    = array_map( 'absint', $rule_data['condition'] );
		$result       = $this->validate_set( $rule_tags, $contact_tags, $rule_data['operator'] );

		return $this->return_is_match( $result, $rule_data );

	}

	public function validate_set( $tags, $found_tags, $operator ) {
		switch ( $operator ) {
			case 'any':
				$result = count( array_intersect( $tags, $found_tags ) ) > 0;
				break;
			case 'has':
				$result = count( array_intersect( $tags, $found_tags ) ) === count( $tags );
				break;
			case 'hasnot':
				$result = count( array_intersect( $tags, $found_tags ) ) === 0;
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

	public function ui_view() {
		esc_html_e( 'Contact Tags matches ', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>');%>

        <%= ('any' === operator) ? 'any of' : ('has' === operator) ? 'all of' : 'none of' %>
        <% var chosen = []; %>
        <% _.each(condition, function( value, key ){ %>
        <%
        if(_.has(uiData, value)) {
        chosen.push("'"+uiData[value]+"'");
        }
        %>
        <% }); %>

        <%= chosen.join(", ") %>
		<?php
	}
}

class BWFAN_Rule_Contact_Lists extends BWFAN_Dynamic_Option_Base {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'contact_lists' );
	}

	public function get_condition_input_type() {
		return 'Chosen_Select';
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_search_results( $term, true );
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$email    = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
		$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $contact_id );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = wc_get_order( $order_id );
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email from abandoned cart */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$contact = bwf_get_contact( null, $email );
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return false;
		}

		$contact_lists = $contact->get_lists();

		$data = $rule_data['data'];
		if ( ! is_array( $data ) && empty( $data ) ) {
			return false;
		}

		$saved_lists = array_map( function ( $list ) {
			return absint( $list['key'] );
		}, $data );
		$result      = $this->validate_set( $saved_lists, $contact_lists, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_values() {
		$result = array();
		$lists  = BWFCRM_Lists::get_lists();
		if ( empty( $lists ) ) {
			return $result;
		}

		foreach ( $lists as $list ) {
			$result[ $list['ID'] ] = $list['name'];
		}

		return $result;
	}

	public function conditions_view() {
		$condition_input_type = $this->get_condition_input_type();
		$values               = $this->get_possible_rule_values();
		$value_args           = array(
			'input'       => $condition_input_type,
			'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition]',
			'choices'     => $values,
			'ajax'        => true,
			'search_type' => $this->get_search_type_name(),
			'rule_type'   => $this->rule_type,
		);
		bwfan_Input_Builder::create_input_field( $value_args );
	}

	public function get_search_type_name() {
		return 'crm_lists';
	}

	public function get_search_results( $term, $v2 = false ) {
		$array     = array();
		$get_lists = BWFCRM_Lists::get_lists( array(), $term );

		if ( empty( $get_lists ) ) {
			if ( $v2 ) {
				return array();
			}

			wp_send_json( array(
				'results' => $array,
			) );
		}

		if ( $v2 ) {
			foreach ( $get_lists as $list ) {
				$array[ $list['ID'] ] = $list['name'];
			}

			return $array;
		}

		foreach ( $get_lists as $list ) {
			array_push( $array, array(
				'id'   => $list['ID'],
				'text' => $list['name'],
			) );
		}
		wp_send_json( array(
			'results' => $array,
		) );
	}

	public function is_match( $rule_data ) {
		/**
		 * @var Woofunnels_Customer $customer
		 */
		$rules_data = BWFAN_Core()->rules->getRulesData();
		$customer   = isset( $rules_data['bwf_customer'] ) ? $rules_data['bwf_customer'] : '';
		$contact    = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

		$contact_id = isset( $rules_data['contact_id'] ) ? $rules_data['contact_id'] : '';
		if ( ! empty( $contact_id ) && ! $contact instanceof WooFunnels_Contact ) {
			$contact = new WooFunnels_Contact( '', '', '', $contact_id );
		}

		$abandoned_data = isset( $rules_data['abandoned_data'] ) ? $rules_data['abandoned_data'] : '';
		$user_id        = $rules_data['user_id'];

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$email = $rules_data['email'];
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = $rules_data['wc_order'];
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email from abandoned cart */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$contact = BWFAN_PRO_Common::get_bwf_contact_by_email( $email );
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return false;
		}

		$contact_lists = $contact->get_lists();
		$rule_lists    = array_map( 'absint', $rule_data['condition'] );
		$result        = $this->validate_set( $rule_lists, $contact_lists, $rule_data['operator'] );

		return $this->return_is_match( $result, $rule_data );
	}

	public function validate_set( $lists, $found_lists, $operator ) {
		switch ( $operator ) {
			case 'any':
				$result = count( array_intersect( $lists, $found_lists ) ) > 0;
				break;
			case 'has':
				$result = count( array_intersect( $lists, $found_lists ) ) === count( $lists );
				break;
			case 'hasnot':
				$result = count( array_intersect( $lists, $found_lists ) ) === 0;
				break;

			default:
				$result = false;
				break;
		}

		return $result;
	}

	public function ui_view() {
		esc_html_e( 'Contact Lists matches ', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>');%>

        <%= ('any' === operator) ? 'any of' : ('has' === operator) ? 'all of' : 'none of' %>
        <% var chosen = []; %>
        <% _.each(condition, function( value, key ){ %>
        <%
        if(_.has(uiData, value)) {
        chosen.push("'"+uiData[value]+"'");
        }
        %>
        <% }); %>

        <%= chosen.join(", ") %>
		<?php
	}

	public function get_possible_rule_operators() {
		return array(
			'any'    => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'hasnot' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			'has'    => __( 'matches all of', 'wp-marketing-automations-pro' ),
		);
	}
}

class BWFAN_Rule_Contact_Country extends BWFAN_Rule_Country {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'contact_country' );
	}

	public function get_objects_country( $automation_data = [] ) {

		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $contact_id );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			if ( ! is_email( $email ) && bwfan_is_woocommerce_active() ) {
				$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				if ( ! empty( $order_id ) ) {
					$order = wc_get_order( $order_id );
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
				}
			}

			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email form abandoned data */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$contact = BWFAN_PRO_Common::get_bwf_contact_by_email( $email );
			if ( ! $contact instanceof WooFunnels_Contact ) {
				return false;
			}
		}

		$contact_country = $contact->get_country();
		if ( empty( $contact_country ) ) {
			return false;
		}

		return array( $contact_country );
	}

	public function ui_view() {
		esc_html_e( 'Contact Country', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

        <%= ops[operator] %>
        <% var chosen = []; %>
        <% _.each(condition, function( value, key ){ %>
        <% chosen.push(uiData[value]); %>

        <% }); %>
        <%= chosen.join("/") %>
		<?php
	}
}

class BWFAN_Rule_Contact_State extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'contact_state' );
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			if ( ! is_email( $email ) && bwfan_is_woocommerce_active() ) {
				$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				if ( ! empty( $order_id ) ) {
					$order = wc_get_order( $order_id );
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
				}
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

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return '';
		}

		$contact = new BWFCRM_Contact( $contact );
		$value   = $contact->contact->get_state();

		$type        = $rule_data['rule'];
		$value       = strtolower( $value );
		$saved_value = strtolower( trim( $rule_data['data'] ) );
		switch ( $type ) {
			case 'is':
				$result = $saved_value === $value;
				break;
			case 'isnot':
			case 'is_not':
				$result = $saved_value !== $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_value() {
		/**
		 * @var Woofunnels_Customer $customer
		 */
		$customer = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
		$contact  = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

		$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
		$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

		/** Get Contact if contact ID is available */
		$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
		if ( ! $contact instanceof WooFunnels_Contact && ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = BWFAN_Core()->rules->getRulesData( 'email' );
			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			return '';
		}

		$contact = new BWFCRM_Contact( $contact );

		return $contact->contact->get_state();
	}

	public function is_match( $rule_data ) {
		$type            = $rule_data['operator'];
		$value           = $this->get_possible_value();
		$value           = strtolower( $value );
		$condition_value = strtolower( trim( $rule_data['condition'] ) );
		switch ( $type ) {
			case 'is':
				$result = $condition_value === $value;
				break;
			case 'isnot':
			case 'is_not':
				$result = $condition_value !== $value;
				break;
			case 'is_blank':
				$result = empty( $value );
				break;
			case 'is_not_blank':
				$result = ! empty( $value );
				break;
			default:
				$result = false;
				break;

		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function ui_view() {
		esc_html_e( 'Contact State', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
        <%= ops[operator] %> '<%= condition %>'
		<?php
	}

	public function get_possible_rule_operators() {
		return array(
			'is'           => __( 'is', 'wp-marketing-automations-pro' ),
			'is_not'       => __( 'is not', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_condition_input_type() {
		return 'text';
	}
}

class BWFAN_Rule_Contact_Address_1 extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_address_1' );
	}

	/** v2 Methods: START */
	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
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
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$address_1 = $contact->get_address_1() ? strtolower( trim( $contact->get_address_1() ) ) : '';
		if ( empty( $address_1 ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $address_1 );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Address_2 extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_address_2' );
	}

	/** v2 Methods: START */
	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
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
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$address_2 = $contact->get_address_2() ? strtolower( trim( $contact->get_address_2() ) ) : '';
		if ( empty( $address_2 ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $address_2 );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */
	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_City extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_city' );
	}

	/** v2 Methods: START */
	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
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
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$city = $contact->get_city() ? strtolower( trim( $contact->get_city() ) ) : '';
		if ( empty( $city ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $city );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Post_Code extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_post_code' );
	}

	/** v2 Methods: START */
	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
		$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

		/** Get Contact if contact ID is available */
		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		$contact    = false;
		if ( ! empty( $contact_id ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_id() ) ) {
			$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
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
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}

			if ( empty( $user_id ) ) {
				$contact = new WooFunnels_Contact( '', $email );
			} else {
				$contact = new WooFunnels_Contact( $user_id, $email );
			}
		}

		if ( ! $contact instanceof WooFunnels_Contact || empty( $contact->get_id() ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $contact );
		if ( ! $contact->is_contact_exists() ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$postcode = $contact->get_postcode() ? strtolower( trim( $contact->get_postcode() ) ) : '';
		if ( empty( $postcode ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $postcode );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}
}

class BWFAN_Rule_Contact_Audience extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'contact_audience' );
	}

	public function conditions_view() {
		$condition_input_type = $this->get_condition_input_type();
		$values               = $this->get_possible_rule_values();
		$value_args           = array(
			'input'       => $condition_input_type,
			'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition]',
			'choices'     => $values,
			'placeholder' => __( 'Value', 'wp-marketing-automations-pro' ),
		);
		bwfan_Input_Builder::create_input_field( $value_args );
	}

	public function get_condition_input_type() {
		return 'Select';
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_possible_rule_values( $term );
	}

	public function get_rule_type() {
		return 'Select';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;

		if ( empty( $contact_id ) ) {
			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$email          = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';

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

			$contact    = new WooFunnels_Contact( '', $email );
			$contact_id = $contact->get_id();
		}

		if ( empty( $contact_id ) ) {
			return $this->return_is_match( false, $rule_data );
		}
		$value             = true;
		$selected_audience = $rule_data['data'];

		$contacts = BWFCRM_Contact::get_contacts_by_audience( $selected_audience, '', $contact_id );
		if ( ! isset( $contacts['contacts'] ) || ! is_array( $contacts['contacts'] ) || 0 === count( $contacts['contacts'] ) ) {
			$value = false;
		}

		$type = $rule_data['rule'];
		switch ( $type ) {
			case 'is':
				$result = true === $value;
				break;
			case 'is_not':
				$result = true !== $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_values( $term = '' ) {
		$result    = array();
		$audiences = BWFAN_Model_Terms::get_terms( 3, 0, 0, $term );
		if ( empty( $audiences ) ) {
			return $result;
		}

		foreach ( $audiences as $audience ) {
			$result[ $audience['name'] ] = $audience['name'];
		}

		return $result;
	}

	public function is_match( $rule_data ) {
		$type  = $rule_data['operator'];
		$value = $this->get_possible_value( $rule_data );
		switch ( $type ) {
			case 'is':
				$result = true === $value;
				break;
			case 'is_not':
				$result = true !== $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_value( $rule_data ) {

		if ( ! isset( $rule_data['condition'] ) ) {
			return false;
		}

		$rules_data = BWFAN_Core()->rules->getRulesData();

		$contact_id = isset( $rules_data['contact_id'] ) ? $rules_data['contact_id'] : '';
		if ( empty( $contact_id ) ) {
			/**
			 * @var Woofunnels_Customer $customer
			 */
			$customer   = isset( $rules_data['bwf_customer'] ) ? $rules_data['bwf_customer'] : '';
			$contact    = $customer instanceof WooFunnels_Customer ? $customer->contact : 0;
			$contact_id = $contact instanceof WooFunnels_Contact ? $contact->get_id() : 0;
		}

		if ( empty( $contact_id ) ) {
			$abandoned_data = isset( $rules_data['abandoned_data'] ) ? $rules_data['abandoned_data'] : '';
			$user_id        = isset( $rules_data['user_id'] ) ? $rules_data['user_id'] : '';

			$email = isset( $rules_data['email'] ) ? $rules_data['email'] : '';

			if ( ! is_email( $email ) && class_exists( 'WooCommerce' ) ) {
				$order = isset( $rules_data['wc_order'] ) ? $rules_data['wc_order'] : '';
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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

			$contact    = new WooFunnels_Contact( '', $email );
			$contact_id = $contact->get_id();
		}

		if ( empty( $contact_id ) ) {
			return false;
		}

		$selected_audience = $rule_data['condition'];
		$contacts          = BWFCRM_Contact::get_contacts_by_audience( $selected_audience, '', $contact_id );
		if ( ! isset( $contacts['contacts'] ) || ! is_array( $contacts['contacts'] ) || 0 === count( $contacts['contacts'] ) ) {
			return false;
		}

		return true;
	}

	public function ui_view() {
		esc_html_e( 'Contact ', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>
        <%= ops[operator] %> in Audience '<%= condition %>'
		<?php
	}

	public function get_possible_rule_operators() {
		return array(
			'is'     => __( 'is', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Contact_First_name extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_first_name' );
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;

		if ( 0 === absint( $cid ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new WooFunnels_Contact( '', '', '', $cid );

		if ( ! $contact->get_id() > 0 ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$first_name = $contact->get_f_name() ? strtolower( trim( $contact->get_f_name() ) ) : '';

		if ( empty( $first_name ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $first_name );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Last_name extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_last_name' );
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;

		if ( 0 === absint( $cid ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new WooFunnels_Contact( '', '', '', $cid );

		if ( ! $contact->get_id() > 0 ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$last_name = $contact->get_l_name() ? strtolower( trim( $contact->get_l_name() ) ) : '';

		if ( empty( $last_name ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $last_name );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Email extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_email' );
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$cid = $automation_data['global']['contact_id'] ?? 0;

		/** If constant is defined then fetch email from contact and return */
		if ( defined( 'BWFAN_GET_CONTACT_EMAIL' ) && true === BWFAN_GET_CONTACT_EMAIL && ! empty( $cid ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $cid );
			$email   = $contact->get_id() > 0 ? trim( $contact->get_email() ) : '';
			if ( ! empty( $email ) ) {
				$type = $rule_data['rule'];
				$data = trim( $rule_data['data'] );

				$result = $this->validate_matches( $type, $data, $email );

				return $this->return_is_match( $result, $rule_data );
			}
		}
		$email = $automation_data['global']['email'] ?? '';

		if ( empty( $email ) ) {
			if ( 0 === intval( $cid ) ) {
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$contact = new WooFunnels_Contact( '', '', '', $cid );
			if ( ! $contact->get_id() > 0 ) {
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$email = $contact->get_email() ? trim( $contact->get_email() ) : '';
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = trim( $rule_data['data'] );

		$result = $this->validate_matches( $type, $data, $email );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return $this->operator_matches();
	}
}

class BWFAN_Rule_Contact_Phone extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_phone' );
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$cid = $automation_data['global']['contact_id'] ?? 0;

		/** If constant is defined then fetch phone no from contact and return */
		if ( defined( 'BWFAN_GET_CONTACT_PHONE' ) && true === BWFAN_GET_CONTACT_PHONE && ! empty( $cid ) ) {
			$contact = new WooFunnels_Contact( '', '', '', $cid );
			$phone   = $contact->get_id() > 0 ? trim( $contact->get_contact_no() ) : '';
			if ( ! empty( $phone ) ) {
				$type   = $rule_data['rule'];
				$data   = trim( $rule_data['data'] );
				$result = $this->validate_matches( $type, $data, $phone );

				return $this->return_is_match( $result, $rule_data );
			}
		}

		$phone = $automation_data['global']['phone'] ?? '';
		if ( empty( $phone ) ) {
			if ( 0 === absint( $cid ) ) {
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$contact = new WooFunnels_Contact( '', '', '', $cid );
			if ( ! $contact->get_id() > 0 ) {
				$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$phone = $contact->get_contact_no() ? trim( $contact->get_contact_no() ) : '';
		}

		if ( empty( $phone ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) );

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = trim( $rule_data['data'] );

		$result = $this->validate_matches( $type, $data, $phone );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Creation_Date extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_creation_date' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Date';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new WooFunnels_Contact( '', '', '', $cid );
		if ( ! $contact->get_id() > 0 ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$creation_date = $contact->get_creation_date() ? date( 'Y-m-d', strtotime( $contact->get_creation_date() ) ) : '';
		if ( empty( $creation_date ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		$creation_date = strtotime( $creation_date );

		$type = $rule_data['rule'];
		if ( 'between' === $type && is_array( $rule_data['data'] ) ) {
			$from   = strtotime( $rule_data['data']['from'] );
			$to     = strtotime( $rule_data['data']['to'] );
			$result = ( ( $creation_date >= $from ) && ( $creation_date <= $to ) );

			return $this->return_is_match( $result, $rule_data );
		}

		$filter_value = strtotime( $rule_data['data'] );

		switch ( $type ) {
			case 'before':
				$result = ( $creation_date < $filter_value );
				break;
			case 'after':
				$result = ( $creation_date > $filter_value );
				break;
			case 'is':
				$result = ( $creation_date === $filter_value );
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'before'  => __( 'Before', 'wp-marketing-automations-pro' ),
			'after'   => __( 'After', 'wp-marketing-automations-pro' ),
			'is'      => __( 'On', 'wp-marketing-automations-pro' ),
			'between' => __( 'Between', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Contact_Creation_Days extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_creation_days' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new WooFunnels_Contact( '', '', '', $cid );
		if ( ! $contact->get_id() > 0 ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$creation_date = $contact->get_creation_date() ? date( 'Y-m-d', strtotime( $contact->get_creation_date() ) ) : ''; //excluding time

		if ( empty( $creation_date ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$creation_date = strtotime( $creation_date );
		$type          = $rule_data['rule'];
		$result        = $this->validate_matches_duration_set( $creation_date, $rule_data, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Contact_Company extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_company' );
	}

	/** v2 Methods: START */

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$company = $contact->get_company() ? strtolower( trim( $contact->get_company() ) ) : '';

		if ( empty( $company ) && 'is_blank' !== $rule_data['rule'] && 'is_not_blank' !== $rule_data['rule'] ) {
			$result = in_array( $rule_data['rule'], array( 'is_not', 'not_contains' ) ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		$result = $this->validate_matches( $type, $data, $company );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		$operators                 = $this->operator_matches();
		$operators['is_blank']     = __( 'is blank', 'wp-marketing-automations-pro' );
		$operators['is_not_blank'] = __( 'is not blank', 'wp-marketing-automations-pro' );

		return $operators;
	}

}

class BWFAN_Rule_Contact_Gender extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_gender' );
	}

	/** v2 Methods: START */
	public function get_options( $term = '' ) {
		return $this->get_possible_rule_values();
	}

	public function get_rule_type() {
		return 'Select';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$gender = $contact->get_gender() ? strtolower( trim( $contact->get_gender() ) ) : '';
		if ( empty( $gender ) ) {
			$result = ( 'is_blank' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$type = $rule_data['rule'];
		$data = strtolower( trim( $rule_data['data'] ) );

		switch ( $type ) {
			case 'is':
				$result = ( $data === $gender );
				break;
			case 'isnot':
			case 'is_not':
				$result = ( $data !== $gender );
				break;
			case 'is_blank':
				$result = empty( $gender );
				break;
			case 'is_not_blank':
				$result = ! empty( $gender );
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_values() {
		return array(
			'male'   => __( 'Male', 'wp-marketing-automations-pro' ),
			'female' => __( 'Female', 'wp-marketing-automations-pro' ),
			'other'  => __( 'Other', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_operators() {
		return array(
			'is'           => __( 'is', 'wp-marketing-automations-pro' ),
			'is_not'       => __( 'is not', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}
}

class BWFAN_Rule_Contact_DOB extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'contact_dob' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Date';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$dob = $contact->get_dob() ? $contact->get_dob() : '';

		$type = $rule_data['rule'];
		$dob  = strtotime( $dob );

		if ( 'between' === $type && is_array( $rule_data['data'] ) ) {
			$from   = strtotime( $rule_data['data']['from'] );
			$to     = strtotime( $rule_data['data']['to'] );
			$result = ( ( $dob >= $from ) && ( $dob <= $to ) );

			return $this->return_is_match( $result, $rule_data );
		}

		$filter_value = strtotime( $rule_data['data'] );

		switch ( $type ) {
			case 'before':
				$result = ( $dob < $filter_value );
				break;
			case 'after':
				$result = ( $dob > $filter_value );
				break;
			case 'is':
				$result = ( $dob === $filter_value );
				break;
			case 'is_blank':
				$result = empty( $dob );
				break;
			case 'is_not_blank':
				$result = ! empty( $dob );
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'before'       => __( 'Before', 'wp-marketing-automations-pro' ),
			'after'        => __( 'After', 'wp-marketing-automations-pro' ),
			'is'           => __( 'On', 'wp-marketing-automations-pro' ),
			'between'      => __( 'Between', 'wp-marketing-automations-pro' ),
			'is_blank'     => __( 'is blank', 'wp-marketing-automations-pro' ),
			'is_not_blank' => __( 'is not blank', 'wp-marketing-automations-pro' ),
		);
	}
}
