<?php

class BWFCRM_Filter_WP_User_Role extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'role';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		global $wpdb;

		if ( 'role' !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$query    = " EXISTS";
		$sub_rule = " AND ";

		switch ( $filter_rule ) {
			case 'is':
				$sub_rule = " OR ";
				break;
			case 'is_not':
				$query = " NOT EXISTS";
				break;
		}

		$filter_value = explode( ',', $filter_value );
		$filter_value = array_map( 'trim', $filter_value );

		$inner_query = [];
		foreach ( $filter_value as $key => $value ) {
			$inner_query[] = "$query ( SELECT 1 FROM `{$wpdb->usermeta}` AS um_{$key} WHERE um_{$key}.`user_id` = c.`wpid` AND um_{$key}.`meta_key` = '{$wpdb->prefix}capabilities' AND um_{$key}.`meta_value` LIKE '%\"{$value}\"%' )";
		}

		$query = implode( " {$sub_rule} ", $inner_query );

		return ' ( ' . $query . ' ) ';
	}
}

$ins = BWFCRM_Filter_WP_User_Role::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
