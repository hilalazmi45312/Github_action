<?php

final class BWFAN_AFFWP_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_AFFWP_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'AffiliateWP', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Affiliate', 'wp-marketing-automations-pro' );
		$this->group_slug = 'affiliate';
		$this->priority   = 75;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_AFFWP_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( bwfan_is_affiliatewp_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_AFFWP_Integration' );
}
