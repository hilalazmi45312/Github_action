<?php
/**
 * View: Slider View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/eventprime/events/list/views/slider.php
 *
 */
$settings          = new Eventprime_Global_Settings;
$ep_functions = new Eventprime_Basic_Functions;
//print_r($args->events);
?>
<div class="ep-event-res-slider ep-box-col-12 ep-p-0 ep-m-0">

    <?php 
if( isset( $args->events->posts ) && !empty( $args->events->posts ) ) {
    foreach ($args->events->posts as $event){ 
        $is_event_expired = $ep_functions->check_event_has_expired($event);
        $options = array();
        $options['global'] = $settings->ep_get_settings();
        $global_options = $options['global'];
        $em_custom_link   = get_post_meta( $event->id, 'em_custom_link', true );
        if ($global_options->redirect_third_party == 1 && $event->em_enable_booking == 'external_bookings') {
            $url = esc_url($em_custom_link);
        } else {
            $url = esc_url($event->event_url);
        }
        $new_window = ( ! empty( $ep_functions->ep_get_global_settings( 'open_detail_page_in_new_tab' ) ) ? 'target="_blank"' : '' );?>
        <div class="ep-slider-items ep-d-flex">
            <?php $event_date = $ep_functions->ep_timestamp_to_date($event->em_start_date);?>
            <div class="ep-bg-white ep-slide-item ep-slide-item-left <?php if ($is_event_expired) echo 'ep-event-expired' ?> ep-d-flex ep-flex-1 ep-flex-column">
                <?php do_action( 'ep_event_view_before_event_title', $event );?>

                <div class="ep-box-title ep-box-card-title ep-text-truncate">
                    <a href="<?php echo esc_url($url); ?>" <?php echo esc_attr( $new_window );?> class="ep-fw-bold ep-fs-3 ep-mt-3 ep-text-dark">
                        <?php echo esc_html( $event->em_name ); ?>
                    </a>
                </div><?php
                $venue = $ep_functions->ep_get_filter_taxonomy_id($event->em_venue);
                
                $event_venue_details =(isset($venue) && !empty($venue))?(array)$ep_functions->get_single_venue( $venue ):'&nbsp;';
                
                if( ! empty( $event_venue_details['name'] ) ) {?>
                    <div class="ep-box-card-venue ep-card-venue ep-text-muted ep-text-truncate"><?php
                        echo esc_html( $event_venue_details['name'] );?>
                    </div><?php
                }
                
                // event dates
                if( ! empty( $event->em_start_date ) ) {?>
                    <div class="ep-event-details ep-d-flex ep-justify-content-between ep-mb-2">
                        <div class="ep-card-event-date ep-d-flex ep-text-muted ">
                            <div class="ep-card-event-date-wrap ep-d-flex ep-fw-bold">
                                <?php do_action( 'ep_event_view_event_dates', $event, 'card' );?>
                            </div>
                        </div>
                    </div><?php
                }?>

                <?php do_action( 'ep_event_view_before_event_description', $event ); ?> 
                
                <!-- Event Description -->
                <div class="ep-box-card-desc ep-text-small ep-mb-auto">
                    <?php if ( ! empty( $event->description ) ) {
                        echo wp_kses_post( $event->description );
                    }?>
                </div>

                <!-- Hook after event description -->
                <?php do_action( 'ep_event_view_after_event_description', $event );?>

                <!-- Event Price -->
                <?php do_action( 'ep_event_view_event_price', $event );?>
                
                <!-- Booking Status -->
                <div class="ep-slider-view-booking-count">
                <?php do_action( 'ep_events_booking_count_slider', $event );?>
                </div>

                <?php do_action( 'ep_event_view_before_event_button', $event );?>

                <div class="ep-text-center ep-slide-button-area">
                    <?php do_action( 'ep_event_view_event_booking_button', $event );?>
                </div>

                <?php do_action( 'ep_event_view_after_event_button', $event );?>
            </div>
            <div class="ep-slide-item ep-slide-item-right">
                <div class="ep-list-icon-group ep-slider-icon-group ep-position-absolute ep-bg-white ep-rounded ep-d-inline-flex ep-mt-3 ep-ml-3">
                    <!--wishlist-->
                    <?php do_action( 'ep_event_view_wishlist_icon', $event, 'event_list' );?>
                    <!--social sharing-->
                    <?php do_action( 'ep_event_view_social_sharing_icon', $event, 'event_list' );?>
                    
                    <?php do_action( 'ep_event_view_event_icons', $event );?>
                </div>
                <?php if ( ! empty( $event->_thumbnail_id ) ) { ?>
                    <a href="<?php echo esc_url($url); ?>" <?php echo esc_attr( $new_window );?> class="ep-img-link">
                        <?php
                        if(is_admin() && defined('ELEMENTOR_VERSION'))
                        {
                            $image = $ep_functions->get_event_image_url($event->id);
                            if($image=='')
                            {
                                $image = plugin_dir_url(EP_PLUGIN_FILE). 'admin/images/dummy_image.png';
                            }
                            ?>
                            <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr( $event->em_name ); ?>" class="img-fluid ep-rounded-1 ep-box-w-100">
                        <?php
                        }
                        else
                        {
                            $thumb_id = get_post_thumbnail_id( $event->id );

                            if( ! empty( $thumb_id ) ) {
                                echo wp_get_attachment_image( $thumb_id, 'large', false, array(
                                    'class' => 'img-fluid ep-rounded-1 ep-box-w-100',
                                    'alt'   => esc_attr( $event->em_name ),
                                ) );
                            } else {
                                // Fallback image
                                echo '<img src="' . esc_url( plugin_dir_url(EP_PLUGIN_FILE) . 'admin/images/dummy_image.png' ) . '" alt="'. esc_attr__('Dummy Image', 'eventprime-event-calendar-management') .'" class="img-fluid ep-rounded-1 ep-box-w-100">';
                            }
                            //echo get_the_post_thumbnail( $event->id, 'large', array('class' => 'img-fluid ep-rounded-1 ep-box-w-100') );
                        }
                        
                        ?>
                    </a><?php
                    } else { ?>
                    <a href="<?php echo esc_url($url); ?>" <?php echo esc_attr( $new_window );?> class="ep-img-link ep-image-default">
                        <img src="<?php echo esc_url( plugin_dir_url(EP_PLUGIN_FILE). 'admin/images/dummy_image.png' ); ?>" alt="<?php echo esc_html( $event->em_name ); ?>" class="em-no-image">
                    </a><?php
                } ?>
            </div>
        </div><?php 
    } 
}
    ?>
    
  
</div>
<div class="ep-event-slider-nav"></div>
<script>
    jQuery(function() {
        jQuery('.ep-event-res-slider').responsiveSlides({
            auto: true, 
            speed: 500, 
            timeout: 4000, 
            pager: false, 
            nav: true, 
            random: false, 
            pause: true, 
            pauseControls: true, 
            prevText: "<span class='material-icons-outlined'> arrow_back_ios </span>", 
            nextText: "<span class='material-icons-outlined'> arrow_forward_ios</span>", 
            maxwidth: "", 
            navContainer: ".ep-event-slider-nav", 
            manualControls: "mundi",
            namespace: "ep-event-rslides"
        });
    });
</script> 