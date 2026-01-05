<?php

class BWFCRM_Filter_Broadcast_Click extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'broadcast_click';
		add_filter( 'bwfan_contact_sql_join_query', array( $this, 'join_query' ), 10, 2 );
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function join_query( $join_query, $custom_filters ) {
		if ( ! $this->is_current_filter_exists( $custom_filters ) ) {
			return $join_query;
		}

		/** If Join is already there */
		if ( false !== strpos( $join_query, 'bwfan_engagement_tracking as et' ) ) {
			return $join_query;
		}

		global $wpdb;

		$sql = " LEFT JOIN {$wpdb->prefix}bwfan_engagement_tracking as et ON c.id=et.cid";

		return $join_query . $sql;
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$status_send    = BWFAN_Email_Conversations::$STATUS_SEND;
		$type_broadcast = BWFAN_Email_Conversations::$TYPE_CAMPAIGN;
		switch ( $filter_rule ) {
			case 'yes':
				$filter_value = "et.click > 0 AND et.c_status = $status_send AND et.type = $type_broadcast AND et.oid = $filter_value";
				break;
			case 'no':
				$filter_value = "et.click = 0 AND et.c_status = $status_send AND et.type = $type_broadcast AND et.oid = $filter_value";
				break;
		}

		return $filter_value;
	}
}

$ins = BWFCRM_Filter_Broadcast_Click::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
