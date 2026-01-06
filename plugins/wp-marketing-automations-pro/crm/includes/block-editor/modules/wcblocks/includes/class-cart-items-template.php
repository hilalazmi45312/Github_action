<?php

if ( ! class_exists( 'BWFBE_WC_Cart_Items_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WC_Cart_Items_Template {
		/**
		 * Instance.
		 *
		 * @access private
		 * @var object Instance
		 * @since 1.2.0
		 */
		private static $instance;

		private $responsive_style;

		private $wrapper_selector = '';

		private $tableAttrs = '';

		private $settings = [];

		public static $cart;
		private static $is_preview = false;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Get the price text label.
		 *
		 * @return string
		 *
		 */
		public static function get_wc_tax_label_if_displayed() {
			if ( ! wc_tax_enabled() ) {
				return '';
			}

			$tax_display_mode   = get_option( 'woocommerce_tax_display_cart' );
			$prices_include_tax = wc_prices_include_tax();

			if ( $tax_display_mode === 'incl' && ! $prices_include_tax ) {
				return WC()->countries->inc_tax_or_vat();
			}

			if ( $tax_display_mode === 'excl' && $prices_include_tax ) {
				return WC()->countries->ex_tax_or_vat();
			}

			return '';
		}

		public function __construct() {
			add_shortcode( 'bwfbe_cart_items', [ $this, 'cart_items_block' ] );
		}

		public function cart_items_block( $atts, $content ) {
			self::$cart       = [];
			$mergetagdata     = BWFAN_Merge_Tag_Loader::get_data();
			self::$is_preview = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];

			if ( isset( $mergetagdata['cart_details'] ) ) {
				//set cart
				if ( isset( $mergetagdata['cart_details']['items'] ) ) {
					$mergetagdata['cart_details']['items'] = maybe_unserialize( $mergetagdata['cart_details']['items'] );
				}
				self::$cart = $mergetagdata['cart_details'];
			} else if ( isset( $mergetagdata['cart_abandoned_id'] ) && ! empty( $mergetagdata['cart_abandoned_id'] ) ) {
				// get cart data
				$cart_details = BWFAN_Model_Abandonedcarts::get( intval( $mergetagdata['cart_abandoned_id'] ) );
				if ( isset( $cart_details['items'] ) ) {
					$cart_details['items'] = maybe_unserialize( $cart_details['items'] );
				}
				self::$cart = $cart_details;
			}

			if ( empty( self::$cart ) && ! self::$is_preview ) {
				return '';
			}

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			do_action( 'bwfan_email_setup_locale', isset( self::$cart['lang'] ) ? self::$cart['lang'] : '' );

			ob_start();

			$defaults = [
				'padding'               => [
					'desktop' => [ 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px' ],
					'mobile'  => [ 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'unit' => 'px' ]
				],
				'dividerStyle'          => [ 'desktop' => 'solid' ],
				'layout'                => 'list',
				'alignment'             => [ 'desktop' => 'center' ],
				'imgAutoWidth'          => [ 'desktop' => false ],
				'imgWidth'              => [ 'desktop' => [ 'value' => 30, 'unit' => 'px' ] ],
				'productTitleBold'      => [ 'desktop' => true ],
				'totalTextBold'         => [ 'desktop' => true ],
				'totalPriceBold'        => [ 'desktop' => true ],
				'uniqueID'              => '',
				'enabledContentOptions' => [
					'desktop' => [ 'image', 'title', 'attrs', 'qty', 'desc', 'price' ]
				],
				'commonPriceFont'       => [
					'desktop' => [
						'size' => 14
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'productTitleFont'      => [
					'desktop' => [
						'size' => 16
					],
					'mobile'  => [
						'size' => 14
					],
				],
				'productTitleFont'      => [
					'desktop' => [
						'size' => 13
					],
					'mobile'  => [
						'size' => 12
					],
				],
				'qtyFont'               => [
					'desktop' => [
						'size' => 14
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'pricesMetaFont'        => [
					'desktop' => [
						'size' => 14
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'pricesMetaColor'       => [
					'desktop' => '#82838e'
				],
				'discountColor'         => [
					'desktop' => '#09B29C'
				],
				'subTotalPriceFont'     => [
					'desktop' => [
						'size' => 14
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'subTotalTextFont'      => [
					'desktop' => [
						'size' => 14
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'totalPriceFont'        => [
					'desktop' => [
						'size' => 16
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'totalTextFont'         => [
					'desktop' => [
						'size' => 16
					],
					'mobile'  => [
						'size' => 13
					],
				],
				'subTotalTextColor'     => [
					'desktop' => '#82838e'
				],
				'productTitleBold'      => [
					'desktop' => true
				],
				'totalTextBold'         => [
					'desktop' => true
				],
				'totalPriceBold'        => [
					'desktop' => true
				],
				'discountColor'         => [
					'desktop' => '#09b29c'
				],
				'itemImgWidth'          => [
					'desktop' => [
						'value' => '48',
						'unit'  => 'px'
					]
				],
				'qtyLabel'              => __( 'Qty', 'woocommerce' ),
				'subtotalLabel'         => __( 'Subtotal', 'woocommerce' ),
				'shippingLabel'         => __( 'Shipping', 'woocommerce' ),
				'taxesLabel'            => __( 'Taxes', 'woocommerce' ),
				'discountLabel'         => __( 'Discount', 'wp-marketing-automations-pro' ),
				'totalLabel'            => __( 'Total', 'woocommerce' ),
				'imageSize'             => 'thumbnail',
				'productMetaFont'       => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '12',
						'unit' => 'px'
					]
				],
				'productMetaColor'      => [
					'desktop' => '#82838e'
				],
				'productSkuFont'        => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '12',
						'unit' => 'px'
					]
				],
				'productSkuColor'       => [
					'desktop' => '#82838e'
				],
			];

			$this->settings = wp_parse_args( $attributes, $defaults );

			$data             = $this->get_cart_data();
			$suffix           = self::get_wc_tax_label_if_displayed();
			$showProductImage = $this->is_section_enable( 'image' );
			$showDesc         = $this->is_section_enable( 'desc' );
			$showPrice        = $this->is_section_enable( 'price' );
			$showSKU          = $this->is_section_enable( 'sku' );

			$imgSize = isset( $this->settings['itemImgWidth']['desktop']['value'] ) ? $this->settings['itemImgWidth']['desktop']['value'] : '48';

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			$this->settings['classname'] = [
				'bwf-email-cart-items',
				'bwf-email-cart-items-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwf-email-cart-items.bwf-email-cart-items-' . $this->settings['uniqueID'];
			$currency               = is_array( $data ) & isset( $data['currency'] ) ? $data['currency'] : '';
			$args                   = array( 'currency' => $currency );
			remove_all_filters( 'woocommerce_currency_symbol' );
			$subtotal            = 0;
			$tax                 = 0;
			$cartItemLinkEnabled = apply_filters( 'bwfan_block_editor_enable_cart_item_link', true );
			$cartItemLink        = apply_filters( 'bwfan_block_editor_alter_cart_item_link', '{{cart_recovery_link}}' );
			?>
            <style data-id='woofunnels'>
                .cart-item.list-layout .product-info .product-meta li div,
                .cart-item.list-layout .product-info .product-meta li p {
                    display: inline-block;
                }

                .cart-item.list-layout .product-info ul.product-meta-ul {
                    margin: 0;
                }
            </style>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width: 100%;">
						<?php
						if ( $this->settings['layout'] === 'list' ) {
							$count       = 0;
							$size        = apply_filters( 'bwfan_alter_product_image_size', $this->settings['imageSize'] );
							$tax_display = get_option( 'woocommerce_tax_display_cart' );
							foreach ( $data['items'] as $product ) {
								if ( ! self::$is_preview && ! $product['data'] instanceof WC_Product ) {
									continue;
								}
								if ( ! apply_filters( 'bwfan_cart_block_item_visible', true, $product ) ) {
									continue;
								}
								$product_price = ( 'excl' === $tax_display ) ? BWFAN_Common::get_line_subtotal( $product ) : BWFAN_Common::get_line_subtotal( $product ) + BWFAN_Common::get_line_subtotal_tax( $product );
								$subtotal      += $product_price;
								$tax           += $product['line_subtotal_tax'];
								$pid           = ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'];
								$image_url     = BWFAN_Common::get_product_image_url( $pid, $size );
								$sku           = self::$is_preview ? $product['data']->data['sku'] : $product['data']->get_sku();
								if ( empty( $image_url ) ) {
									$image_url = BWFCRM_Block_Editor::get_product_placeholder();
								}
								?>
                                <tr>
                                    <td class="cart-item list-layout" style="<?php echo $this->getRowStyle( $count ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                        <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width: 100%;">
                                            <tbody>
                                            <tr>
												<?php if ( $showProductImage ) { ?>
                                                    <td class="product-image" align="center" width="<?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="width: <?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;">
                                                        <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width: <?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;">
                                                            <tbody>
                                                            <tr>
                                                                <td align="center">
																	<?php if ( $cartItemLinkEnabled ) : ?>
                                                                        <a href="<?php echo $cartItemLink; //phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank">
                                                                            <img height="<?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" width="<?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" src="<?php echo $image_url; //phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="product" style="height: <?php echo $imgSize ?>px;width: <?php echo $imgSize ?>px;border-radius:8px;<?php echo $size !== 'thumbnail' ? 'object-fit:contain;' : '' ?>"/>
                                                                        </a>
																	<?php else : ?>
                                                                        <img height="<?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" width="<?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" src="<?php echo $image_url; //phpcs:ignore WordPress.Security.EscapeOutput ?>" alt="product" style="height: <?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;width: <?php echo $imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;border-radius:8px;<?php echo $size !== 'thumbnail' ? 'object-fit:contain;' : ''; //phpcs:ignore WordPress.Security.EscapeOutput ?>"/>
																	<?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            </tbody>
                                                        </table>
                                                    </td>
												<?php } ?>
                                                <td class="product-info" align="<?php echo is_rtl() ? "right" : "left" ?>" style="<?php echo is_rtl() ? ( 'padding-left:4px;width:65%;' . ( $showProductImage ? 'padding-right:10px; mso-padding-alt:0px 10px 0px 4px;' : 'mso-padding-alt:0px 0px 0px 4px;' ) ) : ( 'padding-right:4px;width:65%;' . ( $showProductImage ? 'padding-left:10px; mso-padding-alt:0px 4px 0px 10px;' : 'mso-padding-alt:0px 4px 0px 0px;' ) ) ?>">
													<?php if ( $this->is_section_enable( 'title' ) ) : ?>
                                                        <p class="product-title" style="<?php echo $this->getProductTitleStyle( '.product-info .product-title' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo self::$is_preview ? $product['data']->data['name'] : $product['data']->get_name(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                        </p>
													<?php endif; ?>
													<?php if ( $this->is_section_enable( 'sku' ) && $sku ) : ?>
                                                        <p class="product-sku" style="<?php echo $this->getProductSKUStyle( '.product-info .product-sku' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo $sku; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                        </p>
													<?php endif; ?>
													<?php if ( $this->is_section_enable( 'attrs' ) ) : ?>
														<?php if ( self::$is_preview ) { ?>
                                                            <p class="product-meta" style="<?php echo $this->getProductMetaStyle( '.product-info .product-meta' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
																<?php echo $product['data']->data['attr']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                            </p>
														<?php } else {
															$data_item = $this->bwf_get_formatted_cart_item_data( $product );
															if ( ! empty( $data_item ) ) {
																echo '<ul class="product-meta-ul">'; //phpcs:ignore WordPress.Security.EscapeOutput
																foreach ( $data_item as $meta_id => $meta ) {
																	$value = wp_kses_post( make_clickable( trim( $meta['display'] ) ) );
																	$label = wp_kses_post( $meta['key'] );
																	$html  = apply_filters( 'bwfan_display_cart_item_meta', $label . ': ' . $value, $meta )
																	?>
                                                                    <li class="product-meta" style="<?php echo $this->getProductMetaStyle( '.product-info .product-meta' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
																		<?php echo $html; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                    </li>
																	<?php
																}
																echo '</ul>'; //phpcs:ignore WordPress.Security.EscapeOutput
															}

														} ?>
													<?php endif; ?>
													<?php if ( $this->is_section_enable( 'qty' ) ) { ?>
                                                        <p class="product-qty" style="<?php echo $this->getProductQtyStyle( "product-qty" ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo esc_html( $this->settings['qtyLabel'] ) . '&nbsp;' . $product['quantity']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                        </p>
													<?php } ?>
                                                </td>
												<?php if ( $this->is_section_enable( 'qty' ) ) { ?>
                                                    <td class="product-qty-wrap" style="width:15%;" align="left">
                                                        <p class="product-qty" style="<?php echo $this->getProductQtyStyle( "product-qty" ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo esc_html( $this->settings['qtyLabel'] ) . '&nbsp;' . $product['quantity']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                        </p>
                                                    </td>
												<?php } ?>
												<?php if ( $showPrice ) { ?>
                                                    <td class="product-price-wrap" style="width:20%;" align="<?php echo is_rtl() ? "left" : "right" ?>">
                                                        <p class="product-price" style="<?php echo $this->getPriceStyle( 'p.product-price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
															<?php echo wc_price( $product_price, $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
															<?php if ( $suffix && wc_tax_enabled() ) : ?>
                                                                <small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput ?></small>
															<?php endif; ?>
                                                        </p>
                                                    </td>
												<?php } ?>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
								<?php
								$count ++;
							}
							?>
                            <tr>
                                <td align="<?php echo is_rtl() ? "right" : "left" ?>" class="order-meta" style="padding: 24px 0px; mso-padding-alt: 24px 0px;<?php echo $this->get_block_seperator_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $this->get_price_align(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table style="width: 100%;" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                        <tbody>
                                        <tr class="subtotal">
                                            <td style="width:70%;padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;">
                                                <p class="price-text" style="<?php echo $this->get_subtotal_text_style( '.subtotal .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $this->settings['subtotalLabel'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                            </td>
                                            <td align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:30%;padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;">
                                                <p class="price" style="<?php echo $this->get_subtotal_price_style( '.subtotal .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo wc_price( $subtotal, $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
													<?php if ( $suffix && wc_tax_enabled() ) : ?>
                                                        <small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
													<?php endif; ?>
                                                </p>
                                            </td>
                                        </tr>
										<?php

										$discount    = 0;
										$coupon_data = isset( $data['coupons'] ) ? maybe_unserialize( $data['coupons'] ) : [];
										if ( isset( $coupon_data ) && ! empty( $coupon_data ) ) {
											foreach ( $coupon_data as $coupon_name => $coupon ) {
												$key = 'discount_' . ( $tax_display === 'excl' ? 'excl_tax' : 'incl_tax' );
												$discount += floatval( $coupon[ $key ] ?? 0 );
												if ( isset( $coupon['discount_tax'] ) && floatval( $coupon['discount_tax'] ) > 0 ) {
													$tax -= $coupon['discount_tax'];
												}
											}
										}

										$orderPricingInfo = [];

										if ( self::$is_preview ) {
											$orderPricingInfo[ esc_html( $this->settings['shippingLabel'] ) ] = wc_price( '7.00', $args );
											$orderPricingInfo[ esc_html( $this->settings['taxesLabel'] ) ]    = wc_price( '5.00', $args );
											$orderPricingInfo[ esc_html( $this->settings['discountLabel'] ) ] = '-' . wc_price( 10.00, $args );

										} else {
											if ( isset( $data['shipping_total'] ) && floatval( $data['shipping_total'] ) > 0 ) {
												$orderPricingInfo[ esc_html( $this->settings['shippingLabel'] ) ] = wc_price( ( 'excl' === $tax_display ) ? $data['shipping_total'] : ( $data['shipping_total'] + ( $data['shipping_tax_total'] ?? 0 ) ), $args );
												if ( $suffix && wc_tax_enabled() ) {
													$orderPricingInfo[ esc_html( $this->settings['shippingLabel'] ) ] .= ' <small>' . $suffix . '</small>';
												}
												if ( isset( $data['shipping_tax_total'] ) ) {
													$tax += $data['shipping_tax_total'];
												}
											}

											if ( floatval( $discount ) > 0 ) {

												$orderPricingInfo[ esc_html( $this->settings['discountLabel'] ) ] = '-' . wc_price( $discount, $args );
											}

											if ( ! empty( $data['fees'] ) ) {
												$fee_data = maybe_unserialize( $data['fees'] );
												if ( is_array( $fee_data ) ) {
													foreach ( $fee_data as $fee ) {
														if ( ! isset( $fee->name ) || empty( $fee->total ) ) {
															continue;
														}


														$amount = ( $tax_display === 'excl' ) ? $fee->total : $fee->total + ( $fee->tax ?? 0 );
														if ( $tax_display && ! empty( $fee->tax ) ) {
															$tax += floatval( $fee->tax );
														}
														$orderPricingInfo[ esc_html( ! empty( $fee->name ) ? $fee->name : __( 'Fee', 'wp_marketing_automations-pro' ) ) ] = wc_price( $amount, $args );

													}
												}
											}

											if ( 'excl' === $tax_display && floatval( $tax ) > 0 ) {
												$orderPricingInfo[ esc_html( $this->settings['taxesLabel'] ) ] = wc_price( $tax, $args );
											}


										}
										$index = 0;

										foreach ( $orderPricingInfo as $itemKey => $priceItem ) {
											$padding = count( $orderPricingInfo ) - 1 !== $index ? 'padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;' : "";
											?>
                                            <tr class="prices">
                                                <td class="price-text-wrap" style="width:70%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="price-text" style="<?php echo $this->get_lineitem_text_style( '.prices .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $itemKey; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                </td>
                                                <td align="<?php echo is_rtl() ? "left" : "right" ?>" class="price-amount-wrap" style="width:30%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="price" style="<?php echo $this->get_order_price_style( '.prices .price' ) . ( 'Discount' === $itemKey ? bwf_css()->getColor( $this->settings, 'discountColor', 'desktop' ) : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $priceItem; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>

                                                </td>
                                            </tr>
											<?php
											$index ++;
										}
										?>
                                        </tbody>

                                    </table>

                                </td>
                            </tr>

                            <tr>
                                <td align="left" class="total-container" style="padding:16px 0px; mso-padding-alt:16px 0px;<?php echo $this->get_block_seperator_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo $this->get_price_align(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;">
                                        <tbody>
										<?php
										if ( isset( $data['total'] ) ) {
										?>
                                        <tr class="total">
                                            <td style="width:70%;">
                                                <p class="price-text" style="<?php echo $this->get_total_text_style( '.total .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo esc_html( $this->settings['totalLabel'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ( wc_tax_enabled() && $tax_display !== 'excl' ) : ?>
                                                        <small><?php printf( __( '(includes %s Tax)', 'woocommerce' ), wc_price( $tax ?? 0, $args ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></small>
													<?php endif; ?></p>
                                            </td>
                                            <td align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:30%;">
                                                <p class="price" style="<?php echo $this->get_total_price_style( '.total .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo wc_price( $data['total'], $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </p>
                                            </td>
                                        </tr>
                                        </tbody>
										<?php
										}
										?>

                                    </table>

                                </td>
                            </tr>
							<?php
						} else {
							$product  = array_values( $data['items'] )[0];
							$sku      = self::$is_preview ? $product['data']->data['sku'] : $product['data']->get_sku();
							$size     = apply_filters( 'bwfan_alter_product_image_size', $this->settings['imageSize'] );
							$imgWidth = isset( $this->settings['imageWidthPx']['desktop'] ) && false === $this->settings['imgAutoWidth']['desktop'] ? 'width: ' . $this->settings['imageWidthPx']['desktop'] . 'px;' : 'width:100%;';
							if ( $size !== 'thumbnail' ) {
								$imgWidth .= 'object-fit:contain;';
							}
							$pid       = ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'];
							$image_url = BWFAN_Common::get_product_image_url( $pid, $size );
							if ( empty( $image_url ) ) {
								$image_url = BWFCRM_Block_Editor::get_product_placeholder();
							}
							$img_attrs = [
								'src'    => $image_url,
								'alt'    => 'email-product-image',
								'style'  => $imgWidth,
								'height' => 'auto',
								'width'  => false === $this->settings['imgAutoWidth']['desktop'] && isset( $this->settings['imageWidthPx']['desktop'] ) ? $this->settings['imageWidthPx']['desktop'] : '100%'
							];

							$mStyle                 = 'max-width: 100%; width: 100% !important;';
							$mStyle                 .= bwf_css()->handleAutoWidth( $this->settings, 'imgAutoWidth', 'imgWidth', 'mobile', [], true, 'value', bwf_css()->handleAutoWidth( $this->settings, 'imgAutoWidth', 'imgWidth', 'desktop', [], true, 'value' ) );
							$this->responsive_style .= sprintf( '%1$s %2$s, %1$s %3$s  {%4$s}', $this->wrapper_selector, '.product-image img', '.product-image', $mStyle );

							$product_price = $product['line_subtotal'];

							if ( isset( $product['line_subtotal_tax'] ) && ! empty( $product['line_subtotal_tax'] ) ) {
								$product_price += $product['line_subtotal_tax'];
							}

							?>
							<?php if ( $showProductImage ) { ?>
                                <tr>
                                    <td class="product-image cart-product-layout-image" align="center" style="">
                                        <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width: 100%" width="100%">
                                            <tbody>
                                            <tr>
                                                <td align="center" class="product-image" style="<?php echo $imgWidth; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php if ( $cartItemLinkEnabled ) : ?>
                                                        <a href="<?php echo $cartItemLink; //phpcs:ignore WordPress.Security.EscapeOutput ?>" target="_blank">
                                                            <img <?php echo $this->arr_to_attr( $img_attrs ); //phpcs:ignore WordPress.Security.EscapeOutput ?> />
                                                        </a>
													<?php else : ?>
                                                        <img <?php echo $this->arr_to_attr( $img_attrs ); //phpcs:ignore WordPress.Security.EscapeOutput ?> />
													<?php endif; ?>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
							<?php } ?>
                            <tr>
                                <td class="product-info" align="<?php echo is_rtl() ? 'right' : 'left' ?>" style="<?php echo $this->getProductInfoStyle( '.product-layout.product-info' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <p class="product-title" style="padding: 12px 0 0;mso-padding-alt:12px 0 0;<?php echo $this->getProductTitleStyle( '.product-info .product-title', 'product' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
										<?php echo self::$is_preview ? $product['data']->data['name'] : $product['data']->get_name(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </p>
									<?php if ( $showSKU && ! empty( $sku ) ) { ?>
                                        <p class="product-sku" style="<?php echo $this->getProductSKUStyle( '.product-info .product-sku', 'product' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo $sku; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
									<?php } ?>
									<?php if ( $showDesc ) { ?>
                                        <p class="product-meta" style="<?php echo $this->getProductMetaStyle( '.product-info .product-meta', 'product' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo self::$is_preview ? $product['data']->data['short_description'] : $product['data']->get_short_description(); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </p>
									<?php } ?>
                                </td>
                            </tr>
                            <tr>
								<?php if ( $showPrice ) { ?>
                                    <td class="product-price" style="width:15%;" align="<?php echo is_rtl() ? "left" : "right" ?>">
                                        <p class="product-price" style="padding: 16px 0 0;mso-padding-alt: 16px 0 0 0;<?php echo $this->getPriceStyle( 'p.product-price', 'product' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php echo wc_price( $product['line_subtotal'], $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
											<?php if ( $suffix && wc_tax_enabled() ) : ?>
                                                <small><?php echo $suffix; //phpcs:ignore WordPress.Security.EscapeOutput ?></small>
											<?php endif; ?>
                                        </p>
                                    </td>
								<?php } ?>
                            </tr>

							<?php
						}
						?>
                    </table>
                </td>
            </tr>

            <style data-id='woofunnels'>
                .bwf-email-cart-items .product-info .product-qty {
                    display: none;
                    mso-hide: all;
                    max-height: 0px;
                    overflow: hidden;
                }

                @media only screen and (max-width: 768px) {
                    .bwf-email-cart-items .grid-wrap {
                        width: 100% !important;
                    }

                    .bwf-email-cart-items p {
                        font-size: 13px !important;
                    }

                    .bwf-email-cart-items .list-layout.cart-item td.product-qty-wrap {
                        display: none !important;
                        mso-hide: all !important;
                        max-height: 0px !important;
                        overflow: hidden !important;
                    }

                    .bwf-email-cart-items .list-layout.cart-item td.product-price {
                        width: 20% !important;
                    }

                <?php if( $this->settings['layout'] === 'list' && isset($this->settings['itemImgWidth']['mobile']['value']) && !empty($this->settings['itemImgWidth']['mobile']['value'])) { ?>
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .list-layout .product-image,
                                                                                                      <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .list-layout .product-image table,
                                                                                                      <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .list-layout .product-image img {
                                                                                                          width: <?php echo $this->settings['itemImgWidth']['mobile']['value']; //phpcs:ignore WordPress.Security.EscapeOutput ?>px !important;
                                                                                                          height: <?php echo $this->settings['itemImgWidth']['mobile']['value']; //phpcs:ignore WordPress.Security.EscapeOutput ?>px !important;
                                                                                                      }

                <?php } ?>
                    .bwf-email-cart-items .product-info .product-qty {
                        mso-hide: unset !important;
                        max-height: unset !important;
                        overflow: unset !important;
                        display: block !important;
                    }

                <?php echo $this->responsive_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>

                }
            </style>
			<?php
			$this->responsive_style = '';

			return BWFAN_Common::decode_merge_tags( ob_get_clean() );
		}

		public function is_section_enable( $section = '', $screen = 'desktop' ) {
			$section_enabled = isset( $this->settings['enabledContentOptions'][ $screen ] ) ? $this->settings['enabledContentOptions'][ $screen ] : [];
			if ( in_array( $section, $section_enabled ) ) {
				return true;
			}

			return false;
		}

		public function bwf_get_formatted_cart_item_data( $cart_item ) {
			$item_data = array();

			if ( isset( $cart_item['variation'] ) && is_array( $cart_item['variation'] ) ) {
				foreach ( $cart_item['variation'] as $name => $value ) {
					$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );
					if ( taxonomy_exists( $taxonomy ) ) {
						// If this is a term slug, get the term's nice name.
						$term = get_term_by( 'slug', $value, $taxonomy );
						if ( ! is_wp_error( $term ) && $term && $term->name ) {
							$value = $term->name;
						}
						$label = wc_attribute_label( $taxonomy );
					} else {
						// If this is a custom option slug, get the options name.
						$value = apply_filters( 'woocommerce_variation_option_name', $value, null, $taxonomy, $cart_item['data'] );
						$label = wc_attribute_label( str_replace( 'attribute_', '', $name ), $cart_item['data'] );
					}

					$item_data[] = array(
						'key'   => $label,
						'value' => $value,
					);
				}
			}

			// Filter item data to allow 3rd parties to add more to the array.
			try {
				$new_item_data = apply_filters( 'woocommerce_get_item_data', $item_data, $cart_item );
				if ( is_array( $new_item_data ) && ! is_wp_error( $new_item_data ) ) {
					$item_data = $new_item_data;
				}
			} catch ( Error|Exception $e ) {
				BWFAN_Common::log_test_data( 'woocommerce_get_item_data filter hook error. Error: ' . $e->getMessage() . ' Stack trace: ' . $e->getTraceAsString(), 'email-error', true );
			}

			// Format item data ready to display.
			foreach ( $item_data as $key => $data ) {
				// Set hidden to true to not display meta on cart.
				if ( ! empty( $data['hidden'] ) ) {
					unset( $item_data[ $key ] );
					continue;
				}
				$item_data[ $key ]['key']     = ! empty( $data['key'] ) ? $data['key'] : $data['name'];
				$item_data[ $key ]['display'] = ! empty( $data['display'] ) ? $data['display'] : $data['value'];
			}


			return $item_data;
		}

		public function get_cart_data() {
			if ( empty( self::$cart ) || self::$is_preview ) {
				return array(
					'items' => array(
						'1' => array(
							'product_id'        => 1,
							'quantity'          => 1,
							'line_total'        => '50.00',
							'line_subtotal_tax' => '0.00',
							'line_subtotal'     => '50.00',
							'coupons'           => '',
							'line_tax'          => '0',
							'data'              => (object) array(
								'data' => array(
									'sku'               => 'SKU-1234',
									'name'              => 'Here is the product title',
									'short_description' => 'Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries for previewing layouts and visual mockups.',
									'description'       => 'Lorem ipsum is placeholder text commonly used in the graphic, print, and publishing industries for previewing layouts and visual mockups.',
									'attr'              => 'Black, 32'
								)
							)
						),
					),
					'total' => 52
				);
			} else {
				return self::$cart;
			}
		}

		private function arr_to_attr( $arr = [] ) {
			if ( empty( $arr ) ) {
				return '';
			}
			$string = '';
			foreach ( $arr as $key => $value ) {
				$string .= $key . '="' . $value . '" ';
			}

			return trim( $string );
		}

		public function getRowStyle( $count ) {
			$dividerColor   = isset( $this->settings['dividerColor']['desktop'] ) ? $this->settings['dividerColor']['desktop'] : '';
			$dividerStyle   = isset( $this->settings['dividerStyle']['desktop'] ) ? $this->settings['dividerStyle']['desktop'] : '';
			$separatorStyle = $dividerStyle !== 'none' ? 'border-bottom: 1px ' . $dividerStyle . ' ' . $dividerColor . ';' : '';

			$borderTop = 'border-top: 1px ' . $dividerStyle . ' ' . $dividerColor . ';';
			$style     = 'width:100%;padding:20px 0px; mso-padding-alt: 20px 0px;';
			$style     .= ( $count === 0 ? $borderTop : '' );
			$style     .= $separatorStyle;

			return $style;
		}

		public function getProductTitleStyle( $classname, $layout = '' ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'desktop', false, $default_style['font-size'] );
			$style         .= bwf_css()->getColor( $this->settings, 'productTitleColor', 'desktop' );
			$style         .= bwf_css()->getBold( $this->settings, 'productTitleBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'productTitleItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $layout === 'product' ) {
				$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, 'text-align: left;' );
			}

			if ( $this->wrapper_selector ) {
				$mStyle = bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'mobile', true );
				$mStyle .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				if ( $layout === 'product' ) {
					$mStyle .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				}
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getProductMetaStyle( $classname, $layout = '' ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'productMetaFont', 'desktop', false, 'font-size: 14px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'productMetaColor', 'desktop', 'color', false, $layout === 'product' ? $default_style['color'] : 'color: #8c8f94;' );
			$style         .= bwf_css()->getBold( $this->settings, 'productMetaBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'productMetaItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $layout === 'product' ) {
				$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, 'text-align: left;' );
			}

			if ( $this->wrapper_selector ) {
				$mStyle = bwf_css()->getFontSize( $this->settings, 'productMetaFont', 'mobile', true );
				$mStyle .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				if ( $layout === 'product' ) {
					$mStyle .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				}
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getProductSKUStyle( $classname, $layout = '' ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'productSkuFont', 'desktop', false, 'font-size: 14px;' );
			$style         .= bwf_css()->getColor( $this->settings, 'productSkuColor', 'desktop', 'color', false, $layout === 'product' ? $default_style['color'] : 'color: #8c8f94;' );
			$style         .= bwf_css()->getBold( $this->settings, 'productSkuBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'productSkuItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $layout === 'product' ) {
				$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, 'text-align: left;' );
			}

			if ( $this->wrapper_selector ) {
				$mStyle = bwf_css()->getFontSize( $this->settings, 'productSkuFont', 'mobile', true );
				$mStyle .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				if ( $layout === 'product' ) {
					$mStyle .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				}
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getProductQtyStyle( $classname ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			$style         .= bwf_css()->getFontSize( $this->settings, 'qtyFont', 'desktop', false, $default_style['font-size'] );
			$style         .= bwf_css()->getColor( $this->settings, 'qtyColor', 'desktop' );
			$style         .= bwf_css()->getBold( $this->settings, 'qtyBold', 'desktop' );
			$style         .= bwf_css()->getItalic( $this->settings, 'qtyItalic', 'desktop' );
			$style         .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle = bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );

				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getPriceStyle( $classname, $layout = '' ) {
			$default_style = $this->get_default_style();
			$style         = $default_style['text-style'];
			if ( $layout === 'product' ) {
				$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop' );
			}
			$style .= bwf_css()->getColor( $this->settings, 'commonPriceColor', 'desktop' );
			$style .= bwf_css()->getBold( $this->settings, 'commonPriceBold', 'desktop' );
			$style .= bwf_css()->getItalic( $this->settings, 'commonPriceItalic', 'desktop' );
			$style .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );


			if ( $layout === 'product' ) {
				$style .= bwf_css()->textAlign( $this->settings, 'alignment', 'desktop', false, 'text-align: left;' );
				$style .= bwf_css()->getFontSize( $this->settings, 'commonPriceFont', 'desktop', false, '' );
				$style .= '';
			} else {
				$style .= bwf_css()->getFontSize( $this->settings, 'commonPriceFont', 'desktop', false, $default_style['font-size'] );
			}

			if ( $this->wrapper_selector ) {
				$mStyle = bwf_css()->getFontSize( $this->settings, 'commonPriceFont', 'mobile', true );
				$mStyle .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				if ( $layout === 'product' ) {
					$mStyle .= bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				}
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		public function getProductInfoStyle( $classname ) {
			$style = bwf_css()->textAlign( $this->settings, 'alignment', 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->textAlign( $this->settings, 'alignment', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Get seperator style
		 */
		private function get_block_seperator_style( $pos = 'bottom' ) {
			$dividerColor = isset( $this->settings['dividerColor']['desktop'] ) ? $this->settings['dividerColor']['desktop'] : '';
			$dividerStyle = isset( $this->settings['dividerStyle']['desktop'] ) ? $this->settings['dividerStyle']['desktop'] : '';

			if ( 'none' === $dividerStyle ) {
				return '';
			}

			return sprintf( 'border-%1$s: 1px %2$s %3$s;', $pos, $dividerStyle, $dividerColor );
		}


		private function get_total_text_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontSize( $this->settings, 'totalTextFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'totalTextColor', 'desktop' );
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getBold( $this->settings, 'totalTextBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'totalTextItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'totalTextFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		private function get_total_price_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontSize( $this->settings, 'totalPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'totalPriceColor', 'desktop' );
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getBold( $this->settings, 'totalPriceBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'totalPriceItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'totalPriceFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		private function get_subtotal_text_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontStyles( $this->settings, 'subTotalTextFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'subTotalTextColor', 'desktop' );
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getBold( $this->settings, 'subTotalTextBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'subTotalTextItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'subTotalTextFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_subtotal_price_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontStyles( $this->settings, 'subTotalPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'subTotalPriceColor', 'desktop' );
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getBold( $this->settings, 'subTotalPriceBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'subTotalPriceItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'subTotalPriceFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_lineitem_text_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontStyles( $this->settings, 'pricesMetaFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'pricesMetaColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'pricesMetaBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'pricesMetaItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			$style       .= $defaulStyle['text-style'];

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'pricesMetaFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_order_price_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= bwf_css()->getFontStyles( $this->settings, 'commonPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'commonPriceColor', 'desktop' );
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getBold( $this->settings, 'commonPriceBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'commonPriceItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'commonPriceFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}


		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;text-align: center;';
			$style .= bwf_css()->getPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' );
			$style .= bwf_css()->getVisibilityCss( $this->settings, 'desktop' );

			if ( $this->wrapper_selector ) {
				$mStyle                 = bwf_css()->getPadding( $this->settings, 'padding', 'mobile', true );
				$mStyle                 .= bwf_css()->getVisibilityCss( $this->settings, 'mobile' );
				$this->responsive_style .= sprintf( '%1$s {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		private function get_price_align( $classname = 'order-meta' ) {
			$style = 'width:100%;';
			$style .= bwf_css()->textAlign( $this->settings, 'priceAlign', 'desktop', false, is_rtl() ? 'text-align:right;' : false );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->textAlign( $this->settings, 'priceAlign', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s .order-meta > table, %1$s .total-container > table {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}


		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'  => 'font-size:16px;',
				'text-style' => 'margin:0;line-height: 1.5;' . bwf_css()->getFontFamily( $this->settings ),
				'color'      => bwf_css()->getColor( $this->settings, 'color', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}
	}
}
BWFBE_WC_Cart_Items_Template::get_instance();
