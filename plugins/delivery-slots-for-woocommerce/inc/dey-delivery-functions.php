<?php

/**
 * Delivery functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_format_order_delivery_time_slots' ) ) {

	/**
	 * Format the order delivery time slots.
	 *
	 * @return array
	 */
	function dey_format_order_delivery_time_slots( $time_slot_from, $time_slot_to, $time_slot_id = false, $separator = ' - ' ) {
		$time_slots = '';

		if ( 'soon' == $time_slot_id ) {
			$time_slots = dey_get_order_delivery_as_soon_as_possible_label();
		} elseif ( ! empty( $time_slot_from ) ) {
			$time_slots = dey_format_delivery_time_slot( $time_slot_from ) . $separator . dey_format_delivery_time_slot( $time_slot_to );
		}

		return $time_slots;
	}

}

if ( ! function_exists( 'dey_get_formatted_order_time_slot_label' ) ) {

	/**
	 * Get formatted order time slot label.
	 *
	 * @since 4.0.0
	 * @param string $time_slot_from Time slot from.
	 * @param string $time_slot_to Time slot to.
	 * @param string $time_slot_id Time slot id.
	 * @param string $separator Separator.
	 * @return string
	 */
	function dey_get_formatted_order_time_slot_label( $time_slot_from, $time_slot_to, $time_slot_id = false, $separator = ' - ' ) {
		if ( 'soon' === $time_slot_id ) {
			return dey_get_order_delivery_as_soon_as_possible_label();
		} elseif ( ! empty( $time_slot_from ) ) {
			return dey_format_delivery_time_slot( $time_slot_from ) . $separator . dey_format_delivery_time_slot( $time_slot_to );
		}

		return '';
	}

}

if ( ! function_exists( 'dey_format_product_delivery_time_slots' ) ) {

	/**
	 * Format the product delivery time slots.
	 *
	 * @return array
	 */
	function dey_format_product_delivery_time_slots( $time_slot_from, $time_slot_to, $time_slot_id = false, $separator = ' - ' ) {
		$time_slots = '';

		if ( 'soon' == $time_slot_id ) {
			$time_slots = dey_get_product_delivery_as_soon_as_possible_label();
		} elseif ( ! empty( $time_slot_from ) ) {
			$time_slots = dey_format_delivery_time_slot( $time_slot_from ) . $separator . dey_format_delivery_time_slot( $time_slot_to );
		}

		return $time_slots;
	}

}

if ( ! function_exists( 'dey_format_product_pickup_time_slots' ) ) {

	/**
	 * Format the product pickup time slots.
	 *
	 * @since 3.5.0
	 * @param string $time_slot_from
	 * @param string $time_slot_to
	 * @param int    $time_slot_id
	 * @param string $separator
	 * @return string
	 */
	function dey_format_product_pickup_time_slots( $time_slot_from, $time_slot_to, $time_slot_id = false, $separator = ' - ' ) {
		if ( 'soon' === $time_slot_id ) {
			return dey_get_product_delivery_as_soon_as_possible_label();
		} elseif ( ! empty( $time_slot_from ) ) {
			return dey_format_pickup_time_slot( $time_slot_from ) . $separator . dey_format_pickup_time_slot( $time_slot_to );
		}

		return '';
	}

}

if ( ! function_exists( 'dey_format_delivery_time_slot' ) ) {

	/**
	 * Format the delivery time slot.
	 *
	 * @return array
	 */
	function dey_format_delivery_time_slot( $time_slot ) {
		return DEY_Date_Time::get_wp_format_datetime( $time_slot, 'time' );
	}

}

if ( ! function_exists( 'dey_format_pickup_time_slot' ) ) {

	/**
	 * Format the pickup time slot.
	 *
	 * @since 3.5.0
	 * @param string $time_slot
	 * @return string
	 */
	function dey_format_pickup_time_slot( $time_slot ) {
		return DEY_Date_Time::get_wp_format_datetime( $time_slot, 'time' );
	}

}

