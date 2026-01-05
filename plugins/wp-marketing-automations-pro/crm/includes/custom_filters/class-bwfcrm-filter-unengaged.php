<?php

class BWFCRM_Filter_Unengaged extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'unengaged';
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
		$last_sent = BWFAN_Model_Fields::get_field_by_slug( 'last-sent' );
		$last_open = BWFAN_Model_Fields::get_field_by_slug( 'last-open' );
		if ( empty( $last_sent ) || ! isset( $last_sent['ID'] ) || empty( $last_open ) || ! isset( $last_open['ID'] ) ) {
			return $filter_where;
		}

		$last_sent = 'f' . $last_sent['ID'];
		$last_open = 'f' . $last_open['ID'];

		$current_time = current_time( 'timestamp' );
		$past_time    = $current_time - ( DAY_IN_SECONDS * absint( $filter_value ) );
		$past_time    = date( 'Y-m-d', $past_time );

		return "(cm.$last_sent IS NOT NULL AND (cm.$last_open IS NULL OR cm.$last_open < '$past_time'))";
	}
}

$ins = BWFCRM_Filter_Unengaged::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
