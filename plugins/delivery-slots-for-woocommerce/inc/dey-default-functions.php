<?php

/**
 * Default functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_get_product_time_slot_default_data' ) ) {

	/**
	 * Get the product time slot default data.
	 *
	 * @return array
	 */
	function dey_get_product_time_slot_default_data() {
		/**
		 * This hook is used to alter the product time slot default data.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_product_time_slot_default_data',
			array(
				'name'              => 'Untitled',
				'from_time'         => '',
				'to_time'           => '',
				'price'             => '',
				'week_days_enabled' => 'no',
				'week_days'         => array(),
				'order_count'       => '',
				'used_order_count'  => array(),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_product_special_day_default_data' ) ) {

	/**
	 * Get the product special day default data.
	 *
	 * @return array
	 */
	function dey_get_product_special_day_default_data() {
		/**
		 * This hook is used to alter the product special day default data.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_product_special_day_default_data',
			array(
				'name'             => 'Untitled',
				'date'             => '',
				'price'            => '',
				'order_count'      => '',
				'used_order_count' => 0,
			)
		);
	}
}

if ( ! function_exists( 'dey_get_product_holiday_default_data' ) ) {

	/**
	 * Get the product holiday default data.
	 *
	 * @return array
	 */
	function dey_get_product_holiday_default_data() {
		/**
		 * This hook is used to alter the product holiday default data.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_product_holiday_default_data',
			array(
				'name'      => 'Untitled',
				'from_date' => '',
				'to_date'   => '',
				'recurring' => 'no',
			)
		);
	}
}

if ( ! function_exists( 'dey_weekdays' ) ) {

	/**
	 * Week days.
	 *
	 * @return array
	 */
	function dey_weekdays() {
		static $dey_weekdays;
		if ( isset( $dey_weekdays ) ) {
			return $dey_weekdays;
		}
		/**
		 * This hook is used to alter the weekdays.
		 *
		 * @since 1.0
		 */
		$dey_weekdays = apply_filters(
			'dey_weekdays',
			array(
				'1' => __( 'Sunday', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Monday', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Tuesday', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'Wednesday', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Thursday', 'delivery-slots-for-woocommerce' ),
				'6' => __( 'Friday', 'delivery-slots-for-woocommerce' ),
				'7' => __( 'Saturday', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $dey_weekdays;
	}
}

if ( ! function_exists( 'dey_delivery_mode' ) ) {

	/**
	 * Delivery mode.
	 *
	 * @return array
	 */
	function dey_delivery_modes() {
		/**
		 * This hook is used to alter the delivery modes.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_delivery_modes',
			array(
				'1' => __( 'Calendar', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Expected Delivery Info', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_delivery_time_mode' ) ) {

	/**
	 * Delivery time mode.
	 *
	 * @return array
	 */
	function dey_delivery_time_modes() {
		/**
		 * This hook is used to alter the delivery time modes.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_delivery_time_modes',
			array(
				'1' => __( 'None', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Time Selector', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_pickup_time_mode' ) ) {

	/**
	 * Pickup time mode.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	function dey_pickup_time_mode() {
		/**
		 * This hook is used to alter the pickup time modes.
		 *
		 * @since 3.5.0
		 */
		return apply_filters(
			'dey_pickup_time_modes',
			array(
				'1' => __( 'None', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Time Selector', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Time Slots', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_product_delivery_slot_types' ) ) {

	/**
	 * Product delivery slot types.
	 *
	 * @return array
	 */
	function dey_product_delivery_slot_types() {
		/**
		 * This hook is used to alter the product delivery slot types.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_product_delivery_slot_types',
			array(
				'1' => __( 'No', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Yes', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_order_scheduler_display_positions' ) ) {

	/**
	 * Order scheduler display positions.
	 *
	 * @return array
	 */
	function dey_order_scheduler_display_positions() {
		/**
		 * This hook is used to alter the order scheduler display positions.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_order_scheduler_display_positions',
			array(
				'1' => __( 'Billing Section', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Before Customer Fields', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Before Order Notes', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'After Order Notes', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_order_tip_checkout_display_positions' ) ) {

	/**
	 * Order tip checkout display positions.
	 *
	 * @return array
	 */
	function dey_order_tip_checkout_display_positions() {
		/**
		 * This hook is used to alter the order tip display positions in the checkout.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_order_tip_checkout_display_positions',
			array(
				'1' => __( 'Billing Section', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Before Customer Fields', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Before Order Notes', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'After Order Notes', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_order_tip_cart_display_positions' ) ) {

	/**
	 * Order tip cart display positions.
	 *
	 * @return array
	 */
	function dey_order_tip_cart_display_positions() {
		/**
		 * This hook is used to alter the order tip display positions in the cart.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_order_tip_cart_display_positions',
			array(
				'1' => __( 'Before Cart Table', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'After Cart Table', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_delivery_day_filters' ) ) {

	/**
	 * Get the delivery day filters.
	 *
	 * @return array
	 */
	function dey_get_delivery_day_filters() {
		/**
		 * This hook is used to alter the delivery day filters.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_delivery_day_filters',
			array(
				'1' => __( 'Today', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Tomorrow', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'One Week', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'One Month', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Specific Date Range', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_order_tip_day_filters' ) ) {

	/**
	 * Get the order tip day filters.
	 *
	 * @return array
	 */
	function dey_get_order_tip_day_filters() {
		/**
		 * This hook is used to alter the order tip day filters.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_order_tip_day_filters',
			array(
				'1' => __( 'Today', 'delivery-slots-for-woocommerce' ),
				'6' => __( 'Yesterday', 'delivery-slots-for-woocommerce' ),
				'7' => __( 'Last One Week', 'delivery-slots-for-woocommerce' ),
				'8' => __( 'Last One Month', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Specific Date Range', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_datepicker_themes' ) ) {

	/**
	 * Get the date picker themes.
	 *
	 * @return array
	 */
	function dey_get_datepicker_themes() {
		/**
		 * This hook is used to alter the date picker themes.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_datepicker_themes',
			array(
				'base'           => __( 'Base', 'delivery-slots-for-woocommerce' ),
				'black-tie'      => __( 'Black Tie', 'delivery-slots-for-woocommerce' ),
				'blitzer'        => __( 'Blitzer', 'delivery-slots-for-woocommerce' ),
				'cupertino'      => __( 'Cupertino', 'delivery-slots-for-woocommerce' ),
				'dark-hive'      => __( 'Dark Hive', 'delivery-slots-for-woocommerce' ),
				'dot-luv'        => __( 'Dot Luv', 'delivery-slots-for-woocommerce' ),
				'eggplant'       => __( 'Eggplant', 'delivery-slots-for-woocommerce' ),
				'excite-bike'    => __( 'Excite Bike', 'delivery-slots-for-woocommerce' ),
				'flick'          => __( 'Flick', 'delivery-slots-for-woocommerce' ),
				'hot-sneaks'     => __( 'Hot Sneaks', 'delivery-slots-for-woocommerce' ),
				'humanity'       => __( 'Humanity', 'delivery-slots-for-woocommerce' ),
				'le-frog'        => __( 'Le Frog', 'delivery-slots-for-woocommerce' ),
				'mint-choc'      => __( 'Mint Choc', 'delivery-slots-for-woocommerce' ),
				'overcast'       => __( 'Overcast', 'delivery-slots-for-woocommerce' ),
				'pepper-grinder' => __( 'Pepper Grinder', 'delivery-slots-for-woocommerce' ),
				'redmond'        => __( 'Redmond', 'delivery-slots-for-woocommerce' ),
				'smoothness'     => __( 'Smoothness', 'delivery-slots-for-woocommerce' ),
				'south-street'   => __( 'South Street', 'delivery-slots-for-woocommerce' ),
				'start'          => __( 'Start', 'delivery-slots-for-woocommerce' ),
				'sunny'          => __( 'Sunny', 'delivery-slots-for-woocommerce' ),
				'swanky-purse'   => __( 'Swanky Purse', 'delivery-slots-for-woocommerce' ),
				'trontastic'     => __( 'Trontastic', 'delivery-slots-for-woocommerce' ),
				'ui-darkness'    => __( 'UI Darkness', 'delivery-slots-for-woocommerce' ),
				'ui-lightness'   => __( 'UI Lightness', 'delivery-slots-for-woocommerce' ),
				'vader'          => __( 'Vader', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_filter_product_options' ) ) {

	/**
	 * Get the filter product options.
	 *
	 * @return array
	 */
	function dey_get_filter_product_options() {
		/**
		 * This hook is used to alter the pickup location filter product options.
		 *
		 * @since 1.0
		 */
		return apply_filters(
			'dey_filter_product_options',
			array(
				'1' => __( 'Include Products', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Exclude Products', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Include Categories', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'Exclude Categories', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Include Product Types', 'delivery-slots-for-woocommerce' ),
				'6' => __( 'Exclude Product Types', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_shipping_type_options' ) ) {

	/**
	 * Get the order pickup location shipping type options.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_order_pickup_location_shipping_type_options() {
		static $shipping_type_options;
		if ( isset( $shipping_type_options ) ) {
			return $shipping_type_options;
		}

		/**
		 * This hook is used to alter the order pickup location filter shipping type options.
		 *
		 * @since 4.0.0
		 */
		$shipping_type_options = apply_filters(
			'dey_order_pickup_location_shipping_type_options',
			array(
				'1' => __( 'Include Shipping', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Exclude Shipping', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $shipping_type_options;
	}
}

if ( ! function_exists( 'dey_get_default_restriction_rule_data' ) ) {

	/**
	 * Get the default restriction rule data.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_default_restriction_rule_data() {
		/**
		 * This hook is used to alter the default restriction data.
		 *
		 * @since 4.0.0
		 */
		return apply_filters(
			'dey_default_restriction_rule_data',
			array(
				'rule_type'     => '1',
				'product_type'  => '1',
				'user_type'     => '1',
				'order_type'    => '1',
				'products'      => array(),
				'product_types' => array(),
				'tags'          => array(),
				'categories'    => array(),
				'countries'     => array(),
				'price'         => '',
			)
		);
	}
}

if ( ! function_exists( 'dey_get_default_order_pickup_location_restriction_rule_data' ) ) {

	/**
	 * Get the default order pickup location restriction rule data.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_default_order_pickup_location_restriction_rule_data() {
		/**
		 * This hook is used to alter the default order pickup location restriction data.
		 *
		 * @since 4.0.0
		 */
		return apply_filters(
			'dey_default_order_pickup_location_restriction_rule_data',
			array(
				'rule_type'        => '1',
				'product_type'     => '1',
				'shipping_type'    => '1',
				'user_type'        => '1',
				'order_type'       => '1',
				'products'         => array(),
				'product_types'    => array(),
				'categories'       => array(),
				'tags'             => array(),
				'shipping_methods' => array(),
				'countries'        => array(),
				'price'            => '',
			)
		);
	}
}

if ( ! function_exists( 'dey_product_scheduler_types' ) ) {

	/**
	 * Get product scheduler types.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	function dey_product_scheduler_types() {
		/**
		 * This hook is used to alter the product scheduler types.
		 *
		 * @since 3.5.0
		 */
		return apply_filters(
			'dey_product_scheduler_types',
			array(
				'1' => __( 'Delivery', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Pickup', 'delivery-slots-for-woocommerce' ),
				'3' => __( "User's Selection", 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_product_pickup_location_default_data' ) ) {

	/**
	 * Get the product pickup location default data.
	 *
	 * @since 3.5.0
	 * @static $pickup_location_default_data Pickup location default data.
	 * @return array
	 */
	function dey_get_product_pickup_location_default_data() {
		static $pickup_location_default_data;
		if ( isset( $pickup_location_default_data ) ) {
			return $pickup_location_default_data;
		}

		/**
		 * This hook is used to alter the product pickup location default data.
		 *
		 * @since 3.5.0
		 */
		$pickup_location_default_data = apply_filters(
			'dey_product_pickup_location_default_data',
			array(
				'name'         => 'Untitled',
				'address1'     => '',
				'address2'     => '',
				'city'         => '',
				'country'      => '',
				'pincode'      => '',
				'phone_number' => '',
				'email_lists'  => '',
			)
		);

		return $pickup_location_default_data;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_time_slot_default_data' ) ) {

	/**
	 * Get the scheduler rule time slot default data.
	 *
	 * @since 4.0.0
	 * @static array $time_slot_default_data
	 * @return array
	 */
	function dey_get_scheduler_rule_time_slot_default_data() {
		static $time_slot_default_data;
		if ( isset( $time_slot_default_data ) ) {
			return $time_slot_default_data;
		}

		/**
		 * This hook is used to alter the scheduler rule time slot default data.
		 *
		 * @since 4.0.0
		 */
		$time_slot_default_data = apply_filters(
			'dey_scheduler_rule_time_slot_default_data',
			array(
				'name'             => 'Untitled',
				'cutoff_time'      => '',
				'from_time'        => '',
				'to_time'          => '',
				'price'            => '',
				'week_days'        => array(),
				'order_count'      => '',
				'used_order_count' => array(),
			)
		);

		return $time_slot_default_data;
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_time_slot_default_data' ) ) {

	/**
	 * Get the order pickup location time slot default data.
	 *
	 * @since 4.0.0
	 * @static array $time_slot_default_data
	 * @return array
	 */
	function dey_get_order_pickup_location_time_slot_default_data() {
		static $time_slot_default_data;
		if ( isset( $time_slot_default_data ) ) {
			return $time_slot_default_data;
		}

		/**
		 * This hook is used to alter the order pickup location time slot default data.
		 *
		 * @since 4.0.0
		 */
		$time_slot_default_data = apply_filters(
			'dey_order_pickup_location_time_slot_default_data',
			array(
				'name'             => 'Untitled',
				'from_time'        => '',
				'to_time'          => '',
				'price'            => '',
				'week_days'        => array(),
				'order_count'      => '',
				'used_order_count' => array(),
			)
		);

		return $time_slot_default_data;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_holiday_default_data' ) ) {

	/**
	 * Get the scheduler rule holiday default data.
	 *
	 * @since 4.0.0
	 * @static array holiday_default_data
	 * @return array
	 */
	function dey_get_scheduler_rule_holiday_default_data() {
		static $holiday_default_data;
		if ( isset( $holiday_default_data ) ) {
			return $holiday_default_data;
		}

		/**
		 * This hook is used to alter the scheduler rule holiday default data.
		 *
		 * @since 4.0.0
		 */
		$holiday_default_data = apply_filters(
			'dey_scheduler_rule_holiday_default_data',
			array(
				'name'      => 'Untitled',
				'from_date' => '',
				'to_date'   => '',
				'recurring' => 'no',
			)
		);

		return $holiday_default_data;
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_holiday_default_data' ) ) {

	/**
	 * Get the order pickup location holiday default data.
	 *
	 * @since 4.0.0
	 * @static array $holiday_default_data
	 * @return array
	 */
	function dey_get_order_pickup_location_holiday_default_data() {
		static $holiday_default_data;
		if ( isset( $holiday_default_data ) ) {
			return $holiday_default_data;
		}

		/**
		 * This hook is used to alter the order pickup location holiday default data.
		 *
		 * @since 4.0.0
		 */
		$holiday_default_data = apply_filters(
			'dey_order_pickup_location_holiday_default_data',
			array(
				'name'      => 'Untitled',
				'from_date' => '',
				'to_date'   => '',
				'recurring' => 'no',
			)
		);

		return $holiday_default_data;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_special_day_default_data' ) ) {

	/**
	 * Get the scheduler rule special day default data.
	 *
	 * @since 4.0.0
	 * @static $special_day_default_data
	 * @return array
	 */
	function dey_get_scheduler_rule_special_day_default_data() {
		static $special_day_default_data;
		if ( isset( $special_day_default_data ) ) {
			return $special_day_default_data;
		}

		/**
		 * This hook is used to alter the scheduler rule special day default data.
		 *
		 * @since 4.0.0
		 */
		$special_day_default_data = apply_filters(
			'dey_scheduler_rule_special_day_default_data',
			array(
				'name'             => 'Untitled',
				'date'             => '',
				'price'            => '',
				'order_count'      => '',
				'used_order_count' => 0,
			)
		);

		return $special_day_default_data;
	}
}

if ( ! function_exists( 'dey_get_order_pickup_location_special_day_default_data' ) ) {

	/**
	 * Get the order pickup location special day default data.
	 *
	 * @since 4.0.0
	 * @static $special_day_default_data
	 * @return array
	 */
	function dey_get_order_pickup_location_special_day_default_data() {
		static $special_day_default_data;
		if ( isset( $special_day_default_data ) ) {
			return $special_day_default_data;
		}

		/**
		 * This hook is used to alter the order pickup location special day default data.
		 *
		 * @since 4.0.0
		 */
		$special_day_default_data = apply_filters(
			'dey_order_pickup_location_special_day_default_data',
			array(
				'name'             => 'Untitled',
				'date'             => '',
				'price'            => '',
				'order_count'      => '',
				'used_order_count' => 0,
			)
		);

		return $special_day_default_data;
	}
}

if ( ! function_exists( 'dey_get_order_scheduler_options' ) ) {

	/**
	 * Get order scheduler type options.
	 *
	 * @since 4.0.0
	 * @static array $order_scheduler_options
	 * @return array
	 */
	function dey_get_order_scheduler_options() {
		static $order_scheduler_options;
		if ( isset( $order_scheduler_options ) ) {
			return $order_scheduler_options;
		}

		/**
		 * This hook is used to alter the order scheduler type options.
		 *
		 * @since 4.0.0
		 */
		$order_scheduler_options = apply_filters(
			'dey_order_scheduler_options',
			array(
				'1' => __( 'Delivery only', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Pickup only', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Both Delivery and Pickup', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $order_scheduler_options;
	}
}

if ( ! function_exists( 'dey_get_time_slots_schedule_types' ) ) {

	/**
	 * Get the options time slots schedule types
	 *
	 * @since 2.5
	 * @return array
	 */
	function dey_get_time_slots_schedule_types() {
		/**
		 * This hook is used to alter the options time slots schedule types.
		 *
		 * @since 2.5
		 */
		return apply_filters(
			'dey_time_slots_schedule_types',
			array(
				'2' => __( 'Delivery only', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Pickup only', 'delivery-slots-for-woocommerce' ),
				'1' => __( 'Both Delivery and Pickup', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_special_day_schedule_types' ) ) {

	/**
	 * Get the options for special day schedule types.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	function dey_get_special_day_schedule_types() {
		/**
		 * This hook is used to alter the options for special day schedule types.
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'dey_special_day_schedule_types',
			array(
				'1' => __( 'Both Pickup and Delivery', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Delivery only', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Pickup only', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_holiday_schedule_types' ) ) {

	/**
	 * Get the options for holiday schedule types.
	 *
	 * @since 3.0.0
	 * @return array
	 */
	function dey_get_holiday_schedule_types() {
		/**
		 * This hook is used to alter the options for holiday schedule types.
		 *
		 * @since 3.0.0
		 */
		return apply_filters(
			'dey_holiday_schedule_types',
			array(
				'1' => __( 'Both Pickup and Delivery', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Delivery only', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Pickup only', 'delivery-slots-for-woocommerce' ),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_restriction_rule_options' ) ) {

	/**
	 * Get the restriction rule options.
	 *
	 * @since 4.0.0
	 * @static array $restriction_rule_options Restriction rule options.
	 * @return array
	 */
	function dey_get_scheduler_rule_restriction_rule_options() {
		static $restriction_rule_options;

		if ( $restriction_rule_options ) {
			return $restriction_rule_options;
		}

		/**
		 * This hook is used to alter the restriction rule options.
		 *
		 * @since 4.0.0
		 */
		$restriction_rule_options = apply_filters(
			'dey_scheduler_rule_restriction_rule_options',
			array(
				'1' => __( 'Product', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'User', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Order', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $restriction_rule_options;
	}
}

if ( ! function_exists( 'dey_order_pickup_location_restriction_rule_options' ) ) {

	/**
	 * Get the order pickup location restriction rule options.
	 *
	 * @since 4.0.0
	 * @static array $restriction_rule_options Restriction rule options.
	 * @return array
	 */
	function dey_order_pickup_location_restriction_rule_options() {
		static $restriction_rule_options;
		if ( $restriction_rule_options ) {
			return $restriction_rule_options;
		}

		/**
		 * This hook is used to alter the order pickup location restriction rule options.
		 *
		 * @since 4.0.0
		 */
		$restriction_rule_options = apply_filters(
			'dey_order_pickup_location_restriction_rule_options',
			array(
				'1' => __( 'Product', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'User', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Order', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'Shipping', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $restriction_rule_options;
	}
}

if ( ! function_exists( 'dey_restriction_rule_user_type_options' ) ) {

	/**
	 * Get the restriction rule user type options.
	 *
	 * @since 4.0.0
	 * @static array $user_type_options
	 * @return array
	 */
	function dey_restriction_rule_user_type_options() {
		static $user_type_options;
		if ( $user_type_options ) {
			return $user_type_options;
		}

		/**
		 * This hook is used to alter the restriction rule user type options.
		 *
		 * @since 4.0.0
		 */
		$user_type_options = apply_filters(
			'dey_restriction_rule_user_type_options',
			array(
				'1' => __( 'Include Countries', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Exclude Countries', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $user_type_options;
	}
}

if ( ! function_exists( 'dey_restriction_rule_product_type_options' ) ) {

	/**
	 * Get the restriction rule product type options.
	 *
	 * @since 4.0.0
	 * @static array $product_type_options Product type options.
	 * @return array
	 */
	function dey_restriction_rule_product_type_options() {
		static $product_type_options;
		if ( $product_type_options ) {
			return $product_type_options;
		}

		/**
		 * This hook is used to alter the restriction rule product type options.
		 *
		 * @since 4.0.0
		 */
		$product_type_options = apply_filters(
			'dey_restriction_rule_product_type_options',
			array(
				'1' => __( 'Include Products', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Exclude Products', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Include Product Types', 'delivery-slots-for-woocommerce' ),
				'6' => __( 'Exclude Product Types', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Include Categories', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'Exclude Categories', 'delivery-slots-for-woocommerce' ),
				'7' => __( 'Include Product Tags', 'delivery-slots-for-woocommerce' ),
				'8' => __( 'Exclude Product Tags', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $product_type_options;
	}
}

if ( ! function_exists( 'dey_get_restriction_rule_order_type_options' ) ) {

	/**
	 * Get the restriction rule order type options.
	 *
	 * @since 4.0.0
	 * @static array $order_type_options Order type options.
	 * @return array
	 * */
	function dey_get_restriction_rule_order_type_options() {
		static $order_type_options;
		if ( isset( $order_type_options ) ) {
			return $order_type_options;
		}

		/**
		 * This hook is used to alter the rule order type options.
		 *
		 * @since 4.0.0
		 */
		$order_type_options = apply_filters(
			'dey_restriction_rule_order_type_options',
			array(
				'1' => __( 'Cart Subtotal is less than or equal to', 'delivery-slots-for-woocommerce' ),
				'2' => __( 'Cart Subtotal is more than or equal to', 'delivery-slots-for-woocommerce' ),
				'3' => __( 'Order Total is less than or equal to', 'delivery-slots-for-woocommerce' ),
				'4' => __( 'Order Total is more than or equal to', 'delivery-slots-for-woocommerce' ),
				'5' => __( 'Number of Products in Cart less than or equal to', 'delivery-slots-for-woocommerce' ),
				'6' => __( 'Number of Products in Cart more than or equal to', 'delivery-slots-for-woocommerce' ),
			)
		);

		return $order_type_options;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_default_data' ) ) {

	/**
	 * Get the scheduler rule default data.
	 *
	 * @since 4.0.0
	 * @static $scheduler_rule_default_data
	 * @return array
	 */
	function dey_get_scheduler_rule_default_data() {
		static $scheduler_rule_default_data;
		if ( isset( $scheduler_rule_default_data ) ) {
			return $scheduler_rule_default_data;
		}

		/**
		 * This hook is used to alter the scheduler rule default data.
		 *
		 * @since 4.0.0
		 */
		$scheduler_rule_default_data = apply_filters(
			'dey_scheduler_rule_default_data',
			array(
				'dey_scheduler_type'                     => 3,
				'dey_shipping_methods'                   => '',
				'dey_end_date'                           => '',
				'dey_priority'                           => 10,
				'dey_description'                        => '',
				'dey_delivery_slot_mode'                 => '',
				'dey_delivery_days_availability'         => '',
				'dey_delivery_expected_date_from'        => '',
				'dey_delivery_expected_date_to'          => '',
				'dey_delivery_days'                      => array(),
				'dey_delivery_max_per_day'               => '',
				'dey_delivery_calender_required'         => '',
				'dey_delivery_time_mode'                 => '',
				'dey_delivery_available_time_from'       => '',
				'dey_delivery_available_time_to'         => '',
				'dey_delivery_time_slot_required'        => '',
				'dey_delivery_as_soon_as_possible'       => '',
				'dey_delivery_first_available_time_slot' => '',
				'dey_delivery_time_slot_hide_zero_price' => '',
				'dey_delivery_time_slot_max'             => '',
				'dey_delivery_processing_time'           => array(),
				'dey_delivery_same_day_cutoff_time'      => '',
				'dey_delivery_next_day_cutoff_time'      => '',
				'dey_delivery_weekdays_prices'           => array(),
				'dey_delivery_same_day_fee'              => '',
				'dey_delivery_next_day_fee'              => '',
				'dey_delivery_calculate_tax'             => '',
				'dey_pickup_days_availability'           => '',
				'dey_pickup_days'                        => array(),
				'dey_pickup_max_per_day'                 => '',
				'dey_pickup_location_required'           => '',
				'dey_pickup_calender_required'           => '',
				'dey_pickup_time_mode'                   => '',
				'dey_pickup_time_slot_required'          => '',
				'dey_pickup_as_soon_as_possible'         => '',
				'dey_pickup_first_available_time_slot'   => '',
				'dey_pickup_time_slot_hide_zero_price'   => '',
				'dey_pickup_available_time_from'         => '',
				'dey_pickup_available_time_to'           => '',
				'dey_pickup_time_slot_max'               => '',
				'dey_pickup_processing_time'             => array(),
				'dey_pickup_same_day_cutoff_time'        => '',
				'dey_pickup_next_day_cutoff_time'        => '',
				'dey_pickup_weekdays_prices'             => array(),
				'dey_pickup_same_day_fee'                => '',
				'dey_pickup_next_day_fee'                => '',
				'dey_pickup_calculate_tax'               => '',
				'dey_time_slots_mode'                    => '1',
				'dey_time_slots'                         => array(),
				'dey_holidays_mode'                      => '1',
				'dey_holidays'                           => array(),
				'dey_special_days_mode'                  => '1',
				'dey_special_days'                       => array(),
				'dey_restriction_rule_groups'            => array(),
			)
		);

		return $scheduler_rule_default_data;
	}
}
