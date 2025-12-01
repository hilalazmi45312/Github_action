<?php
defined( 'ABSPATH' ) || exit;
//print_r($atts);die;
//global $post;
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
do_action('ep_before_event_details_page', $atts);
$atts                = array_change_key_case( (array) $atts, CASE_LOWER );
$event_id            = absint( $atts['id'] );
$post                = get_post( $event_id );
$events_data         = array();
if( ! empty( $post ) && $post->post_type=='em_event') {
    $events_data['post'] = $post;
    $events_data['event'] = $ep_functions->get_single_event_detail( $event_id,$post );
    $args = (object)$events_data;
    //print_r($args->event->em_venue);die;
    $args->event->venue_details = (!empty($args->event->em_venue) ) ? $ep_functions->get_single_venue($args->event->em_venue) : array();
    wp_enqueue_style(
        'ep-event-owl-slider-style',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/owl.carousel.min.css',
        false, $this->version
    );
    wp_enqueue_style(
        'ep-event-owl-theme-style',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/owl.theme.default.min.css',
        false, $this->version
    );
    wp_enqueue_script(
        'ep-event-owl-slider-script',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/owl.carousel.min.js',
        array( 'jquery' ), $this->version
    );
    wp_register_script( 'em-google-map', plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-map.js', array( 'jquery' ), $this->version );
    $gmap_api_key = $ep_functions->ep_get_global_settings( 'gmap_api_key' );
    if($gmap_api_key) {
        wp_enqueue_script(
            'google_map_key', 
            'https://maps.googleapis.com/maps/api/js?key='.$gmap_api_key.'&libraries=places,marker,drawing,geometry&callback=Function.prototype&loading=async', 
            array(), $this->version
        );
    }
    wp_enqueue_style( 'ep-responsive-slides-css' );
    wp_enqueue_script( 'ep-responsive-slides-js' );
    wp_enqueue_style(
        'ep-front-single-event-css',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-single-event.css',
        false, $this->version
    );
    wp_enqueue_script(
        'ep-event-single-script',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/ep-frontend-single-event.js',
        array( 'jquery' ), $this->version
    );
    // localized script array
    $max_tickets = $is_able_to_purchase = $ep_functions->ep_check_event_restrictions( $args->event );
    //$max_tickets = isset($args->event->em_event_max_tickets_per_order)?$args->event->em_event_max_tickets_per_order:0;
    $localized_script = array(
        'event'              => $events_data['event'],
        'subtotal_text'      => esc_html__( 'Subtotal', 'eventprime-event-calendar-management' ),
        'single_event_nonce' => wp_create_nonce( 'single-event-data-nonce' ),
        'event_booking_nonce'=> wp_create_nonce( 'event-booking-nonce' ),
        'starting_from_text' => esc_html__( 'Starting from', 'eventprime-event-calendar-management' ),
        'offer_applied_text' => esc_html__( 'Offers are applied in the next step.', 'eventprime-event-calendar-management' ),
        'no_offer_text'      => esc_html__( 'No offer available.', 'eventprime-event-calendar-management' ),
        'capacity_text'      => esc_html__( 'Capacity', 'eventprime-event-calendar-management' ),
        'ticket_left_text'   => esc_html__( 'tickets left!', 'eventprime-event-calendar-management' ),
        'allow_cancel_text'  => esc_html__( 'Cancellations Allowed', 'eventprime-event-calendar-management' ),
        'min_qty_text'       => esc_html__( 'Min Qnty', 'eventprime-event-calendar-management' ),
        'max_qty_text'       => esc_html__( 'Max Qnty', 'eventprime-event-calendar-management' ),
        'event_fees_text'    => esc_html__( 'Event Fee', 'eventprime-event-calendar-management' ),
        'ticket_now_btn_text'=> esc_html__( 'Get Tickets Now', 'eventprime-event-calendar-management' ),
        'multi_offfer_applied'=> esc_html__( 'Offers Applied', 'eventprime-event-calendar-management' ),
        'one_offfer_applied' => esc_html__( 'Offer Applied', 'eventprime-event-calendar-management' ),
        'book_ticket_text'   => esc_html__( 'Book Tickets', 'eventprime-event-calendar-management' ),
        'max_offer_applied'  => esc_html__( 'Max Offer Applied', 'eventprime-event-calendar-management' ),
        'ticket_disable_login' => esc_html__( 'You need to login to book this ticket.', 'eventprime-event-calendar-management' ),
        'ticket_disable_role' => esc_html__( 'You are not authorised to book this ticket.', 'eventprime-event-calendar-management' ),
        'no_ticket_message'  => esc_html__( 'You need to select ticket(s) first.', 'eventprime-event-calendar-management' ),
        'free_text'          => esc_html__( 'Free', 'eventprime-event-calendar-management' ),
        'max_tickets_per_order_msg' => $max_tickets[1],
    );
    // check for child events
    $events_data['event']->child_other_events = array();
    $recurring_events = $ep_functions->ep_get_child_events( $events_data['post']->ID );
    if( ! empty( $recurring_events ) && count( $recurring_events ) > 0 ) {
            $args->event->child_events = $recurring_events;
        }
    if( empty( $recurring_events ) ) {
        // check if event has parent event
        if( ! empty( $events_data['post']->post_parent ) ) {
            $other_events = $ep_functions->ep_get_child_events( $events_data['post']->post_parent );
            if( ! empty( $other_events ) && count( $other_events ) > 0 ) {
                $recurring_events = $ep_functions->load_event_full_data_detail( $other_events );
                $events_data['event']->child_other_events = $recurring_events;
            }
        }
    }
    if( ! empty( $recurring_events ) && count( $recurring_events ) > 0 ) {
        $cal_events = $ep_functions->get_front_calendar_view_event( $recurring_events );
        // load calendar library
        wp_enqueue_style(
            'ep-front-event-calendar-css',
            plugin_dir_url(EP_PLUGIN_FILE) . 'public/css/ep-calendar.min.css',
            false, $this->version
        );
        wp_enqueue_script(
            'ep-front-event-calendar-js',
            plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/ep-calendar.min.js',
            false, $this->version
        );
        wp_enqueue_script(
            'ep-front-event-fulcalendar-local-js',
            plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/locales-all.js',
            array( 'jquery' ), $this->version
        );
        $localized_script['cal_events'] = $cal_events;
        $localized_script['local'] = $ep_functions->ep_get_calendar_locale();
        $localized_script['start_of_week'] = get_option( 'start_of_week' );
    }
    wp_localize_script(
        'ep-event-single-script', 
        'em_front_event_object', 
        array(
            'em_event_data' => $localized_script,
        )
    );
    // enqueue custom scripts and styles from extension
    do_action( 'ep_event_detail_enqueue_custom_scripts', $atts ); 
}

$event_visibility = true;
if( isset($args) && isset($event_id) && post_password_required( $event_id ) ){
    // if events are password protected
    $event_visibility = false;
    echo get_the_password_form();
    
}
elseif ( isset($args) && get_post_status( $event_id ) === 'private' ) 
{
    // Check if the current user has permission to view private posts
    if ( current_user_can( 'read_private_posts' ) || get_current_user_id() === (int) get_post_field( 'post_author', $args->event->em_id ) ) {
        // User can view the private event (author or someone with permission)
        // Continue with normal event display
        $event_visibility = true;
    } 
    else 
    {
        $event_visibility = false;
        // Display message if the user doesn't have permission
        ?>
        <p><?php esc_html_e('This event is private. You do not have permission to view it.','eventprime-event-calendar-management'); ?></p>
       <?php
    }
} 

if($event_visibility===true)
{
?>
<div class="emagic" id="ep_single_event_detail_page_content">
    <?php
    $eventid = $args->event->em_id ?? $event_id;
    do_action( 'pg-ep-event-group-details-banner',$eventid);
    $themepath = $ep_requests->eventprime_get_ep_theme('event-tpl',$event_id);
    include $themepath;
?>
</div>
<?php
}