<?php
/**
 * Main plugin functions.
 *
 * @package woocommerce-checkout-field-editor
 */

/**
 * Initialize the Checkout Field Editor.
 */
function woocommerce_init_checkout_field_editor() {
	global $supress_field_modification;

	$supress_field_modification = false;

	if ( ! class_exists( 'WC_Checkout_Field_Editor' ) ) {
		require_once WC_CHECKOUT_FIELD_EDITOR_PATH . '/includes/class-wc-checkout-field-editor.php';
	}

	if ( ! class_exists( 'WC_Checkout_Field_Editor_PIP_Integration' ) ) {
		require_once WC_CHECKOUT_FIELD_EDITOR_PATH . '/includes/class-wc-checkout-field-editor-pip-integration.php';
	}

	if ( ! class_exists( 'WC_Checkout_Field_Editor_Privacy' ) ) {
		require_once WC_CHECKOUT_FIELD_EDITOR_PATH . '/includes/class-wc-checkout-field-editor-privacy.php';
	}

	require_once WC_CHECKOUT_FIELD_EDITOR_PATH . '/includes/class-wc-checkout-field-editor-order-details.php';
	$order_details = new WC_Checkout_Field_Editor_Order_Details();
	$order_details->register_hooks();

	/**
	 * Localisation
	 */
	load_plugin_textdomain( 'woocommerce-checkout-field-editor', false, dirname( plugin_basename( __DIR__ ) ) . '/languages/' );

	new WC_Checkout_Field_Editor_PIP_Integration();

	$GLOBALS['wc_checkout_field_editor'] = new WC_Checkout_Field_Editor();
}

/**
 * Updates the plugin version to DB.
 *
 * @since 1.5.6
 * @version 1.5.6
 */
function wc_checkout_fields_update_plugin_version() {
	update_option( 'wc_checkout_field_editor_version', WC_CHECKOUT_FIELD_EDITOR_VERSION );
}

/**
 * Performs installation processes such as migrations or data update.
 *
 * @since 1.5.6
 * @version 1.5.6
 */
function wc_checkout_fields_install() {
	$version = get_option( 'wc_checkout_field_editor_version', WC_CHECKOUT_FIELD_EDITOR_VERSION );

	if ( version_compare( WC_VERSION, '3.0.0', '>=' ) && version_compare( $version, '1.5.6', '<' ) ) {
		wc_checkout_fields_wc30_migrate();
	}
}

/**
 * Migrates pre WC3.0 data. Pre WC30 checkout field ordering is using
 * "order" as the key. After WC30, its using "priority" as the key.
 * This migration will rename the key name and re-set the priority values
 * to align with WC core.
 *
 * @since 1.5.6
 * @version 1.5.6
 */
function wc_checkout_fields_wc30_migrate() {
	$shipping_fields   = get_option( 'wc_fields_shipping', array() );
	$billing_fields    = get_option( 'wc_fields_billing', array() );
	$additional_fields = get_option( 'wc_fields_additional', array() );

	if ( ! empty( $shipping_fields ) ) {
		$migrated_shipping_fields = array();

		foreach ( $shipping_fields as $field => $value_arr ) {
			$migrated_shipping_value_arrs = array();

			foreach ( $value_arr as $k => $v ) {
				if ( 'order' === $k ) {
					$migrated_shipping_value_arrs['priority'] = intval( $v ) * 10;
				} else {
					$migrated_shipping_value_arrs[ $k ] = $v;
				}
			}

			$migrated_shipping_fields[ $field ] = $migrated_shipping_value_arrs;
		}

		update_option( 'wc_fields_shipping', $migrated_shipping_fields );
	}

	if ( ! empty( $billing_fields ) ) {
		$migrated_billing_fields = array();

		foreach ( $billing_fields as $field => $value_arr ) {
			$migrated_billing_value_arrs = array();

			foreach ( $value_arr as $k => $v ) {
				if ( 'order' === $k ) {
					$migrated_billing_value_arrs['priority'] = intval( $v ) * 10;
				} else {
					$migrated_billing_value_arrs[ $k ] = $v;
				}
			}

			$migrated_billing_fields[ $field ] = $migrated_billing_value_arrs;
		}

		update_option( 'wc_fields_billing', $migrated_billing_fields );
	}

	if ( ! empty( $additional_fields ) ) {
		$migrated_additional_fields = array();

		foreach ( $additional_fields as $field => $value_arr ) {
			$migrated_additional_value_arrs = array();

			foreach ( $value_arr as $k => $v ) {
				if ( 'order' === $k ) {
					$migrated_additional_value_arrs['priority'] = intval( $v ) * 10;
				} else {
					$migrated_additional_value_arrs[ $k ] = $v;
				}
			}

			$migrated_additional_fields[ $field ] = $migrated_additional_value_arrs;
		}

		update_option( 'wc_fields_additional', $migrated_additional_fields );
	}

	wc_checkout_fields_update_plugin_version();
}


