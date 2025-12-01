<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


function my_admin_inline_css() {
    echo '<style>
        .wp-admin .form-table .gpf-group-heading td{
            display: none;
        }
    </style>';
}
add_action('admin_head', 'my_admin_inline_css');

/**
 * Shipping class: category default + "Use category-level setting" option inside native dropdown
 *
 * WHAT IT DOES
 * 1) Category screen: add "Default Shipping Class" (stores term_id in term meta)
 * 2) Product screen: prepend "Use category-level setting" to the native Shipping class select
 *    - When selected, we save a product meta flag and clear product's class
 * 3) Runtime: if product uses category-level, resolve class id from its categories
 */

/* -----------------------------
 * 1) CATEGORY: add/edit fields
 * ----------------------------- */
add_action('product_cat_add_form_fields', function () {
    $shipping_classes = get_terms(array(
        'taxonomy'   => 'product_shipping_class',
        'hide_empty' => false,
    ));
    ?>
    <div class="form-field">
        <label for="shipping_class"><?php esc_html_e('Default Shipping Class', 'woocommerce'); ?></label>
        <select name="shipping_class" id="shipping_class">
            <option value=""><?php esc_html_e('None', 'woocommerce'); ?></option>
            <?php foreach ($shipping_classes as $class): ?>
                <option value="<?php echo esc_attr($class->term_id); ?>"><?php echo esc_html($class->name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e('Products set to use the category-level setting will resolve to this class.', 'woocommerce'); ?></p>
    </div>
    <?php
});

add_action('product_cat_edit_form_fields', function ($term, $taxonomy) {
    $shipping_classes = get_terms(array(
        'taxonomy'   => 'product_shipping_class',
        'hide_empty' => false,
    ));
    $value = get_term_meta($term->term_id, 'default_shipping_class', true);
    ?>
    <tr class="form-field">
        <th scope="row"><label for="shipping_class"><?php esc_html_e('Default Shipping Class', 'woocommerce'); ?></label></th>
        <td>
            <select name="shipping_class" id="shipping_class">
                <option value=""><?php esc_html_e('None', 'woocommerce'); ?></option>
                <?php foreach ($shipping_classes as $class): ?>
                    <option value="<?php echo esc_attr($class->term_id); ?>" <?php selected($value, $class->term_id); ?>>
                        <?php echo esc_html($class->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <p class="description"><?php esc_html_e('Products set to use the category-level setting will resolve to this class.', 'woocommerce'); ?></p>
        </td>
    </tr>
    <?php
}, 10, 2);

add_action('created_product_cat', 'my_save_cat_default_shipping_class');
add_action('edited_product_cat',  'my_save_cat_default_shipping_class');
function my_save_cat_default_shipping_class($term_id) {
    if (isset($_POST['shipping_class']) && $_POST['shipping_class'] !== '') {
        update_term_meta($term_id, 'default_shipping_class', (int) $_POST['shipping_class']);
    } else {
        delete_term_meta($term_id, 'default_shipping_class');
    }
}

/* ------------------------------------------------------------
 * 2) PRODUCT ADMIN: inject "Use category-level setting" option
 *    - We store _use_category_shipping_class = yes|no (default yes)
 *    - If "yes", we clear product's own class on save
 * ------------------------------------------------------------ */
add_action('woocommerce_product_options_shipping', function () {
    global $post;
    $use_cat = get_post_meta($post->ID, '_use_category_shipping_class', true);
    if ($use_cat === '') $use_cat = 'yes';
    ?>
    <script>
    (function($){
        $(function(){
            var $sel = $('#product_shipping_class'); // native shipping class select
            if (!$sel.length) return;

            // Prepend our virtual option if not already present
            if ($sel.find('option[value="use_category"]').length === 0) {
                $sel.prepend(
                    $('<option/>', {value: 'use_category', text: '<?php echo esc_js(__('Use category-level setting', 'woocommerce')); ?>'})
                );
            }

            // Select it based on saved meta
            <?php if ($use_cat === 'yes'): ?>
                $sel.val('use_category');
            <?php endif; ?>

            
            // Keep a hidden input reflecting the choice (so we can read it on save)
            if ($('#_use_category_shipping_class').length === 0) {
                $('<input/>', {type:'hidden', id:'_use_category_shipping_class', name:'_use_category_shipping_class'}).appendTo('#general_product_data'); // any form area
            }
            function syncHidden(){
                $('#_use_category_shipping_class').val($sel.val()==='use_category' ? 'yes' : 'no');
            }
            $sel.on('change', syncHidden);
            syncHidden();
        });
    })(jQuery);
    </script>
    <?php
});

// Save the flag and normalize the product's own class
add_action('woocommerce_process_product_meta', function ($post_id) {
    // read our hidden field set by JS; default to 'yes' if missing
    $use_cat = isset($_POST['_use_category_shipping_class']) && $_POST['_use_category_shipping_class'] === 'no' ? 'no' : 'yes';
    update_post_meta($post_id, '_use_category_shipping_class', $use_cat);

    // If using category-level, clear product's own class so it doesn't conflict
    if ($use_cat === 'yes') {
        $product = wc_get_product($post_id);
        if ($product) {
            $product->set_shipping_class_id(0); // remove direct class
            $product->save();
        }
    }
}, 5); // run early, but we'll still override after if needed

/* ------------------------------------------------------------
 * 3) RUNTIME: resolve effective shipping class when needed
 * ------------------------------------------------------------ */
function my_get_default_class_from_categories($product_id) {
    $cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
    if (empty($cats) || is_wp_error($cats)) return 0;

    foreach ($cats as $cat_id) {
        $default_id = (int) get_term_meta($cat_id, 'default_shipping_class', true);
        if ($default_id > 0) {
            return $default_id; // first category with a default wins
        }
    }
    return 0;
}

// Simple/grouped/variable products
add_filter('woocommerce_product_get_shipping_class_id', function ($shipping_class_id, $product) {
    $use_cat = get_post_meta($product->get_id(), '_use_category_shipping_class', true);
    if ($use_cat === '') $use_cat = 'yes';

    if ($use_cat === 'yes' || (int)$shipping_class_id === 0) {
        $resolved = my_get_default_class_from_categories($product->get_id());
        if ($resolved > 0) return $resolved;
    }
    return $shipping_class_id;
}, 10, 2);

// Variations: if they have no class and parent uses category-level, resolve via parent categories
add_filter('woocommerce_product_variation_get_shipping_class_id', function ($shipping_class_id, $variation) {
    $parent_id = $variation->get_parent_id();
    $use_cat_parent = get_post_meta($parent_id, '_use_category_shipping_class', true);
    if ($use_cat_parent === '') $use_cat_parent = 'yes';

    if ((int)$shipping_class_id === 0 && $use_cat_parent === 'yes') {
        $resolved = my_get_default_class_from_categories($parent_id);
        if ($resolved > 0) return $resolved;
    }
    return $shipping_class_id;
}, 10, 2);


/**
 * WooCommerce: Block duplicate SKUs (only duplicates; empty allowed)
 * Fixed: Allows same SKU inside same product family (parent + variations)
 * Inline admin error + server-side safety
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ======================================================
 * Helper: Find product ID by SKU (ignore trash/drafts)
 * Excludes current product + its children
 * ====================================================== */
function wc_get_product_id_by_sku_active_only( $sku, $exclude_id = 0, $parent_id = 0 ) {
    global $wpdb;

    $exclude_ids = [ (int) $exclude_id ];
    if ( $parent_id && $parent_id !== $exclude_id ) {
        $exclude_ids[] = (int) $parent_id;
    }

    // Exclude variations of the current product too
    $child_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_parent = %d AND post_type = 'product_variation'",
        $parent_id ?: $exclude_id
    ) );
    $exclude_ids = array_merge( $exclude_ids, array_map( 'intval', $child_ids ) );

    $exclude_sql = implode( ',', array_fill( 0, count( $exclude_ids ), '%d' ) );

    $query = "
        SELECT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE pm.meta_key = '_sku'
        AND pm.meta_value = %s
        AND p.post_status NOT IN ('trash','auto-draft')
        AND p.ID NOT IN ($exclude_sql)
        LIMIT 1
    ";

    $args = array_merge( [ $sku ], $exclude_ids );
    $found = $wpdb->get_var( $wpdb->prepare( $query, ...$args ) );

    return $found ? (int) $found : 0;
}

