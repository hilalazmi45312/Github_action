<?php

class BWFCRM_API_Save_Transactional_Email_Content extends BWFCRM_API_Base {

	public static $ins;

	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

	public function __construct() {
		parent::__construct();
		$this->method       = WP_REST_Server::CREATABLE;
		$this->route        = '/transactional-mail/(?P<type>[a-zA-Z_-]+)/save-email-content';
	}

	public function process_api_call() {
		$type      = $this->get_sanitized_arg( 'type' );
		if ( empty( $type ) ) {
			return $this->error_response( __( 'Invalid or empty transactional mail type', 'wp-marketing-automations-pro' ), null, 400 );
		}
		if ( isset( $this->args['content'] ) ) {
			$content    = BWFAN_Common::is_json( $this->args['content'] ) ? json_decode( $this->args['content'], true ) : [];
			$this->args = wp_parse_args( $content, $this->args );
		}
		$lang = $this->args['lang'] ?? '';
		$subject = $this->get_sanitized_arg( 'subject', 'text_field', $this->args['subject'] );
		$template = isset( $this->args['template'] ) ? $this->args['template'] : '';
		$data = isset($this->args['data']) ? $this->args['data'] : [];

		if ( ! empty( $lang ) ) {
			$data['lang'] = $lang;
		}

		$this->response_code = 200;
		if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
			return $this->success_response( [] );
		}
		$mail_class = BWFCRM_Core()->transactional_mails;
		$template_data =  $mail_class->get_transactional_mail_by_slug( $type, $lang );
		if ( empty( $template_data ) ) {
			$this->response_code = 404;

			return $this->error_response( __( 'Transactional mail not found', 'wp-marketing-automations-pro' ) );
		}
		$template_id = $template_data['ID'];
		//check if the template_id exists in other engagement
		//if exists create a new transactional mail template with the updated content
		//else update the existing transactional mail template with the updated content
		$engagement_exists = BWFAN_Model_Engagement_Tracking::get_engagements_by_tid( $template_id, true );

		// Update the template
		$create_time = current_time( 'mysql', 1 );

		if ( intval( $engagement_exists ) > 0 ) {
			// new template data
			$template_data = [
				'title'      => $type,
				'template'   => $template,
				'subject'    => $subject,
				'type'       => 1,
				'mode'       => 7,
				'canned'     => 0,
				'updated_at' => $create_time,
				'created_at' => $create_time,
				'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
			];
			$template_id = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );
			$result = BWFCRM_Core()->transactional_mails->update_option_template_id( $type, $template_id, $lang );
		} else {
			$template_data = [
				'template'   => $template,
				'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
				'updated_at' => $create_time,
			];
			if ( ! empty( $subject ) ) {
				$template_data[ 'subject' ] = $subject;
			}
			$result = BWFAN_Model_Templates::bwfan_update_template( $template_id, $template_data );
		}

		if ( ! $result ) {
			$this->response_code = 500;

			return $this->error_response( __( 'Unable to update transactional mail', 'wp-marketing-automations-pro' ) );
		}

		return $this->success_response( [], __( 'Transactional mail updated successfully', 'wp-marketing-automations-pro' ) );
	}
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Save_Transactional_Email_Content' );