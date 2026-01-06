<?php

if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	class BWFAN_Rule_Active_Subscription extends BWFAN_Rule_Base {

		public $supports = array( 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'active_subscription' );
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

			$result   = false;
			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$order    = wc_get_order( $order_id );
			$user_id  = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$email    = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';

			/** Fetching user id if not available from the order object */
			if ( empty( $user_id ) && $order instanceof WC_Order ) {
				$user_id = $order->get_user_id();
			}

			/** Fetching user id if not available from the user email */
			if ( empty( $user_id ) ) {
				$user_data = get_user_by( 'email', $email );
				if ( $user_data instanceof WP_User ) {
					$user_id = $user_data->ID;
				}
			}

			if ( absint( $user_id ) > 0 && function_exists( 'wcs_user_has_subscription' ) ) {
				$result = wcs_user_has_subscription( $user_id, '', 'active' );
			}

			return ( 'yes' === $rule_data['data'] ) ? $result : ! $result;
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
				'no'  => __( 'No', 'wp-marketing-automations-pro' ),
			);
		}

		public function is_match( $rule_data ) {

			$result  = false;
			$order   = BWFAN_Core()->rules->getRulesData( 'wc_order' );
			$user_id = BWFAN_Core()->rules->getRulesData( 'user_id' );
			$email   = BWFAN_Core()->rules->getRulesData( 'email' );

			/** Fetching user id if not available from the order object */
			if ( empty( $user_id ) && $order instanceof WC_Order ) {
				$user_id = $order->get_user_id();
			}

			/** Fetching user id if not available from the user email */
			if ( empty( $user_id ) ) {
				$user_data = get_user_by( 'email', $email );
				if ( $user_data instanceof WP_User ) {
					$user_id = $user_data->ID;
				}
			}

			if ( absint( $user_id ) > 0 && function_exists( 'wcs_user_has_subscription' ) ) {
				$result = wcs_user_has_subscription( $user_id, '', 'active' );
			}

			return ( 'yes' === $rule_data['condition'] ) ? $result : ! $result;
		}

		public function ui_view() {
			esc_html_e( 'Contact', 'wp-marketing-automations-pro' );
			?>
			<%  if (condition == "yes") { %> has <% } %>
			<% if (condition == "no") { %> does not have <% } %>

			<?php
			esc_html_e( 'an Active Subscription', 'wp-marketing-automations-pro' );
		}
	}

	class BWFAN_Rule_Subscription_Parent_Order_Status extends BWFAN_Rule_Base {

		public $supports = array( 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_parent_order_status' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription = isset( $automation_data['global']['wc_subscription_id'] ) ? wcs_get_subscription( $automation_data['global']['wc_subscription_id'] ) : null;
			$order        = $subscription instanceof WC_Subscription ? $subscription->get_parent() : false;
			if ( ! $order instanceof WC_Order ) {
				return $this->return_is_match( false, $rule_data );
			}

			$order_status = 'wc-' . $order->get_status();
			$type         = $rule_data['rule'];

			$data = $rule_data['data'];

			$selected_status = array_map( function ( $status ) {
				return $status['key'];
			}, $data );

			$result = false;
			if ( ! is_array( $selected_status ) ) {
				return $this->return_is_match( $result, $rule_data );
			}
			switch ( $type ) {
				case 'is':
					$result = in_array( $order_status, $selected_status, true ) ? true : false;
					break;
				case 'is_not':
					$result = ! in_array( $order_status, $selected_status, true ) ? true : false;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_possible_rule_values() {
			return wc_get_order_statuses();
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function is_match( $rule_data ) {
			/** @var WC_Subscription $subscription */
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );
			$order        = $subscription->get_parent();
			if ( ! $order instanceof WC_Order ) {
				return $this->return_is_match( false, $rule_data );
			}
			$result = false;
			if ( ! is_array( $rule_data['condition'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}
			$order_status = 'wc-' . $order->get_status();
			$type         = $rule_data['operator'];
			switch ( $type ) {
				case 'is':
					$result = in_array( $order_status, $rule_data['condition'], true ) ? true : false;
					break;
				case 'is_not':
					$result = ! in_array( $order_status, $rule_data['condition'], true ) ? true : false;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription parent order status ', 'wp-marketing-automations-pro' )
			?>

			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<%= condition %>
			<?php
		}
	}

	class BWFAN_Rule_Is_Order_Renewal extends BWFAN_Rule_Base {

		public $supports = array( 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'is_order_renewal' );
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

			$result   = false;
			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$order    = wc_get_order( $order_id );
			$result   = $order instanceof WC_Order ? wcs_order_contains_subscription( $order, 'renewal' ) : false;

			return ( 'yes' === $rule_data['data'] ) ? $result : ! $result;
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
				'no'  => __( 'No', 'wp-marketing-automations-pro' ),
			);
		}

		public function is_match( $rule_data ) {
			$order  = BWFAN_Core()->rules->getRulesData( 'wc_order' );
			$result = wcs_order_contains_subscription( $order, 'renewal' );

			return ( 'yes' === $rule_data['condition'] ) ? $result : ! $result;
		}

		public function ui_view() {
			esc_html_e( 'Order', 'woocommerce' );
			?>
			<%  if (condition == "yes") { %> is <% } %>
			<% if (condition == "no") { %> is not <% } %>
			<?php
			esc_html_e( 'a Renewal Order', 'wp-marketing-automations-pro' );
		}
	}

	class BWFAN_Rule_Subscription_Status extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_status' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription = isset( $automation_data['global']['wc_subscription_id'] ) ? wcs_get_subscription( $automation_data['global']['wc_subscription_id'] ) : null;
			$type         = $rule_data['rule'];

			$data = $rule_data['data'];

			$selected_status     = array_map( function ( $status ) {
				return $status['key'];
			}, $data );
			$subscription_status = $subscription instanceof WC_Subscription ? 'wc-' . $subscription->get_status() : '';

			switch ( $type ) {
				case 'is':
					$result = in_array( $subscription_status, $selected_status, true );
					break;
				case 'is_not':
					$result = ! in_array( $subscription_status, $selected_status, true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_values() {
			return wcs_get_subscription_statuses();
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function is_match( $rule_data ) {
			$type                = $rule_data['operator'];
			$subscription        = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );
			$subscription_status = 'wc-' . $subscription->get_status();

			switch ( $type ) {
				case 'is':
					$result = in_array( $subscription_status, $rule_data['condition'], true );
					break;
				case 'is_not':
					$result = ! in_array( $subscription_status, $rule_data['condition'], true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription\'s status ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<% var chosen = []; %>
			<% _.each(condition, function( value, key ){ %>
			<% chosen.push(uiData[value]); %>
			<% }); %>

			<%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}
	}

	class BWFAN_Rule_Subscription_Total extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_total' );
			$this->description = '';
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription = isset( $automation_data['global']['wc_subscription_id'] ) ? wcs_get_subscription( $automation_data['global']['wc_subscription_id'] ) : null;

			$price = $subscription instanceof WC_Subscription ? (float) $subscription->get_total() : 0;
			$value = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $price === $value;
					break;
				case '!=':
					$result = $price !== $value;
					break;
				case '>':
					$result = $price > $value;
					break;
				case '<':
					$result = $price < $value;
					break;
				case '>=':
					$result = $price >= $value;
					break;
				case '<=':
					$result = $price <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function is_match( $rule_data ) {
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );
			$price        = (float) $subscription->get_total();
			$value        = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $price === $value;
					break;
				case '!=':
					$result = $price !== $value;
					break;
				case '>':
					$result = $price > $value;
					break;
				case '<':
					$result = $price < $value;
					break;
				case '>=':
					$result = $price >= $value;
					break;
				case '<=':
					$result = $price <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription Total', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}
	}

	class BWFAN_Rule_Subscription_Item extends BWFAN_Rule_Products {
		public $supports = array( 'subscriptions' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_item' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/** v2 Methods: END */

		public function set_product_types_arr() {
			BWFAN_Common::$offer_product_types = [
				'subscription',
				'variable-subscription',
				'subscription_variation'
			];
		}

		public function get_search_results( $term, $v2 = false ) {
			$this->set_product_types_arr();
			$array = BWFAN_Common::product_search( $term, true, true );
			if ( $v2 ) {
				$v2_data = [];
				foreach ( $array as $item ) {
					$v2_data[ $item['id'] ] = $item['text'];
				}

				return $v2_data;
			}

			wp_send_json( array(
				'results' => $array,
			) );
		}

		public function get_products( $automation_data = [] ) {

			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$subscription_id = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : 0;
				$subscription    = wcs_get_subscription( $subscription_id );
			} else {
				$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );
			}

			$found_ids = [];

			if ( $subscription instanceof WC_Subscription && $subscription->get_items() && is_array( $subscription->get_items() ) && count( $subscription->get_items() ) ) {
				foreach ( $subscription->get_items() as $cart_item ) {

					$product = $cart_item->get_product();

					if ( ! $product instanceof WC_Product ) {
						continue;
					}

					$product_id   = $product->get_id();
					$product_id   = ( $product->get_parent_id() ) ? $product->get_parent_id() : $product_id;
					$variation_id = $cart_item->get_variation_id();

					if ( ! empty( $variation_id ) ) {
						array_push( $found_ids, $variation_id );
						array_push( $found_ids, $product_id );
					} else {
						array_push( $found_ids, $product_id );
					}
				}
			}

			return $found_ids;
		}

		public function ui_view() {
			esc_html_e( 'Subscription\'s items ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %> <% var chosen = []; %>
			<% _.each(condition, function( value, key ){ %>
			<% chosen.push(uiData[value]); %>

			<% }); %>
			<%= chosen.join("/ ") %>
			<?php
		}

	}

	class BWFAN_Rule_Subscription_Payment_Gateway extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_payment_gateway' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription = isset( $automation_data['global']['wc_subscription_id'] ) ? wcs_get_subscription( $automation_data['global']['wc_subscription_id'] ) : null;

			$payment = $subscription instanceof WC_Subscription ? $subscription->get_payment_method() : false;

			/** if empty then check for manual **/
			if ( empty( $payment ) && $subscription->is_manual() ) {
				$payment = 'manual';
			}

			if ( empty( $payment ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$selected_payment = array_map( function ( $payment_gateway ) {
				return $payment_gateway['key'];
			}, $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = in_array( $payment, $selected_payment, true );
					break;
				case 'is_not':
					$result = ! in_array( $payment, $selected_payment, true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_values() {
			$result           = array();
			$result['manual'] = __( 'Manual Renewal', 'woocommerce-subscriptions' );
			foreach ( WC()->payment_gateways()->payment_gateways() as $gateway ) {
				if ( 'yes' === $gateway->enabled && in_array( 'subscriptions', $gateway->supports, true ) ) {
					$result[ $gateway->id ] = $gateway->get_title();
				}
			}

			return $result;
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function is_match( $rule_data ) {
			$type = $rule_data['operator'];
			/** @var WC_Subscription $subscription */
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );
			$payment      = $subscription->get_payment_method();

			/** if empty then check for manual **/
			if ( empty( $payment ) && $subscription->is_manual() ) {
				$payment = 'manual';
			}

			if ( empty( $payment ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			switch ( $type ) {
				case 'is':
					$result = in_array( $payment, $rule_data['condition'], true );
					break;
				case 'is_not':
					$result = ! in_array( $payment, $rule_data['condition'], true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription\'s payment gateway ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<% var chosen = []; %>
			<% _.each(condition, function( value, key ){ %>
			<% chosen.push(uiData[value]); %>

			<% }); %>
			<%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}
	}

	class BWFAN_Rule_Subscription_Old_Status extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_old_status' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_possible_rule_values();
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription_old_status = isset( $automation_data['from_status'] ) ? 'wc-' . $automation_data['from_status'] : '';
			$selected_status         = [];

			if ( is_array( $rule_data['data'] ) ) {

				$selected_status = array_map( function ( $status ) {
					return $status['key'];
				}, $rule_data['data'] );
			}

			switch ( $rule_data['rule'] ) {
				case 'is':
					$result = in_array( $subscription_old_status, $selected_status, true );
					break;
				case 'is_not':
					$result = ! in_array( $subscription_old_status, $selected_status, true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_values() {
			return wcs_get_subscription_statuses();
		}

		public function get_condition_input_type() {
			return 'Chosen_Select';
		}

		public function is_match( $rule_data ) {
			$type                    = $rule_data['operator'];
			$subscription_old_status = 'wc-' . BWFAN_Core()->rules->getRulesData( 'wcs_old_status' );

			switch ( $type ) {
				case 'is':
					$result = in_array( $subscription_old_status, $rule_data['condition'], true );
					break;
				case 'is_not':
					$result = ! in_array( $subscription_old_status, $rule_data['condition'], true );
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription\'s old status ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<% var chosen = []; %>
			<% _.each(condition, function( value, key ){ %>
			<% chosen.push(uiData[value]); %>
			<% }); %>

			<%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			return array(
				'is'     => __( 'is', 'wp-marketing-automations-pro' ),
				'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
			);
		}
	}

	/** this rule will only show when retry failed payment option enabled **/
	if ( 'yes' === get_option( 'woocommerce_subscriptions_enable_retry' ) ) {

		class BWFAN_Rule_Subscription_Failed_Attempt extends BWFAN_Rule_Base {

			public function __construct() {
				$this->v2 = true;
				parent::__construct( 'subscription_failed_attempt' );
				$this->description = '';
			}

			/** v2 Methods: START */

			public function get_rule_type() {
				return 'Number';
			}

			public function is_match_v2( $automation_data, $rule_data ) {
				if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$subscription = isset( $automation_data['global']['wc_subscription_id'] ) ? wcs_get_subscription( $automation_data['global']['wc_subscription_id'] ) : null;

				$order_id = $subscription instanceof WC_Subscription ? $subscription->get_last_order() : 0;
				if ( 0 === absint( $order_id ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				/** @var WCS_Retry[] $retries */
				$retries = WCS_Retry_Manager::store()->get_retries_for_order( $order_id );

				if ( ! is_array( $retries ) || 0 === count( $retries ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$retries = array_filter( $retries, function ( $retry ) {
					return 'failed' === $retry->get_status();
				} );

				$retries = count( $retries );
				$value   = (int) $rule_data['data'];

				switch ( $rule_data['rule'] ) {
					case '==':
						$result = $retries === $value;
						break;
					case '!=':
						$result = $retries !== $value;
						break;
					case '>':
						$result = $retries > $value;
						break;
					case '<':
						$result = $retries < $value;
						break;
					case '>=':
						$result = $retries >= $value;
						break;
					case '<=':
						$result = $retries <= $value;
						break;
					default:
						$result = false;
						break;
				}

				return $this->return_is_match( $result, $rule_data );
			}

			/** v2 Methods: END */

			public function get_condition_input_type() {
				return 'Text';
			}

			public function is_match( $rule_data ) {
				/** @var WC_Subscription $subscription */
				$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );

				$order_id = $subscription->get_last_order();
				if ( 0 === absint( $order_id ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				/** @var WCS_Retry[] $retries */
				$retries = WCS_Retry_Manager::store()->get_retries_for_order( $order_id );

				if ( ! is_array( $retries ) || 0 === count( $retries ) ) {
					return $this->return_is_match( false, $rule_data );
				}

				$retries = array_filter( $retries, function ( $retry ) {
					return 'failed' === $retry->get_status();
				} );

				$retries = count( $retries );
				$value   = (int) $rule_data['condition'];

				switch ( $rule_data['operator'] ) {
					case '==':
						$result = $retries === $value;
						break;
					case '!=':
						$result = $retries !== $value;
						break;
					case '>':
						$result = $retries > $value;
						break;
					case '<':
						$result = $retries < $value;
						break;
					case '>=':
						$result = $retries >= $value;
						break;
					case '<=':
						$result = $retries <= $value;
						break;
					default:
						$result = false;
						break;
				}

				return $this->return_is_match( $result, $rule_data );
			}

			public function ui_view() {
				esc_html_e( 'Subscription Failed Attempt', 'wp-marketing-automations-pro' );
				?>
				<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

				<%= ops[operator] %>
				<%= condition %>
				<?php
			}

			public function get_possible_rule_operators() {
				return array(
					'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
					'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
					'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
					'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
					'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
					'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
				);
			}
		}
	}

	class BWFAN_Rule_Subscription_Note_Text_Match extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_note_text_match' );
		}

		public function conditions_view() {
			$condition_input_type = $this->get_condition_input_type();
			$values               = $this->get_possible_rule_values();
			$value_args           = array(
				'input'       => $condition_input_type,
				'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition]',
				'choices'     => $values,
				'placeholder' => __( 'Enter Few Characters...', 'wp-marketing-automations-pro' ),
			);

			bwfan_Input_Builder::create_input_field( $value_args );
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription_note = isset( $automation_data['global']['wcs_save_note_type']['comment_content'] ) ? $automation_data['global']['wcs_save_note_type']['comment_content'] : '';

			return $this->return_is_match( BWFAN_Common::validate_string( $subscription_note, $rule_data['rule'], $rule_data['data'] ), $rule_data );
		}

		public function get_possible_rule_values() {
			return null;
		}

		public function is_match( $rule_data ) {
			$bwfan_rule_data   = BWFAN_Core()->rules->getRulesData();
			$subscription_note = isset( $bwfan_rule_data['wcs_subscription_note'] ) ? $bwfan_rule_data['wcs_subscription_note'] : '';

			return $this->return_is_match( BWFAN_Common::validate_string( $subscription_note, $rule_data['operator'], $rule_data['condition'] ), $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscriptions\'s notes text', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'contains'     => __( 'contains', 'wp-marketing-automations-pro' ),
				'not_contains' => __( 'does not contain', 'wp-marketing-automations-pro' ),
				'is'           => __( 'matches exactly', 'wp-marketing-automations-pro' ),
				'starts_with'  => __( 'starts with', 'wp-marketing-automations-pro' ),
				'ends_with'    => __( 'ends with', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}
	}

	class BWFAN_Rule_Subscription_Coupon_Text_Match extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_coupon_text_match' );
		}

		public function conditions_view() {
			$condition_input_type = $this->get_condition_input_type();
			$values               = $this->get_possible_rule_values();
			$value_args           = array(
				'input'       => $condition_input_type,
				'name'        => 'bwfan_rule[<%= groupId %>][<%= ruleId %>][condition]',
				'choices'     => $values,
				'placeholder' => __( 'Enter Few Characters...', 'wp-marketing-automations-pro' ),
			);

			bwfan_Input_Builder::create_input_field( $value_args );
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$subscription_id = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : '';

			$subscription = wcs_get_subscription( $subscription_id );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}
			if ( version_compare( WC()->version, 3.7, '>=' ) ) {
				$used_coupons = $subscription->get_coupon_codes();
			} else {
				$used_coupons = $subscription->get_used_coupons();
			}

			return $this->return_is_match( BWFAN_Common::validate_string_multi( $used_coupons, $rule_data['rule'], $rule_data['data'] ), $rule_data );
		}

		public function get_possible_rule_values() {
			return null;
		}

		public function is_match( $rule_data ) {
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}
			if ( version_compare( WC()->version, 3.7, '>=' ) ) {
				$used_coupons = $subscription->get_coupon_codes();
			} else {
				$used_coupons = $subscription->get_used_coupons();
			}

			return $this->return_is_match( BWFAN_Common::validate_string_multi( $used_coupons, $rule_data['operator'], $rule_data['condition'] ), $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscriptions\'s coupon text', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'contains'    => __( 'any contains', 'wp-marketing-automations-pro' ),
				'is'          => __( 'any matches exactly', 'wp-marketing-automations-pro' ),
				'starts_with' => __( 'starts with', 'wp-marketing-automations-pro' ),
				'ends_with'   => __( 'ends with', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}
	}

	class BWFAN_Rule_Subscription_Coupons extends BWFAN_Dynamic_Option_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_coupons' );
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
			global $wpdb;
			$subscription_id = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : '';
			$subscription    = wcs_get_subscription( $subscription_id );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}

			if ( version_compare( WC()->version, 3.7, '>=' ) ) {
				$used_coupons = $subscription->get_coupon_codes();
			} else {
				$used_coupons = $subscription->get_used_coupons();
			}

			if ( empty( $used_coupons ) ) {
				if ( 'all' === $rule_data['rule'] || 'any' === $rule_data['rule'] ) {
					$res = false;
				} else {
					$res = true;
				}

				return $this->return_is_match( $res, $rule_data );
			}

			$used_coupons_ids = [];
			foreach ( $used_coupons as $coupon ) {
				$used_coupons_ids[] = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $coupon ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			$selected_coupon = array_map( function ( $coupon ) {
				return $coupon['key'];
			}, $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case 'all':
					if ( is_array( $selected_coupon ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $selected_coupon, $used_coupons_ids ) ) === count( $selected_coupon );
					}
					break;
				case 'any':
					if ( is_array( $selected_coupon ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $selected_coupon, $used_coupons_ids ) ) >= 1;
					}
					break;

				case 'none':
					if ( is_array( $selected_coupon ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $selected_coupon, $used_coupons_ids ) ) === 0;
					}
					break;

				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_condition_values_nice_names( $values ) {
			$return = [];
			if ( count( $values ) > 0 ) {
				foreach ( $values as $coupon_id ) {
					$return[ $coupon_id ] = get_the_title( $coupon_id );
				}
			}

			return $return;
		}

		public function get_search_type_name() {
			return 'coupon_rule';
		}

		public function get_search_results( $term = '', $v2 = false ) {
			$args  = array(
				'post_type'      => 'shop_coupon',
				'posts_per_page' => 10,
				'paged'          => 1,
				's'              => $term,
				'meta_query'     => [
					[
						'key'     => 'discount_type',
						'value'   =>  [ 'recurring_fee', 'recurring_percent', 'sign_up_fee', 'sign_up_fee_percent', 'renewal_fee', 'renewal_percent', 'renewal_cart', 'initial_cart' ],
						'compare' => 'IN'
					]
				],
			);
			$posts = get_posts( $args );

			$data = [];

			if ( $posts && is_array( $posts ) && count( $posts ) > 0 ) {
				foreach ( $posts as $post ) :
					$post_id = (string) $post->ID;
					if ( $v2 ) {
						$data[ $post_id ] = $post->post_title;
					} else {
						$data[] = array(
							'id'   => $post_id,
							'text' => $post->post_title,
						);
					}
				endforeach;
			}
			if ( $v2 ) {
				return $data;
			}
			wp_send_json( array(
				'results' => $data,
			) );
		}

		public function is_match( $rule_data ) {
			global $wpdb;
			$type         = $rule_data['operator'];
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}

			if ( version_compare( WC()->version, 3.7, '>=' ) ) {
				$used_coupons = $subscription->get_coupon_codes();
			} else {
				$used_coupons = $subscription->get_used_coupons();
			}

			if ( empty( $used_coupons ) ) {
				if ( 'all' === $type || 'any' === $type ) {
					$res = false;
				} else {
					$res = true;
				}

				return $this->return_is_match( $res, $rule_data );
			}

			$used_coupons_ids = [];
			foreach ( $used_coupons as $coupon ) {
				$used_coupons_ids[] = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1;", $coupon ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}

			switch ( $type ) {
				case 'all':
					if ( is_array( $rule_data['condition'] ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $rule_data['condition'], $used_coupons_ids ) ) === count( $rule_data['condition'] );
					}
					break;
				case 'any':
					if ( is_array( $rule_data['condition'] ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $rule_data['condition'], $used_coupons_ids ) ) >= 1;
					}
					break;

				case 'none':
					if ( is_array( $rule_data['condition'] ) && is_array( $used_coupons_ids ) ) {
						$result = count( array_intersect( $rule_data['condition'], $used_coupons_ids ) ) === 0;
					}
					break;

				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscriptions\'s coupons ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %> <% var chosen = []; %>
			<% _.each(condition, function( value, key ){ %>
			<% chosen.push(uiData[value]); %>

			<% }); %>
			<%= chosen.join("/ ") %>
			<?php
		}

		public function get_possible_rule_operators() {
			$operators = array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);

			return $operators;
		}


	}

	class BWFAN_Rule_Subscription_Payment_Count extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'subscription_payment_count' );
			$this->description = '';
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}
			global $wpdb;
			$subscription_id = isset( $automation_data['global']['wc_subscription_id'] ) ? $automation_data['global']['wc_subscription_id'] : '';
			$subscription    = wcs_get_subscription( $subscription_id );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}

			$payment_count = is_callable( [ $subscription, 'get_payment_count' ] ) ? $subscription->get_payment_count() : $subscription->get_completed_payment_count();
			$payment_count = absint( $payment_count );
			$value         = absint( $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $payment_count === $value;
					break;
				case '!=':
					$result = $payment_count !== $value;
					break;
				case '>':
					$result = $payment_count > $value;
					break;
				case '<':
					$result = $payment_count < $value;
					break;
				case '>=':
					$result = $payment_count >= $value;
					break;
				case '<=':
					$result = $payment_count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match( $rule_data ) {
			$subscription = BWFAN_Core()->rules->getRulesData( 'wc_subscription' );

			if ( ! $subscription instanceof WC_Subscription ) {
				return $this->return_is_match( false, $rule_data );
			}

			$payment_count = is_callable( [ $subscription, 'get_payment_count' ] ) ? $subscription->get_payment_count() : $subscription->get_completed_payment_count();
			$payment_count = absint( $payment_count );
			$value         = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $payment_count === $value;
					break;
				case '!=':
					$result = $payment_count !== $value;
					break;
				case '>':
					$result = $payment_count > $value;
					break;
				case '<':
					$result = $payment_count < $value;
					break;
				case '>=':
					$result = $payment_count >= $value;
					break;
				case '<=':
					$result = $payment_count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Subscription Payment Count ', 'wp-marketing-automations-pro' );
			?>
			<% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

			<%= ops[operator] %>
			<%= condition %>
			<?php
		}

		public function get_possible_rule_operators() {
			return array(
				'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
				'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
				'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
				'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
				'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
				'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
			);
		}
	}
}
