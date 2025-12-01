<?php

// enqueue
// add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_style('ikea-menu-custom', SENHENG_CORE_URL . 'assets/css/ikea-category-menu.css');
//     wp_enqueue_script('ikea-menu-custom', SENHENG_CORE_URL . 'assets/js/ikea-category-menu.js', array('jquery'), null, true);
//     // Localize script to pass the AJAX URL
//     wp_localize_script('ikea-menu-custom', 'ikeaMenuAjax', array('ajax_url' => admin_url('admin-ajax.php')));

// });

// Shortcode
add_shortcode('ikea_category_menu', function () {
    ob_start();
    ?>
    <div class="ikea-category-slider">
        <?php
        $subcat_data = [];
        $terms = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => false,
        ]);
        foreach ($terms as $term) {
            $thumb_id = get_term_meta($term->term_id, 'thumbnail_id', true);
            $thumb_url = wp_get_attachment_url($thumb_id);
            ?>
            <div onclick="popupSubCat('<?= esc_attr($term->term_id) ?>')" class="ikea-cat-item"
                data-term-id="<?= esc_attr($term->term_id) ?>">
                <?php if ($thumb_url): ?>
                    <img src="<?= esc_url($thumb_url) ?>" alt="<?= esc_attr($term->name) ?>">
                <?php else: ?>
                    <div class="placeholder-thumb"></div>
                <?php endif; ?>
                <span><?= esc_html($term->name) ?></span>
            </div>
            <?php

            $children = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $term->term_id,
                'hide_empty' => false
            ]);
            foreach ($children as $child) {
                $subcat_data[$term->term_id][] = [
                    'id' => $child->term_id,
                    'name' => $child->name,
                    'link' => get_term_link($child),
                ];
            }
        }
        ?>
    </div>
    <div class="ikea-mega-panel"></div>
    <?php
    ?>
    <script type="application/json" id="ikea-subcat-data">
            <?php echo json_encode($subcat_data); ?>
        </script>
    <?php
    return ob_get_clean();
});


// AJAX handler
add_action('wp_ajax_get_subcategories', 'get_subcategories_callback');
add_action('wp_ajax_nopriv_get_subcategories', 'get_subcategories_callback');

// function get_subcategories_callback()
// {
//     $term_id = intval($_POST['term_id'] ?? 0);

//     $subterms = get_terms([
//         'taxonomy' => 'product_cat',
//         'parent' => $term_id,
//         'hide_empty' => false,
//     ]);

//     if (!empty($subterms)) {
//         $result = [];
//         foreach ($subterms as $term) {
//             $result[] = [
//                 'id' => $term->term_id,
//                 'name' => $term->name,
//                 'link' => get_term_link($term),
//             ];
//         }
//         wp_send_json_success($result);
//     } else {
//         wp_send_json_error('No subcategories found.');
//     }
// }

