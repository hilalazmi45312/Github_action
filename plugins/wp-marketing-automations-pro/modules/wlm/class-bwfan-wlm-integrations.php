<?php

final class BWFAN_WLM_Integration extends BWFAN_Integration {
	public static $integration_type = null;
	public static $headers = null;
	private static $ins = null;

	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'WishList Member', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Membership', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wlm';
		$this->priority   = 60;
	}

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}
}

/**
 * Register this class as an integration.
 */
if ( function_exists( 'bwfan_is_wlm_active' ) && bwfan_is_wlm_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_WLM_Integration' );
}
