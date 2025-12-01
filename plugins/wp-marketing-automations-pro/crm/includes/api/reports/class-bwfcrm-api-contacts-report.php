<?php

class BWFCRM_API_Contacts_Report extends BWFCRM_API_Base {
	public static $ins;

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
		$this->route  = '/analytics/contacts';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {

		$response['totals'] = $this->prepare_item_for_response();

		$response['intervals'] = $this->prepare_item_for_response( 'interval' );

		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * @param string $is_interval
	 *
	 * @return array
	 */
	public function prepare_item_for_response( $is_interval = '' ) {

		$start_date        = ( isset( $this->args['after'] ) && '' !== $this->args['after'] ) ? $this->args['after'] : BWFCRM_Dashboards::default_date( WEEK_IN_SECONDS )->format( BWFCRM_Dashboards::$sql_datetime_format );
		$end_date          = ( isset( $this->args['before'] ) && '' !== $this->args['before'] ) ? $this->args['before'] : BWFCRM_Dashboards::default_date()->format( BWFCRM_Dashboards::$sql_datetime_format );
		$intervals_request = ( isset( $this->args['interval'] ) && '' !== $this->args['interval'] ) ? $this->args['interval'] : 'week';

		$new_contacts       = BWFCRM_Reports::get_total_contacts( $start_date, $end_date, $is_interval, $intervals_request );
		$new_contacts_count = isset( $new_contacts[0]['contact_counts'] ) ? $new_contacts[0]['contact_counts'] : 0;

		$total_contacts       = BWFCRM_Reports::get_contacts_before_date( $end_date );
		$total_contacts_count = isset( $total_contacts[0]['contact_counts'] ) ? $total_contacts[0]['contact_counts'] : 0;

		$total_customers       = BWFCRM_Reports::get_customers_before_date( $end_date );
		$total_customers_count = isset( $total_customers[0]['contact_counts'] ) ? $total_customers[0]['contact_counts'] : 0;

		$total_unsubs       = BWFCRM_Reports::get_unsubscribers_total( $start_date, $end_date, $is_interval, $intervals_request );
		$total_unsubs_count = isset( $total_unsubs[0]['unsubs_count'] ) ? $total_unsubs[0]['unsubs_count'] : 0;


		$result    = [];
		$intervals = [];
		/** when interval present */
		if ( ! empty( $is_interval ) ) {
			$intervals_all = BWFCRM_Dashboards::intervals_between( $start_date, $end_date, $intervals_request );

			$total_contacts_start       = BWFCRM_Reports::get_contacts_before_date( $start_date );
			$total_contacts_start_count = isset( $total_contacts_start[0]['contact_counts'] ) ? $total_contacts_start[0]['contact_counts'] : 0;

			foreach ( $intervals_all as $all_interval ) {
				$interval   = $all_interval['time_interval'];
				$start_date = $all_interval['start_date'];
				$end_date   = $all_interval['end_date'];

				$new_contact       = BWFCRM_Dashboards::maybe_interval_exists( $new_contacts, 'time_interval', $interval );
				$new_contact_count = isset( $new_contact[0]['contact_counts'] ) ? $new_contact[0]['contact_counts'] : 0;

				$total_contacts_start_count = $total_contacts_start_count + $new_contact_count;

				$total_customers       = BWFCRM_Reports::get_customers_before_date( $end_date );
				$total_customers_count = isset( $total_customers[0]['contact_counts'] ) ? $total_customers[0]['contact_counts'] : 0;

				$total_unsub       = BWFCRM_Dashboards::maybe_interval_exists( $total_unsubs, 'time_interval', $interval );
				$total_unsub_count = isset( $total_unsub[0]['unsubs_count'] ) ? $total_unsub[0]['unsubs_count'] : 0;

				$intervals['interval']       = $interval;
				$intervals['start_date']     = $start_date;
				$intervals['date_start_gmt'] = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $start_date )->format( BWFCRM_Dashboards::$sql_datetime_format );
				$intervals['end_date']       = $end_date;
				$intervals['date_end_gmt']   = BWFCRM_Dashboards::convert_local_datetime_to_gmt( $end_date )->format( BWFCRM_Dashboards::$sql_datetime_format );

				$intervals['subtotals'] = array(
					'total_contacts'  => absint( $total_contacts_start_count ),
					'total_customers' => absint( $total_customers_count ),
					'new_contacts'    => absint( $new_contact_count ),
					'unsubscribers'   => absint( $total_unsub_count )
				);
				$result[]               = $intervals;
			}
		} else {
			$result = [
				'total_contacts'  => absint( $total_contacts_count ),
				'total_customers' => absint( $total_customers_count ),
				'new_contacts'    => absint( $new_contacts_count ),
				'unsubscribers'   => absint( $total_unsubs_count )
			];

		}

		return $result;
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_API_Contacts_Report' );
