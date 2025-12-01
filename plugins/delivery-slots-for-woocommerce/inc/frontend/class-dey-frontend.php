<?php
/**
 * Handles the front end.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Frontend' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Frontend {

		/**
		 * Class Initialization.
		 * */
		public static function init() {
			// Define the hooks.
			add_action( 'wp_head', array( __CLASS__, 'define_hooks' ) );
			// Render the delivery slots for the product type.
			$single_product_priority = get_option( 'dey_advanced_product_delivery_date_display_priority', 10 );
			add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_product_type_delivery_slots' ), $single_product_priority );
			// Alter the array of data for a variation. Used in the add to cart form.
			add_action( 'woocommerce_available_variation', array( __CLASS__, 'render_delivery_slots_variable_product' ), 10, 3 );
			// Validate the delivery slots post data.
			add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate_postdata' ), 10, 2 );
			// Display the order delivery details.
			add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'display_order_delivery_details' ) );
			// Add the custom column in my orders table.
			add_filter( 'woocommerce_my_account_my_orders_columns', array( __CLASS__, 'add_my_orders_custom_columns' ), 10, 1 );
			// Render the delivery date column content in my orders table.
			add_action( 'woocommerce_my_account_my_orders_column_dey-delivery-date', array( __CLASS__, 'render_my_orders_delivery_date_column' ) );
			// Render the order delivery fields.
			add_action( 'dey_handle_order_scheduler_fields', array( __CLASS__, 'render_order_delivery_fields' ), 10 );
			// Render the order local pickup fields.
			add_action( 'dey_handle_order_scheduler_fields', array( __CLASS__, 'render_order_local_pickup_fields' ), 20 );
			// Render the product order delivery fields.
			add_action( 'dey_handle_product_scheduler_fields', array( __CLASS__, 'render_product_delivery_fields' ), 10, 1 );
			// Render the product order local pickup fields.
			add_action( 'dey_handle_product_scheduler_fields', array( __CLASS__, 'render_product_local_pickup_fields' ), 20, 1 );
			// Maybe enqueue the product scheduler scripts.
			add_action( 'woocommerce_before_add_to_cart_form', array( __CLASS__, 'maybe_enqueue_product_scheduler_scripts' ), 10 );
		}

		/**
		 * Define the order delivery fields hook.
		 *
		 * @return void
		 * */
		public static function define_hooks() {
			// Hook for the checkout order delivery fields.
			$checkout_location = self::get_checkout_current_location();
			if ( dey_check_is_array( $checkout_location ) ) {
				add_action( $checkout_location['hook'], array( __CLASS__, 'render_order_slots_fields' ), get_option( 'dey_advanced_display_position_priority', 10 ) );
			}

			// Hook for the cart order tip fields.
			$order_tip_cart_location = self::get_order_tip_cart_current_location();
			if ( dey_check_is_array( $order_tip_cart_location ) ) {
				add_action( $order_tip_cart_location['hook'], array( __CLASS__, 'render_cart_order_tip_fields' ), get_option( 'dey_order_tip_cart_display_position_priority', 10 ) );
			}

			// Hook for the checkout order tip fields.
			$order_tip_checkout_location = self::get_order_tip_checkout_current_location();
			if ( dey_check_is_array( $order_tip_checkout_location ) ) {
				add_action( $order_tip_checkout_location['hook'], array( __CLASS__, 'render_checkout_order_tip_fields' ), get_option( 'dey_order_tip_checkout_display_position_priority', 10 ) );
			}
		}

		/**
		 * Get the order delivery checkout current location.
		 *
		 * @return array.
		 */
		public static function get_checkout_current_location() {
			$checkout_location = get_option( 'dey_advanced_display_position' );

			/**
			 * This hook is used to alter the order delivery checkout locations.
			 *
			 * @since 1.0.0
			 */
			$location_details = apply_filters(
				'dey_order_delivery_checkout_locations',
				array(
					'1' => array( 'hook' => 'woocommerce_after_checkout_billing_form' ),
					'2' => array( 'hook' => 'woocommerce_checkout_before_customer_details' ),
					'3' => array( 'hook' => 'woocommerce_before_order_notes' ),
					'4' => array( 'hook' => 'woocommerce_after_order_notes' ),
				)
			);

			$location_detail = isset( $location_details[ $checkout_location ] ) ? $location_details[ $checkout_location ] : reset( $location_details );

			/**
			 * This hook is used to alter the order delivery checkout current location.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_delivery_checkout_current_location', $location_detail, $checkout_location, $location_details );
		}

		/**
		 * Get the order tip cart current location.
		 *
		 * @return array.
		 */
		public static function get_order_tip_cart_current_location() {
			$cart_location = get_option( 'dey_order_tip_cart_display_position' );

			/**
			 * This hook is used to alter the order tip cart locations.
			 *
			 * @since 1.0
			 */
			$location_details = apply_filters(
				'dey_order_tip_cart_locations',
				array(
					'1' => array( 'hook' => 'woocommerce_before_cart_table' ),
					'2' => array( 'hook' => 'woocommerce_after_cart_table' ),
				)
			);

			$location_detail = isset( $location_details[ $cart_location ] ) ? $location_details[ $cart_location ] : reset( $location_details );

			/**
			 * This hook is used to alter the order tip cart current location.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_tip_cart_current_location', $location_detail, $cart_location, $location_details );
		}

		/**
		 * Get the order tip checkout current location.
		 *
		 * @return array.
		 */
		public static function get_order_tip_checkout_current_location() {
			$checkout_location = get_option( 'dey_order_tip_checkout_display_position' );
			/**
			 * This hook is used to alter the order tip checkout current locations.
			 *
			 * @since 1.0
			 */
			$location_details = apply_filters(
				'dey_order_tip_checkout_locations',
				array(
					'1' => array( 'hook' => 'woocommerce_after_checkout_billing_form' ),
					'2' => array( 'hook' => 'woocommerce_checkout_before_customer_details' ),
					'3' => array( 'hook' => 'woocommerce_before_order_notes' ),
					'4' => array( 'hook' => 'woocommerce_after_order_notes' ),
				)
			);

			$location_detail = isset( $location_details[ $checkout_location ] ) ? $location_details[ $checkout_location ] : reset( $location_details );
			/**
			 * This hook is used to alter the order tip checkout current location.
			 *
			 * @since 1.0
			 */
			return apply_filters( 'dey_order_tip_checkout_current_location', $location_detail, $checkout_location, $location_details );
		}

		/**
		 * Render the delivery slots fields.
		 *
		 * @return void.
		 */
		public static function render_order_slots_fields() {
			// Return if the cart contains only virtual products.
			if ( ! dey_order_allow_virtual_products_delivery() ) {
				return;
			}

			// Return if the cart contains only product order scheduler products.
			if ( dey_is_cart_contains_product_scheduler_only() ) {
				return;
			}

			/**
			 * This hook is used to validate order scheduler fields.
			 *
			 * @since 4.0.0
			 */
			if ( apply_filters( 'dey_validate_order_scheduler_fields', false ) ) {
				return;
			}

			// Enqueue the order delivery scripts.
			DEY_Order_Delivery_Handler::enqueue_default_scripts();
			// Enqueue the order local pickup scripts.
			DEY_Scheduler_Rule_Order_Local_Pickup_Handler::enqueue_default_scripts();

			dey_get_template( 'order/order-scheduler.php' );
		}

		/**
		 * Render the delivery slots for the product page.
		 *
		 * @return void
		 * */
		public static function render_product_type_delivery_slots() {
			global $product;

			// Return null, if the product variable is not object.
			if ( ! is_object( $product ) ) {
				return;
			}

			// Return If the product is a variable product type.
			if ( $product->is_type( 'variable' ) ) {
				return;
			}

			// Render the product delivery slots.
			self::render_product_delivery_slots( $product, $product->get_id() );
		}

		/**
		 * Render the delivery slots for the variable product.
		 *
		 * @return array
		 * */
		public static function render_delivery_slots_variable_product( $array, $class, $variation ) {
			// Return if the array is empty.
			if ( ! dey_check_is_array( $array ) ) {
				return $array;
			}

			// Return null, if the variation variable is not object.
			if ( ! is_object( $variation ) ) {
				return $array;
			}

			// Get the product delivery slots html.
			$delivery_slots_html = self::render_product_delivery_slots( $variation, $variation->get_parent_id(), $variation->get_id(), false );

			if ( ! $delivery_slots_html ) {
				return $array;
			}

			$array['dey_delivery_slots'] = $delivery_slots_html;

			return $array;
		}

		/**
		 * Render the product delivery slots.
		 *
		 * @return mixed
		 * */
		public static function render_product_delivery_slots( $product, $product_id, $variation_id = false, $echo = true ) {
			// Return null, if the product variable is not object.
			if ( ! is_object( $product ) ) {
				return null;
			}

			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_valid_product_scheduler() ) {
				return null;
			}

			// Return if the product delivery slot is disabled.
			if ( '2' != get_post_meta( $product_id, 'dey_delivery_slot_type', true ) ) {
				return null;
			}

			// Return if the product delivery not allowed the virtual products.
			if ( ! dey_product_delivery_allow_virtual_products( $product ) ) {
				return null;
			}

			$product_id = ! empty( $variation_id ) ? $variation_id : $product_id;
			if ( ! $echo ) {
				return dey_get_template_html( 'product/product-scheduler.php', array( 'product_id' => $product_id ) );
			}

			// Display the delivery slots.
			dey_get_template( 'product/product-scheduler.php', array( 'product_id' => $product_id ) );
		}

		/**
		 * Render the cart order tip fields.
		 *
		 * @return void.
		 */
		public static function render_cart_order_tip_fields() {
			// Return if the order tip does not valid to render in the cart.
			if ( ! can_render_order_tip_in_cart() ) {
				return;
			}

			/**
			 * This hook is used to alter the order tip wrapper file name.
			 *
			 * @since 1.0
			 */
			$file_name = apply_filters( 'dey_order_tip_wrapper_file_name_cart', 'cart-order-tip.php' );

			dey_get_template( $file_name );
		}

		/**
		 * Render the checkout order tip fields.
		 *
		 * @return void.
		 */
		public static function render_checkout_order_tip_fields() {
			// Return if the order tip does not valid to render in the checkout.
			if ( ! can_render_order_tip_in_checkout() ) {
				return;
			}

			/**
			 * This hook is used to alter the order tip wrapper file name.
			 *
			 * @since 1.0
			 */
			$file_name = apply_filters( 'dey_order_tip_wrapper_file_name_checkout', 'checkout-order-tip.php' );

			dey_get_template( $file_name );
		}

		/**
		 * Validate the order delivery fields.
		 *
		 * @return void.
		 */
		public static function validate_postdata( $posted_data, $errors ) {
			/**
			 * This hook is used to restrict the checkout fields validation.
			 *
			 * @since 3.9.1
			 */
			if ( ! apply_filters( 'dey_validate_checkout_fields', true, $errors, $posted_data ) ) {
				return;
			}

			$errors = DEY_Checkout_Fields_Validator::validate_post_data( $_REQUEST );
			// Return if no errors exists.
			if ( ! is_object( $errors ) || ! $errors->has_errors() ) {
				return;
			}

			foreach ( $errors->get_error_messages() as $error_message ) {
				dey_add_wc_notice( $error_message, 'error' );
			}
		}

		/**
		 * Display the order delivery details.
		 *
		 * @return void.
		 */
		public static function display_order_delivery_details( $order ) {
			// Return if the object is not order object.
			if ( ! is_object( $order ) ) {
				return;
			}

			// Return if the delivery details not exists in order.
			$delivery_details = dey_get_order_delivery_details( $order );
			if ( ! dey_check_is_array( $delivery_details ) ) {
				return;
			}

			dey_get_template( 'order-delivery-details.php', array( 'delivery_details' => $delivery_details ) );
		}

		/**
		 * Add the custom column in my orders table.
		 *
		 * @since 2.3.0
		 * @param array $columns
		 * @return array
		 */
		public static function add_my_orders_custom_columns( $columns ) {
			// Return if the delivery details column hide.
			if ( '1' != get_option( 'dey_advanced_order_table_delivery_details_enabled' ) ) {
				return $columns;
			}

			$custom_columns = array( 'dey-delivery-date' => dey_get_myaccount_order_delivery_date_label() );

			return dey_customize_array_position( $columns, 'order-total', $custom_columns );
		}

		/**
		 * Render the delivery date column content in my orders table.
		 *
		 * @param object $order
		 */
		public static function render_my_orders_delivery_date_column( $order ) {
			// Return if the object is not order object.
			if ( ! is_object( $order ) ) {
				return;
			}

			// Return if the delivery details not exists in order.
			$delivery_details = dey_get_order_delivery_details( $order, true );
			if ( ! dey_check_is_array( $delivery_details ) ) {
				return;
			}

			dey_get_template( 'order-delivery-details.php', array( 'delivery_details' => $delivery_details ) );
		}

		/**
		 * Render the order delivery fields.
		 *
		 * @since 3.0.0
		 * @param object $scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public static function render_order_delivery_fields( $scheduler_rule ) {
			if ( ! dey_is_order_delivery() ) {
				return;
			}

			if ( ! is_object( $scheduler_rule ) ) {
				return;
			}

			/**
			 * This hook is used to validate order delivery fields.
			 *
			 * @since 3.8.0
			 */
			if ( ! apply_filters( 'dey_validate_order_delivery_fields', true, $scheduler_rule ) ) {
				return;
			}

			$order_delivery_handler = new DEY_Order_Delivery_Handler( $scheduler_rule );
			$order_delivery_handler->render();
		}

		/**
		 * Render the order local pickup fields.
		 *
		 * @since 3.0.0
		 * @param object $scheduler_rule Scheduler rule object.
		 * @return void
		 */
		public static function render_order_local_pickup_fields( $scheduler_rule ) {
			if ( ! dey_is_order_local_pickup() ) {
				return;
			}

			if ( ! is_object( $scheduler_rule ) ) {
				return;
			}

			/**
			 * This hook is used to validate order local pickup fields.
			 *
			 * @since 3.8.0
			 */
			if ( ! apply_filters( 'dey_validate_order_local_pickup_fields', true, $scheduler_rule ) ) {
				return;
			}

			$order_local_pickup_handler = dey_get_order_local_pickup_handler();
			if ( ! is_object( $order_local_pickup_handler ) ) {
				return;
			}

			$order_local_pickup_handler->render();
		}

		/**
		 * Render the product delivery fields.
		 *
		 * @since 3.0.0
		 * @param int $product_id Product ID.
		 * @return void
		 */
		public static function render_product_delivery_fields( $product_id ) {
			if ( ! dey_is_product_delivery() ) {
				return;
			}

			if ( ! $product_id ) {
				return;
			}

			$product = wc_get_product( $product_id );
			if ( ! is_object( $product ) ) {
				return;
			}

			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product_id ) && ! dey_is_product_delivery_type( $product_id ) ) {
				return;
			}

			DEY_Product_Delivery_Handler::init( $product )->render();
		}

		/**
		 * Render the product local pickup fields.
		 *
		 * @since 3.0.0
		 * @param int $product_id Product ID.
		 * @return void
		 */
		public static function render_product_local_pickup_fields( $product_id ) {
			if ( ! dey_is_product_local_pickup() ) {
				return;
			}

			if ( ! $product_id ) {
				return;
			}

			$product = wc_get_product( $product_id );
			if ( ! is_object( $product ) ) {
				return;
			}

			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product_id ) && ! dey_is_product_pickup_type( $product_id ) ) {
				return;
			}

			DEY_Product_Local_Pickup_Handler::init( $product )->render();
		}

		/**
		 * Maybe enqueue the product scheduler scripts.
		 *
		 * @since 3.9.1
		 * @global object $product Product object.
		 * @return void
		 */
		public static function maybe_enqueue_product_scheduler_scripts() {
			global $product;
			if ( ! is_object( $product ) || 'variable' !== $product->get_type() ) {
				return;
			}

			/**
			 * This hook is used to alter the variation threshold value.
			 *
			 * @since 3.9.1
			 */
			if ( apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product ) >= count( $product->get_children() ) ) {
				return;
			}

			if ( ! dey_is_valid_product_scheduler() ) {
				return;
			}

			// Product delivery scripts.
			if ( dey_is_product_delivery() && ( dey_is_user_selection_type( $product->get_id() ) || dey_is_product_delivery_type( $product->get_id() ) ) ) {
				DEY_Product_Delivery_Handler::enqueue_assets();
			}

			// Product local pickup scripts.
			if ( dey_is_product_local_pickup() && ( dey_is_user_selection_type( $product->get_id() ) || dey_is_product_pickup_type( $product->get_id() ) ) ) {
				DEY_Product_Local_Pickup_Handler::enqueue_assets();
			}
		}
	}

	DEY_Frontend::init();
}
