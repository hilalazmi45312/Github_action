<?php
/**
 * Single Product Rating Template
 *
 * Custom template to display product ratings with consistent "customer review" text
 * Used specifically for Elementor wd_single_product_rating widget
 *
 * @package SenhengCore
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $product;

if ( ! wc_review_ratings_enabled() ) {
	return;
}

$rating_count = $product->get_rating_count();
$review_count = $product->get_review_count();
$average      = $product->get_average_rating();
$review_link  = woodmart_loop_prop( 'is_quick_view' ) ? get_permalink( $product->get_id() ) . '#reviews' : '#reviews';
?>
<div class="woocommerce-product-rating">
	<?php 
	// Use our custom rating override which handles both stars and count
	echo wc_get_rating_html( $average, $rating_count ); // phpcs:ignore 
	?>
</div>