<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'WCPB_Product_Badges_Public' ) ) {

	class WCPB_Product_Badges_Public {

		public function __construct() {

			add_filter( 'woocommerce_sale_flash', '__return_false', PHP_INT_MAX );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueues' ) );
			add_action( 'wp_footer', array( $this, 'footer_styles' ), PHP_INT_MAX );
			add_action( 'woocommerce_before_shop_loop_item', array( $this, 'add_badge_loop_product' ) );
			add_filter( 'woocommerce_blocks_product_grid_item_html', array( $this, 'add_badge_loop_product_block' ), 0, 3 );
			add_filter( 'woocommerce_single_product_image_thumbnail_html', array( $this, 'add_badge_single_product' ), 0 );
			add_action( 'wp_footer', array($this, 'compatibility_mode_product_pages' ) );
			add_filter( 'safe_style_css', array( $this, 'add_safe_style_css' ) );

		}

		public function enqueues() {

			$compatibility_mode_product_loops_position = get_option( 'wcpb_product_badges_compatibility_mode_product_loops_position' );
			$compatibility_mode_product_pages = get_option( 'wcpb_product_badges_compatibility_mode_product_pages' );
			$multiple_badges_per_product = get_option( 'wcpb_product_badges_multiple_badges_per_product' );

			wp_enqueue_script( 'jquery' );

			wp_enqueue_style(
				'wcpb-product-badges-public',
				plugins_url( 'assets/css/public.min.css', __DIR__ ),
				array(),
				WCPB_PRODUCT_BADGES_VERSION,
				'all'
			);

			if ( 'yes' !== $compatibility_mode_product_loops_position ) {

				wp_enqueue_script(
					'wcpb-product-badges-public-badge-position-loop',
					plugins_url( 'assets/js/public-badge-position-loop.min.js', __DIR__ ),
					array(
						'jquery',
						'wp-i18n',
					),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_set_script_translations(
					'wcpb-product-badges-public-badge-position-loop',
					'wcpb-product-badges',
					plugin_dir_path( __DIR__ ) . 'languages'
				);

			}

			if ( '' !== $compatibility_mode_product_pages && is_product() ) {

				wp_enqueue_script(
					'wcpb-product-badges-public-compatibility-mode-product-pages',
					plugins_url( 'assets/js/public-compatibility-mode-product-pages.min.js', __DIR__ ),
					array(
						'jquery',
						'wp-i18n',
					),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_set_script_translations(
					'wcpb-product-badges-public-compatibility-mode-product-pages',
					'wcpb-product-badges',
					plugin_dir_path( __DIR__ ) . 'languages'
				);

			}

			if ( 'yes' !== $multiple_badges_per_product ) { // This isn't enqueued as not required if multiple badges per product enabled, as in this scenario the magnify icon is hidden via WCPB_Product_Badges_Public::footer_styles, see multiple badges per product setting description for why

				wp_enqueue_script(
					'wcpb-product-badges-public-magnify-position',
					plugins_url( 'assets/js/public-magnify-position.min.js', __DIR__ ),
					array(
						'jquery',
						'wp-i18n',
					),
					WCPB_PRODUCT_BADGES_VERSION,
					true
				);

				wp_set_script_translations(
					'wcpb-product-badges-public-magnify-position',
					'wcpb-product-badges',
					plugin_dir_path( __DIR__ ) . 'languages'
				);

			}

			wp_enqueue_script(
				'wcpb-product-badges-public-badge-countdown',
				plugins_url( 'assets/js/public-badge-countdown.min.js', __DIR__ ),
				array(
					'jquery',
					'wp-i18n',
				),
				WCPB_PRODUCT_BADGES_VERSION,
				true
			);

			wp_set_script_translations(
				'wcpb-product-badges-public-badge-countdown',
				'wcpb-product-badges',
				plugin_dir_path( __DIR__ ) . 'languages'
			);

		}

		public function footer_styles() {

			$multiple_badges_per_product = get_option( 'wcpb_product_badges_multiple_badges_per_product' );

			if ( 'yes' == $multiple_badges_per_product ) { ?>

				<style>.woocommerce-product-gallery__trigger { display: none !important; }</style>

				<?php

			}

		}

		public function add_badge_loop_product() {

			global $product;

			if ( !empty( $product ) ) {

				$this->badge( $product, true );

			}

		}

		public function add_badge_loop_product_block( $html, $data, $product ) {

			if ( !empty( $product ) ) {

				$html = str_replace( '<div class="wc-block-grid__product-image">', '<div class="wc-block-grid__product-image">' . $this->badge( $product, false ), $html );

			}

			return $html;

		}

		public function add_badge_single_product( $html ) {

			$compatibility_mode_product_pages = get_option( 'wcpb_product_badges_compatibility_mode_product_pages' );

			if ( '' == $compatibility_mode_product_pages ) { // If not blank then badge to selectors set in compatibility mode product pages via JS

				if ( !is_admin() ) {

					global $product;

					if ( !empty( $product ) ) {

						$html = str_replace( '</div>', $this->badge( $product, false ) . '</div>', $html ); // This makes the badge sit within the div of the image, however this means the badge is displayed on each image when in a gallery. We have looked into having the badges concatanated after the </div> instead however when this is done it would require some JS to position the badge within a parent container correctly and there are so many permatations outside our control on the parent container so it is not feasible, such as the parent container identifier to target is dependant on if single/is part of gallery/any other theme specific settings, different CSS rules on the container like zero height (such as in twentytwenty when a gallery present), etc

					}

				}

			}

			return $html;

		}

		public function compatibility_mode_product_pages() {

			if ( is_product() ) {

				global $product;

				$compatibility_mode_product_pages = get_option( 'wcpb_product_badges_compatibility_mode_product_pages' );

				if ( '' !== $compatibility_mode_product_pages ) {

					// This is a temporary div used by the compatibility mode product pages JS to append the badge to the selectors set and then is removed from the DOM

					echo '<div id="wcpb-product-badges-compatibility-mode-product-pages" data-selectors="' . esc_attr( $compatibility_mode_product_pages ) . '">';
					$this->badge( $product, true );
					echo '</div>';

				}

			}

		}

		public function add_safe_style_css( $styles ) {

			// Styles needed for text badges which aren't included in safe_style_css by default (may be in future https://core.trac.wordpress.org/ticket/52310 but this would still need to remain for a while for older WordPress versions until this extension drops support for versions without them)

			$styles[] = 'border-top-left-radius';
			$styles[] = 'border-top-right-radius';
			$styles[] = 'border-bottom-left-radius';
			$styles[] = 'border-bottom-right-radius';

			return $styles;

		}

		public function badge( $product, $echo ) {

			$markup = '';

			if ( !empty( $product ) ) {

				// Global variables

				$compatibility_mode_product_loops_position = get_option( 'wcpb_product_badges_compatibility_mode_product_loops_position' ); // This is not currently used below, but added incase is in future and for completeness
				$compatibility_mode_product_pages = get_option( 'wcpb_product_badges_compatibility_mode_product_pages' );
				$low_stock_amount = get_option( 'woocommerce_notify_low_stock_amount' );

				// Product variables

				$product_id = $product->get_id();
				$product_is_on_sale = $product->is_on_sale();
				$product_is_on_backorder = $product->is_on_backorder();
				$product_category_ids = $product->get_category_ids();
				$product_tag_ids = $product->get_tag_ids();
				$product_shipping_class_id = $product->get_shipping_class_id();
				$product_stock_status = $product->get_stock_status();
				$product_featured = $product->is_featured();
				$product_low_stock_amount = $product->get_low_stock_amount();
				$product_low_stock_threshold = !empty( $product_low_stock_amount ) ? $product_low_stock_amount : $low_stock_amount;
				$product_has_low_stock = $product->managing_stock() && $product->get_stock_quantity() !== null && $product->get_stock_quantity() <= $product_low_stock_threshold && $product->get_stock_quantity() > 0;

				// Get badges

				$badges = get_posts(
					array(
						'numberposts'	=> -1,
						'post_type'		=> 'wcpb_product_badge',
						'post_status'	=> 'publish',
						'order'			=> 'DESC',
						'orderby'		=> 'menu_order',
						'fields'		=> 'ids',
					)
				);

				// Display badges

				if ( !empty( $badges ) ) {

					$multiple_badges_per_product = get_option( 'wcpb_product_badges_multiple_badges_per_product' );

					foreach ( $badges as $badge_id ) {

						$is_visible = false;
						$display = false;
						$visibility = get_post_meta( $badge_id, '_wcpb_product_badges_display_visibility', true );
						$products = get_post_meta( $badge_id, '_wcpb_product_badges_display_products', true );

						if ( 'all' == $visibility ) {

							$is_visible = true;

						} elseif ( 'product_pages' == $visibility ) {

							if ( 'woocommerce_single_product_image_thumbnail_html' == current_filter() || ( is_product() && '' !== $compatibility_mode_product_pages ) ) { // If woocommerce_single_product_image_thumbnail_html is the current filter or if it is the product page and compatibility mode product pages is enabled then is visble (as woocommerce_single_product_image_thumbnail_html might not be being used to display product images, hence why compatibiltiy mode product pages is enabled), note that the latter condition should not effect, for example, where a product block/shortcode of looped products is added to the product page, as the compatibility mode product pages CSS selectors should only target the product image display, not the other selectors in those loops

								$is_visible = true;

							}

						} elseif ( 'product_loops' == $visibility ) {

							if ( 'woocommerce_before_shop_loop_item' == current_action() || 'woocommerce_blocks_product_grid_item_html' == current_filter() ) {

								$is_visible = true;

							}

						}

						if ( true == $is_visible ) {

							if ( 'specific' == $products ) {

								$products_specific_categories = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_categories', true );
								$products_specific_tags = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_tags', true );
								$products_specific_products = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_products', true );
								$products_specific_shipping_classes = get_post_meta( $badge_id, '_wcpb_product_badges_display_products_specific_shipping_classes', true );

								if ( !empty( $products_specific_categories ) ) {

									foreach ( $products_specific_categories as $products_specific_category ) {

										if ( in_array( $products_specific_category, $product_category_ids ) ) {

											$display = true;
											break;

										}

									}

								}

								if ( !empty( $products_specific_tags ) ) {

									foreach ( $products_specific_tags as $products_specific_tag ) {

										if ( in_array( $products_specific_tag, $product_tag_ids ) ) {

											$display = true;
											break;

										}

									}

								}

								if ( !empty( $products_specific_products ) ) {

									if ( in_array( $product_id, $products_specific_products ) ) {

										$display = true;

									}

								}

								if ( !empty( $products_specific_shipping_classes ) ) {

									if ( in_array( $product_shipping_class_id, $products_specific_shipping_classes ) ) {

										$display = true;

									}

								}

							} elseif ( 'sale' == $products ) {

								if ( true == $product_is_on_sale ) {

									$display = true;

								}

							} elseif ( 'non_sale' == $products ) {

								if ( false == $product_is_on_sale ) {

									$display = true;

								}

							} elseif ( 'out_of_stock' == $products ) {

								if ( 'outofstock' == $product_stock_status ) {

									$display = true;

								}

							} elseif ( 'low_stock' == $products ) {

								if ( true == $product_has_low_stock ) {

									$display = true;

								}

							} elseif ( 'on_backorder' == $products ) {

								if ( true == $product_is_on_backorder ) {

									$display = true;

								}

							} elseif ( 'featured' == $products ) {

								if ( true == $product_featured ) {

									$display = true;

								}

							} elseif ( 'custom_rules' == $products ) {

								/**
								 * Filter to determine if a badge should be displayed based on custom rules.
								 *
								 * This filter is applied when the badge display option is set to "Custom rules".
								 * Developers can use this filter to implement custom logic for determining
								 * when a specific badge should be displayed on a product.
								 *
								 * @since 3.4.0
								 *
								 * @param bool      $display   Whether the badge should be displayed. Default false.
								 * @param WC_Product $product   The WooCommerce product object.
								 * @param int       $badge_id  The badge post ID.
								 */
								if ( true == apply_filters( 'wcpb_product_badges_custom_rules_display', false, $product, $badge_id ) ) {

									$display = true;

								}

							} else {

								$display = true;

							}

							if ( true == $display ) {

								$type = get_post_meta( $badge_id, '_wcpb_product_badges_badge_type', true );

								if ( !empty( $type ) ) {

									$classes = array();
									$position = get_post_meta( $badge_id, '_wcpb_product_badges_badge_position', true );
									$offset = get_post_meta( $badge_id, '_wcpb_product_badges_badge_offset_pixels', true );
									$width = get_post_meta( $badge_id, '_wcpb_product_badges_badge_size_width', true );
									$animation = get_post_meta( $badge_id, '_wcpb_product_badges_badge_animation', true );
									$animation_duration = get_post_meta( $badge_id, '_wcpb_product_badges_badge_animation_duration', true );

									$classes[] = 'wcpb-product-badges-badge';
									$classes[] = 'wcpb-product-badges-badge-' . $badge_id;

									if ( 'top_left' == $position ) {

										$classes[] = 'wcpb-product-badges-badge-top-left';

									} elseif ( 'top_right' == $position ) {

										$classes[] = 'wcpb-product-badges-badge-top-right';

									} elseif ( 'bottom_left' == $position ) {

										$classes[] = 'wcpb-product-badges-badge-bottom-left';

									} elseif ( 'bottom_right' == $position ) {

										$classes[] = 'wcpb-product-badges-badge-bottom-right';

									}

									if ( !empty( $animation ) && 'none' !== $animation ) {

										$classes[] = 'wcpb-product-badges-animation-' . $animation;

									}

									$classes = implode( ' ', $classes );

									$animation_style = '';

									if ( !empty( $animation ) && 'none' !== $animation ) {

										$animation_style .= ' --wcpb-product-badges-animation-duration: ' . ( !empty( $animation_duration ) ? $animation_duration . 'ms' : '1000ms' ) . ';';

									}

									$markup .= '<div class="' . ( !empty( $classes ) ? $classes : '' ) . '" style="width: ' . ( '0' == $width ? 'auto' : $width . 'px' ) . '; margin: ' . $offset . 'px;' . $animation_style . '">';

									if ( 'image_library' == $type ) {

										$library_image = get_post_meta( $badge_id, '_wcpb_product_badges_badge_image_library_image', true );

										if ( !empty( $library_image ) ) { // If no library image selected do not display or causes a broken image

											$markup .= '<img class="wcpb-product-badges-badge-img" src="' . esc_url( WCPB_PRODUCT_BADGES_BADGES_URL . $library_image ) . '" alt="' . esc_html__( 'Badge', 'wcpb-product-badges' ) . '">';

										}

									} elseif ( 'image_custom' == $type ) {

										$custom_image = get_the_post_thumbnail_url( $badge_id );
										$custom_image_alt_text = get_post_meta( get_post_thumbnail_id( $badge_id ), '_wp_attachment_image_alt', true );

										if ( !empty( $custom_image ) ) { // If no custom image selected do not display or causes a broken image

											$markup .= '<img class="wcpb-product-badges-badge-img" src="' . esc_url( $custom_image ) . '" alt="' . esc_attr( $custom_image_alt_text ) . '">';

										}

									} elseif ( 'countdown' == $type ) {

										$to = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_countdown_to', true );
										$text_before = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_text_before', true );
										$text_after = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_text_after', true );
										$text_align = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_text_align', true );
										$text_color = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_text_color', true );
										$background_color = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_background_color', true );
										$font_size = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_font_size', true );
										$font_style = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_font_style', true );
										$font_weight = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_font_weight', true );
										$padding_top = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_padding_top', true );
										$padding_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_padding_right', true );
										$padding_bottom = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_padding_bottom', true );
										$padding_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_padding_left', true );
										$border_radius_top_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_border_radius_top_left', true );
										$border_radius_top_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_border_radius_top_right', true );
										$border_radius_bottom_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_border_radius_bottom_left', true );
										$border_radius_bottom_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_countdown_border_radius_bottom_right', true );

										if ( !empty( $to ) ) { // If no countdown to selected do not display

											// str_replace on $to is to replace the space between date and time with a "T", this fixes an issue with iOS safari getting the date: https://stackoverflow.com/a/21884244/1951359
											// No spaces after ; as wp_kses_post strips this anyway

											$markup .= '<div class="wcpb-product-badges-badge-countdown" data-countdown-to="' . str_replace( ' ', 'T', $to ) . '" data-countdown-text-before="' . $text_before . '" data-countdown-text-after="' . $text_after . '" style="' . ( '' !== $text_align ? 'text-align: ' . $text_align . ';' : '' ) . ( '' !== $text_color ? 'color: ' . $text_color . ';' : '' ) . ( '' !== $background_color ? 'background-color: ' . $background_color . ';' : '' ) . ( '' !== $font_size ? 'font-size: ' . $font_size . 'px;' : '' ) . ( '' !== $font_style ? 'font-style: ' . $font_style . ';' : '' ) . ( '' !== $font_weight ? 'font-weight: ' . $font_weight . ';' : '' ) . ( '' !== $padding_top ? 'padding-top: ' . $padding_top . 'px;' : '' ) . ( '' !== $padding_right ? 'padding-right: ' . $padding_right . 'px;' : '' ) . ( '' !== $padding_bottom ? 'padding-bottom: ' . $padding_bottom . 'px;' : '' ) . ( '' !== $padding_left ? 'padding-left: ' . $padding_left . 'px;' : '' ) . ( '' !== $border_radius_top_left ? 'border-top-left-radius: ' . $border_radius_top_left . 'px;' : '' ) . ( '' !== $border_radius_top_right ? 'border-top-right-radius: ' . $border_radius_top_right . 'px;' : '' ) . ( '' !== $border_radius_bottom_right ? 'border-bottom-right-radius: ' . $border_radius_bottom_right . 'px;' : '' ) . ( '' !== $border_radius_bottom_left ? 'border-bottom-left-radius: ' . $border_radius_bottom_left . 'px;' : '' ) . '"></div>';

										}

									} elseif ( 'text' == $type ) {

										$text = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_text', true );
										$text_align = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_text_align', true );
										$text_color = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_text_color', true );
										$background_color = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_background_color', true );
										$font_size = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_font_size', true );
										$font_style = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_font_style', true );
										$font_weight = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_font_weight', true );
										$padding_top = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_padding_top', true );
										$padding_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_padding_right', true );
										$padding_bottom = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_padding_bottom', true );
										$padding_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_padding_left', true );
										$border_radius_top_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_border_radius_top_left', true );
										$border_radius_top_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_border_radius_top_right', true );
										$border_radius_bottom_left = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_border_radius_bottom_left', true );
										$border_radius_bottom_right = get_post_meta( $badge_id, '_wcpb_product_badges_badge_text_border_radius_bottom_right', true );

										if ( !empty( $text ) ) {

											// We don't put spaces after ; as wp_kses_post strips this anyway

											$markup .= '<div style="' . ( '' !== $text_align ? 'text-align: ' . $text_align . ';' : '' ) . ( '' !== $text_color ? 'color: ' . $text_color . ';' : '' ) . ( '' !== $background_color ? 'background-color: ' . $background_color . ';' : '' ) . ( '' !== $font_size ? 'font-size: ' . $font_size . 'px;' : '' ) . ( '' !== $font_style ? 'font-style: ' . $font_style . ';' : '' ) . ( '' !== $font_weight ? 'font-weight: ' . $font_weight . ';' : '' ) . ( '' !== $padding_top ? 'padding-top: ' . $padding_top . 'px;' : '' ) . ( '' !== $padding_right ? 'padding-right: ' . $padding_right . 'px;' : '' ) . ( '' !== $padding_bottom ? 'padding-bottom: ' . $padding_bottom . 'px;' : '' ) . ( '' !== $padding_left ? 'padding-left: ' . $padding_left . 'px;' : '' ) . ( '' !== $border_radius_top_left ? 'border-top-left-radius: ' . $border_radius_top_left . 'px;' : '' ) . ( '' !== $border_radius_top_right ? 'border-top-right-radius: ' . $border_radius_top_right . 'px;' : '' ) . ( '' !== $border_radius_bottom_right ? 'border-bottom-right-radius: ' . $border_radius_bottom_right . 'px;' : '' ) . ( '' !== $border_radius_bottom_left ? 'border-bottom-left-radius: ' . $border_radius_bottom_left . 'px;' : '' ) . '">' . $this->process_text_badge_placeholders( $text, $product ) . '</div>';

										}

									} elseif ( 'code' == $type ) {

										$code = get_post_meta( $badge_id, '_wcpb_product_badges_badge_code_code', true );

										if ( !empty( $code ) ) {

											$markup .= $code;

										}

									}

									$markup .= '</div>';

								}

								if ( 'yes' !== $multiple_badges_per_product ) {

									break;

								}

							}

						}

					}

				}

			}

			if ( true == $echo ) {

				echo wp_kses_post( $markup ); // Note that if considering using wp_kses_post here in future it would strip border-radius from text badges and if using elsewhere such as single product page would strip the <style> tag included conditionally in there

			} else {

				return wp_kses_post( $markup );

			}

		}

		public function process_text_badge_placeholders( $text, $product ) {

			if ( empty( $text ) || empty( $product ) ) {

				return $text;

			}

			// Replace [price] placeholder

			if ( strpos( $text, '[price]' ) !== false ) {

				$price_html = $product->get_price_html();
				$text = str_replace( '[price]', $price_html, $text );

			}

			// Replace [stock] placeholder

			if ( strpos( $text, '[stock]' ) !== false ) {

				$stock_quantity = $product->get_stock_quantity();
				$stock_status = $product->get_stock_status();

				if ( $product->managing_stock() && null !== $stock_quantity ) {

					$stock_text = $stock_quantity;

				} elseif ( 'outofstock' === $stock_status ) {

					$stock_text = __( 'Out of Stock', 'wcpb-product-badges' );

				} elseif ( 'onbackorder' === $stock_status ) {

					$stock_text = __( 'On Backorder', 'wcpb-product-badges' );

				} else {

					$stock_text = __( 'In Stock', 'wcpb-product-badges' );

				}

				$text = str_replace( '[stock]', $stock_text, $text );

			}

			// Replace [sale_price] placeholder (shows only sale price if on sale)

			if ( strpos( $text, '[sale_price]' ) !== false ) {

				if ( $product->is_on_sale() ) {

					$sale_price = $product->get_sale_price();
					$text = str_replace( '[sale_price]', wc_price( $sale_price ), $text );

				} else {

					$text = str_replace( '[sale_price]', '', $text );

				}
			}

			// Replace [regular_price] placeholder

			if ( strpos( $text, '[regular_price]' ) !== false ) {

				$regular_price = $product->get_regular_price();
				$text = str_replace( '[regular_price]', wc_price( $regular_price ), $text );

			}

			// Replace [discount_percentage] placeholder

			if ( strpos( $text, '[discount_percentage]' ) !== false ) {

				if ( $product->is_on_sale() ) {

					$regular_price = (float) $product->get_regular_price();
					$sale_price = (float) $product->get_sale_price();

					if ( $regular_price > 0 ) {

						$discount_percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
						$text = str_replace( '[discount_percentage]', $discount_percentage . '%', $text );

					} else {

						$text = str_replace( '[discount_percentage]', '', $text );

					}
				} else {

					$text = str_replace( '[discount_percentage]', '', $text );

				}

			}

			// Return text with placeholders replaced

			return $text;

		}

	}

}
