<?php
/**
 * CRM campaign model call.
 *
 * @package Model
 */

/**
 * Campaign Class
 */
class BWFAN_Model_Broadcast extends BWFAN_Model {

	/**
	 * Campaign table fields
	 *
	 * @var array
	 */
	protected static $table_fields = array(
		'id',
		'title',
		'description',
		'type',
		'status',
		'count',
		'processed',
		'created_by',
		'offset',
		'modified_by',
		'execution_time',
		'created_at',
		'data',
	);

	/**
	 * Table order option
	 *
	 * @var array
	 */
	protected static $order_option = array(
		'ASC',
		'DESC',
	);

	/**
	 * Get all campaigns
	 *
	 * @param string $search search string.
	 * @param int $limit data limit.
	 * @param int $offset data offset.
	 * @param string $order data order.
	 * @param string $type broadcast type.
	 * @param string $status broadcast status.
	 *
	 * @return array
	 */
	public static function get_campaigns( $search, $limit, $offset, $order, $type = '', $status = 'all' ) {
		$broadcast_table = self::_table();

		/** data not pulled as for large data API calls sometimes fail */
		$broadcast_query = "SELECT `id`, `title`, `type`, `execution_time`, `status`, `created_at`, `count` FROM $broadcast_table WHERE 1=1 AND `parent` = 0";
		$count_filter    = array(
			'parent' => array(
				'type'     => '%d',
				'value'    => 0,
				'operator' => 'LIKE',
			)
		);
		if ( ! empty( $search ) ) {
			$broadcast_query .= " AND title LIKE '%" . esc_sql( $search ) . "%'";
			$count_filter    = array(
				'title' => array(
					'type'     => '%s',
					'value'    => "%$search%",
					'operator' => 'LIKE',
				),
			);
		}

		if ( ! empty( $type ) ) {
			$broadcast_query      .= " AND type= $type";
			$count_filter['type'] = array(
				'type'     => '%d',
				'value'    => $type,
				'operator' => 'LIKE',
			);
		}

		if ( $status !== 'all' ) {
			$broadcast_status = '';
			switch ( $status ) {
				case 'scheduled':
					$broadcast_status = [ 2 ];
					break;
				case 'ongoing':
					$broadcast_status = [ 3 ];
					break;
				case 'completed':
					$broadcast_status = [ 4 ];
					break;
				case 'paused':
					$broadcast_status = [ 5, 6 ];
					break;
				case 'cancelled':
					$broadcast_status = [ 7 ];
					break;
			}

			if ( ! empty( $broadcast_status ) ) {
				$count_filter['status'] = array(
					'type'     => '%d',
					'value'    => $broadcast_status,
					'operator' => 'IN',
				);

				$broadcast_status = '(' . implode( ',', $broadcast_status ) . ')';
				$broadcast_query  .= " AND status IN $broadcast_status";
			}
		}

		$broadcast_query .= " ORDER BY id $order LIMIT $offset,$limit";

		$broadcasts = self::get_results( $broadcast_query );
		if ( empty( $search ) && ! empty( $broadcasts ) ) {
			$broadcasts_ids = array_map( function ( $broadcast ) {
				return $broadcast['id'];
			}, $broadcasts );

			$broadcasts = self::include_child_broadcast( $broadcasts_ids, $broadcasts );
		}
		$total = self::count( $count_filter );

		$message = __( 'Got all broadcasts', 'wp-marketing-automations-pro' );
		if ( empty( $broadcasts ) ) {
			$message = __( 'No Broadcast found', 'wp-marketing-automations-pro' );
		}

		return array(
			'data'    => array(
				'campaigns' => self::get_formatted_campaign_data( $broadcasts ),
			),
			'message' => $message,
			'total'   => $total,
		);
	}

	static function count( $data = array() ) {
		global $wpdb;

		$sql        = 'SELECT COUNT(*) AS `count` FROM ' . self::_table() . ' WHERE 1=1';
		$sql_params = [];
		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach ( $data as $key => $val ) {
				if ( 'IN' === $val['operator'] ) {
					$placeholder = array_fill( 0, count( $val['value'] ), $val['type'] );
					$placeholder = implode( ", ", $placeholder );

					$sql .= " AND `{$key}` {$val['operator']} ({$placeholder})";

					$sql_params = array_merge( $sql_params, $val['value'] );
				} else {
					$sql          .= " AND `{$key}` {$val['operator']} {$val['type']}";
					$sql_params[] = $val['value'];
				}
			}

			if ( ! empty( $sql_params ) ) {
				$sql = $wpdb->prepare( $sql, $sql_params ); // WPCS: unprepared SQL OK
			}
		}

