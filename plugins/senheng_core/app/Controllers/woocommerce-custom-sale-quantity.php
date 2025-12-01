<?php

// Custom Sale Quantity Management for WooCommerce

// --- Check if Product is Effectively on Sale ---
function is_effectively_on_sale( $product ) {
	if ( ! $product || ! $product->is_on_sale() ) return false;

	$sale_price     = (float) $product->get_sale_price();
	$regular_price  = (float) $product->get_regular_price();
	$sales_quantity = (int) get_post_meta( $product->get_id(), '_sales_quantity', true );

	return $sales_quantity > 0 && $sale_price < $regular_price;
}

// --- Admin UI: Add Sale Quantity Field ---
add_action( 'woocommerce_product_options_inventory_product_data', function () {
	global $product_object;

	echo '<div class="options_group">';
	woocommerce_wp_text_input([
		'id'                => '_sales_quantity',
		'label'             => __( 'Sale Quantity', 'woocommerce' ),
		'desc_tip'          => true,
		'description'       => __( 'Stock available only when product is on sale.', 'woocommerce' ),
		'type'              => 'number',
		'custom_attributes' => [
			'min'  => '0',
			'step' => '1',
		],
		'value' => $product_object ? get_post_meta( $product_object->get_id(), '_sales_quantity', true ) : '',
	]);
	echo '</div>';
});
add_action( 'woocommerce_process_product_meta', function ( $post_id ) {
	if ( isset( $_POST['_sales_quantity'] ) ) {
		update_post_meta( $post_id, '_sales_quantity', intval( $_POST['_sales_quantity'] ) );
	}
	reset_sale_price_if_empty( $post_id );
});

// --- Variations: Add & Save Sale Quantity ---
add_action( 'woocommerce_variation_options_inventory', function ( $loop, $variation_data, $variation ) {
	woocommerce_wp_text_input([
		'id'                => '_sales_quantity[' . $variation->ID . ']',
		'label'             => __( 'Sale Quantity', 'woocommerce' ),
		'desc_tip'          => true,
		'description'       => __( 'Stock available only when product is on sale.', 'woocommerce' ),
		'type'              => 'number',
		'custom_attributes' => [
			'min'  => '0',
			'step' => '1',
		],
		'value' => get_post_meta( $variation->ID, '_sales_quantity', true ),
	]);
}, 10, 3 );

add_action( 'woocommerce_save_product_variation', function ( $variation_id ) {
	$sale_qty = isset( $_POST['_sales_quantity'][ $variation_id ] ) ? intval( $_POST['_sales_quantity'][ $variation_id ] ) : 0;
	update_post_meta( $variation_id, '_sales_quantity', $sale_qty );
	reset_sale_price_if_empty( $variation_id );
}, 10, 1 );



// --- Reset Sale Price if Sale Stock is 0 ---
function reset_sale_price_if_empty( $product_id ) {
	$sale_qty = (int) get_post_meta( $product_id, '_sales_quantity', true );
	if ( $sale_qty <= 0 ) {
		$product = wc_get_product( $product_id );
		if ( $product && $product->get_sale_price() !== '' ) {
			$product->set_sale_price( '' );
			$product->save();
			
			// Update existing cart items to use regular pricing
			// update_cart_items_pricing_when_sale_ends( $product_id );
		}
	}
}

