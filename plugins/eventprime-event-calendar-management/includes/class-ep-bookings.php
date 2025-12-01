<?php
/**
 * Class for booking module
 */

defined( 'ABSPATH' ) || exit;

class EventPrime_Bookings {
    /**
     * Term Type.
     * 
     * @var string
     */
    private $post_type = 'em_booking';
    
    /**
     * Load order detail
     * 
     * @param int $order_id Order Id.
     * 
     * @param bool $with_event Load booking with or without event.
     * 
     * @return object $order.
     */
    public function load_booking_detail( $order_id, $with_event = true, $title='', $postdata ='' ) {
        $ep_functions = new Eventprime_Basic_Functions;
        if( empty( $order_id ) ) return;
        
        if(!empty($postdata))
        {
            $post = $postdata;
        }
        else
        {
            $post = get_post( $order_id );
        }
        
        if( empty( $post ) ) return;
        
        $booking = new stdClass();
        if(!empty($title))
        {
            $booking->post_title = $title;
        }
        $meta = get_post_meta( $order_id );
        foreach ( $meta as $key => $val ) {
            $booking->{$key} = maybe_unserialize( $val[0] );
        }

        $detail_url = get_permalink( $ep_functions->ep_get_global_settings( 'booking_details_page' ) );
        $booking->booking_detail_url = add_query_arg( array( 'order_id' => $order_id ), $detail_url );
        $booking->post_data = $post;
        $booking->event_data = array();
        if( ! empty( $with_event ) ) {
            // load event data
            $booking->event_data = $ep_functions->get_single_event( $booking->em_event );
        }
        
        return $booking;
    }
    
    /**
     * Get post data
     */
    public function get_bookings_post_data( $args = array(), $with_event = true ) {
        $default = array(
            'orderby'   => 'date',
            'order'       => 'ASC',
            'post_type'   => $this->post_type,
            'numberposts' => -1,
            'offset'      => 0
        );
        $args = wp_parse_args( $args, $default );
        $posts = get_posts( $args );
        if( empty( $posts ) )
           return array();
       
        $bookings = array();
        foreach( $posts as $post ) {
            if( empty( $post ) || empty( $post->ID ) ) continue;
            $booking = $this->load_booking_detail( $post->ID, $with_event );
            if( ! empty( $booking ) ) {
                $bookings[] = $booking;
            }
        }

        $wp_query = new WP_Query( $args );
        $wp_query->posts = $bookings;

        return $wp_query;
    }
    
   
    /**
     * Confirm Booking
     * 
     * @param int $booking_id Booking ID.
     * 
     * @param array $data Payment Data.
     */
    public function confirm_booking( $booking_id, $data = array() ) {
        $notifications = new EventM_Notification_Service;
        $sanitizer  = new EventPrime_sanitizer;
        $data = $sanitizer->sanitize($data);
        $booking = $this->load_booking_detail( $booking_id );
        if( empty( $booking->em_id ) ) return;
        
        // update booking status
        $booking_status = $this->ep_get_event_booking_status( $booking_id, $data );
        $postData = [ 'ID' => $booking->em_id, 'post_status' => $booking_status ];
        wp_update_post( $postData );

        update_post_meta( $booking->em_id, 'em_status', $booking_status );

        do_action( 'ep_after_update_booking_status', $booking->em_id, $data );

        // update order info
        $order_info = apply_filters( 'ep_add_booking_order_info', $booking->em_order_info, $data );
        $order_info['payment_gateway'] = $data['payment_gateway'];
        update_post_meta( $booking->em_id, 'em_order_info', $order_info );

        // update payment log
        $payment_log = apply_filters( 'ep_add_booking_payment_log', $data );
        
        update_post_meta( $booking->em_id, 'em_payment_log', $payment_log );

        do_action( 'ep_after_booking_complete', $booking->em_id, $data );

        // send email notification
        if ( strtolower( $data['payment_status'] ) == 'completed' || (strtolower($data['payment_gateway']) == 'offline' && strtolower($booking_status) == 'completed' )) {
            $notifications->booking_confirmed( $booking->em_id );
        } else if ( strtolower( $data['payment_status'] ) == 'refunded' ) {
            do_action( 'ep_booking_refunded', $booking );
            $notifications->booking_refund( $booking->em_id );
        } else {
            $notifications->booking_pending( $booking->em_id );
        }
    }