/* ================================
 * 1) AJAX: check duplicate SKU
 * ================================ */
add_action( 'wp_ajax_wc_check_sku_duplicate_only', function () {
    check_ajax_referer( 'wc_sku_check_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_products' ) ) {
        wp_send_json_error( [ 'msg' => 'Not allowed' ], 403 );
    }

    $sku       = isset( $_POST['sku'] ) ? wc_clean( wp_unslash( $_POST['sku'] ) ) : '';
    $post_id   = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
    $parent_id = isset( $_POST['parent_id'] ) ? (int) $_POST['parent_id'] : 0;

    if ( $sku === '' ) {
        wp_send_json_success( [ 'duplicate' => false ] ); // empty allowed
    }

    $found = wc_get_product_id_by_sku_active_only( $sku, $post_id, $parent_id );
    wp_send_json_success( [ 'duplicate' => ( $found > 0 ) ] );
});

/* =========================================
 * 2) Admin CSS + JS (classic product editor)
 * ========================================= */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== 'product' ) return;

    // Inline CSS
    add_action( 'admin_head', function () {
        echo '<style>
        .sku-inline-error.error {
            display:block;
            margin-top:4px;
            font-size:12px;
            color:#a00;
            clear:both;
        }
        .woocommerce-input-error {
            border-color:#a00 !important;
            box-shadow:0 0 0 1px #a00;
        }
        </style>';
    });

    wp_register_script( 'wc-sku-dup-check', '', [ 'jquery' ], '3.0', true );
    wp_enqueue_script( 'wc-sku-dup-check' );

    $data = [
        'ajax'     => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'wc_sku_check_nonce' ),
        'post_id'  => isset( $_GET['post'] ) ? (int) $_GET['post'] : 0,
        'msg'      => __( 'Duplicate SKU. Please choose a unique SKU.', 'sku-guard' ),
    ];
    wp_add_inline_script( 'wc-sku-dup-check', 'window.WC_SKU_DUP=' . wp_json_encode( $data ) . ';' );

    // Inline JS
    $js = <<<'JS'
    (function($){
        var $btns, timer=null;

        function ensureInline($input){
            if (!$input.length) return;
            if ($input.nextAll('.sku-inline-error').length) return;
            $('<span class="error sku-inline-error" style="display:none;"></span>').insertAfter($input);
        }

        function showTop(msg){
            removeTop();
            var $n=$('<div class="notice notice-error sku-dup-top"><p></p></div>');
            $n.find('p').text(msg);
            $('#wpbody-content .wrap').first().prepend($n);
        }
        function removeTop(){ $('.sku-dup-top').remove(); }

        function flagField($input,isDup){
            var $err=$input.nextAll('.sku-inline-error').first();
            $input.attr('data-dup',isDup?'1':'0');
            if(isDup){
                $input.addClass('woocommerce-input-error');
                $err.text(WC_SKU_DUP.msg).show();
            } else {
                $input.removeClass('woocommerce-input-error');
                $err.hide();
            }
        }

        function recompute(){
            var anyDup=false;
            $('#_sku,[name^="variable_sku"]').each(function(){
                if($(this).attr('data-dup')==='1'){ anyDup=true; return false; }
            });
            if(anyDup){ $btns.prop('disabled',true); showTop(WC_SKU_DUP.msg); }
            else { $btns.prop('disabled',false); removeTop(); }
        }

        function ajaxCheck($input, sku, cb){
            var vid = $input.closest('.woocommerce_variation').find('input.variation_id').val() || 0;
            var pid = WC_SKU_DUP.post_id || 0;

            $.post(WC_SKU_DUP.ajax,{
                action:'wc_check_sku_duplicate_only',
                nonce:WC_SKU_DUP.nonce,
                sku: sku,
                post_id: parseInt(vid)||pid,
                parent_id: pid
            }).done(function(resp){
                cb(!!(resp && resp.success && resp.data && resp.data.duplicate));
            }).fail(function(){ cb(false); });
        }

        function checkField($input){
            if(!$input.length) return;
            var s=($input.val()||'').trim();
            if(s===''){ flagField($input,false); recompute(); return; }
            ajaxCheck($input, s, function(isDup){ flagField($input,isDup); recompute(); });
        }

        function initAllSkuFields(){
            var $fields=$('#_sku,[name^="variable_sku"]');
            $fields.each(function(){
                var $t=$(this);
                ensureInline($t);
                if(!$t.attr('data-dup')) $t.attr('data-dup','0');
                checkField($t);
            });
        }

        function bindLive(){
            $(document).off('input.sku blur.sku', '#_sku,[name^="variable_sku"]')
            .on('input.sku blur.sku', '#_sku,[name^="variable_sku"]', function(){
                var $t=$(this);
                clearTimeout(timer);
                timer=setTimeout(function(){ checkField($t); }, 250);
            });
        }

        function bindVariationEvents(){
            $(document).on('woocommerce_variations_loaded woocommerce_variations_added wc_variations_loaded', function(){
                initAllSkuFields();
            });
            $(document).on('click', '.woocommerce_variation .toggle_variation', function(){
                setTimeout(initAllSkuFields, 50);
            });
            $(document).on('woocommerce_variations_added', function(){
                setTimeout(initAllSkuFields, 50);
            });
        }

        $(function(){
            $btns = $('#publish,#save-post');
            initAllSkuFields();
            bindLive();
            bindVariationEvents();

            $('#post').on('submit', function(e){
                recompute();
                if($btns.is(':disabled')){
                    e.preventDefault(); e.stopImmediatePropagation();
                    showTop(WC_SKU_DUP.msg);
                    return false;
                }
                return true;
            });
        });
    })(jQuery);
    JS;

    wp_add_inline_script( 'wc-sku-dup-check', $js );
});

