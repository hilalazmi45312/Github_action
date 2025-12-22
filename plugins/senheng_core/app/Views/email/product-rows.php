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
        max-width:700px;
        border-bottom: 2px solid #000;
    }
    #body_content .bwfan-email-product-rows .bwfan-product-rows td {
        padding: 20px 10px;
        border-bottom: 1px solid #000;
        vertical-align: top;
    }
    #body_content .bwfan-email-product-rows .bwfan-product-rows tr:last-child td {
        border-bottom: none;
    }
    .sh-extras-row td {
        padding-top: 0 !important;
        padding-bottom: 20px !important;
        border-top: 0;
        background-color: #fff;
    }
    .sh-extras-container {
        font-size: 13px;
        color: #666;
        padding-left: 72px; /* Align with product title (approx 60px image + padding) */
    }
    .sh-extra-item {
        display: block;
        margin-bottom: 6px;
        line-height: 1.4;
        padding-left: 10px;
        border-left: 2px solid #eee;
    }
    .sh-extra-name {
        font-weight: 500;
        color: #555;
    }
    .sh-extra-meta {
        font-size: 12px;
        color: #999;
        margin-left: 5px;
    }
    .sh-extra-price {
        float: right;
        color: #777;
    }
</style>
<?php

if ( is_array( $products ) ) : ?>
    <div class='bwfan-email-product-rows bwfan-email-table-wrap'>
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

			if ( false !== $cart ) {
				$cartItemLinkEnabled = apply_filters( 'bwfan_block_editor_enable_cart_item_link', true );
				$suffix              = BWFAN_Common::get_wc_tax_label_if_displayed();
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

                    ?>
                    <tr>
						<?php if ( false === $disable_product_thumbnail ) : ?>
                            <td class="image" width="60" style="vertical-align: top;">
								<?php if ( true === $cartItemLinkEnabled ) :
									$cartItemLink = BWFAN_Common::decode_merge_tags( apply_filters( 'bwfan_block_editor_alter_cart_item_link', '{{cart_recovery_link}}' ) );
									?>
                                    <a href="<?php echo esc_url( $cartItemLink ); ?>" target="_blank">
										<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 60 ) ); ?>
                                    </a>
								<?php else : ?>
									<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 60 ) ); ?>
								<?php endif; ?>
                            </td>
						<?php endif; ?>
                        <td width="" style="vertical-align: top;">
                            <h4 style="vertical-align:middle; margin: 0 0 5px;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></h4>
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
                                    
                                    echo '<div style="font-size:13px; color:#666;">' . esc_html( $att_label ) . ' : ' . esc_html( $att_value ) . '</div>';
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
                                    echo '<div style="font-size:13px; color:#666;">' . wp_kses_post( $meta->display_key ) . ': ' . wp_kses_post( strip_tags( $meta->display_value ) ) . '</div>';
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
                                    $trade_in_display = '<div style="font-size:13px; color:#666; margin-top: 4px;">' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($val) . '</div>';
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
                                    $trade_in_display = '<div style="font-size:13px; color:#666; margin-top: 4px;">' . esc_html__('Trade In', 'senheng-core') . ': ' . esc_html($val_display) . '</div>';
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
                                echo '<div style="font-size:13px; color:#666; margin-top: 4px;">' . esc_html__('Payment Option', 'senheng-core') . ': ' . esc_html__('Deposit Payment', 'senheng-core') . '</div>';
                                if ( $actual_price_display > 0 ) {
                                    echo '<div style="font-size:13px; color:#666; margin-top: 2px;">' . esc_html__('Actual Price', 'senheng-core') . ': ' . wc_price( $actual_price_display ) . '</div>';
                                }
                            }
                            ?>
                        </td>
                        <td align="right" class="last" width="100" style="vertical-align: top;">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
		                        <div style="white-space: nowrap;">
                                    <span style="margin-right: 10px;">x<?php echo esc_html( $quantity ); ?></span>
                                    <?php echo wp_kses_post( $line_total ); ?>
                                </div>
		                        <?php if ( $suffix && wc_tax_enabled() ): ?>
									<small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
		                        <?php endif; ?>
	                        <?php else: ?>
								<?php echo $price; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	                        <?php endif; ?>
                        </td>
                    </tr>

                    <?php 
                    // Product Extras Row (Inner Child Display)
                    if ( ! empty( $product_extras ) && ( ! empty( $product_extras['selected_products'] ) || ! empty( $product_extras['selected_info'] ) ) ) : 
                    ?>
                    <tr class="sh-extras-row">
                        <td colspan="3">
                            <div class="sh-extras-container">
                                <?php
                                // Selected Products (Bundles/Addons)
                                if ( ! empty( $product_extras['selected_products'] ) ) {
                                    foreach ( $product_extras['selected_products'] as $extra_product ) {
                                        $extra_title = isset($extra_product['title']) ? $extra_product['title'] : '';
                                        $extra_qty = isset($extra_product['quantity']) ? $extra_product['quantity'] : 1;
                                        // Calculate price if available (optional display)
                                        $extra_price = isset($extra_product['price']) ? $extra_product['price'] : 0;
                                        
                                        echo '<div class="sh-extra-item">';
                                        echo '<span class="sh-extra-name">+ ' . esc_html( $extra_title ) . '</span>';
                                        if ( $extra_qty > 1 ) {
                                            echo ' <span class="sh-extra-meta">(x' . esc_html( $extra_qty ) . ')</span>';
                                        }
                                        // Optional: Display price if > 0
                                        /* if ( $extra_price > 0 ) {
                                            echo '<span class="sh-extra-price">' . wc_price( $extra_price ) . '</span>';
                                        } */
                                        echo '</div>';
                                    }
                                }

                                // Selected Info (Warranty/Services)
                                if ( ! empty( $product_extras['selected_info'] ) ) {
                                     foreach ( $product_extras['selected_info'] as $info_data ) {
                                        $info_label = isset($info_data['infoLabel']) ? $info_data['infoLabel'] : '';
                                        $info_price = isset($info_data['infoPrice']) ? $info_data['infoPrice'] : '';
                                        
                                        echo '<div class="sh-extra-item">';
                                        echo '<span class="sh-extra-name">+ ' . esc_html( $info_label ) . '</span>';
                                        // Info usually has formatted price string like "+RM 100"
                                        // if ( ! empty( $info_price ) ) {
                                        //     echo '<span class="sh-extra-price">' . esc_html( $info_price ) . '</span>';
                                        // }
                                        echo '</div>';
                                     }
                                }
                                ?>
                            </div>
                        </td>
                    </tr>
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
					?>
                    <tr>
						<?php
						if ( true === $disable_product_link ) {
							if ( false === $disable_product_thumbnail ) {
								?>
                                <td class="image" width="60">
									<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 60 ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
								<?php
							} ?>
                            <td width="">
                                <h4 style="margin:0;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></h4>
                            </td>
							<?php
						} else {
							?>
							<?php if ( false === $disable_product_thumbnail ) : ?>
                                <td class="image" width="60">
                                    <a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank">
										<?php echo wp_kses_post( BWFAN_Common::get_product_image( $product, 'thumbnail', false, 60 ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </a>
                                </td>
							<?php endif; ?>
                            <td width="">
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>" target="_blank" style="text-decoration:none; color:#000;">
                                    <h4 style="margin:0;"><?php echo wp_kses_post( BWFAN_Common::get_name( $product ) ); ?></h4>
                                </a>
                            </td>
							<?php
						}
						?>

                        <td align="right" class="last" width="100">
	                        <?php if( false === BWFAN_Merge_Tag_Loader::get_data( 'is_preview' ) ): ?>
		                        <?php echo wp_kses_post( BWFAN_Common::price( $line_total, $currency ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
		                        <?php if ( $suffix && wc_tax_enabled() ): ?>
                                    <small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
		                        <?php endif; ?>
	                        <?php else: ?>
								<?php echo $price; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>think 
	                        <?php endif; ?>
                        </td>
                    </tr>
				<?php }
			} ?>
            </tbody>
        </table>
        <!--[if mso]>
        </td>
        </tr>
        </table>
        <![endif]-->
    </div>
<?php endif;
