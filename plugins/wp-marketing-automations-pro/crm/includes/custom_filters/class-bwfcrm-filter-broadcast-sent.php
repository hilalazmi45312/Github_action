<?php

class BWFCRM_Filter_Broadcast_Sent extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'broadcast_sent';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$status_send    = BWFAN_Email_Conversations::$STATUS_SEND;
		$type_broadcast = BWFAN_Email_Conversations::$TYPE_CAMPAIGN;
		$query          = '';
		switch ( $filter_rule ) {
			case 'yes':
				$query = " EXISTS";
				break;
			case 'no':
				$query = " NOT EXISTS";
				break;
		}
		if ( ! empty( $query ) ) {
			global $wpdb;
			$query = " $query ( SELECT 1 FROM {$wpdb->prefix}bwfan_engagement_tracking AS egt WHERE c.id = egt.cid AND egt.oid = $filter_value AND egt.type = $type_broadcast AND egt.c_status = $status_send ) ";
		}

		return $query;
	}
}

$ins = BWFCRM_Filter_Broadcast_Sent::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