/* =======================================================
 * 3) Server-side safety (products + variations)
 * ======================================================= */
add_action( 'woocommerce_product_object_save_errors', function( $errors, $product ) {
    if ( ! $product || ! is_a( $product, 'WC_Product' ) ) return;

    $product_id = $product->get_id();
    $parent_id  = $product->get_parent_id();

    // Main product SKU
    $sku = $product->get_sku('edit');
    if ( $sku !== '' ) {
        $found = wc_get_product_id_by_sku_active_only( $sku, $product_id, $parent_id );
        if ( $found > 0 ) {
            $errors->add( 'duplicate_sku', __( 'Duplicate SKU. Please choose a unique SKU.', 'sku-guard' ) );
        }
    }

    // Variation SKUs
    if ( isset($_POST['variable_sku']) && is_array($_POST['variable_sku']) ) {
        foreach ( $_POST['variable_sku'] as $vid => $vsku ) {
            $vid  = (int) $vid;
            $vsku = wc_clean( wp_unslash( $vsku ) );
            if ( $vsku === '' ) continue;
            $found = wc_get_product_id_by_sku_active_only( $vsku, $vid, $product_id );
            if ( $found > 0 ) {
                $errors->add(
                    'duplicate_sku_var_' . $vid,
                    sprintf( __( 'Duplicate SKU "%s" in a variation. Please choose a unique SKU.', 'sku-guard' ), $vsku )
                );
            }
        }
    }
}, 10, 2 );


