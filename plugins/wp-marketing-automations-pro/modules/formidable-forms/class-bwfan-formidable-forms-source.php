<?php

/**
 * BWFAN_Formidable_Forms_Source
 */
class BWFAN_Formidable_Forms_Source extends BWFAN_Source {
	private static $instance = null;

	public function __construct() {
		$this->event_dir  = __DIR__;
		$this->nice_name  = __( 'Formidable Forms', 'wp-marketing-automations-pro' );
		$this->group_name = __( 'Forms', 'wp-marketing-automations-pro' );
		$this->group_slug = 'forms';
		$this->priority   = 100;
	}

	/**'
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @return BWFAN_Formidable_Forms_Source|nul
	 *
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
if ( function_exists( 'bwfan_is_formidable_forms_active' ) && bwfan_is_formidable_forms_active() ) {
	BWFAN_Load_Sources::register( 'BWFAN_Formidable_Forms_Source' );
}
