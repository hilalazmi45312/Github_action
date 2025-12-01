<?php

class BWFAN_Model_Import_Export extends BWFAN_Model {
	/** Status 1: In-Progress, 2: Failed, 3: Success */

	public static function get_export_import( $type, $limit, $offset ) {
		global $wpdb;
		$table = "{$wpdb->prefix}bwfan_import_export";

		$query       = " SELECT * FROM $table WHERE type = $type ORDER BY ID DESC LIMIT $offset,$limit";
		$count_query = " SELECT COUNT(id) FROM $table WHERE type = $type ";
		$result      = [
			'exports'     => self::get_results( $query ),
			'total_count' => $wpdb->get_var( $count_query ) //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		];

		return $result;
	}

	/**
	 * Get first export id
	 */
	public static function get_first_export_id() {
		global $wpdb;
		$table = "{$wpdb->prefix}bwfan_import_export";
		$query = " SELECT MIN(`id`) FROM $table WHERE type = 2 ";

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}