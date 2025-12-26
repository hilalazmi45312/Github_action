<?php
/**
 * Block User.
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

if ( ! function_exists( 'ublk_block_user_page' ) ) {
	/**
	 * Block User Page.
	 */
	function ublk_block_user_page() {
		global $wpdb;
		global $wp_roles;
		$orderby          = 'user_login';
		$order            = 'ASC';
		$btn_val          = esc_html__( 'Block User', 'user-blocker' );
		$default_msg      = esc_html__( 'You are temporary blocked.', 'user-blocker' );
		$total_pages      = '';
		$next_page        = '';
		$prev_page        = '';
		$srole            = '';
		$role             = '';
		$block_msg_day    = '';
		$block_url_day    = '';
		$cmb_user_by      = '';
		$block_msg        = '';
		$username         = '';
		$search_arg       = '';
		$msg_class        = '';
		$msg              = '';
		$is_display_role  = 0;
		$display_users    = 1;
		$sr_no            = 1;
		$paged            = 1;
		$records_per_page = 10;
		$option_name      = array();
		$block_time_array = array();
		$reocrd_id        = array();

		$txt_sun_from = '';
		$txt_sun_to   = '';
		$txt_mon_from = '';
		$txt_mon_to   = '';
		$txt_tue_from = '';
		$txt_tue_to   = '';
		$txt_wed_from = '';
		$txt_wed_to   = '';
		$txt_thu_from = '';
		$txt_thu_to   = '';
		$txt_fri_from = '';
		$txt_fri_to   = '';
		$txt_sat_from = '';
		$txt_sat_to   = '';

		if ( '' != ublk_get_data( 'paged' ) ) {
			$display_users = 1;
			$paged         = ublk_get_data( 'paged', 1 );
		}
		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$orderby = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $orderby;
		$order   = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : $order;

		$offset    = ( $paged - 1 ) * $records_per_page;
		$get_roles = $wp_roles->roles;

		$get_role     = ublk_get_data( 'role' );
		$get_username = ublk_get_data( 'username' );

		if ( '' != $get_role ) {
			$reocrd_id       = array( $get_role );
			$reocrd_id       = ublk_recursive_sanitize_text_field( $reocrd_id );
			$role            = $get_role;
			$btn_name        = 'editTime';
			$btn_val         = esc_html__( 'Update Blocked User', 'user-blocker' );
			$is_display_role = 1;
			$user_roles      = explode( ',', $get_role );
			$multi_users     = get_users( array( 'role__in' => $user_roles ) );
			$user_datas      = '';
			foreach ( $user_roles as $user_role ) {
				if ( $GLOBALS['wp_roles']->is_role( $user_role ) ) {
					$time_detail = get_option( $user_role . '_block_day' );
					if ( '' != $time_detail ) {
						if ( array_key_exists( 'sunday', $time_detail ) ) {
							$txt_sun_from = $time_detail['sunday']['from'];
							$txt_sun_to   = $time_detail['sunday']['to'];
						}
						if ( array_key_exists( 'monday', $time_detail ) ) {
							$txt_mon_from = $time_detail['monday']['from'];
							$txt_mon_to   = $time_detail['monday']['to'];
						}
						if ( array_key_exists( 'tuesday', $time_detail ) ) {
							$txt_tue_from = $time_detail['tuesday']['from'];
							$txt_tue_to   = $time_detail['tuesday']['to'];
						}
						if ( array_key_exists( 'wednesday', $time_detail ) ) {
							$txt_wed_from = $time_detail['wednesday']['from'];
							$txt_wed_to   = $time_detail['wednesday']['to'];
						}
						if ( array_key_exists( 'thursday', $time_detail ) ) {
							$txt_thu_from = $time_detail['thursday']['from'];
							$txt_thu_to   = $time_detail['thursday']['to'];
						}
						if ( array_key_exists( 'friday', $time_detail ) ) {
							$txt_fri_from = $time_detail['friday']['from'];
							$txt_fri_to   = $time_detail['friday']['to'];
						}
						if ( array_key_exists( 'saturday', $time_detail ) ) {
							$txt_sat_from = $time_detail['saturday']['from'];
							$txt_sat_to   = $time_detail['saturday']['to'];
						}
					}
					$block_msg_day = get_option( $user_role . '_block_msg_day' );
					$block_url_day = get_option( $user_role . '_block_url_day' );
					$user_datas   .= ', ' . $GLOBALS['wp_roles']->roles[ $user_role ]['name'];
					$user_role     = ltrim( $user_datas, ', ' );
					$curr_edit_msg = esc_html__( 'Update for role', 'user-blocker' ) . ': ' . $user_role;
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Role', 'user-blocker' ) . ' ' . $user_role . ' ' . esc_html__( 'is not exist.', 'user-blocker' );
				}
			}
		}
		if ( '' != $get_username ) {
			$reocrd_id   = array( $get_username );
			$reocrd_id   = ublk_recursive_sanitize_text_field( $reocrd_id );
			$username    = $get_username;
			$btn_name    = 'editTime';
			$btn_val     = esc_html__( 'Update Blocked User', 'user-blocker' );
			$user_ids    = explode( ',', $get_username );
			$multi_users = get_users( array( 'include' => $user_ids ) );
			$user_datas  = '';
			foreach ( $user_ids as $user_id ) {
				if ( false != $multi_users ) {
					$time_detail = get_user_meta( $user_id, 'block_day', true );
					if ( '' != $time_detail ) {
						if ( array_key_exists( 'sunday', $time_detail ) ) {
							$txt_sun_from = $time_detail['sunday']['from'];
							$txt_sun_to   = $time_detail['sunday']['to'];
						}
						if ( array_key_exists( 'monday', $time_detail ) ) {
							$txt_mon_from = $time_detail['monday']['from'];
							$txt_mon_to   = $time_detail['monday']['to'];
						}
						if ( array_key_exists( 'tuesday', $time_detail ) ) {
							$txt_tue_from = $time_detail['tuesday']['from'];
							$txt_tue_to   = $time_detail['tuesday']['to'];
						}
						if ( array_key_exists( 'wednesday', $time_detail ) ) {
							$txt_wed_from = $time_detail['wednesday']['from'];
							$txt_wed_to   = $time_detail['wednesday']['to'];
						}
						if ( array_key_exists( 'thursday', $time_detail ) ) {
							$txt_thu_from = $time_detail['thursday']['from'];
							$txt_thu_to   = $time_detail['thursday']['to'];
						}
						if ( array_key_exists( 'friday', $time_detail ) ) {
							$txt_fri_from = $time_detail['friday']['from'];
							$txt_fri_to   = $time_detail['friday']['to'];
						}
						if ( array_key_exists( 'saturday', $time_detail ) ) {
							$txt_sat_from = $time_detail['saturday']['from'];
							$txt_sat_to   = $time_detail['saturday']['to'];
						}
						if ( array_key_exists( 'block_msg', $time_detail ) ) {
							$block_msg = $time_detail['block_msg'];
						}
					}
					$block_msg_day = get_user_meta( $user_id, 'block_msg_day', true );
					$block_url_day = get_user_meta( $user_id, 'block_url_day', true );
					$user_data     = new WP_User( $user_id );
					$user_datas   .= ', ' . $user_data->user_login;
					$curr_edit_msg = esc_html__( 'Update for user with username: ', 'user-blocker' ) . ltrim( $user_datas, ', ' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'User with ', 'user-blocker' ) . $user_id . esc_html__( ' userid is not exist.', 'user-blocker' );
				}
			}
		}
		if ( isset( $_POST['sbtSaveTime'] ) && isset( $_POST['_wp_block_by_time_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_block_by_time_nonce'] ) ), '_wp_block_by_time_action' ) ) {
			// Check if username is selected in dd.
			$get_username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
			$get_role     = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
			if ( isset( $_POST['cmbUserBy'] ) && 'role' == $_POST['cmbUserBy'] ) {
				$is_display_role = 1;
			}
			if ( isset( $_POST['cmbUserBy'] ) && 'username' == $_POST['cmbUserBy'] ) {
				$display_users = 1;
			}
			$txt_sun_from  = isset( $_POST['txtSunFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtSunFrom'] ) ) : '';
			$txt_sun_to    = isset( $_POST['txtSunTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtSunTo'] ) ) : '';
			$txt_mon_from  = isset( $_POST['txtMonFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtMonFrom'] ) ) : '';
			$txt_mon_to    = isset( $_POST['txtMonTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtMonTo'] ) ) : '';
			$txt_tue_from  = isset( $_POST['txtTueFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtTueFrom'] ) ) : '';
			$txt_tue_to    = isset( $_POST['txtTueTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtTueTo'] ) ) : '';
			$txt_wed_from  = isset( $_POST['txtWedFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtWedFrom'] ) ) : '';
			$txt_wed_to    = isset( $_POST['txtWedTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtWedTo'] ) ) : '';
			$txt_thu_from  = isset( $_POST['txtThuFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtThuFrom'] ) ) : '';
			$txt_thu_to    = isset( $_POST['txtThuTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtThuTo'] ) ) : '';
			$txt_fri_from  = isset( $_POST['txtFriFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtFriFrom'] ) ) : '';
			$txt_fri_to    = isset( $_POST['txtFriTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtFriTo'] ) ) : '';
			$txt_sat_from  = isset( $_POST['txtSatFrom'] ) ? sanitize_text_field( wp_unslash( $_POST['txtSatFrom'] ) ) : '';
			$txt_sat_to    = isset( $_POST['txtSatTo'] ) ? sanitize_text_field( wp_unslash( $_POST['txtSatTo'] ) ) : '';
			$block_msg_day = isset( $_POST['block_msg_day'] ) ? sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) ) : '';
			$block_url_day = isset( $_POST['block_url_day'] ) ? sanitize_url( wp_unslash( $_POST['block_url_day'] ) ) : '';
			if ( '' != $txt_sun_from || '' != $txt_mon_from || '' != $txt_tue_from || '' != $txt_wed_from || '' != $txt_thu_from || '' != $txt_fri_from || '' != $txt_sat_from ) {
				// validate time.
				$invalid_time = 1;
				if ( '' != $txt_sun_from ) {
					$invalid_time = ublk_validate_time( $txt_sun_from );
					if ( 0 == $invalid_time ) {
						$txt_sun_from = '';
					}
				}
				if ( '' != $txt_sun_to ) {
					$invalid_time = ublk_validate_time( $txt_sun_to );
					if ( 0 == $invalid_time ) {
						$txt_sun_to = '';
					}
				}
				if ( '' != $txt_mon_from ) {
					$invalid_time = ublk_validate_time( $txt_mon_from );
					if ( 0 == $invalid_time ) {
						$txt_mon_from = '';
					}
				}
				if ( '' != $txt_mon_to ) {
					$invalid_time = ublk_validate_time( $txt_mon_to );
					if ( 0 == $invalid_time ) {
						$txt_mon_to = '';
					}
				}
				if ( '' != $txt_tue_from ) {
					$invalid_time = ublk_validate_time( $txt_tue_from );
					if ( 0 == $invalid_time ) {
						$txt_tue_from = '';
					}
				}
				if ( '' != $txt_tue_to ) {
					$invalid_time = ublk_validate_time( $txt_tue_to );
					if ( 0 == $invalid_time ) {
						$txt_tue_to = '';
					}
				}
				if ( '' != $txt_wed_from ) {
					$invalid_time = ublk_validate_time( $txt_wed_from );
					if ( 0 == $invalid_time ) {
						$txt_wed_from = '';
					}
				}
				if ( '' != $txt_wed_to ) {
					$invalid_time = ublk_validate_time( $txt_wed_to );
					if ( 0 == $invalid_time ) {
						$txt_wed_to = '';
					}
				}
				if ( '' != $txt_thu_from ) {
					$invalid_time = ublk_validate_time( $txt_thu_from );
					if ( 0 == $invalid_time ) {
						$txt_thu_from = '';
					}
				}
				if ( '' != $txt_thu_to ) {
					$invalid_time = ublk_validate_time( $txt_thu_to );
					if ( 0 == $invalid_time ) {
						$txt_thu_to = '';
					}
				}
				if ( '' != $txt_fri_from ) {
					$invalid_time = ublk_validate_time( $txt_fri_from );
					if ( 0 == $invalid_time ) {
						$txt_fri_from = '';
					}
				}
				if ( '' != $txt_fri_to ) {
					$invalid_time = ublk_validate_time( $txt_fri_to );
					if ( 0 == $invalid_time ) {
						$txt_fri_to = '';
					}
				}
				if ( '' != $txt_sat_from ) {
					$invalid_time = ublk_validate_time( $txt_sat_from );
					if ( 0 == $invalid_time ) {
						$txt_sat_from = '';
					}
				}
				if ( '' != $txt_sat_to ) {
					$invalid_time = ublk_validate_time( $txt_sat_to );
					if ( 0 == $invalid_time ) {
						$txt_sat_to = '';
					}
				}
				if ( 1 == $invalid_time ) {
					$add_time     = 1;
					$txt_sun_from = ublk_time_to_twenty_four_hour( $txt_sun_from );
					$txt_sun_to   = ublk_time_to_twenty_four_hour( $txt_sun_to );
					$txt_mon_from = ublk_time_to_twenty_four_hour( $txt_mon_from );
					$txt_mon_to   = ublk_time_to_twenty_four_hour( $txt_mon_to );
					$txt_tue_from = ublk_time_to_twenty_four_hour( $txt_tue_from );
					$txt_tue_to   = ublk_time_to_twenty_four_hour( $txt_tue_to );
					$txt_wed_from = ublk_time_to_twenty_four_hour( $txt_wed_from );
					$txt_wed_to   = ublk_time_to_twenty_four_hour( $txt_wed_to );
					$txt_thu_from = ublk_time_to_twenty_four_hour( $txt_thu_from );
					$txt_thu_to   = ublk_time_to_twenty_four_hour( $txt_thu_to );
					$txt_fri_from = ublk_time_to_twenty_four_hour( $txt_fri_from );
					$txt_fri_to   = ublk_time_to_twenty_four_hour( $txt_fri_to );
					$txt_sat_from = ublk_time_to_twenty_four_hour( $txt_sat_from );
					$txt_sat_to   = ublk_time_to_twenty_four_hour( $txt_sat_to );
					// Check if start time is set for end time.
					if ( '' != $txt_sun_to && '' == $txt_sun_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_mon_to && '' == $txt_mon_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_tue_to && '' == $txt_tue_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_wed_to && '' == $txt_wed_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_thu_to && '' == $txt_thu_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_fri_to && '' == $txt_fri_from ) {
						$add_time = 0;
					}
					if ( '' != $txt_sat_to && '' == $txt_sat_from ) {
						$add_time = 0;
					}
					if ( isset( $add_time ) && 1 == $add_time ) {
						$block_time_array['sunday']    = array(
							'from' => sanitize_text_field( $txt_sun_from ),
							'to'   => sanitize_text_field( $txt_sun_to ),
						);
						$block_time_array['monday']    = array(
							'from' => sanitize_text_field( $txt_mon_from ),
							'to'   => sanitize_text_field( $txt_mon_to ),
						);
						$block_time_array['tuesday']   = array(
							'from' => sanitize_text_field( $txt_tue_from ),
							'to'   => sanitize_text_field( $txt_tue_to ),
						);
						$block_time_array['wednesday'] = array(
							'from' => sanitize_text_field( $txt_wed_from ),
							'to'   => sanitize_text_field( $txt_wed_to ),
						);
						$block_time_array['thursday']  = array(
							'from' => sanitize_text_field( $txt_thu_from ),
							'to'   => sanitize_text_field( $txt_thu_to ),
						);
						$block_time_array['friday']    = array(
							'from' => sanitize_text_field( $txt_fri_from ),
							'to'   => sanitize_text_field( $txt_fri_to ),
						);
						$block_time_array['saturday']  = array(
							'from' => sanitize_text_field( $txt_sat_from ),
							'to'   => sanitize_text_field( $txt_sat_to ),
						);
						if ( ( '' != $get_role ) || ( '' != $get_username ) ) {
							// get Blocking Time.
							$user_ids          = explode( ',', $get_username );
							$user_datas        = '';
							$multi_users       = get_users( array( 'include' => $user_ids ) );
							$user_roles        = explode( ',', $get_role );
							$multi_users_roles = get_users( array( 'role__in' => $user_roles ) );
							if ( ( '' != $get_role ) || ( '' != $get_username && false != $multi_users ) ) {
								if ( '' != $get_role ) {
									global $wpdb;
									$pattern = '/[\-=+$@\t\r]/';
									foreach ( $user_roles as $multi_users_role ) {
										$old_block_day     = get_option( $multi_users_role . '_block_day' );
										$old_block_msg_day = get_option( $multi_users_role . '_block_msg_day' );
										$old_block_url_day = get_option( $multi_users_role . '_block_url_day' );
										if ( preg_match( $pattern, $block_msg_day ) ) {
											$msg_class = 'error';
											$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
										} else {
											update_option( $multi_users_role . '_block_day', $block_time_array );
											$block_msg_day = $default_msg;
											$block_url_day = '';
											if ( isset( $_POST['block_msg_day'] ) && '' != $_POST['block_msg_day'] ) {
												$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
											}
											if ( isset( $_POST['block_url_day'] ) && '' != $_POST['block_url_day'] ) {
												$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
											}
											update_option( $multi_users_role . '_block_msg_day', $block_msg_day );
											update_option( $multi_users_role . '_block_url_day', $block_url_day );
											$role_name = str_replace( '_', ' ', $multi_users_role );
											// Update all users of this role.
											ublk_block_role_users_day( $multi_users_role, $old_block_day, $block_time_array, $old_block_msg_day, $block_msg_day, $old_block_url_day, $block_url_day );
											// Update all users of this role end.
											$msg_class        = 'updated';
											$user_datas      .= ', ' . $GLOBALS['wp_roles']->roles[ $multi_users_role ]['name'];
											$multi_users_role = ltrim( $user_datas, ', ' );
											$msg              = esc_html__( 'Blocking time for ', 'user-blocker' ) . $multi_users_role . esc_html__( ' is successfully updated.', 'user-blocker' );
											$roles_data       = $wpdb->get_results( $wpdb->prepare( "SELECT user_id from $wpdb->usermeta WHERE meta_key = 'wp_capabilities' AND meta_value = 'a:1:{s: %d : %s \";b:1;}'", strlen( $multi_users_role ), $multi_users_role ) );
											foreach ( $roles_data as $role_date ) {
												$sessions = WP_Session_Tokens::get_instance( $role_date->user_id );
												$sessions->destroy_all();
											}
										}
									}
								}
								if ( '' != $get_username ) {
									$pattern = '/[\-=+$@\t\r]/';
									if ( preg_match( $pattern, $block_msg_day ) ) {
										$msg_class = 'error';
										$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
									} else {
										foreach ( $user_ids as $user_id ) {
											update_user_meta( $user_id, 'block_day', $block_time_array );
											$block_msg_day = $default_msg;
											$block_url_day = '';
											$role_name     = '';
											if ( isset( $_POST['block_msg_day'] ) && '' != $_POST['block_msg_day'] ) {
												$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
											}
											update_user_meta( $user_id, 'block_msg_day', $block_msg_day );
											if ( isset( $_POST['block_url_day'] ) && '' != $_POST['block_url_day'] ) {
												$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
											}
											update_user_meta( $user_id, 'block_url_day', $block_url_day );
											$user_info  = get_userdata( $user_id );
											$role_name .= ', ' . $user_info->user_login;
											$msg_class  = 'updated';
											$msg        = esc_html__( 'Username wise time blocking is successfully added.', 'user-blocker' );
											$sessions   = WP_Session_Tokens::get_instance( $user_id );
											$sessions->destroy_all();
											$txt_sun_from = $txt_sun_to = $txt_mon_from = $txt_mon_to = $txt_tue_from = $txt_tue_to = $txt_wed_from = $txt_wed_to = $txt_thu_from = $txt_thu_to = $txt_thu_to = $txt_fri_from = $txt_fri_to = $txt_sat_from = $txt_sat_to = '';
											$cmb_user_by  = '';

											$username  = '';
											$reocrd_id = array();
										}
										$block_msg_day = '';
										$block_url_day = '';
									}
								}
							}
							$curr_edit_msg = '';
							$btn_val       = esc_html__( 'Block User', 'user-blocker' );
						} else {
							$reocrd_id   = array();
							$cmb_user_by = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
							$pattern     = '/[\-=+$@\t\r]/';
							// Check user by value.
							if ( 'role' == $cmb_user_by ) {
								// If user by is role.
								if ( isset( $_POST['chkUserRole'] ) ) {
									if ( is_array( $_POST['chkUserRole'] ) ) {
										$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
									} else {
										$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
									}
									$reocrd_id     = ublk_recursive_sanitize_text_field( $reocrd_id );
									$block_msg_day = $default_msg;
									$block_url_day = '';
									if ( isset( $_POST['block_msg_day'] ) && '' != $_POST['block_msg_day'] ) {
										$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
									}
									if ( isset( $_POST['block_url_day'] ) && '' != $_POST['block_url_day'] ) {
										$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
									}
									foreach ( $reocrd_id as $key => $val ) {
										$old_block_day     = get_option( $val . '_block_day' );
										$old_block_msg_day = get_option( $val . '_block_msg_day' );
										$old_block_url_day = get_option( $val . '_block_url_day' );
										if ( preg_match( $pattern, $block_msg_day ) ) {
											$msg_class = 'error';
											$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
										} else {
											update_option( $val . '_block_day', $block_time_array );
											update_option( $val . '_block_msg_day', $block_msg_day );
											update_option( $val . '_block_url_day', $block_url_day );
											$role_name = str_replace( '_', ' ', $get_role );
											// Update all users of this role.
											ublk_block_role_users_day( $val, $old_block_day, $block_time_array, $old_block_msg_day, $block_msg_day, $old_block_url_day, $block_url_day );
											// Update all users of this role end.
											$msg_class     = 'updated';
											$msg           = esc_html__( 'Role wise time blocking is successfully added.', 'user-blocker' );
											$txt_sun_from  = '';
											$txt_sun_to    = '';
											$txt_mon_from  = '';
											$txt_mon_to    = '';
											$txt_tue_from  = '';
											$txt_tue_to    = '';
											$txt_wed_from  = '';
											$txt_wed_to    = '';
											$txt_thu_from  = '';
											$txt_thu_to    = '';
											$txt_thu_to    = '';
											$txt_fri_from  = '';
											$txt_fri_to    = '';
											$txt_sat_from  = '';
											$txt_sat_to    = '';
											$cmb_user_by   = '';
											$block_msg_day = $default_msg;
											$block_url_day = '';
										}
									}
								} else {
									$msg_class     = 'error';
									$msg           = esc_html__( 'Please select atleast one role.', 'user-blocker' );
									$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
									$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
								}
							} elseif ( 'username' == $cmb_user_by ) {
								// If user by is username.
								if ( isset( $_POST['chkUserUsername'] ) ) {
									if ( is_array( $_POST['chkUserUsername'] ) ) {
										$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
									} else {
										$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
									}
									$reocrd_id     = ublk_recursive_sanitize_text_field( $reocrd_id );
									$block_msg_day = $default_msg;
									$block_url_day = '';
									if ( isset( $_POST['block_msg_day'] ) && '' != $_POST['block_msg_day'] ) {
										$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
									}
									if ( isset( $_POST['block_url_day'] ) && '' != $_POST['block_url_day'] ) {
										$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
									}
									if ( preg_match( $pattern, $block_msg_day ) ) {
										if ( is_array( $_POST['chkUserUsername'] ) ) {
											$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
										} else {
											$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
										}
										$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
										$msg_class = 'error';
										$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
									} else {
										foreach ( $reocrd_id as $key => $val ) {
											update_user_meta( $val, 'block_day', $block_time_array );
											update_user_meta( $val, 'block_msg_day', $block_msg_day );
											update_user_meta( $val, 'block_url_day', $block_url_day );
										}
										$msg_class     = 'updated';
										$msg           = esc_html__( 'Username wise time blocking is successfully added.', 'user-blocker' );
										$txt_sun_from  = '';
										$txt_sun_to    = '';
										$txt_mon_from  = '';
										$txt_mon_to    = '';
										$txt_tue_from  = '';
										$txt_tue_to    = '';
										$txt_wed_from  = '';
										$txt_wed_to    = '';
										$txt_thu_from  = '';
										$txt_thu_to    = '';
										$txt_thu_to    = '';
										$txt_fri_from  = '';
										$txt_fri_to    = '';
										$txt_sat_from  = '';
										$txt_sat_to    = '';
										$cmb_user_by   = '';
										$block_msg_day = '';
										$block_url_day = '';
									}
								} else {
									$msg_class     = 'error';
									$msg           = esc_html__( 'Please select atleast one username.', 'user-blocker' );
									$block_msg_day = sanitize_textarea_field( wp_unslash( $_POST['block_msg_day'] ) );
									$block_url_day = sanitize_url( wp_unslash( $_POST['block_url_day'] ) );
								}
							}
							$btn_val = esc_html__( 'Block User', 'user-blocker' );
						}
					} else {
						$msg_class   = 'error';
						$msg         = esc_html__( 'Please add from time for respected to time.', 'user-blocker' );
						$get_cmb_val = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
						if ( 'role' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserRole'] ) ) {
								if ( is_array( $_POST['chkUserRole'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						} elseif ( 'username' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserUsername'] ) ) {
								if ( is_array( $_POST['chkUserUsername'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						}
					}
				} else {
					$msg_class   = 'error';
					$msg         = esc_html__( 'Please enter valid time format.', 'user-blocker' );
					$get_cmb_val = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
					if ( 'role' == $get_cmb_val ) {
						if ( isset( $_POST['chkUserRole'] ) ) {
							if ( is_array( $_POST['chkUserRole'] ) ) {
								$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
							} else {
								$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
							}
							$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
						}
					} elseif ( 'username' == $get_cmb_val ) {
						if ( isset( $_POST['chkUserUsername'] ) ) {
							if ( is_array( $_POST['chkUserUsername'] ) ) {
								$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
							} else {
								$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
							}
							$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
						}
					}
				}
			} else {
				$msg_class   = 'error';
				$msg         = esc_html__( 'Time can\'t be blank.', 'user-blocker' );
				$get_cmb_val = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
				if ( 'role' == $get_cmb_val ) {
					if ( isset( $_POST['chkUserRole'] ) ) {
						if ( is_array( $_POST['chkUserRole'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
						}
						$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
					}
				} elseif ( 'username' == $get_cmb_val ) {
					if ( isset( $_POST['chkUserUsername'] ) ) {
						if ( is_array( $_POST['chkUserUsername'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
						}
						$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
					}
				}
			}
		}
		$user_query     = get_users( array( 'role' => 'administrator' ) );
		$admin_id       = wp_list_pluck( $user_query, 'ID' );
		$inactive_users = get_users(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'wp_capabilities',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'is_active',
						'value'   => 'n',
						'compare' => '=',
					),
				),
			)
		);
		$inactive_id    = wp_list_pluck( $inactive_users, 'ID' );
		$exclude_id     = array_unique( array_merge( $admin_id, $inactive_id ) );
		$users_filter   = array( 'exclude' => $exclude_id );
		// Start searching.
		$txt_username = '';
		if ( '' != ublk_get_data( 'txtUsername' ) ) {
			$display_users                  = 1;
			$txt_username                   = trim( ublk_get_data( 'txtUsername' ) );
			$users_filter['search']         = '*' . esc_attr( $txt_username ) . '*';
			$users_filter['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( '' != ublk_get_data( 'srole' ) ) {
				$display_users        = 1;
				$users_filter['role'] = ublk_get_data( 'srole' );
				$srole                = ublk_get_data( 'srole' );
			}
		}
		// end.
		if ( '' != $get_username ) {
			$display_users = 1;
		}
		if ( 1 == $is_display_role ) {
			$display_users = 0;
			$cmb_user_by   = 'role';
		}
		// if order and order by set, display users.
		if ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] && isset( $_GET['order'] ) && '' != $_GET['order'] ) {
			$display_users = 1;
		}
		// Select usermode on reset searching.
		if ( isset( $_GET['resetsearch'] ) && '1' == $_GET['resetsearch'] ) {
			$display_users = 1;
		}
		if ( 1 == $display_users ) {
			$cmb_user_by = 'username';
		}
		// end.
		$users_filter['orderby'] = $orderby;
		$users_filter['order']   = $order;
		$get_users_u1            = new WP_User_Query( $users_filter );
		$total_items             = $get_users_u1->total_users;
		$total_pages             = ceil( $total_items / $records_per_page );
		$next_page               = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$users_filter['number'] = $records_per_page;
		$users_filter['offset'] = $offset;
		$prev_page              = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}
		$sr_no = 1;
		if ( isset( $paged ) && $paged > 1 ) {
			$sr_no = ( $records_per_page * ( $paged - 1 ) + 1 );
		}
		$get_users_u = new WP_User_Query( $users_filter );
		$get_users   = $get_users_u->get_results();
		if ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) {
			$msg = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
		}
		if ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) {
			$msg_class = sanitize_text_field( wp_unslash( $_GET['msg_class'] ) );
		}
		?>
		<div class="wrap">
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
				<?php
			}
			if ( isset( $_SESSION['success_msg'] ) ) {
				?>
				<div class="updated is-dismissible notice settings-error">
					<p><?php echo esc_html( $_SESSION['success_msg'] ); ?></p>
					<?php unset( $_SESSION['success_msg'] ); ?>
				</div>
			<?php } ?>
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Block Users By Time', 'user-blocker' ); ?></h2>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a class="current" href="?page=block_user"><?php esc_html_e( 'Block User By Time', 'user-blocker' ); ?></a></li>
						<li><a href="?page=block_user_date"><?php esc_html_e( 'Block User By Date', 'user-blocker' ); ?></a></li>
						<li><a href="?page=block_user_permenant"><?php esc_html_e( 'Block User Permanent', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<form id="frmSearch" name="frmSearch" method="GET" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
					<div class="tablenav top">
						<?php ublk_user_category_dropdown( $cmb_user_by ); ?>
						<?php ublk_role_selection_dropdown( $display_users, $get_roles, $srole ); ?>
						<?php ublk_pagination( $display_users, $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'block_user' ); ?>
					</div>
					<div class="search_box">
						<?php
						ublk_user_search_field( $display_users, $txt_username, 'block_user' );
						ublk_bulk_actions_dropdown( $display_users, $username, $srole );
						?>
					</div>
				</form>
				<form method="post" action="?page=block_user" id="frmBlockUser">
					<input id="hidden_cmbUserBy" type="hidden" name="cmbUserBy" value='
					<?php
					if ( isset( $cmb_user_by ) && '' != $cmb_user_by ) {
						echo esc_attr( $cmb_user_by );
					} else {
						echo esc_attr( 'role' );
					}
					?>
					'/>
					<input type="hidden" name="paged" value="<?php echo esc_attr( $paged ); ?>"/>
					<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
					<input type="hidden" name="srole" value="<?php echo esc_attr( $srole ); ?>" />
					<input type="hidden" name="username" value="<?php echo esc_attr( $username ); ?>" />
					<input type="hidden" name="txtUsername" value="<?php echo esc_attr( $txt_username ); ?>" />
					<table id="role" class="widefat post fixed user-records striped" 
					<?php
					if ( 1 == $display_users ) {
						echo 'style="display: none;width: 100%;"';
					} else {
						echo 'style="width: 100%;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="th-time aligntextcenter"><?php esc_html_e( 'Block Time', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action aligntextcenter"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="th-time aligntextcenter"><?php esc_html_e( 'Block Time', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action aligntextcenter"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								if ( isset( $_REQUEST['action'] ) && 'edit' == $_REQUEST['action'] ) {
									$chk_user_role = isset( $_REQUEST['role'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['role'] ) ) ) : '';
								} else {
									$chk_user_role = $reocrd_id;
								}
							}
							$chk_multi_role = $chk_user_role;

							if ( $get_roles ) {
								foreach ( $get_roles as $key => $value ) {
									if ( 0 == $sr_no % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}
									if ( 'administrator' == $key || 'n' == get_option( $key . '_is_active' ) ) {
										continue;
									}
									if ( is_array( $chk_user_role ) && in_array( $key, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column"><input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $key ); ?>" name="chkUserRole[]" /></td>
										<td class="user-role"><?php echo esc_html( $value['name'] ); ?></td>
										<td class="aligntextcenter">
											<?php
											$exists_block_day = '';
											$block_day        = get_option( $key . '_block_day' );
											if ( ! empty( $block_day ) ) {
												$exists_block_day = 'y';
												?>
												<a href='' class="view_block_data" data-href="view_block_data_<?php echo esc_attr( $sr_no ); ?>"><img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/view.png" alt="view" /></a>
											<?php } ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_day' ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_day' ) ) ); ?>
										</td>
										<td class="aligntextcenter"><a href="?page=block_user&role=<?php echo esc_attr( $key ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>"><img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="edit" /></a></td>
									</tr>
									<?php if ( 'y' == $exists_block_day ) { ?>
										<tr class="view_block_data_tr" id="view_block_data_<?php echo esc_attr( $sr_no ); ?>">
											<td colspan="5">
												<table class="view_block_table form-table tbl-timing">
													<thead>
														<tr>
															<th align="center"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td align="center">
																<?php
																if ( array_key_exists( 'sunday', $block_day ) ) {
																	$from_time = $block_day['sunday']['from'];
																	$to_time   = $block_day['sunday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'monday', $block_day ) ) {
																	$from_time = $block_day['monday']['from'];
																	$to_time   = $block_day['monday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html_e( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'tuesday', $block_day ) ) {
																	$from_time = $block_day['tuesday']['from'];
																	$to_time   = $block_day['tuesday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'wednesday', $block_day ) ) {
																	$from_time = $block_day['wednesday']['from'];
																	$to_time   = $block_day['wednesday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'thursday', $block_day ) ) {
																	$from_time = $block_day['thursday']['from'];
																	$to_time   = $block_day['thursday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html_e( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html( ' to ' . ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'friday', $block_day ) ) {
																	$from_time = $block_day['friday']['from'];
																	$to_time   = $block_day['friday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'saturday', $block_day ) ) {
																	$from_time = $block_day['saturday']['from'];
																	$to_time   = $block_day['saturday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
										<?php
									}
									$sr_no++;
								}
							}
							?>
						</tbody>
					</table>
					<?php
					$is_checked = '';
					?>
					<table id="username" class="widefat post fixed user-records striped" 
					<?php
					if ( 1 == $display_users ) {
						echo 'style="display: table;"';
					} else {
						echo 'style="display: none;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="th-time aligntextcenter"><?php esc_html_e( 'Block Time', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action aligntextcenter"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="th-time aligntextcenter"><?php esc_html_e( 'Block Time', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								if ( isset( $_REQUEST['action'] ) && 'edit' == $_REQUEST['action'] ) {
									$chk_user_role = isset( $_REQUEST['username'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_REQUEST['username'] ) ) ) : '';
								} else {
									$chk_user_role = $reocrd_id;
								}
							}
							if ( $get_users ) {
								$d = 1;
								foreach ( $get_users as $user ) {
									if ( 0 == $d % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}
									if ( in_array( $user->ID, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column"><input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $user->ID ); ?>" name="chkUserUsername[]" /></td>
										<td><?php echo esc_html( $user->user_login ); ?></td>
										<td><?php echo esc_html( $user->display_name ); ?></td>
										<td><?php echo esc_html( $user->user_email ); ?></td>
										<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
										<td class="aligntextcenter">
											<?php
											$exists_block_day = '';
											$block_day        = get_user_meta( $user->ID, 'block_day', true );
											if ( ! empty( $block_day ) ) {
												$exists_block_day = 'y';
												?>
												<a href='' class="view_block_data" data-href="view_block_data_<?php echo esc_attr( $d ); ?>">
													<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/view.png" alt="<?php esc_html_e( 'view', 'user-blocker' ); ?>" />
												</a>
											<?php } ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_msg_day', true ) ) ); ?>
										</td>
										<td class="aligntextcenter"><?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_url_day', true ) ) ); ?></td>
										<td class="aligntextcenter">
											<a href="?page=block_user&username=<?php echo esc_attr( $user->ID ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>">
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="<?php esc_html_e( 'edit', 'user-blocker' ); ?>" />
											</a>
										</td>
									</tr>
									<?php if ( 'y' == $exists_block_day ) { ?>
										<tr class="view_block_data_tr" id="view_block_data_<?php echo esc_attr( $d ); ?>">
											<td colspan="10">
												<table class="view_block_table form-table tbl-timing">
													<thead>
														<tr>
															<th align="center"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
															<th align="center"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
														</tr>
													</thead>
													<tbody>
														<tr>
															<td align="center">
																<?php
																if ( array_key_exists( 'sunday', $block_day ) ) {
																	$from_time = $block_day['sunday']['from'];
																	$to_time   = $block_day['sunday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'monday', $block_day ) ) {
																	$from_time = $block_day['monday']['from'];
																	$to_time   = $block_day['monday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'tuesday', $block_day ) ) {
																	$from_time = $block_day['tuesday']['from'];
																	$to_time   = $block_day['tuesday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'wednesday', $block_day ) ) {
																	$from_time = $block_day['wednesday']['from'];
																	$to_time   = $block_day['wednesday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'thursday', $block_day ) ) {
																	$from_time = $block_day['thursday']['from'];
																	$to_time   = $block_day['thursday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'friday', $block_day ) ) {
																	$from_time = $block_day['friday']['from'];
																	$to_time   = $block_day['friday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
															<td align="center">
																<?php
																if ( array_key_exists( 'saturday', $block_day ) ) {
																	$from_time = $block_day['saturday']['from'];
																	$to_time   = $block_day['saturday']['to'];
																	if ( '' == $from_time ) {
																		esc_html_e( 'not set', 'user-blocker' );
																	} else {
																		echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
																	}
																	if ( '' != $from_time && '' != $to_time ) {
																		echo esc_html__( ' to ', 'user-blocker' ) . esc_html( ublk_time_to_twelve_hour( $to_time ) );
																	}
																} else {
																	esc_html_e( 'not set', 'user-blocker' );
																}
																?>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									<?php } ?>
									<?php
									$d++;
									$sr_no++;
								}
							} else {
								?>
								<tr>
									<td colspan="8" align="center">
										<?php esc_html_e( 'No records found.', 'user-blocker' ); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<?php
					$role_name = '';
					if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
						if ( $GLOBALS['wp_roles']->is_role( sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) ) {
							$role_name = ' ' . esc_html__( 'For', 'user-blocker' ) . ' <span style="text-transform: capitalize;">' . str_replace( '_', ' ', sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) . '</span>';
						}
					}
					if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
						if ( get_userdata( sanitize_text_field( wp_unslash( $_GET['username'] ) ) ) != false ) {
							$user_info = get_userdata( sanitize_text_field( wp_unslash( $_GET['username'] ) ) );
							$role_name = ' ' . esc_html__( 'For', 'user-blocker' ) . ' ' . $user_info->user_login;
						}
					}
					// Time List.
					?>
					<?php
					if ( ( isset( $chk_user_role ) && count( $chk_user_role ) > 1 ) || count( $chk_multi_role ) > 1 ) {
						$txt_sun_from = '';
						$txt_sun_to   = '';
						$txt_mon_from = '';
						$txt_mon_to   = '';
						$txt_tue_from = '';
						$txt_tue_to   = '';
						$txt_wed_from = '';
						$txt_wed_to   = '';
						$txt_thu_from = '';
						$txt_thu_to   = '';
						$txt_thu_to   = '';
						$txt_fri_from = '';
						$txt_fri_to   = '';
						$txt_sat_from = '';
						$txt_sat_to   = '';
					}

					?>
					<table class="form-table tbl-timing">
						<tr class="tr_head">
							<td style="border: 0;" colspan="20">
								<h3 class="block_msg_title">
									<?php
									esc_html_e( 'Block Time', 'user-blocker' );
									if ( isset( $curr_edit_msg ) && '' != $curr_edit_msg ) {
										?>
										<span><?php echo esc_html( $curr_edit_msg ); ?></span>
										<?php
									}
									?>
								</h3>
							</td>
						</tr>
						<tr>
							<th class="week-lbl"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
							<th class="week-lbl"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
						</tr>
						<tr>
							<td class="week-time" id="week-sun" align="center">
								<input tabindex="1" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_sun_from ) ); ?>" class="time start time-field" type="text" name="txtSunFrom" id="txtSunFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="2" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_sun_to ) ); ?>" class="time end time-field" type="text" name="txtSunTo" id="txtSunTo" />
							</td>
							<td class="week-time" id="week-mon" align="center">
								<input tabindex="3" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_mon_from ) ); ?>" class="time start time-field" type="text" name="txtMonFrom" id="txtMonFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="4" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_mon_to ) ); ?>" class="time end time-field" type="text" name="txtMonTo" id="txtMonTo" />
							</td>
							<td class="week-time" id="week-tue" align="center">
								<input tabindex="5" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_tue_from ) ); ?>" class="time start time-field" type="text" name="txtTueFrom" id="txtTueFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="6" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_tue_to ) ); ?>" class="time end time-field" type="text" name="txtTueTo" id="txtTueTo" />
							</td>
							<td class="week-time" id="week-wed" align="center">
								<input tabindex="7" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_wed_from ) ); ?>" class="time start time-field" type="text" name="txtWedFrom" id="txtWedFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="8" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_wed_to ) ); ?>" class="time end time-field" type="text" name="txtWedTo" id="txtWedTo" />
							</td>
							<td class="week-time" id="week-thu" align="center">
								<input tabindex="9" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_thu_from ) ); ?>" class="time start time-field" type="text" name="txtThuFrom" id="txtThuFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="10" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_thu_to ) ); ?>" class="time end time-field" type="text" name="txtThuTo" id="txtThuTo" />
							</td>
							<td class="week-time" id="week-fri" align="center">
								<input tabindex="11" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_fri_from ) ); ?>" class="time start time-field" type="text" name="txtFriFrom" id="txtFriFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="12" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_fri_to ) ); ?>" class="time end time-field" type="text" name="txtFriTo" id="txtFriTo" />
							</td>
							<td class="week-time" id="week-sat" align="center">
								<input tabindex="13" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_sat_from ) ); ?>" class="time start time-field" type="text" name="txtSatFrom" id="txtSatFrom" />
								<span>&nbsp;<?php esc_html_e( 'to', 'user-blocker' ); ?>&nbsp;</span>
								<input tabindex="14" value="<?php echo esc_attr( ublk_time_to_twelve_hour( $txt_sat_to ) ); ?>" class="time end time-field" type="text" name="txtSatTo" id="txtSatTo" />
							</td>
						</tr>
					</table>
					<input type="button" class="button primary-button" id="chkapply" value="<?php esc_html_e( 'Apply to all', 'user-blocker' ); ?>" />
					<input type="button" class="button primary-button" id="chkreset" value="<?php esc_html_e( 'Reset to all', 'user-blocker' ); ?>" />
					<h3 class="block_msg_title"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></h3>
					<div class="block_msg_div">
						<div class="block_msg_left">
							<textarea style="width:500px;height: 110px" name="block_msg_day"><?php echo esc_html( stripslashes( $block_msg_day ) ); ?></textarea>
						</div>
						<div class="block_msg_note_div">
							<?php
							echo '<b>' . esc_html__( 'Note', 'user-blocker' ) . '</b>: ';
							esc_html_e( 'If you will not set message, default message will be ', 'user-blocker' );
							echo "'<b>" . esc_html( $default_msg ) . "</b>'";
							?>
						</div><br>
						<div class="block_url_div" style="margin: 20px 0 0 0;clear: both;float: left">

							<label for="Block User Redirection" style="font-weight: 600;"><?php esc_html_e( 'Enter Redirection URL: ', 'user-blocker' ); ?></label>
							<input type="url" name="block_url_day" value="<?php echo esc_url( stripslashes( $block_url_day ) ); ?>" id="block_url_day">

						</div>
					</div>
					<?php
					if ( 'role' == $cmb_user_by || '' == $cmb_user_by ) {
						$btn_val = str_replace( 'User', 'Role', $btn_val );
					}
					?>
					<?php
					wp_nonce_field( '_wp_block_by_time_action', '_wp_block_by_time_nonce' );
					?>
					<input id="sbt-block" style="margin: 20px 0 0 0;clear: both;float: left" class="button button-primary" type="submit" name="sbtSaveTime" value="<?php echo esc_attr( $btn_val ); ?>">
					<?php if ( isset( $btn_val ) && ( 'Update Blocked User' == $btn_val || 'Update Blocked Role' == $btn_val ) ) { ?>
						<a style="margin: 20px 0 0 10px;float: left;" href="<?php echo '?page=block_user'; ?>" class="button button-primary"><?php esc_html_e( 'Cancel', 'user-blocker' ); ?></a>
					<?php } ?>
				</form>
			</div>
			<?php echo esc_html( ublk_display_support_section() ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_welcome_page' ) ) {
	/**
	 * Welcome Page.
	 */
	function ublk_welcome_page() {
		global $wpdb;
		$ublk_admin_email = get_option( 'admin_email' );
		?>
		<div class='ublk_header_wizard'>
			<p><?php esc_html_e( 'Hi there!', 'user-blocker' ); ?></p>
			<p><?php esc_html_e( "Don't ever miss an opportunity to opt in for Email Notifications / Announcements about exciting New Features and Update Releases.", 'user-blocker' ); ?></p>
			<p><?php esc_html_e( 'Contribute in helping us making our plugin compatible with most plugins and themes by allowing to share non-sensitive information about your website.', 'user-blocker' ); ?></p>
			<p><b><?php esc_html_e( 'Email Address for Notifications', 'user-blocker' ); ?> :</b></p>
			<p><input type='email' value='<?php echo esc_attr( $ublk_admin_email ); ?>' id='ublk_admin_email' /></p>
			<p><?php esc_html_e( "If you're not ready to Opt-In, that's ok too!", 'user-blocker' ); ?></p>
			<p><b><?php esc_html_e( 'User Blocker will still work fine.', 'user-blocker' ); ?> :</b></p>
			<p onclick="ublk_show_hide_permission()" class='ublk_permission'><b><?php esc_html_e( 'What permissions are being granted?', 'user-blocker' ); ?></b></p>
			<div class='ublk_permission_cover' style='display:none'>
				<div class='ublk_permission_row'>
					<div class='ublk_50'>
						<i class='dashicons dashicons-admin-users gb-dashicons-admin-users'></i>
						<div class='ublk_50_inner'>
							<label><?php esc_html_e( 'User Details', 'user-blocker' ); ?></label>
							<label><?php esc_html_e( 'Name and Email Address', 'user-blocker' ); ?></label>
						</div>
					</div>
					<div class='ublk_50'>
						<i class='dashicons dashicons-admin-plugins gb-dashicons-admin-plugins'></i>
						<div class='ublk_50_inner'>
							<label><?php esc_html_e( 'Current Plugin Status', 'user-blocker' ); ?></label>
							<label><?php esc_html_e( 'Activation, Deactivation and Uninstall', 'user-blocker' ); ?></label>
						</div>
					</div>
				</div>
				<div class='ublk_permission_row'>
					<div class='ublk_50'>
						<i class='dashicons dashicons-testimonial gb-dashicons-testimonial'></i>
						<div class='ublk_50_inner'>
							<label><?php esc_html_e( 'Notifications', 'user-blocker' ); ?></label>
							<label><?php esc_html_e( 'Updates & Announcements', 'user-blocker' ); ?></label>
						</div>
					</div>
					<div class='ublk_50'>
						<i class='dashicons dashicons-welcome-view-site gb-dashicons-welcome-view-site'></i>
						<div class='ublk_50_inner'>
							<label><?php esc_html_e( 'Website Overview', 'user-blocker' ); ?></label>
							<label><?php esc_html_e( 'Site URL, WP Version, PHP Info, Plugins & Themes Info', 'user-blocker' ); ?></label>
						</div>
					</div>
				</div>
			</div>
			<p>
				<input type='checkbox' class='ublk_agree' id='ublk_agree_gdpr' value='1' />
				<label for='ublk_agree_gdpr' class='ublk_agree_gdpr_lbl'><?php esc_html_e( 'By clicking this button, you agree with the storage and handling of your data as mentioned above by this website. (GDPR Compliance)', 'user-blocker' ); ?></label>
			</p>
			<p class='ublk_buttons'>
				<a href="javascript:void(0)" class='button button-secondary' onclick="ublk_submit_optin('cancel')">
				<?php
				wp_nonce_field( 'on_ublk_submit_optin_nonce', 'ublk_submit_optin_nonce' );
				esc_html_e( 'Skip', 'user-blocker' );
				echo ' &amp; ';
				esc_html_e( 'Continue', 'user-blocker' );
				?>
				</a>
				<a href="javascript:void(0)" class='button button-primary' onclick="ublk_submit_optin('submit')">
				<?php
				wp_nonce_field( 'on_ublk_submit_optin_nonce', 'ublk_submit_optin_nonce' );
				esc_html_e( 'Opt-In', 'user-blocker' );
				echo ' &amp; ';
				esc_html_e( 'Continue', 'user-blocker' );
				?>
				</a>
			</p>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_block_user_date_page' ) ) {
	/**
	 * Block User Date Page.
	 */
	function ublk_block_user_date_page() {
		global $wpdb;
		global $wp_roles;
		$default_msg      = esc_html__( 'You are temporary blocked.', 'user-blocker' );
		$btn_val          = esc_html__( 'Block User', 'user-blocker' );
		$orderby          = 'user_login';
		$order            = 'ASC';
		$reocrd_id        = array();
		$option_name      = array();
		$records_per_page = 10;
		$sr_no            = 1;
		$paged            = 1;
		$display_users    = 1;
		$is_display_role  = 0;
		$msg_class        = '';
		$msg              = '';
		$curr_edit_msg    = '';
		$block_msg_date   = '';
		$block_url_date   = '';
		$username         = '';
		$srole            = '';
		$role             = '';
		$frmdate          = '';
		$todate           = '';

		if ( '' != ublk_get_data( 'paged' ) ) {
			$display_users = 1;
			$paged         = ublk_get_data( 'paged', 1 );
		}
		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$orderby = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : $orderby;
		$order   = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : $order;

		$offset = ( $paged - 1 ) * $records_per_page;

		$get_roles = $wp_roles->roles;
		$get_role  = ublk_get_data( 'role' );
		if ( '' != $get_role ) {
			$reocrd_id       = array( $get_role );
			$reocrd_id       = ublk_recursive_sanitize_text_field( $reocrd_id );
			$role            = $get_role;
			$btn_name        = 'editTime';
			$btn_val         = esc_html__( 'Update Blocked User', 'user-blocker' );
			$user_roles      = explode( ',', $get_role );
			$multi_users     = get_users( array( 'role__in' => $user_roles ) );
			$user_datas      = '';
			$is_display_role = 1;
			foreach ( $user_roles as $user_role ) {
				if ( $GLOBALS['wp_roles']->is_role( $user_role ) ) {
					$block_date = get_option( $user_role . '_block_date' );
					if ( '' != $block_date && ! empty( $block_date ) ) {
						$frmdate = $block_date['frmdate'];
						$todate  = $block_date['todate'];
					}
					$block_msg_date = get_option( $user_role . '_block_msg_date' );
					$block_url_date = get_option( $user_role . '_block_url_date' );
					$user_datas    .= ', ' . $GLOBALS['wp_roles']->roles[ $user_role ]['name'];
					$user_role      = ltrim( $user_datas, ', ' );
					$curr_edit_msg  = esc_html__( 'Update for role', 'user-blocker' ) . ': ' . $user_role;
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Role', 'user-blocker' ) . ' ' . $user_role . ' ' . esc_html__( 'is not exist.', 'user-blocker' );
				}
			}
		}
		$get_username = ublk_get_data( 'username' );
		if ( '' != $get_username ) {
			$reocrd_id   = array( $get_username );
			$reocrd_id   = ublk_recursive_sanitize_text_field( $reocrd_id );
			$username    = $get_username;
			$btn_name    = 'editTime';
			$btn_val     = esc_html__( 'Update Blocked User', 'user-blocker' );
			$user_ids    = explode( ',', $get_username );
			$multi_users = get_users( array( 'include' => $user_ids ) );
			$user_datas  = '';
			foreach ( $user_ids as $user_id ) {
				if ( false != $multi_users ) {
					$block_date = get_user_meta( $user_id, 'block_date', true );
					if ( '' != $block_date && ! empty( $block_date ) ) {
						$frmdate = $block_date['frmdate'];
						$todate  = $block_date['todate'];
					}
					$block_msg_date = get_user_meta( $user_id, 'block_msg_date', true );
					$block_url_date = get_user_meta( $user_id, 'block_url_date', true );
					$user_data      = new WP_User( $user_id );
					$user_datas    .= ', ' . $user_data->user_login;
					$curr_edit_msg  = esc_html__( 'Update for user with username: ', 'user-blocker' ) . ltrim( $user_datas, ', ' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'User with', 'user-blocker' ) . ' ' . $user_id . ' ' . esc_html__( 'userid is not exist.', 'user-blocker' );
				}
			}
		}
		if ( isset( $_POST['sbtSaveDate'] ) && isset( $_POST['_wp_block_by_date_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_block_by_date_nonce'] ) ), '_wp_block_by_date_action' ) ) {
			$get_username = '';
			$get_role     = '';
			if ( isset( $_POST['username'] ) ) {
				$get_username = sanitize_textarea_field( wp_unslash( $_POST['username'] ) );
			}
			if ( isset( $_POST['role'] ) ) {
				$get_role = sanitize_textarea_field( wp_unslash( $_POST['role'] ) );
			}
			$frmdate = isset( $_POST['frmdate'] ) ? sanitize_text_field( wp_unslash( $_POST['frmdate'] ) ) : '';
			$todate  = isset( $_POST['todate'] ) ? sanitize_text_field( wp_unslash( $_POST['todate'] ) ) : '';
			// Check if username is selected in dd.
			if ( isset( $_POST['cmbUserBy'] ) && 'role' == $_POST['cmbUserBy'] ) {
				$is_display_role = 1;
			}
			if ( isset( $_POST['cmbUserBy'] ) && 'username' == $_POST['cmbUserBy'] ) {
				$display_users = 1;
			}
			if ( '' != $frmdate && '' != $todate && ( strtotime( $frmdate ) <= strtotime( $todate ) ) ) {
				// Validation for fromdate to todate.
				if ( ( '' != $get_role ) || ( '' != $get_username ) ) {
					// Edit record in date wise blocking.
					$user_ids          = explode( ',', $get_username );
					$user_datas        = '';
					$multi_users       = get_users( array( 'include' => $user_ids ) );
					$user_roles        = explode( ',', $get_role );
					$multi_users_roles = get_users( array( 'role__in' => $user_roles ) );
					if ( '' != $get_role ) {
						$block_date            = array();
						$block_date['frmdate'] = sanitize_text_field( wp_unslash( $_POST['frmdate'] ) );
						$block_date['todate']  = sanitize_text_field( wp_unslash( $_POST['todate'] ) );
						$pattern               = '/[\-=+$@\t\r]/';
						foreach ( $user_roles as $multi_users_role ) {
							$old_block_date     = get_option( $multi_users_role . '_block_date' );
							$old_block_msg_date = get_option( $multi_users_role . '_block_msg_date' );
							$old_block_url_date = get_option( $multi_users_role . '_block_url_date' );
							if ( preg_match( $pattern, $block_msg_date ) ) {
								$msg_class = 'error';
								$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
							} else {
								update_option( $multi_users_role . '_block_date', $block_date );
								$block_msg_date = $default_msg;
								if ( isset( $_POST['block_msg_date'] ) && '' != $_POST['block_msg_date'] ) {
									$block_msg_date = sanitize_text_field( wp_unslash( $_POST['block_msg_date'] ) );
								}
								if ( isset( $_POST['block_url_date'] ) && '' != $_POST['block_url_date'] ) {
									$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
								}
								update_option( $multi_users_role . '_block_msg_date', $block_msg_date );
								update_option( $multi_users_role . '_block_url_date', $block_url_date );
								// Update all users of this role.
								ublk_block_role_users_date( $multi_users_role, $old_block_date, $block_date, $old_block_msg_date, $block_msg_date, $old_block_url_date, $block_url_date );
								// Update all users of this role end.
								$role_name        = str_replace( '_', ' ', $multi_users_role );
								$msg_class        = 'updated';
								$user_datas      .= ', ' . $GLOBALS['wp_roles']->roles[ $multi_users_role ]['name'];
								$multi_users_role = ltrim( $user_datas, ', ' );
								$msg              = $multi_users_role . '\'s ' . esc_html__( 'date wise blocking have been updated successfully', 'user-blocker' );
								$frmdate          = '';
								$todate           = '';
								$block_msg_date   = '';
								$block_url_date   = '';
								$role             = '';
								$reocrd_id        = array();
							}
						}
					}
					if ( '' != $get_username ) {
						$block_date            = array();
						$block_date['frmdate'] = sanitize_text_field( wp_unslash( $_POST['frmdate'] ) );
						$block_date['todate']  = sanitize_text_field( wp_unslash( $_POST['todate'] ) );
						$block_msg_date        = $default_msg;
						$block_url_date        = '';
						$role_name             = '';
						$pattern               = '/[\-=+$@\t\r]/';
						if ( '' != $_POST['block_msg_date'] ) {
							$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
						}
						if ( isset( $_POST['block_url_date'] ) && '' != $_POST['block_url_date'] ) {
							$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
						}
						if ( preg_match( $pattern, $block_msg_date ) ) {
							$msg_class = 'error';
							$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
						} else {
							foreach ( $user_ids as $user_id ) {
								update_user_meta( $user_id, 'block_date', $block_date );
								update_user_meta( $user_id, 'block_msg_date', $block_msg_date );
								update_user_meta( $user_id, 'block_url_date', $block_url_date );
								$user_info  = get_userdata( $user_id );
								$role_name .= ', ' . $user_info->user_login;
								$msg_class  = 'updated';
								$msg        = ltrim( $role_name, ', ' ) . '\'s ' . esc_html__( 'date wise blocking have been updated successfully', 'user-blocker' );
								$frmdate    = '';
								$todate     = '';
								$username   = '';
								$reocrd_id  = array();
							}
							$block_msg_date = '';
							$block_url_date = '';
						}
					}
					$curr_edit_msg = '';
					$btn_val       = esc_html__( 'Block User', 'user-blocker' );
				} else {
					// Add record in date wise blocking.
					$cmb_user_by = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
					if ( 'role' == $cmb_user_by ) {
						if ( isset( $_POST['chkUserRole'] ) ) {
							if ( is_array( $_POST['chkUserRole'] ) ) {
								$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
							} else {
								$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
							}
							$reocrd_id      = ublk_recursive_sanitize_text_field( $reocrd_id );
							$block_msg_date = $default_msg;
							$block_url_date = '';
							$pattern        = '/[\-=+$@\t\r]/';
							if ( '' != $_POST['block_msg_date'] ) {
								$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
							}
							if ( '' != $_POST['block_url_date'] ) {
								$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
							}
							if ( preg_match( $pattern, $block_msg_date ) ) {
								$msg_class = 'error';
								$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
							} else {
								foreach ( $reocrd_id as $key => $val ) {
									$block_date            = array();
									$block_date['frmdate'] = sanitize_text_field( wp_unslash( $_POST['frmdate'] ) );
									$block_date['todate']  = sanitize_text_field( wp_unslash( $_POST['todate'] ) );
									$old_block_date        = get_option( $val . '_block_date' );
									$old_block_msg_date    = get_option( $val . '_block_msg_date' );
									$old_block_url_date    = get_option( $val . '_block_url_date' );
									update_option( $val . '_block_date', $block_date );
									update_option( $val . '_block_msg_date', $block_msg_date );
									update_option( $val . '_block_url_date', $block_url_date );
									// Update all users of this role.
									ublk_block_role_users_date( $val, $old_block_date, $block_date, $old_block_msg_date, $block_msg_date, $old_block_url_date, $block_url_date );
									// Update all users of this role end.
								}
								$msg_class      = 'updated';
								$msg            = esc_html__( 'Selected roles have beeen blocked successfully.', 'user-blocker' );
								$frmdate        = '';
								$todate         = '';
								$block_msg_date = '';
								$block_url_date = '';
							}
						} else {
							$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
							$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
							$msg_class      = 'error';
							$msg            = esc_html__( 'Please select atleast one role.', 'user-blocker' );
						}
					} elseif ( 'username' == $cmb_user_by ) {
						if ( isset( $_POST['chkUserUsername'] ) ) {
							if ( is_array( $_POST['chkUserUsername'] ) ) {
								$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
							} else {
								$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
							}
							$reocrd_id      = ublk_recursive_sanitize_text_field( $reocrd_id );
							$pattern        = '/[\-=+$@\t\r]/';
							$block_msg_date = $default_msg;
							$block_url_date = '';
							if ( isset( $_POST['block_msg_date'] ) && '' != $_POST['block_msg_date'] ) {
								$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
							}
							if ( isset( $_POST['block_url_date'] ) && '' != $_POST['block_url_date'] ) {
								$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
							}

							if ( preg_match( $pattern, $block_msg_date ) ) {
								$msg_class = 'error';
								$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
							} else {
								foreach ( $reocrd_id as $key => $val ) {
									$block_date['frmdate'] = sanitize_text_field( wp_unslash( $_POST['frmdate'] ) );
									$block_date['todate']  = sanitize_text_field( wp_unslash( $_POST['todate'] ) );
									update_user_meta( $val, 'block_date', $block_date );
									update_user_meta( $val, 'block_msg_date', $block_msg_date );
									update_user_meta( $val, 'block_url_date', $block_url_date );
								}
								$msg_class      = 'updated';
								$msg            = esc_html__( 'Selected users have beeen blocked successfully.', 'user-blocker' );
								$frmdate        = '';
								$todate         = '';
								$block_msg_date = '';
								$block_url_date = '';
							}
						} else {
							$msg_class      = 'error';
							$msg            = esc_html__( 'Please select atleast one username.', 'user-blocker' );
							$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
							$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
						}
					}
					$btn_val   = esc_html__( 'Block User', 'user-blocker' );
					$reocrd_id = array();
				}   //database update for add and edit end.
			} else {
				$msg_class      = 'error';
				$msg            = esc_html__( 'Please enter valid block date.', 'user-blocker' );
				$block_msg_date = sanitize_textarea_field( wp_unslash( $_POST['block_msg_date'] ) );
				$block_url_date = sanitize_url( wp_unslash( $_POST['block_url_date'] ) );
				$get_cmb_val    = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
				if ( 'role' == $get_cmb_val ) {
					if ( isset( $_POST['chkUserRole'] ) ) {
						if ( is_array( $_POST['chkUserRole'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
						}
						$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
					}
				} elseif ( 'username' == $get_cmb_val ) {
					if ( isset( $_POST['chkUserUsername'] ) ) {
						if ( is_array( $_POST['chkUserUsername'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
						}
						$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
					}
				}
			}
		}
		$user_query     = get_users( array( 'role' => 'administrator' ) );
		$admin_id       = wp_list_pluck( $user_query, 'ID' );
		$inactive_users = get_users(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key'     => 'wp_capabilities',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'is_active',
						'value'   => 'n',
						'compare' => '=',
					),
				),
			)
		);
		$inactive_id    = wp_list_pluck( $inactive_users, 'ID' );
		$exclude_id     = array_unique( array_merge( $admin_id, $inactive_id ) );
		$users_filter   = array( 'exclude' => $exclude_id );
		// Start searching.
		$txt_username = '';
		if ( '' != ublk_get_data( 'txtUsername' ) ) {
			$display_users                  = 1;
			$txt_username                   = ublk_get_data( 'txtUsername' );
			$users_filter['search']         = '*' . esc_attr( $txt_username ) . '*';
			$users_filter['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( '' != ublk_get_data( 'srole' ) ) {
				$display_users        = 1;
				$users_filter['role'] = ublk_get_data( 'srole' );
				$srole                = ublk_get_data( 'srole' );
			}
		}
		if ( '' != $get_username ) {
			$display_users = 1;
		}
		if ( 1 == $is_display_role ) {
			$display_users = 0;
			$cmb_user_by   = 'role';
		}
		// if order and order by set, display users.
		if ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] && isset( $_GET['order'] ) && '' != $_GET['order'] ) {
			$display_users = 1;
		}
		// Select usermode on reset searching.
		if ( isset( $_GET['resetsearch'] ) && '1' == $_GET['resetsearch'] ) {
			$display_users = 1;
		}
		if ( 1 == $display_users ) {
			$cmb_user_by = 'username';
		}
		// end.
		// Query to get total users.
		$users_filter['orderby'] = $orderby;
		$users_filter['order']   = $order;
		$get_users_u1            = new WP_User_Query( $users_filter );
		$total_items             = $get_users_u1->total_users;
		$total_pages             = ceil( $total_items / $records_per_page );
		$next_page               = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$users_filter['number'] = $records_per_page;
		$users_filter['offset'] = $offset;
		$prev_page              = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}
		$sr_no = 1;
		if ( isset( $paged ) && $paged > 1 ) {
			$sr_no = ( $records_per_page * ( $paged - 1 ) + 1 );
		}
		// Main Query to display users.
		$get_users_u = new WP_User_Query( $users_filter );
		$get_users   = $get_users_u->get_results();
		if ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) {
			$msg = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
		}
		if ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) {
			$msg_class = sanitize_text_field( wp_unslash( $_GET['msg_class'] ) );
		}
		?>
		<div class="wrap">
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
			<?php } ?>
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Block Users By Date', 'user-blocker' ); ?></h2>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=block_user"><?php esc_html_e( 'Block User By Time', 'user-blocker' ); ?></a></li>
						<li><a class="current" href="?page=block_user_date"><?php esc_html_e( 'Block User By Date', 'user-blocker' ); ?></a></li>
						<li><a href="?page=block_user_permenant"><?php esc_html_e( 'Block User Permanent', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
					<div class="tablenav top">
						<?php
						ublk_user_category_dropdown( $cmb_user_by );
						ublk_role_selection_dropdown( $display_users, $get_roles, $srole );
						ublk_pagination( $display_users, $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'block_user_date' );
						?>
					</div>
					<div class="search_box">
						<?php
						ublk_user_search_field( $display_users, $txt_username, 'block_user_date' );
						ublk_bulk_actions_dropdown( $display_users, $get_roles, $srole );
						?>
					</div>
				</form>
				<form method="post" action="?page=block_user_date" id="frmBlockUser">
					<input type="hidden" id='hidden_cmbUserBy' name="cmbUserBy" value='
					<?php
					if ( isset( $cmb_user_by ) && '' != $cmb_user_by ) {
						echo esc_attr( $cmb_user_by );
					} else {
						echo esc_attr( 'role' );
					}
					?>
					'/>
					<input type="hidden" name="paged" value="<?php echo esc_attr( $paged ); ?>"/>
					<input type="hidden" name="srole" value="<?php echo esc_attr( $srole ); ?>" />
					<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
					<input type="hidden" name="username" value="<?php echo esc_attr( $username ); ?>" />
					<input type="hidden" name="txtUsername" value="<?php echo esc_attr( $txt_username ); ?>" />
					<table id="role" class="widefat post fixed user-records striped" 
					<?php
					if ( 1 == $display_users ) {
						echo 'style="display: none;width: 100%;"';
					} else {
						echo 'style="width: 100%;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								$chk_user_role = $reocrd_id;
							}
							if ( $get_roles ) {
								$p_txt_username = isset( $_GET['txtUsername'] ) ? sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) : '';
								$p_srole        = isset( $_GET['srole'] ) ? sanitize_text_field( wp_unslash( $_GET['srole'] ) ) : '';
								$p_paged        = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
								foreach ( $get_roles as $key => $value ) {
									if ( 0 == $sr_no % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}
									if ( 'administrator' == $key || 'n' == get_option( $key . '_is_active' ) ) {
										continue;
									}
									if ( is_array( $chk_user_role ) && in_array( $key, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column">
											<input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $key ); ?>" name="chkUserRole[]" />
										</td>
										<td><?php echo esc_html( $value['name'] ); ?></td>
										<td>
											<?php
											$block_date = get_option( $key . '_block_date' );
											if ( '' != $block_date && ! empty( $block_date ) ) {
												$frmdate1 = $block_date['frmdate'];
												$todate1  = $block_date['todate'];
												echo esc_html( $frmdate1 ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( $todate1 );
											} else {
												esc_html_e( 'not set', 'user-blocker' );
											}
											?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_date' ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_date' ) ) ); ?>
										</td>
									   
										<td class="aligntextcenter">
											<a href="?page=block_user_date&role=<?php echo esc_attr( $key ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="<?php esc_html_e( 'edit', 'user-blocker' ); ?>" />
											</a>
										</td>
									</tr>
									<?php
									$sr_no++;
								}
							} else {
								echo '<tr><td colspan="5" align="center">' . esc_html__( 'No records found.', 'user-blocker' ) . '</td></tr>';
							}
							?>
						</tbody>
					</table>
					<?php
					$is_checked = '';
					?>
					<table id="username" class="widefat post fixed user-records striped" 
					<?php
					if ( 1 == $display_users ) {
						echo 'style="display: table;"';
					} else {
						echo 'style="display: none;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_date&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-url aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								$chk_user_role = $reocrd_id;
							}
							if ( $get_users ) {
								$p_txt_username = isset( $_GET['txtUsername'] ) ? sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) : '';
								$p_srole        = isset( $_GET['srole'] ) ? sanitize_text_field( wp_unslash( $_GET['srole'] ) ) : '';
								$p_paged        = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
								$d              = 1;
								foreach ( $get_users as $user ) {
									if ( 0 == $d % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}
									if ( in_array( $user->ID, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column"><input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $user->ID ); ?>" name="chkUserUsername[]" /></td>
										<td><?php echo esc_html( $user->user_login ); ?></td>
										<td><?php echo esc_html( $user->display_name ); ?></td>
										<td><?php echo esc_html( $user->user_email ); ?></td>
										<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
										<td>
											<?php
											$block_date = array();
											$block_date = get_user_meta( $user->ID, 'block_date', true );
											if ( '' != $block_date ) {
												$frmdate1 = $block_date['frmdate'];
												$todate1  = $block_date['todate'];
												echo esc_html( ublk_date_time_to_twelve_hour( $frmdate1 ) . ' to ' . ublk_date_time_to_twelve_hour( $todate1 ) );
											} else {
												echo esc_html_e( 'not set', 'user-blocker' );
											}
											?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_msg_date', true ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_url_date', true ) ) ); ?>
										</td>
										<td class="aligntextcenter"><a href="?page=block_user_date&username=<?php echo esc_attr( $user->ID ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>"><img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="edit" /></a></td>
									</tr>
									<?php
									$d++;
								}
							} else {
								?>
								<tr>
									<td colspan="8" align="center">
										<?php esc_html_e( 'No records Founds.', 'user-blocker' ); ?>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
					</table>
					<h3 class="block_msg_title">
					<?php
					esc_html_e( 'Block Date', 'user-blocker' );
					if ( isset( $curr_edit_msg ) && '' != $curr_edit_msg ) {
						?>
							<span><?php echo esc_html( $curr_edit_msg ); ?></span>
							<?php
					}
					?>
					</h3>
					<?php
					if ( isset( $btn_val ) && 'Update Blocked User' == $btn_val ) {
						$get_user  = ( isset( $_GET['username'] ) && '' != $_GET['username'] ) ? sanitize_text_field( wp_unslash( $_GET['username'] ) ) : '';
						$block_day = get_user_meta( $get_user, 'block_day', true );
						if ( '' != $block_day && 0 != $block_day ) {
							echo '<div style="width: 100%; clear: both; display:inline-block;">';
							echo '<span style="display: block; padding: 5px 0;">' . esc_html__( 'This user is blocked for below time:', 'user-blocker' ) . '</span>';
							echo '<div class="day-table">';
							ublk_display_block_time( 'sunday', $block_day );
							ublk_display_block_time( 'monday', $block_day );
							ublk_display_block_time( 'tuesday', $block_day );
							ublk_display_block_time( 'wednesday', $block_day );
							ublk_display_block_time( 'thursday', $block_day );
							ublk_display_block_time( 'friday', $block_day );
							ublk_display_block_time( 'saturday', $block_day );
							echo '</div>';
							echo '</div>';
						}
					}
					?>
					<div class="block_msg_div">
						<table class="form-table tbl-timing">
							<tbody>
								<tr>
									<td style="padding: 15px;">&nbsp;
										<?php esc_html_e( 'From', 'user-blocker' ); ?> &nbsp;
										<input type="text" name="frmdate" value="<?php echo esc_attr( $frmdate ); ?>" id="frmdate" /> &nbsp;
										<?php esc_html_e( 'To', 'user-blocker' ); ?> &nbsp;
										<input type="text" name="todate" value="<?php echo esc_attr( $todate ); ?>" id="todate" />
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<h3 class="block_msg_title"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></h3>
					<div class="block_msg_div">
						<div class="block_msg_left">
							<textarea style="width:500px;height: 110px" name="block_msg_date"><?php echo esc_html( stripslashes( $block_msg_date ) ); ?></textarea>
						</div>
						<div class="block_msg_note_div">
							<?php
							echo '<b>' . esc_html__( 'Note', 'user-blocker' ) . '</b>: ';
							esc_html_e( 'If you will not set message, default message will be ', 'user-blocker' );
							echo "'<b>" . esc_html( $default_msg ) . "</b>'";
							?>
						</div>
						<br>
						<div class="block_url_div" style="margin: 20px 0 0 0;clear: both;float: left">

							<label for="Block User Redirection" style="font-weight: 600;"> <?php esc_html_e( 'Enter Redirection URL: ', 'user-blocker' ); ?> </label>
							<input type="url" name="block_url_date" value="<?php echo esc_url( stripslashes( $block_url_date ) ); ?>" id="block_url_date">

						</div>
					</div>
					<?php
					if ( 'role' == $cmb_user_by || '' == $cmb_user_by ) {
						$btn_val = str_replace( 'User', 'Role', $btn_val );
					}
					?>
					<?php
					wp_nonce_field( '_wp_block_by_date_action', '_wp_block_by_date_nonce' );
					?>
					<input id="sbt-block" style="margin: 20px 0 0 0;clear: both;float: left;" class="button button-primary" type="submit" name="sbtSaveDate" value="<?php echo esc_attr( $btn_val ); ?>">
					<?php if ( isset( $btn_val ) && 'Update Blocked User' == $btn_val ) { ?>
						<a style="margin: 20px 0 0 10px;float: left;" href="<?php echo '?page=block_user_date'; ?>" class="button button-primary">
							<?php esc_html_e( 'Cancel', 'user-blocker' ); ?>
						</a>
					<?php } ?>
				</form>
			</div>
			<?php ublk_display_support_section(); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_block_user_permenant_page' ) ) {
	/**
	 * Block User Permanent Page.
	 */
	function ublk_block_user_permenant_page() {
		global $wpdb;
		global $wp_roles;
		$orderby             = 'user_login';
		$order               = 'ASC';
		$btn_val             = esc_html__( 'Block User', 'user-blocker' );
		$option_name         = array();
		$block_time_array    = array();
		$reocrd_id           = array();
		$sr_no               = 1;
		$records_per_page    = 10;
		$is_active           = 1;
		$paged               = 1;
		$display_users       = 1;
		$is_display_role     = 0;
		$msg_class           = '';
		$msg                 = '';
		$curr_edit_msg       = '';
		$block_msg_permenant = '';
		$block_msg           = '';
		$srole               = '';
		$role                = '';
		$username            = '';
		$role                = '';
		$block_msg_permenant = '';
		$block_url_permenant = '';

		$default_msg = esc_html__( 'You are permanently blocked.', 'user-blocker' );
		if ( '' != ublk_get_data( 'paged' ) ) {
			$display_users = 1;
			$paged         = ublk_get_data( 'paged', 1 );
		}
		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}
		if ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) {
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
		}
		if ( isset( $_GET['order'] ) && '' != $_GET['order'] ) {
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		}
		$offset = ( $paged - 1 ) * $records_per_page;

		$get_roles = $wp_roles->roles;
		$get_role  = ublk_get_data( 'role' );
		if ( '' != $get_role ) {
			$reocrd_id       = array( $get_role );
			$reocrd_id       = ublk_recursive_sanitize_text_field( $reocrd_id );
			$role            = $get_role;
			$btn_name        = 'editTime';
			$btn_val         = esc_html__( 'Update Blocked User', 'user-blocker' );
			$is_display_role = 1;
			$user_roles      = explode( ',', $get_role );
			$multi_users     = get_users( array( 'role__in' => $user_roles ) );
			$user_datas      = '';
			foreach ( $user_roles as $user_role ) {
				if ( $GLOBALS['wp_roles']->is_role( $user_role ) ) {
					$is_active           = get_option( $user_role . '_is_active' );
					$block_msg_permenant = get_option( $user_role . '_block_msg_permenant' );
					$block_url_permenant = get_option( $user_role . '_block_url_permenant' );
					$user_datas         .= ', ' . $GLOBALS['wp_roles']->roles[ $user_role ]['name'];
					$user_role           = ltrim( $user_datas, ', ' );
					$curr_edit_msg       = esc_html__( 'Update for role: ', 'user-blocker' ) . $user_role;
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Role', 'user-blocker' ) . ' ' . $user_role . ' ' . esc_html__( 'is not exist.', 'user-blocker' );
				}
			}
		}
		$get_username = ublk_get_data( 'username' );
		if ( '' != $get_username ) {
			$reocrd_id   = array( $get_username );
			$reocrd_id   = ublk_recursive_sanitize_text_field( $reocrd_id );
			$username    = $get_username;
			$btn_name    = 'editTime';
			$btn_val     = esc_html__( 'Update Blocked User', 'user-blocker' );
			$user_ids    = explode( ',', $get_username );
			$multi_users = get_users( array( 'include' => $user_ids ) );
			$user_datas  = '';
			foreach ( $user_ids as $user_id ) {
				if ( false != $multi_users ) {
					$is_active           = get_user_meta( $user_id, 'is_active', true );
					$block_msg_permenant = get_user_meta( $user_id, 'block_msg_permenant', true );
					$block_url_permenant = get_user_meta( $user_id, 'block_url_permenant', true );
					$user_data           = new WP_User( $user_id );
					if ( '' == $is_active ) {
						$is_active = get_option( $user_data->roles[0] . '_is_active' );
					}
					if ( '' == $block_msg_permenant ) {
						$block_msg_permenant = get_option( $user_data->roles[0] . '_block_msg_permenant' );
					}
					if ( '' == $block_url_permenant ) {
						$block_url_permenant = get_option( $user_data->roles[0] . '_block_url_permenant' );
					}
					$user_datas   .= ', ' . $user_data->user_login;
					$curr_edit_msg = esc_html__( 'Update for user with username: ', 'user-blocker' ) . ltrim( $user_datas, ', ' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'User with', 'user-blocker' ) . ' ' . $user_id . ' ' . esc_html__( 'userid is not exist.', 'user-blocker' );
				}
			}
		}
		if ( isset( $_POST['sbtSaveStatus'] ) && isset( $_POST['_wp_block_by_permenant_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_block_by_permenant_nonce'] ) ), '_wp_block_by_permenant_action' ) ) {
			// Check if username is selected in dd.
			$get_username = isset( $_POST['username'] ) ? sanitize_textarea_field( wp_unslash( $_POST['username'] ) ) : '';
			$get_role     = isset( $_POST['role'] ) ? sanitize_textarea_field( wp_unslash( $_POST['role'] ) ) : '';
			if ( isset( $_POST['cmbUserBy'] ) && 'role' == $_POST['cmbUserBy'] ) {
				$is_display_role = 1;
			}
			if ( isset( $_POST['cmbUserBy'] ) && 'username' == $_POST['cmbUserBy'] ) {
				$display_users = 1;
			}
			if ( ( '' != $get_role ) || ( $get_username ) ) {
				$user_ids          = explode( ',', $get_username );
				$user_datas        = '';
				$multi_users       = get_users( array( 'include' => $user_ids ) );
				$user_roles        = explode( ',', $get_role );
				$multi_users_roles = get_users( array( 'role__in' => $user_roles ) );
				$pattern           = '/[\-=+$@\t\r]/';
				$role_name         = '';
				if ( '' != $get_role ) {
					foreach ( $user_roles as $multi_users_role ) {
						$old_block_msg_permenant = get_option( $multi_users_role . '_block_msg_permenant' );
						$old_block_url_permenant = get_option( $multi_users_role . '_block_url_permenant' );
						update_option( $multi_users_role . '_is_active', 'n' );
						$block_msg_permenant = $default_msg;
						$block_url_permenant = '';
						if ( isset( $_POST['block_msg_permenant'] ) && '' != $_POST['block_msg_permenant'] ) {
							$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						}
						if ( isset( $_POST['block_url_permenant'] ) && '' != $_POST['block_url_permenant'] ) {
							$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						}
						if ( preg_match( $pattern, $block_msg_permenant ) ) {
							$msg_class = 'error';
							$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
						} else {
							update_option( $multi_users_role . '_block_msg_permenant', $block_msg_permenant );
							update_option( $multi_users_role . '_block_url_permenant', $block_url_permenant );
							// Update all users of this role.
							ublk_block_role_users_permenant( $multi_users_role, 'n', $old_block_msg_permenant, $block_msg_permenant, $old_block_url_permenant, $block_url_permenant );

							// Update all users of this role end.
							$role_name        = str_replace( '_', ' ', $multi_users_role );
							$msg_class        = 'updated';
							$user_datas      .= ', ' . $GLOBALS['wp_roles']->roles[ $multi_users_role ]['name'];
							$multi_users_role = ltrim( $user_datas, ', ' );
							$msg              = $multi_users_role . '\'s ' . esc_html__( 'permanent blocking has been updated successfully', 'user-blocker' );
						}
					}
				} elseif ( '' != $get_username ) {
					foreach ( $user_ids as $user_id ) {
						update_user_meta( $user_id, 'is_active', 'n' );
						$block_msg_permenant = $default_msg;
						$block_url_permenant = '';
						if ( isset( $_POST['block_msg_permenant'] ) && '' != $_POST['block_msg_permenant'] ) {
							$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						}
						if ( isset( $_POST['block_url_permenant'] ) && '' != $_POST['block_url_permenant'] ) {
							$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						}
						if ( preg_match( $pattern, $block_msg_permenant ) ) {
							$msg_class = 'error';
							$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
						} else {
							update_user_meta( $user_id, 'block_msg_permenant', $block_msg_permenant );
							update_user_meta( $user_id, 'block_url_permenant', $block_url_permenant );
							$user_info           = get_userdata( $user_id );
							$role_name          .= ', ' . $user_info->user_login;
							$msg_class           = 'updated';
							$msg                 = ltrim( $role_name, ', ' ) . '\'s ' . esc_html__( 'permanent blocking has been updated successfully', 'user-blocker' );
							$username            = '';
							$block_msg_permenant = '';
							$block_url_permenant = '';
							$reocrd_id           = array();
						}
					}
				}
				$curr_edit_msg = '';
			} else {
				$cmb_user_by = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
				if ( 'role' == $cmb_user_by ) {
					if ( isset( $_POST['chkUserRole'] ) ) {
						if ( is_array( $_POST['chkUserRole'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
						}
						$reocrd_id           = ublk_recursive_sanitize_text_field( $reocrd_id );
						$block_msg_permenant = $default_msg;
						$block_url_permenant = '';
						$pattern             = '/[\-=+$@\t\r]/';
						if ( '' != $_POST['block_msg_permenant'] ) {
							$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						}
						if ( '' != $_POST['block_url_permenant'] ) {
							$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						}
						if ( preg_match( $pattern, $block_msg_permenant ) ) {
							$msg_class = 'error';
							$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
						} else {
							foreach ( $reocrd_id as $key => $val ) {
								$old_block_msg_permenant = get_option( $val . '_block_msg_permenant' );
								$old_block_url_permenant = get_option( $val . '_block_url_permenant' );
								update_option( $val . '_is_active', 'n' );
								update_option( $val . '_block_msg_permenant', $block_msg_permenant );
								update_option( $val . '_block_url_permenant', $block_url_permenant );
								// Update all users of this role.
								ublk_block_role_users_permenant( $val, 'n', $old_block_msg_permenant, $block_msg_permenant, $old_block_url_permenant, $block_url_permenant );
								// Update all users of this role end.
							}
							$msg_class           = 'updated';
							$msg                 = esc_html__( 'Selected roles have beeen blocked successfully.', 'user-blocker' );
							$role                = '';
							$block_msg_permenant = '';
							$block_url_permenant = '';
						}
					} else {
						$msg_class           = 'error';
						$msg                 = esc_html__( 'Please select atleast one role.', 'user-blocker' );
						$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						$get_cmb_val         = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
						if ( 'role' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserRole'] ) ) {
								if ( is_array( $_POST['chkUserRole'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						} elseif ( 'username' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserUsername'] ) ) {
								if ( is_array( $_POST['chkUserUsername'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						}
					}
				} elseif ( 'username' == $cmb_user_by ) {
					if ( isset( $_POST['chkUserUsername'] ) ) {
						if ( is_array( $_POST['chkUserUsername'] ) ) {
							$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
						} else {
							$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
						}
						$reocrd_id           = ublk_recursive_sanitize_text_field( $reocrd_id );
						$block_msg_permenant = $default_msg;
						$block_url_permenant = '';
						$pattern             = '/[\-=+$@\t\r]/';
						if ( isset( $_POST['block_msg_permenant'] ) && '' != $_POST['block_msg_permenant'] ) {
							$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						}
						if ( isset( $_POST['block_url_permenant'] ) && '' != $_POST['block_url_permenant'] ) {
							$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						}
						if ( preg_match( $pattern, $block_msg_permenant ) ) {
							$msg_class = 'error';
							$msg       = esc_html__( "You're breaking our security!! Please Enter Valid Message.", 'user-blocker' );
						} else {
							foreach ( $reocrd_id as $key => $val ) {
								update_user_meta( $val, 'is_active', 'n' );
								update_user_meta( $val, 'block_msg_permenant', $block_msg_permenant );
								update_user_meta( $val, 'block_url_permenant', $block_url_permenant );
							}
							$msg_class           = 'updated';
							$msg                 = esc_html__( 'Selected users have beeen blocked successfully.', 'user-blocker' );
							$username            = '';
							$block_msg_permenant = '';
							$block_url_permenant = '';
						}
					} else {
						$msg_class           = 'error';
						$msg                 = esc_html__( 'Please select atleast one username.', 'user-blocker' );
						$block_msg_permenant = sanitize_textarea_field( wp_unslash( $_POST['block_msg_permenant'] ) );
						$block_url_permenant = sanitize_url( wp_unslash( $_POST['block_url_permenant'] ) );
						$get_cmb_val         = sanitize_text_field( wp_unslash( $_POST['cmbUserBy'] ) );
						if ( 'role' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserRole'] ) ) {
								if ( is_array( $_POST['chkUserRole'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserRole'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserRole'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						} elseif ( 'username' == $get_cmb_val ) {
							if ( isset( $_POST['chkUserUsername'] ) ) {
								if ( is_array( $_POST['chkUserUsername'] ) ) {
									$reocrd_id = array_map( 'sanitize_text_field', wp_unslash( $_POST['chkUserUsername'] ) );
								} else {
									$reocrd_id = sanitize_text_field( wp_unslash( $_POST['chkUserUsername'] ) );
								}
								$reocrd_id = ublk_recursive_sanitize_text_field( $reocrd_id );
							}
						}
					}
				}
			}
			$btn_val   = esc_html__( 'Block User', 'user-blocker' );
			$reocrd_id = array();
		}
		$user_query   = get_users( array( 'role' => 'administrator' ) );
		$admin_id     = wp_list_pluck( $user_query, 'ID' );
		$users_filter = array( 'exclude' => $admin_id );
		// Start searching.
		$txt_username = '';
		if ( '' != ublk_get_data( 'txtUsername' ) ) {
			$display_users                  = 1;
			$txt_username                   = ublk_get_data( 'txtUsername' );
			$users_filter['search']         = '*' . esc_attr( $txt_username ) . '*';
			$users_filter['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( '' != ublk_get_data( 'srole' ) ) {
				$display_users        = 1;
				$users_filter['role'] = ublk_get_data( 'srole' );
				$srole                = isset( $_GET['srole'] ) ? sanitize_text_field( wp_unslash( $_GET['srole'] ) ) : '';
			}
		}
		if ( '' != $get_username ) {
			$display_users = 1;
		}
		if ( 1 == $is_display_role ) {
			$display_users = 0;
			$cmb_user_by   = 'role';
		}
		// if order and order by set, display users.
		if ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] && isset( $_GET['order'] ) && '' != $_GET['order'] ) {
			$display_users = 1;
		}
		// Select usermode on reset searching.
		if ( isset( $_GET['resetsearch'] ) && '1' == $_GET['resetsearch'] ) {
			$display_users = 1;
		}
		if ( 1 == $display_users ) {
			$cmb_user_by = 'username';
		}
		// end.
		$users_filter['orderby'] = $orderby;
		$users_filter['order']   = $order;
		$get_users_u1            = new WP_User_Query( $users_filter );
		$total_items             = $get_users_u1->total_users;
		$total_pages             = ceil( $total_items / $records_per_page );
		$next_page               = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$users_filter['number'] = $records_per_page;
		$users_filter['offset'] = $offset;
		$prev_page              = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}
		$sr_no = 1;
		if ( isset( $paged ) && $paged > 1 ) {
			$sr_no = ( $records_per_page * ( $paged - 1 ) + 1 );
		}
		$get_users_u = new WP_User_Query( $users_filter );
		$get_users   = $get_users_u->get_results();
		if ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) {
			$msg = sanitize_text_field( wp_unslash( $_GET['msg'] ) );
		}
		if ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) {
			$msg_class = sanitize_text_field( wp_unslash( $_GET['msg_class'] ) );
		}
		?>
		<div class="wrap">
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
			<?php } ?>
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Block Users Permanently', 'user-blocker' ); ?></h2>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=block_user"><?php esc_html_e( 'Block User By Time', 'user-blocker' ); ?></a></li>
						<li><a href="?page=block_user_date"><?php esc_html_e( 'Block User By Date', 'user-blocker' ); ?></a></li>
						<li>
							<a class="current" href="?page=block_user_permenant"><?php esc_html_e( 'Block User Permanent', 'user-blocker' ); ?></a>
						</li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
					<div class="tablenav top">
						<?php
						ublk_user_category_dropdown( $cmb_user_by );
						ublk_role_selection_dropdown( $display_users, $get_roles, $srole );
						ublk_pagination( $display_users, $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'block_user_permenant' );
						?>
					</div>
					<div class="search_box">
						<?php
						ublk_user_search_field( $display_users, $txt_username, 'block_user_permenant' );
						ublk_bulk_actions_dropdown( $display_users, $get_roles, $srole );
						?>
					</div>
				</form>
				<?php
				// Role Records.
				?>
				<form method="post" action="?page=block_user_permenant" id="frmBlockUser">
					<input type="hidden" id='hidden_cmbUserBy' name="cmbUserBy" value='
					<?php
					if ( isset( $cmb_user_by ) && '' != $cmb_user_by ) {
						echo esc_attr( $cmb_user_by );
					} else {
						echo esc_attr( 'role' );
					}
					?>
					'/>
					<input type="hidden" name="paged" value="<?php echo esc_attr( $paged ); ?>" />
					<input type="hidden" name="role" value="<?php echo esc_attr( $role ); ?>" />
					<input type="hidden" name="srole" value="<?php echo esc_attr( $srole ); ?>" />
					<input type="hidden" name="username" value="<?php echo esc_attr( $username ); ?>" />
					<input type="hidden" name="txtUsername" value="<?php echo esc_attr( $txt_username ); ?>" />
					<table id="role" class="widefat post fixed user-records striped" 
					<?php
					if ( ( isset( $_POST['cmbUserBy'] ) && 'username' == $_POST['cmbUserBy'] ) || 1 == $display_users ) {
						echo 'style="display: none;width: 100%;"';
					} else {
						echo 'style="width: 100%;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Status', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<th class="user-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Status', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								$chk_user_role = $reocrd_id;
							}
							if ( $get_roles ) {
								$p_txt_username = isset( $_GET['txtUsername'] ) ? sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) : '';
								$p_srole        = isset( $_GET['srole'] ) ? sanitize_text_field( wp_unslash( $_GET['srole'] ) ) : '';
								$p_paged        = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';
								foreach ( $get_roles as $key => $value ) {
									if ( 0 == $sr_no % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}
									if ( 'administrator' == $key ) {
										continue;
									}
									if ( is_array( $chk_user_role ) && in_array( $key, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column"><input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $key ); ?>" name="chkUserRole[]" /></td>
										<td><?php echo esc_html( $value['name'] ); ?></td>
										<td class="aligntextcenter">
											<?php
											if ( get_option( $key . '_is_active' ) == 'n' ) {
												?>
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/inactive.png" alt="inactive" />
												<?php
											} else {
												?>
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/active.png" alt="active" />
												<?php
											}
											?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_msg_permenant' ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_option( $key . '_block_url_permenant' ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<a href="?page=block_user_permenant&role=<?php echo esc_attr( $key ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="edit" />
											</a>
										</td>
									</tr>
									<?php
									$sr_no++;
								}
							}
							?>
						</tbody>
					</table>
					<?php
					$is_checked = '';
					?>
					<table id="username" class="widefat post fixed user-records striped" 
					<?php
					if ( ( isset( $_POST['cmbUserBy'] ) && 'username' == $_POST['cmbUserBy'] ) || 1 == $display_users ) {
						echo 'style="display: table;"';
					} else {
						echo 'style="display: none;"';
					}
					?>
					>
						<thead>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Status', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</thead>
						<tfoot>
							<tr>
								<th class="check-column"><input type="checkbox" /></th>
								<?php
								$link_order = 'ASC';
								if ( isset( $order ) ) {
									if ( 'ASC' == $order ) {
										$link_order = 'DESC';
									} elseif ( 'DESC' == $order ) {
										$link_order = 'ASC';
									}
								}
								?>
								<th class="th-username sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
									<a href="?page=block_user_permenant&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
										<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
										<span class="sorting-indicator"></span>
									</a>
								</th>
								<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Block Message', 'user-blocker' ); ?></th>
								<th class="blk-msg aligntextcenter"><?php esc_html_e( 'Redirection URL', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Status', 'user-blocker' ); ?></th>
								<th class="tbl-action"><?php esc_html_e( 'Action', 'user-blocker' ); ?></th>
							</tr>
						</tfoot>
						<tbody>
							<?php
							$chk_user_role = array();
							$is_checked    = '';
							if ( isset( $reocrd_id ) && count( $reocrd_id ) > 0 ) {
								$chk_user_role = $reocrd_id;
							}
							if ( $get_users ) {
								$d = 1;
								foreach ( $get_users as $user ) {
									$p_txt_username = isset( $_GET['txtUsername'] ) ? sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) : '';
									$p_srole        = isset( $_GET['srole'] ) ? sanitize_text_field( wp_unslash( $_GET['srole'] ) ) : '';
									$p_paged        = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : '';

									if ( 0 == $d % 2 ) {
										$alt_class = 'alt';
									} else {
										$alt_class = '';
									}

									if ( in_array( $user->ID, $chk_user_role ) ) {
										$is_checked = 'checked="checked"';
									} else {
										$is_checked = '';
									}
									?>
									<tr class="<?php echo esc_attr( $alt_class ); ?>">
										<td class="check-column">
											<input <?php echo esc_attr( $is_checked ); ?> type="checkbox" value="<?php echo esc_attr( $user->ID ); ?>" name="chkUserUsername[]" />
										</td>
										<td><?php echo esc_html( $user->user_login ); ?></td>
										<td><?php echo esc_html( $user->display_name ); ?></td>
										<td><?php echo esc_html( $user->user_email ); ?></td>
										<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_msg_permenant', true ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php echo esc_html( ublk_disp_msg( get_user_meta( $user->ID, 'block_url_permenant', true ) ) ); ?>
										</td>
										<td class="aligntextcenter">
											<?php
											if ( 'n' == get_user_meta( $user->ID, 'is_active', true ) ) {
												?>
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/inactive.png" alt="inactive" />
												<?php
											} else {
												?>
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/active.png" alt="active" />
												<?php
											}
											?>
										</td>
										<td class="aligntextcenter">
											<a href="?page=block_user_permenant&username=<?php echo esc_attr( $user->ID ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>">
												<img src="<?php echo esc_url( UB_PLUGIN_URL ); ?>/images/edit.png" alt="edit" /></a>
										</td>
									</tr>
									<?php
									$d++;
								}
								?>
								<?php
							} else {
								?>
								<tr>
									<td colspan="8" align="center">
										<?php esc_html_e( 'No records found.', 'user-blocker' ); ?>
									</td>
								</tr>
							<?php } ?>
						</tbody>
					</table>
					<?php
					$role_name = '';
					if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
						if ( $GLOBALS['wp_roles']->is_role( sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) ) {
							$role_name = ' ' . esc_html__( 'For', 'user-blocker' ) . ' <span style="text-transform: capitalize;">' . str_replace( '_', ' ', sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) . '</span>';
						}
					}
					if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
						if ( false != get_userdata( sanitize_text_field( wp_unslash( $_GET['username'] ) ) ) ) {
							$user_info = get_userdata( sanitize_text_field( wp_unslash( $_GET['username'] ) ) );
							$role_name = ' ' . esc_html__( 'For', 'user-blocker' ) . ' ' . $user_info->user_login;
						}
					}
					?>
					<h3 class="block_msg_title">
						<?php
						esc_html_e( 'Block Message', 'user-blocker' );
						if ( isset( $curr_edit_msg ) && '' != $curr_edit_msg ) {
							?>
							<span><?php echo esc_html( $curr_edit_msg ); ?></span>
							<?php
						}
						?>
					</h3>
					<div class="block_msg_div">
						<div class="block_msg_left">
							<textarea style="width:500px;height: 110px" name="block_msg_permenant"><?php echo esc_html( stripslashes( $block_msg_permenant ) ); ?></textarea>
						</div>
						<div class="block_msg_note_div">
							<?php
							echo '<b>' . esc_html__( 'Note', 'user-blocker' ) . '</b>: ';
							esc_html_e( 'If you will not set message, default message will be ', 'user-blocker' );
							echo "'<b>" . esc_html( $default_msg ) . "</b>'";
							?>
						</div>
						<br>
						<div class="block_url_div" style="margin: 20px 0 0 0;clear: both;float: left">

							<label for="Block User Redirection" style="font-weight: 600;"> <?php esc_html_e( 'Enter Redirection URL: ', 'user-blocker' ); ?> </label>
							<input type="url" name="block_url_permenant" value="<?php echo esc_url( stripslashes( $block_url_permenant ) ); ?>" id="block_url_permenant">

						</div>
					</div>
					<?php
					if ( 'role' == $cmb_user_by || '' == $cmb_user_by ) {
						$btn_val = str_replace( 'User', 'Role', $btn_val );
					}
					?>
					<?php
					wp_nonce_field( '_wp_block_by_permenant_action', '_wp_block_by_permenant_nonce' );
					?>
					<input id="sbt-block" style="margin: 20px 0 0 0;clear: both;float: left" class="button button-primary" type="submit" name="sbtSaveStatus" value="<?php echo esc_attr( $btn_val ); ?>">
					<?php if ( isset( $btn_val ) && 'Update Blocked User' == $btn_val ) { ?>
						<a style="margin: 20px 0 0 10px;float: left;" href="<?php echo '?page=block_user_permenant'; ?>" class="button button-primary"><?php esc_html_e( 'Cancel', 'user-blocker' ); ?></a>
					<?php } ?>
				</form>
			</div>
			<?php ublk_display_support_section(); ?>
			<div class="ajax-loader"></div>
		</div>
		<?php
	}
}
