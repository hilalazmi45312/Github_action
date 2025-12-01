<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once 'slots.php';

/**
 * Render badge for a product image (block theme)
 *
 * @param string $block_content
 * @param array $block
 * @param WP_Block $instance
 * @return string $block_content
 */
function gtmpb_render_badges( $block_content, $block, $instance ) {
	$badge_img = gtmpb_do_badges( GTMPB_LOCATION_PRODUCT_IMAGE_BLOCK, $block );

	if ( ! empty( $badge_img ) ) {
		$block_content = gtmpb_str_replace_from_last( '</a>', $badge_img . '</a>', $block_content );

		$block_content = new WP_HTML_Tag_Processor( $block_content );
		$block_content->next_tag();
		$block_content->add_class( 'has-gtmpb-image' );
		return $block_content->get_updated_html();
	}

	return $block_content;
}

add_filter( 'render_block_woocommerce/product-image', 'gtmpb_render_badges', 9, 3 );

/**
 * Render badge for a single product page (classic theme & block theme)
 *
 * @param string $html
 * @return string $html
 */
function gtmpb_render_single_product_image_badges( $html ) {
	$badges_html = gtmpb_do_badges( GTMPB_LOCATION_SINGLE );

	if ( $badges_html ) {
		$raw = new WP_HTML_Tag_Processor( $html );
		$raw->next_tag( array( 'class_name' => 'wp-post-image' ) );
		$raw->add_class( 'gtmpb-main-product-image' );
		$html = $raw->get_updated_html();

		// Find the main IMG tag and insert badges after it

		// Find position of "gtmpb-main-product-image"
		$startPos = strpos( $html, 'gtmpb-main-product-image' );
		if ( false === $startPos ) {
			return $html; // Return original HTML if "gtmpb-main-product-image" not found
		}

		// Find position of "/>" after "gtmpb-main-product-image"
		$endPos = strpos( $html, '/>', $startPos );
		if ( false === $endPos ) {
			return $html; // Return original HTML if "/>" not found
		}

		// Insert the string after "/>"
		$insertPos = $endPos + 2;
		return substr_replace( $html, $badges_html, $insertPos, 0 );
	}

	return $html;
}

add_filter( 'woocommerce_single_product_image_thumbnail_html', 'gtmpb_render_single_product_image_badges' );

function gtmpb_render_slot_badges( $block_content, $block, $instance ) {
	if ( ! empty( $block['attrs']['gtmpbSlotName'] ) ) {
		$badge_img = gtmpb_do_badges( GTMPB_LOCATION_SLOT, $block );

		if ( ! empty( $badge_img ) ) {
			$block_content = gtmpb_str_replace_from_last( '</', $badge_img . '</', $block_content );

			$block_content = new WP_HTML_Tag_Processor( $block_content );
			$block_content->next_tag();
			$block_content->add_class( 'has-gtmpb-image' );

			if ( in_array( $block['blockName'], GTMPB_NO_IMG_ALIGMENT_BLOCKS ) ) {
				$block_content->add_class( 'gtmpb-no-image-aligment' );
			}

			return $block_content->get_updated_html();

		} else if ( $block['attrs']['gtmpbHideBlockIfNoBadges'] ) {
			return '';

		}
	}

	return $block_content;
}

add_filter( 'render_block', 'gtmpb_render_slot_badges', 9, 3 );

/**
 * Render badge by echo
 *
 * @param string $location
 * @param array $block
 * @return void
 */
function gtmpb_the_badges( $location = null, $block = null ) {
	echo wp_kses_post( gtmpb_do_badges( $location, $block ) );
}

/**
 * Undocumented function
 *
 * @param string $location
 * @param array $block
 * @return string Html of badges
 */
