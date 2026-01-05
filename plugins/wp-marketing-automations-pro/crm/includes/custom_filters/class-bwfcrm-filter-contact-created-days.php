<?php

class BWFCRM_Filter_Contact_Created_Days extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'creation_days';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}


	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		// creating dates from the value
		if ( ! is_array( $filter_value ) ) {
			$filter_value = absint( $filter_value );
			$date         = new DateTime( 'now', wp_timezone() );
			$date->modify( "-$filter_value days" );
			$filter_value = $date->format( 'Y-m-d' );
		} else {
			$from = absint( $filter_value[0] );
			$date = new DateTime( 'now', wp_timezone() );
			$date->modify( "-$from days" );
			$from = $date->format( 'Y-m-d' );

			$to   = absint( $filter_value[1] );
			$date = new DateTime( 'now', wp_timezone() );
			$date->modify( "-$to days" );
			$to = $date->format( 'Y-m-d' );

			$filter_value = array( $from, $to );
		}

		switch ( $filter_rule ) {
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
				return;
		}

		/** Over and Past rule */
		if ( ! is_array( $filter_value ) ) {
			return "( DATE(c.creation_date) $filter_rule '$filter_value')";
		}

		/** Between Rule */
		return "( DATE(c.creation_date) >= '" . $filter_value[1] . "' AND DATE(c.creation_date) <= '" . $filter_value[0] . "')";
	}
}

$ins = BWFCRM_Filter_Contact_Created_Days::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
