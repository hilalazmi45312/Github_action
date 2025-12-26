<?php

/**
 * Plugin Name: Custom User Login Blocker (AJAX)
 * Description: AJAX-based user login blocker with role blocking, audit log, and temp block.
 * Version: 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * =========================
 * Helper: Check blocked
 * =========================
 */
function culb_is_user_blocked($user_id)
{
    $blocked = get_user_meta($user_id, 'culb_block_login', true);
    $until   = get_user_meta($user_id, 'culb_block_until', true);

    if (!$blocked) return false;

    if ($until && time() > intval($until)) {
        delete_user_meta($user_id, 'culb_block_login');
        delete_user_meta($user_id, 'culb_block_until');
        return false;
    }

    return true;
}

/**
 * =========================
 * Admin Menu
 * =========================
 */
add_action('admin_menu', function () {
    add_users_page(
        'Login Blocker',
        'Login Blocker',
        'manage_users',
        'culb-login-blocker',
        'culb_render_page'
    );
});

/**
 * =========================
 * Enqueue scripts
 * =========================
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'users_page_culb-login-blocker') return;

    wp_enqueue_script('jquery');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js', ['jquery']);
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css');

    wp_enqueue_script(
        'culb-admin',
        plugin_dir_url(__FILE__) . 'culb-admin.js',
        ['jquery', 'select2'],
        '1.0',
        true
    );

    wp_localize_script('culb-admin', 'culbAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('culb_ajax')
    ]);
});

/**
 * =========================
 * Admin Page
 * =========================
 */
function culb_render_page()
{
?>
    <div class="wrap">
        <h1>Login Blocker</h1>

        <h3>Block Individual Users</h3>
        <select id="culb-users" multiple style="width:400px"></select>

        <h3>Block by Role</h3>
        <select id="culb-role">
            <option value="">-- Select role --</option>
            <?php foreach (wp_roles()->roles as $role => $data): ?>
                <option value="<?= esc_attr($role) ?>"><?= esc_html($data['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <h3>Temporary Block</h3>
        <input type="datetime-local" id="culb-until">

        <p>
            <button class="button button-primary" id="culb-block">Block</button>
            <button class="button" id="culb-unblock">Unblock</button>
        </p>

        <div id="culb-result"></div>
    </div>
<?php
}

/**
 * =========================
 * AJAX: Get users (Select2)
 * =========================
 */
add_action('wp_ajax_culb_search_users', function () {
    check_ajax_referer('culb_ajax');

    $term = sanitize_text_field($_GET['q']);
    $users = get_users([
        'search' => "*{$term}*",
        'number' => 20,
        'search_columns' => ['user_login', 'user_email', 'display_name']
    ]);

    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'id' => $user->ID,
            'text' => "{$user->display_name} ({$user->user_email})"
        ];
    }

    wp_send_json($results);
});

/**
 * =========================
 * AJAX: Block / Unblock
 * =========================
 */
add_action('wp_ajax_culb_update_block', function () {
    check_ajax_referer('culb_ajax');

    $action_type = sanitize_text_field($_POST['mode']);
    $users       = array_map('intval', $_POST['users'] ?? []);
    $role        = sanitize_text_field($_POST['role'] ?? '');
    $until       = sanitize_text_field($_POST['until'] ?? '');

    if ($role) {
        $users = get_users(['role' => $role, 'fields' => ['ID']]);
        $users = wp_list_pluck($users, 'ID');
    }

    foreach ($users as $user_id) {

        if ($action_type === 'block') {
            update_user_meta($user_id, 'culb_block_login', '1');
            if ($until) {
                update_user_meta($user_id, 'culb_block_until', strtotime($until));
            }
            wp_destroy_user_sessions($user_id);
        } else {
            delete_user_meta($user_id, 'culb_block_login');
            delete_user_meta($user_id, 'culb_block_until');
        }

        culb_log_action($user_id, $action_type);
    }

    wp_send_json_success('Updated successfully');
});

/**
 * =========================
 * Audit Log
 * =========================
 */
function culb_log_action($user_id, $action)
{
    $log = get_user_meta($user_id, 'culb_audit_log', true) ?: [];

    $log[] = [
        'action' => $action,
        'by'     => get_current_user_id(),
        'time'   => current_time('mysql')
    ];

    update_user_meta($user_id, 'culb_audit_log', $log);
}