function gtmpb_do_badges( $location = null, $block = null ) {
	$badges         = get_option( 'gtmpb_badges' );
	$badges_content = '';

	if ( $badges ) {
		foreach ( $badges as $badge ) {
			$badge     = gtmpb_normalize_badge_option( $badge );
			$is_enable = $badge['enable'] && $badge['img_id'];

			// Check rendering locations
			if ( GTMPB_LOCATION_LOOP == $location && ! $badge['render_in_loop'] ) {
				$is_enable = false;
			} else if ( GTMPB_LOCATION_SINGLE == $location && ! $badge['render_in_single'] ) {
				$is_enable = false;
			} else if ( GTMPB_LOCATION_PRODUCT_IMAGE_BLOCK == $location ) {
				if ( ! empty( $block['attrs']['gtmpbAllowBages'] ) ) {
					// In the case the block has set gtmpbAllowBages option
					if ( 'disallow' == $block['attrs']['gtmpbAllowBages'] ) {
						$is_enable = false;
					}

					// For allow, continue

				} else if ( ! $badge['render_in_product_image_block'] ) {
					// In the case AUTO
					$is_enable = false;
				}
			} else if ( GTMPB_LOCATION_SLOT == $location && ! empty( $block['attrs']['gtmpbSlotName'] ) && ! in_array( $block['attrs']['gtmpbSlotName'], $badge['render_in_slots'] ) ) {
				// Skip this badge since it should not being rendered in this slot
				continue;
			}

			/**
			 * Check from hooks
			 *
			 * @since v1.0.0
			 */
			if ( apply_filters( 'gtmpb_is_badge_enabled', $is_enable, $badge ) && apply_filters( 'gtmpb_should_display_badge', true, $badge ) ) {
				$badge_class = array(
					'gtmpb-image',
					'gtmpb-image-align-' . ( isset( $badge['img_alignment'] ) ? $badge['img_alignment'] : 'top-left' ),
					'gtmpb-class-' . sanitize_title( $badge['name'] ),
				);

				if ( is_string( $badge['img_id'] ) ) {
					$img_html = '<img src="' . esc_url( GTMPB_BADGE_PRESET_URI . $badge['img_id'] ) . '">';
				} else {
					$img_html = wp_get_attachment_image( intval( $badge['img_id'] ), 'full' );
				}

				$badge_img = new WP_HTML_Tag_Processor( $img_html );
				$badge_img->next_tag();
				$badge_img->add_class( implode( ' ', $badge_class ) );
				$inline_style = array( $badge_img->get_attribute( 'style' ) );

				if ( isset( $badge['img_order'] ) && '' != $badge['img_order'] ) {
					$inline_style[] = 'z-index:' . intval( $badge['img_order'] );
				}

				if ( ! empty( $badge['img_offset_x'] ) ) {
					$inline_style[] = '--gtmpb-img-offset-x:' . esc_attr( $badge['img_offset_x'] );
				}

				if ( ! empty( $badge['img_offset_y'] ) ) {
					$inline_style[] = '--gtmpb-img-offset-y:' . esc_attr( $badge['img_offset_y'] );
				}

				if ( ! empty( $badge['img_width'] ) ) {
					$badge_img->add_class( 'gtmpb-has-width' );
					$inline_style[] = '--gtmpb-img-width:' . esc_attr( $badge['img_width'] );
				}

				if ( ! empty( $badge['img_height'] ) ) {
					$badge_img->add_class( 'gtmpb-has-height' );
					$inline_style[] = '--gtmpb-img-height:' . esc_attr( $badge['img_height'] );
				}

				$badge_img->set_attribute( 'style', implode( ';', $inline_style ) );
				$badges_content .= $badge_img->get_updated_html();
			}
		}

		return $badges_content;
	}
}

/**
 * Normalize the badge option to make sure the default is existing
 *
 * @param array $badge
 * @return array $badge
 */
function gtmpb_normalize_badge_option( $badge ) {
	$default = array(
		'img_alignment'                 => 'top-left',
		'render_in_loop'                => true,
		'render_in_single'              => true,
		'render_in_product_image_block' => false,
	);

	return wp_parse_args( $badge, $default );
}

