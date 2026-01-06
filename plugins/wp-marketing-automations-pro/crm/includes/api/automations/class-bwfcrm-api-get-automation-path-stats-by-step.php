<?php

class BWFCRM_API_Get_Split_Path_Stats_By_Step extends BWFCRM_API_Base {
	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public $total_count = 0;

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::READABLE;
		$this->route        = 'automation/(?P<automation_id>[\\d]+)/step/(?P<step_id>[\\d]+)/path/(?P<path_id>[\\d]+)';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'step_id'       => array(
				'description' => __( 'Step id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'path_id'       => array(
				'description' => __( 'Path id to get stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$step_id       = $this->get_sanitized_arg( 'step_id' );
		$path_id       = $this->get_sanitized_arg( 'path_id' );

		if ( empty( $automation_id ) || empty( $step_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}
		$path_name = 'p-' . $path_id;

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		$step_data = BWFAN_Model_Automation_Step::get_step_data_by_id( $step_id );
		$data      = isset( $step_data['data'] ) ? json_decode( $step_data['data'], true ) : [];
		if ( empty( $data ) || ! is_array( $data ) || ! isset( $data['sidebarData'] ) ) {
			return $this->success_response( [], __( 'Path stats not found', 'wp-marketing-automations-pro' ) );
		}
		/** Email and SMS steps in all paths */
		$mail_steps  = isset( $data['sidebarData']['mail_steps'] ) ? $data['sidebarData']['mail_steps'] : [];
		$split_stats = isset( $data['sidebarData']['split_stats'] ) ? $data['sidebarData']['split_stats'] : [];
		$step_stats  = isset( $split_stats['step_stats'] ) ? $split_stats['step_stats'] : [];


		if ( ! isset( $mail_steps[ $path_name ] ) ) {
			return $this->success_response( [], __( 'Path stats not found', 'wp-marketing-automations-pro' ) );
		}
		$path_steps = $mail_steps[ $path_name ];
		$final_data = [];
		foreach ( $path_steps as $step_id ) {
			if ( ! isset( $step_stats[ $step_id ] ) ) {
				$tid_data  = BWFAN_Model_Engagement_Tracking::get_engagements_tid( $automation_id, [ $step_id ] );
				$tids      = array_column( $tid_data, 'tid' );
				$templates = BWFAN_Model_Templates::get_templates_by_ids( $tids );
				$subject   = $type = '';
				foreach ( $tid_data as $data ) {
					$template = $templates[ $data['tid'] ];
					$subject  = isset( $template['subject'] ) ? $template['subject'] : '';
					$subject  = 1 === intval( $template['type'] ) ? $subject : $template['template'];
					$type     = $template['type'];
				}
				$tiles        = $this->get_path_stats( $automation_id, [ $step_id ], $step_data['created_at'] );
				$final_data[] = [
					'step'  => $step_id,
					'title' => $subject,
					'type'  => $type,
					'tile'  => $tiles,
				];
				continue;
			}

			$final_data[] = [
				'step'  => $step_id,
				'title' => isset( $step_stats[ $step_id ]['subject'] ) ? $step_stats[ $step_id ]['subject'] : '',
				'type'  => isset( $step_stats[ $step_id ]['type'] ) ? $step_stats[ $step_id ]['type'] : '',
				'tile'  => $step_stats[ $step_id ]['tiles'],
			];
		}

		$this->response_code = 200;

		return $this->success_response( $final_data, __( 'paths stats found', 'wp-marketing-automations-pro' ) );
	}

	/**
	 * Get path's stats
	 *
	 * @param $automation_id
	 * @param $step_ids
	 *
	 * @return array|WP_Error
	 */
	public function get_path_stats( $automation_id, $step_ids, $after_date ) {
		if ( empty( $step_ids ) ) {
			return [];
		}
		$data = BWFAN_Model_Engagement_Tracking::get_automation_step_analytics( $automation_id, $step_ids, $after_date );

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		$open_rate        = isset( $data['open_rate'] ) ? number_format( $data['open_rate'], 2 ) : 0;
		$click_rate       = isset( $data['click_rate'] ) ? number_format( $data['click_rate'], 2 ) : 0;
		$revenue          = isset( $data['revenue'] ) ? floatval( $data['revenue'] ) : 0;
		$unsubscribes     = isset( $data['unsbuscribers'] ) ? absint( $data['unsbuscribers'] ) : 0;
		$conversions      = isset( $data['conversions'] ) ? absint( $data['conversions'] ) : 0;
		$sent             = isset( $data['sent'] ) ? absint( $data['sent'] ) : 0;
		$open_count       = isset( $data['open_count'] ) ? absint( $data['open_count'] ) : 0;
		$click_count      = isset( $data['click_count'] ) ? absint( $data['click_count'] ) : 0;
		$contacts_count   = isset( $data['contacts_count'] ) ? absint( $data['contacts_count'] ) : 1;
		$rev_per_person   = empty( $contacts_count ) || empty( $revenue ) ? 0 : number_format( $revenue / $contacts_count, 2 );
		$unsubscribe_rate = empty( $contacts_count ) || empty( $unsubscribes ) ? 0 : ( $unsubscribes / $contacts_count ) * 100;

		/** Get click rate from total opens */
		$click_to_open_rate = ( empty( $click_count ) || empty( $open_count ) ) ? 0 : number_format( ( $click_count / $open_count ) * 100, 2 );

		$tiles = [
//			[
//				'l' => 'Contacts',
//				'v' => $contacts_count
//			],
			[
				'l' => __( 'Sent', 'wp-marketing-automations-pro' ),
				'v' => $sent,
			],
			[
				'l' => __( 'Opened', 'wp-marketing-automations-pro' ),
				'v' => empty( $open_count ) ? '-' : $open_count . '( ' . $open_rate . '% )',
			],
			[
				'l' => __( 'Clicked', 'wp-marketing-automations-pro' ),
				'v' => empty( $click_count ) ? '-' : $click_count . ' ( ' . $click_rate . '% )',
			],
			[
				'l' => __( 'Click to Open.', 'wp-marketing-automations-pro' ),
				'v' => $click_to_open_rate . '%',
			]
		];

		if ( bwfan_is_woocommerce_active() ) {

			$currency_symbol = get_woocommerce_currency_symbol();
			$revenue         = html_entity_decode( $currency_symbol . $revenue );
			$rev_per_person  = html_entity_decode( $currency_symbol . $rev_per_person );

			$revenue_tiles = [
				[
					'l' => __( 'Rev.', 'wp-marketing-automations-pro' ),
					'v' => empty( $revenue ) ? '-' : $revenue . ' ( ' . $conversions . ' )',
				],
				[
					'l' => __( 'Rev Per Contact', 'wp-marketing-automations-pro' ),
					'v' => $rev_per_person,
				]
			];

			$tiles = array_merge( $tiles, $revenue_tiles );
		}

		$tiles[] = [
			'l' => __( 'Unsubscribed', 'wp-marketing-automations-pro' ),
			'v' => empty( $unsubscribes ) ? '-' : $unsubscribes . '( ' . number_format( $unsubscribe_rate, 2 ) . '% )',
		];

		return $tiles;
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Split_Path_Stats_By_Step' );
