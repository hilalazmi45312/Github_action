=== WooCommerce Product Add-Ons Ultimate ===
Contributors: Gareth Harris
Tags: add-ons, ecommerce
Requires at least: 4.7
Tested up to: 6.8
Stable tag: 3.27.2
Allow your users to customise products through additional fields

== Description ==

WooCommerce Product Add Ons Ultimate allows your users to customise products through additional fields.

== Installation ==
1. Upload the `product-extras-for-woocommerce` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Start adding Product Add-Ons in your WooCommerce products

== Frequently Asked Questions ==


== Screenshots ==

1.

== Changelog ==

= 3.27.2, 19 November 2025 =
* Added: pewc_filter_rename_uploaded_file, pewc_filter_rename_uploaded_file_order, and pewc_filter_rename_uploaded_url_order filters
* Fixed: child product from Products field with default value and independent quantities not added to cart if initially hidden by a condition
* Fixed: PHP warnings in pewc_add_user_meta_as_options()
* Updated: Layer images setting now work for fields with default value even if you do not enable Replace main image

= 3.27.1, 12 November 2025 =
* Fixed: child product quantities not disabled by default in the cart when using Blocks
* Fixed: HTML tags not stripped from aria-label
* Fixed: Options total not updating until a variation is selected from a Products field that uses the Variations Grid layout
* Updated: condition rules that reference Products/Product Categories field should match the referenced field's Products Layout, e.g. if Products Layout is Checkboxes Images, possible rules are Contains/Does not Contain. If Products Layout is Radio, possible rules are Is/Is Not

= 3.27.0, 3 November 2025 =
* Added: use bookable products from Bookings for WooCommerce as child products
* Added: pewc_child_product_available_stock filter
* Fixed: 'Hide child products in the cart' and 'Hide parent products in the cart' settings not working when using Blocks
* Updated: $child_product param to pewc_option_price filter

= 3.26.19, 28 October 2025 =
* Fixed: backorder setting for simple and variation child products not recognized when using Column layout on Products field

= 3.26.18, 23 October 2025 =
* Added: 'Hide field prices' global setting
* Added: Remove button for repeated groups
* Fixed: PHP deprecated warnings from WooCommerce 10.3

= 3.26.17, 22 October 2025 =
* Fixed: main image of a Swatch field option is not used to replace the product image if an alt image exists
* Fixed: PHP Warnings when loading the Global Add-Ons page
* Updated: allow Value Only in Option Price Visibility setting for Swatch fields if 'Allow multiple' is disabled

= 3.26.16, 21 October 2025 =
* Updated: changed accounting JS handle to wc-accounting for 10.3

= 3.26.15, 15 October 2025 =
* Added: pewc_before_quickview_template_load and pewc_after_quickview_template_load filters
* Added: QuickView template file
* Fixed: JavaScript error if a Swatch field option does not have an image and Replace image is enabled
* Fixed: if default product quantity is more than one, repeatable groups attached to quantity should match the default quantity
* Fixed: accessibility issue with Checkboxes List and Radio List layout for Products fields

= 3.26.14, 7 October 2025 =
* Added: apaou_composite_image_url to order meta
* Fixed: hidden Calculation fields appear in the Summary panel
* Updated: template for Checkboxes List products layout

= 3.26.13, 30 September 2025 =
* Added: pewc_option_attribute_string filter to select fields
* Added: pewc_get_products_for_cats_args filter
* Fixed: radio button default value with spaces not respected
* Fixed: fatal error if Products field has discount and child product does not have a price
* Fixed: variation-level user role pricing from WooCommerce Dynamic Pricing and Discount Rules is applied twice to the parent variable product if only child products are added as add-ons

= 3.26.12, 23 September 2025 =
* Fixed: conditions dependent on Radio options stopped working

= 3.26.11, 22 September 2025 =
* Added: pewc_child_product_default_quantity_check_stock filter
* Added: anti-cache string to the URL of uploaded images in the cart when editing options is enabled
* Added: Hide quantity setting
* Fixed: if 'Hide totals until validated' is enabled, totals are still hidden even if there are no required fields
* Fixed: pewc_key is added to product URL even if the product does not have add-on fields
* Fixed: double default settings appear in the backend for Number and Checkbox fields
* Fixed: calendar popup for the Date field is hidden behind the Lightbox container when using the Lightbox layout
* Fixed: global group added on the product page does not appear if group has different language in WPML
* Fixed: radio buttons not selectable inside lightbox
* Fixed: a Checkbox field that is checked by default remains unchecked when its value is reset by a condition
* Fixed: text counter does not work inside the lightbox
* Updated: default quantity for child products should not exceed a product's stock
* Updated: attributes in conditions now support simple products
* Updated: grand total is now updated inside the lightbox when Display totals fields is Total only

= 3.26.10, 2 September 2025 =
* Added: initial support for conditions on options
* Fixed: child products that are variations do not include the attributes in the title if there are 3 or more attributes

= 3.26.9, 27 August 2025 =
* Added: initial support for offloading uploaded files
* Fixed: ensure layered swatches can't have z-index: 0
* Fixed: global group added from the Edit Product page does not follow the sort order in the Global Groups page

= 3.26.8, 21 August 2025 =
* Added: child_field_ids param to product_extras array in cart item data
* Added: pewc_swatch_wrapper_classes filter
* Fixed: some global groups are not displayed when Display global groups as post type setting is enabled
* Fixed: column sort in Global Groups page not working
* Fixed: add-on field condition rule 'Does Not Contain' not working

= 3.26.7, 6 August 2025 =
* Added: option to exclude empty fields in cart
* Added: option to display hidden fields as disabled
* Added: pewc_radio_label_classes filter
* Added: pewc_multiply_repeatable_field_prices_with_quantity filter
* Fixed: totals not updating when using the quantity selector on the new Add to Cart with Options block
* Fixed: date picker in Clean Up Uploaded Files page not working in other languages

= 3.26.6, 31 July 2025 =
* Added: pewc_maybe_include_tax_on_options filter for exc / inc / inc tax settings and percentage option prices
* Fixed: groups don't repeat when user types new value into quantity input
* Fixed: some conditions don't work on repeated groups
* Fixed: layered image is not applied when editing options from the cart if field is inside a group with conditions

= 3.26.5, 23 July 2025 =
* Added: aria-labels to add-on fields
* Added: alt attributes to images
* Added: notice while the plugin is getting the PDF page count
* Added: for attribute to checkbox fields in field settings
* Fixed: 0 'Repeat Limit' preventing groups from repeating
* Updated: behavior of conditions in repeated groups
* Updated: improved accessibility standards

= 3.26.4, 16 July 2025 =
* Added: pewc_child_product_option_cost filter
* Added: parent_product_id parameter in child product cart data
* Added: pewc_filter_information_row_image_size and pewc_auto_detect_tax_rate_for_adjustment filters
* Fixed: when using the Column layout in Products field and pewc_column_layout_replace_thumbnail filter is active, other product images in the same field get replaced when clicking the Remove button or updating the quantity input

= 3.26.3, 7 July 2025 =
* Added: radio group fields are repeatable
* Added: pewc_child_product_wrapper_classes filter to products-checkboxes.php
* Added: alt text to the Remove button in the AJAX upload box
* Added: pewc_column_layout_replace_thumbnail filter
* Updated: changed how row images are generated in Information fields so that they include alt text from the backend

= 3.26.2, 2 July 2025 =
* Fixed: condition rule 'Is Not' is not displaying correctly in the backend when the condition refers to Products or Product Categories fields

= 3.26.1, 25 June 2025 =
* Added: pewc_enqueue_blocks_script filter
* Fixed: add-on fields not included in PDF Invoices & Packing Slips for WooCommerce
* Fixed: uploaded images not displayed on cart and checkout pages when using GeneratePress theme

= 3.26.0, 19 June 2025 =
* Added: allow formulas in field prices and option prices
* Added: Default setting for Products and Product Categories fields
* Added: allow_text_calcs_fields for returning text ACF fields
* Fixed: add-ons list is displayed twice in the admin Edit Order page
* Fixed: when a Products field is using Components List layout and Force Quantity is enabled, a child product can still be deselected when clicking on its image

= 3.25.6, 10 June 2025 =
* Added: pewc_disable_replace_base_image_with_selected_swatch filter
* Fixed: Select and Select Box fields with field prices and First field is instruction only get added to the totals even if no option is selected
* Fixed: Swatch fields not working correctly for layer images if both main and alt images exist for a swatch option
* Fixed: totals not updated on page load when Products and Product Categories fields that use Variations Grid layout have default values or when editing from the cart
* Fixed: selected variations not added to Summary Panel when using Variations Grid layout
* Updated: use selected Swatch as base image for layered images if Replace main image is enabled for the Swatch field
* Updated: ensure group post types are enabled by default

= 3.25.5, 28 May 2025 =
* Fixed: Javascript infinite loop issue when using a Select Box field in a condition
* Fixed: PHP error if a Swatch field option does not have a main image and Layer images is enabled
* Fixed: Products field using Select layout does not show Select Field Placeholder text when the field has conditions
* Fixed: incorrect price for Name Your Price fields if Multiply Price setting is enabled
* Updated: apply Simple discount on the add-ons on the product page if apply_simple_rules_after_product_rules is active
* Updated: compatibility with Barn2 Fast Cart for forced quantities

= 3.25.4, 30 April 2025 =
* Added: pewc_duplicate_group_map_field_ids action hook
* Added: pewc_update_duplicated_ids jQuery trigger
* Fixed: some Text fields trigger a PHP warning during validation
* Fixed: validation issues if a condition is dependent on a repeatable field
* Fixed: product image not resetting when deselecting Swatch field if there are selected layered Swatch fields
* Fixed: deselecting a swatch doesn't remove the border on mobile
* Updated: allow child products with low stock to be ordered beyond the stock count if backorder is allowed

= 3.25.3, 23 April 2025 =
* Added: pewc_filter_duplicate_field_params filter
* Fixed: Clear All Options button does not work on the product page after adding the product to the cart and if Redirect to the cart page is disabled
* Fixed: Select field becomes blank after clicking Clear All Options button if field does not have a default value
* Fixed: Select Box field not correctly resetting after clicking Clear All Options button
* Fixed: totals are not updating on the product page when using the Components List layout in Products and Product Categories fields
* Fixed: color swatch not square on mobile
* Fixed: issues with Calculation fields with conditions 
* Updated: the custom order item meta pewc_product_extra_id is now hidden from the Edit Order page

= 3.25.2, 17 April 2025 =
* Updated: child products with force quantity cannot be deselected on product page
* Updated: child products with force quantity cannot be edited in cart

= 3.25.1.1, 11 April 2025 =
* Version bump

= 3.25.1, 11 April 2025 =
* Fixed: Product Add-Ons Home page in admin dashboard not working with WooCommerce 9.8.1

= 3.25.0, 10 April 2025 =
* Added: Components List layout for Products Layout
* Added: default quantity setting for child products with independent quantities set
* Fixed: issues with layered Swatch fields with default values and conditions
* Fixed: uploaded image not displayed on the Checkout page if using Blocks
* Fixed: field quantity does not reset when de-selecting a product in Radio Images layout
* Fixed: PHP warning when adding to cart if a Select Box field is hidden by a condition
* Fixed: if First field is instruction only is enabled, Select and Select Box fields still appear in the summary panel even if no option is selected
* Updated: automatically select/deselect products when changing field quantity in Radio Images or Radio List layout

