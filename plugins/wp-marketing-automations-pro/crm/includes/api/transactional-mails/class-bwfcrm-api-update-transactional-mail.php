<?php

class BWFCRM_API_Update_Transactional_Mail extends BWFCRM_API_Base {

    public static $ins;

    public static function get_instance() {
        if ( null === self::$ins ) {
            self::$ins = new self();
        }

        return self::$ins;
    }

    public function __construct() {
        parent::__construct();
        $this->method            = WP_REST_Server::CREATABLE;
        $this->route             = '/transactional-mail/(?P<type>[a-zA-Z_-]+)';
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

        $this->response_code = 200;
        if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
            return $this->success_response( [] );
        }
        $template_data = BWFCRM_Core()->transactional_mails->get_transactional_mail_by_slug( $type, $lang );

        $template_id = $template_data['ID'];

        // Implementing the logic to update the transactional mail content

	    //check if the template_id exists in other engagement
	    //if exists create a new transactional mail template with the updated content
	    //else update the existing transactional mail template with the updated content
	    $engagement_exists = BWFAN_Model_Engagement_Tracking::get_engagements_by_tid( $template_id, true );

        // Update the template
        $create_time = current_time( 'mysql', 1 );

		if ( ! empty( $lang ) ) {
			$data['lang'] = $lang;
		}

        // new template data
        $template_data = [
            'title'      => $type,
            'template'   => $template,
            'subject'    => $subject,
            'type'       => 1,
            'mode'       => 7,
            'canned'     => 0,
            'updated_at' => $create_time,
            'data'       => BWFAN_Common::is_json( $data ) ? $data : wp_json_encode( $data ),
        ];
		if ( intval( $engagement_exists ) > 0 ) {
			$template_data[ 'created_at' ] = $create_time;
			$template_id = BWFAN_Model_Templates::bwfan_create_new_template( $template_data );
			$result = BWFCRM_Core()->transactional_mails->update_option_template_id( $type, $template_id, $lang );
		} else {
            $result = BWFAN_Model_Templates::bwfan_update_template( $template_id, $template_data );
		}

        if ( ! $result ) {
            $this->response_code = 500;

            return $this->error_response( __( 'Unable to update transactional mail', 'wp-marketing-automations-pro' ) );
        }

        return $this->success_response( [], __( 'Transactional mail updated', 'wp-marketing-automations-pro' ) );
    }
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Update_Transactional_Mail' );