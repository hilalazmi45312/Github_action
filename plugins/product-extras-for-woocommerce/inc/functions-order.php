<?php
/**
 * Functions for orders / checkout
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom meta to order
 * Note: as of WC 9.4.2, $item doesn't exist yet, so this always adds/overwrites the item data for an order
 * @version 3.21.7
 */
function pewc_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {

	$display_product_meta = apply_filters( 'pewc_display_child_product_meta', false, $item );

	foreach( $item as $cart_item_key=>$values ) {

		if( isset( $values['product_extras'] ) ) {

			$product_id = $values['product_extras']['product_id'];
			$product = wc_get_product( $product_id ); // 3.22.1

			// Unserialise the add-on data
			if( isset( $values['product_extras']['groups'] ) ) {
				// 3.24.3, so that cloned groups also appear in the backend
				$all_groups = pewc_rebuild_cart_item_data( $values['product_extras'], 'order' );

				//foreach ( $values['product_extras']['groups'] as $group_id=>$group ) 
				foreach ( $all_groups as $group ) {

					if( $group ) {
						$group_id = $group['id'];

						$hidden_group_types = apply_filters( 'pewc_hidden_group_types_in_order', array() );

						foreach( $group as $field_id=>$field ) {

							if( isset( $field['type'] ) ) {

								if( ( $field['type'] == 'products' || $field['type'] == 'product-categories' ) && ! $display_product_meta ) {
									continue;
								}

								if ( ! empty( $field['is_repeatable'] ) && ! empty( $field['hidden'] ) ) {
									continue; // 3.26.5, skip this field, maybe hidden by condition
								}

								$field_label = pewc_get_field_label_order_meta( $field, $item );

								if( $field['type'] == 'upload' ) {

									$value = pewc_get_uploaded_files_per_field( $field, $order->get_id(), $product_id, $values ); // changed $item->get_order_id() to $order->get_id(), because as of WC 9.4.2 $item->get_order_id() is always 0
									$value = join( ', ', $value );

									// 3.21.7, replace backslashes with slashes because backslashes gets trimmed by WP later and then the file isn't readable anymore
									// needed in Windows systems
									if ( ! empty( $field['files'] ) && pewc_replace_backslashes_in_file_paths() ) {
										foreach ( $field['files'] as $index => $file ) {
											$values['product_extras']['groups'][$group_id][$field_id]['files'][$index]['file'] = str_replace( '\\', '/', $file['file'] );
										}
									}

									// 3.21.7, product_extras was already created for an order. Since WC 9.4.2, it might get overwritten with the product_extras from here
									if ( ! empty( $field['files'] ) && 'yes' === $order->get_meta( 'pewc_product_extras_created' ) && ( pewc_get_rename_uploads() || 'yes' === pewc_get_pewc_organise_uploads() ) ) {
										$values['product_extras']['groups'][$group_id][$field_id]['files'] = pewc_rename_uploaded_files( $field['files'], $order->get_id(), $field, $product_id );
									}

								} else {

									$value = isset( $field['value'] ) ? $field['value'] : '';
									$value = str_replace( '__checked__', '<span class="dashicons dashicons-yes"></span>', $value );

								}

								// Add the price
								$price = pewc_get_field_price_order( $field, false, true );
								// 3.22.1, so that field price includes tax if needed
								if ( '' !== $price ) {
									$price = wc_price( pewc_maybe_include_tax($product, $price, true) );
								}
								if( apply_filters( 'pewc_show_field_prices_in_order', true ) ) {
									$value .= ' ' . $price;
								}

								$value = wp_kses_post( apply_filters( 'pewc_filter_item_value_in_cart', $value, $field ) );

								$item->add_meta_data( $field_label, $value, true );

							}

						}

					}

				}

			}

			if( ! empty( $values['composite_image'] ) ) {
				$item->add_meta_data( apply_filters( 'pewc_composite_image_label', __( 'Composite image', 'pewc' ) ), $values['composite_image'], true );
			}

			if( ! empty( $values['apaou_composite_image_url'] ) ) {
				$item->add_meta_data( apply_filters( 'pewc_composite_preview_label', __( 'Composite preview', 'pewc' ) ), $values['apaou_composite_image_url'], true );
			}
			
			// This is all the add-on fields saved as an array
			// This is used in several places, including exports, instead of individual meta data items
			// Note: as of WC 9.4.2, $item and $order might not exist yet, so if payment failed, a new item is created for the existing order
			$item->add_meta_data( 'product_extras', $values['product_extras'], true );

		}

	}

}
add_action( 'woocommerce_checkout_create_order_line_item', 'pewc_add_custom_data_to_order', 10, 4 );

