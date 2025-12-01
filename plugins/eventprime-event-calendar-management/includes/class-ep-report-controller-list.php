<?php
/**
 * Class for booking module
 */

defined( 'ABSPATH' ) || exit;

class EventM_Report_Controller_List {
    /*
     * Get booking
     * @param $filter_args
     */
    public function ep_booking_reports_list($filter_args = null){
        $paged = 1;
        if( ! empty( $filter_args ) ) {
            if( ! empty( $filter_args->start_date ) ) {
                $start_date = $filter_args->start_date;
            } else{
                $start_date  = gmdate('d-m-Y', strtotime('-6 days'));
                $end_date  = gmdate('d-m-Y');
            }
            if( ! empty( $filter_args->end_date ) ) {
                $end_date = $filter_args->end_date;
            }
            if( isset( $filter_args->paged ) && ! empty( $filter_args->paged ) ) {
                $paged = $filter_args->paged;
            }
        } else{
            $start_date = gmdate( 'd-m-Y', strtotime( '-6 days' ) );
            $end_date  = gmdate( 'd-m-Y' );
        }
        $args = array(
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            // 'post_status' => 'completed',
            'post_status' => array('completed','pending'),
            'posts_per_page'=> 10,
            'offset'      => (int) ( $paged-1 ) * 10,
            'paged'       => $paged,
            'date_query' => array(
                array(
                    'after'  => array(
                        'year'   => gmdate( 'Y', strtotime( $start_date ) ),
                        'month'  => gmdate( 'm', strtotime( $start_date ) ),
                        'day'    => gmdate( 'd', strtotime( $start_date ) ),
                    ),
                    'before'     => array(
                        'year'   => gmdate( 'Y', strtotime( $end_date ) ),
                        'month'  => gmdate( 'm', strtotime( $end_date ) ),
                        'day'    => gmdate( 'd', strtotime( $end_date ) ),
                    ),
                    'inclusive'  => true,
                ),
            ),
            'meta_query'=> array('relation'=>'AND'),
            'post_type'   => 'em_booking'
        );

        if(!empty($filter_args)){
            if( isset( $filter_args->event_id ) && ! empty( $filter_args->event_id ) && $filter_args->event_id != 'all' ) {
                $args['meta_query'][] = array(
                    'key'     => 'em_event', 
                    'value'   => $filter_args->event_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                );
            }
        }
        $bookings = get_posts( $args );
        $data = array();
        $booking_controller = new EventPrime_Bookings;
        foreach( $bookings as $booking ) {
                $title = $booking->post_title;
                $data[] = $booking_controller->load_booking_detail( $booking->ID,false,$title,$booking );
        }
        
        $wp_query = new WP_Query( $args );
        $wp_query->posts = $data;
        return $wp_query;
    }

    /*
     * Get booking
     * @param $filter_args
     */
    public function ep_booking_reports( $filter_args = null ){
        if( ! empty( $filter_args ) ) {
            if( ! empty( $filter_args->start_date ) ) {
                $start_date = $filter_args->start_date;
            } else{
                $start_date  = gmdate('d-m-Y', strtotime('-6 days'));
                $end_date  = gmdate('d-m-Y');
            }
            if( ! empty( $filter_args->end_date ) ) {
                $end_date = $filter_args->end_date;
            }
            if( isset( $filter_args->paged ) && ! empty( $filter_args->paged ) ) {
                $paged = $filter_args->paged;
            }
        } else{
            $start_date = gmdate( 'd-m-Y', strtotime( '-6 days' ) );
            $end_date  = gmdate( 'd-m-Y' );
        }

        $args = array(
            'numberposts' => -1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'post_status' => array('completed','pending'),
            'date_query' => array(
                array(
                    'after'  => array(
                        'year'   => date('Y', strtotime($start_date)),
                        'month'  => date('m', strtotime($start_date)),
                        'day'    => date('d', strtotime($start_date)),
                    ),
                    'before'     => array(
                        'year'   => date('Y', strtotime($end_date)),
                        'month'  => date('m', strtotime($end_date)),
                        'day'    => date('d', strtotime($end_date)),
                    ),
                    'inclusive'  => true,
                ),
            ),
            'meta_query'=> array('relation'=>'AND'),
            'post_type'   => 'em_booking'
        );
        
        if(!empty($filter_args)){
            if( isset( $filter_args->event_id ) && ! empty( $filter_args->event_id ) && $filter_args->event_id != 'all' ) {
                $args['meta_query'][] = array(
                    'key'     => 'em_event', 
                    'value'   => $filter_args->event_id, 
                    'compare' => '=', 
                    'type'    => 'NUMERIC,'
                );
            }
        }
        
        $bookings =  new stdClass();
        $posts = get_posts( $args );
        // $posts = $data->posts;

        $bookings->stat = $this->ep_bookings_stat( $posts );        

        $data = $this->ep_booking_reports_list($filter_args);

        // $bookings->posts = $data->posts;
        $bookings->posts = $posts;
        $bookings->posts_details = $data;
        //Calculate Days
        $days_count = 7;
        $from = date_create( $start_date );
        $to = date_create( $end_date );
        if( ! empty( $from ) && ! empty( $to ) ) {
            $diff = date_diff( $to, $from );
            // $days_count = $diff->format('%a');
            $days_count = $diff->format('%a') + 1;
        }
        $bookings->stat->days_count = $days_count;
        $bookings->chart = $this->ep_bookings_chart( $days_count, $start_date, $end_date, $filter_args );
        return $bookings;
    }

