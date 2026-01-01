<?php
/**
 * Twitter post columns
 *
 * @link
 *
 * @package Webtoffee_Product_Feed_Sync_Pro
 */

if (!defined('WPINC')) {
    exit;
}


$post_columns = array(
                
			'id'                           => 'Product Id[id]',
            'title'                        => 'Product Title[title]',
            'description'                  => 'Product Description[description]',
            'availability'                 => 'Availability[availability]',
            'condition'                    => 'Condition[condition]',
            'price'                        => 'Price[price]',
            'link'                         => 'Product URL[link]',
            'image_link'                   => 'Main Image[image_link]',
            'brand'                        => 'Manufacturer[brand]',
            'gtin'                         => 'GTIN[gtin]',
            'mpn'                          => 'MPN[mpn]',                   
            //'additional_image_link'        => 'Additional Images [additional_image_link]',

);
/**
 * Filter the product feed CSV columns.
 *
 * @since 1.0.0
 *
 * @param array   $post_columns    Export columns.
 */
return apply_filters('wt_pf_twitter_product_post_columns',$post_columns);


