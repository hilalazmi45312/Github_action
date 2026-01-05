<?php

class BWFCRM_API_Declare_Path_Winner extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;
	public $automation_id = 0;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = 'automation/(?P<automation_id>[\\d]+)/step/(?P<step_id>[\\d]+)/declare-winner';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to get split step', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'step_id'       => array(
				'description' => __( 'Step Id of split test', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'node_id'       => array(
				'description' => __( 'Step node to get all steps of split step', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'path_num'      => array(
				'description' => __( 'Path number to declare winner', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$this->automation_id = $this->get_sanitized_arg( 'automation_id' );
		$node_id             = $this->get_sanitized_arg( 'node_id' );
		$path_num            = $this->get_sanitized_arg( 'path_num' );
		$step_id             = $this->get_sanitized_arg( 'step_id' );

		if ( empty( $this->automation_id ) || empty( $node_id ) || empty( $path_num ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}
		$current_path = 'p-' . $path_num;

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $this->automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		/** Get all nodes of split step */
		$split_steps = BWFAN_Model_Automationmeta::get_meta( $this->automation_id, 'split_steps' );
		if ( isset( $split_steps[ $step_id ] ) ) {
			$all_steps = $split_steps[ $step_id ];
		}

		/** Get steps to mark archive  */
		$steps_for_archive = [];
		foreach ( $all_steps as $path => $nodes ) {
			if ( $current_path === $path ) {
				continue;
			}
			$steps_for_archive = array_merge( $steps_for_archive, $nodes );
		}
		$steps_for_archive = array_filter( $steps_for_archive );

		/** Mark steps to split archive */
		BWFAN_Model_Automation_Step::update_steps_status( $steps_for_archive );

		$data = $this->set_winner_path( $step_id, $path_num );
		/** Save completed split steps */
		$this->save_completed_split( $step_id, $all_steps, $automation_obj );
		$this->response_code = 200;

		return $this->success_response( $data, __( 'Winner path set', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * @param $step_data
	 *
	 * @return array|array[]
	 */
	public function get_path_stats( $step_data ) {
		$create_time = $step_data['created_at'];
		$data        = isset( $step_data['data'] ) ? $step_data['data'] : [];
		/** Email and SMS steps in all paths */
		$mail_steps = isset( $data['sidebarData']['mail_steps'] ) ? $data['sidebarData']['mail_steps'] : [];

		if ( empty( $mail_steps ) ) {
			return [];
		}

		$paths_stats = [];
		$step_stats  = [];
		foreach ( $mail_steps as $path => $steps ) {

			$sent           = 0;
			$open_count     = 0;
			$click_count    = 0;
			$revenue        = 0;
			$unsubscribes   = 0;
			$conversions    = 0;
			$contacts_count = 0;

			/** Get analytics of each single step */
			foreach ( $steps as $step ) {
				$stats                        = BWFCRM_Automations::get_path_stats( $this->automation_id, [ $step ], $create_time, false );
				$path_num                     = str_replace( 'p-', '', $path );
				$contact_count                = BWFAN_Model_Automation_Contact_Trail::get_path_contact_count( $step_data['ID'], $path_num );
				$stats['tiles'][0]            = [
					'l' => 'Contacts',
					'v' => ! empty( $contact_count ) ? intval( $contact_count ) : '-',
				];
				$sent                         += intval( $stats['stats']['sent'] );
				$open_count                   += intval( $stats['stats']['open_count'] );
				$click_count                  += intval( $stats['stats']['click_count'] );
				$contacts_count               += intval( $stats['stats']['contacts_count'] );
				$revenue                      += floatval( $stats['stats']['revenue'] );
				$unsubscribes                 += intval( $stats['stats']['unsubscribes'] );
				$conversions                  += intval( $stats['stats']['conversions'] );
				$step_stats[ $step ]['tiles'] = $stats['tiles'];
			}

			$open_rate        = $open_count > 0 ? number_format( ( $open_count / $sent ) * 100, 1 ) : 0;
			$click_rate       = $click_count > 0 ? number_format( ( $click_count / $sent ) * 100, 1 ) : 0;
			$rev_per_person   = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 1 );
			$unsubscribe_rate = empty( $contacts_count ) || empty( $unsubscribes ) ? 0 : ( $unsubscribes / $contacts_count ) * 100;
			/** Get click rate from total opens */
			$click_to_open_rate = ( empty( $click_count ) || empty( $open_count ) ) ? 0 : number_format( ( $click_count / $open_count ) * 100, 1 );


			$paths_stats[ $path ] = [
				[
					'l' => __( 'Contact', 'wp-marketing-automations-pro' ),
					'v' => empty( $contacts_count ) ? '-' : $contacts_count,
				],
				[
					'l' => __( 'Sent', 'wp-marketing-automations-pro' ),
					'v' => empty( $sent ) ? '-' : $sent,
				],
				[
					'l' => __( 'Opened', 'wp-marketing-automations-pro' ),
					'v' => empty( $open_count ) ? '-' : $open_count . ' (' . $open_rate . '%)',
				],
				[
					'l' => __( 'Clicked', 'wp-marketing-automations-pro' ),
					'v' => empty( $click_count ) ? '-' : $click_count . ' (' . $click_rate . '%)',
				],
				[
					'l' => __( 'Click to Open.', 'wp-marketing-automations-pro' ),
					'v' => empty( $click_to_open_rate ) ? '-' : $click_to_open_rate . '%',
				],
			];
			if ( bwfan_is_woocommerce_active() ) {

				$currency_symbol = get_woocommerce_currency_symbol();
				$revenue         = empty( $revenue ) ? '' : html_entity_decode( $currency_symbol . $revenue );
				$rev_per_person  = empty( $rev_per_person ) ? '-' : html_entity_decode( $currency_symbol . $rev_per_person );

				$revenue_tiles = [
					[
						'l' => __( 'Rev.', 'wp-marketing-automations-pro' ),
						'v' => empty( $revenue ) ? '-' : $revenue . ' (' . $conversions . ')',
					],
					[
						'l' => __( 'Rev Per Contact', 'wp-marketing-automations-pro' ),
						'v' => empty( $rev_per_person ) ? '-' : $rev_per_person,
					]
				];

				$paths_stats[ $path ] = array_merge( $paths_stats[ $path ], $revenue_tiles );
			}
			$paths_stats[ $path ][] = [
				'l' => __( 'Unsubscribed', 'wp-marketing-automations-pro' ),
				'v' => empty( $unsubscribes ) ? '-' : $unsubscribes . ' (' . number_format( $unsubscribe_rate, 1 ) . '%)',
			];
		}

		return [
			'paths_stats' => $paths_stats,
			'step_steps'  => $step_stats
		];
	}

	/**
	 * Set winner path in split step data
	 *
	 * @param $step_id
	 * @param $winner_path
	 *
	 * @return array[]|void
	 */
	public function set_winner_path( $step_id, $winner_path ) {
		/** Step data */
		$data                         = BWFAN_Model_Automation_Step::get_step_data( $step_id );
		$sidebarData                  = isset( $data['data']['sidebarData'] ) ? $data['data']['sidebarData'] : [];
		$sidebarData['winner']        = $winner_path;
		$sidebarData['complete_time'] = current_time( 'mysql', 1 );
		/** Set split stats */
		$sidebarData['split_stats'] = $this->get_path_stats( $data );
		/** Set split preview data */
		$sidebarData['preview_data'] = BWFCRM_Automations::get_split_preview_data( $this->automation_id, $step_id );
		$this->save_step_data( $step_id, $sidebarData );
		$updated_data = [
			'sidebarData' => $sidebarData
		];

		return $updated_data;
	}

	/**
	 * @param $step_id
	 * @param $sidebarData
	 *
	 * @return void
	 */
	public function save_step_data( $step_id, $sidebarData ) {
		$updated_data = [
			'sidebarData' => $sidebarData
		];

		BWFAN_Model_Automation_Step::update( array(
			'data'   => wp_json_encode( $updated_data ),
			'status' => 4,
		), array(
			'ID' => $step_id,
		) );

	}

	/**
	 * @param $step_id
	 * @param $all_steps
	 * @param $automation_obj
	 *
	 * @return bool|void
	 */
	public function save_completed_split( $step_id, $all_steps, $automation_obj ) {
		$meta_data = $automation_obj->get_automation_meta_data();
		if ( ! isset( $meta_data['completed_split_steps'] ) ) {
			$split_steps = [
				$step_id => $all_steps
			];

			$data = [
				'completed_split_steps' => maybe_serialize( $split_steps )
			];

			return BWFAN_Model_Automationmeta::insert_automation_meta_data( $automation_obj->automation_id, $data );;
		}

		$split_steps             = $meta_data['completed_split_steps'];
		$split_steps[ $step_id ] = $all_steps;
		$meta_data_arr           = [
			'completed_split_steps' => maybe_serialize( $split_steps )
		];


		return BWFAN_Model_Automationmeta::update_automation_meta_values( $automation_obj->automation_id, $meta_data_arr );
	}


}

BWFCRM_API_Loader::register( 'BWFCRM_API_Declare_Path_Winner' );
