<?php

class BWFCRM_Api_Get_Single_Export_Action extends BWFCRM_API_Base {

    public static $ins;

    public static function get_instance() {
        if ( null === self::$ins ) {
            self::$ins = new self();
        }

        return self::$ins;
    }

    public function __construct() {
        parent::__construct();
        $this->method       = WP_REST_Server::EDITABLE;
        $this->route        = '/export/action';
        $this->request_args = array(
            'type' => array(
                'description' => __( 'Export type', 'wp-marketing-automations-pro' ),
                'type'        => 'string',
            ),
            'action' => array(
                'description' => __( 'Export action', 'wp-marketing-automations-pro' ),
                'type'        => 'string',
            ),
        );
    }

    public function default_args_values() {
        return array( 'type' => '' );
    }

    public function process_api_call() {

        $this->response_code = 404;

        /** if isset type param **/
        $type   = $this->get_sanitized_arg( 'type', 'text_field' );
        $action = $this->get_sanitized_arg( 'action', 'text_field' );

        if ( $type === '' || $action === '' ) {
            $response = __( 'Exporter mandatory options not passed', 'wp-marketing-automations-pro' );
            return $this->error_response( $response );
        }

        /** @var  $exporter_registered */
        $exporter_registered = BWFCRM_Core()->single_exporter->get_exporters();

        // check for export type registered
        if ( ! isset( $exporter_registered[ $type ] ) ) {
            $response            = __( 'Exporter type is not found', 'wp-marketing-automations-pro' );
            return $this->error_response( $response );
        }
        $action_status = [
            'status' => false,
        ];
        /** Export action handler */
        switch( $action ){
            case 'start':
                // add user data and start scheduler
                $action_status = BWFCRM_Core()->single_exporter->bwfcrm_start_export( $type, get_current_user_id() );
                break;
            case 'cancel':
                // remove user data and unset scheduler
                $action_status = BWFCRM_Core()->single_exporter->bwfcrm_end_export( $type, get_current_user_id() );
                break;
        }

        $this->response_code = 200;
        return $this->success_response( $action_status );
    }

}

BWFCRM_API_Loader::register( 'BWFCRM_Api_Get_Single_Export_Action' );