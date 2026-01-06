<?php

class BWFCRM_API_Get_Transactional_Mails extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method = WP_REST_Server::READABLE;
		$this->route  = '/transactional-mails';
		$this->lists  = array();
	}


	public function process_api_call() {
		$this->response_code = 200;
		if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
			return $this->success_response( [] );
		}
		$mail_obj = BWFCRM_Core()->transactional_mails;
		$mail_obj->load_all_files();
		$mail_data = $mail_obj->get_registered_transactional_mails();

		if ( empty( $mail_data ) ) {
			return $this->success_response( [], __( 'No transactional mails found', 'wp-marketing-automations-pro' ) );
		}

		$final_data = [];

		foreach ( $mail_data as $mail ) {
			if ( empty( $mail['template_data'] ) ) {
				$final_data[] = $mail;
				continue;
			}

			$template_id = 0;
			if ( empty( $lang ) && ! empty( $mail['template_data']['template_id'] ) ) {
				$template_id = $mail['template_data']['template_id'];
			} else if ( ! empty( $lang ) && ! empty( $mail['template_data']['lang'][ $lang ] ) ) {
				$template_id = $mail['template_data']['lang'][ $lang ];
			}

			if ( empty( $template_id ) ) {
				$final_data[] = $mail;
				continue;
			}

			$template_data = BWFAN_Model_Templates::bwfan_get_template( $template_id, 0 );
			if ( isset( $template_data['data'] ) ) {
				$template_data_data = json_decode( $template_data['data'], true );
				$other_recipient    = ! empty( $template_data_data['other_recipients'] ) ? $template_data_data['other_recipients'] : '';
				if ( ! empty( $other_recipient ) ) {
					$mail['other_recipient'] = $other_recipient;
				}
			}
			$final_data[] = $mail;
		}

		return $this->success_response( $final_data, __( 'Transactional mails fetched successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Transactional_Mails' );