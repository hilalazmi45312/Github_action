<?php
/**
 * Pinterest RSS Product Feed Columns
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if (!defined('WPINC')) {
    exit;
}

$post_columns = array(
    'title' => 'Product Title[title]',
    'description' => 'Product Description[description]',
    'link' => 'Product URL[link]',
    'image_link' => 'Product Image[image]',
    'pubDate' => 'PubDate',
    'guid' => 'guid',
    'identifier_exists' => 'identifier_exists'
);
/**
 * Filter the product feed CSV columns.
 *
 * @since 1.0.0
 *
 * @param array   $post_columns    Export columns.
 */
return apply_filters('wt_pf_pinterest_rss_product_post_columns', $post_columns);

