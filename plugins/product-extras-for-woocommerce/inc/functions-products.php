<?php
/**
 * Functions for add-on products
 * @since 2.2.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add an ID field to connect parent products with their child products
 * @since 2.2.0
 */
function pewc_add_product_hash_field( $args=array() ) {
	$parent_product_hash = uniqid( 'pewc_' ); ?>
	<input type="hidden" name="pewc_product_hash" value="<?php echo $parent_product_hash; ?>">
<?php }
add_action( 'pewc_start_groups', 'pewc_add_product_hash_field' );

/**
 * Add the child product when we add the parent product
 */
function pewc_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

	// If the product being added has a child product set, add that child product
	if( ! empty( $_POST ) ) {

		$child_product_ids = array();
		$product_extra_groups = pewc_get_extra_fields( $product_id );

		if( empty( $cart_item_data['product_extras']['child_fields'] ) ) {
			return;
		}

		foreach( $_POST as $key=>$value ) {

			if( strpos( $key, '_child_product' ) !== false ) {

				$field_id = str_replace( '_child_product', '', $key );

				// Is the child field visible?
				// $is_visible = pewc_get_conditional_field_visibility( $field_id, $item, $group['items'], $product_id, $_POST, $variation_id, $cart_item_data, $quantity );

				if( ! $value || ! in_array( $field_id, $cart_item_data['product_extras']['child_fields'] ) ) {

					// There's no data here, so no child products being added
					continue;

				} else if( is_array( $value ) ) {

					// If $value is an array, we're using checkboxes so add multiple products
					foreach( $value as $value_id ) {

						// Add independent quantity if set
						$child_quantity = '';
						if( ! empty( $_POST[$field_id . '_child_quantity_' . $value_id] ) ) {
							// Get the quantity for the child product in independent checkboxes
							$child_quantity = intval( $_POST[$field_id . '_child_quantity_' . $value_id] );
						} else if( ! empty( $_POST[$field_id . '_child_quantity'] ) ) {
							// Get the quantity for the child product in linked checkboxes
							$child_quantity = intval( $_POST[$field_id . '_child_quantity'] );
						}

						// If we're adding a variable product, then get the variation ID
						if( ! empty( $_POST['pewc_child_variants_' . $field_id . '_' . $value_id] ) ) {
							// Add the variant, not the variable product
							$value_id = $_POST['pewc_child_variants_' . $field_id . '_' . $value_id];
						}

						$child_product_ids[] = array(
							'child_product_id'	=> $value_id,
							'field_id' 				=> $field_id,
							'quantities'			=> $_POST[$field_id . '_quantities'],
							'allow_none'			=> $_POST[$field_id . '_allow_none'],
							'child_quantity'		=> $child_quantity,
							'child_discount'		=> $_POST[$field_id . '_child_discount'],
							'discount_type'			=> $_POST[$field_id . '_discount_type'],
							'force_quantity'		=> isset( $_POST[$field_id . '_force_quantity'] ) ? $_POST[$field_id . '_force_quantity'] : false,
						);

					}

				} else {

					// Not an array, so just a single child product
					$child_quantity = 1;

					if( empty( $_POST[$field_id . '_child_quantity'] ) ) {
						$child_quantity = 0;
					} else if( ! empty( $_POST[$field_id . '_child_quantity'] ) ) {
						$child_quantity = intval( $_POST[$field_id . '_child_quantity'] );
					} else if( ! empty( $_POST[$field_id . '_quantities'] ) && $_POST[$field_id . '_quantities'] == 'linked' ) {
						// Get the quantity for the child product in linked checkboxes
						$child_quantity = $quantity;
					}

					// If we're adding a variable product, then get the variation ID
					if( ! empty( $_POST['pewc_child_variants_' . $field_id . '_' . $value] ) ) {
						// Add the variant, not the variable product
						$value_id = $_POST['pewc_child_variants_' . $field_id . '_' . $value];
					}

					// This checks that the layout isn't swatches
					// To avoid ending up with the parent product and child product in the cart
					if( empty( $_POST[$field_id . '_child_variation']  ) ) {

						$child_product_ids[] = array(
							'child_product_id'	=> $value,
							'field_id' 					=> $field_id,
							'quantities'				=> $_POST[$field_id . '_quantities'],
							'allow_none'				=> $_POST[$field_id . '_allow_none'],
							'child_quantity'		=> $child_quantity,
							'child_discount'		=> $_POST[$field_id . '_child_discount'],
							'discount_type'			=> $_POST[$field_id . '_discount_type']
						);

					}

				}

			} else if( strpos( $key, '_grid_child_variation' ) !== false ) {

				// Grid field
				$field_id = str_replace( '_grid_child_variation', '', $key );

				// Iterate through each child product and identify the selected variation
				foreach( $value as $variation_id=>$child_quantity ) {

					if( $child_quantity ) {
						$child_product_ids[] = array(
							'child_product_id'	=> $variation_id,
							'field_id' 					=> $field_id,
							'quantities'				=> $_POST[$field_id . '_quantities'],
							'allow_none'				=> $_POST[$field_id . '_allow_none'],
							'child_quantity'		=> $child_quantity,
							'child_discount'		=> $_POST[$field_id . '_child_discount'],
							'discount_type'			=> $_POST[$field_id . '_discount_type']
						);
					}

				}

			} else if( strpos( $key, '_child_variation' ) !== false ) {

				// Swatches fields

				$field_id = str_replace( '_child_variation', '', $key );
				$selected_products = ! empty( $_POST[ $field_id . '_parent_product' ] ) && is_array( $_POST[ $field_id . '_parent_product' ] ) ? $_POST[ $field_id . '_parent_product' ] : array();

				// Iterate through each child product and identify the selected variation
				foreach( $value as $parent_id=>$variation_id ) {
					// This should be an array of variation IDs

					$child_quantity = 0;
					if( ! empty( $_POST[$field_id . '_child_quantity_' . $parent_id] ) ) {
						// Get the quantity for the child product in independent checkboxes
						$child_quantity = intval( $_POST[$field_id . '_child_quantity_' . $parent_id] );
					} else if( ! empty( $_POST[$field_id . '_child_quantity'] ) ) {
						// Get the quantity for the child product in linked checkboxes
						$child_quantity = intval( $_POST[$field_id . '_child_quantity'] );
					} else if( ! empty( $_POST[$field_id . '_quantities'] ) && ! empty( $selected_products ) && in_array( $parent_id, $selected_products ) ) {
						// Get the quantity for the child product in linked checkboxes
						if ( $_POST[$field_id . '_quantities'] == 'linked' ) {
							$child_quantity = $quantity;
						} else if ( $_POST[$field_id . '_quantities'] == 'one-only' ) {
							$child_quantity = 1;
						}
					}

					// 3.21.4
					if ( $child_quantity === 0 ) {
						// ignore this
						continue;
					}

					// If we're adding a variable product, then get the variation ID
					// if( ! empty( $_POST['pewc_child_variants_' . $value_id] ) ) {
					// 	// Add the variant, not the variable product
					// 	$value_id = $_POST['pewc_child_variants_' . $value_id];
					// }

					$child_product_ids[] = array(
						'child_product_id'	=> $variation_id,
						'field_id' 				=> $field_id,
						'quantities'			=> $_POST[$field_id . '_quantities'],
						'allow_none'			=> $_POST[$field_id . '_allow_none'],
						'child_quantity'		=> $child_quantity,
						'child_discount'		=> $_POST[$field_id . '_child_discount'],
						'discount_type'			=> $_POST[$field_id . '_discount_type']
					);

				}

				// Remove any parent products
				if( $child_product_ids ) {
					$tmp = array();
					foreach( $child_product_ids as $cp ) {
						// 3.21.4
						if ( $cp['field_id'] === $field_id ) {
							// only check for parent products from this field
							$cprod = wc_get_product( $cp['child_product_id'] );
							if ( 'variable' === $cprod->get_type() ) {
								// this is a parent variable product, skip
								continue;
							}
						}
						$tmp[] = $cp; // save
					}
					$child_product_ids = $tmp;
				}

			}

		}

		$parent_product_hash = isset( $_POST['pewc_product_hash'] ) ? $_POST['pewc_product_hash'] : '';

		pewc_add_on_product( $child_product_ids, $quantity, $product_id, $parent_product_hash, $cart_item_data );

	}

}
add_action( 'woocommerce_add_to_cart', 'pewc_add_to_cart', 10, 6 );

