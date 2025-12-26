<?php

/**
 * Plugin Name: Custom User Login Blocker (AJAX)
 * Description: AJAX-based user login blocker with role blocking, audit log, temp block, and force logout.
 * Version: 2.1.0
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
 * Admin Menu (VIP safe)
 * =========================
 */
add_action('admin_menu', function () {
    add_users_page(
        'Login Blocker',
        'Login Blocker',
        'list_users',
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

    wp_enqueue_script(
        'select2',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js',
        ['jquery'],
        '4.1.0',
        true
    );

    wp_enqueue_style(
        'select2-css',
        'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css',
        [],
        '4.1.0'
    );

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
    if (!current_user_can('list_users')) {
        wp_die(__('You do not have permission.'));
    }

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    $users = [];
    if ($search) {
        $users = get_users([
            'search' => "*{$search}*",
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number' => 50
        ]);
    }
?>
    <div class="wrap">
        <h1>Login Blocker</h1>

        <form method="get">
            <input type="hidden" name="page" value="culb-login-blocker">

            <p>
                <input type="text"
                    name="s"
                    value="<?php echo esc_attr($search); ?>"
                    placeholder="Search user by name or email"
                    style="width:300px">
                <button class="button">Search</button>
            </p>
        </form>

        <?php if ($search): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo esc_html($user->display_name); ?></td>
                                <td><?php echo esc_html($user->user_email); ?></td>
                                <td>
                                    <?php echo culb_is_user_blocked($user->ID)
                                        ? '<strong style="color:red">Blocked</strong>'
                                        : '<span style="color:green">Active</span>'; ?>
                                </td>
                                <td>
                                    <button class="button culb-toggle"
                                        data-id="<?php echo $user->ID; ?>"
                                        data-mode="<?php echo culb_is_user_blocked($user->ID) ? 'unblock' : 'block'; ?>">
                                        <?php echo culb_is_user_blocked($user->ID) ? 'Unblock' : 'Block'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <hr>

        <h2>Blocked Users</h2>
        <table class="widefat striped" id="culb-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Blocked Until</th>
                    <th>Blocked By</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
<?php
}

/**
 * =========================
 * AJAX: Search users (Select2)
 * =========================
 */
add_action('wp_ajax_culb_search_users', function () {
    check_ajax_referer('culb_ajax');

    $term = sanitize_text_field($_GET['q'] ?? '');

    $users = get_users([
        'search' => "*{$term}*",
        'number' => 20,
        'search_columns' => ['user_login', 'user_email', 'display_name']
    ]);

    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'id'   => $user->ID,
            'text' => "{$user->display_name} ({$user->user_email})"
        ];
    }

    wp_send_json_success($results);
});

/**
 * =========================
 * AJAX: Get blocked users
 * =========================
 */
add_action('wp_ajax_culb_get_blocked_users', function () {
    check_ajax_referer('culb_ajax');

    $users = get_users([
        'meta_key'   => 'culb_block_login',
        'meta_value' => '1'
    ]);

    $data = [];

    foreach ($users as $user) {
        $until = get_user_meta($user->ID, 'culb_block_until', true);
        $log   = get_user_meta($user->ID, 'culb_audit_log', true);
        $last  = is_array($log) ? end($log) : null;

        $blocked_by = $last && isset($last['by'])
            ? get_userdata($last['by'])->display_name
            : '-';

        $data[] = [
            'id'    => $user->ID,
            'name'  => $user->display_name,
            'email' => $user->user_email,
            'until' => $until ? date('Y-m-d H:i', $until) : 'Permanent',
            'by'    => $blocked_by
        ];
    }

    wp_send_json_success($data);
});

/**
 * =========================
 * AJAX: Block / Unblock
 * =========================
 */
add_action('wp_ajax_culb_update_block', function () {
    check_ajax_referer('culb_ajax');

    $mode  = sanitize_text_field($_POST['mode']);
    $users = array_map('intval', $_POST['users'] ?? []);
    $role  = sanitize_text_field($_POST['role'] ?? '');
    $until = sanitize_text_field($_POST['until'] ?? '');

    if ($role) {
        $users = get_users(['role' => $role, 'fields' => ['ID']]);
        $users = wp_list_pluck($users, 'ID');
    }

    foreach ($users as $user_id) {
        if ($mode === 'block') {
            update_user_meta($user_id, 'culb_block_login', '1');
            if ($until) {
                update_user_meta($user_id, 'culb_block_until', strtotime($until));
            }
            wp_destroy_user_sessions($user_id);
        } else {
            delete_user_meta($user_id, 'culb_block_login');
            delete_user_meta($user_id, 'culb_block_until');
        }

        culb_log_action($user_id, $mode);
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
    $log = get_user_meta($user_id, 'culb_audit_log', true);
    if (!is_array($log)) $log = [];

    $log[] = [
        'action' => $action,
        'by'     => get_current_user_id(),
        'time'   => current_time('mysql')
    ];

    update_user_meta($user_id, 'culb_audit_log', $log);
}
