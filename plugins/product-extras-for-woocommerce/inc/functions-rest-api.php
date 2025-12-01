<?php
/**
 * Functions for Rest API
 * @since 3.11.5
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supplemntal code for Add-Ons Ultimate REST API integration
 * Added filters pewc_rest_api_add_product_extras and pewc_rest_api_disable_routes on 3.11.6
 */
function pewc_rest_api_init(){
	if ( ! class_exists( 'WooCommerce' ) ) {
		return; // we need WooCommerce
	}

	// adds the product_extras field to the values returned when you call /wp-json/wc/v3/products/<product_id>
	// disabled by default
	if ( apply_filters( 'pewc_rest_api_add_product_extras', false ) ) {
		register_rest_field( 'product', 'product_extras', array(
		'get_callback' => function( $product ) {
			if( function_exists( 'pewc_get_extra_fields' ) ){
				$extra_fields = pewc_get_extra_fields($product['id']);
				foreach($extra_fields as $group_id => $group){
					foreach($group['items'] as $item_id => $item){
						//Swap image IDs for URLs
						if($item['field_type'] == 'select-box'){
						foreach($item['field_options'] as $option_id => $option){
							$image_url = isset($option['image']) ? wp_get_attachment_image_src($option['image'], 'full')[0] : false;
							if(isset($option['image'])){
								$extra_fields[$group_id]['items'][$item_id]['field_options'][$option_id]['image'] = $image_url;
							}
						}
						}
					}
				}
				return $extra_fields;
			} else {
				return false;
			}
		},
		'schema' => array(
			'description' => __( 'Product extras' ),
			'type'        => 'array'
		),
		) );
	}

	// only give REST API access to migrated sites?
	// enabled by default
	if ( pewc_has_migrated() && ! apply_filters( 'pewc_rest_api_disable_routes', false ) ) {
		// initiate new REST API for AOU. allows users to CREATE, UPDATE, DELETE groups and fields
		require_once PEWC_DIRNAME . '/classes/rest-api/class-pewc-rest-api-groups-controller.php';
		require_once PEWC_DIRNAME . '/classes/rest-api/class-pewc-rest-api-fields-controller.php';
		if ( class_exists( 'PEWC_REST_API_Groups_Controller' ) && class_exists( 'PEWC_REST_API_Fields_Controller' ) ) {
			$PEWC_REST_API_Groups_Controller = new PEWC_REST_API_Groups_Controller();
			$PEWC_REST_API_Groups_Controller->register_routes();
			$PEWC_REST_API_Fields_Controller = new PEWC_REST_API_Fields_Controller();
			$PEWC_REST_API_Fields_Controller->register_routes();
		}
	}

}
add_action( 'rest_api_init', 'pewc_rest_api_init' );

/**
  * Save add-on data to order
  * Added filter pewc_rest_api_set_order_item on 3.11.6
  */
function pewc_rest_set_order_item( $item, $posted ){
	if( function_exists( 'pewc_add_custom_data_to_order' ) && apply_filters( 'pewc_rest_api_set_order_item', false ) ){
		$item->product_extras['product_extras'] = $posted['extras'];
		foreach($item->product_extras['product_extras']['groups'] as $group_id => $fields){
			foreach($fields as $field_id => $field){
				$item['total'] += $field['price'] * $item['quantity'];
			}
		}
		pewc_add_custom_data_to_order($item, false, false, false);
	}
}
add_action( 'woocommerce_rest_set_order_item', 'pewc_rest_set_order_item', 10, 2);

/**
  * Save data when order is created
  * Added filter pewc_rest_api_insert_shop_order_object on 3.11.6
  */
function pewc_rest_insert_shop_order_object( $order ){
	if( function_exists( 'pewc_create_product_extra' ) && apply_filters( 'pewc_rest_api_insert_shop_order_object', false ) ){
		pewc_create_product_extra( $order->get_id() );
	}
}
add_action( 'woocommerce_rest_insert_shop_order_object', 'pewc_rest_insert_shop_order_object' );