/**
 * Load function for the export handler.
 */
function woocommmerce_init_cfe_export_handler() {

	if ( ! class_exists( 'WC_Checkout_Field_Editor_Export_Handler' ) ) {
		require_once WC_CHECKOUT_FIELD_EDITOR_PATH . '/includes/class-wc-checkout-field-editor-export-handler.php';
		new WC_Checkout_Field_Editor_Export_Handler();
	}
}

/**
 * Modify billing fields function.
 *
 * @param mixed $old Original list of billing fields.
 */
function wc_checkout_fields_modify_billing_fields( $old ) {
	global $supress_field_modification;

	if ( $supress_field_modification ) {
		return $old;
	}

	return wc_checkout_fields_modify_fields( get_option( 'wc_fields_billing' ), $old );
}

/**
 * Mpdify shipping fields function.
 *
 * @param mixed $old Original list of shipping fields.
 */
function wc_checkout_fields_modify_shipping_fields( $old ) {
	global $supress_field_modification;

	if ( $supress_field_modification ) {
		return $old;
	}

	return wc_checkout_fields_modify_fields( get_option( 'wc_fields_shipping' ), $old );
}

/**
 * Modify order fields function.
 *
 * @param mixed $fields List of fields to modify.
 */
function wc_checkout_fields_modify_order_fields( $fields ) {
	global $supress_field_modification;

	if ( $supress_field_modification ) {
		return $fields;
	}

	$additional_fields = get_option( 'wc_fields_additional' );
	if ( $additional_fields ) {
		foreach ( $additional_fields as $name => $field_values ) {
			$new_field = $field_values;

			// Force number type for fields that have number validation.
			if ( isset( $field_values['validate'] ) && in_array( 'number', $field_values['validate'], true ) ) {
				$new_field['type'] = 'number';
			}

			// Add the new field to the order fields.
			$fields['order'][ $name ] = $new_field;
		}

		// Check if order_comments is enabled/disabled.
		if ( isset( $additional_fields ) && isset( $additional_fields['order_comments'] ) && ! $additional_fields['order_comments']['enabled'] ) {
			unset( $fields['order']['order_comments'] );

			// Remove the additional information header if there are no other additional fields.
			if ( 1 === count( $additional_fields ) ) {
				do_action( 'wc_checkout_fields_disable_order_comments' );
			}
		}
	}

	return $fields;
}

/**
 * Adding our own action here so that 3rd party plugins can remove this
 * because they may need to keep the additional information header even
 * when order comments are disabled
 */
function wc_checkout_fields_maybe_hide_additional_info_header() {
	add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
}

/**
 * Modify the array of billing and shipping fields.
 *
 * @param mixed $fields       New checkout fields from this plugin.
 * @param mixed $old_fields Existing checkout fields from WC.
 */
function wc_checkout_fields_modify_fields( $fields, $old_fields ) {
	if ( empty( $fields ) ) {
		// If we have made no modifications, return the original.
		return $old_fields;
	}

	$locale_field_names = array(
		'billing_address_1',
		'billing_state',
		'billing_city',
		'billing_country',
		'billing_postcode',
		'shipping_address_1',
		'shipping_country',
		'shipping_state',
		'shipping_city',
		'shipping_country',
		'shipping_postcode',
		'order_comments',
	);

	$new_fields = $fields;

	foreach ( $fields as $name => $field_values ) {
		$is_locale_field = in_array( $name, $locale_field_names, true );

		if ( false === $field_values['enabled'] ) {
			unset( $new_fields[ $name ] );
			continue;
		}

		// if the field is custom and not a locale field, modify some properies based on field options before returning as a new field.
		if ( ! $is_locale_field ) {
			/*
			 * Modify the options array for the select type field in case when there is a placeholder set.
			 * The WooCommerce implementation of select field at the checkout requires an empty key with value ( placeholder text ) to display the placeholder.
			 */
			if ( isset( $field_values['type'] ) && 'select' === $field_values['type'] && ! empty( $field_values['placeholder'] ) ) {
				$new_fields[ $name ]['options'] = array( '' => $field_values['placeholder'] ) + $field_values['options'];
			}
			// Force number type for fields that have number validation.
			if ( isset( $field_values['validate'] ) && in_array( 'number', $field_values['validate'], true ) ) {
				$new_fields[ $name ]['type'] = 'number';
			}
			continue;
		}

		// Selectively replace locale field properties.
		$new_fields[ $name ] = $old_fields[ $name ];
		$new_fields[ $name ]['label'] = ! empty( $field_values['label'] ) ? $field_values['label'] : $old_fields[ $name ]['label'];

		if ( ! empty( $field_values['placeholder'] ) ) {
			$new_fields[ $name ]['placeholder'] = $field_values['placeholder'];

		} elseif ( ! empty( $old_fields[ $name ]['placeholder'] ) ) {
			$new_fields[ $name ]['placeholder'] = $old_fields[ $name ]['placeholder'];

		} else {
			$new_fields[ $name ]['placeholder'] = '';
		}

		$new_fields[ $name ]['class'] = $field_values['class'];
		$new_fields[ $name ]['priority'] = $field_values['priority'];
	}

	return $new_fields;
}

