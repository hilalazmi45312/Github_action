<?php

class BWFCRM_API_Get_Transactional_Mail extends BWFCRM_API_Base {

    public static $ins;

    public static function get_instance() {
        if ( null === self::$ins ) {
            self::$ins = new self();
        }

        return self::$ins;
    }

    public function __construct() {
        parent::__construct();
        $this->method            = WP_REST_Server::READABLE;
        $this->route             = '/transactional-mail/(?P<type>[a-zA-Z_-]+)';
    }


    public function process_api_call() {
        $type      = $this->get_sanitized_arg( 'type' );
        if ( empty( $type ) ) {
            return $this->error_response( __( 'Empty transactional mail type', 'wp-marketing-automations-pro' ), null, 400 );
        }
        $lang = $this->args['lang'] ?? '';
        $this->response_code = 200;
		// @todo add lite data if pro not found || pro version is old
        if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
            return $this->success_response( [] );
        }
        $mail_class = BWFCRM_Core()->transactional_mails;
        $template_data =  $mail_class->get_transactional_mail_by_slug( $type, $lang );
		if( ! $template_data ) {
			return $this->error_response( __( 'Invalid transactional mail type', 'wp-marketing-automations-pro' ), null, 404 );
		}
        return $this->success_response( $template_data );
    }
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Transactional_Mail' );