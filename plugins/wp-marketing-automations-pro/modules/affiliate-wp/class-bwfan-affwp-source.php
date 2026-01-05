<?php

class BWFAN_AFFWP_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Affiliate', 'wp-marketing-automations-pro' );
		$this->group_slug = 'affiliate';
		$this->priority   = 30;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_AFFWP_Source|null
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
if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_AFFWP_Source' );
}
