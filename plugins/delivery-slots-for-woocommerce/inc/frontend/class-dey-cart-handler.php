<?php
/**
 * Handles the Cart.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Cart_Handler' ) ) {

	/**
	 * Class.
	 * */
	class DEY_Cart_Handler {

		/**
		 * Class Initialization.
		 * */
		public static function init() {
			// Maybe restrict the product delivery slots add to cart.
			add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'restrict_woocommerce_add_to_cart' ), 12, 3 );
			// Add the item data to the product delivery slots.
			add_action( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_product_delivery_cart_item_data' ), 20, 4 );
			// Add the item data to the product pickup slots.
			add_action( 'woocommerce_add_cart_item_data', array( __CLASS__, 'add_product_pickup_cart_item_data' ), 30, 4 );
			// Gets cart item to display product delivery data in the cart.
			add_action( 'woocommerce_get_item_data', array( __CLASS__, 'display_product_delivery_custom_item_data' ), 10, 2 );
			// Gets cart item to display product pickup data in the cart.
			add_action( 'woocommerce_get_item_data', array( __CLASS__, 'display_product_pickup_custom_item_data' ), 20, 2 );
			// Alter product price based on product delivery slots selection.
			add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'set_product_delivery_price' ), 10, 3 );
			// Alter product price based on product pickup slots selection.
			add_filter( 'woocommerce_get_cart_item_from_session', array( __CLASS__, 'set_product_pickup_price' ), 10, 3 );
			// Add the custom fees.
			add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'custom_fees' ) );
			// May be add the order tip fee HTML.
			add_filter( 'woocommerce_cart_totals_fee_html', array( __CLASS__, 'maybe_add_order_tip_fee_html' ), 10, 2 );
			// Validate the cart items.
			add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'validate_cart_items' ), 1 );
			// Maybe alter the shipping methods.
			add_filter( 'woocommerce_shipping_packages', array( __CLASS__, 'maybe_alter_shipping_packages' ), 10, 1 );
			// Maybe unset the shipping methods.
			add_filter( 'woocommerce_package_rates', array( __CLASS__, 'maybe_unset_shipping_methods' ), 10, 1 );
		}

		/**
		 * Maybe alter the shipping methods.
		 *
		 * @since 4.0.0
		 * @param array $packages Packages.
		 * @return array
		 */
		public static function maybe_alter_shipping_packages( $packages ) {
			if ( ! self::can_restrict_shipping_methods() ) {
				return $packages;
			}

			foreach ( $packages as $key => $package ) {
				$packages[ $key ]['rates'] = self::maybe_unset_shipping_methods( $package['rates'] );
			}

			return $packages;
		}

		/**
		 * Maybe unset the shipping methods.
		 *
		 * @since 4.0.0
		 * @param array $available_shipping_methods Available shipping methods.
		 * @return array
		 */
		public static function maybe_unset_shipping_methods( $available_shipping_methods ) {
			if ( ! self::can_restrict_shipping_methods() ) {
				return $available_shipping_methods;
			}

			$shipping_methods = array();
			foreach ( $available_shipping_methods as $methods => $details ) {
				if ( intval( dey_get_chosen_shipping_method() ) === intval( $details->instance_id ) ) {
					$shipping_methods[ $methods ] = $details;
				}
			}

			return dey_check_is_array( $shipping_methods ) ? $shipping_methods : $available_shipping_methods;
		}

		/**
		 * Can restrict the shipping methods?
		 *
		 * @since 4.0.0
		 * @return bool Whether to restrict the shipping methods or not.
		 */
		public static function can_restrict_shipping_methods() {
			// Return if consider shipping methods.
			if ( '1' === get_option( 'dey_local_pickup_shipping_mode', '1' ) ) {
				return false;
			}

			// Return if order local pickup is not enabled or cart contains only product schedulers.
			if ( ! dey_is_order_local_pickup() || dey_is_cart_contains_product_scheduler_only() ) {
				return false;
			}

			if ( dey_is_order_scheduler_type() && 'order-local-pickup' !== dey_get_selected_order_scheduler_data_from_session( 'order_scheduler_type' ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Restrict the product delivery slots add to cart.
		 *
		 * @return bool
		 * */
		public static function restrict_woocommerce_add_to_cart( $bool, $product_id, $quantity ) {
			$product = wc_get_product( $product_id );
			if ( ! is_object( $product ) ) {
				return $bool;
			}

			if ( 'variation' === $product->get_type() ) {
				$product_id = $product->get_parent_id();
				$product    = wc_get_product( $product->get_parent_id() );
			}

			// Check if the product delivery or product local pickup is enabled.
			if ( ! dey_is_valid_product_scheduler() ) {
				return $bool;
			}

			// Return if the product order scheduler is enabled.
			if ( ! dey_is_product_scheduler_enabled( $product_id ) ) {
				return $bool;
			}

			// Validate the product delivery slots.
			$bool = self::validate_product_delivery_add_to_cart( $bool, $product );
			// Validate the product local pickup slots.
			$bool = self::validate_product_pickup_add_to_cart( $bool, $product );

			return $bool;
		}

		/**
		 * Validate the product delivery slots add to cart.
		 *
		 * @since 1.0.0
		 * @param bool   $bool Boolean.
		 * @param object $product Product object.
		 * @return bool
		 * */
		public static function validate_product_delivery_add_to_cart( $bool, $product ) {
			if ( ! dey_is_product_delivery() ) {
				return $bool;
			}

			// Return if the product delivery slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product->get_id() ) && ! dey_is_product_delivery_type( $product->get_id() ) ) {
				return $bool;
			}

			// Return if the delivery slot is expected delivery date.
			if ( '2' === get_post_meta( $product->get_id(), 'dey_delivery_slot_mode', true ) ) {
				return $bool;
			}

			// Return if the product delivery not allowed the virtual products.
			if ( ! dey_product_delivery_allow_virtual_products( $product ) ) {
				return $bool;
			}

			$product_scheduler = isset( $_REQUEST['dey_product_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_scheduler_type'] ) ) : '';
			if ( dey_is_user_selection_type( $product->get_id() ) && 'product-delivery' !== $product_scheduler ) {
				return $bool;
			}

			$delivery_date = isset( $_REQUEST['dey_delivery_date'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_delivery_date'] ) ) : '';
			$time_slot_id  = isset( $_REQUEST['dey_product_delivery_date_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_delivery_date_time_slots'] ) ) : '';
			$date_object   = DEY_Date_Time::get_date_time_object( $delivery_date );

			// Validate the delivery date.
			$calender_mandatory_field = get_post_meta( $product->get_id(), 'dey_delivery_calender_mandatory_field', true );
			if ( 'yes' === $calender_mandatory_field && empty( $delivery_date ) ) {
				dey_add_wc_notice( dey_get_product_delivery_date_mandatory_message(), 'error' );
				return false;
			}

			// Validate the delivery time slots.
			$time_mode                 = get_post_meta( $product->get_id(), 'dey_delivery_time_mode', true );
			$time_slot_mandatory_field = get_post_meta( $product->get_id(), 'dey_delivery_time_slot_mandatory_field', true );
			if ( '3' === $time_mode && 'yes' === $time_slot_mandatory_field && ( empty( $time_slot_id ) || 'none' === $time_slot_id ) ) {
				dey_add_wc_notice( dey_get_product_delivery_time_slot_mandatory_message(), 'error' );
				return false;
			}

			// Validate the selected date is valid.
			$product_delivery = DEY_Product_Delivery_Handler::init( $product->get_id() );
			$available_dates  = $product_delivery->get_available_dates();
			$formatted_date   = $date_object->format( 'Y-m-d' );

			if ( ! empty( $delivery_date ) && ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				dey_add_wc_notice( dey_get_product_delivery_date_incorrect_message(), 'error' );
				$bool = false;
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						dey_add_wc_notice( dey_get_product_delivery_date_booked_message(), 'error' );
						$bool = false;
						break;

					case 'hy':
						dey_add_wc_notice( dey_get_product_delivery_date_holiday_message(), 'error' );
						$bool = false;
						break;
				}
			}

			// validate the time slots.
			if ( ! empty( $delivery_date ) && ! empty( $time_slot_id ) && 'soon' != $time_slot_id ) {
				if ( ! $product_delivery->is_valid_time_slot( $time_slot_id, $delivery_date ) ) {
					dey_add_wc_notice( dey_get_product_delivery_time_slot_incorrect_message(), 'error' );
					$bool = false;
				}
			}

			return $bool;
		}

		/**
		 * Validate product local pickup add to cart.
		 *
		 * @since 3.5.0
		 * @param bool   $bool Boolean value.
		 * @param object $product Product object.
		 * @return bool
		 */
		public static function validate_product_pickup_add_to_cart( $bool, $product ) {
			if ( ! dey_is_product_local_pickup() ) {
				return $bool;
			}

			// Return if the product pickup slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product->get_id() ) && ! dey_is_product_pickup_type( $product->get_id() ) ) {
				return $bool;
			}

			$product_scheduler = isset( $_REQUEST['dey_product_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_scheduler_type'] ) ) : '';
			if ( dey_is_user_selection_type( $product->get_id() ) && 'product-local-pickup' !== $product_scheduler ) {
				return $bool;
			}

			$pickup_date  = isset( $_REQUEST['dey_product_pickup_date'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_pickup_date'] ) ) : '';
			$time_slot_id = isset( $_REQUEST['dey_product_pickup_date_time_slots'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_pickup_date_time_slots'] ) ) : '';
			$date_object  = DEY_Date_Time::get_date_time_object( $pickup_date );

			// Validate the pickup location.
			$pickup_location          = isset( $_REQUEST['dey_product_pickup_location'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_pickup_location'] ) ) : '';
			$location_mandatory_field = get_post_meta( $product->get_id(), 'dey_pickup_location_mandatory_field', true );
			if ( 'yes' === $location_mandatory_field && ! $pickup_location ) {
				dey_add_wc_notice( dey_get_product_pickup_location_mandatory_message(), 'error' );
				return false;
			}

			// Validate the pickup date.
			$calender_mandatory_field = get_post_meta( $product->get_id(), 'dey_pickup_calender_mandatory_field', true );
			if ( 'yes' === $calender_mandatory_field && empty( $pickup_date ) ) {
				dey_add_wc_notice( dey_get_product_pickup_date_mandatory_message(), 'error' );
				return false;
			}

			// Validate the pickup time slots.
			$time_mode                 = get_post_meta( $product->get_id(), 'dey_pickup_time_mode', true );
			$time_slot_mandatory_field = get_post_meta( $product->get_id(), 'dey_pickup_time_slot_mandatory_field', true );
			if ( '3' === $time_mode && 'yes' === $time_slot_mandatory_field && ( empty( $time_slot_id ) || 'none' === $time_slot_id ) ) {
				dey_add_wc_notice( dey_get_product_pickup_time_slot_mandatory_message(), 'error' );
				return false;
			}

			// Validate the selected date is valid.
			$product_pickup  = DEY_Product_Local_Pickup_Handler::init( $product->get_id() );
			$available_dates = $product_pickup->get_available_dates();
			$formatted_date  = $date_object->format( 'Y-m-d' );

			if ( ! empty( $pickup_date ) && ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				dey_add_wc_notice( dey_get_product_pickup_date_incorrect_message(), 'error' );
				$bool = false;
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						dey_add_wc_notice( dey_get_product_pickup_date_booked_message(), 'error' );
						$bool = false;
						break;

					case 'hy':
						dey_add_wc_notice( dey_get_product_pickup_date_holiday_message(), 'error' );
						$bool = false;
						break;
				}
			}

			// validate the time slots.
			if ( ! empty( $pickup_date ) && ! empty( $time_slot_id ) && 'soon' !== $time_slot_id ) {
				if ( ! $product_pickup->is_valid_time_slot( $time_slot_id, $pickup_date ) ) {
					dey_add_wc_notice( dey_get_product_pickup_time_slot_incorrect_message(), 'error' );
					$bool = false;
				}
			}

			return $bool;
		}

		/**
		 * Add product delivery data to the cart.
		 *
		 * @since 1.0.0
		 * @param array $cart_item_data cart item data.
		 * @param int   $product_id product ID.
		 * @param int   $variation_id variant ID.
		 * @param int   $quantity Quantity.
		 * @return array
		 */
		public static function add_product_delivery_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_product_delivery() ) {
				return $cart_item_data;
			}

			// Return if the product delivery slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product_id ) && ! dey_is_product_delivery_type( $product_id ) ) {
				return $cart_item_data;
			}

			$product_scheduler = isset( $_REQUEST['dey_product_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_scheduler_type'] ) ) : '';
			if ( dey_is_product_scheduler() && dey_is_user_selection_type( $product_id ) && 'product-delivery' !== $product_scheduler ) {
				return $cart_item_data;
			}

			/**
			 * This hook is used to validate the product delivery slot cart item data.
			 *
			 * @since 1.0.0
			 */
			if ( apply_filters( 'dey_validate_product_delivery_slots_cart_item_data', false, $cart_item_data, $product_id, $variation_id, $quantity ) ) {
				return $cart_item_data;
			}

			$custom_item_data = self::prepare_product_delivery_custom_item_data( $product_id, $variation_id, $quantity );
			if ( ! dey_check_is_array( $custom_item_data ) ) {
				return $cart_item_data;
			}

			// Prepare the delivery slot item data.
			$cart_item_data['dey_delivery_slots'] = $custom_item_data;

			return $cart_item_data;
		}

		/**
		 * Add product pickup data to the cart.
		 *
		 * @since 3.5.0
		 * @param array $cart_item_data Cart item data.
		 * @param int   $product_id Product ID.
		 * @param int   $variation_id Variant ID.
		 * @param int   $quantity Quantity.
		 * @return array
		 */
		public static function add_product_pickup_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
			// Return if the product page pickup slots is not enabled.
			if ( ! dey_is_product_local_pickup() ) {
				return $cart_item_data;
			}

			// Return if the product pickup slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $product_id ) && ! dey_is_product_pickup_type( $product_id ) ) {
				return $cart_item_data;
			}

			$product_scheduler = isset( $_REQUEST['dey_product_scheduler_type'] ) ? wc_clean( wp_unslash( $_REQUEST['dey_product_scheduler_type'] ) ) : '';
			if ( dey_is_product_scheduler() && dey_is_user_selection_type( $product_id ) && 'product-local-pickup' !== $product_scheduler ) {
				return $cart_item_data;
			}

			/**
			 * This hook is used to validate the product pickup slot cart item data.
			 *
			 * @since 1.0.0
			 */
			if ( apply_filters( 'dey_validate_product_pickup_slots_cart_item_data', false, $cart_item_data, $product_id, $variation_id, $quantity ) ) {
				return $cart_item_data;
			}

			$custom_item_data = self::prepare_product_pickup_custom_item_data( $product_id, $variation_id, $quantity );
			if ( ! dey_check_is_array( $custom_item_data ) ) {
				return $cart_item_data;
			}

			// Prepare the product pickup slot item data.
			$cart_item_data['dey_product_pickup_slots'] = $custom_item_data;

			return $cart_item_data;
		}

		/**
		 * Prepare the delivery slots cart item.
		 *
		 * @since 1.0.0
		 * @param int $product_id Product ID.
		 * @param int $variation_id Variation ID.
		 * @param int $quantity Quantity.
		 * @return array
		 */
		public static function prepare_product_delivery_custom_item_data( $product_id, $variation_id, $quantity ) {
			$price              = 0;
			$custom_item_data   = array();
			$time_slot_id       = '';
			$time_slot_from     = '';
			$time_slot_to       = '';
			$special_day_id     = '';
			$last_delivery_date = '';
			$price_details      = array();
			$product_id         = ! empty( $variation_id ) ? $variation_id : $product_id;
			$product            = wc_get_product( $product_id );

			$product_delivery = DEY_Product_Delivery_Handler::init( $product );
			$slot_mode        = $product_delivery->get_meta( 'dey_delivery_slot_mode' );
			$time_mode        = $product_delivery->get_meta( 'dey_delivery_time_mode' );

			switch ( $slot_mode ) {
				// Expected delivery date.
				case '2':
					$delivery_date      = $product_delivery->get_expected_first_date();
					$last_delivery_date = $product_delivery->get_expected_last_date();
					break;

				// Calender.
				default:
					$post_data = $_REQUEST;

					// Return if the delivery slot is not selected.
					if ( ! isset( $post_data['dey_delivery_date'] ) || empty( $post_data['dey_delivery_date'] ) ) {
						return $custom_item_data;
					}

					$delivery_date            = $post_data['dey_delivery_date'];
					$date_object              = DEY_Date_Time::get_date_time_object( $delivery_date );
					$price                    = $product_delivery->get_weekday_price( $date_object );
					$price_details['weekday'] = $product_delivery->get_weekday_price( $date_object );
					// Prepare the time slots.
					if ( isset( $post_data['dey_product_delivery_date_time_slots'] ) && ! empty( $post_data['dey_product_delivery_date_time_slots'] ) ) {
						$time_slot_id = $post_data['dey_product_delivery_date_time_slots'];
						$time_slots   = array_filter( (array) $product_delivery->get_meta( 'dey_delivery_time_slots' ) );
						if ( isset( $time_slots[ $time_slot_id ] ) ) {
							$time_slot_from             = $time_slots[ $time_slot_id ]['from_time'];
							$time_slot_to               = $time_slots[ $time_slot_id ]['to_time'];
							$price                     += floatval( $time_slots[ $time_slot_id ]['price'] );
							$price_details['time_slot'] = floatval( $time_slots[ $time_slot_id ]['price'] );
						}
					}

					// Prepare the special days.
					$special_days = $product_delivery->get_special_dates();
					if ( isset( $special_days[ $post_data['dey_delivery_date'] ] ) ) {
						$price                       += floatval( $special_days[ $post_data['dey_delivery_date'] ]['price'] );
						$price_details['special_day'] = floatval( $special_days[ $post_data['dey_delivery_date'] ]['price'] );
						$special_day_id               = $special_days[ $post_data['dey_delivery_date'] ]['id'];
					}

					// Prepare same day price.
					$same_day_price = $product_delivery->get_same_day_price( $date_object );
					if ( ! empty( $same_day_price ) ) {
						$price_details['same_day'] = floatval( $same_day_price );
					}

					// Prepare next day price.
					$next_day_price = $product_delivery->get_next_day_price( $date_object );
					if ( ! empty( $next_day_price ) ) {
						$price_details['next_day'] = floatval( $next_day_price );
					}

					break;
			}

			$custom_item_data = array(
				'date'           => $delivery_date,
				'last_date'      => $last_delivery_date,
				'product_price'  => $price + floatval( $product->get_price() ),
				'price'          => $price,
				'mode'           => $slot_mode,
				'time_mode'      => $time_mode,
				'time_slot_id'   => $time_slot_id,
				'time_slot_from' => $time_slot_from,
				'time_slot_to'   => $time_slot_to,
				'special_day_id' => $special_day_id,
				'price_details'  => $price_details,
			);

			/**
			 * This hook is used to alter the product delivery custom item data.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'dey_product_delivery_custom_item_data', $custom_item_data, $product_id, $variation_id, $quantity );
		}

		/**
		 * Prepare the product pickup slots cart item.
		 *
		 * @since 3.5.0
		 * @param int $product_id Product ID.
		 * @param int $variation_id Variant ID.
		 * @param int $quantity Quantity.
		 * @return array
		 */
		public static function prepare_product_pickup_custom_item_data( $product_id, $variation_id, $quantity ) {
			$price            = 0;
			$custom_item_data = array();
			$time_slot_id     = '';
			$time_slot_from   = '';
			$time_slot_to     = '';
			$special_day_id   = '';
			$last_pickup_date = '';
			$pickup_location  = '';
			$price_details    = array();
			$product_id       = $variation_id ? $variation_id : $product_id;
			$product          = wc_get_product( $product_id );
			$product_pickup   = DEY_Product_Local_Pickup_Handler::init( $product_id );
			$time_mode        = $product_pickup->get_meta( 'dey_pickup_time_mode' );
			$post_data        = $_REQUEST;

			// Return if the pickup slot is not selected.
			if ( ! isset( $post_data['dey_product_pickup_date'] ) || empty( $post_data['dey_product_pickup_date'] ) ) {
				return $custom_item_data;
			}

			$pickup_date              = $post_data['dey_product_pickup_date'];
			$pickup_location_id       = isset( $post_data['dey_product_pickup_location'] ) ? $post_data['dey_product_pickup_location'] : '';
			$date_object              = DEY_Date_Time::get_date_time_object( $pickup_date );
			$price                    = $product_pickup->get_weekday_price( $date_object );
			$price_details['weekday'] = $product_pickup->get_weekday_price( $date_object );

			// Prepare the time slots.
			if ( isset( $post_data['dey_product_pickup_date_time_slots'] ) && ! empty( $post_data['dey_product_pickup_date_time_slots'] ) ) {
				$time_slot_id = $post_data['dey_product_pickup_date_time_slots'];
				$time_slots   = array_filter( (array) $product_pickup->get_meta( 'dey_pickup_time_slots' ) );
				if ( isset( $time_slots[ $time_slot_id ] ) ) {
					$time_slot_from             = $time_slots[ $time_slot_id ]['from_time'];
					$time_slot_to               = $time_slots[ $time_slot_id ]['to_time'];
					$price                     += floatval( $time_slots[ $time_slot_id ]['price'] );
					$price_details['time_slot'] = floatval( $time_slots[ $time_slot_id ]['price'] );
				}
			}

			// Prepare the special days.
			$special_days = $product_pickup->get_special_dates();
			if ( isset( $special_days[ $post_data['dey_product_pickup_date'] ] ) ) {
				$price                       += floatval( $special_days[ $post_data['dey_product_pickup_date'] ]['price'] );
				$price_details['special_day'] = floatval( $special_days[ $post_data['dey_product_pickup_date'] ]['price'] );
				$special_day_id               = $special_days[ $post_data['dey_product_pickup_date'] ]['id'];
			}

			// Prepare the same day price.
			$same_day_price = $product_pickup->get_same_day_price( $date_object );
			if ( ! empty( $same_day_price ) ) {
				$price_details['same_day'] = floatval( $same_day_price );
			}

			// Prepare the next day price.
			$next_day_price = $product_pickup->get_next_day_price( $date_object );
			if ( ! empty( $next_day_price ) ) {
				$price_details['next_day'] = floatval( $next_day_price );
			}

			$custom_item_data = array(
				'location_id'    => $pickup_location_id,
				'date'           => $pickup_date,
				'last_date'      => $last_pickup_date,
				'product_price'  => $price + floatval( $product->get_price() ),
				'price'          => $price,
				'time_mode'      => $time_mode,
				'time_slot_id'   => $time_slot_id,
				'time_slot_from' => $time_slot_from,
				'time_slot_to'   => $time_slot_to,
				'special_day_id' => $special_day_id,
				'price_details'  => $price_details,
			);

			/**
			 * This hook is used to alter the product pickup custom item data.
			 *
			 * @since 3.5.0
			 */
			return apply_filters( 'dey_product_pickup_custom_item_data', $custom_item_data, $product_id, $variation_id, $quantity );
		}

		/**
		 * Get cart item to display product delivery data in the cart.
		 *
		 * @since 1.0.0
		 * @param array $item_data Cart item data.
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public static function display_product_delivery_custom_item_data( $item_data, $cart_item ) {
			if ( ! isset( $cart_item['dey_delivery_slots'] ) ) {
				return $item_data;
			}

			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_product_delivery() ) {
				return $item_data;
			}

			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			// Return if the product delivery slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $cart_item['product_id'] ) && ! dey_is_product_delivery_type( $cart_item['product_id'] ) ) {
				return $item_data;
			}

			$delivery_slots = $cart_item['dey_delivery_slots'];

			switch ( $delivery_slots['mode'] ) {
				// Expected delivery date.
				case '2':
					// Delivery slots date.
					$delivery_slots_item_data[] = array(
						'name'    => dey_get_product_delivery_expected_info_label(),
						'value'   => $delivery_slots,
						'display' => dey_get_expected_product_delivery_date_message( $delivery_slots['date'], $delivery_slots['last_date'] ),
					);

					break;

				// Calender.
				default:
					// Delivery slots date.
					$delivery_slots_item_data[] = array(
						'name'    => dey_get_product_delivery_date_label(),
						'value'   => $delivery_slots['date'],
						'display' => dey_format_delivery_date( $delivery_slots['date'], $delivery_slots['time_mode'] ),
					);

					// Delivery time slots.
					if ( isset( $delivery_slots['time_slot_id'] ) && ! empty( $delivery_slots['time_slot_id'] ) ) {
						$delivery_slots_item_data[] = array(
							'name'    => dey_get_product_delivery_time_slot_label(),
							'value'   => $delivery_slots['time_slot_id'],
							'display' => dey_format_product_delivery_time_slots( $delivery_slots['time_slot_from'], $delivery_slots['time_slot_to'], $delivery_slots['time_slot_id'] ),
						);
					}

					// Delivery slots charge.
					if ( ! empty( $delivery_slots['price'] ) || 'yes' !== get_post_meta( $cart_item['product_id'], 'dey_delivery_fee_display_hide', true ) ) {
						$delivery_slots_item_data[] = array(
							'name'    => dey_get_product_delivery_fee_label(),
							'value'   => $delivery_slots['price'],
							'display' => dey_price( dey_get_product_price_to_display( $product, $delivery_slots['price'] ) ),
						);
					}

					// Same day delivery charge.
					if ( isset( $delivery_slots['price_details']['same_day'] ) && ! empty( $delivery_slots['price_details']['same_day'] ) ) {
						$delivery_slots_item_data[] = array(
							'name'    => dey_get_product_delivery_same_day_fee_label(),
							'value'   => $delivery_slots['price_details']['same_day'],
							'display' => dey_price( dey_get_product_price_to_display( $product, $delivery_slots['price_details']['same_day'] ) ),
						);
					}

					// Next day delivery charge.
					if ( isset( $delivery_slots['price_details']['next_day'] ) && ! empty( $delivery_slots['price_details']['next_day'] ) ) {
						$delivery_slots_item_data[] = array(
							'name'    => dey_get_product_delivery_next_day_fee_label(),
							'value'   => $delivery_slots['price_details']['next_day'],
							'display' => dey_price( dey_get_product_price_to_display( $product, $delivery_slots['price_details']['next_day'] ) ),
						);
					}
					break;
			}

			/**
			 * This hook is used to alter the product delivery get custom item data.
			 *
			 * @since 1.0.0
			 */
			return apply_filters( 'dey_product_delivery_get_custom_item_data', array_merge( $item_data, $delivery_slots_item_data ), $delivery_slots_item_data, $item_data );
		}

		/**
		 * Get cart item to display product pickup data in the cart.
		 *
		 * @since 3.5.0
		 * @param array $item_data Cart item data.
		 * @param array $cart_item Cart item.
		 * @return array
		 */
		public static function display_product_pickup_custom_item_data( $item_data, $cart_item ) {
			if ( ! isset( $cart_item['dey_product_pickup_slots'] ) ) {
				return $item_data;
			}

			// Return if the product page pickup slots is not enabled.
			if ( ! dey_is_product_local_pickup() ) {
				return $item_data;
			}

			$product_id = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product    = wc_get_product( $product_id );
			// Return if the product pickup slot is disabled.
			if ( dey_is_product_scheduler() && ! dey_is_user_selection_type( $cart_item['product_id'] ) && ! dey_is_product_pickup_type( $cart_item['product_id'] ) ) {
				return $item_data;
			}

			$pickup_slots = $cart_item['dey_product_pickup_slots'];

			// Pickup location.
			if ( isset( $pickup_slots['location_id'] ) && ! empty( $pickup_slots['location_id'] ) ) {
				$pickup_slots_item_data[] = array(
					'name'    => dey_get_product_pickup_location_field_label(),
					'value'   => $pickup_slots['location_id'],
					'display' => dey_get_product_pickup_location_to_display( $cart_item['product_id'], $pickup_slots['location_id'] ),
				);
			}

			// Pickup slots date.
			$pickup_slots_item_data[] = array(
				'name'    => dey_get_product_pickup_date_label(),
				'value'   => $pickup_slots['date'],
				'display' => dey_format_pickup_date( $pickup_slots['date'], $pickup_slots['time_mode'] ),
			);

			// Pickup time slots.
			if ( isset( $pickup_slots['time_slot_id'] ) && ! empty( $pickup_slots['time_slot_id'] ) ) {
				$pickup_slots_item_data[] = array(
					'name'    => dey_get_product_pickup_time_slot_label(),
					'value'   => $pickup_slots['time_slot_id'],
					'display' => dey_format_product_pickup_time_slots( $pickup_slots['time_slot_from'], $pickup_slots['time_slot_to'], $pickup_slots['time_slot_id'] ),
				);
			}

			// Pickup slots charge.
			if ( ! empty( $pickup_slots['price'] ) || 'yes' !== get_post_meta( $cart_item['product_id'], 'dey_pickup_fee_display_hide', true ) ) {
				$pickup_slots_item_data[] = array(
					'name'    => dey_get_product_pickup_fee_label(),
					'value'   => $pickup_slots['price'],
					'display' => dey_price( dey_get_product_price_to_display( $product, $pickup_slots['price'] ) ),
				);
			}

			// Same day pickup charge.
			if ( isset( $pickup_slots['price_details']['same_day'] ) && ! empty( $pickup_slots['price_details']['same_day'] ) ) {
				$pickup_slots_item_data[] = array(
					'name'    => dey_get_product_pickup_same_day_fee_label(),
					'value'   => $pickup_slots['price_details']['same_day'],
					'display' => dey_price( dey_get_product_price_to_display( $product, $pickup_slots['price_details']['same_day'] ) ),
				);
			}

			// Next day pickup charge.
			if ( isset( $pickup_slots['price_details']['next_day'] ) && ! empty( $pickup_slots['price_details']['next_day'] ) ) {
				$pickup_slots_item_data[] = array(
					'name'    => dey_get_product_pickup_next_day_fee_label(),
					'value'   => $pickup_slots['price_details']['next_day'],
					'display' => dey_price( dey_get_product_price_to_display( $product, $pickup_slots['price_details']['next_day'] ) ),
				);
			}

			/**
			 * This hook is used to alter the product pickup get custom item data.
			 *
			 * @since 3.5.0
			 */
			return apply_filters( 'dey_product_pickup_get_custom_item_data', array_merge( $item_data, $pickup_slots_item_data ), $pickup_slots_item_data, $item_data );
		}

		/**
		 * Alter product price based on product delivery slots.
		 *
		 * @since 1.0.0
		 * @param array  $session_data Session data.
		 * @param array  $values Array of values.
		 * @param string $key Key.
		 * @return array
		 */
		public static function set_product_delivery_price( $session_data, $values, $key ) {
			// Return if the product page delivery slots is not enabled.
			if ( ! dey_is_product_delivery() ) {
				return $session_data;
			}

			// Return if the current product is not product delivery slots.
			if ( ! isset( $session_data['dey_delivery_slots'] ) ) {
				return $session_data;
			}

			if ( ! is_object( $session_data['data'] ) ) {
				return $session_data;
			}

			// Return if the product order scheduler slot is disabled.
			if ( ! dey_is_product_scheduler_enabled( $session_data['product_id'] ) ) {
				return $session_data;
			}

			// Return if the product delivery slot is disabled.
			if ( '2' == get_post_meta( $session_data['product_id'], 'dey_delivery_slot_mode', true ) ) {
				return $session_data;
			}

			$price_details = isset( $session_data['dey_delivery_slots']['price_details'] ) ? $session_data['dey_delivery_slots']['price_details'] : array();
			$price         = floatval( $session_data['data']->get_price() );
			if ( dey_check_is_array( $price_details ) ) {
				$price += array_sum( array_map( 'floatval', array_filter( $price_details ) ) );
			}

			/**
			 * This hook is used to alter the product delivery cart item price.
			 *
			 * @since 1.0.0
			 */
			$price = apply_filters( 'dey_product_delivery_cart_item_price', $price, $session_data['data'] );
			$session_data['data']->set_price( $price );

			return $session_data;
		}

		/**
		 * Alter product price based on product pickup slots.
		 *
		 * @since 3.5.0
		 * @param array  $session_data Session data.
		 * @param array  $values Array of values.
		 * @param string $key Key.
		 * @return array
		 */
		public static function set_product_pickup_price( $session_data, $values, $key ) {
			// Return if the product page pickup slots is not enabled.
			if ( ! dey_is_product_local_pickup() ) {
				return $session_data;
			}

			// Return if the current product is not product pickup slots.
			if ( ! isset( $session_data['dey_product_pickup_slots'] ) ) {
				return $session_data;
			}

			if ( ! is_object( $session_data['data'] ) ) {
				return $session_data;
			}

			// Return if the product order scheduler slot is disabled.
			if ( ! dey_is_product_scheduler_enabled( $session_data['product_id'] ) ) {
				return $session_data;
			}

			$price_details = isset( $session_data['dey_product_pickup_slots']['price_details'] ) ? $session_data['dey_product_pickup_slots']['price_details'] : array();
			$price         = floatval( $session_data['data']->get_price() );
			if ( dey_check_is_array( $price_details ) ) {
				$price += array_sum( array_map( 'floatval', array_filter( $price_details ) ) );
			}

			/**
			 * This hook is used to alter the product pickup cart item price.
			 *
			 * @since 3.5.0
			 */
			$price = apply_filters( 'dey_product_pickup_cart_item_price', $price, $session_data['data'] );
			$session_data['data']->set_price( $price );

			return $session_data;
		}

		/**
		 * Add the custom fees.
		 *
		 * @return void
		 */
		public static function custom_fees() {
			// Order delivery.
			self::add_order_delivery_custom_fees();
			// Order tip.
			self::add_order_tip_custom_fees();
		}

		/**
		 * Add the order delivery custom fees.
		 *
		 * @return void
		 */
		public static function add_order_delivery_custom_fees() {
			// Return if the order delivery and order local pickup is disabled.
			$scheduler_rule = dey_get_scheduler_rule_by_shipping_method();
			if ( ! is_object( $scheduler_rule ) ) {
				return;
			}

			if ( dey_is_cart_contains_product_scheduler_only() ) {
				return;
			}

			// Return if the cart contains only virtual products.
			if ( ! dey_order_allow_virtual_products_delivery() ) {
				return;
			}

			// Return if is only order delivery with expected order delivery mode.
			if ( $scheduler_rule->is_order_delivery() && '2' === $scheduler_rule->get_delivery_slot_mode() ) {
				return;
			}

			// Return if the session is not set.
			$session_data = DEY_Cart_Session_Handler::get_session_data();

			/**
			 * This hook is used to alter the order delivery session data.
			 *
			 * @since 1.0
			 */
			$session_data = apply_filters( 'dey_order_delivery_session_data', $session_data );
			if ( ! dey_check_is_array( $session_data ) || ( ! isset( $session_data['order_delivery_date'] ) && ! isset( $session_data['order_local_pickup_date'] ) ) ) {
				return;
			}

			// Unset the order delivery time slots if the time slot price is zero.
			if ( isset( $session_data['order_delivery_time_slot'] ) && empty( $session_data['order_delivery_time_slot']['value'] ) && 'yes' === $scheduler_rule->get_delivery_time_slot_hide_zero_price() ) {
				unset( $session_data['order_delivery_time_slot'] );
			}

			// Unset the order local pickup time slots if the time slot price is zero.
			if ( isset( $session_data['order_local_pickup_time_slot'] ) && empty( $session_data['order_local_pickup_time_slot']['value'] ) ) {
				$pickup_location_id = dey_get_selected_order_scheduler_data_from_session( 'order_pickup_location' );
				$pickup_location    = dey_get_pickup_location( $pickup_location_id );

				if ( ( is_object( $pickup_location ) && 'yes' === $pickup_location->get_pickup_time_slot_hide_zero_price() ) || 'yes' === $scheduler_rule->get_pickup_time_slot_hide_zero_price() ) {
					unset( $session_data['order_local_pickup_time_slot'] );
				}
			}

			$tax = 'yes' === $scheduler_rule->get_delivery_calculate_tax();
			foreach ( $session_data as $session ) {
				if ( ! dey_check_is_array( $session ) ) {
					continue;
				}

				// Add the custom fee.
				WC()->cart->add_fee( $session['label'], $session['value'], $tax, '' );
			}
		}

		/**
		 * Add the order tip custom fees.
		 *
		 * @return void
		 */
		public static function add_order_tip_custom_fees() {
			// Return if the cart and checkout order tip is disabled.
			if ( 'yes' !== get_option( 'dey_order_tip_checkout_enabled' ) && 'yes' !== get_option( 'dey_order_tip_cart_enabled' ) ) {
				return;
			}

			// Return if the session is not set.
			$session_data = dey_get_order_tip_session_data();
			if ( ! dey_check_is_array( $session_data ) ) {
				return;
			}

			// Return if the cart contains only virtual products.
			if ( ! dey_order_allow_virtual_products_delivery() ) {
				return;
			}

			// Add the custom fee.
			WC()->cart->fees_api()->add_fee(
				array(
					'id'       => DEY()->order_tip_fee_name(),
					'tip_mode' => $session_data['mode'],
					'tip_type' => $session_data['type'],
					'name'     => dey_get_order_tip_fee_label(),
					'amount'   => floatval( dey_get_order_tip_fee_amount( $session_data['value'], $session_data['type'] ) ),
					'taxable'  => ( 'no' !== get_option( 'dey_order_tip_tax_enabled' ) ),
				)
			);
		}

		/**
		 * May be add the order tip fee HTML.
		 *
		 * @return HTML
		 */
		public static function maybe_add_order_tip_fee_html( $cart_totals_fee_html, $fee ) {
			if ( ! isset( $fee->id ) ) {
				return $cart_totals_fee_html;
			}

			// Return the fee html when the current fee is not order tip.
			if ( DEY()->order_tip_fee_name() != $fee->id ) {
				return $cart_totals_fee_html;
			}

			if ( is_checkout() ) {
				$class_name = 'dey-checkout-remove-order-tip';
			} else {
				$class_name = 'dey-cart-remove-order-tip';
			}

			$cart_totals_fee_html .= ' <a href="#" class="' . $class_name . '">' . dey_get_order_tip_fee_remove_label() . '</a>';

			return $cart_totals_fee_html;
		}

		/**
		 * Validate the cart items.
		 *
		 * @return bool
		 * */
		public static function validate_cart_items() {
			$return = true;
			if ( ! is_object( WC()->cart ) ) {
				return $return;
			}

			$cart_items = WC()->cart->get_cart();
			if ( ! dey_check_is_array( $cart_items ) ) {
				return $return;
			}

			foreach ( $cart_items as $cart_item_key => $value ) {
				$product_delivery = self::validate_product_delivery_cart_items( $value );
				$product_pickup   = self::validate_product_pickup_cart_items( $value );
				if ( is_wp_error( $product_delivery ) ) {
					dey_add_wc_notice( $product_delivery->get_error_message(), 'error' );
					$return = false;
				} elseif ( is_wp_error( $product_pickup ) ) {
					dey_add_wc_notice( $product_pickup->get_error_message(), 'error' );
					$return = false;
				}

				if ( ! $return ) {
					// Remove the product from the cart.
					WC()->cart->set_quantity( $cart_item_key, 0 );
				}
			}

			return $return;
		}

		/**
		 * Validate the product delivery cart items.
		 *
		 * @since 1.0.0
		 * @param array $cart_item Cart item to validate.
		 * @return bool|WP_Error Error message.
		 */
		public static function validate_product_delivery_cart_items( $cart_item ) {
			if ( ! isset( $value['dey_delivery_slots'] ) ) {
				return true;
			}

			// Return if the product delivery slots is not enabled.
			if ( ! dey_is_product_delivery() || ! dey_is_product_scheduler_enabled( $value['product_id'] ) ) {
				return new WP_Error( 'invalid', dey_get_product_delivery_disabled_cart_message() );
			}

			// Return if the product delivery not allowed the virtual products.
			if ( ! dey_product_delivery_allow_virtual_products( $value['data'] ) ) {
				return new WP_Error( 'invalid', dey_get_product_delivery_virtual_product_removed_cart_message() );
			}

			// Return if the product delivery slot is expected calender mode.
			if ( '2' == $value['dey_delivery_slots']['mode'] ) {
				return true;
			}

			// Validate the delivery date.
			$delivery_date = $value['dey_delivery_slots']['date'];
			$product_id    = ! empty( $value['variation_id'] ) ? $value['variation_id'] : $value['product_id'];

			$product_delivery = DEY_Product_Delivery_Handler::init( $product_id );
			$available_dates  = $product_delivery->get_available_dates();
			$date_object      = DEY_Date_Time::get_date_time_object( $delivery_date );
			$formatted_date   = $date_object->format( 'Y-m-d' );

			if ( ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				return new WP_Error( 'invalid', dey_get_product_delivery_date_cart_incorrect_message() );
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						return new WP_Error( 'invalid', dey_get_product_delivery_date_cart_booked_message() );

					case 'hy':
						return new WP_Error( 'invalid', dey_get_product_delivery_date_cart_holiday_message() );
				}
			}

			// validate the time slots.
			if ( isset( $value['dey_delivery_slots']['time_slot_id'] ) && ! empty( $value['dey_delivery_slots']['time_slot_id'] ) ) {
				if ( ! $product_delivery->is_valid_time_slot( $value['dey_delivery_slots']['time_slot_id'], $delivery_date ) ) {
					return new WP_Error( 'invalid', dey_get_product_delivery_time_slot_cart_incorrect_message() );
				}
			}

			return true;
		}

		/**
		 * Validate the product pickup cart items.
		 *
		 * @since 3.5.0
		 * @param array $cart_item Cart item to validate.
		 * @return bool|WP_Error Error message.
		 */
		public static function validate_product_pickup_cart_items( $cart_item ) {
			if ( ! isset( $value['dey_product_pickup_slots'] ) ) {
				return true;
			}

			// Return if the product pickup slots is not enabled.
			if ( ! dey_is_product_local_pickup() || ! dey_is_product_scheduler_enabled( $value['product_id'] ) ) {
				return new WP_Error( 'invalid', dey_get_product_pickup_disabled_cart_message() );
			}

			// Validate the pickup date.
			$pickup_date = $value['dey_product_pickup_slots']['date'];
			$product_id  = ! empty( $value['variation_id'] ) ? $value['variation_id'] : $value['product_id'];

			$product_pickup  = DEY_Product_Local_Pickup_Handler::init( $product_id );
			$available_dates = $product_pickup->get_available_dates();
			$date_object     = DEY_Date_Time::get_date_time_object( $pickup_date );
			$formatted_date  = $date_object->format( 'Y-m-d' );

			if ( ( ! dey_check_is_array( $available_dates ) || ! isset( $available_dates[ $formatted_date ] ) ) ) {
				return new WP_Error( 'invalid', dey_get_product_pickup_date_cart_incorrect_message() );
			} elseif ( isset( $available_dates[ $formatted_date ]['t'] ) ) {
				switch ( $available_dates[ $formatted_date ]['t'] ) {
					case 'fb':
						return new WP_Error( 'invalid', dey_get_product_pickup_date_cart_booked_message() );

					case 'hy':
						return new WP_Error( 'invalid', dey_get_product_pickup_date_cart_holiday_message() );
				}
			}

			// validate the time slots.
			if ( isset( $value['dey_product_pickup_slots']['time_slot_id'] ) && ! empty( $value['dey_product_pickup_slots']['time_slot_id'] ) ) {
				if ( ! $product_pickup->is_valid_time_slot( $value['dey_product_pickup_slots']['time_slot_id'], $pickup_date ) ) {
					return new WP_Error( 'invalid', dey_get_product_pickup_time_slot_cart_incorrect_message() );
				}
			}

			return true;
		}
	}

	DEY_Cart_Handler::init();
}