= 3.24.7, 1 April 2025 =
* Fixed: New tab setting for QuickView not working if Optional link text is blank
* Fixed: repeated groups not displayed in Add-Ons by Order page
* Fixed: quantity and Add to cart button disappear on the product page when Quantity Selector is set to Stepper in Blocks
* Fixed: some add-on fields don't update the totals when used with Product Table Ultimate
* Updated: replace main image behaviour when unselecting swatches
* Updated: licence key fields to password inputs
* Updated: count multiple PDF file pages

= 3.24.6, 18 March 2025 =
* Fixed: if Optimised validation is disabled, required Radio and Select Box fields pass validation even when blank
* Fixed: Select Box option values with quotes don't get passed back to the product page when editing options from the cart
* Fixed: Swatch fields that have layered images enabled and with conditions are not replacing the main image if field has a default value

= 3.24.5, 12 March 2025 =
* Added: pewc_products_for_cats_stock filter
* Fixed: price does not update on product page when using WC Blocks even if Update price label is enabled
* Fixed: JavaScript error on uploading a large file when the filter pewc_use_original_image_for_thumbnail is activated
* Fixed: Number fields that are displayed as sliders always pass JS validation even if a field is required and no value has been selected
* Fixed: option values with quotes don't get passed back to the product page when editing options from the cart
* Updated: PDF page count now works for multiple uploads

= 3.24.4, 3 March 2025 =
* Added: option to open quickview in new tab
* Fixed: uploaded images don't appear in the cart on some servers
* Updated: stop propagation when quickview link is clicked

= 3.24.3, 24 February 2025 =
* Fixed: Disable groups function does not work on some themes because of JS error
* Fixed: Repeated fields values do not appear in the order pages
* Fixed: script errors in Query Monitor plugin
* Updated: .htaccess rules for Apache 2.4

= 3.24.2, 6 February 2025 =
* Added: pewc_allowed_global_groups_post_status filter
* Fixed: product quantity from the cart is not used when editing product add-on fields
* Fixed: product quantity reverts to the default value set by Min Max Quantity and Order instead of the initially selected quantity when using Edit Options from the cart
* Fixed: date picker on the Clean Up Uploaded Files page does not work on non-HPOS sites
* Fixed: repeatable setting does not toggle on a copied group
* Fixed: unable to modify items in the Global Add-Ons Products rule after duplicating a group
* Updated: only published global groups are displayed on the frontend by default
* Updated: product and category search in Products and Product Categories field now work on newly added fields

= 3.24.1, 28 January 2025 =
* Fixed: some required fields with flat rate price always fail Optimised validation
* Fixed: if Allowed multiple and Flat rate is enabled on a Swatch field, it is not getting added to the flat rate list on the cart page
* Fixed: when duplicating a product or exporting a group, field conditions are not copied correctly
* Updated: don't adjust flat rate add-on field prices on the product page if product has DPDR discount 

= 3.24.0, 21 January 2025 =
* Added: options for cleaning uploaded files from the server
* Added: pewc_update_add_on_image_focus_on_main_image jQuery trigger
* Fixed: Checkbox field not showing as checked even when Default is checked
* Fixed: default value is not getting applied to Radio Group and Checkbox fields when hidden by conditions and if the field is displayed as swatch
* Fixed: child product quantity in parent product's postmeta data not getting updated for child products with linked quantities
* Updated: allow add-on fields to be moved into empty groups in Edit Product page
* Updated: close enhanced tooltip window when clicking outside the window
* Updated: allow files to be downloaded from the order even if Organise uploads setting is disabled

= 3.23.0, 8 January 2025 =
* Added: Import/Export add-on groups
* Added: ability to move add-on fields to a different group
* Fixed: main image is not replaced with selected Swatch option when editing an option via the Edit options link on the Cart page
* Fixed: if a Checkbox field is checked by default, the field is always checked when editing options from the cart
* Fixed: entered quantities in Products field using Variation Grid layout not getting passed to the product page when editing options from the cart
* Fixed: linked and one only child products can be removed and their quantities updated in the cart when using Blocks
* Fixed: add-on fields with options show incorrect prices on the order page for some tax settings
* Fixed: auto-focus on the first image not working when Replace main image with swatch is enabled
* Updated: DPDR rule label is retained even if Update pricing label is enabled
* Updated: compatibility with Advanced Uploads multiply price with quantity per upload
* Updated: display groups as post type by default

= 3.22.1, 3 January 2025 =
* Fixed: ensure $group is always array in repeater

= 3.22.0, 2 December 2024 =
* Added: setting to prevent adding add-on field prices to the DPDR pricing table
* Added: pewc_prepare_item_for_database_params filter
* Added: pewc_replace_backslashes_in_file_paths filter for compatibility of Upload fields in Windows systems
* Added: Clear All Options button
* Added: User Role specific groups
* Added: group repeater and quantity repeater
* Fixed: fields using cost-based conditions are not added to the cart
* Fixed: hidden option prices are shown when DPDR updates add-on field prices
* Fixed: required Select Box field that has a condition fails optimised validation when shown
* Fixed: issues with field conditions dependent on a hidden Select Box field
* Fixed: issues with field conditions dependent on a hidden Checkbox field with default value
* Fixed: uploaded images are not displayed in Cart and Checkout pages using blocks
* Fixed: Edit options text does not appear in Cart page using blocks
* Fixed: PDF page count is not displayed when editing add-ons on product pages
* Fixed: if checkout fails and Organise uploads by order is enabled, uploaded image gets moved early and causes issues when checkout completes
* Updated: enhanced Customizer options for group layouts
* Updated: show original price with strikethrough next to adjusted product price if Disable DPDR price labels setting is off
* Updated: adjust a child product's price labels only if Dynamic Pricing rule applies to all products
* Updated: moved Javacript trigger event 'pewc_field_visibility_updated' for better compatibility with other plugins

= 3.21.6, 23 October 2024 =
* Fixed: DPDR table not updating on variable products when selecting add-on fields

= 3.21.5, 10 October 2024 =
* Added: pewc_disable_wcfad_on_addons setting
* Added: pewc_calculating_text and pewc_uploading_text filters
* Fixed: Download button in Edit Order page causes a PHP Fatal error for some sites
* Fixed: Add to cart button text becomes 'Calculating...' when uploading a file and Disable Add to Cart button setting is enabled
* Fixed: login status conditions not working for global add-on fields
* Fixed: JavaScript console error if product does not have DPDR rules
* Updated: references to F+D to DPDR
* Updated: remove group title in the cart if Display group titles in cart is enabled and group is empty
* Updated: load only DPDR compatibility script if Dynamic Pricing is enabled

= 3.21.4, 1 October 2024 =
* Added: $child_product_id parameter to pewc_cart_item_extras_child_product filter
* Added: pewc_swatch_layer_composite_constant, pewc_swatch_layer_base_image_url, and pewc_swatch_layer_swatch_url filters
* Fixed: hidden Swatch field with default value does not replace main image when shown
* Fixed: hidden Swatch field with default value replaces main image when selecting variations
* Fixed: Customizer not working when Product Add-Ons Ultimate is active
* Fixed: radio group fields appear in Summary panel even if nothing is selected
* Fixed: product slugs with special characters sometimes causes issues when generating composite image with swatch fields
* Fixed: display issues with categories in global rules when switching from Global Add-Ons to Global groups as post types
* Fixed: some sites encounter 'Failed to read file' error when using layer images on Swatch fields
* Fixed: required Color Picker fields always fail validation when Optimised validation is enabled
* Fixed: Swatch field option description is displayed twice on variable products when percentage option is enabled and when Preset style is set to 'Inherit from theme'
* Fixed: Checkbox field price not updating on variable products when percentage option is enabled
* Fixed: Checkbox Group field option prices display incorrect prices on variable products when percentage option is enabled
* Fixed: parent product is also added to the cart when using Swatches layout in Products field
* Fixed: quantity sometimes does not update when clicking the Add button on Column layout
* Fixed: child products that are not selected are still added when using Swatches layout and quantity is Linked or One Only
* Fixed: PHP warnings when adding child products and the parent product has an upload field
* Updated: compatibility of layer images with Porto theme
* Updated: prevent fatal error when using layer images feature and Imagick is not installed
* Updated: also validate Number fields with min and max values if Disable groups setting is enabled
* Updated: update add-on field prices on the product page when a Dynamic Pricing and Discount Rules rule applies to all products

= 3.21.3, 1 August 2024 =
* Added: pewc_before_close_td filter
* Fixed: character counter for text fields break layout if using Table group layout
* Fixed: incorrect total price in the cart when using a Calculation field that set the product price of a Booking product
* Fixed: child product quantity input field incorrectly using the last variation's stock as max value when using Column layout in Products field
* Updated: respect woocommerce_price_trim_zeros filter in summary and totals
* Updated: changed how quantity is retrieved by jQuery in pewc.js and conditions.js
* Updated: use the selected variation's stock as max value in the child product quantity input field when editing add-ons from the cart

= 3.21.2, 2 July 2024 =
* Added: pewc_trigger_js_validation Javascript event
* Added: pewc_allow_empty_field_values filter
* Fixed: enhanced tooltips appear sometimes in non-product pages
* Fixed: error message does not disappear when Product Categories field fails then passes validation
* Fixed: PHP warning when saving a product with a new group
* Fixed: error notice disappears on required fields that failed min/max validation if Hide totals until validated or Disable groups settings are enabled
* Fixed: changing field type in the Edit Product page triggers a Javascript warning in the browser console
* Fixed: Number field value not resetting when hidden
* Fixed: display issues when fields have options and option price is a percentage of the product price
* Updated: auto-select child products when using pewc_child_product_independent_quantity filter
* Updated: jQuery deprecated functions

= 3.21.1, 16 June 2024 =
* Fixed: some fields trigger an infinite loop in Javascript when resetting their value
* Fixed: Group class not saving in Edit Product page and Global Product Add-Ons

= 3.21.0, 10 June 2024 =
* Added: enhanced tooltips
* Added: pewc_globals_extra_metadata action
* Added: pewc_group_extra_fields action
* Added: pewc_groups_metabox_fields filter
* Added: Japanese translation
* Fixed: extra characters in Global Groups page
* Fixed: accordion tab does not close when title is clicked
* Fixed: when editing a cart item with a variation child product, the child product quantity field is using the variation ID
* Fixed: if multiple layered images have default values, only the default image of the first field gets applied
* Fixed: incorrect totals in Global Groups page
* Fixed: add-on field image not working
* Fixed: variation-dependent fields do not reset their values when hidden even with Reset field values enabled
* Fixed: Text Preview field not resetting when hiding a variation-dependent field
* Updated: use enhanced search for By product rule in Global Add-Ons
* Updated: when multiple uploads are enabled, if a file fails validation, other uploaded files that passed validation are now saved and added on the next page load

= 3.20.0, 8 May 2024 =
* Added: pewc_allow_tax_on_negative_prices filter
* Added: pewc_disable_cart_item_price_adjust filter
* Added: pewc_name_price_field_display_in_cart filter
* Added: pewc_start_group and pewc_end_group filters
* Added: custom user field functionality
* Added: progress bar layout options
* Added: log-in status conditions
* Added: alt image for swatches
* Added: 'Always include in order' option for group conditions
* Added: setting for permitted file types
* Added: Customizer settings for swatch image size
* Added: add to length, width and height attributes using calculation fields
* Fixed: some child products are not added if a Product field is using the Select layout with placeholder text
* Fixed: add-on prices are not included in cart item prices when using Divi theme cart
* Fixed: incorrect fixed discount on child products in some tax settings
* Fixed: Products field using Column layout use the parent child product's stock instead of the child product variant's stock
* Updated: added $item argument to pewc_maybe_adjust_tax_price_with_extras filter
* Updated: use enhanced search for global groups and products fields
* Updated: Customizer options
* Updated: .pewc-hex markup
* Updated: close accordion group without re-opening
* Updated: optimise conditions and calculations parameters now enabled by default
* Updated: AJAX uploader enabled by default

