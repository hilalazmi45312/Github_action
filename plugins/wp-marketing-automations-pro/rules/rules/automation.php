<?php

class BWFAN_Rule_Automation_Run_Count extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		parent::__construct( 'automation_run_count' );
	}

	/** v2 Methods: START */

	public function get_rule_type() {
		return 'Number';
	}

	/** v2 Methods: END */

	public function get_condition_input_type() {
		return 'Text';
	}

	public function is_match( $rule_data ) {
		/**
		 * @var Woofunnels_Customer $customer
		 */
		$automation_id = BWFAN_Core()->rules->getRulesData( 'automation_id' );
		$customer_id   = BWFAN_Core()->rules->getRulesData( 'bwf_customer' );

		if ( empty( $customer_id ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$count = absint( BWFAN_Model_Contact_Automations::get_contact_automations_run_count( $customer_id, $automation_id ) );
		$value = absint( $rule_data['condition'] );

		switch ( $rule_data['operator'] ) {
			case '==':
				$result = $count === $value;
				break;
			case '!=':
				$result = $count !== $value;
				break;
			case '>':
				$result = $count > $value;
				break;
			case '<':
				$result = $count < $value;
				break;
			case '>=':
				$result = $count >= $value;
				break;
			case '<=':
				$result = $count <= $value;
				break;
			default:
				$result = false;
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	public function ui_view() {
		esc_html_e( 'Automation run count', 'wp-marketing-automations-pro' );
		?>
        <% var ops = JSON.parse('<?php echo wp_json_encode( $this->get_possible_rule_operators() ); ?>'); %>

        <%= ops[operator] %>
        <%= condition %>
		<?php
	}

	public function get_possible_rule_operators() {
		$operators = array(
			'==' => __( 'is equal to', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not equal to', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is greater than', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is less than', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is greater or equal to', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is less or equal to', 'wp-marketing-automations-pro' ),
		);

		return $operators;
	}
}

class BWFAN_Rule_Ever_Entered_Automation extends BWFAN_Rule_Base {


	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'ever_entered_automation' );
	}

	/** v2 Methods: START */

	public function get_options( $search = '' ) {
		$automations   = BWFAN_Common::get_automation_by_title( $search, 2 );
		$prepared_data = [];
		foreach ( $automations as $automation ) {
			$prepared_data[ $automation['id'] ] = $automation['title'];
		}

		return $prepared_data;
	}

	public function get_rule_type() {
		return 'Search';
	}


	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact_id = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
		/** CRM contact object */
		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		/**set automation id in active automation */
		$entered_automation     = $contact->get_field_by_slug( 'automation-entered' );
		$entered_automation_ids = json_decode( $entered_automation, true );
		$entered_automation_ids = ( ! empty( $entered_automation_ids ) && is_array( $entered_automation_ids ) ) ? $entered_automation_ids : array();

		$selected_automations = $rule_data['data'];
		$selected_automations = array_map( function ( $data ) {
			return absint( $data['key'] );
		}, $selected_automations );

		$result = $this->validate_matches_set( $selected_automations, $entered_automation_ids, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
			'none' => __( 'matches none of ', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Currently_Active_Automation extends BWFAN_Rule_Base {


	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'currently_active_automation' );
	}

	/** v2 Methods: START */

	public function get_options( $search = '' ) {
		$automations   = BWFAN_Common::get_automation_by_title( $search, 2 );
		$prepared_data = [];
		foreach ( $automations as $automation ) {
			$prepared_data[ $automation['id'] ] = $automation['title'];
		}

		return $prepared_data;
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact_id = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
		/** CRM contact object */
		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		/**set automation id in active automation */
		$active_automation     = $contact->get_field_by_slug( 'automation-active' );
		$active_automation_ids = json_decode( $active_automation, true );
		$active_automation_ids = ( ! empty( $active_automation_ids ) && is_array( $active_automation_ids ) ) ? $active_automation_ids : array();

		$selected_automations = $rule_data['data'];
		$selected_automations = array_map( function ( $data ) {
			return absint( $data['key'] );
		}, $selected_automations );

		$result = $this->validate_matches_set( $selected_automations, $active_automation_ids, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
			'none' => __( 'matches none of ', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Completed_Automation extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'completed_automation' );
	}

	/** v2 Methods: START */

	public function get_options( $search = '' ) {
		$automations   = BWFAN_Common::get_automation_by_title( $search, 2 );
		$prepared_data = [];
		foreach ( $automations as $automation ) {
			$prepared_data[ $automation['id'] ] = $automation['title'];
		}

		return $prepared_data;
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact_id = isset( $automation_data['global']['cid'] ) ? $automation_data['global']['cid'] : 0;
		/** CRM contact object */
		$contact = new BWFCRM_Contact( $contact_id );

		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		/**set automation id in active automation */
		$completed_automation     = $contact->get_field_by_slug( 'automation-completed' );
		$completed_automation_ids = json_decode( $completed_automation, true );
		$completed_automation_ids = ( ! empty( $completed_automation_ids ) && is_array( $completed_automation_ids ) ) ? $completed_automation_ids : array();

		$selected_automations = $rule_data['data'];
		$selected_automations = array_map( function ( $data ) {
			return absint( $data['key'] );
		}, $selected_automations );

		$result = $this->validate_matches_set( $selected_automations, $completed_automation_ids, $rule_data['rule'] );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'matches any of', 'wp-marketing-automations-pro' ),
			'all'  => __( 'matches all of ', 'wp-marketing-automations-pro' ),
			'none' => __( 'matches none of ', 'wp-marketing-automations-pro' ),
		);
	}

}
