<?php
defined( 'ABSPATH' ) || exit;
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
 wp_enqueue_script(
            'ep-organizer-views-js',
            plugin_dir_url( EP_PLUGIN_FILE ) . '/public/js/em-organizer-frontend-custom.js',
            array( 'jquery' ), $this->version
        );
        wp_localize_script(
            'ep-organizer-views-js', 
            'ep_frontend', 
            array(
                '_nonce' => wp_create_nonce('ep-frontend-nonce'),
                'ajaxurl'   => admin_url( 'admin-ajax.php' ),
                'single_organizer_event_column' => $ep_functions->ep_get_global_settings( 'single_organizer_event_column' ),
            )
        );
        $atts                    = array_change_key_case( (array) $atts, CASE_LOWER );
        if ( !isset( $atts['id'] ) || empty( $atts['id'] ) ) { ?>
            <div class="ep-alert ep-alert-warning ep-mt-3">
                <?php echo esc_html_e( 'Please specify the Organizer ID in the Shortcode.', 'eventprime-event-calendar-management' ); ?>
            </div><?php
            return; 
        }
        $organizer_id            = absint( $atts['id'] );
        $term                    = get_term( $organizer_id );
        $organizers_data         = array();
        if( ! empty( $term ) && ! empty( $term->term_id ) ) {
            $organizers_data['term'] = $term;
            $organizers_data['organizer'] = $ep_functions->get_single_organizer( $term->term_id );
            // upcoming events
            $organizers_data['hide_upcoming_events'] = $ep_functions->ep_get_global_settings( 'shortcode_hide_upcoming_events' ); // Incorrect context!!!
            if ( isset( $atts['upcoming'] ) ) {
                $organizers_data['hide_upcoming_events'] = 1;
                if ( 1 === $atts['upcoming'] ) {
                    $organizers_data['hide_upcoming_events'] = 0;
                }
            }
            // check event limit
            if( isset( $atts['event_limit'] ) ){
                // $single_organizer_event_limit = ( $atts["event_limit"] == 0 || $atts["event_limit"] == '' ) ? EP_PAGINATION_LIMIT : $atts["event_limit"];
                $single_organizer_event_limit = ( $atts["event_limit"] == 0 || $atts["event_limit"] == '' ) ? 10 : $atts["event_limit"];
            } else{
                $single_organizer_event_limit = ( $ep_functions->ep_get_global_settings( 'single_organizer_event_limit' ) == 0 ) ? 10 : $ep_functions->ep_get_global_settings( 'single_organizer_event_limit');
            }
            // check hide past events
            if( isset( $atts['hide_past_events'] ) ){
                $hide_past_events = $atts['hide_past_events'];
            } else{
                $hide_past_events = $ep_functions->ep_get_global_settings( 'single_organizer_hide_past_events' );
            }

            // get upcoming events for organizer
            $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
            $args = array(
                'posts_per_page' => $single_organizer_event_limit,
                'offset'         => (int)( $paged - 1 ) * (int)$single_organizer_event_limit,
                'paged'          => $paged,
                'hide_past_events'=> $hide_past_events,
                'post_status' => 'publish',
            );
            //$args['post_status'] = !empty( $hide_past_events ) == 1 ? 'publish' : 'any';

            //$organizers_data['events'] = $ep_functions->get_upcoming_events_for_organizer( $organizer_id,$args );
            $organizers_data['events'] = $ep_requests->get_upcoming_events_for_taxonomy('em_event_organizer', $organizer_id, $hide_past_events, $single_organizer_event_limit, $paged, $args);
    
            $event_args  = array();
            $event_args['show_events']      = ( isset( $atts['show_events'] ) ? $atts['show_events'] : $ep_functions->ep_get_global_settings( 'single_organizer_show_events' ) );
            $event_args['event_style']      = ( isset( $atts['event_style'] ) ? $atts['event_style'] : $ep_functions->ep_get_global_settings( 'single_organizer_event_display_view' ) );
            $event_args['event_limit']      = $single_organizer_event_limit;
            $event_args['event_cols']       = ( isset( $atts['event_cols'] ) ? $ep_functions->ep_check_column_size( $atts['event_cols'] ) : $ep_functions->ep_check_column_size( $ep_functions->ep_get_global_settings( 'single_organizer_event_column' ) ) );
            $event_args['load_more']        = ( isset( $atts['load_more'] ) ? $atts['load_more'] : $ep_functions->ep_get_global_settings( 'single_organizer_event_load_more' ) );
            $event_args['hide_past_events'] = $hide_past_events;
            $event_args['paged']            = $paged;
            $organizers_data['event_args']  = $event_args;
            $organizers_data['organizer_id']= $organizer_id;
        }

        ob_start();
        wp_enqueue_style(
            'ep-performer-views-css',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/ep-frontend-views.css',
            false, $this->version
        );
$args = (object)$organizers_data;
?>
<div class="emagic">
<?php
if( ! empty( $args ) && ! empty( $args->term ) ) {
$themepath = $ep_requests->eventprime_get_ep_theme('organizer-tpl');
include $themepath;
}
else
{?>
        <div class="ep-alert ep-alert-warning ep-mt-3">
            <?php echo esc_html_e( 'No organizer found.', 'eventprime-event-calendar-management' ); ?>
        </div>
<?php
}
?>
</div>