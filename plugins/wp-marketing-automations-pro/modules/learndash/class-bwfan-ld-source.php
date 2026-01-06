<?php

class BWFAN_LD_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'LearnDash', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'LMS', 'wp-marketing-automations-pro' );
		$this->group_slug = 'lms';
		$this->priority   = 50;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_LD_Source|null
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
if ( bwfan_is_learndash_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_LD_Source' );
}
