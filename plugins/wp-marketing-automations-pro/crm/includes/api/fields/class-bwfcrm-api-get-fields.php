<?php

class BWFCRM_Api_Get_Fields extends BWFCRM_API_Base
{

    public static $ins;
    public $total_count = 0;

    public function __construct()
    {
        parent::__construct();
        $this->method = WP_REST_Server::READABLE;
        $this->route = '/fields';
        $this->response_code = 200;
    }

    public static function get_instance()
    {
        if (null === self::$ins) {
            self::$ins = new self();
        }

        return self::$ins;
    }

    public function process_api_call()
    {
        $type = isset($this->args['type']) ? $this->get_sanitized_arg('type', 'text_field', $this->args['type']) : null;
        $all_fields = isset($this->args['all']) ? $this->get_sanitized_arg('all', 'text_field', $this->args['all']) : false;
        $fields = BWFCRM_Fields::get_custom_fields( null, $all_fields ? 1 : null, null, $all_fields ? false : true, $all_fields ? null : 1, $type);
        $fields = BWFAN_PRO_Common::get_sorted_fields( $fields );
        $this->total_count = count($fields);

        return $this->success_response($fields, __(empty($fields) ? 'No Fields found.' : 'Got all fields', 'wp-marketing-automations-pro'));
    }


    public function get_result_total_count()
    {
        return $this->total_count;
    }
}


BWFCRM_API_Loader::register('BWFCRM_Api_Get_Fields');