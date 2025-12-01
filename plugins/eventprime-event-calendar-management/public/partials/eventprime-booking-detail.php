<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$global_settings = new Eventprime_Global_Settings();
$options['global'] = $global_settings->ep_get_settings();
$booking_data = array();
        if( isset( $_GET['order_id'] ) && ! empty( $_GET['order_id'] ) ) {
            $order_id = absint( $_GET['order_id'] );
            $booking_data = $ep_functions->load_booking_detail( $order_id );
        }
        ob_start();

        wp_enqueue_style(
            'ep-booking-checkout-style',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-booking-checkout.css',
            false, EVENTPRIME_VERSION
        );

        wp_enqueue_script(
            'ep-event-booking-detail-script',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-event-booking-detail.js',
            array( 'jquery' ), EVENTPRIME_VERSION
        );
        wp_localize_script(
            'ep-event-booking-detail-script', 
            'ep_event_booking_detail', 
            array(
                'ajaxurl'              => admin_url( 'admin-ajax.php' ),
                'booking_cancel_nonce' => wp_create_nonce( 'event-booking-cancellation-nonce' ),
                'booking_print_ticket_nonce' => wp_create_nonce( 'event-booking-print-ticket-nonce' )
            )
        );

        // enqueue custom scripts and styles from extension
        do_action( 'ep_bookingh_detail_enqueue_custom_scripts' );
        $args = (object)$booking_data;
        
        ?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('booking-detail-tpl');
include $themepath;
?>
</div>