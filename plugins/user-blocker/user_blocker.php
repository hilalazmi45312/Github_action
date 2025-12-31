<?php
/**
 *  Plugin Name: User Blocker
 *  Plugin URI: https://wordpress.org/plugins/user-blocker/
 *  Description: Block your unwanted site users except admin based on day, time and date or permanently.
 *  Author: Solwin Infotech
 *  Author URI: https://www.solwininfotech.com/
 *  Copyright: Solwin Infotech
 *  Version: 2.2
 *  Requires at least: 5.4
 *  Tested up to: 6.6.1
 *  License: GPLv2 or later
 *
 * @link              https://www.solwininfotech.com/
 * @since             1.0.0
 * @package           User_blocker
 */

/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exit if add_action function not found
 */
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

define( 'UB_PLUGIN_URL', plugins_url( '', __FILE__ ) );
define( 'UB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require UB_PLUGIN_DIR . 'includes/user-blocker-common-functions.php';
require UB_PLUGIN_DIR . 'includes/user-blocker-blocked-users-list.php';
require UB_PLUGIN_DIR . 'includes/user-blocker-block-users.php';
require_once UB_PLUGIN_DIR . 'includes/user-blocker-promo-notice.php';

add_action( 'admin_menu', 'ublk_plugin_setup' );
add_action( 'plugins_loaded', 'ublk_latest_news_solwin_feed' );
add_action( 'current_screen', 'ublk_footer' );
add_action( 'admin_enqueue_scripts', 'ublk_enqueue_style_script' );
add_action( 'plugins_loaded', 'ublk_load_text_domain' );

add_filter( 'set-screen-option', 'ublk_set_screen_option', 10, 3 );

$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'ublk_settings_link', 10, 2 );
add_action( 'init', 'ublk_session_start' );
add_action( 'admin_head', 'ublk_subscribe_mail', 10 );
add_action( 'wp_ajax_close_tab', 'wp_ajax_blocker_close_tab' );

/**
 * Enqueue admin panel required js
 */
if ( ! function_exists( 'ublk_plugin_setup' ) ) {

	/**
	 * User Blocker Plugin Setup.
	 */
	function ublk_plugin_setup() {
		global $screen_option_listbytime, $screen_option_listbydate, $screen_option_listbypermanent, $screen_option_listbyalltypes;
		$ublk_is_optin = get_option( 'ublk_is_optin' );
		if ( 'yes' == $ublk_is_optin || 'no' == $ublk_is_optin ) {
			add_menu_page( esc_html__( 'User Blocker', 'user-blocker' ), esc_html__( 'User Blocker', 'user-blocker' ), 'manage_options', 'block_user', 'ublk_block_user_page', 'dashicons-admin-users', 80 );
		} else {
			add_menu_page( esc_html__( 'User Blocker', 'user-blocker' ), esc_html__( 'User Blocker', 'user-blocker' ), 'manage_options', 'welcome_block_user', 'ublk_welcome_page', 'dashicons-admin-users', 80 );
		}
		$block_date_page = add_submenu_page( 'User Blocker', esc_html__( 'Block User Date Wise', 'user-blocker' ), esc_html__( 'Date Wise Block User', 'user-blocker' ), 'manage_options', 'block_user_date', 'ublk_block_user_date_page', 1 );
		$block_permanent = add_submenu_page( 'User Blocker', esc_html__( 'Block User Permanent', 'user-blocker' ), esc_html__( 'Permanently Block User', 'user-blocker' ), 'manage_options', 'block_user_permenant', 'ublk_block_user_permenant_page', 2 );

		$screen_option_listbytime = add_submenu_page( 'block_user', esc_html__( 'Blocked User list', 'user-blocker' ), esc_html__( 'Blocked User list', 'user-blocker' ), 'manage_options', 'blocked_user_list', 'ublk_block_user_list_page', 3 );
		add_action( "load-$screen_option_listbytime", 'ublk_screen_options_list_by_time' );

		if ( 'yes' == $ublk_is_optin || 'no' == $ublk_is_optin ) {
			$screen_option_listbydate = add_submenu_page( 'User Blocker', esc_html__( 'Date Wise Blocked User list', 'user-blocker' ), esc_html__( 'Date Wise Blocked User list', 'user-blocker' ), 'manage_options', 'datewise_blocked_user_list', 'ublk_datewise_block_user_list_page', 4 );
			add_action( "load-$screen_option_listbydate", 'ublk_screen_options_list_by_date' );
		} else {
			$screen_option_listbydate = add_submenu_page( 'User Blocker', esc_html__( 'Date Wise Blocked User list', 'user-blocker' ), esc_html__( 'Date Wise Blocked User list', 'user-blocker' ), 'manage_options', 'datewise_blocked_user_list', 'ublk_datewise_block_user_list_page', 4 );
			add_action( "load-$screen_option_listbydate", 'ublk_screen_options_list_by_date' );
		}

		if ( 'yes' == $ublk_is_optin || 'no' == $ublk_is_optin ) {
			$screen_option_listbypermanent = add_submenu_page( 'User Blocker', esc_html__( 'Permanent Blocked User list', 'user-blocker' ), esc_html__( 'Permanent Blocked User list', 'user-blocker' ), 'manage_options', 'permanent_blocked_user_list', 'ublk_permanent_block_user_list_page', 5 );
			add_action( "load-$screen_option_listbypermanent", 'ublk_screen_options_list_by_permanent' );
		} else {
			$screen_option_listbypermanent = add_submenu_page( 'User Blocker', esc_html__( 'Permanent Blocked User list', 'user-blocker' ), esc_html__( 'Permanent Blocked User list', 'user-blocker' ), 'manage_options', 'permanent_blocked_user_list', 'ublk_permanent_block_user_list_page', 5 );
			add_action( "load-$screen_option_listbypermanent", 'ublk_screen_options_list_by_permanent' );
		}

		if ( 'yes' == $ublk_is_optin || 'no' == $ublk_is_optin ) {
			$screen_option_listbyalltypes = add_submenu_page( 'User Blocker', esc_html__( 'All Type Blocked User list', 'user-blocker' ), esc_html__( 'All Type Blocked User list', 'user-blocker' ), 'manage_options', 'all_type_blocked_user_list', 'ublk_all_type_block_user_list_page', 6 );
			add_action( "load-$screen_option_listbyalltypes", 'ublk_screen_options_list_by_alltypes' );
		} else {
			$screen_option_listbyalltypes = add_submenu_page( 'User Blocker', esc_html__( 'All Type Blocked User list', 'user-blocker' ), esc_html__( 'All Type Blocked User list', 'user-blocker' ), 'manage_options', 'all_type_blocked_user_list', 'ublk_all_type_block_user_list_page', 6 );
			add_action( "load-$screen_option_listbyalltypes", 'ublk_screen_options_list_by_alltypes' );
		}
		add_submenu_page( 'block_user', esc_html__( 'User Blocker Settings', 'user-blocker' ), esc_html__( 'User Blocker Settings', 'user-blocker' ), 'manage_options', 'user_blocker_settings', 'ublk_block_user_setting_page', 3 );

	}
}