    /**
     * Get user upcoming bookings
     * 
     * @param int $user_id User Id.
     * 
     * @return array
     */
    public function get_user_upcoming_bookings( $user_id ) {
        $args = array(
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_status' => 'any',
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'     => 'em_user', 
                    'value'   => $user_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                ),
                array(
                    'key'     => 'em_status', 
                    'value'   => 'completed', 
                    'compare' => 'LIKE', 
                ),
            ),
            'post_type'   => 'em_booking'
        );
        $bookings = get_posts( $args );
        $upcoming_bookings = array();
        if( ! empty( $bookings ) && count( $bookings ) > 0 ) {
            $booked_events = array();
            foreach( $bookings as $booking ) {
                $booking_event_id = get_post_meta( $booking->ID, 'em_event', true );
                if( ! empty( $booking_event_id ) ) {
                    $event_start_date = get_post_meta( $booking_event_id, 'em_start_date', true );
                    if( $event_start_date > current_time( 'timestamp' ) ) {
                        if( ! in_array( $booking_event_id, $booked_events ) ) {
                            $booked_events[] = $booking_event_id;
                            $event_booking_data = $this->load_booking_detail( $booking->ID );
                            $event_booking_data->running_status = 'upcoming';
                            $upcoming_bookings[] = $event_booking_data;
                        }
                    } else{
                        $event_end_date = get_post_meta( $booking_event_id, 'em_end_date', true );
                        if( ! empty( $event_end_date ) ) {
                            if( $event_end_date > current_time( 'timestamp' ) ) {
                                if( ! in_array( $booking_event_id, $booked_events ) ) {
                                    $booked_events[] = $booking_event_id;
                                    $event_booking_data = $this->load_booking_detail( $booking->ID );
                                    $event_booking_data->running_status = 'ongoing';
                                    $upcoming_bookings[] = $event_booking_data;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $upcoming_bookings;
    }

    /**
     * Get user all bookings
     * 
     * @param int $user_id User Id.
     * 
     * @return array
     */
    public function get_user_all_bookings( $user_id ,$with_events = true) {
        $args = array(
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_status' => 'any',
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'     => 'em_user', 
                    'value'   => $user_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                ),
            ),
            'post_type'   => 'em_booking'
        );
        $bookings = get_posts( $args );
        $all_bookings = array();
        if( ! empty( $bookings ) && count( $bookings ) > 0 ) {
            foreach( $bookings as $booking ) {
                $all_bookings[] = $this->load_booking_detail( $booking->ID ,$with_events,'',$booking);
            }
        }
        return $all_bookings;
    }

    
    /*
     * Add note
     * @param $booking_id, str $note
     * retrun $reponse html;
     */
    public function add_notes($booking_id, $note){
        $response = array( 'message' => esc_html__( 'Successfully added.', 'eventprime-event-calendar-management' ), 'note' => $note );
        $notes = maybe_unserialize( get_post_meta( $booking_id, 'em_notes', true ) );
        if( is_array( $notes ) ) {
            $notes[] = $note;
        }else{
            $notes = array($note);
        }
        update_post_meta( $booking_id, 'em_notes', $notes );
        return $response;
    }
    
    /*
     * Update Order Status
     * @param $booking_id, str status
     * retrun $reponse html;
     */
    
    public function update_status($booking_id, $status = 'pending'){
        $response = array( 'message'=> esc_html__( 'Updated Successfully.', 'eventprime-event-calendar-management' ) );
        if($status == 'refunded'){
            if ( class_exists( 'EventM_Live_Seating_List_Controller' ) ) {
                $seating_controller = new EventM_Live_Seating_List_Controller();
                $seating_controller->refund_and_cancelled_seats_handler( $booking_id ); 
            }
            return $this->mark_booking_refunded($booking_id);
        }
        $postData = array( 'ID' => $booking_id, 'post_status' => $status );
        wp_update_post( $postData );

        update_post_meta( $booking_id, 'em_status', $status );

        $booking = $this->load_booking_detail( $booking_id );
        // update booking transient
        $key = 'ep_admin_all_bookings_data';
        $all_booking_data = ( ! empty( get_transient( $key ) ) ? get_transient( $key ) : array() );
        $all_booking_data[$booking_id] = $booking;
        set_transient( $key, $all_booking_data, 3600 );
        
        $ep_notify = new EventM_Notification_Service(); 
        if ( strtolower( $status ) == 'completed' || strtolower( $status ) == 'publish' ) {
            $ep_notify->booking_confirmed( $booking_id );
        } else if ( strtolower( $status ) == 'refunded' ) {
            do_action( 'ep_booking_refunded', $booking );
            $ep_notify->booking_refund( $booking_id );
        } else if ( strtolower( $status ) == 'cancelled' ) {
            if ( class_exists( 'EventM_Live_Seating_List_Controller' ) ) {
                $seating_controller = new EventM_Live_Seating_List_Controller();
                $seating_controller->refund_and_cancelled_seats_handler( $booking_id ); 
            }
            do_action( 'ep_booking_cancelled', $booking );
            $ep_notify->booking_cancel( $booking_id );
        } else {
            $ep_notify->booking_pending( $booking_id );
        }
        
        return $response;
    }
    
    /*
     * Update Order Status
     * @param $booking_id
     * retrun $reponse html;
     */
    public function mark_booking_refunded($booking_id){
        $response = array( 'message' => esc_html__( 'Successfully refunded.', 'eventprime-event-calendar-management' ) );
        $booking = $this->load_booking_detail( $booking_id );
        $refunded = apply_filters( 'ep_booking_refunded', true, $booking );
        if($refunded){
            $postData = array( 'ID' => $booking_id, 'post_status' => 'refunded' );
            wp_update_post( $postData );
            update_post_meta( $booking_id, 'em_status', 'refunded' );
            $booking = $this->load_booking_detail( $booking_id );
            // update booking transient
            $email_controller = new EventM_Notification_Service;
            $email_controller->booking_refund( $booking_id );
        }else{
            $response = array( 'message' => esc_html__( 'Something went wrong.', 'eventprime-event-calendar-management' ) );
        }
        return $response;
    }
    
    /**
     * Check event booking by user id
     * 
     * @param int $event_id Event ID.
     * 
     * @param int $user_id User ID.
     * 
     * @return int Booking ID.
     */
    public function check_event_booking_by_user( $event_id, $user_id ){
        $booking_id = '';
        if( ! empty( $event_id ) && ! empty( $user_id ) ) {
            $args = array(
                'numberposts' => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'post_status' => 'any',
                'meta_query'  => array(
                    'relation' => 'AND',
                    array(
                        'key'     => 'em_user', 
                        'value'   => $user_id, 
                        'compare' => '=', 
                        'type'    => 'NUMERIC,'
                    ),
                    array(
                        'key'     => 'em_event', 
                        'value'   => $event_id, 
                        'compare' => '=', 
                        'type'    => 'NUMERIC,'
                    ),
                ),
                'post_type'   => 'em_booking'
            );
            $bookings = get_posts( $args );
            if( ! empty( $bookings ) && count( $bookings ) > 0 ) {
                $booking_id = $bookings[0]->ID;
            }
        }
        return $booking_id;
    }

    /**
     * Check event booking by user id
     * 
     * @param int $event_id Event ID.
     * 
     * @return array Bookings.
     */
    public function get_event_bookings_by_event_id( $event_id ){
        $bookings = array();
        if( ! empty( $event_id ) ) {
            $args = array(
                'numberposts' => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'post_status' => array('completed','pending'),
                'meta_query'  => array(
                    array(
                        'key'     => 'em_event', 
                        'value'   => $event_id, 
                        'compare' => '=', 
                        'type'    => 'NUMERIC,'
                    ),
                ),
                'post_type'   => 'em_booking'
            );
            $bookings = get_posts( $args );
        }
        return $bookings;
    }
    
    public function update_offline_payment_status($booking_id, $status){
        $response = array( 'message' =>esc_html__('Updated Successfully.', 'eventprime-event-calendar-management'),'status'=>$status);
        $notifications = new EventM_Notification_Service;
        $ep_functions = new Eventprime_Basic_Functions;
        $send_ticket = $ep_functions->ep_get_global_settings('send_ticket_on_payment_received');
        // update booking status
        $booking = $this->load_booking_detail( $booking_id );
        if( empty( $booking->em_id ) ) return;
        $payment_log = isset($booking->em_payment_log) ? $booking->em_payment_log : array();
        $payment_log['offline_status'] = $status;
        update_post_meta($booking_id,'em_payment_log', $payment_log);

        if( strtolower( $status ) == 'received' && !empty($send_ticket)){
        	$notifications->booking_confirmed( $booking_id );
        }
        if( strtolower( $status ) == 'cancelled' ){
            $postData = [ 'ID' => $booking_id, 'post_status' => 'cancelled'];
            wp_update_post( $postData );   
            update_post_meta( $booking_id, 'em_status', 'cancelled' );
        }
        
        return $response;
    }
    
    /*
     * Export Bookings
     */
    public function export_bookings_bulk_action($action_type='all_export',$post_ids = array()){
        if($action_type == 'selected_export'){
            $args = array(
                'post_status' => array('completed','pending','cancelled','refunded','published'),
                'post__in' => $post_ids
            );
            $bookings = $this->get_bookings_post_data( $args );
            if( isset( $bookings->posts ) && count( $bookings->posts ) > 0 ) {
                $this->process_booking_downloadable_csv( $bookings->posts );
            }
        }
    }
    
    /*
     * Eport All bookings 
     */
    public function export_bookings_all($filters){
        $args = array(
            'post_status' => array('completed','pending','cancelled','refunded','published'),
            'meta_query' => array('relation'=>'AND')
        );
        if(isset($filters['status']) && sanitize_text_field($filters['status']) != 'all'){
            $args['post_status'] = array(sanitize_text_field($filters['status']));
        }
        if(isset($filters['event_id']) && sanitize_text_field($filters['event_id']) != 'all'){
            $args['meta_query'][] = array(
                'key'     => 'em_event',
                'value'   => sanitize_text_field($filters['event_id']),
                'compare' => '=',
                'type'    => 'NUMERIC'
            );
        }
        if(isset($filters['pay_method']) && sanitize_text_field($filters['pay_method']) != 'all'){
            $args['meta_query'][] = array(
                'key'     => 'em_payment_method',
                'value'   => sanitize_text_field($filters['pay_method']),
                'compare' => '=',
            );
        }
        if(isset($filters['start_date']) && !empty($filters['start_date'])){
            $start_date = $filters['start_date'];
            
            $args['meta_query'][] = array(
                'key'     => 'em_date',
                'value'   => strtotime($start_date),
                'compare' => '>=',
                'type'=>'NUMERIC'
            );
        }
        if(isset($filters['end_date']) && !empty($filters['end_date'])){
            $end_date = $filters['end_date'];
            
            $args['meta_query'][] = array(
                'key'     => 'em_date',
                'value'   => strtotime($end_date),
                'compare' => '<=',
                'type'=>'NUMERIC'
            );
        }
        $bookings = $this->get_bookings_post_data( $args );
        if( isset( $bookings->posts ) && count( $bookings->posts ) > 0 ) {
            return $this->process_booking_downloadable_csv( $bookings->posts );
        }
        return;
    }
    
    /*
     * Download Bookings CSV
     */
    public function process_booking_downloadable_csv( $bookings ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $bookings_data = array(); 
        $bookings_data[0]['id'] =              esc_html__('Booking ID', 'eventprime-event-calendar-management');
        $bookings_data[0]['user_name'] =       esc_html__('User Name', 'eventprime-event-calendar-management');
        $bookings_data[0]['email'] =           esc_html__('Email', 'eventprime-event-calendar-management');
        $bookings_data[0]['event'] =           esc_html__('Event Name', 'eventprime-event-calendar-management');
        $bookings_data[0]['sdate'] =           esc_html__('Start Date', 'eventprime-event-calendar-management');
        $bookings_data[0]['stime'] =           esc_html__('Start Time', 'eventprime-event-calendar-management');
        $bookings_data[0]['edate'] =           esc_html__('End Date', 'eventprime-event-calendar-management');
        $bookings_data[0]['etime'] =           esc_html__('End Time', 'eventprime-event-calendar-management');
        $bookings_data[0]['event_type'] =      esc_html__('Event Type', 'eventprime-event-calendar-management');
        $bookings_data[0]['venue'] =           esc_html__('Venue', 'eventprime-event-calendar-management');
        $bookings_data[0]['address'] =         esc_html__('Address', 'eventprime-event-calendar-management');
        $bookings_data[0]['seat_type'] =       esc_html__('Seating Type', 'eventprime-event-calendar-management');
        $bookings_data[0]['attendees'] =       esc_html__('Attendees', 'eventprime-event-calendar-management');
        $bookings_data[0]['seat'] =            esc_html__('Seat No.', 'eventprime-event-calendar-management');
        $bookings_data[0]['currency'] =        esc_html__('Currency', 'eventprime-event-calendar-management');
        $bookings_data[0]['price'] =           esc_html__('Price', 'eventprime-event-calendar-management');
        $bookings_data[0]['attendees_count'] = esc_html__('Ticket Count', 'eventprime-event-calendar-management');
        $bookings_data[0]['subtotal'] =        esc_html__('Subtotal', 'eventprime-event-calendar-management');
        $bookings_data[0]['event_price'] =     esc_html__('Fixed Event Price', 'eventprime-event-calendar-management');
        $bookings_data[0]['discount'] =        esc_html__('Discount', 'eventprime-event-calendar-management');
        $bookings_data[0]['amount_received'] = esc_html__('Amount Received', 'eventprime-event-calendar-management');
        $bookings_data[0]['gateway'] =         esc_html__('Payment Gateway', 'eventprime-event-calendar-management');
        $bookings_data[0]['booking_status'] =  esc_html__('Booking Status', 'eventprime-event-calendar-management');
        $bookings_data[0]['payment_status'] =  esc_html__('Payment Status', 'eventprime-event-calendar-management');
        $bookings_data[0]['log'] =             esc_html__('Transacton Log', 'eventprime-event-calendar-management');
        $bookings_data[0]['guest'] =           esc_html__('Guest Booking Data', 'eventprime-event-calendar-management');
        if( ! empty( $bookings ) ) {
            $row = 1;
            foreach( $bookings as $booking ) {
                $bookings_data[$row]['id']= $booking->em_id;
                $bookings_data[$row]['user_name'] = '';
                $bookings_data[$row]['email'] = '';
                $user_id = isset($booking->em_user) ? (int) $booking->em_user : 0;
                $user = get_user_by('ID',$user_id);
                if($user){
                        $bookings_data[$row]['user_name'] = $user->user_login ;
                        $bookings_data[$row]['email'] = $user->user_email;
                    }else{
                    $bookings_data[$row]['user_name'] =  esc_html__('Guest','eventprime-event-calendar-management');
                    $bookings_data[$row]['email'] =  esc_html__('Guest','eventprime-event-calendar-management');
                }
                
                $bookings_data[$row]['event'] = $booking->em_name;
                $bookings_data[$row]['sdate'] = '';
                $bookings_data[$row]['stime'] = '';
                $bookings_data[$row]['edate'] = '';
                $bookings_data[$row]['etime'] = '';
                $bookings_data[$row]['event_type'] = '';
                $bookings_data[$row]['venue'] = '';
                $bookings_data[$row]['address'] = '';
                $bookings_data[$row]['seat_type'] = '';
                if(isset($booking->event_data) && !empty($booking->event_data)){
                    $event = $booking->event_data;
                    $bookings_data[$row]['sdate'] = isset($event->em_start_date) && !empty($event->em_start_date) ? $ep_functions->ep_timestamp_to_date($event->em_start_date): '';
                    $bookings_data[$row]['edate'] = isset($event->em_end_date) && !empty($event->em_end_date) ? $ep_functions->ep_timestamp_to_date($event->em_end_date): '';
                    $bookings_data[$row]['stime'] = isset($event->em_start_time) && !empty($event->em_start_time) ? $event->em_start_time: '';
                    $bookings_data[$row]['etime'] = isset($event->em_end_time) && !empty($event->em_end_time) ? $event->em_end_time: '';
                    
                    if(isset($event->event_type_details) && !empty($event->event_type_details)){
                        $bookings_data[$row]['event_type'] = $booking->event_data->event_type_details->name;
                    }
                    if(isset($event->venue_details) && !empty($event->venue_details)){
                       $venue = $booking->event_data->venue_details;
                       $bookings_data[$row]['venue']= $venue->name; 
                       $bookings_data[$row]['address']=isset($venue->em_address) ? $venue->em_address : '';
                       $bookings_data[$row]['seat_type']=isset($venue->em_type) ? $venue->em_type : '';
                    }
                }
                $bookings_data[$row]['attendees'] = '';
                $bookings_data[$row]['seat'] = '';
                $bookings_data[$row]['currency']=isset($booking->em_payment_log['currency']) ? $booking->em_payment_log['currency'] : $ep_functions->ep_get_global_settings('currency');
                
                $order_info = isset($booking->em_order_info) ? $booking->em_order_info : array();
                $tickets = isset($order_info['tickets']) ? $order_info['tickets'] : array();
                $ticket_sub_total = 0;
                if( ! empty( $tickets ) ):
                    foreach($tickets as $ticket):
                        $ticket_sub_total = $ticket_sub_total + $ticket->subtotal;           
                    endforeach;
                endif;
                $bookings_data[$row]['price']=$ep_functions->ep_price_with_position($ticket_sub_total);
                $bookings_data[$row]['attendees_count']= '';
                $bookings_data[$row]['subtotal']=$ep_functions->ep_price_with_position($ticket_sub_total);
                $bookings_data[$row]['event_price']='';
                
                if( !empty( $order_info['event_fixed_price'] ) ) {
                    $bookings_data[$row]['event_price']= $ep_functions->ep_price_with_position($order_info['event_fixed_price']);
                }
                $bookings_data[$row]['discount']='';
                
                if(isset($order_info['coupon_code'])){
                    $bookings_data[$row]['discount']= $ep_functions->ep_price_with_position($order_info['discount']);
                }
                $bookings_data[$row]['amount_received']= $ep_functions->ep_price_with_position($order_info['booking_total']) ;
                
                $bookings_data[$row]['gateway']= isset($booking->em_payment_method) ? ucfirst($booking->em_payment_method) : 'N/A';
                $bookings_data[$row]['booking_status'] = isset($booking->em_status) ? ucfirst($booking->em_status) : 'N/A';
                $payment_log = isset($booking->em_payment_log) ? $booking->em_payment_log : array();
                $payment_status='';
                if(strtolower($bookings_data[$row]['gateway']) == 'offline'){
                    $payment_status = isset($payment_log['offline_status']) ? $payment_log['offline_status'] : '';
                }else{
                    $payment_status = isset($payment_log['payment_status']) ? $payment_log['payment_status'] : '';
                }
                $bookings_data[$row]['payment_status']= $payment_status;
                $bookings_data[$row]['log']=serialize($payment_log);
                $except = array('multi_price_option_data', 'coupon_code', 'coupon_discount', 'coupon_amount', 'coupon_type', 'applied_ebd', 'ebd_id', 'ebd_name', 'ebd_rule_type', 'ebd_discount_type', 'ebd_discount', 'ebd_discount_amount', 'ep_rg_field_password' );
                if(!empty($payment_log)){
                    foreach($payment_log as $logs_key => $logs){
                        if(in_array($logs_key, $except)){
                            unset($payment_log[$logs_key]);
                        }
                    }
                }
                $bookings_data[$row]['log']=serialize($payment_log);
                $bookings_data[$row]['guest']='';
                if(isset($booking->em_guest_booking) && !empty($booking->em_guest_booking)){
                    $bookings_data[$row]['guest'] = serialize($order_info['guest_booking_custom_data']);
                }
                
                $attendees_count = 0;
                if( ! empty( $booking->em_attendee_names ) && count( $booking->em_attendee_names ) > 0 ) {
                    $attendee_names = isset($booking->em_attendee_names) &&!empty($booking->em_attendee_names) ? maybe_unserialize($booking->em_attendee_names): array();
                    foreach( $attendee_names as $ticket_id => $attendee_data ) {
                        foreach( $attendee_data as $booking_attendees ) {
                            $booking_attendees_val = array_values( $booking_attendees );
                            $attendees_count++;
                        }
                    }
                    $bookings_data[$row]['attendees_count']=$attendees_count;
                    $booking_attendees_field_labels = array();
                    $count = 0;
                    foreach( $booking->em_attendee_names as $ticket_id => $attendee_data ) {
                        $booking_attendees_field_labels = $ep_functions->ep_get_booking_attendee_field_labels( $attendee_data[1] );
                        foreach( $attendee_data as $booking_attendees ) {
                            $seat = '';
                            $booking_attendees_val = array_values( $booking_attendees );
                            $attendees = '';
                            foreach( $booking_attendees_field_labels as $label_key => $labels ){
                                $formated_val = $ep_functions->ep_get_slug_from_string( $labels );
                                $at_val = '---';
                                foreach( $booking_attendees_val as $key => $baval ) {
                                    if($formated_val == 'seat'){
                                        if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                            $seat = $baval[$formated_val];
                                        }
                                    }
                                    if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                        $at_val = $baval[$formated_val];
                                        break;
                                    }
                                }
                                if( empty( $at_val ) ) {
                                    $formated_val = strtolower( $labels );
                                    foreach( $booking_attendees_val as $key => $baval ) {
                                        if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                            $at_val = $baval[$formated_val];
                                            break;
                                        }
                                    }
                                }
                                $attendees .= esc_html__( $labels, 'eventprime-event-calendar-management' ).' : '.$at_val.' | ';
                            }
                            $bookings_data[$row]['attendees'] = $attendees;
                            $bookings_data[$row]['seat'] = $seat;
                            if( $count < $attendees_count - 1 ) {
                                $row++;
                                $bookings_data[$row]['id']='';
                                $bookings_data[$row]['user_name']='';
                                $bookings_data[$row]['email']='';
                                $bookings_data[$row]['event']='';
                                $bookings_data[$row]['sdate']='';
                                $bookings_data[$row]['stime']='';
                                $bookings_data[$row]['edate']='';
                                $bookings_data[$row]['etime']='';
                                $bookings_data[$row]['event_type']='';
                                $bookings_data[$row]['venue']='';
                                $bookings_data[$row]['address']='';
                                $bookings_data[$row]['seat_type']='';
                                $bookings_data[$row]['attendees']=$attendees;
                                $bookings_data[$row]['seat']= $seat;
                                $bookings_data[$row]['currency']='';
                                $bookings_data[$row]['price']='';
                                $bookings_data[$row]['attendees_count']='';
                                $bookings_data[$row]['subtotal']='';
                                $bookings_data[$row]['event_price']='';
                                $bookings_data[$row]['discount']='';
                                $bookings_data[$row]['amount_received']='';
                                $bookings_data[$row]['gateway']='';
                                $bookings_data[$row]['booking_status']='';
                                $bookings_data[$row]['payment_status']='';
                                $bookings_data[$row]['log']='';
                                $bookings_data[$row]['guest']='';
                            }
                            $count++;
                        }
                    }
                }
                $row++;
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ep-bookings-'.md5(time().wp_rand(100, 999)).'.csv"');
        $f = fopen('php://output', 'w');
        foreach ( $bookings_data as $line ) {
            fputcsv( $f, $line );
        }

        die;
    }

    /**
     * Check if booking eligible for edit
     * 
     * @param int $event_id Event ID.
     * 
     * @return bool
     */
    public function check_booking_eligible_for_edit( $event_id ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $status = 0;
        if( ! empty( $event_id ) && empty( $ep_functions->check_event_has_expired( $event_id ) ) ) {
            $em_allow_edit_booking = get_post_meta( $event_id, 'em_allow_edit_booking', true );
            if( ! empty( $em_allow_edit_booking ) ) {
                $em_edit_booking_date_data = get_post_meta( $event_id, 'em_edit_booking_date_data', true );
                if( empty( $em_edit_booking_date_data ) ) {
                    return 1;
                }
                $em_edit_booking_date_type = ( ! empty( $em_edit_booking_date_data['em_edit_booking_date_type'] ) ? $em_edit_booking_date_data['em_edit_booking_date_type'] : '' );
                if( $em_edit_booking_date_type == 'custom_date' ) {
                    $em_edit_booking_date_date = $em_edit_booking_date_data['em_edit_booking_date_date'];
                    $em_edit_booking_date_time = $em_edit_booking_date_data['em_edit_booking_date_time'];
                    if( empty( $em_edit_booking_date_date ) ) {
                        $status = 1;
                    } else{
                        $end_date = $em_edit_booking_date_date;
                        if( ! empty( $em_edit_booking_date_time ) ) {
                            $end_date = $ep_functions->ep_timestamp_to_date( $em_edit_booking_date_date );
                            $end_date .= ' ' . $em_edit_booking_date_time;
                            $end_date = $ep_functions->ep_datetime_to_timestamp( $end_date, 'Y-m-d', $ep_functions->ep_get_current_user_timezone() );
                        }
                        if( $end_date > $ep_functions->ep_get_current_timestamp() ) {
                            $status = 1;
                        }
                    }
                } else if( $em_edit_booking_date_type == 'event_date' ) {
                    $em_edit_booking_date_event_option = $em_edit_booking_date_data['em_edit_booking_date_event_option'];
                    if( $em_edit_booking_date_event_option == 'event_ends' ) {
                        $status = 1;
                    } else if( $em_edit_booking_date_event_option == 'event_start' ) {
                        $em_start_date = get_post_meta( $event_id, 'em_start_date', true );
                        $em_start_time = get_post_meta( $event_id, 'em_start_time', true );
                        $start_date = $em_start_date;
                        if( ! empty( $em_start_time ) ) {
                            $start_date = $ep_functions->ep_timestamp_to_date( $em_start_date );
                            $start_date .= ' ' . $em_start_time;
                            $start_date = $ep_functions->ep_datetime_to_timestamp( $start_date );
                        }
                        if( $start_date > $ep_functions->ep_get_current_timestamp() ) {
                            $status = 1;
                        }
                    }
                } else if( $em_edit_booking_date_type == 'relative_date' ) {
                    $days = $em_edit_booking_date_data['em_edit_booking_date_days'];
                    $days_option = $em_edit_booking_date_data['em_edit_booking_date_days_option'];
                    $event_option = $em_edit_booking_date_data['em_edit_booking_date_event_option'];
                    $days_string  = ' days';
                    if( $days == 1 ) {
                        $days_string = ' day';
                    }
                    // + or - days
                    $days_icon = '- ';
                    if( $days_option == 'after' ) {
                        $days_icon = '+ ';
                    }
                    if( $event_option == 'event_start' ) {
                        $em_start_date = get_post_meta( $event_id, 'em_start_date', true );
                        $em_start_time = get_post_meta( $event_id, 'em_start_time', true );
                        $start_date = $ep_functions->ep_timestamp_to_date( $em_start_date );
                        if( ! empty( $em_start_time ) ) {
                            $start_date .= ' ' . $em_start_time;
                        }
                        $start_timestamp = $ep_functions->ep_datetime_to_timestamp( $start_date );
                        $min_start = strtotime( $days_icon . $days . $days_string, $start_timestamp );
                        if( $min_start > $ep_functions->ep_get_current_timestamp() ) {
                            $status = 1;
                        }
                    } else if( $event_option == 'event_ends' ) {
                        $em_end_date = get_post_meta( $event_id, 'em_end_date', true );
                        $em_end_time = get_post_meta( $event_id, 'em_end_time', true );
                        $book_start_date = $ep_functions->ep_timestamp_to_date( $em_end_date );
                        if( ! empty( $em_end_time ) ) {
                            $book_start_date .= ' ' . $em_end_time;
                        }
                        $book_start_timestamp = $ep_functions->ep_datetime_to_timestamp( $book_start_date );
                        $min_start = strtotime( $days_icon . $days . $days_string, $book_start_timestamp );
                        if( $min_start > $ep_functions->ep_get_current_timestamp() ) {
                            $status = 1;
                        }
                    }
                } else{
                    $status = 1;
                }
            }
        }
        return $status;
    }

    /**
     * Get booking status
     * 
     * @param int $booking_id Booking ID.
     * 
     * @param array $data Booking Payment data.
     * 
     * @return string $booking_status
     * 
     * @since 3.2.2
     */
    public function ep_get_event_booking_status( $booking_id, $data = array() ) {
        $ep_functions = new Eventprime_Basic_Functions;
        $booking_status = 'completed';
        if( ! empty( $booking_id ) && ! empty( $data ) ) {
            if( isset( $data['payment_gateway'] ) && ! empty( $data['payment_gateway'] ) && 'offline' == $data['payment_gateway'] ) {
                $default_booking_status = $ep_functions->ep_get_global_settings( 'default_booking_status' );
                if( ! empty( $default_booking_status ) ) {
                    $booking_status = $default_booking_status;
                }
            } else {
                if(isset($data['payment_status']) && $data['payment_status']=='pending') {
                    $booking_status = 'pending';
                }
            }
        }

        return $booking_status;
    }
    
    public function ep_get_admin_edit_attendees_html($booking){
        $ep_functions = new Eventprime_Basic_Functions;
        ?>
        <div class="emagic ep-position-relative" id="ep_event_edit_booking_page">
            <?php do_action( 'ep_add_loader_section' );?>
            <div class="ep-box-wrap">

                <div class="ep-box-row ep-mb-3">
                        <input type="hidden" name="ep_event_booking_id" value="<?php echo esc_attr( $booking->em_id );?>" />
                        <input type="hidden" name="ep_event_booking_user_id" value="<?php echo esc_attr( get_current_user_id() );?>" />
                        <div class="ep-box-row">
                            <div class="ep-box-col-12 ep-text-small ep-col-order-1">
                                <!-- Attendees Info Section -->
                                <?php if( ! empty( $booking->em_attendee_names ) ) {?>               
                                    <div id="ep_event_booking_attendee_section">
                                         <div class="ep-mb-3">
                                            <?php esc_html_e( 'Please update the attendees details below:', 'eventprime-event-calendar-management' );?>
                                        </div>
                                        <?php $ticket_num = $num = 1;
                                        $em_event_checkout_attendee_fields = ( ! empty( $booking->event_data->em_event_checkout_attendee_fields ) ? $booking->event_data->em_event_checkout_attendee_fields : array() );
                                        //$em_event_checkout_fixed_fields = ( ! empty( $args->event->em_event_checkout_fixed_fields ) ? $args->event->em_event_checkout_fixed_fields : array() );
                                        $attendees_data = $booking->em_attendee_names;
                                        foreach( $attendees_data as $ticket_id => $ticket_data ) {
                                            $event_ticket_data = $ep_functions->get_event_ticket_by_id( $ticket_id );
                                            if( ! empty( $event_ticket_data ) ) {
                                                $event_ticket_name = $event_ticket_data->name;
                                                $total_ticket_qty_count = count( $ticket_data );
                                                for( $q = 1; $q <= $total_ticket_qty_count; $q ++ ) {?>
                                                    <div class="ep-event-booking-attendee ep-mb-3">
                                                        <div class="ep-event-booking-attendee-head ep-box-row ep-overflow-hidden ep-border ep-rounded-top  ep-mb-0">
                                                            <div class="ep-box-col-12 ep-p-3 ep-d-flex ep-justify-content-between">
                                                                <span class="ep-fs-6 ep-fw-bold">
                                                                    <?php $ticket = $ep_functions->ep_global_settings_button_title( 'Ticket' );
                                                                    if( empty( $ticket ) ) {
                                                                        $ticket = esc_html__( 'Ticket', 'eventprime-event-calendar-management' ); 
                                                                    }
                                                                    echo esc_html($ticket); echo ' ' . esc_html( $ticket_num );?>
                                                                </span>
                                                                <span class="material-icons-round ep-align-bottom ep-bg-light ep-cursor ep-rounded-circle ep-ml-5 ep-event-attendee-handler">expand_more</span>
                                                            </div>
                                                        </div>
                                                        <div class="ep-event-booking-attendee-section ep-box-row ep-border ep-border-top-0 ep-rounded-bottom ">
                                                            <div class="ep-box-col-3 ep-text-small ep-ps-4 ep-d-flex ep-align-items-center">
                                                                <div class="ep-p-2">
                                                                    <div>
                                                                        <?php esc_html_e( 'Type:', 'eventprime-event-calendar-management' );?>&nbsp;
                                                                        <strong><?php echo esc_html( $event_ticket_name );?></strong>
                                                                    </div>
                                                                    <?php if( $event_ticket_data->category_id && $event_ticket_data->category_id > 0 ) {?>
                                                                        <div>
                                                                            <?php esc_html_e( 'Category:', 'eventprime-event-calendar-management' );?>&nbsp;
                                                                            <strong><?php echo esc_html( $ep_functions->get_ticket_category_name( $event_ticket_data->category_id, $booking->event_data ) );?></strong>
                                                                        </div><?php
                                                                    }?>
                                                                    <div>
                                                                        <?php esc_html_e( 'Attendee:', 'eventprime-event-calendar-management' );?>&nbsp;
                                                                        <strong><?php echo esc_html( $q );?></strong>&nbsp;
                                                                        <?php echo '('. esc_html__( 'of', 'eventprime-event-calendar-management' ). ' ' .esc_html( $total_ticket_qty_count ). ')';?>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="ep-box-col-9 ep-p-3">
                                                                <?php $at_field_num = 1;$match_find = 0;
                                                                foreach( $ticket_data as $single_ticket_sec ) {
                                                                    if( $at_field_num == $q ) {
                                                                        $match_find = 1;
                                                                        foreach( $single_ticket_sec as $key => $ticket_attendee_data ) {
                                                                            if( $key == 'name' ) {
                                                                                foreach( $ticket_attendee_data as $key_field => $attendee_field_data ) {
                                                                                    if( $key_field == 'first_name' ) {?>
                                                                                        <div class="ep-mb-3">
                                                                                            <label for="name" class="form-label ep-text-small">
                                                                                                <?php esc_html_e( 'First Name', 'eventprime-event-calendar-management' );
                                                                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] ) ) {?>
                                                                                                    <span class="ep-checkout-fields-required"><?php echo esc_html( '*' ); ?></span><?php
                                                                                                }?>
                                                                                            </label>
                                                                                            <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][name][first_name]" type="text" class="ep-form-control" 
                                                                                                value="<?php echo esc_html( $attendee_field_data );?>"
                                                                                                id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_first_name" 
                                                                                                placeholder="<?php esc_html_e( 'First Name', 'eventprime-event-calendar-management' );?>"
                                                                                                <?php if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] ) ) { echo 'required="required"'; }?>
                                                                                            >
                                                                                            <div class="ep-error-message" id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_first_name_error"></div>
                                                                                        </div><?php
                                                                                    }
                                                                                    if( $key_field == 'middle_name' ) {?>
                                                                                        <div class="ep-mb-3">
                                                                                            <label for="name" class="form-label ep-text-small">
                                                                                                <?php esc_html_e( 'Middle Name', 'eventprime-event-calendar-management' );
                                                                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name_required'] ) ) {?>
                                                                                                    <span class="ep-checkout-fields-required"><?php echo esc_html( '*' ); ?></span><?php
                                                                                                }?>
                                                                                            </label>
                                                                                            <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][name][middle_name]" type="text" class="ep-form-control" 
                                                                                                value="<?php echo esc_html( $attendee_field_data );?>"
                                                                                                id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_middle_name" 
                                                                                                placeholder="<?php esc_html_e( 'Middle Name', 'eventprime-event-calendar-management' );?>"
                                                                                                <?php if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_middle_name_required'] ) ) { echo 'required="required"'; }?>
                                                                                            >
                                                                                            <div class="ep-error-message" id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_middle_name_error"></div>
                                                                                        </div><?php
                                                                                    }
                                                                                    if( $key_field == 'last_name' ) {?>
                                                                                        <div class="ep-mb-3">
                                                                                            <label for="name" class="form-label ep-text-small">
                                                                                                <?php esc_html_e( 'Last Name', 'eventprime-event-calendar-management' );
                                                                                                if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name_required'] ) ) {?>
                                                                                                    <span class="ep-checkout-fields-required"><?php echo esc_html( '*' ); ?></span><?php
                                                                                                }?>
                                                                                            </label>
                                                                                            <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][name][last_name]" type="text" class="ep-form-control" 
                                                                                                value="<?php echo esc_html( $attendee_field_data );?>"
                                                                                                id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_last_name" 
                                                                                                placeholder="<?php esc_html_e( 'Last Name', 'eventprime-event-calendar-management' );?>"
                                                                                                <?php if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_last_name_required'] ) ) { echo 'required="required"'; }?>
                                                                                            >
                                                                                            <div class="ep-error-message" id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_last_name_error"></div>
                                                                                        </div><?php
                                                                                    }
                                                                                }
                                                                            }else{
                                                                                if($key !='seat'){
                                                                                    $checkout_require_fields = array();
                                                                                    $core_field_types = array_keys( $ep_functions->ep_get_core_checkout_fields() );
                                                                                    if( isset( $em_event_checkout_attendee_fields['em_event_checkout_fields_data_required'] ) && ! empty( $em_event_checkout_attendee_fields['em_event_checkout_fields_data_required'] ) ) {
                                                                                        $checkout_require_fields = $em_event_checkout_attendee_fields['em_event_checkout_fields_data_required'];
                                                                                    }
                                                                                    $checkout_fields = $ep_functions->get_checkout_field_by_id( $key );
                                                                                    $input_name = $ep_functions->ep_get_slug_from_string( $ticket_attendee_data['label'] );
                                                                                    if( in_array( $checkout_fields->type, $core_field_types ) ) {?>
                                                                                        <div class="ep-mb-3">
                                                                                            <label for="name" class="form-label ep-text-small">
                                                                                                <?php echo esc_html( $ticket_attendee_data['label'] );
                                                                                                if( in_array( $checkout_fields->id, $checkout_require_fields ) ) {?>
                                                                                                    <span class="ep-checkout-fields-required"><?php echo esc_html( '*' ); ?></span><?php
                                                                                                }?>
                                                                                            </label>
                                                                                            <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][<?php echo esc_attr( $checkout_fields->id );?>][label]" type="hidden" value="<?php echo esc_attr( $ticket_attendee_data['label'] );?>">
                                                                                            <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][<?php echo esc_attr( $checkout_fields->id );?>][<?php echo esc_attr( $input_name );?>]" 
                                                                                                value="<?php echo esc_html( $ticket_attendee_data[$input_name] );?>"
                                                                                                type="<?php echo esc_attr( $checkout_fields->type );?>" 
                                                                                                class="ep-form-control" 
                                                                                                id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_<?php echo esc_attr( $checkout_fields->id );?>_<?php echo esc_attr( $input_name );?>" 
                                                                                                placeholder="<?php echo esc_attr( $ticket_attendee_data['label'] );?>"
                                                                                                <?php if( in_array( $checkout_fields->id, $checkout_require_fields ) ) { echo 'required="required"'; } ?>
                                                                                            >
                                                                                            <div class="ep-error-message" id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_<?php echo esc_attr( $checkout_fields->id );?>_<?php echo esc_attr( $input_name );?>_error"></div>
                                                                                        </div><?php
                                                                                    } else{
                                                                                        $checkout_field_data = array( 'fields' => $checkout_fields, 'tickets' => $event_ticket_data, 'checkout_require_fields' => $checkout_require_fields, 'num' => $num, 'value' => $ticket_attendee_data[$input_name] );
                                                                                        do_action( 'ep_event_advanced_checkout_fields_section', $checkout_field_data );
                                                                                    }
                                                                                }else{?>
                                                                                    <div class="ep-mb-3">
                                                                                        <label for="name" class="form-label ep-text-small">
                                                                                        <?php esc_html_e( 'Seat', 'eventprime-event-calendar-management' );
                                                                                        if( ! empty( $em_event_checkout_attendee_fields['em_event_checkout_name_first_name_required'] ) ) {?>
                                                                                            <span class="ep-checkout-fields-required"><?php echo esc_html( '*' ); ?></span><?php
                                                                                        }?>
                                                                                        </label>
                                                                                        <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][seat][label]" type="hidden" class="ep-form-control" 
                                                                                            value="<?php echo esc_html( $ticket_attendee_data['label'] );?>"
                                                                                        >
                                                                                        <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][seat][seat]" type="text" class="ep-form-control" 
                                                                                           value="<?php echo esc_html( $ticket_attendee_data['seat'] );?>" readonly="readonly"
                                                                                        >
                                                                                        <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][seat][area]" type="hidden" class="ep-form-control" 
                                                                                            value="<?php echo esc_html( $ticket_attendee_data['area'] );?>"
                                                                                        >
                                                                                        <input name="ep_booking_attendee_fields[<?php echo esc_attr( $event_ticket_data->id );?>][<?php echo esc_attr( $num );?>][seat][uid]" type="hidden" class="ep-form-control" 
                                                                                            value="<?php echo esc_html( $ticket_attendee_data['uid'] );?>"
                                                                                        >
                                                                                        <div class="ep-error-message" id="ep_booking_attendee_fields_<?php echo esc_attr( $event_ticket_data->id );?>_<?php echo esc_attr( $num );?>_name_first_name_error"></div>
                                                                                    </div><?php

                                                                                }
                                                                                
                                                                            }


                                                                        }
                                                                        do_action('ep_booking_attendees_edit_admin',$event_ticket_data->id, $num, $single_ticket_sec);
                                                                        $num++;
                                                                    }
                                                                    if( $match_find == 1 ){
                                                                        break;
                                                                    }
                                                                    $at_field_num++;
                                                                    
                                                                }?>
                                                            </div>
                                                        </div>
                                                    </div><?php
                                                    $ticket_num++;
                                                }
                                            }
                                            
                                        }?>
                                    </div><?php
                                }?>
                                <!-- Attendees info section End -->
                            </div>
                        </div>
                </div>

            </div>
        </div><?php
        
    }
    
}