<?php

/**
 * Compatibility Instances Class.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Compatibility_Instances' ) ) {

	/**
	 * Class EFW_Compatibility_Instances
	 */
	class EFW_Compatibility_Instances {

		/**
		 * Compatibilities.
		 * 
		 * @var array
		 * */
		private static $compatibilities ;

		/**
		 * Get Compatibilities.
		 * 
		 * @var array
		 */
		public static function instance() {
			if ( is_null( self::$compatibilities ) ) {
				self::$compatibilities = self::load_compatibilities() ;
			}

			return self::$compatibilities ;
		}

		/**
		 * Load all Compatibilities.
		 */
		public static function load_compatibilities() {

			if ( ! class_exists( 'EFW_Compatibility' ) ) {
				include EFW_PLUGIN_PATH . '/inc/abstracts/abstract-efw-compatibility.php' ;
			}

			$default_compatibility_classes = array(
				'wpml'             => 'EFW_WPML_Compatibility',
				'wc-subscriptions' => 'EFW_WooCommerce_Subscription_Compatibility',
				'wc-avatax'        => 'EFW_WooCommerce_AvaTax_Compatibility',
				'ppn-payment-plan-suites' => 'EFW_Payment_Plan_Compatibility',
				'woocommerce-booking' => 'EFW_WC_Booking_Compatibility',
			);

			foreach ( $default_compatibility_classes as $file_name => $compatibility_class ) {

				// Include file.
				include 'class-' . $file_name . '.php' ;

				// Add compatibility.
				self::add_compatibility( new $compatibility_class() ) ;
			}
		}

		/**
		 * Add a Compatibility.
		 *
		 * @since 1.0.0
		 * @param object $compatibility Compatibility object.
		 * @return object
		 */
		public static function add_compatibility( $compatibility ) {
			self::$compatibilities[ $compatibility->get_id() ] = $compatibility ;

			return new self() ;
		}

		/**
		 * Get compatibility by id.
		 *
		 * @since 1.0.0
		 * @param int|string $module_id Module ID.
		 */
		public static function get_compatibility_by_id( $module_id ) {
			$compatibilities = self::instance() ;

			return isset( $compatibilities[ $compatibility_id ] ) ? $compatibilities[ $compatibility_id ] : false ;
		}
	}

}
