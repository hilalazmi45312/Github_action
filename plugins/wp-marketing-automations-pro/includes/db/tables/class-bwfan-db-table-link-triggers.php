<?php
/**
 * bwfan_link_triggers table class
 *
 */

class BWFAN_DB_Table_Link_Triggers extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_link_triggers';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"ID",
			"hash",
			"title",
			"status",
			"total_clicked",
			"data",
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
			`hash` varchar(40) NOT NULL,
			`title` varchar(255) default NULL,
			`status` tinyint(1) unsigned not null default 1 COMMENT '0 - Draft 1 - Inactive 2 - Active',
			`total_clicked` bigint(10) unsigned default 0,
			`data` longtext,
			`created_by` bigint(10) unsigned default 0,
			`created_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (`ID`),
			KEY `hash` (`hash`),
			KEY `title` (`title`),
			KEY `status` (`status`)
		) $collate;";
	}
}
