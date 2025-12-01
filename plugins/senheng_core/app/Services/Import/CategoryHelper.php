<?php
namespace SenhengCore\App\Services\Import;

class CategoryHelper
{
    /**
     * Assign product to L1/L2/L3 category path
     * Creates missing categories and maintains hierarchy
     * 
     * @param int   $postId  Product ID
     * @param array $path    Array with keys: L1, L2, L3
     */
    public static function assignByPath(int $postId, array $path): void
    {
        $l1 = trim((string)($path['L1'] ?? ''));
        $l2 = trim((string)($path['L2'] ?? ''));
        $l3 = trim((string)($path['L3'] ?? ''));

        // Check if product already has the same categories assigned
        $currentCategories = wp_get_object_terms($postId, 'product_cat', ['fields' => 'names']);
        if (!is_wp_error($currentCategories)) {
            $newCategories = array_filter([$l1, $l2, $l3]);
            if (empty(array_diff($newCategories, $currentCategories)) && empty(array_diff($currentCategories, $newCategories))) {
                Logger::info(
                    WP_CONTENT_DIR . '/uploads/senheng_import.log',
                    "Categories already assigned to product ID $postId - skipping"
                );
                return;
            }
        }

        $ids = [];
        $parent = 0;

        foreach ([$l1, $l2, $l3] as $name) {
            if ($name === '') continue;
            
            // Check if term exists with this parent
            $term = term_exists($name, 'product_cat', $parent);
            
            if (!$term) {
                // Create new term
                $term = wp_insert_term($name, 'product_cat', ['parent' => $parent]);
                
                if (is_wp_error($term)) {
                    Logger::error(
                        WP_CONTENT_DIR . '/uploads/senheng_import.log',
                        "Failed to create category '$name': " . $term->get_error_message()
                    );
                    continue;
                }
            }
            
            $parent = (int)$term['term_id'];
            $ids[] = $parent;
        }

        if ($ids) {
            wp_set_object_terms($postId, $ids, 'product_cat');
        }
    }
}

