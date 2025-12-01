<?php
/**
 * Compatibility file for WooCommerce Polylang
 *
 * @author      StoreApps
 * @since       9.21.0
 * @version     1.0.0
 *
 * @package     woocommerce-smart-coupons/includes/compat/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_Polylang_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with WooCommerce Polylang
	 */
	class WC_SC_Polylang_Compatibility {

		/**
		 * Variable to hold instance of WC_SC_Polylang_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_filter( 'wc_sc_auto_apply_coupons_cart_category_ids', array( $this, 'add_translated_categories_in_query' ), 10, 2 );

			add_filter( 'woocommerce_coupon_get_product_categories', array( $this, 'add_translated_categories_in_query' ), 10, 2 );
		}

		/**
		 * Get single instance of WC_SC_Polylang_Compatibility
		 *
		 * @return WC_SC_Polylang_Compatibility Singleton object of WC_SC_Polylang_Compatibility
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Add translated category IDs for the current language to the list of auto-apply category IDs.
		 *
		 * @param array  $cart_category_ids The original category IDs.
		 * @param object $product The product being processed.
		 * @return array Modified list of category IDs, including translations.
		 */
		public function add_translated_categories_in_query( $cart_category_ids, $product = null ) {

			if ( ! apply_filters( 'enable_wc_sc_polylang_compatibility', false ) ) {
				return $cart_category_ids;
			}

			// Ensure the Polylang plugin function `pll_get_term` is available.
			if ( ! function_exists( 'pll_get_term' ) ) {
				wc_doing_it_wrong(
					__METHOD__,
					__( 'Polylang plugin is required for this functionality.', 'woocommerce-smart-coupons' ),
					'9.21.0'
				);
				return $cart_category_ids;
			}

			// Ensure cart category IDs are valid and not empty.
			if ( empty( $cart_category_ids ) || ! is_array( $cart_category_ids ) ) {
				return $cart_category_ids;
			}

			// Fetch all available Polylang languages.
			$languages = pll_languages_list();
			if ( empty( $languages ) || ! is_array( $languages ) ) {
				return $cart_category_ids; // No languages available, return original IDs.
			}

			foreach ( $cart_category_ids as $category_id ) {
				foreach ( $languages as $language ) {
					// Get the translated category ID for the current language.
					$translated_category_id = pll_get_term( $category_id, $language );

					// Add translated category ID if valid and not already in the list.
					if ( ! empty( $translated_category_id ) && ! in_array( $translated_category_id, $cart_category_ids, true ) ) {
						$cart_category_ids[] = $translated_category_id;
					}
				}
			}

			return $cart_category_ids;
		}

	}

}

WC_SC_Polylang_Compatibility::get_instance();