// --- Fix Cart: Split Cart Items into Sale and Regular ---
add_filter( 'woocommerce_add_to_cart_validation', function ( $passed, $product_id, $quantity, $variation_id = 0, $variations = [] ) {
    static $processing = false;
    
    // Prevent infinite loops and return early if already processing
    if ( $processing ) {
        return $passed;
    }
    
    $product = wc_get_product( $variation_id ?: $product_id );
    $product_key = $variation_id ?: $product_id;
    
    // Check if this product has custom sale quantity meta (this makes it our responsibility)
    $sale_quantity_meta = get_post_meta( $product_key, '_sales_quantity', true );
    
    // If no sale quantity meta exists, let WooCommerce handle it normally
    if ( $sale_quantity_meta === '' || $sale_quantity_meta === false ) {
        return $passed;
    }
    
    $sale_quantity = (int) $sale_quantity_meta;
    
    // For products with sale quantity meta, we handle ALL stock validation
    // This ensures consistent behavior whether product is on sale or not

    // Check total stock availability first
    $stock_quantity = $product->get_stock_quantity();
    $total_in_cart = 0;
    
    foreach ( WC()->cart->get_cart() as $cart_item ) {
        if ( $cart_item['product_id'] == $product_id && 
            ($variation_id ? $cart_item['variation_id'] == $variation_id : true) ) {
            $total_in_cart += $cart_item['quantity'];
        }
    }

    // Check if adding new quantity would exceed total stock
    if ( ($total_in_cart + $quantity) > $stock_quantity ) {
        wc_add_notice( 
            sprintf( 
                __('You cannot add that amount to the cart — we have %s in stock and you already have %s in your cart. Please change your quantity or remove items from your cart. <a class="button wc-forward" href="%s">View Cart</a>', 'woocommerce'),
                $stock_quantity,
                $total_in_cart,
                wc_get_cart_url()
            ), 
            'error' 
        );
        return false;
    }

    // Set processing flag immediately
    $processing = true;

    try {
        // Helper to normalize various truthy values to 'yes'/'no'
        $normalize_yes_no = function($val) {
            if (!isset($val)) return 'no';
            $val = strtolower(trim((string)$val));
            return in_array($val, ['yes','true','1','on'], true) ? 'yes' : (
                in_array($val, ['no','false','0','off'], true) ? 'no' : ($val === 'deposit' ? 'yes' : ($val === 'full' ? 'no' : ($val === 'awcdp-enabled' ? 'yes' : 'no')))
            );
        };

        // Capture current request's trade/deposit options (normalized)
        $req_trade_in = $normalize_yes_no($_POST['trade_in'] ?? null);
        // Prefer AWCDP option; fallback to legacy deposit_option
        $req_deposit_yesno = isset($_POST['awcdp_deposit_option'])
            ? $normalize_yes_no($_POST['awcdp_deposit_option'])
            : $normalize_yes_no($_POST['deposit_option'] ?? null);
        // Count existing quantities in cart
        $already_in_cart_sale_qty = 0;
        $already_in_cart_regular_qty = 0;
        $existing_sale_cart_key = null;
        $existing_regular_cart_key = null;
        
        $already_in_cart_sale_qty_matched = 0;
        $already_in_cart_regular_qty_matched = 0;
        foreach ( WC()->cart->get_cart() as $cart_key => $item ) {
            $item_product_id  = $item['product_id'];
            $item_variation_id = $item['variation_id'] ?? 0;
            $item_force_regular = ! empty( $item['force_regular_price'] );

            $is_same_product = (
                $item_product_id == $product_id &&
                ( $variation_id ? $item_variation_id == $variation_id : true )
            );

            if ( $is_same_product ) {
                // Normalize stored trade/deposit on the cart item for comparison
                $item_trade_yesno = $normalize_yes_no($item['trade_in'] ?? null);
                // Prefer top-level awcdp_deposit_option; fallback to legacy deposit_option
                $item_deposit_yesno = isset($item['awcdp_deposit_option'])
                    ? $normalize_yes_no($item['awcdp_deposit_option'])
                    : $normalize_yes_no($item['deposit_option'] ?? null);

                // Only merge quantities when trade/deposit options MATCH the current request
                $options_match = ($item_trade_yesno === $req_trade_in) && ($item_deposit_yesno === $req_deposit_yesno);

                if ( ! $item_force_regular ) {
                    // Count all sale-price quantities for remaining_sale_qty calculation
                    $already_in_cart_sale_qty += $item['quantity'];
                    // Only count quantities for items with matching trade/deposit options
                    if ($options_match) {
                        $already_in_cart_sale_qty_matched += $item['quantity'];
                        $existing_sale_cart_key = $cart_key;
                    }
                } else {
                    // Count all regular-price quantities (not currently used for limit but kept for parity)
                    $already_in_cart_regular_qty += $item['quantity'];
                    if ($options_match) {
                        $already_in_cart_regular_qty_matched += $item['quantity'];
                        $existing_regular_cart_key = $cart_key;
                    }
                }
            }
        }

        $remaining_sale_qty = max( 0, $sale_quantity - $already_in_cart_sale_qty );

        // Handle different scenarios based on product sale status and quantities
        $is_effectively_on_sale = is_effectively_on_sale( $product );

        // If product is effectively on sale and quantity exceeds remaining sale stock, handle splitting
            if ( $is_effectively_on_sale && $quantity > $remaining_sale_qty ) {
                $sale_qty_to_add = $remaining_sale_qty;
                $regular_qty_to_add = $quantity - $remaining_sale_qty;
                
                // Update existing sale item or add new one
                if ( $sale_qty_to_add > 0 ) {
                    if ( $existing_sale_cart_key ) {
                        WC()->cart->set_quantity( $existing_sale_cart_key, $already_in_cart_sale_qty_matched + $sale_qty_to_add );
                    } else {
                        WC()->cart->add_to_cart( 
                            $product_id, 
                            $sale_qty_to_add, 
                            $variation_id, 
                            $variations, 
                            ['force_regular_price' => false]
                        );
                    }
                }
                
                // Update existing regular item or add new one
                if ( $regular_qty_to_add > 0 ) {
                    if ( $existing_regular_cart_key ) {
                        $current_regular_qty = WC()->cart->cart_contents[$existing_regular_cart_key]['quantity'];
                        WC()->cart->set_quantity( $existing_regular_cart_key, $already_in_cart_regular_qty_matched + $regular_qty_to_add );
                    } else {
                        WC()->cart->add_to_cart( 
                            $product_id, 
                            $regular_qty_to_add, 
                            $variation_id, 
                            $variations, 
                            ['force_regular_price' => true]
                        );
                    }
                }
            
            // Mark that we handled the cart addition via splitting for AJAX handler to treat as success
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('senheng_handled_add', true);
            }
            // Ensure cart changes persist in this request
            if (function_exists('WC') && WC()->cart) {
                WC()->cart->calculate_totals();
                WC()->cart->set_session();
            }
            $processing = false;
            return false; // Prevent the original add to cart
        }

        // If product is effectively on sale and quantity is within sale limit
            if ( $is_effectively_on_sale ) {
                // For quantities within sale limit, update existing or allow normal flow
                if ( $existing_sale_cart_key ) {
                    WC()->cart->set_quantity( $existing_sale_cart_key, $already_in_cart_sale_qty_matched + $quantity );
                    if (function_exists('WC') && WC()->session) {
                        WC()->session->set('senheng_handled_add', true);
                    }
                    if (function_exists('WC') && WC()->cart) {
                        WC()->cart->calculate_totals();
                        WC()->cart->set_session();
                    }
                    $processing = false;
                    return false; // Prevent duplicate addition
                }

            // Allow normal add to cart for sale items within limit
            $processing = false;
            return $passed;
        } else {
            // Product is not on sale or sale quantity is 0 - handle as regular stock only
            // Check if we need to update existing regular price items in cart
            if ( $existing_regular_cart_key ) {
                WC()->cart->set_quantity( $existing_regular_cart_key, $already_in_cart_regular_qty_matched + $quantity );
                $processing = false;
                return false; // Prevent duplicate addition
            }

            // Add as regular price item
            WC()->cart->add_to_cart( 
                $product_id, 
                $quantity, 
                $variation_id, 
                $variations, 
                ['force_regular_price' => true]
            );
            if (function_exists('WC') && WC()->session) {
                WC()->session->set('senheng_handled_add', true);
            }
            if (function_exists('WC') && WC()->cart) {
                WC()->cart->calculate_totals();
                WC()->cart->set_session();
            }
            $processing = false;
            return false; // Prevent original add to cart
        }
    } catch ( Exception $e ) {
        $processing = false;
        return false;
    }
}, 5, 5 );