    /*
     * Generate Booking Stats
     */
    public function ep_bookings_stat( $bookings ) {
        $data = new stdClass();
        $ep_functions = new Eventprime_Basic_Functions;
        $data->total_revenue = $data->daily_revenue = $data->total_booking = $data->total_tickets = $data->total_attendees = $data->coupon_discount = $ticket_sub_total = $coupon_discount = $total_attendees = 0;
        if( ! empty( $bookings ) ) {
            $booking_controller = new EventPrime_Bookings;
            $data->total_booking = count( $bookings );
            foreach( $bookings as $booking ) {
                //$booking = $booking_controller->load_booking_detail( $booking->ID );
                $order_info = isset( $booking->em_order_info ) ? $booking->em_order_info : array();
                $tickets = isset( $order_info['tickets'] ) ? $order_info['tickets'] : array();
                if( isset( $booking->em_old_ep_booking ) && ! empty( $booking->em_old_ep_booking ) ) {
                    if( ! empty( $tickets ) ){
                        foreach( $tickets as $ticket ){
                            $additional_fees = array();
                            if(isset($ticket->additional_fee)){
                                foreach($ticket->additional_fee as $fees){
                                    if(isset($booking->eventprime_updated_pattern))
                                    {
                                        $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price).')';
                                    }
                                    else
                                    {
                                        $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price * $ticket->qty).')';
                                    }
                                    
                                }
                            }
                            $ticket_sub_total = $ticket_sub_total + $ticket->subtotal;
                        }
                    } else if( ! empty( $order_info['order_item_data'] ) ) {
                        foreach( $order_info['order_item_data'] as $order_item_data ){
                            $ticket_sub_total = $ticket_sub_total + $order_item_data->sub_total;
                        }
                    }
                    $att_count = 1;
                    foreach( $booking->em_attendee_names as $key => $attendee_data ) {
                        $att_count++;
                    }
                } else{
                    if( ! empty( $tickets ) ){
                        foreach( $tickets as $ticket ){
                            $additional_fees = array();
                            if(isset($ticket->additional_fee)){
                                foreach($ticket->additional_fee as $fees){
                                    if(isset($booking->eventprime_updated_pattern))
                                    {
                                        $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price).')';
                                    }
                                    else
                                    {
                                        $additional_fees[] = $fees->label.' ('.$ep_functions->ep_price_with_position($fees->price * $ticket->qty).')';
                                    }
                                    
                                }
                            }
                            $ticket_sub_total = $ticket_sub_total + $ticket->subtotal;
                        }
                    }
                }
                $att_count = 0;
                if( ! empty( $booking->em_attendee_names ) ) {
                    foreach( $booking->em_attendee_names as $ticket_id => $attendee_data ) {
                        foreach( $attendee_data as $attendee ){
                            $att_count++;
                        }
                    }
                }
                if( !empty( $order_info['event_fixed_price'] ) ) {
                    $ticket_sub_total = $ticket_sub_total + $order_info['event_fixed_price'];
                }
                if( isset($order_info['coupon_code']) || isset($order_info['ep_coupon_data']) || isset($order_info['woo_coupon_data']) ){
                    $coupon_discount = $coupon_discount + $order_info['discount'];
                }
                $total_attendees = $total_attendees + $att_count; 
            }
        }
        $data->total_revenue = $ticket_sub_total;
        $data->coupon_discount = $coupon_discount;
        $data->total_attendees = $total_attendees;
        return $data;
    }
    /*
     * Generate booking chart data
     */
    public function ep_bookings_chart( $days_count, $start_date, $end_date, $filter_args ) {
        $chart_data = array();
        $start_date = new DateTime( $start_date );
        $end_date = new DateTime( $end_date );
        $end_date = $end_date->modify( '+1 day' ); 
        $interval = new DateInterval('P1D');
        $daterange = new DatePeriod( $start_date, $interval ,$end_date );
        // Step 4: Looping Through the Date Range
        foreach ( $daterange as $date ) {
            $args = array(
                'numberposts' => -1,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'post_status' => 'completed',
                'date_query' => array(
                    array(
                        'year'   => $date->format('Y'),
                        'month'  => $date->format('m'),
                        'day'    => $date->format('d'),
                    )
                ),
                'meta_query'=> array('relation'=>'AND'),
                'post_type'   => 'em_booking'
            );
            if( ! empty( $filter_args ) ) {
                if( isset( $filter_args->event_id ) && ! empty( $filter_args->event_id ) && $filter_args->event_id != 'all' ) {
                    $args['meta_query'][] = array(
                        'key'     => 'em_event', 
                        'value'   => $filter_args->event_id, 
                        'compare' => '=', 
                        'type'    => 'NUMERIC,'
                    );
                }
            }
            $posts = get_posts( $args );
            if( ! empty( $posts ) ) {
                $chart = new stdClass();
                $chart->date = $date->format('Y-m-d');
                $chart->booking = count($posts);
                $chart_data[] = $chart;
            } else{
                $chart = new stdClass();
                $chart->date = $date->format('Y-m-d');
                $chart->booking = 0;
                $chart_data[] = $chart;
            }
        }
        return $chart_data;
    }

    /*
     * @param ajax request(date, event_id, payment_method) 
     */
    public function eventprime_report_filters(){
        $filter_data = '';
        $data = $_POST;
        $start_date  = gmdate( 'Y-m-d', strtotime('-6 days'));
        $end_date  = gmdate( 'Y-m-d' );
        $event_id = 'all';
        if( ! empty( $data ) ) {
            if( isset( $data['ep_filter_date'] ) && ! empty( $data['ep_filter_date'] ) ) {
                $date_range = sanitize_text_field( $data['ep_filter_date'] );
                if ( preg_match( '/\(([^)]+)\)/', $date_range, $matches ) ) {
                    $date_range = $matches[1];
                }
                $dates = explode( ' - ', $date_range );
                if ( count($dates) == 1 ) {
                    $dates[1] = $dates[0];
                }
                $start = isset( $dates[0] ) && ! empty( $dates[0] ) ? $dates[0] : '';
                $end = isset( $dates[1] ) && ! empty( $dates[1] ) ? $dates[1] : '';
                $start_date = gmdate( 'Y-m-d', strtotime( $start ) );
                $end_date = gmdate( 'Y-m-d', strtotime( $end ) );
            }

            if( isset( $data['event_id'] ) && ! empty( $data['event_id'] ) ) {
                $event_id = sanitize_text_field( $data['event_id'] );
            }

            $filter_args = new stdClass();
            $filter_args->start_date = $start_date;
            $filter_args->end_date = $end_date;
            $filter_args->event_id = $event_id;  
            if( isset( $data['ep_report_action_type'] ) && sanitize_text_field( $data['ep_report_action_type']) == 'load_more' ) {
                $filter_args->paged = intval( sanitize_text_field( $data['paged'] ) ) + 1;
                return $this->ep_load_more_report_booking( $filter_args );
            }
            $filter_data = $this->ep_booking_reports( $filter_args );
        }

        $bookings_data = $filter_data;

        ob_start();
        include plugin_dir_path(EP_PLUGIN_FILE) . 'admin/partials/reports/parts/bookings/stat.php';
        $stat_html = ob_get_clean();

        ob_start();
        include plugin_dir_path(EP_PLUGIN_FILE) . 'admin/partials/reports/parts/bookings/booking-list.php';
        $booking_html = ob_get_clean();

        $bookings_data->stat_html  = $stat_html;
        $bookings_data->booking_html  = $booking_html;
        return $bookings_data;
    }

    /*
     * @param  $filter_args
     */
    public function ep_load_more_report_booking($filter_args){
        $bookings_data = new stdClass();
        $bookings_data->posts_details = $this->ep_booking_reports_list($filter_args);
        ob_start();
        include plugin_dir_path(EP_PLUGIN_FILE) . 'admin/partials/reports/parts/bookings/load-more-booking-list.php';
        $booking_html = ob_get_clean();
        $bookings_data->booking_html  = $booking_html;
        return $bookings_data;
    }
}