if ( ! function_exists( 'ublk_screen_options_list_by_time' ) ) {
	/**
	 * User Blocker Screen options.
	 */
	function ublk_screen_options_list_by_time() {
		global $screen_option_listbytime;
		$screen_listbytime = get_current_screen();

		// get out of here if we are not on our settings page.
		if ( ! is_object( $screen_listbytime ) || $screen_listbytime->id != $screen_option_listbytime ) {
			return;
		}

		$args = array(
			'label'   => esc_html__( 'Number of Users per page', 'user-blocker' ) . ' : ',
			'default' => 10,
			'option'  => 'ublk_list_by_time_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'ublk_screen_options_list_by_date' ) ) {

	/**
	 * User Blocker Screen options list by date.
	 */
	function ublk_screen_options_list_by_date() {
		global $screen_option_listbydate;
		$screen_listbydate = get_current_screen();

		// get out of here if we are not on our settings page.
		if ( ! is_object( $screen_listbydate ) || $screen_listbydate->id != $screen_option_listbydate ) {
			return;
		}

		$args = array(
			'label'   => esc_html__( 'Number of Users per page', 'user-blocker' ) . ' : ',
			'default' => 10,
			'option'  => 'ublk_list_by_date_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'ublk_screen_options_list_by_permanent' ) ) {
	/**
	 * User Blocker Screen options list by permanent.
	 */
	function ublk_screen_options_list_by_permanent() {
		global $screen_option_listbypermanent;
		$screen_listbypermanent = get_current_screen();

		// get out of here if we are not on our settings page.
		if ( ! is_object( $screen_listbypermanent ) || $screen_listbypermanent->id != $screen_option_listbypermanent ) {
			return;
		}

		$args = array(
			'label'   => esc_html__( 'Number of Users per page', 'user-blocker' ) . ' : ',
			'default' => 10,
			'option'  => 'ublk_list_by_permanent_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'ublk_screen_options_list_by_alltypes' ) ) {

	/**
	 * User Blocker Screen options list by all types.
	 */
	function ublk_screen_options_list_by_alltypes() {
		global $screen_option_listbyalltypes;
		$screen_listbyalltypes = get_current_screen();

		// get out of here if we are not on our settings page.
		if ( ! is_object( $screen_listbyalltypes ) || $screen_listbyalltypes->id != $screen_option_listbyalltypes ) {
			return;
		}

		$args = array(
			'label'   => esc_html__( 'Number of Users per page', 'user-blocker' ) . ' : ',
			'default' => 10,
			'option'  => 'ublk_list_by_alltypes_per_page',
		);
		add_screen_option( 'per_page', $args );
	}
}

if ( ! function_exists( 'ublk_set_screen_option' ) ) {
	/**
	 * User Blocker Screen options.
	 *
	 * @param string $status Status.
	 * @param string $option Option.
	 * @param string $value Value.
	 * @return \WP_Error
	 */
	function ublk_set_screen_option( $status, $option, $value ) {
		if ( 'ublk_list_by_time_per_page' == $option || 'ublk_list_by_date_per_page' === $option || 'ublk_list_by_permanent_per_page' === $option || 'ublk_list_by_alltypes_per_page' === $option ) {
			return $value;
		}
		return $status;
	}
}


if ( ! function_exists( 'ublk_auth_signon' ) ) {
	/**
	 * User Blocker Authentication Sign-On.
	 *
	 * @param string $user user.
	 * @param string $username username.
	 * @param string $password password.
	 */
	function ublk_auth_signon( $user, $username, $password ) {
		if ( ! is_wp_error( $user ) && is_object( $user ) ) {
			$user_id      = $user->ID;
			$is_active    = get_user_meta( $user_id, 'is_active', true );
			$block_day    = get_user_meta( $user_id, 'block_day', true );
			$block_date   = get_user_meta( $user_id, 'block_date', true );
			$user         = get_userdata( $user_id );
			$user_roles   = $user->roles;
			$rolblock_day = get_option( $user_roles[0] . '_block_day' );
			if ( 'n' == $is_active ) {
				$block_msg_permenant = get_user_meta( $user_id, 'block_msg_permenant', true );
				$block_url_permenant = get_user_meta( $user_id, 'block_url_permenant', true );
				add_filter( 'login_errors', 'ublk_login_error', 10 );
				return new WP_Error( 'authentication_failed', '<strong>' . esc_html__( 'ERROR', 'user-blocker' ) . '</strong>: ' . $block_msg_permenant );
			} else {
				$error_msg = '';
				if ( ! empty( $block_day ) && 0 != $block_day && '' != $block_day ) {
					$full_date    = getdate();
					$current_day  = strtolower( $full_date['weekday'] );
					$current_time = current_time( 'timestamp' );
					if ( array_key_exists( $current_day, $block_day ) ) {
						$from_time = $block_day[ $current_day ]['from'];
						$to_time   = $block_day[ $current_day ]['to'];
						$from_time = strtotime( $from_time );
						$to_time   = strtotime( $to_time );
						if ( $current_time >= $from_time && $current_time <= $to_time ) {
							$block_msg_day = get_user_meta( $user_id, 'block_msg_day', true );
							$error_msg     = ' ' . $block_msg_day;
						}
					}
				}
				if ( ! empty( $rolblock_day ) && 0 != $rolblock_day && '' != $rolblock_day ) {
					$full_date    = getdate();
					$current_day  = strtolower( $full_date['weekday'] );
					$current_time = current_time( 'timestamp' );
					if ( array_key_exists( $current_day, $rolblock_day ) ) {
						$from_time = $rolblock_day[ $current_day ]['from'];
						$to_time   = $rolblock_day[ $current_day ]['to'];
						$from_time = strtotime( $from_time );
						$to_time   = strtotime( $to_time );
						if ( $current_time >= $from_time && $current_time <= $to_time ) {
							$block_msg_day = get_option( $user_roles[0] . '_block_msg_day' );
							$error_msg     = ' ' . $block_msg_day;
						}
					}
				}
				if ( 0 != $block_date && '' != $block_date && ! empty( $block_date ) ) {
					$frmdate      = $block_date['frmdate'];
					$todate       = $block_date['todate'];
					$frmdate      = strtotime( $frmdate ) . '</br>';
					$todate       = strtotime( $todate );
					$current_date = current_time( 'timestamp' );
					if ( $current_date >= $frmdate && $current_date <= $todate ) {
						$block_msg_date = get_user_meta( $user_id, 'block_msg_date', true );
						if ( '' == $error_msg ) {
							$error_msg = ' ' . $block_msg_date;
						} else {
							$error_msg .= ' ' . $block_msg_date;
						}
					}
				}
				if ( '' != $error_msg ) {
					add_filter( 'login_errors', 'ublk_login_error', 10 );
					return new WP_Error( 'authentication_failed', '<strong>' . esc_html__( 'ERROR', 'user-blocker' ) . '</strong>: ' . $error_msg );
				}
			}
		}
		return $user;
	}
}
add_filter( 'authenticate', 'ublk_auth_signon', 30, 3 );

if ( ! function_exists( 'ublk_login_error' ) ) {
	/**
	 * User Blocker Login Error.
	 *
	 * @param string $parm Parameters.
	 * @return \WP_Error
	 */
	function ublk_login_error( $parm ) {
		$my_message = $parm;
		$username   = '';
		$user_id    = '';
		if ( isset( $_REQUEST ) ) {
			foreach ( (array) $_REQUEST as $key => $val ) {
				if ( 'log' == $key ) {
					$username = $val;
					$user     = get_user_by( 'login', $username );
					$user_id  = isset( $user->ID ) ? $user->ID : '';
				}
			}
		}
		if ( ! empty( $user_id ) ) {
			$is_active           = get_user_meta( $user_id, 'is_active', true );
			$block_day           = get_user_meta( $user_id, 'block_day', true );
			$block_date          = get_user_meta( $user_id, 'block_date', true );
			$block_url_day       = get_user_meta( $user_id, 'block_url_day', true );
			$block_url_date      = get_user_meta( $user_id, 'block_url_date', true );
			$block_url_permenant = get_user_meta( $user_id, '$block_url_permenant', true );
			if ( 'n' == $is_active ) {
				$block_msg_permenant = get_user_meta( $user_id, 'block_msg_permenant', true );
				$block_url_permenant = get_user_meta( $user_id, 'block_url_permenant', true );

				if ( '' != $block_msg_permenant && '' == $block_url_permenant ) {
					$my_message = '<strong>' . esc_html__( 'ERROR', 'user-blocker' ) . '</strong>: ' . $block_msg_permenant;
				} elseif ( '' != $block_msg_permenant && '' != $block_url_permenant ) {
					wp_redirect( $block_url_permenant );
				}
			} else {
				$error_msg = '';
				if ( ! empty( $block_day ) && 0 != $block_day && '' != $block_day ) {
					$full_date    = getdate();
					$current_day  = strtolower( $full_date['weekday'] );
					$current_time = current_time( 'timestamp' );
					if ( array_key_exists( $current_day, $block_day ) ) {
						$from_time = $block_day[ $current_day ]['from'];
						$to_time   = $block_day[ $current_day ]['to'];
						$from_time = strtotime( $from_time );
						$to_time   = strtotime( $to_time );
						if ( $current_time >= $from_time && $current_time <= $to_time ) {
							$block_msg_day = get_user_meta( $user_id, 'block_msg_day', true );
							$error_msg     = ' ' . $block_msg_day;
						}
					}
				}
				if ( $block_date && '' != $block_date && ! empty( $block_date ) ) {
					$frmdate      = $block_date['frmdate'];
					$todate       = $block_date['todate'];
					$frmdate      = strtotime( $frmdate ) . '</br>';
					$todate       = strtotime( $todate );
					$current_date = current_time( 'timestamp' );
					if ( $current_date >= $frmdate && $current_date <= $todate ) {
						$block_msg_date = get_user_meta( $user_id, 'block_msg_date', true );
						if ( '' == $error_msg ) {
							$error_msg = ' ' . $block_msg_date;
						} else {
							$error_msg .= ' ' . $block_msg_date;
						}
					}
				}
				if ( '' != $error_msg && '' == $block_url_day ) {
					$my_message = '<strong>' . esc_html__( 'ERROR', 'user-blocker' ) . '</strong>:' . $error_msg;
				} elseif ( '' != $error_msg && '' != $block_url_day ) {
					wp_redirect( $block_url_day );
				}

				if ( '' != $error_msg && '' == $block_url_date ) {
					$my_message = '<strong>' . esc_html__( 'ERROR', 'user-blocker' ) . '</strong>:' . $error_msg;
				} elseif ( '' != $error_msg && '' != $block_url_date ) {
					wp_redirect( $block_url_date );
				}
			}
		}

		return $my_message;
	}
}

if ( ! function_exists( 'ublk_when_register' ) ) {
	/**
	 * User Blocker Register.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_when_register( $user_id ) {
		$user_id;
		$user_info       = get_userdata( $user_id );
		$user_role       = $user_info->roles[0];
		$permenant_block = get_option( $user_role . '_is_active' );
		if ( 'n' == $permenant_block ) {
			update_user_meta( $user_id, 'is_active', 'n' );
			$block_msg_permenant = get_option( $user_role . '_block_msg_permenant' );
			update_user_meta( $user_id, 'block_msg_permenant', $block_msg_permenant );
			$block_url_permenant = get_option( $user_role . '_block_url_permenant' );
			update_user_meta( $user_id, 'block_url_permenant', $block_url_permenant );
		} else {
			$day_wise_block      = get_option( $user_role . '_block_day' );
			$date_wise_block     = get_option( $user_role . '_block_date' );
			$day_wise_block_msg  = get_option( $user_role . '_block_msg_day' );
			$date_wise_block_msg = get_option( $user_role . '_block_msg_date' );
			$all                 = get_option( $user_role . '_all' );
			if ( 0 != $day_wise_block && '' != $day_wise_block ) {
				update_user_meta( $user_id, 'block_day', $day_wise_block );
				update_user_meta( $user_id, 'block_msg_day', $day_wise_block_msg );
			}
			if ( 0 != $date_wise_block && '' != $date_wise_block ) {
				update_user_meta( $user_id, 'block_date', $date_wise_block );
				update_user_meta( $user_id, 'block_msg_date', $date_wise_block_msg );
			}
			if ( 0 != $all && '' != $all ) {
				update_user_meta( $user_id, 'all', $date_wise_block_msg );
			}
		}
	}
}
add_action( 'user_register', 'ublk_when_register', 10, 1 );

if ( ! function_exists( 'ublk_validate_time' ) ) {
	/**
	 * User Blocker Validate the time.
	 *
	 * @param string $time Time.
	 * @return int
	 */
	function ublk_validate_time( $time ) {
		$split_by_space = explode( ' ', $time );
		$first_part     = $split_by_space[0];
		$second_part    = $split_by_space[1];
		if ( 'AM' == $second_part || 'PM' == $second_part ) {
			$time_int_split = explode( ':', $first_part );
			if ( 2 == strlen( $time_int_split[0] ) && 2 == strlen( $time_int_split[1] ) ) {
				$time_first  = intval( $time_int_split[0] );
				$time_second = intval( $time_int_split[1] );
				if ( $time_second >= 0 || $time_second < 60 ) {
					if ( $time_second >= 1 || $time_second < 13 ) {
						return 1;
					} else {
						return 0;
					}
				} else {
					return 0;
				}
			} else {
				return 0;
			}
		} else {
			return 0;
		}
	}
}

if ( ! function_exists( 'ublk_display_block_time' ) ) {
	/**
	 * Display Block Time.
	 *
	 * @param string $day Day.
	 * @param string $block_day Block Day.
	 */
	function ublk_display_block_time( $day, $block_day ) {
		if ( is_array( $block_day ) ) {
			if ( array_key_exists( $day, $block_day ) ) {
				$from_time = $block_day[ $day ]['from'];
				$to_time   = $block_day[ $day ]['to'];
				if ( '' != $from_time && '' != $to_time ) {
					echo '<div class="days">';
					echo '<span>' . esc_html( strtoupper( $day ) ) . '</span>';
					echo '<span>' . esc_html( ublk_time_to_twelve_hour( $from_time ) ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( ublk_time_to_twelve_hour( $to_time ) ) . '</span>';
					echo '</div>';
				}
			}
		}
	}
}

if ( ! function_exists( 'ublk_block_role_users_day' ) ) {
	/**
	 * Block User Role Day wise.
	 *
	 * @param string $role Role.
	 * @param string $old_block_day Old Block Day.
	 * @param string $block_day Block Day.
	 * @param string $old_block_msg_day Old Block Day Message.
	 * @param string $block_msg_day Block Day Message.
	 * @param string $old_block_url_day Old Block Day URL.
	 * @param string $block_url_day Block Day URL.
	 */
	function ublk_block_role_users_day( $role, $old_block_day, $block_day, $old_block_msg_day, $block_msg_day, $old_block_url_day, $block_url_day ) {
		// Update all users of this role.
		$role_usr_qry  = get_users( array( 'role' => $role ) );
		$curr_role_usr = wp_list_pluck( $role_usr_qry, 'ID' );
		if ( count( $curr_role_usr ) > 0 ) {
			foreach ( $curr_role_usr as $u_id ) {
				$own_block_day     = get_user_meta( $u_id, 'block_day', true );
				$own_block_msg_day = get_user_meta( $u_id, 'block_msg_day', true );
				$own_block_url_day = get_user_meta( $u_id, 'block_url_msg', true );
				if ( ( empty( $own_block_day ) || ( $own_block_day == $old_block_day ) ) && ( empty( $own_block_msg_day ) || $old_block_msg_day == $own_block_msg_day ) && ( empty( $own_block_url_day ) || $old_block_url_day == $own_block_url_day ) ) {
					// Not update already date wise blocked users.
					$is_active = get_user_meta( $u_id, 'is_active', true );
					if ( 'n' != $is_active ) {
						update_user_meta( $u_id, 'block_day', $block_day );
						update_user_meta( $u_id, 'block_msg_day', $block_msg_day );
						update_user_meta( $u_id, 'block_url_day', $block_url_day );
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'ublk_block_role_users_date' ) ) {

	/**
	 * Block User Role Date wise.
	 *
	 * @param string $role Role.
	 * @param string $old_block_date Old Block Date.
	 * @param string $block_date Block Date.
	 * @param string $old_block_msg_date Old Block Date Message.
	 * @param string $block_msg_date Block Date Message.
	 * @param string $old_block_url_date Old Block Date URL.
	 * @param string $block_url_date Block Date URL.
	 */
	function ublk_block_role_users_date( $role, $old_block_date, $block_date, $old_block_msg_date, $block_msg_date, $old_block_url_date, $block_url_date ) {
		// Update all users of this role.
		$role_usr_qry  = get_users( array( 'role' => $role ) );
		$curr_role_usr = wp_list_pluck( $role_usr_qry, 'ID' );
		if ( count( $curr_role_usr ) > 0 ) {
			foreach ( $curr_role_usr as $u_id ) {
				$own_block_date     = get_user_meta( $u_id, 'block_date', true );
				$own_block_msg_date = get_user_meta( $u_id, 'block_msg_date', true );
				$own_block_url_date = get_user_meta( $u_id, 'block_url_date', true );
				if ( ( empty( $own_block_date ) || ( $own_block_date == $old_block_date ) ) && ( empty( $own_block_msg_date ) || $old_block_msg_date == $own_block_msg_date ) && ( empty( $own_block_url_date ) || ( $own_block_url_date == $block_url_date ) ) ) {
					// Not update already date wise blocked users.
					$is_active = get_user_meta( $u_id, 'is_active', true );
					if ( 'n' != $is_active ) {
						update_user_meta( $u_id, 'block_date', $block_date );
						update_user_meta( $u_id, 'block_msg_date', $block_msg_date );
						update_user_meta( $u_id, 'block_url_date', $block_url_date );
					}
				}
			}
		}
	}
}

if ( ! function_exists( 'ublk_block_role_users_permenant' ) ) {

	/**
	 * Block the user-role wise permanently.
	 *
	 * @param string $role Role.
	 * @param string $is_active Is Active.
	 * @param string $old_block_msg_permenant Old Block Permanent Message.
	 * @param string $block_msg_permenant Block Permanent Message.
	 * @param string $old_block_url_permenant Old Block Permanent URL.
	 * @param string $block_url_permenant Block Permanent URL.
	 */
	function ublk_block_role_users_permenant( $role, $is_active, $old_block_msg_permenant, $block_msg_permenant, $old_block_url_permenant, $block_url_permenant ) {
		// Update all users of this role.
		$role_usr_qry  = get_users( array( 'role' => $role ) );
		$curr_role_usr = wp_list_pluck( $role_usr_qry, 'ID' );
		if ( count( $curr_role_usr ) > 0 ) {
			foreach ( $curr_role_usr as $u_id ) {
				$is_active_a             = get_user_meta( $u_id, 'is_active', true );
				$own_block_msg_permenant = get_user_meta( $u_id, 'block_msg_permenant', true );
				$own_block_url_permenant = get_user_meta( $u_id, 'block_url_permenant', true );
				if ( ( isset( $is_active_a ) && '' == $is_active_a ) || $own_block_msg_permenant == $old_block_msg_permenant || $own_block_url_permenant == $old_block_url_permenant ) {
					// Not update already date wise blocked users.
					update_user_meta( $u_id, 'is_active', $is_active );
					update_user_meta( $u_id, 'block_msg_permenant', $block_msg_permenant );
					update_user_meta( $u_id, 'block_url_permenant', $block_url_permenant );
				}
			}
		}
	}
}

if ( ! function_exists( 'ublk_sort_by_member_number' ) ) {

	/**
	 * Sort By Member number.
	 *
	 * @param object $vars Variables.
	 */
	function ublk_sort_by_member_number( $vars ) {
		$vars->query_orderby = 'group by user_login ' . $vars->query_orderby;
	}
}

if ( ! function_exists( 'ublk_all_block_data_view' ) ) {
	/**
	 * View of Block Data.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_all_block_data_view( $user_id ) {
		$is_active = get_user_meta( $user_id, 'is_active', true );
		if ( 'n' == $is_active ) {
			?>
			<img src="<?php echo esc_url( UB_PLUGIN_URL . '/images/inactive.png' ); ?>" title="<?php esc_html_e( 'Permanently Blocked', 'user-blocker' ); ?>" />
			<?php
		} else {
			?>
			<a data-href='<?php echo esc_url( $user_id ); ?>' href='' class="view_block_data">
				<img src="<?php echo esc_url( UB_PLUGIN_URL . '/images/view.png' ); ?>" title="<?php esc_html_e( 'View Block Date Time', 'user-blocker' ); ?>" />
			</a>
			<?php
		}
	}
}

if ( ! function_exists( 'ublk_all_block_data_view_role' ) ) {
	/**
	 * View Role of Block Data.
	 *
	 * @param string $key Key.
	 */
	function ublk_all_block_data_view_role( $key ) {
		$is_active  = get_option( $key . '_is_active' );
		$block_day  = get_option( $key . '_block_day' );
		$block_date = get_option( $key . '_block_date' );
		if ( 'n' == $is_active ) {
			?>
			<img src="<?php echo esc_url( UB_PLUGIN_URL . '/images/inactive.png' ); ?>" title="<?php esc_html_e( 'Permanently Blocked', 'user-blocker' ); ?>" />
			<?php
		} else {
			?>
			<a href='' class="view_block_data">
				<img src="<?php echo esc_url( UB_PLUGIN_URL . '/images/view.png' ); ?>" title="<?php esc_html_e( 'View Block Date Time', 'user-blocker' ); ?>" />
			</a>
			<?php
		}
	}
}

if ( ! function_exists( 'ublk_all_block_data_table' ) ) {
	/**
	 * Table of Block Data.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_all_block_data_table( $user_id ) {
		$is_active  = get_user_meta( $user_id, 'is_active', true );
		$block_day  = get_user_meta( $user_id, 'block_day', true );
		$block_date = get_user_meta( $user_id, 'block_date', true );
		if ( 'n' != $is_active ) {
			?>
			<tr id='view_block_day_tr_<?php echo esc_attr( $user_id ); ?>' class="view_block_data_tr">
				<td colspan="8" class='date_detail_row'>
					<table class="view_block_table form-table tbl-timing">
						<tbody>
							<?php
							if ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
								?>
								<tr>
									<td colspan='7'>
										<label><?php esc_html_e( 'Blocked Detail', 'user-blocker' ); ?></label>
									</td>
								</tr>
								<tr>
									<th align="center"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
								</tr>
								<tr>
									<td align="center"><?php ublk_get_time_record( 'sunday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'monday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'tuesday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'wednesday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'thursday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'friday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'saturday', $block_day ); ?></td>
								</tr>
								<?php
							}
							if ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
								?>
								<tr>
									<td class="" colspan='7'>
										<label><?php esc_html_e( 'Blocked Date Detail:', 'user-blocker' ); ?></label>
										<?php echo esc_html( ublk_date_time_to_twelve_hour( $block_date['frmdate'] ) ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( ublk_date_time_to_twelve_hour( $block_date['todate'] ) ); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}
	}
}

if ( ! function_exists( 'ublk_all_block_data_table_role' ) ) {
	/**
	 * Role Table of Block Data.
	 *
	 * @param string $key Key.
	 */
	function ublk_all_block_data_table_role( $key ) {
		$is_active  = get_option( $key . '_is_active' );
		$block_day  = get_option( $key . '_block_day' );
		$block_date = get_option( $key . '_block_date' );
		if ( 'n' != $is_active ) {
			?>
			<tr id='view_block_day_tr_<?php echo esc_attr( $key ); ?>' class="view_block_data_tr">
				<td colspan="3" class='date_detail_row'>
					<table class="view_block_table form-table tbl-timing">
						<tbody>
							<?php
							if ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
								?>
								<tr>
									<td colspan='7'>
										<label><?php esc_html_e( 'Blocked Detail', 'user-blocker' ); ?></label>
									</td>
								</tr>
								<tr>
									<th align="center"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
									<th align="center"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
								</tr>
								<tr>
									<td align="center"><?php ublk_get_time_record( 'sunday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'monday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'tuesday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'wednesday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'thursday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'friday', $block_day ); ?></td>
									<td align="center"><?php ublk_get_time_record( 'saturday', $block_day ); ?></td>
								</tr>
								<?php
							}
							if ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
								?>
								<tr>
									<td class="" colspan='7'>
										<label><?php esc_html_e( 'Blocked Date Detail:', 'user-blocker' ); ?></label>
										<?php echo esc_html( ublk_date_time_to_twelve_hour( $block_date['frmdate'] ) ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( ublk_date_time_to_twelve_hour( $block_date['todate'] ) ); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
				</td>
			</tr>
			<?php
		}
	}
}

if ( ! function_exists( 'ublk_all_block_data_msg' ) ) {
	/**
	 * Message of Block Data.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_all_block_data_msg( $user_id ) {
		$is_active  = get_user_meta( $user_id, 'is_active', true );
		$block_day  = get_user_meta( $user_id, 'block_day', true );
		$block_date = get_user_meta( $user_id, 'block_date', true );
		if ( 'n' == $is_active ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_msg_permenant', true ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_msg_day', true ) . ' And ' . get_user_meta( $user_id, 'block_msg_date', true ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_msg_day', true ) ) );
		} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_msg_date', true ) ) );
		}
	}
}
if ( ! function_exists( 'ublk_all_block_data_url' ) ) {
	/**
	 * URL of Block Data.
	 *
	 * @param int $user_id User ID.
	 */
	function ublk_all_block_data_url( $user_id ) {
		$is_active  = get_user_meta( $user_id, 'is_active', true );
		$block_day  = get_user_meta( $user_id, 'block_day', true );
		$block_date = get_user_meta( $user_id, 'block_date', true );
		if ( 'n' == $is_active ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_url_permenant', true ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date && ! empty( get_user_meta( $user_id, 'block_url_day', true ) ) && ! empty( get_user_meta( $user_id, 'block_url_date', true ) ) ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_url_day', true ) . ' And ' . get_user_meta( $user_id, 'block_url_date', true ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_url_day', true ) ) );
		} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_user_meta( $user_id, 'block_url_date', true ) ) );
		}
	}
}

if ( ! function_exists( 'ublk_all_block_data_msg_role' ) ) {
	/**
	 * Role Message of Block Data.
	 *
	 * @param string $key Key.
	 */
	function ublk_all_block_data_msg_role( $key ) {
		$is_active  = get_option( $key . '_is_active' );
		$block_day  = get_option( $key . '_block_day' );
		$block_date = get_option( $key . '_block_date' );
		if ( 'n' == $is_active ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_permenant' ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {

			echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_day' ) . ' And ' . get_option( $key . '_block_msg_date' ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_day' ) ) );
		} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_date' ) ) );
		}
	}
}
if ( ! function_exists( 'ublk_all_block_data_url_role' ) ) {

	/**
	 * Role wise URL of Block Data.
	 *
	 * @param string $key Key.
	 */
	function ublk_all_block_data_url_role( $key ) {
		$is_active  = get_option( $key . '_is_active' );
		$block_day  = get_option( $key . '_block_day' );
		$block_date = get_option( $key . '_block_date' );
		if ( 'n' == $is_active ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_permenant' ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_day' ) . ' And ' . get_option( $key . '_block_url_date' ) ) );
		} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_day' ) ) );
		} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
			echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_date' ) ) );
		}
	}
}
if ( ! function_exists( 'ublk_get_data' ) ) {
	/**
	 * Get Data.
	 *
	 * @param string $data Data.
	 * @param string $default_val Default Value.
	 */
	function ublk_get_data( $data, $default_val = '' ) {
		$return_val = '';
		if ( '' != $data ) {
			if ( isset( $_GET[ $data ] ) && '' != $_GET[ $data ] ) {
				$return_val = sanitize_text_field( wp_unslash( $_GET[ $data ] ) );
			} else {
				$return_val = $default_val;
			}
		}
		return $return_val;
	}
}

