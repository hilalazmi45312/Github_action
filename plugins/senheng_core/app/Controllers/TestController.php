<?php
/**
 * Optimized Brand + Category Permalink Handler
 *
 * - Caches brand slugs (24 hours)
 * - Avoids get_terms() on every request
 * - Prevents rewrite rules from running unnecessarily
 * - Limits pre_get_posts to only actual product archive queries
 */

add_action('init', 'sh_opt_brand_category_rewrite');
function sh_opt_brand_category_rewrite() {

    // Do not run on admin, ajax, cron, or REST requests
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    // 1. Load cached brand slugs
    $brand_slugs = get_transient('sh_brand_slugs_regex');

    if (!$brand_slugs) {

        $terms = get_terms([
            'taxonomy'   => 'product_brand',
            'fields'     => 'slugs',
            'hide_empty' => false,
        ]);

        if (!is_wp_error($terms) && !empty($terms)) {
            $brand_slugs = implode('|', array_map('preg_quote', $terms));
        } else {
            $brand_slugs = '';
        }

        // Cache for 24 hours
        set_transient('sh_brand_slugs_regex', $brand_slugs, DAY_IN_SECONDS);
    }

    // If no brands found, do nothing
    if (empty($brand_slugs)) {
        return;
    }

    // ----------------------------------------
    // /product-category/{brand}/{cat-path}/
    // ----------------------------------------
    add_rewrite_rule(
        '^product-category/(' . $brand_slugs . ')/(.+)/?$',
        'index.php?product_cat=$matches[2]&brand=$matches[1]',
        'top'
    );

    // ----------------------------------------
    // /brand/{brand}/{cat-path}/
    // ----------------------------------------
    add_rewrite_rule(
        '^brand/([^/]+)/(.+)/?$',
        'index.php?product_brand=$matches[1]&product_cat=$matches[2]',
        'top'
    );
}


/**
 * Register custom query var
 */
add_filter('query_vars', function ($vars) {
    $vars[] = 'brand';
    return $vars;
});


/**
 * Modify main WooCommerce query
 */
add_action('pre_get_posts', 'sh_opt_brand_category_main_query', 20);
function sh_opt_brand_category_main_query($query) {

    // Only affect frontend main WooCommerce product queries
    if (is_admin() || ! $query->is_main_query() || ! $query->is_archive()) {
        return;
    }

    // CASE 1: /brand/{brand}/{cat-path}/
    if ($query->get('product_brand') && $query->get('product_cat')) {
        $query->set('post_type', 'product');
        return;
    }

    // CASE 2: /product-category/{brand}/{cat-path}/
    $brand_slug = $query->get('brand');

    if ($brand_slug && $query->get('product_cat') && ! $query->get('product_brand')) {

        if (term_exists($brand_slug, 'product_brand')) {

            $tax_query = (array) $query->get('tax_query');

            $tax_query[] = [
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $brand_slug,
                'operator' => 'IN',
            ];

            $query->set('tax_query', $tax_query);
        }

        $query->set('post_type', 'product');
    }
}