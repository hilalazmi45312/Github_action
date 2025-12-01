<?php

class BWFCRM_API_Email_Report extends BWFCRM_API_Base {
	public static $ins;
	private $aid = 0;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $contact;

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/analytics/emails/chart';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {
		$response = [];

		$response['totals']          = $this->prepare_item_for_response();
		$response['automation_data'] = $this->get_automation_data();

		$response['intervals'] = $this->prepare_item_for_response( 'interval' );

		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * @param $is_interval
	 *
	 * @return array
	 * @throws Exception
	 */
	public function prepare_item_for_response( $is_interval = '' ) {
		$start_date        = ( isset( $this->args['after'] ) && '' !== $this->args['after'] ) ? $this->args['after'] : BWFCRM_Dashboards::default_date( WEEK_IN_SECONDS )->format( BWFCRM_Dashboards::$sql_datetime_format );
		$end_date          = ( isset( $this->args['before'] ) && '' !== $this->args['before'] ) ? $this->args['before'] : BWFCRM_Dashboards::default_date()->format( BWFCRM_Dashboards::$sql_datetime_format );
		$intervals_request = ( isset( $this->args['interval'] ) && '' !== $this->args['interval'] ) ? $this->args['interval'] : 'day';
		$oid               = ( isset( $this->args['oid'] ) && absint( $this->args['oid'] ) > 0 ) ? $this->args['oid'] : 0;
		$type              = ( isset( $this->args['type'] ) && absint( $this->args['type'] ) ) ? $this->args['type'] : 0;
		$mode              = ( isset( $this->args['mode'] ) && absint( $this->args['mode'] ) ) ? $this->args['mode'] : 1;
		$is_contact        = isset( $this->args['contact'] ) ? $this->args['contact'] : 0;

		$automation_obj = BWFAN_Automation_V2::get_instance( $oid );
		if ( empty( $automation_obj->error ) ) {
			$this->aid = $oid;
		}

		if ( $is_contact ) {
			return $this->get_automation_contacts_data( $start_date, $end_date, $is_interval, $intervals_request );
		}

		$email_stats   = BWFCRM_Reports::get_email_stats( $start_date, $end_date, $is_interval, $intervals_request, $oid, $type, $mode );
		$intervals_all = BWFCRM_Dashboards::intervals_between( $start_date, $end_date, $intervals_request );

		$open_click_interactions = BWFCRM_Reports::get_interaction_stats_by_date_range( [ $start_date, $end_date ], $type, $oid, $mode );

		$open_click_interactions['open']  = array_column( $open_click_interactions['open'], 'f_open_count', 'f_open' );
		$open_click_interactions['click'] = array_column( $open_click_interactions['click'], 'f_click_count', 'f_click' );

		$interactions_count = $this->total_open_clicks_count( $open_click_interactions );

		$result    = [];
		$intervals = [];

		if ( empty( $is_interval ) ) {
			/** No interval */
			$result = $this->get_email_analytics_subtotals( $email_stats );

			$result['email_open']  = $interactions_count['email_open'];
			$result['email_click'] = $interactions_count['email_click'];

			return $result;
		}

		foreach ( $intervals_all as $all_interval ) {
			$interval   = $all_interval['time_interval'];
			$start_date = $all_interval['start_date'];
			$end_date   = $all_interval['end_date'];

			$o_interaction        = isset( $open_click_interactions['open'][ $interval ] ) ? $open_click_interactions['open'][ $interval ] : 0;
			$c_interaction        = isset( $open_click_interactions['click'][ $interval ] ) ? $open_click_interactions['click'][ $interval ] : 0;
			$email_stats_interval = BWFCRM_Dashboards::maybe_interval_exists( $email_stats, 'time_interval', $interval );
			$conversions          = BWFCRM_Reports::get_conversions( $start_date, $end_date, $oid, $type, $mode );

			if ( isset( $email_stats_interval[0]['conversions'] ) ) {
				$email_stats_interval[0]['conversions'] = $conversions['conversions'];
			}
			if ( isset( $email_stats_interval[0]['revenue'] ) ) {
				$email_stats_interval[0]['revenue'] = $conversions['revenue'];
			}
			$intervals['interval']                   = $interval;
			$intervals['start_date']                 = $start_date;
			$intervals['date_start_gmt']             = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $start_date )->format( BWFCRM_Dashboards::$sql_datetime_format );
			$intervals['end_date']                   = $end_date;
			$intervals['date_end_gmt']               = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $end_date )->format( BWFCRM_Dashboards::$sql_datetime_format );
			$intervals['subtotals']                  = $this->get_email_analytics_subtotals( $email_stats_interval );
			$intervals['subtotals']['email_open']    = absint( $o_interaction );
			$intervals['subtotals']['email_click']   = absint( $c_interaction );
			$intervals['subtotals']['total_orders']  = isset( $conversions['conversions'] ) ? $conversions['conversions'] : 0;
			$intervals['subtotals']['total_revenue'] = isset( $conversions['revenue'] ) ? $conversions['revenue'] : 0;
			$result[]                                = $intervals;
		}

		return $result;
	}

	public function get_email_analytics_subtotals( $email_stats ) {
		$open_count  = ! isset( $email_stats[0]['open_count'] ) ? 0 : $email_stats[0]['open_count'];
		$click_count = ! isset( $email_stats[0]['click_count'] ) ? 0 : $email_stats[0]['click_count'];
		$sent        = ! isset( $email_stats[0]['sent'] ) ? 0 : $email_stats[0]['sent'];
		$open_rate   = $open_count > 0 ? ( $open_count / $sent ) * 100 : 0;
		$click_rate  = $click_count > 0 ? ( $click_count / $sent ) * 100 : 0;

		return [
			'email_sents'   => absint( $sent ),
			'email_open'    => absint( $open_count ),
			'open_rate'     => number_format( $open_rate, 2 ),
			'email_click'   => absint( $click_count ),
			'click_rate'    => number_format( $click_rate, 2 ),
			'total_orders'  => ! isset( $email_stats[0]['conversions'] ) ? 0 : $email_stats[0]['conversions'],
			'total_revenue' => ! isset( $email_stats[0]['revenue'] ) ? 0 : $email_stats[0]['revenue']
		];
	}

	public function get_automation_data() {
		$type = ( isset( $this->args['type'] ) ) ? absint( $this->args['type'] ) : 1;
		$oid  = ( isset( $this->args['oid'] ) && absint( $this->args['oid'] ) > 0 ) ? $this->args['oid'] : 0;
		if ( 1 !== $type || empty( $oid ) ) {
			return '';
		}

		return BWFAN_Model_Automations_V2::get_automation( $oid );
	}

	public function get_segments( $oids, $start_date, $end_date ) {
		$segments = [];
		foreach ( $oids as $index => $oid ) {
			$oids                                = [ $oid ];
			$email_stats                         = BWFCRM_Reports::get_email_stats( $start_date, $end_date, '', '', $oids );
			$title                               = BWFCRM_Reports::get_object_title( $oid, 2 );
			$segments[ $index ]['segment_id']    = $oid;
			$segments[ $index ]['segment_label'] = $title;
			$segments[ $index ]['subtotals']     = $this->get_email_analytics_subtotals( $email_stats );
		}

		return $segments;
	}


	public function get_open_click_intervals( $intervals_all ) {
		return array_column( $intervals_all, 'time_interval' );
	}

	public function total_open_clicks_count( $open_click_interactions ) {
		$opens  = isset( $open_click_interactions['open'] ) ? $open_click_interactions['open'] : [];
		$clicks = isset( $open_click_interactions['click'] ) ? $open_click_interactions['click'] : [];

		return [
			'email_open'  => count( $opens ) > 0 ? array_sum( $opens ) : 0,
			'email_click' => count( $clicks ) > 0 ? array_sum( $clicks ) : 0
		];
	}

	public function get_automation_contacts_data( $start_date, $end_date, $is_interval, $intervals_request ) {
		$aid = ( isset( $this->args['oid'] ) && absint( $this->args['oid'] ) > 0 ) ? $this->args['oid'] : 0;

		$active_contacts       = BWFAN_Model_Automation_Contact::get_total_contacts( $aid, $start_date, $end_date, $is_interval, $intervals_request );
		$active_contacts_count = isset( $active_contacts[0]['contact_counts'] ) ? $active_contacts[0]['contact_counts'] : 0;


		$complete_contacts       = BWFAN_Model_Automation_Complete_Contact::get_total_contacts( $aid, $start_date, $end_date, $is_interval, $intervals_request );
		$complete_contacts_count = isset( $complete_contacts[0]['contact_counts'] ) ? $complete_contacts[0]['contact_counts'] : 0;

		$total_unsubs       = BWFCRM_Reports::get_unsubscribers_total( $start_date, $end_date, $is_interval, $intervals_request, $aid );
		$total_unsubs_count = isset( $total_unsubs[0]['unsubs_count'] ) ? $total_unsubs[0]['unsubs_count'] : 0;

		$conversions   = BWFAN_Model_Conversions::get_automation_revenue( $aid, $start_date, $end_date, $is_interval, $intervals_request );
		$total_revenue = isset( $conversions[0]['revenue'] ) ? (float) $conversions[0]['revenue'] : 0;
		/** AOV */
		$orders = isset( $conversions[0]['conversions'] ) ? absint( $conversions[0]['conversions'] ) : 0;
		$aov    = ! empty( $total_revenue ) && absint( $orders ) > 0 ? $total_revenue / $orders : 0;

		if ( empty( $is_interval ) ) {
			return [
				'active_contacts'   => absint( $active_contacts_count ),
				'complete_contacts' => absint( $complete_contacts_count ),
				'unsubscribers'     => absint( $total_unsubs_count ),
				'revenue'           => $total_revenue,
				'conversions'       => $orders,
				'aov'               => $aov,
			];
		}

		/** when interval present */

		$result        = [];
		$intervals     = [];
		$intervals_all = BWFCRM_Dashboards::intervals_between( $start_date, $end_date, $intervals_request );

		foreach ( $intervals_all as $all_interval ) {
			$interval   = $all_interval['time_interval'];
			$start_date = $all_interval['start_date'];
			$end_date   = $all_interval['end_date'];

			$active_contact       = BWFCRM_Dashboards::maybe_interval_exists( $active_contacts, 'time_interval', $interval );
			$active_contact_count = isset( $active_contact[0]['contact_counts'] ) ? $active_contact[0]['contact_counts'] : 0;

			$complete_contact       = BWFCRM_Dashboards::maybe_interval_exists( $complete_contacts, 'time_interval', $interval );
			$complete_contact_count = isset( $complete_contact[0]['contact_counts'] ) ? $complete_contact[0]['contact_counts'] : 0;

			$total_unsub       = BWFCRM_Dashboards::maybe_interval_exists( $total_unsubs, 'time_interval', $interval );
			$total_unsub_count = isset( $total_unsub[0]['unsubs_count'] ) ? $total_unsub[0]['unsubs_count'] : 0;

			$conversion = BWFCRM_Dashboards::maybe_interval_exists( $conversions, 'time_interval', $interval );
			$revenue    = isset( $conversion[0]['revenue'] ) ? $conversion[0]['revenue'] : 0;
			/** AOV */
			$orders = isset( $conversion[0]['conversions'] ) ? $conversion[0]['conversions'] : 0;
			$aov    = ! empty( $revenue ) && absint( $orders ) > 1 ? $revenue / $orders : 0;

			$intervals['interval']       = $interval;
			$intervals['start_date']     = $start_date;
			$intervals['date_start_gmt'] = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $start_date )->format( BWFCRM_Dashboards::$sql_datetime_format );
			$intervals['end_date']       = $end_date;
			$intervals['date_end_gmt']   = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $end_date )->format( BWFCRM_Dashboards::$sql_datetime_format );

			$intervals['subtotals'] = array(
				'active_contacts'   => absint( $active_contact_count ),
				'complete_contacts' => absint( $complete_contact_count ),
				'unsubscribers'     => absint( $total_unsub_count ),
				'revenue'           => $revenue,
				'conversions'       => $orders,
				'aov'               => $aov,
			);
			$result[]               = $intervals;
		}


		return $result;
	}

	/**
	 * @return array
	 */
	public function get_result_count_data() {
		if ( empty( $this->aid ) ) {
			return [];
		}

		return [
			'failed' => BWFAN_Model_Automation_Contact::get_active_count( $this->aid, 2 )
		];
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_API_Email_Report' );
