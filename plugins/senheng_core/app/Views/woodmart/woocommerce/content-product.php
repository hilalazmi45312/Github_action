<?php
/**
 * The template for displaying product content within loops
 * 
 * Senheng Core Custom Template - Based on WoodMart Theme
 * This template combines WoodMart's functionality with Senheng Core's custom features
 *
 * @package Senheng_Core
 * @version 1.0.0
 */

use XTS\Modules\Layouts\Main;

defined( 'ABSPATH' ) || exit;

global $product;

$is_slider       = woodmart_loop_prop( 'is_slider' );
$is_shortcode    = woodmart_loop_prop( 'is_shortcode' );
$different_sizes = woodmart_loop_prop( 'products_different_sizes' );
$hover           = woodmart_loop_prop( 'product_hover' );
$current_view    = woodmart_loop_prop( 'products_view' );
$shop_view       = woodmart_get_opt( 'shop_view' );

// Ensure visibility.
if ( ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) && ! woodmart_loop_prop( 'is_wishlist' ) ) {
	return;
}

// Increase loop count.
wc_set_loop_prop( 'loop', woodmart_loop_prop( 'woocommerce_loop' ) + 1 );
woodmart_set_loop_prop( 'woocommerce_loop', woodmart_loop_prop( 'woocommerce_loop' ) + 1 );
$woocommerce_loop = woodmart_loop_prop( 'woocommerce_loop' );

// Initialize Senheng Core product data early
$product_id = $product->get_id();
$product_data = SenhengCore\Controllers\ProductLoopController::get_product_data($product_id);
$color_variations = $product_data['color_variations'] ?? [];
$scoin_cashback = $product_data['scoin_cashback'] ?? 0;
$brand_name = $product_data['brand_name'] ?? '';
$fallback_image = $product_data['fallback_image'] ?? '';

// Extra post classes.
$classes = array( 'wd-product', 'senheng-product-card' );

// Add class if product has custom color variations
if ( ! empty( $color_variations ) && count( $color_variations ) > 1 ) {
	$classes[] = 'has-custom-variations';
}

if ( 'info' === $hover && ( woodmart_get_opt( 'new_label' ) && woodmart_is_new_label_needed( $product->get_id() ) ) || woodmart_get_product_attributes_label() || $product->is_on_sale() || $product->is_featured() || ! $product->is_in_stock() ) {
	$classes[] = 'wd-with-labels';
}

// Grid or list style.
if ( ( 'grid' === $shop_view || 'list' === $shop_view ) && ! Main::get_instance()->has_custom_layout( 'shop_archive' ) ) {
	$current_view = $shop_view;
}

if ( $is_slider ) {
	$current_view = 'grid';
}

if ( $is_shortcode ) {
	$current_view = woodmart_loop_prop( 'products_view' );
}

if ( 'base' === $hover || 'fw-button' === $hover ) {
	wp_enqueue_script( 'imagesloaded' );
	woodmart_enqueue_js_script( 'product-hover' );
	woodmart_enqueue_js_script( 'product-more-description' );
}

if ( $current_view == 'list' ) {
	$hover     = 'list';
	$classes[] = 'product-list-item';
	woodmart_set_loop_prop( 'products_columns', 1 );
} else {
	$classes[] = 'wd-hover-' . $hover;
	$classes[] = woodmart_get_old_classes( 'woodmart-hover-' . $hover );

	if ( 'base' === $hover || 'fw-button' === $hover ) {
		$classes[] = 'wd-hover-with-fade';
	}
}

if ( woodmart_loop_prop( 'product_quantity' ) && ( 'quick' === $hover || 'standard' === $hover || 'fw-button' === $hover || 'list' === $hover ) && ! $product->is_sold_individually() && ( 'variable' !== $product->get_type() || 'variation_form' === woodmart_get_opt( 'quick_shop_variable_type' ) ) && $product->is_purchasable() && $product->is_in_stock() ) {
	if ( 'quick' === $hover || 'fw-button' === $hover ) {
		$classes[] = 'wd-quantity-overlap';
	} else {
		$classes[] = 'wd-quantity';
	}
}

if ( ! empty( $different_sizes ) && $different_sizes && in_array( $woocommerce_loop, woodmart_get_wide_items_array( $different_sizes ) ) ) {
	woodmart_set_loop_prop( 'double_size', true );
}

$desktop_columns = woodmart_loop_prop( 'products_columns' );
$tablet_columns  = woodmart_loop_prop( 'products_columns_tablet' );
$mobile_columns  = woodmart_loop_prop( 'products_columns_mobile' );

