<?php
/**
 * Functions for the settings
 * @since 3.7.6
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the general settings
 */
function pewc_get_general_settings() {

	$settings = array(

		'general_section_title' => array(
			'name'     => __( 'General', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_general_title'
		),
		'pewc_hide_zero' => array(
			'name'			=> __( 'Hide zero prices', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'		=> true,
			'desc'			=> __( 'Hide prices in the cart for extras which don\'t have a cost.', 'pewc' ),
			'id'			=> 'pewc_hide_zero',
			'default'		=> 'no',
			'std'			=> 'no'
		),
		// 3.26.18
		'pewc_hide_field_prices' => array(
			'name'			=> __( 'Hide field prices', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'		=> true,
			'desc'			=> __( 'Hide field prices in the cart for extras with only one option value (e.g. Radio, Select). Avoids displaying add-on prices twice.', 'pewc' ),
			'id'			=> 'pewc_hide_field_prices',
			'default'		=> 'no',
			'std'			=> 'no'
		),
		'pewc_ignore_tax' => array(
			'name'			=> __( 'Ignore tax setting', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'	=> true,
			'desc'			=> __( 'Ignore the WooCommerce "Display prices in the shop" setting which determines whether prices are displaying including or excluding tax.', 'pewc' ),
			'id'				=> 'pewc_ignore_tax',
			'default'		=> 'no',
			'std'				=> 'no'
		),
		'pewc_tax_suffix' => array(
			'name'		=> __( 'Display tax suffix', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Display the tax suffix after all add-on price fields.', 'pewc' ),
			'id'			=> 'pewc_tax_suffix',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_price_separator' => array(
			'name'		=> __( 'Price separator', 'pewc' ),
			'type'		=> 'text',
			'desc_tip'	=> true,
			'desc'		=> __( 'Define a symbol to separate the add-on label from the add-on price.', 'pewc' ),
			'id'			=> 'pewc_price_separator',
			'default'	=> '+',
			'std'			=> '+'
		),
		'pewc_enable_cart_editing' => array(
			'name'		=> __( 'Enable editing', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Allow users to edit the add-ons in products that have already been added to the cart.', 'pewc' ),
			'id'			=> 'pewc_enable_cart_editing',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_enable_tooltips' => array(
			'name'		=> __( 'Enable tooltips', 'pewc' ),
			'type'		=> 'select',
			'desc_tip'	=> true,
			'desc'		=> __( 'Display add-on field descriptions in interactive elements.', 'pewc' ),
			'id'		=> 'pewc_enable_tooltips',
			'default'	=> 'no',
			'options'	=> array(
				'no'		=> __( 'Disabled', 'pewc' ),
				'yes'		=> __( 'Standard', 'pewc' ),
				'enhanced'	=> __( 'Enhanced', 'pewc' ),
			)
		),
		// 'pewc_enhanced_tooltips' => array(
		// 	'name'		=> __( 'Enhanced tooltips', 'pewc' ),
		// 	'type'		=> 'checkbox',
		// 	'desc_tip'	=> true,
		// 	'desc'		=> __( 'Enhanced tooltips pull content from posts into a modal box.', 'pewc' ),
		// 	'id'		=> 'pewc_enhanced_tooltips',
		// 	'default'	=> 'no',
		// 	'std'		=> 'no'
		// ),
		'pewc_update_price_label' => array(
			'name'		=> __( 'Update price label', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Update the price label on product pages when price changes.', 'pewc' ),
			'id'			=> 'pewc_update_price_label',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_dequeue_scripts' => array(
			'name'		=> __( 'Dequeue scripts', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Dequeue scripts and stylesheets on non-product pages.', 'pewc' ),
			'id'			=> 'pewc_dequeue_scripts',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_cart_group_titles' => array(
			'name'		=> __( 'Display group titles in cart', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Show the group title in the cart.', 'pewc' ),
			'id'			=> 'pewc_cart_group_titles',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_enable_clear_all_button' => array(
			'name'		=> __( 'Enable Clear All Options button', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Display the Clear All Options button on the product page, which allows customers to clear all Add-On field values. Requires Optimise conditions.', 'pewc' ),
			'id'			=> 'pewc_enable_clear_all_button',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_empty_fields_cart' => array(
			'name'		=> __( 'Exclude empty fields in cart', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'If a field does not have a value, do not include it in cart meta', 'pewc' ),
			'id'			=> 'pewc_hide_empty_fields_cart',
			'default'	=> 'yes'
		),
		'general_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_general_title'
		),

		'pewc_progress_title' => array(
			'name'     => __( 'Progress bar', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_progress_title'
		),
		'pewc_progress_bar' => array(
			'name'		=> __( 'Display progress bar', 'pewc' ),
			'type'		=> 'select',
			'desc_tip'	=> true,
			'desc'		=> __( 'Show a graphic progress bar for users to see how many fields they have completed.', 'pewc' ),
			'id'		=> 'pewc_progress_bar',
			'default'	=> 'no',
			'options'	=> array(
				'no'		=> __( 'Disabled', 'pewc' ),
				'fields'	=> __( 'Fields', 'pewc' ),
				'groups'	=> __( 'Groups', 'pewc' ),
			)
		),
		'pewc_progress_required' => array(
			'name'		=> __( 'Required fields only', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Only check required fields for progress bar update.', 'pewc' ),
			'id'		=> 'pewc_progress_required',
			'default'	=> 'no'
		),
		'pewc_progress_bar_layout' => array(
			'name'		=> __( 'Progress bar layout', 'pewc' ),
			'type'		=> 'select',
			'desc_tip'	=> true,
			'desc'		=> __( 'Choose a style for your progress bar.', 'pewc' ),
			'id'		=> 'pewc_progress_bar_layout',
			'default'	=> 'bar',
			'options'	=> array(
				'bar'		=> __( 'Bar', 'pewc' ),
				'steps'		=> __( 'Steps', 'pewc' )
			)
		),
		'progress_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_progress_title'
		),

		'pewc_global_title' => array(
			'name'     => __( 'Global', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_global_title'
		),
		'pewc_enable_groups_as_post_types' => array(
			'name'		=> __( 'Display groups as post type', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Display groups as custom post types, not on single page.', 'pewc' ),
			'id'			=> 'pewc_enable_groups_as_post_types',
			'default'	=> 'yes',
			'std'			=> 'yes'
		),
		'global_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_global_title'
		),

		'pewc_conditions_title' => array(
			'name'     => __( 'Conditions', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_conditions_title'
		),
		'pewc_reset_fields' => array(
			'name'		=> __( 'Reset field values', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Reset field values to null when fields are hidden through a condition.', 'pewc' ),
			'id'			=> 'pewc_reset_fields',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_disable_hidden_fields' => array(
			'name'		=> __( 'Display hidden fields as disabled', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'If a field is hidden by a condition, select this option to make it visible but disabled', 'pewc' ),
			'id'		=> 'pewc_disable_hidden_fields',
			'default'	=> 'no'
		),
		'gconditions_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_conditions_title'
		),

		'labels_section_title' => array(
			'name'     => __( 'Labels', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_labels_title'
		),
		// Price labelling
		'pewc_price_label' => array(
			'name'			=> __( 'Price label', 'pewc' ),
			'type'			=> 'text',
			'desc_tip'	=> true,
			'desc'			=> __( 'Additional or replacement text for the price', 'pewc' ),
			'id'				=> 'pewc_price_label'
		),
		'pewc_price_display' => array(
			'name'			=> __( 'Price label display', 'pewc' ),
			'type'			=> 'select',
			'desc_tip'	=> true,
			'desc'			=> __( 'Decide where to display the label', 'pewc' ),
			'id'				=> 'pewc_price_display',
			'default'		=> 'before',
			'std'				=> 'before',
			'options'     => array(
				'before'			=> __( 'Before price', 'pewc' ),
				'after'				=> __( 'After price', 'pewc' ),
				'hide'				=> __( 'Hide price', 'pewc' )
			)
		),
		// Subtotals
		'pewc_show_totals' => array(
			'name'			=> __( 'Display totals fields', 'pewc' ),
			'type'			=> 'select',
			'desc_tip'	=> true,
			'desc'			=> __( 'Decide how to display totals fields on product pages', 'pewc' ),
			'id'				=> 'pewc_show_totals',
			'default'		=> 'all',
			'std'				=> 'all',
			'options'     => array(
				'all'           => __( 'Show totals', 'woocommerce' ),
				'none'          => __( 'Hide totals', 'woocommerce' ),
				'total'    			=> __( 'Total only', 'woocommerce' ),
			),
		),
		'pewc_product_total_label' => array(
			'name'			=> __( 'Product total label', 'pewc' ),
			'type'			=> 'text',
			'desc_tip'	=> true,
			'desc'			=> __( 'The label for the Product total', 'pewc' ),
			'id'				=> 'pewc_product_total_label',
			'default'		=> __( 'Product total', 'pewc' ),
		),
		'pewc_options_total_label' => array(
			'name'			=> __( 'Options total label', 'pewc' ),
			'type'			=> 'text',
			'desc_tip'	=> true,
			'desc'			=> __( 'The label for the Options total', 'pewc' ),
			'id'				=> 'pewc_options_total_label',
			'default'		=> __( 'Options total', 'pewc' ),
		),
		'pewc_flatrate_total_label' => array(
			'name'			=> __( 'Flat rate total label', 'pewc' ),
			'type'			=> 'text',
			'desc_tip'	=> true,
			'desc'			=> __( 'The label for the Flat rate total', 'pewc' ),
			'id'				=> 'pewc_flatrate_total_label',
			'default'		=> __( 'Flat rate total', 'pewc' ),
		),
		'pewc_grand_total_label' => array(
			'name'			=> __( 'Grand total label', 'pewc' ),
			'type'			=> 'text',
			'desc_tip'	=> true,
			'desc'			=> __( 'The label for the Grand total', 'pewc' ),
			'id'				=> 'pewc_grand_total_label',
			'default'		=> __( 'Grand total', 'pewc' ),
		),
		'labels_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_labels_title'
		),

		'roles_section_title' => array(
			'name'     => __( 'Role-based Pricing', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_roles_title'
		),
		'pewc_role_prices' => array(
			'name'			=> __( 'Roles', 'pewc' ),
			'type'			=> 'multiselect',
			'desc_tip'	=> true,
			'desc'			=> __( 'Enter the roles that you would like to have different add-on prices for.', 'pewc' ),
			'id'				=> 'pewc_role_prices',
			'options'		=> pewc_get_all_roles(),
			'class'			=> 'pewc-multiselect'
		),
		'roles_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_roles_title'
		),

		'optimise_section_title' => array(
			'name'     => __( 'Optimisations', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_optimise_title'
		),
		'pewc_optimise_calculations' => array(
			'name'			=> __( 'Optimise calculations', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'		=> true,
			'desc'			=> __( 'This will enable an alternative method for checking calculations which might improve page performance.', 'pewc' ),
			'id'			=> 'pewc_optimise_calculations',
			'default'		=> 'yes'
		),
		'pewc_optimise_conditions' => array(
			'name'			=> __( 'Optimise conditions', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'		=> true,
			'desc'			=> __( 'This will enable an alternative method for checking conditions which might improve page performance.', 'pewc' ),
			'id'			=> 'pewc_optimise_conditions',
			'default'		=> 'yes'
		),
		'optimise_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_optimise_title'
		),
		'beta_section_title' => array(
			'name'     => __( 'Beta', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_beta_title'
		),
		'pewc_beta_testing' => array(
			'name'			=> __( 'Beta testing', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'	=> true,
			'desc'			=> __( 'Opt in to beta testing the plugin. You should only choose this option on a staging or development site - don\'t enable this on your live site.', 'pewc' ),
			'id'				=> 'pewc_beta_testing',
			'default'		=> 'no',
			'std'				=> 'no'
		),
		'beta_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_beta_title'
		),

	);

	return apply_filters( 'pewc_filter_settings', $settings );

}


/**
 * Get the date settings
 */
function pewc_get_date_settings() {

	$settings = array(

		'pewc_date_title' => array(
			'name'     => __( 'Date fields', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_date_title'
		),
		'pewc_disable_days' => array(
			'name'		=> __( 'Enable days of the week', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable the option to disable specific days of the week.', 'pewc' ),
			'id'			=> 'pewc_disable_days',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_offset_days' => array(
			'name'		=> __( 'Enable offset', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable an option to offset the minimum date by a set number of days.', 'pewc' ),
			'id'			=> 'pewc_offset_days',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_blocked_dates' => array(
			'name'		=> __( 'Enable blocked dates', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable a field to enter dates that are unavailable.', 'pewc' ),
			'id'			=> 'pewc_blocked_dates',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'date_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_date_title'
		),

	);

	return apply_filters( 'pewc_date_settings', $settings );

}

/**
 * Get the uploads settings
 */
function pewc_get_uploads_settings() {

	$settings = array(

		'general_uploads_title' => array(
			'name'     => __( 'Uploads', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'general_uploads_title'
		),
		'pewc_require_log_in' => array(
			'name'		=> __( 'Users must be logged in to upload', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'For security reasons, it is strongly recommended that you require users to be logged in before allowing them to upload files.', 'pewc' ),
			'id'		=> 'pewc_require_log_in',
			'default'	=> 'no',
			'std'		=> 'no'
		),
		'pewc_max_upload' => array(
			'name'		=> __( 'Max file size (MB)', 'pewc' ),
			'type'		=> 'number',
			'desc_tip'	=> true,
			'desc'		=> __( 'The max file size for uploads (in MB)', 'pewc' ),
			'id'		=> 'pewc_max_upload',
			'default'	=> '1',
		),
		'pewc_enable_pdf_uploads' => array(
			'name'		=> __( 'Enable PDF uploads', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Allow users to upload PDFs.', 'pewc' ),
			'id'		=> 'pewc_enable_pdf_uploads',
			'default'	=> 'no'
		),
		'pewc_enable_dropzonejs' => array(
			'name'		=> __( 'Enable AJAX uploader', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Add uploaded images via AJAX.', 'pewc' ),
			'id'		=> 'pewc_enable_dropzonejs',
			'default'	=> 'yes'
		),
		'pewc_retain_dropzone' => array(
			'name'		=> __( 'Retain Upload Graphic', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Retain the upload graphic even after a file has been uploaded.', 'pewc' ),
			'id'		=> 'pewc_retain_dropzone',
			'default'	=> 'no',
			'std'		=> 'no'
		),
		'pewc_disable_add_to_cart' => array(
			'name'		=> __( 'Disable Add to Cart button', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Disable the add to cart button while files are being uploaded.', 'pewc' ),
			'id'		=> 'pewc_disable_add_to_cart',
			'default'	=> 'no',
			'std'		=> 'no'
		),
		'pewc_email_images' => array(
			'name'		=> __( 'Attach uploads to emails', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Add uploaded images to new order emails.', 'pewc' ),
			'id'		=> 'pewc_email_images',
			'default'	=> 'no',
			'std'		=> 'no'
		),
		'pewc_rename_uploads' => array(
			'name'		=> __( 'Rename uploads', 'pewc' ),
			'type'		=> 'text',
			'desc_tip'	=> true,
			'desc'		=> __( 'Rename uploads using the following tags: {original_file_name}, {order_number}, {date}, {product_id}, {product_sku}, {group_id}, {field_id}', 'pewc' ),
			'id'		=> 'pewc_rename_uploads',
			'default'	=> '',
		),
		'pewc_organise_uploads' => array(
			'name'		=> __( 'Organise uploads by order', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Organise uploads into unique folders for each order', 'pewc' ),
			'id'			=> 'pewc_organise_uploads',
			'default'	=> 'no',
		),

		'pewc_upload_mime_types' => array(
            'name'          => __( 'File types', 'pewc' ),
            'type'          => 'multiselect',
            'desc_tip'  	=> true,
            'desc'          => __( 'Enter the permitted file types. Please remember that allowing users to upload files to your server carries a security risk, so please use this feature with caution.', 'pewc' ),
            'id'            => 'pewc_upload_mime_types',
            'options'       => wp_get_mime_types(),
            'class'         => 'pewc-multiselect',
            'default'       => array( 'jpg|jpeg|jpe', 'png', 'gif' )
        ),

		'uploads_section_end' => array(
			'type' => 'sectionend',
			'id' => 'general_uploads_title'
		),

	);

	return apply_filters( 'pewc_uploads_settings', $settings );

}

/**
 * Get the image settings
 * @since 3.16.0
 */
function pewc_get_image_settings() {

	$settings = array(

		'pewc_image_title' => array(
			'name'     => __( 'Swatches settings', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_image_title'
		),
		// 'pewc_circular_swatches' => array(
		// 	'name'		=> __( 'Enable circular swatches', 'pewc' ),
		// 	'type'		=> 'checkbox',
		// 	'desc_tip'	=> true,
		// 	'desc'		=> __( 'Display colour swatches as round not rectangular.', 'pewc' ),
		// 	'id'		=> 'pewc_circular_swatches',
		// 	'default'	=> 'no',
		// 	'std'		=> 'no'
		// ),
		'pewc_replace_image' => array(
			'name'		=> __( 'Replace main image', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this option to replace the main product image with the selected image swatch.', 'pewc' ),
			'id'		=> 'pewc_replace_image',
			'default'	=> 'no',
			'std'		=> 'no'
		),
		// 'pewc_swatch_width' => array(
		// 	'name'		=> __( 'Swatch width', 'pewc' ),
		// 	'type'		=> 'number',
		// 	'desc_tip'	=> true,
		// 	'desc'		=> __( 'Set a swatch width to override the standard thumbnail size.', 'pewc' ),
		// 	'id'		=> 'pewc_swatch_width'
		// ),
		// 'pewc_swatch_height' => array(
		// 	'name'		=> __( 'Swatch height', 'pewc' ),
		// 	'type'		=> 'number',
		// 	'desc_tip'	=> true,
		// 	'desc'		=> __( 'Set a swatch height to override the standard thumbnail size.', 'pewc' ),
		// 	'id'		=> 'pewc_swatch_height'
		// ),
		'image_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_image_title'
		),

	);

	return apply_filters( 'pewc_image_settings', $settings );

}

/**
 * Get the product field settings
 */
function pewc_get_products_settings() {

	$settings = array(

		'products_section_title' => array(
			'name'     => __( 'Products', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_products_title'
		),
		'pewc_child_variations' => array(
			'name'		=> __( 'Include variations as child products', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to include variations as child products.', 'pewc' ),
			'id'			=> 'pewc_child_variations',
			'default'		=> 'yes',
			'std'			=> 'yes'
		),
		'pewc_exclude_skus' => array(
			'name'		=> __( 'Exclude SKUs from child variants', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to exclude SKUs from child variant names.', 'pewc' ),
			'id'			=> 'pewc_exclude_skus',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_child_products_cart' => array(
			'name'		=> __( 'Hide child products in the cart', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to hide child products in the cart.', 'pewc' ),
			'id'			=> 'pewc_hide_child_products_cart',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_display_child_products_as_meta' => array(
			'name'		=> __( 'Display child products as metadata', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to display child products as metadata for parent products in the cart.', 'pewc' ),
			'id'			=> 'pewc_display_child_products_as_meta',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_parent_products_cart' => array(
			'name'		=> __( 'Hide parent products in the cart', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to hide parent products in the cart.', 'pewc' ),
			'id'			=> 'pewc_hide_parent_products_cart',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_child_products_order' => array(
			'name'		=> __( 'Hide child products in the order', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to hide child products in the order.', 'pewc' ),
			'id'			=> 'pewc_hide_child_products_order',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_parent_products_order' => array(
			'name'		=> __( 'Hide parent products in the order', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to hide parent products in the order.', 'pewc' ),
			'id'			=> 'pewc_hide_parent_products_order',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_indent_child_product' => array(
			'name'		=> __( 'Indent child products', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Add padding to child products in the cart and order.', 'pewc' ),
			'id'			=> 'pewc_indent_child_product',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_multiply_independent_quantity' => array(
			'name'		=> __( 'Multiply independent quantities', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to multiply child product independent quantities when parent product quantities are adjusted.', 'pewc' ),
			'id'			=> 'pewc_multiply_independent_quantity',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_remove_parent' => array(
			'name'		=> __( 'Remove parent if child product out of stock', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to automatically remove parent products if a child product is out of stock.', 'pewc' ),
			'id'			=> 'pewc_remove_parent',
			'default'	=> 'no',
			'std'			=> 'no'
		),

		'pewc_redirect_hidden_products' => array(
			'name'		=> __( 'Redirect hidden products', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this to prevent users purchasing child products direct from their product page.', 'pewc' ),
			'id'			=> 'pewc_redirect_hidden_products',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'products_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_products_title'
		),
		'products_quickview_title' => array(
			'name'     => __( 'QuickView', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'products_quickview_title'
		),
		'pewc_child_product_quickview' => array(
			'name'		=> __( 'Enable QuickView for child products', 'pewc' ),
			'type'		=> 'select',
			'desc_tip'	=> true,
			'desc'		=> __( 'Select this to show extra information for child products when clicking the child product title.', 'pewc' ),
			'id'		=> 'pewc_child_product_quickview',
			'default'	=> 'no',
			'options'	=> array(
				'no'	=> __( 'Disabled', 'pewc' ),
				'yes'	=> __( 'Lightbox', 'pewc' ),
				'tab'	=> __( 'New tab', 'pewc' ),
			)
		),
		'pewc_quickview_text' => array(
			'name'		=> __( 'Optional link text', 'pewc' ),
			'type'		=> 'text',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enter text here to display as a separate link below the product name. Leave this empty to link directly from the product name.', 'pewc' ),
			'id'			=> 'pewc_quickview_text'
		),
		'products_quickview_end' => array(
			'type' => 'sectionend',
			'id' => 'products_quickview_title'
		),

	);

	return apply_filters( 'pewc_products_settings', $settings );

}

/**
 * Get the calculation field settings
 */
function pewc_get_calculations_settings() {

	$settings = array(

		'calculations_section_title' => array(
			'name'     => __( 'Calculations', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_calculations_title'
		),
		'pewc_variable_1' => array(
			'name'			=> __( 'Variable 1', 'pewc' ),
			'type'			=> 'number',
			'desc_tip'	=> true,
			'desc'			=> __( 'Enter a value for variable_1 that will be used in calculations', 'pewc' ),
			'id'				=> 'pewc_variable_1',
			'custom_attributes'	=> array(
				'step'		=> apply_filters( 'pewc_global_variable_step', '0.01' )
			),
			'default'		=> '',
		),
		'pewc_variable_2' => array(
			'name'			=> __( 'Variable 2', 'pewc' ),
			'type'			=> 'number',
			'desc_tip'	=> true,
			'desc'			=> __( 'Enter a value for variable_2 that will be used in calculations', 'pewc' ),
			'id'				=> 'pewc_variable_2',
			'custom_attributes'	=> array(
				'step'		=> apply_filters( 'pewc_global_variable_step', '0.01' )
			),
			'default'		=> '',
		),
		'pewc_variable_3' => array(
			'name'			=> __( 'Variable 3', 'pewc' ),
			'type'			=> 'number',
			'desc_tip'	=> true,
			'desc'			=> __( 'Enter a value for variable_3 that will be used in calculations', 'pewc' ),
			'id'				=> 'pewc_variable_3',
			'custom_attributes'	=> array(
				'step'		=> apply_filters( 'pewc_global_variable_step', '0.01' )
			),
			'default'		=> '',
		),
		'pewc_zero_missing_field' => array(
			'name'		=> __( 'Zero value for missing fields', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Return a 0 as the value for any fields that are not included on the page.', 'pewc' ),
			'id'			=> 'pewc_zero_missing_field',
			'default'	=> 'no',
			'std'			=> 'no'
		),

		'calculations_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_calculations_title'
		),

	);

	return apply_filters( 'pewc_calculations_settings', $settings );

}

/**
 * Get the licence settings
 */
function pewc_get_licence_settings() {

	$settings = array(
		'section_title' => array(
			'name'     => __( 'WooCommerce Product Add-Ons Ultimate', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_settings_title'
		),
		'pewc_license_key' => array(
			'name'			=> __( 'License key', 'pewc' ),
			'type'			=> 'pewc_license_key',
			'desc_tip'		=> true,
			'desc'			=> __( 'Enter your license key here. You should have received a key with the email containing the plugin download link.', 'pewc' ),
			'id'			=> 'pewc_license_key',
			'default'		=> '',
		),
		'section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_settings_title'
		),

	);

	return apply_filters( 'pewc_licence_settings', $settings );

}

/**
 * Get settings for Integrations
 * @since 3.12.2
 */
function pewc_get_integrations_settings() {

	$settings = array(

		'integrations_section_title' => array(
			'name'     => __( 'Integrations', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_integrations_title'
		),
		'pewc_pll_integration_enable' => array(
			'name'		=> __( 'Enable compatibility with Polylang', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Enable this only if you have Polylang installed. Also, if you have added the Polylang compatibility snippets before, please remove them before enabling this setting.', 'pewc' ),
			'id'			=> 'pewc_pll_integration_enable',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'integrations_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_integrations_title'
		),

	);

	if( function_exists( 'wcfad_field' ) ) {

		$settings['pewc_wcfad_section_title'] = array(
			'name'     => __( 'Dynamic Pricing and Discount Rules', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_wcfad_section_title'
		);
		$settings['pewc_disable_wcfad_price_label'] = array(
			'name'		=> __( 'Disable Dynamic Pricing and Discount Rules price labels', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Prevent Dynamic Pricing and Discount Rules from updating the price labels on the product page.', 'pewc' ),
			'id'			=> 'pewc_disable_wcfad_price_label',
			'default'	=> 'no',
			'std'			=> 'no'
		);
		$settings['pewc_disable_wcfad_pricing_table'] = array(
			'name'		=> __( 'Disable add-on prices on pricing table', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Prevent Add-Ons Ultimate from adding add-on field prices to the pricing table on the product page.', 'pewc' ),
			'id'			=> 'pewc_disable_wcfad_pricing_table',
			'default'	=> 'no',
			'std'			=> 'no'
		);
		$settings['pewc_wcfad_apply_user_role_adjustments'] = array(
			'name'		=> __( 'Apply User Role Pricing to add-on field prices', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Adjust add-on field prices if User Role Pricing is enabled in Dynamic Pricing and Discount Rules.', 'pewc' ),
			'id'			=> 'pewc_wcfad_apply_user_role_adjustments',
			'default'	=> 'no',
			'std'			=> 'no'
		);
		$settings['pewc_disable_wcfad_on_addons'] = array(
			'name'		=> __( 'Disable discounts for add-ons', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Select this if you don\'t wish to apply dynamic discounts to add-on field prices.', 'pewc' ),
			'id'			=> 'pewc_disable_wcfad_on_addons',
			'default'	=> 'no',
			'std'			=> 'no'
		);
		$settings['pewc_wcfad_section_end'] = array(
			'type' => 'sectionend',
			'id' => 'pewc_wcfad_section_title'
		);

	}

	if( function_exists( 'wcmo_load_plugin_textdomain' ) ) {

		$settings['pewc_wcmo_section_title'] = array(
			'name'     => __( 'Custom user fields', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_wcmo_section_title'
		);
		$settings['pewc_enable_user_fields'] = array(
			'name'		=> __( 'Enable user fields', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Select this to use custom user data in your add-on fields.', 'pewc' ),
			'id'		=> 'pewc_enable_user_fields',
			'default'	=> 'no',
			'std'		=> 'no'
		);
		$settings['pewc_wcmo_section_end'] = array(
			'type' => 'sectionend',
			'id' => 'pewc_wcmo_section_title'
		);

	}

	return apply_filters( 'pewc_integrations_settings', $settings );
}

/**
 * Get settings for Optimised Validation
 * @since 3.13.7
 */
function pewc_get_optimised_validation_settings() {

	$settings = array(

		'optimised_validation_section_title' => array(
			'name'     => __( 'Validation', 'pewc' ),
			'type'     => 'title',
			'desc'     => '',
			'id'       => 'pewc_optimised_validation_title'
		),
		'pewc_optimised_validation' => array(
			'name'			=> __( 'Optimised validation', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'		=> true,
			'desc'			=> __( 'This will enable validation of add-on fields using Javascript.', 'pewc' ),
			'id'			=> 'pewc_optimised_validation',
			'default'		=> 'no',
			'std'			=> 'no'
		),
		'pewc_hide_totals_validation' => array(
			'name'			=> __( 'Hide totals until validated', 'pewc' ),
			'type'			=> 'checkbox',
			'desc_tip'	=> true,
			'desc'			=> __( 'This will hide the subtotals fields until all required fields have been completed. Requires Optimised validation.', 'pewc' ),
			'id'				=> 'pewc_hide_totals_validation',
			'default'		=> 'no',
			'std'				=> 'no'
		),
		'pewc_disable_groups_required_completed' => array(
			'name'		=> __( 'Disable groups', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Disable the next groups until all required fields in the current group are completed. Only works if groups are displayed as Accordion, Steps, or Tabs. Requires Optimised validation.', 'pewc' ),
			'id'			=> 'pewc_disable_groups_required_completed',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'pewc_disable_scroll_on_steps_validation' => array(
			'name'		=> __( 'Disable scroll on Steps layout', 'pewc' ),
			'type'		=> 'checkbox',
			'desc_tip'	=> true,
			'desc'		=> __( 'Disable the scrolling animation on Steps layout when validation fails. Requires Optimised validation.', 'pewc' ),
			'id'			=> 'pewc_disable_scroll_on_steps_validation',
			'default'	=> 'no',
			'std'			=> 'no'
		),
		'optimised_validation_section_end' => array(
			'type' => 'sectionend',
			'id' => 'pewc_optimised_validation_title'
		),

	);

	return apply_filters( 'pewc_optimised_validation_settings', $settings );

}
