<?php
/**
 * Google lpi post columns
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_columns = array(
	'store_code' => 'Store Code[store_code]',
	'id' => 'Product Id[id]',
	'quantity' => 'Quantity[quantity]',
	'price' => 'Regular Price[price]',
	'sale_price' => 'Sale Price[sale_price]',
	'sale_price_effective_date' => 'Sale Price Effective Date[sale_price_effective_date]',
	'availability' => 'Stock Status[availability]',
	'pickup_method' => 'Pickup Method[pickup_method]',
	'pickup_sla' => 'Pickup SLA[pickup_sla]',
);
/**
 * Filter the product feed CSV columns.
 *
 * @since 1.0.0
 *
 * @param array   $post_columns    Export columns.
 */
return apply_filters( 'wt_pf_glpi_product_post_columns', $post_columns );