/**
 * Add products condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_products( $should, $badge ) {
	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_products'] ) ) {
		return $should;
	}

	$result = false;
	$result |= in_array( get_the_ID(), $badge['condition_products'] );

	return $result;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_products', 10, 2 );

/**
 * Add product tags condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_product_tags( $should, $badge ) {
	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_product_tags'] ) ) {
		return $should;
	}

	$result = false;
	foreach ( $badge['condition_product_tags'] as $term_id ) {
		$result |= has_term( $term_id, 'product_tag' );
	}

	return $result;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_product_tags', 10, 2 );

/**
 * Add product categories condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_product_cats( $should, $badge ) {
	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_product_cats'] ) ) {
		return $should;
	}

	$result = false;
	foreach ( $badge['condition_product_cats'] as $term_id ) {
		$result |= has_term( $term_id, 'product_cat' );
	}

	return $result;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_product_cats', 10, 2 );

/**
 * Add product sale status condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_sale_status( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_sale_status'] ) || is_null( $product ) ) {
		return $should;
	}

	if ( 'sale' == $badge['condition_sale_status'] ) {
		return $product->is_on_sale();
	} else if ( 'not-sale' == $badge['condition_sale_status'] ) {
		return ! $product->is_on_sale();
	}
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_sale_status', 10, 2 );

/**
 * Add product stock status condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_stock_status( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_stock_status'] ) || is_null( $product ) ) {
		return $should;
	}

	$stock_status = $product->get_stock_status();

	return in_array( $stock_status, $badge['condition_stock_status'] );
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_stock_status', 10, 2 );

/**
 * Add product stock min qty condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_stock_min( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || '' === ( isset( $badge['condition_stock_min'] ) ? $badge['condition_stock_min'] : '' ) || is_null( $product ) ) {
		return $should;
	}

	return $product->managing_stock() && intval( $badge['condition_stock_min'] ) <= $product->get_stock_quantity();
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_stock_min', 10, 2 );

/**
 * Add product stock max qty condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_stock_max( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || '' === ( isset( $badge['condition_stock_max'] ) ? $badge['condition_stock_max'] : '' ) || is_null( $product ) ) {
		return $should;
	}

	return $product->managing_stock() && intval( $badge['condition_stock_max'] ) >= $product->get_stock_quantity();
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_stock_max', 10, 2 );

/**
 * Add product discount min condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_discount_min( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || '' === ( isset( $badge['condition_discount_min'] ) ? $badge['condition_discount_min'] : '' ) || is_null( $product ) ) {
		return $should;
	}

	// Get the regular price and sale price
	$regular_price = $product->get_regular_price();
	$sale_price    = $product->get_sale_price();

	if ( empty( $regular_price ) || empty( $sale_price ) ) {
		return $should;
	}

	// Calculate the discount percentage
	$discount_pc = ( ( $regular_price - $sale_price ) / $regular_price ) * 100;

	return $product->managing_stock() && intval( $badge['condition_discount_min'] ) <= $discount_pc;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_discount_min', 10, 2 );

/**
 * Add product discount max condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_discount_max( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || '' === ( isset( $badge['condition_discount_max'] ) ? $badge['condition_discount_max'] : '' ) || is_null( $product ) ) {
		return $should;
	}

	// Get the regular price and sale price
	$regular_price = $product->get_regular_price();
	$sale_price    = $product->get_sale_price();

	if ( empty( $regular_price ) || empty( $sale_price ) ) {
		return $should;
	}

	// Calculate the discount percentage
	$discount_pc = ( ( $regular_price - $sale_price ) / $regular_price ) * 100;

	return $product->managing_stock() && intval( $badge['condition_discount_max'] ) >= $discount_pc;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_discount_max', 10, 2 );

/**
 * Add product rating max condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_rating( $should, $badge ) {
	global $product;

	$min = isset( $badge['condition_rating_min'] ) ? $badge['condition_rating_min'] : '';
	$max = isset( $badge['condition_rating_max'] ) ? $badge['condition_rating_max'] : '';

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || ( '' === $min && '' === $max ) || is_null( $product ) ) {
		return $should;
	}

	// Get product rating
	$rating = $product->get_average_rating();

	// Getting result
	$should = $product->managing_stock();

	if ( '' !== $min ) {
		$should &= floatval( $min ) <= $rating;
	}

	if ( '' !== $max ) {
		$should &= floatval( $max ) >= $rating;
	}

	return $should;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_rating', 10, 2 );

/**
 * Add coupon condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_coupon_valid( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_coupon_valid'] ) || is_null( $product ) ) {
		return $should;
	}

	$coupon     = new WC_Coupon( $badge['condition_coupon_valid'] );
	$is_expired = ( $coupon->get_date_expires() && time() > $coupon->get_date_expires()->getTimestamp() );

	if ( $product ) {
		return $coupon->get_id() && ! $is_expired && $coupon->is_valid_for_product( $product );
	}

	// When no product in the scope
	return false;
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_coupon_valid', 10, 2 );

/**
 * Add featured product status condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_featured_status( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_featured_status'] ) || is_null( $product ) ) {
		return $should;
	}

	if ( 'featured' == $badge['condition_featured_status'] ) {
		return $product->is_featured();
	} else if ( 'not-featured' == $badge['condition_featured_status'] ) {
		return ! $product->is_featured();
	}
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_featured_status', 10, 2 );

/**
 * Add low stock status condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_low_stock_status( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_low_stock_status'] ) || is_null( $product ) ) {
		return $should;
	}

	// Get the stock quantity
	$stock_quantity = $product->get_stock_quantity();

	// Get the low stock threshold
	$low_stock_threshold = $product->get_low_stock_amount();

	// Skip for not managing stock
	if ( ! $product->managing_stock() ) {
		return false;
	}

	if ( is_null( $low_stock_threshold ) || is_null( $stock_quantity ) ) {
		return $should;
	}

	$is_low = $stock_quantity <= $low_stock_threshold;

	if ( 'low' == $badge['condition_low_stock_status'] ) {
		return $is_low;
	} else if ( 'not-low' == $badge['condition_low_stock_status'] ) {
		return ! $is_low;
	}
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_low_stock_status', 10, 2 );

/**
 * Add limit purchase status condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_limit_purchase( $should, $badge ) {
	global $product;

	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_limit_purchase'] ) || is_null( $product ) ) {
		return $should;
	}

	if ( 'limit-1' == $badge['condition_limit_purchase'] ) {
		return $product->get_sold_individually();
	} else if ( 'No limit' == $badge['condition_limit_purchase'] ) {
		return ! $product->get_sold_individually();
	}
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_limit_purchase', 10, 2 );

/**
 * Add date-from condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_date_from( $should, $badge ) {
	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_date_from'] ) ) {
		return $should;
	}

	$current_time       = current_time( 'timestamp' );
	$datetime_timestamp = strtotime( $badge['condition_date_from'] );

	return ( $current_time >= $datetime_timestamp );
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_date_from', 10, 2 );

/**
 * Add date-to condition for rendering
 *
 * @param boolean $should
 * @param array $badge
 * @return boolean $should
 */
