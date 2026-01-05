<?php
/**
 * bwfan_bulk_action table class
 *
 */

class BWFAN_DB_Table_Bulk_Action extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_bulk_action';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"ID",
			"offset",
			"processed",
			"count",
			"title",
			"status",
			"actions",
			"meta",
			"created_by",
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
			`offset` bigint(20) unsigned NOT NULL,
			`processed` bigint(20) unsigned NOT NULL,
			`count` bigint(20) unsigned NOT NULL,
			`title` varchar(255) default NULL,
			`status` tinyint(1) unsigned not null default 0 COMMENT '0 - Draft 1 - Ongoing 2 - Completed 3 - Paused',
			`actions` longtext NOT NULL,
			`meta` longtext NOT NULL,
			`created_by` bigint(10) unsigned default 0,
			`created_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (`ID`),
			KEY `title` (`title`),
			KEY `status` (`status`)
	    ) $collate;";
	}
}
