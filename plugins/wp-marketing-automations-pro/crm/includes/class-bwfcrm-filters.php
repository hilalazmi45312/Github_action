<?php

#[AllowDynamicProperties]
class BWFCRM_Filters {
	/** Filter Natures */
	public static $TYPE_JSON_ARRAY = 1;
	public static $TYPE_STRING = 2;
	public static $TYPE_STRING_EXACT = 3;
	public static $TYPE_DATE = 4;
	public static $TYPE_BOOL = 5;
	public static $TYPE_NUMBER = 6;
	public static $TYPE_CURRENCY = 7;
	public static $TYPE_DATE_RELATIVE = 8;
	public static $TYPE_NUMBER_EXACT = 9;
	public static $TYPE_DATETIME = 10;
	public static $TYPE_TIME = 11;

	public static $TYPE_CUSTOM_FILTER = 'custom';

	/** Rules by Filter Nature */
	public static $RULES_JSON_ARRAY = array( 'any', 'none', 'all' );
	public static $RULES_STRING = array( 'is', 'is_not', 'contains', 'not_contains', 'starts_with', 'ends_with', 'is_blank', 'is_not_blank' );
	public static $RULES_STRING_EXACT = array( 'is', 'is_not' );
	public static $RULES_DATE = array( 'is', 'before', 'after', 'between' );
	public static $RULES_BOOL = array( 'yes', 'no' );
	public static $RULES_NUMBER = array( 'is', 'is_not', 'more_than', 'less_than', 'is_blank', 'is_not_blank' );
	public static $RULES_CURRENCY = array( 'more_than', 'less_than' );
	public static $RULES_DATE_RELATIVE = array( 'over', 'past' );
	public static $RULES_NUMBER_EXACT = array( 'is', 'is_not' );

	/** Date Timezones */
	public static $TIMEZONE_UTC = 1;
	public static $TIMEZONE_STORE = 2;

	/** Customer Filters */
	public static $wc_filters = array(
		'total_order_value',
		'total_order_count',
		'l_order_date',
		'l_order_days',
		'f_order_date',
		'f_order_days',
		'aov',
		'purchased_products_cats',
		'purchased_products_tags',
		'purchased_products',
		'used_coupons',
		'has_purchased',
		'has_used_any_coupons',
	);
	/** Contacts Filters */
	public static $wp_filters = array(
		'country',
		'creation_date',
		'f_name',
		'l_name',
		'contact_no',
		'state',
		'status',
		'tags',
		'lists',
		'email',
	);

	/** Contacts Meta Filters */
	public static $field_filters = array(
		'company',
		'dob',
		'gender',
		'postcode',
		'address-1',
		'address-2',
		'last-login',
		'last-open',
		'last-click',
		'last-sent',
	);

	public static $custom_filters = array();

	public static function register_custom_filter( $field ) {
		self::$custom_filters[] = $field;
	}

	/**
	 * Get filter type by contact field type
	 *
	 * @param $type
	 *
	 * @return int
	 */
	public static function get_type_by_field_type( $type ) {
		/** Datetime & time field type value */
		$type_datetime = property_exists( 'BWFCRM_Fields', 'TYPE_DATETIME' ) && BWFCRM_Fields::$TYPE_DATETIME ? BWFCRM_Fields::$TYPE_DATETIME : 8;
		$type_time     = property_exists( 'BWFCRM_Fields', 'TYPE_TIME' ) && BWFCRM_Fields::$TYPE_TIME ? BWFCRM_Fields::$TYPE_TIME : 9;

		switch ( intval( $type ) ) {
			case BWFCRM_Fields::$TYPE_CHECKBOX:
				return self::$TYPE_JSON_ARRAY;

			case BWFCRM_Fields::$TYPE_DATE:
				return self::$TYPE_DATE;

			case $type_datetime:
				return self::$TYPE_DATETIME;

			case $type_time:
				return self::$TYPE_TIME;

			case BWFCRM_Fields::$TYPE_NUMBER:
				return self::$TYPE_NUMBER;

			case BWFCRM_Fields::$TYPE_SELECT:
			case BWFCRM_Fields::$TYPE_RADIO:
				return self::$TYPE_STRING_EXACT;

			case BWFCRM_Fields::$TYPE_TEXT:
			case BWFCRM_Fields::$TYPE_TEXTAREA:
			default:
				return self::$TYPE_STRING;
		}
	}

