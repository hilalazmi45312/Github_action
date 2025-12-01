<?php

class CocartController
{
    public static function get_cart()
    {
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
            ),
            'timeout' => 30
        );

        $response = wp_remote_get(home_url('wp-json/cocart/v2/cart'), $args);
        $body = wp_remote_retrieve_body($response);
        error_log('Cocart Response Body: ' . $body); // Log the response body for debugging
    }
}
