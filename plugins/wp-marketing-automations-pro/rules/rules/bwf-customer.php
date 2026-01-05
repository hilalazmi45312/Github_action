<?php

if ( class_exists( 'WooCommerce' ) ) {
	class BWFAN_Rule_Customer_Order_Count extends BWFAN_Rule_Base {

		public $supports = array( 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_order_count' );
		}

		/** v2 Methods: START */

		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			}

			$count = ( $customer instanceof WooFunnels_Customer ) ? absint( $customer->get_total_order_count() ) : 0;
			$value = absint( $rule_data['data'] );

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $count === $value;
					break;
				case '!=':
					$result = $count !== $value;
					break;
				case '>':
					$result = $count > $value;
					break;
				case '<':
					$result = $count < $value;
					break;
				case '>=':
					$result = $count >= $value;
					break;
				case '<=':
					$result = $count <= $value;
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
			/**
			 * @var Woofunnels_Customer $customer
			 */
			$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
			$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
			$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

			$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
			if ( ! empty( $contact_id ) && ! $customer instanceof WooFunnels_Customer ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = BWFAN_Core()->rules->getRulesData( 'email' );
				if ( ! is_email( $email ) ) {
					$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			}

			$count = $customer instanceof WooFunnels_Customer ? absint( $customer->get_total_order_count() ) : 0;
			$value = absint( $rule_data['condition'] );

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $count === $value;
					break;
				case '!=':
					$result = $count !== $value;
					break;
				case '>':
					$result = $count > $value;
					break;
				case '<':
					$result = $count < $value;
					break;
				case '>=':
					$result = $count >= $value;
					break;
				case '<=':
					$result = $count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Contact Orders Count', 'wp-marketing-automations-pro' );
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

	class BWFAN_Rule_Customer_Total_Spent extends BWFAN_Rule_Base {
		public $supports = array( 'order' );

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_total_spent' );
		}

		public function get_condition_input_type() {
			return 'Text';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			}

			$count = ( $customer instanceof WooFunnels_Customer ) ? (float) $customer->get_total_order_value() : 0;
			$value = (float) $rule_data['data'];

			switch ( $rule_data['rule'] ) {
				case '==':
					$result = $count === $value;
					break;
				case '!=':
					$result = $count !== $value;
					break;
				case '>':
					$result = $count > $value;
					break;
				case '<':
					$result = $count < $value;
					break;
				case '>=':
					$result = $count >= $value;
					break;
				case '<=':
					$result = $count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function is_match( $rule_data ) {
			/**
			 * @var Woofunnels_Customer $customer
			 */
			$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
			$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
			$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

			$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
			if ( ! empty( $contact_id ) && ! $customer instanceof WooFunnels_Customer ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = BWFAN_Core()->rules->getRulesData( 'email' );
				if ( ! is_email( $email ) ) {
					$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			}

			$count = $customer instanceof WooFunnels_Customer ? (float) $customer->get_total_order_value() : (float) 0;
			$value = (float) $rule_data['condition'];

			switch ( $rule_data['operator'] ) {
				case '==':
					$result = $count === $value;
					break;
				case '!=':
					$result = $count !== $value;
					break;
				case '>':
					$result = $count > $value;
					break;
				case '<':
					$result = $count < $value;
					break;
				case '>=':
					$result = $count >= $value;
					break;
				case '<=':
					$result = $count <= $value;
					break;
				default:
					$result = false;
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		public function ui_view() {
			esc_html_e( 'Contact Total Revenue', 'wp-marketing-automations-pro' );
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

	class BWFAN_Rule_Customer_Purchased_Products extends BWFAN_Rule_Products {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_purchased_products' );
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

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';

			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			/** Fetch order products */
			$order_products = [];

			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$order    = wc_get_order( $order_id );
			if ( ! empty( $order_id ) && $order instanceof WC_Order ) {
				$order_products = array_map( function ( $item ) {
					$item_data = $item->get_data();

					return ! empty( $item_data['variation_id'] ) ? $item_data['variation_id'] : $item_data['product_id'];
				}, $order->get_items() );
			}

			/** Selected product in condition */
			$selected_products = array_map( function ( $product ) {
				return absint( $product['key'] );
			}, $rule_data['data'] );

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
				if ( ! $customer instanceof WooFunnels_Customer && 'none' === $rule_data['rule'] ) {
					return $this->return_is_match( true, $rule_data );
				}

				if ( ! $customer instanceof WooFunnels_Customer && ! empty( $order_products ) ) {
					$result = $this->validate_set( $selected_products, array_values( $order_products ), $rule_data['rule'] );

					return $this->return_is_match( $result, $rule_data );
				}

				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $this->return_is_match( false, $rule_data );
				}
			}

			/** Fetch products from wc customer table */
			$products = $customer->get_purchased_products();
			$products = ! empty( $order_products ) ? array_unique( array_merge( $products, $order_products ) ) : $products;

			$result = $this->validate_set( $selected_products, $products, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function is_match( $rule_data ) {
			/**
			 * @var Woofunnels_Customer $customer
			 */
			$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
			$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
			$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );

			$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
			if ( ! empty( $contact_id ) && ! $customer instanceof WooFunnels_Customer ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = BWFAN_Core()->rules->getRulesData( 'email' );
				if ( ! is_email( $email ) ) {
					$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					return $this->return_is_match( false, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $this->return_is_match( false, $rule_data );
				}
			}

			$products      = $customer->get_purchased_products();
			$rule_products = array_map( 'absint', $rule_data['condition'] );
			$result        = $this->validate_set( $rule_products, $products, $rule_data['operator'] );

			return $this->return_is_match( $result, $rule_data );
		}

		public function validate_set( $products, $found_products, $operator ) {
			switch ( $operator ) {
				case 'any':
					$result = count( array_intersect( $products, $found_products ) ) > 0;
					break;
				case 'all':
					$result = count( array_intersect( $products, $found_products ) ) === count( $products );
					break;
				case 'none':
					$result = count( array_intersect( $products, $found_products ) ) === 0;
					break;
				default:
					$result = false;
					break;
			}

			return $result;
		}

		public function ui_view() {
			esc_html_e( 'Contact Purchased Products', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>');%>

            <%= ops[operator] %> <% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>

            <% }); %>
            <%= chosen.join(" | ") %>
			<?php
		}

	}

	class BWFAN_Rule_Customer_Purchased_Cat extends BWFAN_Rule_Term_Taxonomy {

		public $taxonomy_name = 'product_cat';

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_purchased_cat' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/** v2 Methods: END */

		public function get_term_ids( $automation_data = [] ) {

			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$customer   = '';
				$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
				if ( ! empty( $contact_id ) ) {
					$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
					$customer = new WooFunnels_Customer( $contact );
				}
				$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
				$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
				$email          = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
				$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				$order          = wc_get_order( $order_id );

			} else {

				/**
				 * @var Woofunnels_Customer $customer
				 */
				$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
				$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
				$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );
				$email          = BWFAN_Core()->rules->getRulesData( 'email' );
				$order          = BWFAN_Core()->rules->getRulesData( 'wc_order' );

			}

			/** Fetch order product's categories */
			$product_cats = [];
			if ( $order instanceof WC_Order ) {
				$order = wc_get_order( $automation_data['global']['order_id'] );
				foreach ( $order->get_items() as $item ) {
					$item_data = $item->get_data();
					$product   = wc_get_product( $item_data['product_id'] );
					if ( ! $product instanceof WC_Product ) {
						continue;
					}

					$product_cats = array_merge( $product_cats, $product->get_category_ids() );
				}
			}

			if ( ! $customer instanceof WooFunnels_Customer ) {
				if ( ! is_email( $email ) ) {
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					return array();
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $product_cats;
				}
			}

			/** Fetch from wc customer table */
			$category_ids = $customer->get_purchased_products_cats();

			return ! empty( $product_cats ) ? array_unique( array_merge( $category_ids, $product_cats ) ) : $category_ids;
		}

		public function get_possible_rule_operators() {
			return array(
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of ', 'wp-marketing-automations-pro' ),
			);
		}

		public function ui_view() {
			esc_html_e( 'Contact Purchased Products Category', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %><% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>

            <% }); %>
            <%= chosen.join("/") %>
			<?php
		}

	}

	class BWFAN_Rule_Customer_Purchased_Tags extends BWFAN_Rule_Term_Taxonomy {

		public $taxonomy_name = 'product_tag';

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_purchased_tags' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		/** v2 Methods: END */

		public function get_term_ids( $automation_data = [] ) {
			/**
			 * @var Woofunnels_Customer $customer
			 */
			$contact_id     = BWFAN_Core()->rules->getRulesData( 'contact_id' );
			$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
			$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
			$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );
			$email          = BWFAN_Core()->rules->getRulesData( 'email' );
			$order          = BWFAN_Core()->rules->getRulesData( 'wc_order' );

			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$customer   = '';
				$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
				if ( ! empty( $contact_id ) ) {
					$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
					$customer = new WooFunnels_Customer( $contact );
				}
				$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
				$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
				$email          = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
				$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				$order          = wc_get_order( $order_id );
			}

			/** Fetch order product's tags */
			$product_tags = [];
			if ( $order instanceof WC_Order ) {
				$order = wc_get_order( $automation_data['global']['order_id'] );
				foreach ( $order->get_items() as $item ) {
					$item_data = $item->get_data();
					$product   = wc_get_product( $item_data['product_id'] );
					if ( ! $product instanceof WC_Product ) {
						continue;
					}

					$product_tags = array_merge( $product_tags, $product->get_tag_ids() );
				}
			}

			if ( $customer instanceof WooFunnels_Customer && $customer->get_id() > 0 ) {
				$tag_ids = $customer->get_purchased_products_tags();

				return ! empty( $product_tags ) ? array_unique( array_merge( $tag_ids, $product_tags ) ) : $tag_ids;
			}

			if ( ! empty( $contact_id ) ) {
				$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				if ( $contact instanceof WooFunnels_Contact && $contact->get_id() > 0 ) {
					$customer = new WooFunnels_Customer( $contact );
					$tag_ids  = $customer->get_purchased_products_tags();

					return ! empty( $product_tags ) ? array_unique( array_merge( $tag_ids, $product_tags ) ) : $tag_ids;
				}
			}

			if ( ! is_email( $email ) && ! empty( $user_id ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : '';
			}

			/** get email from abandoned data */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) && bwfan_is_woocommerce_active() ) {
				$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
			}

			if ( ! is_email( $email ) ) {
				return array();
			}

			$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

			if ( ! $customer instanceof WooFunnels_Customer ) {
				return $product_tags;
			}

			return $customer->get_purchased_products_tags();
		}

		public function ui_view() {
			esc_html_e( 'Contact Purchased Products Tag', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

            <%= ops[operator] %><% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>

            <% }); %>
            <%= chosen.join("/") %>
			<?php
		}

	}

	class BWFAN_Rule_Customer_Country extends BWFAN_Rule_Country {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_country' );
		}

		public function get_objects_country( $automation_data = [] ) {

			if ( isset( $automation_data['global'] ) && is_array( $automation_data['global'] ) ) {
				$customer   = '';
				$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
				if ( ! empty( $contact_id ) ) {
					$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
					$customer = new WooFunnels_Customer( $contact );
				}
				$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
				$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
				$email          = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
				$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
				$order          = wc_get_order( $order_id );

			} else {

				/**
				 * @var Woofunnels_Customer $customer
				 */
				$contact_id     = BWFAN_Core()->rules->getRulesData( 'contact_id' );
				$customer       = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );
				$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
				$user_id        = BWFAN_Core()->rules->getRulesData( 'user_id' );
				$email          = BWFAN_Core()->rules->getRulesData( 'email' );
				$order          = BWFAN_Core()->rules->getRulesData( 'wc_order' );

			}

			$contact = $customer instanceof WooFunnels_Customer ? $customer->contact : false;

			if ( ! empty( $contact_id ) && ! $contact instanceof WooFunnels_Contact ) {
				$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
			}

			if ( ! is_email( $email ) && ! empty( $user_id ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : '';
			}

			/** get email from abandoned data */
			if ( ! is_email( $email ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! $contact instanceof WooFunnels_Contact ) {
				if ( ! is_email( $email ) ) {
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
				}

				if ( ! is_email( $email ) ) {
					return false;
				}

				$contact = new WooFunnels_Contact( '', $email );

				if ( ! $contact instanceof WooFunnels_Contact ) {
					return false;
				}
			}

			$country = $contact->get_country();
			if ( empty( $country ) ) {
				return false;
			}

			return array( $country );
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

	class BWFAN_Rule_Customer_Past_Purchased_Products extends BWFAN_Rule_Products {

		public function __construct() {
			$this->v2 = true;
			parent::__construct( 'customer_past_purchased_products' );
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

			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

			$order = wc_get_order( $order_id );

			$order_created_date = '';
			$email              = '';
			if ( $order instanceof WC_Order ) {
				$order_created_date = $order->get_date_created();
				$email              = $order->get_billing_email();
			}

			/** Get email from abandoned cart data */
			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			if ( ! is_email( $email ) && ! empty( $abandoned_data ) && is_array( $abandoned_data ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : '';
			}

			/** Get email from WooFunnels contact */
			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			if ( ! is_email( $email ) && ! empty( $contact_id ) ) {
				$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$email   = $contact->get_email();
			}

			/** Get email from the user id */
			$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			if ( ! is_email( $email ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$data              = $rule_data['data'];
			$selected_products = array_map( function ( $product ) {
				return absint( $product['key'] );
			}, $data );

			$current_time = ! empty( $order_created_date ) ? $order_created_date->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );

			/** Fetch products purchased before given time */
			$products = BWFAN_PRO_Common::get_customer_past_purchased_product( $current_time, $email );

			$result = $this->validate_set( $selected_products, $products, $rule_data['rule'] );

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function is_match( $rule_data ) {

			/** @var WC_Order $order */
			$order = BWFAN_Core()->rules->getRulesData( 'wc_order' );

			$order_created_date = '';
			$email              = '';
			if ( $order instanceof WC_Order ) {
				$order_created_date = $order->get_date_created();
				$email              = $order->get_billing_email();
			}

			/** Get email from abandoned cart data */
			$abandoned_data = BWFAN_Core()->rules->getRulesData( 'abandoned_data' );
			if ( ! is_email( $email ) && ! empty( $abandoned_data ) && is_array( $abandoned_data ) ) {
				$email = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				$email = BWFAN_Core()->rules->getRulesData( 'email' );
			}

			/** Get email from WooFunnels contact */
			$contact_id = BWFAN_Core()->rules->getRulesData( 'contact_id' );
			if ( ! is_email( $email ) && ! empty( $contact_id ) ) {
				$contact = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$email   = $contact->get_email();
			}

			/** Get email from the user id */
			$user_id = BWFAN_Core()->rules->getRulesData( 'user_id' );
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			if ( ! is_email( $email ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$rule_products = array_map( 'absint', $rule_data['condition'] );
			$current_time  = ! empty( $order_created_date ) ? $order_created_date->date( 'Y-m-d H:i:s' ) : current_time( 'mysql' );

			/** Fetch products purchased before given time */
			$products = BWFAN_PRO_Common::get_customer_past_purchased_product( $current_time, $email );

			$result = $this->validate_set( $rule_products, $products, $rule_data['operator'] );

			return $this->return_is_match( $result, $rule_data );
		}

		/**
		 * Perform rule validations
		 *
		 * @param $products
		 * @param $found_products
		 * @param $operator
		 *
		 * @return bool
		 */
		public function validate_set( $products, $found_products, $operator ) {
			switch ( $operator ) {
				case 'any':
					$result = count( array_intersect( $products, $found_products ) ) > 0;
					break;
				case 'all':
					$result = count( array_intersect( $products, $found_products ) ) === count( $products );
					break;
				case 'none':
					$result = count( array_intersect( $products, $found_products ) ) === 0;
					break;
				default:
					$result = false;
					break;
			}

			return $result;
		}

		public function ui_view() {
			esc_html_e( 'Past Purchased Products', 'wp-marketing-automations-pro' );
			?>
            <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>');%>

            <%= ops[operator] %> <% var chosen = []; %>
            <% _.each(condition, function( value, key ){ %>
            <% chosen.push(uiData[value]); %>

            <% }); %>
            <%= chosen.join(" | ") %>
			<?php
		}

	}

	class BWFAN_Rule_Customer_Has_Purchased extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_has_purchased' );
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
				'no'  => __( 'No', 'wp-marketing-automations-pro' ),
			);
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

			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			if ( intval( $order_id ) > 0 ) {
				$order = wc_get_order( $order_id );
				if ( $order instanceof WC_Order ) {
					/** Current order found */
					$is_paid = $order->has_status( wc_get_is_paid_statuses() );
					if ( true === $is_paid ) {
						return $this->return_is_match( ( 'yes' === $rule_data['data'] ), $rule_data );
					}
				}
			}

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', intval( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === intval( $customer->get_id() ) ) {
				$customer = self::get_customer_obj( $automation_data );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === intval( $customer->get_id() ) ) {
				return $this->return_is_match( ( 'no' === $rule_data['data'] ), $rule_data );
			}

			$count  = $customer->get_total_order_count() ? intval( $customer->get_total_order_count() ) : 0;
			$result = ( ( intval( $count ) > 0 && 'yes' === $rule_data['data'] ) || ( 0 === intval( $count ) && 'no ' === $rule_data['data'] ) ) ? true : false;

			return $this->return_is_match( $result, $rule_data );
		}

		public static function get_customer_obj( $automation_data ) {
			$email   = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
			$user_id = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			/** get email from the user id */
			if ( ! empty( $user_id ) && ! is_email( $email ) ) {
				$user_data = get_userdata( $user_id );
				$email     = $user_data instanceof WP_User ? $user_data->user_email : false;
			}

			/** get email from abandoned data */
			if ( ! is_email( $email ) ) {
				$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
				$email          = isset( $abandoned_data['email'] ) ? $abandoned_data['email'] : false;
			}

			if ( ! is_email( $email ) ) {
				return false;
			}

			$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			if ( ! $customer instanceof WooFunnels_Customer ) {
				return false;
			}

			return $customer;
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}
	}

	class BWFAN_Rule_Customer_Has_Used_Coupons extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_has_used_coupons' );
		}

		public function get_possible_rule_values() {
			return array(
				'yes' => __( 'Yes', 'wp-marketing-automations-pro' ),
				'no'  => __( 'No', 'wp-marketing-automations-pro' ),
			);
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

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';

			$order_id = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$order    = wc_get_order( $order_id );

			/** If coupon found in order */
			if ( ! empty( $order_id ) && $order instanceof WC_Order && ! empty( $order->get_coupon_codes() ) ) {
				return $this->return_is_match( ( 'yes' === $rule_data['data'] ), $rule_data );
			}

			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					$result = ( 'no' === $rule_data['data'] ) ? true : $result;

					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
				if ( ! $customer instanceof WooFunnels_Customer ) {
					$result = ( 'no' === $rule_data['data'] ) ? true : $result;

					return $this->return_is_match( $result, $rule_data );
				}
			}

			$count = $customer->get_used_coupons() ? count( $customer->get_used_coupons() ) : 0;
			if ( ! empty( $count ) && $count > 0 ) {
				$result = true;
			}

			return $this->return_is_match( ( 'yes' === $rule_data['data'] ) ? $result : ! $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return null;
		}
	}

	class BWFAN_Rule_Customer_First_Order_Date extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_first_order_date' );
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

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			$first_order_dt = '';

			if ( $customer instanceof WooFunnels_Customer && $customer->get_id() > 0 ) {
				$first_order_dt = $customer->get_f_order_date() ? date( 'Y-m-d', strtotime( $customer->get_f_order_date() ) ) : '';
			}

			if ( empty( $first_order_dt ) && ! empty( $order_id ) ) {
				$order = wc_get_order( $order_id );

				$first_order_dt = $order instanceof WC_Order ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
			}

			if ( empty( $first_order_dt ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;

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
					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $this->return_is_match( $result, $rule_data );
				}
			}

			$first_order_dt = ( empty( $first_order_dt ) && $customer->get_f_order_date() ) ? date( 'Y-m-d', strtotime( $customer->get_f_order_date() ) ) : '';
			if ( empty( $first_order_dt ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$type           = $rule_data['rule'];
			$first_order_dt = strtotime( $first_order_dt );

			if ( 'between' === $type && is_array( $rule_data['data'] ) ) {
				$from   = strtotime( $rule_data['data']['from'] );
				$to     = strtotime( $rule_data['data']['to'] );
				$result = ( ( $first_order_dt >= $from ) && ( $first_order_dt <= $to ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$filter_value = strtotime( $rule_data['data'] );
			switch ( $type ) {
				case 'before':
					$result = ( $first_order_dt < $filter_value );
					break;
				case 'after':
					$result = ( $first_order_dt > $filter_value );
					break;
				case 'is':
					$result = ( $first_order_dt === $filter_value );
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

	class BWFAN_Rule_Customer_Last_Order_Date extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_last_order_date' );
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

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

			$contact_id    = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$last_order_dt = '';
			if ( ! empty( $order_id ) ) {
				$order         = wc_get_order( $order_id );
				$last_order_dt = $order instanceof WC_Order ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
			}

			$customer = '';
			if ( empty( $last_order_dt ) && ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( empty( $last_order_dt ) && ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;

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
					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $this->return_is_match( $result, $rule_data );
				}
			}

			$last_order_dt = ( empty( $last_order_dt ) && $customer->get_l_order_date() ) ? date( 'Y-m-d', strtotime( $customer->get_l_order_date() ) ) : $last_order_dt;
			if ( empty( $last_order_dt ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$type          = $rule_data['rule'];
			$last_order_dt = strtotime( $last_order_dt );

			if ( 'between' === $type && is_array( $rule_data['data'] ) ) {
				$from   = strtotime( $rule_data['data']['from'] );
				$to     = strtotime( $rule_data['data']['to'] );
				$result = ( ( $last_order_dt >= $from ) && ( $last_order_dt <= $to ) );

				return $this->return_is_match( $result, $rule_data );
			}

			$filter_value = strtotime( $rule_data['data'] );

			switch ( $type ) {
				case 'before':
					$result = ( $last_order_dt < $filter_value );
					break;
				case 'after':
					$result = ( $last_order_dt > $filter_value );
					break;
				case 'is':
					$result = ( $last_order_dt === $filter_value );
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

	class BWFAN_Rule_Customer_Last_Order_Days extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_last_order_days' );
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

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;

			$contact_id    = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$last_order_dt = '';
			if ( ! empty( $order_id ) ) {
				$order         = wc_get_order( $order_id );
				$last_order_dt = $order instanceof WC_Order ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
			}

			$customer = '';
			if ( empty( $last_order_dt ) && ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( empty( $last_order_dt ) && ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;

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
					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
				if ( ! $customer instanceof WooFunnels_Customer ) {
					return $this->return_is_match( $result, $rule_data );
				}
			}

			$last_order_dt = ( empty( $last_order_dt ) && $customer->get_l_order_date() ) ? date( 'Y-m-d', strtotime( $customer->get_l_order_date() ) ) : $last_order_dt;//excluding time
			if ( empty( $last_order_dt ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$type          = $rule_data['rule'];
			$last_order_dt = strtotime( $last_order_dt );

			$result = $this->validate_matches_duration_set( $last_order_dt, $rule_data, $type );

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

	class BWFAN_Rule_Customer_Average_Order_Value extends BWFAN_Rule_Base {
		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_average_order_value' );
		}

		/** v2 Methods: START */
		public function get_rule_type() {
			return 'Number';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
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
					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );
			}

			$aov = ( $customer instanceof WooFunnels_Customer ) ? $customer->get_aov() : 0;

			$type = $rule_data['rule'];
			$data = $rule_data['data'];

			switch ( $type ) {
				case '<':
					$result = ( $aov < $data );
					break;
				case '>':
					$result = ( $aov > $data );
					break;
				default:
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'<' => __( 'Less Than', 'wp-marketing-automations-pro' ),
				'>' => __( 'More Than', 'wp-marketing-automations-pro' ),
			);
		}

	}

	class BWFAN_Rule_Customer_Used_Coupons extends BWFAN_Rule_Base {

		public function __construct() {
			$this->v1 = false;
			$this->v2 = true;
			parent::__construct( 'customer_used_coupons' );
		}

		/** v2 Methods: START */

		public function get_options( $term = '' ) {
			return $this->get_search_results( $term, true );
		}

		public function get_rule_type() {
			return 'Search';
		}

		public function is_match_v2( $automation_data, $rule_data ) {
			$result = false;
			if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
				return $this->return_is_match( $result, $rule_data );
			}

			$abandoned_data = isset( $automation_data['global']['abandoned_data'] ) ? $automation_data['global']['abandoned_data'] : [];
			$user_id        = isset( $automation_data['global']['user_id'] ) ? $automation_data['global']['user_id'] : 0;
			$order_id       = isset( $automation_data['global']['order_id'] ) ? $automation_data['global']['order_id'] : 0;
			$order          = wc_get_order( $order_id );

			$contact_id = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
			$customer   = '';
			if ( ! empty( $contact_id ) ) {
				$contact  = new WooFunnels_Contact( '', '', '', absint( $contact_id ) );
				$customer = new WooFunnels_Customer( $contact );
			}

			$order_coupons = $order instanceof WC_Order ? $order->get_coupon_codes() : [];
			if ( ! $customer instanceof WooFunnels_Customer || 0 === absint( $customer->get_id() ) ) {
				$email = isset( $automation_data['global']['email'] ) ? $automation_data['global']['email'] : 0;
				if ( ! is_email( $email ) ) {
					$email = $order instanceof WC_Order ? $order->get_billing_email() : false;
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
					$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

					return $this->return_is_match( $result, $rule_data );
				}

				$customer = BWFAN_PRO_Common::get_bwf_customer_by_email( $email );

				if ( 'none' === $rule_data['rule'] && ! $customer instanceof WooFunnels_Customer && empty( $order_id ) ) {
					return $this->return_is_match( true, $rule_data );
				}
			}

			$selected_coupons = array_map( function ( $coupon ) {
				return strtolower( $coupon['label'] );
			}, $rule_data['data'] );
			$type             = $rule_data['rule'];

			/** If type contains and not_contains - selected coupons can be multiple checking directly using query */
			if ( 'contains' === $type || 'not_contains' === $type ) {
				/** Check if selected coupon text matched with order coupons */
				if ( ! empty( $order_coupons ) ) {
					$is_contains = array_filter( $order_coupons, function ( $order_coupon ) use ( $selected_coupons ) {

						return array_filter( $selected_coupons, fn( $selected_coupon ) => strpos( $order_coupon, $selected_coupon ) !== false );
					} );
					if ( ! empty( $is_contains ) ) {
						return $this->return_is_match( ! ( 'not_contains' === $type ), $rule_data );
					}
				}
				$result = $this->coupon_contains_text( $contact_id, $selected_coupons, $type, $order_coupons );
				$result = ( 'not_contains' === $type ) && empty( $result ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}
			$used_coupons = $customer instanceof WooFunnels_Customer && $customer->get_used_coupons() ? $customer->get_used_coupons() : [];
			$used_coupons = array_merge( $used_coupons, $order_coupons );

			/** Fetch used coupons from order */

			if ( empty( $used_coupons ) ) {
				$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

				return $this->return_is_match( $result, $rule_data );
			}

			$used_coupons = array_map( 'strtolower', $used_coupons );

			switch ( $type ) {
				case 'any':
					$result = count( array_intersect( $selected_coupons, $used_coupons ) ) > 0;
					break;
				case 'none':
					$result = count( array_intersect( $selected_coupons, $used_coupons ) ) === 0;
					break;
				default:
					break;
			}

			return $this->return_is_match( $result, $rule_data );
		}

		/** v2 Methods: END */

		public function get_possible_rule_operators() {
			return array(
				'any'          => __( 'Matches any of', 'wp-marketing-automations-pro' ),
				'none'         => __( 'Matches none of', 'wp-marketing-automations-pro' ),
				'contains'     => __( 'contains', 'wp-marketing-automations-pro' ),
				'not_contains' => __( 'does not contains', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_search_results( $term, $v2 = false ) {
			$array = BWFAN_Common::get_coupon( $term );

			if ( $v2 ) {
				$return = array();
				foreach ( $array['results'] as $coupon ) {
					$return[ $coupon['id'] ] = $coupon['text'];
				}

				return $return;
			}
		}

		/**
		 * Check used coupons contains or not contains text
		 *
		 * @param $cid
		 * @param $selected_coupons
		 * @param $type
		 * @param $current_used_coupons
		 *
		 * @return bool
		 */
		private function coupon_contains_text( $cid, $selected_coupons, $type, $current_used_coupons ) {
			global $wpdb;
			$operator     = 'contains' === $type ? 'LIKE' : 'NOT LIKE';
			$search_query = array_map( function ( $coupon ) use ( $operator ) {
				return '`used_coupons` ' . $operator . ' "%' . $coupon . '%"';
			}, $selected_coupons );

			$search_query = implode( ' OR ', $search_query );

			$query = "SELECT id FROM {$wpdb->prefix}bwf_wc_customers WHERE cid = $cid AND ($search_query)";

			return ! empty ( $wpdb->get_var( $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	class BWFAN_Rule_Customer_Reviewed_Product extends BWFAN_Rule_Products {

		public function __construct() {
			$this->v2 = true;
			$this->v1 = false;
			parent::__construct( 'customer_reviewed_product' );
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
			$type           = $rule_data['rule'];
			$saved_products = array_column( $rule_data['data'], 'key' );
			$comment_id     = $automation_data['global']['comment_id'];

			if ( ! $comment_id ) {
				return $this->return_is_match( false, $rule_data );
			}

			$comment = get_comment( $comment_id );

			if ( ! $comment || ! is_a( $comment, 'WP_Comment' ) ) {
				return $this->return_is_match( false, $rule_data );
			}

			$commented_product_id = $comment->comment_post_ID;

			if ( ! $commented_product_id ) {
				return $this->return_is_match( false, $rule_data );
			}

			$product = wc_get_product( $commented_product_id );

			if ( ! $product instanceof WC_Product ) {
				return $this->return_is_match( false, $rule_data );
			}

			switch ( $type ) {
				case 'any':
					$result = count( array_intersect( $saved_products, [ $commented_product_id ] ) ) >= 1;
					break;
				case 'none':
					$result = count( array_intersect( $saved_products, [ $commented_product_id ] ) ) === 0;
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
				'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
				'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
			);
		}

		public function get_search_results( $term, $v2 = false ) {
			$this->set_product_types_arr();
			$array = BWFAN_Common::product_search( $term, true, true );

			if ( $v2 ) {
				$return = array();
				foreach ( $array as $product ) {
					$return[ $product['id'] ] = $product['text'];
				}

				return $return;
			}
		}
	}
}
