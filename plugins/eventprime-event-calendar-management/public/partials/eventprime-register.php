<?php
defined( 'ABSPATH' ) || exit;
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
//print_r($atts);die;
$args = $atts;
wp_enqueue_style( 'ep-user-select2-css' );
wp_enqueue_style( 'ep-user-views-custom-css' );
wp_enqueue_script( 'ep-user-select2-js' );
wp_enqueue_script( 'ep-user-views-js' );
wp_localize_script(
    'ep-user-views-js', 
    'ep_frontend', 
    array(
        '_nonce'                => wp_create_nonce( 'ep-frontend-nonce' ),
        'ajaxurl'               => admin_url( 'admin-ajax.php' ),
        'nonce_error'           => esc_html__( 'Please refresh the page and try again.', 'eventprime-event-calendar-management' ),
        'delete_event_confirm'  => esc_html__( 'Are you sure you want to delete this event?', 'eventprime-event-calendar-management' )
    )
);
$themepath = $ep_requests->eventprime_get_ep_theme('register-tpl');
include $themepath;