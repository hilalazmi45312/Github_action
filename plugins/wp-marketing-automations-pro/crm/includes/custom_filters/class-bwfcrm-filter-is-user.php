<?php

class BWFCRM_Filter_Is_User extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'is_user';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( 'is_user' !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$query = $filter_value === 'yes' ? " EXISTS" : " NOT EXISTS";

		global $wpdb;
		$inner_query = "$query ( SELECT 1 FROM `{$wpdb->users}` AS u WHERE u.`ID` = c.`wpid` )";

		return ' ( ' . $inner_query . ' ) ';
	}
}

BWFCRM_Filter_Is_User::get_instance();
BWFCRM_Filters::register_custom_filter( 'is_user' );
