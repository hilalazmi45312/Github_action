<?php
/**
 * Handles the Cart Session.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Cart_Session_Handler' ) ) {

	/**
	 * Class.
	 */
	class DEY_Cart_Session_Handler {

		/**
		 * Storage key.
		 *
		 * @var string
		 */
		public static $storage_key = 'dey_delivery';

		/**
		 * Order tip storage key.
		 *
		 * @var string
		 */
		public static $order_tip_storage_key = 'dey_order_tip';

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Clear the cart session.
			add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'destroy_cart_session' ), 99999 );
			// Clear the cart session on block checkout.
			add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'destroy_cart_session' ), 99999 );
		}

		/**
		 * Indicates if the WooCommerce session was started.
		 *
		 * @return bool
		 */
		public static function has_session() {
			return ! empty( WC()->session ) && is_object( WC()->session ) && WC()->session->has_session();
		}

		/**
		 * Set the session.
		 *
		 * @return void
		 */
		public static function set( $key, $value ) {
			$session_data = self::get_session_data();
			if ( ! dey_check_is_array( $session_data ) ) {
				$session_data = array();
			}

			$session_data[ $key ] = $value;

			self::set_session_data( $session_data );
		}

		/**
		 * Get the session.
		 *
		 * @return array
		 */
		public static function get( $key, $default = array() ) {

			$session_data = self::get_session_data();
			if ( ! dey_check_is_array( $session_data ) || ! isset( $session_data[ $key ] ) ) {
				return $default;
			}

			return $session_data[ $key ];
		}

		/**
		 * Delete the session.
		 *
		 * @return array
		 */
		public static function delete( $key ) {

			$session_data = self::get_session_data();
			if ( dey_check_is_array( $session_data ) && isset( $session_data[ $key ] ) ) {
				unset( $session_data[ $key ] );
			}

			self::set_session_data( $session_data );
		}

		/**
		 * Safely save data to the session.
		 *
		 * @return void
		 */
		public static function set_session_data( $value ) {
			if ( ! self::has_session() ) {
				return;
			}

			WC()->session->set( self::$storage_key, $value );
		}

		/**
		 * Safely retrieve data from the session.
		 *
		 * @return mixed
		 */
		public static function get_session_data( $default = null ) {
			if ( ! self::has_session() ) {
				return $default;
			}

			$data = WC()->session->get( self::$storage_key );

			return empty( $data ) ? $default : $data;
		}

		/**
		 * Safely save order tip data to the session.
		 *
		 * @return void
		 */
		public static function set_order_tip_session_data( $value ) {
			if ( ! self::has_session() ) {
				return;
			}

			WC()->session->set( self::$order_tip_storage_key, $value );
		}

		/**
		 * Safely retrieve order tip data from the session.
		 *
		 * @return mixed
		 */
		public static function get_order_tip_session_data( $default = null ) {
			if ( ! self::has_session() ) {
				return $default;
			}

			$data = WC()->session->get( self::$order_tip_storage_key );

			return empty( $data ) ? $default : $data;
		}

		/**
		 * Delete the order tip data from the session.
		 *
		 * @return mixed
		 */
		public static function delete_order_tip_session_data() {
			if ( ! self::has_session() ) {
				return $default;
			}

			WC()->session->set( self::$order_tip_storage_key, null );
		}

		/**
		 * Destroy the session.
		 *
		 * @return void
		 */
		public static function destroy_cart_session() {
			// Delivery.
			self::destroy_delivery_cart_session();

			// Order tip.
			WC()->session->set( self::$order_tip_storage_key, null );
		}

		/**
		 * Destroy the delivery cart session.
		 *
		 * @return void
		 */
		public static function destroy_delivery_cart_session() {
			WC()->session->set( self::$storage_key, null );
		}

		/**
		 * Destroy the order scheduler data from session.
		 *
		 * @since 4.0.0
		 * @return void
		 */
		public static function destroy_order_scheduler_data() {
			$session_data = self::get_session_data();
			if ( ! dey_check_is_array( $session_data ) ) {
				return;
			}

			$order_scheduler_data_keys = self::get_destroy_order_scheduler_data_keys();
			if ( ! dey_check_is_array( $order_scheduler_data_keys ) ) {
				return;
			}

			foreach ( $order_scheduler_data_keys as $order_scheduler_data_key ) {
				if ( isset( $session_data[ $order_scheduler_data_key ] ) ) {
					unset( $session_data[ $order_scheduler_data_key ] );
				}
			}

			self::set_session_data( $session_data );
		}

		/**
		 * Order Scheduler data keys to destroy cart session.
		 *
		 * @since 4.0.0
		 * @return array
		 */
		private static function get_destroy_order_scheduler_data_keys() {
			/**
			 * This hook is used to alter the order scheduler data keys to destroy cart session.
			 *
			 * @since 4.0.0
			 * @param array Order scheduler data keys.
			 */
			return apply_filters(
				'dey_cart_session_order_scheduler_data_keys',
				array(
					'order_delivery_date',
					'order_local_pickup_date',
					'order_pickup_location',
					'order_delivery_time_slot',
					'order_local_pickup_time_slot',
					'weekday',
					'same_day_price',
					'next_day_price',
					'special_day',
				)
			);
		}
	}

	DEY_Cart_Session_Handler::init();
}
