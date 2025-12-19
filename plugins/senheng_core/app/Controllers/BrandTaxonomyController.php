<?php

/**
 * =====================================================================
 * SENHENG – BRAND FILTERING ENGINE
 * Supports:
 *  - /brand/{brand}
 *  - /brand/{brand}/{category}
 *  - /brand/{brand}/{category}/page/{n}
 *  - /product-category/{brand}/{category-path}
 *  - /product-category/{brand}/{category-path}/page/{n}
 * =====================================================================
 */


/* ============================================================
 * 1. Register URL rewrite rules for BRAND URLs
 *    Examples:
 *    /brand/apple/
 *    /brand/apple/mobile-phones/
 *    /brand/apple/mobile-phones/page/2/
 * ============================================================ */
add_action('init', function () {

    // Brand only
    add_rewrite_rule(
        '^brand/([^/]+)/?$',
        'index.php?product_brand=$matches[1]',
        'top'
    );

    // Brand + category
    add_rewrite_rule(
        '^brand/([^/]+)/([^/]+)/?$',
        'index.php?product_cat=$matches[2]&filter_brand=$matches[1]',
        'top'
    );

    // Brand + category + pagination
    add_rewrite_rule(
        '^brand/([^/]+)/([^/]+)/page/([0-9]+)/?$',
        'index.php?product_cat=$matches[2]&filter_brand=$matches[1]&paged=$matches[3]',
        'top'
    );
});



/* ============================================================
 * 2. Whitelist our custom query variable: filter_brand
 * ============================================================ */
add_filter('query_vars', function ($vars) {
    $vars[] = 'filter_brand';
    return $vars;
});



/* ============================================================
 * 3. Register URL rewrite rules for PRODUCT CATEGORY URLs
 *    Allows:
 *      /product-category/apple/mobile-phones/
 *      /product-category/apple/mobiles/tablets/android/
 *      /product-category/apple/.../page/2/
 *
 *    Supports unlimited category depth.
 * ============================================================ */
add_action('init', function () {

    // Get all brand slugs
    $brands = get_terms([
        'taxonomy'   => 'product_brand',
        'fields'     => 'slugs',
        'hide_empty' => false,
    ]);

    if (empty($brands)) return;

    $pattern = implode('|', array_map('preg_quote', $brands));

    /**
     * IMPORTANT:
     * Pagination MUST be registered FIRST
     * so it overrides WooCommerce internal pagination rewrites.
     */

    // /product-category/apple/.../page/2/
    add_rewrite_rule(
        '^product-category/(' . $pattern . ')/(.*)/page/([0-9]+)/?$',
        'index.php?product_cat=$matches[2]&filter_brand=$matches[1]&paged=$matches[3]',
        'top'
    );

    // /product-category/apple/.../
    add_rewrite_rule(
        '^product-category/(' . $pattern . ')/(.*)/?$',
        'index.php?product_cat=$matches[2]&filter_brand=$matches[1]',
        'top'
    );
});



/* ============================================================
 * 4. UNIVERSAL BRAND FILTER
 *
 * This is the magic that makes everything work:
 *  - WooCommerce archive queries
 *  - Woodmart AJAX (woo_ajax=1)
 *  - Woodmart shortcode loops
 *  - Pagination (page/2)
 *
 * ALWAYS runs before WP_Query executes.
 * ============================================================ */
add_action('woocommerce_product_query', function ($q) {

    global $wp;

    $brand = get_query_var('filter_brand');

    // Try URL param: ?filter_brand=
    if (!$brand && isset($_GET['filter_brand'])) {
        $brand = sanitize_title($_GET['filter_brand']);
    }

    // Detect brand from /brand/apple/...
    if (!$brand && isset($wp->request) && preg_match('#/brand/([^/]+)#', $wp->request, $m)) {
        $brand = sanitize_title($m[1]);
    }

    // Detect brand from /product-category/apple/...
    if (!$brand && isset($wp->request) && preg_match('#/product-category/([^/]+)/#', $wp->request, $m)) {
        $brand = sanitize_title($m[1]);
    }

    // No brand → stop
    if (!$brand) return;

    // Inject brand taxonomy filter
    $tax_query   = (array) $q->get('tax_query');
    $tax_query[] = [
        'taxonomy' => 'product_brand',
        'field'    => 'slug',
        'terms'    => [$brand],
        'operator' => 'IN'
    ];

    $q->set('tax_query', $tax_query);
});