function gtmpb_display_badge_condition_date_to( $should, $badge ) {
	// Already hide, no need to check it. this also simulate && operation
	if ( ! $should || empty( $badge['condition_date_to'] ) ) {
		return $should;
	}

	$current_time       = current_time( 'timestamp' );
	$datetime_timestamp = strtotime( $badge['condition_date_to'] );

	return ( $current_time <= $datetime_timestamp );
}

add_filter( 'gtmpb_should_display_badge', 'gtmpb_display_badge_condition_date_to', 10, 2 );

/**
 * Register assets on init
 *
 * @return void
 */
function gtmpb_init() {
	wp_register_style( 'gtmpb-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), '1.0.0', 'all' );
}

add_action( 'init', 'gtmpb_init' );

/**
 * Enqueue assets
 *
 * @return void
 */
function gtmpb_enqueue_scripts() {
	wp_enqueue_style( 'gtmpb-style' );
}

add_action( 'wp_enqueue_scripts', 'gtmpb_enqueue_scripts' );

/**
 * Register settings
 *
 * @return void
 */
function gtmpb_register_settings() {
	register_setting(
		'gtmpb_plugin_settings',
		'gtmpb_badges',
		array(
			'default'      => array(),
			'type'         => 'array',
			'show_in_rest' => array(
				'schema' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'enable'                        => array( 'type' => 'boolean' ),
							'name'                          => array( 'type' => 'string' ),
							'img_id'                        => array( 'type' => array( 'integer', 'string' ) ),
							'img_alignment'                 => array(
								'type'    => 'string',
								'default' => 'top-left',
							),
							'img_order'                     => array( 'type' => 'integer', 'default' => 0 ),
							'img_width'                     => array( 'type' => 'string' ),
							'img_height'                    => array( 'type' => 'string' ),
							'img_offset_x'                  => array( 'type' => 'string' ),
							'img_offset_y'                  => array( 'type' => 'string' ),
							'condition_products'            => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'integer',
								),
							),
							'condition_product_tags'        => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'integer',
								),
							),
							'condition_product_cats'        => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'integer',
								),
							),
							'condition_limit_purchase'      => array( 'type' => 'string' ),
							'condition_sale_status'         => array( 'type' => 'string' ),
							'condition_stock_status'        => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'string',
								),
							),
							'condition_stock_min'           => array(
								'type' => 'number',
							),
							'condition_stock_max'           => array(
								'type' => 'number',
							),
							'condition_discount_min'        => array(
								'type' => 'number',
							),
							'condition_discount_max'        => array(
								'type' => 'number',
							),
							'condition_rating_min'          => array(
								'type' => 'number',
							),
							'condition_rating_max'          => array(
								'type' => 'number',
							),
							'condition_coupon_valid'        => array( 'type' => 'string' ),
							'condition_low_stock_status'    => array( 'type' => 'string' ),
							'condition_featured_status'     => array( 'type' => 'string' ),
							'condition_date_from'           => array(
								'type'   => 'string',
								'format' => 'datetime',
							),
							'condition_date_to'             => array(
								'type'   => 'string',
								'format' => 'datetime',
							),
							'render_in_loop'                => array(
								'type'    => 'boolean',
								'default' => true,
							),
							'render_in_single'              => array(
								'type'    => 'boolean',
								'default' => true,
							),
							'render_in_product_image_block' => array(
								'type'    => 'boolean',
								'default' => true,
							),
							'render_in_slots'               => array(
								'type'  => 'array',
								'items' => array(
									'type' => 'string',
								),
							),
						),
					),
				),
			),
		)
	);
}

