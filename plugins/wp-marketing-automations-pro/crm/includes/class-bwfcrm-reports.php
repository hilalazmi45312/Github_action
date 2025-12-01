<?php
/**
 * Reports Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Reports
 *
 */
class BWFCRM_Reports {

	/**
	 * Get all engagements
	 *
	 * @param $search
	 * @param $type
	 * @param $mode
	 * @param $version
	 *
	 * @return array|object|stdClass[]
	 */
	public static function get_engagements( $search, $type, $mode, $version = 2 ) {

		if ( $type === 2 ) {
			$campaigns = self::get_campaigns_by_title( $search, $mode );

			return ! empty( $campaigns ) ? $campaigns : [];
		}

		$automations = BWFAN_Common::get_automation_by_title( $search, $version );

		return ! empty( $automations ) ? $automations : [];

	}

	/**
	 * @param $search
	 * @param $campaign_type
	 * @param $limit
	 * @param $offset
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_campaigns_by_title( $search, $campaign_type = 1, $limit = 10, $offset = 0 ) {
		global $wpdb;
		$args             = [];
		$order_search_str = ! empty( $search ) ? " AND cm.title LIKE '%s' " : '';
		if ( ! empty( $order_search_str ) ) {
			$args[] = "%$search%";
		}
		$query = "SELECT cm.id,cm.title FROM {$wpdb->prefix}bwfan_broadcast AS cm WHERE 1=1 $order_search_str AND cm.type=$campaign_type GROUP BY cm.id ORDER BY cm.title ASC";
		if ( ! empty( $limit ) ) {
			$query .= " LIMIT %d, %d";
			$args  = array_merge( $args, [ $offset, $limit ] );
		}

		return $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_automation_by_title( $search, $version = 1 ) {
		global $wpdb;

		if ( 2 === $version ) {

			$where = ! empty( $search ) ? " WHERE title LIKE '%$search%' " : '';
			$query = "SELECT ID as id, title  FROM {$wpdb->prefix}bwfan_automations $where ORDER BY title ASC";
		} else {

			$where = ! empty( $search ) ? " WHERE am.meta_value LIKE '%$search%' AND am.meta_key = 'title'" : '';
			$query = "SELECT am.bwfan_automation_id AS id,am.meta_value AS title FROM {$wpdb->prefix}bwfan_automationmeta AS am $where GROUP BY am.bwfan_automation_id ORDER BY am.meta_value ASC";
		}

		return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param $start_date
	 * @param $end_date
	 * @param $is_interval
	 * @param $interval
	 *
	 */
	public static function get_email_stats( $start_date, $end_date, $is_interval, $interval, $oid = 0, $type = 0, $mode = 1 ) {
		global $wpdb;

		$table          = "{$wpdb->prefix}bwfan_engagement_tracking";
		$date_col       = "con.created_at";
		$interval_query = '';
		$group_by       = '';
		$order_by       = ' con.ID ';

		if ( 'interval' === $is_interval ) {
			$get_interval   = BWFCRM_Dashboards::get_interval_format_query( $interval, $date_col );
			$interval_query = $get_interval['interval_query'];
			$interval_group = $get_interval['interval_group'];
			$group_by       = "GROUP BY $interval_group";
			$order_by       = ' time_interval ';

			if ( ! empty( $oid ) ) {
				$interval_group = ! empty( $interval_group ) ? ", $interval_group" : '';
				$group_by       = "GROUP BY con.oid $interval_group ";
			}
		}
		$conversions_query = "SELECT trackid, count(ID) as conversions, SUM(wctotal) as revenue FROM {$wpdb->prefix}bwfan_conversions GROUP BY trackid";
		$query             = "SELECT SUM(if(con.open>0,1,0)) AS open_count ,SUM(IF(con.c_status=2, 1, 0)) as sent,SUM(if(con.click>0,1,0)) AS click_count,  SUM(conv.conversions) as conversions, SUM(conv.revenue) as revenue " . $interval_query . "  FROM {$table} AS con LEFT JOIN ({$conversions_query}) as conv ON con.ID = conv.trackid WHERE 1=1 AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " <= '" . $end_date . "' AND con.type < " . BWFAN_Email_Conversations::$TYPE_NOTE . " AND con.mode=$mode ";

		if ( ! empty( $oid ) ) {
			$query .= " AND con.oid IN ($oid) ";
		}

		if ( ! empty( $type ) && is_numeric( $type ) ) {
			$query .= " AND con.type = $type ";
		}

		// $saved_last_id = get_option( 'bwfan_show_contacts_from' );
		// $query         .= ! empty( $saved_last_id ) ? " AND con.cid > $saved_last_id " : '';


		$query .= $group_by . " ORDER BY " . $order_by . " ASC ";

		$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $results;
	}

