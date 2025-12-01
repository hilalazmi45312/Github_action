<?php
/**
 * Functions for orders in the backend
 * @since 3.7.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add product_extra information to back-end view order page
 */
function pewc_add_order_itemmeta_admin( $item_id, $item, $product ) {

	// Check to see if this product has got meta
	// If not, then use the pre 3.7.0 method to display add-ons
	if( isset( $item['product_extras']['groups'] ) ) {

		// See if we've got a meta field for the first field
		foreach( $item['product_extras']['groups'] as $group_id=>$group ) {

			if( $group ) {

				//$output = '<ul>'; // not needed

				foreach( $group as $field_id=>$field ) {

					$field_label = pewc_get_field_label_order_meta( $field, $item );
					$check_meta = $item->get_meta( $field_label, true );

					if( empty( $check_meta ) ) {

						// 3.26.5
						if ( ! empty( $field['is_repeatable'] ) && ! empty( $field['hidden'] ) ) {
							continue; // skip this because this is a hidden repeatable field
						}

						// If there's no meta data then display add-on data using old method
						add_filter( 'pewc_hide_itemised_add_on_data_order', '__return_false' );
						break;

					}

				}

			}

		}

	}

	// You can display the add-on data in its pre-3.7.0 format if you like
	if( apply_filters( 'pewc_hide_itemised_add_on_data_order', true ) ) {
		return;
	}

	if( isset( $item['product_extras']['groups'] ) ) {

		foreach ( $item['product_extras']['groups'] as $groups ) {

			if( $groups ) {

				$output = '<ul class="pewc-add-order-itemmeta-admin">';

				foreach( $groups as $group ) {

					if( isset( $group['type'] ) ) {
					// if( isset( $group['type'] ) && empty( $group['flat_rate'] ) ) {
						$classes = array( strtolower( str_replace( ' ', '_', $group['type'] ) ) );
						$classes[] = strtolower( str_replace( ' ', '_', $group['label'] ) );

						$price = pewc_get_field_price_order( $group, $product );
						if( ! apply_filters( 'pewc_show_field_prices_in_order', true ) ) {
							$price = '';
						}

						if( $group['type'] == 'upload' ) {

							if( ! empty( $group['files'] ) ) {

								$output .= '<li class="' . join( ' ', $classes ) . '">' . $group['label'] . ': ' . $price . '<ul>';

								foreach( $group['files'] as $index=>$file ) {

									$thumb = '';

									// Add a thumb for image files
									if( is_array( getimagesize( $file['file'] ) ) ) {
										$thumb = sprintf(
											'<img src="%s">',
											esc_url( $file['url'] )
										);
									}

									$output .= sprintf(
										'<li><a target="_blank" href="%s"><span>%s</span>%s</a></li>',
										esc_url( $file['url'] ),
										$thumb,
										$file['display']
									);

								}

								$output .= '</ul></li>';

							}

						} else if( $group['type'] == 'checkbox' ) {

							$output .= '<li class="' . join( ' ', $classes ) . '">' . $group['label'] . ' ' . $price . '</li>';

						}  else if( $group['type'] !== 'products' && $group['type'] !== 'product-categories' ) {

							// $output .= '<li class="' . join( ' ', $classes ) . '">' . $group['label'] . ': ' . $group['value'] . ' ' . $price . '</li>';
							$list_item = apply_filters(
								'pewc_itemmeta_admin_item',
								sprintf(
									'%s: %s %s',
									$group['label'],
									$group['value'],
									$price
								),
								$group,
								$price
							);

							$output .= sprintf(
								'<li class="%s">%s</li>',
								join( ' ', $classes ),
								$list_item
							);

						}

					}

				}

				// Optionally show the original product price in the order
				if( apply_filters( 'pewc_show_original_price_in_order', false ) && isset( $item['product_extras']['original_price'] ) ) {

					$output .= sprintf(
						'<li class="%s">%s: %s</li>',
						join( ' ', $classes ),
						apply_filters( 'pewc_original_price_text', __( 'Original price', 'pewc' ) ),
						wc_price( $item['product_extras']['original_price']  )
					);

				}

				$output .= '</ul>';

				echo apply_filters( 'pewc_add_order_itemmeta_admin', $output, $item_id, $item, $product );

			}

		}

	}

}
add_action( 'woocommerce_after_order_itemmeta', 'pewc_add_order_itemmeta_admin', 10, 3 );

/**
 * Filter our meta labels to remove initial underscore
 * @since 3.7.0
 */