function pewc_get_uploaded_files_per_field( $field, $order_id, $product_id, $cart_values=false ) {

	$uploaded_files = array();

	if( ! empty( $field['files'] ) ) {

		foreach( $field['files'] as $index=>$file ) {

			// Only generate a thumb for image files
			// if( is_array( getimagesize( $file['file'] ) ) ) {

				$file_name = isset( $file['file'] ) ? $file['file'] : '';
				$url = isset( $file['url'] ) ? $file['url'] : '';
				$display_name = isset( $file['display'] ) ? $file['display'] : '';

				// Filter the file name if it's renamed
				// Change the file name
				// 3.21.7, only do this if order_id is ready
				if( pewc_get_rename_uploads() && $order_id > 0 ) {

					// 3.27.2, added $index
					$file_name = pewc_get_uploaded_file_name( $file['file'], $order_id, $field, $product_id, $cart_values, $index );
					$url = pewc_get_uploaded_file_url( $file['url'], $order_id, $field, $product_id, $cart_values, $index );
					$display_name = pewc_get_uploaded_file_display( $file['display'], $file_name, $cart_values );

				}
				// 3.21.7, added this
				if( pewc_get_pewc_organise_uploads() == 'yes' && $order_id > 0 ) {

					$upload_dir = trailingslashit( pewc_get_upload_dir() );
					$upload_url = trailingslashit( pewc_get_upload_url() );

					$order_upload_dir = rtrim( trailingslashit( $upload_dir ) . $order_id, '/' );
					$order_upload_url = rtrim( trailingslashit( $upload_url ) . $order_id, '/' );

					$order_upload_dir = apply_filters( 'pewc_order_upload_dir', $order_upload_dir, $field );
					$order_upload_url = apply_filters( 'pewc_order_upload_url', $order_upload_url, $field );

					$info = pathinfo( $file_name );
					$ext = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
					$basename = basename( $file_name, $ext );

					// Create the directory
					//if ( ! file_exists( $order_upload_dir ) ) {
					//	mkdir( $order_upload_dir, 0755, true );
						// Top level blank index.php
					//	@file_put_contents( $order_upload_dir . '/index.php', '<?php' . PHP_EOL . '// That whereof we cannot speak, thereof we must remain silent.' );
					//}

					$file_name = trailingslashit( $order_upload_dir ) . $basename . $ext;
					$url = trailingslashit( $order_upload_url ) . $basename . $ext;

				}

				if( ! empty( $file['quantity'] ) ) {
					$display_name .= sprintf(
						' [%s: %s]',
						__( 'Quantity', 'pewc' ),
						$file['quantity']
					);
				}

				$display_name = apply_filters( 'pewc_uploaded_file_display_name', $display_name, $file, $field );

				$uploaded_files[] = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					esc_url( $url ),
					esc_html( $display_name )
				);

			}

		// }

	}

	return $uploaded_files;

}

/**
 * Add product_extra information to front-end view order page
 */
