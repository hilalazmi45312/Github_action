<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
echo '<!-- SENHENG CUSTOM TEMPLATE LOADED -->';
$currency = is_array( $data ) && isset( $data['currency'] ) ? $data['currency'] : '';

/** checking if woocommerce exists other wise return */
if ( ! function_exists( 'bwfan_is_woocommerce_active' ) || ! bwfan_is_woocommerce_active() ) {
	return;
}

// Output styles directly as well to ensure they are present if the hook has already fired
?>
<style type="text/css">
    .bwfan-email-product-rows .bwfan-product-rows {
        width: 100%;
        border-collapse: collapse;
        max-width: 700px;
    }
    #body_content .bwfan-email-product-rows .bwfan-product-rows td {
        padding: 24px 12px;
        vertical-align: middle;
    }
    #body_content .bwfan-email-product-rows .bwfan-product-rows tr.sh-product-row td {
        border-bottom: 1px solid #333;
    }
    /* Remove border from product row when it has extras (last extra row will have the border) */
    #body_content .bwfan-email-product-rows .bwfan-product-rows tr.sh-product-row.has-extras td {
        border-bottom: none;
    }
    /* Extra product rows - same as normal but with indent */
    #body_content .bwfan-email-product-rows .bwfan-product-rows tr.sh-extra-product-row td {
        padding: 0 12px;
        border-bottom: none;
    }

    .bwfan-email-product-rows .sh-product-image img {
        width: 65px;
        height: auto;
        display: block;
    }
    .bwfan-email-product-rows .sh-product-name {
        font-size: 12px;    
        font-weight: 500;
        color: #333;
        margin: 0 0 6px 0;
        line-height: 1.3;
    }
    .bwfan-email-product-rows .sh-product-attr {
        font-size: 11px;
        color: #666;
        margin: 2px 0;
        line-height: 1.4;
    }
    .bwfan-email-product-rows .sh-product-qty {
        font-size: 13px;
        color: #555;
        text-align: right;
    }
    .bwfan-email-product-rows .sh-product-price {
        font-size: 13px;
        font-weight: 500;
        color: #333;
        text-align: center;
        white-space: nowrap;
    }
    /* Extra product row category header */
    .sh-extra-category-header {
        font-size: 11px;
        font-weight: 600;
        color: #555;
        margin-bottom: 4px;
        display: block;
    }
    /* Smaller image for extras */
    .bwfan-email-product-rows .sh-extra-product-row .sh-product-image img {
        width: 50px;
        height: auto;
    }
    /* Indent for extra rows */
    .sh-extra-indent {
        width: 30px;
    }
    /* Product name column - takes remaining space */
    .bwfan-email-product-rows .sh-product-name-col {
        width: auto;
    }
    /* Quantity column - fixed small width */
    .bwfan-email-product-rows .sh-product-qty-col {
        width: 50px;
        min-width: 50px;
    }
    /* Price column - fixed width to prevent it from being too large */
    .bwfan-email-product-rows .sh-product-price-col {
        width: 100px;
        min-width: 100px;
        max-width: 120px;
    }
    
    /* Mobile Responsive Styles - 360px */
    @media only screen and (max-width: 480px) {
        .bwfan-email-product-rows .bwfan-product-rows {
            width: 100% !important;
        }
        #body_content .bwfan-email-product-rows .bwfan-product-rows td {
            padding: 12px 8px !important;
        }
        .bwfan-email-product-rows .sh-product-image {
            width: 50px !important;
        }
        .bwfan-email-product-rows .sh-product-image img {
            width: 50px !important;
        }
        .bwfan-email-product-rows .sh-product-name {
            font-size: 11px !important;
            line-height: 1.2 !important;
        }
        .bwfan-email-product-rows .sh-product-attr {
            font-size: 9px !important;
        }
        .bwfan-email-product-rows .sh-product-qty {
            font-size: 11px !important;
        }
        .bwfan-email-product-rows .sh-product-price {
            font-size: 11px !important;
        }
        .bwfan-email-product-rows .sh-product-qty-col {
            width: 30px !important;
            min-width: 30px !important;
        }
        .bwfan-email-product-rows .sh-product-price-col {
            width: 80px !important;
            min-width: 80px !important;
            max-width: 90px !important;
        }
        .bwfan-email-product-rows .sh-product-name-col {
            width: 120px !important;
        }
        /* Extra product rows mobile */
        #body_content .bwfan-email-product-rows .bwfan-product-rows tr.sh-extra-product-row td {
            padding: 8px 8px !important;
        }
        .sh-extra-header-row td {
            padding-left: 20px !important;
            font-size: 11px !important;
        }
        .bwfan-email-product-rows .sh-extra-product-row .sh-product-image img {
            width: 40px !important;
        }
        .bwfan-email-product-rows .sh-extra-price-col {
            width: 75px !important;
            min-width: 75px !important;
            white-space: normal !important;
        }
        .bwfan-email-product-rows .sh-extra-product-name {
            font-size: 9px !important;
        }
        .bwfan-email-product-rows .sh-extra-price-text, 
        .bwfan-email-product-rows .sh-extra-qty-text {
            font-size: 10px !important;
        }
    }
