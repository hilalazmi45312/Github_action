<?php

class BWFCRM_API_Direct_Emails_Tabular extends BWFCRM_API_Base {
	public static $ins;

	public $total_count = 0;

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
		$this->route  = '/analytics/direct-emails/tabular';
	}

	public function default_args_values() {
		return array();
	}

	public function process_api_call() {

		$start_date    = ( isset( $this->args['after'] ) && '' !== $this->args['after'] ) ? $this->args['after'] : BWFCRM_Dashboards::default_date( WEEK_IN_SECONDS )->format( BWFCRM_Dashboards::$sql_datetime_format );
		$end_date      = ( isset( $this->args['before'] ) && '' !== $this->args['before'] ) ? $this->args['before'] : BWFCRM_Dashboards::default_date()->format( BWFCRM_Dashboards::$sql_datetime_format );
		$type          = ( isset( $this->args['type'] ) && '' !== $this->args['type'] ) ? $this->args['type'] : '';
		$offset        = ! empty( $this->args['limit'] ) ? absint( $this->args['limit'] ) : 0;
		$limit         = ! empty( $this->args['offset'] ) ? absint( $this->args['offset'] ) : 0;
		$mode          = ! empty( $this->args['mode'] ) ? absint( $this->args['mode'] ) : 1;

		$conversations = BWFCRM_Reports::get_direct_conversations_with_template( $start_date, $end_date, $type, $limit, $offset, $mode );

		if ( ! empty( $conversations ) ) {
			$conversations[ 'data' ] = array_map( function( $con ) {
				if ( isset( $con['author_id'] ) && absint( $con['author_id'] ) > 0 ) {
					$user = get_user_by( 'id', absint( $con['author_id'] ) );
					if ( $user instanceof WP_User ) {
						$con['author'] = array(
							'email' => $user->user_email,
							'name'  => $user->display_name,
							'id'    => $user->ID,
							'link'  => add_query_arg( 'user_id', $user->ID, admin_url( 'user-edit.php' ) ),
						);
						$crmProfile      = bwf_get_contact( $user->ID, $user->user_email );
						if ( $crmProfile && $crmProfile instanceof WooFunnels_Contact && $crmProfile->get_id() > 0 ) {
							$con['author']['contact'] = $crmProfile->id;
						}
					}
					return $con;
				}
			}, $conversations[ 'data' ]);
		}

		if ( isset( $conversations['total_count'] ) ) {
			$this->total_count = $conversations['total_count'];
			unset( $conversations['total_count'] );
		}

		$this->response_code = 200;

		return $this->success_response( $conversations );
	}

	public function get_result_total_count() {
		return $this->total_count;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Direct_Emails_Tabular' );
