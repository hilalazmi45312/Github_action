<?php

class BWFCRM_Filter_WLM_Reg extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'wlm_reg';
		add_filter( 'bwfan_contact_sql_join_query', array( $this, 'join_query' ), 10, 2 );
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function join_query( $join_query, $custom_filters ) {
		if ( ! $this->is_current_filter_exists( $custom_filters ) ) {
			return $join_query;
		}

		/** If Join is already there */
		if ( false !== strpos( $join_query, 'bwf_contact_wlm_fields' ) ) {
			return $join_query;
		}

		global $wpdb;

		$sql = " LEFT JOIN {$wpdb->prefix}bwf_contact_wlm_fields as wlm ON c.id=wlm.cid";

		return $join_query . $sql;
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		BWFCRM_Integration_Wishlist_Member::add_contact_filter( 'reg', $filter_rule, $filter_value );

		return $filter_where;
	}
}

$ins = BWFCRM_Filter_WLM_Reg::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
