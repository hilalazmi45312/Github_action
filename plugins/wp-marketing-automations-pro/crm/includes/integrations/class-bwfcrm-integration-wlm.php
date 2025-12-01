<?php
/**
 * Wishlist Member Integration Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Integration_Wishlist_Member
 */
#[AllowDynamicProperties]
class BWFCRM_Integration_Wishlist_Member {

	public static $LEVEL_STATUS_UNCONFIRMED = 1;
	public static $LEVEL_STATUS_ACTIVE = 2;
	public static $LEVEL_STATUS_PENDING = 3;
	public static $LEVEL_STATUS_CANCELLED = 4;
	public static $LEVEL_STATUS_SCHEDULED = 5;
	public static $LEVEL_STATUS_EXPIRED = 6;

	private static $_contact_filters = array();

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		if ( ! BWFCRM_Common::is_wlm_integration_active() ) {
			return;
		}

		/** Contact Filters SQL Query */
		add_filter( 'bwfan_contact_sql_final_where_query', array( $this, 'get_status_level_where_sql' ), 10, 1 );
		add_filter( 'bwfan_contact_sql_final_where_query', array( $this, 'get_reg_level_where_sql' ), 11, 1 );
		add_filter( 'bwfan_contact_sql_final_where_query', array( $this, 'get_exp_level_where_sql' ), 12, 1 );
		add_filter( 'bwfan_contact_sql_final_where_query', array( $this, 'reset_contact_filters' ), 99, 1 );

		/** Contacts Export */
		add_filter( 'bwfcrm_get_export_custom_selections', array( $this, 'get_export_custom_selections' ) );
		add_filter( 'bwfcrm_export_csv_row_before_insert', array( $this, 'insert_wlm_export_data' ), 10, 3 );
		add_filter( 'bwfcrm_export_fields_headers', array( $this, 'modify_export_fields_headers' ) );

