<?php

class BWFCRM_Filter_AFFWP_Status extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'affwp_status';
		add_filter( 'bwfan_contact_sql_join_query', array( $this, 'join_query' ), 10, 2 );
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function join_query( $join_query, $custom_filters ) {
		if ( ! $this->is_current_filter_exists( $custom_filters ) ) {
			return $join_query;
		}

		/** If Join is already there */
		if ( false !== strpos( $join_query, 'affiliate_wp_affiliates' ) ) {
			return $join_query;
		}

		global $wpdb;
		$sql = " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates as awp ON c.wpid=awp.user_id";

		return $join_query . $sql;
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$filter_value = explode( ',', $filter_value );

		$filter_rule  = 'any' === $filter_rule ? 'IN' : 'NOT IN';
		$filter_value = implode( "','", $filter_value );

		return "awp.status {$filter_rule} ('{$filter_value}')";
	}
}

$ins = BWFCRM_Filter_AFFWP_Status::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