function pewc_order_item_name( $product_name, $item ) {

	if ( function_exists( 'get_current_screen' ) ) {
		$screen = get_current_screen();
		// 3.25.7. Since WC 9.9.3, the filter woocommerce_order_item_name seem to be also called in the admin Edit Order page. This function is only for the frontend, so if this is the admin page, return immediately, to avoid displaying the add-ons list twice
		// 3.26.1. Changed condition from is_admin to using get_current_screen, for compatibility with "PDF Invoices & Packing Slips for WooCommerce" by WP Overnight
		if ( ! empty( $screen->id ) && ( 'woocommerce_page_wc-orders' === $screen->id || 'shop_order' === $screen->id ) && empty( $_POST ) ) {
			// woocommerce_page_wc-orders (HPOS), shop_order (non-HPOS), empty( $_POST ) means we are not resending order emmails
			return $product_name;
		}
	}

	$display_product_meta = apply_filters( 'pewc_display_child_product_meta', false, $item );

	if( isset( $item['product_extras']['groups'] ) ) {

		// 3.13.7, used later for pewc_field_visible_in()
		if ( is_wc_endpoint_url() ) {
			$field_visibility_page = 'order'; // this filter is triggered in the order page
		} else {
			$field_visibility_page = 'docs'; // this filter is triggered in the order email
		}

		$all_groups = pewc_rebuild_cart_item_data( $item['product_extras'] ); // 3.24.3

		//foreach ( $item['product_extras']['groups'] as $group_id => $groups ) 
		foreach ( $all_groups as $groups ) {

			if( $groups ) {
				$group_id = $groups['id'];

				$hidden_group_types = apply_filters( 'pewc_hidden_group_types_in_order', array() );

				$product_name .= '<ul>';

				foreach( $groups as $field_id=>$field ) {

					if( isset( $field['type'] ) ) {

						if( in_array( $field['type'], $hidden_group_types ) ) {
							// Don't add this to the order if it's a hidden field type
							continue;
						}

						if( ! empty( $field['hidden'] ) || ( ! empty( $field['field_visibility'] ) && ! pewc_field_visible_in( $field_visibility_page, $field['field_visibility'], $field_id, $group_id, $item->get_product_id() ) ) ) {
							// Don't add hidden fields. field_visibility added in 3.13.7
							continue;
						}

						if( ( $field['type'] == 'products' || $field['type'] == 'product-categories' ) && ! $display_product_meta ) {
							continue;
						}

						$classes = array( strtolower( str_replace( ' ', '_', $field['type'] ) ) );
						$classes[] = strtolower( str_replace( ' ', '_', $field['label'] ) );

						$product_id = $item->get_product_id();
						$product = wc_get_product( $product_id );
						$price = pewc_get_field_price_order( $field, $product, true );

						if( ! pewc_show_field_prices_in_front_end_order( $field ) ) {
							$price = '';
						} else if ( '' !== $price ) {
							// 3.22.1, so that field price includes tax if needed
							$price = wc_price( pewc_maybe_include_tax($product, $price, true) );
						}

						if( $field['type'] == 'upload' ) {

							if( ! empty( $field['files'] ) ) {

								$display = sprintf(
									'<li class="%s"><span class="pewc-order-item-label">%s:</span> <span class="pewc-order-item-price">%s</span>',
									join( ' ', $classes ),
									$field['label'],
									$price
								);

								foreach( $field['files'] as $index=>$file ) {

									// Only generate a thumb for image files
									if( is_file( $file['file'] ) && ( is_array( getimagesize( $file['file'] ) ) || apply_filters( 'pewc_force_always_display_thumbs', false ) ) && ! apply_filters( 'pewc_remove_thumbs_in_order_page', false ) ) {
										$img = sprintf(
											'<br><img style="max-width: 50px; height: auto;" src="%s">',
											esc_url( $file['url'] )
										);
									} else {
										$img = '';
									}

									if ( ! is_wc_endpoint_url() && apply_filters( 'pewc_remove_thumbs_in_emails', false ) ) {
										$img = ''; // remove thumbnails in email
									}

									$display_name = $file['display'];

									if( ! empty( $file['quantity'] ) ) {
										$display_name .= sprintf(
											' [%s: %s]',
											__( 'Quantity', 'pewc' ),
											$file['quantity']
										);
									}

									$display_name = apply_filters( 'pewc_get_item_data_after_file', $display_name, $file );

									// 3.18.2, added pewc_order_frontend_display_file filter
									$display .= apply_filters( 'pewc_order_frontend_display_file', 
										sprintf(
											'<br><span class="pewc-order-item-item"><a target="_blank" href="%s">%s</a></span>%s',
											$file['url'],
											$display_name,
											$img
										),
										$file,
										$display_name,
										$img
									);

								}

								// added 3.12.1, used by Review and Approve
								$display .= apply_filters( 'pewc_order_item_upload_other_data', '', $field, $item );

								$display .= '</li>';

								$product_name .= $display;

							}

						} else if( $field['type'] == 'checkbox' ) {

							$product_name .= sprintf(
								'<li class="%s">',
								join( ' ', $classes )
							);

							$product_name .= sprintf(
								'<span class="pewc-order-item-label">%s</span> <span class="pewc-order-item-price">%s</span>',
								$field['label'],
								$price
							);

							$product_name .= '</li>';

						} else {

							$value = wp_kses_post( apply_filters( 'pewc_filter_item_value_in_cart', $field['value'], $field ) );

							if( ! apply_filters( 'pewc_use_item_meta_in_order_item', false, $item ) ) {

								$product_name .= apply_filters(
									'pewc_order_item_product_name',
									'<li class="' . join( ' ', $classes ) . '"><span class="pewc-order-item-label">' . $field['label'] . ':</span> <span class="pewc-order-item-item">' . nl2br( $value ) . '</span> <span class="pewc-order-item-price">' . $price . '</span></li>',
									$field,
									$price
								);

							} else {

								$field_label = pewc_get_field_label_order_meta( $field, $item );
								$field_meta = $item->get_meta( $field_label );
								$field_label = ltrim( $field_label, '_' );
								$product_name .= apply_filters(
									'pewc_order_item_product_name',
									'<li class="' . join( ' ', $classes ) . '"><span class="pewc-order-item-label">' . $field_label . ':</span> <span class="pewc-order-item-item">' . nl2br( $field_meta ) . '</span></li>',
									$field,
									$price
								);

							}

						}
					}
				}

				// Optionally show the original product price in the order
				if( apply_filters( 'pewc_show_original_price_in_order', false ) && isset( $item['product_extras']['original_price'] ) ) {

					$product_name .= sprintf(
						'<li class="%s">%s: %s</li>',
						join( ' ', $classes ),
						apply_filters( 'pewc_original_price_text', __( 'Original price', 'pewc' ) ),
						wc_price( $item['product_extras']['original_price']  )
					);

				}

				$product_name .= '</ul>';
			}
		}
		
	}

	if( pewc_indent_child_product() == 'yes' && pewc_is_order_item_child_product( $item ) ) {
		$product_name = apply_filters( 'pewc_indent_markup', '<span style="padding-left: 15px"></span>' ) . $product_name;
	}

	return $product_name;

}
add_filter( 'woocommerce_order_item_name', 'pewc_order_item_name', 10, 2 );

function pewc_show_field_prices_in_front_end_order( $field=false ) {

	$display = true;
	if( isset( $field['price_visibility'] ) && $field['price_visibility'] == 'hidden' ) {
		$display = false;
	}
	return apply_filters( 'pewc_show_field_prices_in_order', $display );

}

/**
 * Create product_extra post when the order is processed
 */
