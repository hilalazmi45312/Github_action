<?php

class bwfan_Input_Funnel_OneTime {
	public function __construct() {
		// vars
		$this->type     = 'Funnel_OneTime';
		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => '',
		);
	}

	public function render( $field, $value = null ) {
		echo esc_html__( 'Run this automation only if the user hasn\'t visited it yet.', 'wp-marketing-automations-pro' );
	}

}
