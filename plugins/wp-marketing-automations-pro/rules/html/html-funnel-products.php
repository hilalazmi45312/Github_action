<?php

class bwfan_Input_Funnel_Products {
	public function __construct() {
		// vars
		$this->type     = 'Funnel_Products';
		$this->defaults = array(
			'default_value' => '',
			'class'         => '',
			'placeholder'   => '',
		);
	}

	public function render( $field, $value = null ) {
		echo esc_html__( 'Run this automation only if any of automation product is not present in primary offer. ', 'wp-marketing-automations-pro' );
	}

}
