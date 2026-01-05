<?php

class BWFAN_WCS_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'WooCommerce Subscription', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'WooCommerce', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wc';
		$this->priority   = 50;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_WCS_Source|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

/**
 * Register this as a source.
 */
if ( bwfan_is_woocommerce_active() && bwfan_is_woocommerce_subscriptions_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_WCS_Source' );
}