/**
 * Add a child product to the cart
 * @since 2.2.0
 */
function pewc_add_on_product( $child_product_ids, $original_quantity, $product_id, $parent_product_hash, $cart_item_data ) {

	if( ! pewc_is_pro() ) {
		return false;
	}

	do_action( 'pewc_add_on_product' );
	// Only add child products once
	$did = did_action( 'pewc_add_on_product' );
	if( $did > 1 ) {
		return;
	}

	// Add each child product to the cart
	foreach( $child_product_ids as $child_product_values ) {

		$child_product_id = $child_product_values['child_product_id'];

		if( ! empty( $cart_item_data['product_extras']['products']['field_id'] ) ) {

			// Only add visible fields to the cart
			// $field_id = $cart_item_data['product_extras']['products']['field_id'];

			// Changed in 3.4.0 to ensure child products are mapped to fields correctly
			$field_id = $child_product_values['field_id'];

			$cart_item['product_extras']['products']['field_id'] = $field_id;
			$cart_item['product_extras']['products']['parent_field_id'] = $parent_product_hash;
			$cart_item['product_extras']['products']['parent_product_id'] = $product_id;
			$cart_item['product_extras']['products']['child_field'] = 1;
			$cart_item['product_extras']['products'][$field_id . '_child_field'] = $field_id;

			// Check the quantity
			if( $child_product_values['quantities'] == 'one-only' ) {
				$quantity = 1;
			} else if( $child_product_values['quantities'] == 'linked' ) {
				$quantity = $original_quantity;
			} else if( $child_product_values['quantities'] == 'independent' || ! empty( $child_product_values['child_quantity'] ) ) {
				$quantity = $child_product_values['child_quantity'];
				// Multiply by parent quantity if enabled
				if( pewc_multiply_independent_quantities_by_parent_quantity() == 'yes' && ! empty( $quantity ) ) {
					$parent_quantity = isset( $_POST['quantity'] ) ? $_POST['quantity'] : 1;
					$quantity = $quantity * $parent_quantity;
				}
			}

			$cart_item['product_extras']['products']['products_quantities'] = $child_product_values['quantities'];
			$cart_item['product_extras']['products']['allow_none'] = $child_product_values['allow_none'];
			$cart_item['product_extras']['products']['force_quantity'] = ! empty( $child_product_values['force_quantity'] ) ? $child_product_values['force_quantity'] : '';

			$child_product = wc_get_product( $child_product_id );
			if( ! is_object( $child_product ) ) {
				//return; // 3.19.2, causes issues if the rest of child products are valid
				continue;
			}
			$child_price = $child_product->get_price();

			// We are setting this because sometimes a child product does not have add-on fields, so original_price is not added when "pewc_add_cart_item_data" is triggered. If original_price is missing, this gets skipped in F&D's "wcfad_before_calculate_totals" function
			$cart_item['product_extras']['original_price'] = $child_price;

			// Discount the child price?
			if( ! empty( $child_product_values['child_discount'] ) && ! empty( $child_product_values['discount_type'] ) ) {
				$child_discount_amount = $child_product_values['child_discount'];
				if ( 'fixed' === $child_product_values['discount_type'] ) {
					// 3.19.2, ensure that the fixed discounts are consistent in sites with exc/inc/inc and other tax settings 
					if ( ! wc_prices_include_tax() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
						// remove tax from $child_discount_amount
						$child_discount_amount = pewc_get_price_without_tax( $child_discount_amount, $child_product );
					} else if ( wc_prices_include_tax() && 'excl' === get_option( 'woocommerce_tax_display_shop' ) ) {
						// add tax to $child_discount_amount
						$tmp_cart_item = array(
							'data' => $child_product
						);
						$child_tax_rate = pewc_get_tax_rate( $tmp_cart_item );
						$child_discount_amount = $child_discount_amount * $child_tax_rate;
					}
				}
				$child_price = pewc_get_discounted_child_price( $child_price, $child_discount_amount, $child_product_values['discount_type'] );
			}

			$cart_item['product_extras']['price_with_extras'] = $child_price;
			// Ensure that any discounted child product price gets carried through to the cart
			$cart_item['product_extras']['price_with_extras_discounted'] = $child_price;

			if( apply_filters( 'pewc_apply_random_hash_child_product', false ) ) {
				// We can use this to force each child product to be a separate line item
				$cart_item['product_extras']['random_hash'] = md5( rand() );
			}

			if( apply_filters( 'pewc_multiply_child_product_quantities', false, $cart_item ) ) {
				$quantity = $quantity * $original_quantity;
			}

			$cart_item['product_extras'] = apply_filters( 'pewc_cart_item_extras_child_product', $cart_item['product_extras'], $cart_item_data, $child_product_id );

			if( apply_filters( 'pewc_add_child_product_to_cart', true, $cart_item['product_extras'], $cart_item_data, $child_product_id ) ) {
				WC()->cart->add_to_cart( $child_product_id, $quantity, 0, array(), $cart_item );	
			}

		}

	}

	// 3.13.0
	do_action( 'pewc_after_pewc_add_on_product', $child_product_ids, $original_quantity, $product_id, $parent_product_hash, $cart_item_data );

}

