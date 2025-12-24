<?php

add_filter('cron_schedules', function ($schedules) {
    $schedules['every_15_minutes'] = [
        'interval' => 15 * 60,
        'display'  => __('Every 15 Minutes'),
    ];
    $schedules['every_25_minutes'] = [
        'interval' => 25 * 60,
        'display'  => __('Every 25 Minutes'),
    ];
    return $schedules;
});

if (! wp_next_scheduled('custom_insider_bulk_feed_event_create')) {
    wp_schedule_event(time(), 'every_15_minutes', 'custom_insider_bulk_feed_event_create');
}
if (! wp_next_scheduled('custom_insider_bulk_feed_event_update')) {
    wp_schedule_event(time(), 'every_25_minutes', 'custom_insider_bulk_feed_event_update');
}


add_action('custom_insider_bulk_feed_event_create', function () {
    custom_insider_bulk_feed_handler('create');
});
add_action('custom_insider_bulk_feed_event_update', function () {
    custom_insider_bulk_feed_handler('update');
});