		/** WLM Events */
		add_action( 'wishlistmember_add_user_levels', array( $this, 'add_level_to_user' ), 20, 3 );
		add_action( 'wishlistmember_remove_user_levels', array( $this, 'remove_level_from_user' ), 20, 3 );
		add_action( 'wishlistmember_cancel_user_levels', array( $this, 'cancel_level_from_user' ), 20, 2 );
		add_action( 'wishlistmember_uncancel_user_levels', array( $this, 'uncancel_level_from_user' ), 20, 2 );
		add_action( 'wishlistmember_expire_user_levels', array( $this, 'expire_level_from_user' ), 20, 2 );
		add_action( 'wishlistmember_unexpire_user_levels', array( $this, 'unexpire_level_from_user' ), 20, 2 );
		add_action( 'wishlistmember_level_created', array( $this, 'level_created' ), 20, 2 );
		add_action( 'wishlistmember_level_deleted', array( $this, 'level_deleted' ), 20, 2 );
	}


	/** Contact Filters Functionality (Includes Static Variable also: self::$_contact_filters ) */
	public static function add_contact_filter( $key, $rule, $value ) {
		if ( empty( $key ) ) {
			return;
		}

		self::$_contact_filters[ $key ] = array(
			'rule'  => $rule,
			'value' => $value,
		);
	}


	/** Contact Filters SQL Queries */
	public function get_status_level_where_sql( $where ) {
		$filters = self::$_contact_filters;

		$is_status = isset( $filters['status'] );
		$is_level  = isset( $filters['level'] );

		$is_level = $is_level && false === strpos( $filters['level']['value'], ',' );

		if ( ! $is_status && ! $is_level ) {
			return $where;
		}

		$both_status_level = $is_status && $is_level;

		$is_status_yes = $is_status && 'any' === $filters['status']['rule'];
		$is_level_yes  = $is_level && 'yes' === $filters['level']['rule'];

		$status_val = $is_status ? explode( ',', $filters['status']['value'] ) : '';
		$level_val  = $is_level ? $filters['level']['value'] : '';

		/** If both of the status and level are 'yes' */
		if ( $both_status_level && $is_status_yes && $is_level_yes ) {
			$where_query = $this->get_formatted_status_where( $status_val, 'LIKE', 'OR', $level_val );

			return ! empty( $where_query ) ? $where . ' AND (' . $where_query . ')' : $where;
		}

		/** If any of the status or level is 'no' */
		$status_op     = $is_status_yes ? 'LIKE' : 'NOT LIKE';
		$status_sub_op = $is_status_yes ? 'OR' : 'AND';
		$level_op      = $is_level_yes ? 'LIKE' : 'NOT LIKE';

		$where_query    = array();
		$where_query[0] = $is_status ? $this->get_formatted_status_where( $status_val, $status_op, $status_sub_op, $level_val ) : false;
		$where_query[0] = ! empty( $where_query[0] ) ? '(' . $where_query[0] . ')' : $where_query[0];
		$where_query[1] = $is_level ? "wlm.status $level_op '%\"$level_val\"%'" : false;

		$where_query = array_filter( $where_query );
		$where_query = implode( ' AND ', $where_query );

		return $where . ' AND ' . $where_query;
	}

	public function get_formatted_status_where( $status, $rule, $subrule, $level = '' ) {
		$where_query = array_map( function ( $st ) use ( $level, $rule ) {
			if ( ! empty( $level ) ) {
				return "wlm.status $rule '%\"$level\":\"$st\"%'";
			}

			return "wlm.status $rule '%\"$st\"%'";
		}, $status );

		return implode( " $subrule ", $where_query );
	}

	public function get_date_level_where_sql( $where, $filter_slug = 'reg' ) {
		$filters = self::$_contact_filters;

		$is_date  = isset( $filters[ $filter_slug ] );
		$is_level = isset( $filters['level'] );

		$is_level = $is_level && false === strpos( $filters['level']['value'], ',' );

		/** Return if no 'registered' filter */
		if ( ! $is_date ) {
			return $where;
		}

		$level_filters = array();
		if ( ! $is_level ) {
			/** If level filter is not available */
			$levels = $this->get_levels();
			if ( empty( $levels ) ) {
				return $where;
			}

			$levels        = array_keys( $levels );
			$level_filters = array_map( function ( $level ) use ( $filters, $filter_slug ) {
				return array(
					'key'   => $filter_slug . '_' . $level,
					'rule'  => $filters[ $filter_slug ]['rule'],
					'value' => $filters[ $filter_slug ]['value'],
				);
			}, $levels );
		} else {
			/** If level filter is available */
			$level = $filters['level']['value'];

			$level_filters[] = array(
				'key'   => $filter_slug . '_' . $level,
				'rule'  => $filters[ $filter_slug ]['rule'],
				'value' => $filters[ $filter_slug ]['value'],
			);
		}

		/** Build final where query including all reg filters */
		$final_where = array();
		foreach ( $level_filters as $level ) {
			$filter_where = BWFCRM_Model_Contact::_set_date_filter_sql( $level, 'wlm', false );

			if ( ! empty( $filter_where ) ) {
				$final_where[] = "$filter_where";
			}
		}

		$final_where = '(' . implode( ' OR ', $final_where ) . ')';

		return $where . ' AND ' . $final_where;
	}

	public function get_reg_level_where_sql( $where ) {
		return $this->get_date_level_where_sql( $where, 'reg' );
	}

	public function get_exp_level_where_sql( $where ) {
		return $this->get_date_level_where_sql( $where, 'exp' );
	}

	public function reset_contact_filters( $where ) {
		if ( empty( self::$_contact_filters ) ) {
			return $where;
		}

		/** AND c.id=wlm.cid is applied, to get WLM related contacts only */
		self::$_contact_filters = array();

		return $where . ' AND c.id=wlm.cid';
	}


	/** WLM Deep Integration Table Functions */
	public function maybe_create_db_table() {
		$levels = $this->get_levels();
		if ( empty( $levels ) ) {
			return false;
		}

		$level_ids = array_keys( $levels );
		$db_cols   = array_map( function ( $id ) {
			return "reg_{$id} datetime default NULL,
                exp_{$id} datetime default NULL";
		}, $level_ids );

		/** Used 'Enter Key' after ',' because dbDelta recognise one line as a column */
		$db_cols = implode( ',
		', $db_cols );

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$creationSQL = "CREATE TABLE {$wpdb->prefix}bwf_contact_wlm_fields (
			id bigint(20) unsigned NOT NULL auto_increment,
			cid bigint(20) unsigned NOT NULL default 0,
			status varchar(255) NOT NULL,
			$db_cols,
			PRIMARY KEY (id),			
			KEY cid (cid)
		) $collate;";

		dbDelta( $creationSQL );

		return true;
	}

	public function maybe_add_level_db_columns( $level_id ) {
		if ( ! BWF_Model_Contact_WLM_Fields::check_if_level_exists( $level_id ) ) {
			BWF_Model_Contact_WLM_Fields::add_level_db_columns( $level_id );
		}
	}

	public function maybe_drop_unwanted_columns() {
		$levels = $this->get_levels();
		if ( empty( $levels ) ) {
			return false;
		}

		global $wpdb;
		$columns = $wpdb->get_results( 'SHOW COLUMNS FROM `' . $wpdb->prefix . 'bwf_contact_wlm_fields`', ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$default_cols = array( 'id', 'cid', 'status' );
		$levels       = array_keys( $levels );

		$cols_to_drop = array();
		foreach ( $columns as $column ) {
			$col = $column['Field'];
			if ( in_array( $col, $default_cols ) ) {
				continue;
			}

			/** Strip reg_ */
			if ( false !== strpos( $col, 'reg_' ) ) {
				$col = explode( 'reg_', $col )[1];
			}

			/** Strip exp_ */
			if ( false !== strpos( $col, 'exp_' ) ) {
				$col = explode( 'exp_', $col )[1];
			}

			/** if not an level, mark it as drop */
			if ( ! in_array( $col, $levels ) ) {
				$cols_to_drop[] = $column['Field'];
			}
		}

		$cols_to_drop = array_map( function ( $col ) {
			return "DROP COLUMN $col";
		}, $cols_to_drop );

		$cols_to_drop = implode( ',', $cols_to_drop );

		$query = "ALTER TABLE {$wpdb->prefix}bwf_contact_wlm_fields $cols_to_drop";
		$wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}

	public function drop_level_db_columns( $level_id ) {
		if ( empty( $level_id ) ) {
			return false;
		}

		global $wpdb;
		$exists = $wpdb->get_col( "SHOW COLUMNS FROM `{ $wpdb->prefix }bwf_contact_wlm_fields` LIKE 'reg_{$level_id}'", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $exists ) ) {
			return false;
		}

		$query = "ALTER TABLE {$wpdb->prefix}bwf_contact_wlm_fields DROP COLUMN reg_{$level_id}, DROP COLUMN exp_{$level_id}";
		$wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}

	public function get_member_as_db_row( $member_id, $contact_id ) {
		$levels = wlmapi_get_member_levels( $member_id );
		if ( ! is_array( $levels ) || empty( $levels ) ) {
			return false;
		}

		$row    = array();
		$status = array();
		foreach ( $levels as $id => $level ) {
			$status[ absint( $id ) ] = strval( $this->get_status_code_from_api( $level ) );
			$row[ 'reg_' . $id ]     = date( 'Y-m-d H:i:s', absint( $level->Timestamp ) );
			if ( ! empty( $level->ExpiryDate ) ) {
				$row[ 'exp_' . $id ] = date( 'Y-m-d H:i:s', absint( $level->ExpiryDate ) );
			}
		}

		if ( ! empty( $status ) ) {
			$row['status'] = wp_json_encode( $status );
		}

		if ( ! empty( $contact_id ) ) {
			$row['cid'] = absint( $contact_id );
		}

		return $row;
	}


	/** WLM Helper Methods */
	public function get_levels( $slim_data = true ) {
		$levels = wlmapi_get_levels();
		if ( ! is_array( $levels ) ) {
			return array();
		}

		if ( ! isset( $levels['levels'] ) || ! isset( $levels['levels']['level'] ) || ! is_array( $levels['levels']['level'] ) ) {
			return array();
		}

		$levels = $levels['levels']['level'];

		$return = array();
		foreach ( $levels as $level ) {
			$return[ absint( $level['id'] ) ] = $slim_data ? $level['name'] : $level;
			if ( ! $slim_data ) {
				$level_link = admin_url();
				$level_link = add_query_arg( array(
					'page'     => 'WishListMember',
					'wl'       => 'setup/levels',
					'level_id' => $level['id'] . '#levels_access-' . $level['id'],
				), $level_link );

				$return[ absint( $level['id'] ) ]['link'] = $level_link;
			}
		}

		return $return;
	}

	public function get_member_ids( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'offset'     => 0,
			'limit'      => 25,
			'order'      => 'ASC',
			'order_by'   => 'user_id',
			'before'     => 0,
			'count_only' => false,
		) );

		$pagination_query = '';
		if ( ! empty( $args['offset'] ) || ! empty( $args['limit'] ) ) {
			$pagination_query = 'LIMIT ' . $args['offset'] . ', ' . $args['limit'];
		}

		$where_query = '';
		if ( ! empty( $args['before'] ) ) {
			$where_query .= 'AND user_id < ' . $args['before'];
		}

		$order    = $args['order'];
		$order_by = $args['order_by'];

		if ( true === $args['count_only'] ) {
			$count = $wpdb->get_col( "SELECT count(DISTINCT user_id) from {$wpdb->prefix}wlm_userlevels WHERE 1=1 $where_query" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return ! empty( $count ) ? absint( $count[0] ) : 0;
		}

		$users = $wpdb->get_results( "SELECT DISTINCT user_id from {$wpdb->prefix}wlm_userlevels WHERE 1=1 $where_query ORDER BY $order_by $order $pagination_query", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $users ) ) {
			return array();
		}
		$users = array_column( $users, 'user_id' );
		$users = array_map( 'absint', $users );
		$users = array_filter( $users );

		return $users;
	}

	public function get_member_address( $member_id ) {
		$default_address = array(
			'company'  => '',
			'address1' => '',
			'address2' => '',
			'city'     => '',
			'state'    => '',
			'country'  => '',
			'zip'      => '',
		);

		if ( ! function_exists( 'wishlistmember_instance' ) ) {
			return $default_address;
		}

		$ins     = wishlistmember_instance();
		$address = $ins->Get_UserMeta( absint( $member_id ), 'wpm_useraddress' );

		if ( ! is_array( $address ) ) {
			$address = array();
		}

		return wp_parse_args( $address, $default_address );
	}

	public function get_level_link( $id ) {
		$level_link = admin_url();

		return add_query_arg( array(
			'page'     => 'WishListMember',
			'wl'       => 'setup/levels',
			'level_id' => $id . '#levels_access-' . $id,
		), $level_link );
	}

	/**
	 * @param array $args
	 * this function fetch the member level details
	 * (status, reg_date, exp_date, name, link)
	 *
	 * @return array
	 */
	public function get_member_level_details( $args = array() ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'offset'    => 0,
			'limit'     => 25,
			'member_id' => '',
			'order'     => 'ASC',
			'order_by'  => 'cid',
		) );

		$pagination_query = '';
		if ( ! empty( $args['offset'] ) || ! empty( $args['limit'] ) ) {
			$pagination_query = 'LIMIT ' . $args['offset'] . ', ' . $args['limit'];
		}

		$where_query = '';
		if ( ! empty( $args['member_id'] ) ) {
			$contact     = new WooFunnels_Contact( $args['member_id'], null );
			$cid         = $contact->get_id();
			$where_query .= 'AND cid = ' . $cid;
		}

		$order    = $args['order'];
		$order_by = $args['order_by'];

		$member_level_details = array();
		$levels               = $wpdb->get_results( "SELECT * from {$wpdb->prefix}bwf_contact_wlm_fields WHERE 1=1 $where_query ORDER BY $order_by $order $pagination_query", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( $levels as $level ) {
			$level_details   = array();
			$level_id_status = (array) json_decode( $level['status'] );
			foreach ( $level_id_status as $level_id => $level_status ) {
				$level_name                                     = ( new \WishListMember\Level( $level_id ) )->get_data()['name'];
				$level_link                                     = admin_url();
				$level_link                                     = add_query_arg( array(
					'page'     => 'WishListMember',
					'wl'       => 'setup/levels',
					'level_id' => $level_id . '#levels_access-' . $level_id,
				), $level_link );
				$level_details['levels'][ $level_id ]['status'] = $level_status;
				$level_details['levels'][ $level_id ]['reg']    = $level[ 'reg_' . $level_id ];
				$level_details['levels'][ $level_id ]['exp']    = $level[ 'exp_' . $level_id ];
				$level_details['levels'][ $level_id ]['name']   = $level_name;
				$level_details['levels'][ $level_id ]['link']   = $level_link;
			}
			$member_level_details[ $level['cid'] ] = $level_details;
		}

		return $member_level_details;
	}

	public function get_contact_member_details( $contact ) {
		if ( is_numeric( $contact ) ) {
			$contact = new WooFunnels_Contact( '', '', '', absint( $contact ) );
		}

		if ( ! $contact instanceof WooFunnels_Contact || 0 === absint( $contact->get_wpid() ) ) {
			return array();
		}

		return $this->get_member_details( $contact->get_wpid() );
	}

	public function get_member_details( $member_id ) {
		/** @var array $levels */
		$levels = wlmapi_get_member_levels( absint( $member_id ) );

		$member_levels = array();
		foreach ( $levels as $level_id => $level ) {
			if ( ! is_object( $level ) ) {
				continue;
			}

			$status = $this->get_status_code_from_api( $level );

			$member_levels[ $level_id ] = array(
				'id'          => $level_id,
				'name'        => $level->Name,
				'link'        => $this->get_level_link( $level_id ),
				'status'      => $status,
				'status_text' => $this->get_status_text_by_code( $status ),
				'reg'         => ! empty( $level->Timestamp ) ? date( 'Y-m-d H:i:s', absint( $level->Timestamp ) ) : '',
				'exp'         => ! empty( $level->ExpiryDate ) ? date( 'Y-m-d H:i:s', absint( $level->ExpiryDate ) ) : '',
			);
		}

		return $member_levels;
	}

	public function get_status_code_from_api( $api_level ) {
		if ( ! is_object( $api_level ) ) {
			return false;
		}

		if ( isset( $api_level->Active ) && 1 === absint( $api_level->Active ) ) {
			return self::$LEVEL_STATUS_ACTIVE;
		}

		if ( isset( $api_level->Expired ) && 1 === absint( $api_level->Expired ) ) {
			return self::$LEVEL_STATUS_EXPIRED;
		}

		if ( isset( $api_level->Cancelled ) && 1 === absint( $api_level->Cancelled ) ) {
			return self::$LEVEL_STATUS_CANCELLED;
		}

		if ( isset( $api_level->Pending ) && 1 === absint( $api_level->Pending ) ) {
			return self::$LEVEL_STATUS_PENDING;
		}

		if ( isset( $api_level->UnConfirmed ) && 1 === absint( $api_level->UnConfirmed ) ) {
			return self::$LEVEL_STATUS_UNCONFIRMED;
		}

		if ( isset( $api_level->Scheduled ) && 1 === absint( $api_level->Scheduled ) ) {
			return self::$LEVEL_STATUS_SCHEDULED;
		}

		return false;
	}

	public function get_status_text_by_code( $status_code ) {
		switch ( $status_code ) {
			case self::$LEVEL_STATUS_ACTIVE:
				return __( 'Active', 'wp-marketing-automation-pro' );
			case self::$LEVEL_STATUS_CANCELLED:
				return __( 'Cancelled', 'wp-marketing-automation-pro' );
			case self::$LEVEL_STATUS_EXPIRED:
				return __( 'Expired', 'wp-marketing-automation-pro' );
			case self::$LEVEL_STATUS_PENDING:
				return __( 'Pending', 'wp-marketing-automation-pro' );
			case self::$LEVEL_STATUS_SCHEDULED:
				return __( 'Scheduled', 'wp-marketing-automation-pro' );
			case self::$LEVEL_STATUS_UNCONFIRMED:
				return __( 'Unconfirmed', 'wp-marketing-automation-pro' );
			default:
				return '';
		}
	}

	public function is_level_scheduled_by_array( $member_level ) {
		$schedule_types = array( 'scheduled_add', 'scheduled_remove', 'scheduled_move' );
		foreach ( $schedule_types as $type ) {
			$meta_data = wlm_maybe_unserialize( $member_level[ $type ] );
			if ( $meta_data ) {
				break;
			}
		}
		if ( wlm_arrval( $meta_data, 'type' ) === 'remove' && wlm_arrval( $meta_data, 'is_current_level' ) ) {
			return false;
		} elseif ( $meta_data ) {
			return true;
		} else {
			return false;
		}
	}

	public function get_member_level( $member_id, $level_id ) {
		if ( ! function_exists( 'wishlistmember_instance' ) || empty( $member_id ) || empty( $level_id ) ) {
			return false;
		}

		$member_id = absint( $member_id );
		$level_id  = absint( $level_id );

		/** Get Level from DB */
		$level = wishlistmember_instance()->Get_All_UserLevelMetas( $member_id, $level_id );

		/** Convert the DB Object into array */
		$option_name_array = $option_value_array = array();
		foreach ( $level as $detail ) {
			$option_name_array[]  = $detail->option_name;
			$option_value_array[] = $detail->option_value;
		}

		$level = array_combine( $option_name_array, $option_value_array );

		/** Dates */
		$registration_date = isset( $level['registration_date'] ) && ! empty( $level['registration_date'] ) ? explode( '#', $level['registration_date'] )[0] : false;
		$registration_date = ! empty( $registration_date ) ? strtotime( $registration_date ) : 0;
		$expired           = isset( $level['expired'] ) && ! empty( $level['expired'] ) ? strtotime( $level['expired'] ) : 0;

		/** Statuses */
		$cancelled   = isset( $level['cancelled'] ) ? $level['cancelled'] : false;
		$pending     = isset( $level['forapproval'] ) ? $level['forapproval'] : false;
		$unconfirmed = isset( $level['unconfirmed'] ) ? $level['unconfirmed'] : false;
		$expired     = wishlistmember_instance()->level_expired( $level_id, $member_id, $registration_date );
		$scheduled   = $this->is_level_scheduled_by_array( $level );
		$active      = ! ( $cancelled | $pending | $unconfirmed | $expired | (bool) $scheduled );

		$status_code = 0;
		if ( $active ) {
			$status_code = self::$LEVEL_STATUS_ACTIVE;
		} else {
			/** Every Other Statuses Last Priority */
			if ( $unconfirmed ) {
				$status_code = self::$LEVEL_STATUS_UNCONFIRMED;
			}
			if ( $pending ) {
				$status_code = self::$LEVEL_STATUS_PENDING;
			}
			if ( $scheduled ) {
				$status_code = self::$LEVEL_STATUS_SCHEDULED;
			}

			/** Third Priority for Cancelled Status */
			if ( $cancelled ) {
				$status_code = self::$LEVEL_STATUS_CANCELLED;
			}
			/** Second Priority for Expired Status */
			if ( true === $expired ) {
				$status_code = self::$LEVEL_STATUS_EXPIRED;
			}
		}

		return array(
			'status'      => $status_code,
			'status_text' => $this->get_status_text_by_code( $status_code ),
			'reg'         => $registration_date,
			'exp'         => $expired,
		);
	}


	/** Export */
	public function get_export_custom_selections( $global_selections = array() ) {
		$levels = $this->get_levels();
		if ( empty( $levels ) ) {
			return $global_selections;
		}

		$global_selections['wishlist-member'] = array(
			'slug'       => 'wishlist-member',
			'name'       => __( 'Wishlist Member', 'wp-marketing-automations-pro' ),
			'selections' => array(
				'wlm_status' => array(
					'slug' => 'wlm_status',
					'name' => 'Membership Status',
				),
				'wlm_reg'    => array(
					'slug' => 'wlm_reg',
					'name' => 'Registration Date',
				),
				'wlm_exp'    => array(
					'slug' => 'wlm_exp',
					'name' => 'Expiration Date',
				),
			),
		);

		return $global_selections;
	}

	public function insert_wlm_export_data( $data, $contact, $fields ) {
		if ( ! is_array( $fields ) || ! $contact instanceof BWFCRM_Contact ) {
			return $data;
		}

		/** Check if wlm_ keys are present in fields */
		$wlm_fields_present = array_reduce( $fields, function ( $carry, $field ) {
			return true === $carry || false !== strpos( $field, 'wlm_' );
		}, false );

		/** Return if no wlm_ fields not found, to prevent unnecessary DB fetching */
		if ( ! $wlm_fields_present ) {
			return $data;
		}

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		/** Get Contact's Member Level Data */
		$member_levels = $this->get_contact_member_details( $contact->contact );
		if ( empty( $member_levels ) ) {
			return $data;
		}

		foreach ( $fields as $key => $field ) {
			if ( false === strpos( $field, 'wlm_' ) ) {
				continue;
			}

			$field    = explode( '_', $field );
			$level_id = $field[2];
			$field    = $field[1];

			/** Get Status text instead of status code */
			if ( 'status' === $field ) {
				$field = 'status_text';
			}

			$data[ $key ] = $member_levels[ $level_id ][ $field ];
		}

		return $data;
	}

	public function modify_export_fields_headers( $fields ) {
		if ( ! is_array( $fields ) || empty( $fields ) ) {
			return $fields;
		}

		/** Check if wlm_ keys are present in fields */
		$wlm_fields_present = array_reduce( $fields, function ( $carry, $field ) {
			return true === $carry || false !== strpos( array_keys( $field )[0], 'wlm_' );
		}, false );

		/** Return if no wlm_ fields not found, to prevent unnecessary DB fetching */
		if ( ! $wlm_fields_present ) {
			return $fields;
		}

		$levels = $this->get_levels();

		$new_fields = array();
		foreach ( $fields as $field ) {
			if ( false === strpos( array_keys( $field )[0], 'wlm_' ) ) {
				$new_fields[] = $field;
				continue;
			}

			$key = explode( '_', array_keys( $field )[0] );
			$key = $key[1];

			foreach ( $levels as $id => $name ) {
				$field_name = array_values( $field )[0];
				array_push( $new_fields, array( "wlm_{$key}_{$id}" => "{$name} {$field_name}" ) );
			}
		}

		return $new_fields;
	}


	/** WLM Events */
	public function add_level_to_user( $user_id, $new_levels, $removed_levels ) {
		if ( empty( $user_id ) || empty( $new_levels ) ) {
			return;
		}

		if ( ! is_array( $new_levels ) ) {
			$new_levels = array( $new_levels );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		$status    = array();
		if ( is_array( $member_db ) && ! empty( $member_db['status'] ) ) {
			$status = json_decode( $member_db['status'], true );
		}

		$reg = $exp = array();
		foreach ( $new_levels as $level_id ) {
			$this->maybe_add_level_db_columns( absint( $level_id ) );

			$level = $this->get_member_level( $user_id, $level_id );
			if ( empty( $level ) ) {
				continue;
			}

			/** Get Data to update or insert */
			$status[ $level_id ] = $level['status'];
			if ( ! empty( $level['reg'] ) ) {
				$reg[ $level_id ] = date( 'Y-m-d H:i:s', $level['reg'] );
			}
			if ( ! empty( $level['exp'] ) ) {
				$exp[ $level_id ] = date( 'Y-m-d H:i:s', $level['exp'] );
			}
		}

		if ( empty( $member_db ) ) {
			BWF_Model_Contact_WLM_Fields::insert( $contact->get_id(), $status, $reg, $exp );
		} else {
			BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status, $reg, $exp );
		}
	}

	public function remove_level_from_user( $user_id, $removed_levels, $new_levels ) {
		if ( empty( $user_id ) || empty( $removed_levels ) ) {
			return;
		}

		if ( ! is_array( $removed_levels ) ) {
			$removed_levels = array( $removed_levels );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		if ( empty( $member_db ) ) {
			return;
		}

		if ( ! is_array( $member_db ) || empty( $member_db['status'] ) ) {
			return;
		}

		$status = json_decode( $member_db['status'], true );
		$reg    = $exp = array();
		foreach ( $removed_levels as $level_id ) {
			if ( ! isset( $member_db["reg_{$level_id}"] ) || ! isset( $status[ $level_id ] ) ) {
				continue;
			}

			unset( $status[ $level_id ] );
			/** String nulls because of DB values */
			$reg[ $level_id ] = 'NULL';
			$exp[ $level_id ] = 'NULL';
		}

		if ( empty( $status ) ) {
			BWF_Model_Contact_WLM_Fields::delete( $contact->get_id() );
		} else {
			BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status, $reg, $exp );
		}
	}

	public function cancel_level_from_user( $user_id, $cancelled_level ) {
		if ( empty( $user_id ) || empty( $cancelled_level ) ) {
			return;
		}

		if ( ! is_array( $cancelled_level ) ) {
			$cancelled_level = array( $cancelled_level );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		if ( empty( $member_db ) ) {
			return;
		}

		if ( ! is_array( $member_db ) || empty( $member_db['status'] ) ) {
			return;
		}

		$status = json_decode( $member_db['status'], true );
		foreach ( $cancelled_level as $level_id ) {
			if ( ! isset( $member_db["reg_{$level_id}"] ) || ! isset( $status[ $level_id ] ) ) {
				continue;
			}

			$status[ $level_id ] = self::$LEVEL_STATUS_CANCELLED;
		}

		BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status );
	}

	public function uncancel_level_from_user( $user_id, $levels ) {
		if ( empty( $user_id ) || empty( $levels ) ) {
			return;
		}

		if ( ! is_array( $levels ) ) {
			$levels = array( $levels );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		if ( empty( $member_db ) ) {
			return;
		}

		if ( ! is_array( $member_db ) || empty( $member_db['status'] ) ) {
			return;
		}

		$status = json_decode( $member_db['status'], true );
		foreach ( $levels as $level_id ) {
			if ( ! isset( $member_db["reg_{$level_id}"] ) || ! isset( $status[ $level_id ] ) ) {
				continue;
			}

			$status[ $level_id ] = self::$LEVEL_STATUS_ACTIVE;
		}

		BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status );
	}

	public function expire_level_from_user( $user_id, $levels ) {
		if ( empty( $user_id ) || empty( $levels ) ) {
			return;
		}

		if ( ! is_array( $levels ) ) {
			$levels = array( $levels );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		if ( empty( $member_db ) ) {
			return;
		}

		if ( ! is_array( $member_db ) || empty( $member_db['status'] ) ) {
			return;
		}

		$status = json_decode( $member_db['status'], true );
		$exp    = array();
		foreach ( $levels as $level_id ) {
			if ( ! isset( $member_db["reg_{$level_id}"] ) || ! isset( $status[ $level_id ] ) ) {
				continue;
			}

			$level = $this->get_member_level( $user_id, $level_id );

			$status[ $level_id ] = self::$LEVEL_STATUS_EXPIRED;
			if ( ! empty( $level['exp'] ) ) {
				$exp[ $level_id ] = date( 'Y-m-d H:i:s', $level['exp'] );
			}
		}

		BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status, array(), $exp );
	}

	public function unexpire_level_from_user( $user_id, $levels ) {
		if ( empty( $user_id ) || empty( $levels ) ) {
			return;
		}

		if ( ! is_array( $levels ) ) {
			$levels = array( $levels );
		}

		$user = get_user_by( 'id', absint( $user_id ) );
		if ( ! $user instanceof WP_User ) {
			return;
		}

		$contact = new WooFunnels_Contact( $user_id, $user->user_email );
		if ( 0 === absint( $contact->get_id() ) ) {
			return;
		}

		$member_db = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id() );
		if ( empty( $member_db ) ) {
			return;
		}

		if ( ! is_array( $member_db ) || empty( $member_db['status'] ) ) {
			return;
		}

		$status = json_decode( $member_db['status'], true );
		$reg    = $exp = array();
		foreach ( $levels as $level_id ) {
			if ( ! isset( $member_db["reg_{$level_id}"] ) || ! isset( $status[ $level_id ] ) ) {
				continue;
			}

			$level = $this->get_member_level( $user_id, $level_id );

			$status[ $level_id ] = self::$LEVEL_STATUS_ACTIVE;
			if ( ! empty( $level['reg'] ) ) {
				$reg[ $level_id ] = date( 'Y-m-d H:i:s', $level['reg'] );
			}
			if ( ! empty( $level['exp'] ) ) {
				$exp[ $level_id ] = date( 'Y-m-d H:i:s', $level['exp'] );
			}
		}

		BWF_Model_Contact_WLM_Fields::update( $contact->get_id(), $status, $reg, $exp );
	}

	public function level_created( $level_id, $level_data ) {
		if ( empty( $level_id ) ) {
			return;
		}

		$this->maybe_add_level_db_columns( $level_id );
	}

	public function level_deleted( $level_id, $level_data ) {
		if ( empty( $level_id ) || 'all' === $level_id ) {
			/** TODO: Get the best use case for this condition after discussion where 'all' */
			return;
		}

		$this->drop_level_db_columns( $level_id );
	}

	public function sync_member_with_contact( $member_id ) {
		$contact = BWFCRM_Common::get_or_create_contact_from_user( absint( $member_id ) );
		if ( ! $contact instanceof BWFCRM_Contact || ! $contact->is_contact_exists() ) {
			return;
		}

		/** @var wpdb $wpdb */ global $wpdb;

		$db_row            = $this->get_member_as_db_row( $member_id, $contact->get_id() );
		$contact_member_id = BWF_Model_Contact_WLM_Fields::get_member( $contact->get_id(), true );
		$exists            = ! empty( $contact_member_id );

		if ( $exists ) {
			if ( ! empty( $db_row ) ) {
				$wpdb->update( $wpdb->prefix . 'bwf_contact_wlm_fields', $db_row, array( 'cid' => $contact->get_id() ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$wpdb->delete( $wpdb->prefix . 'bwf_contact_wlm_fields', array( 'cid' => $contact->get_id() ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		} else {
			$wpdb->insert( $wpdb->prefix . 'bwf_contact_wlm_fields', $db_row ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}
}

if ( class_exists( 'BWFCRM_Integration_Wishlist_Member' ) ) {
	BWFCRM_Core()->integrations->register( 'BWFCRM_Integration_Wishlist_Member' );
}