function pewc_create_product_extra( $order_id ) {

	$order = wc_get_order( $order_id );
	$payment_method = is_callable( array( $order, 'get_payment_method' ) ) ? $order->get_payment_method() : $order->payment_method;

	// Don't publish product_extras for COD orders.
	if ( $order->has_status( 'processing' ) && 'cod' === $payment_method ) {
		// return;
	}

	// Get the product_extra meta data and create the product_extra
	$order_items = $order->get_items( 'line_item' );

	if( $order_items ) {

		if ( 'yes' === $order->get_meta( 'pewc_product_extras_created' ) ) {
			// 3.15.1, the loop below has already been processed, so no need to proceed.
			// e.g. when pewc_create_product_extra() is called by pewc_rest_insert_shop_order_object() which happens every time an order is updated via REST API
			return;
		}

		foreach( $order_items as $order_item_id=>$order_item ) {

			$product_extras = $order_item->get_meta( 'product_extras' );

			if( ! empty( $product_extras['groups'] ) || ! empty( $product_extras['products'] ) || ! empty( $product_extras['product-categories'] ) ) {

				if ( pewc_product_extras_already_processed( $order, $order_item, $product_extras ) ) {
					continue; // 3.15.1, prevent recreation of product_extra_id below
				}

				// Save the product_extra data, used by Add-Ons by Order
				if ( ! apply_filters( 'pewc_disable_product_extra_post_type', false ) ) {
					$product_extra_id = wp_insert_post( array(
						'post_title'	=> $product_extras['title'],
						'post_type'   => 'pewc_product_extra',
						'post_status'	=> 'publish'
					) );
					if( ! is_wp_error( $product_extra_id ) ) {
						wp_update_post(
							array(
								'ID'					=> $product_extra_id,
								'post_title'	=> $product_extras['title'] . ' #' . $product_extra_id
							)
						);
						// User data
						$user_id = $order->get_user_id();
						$user = get_userdata( $user_id );
						if( $user && ! is_wp_error( $user ) ) {
							update_post_meta( $product_extra_id, 'pewc_user_id', absint( $user_id ) );
						}

						update_post_meta( $product_extra_id, 'pewc_order_id', absint( $order_id ) );
						update_post_meta( $product_extra_id, 'pewc_item_cost', $order->get_item_total( $order_item ) );
						update_post_meta( $product_extra_id, 'pewc_order_total', $order->get_total() );
						update_post_meta( $product_extra_id, 'pewc_product_id', absint( $product_extras['product_id'] ) );

						update_post_meta( $product_extra_id, 'pewc_user_name', sanitize_text_field( $order->get_formatted_billing_full_name() ) );
						update_post_meta( $product_extra_id, 'pewc_user_email', sanitize_email( $order->get_billing_email() ) );
						update_post_meta( $product_extra_id, 'pewc_user_phone', sanitize_text_field( $order->get_billing_phone() ) );

						update_post_meta( $product_extra_id, 'pewc_order_item_id', absint( $order_item_id ) ); // 3.24.7, ensures that each order item is linked correctly to a product extra record
						update_post_meta( $product_extra_id, 'pewc_product_extras_version', '2.0' ); // 3.24.7, we add this marker so that we can detect if the extras may contain repeated fields

						// Save the product_extra ID to the order as well
						$order->update_meta_data( 'pewc_product_extra_id', absint( $product_extra_id ) ); // this has an issue, because succeeding order items will overwrite this
						$order_item->update_meta_data( '_pewc_product_extra_id', absint( $product_extra_id ) ); // 3.24.7, used in pewc_product_extras_already_processed(), if there is an underscore in the beginning this doesn't get displayed on the frontend

					}
				}

				$fields = array();

				if( ! empty( $product_extras['groups'] ) ) {

					// Rename any uploads if appropriate
					$product_extras['groups'] = pewc_rename_uploaded_files_item_meta( $order_item );

					foreach( $product_extras['groups'] as $gr_id => $groups ) {

						if( $groups ) {

							foreach( $groups as $group ) {

								if( isset( $group['type'] ) && $group['type'] != 'group_heading' ) {

									$group_id = $group['group_id'];
									$field_id = $group['field_id'];
									$fields[$group_id][$field_id] = array(
										'id'	=> sanitize_text_field( $group['id'] ),
										'type'	=> sanitize_text_field( $group['type'] ),
										'label'	=> sanitize_text_field( $group['label'] )
									);

									if( isset( $group['price'] ) ) {
										$fields[$group_id][$field_id]['price'] = $group['price'];
									}

									if( $group['type'] == 'upload' ) {

										$fields[$group_id][$field_id]['files'] = $group['files'];
										// 3.23.1, used by the clean up tool
										if ( ! empty( $group['files'] ) ) {
											$order->update_meta_data( 'pewc_has_uploads', 'yes' );
											$order->update_meta_data( 'pewc_uploaded_files_not_deleted', true );
										}
										// $fields[$group_id][$field_id]['url'] = esc_url( $group['url'] );
										// $fields[$group_id][$field_id]['display'] = sanitize_text_field( $group['display'] );
										// Delete uploaded image in product_extras folder (tidy up time)
										// unlink( $group['file'] );

									} else {

										$fields[$group_id][$field_id]['value'] = sanitize_text_field( $group['value'] );

									}

									// Use this for fancy stuff, like sending custom emails
									do_action( 'pewc_after_create_product_extra', $product_extra_id, $order, $group );

								}
							}

							// 3.24.7
							if ( isset( $product_extras['cloned_groups'][$gr_id] ) ) {
								// this order item has cloned groups, add them to the $fields array
								foreach ( $product_extras['cloned_groups'][$gr_id] as $clone_index => $cloned_fields ) {
									foreach ( $cloned_fields as $cloned_field_id => $field ) {
										$new_field = array(
											'id' => $field['id'],
											'type' => $field['type'],
											'label' => $field['label'] . ' (' . $clone_index . ')', // add clone_index so that they have their own column in the Export file,
											'price' => $field['price'],
											'value' => $field['value']
										);
										$fields[$gr_id][$cloned_field_id . '_' . $clone_index ] = $new_field;
									}
								}
							}
						}
					}
					if( ! empty( $fields ) ) {

						update_post_meta( $product_extra_id, 'pewc_product_extra_fields', $fields );

					}
				}
			}
		}

		$order->update_meta_data( 'pewc_product_extras_created', 'yes' ); // 3.15.1
		$order->save(); // needed so that data is updated in the DB, do it outside of loop. WC HPOS Compliance

	}
}
add_action( 'woocommerce_checkout_order_processed', 'pewc_create_product_extra', 10, 1 );

