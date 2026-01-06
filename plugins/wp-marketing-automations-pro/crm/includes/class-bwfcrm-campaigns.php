<?php
/**
 * Campaigns Controller Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BWFCRM_Campaigns
 */
#[AllowDynamicProperties]
class BWFCRM_Campaigns {
	public static $CAMPAIGN_DRAFT = 1;
	public static $CAMPAIGN_SCHEDULED = 2;
	public static $CAMPAIGN_RUNNING = 3;
	public static $CAMPAIGN_COMPLETED = 4;
	public static $CAMPAIGN_HALT = 5;
	public static $CAMPAIGN_PAUSED = 6;
	public static $CAMPAIGN_CANCELLED = 7;

	public static $CAMPAIGN_EMAIL = 1;
	public static $CAMPAIGN_SMS = 2;
	public static $CAMPAIGN_WHATSAPP = 3;

	private static $ins = null;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	/** @var BWFCRM_Broadcast_Processing */
	private $broadcast_as_ins = null;

	public function __construct() {
		include_once __DIR__ . '/class-bwfcrm-broadcast-processing.php';
		$this->broadcast_as_ins = BWFCRM_Broadcast_Processing::get_instance();
	}

	public function get_campaign_open_click_analytics( $oid ) {
		$start_date = BWFAN_Model_Engagement_Tracking::get_first_conversation_date( absint( $oid ), BWFAN_Email_Conversations::$TYPE_CAMPAIGN );
		if ( empty( $start_date ) ) {
			$start_date = new DateTime();
		} else {
			$start_date = new DateTime( $start_date );
		}

		$start_date->setTimezone( wp_timezone() );
		$end_date = clone $start_date;
		$end_date->add( new DateInterval( 'P4D' ) );

		/* Add current hour interval */
		$current_date = new DateTime( 'now' );
		$current_date->setTimezone( wp_timezone() );
		$current_date->add( new DateInterval( 'PT1H' ) );
		if ( $current_date < $end_date ) {
			$end_date = $current_date;
		}

		$intervals      = $this->intervals_between( $start_date->format( 'Y-m-d H:i:s' ), $end_date->format( 'Y-m-d H:i:s' ) );
		$time_intervals = empty( $intervals ) ? [] : array_column( $intervals, 'time_interval' );
		$data           = BWFAN_Model_Broadcast::get_interaction_stats_by_date_range( $time_intervals, $oid );

		$final_data = array();
		foreach ( $intervals as $interval ) {
			$opens        = empty( $data['open'] ) || ! is_array( $data['open'] ) || ! isset( $data['open'][0][ $interval['time_interval'] ] ) ? 0 : $data['open'][0][ $interval['time_interval'] ];
			$clicks       = empty( $data['click'] ) || ! is_array( $data['click'] ) || ! isset( $data['click'][0][ $interval['time_interval'] ] ) ? 0 : $data['click'][0][ $interval['time_interval'] ];
			$final_data[] = $this->get_interval_array( $interval, $opens, $clicks );
		}

		return $final_data;
	}

	public function get_interval_array( $interval, $opens, $clicks ) {
		$interval_data                   = array();
		$interval_data['interval']       = $interval['time_interval'];
		$interval_data['start_date']     = $interval['start_date'];
		$interval_data['date_start_gmt'] = $this->convert_local_datetime_to_gmt( $interval['start_date'] )->format( 'Y-m-d H:i:s' );
		$interval_data['end_date']       = $interval['end_date'];
		$interval_data['date_end_gmt']   = $this->convert_local_datetime_to_gmt( $interval['end_date'] )->format( 'Y-m-d H:i:s' );

		$interval_data['subtotals'] = array(
			'opens'    => absint( $opens ),
			'clicks'   => absint( $clicks ),
			'segments' => array(),
		);

		return $interval_data;
	}

	public function convert_local_datetime_to_gmt( $datetime_string ) {
		$datetime = new DateTime( $datetime_string, new \DateTimeZone( wp_timezone_string() ) );
		$datetime->setTimezone( new DateTimeZone( 'GMT' ) );

		return $datetime;
	}

	public function intervals_between( $start, $end ) {
		$interval_type = 'PT60M';
		$format        = 'Y-m-d H';
		$result        = array();

		// Variable that store the date interval
		// of period 1 day
		$period  = new DateInterval( $interval_type );
		$realEnd = new DateTime( $end );
		$period  = new DatePeriod( new DateTime( $start ), $period, $realEnd );
		$count   = iterator_count( $period );

		foreach ( $period as $date ) {
			if ( $count >= 1 ) {

				$new_interval                  = array();
				$new_interval['start_date']    = $date->format( 'Y-m-d H:i:s' );
				$new_interval['end_date']      = $date->format( 'Y-m-d 23:59:59' );
				$new_interval['time_interval'] = $date->format( $format );

				$result[] = $new_interval;
			}
			$count --;
		}

		return $result;
	}

