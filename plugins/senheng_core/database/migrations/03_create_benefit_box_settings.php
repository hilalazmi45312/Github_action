<?php

function senheng_create_benefit_box_tables()
{
    senheng_create_benefit_box_settings_table();
}

function senheng_create_benefit_box_settings_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_benefit_box_settings';

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        type ENUM('whatsapp', 'installment') NOT NULL,
        title VARCHAR(255) NOT NULL,
        subtitle TEXT,
        icon VARCHAR(255),
        whatsapp_number VARCHAR(50),
        predefined_text TEXT,
        always_online TINYINT(1) DEFAULT 0,
        availability_schedule JSON,
        working_days_message TEXT,
        non_working_days_message TEXT,
        is_active TINYINT(1) DEFAULT 1,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    dbDelta($sql);

    // Insert default data
    senheng_insert_default_benefit_box_data();
}

function senheng_insert_default_benefit_box_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_benefit_box_settings';

    // Check if data already exists
    $existing = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    if ($existing > 0) {
        return;
    }

    // Default availability schedule
    $default_schedule = json_encode([
        'sunday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'monday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'tuesday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'wednesday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'thursday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'friday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00'],
        'saturday' => ['enabled' => true, 'start' => '10:00', 'end' => '21:00']
    ]);

    // Insert default WhatsApp settings
    $wpdb->insert(
        $table_name,
        [
            'type' => 'whatsapp',
            'title' => 'Senheng Customer Support',
            'subtitle' => 'Need Help? Chat with us',
            'icon' => '',
            'whatsapp_number' => '+601136004040',
            'predefined_text' => 'Hi, I`ve just visited [senheng_page_title], and I need some help. Here is the link: [senheng_page_url]',
            'always_online' => 0,
            'availability_schedule' => $default_schedule,
            'working_days_message' => 'I will be back in [senheng_time_work]',
            'non_working_days_message' => 'I will be back soon',
            'is_active' => 1,
            'sort_order' => 1
        ],
        ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d']
    );

    // Insert default installment settings
    $wpdb->insert(
        $table_name,
        [
            'type' => 'installment',
            'title' => 'Pay 0% interest for up to 60 months.',
            'subtitle' => 'With an eligible bank and e-wallet, you can choose the financing option that works for you.**',
            'icon' => '',
            'is_active' => 1,
            'sort_order' => 2
        ],
        ['%s', '%s', '%s', '%s', '%d', '%d']
    );
}

