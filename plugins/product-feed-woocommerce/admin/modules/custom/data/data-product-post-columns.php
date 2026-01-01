<?php
/**
 * Product post columns
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}


$post_columns = array(
	'id' => 'Product Id[id]',
	'title' => 'Product Title[title]',
	'description' => 'Product Description[description]',
	'item_group_id' => 'Item Group Id[item_group_id]',
	'link' => 'Product URL[link]',
	'product_type' => 'Product Categories[product_type] ',
	'google_product_category' => 'Google Product Category[google_product_category]',
	'image_link' => 'Main Image[image_link]',
	'condition' => 'Condition[condition]',
	'availability' => 'Availability[availability]',
	'price' => 'Price[price]',
	'mpn' => 'MPN[mpn]',
	'brand' => 'Brand[brand]',
);
/**
 * Filter the product post columns
 *
 * @param array $post_columns The product post columns
 * @return array The filtered product post columns
 * @since 1.0.0
 */
return apply_filters( 'wt_pf_custom_product_post_columns', $post_columns );
