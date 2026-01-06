<?php

class BWFCRM_API_Get_Automation_Path_Stats extends BWFCRM_API_Base {
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
		$this->route        = 'automation/(?P<automation_id>[\\d]+)/step/(?P<step_id>[\\d]+)/path-stats/';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
			'step_id'       => array(
				'description' => __( 'Step id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),
		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );
		$step_id       = $this->get_sanitized_arg( 'step_id' );

		if ( empty( $automation_id ) || empty( $step_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		$step_data = BWFAN_Model_Automation_Step::get_step_data_by_id( $step_id );
		$data      = isset( $step_data['data'] ) ? json_decode( $step_data['data'], true ) : [];
		if ( empty( $data ) || ! is_array( $data ) || ! isset( $data['sidebarData'] ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		$default_stat = [
			[
				'l' => 'Sent',
				'v' => '-',
			],
			[
				'l' => 'Open Rate',
				'v' => '-',
			],
			[
				'l' => 'Click Rate',
				'v' => '-',
			],
			[
				'l' => 'Click to Open Rate',
				'v' => '-',
			],
			[
				'l' => 'Revenue',
				'v' => '-',
			],
			[
				'l' => 'Revenue/Contact',
				'v' => '-',
			],
			[
				'l' => 'Unsubscribe Rate',
				'v' => '-',
			]
		];

		/** Email and SMS steps in all paths */
		$mail_steps = isset( $data['sidebarData']['mail_steps'] ) ? $data['sidebarData']['mail_steps'] : [];
		if ( empty( $mail_steps ) ) {
			$split_path_count = isset( $data['sidebarData']['split_path'] ) ? intval( $data['sidebarData']['split_path'] ) : 3;
			$stats            = [];
			for ( $i = 1; $i <= $split_path_count; $i ++ ) {
				$stats[] = [
					'path' => $i,
					'tile' => $default_stat,
				];
			}

			return $this->success_response( $stats, __( 'No data found', 'wp-marketing-automations-pro' ) );
		}

		foreach ( $mail_steps as $path => $step_ids ) {
			$path    = str_replace( 'p-', '', $path );
			$tiles   = BWFCRM_Automations::get_path_stats( $automation_id, $step_ids, $step_data['created_at'] );
			$stats[] = [
				'path' => $path,
				'tile' => empty( $tiles ) ? $default_stat : $tiles,
			];
		}

		$this->response_code = 200;

		return $this->success_response( $stats, __( 'paths stats found', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Automation_Path_Stats' );
