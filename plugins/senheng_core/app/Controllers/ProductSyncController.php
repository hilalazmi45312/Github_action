<?php

/**
 * Product Sync Controller
 *
 * Bidirectional sync with another WooCommerce website using REST API.
 * - Push: When local products are created/updated/deleted, sync to remote
 * - Pull: Receive webhooks from remote site and update local products
 */
class ProductSyncController
{
    /**
     * Option keys for storing settings
     */
    const OPTION_REMOTE_URL = 'senheng_sync_remote_url';
    const OPTION_CONSUMER_KEY = 'senheng_sync_consumer_key';
    const OPTION_CONSUMER_SECRET = 'senheng_sync_consumer_secret';
    const OPTION_AUTO_SYNC_ENABLED = 'senheng_sync_auto_enabled';
    const OPTION_SYNC_DIRECTION = 'senheng_sync_direction'; // push, pull, both
    const OPTION_WEBHOOK_SECRET = 'senheng_sync_webhook_secret';

    /**
     * Flag to prevent infinite sync loops
     */
    private static $is_syncing = false;

    /**
     * Custom fields to sync (matches ImportExportWoocommerceController)
     */
    const CUSTOM_FIELDS = [
        'mpn' => 'MPN',
        'pn' => 'Part Number (PN)',
        '_global_unique_id' => 'Global Unique ID',
        's_coin_value' => 'S-Coin Value (%)',
        '_awcdp_deposit_enabled' => 'Trade In Deposit',
        '_awcdp_deposit_type' => 'Deposit Type',
        '_awcdp_deposits_deposit_amount' => 'Deposit Amount',
        'warranty_enabled_sh' => 'RM9.90 Warranty',
        'product_warranty' => 'Product Warranty',
        '_yoast_wpseo_title' => 'SEO Title',
        '_yoast_wpseo_metadesc' => 'SEO Description'
    ];

    /**
     * Initialize the controller
     */
    public static function init()
    {
        // AJAX handlers
        add_action('wp_ajax_senheng_sync_products', [self::class, 'ajax_sync_products']);
        add_action('wp_ajax_senheng_test_connection', [self::class, 'ajax_test_connection']);
        add_action('wp_ajax_senheng_save_sync_settings', [self::class, 'ajax_save_settings']);

        // Register webhook endpoint for receiving product updates from remote
        add_action('rest_api_init', [self::class, 'register_webhook_endpoint']);

        // Push hooks - only if auto-sync is enabled and direction includes push
        if (self::is_push_enabled()) {
            // Product create/update
            add_action('woocommerce_update_product', [self::class, 'on_product_save'], 20, 1);
            
            // Product trash
            add_action('wp_trash_post', [self::class, 'on_product_trash'], 10, 1);
            
            // Product restore from trash
            add_action('untrash_post', [self::class, 'on_product_untrash'], 10, 1);
            
            // Product permanent delete
            add_action('before_delete_post', [self::class, 'on_product_delete'], 10, 1);
            
            // Product import via CSV
            add_action('woocommerce_product_import_inserted_product_object', [self::class, 'on_product_import'], 30, 2);
        }
    }

    /**
     * Check if push sync is enabled
     */
    private static function is_push_enabled()
    {
        $auto_enabled = get_option(self::OPTION_AUTO_SYNC_ENABLED, 'no');
        $direction = get_option(self::OPTION_SYNC_DIRECTION, 'both');
        
        return $auto_enabled === 'yes' && in_array($direction, ['push', 'both']);
    }

    /**
     * Check if pull sync is enabled
     */
    private static function is_pull_enabled()
    {
        $auto_enabled = get_option(self::OPTION_AUTO_SYNC_ENABLED, 'no');
        $direction = get_option(self::OPTION_SYNC_DIRECTION, 'both');
        
        return $auto_enabled === 'yes' && in_array($direction, ['pull', 'both']);
    }

    /**
     * Get or generate webhook secret
     */
    private static function get_webhook_secret()
    {
        $secret = get_option(self::OPTION_WEBHOOK_SECRET);
        if (empty($secret)) {
            $secret = wp_generate_password(32, false);
            update_option(self::OPTION_WEBHOOK_SECRET, $secret);
        }
        return $secret;
    }