/**
 * Enqueue scripts for checkout fields.
 */
function wc_checkout_fields_scripts() {
	global $wp_scripts;

	if ( is_checkout() || is_wc_endpoint_url( 'edit-address' ) ) {
		wp_enqueue_script( 'wc-checkout-editor-frontend', plugins_url( '/dist/js/frontend.js', __DIR__ ), array( 'jquery', 'jquery-ui-datepicker' ), WC()->version, true );

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

		$pattern = array(
			// Day.
			'd', // Day of the month.
			'j', // 3 letter name of the day.
			'l', // Full name of the day.
			'z', // Day of the year.
			'S',

			// Month.
			'F', // Month name full.
			'M', // Month name short.
			'n', // Numeric month no leading zeros.
			'm', // Numeric month leading zeros.

			// Year.
			'Y', // Full numeric year.
			'y', // Numeric year: 2 digit.
		);
		$replace = array(
			'dd',
			'd',
			'DD',
			'o',
			'',
			'MM',
			'M',
			'm',
			'mm',
			'yy',
			'y',
		);

		foreach ( $pattern as &$p ) {
			$p = '/' . $p . '/';
		}

		wp_localize_script(
			'wc-checkout-editor-frontend',
			'wc_checkout_fields',
			array(
				'date_format' => preg_replace( $pattern, $replace, wc_date_format() ),
			)
		);
	}
}

/**
 * Markup for date picker field.
 *
 * @param string $field Field markup (default: '').
 * @param mixed  $key   Field key.
 * @param mixed  $args  List of arguments to manipulate how it's displayed.
 * @param mixed  $value Field value.
 */
function wc_checkout_fields_date_picker_field( $field = '', $key = '', $args = array(), $value = '' ) {
	if ( $args['required'] ) {
		$args['class'][] = 'validate-required';
		$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce-checkout-field-editor' ) . '">*</abbr>';
	} else {
		$required = '';
	}

	$args['maxlength'] = ( $args['maxlength'] ) ? 'maxlength="' . absint( esc_attr( $args['maxlength'] ) ) . '"' : '';

	if ( ! empty( $args['validate'] ) ) {
		foreach ( $args['validate'] as $validate ) {
			$args['class'][] = 'validate-' . $validate;
		}
	}

	$field = '<p data-priority="' . esc_attr( $args['priority'] ) . '" class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $key ) . '_field">';

	if ( $args['label'] ) {
		$field .= '<label for="' . esc_attr( $key ) . '" class="' . implode( ' ', $args['label_class'] ) . '">' . $args['label'] . $required . '</label>';
	}

	$field .= '<input readonly type="text" class="checkout-date-picker input-text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" ' . $args['maxlength'] . ' value="' . esc_attr( $value ) . '" />
		</p>';

	return $field;
}

/**
 * Markup for radio field.
 *
 * @param string $field Field markup (default: '').
 * @param mixed  $key   Field key.
 * @param mixed  $args  List of arguments to manipulate how it's displayed.
 * @param mixed  $value Field value.
 */
