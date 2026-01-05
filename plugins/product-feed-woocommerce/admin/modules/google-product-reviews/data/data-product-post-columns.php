<?php
/**
 * Data product post columns
 *
 * @package Product Feed for WooCommerce
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}

$post_columns = array(
	'review_id' => 'Review Id[review_id]',
	'reviewer_name' => 'Reviewer Name[name]',
	'reviewer_id' => 'Reviewer Id[reviewer_id]',
	'review_timestamp' => 'Review Timestamp [review_timestamp]',
	'review_title' => 'Review Title[title]',
	'content' => 'Review Content[content]',
	'review_url' => 'Review URL[review_url]',
	'ratings' => 'Ratings[ratings]',
	'gtin' => 'Product GTIN[gtins]',
	'mpn' => 'Product MPN[mpns]',
	'sku' => 'Product SKU[skus]',
	'brand' => 'Product Brands[brands]',
	'asin' => 'Product ASIN[asins]',
	'is_spam' => 'Is Spam[is_spam]',
	'collection_method' => 'Collection Method[collection_method]',
);
/**
 * Wt pf gpr product post columns
 *
 * @param array $post_columns Post columns.
 * @since 1.0.0
 * @return array $post_columns
 */
return apply_filters( 'wt_pf_gpr_product_post_columns', $post_columns );
