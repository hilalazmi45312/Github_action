<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link              https://www.solwininfotech.com/
 * @since             1.0.0
 * @package           User_blocker
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
$ub_delete_data = get_option( 'ub_delete_data', 0 );

if ( 1 == $ub_delete_data ) {
	global $wpdb;
	global $wp_roles;
	delete_option( 'ublk_is_optin' );
	delete_option( 'user_blocking_promo_time' );
	delete_option( 'is_user_subscribed_cancled' );
	delete_option( 'ublk_version' );

	/**
	 * Remove user meta details upon plugin deletion.
	 */
	function remove_user_meta_on_plugin_delete() {
		// Get all user IDs.
		$user_ids = get_users( array( 'fields' => 'ID' ) );

		// Loop through each user ID and remove the meta.
		foreach ( $user_ids as $user_id ) {
			delete_user_meta( $user_id, 'is_active' );
			delete_user_meta( $user_id, 'block_day' );
			delete_user_meta( $user_id, 'block_msg_day' );
			delete_user_meta( $user_id, 'block_url_day' );
			delete_user_meta( $user_id, 'block_date' );
			delete_user_meta( $user_id, 'block_msg_date' );
			delete_user_meta( $user_id, 'block_url_date' );
			delete_user_meta( $user_id, 'block_msg_permenant' );
			delete_user_meta( $user_id, 'block_url_permenant' );

			// Replace 'your_meta_key' with the actual meta key you want to remove.
		}
	}

	// Call the function to remove user meta details.
	remove_user_meta_on_plugin_delete();

	// Get all roles.
	if ( ! function_exists( 'get_editable_roles' ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
	}
	$rols = get_option( 'wp_user_roles' );
	foreach ( $rols as $key => $val ) {
		if ( 'administrator' == $key ) {
			continue;
		}
		// Access role information.
		$get_role = $key;
		delete_option( $get_role . '_is_active' );
		delete_option( $get_role . '_block_msg_permenant' );
		delete_option( $get_role . '_block_day' );
		delete_option( $get_role . '_block_msg_day' );
		delete_option( $get_role . '_block_date' );
		delete_option( $get_role . '_block_msg_date' );
		delete_option( $get_role . '_block_msg_permenant' );
		delete_option( $gte_role . '_block_url_day' );
		delete_option( $get_role . '_block_url_date' );
		delete_option( $get_role . '_block_url_permenant' );

	}
}