function wc_checkout_fields_radio_field( $field = '', $key = '', $args = array(), $value = '' ) {
	if ( $args['required'] ) {
		$args['class'][] = 'validate-required';
		$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce-checkout-field-editor' ) . '">*</abbr>';
	} else {
		$required = '';
	}

	$args['maxlength'] = ( $args['maxlength'] ) ? 'maxlength="' . absint( $args['maxlength'] ) . '"' : '';

	$field = '<div data-priority="' . esc_attr( $args['priority'] ) . '" class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $key ) . '_field">';

	$field .= '<fieldset><legend>' . $args['label'] . $required . '</legend>';

	if ( ! empty( $args['options'] ) ) {
		foreach ( $args['options'] as $option_key => $option_text ) {
			$field .= '<label><input type="radio" ' . checked( $value, esc_attr( $option_text ), false ) . ' name="' . esc_attr( $key ) . '" value="' . esc_attr( $option_text ) . '" /> ' . esc_html( $option_text ) . '</label>';
		}
	}

	$field .= '</fieldset></div>';

	return $field;
}

/**
 * Markup for multiselect field.
 *
 * @param string $field Field markup (default: '').
 * @param mixed  $key   Field key.
 * @param mixed  $args  List of arguments to manipulate how it's displayed.
 * @param mixed  $value Field value.
 */
function wc_checkout_fields_multiselect_field( $field = '', $key = '', $args = array(), $value = '' ) {
	if ( $args['required'] ) {
		$args['class'][] = 'validate-required';
		$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce-checkout-field-editor' ) . '">*</abbr>';
	} else {
		$required = '';
	}

	$args['maxlength'] = ( $args['maxlength'] ) ? 'maxlength="' . absint( $args['maxlength'] ) . '"' : '';
	$selected_options = is_array( $value ) ? $value : array( $value );

	if ( ! empty( $args['options'] ) ) {
		$options = '';

		foreach ( $args['options'] as $option_key => $option_text ) {
			$options .= '<option ' . selected( in_array( $option_key, $selected_options, true ), true, false ) . '>' . esc_attr( $option_text ) . '</option>';
		}

		$field = '<p data-priority="' . esc_attr( $args['priority'] ) . '" class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $key ) . '_field">';

		if ( $args['label'] ) {
			$field .= '<label for="' . esc_attr( $key ) . '" class="' . implode( ' ', $args['label_class'] ) . '">' . $args['label'] . $required . '</label>';
		}

		$placeholder = ! empty( $args['placeholder'] ) ? $args['placeholder'] : __( 'Select some options', 'woocommerce-checkout-field-editor' );

		$field .= '<select data-placeholder="' . esc_attr( $placeholder ) . '" multiple="multiple" name="' . esc_attr( $key ) . '[]" id="' . esc_attr( $key ) . '" class="checkout_chosen_select select wc-enhanced-select">
				' . $options . '
			</select>
		</p>';
	}

	return $field;
}

/**
 * Markup for heading field.
 *
 * @param string $field Field markup (default: '').
 * @param mixed  $key   Field key.
 * @param mixed  $args  List of arguments to manipulate how it's displayed.
 * @param mixed  $value Field value.
 */
function wc_checkout_fields_heading_field( $field = '', $key = '', $args = array(), $value = '' ) {
	$field = '<h3 data-priority="' . esc_attr( $args['priority'] ) . '" class="form-row ' . esc_attr( implode( ' ', $args['class'] ) ) . '" id="' . esc_attr( $key ) . '_field">' . $args['label'] . '</h3>';

	return $field;
}

/**
 * Checkout fields validation function.
 *
 * @param mixed $posted Fields to validate.
 */
function wc_checkout_fields_validation( $posted ) {
	foreach ( WC()->checkout->checkout_fields as $fieldset_key => $fieldset ) {

		// Skip shipping if its not needed.
		if ( 'shipping' === $fieldset_key && ( wc_ship_to_billing_address_only() || ! empty( $posted['shiptobilling'] ) || ( ! WC()->cart->needs_shipping() && 'no' === get_option( 'woocommerce_require_shipping_address' ) ) ) ) {
			continue;
		}

		foreach ( $fieldset as $key => $field ) {

			if ( ! empty( $field['validate'] ) && is_array( $field['validate'] ) ) {

				// ZIP doesn't have field's type. Pass it to avoid notice.
				if ( ! isset( $field['type'] ) ) {
					continue;
				}

				/**
				 * For non-checkbox fields, `required` validation already
				 * handled properly by WC core. However WC core sets unchecked
				 * checkbox's value to `0` which then bypass the validation
				 * of checking emptiness.
				 *
				 * @see https://github.com/woocommerce/woocommerce/blob/461ec4da1626b28e2a106a4e4530cb22a19e7d36/includes/class-wc-checkout.php#L449-L450
				 */
				if ( 'checkbox' !== $field['type'] && empty( $posted[ $key ] ) ) {
					continue;
				}

				foreach ( $field['validate'] as $rule ) {
					switch ( $rule ) {
						case 'required':
							if ( 'checkbox' === $field['type'] && 0 === $posted[ $key ] ) {
								wc_add_notice( '<strong>' . esc_html( $field['label'] ) . '</strong> ' . __( 'is a required field.', 'woocommerce-checkout-field-editor' ), 'error' );
							}
							break;
						case 'number':
							if ( '' !== $posted[ $key ] && ! is_numeric( $posted[ $key ] ) ) {
								/* translators: %s: Invalid number */
								wc_add_notice( '<strong>' . esc_html( $field['label'] ) . '</strong> ' . sprintf( __( '(%s) is not a valid number.', 'woocommerce-checkout-field-editor' ), $posted[ $key ] ), 'error' );
							}
							break;
					}
				}
			}
		}
	}
}