	public function get_conversions( $campaign_id, $offset = 0, $limit = 25 ) {
		if ( empty( $campaign_id ) ) {
			return array(
				'conversions' => array(),
				'total'       => 0,
			);
		}

		return BWFAN_Model_Conversions::get_conversions_by_source_type( $campaign_id, BWFAN_Email_Conversations::$TYPE_CAMPAIGN, $limit, $offset );
	}

	/**
	 * @return mixed
	 */
	public static function get_top_broadcast( $type = 1 ) {
		$broadcasts = BWFAN_Model_Broadcast::get_top_broadcast( $type );

		$top_broadcast['top_broadcast'] = $broadcasts;

		return $top_broadcast;
	}

	public function save_editor_content( $broadcast_id, $content_number, $design, $html ) {
		$data = BWFAN_Model_Broadcast::get_broadcast_data( $broadcast_id );

		$editor = array(
			'body'   => $html,
			'design' => $design,
		);

		$content_data = ! empty( $data ) && isset( $data['content'] ) && ! empty( $data['content'] ) ? $data['content'] : array();
		if ( empty( $content_data ) || ! is_array( $content_data ) ) {
			$content_data[]['editor']                = $editor;
			$content_data[ $content_number ]['type'] = 'editor';
		} else {
			$content_number                            = absint( $content_number );
			$content_data[ $content_number ]['editor'] = $editor;
			$content_data[ $content_number ]['type']   = 'editor';
		}

		$data            = ! is_array( $data ) ? array() : $data;
		$data['content'] = $content_data;

		BWFAN_Model_Broadcast::update_broadcast_data( $broadcast_id, $data );

		return true;
	}

	public function get_editor_design( $broadcast_id, $content_number ) {
		$content    = $this->get_editor_content( $broadcast_id, $content_number, true );
		$merge_tags = $this->get_merge_tags_for_editor();
		if ( empty( $content ) ) {
			return array(
				'design'     => '',
				'merge_tags' => $merge_tags,
				'subject'    => '',
			);
		}

		$editor_content = ! isset( $content['content'] ) || ! is_array( $content['content'] ) ? array() : $content['content'];
		$subject        = ! isset( $content['subject'] ) ? '' : $content['subject'];
		if ( ! is_array( $editor_content ) || ! isset( $editor_content['design'] ) || empty( $editor_content['design'] ) ) {
			return array(
				'design'     => '',
				'merge_tags' => $merge_tags,
				'subject'    => $subject,
			);
		}

		return array(
			'design'     => $editor_content['design'],
			'merge_tags' => $merge_tags,
			'subject'    => $subject,
		);
	}

	public function get_editor_content( $broadcast_id, $content_number, $get_subject = false ) {
		if ( empty( $content_number ) && 0 !== absint( $content_number ) ) {
			return false;
		}

		$data = BWFAN_Model_Broadcast::get_broadcast_data( $broadcast_id );
		if ( ! is_array( $data ) || empty( $data['content'] ) || ! is_array( $data['content'] ) || ! isset( $data['content'][ $content_number ] ) ) {
			return false;
		}

		$content = $data['content'][ $content_number ];
		if ( ! is_array( $content ) ) {
			return false;
		}

		$editor_content = empty( $content['editor'] ) || ! is_array( $content['editor'] ) ? array() : $content['editor'];
		if ( false === $get_subject ) {
			return $editor_content;
		}

		$subject = isset( $content['subject'] ) && ! empty( $content['subject'] ) ? $content['subject'] : '';

		return array(
			'subject' => $subject,
			'content' => $editor_content,
		);
	}

	public function get_merge_tags_for_editor() {
		$tags   = BWFCRM_Core()->merge_tags->get_registered_grouped_tags( true );
		$return = array();
		foreach ( $tags as $slug => $m_tags ) {
			$group_tags = array();
			foreach ( $m_tags as $tag_slug => $tag_name ) {
				$group_tags[ sanitize_title( $tag_slug ) ] = array(
					'name'  => $tag_name,
					'value' => '{{' . $tag_slug . '}}',
				);
			}

			$group_name = '';
			switch ( $slug ) {
				case 'contact':
					$group_name = 'Contact';
					break;
				case 'contact_fields':
					$group_name = 'Fields';
					break;
				case 'general':
					$group_name = 'General';
					break;
			}

			$return[ $slug ] = array(
				'name'      => $group_name,
				'mergeTags' => $group_tags,
			);
		}

		return $return;
	}

