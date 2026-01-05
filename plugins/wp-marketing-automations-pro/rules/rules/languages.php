<?php

class BWFAN_Rule_Language extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'language' );
	}

	/** v2 Methods: START */
	public function get_options( $term = '' ) {
		return $this->get_possible_rule_values();
	}

	public function get_rule_type() {
		return 'Select';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) || ! isset( $automation_data['global']['language'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$language = $automation_data['global']['language'];
		if ( empty( $language ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$value    = strval( $rule_data['data'] );
		$language = strval( $language );

		switch ( $rule_data['rule'] ) {
			case 'is':
				$result = $language === $value;
				break;
			case 'is_not':
				$result = $language !== $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'is'     => __( 'is', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'is not', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_values() {
		$lang_options  = [];
		$language_data = BWFAN_PRO_Common::get_language_settings();
		if ( is_array( $language_data ) && isset( $language_data['lang_options'] ) && ! empty( $language_data['lang_options'] ) ) {
			$lang_options = array_replace( [ '' => 'Select' ], $language_data['lang_options'] );
		}

		return $lang_options;
	}
}