if ( ! function_exists( 'dey_format_product_delivery_time_slot_label' ) ) {

	/**
	 * Format the product delivery time slot label.
	 *
	 * @return array
	 */
	function dey_format_product_delivery_time_slot_label( $time_slot_from, $time_slot_to, $price, $product ) {
		$time_slots = dey_format_product_delivery_time_slots( $time_slot_from, $time_slot_to );
		$price      = dey_get_product_price_to_display( $product, $price );

		if ( empty( $price ) && 'yes' == get_post_meta( $product->get_id(), 'dey_delivery_time_slot_hide_zero_price', true ) ) {
			$label = $time_slots;
		} else {
			$label = $time_slots . '(' . dey_price( $price ) . ')';
		}
		/**
		 * This hook is used to alter the product delivery date time slot label.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_date_time_slot_label', $label, $time_slot_from, $time_slot_to, $price, $product );
	}

}

if ( ! function_exists( 'dey_format_product_pickup_time_slot_label' ) ) {

	/**
	 * Format the product pickup time slot label.
	 *
	 * @since 3.5.0
	 * @param string $time_slot_from
	 * @param string $time_slot_to
	 * @param string $price
	 * @param object $product
	 * @return string
	 */
	function dey_format_product_pickup_time_slot_label( $time_slot_from, $time_slot_to, $price, $product ) {
		$time_slots = dey_format_product_pickup_time_slots( $time_slot_from, $time_slot_to );
		$price      = dey_get_product_price_to_display( $product, $price );

		if ( ! $price && 'yes' === get_post_meta( $product->get_id(), 'dey_pickup_time_slot_hide_zero_price', true ) ) {
			$label = $time_slots;
		} else {
			$label = $time_slots . '(' . dey_price( $price ) . ')';
		}

		/**
		 * This hook is used to alter the product pickup date time slot label.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_date_time_slot_label', $label, $time_slot_from, $time_slot_to, $price, $product );
	}

}

if ( ! function_exists( 'dey_format_delivery_date' ) ) {

	/**
	 * Format the delivery date.
	 *
	 * @return array
	 */
	function dey_format_delivery_date( $date, $mode ) {
		$format = ( '2' == $mode ) ? false : 'date';

		return DEY_Date_Time::get_wp_format_datetime( $date, $format );
	}

}

if ( ! function_exists( 'dey_format_pickup_date' ) ) {

	/**
	 * Format the pickup date.
	 *
	 * @since 3.5.0
	 * @param string $date
	 * @param string $mode
	 * @return array
	 */
	function dey_format_pickup_date( $date, $mode ) {
		$format = ( '2' == $mode ) ? false : 'date';

		return DEY_Date_Time::get_wp_format_datetime( $date, $format );
	}

}

if ( ! function_exists( 'dey_get_order_delivery_details' ) ) {

	/**
	 * Get the order delivery details.
	 *
	 * @since 1.0.0
	 * @param object $order Order object.
	 * @param bool   $date_only Date only.
	 * @return array
	 */
	function dey_get_order_delivery_details( $order, $date_only = false ) {
		if ( ! $order ) {
			return array();
		}

		$order = is_numeric( $order ) ? wc_get_order( $order ) : $order;
		if ( ! is_object( $order ) || ! is_a( $order, 'WC_Order' ) ) {
			return array();
		}

		$delivery_details = array();
		$delivery_id      = $order->get_meta( 'dey_order_delivery_id' );
		$local_pickup_id  = $order->get_meta( 'dey_order_local_pickup_id' );
		if ( $delivery_id ) {
			$delivery_details = dey_get_order_delivery_data( $delivery_id, $date_only );
		} elseif ( $local_pickup_id ) {
			$delivery_details = dey_get_order_local_pickup_data( $local_pickup_id, $date_only );
		}

		/**
		 * This hook is used to alter the order delivery details.
		 *
		 * @since 1.0.0
		 */
		return apply_filters( 'dey_order_delivery_details', $delivery_details, $order );
	}

}

if ( ! function_exists( 'dey_get_order_delivery_data' ) ) {

	/**
	 * Get the order delivery data.
	 *
	 * @return array
	 */
	function dey_get_order_delivery_data( $delivery_id, $date_only = false ) {
		if ( ! $delivery_id ) {
			return array();
		}

		$order_delivery = dey_get_order_delivery( $delivery_id );
		if ( ! $order_delivery->exists() || ! dey_check_is_array( $order_delivery->get_product_ids() ) ) {
			return array();
		}

		$delivery_data = array();
		switch ( $order_delivery->get_delivery_mode() ) {
			case '2':
				// Prepare the delivery date details.
				$delivery_data['delivery_date'] = array(
					'label' => dey_get_order_delivery_expected_info_label(),
					'value' => $order_delivery->get_formatted_expected_delivery_date_msg(),
				);

				break;

			default:
				// Prepare the delivery date details.
				$delivery_data['delivery_date'] = array(
					'label' => dey_get_order_delivery_date_label(),
					'value' => $order_delivery->get_formatted_delivery_date(),
				);

				// Prepare the time slot details.
				$time_slots = $order_delivery->get_formatted_time_slots();
				if ( ! empty( $time_slots ) ) {
					$delivery_data['time_slot'] = array(
						'label' => dey_get_order_delivery_time_slot_label(),
						'value' => $time_slots,
					);
				}
				break;
		}

		if ( ! $date_only ) {
			// Prepare the delivery products details.
			$delivery_data['delivery_products'] = array(
				'label' => dey_get_order_delivery_products_label(),
				'value' => dey_get_order_products_link( $order_delivery->get_product_ids(), false ),
			);
		}

		return $delivery_data;
	}

}

if ( ! function_exists( 'dey_get_order_local_pickup_data' ) ) {

	/**
	 * Get the order local pickup data.
	 *
	 * @return array
	 */
	function dey_get_order_local_pickup_data( $local_pickup_id, $date_only = false ) {
		if ( ! $local_pickup_id ) {
			return array();
		}

		$order_local_pickup = dey_get_order_local_pickup( $local_pickup_id );
		if ( ! $order_local_pickup->exists() || ! dey_check_is_array( $order_local_pickup->get_product_ids() ) ) {
			return array();
		}

		$pickup_data = array();
		if ( ! $date_only && ! empty( $order_local_pickup->get_formatted_address() ) ) {
			// Prepare the pickup location details.
			$pickup_data['pickup_location'] = array(
				'label' => dey_get_order_pickup_location_label(),
				'value' => $order_local_pickup->get_formatted_address(),
			);
		}

		// Prepare the pickup date details.
		$pickup_data['pickup_date'] = array(
			'label' => dey_get_order_pickup_date_label(),
			'value' => $order_local_pickup->get_formatted_pickup_date(),
		);

		// Prepare the time slot details.
		$time_slots = $order_local_pickup->get_formatted_time_slots();
		if ( ! empty( $time_slots ) ) {
			$pickup_data['time_slot'] = array(
				'label' => dey_get_order_local_pickup_time_slot_label(),
				'value' => $time_slots,
			);
		}

		if ( ! $date_only ) {
			// Prepare the pickup products details.
			$pickup_data['pickup_products'] = array(
				'label' => dey_get_order_pickup_products_label(),
				'value' => dey_get_order_products_link( $order_local_pickup->get_product_ids(), false ),
			);
		}

		return $pickup_data;
	}

}

if ( ! function_exists( 'dey_get_order_delivery_event_data' ) ) {

	/**
	 * Get the order delivery event data.
	 *
	 * @return array
	 */
	function dey_get_order_delivery_event_data( $order_delivery ) {
		if ( ! is_a( $order_delivery, 'DEY_Order_Delivery' ) ) {
			$order_delivery = dey_get_order_delivery( $order_delivery );
		}

		if ( ! $order_delivery->exists() ) {
			return array();
		}

		$event_data = array();

		$event_data['order_id'] = array(
			'label' => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_edit_post_link( $order_delivery->get_order_id(), '#' . $order_delivery->get_order_id() ),
		);

		if ( '2' == $order_delivery->get_delivery_mode() ) {
			$event_data['delivery_date'] = array(
				'label' => __( 'Expected Delivery Date', 'delivery-slots-for-woocommerce' ),
				'value' => $order_delivery->get_formatted_expected_delivery_date_msg(),
			);
		} else {
			$event_data['delivery_date'] = array(
				'label' => __( 'Delivery Date', 'delivery-slots-for-woocommerce' ),
				'value' => $order_delivery->get_formatted_delivery_date(),
			);
		}

		// Prepare the time slot details.
		$time_slots = $order_delivery->get_formatted_time_slots();
		if ( ! empty( $time_slots ) ) {
			$event_data['time_slot'] = array(
				'label' => __( 'Time Slot', 'delivery-slots-for-woocommerce' ),
				'value' => $time_slots,
			);
		}

		$event_data['product_id'] = array(
			'label' => __( 'Products', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_order_products_link( $order_delivery->get_product_ids() ),
		);

		$event_data['mode'] = array(
			'label' => __( 'Mode', 'delivery-slots-for-woocommerce' ),
			'value' => dey_display_delivery_mode( $order_delivery->get_delivery_mode() ),
		);

		$event_data['amount'] = array(
			'label' => __( 'Delivery Fee', 'delivery-slots-for-woocommerce' ),
			'value' => $order_delivery->get_formatted_delivery_charge(),
		);

		$event_data['user_details'] = array(
			'label' => __( 'User Details', 'delivery-slots-for-woocommerce' ),
			'value' => $order_delivery->get_user_name() . ' (' . $order_delivery->get_user_email() . ')',
		);

		return $event_data;
	}

}

if ( ! function_exists( 'dey_get_product_delivery_event_data' ) ) {

	/**
	 * Get the product delivery event data.
	 *
	 * @return array
	 */
	function dey_get_product_delivery_event_data( $product_delivery ) {
		if ( ! is_a( $product_delivery, 'DEY_Product_Delivery' ) ) {
			$product_delivery = dey_get_product_deilvery( $product_delivery );
		}

		if ( ! $product_delivery->exists() ) {
			return array();
		}

		$event_data = array();

		$event_data['product_id'] = array(
			'label' => __( 'Product Name', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_products_link( array( $product_delivery->get_product_id() ) ),
		);

		$event_data['order_id'] = array(
			'label' => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_edit_post_link( $product_delivery->get_order_id(), '#' . $product_delivery->get_order_id() ),
		);

		$event_data['delivery_date'] = array(
			'label' => __( 'Delivery Date', 'delivery-slots-for-woocommerce' ),
			'value' => $product_delivery->get_formatted_delivery_date(),
		);

		// Prepare the time slot details.
		$time_slots = $product_delivery->get_formatted_time_slots();
		if ( ! empty( $time_slots ) ) {
			$event_data['time_slot'] = array(
				'label' => __( 'Time Slot', 'delivery-slots-for-woocommerce' ),
				'value' => $time_slots,
			);
		}

		$event_data['mode'] = array(
			'label' => __( 'Mode', 'delivery-slots-for-woocommerce' ),
			'value' => dey_display_delivery_mode( $product_delivery->get_delivery_mode() ),
		);

		$event_data['product_quantity'] = array(
			'label' => __( 'Product Quantity', 'delivery-slots-for-woocommerce' ),
			'value' => $product_delivery->get_order_product_quantity(),
		);

		$event_data['amount'] = array(
			'label' => __( 'Delivery Fee', 'delivery-slots-for-woocommerce' ),
			'value' => $product_delivery->get_formatted_delivery_charge(),
		);

		$event_data['user_details'] = array(
			'label' => __( 'User Details', 'delivery-slots-for-woocommerce' ),
			'value' => $product_delivery->get_user_name() . ' (' . $product_delivery->get_user_email() . ')',
		);

		return $event_data;
	}

}

if ( ! function_exists( 'dey_get_product_pickup_event_data' ) ) {

	/**
	 * Get the product pickup event data.
	 *
	 * @since 3.5.0
	 * @param object $product_pickup instanceof DEY_Product_Local_Pickup.
	 * @return array
	 */
	function dey_get_product_pickup_event_data( $product_pickup ) {
		if ( ! $product_pickup ) {
			return array();
		}

		$product_pickup = is_numeric( $product_pickup ) ? dey_get_product_local_pickup( $product_pickup ) : $product_pickup;
		if ( ! is_a( $product_pickup, 'DEY_Product_Local_Pickup' ) || ! $product_pickup->exists() ) {
			return array();
		}

		$event_data               = array();
		$event_data['product_id'] = array(
			'label' => __( 'Product Name', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_products_link( array( $product_pickup->get_product_id() ) ),
		);

		$event_data['order_id'] = array(
			'label' => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_edit_post_link( $product_pickup->get_order_id(), '#' . $product_pickup->get_order_id() ),
		);

		$event_data['pickup_date'] = array(
			'label' => __( 'Pickup Date', 'delivery-slots-for-woocommerce' ),
			'value' => $product_pickup->get_formatted_pickup_date(),
		);

		// Prepare the time slot details.
		$time_slots = $product_pickup->get_formatted_time_slots();
		if ( ! empty( $time_slots ) ) {
			$event_data['time_slot'] = array(
				'label' => __( 'Time Slot', 'delivery-slots-for-woocommerce' ),
				'value' => $time_slots,
			);
		}

		$event_data['product_quantity'] = array(
			'label' => __( 'Product Quantity', 'delivery-slots-for-woocommerce' ),
			'value' => $product_pickup->get_order_product_quantity(),
		);

		$event_data['amount'] = array(
			'label' => __( 'Pickup Fee', 'delivery-slots-for-woocommerce' ),
			'value' => $product_pickup->get_formatted_pickup_charge(),
		);

		$event_data['user_details'] = array(
			'label' => __( 'User Details', 'delivery-slots-for-woocommerce' ),
			'value' => $product_pickup->get_user_name() . ' (' . $product_pickup->get_user_email() . ')',
		);

		return $event_data;
	}

}

if ( ! function_exists( 'dey_get_order_local_pickup_event_data' ) ) {

	/**
	 * Get the order local pickup event data.
	 *
	 * @return array
	 */
	function dey_get_order_local_pickup_event_data( $order_local_pickup ) {
		if ( ! is_a( $order_local_pickup, 'DEY_Order_Local_Pickup' ) ) {
			$order_local_pickup = dey_get_order_delivery( $order_local_pickup );
		}

		if ( ! $order_local_pickup->exists() ) {
			return array();
		}

		$event_data = array();

		$event_data['order_id'] = array(
			'label' => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_edit_post_link( $order_local_pickup->get_order_id(), '#' . $order_local_pickup->get_order_id() ),
		);

		$event_data['address'] = array(
			'label' => __( 'Pickup Address', 'delivery-slots-for-woocommerce' ),
			'value' => $order_local_pickup->get_formatted_address(),
		);

		$event_data['pickup_date'] = array(
			'label' => __( 'Pickup Date', 'delivery-slots-for-woocommerce' ),
			'value' => $order_local_pickup->get_formatted_pickup_date(),
		);

		// Prepare the time slot details.
		$time_slots = $order_local_pickup->get_formatted_time_slots();
		if ( ! empty( $time_slots ) ) {
			$event_data['time_slot'] = array(
				'label' => __( 'Pickup Time Slot', 'delivery-slots-for-woocommerce' ),
				'value' => $time_slots,
			);
		}

		$event_data['product_id'] = array(
			'label' => __( 'Products', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_order_products_link( $order_local_pickup->get_product_ids() ),
		);

		$event_data['amount'] = array(
			'label' => __( 'Pickup Fee', 'delivery-slots-for-woocommerce' ),
			'value' => $order_local_pickup->get_formatted_pickup_charge(),
		);

		$event_data['user_details'] = array(
			'label' => __( 'User Details', 'delivery-slots-for-woocommerce' ),
			'value' => $order_local_pickup->get_user_name() . ' (' . $order_local_pickup->get_user_email() . ')',
		);

		return $event_data;
	}

}

if ( ! function_exists( 'dey_get_order_tip_event_data' ) ) {

	/**
	 * Get the order tip event data.
	 *
	 * @return array
	 */
	function dey_get_order_tip_event_data( $order_tip ) {
		if ( ! is_a( $order_tip, 'DEY_Order_Tip' ) ) {
			$order_tip = dey_get_order_tip( $order_tip );
		}

		if ( ! $order_tip->exists() ) {
			return array();
		}

		$event_data = array();

		$event_data['order_id'] = array(
			'label' => __( 'Order ID', 'delivery-slots-for-woocommerce' ),
			'value' => dey_get_edit_post_link( $order_tip->get_order_id(), '#' . $order_tip->get_order_id() ),
		);

		$event_data['type'] = array(
			'label' => __( 'Type', 'delivery-slots-for-woocommerce' ),
			'value' => dey_order_tip_type_name( $order_tip->get_type() ),
		);

		$event_data['tip'] = array(
			'label' => __( 'Tip', 'delivery-slots-for-woocommerce' ),
			'value' => dey_price( $order_tip->get_amount() ),
		);

		$event_data['user_details'] = array(
			'label' => __( 'User Details', 'delivery-slots-for-woocommerce' ),
			'value' => $order_tip->get_user_name() . ' (' . $order_tip->get_user_email() . ')',
		);

		$event_data['order_date'] = array(
			'label' => __( 'Order Date', 'delivery-slots-for-woocommerce' ),
			'value' => $order_tip->get_formatted_created_date(),
		);

		return $event_data;
	}

}

if ( ! function_exists( 'dey_get_expected_order_delivery_date_message' ) ) {

	/**
	 * Get the expected order delivery date message.
	 *
	 * @return string
	 */
	function dey_get_expected_order_delivery_date_message( $first_date, $last_date ) {
		$from_date = DEY_Date_Time::get_wp_format_datetime( $first_date, 'date' );
		$to_date   = DEY_Date_Time::get_wp_format_datetime( $last_date, 'date' );

		$shortcode_array = array( '{min_duration}', '{max_duration}' );
		$replace_array   = array( $from_date, $to_date );

		return str_replace( $shortcode_array, $replace_array, dey_get_order_delivery_expected_message() );
	}

}

if ( ! function_exists( 'dey_get_expected_product_delivery_date_message' ) ) {

	/**
	 * Get the expected product delivery date message.
	 *
	 * @return string
	 */
	function dey_get_expected_product_delivery_date_message( $first_date, $last_date ) {
		$from_date = DEY_Date_Time::get_wp_format_datetime( $first_date, 'date' );
		$to_date   = DEY_Date_Time::get_wp_format_datetime( $last_date, 'date' );

		$shortcode_array = array( '{min_duration}', '{max_duration}' );
		$replace_array   = array( $from_date, $to_date );

		return str_replace( $shortcode_array, $replace_array, dey_get_product_delivery_expected_message() );
	}

}

if ( ! function_exists( 'dey_update_order_time_slot_usage_count' ) ) {

	/**
	 * Update order time slot usage count.
	 *
	 * @since 4.0.0
	 * @param array $usage_data Usage data.
	 * @return bool
	 */
	function dey_update_order_time_slot_usage_count( $usage_data = array() ) {
		if ( ! dey_check_is_array( $usage_data ) || ! isset( $usage_data['time_slot_id'] ) ) {
			return false;
		}

		$usage_data = wp_parse_args(
			$usage_data,
			array(
				'mode'               => 'global',
				'time_slot_id'       => '',
				'date'               => '',
				'count'              => 1,
				'action'             => 'increase',
				'pickup_location_id' => '',
				'scheduler_rule_id'  => '',
			)
		);

		switch ( $usage_data['mode'] ) {
			case 'scheduler_rule_order_delivery':
				if ( empty( $usage_data['scheduler_rule_id'] ) ) {
					return false;
				}

				dey_update_scheduler_rule_order_delivery_time_slot_usage_count( $usage_data['scheduler_rule_id'], $usage_data['time_slot_id'], $usage_data['date'], $usage_data['count'], $usage_data['action'] );
				break;

			case 'scheduler_rule_order_local_pickup':
				if ( empty( $usage_data['scheduler_rule_id'] ) ) {
					return false;
				}

				dey_update_scheduler_rule_order_local_pickup_time_slot_usage_count( $usage_data['scheduler_rule_id'], $usage_data['time_slot_id'], $usage_data['date'], $usage_data['count'], $usage_data['action'] );
				break;

			case 'pickup_location_order_local_pickup':
				if ( empty( $usage_data['pickup_location_id'] ) ) {
					return false;
				}

				dey_update_pickup_location_order_local_pickup_time_slot_usage_count( $usage_data['pickup_location_id'], $usage_data['time_slot_id'], $usage_data['date'], $usage_data['count'], $usage_data['action'] );
				break;

			default:
				dey_update_time_slot_usage_count( $usage_data['time_slot_id'], $usage_data['date'], $usage_data['count'], $usage_data['action'] );
		}

		/**
		 * This hook is used to do extra action after time slot usage count updated.
		 *
		 * @since 4.0.0
		 * @param array $usage_data Usage data.
		 */
		do_action( 'dey_update_order_time_slot_usage_count', $usage_data );
	}
}

if ( ! function_exists( 'dey_update_scheduler_rule_order_delivery_time_slot_usage_count' ) ) {
	/**
	 * Update scheduler rule order delivery time slot usage count.
	 *
	 * @since 4.0.0
	 * @param int     $scheduler_rule_id Scheduler rule ID.
	 * @param string  $time_slot_key Time slot key.
	 * @param string  $date Date.
	 * @param integer $count Count to be updated.
	 * @param string  $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_scheduler_rule_order_delivery_time_slot_usage_count( $scheduler_rule_id, $time_slot_key, $date, $count = 1, $action = 'increase' ) {
		$time_slots = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_time_slots', true ) );
		if ( ! dey_check_is_array( $time_slots ) || ! isset( $time_slots[ $time_slot_key ] ) ) {
			return false;
		}

		$used_counts         = isset( $time_slots[ $time_slot_key ]['order_delivery_usage_count'] ) ? $time_slots[ $time_slot_key ]['order_delivery_usage_count'] : array();
		$existing_used_count = isset( $used_counts[ $date ] ) ? intval( $used_counts[ $date ] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_count ) ? $existing_used_count - $count : 0;
		} else {
			$updated_count = $existing_used_count + $count;
		}

		if ( isset( $time_slots[ $time_slot_key ]['order_delivery_usage_count'] ) ) {
			$time_slots[ $time_slot_key ]['order_delivery_usage_count'][ $date ] = $updated_count;
		} else {
			$time_slots[ $time_slot_key ]['order_delivery_usage_count'] = array( $date => $updated_count );
		}

		dey_update_scheduler_rule( $scheduler_rule_id, array( 'dey_time_slots' => $time_slots ) );

		/**
		 * This hook is used to do extra action after scheduler rule order delivery time slot usage count updated.
		 *
		 * @since 4.0.0
		 * @param int     $scheduler_rule_id Scheduler rule ID.
		 * @param string  $time_slot_key Time slot key.
		 * @param string  $date Date.
		 * @param integer $count Count to be updated.
		 * @param string  $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_scheduler_rule_order_delivery_time_slot_usage_count', $scheduler_rule_id, $time_slot_key, $date, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_scheduler_rule_order_local_pickup_time_slot_usage_count' ) ) {
	/**
	 * Update scheduler rule order local pickup time slot usage count.
	 *
	 * @since 4.0.0
	 * @param int     $scheduler_rule_id Scheduler rule ID.
	 * @param string  $time_slot_key Time slot key.
	 * @param string  $date Date.
	 * @param integer $count Count to be updated.
	 * @param string  $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_scheduler_rule_order_local_pickup_time_slot_usage_count( $scheduler_rule_id, $time_slot_key, $date, $count = 1, $action = 'increase' ) {
		$time_slots = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_time_slots', true ) );
		if ( ! dey_check_is_array( $time_slots ) || ! isset( $time_slots[ $time_slot_key ] ) ) {
			return false;
		}

		$used_counts         = isset( $time_slots[ $time_slot_key ]['order_local_pickup_usage_count'] ) ? $time_slots[ $time_slot_key ]['order_local_pickup_usage_count'] : array();
		$existing_used_count = isset( $used_counts[ $date ] ) ? intval( $used_counts[ $date ] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_count ) ? $existing_used_count - $count : 0;
		} else {
			$updated_count = $existing_used_count + $count;
		}

		if ( isset( $time_slots[ $time_slot_key ]['order_local_pickup_usage_count'] ) ) {
			$time_slots[ $time_slot_key ]['order_local_pickup_usage_count'][ $date ] = $updated_count;
		} else {
			$time_slots[ $time_slot_key ]['order_local_pickup_usage_count'] = array( $date => $updated_count );
		}

		dey_update_scheduler_rule( $scheduler_rule_id, array( 'dey_time_slots' => $time_slots ) );

		/**
		 * This hook is used to do extra action after scheduler rule order local pickup time slot usage count updated.
		 *
		 * @since 4.0.0
		 * @param int     $scheduler_rule_id Scheduler rule ID.
		 * @param string  $time_slot_key Time slot key.
		 * @param string  $date Date.
		 * @param integer $count Count to be updated.
		 * @param string  $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_scheduler_rule_order_local_pickup_time_slot_usage_count', $scheduler_rule_id, $time_slot_key, $date, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_pickup_location_order_local_pickup_time_slot_usage_count' ) ) {
	/**
	 * Update a pickup location order local pickup time slot usage count.
	 *
	 * @since 4.0.0
	 * @param int     $pickup_location_id Pickup location ID.
	 * @param string  $time_slot_key Time slot key.
	 * @param string  $date Date.
	 * @param integer $count Count to be updated.
	 * @param string  $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_pickup_location_order_local_pickup_time_slot_usage_count( $pickup_location_id, $time_slot_key, $date, $count = 1, $action = 'increase' ) {
		$time_slots = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_time_slots', true ) );
		if ( ! dey_check_is_array( $time_slots ) || ! isset( $time_slots[ $time_slot_key ] ) ) {
			return false;
		}

		$used_counts         = isset( $time_slots[ $time_slot_key ]['used_order_count'] ) ? $time_slots[ $time_slot_key ]['used_order_count'] : array();
		$existing_used_count = isset( $used_counts[ $date ] ) ? intval( $used_counts[ $date ] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_count ) ? $existing_used_count - $count : 0;
		} else {
			$updated_count = $existing_used_count + $count;
		}

		if ( isset( $time_slots[ $time_slot_key ]['used_order_count'] ) ) {
			$time_slots[ $time_slot_key ]['used_order_count'][ $date ] = $updated_count;
		} else {
			$time_slots[ $time_slot_key ]['used_order_count'] = array( $date => $updated_count );
		}

		dey_update_pickup_location( $pickup_location_id, array( 'dey_time_slots' => $time_slots ) );

		/**
		 * This hook is used to do extra action after pickup location order local pickup time slot usage count updated.
		 *
		 * @since 4.0.0
		 * @param int     $pickup_location_id Pickup location ID.
		 * @param string  $time_slot_key Time slot key.
		 * @param string  $date Date.
		 * @param integer $count Count to be updated.
		 * @param string  $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_pickup_location_order_local_pickup_time_slot_usage_count', $pickup_location_id, $time_slot_key, $date, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_time_slot_usage_count' ) ) {
	/**
	 * Update time slot usage count on global level.
	 *
	 * @since 4.0.0
	 * @param int    $time_slot_id Time slot ID.
	 * @param string $date Date.
	 * @param int    $count Count to be updated.
	 * @param string $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_time_slot_usage_count( $time_slot_id, $date, $count = 1, $action = 'increase' ) {
		$time_slot = ! is_object( $time_slot_id ) ? dey_get_time_slot( $time_slot_id ) : $time_slot_id;
		if ( ! $time_slot->exists() ) {
			return false;
		}

		$used_counts         = array_filter( (array) $time_slot->get_order_delivery_usage_count() );
		$existing_used_count = isset( $used_counts[ $date ] ) ? intval( $used_counts[ $date ] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_count ) ? $existing_used_count - $count : 0;
		} else {
			$updated_count = $existing_used_count + $count;
		}

		$used_counts[ $date ] = $updated_count;
		$time_slot->update_meta( 'dey_order_usage_count', $used_counts );

		/**
		 * This hook is used to do extra action after time slot usage count updated.
		 *
		 * @since 4.0.0
		 * @param int    $time_slot_id Time slot ID.
		 * @param string $date Date.
		 * @param int    $count Count to be updated.
		 * @param string $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_time_slot_usage_count', $time_slot_id, $date, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_order_special_day_usage_count' ) ) {
	/**
	 * Update order special day usage count.
	 *
	 * @since 4.0.0
	 * @param array $usage_data Usage data.
	 * @return bool
	 */
	function dey_update_order_special_day_usage_count( $usage_data ) {
		if ( ! dey_check_is_array( $usage_data ) || ! isset( $usage_data['special_day_id'] ) ) {
			return false;
		}

		$usage_data = wp_parse_args(
			$usage_data,
			array(
				'mode'               => 'global',
				'special_day_id'     => '',
				'count'              => 1,
				'action'             => 'increase',
				'pickup_location_id' => '',
				'scheduler_rule_id'  => '',
			)
		);

		switch ( $usage_data['mode'] ) {
			case 'scheduler_rule_order_delivery':
			case 'scheduler_rule_order_local_pickup':
				dey_update_scheduler_rule_order_special_day_usage_count( $usage_data['scheduler_rule_id'], $usage_data['special_day_id'], $usage_data['count'], $usage_data['action'] );
				break;

			case 'pickup_location_order_local_pickup':
				dey_update_pickup_location_order_special_day_usage_count( $usage_data['pickup_location_id'], $usage_data['special_day_id'], $usage_data['count'], $usage_data['action'] );
				break;

			default:
				dey_update_special_day_usage_count( $usage_data['special_day_id'], $usage_data['count'], $usage_data['action'] );
		}

		/**
		 * This hook is used to do extra action after order special day usage count updated.
		 *
		 * @since 4.0.0
		 * @param array $usage_data Usage data.
		 */
		do_action( 'dey_update_order_special_day_usage_count', $usage_data );
	}
}

if ( ! function_exists( 'dey_update_scheduler_rule_order_special_day_usage_count' ) ) {
	/**
	 * Update scheduler rule order special day usage count.
	 *
	 * @since 4.0.0
	 * @param int    $scheduler_rule_id Scheduler rule ID.
	 * @param string $special_day_key Special day key.
	 * @param int    $count Count to be update.
	 * @param string $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_scheduler_rule_order_special_day_usage_count( $scheduler_rule_id, $special_day_key, $count = 1, $action = 'increase' ) {
		$special_days = array_filter( (array) get_post_meta( $scheduler_rule_id, 'dey_special_days', true ) );
		if ( ! isset( $special_days[ $special_day_key ] ) || ! dey_check_is_array( $special_days[ $special_day_key ] ) ) {
			return false;
		}

		$existing_used_order_count = isset( $special_days[ $special_day_key ]['used_order_count'] ) ? intval( $special_days[ $special_day_key ]['used_order_count'] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_order_count ) ? $existing_used_order_count - $count : 0;
		} else {
			$updated_count = $existing_used_order_count + $count;
		}

		$special_days[ $special_day_key ]['used_order_count'] = $updated_count;

		dey_update_scheduler_rule( $scheduler_rule_id, array( 'dey_special_days' => $special_days ) );

		/**
		 * This hook is used to do extra action after special day order usage count updated.
		 *
		 * @since 4.0.0
		 * @param int    $scheduler_rule_id Scheduler rule ID.
		 * @param string $special_day_key Special day key.
		 * @param int    $count Count to be update.
		 * @param string $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_scheduler_rule_order_special_day_usage_count', $scheduler_rule_id, $special_day_key, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_pickup_location_order_special_day_usage_count' ) ) {
	/**
	 * Update pickup location order special day usage count.
	 *
	 * @since 4.0.0
	 * @param int    $pickup_location_id Pickup location ID.
	 * @param string $special_day_key Special day key.
	 * @param int    $count Count to be update.
	 * @param string $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_pickup_location_order_special_day_usage_count( $pickup_location_id, $special_day_key, $count = 1, $action = 'increase' ) {
		$special_days = array_filter( (array) get_post_meta( $pickup_location_id, 'dey_special_days', true ) );
		if ( ! isset( $special_days[ $special_day_key ] ) || ! dey_check_is_array( $special_days[ $special_day_key ] ) ) {
			return false;
		}

		$existing_used_order_count = isset( $special_days[ $special_day_key ]['used_order_count'] ) ? intval( $special_days[ $special_day_key ]['used_order_count'] ) : 0;
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_order_count ) ? $existing_used_order_count - $count : 0;
		} else {
			$updated_count = $existing_used_order_count + $count;
		}

		$special_days[ $special_day_key ]['used_order_count'] = $updated_count;

		dey_update_pickup_location( $pickup_location_id, array( 'dey_special_days' => $special_days ) );

		/**
		 * This hook is used to do extra action after pickup location order special day usage count updated.
		 *
		 * @since 4.0.0
		 * @param int    $pickup_location_id Pickup location ID.
		 * @param string $special_day_key Special day key.
		 * @param int    $count Count to be update.
		 * @param string $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_pickup_location_order_special_day_usage_count', $pickup_location_id, $special_day_key, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_special_day_usage_count' ) ) {
	/**
	 * Update special day usage count on global level.
	 *
	 * @since 4.0.0
	 * @param int|string $special_day_id Special day ID or key.
	 * @param int        $count Count to update.
	 * @param string     $action Whether to increase or decrease the count.
	 * @return bool
	 */
	function dey_update_special_day_usage_count( $special_day_id, $count = 1, $action = 'increase' ) {
		$special_day = ! is_object( $special_day_id ) ? dey_get_special_day( $special_day_id ) : $special_day_id;
		if ( ! $special_day->exists() ) {
			return false;
		}

		$existing_used_order_count = intval( $special_day->get_order_usage_count() );
		if ( 'decrease' === $action ) {
			$updated_count = ( $existing_used_order_count ) ? $existing_used_order_count - $count : 0;
		} else {
			$updated_count = $existing_used_order_count + $count;
		}

		$special_day->update_meta( 'dey_order_usage_count', $updated_count );

		/**
		 * This hook is used to do extra action after special day usage count updated.
		 *
		 * @since 4.0.0
		 * @param int|string $special_day_id Special day ID or key.
		 * @param int        $count Count to update.
		 * @param string     $action Whether to increase or decrease the count.
		 */
		do_action( 'dey_update_special_day_usage_count', $special_day_id, $count, $action );
	}
}

if ( ! function_exists( 'dey_update_product_delivery_special_day_order_usage_count' ) ) {

	/**
	 * Update a product delivery special day order usage count.
	 *
	 * @since 1.0.0
	 * @param object $product instanceof WC_Product.
	 * @param string $key Special day key.
	 * @param int    $count Special day order usage count.
	 * @param string $operation Whether to set|decrease.
	 * @return void
	 */
	function dey_update_product_delivery_special_day_order_usage_count( $product, $key, $count = 1, $operation = 'set' ) {
		if ( ! $key ) {
			return;
		}

		$product = ! is_object( $product ) ? wc_get_product( $product ) : $product;
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_delivery = DEY_Product_Delivery_Handler::init( $product );
		$special_days     = array_filter( (array) $product_delivery->get_meta( 'dey_delivery_special_days' ) );
		if ( ! isset( $special_days[ $key ] ) ) {
			return;
		}

		$old_usage_count = isset( $special_days[ $key ]['used_order_count'] ) ? floatval( $special_days[ $key ]['used_order_count'] ) : 0;
		if ( 'decrease' === $operation ) {
			$updated_count = ( $old_usage_count ) ? $old_usage_count - $count : 0;
		} else {
			$updated_count = $old_usage_count + $count;
		}

		$special_days[ $key ]['used_order_count'] = $updated_count;

		$product_delivery->update_meta( 'dey_delivery_special_days', $special_days );
		/**
		 * This hook is used to do extra action after product delivery special day order usage count updated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'dey_update_product_delivery_special_day_order_usage_count', $product_delivery, $key, $operation, $count );
	}

}

if ( ! function_exists( 'dey_update_product_pickup_special_day_order_usage_count' ) ) {

	/**
	 * Update a product pickup special day order usage count.
	 *
	 * @since 3.5.0
	 * @param object $product instanceof WC_Product.
	 * @param string $key Special day key.
	 * @param int    $count Special day order usage count.
	 * @param string $operation Whether to set|decrease.
	 * @return void
	 */
	function dey_update_product_pickup_special_day_order_usage_count( $product, $key, $count = 1, $operation = 'set' ) {
		if ( ! $key ) {
			return;
		}

		$product = ! is_object( $product ) ? wc_get_product( $product ) : $product;
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_pickup = DEY_Product_Local_pickup_Handler::init( $product );
		$special_days   = array_filter( (array) $product_pickup->get_meta( 'dey_pickup_special_days' ) );
		if ( ! isset( $special_days[ $key ] ) ) {
			return;
		}

		$old_usage_count = isset( $special_days[ $key ]['used_order_count'] ) ? floatval( $special_days[ $key ]['used_order_count'] ) : 0;
		if ( 'decrease' === $operation ) {
			$updated_count = ( $old_usage_count ) ? $old_usage_count - $count : 0;
		} else {
			$updated_count = $old_usage_count + $count;
		}

		$special_days[ $key ]['used_order_count'] = $updated_count;
		$product_pickup->update_meta( 'dey_pickup_special_days', $special_days );

		/**
		 * This hook is used to do extra action after product pickup special day order usage count updated.
		 *
		 * @since 3.5.0
		 */
		do_action( 'dey_update_product_pickup_special_day_order_usage_count', $product_pickup, $key, $operation, $count );
	}

}

if ( ! function_exists( 'dey_update_product_delivery_time_slot_order_usage_count' ) ) {

	/**
	 * Update a product delivery time slot order usage count.
	 *
	 * @since 1.0.0
	 * @param object $product instanceof WC_Product.
	 * @param string $key Time slot key.
	 * @param string $date Date to update.
	 * @param int    $count Used order count.
	 * @param string $operation Whether to set|decrease.
	 * @return void
	 */
	function dey_update_product_delivery_time_slot_order_usage_count( $product, $key, $date, $count = 1, $operation = 'set' ) {
		if ( ! $key || ! $date ) {
			return;
		}

		$product = ! is_object( $product ) ? wc_get_product( $product ) : $product;
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_delivery = DEY_Product_Delivery_Handler::init( $product );
		$time_slots       = array_filter( (array) $product_delivery->get_meta( 'dey_delivery_time_slots' ) );
		if ( ! isset( $time_slots[ $key ] ) ) {
			return;
		}

		$usage_count_array = isset( $time_slots[ $key ]['used_order_count'] ) ? $time_slots[ $key ]['used_order_count'] : array();
		$old_usage_count   = isset( $usage_count_array[ $date ] ) ? floatval( $usage_count_array[ $date ] ) : 0;
		if ( 'decrease' === $operation ) {
			$updated_count = ( $old_usage_count ) ? $old_usage_count - $count : 0;
		} else {
			$updated_count = $old_usage_count + $count;
		}

		$time_slots[ $key ]['used_order_count'][ $date ] = $updated_count;
		$product_delivery->update_meta( 'dey_delivery_time_slots', $time_slots );
		/**
		 * This hook is used to do extra action after product delivery time slot order usage count updated.
		 *
		 * @since 1.0.0
		 */
		do_action( 'dey_update_product_delivery_time_slot_order_usage_count', $product_delivery, $key, $date, $operation, $count );
	}

}

if ( ! function_exists( 'dey_update_product_pickup_time_slot_order_usage_count' ) ) {

	/**
	 * Update a product pickup time slot order usage count.
	 *
	 * @since 3.5.0
	 * @param object $product instanceof WC_Product.
	 * @param int    $key Time slot key.
	 * @param string $date Date to update.
	 * @param int    $count Used order count.
	 * @param string $operation Operation to set|decrease.
	 * @return void
	 */
	function dey_update_product_pickup_time_slot_order_usage_count( $product, $key, $date, $count = 1, $operation = 'set' ) {
		if ( ! $key || ! $date ) {
			return;
		}

		$product = ! is_object( $product ) ? wc_get_product( $product ) : $product;
		if ( ! is_object( $product ) || ! is_a( $product, 'WC_Product' ) ) {
			return;
		}

		$product_pickup = DEY_Product_Local_Pickup_Handler::init( $product );
		$time_slots     = array_filter( (array) $product_pickup->get_meta( 'dey_pickup_time_slots' ) );
		if ( ! isset( $time_slots[ $key ] ) ) {
			return;
		}

		$usage_count_array = isset( $time_slots[ $key ]['used_order_count'] ) ? $time_slots[ $key ]['used_order_count'] : array();
		$old_usage_count   = isset( $usage_count_array[ $date ] ) ? floatval( $usage_count_array[ $date ] ) : 0;
		if ( 'decrease' === $operation ) {
			$updated_count = ( $old_usage_count ) ? $old_usage_count - $count : 0;
		} else {
			$updated_count = $old_usage_count + $count;
		}

		$time_slots[ $key ]['used_order_count'][ $date ] = $updated_count;
		$product_pickup->update_meta( 'dey_pickup_time_slots', $time_slots );
		/**
		 * This hook is used to do extra action after product pickup time slot order usage count updated.
		 *
		 * @since 3.5.0
		 */
		do_action( 'dey_update_product_pickup_time_slot_order_usage_count', $product_pickup, $key, $date, $operation, $count );
	}

}

if ( ! function_exists( 'dey_get_order_tip_predefined_values' ) ) {

	/**
	 * Get the order tip predefined values.
	 *
	 * @return bool
	 */
	function dey_get_order_tip_predefined_values() {
		$buttons           = array();
		$predefined_values = get_option( 'dey_order_tip_predefined_values' );
		if ( is_string( $predefined_values ) ) {
			$predefined_values = array_filter( array_map( 'intval', (array) explode( '|', $predefined_values ) ) );
			$tip_type          = get_option( 'dey_order_tip_predefined_value_type' );
			foreach ( $predefined_values as $predefined_value ) {
				$buttons[ $predefined_value ] = ( '2' === $tip_type ) ? $predefined_value . '%' : dey_price( $predefined_value );
			}
		}
		/**
		 * This hook is used to alter the order tip predefined values.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_predefined_values', $buttons );
	}

}

if ( ! function_exists( 'dey_get_order_tip_buttons' ) ) {

	/**
	 * Get the order tip buttons.
	 *
	 * @return bool
	 */
	function dey_get_order_tip_buttons() {
		static $buttons;
		if ( isset( $buttons ) ) {
			return $buttons;
		}

		$buttons = dey_get_order_tip_predefined_values();
		if ( ! dey_is_predefined_order_tip_type() ) {
			$buttons['custom'] = dey_get_order_tip_custom_button_label();
		}

		/**
		 * This hook is used to alter the order tip buttons.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_buttons', $buttons );
	}

}

if ( ! function_exists( 'dey_is_predefined_order_tip_type' ) ) {

	/**
	 * Is a predefined order tip type?.
	 *
	 * @return bool
	 */
	function dey_is_predefined_order_tip_type() {
		/**
		 * This hook is used to check if the order tip type is predefined.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_is_predefined_order_tip_type', '2' == get_option( 'dey_order_tip_display_type' ) );
	}

}

if ( ! function_exists( 'dey_is_custom_order_tip_type' ) ) {

	/**
	 * Is a custom order tip type?.
	 *
	 * @return bool
	 */
	function dey_is_custom_order_tip_type() {
		/**
		 * This hook is used to check if the order tip type is custom.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_is_custom_order_tip_type', '3' == get_option( 'dey_order_tip_display_type' ) );
	}

}

if ( ! function_exists( 'dey_is_mixed_order_tip_type' ) ) {

	/**
	 * Is a mixed order tip type?.
	 *
	 * @return bool
	 */
	function dey_is_mixed_order_tip_type() {
		/**
		 * This hook is used to check if the order tip type is mixed.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_is_mixed_order_tip_type', '1' === get_option( 'dey_order_tip_display_type' ) );
	}

}

if ( ! function_exists( 'dey_get_order_tip_fee_amount' ) ) {

	/**
	 * Get the order tip fee amount.
	 *
	 * @param float  $amount Tip amount.
	 * @param string $type Tip type.
	 * @return string
	 */
	function dey_get_order_tip_fee_amount( $amount, $type ) {
		$fee_amount = floatval( $amount );
		if ( 'custom' !== $type && ! empty( $amount ) && ( '2' === get_option( 'dey_order_tip_predefined_value_type' ) ) ) {
			$order_total = ( '2' === get_option( 'dey_order_tip_percentage_type' ) ) ? dey_get_wc_cart_total() : dey_get_wc_cart_subtotal();
			$fee_amount  = ( $order_total * $amount ) / 100;
		}
		/**
		 * This hook is used to alter the order tip fee.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_fee_amount', $fee_amount, $amount );
	}

}

if ( ! function_exists( 'dey_order_allow_virtual_products_delivery' ) ) {

	/**
	 * Allow virtual products for the order delivery.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	function dey_order_allow_virtual_products_delivery() {
		$return = true;
		// Return if the cart contains only virtual products.
		if ( 'yes' === get_option( 'dey_advanced_virtual_products_disabled' ) && dey_cart_contains_only_virtual_products() ) {
			$return = false;
		}

		/**
		 * This hook is used to alter the virtual products allowing for the order delivery.
		 *
		 * @since 2.4
		 */
		return apply_filters( 'dey_order_allow_virtual_products_delivery', $return );
	}

}

if ( ! function_exists( 'dey_product_delivery_allow_virtual_products' ) ) {

	/**
	 * Allow virtual products for the product delivery.
	 *
	 * @since 2.4
	 *
	 * @return bool
	 */
	function dey_product_delivery_allow_virtual_products( $product ) {
		$return = true;
		// Return if the cart contains only virtual products.
		if ( 'yes' === get_option( 'dey_advanced_virtual_products_disabled' ) && $product->is_virtual() ) {
			$return = false;
		}
		/**
		 * This hook is used to alter the virtual products allowing for the product delivery.
		 *
		 * @since 2.4
		 */
		return apply_filters( 'dey_product_delivery_allow_virtual_products', $return );
	}

}

if ( ! function_exists( 'dey_parse_processing_hours' ) ) {

	/**
	 * Parse the processing hours.
	 *
	 * @since 3.2.0
	 * @param array $processing_time Processing time.
	 *
	 * @return array
	 */
	function dey_parse_processing_hours( $processing_time ) {

		return wp_parse_args(
			(array) $processing_time,
			array(
				'number' => '',
				'unit'   => 'hours',
			)
		);
	}

}

if ( ! function_exists( 'dey_processing_hours_exists' ) ) {

	/**
	 * Get the processing time as hours.
	 *
	 * @since 3.2.0
	 * @param array $processing_time Processing time.
	 *
	 * @return bool
	 */
	function dey_processing_hours_exists( $processing_time ) {
		if ( ! isset( $processing_time['number'] ) || empty( $processing_time['number'] ) ) {
			return false;
		}

		if ( ! isset( $processing_time['unit'] ) || empty( $processing_time['unit'] ) ) {
			return false;
		}

		return true;
	}

}

if ( ! function_exists( 'dey_get_processing_hours' ) ) {

	/**
	 * Get the processing time as hours.
	 *
	 * @since 3.2.0
	 * @param array $processing_time Processing time.
	 *
	 * @return int
	 */
	function dey_get_processing_hours( $processing_time ) {
		$processing_hours = ( 'hours' === $processing_time['unit'] ) ? $processing_time['number'] : $processing_time['number'] * 24;

		return (int) $processing_hours;
	}

}

if ( ! function_exists( 'dey_get_business_hours' ) ) {

	/**
	 * Get the business hours.
	 *
	 * @since 3.2.0
	 * @param object $current_date_object Current Date Object.
	 * @param string $opening_time Opening Time.
	 * @param string $closing_time Closing TIme.
	 *
	 * @return int
	 */
	function dey_get_business_hours( $current_date_object, $opening_time, $closing_time ) {
		// Return if both times are empty.
		if ( empty( $opening_time ) && empty( $closing_time ) ) {
			return 24;
		}

		if ( empty( $opening_time ) && ! empty( $closing_time ) ) {
			$opening_time = '00:00';
		} elseif ( ! empty( $opening_time ) && empty( $closing_time ) ) {
			$closing_time = '23:59';
		}

		$opening_time_object = DEY_Date_Time::get_date_time_object( $opening_time );
		$closing_time_object = DEY_Date_Time::get_date_time_object( $closing_time );

		if ( ( $current_date_object->format( 'd-m-Y' ) === $opening_time_object->format( 'd-m-Y' ) ) && ( $current_date_object > $opening_time_object ) ) {
			$diff = $current_date_object->diff( $closing_time_object );
		} else {
			$diff = $opening_time_object->diff( $closing_time_object );
		}

		return $diff->h;
	}

}

if ( ! function_exists( 'dey_get_date_after_processing' ) ) {

	/**
	 * Get the date after processing hour calculated.
	 *
	 * @since 3.2.0
	 * @param array $business_days Business days options.
	 * @param array $processing_time Processing Time.
	 */
	function dey_get_date_after_processing( $current_date_object, $processing_time, $total_time_diff, $opening_time, $closing_time ) {
		$opening_time_obj               = DEY_Date_Time::get_date_time_object( $current_date_object->format( 'Y-m-d' ) . ' ' . $opening_time );
		$closing_time_obj               = DEY_Date_Time::get_date_time_object( $current_date_object->format( 'Y-m-d' ) . ' ' . $closing_time );
		$date_after_processing          = ( $opening_time_obj >= $current_date_object ) ? $opening_time_obj : $current_date_object;
		$processing_hour_per_day        = $date_after_processing->diff( $closing_time_obj )->h;
		$total_processing_hours_per_day = $total_time_diff + $processing_hour_per_day;
		$processing_hours               = dey_get_processing_hours( $processing_time );

		if ( $processing_hours <= $total_time_diff ) {
			$remaining_hours = $processing_hour_per_day - (int) ( $total_time_diff - $processing_hours );

			return $opening_time_obj->modify( '+' . $remaining_hours . 'hours' );
		}

		return false;
	}

}

if ( ! function_exists( 'dey_get_order_delivery_cancel_cutoff_time' ) ) {

	/**
	 * Get the order delivery cancel cutoff time.
	 *
	 * @since 3.8.0
	 * @return int
	 */
	function dey_get_order_delivery_cancel_cutoff_time() {
		return wp_parse_args(
			(array) get_option( 'dey_advanced_order_delivery_cancel_cutoff_time' ),
			array(
				'number' => '',
				'unit'   => 'hours',
			)
		);
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_cancel_cutoff_time' ) ) {

	/**
	 * Get the order local pickup cancel cutoff time.
	 *
	 * @since 3.8.0
	 * @return int
	 */
	function dey_get_order_local_pickup_cancel_cutoff_time() {
		return wp_parse_args(
			(array) get_option( 'dey_advanced_order_pickup_cancel_cutoff_time' ),
			array(
				'number' => '',
				'unit'   => 'hours',
			)
		);
	}
}

if ( ! function_exists( 'dey_is_valid_to_display_cancel_order' ) ) {

	/**
	 * Is valid to display cancel order button?
	 *
	 * @since 3.8.0
	 * @param object $order Order object.
	 * @return int
	 */
	function dey_is_valid_to_display_cancel_order( $order ) {
		if ( ! is_object( $order ) ) {
			return false;
		}

		if ( ! empty( $order->get_meta( 'dey_order_delivery_id' ) ) ) {
			return dey_is_valid_order_delivery_to_cancel_order( $order );
		} elseif ( ! empty( $order->get_meta( 'dey_order_local_pickup_id' ) ) ) {
			return dey_is_valid_order_local_pickup_to_cancel_order( $order );
		}

		return true;
	}
}

if ( ! function_exists( 'dey_is_valid_order_delivery_to_cancel_order' ) ) {

	/**
	 * Is valid order delivery to cancel order?
	 *
	 * @since 3.8.0
	 * @param object $order Order object.
	 * @return int
	 */
	function dey_is_valid_order_delivery_to_cancel_order( $order ) {
		if ( ! is_object( $order ) ) {
			return false;
		}

		$order_delivery_id = $order->get_meta( 'dey_order_delivery_id' );
		if ( empty( $order_delivery_id ) ) {
			return true;
		}

		$order_delivery = dey_get_order_delivery( $order_delivery_id );
		if ( ! $order_delivery->exists() ) {
			return true;
		}

		if ( ! $order_delivery->is_valid_cancel_order() ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'dey_is_valid_order_local_pickup_to_cancel_order' ) ) {

	/**
	 * Is valid order local pickup to cancel order?
	 *
	 * @since 3.8.0
	 * @param object $order Order object.
	 * @return int
	 */
	function dey_is_valid_order_local_pickup_to_cancel_order( $order ) {
		if ( ! is_object( $order ) ) {
			return false;
		}

		$order_local_pickup_id = $order->get_meta( 'dey_order_local_pickup_id' );
		if ( empty( $order_local_pickup_id ) ) {
			return true;
		}

		$order_local_pickup = dey_get_order_local_pickup( $order_local_pickup_id );
		if ( ! $order_local_pickup->exists() ) {
			return true;
		}

		if ( ! $order_local_pickup->is_valid_cancel_order() ) {
			return false;
		}

		return true;
	}
}
