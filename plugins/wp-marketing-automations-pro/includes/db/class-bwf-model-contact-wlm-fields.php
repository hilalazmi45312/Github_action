<?php

/**
 * BWF_Model_Contact_WLM_Fields Class
 */
class BWF_Model_Contact_WLM_Fields {

	private static function _table() {
		/** @var wpdb $wpdb */
		global $wpdb;

		return $wpdb->prefix . 'bwf_contact_wlm_fields';
	}

	public static function insert( $contact_id, $status = array(), $reg = array(), $exp = array() ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$table = self::_table();

		$data = array(
			'status' => wp_json_encode( $status ),
			'cid'    => absint( $contact_id ),
		);

		/** Registered Dates */
		if ( ! empty( $reg ) && is_array( $reg ) ) {
			foreach ( $reg as $id => $date ) {
				$data[ "reg_$id" ] = $date;
			}
		}

		/** Expiry Dates */
		if ( ! empty( $exp ) && is_array( $exp ) ) {
			foreach ( $exp as $id => $date ) {
				$data[ "exp_$id" ] = $date;
			}
		}

		$wpdb->insert( $table, $data ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return ! empty( $wpdb->insert_id ) ? absint( $wpdb->insert_id ) : new WP_Error( 500, $wpdb->last_error );
	}

	public static function update( $contact_id, $status = array(), $reg = array(), $exp = array() ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$data = array(
			'status' => wp_json_encode( $status ),
		);

		/** Registered Dates */
		if ( ! empty( $reg ) && is_array( $reg ) ) {
			foreach ( $reg as $id => $date ) {
				$data[ "reg_$id" ] = $date;
			}
		}

		/** Expiry Dates */
		if ( ! empty( $exp ) && is_array( $exp ) ) {
			foreach ( $exp as $id => $date ) {
				$data[ "exp_$id" ] = $date;
			}
		}

		if ( false === $wpdb->update( self::_table(), $data, array( 'cid' => $contact_id ) ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return new WP_Error( 500, $wpdb->last_error );
		}

		return true;
	}

	public static function delete( $contact_id ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		if ( false === $wpdb->delete( $wpdb->prefix . 'bwf_contact_wlm_fields', array( 'cid' => $contact_id ) ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return new WP_Error( 500, $wpdb->last_error );
		}

		return true;
	}

	public static function truncate() {
		/** @var wpdb $wpdb */
		global $wpdb;

		$table = self::_table();

		$wpdb->query( "TRUNCATE TABLE $table" );
	}

	public static function get_member( $contact_id, $lookup = false ) {
		/** @var wpdb $wpdb */
		global $wpdb;

		$columns = true === $lookup ? 'id' : '*';
		$table   = self::_table();
		$sql     = "SELECT $columns FROM $table WHERE cid = {$contact_id}";

		if ( ! $lookup ) {
			return $wpdb->get_row( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return absint( $wpdb->get_col( $sql ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function check_if_level_exists( $level_id ) {
		if ( empty( $level_id ) ) {
			return false;
		}

		/** @var wpdb $wpdb */
		global $wpdb;

		$table = self::_table();

		$result = $wpdb->get_col( "SHOW COLUMNS FROM `{$table}` LIKE `reg_{$level_id}`" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return ! empty( $result );
	}

	public static function add_level_db_columns( $level_id ) {
		if ( empty( $level_id ) ) {
			return false;
		}

		/** @var wpdb $wpdb */
		global $wpdb;

		$table = self::_table();

		$wpdb->query( "ALTER TABLE {$table} ADD COLUMN reg_{$level_id} datetime default NULL, ADD COLUMN exp_{$level_id} datetime default NULL" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
	}
}
