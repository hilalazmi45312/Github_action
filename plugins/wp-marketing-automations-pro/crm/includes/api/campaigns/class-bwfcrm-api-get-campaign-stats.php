<?php
/**
 * Campaign Get API file
 *
 * @package BWFCRM_API_Base
 */

/**
 * Campaign Stats Get API class
 */
class BWFCRM_API_Get_Campaign_Stats extends BWFCRM_API_Base {

    /**
     * BWFCRM_Core obj
     *
     * @var BWFCRM_Core
     */
    public static $ins;

    /**
     * Return class instance
     */
    public static function get_instance() {
        if ( null === self::$ins ) {
            self::$ins = new self();
        }

        return self::$ins;
    }

    /**
     * Class constructor
     */
    public function __construct() {
        parent::__construct();
        $this->method       = WP_REST_Server::READABLE;
        $this->route        = '/broadcast/(?P<campaign_id>[\\d]+)/stats';
        $this->request_args = array(
            'campaign_id' => array(
                'description' => __( 'Broadcast ID to retrieve', 'wp-marketing-automations-pro' ),
                'type'        => 'integer',
            ),
        );
    }

    /**
     * Default arg.
     */
    public function default_args_values() {
        return array(
            'campaign_id' => 0,
        );
    }

    /**
     * API callback
     */
    public function process_api_call() {
        $campaign_id = $this->get_sanitized_arg( 'campaign_id', 'key' );

        if ( absint( $campaign_id ) > 0 ) {
            $campaign_data       = BWFCRM_Core()->campaigns->get_campaign_open_click_analytics($campaign_id);
            return $this->success_response( $campaign_data );
        } else {
            return $this->error_response( __( 'Invalid broadcast ID given', 'wp-marketing-automations-pro' ), null, 400 );

        }
    }
}

BWFCRM_API_Loader::register( 'BWFCRM_API_Get_Campaign_Stats' );