= 3.19.1, 4 April 2024 =
* Added: pewc_start_group and pewc_end_group hooks
* Added: Group Class field
* Fixed: conflict with Facebook for WooCommerce plugin
* Updated: reduced number of products queried for Products field

= 3.19.0, 27 March 2024 =
* Added: pewc_dropzone_remove_before_unlink action hook
* Added: pewc_order_frontend_display_file and pewc_uploaded_files_meta filter
* Added: assign global groups from product edit screen
* Added: full progress bar functionality
* Added: number range slider option
* Added: pewc_check_tax_for_option_price filter
* Added: pewc_allow_text_calcs to allow text values from look up tables
* Fixed: minor style issue with selected child product
* Fixed: PHP warning if Display group titles in cart is enabled and group title is blank
* Fixed: uploaded files are not moved to the correct directory when using Checkout Blocks
* Fixed: fatal error on the plugins page when WooCommerce is deactivated while Add-Ons Ultimate is active

= 3.18.1, 27 February 2024 =
* Added: decimal places setting for number fields
* Fixed: parse error in functions-single-product.php

= 3.18.0, 22 February 2024 =
* Added: pewc_calculation_look_up_tables_product_page_only filter
* Added: pewc_reinitiate_js_validation Javascript event
* Added: pewc_disable_line_height_select_box filter
* Added: pewc_set_product_weight_filter_limit filter
* Added: pewc_update_price_label filter
* Fixed: when Disable groups is enabled, some groups are not enabled if they don't have required fields
* Fixed: updating the quantity of an independent child product also updates the quantity of a sibling linked child product
* Fixed: a child product that is sold individually can be added multiple times in the cart
* Fixed: calculation fields using look up tables not working on pages using WooCommerce Product Table Ultimate
* Fixed: child product stock not counted correctly if same product is added to different parent fields
* Fixed: checkbox and color picker fields with default values not getting reset when hidden
* Fixed: some responsiveness issues when using calculation values in conditions
* Fixed: selected swatch not getting removed from main image if swatch field is hidden
* Fixed: Swatch width field setting not saving
* Fixed: responsiveness of Swatch layer if product has a gallery of images
* Fixed: Swatch fields are always using layered images if Replace main image is enabled
* Fixed: Replace main image in Swatch field setting
* Fixed: if Swatch field has a default value, use it when field is reset
* Updated: better compatibility between Optimised validation and Product Table Ultimate
* Updated: replace main image on page load if Image Swatch, Checkbox, or Select Box fields have default value
* Updated: initiate colour picker on new swatch option

= 3.17.1, 17 January 2024 =
* Fixed: error when saving product with layers

= 3.17.0, 16 January 2024 =
* Added: image layers option in Swatch fields
* Fixed: PHP error when WooCommerce is not active
* Updated: UX issues with colour swatch setting
* Updated: EDD_SL_Plugin_Updater for PHP8.2 compatibility

= 3.16.0, 9 January 2024 =
* Added: pewc_disable_product_extra_post_type filter
* Added: pewc_edited_fields, pewc_edited_child_fields, and pewc_products_column_selected_variations filters
* Added: option to replace main image with selected swatch
* Added: colour swatch option
* Fixed: product_extras records are created repeatedly when updating orders using REST API
* Fixed: Products fields using Swatches layout do not have values when editing product options from the cart
* Updated: disable AJAX add-to-cart on archive pages
* Updated: 'Image Swatch' fields have been renamed to 'Swatch' fields

= 3.15.0, 30 November 2023 =
* Added: disable next group until all required fields in current group is completed
* Added: pewc_hide_totals_validation, pewc_disable_groups_required_completed, and pewc_steps_group_disable_scroll_to_top filters
* Added: disable scroll on Steps layout when validation fails
* Added: pewc_after_update_total_js Javascript event
* Added: pewc_child_products_quantity_symbol and pewc_child_products_metadata_separator filters
* Added: pewc_disable_all_transients filter
* Fixed: incorrect line item total in order email if child products are hidden in the order
* Fixed: select box option increase in height continuously when you open and close it repeatedly
* Fixed: PHP error occurs when updating a Review and Approve order and WC session is not yet set
* Fixed: PHP warnings of undefined keys on Add-Ons Ultimate fields created using older versions of AOU, when a new field setting/key did not exist yet
* Fixed: compatibility between Polylang 3.5.2 and Global Groups
* Updated: better compatibility with Divi theme
* Updated: migration function causing PHP deprecated warnings in PHP 8.1
* Updated: product categories in conditions now uses comma-separated product ID(s)
* Updated: child products metadata now shows quantity
* Updated: child products metadata now shows selected variation instead of parent variable product

= 3.14.0, 7 November 2023 =
* Added: field visibility setting

= 3.13.6, 12 September 2023 =
* Fixed: no visual indication that a radio group option is selected if Default value is set and Display as Swatch is enabled
* Fixed: PHP parse error when using Checkboxes Images layout on Products field

= 3.13.5, 11 September 2023 =
* Added: pewc_use_original_image_for_thumbnail filter
* Added: pewc_get_child_quantity_field_attributes to checkbox field templates
* Added: Bulgarian translation (thanks to Simeon for this!)
* Added: pewc_ignore_price_with_extras filter to avoid issues with Aelia converting price
* Added: pewc_filter_minmax_validation_values filter
* Fixed: some PDFs generate thumbnails with inverted colors
* Updated: compatibility with WooCommerce High-Performance Order Storage

= 3.13.4, 25 July 2023 =
* Added: pewc_hide_zero_option_price filter for Select Box field
* Added: pewc_get_all_product_ids_limit and pewc_get_all_product_ids_status filters
* Added: pewc_filter_not_visible_unset_cart_item_data filter for compatibility with Text Preview
* Fixed: AJAX upload issue on Safari if filename has special characters
* Fixed: child product gets deselected when choosing variation in Products field using Column layout
* Fixed: PHP warning if pewc_validate_child_products_stock filter is active and no child product is selected

= 3.13.3, 12 July 2023 =
* Fixed: WooCommerce zoom image not replaced when replace main image with image swatch is active
* Fixed: Incorrect grand total if a calculation field that set the product price is subsequently hidden
* Fixed: Products field using Checkbox Images layout and Linked quantity experiences a JavaScript infinite loop
* Fixed: Add and Remove buttons not working for Products field using Column layout

= 3.13.2, 3 July 2023 =
* Added: pewc_replace_image_focus, pewc_control_container, pewc_control_list, and pewc_control_element filters
* Updated: child products can now be selected by clicking the container
* Updated: reset products select field if quantity is 0
* Updated: prevent products select field from being added to cart metadata if quantity is 0
* Updated: replace main image with image swatch now moves the focus back on the main image

= 3.13.1, 8 June 2023 =
* Added: Display as swatch option for Radio Group and Checkbox Group fields
* Added: pewc_wcmmqo_disable_validate_child_products filter
* Fixed: group condition using 'contains' as operator does not work when referencing a Products field
* Fixed: some issues with quantities of child products when editing add-ons of products in the cart
* Fixed: option prices in add-on fields show zero price in the suffix
* Updated: display an alert message if there was an error in getting PDF page count
* Updated: delete pewc_categories_field_products_ transient on product save
* Updated: compatibility of child products with WooCommerce Minimum Maximum Quantity and Order

= 3.13.0, 16 May 2023 =
* Added: Set child product quantity using calculation fields
* Added: field_xxx_length etc tags for calculations to get dimensions of selected child products (independent quantities, radio images and list only)
* Added: field_xxx_quantity - get quantity of child products in field (independent quantities, radio images and list only)
* Added: pewc_after_pewc_add_on_product action hook
* Fixed: percentage price on a Text field overwrites the per-character calculation
* Fixed: Image Swatch field not validating properly if Flat Rate and Allow Multiple are enabled
* Fixed: default value for Products field with Radio Images layout not getting put back after being hidden
* Fixed: generating a thumbnail fails if a PDF has a very large resolution
* Updated: child product templates
* Updated: Radio Group fields can now be used as look up fields for look up tables
* Updated: added $cart_item_data argument to pewc_cart_item_extras_child_product filter

= 3.12.3, 17 April 2023 =
* Fixed: custom attributes cause PHP warnings on product pages
* Fixed: fatal error when adding to cart a product with add-on field that is dependent on an image swatch or checkbox group field but no options were selected
* Fixed: if AJAX uploader is disabled, product is still added to the cart even if uploaded file is invalid
* Updated: replace main image with image swatch selection compatibility with Porto theme

= 3.12.2, 5 April 2023 =
* Added: pewc_next_step_text filter
* Added: calculation tag for PDF page count
* Added: use {pa_attribute} in calculations
* Added: option to hide totals until fields validated
* Added: hide_totals_timer filter
* Added: pewc_attach_images_to_email_ids filter
* Added: pewc_disable_esc_url_uploaded_file filter
* Fixed: required Products fields fail validation when Optimised validation is enabled and child product price is zero
* Fixed: when using the filter pewc_disable_button_recalculate, Add to cart button also gets disabled if a page does not have calculation fields
* Fixed: PHP warnings on incomplete group conditions
* Fixed: default color for Color Picker field not getting applied on the front end product page
* Fixed: ensure quantity fields aren't hidden in child product fields
* Fixed: image swatch field price does not reset when hidden by a condition
* Fixed: calculation field sometimes triggers Javascript loop
* Fixed: default value for Products field with Radio Images layout not working on frontend
* Fixed: Upload field always fails validation if field is required and if AJAX uploader is disabled
* Fixed: image upload does not work on newly added row on an Information field
* Fixed: rounding issues if the setting Hide child products in the cart is enabled
* Updated: if Optimised validation is enabled, fields now show an error if customer selects more than the max limit
* Updated: when using the filter pewc_disable_button_recalculate, Add to cart button now shows "Calculating..." when disabled
* Updated: compatibility with Polylang
* Updated: hide 'Minimum price' option - use pewc_enable_minimum_price_option filter
* Updated: select field styles for cross browser consistency

= 3.12.1, 7 February 2023 =
* Added: pewc_order_item_upload_other_data filter
* Added: pewc_set_initial_key filter
* Added: total_variations tag for WooCommerce Better Variations grid compatibility
* Fixed: price not hidden on product page if Price label display setting is Hide price and Update price label is enabled
* Fixed: child products not added to parent product's meta if using Variations grid layout
* Fixed: PHP warnings if using Variations Grid layout
* Fixed: fields with incomplete conditions not getting added to the cart
* Updated: images in templates for Products field
* Updated: pewc_filter_group_title params
* Updated: Dutch translation