	/**
	 * Get default rule by filter's nature
	 *
	 * @param $filter_type
	 *
	 * @return string
	 */
	public static function get_default_rule_by_filter_type( $filter_type ) {
		switch ( absint( $filter_type ) ) {
			case self::$TYPE_JSON_ARRAY:
				return 'any';
			case self::$TYPE_STRING:
				return 'contains';
			case self::$TYPE_STRING_EXACT:
				return 'is';
			case self::$TYPE_DATE:
				return 'is';
			case self::$TYPE_BOOL:
				return 'yes';
			case self::$TYPE_NUMBER:
			case self::$TYPE_NUMBER_EXACT:
				return 'is';
			case self::$TYPE_CURRENCY:
				return 'more_than';
			default:
				return 'is';
		}
	}

	/**
	 * Get default rule by contact field type
	 *
	 * @param $type
	 *
	 * @return string
	 */
	public static function get_default_rule_by_field_type( $type ) {
		switch ( absint( $type ) ) {
			case BWFCRM_Fields::$TYPE_CHECKBOX:
				return self::get_default_rule_by_filter_type( self::$TYPE_JSON_ARRAY );

			case BWFCRM_Fields::$TYPE_DATE:
				return self::get_default_rule_by_filter_type( self::$TYPE_DATE );

			case BWFCRM_Fields::$TYPE_NUMBER:
				return self::get_default_rule_by_filter_type( self::$TYPE_NUMBER );

			case BWFCRM_Fields::$TYPE_SELECT:
			case BWFCRM_Fields::$TYPE_RADIO:
				return self::get_default_rule_by_filter_type( self::$TYPE_STRING_EXACT );

			case BWFCRM_Fields::$TYPE_TEXT:
			case BWFCRM_Fields::$TYPE_TEXTAREA:
			default:
				return self::get_default_rule_by_filter_type( self::$TYPE_STRING );
		}
	}

	public static function get_type_by_wc_filters( $filter_key ) {
		switch ( $filter_key ) {
			case 'total_order_value':
				return self::$TYPE_CURRENCY;
			case 'total_order_count':
				return self::$TYPE_NUMBER;
			case 'l_order_date':
			case 'f_order_date':
				return self::$TYPE_DATE;
			case 'l_order_days':
			case 'f_order_days':
				return self::$TYPE_DATE_RELATIVE;
			case 'aov':
				return self::$TYPE_CURRENCY;
			case 'purchased_products_cats':
				return self::$TYPE_JSON_ARRAY;
			case 'purchased_products_tags':
				return self::$TYPE_JSON_ARRAY;
			case 'purchased_products':
				return self::$TYPE_JSON_ARRAY;
			case 'used_coupons':
				return self::$TYPE_JSON_ARRAY;
			case 'has_purchased':
				return self::$TYPE_BOOL;
			case 'has_used_any_coupons':
				return self::$TYPE_BOOL;
			default:
				return 0;
		}
	}

	public static function get_type_by_wp_filters( $filter_key ) {
		switch ( $filter_key ) {
			case 'country':
				return self::$TYPE_STRING_EXACT;
			case 'creation_date':
				return self::$TYPE_DATE;
			case 'f_name':
				return self::$TYPE_STRING;
			case 'l_name':
				return self::$TYPE_STRING;
			case 'contact_no':
				return self::$TYPE_STRING;
			case 'state':
				return self::$TYPE_STRING;
			case 'status':
				return self::$TYPE_NUMBER_EXACT;
			case 'tags':
				return self::$TYPE_JSON_ARRAY;
			case 'lists':
				return self::$TYPE_JSON_ARRAY;
			case 'email':
				return self::$TYPE_STRING;
			default:
				return 0;
		}
	}