</style>
<?php

if ( is_array( $products ) ) : ?>
    <table class='bwfan-email-product-rows bwfan-email-table-wrap' cellspacing="0" cellpadding="0" border="0" width="100%"><tr><td>
        <!--[if mso]>
        <table>
            <tr>
                <td width="700">
        <![endif]-->
        <table cellspacing="0" cellpadding="0" style="width: 100%;" class="bwfan-product-rows">
            <tbody>
			<?php
			$disable_product_link      = BWFAN_Common::disable_product_link();
			$disable_product_thumbnail = BWFAN_Common::disable_product_thumbnail();
			$suffix                    = BWFAN_Common::get_wc_tax_label_if_displayed();

			if ( false !== $cart ) {
				$cartItemLinkEnabled = apply_filters( 'bwfan_block_editor_enable_cart_item_link', true );
				foreach ( $cart as $item ) :
					// Handle both Cart Item (array) and Order Item (object)
                    $product = null;
                    if ( is_array( $item ) && isset( $item['data'] ) ) {
                        $product = $item['data'];
                    } elseif ( is_object( $item ) && method_exists( $item, 'get_product' ) ) {
                        $product = $item->get_product();
                    }

					if ( empty( $product ) || ! $product instanceof WC_Product ) {
						continue; // don't show items if there is no product
					}
					
                    // Price handling
                    $price = null;
                    $line_total = 0;
                    $quantity = 1;

                    if ( is_array( $item ) ) {
                        $price      = isset( $products_price[ $product->get_id() ] ) ? $products_price[ $product->get_id() ] : null;
                        $line_total = is_null( $price ) ? BWFAN_Common::get_prices_with_tax( $product ) : $price;
                        $quantity   = isset( $item['quantity'] ) ? $item['quantity'] : 1;
                    } elseif ( is_object( $item ) ) { // Order Item
                        // For order items, we might need to format price manually or use order functions
                        // Usually BWFAN passes formatted strings in $products_price if available, but let's check
                        $quantity = $item->get_quantity();
                        // If $products_price has key for this item ID (not product ID), use it?
                        // Usually for orders, we use $order->get_formatted_line_subtotal($item)
                        // But here we stick to basic display if not provided
                        $line_total = wc_price( $item->get_total() );
                    }

                    // Logic to get product extras
                    $product_extras = [];
                    if ( is_object( $item ) && is_callable( array( $item, 'get_meta' ) ) ) {
                        // Order Context: Retrieve from meta
                        $selected_products = $item->get_meta( '_product_extras_products', true );
                        $selected_info     = $item->get_meta( '_product_extras_info', true );
                        
                        if ( ! empty( $selected_products ) && is_array( $selected_products ) ) {
                            $product_extras['selected_products'] = $selected_products;
                        }
                        if ( ! empty( $selected_info ) && is_array( $selected_info ) ) {
                            $product_extras['selected_info'] = $selected_info;
                        }
                    } elseif ( is_array( $item ) && isset( $item['product_extras'] ) ) {
                        // Cart Context
                        $product_extras = $item['product_extras'];
                    }

                    // Check if this product has extras (for border styling)
                    $has_extras = ! empty( $product_extras ) && ( ! empty( $product_extras['selected_products'] ) || ! empty( $product_extras['selected_info'] ) );

                    ?>
                    <tr class="sh-product-row<?php echo $has_extras ? ' has-extras' : ''; ?>">
						<?php if ( false === $disable_product_thumbnail ) : ?>
                            <td class="sh-product-image" width="65" style="vertical-align: middle; text-align:center;">
								<?php if ( true === $cartItemLinkEnabled ) :
									$cartItemLink = BWFAN_Common::decode_merge_tags( apply_filters( 'bwfan_block_editor_alter_cart_item_link', '{{cart_recovery_link}}' ) );
									?>
                                    <a href="<?php echo esc_url( $cartItemLink ); ?>" target="_blank">
										<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 100 ) ); ?>
                                    </a>
								<?php else : ?>
									<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 100 ) ); ?>
								<?php endif; ?>
                            </td>
						<?php endif; ?>
                        <td class="sh-product-name-col" style="vertical-align: middle;">
                            <span class="sh-product-name" style="display:block;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></span>
                            <!-- Standard Variation Attributes -->
                            <?php 
                            if ( is_array( $item ) && ! empty( $item['variation'] ) ) {
                                foreach ( $item['variation'] as $att_name => $att_value ) {
                                    $taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $att_name ) ) );
                                    
                                    if ( taxonomy_exists( $taxonomy ) ) {
                                        $term = get_term_by( 'slug', $att_value, $taxonomy );
                                        if ( ! is_wp_error( $term ) && $term && $term->name ) {
                                            $att_value = $term->name;
                                        }
                                        $att_label = wc_attribute_label( $taxonomy );
                                    } else {
                                        $att_value = apply_filters( 'woocommerce_variation_option_name', $att_value, null, $taxonomy, $item['data'] );
                                        $att_label = wc_attribute_label( str_replace( 'attribute_', '', $att_name ), $item['data'] );
                                    }
                                    
                                    echo '<span class="sh-product-attr" style="display:block;">' . esc_html( $att_label ) . ' : ' . esc_html( $att_value ) . '</span>';
                                }
                            } elseif ( is_object( $item ) ) {
                                // Order item meta (variations)
                                $meta_data = $item->get_formatted_meta_data( '' );
                                foreach ( $meta_data as $meta_id => $meta ) {
                                    // Skip internal metas including our extras
                                    if ( in_array( $meta->key, ['_product_extras_products', '_product_extras_info'] ) || strpos( $meta->key, '_product_info_' ) === 0 || strpos( $meta->key, '_product_extra_' ) === 0 ) {
                                        continue;
                                    }
                                    // Also skip Trade In and Payment Option if we are going to handle them manually to ensure consistent styling
                                    if ( in_array( $meta->display_key, ['Trade In', 'Payment Option'] ) || in_array( $meta->key, ['_trade_in_option', 'trade_in', '_deposit_option', 'payment_option'] ) ) {
                                        continue;
                                    }
                                    echo '<span class="sh-product-attr" style="display:block;">' . wp_kses_post( $meta->display_key ) . ': ' . wp_kses_post( strip_tags( $meta->display_value ) ) . '</span>';
                                }
                            }

                            // Logic for Trade In and Payment Option (Unified for Cart and Order)
                            $trade_in_display = '';
                            $is_deposit_display = false;
                            $actual_price_display = 0;

                            if ( is_array( $item ) ) {
                                // Cart Context
                                if ( isset( $item['trade_in'] ) && '' !== $item['trade_in'] ) {
                                    $val = ( 'yes' === $item['trade_in'] ) ? __( 'Yes', 'senheng-core' ) : __( 'No', 'senheng-core' );
                                    $trade_in_display = '<span class="sh-product-attr" style="display:block;">' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($val) . '</span>';
                                }

                                if ( ( isset( $item['awcdp_deposit_option'] ) && 'yes' === $item['awcdp_deposit_option'] ) || 
                                     ( isset( $item['deposit_option'] ) && 'deposit' === $item['deposit_option'] ) ) {
                                    $is_deposit_display = true;
                                    $actual_price_display = $product->get_price();
                                }
                            } elseif ( is_object( $item ) ) {
                                // Order Context
                                // Check for Trade In meta
                                $trade_in_meta = $item->get_meta( 'Trade In', true );
                                if ( empty( $trade_in_meta ) ) {
                                    $trade_in_meta = $item->get_meta( '_trade_in_option', true );
                                    if ( empty( $trade_in_meta ) ) {
                                        $trade_in_meta = $item->get_meta( 'trade_in', true );
                                    }
                                }
                                
                                if ( ! empty( $trade_in_meta ) ) {
                                    // If it's just yes/no, format it
                                    if ( 'yes' === $trade_in_meta ) {
                                        $val_display = __( 'Yes', 'senheng-core' );
                                    } elseif ( 'no' === $trade_in_meta ) {
                                        $val_display = __( 'No', 'senheng-core' );
                                    } else {
                                        $val_display = $trade_in_meta;
                                    }
                                    $trade_in_display = '<span class="sh-product-attr" style="display:block;">' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($val_display) . '</span>';
                                }

                                // Check for Payment Option meta
                                $deposit_opt = $item->get_meta( '_deposit_option', true );
                                if ( empty( $deposit_opt ) ) {
                                    $deposit_opt = $item->get_meta( 'payment_option', true );
                                }
                                
                                if ( 'deposit' === $deposit_opt || 'Deposit Payment' === $item->get_meta( 'Payment Option', true ) ) {
                                    $is_deposit_display = true;
                                    // Attempt to retrieve regular price if available, else use current
                                    $actual_price_display = $product->get_regular_price(); 
                                    if ( ! $actual_price_display ) {
                                        $actual_price_display = $product->get_price();
                                    }
                                }
                            }

                            // Output Trade In
                            if ( ! empty( $trade_in_display ) ) {
                                echo $trade_in_display;
                            }

                            // Output Payment Option & Actual Price
                            if ( $is_deposit_display ) {
                                echo '<span class="sh-product-attr" style="display:block;">' . esc_html__('Deposit Payment', 'senheng-core') . '</span>';
                                if ( $actual_price_display > 0 ) {
                                    echo '<span class="sh-product-attr" style="display:block;">' . esc_html__('Actual Price', 'senheng-core') . ': ' . wc_price( $actual_price_display ) . '</span>';
                                }
                            }
                            ?>
                        </td>
                        <td class="sh-product-qty sh-product-qty-col" width="50" style="vertical-align: middle;">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
                                x<?php echo esc_html( $quantity ); ?>
	                        <?php else: ?>
                                x1
	                        <?php endif; ?>
                        </td>
                        <td class="sh-product-price sh-product-price-col" width="100" style="vertical-align: middle;">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
                                <?php echo wp_kses_post( $line_total ); ?>
		                        <?php if ( $suffix && wc_tax_enabled() ): ?>
									<br><small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
		                        <?php endif; ?>
	                        <?php else: ?>
								<?php echo $price; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	                        <?php endif; ?>
                        </td>
                    </tr>

                    <?php 
                    // Product Extras Rows (Display as normal product rows with indentation)
                    if ( ! empty( $product_extras ) && ( ! empty( $product_extras['selected_products'] ) || ! empty( $product_extras['selected_info'] ) ) ) : 
                        // Group extras by category
                        $grouped_extras = [];
                        
                        // Selected Products (Bundles/Addons/Gifts)
                        if ( ! empty( $product_extras['selected_products'] ) ) {
                            foreach ( $product_extras['selected_products'] as $extra_product ) {
                                $extra_product_id = isset($extra_product['selected_product']) ? intval($extra_product['selected_product']) : 0;
                                $extra_variation_id = isset($extra_product['selected_variation']) ? intval($extra_product['selected_variation']) : 0;
                                
                                // Try to get the actual product object
                                $extra_wc_product = null;
                                if ( $extra_variation_id > 0 ) {
                                    $extra_wc_product = wc_get_product( $extra_variation_id );
                                } elseif ( $extra_product_id > 0 ) {
                                    $extra_wc_product = wc_get_product( $extra_product_id );
                                }
                                
                                $category = isset($extra_product['field_label']) ? $extra_product['field_label'] : __('Extras', 'senheng-core');
                                if ( ! isset( $grouped_extras[$category] ) ) {
                                    $grouped_extras[$category] = [];
                                }
                                
                                $grouped_extras[$category][] = [
                                    'type' => 'product',
                                    'title' => isset($extra_product['title']) ? $extra_product['title'] : '',
                                    'quantity' => isset($extra_product['quantity']) ? intval($extra_product['quantity']) : 1,
                                    'price' => isset($extra_product['price']) ? floatval($extra_product['price']) : 0,
                                    'wc_product' => $extra_wc_product,
                                ];
                            }
                        }
                        
                        // Selected Info (Warranty/Services) - These don't have product images
                        if ( ! empty( $product_extras['selected_info'] ) ) {
                            foreach ( $product_extras['selected_info'] as $info_data ) {
                                $category = isset($info_data['field_label']) ? $info_data['field_label'] : __('Services', 'senheng-core');
                                if ( ! isset( $grouped_extras[$category] ) ) {
                                    $grouped_extras[$category] = [];
                                }
                                
                                $grouped_extras[$category][] = [
                                    'type' => 'info',
                                    'title' => isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '',
                                    'quantity' => 1,
                                    'price' => isset($info_data['infoPrice']) ? $info_data['infoPrice'] : '',
                                    'wc_product' => null,
                                ];
                            }
                        }
                        
                        // Count total items for determining last item
                        $total_items = 0;
                        foreach ( $grouped_extras as $items ) {
                            $total_items += count( $items );
                        }
                        $current_item = 0;
                        
                        // Loop through each category group
                        foreach ( $grouped_extras as $category_name => $category_items ) :
                    ?>
                    <!-- Category Header Row -->
                    <tr class="sh-extra-header-row">
                        <td colspan="4" style="padding: 12px 12px 0 45px; font-size: 12px; font-weight: 600; color: #444; border-bottom: none;">
                            <?php echo esc_html( $category_name ); ?>
                        </td>
                    </tr>
                    <?php 
                        // Loop through items in this category
                        foreach ( $category_items as $extra ) :
                            $current_item++;
                            $is_last_extra = ( $current_item === $total_items );
                            $extra_wc_product = $extra['wc_product'];
                    ?>
                    <!-- Extra Product Row - Using nested table for better layout -->
                    <tr class="sh-extra-product-row<?php echo $is_last_extra ? ' last-extra' : ''; ?>">
                        <td colspan="4" style="padding: 8px 12px 0 30px;<?php echo $is_last_extra ? ' padding-bottom: 15px; border-bottom: 1px solid #333;' : ''; ?>">
                            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse; margin: 0 !important;">
                                <tr>
                                    <!-- Image + Name & Variation (combined wider cell) -->
                                    <td style="vertical-align: middle; padding-right: 15px;">
                                        <table cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse; margin: 0 !important;">
                                            <tr>
                                                <!-- Product Image -->
                                                <td style="vertical-align: middle; width: 50px; padding-right: 12px;">
                                                    <?php 
                                                    if ( $extra_wc_product instanceof WC_Product ) {
                                                        echo wp_kses_post( BWFAN_Common::get_product_image( $extra_wc_product, 'thumbnail', false, 50 ) );
                                                    } else {
                                                        echo '<div style="width:50px;height:50px;background:#f5f5f5;border-radius:4px;"></div>';
                                                    }
                                                    ?>
                                                </td>
                                                <!-- Product Name & Variation -->
                                                <td style="vertical-align: middle;">
                                                    <span class="sh-extra-product-name" style="display:block; font-size: 12px; font-weight: 500; color: #333; line-height: 1.4;"><?php echo esc_html( $extra['title'] ); ?></span>
                                                    <?php 
                                                    // Show variation attributes if it's a variation
                                                    if ( $extra_wc_product instanceof WC_Product_Variation ) {
                                                        $attributes = $extra_wc_product->get_variation_attributes();
                                                        foreach ( $attributes as $attr_key => $attr_value ) {
                                                            $attr_label = wc_attribute_label( str_replace( 'attribute_', '', $attr_key ) );
                                                            echo '<span style="display:block; font-size: 11px; color: #666; line-height: 1.4;">' . esc_html( $attr_label ) . ': ' . esc_html( $attr_value ) . '</span>';
                                                        }
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <!-- Price & Quantity -->
                                    <td class="sh-extra-price-col" style="vertical-align: middle; text-align: center; width: 100px; white-space: nowrap;">
                                        <?php 
                                        if ( $extra['type'] === 'product' ) {
                                            echo '<span class="sh-extra-price-text" style="font-size: 12px; font-weight: 500; color: #333;">' . wp_kses_post( wc_price( $extra['price'] ) ) . '</span>';
                                        } else {
                                            echo '<span class="sh-extra-price-text" style="font-size: 12px; font-weight: 500; color: #333;">' . esc_html( $extra['price'] ) . '</span>';
                                        }
                                        ?>
                                        <br><span class="sh-extra-qty-text" style="font-size: 11px; color: #666;">Qty: <?php echo esc_html( $extra['quantity'] ); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endforeach; // end items loop ?>
                    <?php endforeach; // end category loop ?>
                    <?php endif; ?>

				<?php endforeach;
			} else {
                // Fallback for when $cart is false (e.g. manual product list without cart context)
                // We probably won't have product_extras here unless attached to product object
				foreach ( $products as $product ) {
					if ( ! $product instanceof WC_Product ) {
						continue;
					}
					$price      = isset( $products_price[ $product->get_id() ] ) ? $products_price[ $product->get_id() ] : null;
					$line_total = is_null( $price ) ? BWFAN_Common::get_prices_with_tax( $product ) : $price;
					$quantity   = 1; // Default quantity for fallback

                    // Check if in preview mode
                    $is_preview = BWFAN_Merge_Tag_Loader::get_data( 'is_preview' );
                    
                    // Sample preview data for demonstration
                    $sample_variation = '';
                    $sample_trade_in = '';
                    $sample_payment = '';
                    $sample_extras = [];
                    $has_extras = false;

                    if ( $is_preview ) {
                        // Sample variation
                        $sample_variation = '<span class="sh-product-attr" style="display:block;">Color: Black</span>';
                        
                        // Sample trade-in
                        $sample_trade_in = '<span class="sh-product-attr" style="display:block;">' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html__('Yes', 'senheng-core') . '</span>';
                        
                        // Sample deposit payment  
                        $sample_payment = '<span class="sh-product-attr" style="display:block;">' . esc_html__('Deposit Payment', 'senheng-core') . '</span>';
                        $sample_payment .= '<span class="sh-product-attr" style="display:block;">' . esc_html__('Actual Price', 'senheng-core') . ': ' . wc_price( $product->get_price() ) . '</span>';
                        
                        // Sample extras (free gift + warranty)
                        $sample_extras = [
                            'selected_products' => [
                                [ 'title' => __('Free Gift: Carrying Case', 'senheng-core'), 'quantity' => 1, 'price' => 0 ],
                            ],
                            'selected_info' => [
                                [ 'infoLabel' => __('Extended Warranty (3 Years)', 'senheng-core'), 'infoPrice' => 'RM199' ],
                            ],
                        ];
                        $has_extras = true;
                    }
					?>
                    <tr class="sh-product-row<?php echo $has_extras ? ' has-extras' : ''; ?>">
						<?php
						if ( true === $disable_product_link ) {
							if ( false === $disable_product_thumbnail ) {
								?>
                                <td class="sh-product-image" width="100" style="vertical-align: middle; text-align:center;">
									<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 100 ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
								<?php
							} ?>
                            <td class="sh-product-name-col" style="vertical-align: middle;">
                                <span class="sh-product-name" style="display:block;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></span>
                                <?php 
                                // Preview: Show sample variation, trade-in, payment
                                if ( $is_preview ) {
                                    echo $sample_variation;
                                    echo $sample_trade_in;
                                    echo $sample_payment;
                                }
                                ?>
                            </td>
							<?php
						} else {
							?>
							<?php if ( false === $disable_product_thumbnail ) : ?>
                                <td class="sh-product-image" width="100" style="vertical-align: middle; text-align:center;">
                                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank">
										<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 100 ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </a>
                                </td>
							<?php endif; ?>
                            <td class="sh-product-name-col" style="vertical-align: middle;">
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank" style="text-decoration:none; color:#000;">
                                    <span class="sh-product-name" style="display:block;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></span>
                                </a>
                                <?php 
                                // Preview: Show sample variation, trade-in, payment
                                if ( $is_preview ) {
                                    echo $sample_variation;
                                    echo $sample_trade_in;
                                    echo $sample_payment;
                                }
                                ?>
                            </td>
							<?php
						}
						?>

                        <td class="sh-product-qty sh-product-qty-col" width="50" style="vertical-align: middle;">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
                                x<?php echo esc_html( $quantity ); ?>
	                        <?php else: ?>
                                x1
	                        <?php endif; ?>
                        </td>
                        <td class="sh-product-price sh-product-price-col" width="100" style="vertical-align: middle;">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
		                        <?php echo wp_kses_post( BWFAN_Common::price( $line_total, $currency ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
		                        <?php if ( $suffix && wc_tax_enabled() ): ?>
                                    <br><small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
		                        <?php endif; ?>
	                        <?php else: ?>
								<?php echo wp_kses_post( BWFAN_Common::price( $line_total, $currency ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
	                        <?php endif; ?>
                        </td>
                    </tr>

                    <?php 
                    // Product Extras Row (Preview sample extras) - Display as full product rows with category headers
                    if ( $has_extras && ! empty( $sample_extras ) ) : 
                        // Group sample extras by category
                        $grouped_sample_extras = [];
                        
                        if ( ! empty( $sample_extras['selected_products'] ) ) {
                            $category = __('Gift', 'senheng-core');
                            if ( ! isset( $grouped_sample_extras[$category] ) ) {
                                $grouped_sample_extras[$category] = [];
                            }
                            foreach ( $sample_extras['selected_products'] as $extra_product ) {
                                $grouped_sample_extras[$category][] = [
                                    'type' => 'product',
                                    'title' => isset($extra_product['title']) ? $extra_product['title'] : '',
                                    'quantity' => isset($extra_product['quantity']) ? $extra_product['quantity'] : 1,
                                    'price' => isset($extra_product['price']) ? $extra_product['price'] : 0,
                                ];
                            }
                        }
                        
                        if ( ! empty( $sample_extras['selected_info'] ) ) {
                            $category = __('Warranty', 'senheng-core');
                            if ( ! isset( $grouped_sample_extras[$category] ) ) {
                                $grouped_sample_extras[$category] = [];
                            }
                            foreach ( $sample_extras['selected_info'] as $info_data ) {
                                $grouped_sample_extras[$category][] = [
                                    'type' => 'info',
                                    'title' => isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '',
                                    'quantity' => 1,
                                    'price' => isset($info_data['infoPrice']) ? $info_data['infoPrice'] : '',
                                ];
                            }
                        }
                        
                        // Count total items for determining last item
                        $total_sample_items = 0;
                        foreach ( $grouped_sample_extras as $items ) {
                            $total_sample_items += count( $items );
                        }
                        $current_sample_item = 0;
                        
                        // Loop through each category group
                        foreach ( $grouped_sample_extras as $category_name => $category_items ) :
                    ?>
                    <!-- Category Header Row -->
                    <tr class="sh-extra-header-row">
                        <td colspan="4" style="padding: 12px 12px 0 45px; font-size: 12px; font-weight: 600; color: #444; border-bottom: none;">
                            <?php echo esc_html( $category_name ); ?>
                        </td>
                    </tr>
                    <?php 
                        foreach ( $category_items as $sample_extra ) :
                            $current_sample_item++;
                            $is_last_sample_extra = ( $current_sample_item === $total_sample_items );
                    ?>
                    <!-- Extra Product Row - Using nested table for better layout -->
                    <tr class="sh-extra-product-row<?php echo $is_last_sample_extra ? ' last-extra' : ''; ?>">
                        <td colspan="4" style="padding: 8px 12px 0 30px;<?php echo $is_last_sample_extra ? ' padding-bottom: 15px; border-bottom: 1px solid #333;' : ''; ?>">
                            <table cellspacing="0" cellpadding="0" border="0" width="100%" style="border-collapse: collapse; margin: 0 !important;">
                                <tr>
                                    <!-- Image + Name (combined wider cell) -->
                                    <td style="vertical-align: middle; padding-right: 15px;">
                                        <table cellspacing="0" cellpadding="0" border="0" style="border-collapse: collapse; margin: 0 !important;">
                                            <tr>
                                                <!-- Product Image (placeholder) -->
                                                <td style="vertical-align: middle; width: 50px; padding-right: 12px;">
                                                    <div style="width:50px;height:50px;background:#f5f5f5;border-radius:4px;"></div>
                                                </td>
                                                <!-- Product Name -->
                                                <td style="vertical-align: middle;">
                                                    <span class="sh-extra-product-name" style="display:block; font-size: 12px; font-weight: 500; color: #333; line-height: 1.4;"><?php echo esc_html( $sample_extra['title'] ); ?></span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <!-- Price & Quantity -->
                                    <td class="sh-extra-price-col" style="vertical-align: middle; text-align: center; width: 100px; white-space: nowrap;">
                                        <?php 
                                        if ( $sample_extra['type'] === 'product' ) {
                                            echo '<span class="sh-extra-price-text" style="font-size: 12px; font-weight: 500; color: #333;">' . wp_kses_post( wc_price( $sample_extra['price'] ) ) . '</span>';
                                        } else {
                                            echo '<span class="sh-extra-price-text" style="font-size: 12px; font-weight: 500; color: #333;">' . esc_html( $sample_extra['price'] ) . '</span>';
                                        }
                                        ?>
                                        <br><span class="sh-extra-qty-text" style="font-size: 11px; color: #666;">Qty: <?php echo esc_html( $sample_extra['quantity'] ); ?></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <?php endforeach; // end items loop ?>
                    <?php endforeach; // end category loop ?>
                    <?php endif; ?>

				<?php }
			} ?>
            </tbody>
        </table>
        <!--[if mso]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </td></tr></table>
<?php endif;
