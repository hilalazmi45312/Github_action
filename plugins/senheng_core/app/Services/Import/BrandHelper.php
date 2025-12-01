<?php
namespace SenhengCore\App\Services\Import;

class BrandHelper
{
    /**
     * Assign brand to product by checking existing brands first, then creating new ones if needed
     * 
     * @param int    $productId   Product ID to assign brand to
     * @param string $brandName   Brand name from import data
     * @return bool              Success status
     */
    public static function assignBrand(int $productId, string $brandName): bool
    {
        if (empty($brandName)) {
            Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Skipping brand assignment - empty brand name for product ID: $productId");
            return false;
        }

        // Ensure the product_brand taxonomy exists
        self::ensureProductBrandTaxonomy();

        // Clean the brand name
        $brandName = trim($brandName);
        
        Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Processing brand assignment: '$brandName' for product ID: $productId");

        // Check if product already has this brand assigned
        $currentBrands = wp_get_object_terms($productId, 'product_brand', ['fields' => 'names']);
        if (!is_wp_error($currentBrands) && in_array($brandName, $currentBrands)) {
            Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Brand '$brandName' already assigned to product ID: $productId - skipping");
            return true;
        }

        // First, try to find existing brand by name (case-insensitive)
        $existingBrand = self::findExistingBrand($brandName);
        
        if ($existingBrand) {
            // Use existing brand
            $brandTermId = $existingBrand->term_id;
            Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Found existing brand: '$brandName' (ID: $brandTermId)");
        } else {
            // Create new brand
            $brandTermId = self::createNewBrand($brandName);
            if (!$brandTermId) {
                Logger::error(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Failed to create brand: '$brandName' for product ID: $productId");
                return false;
            }
            Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Created new brand: '$brandName' (ID: $brandTermId)");
        }

        // Assign brand to product
        $result = wp_set_object_terms($productId, $brandTermId, 'product_brand', false);
        
        if (is_wp_error($result)) {
            Logger::error(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Failed to assign brand '$brandName' to product ID $productId: " . $result->get_error_message());
            return false;
        }

        Logger::info(WP_CONTENT_DIR . '/uploads/senheng_import.log', "✓ Successfully assigned brand '$brandName' to product ID: $productId");
        return true;
    }

    /**
     * Find existing brand by name (case-insensitive)
     * 
     * @param string $brandName  Brand name to search for
     * @return \WP_Term|null     Found term or null
     */
    private static function findExistingBrand(string $brandName): ?\WP_Term
    {
        // Try exact name match first
        $term = get_term_by('name', $brandName, 'product_brand');
        if ($term && !is_wp_error($term)) {
            return $term;
        }

        // Try case-insensitive search
        $terms = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => false,
            'meta_query' => [],
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                if (strcasecmp($term->name, $brandName) === 0) {
                    return $term;
                }
            }
        }

        // Try slug match as fallback
        $slug = sanitize_title($brandName);
        $term = get_term_by('slug', $slug, 'product_brand');
        if ($term && !is_wp_error($term)) {
            return $term;
        }

        return null;
    }

    /**
     * Create new brand term
     * 
     * @param string $brandName  Brand name to create
     * @return int|false         Term ID on success, false on failure
     */
    private static function createNewBrand(string $brandName)
    {
        $result = wp_insert_term($brandName, 'product_brand', [
            'description' => "Brand: $brandName",
            'slug' => sanitize_title($brandName),
        ]);

        if (is_wp_error($result)) {
            Logger::error(WP_CONTENT_DIR . '/uploads/senheng_import.log', "Error creating brand '$brandName': " . $result->get_error_message());
            return false;
        }

        return $result['term_id'] ?? false;
    }

    /**
     * Ensure product_brand taxonomy is registered
     */
    private static function ensureProductBrandTaxonomy(): void
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
                    'show_in_rest'      => true,
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

    /**
     * Get all existing brands
     * 
     * @return array  Array of brand terms
     */
    public static function getAllBrands(): array
    {
        $terms = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => false,
        ]);

        return is_wp_error($terms) ? [] : $terms;
    }

    /**
     * Get brand statistics
     * 
     * @return array  Statistics about brands
     */
    public static function getBrandStats(): array
    {
        $brands = self::getAllBrands();
        $stats = [
            'total_brands' => count($brands),
            'brands_with_products' => 0,
            'empty_brands' => 0,
        ];

        foreach ($brands as $brand) {
            if ($brand->count > 0) {
                $stats['brands_with_products']++;
            } else {
                $stats['empty_brands']++;
            }
        }

        return $stats;
    }
}