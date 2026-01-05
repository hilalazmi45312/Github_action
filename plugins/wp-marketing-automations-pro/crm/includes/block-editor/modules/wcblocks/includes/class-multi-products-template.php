<?php
if ( ! class_exists( 'BWFBE_WC_Multi_Product_Template' ) ) {
	#[AllowDynamicProperties]
	class BWFBE_WC_Multi_Product_Template {
		/**
		 * Instance.
		 *
		 * @access private
		 * @var object Instance
		 * @since 1.2.0
		 */
		private static $instance;

		private $responsive_style = '';

		private $wrapper_selector = '';

		private $tableAttrs = '';

		private $settings = [];

		private $global_settings = [];

		public static $order;

		public $product_data = [];

		public $totalWidth = 100;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_shortcode( 'bwfbe_multi_product', [ $this, 'multi_products_block' ] );
		}

		public function multi_products_block( $atts, $content ) {
			$attributes            = json_decode( BWFCRM_Block_Editor::decode_content( $content ), true );
			$this->global_settings = BWFCRM_Block_Editor::$global_settings;

			$this->default_container_width = isset( $this->global_settings['width'] ) && isset( $this->global_settings['width']['desktop'] ) && isset( $this->global_settings['width']['desktop']['value'] ) ? $this->global_settings['width']['desktop']['value'] : 640;

			$mergetagdata = BWFAN_Merge_Tag_Loader::get_data();
			$is_crm       = false;
			if ( isset( $mergetagdata['is_crm'] ) ) {
				$is_crm = $mergetagdata['is_crm'];
			}

			$defaults = array(
				'layout'                 => 'grid',
				'columns'                => 2,
				'colGap'                 => [
					'desktop' => [
						'value' => 16
					],
				],
				'rows'                   => 1,
				'hAlign'                 => [ 'desktop' => 'center' ],
				'vAlign'                 => [ 'desktop' => 'top' ],
				'buttonText'             => 'Buy Now',
				'showBtn'                => true,
				'showDesc'               => true,
				'showPrice'              => true,
				'imageWidth'             => [ 'desktop' => [ 'value' => 40, 'unit' => '%' ] ],
				'btnBgColor'             => $this->global_settings['buttonBackground'] ?? '#fff',
				'btnTextFont'            => $this->global_settings['buttonFont'] ?? '#fff',
				'btnTextColor'           => $this->global_settings['buttonColor'] ?? '#000',
				'descColor'              => $this->global_settings['color'] ?? '#000',
				'salePriceColor'         => $this->global_settings['color'] ?? '#000',
				'sortBy'                 => 'created',
				'padding'                => [
					'desktop' => [
						'top'    => '8',
						'right'  => '8',
						'bottom' => '8',
						'left'   => '8',
						'unit'   => 'px'
					],
				],
				'titleBold'              => [ 'desktop' => true ],
				'onBoarding'             => false,
				'productFeedType'        => 'specific',
				'productRelatable'       => '',
				'productIncludeCategory' => [],
				'productExcludeCategory' => [],
				'productContentEnabled'  => [
					'desktop' => [
						"image",
						"title",
						"regular_price",
						"price",
						"button"
					],
					'mobile'  => [
						"image",
						"title",
						"regular_price",
						"price",
						"button"
					],
				],
				'specificProductData'    => [],
				'lineHeight'             => [
					'desktop' => [
						'value' => '1.5'
					]
				],
				'imageBorder'            => [
					'desktop' => [
						'top-left'     => '8',
						'top-right'    => '8',
						'bottom-left'  => '8',
						'bottom-right' => '8',
						'unit'         => 'px'
					]
				],
				'regularPriceFont'       => [
					'desktop' => [
						'size' => 14
					]
				],
				'responsive'             => true,
				'productExclude'         => [],
				'imageSize'              => 'medium',
			);

			$this->settings              = wp_parse_args( $attributes, $defaults );
			$this->responsive            = $this->settings['responsive'];
			$this->settings['classname'] = [
				'bwf-best-sellers-products',
				'bwf-products-' . ( $attributes['uniqueID'] ?? '' ),
			];
			if ( ! $this->responsive ) {
				$this->settings['classname'][] = 'not-responsive';
			}
			$this->product_data = $this->get_product_data();
			if ( empty( $this->product_data ) || $this->settings['onBoarding'] === false ) {
				return '';
			}
			$colWidth = isset( $this->settings['colWidth']['desktop'] ) ? $this->settings['colWidth']['desktop'] : ( $this->default_container_width / $this->settings['columns'] );
			$colGap   = isset( $this->settings['colGap']['desktop']['value'] ) ? (int) $this->settings['colGap']['desktop']['value'] : 0;
			$columns  = isset( $this->settings['columns'] ) ? $this->settings['columns'] : 1;

			$this->totalWidth       = ( $colWidth * $columns ) + $colGap * ( $columns - 1 );
			$this->wrapper_selector = '.bwf-best-sellers-products.bwf-products-' . ( $this->settings['uniqueID'] ?? '' );

			$this->tableAttrs = 'cellpadding="0" cellspacing="0" role="presentation" border="0"';

			$css    = $this->get_block_styles();
			$html   = $this->get_block_html();
			$output = BWFCRM_Block_Editor::emogrifier_parsed_output( $css, "<table>$html</table>" );
			$output = ltrim( $output, "<table>" );
			$output = rtrim( $output, "</table>" );

			$response_style = $this->get_block_response_style();

			return BWFAN_Common::decode_merge_tags( $output . $response_style, $is_crm );
		}

		// Get related products by options
		public function get_related_products( $type = '', $counts = 5 ) {
			$products_ids = [];
			$products     = [];
			$mergetagdata = BWFAN_Merge_Tag_Loader::get_data();

			if ( ! empty( $mergetagdata['order_id'] ) ) {
				$order = wc_get_order( $mergetagdata['order_id'] );
				if ( $order instanceof WC_Order ) {
					$order_items = $order->get_items();
					foreach ( $order_items as $item_id => $item ) {
						$product    = $item->get_product();
						$products[] = $product->get_id();
					}
				}
			} else if ( ! empty( $mergetagdata['cart_details'] ) || ! empty( $mergetagdata['cart_abandoned_id'] ) ) {
				if ( ! empty( $mergetagdata['cart_details'] ) ) {
					if ( isset( $mergetagdata['cart_details']['items'] ) ) {
						$mergetagdata['cart_details']['items'] = maybe_unserialize( $mergetagdata['cart_details']['items'] );
					}
					$cart_details = $mergetagdata['cart_details'];
				} else if ( ! empty( $mergetagdata['cart_abandoned_id'] ) ) {
					$cart_details = BWFAN_Model_Abandonedcarts::get( intval( $mergetagdata['cart_abandoned_id'] ) );
					if ( isset( $cart_details['items'] ) ) {
						$cart_details['items'] = maybe_unserialize( $cart_details['items'] );
					}
				}
				foreach ( $cart_details['items'] as $product ) {
					$products[] = $product['data']->get_id();
				}
			}
			if ( ! empty( $products ) ) {
				foreach ( $products as $pid ) {
					$product = wc_get_product( $pid );
					if ( $product instanceof WC_Product ) {
						switch ( $type ) {
							case 'cross_sells':
								$cross_sells = $product->get_cross_sell_ids();
								if ( ! empty( $cross_sells ) ) {
									$products_ids = array_merge( $products_ids, $cross_sells );
								}
								break;
							case 'upsells':
								$upsells = $product->get_upsell_ids();
								if ( ! empty( $upsells ) ) {
									$products_ids = array_merge( $products_ids, $upsells );
								}
								break;
							case 'both':
								$cross_sells = $product->get_cross_sell_ids();
								if ( ! empty( $cross_sells ) ) {
									$products_ids = array_merge( $products_ids, $cross_sells );
								}
								$upsells = $product->get_upsell_ids();
								if ( ! empty( $upsells ) ) {
									$products_ids = array_merge( $products_ids, $upsells );
								}
								break;
						}
					}
				}
				// if empty setting related product
				if ( empty( $products_ids ) ) {
					remove_all_filters( 'woocommerce_product_related_posts_relate_by_category' );
					remove_all_filters( 'woocommerce_get_related_product_cat_terms' );
					remove_all_filters( 'woocommerce_product_related_posts_relate_by_tag' );
					remove_all_filters( 'woocommerce_get_related_product_tag_terms' );
					remove_all_filters( 'woocommerce_product_related_posts_force_display' );
					remove_all_filters( 'woocommerce_related_products' );
					add_filter( 'woocommerce_product_related_posts_shuffle', '__return_false' );
					foreach ( $products as $pid ) {
						$products_ids = array_merge( $products_ids, wc_get_related_products( $pid, $counts ) );
					}
				}
			}

			return ! empty( $products_ids ) ? array_unique( $products_ids ) : [];
		}

		public function get_sort_arg( $args = [] ) {
			switch ( $this->settings['sortBy'] ) {
				case 'created':
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
					break;
				case 'modified':
					$args['orderby'] = 'modified';
					$args['order']   = 'DESC';
					break;
				case 'sales':
					$args['meta_key'] = 'total_sales';
					$args['orderby']  = 'meta_value_num';
					$args['order']    = 'DESC';
					break;
				case 'lowest_price':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_price';
					$args['order']    = 'ASC';
					break;
				case 'highest_price':
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = '_price';
					$args['order']    = 'DESC';
					break;
				case 'random':
					$args['orderby'] = 'rand';
					$args['order']   = 'DESC';
			}

			if ( empty( $args['post__in'] ) ) {
				unset( $args['post__in'] );
			}

			return $args;
		}

		/**
		 * Get filler products
		 *
		 * @param $count int
		 * @param $existing_ids array
		 *
		 * @return array
		 */
		public function get_filler_products_ids( $count = 20, $existing_ids = [] ) {
			$args = [
				'post_type'      => 'product',
				'meta_key'       => 'total_sales',
				'order'          => 'DESC',
				'orderby'        => 'meta_value_num',
				'posts_per_page' => $count,
				'paged'          => 1,
				'fields'         => 'ids',
				'post_status'    => 'publish',
			];

			if ( ! empty( $existing_ids ) ) {
				$args['post__not_in'] = $existing_ids;
			}

			$products = new WP_Query( $args );

			return $products->get_posts();
		}

		public function get_product_data() {
			$layout  = $this->settings['layout'];
			$columns = $this->settings['columns'];
			$rows    = $this->settings['rows'];

			if ( 'grid' === $layout ) {
				$total_counts = (int) $columns * (int) $rows;
			} else {
				$total_counts = (int) $rows;
			}

			/**
			 * Product type specific | related | category | best_selling_store | best_selling_order | latest
			 */
			$product_type = $this->settings['productFeedType'];

			$args = [
				'post_type'      => 'product',
				'meta_key'       => 'total_sales',
				'order'          => 'DESC',
				'orderby'        => 'meta_value_num',
				'posts_per_page' => $total_counts,
				'paged'          => 1,
				'fields'         => 'ids',
				'post_status'    => 'publish',
			];

			switch ( $product_type ) {
				case 'specific' :
					$ids = [];
					if ( isset( $this->settings['specificProductData'] ) && ! empty( $this->settings['specificProductData'] ) ) {
						$ids = array_map( function ( $specificData ) {
							if ( isset( $specificData['id'] ) && ! empty( $specificData['id'] ) ) {
								return $specificData['id'];
							}
						}, $this->settings['specificProductData'] );
					}

					return $ids;
				case 'related' :
					if ( isset( $this->settings['productRelatable'] ) && ! empty( $this->settings['productRelatable'] ) ) {
						// upsells || cross_sells || both
						$args = [
							'post_type'      => 'product',
							'posts_per_page' => $total_counts,
							'paged'          => 1,
							'fields'         => 'ids',
							'post__in'       => $this->get_related_products( $this->settings['productRelatable'], $total_counts ),
						];
					}
					break;
				case 'category' :
					if ( isset( $this->settings['productIncludeCategory'] ) && ! empty( $this->settings['productIncludeCategory'] ) ) {
						// included ids
						$included_ids = $this->settings['productIncludeCategory'];
						if ( ! empty( $included_ids ) ) {
							$args['tax_query'] = [
								[
									'taxonomy' => 'product_cat',
									'field'    => 'term_id',
									'terms'    => [ implode( ',', $included_ids ) ],
									'operator' => 'IN',
								]
							];
						}
					}
					if ( isset( $this->settings['productExcludeCategory'] ) && ! empty( $this->settings['productExcludeCategory'] ) ) {
						// excluded ids
						$excluded_ids = $this->settings['productExcludeCategory'];
						if ( ! empty( $excluded_ids ) ) {
							$arg_query = [
								'taxonomy' => 'product_cat',
								'field'    => 'term_id',
								'terms'    => [ implode( ',', $excluded_ids ) ],
								'operator' => 'NOT IN',
							];
							if ( isset( $args['tax_query'] ) ) {
								$args['tax_query'][] = $arg_query;
							} else {
								$args['tax_query'] = [ $arg_query ];
							}
						}
					}
					break;
				case 'best_selling_store':
					// best-selling products
					$args['meta_key'] = 'total_sales';
					break;
				case 'latest':
					$args['orderby'] = 'date';
					$args['order']   = 'DESC';
					break;
			}
			if ( ! empty( $this->settings['productExclude'] ) ) {
				$args['post__not_in'] = $this->settings['productExclude'];
			}
			$args = $product_type !== 'latest' ? $this->get_sort_arg( $args ) : $args;

			// Exclude out of stock products and products from catalog visibility
			if ( $product_type !== 'specific' ) {
				// Exclude out of stock products
				$status_query = [
					'key'     => '_stock_status',
					'value'   => [ 'instock', 'onbackorder' ],  // Explicitly allow in-stock & backorder
					'compare' => 'IN',
				];

				if ( isset( $args['meta_query'] ) ) {
					$args['meta_query'][] = $status_query;
				} else {
					$args['meta_query'] = [ $status_query ];
				}

				// Exclude products from catalog visibility
				$catalog_visibility = [
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => [ 'exclude-from-catalog', 'exclude-from-search', 'hidden' ],
					'operator' => 'NOT IN',
				];

				if ( isset( $args['tax_query'] ) ) {
					$args['tax_query'][] = $catalog_visibility;
				} else {
					$args['tax_query'] = [ $catalog_visibility ];
				}

			}

			$wp_query = new WP_Query( $args );

			$product_ids = $wp_query->get_posts();

			if ( $product_type !== 'category' && ! apply_filters( 'bwfan_disable_block_filler_products', false ) && count( $product_ids ) < $total_counts ) {
				$diff = $total_counts - count( $product_ids );

				// If products are excluded, then we need to exclude them from filler products
				$exclude_ids     = ( isset( $this->settings['productExclude'] ) && is_array( $this->settings['productExclude'] ) && count( $this->settings['productExclude'] ) > 0 ) ? array_merge( $product_ids, $this->settings['productExclude'] ) : $product_ids;
				$filler_products = $this->get_filler_products_ids( $diff, $exclude_ids );
				$product_ids     = array_merge( $product_ids, $filler_products );
			}

			return $product_ids;
		}

		public function get_block_html() {
			ob_start();
			$grid_rows   = (int) $this->settings['rows'];
			$grid_column = (int) $this->settings['columns'];
			?>
            <tr>
                <td class="<?php echo implode( ' ', $this->settings['classname'] ); //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo is_rtl() ? 'dir="rtl"' : '' ?> <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> class="bwf-multi-product-container">
						<?php
						if ( $this->settings['productFeedType'] !== 'specific' ) {
							$count = 1;
							foreach ( $this->product_data as $pid ) {
								$product = wc_get_product( $pid );
								if ( ! $product instanceof WC_Product ) {
									continue;
								}
								if ( 'grid' === $this->settings['layout'] ) {
									echo $this->get_product_layout_1( $product, $count ); //phpcs:ignore WordPress.Security.EscapeOutput
								} else {
									echo $this->get_product_layout_2( $product ); //phpcs:ignore WordPress.Security.EscapeOutput
								}
								$count ++;
							}
						} else {
							if ( isset( $this->settings['specificProductData'] ) && ! empty( $this->settings['specificProductData'] ) ) {
								$product_list = $this->settings['specificProductData'];
								for ( $rowsCount = 0; $rowsCount < $grid_rows; $rowsCount ++ ) {
									if ( 'grid' === $this->settings['layout'] ) {
										for ( $colCount = 0; $colCount < $grid_column; $colCount ++ ) {
											echo $this->get_specific_product_layout_1( $rowsCount, $colCount, $product_list ); //phpcs:ignore WordPress.Security.EscapeOutput
										}
									} else {
										$product      = [];
										$product_info = [];
										if ( isset( $product_list[ $rowsCount . '0' ] ) && isset( $product_list[ $rowsCount . '0' ]['id'] ) ) {
											$product = wc_get_product( $product_list[ $rowsCount . '0' ]['id'] );
											if ( ! $product instanceof WC_Product ) {
												continue;
											}
											$product_info = $product_list[ $rowsCount . '0' ];
										}
										echo $this->get_product_layout_2( $product, $product_info ); //phpcs:ignore WordPress.Security.EscapeOutput
									}
								}
							}
						}

						?>
                    </table>
                </td>
            </tr>
			<?php
			return ob_get_clean();
		}

		private function get_gap_html( $count, $grid_column ) {
			if ( $count !== $grid_column - 1 && $this->settings['colGap']['desktop']['value'] ) {
				ob_start();
				?>
				<?php echo $this->responsive ? ( '<!--[if mso | IE]><td style="width: ' . $this->settings['colGap']['desktop']['value'] . 'px;vertical-align:bottom;" valign="bottom><![endif]--><div class="bwf-product-grid-gap" style="font-size:0;vertical-align:bottom;display:inline-block;width:' . $this->settings['colGap']['desktop']['value'] . 'px;">' ) : '<td class="bwf-product-grid-gap" style="font-size:0;vertical-align:bottom;width:' . $this->settings['colGap']['desktop']['value'] . 'px;">'; //phpcs:ignore WordPress.Security.EscapeOutput
				?>

                <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> >
                    <tbody>
                    <tr>
                        <td></td>
                    </tr>
                    </tbody>
                </table>

				<?php echo $this->responsive ? '</div><!--[if mso | IE]></td><![endif]-->' : '</td>' ?>
				<?php
				return ob_get_clean();
			}

			return '';
		}


		public function get_product_layout_1( $product, &$count ) {
			$grid_column = (int) $this->settings['columns'];
			if ( $count > $grid_column ) {
				$count = 1;
			}
			$colWidth = $count === $grid_column && $this->totalWidth !== $this->settings['containerWidth']['desktop'] ? $this->settings['colWidth']['desktop'] - ( $this->totalWidth - $this->settings['containerWidth']['desktop'] ) : $this->settings['colWidth']['desktop'];

			ob_start();
			echo 1 === $count ? ( '<tr><td class="bwf-product-grid-row">' . ( $this->responsive ? '<!--[if mso | IE]>' : '' ) . '<table ' . ( is_rtl() ? 'dir="rtl"' : '' ) . ' role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;"><tbody><tr>' . ( $this->responsive ? '<![endif]-->' : '' ) ) : '';
			?>

			<?php
			if ( $this->responsive ) {
				echo '<!--[if mso | IE]>';
			}
			?>
            <td class="bwf-multi-product-wrapper <?php echo "bwf-product-col-per-" . $grid_column; //phpcs:ignore WordPress.Security.EscapeOutput ?>" width="<?php echo $colWidth; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="display: inline-block; vertical-align: bottom; width: <?php echo $colWidth . 'px'; //phpcs:ignore WordPress.Security.EscapeOutput ?>;">

				<?php
				if ( $this->responsive ) {
					echo '<![endif]-->';
				}
				?>

                <div class="bwf-multi-product-wrapper <?php echo "bwf-product-col-per-" . $grid_column; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> >
                        <tr>
							<?php echo $this->get_product_image( $product ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                        </tr>
                        <tr>
							<?php echo $this->get_product_meta( $product ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                        </tr>
                    </table>
                </div>
				<?php
				if ( $this->responsive ) {
					echo '<!--[if mso | IE]>';
				}
				?>
            </td>
			<?php
			if ( $this->responsive ) {
				echo '<![endif]-->';
			}
			?>

			<?php echo $this->get_gap_html( $count - 1, $grid_column ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php
			echo $count === $grid_column ? ( ( $this->responsive ? '<!--[if mso | IE]>' : '' ) . '</tr></tbody></table>' . ( $this->responsive ? '<![endif]-->' : '' ) . '</td></tr>' ) : '';

			return ob_get_clean();
		}

		public function get_product_layout_2( $product, $productData = [] ) {
			ob_start();
			?>
            <tr>
                <td class="bwf-multi-product-wrapper bwf-list-layout">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> >
                        <tr>
							<?php if ( ! empty( $product ) ) {
								echo $this->get_product_image( $product, $productData ); //phpcs:ignore WordPress.Security.EscapeOutput
								echo $this->get_product_meta( $product, $productData ); //phpcs:ignore WordPress.Security.EscapeOutput
							} ?>
                        </tr>
                    </table>
                </td>
            </tr>
			<?php
			return ob_get_clean();
		}

		public function get_specific_product_layout_1( $currentRow, $currentCol, $product_list ) {
			$grid_column = (int) $this->settings['columns'];
			$product     = [];
			$productData = [];
			if ( isset( $product_list["$currentRow$currentCol"] ) && isset( $product_list["$currentRow$currentCol"]['id'] ) ) {
				$product     = wc_get_product( $product_list["$currentRow$currentCol"]['id'] );
				$productData = $product_list["$currentRow$currentCol"];
			}
			$colWidth = $currentCol === $grid_column && $this->totalWidth !== $this->settings['containerWidth']['desktop'] ? $this->settings['colWidth']['desktop'] - ( $this->totalWidth - $this->settings['containerWidth']['desktop'] ) : $this->settings['colWidth']['desktop'];

			ob_start();
			echo 0 === $currentCol ? ( '<tr><td class="bwf-product-grid-row">' . ( $this->responsive ? '<!--[if mso | IE]>' : '' ) . '<table ' . ( is_rtl() ? 'dir="rtl"' : '' ) . ' role="presentation" border="0" cellpadding="0" cellspacing="0" style="width:100%;"><tbody><tr>' . ( $this->responsive ? '<![endif]-->' : '' ) ) : '';
			?>

			<?php
			if ( $this->responsive ) {
				echo '<!--[if mso | IE]>';
			}
			?>
            <td class="bwf-multi-product-wrapper <?php echo "bwf-product-col-per-" . $grid_column; //phpcs:ignore WordPress.Security.EscapeOutput ?>" width="<?php echo $colWidth; //phpcs:ignore WordPress.Security.EscapeOutput ?>" style="display: inline-block; vertical-align: bottom; width: <?php echo $colWidth . 'px'; //phpcs:ignore WordPress.Security.EscapeOutput ?>;">

				<?php
				if ( $this->responsive ) {
					echo '<![endif]-->';
				}
				?>

                <div class="bwf-multi-product-wrapper <?php echo "bwf-product-col-per-" . $grid_column; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                    <table <?php echo $this->tableAttrs; //phpcs:ignore WordPress.Security.EscapeOutput ?> >
                        <tr>
							<?php if ( ! empty( $product ) ) {
								echo $this->get_product_image( $product, $productData ); //phpcs:ignore WordPress.Security.EscapeOutput
							} ?>
                        </tr>
                        <tr>
							<?php if ( ! empty( $product ) ) {
								echo $this->get_product_meta( $product, $productData ); //phpcs:ignore WordPress.Security.EscapeOutput
							} ?>
                        </tr>
                    </table>
                </div>

				<?php
				if ( $this->responsive ) {
					echo '<!--[if mso | IE]>';
				}
				?>
            </td>
			<?php
			if ( $this->responsive ) {
				echo '<![endif]-->';
			}
			?>
			<?php echo $this->get_gap_html( $currentCol, $grid_column ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php
			echo $grid_column - 1 === $currentCol ? ( ( $this->responsive ? '<!--[if mso | IE]>' : '' ) . '</tr></tbody></table>' . ( $this->responsive ? '<![endif]-->' : '' ) . '</td></tr>' ) : '';

			return ob_get_clean();
		}

		public function is_section_enable( $section = '', $screen = 'desktop' ) {
			$section_enabled = isset( $this->settings['productContentEnabled'][ $screen ] ) ? $this->settings['productContentEnabled'][ $screen ] : [];
			if ( in_array( $section, $section_enabled ) ) {
				return true;
			}

			return false;
		}

		public function get_product_image( $product, $product_info = [] ) {
			if ( ! $this->is_section_enable( 'image' ) ) {
				return;
			}
			ob_start();
			if ( ! empty( $product_info ) && isset( $product_info['image'] ) && $product_info['image'] !== '' ) {
				$prd_img = $product_info['image'];
			} else {
				$prd_img = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), $this->settings['imageSize'] )[0] ?? BWFCRM_Block_Editor::get_product_placeholder();
			}
			if ( ! empty( $product_info ) && isset( $product_info['permalink'] ) && $product_info['permalink'] !== '{{product_url}}' ) {
				$product_url = $product_info['permalink'];
			} else {
				$product_url = get_the_permalink( $product->get_id() );
			}
			$img_attrs = [
				'src'   => ! empty( $prd_img ) ? $prd_img : BWFCRM_Block_Editor::get_product_placeholder(),
				'alt'   => ! empty( $prd_img ) ? $product->get_name() : '',
				'width' => $this->settings['imgWidthPx']['desktop'],
			];
			if ( empty( $prd_img ) ) {
				$img_attrs['class'] = 'bwf-product-default-img';
			}
			?>
            <td class="bwf-multi-product-image">
                <a href="<?php echo esc_url( $product_url ); ?>" target="_blank">
                    <img <?php echo $this->arr_to_attr( $img_attrs ); //phpcs:ignore WordPress.Security.EscapeOutput ?> />
                </a>
            </td>
			<?php
			return ob_get_clean();
		}

		public function get_product_meta( $product, $product_info = [] ) {
			ob_start();

			if ( ! empty( $product_info ) && isset( $product_info['permalink'] ) && $product_info['permalink'] !== '{{product_url}}' ) {
				$product_url = $product_info['permalink'];
			} else {
				$product_url = get_the_permalink( $product->get_id() );
			}
			if ( ! empty( $product_info ) && isset( $product_info['title'] ) && false !== strpos( $product_info['title'], '{{product_title}}' ) && $this->is_section_enable( 'title' ) ) {
				$product_title = str_replace( '{{product_title}}', $product->get_name(), $product_info['title'] );
			} else {
				$product_title = $this->settings['productFeedType'] === 'specific' && isset( $product_info['title'] ) ? $product_info['title'] : $product->get_name();
			}
			if ( ! empty( $product_info ) && isset( $product_info['description'] ) && false !== strpos( $product_info['description'], '{{product_description}}' ) && $this->is_section_enable( 'description' ) ) {
				$product_desc = str_replace( '{{product_description}}', $product->get_short_description(), $product_info['description'] );
			} else {
				$product_desc = $this->settings['productFeedType'] === 'specific' && isset( $product_info['description'] ) ? $product_info['description'] : $product->get_short_description();
			}
			?>
            <td class="bwf-multi-product-meta">
                <table>
					<?php if ( $this->is_section_enable( 'title' ) ) : ?>
                        <tr>
                            <td class="bwf-multi-product-title">
                                <a href="<?php echo esc_url( $product_url ); ?>" target="_blank">
									<?php echo $product_title; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                </a>
                            </td>
                        </tr>
					<?php endif; ?>
					<?php if ( $this->is_section_enable( 'description' ) && ! empty( $product_desc ) ) : ?>
                        <tr>
                            <td class="bwf-multi-product-desc"><?php echo $product_desc; //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                        </tr>
					<?php endif; ?>

					<?php if ( $this->is_section_enable( 'price' ) ) : ?>
                        <tr>
                            <td class="bwf-multi-product-prices"><?php echo $product->get_price_html(); //phpcs:ignore WordPress.Security.EscapeOutput ?></td>
                        </tr>
					<?php endif; ?>

					<?php if ( $this->is_section_enable( 'button' ) && ! empty( $this->settings['buttonText'] ) ) : ?>
                        <tr>
                            <td class="bwf-multi-product-button-wrap" align="<?php echo 'grid' === $this->settings['layout'] && isset( $this->settings['hAlign']['desktop'] ) ? $this->settings['hAlign']['desktop'] : ''; //phpcs:ignore WordPress.Security.EscapeOutput ?>">
                                <table cellpadding="0" cellspacing="0" role="presentation">
                                    <tr>
                                        <td class="bwf-multi-product-button">
                                            <a href="<?php echo esc_url( get_the_permalink( $product->get_id() ) ); ?>" target="_blank">
												<?php echo $this->settings['buttonText']; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
					<?php endif; ?>
                </table>
            </td>
			<?php
			return ob_get_clean();
		}

		public function get_block_styles() {
			ob_start();

			$colWidth = isset( $this->settings['colWidth']['desktop'] ) ? $this->settings['colWidth']['desktop'] : ( $this->default_container_width / $this->settings['columns'] );

			$containerWidth = isset( $this->settings['containerWidth']['desktop'] ) ? $this->settings['containerWidth']['desktop'] : $this->default_container_width;

			$lastColWidth = ( $containerWidth && $this->totalWidth !== $containerWidth ) ? ( isset( $this->settings['colWidth']['desktop'] ) ? $this->settings['colWidth']['desktop'] - ( $this->totalWidth - $containerWidth ) : $colWidth ) : $colWidth;

			?>
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> {
			<?php echo bwf_css()->getPadding( $this->settings, 'padding', 'desktop' ) . bwf_css()->getMsoPadding( $this->settings, 'padding', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getVisibilityCss( $this->settings, 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-container {
            width: 100%;
            }

			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-wrapper {
			<?php echo bwf_css()->getFontStyles( $this->settings, 'font', 'desktop', false ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            width: 100%;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-grid-row {
            font-size: 0;
            padding-bottom: 16px;
            mso-padding-alt: 0 0 16px 0;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> tr:last-child .bwf-product-grid-row {
            padding-bottom: 0px;
            mso-padding-alt: 0px;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> tr .bwf-multi-product-wrapper.bwf-list-layout {
            padding-bottom: 16px;
            mso-padding-alt: 0 0 16px 0;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> tr:last-child .bwf-multi-product-wrapper.bwf-list-layout {
            padding-bottom: 0px;
            mso-padding-alt: 0px;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-wrapper:not(.bwf-list-layout) {
            display: inline-block;
            vertical-align: bottom;
            width: <?php echo $colWidth . 'px;'; //phpcs:ignore WordPress.Security.EscapeOutput ?>;
            box-sizing: border-box;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-grid-row .bwf-multi-product-wrapper:nth-child(<?php echo $this->settings['colGap']['desktop']['value'] ? ( ( 2 * $this->settings['columns'] ) - 1 ) . 'n' : $this->settings['columns'] . 'n'; //phpcs:ignore WordPress.Security.EscapeOutput ?>) {
            width: <?php echo $lastColWidth . 'px;'; //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-wrapper > table {
            width: 100%;
            }

			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta {
			<?php
			if ( 'grid' === $this->settings['layout'] ) {
				echo 'text-align:' . $this->settings['hAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo 'vertical-align:' . $this->settings['vAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
            }


			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image {
            width: 100%;
			<?php
			if ( 'list' === $this->settings['layout'] ) {
				?>
                width: <?php echo 35 * ( ( $this->settings['imageWidth']['desktop']['value'] ?? 0 ) / 100 ); //phpcs:ignore WordPress.Security.EscapeOutput ?>%;
				<?php
			}
			?>

            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image a {
            text-decoration: none;
            color: #000;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image img {
            width: <?php echo $this->settings['imgWidthPx']['desktop'] . 'px;'; //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderWidth( $this->settings, 'imageBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderColor( $this->settings, 'imageBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderStyle( $this->settings, 'imageBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderRadius( $this->settings, 'imageBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image .bwf-product-default-img {
            border: 1px solid #f2f2f2;
            }

			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta {
			<?php
			if ( 'list' === $this->settings['layout'] ) {
				?>
                width: <?php echo 100 - ( 35 * ( ( $this->settings['imageWidth']['desktop']['value'] ?? 0 ) / 100 ) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>%;
                padding: 0 0 0 16px;
				<?php
			} else {
				?>
                width: 100%;
				<?php
			}
			?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta table {
            width: 100%;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-title {
            padding-top: 8px;
            mso-padding-alt: 8px 0 0 0;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-title,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-title a {
            color: #000;
            font-size: 16px;
            font-weight: 600;
            line-height: 1.3;
			<?php echo bwf_css()->getFontSize( $this->settings, 'titleFont', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getColor( $this->settings, 'titleColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBold( $this->settings, 'titleBold', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getItalic( $this->settings, 'titleItalic', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-title a {
            word-break:break-word;
            text-decoration: none;
            }
            .screen-reader-text{
            display:none;
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-desc {
            word-break:break-word;
            font-size: 14px;
            line-height: 1.3;
            font-weight: 400;
            color: #6d6d6d;
            padding-top: 8px;
            mso-padding-alt: 8px 0 0 0;
			<?php echo bwf_css()->getFontSize( $this->settings, 'descFont', 'desktop', 'font' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getColor( $this->settings, 'descColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBold( $this->settings, 'descBold', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getItalic( $this->settings, 'descItalic', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices {
            color: #353030;
            line-height: 1.3;
            padding-top: 8px;
            mso-padding-alt: 8px 0 0 0;
            font-size: 16px;
			<?php echo bwf_css()->getColor( $this->settings, 'salePriceColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getFontSize( $this->settings, 'salePriceFont', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBold( $this->settings, 'salePriceBold', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getItalic( $this->settings, 'salePriceItalic', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php if ( $this->is_section_enable( 'price' ) && ! $this->is_section_enable( 'regular_price' ) ) : ?>
				<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices del {
                display:none;
                mso-hide:all;
                }
			<?php endif; ?>
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices del,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices del bdi,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices del span {
			<?php echo bwf_css()->getColor( $this->settings, 'regularPriceColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getFontSize( $this->settings, 'regularPriceFont', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBold( $this->settings, 'regularPriceBold', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getItalic( $this->settings, 'regularPriceItalic', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices ins,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices ins bdi,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices ins span {
            text-decoration: none;
			<?php echo bwf_css()->getColor( $this->settings, 'salePriceColor', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getFontSize( $this->settings, 'salePriceFont', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button-wrap > table {
            width: auto;
            border-collapse: separate;
			<?php
			//phpcs:ignore WordPress.Security.EscapeOutput
			echo bwf_css()->handleAutoWidth( $this->settings, 'btnAutoWidth', 'btnWidth', 'desktop', [
				'buttonAuto',
				'buttonSize'
			], false, 'value' ); ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button {
			<?php echo bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background:#007cba;' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getMsoPadding( $this->settings, 'btnPadding', 'desktop', false, '', 'buttonPadding' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderWidth( $this->settings, 'btnBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderColor( $this->settings, 'btnBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderStyle( $this->settings, 'btnBorder', 'desktop' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderRadius( $this->settings, 'btnBorder', 'desktop', false, '', 'buttonBorder' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            text-align: center;
            }


			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-title,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-title a,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-desc,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-prices {
			<?php
			if ( 'grid' === $this->settings['layout'] ) {
				echo 'text-align:' . $this->settings['hAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo 'vertical-align:' . $this->settings['vAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-button-wrap {
			<?php
			if ( 'grid' === $this->settings['layout'] ) {
				echo 'text-align: -webkit-' . $this->settings['hAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo 'vertical-align:' . $this->settings['vAlign']['desktop'] . ';'; //phpcs:ignore WordPress.Security.EscapeOutput
			}
			?>
            padding-top: 8px;
            mso-padding-alt: 8px 0 0 0;
            }

			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-title,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-title a,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-desc,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button a,
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta .bwf-multi-product-prices {
            margin:0;
			<?php echo bwf_css()->getFontFamily( $this->settings ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button a {
            word-break:break-word;
            display: inline-block;
            text-align: center;
            font-size: 16px;
            width: auto;
            color: #fff;
            text-decoration: none;
            text-transform: none;
            box-sizing: border-box;
            mso-padding-alt: 0;
            padding: 10px 20px;
            line-height:1.5;
			<?php echo bwf_css()->getPadding( $this->settings, 'btnPadding', 'desktop', false, '', 'buttonPadding' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBgColor( $this->settings, 'btnBgColor', 'desktop', 'buttonBackground', false, 'background:#007cba;' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'desktop', 'buttonFont' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getColor( $this->settings, 'btnTextColor', 'desktop', 'buttonColor', false, 'color: #ffffff;' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo bwf_css()->getBorderRadius( $this->settings, 'btnBorder', 'desktop', false, '', 'buttonBorder' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
            }
			<?php
			return ob_get_clean();

		}

		public function get_block_response_style() {
			ob_start();
			?>
            <style data-id='woofunnels'>
                @media only screen and (max-width: 768px) {
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-col-per-3, <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-col-per-2, <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-col-per-1 {
                    width: 100% !important;
                }

                    <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?>.not-responsive .bwf-product-col-per-3,
                    <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?>.not-responsive .bwf-product-col-per-2 {
                        display: table-cell !important;
                    }

                    <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?>.not-responsive .bwf-product-col-per-2 {
                        width: 50% !important;
                    }

                    <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?>.not-responsive .bwf-product-col-per-3 {
                        width: 33.33% !important;
                    }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-grid-gap {
                                                                                                          display: inline-block !important;
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> {
                <?php echo bwf_css()->getPadding($this->settings, 'padding', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php echo bwf_css()->getVisibilityCss($this->settings, 'mobile' ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                }
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-title a {
                                                                                                      <?php echo bwf_css()->getFontSize( $this->settings, 'titleFont', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-desc {
                                                                                                      <?php echo bwf_css()->getFontSize( $this->settings, 'descFont', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices {
                                                                                                      <?php echo bwf_css()->getFontSize( $this->settings, 'salePriceFont', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices del span {
                                                                                                      <?php echo bwf_css()->getFontSize( $this->settings, 'regularPriceFont', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getLineHeight( $this->settings, 'lineHeight', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-prices ins span {
                                                                                                      <?php echo bwf_css()->getFontSize( $this->settings, 'salePriceFont', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button {
                                                                                                      <?php echo bwf_css()->getBorderWidth($this->settings, 'btnBorder', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getBorderColor($this->settings, 'btnBorder', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getBorderStyle($this->settings, 'btnBorder', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getBorderRadius($this->settings, 'btnBorder', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-button a {
                                                                                                      <?php echo bwf_css()->getPadding( $this->settings, 'btnPadding', 'mobile', true ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getFontSize( $this->settings, 'btnTextFont', 'mobile', 'buttonFont' ); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->handleAutoWidth($this->settings, 'btnAutoWidth', 'btnWidth', 'mobile', ['buttonAuto', 'buttonSize'], false, 'value'); //phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo bwf_css()->getBorderRadius($this->settings, 'btnBorder', 'mobile', true); //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image {
                                                                                                          padding: 0 0 16px !important;
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image img {
                                                                                                          width: 100% !important;
                                                                                                      }

                <?php if( 'grid' === $this->settings['layout'] ) : ?>
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image img {
                                                                                                          width: <?php echo ($this->settings['imageWidth']['mobile']['value'] ?? $this->settings['imageWidth']['desktop']['value'] ?? '100') . '% !important;'; //phpcs:ignore WordPress.Security.EscapeOutput ?>
                                                                                                      }

                <?php endif; ?>
                <?php if('list' === $this->settings['layout'] && ($this->settings['imageWidth']['mobile']['value'] ?? 0) ) : ?>
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-image {
                                                                                                          width: <?php echo 35 * (($this->settings['imageWidth']['mobile']['value']) / 100); //phpcs:ignore WordPress.Security.EscapeOutput ?>% !important;
                                                                                                      }

                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-multi-product-meta {
                                                                                                          width: <?php echo 100 - ( 35 * (($this->settings['imageWidth']['mobile']['value']) / 100) ); //phpcs:ignore WordPress.Security.EscapeOutput ?>% !important;
                                                                                                      }

                <?php endif; ?>

                <?php if( $this->responsive ) : ?>
                <?php echo $this->wrapper_selector; //phpcs:ignore WordPress.Security.EscapeOutput ?> .bwf-product-grid-gap {
                                                                                                          height: <?php echo ($this->settings['colGap']['mobile']['value'] ?? $this->settings['colGap']['desktop']['value'] ?? '0'); //phpcs:ignore WordPress.Security.EscapeOutput ?>px;
                                                                                                      }

                <?php endif; ?>
                }
            </style>
			<?php
			return ob_get_clean();
		}

		public function arr_to_attr( $arr = [] ) {
			if ( empty( $arr ) ) {
				return '';
			}
			$string = '';
			foreach ( $arr as $key => $value ) {
				$string .= $key . '="' . $value . '" ';
			}

			return trim( $string );
		}


		/**
		 * Block default and common style
		 */
		public function get_default_style( $type = '' ) {
			$defaultStyle = [
				'font-size'  => 'font-size:16px;',
				'text-style' => 'margin:0;line-height: 1.5;' . bwf_css()->getFontStyles( $this->settings, 'font', 'desktop' ),
				'color'      => bwf_css()->getColor( $this->settings, 'Gcolor', 'desktop' )
			];

			if ( ! empty( $type ) && is_string( $type ) ) {
				return $defaultStyle[ $type ];
			}

			return $defaultStyle;
		}

	}
}
BWFBE_WC_Multi_Product_Template::get_instance();