	public function save_broadcast_content( $broadcast_id, $content ) {
		if ( empty( $broadcast_id ) || empty( $content ) || ! is_array( $content ) ) {
			return BWFCRM_Common::crm_error( __( 'Broadcast ID or Content is not valid / empty', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data = BWFAN_Model_Broadcast::get_broadcast_data( absint( $broadcast_id ) );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return BWFCRM_Common::crm_error( __( 'Broadcast not found OR Invalid Broadcast Data', 'wp-marketing-automations-pro' ), null, 400 );
		}

		$data['content'] = ! empty( $content ) ? self::bwf_validate_anchor_tag( $content ) : array();

		BWFAN_Model_Broadcast::update_broadcast_data( absint( $broadcast_id ), $data );

		return true;
	}

	/**
	 * Remove undefined anchor tags
	 *
	 * @param $content
	 *
	 * @return array
	 */
	public static function bwf_validate_anchor_tag( $content ) {
		$content_arr = [];
		foreach ( $content as $data ) {
			if ( isset( $data['editor'] ) && isset( $data['editor']['body'] ) ) {
				$data['editor']['body'] = preg_replace_callback( '%(<a href="undefined".*?>)+?\s*(?P<link>\S+)\s*+(<\/a.*?>)%is', function ( $matches ) {
					$string = $matches[0];
					if ( isset( $matches['link'] ) && wp_http_validate_url( $matches['link'] ) ) {
						$string = preg_replace( '/href="undefined"/i', 'href="' . $matches['link'] . '"', $string );
					} else {
						$string = preg_replace( '/href="undefined"/i', 'href="#"', $string );
					}

					return $string;
				}, $data['editor']['body'] );
			}
			$content_arr[] = $data;
		}

		return $content_arr;
	}

	public function maybe_daily_limit_reached( $type = 1 ) {
		return $this->broadcast_as_ins->maybe_daily_limit_reached( $type );
	}

	public function get_daily_limit_status_array() {
		return $this->broadcast_as_ins->get_daily_limit_status_array();
	}

	public function process_single_scheduled_broadcasts( $campaign_data ) {
		$this->broadcast_as_ins->process_single_scheduled_broadcasts( $campaign_data );
	}

	public function get_unopen_broadcast_contacts( $broadcast_id, $args = array(), $return_type = ARRAY_N ) {
		global $wpdb;

		/** Pagination Query */
		$limit            = isset( $args['limit'] ) ? absint( $args['limit'] ) : 0;
		$offset           = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
		$pagination_query = '';
		if ( ! empty( $limit ) ) {
			$pagination_query = "LIMIT $offset, $limit";
		}

		/** Order By Query */
		$order          = isset( $args['order'] ) && ! empty( $args['order'] ) ? $args['order'] : 'ASC';
		$order_by_query = "ORDER BY cid $order";

		/** END ID Query */
		$end_id       = isset( $args['end_id'] ) ? absint( $args['end_id'] ) : 0;
		$end_id_query = '';
		if ( ! empty( $end_id ) ) {
			$end_id_query = "AND et.cid < $end_id";
		}

		/** Exclude Contact IDs */
		$exclude       = isset( $args['exclude_ids'] ) && is_array( $args['exclude_ids'] ) ? implode( ',', $args['exclude_ids'] ) : array();
		$exclude_query = '';
		if ( ! empty( $exclude ) ) {
			$exclude_query = "AND et.cid NOT IN ($exclude)";
		}
		/** Get parent broadcast's data */
		$broadcast_data    = BWFAN_Model_Broadcast::get_broadcast_data( $broadcast_id );
		$exclude_unsubs    = isset( $broadcast_data['is_promotional'] ) && 1 === intval( $broadcast_data['is_promotional'] );
		$unsubscribe_query = ( true === $exclude_unsubs ) ? BWFCRM_Model_Contact::_get_unsubscribers_query( [], true ) : '';

		/** Engagement Query for Contact IDs */
		$sql  = $wpdb->prepare( "SELECT et.cid from {$wpdb->prefix}bwfan_engagement_tracking AS et JOIN {$wpdb->prefix}bwf_contact AS c ON et.cid=c.id  WHERE et.type=2 AND et.oid=%d AND (et.cid!='' OR et.cid IS NOT NULL) AND et.open=0 $end_id_query $exclude_query $unsubscribe_query $order_by_query $pagination_query", $broadcast_id );
		$cids = $wpdb->get_results( $sql, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $cids ) ) {
			return array(
				'contacts'    => array(),
				'total_count' => 0,
			);
		}

		/** Total Contacts */
		$sql          = $wpdb->prepare( "SELECT count(et.cid) from {$wpdb->prefix}bwfan_engagement_tracking AS et JOIN {$wpdb->prefix}bwf_contact AS c ON et.cid=c.id WHERE et.type=2 AND et.oid=%d AND (et.cid!='' OR et.cid IS NOT NULL) AND et.open=0 $end_id_query $unsubscribe_query $exclude_query", $broadcast_id );
		$total_counts = $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		/** Contact Query */
		$cids               = array_column( $cids, 'cid' );
		$contact_query_args = array(
			'customer_data'  => true,
			'include_ids'    => $cids,
			'grab_totals'    => isset( $args['grab_totals'] ) && $args['grab_totals'],
			'only_count'     => isset( $args['only_count'] ) && $args['only_count'],
			'exclude_unsubs' => $exclude_unsubs,

		);
		$filters            = isset( $args['filters'] ) ? $args['filters'] : array();
		$contacts           = BWFCRM_Contact::get_contacts( '', 0, $limit, $filters, $contact_query_args, $return_type );
		$contacts           = is_array( $contacts ) && ! empty( $contacts['contacts'] ) ? $contacts['contacts'] : [];

		return array(
			'contacts'    => $contacts,
			'total_count' => empty( $contacts ) ? 0 : $total_counts,
		);
	}

	public function get_broadcast_lookup( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'ids'    => array(),
			'type'   => 0,
			'limit'  => 25,
			'offset' => 0,
			'search' => '',
		) );

