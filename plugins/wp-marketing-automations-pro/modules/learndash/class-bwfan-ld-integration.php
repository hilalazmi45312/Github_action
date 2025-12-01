<?php

final class BWFAN_LD_Integration extends BWFAN_Integration {

	private static $instance = null;

	/**
	 * BWFAN_LD_Integration constructor.
	 */
	private function __construct() {
		$this->action_dir = __DIR__;
		$this->nice_name  = __( 'LearnDash', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'LMS', 'wp-marketing-automations-pro' );
		$this->group_slug = 'lms';
		$this->priority   = 65;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_LD_Integration|null
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

if ( bwfan_is_learndash_active() ) {
	BWFAN_Load_Integrations::register( 'BWFAN_LD_Integration' );
}
