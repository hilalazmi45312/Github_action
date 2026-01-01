<?php
/**
 * Idealo post columns
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'WPINC' ) ) {
	exit;
}


$post_columns = array(
	'sku' => 'Product SKU[sku]',
	'title' => 'Product Title[title]',
	'price' => 'Price [price]',
	'description' => 'Product Description[description]',
	'url' => 'Product URL[url]',
	'categoryPath' => 'Product Categories[categoryPath]',
	'imageUrls' => 'Image URLs[imageUrls]',
	'basePrice' => 'Base Price [basePrice]',
);
/**
 * Filter the product feed CSV columns.
 *
 * @since 1.0.0
 *
 * @param array   $post_columns    Export columns.
 */
return apply_filters( 'wt_pf_idealo_product_post_columns', $post_columns );