if ( ! $is_slider ) {
	$grid_different_sizes = woodmart_loop_prop( 'grid_items_different_sizes' );

	if ( ! isset( $different_sizes ) ) {
		$different_sizes = false;
	}

	if ( ( 'grid' === $current_view && $different_sizes && ( in_array( $woocommerce_loop, woodmart_get_wide_items_array( $different_sizes ), true ) ) ) || ( $grid_different_sizes && in_array( $woocommerce_loop, $grid_different_sizes ) ) ) {
		$classes[] = 'wd-wider';
	}

	$classes[] = 'wd-col';
} elseif ( 'base' === $hover || 'fw-button' === $hover ) {
	$classes[] = 'wd-fade-off';
	$classes[] = woodmart_get_old_classes( 'product-in-carousel' );
}

$classes[] = 'product-grid-item';
$classes[] = 'product';

if ( 'yes' === get_option( 'woocommerce_enable_reviews' ) && 'yes' === get_option( 'woocommerce_enable_review_rating' ) && 'base' === $hover && ( $product->get_rating_count() > 0 || woodmart_get_opt( 'show_empty_star_rating' ) ) ) {
	$classes[] = 'has-stars';
}

if ( 'base' === $hover && ! woodmart_have_product_swatches_template() ) {
	$classes[] = 'product-no-swatches';
}

if ( 'default' !== woodmart_loop_prop( 'products_color_scheme' ) ) {
	$classes[] = 'color-scheme-' . woodmart_loop_prop( 'products_color_scheme' );
}

if ( 'no' !== woodmart_loop_prop( 'grid_gallery' ) || ( ! woodmart_loop_prop( 'grid_gallery' ) && woodmart_get_opt( 'grid_gallery' ) ) ) {
	add_action( 'woocommerce_before_shop_loop_item_title', 'woodmart_template_loop_product_thumbnails_gallery', 5 );
}

// Product data already initialized above

// WoodMart swatches compatibility
if ( function_exists( 'woodmart_grid_swatches_attribute' ) && woodmart_grid_swatches_attribute() ) {
    if ( function_exists( 'woodmart_enqueue_inline_style' ) ) {
        woodmart_enqueue_inline_style( 'woo-mod-swatches-base' );
    }
}

