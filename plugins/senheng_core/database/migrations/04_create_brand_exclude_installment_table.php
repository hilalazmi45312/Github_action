<?php

function create_admin_fee_waivers_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_admin_fee_waivers';

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        brand_slug VARCHAR(255) NULL,
        brand_name VARCHAR(255) NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    dbDelta($sql);

    // Insert default data
    seederAdminWaiver();
}

function seederAdminWaiver()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_admin_fee_waivers';

    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )
    );

    if ($table_exists != $table_name) {
        return;
    }
    $existing = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
    if ($existing > 0) {
        return; // Already seeded
    }
    $brands = ['apple', 'viomi'];
    $now = current_time('mysql');

    // Build query
    $values = [];
    foreach ($brands as $brand) {
        $values[] = $wpdb->prepare('(%s, %s)', sanitize_title($brand), $now);
    }
    $sql = "INSERT INTO {$table_name} (brand_slug, created_at) VALUES " . implode(',', $values);
    $wpdb->query($sql);
}
