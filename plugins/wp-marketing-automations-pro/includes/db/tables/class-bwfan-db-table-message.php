<?php
/**
 * bwfan_message table class
 *
 */

if ( ! BWFAN_PRO_Common::is_lite_3_0() && ! class_exists( 'BWFAN_DB_Table_Message' ) ) {
	class BWFAN_DB_Table_Message extends BWFAN_DB_Tables_Base {
		public $table_name = 'bwfan_message';

		/**
		 * Get table's columns
		 *
		 * @return string[]
		 */
		public function get_columns() {
			return [
				"ID",
				"track_id",
				"sub",
				"body",
				"date",
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
		  `track_id` bigint(20) unsigned NOT NULL,
		  `sub` varchar(255) NOT NULL,
		  `body` longtext,
		  `date` datetime DEFAULT NULL,
		  PRIMARY KEY (`ID`),
		  KEY `track_id` (`track_id`)
		) $collate;";
		}
	}
}