/**
 * Filter row classes in the cart for child and parent products
 * @since 2.2.0
 */
function pewc_cart_item_class( $class, $cart_item, $cart_item_key ) {
	if( ! empty( $cart_item['product_extras']['products']['child_products'] ) ) {
		// This is a parent product
		$parent_id = $cart_item['product_extras']['products']['pewc_parent_product'];
		$class .= ' pewc-parent-product pewc-parent-id-' . $parent_id;

	} else if( ! empty( $cart_item['product_extras']['products']['child_field'] ) ) {
		$class .= ' pewc-child-product';
	}
	if( ! empty( $cart_item['product_extras']['products']['force_quantity'] ) ) {
		// Check if this field has force_quantity enabled
		$class .= ' force-quantity';
	}
	if( ! empty( $cart_item['product_extras']['products']['products_quantities'] ) ) {
		$class .= ' ' . sanitize_title_with_dashes( $cart_item['product_extras']['products']['products_quantities'] );
	}
	if( ! empty( $cart_item['product_extras']['products']['parent_field_id'] ) ) {
		$class .= ' ' . $cart_item['product_extras']['products']['parent_field_id'];
	}
	return $class;
}
add_filter( 'woocommerce_cart_item_class', 'pewc_cart_item_class', 10, 3 );

/**
 * Re-order cart items so that parent products sit above child products
 * @since 2.2.0
 */
function pewc_cart_loaded_from_session() {
	if( WC()->cart->get_cart_contents_count() == 0 ) {
		// Empty cart so do nothing
		return;
	}
	$cart = WC()->cart->get_cart();
	$new_order = array();
	$grouped_items = array();
	foreach( $cart as $key=>$item ) {
		if( ! isset( $item['product_extras']['products'] ) ) {
			// Not a linked product
			$new_order[$key] = $item;
		} else {
			// Arrange linked products into groups
			$parent_field_id = isset( $item['product_extras']['products']['parent_field_id'] ) ? $item['product_extras']['products']['parent_field_id'] : false;
			if( ! isset( $grouped_items[$parent_field_id] ) ) {
				// Create a new array for this set of linked products
				$grouped_items[$parent_field_id] = array();
			}
			if( isset( $item['product_extras']['products']['child_products'] ) ) {
				// This is the parent product, so push to the end
				$grouped_items[$parent_field_id][] = $key;
			} else {
				// This is a child product, so prepend to start of list
				array_unshift( $grouped_items[$parent_field_id], $key );
			}
		}
	}
	// If we have linked products, then re-order the cart
	if( ! empty( $grouped_items ) ) {
		foreach( $grouped_items as $unique_key=>$grouped_item ) {
			foreach( $grouped_item as $cart_key ) {
				// Add each cart key, starting with child products, finishing with parent product
				$new_order[$cart_key] = $cart[$cart_key];
			}
		}
		// Reverse the order so that parent items are first
		WC()->cart->cart_contents = array_reverse( $new_order );
	} else {
		// Just display the original cart order
		WC()->cart->cart_contents = $cart;
	}
}
add_action( 'woocommerce_cart_loaded_from_session', 'pewc_cart_loaded_from_session' );

/**
 * Update any link child product quantities
 * @since 2.2.0
 */
