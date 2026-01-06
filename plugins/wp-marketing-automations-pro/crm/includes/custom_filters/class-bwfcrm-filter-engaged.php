<?php

class BWFCRM_Filter_Engaged extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'engaged';
		add_filter( 'bwfan_contact_sql_join_query', array( $this, 'join_query' ), 10, 2 );
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	/**
	 * Add join of custom filter
	 *
	 * @param $join_query
	 * @param $custom_filters
	 *
	 * @return string
	 */
	public function join_query( $join_query, $custom_filters ) {
		if ( ! $this->is_current_filter_exists( $custom_filters ) || strpos( $join_query, 'bwf_contact_fields' ) !== false ) {
			return $join_query;
		}

		global $wpdb;

		$table_name = $wpdb->prefix . 'bwf_contact_fields';

		$sql = " LEFT JOIN {$table_name} as cm ON c.id=cm.cid";

		return $join_query . $sql;
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		/** Get Last Sent & Last Open */
		$last_sent  = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		$last_open  = BWFAN_Model_Fields::get_field_by_slug( 'last-open' );
		$last_click = BWFAN_Model_Fields::get_field_by_slug( 'last-click' );
		if ( empty( $last_sent ) || ! isset( $last_sent['ID'] ) || empty( $last_open ) || ! isset( $last_open['ID'] ) || empty( $last_click ) || ! isset( $last_click['ID'] ) ) {
			return $filter_where;
		}

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

		$last_sent  = 'f' . $last_sent['ID'];
		$last_open  = 'f' . $last_open['ID'];
		$last_click = 'f' . $last_click['ID'];

		$condition = "cm.$last_sent IS NOT NULL AND";
		switch ( $filter_rule ) {
			case 'over':
			case 'past':
			$operator = $filter_rule === 'over' ? '<' : '>=';
			/** Over and Past rule */
			if ( ! is_array( $filter_value ) ) {
				return "($condition ( cm.$last_open $operator '$filter_value' OR cm.$last_click $operator '$filter_value' ))";
			}
			break;
			case 'between':
				$last_open  = "( cm.$last_open >= '" . $filter_value[1] . "' AND cm.$last_open <= '" . $filter_value[0] . "')";
				$last_click = "( cm.$last_click >= '" . $filter_value[1] . "' AND cm.$last_click <= '" . $filter_value[0] . "')";

				return "($condition ( $last_open OR $last_click ))";
		}

		return;
	}
}

$ins = BWFCRM_Filter_Engaged::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