= 3.12.0, 12 December 2022 =
* Added: set attributes in conditions
* Added: pewc_add_cart_item_data_upload_file filter
* Added: pewc_get_item_data_after_file filter
* Added: pewc_filter_price_for_uploads filter
* Added: pewc_uploaded_file_display_name filter
* Added: pewc_disable_wcfad_on_addons filter
* Added: support for attributes on field conditions of variable products
* Added: disable_button_recalculate and recalculate_waiting_time filters
* Fixed: tooltip not working in Global Addons
* Fixed: compatibility issues with Fees and Discounts if calculation field uses product_price tag in its formula
* Fixed: incorrect prices in the dynamic pricing table for Fees and Discounts when a product uses a calculation field that sets the product price
* Fixed: issue with default values in fields hidden by nested conditions
* Updated: disable add-to-cart button for Upload and Calculation fields
* Updated: pewc_wc_price Javascript function now uses WooCommerce's accounting.js
* Updated: check file parameters for uploads

= 3.11.8.1, 17 November 2022 =
* Fixed: security vulnerability when using summary panel

= 3.11.8, 18 October 2022 =
* Updated: additional security hardening

= 3.11.7, 17 October 2022 =
* Fixed: security vulnerability for uploaded files in Firefox under certain conditions
* Updated: changed default preset style to 'simple'

= 3.11.6, 5 October 2022 =
* Added: Value Only option in Option Price Visibility
* Added: {field_x_option_value} tag in Calculation fields
* Added: character counter for Text Preview field
* Added: pewc_show_link_only_cart and pewc_remove_thumbs_in_order_page filters
* Added: pewc_calc_result_format filter for changing display format of Calculation fields
* Fixed: item price in mini cart does not include product add-on price
* Fixed: when referencing a Text Preview field in the conditions, condition value field is not available
* Fixed: validation for Text Preview fields
* Fixed: grand total does not update on the product page when changing the quantity and when the product price is set by a calculation field
* Fixed: conditions based on quantity do not respond when Optimised conditions is enabled
* Fixed: min / max characters do not appear for Text Preview fields
* Fixed: max_date setting for non-English languages

= 3.11.5, 30 August 2022 =
* Added: pewc_remove_thumbs_in_emails filter
* Added: REST API support for product-level add-on fields (beta)
* Fixed: ensure pewc_get_group_attributes always returns attributes
* Fixed: double quote issue in group title
* Fixed: transients are not resetting when saving Global Add-Ons
* Fixed: some fields' prices when inside hidden groups are still getting added to the total on the product page
* Fixed: incorrect field price after removing an AJAX uploaded file
* Fixed: Optimised validation does not open the group accordion if it has a field that failed validation
* Fixed: NaN in Fees and Discounts pricing table if tier amount is blank
* Updated: image swatch replace image function on fields that allow multiple selections
* Updated: if image swatch replace image is enabled, reset main image if field is hidden
* Updated: changes in optimised conditions so that a hidden field still triggers other fields that are dependent on it

= 3.11.4, 9 August 2022 =
* Added: Auto-generate PDF thumbnails on AJAX upload if site supports Imagick
* Added: pewc_enable_calc_in_cart filter to enable re-computing of Calculation fields that are dependent on quantity fields, if the quantity changes in the cart
* Fixed: Checkbox tooltip missing even if tooltip is enabled
* Fixed: tax issues with Booking product if {price_including_tax} is used
* Fixed: jerky movement when text field has max character and optimised validation is enabled
* Fixed: double quote issues in field label and in conditions
* Fixed: products with add-on fields still have AJAX add to cart behaviour on Gutenberg blocks
* Fixed: global group option is not updated if a global group was moved to trash
* Fixed: fatal error in PHP 8 if an add-on field has weight but the parent product does not have weight
* Fixed: if optimised validation is enabled, hidden variation-dependent fields are not skipped during validation
* Fixed: field IDs in calculation formulas are not updated when duplicating global groups
* Updated: if optimised validation is enabled, only perform live validation on fields with options after clicking Add to Cart

= 3.11.3, 20 July 2022 =
* Added: JavaScript counter for Text and Textarea fields
* Fixed: cannot select colour in picker field
* Fixed: default value for Radio Group is not being applied if field or group is initially hidden
* Fixed: conditions are not triggered if the Add button is clicked when using the Column Products Layout and Products Quantities is not Independent
* Fixed: option prices reappear on fields with percentage price when selecting variations, even if Option Price Visibility is set to Hidden
* Fixed: field setting Only Charge Alphanumeric is not getting applied in the cart
* Fixed: pewc_condition_value_step filter not getting applied to number fields in new condition row
* Updated: Optimised validation now includes checking if the number of selected options for image swatch, checkbox group, or product fields exceed that of the max value

= 3.11.2, 22 June 2022 =
* Added: integration with Fees and Discounts to apply role-based discounts to add-on fields
* Added: pewc_global_group_order filter
* Added: triggers for Image Preview to hook into field visibility affected by conditions

= 3.11.1, 8 June 2022 =
* Added: pewc_field_inner_tag action
* Added: filters for option attributes and values
* Added: num_bookings param for calculations (for Bookings for WooCommerce)
* Added: pewc_exit_after_download filter
* Fixed: QuickView does not work on Column layout for Product and Product Categories fields
* Fixed: incorrect display in the summary panel for Product fields using Column layout
* Fixed: Date fields not saving 'Disable days of the week' values properly in Global Add-Ons
* Fixed: bug when using condition operators Greater Than and Less Than
* Fixed: Min Chars and Max Chars are not checked if a text field is not required
* Fixed: Product Categories field is not saving the selected categories in Global Add-Ons
* Fixed: required fields inside hidden groups still fail JS validation
* Fixed: child product discount disappears if product does not have add-on fields and if Fees and Discount is active
* Fixed: Product fields that use Swatches layout are not added to the totals on the product page
* Updated: JS for Fees and Discounts integration

= 3.11.0, 23 May 2022 =
* Added: preset styles option (beta)
* Added: pewc_products_for_cats_limit filter
* Added: optimised validation option (beta)
* Added: $product_id param to pewc_get_add_on_image_action
* Fixed: Product Categories fields do not correctly validate the min/max child products value
* Fixed: when Display child products as metadata is enabled, child products of Product Categories fields are displayed as product IDs
* Fixed: handling of failed AJAX uploads when using drag-and-drop
* Fixed: if Enable editing is active, when validation fails, Update product reverts to Add product
* Fixed: summary panel does not hide hidden groups
* Fixed: bail early in pewc_product_single_add_to_cart_text if get_cart_contents() is null
* Updated: error handling of multiple Upload add-on fields

= 3.10.4, 5 May 2022 =
* Added: pewc_product_column_variants filter
* Fixed: checkbox field price not hidden correctly when set in Field Price Visibility
* Fixed: checkbox field price does not display percentage when setting is enabled
* Fixed: optimised conditions not doing initial check correctly

= 3.10.3, 2 May 2022 =
* Fixed: fatal error in Customizer

= 3.10.2, 29 April 2022 =
* Fixed: error in updating an order when Hide child products in the order is enabled

= 3.10.1, 26 April 2022 =
* Fixed: ensure child products in Product Categories fields link to parent products in cart
* Fixed: Product Categories fields with independent quantities
* Fixed: missing param in pewc_field
* Fixed: missing required element in checkbox template
* Fixed: removed extra colon in Customizer CSS

= 3.10.1, 26 April 2022 =
* Fixed: ensure child products in Product Categories fields link to parent products in cart
* Fixed: Product Categories fields with independent quantities
* Fixed: missing param in pewc_field
* Fixed: missing required element in checkbox template
* Fixed: validation issue for Product / Product Categories fields

= 3.10.0, 20 April 2022 =
* Added: new Products Categories field type
* Added: Checkboxes List and Radio List product field layouts
* Added: calculated_booking_cost and num_units_int params for calculations (for Bookings for WooCommerce)
* Fixed: look up table issue if header has decimals
* Fixed: parse error on products templates if licence not pro
* Fixed: parse error for weight fields with empty value
* Fixed: tax issues on column layout for child products
* Fixed: character settings do not appear for Text Preview in Global Add-Ons
* Fixed: group conditions not firing correctly when field is hidden
* Fixed: currency converted multiple times when add-ons in more than one group
* Fixed: bug in editing child products when the parent product uses a column layout
* Fixed: bug in field condition when using Select products layout and when Optimise conditions is enabled
* Fixed: tax issues in price display suffix
* Fixed: image swatch field price does not reset to zero if there is no option selected
* Fixed: child product independent quantities on the product page do not get multiplied if Multiply independent quantities is enabled
* Updated: set calculation price to 0 if look up table returns null
* Updated: get field parent in conditions.js for improved compatibility with Product Table Ultimate
* Updated: allow editing of upload fields
* Updated: use wp_get_attachment_image_url to get child product images
* Updated: check if quantity field does not exist for Bookings for WooCommerce
* Updated: markup for products-radio.php
* Updated: markup for checkbox.php
* Updated: markup for radio.php
* Updated: add child products prices to parent product price if Hide child products setting is enabled
* Updated: set Include variations as child products default to yes

= 3.9.7, 15 March 2022 =
* Added: save AJAX uploads if validation fails
* Added: pewc_cart_item_extras_child_product filter
* Fixed: PHP notice if default value is an array
* Fixed: enabling QuickView allows child products' attributes to override the parent product's
* Fixed: character settings do not appear for Text Preview in Global Add-Ons
* Fixed: tax issues on calculation fields
* Fixed: tax issues on column layout for child products
* Fixed: ensure scroll on pewc-steps-wrapper doesn't throw error
* Fixed: tax issue on minimum price field
* Fixed: minimum price gets rounded down if a currency's decimal separator is a comma
* Updated: removed wpo_wcpdf_after_item_meta

= 3.9.6, 25 January 2022 =
* Added: pewc_price_with_extras_before_calc_totals filter
* Added: pewc_after_add_cart_item_data filter
* Fixed: Aelia CS doubling conversion
* Fixed: parse error from Aelia CS integration

= 3.9.5, 17 January 2022 =
* Added: support for Aelia Currency Switcher
* Added: option to add to product weight on calculation fields
* Added: pewc_filter_quantity_field filter
* Added: pewc_maybe_adjust_tax_price_with_extras filter
* Added: pewc_export_orders_capability filter
* Fixed: global rules not displaying for Dutch sites
* Fixed: subdirectory changes for uploaded files when user is not logged in
* Fixed: variation specific fields with conditions not displaying correctly
* Fixed: conditions not loading on global add-ons page for Dutch language
* Fixed: apply pewc_before_update_field_all_params to global calculation fields on front end
* Fixed: look up tables on Global Add-ons
* Fixed: QuickView issue if product is used in more than one product fields
* Fixed: "contains" condition not working for product field column layout
* Fixed: calculations displayed as cost with negative value subtracting 1 from value
* Fixed: image swatch not working inside lightboxes
* Fixed: tooltip not working in mobile
* Updated: pewc_enable_numeric_options

= 3.9.4, 22 September 2021 =
* Added: Danish translation
* Fixed: parse error in functions-pro-helpers.php

= 3.9.3, 21 September 2021 =
* Fixed: image swatch optimised condition

