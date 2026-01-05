<?php

class BWFCRM_API_Email_Engagement extends BWFCRM_API_Base {
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
		$this->route  = '/analytics/engagement';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {
		$response = $this->prepare_item_for_response();

		$this->response_code = 200;

		return $this->success_response( $response );
	}

	/**
	 * @param string $is_interval
	 *
	 * @return array
	 */
	public function prepare_item_for_response() {

		$start_date               = ( isset( $this->args['after'] ) && '' !== $this->args['after'] ) ? $this->args['after'] : BWFCRM_Dashboards::default_date( WEEK_IN_SECONDS )->format( BWFCRM_Dashboards::$sql_datetime_format );
		$end_date                 = ( isset( $this->args['before'] ) && '' !== $this->args['before'] ) ? $this->args['before'] : BWFCRM_Dashboards::default_date()->format( BWFCRM_Dashboards::$sql_datetime_format );
		$result                   = [];
		$days                     = array( "1", "2", "3", "4", "5", "6", "7" );
		$hours                    = array( "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23" );
		$get_email_trends_by_day  = BWFCRM_Dashboards::get_email_trends( $start_date, $end_date );
		$get_email_trends_by_hour = BWFCRM_Dashboards::get_email_trends( $start_date, $end_date, 'hour' );
		foreach ( $days as $day ) {
			$day_column = empty( $get_email_trends_by_day ) ? [] : array_column( $get_email_trends_by_day, 'day' );
			if ( ! in_array( $day, $day_column ) ) {
				$get_email_trends_by_day[] = array( 'day' => $day, 'mean' => number_format( 0, 4 ), 'open' => 0, 'total' => 0 );
			}
		}
		foreach ( $hours as $hour ) {
			$hour_column = empty( $get_email_trends_by_hour ) ? [] : array_column( $get_email_trends_by_hour, 'hour' );
			if ( ! in_array( $hour, $hour_column ) ) {
				$get_email_trends_by_hour[] = array( 'hour' => $hour, 'mean' => number_format( 0, 4 ), 'open' => 0, 'total' => 0 );
			}
		}

		usort( $get_email_trends_by_day, function ( $item1, $item2 ) {
			return $item1['day'] < $item2['day'] ? - 1 : 1;
		} );

		usort( $get_email_trends_by_hour, function ( $item1, $item2 ) {
			return $item1['hour'] < $item2['hour'] ? - 1 : 1;
		} );

		$result['day']  = $get_email_trends_by_day;
		$result['hour'] = $get_email_trends_by_hour;

		return $result;
	}

}

BWFCRM_API_Loader::register( 'BWFCRM_API_Email_Engagement' );