// Reorder product tabs in WooCommerce (Woodmart compatible)
add_filter( 'woocommerce_product_tabs', 'woodmart_reorder_product_tabs', 9999 );
function woodmart_reorder_product_tabs( $tabs ) {
    $new_tabs = [];
    
    // Always add description tab even if empty
    if ( isset( $tabs['description'] ) ) {
        $new_tabs['description'] = $tabs['description'];
    } else {
        // Create description tab if it doesn't exist
        $new_tabs['description'] = array(
            'title'    => __( 'Description', 'woocommerce' ),
            'priority' => 10,
            'callback' => 'woodmart_empty_description_tab_content'
        );
    }
    
    if ( isset( $tabs['additional_information'] ) ) {
        $new_tabs['additional_information'] = $tabs['additional_information'];
    }
    if ( isset( $tabs['wd_custom_tab'] ) ) {
        $new_tabs['wd_custom_tab'] = $tabs['wd_custom_tab'];
    }
    if ( isset( $tabs['reviews'] ) ) {
        $new_tabs['reviews'] = $tabs['reviews'];
    }
    
    return $new_tabs;
}
// Callback function for empty description tab
function woodmart_empty_description_tab_content() {
    echo '<p></p>';
}
// Force description tab to show even when empty
add_filter( 'woocommerce_product_description_tab_content', 'woodmart_force_description_tab_content' );
function woodmart_force_description_tab_content( $content ) {
    global $post;
    
    if ( empty( $post->post_content ) ) {
        return '<p> </p>';
    }
    
    return $content;
}

/**
 * Rename product data tabs
 */
add_filter( 'woocommerce_product_tabs', 'woo_rename_tabs', 98 );
function woo_rename_tabs( $tabs ) {
	$tabs['additional_information']['title'] = __( 'Specification' );	// Rename the additional information tab
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'force_show_description_tab' );
function force_show_description_tab( $tabs ) {
    // Make sure the tab exists
    if ( isset( $tabs['description'] ) ) {
        $tabs['description']['callback'] = 'custom_description_tab_content';
    }
    return $tabs;
}

function custom_description_tab_content() {
    global $post;

    // Get description (content)
    $content = get_post_field( 'post_content', $post->ID );

    // Show content if exists, otherwise show placeholder
    if ( ! empty( trim( $content ) ) ) {
        echo apply_filters( 'the_content', $content );
    } else {
        echo '<p>This product currently has no description.</p>';
    }
}


/*
 * Nuke Pixel Manager (Freemius) promo notice everywhere in wp-admin.
 */
add_action('admin_head', function () {
    // If you want to allow admins to see it, uncomment next 2 lines:
    // if ( current_user_can('manage_options') ) { return; }

    echo '<style id="kill-pixel-manager-notice">
        /* Target the exact ID + Freemius slug + promo attribute */
        #woocommerce-google-adwords-conversion-tracking-tag,
        .fs-notice.fs-slug-woocommerce-google-adwords-conversion-tracking-tag,
        .fs-notice[data-id="trial_promotion"]{
            display:none !important; visibility:hidden !important; height:0 !important; overflow:hidden !important;
        }
    </style>';
});

