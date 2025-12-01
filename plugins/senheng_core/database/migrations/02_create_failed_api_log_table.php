<?php

register_activation_hook(__FILE__, 'create_failed_api_log_table');

function create_failed_api_log_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'failed_api_logs';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        request_from_url TEXT NOT NULL,
        request_url TEXT NOT NULL,
        request_method VARCHAR(10) DEFAULT 'GET',
        request_body LONGTEXT,
        response_code INT DEFAULT 0,
        response_message TEXT,
        error_message TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