function pewc_after_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity ) {
	$cart = WC()->cart->get_cart();

	// 3.17.2, 2nd condition ensures that we only run this if a parent product's quantity is updated
	if( ! empty( $cart[$cart_item_key]['product_extras']['products']['parent_field_id'] ) && ! empty( $cart[$cart_item_key]['product_extras']['products']['child_products'] ) ) {
		$parent_field_id = $cart[$cart_item_key]['product_extras']['products']['parent_field_id'];

		// We've updated this parent ID so let's update all child products if linked
		foreach( $cart as $key=>$item ) {
			// Check that parent IDs match, that it's a child product, and that quantities are linked
			if( ! empty( $item['product_extras']['products']['parent_field_id'] ) &&
				$item['product_extras']['products']['parent_field_id'] == $parent_field_id &&
				isset( $item['product_extras']['products']['child_field'] ) &&
				isset( $item['product_extras']['products']['products_quantities'] ) &&
				$item['product_extras']['products']['products_quantities'] == 'linked' ) {

				// This is a child of the product we've just updated, so update the quantity
				WC()->cart->cart_contents[$key]['quantity'] = $quantity;

				// 3.23.1, also update the postmeta value
				if ( isset( $item['product_extras']['products']['field_id'] ) && 'yes' === pewc_display_child_products_as_meta() ) {
					$id = $item['product_extras']['products']['field_id'];
					list( , , $group_id, ) = explode( '_', $id );
					$child_product_id = $item['variation_id'] > 0 ? $item['variation_id'] : $item['product_id'];
					WC()->cart->cart_contents[$cart_item_key]['product_extras']['groups'][$group_id][$id]['child_products_quantities'][$child_product_id] = $quantity;
				}

			} else if( ! empty( $item['product_extras']['products']['parent_field_id'] ) &&
				$item['product_extras']['products']['parent_field_id'] == $parent_field_id &&
				isset( $item['product_extras']['products']['child_field'] ) &&
				isset( $item['product_extras']['products']['products_quantities'] ) &&
				$item['product_extras']['products']['products_quantities'] == 'independent' &&
				pewc_multiply_independent_quantities_by_parent_quantity() == 'yes' ) {

				// This is a child of the product we've just updated, so update the quantity
				// Multiply the child quantity by the parent product's old/new factor
				$child_quantity = WC()->cart->cart_contents[$key]['quantity'];
				$factor = $child_quantity/$old_quantity;
				$new_child_quantity = $quantity * $factor;

				WC()->cart->cart_contents[$key]['quantity'] = $new_child_quantity;

			}
		}
	}

}
add_action( 'woocommerce_after_cart_item_quantity_update', 'pewc_after_cart_item_quantity_update', 10, 3 );

/**
 * Remove any linked child products
 * allow_none means that the child product is not a required field - a user can buy a parent product without selecting a child product
 * @since 2.2.0
 */
function pewc_remove_cart_item( $cart_item_key, $cart ) {
	if( empty( $cart->cart_contents[$cart_item_key]['product_extras']['products']['parent_field_id'] ) ) {
		// This isn't a parent or child product, so don't need to do anything here
		return;
	}
	// Remove a parent, remove all linked children
	$parent_field_id = $cart->cart_contents[$cart_item_key]['product_extras']['products']['parent_field_id'];

	if( empty( $cart->cart_contents[$cart_item_key]['product_extras']['products']['child_field'] ) ) {

		// This is a parent product so let's remove all child products
		foreach( $cart->cart_contents as $key=>$item ) {
			// Check that parent IDs match and that it's a child product
			if( ! empty( $item['product_extras']['products']['parent_field_id'] ) &&
					$item['product_extras']['products']['parent_field_id'] == $parent_field_id &&
					isset( $item['product_extras']['products']['child_field'] ) ) {
						// This is a child of the product we've just removed, so remove it
				unset( $cart->cart_contents[$key] );
				// Add a notice that we'll use to remove the 'removed' notice, so that users can't undo the remove action
				wc_add_notice( 'Clear cart notices', 'pewc_clear_cart_notices' );
			}
		}

	} else {
		// Remove a child, so remove linked parent and other children, if allow_none is not set
		if( ! empty( $cart->cart_contents[$cart_item_key]['product_extras']['products']['allow_none'] ) ) {

			// Allow none is set, meaning the parent product doesn't require a child product - so we don't need to remove anything else
			return;

		} else {

			if( apply_filters( 'pewc_do_not_remove_parents', false ) ) {
				return;
			}

			// Allow none is not set, so all associated products must be removed
			foreach( $cart->cart_contents as $key=>$item ) {
				// Check that parent IDs match
				if( ! empty( $item['product_extras']['products']['parent_field_id'] ) &&
						$item['product_extras']['products']['parent_field_id'] == $parent_field_id ) {
							// This is a child of the product we've just removed, so remove it
					unset( $cart->cart_contents[$key] );
					// Add a notice that we'll use to remove the 'removed' notice, so that users can't undo the remove action
					wc_add_notice( 'Clear cart notices', 'pewc_clear_cart_notices' );
				}
			}
		}

	}

}
add_action( 'woocommerce_remove_cart_item', 'pewc_remove_cart_item', 10, 2 );
add_action( 'woocommerce_before_cart_item_quantity_zero', 'pewc_remove_cart_item', 10, 2 );

/**
 * Remove notices in the cart
 */
function pewc_remove_cart_notice() {
	if( is_admin() ) {
		return;
	}
	if( ! function_exists( 'WC' ) ) {
		return;
	}
	$notices = isset( WC()->session ) ? WC()->session->get( 'wc_notices', array() ) : array();
	// If pewc_clear_cart_notices is set, then remove the success notice
	if( isset( $notices['pewc_clear_cart_notices'] ) ) {
		unset( $notices['pewc_clear_cart_notices'] );
		unset( $notices['success'] );
		$notices = WC()->session->set( 'wc_notices', $notices );
	}
}
add_action( 'init', 'pewc_remove_cart_notice' );

/**
 * Filter remove link in cart
 * 
 * Note: if using WC Blocks (as of 9.4.3), the woocommerce_cart_item_remove_link filter is not used. See inc/functions-blocks.php
 */