= 3.9.2, 20 September 2021 =
* Added: pewc_child_product_title to products-radio.php template
* Added: styles for mobile products layout
* Added: maxThumbnailFilesize param to Dropzone
* Added: field param to pewc_dropzone_thumbnail_width and pewc_dropzone_thumbnail_height filters
* Added: option to return a zero value in a calculation for any fields that are not present on the page or that have an empty value
* Added: indent child products option
* Added: extra date parameters
* Added: pewc_child_product_title filter on select and column child products layout
* Added: pewc_filter_select_option_field filter
* Fixed: error message in cart for uploaded file when cancelling Klarna or Clearpay order
* Fixed: admin CSS enqueued correctly for Dutch language
* Fixed: variation-dependent add-on fields with field images leaves a gap when hidden
* Fixed: div container of uploaded images not displayed in cart
* Fixed: upload field price not displayed if field does not have a label and is not required
* Fixed: variation-dependent hidden calculation fields are still displayed
* Fixed: child product discount does not allow decimals
* Fixed: group title was misaligned in cart when displayed in some themes
* Fixed: when using the same variable product as child products in different fields, and when using Column layout, selected variations was not saved correctly
* Fixed: optimised conditions on products
* Fixed: add to cart button disabled for uploads re-enabled by calculations
* Fixed: clicking headings in steps layout could close group
* Fixed: checkbox label not displaying in order email when price hidden
* Updated: remove file size comparison in Dropzone queuecomplete
* Updated: set datepicker dynamically on field focus
* Updated: more information for message on unavailable Pro fields
* Updated: hide option and field price in summary panel when visibility setting requires
* Updated: performance improvements to pewc_update_total_js
* Updated: performance improvements to conditions.js

= 3.9.1, 10 August 2021 =
* Added: pewc_start_group_layout_options action
* Added: pewc_group_content_wrapper_class filter
* Added: pewc_group_layout filter
* Added: pewc_before_group_inner_tag_open action
* Added: pewc_before_group_inner_tag_close action
* Added: tooltipster filters
* Added: scroll to top when selecting next step
* Added: pewc_enable_numeric_options filter for conditions
* Added: pewc_all_conditions_by_field_id
* Added: pewc_get_tax_rate
* Fixed: JS error from Select Box on Product Table Ultimate
* Fixed: required Products field with Grid layout failing validation
* Fixed: conditions based on is-not for radio buttons not functioning correctly
* Fixed: missing variation in grid layout causing layout to break
* Updated: removed check for y_axis in pewc.js for look up tables
* Updated: changed order of submenu items
* Updated: changed method to count files uploaded to dropzone
* Updated: performance improvements to pewc_update_total_js

= 3.9.0, 15 June 2021 =
* Added: new settings for choosing where to display field and option prices
* Added: support for ACF fields in calculations via Advanced Calculations
* Added: trigger wcaouau-update-quantity-field on upload removal
* Added: pewc_update_select_box trigger
* Added: reset calculation field values
* Added: pewc_validate_child_products_stock filter to prevent parent product added to cart if child product is out of stock
* Added: new Home page in Product Add-Ons menu
* Fixed: set max chars with/without spaces in text fields
* Fixed: expanded select box hidden in accordion group
* Fixed: allow uploads with same filename
* Fixed: next/previous buttons miss hidden groups in steps layout

= 3.8.10, 3 May 2021 =
* Added: pewc_child_product_excerpt filter
* Added: pewc_v_term_name and pewc_h_term_name filters
* Added: option to display group titles in cart
* Added: use getimagesize to validate uploaded files
* Added: pewc_use_item_meta_in_order_item filter
* Added: reset values when using group conditions
* Fixed: disable dropzone while file is removed
* Fixed: image swatch option prices not updating for percentage variations
* Fixed: quickview template removing tabs and related products
* Fixed: update price correctly when removing uploaded file
* Fixed: >= condition not respected in cart
* Fixed: products fields not triggering group conditions
* Fixed: tabs layout on mobile
* Fixed: ensure calculation fields set price correctly on variable products
* Fixed: escape field attributes on single-product.php
* Fixed: updated metadata not shown in resent email notifications
* Fixed: min number of child products respected on column and checkbox layout only
* Fixed: product field select layout not discounting correctly
* Updated: cart editing allowed in Basic licence
* Updated: removed pewc_minicart_item_price filter
* Updated: resetting values through conditions restores defaults (in certain cases)

= 3.8.9, 25 March 2021 =
* Added: pewc_after_upload_script_init and pewc_dz_tpl_td actions
* Added: quantity param in $file object for uploads
* Added: pewc_remove_spaces_in_text filter
* Added: look up tables empty cells return null
* Fixed: group conditions not hiding correctly in the cart
* Fixed: group conditions not duplicating correctly
* Fixed: multiply price for number fields using percentage pricing
* Fixed: Multiply Price setting not saving on new number field
* Fixed: calculations always rounding to 2 decimal places
* Fixed: group conditions based on select box fields
* Updated: removed pewc_license_admin_notices nag

= 3.8.8, 11 March 2021 =
* Added: pewc_image_uploaded action
* Added: pewc_apply_underscore_metakey filter
* Added: pewc_qty_changed trigger to pewc.js
* Added: pewc_bypass_extra_fields_transient filter
* Fixed: calculated price not displaying correctly in cart
* Fixed: per character cost for preview text not added to cart
* Fixed: conditions not firing off radio buttons
* Fixed: deselected image swatch not updated in summary panel
* Fixed: group layout missing in group post type
* Updated: delete pewc_has_extra_fields_ transient on product save

= 3.8.7, 3 March 2021 =
* Added: steps layout for groups
* Added: option to force minimum product price
* Updated: conditions and calculations handling
* Updated: removed database update notice
* Updated: removed number_format from products-column.php
* Updated: licence key field for Advanced Calculations

= 3.8.6, 16 February 2021 =
* Added: integration with Advanced Preview extension
* Added: pewc_product_img_wrap filter for image replacement compatibility
* Added: QuickView option for child products
* Added: minimum price setting
* Fixed: parse error in functions-conditionals.php
* Fixed: child products displaying tax twice on product page
* Fixed: price suffix doubling after price
* Fixed: variable child products in columns layout not honouring discount on product page
* Updated: more reliable triggers for checking conditions and calculations

= 3.8.5, 8 February 2021 =
* Fixed: parse error from pewc_reset_hidden_fields
* Updated: filter image replacement container
* Updated: use data-src for image replacement

= 3.8.4, 8 February 2021 =
* Added: pewc_ignore_tax filter
* Added: fourth parameter to pewc_child_product_name filter
* Added: pewc-calculation-trigger class to fields which trigger calculations
* Added: pewc_add_to_cart_button_class filter
* Added: use select field value in look up tables
* Added: pewc_after_calculation_fields action
* Added: extra hooks for Advanced Preview extension
* Added: quantity parameter for renaming uploaded files
* Added: pewc_min_max_val_step filter
* Added: reset all transients on saving global groups
* Fixed: product checkbox quantities not honoured when editing a product from the cart
* Fixed: group conditions not firing on checkbox
* Fixed: group conditions when evaluating radio button value
* Fixed: tax applied twice to checkbox group options
* Fixed: 'Update Product' displaying on cart button for products without add-ons
* Fixed: look up fields not triggering correctly
* Fixed: bulk variations grid now displays cells in correct sequence irrespective of variations order
* Fixed: price suffix doubling
* Fixed: filter categories in global groups displayed as custom post
* Fixed: >= and <= operators not duplicating correctly
* Updated: enable variable subscription products in grid layout for products field
* Updated: moved pewc-radio-image-desc inside label element in products-radio.php
* Updated: don't deactivate plugin when WooCommerce is deactivated
* Updated: reformatted price so that currency symbol only wraps currency symbol
* Updated: Dutch translation
* Updated: show error message for troubleshooting licence activation issues
* Updated: allow groups to be filterable when wp_doing_ajax

= 3.8.3, 13 January 2021 =
* Fixed: pewc_multiply_independent_quantities_by_parent_quantity fatal error

= 3.8.2, 13 January 2021 =
* Added: duplicate global groups when using post type method
* Added: option to reset field value when hidden by a condition
* Added: pewc_default_product_column_value_before_checked filter
* Added: columns layout for groups
* Fixed: deselecting image swatch field failed to fire condition check
* Fixed: use floatval for child products discount field
* Fixed: group conditions attributes not formed correctly
* Fixed: conditions based on calculation field values not firing correctly
* Fixed: price label duplicating with Fees and Discounts
* Updated: check for options in image swatch template
* Updated: removed conditions from global groups
* Updated: moved product and calculation settings to separate tabs
* Updated: improved rounding on calculations
* Updated: use transients on product archive to check for select options button

= 3.8.1, 11 December 2020 =
* Fixed: Select Box compatibility with jQuery 3x
* Updated: AJAX uploader compatibility

= 3.8.0, 10 December 2020 =
* Added: allow look up tables to use calculation fields as axis values
* Added: pewc_global_variable_step filter
* Added: pewc_redirect_hidden_products
* Added: group conditions
* Added: greater than equals and less than equals condition operators
* Added: pewc_enable_groups_as_post_types option
* Fixed: don't display 'Edit options' on products with no add-ons
* Fixed: don't display 'Edit options' on child products
* Fixed: 'Select Options' text not showing for products with only global add-ons
* Fixed: fields with double quotes not firing in conditions
* Fixed: Bookings with add-ons incorrectly setting price
* Fixed: correct upgrade link on restricted field types
* Updated: create new field and group IDs by default when duplicating products
* Updated: use .one not .on in functions-conditions.php script
* Updated: price label updates correctly

= 3.7.25, 9 December 2020 =
* Fixed: AJAX uploader breaking with jQuery 3x

= 3.7.24, 26 November 2020 =
* Added: global helper functions for products and categories
* Fixed: new global field values not saving correctly
* Fixed: variation prices hidden
* Fixed: reinstated edit product text in cart
* Fixed: conditions with quantity correctly duplicated when duplicating product
* Fixed: update bulk grid quantities on keyup
* Updated: filter post_class only on single product page

= 3.7.23, 24 November 2020 =
* Fixed: performance issue with transients resetting
* Fixed: invalid child product ID causes fatal error in products layout templates
* Updated: apply pewc_number_field_step filter to default value
* Updated: check WC()->cart is object in cart

= 3.7.22, 12 November 2020 =
* Fixed: archive pages not recognising when products have add-ons
* Fixed: parse error in functions-products.php

= 3.7.21, 11 November 2020 =
* Added: pewc_disable_child_quantities
* Added: params for pewc_field_visibility_updated
* Added: options to hide child and parent items in the cart and order
* Added: pewc_child_product_name filter
* Added: error check for empty formula fields
* Added: compatibility with WooCommerce Currency Switcher
* Added: pewc_child_product_independent_quantity filter
* Added: variations grid layout for child products
* Fixed: check for error in wpml_post_language_details
* Fixed: child select fields not adding correctly to cart
* Fixed: child swatches fields not added correctly to cart
* Fixed: {field_XXX_option_price} not replacing field ID when duplicating products
* Fixed: field IDs not duplicating correctly in calculation formula
* Fixed: conditions not duplicating correctly
* Updated: allow child products on backorder
* Updated: removed default value parameter from get_transient
* Updated: pewc_has_product_extra_groups returns yes/no
* Updated: skip pewc_update_total_js on pewc_trigger_calculations if there are no active calculation fields
* Updated: use plus sign as default separator

= 3.7.20, 20 October 2020 =
* Added: $additional_info param to pewc_filter_field_description
* Added: pewc_get_transient_expiration filter
* Added: reset all transients
* Added: retain upload graphic option
* Fixed: upload fields overriding calculations setting product price
* Fixed: option price tax getting applied twice in certain circumstances
* Fixed: calculations for hidden variations incorrectly firing
* Fixed: independent child products not adding to cart correctly

= 3.7.19, 5 October 2020 =
* Fixed: image swatch fields always allowing multiple selections
* Fixed: options always setting price as percentage
* Fixed: parse error in functions-variations.php

