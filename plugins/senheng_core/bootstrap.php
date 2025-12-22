<?php

define('SENHENG_CORE_URL', plugin_dir_url(__FILE__));
define('SENHENG_CORE_PATH', plugin_dir_path(__FILE__));
define('SENHENG_CORE_VIEW_PATH', plugin_dir_path(__FILE__) . 'app/Views/');
define('SENHENG_CORE_ASSETS_URL', SENHENG_CORE_URL . 'assets/');

// Autoload
foreach (glob(__DIR__ . '/app/Controllers/*.php') as $file) {
    // if (basename($file) === 'InsiderController.php') { #disable Insider for now
    //     continue;
    // }
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Services/*.php') as $file) {
    // Defer loading the WooCommerce gateway class until after WooCommerce is loaded
    if (basename($file) === 'WC_Gateway_iPay88.php') {
        continue;
    }
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Models/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Views/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Helpers/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Listeners/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/app/Config/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/database/migrations/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/database/seeder/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/database/update/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/plugins/woocommerce-gateway-ipay88/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/plugins/funnel-kit-e-invoice/*.php') as $file) {
    require_once $file;
}
foreach (glob(__DIR__ . '/api/*.php') as $file) {
    require_once $file;
}
require_once __DIR__ . '/routes/web.php';
require_once __DIR__ . '/routes/api.php';



// Enqueue Font Awesome
function senheng_enqueue_font_awesome() {
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css',
        [],
        '6.4.2'
    );
}
add_action('wp_enqueue_scripts', 'senheng_enqueue_font_awesome');
add_action('admin_enqueue_scripts', 'senheng_enqueue_font_awesome');


// Enqueue SweetAlert2 with proper WordPress dependency management
// bootstrap.php (or functions.php)
function senheng_register_swal2() {
    wp_register_script(
        'sweetalert2',
        'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
        [], // no deps
        null,
        true // load in footer
    );
}
add_action('wp_enqueue_scripts', 'senheng_register_swal2');
add_action('admin_enqueue_scripts', 'senheng_register_swal2');


// Enqueue Animate CSS
function my_enqueue_animate_css() {
    wp_enqueue_style(
        'animate-css',
        'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css',
        array(),
        '4.1.1'
    );
}
add_action('wp_enqueue_scripts', 'my_enqueue_animate_css');

// Register activation hook to create necessary tables
add_action('init', 'senheng_run_table_creation_on_url');
function senheng_run_table_creation_on_url()
{
    if (isset($_GET['senheng_create_tables']) && $_GET['senheng_create_tables'] === '1') {
        if (current_user_can('manage_options')) { // Only admin can trigger this
            create_admin_fee_waivers_table();
            echo 'Waive tables created.';
            exit;
        } else {
            wp_die('Unauthorized');     
        }
    }
}

// // Register activation hook to create necessary tables
// add_action('init', 'senheng_run_failed_api_log_table_creation_on_url');
// function senheng_run_failed_api_log_table_creation_on_url()
// {
//     if (isset($_GET['api_log']) && $_GET['api_log'] === '1') {
//         if (current_user_can('manage_options')) { // Only admin can trigger this
//             create_failed_api_log_table();
//             echo 'Failed API log table created.';
//             exit;
//         } else {
//             wp_die('Unauthorized');     
//         }
//     }
// }

// Increase the maximum slug length to 200 characters
add_filter('woocommerce_attribute_slug_max_length', function() {
    return 200;
});
// add_filter( 'woocommerce_coupons_enabled', '__return_false' );
// Disable cart fragments on non-WooCommerce pages
add_action('wp_enqueue_scripts', function() {
    // Disable on all pages except cart, checkout, and shop pages
    if (!is_cart() && !is_checkout() && !is_shop() && !is_product()) {
        wp_dequeue_script('wc-cart-fragments');
    }
}, 99);

// Use this code below if you want to disable Branch.io
// add_filter('senheng_enable_branch_io', '__return_false');
// Branch.io loader + init in <head>

/**
 * Check if benefit box feature is enabled
 * 
 * @return bool
 */
function senheng_benefit_box_is_enabled(): bool
{
    return BenefitBox::isFeatureEnabled();
}

function senheng_output_branch_snippet() {
    if (!apply_filters('senheng_enable_branch_io', true)) {
        return;
    }
    if (!defined('BRANCH_IO_KEY') || !BRANCH_IO_KEY) {
        return;
    }
    ?>
    <script>
    (function(b,r,a,n,c,h,_,s,d,k){if(!b[n]||!b[n]._q){for(;s<_.length;)c(h,_[s++]);d=r.createElement(a);d.async=1;d.src="https://cdn.branch.io/branch-latest.min.js";k=r.getElementsByTagName(a)[0];k.parentNode.insertBefore(d,k);b[n]=h}})(window,document,"script","branch",function(b,r){b[r]=function(){b._q.push([r,arguments])}},{_q:[],_v:1},"addListener banner closeBanner closeJourney data deepview deepviewCta first init link logout removeListener setBranchViewData setIdentity track trackCommerceEvent logEvent disableTracking getBrowserFingerprintId crossPlatformIds lastAttributedTouchData setAPIResponseCallback qrCode setRequestMetaData setAPIUrl getAPIUrl setDMAParamsForEEA".split(" "), 0);
    branch.init("<?php echo esc_js(BRANCH_IO_KEY); ?>", function(err, data) {
        try { window.__branchInit = { err: err || null, data: data || null }; } catch(e) {}
        if (err) {
            console.error('[Branch] init error:', err);
        } else {
            console.log('[Branch] init ok:', data);
        }
    });
    </script>
    <?php
}
add_action('wp_head', 'senheng_output_branch_snippet', 5);


// Plugin activation hook
register_activation_hook(__FILE__, function() {
    // Create the progress tracking table
    require_once __DIR__ . '/app/Controllers/ProductImportController.php';
    ProductImportController::createProgressTable();
});

// Plugin deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Clean up any ongoing imports
    global $wpdb;
    $table_name = $wpdb->prefix . 'senheng_import_progress';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        // Mark all running imports as cancelled
        $wpdb->update(
            $table_name,
            ['status' => 'cancelled'],
            ['status' => 'running']
        );
    }
});

add_action( 'wp_head', function() {
    echo '<style>
        .woocommerce-mini-cart__buttons .checkout {
            display: none !important;
        }
        .woocommerce-mini-cart__buttons .wc-forward:not(.checkout) {
            font-size: 0;
        }

        .woocommerce-mini-cart__buttons .wc-forward:not(.checkout)::after {
            font-size: 0.9rem;
            content: "Checkout";
            font-family: var(--wd-title-font);
        }
    </style>';
});
