<?php

if (!function_exists('dd')) {
    /**
     * Dump and die — like Laravel's dd()
     *
     * @param  mixed  ...$vars
     * @return void
     */
    function dd(...$vars)
    {
        echo '<pre style="background:#111;color:#0f0;padding:10px;border-radius:6px;">';
        foreach ($vars as $var) {
            print_r($var);
            echo "\n---------------------\n";
        }
        echo '</pre>';
        die;
    }
}

if (!function_exists('dump')) {
    /**
     * Dump and die — like Laravel's dump()
     *
     * @param  mixed  ...$vars
     * @return void
     */
    function dump(...$vars)
    {
        echo '<pre style="background:#111;color:#0f0;padding:10px;border-radius:6px;">';
        foreach ($vars as $var) {
            print_r($var);
            echo "\n---------------------\n";
        }
        echo '</pre>';
    }
}

if (!function_exists('write_log')) {
    /**
     * Write structured logs to a custom file in plugin directory.
     *
     * @param string $fileName  Log file name (e.g. 'api-callback.log')
     * @param mixed  $data      Data to log (array/string/object)
     * @param array  $server    Optional $_SERVER array for request context
     * @return void
     */
    function write_log($fileName, $data, $server = null)
    {
        $dir = plugin_dir_path(__DIR__); // Goes up from /helpers/
        $logPath = $dir . $fileName;

        $timestamp = date("Y-m-d H:i:s");

        // Safe default if $server not passed
        $uri = $server['REQUEST_URI'] ?? 'N/A';
        $ip = $server['REMOTE_ADDR'] ?? 'N/A';

        $log = "\n\n[$timestamp] - URI: $uri - IP: $ip\n";
        $log .= print_r($data, true);

        file_put_contents($logPath, $log, FILE_APPEND);
    }
}

if (!function_exists('log_impact_share_response')) {
    /**
     * Get the current version of the plugin.
     *
     * @return string
     */
    function log_impact_share_response($prodsku, $permalink, $share_url, $ping_url, $response)
    {
        $log_dir = plugin_dir_path(__DIR__) . '/integrations/impact/impact-logs'; // e.g., wp-content/impact-logs

        // Create directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $log_file = $log_dir . '/ping_response_log.txt';

        $log_data = [
            'timestamp' => current_time('mysql'),
            'prodsku' => $prodsku,
            'permalink' => $permalink,
            'share_url' => $share_url,
            'ping_url' => $ping_url,
            'response' => is_array($response) ? $response : ['raw' => $response],
        ];

        // Append to log file
        file_put_contents(
            $log_file,
            print_r($log_data, true) . "\n---\n",
            FILE_APPEND
        );
    }
}

if (!function_exists('woo_authorization_salt')) {

    /**
     * Generate a unique authorization hash for WooCommerce API requests.
     *
     * @return array
     */
    function woo_authorization_salt()
    {
        if (SENHENG_ENV === 'local') {
            $apiUrl       = WOO_STG_API_URL;
            $customerKey  = WOO_STG_CUSTOMER_KEY;
            $apiKey       = WOO_STG_API_KEY;
        } else {
            $apiUrl       = WOO_API_URL;
            $customerKey  = WOO_CUSTOMER_KEY;
            $apiKey       = WOO_API_KEY;
        }

        $timestamp = round(microtime(true) * 1000);
        $authString = $customerKey . $timestamp . $apiKey;
        $hash = hash('sha512', $authString);

        return [
            'api_url'       => $apiUrl,
            'customer_key'  => $customerKey,
            'api_key'       => $apiKey,
            'timestamp'     => $timestamp,
            'hash'          => $hash,
        ];
    }
}

