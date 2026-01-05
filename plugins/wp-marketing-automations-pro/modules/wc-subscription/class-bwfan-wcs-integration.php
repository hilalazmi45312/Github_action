<?php

final class BWFAN_WCS_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_WCS_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'Subscriptions', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'WooCommerce', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wc';
		$this->priority   = 35;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_WCS_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_WCS_Integration' );
}
