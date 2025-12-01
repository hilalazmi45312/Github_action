<?php
/**
 * Notifications Instance Class.
 *
 * @since 1.0.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Notification_Instances' ) ) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 * */
	class DEY_Notification_Instances {

		/**
		 * Notifications.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		private static $notifications = array();

		/**
		 * Get the all notifications.
		 *
		 * @since 1.0.0
		 * @return array
		 * */
		public static function get_notifications() {
			if ( ! self::$notifications ) {
				self::load_notifications();
			}

			return self::$notifications;
		}

		/**
		 * Load all notifications.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function load_notifications() {
			if ( ! class_exists( 'DEY_Notifications' ) ) {
				include DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-notifications.php';
			}

			$default_notification_classes = array(
				'customer-today-order-delivery-without-slot' => 'DEY_Customer_Today_Order_Delivery_Without_Slot_Notification',
				'customer-today-order-delivery-with-slot'  => 'DEY_Customer_Today_Order_Delivery_With_Slot_Notification',
				'customer-today-order-pickup-without-slot' => 'DEY_Customer_Today_Order_Pickup_Without_Slot_Notification',
				'customer-today-order-pickup-with-slot'    => 'DEY_Customer_Today_Order_Pickup_With_Slot_Notification',
				'customer-today-product-delivery-without-slot' => 'DEY_Customer_Today_Product_Delivery_Without_Slot_Notification',
				'customer-today-product-delivery-with-slot' => 'DEY_Customer_Today_Product_Delivery_With_Slot_Notification',
				'customer-product-pickup-without-slot'     => 'DEY_Customer_Product_Pickup_Without_Slot_Notification',
				'customer-product-pickup-with-slot'        => 'DEY_Customer_Product_Pickup_With_Slot_Notification',
				'admin-product-pickup-without-slot'        => 'DEY_Admin_Product_Pickup_Without_Slot_Notification',
				'admin-product-pickup-with-slot'           => 'DEY_Admin_Product_Pickup_With_Slot_Notification',
			);

			foreach ( $default_notification_classes as $file_name => $notification_class ) {
				// Include notification file.
				include 'class-' . $file_name . '.php';

				// Add notification Object.
				self::add_notification( new $notification_class() );
			}
		}

		/**
		 * Add a notification.
		 *
		 * @since 1.0.0
		 * @param object $notification Notification object.
		 * */
		public static function add_notification( $notification ) {
			self::$notifications[ $notification->get_id() ] = $notification;
		}

		/**
		 * Get the notification by id.
		 *
		 * @since 1.0.0
		 * @param int $notification_id Notification ID.
		 * @return object|bool
		 */
		public static function get_notification_by_id( $notification_id ) {
			$notifications = self::get_notifications();

			return isset( $notifications[ $notification_id ] ) ? $notifications[ $notification_id ] : false;
		}

		/**
		 * Reset.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public static function reset() {
			self::$notifications = null;
		}
	}

}
