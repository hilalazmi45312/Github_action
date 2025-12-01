<?php
$ep_functions = $basic_function = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
do_action( 'ep_events_list_before_render_content', $event_data ); ?>

    <div class="ep-events-container ep-box-wrap ep-<?php echo esc_attr($event_data->display_style );?>-view ep-my-4" id="ep-events-container">
        <?php
        if( $event_data->show_event_filter == 1 ) {
            ?>
        <form id="ep_event_search_form" class="ep-box-search-form ep-box-bottom" name="ep_event_search_form" method="get" role="search">
            <div class="ep-bg-light ep-border ep-rounded ep-px-3 ep-box-search-form-wrap">
                <div class="ep-box-row">
                    <div class="ep-box-col-8 ep-p-3 ep-position-relative ep-search-filter-bar">
                        <div class="ep-input-group">
                            <span class="ep-input-group-text ep-bg-white ep-text-muted">
                                <span class="material-icons-outlined">search</span>
                            </span>
                            <input name="keyword" id="ep_event_keyword_search" type="search" class="ep-form-control ep-form-control-sm ep-border-start-0" placeholder="<?php esc_html_e( 'Search Keyword', 'eventprime-event-calendar-management' ); ?>" autocomplete="off">
                            <button class="ep-btn ep-btn-dark ep-btn-sm ep-z-index-1" type="button" id="ep_event_find_events_btn">
                                <?php esc_html_e( 'Find Events', 'eventprime-event-calendar-management' ); ?>
                            </button>
                        </div>
                        <?php
                        // Load search filters template
                        $search_filters_file = $ep_requests->eventprime_get_ep_theme('events/search/search-filters');
                        include $search_filters_file;
                        ?>
                        <div class="ep-search-filter-overlay" style="display: none;"></div>
                    </div>
                    <div class="ep-box-col-4 ep-p-3 ep-d-flex ep-align-items-center ep-justify-content-end ep-event-views-col">
                        <?php
                        // Load event views filters template
                        $event_views_file = $ep_requests->eventprime_get_ep_theme('events/search/event-views');
                        include $event_views_file;
                        ?>
                    </div>
                </div>
            </div>
            <!-- Filters Applied Row -->
            <div class="ep-box-row ep-mt-3" id="ep_event_various_filters_section" style="display:none;">
                <div class="ep-box-col-12 ep-mb-3" id="ep_filter_count_clear" >
                    <span class="ep-text-small ep-mr-2" id="ep_total_filters_applied"></span>
                    <a class="ep-text-small" href="<?php echo esc_url( $ep_functions->ep_get_custom_page_url( 'event_page' ) );?>"><?php esc_html_e( 'Clear All', 'eventprime-event-calendar-management' ); ?></a>
                </div>
                <div class="ep-box-col-12" id="ep_applied_filters_section" ></div>
            </div>

            <!-- Event Loader -->
            <?php do_action( 'ep_add_loader_section', 'show' );?>
            <!-- Event Loader End -->
            <!-- Filters Applied Row End -->
        </form>
            <?php
        }?>
        
        <?php do_action( 'ep_events_list_before_content', $event_data ); ?>

        <div id="ep-events-content-container" class="ep-mt-4"><?php
            if( !isset( $event_data->events ) || empty( $event_data->events ) ) {
                ?>
                <div class="ep-alert ep-alert-warning ep-mt-3">
                    <?php ( isset( $_GET['ep_search'] ) ) ? esc_html_e( 'No event found related to your search.', 'eventprime-event-calendar-management' ) : esc_html_e( 'Currently, there are no events planned. Please check back later.', 'eventprime-event-calendar-management' ); ?>
                </div><?php
            }
                
                ?>
                <div class="ep-events ep-box-row ep-event-list-<?php echo esc_attr( $event_data->display_style );?>-container <?php if( $event_data->display_style == 'masonry' ) { echo esc_attr( 'masonry-entry' ); } ?> ep_events_front_views_<?php echo esc_attr( $event_data->display_style);?>_<?php echo esc_attr( $event_data->section_id);?>" id="ep_events_front_views_<?php echo esc_attr( $event_data->display_style);?>" data-section_id="<?php echo esc_attr( $event_data->section_id);?>">
                    <?php
                    switch ( $event_data->display_style ) {
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
            <?php do_action( 'ep_event_filter_arguments_content', $event_data->atts,$event_data->params ); ?>
        </div>
        <?php do_action( 'ep_events_list_after_content', $event_data ); ?>
    </div>