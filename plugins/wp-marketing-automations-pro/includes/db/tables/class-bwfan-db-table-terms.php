<?php
/**
 * bwfan_terms table class
 *
 */

if ( ! BWFAN_PRO_Common::is_lite_3_0() && ! class_exists( 'BWFAN_DB_Table_Terms' ) ) {
	class BWFAN_DB_Table_Terms extends BWFAN_DB_Tables_Base {
		public $table_name = 'bwfan_terms';

		/**
		 * Get table's columns
		 *
		 * @return string[]
		 */
		public function get_columns() {
			return [
				"ID",
				"name",
				"type",
				"data",
				"created_at",
				"updated_at",
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
 		  `name` varchar(255) NOT NULL,
 		  `type` tinyint(2) unsigned NOT NULL,
 		  `data` longtext, 
 		  `created_at` datetime,
 		  `updated_at` datetime,
		  PRIMARY KEY (`ID`),
		  KEY `type` (`type`)
		) $collate;";
		}
	}
}