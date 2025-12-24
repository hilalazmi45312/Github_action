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
        '_yoast_wpseo_title' => 'SEO Title',
        '_yoast_wpseo_metadesc' => 'SEO Description'
    ];

    /**
     * Special fields that require custom handling (not simple meta fields)
     */
    const SPECIAL_FIELDS = [
        'wcpb_product_badges' => 'Product Badges'
    ];

    public static function init()
    {
        // Export Columns
        add_filter('woocommerce_product_export_column_names', [self::class, 'add_export_columns']);
        add_filter('woocommerce_product_export_product_default_columns', [self::class, 'add_export_columns']);

        // Export Data for regular fields
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            add_filter("woocommerce_product_export_product_column_{$key}", [self::class, 'export_column_data'], 10, 3);
        }

        // Export Data for special fields
        add_filter('woocommerce_product_export_product_column_wcpb_product_badges', [self::class, 'export_wcpb_badges'], 10, 3);

        // Import Mapping
        add_filter('woocommerce_csv_product_import_mapping_options', [self::class, 'add_import_mapping_options']);
        add_filter('woocommerce_csv_product_import_mapping_default_columns', [self::class, 'add_import_mapping_defaults']);

        // Import Data Processing
        // For simple products and parent products
        add_filter('woocommerce_product_import_pre_insert_product_object', [self::class, 'import_product_data'], 10, 2);
        
        // For variations (inserted hook is often safer for variations to ensure parent link, though pre_insert often works too)
        add_action('woocommerce_product_import_inserted_product_object', [self::class, 'import_variation_data'], 10, 2);

        // Process WCPB badges after product is saved (needs product ID)
        add_action('woocommerce_product_import_inserted_product_object', [self::class, 'import_wcpb_badges'], 20, 2);
    }

    /**
     * Add new columns to the export headers
     */
    public static function add_export_columns($columns)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            $columns[$key] = $label;
        }
        // Add special fields
        foreach (self::SPECIAL_FIELDS as $key => $label) {
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
     * Export WCPB Product Badges assigned to the product
     * Returns a pipe-separated list of badge IDs that include this product
     */
    public static function export_wcpb_badges($value, $product, $column_id)
    {
        // Check if WCPB plugin is active
        if (!class_exists('WCPB_Product_Badges')) {
            return '';
        }

        $product_id = $product->get_id();
        $assigned_badges = [];

        // Get all badges that are set to "specific" display mode
        $badges = get_posts([
            'numberposts' => -1,
            'post_type' => 'wcpb_product_badge',
            'post_status' => 'publish',
            'fields' => 'ids',
        ]);

        if (empty($badges)) {
            return '';
        }

        foreach ($badges as $badge_id) {
            $display_products = get_post_meta($badge_id, '_wcpb_product_badges_display_products', true);
            
            // Only check badges with "specific" display setting
            if ($display_products !== 'specific') {
                continue;
            }

            $badge_specific_products = get_post_meta($badge_id, '_wcpb_product_badges_display_products_specific_products', true);
            
            if (!is_array($badge_specific_products)) {
                continue;
            }

            // Check if this product is in the badge's specific products list
            if (in_array($product_id, $badge_specific_products)) {
                // Use badge title instead of ID for better readability
                $badge_title = get_the_title($badge_id);
                if (!empty($badge_title)) {
                    $assigned_badges[] = $badge_title;
                }
            }
        }

        // Return pipe-separated list of badge titles
        return implode('|', $assigned_badges);
    }

    /**
     * Add custom fields to the import mapping dropdown options
     */
    public static function add_import_mapping_options($options)
    {
        foreach (self::CUSTOM_FIELDS as $key => $label) {
            $options[$key] = $label;
        }
        // Add special fields
        foreach (self::SPECIAL_FIELDS as $key => $label) {
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
        // Add special fields mapping
        foreach (self::SPECIAL_FIELDS as $key => $label) {
            $columns[$label] = $key;
            $columns[$key] = $key;
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

    /**
     * Import WCPB Product Badges - add product to specified badges' specific products list
     * Accepts pipe-separated list of badge IDs or badge titles
     * 
     * @param WC_Product $product The product object (already inserted)
     * @param array $data The import data
     */
    public static function import_wcpb_badges($product, $data)
    {
        // Check if WCPB plugin is active
        if (!class_exists('WCPB_Product_Badges')) {
            return;
        }

        // Check if we have badge data to import
        if (!isset($data['wcpb_product_badges']) || $data['wcpb_product_badges'] === '') {
            return;
        }

        $product_id = $product->get_id();
        $badge_identifiers = array_map('trim', explode('|', $data['wcpb_product_badges']));

        foreach ($badge_identifiers as $identifier) {
            if (empty($identifier)) {
                continue;
            }

            $badge_id = null;

            // Check if it's a numeric ID
            if (is_numeric($identifier)) {
                // Verify the badge exists
                $badge_post = get_post((int) $identifier);
                if ($badge_post && $badge_post->post_type === 'wcpb_product_badge') {
                    $badge_id = (int) $identifier;
                }
            } else {
                // Try to find badge by title
                $badge_posts = get_posts([
                    'post_type' => 'wcpb_product_badge',
                    'post_status' => 'publish',
                    'title' => $identifier,
                    'posts_per_page' => 1,
                    'fields' => 'ids',
                ]);

                if (!empty($badge_posts)) {
                    $badge_id = $badge_posts[0];
                }
            }

            if (!$badge_id) {
                continue; // Skip if badge not found
            }

            // Ensure badge is set to "specific" display mode
            $display_products = get_post_meta($badge_id, '_wcpb_product_badges_display_products', true);
            if ($display_products !== 'specific') {
                // Optionally, we could update the badge to "specific" mode, but for now skip
                continue;
            }

            // Get current specific products for this badge
            $badge_specific_products = get_post_meta($badge_id, '_wcpb_product_badges_display_products_specific_products', true);
            
            if (!is_array($badge_specific_products)) {
                $badge_specific_products = [];
            }

            // Add product to badge if not already in the list
            if (!in_array($product_id, $badge_specific_products)) {
                $badge_specific_products[] = $product_id;
                update_post_meta($badge_id, '_wcpb_product_badges_display_products_specific_products', $badge_specific_products);
            }
        }
    }
}

