<?php
/**
 * bwfan_form_feeds table class
 *
 */

class BWFAN_DB_Table_Form_Feeds extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_form_feeds';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"ID",
			"title",
			"status",
			"source",
			"contacts",
			"created_at",
			"updated_at",
			"data",
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
		   `title` varchar(255) NOT NULL,
		   `status` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '1 - Draft 2 - Active 3 - InActive',
		   `source` varchar(255) NOT NULL,
		   `contacts` bigint(20) NOT NULL DEFAULT 0,
		   `created_at` datetime DEFAULT NULL,
		   `updated_at` datetime DEFAULT NULL,
		   `data` longtext,
		   PRIMARY KEY (`ID`),
		   KEY `source` (`source`(191)),
		   KEY `status` (`status`)
		) $collate;";
	}
}
