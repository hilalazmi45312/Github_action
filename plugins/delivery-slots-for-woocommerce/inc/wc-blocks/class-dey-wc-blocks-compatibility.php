<?php

/**
 * WooCommerce Blocks Compatibility.
 *
 * @since 3.7.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('DEY_WC_Blocks_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 3.7.0
	 */
	class DEY_WC_Blocks_Compatibility {

		/**
		 * Class initialization.
		 * 
		 * @since 3.7.0
		 */
		public static function init() {
			add_action('woocommerce_blocks_loaded', array( __CLASS__, 'register_integration' ), 10);
		}

		/**
		 * Register integration.
		 * 
		 * @since 3.7.0
		 */
		public static function register_integration() {
			self::initialize();

			/**
			 * This hook is used to alter the compatible block names.
			 * 
			 * @since 3.7.0
			 */
			$compatible_block_names = apply_filters('dey_compatible_block_names', array( 'cart', 'checkout' ));

			foreach ($compatible_block_names as $block_name) {
				add_action(
						"woocommerce_blocks_{$block_name}_block_registration",
						function ( $registry ) {
							$registry->register(DEY_WC_Blocks_Integration::instance());
						}
				);
			}
		}

		/**
		 * Initialize require files and store API.
		 * 
		 * @since 3.7.0
		 */
		private static function initialize() {
			// Require files.
			include_once DEY_ABSPATH . 'inc/wc-blocks/class-dey-wc-blocks-integration.php';
			include_once DEY_ABSPATH . 'inc/wc-blocks/class-dey-wc-blocks-store-api.php';

			// Initialize the store API.
			DEY_WC_Blocks_Store_API::init();
		}
	}

	DEY_WC_Blocks_Compatibility::init();
}
