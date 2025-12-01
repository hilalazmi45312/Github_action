<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;

$ep_organizer_socials_exists = isset($args->organizer->em_social_links) && !empty($args->organizer->em_social_links) ? !empty(array_filter($args->organizer->em_social_links) ) : [];
?>
<div class="ep-single-frontend-view-container ep-mb-5" id="ep_single_frontend_view_container">
    <div class="ep-view-container">

        <?php do_action('ep_before_organizers_contant'); ?>

        <!-- box wrapper -->
        <div class="ep-box-wrap ep-details-info-wrap">
            <div class="ep-box-row">
                <div class="ep-box-col-2 ep-organizer-img-section">
                    <div class="ep-single-box-thumb">
                        <div class="ep-single-figure-box"><?php
                            if( ! empty( $args->organizer->image_url ) ) {?>
                                <img src="<?php echo esc_url( $args->organizer->image_url ); ?>" alt="<?php echo esc_attr( $args->organizer->name ); ?>" class="ep-no-image" ><?php
                            }?>
                        </div>
                    </div>
                </div>
                
                <div class="ep-box-col-10 ep-organizer-details-section">
                    <div class="ep-single-box-info">
                        <div class="ep-single-box-content">
                            <div class="ep-single-box-title-info">
                                <h3 class="ep-single-box-title ep-organizer-name" title="<?php echo esc_attr( $args->organizer->name ); ?>">
                                    <?php echo esc_html( $args->organizer->name ); ?>
                                </h3>
                                <ul class="ep-single-box-details-meta ep-mx-0 ep-my-2 ep-p-0">
                                <?php 
                                if ( isset($args->organizer->em_organizer_emails) && !empty($args->organizer->em_organizer_emails) ) { ?>
                                    <li> 
                                        <div class="ep-details-box-icon ep-pr-2">
                                        <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/email-icon.png';?>
                                        <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </div>
                                        <div class="ep-details-box-value">
                                        <?php 
                                        if ( ! empty( $args->organizer->em_organizer_emails ) && count( $args->organizer->em_organizer_emails ) > 0 && ! empty( $args->organizer->em_organizer_emails[0] ) ) { 
                                            foreach( $args->organizer->em_organizer_emails as $key => $val ) {
                                                $args->organizer->em_organizer_emails[$key] = '<a href="mailto:'.$val.'">'.htmlentities( $val ).'</a>';
                                            }
                                            echo wp_kses_post(implode( ', ', $args->organizer->em_organizer_emails )); 
                                        } else {
                                            esc_html_e( 'Not Available', 'eventprime-event-calendar-management' );
                                        } ?>
                                        </div>
                                    </li>
                                    <?php 
                                }
                                if ( isset($args->organizer->em_organizer_phones) && !empty($args->organizer->em_organizer_phones) ) { ?>
                                    <li>
                                        <div class="ep-details-box-icon ep-pr-2">
                                        <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/phone-icon.png';?>
                                        <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </div>
                                        <div class="ep-details-box-value">
                                        <?php if ( ! empty( $args->organizer->em_organizer_phones ) && count( $args->organizer->em_organizer_phones ) > 0  && ! empty( $args->organizer->em_organizer_phones[0] ) ) {
                                            echo wp_kses_post(implode( ', ', $args->organizer->em_organizer_phones )); 
                                        }else {
                                            esc_html_e( 'Not Available', 'eventprime-event-calendar-management' );
                                        } ?>
                                        </div>
                                    </li>
                                    <?php 
                                }
                                if(isset($args->organizer->em_organizer_websites) && !empty($args->organizer->em_organizer_websites)) { ?>
                                    <li>
                                        <div class="ep-details-box-icon ep-pr-2">
                                        <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/website-icon.png';?>
                                        <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </div>
                                        <div class="ep-details-box-value">
                                        <?php if ( ! empty( $args->organizer->em_organizer_websites ) && count( $args->organizer->em_organizer_websites ) > 0 && ! empty( $args->organizer->em_organizer_websites[0] ) ) { 
                                            foreach( $args->organizer->em_organizer_websites as $key => $val ) {
                                                if( ! empty( $val ) ){
                                                    $args->organizer->em_organizer_websites[$key] = '<a href="'.$val.'" target="_blank">'.htmlentities( $val ).'</a>';
                                                }
                                            }
                                            echo wp_kses_post(implode( ', ', $args->organizer->em_organizer_websites )); 
                                        } else {
                                            esc_html_e( 'Not Available', 'eventprime-event-calendar-management' ); 
                                        }?>
                                        </div>
                                    </li>
                                    <?php
                                }
                                ?>
                                </ul>
                            </div>

                            <?php if ( ! empty( $ep_organizer_socials_exists ) ){ ?>
                                <div class="ep-single-box-social"><?php
                                    if( ! empty( $args->organizer->em_social_links['facebook'] ) ){ ?>
                                        <a href="<?php echo esc_url( $args->organizer->em_social_links['facebook'] );?>" target="_blank" title="<?php echo esc_attr( 'Facebook' );?>" class="ep-facebook-f"> 
                                            <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/facebook-icon.png';?>
                                            <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </a><?php
                                    }
                                    if( ! empty( $args->organizer->em_social_links['instagram'] ) ){ ?>
                                        <a href="<?php echo esc_url( $args->organizer->em_social_links['instagram'] );?>" target="_blank" title="<?php echo esc_attr( 'Instagram' );?>" class="ep-instagram">
                                            <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/instagram-icon.png';?>
                                            <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </a><?php
                                    }
                                    if( ! empty( $args->organizer->em_social_links['linkedin'] ) ) { ?>
                                        <a href="<?php echo esc_url( $args->organizer->em_social_links['linkedin'] );?>" target="_blank" title="<?php echo esc_attr( 'Linkedin' );?>" class="ep-twitter"> 
                                            <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/linkedin-icon.png';?>
                                            <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </a><?php
                                    }
                                    if( ! empty( $args->organizer->em_social_links['twitter'] ) ){ ?>
                                        <a href="<?php echo esc_url( $args->organizer->em_social_links['twitter'] );?>" target="_blank" title="<?php echo esc_attr( 'Twitter' );?>" class="ep-twitter">
                                            <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/twitter-icon.png';?>
                                            <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </a><?php
                                    }
                                    if ( ! empty( $args->organizer->em_social_links['youtube'] ) ) {?>
                                        <a href="<?php echo esc_url( $args->organizer->em_social_links['youtube'] ); ?>" target="_blank" title="<?php echo esc_attr('Youtube'); ?>" class="ep-youtube">
                                            <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/youtube-icon.png';?>
                                            <img src="<?php echo esc_url( $image_url );?>" width="30" />
                                        </a><?php 
                                    }?>
                                </div><?php
                            } ?>  

                            <div class="ep-single-box-summery ep-single-box-desc">
                                <?php if ( isset( $args->organizer->description ) && $args->organizer->description !== '' ) {
                                    echo wp_kses_post(wpautop( $args->organizer->description ) );
                                } else{
                                    esc_html_e( 'No description available', 'eventprime-event-calendar-management' );
                                }?>
                            </div>

                            <?php do_action( 'ep_organizer_view_after_detail' );?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <?php do_action('ep_after_organizers_contant'); ?>
        <?php
        if ($args->event_args['show_events'] == 1) {
            ?>
        <div class="ep-box-col-12 event-<?php echo esc_attr($args->event_args['event_style']);?>-view">
    <div class="ep-row-heading ep-text-center ep-my-4">
        <div class="ep-upcoming-title ep-fw-bold ep-fs-5 ep-mt-5 ep-d-flex ep-justify-content-center ">
            <?php 
            $single_organizer_event_section_title =  !empty( $ep_functions->ep_get_global_settings( 'single_organizer_event_section_title' ) ) ? $ep_functions->ep_get_global_settings( 'single_organizer_event_section_title' ) : esc_html__("Upcoming Events", "eventprime-event-calendar-management"); 
            echo wp_kses_post( $single_organizer_event_section_title ); 
            ?>
        <span class="em_events_count-wrap em_bg"></span>
        </div>
    </div>
    <div id="ep-upcoming-events" class="em_content_area ep-upcoming-events">
        <div class="event-details-upcoming-<?php echo esc_attr($args->event_args['event_style']);?>-view">
            <?php if( isset( $args->events->posts ) && ! empty( $args->events->posts ) && count( $args->events->posts ) > 0 ) {?>
                <div class="ep-box-row" id="ep-organizer-upcoming-events"><?php
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
                            $upcoming_default_file = $ep_requests->eventprime_get_ep_theme('upcoming_events/mini-list');
                            include $upcoming_default_file;
                            break;

                    }?>
                </div><?php
            } else{?>
                <div class="ep-alert ep-alert-warning ep-mt-3 ep-py-2">
                    <?php esc_html_e( 'No upcoming event found.', 'eventprime-event-calendar-management' ); ?>
                </div><?php
            }?>
            <?php
            if( $args->events->max_num_pages > 1 && isset( $args->event_args['load_more'] ) && $args->event_args['load_more'] == 1 ) {?>
                <div class="ep-organizer-upcoming-event-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center">
                    <button 
                        data-max="<?php echo esc_attr($args->events->max_num_pages);?>" 
                        id="ep-loadmore-upcoming-event-organizer" 
                        class="ep-btn ep-btn-outline-primary"
                        data-id="<?php echo esc_attr( $args->organizer_id );?>"
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