<?php
$ep_requests = new EP_Requests;
$booking_data = array();$booking_ticket_data = array();$previous_event_url = '';
$ep_functions = new Eventprime_Basic_Functions;
if( ! empty( $_POST ) && isset( $_POST['ep_event_booking_data'] ) && ! empty( $_POST['ep_event_booking_data'] ) ) {
    if( '0' === get_option( 'ep_event_booking_timer_start' ) ) {
        delete_option( 'ep_event_booking_timer_start' );
        $_POST = array();
    } else if( FALSE === get_option( 'ep_event_booking_timer_start' ) ) {
        add_option( 'ep_event_booking_timer_start', 1 );
    }
    $ep_event_offer_data = [];
    $ep_event_booking_data = json_decode( stripslashes( $_POST['ep_event_booking_data'] ) );
    
    if( ! empty( $ep_event_booking_data->ticket ) ) {
        $booking_data['tickets'] = json_decode( $ep_event_booking_data->ticket );
    }
    if(!empty($booking_data['tickets']))
    {
        $desire_field = array('offer_text','total_offer_discount_text','formatted_subtotal','applied_offer_uid','applied_offer_obj');
        foreach($booking_data['tickets'] as $ticket)
        {
            $response = (object)$ep_functions->eventprime_update_cart_response($ticket->id, $ticket->qty);
            $newticket = new stdClass();
            // Loop through the response to match and filter the data
            foreach ($response as $key => $value) {
                // Map the response fields to the new object based on matching criteria
                if (!in_array($key, $desire_field)) {
                    $newticket->$key = $value; // Copy the value from the response to the new object
                }
                elseif($key=='applied_offer_obj')
                {
                    if(is_array($value))
                    {
                        foreach($value as $val)
                        {
                            $ep_event_offer_data[] = (object)$val;
                        }
                    }
                    
                }
            }
            
            $booking_ticket_data[] = $newticket;
        }

    }

    $booking_data['tickets'] = $booking_ticket_data;
    $booking_data['ep_event_offer_data'] = $ep_event_offer_data;
    
    $ep_event_offer_data = isset( $_POST['ep_event_offer_data'] ) ? json_decode( stripslashes( $_POST['ep_event_offer_data'] ) ) : '';
    if( ! empty($ep_event_offer_data) ) {
        $booking_data['ep_event_offer_data'] = $ep_event_offer_data;
    }
    if( ! empty( $ep_event_booking_data->event ) ) {
        $event_id = base64_decode( $ep_event_booking_data->event );
        $booking_data['event'] = $ep_functions->get_single_event( $event_id );
        $previous_event_url = $booking_data['event']->event_url;
    }
    // add data in booking data
    $is_able_to_purchase = $ep_functions->ep_check_event_restrictions( $booking_data['event'] );
    $max_ticket_reached_message = ( ! empty($booking_data['event']->em_event_max_tickets_reached_message))?$booking_data['event']->em_event_max_tickets_reached_message:esc_html__('You have already reached the maximum ticket limit for this event and cannot purchase additional tickets.','eventprime-event-calendar-management');

    $booking_data = apply_filters( 'ep_booking_detail_add_booking_data', $booking_data, $ep_event_booking_data );
}

$register_fname = $ep_functions->ep_get_global_settings( 'checkout_register_fname' );
$register_lname = $ep_functions->ep_get_global_settings( 'checkout_register_lname' );
$register_username = $ep_functions->ep_get_global_settings( 'checkout_register_username' );
$register_email = $ep_functions->ep_get_global_settings( 'checkout_register_email' );
$register_password = $ep_functions->ep_get_global_settings( 'checkout_register_password' );
$account_settings = array(
    'fname_label'    =>  isset($register_fname['label']) && !empty($register_fname['label']) ? $register_fname['label'] : esc_html__('First Name','eventprime-event-calendar-management'),
    'lname_label'    =>  isset($register_lname['label']) && !empty($register_lname['label']) ? $register_lname['label'] : esc_html__('last Name','eventprime-event-calendar-management'),
    'username_label' =>  isset($register_username['label']) && !empty($register_username['label']) ? $register_username['label'] : esc_html__('Username','eventprime-event-calendar-management'),
    'email_label'    =>  isset($register_email['label']) && !empty($register_email['label']) ? $register_email['label'] : esc_html__('Email','eventprime-event-calendar-management'),
    'password_label' =>  isset($register_password['label']) && !empty($register_password['label']) ? $register_password['label'] : esc_html__('Password','eventprime-event-calendar-management')
);
$booking_data['account_form'] = (object)$account_settings;

$create_account_validation = array(
    'fname_required'     => sprintf(__("%s is required.", 'eventprime-event-calendar-management'), $booking_data['account_form']->fname_label),
    'lname_required'     => sprintf(__("%s is required.", 'eventprime-event-calendar-management'), $booking_data['account_form']->lname_label),
    'email_required'     => sprintf(__("%s is required.", 'eventprime-event-calendar-management'), $booking_data['account_form']->email_label),
    'username_required'  => sprintf(__("%s is required.", 'eventprime-event-calendar-management'), $booking_data['account_form']->username_label),
    'password_required'  => sprintf(__("%s is required.", 'eventprime-event-calendar-management'), $booking_data['account_form']->password_label),
    'email_duplicate'    => sprintf(__("%s is already exists.", 'eventprime-event-calendar-management'), $booking_data['account_form']->email_label),
    'username_duplicate' => sprintf(__("%s is already exist.", 'eventprime-event-calendar-management'), $booking_data['account_form']->username_label)
);


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
$default_payment_processor = $ep_functions->ep_get_global_settings( 'default_payment_processor' );
if( empty( $default_payment_processor ) ) {
    $default_payment_processor = 'paypal_processor';
}
// check for extensions
if( ! empty( $default_payment_processor ) && 'paypal_processor' !== $default_payment_processor ) {
    $extensions = $ep_functions->ep_get_activate_extensions();
    if( ! in_array( 'Eventprime_Offline', $extensions ) && ! in_array( 'Eventprime_Event_Stripe', $extensions ) ) {
        $default_payment_processor = 'paypal_processor';
    }
    // check if other payments options disabled
    if( empty( $ep_functions->ep_get_global_settings( 'offline_processor' ) ) && empty( $ep_functions->ep_get_global_settings( 'stripe_processor' ) ) ) {
        $default_payment_processor = 'paypal_processor';
    }
}
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
        'create_account_validation'       => $create_account_validation,
        'event_registration_form_nonce'   => wp_create_nonce( 'event-registration-form-nonce' ),
        'reload_user_area_nonce'          => wp_create_nonce( 'event-reload-checkout-user-area' ),
        'enable_captcha_registration'     => $ep_functions->ep_enabled_reg_captcha(),
        'default_payment_processor'       => $default_payment_processor,
        'enabled_woocommerce_checkout'    => $ep_functions->ep_enabled_woocommerce_checkout(),
    )
);
$args = (object)$booking_data;
$themepath = $ep_requests->eventprime_get_ep_theme('checkout-tpl');
include $themepath;