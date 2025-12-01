<?php
/**
 * bwfan_terms table class
 *
 */

class BWFAN_DB_Table_API_Keys extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_api_keys';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"id",
			"uid",
			"permission",
			"api_key",
			"description",
			"status",
			"created_by",
			"created_at",
			"last_access",
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
			`id` bigint(20) unsigned NOT NULL auto_increment,
			`uid` bigint(20) unsigned NOT NULL COMMENT 'wp user_id',
			`permission` tinyint (1) unsigned COMMENT '1 read | 2 write | 3 read/write',
			`api_key` varchar(60) NOT NULL,
			`description` varchar(200),
			`status` tinyint (1) unsigned default 1 COMMENT '1 active | 2 revoked',
			`created_by` bigint(20) unsigned,
			`created_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`last_access` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (`id`),			
			KEY cid (`uid`),
			KEY `api_key` (`api_key`)
		) $collate;";
	}
}
