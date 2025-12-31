<?php
/**
 * Blocked User List.
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
 * Export Date Time Details.
 *
 * @param array $block_day Block day.
 * @return html Display block user list
 */
function ublk_export_date_time_details( $block_day ) {
	$sunday_time    = '';
	$monday_time    = '';
	$tuesday_time   = '';
	$wednesday_time = '';
	$thursday_time  = '';
	$friday_time    = '';
	$saturday_time  = '';
	if ( ! empty( $block_day ) ) {
		if ( array_key_exists( 'sunday', $block_day ) ) {
			$from_time = $block_day['sunday']['from'];
			$to_time   = $block_day['sunday']['to'];
			if ( '' == $from_time ) {
				$sunday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$sunday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$sunday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$sunday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'monday', $block_day ) ) {
			$from_time = $block_day['monday']['from'];
			$to_time   = $block_day['monday']['to'];
			if ( '' == $from_time ) {
				$monday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$monday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$monday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$monday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'tuesday', $block_day ) ) {
			$from_time = $block_day['tuesday']['from'];
			$to_time   = $block_day['tuesday']['to'];
			if ( '' == $from_time ) {
				$tuesday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$tuesday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$tuesday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$tuesday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'wednesday', $block_day ) ) {
			$from_time = $block_day['wednesday']['from'];
			$to_time   = $block_day['wednesday']['to'];
			if ( '' == $from_time ) {
				$wednesday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$wednesday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$wednesday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$wednesday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'thursday', $block_day ) ) {
			$from_time = $block_day['thursday']['from'];
			$to_time   = $block_day['thursday']['to'];
			if ( '' == $from_time ) {
				$thursday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$thursday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$thursday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$thursday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'friday', $block_day ) ) {
			$from_time = $block_day['friday']['from'];
			$to_time   = $block_day['friday']['to'];
			if ( '' == $from_time ) {
				$friday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$friday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$friday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$friday_time .= esc_html__( 'not set', 'user-blocker' );
		}
		if ( array_key_exists( 'saturday', $block_day ) ) {
			$from_time = $block_day['saturday']['from'];
			$to_time   = $block_day['saturday']['to'];
			if ( '' == $from_time ) {
				$saturday_time .= esc_html__( 'not set', 'user-blocker' );
			} else {
				$saturday_time .= ublk_time_to_twelve_hour( $from_time );
			}
			if ( '' != $from_time && '' != $to_time ) {
				$saturday_time .= ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_time_to_twelve_hour( $to_time );
			}
		} else {
			$saturday_time .= esc_html__( 'not set', 'user-blocker' );
		}
	} else {
		$sunday_time    .= esc_html__( 'not set', 'user-blocker' );
		$monday_time    .= esc_html__( 'not set', 'user-blocker' );
		$tuesday_time   .= esc_html__( 'not set', 'user-blocker' );
		$wednesday_time .= esc_html__( 'not set', 'user-blocker' );
		$thursday_time  .= esc_html__( 'not set', 'user-blocker' );
		$friday_time    .= esc_html__( 'not set', 'user-blocker' );
		$saturday_time  .= esc_html__( 'not set', 'user-blocker' );
	}
	$data = $sunday_time . ',' . $monday_time . ',' . $tuesday_time . ',' . $wednesday_time . ',' . $thursday_time . ',' . $friday_time . ',' . $saturday_time;
	return $data;
}
/**
 * Export Data.
 */
function user_blocker_export_data() {
	global $wpdb;
	global $wp_roles;
	$nonce = ( isset( $_POST['_wp_export_users'] ) && ! empty( $_POST['_wp_export_users'] ) ) ? sanitize_text_field( wp_unslash( $_POST['_wp_export_users'] ) ) : '';
	if ( wp_verify_nonce( $nonce, '_wp_export_users' ) ) {
		$get_roles = $wp_roles->roles;
		$orderby   = 'user_login';
		$order     = 'ASC';
		$orderby   = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$order     = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
		add_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		if ( isset( $_POST['ublk_export_blk_time'] ) && isset( $_GET['page'] ) && 'blocked_user_list' == $_GET['page'] ) {
			$meta_query_array[] = array( 'relation' => 'AND' );
			$meta_query_array[] = array( 'key' => 'block_day' );
			$meta_query_array[] = array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'is_active',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'is_active',
						'value'   => 'n',
						'compare' => '!=',
					),
				),
			);
		}
		if ( isset( $_POST['ublk_export_blk_date'] ) && isset( $_GET['page'] ) && 'datewise_blocked_user_list' == $_GET['page'] ) {
			$meta_query_array[] = array( 'relation' => 'AND' );
			$meta_query_array[] = array( 'key' => 'block_date' );
			$meta_query_array[] = array(
				array(
					'relation' => 'OR',
					array(
						'key'     => 'is_active',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => 'is_active',
						'value'   => 'n',
						'compare' => '!=',
					),
				),
			);
		}

		if ( isset( $_POST['ublk_export_blk_permanent'] ) && isset( $_GET['page'] ) && 'permanent_blocked_user_list' == $_GET['page'] ) {
			$meta_query_array[] = array(
				'key'     => 'is_active',
				'value'   => 'n',
				'compare' => '=',
			);
		}
		if ( isset( $_POST['ublk_export_blk_all_users'] ) && isset( $_GET['page'] ) && 'all_type_blocked_user_list' == $_GET['page'] ) {
			$meta_query_array[] = array(
				'relation' => 'OR',
				array(
					'key'     => 'block_date',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'is_active',
					'value'   => 'n',
					'compare' => '=',
				),
				array(
					'key'     => 'block_day',
					'compare' => 'EXISTS',
				),
			);
		}

		$filter_ary['orderby'] = $orderby;
		$filter_ary['order']   = $order;
		if ( ! empty( $meta_query_array ) ) {
			$filter_ary['meta_query'] = $meta_query_array;
		}

		/* export csv by pagination */
		$export_blocked_user_list_time      = get_user_meta( get_current_user_id(), 'ublk_list_by_time_per_page', true );
		$export_blocked_user_list_date      = get_user_meta( get_current_user_id(), 'ublk_list_by_date_per_page', true );
		$export_blocked_user_list_permanent = get_user_meta( get_current_user_id(), 'ublk_list_by_permanent_per_page', true );
		$export_blocked_user_list_alltypes  = get_user_meta( get_current_user_id(), 'ublk_list_by_alltypes_per_page', true );
		$paged                              = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;
		if ( empty( $export_blocked_user_list_time ) ) {
			$export_blocked_user_list_time = 10;
		}
		if ( empty( $export_blocked_user_list_date ) ) {
			$export_blocked_user_list_date = 10;
		}
		if ( empty( $export_blocked_user_list_permanent ) ) {
			$export_blocked_user_list_permanent = 10;
		}
		if ( empty( $export_blocked_user_list_alltypes ) ) {
			$export_blocked_user_list_alltypes = 10;
		}
		if ( isset( $_POST['ublk_export_blk_time'] ) && isset( $_GET['page'] ) && 'blocked_user_list' == $_GET['page'] ) {
			$filter_ary['number'] = $export_blocked_user_list_time;
			$offset               = ( $paged - 1 ) * $export_blocked_user_list_time;
			$filter_ary['offset'] = $offset;
		} elseif ( isset( $_POST['ublk_export_blk_date'] ) && isset( $_GET['page'] ) && 'datewise_blocked_user_list' == $_GET['page'] ) {
			$filter_ary['number'] = $export_blocked_user_list_date;
			$offset               = ( $paged - 1 ) * $export_blocked_user_list_date;
			$filter_ary['offset'] = $offset;
		} elseif ( isset( $_POST['ublk_export_blk_permanent'] ) && isset( $_GET['page'] ) && 'permanent_blocked_user_list' == $_GET['page'] ) {
			$filter_ary['number'] = $export_blocked_user_list_permanent;
			$offset               = ( $paged - 1 ) * $export_blocked_user_list_permanent;
			$filter_ary['offset'] = $offset;
		} elseif ( isset( $_POST['ublk_export_blk_all_users'] ) && isset( $_GET['page'] ) && 'all_type_blocked_user_list' == $_GET['page'] ) {
			$filter_ary['number'] = $export_blocked_user_list_alltypes;
			$offset               = ( $paged - 1 ) * $export_blocked_user_list_alltypes;
			$filter_ary['offset'] = $offset;
		}
		$get_users_u = new WP_User_Query( $filter_ary );
		remove_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$get_users = $get_users_u->get_results();

		if ( isset( $_POST['ublk_export_blk_time'] ) && isset( $_GET['page'] ) && 'blocked_user_list' == $_GET['page'] ) {
			if ( isset( $_POST['export_display'] ) && 'users' == $_POST['export_display'] ) {

				$csv_output  = 'Username, Role, Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Message,URL';
				$csv_output .= "\n";
				foreach ( $get_users as $user ) {
					$block_day     = get_user_meta( $user->ID, 'block_day', true );
					$block_msg_day = get_user_meta( $user->ID, 'block_msg_day', true );
					$block_url_day = get_user_meta( $user->ID, 'block_url_day', true );
					if ( '' == $block_day || '0' == $block_day ) {
						$block_day = get_option( $user->roles[0] . '_block_day' );
					}
					$data        = ublk_export_date_time_details( $block_day );
					$csv_output .= $user->user_login . ',' . ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) . ',' . $data . ',' . ublk_disp_msg( $block_msg_day ) . ',' . ublk_disp_msg( $block_url_day );
					$csv_output .= "\n";
				}
			}
			if ( isset( $_POST['export_display'] ) && 'roles' == $_POST['export_display'] ) {
				$csv_output = ' Role, Sunday, Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Message,URL';
				if ( $get_roles ) {
					$csv_output .= "\n";
					foreach ( $get_roles as $key => $value ) {
						$block_day     = get_option( $key . '_block_day' );
						$block_msg_day = get_option( $key . '_block_msg_day' );
						$block_url_day = get_option( $key . '_block_url_day' );
						$data          = ublk_export_date_time_details( $block_day );
						if ( ! empty( $block_day ) ) {
							$csv_output .= $value['name'] . ',' . $data . ',' . ublk_disp_msg( $block_msg_day ) . ',' . ublk_disp_msg( $block_url_day );
							$csv_output .= "\n";
						}
					}
				}
			}
			$generated_date = gmdate( 'd-m-Y His' );
			$filename       = 'User-Blocker-List-By-Time';
			$csv_file       = $csv_output;
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );                    // Forces the browser to download.
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $filename . ' ' . $generated_date . '.csv";' );
			header( 'Content-Transfer-Encoding: binary' );
			ob_start();
			echo esc_html( $csv_file );
			ob_flush();
			exit();
		}
		if ( isset( $_POST['ublk_export_blk_date'] ) && isset( $_GET['page'] ) && 'datewise_blocked_user_list' == $_GET['page'] ) {
			if ( isset( $_POST['export_display'] ) && 'users' == $_POST['export_display'] ) {
				$csv_output  = 'Username, Name, Email, Role, Block Date, Message,URL';
				$csv_output .= "\n";
				foreach ( $get_users as $user ) {
					$block_date = get_user_meta( $user->ID, 'block_date', true );
					if ( ! empty( $block_date ) ) {
						if ( array_key_exists( 'frmdate', $block_date ) && array_key_exists( 'todate', $block_date ) ) {
							$frmdate = $block_date['frmdate'];
							$todate  = $block_date['todate'];
							if ( '' != $frmdate && '' != $todate ) {
								$data = ublk_date_time_to_twelve_hour( $frmdate ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_date_time_to_twelve_hour( $todate );
							}
						}
					}
					$block_msg_date = get_user_meta( $user->ID, 'block_msg_date', true );
					$block_url_date = get_user_meta( $user->ID, 'block_url_date', true );
					$csv_output    .= $user->user_login . ',' . $user->display_name . ',' . $user->user_email . ',' . ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) . ',' . $data . ',' . ublk_disp_msg( $block_msg_date ) . ',' . $block_url_date;
					$csv_output    .= "\n";
				}
			}
			if ( isset( $_POST['export_display'] ) && 'roles' == $_POST['export_display'] ) {
				$csv_output = 'Role, Block Date, Message,URL';
				if ( $get_roles ) {
					$k           = 1;
					$csv_output .= "\n";
					foreach ( $get_roles as $key => $value ) {
						$block_date = get_option( $key . '_block_date' );
						if ( ! empty( $block_date ) && isset( $block_date ) && '' != $block_date ) {
							if ( array_key_exists( 'frmdate', $block_date ) && array_key_exists( 'todate', $block_date ) ) {
								$frmdate = $block_date['frmdate'];
								$todate  = $block_date['todate'];
								if ( '' != $frmdate && '' != $todate ) {
									$data = ublk_date_time_to_twelve_hour( $frmdate ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_date_time_to_twelve_hour( $todate );
								}
							}
						}
						$block_msg_date = get_option( $key . '_block_msg_date' );
						$block_url_date = get_option( $key . '_block_url_date' );
						if ( ! empty( $block_date ) ) {
							$csv_output .= $value['name'] . ',' . $data . ',' . ublk_disp_msg( $block_msg_date ) . ',' . $block_url_date;
							$csv_output .= "\n";
						}
					}
				}
			}
			$generated_date = gmdate( 'd-m-Y His' );
			$filename       = 'User-Blocker-List-By-Date';
			$csv_file       = $csv_output;
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );                    // Forces the browser to download.
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $filename . ' ' . $generated_date . '.csv";' );
			header( 'Content-Transfer-Encoding: binary' );
			ob_start();
			echo esc_html( $csv_file );
			ob_flush();
			exit();
		}
		if ( isset( $_POST['ublk_export_blk_permanent'] ) && isset( $_GET['page'] ) && 'permanent_blocked_user_list' == $_GET['page'] ) {
			if ( isset( $_POST['export_display'] ) && 'users' == $_POST['export_display'] ) {
				$csv_output  = 'Username, Name, Email, Role, Message,URL';
				$csv_output .= "\n";
				foreach ( $get_users as $user ) {
					$block_msg_permenant = get_user_meta( $user->ID, 'block_msg_permenant', true );
					$block_url_permenant = get_user_meta( $user->ID, 'block_url_permenant', true );

					$csv_output .= $user->user_login . ',' . $user->display_name . ',' . $user->user_email . ',' . ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) . ',' . ublk_disp_msg( $block_msg_permenant ) . ',' . $block_url_permenant;
					$csv_output .= "\n";
				}
			}
			if ( isset( $_POST['export_display'] ) && 'roles' == $_POST['export_display'] ) {
				$csv_output = 'Role, Message,URL';
				if ( $get_roles ) {
					$k           = 1;
					$csv_output .= "\n";
					foreach ( $get_roles as $key => $value ) {
						$block_msg_permenant = get_option( $key . '_block_msg_permenant' );
						$block_url_permenant = get_option( $key . '_block_url_permenant' );

						if ( ! empty( $block_msg_permenant ) ) {
							$csv_output .= $value['name'] . ',' . ublk_disp_msg( $block_msg_permenant ) . ',' . $block_url_permenant;
							$csv_output .= "\n";
						}
					}
				}
			}
			$generated_date = gmdate( 'd-m-Y His' );
			$filename       = 'User-Blocker-List-By-Permanent';
			$csv_file       = $csv_output;
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );                    // Forces the browser to download.
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $filename . ' ' . $generated_date . '.csv";' );
			header( 'Content-Transfer-Encoding: binary' );
			ob_start();
			echo esc_html( $csv_file );
			ob_flush();
			exit();
		}
		if ( isset( $_POST['ublk_export_blk_all_users'] ) && isset( $_GET['page'] ) && 'all_type_blocked_user_list' == $_GET['page'] ) {

			if ( isset( $_POST['export_display'] ) && 'users' == $_POST['export_display'] ) {
				$csv_output  = 'Username, Name, Email, Role , Message,URL';
				$csv_output .= "\n";
				foreach ( $get_users as $user ) {
					$user_id        = $user->ID;
					$block_msg_user = '';
					$is_active      = get_user_meta( $user_id, 'is_active', true );
					$block_day      = get_user_meta( $user_id, 'block_day', true );
					$block_date     = get_user_meta( $user_id, 'block_date', true );
					if ( 'n' == $is_active ) {
						$block_msg_user = get_user_meta( $user_id, 'block_msg_permenant', true );
						$block_url_user = get_user_meta( $user_id, 'block_url_permenant', true );
					} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
						$block_msg_user = get_user_meta( $user_id, 'block_msg_day', true ) . ' And ' . get_user_meta( $user_id, 'block_msg_date', true );
						$block_url_user = get_user_meta( $user_id, 'block_url_day', true ) . ' And ' . get_user_meta( $user_id, 'block_url_date', true );
					} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
						$block_msg_user = get_user_meta( $user_id, 'block_msg_day', true );
						$block_url_user = get_user_meta( $user_id, 'block_url_day', true );
					} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
						$block_msg_user = get_user_meta( $user_id, 'block_msg_date', true );
						$block_url_user = get_user_meta( $user_id, 'block_url_date', true );
					}

					$csv_output .= $user->user_login . ',' . $user->display_name . ',' . $user->user_email . ',' . ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) . ',' . ublk_disp_msg( $block_msg_user ) . ',' . $block_url_user;
					$csv_output .= "\n";
				}
			}
			if ( isset( $_POST['export_display'] ) && 'roles' == $_POST['export_display'] ) {
				$csv_output = 'Role, Message,URL';
				if ( $get_roles ) {
					$k           = 1;
					$csv_output .= "\n";
					foreach ( $get_roles as $key => $value ) {
						$block_msg_role = '';
						$is_active      = get_option( $key . '_is_active' );
						$block_day      = get_option( $key . '_block_day' );
						$block_date     = get_option( $key . '_block_date' );
						if ( 'n' == $is_active ) {
							$block_msg_role = get_option( $key . '_block_msg_permenant' );
							$block_url_role = get_option( $key . '_block_url_permenant' );
						} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day && isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
							$block_msg_role = get_option( $key . '_block_msg_day' ) . ' And ' . get_option( $key . '_block_msg_date' );
							$block_url_role = get_option( $key . '_block_url_day' ) . ' And ' . get_option( $key . '_block_url_date' );
						} elseif ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
							$block_msg_role = get_option( $key . '_block_msg_day' );
							$block_url_role = get_option( $key . '_block_url_day' );
						} elseif ( isset( $block_date ) && ! empty( $block_date ) && '' != $block_date ) {
							$block_msg_role = get_option( $key . '_block_msg_date' );
							$block_url_role = get_option( $key . '_block_url_date' );
						}

						if ( ! empty( $block_msg_role ) ) {
							$csv_output .= $value['name'] . ',' . ublk_disp_msg( $block_msg_role ) . ',' . $block_url_role;
							$csv_output .= "\n";
						}
					}
				}
			}
			$generated_date = gmdate( 'd-m-Y His' );
			$filename       = 'User-Blocker-List-By-All';
			$csv_file       = $csv_output;
			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );                    // Forces the browser to download.
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Disposition: attachment; filename="' . $filename . ' ' . $generated_date . '.csv";' );
			header( 'Content-Transfer-Encoding: binary' );
			ob_start();
			echo esc_html( $csv_file );
			ob_flush();
			exit();
		}
	}

}
add_action( 'admin_init', 'user_blocker_export_data' );
if ( ! function_exists( 'ublk_block_user_list_page' ) ) {
	/**
	 * Block User List Page.
	 */
	function ublk_block_user_list_page() {
		global $wpdb;
		global $wp_roles;
		$txt_username = '';
		$role         = '';
		$srole        = '';
		$msg_class    = '';
		$msg          = '';
		$total_pages  = '';
		$next_page    = '';
		$prev_page    = '';
		$search_arg   = '';

		$user                     = get_current_user_id();
		$screen_listbytime        = get_current_screen();
		$screen_option_listbytime = $screen_listbytime->get_option( 'per_page', 'option' );

		$limit = get_user_meta( $user, $screen_option_listbytime, true );

		$records_per_page = 10;
		if ( isset( $_GET['page'] ) && absint( $_GET['page'] ) ) {
			$records_per_page = absint( $_GET['page'] );
		} elseif ( isset( $limit ) ) {
			$records_per_page = $limit;
		} else {
			$records_per_page = get_option( 'posts_per_page' );
		}
		if ( ! isset( $records_per_page ) || empty( $records_per_page ) ) {
			$records_per_page = 10;
		}
		if ( ! isset( $limit ) || empty( $limit ) ) {
			$limit = 10;
		}
		$paged       = 1;
		$total_pages = 1;

		$orderby = 'user_login';
		$order   = 'ASC';

		$msg       = ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
		$msg_class = ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) ? sanitize_text_field( wp_unslash( $_GET['msg_class'] ) ) : '';
		$orderby   = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$order     = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
		$paged     = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$offset = ( $paged - 1 ) * $records_per_page;
		// Only for roles.
		$get_roles = $wp_roles->roles;
		// Reset users.
		if ( isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) {
			if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
				$r_username = sanitize_text_field( wp_unslash( $_GET['username'] ) );
				$user_data  = new WP_User( $r_username );
				if ( false != get_userdata( $r_username ) ) {
					delete_user_meta( $r_username, 'block_day' );
					delete_user_meta( $r_username, 'block_msg_day' );
					delete_user_meta( $r_username, 'block_url_day' );
					$msg_class = 'updated';
					$msg       = $user_data->user_login . '\'s ' . esc_html__( 'blocking time is successfully reset.', 'user-blocker' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Invalid user for reset blocking time.', 'user-blocker' );
				}
			}
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
				$reset_roles = get_users( array( 'role' => sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) );
				if ( ! empty( $reset_roles ) ) {
					foreach ( $reset_roles as $single_reset_role ) {
						$own_value  = get_user_meta( $single_reset_role->ID, 'block_day', true );
						$role_value = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_day' );
						$own_msg    = get_user_meta( $single_reset_role->ID, 'block_msg_day', true );
						$own_url    = get_user_meta( $single_reset_role->ID, 'block_url_day', true );
						$role_msg   = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_day' );
						$role_url   = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_day' );
						if ( $own_value == $role_value && $own_msg == $role_msg && $own_url == $role_url ) {
							delete_user_meta( $single_reset_role->ID, 'block_day' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_day' );
							delete_user_meta( $single_reset_role->ID, 'block_url_day' );
						}
					}
				}
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_day' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_day' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_day' );
				$msg_class = 'updated';
				$msg       = sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '\'s ' . esc_html__( 'blocking time is successfully reset.', 'user-blocker' );
			}
		}
		if ( isset( $_GET['txtUsername'] ) && '' != trim( sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) ) ) {
			$txt_username                 = sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) );
			$filter_ary['search']         = '*' . $txt_username . '*';
			$filter_ary['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] && ! isset( $_GET['reset'] ) ) {
				$filter_ary['role'] = sanitize_text_field( wp_unslash( $_GET['role'] ) );
				$srole              = sanitize_text_field( wp_unslash( $_GET['role'] ) );
			}
		}
		// end.
		if ( ( isset( $_GET['display'] ) && 'roles' == $_GET['display'] ) || ( isset( $_GET['role'] ) && '' != $_GET['role'] && isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) || ( isset( $_GET['role_edited'] ) && '' != $_GET['role_edited'] && isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ) {
			$display = 'roles';
		} else {
			$display = 'users';
		}
		add_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$meta_query_array[]       = array( 'relation' => 'AND' );
		$meta_query_array[]       = array( 'key' => 'block_day' );
		$meta_query_array[]       = array(
			array(
				'relation' => 'OR',
				array(
					'key'     => 'is_active',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'is_active',
					'value'   => 'n',
					'compare' => '!=',
				),
			),
		);
		$filter_ary['orderby']    = $orderby;
		$filter_ary['order']      = $order;
		$filter_ary['meta_query'] = $meta_query_array;
		// Query for counting results.
		$get_users_u1 = new WP_User_Query( $filter_ary );
		$total_items  = $get_users_u1->total_users;
		$total_pages  = ceil( $total_items / $records_per_page );
		$next_page    = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$filter_ary['number'] = $records_per_page;
		$filter_ary['offset'] = $offset;
		$prev_page            = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}
		/* Sr no start sith 1 on every page */
		if ( isset( $paged ) ) {
			$sr_no = 0;
			$sr_no++;
		}

		$get_users_u = new WP_User_Query( $filter_ary );
		remove_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$get_users = $get_users_u->get_results();

		?>
		<div class="wrap" id="blocked-list">
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Blocked User list', 'user-blocker' ); ?></h2>
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
			<?php } ?>
			<?php if ( isset( $_SESSION['success_msg'] ) ) { ?>
				<div class="updated is-dismissible notice settings-error">
					<p><?php echo esc_html( $_SESSION['success_msg'] ); ?></p>
					<?php
					unset( $_SESSION['success_msg'] );
					?>
				</div>
			<?php } ?>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=blocked_user_list" class="current"><?php esc_html_e( 'Blocked User List By Time', 'user-blocker' ); ?></a></li>
						<li><a href="?page=datewise_blocked_user_list"><?php esc_html_e( 'Blocked User List By Date', 'user-blocker' ); ?></a></li>
						<li><a href="?page=permanent_blocked_user_list"><?php esc_html_e( 'Blocked User List Permanently', 'user-blocker' ); ?></a></li>
						<li><a href="?page=all_type_blocked_user_list"><?php esc_html_e( 'Blocked User List', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<div class="search_box">
					<div class="tablenav top">
						<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
							<div class="actions">
								<?php
								ublk_blocked_user_category_dropdown( $display );
								ublk_blocked_role_selection_dropdown( $display, $get_roles, $srole );
								ublk_blocked_pagination( $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, $display, 'blocked_user_list' );
								?>
							</div>
							<?php ublk_search_field( $display, $txt_username, 'blocked_user_list' ); ?>
						</form>
						<form id="frmExport" method="post" class="frmExport">
							<div class="actions">
								<input type="hidden" name="export_display" class="export_display" value="<?php echo esc_attr( $display ); ?>">
								<?php
								wp_nonce_field( '_wp_export_users', '_wp_export_users' );
								?>
								<input type="submit" name="ublk_export_blk_time" value="Export CSV" class="button ublk_export_blk_time">
							</div>
						</form>
					</div>
				</div>
				<table class="widefat post role_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display: table"';}
				?>
				>
					<thead>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						$no_data = 1;
						if ( $get_roles ) {
							$k = 1;
							foreach ( $get_roles as $key => $value ) {
								$block_day       = get_option( $key . '_block_day' );
								$block_permenant = get_option( $key . '_block_permenant' );
								if ( 0 == $k % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								if ( ( 'administrator' == $key ) || ( '' == $block_day ) || ( '' != $block_permenant ) ) {
									continue;
								}
								$no_data = 0;
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td class="user-role"><?php echo esc_html( $value['name'] ); ?>
										<div class="row-actions">
											<span class="trash">
												<a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=blocked_user_list&reset=1&role=<?php echo esc_attr( $key ); ?>">
													<?php esc_html_e( 'Reset', 'user-blocker' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td>
										<?php
										$block_day = get_option( $key . '_block_day' );
										if ( isset( $block_day ) && ! empty( $block_day ) && '' != $block_day ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_msg_day = get_option( $key . '_block_msg_day' );
										echo esc_html( ublk_disp_msg( $block_msg_day ) );
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_url_day = get_option( $key . '_block_url_day' );
										echo esc_html( ublk_disp_msg( $block_url_day ) );
										?>
									</td>
								</tr>
								<?php
								$k++;
							}
							if ( 1 == $no_data ) {
								?>
								<tr>
									<td colspan="10" style="text-align:center"><?php esc_html_e( 'No records Found.', 'user-blocker' ); ?></td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="9" style="text-align:center"><?php esc_html_e( 'No records Found.', 'user-blocker' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<table class="widefat post fixed users_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display:none"';}
				?>
				>
					<thead>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Sunday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Monday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Tuesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Wednesday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Thursday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Friday', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Saturday', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if ( $get_users ) {
							foreach ( $get_users as $user ) {
								if ( 0 == $sr_no % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td align="center"><?php echo esc_html( $sr_no ); ?></td>
									<td><?php echo esc_html( $user->user_login ); ?>
										<div class="row-actions">
											<span class="trash">
												<a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=blocked_user_list&reset=1&paged=<?php echo esc_attr( $paged ); ?>&username=<?php echo esc_attr( $user->ID ); ?>&role=<?php echo esc_attr( $srole ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>">
													<?php esc_html_e( 'Reset', 'user-blocker' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td class="user-role"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
									<td>
										<?php
										$block_day = get_user_meta( $user->ID, 'block_day', true );
										if ( '' == $block_day || '0' == $block_day ) {
											$block_day = get_option( $user->roles[0] . '_block_day' );
										}
										if ( ! empty( $block_day ) ) {
											if ( array_key_exists( 'sunday', $block_day ) ) {
												$from_time = $block_day['sunday']['from'];
												$to_time   = $block_day['sunday']['to'];
												if ( '' == $from_time ) {
													esc_html_e( 'not set', 'user-blocker' );
												} else {
													echo esc_html( ublk_time_to_twelve_hour( $from_time ) );
												}
												if ( '' != $from_time && '' != $to_time ) {
													echo ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . esc_html( ublk_time_to_twelve_hour( $to_time ) );
												}
											} else {
												esc_html_e( 'not set', 'user-blocker' );
											}
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td>
										<?php
										if ( ! empty( $block_day ) ) {
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
										} else {
											esc_html_e( 'not set', 'user-blocker' );
										}
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_msg_day = get_user_meta( $user->ID, 'block_msg_day', true );
										echo esc_html( ublk_disp_msg( $block_msg_day ) );
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_url_day = get_user_meta( $user->ID, 'block_url_day', true );
										echo esc_html( ublk_disp_msg( $block_url_day ) );
										?>
									</td>
								</tr>
								<?php
								$sr_no++;
							}
						} else {
							?>
							<tr>
								<td colspan="12" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_datewise_block_user_list_page' ) ) {
	/**
	 * Datewise Block User List Page.
	 */
	function ublk_datewise_block_user_list_page() {

		global $wpdb;
		global $wp_roles;
		$txt_username = '';
		$role         = '';
		$srole        = '';
		$msg_class    = '';
		$msg          = '';
		$total_pages  = '';
		$next_page    = '';
		$prev_page    = '';
		$search_arg   = '';
		$orderby      = 'user_login';
		$order        = 'ASC';

		$user                     = get_current_user_id();
		$screen_listbydate        = get_current_screen();
		$screen_option_listbydate = $screen_listbydate->get_option( 'per_page', 'option' );
		$limit                    = get_user_meta( $user, $screen_option_listbydate, true );
		$records_per_page         = 10;
		if ( isset( $_GET['page'] ) && absint( $_GET['page'] ) ) {
			$records_per_page = absint( $_GET['page'] );
		} elseif ( isset( $limit ) ) {
			$records_per_page = $limit;
		} else {
			$records_per_page = get_option( 'posts_per_page' );
		}
		if ( ! isset( $records_per_page ) || empty( $records_per_page ) ) {
			$records_per_page = 10;
		}
		if ( ! isset( $limit ) || empty( $limit ) ) {
			$limit = 10;
		}
		$paged       = 1;
		$total_pages = 1;

		$msg       = ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
		$msg_class = ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) ? sanitize_text_field( wp_unslash( $_GET['msg_class'] ) ) : '';
		$orderby   = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$order     = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
		$paged     = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$offset = ( $paged - 1 ) * $records_per_page;
		// Only for roles.

		$get_roles = $wp_roles->roles;
		// Reset users.
		if ( isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) {
			if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
				$r_username = sanitize_text_field( wp_unslash( $_GET['username'] ) );
				$user_data  = new WP_User( $r_username );
				if ( get_userdata( $r_username ) != false ) {
					delete_user_meta( $r_username, 'block_date' );
					delete_user_meta( $r_username, 'block_msg_date' );
					delete_user_meta( $r_username, 'block_url_date' );
					$msg_class = 'updated';
					$msg       = $user_data->user_login . '\'s ' . esc_html__( 'blocking date is successfully reset.', 'user-blocker' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Invalid user for reset blocking time.', 'user-blocker' );
				}
			}
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
				$reset_roles = get_users( array( 'role' => sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) );
				if ( ! empty( $reset_roles ) ) {
					foreach ( $reset_roles as $single_reset_role ) {
						$own_value  = get_user_meta( $single_reset_role->ID, 'block_date', true );
						$role_value = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_date' );
						if ( $own_value == $role_value ) {
							delete_user_meta( $single_reset_role->ID, 'block_date' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_date' );
							delete_user_meta( $single_reset_role->ID, 'block_url_date' );
						}
					}
				}
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_date' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_date' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_date' );
			}
		}
		if ( isset( $_GET['txtUsername'] ) && '' != trim( sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) ) ) {
			$txt_username                 = sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) );
			$filter_ary['search']         = '*' . esc_attr( $txt_username ) . '*';
			$filter_ary['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] && ! isset( $_GET['reset'] ) ) {
				$filter_ary['role'] = sanitize_text_field( wp_unslash( $_GET['role'] ) );
				$srole              = sanitize_text_field( wp_unslash( $_GET['role'] ) );
			}
		}
		if ( ( isset( $_GET['display'] ) && 'roles' == $_GET['display'] ) || ( isset( $_GET['role'] ) && '' != $_GET['role'] && isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) || ( isset( $_GET['role_edited'] ) && '' != $_GET['role_edited'] && isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ) {
			$display = 'roles';
		} else {
			$display = 'users';
		}
		add_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$meta_query_array[]       = array( 'relation' => 'AND' );
		$meta_query_array[]       = array( 'key' => 'block_date' );
		$meta_query_array[]       = array(
			array(
				'relation' => 'OR',
				array(
					'key'     => 'is_active',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'is_active',
					'value'   => 'n',
					'compare' => '!=',
				),
			),
		);
		$filter_ary['orderby']    = $orderby;
		$filter_ary['order']      = $order;
		$filter_ary['meta_query'] = $meta_query_array;
		// Query for counting results.
		$get_users_u1 = new WP_User_Query( $filter_ary );
		$total_items  = $get_users_u1->total_users;
		$total_pages  = ceil( $total_items / $records_per_page );
		$next_page    = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$filter_ary['number'] = $records_per_page;
		$filter_ary['offset'] = $offset;
		$prev_page            = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}

		/* Sr no start sith 1 on every page. */
		if ( isset( $paged ) ) {
			$sr_no = 0;
			$sr_no++;
		}

		// Main query.
		$get_users_u = new WP_User_Query( $filter_ary );
		remove_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$get_users = $get_users_u->get_results();
		?>
		<div class="wrap" id="blocked-list">
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Date Wise Blocked User list', 'user-blocker' ); ?></h2>
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
				<?php
			}
			?>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=blocked_user_list"><?php esc_html_e( 'Blocked User List By Time', 'user-blocker' ); ?></a></li>
						<li><a class="current" href="?page=datewise_blocked_user_list"><?php esc_html_e( 'Blocked User List By Date', 'user-blocker' ); ?></a></li>
						<li><a href="?page=permanent_blocked_user_list"><?php esc_html_e( 'Blocked User List Permanently', 'user-blocker' ); ?></a></li>
						<li><a href="?page=all_type_blocked_user_list"><?php esc_html_e( 'Blocked User List', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<div class="search_box">
					<div class="tablenav top">
						<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
							<div class="actions">
								<?php
								ublk_blocked_user_category_dropdown( $display );
								ublk_blocked_role_selection_dropdown( $display, $get_roles, $srole );
								ublk_blocked_pagination( $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'datewise_blocked_user_list' );
								?>
							</div>
							<?php ublk_search_field( $display, $txt_username, 'datewise_blocked_user_list' ); ?>

						</form>
						<form id="frmExport" method="post" class="frmExport">
							<div class="actions">
								<input type="hidden" name="export_display" class="export_display" value="<?php echo esc_attr( $display ); ?>">
								<?php
								wp_nonce_field( '_wp_export_users', '_wp_export_users' );
								?>
								<input type="submit" name="ublk_export_blk_date" value="Export CSV" class="button ublk_export_blk_date">
							</div>
						</form>
					</div>
				</div>
				<table class="widefat post role_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display: table"';}
				?>
				>
					<thead>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="blk-date"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						$no_data = 1;
						if ( $get_roles ) {
							$k = 1;
							foreach ( $get_roles as $key => $value ) {
								$block_date      = get_option( $key . '_block_date' );
								$block_permenant = get_option( $key . '_block_permenant' );
								if ( 0 == $k % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								if ( 'administrator' == $key || '' == $block_date || '' != $block_permenant ) {
									continue;
								}
								$no_data = 0;
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td class="user-role"><?php echo esc_html( $value['name'] ); ?>
										<div class="row-actions">
											<span class="trash"><a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=datewise_blocked_user_list&reset=1&role=<?php echo esc_attr( $key ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a></span>
										</div>
									</td>
									<td>
										<?php
										if ( ! empty( $block_date ) && isset( $block_date ) && '' != $block_date ) {
											if ( array_key_exists( 'frmdate', $block_date ) && array_key_exists( 'todate', $block_date ) ) {
												$frmdate = $block_date['frmdate'];
												$todate  = $block_date['todate'];
												if ( '' != $frmdate && '' != $todate ) {
													echo esc_html( ublk_date_time_to_twelve_hour( $frmdate ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_date_time_to_twelve_hour( $todate ) );
												}
											}
										}
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_msg_date = get_option( $key . '_block_msg_date' );
										echo esc_html( ublk_disp_msg( $block_msg_date ) );
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_url_date = get_option( $key . '_block_url_date' );
										echo esc_html( ublk_disp_msg( $block_url_date ) );
										?>
									</td>
								</tr>
								<?php
								$k++;
							}
							if ( 1 == $no_data ) {
								?>
								<tr>
									<td colspan="4" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="3" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<table class="widefat post fixed users_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display:none"';}
				?>
				>
					<thead>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=datewise_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=datewise_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=datewise_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=datewise_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=datewise_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=datewise_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th class="th-time"><?php esc_html_e( 'Block Date', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if ( $get_users ) {
							foreach ( $get_users as $user ) {
								if ( 0 == $sr_no % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td align="center"><?php echo esc_html( $sr_no ); ?></td>
									<td><?php echo esc_html( $user->user_login ); ?>
										<div class="row-actions">
											<span class="trash">
												<a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=datewise_blocked_user_list&reset=1&paged=<?php echo esc_attr( $paged ); ?>&username=<?php echo esc_attr( $user->ID ); ?>&role=<?php echo esc_attr( $srole ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>">
													<?php esc_html_e( 'Reset', 'user-blocker' ); ?>
												</a>
											</span>
										</div>
									</td>
									<td><?php echo esc_html( $user->display_name ); ?></td>
									<td><?php echo esc_html( $user->user_email ); ?></td>
									<td class="user-role"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
									<td>
										<?php

										$block_date = get_user_meta( $user->ID, 'block_date', true );
										if ( ! empty( $block_date ) ) {
											if ( array_key_exists( 'frmdate', $block_date ) && array_key_exists( 'todate', $block_date ) ) {
												$frmdate = $block_date['frmdate'];
												$todate  = $block_date['todate'];
												if ( '' != $frmdate && '' != $todate ) {
													echo esc_html( ublk_date_time_to_twelve_hour( $frmdate ) . ' ' . esc_html__( 'to', 'user-blocker' ) . ' ' . ublk_date_time_to_twelve_hour( $todate ) );
												}
											}
										}
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_msg_date = get_user_meta( $user->ID, 'block_msg_date', true );
										echo esc_html( ublk_disp_msg( $block_msg_date ) );
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_url_date = get_user_meta( $user->ID, 'block_url_date', true );
										echo esc_html( ublk_disp_msg( $block_url_date ) );
										?>
									</td>
								</tr>
								<?php
								$sr_no++;
							}
						} else {
							?>
							<tr>
								<td colspan="8" style="text-align:center">
									<?php esc_html_e( 'No Record Found.', 'user-blocker' ); ?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_permanent_block_user_list_page' ) ) {
	/**
	 * Permanent Block User List Page.
	 */
	function ublk_permanent_block_user_list_page() {
		global $wpdb;
		global $wp_roles;
		$txt_username = '';
		$role         = '';
		$srole        = '';
		$msg_class    = '';
		$msg          = '';
		$total_pages  = '';
		$next_page    = '';
		$prev_page    = '';
		$search_arg   = '';
		$orderby      = 'user_login';
		$order        = 'ASC';

		$user                          = get_current_user_id();
		$screen_listbypermanent        = get_current_screen();
		$screen_option_listbypermanent = $screen_listbypermanent->get_option( 'per_page', 'option' );
		$limit                         = get_user_meta( $user, $screen_option_listbypermanent, true );
		$records_per_page              = 10;
		if ( isset( $_GET['page'] ) && absint( $_GET['page'] ) ) {
			$records_per_page = absint( $_GET['page'] );
		} elseif ( isset( $limit ) ) {
			$records_per_page = $limit;
		} else {
			$records_per_page = get_option( 'posts_per_page' );
		}
		if ( ! isset( $records_per_page ) || empty( $records_per_page ) ) {
			$records_per_page = 10;
		}
		if ( ! isset( $limit ) || empty( $limit ) ) {
			$limit = 10;
		}
		$paged       = 1;
		$total_pages = 1;

		$msg       = ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
		$msg_class = ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) ? sanitize_text_field( wp_unslash( $_GET['msg_class'] ) ) : '';
		$orderby   = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$order     = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
		$paged     = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}
		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$offset = ( $paged - 1 ) * $records_per_page;
		// Only for roles.
		$get_roles = $wp_roles->roles;
		// Reset users.
		if ( isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) {
			if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
				$r_username = sanitize_text_field( wp_unslash( $_GET['username'] ) );
				$user_data  = new WP_User( $r_username );
				if ( get_userdata( $r_username ) != false ) {
					delete_user_meta( $r_username, 'is_active' );
					delete_user_meta( $r_username, 'block_msg_permenant' );
					delete_user_meta( $r_username, 'block_url_permenant' );
					$msg_class = 'updated';
					$msg       = $user_data->user_login . '\'s ' . esc_html__( 'blocking time is successfully reset.', 'user-blocker' );
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Invalid user for reset blocking time.', 'user-blocker' );
				}
			}
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
				$reset_roles = get_users( array( 'role' => sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) );
				if ( ! empty( $reset_roles ) ) {
					foreach ( $reset_roles as $single_reset_role ) {
						$own_value  = get_user_meta( $single_reset_role->ID, 'is_active', true );
						$role_value = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_is_active' );
						if ( $own_value == $role_value ) {
							delete_user_meta( $single_reset_role->ID, 'is_active' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_permenant' );
							delete_user_meta( $single_reset_role->ID, 'block_url_permenant' );
						}
					}
				}
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_is_active' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_permenant' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_permenant' );
				$msg_class = 'updated';
				$msg       = sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '\'s ' . esc_html__( 'blocking time is successfully reset.', 'user-blocker' );
			}
		}
		if ( isset( $_GET['txtUsername'] ) && '' != trim( sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) ) ) {
			$txt_username                 = sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) );
			$filter_ary['search']         = '*' . $txt_username . '*';
			$filter_ary['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] && ! isset( $_GET['reset'] ) ) {
				$filter_ary['role'] = sanitize_text_field( wp_unslash( $_GET['role'] ) );
				$srole              = sanitize_text_field( wp_unslash( $_GET['role'] ) );
			}
		}
		if ( ( isset( $_GET['display'] ) && 'roles' == $_GET['display'] ) || ( isset( $_GET['role'] ) && '' != $_GET['role'] && isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) || ( isset( $_GET['role_edited'] ) && '' != $_GET['role_edited'] && isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ) {
			$display = 'roles';
		} else {
			$display = 'users';
		}
		$filter_ary['orderby']    = $orderby;
		$filter_ary['order']      = $order;
		$meta_query_array[]       = array(
			'key'     => 'is_active',
			'value'   => 'n',
			'compare' => '=',
		);
		$filter_ary['meta_query'] = $meta_query_array;
		// Query for counting results.
		$get_users_u1 = new WP_User_Query( $filter_ary );
		$total_items  = $get_users_u1->total_users;
		$total_pages  = ceil( $total_items / $records_per_page );
		$next_page    = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$filter_ary['number'] = $records_per_page;
		$filter_ary['offset'] = $offset;
		$prev_page            = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}

		/* Sr no start sith 1 on every page. */
		if ( isset( $paged ) ) {
			$sr_no = 0;
			$sr_no++;
		}

		// Main query.
		$get_users_u = new WP_User_Query( $filter_ary );
		$get_users   = $get_users_u->get_results();
		?>
		<div class="wrap" id="blocked-list">
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Permanently Blocked User list', 'user-blocker' ); ?></h2>
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
				<?php
			}
			?>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=blocked_user_list"><?php esc_html_e( 'Blocked User List By Time', 'user-blocker' ); ?></a></li>
						<li><a href="?page=datewise_blocked_user_list"><?php esc_html_e( 'Blocked User List By Date', 'user-blocker' ); ?></a></li>
						<li><a class="current" href="?page=permanent_blocked_user_list"><?php esc_html_e( 'Blocked User List Permanently', 'user-blocker' ); ?></a></li>
						<li><a href="?page=all_type_blocked_user_list"><?php esc_html_e( 'Blocked User List', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<div class="search_box">
					<div class="tablenav top">
						<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
							<div class="actions">
								<?php
								ublk_blocked_user_category_dropdown( $display );
								ublk_blocked_role_selection_dropdown( $display, $get_roles, $srole );
								ublk_blocked_pagination( $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'permanent_blocked_user_list' );
								?>
							</div>
							<?php ublk_search_field( $display, $txt_username, 'permanent_blocked_user_list' ); ?>
						</form>
						<form id="frmExport" method="post" class="frmExport">
							<div class="actions">
								<input type="hidden" name="export_display" class="export_display" value="<?php echo esc_attr( $display ); ?>">
								<?php
								wp_nonce_field( '_wp_export_users', '_wp_export_users' );
								?>
								<input type="submit" name="ublk_export_blk_permanent" value="Export CSV" class="button ublk_export_blk_permanent">
							</div>
						</form>
					</div>
				</div>
				<table class="widefat post role_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display: table"';}
				?>
				>
					<thead>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						$no_data = 1;
						if ( $get_roles ) {
							$k = 1;
							foreach ( $get_roles as $key => $value ) {
								$block_permenant = get_option( $key . '_is_active' );
								if ( 0 == $k % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								if ( 'administrator' == $key || 'n' != $block_permenant ) {
									continue;
								}
								$no_data = 0;
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td class="user-role"><?php echo esc_attr( $value['name'] ); ?>
										<div class="row-actions">
											<span class="trash"><a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=permanent_blocked_user_list&reset=1&role=<?php echo esc_attr( $key ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a></span>
										</div>
									</td>
									<td style="text-align:center">
										<?php
										$block_msg_permenant = get_option( $key . '_block_msg_permenant' );
										echo esc_html( ublk_disp_msg( $block_msg_permenant ) );
										?>
									</td>
									<td style="text-align:center">
										<?php
										$block_url_permenant = get_option( $key . '_block_url_permenant' );
										echo esc_html( ublk_disp_msg( $block_url_permenant ) );
										?>
									</td>
								</tr>
								<?php
								$k++;
							}
							if ( 1 == $no_data ) {
								?>
								<tr>
									<td colspan="3" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="2" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<table class="widefat post fixed users_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display:none"';}
				?>
				>
					<thead>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=permanent_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=permanent_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=permanent_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=permanent_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=permanent_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=permanent_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-time"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if ( $get_users ) {
							foreach ( $get_users as $user ) {
								if ( 0 == $sr_no % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td align="center"><?php echo esc_html( $sr_no ); ?></td>
									<td><?php echo esc_html( $user->user_login ); ?>
										<div class="row-actions">
											<span class="trash"><a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=permanent_blocked_user_list&reset=1&paged=<?php echo esc_attr( $paged ); ?>&username=<?php echo esc_attr( $user->ID ); ?>&role=<?php echo esc_attr( $srole ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a></span>
										</div>
									</td>
									<td><?php echo esc_attr( $user->display_name ); ?></td>
									<td><?php echo esc_attr( $user->user_email ); ?></td>
									<td class="user-role"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
									<td style="text-align:center">
										<?php
										$block_msg_permenant = get_user_meta( $user->ID, 'block_msg_permenant', true );
										echo esc_html( ublk_disp_msg( $block_msg_permenant ) );
										?>
									</td>

									<td style="text-align:center">
										<?php
										$block_url_permenant = get_user_meta( $user->ID, 'block_url_permenant', true );
										echo esc_html( ublk_disp_msg( $block_url_permenant ) );
										?>
									</td>
								</tr>
								<?php
								$sr_no++;
							}
						} else {
							?>
							<tr>
								<td colspan="7" style="text-align:center">
									<?php esc_html_e( 'No records Found.', 'user-blocker' ); ?>
								</td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ublk_all_type_block_user_list_page' ) ) {
	/**
	 * All Type of Block User List Page.
	 */
	function ublk_all_type_block_user_list_page() {
		global $wpdb;
		global $wp_roles;
		$txt_username = '';
		$role         = '';
		$srole        = '';
		$msg_class    = '';
		$msg          = '';
		$total_pages  = '';
		$next_page    = '';
		$prev_page    = '';
		$search_arg   = '';

		$records_per_page            = 10;
		$user                        = get_current_user_id();
		$screen_listbyalltype        = get_current_screen();
		$screen_option_listbyalltype = $screen_listbyalltype->get_option( 'per_page', 'option' );
		$limit                       = get_user_meta( $user, $screen_option_listbyalltype, true );
		$records_per_page            = 10;
		if ( isset( $_GET['page'] ) && absint( $_GET['page'] ) ) {
			$records_per_page = absint( $_GET['page'] );
		} elseif ( isset( $limit ) ) {
			$records_per_page = $limit;
		} else {
			$records_per_page = get_option( 'posts_per_page' );
		}
		if ( ! isset( $records_per_page ) || empty( $records_per_page ) ) {
			$records_per_page = 10;
		}
		if ( ! isset( $limit ) || empty( $limit ) ) {
			$limit = 10;
		}
		$paged       = 1;
		$total_pages = 1;

		$orderby = 'user_login';
		$order   = 'ASC';

		$msg       = ( isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ? sanitize_text_field( wp_unslash( $_GET['msg'] ) ) : '';
		$msg_class = ( isset( $_GET['msg_class'] ) && '' != $_GET['msg_class'] ) ? sanitize_text_field( wp_unslash( $_GET['msg_class'] ) ) : '';
		$orderby   = ( isset( $_GET['orderby'] ) && '' != $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'user_login';
		$order     = ( isset( $_GET['order'] ) && '' != $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
		$paged     = isset( $_GET['paged'] ) ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) : 1;

		if ( ! is_numeric( $paged ) ) {
			$paged = 1;
		}

		if ( isset( $_REQUEST['filter_action'] ) ) {
			if ( 'Search' == $_REQUEST['filter_action'] ) {
				$paged = 1;
			}
		}

		$offset = ( $paged - 1 ) * $records_per_page;
		// Only for roles.

		$get_roles = $wp_roles->roles;
		// Reset users.
		if ( isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) {
			if ( isset( $_GET['username'] ) && '' != $_GET['username'] ) {
				$r_username = sanitize_text_field( wp_unslash( $_GET['username'] ) );
				$user_data  = new WP_User( $r_username );
				if ( get_userdata( $r_username ) != false ) {
					delete_user_meta( $r_username, 'block_day' );
					delete_user_meta( $r_username, 'block_msg_date' );
					delete_user_meta( $r_username, 'block_date' );
					delete_user_meta( $r_username, 'block_msg_date' );
					delete_user_meta( $r_username, 'is_active' );
					delete_user_meta( $r_username, 'block_msg_permenant' );
					delete_user_meta( $r_username, 'block_msg_day' );
					delete_user_meta( $r_username, 'block_url_day' );
					delete_user_meta( $r_username, 'block_url_date' );
					delete_user_meta( $r_username, 'block_url_permenant' );
					$msg_class = 'updated';
					$msg       = $user_data->user_login . '\'s blocking is successfully reset.';
				} else {
					$msg_class = 'error';
					$msg       = esc_html__( 'Invalid user for reset blocking.', 'user-blocker' );
				}
			}
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] ) {
				$reset_roles = get_users( array( 'role' => sanitize_text_field( wp_unslash( $_GET['role'] ) ) ) );
				if ( ! empty( $reset_roles ) ) {
					foreach ( $reset_roles as $single_reset_role ) {
						// Permenant block data.
						$own_value      = get_user_meta( $single_reset_role->ID, 'is_active', true );
						$role_value     = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_is_active' );
						$own_value_msg  = get_user_meta( $single_reset_role->ID, 'block_msg_permenant', true );
						$own_value_url  = get_user_meta( $single_reset_role->ID, 'block_url_permenant', true );
						$role_value_msg = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_permenant' );
						$role_value_url = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_permenant' );
						if ( ( $own_value == $role_value ) && ( $own_value_msg == $role_value_msg ) && ( $own_value_url == $role_value_url ) ) {
							delete_user_meta( $single_reset_role->ID, 'is_active' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_permenant' );
							delete_user_meta( $single_reset_role->ID, 'block_url_permenant' );
						}
						// Date wise block data.
						$own_value_date      = get_user_meta( $single_reset_role->ID, 'block_date', true );
						$role_value_date     = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_date' );
						$own_value_date_msg  = get_user_meta( $single_reset_role->ID, 'block_msg_date', true );
						$role_value_date_msg = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_date' );
						$own_value_date_url  = get_user_meta( $single_reset_role->ID, 'block_url_date', true );
						$role_value_date_url = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_date', true );
						if ( ( $own_value_date == $role_value_date ) && ( $own_value_date_msg == $role_value_date_msg ) && ( $own_value_date_url == $role_value_date_url ) ) {
							delete_user_meta( $single_reset_role->ID, 'block_date' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_date' );
							delete_user_meta( $single_reset_role->ID, 'block_url_date' );
						}
						// Day wise block data.
						$own_value_day      = get_user_meta( $single_reset_role->ID, 'block_day', true );
						$role_value_day     = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_day' );
						$own_value_day_msg  = get_user_meta( $single_reset_role->ID, 'block_msg_day', true );
						$role_value_day_msg = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_day' );
						$own_value_day_url  = get_user_meta( $single_reset_role->ID, 'block_url_day', true );
						$role_value_day_url = get_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_day' );
						if ( ( $own_value_day == $role_value_day ) && ( $own_value_day_msg == $role_value_day_msg ) && ( $own_value_day_url == $role_value_day_url ) ) {
							delete_user_meta( $single_reset_role->ID, 'block_day' );
							delete_user_meta( $single_reset_role->ID, 'block_msg_day' );
							delete_user_meta( $single_reset_role->ID, 'block_url_day' );
						}
					}
				}
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_is_active' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_date' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_day' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_permenant' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_date' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_msg_day' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_day' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_date' );
				delete_option( sanitize_text_field( wp_unslash( $_GET['role'] ) ) . '_block_url_permenant' );

				$msg_class = 'updated';
				$msg       = sanitize_text_field( wp_unslash( $_GET['role'] ) ) . esc_html_e( "\'s blocking is successfully reset.", 'user-blocker' );
			}
		}
		if ( isset( $_GET['txtUsername'] ) && '' != trim( sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) ) ) ) {
			$txt_username                 = sanitize_text_field( wp_unslash( $_GET['txtUsername'] ) );
			$filter_ary['search']         = '*' . esc_attr( $txt_username ) . '*';
			$filter_ary['search_columns'] = array(
				'user_login',
				'user_nicename',
				'user_email',
				'display_name',
			);
		}
		if ( '' == $txt_username ) {
			if ( isset( $_GET['role'] ) && '' != $_GET['role'] && ! isset( $_GET['reset'] ) ) {
				$filter_ary['role'] = sanitize_text_field( wp_unslash( $_GET['role'] ) );
				$srole              = sanitize_text_field( wp_unslash( $_GET['role'] ) );
			}
		}
		// end.
		if ( ( isset( $_GET['display'] ) && 'roles' == $_GET['display'] ) || ( isset( $_GET['role'] ) && '' != $_GET['role'] && isset( $_GET['reset'] ) && '1' == $_GET['reset'] ) || ( isset( $_GET['role_edited'] ) && '' != $_GET['role_edited'] && isset( $_GET['msg'] ) && '' != $_GET['msg'] ) ) {
			$display = 'roles';
		} else {
			$display = 'users';
		}

		$filter_ary['orderby']    = $orderby;
		$filter_ary['order']      = $order;
		$meta_query_array[]       = array(
			'relation' => 'OR',
			array(
				'key'     => 'block_date',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => 'is_active',
				'value'   => 'n',
				'compare' => '=',
			),
			array(
				'key'     => 'block_day',
				'compare' => 'EXISTS',
			),
		);
		$filter_ary['meta_query'] = $meta_query_array;
		add_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		// Query for counting results.
		$get_users_u1 = new WP_User_Query( $filter_ary );
		$total_items  = $get_users_u1->total_users;
		$total_pages  = ceil( $total_items / $records_per_page );
		$next_page    = (int) $paged + 1;
		if ( $next_page > $total_pages ) {
			$next_page = $total_pages;
		}
		$filter_ary['number'] = $records_per_page;
		$filter_ary['offset'] = $offset;
		$prev_page            = (int) $paged - 1;
		if ( $prev_page < 1 ) {
			$prev_page = 1;
		}

		/* Sr no start sith 1 on every page. */
		if ( isset( $paged ) ) {
			$sr_no = 0;
			$sr_no++;
		}

		// Main query.
		$get_users_u = new WP_User_Query( $filter_ary );
		remove_filter( 'pre_user_query', 'ublk_sort_by_member_number' );
		$get_users = $get_users_u->get_results();
		?>
		<div class="wrap" id="blocked-list">
			<h2 class="ublocker-page-title"><?php esc_html_e( 'Blocked User list', 'user-blocker' ); ?></h2>
			<?php
			// Display success/error messages.
			if ( '' != $msg ) {
				?>
				<div class="ublocker-notice <?php echo esc_attr( $msg_class ); ?>">
					<p><?php echo esc_html( $msg ); ?></p>
				</div>
				<?php
			}
			?>
			<div class="tab_parent_parent">
				<div class="tab_parent">
					<ul>
						<li><a href="?page=blocked_user_list"><?php esc_html_e( 'Blocked User List By Time', 'user-blocker' ); ?></a></li>
						<li><a href="?page=datewise_blocked_user_list"><?php esc_html_e( 'Blocked User List By Date', 'user-blocker' ); ?></a></li>
						<li><a href="?page=permanent_blocked_user_list"><?php esc_html_e( 'Blocked User List Permanently', 'user-blocker' ); ?></a></li>
						<li><a class='current' href="?page=all_type_blocked_user_list"><?php esc_html_e( 'Blocked User List', 'user-blocker' ); ?></a></li>
					</ul>
				</div>
			</div>
			<div class="cover_form">
				<div class="search_box">
					<div class="tablenav top">
						<form id="frmSearch" name="frmSearch" method="get" action="<?php echo esc_url( home_url() . '/wp-admin/admin.php' ); ?>">
							<div class="actions">
								<?php
								ublk_blocked_user_category_dropdown( $display );
								ublk_blocked_role_selection_dropdown( $display, $get_roles, $srole );
								ublk_blocked_pagination( $total_pages, $total_items, $paged, $prev_page, $next_page, $srole, $txt_username, $orderby, $order, 'all_type_blocked_user_list' );
								?>
							</div>
							<?php ublk_search_field( $display, $txt_username, 'all_type_blocked_user_list' ); ?>
						</form>
						<form id="frmExport" method="post" class="frmExport">
							<div class="actions">
								<input type="hidden" name="export_display" class="export_display" value="<?php echo esc_attr( $display ); ?>">
								<?php
								wp_nonce_field( '_wp_export_users', '_wp_export_users' );
								?>
								<input type="submit" name="ublk_export_blk_all_users" value="<?php esc_attr_e( 'Export CSV', 'user-blocker' ); ?>" class="button ublk_export_blk_all_users">
							</div>
						</form>
					</div>
				</div>
				<table class="widefat post role_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display: table"';}
				?>
				>
					<thead>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
							<th class="th-username"><?php esc_html_e( 'Block Data', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="th-role"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
							<th class="th-username"><?php esc_html_e( 'Block Data', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						$no_data = 1;
						if ( $get_roles ) {
							$k = 1;
							foreach ( $get_roles as $key => $value ) {
								$block_day  = get_option( $key . '_block_day' );
								$block_date = get_option( $key . '_block_date' );
								$is_active  = get_option( $key . '_is_active' );
								if ( 'administrator' == $key || ( 'n' != $is_active && '' == $block_date && '' == $block_day ) ) {
									continue;
								}
								if ( 0 == $k % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								$no_data = 0;
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td class="user-role"><?php echo esc_html( $value['name'] ); ?>
										<div class="row-actions">
											<span class="trash"><a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=all_type_blocked_user_list&reset=1&role=<?php echo esc_attr( $key ); ?>&orderby=<?php echo esc_attr( $orderby ); ?>&order=<?php echo esc_attr( $order ); ?>"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a></span>
										</div>
									</td>
									<td style="text-align:center">
										<?php ublk_all_block_data_msg_role( $key ); ?>
									</td>
									<td style="text-align:center">
										<?php ublk_all_block_data_url_role( $key ); ?>
									</td>
									<td>
										<?php ublk_all_block_data_view_role( $key ); ?>
									</td>
								</tr>
								<?php
								echo esc_html( ublk_all_block_data_table_role( $key ) );
								$k++;
							}
							if ( 1 == $no_data ) {
								?>
								<tr>
									<td colspan="4" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
								</tr>
								<?php
							}
						} else {
							?>
							<tr>
								<td colspan="3" style="text-align:center"><?php esc_html_e( 'No records found.', 'user-blocker' ); ?></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
				<table class="widefat post fixed users_records striped" 
				<?php
				if ( 'roles' == $display ) {
					echo 'style="display:none"';}
				?>
				>
					<thead>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=all_type_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=all_type_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=all_type_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-username"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
							<th class="th-username aligntextcenter"><?php esc_html_e( 'Block Data', 'user-blocker' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th class="sr-no"><?php esc_html_e( 'S.N.', 'user-blocker' ); ?></th>
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
								<a href="?page=all_type_blocked_user_list&orderby=user_login&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Username', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-name sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=all_type_blocked_user_list&orderby=display_name&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Name', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-email sortable <?php echo esc_attr( strtolower( $order ) ); ?>">
								<a href="?page=all_type_blocked_user_list&orderby=user_email&order=<?php echo esc_attr( $link_order ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>&srole=<?php echo esc_attr( $srole ); ?>&paged=<?php echo esc_attr( $paged ); ?>">
									<span><?php esc_html_e( 'Email', 'user-blocker' ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th class="th-username"><?php esc_html_e( 'Role', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'Message', 'user-blocker' ); ?></th>
							<th style="text-align:center"><?php esc_html_e( 'URL', 'user-blocker' ); ?></th>
							<th class="th-username aligntextcenter"><?php esc_html_e( 'Block Data', 'user-blocker' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						if ( $get_users ) {
							foreach ( $get_users as $user ) {
								if ( 0 == $sr_no % 2 ) {
									$alt_class = 'alt';
								} else {
									$alt_class = '';
								}
								?>
								<tr class="<?php echo esc_attr( $alt_class ); ?>">
									<td align="center"><?php echo esc_html( $sr_no ); ?></td>
									<td><?php echo esc_html( $user->user_login ); ?>
										<div class="row-actions">
											<span class="trash"><a title="<?php esc_html_e( 'Reset this item', 'user-blocker' ); ?>" href="?page=all_type_blocked_user_list&reset=1&paged=<?php echo esc_attr( $paged ); ?>&username=<?php echo esc_attr( $user->ID ); ?>&role=<?php echo esc_attr( $srole ); ?>&txtUsername=<?php echo esc_attr( $txt_username ); ?>"><?php esc_html_e( 'Reset', 'user-blocker' ); ?></a></span>
										</div>
									</td>
									<td><?php echo esc_html( $user->display_name ); ?></td>
									<td><?php echo esc_html( $user->user_email ); ?></td>
									<td class="user-role"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $user->roles[0] ) ) ); ?></td>
									<td style="text-align:center">

										<?php ublk_all_block_data_msg( $user->ID ); ?>
									</td>
									<td style="text-align:center">

										<?php ublk_all_block_data_url( $user->ID ); ?>
									</td>
									<td class="aligntextcenter">
										<?php echo esc_html( ublk_all_block_data_view( $user->ID ) ); ?>
									</td>
								</tr>
								<?php
								echo esc_html( ublk_all_block_data_table( $user->ID ) );
								$sr_no++;
							}
						} else {
							echo '<tr><td colspan="8" style="text-align:center">' . esc_html__( 'No records found.', 'user-blocker' ) . '</td></tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
	}
}