= 3.7.18, 2 October 2020 =
* Fixed: ensure role based prices display correctly
* Fixed: field percentage and flat rate classes getting set incorrectly

= 3.7.17, 30 September 2020 =
* Added: pewc_description_as_placeholder filter
* Added: notice regarding add-on duplication
* Added: pewc_flat_rate_cost_text filter
* Added: option to dequeue scripts on non-product pages
* Fixed: update field ID tags when duplicating calculation fields
* Fixed: duplicated fields saved as pewc_group post type
* Fixed: duplicated fields with 0 value
* Fixed: calculations not working for checkbox groups
* Fixed: discount for child product applied to subsequent child products without discount
* Fixed: child products not addable when max stock not specified
* Fixed: use first variation if default variation not set in products-swatches.php
* Updated: disabled admin notice for inactive licence key
* Updated: on-page help for issues activating licences
* Updated: performance improvement on textarea fields

= 3.7.16, 12 September 2020 =
* Fixed: typo in plugin folder name

= 3.7.15, 4 September 2020 =
* Added: pewc_option_name filter
* Added: pewc_disable_add_to_cart option
* Added: Exclude SKUs from child variants option
* Added: Set Product Price option to Calculation fields
* Added: option to display tax suffix after add-on prices
* Added: pewc_after_cart_item_edit_options_text filter
* Fixed: percentage prices for image swatch and radio group options
* Fixed: error checking conditions for number uploads
* Fixed: indicate out of stock child products in radio layout
* Updated: removed duplicate label for upload fields from cart
* Updated: use pewc_cart_item_has_extra_fields to check for Update Product button
* Updated: allow default for products radio layout

= 3.7.14, 26 August 2020 =
* Added: option to update price on product page dynamically
* Fixed: cart line item price incorrect when field hidden by conditions

= 3.7.13, 20 August 2020 =
* Added: conditions based on number of uploaded files
* Fixed: use unformatted option price in select box template
* Fixed: correctly update option prices in image swatch, select and select box fields using percentage pricing
* Fixed: correctly add role-based option price to cart
* Fixed: ghost fields appearing in groups when editing as post types
* Fixed: checkbox default not saving in global fields
* Fixed: products fields that are variation specific and conditional adding costs on product page
* Fixed: check cost conditions from page load
* Fixed: prevent colour picker throwing error in WP5.5
* Updated: include default param in pewc_create_protection_files
* Updated: include private products in products global rule
* Updated: include empty categories in products global rule

= 3.7.12, 11 August 2020 =
* Fixed: uploaded image not added to order when file renaming empty

= 3.7.11, 2 August 2020 =
* Added: pewc_filter_item_value_in_cart filter in checkout and order
* Fixed: upload directory not changing by order if file renaming not enabled
* Updated: check pewc_do_initial_check is a function on pewc_check_conditions

= 3.7.10, 29 July 2020 =
* Added: pewc_filter_item_value_in_cart
* Added: default third param to pewc_cart_item_quantity
* Added: pewc_cart_item_has_extra_fields
* Added: include variations as child products setting
* Added: pewc_check_conditions event
* Added: Polylang support
* Fixed: non-image files not added to order meta
* Fixed: display file name for non-image files in cart and checkout
* Fixed: hidden fields in accordions set to auto height
* Fixed: prices for options with apostrophes or quotes not respected
* Fixed: flat rate radio buttons on summary panel
* Updated: respect WPML fallback setting
* Updated: background-color for pewc-group-content-wrapper
* Updated: trigger conditions and calculations check on file upload
* Updated: replaced all instances of li.pewc-item with .pewc-item in pewc.js

= 3.7.9, 23 July 2020 =
* Added: WPML config file
* Added: autocomplete attribute to number fields
* Added: pewc_always_show_cart_arrow filter
* Fixed: flat rate costs for select box options not added to totals
* Updated: remove duplicate uploads from Dropzone object
* Updated: correct separator for checkbox group options
* Updated: recalculate percentages using pewc_do_percentages event

= 3.7.8, 6 July 2020 =
* Fixed: default value not displaying correctly

= 3.7.7, 3 July 2020 =
* Added: colour picker field
* Added: pewc_condition_value_step filter
* Added: pewc_add_on_price_separator
* Fixed: all translations for global groups displaying when using WPML
* Fixed: filter to hide option prices in cart
* Updated: radio button layout in child products now deselectable
* Updated: run pewc_create_protection_files weekly

= 3.7.6, 11 June 2020 =
* Added: pewc_files_array filter
* Added: pewc_dropzone_thumbnail_width filter
* Added: pewc_dropzone_thumbnail_height filter
* Added: translation strings for Dropzone
* Fixed: Select Box prices not updating
* Fixed: parse error on line_total in functions-conditionals.php
* Fixed: checkbox group where some options had prices not getting added to cart correctly
* Updated: don't display child product IDs as meta data in the order

= 3.7.5, 27 May 2020 =
* Added: pewc_order_upload_dir and pewc_order_upload_url filters
* Fixed: missing URL to uploaded file in order screen
* Fixed: conditions not working for Select Box field
* Updated: pewc_dequeue_tooltips filter to avoid tooltipster conflicts with certain themes
* Updated: removed double quotes from radio field checked attribute

= 3.7.4, 21 May 2020 =
* Fixed: woocommerce_order_number filter calling order_id incorrectly

= 3.7.3, 15 May 2020 =
* Added: pewc_item_object_{$field_id} transient to reduce number of queries
* Fixed: missing conditions on 'OR' for number fields
* Updated: use pewc_maybe_include_tax when calculating option_price in cart

= 3.7.2, 12 May 2020 =
* Fixed: fields with multiple conditions not hidden correctly in cart
* Updated: enqueue wpColorPicker in Customizer
* Updated: include woocommerce_order_number filter when moving uploaded files

= 3.7.1, 7 May 2020 =
* Added: Select Box field type
* Added: category ID to category names in global rules
* Added: pewc_show_field_prices_in_order filter to hide all add-on prices in the order and order confirmation email
* Fixed: conditional fields visibility not recognised correctly
* Updated: renamed display name for renamed uploaded file
* Updated: check for order add-on meta data and display using old method if necessary
* Updated: respect tax settings where enter and display settings are contrary
* Updated: add-on field prices added to order meta data

= 3.7.0, 30 April 2020 =
* Added: option to rename uploaded files
* Added: option to organise uploads by order number
* Added: download all uploaded files per order
* Added: unserialised add-on meta data in order items
* Added: unserialised meta item data in export
* Added: option to upload PDFs
* Added: min/max settings for Image Swatches
* Added: add-on data to order again buttons
* Fixed: image swatch fields conditionally hidden displaying selection in summary panel
* Fixed: apostrophes in select fields failing conditional checks in cart
* Updated: removed pewc_maybe_include_tax from pewc_field_label

= 3.6.3, 14 April 2020 =
* Added: pewc_enable_assign_duplicate_groups filter allowing users to duplicate and assign groups to other products
* Updated: added default param to pewc_filter_field_title

= 3.6.2, 7 April 2020 =
* Added: pewc_field_item_price_step filter
* Fixed: new global group descriptions not saving
* Fixed: posted child product independent quantities not repopulating after failed validation
* Fixed: lightbox fields not updating fields in main page
* Updated: display variation get_formatted_name in child product select field in products-column.php
* Updated: enabled percentage pricing for Image Swatch options

= 3.6.1, 2 April 2020 =
* Added: pewc_multiply_child_product_quantities filter
* Fixed: missing $group_id in child products field
* Fixed: calculations not updating values
* Updated: full-width number field in Customizer

= 3.6.0, 1 April 2020 =
* Added: role-based prices for add-ons
* Added: pewc_get_field_price function
* Added: number field width setting to Customizer
* Added: enable per unit pricing for Number fields with Bookings for WooCommerce plugin
* Added: pewc_flat_rate_fee_is_taxable and pewc_flat_rate_fee_tax_class filters
* Added: pewc_default_field_value filter
* Added: pewc_check_did_action filter
* Added: optionally display original product price in cart and order
* Fixed: stripslashes for all fields when adding to cart
* Fixed: conditional values containing apostrophes
* Fixed: don't display min val and max val on non-number/NYP fields
* Fixed: save translated global groups when WPML is active
* Fixed: child products in select field displaying zero prices
* Fixed: prevent Customizer loading when theme is Hello Elementor
* Fixed: hidden, required fields with values as arrays getting incorrectly validated
* Fixed: deleting condition from multiple conditions deletes all conditions
* Updated: set field widths to 100% by default
* Updated: Dutch translation

= 3.5.5, 10 March 2020 =
* Added: don't display hidden calculation fields in cart or order
* Fixed: incorrectly counting line breaks in price per character fields
* Fixed: strip slashes from textarea fields

= 3.5.4, 4 March 2020 =
* Added: pewc_filter_global_categories_taxonomy filter
* Fixed: empty conditions for radio groups and image swatches not firing correctly
* Fixed: linked child product quantities not setting correctly
* Fixed: look up calculation not finding first index correctly

= 3.5.3, 26 February 2020 =
* Added: use product dimensions in calculations
* Added: pewc_display_child_product_meta filter to display child product IDs in parent product meta in cart
* Added: default values for products fields using select layout
* Added: pewc_menu_position filter to adjust menu position
* Fixed: parse error when exporting add-ons with incorrect order number
* Fixed: removed allow_none parameter when filtering remove item icon in cart
* Fixed: hidden child products getting added to cart
* Updated: added readonly parameter to date field
* Updated: set pewc-field-select_placeholder field type to text

= 3.5.2, 21 February 2020 =
* Fixed: select field options not added to flat rate
* Fixed: image swatch fields not editable
* Fixed: image swatch and checkbox group values sometimes getting carried into the next field's value
* Updated: changed 'View product' to 'Update options' in line with variable products

= 3.5.1, 20 February 2020 =
* Fixed: parse errors in functions-single-product.php

= 3.5.0, 20 February 2020 =
* Added: {look_up_table} parameter for calculation fields
* Added: initial support for replacing product image - checkbox and image swatch fields
* Added: pewc_group_display filter
* Fixed: hidden number fields with min/max values not validating correctly
* Fixed: not all global groups displaying when using post types
* Fixed: used floatval in $variant_price in products-column.php
* Fixed: allow 0 as default value
* Fixed: global group operator not saving correctly

= 3.4.4, 13 February 2020 =
* Fixed: conditions based on Products fields not setting correctly
* Fixed: radio group and swatch fields sometimes doubling option price
* Fixed: ensure totals don't display NaN from Bookings for WooCommerce add-ons

= 3.4.3, 11 February 2020 =
* Added: pewc_before_update_field_all_params filter
* Added: pewc_dropzone_timeout filter
* Added: pewc_itemmeta_admin_item filter
* Fixed: parse error in functions-cart.php from empty _child_quantity_
* Fixed: default values not saving on new fields
* Updated: format price totals with separator

= 3.4.2, 28 January 2020 =
* Added: extra styles in the Customizer
* Added: support for woocommerce_order_number filter
* Fixed: global fields getting deleted when updating product pages

= 3.4.1, 27 January 2020 =
* Added: pewc_filter_default_price for Fees and Discounts integration
* Added: original_price parameter in cart data for Fees and Discounts integration
* Fixed: fields without price not displaying in summary panel
* Fixed: standard upload field adding price even when empty

