<?php

final class BWFAN_ADV_Coupons_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_ADV_Coupons_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'Advanced Coupons', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'WooCommerce', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wc';
		$this->priority   = 35;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_ADV_Coupons_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( bwfan_is_woocommerce_active() && bwfan_is_advanced_coupon_for_woocommerce_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_ADV_Coupons_Integration' );
}
