<?php

class BWFCRM_Filter_City extends BWFCRM_Custom_Filter_Base {
	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		$this->name = 'city';
		add_filter( 'bwfan_contact_sql_join_query', array( $this, 'join_query' ), 10, 2 );
		add_filter( 'bwfan_contact_sql_where_query', array( $this, 'where_query' ), 10, 4 );
	}

	/**
	 * Add join of custom filter
	 *
	 * @param string $join_query
	 * @param array $custom_filters
	 *
	 * @return string
	 */
	public function join_query( $join_query, $custom_filters ) {
		// Avoid duplicate joins
		if ( ! $this->is_current_filter_exists( $custom_filters ) || strpos( $join_query, 'bwf_contact_fields' ) !== false ) {
			return $join_query;
		}

		global $wpdb;

		$sql = " LEFT JOIN {$wpdb->prefix}bwf_contact_fields as cm ON c.id=cm.cid";

		return $join_query . $sql;
	}

	/**
	 * Add WHERE clause for city filter
	 *
	 * @param string $filter_where
	 * @param string $filter_key
	 * @param string $filter_rule
	 * @param mixed $filter_value
	 *
	 * @return string
	 */
	public function where_query( $filter_where, $filter_key, $filter_rule, $filter_value ) {
		if ( $this->name !== $filter_key || empty( $filter_value ) ) {
			return $filter_where;
		}

		$city = BWFAN_Model_Fields::get_field_by_slug( 'city' );
		if ( empty( $city ) || ! isset( $city['ID'] ) ) {
			return $filter_where;
		}

		$city_field = 'cm.f' . $city['ID'];

		if ( is_string( $filter_value ) && strpos( $filter_value, ',' ) !== false ) {
			$filter_value = explode( ',', $filter_value );
		}

		$filter_value = is_array( $filter_value ) ? array_map( 'trim', $filter_value ) : trim( $filter_value );

		global $wpdb;

		switch ( $filter_rule ) {
			case 'is':
				$operator = is_array( $filter_value ) ? 'IN' : '=';
				break;

			case 'is_not':
				$operator = is_array( $filter_value ) ? 'NOT IN' : '!=';
				break;

			case 'contains':
			case 'not_contains':
			case 'starts_with':
			case 'ends_with':
				$operator = ( $filter_rule === 'not_contains' ) ? 'NOT LIKE' : 'LIKE';

				if ( is_array( $filter_value ) ) {
					$conditions = [];
					foreach ( $filter_value as $val ) {
						$val          = esc_sql( $wpdb->esc_like( $val ) );
						$like_value   = $this->get_like_value( $filter_rule, $val );
						$conditions[] = "$city_field $operator '$like_value'";
					}
					$connector     = ( $operator === 'LIKE' ) ? 'OR' : 'AND';
					$condition_sql = '(' . implode( " $connector ", $conditions ) . ')';
				} else {
					$val           = esc_sql( $wpdb->esc_like( $filter_value ) );
					$like_value    = $this->get_like_value( $filter_rule, $val );
					$condition_sql = "$city_field $operator '$like_value'";
				}
				break;

			case 'is_blank':
				$condition_sql = "($city_field IS NULL OR $city_field = '')";
				break;

			case 'is_not_blank':
				$condition_sql = "($city_field IS NOT NULL AND $city_field != '')";
				break;

			default:
				return $filter_where;
		}

		/** Handle 'is' and 'is_not' with single or multiple values */
		if ( in_array( $filter_rule, [ 'is', 'is_not' ] ) ) {
			if ( is_array( $filter_value ) ) {
				$escaped_values = array_map( 'esc_sql', $filter_value );
				$values_list    = "('" . implode( "','", $escaped_values ) . "')";
				$condition_sql  = "$city_field $operator $values_list";
			} else {
				$escaped_value = esc_sql( $filter_value );
				$condition_sql = "$city_field $operator '$escaped_value'";
			}
		}

		/** Append the condition to filter_where */
		if ( ! empty( $filter_where ) ) {
			$filter_where .= " AND $condition_sql";
		} else {
			$filter_where = $condition_sql;
		}

		return $filter_where;
	}

	private function get_like_value( $filter_rule, $value ) {
		switch ( $filter_rule ) {
			case 'contains':
			case 'not_contains':
				return "%$value%";
			case 'starts_with':
				return "$value%";
			case 'ends_with':
				return "%$value";
			default:
				return $value;
		}
	}
}

$ins = BWFCRM_Filter_City::get_instance();
BWFCRM_Filters::register_custom_filter( $ins->name );
