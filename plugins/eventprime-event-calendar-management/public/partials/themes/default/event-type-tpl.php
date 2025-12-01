<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
?>
<div class="ep-single-frontend-view-container ep-mb-5" id="ep_single_frontend_view_container">
            <div class="ep-view-container">

                <?php do_action( 'ep_before_event_types_contant');?>

                <!-- box wrapper -->
                <div class="ep-box-wrap ep-details-info-wrap">
                    <div class="ep-box-row">
                        <div class="ep-box-col-2 ep-event-type-img-section">
                            <div class="ep-single-box-thumb">
                                <div class="ep-single-figure-box">
                                    <img src="<?php echo esc_url($args->event_type->image_url); ?>" alt="<?php echo esc_attr( $args->event_type->name ); ?>"  class="ep-no-image"/>
                                </div>
                            </div>
                        </div>
                        <div class="ep-box-col-10 ep-event-type-details-section">
                            <div class="ep-single-box-info">
                                <div class="ep-single-box-content">
                                    <div class="ep-single-box-title-info">
                                        <div class="ep-single-box-title ep-organizer-name ep-fs-3 ep-fw-bold" title="<?php echo esc_attr( $args->event_type->name ); ?>">
                                            <?php echo esc_html( $args->event_type->name ); ?>
                                        </div>
                                        <div class="ep-single-age-group ep-pb-2"><?php 
                                            if( ! empty( $args->event_type->em_age_group ) ) {
                                                esc_html_e( 'Age Group', 'eventprime-event-calendar-management' );
                                                echo ': ';
                                                if( $args->event_type->em_age_group == 'parental_guidance' ) {
                                                    esc_html_e( 'All ages but parental guidance', 'eventprime-event-calendar-management' );
                                                } elseif( $args->event_type->em_age_group == 'custom_group' ) {
                                                    if( isset( $args->event_type->em_custom_group ) ) {
                                                        echo esc_html( $args->event_type->em_custom_group );
                                                    }
                                                }else{
                                                    esc_html_e( 'All','eventprime-event-calendar-management' );
                                                }
                                            }?>
                                        </div>
                                    </div>

                                    <div class="ep-single-box-summery ep-single-box-desc">
                                        <?php if ( isset( $args->event_type->description ) && $args->event_type->description !== '' ) {
                                            echo wp_kses_post( wpautop( $args->event_type->description ) );
                                        } else{
                                            esc_html_e( 'No description available', 'eventprime-event-calendar-management' );
                                        }?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <?php do_action( 'ep_after_event_types_contant');?>
                <?php
                if( $args->event_args['show_events'] == 1 ) {
                    ?>
                <div class="ep-box-col-12 event-<?php echo esc_attr($args->event_args['event_style']);?>-view">
                    <div class="ep-row-heading ep-text-center ep-my-4">
                        <div class="ep-upcoming-title ep-fw-bold ep-fs-5 ep-mt-5 ep-d-flex ep-justify-content-center">
                            <?php 
                            $single_type_event_section_title =  !empty( $ep_functions->ep_get_global_settings( 'single_type_event_section_title' ) ) ? $ep_functions->ep_get_global_settings( 'single_type_event_section_title' ) : esc_html__("Upcoming Events", "eventprime-event-calendar-management"); 
                            echo wp_kses_post( $single_type_event_section_title ); 
                            ?>
                            <span class="em_events_count-wrap em_bg"></span>
                        </div>
                    </div>
                    <div id="ep-upcoming-events" class="em_content_area ep-upcoming-events">
                        <div class="event-details-upcoming-<?php echo esc_attr($args->event_args['event_style']);?>-view">
                        <?php if( isset( $args->events->posts ) && ! empty( $args->events->posts ) && count( $args->events->posts ) > 0 ) {
                           
                            ?>
                            <div class="ep-box-row" id="ep-eventtype-upcoming-events"><?php
                            switch ( $args->event_args['event_style'] ) {
                                case 'card': 
                                    case 'grid': 
                                        $upcoming_card_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/card');
                                        include $upcoming_card_file;
                                        break;
                                    case 'mini-list': 
                                    case 'plain_list': 
                                        $upcoming_mini_list_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/mini-list');
                                        include $upcoming_mini_list_file;
                                        break;
                                    case 'list':
                                    case 'rows': 
                                        $upcoming_list_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/list');
                                        include $upcoming_list_file;
                                        break;
                                    default:
                                        $upcoming_mini_list_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/mini-list');
                                        include $upcoming_mini_list_file;
                                        break;
                            }?>
                            </div>
                        <?php
                        } else{?>
                            <div class="ep-alert ep-alert-warning ep-mt-3 ep-py-2">
                                <?php esc_html_e( 'No upcoming event found.', 'eventprime-event-calendar-management' ); ?>
                            </div><?php
                        }?>
                        <?php
                        if( $args->events->max_num_pages > 1 && isset( $args->event_args['load_more'] ) && $args->event_args['load_more'] == 1 ) {?>
                            <div class="ep-eventtype-upcoming-event-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center">
                                <button 
                                    data-max="<?php echo esc_attr($args->events->max_num_pages);?>" 
                                    id="ep-loadmore-upcoming-event-eventtype" 
                                    class="ep-btn ep-btn-outline-primary"
                                    data-id="<?php echo esc_attr( $args->eventtype_id );?>"
                                    data-style="<?php echo esc_attr( $args->event_args['event_style'] );?>"
                                    data-limit="<?php echo esc_attr( $args->event_args['event_limit'] );?>"
                                    data-cols="<?php echo esc_attr( $args->event_args['event_cols'] );?>"
                                    data-paged="<?php echo esc_attr( $args->event_args['paged'] );?>"
                                    data-pastevent="<?php echo esc_attr( $args->event_args['hide_past_events'] );?>"
                                >
                                    <span class="ep-spinner ep-spinner-border-sm ep-mr-1"></span>
                                    <?php esc_html_e( 'Load more', 'eventprime-event-calendar-management' );?>
                                </button>
                            </div><?php
                        }?>
                      </div>  
                    </div>
                </div>
                <?php
                }
                ?>
            </div>
        </div>