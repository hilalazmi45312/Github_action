<?php

class BWFCRM_API_Update_Transactional_Mail_Status extends BWFCRM_API_Base {

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
        $this->route             = '/transactional-mail/(?P<type>[a-zA-Z_-]+)/status';
    }


    public function process_api_call() {
        $type      = $this->get_sanitized_arg( 'type' ); // Transactional mail type or 'all' for all transactional mails
        if ( empty( $type ) ) {
            return $this->error_response( __( 'Invalid or empty transactional mail type', 'wp-marketing-automations-pro' ), null, 400 );
        }

        $status         = $this->get_sanitized_arg( 'status', 'bool' );
        $this->response_code = 200;
        if ( ! isset( BWFCRM_Core()->transactional_mails ) ) {
	        return $this->error_response( __( 'Unable to find transactional mail', 'wp-marketing-automations-pro' ), null, 400 );
        }
		/** @var  $mail_class BWFCRM_Transactional_Mail_Loader */
        $mail_class = BWFCRM_Core()->transactional_mails;

        $result =  $mail_class->update_transactional_mail_status( $type, $status );

        if ( ! $result ) {
            return $this->error_response( __( 'Unable to update transactional mail status', 'wp-marketing-automations-pro' ), null, 400 );
        }

        return $this->success_response( [], __( 'Transactional mail status updated', 'wp-marketing-automations-pro' ) );
    }
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Update_Transactional_Mail_Status' );