= 3.4.0, 21 January 2020 =
* Added: edit add-on fields in products already added to cart
* Added: pewc_hidden_field_types_in_cart filter to hide field types in cart and checkout
* Added: pewc_after_price_subtotal_table action
* Added: field styles in the Customizer
* Added: pewc_bypass_is_admin_check_in_groups_filter filter to avoid is_admin check when getting global groups
* Updated: use wp_kses_post for sanitising radio and image swatch option labels

= 3.3.9, 8 January 2020 =
* Added: uploaded image thumbnail to order pages
* Added: link to uploaded image thumbnail in order emails
* Added: pewc_end_get_item_data filter
* Added: pewc_add_order_itemmeta_admin filter
* Fixed: spaces removed in text fields when max chars is reached
* Fixed: missing dashicons on front end
* Fixed: check that options are defined in update_conditional_value_fields in admin-fields.js

= 3.3.8, 10 December 2019 =
* Added: pewc_rules_transient_pewc_group_xxx transients for condition rules
* Added: pewc_calculation_global_calculation_vars filter for multiple global variables
* Fixed: update field type when duplicating fields
* Fixed: 'is not' conditions not saving for image swatch fields
* Fixed: number format in data-option-cost in products-column.php
* Fixed: name conflict with ACF when removing groups
* Updated: removed input detection from pewc-radio-form-field and pewc-checkbox-form-field condition changes
* Updated: step pewc_variable_2 and 3

= 3.3.7, 21 November 2019 =
* Fixed: missing duplicated fields
* Fixed: multiple uploads not pricing correctly

= 3.3.6, 19 November 2019 =
* Added: pewc_duplicate_fields option to duplicate fields and groups when duplicating products
* Fixed: validation strings not translatable
* Fixed: parse error for missing $post->ID
* Fixed: Dropzone already attached error
* Fixed: group layout not saving in table layout
* Fixed: global field options not saving correctly after first option is deleted

= 3.3.5, 6 November 2019 =
* Added: new filters for add to cart buttons in blocks
* Added: pewc_apply_random_hash_child_product filter
* Added: pewc_order_item_product_name filter
* Fixed: uploaded files not displaying in cart and order
* Fixed: parse errors in export

= 3.3.4, 1 November 2019 =
* Fixed: parse error for View product button on some themes

= 3.3.3, 30 October 2019 =
* Fixed: parse error after add to cart validation for products without add-ons
* Fixed: only show thumbs for image files in the cart
* Fixed: radio group default option
* Updated: Elementor styles
* Updated: include original classes in button link in archives

= 3.3.2, 25 October 2019 =
* Fixed: group and field ordering

= 3.3.1, 24 October 2019 =
* Fixed: parse error for empty option values

= 3.3.0, 23 October 2019 =
* Added: pewc_enable_groups_as_post_types filter to view global groups as standard post types
* Added: pewc_enable_ajax_load_addons filter to load add-on fields on edit screens via AJAX
* Added: pewc_after_option_header and pewc_after_option_row actions
* Added: pewc_filter_validate_cart_item_status filter
* Added: pewc_filter_cart_item_data filter
* Added: pewc_filter_permitted_cats filter
* Added: use quantity in calculations
* Added: empty option in conditions based on select and radio fields
* Fixed: products select field showing zero prices
* Fixed: calculations decimal places setting to 0
* Fixed: thumbnail not displaying in AJAX uploader
* Updated: reverted default step attribute in Number fields to 1
* Updated: enable discount fields for all child product quantity types
* Updated: selected image swatch border colour
* Updated: mobile swatches single column
* Updated: products-column.php template to allow variation descriptions
* Updated: use pewc_global_order transient
* Updated: remove unnecessary queries for non-existent conditions
* Updated: use multiple variables for group and field parameters
* Updated: global groups now displayed as standard post types
* Updated: dropzone.js to 5.5.0

= 3.2.19, 18 October 2019 =
* Added: prefix_filter_field_option_name filter
* Added: pewc_filter_initial_accordion_states filter
* Fixed: correctly calculate percentages for options in cart
* Updated: allow textarea sanitisation
* Updated: sanitise information field using wp_kses_post

= 3.2.18, 16 October 2019 =
* Added: Elementor styles
* Fixed: percentages select options in simple products not calculating correctly
* Fixed: inactive variation dependent field costs included in total price on product page
* Fixed: radio options respect percentage setting
* Fixed: missing 'Default' param for select fields

= 3.2.17, 9 October 2019 =
* Added: pewc_number_field_step filter
* Fixed: correctly respect multiple conditions

= 3.2.16, 24 September 2019 =
* Added: pewc_hidden_group_types_in_order filter
* Updated: trigger calculations on page load
* Updated: allow calculations without input fields

= 3.2.15, 23 September 2019 =
* Added: $value parameter to pewc_filter_end_add_cart_item_data filter

= 3.2.14, 18 September 2019 =
* Added: $cart_item_data and $quantity parameters to pewc_get_conditional_field_visibility
* Added: conditions based on quantity
* Added: pewc_after_option_params action
* Added: multiple filters for AJAX file upload strings
* Fixed: correctly respect conditions based on products

= 3.2.13, 7 September 2019 =
* Fixed: pewc_filter_end_add_cart_item_data filter
* Fixed: child product checkbox layout respects discounts
* Fixed: strip slashes from text fields

= 3.2.12, 29 August 2019 =
* Added: pewc_filter_end_add_cart_item_data filter
* Fixed: information fields not displaying correctly for Basic licences

= 3.2.11, 20 August 2019 =
* Added: pewc_filter_child_products_method filter
* Fixed: incorrectly validating required upload fields

= 3.2.10, 17 August 2019 =
* Added: pewc_option_price_separator filter
* Added: additional parameters for pewc_filter_minchars_validation_notice and pewc_filter_minchars_validation_notice filters
* Fixed: allow multiple ajax uploads fields per product
* Fixed: min / max char validation only on required fields

= 3.2.9, 2 August 2019 =
* Fixed: JS error on upload fields

= 3.2.8, 1 August 2019 =
* Added: increased number of columns for image swatches
* Added: pewc_total_only_text filter
* Added: pewc_after_create_product_extra action
* Added: additional parameters for pewc_filter_validation_notice
* Fixed: respecting conditions based on products fields
* Fixed: media upload fields in group post types
* Fixed: respecting min and max chars in textareas
* Fixed: show min/max for new checkbox fields

= 3.2.7, 5 July 2019 =
* Fixed: checkbox swatches not toggling class
* Updated: extended pewc_is_group_public filter to all field types with options

= 3.2.6, 5 July 2019 =
* Added: filter to hide prices in options
* Updated: respect percentage setting for select field options
* Updated: greater than and less than operators for numeric field conditions

= 3.2.5, 3 July 2019 =
* Fixed: issues with conditionals for calculation fields

= 3.2.4, 1 July 2019 =
* Fixed: issues with AJAX uploads

= 3.2.3, 28 June 2019 =
* Fixed: Tabs and Accordion layout

= 3.2.2, 28 June 2019 =
* Fixed: JS error when dropzone.js not enqueued
* Fixed: JS error when formula missing in calculation field

= 3.2.1, 28 June 2019 =
* Added: AJAX upload option
* Fixed: allow multiple file uploads
* Fixed: global information fields not saving correctly
* Fixed: default radio button value not set
* Updated: reduce size of image thumb in order email

= 3.2.0, 24 June 2019 =
* Added: swatch option to variable child products
* Added: information field type
* Added: allow multiple uploads setting
* Fixed: escape condition fields with apostrophes
* Fixed: conditional field visibility not correctly evaluating on add to cart
* Updated: conditionally enqueue math.js

= 3.1.2, 13 June 2019 =
* Fixed: checkboxes in global groups not saving correctly

= 3.1.1, 13 June 2019 =
* Added: group layout option
* Fixed: clear product price when no variation set
* Updated: cost and action settings for calculation field
* Updated: exclude upload fields from conditions

= 3.1.0, 10 June 2019 =
* Added: calculation field

= 3.0.2, 8 June 2019 =
* Added: hide groups where all fields are hidden
* Added: option to attach uploaded images to order email
* Fixed: missing select_placeholder parameter
* Fixed: options in global conditions not populating correctly
* Fixed: incorrectly removing uploaded images
* Fixed: duplicated group and field conditions
* Fixed: default values not displaying correctly
* Updated: restored duplicate global groups
* Updated: reinstated allow_multiple parameter
* Updated: don't check character fields for non-text fields
* Updated: timing on initial page load for pewc_update_total_js

= 3.0.1, 4 June 2019 =
* Fixed: global groups not deleting correctly

= 3.0.0, 3 June 2019 =
* Added: allow html in group description
* Added: further front end template filters
* Added: pewc_flat_rate_label filter
* Fixed: checkbox group field values persisting in fields
* Fixed: image swatch prices not added
* Fixed: parse errors in field-item.php
* Fixed: parse error in field description
* Fixed: missing cost value in condition
* Fixed: JS error when setting condition rule fields
* Fixed: condition cost operator not setting correctly
* Fixed: removing conditions incorrectly hiding condition rules
* Fixed: checkbox default value not retained correctly
* Fixed: repeat pewc_update_total_js after running to help quicker browsers
* Updated: Pro fields visible to Basic users
* Updated: populate pewc_product_extra post with order details when customer is not registered
* Updated: CSS for globals page
* Updated: default total for variable products set to 0
* Updated: uploads no longer moved to media folder
* Updated: migrated product extras data to custom post type

= 2.8.6, 29 May 2019 =
* Added: updater upgrade functions

= 2.8.5, 21 May 2019 =
* Added: beta testing option
* Fixed: reinstated child product functions lost due to version control
* Fixed: zero value number field not validating correctly

= 2.8.4, 10 May 2019 =
* Fixed: hidden child products added to cart
* Updated: POT file and Dutch translation

= 2.8.3, 7 May 2019 =
* Fixed: correctly enqueue pewc-variations.js script

= 2.8.2, 6 May 2019 =
* Updated: changed plugin name to WooCommerce Product Add-Ons Ultimate

= 2.8.2, 3 May 2019 =
* Fixed: removed field price from Products field type
* Fixed: spaces and accented characters counted incorrectly
* Updated: deprecated Allow Multiple option from Products field

= 2.8.1, 1 May 2019 =
* Added: pewc_force_update_total_js trigger to JS
* Fixed: inactive variation specific fields updating price on product page
* Fixed: incorrect validation on hidden product fields with min/max products
* Updated: allow separate flat rate charges for variations
* Updated: reduced length of field ID string

= 2.8.0, 18 April 2019 =
* Added: product cost conditions
* Added: filter for multiple file uploads
* Fixed: default values not setting correctly
* Fixed: condition rules not saving correctly

= 2.7.0, 16 April 2019 =
* Added: minimum and maximum quantities for child product fields
* Fixed: variation prices not updating correctly
* Updated: additional methods for pewc-child-quantity-field field updates

= 2.6.1, 11 April 2019 =
* Updated: allow independent child products to be deleted in the cart
* Updated: allow independent child products quantities to be updated in the cart

= 2.6.0, 9 April 2019 =
* Added: column layout for child products
* Added: support for variable child products
* Fixed: parse error in global settings
* Updated: removed AJAX totals updater in pewc.js

= 2.5.1, 5 April 2019 =
* Fixed: mini cart returning zero price for products without extras

= 2.5.0, 4 April 2019 =
* Added: variation-specific fields
* Fixed: restrict per character pricing to text and textarea fields only
* Fixed: update product price in mini cart

