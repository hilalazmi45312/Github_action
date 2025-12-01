<?php
class ProductImportModel
{
    public function updateProductBySKU($data, &$log)
    {
        $product_name = isset($data['name']) ? trim($data['name']) : '';
        $sku = isset($data['itemId']) ? trim($data['itemId']) : '';
        $is_in_stock = (isset($data['in_stock']) && $data['in_stock'] == '1');
        $stock_qty = isset($data['StockQty']) ? intval($data['StockQty']) : null;

        if (empty($sku)) {
            throw new Exception('SKU is required for updating products');
        }

        $log .= "Starting update for product: $product_name | SKU: $sku\n";
        error_log("[ProductImport] Processing update for product: $product_name | SKU: $sku");

        $product_id = wc_get_product_id_by_sku($sku);
        if (!$product_id) {
            throw new Exception("Product with SKU $sku not found");
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            throw new Exception("Failed to load product with SKU $sku");
        }

        // Check if this is a variation product
        if ($product->get_type() === 'variation') {
            $log .= "Updating variation product with SKU: $sku\n";
            return $this->updateVariationProduct($product, $data, $log);
        }

        // Handle simple/variable product updates
        if (!empty($product_name)) {
            $product->set_name($product_name);
        }
        if (isset($data['original_price'])) {
            $product->set_regular_price($data['original_price']);
        }
        if (!empty($data['price'])) {
            $product->set_sale_price($data['price']);
        }
        $product->set_manage_stock(true);
        $product->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
        if ($stock_qty !== null) {
            $product->set_stock_quantity($stock_qty);
        }

        $image_url = $data['imageUrl'] ?? '';
        if (!empty($image_url)) {
            $log .= "  Processing image for product: $image_url\n";
            
            // Check if this image URL is already being used by this product
            $already_used = $this->isImageUrlAlreadyUsed($image_url, $product_id);
            if ($already_used) {
                $log .= "  Skipped: Image URL already in use for product SKU: $sku\n";
            } else {
                $image_id = $this->importImage($image_url);
                if (is_wp_error($image_id)) {
                    $log .= "  Image update error: " . $image_id->get_error_message() . " (URL: $image_url)\n";
                } elseif ($image_id) {
                    // Check if this image is already being used by other products
                    $used_by_others = $this->isImageAlreadyUsed($image_id, $product_id);
                    if ($used_by_others) {
                        $log .= "  Using existing image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                    } else {
                        $log .= "  Using existing image (ID: $image_id) not used by other products\n";
                    }
                    set_post_thumbnail($product_id, $image_id);
                    $log .= "  Updated featured image for product\n";
                } else {
                    $log .= "  Image update failed, no image ID returned (URL: $image_url)\n";
                }
            }
        }

        if (function_exists('update_field')) {
            update_field('s_coin_value', $data['Scoin'] ?? '', $product_id);
        } else {
            update_post_meta($product_id, 's_coin_value', $data['Scoin'] ?? '');
        }
        update_post_meta($product_id, '_sales_quantity', $data['sale_quantity'] ?? '');

        $brand_name = $data['brand_name'] ?? '';
        if (!empty($brand_name)) {
            $this->assignBrand($product_id, $brand_name, $log);
        }
        $category_path = $data['categoryId'] ?? '';
        if (!empty($category_path)) {
            $this->assignCategory($product_id, $category_path, $log);
        }

        // Only call updateVariableProduct for variable products, not variations
        if (!empty($data['Variant']) && trim($data['Variant']) !== '[]' && $product->get_type() === 'variable') {
            $this->updateVariableProduct($product_id, $data, $log);
        }

        $product->save();
        $log .= "Updated product: $product_name\n\n";
        return $product_id;
    }

    /**
     * Update a variation product specifically
     */
    public function updateVariationProduct($variation, $data, &$log)
    {
        global $wpdb;
        
        $product_name = isset($data['name']) ? $data['name'] : '';
        $sku = isset($data['itemId']) ? $data['itemId'] : '';
        $is_in_stock = (isset($data['in_stock']) && $data['in_stock'] == '1');
        $stock_qty = isset($data['StockQty']) ? intval($data['StockQty']) : null;
        
        $variation_id = $variation->get_id();
        $parent_id = $variation->get_parent_id();
        
        $log .= "Updating variation ID: $variation_id (Parent: $parent_id) with SKU: $sku\n";
        error_log("[ProductImport] Processing update variation for product: $product_name | SKU: $sku");
        
        // Update variation basic properties
        if (isset($data['original_price'])) {
            $variation->set_regular_price($data['original_price']);
        }
        if (!empty($data['price'])) {
            $variation->set_sale_price($data['price']);
        }
        $variation->set_manage_stock(true);
        $variation->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
        if ($stock_qty !== null) {
            $variation->set_stock_quantity($stock_qty);
        }
        
        // Update variation image if provided
        $variant_json = $data['Variant'] ?? '';
        if (!empty($variant_json) && trim($variant_json) !== '[]') {
            $attributes_array = json_decode($variant_json, true);
            if (is_array($attributes_array)) {
                $variation_image_url = '';
                
                // Find image from variant attributes
                foreach ($attributes_array as $attr) {
                    if (!empty($attr['image'])) {
                        $variation_image_url = $attr['image'];
                        break;
                    }
                }
                
                // Use main image if no variant image found
                if (empty($variation_image_url) && !empty($data['imageUrl'])) {
                    $variation_image_url = $data['imageUrl'];
                }
                
                if (!empty($variation_image_url)) {
                    $log .= "  Processing variation image: $variation_image_url\n";
                    
                    // Check if this image URL is already being used by this variation
                    $already_used = $this->isImageUrlAlreadyUsed($variation_image_url, $variation_id);
                    if ($already_used) {
                        $log .= "  Skipped: Image URL already in use for variation SKU: $sku\n";
                    } else {
                        $image_id = $this->importImage($variation_image_url);
                        if (is_wp_error($image_id)) {
                            $log .= "  Variation image update error: " . $image_id->get_error_message() . "\n";
                        } elseif ($image_id) {
                            // Check if this image is already being used by other variations/products
                            $used_by_others = $this->isImageAlreadyUsed($image_id, $variation_id);
                            if ($used_by_others) {
                                $log .= "  Using existing variation image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                            } else {
                                $log .= "  Using existing variation image (ID: $image_id) not used by other products\n";
                            }
                            $variation->set_image_id($image_id);
                            $log .= "  Updated variation image\n";
                            
                            // Also update the parent variable product's featured image
                            $parent_id = $variation->get_parent_id();
                            if ($parent_id) {
                                // Check if parent already has this image URL
                                $parent_already_has_url = $this->isImageUrlAlreadyUsed($variation_image_url, $parent_id);
                                if ($parent_already_has_url) {
                                    $log .= "  Parent variable product already has this image URL, skipping parent update\n";
                                } else {
                                    // Check if parent has any featured image
                                    $parent_featured_id = get_post_thumbnail_id($parent_id);
                                    if ($parent_featured_id) {
                                        $parent_featured_url = get_post_meta($parent_featured_id, '_source_url', true);
                                        if ($parent_featured_url === $variation_image_url) {
                                            $log .= "  Parent variable product already has this image as featured, skipping\n";
                                        } else {
                                            // Only update parent if it doesn't have a featured image from imageUrl
                                            $parent_product = wc_get_product($parent_id);
                                            if ($parent_product && !empty($parent_product->get_meta('_has_imageurl_featured'))) {
                                                $log .= "  Parent variable product has featured image from imageUrl, skipping variation image override\n";
                                            } else {
                                                set_post_thumbnail($parent_id, $image_id);
                                                $log .= "  Updated parent variable product featured image (ID: $parent_id) with variation image\n";
                                            }
                                        }
                                    } else {
                                        // Only set parent featured image if it doesn't have one from imageUrl
                                        $parent_product = wc_get_product($parent_id);
                                        if ($parent_product && !empty($parent_product->get_meta('_has_imageurl_featured'))) {
                                            $log .= "  Parent variable product has featured image from imageUrl, skipping variation image override\n";
                                        } else {
                                            set_post_thumbnail($parent_id, $image_id);
                                            $log .= "  Set parent variable product featured image (ID: $parent_id) with variation image\n";
                                        }
                                    }
                                }
                            }
                        } else {
                            $log .= "  Variation image update failed, no image ID returned\n";
                        }
                    }
                }
                
                // Update variation attributes if needed
                $this->updateVariationAttributes($variation, $attributes_array, $log);
            }
        }
        
        // Update custom fields for variation
        if (function_exists('update_field')) {
            update_field('s_coin_value', $data['Scoin'] ?? '', $variation_id);
        } else {
            update_post_meta($variation_id, 's_coin_value', $data['Scoin'] ?? '');
        }
        update_post_meta($variation_id, '_sales_quantity', $data['sale_quantity'] ?? '');
        
        $variation->save();
        
        // Update parent product name if provided
        if (!empty($product_name)) {
            $parent_product = wc_get_product($parent_id);
            if ($parent_product && $parent_product->get_name() !== $product_name) {
                $parent_product->set_name($product_name);
                $parent_product->save();
                $log .= "  Updated parent product name to: $product_name\n";
            }
        }
        
        $log .= "Updated variation: $sku\n\n";
        return $variation_id;
    }