add_action('admin_footer', function () {
    // Remove existing node + any that appear later (Freemius sometimes injects late)
    ?>
    <script>
    (function () {
        function kill() {
            document.querySelectorAll(
                '#woocommerce-google-adwords-conversion-tracking-tag,' +
                '.fs-notice.fs-slug-woocommerce-google-adwords-conversion-tracking-tag,' +
                '.fs-notice[data-id="trial_promotion"]'
            ).forEach(function(el){ el.remove(); });
        }
        // Initial pass
        kill();
        // Watch for dynamically added notices
        var mo = new MutationObserver(kill);
        mo.observe(document.body, {childList: true, subtree: true});
    })();
    </script>
    <?php
});







/**
 * Remove ATUM Supplier and Supplier SKU fields from WooCommerce product edit page
 */
add_action('admin_head', function() {
    global $pagenow;

    // Only run on product edit/add pages
    if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
        echo '<style>
            .form-field._supplier_field,
            .form-field._supplier_sku_field {
                display: none !important;
            }
            .woocommerce_variations h2.atum-section-title{display:none !important}

        </style>';
    }
});




/**
 * Rename default order status
 */

add_filter( 'wc_order_statuses', function ( $statuses ) {
    $statuses['wc-pending']    = _x( 'To Pay', 'Order status', 'woocommerce' );
    $statuses['wc-processing'] = _x( 'To Pack', 'Order status', 'woocommerce' );
    return $statuses;
} );


/**
 * Rename the Multiple Customer Addresses "Identifier / Name" field on add/edit address modal.
 * Works for both Billing & Shipping address forms.
 */

add_filter( 'woocommerce_form_field_args', function( $args, $key, $value ) {

    $targets = [
        'wcmca_billing_address_internal_name',
        'wcmca_shipping_address_internal_name',
    ];

    if ( in_array( $key, $targets, true ) ) {
        $args['label'] = 'Address Name'; 

        // Optional: nicer placeholder
        $args['placeholder'] = 'Home address / Office address, etc';

        // Optional: hide the long examples text if present
        if ( isset( $args['description'] ) ) {
            $args['description'] = '';

        }
    }

    return $args;
}, 10, 3 );





/**
 * Custom Coupon Rule – Only One Brand or Platform Voucher Per Order, different type are stackable
 */


add_action('woocommerce_coupon_options_usage_restriction', function() {
    woocommerce_wp_select([
        'id' => 'voucher_type',
        'label' => __('Voucher Type', 'woocommerce'),
        'options' => [
            '' => __('Select type', 'woocommerce'),
            'brand' => __('Brand Coupon', 'woocommerce'),
            'platform' => __('Platform Coupon', 'woocommerce'),
        ],
    ]);
});
add_action('woocommerce_coupon_options_save', function($coupon_id) {
    if (isset($_POST['voucher_type']))
        update_post_meta($coupon_id, 'voucher_type', sanitize_text_field($_POST['voucher_type']));
});



add_filter('woocommerce_coupon_is_valid', function($valid, $coupon) {
    if (!$valid) return false;

    $type = get_post_meta($coupon->get_id(), 'voucher_type', true);
    if (!$type) return $valid;

    $applied = WC()->cart->get_applied_coupons();

    foreach ($applied as $code) {
        $applied_coupon = new WC_Coupon($code);
        $applied_type = get_post_meta($applied_coupon->get_id(), 'voucher_type', true);
        if ($applied_type === $type && strtolower($code) !== strtolower($coupon->get_code())) {
            wc_add_notice(sprintf(__('Only one %s coupon can be used per order.', 'woocommerce'), ucfirst($type)), 'error');
            return false;
        }
    }
    return true;
}, 10, 2);


// Disable WooCommerce Multiple Customer Addresses only on WP All Import admin screens
add_filter('option_active_plugins', function($plugins){
    if (
        is_admin() &&
        isset($_GET['page']) &&
        (strpos($_GET['page'], 'pmxi') !== false || strpos($_GET['page'], 'wpai') !== false)
    ) {
        // Remove the WCMCA plugin from active plugins for this request
        $plugins = array_values(array_filter($plugins, function($plugin){
            return strpos($plugin, 'woocommerce-multiple-customer-addresses') === false;
        }));
    }
    return $plugins;
});



/**
 * Fix WP 6.7 “_load_textdomain_just_in_time was called too early” notices.
 * Put this in your (child) theme's functions.php.
 */

/**
 * Helper: try a list of possible language folder paths for a plugin.
 * $relative_paths are relative to WP_PLUGIN_DIR.
 */
