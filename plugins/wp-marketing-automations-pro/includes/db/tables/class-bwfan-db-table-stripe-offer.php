<?php

class BWFAN_DB_Table_Stripe_Offer extends BWFAN_DB_Tables_Base {
	public $table_name = 'bwfan_stripe_offer';

	/**
	 * Get table's columns
	 *
	 * @return string[]
	 */
	public function get_columns() {
		return [
			"ID",
			"cid",
			"hash_code",
			"oid",
			"fid",
			"created_at",
			"expiry_days",
			"updated_at",
			"sid",
			"clicked",
			"order_id",
			"order_total",
			"order_date",
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
 		    `ID` INT(10) UNSIGNED NOT NULL auto_increment,
			`cid` bigint(10) unsigned NOT NULL,
			`hash_code` varchar(60) NOT NULL,
			`oid` bigint(10) unsigned NOT NULL COMMENT 'Offer id',
			`fid` bigint(10) unsigned NOT NULL COMMENT 'Funnel id',
			`created_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`expiry_days` smallint(4) unsigned NOT NULL,
			`updated_at` datetime NOT NULL default '0000-00-00 00:00:00',
			`sid` bigint(10) unsigned NOT NULL COMMENT 'Step id',
			`clicked` tinyint(1) unsigned not null default 0,
			`order_id` bigint(10) unsigned default NULL,
			`order_total` DECIMAL(10,2) default NULL,
			`order_date` datetime NOT NULL default '0000-00-00 00:00:00',
			PRIMARY KEY (`ID`),
			KEY `hash_code` (`hash_code`),
			KEY `sid` (`sid`),
			KEY `order_id` (`order_id`),
			KEY `oid` (`oid`),
			KEY `fid` (`fid`),
			KEY `created_at` (`created_at`)
	    ) $collate;";
	}
}
