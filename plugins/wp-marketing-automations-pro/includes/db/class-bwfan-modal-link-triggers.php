<?php

/**
 * Link Triggers modal class
 */
class BWFAN_Model_Link_Triggers extends BWFAN_Model {
	public static $primary_key = 'ID';
	public static $cached_query = [];

	protected static function _table() {
		global $wpdb;

		return $wpdb->prefix . 'bwfan_link_triggers';
	}

	/**
	 * Insert new link trigger to db
	 *
	 * @param $data
	 */
	public static function bwfan_create_new_link_trigger( $data ) {
		if ( empty( $data ) ) {
			return;
		}
		self::insert( $data );
		$link_id = absint( self::insert_id() );

		$hash = md5( $link_id . time() );

		self::update_link_trigger_data( $link_id, [
			'hash' => $hash
		] );

		return $link_id;
	}

	/**
	 * Update link trigger data by id
	 *
	 * @param $id
	 * @param $data
	 */
	public static function update_link_trigger_data( $id, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		return ! ! self::update( $data, array(
			'id' => absint( $id ),
		) );
	}

	/**
	 * Check if link already exists with field and value
	 *
	 * @param $field
	 * @param $value
	 *
	 * @return bool
	 */
	public static function check_link_exists_with( $field, $value ) {
		global $wpdb;
		$exists = false;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		$query  = 'SELECT `ID` FROM ' . self::_table();
		$query  .= $wpdb->prepare( " WHERE {$field} = %s LIMIT 0,1", $value, 1 );
		$result = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $result ) ) {
			$exists = true;
		}

		return $exists;
	}

	/**
	 * Returns link triggers data
	 *
	 * @param string $search
	 * @param int $limit
	 * @param int $offset
	 * @param string $status
	 * @param false $get_total
	 *
	 * @return array
	 */
	public static function get_link_triggers( $search = '', $status = '', $limit = 0, $offset = 0, $get_total = false, $ids = [] ) {

		global $wpdb;

		/**
		 * Default response
		 */
		$response = [
			'links' => [],
			'total' => 0
		];

		$table = self::_table();

		$sql = "SELECT * FROM {$table}  ";

		$where_sql = ' WHERE 1=1';

		/**
		 * If search needed
		 */
		if ( ! empty( $search ) ) {
			$where_sql .= " AND `title` LIKE '%" . esc_sql( $search ) . "%'";
		}

		/** Get by Status */
		if ( ! empty( $status ) ) {
			$where_sql .= " AND `status` = {$status}";
		}

		if ( ! empty( $ids ) ) {
			$where_sql .= " AND `ID` IN(" . implode( ',', $ids ) . ")";
		}

		/** Set Pagination */
		$pagination_sql = '';
		$limit          = ! empty( $limit ) ? absint( $limit ) : 0;
		$offset         = ! empty( $offset ) ? absint( $offset ) : 0;
		if ( ! empty( $limit ) || ! empty( $offset ) ) {
			$pagination_sql = " LIMIT $offset, $limit";
		}

		/** Order By */
		$order = ' ORDER BY `ID` DESC';

		$sql = $sql . $where_sql . $order . $pagination_sql;

		$response['links'] = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $response['links'] ) ) {
			$response['links'] = self::format_link_data( $response['links'] );
		}

		/**
		 * Get total
		 */
		if ( $get_total ) {
			$total_sql         = "SELECT count(*) FROM {$table} " . $where_sql;
			$response['total'] = absint( $wpdb->get_var( $total_sql ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return $response;
	}

	/**
	 * Format link data
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public static function format_link_data( $links ) {
		return array_map( function ( $link ) {
			if ( ! empty( $link['data'] ) ) {
				$link['data'] = json_decode( $link['data'], true );
			}

			return $link;
		}, $links );
	}

	/**
	 * Clone link trigger
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function clone_link_trigger( $id ) {
		$status    = 404;
		$message   = __( 'Unable to find link with the given id.', 'wp-marketing-automations-pro' );
		$link_data = self::get_specific_rows( 'id', $id );

		if ( ! empty( $link_data ) ) {
			$create_time = current_time( 'mysql', 1 );
			$link_data   = $link_data[0];
			unset( $link_data['ID'] );
			unset( $link_data['hash'] );
			$link_data['title'] = $link_data['title'] . ' ( Copy )';

			$link_data['created_at'] = $create_time;
			$link_data['updated_at'] = $create_time;

			self::insert( $link_data );
			$new_link_id = self::insert_id();

			if ( $new_link_id ) {
				/** Add hash */
				$hash = md5( $new_link_id . time() );
				self::update_link_trigger_data( $new_link_id, [
					'hash' => $hash
				] );

				$status  = 200;
				$message = __( 'Link Trigger cloned successfully.', 'wp-marketing-automations-pro' );
			}
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Delete multiple links
	 *
	 * @param $link_ids
	 *
	 * @return bool
	 */
	public static function delete_multiple_links( $link_ids ) {
		global $wpdb;
		$link_table = self::_table();
		$ids        = implode( ',', array_map( 'absint', $link_ids ) );

		return $wpdb->query( "DELETE FROM $link_table WHERE `ID` IN( $ids )" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Return link id
	 */
	public static function get_first_link_id() {
		global $wpdb;
		$query = 'SELECT MIN(`ID`) FROM ' . self::_table();

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get single Link data
	 *
	 * @param $link_id
	 *
	 * @return array|mixed
	 */
	public static function bwfan_get_link_trigger( $link_id, $hash = '' ) {
		$link_table = self::_table();

		$query = "SELECT * FROM $link_table WHERE 1=1 ";
		$args  = [];
		if ( intval( $link_id ) > 0 ) {
			$query  .= " AND `ID` = %d";
			$args[] = $link_id;
		}

		if ( ! empty( $hash ) ) {
			$query  .= " AND `hash` = %s";
			$args[] = $hash;
		}
		global $wpdb;
		$query = $wpdb->prepare( $query, $args );

		$key = md5( $query );
		if ( isset( self::$cached_query[ $key ] ) ) {
			$result = self::$cached_query[ $key ];
		} else {
			$result = self::get_results( $query );

			self::$cached_query[ $key ] = $result;
		}

		return is_array( $result ) && ! empty( $result ) ? self::format_link_data( $result )[0] : array();
	}

	/**
	 * Returns link triggers count by status
	 *
	 * @param int $status
	 *
	 * @return string|null
	 */
	public static function get_link_triggers_total_count() {
		global $wpdb;
		$response   = [
			'all' => 0,
			'0'   => 0,
			'1'   => 0,
			'2'   => 0,
		];
		$all        = 0;
		$link_table = self::_table();
		$query      = "SELECT status, COUNT(*) as count FROM {$link_table} GROUP BY status ";

		$result = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $result ) ) {
			return $response;
		}
		foreach ( $result as $row ) {
			$response[ $row['status'] ] = intval( $row['count'] );
			$all                        += intval( $row['count'] );
		}
		$response['all'] = $all;

		return $response;
	}
}