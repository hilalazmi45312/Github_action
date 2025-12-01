<?php
$basic_functions  = $ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
if( !isset( $args->events ) || empty( $args->events ) ) {
    ?>
    <div class="ep-alert ep-alert-warning ep-mt-3">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No event found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'Currently, there are no events planned. Please check back later.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
}
            switch ( $args->display_style ) {
                case 'card': 
                case 'square_grid': 
                    $events_card_file = $ep_requests->eventprime_get_ep_theme('events/views/card');
                    include $events_card_file;
                    break;

                case 'list':
                case 'rows':
                    $events_list_file = $ep_requests->eventprime_get_ep_theme('events/views/list');
                    include $events_list_file;
                    break;

                case 'masonry': 
                case 'staggered_grid': 
                    $events_masonry_file = $ep_requests->eventprime_get_ep_theme('events/views/masonry');
                    include $events_masonry_file;
                    break;

                case 'slider': 
                    $events_slider_file = $ep_requests->eventprime_get_ep_theme('events/views/slider');
                    include $events_slider_file;
                    break;

                default: 
                    $events_calendar_file = $ep_requests->eventprime_get_ep_theme('events/views/calendar');
                    include $events_calendar_file;
                    break;

            }
do_action( 'ep_event_filter_arguments_content', $args->atts,$args->params ); ?>
<script>
//ep_load_calendar_view();
</script>