<?php
/**
 * Functions for adding product to cart
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add product_extra field prices to cart item.
 */
function pewc_wc_calculate_total( $cart_obj = false ) {

	if( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if( did_action( 'woocommerce_before_calculate_totals' ) > 1 ) {
		// return;
	}

	if ( ! $cart_obj ) {
		// we might be running the mini cart action hook
		$cart_obj = WC()->cart;
	}

	// Iterate through each cart item
	foreach( $cart_obj->get_cart() as $key=>$value ) {

		// Skip setting the price again if the price with extras is the same as the original price
		// Avoids issues with Aelia converting prices that are already set as regular price in alt currency
		if( apply_filters( 'pewc_ignore_price_with_extras', false, $value ) && ( empty( $value['product_extras']['price_with_extras'] ) || $value['product_extras']['price_with_extras'] == $value['product_extras']['original_price'] ) ) {
			continue;
		}

		// Set the price to include extras
		if( isset( $value['product_extras']['price_with_extras'] ) ) { // ensure we don't override a price set by Bookings
			// No need to adjust here, because tax is adjusted by WC later if needed
			// Filtered by Bookings
			$new_price = apply_filters( 'pewc_price_with_extras_before_calc_totals', $value['product_extras']['price_with_extras'], $value );
			$value['data']->set_price( floatval( $new_price ) );

		}

	}

}
add_action( 'woocommerce_before_calculate_totals', 'pewc_wc_calculate_total', 10, 1 );
// since 3.11.6, adjust mini cart prices to include add-on price
add_action( 'woocommerce_before_mini_cart_contents', 'pewc_wc_calculate_total', 11 );

/*
 * Checks the cart for parent products with child products. This is only run if Hide child products is enabled
 * This is run after Dynamic Pricing and Discount Rules
 * @since 3.9.8
 */
function pewc_prepare_parent_products( $cart_obj ) {

	if( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( 'yes' === get_option( 'pewc_hide_child_products_cart', 'no' ) ) {

		// we use the arrays below later if hide == yes, so that we can get the totals of the child products and add it to the parent's
		$child_products_totals = array();
		$parent_products_keys = array();

		foreach( $cart_obj->get_cart() as $key=>$value ) {
			if ( isset( $value['product_extras']['products'] ) ) {

				$product = wc_get_product( $value['product_id'] );
				$item_price = $value['data']->get_price();

				if ( isset( $value['product_extras']['products']['child_field'] ) ) {

					// this is a child product
					$parent_field_id = $value['product_extras']['products']['parent_field_id'];
					if ( ! isset( $child_products_totals[$parent_field_id] ) )
						$child_products_totals[$parent_field_id] = 0;
					// add this child product's price to the parent's total
					$child_products_totals[$parent_field_id] += $value['quantity'] * pewc_maybe_include_tax( $product, $item_price, true ); // 3.12.2. removed wc_format_decimal because it causes rounding issues

				} else if ( isset( $value['product_extras']['child_fields'] )) {

					// this is a parent product, save some things for later
					if ( ! isset( $parent_products_keys[$key] ) ) {
						$parent_products_keys[$key] = array(
							'parent_field_id' => $value['product_extras']['products']['parent_field_id'],
							'parent_price' => pewc_maybe_include_tax( $product, $item_price, true )
						);
					}

				}
			}
		}

		if ( ! empty( $child_products_totals ) && ! empty( $parent_products_keys ) ) {
			// let's save this in a session for later use
			WC()->session->set( 'child_products_totals', $child_products_totals );
			WC()->session->set( 'parent_products_keys', $parent_products_keys );
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'pewc_prepare_parent_products', 100, 1 );

/*
 * Filters the cart item's line price and subtotal if Hide child products is enabled
 * @since	3.9.8
 * @version	3.19.2
 */
function pewc_cart_item_price_adjust( $subtotal, $cart_item, $cart_item_key ) {

	// 3.19.2, changed function name from pewc_cart_item_price_parent_products to pewc_cart_item_price_adjust because we now also use this to handle cart item price when using Divi theme cart
	if ( 'yes' === get_option( 'pewc_hide_child_products_cart', 'no' ) && isset( $cart_item['product_extras']['products'] ) ) {
		// get from session, generated from pewc_prepare_parent_products()
		$child_products_totals = WC()->session->get( 'child_products_totals');
		$parent_products_keys = WC()->session->get( 'parent_products_keys');

		if ( ! empty( $parent_products_keys[$cart_item_key]['parent_field_id'] ) && ! empty( $child_products_totals[$parent_products_keys[$cart_item_key]['parent_field_id']] ) ) {
			// this is a parent product that needs price display adjustment

			// get the total for this parent product's children
			$child_products_total = $child_products_totals[$parent_products_keys[$cart_item_key]['parent_field_id']];

			// get this parent product's price
			$parent_price = $parent_products_keys[$cart_item_key]['parent_price'];

			// this is for the line subtotal, so no need to divide by quantity
			$new_price = ($parent_price * $cart_item['quantity']) + $child_products_total;
			$old_price = $parent_price * $cart_item['quantity'];

			if ( doing_filter( 'woocommerce_cart_item_price' ) && $cart_item['quantity'] > 0 ) {
				// some child products do not get multiplied based on the parent quantity, so we need to consider that
				$new_price = $new_price / $cart_item['quantity'];
				$old_price = $parent_price;
			}

			//return wc_price($new_price); // this does not have the suffix
			$subtotal = str_replace( wc_price( $old_price ), wc_price( $new_price ), $subtotal); // this keeps the suffix
		}
	} else if ( ! empty( $cart_item['product_extras']['price_with_extras'] ) && ! apply_filters( 'pewc_disable_cart_item_price_adjust', false ) ) {
		// 3.19.2, compatibility with Divi theme cart
		$old_price = pewc_maybe_include_tax( $cart_item['data'], $cart_item['product_extras']['original_price'], true );
		$new_price = pewc_maybe_include_tax( $cart_item['data'], $cart_item['product_extras']['price_with_extras'], true );

		if ( doing_filter( 'woocommerce_cart_item_subtotal' ) && $cart_item['quantity'] > 0 ) {
			$new_price = $new_price * $cart_item['quantity'];
			$old_price = $old_price * $cart_item['quantity'];
		}

		$subtotal = str_replace( wc_price( $old_price ), wc_price( $new_price ), $subtotal); // this keeps the suffix
	}

	return $subtotal;

}
add_filter( 'woocommerce_cart_item_price', 'pewc_cart_item_price_adjust', 100, 3 );
add_filter( 'woocommerce_cart_item_subtotal', 'pewc_cart_item_price_adjust', 100, 3 );


function pewc_minicart_item_price( $price, $cart_item, $cart_item_key ) {

	if( ! empty( $cart_item['product_extras']['price_with_extras'] ) ) {

		$price = pewc_get_adjusted_product_addon_price( $cart_item, $cart_item_key );
		$price = wc_price( $price );

	}

	return $price;

}
// Removed in 3.8.10 to prevent line item price showing without tax in the cart
// add_filter( 'woocommerce_cart_item_price', 'pewc_minicart_item_price', 10, 3 );

/**
 * Add product_extra flat rates to cart.
 */
function pewc_cart_calculate_fees() {
	if( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$cart = WC()->cart->get_cart();
	$all_flat_rates = array();
	// Iterate through each cart item
	foreach( $cart as $cart_key=>$value ) {
		// Then through each group of Product Add-Ons
		if( isset( $value['product_extras']['groups'] ) ) {
			foreach( $value['product_extras']['groups'] as $group ) {
				foreach( $group as $group_key=>$item ) {
					// If the item is flat rate, then add it as a fee to the cart rather than include in the product price
					if( ! empty( $item['flat_rate'] ) ) {
						foreach( $item['flat_rate'] as $id=>$flat_rate ) {
							// Do it like this so we overwrite any duplicates
							if( ! empty( $all_flat_rates[$cart_key . '_' . $id] ) ) {
								// If we already have this ID, it's a global flat rate so only added once
								$flat_rate['label'] = apply_filters( 'pewc_filter_flat_rate_cart_global_label', $flat_rate['label'], $item );
							} else {
								// Include the product name in the label for clarity if it is not a global flat rate
								$product = wc_get_product( $value['product_id'] );
								// Include the variation
								$name = $product->get_name();
								if( apply_filters( 'pewc_allow_flat_rate_cart_label_variations', true, $item ) && ! empty( $value['variation_id'] ) ) {
									$variation = wc_get_product( $value['variation_id'] );
									$name = $variation->get_name();
								}
								$flat_rate['label'] = apply_filters( 'pewc_filter_flat_rate_cart_label', $name . ': ' . $flat_rate['label'], $item );

							}
							$all_flat_rates[$cart_key . '_' . $id] = $flat_rate;
						}
					}
				}
			}
		}
	}
	// If we have any flat rates, add them now
	if( $all_flat_rates ) {
		foreach( $all_flat_rates as $id=>$flat_rate ) {
			// But you can filter it if you like
			WC()->cart->add_fee(
				apply_filters( 'pewc_flat_rate_label', $flat_rate['label'], $id, $flat_rate ),
				apply_filters( 'pewc_flat_rate_fee', $flat_rate['price'], $id, $flat_rate ),
				apply_filters( 'pewc_flat_rate_fee_is_taxable', true, $id ),
				apply_filters( 'pewc_flat_rate_fee_tax_class', 'standard', $id )
			);
		}
	}

}
add_action( 'woocommerce_cart_calculate_fees', 'pewc_cart_calculate_fees', 25, 1 );

/**
 * We fire this after a product has been added to cart
 *
 * @param Array 		$cart_item_data Cart item meta data.
 * @param Integer   $product_id     Product ID.
 * @param Boolean  	$variation_id   Variation ID.
 *
 * @return Array
 */
function pewc_after_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

	$cart_key = ! empty( $_GET['pewc_key'] ) ? $_GET['pewc_key'] : false;

	/**
	 * Check for the minimum price
	 * @since 3.8.6
	 */
	$minimum_price = get_post_meta( $product_id, 'pewc_minimum_price', true );
	$force_minimum = get_post_meta( $product_id, 'pewc_force_minimum', true );
	if( ! $force_minimum ) {
		$force_minimum = 'no';
	}

	if( $minimum_price ) {
		$product_price = isset( $cart_item_data['product_extras']['original_price'] ) ? $cart_item_data['product_extras']['original_price'] : 0;
		$product_price = isset( $cart_item_data['product_extras']['price_with_extras'] ) ? $cart_item_data['product_extras']['price_with_extras'] : $product_price;

		$product = wc_get_product( $product_id ); // this is moved outside of the condition below because this is needed later when we adjust minimum price for tax
		if( ! $product_price ) {
			$product_price = $product->get_price();
		}
		$product_price = $product_price * $quantity;

		if( $product_price < $minimum_price && $force_minimum == 'no' ) {
			// If the price is less than the minimum and we're not forcing a minimum

			// Hide the notice that the product has been added to the cart
			add_filter( 'wc_add_to_cart_message_html', '__return_false' );

			// Display our own notice
			wc_add_notice(
				sprintf(
					'%s %s',
					apply_filters(
						'pewc_minimum_price_error_notice',
						__( 'This product has not been added to your cart. The product has a minimum price of', 'pewc' ),
						$product_id
					),
					wc_price( pewc_maybe_include_tax( $product, $minimum_price ) )
				),
				'error'
			);

      // Remove this item from the cart
			WC()->cart->remove_cart_item( $cart_item_key );

		} else if( $product_price < $minimum_price && $force_minimum == 'yes' ) {
			// Force the product price to the minimum

			// Display a notice
			wc_add_notice(
				sprintf(
					'%s %s',
					apply_filters(
						'pewc_force_minimum_price_notice',
						__( 'The price has been set to the minimum price of', 'pewc' ),
						$product_id
					),
					wc_price( pewc_maybe_include_tax( $product, $minimum_price ) )
				),
				'notice'
			);

			$cart = WC()->cart->cart_contents;
			$cart_item = $cart[$cart_item_key];

			$cart_item['product_extras']['price_with_extras'] = $minimum_price;
			WC()->cart->cart_contents[$cart_item_key] = $cart_item;
			WC()->cart->set_session();

		}

	}

}
add_action( 'woocommerce_add_to_cart', 'pewc_after_add_to_cart', 10, 6 );

/**
 * Add cart item data.
 *
 * @param Array 		$cart_item_data Cart item meta data.
 * @param Integer   $product_id     Product ID.
 * @param Boolean  	$variation_id   Variation ID.
 *
 * @return Array
 */
function pewc_add_cart_item_data( $cart_item_data, $product_id, $variation_id, $quantity=0 ) {

	$product = wc_get_product( $product_id );

	if( ( $product->get_type() == 'variable' || $product->get_type() == 'variable-subscription' ) && $variation_id !== 0 ) {
		$product = wc_get_product( $variation_id );
	} else {
		$product = wc_get_product( $product_id );
	}

	$product_price = $product->get_price();
	$extra_price = 0;
	$title_str = $product->get_title();

	$post_data = $_POST;

	if( ! isset( $cart_item_data['product_extras'] ) ) {

		$cart_item_data['product_extras'] = array(
			'product_id'	=> $product_id,
			'title'			=> $title_str,
			'groups'		=> array()
		);

	} else {

		$cart_item_data['product_extras']['product_id']	= $product_id;
		$cart_item_data['product_extras']['title']	= $title_str;
		$cart_item_data['product_extras']['groups']	= array();

	}

	// Set the language when this product was purchased
	// Only if we are using WPML
	if( pewc_get_current_wpml_language() ) {
		$cart_item_data['product_extras']['purchased_lang'] = pewc_get_current_wpml_language();
	}

	/**
	 * Delete the old cart item if this is an edited version
	 * @since 3.4.0
	 */
	if( ! empty( $_POST['pewc_delete_cart_key'] ) && pewc_user_can_edit_products() ) {
		WC()->cart->remove_cart_item( $_POST['pewc_delete_cart_key'] );
		add_filter( 'wc_add_to_cart_message_html', 'pewc_add_to_cart_message_html', 10, 3 );
	}

	// Check for product_extra groups
	$product_extra_groups = pewc_get_extra_fields( $product_id );

	// Use a final product price from a calculation field
	$use_calc_set_price = false;

	if( $product_extra_groups ) {

		// 3.22.0, build a new array with repeated groups
		$all_groups = pewc_build_groups_array_with_repeated( $product_extra_groups );

		foreach( $all_groups as $group ) {

			$group_id = $group['id'];
			$group_is_repeatable = ! empty( $group['is_repeatable'] ) ? true : false;

			// Display a group title if enabled
			if( pewc_show_group_titles_in_cart( $group_id ) == 'yes' ) {
				$group_title = pewc_get_group_title( $group_id, $group, true );
				// 3.18.2, don't add data if group title is blank
				if ( ! empty( $group_title ) ) {
					$cart_item_data['product_extras']['groups'][$group_id][$group_id] = array(
						'label'			=> $group_title,
						'value'			=> '',
						'type'			=> 'group_heading'
					);
				}
			}

			if( isset( $group['items'] ) ) {

				foreach( $group['items'] as $item ) {

					$item = apply_filters( 'pewc_filter_item_start_list', $item, $group, $group_id, $product_id );

					$show_option_prices_in_cart = pewc_show_option_prices_in_cart( $item );

					// Use this for storing values without prices in case we need to edit the product
					$value_without_price = false;

					$group_id = $item['group_id'];
					$field_id = $item['field_id'];
					$field_type = $item['field_type'];

					if( isset( $item['field_type'] ) && $item['field_type'] != 'upload' && $item['field_type'] != 'products' && $item['field_type'] != 'product-categories' ) {

						$id = $item['id'];
						$price = 0;
						$value = isset( $_POST[$id] ) ? $_POST[$id] : '';

						// 3.22.0, $_POST[$id] might be an array, so get the correct value
						if ( $group_is_repeatable ) {
							$value = pewc_filter_repeated_value( $value, $id, $item, $_POST );
						}

						if( ! $value && pewc_hide_empty_fields_cart() == 'yes' ) continue;

						$label = isset( $item['field_label'] ) ? $item['field_label'] : $item['id'];

						// If an extra is flat rate, it's not charged per product
						// It's a one-off fee that's added separately in the cart
						$is_flat_rate = ! empty( $item['field_flatrate'] ) ? true : false;
						$flat_rate_items = array();

						$total_grid_variations = ! empty( $_POST['pewc-grid-total-variations'] ) ? $_POST['pewc-grid-total-variations'] : false;

						$is_percentage = ! empty( $item['field_percentage'] ) ? true : false;

						// Assume the field is visible
						$is_visible = true;

						/**
						 * Set all the add-on data first
						 * We need the add-on data first in order to check certain conditions, e.g. cost
						 * @since 3.7.13
						 */
						if( ( ! empty( $_POST[$id] ) || apply_filters( 'pewc_allow_empty_field_values', false, $item ) ) && $is_visible ) {

							$field_price = pewc_get_field_price( $item, $product, true ); // pass true for cart price (inc or exc tax)

							// Add the value of the field (not including the value of options)
							if( ! $is_flat_rate ) {

								$price = floatval( $field_price );

							} else {

								$flat_rate_items[$field_id] = array(
									'label'		=> $label,
									'price'		=> floatval( $field_price )
								);

							}

							// Check for Calculation fields
							if( $field_type == 'calculation' ) {

								if( isset( $item['formula_action'] ) && $item['formula_action'] == 'cost' ) {

									if( ! $is_flat_rate ) {
										$price = $value;
									} else {
										$flat_rate_items[$field_id] = array(
											'label'		=> $label,
											'price'		=> $value
										);
									}

								} else if( isset( $item['formula_action'] ) && $item['formula_action'] == 'price' ) {

									$use_calc_set_price = true;

								} else {

									if( ! $is_flat_rate ) {
										// $price = $value;
									} else {
										$flat_rate_items[$field_id] = array(
											'label'		=> $label
											// 'price'		=> $value
										);
									}

								}

							}

							// Calculate price for percentage fields
							// 3.12.4 - moved here so that it doesn't overwrite the per character calculation, and so that the per character calc can use the percentage price as well
							if( $is_percentage && $field_type != 'calculation' ) {
								if( ! $is_flat_rate ) {
									$price = pewc_calculate_percentage_price( $field_price, $product );
									// $price = $value * $price;
								} else {
									$flat_rate_items[$field_id] = array(
										'label'		=> $label,
										'price'		=> pewc_calculate_percentage_price( $field_price, $product )
									);
								}
							}

							// Calculate price for per character fields
							if( ! empty( $item['per_character'] ) && ( $field_type == 'text' || $field_type == 'textarea' || $field_type == 'advanced-preview' ) ) {
								$remove_line_breaks = preg_replace( "/\r|\n/", "", $value );
								$str_length = mb_strlen( str_replace( ' ', '', $remove_line_breaks ) );
								if( ! empty( $item['field_alphanumeric_charge'] ) ) {
									// only charge alphanumeric
									$alphanum_only = preg_replace( "/\W/", "", $value);
									$str_length = strlen( $alphanum_only );
								}
								if( ! empty( $item['field_freechars'] ) ) {
									$str_length -= absint( $item['field_freechars'] );
									$str_length = max( 0, $str_length );
								}
								if( ! $is_flat_rate ) {
									$price = $str_length * $price;
								} else {
									$flat_rate_items[$field_id] = array(
										'label'		=> $label,
										'price'		=> $str_length * floatval( $field_price )
									);
								}
							}

							// Check for Name Your Price, moved here in 3.25.5 and added the empty( $item['multiply'] ) condition
							if( empty( $item['multiply'] ) && $field_type == 'name_price' ) {
								if( ! $is_flat_rate ) {
									$price = $value;
								} else {
									$flat_rate_items[$field_id] = array(
										'label'		=> $label,
										'price'		=> $value
									);
								}
							} else if( ! empty( $item['multiply'] ) && ( $field_type == 'number' || $field_type == 'name_price' ) ) {
								// Calculate price for multiply fields
								if( ! $is_flat_rate ) {
									$price = $value * $price;
								} else {
									$flat_rate_items[$field_id] = array(
										'label'		=> $label,
										'price'		=> $value * floatval( $field_price )
									);
								}
							}

							// Filtered by Bookings to include per unit cost for extras
							$price = apply_filters( 'pewc_filter_cart_item_data_price', $price, $cart_item_data, $item, $group_id, $field_id );

							// Find any additional cost for options and select fields
							if( ! empty( $item['field_options'] ) ) {

								// Record checkbox group values differently
								$checkbox_group_values = array();

								// Radio buttons are arrays, select are simple values
								// 3.26.5, added condition is_array( $value ), because Radio field value seems to be a single value now?
								if( ( $field_type == 'radio' && is_array( $value ) ) || ( $field_type == 'image_swatch' && empty( $item['allow_multiple'] ) ) ) {
									$option_value = stripslashes( $value[0] ); // 3.24.5, added stripslashes
								} else if ( ( $field_type == 'select' || $field_type == 'select-box' ) && is_string( $value ) ) {
									$option_value = stripslashes( $value ); // 3.24.6
								} else {
									$option_value = $value;
								}
								//$value_without_price = $option_value; // 3.24.5, commented out, moved to below

								// Some fields, like radio fields, have a key element
								// We use the key element, which has been sanitised, to find the value element, which is the proper label for the field value
								// The key element was only introduced in 2.4.5
								if( ! empty( $item['field_options'][0]['key'] ) ) {
									foreach( $item['field_options'] as $field_option ) {
										if( $field_option['key'] == $option_value ) {
											// Change the value (the label for the extra field) to the value element rather than the key element
											$value = $field_option['value'];
											break;
										}
									}
								}

								$options_total_price = 0;
								$multiple_options = ( $field_type == 'checkbox_group' || ( $field_type == 'image_swatch' && ! empty( $item['allow_multiple'] ) ) ) ? true : false; // 3.24.1
								$option_index = 0; // 3.26.0, formulas in prices

								foreach( $item['field_options'] as $option ) {

									// Strip slashes from $option_value
									if( is_array( $option_value ) ) {
										$option_value = pewc_stripslashes_from_options( $option_value );
									}

									// If it's a checkbox group, we need to total all selected options
									if( $multiple_options ) {

										if( ! empty( $option['price'] ) && in_array( $option['value'], $option_value ) ) {

											// third argument $cart_price added on 3.9.5. Return price as to be displayed on the cart (inc or exc tax)
											$option_price = pewc_get_option_price( $option, $item, $product, true, $option_index );

											if( $is_percentage ) {
												$option_price = pewc_calculate_percentage_price( $option_price, $product );
											}

											if( ! $is_flat_rate ) {

												$price += floatval( $option_price );
												/**
												 * Removed in 3.7.1 to avoid tax getting doubled
												 */
												// $option_price = pewc_maybe_include_tax( $product, $option_price );
												$checkbox_group_values[] = $show_option_prices_in_cart === true ? $option['value'] . ' (' . wc_price( $option_price ) . ')' : $option['value'];

											} else {

												$options_total_price += floatval( $option_price );
												$checkbox_group_values[] = $show_option_prices_in_cart === true ? $option['value'] . ' (' . wc_price( $option_price ) . ')' : $option['value'];

											}

										} else if( empty( $option['price'] ) && in_array( $option['value'], $option_value ) ) {

											// Added in 3.7.6 to ensure that groups where some options had prices (but not all) were captured correctly
											$checkbox_group_values[] = $option['value'];

										}

									} else if( ! empty( $option['price'] ) && $option['value'] == stripslashes( $option_value ) ) {

										if ( isset( $item['option_price_visibility'] ) && $item['option_price_visibility'] === 'value' ) {
											// option is used for value only on Calculation fields, so don't add to the totals
											continue;
										}

										// third argument $cart_price added on 3.9.5. Return price as to be displayed on the cart (inc or exc tax)
										$option_price = pewc_get_option_price( $option, $item, $product, true, $option_index );

										// $option_price = pewc_maybe_include_tax( $product, $option_price );

										if( $is_percentage ) {
											$option_price = pewc_calculate_percentage_price( $option_price, $product );
										}

										if( ! $is_flat_rate ) {
											$price += floatval( $option_price );
											//$value_without_price = $option_value; // Used when restoring a product from the cart for editing
											$value = $show_option_prices_in_cart === true ? $option_value . ' (' . wc_price( $option_price ) . ')' : $option_value;
											break;
										} else {
											$flat_rate_items[$field_id] = array(
												'label'		=> $label . ' (' . $option_value . ')',
												'price'		=> floatval( $option_price ) + floatval( $field_price )
											);
											//$value_without_price = $option_value; // Used when restoring a product from the cart for editing
											$value = $show_option_prices_in_cart === true ? $option_value . ' (' . wc_price( $option_price ) . ')' : $option_value;
										}
									}

									$option_index++;

								}

								// 3.24.5, moved here so that values are stripped of slashes
								$value_without_price = $option_value; // Used when restoring a product from the cart for editing

								// Add the flat rate for the checkboxes here
								// 3.24.1, updated condition so that image_swatch is also considered
								if( $multiple_options && $is_flat_rate ) {
									// Need to add the field cost as well
									$field_price = pewc_calculate_percentage_price( $field_price, $product );
									$flat_rate_items[$field_id] = array(
										'label'		=> $label,
										'price'		=> floatval( $options_total_price ) + floatval( $field_price )
									);
									$value = $item['field_label'];
								}

								// Removed field_type check in 3.4.0 to update image_swatch fields with multiple selections
								// This allows these fields to carry a default value back when being edited
								// if( ! empty( $checkbox_group_values ) && $field_type == 'checkbox_group' ) {
								if( ! empty( $checkbox_group_values ) ) {
									$value = join( ' | ', $checkbox_group_values );
								}

							}

							// Just ensure we haven't ended up with any arrays here - $value will be displayed as meta data in the cart and order
							if( is_array( $value ) ) {
								$value = join( ' ', $value );
							}

							// Filter the price of the product extra
							$price = apply_filters( 'pewc_add_cart_item_data_price', $price, $item, $product_id );

							if( $total_grid_variations ) {
								$price = $price * $total_grid_variations;
							}

							if( $item['field_type'] == 'textarea' ) {
								$value = sanitize_textarea_field( stripslashes( $value ) );
							} else if( in_array( $field_type, array( 'image_swatch', 'radio' ) ) ) {
								$value = wp_kses_post( stripslashes( $value ) );
							} else {
								$value = sanitize_text_field( stripslashes( $value ) );
							}

							$cart_item_field_data = array(
								'type'			=> $item['field_type'],
								'label'			=> isset( $item['field_label'] ) ? sanitize_text_field( $item['field_label'] ) : '',
								'id'    		=> esc_attr( $id ),
								'group_id'  => $group_id,
								'field_id'  => $field_id,
								'price'   	=> floatval( $price ),
								'value'   	=> $value,
								'flat_rate'	=> $flat_rate_items,
								'hidden'		=> ! empty( $item['hidden_calculation'] ) ? sanitize_text_field( $item['hidden_calculation'] ) : '',
								'price_visibility' => isset( $item['price_visibility'] ) ? $item['price_visibility'] : '',
								'field_visibility' => isset( $item['field_visibility'] ) ? $item['field_visibility'] : '',
							);

							// 3.25.5, used when displaying multiplied Name Your Price field
							if ( ! empty( $item['multiply'] ) ) {
								$cart_item_field_data['multiply'] = $item['multiply'];
							}

							// We use this value when editing a product from the cart
							if( $value_without_price ) {
								//$cart_item_data['product_extras']['groups'][$group_id][$field_id]['value_without_price'] = $value_without_price;
								$cart_item_field_data['value_without_price'] = $value_without_price;
							}

							if( $field_type == 'calculation' && ( ! isset( $item['formula_action'] ) || $item['formula_action'] != 'cost' ) ) {
								//unset( $cart_item_data['product_extras']['groups'][$group_id][$field_id]['price'] );
								unset( $cart_item_field_data['price'] );
							}

							if( ! empty( $item['per_character'] ) ) {
								//$cart_item_data['product_extras']['groups'][$group_id][$field_id]['per_character'] = 1;
								$cart_item_field_data['per_character'] = 1;
							}

							// 3.22.0
							if ( ! empty( $group['clone_count'] ) ) {
								// let's save the cloned groups separately, so that they don't affect the default way we display add-on fields?
								$cart_item_data['product_extras']['cloned_groups'][$group_id][$group['clone_count']][$field_id] = $cart_item_field_data;
							} else {
								// this is not a repeatable group, or is the original group in a repeatable group
								$cart_item_data['product_extras']['groups'][$group_id][$field_id] = $cart_item_field_data;
							}

							do_action( 'pewc_end_add_cart_item_data', $cart_item_data, $item, $group_id, $field_id );

							$cart_item_data = apply_filters( 'pewc_filter_end_add_cart_item_data', $cart_item_data, $item, $group_id, $field_id, $value );

						} // $is_visible

						$is_visible = pewc_get_conditional_field_visibility( $id, $item, $group['items'], $product_id, $_POST, $variation_id, $cart_item_data, $quantity, $group_id, $group );

						// Unset the add-on data here if the field isn't visible
						if( ! $is_visible ) {

							if ( $group_is_repeatable ) {
								// 3.26.5, it's possible that this is a cloned field that is not visible for its own set of conditions, while the other cloned / sibling groups are still available. For now, let's not remove it and add a marker
								if ( ! empty( $group['clone_count'] ) ) {
									// this is a cloned field
									$cart_item_data['product_extras']['cloned_groups'][$group_id][$group['clone_count']][$field_id]['hidden'] = true;
									// additional date, used in pewc_add_custom_data_to_order()
									$cart_item_data['product_extras']['cloned_groups'][$group_id][$group['clone_count']][$field_id]['is_repeatable'] = true;
								} else {
									// this is a field in the parent repeatable group
									$cart_item_data['product_extras']['groups'][$group_id][$field_id]['hidden'] = true;
									// additional date, used in pewc_add_custom_data_to_order()
									$cart_item_data['product_extras']['groups'][$group_id][$field_id]['is_repeatable'] = true;
								}
							} else {
								unset( $cart_item_data['product_extras']['groups'][$group_id][$field_id] );
							}
							// 3.13.4, allows Text Preview and other plugins to unset their custom cart_item_data
							$cart_item_data = apply_filters( 'pewc_filter_not_visible_unset_cart_item_data', $cart_item_data, $group_id, $field_id );

						} else {

							// used in totals
							// I think this is needed to put back the original product price, especially if "prices entered with" and "display prices during cart" are different
							// 3.19.2, added $item argument to the filter below
							if( apply_filters( 'pewc_maybe_adjust_tax_price_with_extras', true, $item ) ) {
								if ( $price > 0 || ( $price < 0 && apply_filters('pewc_allow_tax_on_negative_prices', false ) ) ) {
									$price = pewc_get_adjusted_product_addon_field_price( $price, $product );
									// also adjust cart item price for totals
									if ( $group_is_repeatable && ! empty( $group['clone_count'] ) && isset( $cart_item_data['product_extras']['cloned_groups'][$group_id][$group['clone_count']][$field_id]['price'] ) ) {
										$cart_item_data['product_extras']['cloned_groups'][$group_id][$group['clone_count']][$field_id]['price'] = $price;
									} else if( isset( $cart_item_data['product_extras']['groups'][$group_id][$field_id]['price'] ) ) {
										$cart_item_data['product_extras']['groups'][$group_id][$field_id]['price'] = $price;
									}
								} else if ( $is_flat_rate && isset($cart_item_data['product_extras']['groups'][$group_id][$field_id]['flat_rate']) ) {
									// Flat rates have 0 prices, so we catch them here instead
									$flat_rate_price = $cart_item_data['product_extras']['groups'][$group_id][$field_id]['flat_rate'][$field_id]['price'];
									// Adjust flat rate prices as well. Since by default tax is added on flat fees, we need to remove tax if setting is "display incl tax in cart"
									if ( $flat_rate_price > 0 && get_option( 'woocommerce_tax_display_cart' ) == 'incl') {
										$flat_rate_price = pewc_get_price_without_tax( $flat_rate_price, $product );
										$cart_item_data['product_extras']['groups'][$group_id][$field_id]['flat_rate'][$field_id]['price'] = $flat_rate_price;
									}
								}
							}

							if ( pewc_is_repeatable_field( $item ) && ! pewc_multiply_repeatable_with_quantity( $item ) && $quantity > 0 ) {
								// 3.26.7, we divide the repeatable add-on field price by the quantity, because the cart_item_data price is multiplied with the quantity when displayed on the cart.
								// This is the default behavior of repeated fields, a cloned field price is treated differently from the original. 
								$extra_price += floatval( $price/$quantity );
							} else {
								$extra_price += floatval( $price );
							}

							// Are we using a price set by a calculation field?
							if( $use_calc_set_price ) {

								// Remove these filters to prevent F+D applying the role-based price instead of the calculated price
								remove_filter( 'woocommerce_product_get_price', 'wcfad_get_regular_price', 10, 2 );
								remove_filter( 'woocommerce_product_variation_get_price', 'wcfad_get_regular_price', 10, 2 );

								$new_price = isset( $_POST['pewc_calc_set_price'] ) ? $_POST['pewc_calc_set_price'] : 0;
								$cart_item_data['product_extras']['use_calc_set_price'] = true;

								// for improvement later: maybe change pewc_adjust_tax() a bit and add these conditions there
								if ( 'yes' == get_option('woocommerce_calc_taxes') ) {
									if ( 'incl' == get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
										// remove tax from price if prices display on shop is tax-inclusive, to avoid double taxing
										$new_price = pewc_get_price_without_tax( $new_price, $product );
									}
									else if ( 'excl' == get_option( 'woocommerce_tax_display_shop' ) && wc_prices_include_tax() ) {
										// add tax to price if prices display on shop is tax-exclusive
										$tmp_cart_item = array(
											'data' => $product
										); // we need cart_item below, but it's not available, so create a tmp one
										$tax_rate = pewc_get_tax_rate( $tmp_cart_item );
										$new_price = $new_price * $tax_rate;
									}
								}

							} else {

								$new_price = floatval( $product_price ) + floatval( $extra_price );

							}

							// Ensure price can't be less than 0
							if( $new_price < 0 ) $new_price = 0;

							// Set parameter to record total product price including extras
							// Set this here before we start doing the uploads
							$cart_item_data['product_extras']['price_with_extras'] = floatval( $new_price );
							$cart_item_data['product_extras']['original_price'] = floatval( $product_price );

						}

					} else if( ( isset( $item['field_type'] ) && ( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories' ) ) && pewc_is_pro() ) {

						$field_id = $item['id'];

						// Add-on products are handled differently
						$is_visible = pewc_get_conditional_field_visibility( $field_id, $item, $group['items'], $product_id, $_POST, $variation_id, $cart_item_data, $quantity, $group_id, $group );

						if( ( ! empty( $_POST[$field_id . '_parent_product'] ) || ! empty( $_POST[$field_id . '_child_product'] ) || ! empty( $_POST[$field_id . '_grid_child_variation'] ) ) && $is_visible ) {

							// Added in 3.5.3 to ensure hidden child fields don't get added to the cart
							if( empty( $cart_item_data['product_extras']['child_fields'] ) ) {
								$cart_item_data['product_extras']['child_fields'] = array( $field_id ); // This is the defunct method of identifying fields, e.g. pewc_group_GROUP-ID_FIELD-ID
								$cart_item_data['product_extras']['child_field_ids'] = array( $item['field_id'] ); // This is the simple method, just the field ID
							} else {
								$cart_item_data['product_extras']['child_fields'][] = $field_id;
								$cart_item_data['product_extras']['child_field_ids'][] = $item['field_id'];
							}

							if ( ! empty( $_POST[$field_id . '_parent_product'] ) ) {
								$child_product_id = $_POST[$field_id . '_parent_product']; // 3.15.0, swatch variation field
							} else if ( ! empty( $_POST[$field_id . '_child_product'] ) ) {
								$child_product_id = $_POST[$field_id . '_child_product'];
							} else {
								$child_product_id = $_POST[$field_id . '_grid_child_variation']; // maybe using grid
							}

							// Check if one or more child products has been selected and confirm that the main product is not in the array of child products
							$parent_product_hash = $_POST['pewc_product_hash'];

							if( ! isset( $cart_item_data['product_extras']['products']['child_products'] ) ) {
								$cart_item_data['product_extras']['products']['field_id'] = $field_id;
								$cart_item_data['product_extras']['products']['child_products'] = array();
							}

							// 3.15.0, use to keep track of how many quantities per child product
							$child_products_quantities = array();

							// Add child product data to the main product
							if ( ! empty( $_POST[$field_id . '_grid_child_variation'] ) ) {

								// We use $value if we are displaying child product field IDs in the cart meta for the parent product
								$value = array();

								// Adding multiple child products from grid
								foreach( $child_product_id as $each_id => $each_quantity ) {

									if( $each_quantity > 0 ) {

										$cart_item_data['product_extras']['products']['child_products'][$each_id] = array(
											'child_product_id' 	=> $child_product_id,
											'field_id' 					=> $field_id,
											'quantities'				=> $_POST[$field_id . '_quantities'],
											'quantity'					=> $each_quantity,
											'allow_none'				=> $_POST[$field_id . '_allow_none']
										);

										$value[] = $each_id;
										$child_products_quantities[$each_id] = $each_quantity; // 3.15.0
									}

								}

								$value = join( ',', $value );

							} else if( ! is_array( $child_product_id ) ) {

								if ( 'select' === $item['products_layout'] && 'independent' === $item['products_quantities'] && empty( $_POST[ $field_id . '_child_quantity' ] ) ) {
									continue; // don't add to metadata if quantity is 0
								}

								if ( 'linked' !== $item['products_quantities'] ) {
									// independent and one-only go here
									if ( isset( $_POST[$field_id . '_child_quantity_' . $child_product_id] ) ) {
										$child_product_quantity = intval( $_POST[$field_id . '_child_quantity_' . $child_product_id] );
									} else if ( isset( $_POST[$field_id . '_child_quantity'] ) ) {
										$child_product_quantity = intval( $_POST[$field_id . '_child_quantity'] );
									} else {
										$child_product_quantity = 1;
									}
								} else {
									$child_product_quantity = $quantity; // parent product quantity
								}

								$cart_item_data['product_extras']['products']['child_products'][$child_product_id] = array(
									'child_product_id' 	=> $child_product_id,
									'field_id' 					=> $field_id,
									'quantities'				=> $_POST[$field_id . '_quantities'],
									'quantity'					=> $child_product_quantity,
									'allow_none'				=> $_POST[$field_id . '_allow_none']
								);

								$value = $child_product_id;
								$child_products_quantities[$child_product_id] = $child_product_quantity; // 3.15.0

							} else {

								// We use $value if we are displaying child product field IDs in the cart meta for the parent product
								$value = array();

								// Adding multiple child products from checkboxes
								foreach( $child_product_id as $each_id ) {

									if ( isset( $_POST[$field_id . '_child_quantity_' . $each_id] ) ) {
										$child_product_quantity = intval( $_POST[$field_id . '_child_quantity_' . $each_id] );
									} else if ( isset( $_POST[$field_id . '_child_quantity'] ) ) {
										$child_product_quantity = intval( $_POST[$field_id . '_child_quantity'] );
									} else if ( 'linked' === $item['products_quantities'] ) {
										$child_product_quantity = $quantity; // parent quantity
									} else {
										$child_product_quantity = 1;
									}

									// 3.15.0
									if ( ! empty( $_POST['pewc_child_variants_' . $field_id . '_' . $each_id ] ) && $child_product_quantity > 0 ) {
										// use variation ID instead for column fields
										$each_id = intval( $_POST['pewc_child_variants_' . $field_id . '_' . $each_id ] );
									} else if ( ! empty( $_POST[$field_id . '_child_variation'][$each_id] ) ) {
										// use variation ID instead for swatch fields
										$each_id = intval( $_POST[$field_id . '_child_variation'][$each_id] );
									}

									$cart_item_data['product_extras']['products']['child_products'][$each_id] = array(
										'child_product_id' 	=> $child_product_id,
										'field_id' 					=> $field_id,
										'quantities'				=> $_POST[$field_id . '_quantities'],
										'quantity'					=> $child_product_quantity,
										'allow_none'				=> $_POST[$field_id . '_allow_none']
									);

									$value[] = $each_id;
									$child_products_quantities[$each_id] = $child_product_quantity; // 3.15.0

								}

								$value = join( ',', $value );

							}

							if ( empty( $value ) ) {
								// no child products, skip
								continue;
							}

							// $cart_item_data['product_extras']['products'][$parent_product_hash]['child_products'] = $_POST['_pewc_child_products'];
							// If we've added a child product to this item, let's link them in the cart
							$cart_item_data['product_extras']['products']['pewc_parent_product'] = $product_id;
							$cart_item_data['product_extras']['products']['parent_field_id'] = $parent_product_hash;

							// Still add some data in case we want to be able to specify what child products belong to a parent product
							$cart_item_data['product_extras']['groups'][$group_id][$field_id] = array(
								'type'			=> $item['field_type'],
								'label'			=> isset( $item['field_label'] ) ? sanitize_text_field( $item['field_label'] ) : '',
								'id'    		=> esc_attr( $item['id'] ),
								'group_id'  => $item['group_id'],
								'field_id'  => $item['field_id'],
								'value'			=> apply_filters( 'pewc_cart_item_value_child_products', $value, $item ),
								'field_visibility' => isset( $item['field_visibility'] ) ? $item['field_visibility'] : '',
								'child_products_quantities' => $child_products_quantities // 3.15.0
							);

							// 3.13.0
							do_action( 'pewc_end_add_cart_item_data', $cart_item_data, $item, $group_id, $field_id );

							$cart_item_data = apply_filters( 'pewc_filter_end_add_cart_item_data', $cart_item_data, $item, $group_id, $field_id, $value );

						}

						// 3.26.13, if these are not yet set, variation-level user role pricing in DPDR is applied twice
						if ( ! isset( $cart_item_data['product_extras']['original_price'] ) ) {
							$cart_item_data['product_extras']['original_price'] = floatval( $product_price );
						}
						if ( ! isset( $cart_item_data['product_extras']['price_with_extras'] ) ) {
							$cart_item_data['product_extras']['price_with_extras'] = floatval( $product_price );
						}

					} // end of products or product-categories fields

				}

			}

		}

		do_action( 'pewc_cart_item_data_after_groups', $cart_item_data, $product_extra_groups );

	}

	// Do the file uploads separately

	if( pewc_enable_ajax_upload() == 'yes' ) {

		// Iterate through each field and get all the files

		foreach( $product_extra_groups as $group ) {

			if( isset( $group['items'] ) ) {

				foreach( $group['items'] as $item ) {

					$field_id = $item['field_id'];

					if( ! empty( $_POST['pewc_file_data'][$field_id] ) ) {

						// Make this an array like $_FILES
						if( empty( $files ) ) {

							$files = pewc_get_files_array( $_POST['pewc_file_data'][$field_id], $item['id'], $product_id );

						} else {

							$files = array_merge( $files, pewc_get_files_array( $_POST['pewc_file_data'][$field_id], $item['id'], $product_id ) );

						}

						$is_ajax_upload = true;

					}

				}

			}

		}

		// Add some quantities?

	} else if( ! empty( $_FILES ) ) {

		// Standard method

		$files = $_FILES;
		$is_ajax_upload = false;

	}

	if( isset( $files ) ) {

		$max = pewc_get_max_upload();
		$max_mb = $max * pow( 1024, 2 );

		foreach( $files as $id=>$file ) {

			// Work out group and field IDs from the $id
			$last_index = strrpos( $id, '_' );
			$field_id = substr( $id, $last_index + 1 ); // Find last instance of _
			$group_id = substr( $id, 0, $last_index ); // Remove _field_id from $id
			//$field_id = str_replace( '_', '', $field_id );
			$group_id = strrchr( $group_id, '_' );
			$group_id = str_replace( '_', '', $group_id );

			if ( ! isset( $product_extra_groups[$group_id] ) ) {
				continue; // 3.21.4, if this is not set, the rest of this loop can't be processed, so skip
			}
			$group = $product_extra_groups[$group_id];
			$item = $group['items'][$field_id];
			$is_visible = pewc_get_conditional_field_visibility( $id, $item, $group['items'], $product_id, $_POST, $variation_id, $cart_item_data, $quantity, $group_id, $group );

			if( isset( $product_extra_groups[$group_id]['items'][$field_id] ) && $is_visible ) {

				$item = $product_extra_groups[$group_id]['items'][$field_id];
				$label = isset( $item['field_label'] ) ? $item['field_label'] : '';
				$price = pewc_get_field_price( $item, $product, true ); // pass true for cart-dependent price (inc or exc tax)

				// Calculate multiple upload price
				if( ! empty( $_POST[$item['id'] . '_number_uploads'] ) && ! empty( $_POST[$item['id'] . '_multiply_price'] ) ) {
					// 3.22.1
					if ( ! empty( $item['quantity_per_upload'] ) && ! empty( $item['price_quantity_per_upload'] ) && isset( $_POST[$id . '_extra_fields']['quantity'] ) && is_array( $_POST[$id . '_extra_fields']['quantity'] ) ) {
						// Multiply the price by the total quantity per upload
						$total_quantity_per_upload = 0;
						foreach ( $_POST[$id . '_extra_fields']['quantity'] as $index => $quantity_per_upload ) {
							$total_quantity_per_upload += (int) $quantity_per_upload;
						}
						$price = $price * $total_quantity_per_upload;
					} else {
						// Multiply the price by the number of uploads
						$price = $price * floatval( $_POST[$item['id'] . '_number_uploads'] );
					}
				}

				$flat_rate_items = array();
				$is_flat_rate = ! empty( $item['field_flatrate'] ) ? true : false;
				if( $is_flat_rate ) {
					$flat_rate_items = array(
						$field_id => array(
							'label'		=> $label,
							'price'		=> floatval( $price )
						)
					);
				}

				$can_upload = pewc_can_upload();

				if( $can_upload ) {

					$uploads = array();

					if( empty( $file['size'] ) ) {
						continue;
					}

					foreach( $file['size'] as $i=>$size ) {

						// Check file size
						if( empty( $size ) || $size > $max_mb ) {

							// File size wrong

						} else {

							$upload_file = array(
								'name'			=> $file['name'][$i],
								'display'		=> $file['name'][$i],
								'type'			=> $file['type'][$i],
								'tmp_name'	=> $file['tmp_name'][$i],
								'error'			=> $file['error'][$i],
								'size'			=> $file['size'][$i],
								'quantity'	=> isset( $_POST[$id . '_extra_fields']['quantity'][$i] ) ? $_POST[$id . '_extra_fields']['quantity'][$i] : false
							);

							if( isset( $file['url'][$i] ) && empty( $upload_file['url'] ) ) {
								$upload_file['url'] = esc_url( $file['url'][$i] );
							}

							// 3.26.9 - moved this filter to after the url param gets set
							$upload_file = apply_filters( 'pewc_add_cart_item_data_upload_file', $upload_file, $file, $id, $i, $_POST );

							//$upload_file['file'] = isset( $file['file'][$i] ) ? esc_url( $file['file'][$i] ) : '';
							if ( isset( $file['file'][$i] ) ) {
								$upload_file['file'] = $file['file'][$i];
								// 3.12.2. esc_url causes issues in systems with spaces in the path e.g. Windows local dev site. Allow users to bypass this.
								if ( ! apply_filters( 'pewc_disable_esc_url_uploaded_file', false, $item, $product_id ) ) {
									$upload_file['file'] = esc_url( $upload_file['file'] );
								}
							} else {
								$upload_file['file'] = '';
							}

							if( ! $is_ajax_upload ) {

								// We need to upload the files if they haven't already been uploaded via AJAX
								$upload = pewc_handle_upload( $upload_file );
								if ( false === $upload ) {
									// 3.12.3, error in uploading
								} else {
									$upload['name'] = $file['name'][$i];
									$upload['display'] = $file['name'][$i];

									if( empty( $upload['error'] ) && ! empty( $upload['file'] ) ) {
										$uploads[] = $upload;
									}
								}

							} else {

								// AJAX upload has already been done

								// add the PDF thumb to the array so that we can put it back when Edit cart is enabled
								if ( ! empty( $file['pdf_thumb'][$i] ) ) {
									$upload_file['pdf_thumb'] = $file['pdf_thumb'][$i];
								}

								$base_url = get_site_url();
								$truncated_url = $upload_file['url'];
								if( ! apply_filters( 'pewc_offload_media', false ) ) {
									// Stitch our URL back together again
									$upload_file['url'] = $base_url . $truncated_url; // If we're offloading uploads, the URL will already be absolute here
								}
								
								$uploads[] = $upload_file;

							}

						}

					}

					if( ! empty( $uploads ) ) {

						$price = apply_filters( 'pewc_filter_price_for_uploads', $price, $cart_item_data, $item, $group_id, $field_id );

						// tax adjustment is done here
						// 3.19.2, added $item argument to the filter below
						if( apply_filters( 'pewc_maybe_adjust_tax_price_with_extras', true, $item ) && $price > 0 ) {
							$price = pewc_get_adjusted_product_addon_field_price($price, $product);
							// also adjust cart item price for totals
							if (isset($cart_item_data['product_extras']['groups'][$group_id][$field_id]['price']))
								$cart_item_data['product_extras']['groups'][$group_id][$field_id]['price'] = $price;
						}

						$cart_item_data['product_extras']['groups'][$group_id][$field_id] = apply_filters(
							'pewc_filter_cart_item_data',
									array(
									'files'			=> $uploads,
									// 'file'			=> $upload['file'], // Save this so we can delete file later
									'type'			=> 'upload',
									'label'			=> sanitize_text_field( $label ),
									'id'    		=> esc_attr( $id ),
									'group_id'  => $group_id,
									'field_id' 	=> $field_id,
									'price'   	=> floatval( $price ),
									// 'url'   		=> wc_clean( $upload['url'] ),
									// 'display' 	=> basename( wc_clean( $upload['url'] ) ),
									'flat_rate'	=> $flat_rate_items,
									'price_visibility' => isset( $item['price_visibility'] ) ? $item['price_visibility'] : '',
									'field_visibility' => isset( $item['field_visibility'] ) ? $item['field_visibility'] : '',
								),
							$item,
							$group_id,
							$field_id,
							$uploads
						);

						// Only add the cost of the extra to the product price if it's not flat rate
						if( ! $is_flat_rate ) {
							$extra_price += floatval( $price );
						}

						// Ensure price can't be less than 0
						$new_price = floatval( $product_price ) + floatval( $extra_price );
						if( $new_price < 0 ) $new_price = 0;

						// Set parameter to record total product price including extras
						if( empty( $_POST['pewc_calc_set_price'] ) ) {
							$cart_item_data['product_extras']['price_with_extras'] = floatval( $new_price );
							$cart_item_data['product_extras']['original_price'] = floatval( $product_price );
						}

						// 3.9.7: Files have been added to the cart, so maybe remove them from session now. Passing a blank value should do it
						pewc_save_uploaded_files_to_session( '', $field_id );

					}

				}

			}

		}

	}

	/**
	 * @since 3.3.3
	 * Ensures that any child product does not have its discounted price overwritten
	 */
	if( isset( $cart_item_data['product_extras']['price_with_extras_discounted'] ) ) {
		$cart_item_data['product_extras']['price_with_extras'] = floatval( $cart_item_data['product_extras']['price_with_extras_discounted'] );
	}

	return apply_filters( 'pewc_after_add_cart_item_data', $cart_item_data, $product_extra_groups );

}
add_filter( 'woocommerce_add_cart_item_data', 'pewc_add_cart_item_data', 10, 4 );

/**
 * Validate cart item data.
 *
 * @param Array 	$cart_item_data Cart item meta data.
 * @param Integer   $product_id     Product ID.
 * @param Boolean  	$variation_id   Variation ID.
 *
 * @return Array
 */
function pewc_validate_cart_item_data( $passed, $product_id, $quantity, $variation_id=null, $cart_item_data=array() ) {

	// Check for product_extra groups
	$product_extra_groups = pewc_get_extra_fields( $product_id );

	if( $product_extra_groups ) {

		$max = pewc_get_max_upload();
		$max_mb = $max * pow( 1024, 2 );
		$products_qty_to_be_added = array(); // 3.17.2, used when validating child products stock

		// 3.26.5, build a new array with repeated groups
		$all_groups = pewc_build_groups_array_with_repeated( $product_extra_groups );

		//foreach( $product_extra_groups as $group_id => $group ) 
		foreach( $all_groups as $group ) {

			// 3.26.5
			$group_id = $group['id'];
			$group_is_repeatable = ! empty( $group['is_repeatable'] ) ? true : false;

			// The group requirement setting
			// This is going to be deprecated in favour of conditionals for groups
			$group_req = false; // No requirement set by default
			if( ! empty( $group['meta']['group_required'] ) ) {
				$group_req = $group['meta']['group_required'];
			}

			if( isset( $group['items'] ) ) {

				// 3.22.0
				$group_is_repeatable = pewc_get_group_repeatable( $group_id );
				$group_title = pewc_get_group_title( $group_id, $group, pewc_has_migrated() ); // used later
				// add some data to $group which we will use in pewc_get_conditional_field_visibility() filter
				$group['is_repeatable'] = $group_is_repeatable;
				$group['group_title'] = $group_title;
				if ( $group_is_repeatable ) {
					$group['repeat_by_quantity'] = pewc_get_group_repeatable_by_quantity( $group_id );
					$group['labeling_type'] = pewc_repeatable_labeling( $group_id );
					$group['label_format'] = pewc_repeatable_label_format( $group['labeling_type'], 'notice' );
				}

				foreach( $group['items'] as $item ) {

					// 3.22.0, let's add it to $item so that we can pass it to our validator function
					$item['group_is_repeatable'] = $group_is_repeatable;

					$id = $item['id'];
					// If label isn't set, use id
					$label = $id;
					if( isset( $item['field_label'] ) ) {
						$label = $item['field_label'];
					}

					// Check if the field is required
					$field_req = false;
					if( ! empty( $item['field_required'] ) ) {
						$field_req = $item['field_required'];
					}

					$is_visible = pewc_get_conditional_field_visibility( $id, $item, $group['items'], $product_id, $_POST, $variation_id, $cart_item_data, $quantity, $group_id, $group );

					if( ! $is_visible ) {
						// If the field is hidden by a condition, it can't be required
						$is_required = false;
					} else {
						// Will reinstate something similar to this with group conditionals
						// $is_required = pewc_is_field_required( $group_req, $field_req, $id, $group['items'] );
						$is_required = $field_req;
					}

					if( isset( $item['field_type'] ) && ( $item['field_type'] == 'text' || $item['field_type'] == 'textarea' || $item['field_type'] == 'advanced-preview' ) ) {

						// 3.22.0
						$passed = pewc_validate_cart_item_data_text( $passed, $is_required, $item, $label, $group, $_POST );

						/*if( empty( $_POST[$id] ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;
						} else if ( ! empty( $_POST[$id] ) ) {
							// Character length
							if( ! empty( $item['field_minchars'] ) || ! empty( $item['field_maxchars'] ) ) {
								$length = isset( $_POST[$id] ) ? mb_strlen( str_replace( ' ', '', $_POST[$id] ) ) : 0;
								if( ! empty( $item['field_minchars'] ) && $length < $item['field_minchars'] ) {
									wc_add_notice( apply_filters( 'pewc_filter_minchars_validation_notice', esc_html( $label ) . __( ': minimum number of characters: ', 'pewc' ) . esc_html( $item['field_minchars'] ), $label, $item ), 'error' );
									$passed = false;
								} else if( ! empty( $item['field_maxchars'] ) && $length > $item['field_maxchars'] ) {
									wc_add_notice( apply_filters( 'pewc_filter_maxchars_validation_notice', esc_html( $label ) . __( ': maximum number of characters: ', 'pewc' ) . esc_html( $item['field_maxchars'] ), $label, $item ), 'error' );
									$passed = false;
								}
							}
						}*/

					} else if( isset( $item['field_type'] ) && $item['field_type'] == 'date' ) {
						if( empty( $_POST[$id] ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;
						}

					} else if( isset( $item['field_type'] ) && $item['field_type'] == 'checkbox' ) {
						if( empty( $_POST[$id] ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;
						}

					} else if( isset( $item['field_type'] ) && ( $item['field_type'] == 'checkbox_group' || $item['field_type'] == 'image_swatch') && pewc_is_pro() ) {
						if( empty( $_POST[$id] ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;
						}
						// Check for minimum and maximum number of checkboxes
						if( ! empty( $item['field_minchecks'] ) && ! empty( $_POST[$id] ) && count( $_POST[$id] ) < $item['field_minchecks'] ) {
							// Not enough checkboxes checked
							wc_add_notice(
								apply_filters(
									'pewc_filter_minchecks_notice',
									sprintf(
										__( '%s requires at least %s items to be selected', 'pewc' ),
										$label,
										$item['field_minchecks']
									),
									$label,
									$item['field_minchecks']
								),
								'error'
							);
							$passed = false;
						}
						if( ! empty( $item['field_maxchecks'] ) && ! empty( $_POST[$id] ) && count( $_POST[$id] ) > $item['field_maxchecks'] ) {
							// Not enough checkboxes checked
							wc_add_notice(
								apply_filters(
									'pewc_filter_maxchecks_notice',
									sprintf(
										__( '%s requires a maximum of %s items to be selected', 'pewc' ),
										$label,
										$item['field_maxchecks']
									),
									$label,
									$item['field_maxchecks']
								),
								'error'
							);
							$passed = false;
						}

					} else if( isset( $item['field_type'] ) && ( $item['field_type'] == 'number' || $item['field_type'] == 'name_price' ) ) {

						// 3.22.0
						$passed = pewc_validate_cart_item_data_number( $passed, $is_required, $is_visible, $product_id, $item, $label, $group, $_POST );

						/*if( ( empty( $_POST[$id] ) && ! is_numeric( $_POST[$id] ) ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;

						} else {

							// Does the number field need to be required in order to carry out value validation?
							$require_required = apply_filters( 'pewc_only_validate_number_field_value_if_field_required', false, $product_id, $item );

							if( ( ! $require_required || $is_required ) && $is_visible ) {

								// The field doesn't need to be required or it is required - so do value validation
								if( ! empty( $item['field_minval'] ) || ! empty( $item['field_maxval'] ) ) {

									$val = $_POST[$id];

									/** 
									 * Filter before Min and Max validations values
									 * @since 3.13.5
									 * @param array $item
									 * @param int $product_id
									 */
									/*$item = apply_filters( 'pewc_filter_minmax_validation_values', $item, $product_id );

									if( ! empty( $item['field_minval'] ) && $val < $item['field_minval'] ) {
										wc_add_notice( apply_filters( 'pewc_filter_minval_validation_notice', esc_html( $label ) . __( ': minimum value is ', 'pewc' ) . esc_html( $item['field_minval'] ) ), 'error' );
										$passed = false;
									} else if( ! empty( $item['field_maxval'] ) && $val > $item['field_maxval'] ) {
										wc_add_notice( apply_filters( 'pewc_filter_maxval_validation_notice', esc_html( $label ) . __( ': maximum value is ', 'pewc' ) . esc_html( $item['field_maxval'] ) ), 'error' );
										$passed = false;
									}

								}

							}

						}*/

					} else if( isset( $item['field_type'] ) && $item['field_type'] == 'color-picker' ) {

						if( empty( $_POST[$id] ) && $is_required ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							$passed = false;
						} else if ( ! empty( $_POST[$id] ) && ( strpos( $_POST[$id], '#' ) !== 0 || strlen( $_POST[$id] ) !== 7 || ! ctype_alnum( str_replace( '#', '', $_POST[$id] ) ) ) ) {
							wc_add_notice( apply_filters( 'pewc_filter_color_validation_notice', esc_html( $label ) . __( ': the value is not a valid hex code', 'pewc' ) ), 'error' );
							$passed = false;
						}

					} else if( isset( $item['field_type'] ) && $item['field_type'] == 'upload' ) {

						$files = array();
						$upload_passed = true; // used for this field type only

						if( ! empty( $_FILES ) ) {
							// We're using the standard image upload
							$files = $_FILES;

						} else if( ! empty( $_POST['pewc_file_data'][$item['field_id']] ) ) {
							// Using jQuery version

							// Make this an array like $_FILES
							$files = pewc_get_files_array( $_POST['pewc_file_data'][$item['field_id']], $id, $product_id );

						}

						if( ! empty( $files[$id]['size'] ) ) {
							// 3.20.1, let's use the indexes to keep track of problematic files to remove
							$f_index_remove = array();

							// this is not empty for AJAX uploads, but empty for standard uploads
							if ( ! empty( $files[$id]['file'] ) ) {
								// Is the upload path in the file page? Checking that someone hasn't changed the file path
								$file_valid = true;
								$upload_dir = pewc_get_upload_dir();
								$upload_subdir = $upload_dir . pewc_get_upload_subdirs();

								foreach( $files[$id]['file'] as $f_index=>$f ) {
									$uploaded_dir = substr( $files[$id]['file'][$f_index], 0, strrpos( $files[$id]['file'][$f_index], '/') );

									// Does the path match the expected path?
									$uploaded_path = trailingslashit( $uploaded_dir ) . $files[$id]['name'][$f_index];
									$expected_path = trailingslashit( $upload_subdir ) . $files[$id]['name'][$f_index];

									// Ensure the file doesn't have a php suffix
									$ext = substr( strrchr( $files[$id]['file'][$f_index], '.' ), 1 );

									if( strpos( $files[$id]['file'][$f_index], $upload_dir ) === false || $uploaded_path != $expected_path || $ext == 'php' ) {
										wc_add_notice(  __( 'The uploaded file has failed a security check. Please try uploading it again: ' . basename( $files[$id]['file'][$f_index] ) , 'pewc' ), 'error' );
										// 3.20.1, add some error logs to help debug the issue
										pewc_error_log('AOU: The uploaded file has failed a security check. Please try uploading it again. id:'.$id.', file:'.$files[$id]['file'][$f_index].', upload_dir:'.$upload_dir.', uploaded_path:'.$uploaded_path.', expected_path:'.$expected_path.', ext:'.$ext.', $files:'.print_r($files, true));
										//$files = array(); // commented out on 3.20.1
										if ( ! in_array( $f_index, $f_index_remove ) ) {
											// 3.20.1, let's save this index, to be used later
											$f_index_remove[] = $f_index;
										}
										$upload_passed = false;
										$passed = false;
									}
								}
							}

							if( ! empty( $files[$id]['size'] ) ) {

								foreach( $files[$id]['size'] as $key=>$size ) {

									if( $size > $max_mb ) {
										// File too big
										wc_add_notice( apply_filters( 'pewc_filter_file_size_validation_notice', esc_html( $files[$id]['name'][$key] ) . __( ': File size too large.', 'pewc' ) ), 'error' );
										$upload_passed = $passed = false;
										if ( ! in_array( $key, $f_index_remove ) ) {
											// 3.20.1, let's save this key, to be used later
											$f_index_remove[] = $key;
										}
									}

									if( $size == 0 && $is_required ) {
										// Required field
										wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required upload field.', 'pewc' ), $label, $item ), 'error' );
										$upload_passed = $passed = false;
										if ( ! in_array( $key, $f_index_remove ) ) {
											// 3.20.1, let's save this key, to be used later
											$f_index_remove[] = $key;
										}
									}

								}

							}

							$mime_types = pewc_get_permitted_mimes();

							// Check file type
							// moved here in 3.20.1
							if( ! empty( $files[$id]['name'] ) ) {

								foreach( $files[$id]['name'] as $key => $name ) {

									// Use wp_check_filetype for additional security
									$file_info = wp_check_filetype( basename( $name ), $mime_types );

									if( ! empty( $file_info['type'] ) ) {
										// File type is permitted
									} else {

										if( $is_required ) {
											wc_add_notice( apply_filters( 'pewc_file_not_valid_message', __( 'File not valid.', 'pewc' ) ), 'error' );
											$upload_passed = $passed = false;
											if ( ! in_array( $key, $f_index_remove ) ) {
												// 3.20.1, let's save this key, to be used later
												$f_index_remove[] = $key;
											}
										}

									}

								}

							}

						} else if( $is_required ) {

							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required upload field.', 'pewc' ), $label, $item ), 'error' );
							// Delete the uploaded session in case he added something before
							pewc_save_uploaded_files_to_session( '', $item['field_id'] );
							$upload_passed = $passed = false;
							continue; // 3.20.1, proceed to the next field

						}

						// commented out on 3.20.1, if a file failed validation earlier, allow other uploaded files to be saved and passed on the next page
						//if ( ! $upload_passed ) {
						//	continue; // no need to run the code below, go to next field
						//}

						// We have passed validation for files, save now. If empty $files is passed into pewc_save_uploaded_files_to_session, saved data is removed
						// 3.20.1, allow other files to be saved even if $upload_passed = false
						if( pewc_enable_ajax_upload() == 'yes' /*&& $upload_passed*/ ) {
							// AJAX upload, perhaps save $_POST['pewc_file_data'][$item['field_id']]
							$pewc_file_data = stripslashes( $_POST['pewc_file_data'][$item['field_id']] );

							if ( ! $upload_passed && ! empty( $f_index_remove ) ) {
								// we have some uploads that failed and need to be removed
								$pewc_file_data_arr = json_decode( $pewc_file_data, true );
								$tmp_file_arr = array();
								foreach ( $pewc_file_data_arr as $f_index => $fdata ) {
									if ( ! in_array( $f_index, $f_index_remove ) ) {
										// ok to save
										$tmp_file_arr[] = $fdata;
									}
								}
								if ( ! empty( $tmp_file_arr ) ) {
									// save this
									$pewc_file_data_arr = $tmp_file_arr;
									$pewc_file_data = json_encode( $pewc_file_data_arr );
								} else {
									// no more valid files left
									$pewc_file_data = ''; // empty string clears the session
								}
							}

							if ( isset($_POST[$id . '_extra_fields']) && is_array( $_POST[$id . '_extra_fields'] ) && ! empty( $pewc_file_data ) ) {
								// quantity is set for Advanced Uploads, save it as well
								$pfd_arr = json_decode( $pewc_file_data, true);
								if ( is_array($pfd_arr) && count($pfd_arr) > 0 ) {
									foreach ( $pfd_arr as $key => $value ) {
										// add quantity from Advanced Uploads
										$quantity = isset( $_POST[$id . '_extra_fields'][$key] ) ? $_POST[$id . '_extra_fields'][$key] : false;
										if ( false !== $quantity ) {
											$pfd_arr[$key]['quantity'] = $quantity;
										}
									}
									$pewc_file_data = json_encode($pfd_arr);
								}
							}

							// save the uploaded data into a WooCommerce session
							pewc_save_uploaded_files_to_session( $pewc_file_data, $item['field_id'] );
						}
						else {
							// standard
						}

					} else if( isset( $item['field_type'] ) && ( $item['field_type'] == 'radio' || $item['field_type'] == 'select' || $item['field_type'] == 'select-box' ) ) {

						// 3.22.0
						$passed = pewc_validate_cart_item_data_select( $passed, $is_required, $item, $label, $group, $_POST );

					} else if( isset( $item['field_type'] ) && ( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories' ) ) {

						// Validate minimum / maximum
						if( $item['products_quantities'] == 'independent' &&
						 	( $item['products_layout'] == 'column' || $item['products_layout'] == 'checkboxes' || $item['products_layout'] == 'checkboxes-list' ) &&
							( ! empty( $item['min_products'] ) || ! empty( $item['max_products'] ) ) ) {

							$min_products = ! empty( $item['min_products'] ) ? $item['min_products'] : 0;
							$max_products = ! empty( $item['max_products'] ) ? $item['max_products'] : '';

							// $item['child_products'] is empty here for product-categories, so populate
							// future: maybe add pewc_filter_item_start_list filter in this function as well?
							if ( $item['field_type'] == 'product-categories' && ! empty( $item['child_categories'] ) ) {
								$item['child_products'] = pewc_get_product_categories_addon_products( $item['field_id'], $item['child_categories'] );
							}

							// Total up the quantity of child products
							$child_products = ! empty( $item['child_products'] ) ? $item['child_products'] : array();
							$child_quantity = 0;
							foreach( $child_products as $key=>$child_product_id ) {
								if( ! empty( $_POST[$id . '_child_quantity_' . $child_product_id] ) ) {
									$child_quantity += $_POST[$id . '_child_quantity_' . $child_product_id];
								}
							}

							// Check if we've got too many or too few child products
							if( $min_products == $max_products && $child_quantity != $min_products && $is_visible ) {
								wc_add_notice(
									apply_filters(
										'pewc_filter_exact_children_validation_notice',
										sprintf(
											__( '%s requires you to choose %s products', 'pewc' ),
											esc_html( $label ),
											$min_products
										),
										esc_html( $label ),
										$min_products
									),
									'error'
								);
								$passed = false;

							} else if( $child_quantity < $min_products && $is_visible ) {

								wc_add_notice(
									apply_filters(
										'pewc_filter_min_children_validation_notice',
										sprintf(
											__( '%s requires you to choose a minimum of %s products', 'pewc' ),
											esc_html( $label ),
											$min_products
										),
										esc_html( $label ),
										$min_products
									),
									'error'
								);
								$passed = false;

							} else if( $max_products && $child_quantity > $max_products && $is_visible ) {

								wc_add_notice(
									apply_filters(
										'pewc_filter_max_children_validation_notice',
										sprintf(
											__( '%s requires you to choose a maximum of %s products', 'pewc' ),
											esc_html( $label ),
											$max_products
										),
										esc_html( $label ),
										$max_products
									),
									'error'
								);
								$passed = false;

							}

						}

						// If the products layout is select, the quantities type is independent and the field is required, the quantity field must be a minimum of 1
						if( $item['products_layout'] == 'select' && $item['products_quantities'] == 'independent' && ! empty( $item['field_required'] ) && empty( $_POST[$id . '_child_quantity'] ) ) {
							wc_add_notice( apply_filters( 'pewc_filter_independent_select_validation_notice', esc_html( $label ) . __( ' must have a quantity entered.', 'pewc' ) ), 'error' );
							$passed = false;
						}

						if( empty( $_POST[$id . '_child_product'] ) && $is_required && $item['products_layout'] != 'grid' ) {
							// Required field
							wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
							return false;
						}

						if( $item['products_layout'] == 'grid' ) {

							if( array_sum( $_POST[$id . '_grid_child_variation'] ) <= 0 && $is_required ) {
								// Required field
								wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
								return false;
							}

						}

						// Check for out of stock child products or if sold individually
						if( apply_filters( 'pewc_validate_child_products_stock', false, $item ) || pewc_validate_child_products_sold_individually() ) {

							$child_products = ! empty( $item['child_products'] ) ? $item['child_products'] : array();

							$child_quantity = 0;
							foreach( $child_products as $key=>$child_product_id ) {

								if ( ! isset( $_POST[$id . '_child_product'] ) ) {
									continue; // no child product selected, skip
								}

								if( ! is_array( $_POST[$id . '_child_product'] ) ) {
									$_POST[$id . '_child_product'] = array( $_POST[$id . '_child_product'] );
								}

								if( in_array( $child_product_id, $_POST[$id . '_child_product'] ) ) {

									$child_product = wc_get_product( $child_product_id );
									$products_qty_in_cart = WC()->cart->get_cart_item_quantities();

									if ( $child_product->is_sold_individually() && isset( $products_qty_in_cart[$child_product_id] ) && pewc_validate_child_products_sold_individually() ) {
										// 3.17.2, child product that is only sold individually is already in the cart, prevent from getting added
										wc_add_notice(
											apply_filters(
												'pewc_child_products_sold_individually_validation_notice',
												__( 'The product has not been added to the cart because one or more of its components is sold individually and already exists in the cart.', 'pewc' ),
												$item
											),
											'error'
										);
										$passed = false;
										break;
									}

									if ( ! apply_filters( 'pewc_validate_child_products_stock', false, $item ) ) continue; // checking of stock is disabled, so skip this process

									// Check the quantity
									if( $item['products_quantities'] == 'independent' ) {
										$quantity_in_cart = isset( $_POST[$id . '_child_quantity_' . $child_product_id] ) ? intval( $_POST[$id . '_child_quantity_' . $child_product_id] ) : 1;

										if ( 'yes' === pewc_multiply_independent_quantities_by_parent_quantity() ) {
											$quantity_in_cart = $quantity_in_cart * $quantity; // 3.17.2
										}
									} else if( $item['products_quantities'] == 'linked' ) {
										$quantity_in_cart = $quantity;
									} if( $item['products_quantities'] == 'one-only' ) {
										$quantity_in_cart = 1;
									}
									$current_quantity = $quantity_in_cart; // 3.17.2, current child product quantity, for this field only, to be saved later

									if( isset( $products_qty_in_cart[$child_product_id] ) ) {
										$quantity_in_cart += $products_qty_in_cart[$child_product_id];
									}

									if ( isset( $products_qty_to_be_added[$child_product_id] ) ) {
										$quantity_in_cart += $products_qty_to_be_added[$child_product_id]; // 3.17.2, maybe this child product is in 2 different fields (different parents)
									}

									if( ! $child_product->has_enough_stock( $quantity_in_cart ) ) {
										// Not enough stock
										wc_add_notice(
											apply_filters(
												'pewc_child_products_stock_validation_notice',
												__( 'The product has not been added to the cart because one or more of its components is out of stock.', 'pewc' ),
												$item
											),
											'error'
										);
										$passed = false;
										break 2; // 3.17.2, added '2' to break out of 2 foreach loops
									} else {
										// 3.17.2, keep track of child products across different fields and groups
										$products_qty_to_be_added[$child_product_id] = $current_quantity;
									}

								}

							}

						}

					}

					$passed = apply_filters( 'pewc_filter_validate_cart_item_status', $passed, $_POST, $item );

				}

			}

		}

	}

	return $passed;

}
add_filter( 'woocommerce_add_to_cart_validation', 'pewc_validate_cart_item_data', 10, 5 );

/**
 * Is this field required?
 * @param $group_req 	The requirement setting for the group
 * @param $field_req 	The requirement setting for the field
 * @param $id					Current item ID
 * @param $items			Array of all items in group
 * @return Boolean
 */
function pewc_is_field_required( $group_req, $field_req, $id, $items ) {
	if( ! $group_req && ! $field_req ) {
		// No requirements set
		return false;
	} else if( $group_req == 'all' && $field_req ) {
		return true;
	} else if( $group_req == 'depends' && $field_req ) { // Remove this option - conditionals will replace this
		// Field is not required if it's the first field in the group
		if( isset( $items[0]['id'] ) && $items[0]['id'] == $id ) {
			return false;
		} else {
			// Field is required if the first field in the group is not empty
			if( ! empty( $_POST[$items[0]['id']] ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Display custom fields for each cart item
 */
function pewc_get_item_data( $other_data, $cart_item ) {

	$groups = false;

	if ( ! empty( $cart_item['product_extras']['groups'] ) ) {

		$hidden_group_types = apply_filters( 'pewc_hidden_field_types_in_cart', array() );

		// 3.21.7
		$has_blocks = pewc_page_has_wc_blocks();

		// 3.22.0, build the array to include cloned groups if they exist
		$all_groups = pewc_rebuild_cart_item_data( $cart_item['product_extras'] );

		foreach( $all_groups as $groups ) {

			if( $groups ) {

				$group_id = $groups['id'];

				foreach( $groups as $item ) {

					if ( ! isset( $item['type'] ) ) {
						continue; // this might be the group ID so skip
					}

					if( in_array( $item['type'], $hidden_group_types ) ) {
						// Don't add this to the cart if it's a hidden field type
						continue;
					}

					// Don't display hidden fields. 3.13.7, field_visibility is added
					if( ! empty( $item['hidden'] ) || ( ! empty( $item['field_visibility'] ) && ! pewc_field_visible_in( 'cart', $item['field_visibility'], $item['field_id'], $group_id, $cart_item['product_id'] ) ) ) {
						continue;
					}

					// Added in 3.5.3 to allow us to link parent products with children in cart
					$display_product_meta = apply_filters( 'pewc_display_child_product_meta', false, $item );

					if( isset( $item['type'] ) ) {

						if( ( $item['type'] == 'products' || $item['type'] == 'product-categories' ) && ! $display_product_meta ) {
							continue;
						}

						$price = '';
						$hide_zero = get_option( 'pewc_hide_zero', 'no' );
						//$show_prices = apply_filters( 'pewc_show_field_prices_in_cart', true, $item ); // 3.26.18, commented out, use function instead
						$show_prices = pewc_show_field_prices_in_cart( $item );

						// Calculate price
						if( isset( $item['price'] ) ) {

							if( ( $hide_zero == 'yes' && $item['price'] == '0.00' ) || ! $show_prices ) {

								// If price is zero and hide_zero is set, hide the price
								$price = '';

							} else {

								/**
								 * Removed in 3.7.1 because tax was getting doubled
								 */
								// $product_id = $cart_item['data']->get_id();
								// $product = wc_get_product( $product_id );
								// $price = pewc_maybe_include_tax( $product, $item['price'] );

								// 3.9.5 do tax adjustments here as well so that add-on prices are displayed properly in cart
								$price = $item['price'];

								// version 2
								$product = $cart_item['data'];
								$price = pewc_maybe_include_tax( $product, $price, true ); // price for cart

								$price = ' ' . wc_price( $price );

							}

						}

						if( ! empty( $item['flat_rate'] ) ) {
							$price = '<span class="pewc-flat-rate-cart-label">(' . apply_filters( 'pewc_flat_rate_cost_text', __( 'Flat rate cost', 'pewc' ), $item ) . ')</span>';
						}

						$price = apply_filters( 'pewc_filter_cart_item_price', $price, $item );

						if( $item['type'] == 'upload' ) {

							if( ! empty( $item['files'] ) ) {

								$display = sprintf(
									'<div class="pewc-upload-thumb-wrapper">',
									$price
								);

								if( $show_prices ) {
									$display .= sprintf(
										'<span class="pewc-cart-item-price">%s</span>',
										$price
									);
								}

								foreach( $item['files'] as $index=>$file ) {

									if ( file_exists( $file['file'] ) && apply_filters( 'pewc_show_link_only_cart', false ) && ! $has_blocks ) {
										// 3.11.6, allow users to only display a link to the uploaded file, e.g. if the uploaded image is too large
										// 3.21.7, only add the links if using Blocks, because WC strips HTML tags in Cart Blocks, and what remains are the file names
										$display .= sprintf(
											'<br><span><a href="%s" target="_blank">%s</a></span>',
											$file['url'],
											$file['display']
										);
									} else if( file_exists( $file['file'] ) && is_array( getimagesize( $file['file'] ) ) || apply_filters( 'pewc_force_always_display_thumbs', false ) ) {
										// Add a thumb for image files
										$thumb = pewc_thumb_anti_cache( $file['url'] );
										$display .= sprintf(
											'<br><img src="%s">',
											esc_url( $thumb )
										);
									} else if( apply_filters( 'pewc_offload_media', false ) && ! empty( $file['url'] ) ) {

										// Since 3.26.9
										$image_mimes = pewc_get_image_mimes();
										if( ! empty( $file['type'] ) && in_array( $file['type'], $image_mimes ) ) {
											// Get the thumnbnail from remote media
											$remote_headers = get_headers( $file['url'] );
											$exists = stripos( $remote_headers[0], "200 OK" ) ? true : false;
											if( $exists ) {
												$display .= sprintf(
													'<br><span><img class="pewc-remote-thumb" src="%s" /></span>',
													$file['url']
												);
											}
										}
										
									} else if ( ! $has_blocks ) {
										// 3.21.7, only do this if not using Blocks, because HTML tags are stripped and the filenames remain
										$display .= sprintf(
											'<br><span>%s</span>',
											$file['display']
										);
									}

									if( ! empty( $file['quantity'] ) && ! $has_blocks ) {
										$display .= sprintf(
											'%s: %s',
											__( 'Quantity', 'pewc' ),
											$file['quantity']
										);
									}
									$display = apply_filters( 'pewc_get_item_data_after_file', $display, $file );

								}

								$display .= '</div>';

								$other_data[] = array(
									'name'    => sanitize_text_field( $item['label'] ),
									// 'value'   => sanitize_text_field( $item['display'] ),
									'display' => $display,
									'className' => 'pewc-upload-'.$item['field_id'], // used by Blocks
								);

							}

						} else if( $item['type'] == 'checkbox' ) {

							$value = '';
							if( $show_prices ) {
								$value = '<span class="pewc-price pewc-cart-item-price">' . sanitize_text_field( $price ). '</span>';
							}
							$other_data[] = array(
								'name'    => sanitize_text_field( $item['label'] ),
								'value'   => $value,
								'display' => '',
							);

						} else if( $item['type'] == 'checkbox_group' ) {

							$value = str_replace( ' | ', '<br>', $item['value'] );
							if( $show_prices ) {
								$value .= '<span class="pewc-price pewc-cart-item-price">' . sanitize_text_field( $price ). '</span>';
							}

							$other_data[] = array(
								'name'    => sanitize_text_field( $item['label'] ),
								'value'   => $value,
								'display' => '',
							);

						} else if( $item['type'] == 'name_price' ) {

							// 3.25.5, show multiplied price beside value
							if ( ! empty( $item['multiply'] ) ) {
								add_filter( 'pewc_name_price_field_display_in_cart', function( $value, $price, $item ){
									return $item['value'] . ' ' . $price;
								}, 11, 3 );
							}
							// 3.19.2, added filter to allow display of both value and price (maybe price has tax and the value entered doesn't)
							$value = apply_filters( 'pewc_name_price_field_display_in_cart', wc_price( $item['value'] ), $price, $item );
							$other_data[] = array(
								'name'    => sanitize_text_field( $item['label'] ),
								'value'   => sanitize_text_field( $value ),
								'display' => '',
							);

						} else if( $item['type'] == 'group_heading' ) {
							$other_data[] = array(
								'name'    => '<span class="pewc-cart-group-heading">' . sanitize_text_field( $item['label'] ) . '</span>',
								'value'   => '',
								'display' => '',
							);
						} else {

							//$show_field_prices_in_cart = pewc_show_field_prices_in_cart( $item ); // 3.26.18, commented out, use $show_prices instead
							$display = wp_kses_post( apply_filters( 'pewc_filter_item_value_in_cart', $item['value'], $item ) );

							if( $show_prices ) {
								$display .= '<span class="pewc-cart-item-price">' . $price . '</span>';
							}

							$other_data[] = array(
								'name'    => sanitize_text_field( $item['label'] ),
								'value'   => sanitize_text_field( $item['value'] ),
								'display' => $display
							);

						}
					}
				}
			}
		}
	}

	// Optionally show the original product price in the cart
	if( apply_filters( 'pewc_show_original_price_in_order', false ) && isset( $cart_item['product_extras']['original_price'] ) ) {

		$other_data[] = array(
			'name'    => apply_filters( 'pewc_original_price_text', __( 'Original price', 'pewc' ) ),
			// 'value'   => sanitize_text_field( $item['value'] ),
			'display' => '<span class="pewc-cart-item-price">' . wc_price( $cart_item['product_extras']['original_price'] ) . '</span>'
		);

	}

	return apply_filters( 'pewc_end_get_item_data', $other_data, $cart_item, $groups );

}
add_filter( 'woocommerce_get_item_data', 'pewc_get_item_data', 10, 2 );

/**
 * Add the cart item key as a param to the product permalink
 * @since	3.4.0
 * @version	3.26.11
 */
function pewc_cart_item_permalink( $permalink, $cart_item, $cart_item_key ) {

	// Only filter if editing is allowed
	// 3.26.11, only add pewc_key if this product has add-on fields
	if( pewc_user_can_edit_products() && ! empty( $cart_item['product_extras']['groups'] ) ) {

		$permalink = add_query_arg(
			'pewc_key',
			$cart_item_key,
			$permalink
		);

	}

	return $permalink;

}
add_filter( 'woocommerce_cart_item_permalink', 'pewc_cart_item_permalink', 10, 3 );

/**
 * Add an 'Edit' button to cart items
 * @since 3.4.0
 */
function pewc_after_cart_item_name( $cart_item, $cart_item_key ) {

	if( pewc_user_can_edit_products() && ! empty( $cart_item['product_extras']['groups'] ) && ! isset( $cart_item['product_extras']['products']['child_field'] ) ) {

		$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
		$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

		if( pewc_cart_item_has_extra_fields( $cart_item ) && $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );

			echo apply_filters(
				'pewc_after_cart_item_edit_options',
				sprintf(
					'&nbsp;<small>[<a href="%s">%s</a>]</small>',
					esc_url( $product_permalink ),
					apply_filters( 'pewc_after_cart_item_edit_options_text', __( 'Edit options', 'pewc' ), $product_id )
				)
			);

		}

	}

}
add_action( 'woocommerce_after_cart_item_name', 'pewc_after_cart_item_name', 100, 2 );

/**
 * Indent child product names in the cart
 * @since 3.9.2
 */
function pewc_cart_item_name( $product_name, $cart_item, $cart_item_key ) {
	if( pewc_indent_child_product() == 'yes' && pewc_is_order_item_child_product( $cart_item ) ) {
		$product_name = apply_filters( 'pewc_indent_markup', '<span style="padding-left: 15px"></span>' ) . $product_name;
	}
	return $product_name;
}
add_filter( 'woocommerce_cart_item_name', 'pewc_cart_item_name', 10, 3 );

/**
 * Filter the added to cart notice if we are editing a product in the cart
 * @since 3.4.0
 */
function pewc_add_to_cart_message_html( $message, $products, $show_qty ) {

	if( pewc_user_can_edit_products() ) {

		$count = 0;

		foreach ( $products as $product_id => $qty ) {
			/* translators: %s: product name */
			$titles[] = apply_filters( 'woocommerce_add_to_cart_qty_html', ( $qty > 1 ? absint( $qty ) . ' &times; ' : '' ), $product_id ) . apply_filters( 'woocommerce_add_to_cart_item_name_in_quotes', sprintf( _x( '&ldquo;%s&rdquo;', 'Item name in quotes', 'woocommerce' ), strip_tags( get_the_title( $product_id ) ) ), $product_id );
			$count   += $qty;
		}

		$titles = array_filter( $titles );
		$added_text = sprintf( _n( '%s has been updated.', '%s have been updated.', $count, 'pewc' ), wc_format_list_of_items( $titles ) );
		$message = sprintf( '%s', esc_html( $added_text ) );

		// Automatically return the user to the cart
		add_filter( 'woocommerce_add_to_cart_redirect', 'pewc_add_to_cart_redirect', 10, 2 );

	}

	return $message;

}

/**
 * Filter the redirect URL to send the user back to the cart after updating a product
 * @since 3.4.0
 */
function pewc_add_to_cart_redirect( $url, $adding_to_cart ) {
	return wc_get_cart_url();
}

/**
 * Remove the pewc_key param from the URL if the item has been removed from the cart
 * @since 3.4.0
 */
function pewc_check_cart_key() {

	$cart_key = ! empty( $_GET['pewc_key'] ) ? $_GET['pewc_key'] : false;

	// Check this cart key exists
	$cart = is_object( WC()->cart ) ? WC()->cart->get_cart_contents() : false;
	if( $cart_key && ! isset( $cart[$cart_key] ) ) {
		$url = esc_url( remove_query_arg( 'pewc_key' ) );
		wp_redirect( $url );
		die;
	}

}
add_action( 'template_redirect', 'pewc_check_cart_key' );

/**
 * Filter the add to cart text if we are editing a product from the cart
 * @since 3.4.0
 */
function pewc_product_single_add_to_cart_text( $text, $product ) {

	$cart_key = ! empty( $_GET['pewc_key'] ) ? $_GET['pewc_key'] : false;

	if( ! $cart_key ) {
    return $text;
  }

	// Check this cart key still exists
	$cart = WC()->cart->get_cart_contents();
	$cart_item = isset( $cart[$cart_key] ) ? $cart[$cart_key] : false;

	if( $cart_key && $cart_item && pewc_user_can_edit_products() && pewc_cart_item_has_extra_fields( $cart_item ) ) {
		$text = __( 'Update product', 'pewc' );
	}

	return $text;

}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'pewc_product_single_add_to_cart_text', 10, 2 );

/**
 * Get whether to show the field price in the cart
 * @since 3.9.0
 */
function pewc_show_field_prices_in_cart( $field=false ) {

	$display = true;
	$hide_field_prices_types = array( 'radio', 'select', 'select-box' ); // 3.26.18, these fields only have one option price, so also displaying field price could show double prices

	if(
		( isset( $field['price_visibility'] ) && $field['price_visibility'] == 'hidden' ) || 
		( 'yes' === get_option( 'pewc_hide_field_prices', 'no' ) && ! empty( $field['type'] ) && in_array( $field['type'], $hide_field_prices_types ) )
	) {
		$display = false;
	}
	return apply_filters( 'pewc_show_field_prices_in_cart', $display, $field );

}
/**
 * Get whether to show the option prices in the cart
 * @since 3.9.0
 */
function pewc_show_option_prices_in_cart( $item ) {

	$display = true;
	if( isset( $item['option_price_visibility'] ) && $item['option_price_visibility'] == 'hidden' ) {
		$display = false;
	}

	$display = apply_filters( 'pewc_show_option_prices_in_cart', $display, $item );
	return $display;

}

/**
 * Get whether to show the group titles in the cart
 * @since 3.8.10
 */
function pewc_show_group_titles_in_cart( $group_id ) {
	$show = get_option( 'pewc_cart_group_titles', 'no' );
	return apply_filters( 'pewc_display_group_titles_cart', $show, $group_id );
}

/**
 * Remove group title if group is empty
 * @since 3.21.5
 */
function pewc_remove_group_title_if_empty( $cart_item_data, $group_id ) {
	if ( isset( $cart_item_data['product_extras']['groups'] ) ) {
		foreach ( $cart_item_data['product_extras']['groups'] as $group_id => $item ) {
			if ( pewc_show_group_titles_in_cart( $group_id ) == 'yes' ) {
				if ( count( $item ) === 1 && array_key_exists( $group_id, $item ) && $item[$group_id]['type'] == 'group_heading' ) {
					unset( $cart_item_data['product_extras']['groups'][$group_id] );
				}
			}
		}
	}

	return $cart_item_data;
}
add_filter( 'pewc_after_add_cart_item_data', 'pewc_remove_group_title_if_empty', 10, 2 );

/**
 * Filter the thumbnail image if we have a composite version
 * @since 3.17.0
 */
function pewc_cart_item_thumbnail( $image, $cart_item, $cart_item_key ) {

	if( ! empty( $cart_item['composite_image'] ) ) {
		
		$image = sprintf(
			'<img src="%s" alt="%s">',
			esc_url( $cart_item['composite_image'] ),
			$cart_item['data']->get_name()
		);
	}

	return $image;

}
add_filter( 'woocommerce_cart_item_thumbnail', 'pewc_cart_item_thumbnail', 10, 3 );

/**
 * Function for validating text-based add-on field values
 * @since 3.22.0
 */
function pewc_validate_cart_item_data_text( $passed, $is_required, $item, $label, $group, $posted ) {

	$id = $item['id'];
	$is_repeatable = $item['group_is_repeatable'];
	$group_id = $item['group_id'];
	$group_title = $group['group_title'];
	if ( $is_repeatable ) {
		$labeling_type = $group['labeling_type'];
		$label_format = $group['label_format'];
		$quantity = (int) $posted['quantity'];
	}

	// 3.26.5
	$repeatable_index = ( $is_repeatable && ! empty( $group['clone_count'] ) ) ? $group['clone_count'] - 1 : 0;

	// let's all put the values in an array whether they are repeatable or not so that we can reuse the validation process below
	// 3.25.4, new version, a customer reported Undefined array key error because $posted[$id] is not set, can't replicate yet but this new code below can help avoid it
	$posted_values = array();
	if ( ! empty( $posted[$id] ) ) {
		$posted_values = ( is_array( $posted[$id] ) ) ? $posted[$id] : array( $posted[$id] );
	}
	// 3.25.3 and earlier
	//if ( $is_repeatable && is_array( $posted[$id] ) ) {
	//	$posted_values = $posted[$id]; // this is an array
	//} else {
	//	$posted_values = array( $posted[$id] );
	//}

	$original_label = $label;

	// 3.25.4
	if ( empty( $posted_values ) && $is_required ) {
		wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
		return false;
	}

	foreach ( $posted_values as $index => $value ) {
		if ( $is_repeatable ) {
			$clone_count = $index + 1;
			$label = pewc_repeatable_get_label( $label_format, $group_title, $original_label, $clone_count );

			if ( ! empty( $group['repeat_by_quantity'] ) && $clone_count > 1 && $clone_count > $quantity ) {
				// don't validate other posted values because they might be hidden
				continue;
			}

			// 3.26.5
			if ( $index != $repeatable_index ) {
				// we are now only checking the value of the current cloned group, if this value is for another cloned group, skip
				continue;
			}
		}

		if( empty( $value ) && $is_required ) {
			// Required field
			wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
			$passed = false;
		} else if ( ! empty( $value ) ) {
			// Character length
			if( ! empty( $item['field_minchars'] ) || ! empty( $item['field_maxchars'] ) ) {
				$length = isset( $value ) ? mb_strlen( str_replace( ' ', '', $value ) ) : 0;
				if( ! empty( $item['field_minchars'] ) && $length < $item['field_minchars'] ) {
					wc_add_notice( apply_filters( 'pewc_filter_minchars_validation_notice', esc_html( $label ) . __( ': minimum number of characters: ', 'pewc' ) . esc_html( $item['field_minchars'] ), $label, $item ), 'error' );
					$passed = false;
				} else if( ! empty( $item['field_maxchars'] ) && $length > $item['field_maxchars'] ) {
					wc_add_notice( apply_filters( 'pewc_filter_maxchars_validation_notice', esc_html( $label ) . __( ': maximum number of characters: ', 'pewc' ) . esc_html( $item['field_maxchars'] ), $label, $item ), 'error' );
					$passed = false;
				}
			}
		}
	}
	return $passed;

}

/**
 * Function for validating number-based add-on field values
 * @since 3.22.0
 */
function pewc_validate_cart_item_data_number( $passed, $is_required, $is_visible, $product_id, $item, $label, $group, $posted ) {

	$id = $item['id'];
	$is_repeatable = $item['group_is_repeatable'];
	$group_id = $item['group_id'];
	$group_title = $group['group_title'];
	if ( $is_repeatable ) {
		$labeling_type = $group['labeling_type'];
		$label_format = $group['label_format'];
		$quantity = (int) $posted['quantity'];
	}

	// 3.26.5
	$repeatable_index = ( $is_repeatable && ! empty( $group['clone_count'] ) ) ? $group['clone_count'] - 1 : 0;

	// let's all put the values in an array whether they are repeatable or not so that we can reuse the validation process below
	if ( $is_repeatable && is_array( $posted[$id] ) ) {
		$posted_values = $posted[$id]; // this is an array
	} else {
		$posted_values = array( $posted[$id] );
	}

	$original_label = $label;

	foreach ( $posted_values as $index => $value ) {
		if ( $is_repeatable ) {
			$clone_count = $index + 1;
			$label = pewc_repeatable_get_label( $label_format, $group_title, $original_label, $clone_count );

			if ( ! empty( $group['repeat_by_quantity'] ) && $clone_count > 1 && $clone_count > $quantity ) {
				// don't validate other posted values because they might be hidden
				continue;
			}

			// 3.26.5
			if ( $index != $repeatable_index ) {
				// we are now only checking the value of the current cloned group, if this value is for another cloned group, skip
				continue;
			}
		}

		if( ( empty( $value ) && ! is_numeric( $value ) ) && $is_required ) {
			// Required field
			wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
			$passed = false;

		} else {

			// Does the number field need to be required in order to carry out value validation?
			$require_required = apply_filters( 'pewc_only_validate_number_field_value_if_field_required', false, $product_id, $item );

			if( ( ! $require_required || $is_required ) && $is_visible ) {

				// The field doesn't need to be required or it is required - so do value validation
				if( ! empty( $item['field_minval'] ) || ! empty( $item['field_maxval'] ) ) {

					$val = $value;

					/** 
					 * Filter before Min and Max validations values
					 * @since 3.13.5
					 * @param array $item
					 * @param int $product_id
					 */
					$item = apply_filters( 'pewc_filter_minmax_validation_values', $item, $product_id );

					if( ! empty( $item['field_minval'] ) && $val < $item['field_minval'] ) {
						wc_add_notice( apply_filters( 'pewc_filter_minval_validation_notice', esc_html( $label ) . __( ': minimum value is ', 'pewc' ) . esc_html( $item['field_minval'] ) ), 'error' );
						$passed = false;
					} else if( ! empty( $item['field_maxval'] ) && $val > $item['field_maxval'] ) {
						wc_add_notice( apply_filters( 'pewc_filter_maxval_validation_notice', esc_html( $label ) . __( ': maximum value is ', 'pewc' ) . esc_html( $item['field_maxval'] ) ), 'error' );
						$passed = false;
					}

				}

			}

		}
	}

	return $passed;

}

/**
 * Function for validating radio and select add-on field values
 * @since	3.22.0
 * @version	3.24.8
 */
function pewc_validate_cart_item_data_select( $passed, $is_required, $item, $label, $group, $posted ) {

	$id = $item['id'];
	$is_repeatable = $item['group_is_repeatable'];
	$group_id = $item['group_id'];
	$group_title = $group['group_title'];
	if ( $is_repeatable ) {
		$labeling_type = $group['labeling_type'];
		$label_format = $group['label_format'];
		$quantity = (int) $posted['quantity'];
	}

	// 3.26.5
	$repeatable_index = ( $is_repeatable && ! empty( $group['clone_count'] ) ) ? $group['clone_count'] - 1 : 0;

	// let's all put the values in an array whether they are repeatable or not so that we can reuse the validation process below
	$posted_values = array();
	if ( ! empty( $posted[$id] ) ) {
		// radio groups pass $posted[$id] as an array; select and select-box pass single values
		$posted_values = ( is_array( $posted[$id] ) ) ? $posted[$id] : array( $posted[$id] );
	}

	$original_label = $label;

	if ( ! empty( $posted_values ) ) {
		foreach ( $posted_values as $index => $value ) {
			if ( $is_repeatable ) {
				$clone_count = $index + 1;
				$label = pewc_repeatable_get_label( $label_format, $group_title, $original_label, $clone_count );

				if ( ! empty( $group['repeat_by_quantity'] ) && $clone_count > 1 && $clone_count > $quantity ) {
					// don't validate other posted values because they might be hidden
					continue;
				}

				// 3.26.5
				if ( $index != $repeatable_index ) {
					// we are now only checking the value of the current cloned group, if this value is for another cloned group, skip
					continue;
				}
			}

			if( empty( $value ) && $is_required ) {
				// Required field
				wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
				$passed = false;
			}
		}
	} else if ( $is_required ) {
		if ( $is_repeatable ) {
			// 3.26.5, this could be a cloned field, update label
			$clone_count = ! empty( $group['clone_count'] ) ? $group['clone_count'] : 1;
			$label = pewc_repeatable_get_label( $label_format, $group_title, $original_label, $clone_count );
		}
		// 3.24.8, checks radio, select, and select-box
		wc_add_notice( apply_filters( 'pewc_filter_validation_notice', esc_html( $label ) . __( ' is a required field.', 'pewc' ), $label, $item ), 'error' );
		$passed = false;
	}

	return $passed;

}

/**
 * Prevent thumb of uploaded image from getting cached on the cart page, in case the image was edited with AU
 * @since 3.26.11
 */
function pewc_thumb_anti_cache( $thumb ) {

	if ( pewc_user_can_edit_products() && function_exists( 'rand' ) ) {
		$thumb .= '?' . rand();
	}
	return $thumb;

}
