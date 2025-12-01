<?php
namespace SenhengCore\App\Services\Import;

use SenhengCore\App\Services\Import\Logger;

class AttributeHelper
{
    /**
     * Resolve variant keys and values into WooCommerce attribute format
     * 
     * @param array $keys   Array of attribute labels (e.g., ["Color", "Size"])
     * @param array $vals   Array of attribute values (e.g., ["Starlight", "40mm"])
     * @return array        [$attrs for variation, $map for parent]
     */
    /**
     * Resolve attributes from keys and values arrays
     * 
     * @param array $keys  Attribute names (e.g., ["Color", "Size"])
     * @param array $vals  Attribute values (e.g., ["Starlight", "40mm S/M"])
     * @return array       [$variationAttrs, $parentAttrMap]
     */
    public static function resolveAttributes(array $keys, array $vals): array
    {
        $variationAttrs = []; // for variation product (taxonomy => term_slug)
        $parentAttrMap  = []; // for parent variable attributes (taxonomy => [term_slugs])

        $pairs = array_values(array_filter(array_map(function($k, $v) {
            $k = trim((string)$k); 
            $v = trim((string)$v);
            if ($k === '' || $v === '') return null;
            return [$k, $v];
        }, $keys, $vals)));

        foreach ($pairs as [$label, $value]) {
            [$taxonomy, $termSlug] = self::ensureGlobalAttribute($label, $value);
            
            // For variation products, use term slug as the attribute value
            $variationAttrs[$taxonomy] = $termSlug;
            
            // For parent variable product, collect all term slugs for each taxonomy
            $parentAttrMap[$taxonomy][] = $termSlug;
            
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Resolved attribute: '$label' => '$value' (taxonomy: $taxonomy, term_slug: $termSlug)"
            );
        }
        
