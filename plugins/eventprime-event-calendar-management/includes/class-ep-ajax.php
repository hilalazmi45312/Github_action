<?php
/**
 * EventPrime Ajax Event Handler Class.
 */
defined( 'ABSPATH' ) || exit;

class EventM_Ajax_Service {
    
    public function cancel_current_booking_process() {
        // Add security checks 
        if( wp_verify_nonce( $_POST['security'], 'event-registration-form-nonce' ) ) {
            $event_id = absint( $_POST['event_id'] ); 
            $ticket_data = json_decode( stripslashes( $_POST['ticket_data'] ) );
    
            $event_seat_data = get_post_meta( $event_id, 'em_seat_data', true );
            if( ! empty( $event_seat_data ) ) { 
                // wp_send_json_success('seated event'); 
    
                if ( class_exists( 'EventM_Live_Seating_List_Controller' ) ) {
                    $seating_controller = new EventM_Live_Seating_List_Controller;
                }
                $em_ls_seat_plan_id = get_post_meta( $event_id, 'em_ls_seat_plan', true ); 
                $plan_color_data = $seating_controller->get_plan_colors_data( $em_ls_seat_plan_id );
    
                $event_seat_data = maybe_unserialize( $event_seat_data );
                foreach( $ticket_data as $tickets ) {
                    if( ! empty( $tickets->seats ) ) {
                        $ticket_seats = $tickets->seats;
                        foreach( $ticket_seats as $seats_data ) {
                            $ticket_area_id = $seats_data->area_id;
                            if( $event_seat_data->{$ticket_area_id} ) {
                                $ticket_seat_data = $seats_data->seat_data;
                                if( ! empty( $ticket_seat_data ) ) { 
                                    foreach( $ticket_seat_data as $tsd ) {
                                        if( ! empty( $tsd->uid ) ) {
                                            $seat_uid = $tsd->uid;
                                            $seat_uid = explode( '-', $seat_uid );
                                            $row_index = $seat_uid[0];
                                            $col_index = $seat_uid[1];
                                            if( ! empty( $event_seat_data->{$ticket_area_id}->seats[$row_index] ) ) {
                                                    
                                                    foreach ( $event_seat_data->{$ticket_area_id}->seats[$row_index] as $key => $seat ) {
                                                        if ( $seat->col == $col_index ) {
                                                            if( $seat->type == 'hold' ) {
                                                                $seat->type = 'general';
                                                                $seat->hold_time = '';
                                                                $seat_available_color = $plan_color_data['seat_available_color'];
                                                                $seat->seatColor = $seat_available_color;
                                                        
                                                                $event_seat_data->{$ticket_area_id}->seats[$row_index][$key]  = $seat;
                                                            }
                                                        }
                                                    }
                                                   
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
    
               $update =  update_post_meta( $event_id, 'em_seat_data', maybe_serialize( $event_seat_data ) );
               wp_send_json_success($update);
    
            } else {
                wp_send_json_success('not a seated event'); 
            }
        } else {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-seating' ) ) );
        }
        
        
    }

    /**
     * save checkout field
     */
    public function save_checkout_field() {
        check_ajax_referer( 'save-checkout-fields', 'security' );

        $response = array();
        parse_str( wp_unslash( $_POST['data'] ), $data );
        if( ! isset( $data['em_checkout_field_label'] ) || empty( $data['em_checkout_field_label'] ) ) {
            $response['message'] = esc_html__( 'Label should not be empty', 'eventprime-event-calendar-management' );
            wp_send_json_error($response);
        }
        if( ! isset( $data['em_checkout_field_type'] ) || empty( $data['em_checkout_field_type'] ) ) {
            $response['message'] = esc_html__( 'Type should not be empty', 'eventprime-event-calendar-management' );
            wp_send_json_error( $response );
        }
        try{
            
            $dbhandler = new EP_DBhandler;
            $table_name = 'CHECKOUT_FIELDS';
            $save_data = array();
            $save_data['label'] = sanitize_text_field( $data['em_checkout_field_label'] );
            $save_data['type'] = sanitize_text_field( $data['em_checkout_field_type'] );
            // for option data
            $save_data['option_data'] = '';
            $option_data = ( ! empty( $data['ep_checkout_field_option_value'] ) ? $data['ep_checkout_field_option_value'] : '' );
            // set selected value
            if( isset( $data['ep_checkout_field_option_value_selected'] ) ) {
                $option_index = $data['ep_checkout_field_option_value_selected'];
                $option_data[$option_index]['selected'] = 1;
            }
            if( ! empty( $option_data ) ) {
                $save_data['option_data'] = maybe_serialize( $option_data );
            }
            if( empty( $data['em_checkout_field_id'] ) ) {
                $save_data['priority'] = 1;
                $save_data['status'] = 1;
                $save_data['created_by'] = get_current_user_id();
                $save_data['created_at'] = wp_date( "Y-m-d H:i:s", time() );
                $field_id = $dbhandler->insert_row($table_name, $save_data);
                $response['message'] = esc_html__( 'Field Saved Successfully.', 'eventprime-event-calendar-management' );
                // format created_at to display after saving it in DB 
                $wp_saved_format = get_option('date_format').' '.get_option('time_format');
                $format = !empty($wp_saved_format) ? $wp_saved_format : "Y-m-d H:i:s"; 
                $save_data['created_at'] = wp_date( $format, time() );
            } else{
                $field_id = absint( $data['em_checkout_field_id'] );
                $save_data['updated_at'] = wp_date( "Y-m-d H:i:s", time() );
                $save_data['last_updated_by'] = get_current_user_id();
                $result = $dbhandler->update_row($table_name,'id', $field_id, $save_data);
                $response['message'] = esc_html__( 'Field Updated Successfully.', 'eventprime-event-calendar-management' );
            }
            $save_data['field_id'] = $field_id;
            $response['field_data'] = $save_data;
        } catch( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}

        wp_send_json_success( $response );
    }

    // delete the checkout field
    public function delete_checkout_field(){
        check_ajax_referer( 'delete-checkout-fields', 'security' );

        $response = array();
        if( isset( $_POST['field_id'] ) && ! empty( $_POST['field_id'] ) ) {
            $id = $_POST['field_id'];
            $dbhandler = new EP_DBhandler;
            $table_name = 'CHECKOUT_FIELDS';
            $get_field_data = $dbhandler->get_all_result($table_name,'*',array('id'=>$id));
            if( ! empty( $get_field_data ) && count( $get_field_data ) > 0 ) {
                $dbhandler->remove_row($table_name,'id',$id);
                $response['message'] = esc_html__( 'Field Deleted Successfully.', 'eventprime-event-calendar-management' );
            } else{
                $response['message'] = esc_html__( 'No Record Found.', 'eventprime-event-calendar-management' );
                wp_send_json_error( $response );
            }
        } else{
            $response['message'] = esc_html__( 'Some Data Missing.', 'eventprime-event-calendar-management' );
            wp_send_json_error( $response );
        }
         
        wp_send_json_success( $response );
    }
    
    public function submit_payment_setting(){  
        $payment_gateway = apply_filters( 'ep_payments_gateways_list', array() );
        $global_settings = new Eventprime_Global_Settings;
        $global_settings_data = $global_settings->ep_get_settings();
        $form_data = $_POST;
        if( isset( $form_data ) && isset( $form_data['em_payment_type'] ) ) {
            if( $form_data['em_payment_type'] == 'basic' ) {
                $payment_method = isset( $form_data['payment_method'] ) && ! empty( $form_data['payment_method'] ) ? sanitize_text_field( $form_data['payment_method'] ) : '';
                $method_status = $form_data['method_status'];
                $nonce = wp_create_nonce('ep_settings_tab');
                if( ! empty( $method_status ) ) {
                    if( $payment_method == 'paypal_processor' ) {
                        if( empty( $global_settings_data->paypal_client_id ) && $method_status == 1 ) {
                            $url = add_query_arg( array( 'settings-updated' => false, 'tab'=> 'payments', 'section'=> 'paypal','tab_nonce'=>$nonce ), admin_url().'edit.php?post_type=em_event&page=ep-settings' );
                            wp_send_json_success( array( 'url' => $url ) );
                        }
                    }
                    if( $payment_method == 'stripe_processor' ) {
                        if( ( empty( $global_settings_data->stripe_api_key ) || empty( $global_settings_data->stripe_pub_key ) ) && $method_status == 1 ) {
                            $url = add_query_arg( array( 'settings-updated' => false, 'tab'=> 'payments', 'section'=> 'stripe','tab_nonce'=>$nonce ), admin_url().'edit.php?post_type=em_event&page=ep-settings' );
                            wp_send_json_success( array( 'url' => $url ) );
                        }
                    }
                }
                if( ! empty( $payment_method ) ) {
                    $global_settings_data->$payment_method = $method_status;
                }
            }
            $global_settings->ep_save_settings( $global_settings_data );
        }

        $method = ucfirst( explode( '_', $payment_method )[0] );
        
        $message = $method . ' ' . esc_html__( 'is activated.', 'eventprime-event-calendar-management' );
        if( $method_status == 0 ) {
            $message = $method . ' ' . esc_html__( 'is deactivated.', 'eventprime-event-calendar-management' );
        }
        
        wp_send_json_success( array( 'url' => '', 'message' => $message ) );
        die();
    }
    
    public function submit_login_form(){
        $user_controller = new EventM_User_Controller();
        $response = $user_controller->ep_handle_login();
        wp_send_json_success($response);
        die();
    }
    
    public function submit_register_form(){
        $user_controller = new EventM_User_Controller();
        $response = $user_controller->ep_handle_registration();
        wp_send_json_success($response);
        die();
    }
    
    /*
     * Load more Event Types
     */
    public function load_more_event_types(){
        $controller = new Eventprime_Basic_Functions;
        $response = $controller->get_event_types_loadmore();
        wp_send_json_success($response);
        die();
    }
    
    /*
     * Load More Event Performer
     */
    public function load_more_event_performer(){
        $controller = new Eventprime_Basic_Functions;
        $response = $controller->get_event_performer_loadmore();
        wp_send_json_success($response);
        die();
    }
    
    /*
     * Load More Event Venue
     */
    public function load_more_event_venue(){
        $controller = new Eventprime_Basic_Functions;
        $response = $controller->get_event_venue_loadmore();
        wp_send_json_success($response);
        die();
    }
    
    /*
     * Load More Event Organizers
     */
    public function load_more_event_organizer(){
        $controller = new Eventprime_Basic_Functions;
        $response = $controller->get_event_organizer_loadmore();
        wp_send_json_success($response);
        die();
    }

     /*
     * Load More Events
     */
    public function load_more_events(){
        $controller = new Eventprime_Basic_Functions;
        $response = $controller->get_events_loadmore();
        wp_send_json_success($response);
        die();
    }   
    /**
     * Load single event page on chenge of child event date
     */
    public function load_event_single_page() {
        check_ajax_referer( 'single-event-data-nonce', 'security' );

        if( isset( $_POST['event_id'] ) && ! empty( $_POST['event_id'] ) ) {
            $event_id = absint( $_POST['event_id'] );
            $event_controller = new Eventprime_Basic_Functions;
            $single_event = $event_controller->ep_load_other_date_event_detail( $event_id );
            //$single_event->venue_other_events = EventM_Factory_Service::get_upcoming_event_by_venue_id( $single_event->em_venue, array( $single_event->id ) );
            if( ! empty( $single_event ) ) {
                wp_send_json_success( $single_event );
            } else{
                wp_send_json_error( array( 'error' => esc_html__( 'Data Not Found', 'eventprime-event-calendar-management' ) ) );
            }
            wp_die();
        }
        wp_send_json_error( array( 'error' => esc_html__( 'Data Not Found', 'eventprime-event-calendar-management' ) ) );
    }

    /**
     * Save event booking
     */
    public function save_event_booking() {
        if( ! empty( $_POST['data'] ) ) {
            $ep_functions = new Eventprime_Basic_Functions;
            $sanitizer = new EventPrime_sanitizer;
            parse_str( wp_unslash( $_POST['data'] ), $data );
            if(isset($_POST['offer_data']))
            {
                $offer_data = json_decode( wp_unslash( $_POST['offer_data'] ));
            }
            else
            {
                $offer_data = array();
            }
            $result = array( 'success' => 1, 'msg' => '' );
            $checkpoint = apply_filters('ep_handle_checkout_additional_check',$result, $data);
            if(isset($checkpoint['success']) && empty($checkpoint['success'])){
                wp_send_json_error( array( 'error' =>  $checkpoint['msg']) );
                die();
            }
            if( wp_verify_nonce( $data['ep_save_event_booking_nonce'], 'ep_save_event_booking' ) ) {
                
                if(isset($data['ep_event_booking_ticket_data']))
                {
                    $ticket_data = json_decode( $data['ep_event_booking_ticket_data'] );
                    //print_r($ticket_data);
                    if(isset($ticket_data[0]->id))
                    {
                       $ticket_data_object = $ep_functions->ep_get_ticket_data($ticket_data[0]->id);
                       if(empty($ticket_data_object))
                       {
                           wp_send_json_error( array( 'error' => esc_html__( 'Something went wrong.', 'eventprime-event-calendar-management' ) ) );
                           die;
                       }
                    }
                    else
                    {
                        wp_send_json_error( array( 'error' => esc_html__( 'Something went wrong.', 'eventprime-event-calendar-management' ) ) );
                        die;
                    }
                }
                else
                {
                    wp_send_json_error( array( 'error' => esc_html__( 'Something went wrong.', 'eventprime-event-calendar-management' ) ) );
                    die;
                }
                if(!isset($data['ep_event_booking_event_fixed_price']))
                {
                    $data['ep_event_booking_event_fixed_price'] = 0;
                }
                $current_user = wp_get_current_user();
                //echo 'data 1';
                //print_r($data);
                if( class_exists("Eventprime_Admin_Attendee_Booking")){
                    if(empty( get_option( 'ep_set_admin_aab_'.$current_user->ID )))
                    {
                        $data = $ep_functions->ep_recalculate_and_verify_the_cart_data($data,$offer_data);
                    }
                   
                }
                else
                {
                    $data = $ep_functions->ep_recalculate_and_verify_the_cart_data($data,$offer_data);
                }
                
                if($data=='ticket_sold')
                {
                    wp_send_json_error( array( 'error' => esc_html__( 'One or more ticket types for this event are sold out. Please select from the available tickets or check back later for availability.', 'eventprime-event-calendar-management' ) ) );
                    die;
                }
                
                $enable_gdpr = $ep_functions->ep_get_global_settings( 'enable_gdpr_tools' );
                $show_checkbox = $ep_functions->ep_get_global_settings('show_gdpr_consent_checkbox');
                if($enable_gdpr==1 && $show_checkbox==1)
                {
                    if(!isset($data['ep_gdpr_consent']) || empty($data['ep_gdpr_consent']))
                    {
                        wp_send_json_error( array( 'error' => esc_html__( 'You must accept the Privacy Policy.', 'eventprime-event-calendar-management' ) ) );
                        die;
                    }
                }
                
                $woocommerce_validate = $ep_functions->ep_validate_woocommerce_product_data($data);
                if($woocommerce_validate===false)
                {
                    wp_send_json_error( array( 'error' => esc_html__( 'WooCommerce Product calculation missed matched.', 'eventprime-event-calendar-management' ) ) );
                    die;
                }
                //var_dump($woocommerce_validate);die;
                //echo 'data 2';
                //print_r($data);die;
                // If Seated Venue then verify if seats in the ticekt data are sold or not.
                // Check it after ep_recalculate_and_verify_the_cart_data() as $data is set false later. (Refractor it!!!) 
                $incoming_ticket_data = json_decode( $data['ep_event_booking_ticket_data'] ); 
                //$ep_functions->epd($incoming_ticket_data);
                $event_seats_current_details = maybe_unserialize( get_post_meta( absint( $data['ep_event_booking_event_id'] ), 'em_seat_data', true  ) ); 
                foreach ( $incoming_ticket_data as $single_ticket_type ) {
                    $single_ticket_type_id = $single_ticket_type->id; 
                    if(isset($single_ticket_type->seats) && !empty($single_ticket_type->seats))
                    {
                        $single_ticket_type_seats_data = $single_ticket_type->seats; 
                        foreach ( $single_ticket_type_seats_data as $ticket_area_data ) {
                            $area_id = $ticket_area_data->area_id; 
                            foreach ( $ticket_area_data->seat_data as $ticket_seat ) {

                                if( ! empty( $ticket_seat->uid ) ) {
                                    $ticket_seat_uid = $ticket_seat->uid;

                                    // If seat has been sold then throw error. ***** 
                                    if(isset($event_seats_current_details) && !empty($event_seats_current_details))
                                    {
                                        foreach ( $event_seats_current_details->{$area_id}->seats as $event_seats_data ) {
                                            foreach ( $event_seats_data as $event_seats_row ) {
                                                if ( ($event_seats_row->uniqueIndex == $ticket_seat_uid) && ($event_seats_row->type == 'sold') ) {
                                                    $data = false;  
                                                }
                                            }
                                        }
                                    }

                                }
                            }

                        }
                    }
                }
                
                if($data===false)
                {
                    wp_send_json_error( array( 'error' => esc_html__( 'Something went wrong.', 'eventprime-event-calendar-management' ) ) );
                    die;
                }
                $event_id       = absint( $data['ep_event_booking_event_id'] );
                $event_name     = get_the_title( $event_id );
                $user_id        = absint( $data['ep_event_booking_user_id'] );
                $payment_method = ! empty( $data['payment_processor'] ) ? sanitize_text_field( $data['payment_processor'] ) : 'paypal';
                if( ! isset( $data['ep_event_booking_total_price'] ) || empty( $data['ep_event_booking_total_price'] ) ) {
                    $payment_method = 'none';
                }
                
                $post_status = 'failed';
                            
                if ( class_exists("Eventprime_Admin_Attendee_Booking") && !empty( get_option( 'ep_set_admin_aab_'.$current_user->ID )) ) {
                    $post_status = 'completed'; 
                    delete_option( 'ep_set_admin_aab_'.$current_user->ID );
                } 
                
                if( isset( $data['ep_rg_field_email'] ) && ! empty( $data['ep_rg_field_email'] ) ) {
                    if( isset($data['ep_rg_field_user_name'] ) && ! empty( $data['ep_rg_field_user_name'] ) ) {
                        $user_controller = new EventM_User_Controller();
                        $user_data = new stdClass();
                        $user_data->email = sanitize_text_field($data['ep_rg_field_email']);
                        $user_data->username = sanitize_text_field($data['ep_rg_field_user_name']);
                        $user_data->fname = isset($data['ep_rg_field_first_name']) ? sanitize_text_field($data['ep_rg_field_first_name']) : '';
                        $user_data->lname = isset($data['ep_rg_field_last_name']) ? sanitize_text_field($data['ep_rg_field_last_name']) : '';
                        $user_data->password = sanitize_text_field($data['ep_rg_field_password']);
                        unset($data['ep_rg_field_password']);
                        $user = get_user_by( 'email', $user_data->email );
                        if(!empty($user)){
                            $user_id = $user->ID;
                        }else{
                            $user_id = $user_controller->ep_checkout_registration($user_data);
                        }
                    }
                }
                // add new booking
                $new_post = array(
                    'post_title'  => $event_name,
                    'post_status' => $post_status,
                    'post_type'   => 'em_booking',
                    'post_author' => $user_id,
                );
                $new_post_id = wp_insert_post( $new_post ); // new post id
            
                update_post_meta( $new_post_id, 'em_id', $new_post_id );
                update_post_meta( $new_post_id, 'em_event', $event_id );
                update_post_meta( $new_post_id, 'em_date', current_time( 'timestamp',true ) );
                update_post_meta( $new_post_id, 'em_user', $user_id );
                update_post_meta( $new_post_id, 'em_name', $event_name );
                update_post_meta( $new_post_id, 'em_status', $post_status );
                update_post_meta( $new_post_id, 'em_payment_method', $payment_method );
                if(isset($data['ep_gdpr_consent']))
                {
                    update_post_meta( $new_post_id, 'ep_gdpr_consent', $data['ep_gdpr_consent'] );
                    update_post_meta( $new_post_id, 'ep_gdpr_consent_time', current_time('mysql'));
                }
                if( isset( $_POST['rid'] ) && ! empty( $_POST['rid'] ) ) {
                    update_post_meta( $new_post_id, 'em_random_order_id', sanitize_text_field( $_POST['rid'] ) );
                }
                // order info
                $order_info = array();
                $order_info['tickets']           = json_decode( $data['ep_event_booking_ticket_data'] );
                $order_info['event_fixed_price'] = ( ! empty( $data['ep_event_booking_event_fixed_price'] ) ? (float)$data['ep_event_booking_event_fixed_price'] : 0.00 );
                $order_info['booking_total']     = ( ! empty( $data['ep_event_booking_total_price'] ) ? (float)$data['ep_event_booking_total_price'] : 0.00 );
                $order_info = apply_filters('ep_update_booking_order_info', $order_info, $data);
                update_post_meta( $new_post_id, 'em_order_info', $order_info );
                update_post_meta( $new_post_id, 'em_notes', array() );
                update_post_meta( $new_post_id, 'em_payment_log', array() );
                update_post_meta( $new_post_id, 'em_booked_seats', array() );
                update_post_meta( $new_post_id, 'eventprime_updated_pattern',1);
                $ep_booking_attendee_fields =(isset($data['ep_booking_attendee_fields']))?$sanitizer->sanitize($data['ep_booking_attendee_fields']):array();
                update_post_meta( $new_post_id, 'em_attendee_names', $ep_booking_attendee_fields );
                // check for booking fields data
                $em_booking_fields_data = array();
                if( ! empty( $data['ep_booking_booking_fields'] ) ) {
                    $em_booking_fields_data = $data['ep_booking_booking_fields'];
                }
                update_post_meta( $new_post_id, 'em_booking_fields_data', $em_booking_fields_data );
                $order_key = $ep_functions->ep_encrypt_decrypt_pass('encrypt', 'ep_order_'.$new_post_id);
                update_post_meta( $new_post_id, 'ep_order_key', $order_key );
                
                do_action( 'ep_after_booking_created', $new_post_id, $data );
                
                // if booking total is 0 then confirm booking
                if( $payment_method == 'none' && empty( $order_info['booking_total'] ) ){
                    $data['payment_gateway'] = 'none';
                    $data['payment_status']  = 'completed';
                    $data['total_amount']    = $order_info['booking_total'];
                    $booking_controller      = new EventPrime_Bookings;
                    $booking_controller->confirm_booking( $new_post_id, $data );
                }

                $response                 = new stdClass();
                $response->order_id       = $new_post_id;
                $response->payment_method = $payment_method;
                $response->post_status    = $post_status;

                // Items for paypal order 
                $items = []; 
                $items = $ep_functions->ep_get_paypal_order_items($data); 
                $items = apply_filters('ep_extend_paypal_order_items', $items, $data); 
                $response->items_total = $items['items_total']; 
                $response->items = $items['items']; 

                $response->booking_total  = round( (float)$data['ep_event_booking_total_price'], 2 );
                $response->discount_total = (isset($data['ep_event_booking_total_discount'])) ? round( (float)$data['ep_event_booking_total_discount'], 2 ) : 0;
                // $response->booking_total  = (float)$data['ep_event_booking_total_price'];
                // $response->discount_total = (isset($data['ep_event_booking_total_discount']))?(float)$data['ep_event_booking_total_discount']:0;
                
                $response->item_total     = (float)$data['ep_event_booking_total_tickets'];                
                
                // $redirect                 = esc_url( add_query_arg( array( 'order_id' => $new_post_id ), get_permalink( ep_get_global_settings( 'booking_details_page' ) ) ) );
                $redirect                 = add_query_arg( array( 'order_id' => $new_post_id ), esc_url( get_permalink( $ep_functions->ep_get_global_settings( 'booking_details_page' ) ) ) );
                $response->redirect       = apply_filters( 'ep_booking_redirection_url', $redirect, $new_post_id );
                wp_send_json_success( $response );
            } else{
                wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Data Not Found', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Delete booking timer data from option table
     */
    public function booking_timer_complete() {
        check_ajax_referer( 'flush_event_booking_timer_nonce', 'security' );
        delete_option( 'ep_event_booking_timer_start' );
        $booking_data = json_decode( stripslashes( $_POST['booking_data'] ) );
        
        do_action( 'ep_event_booking_timer_finished', $booking_data );
        wp_send_json_success(true);
    }

    /**
     * Method call from paypal approval
     */
    public function paypal_sbpr() {
        if( isset( $_POST ) && ! empty( $_POST ) ) {
            $ep_functions = new Eventprime_Basic_Functions;
            $data       = $ep_functions->ep_sanitize_input($_POST['data']);
            $booking_id = absint( $_POST['order_id'] );
            $order_info = maybe_unserialize(get_post_meta($booking_id,'em_order_info',true));
            
            if( ! empty( $booking_id ) && ! empty( $data ) && $order_info['booking_total'] == $data['purchase_units'][0]['amount']['value']) {
                $data['payment_gateway'] = 'paypal';
                $data['payment_status']  = strtolower( $data['status'] );
                $data['total_amount']    = $data['purchase_units'][0]['amount']['value'];
                $data['currency']        = $ep_functions->ep_get_global_settings('currency');
                $booking_controller = new EventPrime_Bookings;
                $booking_controller->confirm_booking( $booking_id, $data );
                //$return_url = esc_url( add_query_arg( array( 'order_id' => $booking_id ), get_permalink( ep_get_global_settings( 'booking_details_page' ) ) ) );
                $redirect        = add_query_arg( array( 'order_id' => $booking_id ), esc_url( get_permalink( $ep_functions->ep_get_global_settings( 'booking_details_page' ) ) ) );
                $return_url       = apply_filters( 'ep_booking_redirection_url', $redirect, $booking_id );
        
                $response = array( 'status' => 'success', 'redirect' => $return_url );
                wp_send_json_success($response);
            }
            else
            {
                wp_send_json_error( array( 'error' => esc_html__( 'Something went wrong', 'eventprime-event-calendar-management' ) ) );
            }
        }
    }

    /**
     * Booking cancellation action
     */
    public function event_booking_cancel() {
        if( wp_verify_nonce( $_POST['security'], 'event-booking-cancellation-nonce' ) ) {
            if( isset( $_POST['booking_id'] ) ) {
                $booking_id = absint( $_POST['booking_id'] );
                if( ! empty( $booking_id ) ) {
                    if (is_user_logged_in()) {
                        $current_user_id = get_current_user_id();
                        $booking_controller = new EventPrime_Bookings;
                        $notification = new EventM_Notification_Service();
                        $booking = $booking_controller->load_booking_detail( $booking_id );
                        if( ! empty( $booking ) && $booking->em_user==$current_user_id) {
                            if ( $booking->em_status == 'cancelled' ) {
                                wp_send_json_error( array( 'error' => esc_html__( 'The booking is already cancelled', 'eventprime-event-calendar-management' ) ) );
                            }
                            if( $booking->em_status == 'refunded' ) {
                                wp_send_json_error( array( 'error' => esc_html__( 'The booking can not be cancelled. The amount is already refunded', 'eventprime-event-calendar-management' ) ) );
                            }
                            if( ! empty( $booking->em_user ) && get_current_user_id() != $booking->em_user ) {
                                wp_send_json_error( array( 'error' => esc_html__( 'You are not allowed to cancel this booking', 'eventprime-event-calendar-management' ) ) );
                            }

                            // cancel the booking
                            update_post_meta( $booking->em_id, 'em_status', 'cancelled' );

                            $booking_controller->update_status( $booking_id, 'cancelled' );

                            // send cancellation mail 
                            $notification->booking_cancel( $booking_id );

                            do_action( 'ep_after_booking_cancelled', $booking );

                            wp_send_json_success( array( 'message' => esc_html__( 'Booking Cancelled Successfully', 'eventprime-event-calendar-management' ) ) );
                        } else{
                            wp_send_json_error( array( 'error' => esc_html__( 'Invalid Data', 'eventprime-event-calendar-management' ) ) );
                        }
                    } else{
                        wp_send_json_error( array( 'error' => esc_html__( 'You are not allowed to cancel this booking', 'eventprime-event-calendar-management' ) ) );
                    }
                }
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }
    
    /*
     * Add booking Notes
     */
    public function booking_add_notes(){
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'ep_booking_nonce')) {
            wp_die('Security check failed');
        }
        if( isset( $_POST['booking_id'] ) && isset($_POST['note']) && !empty(trim($_POST['note'])) && current_user_can('manage_options')) {
            $booking_id = absint( $_POST['booking_id'] );
            $note = sanitize_text_field($_POST['note']);
            $booking_controller = new EventPrime_Bookings();
            $response = $booking_controller->add_notes( $booking_id, $note);
            wp_send_json_success( $response );
        }else{
            wp_send_json_error();
        }
    }

    /**
     * Event wishlist action
     */
    public function event_wishlist_action() {
        if( isset($_POST['security']) && wp_verify_nonce( $_POST['security'], 'event-wishlist-action-nonce' ) ){
            if( isset( $_POST['event_id'] ) && ! empty( $_POST['event_id'] ) ) {
                $event_id = absint( $_POST['event_id'] );
                $user_id = get_current_user_id();
                if( empty( $user_id ) ) {
                    wp_send_json_error( array( 'error' => esc_html__( 'You need to login to add event to wishlist', 'eventprime-event-calendar-management' ) ) );
                }
                $ep_functions = new Eventprime_Basic_Functions;
                $single_event = $ep_functions->get_single_event( $event_id );
                if( empty( $single_event ) ) {
                    wp_send_json_error( array( 'error' => esc_html__( 'Event Not Found', 'eventprime-event-calendar-management' ) ) );
                }
                // get user wishlist meta
                $wishlist_meta = get_user_meta( $user_id, 'ep_wishlist_event', true );
                if( empty( $wishlist_meta ) ) { // if empty the add event id
                    $wishlist_array = array( $event_id => 1 );
                    update_user_meta( $user_id, 'ep_wishlist_event', $wishlist_array );
                    wp_send_json_success( array( 'action' => 'add', 'title'=> $ep_functions->ep_global_settings_button_title( 'Remove From Wishlist' ), 'message' => esc_html__( 'Event added successfully into wishlist', 'eventprime-event-calendar-management' ) ) );
                } else{
                    // if already added then remove the event from wishlist
                    if( array_key_exists( $event_id, $wishlist_meta ) ) {
                        unset( $wishlist_meta[$event_id] );
                        update_user_meta( $user_id, 'ep_wishlist_event', $wishlist_meta );
                        wp_send_json_success( array( 'action' => 'remove', 'title'=> $ep_functions->ep_global_settings_button_title( 'Add To Wishlist' ), 'message' => esc_html__( 'Event removed successfully from wishlist', 'eventprime-event-calendar-management' ) ) );
                    } else{
                        $wishlist_meta[$event_id] = 1;
                        update_user_meta( $user_id, 'ep_wishlist_event', $wishlist_meta );
                        wp_send_json_success( array( 'action' => 'add', 'title'=> $ep_functions->ep_global_settings_button_title( 'Remove From Wishlist' ), 'message' => esc_html__( 'Event added successfully into wishlist', 'eventprime-event-calendar-management' ) ) );
                    }
                }
            } else{
                wp_send_json_error( array( 'error' => esc_html__( 'Wrong data.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Submit the frontend event submission form
     */
    public function save_frontend_event_submission() {
        if( wp_verify_nonce( $_POST['security'], 'ep-frontend-event-submission-nonce' ) ) {
            global $wpdb;
            parse_str( wp_unslash( $_POST['data'] ), $data );
            $ep_functions = new Eventprime_Basic_Functions;
            $notifications = new EventM_Notification_Service;
            $sanitizer = new EventPrime_sanitizer;
            $em_name = htmlspecialchars_decode( sanitize_text_field( $data['em_name'] ) );
            
            $result = array( 'success' => 1, 'msg' => '' );
            $checkpoint = apply_filters('ep_handle_frontend_submission_additional_check',$result, $data);
            if(isset($checkpoint['success']) && empty($checkpoint['success'])){
                wp_send_json_error( array( 'error' =>  $checkpoint['msg']) );
                die();
            }
            if( empty( $em_name ) ) {
                wp_send_json_error( array( 'error' => esc_html__( 'Event Name cannot be empty.', 'eventprime-event-calendar-management' ) ) );
            }
            
            $guest_submission = $ep_functions->ep_get_global_settings('allow_submission_by_anonymous_user');
            if( empty( $guest_submission ) && empty( get_current_user_id() ) ) {
                wp_send_json_error( array( 'error' => esc_html__( 'User login required to submit event.', 'eventprime-event-calendar-management' ) ) );
            }
            
            if(empty($guest_submission)){
                $hasUserRestriction = 0;
                $frontend_submission_roles = (array) $ep_functions->ep_get_global_settings( 'frontend_submission_roles' );
                if( ! empty( $frontend_submission_roles ) ) {
                    $user = wp_get_current_user();
                    foreach ( $user->roles as $key => $value ) {
                        if( in_array( $value, $frontend_submission_roles ) ) {
                            $hasUserRestriction = 1;
                            break;
                        }
                    }
                }else{
                    $hasUserRestriction = 1;
                } 
                if(empty($hasUserRestriction)){
                       wp_send_json_error( array( 'error' => $ep_functions->ep_get_global_settings('ues_restricted_submission_message') ) ); 
                }
            }
            
            
            
            $post_status = $ep_functions->ep_get_global_settings( 'ues_default_status' );
            if( empty( $post_status ) ) {
                $post_status = 'draft';
            }

            $event_description = wp_kses_post( stripslashes( $data['em_descriptions'] ) );
            
            if( isset( $data['event_id'] ) && ! empty( $data['event_id'] ) ) {
                $post_id = $data['event_id'];
                if(empty(get_post($post_id)) || get_post_type($post_id) != 'em_event' ){
                    wp_send_json_error( array( 'error' => esc_html__( 'There is some issue with event. Please try later.', 'eventprime-event-calendar-management' ) ) );
                }
                if(!empty($guest_submission) && get_post_meta($post_id, 'em_user_submitted', true) != get_current_user_id()){
                       wp_send_json_error( array( 'error' => esc_html__( 'Event does not belong to you.', 'eventprime-event-calendar-management' ) ) );
                
                }
                $post_update = array(
                    'ID'         => $post_id,
                    'post_title' => $em_name,
                    'post_content' => $event_description,
                );
                wp_update_post( $post_update );
            }else{
                $post_id = wp_insert_post(array (
                    'post_type' => 'em_event',
                    'post_title' => $em_name,
                    'post_content' => $event_description,
                    'post_status' => $post_status,
                    'post_author' => get_current_user_id(),
                )); 
            }

            update_post_meta( $post_id, 'em_frontend_submission', 1 );
            update_post_meta( $post_id, 'em_user_submitted', 1 );
            update_post_meta( $post_id, 'em_user', get_current_user_id() );

            update_post_meta( $post_id, 'em_id', $post_id );
            update_post_meta( $post_id, 'em_name', $em_name );

            $event_data = new stdClass();
            $thumbnail_id = isset( $data['attachment_id'] ) ? $data['attachment_id'] : '';
            set_post_thumbnail( $post_id, $thumbnail_id );
        
            $em_start_date = isset( $data['em_start_date'] ) ? $ep_functions->ep_date_to_timestamp( sanitize_text_field( $data['em_start_date'] ) ) : '';
            update_post_meta($post_id, 'em_start_date', $em_start_date);
            
            $em_start_time = isset( $data['em_start_time'] ) ? sanitize_text_field( $data['em_start_time'] ) : '';
            update_post_meta($post_id, 'em_start_time', $em_start_time);
            
            $em_hide_event_start_time = isset( $data['em_hide_event_start_time'] ) && !empty($data['em_hide_event_start_time'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_hide_event_start_time', $em_hide_event_start_time );
            
            $em_hide_event_start_date = isset( $data['em_hide_event_start_date'] ) && !empty( $data['em_hide_event_start_date'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_hide_event_start_date', $em_hide_event_start_date );
            
            $em_end_date = isset( $data['em_end_date'] ) ? $ep_functions->ep_date_to_timestamp( sanitize_text_field( $data['em_end_date'] ) ) : $em_start_date;
            update_post_meta($post_id, 'em_end_date', $em_end_date);
            
            $em_end_time = isset( $data['em_end_time'] ) ? sanitize_text_field( $data['em_end_time'] ) : '';
            update_post_meta($post_id, 'em_end_time', $em_end_time);
            
            $em_hide_event_end_time = isset( $data['em_hide_event_end_time'] ) && !empty($data['em_hide_event_end_time']) ? 1 : 0;
            update_post_meta( $post_id, 'em_hide_event_end_time', $em_hide_event_end_time );
            
            $em_hide_end_date = isset( $data['em_hide_end_date'] ) && !empty( $data['em_hide_end_date'] )? 1 : 0;
            update_post_meta( $post_id, 'em_hide_end_date', $em_hide_end_date );
            
            $em_all_day = isset( $data['em_all_day'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_all_day', $em_all_day );
            // if event is all day then end date will be same as start date
            if( $em_all_day == 1 ) {
                $em_end_date = $em_start_date;
                update_post_meta( $post_id, 'em_end_date', $em_end_date );
                $em_start_time = '12:00 AM'; $em_end_time = '11:59 PM';
                update_post_meta( $post_id, 'em_start_time', $em_start_time );
                update_post_meta( $post_id, 'em_end_time', $em_end_time );
            }
            // update start and end datetime meta
            $ep_date_time_format = 'Y-m-d';
            $start_date = get_post_meta( $post_id, 'em_start_date', true );
            $start_time = get_post_meta( $post_id, 'em_start_time', true );
            $merge_start_date_time = $ep_functions->ep_datetime_to_timestamp( $ep_functions->ep_timestamp_to_date( $start_date, 'Y-m-d', 1 ) . ' ' . $start_time, $ep_date_time_format, '', 0, 1 );
            if( ! empty( $merge_start_date_time ) ) {
                update_post_meta( $post_id, 'em_start_date_time', $merge_start_date_time );
            }
            $end_date = get_post_meta( $post_id, 'em_end_date', true );
            $end_time = get_post_meta( $post_id, 'em_end_time', true );
            $merge_end_date_time = $ep_functions->ep_datetime_to_timestamp( $ep_functions->ep_timestamp_to_date( $end_date, 'Y-m-d', 1 ) . ' ' . $end_time, $ep_date_time_format, '', 0, 1 );
            if( ! empty( $merge_end_date_time ) ) {
                update_post_meta( $post_id, 'em_end_date_time', $merge_end_date_time );
            }

            $em_event_date_placeholder = isset( $data['em_event_date_placeholder'] ) ? sanitize_text_field( $data['em_event_date_placeholder'] ) : '';
            update_post_meta( $post_id, 'em_event_date_placeholder', $em_event_date_placeholder );
            $em_event_date_placeholder_custom_note = '';
            if( ! empty( $em_event_date_placeholder ) && $em_event_date_placeholder == 'custom_note' ) {
                $em_event_date_placeholder_custom_note = sanitize_text_field( $data['em_event_date_placeholder_custom_note'] );
            }
            update_post_meta( $post_id, 'em_event_date_placeholder_custom_note', $em_event_date_placeholder_custom_note );

            // add event more dates
            $em_event_more_dates = isset( $data['em_event_more_dates'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_event_more_dates', $em_event_more_dates );
            $event_more_dates = array();
            if( isset( $data['em_event_more_dates'] ) && !empty( $data['em_event_more_dates'] ) ) {
                if( isset( $data['em_event_add_more_dates'] ) && count( $data['em_event_add_more_dates'] ) > 0 ) {
                    foreach( $data['em_event_add_more_dates'] as $key => $more_dates ) {
                        $new_date = array();
                        $new_date['uid']    = absint( $more_dates['uid'] );
                        $new_date['date']   = $ep_functions->ep_date_to_timestamp( sanitize_text_field( $more_dates['date'] ) );
                        $new_date['time']   = sanitize_text_field( $more_dates['time'] );
                        $new_date['label']  = sanitize_text_field( $more_dates['label'] );
                        $event_more_dates[] = $new_date;
                    }
                }
            }
		    update_post_meta( $post_id, 'em_event_add_more_dates', $event_more_dates );

            // booking & tickets
            $em_enable_booking = isset( $data['em_enable_booking'] ) ? sanitize_text_field( $data['em_enable_booking'] ) : 'bookings_off';
            update_post_meta( $post_id, 'em_enable_booking', $em_enable_booking );
            // check for external booking
            if( ! empty( $em_enable_booking ) && $em_enable_booking == 'external_bookings' ) {
                $em_custom_link = isset( $data['em_custom_link'] ) && ! empty( $data['em_custom_link'] ) ? sanitize_url( $data['em_custom_link'] ) : '';
                update_post_meta( $post_id, 'em_custom_link', $em_custom_link );
                // open in new browser
                $em_custom_link_new_browser = isset( $data['em_custom_link_new_browser'] ) ? 1 : 0;
                update_post_meta( $post_id, 'em_custom_link_new_browser', $em_custom_link_new_browser );
            }

            // One time event fee
            $em_fixed_event_price = isset( $data['em_fixed_event_price'] ) && ! empty( $data['em_fixed_event_price'] ) ? sanitize_text_field( $data['em_fixed_event_price'] ) : '';
            update_post_meta( $post_id, 'em_fixed_event_price', $em_fixed_event_price );
            // hide booking status
            $em_hide_booking_status = isset( $data['em_hide_booking_status'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_hide_booking_status', $em_hide_booking_status );
            // allow cancellation option
            $em_allow_cancellations = isset( $data['em_allow_cancellations'] ) ? 1 : 0;
            update_post_meta( $post_id, 'em_allow_cancellations', $em_allow_cancellations );

            // event type
            if( isset( $data['em_event_type'] ) && ! empty( $data['em_event_type'] ) ) {
                $em_event_type_id = absint( $data['em_event_type'] );
                update_post_meta( $post_id, 'em_event_type', $em_event_type_id );
                wp_set_object_terms( $post_id, intval( $em_event_type_id ), 'em_event_type' );
                if( $data['em_event_type'] == 'new_event_type' ) {
                    $type_data = new stdClass();
                    $type_data->name = isset( $data['new_event_type_name'] ) ? sanitize_text_field( $data['new_event_type_name'] ) : '';
                    if( ! empty( trim( $type_data->name ) ) ) {
                        $eventType = $type_data->name;
                        $type_term = get_term_by( 'name', $eventType, 'em_event_type' );
                        if( empty( $type_term ) ) {
                            $type_data->em_color = isset($data['new_event_type_background_color']) ? sanitize_text_field($data['new_event_type_background_color']) : '#FF5599';
                            $type_data->em_type_text_color = isset($data['new_event_type_text_color']) ? sanitize_text_field($data['new_event_type_text_color']) : '#43CDFF';
                            $type_data->em_age_group = isset($data['ep_new_event_type_age_group']) ? sanitize_text_field($data['ep_new_event_type_age_group']) : 'all';
                            $type_data->custom_age = isset($data['ep-new_event_type_custom_group']) ? sanitize_text_field($data['ep-new_event_type_custom_group']) : '';
                            $type_data->description = isset($data['new_event_type_description']) ? $data['new_event_type_description'] : '';
                            $type_data->em_image_id = isset($data['event_type_image_id']) ? $data['event_type_image_id'] : '';
                            $em_event_type_id = $ep_functions->create_event_types((array)$type_data);
                        } else{
                            $em_event_type_id = $type_term->term_id;
                        }
                        update_post_meta( $post_id, 'em_event_type', $em_event_type_id );
                        wp_set_object_terms( $post_id, intval( $em_event_type_id ), 'em_event_type' );
                    }
                }
            }

            // venues
            if( isset( $data['em_venue'] ) && ! empty( $data['em_venue'] ) ) {
                $em_venue_id = absint( $data['em_venue'] );
                update_post_meta( $post_id, 'em_venue', $em_venue_id );
                wp_set_object_terms( $post_id, intval( $em_venue_id ), 'em_venue' );
                if( $data['em_venue'] == 'new_venue' ) {
                    $venue_data = new stdClass();
                    $venue_data->name = isset($data['new_venue']) ? sanitize_text_field($data['new_venue']) : '';
                    if( ! empty( trim( $venue_data->name ) ) ) {
                        $location_name = $venue_data->name;
                        $location_term = get_term_by( 'name', $location_name, 'em_venue' );
                        if( empty( $location_term ) ) {
                            $venue_data->em_type = 'standings';
                            $venue_data->em_address = isset( $data['em_address'] ) ? sanitize_text_field( $data['em_address'] ) : '';
                            if( empty( $venue_data->em_address ) ) {
                                $venue_data->em_address = sanitize_text_field( $venue_data->name );
                            }
                            $venue_data->em_type = isset($data['seating_type']) ? sanitize_text_field($data['seating_type']) : '';
                            $venue_data->em_lng = isset($data['em_lng']) ? sanitize_text_field($data['em_lng']) : '';
                            $venue_data->em_lat = isset($data['em_lat']) ? sanitize_text_field($data['em_lat']) : '';
                            $venue_data->em_state = isset($data['em_state']) ? sanitize_text_field($data['em_state']) : '';
                            $venue_data->em_country = isset($data['em_country']) ? sanitize_text_field($data['em_country']) : '';
                            $venue_data->em_postal_code = isset($data['em_postal_code']) ? sanitize_text_field($data['em_postal_code']) : '';
                            $venue_data->em_zoom_level = isset($data['em_zoom_level']) ? sanitize_text_field($data['em_zoom_level']) : '';
                            $venue_data->em_display_address_on_frontend = isset($data['em_display_address_on_frontend']) & !empty($data['em_display_address_on_frontend']) ? 1: 0;
                            $venue_data->em_established = isset($data['em_established']) ? sanitize_text_field($data['em_established']) : '';
                            $venue_data->standing_capacity = isset($data['standing_capacity']) ? sanitize_text_field($data['standing_capacity']) : '';
                            $venue_data->em_seating_organizer = isset($data['em_seating_organizer']) ? sanitize_text_field($data['em_seating_organizer']) : '';
                            $venue_data->em_facebook_page = isset($data['em_facebook_page']) ? sanitize_text_field($data['em_facebook_page']) : '';
                            $venue_data->em_instagram_page = isset($data['em_instagram_page']) ? sanitize_text_field($data['em_instagram_page']) : '';
                            $venue_data->em_image_id = isset($data['venue_attachment_id']) ? sanitize_text_field($data['venue_attachment_id']) : '';
                            $em_venue_id = $ep_functions->create_venue((array)$venue_data);
                        } else{
                            $em_venue_id = $location_term->term_id;
                        }
                        update_post_meta( $post_id, 'em_venue', $em_venue_id );
                        wp_set_object_terms( $post_id, intval( $em_venue_id ), 'em_venue' );
                    }
                }
            }

            // organizer
            $org = array();
            if( isset( $data['em_organizer'] ) && !empty( $data['em_organizer'] ) ) {
                $org = $data['em_organizer'];
                update_post_meta( $post_id, 'em_organizer', $org );
            }
            if( isset( $data['new_organizer'] ) && $data['new_organizer'] == 1 ) {
                $organizer_name = isset( $data['new_organizer_name'] ) ? $data['new_organizer_name'] : '';
                if( ! empty( $organizer_name ) ) {
                    $organizer = get_term_by( 'name', $organizer_name, 'em_event_organizer' );
                    if( ! empty( $organizer ) ) {
                        $org[] = $organizer->term_id;
                    } else{
                        $org_data = new stdClass();
                        $org_data->name = $organizer_name;
                        
                        if( isset( $data['em_organizer_phones'] ) && ! empty( $data['em_organizer_phones'] ) ) {
                            $org_data->em_organizer_phones = $data['em_organizer_phones'];
                        }
                        if( isset( $data['em_organizer_emails'] ) && ! empty( $data['em_organizer_emails'] ) ) {
                            $org_data->em_organizer_emails = $data['em_organizer_emails'];
                        }
                        if( isset( $data['em_organizer_websites'] ) && ! empty( $data['em_organizer_websites'] ) ) {
                            $org_data->em_organizer_websites = $data['em_organizer_websites'];
                        }
                        $org_data->description = isset( $data['new_event_organizer_description'] ) ? $data['new_event_organizer_description'] : '';
                        $org_data->em_image_id = isset( $data['org_attachment_id'] ) ? $data['org_attachment_id'] : '';
                        $org_data->em_social_links = isset( $data['em_per_social_links'] ) ? $data['em_per_social_links'] : '';
                        $org[] = $ep_functions->create_organizer( (array)$org_data );
                    }
                }
                update_post_meta( $post_id, 'em_organizer', $org );
            }
            if( ! empty( $org ) ) {
                foreach( $org as $organizer ) {
                    if( ! empty( $organizer ) ) {
                        wp_set_object_terms( $post_id, intval( $organizer ), 'em_organizer' );
                    }
                }
            }
        
            $performers = array();
            if( isset( $data['em_performer'] ) && !empty( $data['em_performer'] )) {
                $performers = $data['em_performer'];
                update_post_meta( $post_id, 'em_performer', $performers );
            }
            if( isset( $data['new_performer'] ) && $data['new_performer'] == 1 ) {
                $performer_name = isset( $data['new_performer_name'] ) ? $data['new_performer_name'] : '';
                if( ! empty( $performer_name ) ) {
                    $performer_data = new stdClass();
                    $performer_data->name = $performer_name;
                    $performer_data->em_type = isset( $data['new_performer_type'] ) ? sanitize_text_field( $data['new_performer_type'] ) : 'person';
                    $performer_data->em_role = isset( $data['new_performer_role'] ) ? sanitize_text_field( $data['new_performer_role'] ) : '';

                    if(isset($data['em_performer_phones']) && !empty($data['em_performer_phones'])){
                        $performer_data->em_performer_phones = $data['em_performer_phones'];
                    }
                    if(isset($data['em_performer_emails']) && !empty($data['em_performer_emails'])){
                        $performer_data->em_performer_emails = $data['em_performer_emails'];
                    }
                    if(isset($data['em_performer_websites']) && !empty($data['em_performer_websites'])){
                        $performer_data->em_performer_websites = $data['em_performer_websites'];
                    }
                    $performer_data->description = isset($data['qt_new_performer_description']) ? $data['qt_new_performer_description'] : '';
                    $performer_data->thumbnail = isset($data['performer_attachment_id']) ? $data['performer_attachment_id'] : '';
                    $performer_data->em_social_links = isset($data['em_social_links']) ? $data['em_social_links'] : '';
                    $performers[] = $ep_functions->insert_performer_post_data((array)$performer_data);
                }
                update_post_meta( $post_id, 'em_performer', $performers );
            }
            
            
            // save category
            $dbhandler = new EP_DBhandler;
            $cat_table_name = 'TICKET_CATEGORIES';
            $price_options_table = 'TICKET';
            if( isset( $data['em_ticket_category_data'] ) && ! empty( $data['em_ticket_category_data'] ) ) {
                $em_ticket_category_data = json_decode( stripslashes( $data['em_ticket_category_data'] ), true) ;
            }
            if( ! empty( $em_ticket_category_data ) ) {
                $cat_priority = 1;
                foreach( $em_ticket_category_data as $cat ) {
                    $cat = $sanitizer->sanitize($cat);
                    $cat_id = $cat['id'];
                    $get_field_data = '';
                    if( !empty( $cat_id ) ) {
                        $get_field_data = $dbhandler->get_all_result($cat_table_name,'*',array('event_id'=>$post_id,'id'=>$cat_id));
                    }
                    if( empty( $get_field_data ) ) {
                        $save_data 				 = array();
                        $save_data['event_id'] 	 = $post_id;
                        $save_data['name'] 	     = $cat['name'];
                        $save_data['capacity']   = $cat['capacity'];
                        $save_data['priority']   = 1;
                        $save_data['status']     = 1;
                        $save_data['created_by'] = get_current_user_id();
                        $save_data['created_at'] = wp_date( "Y-m-d H:i:s", time() );
                        $cat_id = $dbhandler->insert_row($cat_table_name, $save_data);
                    } else{
                       $update_data =  array( 
                                'name' 		  	  => $cat['name'],
                                'capacity' 		  => $cat['capacity'],
                                'priority'		  => $cat_priority,
                                'last_updated_by' => get_current_user_id(),
                                'updated_at' 	  => wp_date("Y-m-d H:i:s", time())
                            );
                        $dbhandler->update_row($cat_table_name,'id', $cat_id, $update_data);
                        
                    }
                    $cat_priority++;
                    //save tickets
                    if( isset( $cat['tickets'] ) && ! empty( $cat['tickets'] ) ) {
                        $cat_ticket_priority = 1;
                        foreach( $cat['tickets'] as $ticket ) {
                            $ticket = $sanitizer->sanitize($ticket);
                            $ticket_data = array();
                            if( isset( $ticket['id'] ) && ! empty( $ticket['id'] && is_int( $ticket['id'] ) ) ) {
                                $ticket_id = $ticket['id'];
                                $get_ticket_data = $dbhandler->get_all_result($price_options_table,'*',array('id'=>$ticket_id));
                                if( ! empty( $get_ticket_data ) ) {
                                    $ticket_data['name'] 		   		   = addslashes( $ticket['name'] );
                                    $ticket_data['description']    		   = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                                    $ticket_data['price'] 		   		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                                    $ticket_data['capacity'] 	   		   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                                    $ticket_data['icon'] 		   		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                                    $ticket_data['priority'] 	   		   = $cat_ticket_priority;
                                    $ticket_data['updated_at'] 	   		   = wp_date("Y-m-d H:i:s", time());
                                    $ticket_data['additional_fees']    	   = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                                    $ticket_data['allow_cancellation'] 	   = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                                    $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                                    // date
                                    $start_date = [];
                                    if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                        $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                        if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                            if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                                $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                            }
                                            if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                                $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                            }
                                        } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                            if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                                $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                            }
                                            if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                                $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                            }
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_starts'] = json_encode( $start_date );
                                    // end date
                                    $end_date = [];
                                    if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                        $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                        if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                            if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                                $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                            }
                                            if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                                $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                            }
                                        } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        } elseif( $ticket['em_ticket_ends_booking_type'] == 'relative_date' ) {
                                            if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                                $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                            }
                                            if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                                $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                            }
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_ends'] = json_encode( $end_date );
                                    
                                    $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                                    $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                                    $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                                    $dbhandler->update_row($price_options_table,'id',$ticket_id, $ticket_data);
                                    
                                } else{
                                    $ticket_data['category_id']    = $cat_id;
                                    $ticket_data['event_id'] 	   = $post_id;
                                    $ticket_data['name'] 		   = addslashes( $ticket['name'] );
                                    $ticket_data['description']    = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                                    $ticket_data['price'] 		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                                    $ticket_data['special_price']  = '';
                                    $ticket_data['capacity'] 	   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                                    $ticket_data['is_default']     = 1;
                                    $ticket_data['is_event_price'] = 0;
                                    $ticket_data['icon'] 		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                                    $ticket_data['priority'] 	   = $cat_ticket_priority;
                                    $ticket_data['status'] 		   = 1;
                                    $ticket_data['created_at'] 	   = wp_date("Y-m-d H:i:s", time());

                                    // new
                                    $ticket_data['additional_fees']    = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                                    $ticket_data['allow_cancellation'] = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                                    $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                                    // date
                                    $start_date = [];
                                    if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                        $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                        if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                            if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                                $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                            }
                                            if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                                $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                            }
                                        } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                            if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                                $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                            }
                                            if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                                $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                            }
                                            $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_starts'] = json_encode( $start_date );
                                    // end date
                                    $end_date = [];
                                    if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                        $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                        if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                            if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                                $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                            }
                                            if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                                $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                            }
                                        } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_ends' ) {
                                            if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                                $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                            }
                                            if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                                $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                            }
                                            $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                        }
                                    }
                                    $ticket_data['booking_ends'] = json_encode( $end_date );
                                
                                    $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                                    $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                                    $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                                    $result = $dbhandler->insert_row($price_options_table, $ticket_data);
                           
                                }
                            } else{
                                $ticket_data['category_id']    = $cat_id;
                                $ticket_data['event_id'] 	   = $post_id;
                                $ticket_data['name'] 		   = addslashes( $ticket['name'] );
                                $ticket_data['description']    = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                                $ticket_data['price'] 		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                                $ticket_data['special_price']  = '';
                                $ticket_data['capacity'] 	   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                                $ticket_data['is_default']     = 1;
                                $ticket_data['is_event_price'] = 0;
                                $ticket_data['icon'] 		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                                $ticket_data['priority'] 	   = $cat_ticket_priority;
                                $ticket_data['status'] 		   = 1;
                                $ticket_data['created_at'] 	   = wp_date("Y-m-d H:i:s", time());

                                // new
                                $ticket_data['additional_fees']    = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                                $ticket_data['allow_cancellation'] = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                                $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                                // date
                                $start_date = [];
                                if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                    $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                    if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                            $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                            $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                            $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                            $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                        }
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_starts'] = json_encode( $start_date );
                                // end date
                                $end_date = [];
                                if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                    $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                    if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                            $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                            $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_ends' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                            $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                            $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                        }
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_ends'] = json_encode( $end_date );

                                $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                                $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                                $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                                $result = $dbhandler->insert_row($price_options_table, $ticket_data);
                                
                            }
                            $cat_ticket_priority++;
                        }

                        update_post_meta( $post_id, 'em_enable_booking', 'bookings_on' );
                    }
                }
            }

            // delete category
            if( isset( $data['em_ticket_category_delete_ids'] ) && !empty( $data['em_ticket_category_delete_ids'] ) ) {
                $em_ticket_category_delete_ids = $data['em_ticket_category_delete_ids'];
                $del_ids = json_decode( stripslashes( $em_ticket_category_delete_ids ) );
                if( is_string( $em_ticket_category_delete_ids ) && is_array( json_decode( stripslashes( $em_ticket_category_delete_ids ) ) ) &&  json_last_error() == JSON_ERROR_NONE ) {
                    foreach( $del_ids as $id ) {
                        $dbhandler->remove_row($cat_table_name,'id', $id);
                    }
                }
            }

            // save tickets
            if( isset( $data['em_ticket_individual_data'] ) && ! empty( $data['em_ticket_individual_data'] ) ) {
                $em_ticket_individual_data = json_decode( stripslashes( $data['em_ticket_individual_data'] ), true) ;
                if( isset( $em_ticket_individual_data ) && ! empty( $em_ticket_individual_data ) ) {
                    foreach( $em_ticket_individual_data as $ticket ) {
                        $ticket = $sanitizer->sanitize($ticket);
                        if( isset( $ticket['id'] ) && ! empty( $ticket['id'] ) && is_int( $ticket['id'] ) ) {
                            $ticket_id = $ticket['id'];
                            $get_ticket_data = $dbhandler->get_all_result($price_options_table,'*',array('id'=>$ticket_id));
                            if( ! empty( $get_ticket_data ) ) {
                                $ticket_data 				   = array();
                                $ticket_data['name'] 		   = addslashes( $ticket['name'] );
                                $ticket_data['description']    = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                                $ticket_data['price'] 		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                                $ticket_data['capacity'] 	   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                                $ticket_data['icon'] 		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                                $ticket_data['updated_at'] 	   = wp_date("Y-m-d H:i:s", time());
                                $ticket_data['additional_fees']    = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                                $ticket_data['allow_cancellation'] = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                                $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                                // date
                                $start_date = [];
                                if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                    $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                    if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                            $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                            $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                            $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                            $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                        }
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_starts'] = json_encode( $start_date );
                                // end date
                                $end_date = [];
                                if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                    $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                    if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                            $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                            $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'relative_date' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                            $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                            $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                        }
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_ends'] = json_encode( $end_date );

                                $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                                $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                                $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                                $dbhandler->update_row($price_options_table,'id', $ticket_id, $ticket_data);
                                
                            } else{
                                $ticket_data 				   = array();
                                $ticket_data['category_id']    = 0;
                                $ticket_data['event_id'] 	   = $post_id;
                                $ticket_data['name'] 		   = addslashes( $ticket['name'] );
                                $ticket_data['description']    = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                                $ticket_data['price'] 		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                                $ticket_data['special_price']  = '';
                                $ticket_data['capacity'] 	   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                                $ticket_data['is_default']     = 1;
                                $ticket_data['is_event_price'] = 0;
                                $ticket_data['icon'] 		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                                $ticket_data['priority'] 	   = 1;
                                $ticket_data['status'] 		   = 1;
                                $ticket_data['created_at'] 	   = wp_date("Y-m-d H:i:s", time());

                                // new
                                $ticket_data['additional_fees']    = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                                $ticket_data['allow_cancellation'] = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                                $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                                // date
                                $start_date = [];
                                if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                    $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                    if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                            $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                            $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                        if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                            $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                            $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                        }
                                        $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_starts'] = json_encode( $start_date );
                                // end date
                                $end_date = [];
                                if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                    $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                    if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                            $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                            $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                        }
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    } elseif( $ticket['em_ticket_ends_booking_type'] == 'relative_date' ) {
                                        if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                            $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                        }
                                        if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                            $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                        }
                                        $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                    }
                                }
                                $ticket_data['booking_ends'] = json_encode( $end_date );

                                $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                                $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                                $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                                $result = $dbhandler->insert_row($price_options_table, $ticket_data);
                                
                            }
                        } else{
                            $ticket_data 				   = array();
                            $ticket_data['category_id']    = 0;
                            $ticket_data['event_id'] 	   = $post_id;
                            $ticket_data['name'] 		   = addslashes( $ticket['name'] );
                            $ticket_data['description']    = isset( $ticket['description'] ) ? addslashes( $ticket['description'] ) : '';
                            $ticket_data['price'] 		   = isset( $ticket['price'] ) ? $ticket['price'] : 0;
                            $ticket_data['special_price']  = '';
                            $ticket_data['capacity'] 	   = isset( $ticket['capacity'] ) ? absint( $ticket['capacity'] ) : 0;
                            $ticket_data['is_default']     = 1;
                            $ticket_data['is_event_price'] = 0;
                            $ticket_data['icon'] 		   = isset( $ticket['icon'] ) ? absint( $ticket['icon'] ) : '';
                            $ticket_data['priority'] 	   = 1;
                            $ticket_data['status'] 		   = 1;
                            $ticket_data['created_at'] 	   = wp_date("Y-m-d H:i:s", time());

                            // new
                            $ticket_data['additional_fees']    = ( isset( $ticket['ep_additional_ticket_fee_data'] ) && !empty( $ticket['ep_additional_ticket_fee_data'] ) ) ? json_encode( $ticket['ep_additional_ticket_fee_data'] ) : '';
                            $ticket_data['allow_cancellation'] = isset( $ticket['allow_cancellation'] ) ? absint( $ticket['allow_cancellation'] ) : 0;
                            $ticket_data['show_remaining_tickets'] = isset( $ticket['show_remaining_tickets'] ) ? absint( $ticket['show_remaining_tickets'] ) : 0;
                            // date
                            $start_date = [];
                            if( isset( $ticket['em_ticket_start_booking_type'] ) && !empty( $ticket['em_ticket_start_booking_type'] ) ) {
                                $start_date['booking_type'] = $ticket['em_ticket_start_booking_type'];
                                if( $ticket['em_ticket_start_booking_type'] == 'custom_date' ) {
                                    if( isset( $ticket['em_ticket_start_booking_date'] ) && ! empty( $ticket['em_ticket_start_booking_date'] ) ) {
                                        $start_date['start_date'] = $ticket['em_ticket_start_booking_date'];
                                    }
                                    if( isset( $ticket['em_ticket_start_booking_time'] ) && ! empty( $ticket['em_ticket_start_booking_time'] ) ) {
                                        $start_date['start_time'] = $ticket['em_ticket_start_booking_time'];
                                    }
                                } elseif( $ticket['em_ticket_start_booking_type'] == 'event_date' ) {
                                    $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                } elseif( $ticket['em_ticket_start_booking_type'] == 'relative_date' ) {
                                    if( isset( $ticket['em_ticket_start_booking_days'] ) && ! empty( $ticket['em_ticket_start_booking_days'] ) ) {
                                        $start_date['days'] = $ticket['em_ticket_start_booking_days'];
                                    }
                                    if( isset( $ticket['em_ticket_start_booking_days_option'] ) && ! empty( $ticket['em_ticket_start_booking_days_option'] ) ) {
                                        $start_date['days_option'] = $ticket['em_ticket_start_booking_days_option'];
                                    }
                                    $start_date['event_option'] = $ticket['em_ticket_start_booking_event_option'];
                                }
                            }
                            $ticket_data['booking_starts'] = json_encode( $start_date );
                            // end date
                            $end_date = [];
                            if( isset( $ticket['em_ticket_ends_booking_type'] ) && !empty( $ticket['em_ticket_ends_booking_type'] ) ) {
                                $end_date['booking_type'] = $ticket['em_ticket_ends_booking_type'];
                                if( $ticket['em_ticket_ends_booking_type'] == 'custom_date' ) {
                                    if( isset( $ticket['em_ticket_ends_booking_date'] ) && ! empty( $ticket['em_ticket_ends_booking_date'] ) ) {
                                        $end_date['end_date'] = $ticket['em_ticket_ends_booking_date'];
                                    }
                                    if( isset( $ticket['em_ticket_ends_booking_time'] ) && ! empty( $ticket['em_ticket_ends_booking_time'] ) ) {
                                        $end_date['end_time'] = $ticket['em_ticket_ends_booking_time'];
                                    }
                                } elseif( $ticket['em_ticket_ends_booking_type'] == 'event_date' ) {
                                    $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                } elseif( $ticket['em_ticket_ends_booking_type'] == 'relative_date' ) {
                                    if( isset( $ticket['em_ticket_ends_booking_days'] ) && ! empty( $ticket['em_ticket_ends_booking_days'] ) ) {
                                        $end_date['days'] = $ticket['em_ticket_ends_booking_days'];
                                    }
                                    if( isset( $ticket['em_ticket_ends_booking_days_option'] ) && ! empty( $ticket['em_ticket_ends_booking_days_option'] ) ) {
                                        $end_date['days_option'] = $ticket['em_ticket_ends_booking_days_option'];
                                    }
                                    $end_date['event_option'] = $ticket['em_ticket_ends_booking_event_option'];
                                }
                            }
                            $ticket_data['booking_ends'] = json_encode( $end_date );

                            $ticket_data['show_ticket_booking_dates'] = (isset( $ticket['show_ticket_booking_dates'] ) ) ? 1 : 0;
                            $ticket_data['min_ticket_no'] = isset( $ticket['min_ticket_no'] ) ? $ticket['min_ticket_no'] : 0;
                            $ticket_data['max_ticket_no'] = isset( $ticket['max_ticket_no'] ) ? $ticket['max_ticket_no'] : 0;
                            $result = $dbhandler->insert_row($price_options_table, $ticket_data);
                           
                        }
                    }

