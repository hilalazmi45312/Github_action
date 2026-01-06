<?php

class BWFCRM_Filter_Funnelkit_Checkouts extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'funnelkit_checkout';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$sub_rule       = 'AND';
		$group_by_query = " GROUP BY wp_st.cid";
		$query          = "EXISTS";
		global $wpdb;
		switch ( $filter_rule ) {
			case 'any':
				$sub_rule = 'OR';
				break;
			case 'none':
				$group_by_query = '';
				$query          = "NOT EXISTS";
				break;
		}
		$filter_rule  = 'LIKE';
		$filter_value = explode( ',', $filter_value );

		$filter_value = array_map( function ( $val ) use ( $filter_rule ) {
			return "wp_st.wfacp_id $filter_rule '$val'";
		}, $filter_value );

		$filter_value = implode( " {$sub_rule} ", $filter_value );
		if ( empty( $filter_value ) ) {
			return '';
		}

		return " $query ( SELECT 1 FROM {$wpdb->prefix}wfacp_stats as wp_st WHERE c.id = wp_st.cid AND ($filter_value) $group_by_query ) ";
	}

}

$ins = BWFCRM_Filter_Funnelkit_Checkouts::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
