<?php

class BWFAN_FluentForms_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'Fluent Forms', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Forms', 'wp-marketing-automations-pro' );
		$this->group_slug = 'forms';
		$this->priority   = 100;
	}

	/**
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_FluentForms_Source|null
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
if ( bwfan_is_fluent_forms_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_FluentForms_Source' );
}