    /**
     * Update variation attributes
     */
    private function updateVariationAttributes($variation, $attributes_array, &$log)
    {
        global $wpdb;
        
        $variation_attributes = [];
        $parent_id = $variation->get_parent_id();
        $parent_product = wc_get_product($parent_id);
        
        foreach ($attributes_array as $attr) {
            $original_label = trim($attr['attrKey']);
            $display_value = trim($attr['attrVal']);
            
            // Smart shorten the attribute name for slug only
            $slug = $this->smartShortenAttributeName($original_label);
            
            $log .= "  Processing variation attribute: $original_label";
            $log .= " (Slug: $slug)";
            $log .= " = $display_value\n";
            
            // Find or create the global attribute
            $attribute_row = $wpdb->get_row($wpdb->prepare(
                "SELECT attribute_id, attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE LOWER(attribute_label) = %s OR attribute_name = %s",
                strtolower($original_label), wc_sanitize_taxonomy_name($slug)
            ));
            
            if ($attribute_row) {
                $taxonomy = 'pa_' . $attribute_row->attribute_name;
            } else {
                $taxonomy = 'pa_' . $slug;
                
                // Create attribute if it doesn't exist
                $attribute_id = wc_create_attribute([
                    'name'         => $original_label, // Keep the original label
                    'slug'         => $slug,
                    'type'         => 'select',
                    'order_by'     => 'menu_order',
                    'has_archives' => false,
                ]);
                
                if (is_wp_error($attribute_id)) {
                    $log .= "  Failed to create attribute $original_label: " . $attribute_id->get_error_message() . "\n";
                    continue;
                }
                
                delete_transient('wc_attribute_taxonomies');
            }
            
            // Ensure term exists
            $term = get_term_by('name', $display_value, $taxonomy);
            if (!$term || is_wp_error($term)) {
                $term_data = wp_insert_term($display_value, $taxonomy);
                if (is_wp_error($term_data)) {
                    $log .= "  Failed to create term $display_value: " . $term_data->get_error_message() . "\n";
                    continue;
                }
                $term = get_term($term_data['term_id'], $taxonomy);
            }
            
            if ($term && !is_wp_error($term)) {
                $variation_attributes[$taxonomy] = $term->slug;
            }
        }
        
        // Update variation attributes
        if (!empty($variation_attributes)) {
            $variation->set_attributes($variation_attributes);
            $log .= "  Updated variation attributes: " . print_r($variation_attributes, true) . "\n";
        }
    }