        return [$variationAttrs, $parentAttrMap];
    }

    /**
     * Attach variable attributes to parent product
     * 
     * @param int   $parentId  Parent variable product ID
     * @param array $attrMap   Map of taxonomy => terms array
     */
    public static function attachVariableAttributesToParent(int $parentId, array $attrMap): void
    {
        $product = wc_get_product($parentId);
        if (!$product) return;

        $existing = $product->get_attributes();
        $hasChanges = false;

        foreach ($attrMap as $taxonomy => $terms) {
            $terms = array_unique($terms);
            
            // Convert term slugs/names to term IDs for global attributes
            $termIds = [];
            foreach ($terms as $termSlug) {
                $term = get_term_by('slug', $termSlug, $taxonomy);
                if (!$term) {
                    // Try by name if slug doesn't work
                    $term = get_term_by('name', $termSlug, $taxonomy);
                }
                if ($term && !is_wp_error($term)) {
                    $termIds[] = $term->term_id;
                }
            }
            
            // Merge with existing term IDs if attribute already exists
            if (isset($existing[$taxonomy])) {
                $existingOptions = is_array($existing[$taxonomy]->get_options()) 
                    ? $existing[$taxonomy]->get_options() 
                    : [];
                $termIds = array_unique(array_merge($existingOptions, $termIds));
                
                // Check if the term IDs have actually changed
                sort($existingOptions);
                sort($termIds);
                if ($existingOptions === $termIds) {
                    // No changes needed for this attribute
                    continue;
                }
            }

            // Get the global attribute ID
            $attributeId = wc_attribute_taxonomy_id_by_name($taxonomy);
            
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id($attributeId);
            $attribute->set_name($taxonomy);
            $attribute->set_options($termIds); // Use term IDs for global attributes
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            
            $existing[$taxonomy] = $attribute;
            $hasChanges = true;
            
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Attached global attribute '$taxonomy' to parent product $parentId with term IDs: " . implode(', ', $termIds)
            );
        }

        // Only save if there are actual changes
        if ($hasChanges) {
            $product->set_attributes($existing);
            $product->save();
        }
    }

    /**
     * Ensure global attribute and term exist
     * 
     * @param string $label  Attribute label (e.g., "Color")
     * @param string $value  Term value (e.g., "Package A")
     * @return array         [$taxonomy, $termSlug]
     */
    private static function ensureGlobalAttribute(string $label, string $value): array
    {
        // Use smartShortenAttributeName to handle long attribute names
        $slug = self::smartShortenAttributeName($label);
        $taxonomy = 'pa_' . $slug;

        // Ensure attribute taxonomy exists
        if (!taxonomy_exists($taxonomy)) {
            self::registerGlobalAttribute($label, $slug);
        }

        // First check if term exists by name (exact match)
        $termObj = get_term_by('name', $value, $taxonomy);
        
        if (!$termObj) {
            // Also check by slug in case the term was created with a different name
            $termObj = get_term_by('slug', sanitize_title($value), $taxonomy);
        }
        
        if (!$termObj) {
            // Create new term with the exact value as the name
            $term = wp_insert_term($value, $taxonomy);
            if (is_wp_error($term)) {
                Logger::error(
                    WP_CONTENT_DIR . '/uploads/senheng_import.log',
                    "Failed to create term '$value' for taxonomy '$taxonomy': " . $term->get_error_message()
                );
                // Return sanitized value as fallback
                return [$taxonomy, sanitize_title($value)];
            }
            
            // Get the newly created term object
            $termObj = get_term($term['term_id'], $taxonomy);
            
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Created attribute term: '$value' in taxonomy '$taxonomy' (slug: {$termObj->slug})"
            );
        }

        // Get the term object to ensure we have the correct slug
        // Note: $term is only defined if we created a new term above
        if (isset($term) && is_array($term) && isset($term['term_id'])) {
            $termObj = get_term($term['term_id'], $taxonomy);
        } else {
            // Try to get existing term (this handles cases where term already existed)
            if (!$termObj) {
                $termObj = get_term_by('name', $value, $taxonomy);
                if (!$termObj) {
                    $termObj = get_term_by('slug', sanitize_title($value), $taxonomy);
                }
            }
        }
        
        if ($termObj && !is_wp_error($termObj)) {
            $termSlug = $termObj->slug;
            
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Using term slug '$termSlug' for value '$value' in taxonomy '$taxonomy'"
            );
        } else {
            // Fallback to sanitized value
            $termSlug = sanitize_title($value);
            
            Logger::warning(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Could not find term object for '$value' in '$taxonomy', using fallback slug: '$termSlug'"
            );
        }

        return [$taxonomy, $termSlug];
    }

    /**
     * Smart shortening of attribute names to fit within 32 character limit
     * Based on smartShortenAttributeName from ProductImportModel
     * 
     * @param string $label  Attribute label to shorten
     * @return string        Shortened and sanitized slug
     */
    private static function smartShortenAttributeName(string $label): string
    {
        $max_length = 32; // Maximum length for attribute slug (increased from 28 to 32 as requested)
        $label = trim($label);
        
        Logger::info(
            WP_CONTENT_DIR . '/uploads/senheng_import.log',
            "Original attribute name: $label"
        );
        
        // If already short enough, return sanitized version
        if (strlen($label) <= $max_length) {
            $sanitized_slug = wc_sanitize_taxonomy_name($label);
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Label short enough, no changes needed"
            );
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
            if ($new_label !== $shortened_label) {
                Logger::info(
                    WP_CONTENT_DIR . '/uploads/senheng_import.log',
                    "Applied abbreviation: '$full' -> '$abbr'"
                );
            }
            $shortened_label = $new_label;
        }

        // Join words with dashes for slug compatibility
        $shortened_label = implode('-', array_filter(explode(' ', trim($shortened_label))));
        Logger::info(
            WP_CONTENT_DIR . '/uploads/senheng_import.log',
            "After joining words: $shortened_label"
        );

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
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Truncated to $max_length characters: $sanitized_slug"
            );
        }

        Logger::info(
            WP_CONTENT_DIR . '/uploads/senheng_import.log',
            "Final slug: $sanitized_slug"
        );
        
        return $sanitized_slug;
    }

    /**
     * Register a global WooCommerce attribute
     * 
     * @param string $label  Attribute label
     * @param string $slug   Attribute slug
     */
    private static function registerGlobalAttribute(string $label, string $slug): void
    {
        global $wpdb;
        
        // Check if attribute already exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = %s",
            $slug
        ));
        
        if ($exists) {
            // Ensure taxonomy is registered even if attribute exists
            self::registerTaxonomy($slug);
            return;
        }
        
        // Insert new attribute
        $wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', [
            'attribute_name'    => $slug,
            'attribute_label'   => $label,
            'attribute_type'    => 'select',
            'attribute_orderby' => 'menu_order',
            'attribute_public'  => 0,
        ]);
        
        // Flush attribute taxonomy cache immediately
        delete_transient('wc_attribute_taxonomies');
        wp_cache_delete('woocommerce_attribute_taxonomies', 'woocommerce');
        wc_delete_product_transients();
        
        // Register the taxonomy immediately
        self::registerTaxonomy($slug);
        
        // Fire the action hook
        do_action('woocommerce_attribute_added', $wpdb->insert_id, ['name' => $slug]);
        
        Logger::info(
            WP_CONTENT_DIR . '/uploads/senheng_import.log',
            "Created global attribute: '$label' (taxonomy: pa_$slug)"
        );
    }

    /**
     * Register runtime taxonomy
     */
    private static function registerTaxonomy(string $slug): void
    {
        $taxonomy = 'pa_' . $slug;
        
        // Register runtime taxonomy if it doesn't exist
        if (!taxonomy_exists($taxonomy)) {
            register_taxonomy($taxonomy, 'product', [
                'hierarchical' => false,
                'show_ui'      => false,
                'query_var'    => true,
                'rewrite'      => false,
                'public'       => false,
            ]);
            
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "Registered taxonomy: $taxonomy"
            );
        }
    }

    /**
     * Get all attributes actually used by existing variations under a parent product
     * 
     * @param int $parentId Parent variable product ID
     * @return array Array of taxonomies that are actually used by variations
     */
    public static function getUsedAttributesByVariations(int $parentId): array
    {
        global $wpdb;
        
        // Get all variations under this parent
        $variationIds = $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'product_variation' 
            AND post_parent = %d 
            AND post_status IN ('publish', 'private')",
            $parentId
        ));
        
        if (empty($variationIds)) {
            return [];
        }
        
        $usedTaxonomies = [];
        
        // Check each variation's attributes
        foreach ($variationIds as $variationId) {
            $variation = wc_get_product($variationId);
            if (!$variation) continue;
            
            $variationAttributes = $variation->get_attributes();
            foreach ($variationAttributes as $taxonomy => $value) {
                // Only include non-empty attributes
                if (!empty($value)) {
                    $usedTaxonomies[] = $taxonomy;
                }
            }
        }
        
        return array_unique($usedTaxonomies);
    }

    /**
     * Clean up unused attributes from parent product after variation updates
     * 
     * @param int $parentId Parent variable product ID
     */
    public static function cleanupUnusedAttributes(int $parentId): void
    {
        $product = wc_get_product($parentId);
        if (!$product || $product->get_type() !== 'variable') {
            return;
        }
        
        // Get attributes currently assigned to parent
        $parentAttributes = $product->get_attributes();
        if (empty($parentAttributes)) {
            return;
        }
        
        // Get attributes actually used by variations
        $usedTaxonomies = self::getUsedAttributesByVariations($parentId);
        
        $hasChanges = false;
        $removedAttributes = [];
        
        // Check each parent attribute
        foreach ($parentAttributes as $taxonomy => $attribute) {
            // If this attribute is not used by any variation, remove it
            if (!in_array($taxonomy, $usedTaxonomies)) {
                unset($parentAttributes[$taxonomy]);
                $removedAttributes[] = $taxonomy;
                $hasChanges = true;
            }
        }
        
        // Save changes if any attributes were removed
        if ($hasChanges) {
            $product->set_attributes($parentAttributes);
            $product->save();
            
            // Log the cleanup action
            Logger::info(
                WP_CONTENT_DIR . '/uploads/senheng_import.log',
                "🧹 ATTRIBUTE CLEANUP: Removed unused attributes from parent product $parentId: " . 
                implode(', ', $removedAttributes) . " (Global attributes still exist)"
            );
        }
    }
}

