<?php

class BWFAN_WLM_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'WishList Member', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Membership', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wlm';
		$this->priority   = 50;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_WLM_Source|null
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
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_WLM_Source' );
}
