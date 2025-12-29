<?php

// app/Controllers/ImpactController.php

class ImpactController
{
    /**
     * Default affiliate templates that will be seeded on activation
     */
    const DEFAULT_TEMPLATES = [
        'affiliate-overview' => [
            'title' => 'Overview',
            'fallback' => 'overview.php',
            'order' => 1,
        ],
        'affiliate-signup' => [
            'title' => 'Sign Up',
            'fallback' => 'index.php',
            'order' => 2,
            'conditional' => 'signup', // Special handling for signup visibility
        ],
        'affiliate-commission-structure' => [
            'title' => 'Commission Structure',
            'fallback' => 'commission.php',
            'order' => 3,
        ],
        'affiliate-learning-support' => [
            'title' => 'Learning & Support',
            'fallback' => 'learning-support.php',
            'order' => 4,
        ],
        'affiliate-manage-earning' => [
            'title' => 'Manage Earnings',
            'fallback' => null,
            'order' => 5,
        ],
        'affiliate-faq' => [
            'title' => 'FAQ',
            'fallback' => 'faq.php',
            'order' => 6,
        ],
        'affiliate-terms-conditions' => [
            'title' => 'Terms & Conditions',
            'fallback' => 'terms-conditions.php',
            'order' => 7,
        ],
        'affiliate-return-refund' => [
            'title' => 'Return & Refund',
            'fallback' => 'return-refund.php',
            'order' => 8,
        ],
    ];

    /**
     * Register the affiliate_template custom post type
     */
    public static function registerAffiliatePostType()
    {
        register_post_type('affiliate_template', [
            'labels' => [
                'name' => __('Affiliate Pages', 'mytext'),
                'singular_name' => __('Affiliate Page', 'mytext'),
                'add_new' => __('Add New', 'mytext'),
                'add_new_item' => __('Add New Affiliate Page', 'mytext'),
                'edit_item' => __('Edit Affiliate Page', 'mytext'),
                'new_item' => __('New Affiliate Page', 'mytext'),
                'view_item' => __('View Affiliate Page', 'mytext'),
                'search_items' => __('Search Affiliate Pages', 'mytext'),
                'not_found' => __('No affiliate pages found', 'mytext'),
                'not_found_in_trash' => __('No affiliate pages found in trash', 'mytext'),
                'menu_name' => __('Affiliate Pages', 'mytext'),
            ],
            'public' => true, // Required for Elementor to work
            'publicly_queryable' => true, // Required for Elementor preview
            'exclude_from_search' => true, // Hide from search results
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true, // Gutenberg/REST API support
            'menu_position' => 30,
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title', 'editor', 'elementor'], // Add elementor support
            'capability_type' => 'post',
            'has_archive' => false,
            'rewrite' => false,
        ]);

        // Explicitly add Elementor support for this post type
        self::addElementorSupport();

        // Seed default templates if not already created
        self::seedDefaultTemplates();
    }

    /**
     * Explicitly add Elementor support for affiliate_template
     */
    public static function addElementorSupport()
    {
        // Get Elementor's supported post types from options
        $cpt_support = get_option('elementor_cpt_support', ['page', 'post']);
        
        // Add our post type if not already included
        if (!in_array('affiliate_template', $cpt_support)) {
            $cpt_support[] = 'affiliate_template';
            update_option('elementor_cpt_support', $cpt_support);
        }
    }

    /**
     * Enable Elementor support for affiliate_template post type via filter
     */
    public static function enableElementorSupport($post_types)
    {
        if (!in_array('affiliate_template', $post_types)) {
            $post_types[] = 'affiliate_template';
        }
        return $post_types;
    }