	public static function get_interaction_stats_by_date_range( $intervals, $type, $oids = [], $mode = 1 ) {
		global $wpdb;

		$open_queries  = "DATE_FORMAT(f_open, '%Y-%m-%d' ) AS f_open, count(DATE_FORMAT(f_open, '%Y-%m-%d' )) AS f_open_count";
		$click_queries = "DATE_FORMAT(f_click, '%Y-%m-%d' ) AS f_click, count(DATE_FORMAT(f_click, '%Y-%m-%d' )) AS f_click_count";

		$open_query  = "SELECT $open_queries FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE 1=1 AND type < " . BWFAN_Email_Conversations::$TYPE_NOTE . " AND mode=$mode AND (`f_open` BETWEEN '" . $intervals[0] . "' AND '" . $intervals[1] . "')";
		$click_query = "SELECT $click_queries FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE 1=1 AND type < " . BWFAN_Email_Conversations::$TYPE_NOTE . " AND mode=$mode AND (`f_click` BETWEEN '" . $intervals[0] . "' AND '" . $intervals[1] . "')";

		if ( ! empty( $type ) ) {
			$open_query  .= " AND `type` = $type ";
			$click_query .= " AND `type` = $type ";
		}

		$where = '';
		if ( ! empty( $oids ) ) {
			$oids  = is_array( $oids ) ? implode( ' ,', $oids ) : $oids;
			$where = " AND oid IN ($oids) ";
		}

		$open_group_by  = "GROUP BY DATE_FORMAT(f_open, '%Y-%m-%d' )";
		$click_group_by = "GROUP BY DATE_FORMAT(f_click, '%Y-%m-%d' )";
		$open_query     .= $where . ' ' . $open_group_by;
		$click_query    .= $where . ' ' . $click_group_by;

		return array(
			'open'  => ! empty( $open_queries ) ? $wpdb->get_results( $open_query, ARRAY_A ) : [], //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			'click' => ! empty( $click_queries ) ? $wpdb->get_results( $click_query, ARRAY_A ) : [] //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		);
	}

	/**
	 * Get all contacts before given date
	 *
	 * @param $date
	 *
	 * @return array|object|null
	 */
	public static function get_contacts_before_date( $date ) {
		global $wpdb;

		// $saved_last_id  = get_option( 'bwfan_show_contacts_from' );
		// $start_id_query = ! empty( $saved_last_id ) ? " AND id > $saved_last_id " : '';
		$start_id_query = '';
		$query          = "SELECT COUNT(id) as `contact_counts` FROM {$wpdb->prefix}bwf_contact WHERE `creation_date` < '{$date}' $start_id_query";
		$contact_counts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_counts;
	}

	public static function get_customers_before_date( $date ) {
		global $wpdb;

		$query          = "SELECT COUNT(`{$wpdb->prefix}bwf_contact`.`id`) as `contact_counts` FROM `{$wpdb->prefix}bwf_contact` INNER JOIN `{$wpdb->prefix}bwf_wc_customers` ON `{$wpdb->prefix}bwf_contact`.`id` = `{$wpdb->prefix}bwf_wc_customers`.`cid` WHERE `creation_date` < '{$date}'";
		$contact_counts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_counts;
	}

	/**
	 * @param $start_date
	 * @param $end_date
	 * @param $is_interval
	 * @param $interval
	 * @param $aid automation_id
	 *
	 * @return array|object|null
	 */
	public static function get_unsubscribers_total( $start_date, $end_date, $is_interval, $interval, $aid = 0 ) {
		global $wpdb;
		$table          = $wpdb->prefix . 'bwfan_message_unsubscribe';
		$date_col       = "c_date";
		$interval_query = '';
		$group_by       = '';
		$order_by       = ' ID ';
		$where_aid      = '';

		if ( 'interval' === $is_interval ) {
			$get_interval   = BWFCRM_Dashboards::get_interval_format_query( $interval, $date_col );
			$interval_query = $get_interval['interval_query'];
			$interval_group = $get_interval['interval_group'];
			$group_by       = "GROUP BY " . $interval_group;
			$order_by       = ' time_interval ';
		}

		if ( absint( $aid ) > 0 ) {
			$where_aid = " AND automation_id = $aid AND c_type = 1 ";
		}

		$base_query = "SELECT  COUNT(ID) as unsubs_count" . $interval_query . "  FROM `" . $table . "` WHERE 1=1 $where_aid AND `" . $date_col . "` >= '" . $start_date . "' AND `" . $date_col . "` <= '" . $end_date . "'" . $group_by . " ORDER BY " . $order_by . " ASC";

		$contact_counts = $wpdb->get_results( $base_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_counts;
	}

	/**
	 * @param $all_data
	 * @param $interval_key
	 * @param $current_interval
	 *
	 * @return array|false
	 */
	public static function maybe_interval_exists( $all_data, $interval_key, $current_interval ) {
		if ( is_array( $all_data ) && count( $all_data ) > 0 ) {
			$new_data = [];

			foreach ( $all_data as $data ) {
				if ( isset( $data[ $interval_key ] ) && $current_interval === $data[ $interval_key ] ) {

					$new_data[ $data[ $interval_key ] ][] = $data;
				}
			}

			return $new_data;
		}

		return false;
	}

	/**
	 * @param $offset
	 * @param $limit
	 *
	 * @return array|object|null
	 */
	public static function get_conversations_with_template( $start_date, $end_date, $type, $offset, $limit, $oid = [], $mode = 1 ) {
		global $wpdb;

		$table       = "{$wpdb->prefix}bwfan_engagement_tracking";
		$date_col    = "con.created_at";
		$limit_query = '';
		$oids_query  = '';
		$mode_query  = " AND con.mode= $mode ";
		$type_query  = '';
		$order_by    = ' con.oid ';

		if ( ! empty( $oid ) && is_array( $oid ) ) {
			$oids       = implode( ' ,', $oid );
			$oids_query = " AND con.oid IN ($oids) ";
		}

		if ( ! empty( $type ) && is_numeric( $type ) ) {
			$type_query = " AND con.type = $type ";
		}

		$conversions_query = "SELECT trackid, count(ID) as conversions, SUM(wctotal) as revenue FROM {$wpdb->prefix}bwfan_conversions GROUP BY trackid";

		// $saved_last_id  = get_option( 'bwfan_show_contacts_from' );
		// $start_id_query = ! empty( $saved_last_id ) ? " AND con.id > $saved_last_id " : '';
		$start_id_query = '';
		$query          = "SELECT con.ID,con.oid,con.tid,con.type, SUM(IF(con.c_status=2, 1, 0)) as sent, SUM(con.open) AS open_count , (SUM(IF(con.open>0, 1, 0))/COUNT(con.ID)) * 100 as open_rate, SUM(con.click) as click_count, (SUM(IF(con.click>0, 1, 0))/COUNT(con.ID)) * 100 as click_rate, SUM(conv.conversions) as conversions, SUM(conv.revenue) as revenue  FROM {$table} AS con LEFT JOIN ({$conversions_query}) as conv ON con.ID = conv.trackid WHERE 1=1 AND con.c_status=2 $start_id_query AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " <= '" . $end_date . "' AND con.type < " . BWFAN_Email_Conversations::$TYPE_NOTE . "  $oids_query  $mode_query $type_query ";
		$count_query    = "SELECT COUNT(con.ID) FROM {$table} AS con WHERE 1=1 AND con.c_status=2 $start_id_query AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " <= '" . $end_date . "' AND con.type < " . BWFAN_Email_Conversations::$TYPE_NOTE . " $oids_query  $mode_query $type_query ";

		if ( ! empty( $limit ) ) {
			$limit_query .= " LIMIT $offset,$limit";
		}

		$query        .= "GROUP BY con.oid,con.tid,con.type ORDER BY $order_by DESC $limit_query";
		$count_query  .= "GROUP BY con.oid,con.tid,con.type ";
		$results      = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_result = $wpdb->get_results( $count_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$response                = array_map( function ( $data ) {
			$title         = self::get_object_title( $data['oid'], $data['type'] );
			$template_data = BWFAN_Model_Templates::get( absint( $data['tid'] ) );
			if ( empty( $template_data ) ) {
				$template_data = BWFAN_Model_Message::get_message_by_track_id( $data['ID'] );
			}
			$data['title']    = $title;
			$data['subject']  = empty( $template_data['subject'] ) ? '' : $template_data['subject'];
			$data['template'] = empty( $template_data['template'] ) ? '' : $template_data['template'];

			return $data;
		}, $results );
		$response['total_count'] = count( $count_result );

		return $response;
	}

	public static function get_object_title( $oid, $type ) {

		switch ( $type ) {
			case BWFAN_Email_Conversations::$TYPE_CAMPAIGN:
				$campaign = BWFAN_Model_Broadcast::get( absint( $oid ) );

				return isset( $campaign['title'] ) ? $campaign['title'] : '';
			case BWFAN_Email_Conversations::$TYPE_AUTOMATION:
				$automation_meta = BWFAN_Core()->automations->get_automation_data_meta( $oid );

				return is_array( $automation_meta ) && isset( $automation_meta['title'] ) ? $automation_meta['title'] : '';
			case BWFAN_Email_Conversations::$TYPE_NOTE:
				$note = BWFAN_Model_Contact_Note::get( absint( $oid ) );

				return isset( $note['title'] ) ? $note['title'] : '';
			default:
				return 'Direct';

		}
	}

	/**
	 * @param $start_date
	 * @param $end_date
	 * @param $is_interval
	 * @param $interval
	 *
	 * @return array|object|null
	 */
	public static function get_total_contacts( $start_date, $end_date, $is_interval, $interval ) {
		global $wpdb;
		$table          = $wpdb->prefix . 'bwf_contact';
		$date_col       = "creation_date";
		$interval_query = '';
		$group_by       = '';
		$order_by       = ' id ';

		if ( 'interval' === $is_interval ) {
			$get_interval   = BWFCRM_Dashboards::get_interval_format_query( $interval, $date_col );
			$interval_query = $get_interval['interval_query'];
			$interval_group = $get_interval['interval_group'];
			$group_by       = "GROUP BY " . $interval_group;
			$order_by       = ' time_interval ';
		}

		// $saved_last_id  = get_option( 'bwfan_show_contacts_from' );
		// $start_id_query = ! empty( $saved_last_id ) ? " AND id > $saved_last_id " : '';
		$start_id_query = '';

		$base_query = "SELECT  count(id) as contact_counts" . $interval_query . "  FROM `" . $table . "` WHERE 1=1 $start_id_query AND `" . $date_col . "` >= '" . $start_date . "' AND `" . $date_col . "` <= '" . $end_date . "'" . $group_by . " ORDER BY " . $order_by . " ASC";

		$contact_counts = $wpdb->get_results( $base_query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $contact_counts;
	}

	public static function get_conversions( $start_date, $end_date, $oid, $type, $mode = 1 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'bwfan_conversions';

		$query = "SELECT count(conv.ID) as conversions, if((SUM(wctotal))>0,SUM(wctotal),0) as revenue From {$table} as conv JOIN {$wpdb->prefix}bwfan_engagement_tracking AS et ON conv.trackid = et.ID WHERE 1=1 AND date>='$start_date' AND date<'$end_date' AND et.mode=$mode ";

		if ( ! empty( $oid ) ) {
			$query .= " AND conv.oid = $oid ";
		}
		if ( ! empty( $type ) ) {
			$query .= " AND conv.otype=$type ";
		}

		return $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * @param $start_date
	 * @param $end_date
	 * @param $type
	 * @param $offset
	 * @param $limit
	 * @param int $mode
	 */
	public static function get_direct_conversations_with_template( $start_date, $end_date, $type, $offset, $limit, $mode = 1 ) {
		global $wpdb;
		$con_table     = "{$wpdb->prefix}bwfan_engagement_tracking";
		$message_table = "{$wpdb->prefix}bwfan_message";
		$contact_table = "{$wpdb->prefix}bwf_contact";
		$limit_query   = '';

		$select_query = "message.sub,message.track_id, message.date, con.cid, con.author_id, con.open, con.click, con.o_interaction, con.c_interaction, contact.f_name, contact.l_name, contact.email";

		$date_col   = 'message.date';
		$date_query = "AND " . $date_col . " >= '" . $start_date . "' AND " . $date_col . " <= '" . $end_date . "' ";

		if ( ! empty( $limit ) ) {
			$limit_query .= " LIMIT $offset,$limit";
		}

		$query = "SELECT $select_query FROM $message_table as message LEFT JOIN $con_table as con on message.track_id = con.ID LEFT JOIN $contact_table as contact on contact.id = con.cid WHERE con.mode = $mode $date_query";

		$count_query = "SELECT count( message.ID ) FROM $message_table as message LEFT JOIN $con_table as con on message.track_id = con.ID LEFT JOIN $contact_table as contact on contact.id = con.cid WHERE con.mode = $mode $date_query";

		$query .= " ORDER BY message.date DESC $limit_query";

		$results      = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count_result = $wpdb->get_var( $count_query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return array(
			'data'        => $results,
			'total_count' => $count_result,
		);
	}

}