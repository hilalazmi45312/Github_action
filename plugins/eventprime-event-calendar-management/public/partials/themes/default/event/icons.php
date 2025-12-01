<?php
$ep_functions = new Eventprime_Basic_Functions;
?>
<div class="ep-box-row ep-event-type-cal-section">
    <div class="ep-box-col-8 ep-sm-py-3 ep-md-py-4 ep-lg-py-5">
        <div class="ep-event-category ep-box-row">
            <?php if( ! empty( $args->event->event_type_details ) ) {
                $styles = '';
                $styles .= ( ! empty( $args->event->event_type_details->em_color ) ? 'background-color:' . $args->event->event_type_details->em_color . ';' : '');
                $styles .= ( ! empty( $args->event->event_type_details->em_type_text_color ) ? 'color:' . $args->event->event_type_details->em_type_text_color . ';' : '');?>
                <div class="ep-box-col-12">
                    <span class="ep-bg-warning ep-py-2 ep-px-3 ep-mr-3" id="ep_single_event_event_type" style="<?php echo esc_attr( $styles );?>">
                        <?php
                        if( ! empty( $args->event->event_type_details ) ) {
                            echo esc_html( $args->event->event_type_details->name );
                        }?>
                    </span>
                </div><?php
            }?>
        </div>
    </div>
    <div class="ep-box-col-4 ep-sm-py-3 ep-md-py-4 ep-lg-py-5 ep-align-right ep-di-flex ep-justify-content-end">
        <!-- Social Information -->
        <?php if ( ! empty( $args->event->em_social_links ) ) { ?>
            <?php if ( ! empty( $args->event->em_social_links['facebook'] ) ) { ?>
                <a href="<?php echo esc_url( $args->event->em_social_links['facebook'] ); ?>" target="_blank" title="<?php echo esc_attr('Facebook'); ?>" class="ep-facebook-f ep-px-2">
                    <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/facebook-icon.png'; ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" width="24" />
                </a><?php
            }
            if ( ! empty( $args->event->em_social_links['instagram'] ) ) { ?>
                <a href="<?php echo esc_url( $args->event->em_social_links['instagram'] ); ?>" target="_blank" title="<?php echo esc_attr('Instagram'); ?>" class="ep-instagram ep-px-2">
                    <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE )  . 'public/partials/images/instagram-icon.png'; ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" width="24" />
                </a><?php
            }
            if ( ! empty( $args->event->em_social_links['linkedin'] ) ) { ?>
                <a href="<?php echo esc_url( $args->event->em_social_links['linkedin'] ); ?>" target="_blank" title="<?php echo esc_attr('Linkedin'); ?>" class="ep-linkedin ep-px-2">
                    <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/linkedin-icon.png'; ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" width="24" />
                </a><?php
            }
            if ( ! empty( $args->event->em_social_links['twitter'] ) ) { ?>
                <a href="<?php echo esc_url( $args->event->em_social_links['twitter'] ); ?>" target="_blank" title="<?php echo esc_attr('Twitter'); ?>" class="ep-twitter ep-px-2">
                    <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/twitter-icon.png'; ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" width="24" />
                </a><?php
            }
            if ( ! empty( $args->event->em_social_links['youtube'] ) ) { ?>
                <a href="<?php echo esc_url( $args->event->em_social_links['youtube'] ); ?>" target="_blank" title="<?php echo esc_attr('Youtube'); ?>" class="ep-youtube ep-px-2">
                    <?php $image_url = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/youtube-icon.png'; ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" width="24" />
                </a><?php
            }
        } ?>
        <!-- <span class="material-icons-outlined ep-mr-3">notifications_active</span> -->
        <?php //wishlist
        do_action( 'ep_event_view_wishlist_icon', $args->event, 'event_detail' );
        
        //calendar icon
        //do_action( 'ep_event_view_calendar_icon', $args->event, 'event_detail' );?>
        <?php if(!empty( $ep_functions->ep_get_global_settings( 'show_print_icon' ) )): ?>
        <div class="ep-event-action ep_event_print_action ep-px-2 ep-d-flex ep-align-items-center ep-bg-white ep-rounded-tbl-right">
                    <span class="material-icons-outlined ep-handle-fav ep-cursor ep-button-text-color ep-mr-3" onclick="window.print();">print</span>
                </div>
        <?php endif; ?>
        <div class="ep-sl-event-action ep-cursor ep-position-relative ep-event-ical-action ep-d-flex ep-align-items-center">
                <span class="material-icons-outlined ep-exp-dropbtn ep-mr-3 ep-cursor"><svg class="ep-btn-text-fill-color" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="#000000"><g><rect fill="none" height="24" width="24"/></g><g><path d="M19,4h-1V2h-2v2H8V2H6v2H5C3.89,4,3.01,4.9,3.01,6L3,20c0,1.1,0.89,2,2,2h14c1.1,0,2-0.9,2-2V6C21,4.9,20.1,4,19,4z M19,20 H5V10h14V20z M19,8H5V6h14V8z M9,14H7v-2h2V14z M13,14h-2v-2h2V14z M17,14h-2v-2h2V14z M9,18H7v-2h2V18z M13,18h-2v-2h2V18z M17,18 h-2v-2h2V18z"/></g></svg></span>
                <ul class="ep-calendar-exp-dropdown-content ep-event-share ep-m-0 ep-p-0">
                    
                    <li class="ep-event-social-icon">  <a href="javascript:void();"  id="ep_event_ical_export" title="<?php esc_html_e( '+ iCal Export','eventprime-event-calendar-management' ); ?>" data-event_id="<?php echo esc_attr( $args->event->id );?>"><?php esc_html_e( 'iCal Export','eventprime-event-calendar-management' ); ?></a></li>
                    <?php
                    // add to google calendar 
                    $gcal_starts = $gcal_ends = $gcal_details = $location = $calendar_url = '';
                    $gcal_starts = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'start' );
                    if( ! empty( $gcal_starts ) ) {
                        $gcal_ends = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'end' );
                    }
                    
                        
                    $gcal_details = urlencode( wp_kses_post( $args->event->description ) );
                    $calendar_url = ($gcal_starts) ? 'https://www.google.com/calendar/event?action=TEMPLATE&text=' . urlencode( esc_attr( $args->event->name ) ) . '&dates=' . gmdate( 'Ymd\\THi00\\Z', esc_attr( $gcal_starts ) ) . '/' . gmdate('Ymd\\THi00\\Z', esc_attr( $gcal_ends ) ) . '&details=' . esc_attr( $gcal_details ):'';
                    if ( ! empty( $args->event->venue_details ) ) {
                        //print_r($args->event->venue_details);die;
                        $location = urlencode( $args->event->venue_details->em_address );
                        if( ! empty( $location ) ) {
                            $calendar_url .= '&location=' . esc_attr( $location );
                        }
                    }
            
                    if( ! empty( $gcal_starts ) && ! empty( $gcal_ends ) ) {?>
                    <li class="ep-event-social-icon"> <a href="<?php echo esc_url( $calendar_url );?>" target="_blank" title="<?php esc_html_e( 'Google Calendar','eventprime-event-calendar-management' ); ?>"><?php esc_html_e( 'Google Calendar','eventprime-event-calendar-management' ); ?></a></li>
                    <?php }

                    // add to Outlook 365  
                    $outlook_starts = $outlook_ends = $outlook_details = $location = $outlook_url = '';
                    $outlook_starts = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'start' );
                    if( ! empty( $outlook_starts ) ) {
                        $outlook_ends = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'end' );
                    }
                    //$outlook_details = urlencode( wp_kses_post( $args->event->description ) );
                    $outlook_details = '';
                    $outlook_url = ($outlook_starts) ? 'https://outlook.office365.com/owa/?path=/calendar/action/compose&subject=' . rawurlencode( esc_attr( $args->event->name ) ) . '&startdt='.date( 'Y-m-d\TH:i:s', $outlook_starts ).'&enddt='.date( 'Y-m-d\TH:i:s', $outlook_ends ):'';
                    if ( ! empty( $args->event->venue_details ) ) {
                        $location = urlencode( $args->event->venue_details->em_address );
                        if( ! empty( $location ) ) {
                            $outlook_url .= '&location=' . esc_attr( $location );
                        }
                    }
                    if ( ! empty( $outlook_details ) ) {
                        $outlook_url .= '&body=' . $outlook_details;
                    }
                    
                    if( ! empty( $outlook_starts ) && ! empty( $outlook_ends ) ) {?>
                        <li class="ep-event-social-icon">  <a href="<?php echo esc_url( $outlook_url );?>" target="_blank" title="<?php esc_html_e( 'Outlook 365','eventprime-event-calendar-management' ); ?>"><?php esc_html_e( 'Outlook 365','eventprime-event-calendar-management' ); ?></a></li>
                    <?php  }

                    // add to Outlook Live 
                    $outlook_lv_starts = $outlook_lv_ends = $outlook_lv_details = $location = $outlook_lv_url = '';
                    $outlook_lv_starts = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'start' );
                    if( ! empty( $outlook_lv_starts ) ) {
                        $outlook_lv_ends = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event, 'end' );
                    }
                    //$outlook_lv_details = urlencode( wp_kses_post( $args->event->description ) );
                    $outlook_lv_details = '';
                    $outlook_lv_url = ($outlook_lv_starts) ? "https://outlook.live.com/owa/?path=/calendar/action/compose&startdt=".date( 'Ymd\THis\Z', $outlook_lv_starts )."&enddt=".date( 'Ymd\THis\Z', $outlook_lv_ends )."&subject=".rawurlencode( esc_attr( $args->event->name ) ): '';
                    if ( ! empty( $args->event->venue_details ) ) {
                        $location = urlencode( $args->event->venue_details->em_address );
                        if( ! empty( $location ) ) {
                            $outlook_lv_url .= '&location=' . esc_attr( $location );
                        }
                    }
                    if ( ! empty( $outlook_lv_details ) ) {
                        $outlook_lv_url .= '&body=' . $outlook_lv_details;
                    }
                    
                    if( ! empty( $outlook_lv_starts ) && ! empty( $outlook_lv_ends ) ) {?>
                        <li class="ep-event-social-icon">  <a href="<?php echo esc_url( $outlook_lv_url );?>" target="_blank" title="<?php esc_html_e( 'Outlook Live','eventprime-event-calendar-management' ); ?>"><?php esc_html_e( 'Outlook Live','eventprime-event-calendar-management' ); ?></a></li>
                    <?php } ?>
                        
                        
                </ul>

        </div>
        <?php
        
        //social sharing
        do_action( 'ep_event_view_social_sharing_icon', $args->event, 'event_detail' );?>
        
        <?php do_action( 'ep_single_event_load_icons', $args );?>
    </div>   
</div>