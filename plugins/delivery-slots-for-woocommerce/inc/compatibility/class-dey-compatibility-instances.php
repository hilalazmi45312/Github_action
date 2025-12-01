<?php

/**
 * Compatibility instances class.
 *
 * @since 3.7.0
 */
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'DEY_Compatibility_Instances' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.7.0
	 */
	class DEY_Compatibility_Instances {

		/**
		 * Compatibilities.
		 *
		 * @since 3.7.0
		 * @var array
		 * */
		private static $compatibilities;

		/**
		 * Get compatibilities.
		 *
		 * @since 3.7.0
		 * @return array
		 */
		public static function instance() {
			if ( is_null( self::$compatibilities ) ) {
				self::$compatibilities = self::load_compatibilities();
			}

			return self::$compatibilities;
		}

		/**
		 * Load all compatibilities.
		 *
		 * @since 3.7.0
		 */
		public static function load_compatibilities() {
			if ( ! class_exists( 'DEY_Compatibility' ) ) {
				include DEY_PLUGIN_PATH . '/inc/abstracts/abstract-dey-compatibility.php';
			}

			$default_compatibility_classes = array(
				'wpml'                                   => 'DEY_WPML_Compatibility',
				'woocommerce-pdf-invoices-packing-slips' => 'DEY_WooCommerce_PDF_Invoices_Packing_Slips_Compatibility',
				'advanced-cancel-order'                  => 'DEY_Advanced_Cancel_Order_Compatibility',
				'woocommerce-multi-step-checkout'        => 'DEY_WC_Multi_Step_Checkout_Compatibility',
			);

			foreach ( $default_compatibility_classes as $file_name => $compatibility_class ) {
				// Include file.
				include 'class-' . $file_name . '.php';

				// Add compatibility.
				self::add_compatibility( new $compatibility_class() );
			}
		}

		/**
		 * Add a Compatibility.
		 *
		 * @since 3.7.0
		 * @param object $compatibility Compatibility object.
		 * @return object
		 */
		public static function add_compatibility( $compatibility ) {
			self::$compatibilities[ $compatibility->get_id() ] = $compatibility;

			return new self();
		}

		/**
		 * Get compatibility by id.
		 *
		 * @since 3.7.0
		 * @param object $compatibility_id compatibility ID.
		 * @return object
		 */
		public static function get_compatibility_by_id( $compatibility_id ) {
			$compatibilities = self::instance();

			return isset( $compatibilities[ $compatibility_id ] ) ? $compatibilities[ $compatibility_id ] : false;
		}
	}

}