add_action( 'init', 'gtmpb_register_settings', 10 );

/**
 * Register settings page
 *
 * @return void
 */
function gtmpb_settings_page() {
	add_submenu_page(
		'edit.php?post_type=product',
		esc_html__( 'Promotion badges', 'product-promotion-badge' ),
		esc_html__( 'Promotion badges', 'product-promotion-badge' ),
		'manage_options',
		'gtmpb-setting-page',
		function () {
			?>
			<div id="gtmpb-setting-page-container"></div>
			<?php
}
	);
}

add_action( 'admin_menu', 'gtmpb_settings_page', 111 );

/**
 * Register assets for settings page
 *
 * @return void
 */
function gtmpb_settings_page_admin_scripts() {
	$current_screen = get_current_screen();

	if ( $current_screen && 'product_page_gtmpb-setting-page' == $current_screen->base ) {
		$asset_path = '/build/settings-page/';
		$asset      = require __DIR__ . $asset_path . 'index.asset.php';
		$asset_js   = plugins_url( $asset_path . 'index.js', __FILE__ );
		$asset_css  = plugins_url( $asset_path . 'style-index.css', __FILE__ );

		wp_enqueue_script(
			'gtmpb-settings-page',
			$asset_js,
			$asset['dependencies'],
			$asset['version']
		);
		wp_set_script_translations( 'gtmpb-settings-page', 'product-promotion-badge' );
		gtmpb_enqueue_settings( 'gtmpb-settings-page' );

		wp_enqueue_style(
			'gtmpb-settings-page',
			$asset_css,
			array( 'wp-components', 'wc-components' ),
			$asset['version']
		);

		// Required for media upload
		wp_enqueue_media();
	}
}

add_action( 'admin_enqueue_scripts', 'gtmpb_settings_page_admin_scripts', 10 );

/**
 * Register assets for block extension
 *
 * @return void
 */
function gtmpb_enqueue_block_editor_assets() {
	$asset_path = '/build/block-extension/';
	$asset      = require __DIR__ . $asset_path . 'index.asset.php';
	$asset_js   = plugins_url( $asset_path . 'index.js', __FILE__ );

	wp_enqueue_script(
		'gtmpb-block-extension',
		$asset_js,
		$asset['dependencies'],
		$asset['version']
	);

	gtmpb_enqueue_settings( 'gtmpb-block-extension' );
}

add_action( 'enqueue_block_editor_assets', 'gtmpb_enqueue_block_editor_assets' );

/**
 * Enqueue additional data for settings page
 *
 * @param string $js_handle
 * @return void
 */
function gtmpb_enqueue_settings( $js_handle ) {
	wp_localize_script( $js_handle, 'gtmpbSettings', array(
		'stockStatusOptions' => wc_get_product_stock_status_options(),
		'badgePresets'       => gtmpb_get_badge_presets(),
		'badgePresetUri'     => GTMPB_BADGE_PRESET_URI,
	) );
}

/**
 * Checking the loop is the main query of the single product page.
 *
 * @return void
 */
function gtmpb_is_product_post() {
	global $post;
	return ( in_the_loop() || is_main_query() ) && get_post_type() == 'product';
}

