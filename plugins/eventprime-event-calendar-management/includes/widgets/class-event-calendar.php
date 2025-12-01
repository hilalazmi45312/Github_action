<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('EventM_calendar')) {

    class EventM_calendar extends WP_Widget {

        function __construct() {
            parent::__construct( 'eventm_calendar', esc_html__( "EventPrime Calendar", 'eventprime-event-calendar-management' ), array('description' => esc_html__( "Event Calendar to show all the events", 'eventprime-event-calendar-management' ) ) );
        }

        public function widget($args, $instance) {
            $basic_functions = new Eventprime_Basic_Functions;
            $title = apply_filters('widget_title', $instance['title']);
            echo wp_kses_post($args['before_widget']);
            if ( ! empty( $title ) )
                echo wp_kses_post($args['before_title'] . $title . $args['after_title']);
                
            wp_enqueue_style(
                'ep-widgets-style',
                plugin_dir_url(dirname(__DIR__)) . '/admin/css/ep-widgets-style.css',
                false, ''
            );
            wp_enqueue_script(
                'ep-widgets-scripts',
                plugin_dir_url(dirname(__DIR__)) . '/admin/js/ep-widgets-public.js',
                false, ''
            );
            $data_array = array(
                'ajax_url'=> admin_url('admin-ajax.php'),
                'event_page_url' => $basic_functions->ep_get_custom_page_url( 'events_page')
            );
            wp_localize_script( 'ep-widgets-scripts', 'widgets_obj', $data_array );
            wp_enqueue_script('jquery-ui-datepicker', array('jquery'));
            $events_page_id = $basic_functions->ep_get_global_settings("events_page");
            wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css', array(), EVENTPRIME_VERSION);
            ?>
            <div class="emagic">
                <div class="ep_widget_container">
                    <div id="ep_calendar_widget"></div>
                    <form name="em_calendar_event_form" method="get" action="<?php echo esc_url( get_permalink( $events_page_id ) ); ?>">
                        <input type="hidden" name="ep-search" value="1" />
                        <input type="hidden" name="date" id="em_start_date" value="" />
                        <?php if( $basic_functions->ep_get_global_settings( 'shortcode_hide_upcoming_events' ) == 0 ) {?>
                            <div class="ep_upcoming_events">
                                <div class="ep_calendar_widget-events-title ep-py-2 ep-fs-5 ep-mt-2"><?php esc_html_e( 'Upcoming Events', 'eventprime-event-calendar-management' ) ?></div>
                                <?php
                                $start_date = new DateTime( ' ' . gmdate('Y-m-d') );
                                $start_date->setTime( 0,0,0,0 );
                                $event_controller = new Eventprime_Basic_Functions();
                                $query = array(
                                    'meta_query'  => array( 
                                        'relation' => 'AND',
                                        array(
                                            array(
                                                'key'     => 'em_start_date',
                                                'value'   =>  $start_date->getTimestamp(),
                                                'compare' => '>',
                                                'type'=>'NUMERIC'
                                            )
                                        )
                                    )
                                );
                                $events = $event_controller->get_events_post_data($query);
                                $today = $basic_functions->ep_date_to_timestamp(gmdate('Y-m-d'));
                                
                                if ( ! empty( $events ) ) {
                                    for ($i = 0; $i < min(5, count($events->posts)); $i++) {
                                        $event= $events->posts[$i]; 
                                        $event_end_date = $basic_functions->ep_timestamp_to_date( $event->em_end_date );
                                        if( ! empty( $event->em_end_time ) ) {
                                            $event_end_date .= ' ' . $event->em_end_time;
                                        }
                                        $event_end_date_timestamp = $basic_functions->ep_datetime_to_timestamp( $event_end_date );?>
                                        <div class="ep-upcoming-event ep-box-w-100"><a href="<?php echo esc_url( $event->event_url ); ?>">
                                        <?php echo esc_attr( $event->name ); ?></a>
                                            <?php if ($today >= $event->em_start_date && $today <= $event_end_date_timestamp): ?>
                                                <span class="ep-live-event"><?php esc_html_e( 'Live', 'eventprime-event-calendar-management' ); ?></span>
                                            <?php endif; ?>
                                        </div><?php
                                    }
                                }?>
                            </div><?php
                        }?>
                    </form>
                </div>
            </div>
            <?php
            echo wp_kses_post($args['after_widget']);
        }

        /**
         * 
         * Widget Backend
         */
        public function form($instance) {
            if (isset($instance['title'])) {
                $title = $instance['title'];
            } else {
                $title = esc_html__( 'New Title', 'eventprime-event-calendar-management' );
            }?>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>"><?php esc_html_e( 'Title:', 'eventprime-event-calendar-management' ); ?></label> 
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p><?php
        }

        // Updating widget replacing old instances with new
        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
            return $instance;
        }
    }
}



