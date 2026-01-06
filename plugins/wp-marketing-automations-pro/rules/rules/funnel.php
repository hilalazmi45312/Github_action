<?php

class BWFAN_Rule_funnel extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'funnel' );
	}

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'none' => __( 'matches none of', 'wp-marketing-automations-pro' ),
		);
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Search';
	}

	public function get_options( $term = '' ) {
		$results = array();
		$args    = array( 's' => $term );
		$funnels = WFFN_Core()->admin->get_funnels( $args );
		foreach ( $funnels['items'] as $funnel ) {
			$results[ $funnel['id'] ] = $funnel['title'];
		}

		return $results;

	}

	public function is_match_v2( $automation_data, $rule_data ) {

		$funnel_ids = array();
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}
		$funnel_id    = isset( $automation_data['global']['funnel_id'] ) ? $automation_data['global']['funnel_id'] : [];
		$type         = $rule_data['rule'];
		$data         = $rule_data['data'];
		$saved_ids    = array_column( $rule_data['data'], 'key' );
		$funnel_ids[] = $funnel_id;

		$data = $rule_data['data'];
		if ( ! is_array( $data ) && empty( $data ) ) {
			return false;
		}
		$result = $this->validate_set( $saved_ids, $funnel_ids, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	public function validate_set( $saved_ids, $funnel_ids, $operator ) {
		switch ( $operator ) {
			case 'any':
				$result = count( array_intersect( $saved_ids, $funnel_ids ) ) > 0;
				break;
			case 'none':
				$result = count( array_intersect( $saved_ids, $funnel_ids ) ) === 0;
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}

}