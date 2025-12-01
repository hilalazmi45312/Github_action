<?php
/**
 * Functions for preset styles
 * @since 3.11.0
 * @package WooCommerce Product Add-Ons Ultimate
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether to show the accent colour control in the Customizer
 * @since 3.11.0
 */
function pewc_use_preset_style() {
	return pewc_get_preset_style() != 'inherit' ? true : false;
}

/**
 * Get the preset style
 * @since 3.11.0
 */
function pewc_get_preset_style() {
	$style = get_option( 'pewc_preset_style', 'simple' );
	return apply_filters( 'pewc_preset_style', $style );
}

/**
 * Add style classes
 * @since 3.11.0
 */
function pewc_add_preset_style_classes( $classes ) {
	$style = pewc_get_preset_style();
	if( $style != 'inherit' ) {
		$classes[] = 'pewc-preset-style';
		$classes[] = 'pewc-style-' . $style;
	}
	if( $style == 'minimal' ) {
		// Minimal styles inherit a lot of styles from Simple
		$classes[] = 'pewc-preset-simple';
	}
	return $classes;
}
add_filter( 'body_class', 'pewc_add_preset_style_classes' );

/**
 * Add styles
 * @since 3.11.0
 */
function pewc_add_theme_styles() {
    $colour = get_option( 'pewc_preset_accent_colour', '#2196F3' );
	$style = pewc_get_preset_style();
    $custom_css = pewc_get_preset_styles( $style, $colour );;
    wp_add_inline_style( 'pewc-style', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'pewc_add_theme_styles', 9999 );

/**
 * Get the custom CSS
 * @since 3.11.0
 */
function pewc_get_preset_styles( $style, $colour ) {
	$rgb = pewc_get_rgba( $colour );
	$rgb_semi = pewc_get_rgba( $colour, 0.5 );
	$custom_css = '
	ul.pewc-product-extra-groups label {
		font-weight: normal !important
	}
	.pewc-preset-style .child-product-wrapper {
		-webkit-justify-content: space-around;
		justify-content: space-around
	}
	.pewc-item-field-wrapper label {
		cursor: pointer
	}
	.pewc-preset-style .pewc-radio-images-wrapper:not(.pewc-components-wrapper),
	.pewc-preset-style .pewc-checkboxes-images-wrapper:not(.pewc-components-wrapper) {
		-webkit-justify-content: space-between;
		justify-content: space-between
	}
	.pewc-preset-style .pewc-radio-list-wrapper .pewc-radio-wrapper,
	.pewc-preset-style .pewc-checkboxes-list-wrapper .pewc-checkbox-wrapper {
		position: relative;
	}
	.pewc-preset-style .pewc-item-products input[type=number].pewc-child-quantity-field.pewc-independent-quantity-field {
		margin-top: 0
	}
	.pewc-preset-style input[type=number].pewc-child-quantity-field {
		margin-left: 0
	}
	.pewc-product-extra-groups .dd-options li {
		margin-bottom: 0
	}
	.pewc-product-extra-groups .dd-options li a,
	.pewc-product-extra-groups .dd-selected {
		padding: 1em
	}
	.pewc-product-extra-groups .dd-pointer {
		right: 1em
	}
	.pewc-product-extra-groups .dd-pointer:after {
		content: "";
	  width: 0.8em;
	  height: 0.5em;
	  background-color: var(--select-arrow);
	  clip-path: polygon(100% 0%, 0 0%, 50% 100%);
	}
	p.pewc-description {
		margin-top: 1em
	}
	';

	// Add custom CSS based on theme (skin/preset style) selected
	$custom_css .= '
	.pewc-style-shadow .pewc-item {
		padding: 2em;
		margin-bottom: 3em;
		box-shadow: 0px 23px 56px #f1f1f1;
		background: transparent;
		border: 2px solid #f7f7f7;
		border-radius: 0.5em;
		transition: 0.3s box-shadow
	}
	.pewc-style-shadow .pewc-groups-standard .pewc-item {
		width: 95%;
	}
	.pewc-preset-style .pewc-checkbox-form-label,
	.pewc-preset-style .pewc-radio-form-label,
	.pewc-preset-style .pewc-item-field-wrapper,
	.pewc-preset-style .pewc-item-checkbox label {
		display: block;
		position: relative;
		margin-bottom: 12px;
		cursor: pointer;
		-webkit-user-select: none;
		-moz-user-select: none;
		-ms-user-select: none;
		user-select: none;
	}
	.pewc-preset-style .pewc-force-quantity .pewc-component-wrapper,
	.pewc-preset-style .pewc-force-quantity .pewc-component-wrapper img {
		cursor: not-allowed
	}
	.pewc-preset-style .has-enhanced-tooltip .pewc-item-field-wrapper {
		cursor: auto;
	}
	.pewc-preset-style .has-enhanced-tooltip span.pewc-tooltip-button {
		color: rgba( ' . $rgb . ', 1 );
	}
	.pewc-preset-style .has-enhanced-tooltip span.pewc-tooltip-button:hover {
		color: inherit;
	}
	
	.pewc-preset-style .pewc-checkbox-form-label label,
	.pewc-preset-style .pewc-radio-form-label label,
	.pewc-preset-style .pewc-option-list .pewc-item-field-wrapper label,
	.pewc-preset-style .pewc-checkboxes-list-desc-wrapper,
	.pewc-preset-style .pewc-radio-list-desc-wrapper,
	.pewc-preset-style .pewc-option-list td label {
		padding-left: 35px;
	}
	.pewc-preset-style label.pewc-field-label {
		padding-left: 0
	}
	.pewc-preset-style .pewc-checkbox-form-label input,
	.pewc-preset-style .pewc-radio-form-label input,
	.pewc-preset-style .pewc-item-field-wrapper input[type="checkbox"],
	.pewc-preset-style .pewc-item-field-wrapper input[type="radio"],
	.pewc-preset-style input[type="checkbox"].pewc-form-field,
	.pewc-preset-style input[type="checkbox"].pewc-checkbox-form-field,
	.pewc-preset-style input[type="radio"].pewc-radio-form-field {
		position: absolute;
		opacity: 0;
		cursor: pointer;
		height: 0;
		width: 0;
	}
	.pewc-preset-style .pewc-checkbox-form-label span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-form-label span.pewc-theme-element,
	.pewc-preset-style .pewc-item-field-wrapper span.pewc-theme-element,
	.pewc-preset-style .pewc-item-checkbox span.pewc-theme-element,
	.pewc-preset-style .pewc-checkbox-wrapper span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-wrapper span.pewc-theme-element {
		content: "";
		position: absolute;
		top: 0;
		left: 0;
		height: 25px;
		width: 25px;
		background: #eee;
		cursor: pointer
	}
	.pewc-style-colour .pewc-checkbox-form-label span.pewc-theme-element,
	.pewc-style-colour .pewc-radio-form-label span.pewc-theme-element,
	.pewc-style-colour .pewc-item-field-wrapper span.pewc-theme-element,
	.pewc-style-colour .pewc-item-checkbox span.pewc-theme-element,
	.pewc-style-colour .pewc-checkbox-wrapper span.pewc-theme-element,
	.pewc-style-colour .pewc-radio-wrapper span.pewc-theme-element {
		background: rgba( ' . $rgb . ', 0.2 );
	}
	.pewc-preset-style .pewc-item-field-wrapper .pewc-checkboxes-list-wrapper span.pewc-theme-element,
	.pewc-preset-style .pewc-item-field-wrapper .pewc-radio-list-wrapper span.pewc-theme-element,
	.pewc-style-colour .pewc-checkbox-wrapper span.pewc-theme-element {
		top: 50%;
		transform: translateY( -50% )
	}
	.pewc-preset-style .pewc-radio-form-label span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-list-label-wrapper span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-wrapper span.pewc-theme-element {
		border-radius: 50%
	}
	.pewc-preset-style .pewc-checkbox-form-label span.pewc-theme-element:hover,
	.pewc-preset-style .pewc-radio-form-label span.pewc-theme-element:hover,
	.pewc-preset-style .pewc-item-field-wrapper span.pewc-theme-element:hover,
	.pewc-preset-style .pewc-item-checkbox span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-checkbox-wrapper span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-radio-wrapper span.pewc-theme-element:hover {
		background: #ddd;
		transition: background 0.2s;
	}
	.pewc-style-colour .pewc-checkbox-form-label span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-radio-form-label span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-item-field-wrapper span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-item-checkbox span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-checkbox-wrapper span.pewc-theme-element:hover,
	.pewc-style-colour .pewc-radio-wrapper span.pewc-theme-element:hover {
		background: rgba( ' . $rgb . ', 0.4 );
	}
	.pewc-preset-style .pewc-checkbox-form-label input:checked ~ span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-form-label input:checked ~ span.pewc-theme-element,
	.pewc-preset-style .pewc-item-field-wrapper input:checked ~ span.pewc-theme-element,
	.pewc-preset-style .pewc-item-checkbox input:checked ~ span.pewc-theme-element,
	.pewc-preset-style .pewc-checkbox-wrapper input:checked ~ span.pewc-theme-element,
	.pewc-preset-style .pewc-radio-wrapper input:checked ~ span.pewc-theme-element {
		background: ' . $colour . ';
	}
	.pewc-preset-style span.pewc-theme-element:after {
		content: "";
		position: absolute;
		display: none;
	}
	.pewc-preset-style .pewc-checkbox-form-label input:checked ~ span.pewc-theme-element:after,
	.pewc-preset-style .pewc-radio-form-label input:checked ~ span.pewc-theme-element:after,
	.pewc-preset-style .pewc-item-field-wrapper input:checked ~ span.pewc-theme-element:after,
	.pewc-preset-style .pewc-item-checkbox input:checked ~ span.pewc-theme-element:after,
	.pewc-preset-style .pewc-checkbox-wrapper input:checked ~ span.pewc-theme-element:after,
	.pewc-preset-style .pewc-radio-wrapper input:checked ~ span.pewc-theme-element:after {
		display: block;
	}
	.pewc-preset-style .pewc-checkbox-form-label span.pewc-theme-element:after,
	.pewc-preset-style .pewc-item-field-wrapper span.pewc-theme-element:after,
	.pewc-preset-style .pewc-item-checkbox span.pewc-theme-element:after,
	.pewc-preset-style .pewc-checkbox-wrapper span.pewc-theme-element:after,
	.pewc-preset-style .pewc-radio-wrapper span.pewc-theme-element:after {
		left: 9px;
		top: 5px;
		width: 5px;
		height: 10px;
		border: solid white;
		border-width: 0 3px 3px 0;
		-webkit-transform: rotate(45deg);
		-ms-transform: rotate(45deg);
		transform: rotate(45deg);
	}
	.pewc-preset-style .pewc-radio-form-label span.pewc-theme-element:after,
	.pewc-preset-style .pewc-radio-list-label-wrapper span.pewc-theme-element:after {
		top: 7px;
	  left: 7px;
	  width: 8px;
	  height: 8px;
	  border-radius: 50%;
	  background: white;
	}
	.pewc-preset-style .pewc-radio-image-wrapper,
	.pewc-preset-style .pewc-checkbox-image-wrapper {
		border: 2px solid #eee;
		padding: 0.5em;
		position: relative
	}
	.pewc-preset-style .pewc-item-products-radio .pewc-theme-element,
	.pewc-preset-style .pewc-item-products-checkboxes .pewc-theme-element,
	.pewc-preset-style .pewc-item-products-components .pewc-theme-element,
	.pewc-preset-style .pewc-item-image_swatch .pewc-theme-element {
		display: none
	}
	.pewc-preset-style.pewc-show-inputs .pewc-item-products-radio .checked .pewc-theme-element,
	.pewc-preset-style.pewc-show-inputs .pewc-item-products-checkboxes .checked .pewc-theme-element,
	.pewc-preset-style.pewc-show-inputs .pewc-item-products-components .checked .pewc-theme-element,
	.pewc-preset-style.pewc-show-inputs .pewc-item-image_swatch .checked .pewc-theme-element {
		display: block;
		top: 2px;
		left: 2px
	}
	.pewc-preset-style.pewc-show-inputs .pewc-radio-image-wrapper,
	.pewc-preset-style.pewc-show-inputs .pewc-checkbox-image-wrapper {
		border-width: 4px
	}
	.pewc-preset-style .pewc-item[not:.pewc-circular-swatches] .pewc-radio-image-wrapper.checked,
	.pewc-preset-style .pewc-item[not:.pewc-circular-swatches] .pewc-radio-image-wrapper:not(.pewc-checkbox-disabled):hover,
	.pewc-preset-style .pewc-item[not:.pewc-circular-swatches] .pewc-checkbox-image-wrapper.checked,
	.pewc-preset-style .child-product-wrapper:not(.pewc-column-wrapper) .pewc-checkbox-image-wrapper:not(.pewc-checkbox-disabled):hover {
		border: 2px solid ' . $colour . '
	}
	.pewc-preset-style .pewc-radio-image-wrapper label input:checked + img,
	.pewc-preset-style .pewc-checkbox-image-wrapper label input:checked + img {
		border: 0
	}
	.pewc-preset-style .pewc-item-image_swatch .pewc-checkboxes-images-wrapper .pewc-checkbox-image-wrapper,
	.pewc-preset-style ul.pewc-product-extra-groups .pewc-item-image_swatch.pewc-item label,
	.pewc-preset-style .pewc-item-products .child-product-wrapper:not(.pewc-column-wrapper) .pewc-checkbox-image-wrapper:not(.pewc-component-wrapper),
	.pewc-preset-style .pewc-item-products .child-product-wrapper .pewc-radio-image-wrapper:not(.pewc-component-wrapper),
	.pewc-preset-style ul.pewc-product-extra-groups .pewc-item-products.pewc-item label {
		display: -webkit-flex !important;
		display: flex !important;
		-webkit-flex-direction: column;
		flex-direction: column;
	}
	.pewc-quantity-layout-grid .pewc-preset-style .pewc-checkbox-desc-wrapper,
	.pewc-quantity-layout-grid .pewc-preset-style .pewc-radio-desc-wrapper {
		margin-top: auto;
	}
	.pewc-preset-style .products-quantities-independent:not(.pewc-column-wrapper) .pewc-checkbox-desc-wrapper,
	.pewc-preset-style .products-quantities-independent:not(.pewc-column-wrapper) .pewc-radio-desc-wrapper {
		display: grid;
    	grid-template-columns: 80px 1fr;
		-webkit-align-items: center;
		align-items: center
	}
	.pewc-preset-style .pewc-text-swatch .pewc-checkbox-form-label:hover,
    .pewc-preset-style .pewc-text-swatch .pewc-radio-form-label:hover {
      border-color: ' . $colour . ';
    }
	.pewc-preset-style .pewc-text-swatch .pewc-checkbox-form-label.active-swatch,
    .pewc-preset-style .pewc-text-swatch .pewc-radio-form-label.active-swatch {
		border-color: ' . $colour . ';
      	background: ' . $colour . ';
	  	color: #fff;
    }
	.pewc-range-slider {
		color: ' . $colour . ';
	}
	.pewc-preset-style .wp-color-result-text {
		background-color: #f1f1f1;
    	/* padding: 0.5em 1em; */
	}
	.pewc-preset-style .pewc-item-field-wrapper .wp-color-result {
		padding-left: 3em !important;
    	font-size: inherit !important;
	}
	.pewc-preset-style .pewc-item input[type=number],
	.pewc-preset-style .pewc-item input[type=text],
	.pewc-preset-style .pewc-item textarea {
		padding: 0.5em 1em;
		background-color: #f7f7f7;
		outline: 0;
		border: 0;
		-webkit-appearance: none;
		box-sizing: border-box;
		font-weight: normal;
		box-shadow: none;
	}
	.pewc-style-simple .pewc-item input[type=number],
	.pewc-style-simple .pewc-item input[type=text],
	.pewc-style-simple .pewc-item textarea {
		background: none;
		border: 1px solid #ccc
	}
	.pewc-style-colour .pewc-item input[type=number],
	.pewc-style-colour .pewc-item input[type=text],
	.pewc-style-colour .pewc-item textarea {
    	background: rgba( ' . $rgb . ', 0.1 );
	}
	.pewc-preset-style input[type=number]:focus,
	.pewc-preset-style input[type=text]:focus,
	.pewc-preset-style textarea:focus {
    	border: 1px solid rgba( ' . $rgb . ', 0.2 );
	}
	.pewc-style-colour .dropzone {
		border-color: ' . $colour . ';
		background: rgba( ' . $rgb . ', 0.1 )
	}
	.pewc-select-wrapper select {
		background-color: transparent;
		border: none;
		padding: 0 1em 0 0;
		margin: 0;
		width: 100%;
		font-family: inherit;
		font-size: inherit;
		cursor: inherit;
		line-height: inherit;
		outline: none
	}
	.pewc-select-wrapper {
		width: 100%;
		border: 2px solid #eee;
		border-radius: 0;
		padding: 0.75em 0.75em;
		cursor: pointer;
		line-height: 1.1;
		background-color: #fff
	}
	.pewc-preset-style .select2-container--default .select2-selection--single {
		border: 2px solid #eee;
		border-radius: 0;
	}
	.pewc-preset-style .select2-container .select2-selection--single {
		height: auto;
		padding: 0.5em;
	}
	.pewc-preset-style .select2-container--default .select2-selection--single .select2-selection__arrow {
    top: 50%;
    transform: translateY(-50%);
	}
	.pewc-preset-style .dd-select {
		border: 2px solid #eee;
		background: white !important
	}
	.pewc-style-rounded .pewc-item-field-wrapper span.pewc-theme-element {
		border-radius: 0.5em
	}
	.pewc-preset-style.pewc-style-rounded .pewc-radio-form-label span.pewc-theme-element,
	.pewc-preset-style.pewc-style-rounded .pewc-radio-list-label-wrapper span.pewc-theme-element {
		border-radius: 50%
	}
	.pewc-style-rounded input[type=number],
	.pewc-style-rounded input[type=text],
	.pewc-style-rounded textarea,
	.pewc-style-rounded .pewc-radio-image-wrapper,
	.pewc-style-rounded .pewc-checkbox-image-wrapper,
	.pewc-style-rounded .pewc-select-wrapper,
	.pewc-style-rounded .dd-select,
	.pewc-style-rounded .dd-options,
	.pewc-style-rounded .dropzone {
		border-radius: 1em
	}
	.pewc-preset-style .pewc-groups-tabs .pewc-group-wrap {
		background: none;
		padding: 2em 2em 1em;
		margin-bottom: 1em;
		border: 1px solid #eee
	}
	.pewc-style-colour .pewc-groups-tabs .pewc-group-wrap {
		border: 1px solid rgba( ' . $rgb . ', 0.1 );
	}
	.pewc-style-rounded .pewc-groups-tabs .pewc-group-wrap {
		border-radius: 1em;
		border-top-left-radius: 0
	}
	.pewc-preset-style .pewc-tabs-wrapper .pewc-tab {
		background: #f1f1f1;
    border: 1px solid #f1f1f1;
		border-bottom: 1px solid #fff;
    margin-bottom: -1px;
		transition: 0.3s background
	}
	.pewc-style-rounded .pewc-tabs-wrapper .pewc-tab {
		border-top-right-radius: 0.5em;
		border-top-left-radius: 0.5em;
	}
	.pewc-preset-style .pewc-tabs-wrapper .pewc-tab:hover {
		background: #ddd;
	}
	.pewc-style-colour .pewc-tabs-wrapper .pewc-tab {
		background: rgba( ' . $rgb . ', 0.1 );
		border: 1px solid rgba( ' . $rgb . ', 0.1 );
		border-bottom: 0;
	}
	.pewc-style-colour .pewc-tabs-wrapper .pewc-tab:hover {
		background: rgba( ' . $rgb . ', 0.2 );
	}
	.pewc-preset-style .pewc-tabs-wrapper .pewc-tab.active-tab,
	.pewc-style-colour .pewc-tabs-wrapper .pewc-tab.active-tab {
		background: #fff;
		border-bottom-color: #fff
	}
	.pewc-preset-style .pewc-groups-accordion .pewc-group-wrap.group-active .pewc-group-content-wrapper {
		padding: 2em 0;
		background: none
	}
	.pewc-preset-style .pewc-groups-accordion .pewc-group-wrap h3 {
		background: #eee;
	}
	.pewc-style-colour .pewc-groups-accordion .pewc-group-wrap h3 {
		background: rgba( ' . $rgb . ', 0.1 );
	}
	.pewc-style-colour .pewc-steps-wrapper .pewc-tab,
	.pewc-style-colour .pewc-groups-accordion .pewc-group-wrap h3 {
		background: rgba( ' . $rgb . ', 0.1 );
	}
	.pewc-style-colour .pewc-steps-wrapper .pewc-tab:after,
	.pewc-style-colour .pewc-groups-accordion .pewc-group-wrap h3 {
		border-left-color: rgba( ' . $rgb . ', 0.1 );
	}
	.pewc-style-colour .pewc-steps-wrapper .pewc-tab.active-tab,
	.pewc-style-colour .pewc-groups-accordion .pewc-group-wrap.group-active h3 {
		background: rgba( ' . $rgb . ', 0.2 );
	}
	.pewc-style-colour .pewc-steps-wrapper .pewc-tab.active-tab:after,
	.pewc-style-colour .pewc-groups-accordion .pewc-group-wrap.group-active h3 {
		border-left-color: rgba( ' . $rgb . ', 0.2 );
	}';
  return $custom_css;
}

/**
 * Filter the label HTML
 * @since 3.11.0
 */
function pewc_theme_field_label_tag( $tag, $item ) {
	$style = pewc_get_preset_style();
	if( $style != 'inherit' ) {
		$tag = 'h4';
	}
	return $tag;
}
add_filter( 'pewc_field_label_tag', 'pewc_theme_field_label_tag', 10, 2 );

/**
 * Open wrapper for select fields
 * @since 3.11.0
 */
function pewc_open_select_field_wrapper( $item, $id, $group_layout ) {
	$style = pewc_get_preset_style();
	if( $style != 'inherit' && $item['field_type'] == 'select' ) {
		echo '<div class="pewc-select-wrapper">';
	}
}
add_action( 'pewc_before_select_field', 'pewc_open_select_field_wrapper', 10, 3 );

/**
 * Close wrapper for select fields
 * @since 3.11.0
 */
function pewc_close_select_field_wrapper( $item, $id, $group_layout ) {
	$style = pewc_get_preset_style();
	if( $style != 'inherit' && $item['field_type'] == 'select' ) {
		echo '</div>';
	}
}
add_action( 'pewc_after_select_field', 'pewc_close_select_field_wrapper', 10, 3 );

/**
 * Convert hex to RGBA
 * @since 3.11.0
 */
function pewc_get_rgba( $hex, $alpha = false ) {
   $hex = str_replace('#', '', $hex);
   $length = strlen($hex);
   $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
   $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
   $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
   if( $alpha ) {
     $rgb['a'] = $alpha;
   }
   return join( ',', $rgb );
}
