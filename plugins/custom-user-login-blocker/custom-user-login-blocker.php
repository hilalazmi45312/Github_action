<?php

/**
 * Plugin Name: Custom User Login Blocker (AJAX)
 * Description: AJAX-based user login blocker with role blocking, audit log, temp block, and force logout.
 * Version: 2.2.0
 */

if (!defined('ABSPATH')) exit;

/**
 * Helper: Check if user is blocked
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
 * Admin Menu
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
 * Enqueue scripts & styles
 */
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'users_page_culb-login-blocker') return;

    wp_enqueue_script('jquery');
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js', ['jquery'], null, true);

    wp_enqueue_script(
        'culb-admin',
        plugin_dir_url(__FILE__) . 'culb-admin.js',
        ['jquery', 'select2'],
        '2.2',
        true
    );

    wp_localize_script('culb-admin', 'culbAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('culb_ajax')
    ]);
});

/**
 * Render Admin Page
 */
function culb_render_page()
{
    if (!current_user_can('list_users')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $users = [];

    if ($search) {
        $users = get_users([
            'search'         => "*{$search}*",
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 50
        ]);
    }
?>
    <div class="wrap">
        <h1>Login Blocker</h1>

        <form method="get" style="margin-bottom:20px;">
            <input type="hidden" name="page" value="culb-login-blocker">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>"
                placeholder="Search by username, name or email" style="width:350px;">
            <button class="button button-primary">Search Users</button>
        </form>

        <?php if ($search && $users): ?>
            <h2>Search Results</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <?php if (culb_is_user_blocked($user->ID)): ?>
                                    <strong style="color:red">Blocked</strong>
                                <?php else: ?>
                                    <span style="color:green">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button culb-toggle"
                                    data-id="<?php echo $user->ID; ?>"
                                    data-mode="<?php echo culb_is_user_blocked($user->ID) ? 'unblock' : 'block'; ?>">
                                    <?php echo culb_is_user_blocked($user->ID) ? 'Unblock' : 'Block Permanently'; ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($search): ?>
            <p>No users found.</p>
        <?php endif; ?>

        <hr style="margin:40px 0;">

        <h2>Bulk Block Users</h2>
        <div style="max-width:600px; background:#fff; padding:15px; border:1px solid #ccd0d4;">
            <select id="culb-user-select" multiple="multiple" style="width:100%;"></select>
            <p style="margin:15px 0 5px;"><strong>Or block all users in role:</strong></p>
            <select id="culb-role-select">
                <option value="">— Select Role —</option>
                <?php wp_dropdown_roles(); ?>
            </select>

            <p style="margin:15px 0 5px;">
                <label><input type="checkbox" id="culb-temp-block"> Temporary block until:</label>
                <input type="datetime-local" id="culb-until" style="margin-left:10px;">
            </p>

            <button id="culb-bulk-block" class="button button-primary">Block Selected</button>
            <span id="culb-status" style="margin-left:15px; font-weight:bold;"></span>
        </div>

        <hr style="margin:40px 0;">

        <h2>Currently Blocked Users</h2>
        <table class="widefat fixed striped" id="culb-blocked-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Blocked Until</th>
                    <th>Blocked By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Filled by AJAX -->
            </tbody>
        </table>
    </div>
<?php
}

/**
 * AJAX: Search users for Select2
 */
add_action('wp_ajax_culb_search_users', function () {
    check_ajax_referer('culb_ajax');

    $term = sanitize_text_field($_GET['q'] ?? '');

    $users = get_users([
        'search'         => "*{$term}*",
        'number'         => 30,
        'search_columns' => ['user_login', 'user_email', 'display_name']
    ]);

    $results = [];
    foreach ($users as $user) {
        $results[] = [
            'id'   => $user->ID,
            'text' => sprintf('%s (%s)', $user->display_name, $user->user_email)
        ];
    }

    wp_send_json_success($results);
});

/**
 * AJAX: Get blocked users
 */
add_action('wp_ajax_culb_get_blocked_users', function () {
    check_ajax_referer('culb_ajax');

    $users = get_users([
        'meta_key'     => 'culb_block_login',
        'meta_value'   => '1',
        'meta_compare' => '='
    ]);

    $data = [];
    foreach ($users as $user) {
        $until = get_user_meta($user->ID, 'culb_block_until', true);
        $log   = get_user_meta($user->ID, 'culb_audit_log', true);
        $last  = is_array($log) && !empty($log) ? end($log) : null;

        $blocked_by = $last && !empty($last['by'])
            ? get_userdata($last['by'])->display_name ?? 'Unknown'
            : 'Unknown';

        $data[] = [
            'id'    => $user->ID,
            'name'  => esc_html($user->display_name),
            'email' => esc_html($user->user_email),
            'until' => $until ? date_i18n('Y-m-d H:i', $until) : 'Permanent',
            'by'    => esc_html($blocked_by)
        ];
    }

    wp_send_json_success($data);
});

/**
 * AJAX: Block / Unblock
 */
add_action('wp_ajax_culb_update_block', function () {
    check_ajax_referer('culb_ajax');

    $mode  = sanitize_text_field($_POST['mode'] ?? ''); // 'block' or 'unblock'
    $users = array_map('intval', (array) ($_POST['users'] ?? []));
    $role  = sanitize_text_field($_POST['role'] ?? '');
    $until = !empty($_POST['until']) ? sanitize_text_field($_POST['until']) : '';

    if ($role) {
        $role_users = get_users(['role' => $role, 'fields' => 'ID']);
        $users = array_merge($users, $role_users);
        $users = array_unique($users);
    }

    if (empty($users) || !in_array($mode, ['block', 'unblock'])) {
        wp_send_json_error('Invalid request.');
    }

    foreach ($users as $user_id) {
        if ($user_id == get_current_user_id()) {
            continue; // Prevent self-lockout
        }

        if ($mode === 'block') {
            update_user_meta($user_id, 'culb_block_login', '1');

            if ($until) {
                update_user_meta($user_id, 'culb_block_until', strtotime($until));
            } else {
                delete_user_meta($user_id, 'culb_block_until');
            }

            // Force logout (VIP safe)
            if (class_exists('WP_Session_Tokens')) {
                $sessions = WP_Session_Tokens::get_instance($user_id);
                $sessions->destroy_all();
            }
        } else {
            delete_user_meta($user_id, 'culb_block_login');
            delete_user_meta($user_id, 'culb_block_until');
        }

        culb_log_action($user_id, $mode . ($until ? ' (temp)' : ''));
    }

    wp_send_json_success(['message' => 'Updated successfully', 'count' => count($users)]);
});

/**
 * Audit Log
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

    update_user_meta($user_id, 'culb_audit_log', array_slice($log, -50)); // Keep last 50 entries
}
