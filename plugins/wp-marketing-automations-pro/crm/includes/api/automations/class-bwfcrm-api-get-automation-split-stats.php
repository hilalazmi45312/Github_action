<?php

class BWFCRM_API_Get_Automation_split_Stats extends BWFCRM_API_Base {
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
		$this->route        = 'automation/(?P<automation_id>[\\d]+)/split-stats/';
		$this->request_args = array(
			'automation_id' => array(
				'description' => __( 'Automation id to get path stats', 'wp-marketing-automations-pro' ),
				'type'        => 'integer',
			),

		);
	}

	public function process_api_call() {
		$automation_id = $this->get_sanitized_arg( 'automation_id' );

		if ( empty( $automation_id ) ) {
			return $this->error_response( __( 'Invalid / Empty automation ID provided', 'wp-marketing-automations-pro' ), null, 400 );
		}

		/** Initiate automation object */
		$automation_obj = BWFAN_Automation_V2::get_instance( $automation_id );

		/** Check for automation exists */
		if ( ! empty( $automation_obj->error ) ) {
			return $this->error_response( [], $automation_obj->error );
		}

		$meta_data             = $automation_obj->get_automation_meta_data();
		$split_steps           = isset( $meta_data['split_steps'] ) ? $meta_data['split_steps'] : [];
		$completed_split_steps = isset( $meta_data['completed_split_steps'] ) ? $meta_data['completed_split_steps'] : [];
		$split_steps           = array_replace( $split_steps, $completed_split_steps );
		$prepared_data         = [];
		$empty_tiles           = [
			[
				'l' => __( 'Contacts', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Sent', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Opened', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Clicked', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Click to Open', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Rev.', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Rev. Per Contact', 'wp-marketing-automations-pro' ),
				'v' => '-',
			],
			[
				'l' => __( 'Unsubscribed', 'wp-marketing-automations-pro' ),
				'v' => '-',
			]
		];
		foreach ( $split_steps as $step_id => $step ) {
			$data = BWFAN_Model_Automation_Step::get_step_data( $step_id );

			$sidebarData = isset( $data['data']['sidebarData'] ) ? $data['data']['sidebarData'] : [];
			$paths       = isset( $sidebarData['mail_steps'] ) ? $sidebarData['mail_steps'] : [];
			$split_stats = isset( $sidebarData['split_stats'] ) ? $sidebarData['split_stats'] : [];
			$paths_stats = isset( $split_stats['paths_stats'] ) ? $split_stats['paths_stats'] : [];
			$title       = isset( $sidebarData['title'] ) ? $sidebarData['title'] : '';
			$completed   = isset( $sidebarData['complete_time'] ) ? $sidebarData['complete_time'] : '';
			$winner      = isset( $sidebarData['winner'] ) ? $sidebarData['winner'] : '';
			$desc        = isset( $sidebarData['desc'] ) ? $sidebarData['desc'] : '';
			$prep_data   = [
				'title'     => $title,
				'status'    => isset( $data['status'] ) && intval( $data['status'] ) !== 4 ? 'ongoing' : 'completed',
				'started'   => isset( $data['created_at'] ) ? $data['created_at'] : '',
				'completed' => $completed,
				'desc'      => $desc,
				'step_id'   => $step_id,
			];
			/** If no steps found in paths */
			if ( empty( $paths ) ) {
				$split_path_count = isset( $sidebarData['split_path'] ) ? intval( $sidebarData['split_path'] ) : 3;
				for ( $i = 1; $i <= $split_path_count; $i ++ ) {
					$prep_data['path'][] = [
						'path' => $i,
						'tile' => $empty_tiles
					];
				}
			}
			/** For ongoing split step */
			if ( empty( $split_stats ) ) {
				foreach ( $paths as $path_name => $mail_steps ) {
					$path = str_replace( 'p-', '', $path_name );
					if ( empty( $mail_steps ) ) {
						$prep_data['path'][] = [
							'path' => $path,
							'tile' => $empty_tiles
						];
						continue;
					}
					$tiles               = BWFCRM_Automations::get_path_stats( $automation_id, $mail_steps, $data['created_at'] );
					$contact_count       = BWFAN_Model_Automation_Contact_Trail::get_path_contact_count( $step_id, $path );
					$tiles[0]            = [
						'l' => __( 'Contacts', 'wp-marketing-automations-pro' ),
						'v' => ! empty( $contact_count ) ? intval( $contact_count ) : '-',
					];
					$prep_data['path'][] = [
						'path'   => $path,
						'tile'   => $tiles,
						'winner' => false,
					];
				}
				$prepared_data[] = $prep_data;
				continue;
			}

			/** For completed split step */
			foreach ( $paths as $path_name => $mail_steps ) {
				$path_stats = isset( $paths_stats[ $path_name ] ) ? $paths_stats[ $path_name ] : [];
				$p_name     = str_replace( 'p-', '', $path_name );
				if ( empty( $path_stats ) ) {
					$prep_data['path'][] = [
						'path' => $p_name,
						'tile' => $empty_tiles
					];
					continue;
				}

				$prep_data['path'][] = [
					'path'   => $p_name,
					'tile'   => $path_stats,
					'winner' => ( intval( $winner ) === intval( $p_name ) ),
				];
			}
			$prepared_data[] = $prep_data;
		}

		$key_values = array_column( $prepared_data, 'started' );
		array_multisort( $key_values, SORT_DESC, $prepared_data );

		$this->response_code = 200;

		return $this->success_response( [
			'automation_data' => $automation_obj->get_automation_data(),
			'data'            => $prepared_data,
		], __( 'paths stats found', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Automation_split_Stats' );