    /**
     * Register REST API endpoint for receiving webhooks
     */
    public static function register_webhook_endpoint()
    {
        register_rest_route('senheng/v1', '/product-webhook', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_webhook'],
            'permission_callback' => [self::class, 'verify_webhook_signature']
        ]);
    }

    /**
     * Verify webhook signature
     */
    public static function verify_webhook_signature($request)
    {
        // Get the signature from headers
        $signature = $request->get_header('X-WC-Webhook-Signature');
        
        if (empty($signature)) {
            // Also check for custom header
            $signature = $request->get_header('X-Senheng-Signature');
        }
        
        if (empty($signature)) {
            error_log('[ProductSync] Webhook received without signature');
            return false;
        }

        // Get webhook secret
        $secret = self::get_webhook_secret();
        
        // Calculate expected signature
        $payload = $request->get_body();
        $expected_signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        if (!hash_equals($expected_signature, $signature)) {
            error_log('[ProductSync] Webhook signature mismatch');
            return false;
        }

        return true;
    }

    /**
     * Handle incoming webhook from remote site
     */
    public static function handle_webhook($request)
    {
        if (!self::is_pull_enabled()) {
            return new WP_REST_Response(['message' => 'Pull sync disabled'], 200);
        }

        // Prevent sync loops
        if (self::$is_syncing) {
            return new WP_REST_Response(['message' => 'Already syncing'], 200);
        }

        self::$is_syncing = true;

        try {
            $body = json_decode($request->get_body(), true);
            
            if (empty($body)) {
                return new WP_REST_Response(['error' => 'Empty payload'], 400);
            }

            // Get webhook topic from header
            $topic = $request->get_header('X-WC-Webhook-Topic');
            
            error_log('[ProductSync] Webhook received: ' . $topic);

            // Handle different webhook topics
            if (strpos($topic, 'product.deleted') !== false) {
                // Product was deleted on remote - trash locally
                $result = self::handle_remote_delete($body);
            } else {
                // Product created/updated - sync it
                $result = self::process_remote_product($body);
            }

            self::$is_syncing = false;

            if (is_wp_error($result)) {
                return new WP_REST_Response(['error' => $result->get_error_message()], 400);
            }

            return new WP_REST_Response(['success' => true, 'result' => $result], 200);

        } catch (Exception $e) {
            self::$is_syncing = false;
            error_log('[ProductSync] Webhook error: ' . $e->getMessage());
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle remote product deletion
     */
    private static function handle_remote_delete($remote_product)
    {
        $sku = isset($remote_product['sku']) ? $remote_product['sku'] : '';
        
        if (empty($sku)) {
            return new WP_Error('no_sku', 'Product has no SKU');
        }

        $local_product_id = wc_get_product_id_by_sku($sku);
        
        if (!$local_product_id) {
            return ['action' => 'skipped', 'reason' => 'Product not found locally'];
        }

        // Trash the local product (don't permanently delete)
        wp_trash_post($local_product_id);

        error_log('[ProductSync] Trashed local product: SKU ' . $sku);

        return ['action' => 'trashed', 'sku' => $sku];
    }

    /**
     * Hook: Product saved (create/update)
     */
    public static function on_product_save($product_id)
    {
        // Prevent sync loops
        if (self::$is_syncing) {
            return;
        }

        // Check if this is a valid product
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        // Skip auto-drafts and revisions
        $post_status = get_post_status($product_id);
        if (in_array($post_status, ['auto-draft', 'inherit'])) {
            return;
        }

        self::push_product_to_remote($product, 'update');
    }

    /**
     * Hook: Product trashed
     */
    public static function on_product_trash($post_id)
    {
        if (self::$is_syncing) return;

        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) return;

        self::push_product_status_to_remote($product, 'trash');
    }

    /**
     * Hook: Product restored from trash
     */
    public static function on_product_untrash($post_id)
    {
        if (self::$is_syncing) return;

        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) return;

        self::push_product_to_remote($product, 'restore');
    }

    /**
     * Hook: Product permanently deleted
     */
    public static function on_product_delete($post_id)
    {
        if (self::$is_syncing) return;

        if (get_post_type($post_id) !== 'product') {
            return;
        }

        $product = wc_get_product($post_id);
        if (!$product) return;

        self::push_product_status_to_remote($product, 'delete');
    }

    /**
     * Hook: Product imported via CSV
     */
    public static function on_product_import($product, $data)
    {
        if (self::$is_syncing) return;

        self::push_product_to_remote($product, 'import');
    }

    /**
     * Push product data to remote site
     */
    private static function push_product_to_remote($product, $action = 'update')
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        if (empty($remote_url) || empty($consumer_key) || empty($consumer_secret)) {
            return;
        }

        self::$is_syncing = true;

        $sku = $product->get_sku();
        if (empty($sku)) {
            self::$is_syncing = false;
            return;
        }

        // Build product data
        $product_data = self::build_product_data($product);

        // Check if product exists on remote by SKU
        $remote_product_id = self::get_remote_product_id_by_sku($sku);

        try {
            if ($remote_product_id) {
                // Update existing product on remote
                $endpoint = $remote_url . '/wp-json/wc/v3/products/' . $remote_product_id;
                $response = wp_remote_request($endpoint, [
                    'method' => 'PUT',
                    'timeout' => 30,
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                        'Content-Type' => 'application/json'
                    ],
                    'body' => wp_json_encode($product_data)
                ]);
            } else {
                // Create new product on remote
                $endpoint = $remote_url . '/wp-json/wc/v3/products';
                $response = wp_remote_post($endpoint, [
                    'timeout' => 30,
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                        'Content-Type' => 'application/json'
                    ],
                    'body' => wp_json_encode($product_data)
                ]);
            }

            if (is_wp_error($response)) {
                error_log('[ProductSync] Push failed for SKU ' . $sku . ': ' . $response->get_error_message());
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                if ($status_code >= 200 && $status_code < 300) {
                    error_log('[ProductSync] Push successful for SKU ' . $sku . ' (action: ' . $action . ')');
                } else {
                    error_log('[ProductSync] Push failed for SKU ' . $sku . ': HTTP ' . $status_code);
                }
            }

        } catch (Exception $e) {
            error_log('[ProductSync] Push exception for SKU ' . $sku . ': ' . $e->getMessage());
        }

        self::$is_syncing = false;
    }

    /**
     * Push product status change to remote (trash/delete)
     */
    private static function push_product_status_to_remote($product, $action)
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        if (empty($remote_url) || empty($consumer_key) || empty($consumer_secret)) {
            return;
        }

        self::$is_syncing = true;

        $sku = $product->get_sku();
        $remote_product_id = self::get_remote_product_id_by_sku($sku);

        if (!$remote_product_id) {
            self::$is_syncing = false;
            return;
        }

        try {
            if ($action === 'delete') {
                // Permanently delete on remote
                $endpoint = $remote_url . '/wp-json/wc/v3/products/' . $remote_product_id . '?force=true';
                $response = wp_remote_request($endpoint, [
                    'method' => 'DELETE',
                    'timeout' => 30,
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
                    ]
                ]);
            } else {
                // Trash on remote (set status to trash)
                $endpoint = $remote_url . '/wp-json/wc/v3/products/' . $remote_product_id;
                $response = wp_remote_request($endpoint, [
                    'method' => 'PUT',
                    'timeout' => 30,
                    'headers' => [
                        'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret),
                        'Content-Type' => 'application/json'
                    ],
                    'body' => wp_json_encode(['status' => 'trash'])
                ]);
            }

            if (!is_wp_error($response)) {
                error_log('[ProductSync] ' . ucfirst($action) . ' pushed for SKU ' . $sku);
            }

        } catch (Exception $e) {
            error_log('[ProductSync] Status push exception: ' . $e->getMessage());
        }

        self::$is_syncing = false;
    }

    /**
     * Get remote product ID by SKU
     */
    private static function get_remote_product_id_by_sku($sku)
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        $endpoint = $remote_url . '/wp-json/wc/v3/products?sku=' . urlencode($sku);
        
        $response = vip_safe_wp_remote_get($endpoint, '', 3, 15, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ]
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $products = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($products) && isset($products[0]['id'])) {
            return $products[0]['id'];
        }

        return null;
    }

    /**
     * Build product data array for API
     */
    private static function build_product_data($product)
    {
        $data = [
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'status' => $product->get_status(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => $product->get_stock_status(),
        ];

        // Add prices (skip if <= 1)
        $regular_price = $product->get_regular_price();
        if (!empty($regular_price) && floatval($regular_price) > 1) {
            $data['regular_price'] = $regular_price;
        }

        $sale_price = $product->get_sale_price();
        if (!empty($sale_price) && floatval($sale_price) > 1) {
            $data['sale_price'] = $sale_price;
        }

        // Add custom meta fields
        $meta_data = [];
        foreach (self::CUSTOM_FIELDS as $meta_key => $label) {
            $value = get_post_meta($product->get_id(), $meta_key, true);
            if ($value !== '' && $value !== null) {
                $meta_data[] = [
                    'key' => $meta_key,
                    'value' => $value
                ];
            }
        }

        if (!empty($meta_data)) {
            $data['meta_data'] = $meta_data;
        }

        return $data;
    }



    /**
     * Render the settings page
     */
    public static function render_settings_page()
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL, '');
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY, '');
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET, '');
        $auto_sync = get_option(self::OPTION_AUTO_SYNC_ENABLED, 'no');
        $sync_direction = get_option(self::OPTION_SYNC_DIRECTION, 'both');
        $webhook_secret = self::get_webhook_secret();
        $webhook_url = rest_url('senheng/v1/product-webhook');
        ?>
        <div class="wrap">
            <h1>Product Sync Settings</h1>
            <p>Bidirectional sync with another WooCommerce website using REST API.</p>

            <!-- Remote Site Configuration -->
            <div class="card" style="max-width: 900px; padding: 20px;">
                <h2>🔗 Remote Site Configuration</h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="remote_url">Remote Site URL</label></th>
                        <td>
                            <input type="url" id="remote_url" name="remote_url" 
                                   value="<?php echo esc_attr($remote_url); ?>" 
                                   class="regular-text" placeholder="https://example.com">
                            <p class="description">The URL of the WooCommerce site to sync with (without trailing slash)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="consumer_key">Consumer Key</label></th>
                        <td>
                            <input type="text" id="consumer_key" name="consumer_key" 
                                   value="<?php echo esc_attr($consumer_key); ?>" 
                                   class="regular-text" placeholder="ck_xxxxxxxxxxxxxxxx">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="consumer_secret">Consumer Secret</label></th>
                        <td>
                            <input type="password" id="consumer_secret" name="consumer_secret" 
                                   value="<?php echo esc_attr($consumer_secret); ?>" 
                                   class="regular-text" placeholder="cs_xxxxxxxxxxxxxxxx">
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="save-settings" class="button button-primary">Save Settings</button>
                    <button type="button" id="test-connection" class="button">Test Connection</button>
                </p>
            </div>

            <!-- Auto Sync Settings -->
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2>⚡ Auto Sync Settings</h2>
                <p>Automatically sync products when they are created, updated, trashed, restored, or deleted.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Enable Auto Sync</th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_sync_enabled" name="auto_sync_enabled" 
                                       value="yes" <?php checked($auto_sync, 'yes'); ?>>
                                Enable automatic bidirectional sync
                            </label>
                            <p class="description">When enabled, product changes will automatically sync to the remote site</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Sync Direction</th>
                        <td>
                            <select id="sync_direction" name="sync_direction">
                                <option value="both" <?php selected($sync_direction, 'both'); ?>>Both (Push & Pull)</option>
                                <option value="push" <?php selected($sync_direction, 'push'); ?>>Push Only (Local → Remote)</option>
                                <option value="pull" <?php selected($sync_direction, 'pull'); ?>>Pull Only (Remote → Local)</option>
                            </select>
                            <p class="description">Choose which direction to sync products</p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Webhook Configuration -->
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2>🔔 Webhook Configuration (For Pull)</h2>
                <p>Configure these on the <strong>remote site</strong> to receive product updates here.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Webhook URL</th>
                        <td>
                            <input type="text" value="<?php echo esc_attr($webhook_url); ?>" class="large-text" readonly>
                            <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_url); ?>'); alert('Copied!');">Copy</button>
                            <p class="description">Use this URL in the remote site's WooCommerce Webhook settings</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Webhook Secret</th>
                        <td>
                            <input type="text" value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text" readonly>
                            <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_secret); ?>'); alert('Copied!');">Copy</button>
                            <p class="description">Use this secret when creating webhooks on the remote site</p>
                        </td>
                    </tr>
                </table>

                <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-top: 15px;">
                    <strong>📋 Setup Instructions for Remote Site:</strong>
                    <ol style="margin: 10px 0 0 20px;">
                        <li>Go to <strong>WooCommerce → Settings → Advanced → Webhooks</strong></li>
                        <li>Click <strong>Add webhook</strong></li>
                        <li>Set <strong>Topic</strong> to "Product created", "Product updated", or "Product deleted"</li>
                        <li>Set <strong>Delivery URL</strong> to the Webhook URL above</li>
                        <li>Set <strong>Secret</strong> to the Webhook Secret above</li>
                        <li>Click <strong>Save webhook</strong></li>
                    </ol>
                </div>
            </div>

            <!-- Manual Sync -->
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2>🔄 Manual Sync</h2>
                <p>Pull products from the remote site and update local products by SKU.</p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="sync_sku">SKU to Sync (Optional)</label></th>
                        <td>
                            <input type="text" id="sync_sku" name="sync_sku" class="regular-text" placeholder="Leave empty to sync all">
                            <p class="description">Enter a specific SKU to sync, or leave empty to sync all products</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" id="sync-products" class="button button-primary">Pull from Remote</button>
                </p>

                <div id="sync-progress" style="display: none; margin-top: 20px;">
                    <div class="progress-bar" style="width: 100%; background: #ddd; height: 20px; border-radius: 3px;">
                        <div class="progress-fill" style="width: 0%; background: #0073aa; height: 100%; border-radius: 3px; transition: width 0.3s;"></div>
                    </div>
                    <p class="progress-text">Syncing...</p>
                </div>

                <div id="sync-log" style="margin-top: 20px; max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; font-family: monospace; font-size: 12px; display: none;"></div>
            </div>

            <!-- Sync Events Info -->
            <div class="card" style="max-width: 900px; padding: 20px; margin-top: 20px;">
                <h2>📊 Auto Sync Events</h2>
                <p>When auto sync is enabled, the following events trigger a sync:</p>
                
                <table class="widefat" style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Push (Local → Remote)</th>
                            <th>Pull (Remote → Local)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Product Created</td>
                            <td>✅ Creates on remote</td>
                            <td>✅ Via webhook</td>
                        </tr>
                        <tr>
                            <td>Product Updated</td>
                            <td>✅ Updates on remote</td>
                            <td>✅ Via webhook</td>
                        </tr>
                        <tr>
                            <td>Product Trashed</td>
                            <td>✅ Trashes on remote</td>
                            <td>✅ Via webhook</td>
                        </tr>
                        <tr>
                            <td>Product Restored</td>
                            <td>✅ Restores on remote</td>
                            <td>✅ Via webhook</td>
                        </tr>
                        <tr>
                            <td>Product Deleted</td>
                            <td>✅ Deletes on remote</td>
                            <td>✅ Via webhook</td>
                        </tr>
                        <tr>
                            <td>CSV Import</td>
                            <td>✅ Syncs to remote</td>
                            <td>—</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var nonce = '<?php echo wp_create_nonce('senheng_sync_nonce'); ?>';

            // Save Settings
            $('#save-settings').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'senheng_save_sync_settings',
                        nonce: nonce,
                        remote_url: $('#remote_url').val(),
                        consumer_key: $('#consumer_key').val(),
                        consumer_secret: $('#consumer_secret').val(),
                        auto_sync_enabled: $('#auto_sync_enabled').is(':checked') ? 'yes' : 'no',
                        sync_direction: $('#sync_direction').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Settings saved successfully! Page will reload to apply changes.');
                            location.reload();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Failed to save settings');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Save Settings');
                    }
                });
            });

            // Test Connection
            $('#test-connection').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Testing...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'senheng_test_connection',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ Connection successful!\n\nStore: ' + response.data.store_name + '\nProducts: ' + response.data.product_count);
                        } else {
                            alert('✗ Connection failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Connection test failed');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Test Connection');
                    }
                });
            });

            // Sync Products
            $('#sync-products').on('click', function() {
                var $btn = $(this);
                var sku = $('#sync_sku').val().trim();

                $btn.prop('disabled', true).text('Syncing...');
                $('#sync-progress').show();
                $('#sync-log').show().html('');

                function log(message, type) {
                    var color = type === 'error' ? '#dc3545' : (type === 'success' ? '#28a745' : '#333');
                    $('#sync-log').append('<div style="color: ' + color + '">[' + new Date().toLocaleTimeString() + '] ' + message + '</div>');
                    $('#sync-log').scrollTop($('#sync-log')[0].scrollHeight);
                }

                log('Starting product sync...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'senheng_sync_products',
                        nonce: nonce,
                        sku: sku
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            log('Sync completed!', 'success');
                            log('Total: ' + data.total + ', Updated: ' + data.updated + ', Created: ' + data.created + ', Skipped: ' + data.skipped + ', Errors: ' + data.errors, 'success');
                            
                            if (data.log && data.log.length) {
                                data.log.forEach(function(entry) {
                                    log(entry.message, entry.type);
                                });
                            }

                            $('.progress-fill').css('width', '100%');
                            $('.progress-text').text('Complete!');
                        } else {
                            log('Sync failed: ' + response.data, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        log('AJAX error: ' + error, 'error');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('Pull from Remote');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler: Save settings
     */
    public static function ajax_save_settings()
    {
        check_ajax_referer('senheng_sync_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }

        $remote_url = esc_url_raw(rtrim($_POST['remote_url'], '/'));
        $consumer_key = sanitize_text_field($_POST['consumer_key']);
        $consumer_secret = sanitize_text_field($_POST['consumer_secret']);
        $auto_sync = isset($_POST['auto_sync_enabled']) ? sanitize_text_field($_POST['auto_sync_enabled']) : 'no';
        $sync_direction = isset($_POST['sync_direction']) ? sanitize_text_field($_POST['sync_direction']) : 'both';

        update_option(self::OPTION_REMOTE_URL, $remote_url);
        update_option(self::OPTION_CONSUMER_KEY, $consumer_key);
        update_option(self::OPTION_CONSUMER_SECRET, $consumer_secret);
        update_option(self::OPTION_AUTO_SYNC_ENABLED, $auto_sync);
        update_option(self::OPTION_SYNC_DIRECTION, $sync_direction);

        wp_send_json_success('Settings saved');
    }

    /**
     * AJAX handler: Test connection
     */
    public static function ajax_test_connection()
    {
        check_ajax_referer('senheng_sync_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }

        $result = self::test_remote_connection();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Test connection to remote WooCommerce site
     */
    private static function test_remote_connection()
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        if (empty($remote_url) || empty($consumer_key) || empty($consumer_secret)) {
            return new WP_Error('missing_config', 'Please configure all API settings first');
        }

        // Test with a simple products request (limit 1)
        $endpoint = $remote_url . '/wp-json/wc/v3/products?per_page=1';
        
        $response = vip_safe_wp_remote_get($endpoint, '', 3, 30, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ]
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 401) {
            return new WP_Error('auth_failed', 'Authentication failed. Check your API keys.');
        }

        if ($status_code !== 200) {
            return new WP_Error('api_error', 'API returned status: ' . $status_code);
        }

        // Get store info
        $store_endpoint = $remote_url . '/wp-json/wc/v3/';
        $store_response = vip_safe_wp_remote_get($store_endpoint, '', 3, 30, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ]
        ]);

        $store_info = json_decode(wp_remote_retrieve_body($store_response), true);
        
        // Get total product count from headers
        $total_products = wp_remote_retrieve_header($response, 'x-wp-total');

        return [
            'store_name' => isset($store_info['store']['name']) ? $store_info['store']['name'] : $remote_url,
            'product_count' => $total_products ?: 'Unknown'
        ];
    }

    /**
     * AJAX handler: Sync products
     */
    public static function ajax_sync_products()
    {
        check_ajax_referer('senheng_sync_nonce', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Permission denied');
        }

        // Increase time limit for large syncs
        set_time_limit(300);

        $sku = isset($_POST['sku']) ? sanitize_text_field($_POST['sku']) : '';

        $result = self::sync_products($sku);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * Main sync function (pull from remote)
     */
    public static function sync_products($sku = '')
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        if (empty($remote_url) || empty($consumer_key) || empty($consumer_secret)) {
            return new WP_Error('missing_config', 'Please configure API settings first');
        }

        self::$is_syncing = true;

        $log = [];
        $stats = [
            'total' => 0,
            'updated' => 0,
            'created' => 0,
            'skipped' => 0,
            'errors' => 0
        ];

        // Fetch products from remote
        $page = 1;
        $per_page = 100;
        $all_fetched = false;

        while (!$all_fetched) {
            $endpoint = $remote_url . '/wp-json/wc/v3/products';
            $params = [
                'per_page' => $per_page,
                'page' => $page,
                'status' => 'publish'
            ];

            if (!empty($sku)) {
                $params['sku'] = $sku;
            }

            $endpoint .= '?' . http_build_query($params);

            $response = vip_safe_wp_remote_get($endpoint, '', 3, 60, [
                'timeout' => 60,
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
                ]
            ]);

            if (is_wp_error($response)) {
                self::$is_syncing = false;
                return new WP_Error('fetch_failed', 'Failed to fetch products: ' . $response->get_error_message());
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                self::$is_syncing = false;
                return new WP_Error('api_error', 'API returned status: ' . $status_code);
            }

            $products = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($products)) {
                $all_fetched = true;
                break;
            }

            foreach ($products as $remote_product) {
                $stats['total']++;
                
                $result = self::process_remote_product($remote_product);
                
                if (is_wp_error($result)) {
                    $stats['errors']++;
                    $log[] = [
                        'type' => 'error',
                        'message' => 'SKU ' . ($remote_product['sku'] ?? 'N/A') . ': ' . $result->get_error_message()
                    ];
                } else {
                    $stats[$result['action']]++;
                    $log[] = [
                        'type' => 'success',
                        'message' => 'SKU ' . $remote_product['sku'] . ': ' . ucfirst($result['action'])
                    ];
                }
            }

            $total_products = wp_remote_retrieve_header($response, 'x-wp-total');
            if (count($products) < $per_page || ($stats['total'] >= $total_products)) {
                $all_fetched = true;
            }

            if (!empty($sku)) {
                $all_fetched = true;
            }

            $page++;
        }

        self::$is_syncing = false;

        return array_merge($stats, ['log' => $log]);
    }

    /**
     * Process a single remote product
     */
    private static function process_remote_product($remote_product)
    {
        $remote_sku = isset($remote_product['sku']) ? $remote_product['sku'] : '';

        if (empty($remote_sku)) {
            return new WP_Error('no_sku', 'Product has no SKU');
        }

        $local_product_id = wc_get_product_id_by_sku($remote_sku);
        $is_new = false;

        if (!$local_product_id) {
            return ['action' => 'skipped', 'reason' => 'Product not found locally'];
        }

        $local_product = wc_get_product($local_product_id);
        
        if (!$local_product) {
            return new WP_Error('product_error', 'Could not load local product');
        }

        self::update_product_fields($local_product, $remote_product);
        self::update_product_meta($local_product, $remote_product);
        $local_product->save();

        if (isset($remote_product['type']) && $remote_product['type'] === 'variable' && !empty($remote_product['variations'])) {
            self::sync_variations($local_product, $remote_product);
        }

        return ['action' => $is_new ? 'created' : 'updated'];
    }

    /**
     * Update standard product fields
     */
    private static function update_product_fields($product, $remote_data)
    {
        if (!empty($remote_data['name'])) {
            $product->set_name($remote_data['name']);
        }

        if (isset($remote_data['description'])) {
            $product->set_description($remote_data['description']);
        }

        if (isset($remote_data['short_description'])) {
            $product->set_short_description($remote_data['short_description']);
        }

        if (isset($remote_data['regular_price'])) {
            $regular_price = floatval($remote_data['regular_price']);
            if ($regular_price > 1) {
                $product->set_regular_price($remote_data['regular_price']);
            }
        }

        if (isset($remote_data['sale_price']) && $remote_data['sale_price'] !== '') {
            $sale_price = floatval($remote_data['sale_price']);
            if ($sale_price > 1) {
                $product->set_sale_price($remote_data['sale_price']);
            }
        }

        if (isset($remote_data['stock_quantity'])) {
            $product->set_stock_quantity($remote_data['stock_quantity']);
        }

        if (isset($remote_data['stock_status'])) {
            $product->set_stock_status($remote_data['stock_status']);
        }

        if (isset($remote_data['manage_stock'])) {
            $product->set_manage_stock($remote_data['manage_stock']);
        }
    }

    /**
     * Update custom meta fields
     */
    private static function update_product_meta($product, $remote_data)
    {
        $product_id = $product->get_id();

        $meta_data = isset($remote_data['meta_data']) ? $remote_data['meta_data'] : [];
        
        $remote_meta = [];
        foreach ($meta_data as $meta) {
            if (isset($meta['key']) && isset($meta['value'])) {
                $remote_meta[$meta['key']] = $meta['value'];
            }
        }

        foreach (self::CUSTOM_FIELDS as $meta_key => $label) {
            if (isset($remote_meta[$meta_key])) {
                $value = $remote_meta[$meta_key];
                update_post_meta($product_id, $meta_key, $value);

                if ($meta_key === 's_coin_value' && function_exists('update_field')) {
                    update_field('s_coin_value', floatval($value), $product_id);
                }
            }
        }

        if (!empty($remote_data['yoast_head_json'])) {
            if (!empty($remote_data['yoast_head_json']['title'])) {
                update_post_meta($product_id, '_yoast_wpseo_title', $remote_data['yoast_head_json']['title']);
            }
            if (!empty($remote_data['yoast_head_json']['description'])) {
                update_post_meta($product_id, '_yoast_wpseo_metadesc', $remote_data['yoast_head_json']['description']);
            }
        }
    }

    /**
     * Sync variations for a variable product
     */
    private static function sync_variations($parent_product, $remote_product)
    {
        $remote_url = get_option(self::OPTION_REMOTE_URL);
        $consumer_key = get_option(self::OPTION_CONSUMER_KEY);
        $consumer_secret = get_option(self::OPTION_CONSUMER_SECRET);

        $endpoint = $remote_url . '/wp-json/wc/v3/products/' . $remote_product['id'] . '/variations?per_page=100';
        
        $response = vip_safe_wp_remote_get($endpoint, '', 3, 60, [
            'timeout' => 60,
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ]
        ]);

        if (is_wp_error($response)) {
            return;
        }

        $remote_variations = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($remote_variations)) {
            return;
        }

        foreach ($remote_variations as $remote_variation) {
            $variation_sku = isset($remote_variation['sku']) ? $remote_variation['sku'] : '';
            
            if (empty($variation_sku)) {
                continue;
            }

            $local_variation_id = wc_get_product_id_by_sku($variation_sku);
            
            if (!$local_variation_id) {
                continue;
            }

            $local_variation = wc_get_product($local_variation_id);
            
            if (!$local_variation || !$local_variation->is_type('variation')) {
                continue;
            }

            self::update_product_fields($local_variation, $remote_variation);
            self::update_product_meta($local_variation, $remote_variation);
            
            $local_variation->save();
        }
    }
}