if (!function_exists('mini_orange_authorization_salt')) {

    /**
     * Generate a unique authorization hash for Mini Orange API requests.
     *
     * @return array
     */
    function mini_orange_authorization_salt()
    {
        if (SENHENG_ENV === 'local') {
            $apiUrl       = SSO_STG_API_URL;
            $customerKey  = MINI_ORANGE_CUSTOMER_KEY;
            $apiKey       = MINI_ORANGE_API_KEY;
        } else {
            $apiUrl       = SSO_API_URL;
            $customerKey  = MINI_ORANGE_CUSTOMER_KEY;
            $apiKey       = MINI_ORANGE_API_KEY;
        }
        $timestamp = round(microtime(true) * 1000);
        $authString = $customerKey . $timestamp . $apiKey;
        $hash = hash('sha512', $authString);
        return [
            'api_url'       => $apiUrl,
            'customer_key'  => $customerKey,
            'api_key'       => $apiKey,
            'timestamp'     => $timestamp,
            'hash'          => $hash,
        ];
    }
}

if (!function_exists('log_api_request')) {
    /**
     * Log API requests to a custom table.
     *
     * @param string $url The request URL.
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param mixed $body The request body (optional).
     * @param int $response_code The response code (default 0).
     * @param string $response_message The response message (optional).
     * @param string $error_message The error message (optional).
     */
    function log_api_request($url, $method = 'GET', $body = null, $response_code = 0, $response_message = '', $error_message = '')
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'failed_api_logs';

        $wpdb->insert($table_name, [
            'request_from_url' => esc_url_raw((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}"),
            'request_url'      => esc_url_raw($url),
            'request_method'   => strtoupper($method),
            'request_body'     => maybe_serialize($body),
            'response_code'    => intval($response_code),
            'response_message' => $response_message,
            'error_message'    => $error_message,
            'created_at'       => current_time('mysql'),
        ]);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data against a set of rules.
     *
     * @param array $data The data to validate.
     * @param array $rules The validation rules.
     * @return array An array of error messages, or an empty array if validation passes.
     */
    function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $field_rules) {
            $value = isset($data[$field]) ? $data[$field] : null;

            foreach ($field_rules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                }

                if ($rule === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be an integer.';
                }

                if ($rule === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be numeric.';
                }

                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid email address.';
                }

                // Add more rules here if needed (e.g., min, max, string, email, etc.)
            }
        }

        return $errors;
    }
}

if (!function_exists('force_login_page')) {

    /**
     * Force the user to the login page if not authenticated.
     */
    function force_login_page()
    {
        if (is_user_logged_in()) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            // Normalize to lowercase for comparison
            $uri = strtolower($request_uri);

            nocache_headers();
            header("Cache-Control: no-cache, must-revalidate, max-age=0");


            // Check for /login or /register in the URL path
            if (
                preg_match('#/(login|register)(/|$|\?)#', $uri)
            ) {
                wp_redirect(site_url('/my-account'));
                exit;
            }
        }

        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $current_url .= "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

        // Check if current URL contains 'my-account'
        if (strpos($_SERVER['REQUEST_URI'], '/my-account') !== false && !is_user_logged_in()) {
            // wp_redirect(site_url('/login'));
            wp_redirect(site_url('/'));
            exit;
        }
    }
}

if (!function_exists('cannot_view_login_register_page')) {

    /**
     * Redirect to my account page if user already logged in and tries to access login or register page.
     */
    function cannot_view_login_register_page()
    {
        if (is_user_logged_in()) {
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            // Normalize to lowercase for comparison
            $uri = strtolower($request_uri);

            // Check for /login or /register in the URL path
            if (
                preg_match('#/(login|register)(/|$|\?)#', $uri)
            ) {
                wp_redirect(site_url('/my-account'));
                exit;
            }
        }
    }
}

if (!function_exists('split_cust_name')) {
    /**
     * Split a full name into first and last names.
     *
     * @param int $user_id The user ID to get the full name from user meta.
     * @return array An associative array with 'first_name' and 'last_name'.
     */

    function split_cust_name($user_id)
    {
        // Get full name from user meta
        $full_name = trim((string) get_user_meta($user_id, 'cust_name', true));

        if ($full_name === '') {
            return ['first_name' => '', 'last_name' => ''];
        }

        // Split by whitespace
        $parts = preg_split('/\s+/', $full_name);

        if (count($parts) === 1) {
            // Only one name → use same for first and last
            return [
                'first_name' => $parts[0],
                'last_name'  => $parts[0],
            ];
        }

        // More than one word → first is first name, rest is last name
        $first_name = array_shift($parts);
        $last_name  = implode(' ', $parts);

        return [
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ];
    }
}

