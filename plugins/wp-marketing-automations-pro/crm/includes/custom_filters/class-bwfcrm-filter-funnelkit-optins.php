<?php

class BWFCRM_Filter_Funnelkit_Optins extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'funnelkit_optin';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$query    = " EXISTS";
		$sub_rule = " AND ";

		/** 3 cases : any | all | none */
		switch ( $filter_rule ) {
			case 'any':
				$sub_rule = " OR ";
				break;
			case 'none':
				$query = " NOT EXISTS";
				break;
		}

		$filter_value = explode( ',', $filter_value );
		$filter_value = array_map( 'trim', $filter_value );

		global $wpdb;
		$inner_query = [];
		foreach ( $filter_value as $key => $value ) {
			$inner_query[] = " $query ( SELECT 1 FROM `{$wpdb->prefix}bwf_optin_entries` AS bwf_opt_{$key} WHERE bwf_opt_{$key}.`cid` = c.`id` AND bwf_opt_{$key}.`step_id` = '{$value}' ) ";
		}

		$query = implode( " {$sub_rule} ", $inner_query );

		return ' ( ' . $query . ' ) ';
	}
}

$ins = BWFCRM_Filter_Funnelkit_Optins::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
