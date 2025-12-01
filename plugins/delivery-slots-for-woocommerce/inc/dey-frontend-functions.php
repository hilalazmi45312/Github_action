<?php

/**
 * Front end functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'dey-template-functions.php';

if ( ! function_exists( 'dey_cart_having_delivery_slots_product' ) ) {

	/**
	 * Cart Having the delivery slots product.
	 *
	 * @return bool
	 */
	function dey_cart_having_delivery_slots_product() {
		// Return if the product page delivery slots is not enabled.
		if ( ! dey_is_product_delivery() ) {
			return false;
		}

		// Return false if the cart is not initialize.
		if ( ! is_object( WC()->cart ) ) {
			return false;
		}

		// Return false if the cart is empty.
		$cart_contents = WC()->cart->get_cart();
		if ( ! dey_check_is_array( $cart_contents ) ) {
			return false;
		}

		foreach ( $cart_contents as $key => $value ) {
			if ( ! isset( $value['dey_delivery_slots'] ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

}

if ( ! function_exists( 'dey_get_product_price_to_display' ) ) {

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
	 *
	 * @return bool/String
	 */
	function dey_get_product_price_to_display( $product, $price, $qty = 1 ) {

		if ( empty( $price ) ) {
			return 0;
		}

		$args = array(
			'qty'   => 1,
			'price' => $price,
		);

		return wc_get_price_to_display( $product, $args );
	}

}

if ( ! function_exists( 'dey_get_cart_price_to_display' ) ) {

	/**
	 * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_cart' setting.
	 *
	 * @return bool/String
	 */
	function dey_get_cart_price_to_display( $price, $tax_class = '' ) {

		if ( empty( $price ) ) {
			return 0;
		}

		// Return false if the cart is not initialize.
		if ( ! is_object( WC()->cart ) ) {
			return $price;
		}

		// If the tax is disabled.
		// if the tax is not valid for the cusotmer.
		if ( ! wc_tax_enabled() || WC()->cart->get_customer()->get_is_vat_exempt() ) {
			return $price;
		}

		$taxes       = WC_Tax::calc_tax( $price, WC_Tax::get_rates( $tax_class, WC()->cart->get_customer() ), false );
		$taxes_total = array_sum( $taxes );

		return $price + $taxes_total;
	}

}

if ( ! function_exists( 'dey_get_wc_cart_subtotal' ) ) {

	/**
	 * Get the WC cart subtotal.
	 *
	 * @return string/float
	 */
	function dey_get_wc_cart_subtotal() {
		if ( ! is_object( WC()->cart ) ) {
			return 0;
		}

		if ( method_exists( WC()->cart, 'get_subtotal' ) ) {
			$subtotal = ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) ? WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() : WC()->cart->get_subtotal();
		} else {
			$subtotal = ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) ? WC()->cart->subtotal + WC()->cart->subtotal_tax : WC()->cart->subtotal;
		}

		return $subtotal;
	}

}

if ( ! function_exists( 'dey_get_wc_cart_total' ) ) {

	/**
	 * Get the WC cart total.
	 *
	 * @since 3.2.0
	 * @return string/float
	 */
	function dey_get_wc_cart_total() {
		if ( ! is_object( WC()->cart ) ) {
			return 0;
		}

		if ( method_exists( WC()->cart, 'get_cart_contents_total' ) ) {
			$total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax() + WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax() + WC()->cart->get_fee_total() + WC()->cart->get_fee_tax();
		} else {
			$total = WC()->cart->cart_contents_total + WC()->cart->cart_contents_tax + WC()->cart->shipping_total + WC()->cart->shipping_tax + WC()->cart->fee_total + WC()->cart->fee_tax;
		}

		return $total;
	}

}

if ( ! function_exists( 'dey_get_cart_item_count' ) ) {

	/**
	 * Get the cart item count from the cart.
	 *
	 * @since 4.0.0
	 * @return int
	 */
	function dey_get_cart_item_count() {
		return is_object( WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;
	}
}

if ( ! function_exists( 'dey_get_order_tip_session_data' ) ) {

	/**
	 * Get the order tip session data.
	 *
	 * @return string/float
	 */
	function dey_get_order_tip_session_data() {
		/**
		 * This hook is used to alter the order tip session data.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_session_data', DEY_Cart_Session_Handler::get_order_tip_session_data() );
	}

}

if ( ! function_exists( 'dey_number_field_step_value' ) ) {

	/**
	 * Get the number field step value.
	 *
	 * @return string/int
	 */
	function dey_number_field_step_value() {
		$decimals   = wc_get_price_decimals();
		$step_value = 1;

		if ( $decimals ) {
			$num_value = 1;
			for ( $i = 1; $i <= $decimals; $i++ ) {
				$num_value = $num_value * 10;
			}

			$step_value = 1 / $num_value;
		}
		/**
		 * This hook is used to alter the number field step value.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_number_field_step_value', $step_value );
	}

}

if ( ! function_exists( 'dey_get_chosen_shipping_method_id' ) ) {

	/**
	 * Get the chosen shipping method ID.
	 *
	 * @return string/float
	 */
	function dey_get_chosen_shipping_method_id() {
		$shipping_method_ids = wc_get_chosen_shipping_method_ids();
		if ( ! dey_check_is_array( $shipping_method_ids ) ) {
			return false;
		}

		$shipping_method_id = reset( $shipping_method_ids );
		/**
		 * This hook is used to alter the chosen shipping method ID.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_chosen_shipping_method_id', $shipping_method_id );
	}

}

if ( ! function_exists( 'dey_get_chosen_billing_country_code' ) ) {

    /**
     * Get the chosen billing country code from customer.
     *
	 * @since 4.5.0
     * @return string|false  
     */
    function dey_get_chosen_billing_country_code() {
		
		return is_object( WC()->customer ) ? apply_filters( 'dey_customer_billing_country_code', WC()->customer->get_billing_country() ) : false;
    }

}

if ( ! function_exists( 'dey_is_local_pickup_shipping_method_chosen' ) ) {

	/**
	 * Is local pickup shipping method chosen?.
	 *
	 * @return bool
	 */
	function dey_is_local_pickup_shipping_method_chosen() {
		$shipping_method_id = dey_get_chosen_shipping_method_id();
		if ( 'local_pickup' !== $shipping_method_id ) {
			return false;
		}

		return true;
	}

}

if ( ! function_exists( 'dey_get_pickup_location_options' ) ) {

	/**
	 * Get the pickup location options.
	 *
	 * @return array
	 */
	function dey_get_pickup_location_options() {
		$pickup_location_ids = dey_get_pickup_location_ids();
		if ( ! dey_check_is_array( $pickup_location_ids ) ) {
			return array();
		}

		$options = array( '' => dey_get_pickup_location_default_option_label() );
		foreach ( $pickup_location_ids as $pickup_location_id ) {
			$pickup_location = dey_get_pickup_location( $pickup_location_id );
			if ( ! $pickup_location->exists() || ! DEY_Order_Pickup_Location_Validator::is_valid( $pickup_location ) ) {
				continue;
			}

			$options[ $pickup_location_id ] = $pickup_location->get_name();
		}

		return $options;
	}

}

if ( ! function_exists( 'dey_cart_contains_only_virtual_products' ) ) {

	/**
	 * Is cart contains only virtual products?
	 *
	 * @since 2.4.0
	 * @return bool
	 */
	function dey_cart_contains_only_virtual_products() {
		// Return if a cart object is not initialized.
		if ( ! is_object( WC()->cart ) ) {
			return false;
		}

		// Return empty array if the cart is empty.
		$cart_contents = WC()->cart->get_cart();
		if ( ! dey_check_is_array( $cart_contents ) ) {
			return false;
		}

		$bool = true;
		foreach ( $cart_contents as $cart_content ) {
			// Don't consider if the product is a product delivery.
			if ( isset( $cart_content['dey_delivery_slots'] ) ) {
				continue;
			}

			if ( $cart_content['data']->is_virtual() ) {
				continue;
			}

			$bool = false;
		}

		/**
		 * This hook used to alter the cart contains only virtual products.
		 *
		 * @since 2.4
		 */
		return apply_filters( 'dey_cart_contains_only_virtual_products', $bool );
	}

}

if ( ! function_exists( 'dey_is_cart_contains_product_scheduler_only' ) ) {

	/**
	 * Get if cart contains product order scheduler only?
	 *
	 * @since 3.5.0
	 * @return bool
	 */
	function dey_is_cart_contains_product_scheduler_only() {
		// Return if a cart object is not initialized.
		if ( ! is_object( WC()->cart ) ) {
			return false;
		}

		// Return empty array if the cart is empty.
		$cart_contents = WC()->cart->get_cart();
		if ( ! dey_check_is_array( $cart_contents ) ) {
			return false;
		}

		$bool = true;
		foreach ( $cart_contents as $cart_content ) {
			if ( isset( $cart_content['dey_delivery_slots'] ) || isset( $cart_content['dey_product_pickup_slots'] ) ) {
				continue;
			}

			$bool = false;
		}

		/**
		 * This hook used to alter the cart contains product order scheduler only.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_is_cart_contains_product_scheduler_only', $bool );
	}

}

if ( ! function_exists( 'dey_is_order_scheduler_type' ) ) {

	/**
	 * Check order scheduler types enabled.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	function dey_is_order_scheduler_type() {
		$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
		if ( ! is_object( $scheduler_rule ) ) {
			return false;
		}

		/**
		* This hook is used to validate the order scheduler type.
		*
		* @since 4.0.0
		*/
		return apply_filters( 'dey_is_order_scheduler_type', $scheduler_rule->is_order_scheduler() );
	}
}

if ( ! function_exists( 'dey_is_order_delivery' ) ) {

	/**
	 * Check order delivery type enabled.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	function dey_is_order_delivery() {
		$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
		if ( ! is_object( $scheduler_rule ) ) {
			return false;
		}

		/**
		* This hook is used to alter the order delivery enable.
		*
		* @since 3.9.0
		*/
		return apply_filters( 'dey_order_delivery_enable', ! $scheduler_rule->is_order_local_pickup() );
	}

}

if ( ! function_exists( 'dey_is_order_local_pickup' ) ) {

	/**
	 * Check order local pickup type enabled.
	 *
	 * @since 3.0.0
	 * @return bool
	 */
	function dey_is_order_local_pickup() {
		$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
		if ( ! is_object( $scheduler_rule ) ) {
			return false;
		}

		/**
		* This hook is used to alter the order local pickup enable.
		*
		* @since 3.9.0
		*/
		return apply_filters( 'dey_order_local_pickup_enable', ! $scheduler_rule->is_order_delivery() );
	}

}

if ( ! function_exists( 'dey_get_product_pickup_location_options' ) ) {

	/**
	 * Get the product pickup location options.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @return array
	 */
	function dey_get_product_pickup_location_options( $product_id ) {
		if ( ! $product_id ) {
			return array();
		}

		if ( '1' === dey_get_product_pickup_location_selection_type( $product_id ) ) {
			return dey_get_pickup_location_options();
		}

		$options          = array( '' => dey_get_product_pickup_location_field_label() );
		$pickup_locations = dey_get_product_pickup_locations( $product_id );
		foreach ( $pickup_locations as $pickup_location_id => $pickup_location_values ) {
			if ( ! dey_check_is_array( $pickup_location_values ) || ! isset( $pickup_location_values['name'] ) ) {
				continue;
			}

			$options[ $pickup_location_id ] = $pickup_location_values['name'];
		}

		return $options;
	}

}

if ( ! function_exists( 'dey_get_product_pickup_location_to_display' ) ) {

	/**
	 * Get product pickup location to display.
	 *
	 * @since 3.5.0
	 * @param int $product_id Product ID.
	 * @param int $location_id Location ID.
	 * @return string
	 */
	function dey_get_product_pickup_location_to_display( $product_id, $location_id ) {
		if ( ! $product_id || ! $location_id ) {
			return;
		}

		// Global level pickup locations.
		if ( '1' === dey_get_product_pickup_location_selection_type( $product_id ) ) {
			$pickup_location = dey_get_pickup_location( $location_id );
			if ( ! is_object( $pickup_location ) || ! $pickup_location->exists() ) {
				return;
			}

			return $pickup_location->get_name();
		}

		// Product level pickup locations.
		$product_pickup_locations = dey_get_product_pickup_locations( $product_id );
		if ( ! isset( $product_pickup_locations[ $location_id ] ) || ! dey_check_is_array( $product_pickup_locations[ $location_id ] ) ) {
			return;
		}

		return $product_pickup_locations[ $location_id ]['name'];
	}

}

if ( ! function_exists( 'dey_get_delivery_pickup_scheduler_html' ) ) {

	/**
	 * Get the delivery and pickup scheduler HTML
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_delivery_pickup_scheduler_html() {
		ob_start();
		DEY_Frontend::render_order_slots_fields();
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

}

if ( ! function_exists( 'dey_get_cart_order_tip_html' ) ) {

	/**
	 * Get the cart order tip HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_cart_order_tip_html() {
		ob_start();
		DEY_Frontend::render_cart_order_tip_fields();
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

}

if ( ! function_exists( 'dey_get_checkout_order_tip_html' ) ) {

	/**
	 * Get the checkout order tip HTML.
	 *
	 * @since 3.7.0
	 * @return HTML
	 */
	function dey_get_checkout_order_tip_html() {
		ob_start();
		DEY_Frontend::render_checkout_order_tip_fields();
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

}

if ( ! function_exists( 'dey_add_wc_notice' ) ) {

	/**
	 * Add a WC notice.
	 *
	 * @since 3.7.0
	 * @param string $message
	 * @param string $notice_type
	 * @param array  $data
	 */
	function dey_add_wc_notice( $message, $notice_type = 'success', $data = array() ) {
		if ( dey_is_block_cart() || dey_is_block_checkout() || wc_has_notice( $message ) ) {
			return;
		}

		wc_add_notice( $message, $notice_type, $data );
	}

}

if ( ! function_exists( 'can_render_order_tip_in_cart' ) ) {

	/**
	 * Can render order tip in the cart?
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	function can_render_order_tip_in_cart() {
		// Check if the cart order tip enabled.
		if ( 'no' === get_option( 'dey_order_tip_cart_enabled' ) ) {
			return false;
		}

		/**
		 * This hook is used to validate the cart page order tip to display.
		 *
		 * @since 1.0
		 */
		if ( ! apply_filters( 'dey_is_valid_cart_order_tip', true ) ) {
			return false;
		}

		// Return if the cart contains only virtual products.
		if ( ! dey_order_allow_virtual_products_delivery() ) {
			return false;
		}

		/**
		 * This hook is used to validate the order tip to display.
		 *
		 * @since 1.0
		 */
		if ( ! apply_filters( 'dey_is_valid_order_tip', true ) ) {
			return false;
		}

		return true;
	}

}

if ( ! function_exists( 'can_render_order_tip_in_checkout' ) ) {

	/**
	 * Can render order tip in the checkout?
	 *
	 * @since 3.7.0
	 * @return boolean
	 */
	function can_render_order_tip_in_checkout() {
		// Check if the checkout order tip enabled.
		if ( 'no' === get_option( 'dey_order_tip_checkout_enabled' ) ) {
			return false;
		}

		/**
		 * This hook is used to validate the checkout page order tip to display.
		 *
		 * @since 1.0
		 */
		if ( ! apply_filters( 'dey_is_valid_checkout_order_tip', true ) ) {
			return false;
		}

		// Return if the cart contains only virtual products.
		if ( ! dey_order_allow_virtual_products_delivery() ) {
			return false;
		}

		/**
		 * This hook is used to validate the order tip to display.
		 *
		 * @since 1.0
		 */
		if ( ! apply_filters( 'dey_is_valid_order_tip', true ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'dey_can_display_order_calendar_color_info' ) ) {

	/**
	 * Can display order calendar color info?
	 *
	 * @since 3.9.0
	 * @return bool
	 */
	function dey_can_display_order_calendar_color_info() {
		return 'yes' === get_option( 'dey_advanced_order_calendar_date_color_info', 'no' );
	}
}

if ( ! function_exists( 'dey_get_chosen_shipping_method' ) ) {

	/**
	 * Get the chosen shipping method ID.
	 *
	 * @since 3.9.1
	 * @return int
	 */
	function dey_get_chosen_shipping_method() {
		$chosen_shipping_method = WC()->session->get( 'chosen_shipping_methods' );

		return ! empty( $chosen_shipping_method ) ? filter_var( reset( $chosen_shipping_method ), FILTER_SANITIZE_NUMBER_INT ) : '';
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_by_shipping_method' ) ) {

	/**
	 * Get the scheduler rule by chosen shipping method.
	 *
	 * @since 4.0.0
	 * @param bool $force Whether force to check the scheduler rule or not.
	 * @static object $dey_scheduler_rule Scheduler rule object.
	 * @return array
	 */
	function dey_get_scheduler_rule_by_shipping_method( $force = false ) {
		static $dey_scheduler_rule;
		if ( ! $force && isset( $dey_scheduler_rule ) ) {
			return $dey_scheduler_rule;
		}

		$chosen_shipping_method_id = dey_get_chosen_shipping_method();
		$scheduler_rule_ids        = dey_get_scheduler_rule_ids();
		if ( ! dey_check_is_array( $scheduler_rule_ids ) ) {
			return false;
		}

		foreach ( $scheduler_rule_ids as $scheduler_rule_id ) {			
			$scheduler_rule = dey_get_scheduler_rule( $scheduler_rule_id );
			if ( ! $scheduler_rule->exists() ) {
				continue;
			}

			// Check the shipping methods.
			if ( dey_check_is_array( $scheduler_rule->get_shipping_methods() ) && ! in_array( $chosen_shipping_method_id, $scheduler_rule->get_shipping_methods() ) ) {
				continue;
			}

			if ( ! DEY_Scheduler_Rule_Validator::is_valid( $scheduler_rule ) ) {
				continue;
			}

			$dey_scheduler_rule = $scheduler_rule;

			return $dey_scheduler_rule;
		}

		return false;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_id_by_shipping_method' ) ) {

	/**
	 * Get the scheduler rule id by chosen shipping method.
	 *
	 * @since 4.0.0
	 * @return int|false
	 */
	function dey_get_scheduler_rule_id_by_shipping_method() {
		$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();

		return is_object( $scheduler_rule ) ? $scheduler_rule->get_id() : '';
	}
}

if ( ! function_exists( 'dey_get_current_user_country' ) ) {

	/**
	 * Get the current user country.
	 *
	 * @since 4.0.0
	 * @return string
	 */
	function dey_get_current_user_country() {
		return is_object( WC()->customer ) ? WC()->customer->get_billing_country() : '';
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_handler' ) ) {
	/**
	 * Get the order local pickup handler object.
	 *
	 * @since 4.0.0
	 * @return object
	 */
	function dey_get_order_local_pickup_handler() {
		$pickup_location_id = dey_get_selected_order_scheduler_data_from_session( 'order_pickup_location' );
		$pickup_location    = dey_get_pickup_location( $pickup_location_id );
		$scheduler_rule     = dey_get_scheduler_rule_by_shipping_method();
		if ( $pickup_location->exists() && '2' === $pickup_location->get_pickup_mode() ) {
			$order_local_pickup_handler = new DEY_Pickup_Location_Order_Local_Pickup_Handler( $pickup_location );
			return $order_local_pickup_handler;
		} elseif ( is_object( $scheduler_rule ) ) {
			$order_local_pickup_handler = new DEY_Scheduler_Rule_Order_Local_Pickup_Handler( $scheduler_rule );
			return $order_local_pickup_handler;
		}

		return false;
	}
}

if ( ! function_exists( 'dey_get_selected_order_scheduler_data_from_session' ) ) {

	/**
	 * Get selected order local pickup date from session.
	 *
	 * @since 4.0.0
	 * @param string $key Order scheduler data key.
	 * @param mixed  $default Default value.
	 * @return string
	 */
	function dey_get_selected_order_scheduler_data_from_session( $key, $default = '' ) {
		$order_scheduler_data = '';
		switch ( $key ) {
			case 'order_scheduler_type':
				$order_scheduler_type = DEY_Cart_Session_Handler::get( 'order_scheduler_type', $default );
				$order_scheduler_data = ! empty( $order_scheduler_type ) ? $order_scheduler_type : dey_get_default_order_scheduler_type();
				break;

			default:
				$order_scheduler_data = DEY_Cart_Session_Handler::get( $key, $default );
				break;
		}

		return $order_scheduler_data;
	}
}
