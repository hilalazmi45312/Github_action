<?php 
$ep_functions = new Eventprime_Basic_Functions;
if( ! empty( $args->event->em_venue ) ) {
    $event_venue = $ep_functions->get_single_venue( $args->event->em_venue );
    if( ! empty( $event_venue ) ) {
        $expand_venue_container = $ep_functions->ep_get_global_settings('expand_venue_container');
        
        $other_events = $ep_functions->get_upcoming_event_by_venue_id( $args->event->em_venue, array( $args->event->id ) );?>
        <div class="ep-box-col-12 mt-3" id="ep_sl_event_venue_detail">
            <div class="ep-sl-event-venue-box ep-overflow-hidden ep-py-2">
                <div id="ep-sl-venue-map">
                    <span class="ep-text-small ep-text-muted ep-d-flex ep-align-items-center" id="ep-sl-venue-details">
                        <span class="material-icons-outlined ep-mr-1 ep-d-flex" style="vertical-align:middle;"><svg class="ep-btn-text-fill-color" xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000"><path d="M0 0h24v24H0z" fill="none"/><path d="M12 12c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6-1.8C18 6.57 15.35 4 12 4s-6 2.57-6 6.2c0 2.34 1.95 5.44 6 9.14 4.05-3.7 6-6.8 6-9.14zM12 2c4.2 0 8 3.22 8 8.2 0 3.32-2.67 7.25-8 11.8-5.33-4.55-8-8.48-8-11.8C4 5.22 7.8 2 12 2z"/></svg></span>
                        <span class="ep-text-dark ep-fw-bold ep-mr-1">
                            <?php echo esc_html( $event_venue->name );?>
                        </span>
                        <?php if( ! empty( $event_venue->em_address ) && ! empty( $event_venue->em_display_address_on_frontend ) ) {?>
                            <span id="ep_single_event_venue_address">
                                <?php echo esc_html( $event_venue->em_address );?>
                            </span><?php
                        }?>
                            <span id="ep_sl_venue_more" class="material-icons-outlined ep-cursor ep-bg-secondary ep-bg-opacity-10 ep-ml-2 ep-rounded-circle <?php echo ($expand_venue_container==0)?'':'ep-arrow-active';?>">
                            
                             <span class="ep_expand_more_icon ep-d-flex"><svg class="ep-btn-text-fill-color" xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 0 24 24" width="20px" fill="#5f6368"><path d="M24 24H0V0h24v24z" fill="none" opacity=".87"/><path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z"/></svg></span>
                             <span class="ep_expand_less_icon ep-d-flex" style="display: none;"><svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 0 24 24" width="20px" fill="#5f6368"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M12 8l-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14l-6-6z"/></svg></span>
                        </span>
                    </span>
                </div>
                <div id="venue_hidden_details" class="ep-box-row ep-mb-3 ep-mt-3 ep-pt-3 ep-border-top" style="<?php echo ($expand_venue_container==0)?'display:none':'display:block';?>">
                    <div class="ep-box-col-12">
                        <ul class="ep-nav-pills ep-mx-0 ep-p-0 ep-mb-3 ep-venue-details-tabs" role="tablist">
                            <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-sl-venue" class="ep-tab-link ep-tab-active"><?php esc_html_e( 'Details', 'eventprime-event-calendar-management' );?></a></li>
                            <?php if( ! empty( $ep_functions->ep_get_global_settings( 'gmap_api_key' ) ) && empty( $ep_functions->ep_get_global_settings( 'hide_map_tab' ) ) ) {?>
                                <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-sl-address-map" class="ep-tab-link"><?php esc_html_e( 'Map', 'eventprime-event-calendar-management' );?></a></li><?php
                            }?>
                            <?php if(empty($ep_functions->ep_get_global_settings('hide_weather_tab'))){?>
                                <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-sl-venue-weather" class="ep-tab-link"><?php esc_html_e( 'Weather', 'eventprime-event-calendar-management' );?></a></li><?php 
                            }
                            $other_display = 'style=display:none;';
                            if( count( $other_events ) > 0 ) {
                                $other_display = '';
                            }
                            if(empty($ep_functions->ep_get_global_settings('hide_other_event_tab'))){?>
                                <li class="ep-tab-item ep-mx-0" role="presentation" id="ep_event_venue_other_event_tab" <?php echo esc_attr( $other_display );?>>
                                    <a href="javascript:void(0)" data-tag="ep-sl-other-events" class="ep-tab-link">
                                        <?php esc_html_e( 'Other Events', 'eventprime-event-calendar-management' );?>
                                    </a>
                                </li><?php 
                            } ?>
                        </ul>    

                        <div id="ep-tab-container" class="ep-box-w-100">
                            <div class="ep-tab-content ep-sl-venue" id="ep-sl-venue"  role="tabpanel" >                                        
                                <div class="ep-box-row">
                                    <div class="ep-box-col-4 ep-venue-img-section">
                                        <?php 
                                        $image = plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/dummy_image.png';
                                        if ( isset( $event_venue->em_gallery_images[0] ) && !empty( $event_venue->em_gallery_images[0] ) ) {
                                            $image = wp_get_attachment_url( $event_venue->em_gallery_images[0] );
                                        }?>
                                        <div class="ep-venue-card-thumb ep-rounded-1">
                                        <img src="<?php echo esc_url( $image );?>" alt="<?php echo esc_attr( $args->event->name ); ?>" class="ep-rounded-1 primary" style="max-width:100%;" id="ep_event_venue_main_image" />
                                        </div>
                                    </div>
                                    <div class="ep-box-col-8 ep-venue-details-section">
                                        <?php if( ! empty( $args->event->em_venue ) ) {?>
                                            <div class="ep-fs-6 ep-fw-bold">
                                                <?php 
                                                echo esc_html( $event_venue->name );
                                                $venue_url = $event_venue->venue_url;
                                                ?>
                                                <a href="<?php echo esc_url( $venue_url );?>" target="_blank" id="ep_event_venue_url">
                                                    <span class="material-icons-outlined ep-fs-6 ep-text-primary ep-align-text-bottom ep-align-middle ep-lh-0"><svg xmlns="http://www.w3.org/2000/svg" height="18px" viewBox="0 0 24 24" width="18px" fill="#000000"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/></svg></span>
                                                </a>
                                            </div>
                                            <?php if( ! empty( $event_venue->em_address ) && ! empty( $event_venue->em_address->em_display_address_on_frontend ) ) {?>
                                                <p class="ep-text-muted ep-text-small" id="ep_event_venue_address">
                                                    <?php echo esc_html( $event_venue->em_address );?>
                                                </p><?php
                                            }
                                        }?>
                                        <p class="ep-text-small ep-content-truncate" id="ep_event_venue_description">
                                            <?php echo wp_kses_post( $event_venue->description );?>
                                        </p>
                                    </div>
                                    <!-- Gallery Images -->
                                    <?php $em_venue_gallery = ( ! empty( $event_venue->em_gallery_images ) ? $event_venue->em_gallery_images : array() );
                                    if( ! empty( $em_venue_gallery ) && count( $em_venue_gallery ) > 1 ) {?>
                                        <div class="owl-carousel owl-theme selected ep-box-col-12 thumbnails ep-box-col-12 ep-mt-2 ep-d-flex justify-content-start ep-image-slider"><?php
                                            foreach( $em_venue_gallery as $gal_id ) {
                                                $attachment_image_url = wp_get_attachment_url( $gal_id );
                                                if( $attachment_image_url ) {?>
                                                    <div class="thumbnail ep-d-inline-flex ep-mr-2" data-image_url="<?php echo esc_url( $attachment_image_url );?>">
                                                        <img class="ep-rounded-1" src="<?php echo esc_url( $attachment_image_url );?>" style="max-width:140px;">
                                                    </div><?php
                                                }
                                            }?>
                                        </div><?php
                                    }?>
                                </div>
                            </div>
                            <?php if( ! empty( $ep_functions->ep_get_global_settings( 'gmap_api_key' ) ) && empty( $ep_functions->ep_get_global_settings( 'hide_map_tab' ) ) ) {?>
                                <div class="ep-tab-content ep-item-hide" id="ep-sl-address-map" role="tabpanel">
                                    <?php if( ! empty( $event_venue->em_address ) ) {?>
                                        <div id="ep-event-venue-map" data-venue_address="<?php echo esc_attr( $event_venue->em_address );?>" data-venue_lat="<?php echo esc_attr( $event_venue->em_lat );?>" data-venue_lng="<?php echo esc_attr( $event_venue->em_lng );?>" data-venue_zoom_level="<?php echo esc_attr( $event_venue->em_zoom_level );?>" style="height: 400px;"></div><?php
                                    } else{
                                        esc_html_e( 'No address found', 'eventprime-event-calendar-management' );
                                    }?>
                                </div><?php
                            }?>
                            <!-- Weather -->
                            <?php if( empty( $ep_functions->ep_get_global_settings( 'hide_weather_tab' ) ) ) {?>
                                <div class="ep-tab-content ep-item-hide" id="ep-sl-venue-weather" role="tabpanel">
                                    <?php do_action( 'ep_event_detail_weather_data', $event_venue );?>
                                </div>
                            <?php }?>
                            <!-- Other Events -->
                            <?php if(empty($ep_functions->ep_get_global_settings('hide_other_event_tab'))){?>
                            <div class="ep-tab-content ep-item-hide" id="ep-sl-other-events" role="tabpanel">
                                <?php if( count( $other_events ) > 0 ) {
                                    $new_window = ( ! empty( $ep_functions->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );
                                    foreach( $other_events as $event ) {?>
                                        <div class="ep-box-row ep-align-items-center ep-mb-4 ep-pb-2 ep-border-bottom">
                                            <?php $image_url = ($event->image_url)?$event->image_url:$event->placeholder_image_url; ?>
                                            <div class="ep-box-col-2">
                                                <a href="<?php echo esc_url($image_url);?>" <?php echo esc_attr( $new_window );?> class="ep-sl-other-event-link">
                                                    <img class="ep-rounded-circle ep-sl-other-event-img" src="<?php echo esc_url( $image_url );?>" width="60px" height="60px">
                                                </a>
                                            </div>
                                            
                                            <div class="ep-box-col-7">
                                                <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                    <div class="ep-fw-bold ep-text-small"><?php echo esc_html( $event->name );?></div>
                                                </a>
                                                <div class="ep-text-small ep-text-muted ep-desc-truncate"><?php echo wp_kses_post( wp_trim_words($event->description,35 ));?></div>
                                            </div>
                                            <div class="ep-box-col-3">
                                                <?php 
                                                $view_details_text = $ep_functions->ep_global_settings_button_title('View Details');
                                                if( $ep_functions->check_event_has_expired( $event ) ) {?>
                                                    <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                        <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" value="<?php echo esc_html( $view_details_text ); ?>">
                                                    </a><?php
                                                } else{
                                                    if( ! empty( $event->em_enable_booking ) ) {
                                                        if( $event->em_enable_booking == 'bookings_off' ) {?>
                                                            <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                                <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" value="<?php echo esc_html( $view_details_text ); ?>">
                                                            </a><?php
                                                        } elseif( $event->em_enable_booking == 'external_bookings' ) {
                                                            if( empty( $event->em_custom_link_new_browser ) ) {
                                                                $new_window = '';
                                                            }?>
                                                            <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                                <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" value="<?php echo esc_html( $view_details_text ); ?>">
                                                            </a><?php
                                                        } else{
                                                            // check for booking status 
						                                    if( ! empty( $event->all_tickets_data ) ) {
                                                                $check_for_booking_status = $ep_functions->check_for_booking_status( $event->all_tickets_data, $event );
                                                                if( ! empty( $check_for_booking_status ) ) {
                                                                    if( $check_for_booking_status['status'] == 'not_started' ) {?>
                                                                        <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                                            <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" value="<?php echo esc_html( $check_for_booking_status['message'] );?>">
                                                                        </a><?php
                                                                    } elseif( $check_for_booking_status['status'] == 'off' ) {?>
                                                                        <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                                            <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" value="<?php echo esc_html( $check_for_booking_status['message'] );?>">
                                                                        </a><?php
                                                                    } else{?>
                                                                        <a href="<?php echo esc_url( $event->event_url );?>" <?php echo esc_attr( $new_window );?>>
                                                                            <input type="button" class="ep-btn ep-btn-outline-primary ep-btn-sm" data-event_id="<?php echo esc_html( $event->id );?>" value="<?php echo  esc_html__( $check_for_booking_status['message'], 'eventprime-event-calendar-management' );?>">
                                                                        </a><?php
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }?>
                                            </div>
                                        </div><?php
                                    }
                                }?>
                            </div>
                            <?php } ?>
                        </div>   
                    </div>
                </div>
            </div>
        </div><?php
    }
}?>