/**
 * Rename uploaded files if necessary. This is run at checkout as of WC 10.3.4.
 * @return Array 	Updated file array
 * @param $files	$files array from field
 * @since 3.7.0
 */
function pewc_rename_uploaded_files_item_meta( $item ) {

	if( isset( $item['product_extras']['groups'] ) ) {

		$order_id = $item->get_order_id();
		$order = wc_get_order( $order_id );
		$order_id = apply_filters( 'woocommerce_order_number', $order_id, $order );

		$item_id = $item->get_id();
		$product_id = $item['product_extras']['product_id'];

		$new_item_meta = $item['product_extras'];

		if( ( pewc_get_rename_uploads() || 'yes' === pewc_get_pewc_organise_uploads() ) && isset( $new_item_meta['groups'] ) ) {

			// Save a list of all uploaded files in this order
			//$uploaded_files = get_post_meta( $order_id, 'pewc_uploaded_files', true );
			$uploaded_files = $order->get_meta( 'pewc_uploaded_files', true ); // WC HPOS compliance
			if( ! $uploaded_files ) {
				$uploaded_files = array();
			}

			foreach( $new_item_meta['groups'] as $group_id=>$group ) {

				if( $group ) {

					foreach( $group as $field_id=>$field ) {

						if( isset( $field['type'] ) && $field['type'] == 'upload' ) {

							if( ! empty( $field['files'] ) ) {

								$uploaded_files_meta = array();

								foreach( $field['files'] as $index=>$file ) {

									$new_file_name = $file['file'];
									$new_url = $file['url'];
									$new_display_name = $file['display'];

									if( pewc_get_rename_uploads() ) {

										// Change the file name
										// 3.27.2, added $index
										$new_file_name = pewc_get_uploaded_file_name( $file['file'], $order_id, $field, $product_id, $item, $index );
										$new_url = pewc_get_uploaded_file_url( $file['url'], $order_id, $field, $product_id, $item, $index );
										$new_display_name = pewc_get_uploaded_file_display( $file['display'], $new_file_name, $item );

									}

									// Move files into order specific folders
									// Check if we are moving into order specific folders
									if( pewc_get_pewc_organise_uploads() == 'yes' ) {

										$upload_dir = trailingslashit( pewc_get_upload_dir() );
										$upload_url = trailingslashit( pewc_get_upload_url() );

										$order_upload_dir = rtrim( trailingslashit( $upload_dir ) . $order_id, '/' );
										$order_upload_url = rtrim( trailingslashit( $upload_url ) . $order_id, '/' );

										$order_upload_dir = apply_filters( 'pewc_order_upload_dir', $order_upload_dir, $field );
										$order_upload_url = apply_filters( 'pewc_order_upload_url', $order_upload_url, $field );

										$info = pathinfo( $new_file_name );
										$ext = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
										$basename = basename( $new_file_name, $ext );

										// Create the directory
										if ( ! file_exists( $order_upload_dir ) ) {
											mkdir( $order_upload_dir, 0755, true );
											// Top level blank index.php
											@file_put_contents( $order_upload_dir . '/index.php', '<?php' . PHP_EOL . '// That whereof we cannot speak, thereof we must remain silent.' );
										}

										$new_file_name = trailingslashit( $order_upload_dir ) . $basename . $ext;
										$new_url = trailingslashit( $order_upload_url ) . $basename . $ext;

									} else {
										// for PDF thumbs
										$info = pathinfo( $new_file_name );
										$ext = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
										$order_upload_dir = '';
										$order_upload_url = '';
									}

									// Move / rename the file
									if( file_exists( $file['file'] ) ) {
										// Don't rename twice
										//rename( $file['file'], $new_file_name );
										copy( $file['file'], $new_file_name ); // 3.21.7, let's copy instead. If the pending fails, the image should still work when viewed from the cart
										$uploaded_files[] = $new_file_name;

										if ( '.pdf' == $ext && is_file( $file['file'].'.jpg' ) ) {
											// delete PDF thumb
											unlink( $file['file'].'.jpg' );
										}
									}

									$new_item_meta['groups'][$group_id][$field_id]['files'][$index]['file'] = $new_file_name;
									$new_item_meta['groups'][$group_id][$field_id]['files'][$index]['url'] = $new_url;
									$new_item_meta['groups'][$group_id][$field_id]['files'][$index]['display'] = $new_display_name;

									// 3.21.7, replace backslashes with slashes because backslashes gets trimmed by WP later and then the file isn't readable anymore
									// needed in Windows systems
									if ( pewc_replace_backslashes_in_file_paths() ) {
										$new_item_meta['groups'][$group_id][$field_id]['files'][$index]['file'] = str_replace( '\\', '/', $new_item_meta['groups'][$group_id][$field_id]['files'][$index]['file'] );
									}

									if( ! empty( $file['quantity'] ) ) {
										$new_display_name .= sprintf(
											' [%s: %s]',
											__( 'Quantity', 'pewc' ),
											$file['quantity']
										);
									}

									$new_display_name = apply_filters( 'pewc_uploaded_file_display_name', $new_display_name, $file );

									// 3.18.2, pewc_uploaded_files_meta filter added, moving the original file is also done here by Advanced Uploads
									$uploaded_files_meta[] = apply_filters( 'pewc_uploaded_files_meta', 
										sprintf(
											'<a href="%s" target="_blank">%s</a>',
											esc_url( $new_url ),
											esc_html( $new_display_name )
										),
										$file,
										$new_file_name,
										$new_url,
										$new_display_name,
										$order_upload_dir,
										$order_upload_url,
										$order_id,
										$field,
										$product_id,
										$item
									);

								}

								// Update the meta
								$field_label = pewc_get_field_label_order_meta( $field, $item );
								wc_update_order_item_meta( $item_id, $field_label, join( ', ', $uploaded_files_meta ) );

							}

						}

					}

				}

			}

			// Save the list of uploaded files
			//update_post_meta( $order_id, 'pewc_uploaded_files', array_unique( $uploaded_files ) );
			$order->update_meta_data( 'pewc_uploaded_files', array_unique( $uploaded_files ) ); // WC HPOS compliance
			$order->save(); // needed so that data is updated in the DB?

			// Update the meta
			wc_update_order_item_meta( $item_id, 'product_extras', $new_item_meta );
			return $new_item_meta['groups'];

		}

	}

	return $item['product_extras']['groups'];

}