	public static function get_type_by_field_filters( $filter_key ) {
		switch ( $filter_key ) {
			case 'company':
				return self::$TYPE_STRING;
			case 'dob':
				return self::$TYPE_DATE;
			case 'gender':
				return self::$TYPE_STRING_EXACT;
			case 'postcode':
				return self::$TYPE_STRING;
			case 'address-1':
				return self::$TYPE_STRING;
			case 'address-2':
				return self::$TYPE_STRING;
			case 'last-login':
				return self::$TYPE_DATE_RELATIVE;
			case 'last-open':
				return self::$TYPE_DATE_RELATIVE;
			case 'last-sent':
				return self::$TYPE_DATE_RELATIVE;
			case 'last-click':
				return self::$TYPE_DATE_RELATIVE;
			default:
				return 0;
		}
	}

	public static function get_filter_timezone( $filter_key ) {
		return self::$TIMEZONE_STORE;
		switch ( $filter_key ) {
			case 'creation_date':
			case 'l_order_date':
			case 'f_order_date':
			default:
				return self::$TIMEZONE_STORE;
		}
	}

	public static function get_alternate_filter_key( $filter_key ) {
		switch ( $filter_key ) {
			case 'l_order_days':
				return 'l_order_date';
			case 'f_order_days':
				return 'f_order_date';
			default:
				return $filter_key;
		}
	}

	private static function _normalize_single_filter( $filter ) {
		/** Contact Filters */
		foreach ( self::$wp_filters as $filter_key ) {
			if ( substr( $filter, 0, strlen( $filter_key ) ) === $filter_key ) {
				$filter = explode( $filter_key . '_', $filter );

				return array(
					'key'      => self::get_alternate_filter_key( $filter_key ),
					'category' => 'c',
					'rule'     => count( $filter ) > 1 ? $filter[1] : '',
					'type'     => self::get_type_by_wp_filters( $filter_key ),
				);
			}
		}

		/** Customer Filters */
		foreach ( self::$wc_filters as $filter_key ) {
			if ( substr( $filter, 0, strlen( $filter_key ) ) === $filter_key ) {
				$filter = explode( $filter_key . '_', $filter );

				return array(
					'key'      => self::get_alternate_filter_key( $filter_key ),
					'category' => 'wc',
					'rule'     => count( $filter ) > 1 ? $filter[1] : '',
					'type'     => self::get_type_by_wc_filters( $filter_key ),
				);
			}
		}

		/** Contact Field Filters */
		foreach ( self::$field_filters as $filter_key ) {
			if ( substr( $filter, 0, strlen( $filter_key ) ) === $filter_key ) {
				$filter      = explode( $filter_key . '_', $filter );
				$filter_type = self::get_type_by_field_filters( $filter_key );
				$filter_key  = BWFCRM_Fields::get_field_id_by_slug( self::get_alternate_filter_key( $filter_key ) );
				if ( empty( $filter_key ) ) {
					return false;
				}

				return array(
					'key'      => 'f' . absint( $filter_key ),
					'category' => 'cm',
					'rule'     => count( $filter ) > 1 ? $filter[1] : '',
					'type'     => $filter_type,
				);
			}
		}

		/** Custom Filters */
		foreach ( self::$custom_filters as $filter_key ) {
			if ( substr( $filter, 0, strlen( $filter_key ) ) === $filter_key ) {
				$filter = explode( $filter_key . '_', $filter );

				return array(
					'key'      => $filter_key,
					'category' => 'custom',
					'rule'     => count( $filter ) > 1 ? $filter[1] : '',
					'type'     => 'custom',
				);
			}
		}

		return false;
	}

	public static function _convert_to_appropriate_timezone( $key, $value ) {
		if ( self::$TIMEZONE_UTC === self::get_filter_timezone( $key ) ) {
			return get_gmt_from_date( $value, 'Y-m-d' );
		}

		return $value;
	}

	public static function _handle_date_filter_value( $filter, $filter_value ) {
		if ( is_array( $filter_value ) ) {
			return array_map( function ( $date_val ) use ( $filter ) {
				return self::_convert_to_appropriate_timezone( $filter['key'], $date_val );
			}, $filter_value );
		}

		return self::_convert_to_appropriate_timezone( $filter['key'], $filter_value );
	}