    public function updateVariableProduct($parent_id, $data, &$log)
    {
        global $wpdb;
        $product_name = isset($data['name']) ? $data['name'] : '';
        $sku = isset($data['itemId']) ? $data['itemId'] : '';

        $wc_product = wc_get_product($parent_id);
        if ($wc_product->get_type() !== 'variable') {
            $wc_product = new WC_Product_Variable();
            $wc_product->set_id($parent_id);
            $wc_product->set_name($product_name);
            $wc_product->set_regular_price($data['original_price'] ?? '0');
            if (!empty($data['price'])) {
                $wc_product->set_sale_price($data['price']);
            }
            $wc_product->save();
            $log .= "Converted to variable product: $product_name\n";
        }

        $parent_image_url = $data['imageUrl'] ?? '';
        if (!empty($parent_image_url)) {
            $log .= "  Processing image for variable product update: $parent_image_url\n";
            
            // Check if this image URL is already being used by this product
            $already_used = $this->isImageUrlAlreadyUsed($parent_image_url, $parent_id);
            if ($already_used) {
                $log .= "  Skipped: Image URL already in use for variable product SKU: $sku\n";
            } else {
                $image_id = $this->importImage($parent_image_url);
                if (is_wp_error($image_id)) {
                    $log .= "  Image update error: " . $image_id->get_error_message() . " (URL: $parent_image_url)\n";
                } elseif ($image_id) {
                    // Check if this image is already being used by other products
                    $used_by_others = $this->isImageAlreadyUsed($image_id, $parent_id);
                    if ($used_by_others) {
                        $log .= "  Using existing image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                    } else {
                        $log .= "  Using existing image (ID: $image_id) not used by other products\n";
                    }
                    set_post_thumbnail($parent_id, $image_id);
                    $log .= "  Updated parent featured image\n";
                    
                    // Mark that this parent has a featured image from imageUrl
                    update_post_meta($parent_id, '_has_imageurl_featured', 'yes');
                } else {
                    $log .= "  Image update failed, no image ID returned (URL: $parent_image_url)\n";
                }
            }
        }

        $attributes_array = json_decode($data['Variant'], true);
        $wc_attributes = [];
        $attribute_terms_map = [];
        $variation_attributes = [];

        if (is_array($attributes_array)) {
            foreach ($attributes_array as $attr) {
                $original_label = trim($attr['attrKey']);
                $display_value = trim($attr['attrVal']);

                // Skip empty attribute values
                if (empty($display_value)) {
                    $log .= "  Skipping empty attribute value for key: $original_label\n";
                    continue;
                }

                // Smart shorten the attribute name for slug
                $slug = $this->smartShortenAttributeName($original_label, $log);
                $taxonomy = 'pa_' . $slug;

                $log .= "  Processing attribute: $original_label (Slug: $slug) = $display_value\n";

                // Check if attribute exists
                $attribute_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT attribute_id, attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE LOWER(attribute_label) = %s OR attribute_name = %s",
                    strtolower($original_label), $slug
                ));

                if ($attribute_row) {
                    $attribute_id = $attribute_row->attribute_id;
                    $taxonomy = 'pa_' . $attribute_row->attribute_name;
                } else {
                    // Create new attribute
                    $attribute_id = wc_create_attribute([
                        'name'         => $original_label,
                        'slug'         => $slug,
                        'type'         => 'select',
                        'order_by'     => 'menu_order',
                        'has_archives' => false,
                    ]);
                    if (is_wp_error($attribute_id)) {
                        $log .= "  Failed to create attribute $original_label: " . $attribute_id->get_error_message() . "\n";
                        continue;
                    }
                    delete_transient('wc_attribute_taxonomies');
                    register_taxonomy(
                        $taxonomy,
                        apply_filters('woocommerce_taxonomy_objects_' . $taxonomy, ['product']),
                        apply_filters('woocommerce_taxonomy_args_' . $taxonomy, [
                            'labels'       => ['name' => $original_label],
                            'hierarchical' => true,
                            'show_ui'      => false,
                            'query_var'    => true,
                            'rewrite'      => false,
                        ])
                    );
                }

                // Sanitize term name and slug separately
                $sanitized_term_name = sanitize_text_field($display_value);
                $sanitized_term_slug = sanitize_title($display_value);

                // Check for existing terms and handle conflicts
                $existing_terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'fields'     => 'all',
                ]);
                if (is_wp_error($existing_terms)) {
                    $log .= "  Error retrieving terms for taxonomy '$taxonomy': " . $existing_terms->get_error_message() . "\n";
                    continue;
                }

                foreach ($existing_terms as $existing_term) {
                    // Handle case where $existing_term is an array instead of a WP_Term object
                    $term_name = is_object($existing_term) ? $existing_term->name : (isset($existing_term['name']) ? $existing_term['name'] : '');
                    $term_slug = is_object($existing_term) ? $existing_term->slug : (isset($existing_term['slug']) ? $existing_term['slug'] : '');
                    $term_id = is_object($existing_term) ? $existing_term->term_id : (isset($existing_term['term_id']) ? $existing_term['term_id'] : 0);

                    if ($term_name && $term_name !== $display_value && sanitize_title($term_name) === $sanitized_term_slug) {
                        wp_delete_term($term_id, $taxonomy);
                        $log .= "  Deleted conflicting term: $term_name (Slug: $term_slug)\n";
                    }
                }

                // Create or get term
                $term = get_term_by('name', $display_value, $taxonomy);
                if (!$term || is_wp_error($term)) {
                    $term_data = wp_insert_term($display_value, $taxonomy, [
                        'slug' => $sanitized_term_slug,
                    ]);
                    if (is_wp_error($term_data)) {
                        $log .= "  Failed to create term '$display_value' for taxonomy '$taxonomy': " . $term_data->get_error_message() . "\n";
                        continue;
                    }
                    $term = get_term($term_data['term_id'], $taxonomy);
                    $log .= "  Created new term: $display_value (ID: {$term->term_id}, Slug: {$term->slug})\n";
                } else {
                    $log .= "  Found existing term: $display_value (ID: {$term->term_id}, Slug: {$term->slug})\n";
                }

                if ($term && !is_wp_error($term)) {
                    wp_set_object_terms($parent_id, (int)$term->term_id, $taxonomy, true);
                    $attribute_terms_map[$taxonomy] = $attribute_terms_map[$taxonomy] ?? [];
                    $attribute_terms_map[$taxonomy][$display_value] = (int)$term->term_id;
                    $variation_attributes[$taxonomy] = $display_value;

                    // Create attribute object
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_id($attribute_id);
                    $attribute->set_name($taxonomy);
                    $attribute->set_position(0);
                    $attribute->set_visible(true);
                    $attribute->set_variation(true);
                    $attribute->set_options(array_values($attribute_terms_map[$taxonomy]));
                    $wc_attributes[$taxonomy] = $attribute;
                }
            }

            // Update parent product attributes
            $parent_attributes = $wc_product->get_attributes();
            foreach ($wc_attributes as $taxonomy => $new_attr) {
                if (isset($parent_attributes[$taxonomy])) {
                    $existing_options = $parent_attributes[$taxonomy]->get_options();
                    $merged_options = array_unique(array_merge($existing_options, $new_attr->get_options()));
                    $new_attr->set_options($merged_options);
                }
                $parent_attributes[$taxonomy] = $new_attr;
            }
            $wc_product->set_attributes($parent_attributes);
            $wc_product->save();

            // Create variation if attributes exist
            if (!empty($variation_attributes)) {
                $this->createVariation($parent_id, $data, $variation_attributes, $log);
                $log .= "  Created variation with attributes: " . print_r($variation_attributes, true) . "\n";
            }

            // Update attribute lookup table
            if (class_exists('WC_Product_Attribute_Lookup_Data_Store')) {
                $lookup_store = new WC_Product_Attribute_Lookup_Data_Store();
                $lookup_store->delete_product_attributes_lookup_data($parent_id);
                $lookup_store->create_product_attributes_lookup_data($parent_id);
                $log .= "  Rebuilt attribute lookup table for product ID: $parent_id\n";
            } else {
                delete_transient('wc_layered_nav_counts');
                WC_Cache_Helper::get_transient_version('wc_layered_nav_counts', true);
                $log .= "  Cleared attribute nav cache for product ID: $parent_id\n";
            }
        }

        return $parent_id;
    }

    public function createSimpleProduct($data, &$log)
    {
        $product_name = isset($data['name']) ? trim($data['name']) : '';
        $is_in_stock = (isset($data['in_stock']) && $data['in_stock'] == '1');
        $stock_qty = isset($data['StockQty']) ? intval($data['StockQty']) : null;
        $sku = isset($data['itemId']) ? $data['itemId'] : '';

        $log .= "Starting import for simple product: $product_name | SKU: $sku\n";
        error_log("[ProductImport] Processing for simple product: $product_name | SKU: $sku");

        $simple = new WC_Product_Simple();
        $simple->set_name($product_name);
        $simple->set_regular_price($data['original_price'] ?? '0');
        if (!empty($data['price'])) {
            $simple->set_sale_price($data['price']);
        }
        if (!empty($sku)) {
            $simple->set_sku($sku);
        }
        $simple->set_manage_stock(true);
        $simple->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
        if ($stock_qty !== null) {
            $simple->set_stock_quantity($stock_qty);
        }
        $simple->save();
        $product_id = $simple->get_id();

        $image_url = $data['imageUrl'] ?? '';
        if (!empty($image_url)) {
            $log .= "  Processing image for simple product: $image_url\n";
            
            // Check if this image URL is already being used by this product
            $already_used = $this->isImageUrlAlreadyUsed($image_url, $product_id);
            if ($already_used) {
                $log .= "  Skipped: Image URL already in use for simple product SKU: $sku\n";
            } else {
                $image_id = $this->importImage($image_url);
                if (is_wp_error($image_id)) {
                    $log .= "  Image import error: " . $image_id->get_error_message() . " (URL: $image_url)\n";
                } elseif ($image_id) {
                    // Check if this image is already being used by other products
                    $used_by_others = $this->isImageAlreadyUsed($image_id, $product_id);
                    if ($used_by_others) {
                        $log .= "  Using existing image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                    } else {
                        $log .= "  Using existing image (ID: $image_id) not used by other products\n";
                    }
                    set_post_thumbnail($product_id, $image_id);
                    $log .= "  Set featured image for simple product\n";
                } else {
                    $log .= "  Image import failed, no image ID returned (URL: $image_url)\n";
                }
            }
        }

        if (function_exists('update_field')) {
            update_field('s_coin_value', $data['Scoin'] ?? '', $product_id);
        } else {
            update_post_meta($product_id, 's_coin_value', $data['Scoin'] ?? '');
        }
        update_post_meta($product_id, '_sales_quantity', $data['sale_quantity'] ?? '');

        $brand_name = $data['brand_name'] ?? '';
        if (!empty($brand_name)) {
            $this->assignBrand($product_id, $brand_name, $log);
        }
        $category_path = $data['categoryId'] ?? '';
        if (!empty($category_path)) {
            $this->assignCategory($product_id, $category_path, $log);
        }
        $log .= "Created simple product: $product_name\n\n";
        return $product_id;
    }

    public function createVariableProduct($data, &$log)
    {
        global $wpdb;
        $product_name = isset($data['name']) ? $data['name'] : '';
        $sku = isset($data['itemId']) ? $data['itemId'] : '';

        $parent = get_posts([
            'post_type'      => 'product',
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 10,
            'meta_query'     => [
                ['key' => '_import_group_key', 'value' => $product_name, 'compare' => '='],
            ],
        ]);

        if (!$parent) {
            $simple = get_posts([
                'post_type'      => 'product',
                'post_status'    => ['publish', 'draft', 'pending', 'private'],
                'posts_per_page' => 10,
                's'              => $product_name,
            ]);
            $simple = array_filter($simple, function ($post) use ($product_name) {
                return $post->post_title === $product_name;
            });
            if ($simple) {
                $log .= "Processing conversion simple to variable product: $product_name\n";
                $parent_id = reset($simple)->ID;
                $wc_product = new WC_Product_Variable();
                $wc_product->set_id($parent_id);
                $wc_product->set_name($product_name);
                $wc_product->set_regular_price($data['original_price'] ?? '0');
                if (!empty($data['price'])) {
                    $wc_product->set_sale_price($data['price']);
                }
                $wc_product->save();
                update_post_meta($parent_id, '_import_group_key', $product_name);
                $log .= "Converted simple product to variable: $product_name\n\n";
            } else {
                $log .= "Processing import for variable product: $product_name\n";
                $wc_product = new WC_Product_Variable();
                $wc_product->set_name($product_name);
                $wc_product->set_regular_price($data['original_price'] ?? '0');
                if (!empty($data['price'])) {
                    $wc_product->set_sale_price($data['price']);
                }
                $wc_product->save();
                $parent_id = $wc_product->get_id();
                update_post_meta($parent_id, '_import_group_key', $product_name);
                $log .= "Created variable parent: $product_name\n\n";
            }
        } else {
            $log .= "Processing conversion to variable product: $product_name\n";
            $parent_id = $parent[0]->ID;
            $wc_product = wc_get_product($parent_id);
            if ($wc_product->get_type() !== 'variable') {
                $wc_product = new WC_Product_Variable();
                $wc_product->set_id($parent_id);
                $wc_product->set_name($product_name);
                $wc_product->set_regular_price($data['original_price'] ?? '0');
                if (!empty($data['price'])) {
                    $wc_product->set_sale_price($data['price']);
                }
                $wc_product->save();
                $log .= "Converted to variable product: $product_name\n\n";
            }   
        }
        $parent_id = $wc_product->get_id();

        $attr_str = '';
        $label_map = [];
        if (!empty($data['Variant'])) {
            $attributes_array = json_decode($data['Variant'], true);
            if (is_array($attributes_array)) {
                foreach ($attributes_array as $attr) {
                    if (isset($attr['attrKey'], $attr['attrVal'])) {
                        $label_map[$attr['attrKey']] = $attr['attrVal'];
                    }
                }
            }
        }
        if (!empty($label_map)) {
            $attr_pairs = [];
            foreach ($label_map as $label => $val) {
                $attr_pairs[] = $label . ': ' . $val;
            }
            $attr_str = implode(', ', $attr_pairs);
        }
        $log .= "Starting import for variation: $product_name | SKU: $sku | Attributes: $attr_str\n";
        error_log("[ProductImport] Processing for variation of variable product: $product_name | SKU: $sku");

        $parent_image_url = $data['imageUrl'] ?? '';
        $image_id = false;
        if (!empty($parent_image_url)) {
            $log .= "  Processing image for variable product: $parent_image_url\n";
            
            // Check if this image URL is already being used by this product
            $already_used = $this->isImageUrlAlreadyUsed($parent_image_url, $parent_id);
            if ($already_used) {
                $log .= "  Skipped: Image URL already in use for variable product SKU: $sku\n";
            } else {
                $image_id = $this->importImage($parent_image_url);
                if (is_wp_error($image_id)) {
                    $log .= "  Image import error: " . $image_id->get_error_message() . " (URL: $parent_image_url)\n";
                } elseif ($image_id) {
                    // Check if this image is already being used by other products
                    $used_by_others = $this->isImageAlreadyUsed($image_id, $parent_id);
                    if ($used_by_others) {
                        $log .= "  Using existing image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                    } else {
                        $log .= "  Using existing image (ID: $image_id) not used by other products\n";
                    }
                    set_post_thumbnail($parent_id, $image_id);
                    $log .= "  Set parent featured image from imageUrl\n";
                    
                    // Mark that this parent has a featured image from imageUrl
                    update_post_meta($parent_id, '_has_imageurl_featured', 'yes');
                } else {
                    $log .= "  Image import failed, no image ID returned (URL: $parent_image_url)\n";
                }
            }
        }

        if (function_exists('update_field')) {
            update_field('s_coin_value', $data['Scoin'] ?? '', $parent_id);
        } else {
            update_post_meta($parent_id, 's_coin_value', $data['Scoin'] ?? '');
        }
        $brand_name = $data['brand_name'] ?? '';
        if (!empty($brand_name)) {
            $this->assignBrand($parent_id, $brand_name, $log);
        }
        $category_path = $data['categoryId'] ?? '';
        if (!empty($category_path)) {
            $this->assignCategory($parent_id, $category_path, $log);    
        }

        $attributes_array = json_decode($data['Variant'], true);
        $wc_attributes = [];
        $attribute_terms_map = [];
        $variation_attributes = [];

        if (is_array($attributes_array)) {
            foreach ($attributes_array as $attr) {
                $original_label = trim($attr['attrKey']);
                $display_value = trim($attr['attrVal']);

                // Skip empty attribute values
                if (empty($display_value)) {
                    $log .= "  Skipping empty attribute value for key: $original_label\n";
                    continue;
                }

                // Smart shorten the attribute name for slug
                $slug = $this->smartShortenAttributeName($original_label, $log);
                $taxonomy = 'pa_' . $slug;

                $log .= "  Processing attribute: $original_label (Slug: $slug) = $display_value\n";

                // Check if attribute exists
                $attribute_row = $wpdb->get_row($wpdb->prepare(
                    "SELECT attribute_id, attribute_name, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE LOWER(attribute_label) = %s OR attribute_name = %s",
                    strtolower($original_label), $slug
                ));

                if ($attribute_row) {
                    $attribute_id = $attribute_row->attribute_id;
                    $taxonomy = 'pa_' . $attribute_row->attribute_name;
                } else {
                    // Create new attribute
                    $attribute_id = wc_create_attribute([
                        'name'         => $original_label,
                        'slug'         => $slug,
                        'type'         => 'select',
                        'order_by'     => 'menu_order',
                        'has_archives' => false,
                    ]);
                    if (is_wp_error($attribute_id)) {
                        $log .= "  Failed to create attribute $original_label: " . $attribute_id->get_error_message() . "\n";
                        continue;
                    }
                    delete_transient('wc_attribute_taxonomies');
                    register_taxonomy(
                        $taxonomy,
                        apply_filters('woocommerce_taxonomy_objects_' . $taxonomy, ['product']),
                        apply_filters('woocommerce_taxonomy_args_' . $taxonomy, [
                            'labels'       => ['name' => $original_label],
                            'hierarchical' => true,
                            'show_ui'      => false,
                            'query_var'    => true,
                            'rewrite'      => false,
                        ])
                    );
                }

                // Sanitize term name and slug separately
                $sanitized_term_name = sanitize_text_field($display_value);
                $sanitized_term_slug = sanitize_title($display_value);

                // Check for existing terms and handle conflicts
                $existing_terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                    'fields'     => 'all',
                ]);
                if (is_wp_error($existing_terms)) {
                    $log .= "  Error retrieving terms for taxonomy '$taxonomy': " . $existing_terms->get_error_message() . "\n";
                    continue;
                }

                foreach ($existing_terms as $existing_term) {
                    // Handle case where $existing_term is an array instead of a WP_Term object
                    $term_name = is_object($existing_term) ? $existing_term->name : (isset($existing_term['name']) ? $existing_term['name'] : '');
                    $term_slug = is_object($existing_term) ? $existing_term->slug : (isset($existing_term['slug']) ? $existing_term['slug'] : '');
                    $term_id = is_object($existing_term) ? $existing_term->term_id : (isset($existing_term['term_id']) ? $existing_term['term_id'] : 0);

                    if ($term_name && $term_name !== $display_value && sanitize_title($term_name) === $sanitized_term_slug) {
                        wp_delete_term($term_id, $taxonomy);
                        $log .= "  Deleted conflicting term: $term_name (Slug: $term_slug)\n";
                    }
                }

                // Create or get term
                $term = get_term_by('name', $display_value, $taxonomy);
                if (!$term || is_wp_error($term)) {
                    $term_data = wp_insert_term($display_value, $taxonomy, [
                        'slug' => $sanitized_term_slug,
                    ]);
                    if (is_wp_error($term_data)) {
                        $log .= "  Failed to create term '$display_value' for taxonomy '$taxonomy': " . $term_data->get_error_message() . "\n";
                        continue;
                    }
                    $term = get_term($term_data['term_id'], $taxonomy);
                    $log .= "  Created new term: $display_value (ID: {$term->term_id}, Slug: {$term->slug})\n";
                } else {
                    $log .= "  Found existing term: $display_value (ID: {$term->term_id}, Slug: {$term->slug})\n";
                }

                if ($term && !is_wp_error($term)) {
                    wp_set_object_terms($parent_id, (int)$term->term_id, $taxonomy, true);
                    $attribute_terms_map[$taxonomy] = $attribute_terms_map[$taxonomy] ?? [];
                    $attribute_terms_map[$taxonomy][$display_value] = (int)$term->term_id;
                    $variation_attributes[$taxonomy] = $display_value;

                    // Create attribute object
                    $attribute = new WC_Product_Attribute();
                    $attribute->set_id($attribute_id);
                    $attribute->set_name($taxonomy);
                    $attribute->set_position(0);
                    $attribute->set_visible(true);
                    $attribute->set_variation(true);
                    $attribute->set_options(array_values($attribute_terms_map[$taxonomy]));
                    $wc_attributes[$taxonomy] = $attribute;
                }
            }

            // Update parent product attributes
            $parent_attributes = $wc_product->get_attributes();
            foreach ($wc_attributes as $taxonomy => $new_attr) {
                if (isset($parent_attributes[$taxonomy])) {
                    $existing_options = $parent_attributes[$taxonomy]->get_options();
                    $merged_options = array_unique(array_merge($existing_options, $new_attr->get_options()));
                    $new_attr->set_options($merged_options);
                }
                $parent_attributes[$taxonomy] = $new_attr;
            }
            $wc_product->set_attributes($parent_attributes);
            $wc_product->save();

            // Create variation if attributes exist
            if (!empty($variation_attributes)) {
                $this->createVariation($parent_id, $data, $variation_attributes, $log);
                $log .= "  Created variation with attributes: " . print_r($variation_attributes, true) . "\n";
            }

            // Update attribute lookup table
            if (class_exists('WC_Product_Attribute_Lookup_Data_Store')) {
                $lookup_store = new WC_Product_Attribute_Lookup_Data_Store();
                $lookup_store->delete_product_attributes_lookup_data($parent_id);
                $lookup_store->create_product_attributes_lookup_data($parent_id);
                $log .= "  Rebuilt attribute lookup table for product ID: $parent_id\n";
            } else {
                delete_transient('wc_layered_nav_counts');
                WC_Cache_Helper::get_transient_version('wc_layered_nav_counts', true);
                $log .= "  Cleared attribute nav cache for product ID: $parent_id\n";
            }
        }

        return $parent_id;
    }
    public function createVariation($parentId, $data, $variation_attributes, &$log) 
    {
        $is_in_stock = (isset($data['in_stock']) && $data['in_stock'] === '1');
        $stock_qty = isset($data['StockQty']) ? intval($data['StockQty']) : null;

        $processed_attributes = [];
        foreach ($variation_attributes as $taxonomy => $term_name) {
            $term = get_term_by('name', $term_name, $taxonomy);
            if ($term && !is_wp_error($term)) {
                $processed_attributes[$taxonomy] = $term->slug;
                $log .= "  Setting variation attribute {$taxonomy}: {$term_name} (slug: {$term->slug})\n";
            } else {
                $log .= "  Error: Term '$term_name' not found for taxonomy '$taxonomy'\n";
                continue;
            }
        }

        // Skip if no valid attributes
        if (empty($processed_attributes)) {
            $log .= "  No valid attributes to create variation for product ID: $parentId\n";    
            return false;
        }

        $variation = new WC_Product_Variation();
        $variation->set_parent_id($parentId);
        $variation->set_attributes($processed_attributes);
        $variation->set_regular_price($data['original_price'] ?? '0');
        if (!empty($data['price'])) {
            $variation->set_sale_price($data['price']);
        }
        $sku = isset($data['itemId']) ? $data['itemId'] : '';
        $variation->set_sku($sku);
        $variation->set_manage_stock(true);
        $variation->set_stock_status($is_in_stock ? 'instock' : 'outofstock');
        if ($stock_qty !== null) {
            $variation->set_stock_quantity($stock_qty);
        }

        $variant_json = $data['Variant'] ?? '';
        $variation_image_url = '';
        if (!empty($variant_json)) {
            $attributes_array = json_decode($variant_json, true);
            foreach ($attributes_array as $attr) {
                if (!empty($attr['image'])) {
                    $variation_image_url = $attr['image'];
                    break;
                }
            }
        }
        if (!empty($variation_image_url)) {
            $log .= "  Processing variation image: $variation_image_url\n";
            
            // Check if this image URL is already being used by this variation
            $already_used = $this->isImageUrlAlreadyUsed($variation_image_url, $variation->get_id());
            if ($already_used) {
                $log .= "  Skipped: Image URL already in use for variation SKU: $sku\n";
            } else {
                $image_id = $this->importImage($variation_image_url);
                if (is_wp_error($image_id)) {
                    $log .= "  Variation image import error: " . $image_id->get_error_message() . " (URL: $variation_image_url)\n";
                } elseif ($image_id) {
                    // Check if this image is already being used by other variations/products
                    $used_by_others = $this->isImageAlreadyUsed($image_id, $variation->get_id());
                    if ($used_by_others) {
                        $log .= "  Using existing variation image (ID: $image_id) already used by " . count($used_by_others) . " other product(s)\n";
                    } else {
                        $log .= "  Using existing variation image (ID: $image_id) not used by other products\n";
                    }
                    $variation->set_image_id($image_id);
                    $log .= "  Set variation image ID: $image_id\n";
                    
                    // Also update the parent variable product's featured image
                    $parent_id = $variation->get_parent_id();
                    if ($parent_id) {
                        // Check if parent already has this image URL
                        $parent_already_has_url = $this->isImageUrlAlreadyUsed($variation_image_url, $parent_id);
                        if ($parent_already_has_url) {
                            $log .= "  Parent variable product already has this image URL, skipping parent update\n";
                        } else {
                            // Check if parent has any featured image
                            $parent_featured_id = get_post_thumbnail_id($parent_id);
                            if ($parent_featured_id) {
                                $parent_featured_url = get_post_meta($parent_featured_id, '_source_url', true);
                                if ($parent_featured_url === $variation_image_url) {
                                    $log .= "  Parent variable product already has this image as featured, skipping\n";
                                } else {
                                    // Only update parent if it doesn't have a featured image from imageUrl
                                    $parent_product = wc_get_product($parent_id);
                                    if ($parent_product && !empty($parent_product->get_meta('_has_imageurl_featured'))) {
                                        $log .= "  Parent variable product has featured image from imageUrl, skipping variation image override\n";
                                    } else {
                                        set_post_thumbnail($parent_id, $image_id);
                                        $log .= "  Updated parent variable product featured image (ID: $parent_id) with variation image\n";
                                    }
                                }
                            } else {
                                // Only set parent featured image if it doesn't have one from imageUrl
                                $parent_product = wc_get_product($parent_id);
                                if ($parent_product && !empty($parent_product->get_meta('_has_imageurl_featured'))) {
                                    $log .= "  Parent variable product has featured image from imageUrl, skipping variation image override\n";
                                } else {
                                    set_post_thumbnail($parent_id, $image_id);
                                    $log .= "  Set parent variable product featured image (ID: $parent_id) with variation image\n";
                                }
                            }
                        }
                    }
                } else {
                    $log .= "  Variation image import failed, no image ID returned (URL: $variation_image_url)\n";
                }
            }
        }
        $variation->save();

        // Sync attributes with variation
        foreach ($variation_attributes as $taxonomy => $term_name) {
            $term = get_term_by('name', $term_name, $taxonomy);
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($variation->get_id(), $term->term_id, $taxonomy);
                $log .= "  Synced variation attribute: $taxonomy = $term_name (ID: {$term->term_id})\n";
            } else {
                $log .= "  Error syncing variation attribute: $taxonomy = $term_name (Term not found)\n";
            }
        }

        // Update attribute lookup for variation
        if (class_exists('WC_Product_Attribute_Lookup_Data_Store')) {
            $lookup_store = new WC_Product_Attribute_Lookup_Data_Store();
            $lookup_store->delete_product_attributes_lookup_data($parentId);
            $lookup_store->create_product_attributes_lookup_data($parentId);
            $lookup_store->create_product_attributes_lookup_data($variation->get_id());
            $log .= "  Rebuilt attribute lookup table for variation ID: {$variation->get_id()}\n";
        }

        if (function_exists('update_field')) {
            update_field('s_coin_value', $data['Scoin'] ?? '', $variation->get_id());
        } else {
            update_post_meta($variation->get_id(), 's_coin_value', $data['Scoin'] ?? '');
        }
        update_post_meta($variation->get_id(), '_sales_quantity', $data['sale_quantity'] ?? '');
        $log .= "Created variation for " . (isset($data['name']) ? $data['name'] : '') . " with ID: {$variation->get_id()}\n\n";
        return $variation->get_id();
    }

    public function assignBrand($productId, $brandName, &$log)
    {
        $this->ensureProductBrandTaxonomy();    
        $brand_term = get_term_by('name', $brandName, 'product_brand');
        if (!$brand_term) {
            $brand_term = wp_insert_term($brandName, 'product_brand');
            if (!is_wp_error($brand_term)) {
                $brand_term = get_term($brand_term['term_id'], 'product_brand');
            }
        }
        if ($brand_term && !is_wp_error($brand_term)) {
            wp_set_object_terms($productId, $brand_term->term_id, 'product_brand', false);
            $log .= "  Assigned brand: $brandName\n";
            return true;
        }
        return false;
    }

    public function assignCategory($productId, $categoryPath, &$log)
    {
        $category_levels = array_map('trim', explode(',', $categoryPath));
        $category_levels = array_filter($category_levels);
        if (empty($category_levels)) {
            $log .= "  Empty category path provided\n";
            return false;
        }
        $parent_id = 0;
        $all_term_ids = [];
        foreach ($category_levels as $category_name) {
            $args = [
                'taxonomy'   => 'product_cat',
                'name'       => $category_name,
                'parent'     => $parent_id,
                'hide_empty' => false,
            ];
            $existing_category = get_terms($args);
            if (!empty($existing_category) && !is_wp_error($existing_category)) {
                $category_term = $existing_category[0];
                $parent_id = $category_term->term_id;
                $all_term_ids[] = $category_term->term_id;
                $log .= "  Found existing category: $category_name\n";
            } else {
                $new_category = wp_insert_term($category_name, 'product_cat', ['parent' => $parent_id]);
                if (is_wp_error($new_category)) {
                    $log .= "  Error creating category '$category_name': " . $new_category->get_error_message() . "\n";
                    return false;
                } else {
                    $parent_id = $new_category['term_id'];
                    $all_term_ids[] = $new_category['term_id'];
                    $log .= "  Created new category: $category_name\n";
                }
            }
        }
        if (!empty($all_term_ids)) {
            wp_set_object_terms($productId, $all_term_ids, 'product_cat', false);
            $log .= "  Assigned product to category hierarchy: " . implode(' > ', $category_levels) . "\n";
            return true;
        }
        return false;
    }

    public function importImage($image_url)
    {
        if (empty($image_url)) {
            return false;
        }
        $image_url = trim($image_url);
        if (empty($image_url)) {
            return false;
        }

        // Check if the image already exists by URL
        $existing_attachment = $this->getAttachmentByUrl($image_url);
        if ($existing_attachment) {
            error_log("[ProductImport] Using existing image for URL: $image_url (ID: $existing_attachment)");
            return $existing_attachment;
        }

        // Check if the image already exists by filename
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        if (!empty($filename)) {
            $existing_by_filename = $this->getAttachmentByFilename($filename);
            if ($existing_by_filename) {
                error_log("[ProductImport] Using existing image by filename: $filename (ID: $existing_by_filename)");
                return $existing_by_filename;
            }
        }

        // Check if the image already exists by checking attachment metadata
        $existing_by_metadata = $this->getAttachmentByMetadata($image_url);
        if ($existing_by_metadata) {
            error_log("[ProductImport] Using existing image by metadata for URL: $image_url (ID: $existing_by_metadata)");
            return $existing_by_metadata;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Increase HTTP request timeout
        add_filter('http_request_timeout', function () {
            return 120;
        });

        // Suppress EXIF warnings
        set_error_handler(function ($severity, $message, $file, $line) {
            if (strpos($message, 'exif_read_data') !== false) {
                return true; // Suppress EXIF-related warnings
            }
            return false;
        }, E_WARNING);

        // Attempt to sideload the image
        $upload = media_sideload_image($image_url, 0, '', 'id');

        // Restore default error handler
        restore_error_handler();

        if (is_wp_error($upload)) {
            error_log("[ProductImport] Image import error for URL $image_url: " . $upload->get_error_message());
            return $upload;
        }

        if (is_numeric($upload)) {
            // Store the source URL as metadata for future reference
            update_post_meta($upload, '_source_url', $image_url);
            update_post_meta($upload, '_import_date', current_time('mysql'));
            error_log("[ProductImport] Successfully imported new image for URL: $image_url (ID: $upload)");
            return $upload;
        }

        if (is_string($upload)) {
            global $wpdb;
            $attachment = $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM $wpdb->posts WHERE guid=%s",
                $upload
            ));
            if (!empty($attachment)) {
                // Store the source URL as metadata for future reference
                update_post_meta($attachment[0], '_source_url', $image_url);
                update_post_meta($attachment[0], '_import_date', current_time('mysql'));
                error_log("[ProductImport] Successfully imported new image for URL: $image_url (ID: {$attachment[0]})");
                return $attachment[0];
            }
        }

        // Fallback: Check for the most recent attachment
        $args = [
            'post_type'   => 'attachment',
            'numberposts' => 1,
            'post_status' => 'any',
            'orderby'     => 'date',
            'order'       => 'DESC',
        ];
        $attachments = get_posts($args);
        if (!empty($attachments)) {
            error_log("[ProductImport] Using fallback attachment for URL: $image_url (ID: {$attachments[0]->ID})");
            return $attachments[0]->ID;
        }

        error_log("[ProductImport] Image import failed, no attachment ID returned for URL $image_url");
        return false;
    }

    public function getAttachmentByUrl($url)
    {
        global $wpdb;
        $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url));
        if (!empty($attachment)) {
            return $attachment[0];
        }
        return false;
    }

    public function getAttachmentByFilename($filename)
    {
        global $wpdb;
        
        // Clean the filename
        $filename = sanitize_file_name($filename);
        
        // Search in post content and meta
        $attachment = $wpdb->get_var($wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p 
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'attachment' 
             AND (p.post_title LIKE %s OR p.post_content LIKE %s OR pm.meta_value LIKE %s)",
            '%' . $wpdb->esc_like($filename) . '%',
            '%' . $wpdb->esc_like($filename) . '%',
            '%' . $wpdb->esc_like($filename) . '%'
        ));
        
        if ($attachment) {
            return $attachment;
        }
        
        return false;
    }

    public function getAttachmentByMetadata($image_url)
    {
        global $wpdb;
        
        // Search for attachments with this URL in their metadata
        $attachment = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_wp_attached_file' 
             AND meta_value LIKE %s",
            '%' . $wpdb->esc_like(basename(parse_url($image_url, PHP_URL_PATH))) . '%'
        ));
        
        if ($attachment) {
            return $attachment;
        }
        
        // Also check for source URL in custom fields
        $attachment = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key IN ('_source_url', '_original_url', '_import_url') 
             AND meta_value = %s",
            $image_url
        ));
        
        if ($attachment) {
            return $attachment;
        }
        
        return false;
    }

    public function isImageAlreadyUsed($image_id, $exclude_product_id = null)
    {
        global $wpdb;
        
        // Check if this image is already set as featured image for any product
        $query = "SELECT post_id FROM {$wpdb->postmeta} 
                  WHERE meta_key = '_thumbnail_id' 
                  AND meta_value = %d";
        $params = [$image_id];
        
        if ($exclude_product_id) {
            $query .= " AND post_id != %d";
            $params[] = $exclude_product_id;
        }
        
        $used_by = $wpdb->get_col($wpdb->prepare($query, $params));
        
        if (!empty($used_by)) {
            return $used_by;
        }
        
        return false;
    }

    public function ensureProductBrandTaxonomy()
    {
        if (!taxonomy_exists('product_brand')) {
            register_taxonomy(
                'product_brand',
                'product',
                [
                    'hierarchical'      => true,
                    'labels'            => [
                        'name'              => 'Brands',
                        'singular_name'     => 'Brand',
                        'menu_name'         => 'Brands',
                        'all_items'         => 'All Brands',
                        'edit_item'         => 'Edit Brand',
                        'update_item'       => 'Update Brand',
                        'add_new_item'      => 'Add New Brand',
                        'new_item_name'     => 'New Brand Name',
                        'parent_item'       => 'Parent Brand',
                        'parent_item_colon' => 'Parent Brand:',
                        'search_items'      => 'Search Brands',
                        'popular_items'     => 'Popular Brands',
                        'not_found'         => 'No brands found',
                        'not_found_in_trash'=> 'No brands found in trash',
                    ],
                    'show_ui'           => true,
                    'show_admin_column' => true,
                    'query_var'         => true,
                    'rewrite'           => ['slug' => 'brand'],
                    'capabilities'      => [
                        'manage_terms' => 'manage_product_terms',
                        'edit_terms'   => 'edit_product_terms',
                        'delete_terms' => 'delete_product_terms',
                        'assign_terms' => 'assign_product_terms',
                    ],
                ]
            );
        }
    }

    private function smartShortenAttributeName($label, &$log = null)
    {
        $max_length = 28; // Maximum length for WooCommerce taxonomy slug
        $label = trim($label);
        if ($log !== null) {
            $log .= "  Original attribute name: $label\n";
        }
        // If already short enough, return sanitized version
        if (strlen($label) <= $max_length) {
            $sanitized_slug = wc_sanitize_taxonomy_name($label);
            if ($log !== null) {
                $log .= "  Label short enough, no changes needed\n";
            }
            return $sanitized_slug;
        }

        // Define abbreviation rules
        $abbreviations = [
            // General product terms
            'Bottle'            => 'Btl',
            'Ounce'            => 'oz',
            'Sense Cup'        => 'SCup',
            'Ace Bottle'       => 'ABtl',
            'Coffee Cup'       => 'CCup',
            'Montigo'          => 'mtg',
            
            // Disney themes
            'Disney Pixar'     => 'DPix',
            'Toy Story'        => 'TS',
            'Monster Inc'      => 'MI',
            'Disney Cars'      => 'DCars',
            'Disney Frozen'    => 'DFrozen',
            'Disney Mickey'    => 'DMickey',
            'Disney Minnie'    => 'DMinnie',
            'Disney Marvel'    => 'DMarvel',
            'Disney Princess'  => 'DPrncs',
            'Princess'         => 'Prncs',
            
            // Character-color combinations
            'Ariel Green'      => 'ArielG',
            'Snow White'       => 'SnowW',
            'Cinderella Blue'  => 'CinderB',
            'Cinderella Pink'  => 'CinderPi',
            'Elsa Blue'        => 'ElsaB',
            'Elsa Pink'        => 'ElsaPi',
            'Anna Blue'        => 'AnnaB',
            'Anna Pink'        => 'AnnaPi',
            'Rapunzel Purple'  => 'RapunzPu',
            'Rapunzel Pink'    => 'RapunzPi',
            'Belle Yellow'     => 'BelleY',
            'Aurora Pink'      => 'AuroraPi',
            'Aurora Blue'      => 'AuroraB',
            'Tiana Green'      => 'TianaG',
            'Moana Orange'     => 'MoanaO',
            'Mulan Red'        => 'MulanR',
            'Pocahontas Brown' => 'PocahB',
            'Jasmine Blue'     => 'JasmineB',
            'Jasmine Green'    => 'JasmineG',
            'Merida Green'     => 'MeridaG',
            'Merida Red'       => 'MeridaR',
            'Tinkerbell Green' => 'TinkerG',
            'Tinkerbell Yellow' => 'TinkerY',
            'Tinkerbell Pink'  => 'TinkerPi',
            'Buzz Lightyear'   => 'Buzz',
            'Aliens'           => 'Alien',
            'Sully'            => 'Sully',
        ];
        

        // Sort abbreviations by key length (longest to shortest)
        uksort($abbreviations, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        // Apply abbreviations (case-insensitive)
        $shortened_label = $label;
        foreach ($abbreviations as $full => $abbr) {
            $new_label = str_ireplace($full, $abbr, $shortened_label);
            if ($new_label !== $shortened_label && $log !== null) {
                $log .= "  Applied abbreviation: '$full' -> '$abbr'\n";
            }
            $shortened_label = $new_label;
        }

        // Join words with dashes for slug compatibility
        $shortened_label = implode('-', array_filter(explode(' ', trim($shortened_label))));
        if ($log !== null) {
            $log .= "  After joining words: $shortened_label\n";
        }

        // Sanitize and ensure unique slug
        global $wpdb;
        $sanitized_slug = wc_sanitize_taxonomy_name($shortened_label);
        $base_slug = $sanitized_slug;
        $counter = 1;
        while ($wpdb->get_var($wpdb->prepare("SELECT term_id FROM {$wpdb->terms} WHERE slug = %s", $sanitized_slug))) {
            $sanitized_slug = substr($base_slug, 0, $max_length - 2 - strlen((string)$counter)) . '-' . $counter;
            $counter++;
        }

        // Truncate if still too long
        if (strlen($sanitized_slug) > $max_length) {
            $sanitized_slug = substr($sanitized_slug, 0, $max_length);
            if ($log !== null) {
                $log .= "  Truncated to $max_length characters: $sanitized_slug\n";
            }
        }

        if ($log !== null) {
            $log .= "  Final slug: $sanitized_slug\n";
        }
        return $sanitized_slug;
    }

    public function isImageUrlAlreadyUsed($image_url, $product_id = null)
    {
        global $wpdb;
        
        // Check if this image URL is already stored in the product's metadata
        $query = "SELECT post_id FROM {$wpdb->postmeta} 
                  WHERE meta_key IN ('_source_url', '_original_url', '_import_url') 
                  AND meta_value = %s";
        $params = [$image_url];
        
        if ($product_id) {
            $query .= " AND post_id = %d";
            $params[] = $product_id;
        }
        
        $used_by = $wpdb->get_col($wpdb->prepare($query, $params));
        
        if (!empty($used_by)) {
            return $used_by;
        }
        
        // Also check if the image is already set as featured image for this product
        if ($product_id) {
            $featured_image_id = get_post_thumbnail_id($product_id);
            if ($featured_image_id) {
                $source_url = get_post_meta($featured_image_id, '_source_url', true);
                if ($source_url === $image_url) {
                    return [$product_id];
                }
            }
        }
        
        return false;
    }

    /**
     * Process image import for a batch of products
     */
    public function processImageImportBatch($rows, &$progress)
    {
        // First, group products by parent ID and collect parent data from variations
        $grouped_products = [];
        $parent_data_from_variations = [];
        
        // First pass: Group products and collect parent data from variations
        foreach ($rows as $data) {
            $sku = isset($data['itemId']) ? trim($data['itemId']) : '';
            $image_url = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
            $variant_json = isset($data['Variant']) ? trim($data['Variant']) : '';
            
            if (empty($sku)) {
                $progress['log'] .= "Skipped: Empty SKU found\n";
                $progress['error_count']++;
                continue;
            }

            $product_id = wc_get_product_id_by_sku($sku);
            if (!$product_id) {
                $progress['log'] .= "Skipped: Product with SKU $sku not found\n";
                $progress['error_count']++;
                continue;
            }

            $product = wc_get_product($product_id);
            if (!$product) {
                $progress['log'] .= "Skipped: Failed to load product with SKU $sku\n";
                $progress['error_count']++;
                continue;
            }

            if ($product->get_type() === 'variation') {
                $parent_id = $product->get_parent_id();
                if (!isset($grouped_products[$parent_id])) {
                    $grouped_products[$parent_id] = [];
                    // Get parent product
                    $parent_product = wc_get_product($parent_id);
                    if ($parent_product) {
                        // Store parent info for later processing
                        $parent_data_from_variations[$parent_id] = [
                            'product' => $parent_product,
                            'image_url' => $image_url, // Use variation's imageUrl for parent
                            'has_variations' => true
                        ];
                    }
                }
                $grouped_products[$parent_id][] = ['product' => $product, 'data' => $data];
            } else {
                // Handle non-variation products normally
                $grouped_products['single_' . $product_id] = [['product' => $product, 'data' => $data]];
            }
        }

        // Now process each group
        foreach ($grouped_products as $group_id => $products) {
            try {
                // If this is a variable product group
                if (strpos($group_id, 'single_') === false && isset($parent_data_from_variations[$group_id])) {
                    $parent_info = $parent_data_from_variations[$group_id];
                    $parent_product = $parent_info['product'];
                    $parent_sku = $parent_product->get_sku();
                    $parent_image_url = $parent_info['image_url'];
                    
                    // Process parent first using the imageUrl from variation data
                    if (!empty($parent_image_url)) {
                        $progress['log'] .= "\nProcessing variable product parent: $parent_sku (from variation data)\n";
                        error_log("[ImageImport] Processing variable product parent: $parent_sku (from variation data)");
                        
                        // Process parent's image
                        $already_used = $this->isImageUrlAlreadyUsed($parent_image_url, $group_id);
                        if ($already_used) {
                            $progress['log'] .= "Parent: Skipped - Image URL already in use for parent SKU: $parent_sku\n";
                            error_log("[ImageImport] Parent: Skipped - Image URL already in use for parent SKU: $parent_sku");
                        } else {
                            $parent_image_id = $this->importImage($parent_image_url);
                            if (!is_wp_error($parent_image_id) && $parent_image_id) {
                                set_post_thumbnail($group_id, $parent_image_id);
                                update_post_meta($group_id, '_has_imageurl_featured', 'yes');
                                $progress['log'] .= "Parent: Set featured image for parent SKU: $parent_sku\n";
                                error_log("[ImageImport] Parent: Set featured image for parent SKU: $parent_sku");
                            }
                        }
                    } else {
                        $progress['log'] .= "\nProcessing variable product parent: $parent_sku (no imageUrl in variation data)\n";
                        error_log("[ImageImport] Processing variable product parent: $parent_sku (no imageUrl in variation data)");
                    }
                }

                // Now process the actual product(s) in this group
                foreach ($products as $item) {
                    $product = $item['product'];
                    $data = $item['data'];
                    $sku = $product->get_sku();
                    $product_id = $product->get_id();

                    $this->processSingleProductImage($product, $data, $progress);
                }

            } catch (Exception $e) {
                $error_message = "ERROR: " . $e->getMessage();
                $progress['log'] .= "Error processing product group: $error_message\n";
                error_log("[ImageImport] Error processing product group: $error_message");
                $progress['error_count']++;
            }
        }
    }

    /**
     * Process image import for a single product
     */
    private function processSingleProductImage($product, $data, &$progress)
    {
        $sku = $product->get_sku();
        $product_id = $product->get_id();
        $image_url = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
        $variant_json = isset($data['Variant']) ? trim($data['Variant']) : '';

        try {
            // Check if this is a variation product
            if ($product->get_type() === 'variation') {
                $this->processVariationImage($product, $data, $progress);
            } else {
                $this->processSimpleProductImage($product, $data, $progress);
            }
        } catch (Exception $e) {
            $error_message = "ERROR: " . $e->getMessage();
            $progress['log'] .= "Error processing image for SKU: $sku | $error_message\n";
            error_log("[ImageImport] Error processing image for SKU: $sku | $error_message");
            $progress['error_count']++;
        }
    }

    /**
     * Process image import for a variation product
     */
    private function processVariationImage($product, $data, &$progress)
    {
        $sku = $product->get_sku();
        $product_id = $product->get_id();
        $image_url = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
        $variant_json = isset($data['Variant']) ? trim($data['Variant']) : '';

        $progress['log'] .= "Processing variation product with SKU: $sku\n";
        
        // For variations, check the Variant column for images
        if (!empty($variant_json)) {
            $attributes_array = json_decode($variant_json, true);
            if (is_array($attributes_array)) {
                $variation_image_url = '';
                
                // Find image from variant attributes
                foreach ($attributes_array as $attr) {
                    if (!empty($attr['image'])) {
                        $variation_image_url = $attr['image'];
                        break;
                    }
                }
                
                // Use main image if no variant image found
                if (empty($variation_image_url) && !empty($image_url)) {
                    $variation_image_url = $image_url;
                }
                
                if (!empty($variation_image_url)) {
                    $this->setProductImage($product, $variation_image_url, $progress, 'variation');
                } else {
                    $progress['log'] .= "No image URL found in variant data for SKU: $sku\n";
                    $progress['error_count']++;
                }
            } else {
                $progress['log'] .= "Invalid variant JSON format for SKU: $sku\n";
                $progress['error_count']++;
            }
        } else {
            // No variant data, try main image URL
            if (!empty($image_url)) {
                $this->setProductImage($product, $image_url, $progress, 'variation');
            } else {
                throw new Exception('No image URL provided for variation');
            }
        }
    }

    /**
     * Process image import for a simple/variable product
     */
    private function processSimpleProductImage($product, $data, &$progress)
    {
        $sku = $product->get_sku();
        $product_id = $product->get_id();
        $image_url = isset($data['imageUrl']) ? trim($data['imageUrl']) : '';
        $variant_json = isset($data['Variant']) ? trim($data['Variant']) : '';

        // Handle simple/variable product images
        $image_url_to_use = '';
        
        // First check if there's a main image URL
        if (!empty($image_url)) {
            $image_url_to_use = $image_url;
        }
        
        // If product has variants, check Variant column for images
        if (!empty($variant_json)) {
            $attributes_array = json_decode($variant_json, true);
            if (is_array($attributes_array)) {
                // Find image from variant attributes
                foreach ($attributes_array as $attr) {
                    if (!empty($attr['image'])) {
                        $image_url_to_use = $attr['image'];
                        break;
                    }
                }
            }
        }
        
        if (!empty($image_url_to_use)) {
            $this->setProductImage($product, $image_url_to_use, $progress, 'simple');
        } else {
            throw new Exception('No image URL found for product');
        }
    }

    /**
     * Set image for a product (variation or simple)
     */
    private function setProductImage($product, $image_url, &$progress, $type = 'simple')
    {
        $sku = $product->get_sku();
        $product_id = $product->get_id();

        // Check if this image URL is already being used by this product
        $already_used = $this->isImageUrlAlreadyUsed($image_url, $product_id);
        if ($already_used) {
            $progress['log'] .= "Skipped: Image URL already in use for " . ($type === 'variation' ? 'variation' : 'product') . " SKU: $sku\n";
            error_log("[ImageImport] Skipped: Image URL already in use for " . ($type === 'variation' ? 'variation' : 'product') . " SKU: $sku");
            $progress['success_count']++; // Count as success since it's already correct
            return;
        }

        $image_id = $this->importImage($image_url);
        if (is_wp_error($image_id)) {
            throw new Exception(($type === 'variation' ? 'Variation' : 'Product') . ' image import failed: ' . $image_id->get_error_message());
        }

        if ($image_id) {
            if ($type === 'variation') {
                // Set as variation image
                $product->set_image_id($image_id);
                $product->save();
                $progress['success_count']++;
                $progress['log'] .= "Success: Updated variation image for SKU: $sku (Image ID: $image_id)\n";
                error_log("[ImageImport] Success: Updated variation image for SKU: $sku (Image ID: $image_id)");
                
                // Only update parent if it doesn't already have a featured image from imageUrl processing
                $parent_id = $product->get_parent_id();
                if ($parent_id) {
                    $parent_product = wc_get_product($parent_id);
                    if ($parent_product && empty($parent_product->get_meta('_has_imageurl_featured'))) {
                        $this->updateParentFeaturedImage($product, $image_id, $image_url, $progress);
                    } else {
                        $progress['log'] .= "Parent already has imageUrl featured image, skipping variation override\n";
                        error_log("[ImageImport] Parent already has imageUrl featured image, skipping variation override");
                    }
                }
            } else {
                // Set as featured image
                set_post_thumbnail($product_id, $image_id);
                $progress['success_count']++;
                $progress['log'] .= "Success: Updated image for product SKU: $sku (Image ID: $image_id)\n";
                error_log("[ImageImport] Success: Updated image for product SKU: $sku (Image ID: $image_id)");
                
                // If this is a variable product, also update the parent's featured image
                if ($product->get_type() === 'variable') {
                    $this->updateParentFeaturedImage($product, $image_id, $image_url, $progress);
                }
            }
        } else {
            throw new Exception(($type === 'variation' ? 'Variation' : 'Product') . ' image import returned no image ID');
        }
    }

    /**
     * Update parent variable product's featured image
     */
    private function updateParentFeaturedImage($product, $image_id, $image_url, &$progress)
    {
        $parent_id = $product->get_parent_id();
        if (!$parent_id) {
            return;
        }

        // Check if parent already has this image URL
        $parent_already_has_url = $this->isImageUrlAlreadyUsed($image_url, $parent_id);
        if ($parent_already_has_url) {
            $progress['log'] .= "Parent variable product already has this image URL, skipping parent update\n";
            error_log("[ImageImport] Parent variable product already has this image URL, skipping parent update");
            return;
        }

        // Check if parent has any featured image
        $parent_featured_id = get_post_thumbnail_id($parent_id);
        if ($parent_featured_id) {
            $parent_featured_url = get_post_meta($parent_featured_id, '_source_url', true);
            if ($parent_featured_url === $image_url) {
                $progress['log'] .= "Parent variable product already has this image as featured, skipping\n";
                error_log("[ImageImport] Parent variable product already has this image as featured, skipping");
                return;
            }
        }

        // Only update parent if it doesn't have a featured image from imageUrl
        $parent_product = wc_get_product($parent_id);
        if ($parent_product && !empty($parent_product->get_meta('_has_imageurl_featured'))) {
            $progress['log'] .= "Variable product has featured image from imageUrl, skipping variation image override\n";
            error_log("[ImageImport] Variable product has featured image from imageUrl, skipping variation image override");
            return;
        }

        // Check if parent already has a featured image set from the main imageUrl processing
        if ($parent_featured_id) {
            $progress['log'] .= "Parent already has a featured image, skipping variation override\n";
            error_log("[ImageImport] Parent already has a featured image, skipping variation override");
            return;
        }

        set_post_thumbnail($parent_id, $image_id);
        $progress['log'] .= "Success: Updated parent variable product featured image (ID: $parent_id) with variation image\n";
        error_log("[ImageImport] Success: Updated parent variable product featured image (ID: $parent_id) with variation image");
    }

    // ========================================
    // PROGRESS TRACKING DATABASE FUNCTIONS
    // ========================================

    /**
     * Create the progress tracking table
     */
    public static function createProgressTable()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            import_id varchar(255) NOT NULL,
            processed int(11) DEFAULT 0,
            success_count int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            total_rows int(11) DEFAULT 0,
            current_batch int(11) DEFAULT 0,
            total_batches int(11) DEFAULT 0,
            operation_type varchar(50) DEFAULT 'import',
            processing_mode varchar(50) DEFAULT 'background',
            status varchar(50) DEFAULT 'running',
            log longtext,
            last_activity timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            estimated_completion timestamp NULL,
            PRIMARY KEY (id),
            UNIQUE KEY import_id (import_id),
            KEY status (status),
            KEY last_activity (last_activity)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('[ProductImportModel] Progress table created/updated: ' . $table_name);
    }

    /**
     * Initialize progress in database
     */
    public static function initializeProgress($import_id, $initial_data)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        // Ensure table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            self::createProgressTable();
        }
        
        $data = array_merge($initial_data, [
            'import_id' => $import_id,
            'last_activity' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ]);
        
        $wpdb->replace($table_name, $data);
        
        // Also set transient for backward compatibility
        set_transient($import_id . '_progress', $initial_data, 60 * 60);
        
        error_log('[ProductImportModel] Progress initialized for import_id: ' . $import_id);
    }

    /**
     * Update progress in database
     */
    public static function updateProgress($import_id, $progress_data)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        $data = [
            'import_id' => $import_id,
            'processed' => $progress_data['processed'] ?? 0,
            'success_count' => $progress_data['success_count'] ?? 0,
            'error_count' => $progress_data['error_count'] ?? 0,
            'total_rows' => $progress_data['total_rows'] ?? 0,
            'current_batch' => $progress_data['current_batch'] ?? 0,
            'total_batches' => $progress_data['total_batches'] ?? 0,
            'operation_type' => $progress_data['operation_type'] ?? 'import',
            'processing_mode' => $progress_data['processing_mode'] ?? 'background',
            'status' => $progress_data['status'] ?? 'running',
            'log' => $progress_data['log'] ?? '',
            'last_activity' => current_time('mysql')
        ];
        
        $wpdb->replace($table_name, $data);
        
        // Also update transient for backward compatibility
        set_transient($import_id . '_progress', $progress_data, 60 * 60);
    }

    /**
     * Get stored progress from database
     */
    public static function getStoredProgress($import_id)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        // Try database first
        $result = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE import_id = %s", $import_id),
            ARRAY_A
        );
        
        if ($result) {
            // Convert timestamp strings to integers for compatibility
            $result['last_activity'] = strtotime($result['last_activity']);
            $result['created_at'] = strtotime($result['created_at']);
            return $result;
        }
        
        // Fallback to transient
        $transient_data = get_transient($import_id . '_progress');
        if ($transient_data) {
            return $transient_data;
        }
        
        return null;
    }

    /**
     * Get enhanced progress with estimates and status
     */
    public static function getEnhancedProgress($import_id)
    {
        $progress = self::getStoredProgress($import_id);
        
        if (!$progress) {
            return null;
        }

        // Calculate estimated completion time
        if (!$progress['done'] && $progress['processed'] > 0 && $progress['total_rows'] > 0) {
            $elapsed_time = time() - ($progress['last_activity'] ?? time());
            $avg_time_per_row = $elapsed_time / $progress['processed'];
            $remaining_rows = $progress['total_rows'] - $progress['processed'];
            $estimated_remaining = $remaining_rows * $avg_time_per_row;
            
            $progress['estimated_completion'] = time() + $estimated_remaining;
            $progress['estimated_remaining_seconds'] = $estimated_remaining;
        }

        // Check for stuck imports (no activity for more than 5 minutes)
        if (!$progress['done'] && isset($progress['last_activity'])) {
            $time_since_activity = time() - $progress['last_activity'];
            if ($time_since_activity > 300) { // 5 minutes
                $progress['status'] = 'stuck';
                $progress['log'] .= "\nWarning: Import appears to be stuck (no activity for " . round($time_since_activity / 60, 1) . " minutes)\n";
            }
        }

        // Add WordPress Cron status for background imports
        if ($progress['processing_mode'] === 'background') {
            // Check if there are any scheduled cron events for this import
            $cron_jobs = _get_cron_array();
            $scheduled_batches = 0;
            
            foreach ($cron_jobs as $timestamp => $cron) {
                if (isset($cron['senheng_import_batch_event'])) {
                    foreach ($cron['senheng_import_batch_event'] as $key => $event) {
                        if (isset($event['args'][0]) && $event['args'][0] === $import_id) {
                            $scheduled_batches++;
                        }
                    }
                }
            }
            
            $progress['cron_status'] = [
                'scheduled_batches' => $scheduled_batches,
                'current_status' => $progress['status'] ?? 'unknown'
            ];
        }

        return $progress;
    }

    /**
     * Clean up old progress data
     */
    public static function cleanupOldProgress($days_old = 7)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            )
        );
        
        error_log("[ProductImportModel] Cleaned up $deleted old progress records");
        
        return $deleted;
    }

    /**
     * Get all active imports
     */
    public static function getActiveImports()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status IN ('scheduled', 'running') ORDER BY created_at DESC",
            ARRAY_A
        );
    }

    /**
     * Get import statistics
     */
    public static function getImportStatistics()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'senheng_import_progress';
        
        $stats = [
            'total_imports' => 0,
            'completed_imports' => 0,
            'failed_imports' => 0,
            'active_imports' => 0,
            'total_products_processed' => 0,
            'total_products_success' => 0,
            'total_products_error' => 0
        ];
        
        // Get basic counts
        $stats['total_imports'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $stats['completed_imports'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
        $stats['active_imports'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('scheduled', 'running')");
        
        // Get product counts
        $stats['total_products_processed'] = $wpdb->get_var("SELECT SUM(processed) FROM $table_name");
        $stats['total_products_success'] = $wpdb->get_var("SELECT SUM(success_count) FROM $table_name");
        $stats['total_products_error'] = $wpdb->get_var("SELECT SUM(error_count) FROM $table_name");
        
        return $stats;
    }
}