/**
 * Filter the uploaded file name to include tags
 * @since 3.7.0
 */
function pewc_get_uploaded_file_name( $name, $order_id, $field, $product_id, $order_item=false, $file_index=false ) {

	$quantity = isset( $order_item['quantity'] ) ? $order_item['quantity'] : false;

	// Change the file name
	$new_file_name = str_replace( 'xxorder_numberxx', $order_id, $name );
	$new_file_name = str_replace( 'xxgroup_idxx', $field['group_id'], $new_file_name );
	$new_file_name = str_replace( 'xxfield_idxx', $field['field_id'], $new_file_name );
	$new_file_name = str_replace( 'xxquantityxx', $quantity, $new_file_name );

	// 3.27.2, added filter
	$new_file_name = apply_filters( 'pewc_filter_rename_uploaded_file_order', $new_file_name, $order_id, $field, $product_id, $order_item, $file_index );

	return $new_file_name;

}

/**
 * Filter the uploaded file name to include tags
 * @since 3.7.0
 */
function pewc_get_uploaded_file_url( $url, $order_id, $field, $product_id, $order_item=false, $file_index=false ) {

	$quantity = isset( $order_item['quantity'] ) ? $order_item['quantity'] : false;

	// Change the file name
	$new_url = str_replace( 'xxorder_numberxx', $order_id, $url );
	$new_url = str_replace( 'xxgroup_idxx', $field['group_id'], $new_url );
	$new_url = str_replace( 'xxfield_idxx', $field['field_id'], $new_url );
	$new_url = str_replace( 'xxquantityxx', $quantity, $new_url );

	// 3.27.2, added filter
	$new_url = apply_filters( 'pewc_filter_rename_uploaded_url_order', $new_url, $order_id, $field, $product_id, $order_item, $file_index );

	return $new_url;

}

/**
 * Filter the uploaded file name to include tags
 * @since 3.7.0
 */
function pewc_get_uploaded_file_display( $display, $filename ) {

	// Change the file display name
	$info = pathinfo( $filename );
	$ext  = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
	$name = basename( $filename, $ext );

	return $name . $ext;

}

function pewc_get_field_label_order_meta( $field, $item ) {

	if( empty( $field['label'] ) || apply_filters( 'pewc_use_field_id_order_item_meta_label', false, $field, $item ) ) {
		$field_label = $field['id'];
	} else {
		$field_label = $field['label'];
	}

	// Hide the meta key from the front end
	if( apply_filters( 'pewc_apply_underscore_metakey', true, $field, $item ) ) {
		$field_label = '_' . $field_label;
	}

	return apply_filters( 'pewc_field_label_item_meta_data', $field_label, $field, $item );

}