    /**
     * Seed default affiliate templates if they don't exist
     */
    public static function seedDefaultTemplates()
    {
        // Only run once per request
        static $seeded = false;
        if ($seeded) {
            return;
        }
        $seeded = true;

        // Check if we already have templates
        $existing = get_posts([
            'post_type' => 'affiliate_template',
            'posts_per_page' => 1,
            'post_status' => 'any',
        ]);

        if (!empty($existing)) {
            return; // Templates already exist
        }

        foreach (self::DEFAULT_TEMPLATES as $slug => $config) {
            $post_id = wp_insert_post([
                'post_title' => $config['title'],
                'post_name' => $slug,
                'post_type' => 'affiliate_template',
                'post_status' => 'publish',
                'menu_order' => $config['order'],
                'meta_input' => [
                    '_affiliate_fallback_view' => $config['fallback'] ?? '',
                    '_affiliate_conditional' => $config['conditional'] ?? '',
                    '_wp_page_template' => 'elementor_canvas', // Use Elementor Canvas template
                ],
            ]);

            // Set Elementor canvas template
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_elementor_template_type', 'wp-page');
                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
            }
        }

        // Flush rewrite rules after seeding
        flush_rewrite_rules();
    }

    /**
     * Set Elementor Canvas template for newly created affiliate templates
     */
    public static function setElementorCanvasTemplate($post_id, $post, $update)
    {
        // Only for affiliate_template post type
        if ($post->post_type !== 'affiliate_template') {
            return;
        }

        // Only for new posts (not updates)
        if ($update) {
            return;
        }

        // Set Elementor Canvas template
        update_post_meta($post_id, '_wp_page_template', 'elementor_canvas');
        update_post_meta($post_id, '_elementor_template_type', 'wp-page');
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
    }

    /**
     * Get all affiliate templates
     */
    public static function getAffiliateTemplates()
    {
        static $templates = null;
        
        if ($templates !== null) {
            return $templates;
        }

        $templates = get_posts([
            'post_type' => 'affiliate_template',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ]);

        return $templates;
    }

    /**
     * Render Elementor content for a template, with fallback to PHP view
     * Wraps content with my-account compatible structure
     */
    public static function renderTemplate($slug)
    {
        $template = get_page_by_path($slug, OBJECT, 'affiliate_template');
        
        if ($template && class_exists('\Elementor\Plugin')) {
            $elementor = \Elementor\Plugin::instance();
            $content = $elementor->frontend->get_builder_content_for_display($template->ID);
            
            if (!empty($content)) {
                // Wrap Elementor content with my-account compatible structure
                echo '<div class="profile-right-content affiliate-elementor-content">';
                echo '<div class="setting-user-profile">';
                echo $content;
                echo '</div>';
                echo '</div>';
                return;
            }
        }

        // Fallback to PHP template
        $fallback = $template ? get_post_meta($template->ID, '_affiliate_fallback_view', true) : null;
        
        // Check default templates for fallback
        if (!$fallback && isset(self::DEFAULT_TEMPLATES[$slug])) {
            $fallback = self::DEFAULT_TEMPLATES[$slug]['fallback'];
        }

        if ($fallback && file_exists(SENHENG_CORE_VIEW_PATH . 'impact/' . $fallback)) {
            include SENHENG_CORE_VIEW_PATH . 'impact/' . $fallback;
        } else {
            // Default message if no content - also wrapped
            echo '<div class="profile-right-content affiliate-no-content">';
            echo '<div class="setting-user-profile">';
            echo '<p>' . __('Content coming soon.', 'mytext') . '</p>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Hide admin bar when editing affiliate_template in Elementor
     */
    public static function hideAdminBarForElementor($show)
    {
        // Check if we're in Elementor preview/edit mode for affiliate_template
        if (isset($_GET['elementor-preview']) || isset($_GET['action']) && $_GET['action'] === 'elementor') {
            $post_id = isset($_GET['elementor-preview']) ? intval($_GET['elementor-preview']) : get_the_ID();
            if ($post_id && get_post_type($post_id) === 'affiliate_template') {
                return false;
            }
        }
        return $show;
    }

    /**
     * Add CSS to hide admin bar in Elementor editor for affiliate templates
     */
    public static function hideAdminBarStyles()
    {
        if (!is_admin() && (isset($_GET['elementor-preview']) || isset($_GET['action']) && $_GET['action'] === 'elementor')) {
            $post_id = isset($_GET['elementor-preview']) ? intval($_GET['elementor-preview']) : get_the_ID();
            if ($post_id && get_post_type($post_id) === 'affiliate_template') {
                echo '<style>
                    #wpadminbar { display: none !important; }
                    html { margin-top: 0 !important; }
                    body.admin-bar { margin-top: 0 !important; }
                </style>';
            }
        }
    }

    /**
     * Dynamic endpoint callback for any affiliate template
     */
    public static function renderDynamicTemplate()
    {
        global $wp_query;
        
        // Find which endpoint was requested
        foreach (self::getAffiliateTemplates() as $template) {
            $slug = $template->post_name;
            if (isset($wp_query->query_vars[$slug])) {
                self::renderTemplate($slug);
                return;
            }
        }
        
        // Also check default templates
        foreach (array_keys(self::DEFAULT_TEMPLATES) as $slug) {
            if (isset($wp_query->query_vars[$slug])) {
                self::renderTemplate($slug);
                return;
            }
        }
    }

    public static function enqueueAssets()
    {
        // // Only load assets for logged-in users with ambassador IDs
        // if (!is_user_logged_in()) {
        //     return;
        // }

        $current_user_id = get_current_user_id();
        $ambassadorID = get_user_meta($current_user_id, 'ambassodor_id', true);
        $userAgent = get_userAgent();
        $appShareLink = null;
        if ($userAgent === 'SRC') {
            $appShareLink = self::generateAppShareLink($current_user_id);
        }

        // // Don't load assets if user doesn't have an ambassador ID
        // if (empty($ambassadorID)) {
        //     return;
        // }

        wp_enqueue_style(
            'impact-styles',
            SENHENG_CORE_URL . 'assets/css/impact.css'
        );

        wp_enqueue_script(
            'impact-share-script',
            SENHENG_CORE_URL . 'assets/js/impact.js',
            ['jquery'],
            '1.0',
            true
        );

        global $product;
        $prodsku = '';
        $permalink = '';

        if (is_product()) {
            if (!is_a($product, 'WC_Product')) {
                $product = wc_get_product(get_the_ID());
            }
            $prodsku = $product ? $product->get_sku() : '';
            $permalink = $product ? get_permalink($product->get_id()) : '';
        }

        $default_message = "I thought you'd like this! Spotted a deal worth sharing, check out through my link on the Senheng website:";
        $share_message   = apply_filters('senheng_share_message', $default_message);

        wp_localize_script('impact-share-script', 'shareData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'ambassadorID' => $ambassadorID,
            'prodsku' => $prodsku,
            'permalink' => $permalink,
            'shareMessage' => $share_message,
            'userAgent' => $userAgent,
            'appShareLink' => $appShareLink,
        ]);
    }


    public static function addMenuItem($items)
    {
        $current_user_id = get_current_user_id();
        $config = woo_authorization_salt();
        $idsso = get_user_meta($current_user_id, 'idsso', true);
        if (!$idsso) {
            update_user_meta($current_user_id, 'impact_ambassador_sign_up', false);
        }

        $has_signed_up = get_user_meta($current_user_id, 'impact_ambassador_sign_up', true);

        $is_ambassador = $has_signed_up ? true : self::get_ambassador_id($current_user_id, $config, $idsso);

        if ($is_ambassador) {
            update_user_meta($current_user_id, 'impact_ambassador_sign_up', true);
        }

        $new = [];

        foreach ($items as $key => $value) {
            if ($key === 'customer-logout') {
                // Parent
                $new['affiliate-program'] = __('Senheng Affiliate Program', 'mytext');

                // Get dynamic menu items from affiliate_template post type
                $templates = self::getAffiliateTemplates();
                
                if (!empty($templates)) {
                    foreach ($templates as $template) {
                        $slug = $template->post_name;
                        $title = $template->post_title;
                        $conditional = get_post_meta($template->ID, '_affiliate_conditional', true);
                        
                        // Handle conditional visibility (e.g., signup only for non-ambassadors)
                        if ($conditional === 'signup') {
                            if (!$is_ambassador && $idsso) {
                                $new[$slug] = __($title, 'mytext');
                            }
                        } else {
                            $new[$slug] = __($title, 'mytext');
                        }
                    }
                } else {
                    // Fallback to default templates if no templates exist yet
                    foreach (self::DEFAULT_TEMPLATES as $slug => $config) {
                        $conditional = $config['conditional'] ?? '';
                        
                        if ($conditional === 'signup') {
                            if (!$is_ambassador && $idsso) {
                                $new[$slug] = __($config['title'], 'mytext');
                            }
                        } else {
                            $new[$slug] = __($config['title'], 'mytext');
                        }
                    }
                }
            }

            $new[$key] = $value;
        }

        return $new;
    }

    public static function addEndpoint()
    {
        // Always register parent endpoint
        add_rewrite_endpoint('affiliate-program', EP_ROOT | EP_PAGES);
        
        // Get templates and register their endpoints
        $templates = self::getAffiliateTemplates();
        
        if (!empty($templates)) {
            foreach ($templates as $template) {
                add_rewrite_endpoint($template->post_name, EP_ROOT | EP_PAGES);
            }
        } else {
            // Fallback: register default endpoints if no templates exist yet
            foreach (array_keys(self::DEFAULT_TEMPLATES) as $slug) {
                add_rewrite_endpoint($slug, EP_ROOT | EP_PAGES);
            }
        }
    }

    /**
     * Register dynamic WooCommerce endpoint actions
     * Called after post type is registered
     */
    public static function registerDynamicEndpointActions()
    {
        $templates = self::getAffiliateTemplates();
        
        if (!empty($templates)) {
            foreach ($templates as $template) {
                $slug = $template->post_name;
                add_action('woocommerce_account_' . $slug . '_endpoint', function() use ($slug) {
                    ImpactController::renderTemplate($slug);
                });
            }
        } else {
            // Fallback: register actions for default templates
            foreach (array_keys(self::DEFAULT_TEMPLATES) as $slug) {
                add_action('woocommerce_account_' . $slug . '_endpoint', function() use ($slug) {
                    ImpactController::renderTemplate($slug);
                });
            }
        }
    }

    // Legacy render functions - now call renderTemplate() for backward compatibility
    public static function renderSignUpAffiliatePage()
    {
        self::renderTemplate('affiliate-signup');
    }

    public static function renderOverview()
    {
        self::renderTemplate('affiliate-overview');
    }

    public static function renderCommissionStructure()
    {
        self::renderTemplate('affiliate-commission-structure');
    }

    public static function renderLearningSupport()
    {
        self::renderTemplate('affiliate-learning-support');
    }

    public static function renderManageEarnings()
    {
        self::renderTemplate('affiliate-manage-earning');
    }

    public static function renderFAQ()
    {
        self::renderTemplate('affiliate-faq');
    }

    public static function renderTermsConditions()
    {
        self::renderTemplate('affiliate-terms-conditions');
    }

    public static function renderReturnRefund()
    {
        self::renderTemplate('affiliate-return-refund');
    }

    public static function restrictUrlSignUp()
    {
        // Must be logged in
        if (!is_user_logged_in()) {
            wp_redirect(site_url('/'));
            exit;
        }

        $config = woo_authorization_salt();
        $user_id       = get_current_user_id();
        $idsso         = get_user_meta($user_id, 'idsso', true);
        $has_signed_up = get_user_meta($user_id, 'impact_ambassador_sign_up', true);
        $is_ambassador = $has_signed_up ? true : self::get_ambassador_id($user_id, $config, $idsso);

        // If user has already signed up
        if (!$is_ambassador && $idsso) {
            // wp_redirect(site_url('/my-account/affiliate-signup/'));
            // exit;
        } else {
            wp_redirect(site_url('/'));
            exit;
        }
    }


    public static function impact_affiliate_signup()
    {
        //validate fields
        if (empty($_POST['email']) || empty($_POST['username']) || empty($_POST['staff_code'])) {
            wp_send_json_error(['message' => __('All fields are required', 'mytext')]);
        }

        if (
            !isset($_POST['affiliate_signup_nonce']) ||
            !wp_verify_nonce($_POST['affiliate_signup_nonce'], 'affiliate_signup')
        ) {
            wp_send_json_error(['message' => __('Nonce verification failed', 'mytext')]);
        }

        $current_user_id = get_current_user_id();

        $split_name = split_cust_name($current_user_id);

        if (!empty($split_name['first_name']) && !empty($split_name['last_name'])) {
            $first_name = sanitize_text_field($split_name['first_name']);
            $last_name = sanitize_text_field($split_name['last_name']);
        } else {
            $first_name = sanitize_text_field($_POST['first_name'] ?? '');
            $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        }
        $email = sanitize_email($_POST['email'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $mobile = get_user_meta($current_user_id, 'cust_contact', true);
        $code = sanitize_text_field($_POST['staff_code'] ?? '');
        $idsso = get_user_meta($current_user_id, 'idsso', true);

        // Configurable keys
        $config = woo_authorization_salt();

        $postData = [
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
            'username'   => $username,
            'mobile'     => $mobile,
            'code'       => $code,
            'from_tab'   => "2",
            'guid'       => $idsso,
        ];

        $required_fields = [
            'first_name' => __('First Name', 'mytext'),
            'last_name'  => __('Last Name', 'mytext'),
            'email'      => __('Email', 'mytext'),
            'username'   => __('Username', 'mytext'),
            'mobile'     => __('Mobile', 'mytext'),
            'code'       => __('Code', 'mytext'),
            'guid'       => __('ID SSO', 'mytext'),
        ];

        $missing = [];

        foreach ($required_fields as $key => $label) {
            if (empty(trim($postData[$key] ?? ''))) {
                $missing[] = $label;
            }
        }

        // If any field is missing
        if (!empty($missing)) {
            $message = sprintf(
                __('The following fields are required: %s', 'mytext'),
                implode(', ', $missing)
            );

            wp_send_json_error(['message' => $message]);
            exit;
        }

        $response = wp_remote_post(
            $config['api_url'] . '/ambassodor_create',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $config['customer_key'],
                    'Timestamp'    => $config['timestamp'],
                    'Authorization' => $config['hash'],
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'      => json_encode($postData),
                'timeout'   => 15,
            ]
        );

        $body = json_decode(wp_remote_retrieve_body($response), true);

        // $status_code = 201;
        // $body = '{"body":{"message":"Record inserted","status":"SUCCESS"},"code":201,"errors":null,"status":"SUCCESS","success":true}';
        // $body = json_decode($body, true);

        if (
            in_array($body['code'], [200, 201]) &&
            isset($body['status']) &&
            strtolower($body['status']) === 'success' &&
            isset($body['success']) &&
            $body['success'] === true
        ) {
            update_user_meta($current_user_id, 'impact_ambassador_sign_up', true);

            // Get Ambassador ID
            self::get_ambassador_id($current_user_id, $config, $idsso);

            wp_send_json([
                'status'  => 'success',
                'success' => true,
                'code'    => 201,
                'message' => $body['body']['message'] ?? __('Sign Up successful!', 'mytext'),
                'data'    => [
                    'redirect_url' => wc_get_account_endpoint_url('dashboard')
                ]
            ]);
        } else {
            $errorMessage = null;

            if (isset($body['errors'][0]['message'])) {
                $errorMessage = $body['errors'][0]['message'];
            } elseif (isset($body['Errors'])) {
                $errorMessage = $body['Errors']; // Capital E
            } elseif (isset($body['message'])) {
                $errorMessage = $body['message'];
            } elseif (isset($body['error'])) {
                $errorMessage = $body['error'];
            } else {
                $errorMessage = __('Signup failed. Please try again.', 'mytext');
            }

            wp_send_json([
                'status'  => 'error',
                'success' => false,
                'code'    => $body['code'] ?: 400,
                'message' => $errorMessage
            ]);
        }
    }

    public static function get_ambassador_id($current_user_id, $config, $idsso)
    {
        // Already have ambassador ID? Done.
        $cached = get_user_meta($current_user_id, 'ambassodor_id', true);
        if (!empty($cached)) {
            return $cached;
        }

        if (!$current_user_id || !$config || !$idsso) {
            return false;
        }

        $response = wp_remote_post(
            $config['api_url'] . '/ambassodor_retrieve',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $config['customer_key'],
                    'Timestamp'    => $config['timestamp'],
                    'Authorization' => $config['hash'],
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/json',
                ],
                'body' => [
                    'mousername' => $idsso
                ],
                'timeout'   => 15,
            ]
        );

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['body']['ambassodor_id']) && !empty($body['body']['is_ambassador'])) {

            update_user_meta($current_user_id, 'ambassodor_id', $body['body']['ambassodor_id']);

            return $body['body']['ambassodor_id'];
        }

        return false;
    }


    public static function pingShare()
    {
        $prodsku = sanitize_text_field($_POST['prodsku'] ?? '');
        $permalink = esc_url_raw($_POST['permalink'] ?? '');
        $ping_url = esc_url_raw($_POST['ping_url'] ?? '');
        $share_url = esc_url_raw($_POST['share_url'] ?? '');

        if (empty($prodsku) || empty($permalink) || empty($ping_url)) {
            wp_send_json_error(['message' => 'Missing data']);
        }

        $response = wp_remote_get($ping_url);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'status' => 'error',
                'message' => 'Ping failed'
            ]);
        }

        wp_send_json_success();
    }

    public static function send_impact_conversion_payload($order_id)
    {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        if (! $order->has_status(array('processing', 'completed'))) {
            return;
        }

        $user_id = $order->get_user_id();
        $idsso   = get_user_meta($user_id, 'idsso', true);

        if (!$idsso) {
            return;
        }

        $click_id = $_COOKIE['irclickid'] ?? null;
        custom_log('send_impact_conversion_payload - click_id: ' . json_encode(($click_id)));
        if (!$click_id) {
            return;
        }
        update_post_meta($order_id, '_irclickid', sanitize_text_field($click_id));

        $payload = [
            'event_date'       => date('Y-m-d H:i:s'),
            'click_id'         => $click_id,
            'order_id'         => $order->get_order_number(),
            'customer_id'      => $idsso,
            'customer_email' => sha1($order->get_billing_email()),
            'order_discount'   => floatval($order->get_discount_total()),
            'order_promo_code' => implode(',', $order->get_coupon_codes()),
            'platform'         => getChannelWeb(),
            'channel'          => getChannelWeb(),
            'items'            => [],
        ];
        custom_log('send_impact_conversion_payload - payload: ' . json_encode(($payload)));
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;
            $parent_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
            // Get Brand
            $brands = wp_get_post_terms($parent_id, 'product_brand');
            $brand = (!empty($brands)) ? $brands[0]->name : 'Unknown';
            // Get Category
            $terms = get_the_terms($parent_id, 'product_cat');
            $category = (!is_wp_error($terms) && !empty($terms)) ? implode(', ', wp_list_pluck($terms, 'name')) : '';

            $payload['items'][] = [
                'brand'         => $brand,
                'category'      => $category,
                'sku'           => $product->get_sku(),
                'sub_total'     => floatval($item->get_subtotal()),
                'quantity'      => $item->get_quantity(),
                'name'          => $item->get_name(),
            ];
        }

        // Optionally log it for debug
        // error_log("🔁 Sending impact payload:\n" . json_encode($payload, JSON_PRETTY_PRINT));
        $config = woo_authorization_salt();

        $response = wp_remote_post(
            $config['api_url'] . '/event_conversion',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $config['customer_key'],
                    'Timestamp'    => $config['timestamp'],
                    'Authorization' => $config['hash'],
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'      => json_encode($payload),
                'timeout'   => 15,
            ]
        );

        $body = json_decode(wp_remote_retrieve_body($response), true);

        log_api_request(
            $config['api_url'] . '/event_conversion',
            'POST',
            json_encode($payload),
            wp_remote_retrieve_response_code($response),
            wp_remote_retrieve_body($response),
            isset($body['message']) ? $body['message'] : ''
        );
    }

    public static function handle_order_status_change($order_id, $old_status, $new_status, $order)
    {
        log_api_request(
            'Order Status Change',
            'N/A',
            "Order ID: $order_id, Old Status: $old_status, New Status: $new_status",
            200,
            'Status change detected',
            ''
        );

        $in_array = ['cancelled', 'failed', 'to-refund', 'to-return'];
        if (in_array($new_status, $in_array)) {
            // Get the refund ID
            // $refunds = $order->get_refunds();
            // if (!empty($refunds)) {
            //     $latest_refund = end($refunds);
            //     $refund_id = $latest_refund->get_id();
            // self::send_impact_reversion_payload($order_id, null);
            // }
            $irclickid = get_post_meta($order_id, '_irclickid', true);
            if (!empty($irclickid)) {
                self::send_impact_reversion_payload($order_id, null);
            }
        }
    }

    public static function send_impact_reversion_payload($order_id, $refund_id)
    {
        if (!$order_id) return;

        $order = wc_get_order($order_id);
        if (!$order) return;

        $reason = 'OTHER';

        $order_refunds = $order->get_refunds();

        // foreach ($order_refunds as $refund) {
        //     if ($refund->get_id() == $refund_id) {
        //         $reason = $refund->get_reason() ?: 'OTHER';
        //         break;
        //     }
        // }
        $user_id = $order->get_user_id();
        $idsso   = get_user_meta($user_id, 'idsso', true);

        // if (!$idsso) {
        //     return;
        // }

        $config = woo_authorization_salt();

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $sku = $product->get_sku();
            $amount = floatval($item->get_total());
            $quantity = $item->get_quantity();

            $payload = [
                'oid'      => $order->get_order_number(),
                'amount'   => 0,
                'reason'   => $reason,
                'sku'      => $sku,
                'quantity' => 0,
                'platform' => getChannelWeb(),
                'channel'  => getChannelWeb(),
            ];

            $response = wp_remote_post(
                $config['api_url'] . '/reverse_conversion',
                [
                    'method'    => 'POST',
                    'headers'   => [
                        'Customer-Key'  => $config['customer_key'],
                        'Timestamp'     => $config['timestamp'],
                        'Authorization' => $config['hash'],
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ],
                    'body'      => json_encode($payload),
                    'timeout'   => 30,
                ]
            );

            $body = json_decode(wp_remote_retrieve_body($response), true);

            log_api_request(
                $config['api_url'] . '/reverse_conversion',
                'POST',
                json_encode($payload),
                wp_remote_retrieve_response_code($response),
                wp_remote_retrieve_body($response),
                isset($body['message']) ? $body['message'] : ''
            );
        }
    }


    public static function handle_refund($order_id, $refund_id)
    {
        $order  = wc_get_order($order_id);
        $refund = wc_get_order($refund_id);

        if (!$order || !$refund) {
            return;
        }

        $irclickid = get_post_meta($order_id, '_irclickid', true);
        if (empty($irclickid)) {
            return;
        }

        $reason = 'OTHER';

        $config = woo_authorization_salt();

        // Loop only the items refunded in THIS refund
        foreach ($refund->get_items('line_item') as $ref_item_id => $ref_item) {

            $orig_item_id = (int) $ref_item->get_meta('_refunded_item_id');

            $product  = $ref_item->get_product();
            if (!$product) {
                continue;
            }

            $sku      = $product->get_sku();
            $qty      = absint($ref_item->get_quantity());
            $amount   = abs((float) $ref_item->get_total());

            // Build the payload for THIS refunded item
            $payload = [
                'oid'      => $order->get_order_number(),
                'amount'   => $amount,
                'reason'   => $reason,
                'sku'      => $sku,
                'quantity' => $qty,
                'platform' => getChannelWeb(),
                'channel'  => getChannelWeb(),
            ];

            $response = wp_remote_post(
                $config['api_url'] . '/reverse_conversion',
                [
                    'method'  => 'POST',
                    'headers' => [
                        'Customer-Key'  => $config['customer_key'],
                        'Timestamp'     => $config['timestamp'],
                        'Authorization' => $config['hash'],
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json',
                    ],
                    'body'    => wp_json_encode($payload),
                    'timeout' => 30,
                ]
            );

            $body_str = wp_remote_retrieve_body($response);
            $body     = json_decode($body_str, true);

            log_api_request(
                $config['api_url'] . '/reverse_conversion',
                'POST',
                wp_json_encode($payload),
                wp_remote_retrieve_response_code($response),
                $body_str,
                isset($body['message']) ? $body['message'] : ''
            );
        }
    }

    public static function generateAppShareLink($current_user_id)
    {
        $idsso = get_user_meta($current_user_id, 'idsso', true);
        $p1no = get_user_meta($current_user_id, 'cust_p1no', true);
        if (!$idsso) {
            return null;
        }
        // Configurable keys
        $config = woo_authorization_salt();

        global $post;

        if ($post && $post->post_type === 'product') {
            $product = wc_get_product($post->ID);

            $product_title = $product ? $product->get_name() : '';
            $product_desc = $product ? $product->get_short_description() : '';
            $product_image_url = $product ? wp_get_attachment_url($product->get_image_id()) : '';
            $prodsku = $product ? $product->get_sku() : '';
            $product_url = $product ? get_permalink($product->get_id()) : '';
        }
        if (empty($prodsku) || empty($product_url)) {
            return null;
        }

        $postData = [
            'mousername' => $idsso,
            'is_invite_link' => 3,
            'deep_link_value' => 'shapp:///service::S-Rewards Centre::0::0::' . $product_url . '?',
            'p1no' => $p1no,
            'campaign_type' => 1,
            'body' => [
                '$og_title' => $product_title ?? '',
                '$og_description' => $product_desc ?? '',
                '$og_image_url' => $product_image_url ?? '',
                '$marketing_title' => getChannelWeb() . ' App',
                '$deeplink_path' => 'shapp:///service::S-Rewards Centre::0::0::' . $product_url . '?',
                '$fallback_url' => '',
                'og_product_sku_ids' => $prodsku,
            ],
            'type' => 2,
            'alias' => '',
            'duration' => 7200,
        ];

        $response = wp_remote_post(
            $config['api_url'] . '/sharelink',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $config['customer_key'],
                    'Timestamp'    => $config['timestamp'],
                    'Authorization' => $config['hash'],
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'      => json_encode($postData),
                'timeout'   => 15,
            ]
        );

        $body = json_decode(wp_remote_retrieve_body($response), true);

        log_api_request(
            $config['api_url'] . '/sharelink',
            'POST',
            json_encode($postData),
            wp_remote_retrieve_response_code($response),
            wp_remote_retrieve_body($response),
            is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response),
        );

        if (isset($body['status']) && $body['status'] === 'SUCCESS') {
            $onelink = isset($body['body']['onelink']) ? $body['body']['onelink'] : null;
            $shortlinkid = isset($body['body']['shorlinkid']) ? $body['body']['shorlinkid'] : null;

            return [
                'onelink' => $onelink,
                'shortlinkid' => $shortlinkid,
            ];
        }

        return null;
    }
}
