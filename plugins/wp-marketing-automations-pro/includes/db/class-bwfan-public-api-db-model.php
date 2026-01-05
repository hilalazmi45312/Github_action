<?php

/**
 * Class BWFAN_Rest_API_DB_Model
 */
class BWFAN_Rest_API_DB_Model {
	private static function _table() {
		/** @var wpdb $wpdb */ global $wpdb;

		return $wpdb->prefix . 'bwfan_api_keys';
	}

	/**
	 * @param $data
	 *
	 * @return int|WP_Error
	 */
	public static function insert( $data ) {
		global $wpdb;

		if ( empty( $data ) ) {
			new WP_Error( 500, __( 'Data to insert is not provided', 'wp-marketing-automations-pro' ) );
		}

		$wpdb->insert( self::_table(), $data ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return ! empty( $wpdb->insert_id ) ? absint( $wpdb->insert_id ) : new WP_Error( 500, $wpdb->last_error );
	}

	/**
	 * @param array $columns
	 * @param array $where
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function fetch( $columns = array(), $where = array(), $limit = 0, $offset = 0, $search = '', $grab_count = false ) {
		global $wpdb;
		$table       = self::_table();

		if ( ! empty( $columns ) ) {
			$column = implode( ',', array_map( function ( $col ) {
				return 'p.' . $col;
			}, $columns ) );
		} else {
			$column = 'p.*, u.display_name as user_login,u.user_email';
		}

		$sql       = "Select $column FROM $table as p JOIN {$wpdb->users} as u ON p.uid = u.ID";
		$total_sql = "Select count(p.id) FROM $table as p JOIN {$wpdb->users} as u ON p.uid = u.ID";

		$where_sql = " WHERE 1=1";
		// mapping field and value present in $where and imploding it with &
		if ( ! empty( $where ) ) {
			$data      = array_map( function ( $k, $v ) {
				return 'p.' . $k . '=' . $v;
			}, array_keys( $where ), array_values( $where ) );
			$where_sql .= " AND " . implode( ' AND ', $data );
		}

		if ( ! empty( $search ) ) {
			$where_sql .= " AND (u.user_login LIKE '%$search%' || u.user_email LIKE '%$search%')";
		}

		if ( ! empty( $where_sql ) ) {
			$sql       .= $where_sql . ' ORDER BY `id` DESC';
			$total_sql .= $where_sql;
		}
		//adding limit and offset if limit provided
		if ( ! empty( $limit ) ) {
			$sql .= " LIMIT $offset,$limit";
		}

		if ( $grab_count ) {
			return [
				'total' => $wpdb->get_var( $wpdb->prepare( $total_sql ) ), //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				'data'  => $wpdb->get_results( $wpdb->prepare( $sql ), ARRAY_A ) //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			];
		}

		return $wpdb->get_results( $wpdb->prepare( $sql ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param $data
	 *
	 * @return bool|WP_Error
	 */
	public static function delete( $data ) {
		/** @var wpdb $wpdb */ global $wpdb;

		if ( empty( $data ) ) {
			new WP_Error( 500, __( 'Data to be deleted not provided', 'wp-marketing-automations-pro' ) );
		}

		if ( false === $wpdb->delete( self::_table(), $data ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return new WP_Error( 500, $wpdb->last_error );
		}

		return true;
	}

	/**
	 * @param $data
	 * @param $where
	 *
	 * @return bool|WP_Error
	 */
	public static function update( $data, $where ) {
		global $wpdb;

		if ( empty( $data ) || empty( $where ) ) {
			new WP_Error( 500, __( 'Data to be updated not provided', 'wp-marketing-automations-pro' ) );
		}
		if ( false === $wpdb->update( self::_table(), $data, $where ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return new WP_Error( 500, $wpdb->last_error );
		}

		return true;
	}

	/**
	 * @param $api_key
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_user_details_by_api( $api_key ) {
		global $wpdb;

		if ( empty( $api_key ) ) {
			new WP_Error( 500, __( 'API key not provided', 'wp-marketing-automations-pro' ) );
		}

		$table = self::_table();
		$sql   = "SELECT * FROM $table WHERE api_key = %s";

		return $wpdb->get_row( $wpdb->prepare( $sql, $api_key ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param $api_key_id
	 * update last access time of the api key
	 *
	 * @return bool
	 */
	public static function update_api_key_last_access( $api_key_id ) {
		global $wpdb;

		if ( empty( $api_key_id ) ) {
			new WP_Error( 500, __( 'API key ID not provided', 'wp-marketing-automations-pro' ) );
		}

		$wpdb->update( self::_table(), array( 'last_access' => current_time( 'mysql' ) ), array( 'id' => $api_key_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}
}