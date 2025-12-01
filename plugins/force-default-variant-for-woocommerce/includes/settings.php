<?php

/**
 * Create the section beneath the products tab
 */
add_filter( 'woocommerce_get_sections_products', 'hpy_woo_add_section' );
function hpy_woo_add_section( $sections ) {
	$sections['hpy_variants'] = __( 'Variants', 'force-default-variant-for-woocommerce' );
	return $sections;
}

/**
 * Add settings to the specific section we created before
 */
add_filter( 'woocommerce_get_settings_products', 'hpy_woo_all_settings', 10, 2 );
function hpy_woo_all_settings( $settings, $current_section ) {
	/**
	 * Check the current section is what we want
	 */
	if ( 'hpy_variants' === $current_section ) {
		$settings_variant = array();

		$settings_variant[] = array(
			'name' => __( 'Default Variant Settings', 'force-default-variant-for-woocommerce' ),
			'type' => 'title',
			'id'   => 'hpy_variants',
		);

		$settings_variant[] = array(
			'name'     => __( 'Sort by:', 'force-default-variant-for-woocommerce' ),
			'desc_tip' => __( 'Change how you want the default variant to be sorted.', 'force-default-variant-for-woocommerce' ),
			'id'       => 'hpy_variant_sort',
			'type'     => 'select',
			'options'  => array(
				'id'             => __( 'ID', 'force-default-variant-for-woocommerce' ),
				'position'       => __( 'Variation Position', 'force-default-variant-for-woocommerce' ),
				'menu_order'     => __( 'Attribute Order', 'force-default-variant-for-woocommerce' ),
				'alphabetically' => __( 'Alphabetically', 'force-default-variant-for-woocommerce' ),
				'price-low'      => __( 'Price Low -> High', 'force-default-variant-for-woocommerce' ),
				'price-high'     => __( 'Price High -> Low', 'force-default-variant-for-woocommerce' ),
				'stock'          => __( 'Stock Levels', 'force-default-variant-for-woocommerce' ),
			),
			'css'      => 'min-width:300px;',
			'desc'     => __( 'How do you want to sort the variations', 'force-default-variant-for-woocommerce' ),
		);

		$settings_variant[] = array(
			'name'     => __( 'Then Sort by:', 'force-default-variant-for-woocommerce' ),
			'desc_tip' => __( 'If any Variations are the same in the above sorting method you can define a secondary method to sort them.', 'force-default-variant-for-woocommerce' ),
			'id'       => 'hpy_variant_then_sort',
			'type'     => 'select',
			'options'  => array(
				'then_id'    => __( 'ID', 'force-default-variant-for-woocommerce' ),
				'default'    => __( 'Position', 'force-default-variant-for-woocommerce' ),
				'then_sales' => __( 'Sales', 'force-default-variant-for-woocommerce' ),
				'then_stock' => __( 'Stock Levels', 'force-default-variant-for-woocommerce' ),
			),
			'css'      => 'min-width:300px;',
		);

		$settings_variant[] = array(
			'name'     => __( 'Stock Limit:', 'force-default-variant-for-woocommerce' ),
			'desc_tip' => __( 'Check the stock limit of the variant before displaying. If this is not set the Plugin will still check for the overall stock status.', 'force-default-variant-for-woocommerce' ),
			'id'       => 'hpy_variant_stockLimit',
			'type'     => 'text',
			'desc'     => __( 'Skip Variant if Stock level is below this limit', 'force-default-variant-for-woocommerce' ),
		);

		$settings_variant[] = array(
			'name'     => __( 'Keep manually set defaults:', 'force-default-variant-for-woocommerce' ),
			'desc_tip' => __( 'If you have already set manual defaults for some products and want to keep those select this box. Otherwise Force Default Variant will overwrite that option.', 'force-default-variant-for-woocommerce' ),
			'id'       => 'hpy_variant_respect',
			'std'      => 'no', // WooCommerce < 2.0
			'default'  => 'no', // WooCommerce >= 2.0
			'type'     => 'checkbox',
			'desc'     => __( 'Respect the Product\'s Default if it is already set.', 'force-default-variant-for-woocommerce' ),
		);

		$settings_variant[] = array(
			'name'     => __( 'Disable auto-removal of \'Select Option\' text ', 'force-default-variant-for-woocommerce' ),
			'desc_tip' => __( 'If you would like dropdowns to have the \'Select Option\' text by default when no product defaults are set, select this box. Force Default Variant options will still apply.', 'force-default-variant-for-woocommerce' ),
			'id'       => 'hpy_disabled_auto_remove_dropdown',
			'std'      => 'no', // WooCommerce < 2.0
			'default'  => 'no', // WooCommerce >= 2.0
			'type'     => 'checkbox',
			'desc'     => __( 'Re-enable \'Select Option\' text globally, manually set defaults will still apply.', 'force-default-variant-for-woocommerce' ),
		);

		$settings_variant[] = array(
			'type' => 'sectionend',
			'id'   => 'hpy_variants',
		);
		return apply_filters( 'hpy_fdv_woocommerce_settings', $settings_variant );

		/**
		 * If not, return the standard settings
		 */
	} else {
		return $settings;
	}
}