function sh_force_load_plugin_textdomain( $domain, array $relative_paths ) {
    foreach ( $relative_paths as $rel ) {
        // Normalize and check that folder exists before attempting load
        $rel = trim( $rel, '/' );
        if ( is_dir( WP_PLUGIN_DIR . '/' . $rel ) ) {
            load_plugin_textdomain( $domain, false, $rel );
            return;
        }
    }
}

// Force-load common noisy domains at init (earliest safe point).
add_action( 'init', function () {
    // Advanced Custom Fields
    sh_force_load_plugin_textdomain( 'acf', array(
        'advanced-custom-fields/languages',
        'advanced-custom-fields/lang',
    ) );

    // WooCommerce Shipping: Local Pickup Plus
    sh_force_load_plugin_textdomain( 'woocommerce-shipping-local-pickup-plus', array(
        'woocommerce-shipping-local-pickup-plus/languages',
        'woocommerce-shipping-local-pickup-plus/i18n/languages',
    ) );

    // WooCommerce Memberships
    sh_force_load_plugin_textdomain( 'woocommerce-memberships', array(
        'woocommerce-memberships/languages',
        'woocommerce-memberships/i18n/languages',
    ) );
}, 0 );

/**
 * TEMP: Silence the specific “doing it wrong” notice about _load_textdomain_just_in_time.
 * Remove this once the noisy plugins are updated.
 */
add_filter( 'doing_it_wrong_trigger_error', function ( $trigger, $function, $message ) {
    if ( strpos( $message, '_load_textdomain_just_in_time' ) !== false ) {
        return false; // don't trigger a PHP notice for this one
    }
    return $trigger;
}, 10, 3 );




/**
 * Full clone that keeps Elementor data intact and saves as Draft.
 * Put in functions.php or a small plugin.
 */

/** 1) Add "Clone (Full)" link on row actions (posts, pages, and common CPTs) */
add_filter( 'post_row_actions', 'sh_add_full_clone_link', 20, 2 );
add_filter( 'page_row_actions', 'sh_add_full_clone_link', 20, 2 );
function sh_add_full_clone_link( $actions, $post ) {
    // Limit to types you care about; add more if needed.
    $allowed_types = array( 'post', 'page', 'product', 'woodmart_layout', 'elementor_library' );
    if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
        return $actions;
    }

    if ( current_user_can( 'edit_post', $post->ID ) ) {
        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'sh_clone_full',
                    'post'   => $post->ID,
                ),
                admin_url( 'admin.php' )
            ),
            'sh_clone_full_' . $post->ID
        );

        $actions['sh_clone_full'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Clone (Full)', 'default' ) . '</a>';
    }

    return $actions;
}

/** 2) Handle the cloning (keeps Elementor meta safe) */
add_action( 'admin_action_sh_clone_full', 'sh_handle_full_clone' );
function sh_handle_full_clone() {
    if ( empty( $_GET['post'] ) ) {
        wp_die( esc_html__( 'No post to clone has been supplied!', 'default' ) );
    }

    $post_id = absint( $_GET['post'] );
    if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'sh_clone_full_' . $post_id ) ) {
        wp_die( esc_html__( 'Security check failed.', 'default' ) );
    }

    $post = get_post( $post_id );
    if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_die( esc_html__( 'You are not allowed to clone this item.', 'default' ) );
    }

    // Insert the new draft
    $new_post_id = wp_insert_post( array(
        'post_author'  => get_current_user_id(),
        'post_content' => $post->post_content, // not used by Elementor but fine to keep
        'post_excerpt' => $post->post_excerpt,
        'post_title'   => $post->post_title . ' (copy)',
        'post_status'  => 'draft',
        'post_type'    => $post->post_type,
        'post_parent'  => 0,
        'menu_order'   => $post->menu_order,
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
    ), true );

    if ( is_wp_error( $new_post_id ) ) {
        wp_die( $new_post_id );
    }

    // Copy terms (all taxonomies registered to this post type)
    $taxonomies = get_object_taxonomies( $post->post_type );
    foreach ( $taxonomies as $taxonomy ) {
        $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
        if ( ! is_wp_error( $terms ) ) {
            wp_set_object_terms( $new_post_id, $terms, $taxonomy, false );
        }
    }

    // Copy all post meta; keep a small blacklist of system/meta that shouldn't be copied
    $blacklist = array(
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_elementor_css',              // regenerated by Elementor
        '_elementor_source_image_hash' // transient-ish
    );

    $all_meta = get_post_meta( $post_id );
    foreach ( $all_meta as $meta_key => $values ) {
        if ( in_array( $meta_key, $blacklist, true ) ) {
            continue;
        }

        foreach ( $values as $value ) {
            // Ensure Elementor JSON stays valid (and any serialized content is preserved)
            if ( str_starts_with( $meta_key, '_elementor_' ) ) {
                // If the value is an array/object, encode; otherwise keep as string
                if ( is_array( $value ) || is_object( $value ) ) {
                    $value = wp_json_encode( $value );
                }
                $value = wp_slash( (string) $value );
                update_post_meta( $new_post_id, $meta_key, $value );
            } else {
                // Preserve serialized data & strings safely
                $value = maybe_unserialize( $value );
                $value = is_string( $value ) ? wp_slash( $value ) : $value;
                add_post_meta( $new_post_id, $meta_key, $value );
            }
        }
    }

    // Optional: fix common theme meta so layout/type is preserved (Woodmart examples)
    foreach ( array( 'wd_layout_type', 'woodmart_layout_type', 'conditions' ) as $maybe_key ) {
        $v = get_post_meta( $post_id, $maybe_key, true );
        if ( $v !== '' && $v !== null ) {
            update_post_meta( $new_post_id, $maybe_key, is_string( $v ) ? wp_slash( $v ) : $v );
        }
    }

    // Redirect back to the list table with a notice; do NOT open Elementor.
    $redirect = add_query_arg(
        array(
            'post_type' => $post->post_type,
            'sh_cloned' => 1,
        ),
        admin_url( 'edit.php' )
    );
    wp_safe_redirect( $redirect );
    exit;
}

