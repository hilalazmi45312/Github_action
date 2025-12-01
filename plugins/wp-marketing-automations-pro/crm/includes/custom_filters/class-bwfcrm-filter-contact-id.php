<?php

class BWFCRM_Filter_Contact_ID extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'contact_id';
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( 'contact_id' !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		switch ( $filter_rule ) {
			case 'is':
				$filter_rule = '=';
				break;
			case 'is_not':
				$filter_rule = '!=';
				break;
			case 'more_than':
				$filter_rule = '>';
				break;
		}

		return "c.id {$filter_rule} {$filter_value}";
	}
}

$ins = BWFCRM_Filter_Contact_ID::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
