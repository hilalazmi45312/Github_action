<?php

if ( ! class_exists( 'BWFBE_Order_Summary_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_Order_Summary_Template {

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

		public static $order;

		public static $is_preview;

		public static $wc_transactional_recipient = '';
		public static $is_wc_transactional = false;
		private $imgSize = null;
		public static $date_format = '';

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_order_summary', [ $this, 'order_summary_block' ] );
		}

		public function order_summary_block( $atts, $content ) {
			$mergetagdata     = BWFAN_Merge_Tag_Loader::get_data();
			self::$is_preview = isset( $mergetagdata['is_preview'] ) && $mergetagdata['is_preview'];
			self::$order      = ! self::$is_preview && ! empty( $mergetagdata['order_id'] ) ? wc_get_order( $mergetagdata['order_id'] ) : '';
			if ( ! self::$is_preview && empty( self::$order ) ) {
				return '';
			}
			self::$is_wc_transactional        = isset( $mergetagdata['bwfan_transactional_mail'] ) ? $mergetagdata['bwfan_transactional_mail'] : false;
			self::$wc_transactional_recipient = isset( $mergetagdata['bwfan_wc_transactional_recipient'] ) ? $mergetagdata['bwfan_wc_transactional_recipient'] : '';
			self::$date_format                = get_option( 'date_format' ) ?? 'd M, Y';

			do_action( 'bwfan_email_setup_locale', BWFAN_Common::get_order_language( self::$order ) );

			ob_start();

			$attributes = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );

			$defaults = [
				'orderInfoAlign'        => [ 'desktop' => 'center' ],
				'padding'               => [
					'desktop' => [
						'top'    => '16',
						'right'  => '16',
						'bottom' => '16',
						'left'   => '16',
						'unit'   => 'px'
					],
					'mobile'  => [
						'top'    => '8',
						'right'  => '8',
						'bottom' => '8',
						'left'   => '8',
						'unit'   => 'px'
					]
				],
				'dividerStyle'          => [ 'desktop' => 'solid' ],
				'uniqueID'              => '',
				'enabledContentOptions' => [
					'desktop' => [ 'orderDate', 'orderId', 'payment', 'image', 'qty' ]
				],
				'orderInfoLabelFont'    => [
					'desktop' => [
						'size' => '12'
					],
				],
				'orderInfoTextFont'     => [
					'desktop' => [
						'size' => '13'
					],
				],
				'commonPriceFont'       => [
					'desktop' => [
						'size' => '14'
					],
					'mobile'  => [
						'size' => '13'
					],
				],
				'productTitleFont'      => [
					'desktop' => [
						'size' => '13'
					],
					'mobile'  => [
						'size' => '12'
					],
				],
				'qtyFont'               => [
					'desktop' => [
						'size' => '14'
					],
					'mobile'  => [
						'size' => '13'
					],
				],
				'pricesMetaFont'        => [
					'desktop' => [
						'size' => '14'
					],
					'mobile'  => [
						'size' => '13'
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
						'size' => '14'
					],
					'mobile'  => [
						'size' => '13'
					],
				],
				'subTotalTextFont'      => [
					'desktop' => [
						'size' => '14'
					],
					'mobile'  => [
						'size' => '13'
					],
				],
				'totalPriceFont'        => [
					'desktop' => [
						'size' => '16'
					],
					'mobile'  => [
						'size' => '13'
					],
				],
				'totalTextFont'         => [
					'desktop' => [
						'size' => '16'
					],
					'mobile'  => [
						'size' => '13'
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
				'scTitleFont'           => [
					'desktop' => [
						'size' => '18px',
						'unit' => 'px'
					]
				],
				'scLabelFont'           => [
					'desktop' => [
						'size' => '12',
						'unit' => 'px'
					]
				],
				'scLabelColor'          => [ 'desktop' => '#82838e' ],
				'scTitle'               => '<strong>' . __( 'Subscription Information', 'wp-marketing-automations-pro' ) . '</strong>',
				'scIdLabel'             => '<strong>' . __( 'ID', 'wp-marketing-automations-pro' ) . '</strong>',
				'scStartDateLabel'      => '<strong>' . __( 'Start Date', 'wp-marketing-automations-pro' ) . '</strong>',
				'scEndDateLabel'        => '<strong>' . __( 'End Date', 'wp-marketing-automations-pro' ) . '</strong>',
				'scAmountLabel'         => '<strong>' . __( 'Recurring Total', 'wp-marketing-automations-pro' ) . '</strong>',
				'scNextPaymentLabel'    => '<strong>' . __( 'Next Payment', 'wp-marketing-automations-pro' ) . '</strong>',
				'scIdFont'              => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '13',
						'unit' => 'px'
					]
				],
				'scStartDateFont'       => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '13',
						'unit' => 'px'
					]
				],
				'scEndDateFont'         => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '13',
						'unit' => 'px'
					]
				],
				'scAmountFont'          => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '13',
						'unit' => 'px'
					]
				],
				'scNextPaymentFont'     => [
					'desktop' => [
						'size' => '14',
						'unit' => 'px'
					],
					'mobile'  => [
						'size' => '13',
						'unit' => 'px'
					]
				],
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

			$this->settings['classname'] = [
				'bwfbe-email-order-summary',
				'bwfbe-email-order-summary-' . ( $attributes['uniqueID'] ?? '' )
			];

			$this->wrapper_selector = '.bwfbe-email-order-summary.bwfbe-email-order-summary-' . $this->settings['uniqueID'];

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';


			$this->imgSize = isset( $this->settings['itemImgWidth']['desktop']['value'] ) ? $this->settings['itemImgWidth']['desktop']['value'] : '48';

			if ( self::$order instanceof WC_Order ) {
				$this->order_summary_html();
			} else {
				$this->default_order_summary_html();
			}

			?>
            <style data-id='woofunnels'>
                .bwfbe-email-order-summary .product-info .product-qty {
                    display: none;
                    mso-hide: all;
                    max-height: 0px;
                    overflow: hidden;
                }

                .bwfbe-email-order-summary .product-info ul.product-meta li div,
                .bwfbe-email-order-summary .product-info ul.product-meta li p {
                    display: inline-block;
                }

                .bwfbe-email-order-summary .product-info ul.product-meta li {
                    margin: 0;
                }

                .bwfbe-email-order-summary .product-info .bwf-item-meta-extra * {
                    font-size: 13px;
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra {
                <?php echo bwf_css()->textAlign( $this->settings, 'orderInfoAlign', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?> padding: 8px 0;
                <?php echo bwf_css()->getFontFamily( $this->settings ); //phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo bwf_css()->getColor( $this->settings, 'orderInfoTextColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo bwf_css()->getFontSize( $this->settings, 'orderInfoLabelFont', 'desktop', false, 'font-size:16px;' ); //phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra li {
                    text-align: left;
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra table.woocommerce-table.woocommerce-table--order-details {
                    border: 0 !important;
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra table.woocommerce-table.woocommerce-table--order-details * {
                <?php echo bwf_css()->getColor( $this->settings, 'orderInfoLabelColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo bwf_css()->getFontSize( $this->settings, 'orderInfoLabelFont', 'desktop', false, 'font-size:16px;' ); //phpcs:ignore WordPress.Security.EscapeOutput ?> <?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra table.woocommerce-table.woocommerce-table--order-details tbody tr.woocommerce-table__line-item.order_item td {
                    padding: 10px 0 !important;
                    mso-padding-alt: 10px 0 !important;
                    text-align: center;
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra table.woocommerce-table.woocommerce-table--order-details tbody tr.woocommerce-table__line-item.order_item td:first-child {
                    text-align: left;
                }

                .bwfbe-email-order-summary .bwf-item-meta-extra table.woocommerce-table.woocommerce-table--order-details tbody tr.woocommerce-table__line-item.order_item td:last-child {
                    text-align: right;
                }

                @media only screen and (max-width: 768px) {
                    .bwfbe-email-order-summary .bwf-item-meta-extra {
                    <?php echo bwf_css()->textAlign( $this->settings, 'orderInfoAlign', 'mobile' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                    }

                    .bwfbe-email-order-summary .order_item td.product-qty-wrap {
                        display: none !important;
                        mso-hide: all !important;
                        max-height: 0px !important;
                        overflow: hidden !important;
                    }

                    .bwfbe-email-order-summary .order_item td.product-price {
                        width: 17% !important;
                    }

                    .bwfbe-email-order-summary .order-meta > table > tbody > tr > td:first-child, .bwfbe-email-order-summary .total > table > tbody > tr > td:first-child {
                        width: 60% !important;
                    }

                    .bwfbe-email-order-summary .order-meta > table > tbody > tr > td:nth-child(2), .bwfbe-email-order-summary .total > table > tbody > tr > td:nth-child(2) {
                        width: 40% !important;
                    }

                <?php if(isset($this->settings['itemImgWidth']['mobile']['value']) && !empty($this->settings['itemImgWidth']['mobile']['value'])) { ?>
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .product-image,
                                                                                                      <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .product-image table,
                                                                                                      <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .product-image img {
                                                                                                          width: <?php echo $this->settings['itemImgWidth']['mobile']['value']; //phpcs:ignore WordPress.Security.EscapeOutput ?>px !important;
                                                                                                          height: <?php echo $this->settings['itemImgWidth']['mobile']['value']; //phpcs:ignore WordPress.Security.EscapeOutput ?>px !important;
                                                                                                      }

                <?php } ?>
                    .bwfbe-email-order-summary .product-info .product-qty {
                        mso-hide: all !important;
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

		public function get_shipping_label() {
			$tax_display    = get_option( 'woocommerce_tax_display_cart' );
			$shipping_label = $this->settings['shippingLabel'];
			if ( 0 < abs( (float) self::$order->get_shipping_total() ) ) {
				if ( 'excl' === $tax_display ) {
					if ( (float) self::$order->get_shipping_tax() > 0 && self::$order->get_prices_include_tax() ) {
						$shipping_label .= '&nbsp;<small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				} else {
					if ( (float) self::$order->get_shipping_tax() > 0 && ! self::$order->get_prices_include_tax() ) {
						$shipping_label .= '&nbsp;<small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				}
				/* translators: %s: method */
				$shipping_label .= '&nbsp;<small class="shipped_via">' . sprintf( __( 'via %s', 'woocommerce' ), self::$order->get_shipping_method() ) . '</small>';
			} elseif ( self::$order->get_shipping_method() ) {
				$shipping_label .= '&nbsp;<small class="shipped_via">' . sprintf( __( 'via %s', 'woocommerce' ), self::$order->get_shipping_method() ) . '</small>';
			} else {
				$shipping_label .= '&nbsp;' . __( 'Free!', 'woocommerce' );
			}

			return $shipping_label;
		}

		public function get_shipping_total( $currency ) {
			$shipping_total = self::$order->get_shipping_total();
			if ( $shipping_total > 0 ) {
				$tax_display = get_option( 'woocommerce_tax_display_cart' );
				if ( 'excl' === $tax_display ) {
					return wc_price( $shipping_total, $currency );
				} else {
					return wc_price( $shipping_total + self::$order->get_shipping_tax(), $currency );
				}
			}

			return '';
		}

		/**
		 * Check if is transactional section is enabled.
		 *
		 * @return mixed
		 */
		public function is_transactional_send_to_admin() {
			return self::$is_wc_transactional && self::$wc_transactional_recipient === 'admin';
		}

		public function get_order_total_data( $currency ) {
			$formatted_total = wc_price( self::$order->get_total(), $currency );
			$tax_display     = get_option( 'woocommerce_tax_display_cart' );
			$tax_string      = '';

			$order_total    = self::$order->get_total();
			$total_refunded = self::$order->get_total_refunded();

			// Tax for inclusive prices.
			if ( wc_tax_enabled() && 'incl' === $tax_display ) {
				$tax_string_array = array();
				$tax_totals       = self::$order->get_tax_totals();

				if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
					foreach ( $tax_totals as $code => $tax ) {
						$tax_amount         = $tax->formatted_amount;
						$tax_string_array[] = sprintf( '%s %s', $tax_amount, $tax->label );
					}
				} elseif ( ! empty( $tax_totals ) ) {
					$tax_amount         = self::$order->get_total_tax();
					$tax_string_array[] = sprintf( '%s %s', wc_price( $tax_amount, $currency ), WC()->countries->tax_or_vat() );
				}

				if ( ! empty( $tax_string_array ) ) {
					$tax_string = ' <small class="includes_tax">' . sprintf( __( '(includes %s)', 'woocommerce' ), implode( ', ', $tax_string_array ) ) . '</small>';
				}
			}

			if ( $total_refunded ) {
				$formatted_total = '<del aria-hidden="true">' . wp_strip_all_tags( $formatted_total ) . '</del> <ins>' . wc_price( $order_total - $total_refunded, $currency ) . $tax_string . '</ins>';
			} else {
				$formatted_total .= $tax_string;
			}

			return apply_filters( 'woocommerce_get_formatted_order_total', $formatted_total, self::$order, $tax_display, true );
		}

		public function order_summary_html() {
			$showOrderInfo = $this->is_section_enable( 'orderDate' ) || $this->is_section_enable( 'orderId' ) || $this->is_section_enable( 'payment' );
			$order_data    = self::$order->get_data();
			$currency      = array( 'currency' => self::$order->get_currency() );
			remove_all_filters( 'woocommerce_currency_symbol' );
			?>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style() ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%">
                        <tbody>
						<?php
						$extra_data = $this->get_hook_content( 'woocommerce_email_before_order_table', [ self::$order, $this->is_transactional_send_to_admin(), false, '' ] );
						if ( ! empty( $extra_data ) ) {
							?>
                            <tr>
                                <td class='bwf-item-meta-extra'>
									<?php echo $extra_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
                            </tr>
							<?php
						}
						?>
						<?php if ( $showOrderInfo ) : ?>
                            <tr>
                                <td class="order-info" style="<?php echo $this->get_order_info_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table className="order-info-container" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="<?php echo $this->get_order_info_container_style( '.order-info > table' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                        <tbody>
                                        <tr>
											<?php
											$orderInfoItems = [
												[ 'orderDate', 'orderDateLabel', __( 'Order Date', 'woocommerce' ), date_i18n( self::$date_format ) ],
												[ 'orderId', 'orderIdLabel', __( 'Order Number', 'woocommerce' ), '#ABCD1234' ],
												[ 'payment', 'paymentLabel', __( 'Payment', 'woocommerce' ), 'VISA-5902' ]
											];

											$totalInfoItems = array_filter( $orderInfoItems, function ( $orderInfoItem ) {
												return $this->is_section_enable( $orderInfoItem[0] );
											} );
											$width          = count( $totalInfoItems ) === 3 ? '33%' : ( count( $totalInfoItems ) === 2 ? '50%' : '100%' );
											$index          = 0;
											foreach ( $totalInfoItems as $orderInfoItem ) {

												$itemLabel = isset( $this->settings[ $orderInfoItem[1] ] ) && $this->settings[ $orderInfoItem[1] ] !== '' ? $this->settings[ $orderInfoItem[1] ] : $orderInfoItem[2];
												?>
                                                <td style="vertical-align:top;<?php echo 'width:' . $width . ';' . ( $index !== count( $totalInfoItems ) - 1 ? 'padding: 0 4px 0 0;mso-padding-alt: 0 4px 0 0;' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="order-info-label" style="<?php echo $this->get_order_info_label_style( '.order-info-label' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $itemLabel; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                    <p class="order-info-text" style="<?php echo $this->get_order_info_text_style( '.order-info-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php
														switch ( $orderInfoItem[1] ) {
															case 'orderDateLabel':
																echo date_i18n( self::$date_format, $order_data['date_created']->getTimestamp() ); //phpcs:ignore WordPress.Security.EscapeOutput
																break;
															case 'orderIdLabel':
																echo '#' . ( isset( $order_data['number'] ) ? $order_data['number'] : $order_data['id'] ); //phpcs:ignore WordPress.Security.EscapeOutput
																break;
															case 'paymentLabel':
																echo isset( $order_data['payment_method_title'] ) ? $order_data['payment_method_title'] : ''; //phpcs:ignore WordPress.Security.EscapeOutput
																break;
														}
														?>
                                                    </p>

                                                </td>
												<?php
												$index ++;
											}
											?>

                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
						<?php endif; ?>
						<?php $this->order_summary_items(); ?>

                        <tr>
                            <td align="<?php echo is_rtl() ? "right" : "left" ?>" class="order-meta" style="padding: 25px 0px; mso-padding-alt: 25px 0px;<?php echo $this->get_block_seperator_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                <table style="<?php echo $this->get_order_meta_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                    <tbody>
                                    <tr class="subtotal">
                                        <td class="subtotal-text" style="width:65%;padding: 0 0 8px; mso-padding-alt: 0 0 8px;">
                                            <p class="price-text" style="<?php echo $this->get_subtotal_text_style( '.subtotal .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $this->settings['subtotalLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                        <td align="<?php echo is_rtl() ? "left" : "right" ?>" class="subtotal-price" style="width:30%;padding: 0 0 5px; mso-padding-alt: 0 0 5px;">
                                            <p class="price" style="<?php echo $this->get_subtotal_price_style( '.subtotal .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
												<?php echo self::$order->get_subtotal_to_display( false ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                            </p>
                                        </td>
                                    </tr>
									<?php
									$orderPricingInfo = [];
									if ( self::$order->get_shipping_method() ) {
										array_push( $orderPricingInfo, [
											$this->get_shipping_label(),
											$this->get_shipping_total( $currency )
										] );
									}
									if ( self::$order->get_total_discount() > 0 ) {
										array_push( $orderPricingInfo, [ $this->settings['discountLabel'], '-' . self::$order->get_discount_to_display() ] );
									}
									if ( self::$order->get_total_fees() > 0 ) {
										$fee_data = self::$order->get_fees();
										foreach ( $fee_data as $fee ) {
											array_push( $orderPricingInfo, [
												$fee['name'],
												wc_price( ( wc_tax_enabled() && 'excl' !== get_option( 'woocommerce_tax_display_cart' ) ) ? ( $fee['total'] + $fee['total_tax'] ) : $fee['total'], $currency )
											] );
										}
									}
									if ( 'excl' === get_option( 'woocommerce_tax_display_cart' ) && wc_tax_enabled() ) {
										$tax_totals = self::$order->get_tax_totals();
										$tax_amount = 0;
										foreach ( $tax_totals as $code => $tax ) {
											$tax_amount += $tax->amount;
										}
										if ( $tax_amount > 0 ) {
											array_push( $orderPricingInfo, [
												$this->settings['taxesLabel'],
												wc_price( $tax_amount, $currency )
											] );
										}
									}
									$index = 0;
									foreach ( $orderPricingInfo as $priceItem ) {
										$padding = count( $orderPricingInfo ) - 1 !== $index ? 'padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;' : "";
										?>
                                        <tr class="prices">
                                            <td class="price-text-wrap" style="width:65%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="price-text" style="<?php echo $this->get_lineitem_text_style( '.prices .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo $priceItem[0]; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </p>
                                            </td>
                                            <td class="price-wrap" align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:30%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="price" style="<?php echo $this->get_order_price_style( '.prices .price' ) . ( 'Discount' === $priceItem[0] ? bwf_css()->getColor( $this->settings, 'discountColor', 'desktop' ) : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo $priceItem[1]; //phpcs:ignore WordPress.Security.EscapeOutput ?>
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

						<?php $orderExtraData = apply_filters( 'woocommerce_get_order_item_totals', [], self::$order, get_option( 'woocommerce_tax_display_cart' ) ); ?>

                        <tr>
                            <td class="total" style="<?php echo( count( $orderExtraData ) > 0 ? 'padding: 16px 0px; mso-padding-alt: 16px 0px;' : 'padding: 16px 0px 0px; mso-padding-alt: 16px 0px 0px;' ) ?>">
                                <table align="<?php echo is_rtl() ? 'right' : 'left' ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;<?php echo $this->get_order_meta_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <tbody>
                                    <tr class="total">
                                        <td class="total-text-wrap" style="width:60%;">
                                            <p class="price-text" style="<?php echo $this->get_total_text_style( '.total .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $this->settings['totalLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                        <td class="total-price-wrap" align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:40%;">
                                            <p class="price" style="<?php echo $this->get_total_price_style( '.total .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
												<?php
												echo $this->get_order_total_data( $currency ); //phpcs:ignore WordPress.Security.EscapeOutput
												?>
                                            </p>
                                        </td>
                                    </tr>
                                    </tbody>

                                </table>

                            </td>
                        </tr>
						<?php

						if ( ! empty( $orderExtraData ) && is_array( $orderExtraData ) ) {
							?>
                            <tr>
                                <td align="<?php echo is_rtl() ? "right" : "left" ?>" class="order-meta" style="padding: 15px 0px; mso-padding-alt: 25px 0px;<?php echo $this->get_block_seperator_style() . $this->get_block_seperator_style( 'top' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table style="<?php echo $this->get_order_meta_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                        <tbody>
										<?php
										$index = 0;
										foreach ( $orderExtraData as $key => $data ) {
											if ( $index === 0 ) {
												$padding = 'padding: 8px 0px 8px 0px; mso-padding-alt: 8px 0px 8px 0px;';
											} else {
												$padding = count( $orderExtraData ) - 1 !== $index ? 'padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;' : "";
											}
											if ( empty( $data['value'] ) || empty( $data['label'] ) ) {
												continue;
											}
											?>
                                            <tr class="prices">
                                                <td class="price-text-wrap" style="width:70%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="price-text" style="<?php echo $this->get_lineitem_text_style( '.prices .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $data['label']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                </td>
                                                <td class="price-wrap" align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:30%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="price" style="<?php echo $this->get_order_price_style( '.prices .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $data['value']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                </td>
                                            </tr>
											<?php
										}
										?>
                                        </tbody>

                                    </table>

                                </td>
                            </tr>
							<?php
						}
						?>
						<?php $this->subscription_html( $currency ) ?>
						<?php
						$extra_data = $this->get_hook_content( 'woocommerce_email_after_order_table', [ self::$order, $this->is_transactional_send_to_admin(), false, '' ] );
						if ( ! empty( $extra_data ) ) {
							?>
                            <tr>
                                <td class='bwf-item-meta-extra'>
									<?php echo $extra_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
                            </tr>
							<?php
						}
						?>
                        </tbody>
                    </table>
                </td>
            </tr>
			<?php
		}

		/**
		 * Get the action data to set in mail
		 *
		 * @param $hook
		 * @param $args
		 *
		 * @return false|string
		 */
		function get_hook_content( $hook, $args ) {

			// If preview is enabled or the hook is not disabled, no content to return.
			if ( self::$is_preview || apply_filters( 'bwfan_disable_order_hooks', false, $hook, $args ) ) {
				return '';
			}

			ob_start();
			do_action_ref_array( $hook, $args );

			return ob_get_clean();
		}

		/**
		 * Return the item metadata
		 *
		 * @param $item
		 *
		 * @return string
		 */
		public function get_block_item_meta( $item ) {
			$strings = [];
			foreach ( $item->get_all_formatted_meta_data() as $meta_id => $meta ) {
				$separator = substr( $meta->display_key, - 1 ) === ':' ? '' : ': ';
				$strings[] = '<li><strong>' . $meta->display_key . $separator . ' </strong> ' . wp_kses_post( $meta->display_value ) . '</li>';
			}
			ob_start();
			if ( ! empty( $strings ) ) {
				echo implode( '', $strings ); //phpcs:ignore WordPress.Security.EscapeOutput
			}

			return ob_get_clean();
		}

		public function get_block_item_sku( $item ) {
			$sku = $item->get_product()->get_sku();
			if ( ! empty( $sku ) ) {
				return $sku;
			}

			return '';
		}

		public function order_summary_items() {
			$order_items = self::$order->get_items();
			$show_image  = $this->is_section_enable( 'image' );
			$size        = apply_filters( 'bwfan_alter_product_image_size', $this->settings['imageSize'] );
			foreach ( $order_items as $item_id => $item ) :
				$product = $item->get_product();

				if ( ! apply_filters( 'bwfan_order_item_visible', true, $item ) ) {
					continue;
				}

				$image_url = '';
				if ( is_object( $product ) ) {
					$image_url = BWFAN_Common::get_product_image_url( $product, $size );
				}

				if ( empty( $image_url ) ) {
					$image_url = BWFCRM_Block_Editor::get_product_placeholder();
				}
				$image = '<img src="' . esc_url( $image_url ) . '" height="' . $this->imgSize . '" width="' . $this->imgSize . '" style="border-radius:8px;height:' . $this->imgSize . 'px;width:' . $this->imgSize . 'px;' . ( $size !== 'thumbnail' ? 'object-fit:contain;' : '' ) . '" />';
				?>
                <tr>
                    <td class="<?php echo esc_attr( apply_filters( 'bwfan_order_item_class', 'order_item', $item, self::$order ) ); ?>" style="<?php echo $this->get_item_wrap_style(); //phpcs:ignore WordPress.Security.EscapeOutput
					?>">
                        <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput
						?> style="width: 100%">
                            <tbody>
                            <tr>
								<?php if ( $show_image ) : ?>
                                    <td class="product-image" align="center" width="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="width:<?php echo $this->imgSize ?>px">
                                        <table style="width:<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                            <tr>
                                                <td align="center" style="border: 0;border-radius:8px;" width="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" height="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo wp_kses_post( apply_filters( 'bwfan_order_item_thumbnail', $image, $item ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
								<?php endif; ?>

								<?php
								$item_meta_style     = 'padding-right:4px;width:auto;' . ( $show_image ? 'padding-left:10px; mso-padding-alt:0px 4px 0px 10px;' : 'mso-padding-alt:0px 4px 0px 0px;' );
								$item_meta_style_rtl = 'padding-left:4px;width:auto;' . ( $show_image ? 'padding-right:10px; mso-padding-alt:0px 10px 0px 4px;' : 'mso-padding-alt:0px 0px 0px 4px;' );
								?>
                                <td align="<?php echo is_rtl() ? 'right' : 'left' ?>" class="product-info" style="<?php echo is_rtl() ? $item_meta_style_rtl : $item_meta_style; //phpcs:ignore WordPress.Security.EscapeOutput
								?>">
                                    <p class="product-title" style="<?php echo $this->get_item_title_style( '.product-info .product-title' ); //phpcs:ignore WordPress.Security.EscapeOutput
									?>">
										<?php
										echo wp_kses_post( apply_filters( 'bwfan_order_item_name', $item->get_name(), $item, false ) ); //phpcs:ignore WordPress.Security.EscapeOutput
										?>
                                    </p>
									<?php
									// allow other plugins to add additional product information here at item start.
									$extra_data = $this->get_hook_content( 'woocommerce_order_item_meta_start', [ $item_id, $item, self::$order, false ] );
									if ( ! empty( $extra_data ) ) {
										?>
                                        <span class='bwf-item-meta-extra' style="<?php echo $this->get_item_meta_style( '.product-info .product-meta ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>text-align: left;">
											<?php echo $extra_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </span>
										<?php
									}

									if ( $this->is_section_enable( 'sku' ) ) {
										$sku_data = $this->get_block_item_sku( $item );

										if ( ! empty( $sku_data ) ) {
											?>
                                            <p class='product-sku' style="<?php echo $this->get_item_sku_style( '.product-info .product-sku ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
												<?php echo $sku_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                            </p>
											<?php
										}
									}

									$meta_data = $this->get_block_item_meta( $item );

									if ( ! empty( $meta_data ) ) {
										?>
                                        <ul class='product-meta' style="<?php echo $this->get_item_meta_style( '.product-info .product-meta ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>padding-left:20px;">
											<?php echo $meta_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </ul>
										<?php
									}


									// Handle extra metadata from other plugins at end.
									$extra_data = $this->get_hook_content( 'woocommerce_order_item_meta_end', [ $item_id, $item, self::$order, false ] );
									if ( ! empty( $extra_data ) ) {
										?>
                                        <span class='bwf-item-meta-extra' style="<?php echo $this->get_item_meta_style( '.product-info .product-meta ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>text-align: left;">
											<?php echo $extra_data; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                        </span>
										<?php
									}
									if ( $this->is_section_enable( 'qty' ) ) : ?>
                                        <p class="product-qty" style="<?php echo $this->get_item_qty_style( '.product-qty' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php
											echo $this->settings['qtyLabel'] . ' '; //phpcs:ignore WordPress.Security.EscapeOutput
											$qty = $item->get_quantity();

											$refunded_qty = self::$order->get_qty_refunded_for_item( $item_id );

											if ( $refunded_qty ) {
												$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * - 1 ) ) . '</ins>';
											} else {
												$qty_display = esc_html( $qty );
											}
											echo wp_kses_post( apply_filters( 'bwfan_email_order_item_quantity', $qty_display, $item ) );
											?>
                                        </p>
									<?php endif; ?>
                                </td>
								<?php if ( $this->is_section_enable( 'qty' ) ) : ?>
                                    <td class="product-qty-wrap" style="width:15%;">
                                        <p style="<?php echo $this->get_item_qty_style( '.product-qty' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
											<?php
											echo $this->settings['qtyLabel'] . ' '; //phpcs:ignore WordPress.Security.EscapeOutput
											$qty = $item->get_quantity();

											$refunded_qty = self::$order->get_qty_refunded_for_item( $item_id );

											if ( $refunded_qty ) {
												$qty_display = '<del>' . esc_html( $qty ) . '</del> <ins>' . esc_html( $qty - ( $refunded_qty * - 1 ) ) . '</ins>';
											} else {
												$qty_display = esc_html( $qty );
											}
											echo wp_kses_post( apply_filters( 'bwfan_email_order_item_quantity', $qty_display, $item ) );
											?>
                                        </p>
                                    </td>
								<?php endif; ?>
                                <td class="product-price" style="width:20%;" align="<?php echo is_rtl() ? "left" : "right" ?>">
                                    <p style="<?php echo $this->get_order_price_style( '.product-price p' ); //phpcs:ignore WordPress.Security.EscapeOutput
									?>">
										<?php echo wp_kses_post( self::$order->get_formatted_line_subtotal( $item ) ); //phpcs:ignore WordPress.Security.EscapeOutput
										?>
                                    </p>
                                </td>
                            </tr>

                            </tbody>
                        </table>
                    </td>
                </tr>

			<?php endforeach;

		}

		public function default_order_summary_html() {

			$showOrderInfo    = $this->is_section_enable( 'orderDate' ) || $this->is_section_enable( 'orderId' ) || $this->is_section_enable( 'payment' );
			$showProductImage = $this->is_section_enable( 'image' );
			$dummy_product    = [
				[
					"id"        => 1,
					"title"     => __( 'Here is the product title', 'wp-marketing-automations-pro' ),
					"price"     => wc_price( '50' ),
					"meta_data" => __( 'Black, 32', 'wp-marketing-automations-pro' ),
					"qty"       => 1,
					"sku"       => 'SKU-1234',
				],
			];
			?>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="<?php echo $this->get_block_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%">
                        <tbody>
						<?php if ( $showOrderInfo ) : ?>
                            <tr>
                                <td class="order-info" style="<?php echo $this->get_order_info_wrapper_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table className="order-info-container" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="<?php echo $this->get_order_info_container_style( '.order-info > table' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                        <tbody>
                                        <tr>
											<?php
											$orderInfoItems = [
												[ 'orderDate', 'orderDateLabel', __( 'Order Date', 'woocommerce' ), date_i18n( self::$date_format ) ],
												[ 'orderId', 'orderIdLabel', __( 'Order Number', 'woocommerce' ), '#ABCD1234' ],
												[ 'payment', 'paymentLabel', __( 'Payment', 'woocommerce' ), 'VISA-5902' ]
											];
											$totalInfoItems = array_filter( $orderInfoItems, function ( $orderInfoItem ) {
												return $this->is_section_enable( $orderInfoItem[0] );
											} );
											$width          = count( $totalInfoItems ) === 3 ? '33%' : ( count( $totalInfoItems ) === 2 ? '50%' : '100%' );
											$index          = 0;
											foreach ( $totalInfoItems as $orderInfoItem ) {
												$itemLabel = isset( $this->settings[ $orderInfoItem[1] ] ) && $this->settings[ $orderInfoItem[1] ] !== '' ? $this->settings[ $orderInfoItem[1] ] : $orderInfoItem[2];
												?>

                                                <td style="vertical-align:top;<?php echo 'width:' . $width . ';' . ( $index !== count( $totalInfoItems ) - 1 ? 'padding: 0 4px 0 0;mso-padding-alt: 0 4px 0 0;' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                    <p class="order-info-label" style="<?php echo $this->get_order_info_label_style( '.order-info-label' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $itemLabel; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
                                                    <p class="order-info-text" style="<?php echo $this->get_order_info_text_style( '.order-info-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $orderInfoItem[3]; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>

                                                </td>
												<?php
												$index ++;
											}
											?>

                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
						<?php
						endif;

						foreach ( $dummy_product as $product ) {
							?>
                            <tr>
                                <td class="order_item" style="<?php echo $this->get_item_wrap_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width: 100%">
                                        <tbody>
                                        <tr>
											<?php if ( $showProductImage ) : ?>
                                                <td class="product-image" width="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" align="center" style="width:<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px">
                                                    <table style="width:<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                                        <tr>
                                                            <td align="center" style="border-radius:8px;">
                                                                <img height="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" width="<?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>" src="<?php echo esc_url( BWFCRM_Block_Editor::get_product_placeholder() ); ?>" alt="product" style="height: <?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;width: <?php echo $this->imgSize; //phpcs:ignore WordPress.Security.EscapeOutput ?>px;border-radius:8px;"/>
                                                            </td>
                                                        </tr>
                                                    </table>

                                                </td>
											<?php endif; ?>

											<?php
											$item_meta_style = 'padding-right:4px;width:65%;' . ( $showProductImage ? 'padding-left:10px; mso-padding-alt:0px 4px 0px 10px;' : 'mso-padding-alt:0px 4px 0px 0px;' );

											$item_meta_style_rtl = 'padding-left:4px;width:65%;' . ( $showProductImage ? 'padding-right:10px; mso-padding-alt:0px 10px 0px 4px;' : 'mso-padding-alt:0px 0px 0px 4px;' );
											?>
                                            <td align="<?php echo is_rtl() ? 'right' : 'left' ?>" class="product-info" style="<?php echo is_rtl() ? $item_meta_style_rtl : $item_meta_style; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="product-title" style="<?php echo $this->get_item_title_style( '.product-info .product-title' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $product['title']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
												<?php if ( $this->is_section_enable( 'sku' ) ) : ?>
                                                    <p class='product-sku' style="<?php echo $this->get_item_sku_style( '.product-info .product-sku ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $product['sku']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
												<?php endif; ?>
                                                <p class="product-meta" style="<?php echo $this->get_item_meta_style( '.product-info .product-meta ' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $product['meta_data']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
												<?php if ( $this->is_section_enable( 'qty' ) ) : ?>
                                                    <p class="product-qty" style="<?php echo $this->get_item_qty_style( '.product-qty' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php echo $this->settings['qtyLabel'] . ' ' . $product['qty']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                    </p>
												<?php endif; ?>
                                            </td>
											<?php if ( $this->is_section_enable( 'qty' ) ) : ?>
                                                <td class="product-qty-wrap" style="width:15%;">
                                                    <p style="<?php echo $this->get_item_qty_style( '.product-qty' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
														<?php
														echo $this->settings['qtyLabel'] . ' ' . $product['qty']; //phpcs:ignore WordPress.Security.EscapeOutput
														?>
                                                    </p>
                                                </td>
											<?php endif; ?>

                                            <td class="product-price" style="width:20%;" align="<?php echo is_rtl() ? "left" : "right" ?>">
                                                <p style="<?php echo $this->get_order_price_style( '.product-price p' ) ?>"><?php echo $product['price']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                            </td>
                                        </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
							<?php
						}
						?>
                        <tr>
                            <td align="<?php echo is_rtl() ? "right" : "left" ?>" class="order-meta" style="padding: 24px 0; mso-padding-alt: 24px 0;<?php echo $this->get_block_seperator_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                <table style="<?php echo $this->get_order_meta_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?>>
                                    <tbody>
                                    <tr class="subtotal">
                                        <td class="subtotal-text" style="width:65%;padding: 0 0 5px; mso-padding-alt: 0 0 5px;">
                                            <p class="price-text" style="<?php echo $this->get_subtotal_text_style( '.subtotal .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $this->settings['subtotalLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                        <td align="<?php echo is_rtl() ? "left" : "right" ?>" class="subtotal-price" style="width:30%;padding: 0 0 5px; mso-padding-alt: 0 0 5px;">
                                            <p class="price" style="<?php echo $this->get_subtotal_price_style( '.subtotal .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo wc_price( 10.00 ); //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                    </tr>
									<?php
									$orderPricingInfo = [
										[ $this->settings['shippingLabel'], wc_price( 7.00 ) ],
										[ $this->settings['taxesLabel'], wc_price( 5.00 ) ],
										[ $this->settings['discountLabel'], '-' . wc_price( 10.00 ) ]
									];
									$index            = 0;
									foreach ( $orderPricingInfo as $priceItem ) {
										$padding = count( $orderPricingInfo ) - 1 !== $index ? 'padding: 0px 0px 8px 0px; mso-padding-alt: 0px 0px 8px 0px;' : "";
										?>
                                        <tr class="prices">
                                            <td class="price-text-wrap" style="width:65%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="price-text" style="<?php echo $this->get_lineitem_text_style( '.prices .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo $priceItem[0]; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                </p>
                                            </td>
                                            <td align="<?php echo is_rtl() ? "left" : "right" ?>" class="price-wrap" style="width:30%;<?php echo $padding; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                                <p class="price" style="<?php echo $this->get_order_price_style( '.prices .price' ) . ( 'Discount' === $priceItem[0] ? bwf_css()->getColor( $this->settings, 'discountColor', 'desktop' ) : '' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
													<?php echo $priceItem[1]; //phpcs:ignore WordPress.Security.EscapeOutput ?>
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
                            <td class="total" style="padding: 16px 0px 0px; mso-padding-alt: 16px 0px 0px;">
                                <table align="<?php echo is_rtl() ? 'right' : 'left' ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;<?php echo $this->get_order_meta_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <tbody>
                                    <tr class="total">
                                        <td class="total-text-wrap" style="width:65%;">
                                            <p class="price-text" style="<?php echo $this->get_total_text_style( '.total .price-text' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $this->settings['totalLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                        <td class="total-price-wrap" align="<?php echo is_rtl() ? "left" : "right" ?>" style="width:30%;">
                                            <p class="price" style="<?php echo $this->get_total_price_style( '.total .price' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo wc_price( 52.00 ); //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                        </td>
                                    </tr>
                                    </tbody>

                                </table>

                            </td>
                        </tr>
						<?php $this->subscription_html() ?>
                        </tbody>
                    </table>
                </td>
            </tr>
			<?php
		}

		public function get_subscription_data( $currency ) {
			if ( self::$is_preview ) {
				return [
					[
						'id'           => '123',
						'start_date'   => date_i18n( 'd F, Y' ),
						'end_date'     => __( 'When cancelled', 'wp-marketing-automations-pro' ),
						'amount'       => '$1300 / month',
						'next_payment' => ( new DateTime() )->modify( '+1 month' )->format( 'd F, Y' ),
						'view_link'    => '#',
					],
				];
			}

			$subscription_data = BWFAN_PRO_Common::order_contains_subscriptions( self::$order );
			if ( empty( $subscription_data ) ) {
				return [];
			}

			$result = [];
			foreach ( $subscription_data as $subscription ) {
				$interval = $subscription->get_billing_interval();
				$interval = intval( $interval ) > 1 ? $interval . ' ' : '';
				$result[] = [
					'id'           => $subscription->get_id(),
					'start_date'   => esc_html( date_i18n( self::$date_format, $subscription->get_time( 'start', 'site' ) ) ),
					'end_date'     => esc_html( ( 0 < $subscription->get_time( 'end' ) ) ? date_i18n( self::$date_format, $subscription->get_time( 'end', 'site' ) ) : __( 'When cancelled', 'wp-marketing-automations-pro' ) ),
					'amount'       => wc_price( $subscription->get_total(), $currency ) . ' / ' . $interval . $subscription->get_billing_period(),
					'next_payment' => esc_html( date_i18n( self::$date_format, $subscription->get_time( 'next_payment', 'site' ) ) ),
					'view_link'    => esc_url( ( false ) ? wcs_get_edit_post_link( $subscription->get_id() ) : $subscription->get_view_order_url() ),
				];
			}

			return $result;
		}

		public function subscription_html( $currency = [] ) {

			// remove action for WooCommerce Subscription if block html is enabled
			remove_action( 'woocommerce_email_after_order_table', 'WC_Subscriptions_Order::add_sub_info_email', 15 );

			$showSubscriptions = $this->is_section_enable( 'subscriptions' );
			if ( ! ( function_exists( 'bwfan_is_woocommerce_subscriptions_active' ) && bwfan_is_woocommerce_subscriptions_active() ) || ! $showSubscriptions ) {
				return;
			}
			$subscriptions = $this->get_subscription_data( $currency );
			if ( empty( $subscriptions ) ) {
				return;
			}
			?>
            <tr>
                <td class="sc-wrap">
                    <p class="sc-title" style="<?php echo $this->get_sc_title_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>"><?php echo $this->settings['scTitle']; //phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                    <table align="<?php echo is_rtl() ? 'right' : 'left' ?>" <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> style="width:100%;" class="sc-table">
                        <tbody>
                        <tr>
                            <td class="sc-label" style="width:10%;<?php echo $this->get_sc_label_style(); ?>"><?php echo $this->settings['scIdLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                            <td class="sc-label" style="width:24%;<?php echo $this->get_sc_label_style(); ?>"><?php echo $this->settings['scStartDateLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                            <td class="sc-label" style="width:24%;<?php echo $this->get_sc_label_style(); ?>"><?php echo $this->settings['scEndDateLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                            <td class="sc-label" style="width:28%;<?php echo $this->get_sc_label_style( 'sc-label', '0px' ); ?>"><?php echo $this->settings['scAmountLabel']; //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                        </tr>
						<?php foreach ( $subscriptions as $subscription ) {
							?>
                            <tr>
                                <td style="<?php echo $this->get_sc_td_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>width:10%;">
                                    <a href="<?php echo $subscription['view_link']; ?>" target="_blank" class="sc-id" style="<?php echo $this->get_sc_id_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">#<?php echo $subscription['id']; //phpcs:ignore WordPress.Security.EscapeOutput ?></a>
                                </td>
                                <td class="sc-start-date" style="width:24%;<?php echo $this->get_sc_start_date_style() . $this->get_sc_td_style();; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
									<?php echo $subscription['start_date']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
                                <td class="sc-end-date" style="width:24%;<?php echo $this->get_sc_end_date_style() . $this->get_sc_td_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
									<?php echo $subscription['end_date']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </td>
                                <td style="width:28%;<?php echo $this->get_sc_td_style( '0px' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                    <p class="sc-amount" style="<?php echo $this->get_sc_amount_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
										<?php echo $subscription['amount']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </p>
                                    <p class="sc-next-payment" style="<?php echo $this->get_sc_next_payment_style(); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
										<?php echo $this->settings['scNextPaymentLabel'] . ' :' . $subscription['next_payment']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    </p>
                                </td>
                            </tr>
						<?php } ?>
                        </tbody>
                    </table>
                </td>
            </tr>
			<?php
		}

		public function is_section_enable( $section = '', $screen = 'desktop' ) {
			$section_enabled = isset( $this->settings['enabledContentOptions'][ $screen ] ) ? $this->settings['enabledContentOptions'][ $screen ] : [];

			return ( in_array( $section, $section_enabled ) );
		}

		/**
		 * Get Block Container style
		 */
		private function get_block_wrapper_style() {
			$style = 'word-break:break-word;';
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

		/**
		 * Get order info wrapper style
		 */
		private function get_order_info_wrapper_style( $classname = '' ) {
			$style = 'padding: 24px 0px 24px;';
			$style .= 'mso-padding-alt: 24px 0px 24px;';
			$style .= $this->get_block_seperator_style( 'top' );
			$style .= $this->get_block_seperator_style( 'bottom' );

			return $style;
		}

		private function get_order_info_container_style( $classname = '' ) {
			$style = 'width:100%;';
			$style .= bwf_css()->textAlign( $this->settings, 'orderInfoAlign', 'desktop' );

			if ( $this->wrapper_selector && $classname ) {
				$mStyle                 = bwf_css()->textAlign( $this->settings, 'orderInfoAlign', 'mobile', true );
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

		/**
		 * Get order info label style
		 */
		private function get_order_info_label_style( $classname = '' ) {
			$defaulStyle = $this->get_default_style();
			$style       = 'color: #a5abb6;';
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'orderInfoLabelFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'orderInfoLabelColor', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'orderInfoLabelFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;

		}

		/**
		 * get order info text style
		 */
		private function get_order_info_text_style( $classname = '' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'orderInfoTextFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'orderInfoTextColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'orderInfoTextBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'orderInfoTextItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'orderInfoTextFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;

		}

		/**
		 * Get order item wrapper style
		 */
		private function get_item_wrap_style() {
			$style = 'padding:20px 0px; mso-padding-alt: 20px 0px;';
			$style .= $this->get_block_seperator_style();

			return $style;
		}

		private function get_item_title_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = '';
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'productTitleColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'productTitleBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'productTitleItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'productTitleFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_item_meta_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = 'color: #8c8f94;';
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'productMetaFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'productMetaColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'productMetaBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'productMetaItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'productMetaFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_item_sku_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = 'color: #8c8f94;';
			$style       .= $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'productSkuFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'productSkuColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'productSkuBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'productSkuItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'productSkuFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_item_qty_style() {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'qtyFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'qtyColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'qtyBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'qtyItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'qtyFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_order_price_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'commonPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'commonPriceColor', 'desktop' );
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

		private function get_subtotal_text_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'subTotalTextFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'subTotalTextColor', 'desktop' );
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
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'subTotalPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'subTotalPriceColor', 'desktop' );
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
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'pricesMetaFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'pricesMetaColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'pricesMetaBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'pricesMetaItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'pricesMetaFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_total_text_style( $classname ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'totalTextFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'totalTextColor', 'desktop' );
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
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'totalPriceFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'totalPriceColor', 'desktop' );
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


		private function get_order_meta_style( $classname = '.order-meta' ) {
			$style = 'width:100%;';
			$style .= bwf_css()->textAlign( $this->settings, 'orderMetaAlign', 'desktop', false, is_rtl() ? 'text-align:right;' : "" );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->textAlign( $this->settings, 'orderMetaAlign', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s .order-meta > table, %1$s .total > table {%2$s}', $this->wrapper_selector, $mStyle );
			}

			return $style;
		}

		private function get_sc_title_style( $classname = '.sc-title' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scTitleFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scTitleColor', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			$style       .= 'padding: 48px 0 0;';

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scTitleFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_label_style( $classname = '.sc-label', $right = '4px' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scLabelFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scLabelColor', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );
			$style       .= $this->get_block_seperator_style();
			$style       .= 'padding:24px ' . $right . ' 8px 0;mso-padding-alt:24px ' . $right . ' 8px 0;';

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scLabelFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_id_style( $classname = '.sc-id' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scIdFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scIdColor', 'desktop', 'linkColor' );
			$style       .= bwf_css()->getBold( $this->settings, 'scIdBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'scIdItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scIdFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_start_date_style( $classname = '.sc-start-date' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scStartDateFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scStartDateColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'scStartDateBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'scStartDateItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scStartDateFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_end_date_style( $classname = '.sc-end-date' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scEndDateFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scEndDateColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'scEndDateBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'scEndDateItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scEndDateFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_amount_style( $classname = '.sc-amount' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scAmountFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scAmountColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'scAmountBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'scAmountItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scAmountFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_next_payment_style( $classname = '.sc-next-payment' ) {
			$defaulStyle = $this->get_default_style();
			$style       = $defaulStyle['text-style'];
			$style       .= bwf_css()->getFontSize( $this->settings, 'scNextPaymentFont', 'desktop', false, $defaulStyle['font-size'] );
			$style       .= bwf_css()->getColor( $this->settings, 'scNextPaymentColor', 'desktop' );
			$style       .= bwf_css()->getBold( $this->settings, 'scNextPaymentBold', 'desktop' );
			$style       .= bwf_css()->getItalic( $this->settings, 'scNextPaymentItalic', 'desktop' );
			$style       .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' );

			if ( ! empty( $classname ) ) {
				$mStyle                 = bwf_css()->getFontSize( $this->settings, 'scNextPaymentFont', 'mobile', true );
				$mStyle                 .= bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true );
				$this->responsive_style .= sprintf( '%1$s %2$s {%3$s}', $this->wrapper_selector, $classname, $mStyle );
			}

			return $style;
		}

		private function get_sc_td_style( $right = '4px' ) {
			$style = $this->get_block_seperator_style();
			$style .= 'padding:20px ' . $right . ' 20px 0;mso-padding-alt:20px ' . $right . ' 20px 0;';

			return $style;
		}


		/**
		 * Block default and common style
		 */
		private function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'   => 'font-size:16px;',
				'font-family' => bwf_css()->getFontFamily( $this->settings ),
				'text-style'  => 'margin:0;line-height: 1.5;' . bwf_css()->getFontFamily( $this->settings ),
				'color'       => bwf_css()->getColor( $this->settings, 'color', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}
	}
}
BWFBE_Order_Summary_Template::get_instance();