// --- Ensure sale items get proper metadata ---
add_filter( 'woocommerce_add_cart_item_data', function( $cart_item_data, $product_id, $variation_id ) {
    $product = wc_get_product( $variation_id ?: $product_id );
    
    // Only set force_regular_price if not already set and product is on sale
    if ( ! isset( $cart_item_data['force_regular_price'] ) && is_effectively_on_sale( $product ) ) {
        $cart_item_data['force_regular_price'] = false;
    }
    
    return $cart_item_data;
}, 10, 3 );

// Ensure cart items with same product but different pricing are grouped properly in display
add_filter( 'woocommerce_cart_item_name', function( $product_name, $cart_item, $cart_item_key ) {
    if ( ! empty( $cart_item['force_regular_price'] ) ) {
        $product_name .= '';
    } else {
        // Check if this product has sale pricing
        $product = $cart_item['data'];
        if ( is_effectively_on_sale( $product ) ) {
            $product_name .= ' <small class="sale-price-indicator">(' . __( 'Promotion', 'woocommerce' ) . ')</small>';
        }
    }
    return $product_name;
}, 10, 3 );

// --- Apply Force Regular Price ---
// NOTE: This logic is now handled in WooCommerceAddtoCartController::handle_cart_pricing()
// to prevent conflicts between multiple woocommerce_before_calculate_totals hooks

