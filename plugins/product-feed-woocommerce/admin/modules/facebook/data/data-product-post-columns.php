<?php
/**
 * Facebook post columns
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'wc_get_filename_from_url' ) ) {
	$file_path_header = 'downloadable_files';
} else {
	$file_path_header = 'file_paths';
}


$post_columns = array(

	'id'                           => 'Product Id[id]',
	'title'                        => 'Product Title[title]',
	'description'                  => 'Product Description[description]',
	'link'                         => 'Product URL[link]',
	'mobile_link'                  => 'Product URL[mobile_link]',
	'product_type'                 => 'Product Categories[product_type] ',
	'fb_product_category'          => 'Facebook Product Category[fb_product_category]',
	'google_product_category'             => 'Google Product Category[google_product_category]',
	'image_link'                   => 'Main Image[image_link]',
	'additional_image_link'        => 'Additional Images [additional_image_link]',
	'condition'                    => 'Condition[condition]',
);
/**
 * Filter the product feed CSV columns.
 *
 * @since 1.0.0
 *
 * @param array   $post_columns    Export columns.
 */
return apply_filters( 'wt_pf_product_post_columns', $post_columns );