function pewc_cart_item_remove_link( $link, $cart_item_key ) {

	$cart = WC()->cart->get_cart();
	$cart_item_data = $cart[$cart_item_key];

	// Updated 3.25.2 to ensure child products with force_quantity are linked to parent product
	$force_quantity = isset( $cart_item_data['product_extras']['products']['force_quantity'] ) ? $cart_item_data['product_extras']['products']['force_quantity'] : false;
	$independent = isset( $cart_item_data['product_extras']['products']['products_quantities'] ) ? $cart_item_data['product_extras']['products']['products_quantities'] == 'independent' : false;
	$fully_independent = ( $independent && ! $force_quantity );

	if( isset( $cart_item_data['product_extras']['products']['products_quantities'] ) &&
		$fully_independent &&
	 	! apply_filters( 'pewc_always_show_cart_arrow', false, $cart_item_key ) ) {

		// Independent quantities can be removed separately
		return $link;

	}

	// Filter out the remove link if it's a child product and allow_none is not set
	// Removed allow_none param in 3.5.3

	// if( isset( $cart_item_data['product_extras']['products']['child_field'] ) &&
	// 		$cart_item_data['product_extras']['products']['child_field'] &&
	// 		empty( $cart_item_data['product_extras']['products']['allow_none'] ) ) {

	if( isset( $cart_item_data['product_extras']['products']['child_field'] ) ) {
		// This is a child product with a linked quantity
		$arrow_right = sprintf(
			'<img src="%s" class="pewc-arrow-right">',
			esc_url( trailingslashit( PEWC_PLUGIN_URL ) . 'assets/images/arrow-right.svg' )
		);

		return apply_filters( 'pewc_filter_cart_remove_linked_product', $arrow_right, $cart_item_key );

	}

	return $link;

}
add_filter( 'woocommerce_cart_item_remove_link', 'pewc_cart_item_remove_link', 10, 2 );

/**
 * Filter quantity in cart
 * 
 * Note: if using WC Blocks (as of 9.4.3), the woocommerce_cart_item_quantity filter is not used. See inc/functions-blocks.php
 */
function pewc_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item=false ) {
	if( isset( $cart_item['product_extras']['products']['child_field'] ) &&
			$cart_item['product_extras']['products']['child_field'] == 1 &&
			isset( $cart_item['product_extras']['products']['products_quantities'] ) &&
			$cart_item['product_extras']['products']['products_quantities'] != 'independent' ) {
				// This is a child product with a linked quantity
		return apply_filters( 'pewc_filter_cart_quantity_linked_product', $cart_item['quantity'], $cart_item_key );
	} else if ( ! empty( $cart_item['data'] ) && pewc_hide_quantity( $cart_item['data'] ) ) {
		// 3.26.11, this is not a child product, check if quantity is hidden for the product
		return ! empty( $cart_item['quantity'] ) ? $cart_item['quantity'] : 1;
	}
	return $product_quantity;
}
add_filter( 'woocommerce_cart_item_quantity', 'pewc_cart_item_quantity', 10, 3 );

/**
 * Return the discounted child product price
 * @since 2.7.0
 */
function pewc_get_discounted_child_price( $child_price, $discount, $discount_type ) {
	$child_price = (float) $child_price; // 3.26.13, prevent errors from child products with no price
	$discounted_price = $child_price;
	if( $discount_type == 'fixed' ) {
		$discounted_price = max( $child_price - $discount, 0 );
	} else {
		$discounted_price = max( $child_price * ( ( 100 -  $discount ) / 100 ), 0 );
	}
	return $discounted_price;
}

/**
 * Find matching product variation
 *
 * @param WC_Product $product
 * @param array $attributes
 * @return int Matching variation ID or 0.
 */
function pewc_find_matching_product_variation( $product, $attributes ) {

    foreach( $attributes as $key => $value ) {
	    if( strpos( $key, 'attribute_' ) === 0 ) {
		    continue;
	    }

	    unset( $attributes[ $key ] );
	    $attributes[ sprintf( 'attribute_%s', $key ) ] = $value;
    }

    if( class_exists('WC_Data_Store') ) {

        $data_store = WC_Data_Store::load( 'product' );
        return $data_store->find_matching_product_variation( $product, $attributes );

    } else {

        return $product->get_matching_variation( $attributes );

    }

}

/**
 * Get variation default attributes
 *
 * @param WC_Product $product
 * @return array
 */
function pewc_get_default_attributes( $product ) {

    if( method_exists( $product, 'get_default_attributes' ) ) {

        return $product->get_default_attributes();

    } else {

        return $product->get_variation_default_attributes();

    }

}

/**
 * Used in Swatches layout in Products and Product Categories fields
 * @version	3.26.0
 */
function pewc_get_default_variation_id( $product, $default_child_products=array() ) {

	$default_attributes = pewc_get_default_attributes( $product );
	$variation_id = pewc_find_matching_product_variation( $product, $default_attributes );

	if( ! $variation_id ) {
		$variations = $product->get_children();

		if ( ! empty( $default_child_products ) ) {
			// 3.26.0
			foreach ( $variations as $var2 ) {
				if ( in_array( $var2, $default_child_products ) ) {
					$variation_id = $var2;
					break;
				}
			}
		}
		if ( ! $variation_id ) {
			// Get the first variation if a default isn't set
			$variation_id = $variations[0];
		}
	}
	return $variation_id;

}

/**
 * Get an already posted child product quantity value
 * @since 3.6.2
 */
function pewc_get_post_child_product_quantity( $quantity_field_value, $child_product_id, $id ) {

	if( ! empty( $_POST[$id . '_child_quantity'] ) ) {
		$quantity_field_value = $_POST[$id . '_child_quantity'];
	}
	return $quantity_field_value;

}