		return $wpdb->get_var( $sql ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function include_open_click_rate( $broadcasts_ids, $broadcasts, $only_engagement_data = false ) {
		global $wpdb;
		$engagement_table  = $wpdb->prefix . 'bwfan_engagement_tracking ';
		$engagement_fields = 'con.`oid`, (SUM(IF(con.`open`>0, 1, 0))/COUNT(con.`ID`)) * 100 AS `open_rate`, SUM(IF(con.`c_status`=2, 1, 0)) AS `sent`, (SUM(IF(con.`click`>0, 1, 0))/COUNT(con.`ID`)) * 100 AS `click_rate`';

		$broadcasts_ids = implode( ',', $broadcasts_ids );

		$engagement_query = "SELECT $engagement_fields FROM $engagement_table AS con WHERE con.`oid` IN ($broadcasts_ids) AND con.`type` = 2 GROUP BY con.`oid`";
		$engagements      = self::get_results( $engagement_query );

		if ( true === $only_engagement_data ) {
			return $engagements;
		}

		foreach ( $engagements as $engagement ) {
			$broadcasts = array_map( function ( $broadcast ) use ( $engagement ) {
				if ( $broadcast['id'] === $engagement['oid'] ) {
					unset( $engagement['oid'] );

					return array_merge( $broadcast, $engagement );
				}

				return $broadcast;
			}, $broadcasts );
		}

		return $broadcasts;
	}

	public static function include_child_broadcast( $broadcasts_ids, $broadcasts ) {
		$child_broadcasts = self::get_broadcast_child( $broadcasts_ids );

		if ( empty( $child_broadcasts ) ) {
			return $broadcasts;
		}

		$child = array();
		foreach ( $child_broadcasts as $child_broadcast ) {
			$child[ absint( $child_broadcast['parent'] ) ][] = $child_broadcast;
		}
		$broadcasts = array_map( function ( $broadcast ) use ( $child ) {
			$broadcast_id = absint( $broadcast['id'] );
			if ( isset( $child[ $broadcast_id ] ) ) {
				$child_broadcasts      = array_map( function ( $child_broadcast ) {
					/** Get grand child */
					$grand_child = self::get_broadcast_child( $child_broadcast['id'] );

					if ( ! empty( $grand_child ) ) {
						$child_broadcast['children'] = $grand_child;
					}

					return $child_broadcast;

				}, $child[ $broadcast_id ] );
				$broadcast['children'] = $child_broadcasts;
			}

			return $broadcast;
		}, $broadcasts );

		return $broadcasts;
	}

	public static function get_broadcast_child( $broadcast_ids, $return_ids = false ) {
		$broadcast_table = self::_table();
		$broadcast_query = "SELECT `id`, `title`, `type`, `data`, `created_at`, `execution_time`, `status`, `count`, `parent` FROM $broadcast_table WHERE 1=1";
		if ( is_array( $broadcast_ids ) ) {
			$broadcast_ids   = implode( ',', $broadcast_ids );
			$broadcast_query .= " AND `parent` in ($broadcast_ids)";
		} elseif ( is_numeric( $broadcast_ids ) ) {
			$broadcast_query .= " AND `parent` = $broadcast_ids";
		}
		$broadcast_query  .= ' ORDER BY `id`';
		$child_broadcasts = self::get_results( $broadcast_query );
		$broadcasts_ids   = array_map( function ( $child_broadcast ) {
			return $child_broadcast['id'];
		}, $child_broadcasts );
		if ( empty( $broadcasts_ids ) ) {
			return array();
		}

		if ( true === $return_ids ) {
			return $broadcasts_ids;
		}

		$child_broadcasts = self::include_conversion_into_campaigns( $child_broadcasts );

		return self::get_formatted_campaign_data( $child_broadcasts );
	}

	public static function include_conversion_into_campaigns( $campaigns, $only_conversion_data = false ) {
		global $wpdb;
		if ( ! is_array( $campaigns ) || 0 === count( $campaigns ) ) {
			return $campaigns;
		}

		$ids = array_map( function ( $campaign ) {
			return isset( $campaign['id'] ) ? absint( $campaign['id'] ) : false;
		}, $campaigns );

		$ids = implode( ',', array_filter( $ids ) );

		$query       = "SELECT `oid`, count(`ID`) AS `conversions`, SUM(`wctotal`) AS `revenue` FROM {$wpdb->prefix}bwfan_conversions WHERE `oid` IN ($ids) AND `otype` = 2 GROUP BY `oid`";
		$conversions = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( true === $only_conversion_data ) {
			return $conversions;
		}
		$campaigns = array_map( function ( $campaign ) use ( $conversions ) {
			if ( ! isset( $campaign['id'] ) ) {
				return false;
			}

			foreach ( $conversions as $conversion ) {
				if ( absint( $campaign['id'] ) === absint( $conversion['oid'] ) ) {
					return array_replace( $campaign, $conversion );
				}
			}

			return $campaign;
		}, $campaigns );

		return array_filter( $campaigns );
	}

	/**
	 * Get campaign details
	 *
	 * @param $campaign_id
	 * @param false $steps
	 * @param false $ab_mode
	 *
	 * @return array
	 */
	public static function get_campaign( $campaign_id, $steps = false, $ab_mode = false ) {
		$response = array(
			'data'    => array(),
			'message' => __( 'No broadcast found for the given ID.', 'wp-marketing-automations-pro' ),
			'status'  => 404,
		);
		global $wpdb;

		$campaign = self::get_specific_rows( 'id', $campaign_id );
		if ( ! is_array( $campaign ) || ! isset( $campaign[0] ) || ! isset( $campaign[0]['id'] ) || absint( $campaign[0]['id'] ) !== absint( $campaign_id ) ) {
			return $response;
		}

		/** Check if status is draft or scheduled */
		if ( in_array( absint( $campaign[0]['status'] ), array( BWFCRM_Campaigns::$CAMPAIGN_DRAFT, BWFCRM_Campaigns::$CAMPAIGN_SCHEDULED ) ) ) {
			$data                = self::get_formatted_campaign_data( $campaign )[0];
			$response['data']    = $data;
			$response['status']  = 200;
			$response['message'] = __( 'Broadcast loaded', 'wp-marketing-automations-pro' );

			return $response;
		}

		/** Get Broadcast */
		$broadcast = array();
		if ( ! $ab_mode ) {
			$broadcast_table = self::_table();
			$query           = "SELECT * from {$broadcast_table} WHERE `id` = '$campaign_id' LIMIT 0, 1";
			$broadcast       = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		/** Check if the broadcast is grandchild */
		if ( isset( $broadcast['parent'] ) && ! empty( absint( $broadcast['parent'] ) ) ) {
			$parent = absint( $broadcast['parent'] );
			$parent = self::get( $parent );
			if ( is_array( $parent ) && isset( $parent['parent'] ) && ! empty( absint( $parent['parent'] ) ) ) {
				$broadcast['is_unopen_grandchild'] = true;
			}
		}

		/** Get broadcast's engagement Data grouped by TID (Template ID) */
		$engagement_table = $wpdb->prefix . 'bwfan_engagement_tracking';
		$columns          = 'SUM(`open`) AS `open_count`, (SUM(IF(`open`>0, 1, 0))/COUNT(`ID`)) * 100 AS `open_rate`, SUM(`click`) AS `click_count`, (SUM(IF(`click`>0, 1, 0))/COUNT(`ID`)) * 100 AS `click_rate`, SUM(IF(`c_status`=2, 1, 0)) AS `sent`, `tid`';
		$group_sql        = $ab_mode ? 'GROUP BY `tid`' : '';
		$query            = "SELECT $columns FROM {$engagement_table} WHERE `oid` = '$campaign_id' AND `type` = 2 $group_sql";
		$engagements      = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		/** Get Conversions Grouped by TID */
		$conversions = array();
		if ( ! empty( $engagements ) ) {
			$campaign = array_map( function ( $data ) {
				$data['data'] = json_decode( $data['data'], true );

				return $data;
			}, $campaign );

			$template_ids = [];
			if ( isset( $campaign[0]['data']['content'] ) ) {
				foreach ( $campaign[0]['data']['content'] as $content ) {
					if ( isset( $content['template_id'] ) ) {
						$template_ids[] = $content['template_id'];
					}
				}
			}
			$conversions = self::get_conversions_by_tids( $campaign_id, $ab_mode, $template_ids );
		}

		/** Combine Engagements and Conversion data by TID */
		$final_broadcast = array();
		$conversion_data = $conversions;
		if ( ! empty( $engagements ) ) {
			foreach ( $engagements as $engagement ) {
				$tid             = absint( $engagement['tid'] );
				$broadcast['id'] = $campaign_id;
				$engagement      = array_replace( $engagement, $broadcast );
				if ( isset( $conversions[ $tid ] ) && is_array( $conversions[ $tid ] ) ) {
					$conversion_data = $conversions[ $tid ];
				}
				$engagement        = array_replace( $engagement, $conversion_data );
				$final_broadcast[] = $engagement;
			}
		}

		if ( empty( $final_broadcast ) ) {
			$final_broadcast[] = $broadcast;
		}

		$campaign = $final_broadcast;
		if ( is_array( $campaign ) && count( $campaign ) > 0 && isset( $campaign[0]['id'] ) && ! empty( $campaign[0]['id'] ) ) {
			$campaign = self::get_formatted_campaign_data( $campaign );
			$campaign = $ab_mode ? $campaign : $campaign[0];
		} else {
			$campaign = false;
		}
		// handle broadcast notice data.
		$campaign_notice = [];
		if ( ! $ab_mode ) {
			$campaign_notice = self::get_campaign_notice_data( $campaign['status'], $campaign['data'] ?? [] );
		}

		if ( ! empty( $campaign ) ) {
			if ( ! empty( $campaign_notice ) ) {
				$campaign['notice_data'] = $campaign_notice;
			}

			$response['data']    = $campaign;
			$response['status']  = 200;
			$response['message'] = __( 'Broadcast loaded for ID: ', 'wp-marketing-automations-pro' ) . $campaign_id;
		}
		if ( $steps ) {
			$response['data']['step'] = 1;
		}

		return $response;
	}

	/**
	 * Get campaign notice data
	 *
	 * @param string $campaign_status campaign status.
	 * @param array $data campaign data.
	 *
	 * @return array
	 */
	public static function get_campaign_notice_data( $campaign_status = '', $data = [] ) {
		if ( empty( $campaign_status ) || empty( $data ) ) {
			return [];
		}
		$campaign_notice = [];
		if ( absint( $campaign_status ) === BWFCRM_Campaigns::$CAMPAIGN_HALT ) {
			$campaign_notice[] = array(
				'msg'    => sprintf( '%s %s', "<b>" . __( 'Broadcast Halted:', 'wp-marketing-automations-pro' ) . " </b>", isset( $campaign['data'] ) && isset( $data['halt_reason'] ) ? $data['halt_reason'] : __( 'Unknwon error occurred', 'wp-marketing-automations-pro' ) ),
				'isHtml' => true,
				'type'   => 'warning',
			);
		}

		if ( isset( $data['smart_send']['enable'] ) && 1 === intval( $data['smart_send']['enable'] ) && ( isset( $data['hold_broadcast'] ) && 1 === intval( $data['hold_broadcast'] ) ) ) {
			$value             = count( $data['content'] );
			$value             = ( 2 === intval( $value ) ) ? __( 'two', 'wp-marketing-automations-pro' ) : __( 'three', 'wp-marketing-automations-pro' );
			$message           = sprintf( __( 'We\'ve sent %s email variants to %s of your contacts. The version with the highest open rate will be sent to the rest.', 'wp-marketing-automations-pro' ), $value, intval( $data['smart_send']['percent'] ) . '%' );
			$campaign_notice[] = array(
				'msg'  => $message,
				'type' => 'info',
			);
		}
		if ( isset( $data['failed_cids'] ) && count( $data['failed_cids'] ) >= 20 ) {
			$failed_reason     = isset( $data['failed_reason'] ) ? implode( '<br/>', $data['failed_reason'] ) : '';
			$campaign_notice[] = array(
				'msg'    => sprintf( __( 'Broadcast has been paused because 20 consecutive emails failed to send through your email service provider. The error message received from the provider is: "%s". Please check the email logs, verify your SMTP plugin and email service configuration, and resolve any connection or authentication issues. Once resolved, you can resume the broadcast.', 'wp-marketing-automations-pro' ), $failed_reason ),
				'type'   => 'error',
				'isHtml' => true,
			);
		}

		return $campaign_notice;
	}

	/**
	 * Get conversions group by template id of broadcast
	 *
	 * @param $broadcast_id
	 * @param $ab_mode
	 * @param $tids
	 *
	 * @return array|int[]
	 */
	public static function get_conversions_by_tids( $broadcast_id, $ab_mode = true, $tids = [] ) {
		if ( empty( $tids ) ) {
			return [ 'conversions' => 0, 'revenue' => 0 ];
		}
		global $wpdb;
		$conversion_table = $wpdb->prefix . 'bwfan_conversions';
		$engagement_table = $wpdb->prefix . 'bwfan_engagement_tracking';

		$args        = [ $broadcast_id, 2 ];
		$placeholder = array_fill( 0, count( $tids ), '%d' );
		$placeholder = implode( ", ", $placeholder );
		$args        = array_merge( $args, $tids );

		$columns        = 'COUNT(con.ID) AS conversions, SUM(con.wctotal) AS `revenue`, et.`tid` ';
		$query          = $wpdb->prepare( "SELECT $columns FROM {$engagement_table} AS `et` LEFT JOIN {$conversion_table} AS `con` ON et.`ID` = con.`trackid` WHERE et.`oid` = %d AND et.`type` = %d AND et.`tid` IN ($placeholder) GROUP BY et.`tid`", $args );
		$converted_tids = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $converted_tids ) ) {
			return [];
		}

		$conversion_data  = array();
		$conversion_count = 0;
		$revenue          = 0;
		foreach ( $converted_tids as $conversions ) {
			$tid = $conversions['tid'];
			if ( false === $ab_mode ) {
				$conversion_count += isset( $conversions['conversions'] ) ? intval( $conversions['conversions'] ) : 0;
				$revenue          += isset( $conversions['revenue'] ) ? floatval( $conversions['revenue'] ) : 0;
				continue;
			}
			$conversion_data[ intval( $tid ) ] = $conversions;
		}

		if ( false === $ab_mode ) {
			return [
				'conversions' => $conversion_count,
				'revenue'     => $revenue
			];
		}

		return $conversion_data;
	}

