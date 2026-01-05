<?php

/**
 * Bulk action modal class
 */
class BWFAN_Model_Bulk_Action extends BWFAN_Model {
	static $primary_key = 'ID';

	protected static function _table() {
		global $wpdb;

		return $wpdb->prefix . 'bwfan_bulk_action';
	}

	/**
	 * Insert new bulk action to db
	 *
	 * @param $data
	 */
	public static function bwfan_create_new_bulk_action( $data ) {
		if ( empty( $data ) ) {
			return;
		}
		self::insert( $data );
		$insert_id = absint( self::insert_id() );

		return $insert_id;
	}

	/**
	 * Update bulk action data by id
	 *
	 * @param $id
	 * @param $data
	 */
	public static function update_bulk_action_data( $id, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		return ! ! self::update( $data, array(
			'id' => absint( $id ),
		) );
	}

	/**
	 * Check if action exists with field and value
	 *
	 * @param $field
	 * @param $value
	 *
	 * @return bool
	 */
	public static function check_bulk_action_exists_with( $field, $value ) {
		global $wpdb;
		$exists = false;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		$query  = 'SELECT ID FROM ' . self::_table();
		$query  .= $wpdb->prepare( " WHERE {$field} = %s ", $value, 1 );
		$result = $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! empty( $result ) ) {
			$exists = true;
		}

		return $exists;
	}

	/**
	 * Returns bulk action list
	 *
	 * @param string $search
	 * @param string $status
	 * @param int $limit
	 * @param int $offset
	 * @param false $get_total
	 * @param array $ids
	 *
	 * @return array
	 */
	public static function get_bulk_actions( $search = '', $status = '', $limit = 0, $offset = 0, $get_total = false, $ids = [] ) {

		global $wpdb;

		/**
		 * Default response
		 */
		$response = [
			'list'  => [],
			'total' => 0
		];

		$table = self::_table();

		$sql = "SELECT * FROM {$table}  ";

		$where_sql = ' WHERE 1=1';

		/**
		 * If search needed
		 */
		if ( ! empty( $search ) ) {
			$where_sql .= " AND title LIKE '%$search%'";
		}

		/** Get by Status */
		if ( ! empty( $status ) ) {
			$where_sql .= " AND `status` = {$status}";
		}

		if ( ! empty( $ids ) ) {
			$where_sql .= " AND ID IN(" . implode( ',', $ids ) . ")";
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

		$response['list'] = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $response['list'] ) ) {
			$response['list'] = self::format_action_data( $response['list'] );
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
	 * Format bulk action data
	 *
	 * @param array $bulk_action
	 * @param bool $format
	 *
	 * @return array
	 */
	public static function format_action_data( $bulk_actions, $format = true ) {

		if ( ! $format ) {
			return $bulk_actions;
		}

		return array_map( function ( $bulk_action ) {
			$meta = [];
			if ( ! empty( $bulk_action['meta'] ) ) {
				$meta = json_decode( $bulk_action['meta'], true );
				unset( $bulk_action['meta'] );
			}
			if ( ! empty( $bulk_action['actions'] ) ) {
				$bulk_action['actions'] = json_decode( $bulk_action['actions'], true );
			}

			return array_merge( $bulk_action, $meta );
		}, $bulk_actions );
	}

	/**
	 * Clone bulk action
	 *
	 * @param $id
	 *
	 * @return array
	 */
	public static function clone_bulk_action( $id ) {
		$status           = 404;
		$message          = __( 'Unable to find bulk action with the given id.', 'wp-marketing-automations-pro' );
		$bulk_action_data = self::get_specific_rows( 'ID', $id );

		if ( ! empty( $bulk_action_data ) ) {
			$create_time      = current_time( 'mysql', 1 );
			$bulk_action_data = $bulk_action_data[0];
			unset( $bulk_action_data['ID'] );
			unset( $bulk_action_data['ID'] );
			$bulk_action_data['status'] = 0;
			$bulk_action_data['meta']   = json_decode( $bulk_action_data['meta'], true );
			if ( isset( $bulk_action_data['meta'] ) && ! empty( $bulk_action_data['meta'] ) && isset( $bulk_action_data['meta']['log_file'] ) ) {
				unset( $bulk_action_data['meta']['log_file'] );
			}
			$bulk_action_data['meta'] = json_encode( $bulk_action_data['meta'] );
			$bulk_action_data['title']      = $bulk_action_data['title'] . ' ( ' . __( 'Copy', 'wp-marketing-automations-pro' ) . ' )';
			$bulk_action_data['created_at'] = $create_time;
			$bulk_action_data['updated_at'] = $create_time;
			$bulk_action_data['offset']     = 0;
			$bulk_action_data['processed']  = 0;

			self::insert( $bulk_action_data );
			$new_bulk_action_id = self::insert_id();

			if ( $new_bulk_action_id ) {
				$status  = 200;
				$message = __( 'Bulk Action cloned.', 'wp-marketing-automations-pro' );
			}
		}

		return array(
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Delete multiple bulk actions
	 *
	 * @param $ids
	 *
	 * @return bool
	 */
	public static function delete_multiple_bulk_actions( $ids ) {
		global $wpdb;
		$bulk_action_table = self::_table();
		$ids               = implode( ',', array_map( 'absint', $ids ) );

		return $wpdb->query( "DELETE FROM $bulk_action_table WHERE id IN( $ids )" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Return bulk action id
	 */
	public static function get_first_bulk_action_id() {
		global $wpdb;
		$query = 'SELECT MIN(ID) from ' . self::_table();

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get single bulk action data
	 *
	 * @param int $id
	 * @param bool $format
	 *
	 * @return array|mixed
	 */
	public static function bwfan_get_bulk_action( $id, $format = true ) {
		$bulk_action_table = self::_table();

		$query = "SELECT * FROM $bulk_action_table WHERE 1=1 ";

		if ( absint( $id ) > 0 ) {
			$query .= " AND ID=$id ";
		}

		$result = self::get_results( $query );

		return is_array( $result ) && ! empty( $result ) ? self::format_action_data( $result, $format )[0] : array();
	}

	/**
	 * Returns link trigegrs count by status
	 *
	 * @param $only_total get only total
	 *
	 * @return string|null
	 */
	public static function get_bulk_actions_total_count( $only_total = false ) {
		global $wpdb;
		$response          = [
			'all' => 0,
			'0'   => 0,
			'1'   => 0,
			'2'   => 0,
			'3'   => 0,
		];
		$all               = 0;
		$bulk_action_table = self::_table();
		$query             = "SELECT status, COUNT(*) as count FROM {$bulk_action_table} GROUP BY status ";
		$result            = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $result ) ) {
			return $response;
		}
		foreach ( $result as $row ) {
			$response[ $row['status'] ] = intval( $row['count'] );
			$all                        += intval( $row['count'] );
		}

		if ( $only_total ) {
			return $all;
		}

		$response['all'] = $all;

		return $response;
	}
}