= 2.4.12, 28 March 2019 =
* Added: allow conditions on checkbox groups and product fields
* Fixed: duplicate options for conditions

= 2.4.11, 17 March 2019 =
* Added: display upload thumbs in cart and checkout
* Fixed: conditional fields dependent on checkboxes not saving correctly
* Fixed: flat rate input fields not appearing in order confirmation
* Updated: disabled autocomplete for datepicker fields

= 2.4.10, 4 March 2019 =
* Fixed: conditions for radio groups not firing correctly

= 2.4.9, 21 February 2019 =
* Fixed: condition values getting overwritten

= 2.4.8, 19 February 2019 =
* Fixed: parse error when adding variable child product to cart

= 2.4.7, 16 February 2019 =
* Updated: licensing after site migration

= 2.4.6, 13 February 2019 =
* Updated: provide support for non-image uploads

= 2.4.5, 13 February 2019 =
* Added: better sanitisation for fields
* Added: key element for radio fields
* Fixed: remove child product from cart when parent quantity set to 0
* Fixed: new condition fields not retaining action and rule settings
* Fixed: pewc_get_permitted_mimes filter

= 2.4.4, 25 January 2019 =
* Fixed: changed permitted mime element to 'jpg|jpeg|jpe'	=> 'image/jpeg'
* Updated: removed simple products requirement from json_search in Products field

= 2.4.3, 21 January 2019 =
* Added: actions after each field
* Added: checkbox option for swatch field
* Added: pewc_name_your_price_step filter for Name Your Price field
* Fixed: missing checkbox group items in order screens
* Fixed: parse error in functions-conditionals.php
* Fixed: default values overriding submitted values
* Updated: field description now runs off pewc_after_field_template hook
* Updated: changed name of Radio Image to Image Swatch

= 2.4.2, 9 January 2019 =
* Added: pewc_filter_item_start_list filter
* Fixed: re-allow negative values for fields
* Fixed: parse error on missing placeholder in field-item.php
* Fixed: NaN error on child products with zero value

= 2.4.1, 24 December 2018 =
* Fixed: missing <li> tags in checkbox group
* Updated: change hook for creating new product extra to woocommerce_checkout_order_processed

= 2.4.0, 16 December 2018 =
* Added: German translation
* Added: customizer support
* Added: pricing and subtotal labels and options

= 2.3.2, 11 December 2018 =
* Fixed: conditionals dependent on radio groups not adding to cart correctly
* Fixed: undefined variable in global extras
* Fixed: added space between attributes in front end form fields

= 2.3.1, 27 November 2018 =
* Fixed: new global groups not saving correctly
* Fixed: removed esc_html from field names containing formatted prices

= 2.3.0, 22 November 2018 =
* Added: checkbox groups
* Added: products field in global extras
* Fixed: respect tax settings for product prices
* Fixed: respect tax settings for option prices
* Fixed: correctly calculate totals when using percentage fields
* Fixed: conditions dependent on checkboxes now functioning correctly
* Updated: formatted option prices
* Updated: changed pewc_get_price_for_display to pewc_maybe_include_tax
* Updated: percentage values for variations update dynamically
* Updated: removed pewc_filter_field_label filter to display percentage instead of price

= 2.2.3, 20 November 2018 =
* Fixed: global condition not retaining field from other group
* Updated: tweaked styles for default parameter in new fields

= 2.2.2, 13 November 2018 =
* Added: explanatory text in Product Extras page
* Added: explanatory text in Product Add-Ons page
* Fixed: removed escaping characters from field and group titles
* Fixed: global conditions not picking up fields from other groups
* Fixed: PHP error for missing pewc_product_hash
* Fixed: prevent order without Product Add-Ons generating a new product extra post
* Updated: changed dashicon to plus-alt
* Updated: changed post type label to 'Extras by Order'

= 2.2.1, 6 November 2018 =
* Fixed: prevent 'View Product' button displaying for products that don't have extras
* Updated: French, Italian and Spanish translations

= 2.2.0, 1 November 2018 =
* Added: child products (Pro only)
* Added: tooltips
* Fixed: validation for radio and select fields
* Fixed: 0 default values
* Fixed: missing prices for extras in order confirmation
* Fixed: hide flat rate items in product itemisation in order confirmation
* Fixed: min_date_today field not saving correctly
* Updated: improved price formatting for extras
* Updated: extra prices now respect the WooCommerce tax display setting
* Updated: improved UX for conditionals
* Updated: updated UI
* Updated: changed icon to wcicon-plus
* Updated: removed pewc_filter_is_purchasable and replaced with pewc_view_product_button

= 2.1.8, 31 October 2018 =
* Fixed: date field not validating correctly

= 2.1.7, 29 October 2018 =
* Fixed: Name Your Price field not validating correctly

= 2.1.6, 29 October 2018 =
* Fixed: Name Your Price field not validating correctly
* Fixed: select and radio fields not validating correctly

= 2.1.5, 22 October 2018 =
* Fixed: admin styles for select fields

= 2.1.4, 21 October 2018 =
* Added: 'Instruction only' option for select fields
* Fixed: field image in Global Add-Ons
* Fixed: radio button prices not updating correctly in totals

= 2.1.3, 18 October 2018 =
* Added: integration with WooCommerce PDF Invoices & Packing Slips
* Fixed: missing colon in order confirmation and emails
* Fixed: radio image buttons displaying arrays as labels

= 2.1.2, 30 September 2018 =
* Added: Dutch translation
* Fixed: flat rate pricing in radio buttons
* Fixed: retain field values after validation fails
* Updated: allow HTML in Description field

= 2.1.1, 27 September 2018 =
* Added: conditions for global extras
* Fixed: prevent non-object error in functions-order.php for empty $user object
* Fixed: add correct flat rate values for select and radio button fields
* Fixed: values of select fields not getting added to cart
* Updated: improved conditional field population using JS

= 2.1.0, 18 September 2018 =
* Added: allow free characters (Pro only)
* Added: only allow alphanumeric characters (Pro only)
* Added: only charge for alphanumeric characters (Pro only)
* Fixed: duplicated pewc-field-label class
* Fixed: correctly save Price Per Character value for new fields
* Updated: deprecated import feature
* Updated: text and textarea field templates

= 2.0.1, 13 September 2018 =
* Fixed: out of memory error in import-groups.php

= 2.0.0, 10 September 2018 =
* Added: Radio buttons with image backgrounds (Pro only)
* Added: Percentages (Pro only)
* Added: Group toggles and tabs (Pro only)
* Added: French translation
* Added: Italian translation
* Added: Spanish translation
* Added: upgrade action links
* Fixed: incorrect default value in text fields following a select or radio field
* Fixed: new condition field not showing select options
* Updated: better detection of radio button selection
* Updated: admin templates moved to templates/admin
* Updated: created separate template files for all field types on the frontend
* Updated: pewc_field_label returns value instead of echoing
* Updated: pewc_field_description returns value instead of echoing
* Updated: removed pewc-product-extra-group-wrap class in favour of pewc-group-wrap

= 1.7.4, 15 August 2018 =
* Added: Portuguese translation
* Added: WooCommerce Subscriptions support
* Fixed: formatting issue for 'Duplicate' link in Products table
* Updated: ensure pewc_product_extra_fields only runs once
* Updated: displays extra fields on all product types

= 1.7.3, 15 August 2018 =
* Fixed: radio button conditionals triggering duplicated fields
* Updated: add pewc-has-maxchars class correctly to fields

= 1.7.2, 14 August 2018 =
* Added: field images
* Added: filterable classes for group wrap div
* Added: prevent users entering more than the max chars for input fields
* Fixed: parse errors in empty field values
* Updated: .pot file

= 1.7.1, 2 August 2018 =
* Fixed: undefined qty for products without quantity selector

= 1.7.0, 1 August 2018 =
* Added: flat rate extras
* Fixed: total calculation error with right space currency position
* Fixed: global extras not showing on products with no local extras
* Updated: improved totals fields on product page

= 1.6.1, 30 July 2018 =
* Added: multiplier option on number fields
* Fixed: global extra rules

= 1.6.0, 30 July 2018 =
* Added: global extras
* Fixed: remove deleted conditions from front end
* Fixed: display options group for new radio and select fields

= 1.5.3, 21 June 2018 =
* Added: modal image viewer in Product Extras entries
* Added: modal image viewer in Product Add-Ons entries
* Fixed: deleting product extra group data on save
* Updated: set create_posts capability for pewc post type to do_not_allow

= 1.5.2, 14 May 2018 =
* Fixed: prices for multiple fields of the same type not totalling correctly

= 1.5.1, 3 May 2018 =
* Added: support for WooCommerce Print Invoices/Packing Lists

= 1.5.0, 27 April 2018 =
* Added: radio button group
* Added: default values
* Added: span wrapper for prices in cart meta data
* Added: discount pricing - select extras to reduce the product cost
* Fixed: too many parameters for pewc_order_item_name
* Updated: spaces no longer costed in cost per character fields

= 1.4.5, 6 April 2018 =
* Added: filter for Total heading on single product page
* Added: upload URLs in order meta
* Fixed: hidden required uploads forcing validation to fail

= 1.4.4, 6 April 2018 =
* Added: product extra line item meta on edit order screen

= 1.4.3, 4 April 2018 =
* Added: added pewc-description to description fields
* Added: permitted file type at add to cart validation
* Fixed: overwriting line items in Product Extras custom post type
* Fixed: overwriting line items in Product Add-Ons custom post type

= 1.4.2, 15 March 2018 =
* Updated: wrap order item prices in span tags

= 1.4.1, 20 February 2018 =
* Fixed: incorrectly adding variation price to cart
* Fixed: parse error for empty conditional
* Fixed: incorrectly priced file uploads

= 1.4.0, 9 February 2018 =
* Added: support for variable products
* Updated: default pewc_require_log_in set to no
* Updated: moved log in requirement to upload fields, not all fields

= 1.3.3, 22 January 2018 =
* Fixed: set product price in cart via woocommerce_add_cart_item
* Updated: improved integration with Bookings

= 1.3.2, 19 January 2018 =
* Added: added per_unit field for new fields

= 1.3.1, 17 January 2018 =
* Updated: improved Bookings for WooCommerce integration

= 1.3.0, 17 January 2018 =
* Added: support for Bookings for WooCommerce plugin

= 1.2.4, 16 January 2018 =
* Fixed: correctly remove associated conditions when field is deleted
* Updated: product name for updater

= 1.2.3, 22 November 2017 =
* Added: Price per character option for text input and textarea fields
* Updated: subtotal calculated directly in JS, not via AJAX
* Updated: allow Product Extras on simple products only
* Updated: allow Product Add-Ons on simple products only

= 1.2.2, 21 November 2017 =
* Added: Name Your Price field
* Added: min and max attributes for number fields
* Fixed: missing ID attribute in new field type fields

= 1.2.1, 13 November 2017 =
* Added: total field on product page
* Fixed: parse error condition_action
* Fixed: not adding hidden items to cart
* Updated: 'is-not' parameter not allowed for conditions on checkboxes

= 1.2.0, 8 November 2017 =
* Added: group and field duplication
* Updated: icon font to WooCommerce
* Updated: updater class

= 1.1.0, 6 November 2017 =
* Added: conditional fields

= 1.0.1, 14 October 2017 =
* Fixed: removed duplicate updater class

= 1.0.0, 14 October 2017 =
* Initial commit

== Upgrade Notice ==
