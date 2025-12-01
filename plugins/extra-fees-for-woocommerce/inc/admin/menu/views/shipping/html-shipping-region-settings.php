<?php
/* Admin HTML Fee Settings for Shipping */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="efw-shipping-fee-contents-title">
<h2><?php echo esc_html( $shipping_zone['zone_name'] . '(' . $shipping_zone['formatted_zone_location'] . ')' ); ?></h2>
<?php
foreach ($shipping_zone['shipping_methods'] as $shipping_method) {

	if ('no' == $shipping_method->enabled) {
		continue;
	}

	$method_title = $shipping_method->method_title;
	$shipping_method_key = $shipping_method->id . '_' . $shipping_method->instance_id;

	$enable                      = get_option( 'efw_enable_' . $shipping_method_key );
	$user_filter_type            = get_option( 'efw_shipping_user_filter_type_' . $shipping_method_key );
	$include_users               = get_option( 'efw_shipping_include_users_' . $shipping_method_key );
	$exclude_users               = get_option( 'efw_shipping_exclude_users_' . $shipping_method_key );
	$user_roles                  = efw_get_user_roles();
	$include_user_roles          = get_option( 'efw_shipping_include_userroles_' . $shipping_method_key );
	$exclude_user_roles          = get_option( 'efw_shipping_exclude_userroles_' . $shipping_method_key );
	$product_filter_type         = get_option( 'efw_shipping_product_filter_type_' . $shipping_method_key );
	$include_products            = get_option( 'efw_shipping_include_products_' . $shipping_method_key );
	$exclude_products            = get_option( 'efw_shipping_exclude_products_' . $shipping_method_key );
	$categories                  = efw_get_wc_categories();
	$include_categories          = get_option( 'efw_shipping_include_categories_' . $shipping_method_key );
	$exclude_categories          = get_option( 'efw_shipping_exclude_categories_' . $shipping_method_key );
	$include_additional_products            = get_option( 'efw_shipping_include_additional_products_' . $shipping_method_key );
	$exclude_additional_products            = get_option( 'efw_shipping_exclude_additional_products_' . $shipping_method_key );
	$fee_based_on             = get_option( 'efw_shipping_fee_based_on_' . $shipping_method_key );
	$from_date                   = get_option( 'efw_shipping_from_date_' . $shipping_method_key );
	$to_date                     = get_option( 'efw_shipping_to_date_' . $shipping_method_key );
	$selected_week_days       = get_option( 'efw_shipping_weekdays_for_' . $shipping_method_key );
	$fee_text                    = get_option( 'efw_shipping_fee_text_' . $shipping_method_key );
	$fee_description = get_option( 'efw_shipping_fee_description_' . $shipping_method_key );
	$tax_class                   = get_option( 'efw_shipping_tax_class_' . $shipping_method_key );
	$fee_type                    = get_option( 'efw_shipping_fee_type_' . $shipping_method_key );
	$percentage_fee_type      = get_option( 'efw_percentage_fee_type_for_' . $shipping_method_key );
	$percentage_type             = get_option( 'efw_percentage_based_on_' . $shipping_method_key );
	$fixed_value                 = get_option( 'efw_shipping_fixed_value_' . $shipping_method_key );
	$add_fixed_fee_on         = get_option( 'efw_add_fixed_for_' . $shipping_method_key );
	$percentage_value            = get_option( 'efw_shipping_percentage_value_' . $shipping_method_key );
	$minimum_fee_value           = get_option( 'efw_shipping_minimum_fee_value_' . $shipping_method_key );
	$maximum_fee_value           = get_option( 'efw_shipping_maximum_fee_value_' . $shipping_method_key );
	$minimum_restriction_value   = get_option( 'efw_shipping_fee_minimum_restriction_value_' . $shipping_method_key );
	$maximum_restriction_value   = get_option( 'efw_shipping_fee_maximum_restriction_value_' . $shipping_method_key);

	include EFW_PLUGIN_PATH . '/inc/admin/menu/views/shipping/html-shipping-settings.php';
}
?>
</div>
<?php
