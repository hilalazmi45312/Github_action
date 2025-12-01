<?php

if ( ! class_exists( 'BWFAN_Model_Stripe_Offer' ) ) {
	class BWFAN_Model_Stripe_Offer extends BWFAN_Model {
		public static $primary_key = 'ID';

		/**
		 * Get stripe offer by cid and funnel id
		 *
		 * @param $cid
		 * @param $fid
		 *
		 * @return string|null
		 */
		public static function get_stripe_offer_id( $cid, $fid ) {
			global $wpdb;
			$table = self::_table();
			$query = "SELECT ID FROM {$table} WHERE `clicked`= %d AND `cid`=%d AND `fid`=%d ORDER BY `ID` DESC LIMIT 1";

			return $wpdb->get_var( $wpdb->prepare( $query, 1, $cid, $fid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		protected static function _table() {
			global $wpdb;

			return $wpdb->prefix . 'bwfan_stripe_offer';
		}

		public static function get_analytics( $step_id ) {
			global $wpdb;
			$table = self::_table();
			$query = "SELECT count(`ID`) AS `sent`, SUM(`clicked`) AS `click_count`, (SUM(`clicked`)/COUNT(`ID`)) * 100 as `click_rate`, COUNT(distinct NULLIF(`order_id`, '')) AS `conversions`, SUM(`order_total`) AS `revenue`, COUNT(DISTINCT `cid`) as `contacts_count`  FROM {$table} WHERE `sid`=%d";

			return $wpdb->get_row( $wpdb->prepare( $query, $step_id ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
}