/**
 * Check if we nend to indent child products
 * @since 3.9.2
 */
function pewc_indent_child_product( ) {

	return apply_filters( 'pewc_indent_child_product', get_option( 'pewc_indent_child_product', 'no' ) );

}
/**
 * Check if this order item is a child product
 * @since 3.9.2
 */
function pewc_is_order_item_child_product( $item ) {

	if( ! empty( $item['product_extras']['products']['child_field'] ) ) {
		return true;
	}

	return false;

}

/*
 * Checks the order for parent products with child products. This is only run if 'Hide child products in the order' is enabled
 * @since 3.9.8
 */
function pewc_prepare_parent_products_order( $order ) {

	if ( 'yes' === get_option( 'pewc_hide_child_products_order', 'no' ) ) {

		$order_id = $order->get_id();

		// get from session, to check if we've done this before
		if ( isset( WC()->session ) ) {
			$child_products_totals = WC()->session->get( 'child_products_totals_'.$order_id );
			$parent_products_keys = WC()->session->get( 'parent_products_keys_'.$order_id );

			if ( ! empty( $child_products_totals ) && ! empty( $parent_products_keys ) ) {
				return; // both are not empty, no need to run again
			}
		}

		// we use the arrays below later if hide == yes, so that we can get the totals of the child products and add it to the parent's
		$child_products_totals = array();
		$parent_products_keys = array();

		$order_items = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
		foreach ( $order_items as $item_id => $item ) {

			if ( isset( $item['product_extras']['products'] ) ) {

				$item_price = $item->get_subtotal();
				if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) {
					$item_price += $item->get_subtotal_tax();
				}

				if ( isset( $item['product_extras']['products']['child_field'] ) ) {

					// this is a child product
					$parent_field_id = $item['product_extras']['products']['parent_field_id'];
					if ( ! isset( $child_products_totals[$parent_field_id] ) )
						$child_products_totals[$parent_field_id] = 0;
					// add this child product's price to the parent's total later
					$child_products_totals[$parent_field_id] += wc_format_decimal( $item_price, '' );

				} else if ( isset( $item['product_extras']['child_fields'] )) {

					// this is a parent product, save some things for later
					if ( ! isset( $parent_products_keys[$item_id] ) ) {
						$parent_products_keys[$item_id] = array(
							'parent_field_id' => $item['product_extras']['products']['parent_field_id'],
							'parent_price' => wc_format_decimal( $item_price, '' )
						);
					}

				}
			}
		}

		if ( ! empty( $child_products_totals ) && ! empty( $parent_products_keys ) && isset( WC()->session ) ) {
			// let's save this in a session for later use
			WC()->session->set( 'child_products_totals_'.$order_id, $child_products_totals );
			WC()->session->set( 'parent_products_keys_'.$order_id, $parent_products_keys );
		}
	}

}
add_action( 'woocommerce_email_before_order_table', 'pewc_prepare_parent_products_order', 100, 1); // 3.13.7, needed for emails, triggered first
add_action( 'woocommerce_order_details_before_order_table_items', 'pewc_prepare_parent_products_order', 100, 1);

/*
 * Filters the order item's line subtotal if 'Hide child products in the order' is enabled
 * @since 3.9.10
 */
function pewc_line_subtotal_parent_product( $subtotal, $item, $order ) {

	// only do this on the front end
	if ( 'yes' === get_option( 'pewc_hide_child_products_order', 'no' ) && ( ! is_admin() || wp_doing_ajax() ) ) {
		$order_id = $order->get_id();
		$item_id = $item->get_id();

		// get from session, generated from pewc_prepare_parent_products()
		$child_products_totals = ( WC()->session ) ? WC()->session->get( 'child_products_totals_'.$order_id ) : array();
		$parent_products_keys = ( WC()->session ) ? WC()->session->get( 'parent_products_keys_'.$order_id ) : array();

		if ( ! empty( $parent_products_keys[$item_id]['parent_field_id'] ) && ! empty( $child_products_totals[$parent_products_keys[$item_id]['parent_field_id']] ) ) {
			// this is a parent product that needs price display adjustment

			// get the total for this parent product's children
			$child_products_total = $child_products_totals[$parent_products_keys[$item_id]['parent_field_id']];

			// get this parent product's price
			$parent_price = $parent_products_keys[$item_id]['parent_price'];

			// this is for the line subtotal, so no need to divide by quantity
			$old_price = $parent_price;
			$new_price = $old_price + $child_products_total;

			// we need this for older orders, in case the shop changed currency
			$args = array(
				'currency' => $order->get_currency()
			);

			//return wc_price($new_price); // this does not have the suffix
			$subtotal = str_replace( wc_price( $old_price, $args ), wc_price( $new_price, $args ), $subtotal); // this keeps the suffix
		}
	}
	return $subtotal;

}
add_filter( 'woocommerce_order_formatted_line_subtotal', 'pewc_line_subtotal_parent_product', 100, 3);