/**
 * Get custom checkout fields.
 *
 * @param  object $order WC_Order object.
 * @param  array  $types Field types to retrieve.
 * @return array  $custom_fields
 */
function wc_get_custom_checkout_fields( $order, $types = array( 'billing', 'shipping', 'additional' ) ) {
	$order_id      = $order->get_id();
	$all_fields    = array();
	$custom_fields = array();

	// Get all the fields.
	foreach ( $types as $type ) {
		// Skip if an unsupported type.
		if ( ! in_array( $type, array( 'billing', 'shipping', 'additional' ), true ) ) {
			continue;
		}

		$temp_fields = get_option( 'wc_fields_' . $type );
		if ( false !== $temp_fields ) {
			$all_fields = array_merge( $all_fields, $temp_fields );
		}
	}

	// Loop through each field to see if it is a custom field.
	foreach ( $all_fields as $name => $options ) {
		if ( isset( $options['custom'] ) && $options['custom'] ) {
			$custom_fields[ $name ] = $options;
		}
	}

	return $custom_fields;
}

/**
 * Get custom checkout fields data for admin order area
 *
 * @param object $order WC_Order object.
 * @param array  $types Field types to retrieve.
 */
function wc_get_custom_fields_for_admin_order( $order, $types ) {
	$fields = wc_get_custom_checkout_fields( $order, $types );
	$html   = '<div class="address custom_checkout_fields">';
	$found  = false;

	// Loop through all custom fields to see if it should be added.
	foreach ( $fields as $name => $options ) {
		$field_value = $order->get_meta( $name );
		if ( isset( $options['display_options'] ) && in_array( 'view_order', $options['display_options'], true ) && '' !== $field_value ) {
			$found = true;
			$html .= '<p><strong>' . esc_attr( $options['label'] ) . ':</strong>' . $field_value . '</p>';
		}
	}

	$html .= '</div>';

	if ( $found ) {
		echo wp_kses_post( $html );
	}
}

/**
 * Display custom billing checkout fields in admin order area.
 *
 * @param object $order WC_Order object.
 */
function wc_display_custom_billing_fields_admin_order( $order ) {
	wc_get_custom_fields_for_admin_order( $order, array( 'billing' ) );
}

/**
 * Display custom shipping and additional checkout fields in admin order area.
 *
 * @param object $order WC_Order object.
 */
function wc_display_custom_shipping_fields_admin_order( $order ) {
	wc_get_custom_fields_for_admin_order( $order, array( 'shipping', 'additional' ) );
}

/**
 * Remove the localization WC core script to ensure
 * the order remains how it is set in the field editor
 * settings.
 */
function wc_checkout_fields_dequeue_address_i18n() {
	if ( ! is_checkout() ) {
		return;
	}

	if ( apply_filters( 'wc_checkout_fields_dequeue_address_i18n', true ) ) {
		wp_dequeue_script( 'wc-address-i18n' );
		wp_deregister_script( 'wc-address-i18n' );

		wp_register_script( 'wc-address-i18n', plugins_url( '/dist/js/frontend.js', __DIR__ ), array( 'jquery', 'wc-country-select' ), WC_CHECKOUT_FIELD_EDITOR_VERSION, true );
	}
}

/**
 * Returns the value of an order's checkout field
 *
 * @param WC_Order $order Field's order.
 * @param string   $name Field's name.
 * @param array    $options Field's properties.
 * @return string
 */
function wc_get_checkout_field_value( $order, $name, $options ) {
	$field_value = $order->get_meta( $name );

	if ( array_key_exists( $field_value, $options['options'] ) ) {
		$field_value = $options['options'][ $field_value ];
	}

	if ( 'checkbox' === $options['type'] && '1' === $field_value ) {
		$field_value = __( 'yes', 'woocommerce-checkout-field-editor' );
	}

	return $field_value;
}