// --- Save Metadata for Force Regular Price (Hidden from Customer) ---
add_filter( 'woocommerce_checkout_create_order_line_item', function ( $item, $cart_item_key, $values ) {
	if ( ! empty( $values['force_regular_price'] ) ) {
		// Save as hidden meta data (prefixed with underscore to hide from customer view)
		$item->add_meta_data( '_force_regular_price', 'yes', false );
	}
	return $item;
}, 10, 3 );

// --- Reduce Sale Quantity and Stock on Order Processing ---
add_action( 'woocommerce_order_status_processing', function ( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) continue;
        
        $product_id = $product->get_id();
        $qty = $item->get_quantity();
        $force_regular = $item->get_meta( '_force_regular_price' );

        // Handle sale quantity reduction for items purchased at sale price
        if ( is_effectively_on_sale( $product ) && ! $force_regular ) {
            $sale_stock = (int) get_post_meta( $product_id, '_sales_quantity', true );
            if ( $sale_stock >= $qty ) {
                $new_sale_qty = max( 0, $sale_stock - $qty );
                update_post_meta( $product_id, '_sales_quantity', $new_sale_qty );
                reset_sale_price_if_empty( $product_id );
            }
        }

        // Ensure regular stock is also reduced (WooCommerce should handle this automatically,
        // but we'll double-check to be safe)
        if ( $product->managing_stock() ) {
            $current_stock = $product->get_stock_quantity();
            if ( $current_stock !== null && $current_stock >= $qty ) {
                // Only reduce if stock hasn't been reduced already
                $stock_reduced = $item->get_meta( '_stock_reduced' );
                if ( ! $stock_reduced ) {
                    $new_stock = max( 0, $current_stock - $qty );
                    $product->set_stock_quantity( $new_stock );
                    $product->save();
                    
                    // Mark that stock has been reduced for this item
                    $item->add_meta_data( '_stock_reduced', 'yes' );
                    $item->save();
                }
            }
        }
    }
});

// --- Also handle stock reduction on order completion (backup) ---
add_action( 'woocommerce_order_status_completed', function ( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    foreach ( $order->get_items() as $item ) {
        $product = $item->get_product();
        if ( ! $product ) continue;
        
        $product_id = $product->get_id();
        $qty = $item->get_quantity();
        $force_regular = $item->get_meta( '_force_regular_price' );

        // Only handle sale quantity if not already processed
        if ( is_effectively_on_sale( $product ) && ! $force_regular ) {
            $sale_stock = (int) get_post_meta( $product_id, '_sales_quantity', true );
            // Check if sale stock reduction was already handled
            $sale_stock_reduced = $item->get_meta( '_sale_stock_reduced' );
            if ( ! $sale_stock_reduced && $sale_stock >= $qty ) {
                $new_sale_qty = max( 0, $sale_stock - $qty );
                update_post_meta( $product_id, '_sales_quantity', $new_sale_qty );
                reset_sale_price_if_empty( $product_id );
                
                // Mark that sale stock has been reduced
                $item->add_meta_data( '_sale_stock_reduced', 'yes' );
                $item->save();
            }
        }
    }
});

