<?php

add_action('init', function () {
    // ensure when on my-account page, it redirects to the login page if not logged in
    [LoginController::class, 'registerLoginRewrite']();
    [RegisterController::class, 'registerRegisterRewrite']();
    force_login_page();
});


add_action('template_redirect', [PwaSessionController::class, 'shweb_auto_login_from_token']);
add_action('wp_head', [HeaderController::class, 'renderPWAHeader']);
add_action('wp_head', [PwaSessionController::class, 'hideHeaderFooter']);

// RegisterController routes
add_action('template_redirect', [RegisterController::class, 'handleTemplateRedirect']);
add_filter('woocommerce_enable_myaccount_registration', '__return_false');
add_action('wp_ajax_checking_phone', [RegisterController::class, 'checkingPhone']);
add_action('wp_ajax_nopriv_checking_phone', [RegisterController::class, 'checkingPhone']);
add_action('wp_ajax_request_otp', [RegisterController::class, 'requestOtp']);
add_action('wp_ajax_nopriv_request_otp', [RegisterController::class, 'requestOtp']);
add_action('wp_ajax_verify_otp', [RegisterController::class, 'verifyOtp']);
add_action('wp_ajax_nopriv_verify_otp', [RegisterController::class, 'verifyOtp']);
add_action('wp_ajax_create_user', [RegisterController::class, 'createUser']);
add_action('wp_ajax_nopriv_create_user', [RegisterController::class, 'createUser']);

// LoginController routes
add_action('template_redirect', [LoginController::class, 'handleTemplateRedirect']);
add_action('wp_ajax_login', [LoginController::class, 'login']);
add_action('wp_ajax_nopriv_login', [LoginController::class, 'login']);
add_action('wp_ajax_social_login', [LoginController::class, 'socialLogin']);
add_action('wp_ajax_nopriv_social_login', [LoginController::class, 'socialLogin']);
add_action('wp_footer', [LoginController::class, 'enqueueAssetsPopup']);

//Impact Controller routes and actions (Can be Edit by Elementor)
add_action('wp_enqueue_scripts', [ImpactController::class, 'enqueueAssets']);
add_action('init', [ImpactController::class, 'registerAffiliatePostType']);
add_filter('elementor/cpt_support', [ImpactController::class, 'enableElementorSupport']);
add_filter('show_admin_bar', [ImpactController::class, 'hideAdminBarForElementor']);
add_action('wp_head', [ImpactController::class, 'hideAdminBarStyles']);
add_action('init', [ImpactController::class, 'addEndpoint']);
add_filter('woocommerce_account_menu_items', [ImpactController::class, 'addMenuItem']);
add_action('wp', [ImpactController::class, 'registerDynamicEndpointActions']);
add_action('wp_insert_post', [ImpactController::class, 'setElementorCanvasTemplate'], 10, 3);

add_action('wp_ajax_impact_affiliate_signup', [ImpactController::class, 'impact_affiliate_signup']);
add_action('wp_ajax_nopriv_impact_affiliate_signup', [ImpactController::class, 'impact_affiliate_signup']);
add_action('woocommerce_account_affiliate-signup_endpoint', [ImpactController::class, 'restrictUrlSignUp']);

add_action('wp_ajax_ping_share_link', [ImpactController::class, 'pingShare']);
add_action('wp_ajax_nopriv_ping_share_link', [ImpactController::class, 'pingShare']);

// Share popup (product share modal)
add_action('wp_enqueue_scripts', [SharePopupController::class, 'enqueueAssets']);
add_action('wp_footer', [SharePopupController::class, 'renderModal']);
add_action('whb_after_header', [SharePopupController::class, 'injectHeaderShareButton']);