	public static function _normalize_custom_fields_filters( $filters ) {
		if ( ! is_array( $filters ) || empty( $filters ) ) {
			return array();
		}

		/** Fetch Custom Field filters from filters */
		$field_filters = array();
		foreach ( $filters as $filter_key => $filter ) {
			if ( false !== strpos( $filter_key, 'bwf_cf' ) ) {
				$field_filters[ $filter_key ] = $filter;
			}
		}

		if ( ! is_array( $filters ) || empty( $filters ) ) {
			return array();
		}

		$normalized_filters = array();
		foreach ( $field_filters as $filter_key => $filter ) {
			$filter_key = explode( 'bwf_cf', $filter_key )[1];
			$filter_key = explode( '_', $filter_key, 2 );

			$field_id     = absint( $filter_key[0] );
			$filter_rule  = $filter_key[1];
			$filter_value = $filter;
			if ( empty( $filter_value ) ) {
				continue;
			}

			$filter_db     = BWFAN_Model_Fields::get_field_by_id( $field_id );
			$filter_nature = self::get_type_by_field_type( absint( $filter_db['type'] ) );
			if ( self::$TYPE_JSON_ARRAY === $filter_nature ) {
				$filter_value = explode( ',', $filter_value );
			}

			$normalized_filters['cm'][] = array(
				'key'   => 'f' . $field_id,
				'value' => is_array( $filter_value ) ? array_map( 'trim', $filter_value ) : trim( $filter_value ),
				'type'  => $filter_nature,
				'rule'  => $filter_rule,
			);
		}

		return $normalized_filters;
	}

	public static function _normalize_input_filters( $filters ) {
		/** Add Custom Field Filters */
		$normalized_filters = self::_normalize_custom_fields_filters( $filters );

		/** Add Rest of the Filters */
		foreach ( $filters as $filter => $filter_value ) {
			$filter = self::_normalize_single_filter( $filter );
			if ( empty( $filter ) || ! is_array( $filter ) ) {
				continue;
			}

			if ( self::$TYPE_DATE === $filter['type'] || self::$TYPE_DATE_RELATIVE === $filter['type'] ) {
				$filter_value = self::_handle_date_filter_value( $filter, $filter_value );
			}

			if ( 'total_order_count' === $filter['key'] || 'total_order_value' === $filter['key'] ) {
				$filter['value'] = $filter_value;
			} else {
				$filter['value'] = is_array( $filter_value ) ? array_map( 'trim', $filter_value ) : trim( $filter_value );
			}
			$filter_category = $filter['category'];
			unset( $filter['category'] );

			$normalized_filters[ $filter_category ][] = $filter;
		}

		return $normalized_filters;
	}

	public static function maybe_add_unsubscribe_lists_filter( $filters ) {
		if ( ! is_array( $filters ) || ! isset( $filters['lists_any'] ) ) {
			return $filters;
		}

		$settings = BWFAN_Common::get_global_settings();
		$enabled  = isset( $settings['bwfan_unsubscribe_lists_enable'] ) ? $settings['bwfan_unsubscribe_lists_enable'] : 0;
		if ( 0 === absint( $enabled ) ) {
			return $filters;
		}

		$public_lists = $settings['bwfan_unsubscribe_public_lists'];
		if ( empty( $public_lists ) || ! is_array( $public_lists ) ) {
			return $filters;
		}
		$lists = is_array( $filters['lists_any'] ) ? array_reduce( $filters['lists_any'], function ( $carry, $item ) {
			$items = explode( ',', $item );

			return array_replace( $carry, $items );
		}, array() ) : explode( ',', $filters['lists_any'] );
		$lists = array_values( array_intersect( $lists, $public_lists ) );
		$lists = implode( ',', $lists );

		$unsub_lists_field = BWFAN_Model_Fields::get_field_by_slug( 'unsubscribed-lists' );
		if ( is_array( $unsub_lists_field ) && isset( $unsub_lists_field['ID'] ) ) {
			$field_id                          = $unsub_lists_field['ID'];
			$filters["bwf_cf{$field_id}_none"] = $lists;
		}

		return $filters;
	}
}
