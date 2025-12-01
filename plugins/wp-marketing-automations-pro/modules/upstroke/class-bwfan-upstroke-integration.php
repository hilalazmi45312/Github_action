<?php

final class BWFAN_UpStroke_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_UpStroke_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'One-Click Upsells', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Funnel Builder', 'wp-marketing-automations-pro' );
		$this->group_slug = 'woofunnels';
		$this->priority   = 8;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_UpStroke_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


if ( bwfan_is_woocommerce_active() && bwfan_is_woofunnels_upstroke_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_UpStroke_Integration' );
}
