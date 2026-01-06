<?php

class BWFCRM_AFFWP_Column_Preferences {
	private static $ins = null;

	/** @return BWFCRM_AFFWP_Column_Preferences */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		add_filter( 'bwfcrm_contact_columns_group', array( $this, 'add_column_group' ), 10, 2 );
		add_filter( 'bwfcrm_contact_columns_preferences', array( $this, 'add_columns' ), 10, 3 );
		add_filter( 'bwfcrm_modify_contact_columns_preferences_data', [ $this, 'modify_columns_data' ] );
	}

	public function add_column_group( $slug, $fields ) {
		if ( ! bwfan_is_affiliatewp_active() || "affiliate" !== $fields['groupKey'] ) {
			return $slug;
		}

		$column = " awp.affiliate_id AS `$slug` ";
		switch ( $slug ) {
			case 'affwp_status' :
				$column = " awp.status AS `$slug` ";
				break;
			case 'affwp_total_earnings' :
				$column = " awp.earnings AS `$slug` ";
				break;
			case 'affwp_unpaid_earnings' :
				$column = " awp.unpaid_earnings AS `$slug` ";
				break;
			case 'affwp_total_referrals' :
				$column = " awp.referrals AS `$slug` ";
				break;
			case 'affwp_total_visits' :
				$column = " awp.visits AS `$slug` ";
				break;
			case 'affwp_payment_email' :
				$column = " awp.payment_email AS `$slug` ";
				break;
		}

		return $column;
	}

	public function add_columns( $data, $group_columns, $join_cf_query ) {
		if ( ! bwfan_is_affiliatewp_active() || ! isset( $group_columns['affiliate'] ) ) {
			return $data;
		}

		$affiliate     = isset( $group_columns['affiliate'] ) && is_array( $group_columns['affiliate'] ) ? $group_columns['affiliate'] : [];
		$total_columns = isset( $data['total_columns'] ) && is_array( $data['total_columns'] ) ? array_merge( $data['total_columns'], $affiliate ) : [];

		$join = '';
		/** Checking ff Join is already in custom filter */
		if ( false === strpos( $join_cf_query, 'affiliate_wp_affiliates' ) ) {
			global $wpdb;
			$join = " LEFT JOIN {$wpdb->prefix}affiliate_wp_affiliates as awp ON c.wpid=awp.user_id";
		}

		return [
			'total_columns' => $total_columns,
			'join_query'    => isset( $data['join_query'] ) ? $data['join_query'] . $join : $join
		];
	}

	public function modify_columns_data( $contact_data ) {
		if ( ! bwfan_is_affiliatewp_active() ) {
			return $contact_data;
		}
		if ( isset( $contact_data['affwp_total_earnings'] ) ) {
			$total_earnings                       = ! empty( $contact_data['affwp_total_earnings'] ) ? $contact_data['affwp_total_earnings'] : 0;
			$contact_data['affwp_total_earnings'] = affwp_currency_filter( affwp_format_amount( $total_earnings ) );
		}
		if ( isset( $contact_data['affwp_unpaid_earnings'] ) ) {
			$unpaid_earnings                       = ! empty( $contact_data['affwp_unpaid_earnings'] ) ? $contact_data['affwp_unpaid_earnings'] : 0;
			$contact_data['affwp_unpaid_earnings'] = affwp_currency_filter( affwp_format_amount( $unpaid_earnings ) );
		}

		/** static column */
		if ( ! empty( $contact_data['affwp_affiliate_url'] ) ) {
			$contact_data['affwp_affiliate_url'] = affwp_get_affiliate_referral_url( [ 'affiliate_id' => $contact_data['affwp_affiliate_url'] ] );
		}

		return $contact_data;
	}
}

$ins = BWFCRM_AFFWP_Column_Preferences::get_instance();