//PAID | may to change for paid only
add_action('woocommerce_thankyou', [ImpactController::class, 'send_impact_conversion_payload'], 10, 1);
// add_action('woocommerce_payment_complete', [ImpactController::class, 'send_impact_conversion_payload'], 10, 1);
add_action('woocommerce_order_status_changed', [ImpactController::class, 'handle_order_status_change'], 10, 4);
add_action('woocommerce_order_refunded', function ($order_id, $refund_id) {
    ImpactController::handle_refund($order_id, $refund_id);
}, 10, 2);
// Show IR Click ID in the order edit page
add_action('woocommerce_admin_order_data_after_order_details', function ($order) {
    $irclickid = get_post_meta($order->get_id(), '_irclickid', true);
    if ($irclickid) {
        echo '<p class="form-field form-field-wide" style="margin-top: 30px"><strong>Impact Click ID:</strong> ' . esc_html($irclickid) . '</p>';
    }

    $payment_type = get_post_meta($order->get_id(), '_ipay88_payment_type_name', true);
    $payment_plan = (int) get_post_meta($order->get_id(), '_ipay88_payment_plan', true);

    if ($payment_type) {

        $label = $payment_type;

        // Append "x {months}" only if payment plan > 0
        if ($payment_plan > 0) {
            $label .= ' x ' . $payment_plan . ' month(s)';
        }

        echo '<p class="form-field form-field-wide" style="margin-top: 30px"><strong>Payment Method:</strong> ' . esc_html($label) . '</p>';
    }
});


// Warranty Controller
// Store per-item selections in session
add_action('wp_ajax_set_warranty_selection', [WarrantyController::class, 'ajaxSetWarrantySelection']);
add_action('wp_ajax_nopriv_set_warranty_selection', [WarrantyController::class, 'ajaxSetWarrantySelection']);

// Add fees based on session on Cart & Checkout
add_action('woocommerce_cart_calculate_fees', [WarrantyController::class, 'applyWarrantyFees'], 20, 1);

// Keep your existing decorateCartItemName (we’ll read/write a new data attr)
add_filter('woocommerce_cart_item_name', [WarrantyController::class, 'decorateCartItemName'], 20, 3);

// JS injectors
add_action('wp_footer', [WarrantyController::class, 'injectCartCheckboxScript']);
// add_action('wp_footer', [WarrantyController::class, 'injectCheckoutCheckboxScript']);

//Store in Order Item Meta
add_action('woocommerce_checkout_create_order_line_item', [WarrantyController::class, 'store_warranty_in_order_item'], 10, 4);


// Installment Controller
// Ensure payment methods table has all required columns
add_action('admin_init', [PaymentMethod::class, 'ensureTableColumns']);
add_action('wp_enqueue_scripts', [InstallmentController::class, 'sh_enqueue_bnpl_assets']);
add_action('wp_ajax_create_payment_method', [InstallmentController::class, 'createPaymentMethod']);
add_action('wp_ajax_create_payment_plan', [InstallmentController::class, 'createPaymentPlan']);
add_action('woocommerce_single_product_summary', [InstallmentController::class, 'payment_plan_front_end']);
add_action('wp_ajax_senheng_exclude_brands', [InstallmentController::class, 'senheng_exclude_brands']);

// Benefit Box Shortcode (MVC)
add_action('wp_enqueue_scripts', [BenefitBoxController::class, 'enqueueAssets']);
add_action('init', [BenefitBoxController::class, 'registerShortcode']);
add_action('wp_ajax_delete_benefit_box_data', [BenefitBoxController::class, 'handleDeleteBenefitBoxData']);
add_action('wp_ajax_update_benefit_box_status', [BenefitBoxController::class, 'handleUpdateBenefitBoxStatus']);
add_action('wp_ajax_bulk_delete_benefit_boxes', [BenefitBoxController::class, 'handleBulkDeleteBenefitBoxes']);
add_action('init', [BenefitBoxController::class, 'registerProductWarrantyShortcode']);

// Benefit Box Admin AJAX routes
add_action('wp_ajax_save_benefit_box_data', [BenefitBoxController::class, 'handleSaveBenefitBoxData']);

// Product Import/Export Controller
add_action('init', [ImportExportWoocommerceController::class, 'init']);

// Product Import Controller routes and actions
add_action('admin_enqueue_scripts', [ProductImportController::class, 'enqueueAssets']);
add_action('wp_ajax_custom_import_upload', [ProductImportController::class, 'handleUpload']);
add_action('wp_ajax_start_cli_import', [ProductImportController::class, 'startCliImport']);
add_action('wp_ajax_process_next_chunk', [ProductImportController::class, 'processNextChunk']);
// add_action('wp_ajax_clear_import_lock', [ProductImportController::class, 'clearImportLock']); // Removed - no longer using lock files
add_action('wp_ajax_clear_import_logs', [ProductImportController::class, 'clearImportLogs']);

// ACF Controller
add_filter('acf/location/rule_values/post_type', [AcfController::class, 'acf_location_rule_values_Post']);
// add_filter('acf/update_value/name=self_pickup', [AcfController::class, 'acf_update_value_self_pickup'], 10, 3);


