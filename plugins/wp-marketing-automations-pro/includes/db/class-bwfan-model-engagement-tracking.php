<?php

if ( ! BWFAN_PRO_Common::is_lite_3_0() && ! class_exists( 'BWFAN_Model_Engagement_Tracking' ) ) {
	class BWFAN_Model_Engagement_Tracking extends BWFAN_Model {
		static $primary_key = 'ID';

		static function get_conversations_by_cid( $cid, $mode, $offset = 0, $limit = 25 ) {
			global $wpdb;
			$table = self::_table();
			$and   = '';
			if ( ! empty( $mode ) ) {
				$and = " AND con.mode = $mode";
			}
			$query = "SELECT con.*, ct.subject, ct.template as message FROM $table AS con LEFT JOIN {$wpdb->prefix}bwfan_templates AS ct ON con.tid=ct.ID WHERE cid = '$cid' $and ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

			return self::get_results( $query );
		}

		public static function get_total_engagements( $cid, $mode ) {
			global $wpdb;
			$table   = self::_table();
			$query   = [];
			$query[] = "SELECT COUNT(ID) FROM {$table} WHERE 1=1";
			$query[] = $wpdb->prepare( "AND cid = %d", $cid );
			$query[] = $wpdb->prepare( "AND mode = %d", $mode );

			return $wpdb->get_var( implode( ' ', $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_recipents_by_type( $oid, $type, $offset = 0, $limit = 25 ) {
			global $wpdb;

			/** Fetching all Engagements for broadcast **/
			$table   = self::_table();
			$query   = "SELECT conv.ID AS conversation_id,c.f_name,c.l_name,c.wpid,conv.send_to,conv.cid,conv.mode,conv.type,conv.open,conv.click,conv.oid,if(conv.c_status=2,1,0) as sent,conv.created_at as sent_time FROM {table_name} AS conv LEFT JOIN {$wpdb->prefix}bwf_contact AS c ON c.ID = conv.cid WHERE conv.type = $type AND conv.oid = $oid AND (c_status = 2 OR c_status = 3) ORDER BY conv.updated_at DESC LIMIT $limit OFFSET $offset";
			$results = self::get_results( $query );

			/** Fetch Engagements total count **/
			$query = "SELECT COUNT(conv.ID) FROM $table AS conv WHERE conv.type = $type AND conv.oid = $oid";
			$total = absint( $wpdb->get_var( $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$conversations_ids = empty( $results ) ? [] : array_column( $results, 'conversation_id' );
			$conversions       = [];

			/** Fetch Engagement's conversions **/
			if ( ! empty( $conversations_ids ) ) {
				$conv_ids           = implode( ', ', $conversations_ids );
				$conversion_query   = "SELECT wcid,cid,trackid,wctotal FROM {$wpdb->prefix}bwfan_conversions WHERE trackid IN( $conv_ids ) AND otype = $type";
				$conversions_result = self::get_results( $conversion_query );
				foreach ( $conversions_result as $conversion ) {
					if ( ! isset( $conversions[ absint( $conversion['cid'] ) ] ) ) {
						$conversions[ absint( $conversion['cid'] ) ] = [];
					}
					if ( ! isset( $conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] ) ) {
						$conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] = 0;
					}
					$conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] += floatval( $conversion['wctotal'] );
				}
			}
			$send_to       = empty( $results ) ? [] : array_column( $results, 'send_to' );
			$recipients    = implode( "', '", $send_to );
			$unsubscribers = [];

			/** Fetch Unsubsribers of broadcast **/
			if ( ! empty( $recipients ) ) {
				$unsubscribe_query    = "SELECT ID,recipient FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE recipient IN ('$recipients') AND automation_id=$oid AND c_type = 2";
				$unsubscribers_result = self::get_results( $unsubscribe_query );
				foreach ( $unsubscribers_result as $unsubscriber ) {
					$unsubscribers[ $unsubscriber['recipient'] ] = $unsubscriber;
				}
			}

			$conversations = array_map( function ( $engagement ) use ( $conversions, $unsubscribers ) {
				$conv_id    = absint( $engagement['conversation_id'] );
				$contact_id = absint( $engagement['cid'] );
				$contact    = new BWFCRM_Contact( $contact_id );

				$deleted           = ! $contact->is_contact_exists();
				$revenue           = ( isset( $conversions[ $contact_id ][ $conv_id ] ) ) ? $conversions[ $contact_id ][ $conv_id ] : 0;
				$conversions_count = ( isset( $conversions[ $contact_id ][ $conv_id ] ) ) ? 1 : 0;
				$unsubscribed      = ( isset( $unsubscribers[ $engagement['send_to'] ] ) ) ? 1 : 0;

				$engagement['revenue']         = $revenue;
				$engagement['conversions']     = $conversions_count;
				$engagement['unsubscribed']    = $unsubscribed;
				$engagement['contact_deleted'] = $deleted;

				return $engagement;
			}, $results );

			return array( 'conversations' => $conversations, 'total' => $total );
		}

		public static function get_automation_recipents( $oid, $offset = 0, $limit = 25 ) {
			global $wpdb;
			$table = self::_table();
			$type  = BWFAN_Email_Conversations::$TYPE_AUTOMATION;

			/** Fetching all Engagements for automation **/
			$query       = "SELECT GROUP_CONCAT(DISTINCT ID) as track_ids,cid, send_to, SUM(open) as open,mode, SUM(click) as click, COUNT(*) as total, SUM(IF(c_status=2,1,0)) as sent,MAX(created_at) as sent_time FROM $table WHERE oid = $oid AND type = $type AND cid>0 AND (c_status = 2 OR c_status = 3) GROUP BY send_to,cid,mode ORDER BY sent_time DESC LIMIT $limit OFFSET $offset";
			$engagements = self::get_results( $query );
			if ( empty( $engagements ) ) {
				array( 'conversations' => [], 'total' => 0 );
			}

			/** Get Engagements total count **/
			$count_query = "SELECT count(send_to) FROM $table WHERE oid = $oid AND type = $type AND cid>0 AND (c_status = 2 OR c_status = 3)  GROUP BY send_to,cid,mode";
			$total       = self::get_results( $count_query );

			$c_ids       = empty( $engagements ) ? [] : array_column( $engagements, 'cid' );
			$conversions = [];
			$contacts    = [];
			if ( ! empty( $c_ids ) ) {

				/** Get contacts f_name, l_name **/
				$cids          = implode( ',', $c_ids );
				$contact_query = "SELECT id,f_name,l_name,email,contact_no FROM {$wpdb->prefix}bwf_contact WHERE id IN($cids)";
				$contact_data  = self::get_results( $contact_query );
				foreach ( $contact_data as $contact ) {
					$contacts[ $contact['id'] ] = $contact;
				}

				/** Get contacts conversion **/
				$conversion_query  = "SELECT c.wcid,c.cid,c.trackid,c.wctotal FROM {$wpdb->prefix}bwfan_conversions as c JOIN {$wpdb->prefix}posts as p on c.wcid=p.ID  WHERE 1=1 AND c.oid = $oid AND c.otype = $type AND c.cid IN( $cids )";
				$conversion_result = self::get_results( $conversion_query );
				foreach ( $conversion_result as $conversion ) {
					if ( ! isset( $conversions[ absint( $conversion['cid'] ) ] ) ) {
						$conversions[ absint( $conversion['cid'] ) ] = [];
					}
					if ( ! isset( $conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] ) ) {
						$conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] = 0;
					}
					$conversions[ absint( $conversion['cid'] ) ][ absint( $conversion['trackid'] ) ] += floatval( $conversion['wctotal'] );
				}
			}

			$send_to       = empty( $engagements ) ? [] : array_column( $engagements, 'send_to' );
			$recipients    = implode( "', '", $send_to );
			$unsubscribers = [];

			if ( ! empty( $recipients ) ) {

				/** Get unsubscribers data **/
				$unsubscribe_query    = "SELECT ID,recipient FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE recipient IN ('$recipients') AND automation_id=$oid AND c_type = $type";
				$unsubscribers_result = self::get_results( $unsubscribe_query );
				foreach ( $unsubscribers_result as $unsubscriber ) {
					$unsubscribers[ $unsubscriber['recipient'] ] = $unsubscriber;
				}
			}

			$conversations = array_map( function ( $engagement ) use ( $contacts, $conversions, $unsubscribers ) {
				$contact_id = absint( $engagement['cid'] );
				$deleted    = true;
				if ( isset( $contacts[ $contact_id ] ) ) {
					$engagement['f_name'] = $contacts[ $contact_id ]['f_name'];
					$engagement['l_name'] = $contacts[ $contact_id ]['l_name'];
					$deleted              = false;
				}

				$revenue           = 0;
				$conversions_count = 0;
				$track_ids         = $engagement['track_ids'];
				$track_ids         = explode( ',', $track_ids );
				$track_ids         = array_map( 'absint', $track_ids );

				if ( isset( $conversions[ $contact_id ] ) ) {
					$conversion_trackids = array_keys( $conversions[ $contact_id ] );
					if ( array_intersect( $track_ids, $conversion_trackids ) ) {
						$revenue = array_sum( $conversions[ $contact_id ] );
					}
					$conversions_count = count( $conversion_trackids );
				}

				$engagement['revenue']         = $revenue;
				$engagement['conversions']     = $conversions_count;
				$engagement['unsubscribed']    = isset( $unsubscribers[ $engagement['send_to'] ] ) ? 1 : 0;
				$engagement['contact_deleted'] = $deleted;

				return $engagement;
			}, $engagements );

			return array( 'conversations' => $conversations, 'total' => count( $total ) );
		}

		public static function get_engagement_recipient_timeline( $convid ) {
			$conv = self::get( absint( $convid ) );
			if ( empty( $conv ) || ! isset( $conv['o_interaction'] ) ) {
				return array();
			}
			$conversions = BWFAN_Model_Conversions::get_specific_rows( 'trackid', absint( $convid ) );
			$final_data  = self::prepare_timeline_data( $conv, $conversions );

			return $final_data;
		}

		public static function prepare_timeline_data( $conv, $conversions = [] ) {
			$opens      = ! empty( $conv['o_interaction'] ) ? json_decode( $conv['o_interaction'], true ) : array();
			$clicks     = ! empty( $conv['c_interaction'] ) ? json_decode( $conv['c_interaction'], true ) : array();
			$mode       = $conv['mode'];
			$final_data = [
				[
					'type' => 2 === absint( $conv['c_status'] ) ? 'sent' : 'failed',
					'mode' => $mode,
					'date' => ! empty( $conv['created_at'] ) ? get_date_from_gmt( $conv['created_at'] ) : ''
				]
			];
			if ( 3 === absint( $conv['c_status'] ) ) {
				$msg                          = BWFAN_Model_Engagement_Trackingmeta::get_meta( $conv['ID'], 'error_msg' );
				$final_data[0]['err_message'] = empty( $msg[0]['meta_value'] ) ? 'Email not sent' : $msg[0]['meta_value'];
			}

			/** Opens */
			$final_data = array_merge( $final_data, array_map( function ( $open ) {
				return array( 'type' => 'open', 'date' => $open );
			}, $opens ) );

			/** Clicks */
			$final_data = array_merge( $final_data, array_map( function ( $click ) use ( $mode ) {
				return array( 'type' => 'click', 'mode' => $mode, 'date' => $click );
			}, $clicks ) );

			if ( ! empty( $conversions ) ) {
				/** Conversions */
				$final_data = array_merge( $final_data, array_map( function ( $conversion ) {
					return array(
						'type'     => 'conversion',
						'date'     => ! empty( $conversion['date'] ) ? get_date_from_gmt( $conversion['date'] ) : '',
						'revenue'  => $conversion['wctotal'],
						'order_id' => $conversion['wcid']
					);
				}, $conversions ) );
			}

			usort( $final_data, function ( $datum1, $datum2 ) {
				return strtotime( $datum1['date'] ) > strtotime( $datum2['date'] ) ? - 1 : 1;
			} );

			return $final_data;
		}

		/**
		 * @param $automation_id
		 * @param int $contat_id
		 *
		 * @return array
		 */
		public static function get_automation_recipient_timeline( $automation_id, $contact_id, $mode ) {
			$table       = self::_table();
			$query       = " SELECT ID,mode,c_status,c_interaction,o_interaction,created_at FROM $table WHERE type = 1 AND  oid = $automation_id AND cid = $contact_id AND (c_status = 2 OR c_status = 3) AND mode=$mode";
			$engagements = self::get_results( $query );
			$engage_ids  = array_map( function ( $engagement ) {
				return $engagement['ID'];
			}, $engagements );
			$conversions = BWFAN_Model_Conversions::get_conversions_by_oid( $automation_id, $contact_id, $engage_ids );

			$final_data = [];
			foreach ( $engagements as $engagement ) {
				$final_data = array_merge( $final_data, self::prepare_timeline_data( $engagement ) );
			}

			/** Conversions */
			$final_data = array_merge( $final_data, array_map( function ( $conversion ) {
				return array(
					'type'     => 'conversion',
					'date'     => ! empty( $conversion['date'] ) ? get_date_from_gmt( $conversion['date'] ) : '',
					'revenue'  => $conversion['wctotal'],
					'order_id' => $conversion['wcid']
				);
			}, $conversions ) );

			usort( $final_data, function ( $data1, $data2 ) {
				return strtotime( $data1['date'] ) > strtotime( $data2['date'] ) ? - 1 : 1;
			} );

			return $final_data;
		}

		/**
		 * @param $oid (Conversation's Source ID)
		 * @param int $o_type (Conversation's Source Type)
		 *
		 * @return false|string|null
		 */
		public static function get_first_conversation_date( $oid, $o_type = 1 ) {
			if ( empty( $oid ) ) {
				return false;
			}

			global $wpdb;

			$query = "SELECT MIN(created_at) FROM `{$wpdb->prefix}bwfan_engagement_tracking` where oid = $oid and type = $o_type";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * @param $after
		 * @param $before
		 *
		 * @return array|object|null
		 */
		public static function get_stats( $after, $before ) {
			global $wpdb;

			$query = "select count( ID ) as sent_total, sum( open ) as total_open, sum( click ) as total_click from {$wpdb->prefix}bwfan_engagement_tracking where c_status = 2 group by c_status";

			return $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * @return array|object|null
		 */
		public static function get_popular_emails() {
			global $wpdb;
			$template_table   = $wpdb->prefix . 'bwfan_templates';
			$engagement_table = $wpdb->prefix . 'bwfan_engagement_tracking';

			$query   = "SELECT `tid`, SUM(`open`) as `opens`, SUM(`click`) as `clicks` FROM $engagement_table WHERE tid IS NOT NULL AND open > 0 GROUP BY tid ORDER BY `opens` DESC LIMIT 0,5";
			$results = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( empty( $results ) ) {
				return [];
			}

			$tids = array_column( $results, 'tid' );

			$placeholders = array_fill( 0, count( $tids ), '%d' );
			$placeholders = implode( ', ', $placeholders );

			$query   = $wpdb->prepare( "SELECT `ID`, `subject` FROM $template_table WHERE `ID` IN ($placeholders)", $tids );
			$result2 = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$subjects = [];
			foreach ( $result2 as $val ) {
				$subjects[ $val['ID'] ] = $val['subject'];
			}

			foreach ( $results as $key => $val ) {
				if ( isset( $subjects[ $val['tid'] ] ) ) {
					$results[ $key ]['subject'] = $subjects[ $val['tid'] ];
				}
			}

			return $results;
		}

		public static function get_last_engagement_sent_time( $contact_id, $mode = 1 ) {
			$query   = "SELECT max(created_at) as last_sent FROM {table_name} WHERE cid = $contact_id AND mode = $mode AND c_status = 2";
			$results = self::get_results( $query );
			$res     = '';
			if ( ! empty( $results[0]['last_sent'] ) ) {
				$res = $results[0]['last_sent'];
			}

			return $res;
		}

		public static function get_last_email_open_time( $contact_id ) {
			$query           = "SELECT o_interaction,c_interaction FROM {table_name} WHERE cid = $contact_id AND c_status = 2";
			$results         = self::get_results( $query );
			$last_open_time  = 0;
			$last_click_time = 0;
			foreach ( $results as $data ) {
				$o_interactions = ! empty( $data['o_interaction'] ) ? json_decode( $data['o_interaction'], true ) : '';
				$c_interaction  = ! empty( $data['c_interaction'] ) ? json_decode( $data['c_interaction'], true ) : '';

				// Get last open time
				if ( is_array( $o_interactions ) ) {
					$max_o_interaction = max( $o_interactions );
					if ( $max_o_interaction > $last_open_time ) {
						$last_open_time = $max_o_interaction;
					}
				}

				// Get last click time
				if ( is_array( $c_interaction ) ) {
					$max_c_interaction = max( $c_interaction );
					if ( $max_c_interaction > $last_click_time ) {
						$last_click_time = $max_c_interaction;
					}
				}
			}

			return [ 'last_open_time' => $last_open_time, 'last_click_time' => $last_click_time ];
		}

		public static function get_last_24_hours_conversations_count( $mode = 1 ) {
			global $wpdb;
			$start_time = date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
			$and_mode   = ! empty( absint( $mode ) ) ? " AND mode = $mode " : "";
			$query      = "SELECT COUNT(ID)  FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE c_status = 2 AND created_at > '$start_time' $and_mode";

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_most_interacted_template( $oid, $otype, $mode ) {
			global $wpdb;
			$interaction = BWFAN_Email_Conversations::$MODE_SMS === absint( $mode ) ? 'click' : 'open';
			$sql         = "SELECT tid, SUM($interaction) as $interaction FROM `{$wpdb->prefix}bwfan_engagement_tracking` WHERE oid=$oid AND type=$otype AND c_status=2 GROUP BY tid ORDER BY $interaction DESC LIMIT 0, 1";

			return $wpdb->get_row( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Return first engagement id
		 */
		public static function get_first_engagement_id() {
			global $wpdb;
			$query = 'SELECT MIN(`ID`) FROM ' . self::_table();

			return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/**
		 * Get automation step analytics
		 *
		 * @param $oid
		 * @param $step_ids
		 * @param $after_date
		 *
		 * @return array|object|stdClass
		 */
		public static function get_automation_step_analytics( $oid, $step_ids, $after_date = '', $end_date = '' ) {
			global $wpdb;

			if ( empty( $step_ids ) ) {
				return [];
			}

			$table    = "{$wpdb->prefix}bwfan_engagement_tracking";
			$step_ids = ! is_array( $step_ids ) ? [ $step_ids ] : $step_ids;
			$ids      = implode( "','", $step_ids );

			$conversions_query = "SELECT trackid, count(ID) as conversions, SUM(wctotal) as revenue, cid FROM {$wpdb->prefix}bwfan_conversions GROUP BY trackid,cid";
			$query             = "SELECT SUM(if(con.open>0,1,0)) AS open_count,(SUM(IF(con.open>0, 1, 0))/COUNT(con.ID)) * 100 as open_rate ,SUM(IF(con.c_status=2, 1, 0)) as sent,SUM(if(con.click>0,1,0)) AS click_count,(SUM(IF(con.click>0, 1, 0))/COUNT(con.ID)) * 100 as click_rate,  SUM(conv.conversions) as conversions, SUM(conv.revenue) as revenue, COUNT(DISTINCT con.cid) as contacts_count  FROM {$table} AS con LEFT JOIN ({$conversions_query}) as conv ON con.ID = conv.trackid WHERE 1=1 AND con.type = " . BWFAN_Email_Conversations::$TYPE_AUTOMATION . " AND con.sid IN('$ids') AND con.c_status = 2 ";
			if ( ! empty( $oid ) ) {
				$query .= " AND con.oid IN ($oid) ";
			}
			/** Add query for get data after date */
			if ( ! empty( $after_date ) && empty( $end_date ) ) {
				$query .= " AND con.created_at > '$after_date'";
			}

			if ( ! empty( $after_date ) && ! empty( $end_date ) ) {
				$query .= " AND ( con.created_at > '$after_date' AND con.created_at < '$end_date' ) ";
			}

			$results                  = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$results['unsubscribers'] = self::get_automation_unsubscribers( $oid, $step_ids );

			return $results;
		}

		public static function get_automation_unsubscribers( $aid, $step_ids = [] ) {
			global $wpdb;
			$query = "SELECT COUNT(*) FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE `automation_id`=%d AND `c_type`=1";
			$args  = [ $aid ];

			if ( count( $step_ids ) > 0 ) {
				$placeholders = array_fill( 0, count( $step_ids ), '%d' );
				$placeholders = implode( ', ', $placeholders );
				$query        .= " AND `sid` IN($placeholders) ";
				$args         = array_merge( $args, $step_ids );
			}

			$query = $wpdb->prepare( $query, $args );

			return absint( $wpdb->get_var( $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_contact_engagements( $aid, $cid, $start_date, $last_date ) {
			global $wpdb;
			$table = self::_table();
			$query = $wpdb->prepare( "SELECT ID FROM {$table} WHERE cid = %d AND oid = %d AND type = 1 AND `created_at` BETWEEN %s AND %s", $cid, $aid, $start_date, $last_date );

			return $wpdb->get_col( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function delete_contact_engagements( $ids ) {
			if ( empty( $ids ) ) {
				return;
			}

			global $wpdb;
			$table = self::_table();

			$placeholders = array_fill( 0, count( $ids ), '%d' );
			$placeholders = implode( ', ', $placeholders );

			$query = $wpdb->prepare( "DELETE FROM {$table} WHERE ID IN ($placeholders)", $ids );

			return $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		public static function get_engagements_tid( $aid, $sids ) {
			global $wpdb;
			if ( ! is_array( $sids ) || empty( $sids ) ) {
				return [];
			}

			$placeholders = array_fill( 0, count( $sids ), '%d' );
			$placeholders = implode( ', ', $placeholders );
			$args         = [ $aid, 1 ];
			$args         = array_merge( $args, $sids );
			$query        = "SELECT `sid`, `tid` FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `oid` = %d AND `type` = %d AND `sid` IN ($placeholders)";

			return $wpdb->get_results( $wpdb->prepare( $query, $args ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}
}