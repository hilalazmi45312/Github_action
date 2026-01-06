<?php

class BWFAN_UpStroke_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'Upsell', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Funnel Builder', 'wp-marketing-automations-pro' );
		$this->group_slug = 'woofunnels';
		$this->priority   = 8;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_UpStroke_Source|null
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
if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_UpStroke_Source' );
}
