<?php
$ep_requests = new EP_Requests;
$basic_function = $ep_functions = new Eventprime_Basic_Functions;
if( !isset( $args->events ) || empty( $args->events ) ) {
    ?>
    <div class="ep-alert ep-alert-warning ep-mt-3">
        <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No event found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'Currently, there are no events planned. Please check back later.', 'eventprime-event-calendar-management' ); ?>
    </div><?php
    
}
?>
        <div class="ep-events ep-box-row ep-event-list-<?php echo esc_attr($args->display_style);?>-container <?php if( $args->display_style == 'masonry' ) { echo 'masonry-entry'; } ?> ep_events_front_views_<?php echo esc_attr( $args->display_style);?>_<?php echo esc_attr( $args->section_id);?>" id="ep_events_front_views_<?php echo esc_attr($args->display_style);?>">
            <?php
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

            }?>
        </div>
    

<?php
// Load event load more template

if(!isset($args->display_style) || (isset($args->display_style) && $args->display_style!=='slider'))
{
    $load_more = isset( $args->load_more ) ? $args->load_more : 0;
    if ( isset($args->event_atts) && !empty($args->event_atts) && count( $args->event_atts ) > 0 && isset($args->event_atts['load_more'])) {
        $load_more = $args->event_atts['load_more'];
    }
    // if( isset( $args->events->max_num_pages ) && $args->events->max_num_pages > 1 && isset( $args->load_more ) && $args->load_more == 1 ) {
    if( isset( $args->events->max_num_pages ) && $args->events->max_num_pages > 1 && $load_more == 1 ) {

        $show_no_of_events_card = ( isset( $args->atts['show'] ) && !empty( $args->atts['show'] ) ) ? $args->atts['show'] : $basic_function->ep_get_global_settings( 'show_no_of_events_card' );

        if( 'custom' == $show_no_of_events_card ) {
            $show_no_of_events_card = $basic_function->ep_get_global_settings( 'card_view_custom_value' );
        }
        if( ! empty( $args->events->posts ) && count( $args->events->posts ) >= $show_no_of_events_card ) {?>
            <div class="ep-events-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center ep-events-load-more-<?php echo esc_attr( $args->section_id );?>">
                <input type="hidden" id="ep-events-limit-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->limit );?>"/>
                <input type="hidden" id="ep-events-order-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->order );?>"/>
                <input type="hidden" id="ep-events-paged-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->paged );?>"/>
                <input type="hidden" id="ep-events-style-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->display_style );?>"/>
                <input type="hidden" id="ep-events-types-ids-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( isset( $args->types_ids ) ? implode( ',', $args->types_ids ) : '' ); ?>"/>
                <input type="hidden" id="ep-events-venues-ids-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( isset( $args->venue_ids ) ? implode( ',', $args->venue_ids ) : '' ); ?>"/>
                <input type="hidden" id="ep-events-cols-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->cols );?>"/>
                <input type="hidden" id="ep-events-i-events-<?php echo esc_attr( $args->section_id );?>" value="<?php echo esc_attr( $args->i_events );?>"/>
                <button data-max="<?php echo esc_attr( $args->events->max_num_pages );?>" id="ep-loadmore-events" class="ep-bg-white ep-btn ep-btn-outline-primary ep-loadmore-events" data-section_id="<?php echo esc_attr( $args->section_id );?>">
                    <span class="ep-spinner ep-spinner-border-sm ep-mr-1 ep-spinner-<?php echo esc_attr( $args->section_id );?>"></span>
                    <?php echo wp_kses_post( $args->load_more_text );   ?>
                </button>
            </div><?php
        }
    }

}
?>
<?php do_action( 'ep_event_filter_arguments_content', $args->atts,$args->params ); ?>
<script>
//ep_load_calendar_view();
</script>