<?php

/**
 * Post functions.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'dey_create_new_order_delivery' ) ) {

	/**
	 * Create a new order delivery.
	 *
	 * @return integer/string
	 */
	function dey_create_new_order_delivery( $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Delivery();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_order_delivery' ) ) {

	/**
	 * Get the order delivery object.
	 *
	 * @return object
	 */
	function dey_get_order_delivery( $id ) {
		$object = new DEY_Order_Delivery( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_order_delivery' ) ) {

	/**
	 * Update the order delivery.
	 *
	 * @return object
	 */
	function dey_update_order_delivery( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Delivery( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_order_delivery' ) ) {

	/**
	 * Delete the order delivery.
	 *
	 * @return bool
	 */
	function dey_delete_order_delivery( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_order_delivery_statuses' ) ) {

	/**
	 * Get the order delivery statuses.
	 *
	 * @return array
	 */
	function dey_get_order_delivery_statuses() {
		/**
		 * This hook is used to alter the order delivery statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_delivery_statuses', array( 'dey_pending_payment', 'dey_upcoming', 'dey_delivered' ) );
	}
}

if ( ! function_exists( 'dey_create_new_product_delivery' ) ) {

	/**
	 * Create a new product delivery.
	 *
	 * @return integer/string
	 */
	function dey_create_new_product_delivery( $meta_args, $post_args = array() ) {
		$object = new DEY_Product_Delivery();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_product_delivery' ) ) {

	/**
	 * Get the product delivery object.
	 *
	 * @return object
	 */
	function dey_get_product_delivery( $id ) {
		$object = new DEY_Product_Delivery( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_product_delivery' ) ) {

	/**
	 * Update the product delivery.
	 *
	 * @return object
	 */
	function dey_update_product_delivery( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Product_Delivery( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_product_delivery' ) ) {

	/**
	 * Delete the product delivery.
	 *
	 * @return bool
	 */
	function dey_delete_product_delivery( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_product_delivery_statuses' ) ) {

	/**
	 * Get the product delivery statuses.
	 *
	 * @return array
	 */
	function dey_get_product_delivery_statuses() {
		/**
		 * This hook is used to alter the product delivery statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_product_delivery_statuses', array( 'dey_pending_payment', 'dey_upcoming', 'dey_delivered' ) );
	}
}

if ( ! function_exists( 'dey_create_new_product_local_pickup' ) ) {
	/**
	 * Create a new product local pickup.
	 *
	 * @since 3.5.0
	 * @param array $meta_args Meta arguments.
	 * @param array $post_args Post arguments.
	 * @return int
	 */
	function dey_create_new_product_local_pickup( $meta_args, $post_args = array() ) {
		$object = new DEY_Product_Local_Pickup();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_product_local_pickup' ) ) {
	/**
	 * Get the product local pickup object.
	 *
	 * @since 3.5.0
	 * @param int|string $id ID of the product local pickup object.
	 * @return object
	 */
	function dey_get_product_local_pickup( $id ) {
		$object = new DEY_Product_Local_Pickup( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_product_local_pickup' ) ) {
	/**
	 * Update the product local pickup.
	 *
	 * @since 3.5.0
	 * @param int|string $id ID of the product local pickup object.
	 * @param array      $meta_args Meta arguments.
	 * @param array      $post_args Post arguments.
	 * @return object
	 */
	function dey_update_product_local_pickup( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Product_Local_Pickup( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_product_local_pickup' ) ) {
	/**
	 * Delete the product local pickup.
	 *
	 * @since 3.2.0
	 * @param int|string $id ID of the product local pickup object.
	 * @param bool       $force Force to delete.
	 * @return bool
	 */
	function dey_delete_product_local_pickup( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_product_local_pickup_statuses' ) ) {

	/**
	 * Get the product local pickup statuses.
	 *
	 * @since 3.5.0
	 * @return array
	 */
	function dey_get_product_local_pickup_statuses() {

		/**
		 * This hook is used to alter the product local pickup statuses.
		 *
		 * @since 3.5.0
		 */
		return apply_filters( 'dey_product_pickup_statuses', array( 'dey_pending_payment', 'dey_upcoming', 'dey_picked_up' ) );
	}
}

if ( ! function_exists( 'dey_create_new_order_local_pickup' ) ) {

	/**
	 * Create a new order local pickup.
	 *
	 * @since 2.2
	 *
	 * @return integer/string
	 */
	function dey_create_new_order_local_pickup( $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Local_Pickup();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup' ) ) {

	/**
	 * Get the order local pickup object.
	 *
	 * @since 2.2
	 *
	 * @return object
	 */
	function dey_get_order_local_pickup( $id ) {
		$object = new DEY_Order_Local_Pickup( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_order_local_pickup' ) ) {

	/**
	 * Update the order local pickup.
	 *
	 * @since 2.2
	 *
	 * @return object
	 */
	function dey_update_order_local_pickup( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Local_Pickup( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_order_local_pickup' ) ) {

	/**
	 * Delete the order local pickup.
	 *
	 * @since 2.2
	 *
	 * @return bool
	 */
	function dey_delete_order_local_pickup( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_statuses' ) ) {

	/**
	 * Get the order local pickup statuses.
	 *
	 * @since 2.2
	 *
	 * @return array
	 */
	function dey_get_order_local_pickup_statuses() {
		/**
		 * This hook is used to alter the order local pickup statuses.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_order_local_pickup_statuses', array( 'dey_pending_payment', 'dey_upcoming', 'dey_picked_up' ) );
	}
}

if ( ! function_exists( 'dey_create_new_time_slot' ) ) {

	/**
	 * Create a new time slot.
	 *
	 * @return integer/string
	 */
	function dey_create_new_time_slot( $meta_args, $post_args = array() ) {
		$object = new DEY_Time_Slot();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_time_slot' ) ) {

	/**
	 * Get the time slot object.
	 *
	 * @return object
	 */
	function dey_get_time_slot( $id ) {
		$object = new DEY_Time_Slot( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_time_slot' ) ) {

	/**
	 * Update the time slot.
	 *
	 * @return object
	 */
	function dey_update_time_slot( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Time_Slot( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_time_slot' ) ) {

	/**
	 * Delete the time slot.
	 *
	 * @return bool
	 */
	function dey_delete_time_slot( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_time_slot_statuses' ) ) {

	/**
	 * Get the time slot statuses.
	 *
	 * @return array
	 */
	function dey_get_time_slot_statuses() {
		/**
		 * This hook is used to alter the time slot statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_time_slot_statuses', array( 'publish' ) );
	}
}

if ( ! function_exists( 'dey_create_new_holiday' ) ) {

	/**
	 * Create a new holiday.
	 *
	 * @return integer/string
	 */
	function dey_create_new_holiday( $meta_args, $post_args = array() ) {
		$object = new DEY_Holiday();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_holiday' ) ) {

	/**
	 * Get the holiday object.
	 *
	 * @return object
	 */
	function dey_get_holiday( $id ) {
		$object = new DEY_Holiday( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_holiday' ) ) {

	/**
	 * Update the holiday.
	 *
	 * @return object
	 */
	function dey_update_holiday( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Holiday( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_holiday' ) ) {

	/**
	 * Delete the holiday.
	 *
	 * @return bool
	 */
	function dey_delete_holiday( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_holiday_statuses' ) ) {

	/**
	 * Get the holiday statuses.
	 *
	 * @return array
	 */
	function dey_get_holiday_statuses() {
		/**
		 * This hook is used to alter the holiday statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_holiday_statuses', array( 'publish' ) );
	}
}

if ( ! function_exists( 'dey_create_new_special_day' ) ) {

	/**
	 * Create a new special day.
	 *
	 * @return integer/string
	 */
	function dey_create_new_special_day( $meta_args, $post_args = array() ) {
		$object = new DEY_Special_Day();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_special_day' ) ) {

	/**
	 * Get the special day object.
	 *
	 * @return object
	 */
	function dey_get_special_day( $id ) {
		$object = new DEY_Special_Day( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_special_day' ) ) {

	/**
	 * Update the special day.
	 *
	 * @return object
	 */
	function dey_update_special_day( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Special_Day( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_special_day' ) ) {

	/**
	 * Delete the special day.
	 *
	 * @return bool
	 */
	function dey_delete_special_day( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_special_day_statuses' ) ) {

	/**
	 * Get the special day statuses.
	 *
	 * @return array
	 */
	function dey_get_special_day_statuses() {
		/**
		 * This hook is used to alter the special day statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_special_day_statuses', array( 'publish' ) );
	}
}

if ( ! function_exists( 'dey_create_new_order_tip' ) ) {

	/**
	 * Create a new order tip.
	 *
	 * @return integer/string
	 */
	function dey_create_new_order_tip( $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Tip();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_order_tip' ) ) {

	/**
	 * Get the order tip object.
	 *
	 * @return object
	 */
	function dey_get_order_tip( $id ) {
		$object = new DEY_Order_Tip( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_order_tip' ) ) {

	/**
	 * Update the order tip.
	 *
	 * @return object
	 */
	function dey_update_order_tip( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Order_Tip( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_order_tip' ) ) {

	/**
	 * Delete the order tip.
	 *
	 * @return bool
	 */
	function dey_delete_order_tip( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_order_tip_statuses' ) ) {

	/**
	 * Get the order tip statuses.
	 *
	 * @return array
	 */
	function dey_get_order_tip_statuses() {
		/**
		 * This hook is used to alter the order tip statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters( 'dey_order_tip_statuses', array( 'dey_pending_payment', 'dey_paid', 'dey_failed' ) );
	}
}

if ( ! function_exists( 'dey_create_new_pickup_location' ) ) {

	/**
	 * Create a new pickup location.
	 *
	 * @since 2.2
	 *
	 * @return integer/string
	 */
	function dey_create_new_pickup_location( $meta_args, $post_args = array() ) {
		$object = new DEY_Pickup_Location();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_pickup_location' ) ) {

	/**
	 * Get the pickup location object.
	 *
	 * @since 2.2
	 *
	 * @return object
	 */
	function dey_get_pickup_location( $id ) {
		$object = new DEY_Pickup_Location( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_pickup_location' ) ) {

	/**
	 * Update the pickup location.
	 *
	 * @since 2.2
	 *
	 * @return object
	 */
	function dey_update_pickup_location( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_Pickup_Location( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_pickup_location' ) ) {

	/**
	 * Delete the pickup location.
	 *
	 * @since 2.2
	 *
	 * @return bool
	 */
	function dey_delete_pickup_location( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_pickup_location_statuses' ) ) {

	/**
	 * Get the pickup location statuses.
	 *
	 * @since 2.2
	 *
	 * @return array
	 */
	function dey_get_pickup_location_statuses() {
		/**
		 * This hook is used to alter the pickup location statuses.
		 *
		 * @since 2.2
		 */
		return apply_filters( 'dey_pickup_location_statuses', array( 'publish' ) );
	}
}

if ( ! function_exists( 'dey_display_post_status' ) ) {

	/**
	 * Display the formatted post status.
	 *
	 * @return string
	 */
	function dey_display_post_status( $status, $html = true ) {
		$status_object = get_post_status_object( $status );
		if ( ! isset( $status_object ) ) {
			return '';
		}

		return $html ? '<mark class="dey_status_label ' . esc_attr( $status ) . '_status"><span >' . esc_html( $status_object->label ) . '</span></mark>' : esc_html( $status_object->label );
	}
}

if ( ! function_exists( 'dey_get_pickup_location_ids' ) ) {

	/**
	 * Get the pickup locations.
	 *
	 * @since 2.2.0
	 * @return array
	 */
	function dey_get_pickup_location_ids() {
		return get_posts(
			array(
				'post_type'        => DEY_Register_Post_Types::PICKUP_LOCATIONS_POSTTYPE,
				'post_status'      => array( 'publish', 'dey_active' ),
				'fields'           => 'ids',
				'numberposts'      => '-1',
				'suppress_filters' => false,
			)
		);
	}
}

if ( ! function_exists( 'dey_update_post_meta' ) ) {

	/**
	 * Update post meta.
	 *
	 * @since 3.8.0
	 * @param int $post_id
	 * @param string $meta_key
	 * @param mixed $meta_value 
	 */
	function dey_update_post_meta( $post_id, $meta_key, $meta_value ) {
		$return = update_post_meta( $post_id, $meta_key, $meta_value );

		if ( $return ) {
			/**
			 * Update post meta.
			 *
			 * @since 3.8.0
			 */
			do_action( 'dey_update_post_meta', $post_id, $meta_key, $meta_value );
		}

		return $return;
	}

}

if ( ! function_exists( 'dey_get_product_id' ) ) {

	/**
	 * Get product ID
	 *
	 * @since 3.8.0
	 * @param int $product_id
	 * @return int
	 */
	function dey_get_product_id( $product_id ) {
		/**
		 * Get Product ID.
		 *
		 * @since 3.8.0
		 */
		return apply_filters( 'dey_get_product_id', $product_id );
	}

}

if ( ! function_exists( 'dey_create_new_scheduler_rule' ) ) {

	/**
	 * Create a new scheduler rule.
	 *
	 * @since 4.0.0
	 * @param array $meta_args Meta arguments.
	 * @param array $post_args Post arguments.
	 * @return int
	 */
	function dey_create_new_scheduler_rule( $meta_args, $post_args = array() ) {
		$object = new DEY_scheduler_rule();
		$id     = $object->create( $meta_args, $post_args );

		return $id;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule' ) ) {

	/**
	 * Get the order delivery rule object.
	 *
	 * @since 4.0.0
	 * @param int $id Order delivery rule ID.
	 * @return object
	 */
	function dey_get_scheduler_rule( $id ) {
		$object = new DEY_scheduler_rule( $id );

		return $object;
	}
}

if ( ! function_exists( 'dey_update_scheduler_rule' ) ) {

	/**
	 * Update the order delivery rule.
	 *
	 * @since 4.0.0
	 * @param int   $id Order delivery rule ID.
	 * @param array $meta_args Meta arguments.
	 * @param array $post_args Post arguments.
	 * @return object
	 */
	function dey_update_scheduler_rule( $id, $meta_args, $post_args = array() ) {
		$object = new DEY_scheduler_rule( $id );
		$object->update( $meta_args, $post_args );

		return $object;
	}
}

if ( ! function_exists( 'dey_delete_scheduler_rule' ) ) {

	/**
	 * Delete the order delivery rule.
	 *
	 * @since 4.0.0
	 * @param int  $id Order delivery rule ID.
	 * @param bool $force Whether to force deletion or not.
	 * @return bool
	 */
	function dey_delete_scheduler_rule( $id, $force = true ) {
		wp_delete_post( $id, $force );

		return true;
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_statuses' ) ) {

	/**
	 * Get the order delivery rule statuses.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_scheduler_rule_statuses() {
		/**
		 * This hook is used to alter the order delivery rule statuses.
		 *
		 * @since 4.0.0
		 */
		return apply_filters( 'dey_scheduler_rule_statuses', array( 'dey_active', 'dey_inactive' ) );
	}
}

if ( ! function_exists( 'dey_get_scheduler_rule_ids' ) ) {

	/**
	 * Get the scheduler rules.
	 *
	 * @since 4.0.0
	 * @param string $post_status Post status.
	 * @return array
	 */
	function dey_get_scheduler_rule_ids( $post_status = 'dey_active' ) {
		global $wpdb;

		$post_query = new DEY_Query( $wpdb->prefix . 'posts', 'p' );
		$post_query->select( 'DISTINCT p.ID' )
				->leftJoin( $wpdb->prefix . 'postmeta', 'pm1', 'p.ID = pm1.post_id' )
				->where( 'p.post_type', DEY_Register_Post_Types::SCHEDULER_RULE_POSTTYPE )
				->where( 'p.post_status', $post_status )
				->where( 'pm1.meta_key', 'dey_priority' )
				->orderBy( 'CAST(pm1.meta_value AS SIGNED)' );

		$scheduler_rule_ids = $post_query->fetchCol( 'ID' );

		return $scheduler_rule_ids;
	}
}

if ( ! function_exists( 'dey_get_order_local_pickup_scheduler_rules' ) ) {

	/**
	 * Get the order local pickup scheduler rules.
	 *
	 * @since 4.0.0
	 * @param string $post_status Post status.
	 * @return array
	 */
	function dey_get_order_local_pickup_scheduler_rules( $post_status = array() ) {
		global $wpdb;

		if ( ! dey_check_is_array( $post_status ) ) {
			$post_status = dey_get_scheduler_rule_statuses();
		}

		$post_query = new DEY_Query( $wpdb->prefix . 'posts', 'p' );
		$post_query->select( 'DISTINCT p.ID' )
				->leftJoin( $wpdb->prefix . 'postmeta', 'pm1', 'p.ID = pm1.post_id' )
				->where( 'p.post_type', DEY_Register_Post_Types::SCHEDULER_RULE_POSTTYPE )
				->whereIn( 'p.post_status', $post_status )
				->where( 'pm1.meta_key', 'dey_scheduler_type' )
				->whereIn( 'pm1.meta_value', array( 2, 3 ) );

		$scheduler_rule_ids = $post_query->fetchCol( 'ID' );

		return $scheduler_rule_ids;
	}
}

if ( ! function_exists( 'dey_get_order_delivery_time_slots' ) ) {

	/**
	 * Get the order delivery time slots.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_order_delivery_time_slots() {
		return get_posts(
			array(
				'post_type'        => DEY_Register_Post_Types::TIME_SLOTS_POSTTYPE,
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'numberposts'      => '-1',
				'suppress_filters' => false,
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'     => 'dey_time_slot_schedule_type',
						'value'   => '3',
						'compare' => '!=',
					),
					array(
						'key'     => 'dey_time_slot_schedule_type',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
	}
}

if ( ! function_exists( 'dey_get_order_pickup_time_slots' ) ) {

	/**
	 * Get the order pickup time slots.
	 *
	 * @since 4.0.0
	 * @return array
	 */
	function dey_get_order_pickup_time_slots() {
		return get_posts(
			array(
				'post_type'        => DEY_Register_Post_Types::TIME_SLOTS_POSTTYPE,
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'numberposts'      => '-1',
				'suppress_filters' => false,
				'meta_query'       => array(
					'relation' => 'OR',
					array(
						'key'     => 'dey_time_slot_schedule_type',
						'value'   => '2',
						'compare' => '!=',
					),
					array(
						'key'     => 'dey_time_slot_schedule_type',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
	}
}