/**
 * Do the block visibility options
 *
 * @param string $block_content
 * @param array $block
 * @param WP_Block $instance
 * @return void
 */
function gtmpb_do_block_visibility( $block_content, $block, $instance ) {
	if ( ! empty( $block['attrs']['gtmpbEnableVisibility'] ) && gtmpb_is_product_post() ) {
		global $product;

		// Hide first
		$should = false;

		// Check product cats
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityProductCats'] ) ) {
			$result = false;
			foreach ( $block['attrs']['gtmpbVisibilityProductCats'] as $term_id ) {
				$result |= has_term( $term_id, 'product_cat' );
			}

			$should = $result;
		}

		// Check product tags
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityProductTags'] ) ) {
			$result = false;
			foreach ( $block['attrs']['gtmpbVisibilityProductTags'] as $term_id ) {
				$result |= has_term( $term_id, 'product_tag' );
			}

			$should = $result;
		}

		// Check stock statuses
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityStockStatus'] ) && ! is_null( $product ) ) {
			$stock_status = $product->get_stock_status();
			$should       = in_array( $stock_status, $block['attrs']['gtmpbVisibilityStockStatus'] );
		}

		// Check sale statuses
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilitySaleStatus'] ) && ! is_null( $product ) ) {
			if ( 'sale' == $block['attrs']['gtmpbVisibilitySaleStatus'] ) {
				$should = $product->is_on_sale();
			} else if ( 'not-sale' == $block['attrs']['gtmpbVisibilitySaleStatus'] ) {
				$should = ! $product->is_on_sale();
			}
		}

		// Check featured status
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityFeaturedStatus'] ) && ! is_null( $product ) ) {
			if ( 'featured' == $block['attrs']['gtmpbVisibilityFeaturedStatus'] ) {
				$should = $product->is_featured();
			} else if ( 'not-featured' == $block['attrs']['gtmpbVisibilityFeaturedStatus'] ) {
				$should = ! $product->is_featured();
			}
		}

		// Check date from
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityDateFrom'] ) ) {
			$current_time       = current_time( 'timestamp' );
			$datetime_timestamp = strtotime( $block['attrs']['gtmpbVisibilityDateFrom'] );

			$should = ( $current_time >= $datetime_timestamp );
		}

		// Check date to
		if ( ! $should && ! empty( $block['attrs']['gtmpbVisibilityDateTo'] ) ) {
			$current_time       = current_time( 'timestamp' );
			$datetime_timestamp = strtotime( $block['attrs']['gtmpbVisibilityDateTo'] );

			$should = ( $current_time <= $datetime_timestamp );
		}

		// Hide the block if conditions are met
		if ( ! $should ) {
			return '';
		}
	}

	return $block_content;
}

add_filter( 'render_block', 'gtmpb_do_block_visibility', 10, 3 );

// Add wrapper to images in the loop (classic theme)
function gtmpb_classic_image_wrapper_open() {
	ob_start();
}

function gtmpb_classic_image_wrapper_close() {
	$badges_html = gtmpb_the_badges( GTMPB_LOCATION_LOOP );
	$html        = ob_get_clean();

	if ( $badges_html ) {
		$raw = new WP_HTML_Tag_Processor( $html );
		$raw->next_tag( array( 'class_name' => 'attachment-woocommerce_thumbnail' ) );
		$raw->add_class( 'gtmpb-main-product-image' );
		$html = $raw->get_updated_html();

		// Find position of "gtmpb-main-product-image"
		$startPos = strpos( $html, 'gtmpb-main-product-image' );
		if ( false === $startPos ) {
			echo $html;
			return $html; // Return original HTML if "gtmpb-main-product-image" not found
		}

		// Find position of "/>" after "gtmpb-main-product-image"
		$endPos = strpos( $html, '/>', $startPos );
		if ( false === $endPos ) {
			echo $html;
			return $html; // Return original HTML if "/>" not found
		}

		// Insert the string after "/>"
		$insertPos = $endPos + 2;
		echo substr_replace( $html, $badges_html, $insertPos, 0 );
		return;
	}

	echo $html;
}

add_action( 'woocommerce_before_shop_loop_item_title', 'gtmpb_classic_image_wrapper_open', 0 );
add_action( 'woocommerce_before_shop_loop_item_title', 'gtmpb_classic_image_wrapper_close', 20 );