function pewc_child_product_independent_quantity_field( $quantity_field_values, $child_product_id, $id, $item ) {

	list( $pewc, $group, $group_id, $field_id ) = @explode( '_', $id ); // 3.13.7

	$quantity_field_value = apply_filters( 'pewc_products_field_independent_quantity', 0, $field_id ); // filter added in 3.13.7

	if( ! empty( $quantity_field_values ) ) {
		// If we're editing a product, this sets the quantity
		$quantity_field_value = array_values( $quantity_field_values )[0];
	}

	// Get a value that's already been posted
	$quantity_field_value = pewc_get_post_child_product_quantity( $quantity_field_value, $child_product_id, $id );

	// 3.26.5
	$attributes = apply_filters( 'pewc_filter_independent_quantity_field_attributes', array( 'aria-label' => strip_tags( $item['field_label'] ) . ' ' . __( 'Quantity', 'pewc' ) ), $child_product_id, $item );
	$attribute_string = '';
	if( ! empty( $attributes ) ) {
		foreach( $attributes as $attribute=>$attr_value ) {
			$attribute_string .= $attribute . '="' . esc_attr( $attr_value ) . '" ';
		}
	}

	// Add a quantity field for the child product
	printf(
		'<input type="number" min="0" step="1" class="pewc-form-field pewc-child-quantity-field pewc-independent-quantity-field" name="%s" value="%s" %s>',
		esc_attr( $id ) . '_child_quantity',
		$quantity_field_value,
		$attribute_string,
	);

}

/**
 * Hide child products in the cart
 * @since 3.7.21
 */
function pewc_hide_child_product_in_cart( $visible, $cart_item, $cart_item_key ) {
	$hide = get_option( 'pewc_hide_child_products_cart', 'no' );
	if( $hide != 'yes' ) {
		return $visible;
	}
  if( ! empty( $cart_item['product_extras']['products']['child_field'] ) ) {
    $visible = false;
  }
  return $visible;
}
add_filter( 'woocommerce_cart_item_visible', 'pewc_hide_child_product_in_cart' , 10, 3 );
add_filter( 'woocommerce_widget_cart_item_visible', 'pewc_hide_child_product_in_cart', 10, 3 );
add_filter( 'woocommerce_checkout_cart_item_visible', 'pewc_hide_child_product_in_cart', 10, 3 );

/**
 * Hide child products in the cart
 * @since 3.7.21
 */
function pewc_display_child_products_as_meta() {
	$display_meta = get_option( 'pewc_display_child_products_as_meta', 'no' );
  return $display_meta;
}

/**
 * Display child products as cart meta
 * @since 3.8.9
 */
function pewc_display_child_product_meta( $display, $field ) {

	if( pewc_display_child_products_as_meta() != 'yes' ) {
		return false;
	}
	return true;

}
add_filter( 'pewc_display_child_product_meta', 'pewc_display_child_product_meta', 10, 2 );

/**
 * Replace child product IDs with product names in cart meta
 * @since 3.8.9
 */
function pewc_replace_child_ids_with_titles( $value, $field ) {

	if( pewc_display_child_products_as_meta() != 'yes' ) {
		return $value;
	}

	if( isset( $field['type'] ) && ( $field['type'] == 'products' || $field['type'] == 'product-categories' ) ) {

		$ids = explode( ',', $value );
		if( $ids ) {
			$new_value = array();
			foreach( $ids as $id ) {
				$id = trim( $id );
				$product = wc_get_product( $id );
				if( is_object( $product ) ) {
					//$new_value[] = $product->get_name(); // since 3.12.1, this also works for variations
					$product_name = $product->get_name();
					if ( isset( $field['child_products_quantities'][$id] ) ) {
						// 3.15.0
						$child_product_quantity = $field['child_products_quantities'][$id];
						$product_name .= apply_filters( 'pewc_child_products_quantity_symbol', ' x ', $field ) . $child_product_quantity;
					}
					$new_value[] = $product_name;
				}
			}
			return join( apply_filters( 'pewc_child_products_metadata_separator', ', ', $field ), $new_value );
		}

	}

	return $value;

}
add_filter( 'pewc_filter_item_value_in_cart', 'pewc_replace_child_ids_with_titles', 10, 2 );

/**
 * Hide parent products in the cart
 * @since 3.7.21
 */
function pewc_hide_parent_product_in_cart( $visible, $cart_item, $cart_item_key ) {
	$hide = get_option( 'pewc_hide_parent_products_cart', 'no' );
	if( $hide != 'yes' ) {
		return $visible;
	}
  if( ! empty( $cart_item['product_extras']['products']['child_products'] ) ) {
    $visible = false;
  }
  return $visible;
}
add_filter( 'woocommerce_cart_item_visible', 'pewc_hide_parent_product_in_cart' , 10, 3 );
add_filter( 'woocommerce_widget_cart_item_visible', 'pewc_hide_parent_product_in_cart', 10, 3 );
add_filter( 'woocommerce_checkout_cart_item_visible', 'pewc_hide_parent_product_in_cart', 10, 3 );

/**
 * Don't count child products in the minicart
 * @since 3.7.21
 */
function pewc_exclude_child_products_minicart_counter( $quantity ) {
	$hide = get_option( 'pewc_hide_child_products_cart', 'no' );
	if( $hide != 'yes' ) {
		return $quantity;
	}
  $hidden = 0;
  foreach( WC()->cart->get_cart() as $cart_item ) {
    if( isset( $cart_item['product_extras']['products']['child_field'] ) ) {
			$hidden += $cart_item['quantity'];
		}
  }
  $quantity -= $hidden;
  return $quantity;
}
add_filter( 'woocommerce_cart_contents_count', 'pewc_exclude_child_products_minicart_counter' );

/**
 * Don't count parent products in the minicart
 * @since 3.7.21
 */