if (!function_exists('get_userAgent')) {
    /**
     * Best-effort platform classifier from the User-Agent.
     * Returns: 'APP', 'MOBILE', 'WEB', 'BOT', or 'UNKNOWN'
     */
    function get_userAgent($type = 'event'): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $uri  = $_SERVER['REQUEST_URI'] ?? '';
        $title = get_bloginfo('name');
        $is_senheng_domain = (
            stripos($title, 'Senheng') !== false
        );
        if ($ua === '') {
            return 'UNKNOWN';
        }

        // 1) Known bots/crawlers
        if (preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|monitor|crawler|wget|curl/i', $ua)) {
            return 'BOT';
        }

        // 2) In-app / WebView / Flutter
        //    - Flutter: add a custom token from your app if you can (e.g., "MyFlutterApp/1.0")
        //    - Android WebView typically has "; wv"
        //    - Many in-app browsers include "FBAN", "FBAV", "Instagram", "Line", etc.
        // if (
        //     stripos($ua, 'Flutter') !== false ||
        //     preg_match('/;\s*wv\)/i', $ua) ||
        //     preg_match('/FBAN|FBAV|Instagram|Line\/|OKHttp|Electron|Cordova|Ionic|ReactNative/i', $ua)
        // ) {
        //     return 'SRC';
        // }

        // 3) iPadOS desktop-like UA quirk:
        //    iPad can report "Macintosh; Intel Mac OS X" but still include "Mobile/"
        $is_ipad_disguised = (stripos($ua, 'Macintosh') !== false && stripos($ua, 'Mobile') !== false);

        // 4) Mobile browsers (phones & tablets)
        if (
            $is_ipad_disguised ||
            preg_match('/Mobile|Android|iPhone|iPad|iPod|Windows Phone|IEMobile|BlackBerry|Silk\/|Kindle/i', $ua)
        ) {

            if ($is_senheng_domain) {
                return ($type === 'product_feed') ? 'SHWEB' : 'Senheng Web';
            } else {
                return ($type === 'product_feed') ? 'SQWEB' : 'SenQ Web';
            }
        }

        // 5) Fallback: desktop web
        if ($is_senheng_domain) {
            return ($type === 'product_feed') ? 'SHWEB' : 'Senheng Web';
        } else {
            return ($type === 'product_feed') ? 'SQWEB' : 'SenQ Web';
        }
    }
}

if (!function_exists('getChannelWeb')) {
    /**
     * Determine the channel based on the domain.
     * Returns: 'Senheng' or 'SenQ'
     */
    function getChannelWeb()
    {
        $title = get_bloginfo('name');

        if (stripos($title, 'Senheng') !== false) {
            return 'Senheng';
        }
        if (stripos($title, 'SenQ') !== false) {
            return 'SenQ';
        }

        return 'SRC';
    }
}

if (!function_exists('insider_config')) {

    /**
     * Get the Insider API configuration.
     *
     * @return array
     */
    function insider_config()
    {
        if (SENHENG_ENV === 'local') {
            $insider['partner_name'] = INSIDER_PARTNER_NAME_STG;
            $insider['partner_id'] = INSIDER_PARTNER_ID_STG;
            $insider['channel'] = get_UserAgent();
            $insider['catalog_token'] = INSIDER_CATALOG_TOKEN_STG;
        } else {
            $insider['partner_name'] = INSIDER_PARTNER_NAME;
            $insider['partner_id'] = INSIDER_PARTNER_ID;
            $insider['channel'] = get_UserAgent();
            $insider['catalog_token'] = INSIDER_CATALOG_TOKEN;
        }

        return $insider;
    }
}

