<?php
/**
 * Compatibility file for CheckoutWC.
 *
 * @author      StoreApps
 * @since       8.11.0
 * @version     1.0.0
 *
 * @package     woocommerce-smart-coupons/includes/compat/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_SC_CFW_Compatibility' ) ) {

	/**
	 * Class for handling compatibility with CheckoutWC.
	 */
	class WC_SC_CFW_Compatibility {

		/**
		 * Singleton instance.
		 *
		 * @var WC_SC_CFW_Compatibility|null
		 */
		private static $instance = null;

		/**
		 * Get instance.
		 *
		 * @return WC_SC_CFW_Compatibility
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			add_action( 'init', array( $this, 'register_hooks' ) );
		}

		/**
		 * Register hooks for compatibility.
		 */
		public function register_hooks() {
			if ( defined( 'CFW_VERSION' ) ) {
				add_filter( 'wc_sc_checkout_coupon_success_js', array( $this, 'filter_checkout_coupon_success_js' ), 10, 2 );
				add_filter( 'wc_sc_should_use_block_coupon_js', '__return_false' );

				add_action(
					'cfw_checkout_main_container_start',
					array( WC_SC_Display_Coupons::get_instance(), 'show_available_coupons_before_checkout_form' )
				);
			}
		}

		/**
		 * JS override for coupon application in CheckoutWC.
		 *
		 * @param string $js   Existing JS code.
		 * @param array  $args Additional context.
		 * @return string Modified JS.
		 */
		public function filter_checkout_coupon_success_js( $js, $args ) {
			// Return default if CFW is not active.
			if ( empty( $args['cfw_active'] ) ) {
				return $js;
			}

			$custom_js = <<<JS
success: function( response ) {
	if ( response ) {
		jQuery( '.woocommerce-error, .woocommerce-message' ).remove();
		response = response.replace(/woocommerce-message/g, 'message');
		var notice_html = jQuery('<div>', {
			class: 'cfw-alert cfw-alert-success',
			html: response
		});

		if (jQuery('.woocommerce-notices-wrapper').length) {
			jQuery('.woocommerce-notices-wrapper').html(notice_html);
		}

		jQuery( document.body ).trigger( 'update_checkout', { update_shipping_method: false } );
	}
},
complete: function() {
	jQuery( '.sc-coupons-list' ).removeClass( 'processing' ).unblock();
},
JS;

			return $custom_js;
		}
	}
}

WC_SC_CFW_Compatibility::get_instance();
