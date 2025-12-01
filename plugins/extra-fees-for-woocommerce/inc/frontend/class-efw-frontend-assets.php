<?php

/**
 * Enqueue Front End Enqueue Files
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'EFW_Frontend_Assets' ) ) {

	/**
	 * EFW_Frontend_Assets Class.
	 */
	class EFW_Frontend_Assets {

		/**
		 * EFW_Frontend_Assets Class Initialization.
		 */
		public static function init() {
			add_action( 'wp_enqueue_scripts' , array( __CLASS__, 'external_js_files' ) , 99 ) ;
			add_action( 'wp_enqueue_scripts' , array( __CLASS__, 'external_css_files' ) ) ;
		}

		/**
		 * Enqueue Front end required JS files.
		 */
		public static function external_js_files() {
			// delete_option('efw_report_updated');
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ;

			$enqueue_array = array(
				'efw-frontend' => array(
					'callable' => array( 'EFW_Frontend_Assets', 'frontend_script' ),
					'restrict' => ( is_shop() || is_product() || is_cart() || is_checkout() || is_order_received_page() ),
				),
				'efw-wc-blocks' => array(
					'callable' => array( 'EFW_Frontend_Assets', 'efw_blocks_script' ),
					'restrict' => true,
				),
					) ;
						/**
						 * Hook:efw_frontend_enqueue_scripts. 
						 *
						 * @since 1.0
						 */
			$enqueue_array = apply_filters( 'efw_frontend_enqueue_scripts' , $enqueue_array ) ;

			if ( ! efw_check_is_array( $enqueue_array ) ) {
				return ;
			}

			foreach ( $enqueue_array as $key => $enqueue ) {
				if ( ! efw_check_is_array( $enqueue ) ) {
					continue ;
				}

				if ( $enqueue[ 'restrict' ] ) {
					call_user_func_array( $enqueue[ 'callable' ] , array() ) ;
				}
			}
						/**
						 * Hook: efw_frontend_after_enqueue_js. 
						 *
						 * @since 1.0
						 */
			do_action( 'efw_frontend_after_enqueue_js' , $suffix ) ;
		}

		/**
		 * Enqueue Section Frontend scripts.
		 */
		public static function frontend_script() {
			global $wp;
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ;
			// JQuery Modal
			wp_register_script( 'jquery-modal' , EFW_PLUGIN_URL . '/assets/js/jquery.modal' . $suffix . '.js' , array( 'jquery' ) , EFW_VERSION ) ;
			wp_enqueue_script( 'efw-frontend-script' , EFW_PLUGIN_URL . '/assets/js/frontend/frontend.js' , array( 'jquery', 'jquery-modal' ) , EFW_VERSION ) ;
			wp_localize_script(
					'efw-frontend-script' , 'efw_frontend_param' , array(
				'fee_nonce'   => wp_create_nonce( 'efw-fee-nonce' ),
				'booking_nonce'   => wp_create_nonce( 'efw-booking-nonce' ),
				'fee_desc_rule_popup_nonce'   => wp_create_nonce( 'efw-fee-desc-rule-popup-nonce' ),            
				'is_enabled'  => get_option( 'efw_productfee_enable' ),
				'is_gateway_fee_enabled' => get_option('efw_gatewayfee_enable'),            
				'is_checkout' => is_checkout(),
				'is_pay_for_order_page' => is_wc_endpoint_url( 'order-pay' ),
				'order_id' => isset($wp->query_vars['order-pay']) ? absint( $wp->query_vars['order-pay'] ) : 0,
				'is_product'  => is_product(),
				'ajaxurl'     => EFW_ADMIN_AJAX_URL,
				'fee_desc_popup_nonce' => wp_create_nonce( 'efw-fee-desc-popup-nonce' ),
				'fee_gateway_desc_popup_nonce' => wp_create_nonce( 'efw-fee-gateway-desc-popup-nonce' ),
				'fee_order_desc_popup_nonce' => wp_create_nonce( 'efw-fee-order-desc-popup-nonce' ),
				'combined_fee_desc_popup_nonce' => wp_create_nonce( 'efw-combined-fee-desc-popup-nonce' ),
				'fee_shipping_desc_popup_nonce' => wp_create_nonce( 'efw-fee-shipping-desc-popup-nonce' ),
					)
			) ;
		}

		/**
		 * Enqueue Section Frontend scripts.
		 */
		public static function efw_blocks_script() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ;
			// JQuery Modal
			wp_enqueue_script( 'efw-blocks-script' , EFW_PLUGIN_URL . '/assets/js/frontend/efw-wc-blocks.js' , array( 'jquery', 'jquery-modal', 'wc-blocks-checkout' ) , EFW_VERSION , true ) ;
			wp_localize_script(
					'efw-blocks-script' , 'efw_blocks_param' , array(
				'is_gateway_fee_enabled' => get_option('efw_gatewayfee_enable'),            
				'is_checkout' => is_checkout(),
				'ajaxurl'     => EFW_ADMIN_AJAX_URL,
					)
			) ;
		}
				
				/**
		 * Enqueue external CSS files.
		 * */
		public static function external_css_files() {
			// Frontend.
			wp_enqueue_style( 'efw-frontend', EFW_PLUGIN_URL . '/assets/css/frontend.css', array( 'dashicons' ), EFW_VERSION );

			wp_register_style( 'efw-inline-style' , false , array() , EFW_VERSION ) ; // phpcs:ignore
			wp_enqueue_style( 'efw-inline-style' );

			// Add inline style.
			self::add_inline_style();

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ;
			wp_enqueue_style( 'jquery-modal' , EFW_PLUGIN_URL . '/assets/css/jquery.modal' . $suffix . '.css' , array() , EFW_VERSION ) ;       
		}

		/**
		 * Add Inline style.
		 */
		public static function add_inline_style() {
			$contents = get_option( 'efw_advance_custom_css', '' );

			if ( ! $contents ) {
				return;
			}

			// Add custom css as inline style.
			wp_add_inline_style( 'efw-inline-style', $contents );
		}
	}

	EFW_Frontend_Assets::init() ;
}
