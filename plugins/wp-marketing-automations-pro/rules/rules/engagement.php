<?php

class BWFAN_Rule_Link_Trigger_Clicked extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'link_trigger_clicked' );
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_search_results( $term, true );
	}

	public function get_search_results( $term, $v2 = false ) {
		$link_triggers = BWFAN_Model_Link_Triggers::get_link_triggers( $term );

		if ( $v2 ) {
			$return = array();
			foreach ( $link_triggers['links'] as $link ) {
				$return[ $link['ID'] ] = $link['title'];
			}

			return $return;
		}
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$link_cliked = $contact->get_field_by_slug( 'link-trigger-click' ) ? json_decode( $contact->get_field_by_slug( 'link-trigger-click' ) ) : array();
		if ( empty( $link_cliked ) ) {
			$result = ( 'none' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$link_cliked   = array_values( $link_cliked );
		$selected_link = array_map( function ( $link ) {
			return $link['key'];
		}, $rule_data['data'] );

		$type   = $rule_data['rule'];
		$result = $this->validate_matches_set( $selected_link, $link_cliked, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'any'  => __( 'Matches any of', 'wp-marketing-automations-pro' ),
			'none' => __( 'Matches none of', 'wp-marketing-automations-pro' ),
			'all'  => __( 'Matches all of', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Last_Login extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'last_login' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$last_login = $contact->get_field_by_slug( 'last-login' ) ? strtotime( $contact->get_field_by_slug( 'last-login' ) ) : '';
		if ( empty( $last_login ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$type   = $rule_data['rule'];
		$result = $this->validate_matches_duration_set( $last_login, $rule_data, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Last_Open extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'last_open' );
	}

	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$last_open = $contact->get_field_by_slug( 'last-open' ) ? strtotime( $contact->get_field_by_slug( 'last-open' ) ) : '';
		if ( empty( $last_open ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$type   = $rule_data['rule'];
		$result = $this->validate_matches_duration_set( $last_open, $rule_data, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}
}

class BWFAN_Rule_Last_Sent extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'last_sent' );
	}

	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( false, $rule_data );
		}

		$last_sent = $contact->get_field_by_slug( 'last-sent' ) ? strtotime( $contact->get_field_by_slug( 'last-sent' ) ) : '';
		if ( empty( $last_sent ) ) {
			return $this->return_is_match( false, $rule_data );
		}

		$type   = $rule_data['rule'];
		$result = $this->validate_matches_duration_set( $last_sent, $rule_data, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}
}

class BWFAN_Rule_Last_Clicked extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'last_clicked' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$last_click = $contact->get_field_by_slug( 'last-click' ) ? strtotime( $contact->get_field_by_slug( 'last-click' ) ) : '';
		if ( empty( $last_click ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$type   = $rule_data['rule'];
		$result = $this->validate_matches_duration_set( $last_click, $rule_data, $type );

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */
	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Engaged extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'engaged' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$type         = $rule_data['rule'];
		$current_time = current_time( 'timestamp' );

		if ( ! is_array( $rule_data['data'] ) ) {
			$data = date( 'Y-m-d', $current_time - ( DAY_IN_SECONDS * absint( $rule_data['data'] ) ) );// excluding time
		} else {
			$from = absint( $rule_data['data']['from'] );
			$date = new DateTime( 'now', wp_timezone() );
			$date->modify( "-$from days" );
			$from = $date->format( 'Y-m-d' );

			$to   = absint( $rule_data['data']['to'] );
			$date = new DateTime( 'now', wp_timezone() );
			$date->modify( "-$to days" );
			$to = $date->format( 'Y-m-d' );
		}

		$filter_rule = '';
		switch ( $type ) {
			case 'over':
				$filter_rule = '<';
				break;
			case 'past':
				$filter_rule = '>=';
				break;
			case 'between':
				$filter_rule = 'between';
				break;
			default:
				break;
		}

		/** Get Last Sent & Last Open */
		$last_sent  = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		$last_open  = BWFAN_Model_Fields::get_field_by_slug( 'last-open' );
		$last_click = BWFAN_Model_Fields::get_field_by_slug( 'last-click' );

		$last_sent  = 'f' . $last_sent['ID'];
		$last_open  = 'f' . $last_open['ID'];
		$last_click = 'f' . $last_click['ID'];

		if ( 'between' === $filter_rule ) {
			$where = " AND ( cm.$last_sent IS NULL OR ( ( cm.$last_open >= '$to' AND cm.$last_open <= '$from') OR ( cm.$last_click >= '$to' AND cm.$last_click <= '$from') ) )";
		} else {
			$where = " AND ( cm.$last_sent IS NULL OR ( cm.$last_open $filter_rule '$data' OR cm.$last_click $filter_rule '$data' ) )";
		}

		$engaged_count = BWFAN_Pro_Common::get_engaged_or_unengaged_data_count( $where, $cid );

		if ( ! empty( $engaged_count ) && $engaged_count > 0 ) {
			$result = true;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'over'    => __( 'Before', 'wp-marketing-automations-pro' ),
			'past'    => __( 'In the last', 'wp-marketing-automations-pro' ),
			'between' => __( 'In between', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Unengaged extends BWFAN_Rule_Base {
	public function __construct() {
		$this->v1 = false;
		$this->v2 = true;
		parent::__construct( 'unengaged' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'Days';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			return $this->return_is_match( $result, $rule_data );
		}

		/** Get Last Sent & Last Open */
		$last_sent = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		$last_open = BWFAN_Model_Fields::get_field_by_slug( 'last-open' );

		$last_sent = 'f' . $last_sent['ID'];
		$last_open = 'f' . $last_open['ID'];

		$current_time = current_time( 'timestamp' );
		$data         = date( 'Y-m-d', $current_time - ( DAY_IN_SECONDS * absint( $rule_data['data'] ) ) ); // excluding time

		$where           = " AND ( cm.$last_sent IS NOT NULL AND (cm.$last_open IS NULL OR cm.$last_open < '$data') )";
		$unengaged_count = BWFAN_Pro_Common::get_engaged_or_unengaged_data_count( $where, $cid );

		if ( ! empty( $unengaged_count ) && $unengaged_count > 0 ) {
			$result = true;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'from' => __( 'from', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Broadcast_Sent extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1    = false;
		$this->v2    = true;
		$this->title = 'Broadcast';
		parent::__construct( 'broadcast_sent' );
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return BWFAN_PRO_Common::get_broadcasts_by_term( $term );
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$data = $rule_data['data'][0]['key'];

		$broadcast_sent = BWFAN_PRO_Common::get_contacts_from_broadcast( $data, '' );
		if ( empty( $broadcast_sent ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$broadcast_sent = array_map( function ( $user_ids ) {
			return $user_ids['id'];
		}, $broadcast_sent );


		$type = $rule_data['rule'];

		switch ( $type ) {
			case 'is':
				$result = in_array( $cid, $broadcast_sent );
				break;
			case 'isnot':
			case 'is_not':
				$result = ! in_array( $cid, $broadcast_sent );
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'is'     => __( 'is sent', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'is not sent', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Broadcast_Open extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1    = false;
		$this->v2    = true;
		$this->title = 'Broadcast';
		parent::__construct( 'broadcast_open' );
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return BWFAN_PRO_Common::get_broadcasts_by_term( $term );
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$data           = $rule_data['data'][0]['key'];
		$broadcast_open = BWFAN_PRO_Common::get_contacts_from_broadcast( $data, 'open' );

		if ( empty( $broadcast_open ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$broadcast_open = array_map( function ( $user_ids ) {
			return $user_ids['id'];
		}, $broadcast_open );


		$type = $rule_data['rule'];

		switch ( $type ) {
			case 'is':
				$result = in_array( $cid, $broadcast_open );
				break;
			case 'isnot':
			case 'is_not':
				$result = ! in_array( $cid, $broadcast_open );
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'is'     => __( 'has opened', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'has not opened', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Broadcast_Clicked extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1    = false;
		$this->v2    = true;
		$this->title = 'Broadcast';
		parent::__construct( 'broadcast_clicked' );
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return BWFAN_PRO_Common::get_broadcasts_by_term( $term );
	}

	public function get_rule_type() {
		return 'Search';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$data              = $rule_data['data'][0]['key'];
		$broadcast_clicked = BWFAN_PRO_Common::get_contacts_from_broadcast( $data, 'click' );
		if ( empty( $broadcast_clicked ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$broadcast_clicked = array_map( function ( $user_ids ) {
			return $user_ids['id'];
		}, $broadcast_clicked );


		$type = $rule_data['rule'];

		switch ( $type ) {
			case 'is':
				$result = in_array( $cid, $broadcast_clicked );
				break;
			case 'isnot':
			case 'is_not':
				$result = ! in_array( $cid, $broadcast_clicked );
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
			'is'     => __( 'has clicked', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'has not clicked', 'wp-marketing-automations-pro' ),
		);
	}

}

class BWFAN_Rule_Email_Opens extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v1    = false;
		$this->v2    = true;
		$this->title = 'Email';
		parent::__construct( 'email_opens' );
	}

	/** v2 Methods: START */

	public function get_options( $term = '' ) {
		return $this->get_search_results( $term );
	}

	public function get_search_results( $term, $v = 2 ) {
		return BWFAN_PRO_Common::get_emails_by_subject_in_data( $term, $v );
	}

	public function get_rule_type() {
		return 'search-list';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;
		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$cid = isset( $automation_data['global']['contact_id'] ) ? $automation_data['global']['contact_id'] : 0;
		if ( 0 === absint( $cid ) ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$contact = new BWFCRM_Contact( $cid );
		if ( ! $contact->is_contact_exists() ) {
			$result = ( 'is_not' === $rule_data['rule'] ) ? true : $result;

			return $this->return_is_match( $result, $rule_data );
		}

		$step_ids    = empty( $rule_data['data'] ) ? [] : array_column( $rule_data['data'], 'id' );
		$emails_open = BWFAN_PRO_Common::get_contacts_engagement( $cid, $step_ids, 1, 1 );

		if ( ! empty( $emails_open ) ) {
			$result = true;
		}

		$type = $rule_data['rule'];

		switch ( $type ) {
			case 'is':
				break;
			case 'is_not':
				$result = ! $result;
				break;
			default:
				break;
		}

		return $this->return_is_match( $result, $rule_data );
	}

	/** v2 Methods: END */

	public function get_possible_rule_operators() {
		return array(
			'is'     => __( 'has opened', 'wp-marketing-automations-pro' ),
			'is_not' => __( 'has not opened', 'wp-marketing-automations-pro' ),
		);
	}

}