<?php
defined( 'ABSPATH' ) || exit;
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$atts                     = array_change_key_case( (array) $atts, CASE_LOWER );
if ( !isset( $atts['id'] ) || empty( $atts['id'] ) ) { ?>
    <div class="ep-alert ep-alert-warning ep-mt-3">
        <?php echo esc_html_e( 'Please specify the Event-Type ID in the Shortcode.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
    return; 
}
        $event_type_id            = absint( $atts['id'] );
        $term                     = get_term( $event_type_id );
        if( ! empty( $term ) ) {
            wp_enqueue_script(
                'ep-eventtypes-details',
                plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/em-type-frontend-custom.js',
                array( 'jquery' ), EVENTPRIME_VERSION
            );
            wp_localize_script(
                'ep-eventtypes-details', 
                'ep_frontend', 
                array(
                    '_nonce' => wp_create_nonce('ep-frontend-nonce'),
                    'ajaxurl'   => admin_url( 'admin-ajax.php' )
                )
            );
            
            $event_types_data         = array();
            $event_types_data['term'] = $term;
            $event_types_data['event_type'] = $ep_functions->get_single_event_type( $term->term_id );
            // upcoming events
            $event_types_data['hide_upcoming_events'] = $ep_functions->ep_get_global_settings( 'shortcode_hide_upcoming_events' ); // Incorrect context!!!
            if ( isset( $atts['upcoming'] ) ) {
                $event_types_data['hide_upcoming_events'] = 1;
                if ( 1 === $atts['upcoming'] ) {
                    $event_types_data['hide_upcoming_events'] = 0;
                }
            }
            // check event limit
            if( isset( $atts['event_limit'] ) ){
                $single_type_event_limit = ( $atts["event_limit"] == 0 || $atts["event_limit"] == '' ) ? 10 : $atts["event_limit"];
            } else{
                $single_type_event_limit = ( $ep_functions->ep_get_global_settings( 'single_type_event_limit' ) == 0 ) ? 10 : $ep_functions->ep_get_global_settings( 'single_type_event_limit');
            }
            // check hide past events
            if( isset( $atts['hide_past_events'] ) ){
                $hide_past_events = $atts['hide_past_events'];
            } else{
                $hide_past_events = $ep_functions->ep_get_global_settings( 'single_type_hide_past_events' );
            }
            // get upcoming events for event_type
            $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
            // $single_type_event_orderby = $hide_past_events = $ep_functions->ep_get_global_settings( 'single_type_event_orderby' );
            // $single_type_event_order = $hide_past_events = $ep_functions->ep_get_global_settings( 'single_type_event_order' );
            $single_type_event_orderby = $ep_functions->ep_get_global_settings( 'single_type_event_orderby' );
            $single_type_event_order = $ep_functions->ep_get_global_settings( 'single_type_event_order' );
            $args = array(
                'orderby'        => $single_type_event_orderby,
                'order'        => $single_type_event_order,
                'posts_per_page' => $single_type_event_limit,
                'offset'         => (int)( $paged - 1 ) * (int)$single_type_event_limit,
                'paged'          => $paged,
                'hide_past_events'=> $hide_past_events,
                'post_status' => 'publish',
            );
            //$args['post_status'] = !empty( $hide_past_events ) == 1 ? 'publish' : 'any';

            //$event_types_data['events'] = $ep_functions->get_upcoming_events_for_event_type( $event_type_id, $args );
            $event_types_data['events'] = $ep_requests->get_upcoming_events_for_taxonomy('em_event_type', $event_type_id, $hide_past_events, $single_type_event_limit, $paged, $args);
            
            $event_args  = array();
            $event_args['show_events']      = ( isset( $atts['show_events'] ) ? $atts['show_events'] : $ep_functions->ep_get_global_settings( 'single_type_show_events' ) );
            $event_args['event_style']      = ( isset( $atts['event_style'] ) ? $atts['event_style'] : $ep_functions->ep_get_global_settings( 'single_type_event_display_view' ) );
            $event_args['event_limit']      = $single_type_event_limit;
            $event_args['event_cols']       = ( isset( $atts['event_cols'] ) ? $ep_functions->ep_check_column_size( $atts['event_cols'] ) : $ep_functions->ep_check_column_size( $ep_functions->ep_get_global_settings( 'single_type_event_column' ) ) );
            $event_args['load_more']        = ( isset( $atts['load_more'] ) ? $atts['load_more'] : $ep_functions->ep_get_global_settings( 'single_type_event_load_more' ) );
            $event_args['hide_past_events'] = $hide_past_events;
            $event_args['paged']            = $paged;
            $event_types_data['event_args'] = $event_args;
            $event_types_data['eventtype_id']= $event_type_id;
        }
 
        wp_enqueue_style(
            'ep-performer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, EVENTPRIME_VERSION
        );
        $args = (object)$event_types_data;
?>
<div class="emagic">
<?php
if( ! empty( $args ) && ! empty( $args->term ) ) {
$themepath = $ep_requests->eventprime_get_ep_theme('event-type-tpl');
include $themepath;
}
else
{?>
        <div class="ep-alert ep-alert-warning ep-mt-3">
            <?php echo esc_html_e( 'No event-type found.', 'eventprime-event-calendar-management' ); ?>
        </div>
<?php
}
?>
</div>