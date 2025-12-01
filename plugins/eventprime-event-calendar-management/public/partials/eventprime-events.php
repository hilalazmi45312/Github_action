<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$events_data = $ep_functions->load_event_common_options( $atts );
if($ep_functions->ep_get_global_settings('show_event_types_on_calendar')==1 || $ep_functions->ep_get_global_settings( 'disable_filter_options' ) != 1 )
{
     $events_data['event_types'] =  $ep_functions->ep_get_terms_with_meta_on_all_events_page('em_event_type',array( 'id', 'name', 'em_color', 'em_type_text_color' ));
}

if($ep_functions->ep_get_global_settings( 'disable_filter_options' ) != 1 )
{
    //get performaers
    $events_data['performers']  = $ep_functions->ep_get_performers_list( array( 'id', 'name' ) );
     //get organizers
    $events_data['organizers']  = $ep_functions->ep_get_terms_with_meta_on_all_events_page('em_event_organizer', array( 'id', 'name' ) );
     // get organizers
    $events_data['venues']      = $ep_functions->ep_get_terms_with_meta_on_all_events_page( 'em_venue',array( 'id', 'name', 'address', 'image' ), 1 );
}


        
$event_data = (object)$events_data;
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_script( 'jquery-ui-slider' );
wp_enqueue_style(
            'em-front-jquery-ui',
            plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/jquery-ui.min.css',
            false, EVENTPRIME_VERSION
);
// enqueue select2
wp_enqueue_style(
        'em-front-select2-css',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/css/select2.min.css',
        false, EVENTPRIME_VERSION
);
wp_enqueue_script(
        'em-front-select2-js',
        plugin_dir_url( EP_PLUGIN_FILE ) . 'public/js/select2.full.min.js',
        array( 'jquery' ), EVENTPRIME_VERSION
);
// load calendar library
wp_enqueue_style(
        'ep-front-event-calendar-css',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/css/ep-calendar.min.css',
        false, EVENTPRIME_VERSION
);
wp_enqueue_script(
        'ep-front-event-moment-js',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/moment.min.js',
        array('jquery'), EVENTPRIME_VERSION
);
wp_enqueue_script(
        'ep-front-event-calendar-js',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/ep-calendar.min.js',
        array('jquery'), EVENTPRIME_VERSION
);
wp_enqueue_script(
        'ep-front-event-fulcalendar-moment-js',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/fullcalendar-moment.min.js',
        array('jquery'), EVENTPRIME_VERSION
);
wp_enqueue_script(
        'ep-front-event-fulcalendar-local-js',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/locales-all.js',
        array('jquery'), EVENTPRIME_VERSION
);
wp_enqueue_style(
        'ep-front-events-css',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/css/ep-frontend-events.css',
        false, EVENTPRIME_VERSION
);

wp_enqueue_style(
        'em-front-common-utility-css',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/css/em-front-common-utility.css',
        false, EVENTPRIME_VERSION
);

wp_enqueue_style(
        'ep-front-events-css',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/css/ep-frontend-events.css',
        false, EVENTPRIME_VERSION
);

wp_enqueue_script(
        'ep-front-events-js',
        plugin_dir_url(EP_PLUGIN_FILE) . 'public/js/ep-frontend-events.js',
        array('jquery'), EVENTPRIME_VERSION
);
// get calendar events
$cal_events = array();

//if (!empty($events_data['events']->posts)) {
//    $cal_events = $ep_functions->get_front_calendar_view_event($events_data['events']->posts);
//}
wp_localize_script(
        'ep-front-events-js',
        'em_front_event_object',
        array(
            '_nonce' => wp_create_nonce('ep-frontend-nonce'),
            'ajaxurl' => admin_url('admin-ajax.php', null),
            'filter_applied_text' => esc_html__('Filter Applied', 'eventprime-event-calendar-management'),
            'filters_applied_text' => esc_html__('Filters Applied', 'eventprime-event-calendar-management'),
            'nonce_error' => esc_html__('Please refresh the page and try again.', 'eventprime-event-calendar-management'),
            'event_attributes' => $atts,
            'start_of_week' => get_option('start_of_week'),
            'cal_events' => $cal_events,
            'view' => $events_data['display_style'],
            'local' => $ep_functions->ep_get_calendar_locale(),
            'event_types' => $ep_functions->ep_global_settings_button_title('Event-Types'),
            'performers' => $ep_functions->ep_global_settings_button_title('Performers'),
            'venues' => $ep_functions->ep_global_settings_button_title('Venues'),
            'organizers' => $ep_functions->ep_global_settings_button_title('Organizers'),
            'list_week_btn_text' => esc_html__('Agenda', 'eventprime-event-calendar-management'),
            'hide_time_on_front_calendar' => $ep_functions->ep_get_global_settings( 'hide_time_on_front_calendar' ),
            'timezone' => $ep_functions->ep_get_site_timezone(),
            'timeformat' => $ep_functions->ep_get_global_settings( 'time_format' )
        )
);

// localized global settings
$global_settings = $ep_functions->ep_get_global_settings();
$currency_symbol = $ep_functions->ep_currency_symbol();
$datepicker_format = $ep_functions->ep_get_datepicker_format( 2 );
wp_localize_script(
        'ep-front-events-js',
        'eventprime',
        array(
            'global_settings' => $global_settings,
            'currency_symbol' => $currency_symbol,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'trans_obj' => $ep_functions->ep_define_common_field_errors(),
            'datepicker_format'    => $datepicker_format,
            'timezone' => $ep_functions->ep_get_site_timezone()
        )
);

// event masonry view library
wp_enqueue_script('masonry');
wp_enqueue_style('masonry');
// event slide view library
wp_enqueue_style('ep-responsive-slides-css');
wp_enqueue_script('ep-responsive-slides-js');
$event_data->event_atts = $atts;
$args = $event_data;
?>
<div class="emagic">
<?php
$themepath = $ep_requests->eventprime_get_ep_theme('events-tpl');
include $themepath;
?>
</div>