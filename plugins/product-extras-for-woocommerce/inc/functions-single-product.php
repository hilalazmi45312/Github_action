<?php
/**
 * Functions for the product page
 * @since 1.0.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return whether user can upload files
 * @return Boolean
 */
function pewc_can_upload() {
	$require_log_in = get_option( 'pewc_require_log_in', 'yes' );
	if( $require_log_in == 'yes' && ! is_user_logged_in() ) {
		return false;
	}
	return true;
}

function pewc_enqueue_scripts() {

	if( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
		return;
	}

	// Better performance
	$dequeue = get_option( 'pewc_dequeue_scripts', 'no' );
	if( $dequeue == 'yes' && ! is_product() ) {
		return;
	}

	global $product, $post;
	// $post_id = $post->ID;
	$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;

	if( pewc_enable_ajax_upload() == 'yes' ) {
		wp_enqueue_style( 'pewc-dropzone-basic', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/basic.min.css', array(), $version );
		wp_enqueue_style( 'pewc-dropzone', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/dropzone.min.css', array(), $version );
	}

	wp_enqueue_style( 'pewc-style', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/style.css', array( 'dashicons' ), $version );

	$deps = array( 'jquery', 'jquery-ui-datepicker' );
	$deps[] = version_compare( WC_VERSION, '10.3', '>=' ) ? 'wc-jquery-blockui' : 'jquery-blockui'; // 3.26.18

	// Only load math.js if we have a calculation field
	// Need to override this for Elementor???
	if( apply_filters( 'pewc_enqueue_calculation_script', isset( $post->ID ) && pewc_has_calculation_field( $post->ID ) ) ) {
		wp_enqueue_script( 'pewc-math-js', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/math.min.js', array(), '5.10.3', true );
		$deps[] = 'pewc-math-js';
	}

	// Only load the Iris-JS library if we have a color-picker field
	if( apply_filters( 'pewc_enqueue_color-picker_script', isset ( $post->ID ) && pewc_has_color_picker_field( $post->ID ) ) ) {
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, 1 );
    wp_enqueue_script( 'wp-color-picker', admin_url( 'js/color-picker.js' ), array( 'iris' ), false, 1 );
		if( version_compare( $GLOBALS['wp_version'], '5.5', '<' ) ) {
			wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n',
				array(
					'clear' => __( 'Clear', 'pewc' ),
					'defaultString' => __( 'Default', 'pewc' ),
					'pick' => __( 'Select Color', 'pewc' ),
					'current' => __( 'Current Color', 'pewc' ),
				)
			);
		} else {
			wp_set_script_translations( 'wp-color-picker' );
		}
  	}

	if( pewc_enable_tooltips() == 'yes' && ! apply_filters( 'pewc_dequeue_tooltips', false ) ) {
		wp_enqueue_style( 'pewc-tooltipster-style', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/tooltipster.bundle.min.css', array(), $version );
		wp_enqueue_style( 'pewc-tooltipster-shadow', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/tooltipster-sideTip-shadow.min.css', array(), $version );
		wp_register_script( 'tooltipster', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/tooltipster.bundle.min.js', $deps, $version, true );
		$deps[] = 'tooltipster';
	}

	if( pewc_enhanced_tooltips_enabled() == 'yes' ) {
		wp_register_script( 'pewc-enhanced-tooltips', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-enhanced-tooltips.js', $deps, $version, true );
		$deps[] = 'pewc-enhanced-tooltips';
	}

	if( isset( $post->ID ) && pewc_enable_progress_bar( $post->ID, array() ) != 'no' ) {
		wp_register_script( 'pewc-progress', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-progress.js', $deps, $version, true );
		$deps[] = 'pewc-progress';
	}

	wp_register_script( 'pewc-conditions', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/conditions.js', $deps, $version, true );
	$deps[] = 'pewc-conditions';

	if( pewc_enable_ajax_upload() == 'yes' ) {
		wp_register_script( 'pewc-dropzone', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/dropzone.js', $deps, $version, false );
		$deps[] = 'pewc-dropzone';
	}

	wp_register_script( 'dd-slick', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/select-box.js', array(), $version, true );
	$deps[] = 'dd-slick';

	// Using this for QuickView
	$deps[] = 'wc-single-product';

	// since 3.12.0, for formatting currency
	$deps[] = version_compare( WC_VERSION, '10.3', '>=' ) ? 'wc-accounting' : 'accounting'; // Issue with WC10.3

	wp_register_script( 'pewc-script', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc.js', $deps, $version, true );

	// JS Validation
	$optimised_validation = get_option( 'pewc_optimised_validation', 'no' );
	if ( 'yes' === $optimised_validation ) {
		wp_register_script( 'pewc-js-validation', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/js-validation.js', array( 'pewc-script' ), $version, true );
		//$deps[] = 'pewc-js-validation';
		wp_enqueue_script( 'pewc-js-validation' );
		wp_enqueue_style( 'pewc-js-validation', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/js-validation.css', array(), $version );
	}

	// 3.21.7, Clear All Options button
	if ( pewc_clear_all_enabled() && apply_filters( 'pewc_conditions_timer', 0 ) > 0 ) {
		wp_register_script( 'pewc-clear-all', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-clear-all.js', array( 'pewc-script' ), $version, true );
		wp_enqueue_script( 'pewc-clear-all' );
		wp_enqueue_style( 'pewc-clear-all', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/css/pewc-clear-all.css', array(), $version );
	}

	$vars = array(
		'ajaxurl'					=> admin_url( 'admin-ajax.php' ),
		'currency_symbol'			=> get_woocommerce_currency_symbol(),
		'decimal_separator'  		=> wc_get_price_decimal_separator(),
		'thousand_separator' 		=> wc_get_price_thousand_separator(),
		'decimals'           		=> wc_get_price_decimals(),
		'price_format'       		=> get_woocommerce_price_format(),
		'currency_pos' 				=> get_option( 'woocommerce_currency_pos' ),
		'variable_1'				=> get_option( 'pewc_variable_1', 0 ),
		'variable_2'				=> get_option( 'pewc_variable_2', 0 ),
		'variable_3'				=> get_option( 'pewc_variable_3', 0 ),
		'enable_tooltips'			=> pewc_enable_tooltips(),
		'dequeue_tooltips'			=> apply_filters( 'pewc_dequeue_tooltips', false ),
		'separator'					=> ' '.get_option( 'pewc_price_separator', false ).' ', // 3.21.2, pewc_add_on_price_separator() has HTML tags which is not needed when used in option texts
		'update_price'				=> pewc_get_update_price_label(),
		'disable_qty'				=> apply_filters( 'pewc_disable_child_quantities', true ),
		'product_gallery'			=> apply_filters( 'pewc_product_gallery', '.images' ),
		'product_img_wrap'			=> apply_filters( 'pewc_product_img_wrap', '.woocommerce-product-gallery__image, .woocommerce-product-gallery__image--placeholder' ),
		'calculations_timer'		=> apply_filters( 'pewc_calculations_timer', 0 ),
		'conditions_timer'			=> apply_filters( 'pewc_conditions_timer', 0 ),
		'remove_spaces'				=> apply_filters( 'pewc_remove_spaces_in_text', 'no' ),
		'math_round'				=> apply_filters( 'pewc_math_round', 'no' ),
		'disable_button_calcs'		=> apply_filters( 'pewc_disable_button_calcs', 'no' ),
		'disable_button_uploads'	=> pewc_disable_add_to_cart_upload() ? 'yes' : 'no',
		'null_signifier'			=> apply_filters( 'pewc_look_up_table_null_signifier', '*' ),
		'disable_wcfad_label'		=> get_option( 'pewc_disable_wcfad_price_label', 'no' ),
		'disable_wcfad_table'		=> get_option( 'pewc_disable_wcfad_pricing_table', 'no' ),
		'zero_missing_field'		=> get_option( 'pewc_zero_missing_field', 'no' ),
		'set_initial_key'			=> apply_filters( 'pewc_set_initial_key', 'no' ), // Used in look up tables to set first element to 0 if empty
		'pdf_count'					=> get_option( 'wcpauau_pdf_count', 'no' ),
		'layer_parent'				=> apply_filters( 'pewc_layer_parent', 'woocommerce-product-gallery__wrapper', $post ),
		'exclude_field_types'		=> pewc_progress_bar_exclude_field_types( $post ),
		'exclude_groups'			=> pewc_progress_bar_exclude_groups( $post ),
		'required_fields_only'		=> pewc_progress_bar_required_fields_only( $post ),
		'complete_by_groups'		=> pewc_percentage_complete_by_groups( $post ),
		'progress_bar_log'			=> pewc_progress_bar_log( $post ),
		'progress_bar_timeout'		=> pewc_progress_bar_timeout( $post ),
		'progress_text'				=> pewc_progress_bar_progress_text( $post ),
		'group_progress'			=> pewc_add_group_progress( $post ),
		'quickview'					=> pewc_child_product_quickview(),
		'pdf_count_timer'			=> apply_filters( 'pewc_pdf_count_timer', 500 ),
		'allow_text_calcs_fields' 	=> apply_filters( 'pewc_allow_text_calcs_fields', array() )
	);

	if( is_product() ) {
		$product = wc_get_product( $post->ID );
		$vars['show_suffix'] 	= pewc_show_price_suffix();
		$vars['price_suffix'] = $product->get_price_suffix();
		$vars['price_suffix_setting'] = get_option( 'woocommerce_price_display_suffix' );
		$vars['replace_image'] = pewc_get_add_on_image_action( $post->ID );
		$vars['allow_text_calcs'] = apply_filters( 'pewc_allow_text_calcs', false, $post->ID );

		// 3.13.2. Auto-focus on main image if an image swatch is selected
		// 3.22.1. Commented out the condition for cases where Replace main image is activated on the field level
		//if ( 'replace_hide' === $vars['replace_image'] ) {
			$vars['replace_image_focus'] = apply_filters( 'pewc_replace_image_focus', true, $post ) ? 'yes' : 'no';
			$vars['control_container'] = apply_filters( 'pewc_control_container', 'flex-control-thumbs', $post );
			$vars['control_list'] = apply_filters( 'pewc_control_list', 'li', $post );
			$vars['control_element'] = apply_filters( 'pewc_control_element', 'img', $post );
		//}

		// 3.9.8 tax computations
		$base_exc_tax = wc_get_price_excluding_tax( $product, $args = array( 'price' => 100, 'qty' => 1 ) );
		$base_inc_tax = wc_get_price_including_tax( $product, $args = array( 'price' => 100, 'qty' => 1 ) );
		$tax_display_shop = get_option('woocommerce_tax_display_shop');

		// we do the following to avoid double computations of tax, e.g. in case a user decides to use {price_including_tax} when woocommerce_tax_display_shop is already incl
		if ('yes' === get_option('woocommerce_prices_include_tax')) {
			$vars['percent_exc_tax'] = $tax_display_shop == 'incl' ? $base_exc_tax : 100;
			$vars['percent_inc_tax'] = $tax_display_shop == 'excl' ? 10000/$base_exc_tax : 100;
		}
		else {
			$vars['percent_exc_tax'] = $tax_display_shop == 'incl' ? 10000/$base_inc_tax : 100;
			$vars['percent_inc_tax'] = $tax_display_shop == 'excl' ? $base_inc_tax: 100;
		}

		$vars['contentAsHTML'] = apply_filters( 'pewc_tooltipster_html', false );
		$vars['autoClose'] = apply_filters( 'pewc_tooltipster_autoclose', true );
		$vars['interactive'] = apply_filters( 'pewc_tooltipster_interactive', false );
		$vars['hideOnClick'] = apply_filters( 'pewc_tooltipster_hide_on_click', false );
		$vars['trigger'] = apply_filters( 'pewc_tooltipster_trigger', 'custom' );
		$vars['triggerOpen'] = apply_filters( 'pewc_tooltipster_trigger_open', array('mouseenter' => true, 'tap' => true) );
		$vars['triggerClose'] = apply_filters( 'pewc_tooltipster_trigger_close', array('mouseleave' => true, 'originClick' => true, 'tap' => true) );

		// AOU character counter for text fields
		$vars['enable_character_counter'] = apply_filters( 'pewc_character_counter_enabled', true ) ? 'yes' : 'no';

		// Don't apply Dynamic Pricing and Discount Rules to add-on field prices
		$vars['disable_wcfad_on_addons'] = apply_filters( 'pewc_disable_wcfad_on_addons', false, $post->ID ) ? 'yes' : 'no';

		// since 3.12.0, enable toggling of add-to-cart button while calculations are running. Only works if calculations_timer > 0 aka optimised calculations. still beta
		$vars['disable_button_recalculate'] = apply_filters( 'pewc_disable_button_recalculate', false, $post->ID ) ? 'yes' : 'no';
		$vars['recalculate_waiting_time'] = apply_filters( 'pewc_recalculate_waiting_time', 700, $post->ID );
		$vars['calculating_text'] = apply_filters( 'pewc_calculating_text', __( 'Calculating...', 'pewc' ), $post->ID );
		$vars['default_add_to_cart_text'] = $product->single_add_to_cart_text();

		// 3.21.5
		if ( pewc_disable_add_to_cart_upload() ) {
			$vars['uploading_text'] = apply_filters( 'pewc_uploading_text', __( 'Uploading...', 'pewc' ), $post->ID );
		}

		// 3.13.7
		if ( 'steps' === pewc_get_group_display( $post->ID ) ) {
			$vars['steps_group_disable_scroll_to_top'] = apply_filters( 'pewc_steps_group_disable_scroll_to_top', false, $post->ID ) ? 'yes' : 'no';
		}

		// 3.17.2, setting line height in Select Box causes inconsistent heights if some options don't have prices. Used in assets/js/select-box.js
		// Set to true by default since it seems line height is no longer needed on WordPress default themes, including Storefront
		if ( apply_filters( 'pewc_disable_line_height_select_box', true ) ) {
			$vars['disable_line_height_select_box'] = 'yes';
		}

		if ( 'yes' === $optimised_validation ) {
			// 3.12.2
			if ( pewc_hide_totals_validation( $product ) ) {
				$vars['hide_totals_if_missing_required_fields'] = 'yes';
				$vars['hide_totals_timer'] = apply_filters( 'pewc_hide_totals_timer', 300, $post->ID );
			}
			// 3.13.7
			if ( pewc_disable_groups_required_completed( $product ) ) {
				$vars['disable_groups_if_missing_required_fields'] = 'yes';
				if ( empty( $vars['hide_totals_timer'] ) ) {
					$vars['hide_totals_timer'] = apply_filters( 'pewc_hide_totals_timer', 300, $post->ID ); // let's use the same timer as above, since we're going to loop through the required fields only once
				}
			}
			// 3.15.0
			if ( pewc_disable_scroll_on_steps_validation( $product ) ) {
				$vars['disable_scroll_on_steps_validation'] = 'yes';
			}
		}

		// Add attribute data for calculations
		$attribute_data = array();
		$attributes = $product->get_attributes();
		if( $attributes ) {
			foreach( $attributes as $attribute=>$data ) {

				if ( ! empty( $data['options'][0] ) ) {
					$term_id = $data['options'][0];
					$term_obj = get_term( $term_id );
					// $term_obj is null if this is a custom attribute
					if ( isset( $term_obj->name ) ) {
						$term_name = $term_obj->name;
						// Strip any non-numeric characters
						$value = preg_replace( "/[^0-9.]/", "", $term_name );
						$attribute_data[$attribute] = $value;
					}
				}
				continue;

			}
		}
		$vars['attribute_data'] = $attribute_data;

		// 3.21.3
		$vars['price_trim_zeros'] = apply_filters( 'woocommerce_price_trim_zeros', false ) ? 'yes' : 'no';

		// 3.26.5
		if ( 'yes' === $vars['pdf_count'] ) {
			$vars['counting_pages_text'] = __( 'Counting the number of pages...', 'pewc' );
		}

		// 3.21.4, load separate script for AOU/FD compatibility
		// 3.21.6, added dependency on wcfad-script, so that this is loaded after DPDR
		if( function_exists( 'wcfad_is_dynamic_pricing_enabled' ) && 'yes' === wcfad_is_dynamic_pricing_enabled() ) {
			wp_register_script( 'pewc-wcfad-compatibility', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-wcfad.js', array( 'pewc-script', 'wcfad-script', 'jquery' ), $version, true );
			wp_enqueue_script( 'pewc-wcfad-compatibility' );
		}
	}

	if( pewc_is_pro() && function_exists( 'pewc_multiply_independent_quantities_by_parent_quantity' ) ) {
		$vars['multiply_independent']	= pewc_multiply_independent_quantities_by_parent_quantity();
	}

	// Allow filterable global vars
	if( isset( $post->ID ) && pewc_has_calculation_field( $post->ID ) ) {
		$vars['global_calc_vars'] = apply_filters( 'pewc_calculation_global_calculation_vars', false );
	}

	if( isset( $post->ID ) ) {
		$vars['post_id'] = $post->ID;
		// $vars['drop_files_message'] = apply_filters( 'pewc_filter_drop_files_message', __( 'Drop files here to upload', 'pewc' ), $post->ID );
		$vars['accordion_toggle'] = apply_filters( 'pewc_filter_initial_accordion_states', false, $post->ID );
		$vars['close_accordion'] = apply_filters( 'pewc_close_accordion', 'no', $post->ID );
		$vars['reset_fields'] = pewc_reset_hidden_fields( $post->ID );

		$vars['set_child_quantity_default'] = pewc_set_child_quantity_default( $post->ID );

		$secondary_images = get_post_meta( $post->ID, 'pewc_secondary_images', true );
		$vars['child_swatch_ids'] = json_encode( pewc_get_swatch_child_ids( $secondary_images ) );

		$vars = apply_filters( 'pewc_localize_script_vars', $vars, $post->ID );
	
	}

	wp_localize_script(
		'pewc-script',
		'pewc_vars',
		$vars
	);

	wp_enqueue_script( 'pewc-script' );

}
add_action( 'wp_enqueue_scripts', 'pewc_enqueue_scripts' );

function pewc_enqueue_child_products_script() {

	$version = defined( 'PEWC_SCRIPT_DEBUG' ) && PEWC_SCRIPT_DEBUG ? time() : PEWC_PLUGIN_VERSION;
	wp_register_script( 'pewc-variations-script', trailingslashit( PEWC_PLUGIN_URL ) . 'assets/js/pewc-variations.js', array( 'jquery', 'pewc-script', 'wc-add-to-cart-variation' ), $version, true );
	wp_enqueue_script( 'pewc-variations-script' );

}
add_action( 'pewc_products_column_layout', 'pewc_enqueue_child_products_script', 10 );

/**
 * Display the product_extra fields
 */
function pewc_product_extra_fields() {

	// We added this to prevent some themes displaying fields twice
	// You can use the filter to ensure that other themes, i.e. Divi, will display the fields at all
	$did = did_action( 'woocommerce_before_add_to_cart_button' );
	if( $did > apply_filters( 'pewc_check_did_action', 1 ) ) {
		return;
	}

	global $product, $post;
	if( ! isset( $post->ID ) ) {
		return;
	}
	$post_id = $post->ID;
	$licence = pewc_get_license_level();

	if( $product->get_type() != 'simple' && $product->get_type() != 'variable' && $product->get_type() != 'simple_booking' ) {
		// return;
	}

	$product_extra_groups = pewc_get_extra_fields( $post_id );
	$group_ids = array_keys( $product_extra_groups );
	$all_group_conditions = pewc_get_all_group_conditions_fields( $group_ids );
	$calculation_components = pewc_get_all_calculation_components( $product_extra_groups );
	$calculation_dependents = pewc_get_all_child_qty_dependents( $product_extra_groups );

	// This is a list of fields which have conditions
	$all_field_conditions = pewc_get_all_field_conditions_fields( $product_extra_groups, $post_id );
	$field_conditions_by_field_id = pewc_get_conditions_by_field_id( $product_extra_groups, $post_id );

	// Use this list to get fields which fields are conditional on, i.e. fields which need to trigger a condition check when their value changes
	// If field 1234 has a condition to display if field 4567 is checked, then 4567 is a trigger for 1234
	$triggers = pewc_get_all_conditional_triggers( $all_field_conditions, $post_id );
	$triggers_for = pewc_get_triggers_for_fields( $all_field_conditions, $post_id );

	?>
	<script>
		var pewc_cost_triggers = <?php echo json_encode( pewc_get_triggered_by_field_type( $field_conditions_by_field_id, 'cost' ), JSON_NUMERIC_CHECK ); ?>;
		var pewc_quantity_triggers = <?php echo json_encode( pewc_get_triggered_by_field_type( $field_conditions_by_field_id, 'quantity' ), JSON_NUMERIC_CHECK ); ?>;
	</script>
	<?php

	// Use this to populate the summary panel
	$summary_panel = array();

	if( $product_extra_groups ) {

		// Check if this product has been reloaded from the cart
		$cart_key = ! empty( $_GET['pewc_key'] ) ? $_GET['pewc_key'] : false;
		$edited_fields = false;
		$child_fields = array();

		$cart = WC()->cart->cart_contents;
		$cart_item = false;

		if( isset( $cart[$cart_key] ) && pewc_user_can_edit_products() ) {

			// This product has already been added to the cart, so we're now editing it
			$cart_item = $cart[$cart_key];
			if( ! empty( $cart_item['product_extras'] ) ) {
				// This is an array of add-ons and values for this product
				$edited_fields = $cart_item['product_extras']['groups'];
			}

			// If this product has child products in the cart, let's find them
			if( ! empty( $cart_item['product_extras']['products']['child_products'] ) ) {

				// This field has children
				$pewc_parent_product_id = $cart_item['product_extras']['product_id'];
				$parent_field_id = $cart_item['product_extras']['products']['parent_field_id'];

				// Get the child field IDs
				foreach( $cart_item['product_extras']['products']['child_products'] as $child_field_id=>$child_field_data ) {
					// This builds an array of child products belonging to each Products field
					// E.g. pewc_group_4850_4866 => array( 525 => 525 )
					$child_fields[$child_field_data['field_id']][$child_field_id] = $child_field_id;
				}

				foreach( $cart as $child_cart_item_key=>$child_cart_item ) {

					if( ! empty( $child_cart_item['product_extras']['groups'] ) ) {
						// This is a parent item so we can skip it
						continue;
					}
					if ( empty( $child_cart_item['product_extras']['products']['child_field'] ) ) {
						// this is not a child product, skip
						continue;
					}
					if ( empty( $child_cart_item['product_extras']['products']['parent_field_id'] ) || $parent_field_id != $child_cart_item['product_extras']['products']['parent_field_id'] ) {
						// this is a child product of another product, skip
						continue;
					}

					// Now we're looking for quantities
					// Child Product ID => Quantity
					if( ! empty( $child_cart_item['product_extras']['products']['field_id'] ) && ! empty( $child_cart_item['product_extras']['product_id'] ) ) {
						// This is a child product of the product we're currently editing
						if ( ! empty( $child_cart_item['variation_id'] ) ) {
							// 3.20.1, this is a variant
							$child_fields[$child_cart_item['product_extras']['products']['field_id']][$child_cart_item['variation_id']] = $child_cart_item['quantity'];
						} else {
							$child_fields[$child_cart_item['product_extras']['products']['field_id']][$child_cart_item['product_extras']['product_id']] = $child_cart_item['quantity'];
						}
					}
				}

			}

			// Add a field so that we can use this cart key after we have finished editing
			printf(
				'<input type="hidden" id="pewc_delete_cart_key" name="pewc_delete_cart_key" value="%s">',
				esc_attr( $cart_key )
			);

		}

		// 3.15.1, both filters are used by Wishlists Ultimate
		$edited_fields = apply_filters( 'pewc_edited_fields', $edited_fields, $product_extra_groups, $post_id ); 
		$child_fields = apply_filters( 'pewc_edited_child_fields', $child_fields, $edited_fields, $product_extra_groups, $post_id );

		$display = pewc_get_group_display( $post_id );
		$groups_wrapper_classes = pewc_get_groups_wrapper_classes( $product_extra_groups, $post_id, $display );

		$secondary_images = get_post_meta( $post_id, 'pewc_secondary_images', true );
		$groups_wrapper_attributes = pewc_get_groups_wrapper_attributes( $product_extra_groups, $post_id, $secondary_images );

		printf(
			'<div class="%s" %s>',
			join( ' ', $groups_wrapper_classes ),
			$groups_wrapper_attributes
		);

		do_action( 'pewc_start_groups', array( $post_id, $product_extra_groups ) );

		// Check for permissions
		$can_upload = pewc_can_upload();
		$first_group_class = 'first-group';

		$number_teaser_fields = pewc_get_number_teaser_fields();
		$count_fields = 0;
		$group_index = 0;

		// 3.22.0, build a new array with repeated groups
		$all_groups = pewc_build_groups_array_with_repeated( $product_extra_groups );

		// Iterate through each group
		foreach( $all_groups as $group ) {

			$group_id = $group['id'];

			do_action( 'pewc_start_group', $group_id, $group, $group_index );

			$wrapper_classes = pewc_get_group_wrapper_classes( $group_id, $group_index, $first_group_class, $group, $post_id );

			$group_conditions = pewc_get_group_conditions( $group_id );
			$group_attributes = pewc_get_group_attributes( $group_conditions, $group_id, $group, $group_index );

			// since 3.12.0
			if ( false !== strpos( $group_attributes, '"field":"pa_' ) ) {
				$wrapper_classes[] = 'pewc-has-attribute-condition';
			}

			$first_group_class = '';

			printf(
				'<div id="%s" class="%s" %s>',
				'pewc-group-' . $group_id,
				join( ' ', $wrapper_classes ),
				$group_attributes
			);

			do_action( 'pewc_open_group_inner', $group_id, $group, $group_index );

				echo '<div class="pewc-group-heading-wrapper">';

					if ( ! empty( $group['title'] ) ) {
						$group_title = $group['title'];
					} else {
						$group_title = pewc_get_group_title( $group_id, $group, pewc_has_migrated() );
					}

					if( $group_title ) {
						echo apply_filters( 'pewc_filter_group_title', sprintf( '<h3>%s</h3>', esc_html( $group_title ) ), $group, $group_id );
					}
					$group_class = '';
					if( isset( $group['meta']['group_required'] ) ) {
						$group_class = 'require-' . $group['meta']['group_required'];
					}

					if( pewc_is_pro() && pewc_display_summary_panel_enabled() ) {
						$summary_panel[$group_id]['title'] = $group_title;
					}

				echo '</div>';//<!-- .pewc-group-heading-wrapper -->';

				$group_content_wrapper_class = apply_filters( 'pewc_group_content_wrapper_class', '', $group_id );
				echo '<div class="pewc-group-content-wrapper '. $group_content_wrapper_class .'">';

					$description = pewc_get_group_description( $group_id, $group, pewc_has_migrated() );

					if( $description ) {
						echo apply_filters(
							'pewc_filter_group_description',
							sprintf(
								'<p class="pewc-group-description">%s</p>',
								wp_kses_post( $description ),
								$group
							)
						);
					}

					$group_layout = pewc_get_group_layout( $group_id );

					if( strpos( $group_layout, 'cols' ) !== false ) {
						$group_class .= ' ' . $group_layout;
						$group_layout = 'ul';
					}

					$group_layout = apply_filters( 'pewc_group_layout', $group_layout, $group_id );

					echo '<' . $group_layout . ' class="pewc-product-extra-groups ' . esc_attr( $group_class ) . '">';

					if( $group_layout == 'table' ) {
						echo '<tbody>';
					}

					// Iterate through each field
					if( isset( $group['items'] ) ) {

						foreach( $group['items'] as $item ) {

							$label = isset( $item['field_label' ] ) ? $item['field_label' ] : '';

							$item = apply_filters( 'pewc_filter_item_start_list', $item, $group, $group_id, $post_id );

							if( isset( $item['field_type'] ) ) {

								$id = $item['id'];

								// 3.25.2, added 'front'
								$value = pewc_get_default_value( $id, $item, $_POST, 'front' );

								// Replace default value with editable value
								// 3.23.1, updated the last condition so that checkbox fields with default value are not affected
								if ( 'checkbox' === $item['field_type'] && 'checked' === $value && false !== $edited_fields ) {
									// 3.22.1, maybe the checkbox was unchecked, so uncheck the checkbox when editing the product
									if ( ! isset( $edited_fields[$group_id][$item['field_id']] ) ) {
										$value = '';
									}
								} else if( isset( $edited_fields[$group_id][$item['field_id']]['value_without_price'] ) ) {
									// This is a value from arrays without any prices getting in the way
									$value = isset( $edited_fields[$group_id][$item['field_id']]['value_without_price'] ) ? $edited_fields[$group_id][$item['field_id']]['value_without_price'] : $value;
								} else if( isset( $edited_fields[$group_id][$item['field_id']]['value'] ) ) {
									// Override any default values if we're editing a product - repopulate with existing values
									$value = isset( $edited_fields[$group_id][$item['field_id']]['value'] ) ? $edited_fields[$group_id][$item['field_id']]['value'] : $value;
								};

								// Check for existing values for product fields
								$quantity_field_values = array();

								if( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories' ) {

									if ( ! empty( $child_fields[$id] ) ) {
										// Get the list of child products seleected for this field
										$value = array_keys( $child_fields[$id] );
										// Use this to set the quantities
										$quantity_field_values = $child_fields[$id];
									} else if ( ! empty( $value ) && 'radio' === $item['products_layout'] && 'independent' === $item['products_quantities'] ) {
										// 3.12.2. This child product has a default value. Let's do this for this specific setup for now (Layout: Radio Images, Quantities: Independent)
										$quantity_field_values = array( 1 );
									}

								}

								// Ensure checkbox default is retained
								if( $value == 'checked' || $value == '__checked__' || ( 'checkbox' === $item['field_type'] && 1 == $value ) ) $value = 1;

								// Set the wrapper classes
								$required_class = '';
								if( isset( $item['field_required'] ) && ( $item['field_type'] != 'products' && $item['field_type'] != 'product-categories' ) ) {
									$required_class = 'required-field';
								}

								$classes = pewc_get_field_classes( $item, $id, $post_id, $product, $count_fields, $number_teaser_fields, $display, $all_group_conditions, $calculation_components, $calculation_dependents, $secondary_images );

								$field_price = pewc_get_field_price( $item, $product );

								if( pewc_is_pro() && pewc_display_summary_panel_enabled() ) {
									$summary_panel[$group_id]['fields'][$item['field_id']] = array(
										'label' => $label,
										'value'	=> $value,
										'price'	=> $field_price,
										'option_price_visibility'	=> isset( $item['option_price_visibility'] ) ? $item['option_price_visibility'] : false,
										'price_visibility'	=> isset( $item['price_visibility'] ) ? $item['price_visibility'] : false
									);
								}

								// Create a string for the existing value, used when editing the cart item
								$data_field_value = is_array( $value ) ? join( ', ', $value ) : $value;

								$attributes = array(
									'data-price'					=> $field_price,
									'data-id'						=> $id,
									'data-selected-option-price'	=> '',
									'data-field-id'					=> $item['field_id'],
									'data-field-type'				=> $item['field_type'],
									'data-field-price'				=> $field_price,
									'data-field-label'				=> $label,
									'data-field-value'				=> $data_field_value,
									'data-field-layered'			=> pewc_is_field_layered( $item ),
									'data-field-index'				=> $count_fields
								);

								// Since 3.11.6. This will be used on Calculation fields so only do this for Pro
								if ( pewc_is_pro() && ! empty( $item['option_price_visibility'] ) ) {
									$attributes['data-option-price-visibility'] = $item['option_price_visibility'];
								}

								// Set which groups this field is a condition trigger for
								if( isset( $all_group_conditions[$id] ) ) {
									$attributes['data-trigger-groups'] = json_encode( array_values( $all_group_conditions[$id] ) );
								}
								
								if( isset( $calculation_components[$item['field_id']] ) ) {
									$attributes['data-trigger-calculations'] = json_encode( array_values( $calculation_components[$item['field_id']] ) );
									// Check if this triggers a child product quantity
									if( $item['field_type'] == 'calculation' && ! empty( $item['child_qty_product_id'] ) ) {
										$attributes['data-trigger-child-qty'] = $item['child_qty_product_id'];
									}
									if( $item['field_type'] == 'calculation' && ! empty( $item['reverse_formula_field'] ) ) {
										$attributes['data-reverse-formula-field'] = $item['reverse_formula_field'];
									}
								}

								if( isset( $calculation_dependents[$item['field_id']] ) ) {
									$attributes['data-child-qty-set'] = $calculation_dependents[$item['field_id']];
								}
								$calculation_trigger = pewc_get_calculation_trigger_for_child_qty( $item['field_id'], $calculation_dependents );
								if( $calculation_trigger ) {
									$attributes['data-child-qty-triggered-by'] = $calculation_trigger;
								}

								// If this is a calculation field that sets a child product quantity, we might want to reverse the calculation when the quantity is updated
								// The reverse_input_field refers to the number field used in the calculation field
								if( ! empty( $item['reverse_input_field'] ) ) {
									$attributes['data-reverse-input-field'] = absint( $item['reverse_input_field'] );
								}

								if( isset( $all_field_conditions[$item['field_id']] ) ) {
									// data-trigger-fields holds a list of the fields that this field is conditional on
									$attributes['data-trigger-fields'] = json_encode( array_values( $all_field_conditions[$item['field_id']] ), JSON_NUMERIC_CHECK ); // Removes the quotes
									// Add a class to show that this field has a condition
									$classes[] = 'pewc-field-has-condition';
								}

								if( isset( $triggers_for[$item['field_id']] ) ) {
									$attributes['data-triggers-for'] = json_encode( array_values( $triggers_for[$item['field_id']] ), JSON_NUMERIC_CHECK ); // Removes the quotes
								}

								if( ! empty( $field_conditions_by_field_id[$item['field_id']] ) ) {
									$attributes['data-field-conditions-match'] = get_post_meta( $item['field_id'], 'condition_match', true );
									$attributes['data-field-conditions-action'] = get_post_meta( $item['field_id'], 'condition_action', true );
									$attributes['data-field-conditions'] = json_encode( $field_conditions_by_field_id[$item['field_id']], JSON_NUMERIC_CHECK ); // Removes the quotes
									if ( false !== strpos( $attributes['data-field-conditions'], '"field":"pa_' ) ) {
										$classes[] = 'pewc-field-has-attribute-condition';
									}
								}

								if( in_array( $item['field_id'], $triggers ) ) {
									$classes[] = 'pewc-field-triggers-condition';
								}

								if( pewc_reset_hidden_fields( $post_id ) == 'yes' ) {
									$default_value = pewc_get_default_value( $id, $item, $_POST );
									$attributes['data-default-value'] = is_array( $default_value ) ? join( ', ', $default_value ) : $default_value;
								} else {
									$attributes['data-default-value'] = '';
								}

								if( pewc_is_pro() ) {

									if( ! empty( $item['field_percentage'] ) && ! empty( $item['field_price'] ) ) {
										// Set the option price as a percentage of the product price
										$product_price = $product->get_price();
										$price = ( floatval( $field_price ) / 100 ) * $product_price;
										// Get display price according to inc tax / ex tax setting
										$price = pewc_maybe_include_tax( $product, $price );
										$attributes['data-price']	= $price;
										$attributes['data-percentage'] = floatval( $field_price );
									}

								}

								$attributes = apply_filters( 'pewc_filter_item_attributes', $attributes, $item );

								do_action( 'pewc_before_group_inner_tag_open', $item );

								// Print the field
								pewc_field( $item['field_id'], $item, $product, $id, $post_id, $classes, $attributes, $group_layout, $field_price, $value, $cart_item, $quantity_field_values );

								$count_fields++;

							}

						}

						if( $group_layout == 'table' ) {
							echo '</tbody>';
						}

					}

					echo '</' . $group_layout . '>';//<!-- .pewc-product-extra-groups -->';

					do_action( 'pewc_end_group_content_wrapper', $group, $group_id, $display, $product_extra_groups, $product );

				echo '</div>';//<!-- .pewc-group-content-wrapper -->';

				do_action( 'pewc_close_group_inner', $group_id, $group, $group_index );

			echo '</div>';//<!-- .pewc-group-wrap -->';

			do_action( 'pewc_end_group', $group_id, $group, $group_index );

			$group_index++;

		}

		/**
		 * @hooked pewc_display_summary_panel				5
		 * @hooked pewc_totals_fields								10
		 * @hooked pewc_hidden_fields_product_page	20
		 */
		do_action( 'pewc_after_group_wrap', $post_id, $product, $summary_panel );

		echo "\n".'</div>';//<!-- .pewc-product-extra-groups-wrap -->';

	}

	do_action( 'pewc_after_product_fields' );

}
add_action( 'woocommerce_before_add_to_cart_button', 'pewc_product_extra_fields' );
// add_action( 'woocommerce_before_single_variation', 'pewc_product_extra_fields' );

/**
 * Prints the mark up for each field
 * @param $field_id				String	The field ID (e.g. 5678)
 * @param $item					Array	The field settings
 * @param $product				Object	The product object
 * @param $id					String	The field ID (including group ID e.g. pewc_group_1234_5678)
 * @param $post_id				Int		The post ID
 * @param $classes				Arrray	The field wrapper classes
 * @param $attributes			Array	The field wrapper data attributes
 * @param $group_layout			String	The group layout setting, e.g. 'table'
 * @param $field_price			Int		The field price

 * @since 3.9.2
 */
function pewc_field( $field_id=false, $item=array(), $product=false, $id=false, $post_id=false, $classes=array(), $attributes=array(), $group_layout='ul', $field_price=false, $value=false, $cart_item=false, $quantity_field_values=array() ) {

	// Get our field settings using just the field ID
	if( empty( $item ) && $field_id ) {
		$item = pewc_create_item_object( $field_id );
	}

	// Get the field price if we don't already have it
	if( ! $field_price ) {
		$field_price = pewc_get_field_price( $item, $product );
	}

	$group_inner_tag = 'li';
	$cell_tag = 'div';
	$open_td = '';
	$close_td = '';
	if( $group_layout == 'table' ) {
		$group_inner_tag = 'tr';
		$cell_tag = 'td';
		$open_td = '<td>';
		$close_td = apply_filters( 'pewc_before_close_td', '</td>', $item, $id );
	}

	$attribute_string = '';
	foreach( $attributes as $attribute=>$attribute_value ) {
		$attribute_string .= " " . $attribute . "='" . esc_attr( $attribute_value ) . "'";
	} ?>

	<<?php echo $group_inner_tag; ?> class="pewc-item pewc-group <?php echo join( ' ', $classes ); ?>" <?php echo $attribute_string; ?> <?php do_action( 'pewc_field_inner_tag', $item ); ?>>

		<?php // Check for an image
		$field_image = pewc_get_field_image( $item, $id );
		if( $field_image ) {

			$full_size_image_url = pewc_get_field_image_url( $item, 'full' );

			// Don't display the image if we're using it to replace the main image
			if( pewc_get_add_on_image_action( $post_id ) == 'replace_hide' ) {

				printf(
					'<span data-image-full-size="%s" data-large_image_width="%s" data-large_image_height="%s" class="pewc-item-field-image-wrapper" style="display: none;"></span>',
					$full_size_image_url[0],
					$full_size_image_url[1],
					$full_size_image_url[2]
				);
			} else {

				printf(
					'<%s data-image-full-size="%s" class="pewc-item-field-image-wrapper">%s</%s>',
					$cell_tag,
					$full_size_image_url[0],
					$field_image,
					$cell_tag
				);

			}

		} else if( ! $field_image && $group_layout == 'table' ) {

			// Include an empty td to ensure table columns are equal
			echo '<td></td>';

		}

		if( $group_layout == 'ul' ) {
			echo '<' . $cell_tag . ' class="pewc-item-field-wrapper">';
		}

		// Include the field template
		$file = str_replace( '_', '-', $item['field_type'] ) . '.php';

		if( $file ) {

			// 3.22.0, change input form name to an array if group is repeatable
			$name = apply_filters( 'pewc_filter_input_id', $id, $item );

			if( $file == 'radio-image.php') $file = 'image-swatch.php';

			/**
			 * @hooked pewc_before_frontend_template
			 */
			do_action( 'pewc_before_include_frontend_template', $item, $id, $group_layout, $file );

			$path = pewc_include_frontend_template( $file );
			if( $path ) {
				include( $path );
			}

			/**
			 * @hooked pewc_after_frontend_template	10
			 */
			do_action( 'pewc_after_include_frontend_template', $item, $id, $group_layout, $file );

		}

		/**
		 * @hooked pewc_field_description_list_layout
		 */
		do_action( 'pewc_after_field_template', $item, $id, $group_layout );

		if( $group_layout == 'ul' ) {
			echo '</' . $cell_tag . '>';
		}

		do_action( 'pewc_before_group_inner_tag_close', $item, $group_layout ); ?>

	</<?php echo $group_inner_tag; ?>><!-- .pewc-item -->

<?php }

/**
 * Return the claasses for the groups wrapper
 * @since 3.6.0
 */
function pewc_get_groups_wrapper_classes( $product_extra_groups, $post_id, $display ) {

	$groups_wrapper_classes = apply_filters(
		'pewc_filter_groups_wrapper_classes',
		array(
			'pewc-product-extra-groups-wrap'
		),
		$product_extra_groups,
		$post_id
	);

	$groups_wrapper_classes[] = 'pewc-groups-' . $display;

	$number_teaser_options = pewc_get_number_teaser_options();
	if( $number_teaser_options ) {
		$groups_wrapper_classes[] = 'pewc-teaser-options-'. $number_teaser_options;
	}

	$retain_upload = get_option( 'pewc_retain_dropzone', 'no' );
	if( $retain_upload == 'yes' ) {
		$groups_wrapper_classes[] = 'retain-upload-graphic';
	}

	return apply_filters( 'pewc_groups_wrapper_classes', $groups_wrapper_classes, $product_extra_groups, $post_id );

}

/**
 * Return the attributes for the groups wrapper
 * @since 3.20.0
 */
function pewc_get_groups_wrapper_attributes( $product_extra_groups, $post_id, $secondary_images ) {

	$attributes = array();
	$attribute_string = '';

	// Secondary images are used for swatch fields to allow different base layers
	if( $secondary_images ) {
		
		$parent_field_id = pewc_get_swatch_parent_id( $secondary_images );
		if( $parent_field_id ) {
			$attributes['data-swatch-parent-id'] = $parent_field_id;
		}

		$child_field_ids = pewc_get_swatch_child_ids( $secondary_images );
		if( $child_field_ids ) {
			$attributes['data-swatch-child-ids'] = json_encode( $child_field_ids );
		}

	}

	foreach( $attributes as $attribute=>$attribute_value ) {
		$attribute_string .= " " . $attribute . "='" . $attribute_value . "'";
	}

	return $attribute_string;

}

/**
 * Get the swatch parent field ID
 * The parent field is the one that determines which child swatches to display
 * E.g. in a custom tshirt designer, the parent field is the 'View' field - like front, back, side etc
 * @since 2.0.0
 */
function pewc_get_swatch_parent_id( $secondary_images ) {

	$parent_field_id = false;

	if( $secondary_images ) {
		
		$keys = array_keys( $secondary_images );
		$parent_field_id = ! empty( $keys[0] ) ? $keys[0] : false;

	}

	return $parent_field_id;

}

/**
 * Get the swatch child field IDs
 * The child fields are the swatch fields that define which image to display for the selected parent swatch
 * E.g. in a custom tshirt designer, the child fields are the different colour swatches for front, back, etc
 * @since 2.0.0
 */
function pewc_get_swatch_child_ids( $secondary_images ) {

	$swatch_child_ids = array();

	if( $secondary_images ) {
		$swatch_child_ids = array_values( $secondary_images );
		$swatch_child_ids = $swatch_child_ids[0];
	}

	return $swatch_child_ids;

}

/**
 * Return the claasses for the group wrapper
 * @since 3.19.1
 */
function pewc_get_group_wrapper_classes( $group_id, $group_index, $first_group_class, $group, $post_id ) {

	$wrapper_classes = apply_filters(
		'pewc_filter_group_wrapper_class',
		array(
			'pewc-group-wrap',
			'pewc-group-wrap-' . $group_id,
			'pewc-group-index-' . $group_index,
			$first_group_class
		),
		$group_id,
		$group,
		$post_id
	);

	$group_classes = pewc_get_group_class( $group_id );
	if( $group_classes ) {
		$wrapper_classes[] = $group_classes;
	}

	return apply_filters( 'pewc_group_wrapper_classes', $wrapper_classes, $group_id, $group_index, $first_group_class, $group, $post_id );

}

/**
 * Return the attributes for the group wrapper
 * @since 3.8.0
 */
function pewc_get_group_attributes( $conditions, $group_id, $group, $group_index ) {

	$attribute_string = "";

	$action = pewc_get_group_condition_action( $group_id, $group );

	$match = pewc_get_group_condition_match( $group_id );

	$attributes = array(
		'data-group-id'				=> $group_id,
		'data-group-index'			=> $group_index,
		'data-condition-action'		=> $action,
		'data-condition-match'		=> $match,
		'data-conditions'			=> json_encode( $conditions )
	);

	foreach( $attributes as $attribute=>$attribute_value ) {
		$attribute_string .= " " . $attribute . "= '" . $attribute_value . "'";
	}

	return $attribute_string;

}

/**
 * Insert some hidden fields with useful values
 * @since 3.5.3
 */
function pewc_hidden_fields_product_page( $post_id, $product, $summary_panel ) {

	// Hidden fields with product data
	if( $product->is_type( 'variable' ) ) {
		$default_price = 0;
	} else {
		if( function_exists( 'wcfad_get_regular_price' ) ) {
			// Ensure these filters are running for F+D
			add_filter( 'woocommerce_product_get_price', 'wcfad_get_regular_price', 10, 2 );
			add_filter( 'woocommerce_product_variation_get_price', 'wcfad_get_regular_price', 10, 2 );
		}
		$default_price = $product->get_price();

		// 3.26.11, add attributes so that conditions that use attributes work
		$attributes = $product->get_attributes();
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $attr_taxonomy => $attr ) {
				$attr_slugs = $attr->get_slugs();
				if ( ! empty( $attr_slugs ) && is_array( $attr_slugs ) ) {
					$attr_values = implode( ',', $attr_slugs );
					echo '<input type="hidden" name="' . $attr_taxonomy . '" id="' . $attr_taxonomy . '" value="' . esc_attr( $attr_values ) . '" >';
				}
			}
		}
	}

	// Look for anything that might have changed the price
	$default_price = apply_filters( 'pewc_filter_default_price', $default_price, $product );

	// Fields used for pricing
	echo '<input type="hidden" id="pewc-product-price" name="pewc-product-price" value="' . pewc_maybe_include_tax( $product, $default_price ) . '">';
	// added 3.12.0, for WCFAD compatibility
	if( function_exists( 'wcfad_get_regular_price' ) ) {
		echo '<input type="hidden" id="pewc-product-price-original" name="pewc-product-price-original" value="' . pewc_maybe_include_tax( $product, $default_price ) . '">';
	}
	echo '<input type="hidden" id="pewc_calc_set_price" name="pewc_calc_set_price" data-calc-set value="">';
	echo '<input type="hidden" id="pewc_total_calc_price" name="pewc_total_calc_price" value="' . pewc_maybe_include_tax( $product, $default_price ) . '">';
	echo '<input type="hidden" id="pewc_variation_price" name="pewc_variation_price" value="">';

	// Fields used for product dimensions
	printf(
		'<input type="hidden" id="pewc_product_length" name="pewc_product_length" value="%s">',
		$product->get_length()
	);
	printf(
		'<input type="hidden" id="pewc_product_width" name="pewc_product_width" value="%s">',
		$product->get_width()
	);
	printf(
		'<input type="hidden" id="pewc_product_height" name="pewc_product_height" value="%s">',
		$product->get_height()
	);
	printf(
		'<input type="hidden" id="pewc_product_weight" name="pewc_product_weight" value="%s">',
		$product->get_weight()
	);

	printf(
		'<input type="hidden" id="pewc_product_id" name="pewc_product_id" value="%s">',
		$product->get_id()
	);

	// Variations grid
	echo '<input type="hidden" name="pewc-grid-total-variations" id="pewc-grid-total-variations" value="">';

	// Nonces
	wp_nonce_field( 'pewc_file_upload', 'pewc_file_upload' );
	wp_nonce_field( 'pewc_total', 'pewc_total' );

}
add_action( 'pewc_after_group_wrap', 'pewc_hidden_fields_product_page', 10, 4 );

/**
 * Display the totals fields
 * @since 3.5.3
 */
function pewc_totals_fields( $post_id, $product, $summary_panel ) {

	$show_totals = apply_filters( 'pewc_product_show_totals', get_option( 'pewc_show_totals', 'all' ), $post_id );
	if( $show_totals == 'all' ) {
		$path = pewc_include_frontend_template( 'price-subtotals.php' );
		include( $path );
	} else if( $show_totals == 'total' ) {
		printf(
			'<p class="pewc-total-only">%s<span id="pewc-grand-total" class="pewc-total-field"></span></p>',
			apply_filters( 'pewc_total_only_text', '', $post_id )
		);
	}

}
add_action( 'pewc_after_group_wrap', 'pewc_totals_fields', 20, 4 );

/**
 * Display the field label
 * For all fields except checkbox in list view
 */
function pewc_before_frontend_template( $item, $id, $group_layout, $file ) {
	if( $group_layout == 'table' || ( $item['field_type'] != 'checkbox' && $item['field_type'] != 'checkbox-list' ) ) {
		echo pewc_field_label( $item, $id, $group_layout );
	}
}
add_action( 'pewc_before_include_frontend_template', 'pewc_before_frontend_template', 10, 4 );

/**
 * Display the field label for the checkbox in list view
 */
function pewc_after_frontend_template( $item, $id, $group_layout, $file ) {
	if( $group_layout == 'ul' && ( $item['field_type'] == 'checkbox' || $item['field_type'] == 'checkbox-list' ) ) {
		echo pewc_field_label( $item, $id, $group_layout );
	}
}
add_action( 'pewc_after_include_frontend_template', 'pewc_after_frontend_template', 10, 4 );

/**
 * Populate the products currently assigned to categories
 *
 * @param array $item
 * @param array $group
 * @param int $group_id
 * @param int $post_id
 * @return array $item
 */
function pewc_populate_product_categories_fields( $item, $group, $group_id, $post_id ) {

	if( $item['field_type'] !== 'product-categories' ){

		return $item;
	}

	if( !isset( $item['child_categories']) || empty( $item['child_categories'])){

		return $item;
	}

	$child_products = pewc_get_product_categories_addon_products( $item['field_id'], $item['child_categories'] );

	if( $child_products ){

		$item['child_products'] = $child_products;
	}

	return $item;
}
add_filter( 'pewc_filter_item_start_list', 'pewc_populate_product_categories_fields', 10, 4 );

/**
 * Get the field classes
 * @since	3.6.0
 * @version	3.24.8
 */
function pewc_get_field_classes( $item, $id, $post_id, $product, $count_fields, $number_teaser_fields, $display, $all_group_conditions=false, $calculation_components=false, $calculation_dependents=array(), $secondary_images=array() ) {

	$classes = array( $id );

	$classes[] = 'pewc-group-' . esc_attr( $item['field_type'] );
	$classes[] = 'pewc-item-' . esc_attr( $item['field_type'] );
	$classes[] = 'pewc-field-' . esc_attr( $item['field_id'] );
	$classes[] = 'pewc-field-count-' . esc_attr( $count_fields );

	if( in_array( $item['field_type'], array( 'radio', 'checkbox', 'checkbox_group' ) ) ) {
		if ( ! empty( $item['field_display_as_swatch'] ) ) {
			$classes[] = 'pewc-text-swatch';
		} else {
			$classes[] = 'pewc-option-list';
		}
	}

	// 3.24.8
	if ( in_array( $item['field_type'], array( 'select', 'select-box' ) ) && ! empty( $item['first_field_empty'] ) ) {
		$classes[] = 'pewc-first-field-empty';
	}

	// Hide certain fields if we're using the lightbox
	if( $display == 'lightbox' && $count_fields >= $number_teaser_fields ) {
		$classes[] = 'pewc-hidden-teaser-field';
	} else if( $display == 'lightbox' && $count_fields < $number_teaser_fields ) {
		$classes[] = 'pewc-lightbox-field';
	}

	if( ! empty( $item['field_required'] ) ) {
		$classes[] = 'required-field';
	}
	if( pewc_is_field_hidden( $item, $post_id ) ) {
		$classes[] = 'pewc-hidden-field';
	}
	if( ! empty( $item['field_price'] ) ) {
		$classes[] = 'pewc-has-field-price';
	}
	if( ! empty( $item['per_character'] ) ) {
		$classes[] = 'pewc-per-character-pricing';
	}
	if( ! empty( $item['field_maxchars'] ) ) {
		$classes[] = 'pewc-has-maxchars';
	}
	if( ! empty( $item['multiply'] ) ) {
		$classes[] = 'pewc-multiply-pricing';
	}
	if( ! empty( $item['field_flatrate'] ) ) {
		$classes[] = 'pewc-flatrate';
	}
	if( ! empty( $item['variation_field'] ) ) {
		$classes[] = 'pewc-variation-dependent';
	}
	if( ! empty( $item['field_percentage'] ) ) {
		$classes[] = 'pewc-percentage';
	}

	if( ! pewc_display_option_prices_product_page( $item ) ) {
		$classes[] = 'pewc-hide-option-price';
	}

	$hidden_calculation = ! empty( $item['hidden_calculation'] );
	if( $hidden_calculation && $item['field_type'] == 'calculation' ) {
		$classes[] = 'pewc-hidden-calculation';
	}

	$circular_swatches = pewc_get_circular_swatches( $item, $post_id );
	if( $circular_swatches ) {
		$classes[] = 'pewc-circular-swatches';
	}

	// Hex value for image swatches
	if( $item['field_type'] == 'image_swatch' && ! empty( $item['field_options'][0]['hex'] ) ) {
		$classes[] = 'pewc-has-hex';
	}

	$field_image = pewc_get_field_image( $item, $id );
	if( $field_image ) {
		$classes[] = 'pewc-has-field-image';
	}

	if( ( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories' ) && ! empty( $item['products_layout'] ) ) {
		$classes[] = 'pewc-item-products-' . esc_attr( $item['products_layout'] );
	}

	if( ( $item['field_type'] == 'products' || $item['field_type'] == 'product-categories' ) && $item['products_quantities'] == 'independent' ) {
		$classes[] = 'pewc-item-products-' . esc_attr( $item['products_quantities'] );
	}

	if( $item['field_type'] == 'upload' && ! empty( $item['field_preview_image'] ) ) {
		$classes[] = 'pewc-image-preview';
	}

	if( $item['field_type'] == 'calculation' && ! empty( $item['child_qty_product_id'] ) ) {
		$classes[] = 'pewc-child-qty-trigger';
		if( ! empty( $item['quantity_override'] ) ) {
			$classes[] = 'pewc-quantity-overrides';
		}
	}

	if( is_array( $calculation_dependents ) && pewc_get_calculation_trigger_for_child_qty( $item['field_id'], $calculation_dependents ) ) {
		$classes[] = 'pewc-child-qty-target';
		$classes[] = 'pewc-child-qty-target-' . pewc_get_calculation_trigger_for_child_qty( $item['field_id'], $calculation_dependents );
	}

	// Is this field part of a condition somewhere?
	if( isset( $all_group_conditions[$id] ) ) {
		$classes[] = 'pewc-condition-trigger';
	}

	if( isset( $calculation_components[$item['field_id']] ) ) {
		$classes[] = 'pewc-calculation-trigger';
	}

	// 3.13.7
	if ( ! pewc_field_visible_in( 'product', $item['field_visibility'], $item['field_id'], $item['group_id'], $post_id ) ) {
		$classes[] = 'pewc-visibility-hidden';
	}

	// These are used for layered swatches when we have multiple base layers - e.g. for tshirts with front, back views
	$swatch_parent_id = pewc_get_swatch_parent_id( $secondary_images );
	$swatch_child_ids = pewc_get_swatch_child_ids( $secondary_images );

	if( $swatch_parent_id == $item['field_id'] ) {
		$classes[] = 'pewc-swatch-parent';
	}
	if( in_array( $item['field_id'], $swatch_child_ids ) ) {
		$classes[] = 'pewc-swatch-child';
		// Add an index so we know which fields to hide/show
		$classes[] = sprintf(
			'pewc-swatch-child-%s',
			array_search( $item['field_id'], $swatch_child_ids )
		);
	}
	if( ! empty( $item['layered_images'] ) ) {
		$classes[] = 'pewc-layered-image';
	}
	
	$classes = apply_filters( 'pewc_filter_single_product_classes', $classes, $item );

	return $classes;

}

/**
 * If this field quantity is triggered by a calculation
 * @return Integer field ID of the calculation that updates the quantity
 * @since 3.12.4
 */
function pewc_get_calculation_trigger_for_child_qty( $field_id, $calculation_dependents ) {

	$products_field_id = array_search( $field_id, $calculation_dependents );
	return $products_field_id;
	
}

/**
 * Get the field price
 * @since 3.6.0
 */
function pewc_get_field_price( $item, $product, $cart_price = false ) {

	// 3.26.0, this has a formula, return as is
	if ( pewc_formulas_in_prices_enabled() && pewc_price_has_formula( $item['field_price'] ) ) {
		if ( $cart_price ) {
			// this is used on the cart
			if ( isset( $_POST[ $item['id'] . '_field_price_calculated' ] ) ) {
				return (float) $_POST[ $item['id'] . '_field_price_calculated' ];
			}
		}
		// this is used on the single product page
		return $item['field_price'];
	}

	$price = 0;

	if( isset( $item['field_price'] ) && $item['field_type'] != 'products' && $item['field_type'] != 'product-categories' ) {

		$price = floatval( $item['field_price'] );
		// Filter the field price, e.g. for role-based pricing
		$price = apply_filters( 'pewc_get_field_price_before_maybe_include_tax', $price, $item, $product );

	}

	$price = pewc_maybe_include_tax( $product, $price, $cart_price );

	/**
	 * Filtered by pewc_get_multicurrency_price and pewc_aelia_cs_convert
	 */
	return apply_filters( 'pewc_filter_field_price', $price, $item, $product );

}

/**
 * Get the option price
 * @since 3.6.0
 */
function pewc_get_option_price( $option_value, $item, $product, $cart_price=false, $option_index=0 ) {

	// 3.26.0, this has a formula, return as is
	if ( pewc_formulas_in_prices_enabled() && pewc_price_has_formula( $option_value['price'] ) ) {
		if ( $cart_price ) {
			if ( isset( $_POST[ $item['id'] . '_option_' . $option_index . '_price_calculated' ] ) ) {
				return (float) $_POST[ $item['id'] . '_option_' . $option_index . '_price_calculated' ];
			}
		}
		return $option_value['price'];
	}

	$option_price = ! empty( $option_value['price'] ) ? $option_value['price'] : 0;
	$option_price = apply_filters( 'pewc_get_option_price_before_maybe_include_tax', $option_price, $option_value, $product );
	if( apply_filters( 'pewc_check_tax_for_option_price', true, $option_price, $item ) ) {
		$option_price = pewc_maybe_include_tax( $product, $option_price, $cart_price );
	}

	/**
	 * Filtered by pewc_get_multicurrency_price and pewc_aelia_cs_convert
	 */
	return apply_filters( 'pewc_filter_option_price', $option_price, $item, $product );

}

function pewc_get_swatch_option_attributes( $item, $option_value, $key, $option_index ) {

	$attributes = array(
		'data-option-index'	=> $option_index
	);

	return $attributes;

}
add_filter( 'pewc_swatch_option_attributes', 'pewc_get_swatch_option_attributes', 10, 4 );

/**
 * Return the attributes for the option
 * @since 3.11.1
 */
function pewc_get_option_attribute_string( $attributes='' ) {

	$attribute_string = "";
	if ( is_string( $attributes ) ) {
		// 3.26.5
		$attribute_string .= $attributes;
	} else if( is_array( $attributes ) ) {
		foreach( $attributes as $attribute=>$attribute_value ) {
			$attribute_string .= " " . $attribute . "= '" . $attribute_value . "'";
		}
	}

	return $attribute_string;

}

function pewc_add_colpan_cell_tag( $attributes, $item, $group_layout ) {
	$attributes[] = 'colspan=2';
	return $attributes;
}
add_filter( 'pewc_cell_tag_attributes', 'pewc_add_colpan_cell_tag', 10, 3 );

/**
 * Get the field label
 */
function pewc_field_label( $item, $id, $group_layout='ul' ) {

	if( ! empty( $item['field_type'] ) && $item['field_type'] == 'checkbox' ) {
		return '';
	}

	global $product;

	$open_td = '';
	$close_td = '';
	if( $group_layout == 'table' ) {
		$open_td = '<td>';
		$close_td = '</td>';
	}

	$label = $open_td;
	$price_label = '';

	if( isset( $item['field_label'] ) || isset( $item['field_price'] ) ) {

		$label_tag = apply_filters( 'pewc_field_label_tag', 'label', $item );

		$label .= '<' . $label_tag . ' class="pewc-field-label" for="' . esc_attr( $id ) . '">';
		if( isset( $item['field_label'] ) ) {
			$label .= '<span class="pewc-field-label-text">' . wp_kses_post( $item['field_label'] ) . '</span>';
		}
		$label .= '<span class="required"> &#42;</span>';

		// Get the price
		if( ! empty( $item['field_price'] ) && $item['field_type'] != 'name_price' && $item['field_type'] != 'products' && $item['field_type'] != 'product-categories' && ( empty( $item['price_visibility'] ) || $item['price_visibility'] == 'visible' ) ) {

			$field_price = pewc_get_field_price( $item, $product );

			// Check if it's a percentage
			$price = apply_filters( 'pewc_filter_display_price_for_percentages', $field_price, $product, $item );

			// Format the price
			$formatted_price = apply_filters(
				'pewc_field_formatted_price',
				pewc_wc_format_price( $price ),
				$item,
				$product,
				$price
			);

			$price_label .= '<span class="pewc-field-price"> ' . $formatted_price;
			if( ! empty( $item['per_character'] ) && ( $item['field_type'] == 'text' || $item['field_type'] == 'textarea' ) ) {
				$price_label .= ' <span class="pewc-per-character-label">' . __( 'per character', 'pewc' ) . '</span>';
			}
			$price_label .= '</span>';

		}

		$label .= $price_label;
		$label = apply_filters( 'pewc_field_label_end', $label, $product, $item, $group_layout );

		$label .= '</' . $label_tag . '>';

		if( $group_layout == 'table' && pewc_enable_tooltips() != 'yes' ) {
			$label .= pewc_get_field_description( $item, $id, $group_layout );
		}

		$label .= $close_td;

	}

	return apply_filters( 'pewc_filter_field_label', $label, $item, $id );

}

/**
 * Return the markup for the field image, if present
 * @since 1.7.2
 * @return Markup
 */
function pewc_get_field_image( $item, $id ) {
	$image = '';
	if( ! empty( $item['field_image'] ) ) {
		$attachment_id = $item['field_image'];
		$size = apply_filters( 'pewc_filter_field_image_size', 'thumbnail' );
		$image = wp_get_attachment_image( $attachment_id, $size );
	}
	return apply_filters( 'pewc_filter_field_image', $image, $item, $id );
}

/**
 * Return the URL for the field image, if present
 * @since 1.7.2
 * @return Markup
 */
function pewc_get_field_image_url( $item, $size ) {
	$url = '';
	if( ! empty( $item['field_image'] ) ) {
		$attachment_id = $item['field_image'];
		$url = wp_get_attachment_image_src( $attachment_id, $size );
	}
	return $url;
}

/**
 * Show the description for the list view
 */
function pewc_field_description_list_layout( $item, $id, $group_layout='ul' ) {
	if( $group_layout == 'ul' ) {
		pewc_field_description( $item, $id, $group_layout );
	}
}
add_action( 'pewc_after_field_template', 'pewc_field_description_list_layout', 10, 3 );

/**
 * Get the description
 */
function pewc_get_field_description( $item, $id, $group_layout='ul' ) {

	// If enhanced tooltips are enabled, the description should return a post ID
	if( pewc_enhanced_tooltips_enabled() == 'yes' ) {
		return false;
	}

	$additional_info = '';
	if( ! empty( $item['field_minval'] ) && ( $item['field_type'] == 'name_price' || $item['field_type'] == 'number' ) ) {
		if( $item['field_type'] == 'name_price' ) {
			$min = wc_price( $item['field_minval'] );
		} else {
			$min = esc_html( $item['field_minval'] );
		}
		$additional_info .= sprintf( '<small>%s: %s</small>',
			__( 'Min', 'pewc' ),
			$min
		);
	}
	if( ! empty( $item['field_maxval'] ) && ( $item['field_type'] == 'name_price' || $item['field_type'] == 'number' ) ) {
		if( $item['field_type'] == 'name_price' ) {
			$max = wc_price( $item['field_maxval'] );
		} else {
			$max = esc_html( $item['field_maxval'] );
		}
		$additional_info .= sprintf( '<small>%s: %s</small>',
			__( 'Max', 'pewc' ),
			$max
		);
	}
	if( ! empty( $item['field_minchars'] ) && ( $item['field_type'] == 'text' || $item['field_type'] == 'textarea' || $item['field_type'] == 'advanced-preview' ) ) {
		$additional_info .= sprintf( '<small>%s: %s %s</small>',
			__( 'Min', 'pewc' ),
			esc_html( $item['field_minchars'] ),
			__( 'characters', 'pewc' )
		);
	}
	if( ! empty( $item['field_maxchars'] ) && ( $item['field_type'] == 'text' || $item['field_type'] == 'textarea' || $item['field_type'] == 'advanced-preview' ) ) {
		$additional_info .= sprintf( '<small>%s: %s %s</small>',
			__( 'Max', 'pewc' ),
			esc_html( $item['field_maxchars'] ),
			__( 'characters', 'pewc' )
		);
	}
	if( $item['field_type'] == 'upload' ) {
		$max = pewc_get_max_upload();
		$file_types = pewc_get_pretty_permitted_mimes();
		$additional_info .= sprintf( '<small>%s: %s MB</small>',
			apply_filters( 'pewc_filter_max_file_size_message', __( 'Max file size', 'pewc' ) ),
			$max
		);
		$additional_info .= sprintf( '<small>%s: %s</small>',
			apply_filters( 'pewc_filter_permitted_file_types_message', __( 'Permitted file types', 'pewc' ) ),
			$file_types
		);
	}

	$field_description = '';
	if( pewc_enable_tooltips() != 'yes' && ! apply_filters( 'pewc_description_as_placeholder', false, $item ) ) {
		$field_description = ! empty( $item['field_description'] ) ? $item['field_description'] : '';
	}

	if( ! empty( $item['field_description'] ) || $additional_info ) {
		return apply_filters(
			'pewc_filter_field_description',
			sprintf(
				'<p class="pewc-description">%s%s</p>',
				// wp_kses_post( $field_description ),
				wp_kses_post( $field_description ),
				$additional_info
			),
			$item,
			$additional_info
		);
	}
}

function pewc_field_description( $item, $id, $group_layout='ul' ) {

	echo pewc_get_field_description( $item, $id, $group_layout='ul' );

}

/**
 * Filter the price label
 */
function pewc_get_price_html( $price, $product ) {

	// Only for products that have Product Add-Ons
	if( pewc_has_product_extra_groups( $product->get_id() ) != 'yes' ) {
		return $price;
	}
	// Override with any product specific settings
	$pewc_price_label = $product->get_meta( 'pewc_price_label' );
	$pewc_price_display = $product->get_meta( 'pewc_price_display' );
	if( $pewc_price_label && $pewc_price_display == 'before' ) {
		$price = sprintf(
			'<span class="pewc-label-before">%s</span> %s',
			$pewc_price_label,
			$price
		);
		// $price = $pewc_price_label . ' ' . $price;
	} else if( $pewc_price_label && $pewc_price_display == 'after' ) {
		$price = sprintf(
			'%s <span class="pewc-label-after">%s</span>',
			$price,
			$pewc_price_label
		);
	} else if( $pewc_price_display == 'hide' ) {
		$price = sprintf(
			'<span class="pewc-label-hidden">%s</span>',
			$pewc_price_label
		);
	} else {
		// If no product label set, check the global
		$pewc_price_label = get_option( 'pewc_price_label' );
		$pewc_price_display = get_option( 'pewc_price_display' );
		if( $pewc_price_label && $pewc_price_display == 'before' ) {
			$price = sprintf(
				'<span class="pewc-label-before">%s</span> %s',
				$pewc_price_label,
				$price
			);
		} else if( $pewc_price_label && $pewc_price_display == 'after' ) {
			$price = sprintf(
				'%s <span class="pewc-label-after">%s</span>',
				$price,
				$pewc_price_label
			);
		} else if( $pewc_price_display == 'hide' ) {
			$price = sprintf(
				'<span class="pewc-label-hidden">%s</span>',
				$pewc_price_label
			);
		}
	}

	return $price;

}
add_filter( 'woocommerce_get_price_html', 'pewc_get_price_html', 10, 2 );

/**
 * Filter the price label
 */
function pewc_minimum_price_html( $price, $product ) {

	$minimum_price = get_post_meta( $product->get_id(), 'pewc_minimum_price', true );

	if( $minimum_price ) {

		$minimum_price = pewc_maybe_include_tax( $product, $minimum_price );
		$min_price_html = sprintf(
			'<p class="pewc-minimum-price">%s %s</p>',
			__( 'Minimum price:', 'pewc' ),
			wc_price( $minimum_price )
		);

		return $price . $min_price_html;

	} else {

		return $price;

	}

}
add_filter( 'woocommerce_get_price_html', 'pewc_minimum_price_html', 100, 2 );

/**
 * Whether image replacement is enabled
 * @param $product_id @since 3.11.0
 * @since 3.5.0
 */
function pewc_get_add_on_image_action( $product_id=false ) {
	// replace_hide					Hides the add-on image but replaces main image
	// replace						Displays add-on image and replaces main image
	// replace_image_swatch_only	3.13.7, Apply the image replace on image swatch fields only
	// false						Just displays the add-on image
	$replace = get_option( 'pewc_replace_image', 'no' ) == 'yes' ? true : false;
	return apply_filters( 'pewc_get_add_on_image_action', $replace, $product_id );
}

/**
 * Get the default value for our field
 * Used on the front end
 * @since	3.5.0
 * @version	3.25.2
 */
function pewc_get_default_value( $id, $item, $posted, $location='' ) {

	// Set default value if it exists
	$value = ( ! empty( $item['field_default_hidden'] ) || ( isset( $item['field_default_hidden'] ) && $item['field_default_hidden'] === '0' ) ) ? $item['field_default_hidden'] : '';

	// 3.12.2, add default value for color picker
	if ( $item['field_type'] == 'color-picker' && ! empty( $item['field_color'] ) && empty( $value ) ) {
		$value = $item['field_color'];
	}

	// Ensure fields are repopulated after failed validation
	if ( 'front' === $location ) {
		// 3.25.2, only do this if used on the frontend, not the data-default-value attribute, so that Clear All Options still works
		$value = ! empty( $posted[$id] ) ? $posted[$id] : $value;
	}

	return apply_filters( 'pewc_default_field_value', $value, $id, $item, $posted );

}

/**
 * Get the separator between the add-on label and the add-on price
 * The default separator used be a minus sign
 * Now it's a plus sign
 * @since 3.7.7
 */
function pewc_add_on_price_separator( $sep=false, $item=false ) {

	$separator = get_option( 'pewc_price_separator', false );
	$sep = sprintf(
		'<span class="pewc-separator"> %s </span>',
		$separator
	);
	// $sep = ' ' . $separator . ' ';
	return $sep;

}
add_filter( 'pewc_option_price_separator', 'pewc_add_on_price_separator', 1, 2 );

/**
 * Do we update the product price label?
 * @since 3.7.14
 */
function pewc_get_update_price_label() {

	$update = get_option( 'pewc_update_price_label', 'no' );
	return apply_filters( 'pewc_update_price_label', $update );

}

/**
 * Add an extra class to the main price display so we can adjust it
 * Note: doesn't work on WooCommerce Blocks as of WC 9.7.1
 */
function pewc_filter_price_class( $class ) {
	return $class . ' pewc-main-price';
}
add_filter( 'woocommerce_product_price_class', 'pewc_filter_price_class' );

/**
 * Remove SKUs from child variant names
 * @since 3.7.15
 */
function pewc_exclude_skus_child_variants( $name, $variant ) {
	$exclude = get_option( 'pewc_exclude_skus', 'no' );
	if( $exclude == 'yes' ) {
		$name = $variant->get_name();
	}
	return $name;
}
add_filter( 'pewc_variation_name_variable_child_select', 'pewc_exclude_skus_child_variants', 10, 2 );

/**
 * Add the tax suffix after all add-on prices
 * @since 3.7.15
 */
function pewc_add_tax_suffix_options( $name, $item, $product, $price=false ) {
	$enable = pewc_show_price_suffix();
	if( $enable == 'yes' ) {
		$suffix = $product->get_price_suffix( $price, 1 );
		if( $suffix ) {
			$name .= ' ' . $suffix;
		}
	}
	return $name;
}
add_filter( 'pewc_option_name', 'pewc_add_tax_suffix_options', 10, 4 );
add_filter( 'pewc_field_formatted_price', 'pewc_add_tax_suffix_options', 10, 4 );

/**
 * Check whether to display option prices on the product page
 * @since 3.9.0
 */
function pewc_display_option_prices_product_page( $item ) {

	$display = true;
	if( ! empty( $item['option_price_visibility'] ) && $item['option_price_visibility'] != 'visible' ) {
		$display = false;
	}

	return apply_filters( 'pewc_show_option_prices', $display, $item );

}

/**
 * Check whether to display add-on field prices on the product page
 * @since 3.9.0
 */
function pewc_display_field_prices_product_page( $item ) {

	$display = true;
	if( isset( $item['price_visibility'] ) && $item['price_visibility'] != 'visible' ) {
		$display = false;
	}

	return apply_filters( 'pewc_show_field_prices', $display, $item );

}

/**
 * Adds the pewc_key to the form action URL so that if validation fails, the product page can detect that we're still editing a product
 * @since 3.11.0
 */
function pewc_add_to_cart_form_action( $url ) {

	if( pewc_user_can_edit_products() && ! empty( $_GET['pewc_key'] ) ) {

		$url = add_query_arg(
			'pewc_key',
			$_GET['pewc_key'],
			$url
		);

	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_form_action', 'pewc_add_to_cart_form_action', 10, 1 );

/**
 * Get attributes for child product field wrapper
 * @since 3.12.4
 */
function pewc_get_child_product_attributes( $child_product_id, $child_product, $item ) {

	$attributes = array();

	// Get product dimensions
	$attributes[] = sprintf(
		'data-product-weight="%s"',
		$child_product->get_weight()
	);
	$attributes[] = sprintf(
		'data-product-length="%s"',
		$child_product->get_length()
	);
	$attributes[] = sprintf(
		'data-product-width="%s"',
		$child_product->get_width()
	);
	$attributes[] = sprintf(
		'data-product-height="%s"',
		$child_product->get_height()
	);

	return apply_filters( 'pewc_child_product_attributes', $attributes, $child_product_id, $child_product, $item );

}

/**
 * Get attributes for child product independent quantity field
 * @since 3.13.5
 */
function pewc_get_child_quantity_field_attributes( $attributes, $child_product_id, $item, $quantity_field_value ) {

	$attributes[] = sprintf(
		'data-default-quantity="%s"',
		$quantity_field_value
	);

	return apply_filters( 'pewc_child_quantity_field_attributes', $attributes, $child_product_id, $item, $quantity_field_value );

}

/**
 * Check if we're using layered swatch images
 * @since 3.17.0
 */
function pewc_is_field_layered( $item ) {
	$is_layered = 'no';
	$field_types = pewc_fields_with_replace_main_image();

	if( isset( $item['field_type'] ) && in_array( $item['field_type'], $field_types ) && ! empty( $item['layered_images'] ) ) {
		$is_layered = 'yes';
	}
	return $is_layered;
}

/**
 * Fields that have field-level replace main image setting and layering
 * @since 3.17.2
 */
function pewc_fields_with_replace_main_image() {
	$field_types = array( 'image_swatch' ); // only allow image_swatch for now. Other possible fields: 'checkbox', 'select-box', 
	return $field_types;
}

/**
 * Add replace-main-image class to field if enabled
 * @since 3.17.2
 */
function pewc_field_replace_main_image( $classes, $item ) {
	$field_types = pewc_fields_with_replace_main_image();

	if ( isset( $item['field_type'] ) && in_array( $item['field_type'], $field_types ) && ! empty( $item['replace_main_image'] ) ) {
		$classes[] = 'pewc-replace-main-image';
	}
	return $classes;
}
add_filter( 'pewc_filter_single_product_classes', 'pewc_field_replace_main_image', 10, 2 );

/**
 * Put back the product quantity from the cart when editing product add-ons
 * @since 3.24.2
 */
function pewc_use_original_quantity( $args, $product ) {

	if ( ! pewc_user_can_edit_products() || ! is_product() ) {
		return $args;
	}

	// Check if this product has been reloaded from the cart
	$cart_key = ! empty( $_GET['pewc_key'] ) ? $_GET['pewc_key'] : false;

	if ( false === $cart_key ) {
		return $args;
	}

	$cart = WC()->cart->cart_contents;

	if( isset( $cart[$cart_key]['quantity'] ) ) {
		$args['input_value'] = (int) $cart[$cart_key]['quantity'];
	}
	return $args;

}
add_filter( 'woocommerce_quantity_input_args', 'pewc_use_original_quantity', 11, 2 );

/**
 * @since 3.26.0
 */
function pewc_get_default_child_products( $default_products ) {

	$defaults = array();
	if ( ! empty( $default_products ) ) {
		$def = explode( ',', $default_products );
		foreach ( $def as $def2 ) {
			$def2 = (int) trim( $def2 );
			if ( ! in_array( $def2, $defaults ) ) {
				$defaults[] = (int) $def2;
			}
		}
	}
	return $defaults;

}

/**
 * Hide the quantity field if the setting is enabled on the backend
 * @since 3.26.11
 */
function pewc_hide_woocommerce_quantity_input() {

	global $product;
	if ( is_product() && isset( $product ) && pewc_hide_quantity( $product ) ) {
		?>
		<style>form.cart input.qty{ display:none; }</style>
		<?php
	}

}
add_action( 'pewc_after_product_fields', 'pewc_hide_woocommerce_quantity_input' );
