<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('EventM_Event_Countdown')):

    class EventM_Event_Countdown extends WP_Widget {

        function __construct() {
            parent::__construct( 'EventM_Event_Countdown', esc_html__( 'Event Countdown', 'eventprime-event-calendar-management' ), array() );
        }

        public function widget($args, $instance) {
            $basic_functions = new Eventprime_Basic_Functions;
            wp_enqueue_style(
            'ep-widgets-style',
            plugin_dir_url(dirname(__DIR__)) . '/admin/css/ep-widgets-style.css',
                false, ''
            );
            $title = apply_filters('widget_title', $instance['title'], $instance['event_id']);
            echo wp_kses_post($args['before_widget']);
            $event_id = (int) $instance['event_id'];
            $event = $basic_functions->get_single_event($event_id);
            if ( ! empty( $event->id ) ):
                if ( ! empty( $title ) ){
                    echo wp_kses_post($args['before_title'] . $title . $args['after_title']);
                }?>
                <?php if ($event->em_start_date > current_time('timestamp')): ?>
                    <div class="event_title dbfl"><a href="<?php echo esc_url( $event->event_url ); ?>"><?php echo esc_html( $event->name ); ?></a></div> 
                    <?php 
                    $start_date = $basic_functions->ep_timestamp_to_date($event->em_start_date,'Y-m-d');
                    $start_time = isset( $event->em_start_time ) && ! empty( $event->em_start_time ) ? $event->em_start_time : '';
                    if( $start_time ) {
                        $start_date_time = $basic_functions->ep_datetime_to_timestamp( $start_date.' '.$start_time );
                    } else{
                        $start_date_time = $basic_functions->ep_date_to_timestamp( $start_date );
                    }
                    //$formate = isset($event->em_start_time) && !empty($event->em_start_time) ? 'Y-m-d h:i a' : 'Y-m-d h:i';
                    $formate = 'Y-m-d h:i';
                    $start_date = $basic_functions->ep_timestamp_to_datetime( $start_date_time, $formate, 1 );
                    wp_enqueue_script("em_countdown_jquery", plugin_dir_url(dirname(__DIR__)) . '/admin/js/jquery.countdown.min.js', false, '');
                    ?>
                    <div class="ep_widget_container">
                        <div class="ep_countdown_timer dbfl" id="ep_widget_event_countdown_<?php echo esc_attr( $this->number ); ?>">
                            <span class="days ep_color" id="ep_countdown_days_<?php echo esc_attr( $this->number ); ?>"></span>
                            <span class="hours ep_color" id="ep_countdown_hours_<?php echo esc_attr( $this->number ); ?>"></span>
                            <span class="minutes ep_color" id="ep_countdown_minutes_<?php echo esc_attr( $this->number ); ?>"></span>
                            <span class="seconds ep_color" id="ep_countdown_seconds_<?php echo esc_attr( $this->number ); ?>"></span>
                        </div>
                    </div>
                    <script type="text/javascript">
                        jQuery(document).ready(function () {
                            $ = jQuery;
                            var date = new Date("<?php echo esc_attr( $start_date ); ?>");
                            $('#ep_widget_event_countdown_<?php echo esc_attr( $this->number ); ?>').countdown(date, function (event) {
                                $("#ep_countdown_days_<?php echo esc_attr( $this->number ); ?>").html(event.strftime('%D'));
                                $("#ep_countdown_hours_<?php echo esc_attr( $this->number ); ?>").html(event.strftime('%H'));
                                $("#ep_countdown_minutes_<?php echo esc_attr( $this->number ); ?>").html(event.strftime('%M'));
                                $("#ep_countdown_seconds_<?php echo esc_attr( $this->number ); ?>").html(event.strftime('%S'));
                            });
                        });
                    </script>
                    
                    <?php
                endif;
            endif;
            echo wp_kses_post($args['after_widget']);
        }

        public function form($instance) {
            if (isset($instance['title'])) {
                $title = $instance['title'];
            } else {
                $title = esc_html__( 'New Title', 'eventprime-event-calendar-management' );
            }

            if ( isset( $instance['event_id'] ) ) {
                $event_id = $instance['event_id'];
            } else {
                $event_id = "";
            }
            ?>
            <p>
                <label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>"><?php esc_html_e( 'Title:', 'eventprime-event-calendar-management' ); ?></label> 
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            </p>

            <p>
                <label for="event">Event</label> <br>
                <?php
                $event_controller = new Eventprime_Basic_Functions();
                $events = $event_controller->get_events_post_data();
                ?>
                <select id="<?php echo esc_attr( $this->get_field_id('event_id') ); ?>" name="<?php echo esc_attr( $this->get_field_name('event_id') ); ?>">
                    <option><?php esc_html_e( 'Select Event', 'eventprime-event-calendar-management' ); ?></option>
                    <?php
                    if ( ! empty( $events )):
                        foreach ( $events->posts as $event ):
                            if ( $event->em_start_date <= current_time('timestamp') )
                                continue;
                            ?>
                            <option <?php if ($event_id == $event->id) echo 'selected'; ?> value="<?php echo esc_attr($event->id); ?>"><?php echo esc_attr( $event->name ); ?></option>    
                            <?php
                        endforeach;
                    endif;
                    ?>
                </select>
            </p>
            <?php
        }

        // Updating widget replacing old instances with new
        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
            $instance['event_id'] = ( ! empty( $new_instance['event_id'] ) ) ? strip_tags( $new_instance['event_id'] ) : '';
            return $instance;
        }

    }

    endif;