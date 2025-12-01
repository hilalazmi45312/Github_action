<?php


class BWFAN_Model_Form_Feeds extends BWFAN_Model {
	static $primary_key = 'ID';

	public static function get_feeds( $args = array() ) {
		global $wpdb;
		$table = self::_table();

		$sql = "SELECT * FROM {$table}";
		if ( ! is_array( $args ) || empty( $args ) ) {
			return array(
				'feeds' => $wpdb->get_results( $sql, ARRAY_A ), //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				'total' => 0
			);
		}

		$where_sql = ' WHERE 1=1';

		if ( isset( $args['search'] ) && ! empty( $args['search'] ) ) {
			$where_sql .= $wpdb->prepare( " AND `title` LIKE %s", "%{$args['search']}%" );
		}

		/** Get by Source */
		if ( isset( $args['source'] ) && ! empty( $args['source'] ) ) {
			$where_sql .= $wpdb->prepare( " AND `source` = %s", $args['source'] );
		}

        /** Get by title Search */
        if ( isset( $args['search'] ) && ! empty( $args['search'] ) ) {
            $where_sql .= " AND `title` LIKE '%" . esc_sql( $args['search'] ) . "%'";
        }

		/** Get by Status */
		if ( isset( $args['status'] ) && ! empty( $args['status'] ) ) {
			$where_sql .= $wpdb->prepare( " AND `status` = %d", $args['status'] );
		}

		/** Set Pagination */
		$pagination_sql = '';
		$limit          = isset( $args['limit'] ) ? absint( $args['limit'] ) : 0;
		$offset         = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
		if ( ! empty( $limit ) || ! empty( $offset ) ) {
			$pagination_sql = " LIMIT $offset, $limit";
		}

		/** Order By */
		$order = ' ORDER BY `ID` DESC';

		$sql         = $sql . $where_sql . $order . $pagination_sql;
		$total_sql   = "SELECT count(*) FROM {$table}" . $where_sql;
		$grab_totals = isset( $args['grab_totals'] ) && ! empty( absint( $args['grab_totals'] ) );

		return array(
			'feeds' => $wpdb->get_results( $sql, ARRAY_A ), //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'total' => $grab_totals ? absint( $wpdb->get_var( $total_sql ) ) : 0 //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		);
	}

	/**
	 * Get first form id
	 *
	 */
	public static function get_first_form_id() {
		global $wpdb;
		$query = "SELECT MIN(`ID`) FROM " . self::_table();

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Return form count data by status
	 *
	 * @return string|null
	 */
	public static function get_forms_total_count() {
		global $wpdb;
		$response = [
			'all' => 0,
			'1'   => 0,
			'2'   => 0,
			'3'   => 0,
		];
		$all      = 0;
		$table    = self::_table();
		$query    = "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status ";

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