/** 3) Small notice after cloning */
add_action( 'admin_notices', function () {
    if ( isset( $_GET['sh_cloned'] ) ) {
        echo '<div class="notice notice-success is-dismissible"><p>' .
             esc_html__( 'Clone created as Draft. You can now edit it with Elementor.', 'default' ) .
             '</p></div>';
    }
} );




// Enable WoodMart Text Swatch only on the "Add new term" form (not on existing term edit).
add_action( 'admin_footer', function () {
    $screen = get_current_screen();
    if ( ! $screen ) return;

    // Only run on WooCommerce attribute term list pages
    if ( strpos( $screen->id, 'edit-pa_' ) !== 0 ) return;
    ?>
    <script>
        jQuery(function($) {

            // Only target the ADD NEW TERM form, not the edit form
            var addForm = $('#addtag');

            // Inside this form only, force default ON
            addForm.find('input[name="not_dropdown"]').val('on');

            // Turn the switch ON visually only inside add form
            addForm.find('.xts-switcher-btn').addClass('xts-active');

            // DO NOT touch the edit form
        });
    </script>
    <?php
});




/**
 * Fix WooCommerce brand archive pagination (/brand/haier/page/2 etc.).
 */
function sh_fix_brand_archive_query( $q ) {

    // Only adjust main front-end query.
    if ( is_admin() || ! $q->is_main_query() ) {
        return;
    }

    // If your brand taxonomy slug is not "brand", change it here.
    if ( $q->is_tax( 'brand' ) ) {

        // Always query products.
        $q->set( 'post_type', 'product' );
        $q->set( 'post_status', 'publish' );

        // Ensure paged is set correctly.
        $paged = max( 1, get_query_var( 'paged' ), get_query_var( 'page' ) );
        $q->set( 'paged', $paged );

        // Offsets + pagination cause empty pages – remove any offset.
        if ( $q->get( 'offset' ) ) {
            $q->set( 'offset', 0 );
        }
    }
}
add_action( 'pre_get_posts', 'sh_fix_brand_archive_query', 999 );









/**
 * Funnelkit Checkout: Change the position of multiple customer Address field position 
 */


class WFACP_Compatibility_With_WC_Multiple_Customer_Addresses_field_position {

    public $instance = null;
    public $checkout_instance = null;

    public function __construct() {

        add_action( 'process_wfacp_html', [ $this, 'display_field' ], 999, 3 );

        // Remove the default action
        add_action( 'wfacp_template_load', [ $this, 'actions' ], 8 );

        add_action( 'wfacp_after_checkout_page_found', [ $this, 're_attach_actions' ], 10 );

        // Remove default shipping address selector placement
        add_action( 'wfacp_divider_shipping', function() {
            WFACP_Common::remove_actions(
                'woocommerce_before_checkout_shipping_form',
                'WCMCA_CheckoutPage',
                'add_shipping_address_select_menu'
            );
        }, 1 );

        // Display shipping selector below "Use a different shipping address"
        add_action( 'woocommerce_before_checkout_shipping_form', [ $this, 'display_shipping_address_select_menu' ], 5 );
    }

    public function re_attach_actions() {
        // Display billing selector below Billing Address title
        add_action(
            'wfacp_divider_billing',
            [ $this, 'display_billing_address_select_menu' ],
            5,
            1 // must accept 1 arg
        );
    }