function pewc_attribute_label( $label, $meta_key ) {

	$label = ltrim( $label, '_' );
	return $label;

}
add_filter( 'woocommerce_attribute_label', 'pewc_attribute_label', 10, 2 );

/**
 * Get the price of the field in the order
 * @since	3.7.0
 * @version	3.22.1
 */
function pewc_get_field_price_order( $field, $product=false, $price_only=false ) {

	$hide_zero = get_option( 'pewc_hide_zero', 'no' );
	$price = '';

	// Calculate price
	if( isset( $field['price'] ) ) {

		if( $hide_zero == 'yes' && empty( $field['price'] ) ) {

			$price = '';

		} else {

			/**
			 * Removed in 3.7.1 to avoid doubling of tax
			 */
			// $price = pewc_maybe_include_tax( $product, $field['price'] );
			// 3.22.1, return price only so that we can calculate taxes for the frontend and backend orders if needed
			if ( $price_only ) {
				$price = $field['price'];
			} else {
				$price = ' ' . wc_price( $field['price'] );
			}

		}

	}

	return $price;

}


/**
 * Optionally attach uploaded images to the order email
 */
function pewc_attach_images_to_email( $attachments, $id, $order ) {

	$email_ids = apply_filters( 'pewc_attach_images_to_email_ids', array( 'new_order', 'customer_on_hold_order' ) );

	if( in_array( $id, $email_ids ) && get_option( 'pewc_email_images', 'no' ) == 'yes' ) {

		// Find any attachments
		$order_items = $order->get_items( 'line_item' );
		if( $order_items ) {
			foreach( $order_items as $order_item ) {
				$product_extras = $order_item->get_meta( 'product_extras' );
				if( ! empty( $product_extras['groups'] ) ) {
					foreach( $product_extras['groups'] as $group ) {
						foreach( $group as $item_id=>$item ) {
							if( ! empty( $item['files'] ) ) {
								foreach( $item['files'] as $index=>$file ) {
									$attachments[] = $file['file'];
								}
							}
						}
					}
				}
			}
		}

	}

	return $attachments;

}
add_filter( 'woocommerce_email_attachments', 'pewc_attach_images_to_email', 10, 3 );

/**
 * Add 'Download Files' and 'Delete Files' button to orders with uploaded files
 */
