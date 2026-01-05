<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Paid Membership Pro Detection
 */
if ( ! function_exists( 'bwfan_is_paid_membership_pro_active' ) ) {
	function bwfan_is_paid_membership_pro_active() {
		return BWFAN_PRO_Plugin_Dependency::paid_membership_active_check();
	}
}

/**
 * Thrive Lead Form Detection
 */
if ( ! function_exists( 'bwfan_is_tve_active' ) ) {
	function bwfan_is_tve_active() {
		return BWFAN_PRO_Plugin_Dependency::tve_active_check();
	}
}
/**
 * Thrive Lead architech Detection
 */
if ( ! function_exists( 'bwfan_is_tve_architect_active' ) ) {
	function bwfan_is_tve_architect_active() {
		return BWFAN_PRO_Plugin_Dependency::tve_architect_active_check();
	}
}

/**
 * Learndash Detection
 */
if ( ! function_exists( 'bwfan_is_learndash_active' ) ) {
	function bwfan_is_learndash_active() {
		return BWFAN_PRO_Plugin_Dependency::learndash_active_check();
	}
}

/**
 * Ninja Forms Detection
 */
if ( ! function_exists( 'bwfan_is_ninja_forms_active' ) ) {
	function bwfan_is_ninja_forms_active() {
		return BWFAN_PRO_Plugin_Dependency::ninja_forms_active_check();
	}
}

/**
 * Fluent Forms Detection
 */
if ( ! function_exists( 'bwfan_is_fluent_forms_active' ) ) {
	function bwfan_is_fluent_forms_active() {
		return BWFAN_PRO_Plugin_Dependency::fluent_forms_active_check();
	}
}

/**
 * Caldera Forms Detection
 */
if ( ! function_exists( 'bwfan_is_caldera_forms_active' ) ) {
	function bwfan_is_caldera_forms_active() {
		return BWFAN_PRO_Plugin_Dependency::caldera_forms_active_check();
	}
}

/**
 * Optin Forms Detection
 */
if ( ! function_exists( 'bwfan_is_optin_forms_active' ) ) {
	function bwfan_is_optin_forms_active() {
		return BWFAN_PRO_Plugin_Dependency::optin_forms_active_check();
	}
}
/**
 * Forminator Forms Detection
 */
if ( ! function_exists( 'bwfan_is_forminator_forms_active' ) ) {
	function bwfan_is_forminator_forms_active() {
		return BWFAN_PRO_Plugin_Dependency::forminator_forms_active_check();
	}
}

/**
 * Wishlist plugin Detection
 */
if ( ! function_exists( 'bwfan_is_wc_wishlist_active' ) ) {
	function bwfan_is_wc_wishlist_active() {
		return BWFAN_PRO_Plugin_Dependency::wc_wishlist_active_check();
	}
}

/**
 *
 * Weglot language plugin
 */
if ( ! function_exists( 'bwfan_is_weglot_active' ) ) {
	function bwfan_is_weglot_active() {
		return BWFAN_PRO_Plugin_Dependency::weglot_active_check();
	}
}

/**
 * Wishlist Member plugin by Wishlist Products
 */

if ( ! function_exists( 'bwfan_is_wlm_active' ) ) {
	function bwfan_is_wlm_active() {
		return BWFAN_PRO_Plugin_Dependency::wlm_active_check();
	}
}

/**
 * Woocommerce Advanced Shipping
 */
if ( ! function_exists( 'bwfan_is_woocommerce_advanced_shipping_pro_active' ) ) {
	function bwfan_is_woocommerce_advanced_shipping_pro_active() {
		return BWFAN_PRO_Plugin_Dependency::wc_advanced_shipping_active_check();
	}
}

/**
 * Advanced Coupons for woocommerce free
 */
if ( ! function_exists( 'bwfan_is_advanced_coupon_for_woocommerce_active' ) ) {
	function bwfan_is_advanced_coupon_for_woocommerce_active() {
		return BWFAN_PRO_Plugin_Dependency::wc_advanced_coupons_active_check();
	}
}

if ( ! function_exists( 'bwfan_is_loyalty_program_for_woocommerce_active' ) ) {
	function bwfan_is_loyalty_program_for_woocommerce_active() {
		return BWFAN_PRO_Plugin_Dependency::wc_loyalty_program_active_check();
	}
}

/**
 * Funnel Builder Lite
 */
if ( ! function_exists( 'bwfan_is_funnel_active' ) ) {
	function bwfan_is_funnel_active() {
		return BWFAN_PRO_Plugin_Dependency::bwfan_is_funnel_active_check();
	}
}

/**
 * Yith Wishlist plugin Detection
 */
if ( ! function_exists( 'bwfan_is_yith_wishlist_active' ) ) {
	function bwfan_is_yith_wishlist_active() {
		return BWFAN_PRO_Plugin_Dependency::yith_wishlist_active_check();
	}
}
/**
 * TI WC Wishlist plugin Detection
 */
if ( ! function_exists( 'bwfan_is_ti_wc_wishlist_active' ) ) {
	function bwfan_is_ti_wc_wishlist_active() {
		return BWFAN_PRO_Plugin_Dependency::ti_wc_wishlist_active_check();
	}
}

/**
 * XL Next move plugin Detection
 */
if ( ! function_exists( 'bwfan_is_xl_nextmove_thankyou_active' ) ) {
	function bwfan_is_xl_nextmove_thankyou_active() {
		return BWFAN_PRO_Plugin_Dependency::xl_nextmove_thankyou_active_check();
	}
}

/**
 * WooCommerce Orders Tracking Premium plugin Detection
 */
if ( ! function_exists( 'bwfan_wc_order_tracking_active' ) ) {
	function bwfan_wc_order_tracking_active() {
		return BWFAN_PRO_Plugin_Dependency::wc_order_tracking_active_check();
	}
}

/**
 * FunnelKit stripe payment gateway
 */
if ( ! function_exists( 'bwfan_is_fk_stripe_active' ) ) {
	function bwfan_is_fk_stripe_active() {
		return BWFAN_PRO_Plugin_Dependency::bwfan_is_fk_stripe_active();
	}
}

/**
 * FunnelKit stripe payment gateway
 */
if ( ! function_exists( 'bwfan_is_funnel_builder_pro_active' ) ) {
	function bwfan_is_funnel_builder_pro_active() {
		return BWFAN_PRO_Plugin_Dependency::bwfan_is_funnel_builder_pro_active();
	}
}
