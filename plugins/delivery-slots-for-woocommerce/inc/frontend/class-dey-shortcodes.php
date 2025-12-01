<?php
/**
 * Shortcodes.
 *
 * @since 3.1.0
 * */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'DEY_Shortcodes' ) ) {

	/**
	 * Class.
	 *
	 * @since 3.1.0
	 */
	class DEY_Shortcodes {

		/**
		 * Plugin slug.
		 *
		 * @since 3.1.0
		 * @var string
		 * */
		private static $plugin_slug = 'dey';

		/**
		 * Class Initialization.
		 *
		 * @since 3.1.0
		 * @return void
		 * */
		public static function init() {
			/**
			 * This hook is used to alter the load shortcodes.
			 *
			 * @since 3.1.0
			 */
			$shortcodes = apply_filters(
				'dey_load_shortcodes',
				array(
					'dey_order_scheduler_field',
					'dey_product_delivery_field',
					'dey_tip_field',
				)
			);

			foreach ( $shortcodes as $shortcode_name ) {
				add_shortcode( $shortcode_name, array( __CLASS__, 'process_shortcode' ) );
			}
		}

		/**
		 * Process shortcode.
		 *
		 * @since 3.1.0
		 * @param array  $atts
		 * @param string $content
		 * @param string $tag
		 * @return string
		 * */
		public static function process_shortcode( $atts, $content, $tag ) {
			$shortcode_name = str_replace( 'dey_', '', $tag );
			$function       = 'shortcode_' . $shortcode_name;

			switch ( $shortcode_name ) {
				case 'order_scheduler_field':
				case 'tip_field':
					self::$function();
					break;
				case 'product_delivery_field':
					ob_start();
					self::$function( $atts ); // output for shortcode.
					$content = ob_get_contents();
					ob_end_clean();
					break;
				default:
					ob_start();
					/**
					 * This hook is used to display the shortcode content.
					 *
					 * @since 3.1.0
					 */
					do_action( "dey_shortcode_{$shortcode_name}_content" );
					$content = ob_get_contents();
					ob_end_clean();
					break;
			}

			return $content;
		}

		/**
		 * Get product based on shortcode attributes.
		 *
		 * @since 3.1.0
		 * @param array $atts
		 * @return object
		 * */
		public static function get_product_based_on_shortcode_attributes( $atts ) {
			global $product;
			$product_id = isset( $atts['product_id'] ) ? absint( $atts['product_id'] ) : '';

			return '' !== $product_id ? wc_get_product( $product_id ) : $product;
		}

		/**
		 * Shortcode order scheduler field.
		 *
		 * @since 3.1.0
		 * @return void
		 * */
		public static function shortcode_order_scheduler_field() {
			// Return if the cart contains only virtual products.
			if ( ! dey_order_allow_virtual_products_delivery() ) {
				return;
			}

			// Return if the cart contains only product order scheduler products.
			if ( dey_is_cart_contains_product_scheduler_only() ) {
				return;
			}

			// Enqueue the order delivery scripts.
			DEY_Order_Delivery_Handler::enqueue_default_scripts();
			// Enqueue the order local pickup scripts.
			DEY_Scheduler_Rule_Order_Local_Pickup_Handler::enqueue_default_scripts();

			dey_get_template( 'order/order-scheduler.php' );
		}

		/**
		 * Shortcode product delivery field.
		 *
		 * @since 3.1.0
		 * @param array $atts
		 * @return void
		 * */
		public static function shortcode_product_delivery_field( $atts ) {
			$product = self::get_product_based_on_shortcode_attributes( $atts );

			if ( ! is_a( $product, 'WC_Product' ) || ! $product->exists() ) {
				return;
			}

			// Display the product delivery slots.
			DEY_Product_Delivery_Handler::init( $product )->render();
		}

		/**
		 * Shortcode tip field.
		 *
		 * @since 3.1.0
		 * @return void
		 * */
		public static function shortcode_tip_field() {
			// Return if the cart contains only virtual products.
			if (!dey_order_allow_virtual_products_delivery()) {
				return;
			}

			/**
			 * This hook is used to validate the order tip to display.
			 *
			 * @since 1.0
			 */
			if (!apply_filters('dey_is_valid_order_tip', true)) {
				return;
			}

			/**
			 * This hook is used to alter the order tip wrapper file name.
			 *
			 * @since 1.0
			 */
			$file_name = apply_filters('dey_order_tip_wrapper_file_name_shortcode', 'cart-order-tip.php');

			dey_get_template($file_name);
		}
	}

	DEY_Shortcodes::init();
}