function pewc_order_item_add_action_buttons( $order ) {

	if( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$order_id = $order->get_id();

	$pewc_has_uploads = $order->get_meta( 'pewc_has_uploads', true );
	if ( 'no' === $pewc_has_uploads || true === $order->get_meta( 'pewc_uploaded_files_deleted', true ) ) {
		// this order does not have a product that has an upload field, or the files have already been deleted
		return;
	}

	// Get the uploaded files
	//$uploaded_files = $order->get_meta( 'pewc_uploaded_files', true ); // WC HPOS compatible

	//if( ! $uploaded_files ) {
		// pewc_uploaded_files doesn't exist if rename uploads is disabled and organised uploads is disabled
		// retrieve them from the order's items
		//$uploaded_files = pewc_get_uploaded_files_from_order_items( $order );
	//}

	$organise_uploads = pewc_get_pewc_organise_uploads();

	if( 'yes' === $pewc_has_uploads ) {

		// check if we have files to download or delete
		// could we use pewc_uploaded_files_not_deleted here so that we don't have to check uploaded files?
		$has_files = false;

		if ( true === $order->get_meta( 'pewc_uploaded_files_not_deleted', true ) ) {
			$has_files = true;
		} else {
			// check again? retrieve uploaded_files from the order's items
			$uploaded_files = pewc_get_uploaded_files_from_order_items( $order, 'check only' );
			if ( ! empty( $uploaded_files ) ) {
				foreach ( $uploaded_files as $uploaded_file ) {
					if ( is_file( $uploaded_file ) ) {
						$has_files = true;
						break;
					}
				}
			}
		}

		if ( ! $has_files ) {
			printf(
				'<button class="button" disabled="disabled">%s</button>',
				__( 'All files for this order have been deleted.', 'pewc' ),
			);
			return;
		}

		// 3.23.1, always allow the Download Files button to be displayed
		//if ( $organise_uploads !== 'no' ) {

			if( isset( $_GET['download_zip'] ) ) {

				$dir = trailingslashit( pewc_get_upload_dir() );
				// 3.23.1, if organise_uploads == no, the order_id directory does not exist, so let's create it so that we can store our zip file there
				if ( ! is_dir( trailingslashit( $dir ) . $order_id ) ) {
					mkdir( trailingslashit( $dir ) . $order_id );
				}
				$dir = trailingslashit( $dir ) . $order_id;
				//$url = trailingslashit( pewc_get_upload_url() ) . $order_id; // 3.21.5, commented out because this doesn't seem to be used anymore
	
				$filename = trailingslashit( $dir ) . 'uploads-' . $order_id;
	
				$uploaded_files = pewc_get_uploaded_files_from_order_items( $order ); // 3.23.1, added this here to ensure we get the files list
				$result = pewc_create_zip( $uploaded_files, $filename, false, $order_id );
	
				if( ! $result ) {
					return;
				}
	
				$filename .= '.zip';
	
				if( file_exists( $filename ) ) {
	
					header( "Content-Type: application/zip" );
					header( "Content-Disposition: attachment; filename=" . basename( $filename ) );
					header( "Content-Length: " . filesize( $filename ) );
					ob_clean();
					flush();
					readfile( $filename );
	
					if( apply_filters( 'pewc_exit_after_download', false ) ) {
						exit;
					}
	
				}
	
			}

			$url = admin_url( 'post.php' );
			$url = add_query_arg(
				array(
					'post'	=> $order_id,
					'action'	=> 'edit',
					'download_zip'	=> 'true'
				),
				$url
			);
			printf(
				'<a href="%s" class="button pewc-download-files">%s</a>',
				esc_url( $url ),
				__( 'Download Files', 'pewc' )
			);
		//}

		// 3.23.1, added Delete Files button
		$url = admin_url( 'post.php' );
		$url = add_query_arg(
			array(
				'post'	=> $order_id,
				'action'	=> 'edit',
				'delete_files'	=> 'true'
			),
			$url
		);
		printf(
			'<a href="%s" class="button pewc-download-files" onclick="return confirm(\'%s\')">%s</a>',
			wp_nonce_url( $url, 'delete files', 'pewc_delete_files_nonce' ),
			__( 'Delete uploaded files in this order? This cannot be undone.', 'pewc' ),
			__( 'Delete Files', 'pewc' )
		);

	}

}
add_action( 'woocommerce_order_item_add_action_buttons', 'pewc_order_item_add_action_buttons' );

/* creates a compressed zip file */
function pewc_create_zip( $files = array(), $folder_name='', $overwrite=false, $order_id=false ) {
	// if the zip file already exists and overwrite is false, return false
	if( file_exists( $folder_name ) && ! $overwrite) {
		// return false;
	}

	$valid_files = array();
	//if files were passed in...
	if(is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists

			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}

	//if we have good files...
	if( count( $valid_files ) ) {
		//create the archive
		$zip = new ZipArchive();
		if( $zip->open( $folder_name . '.zip', $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true ) {
			return false;
		}
		//add the files
		foreach($valid_files as $file) {

			$info = pathinfo( $file );
			$ext = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
			$basename = basename( $file, $ext );
			$zip->addFile( $file, trailingslashit( 'uploads-' . $order_id ) . $basename . $ext );
		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

		//close the zip -- done!
		$zip->close();

		//check to make sure the file exists
		return file_exists( $folder_name . '.zip' );
	}
	else {
		return false;
	}
}


/**
 * Add add-ons to order again
 * @since 3.7.0
 */
function pewc_order_again_cart_item_data( $cart_item_meta, $product, $order ) {
	$customfields = [
		'product_extras'
	];
	global $woocommerce;
	remove_all_filters( 'woocommerce_add_to_cart_validation' );
	if( ! array_key_exists('item_meta', $cart_item_meta ) || ! is_array( $cart_item_meta['item_meta'] ) ) {
		foreach( $customfields as $key ) {
			if( ! empty($product[$key] ) ) {
				$cart_item_meta[$key] = $product[$key];
			}
		}
	}
	return $cart_item_meta;
}
add_filter( 'woocommerce_order_again_cart_item_data', 'pewc_order_again_cart_item_data', 20, 3 );

/**
 * Integrate with Repeat Order for WooCommerce
 * @link https://wordpress.org/plugins/repeat-order-for-woocommerce/
 * @since 3.7.0
 */
function pewc_order_again( $cart_item_meta, $item, $order ) {

	$addon_fields = wc_get_order_item_meta( $item->get_id(), 'product_extras');

	if( ! $addon_fields ) {
		return $cart_item_meta;
	}
	$cart_item_meta['product_extras'] = $addon_fields;

	return $cart_item_meta;

}
add_filter( 'woocommerce_order_again_cart_item_data', 'pewc_order_again', 99, 3 );

/**
 * Get uploaded files via the order items
 * @since 3.23.1
 */
function pewc_get_uploaded_files_from_order_items( $order, $action = 'get files' ) {

	$uploaded_files = array();
	$has_uploads = false;
	$files_deleted = true;
	$order_items = $order->get_items();
	if ( $order_items ) {
		$upload_dir = pewc_get_upload_dir();
		if ( pewc_replace_backslashes_in_file_paths() ) {
			$upload_dir = str_replace( '\\', '/', $upload_dir );
		}
		foreach ( $order_items as $order_item ) {
			$product_extras = $order_item->get_meta( 'product_extras', true );
			if ( ! empty( $product_extras['groups'] ) ) {
				foreach ( $product_extras['groups'] as $group_id => $fields ) {
					foreach ( $fields as $field_id => $field ) {
						if ( ! empty( $field['files'] ) ) {
							$has_uploads = true;
							foreach ( $field['files'] as $file ) {
								$uploaded_file = $file['file'];
								if ( is_file( $uploaded_file ) && false !== strpos( $uploaded_file, $upload_dir ) ) {
									$uploaded_files[] = $uploaded_file;
									$files_deleted = false;
									if ( 'check only' === $action ) {
										// we're on the order page and we're only checking for orders that have undeleted files, so return immediately
										// but first, save a metadata marker that we can use later so that we don't always have to loop through this code
										$order->update_meta_data( 'pewc_has_uploads', 'yes' );
										$order->update_meta_data( 'pewc_uploaded_files_not_deleted', true );
										$order->save();
										return $uploaded_files;
									}
								}
							}
						}
					}
				}
			}
		}

		if ( false === $has_uploads ) {
			// let's save a marker for this so that we don't have to loop through the order items again
			$order->update_meta_data( 'pewc_has_uploads', 'no' );
			$order->save();
		} else {
			$order->update_meta_data( 'pewc_has_uploads', 'yes' );
			if ( true === $files_deleted ) {
				$order->update_meta_data( 'pewc_uploaded_files_deleted', true );
				$order->delete_meta_data( 'pewc_uploaded_files_not_deleted' );
			} else {
				$order->update_meta_data( 'pewc_uploaded_files_not_deleted', true );
			}
			$order->save();
		}
	}

	return $uploaded_files;

}

/**
 * Process for deleting uploaded files. We attach it to WooCommerce init so that notices can be added
 * @since 3.23.1
 */
function pewc_admin_order_processes() {

	if( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( is_admin() && isset( $_GET['delete_files'] ) && ( isset( $_GET['id'] ) || isset( $_GET['post'] ) ) ) {

		$order_id = ! empty( $_GET['id'] ) ? (int) $_GET['id'] : (int) $_GET['post']; // $_GET['id'] is HPOS, $_GET['post'] is non-HPOS
		$order = wc_get_order( $order_id ); // this function is only available after woocommerce_after_register_post_type hook

		if ( ! $order ) {
			return;
		}

		$uploaded_files = $order->get_meta( 'pewc_uploaded_files', true );
		if ( empty( $uploaded_files ) ) {
			// pewc_uploaded_files doesn't exist if rename uploads is disabled and organised uploads is disabled
			// retrieve them from the order's items
			$uploaded_files = pewc_get_uploaded_files_from_order_items( $order );
			if ( empty( $uploaded_files ) ) {
				return;
			}
		}

		$upload_dir = pewc_get_upload_dir();
		if ( pewc_replace_backslashes_in_file_paths() ) {
			$upload_dir = str_replace( '\\', '/', $upload_dir );
		}

		if ( 'success' === $_GET['delete_files'] ) {
			add_action( 'admin_notices', function() {
				$class = 'notice notice-success';
				$message = __( 'All files for this order have been deleted.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
		} else if ( 'fail' === $_GET['delete_files'] ) {
			add_action( 'admin_notices', function() {
				$class = 'notice notice-error';
				$message = __( 'Some files for this order have not been deleted because of an error.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
		} else if( ! isset( $_GET['pewc_delete_files_nonce'] ) || ! wp_verify_nonce( $_GET['pewc_delete_files_nonce'], 'delete files' ) ) {	
			add_action( 'admin_notices', function() {
				$class = 'notice notice-error';
				$message = __( 'Invalid nonce. No files were deleted.', 'pewc' );
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			});
			return;
		} else {
			// delete all files
			$all_deleted = true;
			foreach ( $uploaded_files as $uploaded_file ) {
				if ( is_file( $uploaded_file ) && false !== strpos( $uploaded_file, $upload_dir ) ) {
					if ( ! unlink( $uploaded_file ) ) {
						$all_deleted = false;
						pewc_error_log( 'Error in deleting file:' . $uploaded_file );
					}
				}
			}

			if ( $all_deleted ) {
				// we add this so that we can skip this order when running the Clean Up tools
				$order->update_meta_data( 'pewc_uploaded_files_deleted', true );
				$order->delete_meta_data( 'pewc_uploaded_files_not_deleted' );
				$order->save();
			}

			$url = admin_url( 'post.php' );
			$url = add_query_arg(
				array(
					'post'	=> $order_id,
					'action'	=> 'edit',
					'delete_files' => $all_deleted ? 'success' : 'fail'
				),
				$url
			);
			wp_redirect( $url );
			die();
		}
		return;
	}

}
add_action( 'admin_init', 'pewc_admin_order_processes' );

/**
 * Adds a custom column on the order page
 * @since 3.23.1
 */
function pewc_custom_order_columns( $columns ) {

	$reordered_columns = array();

	// Inserting columns to a specific location
	foreach( $columns as $key => $column){
		$reordered_columns[$key] = $column;
		if( $key ===  'order_date' ){
			// Inserting after "Status" column
			$reordered_columns['pewc_has_uploads'] = __( 'Has uploads?', 'pewc' );
		}
	}
	return $reordered_columns;

}
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'pewc_custom_order_columns', 10, 1 ); // hpos
add_filter( 'manage_edit-shop_order_columns', 'pewc_custom_order_columns', 10, 1 ); // non-hpos

/**
 * Display the order value for the custom column (HPOS)
 * @since 3.23.1
 */
function pewc_custom_order_column_content_hpos( $column, $order ) {

	if ( 'pewc_has_uploads' === $column ) {
		$value = $order->get_meta( 'pewc_has_uploads', true );
		if ( 'no' === $value ) {
			// this order does not have a product that has an upload field
			return;
		}
		$value = $order->get_meta( 'pewc_uploaded_files_deleted', true );
		if ( ! empty( $value ) ) {
			// this order's uploaded files have already been deleted
			return;
		}

		if ( ! $order->meta_exists( 'pewc_uploaded_files_not_deleted' ) ) {
			// only do this if the metadata pewc_uploaded_files_not_deleted isn't set yet
			// we don't use pewc_uploaded_files_deleted because check if it doesn't exist in our clean up
			// also, using OR statement in wc_get_orders in the clean up script seems to be slow?
			$value = pewc_get_uploaded_files_from_order_items( $order, 'check only' ); // this function also checks if order has a product with upload field
			if ( empty( $value ) ) {
				// if this is empty, the file doesn't have an upload field, or the files were already deleted
				return;
			}
		}
		echo '<div class="dashicons dashicons-saved"></div>';
	}

}
add_action('manage_woocommerce_page_wc-orders_custom_column', 'pewc_custom_order_column_content_hpos', 10, 2);

/**
 * Display the order value for the custom column (Non-HPOS)
 * @since 3.23.1
 */
function pewc_custom_order_column_content( $column, $post_id ) {

	$order = wc_get_order( $post_id );

	if ( $order ) {
		// we can reuse the HPOS function now
		pewc_custom_order_column_content_hpos( $column, $order );
	}

}
add_action( 'manage_shop_order_posts_custom_column', 'pewc_custom_order_column_content', 10, 2 );

/**
 * We check if this order's uploaded files have already been deleted. If so, we activate the filter for woocommerce_order_item_display_meta_value below
 * We do this here so that it's only done once per order, instead of repeatedly checking per order item meta
 * @since 3.23.1
 */
function pewc_admin_order_item_headers( $order ) {

	$uploaded_files_deleted = $order->get_meta( 'pewc_uploaded_files_deleted', true );

	if ( $uploaded_files_deleted ) {
		add_filter( 'woocommerce_order_item_display_meta_value', 'pewc_order_item_display_meta_value', 10, 3 );
	}

}
add_action( 'woocommerce_admin_order_item_headers', 'pewc_admin_order_item_headers', 10, 1 );

/**
 * Filter an order item's metadata. This is only activated if order has the meta pewc_uploaded_files_deleted set to true. See pewc_admin_order_item_headers()
 * @since 3.23.1
 */
function pewc_order_item_display_meta_value( $display_value, $meta, $order_item ) {

	$upload_url = pewc_get_upload_url();
	if ( false !== strpos( $display_value, 'href="' . $upload_url ) ) {
		// this could be an Upload field with links, strip the tags?
		$display_value = strip_tags( $display_value );
	}
	return $display_value;

}
