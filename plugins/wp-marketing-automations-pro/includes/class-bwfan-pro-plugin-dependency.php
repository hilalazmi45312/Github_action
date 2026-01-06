<?php

/**
 * WC Dependency Checker
 */
#[AllowDynamicProperties]
class BWFAN_PRO_Plugin_Dependency {

	private static $active_plugins;

	/** checking paid membership pro is active
	 * @return bool
	 */
	public static function paid_membership_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'paid-memberships-pro/paid-memberships-pro.php', self::$active_plugins, true ) || array_key_exists( 'paid-memberships-pro/paid-memberships-pro.php', self::$active_plugins );
	}

	public static function init() {
		self::$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}
	}

	/**
	 * check if thrive plugin active
	 * @return bool
	 */
	public static function tve_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'thrive-leads/thrive-leads.php', self::$active_plugins, true ) || array_key_exists( 'thrive-leads/thrive-leads.php', self::$active_plugins );
	}

	/**
	 * @return bool
	 */
	public static function tve_architect_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'thrive-visual-editor/thrive-visual-editor.php', self::$active_plugins, true ) || array_key_exists( 'thrive-visual-editor/thrive-visual-editor.php', self::$active_plugins );
	}

	/**
	 * Checking if learndash plugin active
	 * @return bool
	 */
	public static function learndash_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( defined( 'LEARNDASH_VERSION' ) ) {
			return true;
		}

		return in_array( 'sfwd-lms/sfwd_lms.php', self::$active_plugins, true ) || array_key_exists( 'sfwd-lms/sfwd_lms.php', self::$active_plugins );
	}

	/**
	 * Checking if ninja form plugin active
	 * @return bool
	 */
	public static function ninja_forms_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( function_exists( 'Ninja_Forms' ) ) {
			return true;
		}

		return in_array( 'ninja-forms/ninja-forms.php', self::$active_plugins, true ) || array_key_exists( 'ninja-forms/ninja-forms.php', self::$active_plugins );
	}

	/**
	 * Checking if fluent form plugin active
	 * @return bool
	 */
	public static function fluent_forms_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( defined( 'FLUENTFORM' ) ) {
			return true;
		}

		return in_array( 'fluentform/fluentform.php', self::$active_plugins, true ) || array_key_exists( 'fluentform/fluentform.php', self::$active_plugins );
	}

	/**
	 * Checking if caldera form plugin active
	 * @return bool
	 */
	public static function caldera_forms_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'Caldera_Forms' ) ) {
			return true;
		}

		return in_array( 'caldera-forms/caldera-core.php', self::$active_plugins, true ) || array_key_exists( 'caldera-forms/caldera-core.php', self::$active_plugins );
	}

	/**
	 * Checking if optim form plugin active
	 * @return bool
	 */
	public static function optin_forms_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WFFN_Core' ) ) {
			return true;
		}

		return in_array( 'funnel-builder/funnel-builder.php', self::$active_plugins, true ) || array_key_exists( 'woofunnels-flex-funnels/woofunnels-flex-funnels.php', self::$active_plugins );
	}

	/**
	 * Checking if forminator form plugin active
	 * @return bool
	 */
	public static function forminator_forms_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'Forminator' ) ) {
			return true;
		}

		return in_array( 'forminator/forminator.php', self::$active_plugins, true ) || array_key_exists( 'forminator/forminator.php', self::$active_plugins );
	}

	public static function wc_wishlist_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WC_Wishlists_Plugin' ) ) {
			return true;
		}

		return in_array( 'woocommerce-wishlists/woocommerce-wishlists.php', self::$active_plugins, true ) || array_key_exists( 'woocommerce-wishlists/woocommerce-wishlists.php', self::$active_plugins );
	}

	/**
	 * Checking if weglot lanuage plugin active
	 * @return bool
	 */
	public static function weglot_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( defined( 'WEGLOT_NAME' ) ) {
			return true;
		}

		return in_array( 'weglot/weglot.php', self::$active_plugins, true ) || array_key_exists( 'weglot/weglot.php', self::$active_plugins );
	}


	/**
	 * Checking if wishlist member plugin active
	 * @return bool
	 */
	public static function wlm_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WishListMember' ) ) {
			return true;
		}

		return in_array( 'wishlist-member-x/wpm.php', self::$active_plugins, true ) || array_key_exists( 'wishlist-member-x/wpm.php', self::$active_plugins );
	}

	/**
	 * WC Advanced shipping plugin checking
	 * @return bool
	 */
	public static function wc_advanced_shipping_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WooCommerce_Advanced_Shipping' ) ) {
			return true;
		}

		return in_array( 'woocommerce-advanced-shipping/woocommerce-advanced-shipping.php', self::$active_plugins, true ) || array_key_exists( 'woocommerce-advanced-shipping/woocommerce-advanced-shipping.php', self::$active_plugins );
	}

	/**
	 * Advanced Coupons for woocommerce free
	 * @return bool
	 */
	public static function wc_advanced_coupons_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'ACFWF' ) ) {
			return true;
		}

		return in_array( 'advanced-coupons-for-woocommerce-free/advanced-coupons-for-woocommerce-free.php', self::$active_plugins, true ) || array_key_exists( 'advanced-coupons-for-woocommerce-free/advanced-coupons-for-woocommerce-free.php', self::$active_plugins );
	}

	public static function wc_loyalty_program_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'LPFW' ) ) {
			return true;
		}

		return in_array( 'loyalty-program-for-woocommerce/loyalty-program-for-woocommerce.php', self::$active_plugins, true ) || array_key_exists( 'loyalty-program-for-woocommerce/loyalty-program-for-woocommerce.php', self::$active_plugins );
	}

	/**
	 * WC Advanced shipping plugin checking
	 * @return bool
	 */
	public static function bwfan_is_funnel_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WFFN_Core' ) ) {
			return true;
		}

		return in_array( 'funnel-builder.php', self::$active_plugins, true ) || array_key_exists( 'funnel-builder.php', self::$active_plugins );
	}

	/**
	 * Yith Wishlist  plugin checking
	 * @return bool
	 */
	public static function yith_wishlist_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'YITH_WCWL' ) ) {
			return true;
		}

		/** Checking for lite version */
		if ( in_array( 'yith-woocommerce-wishlist/init.php', self::$active_plugins, true ) || array_key_exists( 'yith-woocommerce-wishlist/init.php', self::$active_plugins ) ) {
			return true;
		}

		return in_array( 'yith-woocommerce-wishlist-premium/init.php', self::$active_plugins, true ) || array_key_exists( 'yith-woocommerce-wishlist-premium/init.php', self::$active_plugins );
	}

	/**
	 * TI WC Wishlist plugin checking
	 * @return bool
	 */
	public static function ti_wc_wishlist_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}
		if ( in_array( 'ti-woocommerce-wishlist/ti-woocommerce-wishlist.php', self::$active_plugins, true ) || array_key_exists( 'ti-woocommerce-wishlist/ti-woocommerce-wishlist.php', self::$active_plugins ) ) {
			return true;
		}

		return in_array( 'ti-woocommerce-wishlist-premium/ti-woocommerce-wishlist-premium.php', self::$active_plugins, true ) || array_key_exists( 'ti-woocommerce-wishlist-premium/ti-woocommerce-wishlist-premium.php', self::$active_plugins );
	}

	/**
	 * Xl next move  plugin checking
	 * @return bool
	 */
	public static function xl_nextmove_thankyou_active_check() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		return in_array( 'thank-you-page-for-woocommerce-nextmove/woocommerce-thankyou-pages.php', self::$active_plugins, true ) || array_key_exists( 'thank-you-page-for-woocommerce-nextmove/woocommerce-thankyou-pages.php', self::$active_plugins );
	}

	/**
	 * Checking if WooCommerce Orders Tracking Premium plugin active
	 * @return bool
	 */
	public static function wc_order_tracking_active_check() {

		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( in_array( 'woocommerce-orders-tracking/woocommerce-orders-tracking.php', self::$active_plugins, true ) || array_key_exists( 'woocommerce-orders-tracking/woocommerce-orders-tracking.php', self::$active_plugins ) ) {
			return true;
		}

		return in_array( 'woo-orders-tracking/woo-orders-tracking.php', self::$active_plugins, true ) || array_key_exists( 'woo-orders-tracking/woo-orders-tracking.php', self::$active_plugins );
	}

	public static function bwfan_is_fk_stripe_active() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'FKWCS_Gateway_Stripe' ) ) {
			return true;
		}

		return in_array( 'funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php', self::$active_plugins, true ) || array_key_exists( 'funnelkit-stripe-woo-payment-gateway/funnelkit-stripe-woo-payment-gateway.php', self::$active_plugins );
	}

	/**
	 * Funnel Builder Pro plugin checking
	 *
	 * @return bool
	 */
	public static function bwfan_is_funnel_builder_pro_active() {
		if ( ! self::$active_plugins ) {
			self::init();
		}

		if ( class_exists( 'WFFN_Pro_Core' ) && class_exists( 'WFOCU_Core' ) ) {
			return true;
		}

		return in_array( 'funnel-builder-pro/funnel-builder-pro.php', self::$active_plugins, true ) || array_key_exists( 'funnel-builder-pro/funnel-builder-pro.php', self::$active_plugins );
	}
}
