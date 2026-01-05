<?php


class BWFAN_DateTime_Rule_Common {

	public static function is_match( $operator, $value, $selected_value ) {
		if ( empty( $operator ) ) {
			return false;
		}
		switch ( $operator ) {
			case '==':
				$result = $value === $selected_value;
				break;
			case '!=':
				$result = $value !== $selected_value;
				break;
			case '>':
				$result = $value > $selected_value;
				break;
			case '<':
				$result = $value < $selected_value;
				break;
			case '>=':
				$result = $value >= $selected_value;
				break;
			case '<=':
				$result = $value <= $selected_value;
				break;
			default:
				$result = false;
				break;
		}

		return $result;
	}
}

class BWFAN_Rule_Current_Year extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_year' );
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

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$current_year  = date_i18n( 'Y' );
		$selected_year = $rule_data['data'];
		$result        = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_year, $selected_year );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_values() {
		$years        = array();
		$current_year = date_i18n( 'Y' );
		$end_year     = intval( $current_year ) + 25;
		for ( $year = $current_year; $year <= $end_year; $year ++ ) {
			$localized_year = date_i18n( 'Y', strtotime( $year . '-01-01' ) );
			$years[ $year ] = $localized_year;
		}

		return $years;
	}
}

class BWFAN_Rule_Current_Month extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_month' );
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

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		if ( ! isset( $rule_data['data'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		$current_month  = intval( date_i18n( 'n' ) );
		$selected_month = intval( $rule_data['data'] );
		$result         = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_month, $selected_month );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_values() {
		$months = range( 1, 12 );

		$localized_months = array_map( function ( $month ) {
			return date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) );
		}, $months );

		return array_combine( $months, $localized_months );
	}

}

class BWFAN_Rule_Current_Day_Of_Month extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_day_of_month' );
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

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		if ( ! isset( $rule_data['data'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$current_day  = intval( date_i18n( 'j' ) );
		$selected_day = intval( $rule_data['data'] );
		$result       = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_day, $selected_day );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_values() {
		$days      = range( 1, 31 );
		$day_array = array_combine( $days, $days );

		return $day_array;
	}

}

class BWFAN_Rule_Current_Day_Of_Week extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_day_of_week' );
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

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}

		$current_day_of_week  = date( 'l' );
		$selected_day_of_week = $rule_data['data'];
		$result               = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_day_of_week, $selected_day_of_week );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
		);
	}

	public function get_possible_rule_values() {
		$days_of_week = array(
			'Sunday'    => __( 'Sunday', 'wp-marketing-automations-pro' ),
			'Monday'    => __( 'Monday', 'wp-marketing-automations-pro' ),
			'Tuesday'   => __( 'Tuesday', 'wp-marketing-automations-pro' ),
			'Wednesday' => __( 'Wednesday', 'wp-marketing-automations-pro' ),
			'Thursday'  => __( 'Thursday', 'wp-marketing-automations-pro' ),
			'Friday'    => __( 'Friday', 'wp-marketing-automations-pro' ),
			'Saturday'  => __( 'Saturday', 'wp-marketing-automations-pro' )
		);

		return $days_of_week;
	}
}

class BWFAN_Rule_Current_Time extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_time' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'time';
	}

	public function is_match_v2( $automation_data, $rule_data ) {

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}
		if ( empty( $rule_data['data'] ) ) {
			return $this->return_is_match( false, $rule_data );
		}
		$current_time  = strtotime( date_i18n( 'H:i' ) );
		$selected_time = strtotime( $rule_data['data'] );
		$result        = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_time, $selected_time );

		return $this->return_is_match( $result, $rule_data );
	}


	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' )
		);
	}

}

class BWFAN_Rule_Current_Date_Time extends BWFAN_Rule_Base {

	public function __construct() {
		$this->v2 = true;
		$this->v1 = false;
		parent::__construct( 'current_date_time' );
	}

	/** v2 Methods: START */
	public function get_rule_type() {
		return 'datetime';
	}

	public function is_match_v2( $automation_data, $rule_data ) {
		$result = false;

		if ( ! isset( $automation_data['global'] ) || ! is_array( $automation_data['global'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		if ( empty( $rule_data['data'] ) ) {
			return $this->return_is_match( $result, $rule_data );
		}
		$current_datetime  = date_i18n( 'Y-m-d\TH:i' );
		$datetime          = new DateTime( $rule_data['data'] );
		$selected_datetime = $datetime->format( 'Y-m-d\TH:i' );
		$result            = BWFAN_DateTime_Rule_Common::is_match( $rule_data['rule'], $current_datetime, $selected_datetime );

		return $this->return_is_match( $result, $rule_data );
	}

	public function get_possible_rule_operators() {
		return array(
			'==' => __( 'is', 'wp-marketing-automations-pro' ),
			'!=' => __( 'is not', 'wp-marketing-automations-pro' ),
			'>'  => __( 'is after', 'wp-marketing-automations-pro' ),
			'<'  => __( 'is before', 'wp-marketing-automations-pro' ),
			'>=' => __( 'is on or after', 'wp-marketing-automations-pro' ),
			'<=' => __( 'is on or before', 'wp-marketing-automations-pro' )
		);
	}

}



