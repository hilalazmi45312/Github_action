<?php
/**
 * Plugin Name: WC Parent+Child Category Archives
 * Description: Enforce /shop/{parent}/{child}/ to show only products where child belongs to parent. Adds pagination too.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

// 1) Add rewrites for /shop/{parent}/{child}/ and /shop/{parent}/{child}/page/2/
add_action('init', function () {
    // IMPORTANT: change 'shop' below if your category base is different
    $base = 'shop';

    add_rewrite_rule(
        '^' . $base . '/([^/]+)/([^/]+)/page/([0-9]+)/?$',
        'index.php?product_cat=$matches[2]&_pc_parent=$matches[1]&paged=$matches[3]',
        'top'
    );

    add_rewrite_rule(
        '^' . $base . '/([^/]+)/([^/]+)/?$',
        'index.php?product_cat=$matches[2]&_pc_parent=$matches[1]',
        'top'
    );
}, 9);

// 2) Allow our custom query var
add_filter('query_vars', function ($vars) {
    $vars[] = '_pc_parent';
    return $vars;
});

// 3) Validate parent/child relationship and constrain the main query
add_action('pre_get_posts', function ($q) {
    if (is_admin() || !$q->is_main_query()) return;

    // Only run on product archives that came through our rewrite
    $child_slug  = get_query_var('product_cat');
    $parent_slug = get_query_var('_pc_parent');

    if (!$child_slug || !$parent_slug) return;

    $child  = get_term_by('slug', $child_slug, 'product_cat');
    $parent = get_term_by('slug', $parent_slug, 'product_cat');
    if (!$child || is_wp_error($child) || !$parent || is_wp_error($parent)) {
        // If either term missing, force 404
        $q->set_404();
        return;
    }

    // Check hierarchy: is the child's parent actually the requested parent?
    if ((int) $child->parent !== (int) $parent->term_id) {
        // Not a real parent→child pair -> 404 (or you could redirect)
        $q->set_404();
        return;
    }

    // Constrain the query to the child term explicitly
    $q->set('post_type', 'product');
    $q->set('tax_query', [
        [
            'taxonomy'         => 'product_cat',
            'field'            => 'term_id',
            'terms'            => [$child->term_id],
            'include_children' => true, // child can include its own descendants if any
        ],
    ]);

    // Optional: control ordering / per-page here if you want
    // $q->set('orderby', 'menu_order');
    // $q->set('order', 'ASC');
});

// 4) Use WooCommerce archive template so layout/sorting work as usual
add_filter('template_include', function ($template) {
    $child_slug  = get_query_var('product_cat');
    $parent_slug = get_query_var('_pc_parent');

    if ($child_slug && $parent_slug) {
        $wc_template = wc_locate_template('archive-product.php');
        if ($wc_template) return $wc_template;
    }
    return $template;
});

// 5) Flush rewrites on activation (if this is a normal plugin)
register_activation_hook(__FILE__, function () { flush_rewrite_rules(); });
register_deactivation_hook(__FILE__, function () { flush_rewrite_rules(); });