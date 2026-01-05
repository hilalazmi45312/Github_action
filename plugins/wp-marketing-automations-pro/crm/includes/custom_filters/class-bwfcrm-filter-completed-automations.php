<?php

class BWFCRM_Filter_Completed_Automations extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'automation_completed';
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

	/**
	 * Add filter sql query
	 *
	 * @param $filter_key
	 * @param $filter_rule
	 * @param $filter_value
	 *
	 * @return false|string
	 */
	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( 'automation_completed' !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}
		$field_from_db = BWFAN_Model_Fields::get_field_by_slug( 'automation-completed' );
		if ( empty( $field_from_db ) || ! isset( $field_from_db['ID'] ) ) {
			return $filter_where;
		}
		$field_col = 'f' . $field_from_db['ID'];
		$not_null  = '';
		$sub_rule  = 'AND';
		switch ( $filter_rule ) {
			case 'any':
				$filter_rule = 'LIKE';
				$sub_rule = 'OR';
				break;
			case 'none':
				$filter_rule = 'NOT LIKE';
				$not_null    = " OR cm.$field_col IS NULL ";
				break;
			case 'all':
				$filter_rule = 'LIKE';
				break;
		}

		$filter_value = explode( ',', $filter_value );

		$filter_value = array_map( function ( $val ) use ( $filter_rule, $field_col ) {
			return "cm.$field_col $filter_rule '%\"$val\"%'";
		}, $filter_value );

		$filter_value = implode( " {$sub_rule} ", $filter_value );

		return ' ( ' . $filter_value . $not_null . ' ) ';

		
	}
}

$ins = BWFCRM_Filter_Completed_Automations::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