if ( ! function_exists( 'ublk_wp_member_plugin_login_failed_sb_msg' ) ) {
	/**
	 * WP-MEMBERS plugin change login filed message for widget login.
	 *
	 * @param string $str String.
	 */
	function ublk_wp_member_plugin_login_failed_sb_msg( $str ) {
		$nonce = ( isset( $_POST['_wpmem_login_nonce'] ) && ! empty( $_POST['_wpmem_login_nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wpmem_login_nonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'wpmem_shortform_nonce' ) ) {
			if ( isset( $_POST['log'] ) && isset( $_POST['pwd'] ) ) {
				// Get username and sanitize.
				$user_login = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
				// Assemble login credentials.
				$creds                  = array();
				$creds['user_login']    = $user_login;
				$creds['user_password'] = isset( $_POST['pwd'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) ) : '';
				$user                   = wp_signon( $creds, is_ssl() );
				if ( isset( $user->errors['invalid_username'][0] ) ) {
					return $str;
				}
				if ( isset( $user->errors['authentication_failed'][0] ) ) {
					$logfail = esc_html__( 'Login Failed!', 'user-blocker' );
					$str     = "<p class='err'>$logfail<br>" . $user->errors['authentication_failed'][0] . '</p>';
					return $str;
				}
			}
		}
		return $str;
	}
}
add_filter( 'wpmem_login_failed_sb', 'ublk_wp_member_plugin_login_failed_sb_msg' );

if ( ! function_exists( 'ublk_wp_member_plugin_login_failed_msg' ) ) {
	/**
	 * WP-MEMBERS plugin change login failed message for login shortcode.
	 *
	 * @param string $str String.
	 */
	function ublk_wp_member_plugin_login_failed_msg( $str ) {
		$nonce = ( isset( $_POST['_wpmem_login_nonce'] ) && ! empty( $_POST['_wpmem_login_nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wpmem_login_nonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'wpmem_shortform_nonce' ) ) {
			if ( isset( $_POST['log'] ) && isset( $_POST['pwd'] ) ) {
				// Get username and sanitize.
				$user_login = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '';
				// Assemble login credentials.
				$creds                  = array();
				$creds['user_login']    = $user_login;
				$creds['user_password'] = isset( $_POST['pwd'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['pwd'] ) ) ) : '';
				$user                   = wp_signon( $creds, is_ssl() );
				if ( isset( $user->errors['invalid_username'][0] ) ) {
					return $str;
				}
				if ( isset( $user->errors['authentication_failed'][0] ) ) {
					$logfail = esc_html__( 'Login Failed!', 'user-blocker' );
					$str     = "<div align='center' id='wpmem_msg'>
								<h2>$logfail</h2>
								<p>" . $user->errors['authentication_failed'][0] . '</p>
							</div>';
					return $str;
				}
			}
		}
		return $str;
	}
}
add_filter( 'wpmem_login_failed', 'ublk_wp_member_plugin_login_failed_msg' );

if ( ! function_exists( 'ublk_plugin_links' ) ) {
	/**
	 * Display links.
	 *
	 * @param string $links Links.
	 */
	function ublk_plugin_links( $links ) {
		$links[] = '<a class="documentation_ublk_plugin" target="_blank" href="' . esc_url( 'https://www.solwininfotech.com/documents/wordpress/user-blocker/' ) . '">' . esc_html__( 'Documentation', 'user-blocker' ) . '</a>';
		return $links;
	}
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ublk_plugin_links' );


if ( ! function_exists( 'ublk_load_text_domain' ) ) {
	/**
	 * Load the Text Domain.
	 */
	function ublk_load_text_domain() {

		if ( in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {
			unload_textdomain( 'default' );
		}

		load_plugin_textdomain( 'user-blocker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

add_action( 'wp_ajax_ublk_submit_optin', 'ublk_submit_optin' );
if ( ! function_exists( 'ublk_submit_optin' ) ) {
	/**
	 * Submit option data.
	 */
	function ublk_submit_optin() {
		global $wpdb, $wp_version;
		$ublk_submit_type = '';
		$nonce            = ( isset( $_POST['nonce'] ) && ! empty( $_POST['nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( isset( $_POST['email'] ) && wp_verify_nonce( $nonce, 'on_ublk_submit_optin_nonce' ) ) {
			$ublk_email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		} else {
			$ublk_email = get_option( 'admin_url' );
		}
		if ( isset( $_POST['type'] ) && wp_verify_nonce( $nonce, 'on_ublk_submit_optin_nonce' ) ) {
			$ublk_submit_type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		}
		if ( 'submit' == $ublk_submit_type ) {
			$status_type   = get_option( 'ublk_is_optin' );
			$theme_details = array();
			if ( $wp_version >= 3.4 ) {
				$active_theme                   = wp_get_theme();
				$theme_details['theme_name']    = wp_strip_all_tags( $active_theme->name );
				$theme_details['theme_version'] = wp_strip_all_tags( $active_theme->version );
				$theme_details['author_url']    = wp_strip_all_tags( $active_theme->{'Author URI'} );
			}
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
			$plugins = array();
			if ( count( $active_plugins ) > 0 ) {
				$get_plugins = array();
				foreach ( $active_plugins as $plugin ) {
					$plugin_data = get_plugin_data( UB_PLUGIN_DIR . '/' . $plugin );

					$get_plugins['plugin_name']    = wp_strip_all_tags( $plugin_data['Name'] );
					$get_plugins['plugin_author']  = wp_strip_all_tags( $plugin_data['Author'] );
					$get_plugins['plugin_version'] = wp_strip_all_tags( $plugin_data['Version'] );
					array_push( $plugins, $get_plugins );
				}
			}

			$plugin_data     = get_plugin_data( UB_PLUGIN_DIR . '/user_blocker.php', $markup = true, $translate = true );
			$current_version = $plugin_data['Version'];

			$plugin_data                           = array();
			$plugin_data['plugin_name']            = 'User Blocker';
			$plugin_data['plugin_slug']            = 'user-blocker';
			$plugin_data['plugin_version']         = $current_version;
			$plugin_data['plugin_status']          = $status_type;
			$plugin_data['site_url']               = home_url();
			$plugin_data['site_language']          = defined( 'WPLANG' ) && WPLANG ? WPLANG : get_locale();
			$current_user                          = wp_get_current_user();
			$f_name                                = $current_user->user_firstname;
			$l_name                                = $current_user->user_lastname;
			$plugin_data['site_user_name']         = esc_attr( $f_name ) . ' ' . esc_attr( $l_name );
			$plugin_data['site_email']             = false !== $ublk_email ? esc_attr( $ublk_email ) : get_option( 'admin_email' );
			$plugin_data['site_wordpress_version'] = $wp_version;
			$plugin_data['site_php_version']       = esc_attr( phpversion() );
			$plugin_data['site_mysql_version']     = $wpdb->db_version();
			$plugin_data['site_max_input_vars']    = ini_get( 'max_input_vars' );
			$plugin_data['site_php_memory_limit']  = ini_get( 'max_input_vars' );
			$plugin_data['site_operating_system']  = ini_get( 'memory_limit' ) ? ini_get( 'memory_limit' ) : 'N/A';
			$plugin_data['site_extensions']        = get_loaded_extensions();
			$plugin_data['site_activated_plugins'] = $plugins;
			$plugin_data['site_activated_theme']   = $theme_details;
			$url                                   = 'https://www.solwininfotech.com/analytics/';
			$response                              = wp_safe_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'data'   => maybe_serialize( $plugin_data ),
						'action' => 'plugin_analysis_data',
					),
				)
			);
			update_option( 'ublk_is_optin', 'yes' );
		} elseif ( 'cancel' == $ublk_submit_type ) {
			update_option( 'ublk_is_optin', 'no' );
		} elseif ( 'deactivate' == $ublk_submit_type ) {
			$status_type   = get_option( 'ublk_is_optin' );
			$theme_details = array();
			if ( $wp_version >= 3.4 ) {
				$active_theme                   = wp_get_theme();
				$theme_details['theme_name']    = wp_strip_all_tags( $active_theme->name );
				$theme_details['theme_version'] = wp_strip_all_tags( $active_theme->version );
				$theme_details['author_url']    = wp_strip_all_tags( $active_theme->{'Author URI'} );
			}
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
			$plugins = array();
			if ( count( $active_plugins ) > 0 ) {
				$get_plugins = array();
				foreach ( $active_plugins as $plugin ) {
					$plugin_data                   = get_plugin_data( UB_PLUGIN_DIR . '/' . $plugin );
					$get_plugins['plugin_name']    = wp_strip_all_tags( $plugin_data['Name'] );
					$get_plugins['plugin_author']  = wp_strip_all_tags( $plugin_data['Author'] );
					$get_plugins['plugin_version'] = wp_strip_all_tags( $plugin_data['Version'] );
					array_push( $plugins, $get_plugins );
				}
			}

			$plugin_data     = get_plugin_data( UB_PLUGIN_DIR . '/user_blocker.php', $markup = true, $translate = true );
			$current_version = $plugin_data['Version'];

			$plugin_data                             = array();
			$plugin_data['plugin_name']              = 'User Blocker';
			$plugin_data['plugin_slug']              = 'user-blocker';
			$reason_id                               = isset( $_POST['selected_option_de'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_option_de'] ) ) : '';
			$plugin_data['deactivation_option']      = $reason_id;
			$plugin_data['deactivation_option_text'] = isset( $_POST['selected_option_de_text'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_option_de_text'] ) ) : '';
			if ( 7 == $reason_id ) {
				$plugin_data['deactivation_option_text'] = isset( $_POST['selected_option_de_other'] ) ? sanitize_text_field( wp_unslash( $_POST['selected_option_de_other'] ) ) : '';
			}
			$plugin_data['plugin_version']         = $current_version;
			$plugin_data['plugin_status']          = $status_type;
			$plugin_data['site_url']               = home_url();
			$plugin_data['site_language']          = defined( 'WPLANG' ) && WPLANG ? WPLANG : get_locale();
			$current_user                          = wp_get_current_user();
			$f_name                                = $current_user->user_firstname;
			$l_name                                = $current_user->user_lastname;
			$plugin_data['site_user_name']         = esc_attr( $f_name ) . ' ' . esc_attr( $l_name );
			$plugin_data['site_email']             = false !== $ublk_email ? esc_attr( $ublk_email ) : get_option( 'admin_email' );
			$plugin_data['site_wordpress_version'] = $wp_version;
			$plugin_data['site_php_version']       = esc_attr( phpversion() );
			$plugin_data['site_mysql_version']     = $wpdb->db_version();
			$plugin_data['site_max_input_vars']    = ini_get( 'max_input_vars' );
			$plugin_data['site_php_memory_limit']  = ini_get( 'max_input_vars' );
			$plugin_data['site_operating_system']  = ini_get( 'memory_limit' ) ? ini_get( 'memory_limit' ) : 'N/A';
			$plugin_data['site_extensions']        = get_loaded_extensions();
			$plugin_data['site_activated_plugins'] = $plugins;
			$plugin_data['site_activated_theme']   = $theme_details;
			$url                                   = 'https://www.solwininfotech.com/analytics/';
			$response                              = wp_safe_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => array(
						'data'   => maybe_serialize( $plugin_data ),
						'action' => 'plugin_analysis_data_deactivate',
					),
				)
			);
			update_option( 'ublk_is_optin', '' );
		}
		exit();
	}
}

add_action( 'activated_plugin', 'ublk_activated_plugin' );
if ( ! function_exists( 'ublk_activated_plugin' ) ) {
	/**
	 * Redirection on welcome page.
	 *
	 * @param string $plugin Plugin.
	 */
	function ublk_activated_plugin( $plugin ) {
		if ( plugin_basename( __FILE__ ) == $plugin ) {
			$ublk_is_optin = get_option( 'ublk_is_optin' );
			if ( 'yes' == $ublk_is_optin || 'no' == $ublk_is_optin ) {
				wp_redirect( admin_url( 'admin.php?page=block_user' ) );
				exit();
			} else {
				// wp_redirect( admin_url( 'admin.php?page=welcome_block_user' ) );
			}
		}
	}
}

/**
 * Delete optin on deactivation of plugin
 */
register_deactivation_hook( __FILE__, 'ublk_update_optin' );
if ( ! function_exists( 'ublk_update_optin' ) ) {
	/**
	 * Update Option.
	 */
	function ublk_update_optin() {
		update_option( 'ublk_is_optin', '' );
	}
}

/**
 * Sanitize array recursive.
 *
 * @param array $array array to be sanitized.
 * @return array $array Sanitized array.
 */
function ublk_recursive_sanitize_text_field( $array ) {
	foreach ( $array as $key => &$value ) {
		if ( is_array( $value ) ) {
			$value = ublk_recursive_sanitize_text_field( $value );
		} else {
			$value = sanitize_text_field( wp_unslash( $value ) );
		}
	}
	return $array;
}