// Product Loop Controller
add_action('init', [\SenhengCore\Controllers\ProductLoopController::class, 'init']);
add_filter('woocommerce_loop_product_link', '__return_empty_string', 10);
// Price and rating hooks are registered in ProductLoopController::init() to avoid conflicts
add_action('woocommerce_after_shop_loop_item_title', [\SenhengCore\Controllers\ProductLoopController::class, 'scoin_display_on_product_loop'], 15);

// S-Coin Controller
// Removed manual S-Coin Value field registration hooks (handled by ACF)
add_action('wp_enqueue_scripts', [ScoinController::class, 'sh_enqueue_s_coin_assets']);
// Removed: add_action('woocommerce_after_shop_loop_item_title', [ScoinController::class, 'scoin_display_on_product_loop'], 15); - Now handled in ProductLoopController
// add_action('woocommerce_before_single_product_summary', [ScoinController::class, 'scoin_display_on_single_product'], 10);
add_filter('woocommerce_cart_item_name', [ScoinController::class, 'scoin_display_on_cart_item'], 20, 3);
add_filter('woocommerce_cart_totals_before_order_total', [ScoinController::class, 'sh_cart_totals_scoin_row'], 20, 3);
add_filter('woocommerce_review_order_before_order_total', [ScoinController::class, 'sh_cart_totals_scoin_row'], 20, 3);
// S-Coin field in WooCommerce variation tab
add_action('woocommerce_variation_options_pricing', [ScoinController::class, 'add_variation_scoin_field'], 10, 3);
add_action('woocommerce_save_product_variation', [ScoinController::class, 'save_variation_scoin_field'], 10, 2);

// S-Coin Export/Import Support MOVED TO ImportExportWoocommerceController
// add_filter('woocommerce_product_export_column_names', [ScoinController::class, 'add_export_column']);
// add_filter('woocommerce_product_export_product_default_columns', [ScoinController::class, 'add_export_column']);
// add_filter('woocommerce_product_export_product_column_s_coin_value', [ScoinController::class, 'export_column_data'], 10, 2);
// add_filter('woocommerce_csv_product_import_mapping_options', [ScoinController::class, 'add_import_options']);
// add_filter('woocommerce_csv_product_import_mapping_default_columns', [ScoinController::class, 'add_import_mapping']);
// add_filter('woocommerce_product_importer_parsed_data', [ScoinController::class, 'handle_parsed_import_data'], 10, 2);
// add_filter('woocommerce_product_import_pre_insert_product_object', [ScoinController::class, 'import_product_data'], 10, 2);
// add_action('woocommerce_product_import_inserted_product_object', [ScoinController::class, 'import_variation_data'], 10, 2);

// Custom Add to Cart Controller routes and actions
add_action('init', [WooCommerceAddtoCartController::class, 'init']);

// Trade-In Controller
add_action('init', [TradeInController::class, 'init']);

// Cart Controller - Handle cart page product extras display
add_action('init', [CartController::class, 'init']);

// Thank You Controller - Override Funnel Builder order details
add_action('init', [ThankYouController::class, 'init']);


// Woodmart Rating Controller - Override rating display to show zero stars
add_action('init', [WoodmartRatingController::class, 'init']);

// Elementor Widgets Registration
add_action('init', [ElementorWidgetsController::class, 'init']);



// WooCommerce Variations Mobile Fixes Controller (removed Insider Product ID field)

// Payment Gateway Controller
// add_action('wp_enqueue_scripts', [PaymentGatewayController::class, 'enqueueFrontendAssets']);
// add_action('admin_enqueue_scripts', [PaymentGatewayController::class, 'enqueueAdminAssets']);
// add_action('plugins_loaded', [PaymentGatewayController::class, 'initPaymentGateway']);

// AutoSon Controller
add_filter('woocommerce_rest_prepare_shop_order_object', [AutoSonController::class, 'includeUserMeta'], 10, 3);
add_action('rest_api_init', function () {
    register_rest_route('shweb/v1', '/get-paid-orders', [
        'methods'  => 'POST', // switched from GET to POST
        'callback' => [AutoSonController::class, 'get_paid_orders'],
        'permission_callback' => [AutoSonController::class, 'shweb_validate_woocommerce_auth']
    ]);
});

