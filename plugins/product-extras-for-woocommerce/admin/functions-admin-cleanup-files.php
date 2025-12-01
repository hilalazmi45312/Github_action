<?php
/**
 * Functions for cleaning up uploaded files
 * @since 3.23.1
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Confirm before delete, i.e. display first a list of files to be deleted
 * @since 3.23.1
 */
function pewc_clean_up_files_confirm_before_delete() {
	return apply_filters( 'pewc_filter_clean_up_confirm_before_delete', true );
}

/**
 * Enqueue script used on the admin side
 * @since 3.23.1
 */
function pewc_enqueue_clean_up_files_script( $hook ) {

	// 3.26.7, updated condition because the string product-add-ons in $hook gets translated and the previous condition fails because of it
	if ( false === strpos( $hook, 'pewc-clean-up-files' ) ) {
		return; // only load this script on our Clean Up page
	}
	$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;
	wp_enqueue_style( 'pewc-admin-clean-up-files-style', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/admin-clean-up-files.css', array(), $version );
	wp_register_script( 'pewc-admin-clean-up-files-script', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/' . 'admin-clean-up-files.js', array( 'pewc-admin-script' ), $version, true );
	wp_enqueue_script( 'pewc-admin-clean-up-files-script' );

	$params = array(
		'maxDate' => apply_filters( 'pewc_filter_clean_up_date_max', '-2M' ), // default: 2 months
		'confirm_before_delete' => pewc_clean_up_files_confirm_before_delete() ? 'yes' : 'no'
	);

	wp_localize_script(
		'pewc-admin-clean-up-files-script',
		'pewc_admin_clean_up_files_vars',
		$params
	);

}
add_action( 'admin_enqueue_scripts', 'pewc_enqueue_clean_up_files_script', 100 );

/**
 * Add the Clean Up page to the Add-Ons menu in the admin
 * @since 3.23.1
 */
function pewc_register_clean_up_page() {
	add_submenu_page(
		'pewc_home',
		__( 'Clean Up Files', 'pewc' ),
		__( 'Clean Up Files', 'pewc' ),
		apply_filters( 'pewc_clean_up_files_capability', 'manage_options' ),
		'pewc-clean-up-files',
		'pewc_clean_up_page_callback'
	);
}
add_action( 'admin_menu', 'pewc_register_clean_up_page', 200 );

/**
 * Display page for Clean Up tool
 * @since 3.23.1
 */
function pewc_clean_up_page_callback() {

?>
	<div class="wrap">
		<?php printf( '<h1>%s</h2>', __( 'Clean Up Uploaded Files', 'pewc' ) ); ?>

		<p class="pewc-clean-up-files-scanning" style="display:none">
			<?php _e( 'Scanning directories...', 'pewc' ); ?>
		</p>
		<p class="pewc-clean-up-files-no-directories" style="display:none">
			<?php _e( 'No directories found', 'pewc' ); ?>
		</p>
		<p class="pewc-clean-up-files-scanning-files" style="display:none">
			<?php _e( 'Getting all files that are older than [before_date]... [percentage]', 'pewc' ); ?>
		</p>
		<p class="pewc-clean-up-files-done-scanning" style="display:none">
			<?php _e( 'Scanning has finished for files older than [before_date]. Number of files to be deleted: [files_to_delete]', 'pewc' ); ?>
		</p>
		<p class="pewc-clean-up-files-deleting" style="display:none">
			<?php _e( 'Deleting files older than [before_date]... [percentage]', 'pewc' ); ?>
		</p>
		<p class="pewc-clean-up-files-done" style="display:none">
			<span><?php _e( 'Clean up done for files older than [before_date]. Number of files deleted: [total_deleted]', 'pewc' ); ?></span>
			<a href="#" class="pewc-clean-up-files-done-button button"><?php _e( 'Done', 'pewc'); ?></a>
		</p>
		<p class="pewc-clean-up-files-notification" style="display:none"></p>

		<?php
		$confirm_button = sprintf(
			'<a href="#" class="pewc-clean-up-files-confirm button-primary" style="display:none;">%s</a> 
			<a href="#" class="pewc-clean-up-files-cancel button" style="display:none;">%s</a>',
			__( 'Confirm delete? This cannot be undone.', 'pewc' ),
			__( 'Cancel', 'pewc' )
		);
		echo $confirm_button; ?>
		<div class="pewc-clean-up-files-list" style="display:none"><p class="deleted"><?php _e( 'Deleted files:', 'pewc' ); ?></p></div>
		<?php echo $confirm_button; ?>

		<p>&nbsp;</p>
<?php
		$url = admin_url( 'admin.php' );
		$url = add_query_arg(
			array(
				'page' => 'pewc-clean-up-files',
				'action' => 'clean-up-completed'
			),
			$url
		);
		printf(
			'<p><a href="%s" class="pewc-clean-up-completed button-primary" onclick="return confirm(\'%s\')">%s</a></p>',
			wp_nonce_url( $url, 'clean up completed', 'pewc_clean_up_completed_nonce' ),
			__( 'Delete uploaded files for all Completed orders? This cannot be undone.', 'pewc' ),
			__( 'Delete uploaded files from Completed orders', 'pewc' )
		);
		
		wp_nonce_field( 'pewc_clean_up_files_nonce', 'pewc_clean_up_files_nonce' ); ?>

		<p>&nbsp;</p>
		<p class="pewc-clean-up-before-date-container">
			<?php _e( 'Delete files uploaded before date:', 'pewc' ); ?> <input type="text" class="pewc-clean-up-before-date" readonly="readonly">
			<button id="pewc_clean_up_before_date_delete" class="button-primary" style="display:none;"><?php
			if ( pewc_clean_up_files_confirm_before_delete() ) {
				_e( 'Scan files to delete', 'pewc' );
			} else {
				_e( 'Delete', 'pewc' );
			} ?></button>
		</p>

	</div>
<?php

}

/**
 * Clean up processes
 * @since 3.23.1
 */
function pewc_admin_clean_up_processes() {

	// Clean up uploaded files in completed orders
	if ( isset( $_GET['page']) && 'pewc-clean-up-files' === $_GET['page'] && isset( $_GET['action'] ) && 'clean-up-completed' === $_GET['action'] ) {
		//ob_clean();

		if ( isset( $_GET['clean_up'] ) && 'success' === $_GET['clean_up'] ) {
			add_action( 'admin_notices', function() {
				$class = 'notice notice-success';
				$message = __( 'All uploaded files in Completed orders have been deleted.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
		} else if ( isset( $_GET['clean_up'] ) && 'fail' === $_GET['clean_up'] ) {
			add_action( 'admin_notices', function() {
				$class = 'notice notice-error';
				$message = __( 'Some files have not been deleted because of an error.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
		} else if( ! isset( $_GET['pewc_clean_up_completed_nonce'] ) || ! wp_verify_nonce( $_GET['pewc_clean_up_completed_nonce'], 'clean up completed' ) ) {	
			add_action( 'admin_notices', function() {
				$class = 'notice notice-error';
				$message = __( 'Invalid nonce. No files were deleted.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
		} else {

			
			// HPOS (High-Performance Order Storage) enabled. meta_query can only be used in HPOS
			if ( 'yes' === get_option( 'woocommerce_custom_orders_table_enabled', 'no' ) ) {
				$args = array(
					'status' => 'completed',
					'meta_query' => array(
						/*array(
							'key' => 'pewc_uploaded_files',
							'compare' => 'EXISTS',
						),*/
						array(
							'key' => 'pewc_uploaded_files_deleted',
							'compare' => 'NOT EXISTS',
						)
					),
				);
			} else {
				// non-HPOS, uses `woocommerce_order_data_store_cpt_get_orders_query` filter for custom queries
				$args = array(
					'status' => 'completed',
					//'pewc_uploaded_files' => 'EXISTS',
					'pewc_uploaded_files_deleted' => 'NOT EXISTS',
				);
			}
			
			$orders = wc_get_orders( $args );
			
			if ( empty( $orders ) ) {

				add_action( 'admin_notices', function() {
					$class = 'notice notice-success';
					$message = __( 'No Completed orders with uploaded files were found.', 'pewc' );
					printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
				});

			} else {

				$upload_dir = pewc_get_upload_dir();
				if ( pewc_replace_backslashes_in_file_paths() ) {
					$upload_dir = str_replace( '\\', '/', $upload_dir );
				}
				$all_deleted = true;

				foreach ( $orders as $order ) {
					$all_deleted_order = true;
					$uploaded_files = $order->get_meta( 'pewc_uploaded_files', true );
					$uploaded_files_deleted = $order->get_meta( 'pewc_uploaded_files_deleted', true );

					if ( $uploaded_files_deleted ) {
						// skip for orders that were marked deleted already. This also applies to orders that don't have uploads
						continue;
					}
					if ( empty( $uploaded_files ) ) {
						// pewc_uploaded_files doesn't exist if rename uploads is disabled and organised uploads is disabled
						// retrieve them from the order's items
						$uploaded_files = pewc_get_uploaded_files_from_order_items( $order );
					}
					if ( ! empty( $uploaded_files ) ) {
						foreach ( $uploaded_files as $uploaded_file ) {
							if ( is_file( $uploaded_file ) && false !== strpos( $uploaded_file, $upload_dir ) ) {
								if ( ! unlink( $uploaded_file ) ) {
									$all_deleted = false;
									$all_deleted_order = false;
									pewc_error_log( 'Order #' . $order->get_id() .', error in deleting file:' . $uploaded_file );
								}
							}
						}
					}

					if ( $all_deleted_order ) {
						// we add this so that we can skip this order when running the Clean Up tools in the future
						$order->update_meta_data( 'pewc_uploaded_files_deleted', true );
						$order->delete_meta_data( 'pewc_uploaded_files_not_deleted' );
						$order->save();
					}
				}

				$url = admin_url( 'admin.php' );
				$url = add_query_arg(
					array(
						'page' => 'pewc-clean-up-files',
						'action' => 'clean-up-completed',
						'clean_up' => $all_deleted ? 'success' : 'fail',
					),
					$url
				);
				wp_redirect( $url );
				die();

			}

		}
	}

}
add_action( 'admin_init', 'pewc_admin_clean_up_processes' );

/**
 * Add custom query vars to the order query when retrieving Completed orders (non-HPOS)
 * @since 3.23.1
 */
function pewc_handle_custom_query_var_for_clean_up( $query, $query_vars ) {

	if ( ! empty( $query_vars['pewc_uploaded_files'] ) ) {
		$query['meta_query'][] = array(
			'key' => 'pewc_uploaded_files',
			'compare' => esc_attr( $query_vars['pewc_uploaded_files'] ),
		);
	}
	if ( ! empty( $query_vars['pewc_uploaded_files_deleted'] ) ) {
		$query['meta_query'][] = array(
			'key' => 'pewc_uploaded_files_deleted',
			'compare' => esc_attr( $query_vars['pewc_uploaded_files_deleted'] ),
		);
	}

	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'pewc_handle_custom_query_var_for_clean_up', 999, 2 );

/**
 * Scan the product-extras directory for all directories
 * @since 3.23.1
 */
function pewc_clean_up_files_scandir() {

	if( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'pewc_clean_up_files_nonce' ) ) {
		wp_send_json_error( 'nonce_fail' );
		exit;
	}

	$upload_dir = pewc_get_upload_dir();
	if ( pewc_replace_backslashes_in_file_paths() ) {
		$upload_dir = str_replace( '\\', '/', $upload_dir );
	}

	$directories = array();
	if ( is_dir( $upload_dir ) ) {
		if ( $handle = opendir( $upload_dir ) ) {
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( $entry != "." && $entry != ".." && is_dir( $upload_dir . '/' . $entry ) && ! in_array( $entry, $directories ) ) {
					$directories[] = $entry;
				}
			}
			closedir( $handle );
		}
	}

	wp_send_json( $directories );
	exit;

}
add_action( 'wp_ajax_pewc_clean_up_files_scandir', 'pewc_clean_up_files_scandir' );

/**
 * Gets all files that can be deleted based on the file's modified date
 * @since 3.23.1
 */
function pewc_clean_up_files_get_files_to_delete() {

	if( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'pewc_clean_up_files_nonce' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid nonce. Please refresh page then try again.', 'pewc' )
		) );
		exit;
	}

	if ( ! isset( $_POST['before_date'] ) || ! isset( $_POST['directory'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid request. Some parameters are missing.', 'pewc' )
		) );
		exit;
	}

	$before_date = $_POST['before_date'];
	if ( $before_date != date( 'Y-m-d', strtotime( $before_date ) ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid date format: ', 'pewc' ) . $before_date
		) );
		exit;
	}

	$upload_dir = pewc_get_upload_dir();
	if ( pewc_replace_backslashes_in_file_paths() ) {
		$upload_dir = str_replace( '\\', '/', $upload_dir );
	}

	$directory = $_POST['directory'];
	if ( '.' === $directory || false !== strpos( $directory, '/' ) || false !== strpos( $directory, '\\' ) || false !== strpos( $directory, '..' ) || ! is_dir( $upload_dir . '/' . $directory ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid directory: ', 'pewc' ) . $directory
		) );
		exit;
	}

	$data = pewc_clean_up_files_recursive( $upload_dir . '/' . $directory, $upload_dir, $before_date );

	wp_send_json_success( array(
		'message' => 'done',
		'counter' => (int) $data['counter'],
		'files' => ! empty( $data['files'] ) ? $data['files'] : array()
	) );

}
add_action( 'wp_ajax_pewc_clean_up_files_get_files_to_delete', 'pewc_clean_up_files_get_files_to_delete' );

/**
 * Delete files in a directory if modified before a specific date
 * @since 3.23.1
 */
function pewc_clean_up_files_delete_in_dir() {

	if( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'pewc_clean_up_files_nonce' ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid nonce. Please refresh page then try again.', 'pewc' )
		) );
		exit;
	}

	if ( ! isset( $_POST['before_date'] ) || ! isset( $_POST['directory'] ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid request. Some parameters are missing.', 'pewc' )
		) );
		exit;
	}

	$before_date = $_POST['before_date'];
	if ( $before_date != date( 'Y-m-d', strtotime( $before_date ) ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid date format: ', 'pewc' ) . $before_date
		) );
		exit;
	}

	$upload_dir = pewc_get_upload_dir();
	if ( pewc_replace_backslashes_in_file_paths() ) {
		$upload_dir = str_replace( '\\', '/', $upload_dir );
	}

	$directory = $_POST['directory'];
	if ( '.' === $directory || false !== strpos( $directory, '/' ) || false !== strpos( $directory, '\\' ) || false !== strpos( $directory, '..' ) || ! is_dir( $upload_dir . '/' . $directory ) ) {
		wp_send_json_error( array(
			'message' => __( 'Invalid directory: ', 'pewc' ) . $directory
		) );
		exit;
	}

	$data = pewc_clean_up_files_recursive( $upload_dir . '/' . $directory, $upload_dir, $before_date, 'delete' );

	wp_send_json_success( array(
		'message' => 'done',
		'total_deleted' => $data['counter'],
		'files' => ! empty( $data['files'] ) ? $data['files'] : array()
	) );

}
add_action( 'wp_ajax_pewc_clean_up_files_delete_in_dir', 'pewc_clean_up_files_delete_in_dir' );

/**
 * Recursively scans directories, then deletes files that are older than the before date
 * @since 3.23.1
 */
function pewc_clean_up_files_recursive( $directory, $upload_dir, $before_date, $action = 'scan', $counter = 0, $files = array() ) {

	if ( is_dir( $directory ) ) {
		if ( $handle = opendir( $directory ) ) {
			while ( false !== ( $entry = readdir( $handle ) ) ) {
				if ( '.' === $entry || '..' === $entry || 'index.php' === $entry || '.htaccess' === $entry ) {
					continue; // skip these
				} else {
					$full_path = $directory . '/' . $entry;
					if ( is_dir( $full_path ) ) {
						$data = pewc_clean_up_files_recursive( $full_path, $upload_dir, $before_date, $action, $counter, $files );
						$counter += (int) $data['counter'];
						if ( ! empty( $data['files'] ) ) {
							$files = $data['files'];
						}
					} else if ( is_file( $full_path ) ) {
						// this is a file, check modified date
						$mtime = filemtime( $full_path );
						if ( $mtime ) {
							$mdate = date( 'Y-m-d', $mtime );
							if ( $mdate <= $before_date ) {
								$subpath = str_replace( $upload_dir, '', $full_path );
								if ( 'scan' === $action ) {
									if ( ! in_array( $subpath, $files ) ) {
										$files[] = $subpath;
										$counter++;
									}
								} else if ( 'delete' === $action && unlink( $full_path ) ) {
									if ( ! in_array( $subpath, $files ) ) {
										$files[] = $subpath;
										$counter++;
									}
								}
							}
						}
					}
				}
			}
			closedir( $handle );
		}
	}

	return array(
		'counter' => $counter,
		'files'	=> $files
	);

}
