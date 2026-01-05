<?php


class BWFCRM_Model_Automations extends BWFAN_Model {
	static $primary_key = 'ID';

	protected static function _table() {
		global $wpdb;

		return $wpdb->prefix . 'bwfan_contact_automations';
	}

	public static function get_tasks_for_contact( $contact_id ) {
		global $wpdb;
		$scheduled_tasks           = array();
		$get_contact_automation_id = $wpdb->get_col( "SELECT DISTINCT automation_id from {$wpdb->prefix}bwfan_contact_automations where contact_id='" . $contact_id . "'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $get_contact_automation_id ) ) {
			return $scheduled_tasks;
		}

		$stringPlaceholders     = array_fill( 0, count( $get_contact_automation_id ), '%s' );
		$placeholdersautomation = implode( ', ', $stringPlaceholders );

		$scheduled_tasks_query = $wpdb->prepare( "
								SELECT t.ID as id, t.integration_slug as slug, t.integration_action as action, t.automation_id as a_id, t.status as status, t.e_date as date
								FROM {$wpdb->prefix}bwfan_tasks as t
								LEFT JOIN {$wpdb->prefix}bwfan_taskmeta as m
								ON t.ID = m.bwfan_task_id
								WHERE t.automation_id IN ($placeholdersautomation)
								ORDER BY t.e_date DESC
								", $get_contact_automation_id );
		$scheduled_tasks       = $wpdb->get_results( $scheduled_tasks_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $scheduled_tasks;
	}


	public static function get_logs_for_contact( $contact_id ) {
		$contact_logs = array();

		global $wpdb;

		$get_contact_automation_id = $wpdb->get_col( "SELECT DISTINCT automation_id from {$wpdb->prefix}bwfan_contact_automations where contact_id='" . $contact_id . "'" ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $get_contact_automation_id ) ) {
			return $contact_logs;
		}

		$stringPlaceholders     = array_fill( 0, count( $get_contact_automation_id ), '%s' );
		$placeholdersautomation = implode( ', ', $stringPlaceholders );

		$contact_logs_query = $wpdb->prepare( "
								SELECT l.ID as id, l.integration_slug as slug, l.integration_action as action, l.automation_id as a_id, l.status as status, l.e_date as date
								FROM {$wpdb->prefix}bwfan_logs as l
								LEFT JOIN {$wpdb->prefix}bwfan_logmeta as m
								ON l.ID = m.bwfan_log_id
								WHERE l.automation_id IN ($placeholdersautomation)
								ORDER BY l.e_date DESC
								", $get_contact_automation_id );
		$contact_logs       = $wpdb->get_results( $contact_logs_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_logs;
	}

	/**
	 * Get last abandoned cart
	 *
	 * @param $contact_email
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_last_abandoned_cart( $contact_email = '' ) {
		global $wpdb;
		$query = "SELECT `last_modified`, `items`, `total` FROM {$wpdb->prefix}bwfan_abandonedcarts WHERE `status` IN (1,3,4) and `email` = %s ORDER BY `last_modified` DESC LIMIT 0,1";

		return $wpdb->get_results( $wpdb->prepare( $query, $contact_email ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/** get top automation data
	 * @return array|object|null
	 */
	public static function get_top_automations() {
		global $wpdb;
		$automation_table = $wpdb->prefix . 'bwfan_automations';
		$query            = "SELECT a.`ID` AS aid, a.`event`, `title` AS name, a.`v`, COUNT(ac.`cid`) AS `contact` FROM {$automation_table} AS a LEFT JOIN {$wpdb->prefix}bwfan_automation_complete_contact AS ac ON ac.aid = a.ID WHERE `status` = 1 GROUP BY ac.`aid` ORDER BY `contact` DESC LIMIT 0,5";
		if ( bwfan_is_woocommerce_active() ) {
			$conversion_table = $wpdb->prefix . 'bwfan_conversions';
			$post_table       = $wpdb->prefix . 'posts';
			$query            = "SELECT c.`oid` AS `aid`,a.`event`, SUM(c.`wctotal`) AS `total_revenue`, a.`v`, a.`title` AS `name` FROM $automation_table AS a LEFT JOIN $conversion_table AS c ON a.ID = c.oid  JOIN $post_table AS p ON c.`wcid` = p.`ID` WHERE c.`otype` = 1 GROUP BY c.`oid` ORDER BY `total_revenue` DESC LIMIT 0,5";
		}
		$automations = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return ! empty( $automations ) ? array_map( function ( $automation ) {
			if ( isset( $automation['event'] ) ) {
				$event_obj           = BWFAN_Core()->sources->get_event( $automation['event'] );
				$automation['event'] = ! empty( $event_obj ) ? $event_obj->get_name() : '';
			}

			return $automation;
		}, $automations ) : [];
	}

}