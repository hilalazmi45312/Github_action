<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * BWFAN_Fix_Collation class
 */
if ( ! class_exists( 'BWFAN_Fix_Collation' ) ) {
	class BWFAN_Fix_Collation {

		/**
		 * Fix table collation
		 *
		 * @return bool
		 */
		public static function maybe_fix_collation_issue() {
			global $wpdb;

			$table1 = $wpdb->prefix . 'bwfan_message_unsubscribe';
			$table2 = $wpdb->prefix . 'bwf_contact';
			$table3 = $wpdb->prefix . 'bwf_contact_meta';
			$table4 = $wpdb->prefix . 'bwf_wc_customers';

			$collation1 = '';
			$collation2 = '';

			$charset = ( $wpdb->has_cap( 'collation' ) ) ? $wpdb->charset : '';
			if ( empty( $charset ) ) {
				return false;
			}

			$table1_columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM {$table1}", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( is_array( $table1_columns ) && count( $table1_columns ) > 0 ) {
				$table1_columns = array_filter( $table1_columns, function ( $i ) {
					return ( 'recipient' === $i['Field'] );
				} );
			}

			if ( is_array( $table1_columns ) && count( $table1_columns ) > 0 ) {
				$collation1 = array_column( $table1_columns, 'Collation' );
			}
			$collation1 = is_array( $collation1 ) ? $collation1[0] : '';
			BWFAN_Common::log_test_data( "{$table1} table 'recipient' column collation is {$collation1}", 'collation-issue', true );

			$table2_columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM {$table2}", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( is_array( $table2_columns ) && count( $table2_columns ) > 0 ) {
				$table2_columns = array_filter( $table2_columns, function ( $i ) {
					return ( 'email' === $i['Field'] );
				} );
			}
			if ( is_array( $table2_columns ) && count( $table2_columns ) > 0 ) {
				$collation2 = array_column( $table2_columns, 'Collation' );
			}
			$collation2 = is_array( $collation2 ) ? $collation2[0] : '';
			BWFAN_Common::log_test_data( "{$table2} table 'email' column collation is {$collation2}", 'collation-issue', true );

			if ( $collation1 === $collation2 ) {
				return true;
			}

			$query = "ALTER TABLE {$table2} CHARACTER SET {$charset} COLLATE {$collation1};";
			$res   = $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BWFAN_Common::log_test_data( "Contact table modify query result: {$res}", 'collation-issue', true );

			$query = "ALTER TABLE {$table2} MODIFY email VARCHAR(100) CHARACTER SET {$charset} COLLATE {$collation1};";
			$res   = $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BWFAN_Common::log_test_data( "Email column modify query result: {$res}", 'collation-issue', true );

			$query = "ALTER TABLE {$table2} MODIFY contact_no VARCHAR(20) CHARACTER SET {$charset} COLLATE {$collation1};";
			$res   = $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BWFAN_Common::log_test_data( "Phone column modify query result: {$res}", 'collation-issue', true );

			$query = "ALTER TABLE {$table3} CHARACTER SET {$charset} COLLATE {$collation1};";
			$res   = $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BWFAN_Common::log_test_data( "Contact Meta table modify query result: {$res}", 'collation-issue', true );

			$query = "ALTER TABLE {$table4} CHARACTER SET {$charset} COLLATE {$collation1};";
			$res   = $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			BWFAN_Common::log_test_data( "Customer table modify query result: {$res}", 'collation-issue', true );

			return true;
		}

	}
}