// --- Display Availability ---
add_filter( 'woocommerce_get_availability_text', function ( $availability, $product ) {
	if ( is_effectively_on_sale( $product ) ) {
		$sale_stock = (int) get_post_meta( $product->get_id(), '_sales_quantity', true );
		if ( $sale_stock <= 0 ) return __( 'Out of sale stock', 'woocommerce' );
	}
	return $availability;
}, 10, 2 );

// --- Hide Sale Price if Sale Stock = 0 ---
add_filter( 'woocommerce_product_get_sale_price', 'disable_sale_price_if_no_sale_stock', 20, 2 );
add_filter( 'woocommerce_product_get_price', 'disable_sale_price_if_no_sale_stock', 20, 2 );
function disable_sale_price_if_no_sale_stock( $price, $product ) {
	static $running = false;
	if ( $running ) return $price;

	$running = true;
	if ( $product && is_a( $product, 'WC_Product' ) ) {
		$sale_qty = (int) get_post_meta( $product->get_id(), '_sales_quantity', true );
		if ( $sale_qty <= 0 ) {
			$price = $product->get_regular_price();
		}
	}
	$running = false;
	return $price;
}

// --- Ensure proper cart item data handling ---
add_filter( 'woocommerce_add_cart_item_data', function( $cart_item_data, $product_id, $variation_id ) {
    // Ensure force_regular_price is always set
    if ( ! isset( $cart_item_data['force_regular_price'] ) ) {
        $cart_item_data['force_regular_price'] = false;
    }
    return $cart_item_data;
}, 10, 3 );

// --- Generate unique cart item key based on pricing type ---
add_filter( 'woocommerce_cart_id', function( $cart_id, $product_id, $variation_id, $variation, $cart_item_data ) {
    if ( isset( $cart_item_data['force_regular_price'] ) ) {
        $force_regular = $cart_item_data['force_regular_price'];
        $cart_id .= '_' . ( $force_regular ? 'regular' : 'sale' );
    }
    return $cart_id;
}, 10, 5 );

// --- Prevent double stock reduction ---
add_filter( 'woocommerce_can_reduce_order_stock', function( $reduce_stock, $order ) {
    // Check if we've already handled stock reduction manually
    foreach ( $order->get_items() as $item ) {
        $stock_reduced = $item->get_meta( '_stock_reduced' );
        if ( $stock_reduced ) {
            return false; // Prevent WooCommerce from reducing stock again
        }
    }
    return $reduce_stock;
}, 10, 2 );

// --- Hide force_regular_price meta from order item display ---
add_filter( 'woocommerce_hidden_order_itemmeta', function( $hidden_meta ) {
    $hidden_meta[] = 'force_regular_price';
    $hidden_meta[] = '_force_regular_price';
    return $hidden_meta;
} );

// --- Remove force_regular_price from order item meta display in thank you page and emails ---
add_filter( 'woocommerce_order_item_display_meta_key', function( $display_key, $meta, $item ) {
    if ( $meta->key === 'force_regular_price' || $meta->key === '_force_regular_price' ) {
        return false; // Hide this meta key
    }
    return $display_key;
}, 10, 3 );

add_filter( 'woocommerce_order_item_display_meta_value', function( $display_value, $meta, $item ) {
    if ( $meta->key === 'force_regular_price' || $meta->key === '_force_regular_price' ) {
        return false; // Hide this meta value
    }
    return $display_value;
}, 10, 3 );
