<?php
/**
 * Compatibility - WooCommerce AvaTax
 *
 * Tested upto: 2.6.1
 *
 * @since 5.3.0
 * @link https://woo.com/products/woocommerce-avatax/
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'EFW_WooCommerce_AvaTax_Compatibility' ) ) {

	/**
	 * Class.
	 *
	 * @since 5.3.0
	 */
	class EFW_WooCommerce_AvaTax_Compatibility extends EFW_Compatibility {

		/**
		 * Class constructor.
		 *
		 * @since 5.3.0
		 */
		public function __construct() {
			$this->id = 'woocommerce_avatax';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 5.3.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists( 'WC_AvaTax' );
		}

		/**
		 * Actions.
		 *
		 * @since 5.3.0
		 */
		public function actions() {
			// Alter tax classes.
			add_filter( 'efw_tax_classes', array( __CLASS__, 'alter_tax_classes' ), 10, 1 );
		}

		/**
		 * Alter tax classes.
		 *
		 * @since 5.3.0
		 * @param array $tax_classes Tax classes.
		 * @return array
		 */
		public static function alter_tax_classes( $tax_classes ) {
			if ( 'yes' !== get_option( 'wc_avatax_enable_tax_calculation' ) ) {
				return $tax_classes;
			}

			$tax_classes['avatax'] = __( 'AvaTax', 'extra-fees-for-woocommerce' );

			return $tax_classes;
		}
	}

}
