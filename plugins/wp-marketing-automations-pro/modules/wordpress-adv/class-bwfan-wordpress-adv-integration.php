<?php

#[AllowDynamicProperties]
final class BWFAN_WP_Adv_Integration extends BWFAN_Integration {
	public static $integration_type = null;
	public static $headers = null;
	private static $ins = null;

	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'WordPress', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'WordPress', 'wp-marketing-automations-pro' );
		$this->group_slug = 'wp';
		$this->priority   = 25;
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
BWFAN_Load_Integrations::register( 'BWFAN_WP_Adv_Integration' );