		$pagination_query = '';
		if ( ! empty( $args['offset'] ) || ! empty( $args['limit'] ) ) {
			$pagination_query = 'LIMIT ' . $args['offset'] . ', ' . $args['limit'];
		}

		$ids         = $args['ids'];
		$where_query = 'WHERE 1 = 1 ';
		if ( is_array( $ids ) && ! empty( $ids ) ) {
			$ids         = implode( ',', $ids );
			$where_query .= "AND id IN ($ids)";
		}

		$type = $args['type'];
		if ( ! empty( $type ) ) {
			$where_query .= "AND type = $type";
		}

		$db_broadcasts = $wpdb->get_results( "SELECT id, title from {$wpdb->prefix}bwfan_broadcast $where_query $pagination_query", ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$broadcasts    = array();
		foreach ( $db_broadcasts as $broadcast ) {
			$broadcasts[ absint( $broadcast['id'] ) ] = array(
				'id'    => absint( $broadcast['id'] ),
				'title' => $broadcast['title'],
			);
		}

		return $broadcasts;
	}

	/**
	 * If broadcast is in scheduled and paused status then get updated contact's count
	 *
	 * @param $broadcast
	 *
	 * @return array|mixed
	 */
	public static function get_broadcast_updated_contact_count( $broadcast ) {
		/** If broadcast status is not scheduled and paused */
		$allowed_status = [ BWFCRM_Broadcast_Processing::$CAMPAIGN_SCHEDULED, BWFCRM_Broadcast_Processing::$CAMPAIGN_PAUSED ];
		if ( ! in_array( intval( $broadcast['status'] ), $allowed_status, true ) ) {
			return $broadcast['count'];
		}

		$data            = isset( $broadcast['data'] ) && isset( $broadcast['data']['filters'] ) ? $broadcast['data'] : BWFAN_Model_Broadcast::get_broadcast_data( $broadcast['id'] );
		$filters         = isset( $data['filters'] ) ? $data['filters'] : [];
		$additional_info = [
			'grab_totals'   => true,
			'only_count'    => true,
			'customer_data' => false,
		];
		if ( isset( $data['includeSoftBounce'] ) ) {
			$additional_info['include_soft_bounce'] = $data['includeSoftBounce'];
		}
		if ( isset( $data['includeUnverified'] ) ) {
			$additional_info['include_unverified'] = $data['includeUnverified'];
		}
		if ( empty( $data['is_promotional'] ) ) {
			$additional_info['include_unsubscribe'] = true;
		}
		$filters['status_is'] = 1;

		$count = BWFCRM_Contact::get_contacts( '', 0, 0, $filters, $additional_info );

		return isset( $count['total_count'] ) ? $count['total_count'] : $broadcast['count'];
	}

	/**
	 * Get sent engagements of broadcast
	 *
	 * @param int $broadcast_id
	 * @param int $limit
	 * @param string $date last sent date processed
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_broadcast_engagements( $broadcast_id, $limit = 100, $date = 0 ) {
		global $wpdb;

		$placeholder = empty( $date ) ? '%d': '%s';

		$query = $wpdb->prepare( "SELECT `created_at`, GROUP_CONCAT(`cid` SEPARATOR ',') AS cids FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `created_at` > $placeholder AND `oid` = %d AND `type` = %d AND `c_status` = %d GROUP BY `created_at` ORDER BY `created_at` ASC LIMIT %d", $date, intval( $broadcast_id ), 2, 2, $limit );

		return $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}
}

if ( class_exists( 'BWFCRM_Campaigns' ) ) {
	BWFCRM_Core::register( 'campaigns', 'BWFCRM_Campaigns' );
}