                    update_post_meta( $post_id, 'em_enable_booking', 'bookings_on' );
                }
            }

            // delete tickets
            if( isset( $data['em_ticket_individual_delete_ids'] ) && !empty( $data['em_ticket_individual_delete_ids'] ) ) {
                $em_ticket_individual_delete_ids = $data['em_ticket_individual_delete_ids'];
                $del_ids = json_decode( stripslashes( $em_ticket_individual_delete_ids ) );
                if( is_string( $em_ticket_individual_delete_ids ) && is_array( json_decode( stripslashes( $em_ticket_individual_delete_ids ) ) ) &&  json_last_error() == JSON_ERROR_NONE ) {
                    foreach( $del_ids as $id ) {
                        $dbhandler->remove_row($price_options_table,'id', $id);
                    }
                }
            }
            
            /* $frontend_event_controller = EventM_Factory_Service::ep_get_instance( 'EventM_Event_Controller_Frontend_Submission');
            $response = $frontend_event_controller->insert_frontend_event_post_data((array)$event_data); */
            do_action( 'ep_after_save_front_end_event', $post_id );
            $notifications->event_submitted( $post_id );
            $submit_message = esc_html__( 'Thank you for submitting your event. We will review and publish it soon.', 'eventprime-event-calendar-management' );
            if( $post_status == 'draft' ) {
                $ues_confirm_message = $ep_functions->ep_get_global_settings( 'ues_confirm_message' );
                if( ! empty( $ues_confirm_message ) ) {
                    $submit_message = $ues_confirm_message;
                }
            } else{
                if( ! empty( $data['event_id'] ) ) {
                    $submit_message = esc_html__( 'Event Updated Successfully.', 'eventprime-event-calendar-management' );
                } else{
                    $submit_message = esc_html__( 'Event Saved Successfully.', 'eventprime-event-calendar-management' );
                }
            }
            
            $data_send = array( 'message' => $submit_message, 'redirect' => null );
            $data_send = apply_filters( 'ep_front_end_event_send_additional_data', $data_send );
            wp_send_json_success( $data_send );

            // wp_send_json_success( array( 'message' => $submit_message ) );

        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }


    public function upload_file_media(){
        if(isset($_FILES["file"]) && !empty($_FILES["file"])){
            $extension = pathinfo( $_FILES["file"]["name"], PATHINFO_EXTENSION );
            if( $extension != 'jpg' && $extension != 'jpeg' && $extension != 'png' && $extension != 'gif' ) {
                wp_send_json_error( array( 'errors' => array( 'Only Image File Allowed.' ) ) );
            }
            $file = $_FILES['file'];
            $filename = $file['name'];
            $tmp_name = $file['tmp_name'];
            $upload_dir = wp_upload_dir();
            if (move_uploaded_file($file["tmp_name"], $upload_dir['path'] . "/" . $filename)) {
                $uploaded_file['file_name'] = $filename;
                $uploaded_file['upload_url'] = $upload_dir['url'] . "/" . $filename;
                $wp_filetype = wp_check_filetype($filename, null );
                $attachment = array(
                    'guid'           => $uploaded_file['upload_url'],
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', $filename ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                $attachment_id = wp_insert_attachment( $attachment, $upload_dir['path'] . "/" . $filename );
                if ( ! is_wp_error( $attachment_id ) ) {
                    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
                    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_dir['path'] . "/" . $filename );
                    wp_update_attachment_metadata( $attachment_id,  $attachment_data );
                    $returnData['success'] = array( 'attachment_id' => $attachment_id );
                }
            }
            else{
                $returnData['errors'] = __($upload_file['error']);
            }
        }
        if( isset( $returnData['success'] ) ) {
            wp_send_json_success( $returnData['success'] );
        }else{
            wp_send_json_success( $returnData );
        }
    }


    public function booking_update_status(){
        // Check if nonce is valid
        
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'ep_booking_nonce')) {
            wp_die('Security check failed');
        }
    
        if( isset( $_POST['booking_id'] ) && isset($_POST['status']) && !empty(trim($_POST['status'])) && current_user_can('manage_options')) {
            $booking_id = absint( $_POST['booking_id'] );
            $status = sanitize_text_field($_POST['status']);
            $booking_controller = new EventPrime_Bookings;
            $response = $booking_controller->update_status( $booking_id, $status);
            do_action('ep_booking_after_status_updated', $booking_id, $status);
            wp_send_json_success( $response );
        }else{
            wp_send_json_error();
        }   
    }
    
    public function load_event_dates() {
        // Get all the events dates
        $data = new stdClass();
        $data->start_dates = array();
        $data->event_ids = array();
        $ep_functions = new Eventprime_Basic_Functions;
        $query = array('meta_query'  => array( 'relation' => 'AND',
            array(
                array(
                    'key'     => 'em_start_date',
                    'value'   =>  current_time( 'timestamp' ),
                    'compare' => '>',
                    'type'=>'NUMERIC'
                    )
                )
            )
        );
        $events = $ep_functions->get_events_post_data($query);
        if($events->posts){
            foreach ($events->posts as $event){
                $start_date = date('Y-m-d', get_post_meta($event->id, 'em_start_date', true));
                $end_date = date('Y-m-d', get_post_meta($event->id, 'em_end_date', true));

                if (!empty($start_date)){
                    preg_match('/[0-9]{4}\-[0-9]{1,2}-[0-9]{1,2}/', $start_date, $matches);
                    if (count($matches) > 0 && !empty($matches[0])) {
                        //epd($matches);
                        if ( strtotime($matches[0]) <= strtotime(date('Y-m-d') ) && strtotime( $end_date ) >= strtotime( date('Y-m-d') ) ) {
                            $data->start_dates[] = date($ep_functions->ep_get_datepicker_format());
                        } else{
                            $data->start_dates[] = date($ep_functions->ep_get_datepicker_format(), strtotime($matches[0]) );
                        }
                        $data->event_ids[] = $event->id;
                    }
                }
            }
        }

        echo json_encode($data);
        die;
    }
    
    /*
     * Load more upcoming events
     */
    public function load_more_upcomingevent_performer(){
        $ep_functions = new Eventprime_Basic_Functions;
        $response = $ep_functions->get_eventupcoming_performer_loadmore();
        wp_send_json_success($response);
        die();
    }
    public function load_more_upcomingevent_venue(){
        $ep_functions = new Eventprime_Basic_Functions;
        $response = $ep_functions->get_eventupcoming_venue_loadmore();
        wp_send_json_success($response);
        die();
    }
    public function load_more_upcomingevent_organizer(){
        $ep_functions = new Eventprime_Basic_Functions;
        $response = $ep_functions->get_eventupcoming_organizer_loadmore();
        wp_send_json_success($response);
        die();
    }
    public function load_more_upcomingevent_eventtype(){
        $ep_functions = new Eventprime_Basic_Functions;
        $response = $ep_functions->get_eventupcoming_eventtype_loadmore();
        wp_send_json_success($response);
        die();
    }

    /**
     * Filter event data
     * 
     * @return Event Html.
     */
    public function filter_event_data() {
        $ep_functions = new Eventprime_Basic_Functions;
        $response = $ep_functions->get_filtered_event_content();
        wp_send_json_success( $response );
        die();
    }

    /**
     * Get all offers date of an event
     */
    public function load_event_offers_date() {
        
        check_ajax_referer( 'single-event-data-nonce', 'security' );
        $ep_functions = new Eventprime_Basic_Functions;
        $offer_data = $event_data = $offer_dates = array();
        if( isset( $_POST['offer_data'] ) && ! empty( $_POST['offer_data'] ) ) {
            $offer_data = json_decode( stripslashes( $_POST['offer_data'] ) );
            
            /* $event_controller = EventM_Factory_Service::ep_get_instance( 'EventM_Event_Controller_List' );
            $single_event = $event_controller->get_single_event( $event_id );
            $single_event->venue_other_events = EventM_Factory_Service::get_upcoming_event_by_venue_id( $single_event->em_venue, array( $single_event->id ) );
            wp_send_json_success( $single_event ); */
        }
        if( isset( $_POST['event_data'] ) && ! empty( $_POST['event_data'] ) ) {
            $event_data = $_POST['event_data'];
        }
        if( ! empty( $offer_data ) && ! empty( $event_data ) ) {
            foreach( $offer_data as $offer ) {
                $offer_date = $ep_functions->get_offer_date( $offer, $event_data );
                if( ! empty( $offer_date ) ) {
                    $offer_dates[ $offer->uid ] = $offer_date;
                }
            }
            wp_send_json_success( $offer_dates );
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Data Not Found', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Update user's timezone
     */
    public function update_user_timezone() {
        check_ajax_referer( 'ep-frontend-nonce', 'security' );
        if( isset( $_POST['time_zone'] ) && ! empty( $_POST['time_zone'] ) ) {
            $time_zone = $_POST['time_zone'];
            $user_id = get_current_user_id();
            if( ! empty( $user_id ) ) {
                // get tizone string from offset
                /* if( strpos( $time_zone, 'UTC-' ) !== false ) {
                    $offset = explode( 'UTC-', $time_zone )[1];
                    if( ! empty( $offset ) ) {
                        $time_zone = get_site_timezone_from_offset( $offset );
                    }
                } elseif( strpos( $time_zone, 'UTC+' ) !== false ) {
                    $offset = explode( 'UTC+', $time_zone )[1];
                    if( ! empty( $offset ) ) {
                        $time_zone = get_site_timezone_from_offset( $offset );
                    }
                } */

                update_user_meta( $user_id, 'ep_user_timezone_meta', $time_zone );

                wp_send_json_success( array( 'message' => esc_html__( 'Timezone updated successfully', 'eventprime-event-calendar-management' ) ) );
            } else{
                //wp_send_json_error( array( 'error' => esc_html__( 'Unauthorized access. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
                //setcookie( 'ep_user_timezone_meta', $time_zone, time() + (86400 * 30), "/");
                //wp_send_json_success( array( 'message' => esc_html__( 'Timezone updated successfully', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Please select timezone and save.', 'eventprime-event-calendar-management' ) ) );
        }
    }
    
    /*
     * Validate Create account details on checkout page 
     */
    public function validate_user_details_booking(){
        $response_code = 1;
        $response = '';
        $rule = sanitize_text_field($_POST['rules']);
        //Validate Username
        if($rule == 'username'){
            $username = sanitize_text_field($_POST['username']);
            if(!empty($username)){
                if(validate_username($username)){
                    if(username_exists($username)){
                        $response_code = 0;
                        $response = esc_html__('Username already exists.','eventprime-event-calendar-management');
                    }else{
                        $response_code = 1;
                        $response = esc_html__('Valid Username.','eventprime-event-calendar-management');
                    }
                }else{
                    $response_code = 0;
                    $response = esc_html__('Invalid Username.','eventprime-event-calendar-management');
                }   
            }else{
                $response_code = 0;
                $response = esc_html__('Username is required.','eventprime-event-calendar-management');
            }
        }elseif($rule =='email'){
            $email = sanitize_text_field($_POST['email']);
            if(!empty($email)){
                    if(email_exists($email)){
                        $response_code = 0;
                        $response = esc_html__('Email already exists.','eventprime-event-calendar-management');
                    }else{
                        $response_code = 1;
                        $response = esc_html__('Valid Email.','eventprime-event-calendar-management');
                    }
                   
            }else{
                $response_code = 0;
                $response = esc_html__('Email is required.','eventprime-event-calendar-management');
            }
        }
        wp_send_json_success(array('status'=>$response_code,'message'=>$response));
        wp_die();
    }
    
    public function get_attendees_email_by_event_id(){
        if(!isset($_POST['_wpnonce']) || !wp_verify_nonce( $_POST['_wpnonce'], 'ep_email_attendies' )){
            wp_send_json_error( array( 'success'=> false, 'errors' => esc_html__( 'Security check failed.', 'eventprime-event-calendar-management' ) ) );
        }
        if(empty(get_current_user_id()) || !current_user_can( 'manage_options' ) || !current_user_can( 'edit_posts' )){
            wp_send_json_error( array( 'success'=> false, 'errors' => esc_html__( 'You do not have permission.', 'eventprime-event-calendar-management' ) ) );
        }
        $data = $_POST;
        $emails = array();
        $event_id = absint($data['ep_event_id']);
        if(!empty($event_id)){
            $booking_controller = new EventPrime_Bookings;
            $bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
            if(!empty($bookings)){
                foreach($bookings as $booking){
                    $user_id = isset($booking->em_user) ? (int) $booking->em_user : 0;
                    if($user_id){
                        $user = get_userdata($user_id);
                        $emails[] = $user->user_email;
                    }else{
                        $order_info = $booking->em_order_info;
                        if( ! empty( $order_info ) && ! empty( $order_info['user_email'] ) ) {
                            $emails[] = esc_html( $order_info['user_email'] );
                        }
                    }
                }
            }
        }
        if( !empty( $emails ) && count( $emails ) > 0 ) {
            $emails = array_unique( $emails );
            wp_send_json_success( array('status'=> true, 'emails'=> implode(',',$emails) ));
        }
        else{
            wp_send_json_error( array( 'status'=> false, 'errors' => esc_html__( 'No Attendee Found', 'eventprime-event-calendar-management' ) ) );
        }
        
    }
    
    public function send_attendees_email(){
        if(!isset($_POST['_wpnonce']) || !wp_verify_nonce( $_POST['_wpnonce'], 'ep_email_attendies' )){
            wp_send_json_error( array( 'success'=> false, 'message' => esc_html__( 'Security check failed.', 'eventprime-event-calendar-management' ) ) );
        }
        if(empty(get_current_user_id()) || !current_user_can( 'manage_options' ) || !current_user_can( 'edit_posts' )){
            wp_send_json_error( array( 'success'=> false, 'message' => esc_html__( 'You do not have permission.', 'eventprime-event-calendar-management' ) ) );
        }
        $data = $_POST;
        $email_address = isset($data['email_address']) && !empty($data['email_address']) ? explode(',', $data['email_address']) : array();
        $email_subject = isset($data['email_subject']) && !empty($data['email_subject']) ? sanitize_text_field($data['email_subject']) : get_bloginfo();
        $content = isset($data['content_html']) ? $data['content_html'] : '';
        $cc_email_address = isset($data['cc_email_address']) && !empty($data['cc_email_address']) ? explode(',', $data['cc_email_address']) : array();
        
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if( ! empty( $cc_email_address ) ) {
            foreach($cc_email_address as $cc){
                if ( filter_var( $cc, FILTER_VALIDATE_EMAIL ) ) {
                    array_push( $headers , "cc: $cc" );
                }
            }
        }
        $sent = 0;
        if( count( $email_address ) > 0 ) {
            foreach( $email_address as $email ) {
               $email_sent = wp_mail( $email, $email_subject, $content, $headers );
               if(empty($sent)){
                  $sent =  $email_sent;
               }
            }
        }
        if(!empty($sent)){
            wp_send_json_success( array( 'success' => true, "message" => esc_html__( 'Email send successfully', 'eventprime-event-calendar-management' ) ) );
        }else{
            wp_send_json_error( array( 'success'=> false, 'message' => esc_html__( 'Email not send', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Check if user name already exist
     */
    public function rg_check_user_name() {
        if( wp_verify_nonce( $_POST['security'], 'event-registration-form-nonce' ) ) {
            if( ! empty( $_POST['user_name'] ) ) {
                $user_name = sanitize_text_field( $_POST['user_name'] );
                if ( username_exists( $user_name ) ) {
                    wp_send_json_error( array( 'error' => esc_html__( 'User name already exist.', 'eventprime-event-calendar-management' ) ) );
                } else{
                    wp_send_json_success();
                }
            } else{
                wp_send_json_error( array( 'error' => esc_html__( 'User name is required.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Check if email already exist
     */
    public function rg_check_email() {
        if( wp_verify_nonce( $_POST['security'], 'event-registration-form-nonce' ) ) {
            if( ! empty( $_POST['email'] ) ) {
                $email = sanitize_text_field( $_POST['email'] );
                if ( email_exists( $email ) ) {
                    wp_send_json_error( array( 'error' => esc_html__( 'Email already exist.', 'eventprime-event-calendar-management' ) ) );
                } else{
                    wp_send_json_success();
                }
            } else{
                wp_send_json_error( array( 'error' => esc_html__( 'Email is required.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Download attendees
     */
    public function export_submittion_attendees(){
        check_ajax_referer( 'ep-frontend-nonce', 'security' );
        $ep_functions = new Eventprime_Basic_Functions;
        $export_attendee = false;
        if( isset( $_POST['event_id'] ) && ! empty( $_POST['event_id'] ) ) {
            $event_id = $_POST['event_id'];
            $current_user_id = get_current_user_id();
            // Get the post object
            $single_event = get_post($event_id);
            // Check if the post exists and the current user is the author
            if ($single_event && (int) $single_event->post_author === $current_user_id) {
                $export_attendee =  true; // The current user is the post owner
            }
            
            if($export_attendee==false)
            {
                wp_send_json_error( array( 'error' => esc_html__( "You do not have permission to download the attendees CSV file for this event.", 'eventprime-event-calendar-management' ) ) );
            }
            
            $bookings = array();
            $data = new stdClass();
            $booking_args = array(
                'numberposts' => -1,
                'post_status' => 'completed',
                'post_type'   => 'em_booking',
                'meta_key'    => 'em_event',
                'meta_value'  => $event_id
            );
            $booking_posts = get_posts( $booking_args );
            $booking_controller = new EventPrime_Bookings;
            foreach ( $booking_posts as $post ) {
                array_push( $bookings, $booking_controller->load_booking_detail( $post->ID ) );
            }
            //epd($bookings);
            $csv = new stdClass();
            foreach ( $bookings as $booking ) {
                $user = get_user_by( 'id', $booking->em_user );
                $other_order_info = $booking->em_order_info;
                $csv = new stdClass();
                $csv->ID = $booking->em_id;
                $csv->user_display_name = (isset($user) && !empty($user)) ? $user->display_name : 
                ( 
                    (!empty($other_order_info) && isset($other_order_info['user_name']) && !empty($other_order_info['user_name'])) ? $other_order_info['user_name'] : '' 
                );
                $csv->user_email = (isset($user) && !empty($user)) ? $user->user_email : 
                ( 
                    (!empty($other_order_info) && isset($other_order_info['user_email']) && !empty($other_order_info['user_email'])) ? $other_order_info['user_email'] : '' 
                );
                $ticket_sub_total = 0;
                $ticket_qty = 0;
                foreach( $other_order_info['tickets'] as $ticket ){
                    $ticket_sub_total = $ticket_sub_total + $ticket->subtotal;
                    $ticket_qty = $ticket_qty + $ticket->qty;
                }
                $csv->price =  $ticket_sub_total + $other_order_info['event_fixed_price'];
                $csv->no_tickets =  $ticket_qty;
                $csv->amount_total =  $other_order_info['booking_total'];
                $event = $ep_functions->get_single_event( $booking->em_event,$single_event );
                if( ! empty( $event->id ) ){
                    $csv->event_name = $event->name;
                }
                else{
                    $csv->event_name = __( 'Event deleted', 'eventprime-event-calendar-management' );
                }
                $csv->event_type_name = '';
                if( ! empty( $event->em_event_type ) ){
                    $event_type = $ep_functions->get_single_event_type( $event->em_event_type );
                    if( ! empty( $event_type ) ){
                        $csv->event_type_name = $event_type->name;
                    }
                }
                $csv->venue = '';
                $csv->seating_type = '';
                if( ! empty( $event->em_venue ) ){
                    $event_venue = $ep_functions->get_single_venue( $event->em_venue );
                    if( ! empty( $event_venue ) ){
                        $csv->venue = $event_venue->name;
                        $csv->seating_type = $event_venue->em_type;
                    }
                }
                $i = 1;
                $attendee_name_data = '';
                foreach( $booking->em_attendee_names as $ticket_id => $attendee_data ) {
                    $booking_attendees_field_labels = $ep_functions->ep_get_booking_attendee_field_labels( $attendee_data[1] );
                    foreach( $attendee_data as $booking_attendees ) {
                        $attendee_name_data .= esc_html__( 'Attendees '.$i.' ', 'eventprime-event-calendar-management' );
                        $booking_attendees_val = array_values( $booking_attendees );
                        foreach( $booking_attendees_field_labels as $labels ){
                            $formated_val = str_replace( ' ', '_', strtolower( $labels ) );
                            $at_val = '---';
                            foreach( $booking_attendees_val as $baval ) {
                            if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                    $at_val = is_array($baval[$formated_val]) ? implode(',',$baval[$formated_val]) : $baval[$formated_val];
                                    break;
                                }
                            }
                            $attendee_name_data .= esc_html__( $labels, 'eventprime-event-calendar-management' ) .' : '. $at_val.', ';
                        }

                        $i++;
                    }
                }
                $csv->attendee_name = $attendee_name_data;
                $csv->seat_sequences = '';
                // if( isset( $other_order_info['seat_sequences'] ) && ! empty( $other_order_info['seat_sequences'] ) ){
                //     $csv->seat_sequences = implode( ',', $other_order_info['seat_sequences'] );
                // }
                $csv->status= $booking->em_status;
                $data->posts[] = $csv;
            }

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="attendees.csv"');
            header('Cache-Control: max-age=0');
            $csv_name = 'em_Attendees' . time() . mt_rand(10, 1000000);
            $csv_path = get_temp_dir() . $csv_name . '.csv';
            $csv = fopen('php://output', "w");
            if ( ! $csv ) {
                return false;
            }
            //Add UTF-8 header for proper encoding of the file
            fputs($csv, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $csv_fields = array();
            $csv_fields[] = __('Booking ID', 'eventprime-event-calendar-management');
            $csv_fields[] = __('User Name', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Email', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Price', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Ticket Count', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Total Amount', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Event Name', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Event Type', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Venue', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Seating Type', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Attendees', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Seat No.', 'eventprime-event-calendar-management');
            $csv_fields[] = __('Status', 'eventprime-event-calendar-management');
            fputcsv( $csv, $csv_fields );
            foreach ( $data->posts as $a ) {
                if ( ! fputcsv( $csv, array_values((array) $a ) ) )
                    return false;
            }

            fclose( $csv );
            wp_die();
        }
    }
    
    /**
     * Run migration process for < 3.0.0 installation
     */
    public function eventprime_run_migration() {
        if( wp_verify_nonce( $_POST['security'], 'ep-migration-nonce' ) ) {
            // check if already migrated
            if( empty( get_option( 'ep_db_need_to_run_migration' ) ) ) {
                wp_send_json_success( array( 'message' => esc_html__( 'EventPrime already migrated with the latest version.', 'eventprime-event-calendar-management' ) ) );
            }
            // check if user has capability
            if( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'You are not authorised user for this action. Please contact with your administrator.', 'eventprime-event-calendar-management' ) ) );
            }

            update_option( 'ep_db_need_to_run_migration', 0 );
            
            wp_send_json_success( array( 'message' => esc_html__( 'Migration Complete!', 'eventprime-event-calendar-management' ) ) );
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Cancel the migration process
     */
    public function eventprime_cancel_migration() {
        if( wp_verify_nonce( $_POST['security'], 'ep-migration-nonce' ) ) {
            /* $ep_deactivate_extensions_on_migration = get_option( 'ep_deactivate_extensions_on_migration ');
            if( ! empty( $ep_deactivate_extensions_on_migration ) ) {
                foreach( $ep_deactivate_extensions_on_migration as $ext_list ) {
                    activate_plugin( $ext_list );
                }
            }
            deactivate_plugins( plugin_basename( EP_PLUGIN_FILE ) ); */

            wp_send_json_success( array( 'message' => esc_html__( 'Migration Cancelled! Redirecting you to the plugins page.', 'eventprime-event-calendar-management' ) ) );
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    /**
     * Reload user sectionon the checkout page
     */
    public function reload_checkout_user_section() {
        $ep_functions = new Eventprime_Basic_Functions;
        if( ! empty( $_POST['userId'] ) ) {
            $user_id = $_POST['userId'];
            $user_data = get_user_by( 'id', $user_id );
            if( ! empty( $user_data ) ) {
                if( get_current_user_id() == $user_id ) {
                    $user_html = '';
                    $user_html .= '<div class="ep-logged-user ep-py-3 ep-border ep-rounded">';
                        $user_html .= '<div class="ep-box-row">';
                            $user_html .= '<div class="ep-box-col-12 ep-d-flex ep-align-items-center ">';
                                $user_html .= '<div class="ep-d-inline-flex ep-mx-3">';
                                    $user_html .= '<img class="ep-rounded-circle" src="'. esc_url( get_avatar_url( $user_id ) ) .'" style="height: 32px;">';
                                $user_html .= '</div>';
                                $user_html .= '<div class="ep-d-inline-flex">';
                                    $user_html .= '<span class="ep-mr-1"> '. esc_html__( 'Logged in as', 'eventprime-event-calendar-management' ) .'</span>';
                                    $user_html .= '<span class="ep-fw-bold">'. esc_html( $ep_functions->ep_get_current_user_profile_name() ) .'</span>';
                                $user_html .= '</div>';
                            $user_html .= '</div>';
                        $user_html .= '</div>';
                    $user_html .= '</div>';

                    wp_send_json_success( array( 'user_html' => $user_html ) );
                }
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Wrong information.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Wrong information.', 'eventprime-event-calendar-management' ) ) );
        }
    }
    
    public function eventprime_reports_filter(){
        $report_controller = new EventM_Report_Controller_List;
        $filter_data = $report_controller->eventprime_report_filters();
        wp_send_json_success($filter_data);
    }

    public function set_default_payment_processor(){
        if( wp_verify_nonce( $_POST['security'], 'ep-default-payment-processor' ) ) {
            $global_settings = new Eventprime_Global_Settings;
            $global_settings_data = $global_settings->ep_get_settings();
            $form_data = $_POST;
            if( isset( $form_data ) && isset( $form_data['ep_default_payment_processor'] ) && ! empty( $form_data['ep_default_payment_processor'] ) ){
                $global_settings_data->default_payment_processor = $form_data['ep_default_payment_processor'];
                $global_settings->ep_save_settings( $global_settings_data );
            }
            wp_send_json_success();
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }
    
    public function booking_export_all(){
        $data = $_POST;
        if(!empty($data)){
            if(is_user_logged_in() && (current_user_can('edit_em_event') || current_user_can('edit_posts'))){
                $booking_controller = new EventPrime_Bookings;
                echo $booking_controller->export_bookings_all($data);
            }
        }
        die;
    }
    
    public function calendar_event_create(){
        parse_str( wp_unslash( $_POST['data'] ), $data );
        if(isset($data['_wpnonce']) && wp_verify_nonce(sanitize_text_field($data['_wpnonce']), 'ep_calendar_create_events' ) ){
            if(current_user_can('manage_options') || current_user_can('publish_em_events')) {
                $ep_functions = new Eventprime_Basic_Functions;
                $response = $ep_functions->ep_calendar_events_create();
            }
            else{
                $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
            }
        }
        else
        {
            $response = array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) );
        }
        wp_send_json_success($response);
        die();
    }
    
    public function calendar_events_drag_event_date(){
        $ep_functions = new Eventprime_Basic_Functions;
        if( isset( $_POST['id'] ) && ! empty( $_POST['id'] ) ) {
            if( !empty( get_post( $_POST['id'] ) ) && get_post_type( $_POST['id'] ) == 'em_event' ){ 
                
                if ( current_user_can( 'edit_em_event', $_POST['id'] ) && current_user_can('manage_options') ) {
                    $response = $ep_functions->ep_calendar_events_drag_event_date($_POST);
                } else {
                    $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
                }
            } else {
                $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
            }
        } else {
            $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
        }
        wp_send_json_success($response);
        
    }
    public function calendar_events_delete(){
        if( !wp_verify_nonce( '_wpnonce', 'ep-admin-calendar-action' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
        if(current_user_can('manage_options') && isset( $_POST['event_id'] ) && ! empty( $_POST['event_id'] ) ) {
            $event_id = abs( sanitize_text_field( $_POST['event_id'] ) );
            $current_user = wp_get_current_user();
            if(!empty($event_id)){
                if(!empty(get_post($event_id)) && get_post_type($event_id) == 'em_event' && ( user_can( $current_user->ID, 'edit_em_event', $event_id ) && current_user_can('manage_options') )){
                    wp_delete_post( $event_id );
                    $response = array( 'post_id' => $event_id, 'status' => true );   
                }else{
                    $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
         
                }
            }
            
        } else{
            $response = array( 'post_id' =>'', 'status' => false, 'message' => esc_html( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
        }
        wp_send_json_success($response);
    }

    public function eventprime_activate_license()
        {
            $retrieved_nonce = filter_input( INPUT_POST, 'nonce' );
            
            if ( !wp_verify_nonce( $retrieved_nonce, 'ep-license-nonce' ) ) {
                    die( esc_html__( 'Failed security check', 'eventprime-event-calendar-management' ) );
            }
            $ep_license_activate = sanitize_text_field(filter_input( INPUT_POST, 'ep_license_activate' ));
            $license_key = sanitize_text_field(filter_input( INPUT_POST, 'ep_license' ));
            $item_id = sanitize_text_field(filter_input( INPUT_POST, 'ep_item_id' ));
            $item_key = sanitize_text_field(filter_input( INPUT_POST, 'ep_item_key' ));
            update_option( $item_key.'_license_key', $license_key );
            update_option( $item_key.'_license_id', $item_id );
            
            
            $response = array();
            if( isset( $ep_license_activate ) && ! empty( $ep_license_activate ) ){
                $license = new EventPrime_License();
                $response = $license->ep_activate_license($license_key,$item_id,$item_key);
                wp_send_json_success( $response );
            }
            else
            {
                wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
            
    }

    public function eventprime_deactivate_license(){
        
        $retrieved_nonce = filter_input( INPUT_POST, 'nonce' );
            if ( !wp_verify_nonce( $retrieved_nonce, 'ep-license-nonce' ) ) {
                    die( esc_html__( 'Failed security check', 'eventprime-event-calendar-management' ) );
            }
            $ep_license_deactivate = sanitize_text_field(filter_input( INPUT_POST, 'ep_license_deactivate' ));
            $license_key = sanitize_text_field(filter_input( INPUT_POST, 'ep_license' ));
            $item_id = sanitize_text_field(filter_input( INPUT_POST, 'ep_item_id' ));
            $item_key = sanitize_text_field(filter_input( INPUT_POST, 'ep_item_key' ));
            update_option( $item_key.'_license_key', $license_key );
            update_option( $item_key.'_license_id', $item_id );
            $response = array();
            if( isset( $ep_license_deactivate ) && ! empty( $ep_license_deactivate ) ){
                $license = new EventPrime_License();
                $response = $license->ep_deactivate_extension_license($license_key,$item_id);
                $all_license_data = get_option('metagauss_license_data', []);
                if(isset($all_license_data[$license_key]))
                {
                    unset($all_license_data[$license_key]);
                    update_option('metagauss_license_data', $all_license_data);
                }

                delete_option($item_key.'_license_response');
                delete_option($item_key. '_license_status');
                delete_option($item_key. '_license_key');
                delete_option($item_key. '_item_id');
                delete_option($item_key.'_license_id' );
                wp_send_json_success( $response );
            }
            else
            {
                wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
        
    }

    /**
     * Update booking action
     */
    public function update_event_booking_action() {
        $sanitizer = new EventPrime_sanitizer;
        parse_str( wp_unslash( $_POST['data'] ), $data );
        $ep_functions = new Eventprime_Basic_Functions;
        if( wp_verify_nonce( $data['ep_update_event_booking_nonce'], 'ep_update_event_booking' ) ) {
            $ep_event_booking_id = ( ! empty( $data['ep_event_booking_id'] ) ? $data['ep_event_booking_id'] : '' );
            if( ! empty( $ep_event_booking_id ) ) {
                $booking_controller = new EventPrime_Bookings;
                $single_booking = $booking_controller->load_booking_detail( $ep_event_booking_id );
                if( ! empty( $single_booking ) ) {
                    if( ! empty( $data['ep_booking_attendee_fields'] ) ) {
                        $ep_booking_attendde_field = $sanitizer->sanitize($data['ep_booking_attendee_fields']);
                        update_post_meta( $ep_event_booking_id, 'em_attendee_names', $ep_booking_attendde_field );
                    }
                }
                wp_send_json_success( array( 'message' => esc_html__( 'Booking Updated Successfully.', 'eventprime-event-calendar-management' ), 'redirect_url' => esc_url( $ep_functions->ep_get_custom_page_url( 'profile_page' ) ) ) );
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Booking id can\'t be null. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    // Print all attendees of an event
    public function event_print_all_attendees() {
        $ep_functions = new Eventprime_Basic_Functions;
        if( wp_verify_nonce( $_POST['security'], 'ep_print_event_attendees' ) ) {
            $event_id = ( ! empty( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : '' );
            if( ! empty( $event_id ) ) {
                $em_event_checkout_attendee_fields = get_post_meta( $event_id, 'em_event_checkout_attendee_fields', true );
                $attendee_fileds_data = ( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_fields_data'] ) ? $em_event_checkout_attendee_fields['em_event_checkout_fields_data'] : array() );
                $bookings_data = array(); 
                $bookings_data[0]['id']   = esc_html__( 'Booking ID', 'eventprime-event-calendar-management' );
                $bookings_data[0]['event'] = esc_html__( 'Event', 'eventprime-event-calendar-management' );
                if( empty( $attendee_fileds_data ) ) {
                    $bookings_data[0]['first_name'] = esc_html__( 'First Name', 'eventprime-event-calendar-management' );
                    $bookings_data[0]['last_name']  = esc_html__( 'Last Name', 'eventprime-event-calendar-management' );
                } else{
                    if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name'] ) ) {
                        if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name'] ) ) {
                            $bookings_data[0]['first_name'] = esc_html__( 'First Name', 'eventprime-event-calendar-management' );
                        }
                        if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name'] ) ) {
                            $bookings_data[0]['middle_name'] = esc_html__( 'Middle Name', 'eventprime-event-calendar-management' );
                        }
                        if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name'] ) ) {
                            $bookings_data[0]['last_name'] = esc_html__( 'Last Name', 'eventprime-event-calendar-management' );
                        }
                    }
                    foreach( $attendee_fileds_data as $fields ) {
                        $label = $ep_functions->get_checkout_field_label_by_id( $fields );
                        $bookings_data[0][$label->label] = esc_html( $label->label );
                    }
                }
                $bookings_data[0]['user_email']  = esc_html__( 'Email', 'eventprime-event-calendar-management' );
                $bookings_data[0]['ticket_name'] = esc_html__( 'Ticket', 'eventprime-event-calendar-management' );
                $bookings_data[0]['booked_on']   = esc_html__( 'Booked On', 'eventprime-event-calendar-management' );

                $booking_controller = new EventPrime_Bookings;
                $event_bookings = $booking_controller->get_event_bookings_by_event_id( $event_id );
                if( ! empty( $event_bookings ) ) {
                    $row = 1;
                    foreach( $event_bookings as $booking ) {
                        $booking_id = $booking->ID;
                        $em_attendee_names = get_post_meta( $booking_id, 'em_attendee_names', true );
                        if( ! empty( $em_attendee_names ) ) {
                            $ticket_name = '';
                            foreach( $em_attendee_names as $ticket_id => $ticket_attendees ) {
                                $ticket_name = $ep_functions->get_ticket_name_by_id( $ticket_id );
                                if( ! empty( $ticket_attendees ) && count( $ticket_attendees ) > 0 ) {
                                    foreach( $ticket_attendees as $attendee_data ) {
                                        //$bookings_data[$row]['id'] = $bookings_data[$row]['event'] = $bookings_data[$row]['user_email'] = $bookings_data[$row]['booked_on'] = '';
                                        $bookings_data[$row]['id'] = $booking_id;
                                        $bookings_data[$row]['event'] = get_the_title( $event_id );
                                        if( empty( $attendee_fileds_data ) ) {
                                            $bookings_data[$row]['first_name'] = $attendee_data['name']['first_name'];
                                            $bookings_data[$row]['last_name']  = $attendee_data['name']['last_name'];
                                        } else{
                                            if( isset( $attendee_data['name'] ) ) {
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name'] ) ) {
                                                    $bookings_data[$row]['first_name']  = isset($attendee_data['name']['first_name']) ? $attendee_data['name']['first_name'] : '---';
                                                }
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name'] ) ) {
                                                    $bookings_data[$row]['middle_name'] = isset($attendee_data['name']['middle_name']) ? $attendee_data['name']['middle_name'] : '---';
                                                }
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name'] ) ) {
                                                    $bookings_data[$row]['last_name']   = isset($attendee_data['name']['last_name']) ? $attendee_data['name']['last_name'] : '---';
                                                }
                                            } else { 
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name'] ) ) { 
                                                    $bookings_data[$row]['first_name']  = '---';
                                                }
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name'] ) ) { 
                                                    $bookings_data[$row]['middle_name']  = '---';
                                                } 
                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name'] ) ) { 
                                                    $bookings_data[$row]['last_name']  = '---';
                                                }
                                            }
                                            foreach( $attendee_fileds_data as $fields ) {
                                                $checkout_field_val = '';
                                                $label_val = '';
                                                if( isset($attendee_data[$fields]) && ! empty( $attendee_data[$fields] ) ) {
                                                    $label_val = $attendee_data[$fields]['label'];
                                                    $input_name = $ep_functions->ep_get_slug_from_string( $label_val );
                                                    if( isset($attendee_data[$fields][$input_name]) && ! empty( $attendee_data[$fields][$input_name] ) ) {
                                                        $input_val = $attendee_data[$fields][$input_name];
                                                        if( is_array( $input_val ) ) {
                                                            $checkout_field_val = esc_html( implode( ', ', $input_val ) );
                                                        } else{
                                                            $checkout_field_val = esc_html( $attendee_data[$fields][$input_name] );
                                                        }
                                                    }
                                                } else {
                                                    $label_result = $ep_functions->get_checkout_field_label_by_id( $fields ); 
                                                    if ( !empty($label_result) ) {
                                                        $label_val = $label_result->label; 
                                                    }
                                                }
                                                if ( empty($checkout_field_val) ) {
                                                    $bookings_data[$row][$label_val] = "---";    
                                                } else {
                                                    $bookings_data[$row][$label_val] = $checkout_field_val;
                                                }
                                            }
                                        }

                                        $user_id = get_post_meta( $booking_id, 'em_user', true );
                                        if( ! empty( $user_id ) ) {
                                            $user = get_user_by( 'id', $user_id );
                                            if( ! empty( $user ) ) {
                                                $booking_user_email = esc_html( $user->user_email );
                                            } else{
                                                $booking_user_email = '----';
                                            }
                                        } else{
                                            $is_guest_booking = get_post_meta( $booking_id, 'em_guest_booking', true );
                                            if( ! empty( $is_guest_booking ) ) {
                                                $em_order_info = get_post_meta( $booking_id, 'em_order_info', true );
                                                if( ! empty( $em_order_info ) && ! empty( $em_order_info['user_email'] ) ) {
                                                    $booking_user_email = esc_html( $em_order_info['user_email'] );
                                                }
                                            }
                                        }
                                        $bookings_data[$row]['user_email'] = $booking_user_email;
                                        $bookings_data[$row]['ticket_name'] = $ticket_name;
                                        $em_date = get_post_meta( $booking_id, 'em_date', true );
                                        if( ! empty( $em_date ) ) {
                                            $bookings_data[$row]['booked_on'] = esc_html( $ep_functions->ep_timestamp_to_date( $em_date, 'd M, Y' ) );
                                        }
                                        $row++;
                                    }
                                }
                            }
                        } else{
                            $tickets_info = ( ! empty( $booking->em_order_info['tickets'] ) ? $booking->em_order_info['tickets'] : array() );
                            if( ! empty( $tickets_info ) && count( $tickets_info ) > 0 ) {
                                for( $con = 0; $con < count( $tickets_info ); $con++ ) {
                                    $bookings_data[$row]['id'] = $booking_id;
                                    $bookings_data[$row]['event'] = get_the_title( $event_id );
                                    $ticket_id = ( ! empty( $tickets_info[$con] ) && ! empty( $tickets_info[$con]->id ) ) ? $tickets_info[$con]->id : '';
                                    $ticket_name = ( ! empty( $ticket_id ) ? $ep_functions->get_ticket_name_by_id( $ticket_id ) : '----' );
                                    if( empty( $attendee_fileds_data ) ) {
                                        $bookings_data[$row]['first_name'] = '----';
                                        $bookings_data[$row]['last_name']  = '----';
                                    } else{
                                        if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name'] ) ) {
                                            if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name'] ) ) {
                                                $bookings_data[$row]['first_name'] = '----';
                                            }
                                            if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name'] ) ) {
                                                $bookings_data[$row]['middle_name'] = '----';
                                            }
                                            if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name'] ) ) {
                                                $bookings_data[$row]['last_name'] = '----';
                                            }
                                        }
                                        foreach( $attendee_fileds_data as $fields ) {
                                            $label = $ep_functions->get_checkout_field_label_by_id( $fields );
                                            $bookings_data[$row][$label->label] = '----';
                                        }
                                    }
                                    $user_id = get_post_meta( $booking_id, 'em_user', true );
                                    if( ! empty( $user_id ) ) {
                                        $user = get_user_by( 'id', $user_id );
                                        if( ! empty( $user ) ) {
                                            $booking_user_email = esc_html( $user->user_email );
                                        } else{
                                            $booking_user_email = '----';
                                        }
                                    } else{
                                        $is_guest_booking = get_post_meta( $booking_id, 'em_guest_booking', true );
                                        if( ! empty( $is_guest_booking ) ) {
                                            $em_order_info = get_post_meta( $booking_id, 'em_order_info', true );
                                            if( ! empty( $em_order_info ) && ! empty( $em_order_info['user_email'] ) ) {
                                                $booking_user_email = esc_html( $em_order_info['user_email'] );
                                            }
                                        }
                                    }
                                    $bookings_data[$row]['user_email'] = $booking_user_email;
                                    $bookings_data[$row]['ticket_name'] = $ticket_name;
                                    $em_date = get_post_meta( $booking_id, 'em_date', true );
                                    if( ! empty( $em_date ) ) {
                                        $bookings_data[$row]['booked_on'] = esc_html( $ep_functions->ep_timestamp_to_date( $em_date, 'd M, Y' ) );
                                    }
                                    $row++;
                                }
                            }
                        }
                    }
                }

                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="ep-bookings-'.md5(time().mt_rand(100, 999)).'.csv"');
                $f = fopen('php://output', 'w');
                foreach ( $bookings_data as $line ) {
                    fputcsv( $f, $line );
                }
                die;
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Event id can\'t be null. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    // load attendee fields html for edit booking
    public function load_edit_booking_attendee_data() {
        $response = new stdClass();
        if( wp_verify_nonce( $_POST['security'], 'ep_booking_attendee_data' ) ) {
            $event_id = ( ! empty( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : '' );
            $booking_id = ( ! empty( $_POST['booking_id'] ) ? absint( $_POST['booking_id'] ) : '' );
            $ticket_id = ( ! empty( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : '' );
            if( ! empty( $event_id ) && ! empty( $booking_id ) && ! empty( $ticket_id )) {
                $booking_controller = new EventPrime_Bookings;
                $booking = $booking_controller->load_booking_detail( $booking_id );
                if(!empty($booking)){
                    echo $booking_controller->ep_get_admin_edit_attendees_html($booking);
                    
   
                }else{
                    esc_html_e('No booking found','eventprime-event-calendar-management');
                }
                
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Some data is missing. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
            }
        }
        die();
    }


    // sanitize input field data
    public function sanitize_input_field_data() {
        if( wp_verify_nonce( $_POST['security'], 'ep-frontend-nonce' ) ) {
            if( isset( $_POST['input_val'] ) && ! empty( $_POST['input_val'] ) ) {
                $input_val = sanitize_text_field( $_POST['input_val'] );
                wp_send_json_success( $input_val );
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Value is missing.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    // send feedback
    public function send_plugin_deactivation_feedback() {
        if( wp_verify_nonce( $_POST['security'], 'ep-plugin-deactivation-nonce' ) ) {
            if( isset( $_POST['feedback'] ) && ! empty( $_POST['feedback'] ) ) {
                $feedback = sanitize_text_field( $_POST['feedback'] );
                $message = sanitize_text_field( $_POST['message'] );
                $email_message = '';
                if( ! empty( $_POST['ep_user_support_email'] ) ){
                    $ep_user_support_email = sanitize_email( $_POST['ep_user_support_email'] );
                    $from_email_address = '<' . $ep_user_support_email . '>';
                }else{
                    $from_email_address = '<' . get_option('admin_email') . '>';
                }
                
                switch( $feedback ) {
                    case 'feature_not_available': $body='Feature not available: '; break;
                    case 'feature_not_working': $body='Feature not working: '; break;
                    case 'plugin_difficult-to-use': $body='Plugin is difficult or confusing to use: '; break;
                    case 'plugin_broke_site': $body='Plugin broke my site'; break;
                    case 'plugin_has_design_issue': $body='Plugin has design issue'; break;
                    case 'temporary_deactivation': $body = "It's a temporary deactivation"; break;
                    case 'plugin_missing-documentation': $body = "Plugin is missing documentation"; break;
                    case 'other': $body='Other: '; break;
                    default: return;
                }
                if( ! empty( $feedback ) ) {
                    $email_message .= $body."\n\r";
                    if( ! empty( $message ) ) {
                        $email_message .= "<br><u>User Feedback Message</u> - "; 
                        // $email_message .= $message."\n\r";
                        $email_message .= $message."<br>";
                    }
                    $email_message .= "\n\r EventPrime Version - ".EVENTPRIME_VERSION;
                    $headers = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
                    $headers .= 'From:'.$from_email_address."\r\n";
                    if ( wp_mail( 'feedback@theeventprime.com', 'EventPrime Uninstallation Feedback', $email_message, $headers ) ){
                        if( isset( $_POST['ep_inform_email'] ) && ! empty( $_POST['ep_inform_email'] ) ){
                            wp_mail( 'support@theeventprime.com', 'EventPrime Uninstallation Feedback', $email_message, $headers ); 
                        }
                        wp_send_json_success();
                    }else{
                        wp_send_json_error();
                    }    
                }
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Feedback is missing.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }

    // delete fes event from user profile
    public function delete_user_fes_event() {
        if( wp_verify_nonce( $_POST['security'], 'ep-frontend-nonce' ) ) {
            $ep_functions = new Eventprime_Basic_Functions;
            $dbhandler = new EP_DBhandler;
            if( isset( $_POST['fes_event_id'] ) && ! empty( $_POST['fes_event_id'] ) ) {
                global $wpdb;
                $fes_event_id = absint( $_POST['fes_event_id'] );
                $current_user_id = get_current_user_id();
                $single_event = $ep_functions->get_single_event( $fes_event_id );
                if( empty( $single_event ) ) {
                    wp_send_json_error( array( 'message' => esc_html__( 'Event does not found.', 'eventprime-event-calendar-management' ) ) );
                }
                if( empty( $single_event->em_frontend_submission ) ) {
                    wp_send_json_error( array( 'message' => esc_html__( 'Event can\'t be deleted.', 'eventprime-event-calendar-management' ) ) );
                }
                // check if the logged user is same
                $event_user = $single_event->em_user;
                if( $event_user != $current_user_id ) {
                    wp_send_json_error( array( 'message' => esc_html__( 'You are not authorised to delete this event.', 'eventprime-event-calendar-management' ) ) );
                }
                // start event deletion
                // first check for recurring events
                $booking_controllers = new EventPrime_Bookings;
                $metaboxes_controllers = new EP_DBhandler;
                $metaboxes_controllers->ep_delete_child_events( $fes_event_id );
                // check category and tickets and delete them
                $cat_table_name = 'TICKET_CATEGORIES';
                $price_options_table = 'TICKET';
                // delete all ticket categories
                if( ! empty( $single_event->ticket_categories ) ) {
                    foreach( $single_event->ticket_categories as $category ) {
                        if( ! empty( $category->id ) ) {
                            $dbhandler->remove_row($cat_table_name,'id', $category->id);
                        }
                    }
                }
                // delete all tickets
                if( ! empty( $single_event->all_tickets_data ) ) {
                    foreach( $single_event->all_tickets_data as $ticket ) {
                        if( ! empty( $ticket->id ) ) {
                            $dbhandler->remove_row($price_options_table,'id', $ticket->id);
                        }
                    }
                }
                // delete booking of this event
                $event_bookings = $booking_controllers->get_event_bookings_by_event_id( $fes_event_id );
                if( ! empty( $event_bookings ) ) {
                    foreach( $event_bookings as $booking ) {
                        // delete booking
                        wp_delete_post( $booking->ID, true );
                    }
                }
                // delete terms relationships
                wp_delete_object_term_relationships( $fes_event_id, array( 'em_venue', 'em_event_type', 'em_event_organizer' ) );

                // delete ext data
                do_action( 'ep_delete_event_data', $fes_event_id );

                wp_delete_post( $fes_event_id, true );

                wp_send_json_success( array( 'message' => esc_html__( 'Event Deleted Successfully', 'eventprime-event-calendar-management' ) ) );
            } else{
                wp_send_json_error( array( 'message' => esc_html__( 'Event Id Is Missing.', 'eventprime-event-calendar-management' ) ) );
            }
        } else{
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
    }
    
    public function edit_booking_attendee_data_save(){
        $sanitizer = new EventPrime_sanitizer;
        parse_str( wp_unslash( $_POST['data'] ), $data );
        
        if( wp_verify_nonce( $_POST['security'], 'ep_booking_attendee_data' ) ) {
            $attendees_names = isset($data['ep_booking_attendee_fields']) ? $sanitizer->sanitize($data['ep_booking_attendee_fields']) : array();
            if(is_array($attendees_names)){
                update_post_meta( $_POST['booking_id'], 'em_attendee_names', $attendees_names );
            }
            
        }
        die();
    }
    
    public function get_calendar_event()
    {
        $ep_functions = new Eventprime_Basic_Functions;
        // Create a DateTime object from the string
        $timezone = new DateTimeZone($ep_functions->ep_get_site_timezone());
        $startdate = new DateTime($_POST['start'],$timezone);
        $format = $ep_functions->ep_get_datepicker_format();
        $formattedstartDate = $startdate->format($format);
        
        $enddate = new DateTime($_POST['end'],$timezone);
        $formattedendDate = $enddate->format($format);

        $start = $formattedstartDate;
        $end = $formattedendDate;
        
        $is_admin = (isset($_POST['is_dashboard']))?true:false;
        
        $dateInfo = array(
    array(
        'label' => 'From',
        'key' => 'date_from',
        'value' => $start,
        'text' => $start
    ),
    array(
        'label' => 'To',
        'key' => 'date_to',
        'value' => $end,
        'text' => $end
    ),
    array(
        'label' => 'Days',
        'key' => 'days',
        'value' => 'all',
        'text' => 'All Days'
    )
);

      
        $params = array
        (
            'meta_key' => 'em_start_date_time',
            'orderby' => 'meta_value_num',
            'posts_per_page' => -1,
            'offset' => 0,
            'paged' => 1,
            'meta_query' => array
                (
                    'relation' => 'AND'
                ),

            'order' => 'ASC'
        );
        
        $params = $ep_functions->create_filter_query($dateInfo,$params);
        
        $event_search_params = array();
        if( isset( $_POST['search_param'] ) && ! empty( $_POST['search_param'] ) ) {
            $event_search_params = json_decode( stripslashes( $_POST['search_param'] ), true );
            if( ! empty( $event_search_params ) && count( $event_search_params ) > 0 ) {
                $params = $ep_functions->create_filter_query($event_search_params, $params);
            }
        }

        
        // if(isset($_POST['args']))
        // {
            $atts = isset($_POST['args']) ? json_decode( stripslashes( $_POST['args'] ), true ) : array(); //$_POST['args'];

            // shortcode limit
            if( isset( $atts['show'] ) && ! empty( $atts['show'] ) ){
                $events_data['limit'] = $atts['show'];
                $params = array(
                    'meta_key'       => 'em_start_date_time',
                    'orderby'        => 'meta_value_num',
                    'posts_per_page' => $events_data['limit'],
                    'meta_query'     => array( 'relation' => 'AND' ),
                    //'order'          => 'DESC'
                );
            }

            // condition for hide upcoming events
            $hide_upcoming_events = 0;
            if( $ep_functions->ep_get_global_settings('shortcode_hide_upcoming_events') == 1 ) {
                $hide_upcoming_events = 1;
            }
            if( isset( $atts['upcoming'] ) && $atts['upcoming'] == 0 ) {
                $hide_upcoming_events = 1;
            }
            if( $hide_upcoming_events == 1 ) {
                array_push( $params['meta_query'], array(
                    'key'     => 'em_start_date',
                    'value'   => $ep_functions->ep_get_current_timestamp(),
                    'compare' => '<='
                ) );
            }

            // condition for hide past events
            $hide_past_events = 0;
            // from settings
            if( $ep_functions->ep_get_global_settings( 'hide_past_events' ) == 1 ) {
                $hide_past_events = 1;
            }
            // from shortcode
            if( isset( $atts['upcoming'] ) && $atts['upcoming'] == 1 ) {
                $hide_past_events = 1;
            }
            if( $hide_past_events == 1 ) {
                array_push( $params['meta_query'], array(
                    'key'     => 'em_end_date',
                    'value'   => strtotime( 'today' ),
                    'compare' => '>='
                ) );
            }

            // shortcode event types
            $type_ids = array();
            if( isset( $atts['types'] ) && ! empty( $atts['types'] ) ) {
                $type_ids = explode( ',', $atts['types'] );
            }
            if ( ! empty( $type_ids ) ) {
                array_push( $params['meta_query'], array(
                    'key'     => 'em_event_type',
                    'value'   => $type_ids,
                    'compare' => 'IN',
                    'type'    =>'NUMERIC'
                ) );
            }
            // shortcode event venues
            $venue_ids = array();
            if( isset( $atts['sites'] ) && ! empty( $atts['sites'] ) ) {
                $venue_ids = explode( ',', $atts['sites'] );
            }
//            if ( ! empty( $venue_ids ) ) {
//                $filter_venues_ids = array('relation'     => 'OR');
//                foreach ($venue_ids as $venue_id){
//                    $filter_venues_ids[]= array(
//                        'key'     => 'em_venue',
//                        'value'   =>  serialize( array($venue_id) ),
//                        'compare' => '='
//                    );
//                }
//                $params['meta_query'][] = $filter_venues_ids;
//            }
            
            // Filter events by Venue taxonomy term IDs
        if ( ! empty( $venue_ids ) ) {
            // Ensure it's an array of ints
            $venue_ids = array_map( 'absint', (array) $venue_ids );

            // Initialize tax_query if needed (keeps other tax filters intact)
            if ( empty( $params['tax_query'] ) ) {
                $params['tax_query'] = array( 'relation' => 'AND' );
            }

            $params['tax_query'][] = array(
                'taxonomy'         => 'em_venue',      // <-- change if your taxonomy slug differs
                'field'            => 'term_id',
                'terms'            => $venue_ids,      // match ANY of these IDs
                'operator'         => 'IN',
                'include_children' => false,           // set true if you want child venues included
            );
        }
            
            // individual events argument
        $events_data['i_events'] = '';
        if( isset( $atts['individual_events'] ) && ! empty( $atts['individual_events'] ) ){
            $events_data['i_events'] = $atts['individual_events'];
            $params['meta_query'] = $ep_functions->individual_events_shortcode_argument( $params['meta_query'],  $events_data['i_events'] );
        }
        

            $params = apply_filters( 'ep_events_render_attribute_data', $params, $atts ); 
        // }
        
        $events = $ep_functions->get_multiple_events_post_data($params);
        
        if(isset($events->posts))
        {
            wp_send_json_success($ep_functions->get_front_calendar_view_event( $events->posts,$is_admin ));
        }
        die;
    }
    
    public function check_offer_applied()
    {
        $ep_functions = new Eventprime_Basic_Functions;
        $dbhandler = new EP_DBhandler;
        $all_offers_data = array(
            'all_offers' => array(),
            'all_show_offers' => array(),
            'show_ticket_offers' => array(),
            'ticket_offers' => array(),
            'applicable_offers' => array()
        );
        $applicable_offer = array();
        if( wp_verify_nonce( $_POST['security'], 'single-event-data-nonce' ) ) {
            $ticket_id = ( ! empty( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : '' );
            $qty = ( ! empty( $_POST['qty'] ) ? absint( $_POST['qty'] ) : '' );
            $ticket =  $dbhandler->get_row('TICKET', $ticket_id);
            if (isset($ticket) && !empty($ticket) && isset($ticket->offers) && !empty($ticket->offers)) 
            {
                $all_offers_data = $ep_functions->get_event_single_offer_data($all_offers_data, $ticket, $ticket->event_id,$qty);
            }
            
            if(isset($all_offers_data['applicable_offers'][$ticket_id]) && !empty($all_offers_data['applicable_offers'][$ticket_id]))
            {
                
                foreach($all_offers_data['applicable_offers'][$ticket_id] as $key=>$offer)
                {
                    $applicable_offer[] = $offer;
                }
            }
            
            
            wp_send_json_success( array( 'offers' => $applicable_offer ) );
        } else{
            wp_send_json_success( array( 'message' => esc_html__( 'Failed security checks.', 'eventprime-event-calendar-management' ) ) );
        }
        
        
    }
    
    public function update_tickets_data()
    {
        if( wp_verify_nonce( $_POST['security'], 'single-event-data-nonce' ) ) 
        {
            $ep_functions = new Eventprime_Basic_Functions;
            
            $ticket_id = ( ! empty( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : '' );
            $qty = ( ! empty( $_POST['qty'] ) ? absint( $_POST['qty'] ) : '' );
            
            if (!$ticket_id || $qty < 0) {
                wp_send_json_error(['message' => 'Invalid ticket ID or quantity.']);
            }
            
            $response = $ep_functions->eventprime_update_cart_response($ticket_id,$qty);
            if(isset($response['message']))
            {
                wp_send_json_error($response);
            }
            else
            {
                wp_send_json_success($response);
            }
        }
    }
    
    public function check_license_status()
    {
        if( !wp_verify_nonce( $_POST['nonce'], 'ep-license-nonce' ) ) 
        {
            wp_send_json_error([
                'error' => 'invalid_nonce',
                'message' => esc_html__('Failed security checks.', 'eventprime-event-calendar-management')
            ]);
        }
        
        $global_settings = new Eventprime_Global_Settings;
        $license = new EventPrime_License();
        $admin_notices = new EventM_Admin_Notices;
        $global_settings->ep_get_settings();
        $license_key = (! empty($_POST['ep_license_key'])) ? sanitize_text_field($_POST['ep_license_key']) : '';

        
        // Fetch license data from server
        //print_r($license_key);
        if (empty($license_key)) {
            wp_send_json_error([
                'error' => 'invalid_license',
                'message' => esc_html__('Invalid license key.', 'eventprime-event-calendar-management')
            ]);
        }
        $response = $this->mg_edd_remote_get_extensions($license_key);
       // print_r($response);die;

         
        // Handle failed or unreachable server
        if (empty($response)) {
            wp_send_json_error([
                'error' => 'connection',
                'message' => esc_html__('Unable to connect to the license server. Please upload a .json license file.', 'eventprime-event-calendar-management')
            ]);
        }

        // Handle known license key errors
        if (isset($response['error']) || (isset($response['success']) && $response['success'] === false)) {
            wp_send_json_error([
                'message' => $response['error'] ?? esc_html__('Unable to retrieve license data.', 'eventprime-event-calendar-management')
            ]);
        }


        // Get plugins and filter only those that can be activated
        $valid_plugins = [];
        $error_messages = [];

        if (isset($response['plugins']) && is_array($response['plugins'])) {
            

            // Save only activatable plugins
            $all_license_data = get_option('metagauss_license_data', []);
            $all_license_data[$license_key] = [
                'plugins' => $response['plugins']
            ];
            update_option('metagauss_license_data', $all_license_data);
            // Save global settings (even if license is invalid — optional: move this below if you want conditional save)
            //$global_settings->ep_save_settings($global_settings_data);

            // Return success with only the activatable plugins
            wp_send_json_success([
                'plugins' => $valid_plugins,
                'license_key' => $license_key,
                'html' => esc_html__('License verified successfully.', 'eventprime-event-calendar-management')
            ]);
        } else {
            wp_send_json_error([
                'message' => esc_html__('Invalid license data received.', 'eventprime-event-calendar-management'),
                'response' => $response
            ]);
        }

    }
    
    public function save_license_settings() {
        if( !wp_verify_nonce( $_POST['nonce'], 'ep-license-nonce' ) ) 
        {
            wp_send_json_error([
                'error' => 'invalid_nonce',
                'message' => esc_html__('Failed security checks.', 'eventprime-event-calendar-management')
            ]);
        }
        
        $global_settings = new Eventprime_Global_Settings;
        $license = new EventPrime_License();
        $admin_notices = new EventM_Admin_Notices;
        $global_settings_data = $global_settings->ep_get_settings();
        $global_settings_data->ep_license_key = $license_key = (! empty($_POST['ep_license_key'])) ? sanitize_text_field($_POST['ep_license_key']) : '';

        
        // Fetch license data from server
        //print_r($license_key);
        if (empty($license_key)) {
            wp_send_json_error([
                'error' => 'invalid_license',
                'message' => esc_html__('Invalid license key.', 'eventprime-event-calendar-management')
            ]);
        }
        $response = $this->mg_edd_remote_get_extensions($license_key);
       // print_r($response);die;

         
        // Handle failed or unreachable server
        if (empty($response)) {
            wp_send_json_error([
                'error' => 'connection',
                'message' => esc_html__('Unable to connect to the license server. Please upload a .json license file.', 'eventprime-event-calendar-management')
            ]);
        }

        // Handle known license key errors
        if (isset($response['error']) || (isset($response['success']) && $response['success'] === false)) {
            wp_send_json_error([
                'message' => $response['error'] ?? esc_html__('Unable to retrieve license data.', 'eventprime-event-calendar-management')
            ]);
        }


        // Get plugins and filter only those that can be activated
        $valid_plugins = [];
        $error_messages = [];

        if (isset($response['plugins']) && is_array($response['plugins'])) {
            foreach ($response['plugins'] as $id => $plugin) {
                if (!empty($plugin['can_activate'])) {
                    $valid_plugins[$id] = $plugin;
                } else {
                    $error_messages[] = $plugin['name'] . ': ' . ($plugin['message'] ?? esc_html__('Cannot activate this license.', 'eventprime-event-calendar-management'));
                }
            }

            if (empty($valid_plugins)) {
                // None of the plugins can be activated — don't save
                
                wp_send_json_error([
                    'message' =>  $error_messages[0]
                ]);
            }

            // Save only activatable plugins
            $all_license_data = get_option('metagauss_license_data', []);
            $all_license_data[$license_key] = [
                'plugins' => $valid_plugins
            ];
            update_option('metagauss_license_data', $all_license_data);
            // Save global settings (even if license is invalid — optional: move this below if you want conditional save)
            $global_settings->ep_save_settings($global_settings_data);

            // Return success with only the activatable plugins
            wp_send_json_success([
                'plugins' => $valid_plugins,
                'license_key' => $license_key,
                'html' => esc_html__('License verified successfully.', 'eventprime-event-calendar-management')
            ]);
        } else {
            wp_send_json_error([
                'message' => esc_html__('Invalid license data received.', 'eventprime-event-calendar-management'),
                'response' => $response
            ]);
        }
    }

    
    public function deactivate_bundle_license()
    {
        if( wp_verify_nonce( $_POST['nonce'], 'ep-license-nonce' ) ) 
        {
            
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Permission denied.');
            }
            
            $license = new EventPrime_License();
            $license_key = sanitize_text_field($_POST['ep_license']);
            $license_data = get_option('metagauss_license_data', []);

            if (!isset($license_data[$license_key])) {
                wp_send_json_error('License key not found.');
            }
            
            $site_url = preg_replace( '/^https?:\/\/(www\.)?/', '', site_url() );
            
            $request = wp_remote_post( 'https://theeventprime.com/wp-json/custom/v1/deactivate-all', [
                'timeout' => 20,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => wp_json_encode([
                    'license_key' => $license_key,
                    'site_url' => $site_url
                ]),
            ] );
            
            $license_plugins = $license_data[$license_key]['plugins'];
            $status = array();
            foreach($license_plugins as $download_id =>$license_info)
            {
                $ext_license_key = isset($license_info->license_key)?$license_info->license_key:'';
                
                $item_key = $license->ep_get_extension_key_by_download_id($download_id);
                //$response = $license->ep_deactivate_license($ext_license_key, $download_id,$item_key);
                delete_option($item_key.'_license_response');
                delete_option($item_key. '_license_status');
                delete_option($item_key. '_license_key');
                delete_option($item_key. '_item_id');
                delete_option($item_key.'_license_id' );
                delete_option($item_key.'_'.$ext_license_key);
                
               
            }

            unset($license_data[$license_key]); // Remove entire license record
            update_option('metagauss_license_data', $license_data);

             wp_send_json_success([
                'plugins' => $request,
                'license_key' => $license_key,
                'html' => esc_html__('License deactivated successfully.', 'eventprime-event-calendar-management')
            ]);
        }
        else
        {
            wp_send_json_error('Security checks failed.');
        }
    }
    
    public function mg_edd_remote_get_extensions($license ) {
        $site_url = preg_replace( '/^https?:\/\/(www\.)?/', '', site_url() );

        $request = wp_remote_post( 'https://theeventprime.com/wp-json/custom/v1/extensions', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => wp_json_encode([
                'license_key' => $license,
                'site_url' => $site_url
            ]),
        ] );

        if ( is_wp_error( $request ) ) {
            error_log('[License Server Connection Error] ' . $request->get_error_message());
            return [];
        }

        return json_decode( wp_remote_retrieve_body( $request ), true );
    }
    
    public function install_remote_plugin() {
        if ( ! current_user_can( 'install_plugins' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $plugin_url = esc_url_raw($_POST['plugin_url'] ?? '');

        if ( empty($plugin_url) ) {
            wp_send_json_error( 'Missing plugin URL' );
        }
     
            $license_key = sanitize_text_field(filter_input( INPUT_POST, 'license_key' ));
            $item_id = sanitize_text_field(filter_input( INPUT_POST, 'itemid' ));
            $item_key = sanitize_text_field(filter_input( INPUT_POST, 'key' ));
            update_option( $item_key.'_license_key', $license_key );
            update_option( $item_key.'_license_id', $item_id );
            $license = new EventPrime_License();
            $response = $license->ep_activate_extension_license($license_key,$item_id);
            //wp_send_json_error( array( 'message' => $response) );
            if ($response['status']) 
            {
                // Store license info
                update_option($item_key.'_license_response', $response['data'] );
                update_option($item_key. '_license_status', 'valid');
                update_option($item_key. '_license_key', $license_key);
                update_option($item_key. '_item_id', $item_id);
                update_option($item_key.'_license_id', $item_id );
            } else {
                wp_send_json_error( array( 'message' => $response['message']) );
            }
            

        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/misc.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php';

        $upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
        $result = $upgrader->install($plugin_url);

        $plugin_file = $upgrader->plugin_info(); // Path to main plugin file
        activate_plugin($plugin_file);

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message()) );
        }
        
        
         wp_send_json_success( array( 'message' => esc_html__('Plugin installed and  activated.','eventprime-event-calendar-management')));
            
        
    }
    
    public function activate_plugin() {
        //check_ajax_referer('ep_license_nonce');
        if( wp_verify_nonce( $_POST['nonce'], 'ep-license-nonce' ) ) 
        {
            if (!current_user_can('activate_plugins')) {
                wp_send_json_error('Permission denied.');
            }

            $plugin = sanitize_text_field($_POST['plugin']);
            $full_path = $this->ep_get_full_plugin_path_by_file($plugin);

            if (!$full_path || !file_exists(WP_PLUGIN_DIR . '/' . $full_path)) {
                wp_send_json_error('Plugin not found.');
            }

            $result = activate_plugin($full_path);

            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }

            wp_send_json_success('Plugin activated.');
        }
        else
        {
            wp_send_json_error('Security checks failed.');
        }
    }

    public function deactivate_plugin() {
        //check_ajax_referer('ep_license_nonce');
        if( wp_verify_nonce( $_POST['nonce'], 'ep-license-nonce' ) ) 
        {
            if (!current_user_can('activate_plugins')) {
                wp_send_json_error('Permission denied.');
            }

            $plugin = sanitize_text_field($_POST['plugin']);
            $full_path = $this->ep_get_full_plugin_path_by_file($plugin);

            if (!$full_path || !file_exists(WP_PLUGIN_DIR . '/' . $full_path)) {
                wp_send_json_error('Plugin not found.');
            }

            deactivate_plugins($full_path);
            wp_send_json_success('Plugin deactivated.');
        }
        else
        {
            wp_send_json_error('Security checks failed.');
        }
    }
    
   
    public function ep_get_full_plugin_path_by_file($file_name) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        foreach ($plugins as $path => $data) {
            if (basename($path) === $file_name) {
                return $path; 
            }
        }

        return false; // not found
    }
    
   
    public function upload_license_file() 
    {
        
        $license_json = $_POST['license_data'] ?? '';
        if (empty($license_json)) {
            wp_send_json_error(['message' => 'No license data provided.']);
        }

        $license_data = json_decode(stripslashes($license_json), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($license_data) || !is_array($license_data)) {
            wp_send_json_error(['message' => 'Invalid license data format.']);
        }

        $all_license_data = get_option('metagauss_license_data', []);
        $i=0;
        foreach ($license_data as $license_key => $data) {
            
            if (empty($license_key)) {
                
                wp_send_json_error([
                    'message' => esc_html__('License key not found.', 'eventprime-event-calendar-management'),
                    
                ]);
            }
            
            if (!isset($data['plugins']) || !is_array($data['plugins'])) {
                 wp_send_json_error([
                    'message' => esc_html__('Invalid license data received.', 'eventprime-event-calendar-management'),
                    
                ]);
            }
            
            if($i==0)
            {
                $license_primary_key = $license_key;
            }
            $i++;

            // Save to DB
            $all_license_data[$license_key] = [
                'plugins' => $data['plugins']
            ];
            
           
            
        }
        
        update_option('metagauss_license_data', $all_license_data);
        update_option('metagauss_manual_license_data', 1);
        
        wp_send_json_success([
                'plugins' => $all_license_data[$license_primary_key]['plugins'],
                'license_key' => $license_primary_key,
                'html' => esc_html__('License verified successfully.', 'eventprime-event-calendar-management')
            ]);
    }


    
    public function delete_user_bookings_data()
    {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'error' => esc_html__( 'You must be logged in.', 'eventprime-event-calendar-management' ) ) );
        }

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ep-frontend-nonce' ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Invalid request.', 'eventprime-event-calendar-management' ) ) );
        }

        $user = wp_get_current_user();
        if ( ! $user || empty( $user->user_email ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Unable to identify user.', 'eventprime-event-calendar-management' ) ) );
        }

        $basic_function = new Eventprime_Basic_Functions();
        
        // Create request
        $deleted_data = $basic_function->ep_privacy_delete_personal_data( $user->user_email );

        if ( is_wp_error( $deleted_data ) ) {
            wp_send_json_error( array( 'error' => $deleted_data->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => esc_html__( 'Your bookings data has been removed.', 'eventprime-event-calendar-management' )
        ) );
    }

    public function export_user_bookings_data() 
    {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'You must be logged in.', 'eventprime-event-calendar-management' ) );
        }

        if ( ! wp_verify_nonce( $_POST['nonce'], 'ep-frontend-nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'eventprime-event-calendar-management' ) );
        }

        $user = wp_get_current_user();

        // Call the same exporter function used in wp_privacy_personal_data_exporters
        $ep_functions = new Eventprime_Basic_Functions;
        $result   = $ep_functions->ep_privacy_export_personal_data( $user->user_email );

        wp_send_json_success( array(
            'filename' => 'eventprime-data-' . date( 'Y-m-d' ) . '.json',
            'payload'  => $result['data'],
        ) );
    }
    
    public function request_data_erasure()
    {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'error' => esc_html__( 'You must be logged in.', 'eventprime-event-calendar-management' ) ) );
        }

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ep-frontend-nonce' ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Invalid request.', 'eventprime-event-calendar-management' ) ) );
        }

        $user = wp_get_current_user();
        if ( ! $user || empty( $user->user_email ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Unable to identify user.', 'eventprime-event-calendar-management' ) ) );
        }

        // Create request
        $request_id = wp_create_user_request( $user->user_email, 'remove_personal_data' );

        if ( is_wp_error( $request_id ) ) {
            wp_send_json_error( array( 'error' => $request_id->get_error_message() ) );
        }

        wp_send_user_request( $request_id );

        wp_send_json_success( array(
            'message' => esc_html__( 'Your data erasure request has been submitted. Please check your email to confirm.', 'eventprime-event-calendar-management' )
        ) );
    }
    
    public function request_data_export()
    {
         if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'error' => esc_html__( 'You must be logged in.', 'eventprime-event-calendar-management' ) ) );
        }

        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'ep-frontend-nonce' ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Invalid request.', 'eventprime-event-calendar-management' ) ) );
        }

        $user = wp_get_current_user();
        if ( ! $user || empty( $user->user_email ) ) {
            wp_send_json_error( array( 'error' => esc_html__( 'Unable to identify user.', 'eventprime-event-calendar-management' ) ) );
        }

        // Create export request
        $request_id = wp_create_user_request( $user->user_email, 'export_personal_data' );

        if ( is_wp_error( $request_id ) ) {
            wp_send_json_error( array( 'error' => $request_id->get_error_message() ) );
        }

        wp_send_user_request( $request_id );

        wp_send_json_success( array(
            'message' => esc_html__( 'Your data export request has been submitted. Please check your email to confirm.', 'eventprime-event-calendar-management' )
        ) );
    }
    
    public function delete_guest_booking_data()
    {
        $ep_functions = new Eventprime_Basic_Functions;
        if( wp_verify_nonce( $_POST['security'], 'event-booking-cancellation-nonce' ) ) {
            if( isset( $_POST['booking_id'] ) && isset($_POST['key']) ) {
                $order_id = absint( $_POST['booking_id'] );
                $key = sanitize_text_field($_POST['key']);
                $order_key = get_post_meta($order_id, 'ep_order_key', true);
                if(!empty($order_key) && $order_key == $key){
                    wp_delete_post( $order_id, true );
                    wp_send_json_success( array( 'message' => esc_html__( 'Booking delete Successfully.', 'eventprime-event-calendar-management' ), 'redirect_url' => esc_url( $ep_functions->ep_get_custom_page_url( 'profile_page' ) ) ) );
                }
                else
                {
                    wp_send_json_error( array( 'error' => esc_html__( 'You are not allowed to delete this booking', 'eventprime-event-calendar-management' ) ) );
                
                }
            } 
            else {
                wp_send_json_error( array( 'error' => esc_html__( 'You are not allowed to delete this booking', 'eventprime-event-calendar-management' ) ) );
                    
            }
            
        } else{
            wp_send_json_error( array( 'error' => esc_html__( 'Security check failed. Please refresh the page and try again later.', 'eventprime-event-calendar-management' ) ) );
        }
        
        if( wp_verify_nonce( $_POST['security'], 'single-event-data-nonce' ) ) 
        {
            
        }
            $ep_functions = new Eventprime_Basic_Functions;
            
            $ticket_id = ( ! empty( $_POST['ticket_id'] ) ? absint( $_POST['ticket_id'] ) : '' );
        $key = 
                
        $order_key = get_post_meta($order_id, 'ep_order_key', true);
        if(!empty($order_key) && $order_key != $key){

        }
    }

    
}