?>
<div <?php wc_product_class( $classes, $product ); ?> data-loop="<?php echo esc_attr( $woocommerce_loop ); ?>" data-id="<?php echo esc_attr( $product->get_id() ); ?>" data-fallback-image="<?php echo esc_attr( $fallback_image ); ?>">
	<?php if ( woodmart_grid_swatches_attribute() ) : ?>
		<?php woodmart_enqueue_inline_style( 'woo-mod-swatches-base' ); ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	
	<div class="product-wrapper">
		<!-- Product Image Section -->
		<div class="product-element-top wd-quick-shop">
			<a href="<?php echo esc_url( get_permalink() ); ?>" class="product-image-link">
				<?php
				/**
				 * Hook woocommerce_before_shop_loop_item_title.
				 *
				 * @hooked woodmart_template_loop_product_thumbnails_gallery - 5
				 * @hooked woocommerce_show_product_loop_sale_flash - 10
				 * @hooked woodmart_template_loop_product_thumbnail - 10
				 */
				do_action( 'woocommerce_before_shop_loop_item_title' );
				
				// Add WoodMart sale flash if not already added by hook
				if ( ! has_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash' ) ) {
					woocommerce_show_product_loop_sale_flash();
				}
				?>
				
				<!-- S-Coin Badge is now handled by ProductLoopController -->
			</a>

			<?php
			if ( 'no' === woodmart_loop_prop( 'grid_gallery' ) || ! woodmart_loop_prop( 'grid_gallery' ) ) {
				woodmart_hover_image();
			}
			?>

			<!-- Color Swatches Overlay (always visible) -->
			<?php if ( ! empty( $color_variations ) && count( $color_variations ) > 1 ) : ?>
			<div class="swatches-overlay senheng-color-swatches always-visible">
				<div class="color-swatches" data-product-id="<?php echo esc_attr($product_id); ?>">
					<?php 
					$count = 0;
					foreach ($color_variations as $color_data) {
						$active_class = $count === 0 ? 'active' : '';
						$color = esc_attr($color_data['color'] ?: '#cccccc');
						echo '<span class="color-swatch swatch-item ' . $active_class . '" ';
						echo 'style="background-color: ' . $color . '" ';
						echo 'data-value="' . esc_attr($color_data['slug']) . '" ';
						$image_url = $color_data['variation_image'] ?: $color_data['image'];
						echo 'data-image="' . esc_attr($image_url) . '" ';
						echo 'data-variation-id="' . esc_attr($color_data['variation_id']) . '" ';
						echo 'title="' . esc_attr($color_data['name']) . '">';
						echo '</span>';
						$count++;
					}
					?>
				</div>
			</div>
			<?php endif; ?>

			<!-- Action Buttons -->
			<div class="wrapp-buttons">
				<div class="wd-buttons<?php echo woodmart_get_old_classes( ' woodmart-buttons' ); ?>">
					<div class="wd-add-btn wd-action-btn wd-style-icon wd-add-cart-icon<?php echo woodmart_get_old_classes( ' wd-add-cart-btn woodmart-add-btn' ); ?>"><?php do_action( 'woodmart_add_loop_btn' ); ?></div>
					<?php woodmart_enqueue_js_library( 'tooltips' ); ?>
					<?php woodmart_enqueue_js_script( 'btns-tooltips' ); ?>
					<?php woodmart_quick_view_btn( get_the_ID() ); ?>
					<?php woodmart_add_to_compare_loop_btn(); ?>
					<?php do_action( 'woodmart_product_action_buttons' ); ?>
				</div> 
			</div>
		</div>

		<div class="divider"></div>

		<!-- Product Info Section -->
		<?php if ( woodmart_loop_prop( 'stretch_product_desktop' ) || woodmart_loop_prop( 'stretch_product_tablet' ) || woodmart_loop_prop( 'stretch_product_mobile' ) ) : ?>
		<div class="product-element-bottom">
		<?php endif; ?>
			<div class="product-info">
				<!-- Brand -->
				<?php woodmart_product_brands_links(); ?>
				
				<!-- WoodMart Swatches (only if no custom color variations) -->
				<?php if ( empty( $color_variations ) ) : ?>
					<?php echo \SenhengCore\Controllers\ProductLoopController::custom_swatches_list(); ?>
				<?php endif; ?>
				
				<!-- Product Title -->
				<?php
					/**
					 * woocommerce_shop_loop_item_title hook
					 *
					 * @hooked woocommerce_template_loop_product_title - 10
					 */	
					do_action( 'woocommerce_shop_loop_item_title' );
				?>
				
				<!-- Categories, SKU, Stock Status -->
				<?php
					woodmart_product_categories();
					woodmart_product_sku();
					woodmart_stock_status_after_title();
				?>
				
				<!-- Price -->
				<div class="product-price">
					<?php
						/**
						 * woocommerce_after_shop_loop_item_title hook
						 *
						 * @hooked woocommerce_template_loop_rating - 5
						 * @hooked woocommerce_template_loop_price - 10
						 */
						remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
						do_action( 'woocommerce_after_shop_loop_item_title' );
						add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
					?>
				</div>
			</div>
			
			<!-- Rating (positioned at bottom) -->
			<div class="product-rating">
				<?php if ( 0 < $product->get_average_rating() || woodmart_get_opt( 'show_empty_star_rating' ) ) : ?>
					<?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); ?>
				<?php endif; ?>
			</div>

			<?php do_action( 'woocommerce_after_shop_loop_item' ); ?>

			<?php if ( woodmart_loop_prop( 'progress_bar' ) ): ?>
				<?php woodmart_stock_progress_bar(); ?>
			<?php endif ?>

			<?php if ( woodmart_loop_prop( 'timer' ) ): ?>
				<?php woodmart_product_sale_countdown( array( 'products_hover' => 'icons' ) ); ?>
			<?php endif ?>
		<?php if ( woodmart_loop_prop( 'stretch_product_desktop' ) || woodmart_loop_prop( 'stretch_product_tablet' ) || woodmart_loop_prop( 'stretch_product_mobile' ) ) : ?>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
// Include styles and scripts for AJAX requests
if (wp_doing_ajax() || (function_exists('woodmart_is_woo_ajax') && woodmart_is_woo_ajax())) {
    // Only include once per AJAX request
    static $ajax_assets_included = false;
    if (!$ajax_assets_included) {
        $ajax_assets_included = true;
        
        // Assets are properly enqueued via ProductLoopController::enqueue_styles()
        // No need to include CSS/JS directly in template
        
        // Ensure WoodMart swatches styles are available for AJAX requests
        if (function_exists('woodmart_enqueue_inline_style')) {
            woodmart_enqueue_inline_style('woo-mod-swatches-base');
        }
    }
}
?>