function pewc_exclude_parent_products_minicart_counter( $quantity ) {
	$hide = get_option( 'pewc_hide_parent_products_cart', 'no' );
	if( $hide != 'yes' ) {
		return $quantity;
	}
  $hidden = 0;
  foreach( WC()->cart->get_cart() as $cart_item ) {
    if( isset( $cart_item['product_extras']['products']['child_products'] ) ) {
			$hidden += $cart_item['quantity'];
		}
  }
  $quantity -= $hidden;
  return $quantity;
}
add_filter( 'woocommerce_cart_contents_count', 'pewc_exclude_parent_products_minicart_counter' );

/**
 * Hide child products in the order
 * @since 3.7.21
 */
function pewc_hide_child_product_in_order( $visible, $order_item ) {
	$hide = get_option( 'pewc_hide_child_products_order', 'no' );
	if( $hide != 'yes' ) {
		return $visible;
	}
	if( ! empty( $order_item['product_extras']['products']['child_field'] ) ) {
		$visible = false;
	}
  return $visible;
}
add_filter( 'woocommerce_order_item_visible', 'pewc_hide_child_product_in_order', 10, 2 );

/**
 * Hide parent products in the order
 * @since 3.7.21
 */
function pewc_hide_parent_product_in_order( $visible, $order_item ) {
	$hide = get_option( 'pewc_hide_parent_products_order', 'no' );
	if( $hide != 'yes' ) {
		return $visible;
	}
	if( ! empty( $order_item['product_extras']['products']['child_products'] ) ) {
		$visible = false;
	}
  return $visible;
}
add_filter( 'woocommerce_order_item_visible', 'pewc_hide_parent_product_in_order', 10, 2 );

function pewc_get_redirect_hidden_products() {
	$redirect = get_option( 'pewc_redirect_hidden_products', 'no' );
	return apply_filters( 'pewc_redirect_hidden_products', $redirect );
}

/**
 * Prevent users accessing the product pages for hidden products
 * @since 3.8.0
 */
function pewc_redirect_hidden_products() {

	if( is_admin() ) return;

	if( pewc_get_redirect_hidden_products() != 'yes' ) {
		return;
	}

	$product_id = get_the_ID();
	$product = wc_get_product( $product_id );

	if( ! is_wp_error( $product ) && is_object( $product ) ) {
		if( $product->get_catalog_visibility() == 'hidden' ) {
			 wp_redirect( home_url() );
			 exit;
		}
	}
	// wp_redirect( home_url() );

}
// Hook on wp so that WooCommerce conditionals are available
add_action( 'wp', 'pewc_redirect_hidden_products' );

/**
 * Multiply quantities of child products with independent quantities when the parent product quantity is adjusted in the cart
 * @since 3.8.2
 */
function pewc_multiply_independent_quantities_by_parent_quantity() {

	$multiply = get_option( 'pewc_multiply_independent_quantity', 'no' );
	return $multiply;

}

function pewc_enable_child_product_stock_check( $child_product_id ) {
	$enable = get_option( 'pewc_remove_parent', 'no' );
	return apply_filters( 'pewc_check_child_product_stock', $enable, $child_product_id );
}


/**
 * Remove the parent product from the cart if a child product is out of stock, or if sold individually
 * @since	3.9.2
 * @version	3.17.2
 */
function pewc_check_cart_items() {

	// If we've enabled the check
	$sold_individually = array();

	foreach( WC()->cart->cart_contents as $cart_item_key=>$cart_item ) {

		if( ! empty( $cart_item['product_extras']['products']['child_products'] ) ) {

			// This is a parent product so we might need to check that the child products are still in stock
			foreach( $cart_item['product_extras']['products']['child_products'] as $child_product_id=>$data ) {

				$child_product = wc_get_product( $child_product_id );

				// 3.17.2
				if ( is_object( $child_product ) && ! is_wp_error( $child_product ) && $child_product->is_sold_individually() && pewc_validate_child_products_sold_individually() ) {
					// check if we've seen this in the loop before
					if ( ! isset( $sold_individually[$child_product_id] ) ) {
						// first time we've seen this, save it for now including the parent's cart item key
						$sold_individually[$child_product_id] = $cart_item_key;
					} else {
						// this child product already exists in the cart, so remove this and the parent
						wc_add_notice(
							apply_filters(
								'pewc_child_product_sold_individually_message',
								__( 'One of the options in this order is sold individually, so duplicates have been removed from the cart', 'pewc' )
							),
							'error'
						);

						// Remove the main product from the cart
						WC()->cart->remove_cart_item( $cart_item_key );
					}
				}

				// Iterate through each child product and check its stock level
				// Remove the parent from the cart if this option is enabled
				if( pewc_enable_child_product_stock_check( $child_product_id ) == 'yes' ) {

					if( ( is_object( $child_product ) && ! is_wp_error( $child_product ) ) && ! $child_product->is_in_stock() ) {
						// Check whether it's still in stock and if we need to prevent purchasing the parent product
						wc_add_notice(
							apply_filters(
								'pewc_child_product_out_of_stock_message',
								__( 'One of the options in this order is out of stock so the product has been removed from the cart', 'pewc' )
							),
							'error'
						);

						// Remove the main product from the cart
						WC()->cart->remove_cart_item( $cart_item_key );

					}

				}

			}

		} else if ( empty( $cart_item['product_extras']['products']['child_field'] ) && pewc_validate_child_products_sold_individually() ) {

			// 3.17.2, we also need to keep track of other products in the cart that are not child products
			$product = wc_get_product( $cart_item['product_id'] );

			if ( ( is_object( $product ) && ! is_wp_error( $product ) ) && $product->is_sold_individually() ) {
				
				if ( ! isset( $sold_individually[$cart_item['product_id']] ) ) {
					// first time we've seen this, save it for now including the cart item key
					$sold_individually[$cart_item['product_id']] = $cart_item_key;
				} else {
					// this product already exists in the cart, so remove this
					wc_add_notice(
						apply_filters(
							'pewc_child_product_sold_individually_message',
							__( 'One of the options in this order is sold individually, so duplicates have been removed from the cart', 'pewc' )
						),
						'error'
					);

					// Remove the main product from the cart
					WC()->cart->remove_cart_item( $cart_item_key );
				}

			}

		}

	}

};
add_action( 'woocommerce_check_cart_items', 'pewc_check_cart_items', 10 );