/**
 * Replace string from the beginning of the string
 *
 * @param string $search
 * @param string $replace
 * @param string $subject
 * @return string
 */
function gtmpb_str_replace_from_first( $search, $replace, $subject ) {
	// Find the position of the first occurrence of $search
	$position = strpos( $subject, $search );

	// If $search is found, replace it with $replace
	if ( false !== $position ) {
		$subject = substr_replace( $subject, $replace, $position, strlen( $search ) );
	}

	return $subject;
}

/**
 * Replace string from the end of the string
 *
 * @param string $search
 * @param string $replace
 * @param string $subject
 * @return string
 */
function gtmpb_str_replace_from_last( $search, $replace, $subject ) {
	$pos = strrpos( $subject, $search );
	if ( false === $pos ) {
		return $subject;
	}

	return substr( $subject, 0, $pos ) . $replace . substr( $subject, $pos + strlen( $search ) );
}

/**
 * Get badge presets
 *
 * @return string
 */

function gtmpb_get_badge_presets() {
	return array(
		'badge-best-1'     => array(
			'image'   => 'badge-best-1.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_y'  => '8px',
				'img_width'     => '100px',
			),
		),
		'badge-best-2'     => array(
			'image'   => 'badge-best-2.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_y'  => '8px',
				'img_width'     => '100px',
			),
		),
		'badge-gift-1'     => array(
			'image'   => 'badge-gift-1.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_x'  => '8px',
				'img_offset_y'  => '8px',
				'img_width'     => '48px',
			),
		),
		'badge-interest-1' => array(
			'image'   => 'badge-interest-1.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_x'  => '8px',
				'img_offset_y'  => '8px',
				'img_width'     => '48px',
			),
		),
		'badge-new-1'      => array(
			'image'   => 'badge-new-1.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_x'  => '8px',
				'img_offset_y'  => '8px',
				'img_width'     => '48px',
			),
		),
		'badge-preorder-1' => array(
			'image'   => 'badge-preorder-1.webp',
			'options' => array(
				'img_alignment' => 'top-left',
				'img_offset_x'  => '8px',
				'img_offset_y'  => '8px',
				'img_width'     => '48px',
			),
		),
		'badge-sale-1'     => array(
			'image'   => 'badge-sale-1.webp',
			'options' => array(
				'img_alignment'         => 'top-left',
				'img_offset_x'          => '8px',
				'img_offset_y'          => '8px',
				'img_width'             => '48px',
				'condition_sale_status' => 'sale',
			),
		),
		'badge-sold-1'     => array(
			'image'   => 'badge-sold-1.webp',
			'options' => array(
				'img_alignment'          => 'top-left',
				'img_offset_x'           => '8px',
				'img_offset_y'           => '8px',
				'img_width'              => '48px',
				'condition_stock_status' => 'outofstock',
			),
		),
		'badge-sold-2'     => array(
			'image'   => 'badge-sold-2.webp',
			'options' => array(
				'img_alignment' => 'center-center',
				'img_width'     => '50%',
			),
		),
		'card-bundle-1'    => array(
			'image'   => 'card-bundle-1.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-coupon-1'    => array(
			'image'   => 'card-coupon-1.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-gift-1'      => array(
			'image'   => 'card-gift-1.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-interest-1'  => array(
			'image'   => 'card-interest-1.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-1'      => array(
			'image'   => 'card-sale-1.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-2'      => array(
			'image'   => 'card-sale-2.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-3'      => array(
			'image'   => 'card-sale-3.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-4'      => array(
			'image'   => 'card-sale-4.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-5'      => array(
			'image'   => 'card-sale-5.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'card-sale-6'      => array(
			'image'   => 'card-sale-6.webp',
			'options' => array(
				'img_alignment' => 'bottom-center',
			),
		),
		'frame-gift-1'     => array(
			'image'   => 'frame-gift-1.webp',
			'options' => array(
				'img_alignment' => 'center-center',
				'img_width'     => '100%',
			),
		),
		'frame-new-1'      => array(
			'image'   => 'frame-new-1.webp',
			'options' => array(
				'img_alignment' => 'center-center',
				'img_width'     => '100%',
			),
		),
		'frame-sale-1'     => array(
			'image'   => 'frame-sale-1.webp',
			'options' => array(
				'img_alignment'         => 'center-center',
				'img_width'             => '100%',
				'condition_sale_status' => 'sale',
			),
		),
	);
}
