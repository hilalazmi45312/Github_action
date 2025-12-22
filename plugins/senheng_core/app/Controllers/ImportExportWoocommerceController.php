<?php


class ImportExportWoocommerceController
{
    /**
     * Custom fields to register for Import/Export
     */
    const CUSTOM_FIELDS = [
        'mpn' => 'MPN',
        'pn' => 'Part Number (PN)',
        '_global_unique_id' => 'Global Unique ID',
        's_coin_value' => 'S-Coin Value (%)',
        '_awcdp_deposit_enabled' => 'Trade In Deposit',
        '_awcdp_deposit_type' => 'Deposit Type',
        '_awcdp_deposits_deposit_amount' => 'Deposit Amount',
        'warranty_enabled_sh' => 'RM9.90 Warranty',
        'product_warranty' => 'Product Warranty',
        '_yoast_wpseo_title' => 'SEO Title (Yoast)',
        '_yoast_wpseo_metadesc' => 'SEO Description (Yoast)'
    ];

    public static function init()
    {
        // Export Columns
        add_filter('woocommerce_product_export_column_names', [self::class, 'add_export_columns']);
        add_filter('woocommerce_product_export_product_default_columns', [self::class, 'add_export_columns']);

        // Export Data
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            add_filter("woocommerce_product_export_product_column_{$key}", [self::class, 'export_column_data'], 10, 3);
        }

        // Import Mapping
        add_filter('woocommerce_csv_product_import_mapping_options', [self::class, 'add_import_mapping_options']);
        add_filter('woocommerce_csv_product_import_mapping_default_columns', [self::class, 'add_import_mapping_defaults']);

        // Import Data Processing
        // For simple products and parent products
        add_filter('woocommerce_product_import_pre_insert_product_object', [self::class, 'import_product_data'], 10, 2);
        
        // For variations (inserted hook is often safer for variations to ensure parent link, though pre_insert often works too)
        add_action('woocommerce_product_import_inserted_product_object', [self::class, 'import_variation_data'], 10, 2);
    }

    /**
     * Add new columns to the export headers
     */
    public static function add_export_columns($columns)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            $columns[$key] = $label;
        }
        return $columns;
    }

    /**
     * Populate the data for the custom columns during export
     */
    public static function export_column_data($value, $product, $column_id)
    {
        // Check if we are handling one of our custom columns
        if (array_key_exists($column_id, self::CUSTOM_FIELDS)) {
            $product_id = $product->get_id();
            $meta_value = get_post_meta($product_id, $column_id, true);
            
            // Fallback: Check if it's available via get_field (ACF) if empty
            if (empty($meta_value) && function_exists('get_field')) {
                $meta_value = get_field($column_id, $product_id);
            }

            return $meta_value !== '' && $meta_value !== null ? $meta_value : '';
        }
        
        return $value;
    }

    /**
     * Add custom fields to the import mapping dropdown options
     */
    public static function add_import_mapping_options($options)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            $options[$key] = $label;
        }
        return $options;
    }

    /**
     * Auto-map columns if the CSV header matches our keys or labels
     */
    public static function add_import_mapping_defaults($columns)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            $columns[$label] = $key; // Map Label -> Key
            $columns[$key] = $key;   // Map Key -> Key
        }
        
        return $columns;
    }

    /**
     * Process import data for Products (Simple, Variable Parent)
     * Runs before the product object is saved.
     */
    public static function import_product_data($product, $data)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            if (isset($data[$key]) && $data[$key] !== '') {
                $value = sanitize_text_field($data[$key]);
                
                // Update product meta
                $product->update_meta_data($key, $value);
                
                // Also update ACF field for s_coin_value to ensure compatibility
                // Note: Product ID may not exist yet at this hook, so we rely on meta update
                // ACF field will be synced after save if needed
            }
        }
        return $product;
    }

    /**
     * Process import data for Variations
     * Runs after the product object is inserted.
     */
    public static function import_variation_data($product, $data)
    {
        if ($product->is_type('variation')) {
            $needs_save = false;
            
            foreach (self::CUSTOM_FIELDS as $key => $label) {
                if (isset($data[$key]) && $data[$key] !== '') {
                    $value = sanitize_text_field($data[$key]);
                    
                    // Update post meta
                    $product->update_meta_data($key, $value);
                    $needs_save = true;
                    
                    // Also update ACF field for s_coin_value to ensure compatibility
                    if ($key === 's_coin_value' && function_exists('update_field')) {
                        update_field('s_coin_value', floatval($value), $product->get_id());
                    }
                }
            }
            
            if ($needs_save) {
                $product->save(); // Save once after all meta updates
            }
        }
    }
}
