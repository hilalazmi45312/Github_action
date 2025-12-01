<?php

function senheng_update_benefit_box_table_structure()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_benefit_box_settings';

    // Check if new columns exist, if not add them
    $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
    $column_names = array_column($columns, 'Field');

    if (!in_array('always_online', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN always_online TINYINT(1) DEFAULT 0 AFTER predefined_text");
    }

    if (!in_array('availability_schedule', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN availability_schedule JSON AFTER always_online");
    }

    if (!in_array('working_days_message', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN working_days_message TEXT AFTER availability_schedule");
    }

    if (!in_array('non_working_days_message', $column_names)) {
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN non_working_days_message TEXT AFTER working_days_message");
    }

    // Update existing WhatsApp records with default values
    $default_schedule = json_encode([
        'sunday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'monday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'tuesday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'wednesday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'thursday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'friday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'saturday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00']
    ]);

    $wpdb->update(
        $table_name,
        [
            'always_online' => 0,
            'availability_schedule' => $default_schedule,
            'working_days_message' => 'I will be back in [senheng_time_work]',
            'non_working_days_message' => 'I will be back soon'
        ],
        ['type' => 'whatsapp'],
        ['%d', '%s', '%s', '%s'],
        ['%s']
    );
}