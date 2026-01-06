<?php
/**
 * bwf_contact_fields table class
 *
 */

if ( ! BWFAN_PRO_Common::is_lite_3_0() && ! class_exists( 'BWFAN_DB_Table_Contact_Fields' ) ) {
	class BWFAN_DB_Table_Contact_Fields extends BWFAN_DB_Tables_Base {
		public $table_name = 'bwf_contact_fields';

		/**
		 * Get table's columns
		 *
		 * @return string[]
		 */
		public function get_columns() {
			return [
				"ID",
				"cid",
			];
		}

		/**
		 * Get query for create table
		 *
		 * @return string
		 */
		public function get_create_table_query() {
			global $wpdb;
			$collate = $this->get_collation();

			return "CREATE TABLE {$wpdb->prefix}$this->table_name (
 		   `ID` bigint(20) unsigned NOT NULL auto_increment,
		   `cid` bigint(20) unsigned NOT NULL,
		  PRIMARY KEY (`ID`),
		  KEY `cid` (`cid`)
		) $collate;";
		}
	}
}