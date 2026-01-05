<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'BWFAN_Allowed_Automations' ) ) {
	class BWFAN_Allowed_Automations {

		private static $ins = null;

		public static function get_instance() {
			if ( null === self::$ins ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public function get_slug() {
			return 'allowed_automations';
		}

		/**
		 * Get options
		 *
		 * @param $search
		 *
		 * @return array
		 */
		public function get_options( $search ) {
			$events = BWFAN_Core()->sources->get_events_to_add_contact_manually();
			if ( empty( $events ) ) {
				return [];
			}

			$placeholder = array_fill( 0, count( $events ), '%s' );
			$placeholder = implode( ", ", $placeholder );

			global $wpdb;
			$query = $wpdb->prepare( "SELECT `ID` as `key`,`title` as `value` FROM `{$wpdb->prefix}bwfan_automations` WHERE `event` IN ($placeholder)", $events );

			if ( ! empty( $search ) ) {
				$query .= $wpdb->prepare( " AND `title` LIKE %s", '%' . $search . '%' );
			}

			$query .= " AND `status` = 1 AND `v` = 2 LIMIT 0, 25";

			$automations           = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
			$formatted_automations = [];
			foreach ( $automations as $automation ) {
				$formatted_automations[ $automation['key'] ] = $automation['value'] . ' (#' . $automation['key'] . ')';
			}

			return $formatted_automations;
		}
	}

	if ( class_exists( 'BWFAN_Load_Custom_Search' ) ) {
		BWFAN_Load_Custom_Search::register( 'BWFAN_Allowed_Automations' );
	}
}
