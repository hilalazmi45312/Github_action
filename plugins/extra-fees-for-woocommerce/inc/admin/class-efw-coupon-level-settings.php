<?php
/**
 * Coupon Level Settings.
 *
 * @package Extra Fees for WooCommerce/Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Coupon_Level_Settings' ) ) {

	/**
	 * Class EFW_Coupon_Level_Settings.
	 */
	class EFW_Coupon_Level_Settings {

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add Fee Settings for Coupon.
			add_filter( 'woocommerce_coupon_data_tabs', array( __CLASS__, 'fee_settings_tab' ), 10 );
			add_action( 'woocommerce_coupon_data_panels', array( __CLASS__, 'fee_settings' ), 10, 2 );
			add_action( 'woocommerce_coupon_options_save' , array( __CLASS__, 'save_coupon_settings' ) , 10 , 2 ) ;
		}

		/**
		 * Fee Settings for Coupon.
		 */
		public static function fee_settings_tab( $tabs ) {
			if ( ! is_admin() ) {
				return $tabs;
			}

			if ( 'yes' !== get_option( 'efw_productfee_enable' ) && 'yes' !== get_option( 'efw_gatewayfee_enable' ) && 'yes' !== get_option( 'efw_ordertotalfee_enable' ) && 'yes' !== get_option( 'efw_shippingfee_enable' )) {
				return $tabs;
			}

			$fee_tab = array(
				'extra_fees'           => array(
					'label'  => __( 'Extra Fees', 'extra-fees-for-woocommerce' ),
					'target' => 'efw_fee_coupon_data',
					'class'  => 'efw_fee_coupon_data',
				),
			);

			return array_merge($tabs, $fee_tab);
		}

		/**
		 * Fee Settings for Coupon.
		 */
		public static function fee_settings( $coupon_id, $coupon ) {
			include EFW_ABSPATH . 'inc/admin/menu/views/html-coupon-restrictions.php';
		}

		/**
		 * Save Simple Product Settings.
		 */
		public static function save_coupon_settings( $coupon_id, $coupon ) {
			if ( ! is_admin() ) {
				return ;
			}

			$enable_product_fee = isset($_REQUEST['efw_product_fee']) ? 'yes' : 'no';
			$enable_gateway_fee = isset($_REQUEST['efw_gateway_fee']) ? 'yes' : 'no';
			$enable_order_fee = isset($_REQUEST['efw_order_fee']) ? 'yes' : 'no';
			$enable_shipping_fee = isset($_REQUEST['efw_shipping_fee']) ? 'yes' : 'no';
			update_post_meta( $coupon_id , '_efw_enable_product_fee' , $enable_product_fee ) ;
			update_post_meta( $coupon_id , '_efw_enable_gateway_fee' , $enable_gateway_fee ) ;
			update_post_meta( $coupon_id , '_efw_enable_order_fee' , $enable_order_fee ) ;
			update_post_meta( $coupon_id , '_efw_enable_shipping_fee' , $enable_shipping_fee ) ;
		}
	}

	EFW_Coupon_Level_Settings::init();
}