//Checkout Controller
add_action('wp', [CheckoutController::class, 'init_checkout_page_hooks']);
add_filter('woocommerce_checkout_fields', [CheckoutController::class, 'custom_virtual_checkout_fields']);
add_action('woocommerce_checkout_process', [CheckoutController::class, 'validate_custom_virtual_fields']);
add_action('woocommerce_checkout_update_order_meta', [CheckoutController::class, 'save_custom_virtual_fields']);
add_filter('woocommerce_add_to_cart_validation', [CheckoutController::class, 'restrict_virtual_and_physical_cart'], 10, 3);
add_action('woocommerce_check_cart_items', [CheckoutController::class, 'filter_wc_check_cart_items']);

add_filter('woocommerce_checkout_fields', [CheckoutController::class, 'register_einvoice_fields']);
add_action('woocommerce_after_checkout_validation', [CheckoutController::class, 'validate_einvoice_fields'], 10, 2);
add_action('woocommerce_checkout_create_order', [CheckoutController::class, 'save_einvoice_fields'], 10, 2);
add_action('wp_footer', [CheckoutController::class, 'show_hide_e_invoice_fields']);
add_filter('woocommerce_form_field', [CheckoutController::class, 'remove_optional_label'], 10, 4);

add_action('woocommerce_checkout_create_order_line_item', [CheckoutController::class, 'add_scoin_to_order_item'], 10, 4);
add_action('woocommerce_checkout_update_order_meta', [CheckoutController::class, 'update_order_meta_with_scoin'], 10, 2);
add_action('woocommerce_checkout_update_order_meta', [CheckoutController::class, 'capture_raw_checkout_post'], 5, 2);

add_action('wp_enqueue_scripts', [CheckoutController::class, 'enqueue_checkout_assets']);
add_action('woocommerce_checkout_cart_item_quantity', [CheckoutController::class, 'display_product_extras_in_checkout_after_quantity'], 10, 3);
add_filter('woocommerce_cart_item_subtotal', [CheckoutController::class, 'modify_checkout_item_subtotal'], 2001, 3);

// Order Meta Controller - Admin & Display
add_action('init', [OrderMetaController::class, 'init']);
add_action('woocommerce_checkout_create_order_line_item', [CheckoutController::class, 'save_product_extras_to_order_item'], 20, 4);

//BNPL - iPay88 Admin Fee Update Handler
add_action('wp_ajax_update_ipay88_admin_fee', [CheckoutController::class, 'update_ipay88_admin_fee_callback']);
add_action('wp_ajax_nopriv_update_ipay88_admin_fee', [CheckoutController::class, 'update_ipay88_admin_fee_callback']);
add_action('woocommerce_cart_calculate_fees', [CheckoutController::class, 'add_ipay88_admin_fee_to_cart']);

// add_action('woocommerce_review_order_after_order_total', [CheckoutController::class, 'display_ipay88_admin_fee_note']);
add_action('woocommerce_checkout_order_processed', [CheckoutController::class, 'clear_ipay88_admin_fee']);
add_action('woocommerce_before_checkout_form', [CheckoutController::class, 'clear_ipay88_admin_fee'], 5);
add_action('woocommerce_before_cart', [CheckoutController::class, 'clear_ipay88_admin_fee'], 5);

// MyAccount Controller
add_filter('woocommerce_locate_template', [MyAccountController::class, 'overide_account_details'], 10, 3);

// SplashController
// add_action('wp_head', [SplashController::class, 'header']);
// add_action('wp_footer', [SplashController::class, 'footer']);

// HTTP Logger for debugging API calls
add_action('http_api_debug', [HttpLogger::class, 'listen'], 10, 5);


// Flixmedia Controller
add_action('init', [FlixmediaController::class, 'add_mpn_field']);
add_action('wp_footer', [FlixmediaController::class, 'flixmedia_dynamic_script']);

// OneSync Controller
// add_action('init', [OneSyncController::class, 'add_1ws_field']); 
add_action('wp_footer', [OneSyncController::class, 'one_sync_dynamic_script']);

add_action('init', [LimitedTimeOfferController::class, 'init']);

// Product Sync Controller (bidirectional sync with remote WooCommerce site)
add_action('init', [ProductSyncController::class, 'init']);

// Coupon Controller (deposit/full payment restrictions)
add_action('init', [CouponController::class, 'init']);

// Product Feed Controller (WPVIP compatibility - redirect feed files to /tmp)
ProductFeedController::init();