/**
 * Schedule the data purge for product-categories field type add ons
 * @since 3.9.7
 *
 * @param int $product_id
 * @param WC_Product object $product
 */
function pewc_purge_product_categories_addons_products( $product_id, $product=false ){

	global $wpdb;

	$wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "%pewc_product_categories_products%"');

}
add_action( 'woocommerce_update_product', 'pewc_purge_product_categories_addons_products', 10, 2 );

/**
 * Set a default quantity for child products with independent quantities
 * @since	3.25.0
 * @version	3.26.11
 */
function pewc_child_product_default_quantity( $quantity_field_value, $child_product_id, $item, $variant_id=false ) {
	
	if( empty( $item['products_quantities'] ) || $item['products_quantities'] != 'independent' ) {
		return $quantity_field_value;
	}
	if( empty( $item['default_quantity'] ) ) {
		return $quantity_field_value;
	}

	// 3.26.11, default quantity shouldn't exceed available stock
	if ( apply_filters( 'pewc_child_product_default_quantity_check_stock', true, $child_product_id, $item, $variant_id ) ) {
		if ( false !== $variant_id ) {
			$child_product = wc_get_product( $variant_id );
		} else {
			$child_product = wc_get_product( $child_product_id );
		}
		if( is_object( $child_product ) && get_post_status( $child_product_id ) == 'publish' && $child_product->managing_stock() ) {
			$available_stock = $child_product->get_stock_quantity();
			if( $available_stock > 0 && ! $child_product->backorders_allowed() && $available_stock < (int) $item['default_quantity'] ) {
				return $available_stock;
			}
		}
	}

	return $item['default_quantity'];

}
add_filter( 'pewc_child_product_independent_quantity', 'pewc_child_product_default_quantity', 10, 4 );

/**
 * Return text only quantity info for child products with independent quantities and force quantity set
 * @since 3.25.0
 */
function pewc_force_quantity_field( $quantity_field, $max, $child_product_id, $id, $quantity_field_value, $disabled ) {
	
	$field_id = pewc_get_field_id( $id );
	$products_quantities = get_post_meta( $field_id, 'products_quantities', true );
	$force_quantity = get_post_meta( $field_id, 'force_quantity', true );

	if( empty( $products_quantities ) || $products_quantities != 'independent' ) {
		return $quantity_field;
	}
	if( empty( $force_quantity ) ) {
		return $quantity_field;
	}

	$quantity_field = sprintf(
		'<span class="pewc-force-quantity-wrapper"><input type="hidden" class="pewc-form-field pewc-child-quantity-field" name="%s" value="%s">%s</span>',
		esc_attr( $id ) . '_child_quantity_' . esc_attr( $child_product_id ),
		$quantity_field_value,
		$quantity_field_value,
	);

	return $quantity_field;

}
add_filter( 'pewc_filter_quantity_field', 'pewc_force_quantity_field', 10, 6 );

function pewc_grid_layout( $checkbox, $child_product_id, $price, $id, $name, $item, $child_product, $checkbox_id ) {

	$wrapper_classes = array(
		'pewc-checkbox-image-wrapper',
		'pewc-radio-checkbox-image-wrapper',
		'pewc-checkbox-wrapper'
	);

	$checkbox = sprintf(
		'<div class="%s">1<label for="%s"><input data-option-cost="%s" data-field-label="%s" type="checkbox" name="%s[]" id="%s" class="pewc-checkbox-form-field" value="%s" %s %s>%s<span class="pewc-theme-element"></span></label><div class="pewc-checkbox-desc-wrapper">%s<div class="pewc-radio-image-desc">%s</div></div></div>',
		join( ' ', $wrapper_classes ),
		esc_attr( $checkbox_id ),
		esc_attr( $option_cost ),
		get_the_title( $child_product_id ),
		esc_attr( $field_name ),
		esc_attr( $checkbox_id ),
		esc_attr( $child_product_id ),
		esc_attr( $checked ),
		esc_attr( $disabled ),
		$image,
		$quantity_field,
		apply_filters( 'pewc_child_product_name', $name, $item, $available_stock, $child_product )
	);
	
	return $checkbox;

}
// add_filter( 'pewc_filter_checkbox', 'pewc_grid_layout', 10, 8 );

/**
 * WooCommerce doesn't save the complete variation title (with attribute names) if the product has 3 or more attributes. We add back the attribute values below
 * @since 3.26.10
 */
function pewc_show_complete_variation_title( $title, $child_product ) {

	if ( is_a( $child_product, 'WC_Product_Variation' ) ) {
		$attributes = $child_product->get_variation_attributes( false ); // false, no attribute_ prefix
		if ( ! empty( $attributes) && $title === $child_product->get_title() ) {
			// if the variation title is the same as the parent product title, then the title doesn't contain the attributes
			$attribute_names = array();
			foreach ( $attributes as $attribute_slug=>$value_slug ) {
				$attr_name = $child_product->get_attribute( $attribute_slug ); // get display name
				if ( ! empty( $attr_name ) && ! in_array( $attr_name, $attribute_names ) ) {
					$attribute_names[] = $attr_name;
				}
			}
			if ( ! empty( $attribute_names ) ) {
				$title .= ' - ' . implode( ', ', $attribute_names );
			}
		}
	}
	return $title;

}
add_filter( 'pewc_child_product_title', 'pewc_show_complete_variation_title', 1, 2 );