    // Billing address selector
    public function display_billing_address_select_menu( $unused = null ) {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Render selector
        echo '<div class="wfacp_address_multi_select_wrap">';
        echo '<div class="wfacp_billing_address_multi_select wfacp-col-full wfacp_billing_fields">';
        $this->instance->add_billing_address_select_menu( WC()->checkout() );
        echo '</div>';
        echo '</div>';

        // Move selector above Billing First Name
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var selector = document.querySelector('.wfacp_billing_address_multi_select');
                var firstNameField = document.getElementById('billing_first_name_field');

                if (selector && firstNameField && firstNameField.parentNode) {
                    firstNameField.parentNode.insertBefore(selector, firstNameField);
                }
            });
        </script>
        <?php
    }

    public function actions() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        WFACP_Common::remove_actions(
            'process_wfacp_html',
            'WFACP_Compatibility_With_WC_Multiple_Customer_Addresses',
            'display_field'
        );
    }

    // Shipping address selector
    public function display_shipping_address_select_menu() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Render selector
        echo '<div class="wfacp_address_multi_select_wrap">';
        echo '<div class="wfacp_shipping_address_multi_select wfacp-col-full wfacp_shipping_fields">';
        $this->instance->add_shipping_address_select_menu( WC()->checkout() );
        echo '</div>';
        echo '</div>';

        // Move selector above Shipping Street Address
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var selector = document.querySelector('.wfacp_shipping_address_multi_select');
                var streetField = document.getElementById('shipping_address_1_field');

                if (!streetField) {
                    // fallback for themes that change field IDs
                    streetField = document.querySelector('[id$="shipping_address_1_field"], [id^="shipping_address_1_field"]');
                }

                if (selector && streetField && streetField.parentNode) {
                    streetField.parentNode.insertBefore(selector, streetField);
                }
            });
        </script>
        <?php
    }

    // Disable original duplicate output
    public function display_field( $field, $key, $args ) {
        return '';
    }

    // Check plugin dependencies
    public function is_enabled() {
        if ( ! class_exists( 'WCMCA_CheckoutPage' ) ) {
            return false;
        }

        global $wcmca_checkout_page_addon;

        if ( ! $wcmca_checkout_page_addon instanceof WCMCA_CheckoutPage ) {
            return false;
        }

        $this->instance = $wcmca_checkout_page_addon;

        return class_exists( 'WFACP_Compatibility_With_WC_Multiple_Customer_Addresses' );
    }
}

new WFACP_Compatibility_With_WC_Multiple_Customer_Addresses_field_position();








/**
 * Woodmart – change sale badge percentage according
 * to selected variation on single product page.
 */

add_action( 'wp_enqueue_scripts', 'wdm_dynamic_variation_sale_badge', 100 );
function wdm_dynamic_variation_sale_badge() {

    // Only on single product pages.
    if ( ! is_product() ) {
        return;
    }

    // Make sure WooCommerce variation script is loaded.
    wp_enqueue_script( 'wc-add-to-cart-variation' );

    $script = <<<JS
jQuery(function($){

    var \$form  = $('.variations_form');

    if (!\$form.length) {
        return;
    }

    // Woodmart badge selector (fallback to default WooCommerce .onsale)
    var \$badge = $('.single-product .product-labels .product-label.onsale, .single-product span.onsale').first();

    if (!\$badge.length) {
        return;
    }

    // Save original text so we can restore on reset.
    var originalText = \$badge.text();

    // When a variation is found/selected
    \$form.on('found_variation', function(event, variation){

        if (!variation) {
            return;
        }

        var regular = parseFloat(variation.display_regular_price || variation.display_price || 0);
        var sale    = parseFloat(variation.display_price || 0);

        if (regular > 0 && sale < regular) {
            var discount = Math.round( (regular - sale) / regular * 100 );
            \$badge.text('-' + discount + '%').show();
        } else {
            // No sale for this variation -> hide badge (or show "Sale" if you want)
            \$badge.hide();
        }
    });

    // When variation selection is cleared
    \$form.on('reset_data', function(){
        if (originalText) {
            \$badge.text(originalText).show();
        } else {
            \$badge.hide();
        }
    });

});
JS;

    // Attach our JS to the WC variation script.
    wp_add_inline_script( 'wc-add-to-cart-variation', $script );
}

// FunnelKit Builder Pro adjusment
add_filter( 'wp_headers', function( $headers ) {
    // Only add the header if it is not already set
    if ( ! isset( $headers['Access-Control-Allow-Origin'] ) ) {
        $headers['Access-Control-Allow-Origin'] = 'https://app.wpmailkit.com';
    }

    return $headers;
}, 999 );