	/**
	 * Get un-subscribers count of a broadcast campaign
	 *
	 * @param $campaign_id
	 *
	 * @return int
	 */
	public static function get_campaign_unsubscribers( $campaign_id ) {
		global $wpdb;
		$query = "SELECT COUNT(*) FROM {$wpdb->prefix}bwfan_message_unsubscribe WHERE `automation_id` = $campaign_id AND `c_type` = 2";

		return intval( $wpdb->get_var( $query ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Create a new campaign
	 *
	 * @param string $title campaign title.
	 * @param string $desc campaign description.
	 * @param int $type campaign type.
	 * @param int $created_by created user id.
	 *
	 * @return array
	 */
	public static function create_campaign( $title, $desc, $type, $created_by, $createUnopen = false ) {
		$status  = 404;
		$data    = array();
		$message = __( 'Unable to create broadcast.', 'wp-marketing-automations-pro' );

		$db_arr = array(
			'title'         => $title,
			'description'   => $desc,
			'created_by'    => $created_by,
			'type'          => $type,
			'modified_by'   => $created_by,
			'created_at'    => current_time( 'mysql', 1 ),
			'last_modified' => current_time( 'mysql', 1 ),
		);

		/** Create as Unopen Broadcast */
		if ( ! empty( $createUnopen ) ) {
			/** Set Parent ID of broadcast */
			$parent           = absint( $createUnopen );
			$db_arr['parent'] = $parent;

			$parent = self::get( $parent );
			if ( ! empty( $parent['data'] ) ) {
				$parent_data = json_decode( $parent['data'], true );

				/** Remove Unnecessary Parent data */
				if ( is_array( $parent_data ) ) {
					unset( $parent_data['exclude'] );
					unset( $parent_data['filters'] );
					unset( $parent_data['is_promotional'] );
					unset( $parent_data['halt_reason'] );

					unset( $parent_data['winner'] );
					if ( isset( $parent_data['smart_send_stat'] ) ) {
						unset( $parent_data['smart_send_stat'] );
					}
					unset( $parent_data['hold_broadcast'] );
					unset( $parent_data['smart_sending_done'] );
				}

				$db_arr['data'] = wp_json_encode( $parent_data );
			}
		}

		self::insert( $db_arr );
		$campaign_id = self::insert_id();

		if ( $campaign_id ) {
			$campaign = self::get_specific_rows( 'id', $campaign_id );
			$data     = self::get_formatted_campaign_data( $campaign )[0];
			$status   = 200;
			$message  = __( 'Broadcast created', 'wp-marketing-automations-pro' );
		}

		return array(
			'data'    => $data,
			'message' => $message,
			'status'  => $status,
		);
	}

	/**
	 * Delete a particular campaign
	 *
	 * @param int $campaign_id campaign Id.
	 *
	 * @return  array
	 */
	public static function delete_campaign( $campaign_id ) {
		$status  = 404;
		$data    = array();
		$message = __( 'Unable to delete the broadcast.', 'wp-marketing-automations-pro' );

		$campaign = self::delete( $campaign_id );

		if ( (bool) $campaign ) {
			$data    = array();
			$status  = 200;
			$message = __( 'Broadcast deleted', 'wp-marketing-automations-pro' );
			self::remove_parent_from_child( [ $campaign_id ] );
		}

		return array(
			'data'    => $data,
			'message' => $message,
			'status'  => $status,
		);
	}

	/**
	 * Update campaign
	 *
	 * @param int $campaign_id campaign id.
	 * @param int $step step id.
	 * @param array $data campaign data to update.
	 *
	 * @return array
	 */
	public static function update_campaign( $campaign_id, $step, $data ) {
		$status          = 404;
		$res_data        = array();
		$message         = __( 'Unable to update the broadcast.', 'wp-marketing-automations-pro' );
		$campaign        = self::get_specific_rows( 'id', $campaign_id );
		$current_user_id = get_current_user_id();

		/** checking if user not exists than return in broadcast update */
		if ( empty( $current_user_id ) ) {
			$message = __( 'Permission denied, no valid user available', 'wp-marketing-automations-pro' );

			return array(
				'data'    => $res_data,
				'status'  => $status,
				'message' => $message,
			);
		}

		if ( empty( $campaign ) ) {
			return array(
				'data'    => $res_data,
				'status'  => $status,
				'message' => $message,
			);
		}

		$campaign_data = isset( $campaign[0]['data'] ) && ! empty( $campaign[0]['data'] ) ? json_decode( $campaign[0]['data'], true ) : array();
		$error         = false;
		if ( 1 == $step ) {
			if ( empty( $data['title'] ) ) {
				$error   = true;
				$message = __( 'Broadcast name is required.', 'wp-marketing-automations-pro' );
			}
			if ( ! $error && ( intval( $data['type'] ) <= 0 || ! in_array( intval( $data['type'] ), array( 1, 2, 3 ), true ) ) ) {
				$error   = true;
				$message = __( 'Broadcast type is required.', 'wp-marketing-automations-pro' );
			}
			if ( ! $error ) {
				$campaign_data['is_promotional']    = $data['promotional'];
				$campaign_data['ab_type']           = $data['ab_type'];
				$campaign_data['exclude']           = $data['exclude'];
				$campaign_data['includeSoftBounce'] = isset( $data['includeSoftBounce'] ) ? $data['includeSoftBounce'] : false;
				$campaign_data['includeUnverified'] = isset( $data['includeUnverified'] ) ? $data['includeUnverified'] : false;
				self::update( array(
					'title'         => $data['title'],
					'type'          => $data['type'],
					'description'   => $data['description'],
					'modified_by'   => $current_user_id,
					'data'          => wp_json_encode( $campaign_data ),
					'last_modified' => current_time( 'mysql', 1 ),
				), array(
					'id' => $campaign_id,
				) );
			}
		}

		if ( 2 == $step ) {
			$campaign_data['filters'] = $data['filters'];
			$campaign_data['exclude'] = $data['exclude'];
			$total_count              = isset( $data['count'] ) ? $data['count'] : 0;

			self::update( array(
				'data'          => wp_json_encode( $campaign_data ),
				'count'         => $total_count,
				'modified_by'   => $current_user_id,
				'last_modified' => current_time( 'mysql', 1 ),
			), array(
				'id' => $campaign_id,
			) );

		}

		if ( 3 == $step ) {
			$campaign_data['content'] = isset( $data['content'] ) ? $data['content'] : array();

			/** Checking utm is enabled or not */
			$campaign_data['content'] = array_map( function ( $content ) {
				/** if utm is disabled then unset utm parameters */
				if ( empty( $content['utmEnabled'] ) ) {
					unset( $content['utm'] );
				}

				return $content;
			}, $campaign_data['content'] );

			$campaign_data['senders_name']  = isset( $data['senders_name'] ) ? $data['senders_name'] : '';
			$campaign_data['senders_email'] = isset( $data['senders_email'] ) ? $data['senders_email'] : '';
			$campaign_data['replyto']       = isset( $data['replyto'] ) ? $data['replyto'] : '';
			$campaign_data['smart_send']    = isset( $data['smart_send'] ) ? $data['smart_send'] : '';

			if ( ! $error ) {
				self::update( array(
					'data'          => wp_json_encode( $campaign_data ),
					'modified_by'   => $current_user_id,
					'last_modified' => current_time( 'mysql', 1 ),
				), array(
					'id' => $campaign_id,
				) );
			}
		}

		if ( 4 == $step ) {
			$schedule_time = isset( $data['schedule_time'] ) ? $data['schedule_time'] : '';

			try {
				$schedule_date = new DateTime( $schedule_time );
				self::update( array(
					'execution_time' => $schedule_date->format( 'Y-m-d H:i:s' ),
					'status'         => 2,
					'data'           => wp_json_encode( $campaign_data ),
					'modified_by'    => $current_user_id,
					'last_modified'  => date( 'Y-m-d H:i:s', time() - 6 ),
				), array(
					'id' => $campaign_id,
				) );

				/** Start the campaign if scheduled time is less than or equal to current time (i.e.: on Broadcast Now) */
				if ( strtotime( $schedule_time ) <= time() ) {
					$campaign_data = self::get( absint( $campaign_id ) );
					if ( ! is_array( $campaign_data ) || empty( $campaign_data ) ) {
						$error   = true;
						$message = __( 'Broadcast not found', 'wp-marketing-automations-pro' );
					} else {
						BWFCRM_Core()->campaigns->process_single_scheduled_broadcasts( $campaign_data );
						BWFCRM_Common::reschedule_broadcast_action();
					}
				} else {
					BWFAN_PRO_Common::validate_scheduled_recurring_actions();
				}
			} catch ( Exception $e ) {
				$error   = true;
				$message = $e->getMessage();
			}

		}

		if ( ! $error ) {
			$res_data = self::get_formatted_campaign_data( self::get_specific_rows( 'id', $campaign_id ) )[0];
			$status   = 200;
			$message  = __( 'Broadcast updated', 'wp-marketing-automations-pro' );
		}

		return array(
			'data'    => $res_data,
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Clone campaign
	 *
	 * @param int $campaign_id campaign id.
	 * @param int $created_by user id.
	 *
	 * @return array
	 */
	public static function clone_campaign( $campaign_id, $created_by, $send_to_unopen = false ) {
		$status   = 404;
		$data     = array();
		$message  = __( 'Broadcast details missing.', 'wp-marketing-automations-pro' );
		$campaign = self::get_specific_rows( 'id', $campaign_id );

		if ( ! empty( $campaign ) ) {
			$campaign      = $campaign[0];
			$clone_title   = true === $send_to_unopen ? $campaign['title'] . ' ' . __( '(Unopen Broadcast)', 'wp-marketing-automations-pro' ) : $campaign['title'] . ' ' . __( '(Copy)', 'wp-marketing-automations-pro' );
			$campaign_data = ! empty( $campaign['data'] ) ? json_decode( $campaign['data'], true ) : array();
			if ( is_array( $campaign_data ) ) {
				unset( $campaign_data['halt_reason'] );
				unset( $campaign_data['winner'] );
				unset( $campaign_data['hold_broadcast'] );
				unset( $campaign_data['smart_sending_done'] );
				unset( $campaign_data['includeSoftBounce'] );
				unset( $campaign_data['includeUnverified'] );
				if ( isset( $campaign_data['smart_send_stat'] ) ) {
					unset( $campaign_data['smart_send_stat'] );
				}
				if ( isset( $campaign_data['failed_cids'] ) ) {
					unset( $campaign_data['failed_cids'] );
				}
				if ( isset( $campaign_data['failed_reason'] ) ) {
					unset( $campaign_data['failed_reason'] );
				}
				if ( isset( $campaign_data['content'] ) ) {
					$content = $campaign_data['content'];
					foreach ( $content as $key => $value ) {
						if ( isset( $value['utm'] ) && isset( $value['utm']['name'] ) ) {
							$campaign_data['content'][ intval( $key ) ]['utm']['name'] = $clone_title;
						}
					}
				}
			}

			if ( true === $send_to_unopen ) {
				$campaign_data['parent'] = absint( $campaign_id );
			}

			$db_arr = array(
				'title'         => $clone_title,
				'description'   => $campaign['description'],
				'created_by'    => $created_by,
				'type'          => $campaign['type'],
				'parent'        => $campaign['parent'],
				'count'         => 0,
				'modified_by'   => $created_by,
				'data'          => wp_json_encode( $campaign_data ),
				'created_at'    => current_time( 'mysql', 1 ),
				'last_modified' => current_time( 'mysql', 1 ),
			);
			self::insert( $db_arr );
			$campaign_id = self::insert_id();

			if ( $campaign_id ) {
				$campaign = self::get_specific_rows( 'id', $campaign_id );
				$data     = self::get_formatted_campaign_data( $campaign )[0];
				$status   = 200;
				$message  = __( 'Broadcast cloned', 'wp-marketing-automations-pro' );
				if ( true === $send_to_unopen ) {
					$message = __( 'Broadcast created', 'wp-marketing-automations-pro' );
				}
			}
		}

		return array(
			'data'    => $data,
			'status'  => $status,
			'message' => $message,
		);
	}

	/**
	 * Returns formatted data
	 *
	 * @param array $data campaign data.
	 *
	 * @return array
	 */
	public static function get_formatted_campaign_data( $data ) {
		if ( empty( $data ) ) {
			return [];
		}
		$final_data = array();
		foreach ( $data as $campaign ) {
			if ( isset( $campaign['data'] ) && ! empty( $campaign['data'] ) ) {
				$campaign['data'] = json_decode( $campaign['data'], true );
			}

			/** Setting default values 0 if not found */
			$campaign['click_count'] = ( isset( $campaign['click_count'] ) && absint( $campaign['click_count'] ) > 0 ) ? $campaign['click_count'] : 0;
			$campaign['click_rate']  = ( isset( $campaign['click_rate'] ) && floatval( $campaign['click_rate'] ) > 0 ) ? number_format( $campaign['click_rate'], 2 ) : 0;
			$campaign['open_count']  = ( isset( $campaign['open_count'] ) && absint( $campaign['open_count'] ) > 0 ) ? $campaign['open_count'] : 0;
			$campaign['open_rate']   = ( isset( $campaign['open_rate'] ) && floatval( $campaign['open_rate'] ) > 0 ) ? number_format( $campaign['open_rate'], 2 ) : 0;
			$campaign['revenue']     = ( isset( $campaign['revenue'] ) && floatval( $campaign['revenue'] ) > 0 ) ? $campaign['revenue'] : 0;
			$campaign['conversions'] = ( isset( $campaign['conversions'] ) && absint( $campaign['conversions'] ) > 0 ) ? $campaign['conversions'] : 0;
			$campaign['sent']        = ( isset( $campaign['sent'] ) && absint( $campaign['sent'] ) > 0 ) ? $campaign['sent'] : 0;
			$campaign['subject']     = isset( $campaign['tid'] ) && ! empty( $campaign['tid'] ) ? self::get_template_subject( $campaign['tid'] ) : '';
			$campaign['parent']      = isset( $campaign['parent'] ) && absint( $campaign['parent'] ) > 0 ? absint( $campaign['parent'] ) : 0;
			$campaign['unsubs']      = isset( $campaign['id'] ) ? self::get_campaign_unsubscribers( absint( $campaign['id'] ) ) : 0;
			$campaign['created_at']  = isset( $campaign['created_at'] ) ? get_date_from_gmt( $campaign['created_at'] ) : '';
			$final_data[]            = $campaign;
		}

		return $final_data;
	}

	public static function get_template_subject( $tid ) {
		$template = BWFAN_Model_Templates::get( absint( $tid ) );
		if ( empty( $template ) || ! is_array( $template ) || ! isset( $template['subject'] ) ) {
			return '';
		}

		return $template['subject'];
	}

	public static function get_broadcasts_by_status( $status = 1, $check_execution_time = false ) {
		global $wpdb;
		$table_name = self::_table();
		$query      = "SELECT * FROM $table_name WHERE `status` = %s";
		$query_args = array( $status );
		if ( $check_execution_time ) {
			$current_time = current_time( 'mysql', 1 );
			$query        .= ' AND `execution_time` <= %s';
			$query_args[] = $current_time;
		}

		/** Fetch running broadcasts order by execution time ascending */
		if ( BWFCRM_Broadcast_Processing::$CAMPAIGN_RUNNING === intval( $status ) ) {
			$query .= " ORDER BY `execution_time` ASC";
		}

		$query      = $wpdb->prepare( $query, $query_args );
		$broadcasts = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return self::get_formatted_campaign_data( $broadcasts );
	}

	public static function get_scheduled_broadcasts_to_run() {
		global $wpdb;
		$table_name   = self::_table();
		$status       = BWFCRM_Campaigns::$CAMPAIGN_SCHEDULED;
		$current_time = current_time( 'mysql', 1 );

		$query     = $wpdb->prepare( "SELECT * FROM $table_name WHERE `status` = %s AND `execution_time` <= %s ORDER BY `execution_time` ASC", $status, $current_time );
		$campaigns = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return self::get_formatted_campaign_data( $campaigns );
	}

	public static function update_status_to_run( $campaign_id, $offset, $campaign_data, $contact_count ) {
		self::update( array(
			'offset'        => $offset,
			'data'          => json_encode( $campaign_data ),
			'count'         => absint( $contact_count ),
			'status'        => BWFCRM_Campaigns::$CAMPAIGN_RUNNING,
			'last_modified' => date( 'Y-m-d H:i:s', time() - 6 ),
		), array(
			'id' => absint( $campaign_id ),
		) );
	}

	public static function update_status_to_halt( $campaign_id, $campaign_data ) {
		self::update( array(
			'data'          => json_encode( $campaign_data ),
			'status'        => BWFCRM_Campaigns::$CAMPAIGN_HALT,
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => absint( $campaign_id ),
		) );
	}

	public static function update_status_to_complete( $campaign_id, $campaign_data, $processed, $count ) {
		self::update( array(
			'data'          => json_encode( $campaign_data ),
			'status'        => BWFCRM_Campaigns::$CAMPAIGN_COMPLETED,
			'count'         => absint( $processed ) > absint( $count ) ? $processed : $count,
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => absint( $campaign_id ),
		) );
	}

	public static function update_campaign_offsets( $campaign_id, $offset, $processed ) {
		self::update( array(
			'offset'        => absint( $offset ),
			'processed'     => absint( $processed ),
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => absint( $campaign_id ),
		) );
	}

	public static function update_status_to_pause( $campaign_id ) {
		$rows = self::update( array(
			'status' => BWFCRM_Campaigns::$CAMPAIGN_PAUSED,
		), array(
			'id'     => absint( $campaign_id ),
			'status' => BWFCRM_Campaigns::$CAMPAIGN_RUNNING,
		) );

		return ! empty( $rows );
	}

	/**
	 * Update status to ongoing
	 *
	 * @param $broadcast_id
	 *
	 * @return bool
	 */
	public static function update_status_to_ongoing( $broadcast_id ) {
		$broadcast       = self::get( intval( $broadcast_id ) );
		$b_data          = ! empty( $broadcast['data'] ) ? json_decode( $broadcast['data'], true ) : array();
		$updated_columns = array(
			'status' => BWFCRM_Campaigns::$CAMPAIGN_RUNNING,
		);

		/** Delete failed engagements if failed contact ids found in data */
		if ( ! empty( $b_data['failed_cids'] ) ) {
			unset( $b_data['failed_cids'] );
			unset( $b_data['failed_reason'] );

			/** Set data, offset and count updated values */
			$updated_columns['data'] = json_encode( $b_data );
		}

		$rows = self::update( $updated_columns, array(
			'id'     => intval( $broadcast_id ),
			'status' => BWFCRM_Campaigns::$CAMPAIGN_PAUSED,
		) );

		return ! empty( $rows );
	}

	public static function update_status_to_draft( $campaign_id ) {
		$rows = self::update( array(
			'status'         => BWFCRM_Campaigns::$CAMPAIGN_DRAFT,
			'execution_time' => null,
		), array(
			'id' => absint( $campaign_id ),
		) );

		return ! empty( $rows );
	}

	public static function update_status_to_cancel( $campaign_id ) {
		$rows = self::update( array(
			'status'         => BWFCRM_Campaigns::$CAMPAIGN_CANCELLED,
			'execution_time' => null,
		), array(
			'id' => absint( $campaign_id ),
		) );

		return ! empty( $rows );
	}

	public static function get_interaction_stats_by_date_range( $intervals, $oid ) {
		global $wpdb;

		$campaign = self::get( $oid );
		$mode     = absint( $campaign['type'] );

		$open_queries = array_map( function ( $interval ) {
			return "SUM(ROUND ( ( LENGTH(o_interaction) - LENGTH( REPLACE ( o_interaction, '$interval', '') ) ) / LENGTH('$interval') )) AS '$interval'";
		}, $intervals );
		$open_queries = implode( ' , ', $open_queries );

		$click_queries = array_map( function ( $interval ) {
			return "SUM(ROUND ( ( LENGTH(c_interaction) - LENGTH( REPLACE ( c_interaction, '$interval', '') ) ) / LENGTH('$interval') )) AS '$interval'";
		}, $intervals );
		$click_queries = implode( ' , ', $click_queries );

		$open_query  = "SELECT $open_queries FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `oid` = $oid AND `type` = 2";
		$click_query = "SELECT $click_queries FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `oid` = $oid AND `type` = 2";

		return array(
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
			'open'  => ! empty( $open_queries ) && BWFCRM_Campaigns::$CAMPAIGN_EMAIL === $mode ? $wpdb->get_results( $open_query, ARRAY_A ) : array(),
			//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
			'click' => ! empty( $click_queries ) ? $wpdb->get_results( $click_query, ARRAY_A ) : array(),
		);
	}

	/**
	 * Get top broadcast
	 *
	 * @param $type
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_top_broadcast( $type = 1 ) {
		global $wpdb;
		$campaign_table = $wpdb->prefix . 'bwfan_broadcast';

		if ( ! bwfan_is_woocommerce_active() ) {
			$base_query = "SELECT COALESCE(b.`id`,'') AS `id`, COALESCE(b.`title`,'') AS `title`, COALESCE(b.`processed`,0) AS `count`, SUM(IF(open>0, 1, 0)) AS `open_count` FROM {$campaign_table} AS b LEFT JOIN {$wpdb->prefix}bwfan_engagement_tracking AS et ON et.oid = b.id WHERE b.`type` = %d AND et.type = 2 GROUP BY et.`oid` ORDER BY `open_count` DESC LIMIT 0,5";
		} else {
			$conversion_table = $wpdb->prefix . 'bwfan_conversions';
			$base_query       = "SELECT COALESCE(cm.`id`,'') AS `id`, COALESCE(cm.`title`,'') AS `title`, cm.`type`, COALESCE(cm.`processed`,0) AS `count`, SUM(c.`wctotal`) AS `total_revenue` FROM $campaign_table AS cm LEFT JOIN $conversion_table AS c ON c.`oid` = cm.`id` WHERE c.`otype` = 2 AND cm.`type` = %d GROUP BY cm.`id` ORDER BY `total_revenue` DESC LIMIT 0,5";
		}

		return $wpdb->get_results( $wpdb->prepare( $base_query, $type ), ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function hold_broadcast_for_smart_send( $broadcast_id, $hours_to_delay ) {
		$broadcast                = self::get( absint( $broadcast_id ) );
		$b_data                   = ! empty( $broadcast['data'] ) ? json_decode( $broadcast['data'], true ) : array();
		$b_data                   = ! is_array( $b_data ) ? array() : $b_data;
		$b_data['hold_broadcast'] = 1;

		return self::set_delay( $broadcast_id, $hours_to_delay, 'H', $b_data );
	}

	public static function remove_hold_flag_smart_sending( $broadcast_id ) {
		$broadcast                    = self::get( absint( $broadcast_id ) );
		$b_data                       = ! empty( $broadcast['data'] ) ? json_decode( $broadcast['data'], true ) : array();
		$b_data                       = ! is_array( $b_data ) ? array() : $b_data;
		$b_data['smart_sending_done'] = 1;
		if ( isset( $b_data['hold_broadcast'] ) ) {
			unset( $b_data['hold_broadcast'] );
		}

		return ! ! self::update( array(
			'data'          => json_encode( $b_data ),
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => absint( $broadcast_id ),
		) );
	}

	public static function declare_the_winner_email( $broadcast_id, $winner_data ) {
		if ( empty( $winner_data ) ) {
			return false;
		}
		$broadcast = self::get( intval( $broadcast_id ) );
		$b_data    = ! empty( $broadcast['data'] ) ? json_decode( $broadcast['data'], true ) : array();
		$b_data    = ! is_array( $b_data ) ? array() : $b_data;

		$b_data                       = array_replace( $b_data, $winner_data );
		$b_data['smart_sending_done'] = 1;
		if ( isset( $b_data['hold_broadcast'] ) ) {
			unset( $b_data['hold_broadcast'] );
		}

		return ! ! self::update( array(
			'data'          => json_encode( $b_data ),
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => intval( $broadcast_id ),
		) );
	}

	public static function update_broadcast_data( $broadcast_id, $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		$broadcast = self::get( absint( $broadcast_id ) );
		$b_data    = ! empty( $broadcast['data'] ) ? json_decode( $broadcast['data'], true ) : array();
		$b_data    = ! is_array( $b_data ) ? array() : $b_data;
		$b_data    = array_replace( $b_data, $data );

		return ! ! self::update( array(
			'data'          => json_encode( $b_data ),
			'last_modified' => current_time( 'mysql', 1 ),
		), array(
			'id' => absint( $broadcast_id ),
		) );
	}

	/**
	 * Get first broadcast id
	 */
	public static function get_first_broadcast_id() {
		global $wpdb;
		$query = 'SELECT MIN(`id`) FROM ' . self::_table();

		return $wpdb->get_var( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	public static function get_broadcast_data( $broadcast_id ) {
		$broadcast = self::get( absint( $broadcast_id ) );
		if ( ! is_array( $broadcast ) || empty( $broadcast ) || ! isset( $broadcast['data'] ) || empty( $broadcast['data'] ) ) {
			return array();
		}

		$data = json_decode( $broadcast['data'], true );

		return ( ! is_array( $data ) || empty( $data ) ) ? array() : $data;
	}

	public static function get_broadcast_basic_stats( $oid ) {
		global $wpdb;

		$tracking_table  = "{$wpdb->prefix}bwfan_engagement_tracking";
		$broadcast_table = self::_table();

		$query = $wpdb->prepare( "SELECT oid as id, SUM(open) as open_count, (SUM(IF(open>0, 1, 0))/COUNT(ID)) * 100 as open_rate, SUM(click) as click_count, (SUM(IF(click>0, 1, 0))/COUNT(ID)) * 100 as click_rate, SUM(IF(c_status=2, 1, 0)) as sent FROM {$tracking_table} WHERE oid = %d AND type= %d", absint( $oid ), BWFAN_Email_Conversations::$TYPE_CAMPAIGN );
		$stats = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats = ! is_array( $stats ) ? array() : self::get_formatted_campaign_data( array( $stats ) )[0];

		$query   = $wpdb->prepare( "SELECT id,count,status, last_modified, data FROM {$broadcast_table} WHERE id = %d LIMIT 0, 1", $oid );
		$b_stats = $wpdb->get_row( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$b_stats = ! is_array( $b_stats ) ? array() : $b_stats;

		$stats                = array_replace( $stats, $b_stats );
		$stats['notice_data'] = [];
		if ( isset( $stats['status'] ) && ! in_array( absint( $stats['status'] ), [
				BWFCRM_Campaigns::$CAMPAIGN_DRAFT,
				BWFCRM_Campaigns::$CAMPAIGN_SCHEDULED,
				BWFCRM_Campaigns::$CAMPAIGN_COMPLETED
			], true ) ) {
			$campaign_data = ! empty( $b_stats['data'] ) ? json_decode( $b_stats['data'], true ) : array();
			if ( ! empty( $campaign_data ) ) {
				$stats['notice_data'] = self::get_campaign_notice_data( $stats['status'], $campaign_data );
			}
		}
		unset( $stats['data'] );

		return $stats;
	}

	/**
	 * Return broadcast count by type
	 *
	 * @return int[]
	 */
	public static function get_broadcast_total_count() {
		global $wpdb;
		$response = [
			'all' => 0,
			'1'   => 0,
			'2'   => 0,
			'3'   => 0,
		];
		$all      = 0;
		$table    = self::_table();
		$query    = "SELECT type, COUNT(*) as count FROM {$table} WHERE 1=1 AND parent=0 GROUP BY type ";
		$result   = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $result ) ) {
			return $response;
		}
		foreach ( $result as $row ) {
			$response[ $row['type'] ] = intval( $row['count'] );
			$all                      += intval( $row['count'] );
		}
		$response['all'] = $all;

		return $response;
	}

	/**
	 * Return broadcast count by status
	 *
	 * @param int $type broadcast type
	 *
	 * @return int[]
	 */
	public static function get_broadcast_total_count_by_status( $type = 1 ) {
		global $wpdb;
		$response = [
			'2' => 0,
			'3' => 0,
			'4' => 0,
			'5' => 0,
			'6' => 0,
			'7' => 0,
		];
		$all      = 0;
		$table    = self::_table();
		$query    = "SELECT `status`, COUNT(*) as `count` FROM {$table} WHERE 1=1 AND `parent`=0 AND `type`={$type} GROUP BY `status`";
		$result   = $wpdb->get_results( $query, ARRAY_A ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( empty( $result ) ) {
			return $response;
		}
		foreach ( $result as $row ) {
			$response[ $row['status'] ] = intval( $row['count'] );
			$all                        += intval( $row['count'] );
		}
		$response['status_all'] = $all;

		return $response;
	}

	public static function set_delay( $broadcast_id, $delay_time = "10", $time_type = "M", $b_data = [] ) {
		$execute_time = new DateTime( 'now' );
		$execute_time->add( new DateInterval( "PT{$delay_time}{$time_type}" ) );

		$data = array(
			'execution_time' => $execute_time->format( 'Y-m-d H:i:s' ),
			'last_modified'  => current_time( 'mysql', 1 ),
		);

		if ( is_array( $b_data ) && ! empty( $b_data ) ) {
			$data['data'] = json_encode( $b_data );
		}

		return ! ! self::update( $data, array(
			'id' => absint( $broadcast_id ),
		) );
	}

	/**
	 * Remove parent from child's broadcast
	 *
	 * @param $ids
	 *
	 * @return bool|int|mysqli_result|resource|void|null
	 */
	public static function remove_parent_from_child( $ids ) {
		if ( empty( $ids ) ) {
			return false;
		}
		global $wpdb;

		$child_ids = BWFAN_Model_Broadcast::get_broadcast_child( $ids, true );
		if ( empty( $child_ids ) ) {
			return;
		}

		$table       = self::_table();
		$placeholder = array_fill( 0, count( $child_ids ), '%d' );
		$placeholder = implode( ", ", $placeholder );
		$query       = "UPDATE `{$table}` SET `parent` = 0 WHERE `id` IN ( $placeholder )";
		$query       = $wpdb->prepare( $query, $child_ids );

		return $wpdb->query( $query ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Get broadcast title
	 *
	 * @param $search
	 * @param $limit
	 * @param $offset
	 *
	 * @return array|object|stdClass[]|null
	 */
	public static function get_broadcasts_titles( $search = '', $limit = 10, $offset = 0 ) {
		$broadcast_table = self::_table();

		$broadcast_query = "SELECT `id`, `title` FROM $broadcast_table WHERE `parent` = 0";
		if ( ! empty( $search ) ) {
			$broadcast_query .= " AND title LIKE '%" . esc_sql( $search ) . "%'";
		}

		if ( ! empty( $limit ) ) {
			global $wpdb;
			$broadcast_query .= " LIMIT %d, %d";
			$broadcast_query = $wpdb->prepare( $broadcast_query, $offset, $limit );
		}

		return self::get_results( $broadcast_query );
	}

	/**
	 * Get broadcast template's stats & winner
	 *
	 * @param $bid
	 * @param $mode
	 *
	 * @return array|stdClass[]
	 */
	public static function get_broadcast_tids_stats( $bid, $mode ) {
		global $wpdb;

		$engagement_table = $wpdb->prefix . 'bwfan_engagement_tracking';
		$columns          = 'SUM(IF(`open`>0, 1, 0)) AS `opened`, SUM(IF(`click`>0, 1, 0)) AS `clicked`, SUM(IF(`c_status`=2, 1, 0)) AS `sent`, `tid`';

		$interaction = ( BWFAN_Email_Conversations::$MODE_SMS === intval( $mode ) ) ? 'clicked' : 'opened';
		$interaction = apply_filters( 'bwfan_most_interacted_template_based_on', $interaction, $mode, $bid );

		$query = "SELECT $columns FROM {$engagement_table} WHERE `oid` = %d AND `type` = 2 GROUP BY `tid` ORDER BY `$interaction` DESC";
		$data  = $wpdb->get_results( $wpdb->prepare( $query, $bid ), ARRAY_A );//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
		if ( empty( $data ) ) {
			return [];
		}

		$tids        = array_column( $data, 'tid' );
		$conversions = BWFAN_Model_Broadcast::get_conversions_by_tids( $bid, true, $tids );
		$winner      = intval( $data[0]['tid'] );

		$stats = [];
		foreach ( $data as $row ) {
			if ( isset( $conversions[ $row['tid'] ] ) ) {
				$row['revenue'] = $conversions[ $row['tid'] ]['revenue'] ?? 0;
			}
			$stats[ $row['tid'] ] = $row;
		}

		return array( 'winner' => $winner, 'smart_send_stat' => $stats );
	}

	/**
	 * Get formatted smart stats
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function get_formatted_smart_stat( $data = [], $only_formatted = false ) {
		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		return array_map( function ( $stat ) use ( $only_formatted ) {
			$clicked = intval( $stat['clicked'] );
			$opened  = intval( $stat['opened'] );
			$sent    = intval( $stat['sent'] );
			$revenue = floatval( $stat['revenue'] );

			$open_rate  = ( $sent > 0 ) ? number_format( ( $opened / $sent ) * 100, 2 ) : 0;
			$click_rate = ( $sent > 0 ) ? number_format( ( $clicked / $sent ) * 100, 2 ) : 0;

			$click_to_open_rate = ( empty( $clicked ) || empty( $opened ) ) ? 0 : number_format( ( $clicked / $opened ) * 100, 2 );

			$stat['formatted_data'] = [
				[
					'l' => __( 'Sent', 'wp-marketing-automations-pro' ),
					'v' => empty( $sent ) ? '-' : $sent,
				],
				[
					'l' => __( 'Open Rate', 'wp-marketing-automations-pro' ),
					'v' => empty( $opened ) ? '-' : $open_rate . '% (' . $opened . ')',
				],
				[
					'l' => __( 'Click Rate', 'wp-marketing-automations-pro' ),
					'v' => empty( $clicked ) ? '-' : $click_rate . '% (' . $clicked . ')',
				],
				[
					'l' => __( 'Click to Open Rate', 'wp-marketing-automations-pro' ),
					'v' => empty( $click_to_open_rate ) ? '-' : $click_to_open_rate . '%',
				],
			];

			if ( bwfan_is_woocommerce_active() ) {
				$currency_symbol          = get_woocommerce_currency_symbol();
				$stat['formatted_data'][] = [
					'l' => __( 'Revenue', 'wp-marketing-automations-pro' ),
					'v' => empty( $revenue ) ? '-' : html_entity_decode( $currency_symbol . $revenue ),
				];
			}

			return $only_formatted ? $stat['formatted_data'] : $stat;
		}, $data );
	}

	/**
	 * Update broadcast data with status
	 *
	 * @param $broadcast_id
	 * @param $data
	 * @param $update_status
	 * @param $offset
	 * @param $processed
	 *
	 * @return bool
	 */
	public static function update_broadcast_data_with_status( $broadcast_id, $data = [], $update_status = false, $offset = null, $processed = null ) {
		$updated_data = [];
		if ( ! empty( $data ) && is_array( $data ) ) {
			$updated_data['data'] = json_encode( $data );
		}

		if ( false !== $update_status ) {
			$updated_data['status'] = $update_status;
		}
		if ( ! is_null( $offset ) ) {
			$updated_data['offset'] = $offset;
		}
		if ( ! is_null( $processed ) ) {
			$updated_data['processed'] = $processed;
		}

		if ( empty( $updated_data ) ) {
			return false;
		}
		$updated_data['last_modified'] = current_time( 'mysql', 1 );

		return ! ! self::update( $updated_data, array(
			'id' => absint( $broadcast_id ),
		) );

	}

	/**
	 * Delete failed engagements
	 *
	 * @param $broadcast_id
	 * @param $failed_cids
	 *
	 * @return bool|int|mysqli_result|null
	 */
	public static function delete_failed_engagements( $broadcast_id, $failed_cids ) {
		global $wpdb;

		$placeholder = array_fill( 0, count( $failed_cids ), '%d' );
		$placeholder = implode( ", ", $placeholder );
		$args        = array_merge( [ $broadcast_id ], $failed_cids );
		$query       = $wpdb->prepare( "DELETE FROM {$wpdb->prefix}bwfan_engagement_tracking WHERE `oid`=%d AND `type`=2 AND `cid` IN ( $placeholder )", $args );

		return $wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL
	}

}
