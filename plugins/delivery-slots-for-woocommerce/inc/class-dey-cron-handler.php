<?php
/**
 * Handles the Cron.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Cron_Handler' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 * */
	class DEY_Cron_Handler {

		/**
		 * Class initialization.
		 *
		 * @since 1.0.0
		 * */
		public static function init() {
			// Maybe set the WP schedule event.
			add_action( 'init', array( __CLASS__, 'maybe_set_wp_schedule_event' ), 10 );
			// Handle the delivery emails.
			add_action( 'dey_delivery_emails', array( __CLASS__, 'handle_wp_cron' ) );
		}

		/**
		 * Maybe set the WP schedule event.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function maybe_set_wp_schedule_event() {
			// Check if the delivery schedule event is already exists.
			if ( wp_next_scheduled( 'dey_delivery_emails' ) ) {
				return;
			}

			// Add the delivery emails schedule event.
			wp_schedule_event( time(), 'hourly', 'dey_delivery_emails' );
		}

		/**
		 *  Handles the WP cron.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function handle_wp_cron() {
			// Update the WP cron current date.
			update_option( 'dey_update_wp_cron_last_updated_date', DEY_Date_Time::get_mysql_date_time_format( 'now', true ) );
			// Maybe handle the order delivery emails.
			self::maybe_handle_order_delivery_emails();
			// Maybe handle the order local pickup emails.
			self::maybe_handle_order_local_pickup_emails();
			// Maybe handle the product delivery emails.
			self::maybe_handle_product_delivery_emails();
			// Maybe handle the product pickup emails.
			self::maybe_handle_product_pickup_emails();
		}

		/**
		 * Maybe handle the order delivery emails.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function maybe_handle_order_delivery_emails() {
			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			$args                = array(
				'post_type'   => DEY_Register_Post_Types::ORDER_DELIVERY_POSTTYPE,
				'post_status' => 'dey_upcoming',
				'fields'      => 'ids',
				'numberposts' => '-1',
				'meta_query'  => array(
					array(
						'key'     => 'dey_delivery_mode',
						'value'   => '2',
						'compare' => '!=',
					),
					array(
						'key'     => 'dey_delivery_date',
						'value'   => $current_date_object->format( 'Y-m-d 00:00:00' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'dey_delivery_date',
						'value'   => $current_date_object->format( 'Y-m-d 23:59:59' ),
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			);

			$post_ids = get_posts( $args );
			// Return if the post ids is not exists.
			if ( ! dey_check_is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$order_delivery = dey_get_order_delivery( $post_id );
				if ( ! $order_delivery->exists() ) {
					continue;
				}

				// return if the email is already sent.
				if ( 'yes' === $order_delivery->get_delivery_email_reminder_sent() ) {
					continue;
				}

				/**
				 * This hook is used to send the order delivery reminder email to the user.
				 *
				 * @hooked DEY_Customer_Today_Order_Delivery_With_Slot_Notification->trigger - 10
				 * @hooked DEY_Customer_Today_Order_Delivery_Without_Slot_Notification->trigger - 10
				 * @since 1.0
				 */
				do_action( 'dey_order_delivery_reminder_email', $order_delivery );
			}
		}

		/**
		 * Maybe handle the product delivery emails.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function maybe_handle_product_delivery_emails() {
			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			$args                = array(
				'post_type'   => DEY_Register_Post_Types::PRODUCT_DELIVERY_POSTTYPE,
				'post_status' => 'dey_upcoming',
				'fields'      => 'ids',
				'numberposts' => '-1',
				'meta_query'  => array(
					array(
						'key'     => 'dey_delivery_mode',
						'value'   => '2',
						'compare' => '!=',
					),
					array(
						'key'     => 'dey_delivery_date',
						'value'   => $current_date_object->format( 'Y-m-d 00:00:00' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'dey_delivery_date',
						'value'   => $current_date_object->format( 'Y-m-d 23:59:59' ),
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			);

			$post_ids = get_posts( $args );
			// Return if the post ids is not exists.
			if ( ! dey_check_is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$product_delivery = dey_get_product_delivery( $post_id );
				if ( ! $product_delivery->exists() ) {
					continue;
				}

				// return if the email is already sent.
				if ( 'yes' === $product_delivery->get_delivery_email_reminder_sent() ) {
					continue;
				}

				/**
				 * This hook is used to send the product delivery reminder email to the user.
				 *
				 * @hooked DEY_Customer_Today_Product_Delivery_With_Slot_Notification->trigger - 10
				 * @hooked DEY_Customer_Today_Product_Delivery_Without_Slot_Notification->trigger - 10
				 * @since 1.0
				 */
				do_action( 'dey_product_delivery_reminder_email', $product_delivery );
			}
		}

		/**
		 * Maybe handle the product pickup emails.
		 *
		 * @since 3.5.0
		 * @return void
		 */
		public static function maybe_handle_product_pickup_emails() {
			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			$args                = array(
				'post_type'   => DEY_Register_Post_Types::PRODUCT_LOCAL_PICKUP_POSTTYPE,
				'post_status' => 'dey_upcoming',
				'fields'      => 'ids',
				'numberposts' => '-1',
				'meta_query'  => array(
					array(
						'key'     => 'dey_pickup_date',
						'value'   => $current_date_object->format( 'Y-m-d 00:00:00' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'dey_pickup_date',
						'value'   => $current_date_object->format( 'Y-m-d 23:59:59' ),
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			);

			$post_ids = get_posts( $args );
			// Return if the post ids is not exists.
			if ( ! dey_check_is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$product_pickup = dey_get_product_local_pickup( $post_id );
				if ( ! $product_pickup->exists() ) {
					continue;
				}

				// return if the email is already sent.
				if ( 'yes' === $product_pickup->get_pickup_email_reminder_sent() ) {
					continue;
				}

				/**
				 * This hook is used to send the product pickup reminder email to the user.
				 *
				 * @since 3.5.0
				 */
				do_action( 'dey_product_pickup_reminder_email', $product_pickup );
			}
		}

		/**
		 * Maybe handle the order local pickup emails.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function maybe_handle_order_local_pickup_emails() {
			$current_date_object = DEY_Date_Time::get_date_time_object( 'now' );
			$args                = array(
				'post_type'   => DEY_Register_Post_Types::ORDER_LOCAL_PICKUP_POSTTYPE,
				'post_status' => 'dey_upcoming',
				'fields'      => 'ids',
				'numberposts' => '-1',
				'meta_query'  => array(
					array(
						'key'     => 'dey_pickup_date',
						'value'   => $current_date_object->format( 'Y-m-d 00:00:00' ),
						'compare' => '>=',
						'type'    => 'DATETIME',
					),
					array(
						'key'     => 'dey_pickup_date',
						'value'   => $current_date_object->format( 'Y-m-d 23:59:59' ),
						'compare' => '<=',
						'type'    => 'DATETIME',
					),
				),
			);

			$post_ids = get_posts( $args );

			// Return if the post ids is not exists.
			if ( ! dey_check_is_array( $post_ids ) ) {
				return;
			}

			foreach ( $post_ids as $post_id ) {
				$order_local_pickup = dey_get_order_local_pickup( $post_id );
				if ( ! $order_local_pickup->exists() ) {
					continue;
				}

				// return if the email is already sent.
				if ( 'yes' === $order_local_pickup->get_pickup_email_reminder_sent() ) {
					continue;
				}

				/**
				 * This hook is used to send the order local pickup reminder email to the user.
				 *
				 * @hooked DEY_Customer_Today_Order_Pickup_With_Slot_Notification->trigger - 10
				 * @hooked DEY_Customer_Today_Order_Pickup_Without_Slot_Notification->trigger - 10
				 * @since 1.0
				 */
				do_action( 'dey_order_local_pickup_reminder_email', $order_local_pickup );
			}
		}
	}

	DEY_Cron_Handler::init();
}
