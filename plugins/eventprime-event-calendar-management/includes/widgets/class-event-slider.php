<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class EventM_slider extends WP_Widget {

    function __construct() {
        parent::__construct('eventm_slider', esc_html__("EventPrime - Event Slider", 'eventprime-event-calendar-management'), array('description' => esc_html__("Event Slider to show all the events", 'eventprime-event-calendar-management'))
        );
    }

    public function widget($args, $instance) {
        $basic_functions = new Eventprime_Basic_Functions;
        wp_enqueue_style( 'ep-responsive-slides-css' );
        wp_enqueue_script( 'ep-responsive-slides-js' );

        wp_enqueue_script(
        'ep-widgets-scripts',
        plugin_dir_url(dirname(__DIR__)) . '/admin/js/ep-widgets-public.js',
            false, ''
        );

        wp_enqueue_style(
        'ep-widgets-style',
            plugin_dir_url(dirname(__DIR__)) . '/admin/css/ep-widgets-style.css',
            false, ''
        );

        $query = array(
            'meta_query' => array( 
                'relation' => 'AND',
                array(
                    array(
                        'key'     => 'em_start_date',
                        'value'   =>  current_time( 'timestamp' ),
                        'compare' => '>',
                        'type'=>'NUMERIC'
                    )
                )
            )
        );
        $events = $basic_functions->get_events_post_data( $query ); 
        if( $events->posts ):?>
        <div class="emagic">
             <div id="ep_widget_container" class="ep-event-slide-container ep-position-relative">
                <ul class="ep_event_slides ep-event-slider-<?php echo esc_attr( $this->number ); ?> ep-m-0 ep-p-0">
                    <?php if( isset( $events->posts ) ): foreach ( $events->posts as $event ): ?>
                    <li class="ep-m-0 ep-p-0 ep-widget-event-slide">
                            <div class="ep-widget-slider-meta">
                                <?php
                                $event_date = $basic_functions->ep_timestamp_to_date( $event->em_start_date );
                                ?>
                                <div class="ep-widget-slider-title ep-text-truncate ep-fw-bold"><?php echo esc_attr( $event->name ); ?></div>
                                <div class="ep-widget-slider-date"><?php echo esc_attr( $event_date ); ?></div>
                            </div>
                                <a target="_blank" href="<?php echo esc_url( $event->event_url ); ?>"><img src="<?php echo esc_url( $event->image_url ); ?>"> </a>
                        </li>
                    <?php endforeach;endif;?>
                </ul>  
                <div class="ep-event-widget-slider-nav-<?php echo esc_attr( $this->number ); ?> ep-event-widget-slider-nav" ></div>
            </div>
         </div>
        <?php endif;?>

        <script>
            window.onload = function() { 
                jQuery('.ep-event-slider-<?php echo esc_attr( $this->number ); ?>').responsiveSlides({
                    auto: true, 
                    speed: 500, 
                    timeout: 4000, 
                    pager: false, 
                    nav: true, 
                    random: false, 
                    pause: true, 
                    prevText: "<span class='material-icons-outlined'> arrow_back_ios </span>", 
                    nextText: "<span class='material-icons-outlined'> arrow_forward_ios</span>",
                    maxwidth: "", 
                    pauseControls: true, 
                    navContainer: ".ep-event-widget-slider-nav-<?php echo esc_attr( $this->number ); ?>", 
                    manualControls: "",
                    namespace: "ep-widget-rslides"
                });
            }
        </script><?php
    }

}