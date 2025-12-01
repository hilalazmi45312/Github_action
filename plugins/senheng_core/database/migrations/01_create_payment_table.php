<?php

register_activation_hook(__FILE__, 'senheng_create_payment_tables');
function senheng_create_payment_tables()
{
    senheng_create_payment_methods_table();
    senheng_create_payment_plans_table();
}

function senheng_create_payment_plans_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_payment_plans';

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        method_id BIGINT UNSIGNED NOT NULL,
        months INT NOT NULL,
        min_amount DECIMAL(10, 2) NOT NULL,
        charge_percent DECIMAL(5, 2) DEFAULT 0.00,
        charge_rm DECIMAL(10, 2) DEFAULT 0.00,
        cost_share_operator DECIMAL(5, 2) DEFAULT 0.00,
        cost_share_merchant DECIMAL(5, 2) DEFAULT 0.00,
        apply_admin_fee TINYINT(1) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        ipay88_id INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    dbDelta($sql);

    (new PaymentTableSeeder())->run();
}

function senheng_create_payment_methods_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'c_payment_methods';

    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) $charset_collate;";

    dbDelta($sql);
}
