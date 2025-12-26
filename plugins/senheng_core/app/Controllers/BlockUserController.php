<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper: Check if user is blocked
 */
function culb_is_user_blocked($user_id)
{
    return (bool) get_user_meta($user_id, 'culb_block_login', true);
}

/**
 * Admin menu page
 */
add_action('admin_menu', function () {
    add_users_page(
        'Login Blocker',
        'Login Blocker',
        'manage_users',
        'culb-login-blocker',
        'culb_render_admin_page'
    );
});

/**
 * Render admin page
 */
function culb_render_admin_page()
{
    if (!current_user_can('manage_users')) {
        return;
    }

    // Handle form submission
    if (isset($_POST['culb_nonce']) && wp_verify_nonce($_POST['culb_nonce'], 'culb_save')) {
        $blocked_users = isset($_POST['blocked_users']) ? array_map('intval', $_POST['blocked_users']) : [];

        // Clear existing blocks
        $users = get_users(['fields' => ['ID']]);
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'culb_block_login');
        }

        // Apply new blocks
        foreach ($blocked_users as $user_id) {
            update_user_meta($user_id, 'culb_block_login', '1');
        }

        echo '<div class="updated notice"><p>Login block list updated.</p></div>';
    }

    $users = get_users();
?>
    <div class="wrap">
        <h1>Login Blocker</h1>
        <p>Select users who should be <strong>blocked from logging in</strong>.</p>

        <form method="post">
            <?php wp_nonce_field('culb_save', 'culb_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th>Select Users</th>
                    <td>
                        <select name="blocked_users[]" multiple size="12" style="width: 350px;">
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo esc_attr($user->ID); ?>"
                                    <?php selected(culb_is_user_blocked($user->ID)); ?>>
                                    <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            Hold <strong>Ctrl / Cmd</strong> to select multiple users.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Save Blocked Users'); ?>
        </form>
    </div>
<?php
}