if (!function_exists('is_valid_product_payload')) {
    /**
     * Validate the product payload structure for Insider API.
     *
     * @param array $data The product payload data.
     * @return bool True if valid, false otherwise.
     */
    function is_valid_product_payload($data)
    {
        $required_fields = [
            'item_id'        => $data['item_id'] ?? null,
            'locale'         => $data['locale'] ?? null,
            'name'           => $data['name'] ?? null,
            'image_url'      => $data['image_url'] ?? null,
            'url'            => $data['url'] ?? null,
            'original_price' => $data['original_price'] ?? null,
            'price'          => $data['price'] ?? null,
            'in_stock'       => $data['in_stock'], // allow 0 or 1, just check isset
        ];

        foreach ($required_fields as $key => $value) {
            if (!isset($value) || $value === '') {
                // echo 'Missing or empty required field: ' . $key . PHP_EOL;
                return false;
            }

            // Also make sure price/original_price are arrays with at least one currency
            if (in_array($key, ['price', 'original_price']) && (!is_array($value) || empty($value))) {
                // echo 'Field ' . $key . ' must be a non-empty array.' . PHP_EOL;
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('log_insider_sync_response')) {
    /**
     * Log Insider product sync responses to a file.
     *
     * @param int    $product_id The product ID.
     * @param array  $response   The response from the Insider API.
     * @param string $method     The HTTP method used (optional).
     */
    function log_insider_sync_response($product_id, $response, $method = null)
    {
        $log_dir = plugin_dir_path(__FILE__) . 'insider_logs/';
        if (!file_exists($log_dir)) mkdir($log_dir, 0755, true);

        $log_file = $log_dir . 'insider_product_sync.log';
        $log_entry = sprintf(
            "[%s] Product ID: %d, Response Code: %d, Response Body: %s, Method: %s\n",
            date('Y-m-d H:i:s'),
            $product_id,
            wp_remote_retrieve_response_code($response),
            wp_remote_retrieve_body($response),
            $method
        );

        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

if (!function_exists('get_product_status_label')) {
    /**
     * Map WordPress product post status to Insider status labels.
     *
     * @param int $product_id The product ID.
     * @return string The corresponding Insider status label.
     */

    function get_product_status_label($product)
    {
        $status = $product->get_status();
        switch ($status) {
            case 'publish':
                return 'ACTIVE';
            case 'draft':
                return 'INACTIVE';
            case 'pending':
                return 'INACTIVE';
            case 'private':
                return 'INACTIVE';
            case 'trash':
                return 'DELETED';
            default:
                return 'INACTIVE';
        }
    }
}

if (!function_exists('get_product_category_path_ids')) {
    /**
     * Get the full category path IDs for a product, formatted for Insider.
     *
     * @param int $product_id The product ID.
     * @return string The category path in the format "id|name>id|name>..."
     */
    function get_product_category_path_ids($product_id)
    {
        $terms = wp_get_post_terms($product_id, 'product_cat');

        if (empty($terms) || is_wp_error($terms)) {
            return '';
        }

        // Find the category with the deepest hierarchy
        $deepest_term   = null;
        $deepest_levels = -1;

        foreach ($terms as $term) {
            $ancestors = get_ancestors($term->term_id, 'product_cat');
            $level     = count($ancestors);
            if ($level > $deepest_levels) {
                $deepest_levels = $level;
                $deepest_term   = $term;
            }
        }

        if (! $deepest_term) {
            return '';
        }

        // Build the full path (top level > ... > deepest)
        $ancestors = array_reverse(get_ancestors($deepest_term->term_id, 'product_cat'));
        $path      = [];

        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if (! is_wp_error($ancestor)) {
                $path[] = $ancestor->term_id . '|' . html_entity_decode($ancestor->name);
            }
        }

        $path[] = $deepest_term->term_id . '|' . html_entity_decode($deepest_term->name);

        return implode('>', $path);
    }
}

// if (!function_exists('custom_log')) {
//     /**
//      * Write a custom log entry to a specified log file within the plugin's logs directory.
//      *
//      * @param string $fileName The name of the log file (e.g., 'debug.log').
//      * @param string $message  The log message to write.
//      * @throws RuntimeException If the log directory cannot be created.
//      */
//     function custom_log(string $message, string $fileName = 'custom-log'): void
//     {
//         $log_dir = rtrim(SENHENG_CORE_PATH, "/\\") . '/logs';

//         // Ensure log directory exists
//         if (!is_dir($log_dir) && !mkdir($log_dir, 0755, true) && !is_dir($log_dir)) {
//             throw new RuntimeException("Unable to create log directory: {$log_dir}");
//         }

//         // Sanitize filename to prevent directory traversal or weird chars
//         $safeFile = preg_replace('/[^\w.\-]/', '_', $fileName);

//         // Force .txt extension if not already present
//         if (pathinfo($safeFile, PATHINFO_EXTENSION) !== 'txt') {
//             $safeFile .= '.txt';
//         }

//         $log_file  = $log_dir . '/' . $safeFile;
//         $log_entry = sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $message, PHP_EOL);

//         file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
//     }
// }

if (!function_exists('custom_log')) {
    /**
     * Write a custom log entry to the PHP error log.
     *
     * @param string $message  The log message to write.
     */
    function custom_log(string $message): void
    {
        $log_entry = sprintf('[%s] %s', date('Y-m-d H:i:s'), $message);
        error_log($log_entry);
    }
}
if (!function_exists('count_wishlist')) {
    /**
     * Count the number of items in the current user's wishlist.
     *
     * @return int|false The count of wishlist items, or false if user not logged in.
     */

    function count_wishlist()
    {
        global $wpdb;

        // Get the current user ID
        $user_id = get_current_user_id();
        if (!$user_id) {
            return false; // User not logged in
        }

        // Query to check if the product exists in the wishlist
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT count(*)
        FROM {$wpdb->prefix}users a
        LEFT JOIN {$wpdb->prefix}woodmart_wishlists b ON b.user_id = a.ID
        LEFT JOIN {$wpdb->prefix}woodmart_wishlist_products c ON c.wishlist_id = b.ID
        WHERE a.ID = %d",
            $user_id
        ));
        return ($exists > 0);
    }
}

if (!function_exists('mapping_membership_SH')) {
    function mapping_membership_SH($cardType)
    {
        switch ($cardType) {
            case 'G':
                return 'p1_membership_gold';
                break;
            case 'C':
                return 'p1_membership_classic';
                break;
            default:
                return 'p1_membership_basic';
                break;
        }
    }
}

if (!function_exists('my_get_membership_plan_id_by_slug')) {
    function my_get_membership_plan_id_by_slug($slug)
    {
        $plan = get_page_by_path($slug, OBJECT, 'wc_membership_plan');
        return $plan ? (int) $plan->ID : 0;
    }
}

if (!function_exists('my_assign_or_switch_membership')) {
    /**
     * Assign a plan to a user:
     * - If the user already has this plan, (re)activate it and optionally update dates.
     * - If not, create it and cancel any other active plans (so the user ends with only this plan).
     */
    function my_assign_or_switch_membership($user_id, $plan_id, $status = 'active', $cancel_others = true)
    {

        if (! function_exists('wc_memberships_create_user_membership')) {
            return new WP_Error('missing_wc_memberships', 'WooCommerce Memberships is not active.');
        }
        if (! $user_id || ! $plan_id) {
            return new WP_Error('bad_args', 'User ID or Plan ID missing.');
        }

        // 1) Does the user already have THIS plan?
        $existing_for_plan = wc_memberships_get_user_membership($user_id, $plan_id);
        if ($existing_for_plan) {
            // Make sure it's active (or set the status you want)
            if ($existing_for_plan->get_status() !== $status) {
                $existing_for_plan->update_status($status);
            }
            // Optional: update dates
            // $existing_for_plan->set_start_date( current_time( 'mysql', true ) );
            // $existing_for_plan->set_end_date( '' ); // or a date string
            // $existing_for_plan->save();

            // Optionally cancel other plans so the user only keeps this one
            if ($cancel_others) {
                $all = wc_memberships_get_user_memberships($user_id);
                foreach ($all as $m) {
                    if ((int) $m->get_plan_id() !== (int) $plan_id && 'cancelled' !== $m->get_status()) {
                        $m->update_status('cancelled'); // or $m->delete( true );
                    }
                }
            }
            return $existing_for_plan;
        }

        // 2) No membership for this plan yet → create it
        $new = wc_memberships_create_user_membership(array(
            'user_id'    => $user_id,
            'plan_id'    => $plan_id,
            'status'     => $status,
            'start_date' => current_time('mysql', true),
            'source'     => 'API',
        ));

        if (! is_wp_error($new) && $cancel_others) {
            $all = wc_memberships_get_user_memberships($user_id);
            foreach ($all as $m) {
                if ($m->get_id() !== $new->get_id() && 'cancelled' !== $m->get_status()) {
                    $m->update_status('cancelled'); // or $m->delete( true );
                }
            }
        }

        return $new;
    }
}

if (!function_exists('senhengallInfo')) {
    /**
     * Retrieve Senheng user meta information for the current logged-in user.
     *
     * @return array|false An associative array of user meta data, or false if not logged in.
     */

    function senhengallInfo()
    {
        if (!is_user_logged_in()) {
            return false;
        }

        $current_user = wp_get_current_user();
        $data['user_id'] = $current_user->ID;
        $data['idsso'] = get_user_meta($current_user->ID, 'idsso', true);
        $data['idmapping']    = get_user_meta($current_user->ID, 'idmapping', true);
        $data['is_pin'] = get_user_meta($current_user->ID, 'is_pin', true);
        $data['cust_allp1no'] = get_user_meta($current_user->ID, 'cust_allp1no', true);
        $data['cust_cardtype'] = get_user_meta($current_user->ID, 'cust_cardtype', true);
        $data['cust_contact'] = get_user_meta($current_user->ID, 'cust_contact', true);
        $data['cust_contact_key'] = get_user_meta($current_user->ID, 'cust_contact_key', true);
        $data['cust_country'] = get_user_meta($current_user->ID, 'cust_country', true);
        $data['cust_email'] = get_user_meta($current_user->ID, 'cust_email', true);
        $data['cust_icno'] = get_user_meta($current_user->ID, 'cust_icno', true);
        $data['cust_id'] = get_user_meta($current_user->ID, 'cust_id', true);
        $data['cust_idmapping'] = get_user_meta($current_user->ID, 'cust_idmapping', true);
        $data['cust_name']    = get_user_meta($current_user->ID, 'cust_name', true);
        $data['cust_p1no']    = get_user_meta($current_user->ID, 'cust_p1no', true);
        $data['cust_all_cardtype']    = get_user_meta($current_user->ID, 'cust_all_cardtype', true);
        $data['ambassodor_id']    = get_user_meta($current_user->ID, 'ambassodor_id', true);

        return $data;
    }
}

if (!function_exists('insiderLocale')) {

    function insiderLocale()
    {
        $env = getChannelWeb();

        if ($env == 'Senheng') {
            return 'en_MY:2';
        }

        if ($env == 'SenQ') {
            return 'en_MY:3';
        }

        return 'en_MY:1';
    }
}

if (! function_exists('ipay88_types_mapping')) {

    function ipay88_types_mapping()
    {
        return [
            'image' => [
                '2'   => 'payment_card',
                '6'   => 'maybank2u',
                '8'   => 'allianceonline',
                '10'  => 'ambank',
                '14'  => 'rhb',
                '15'  => 'hong_leong_connect',
                '20'  => 'cimb',
                '31'  => 'publicbank',
                '102' => 'bankrakyat',
                '103' => 'affinbank',
                '124' => 'bsn',
                '134' => 'bankislam',
                '152' => 'uobbankmy',
                '166' => 'bank_muamalat',
                '167' => 'ocbc',
                '168' => 'standard_chartered',
                '198' => 'hsbc',

                '210' => 'boost_wallet',
                '523' => 'grabpay',
                '538' => 'tng',
                '542' => 'maybank_payqr',
                '801' => 'shopeepay',

                '111' => 'publicbank',
                '112' => 'maybank_ezypay_visa_mc',
                '115' => 'maybank_ezypay_amex',
                '157' => 'hsbc_instalment',
                '174' => 'cimb_easy_pay',
                '179' => 'hongleong_epp',
                '534' => 'rhb_instalment',
                '606' => 'ambank_epp',
                '727' => 'standard_chartered_instalment',
                '891' => 'atome',
            ],

            'id' => [
                '2'   => '_credit_card',
                '6'   => '_maybank2u',
                '8'   => '_alliance_online',
                '10'  => '_ambank',
                '14'  => '_rhb',
                '15'  => '_hongleong',
                '20'  => '_cimb_clicks',
                '31'  => '_publicbank',
                '102' => '_bankrakyat',
                '103' => '_affinbank',
                '124' => '_bsn',
                '134' => '_bankislam',
                '152' => '_uob',
                '166' => '_bankmuamalat',
                '167' => '_ocbc',
                '168' => '_standard_chartered',
                '198' => '_hsbc',

                '210' => '_boost_wallet',
                '523' => '_grabpay',
                '538' => '_tng',
                '542' => '_maybank_payqr',
                '801' => '_shopeepay',

                '111' => '_publicbank_epp',
                '112' => '_maybank_ezypay_vm',
                '115' => '_maybank_ezypay_amex',
                '157' => '_hsbc_instalment',
                '174' => '_cimb_easy_pay',
                '179' => '_hongleong_epp',
                '534' => '_rhb_instalment',
                '606' => '_ambank_epp',
                '727' => '_standard_chartered_instalment',
                '891' => '_atome',
            ],

            'name' => [
                '2'   => 'Credit/Debit Card',
                '6'   => 'Maybank2U',
                '8'   => 'AllianceOnline',
                '10'  => 'Ambank',
                '14'  => 'RHB',
                '15'  => 'HongLeongConnect',
                '20'  => 'CIMB',
                '31'  => 'PublicBank',
                '102' => 'BankRakyat',
                '103' => 'AffinBank',
                '124' => 'BSN',
                '134' => 'BankIslam',
                '152' => 'UOBBank',
                '166' => 'BankMuamalat',
                '167' => 'OCBC',
                '168' => 'StandardChartered',
                '198' => 'HSBC',

                '210' => 'BoostWallet',
                '523' => 'GrabPay',
                '538' => 'TNG',
                '542' => 'MaybankPayQR',
                '801' => 'ShopeePay',

                '111' => 'PublicBankEPP',
                '112' => 'MaybankEzyPayVisaMastercard',
                '115' => 'MaybankEzyPayAMEX',
                '157' => 'HSBCInstalment',
                '174' => 'CIMBEasyPay',
                '179' => 'HongLeongEPP',
                '534' => 'RHBInstalment',
                '606' => 'AmBankEPP',
                '727' => 'StandardCharteredInstalment',
                '891' => 'Atome',
            ],
        ];
    }
}

if (! function_exists('sh_logs')) {

    function sh_logs($message)
    {
        $logger = wc_get_logger();
        $logger->add('senheng_core_logs', $message);
    }
}

if (! function_exists('wc_notice_to_plain_text')) {
    /**
     * Convert a WooCommerce notice (HTML) to plain text.
     *
     * @param string $notice The WooCommerce notice in HTML format.
     * @return string The plain text version of the notice.
     */
    function wc_notice_to_plain_text($notice)
    {
        return html_entity_decode(
            wp_strip_all_tags($notice),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
