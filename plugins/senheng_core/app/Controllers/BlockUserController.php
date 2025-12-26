<?php

/**
 * Add block checkbox to user profile
 */
function culb_add_block_field($user) {
    if (!current_user_can('edit_users')) {
        return;
    }
    ?>
    <h3>Login Restrictions</h3>
    <table class="form-table">
        <tr>
            <th><label for="culb_block_login">Block Login</label></th>
            <td>
                <input type="checkbox" name="culb_block_login" id="culb_block_login" value="1"
                    <?php checked(get_user_meta($user->ID, 'culb_block_login', true), '1'); ?> />
                <span class="description">Prevent this user from logging in (custom login flows).</span>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'culb_add_block_field');
add_action('edit_user_profile', 'culb_add_block_field');

/**
 * Save block flag
 */
function culb_save_block_field($user_id) {
    if (!current_user_can('edit_users')) {
        return;
    }

    if (isset($_POST['culb_block_login'])) {
        update_user_meta($user_id, 'culb_block_login', '1');
    } else {
        delete_user_meta($user_id, 'culb_block_login');
    }
}
add_action('personal_options_update', 'culb_save_block_field');
add_action('edit_user_profile_update', 'culb_save_block_field');

/**
 * Helper function to check if user login is blocked
 */
function culb_is_user_blocked($user_id) {
    return (bool) get_user_meta($user_id, 'culb_block_login', true);
}
