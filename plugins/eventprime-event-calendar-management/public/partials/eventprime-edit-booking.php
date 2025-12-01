<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$booking_data = array();$previous_event_url = '';
if( isset( $_POST['action'] ) && 'edit_booking' == sanitize_text_field( $_POST['action'] ) ) {
    $booking_data['ep_nonce_verified'] = false;
    if( wp_verify_nonce( $_POST['ep_edit_event_booking_nonce'], 'ep_edit_event_booking_action' ) ) {
        $booking_data['ep_nonce_verified'] = true;
        if( ! empty( $_POST['booking_id'] ) ) {
            $booking_id = absint( $_POST['booking_id'] );
            $single_booking = $ep_functions->load_booking_detail( $booking_id );
            if( ! empty( $single_booking->em_user ) ) {
                if( $single_booking->em_user == get_current_user_id() ) {
                    $booking_data['booking_data'] = $single_booking;
                    $booking_data = apply_filters( 'ep_booking_edit_booking_data', $booking_data, $_POST );
                }
            }
        }
    }

    wp_enqueue_style(
        'ep-booking-checkout-style',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-booking-checkout.css',
        false, EVENTPRIME_VERSION
    );
    wp_enqueue_script(
        'ep-event-booking-script',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-event-booking.js',
        array( 'jquery' ), EVENTPRIME_VERSION
    );
    $checkout_text = $ep_functions->ep_global_settings_button_title('Checkout');
    wp_localize_script(
        'ep-event-booking-script', 
        'ep_event_booking', 
        array(
            'ajaxurl'                         => admin_url( 'admin-ajax.php' ),
            'confirm_booking_text'            => esc_html__( 'Confirm Booking', 'eventprime-event-calendar-management' ),
            'checkout_text'                   => $checkout_text,
            'flush_booking_timer_nonce'       => wp_create_nonce( 'flush_event_booking_timer_nonce' ),
            'booking_item_expired'            => esc_html__( 'Your cart has expired. Redirecting..', 'eventprime-event-calendar-management' ),
            'previous_event_url'              => $previous_event_url,
            'event_page_url'                  => esc_url( get_permalink( $ep_functions->ep_get_global_settings( 'event_page' ) ) ),
            'is_payment_method_enabled'       => $ep_functions->em_is_payment_gateway_enabled(),
            'booking_data'                    => $booking_data,
            'enabled_guest_booking'           => $ep_functions->ep_enabled_guest_booking(),
            'enabled_woocommerce_integration' => $ep_functions->ep_enabled_woocommerce_integration(),
            'enabled_woocommerce_checkout'    => $ep_functions->ep_enabled_woocommerce_checkout(),
        )
    );
    $args =(object)$booking_data;
    if( empty( $args->ep_nonce_verified ) || empty( $args->booking_data ) ) {?>
        <div class="ep-alert ep-alert-warning ep-mt-3">
            <?php esc_html_e( 'No data found.', 'eventprime-event-calendar-management' ); ?>
        </div><?php
    } 
    else{  
        $themepath = $ep_requests->eventprime_get_ep_theme('edit-booking-tpl');
        include $themepath;
    }
}
?>