/**
 * Used by pewc_create_product_extra() to check if we need to create the product_extras records
 * Prevents recreation of product_extras records when the REST API function pewc_rest_insert_shop_order_object() is used
 * @since 3.15.1
 */
function pewc_product_extras_already_processed( $order, $order_item, $product_extras ) {
	global $wpdb;

	if ( 'woocommerce_checkout_order_processed' === current_action() ) {
		return false; // this is a new order maybe, so no need to check
	}

	if ( ! $order_item->get_meta( '_pewc_product_extra_id' ) ) {
		return false; // 3.24.7
	}

	if ( $order->get_meta( 'pewc_product_extra_id') ) {
		// this order has a pewc_product_extra_id saved, check if we have this for this specific order item
		$query = $wpdb->prepare(
			"SELECT a.`post_id`, b.`meta_value` FROM `" . $wpdb->prefix . "postmeta` a 
			LEFT JOIN `" . $wpdb->prefix . "postmeta` b ON b.`post_id` = a.`post_id` 
			LEFT JOIN `" . $wpdb->prefix . "postmeta` c ON c.`post_id` = a.`post_id` 
			WHERE a.`meta_key` = 'pewc_order_id' AND a.`meta_value` = '%d' 
			AND b.`meta_key` = 'pewc_product_id' AND b.`meta_value` = '%d' 
			AND c.`meta_key` = 'pewc_item_cost' AND c.`meta_value` = '%d' ",
			$order->get_id(),
			$product_extras['product_id'],
			$order->get_item_total( $order_item )
		);
		$results = $wpdb->query( $query );

		if ( $results > 0 ) {
			return true; // product extras have already been processed?
		}
	}
	return false;
}

/**
 * Rename uploaded files if needed, before they are re-added to the item meta.
 * This is a fix for an issue where if payment failed, an order is created with the renamed/moved uploaded file,
 * but it may get rewritten to an incorrect path if customer tries to checkout again
 * @since 3.21.7
 */
function pewc_rename_uploaded_files( $files, $order_id, $field, $product_id ) {

	if ( ! empty( $files ) ) {
		foreach( $files as $index=>$file ) {

			$new_file_name = $file['file'];
			$new_url = $file['url'];
			$new_display_name = $file['display'];

			if( pewc_get_rename_uploads() ) {

				// Change the file 
				// 3.27.2, added false, $index
				$new_file_name = pewc_get_uploaded_file_name( $file['file'], $order_id, $field, $product_id, false, $index );
				$new_url = pewc_get_uploaded_file_url( $file['url'], $order_id, $field, $product_id, false, $index );
				$new_display_name = pewc_get_uploaded_file_display( $file['display'], $new_file_name );

			}

			// Move files into order specific folders
			// Check if we are moving into order specific folders
			if( pewc_get_pewc_organise_uploads() == 'yes' ) {

				$upload_dir = trailingslashit( pewc_get_upload_dir() );
				$upload_url = trailingslashit( pewc_get_upload_url() );

				$order_upload_dir = rtrim( trailingslashit( $upload_dir ) . $order_id, '/' );
				$order_upload_url = rtrim( trailingslashit( $upload_url ) . $order_id, '/' );

				$order_upload_dir = apply_filters( 'pewc_order_upload_dir', $order_upload_dir, $field );
				$order_upload_url = apply_filters( 'pewc_order_upload_url', $order_upload_url, $field );

				$info = pathinfo( $new_file_name );
				$ext = isset( $info['extension'] ) && ! empty( $info['extension'] ) ? '.'. $info['extension'] : '';
				$basename = basename( $new_file_name, $ext );

				// Create the directory
				//if ( ! file_exists( $order_upload_dir ) ) {
				//	mkdir( $order_upload_dir, 0755, true );
					// Top level blank index.php
				//	@file_put_contents( $order_upload_dir . '/index.php', '<?php' . PHP_EOL . '// That whereof we cannot speak, thereof we must remain silent.' );
				//}

				$new_file_name = trailingslashit( $order_upload_dir ) . $basename . $ext;
				$new_url = trailingslashit( $order_upload_url ) . $basename . $ext;

			}

			$files[$index]['file'] = $new_file_name;
			$files[$index]['url'] = $new_url;
			$files[$index]['display'] = $new_display_name;

			// 3.21.7, replace backslashes with slashes because backslashes gets trimmed by WP later and then the file isn't readable anymore
			// needed in Windows systems
			if ( pewc_replace_backslashes_in_file_paths() ) {
				$files[$index]['file'] = str_replace( '\\', '/', $files[$index]['file'] );
			}
			
		}
	}
	return $files;
}

/**
 * Remove 'pewc_product_extra_id' from order item meta in the Edit Order page
 * @since 3.25.3
 */
function pewc_order_item_remove_meta_data( $formatted_meta, $item ) {

	if ( ! empty( $formatted_meta ) ) {
		foreach ( $formatted_meta as $meta_id => $meta_obj ) {
			if ( isset( $meta_obj->key ) && '_pewc_product_extra_id' === $meta_obj->key ) {
				unset( $formatted_meta[$meta_id] );
			}
		}
	}
	return $formatted_meta;

}
add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'pewc_order_item_remove_meta_data', 10, 2 );
