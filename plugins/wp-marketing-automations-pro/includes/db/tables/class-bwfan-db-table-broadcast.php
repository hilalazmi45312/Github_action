<?php
/**
 * bwfan_broadcast table class
 *
 */

class BWFAN_DB_Table_Broadcast extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_broadcast';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"id",
			"title",
			"description",
			"type",
			"status",
			"count",
			"processed",
			"created_by",
			"offset",
			"modified_by",
			"execution_time",
			"created_at",
			"last_modified",
			"data",
			"parent",
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
 		  `id` bigint(20) UNSIGNED NOT NULL auto_increment,
		  `title` varchar(255) NOT NULL,
		  `description` longtext,
		  `type` tinyint(1) unsigned NOT NULL DEFAULT 1,
		  `status` tinyint(1) unsigned NOT NULL DEFAULT 1,
		  `count` bigint(10) unsigned NOT NULL,
		  `processed` bigint(10) unsigned NOT NULL,
		  `created_by` bigint(20) unsigned NOT NULL,
		  `offset` bigint(10) unsigned NOT NULL,
		  `modified_by` bigint(20) NOT NULL,
		  `execution_time` datetime DEFAULT NULL,
		  `created_at` datetime DEFAULT NULL,
		  `last_modified` datetime DEFAULT NULL,
		  `data` longtext,
		  `parent` bigint(20) unsigned NOT NULL DEFAULT 0,
		  PRIMARY KEY (`id`),
		  KEY `type` (`type`),
		  KEY `status` (`status`)
		  ) $collate;";
	}
}
