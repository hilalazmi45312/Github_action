<?php

// 1) Rewrite: /senheng-api/<endpoint>
add_action('init', function () {
    add_rewrite_rule(
        '^api/(.+)$',
        'index.php?senheng_api_endpoint=$matches[1]',
        'top'
    );

    add_rewrite_rule(
        '^api$',
        'index.php?senheng_api_endpoint=',
        'top'
    );
});

// 2) Register the query var
add_filter('query_vars', function ($vars) {
    $vars[] = 'senheng_api_endpoint';
    return $vars;
});

// Ultra-fast & SAFE plugin reduction for API only
add_filter('option_active_plugins', function ($plugins) {
    // Only run on real HTTP requests (skip cron, wp-cli, etc.)
    if (!isset($_SERVER['REQUEST_URI'])) {
        return $plugins;
    }

    // Check if request starts with your API path
    if (strpos($_SERVER['REQUEST_URI'], '/senheng-api/') === 0) {
        // This IS your API → load only essential plugins
        $keep = [
            'woocommerce/woocommerce.php',
        ];

        $filtered = [];
        foreach ($plugins as $path => $plugin) {
            if (in_array($path, $keep, true)) {
                $filtered[$path] = $plugin;
            }
        }
        return $filtered;
    }
    return $plugins;
});

// Prevent theme from loading (saves ~100–200ms)
add_filter('template_include', function ($template) {
    if (get_query_var('senheng_api_endpoint')) {  // FIXED
        return ABSPATH . WPINC . '/rest-api.php'; // or any existing file
        // or use: return false; on most hosts
    }
    return $template;
});

// Optional: Disable theme entirely
add_action('setup_theme', function () {
    if (get_query_var('api-type')) {
        return;
    }
});

add_action('template_redirect', function () {
    $endpoint = get_query_var('senheng_api_endpoint');
    if (!$endpoint) {
        return;
    }

    // Allow all methods early
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    $endpoint = trim($endpoint, '/');
    $method   = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    nocache_headers();
    header('Content-Type: application/json; charset=utf-8');

    $headers = function_exists('getallheaders') ? getallheaders() : [];

    $rawBody = file_get_contents('php://input');
    $body    = json_decode($rawBody, true);
    if (!is_array($body)) {
        $body = [];
    }


    $input = $body;
    $input['_headers'] = $headers;
    $input['_method']  = $method;

    /**
     * Route definitions
     *
     * key    = base endpoint (without path params)
     * handler = function name (or callable)
     * methods = allowed HTTP methods
     * params  = trailing URL segments mapped to these names (right-to-left)
     */
    $routes = [
        'user/web/login/login-by-mobile-app'      =>
        [
            'handler' => 'auth_login',
            'methods' => ['POST'],
        ],
        'user/web/current-user'                   =>
        [
            'handler' => 'auth_current_user',
            'methods' => ['GET'],
        ],
        'user/web/shipping-address/self/list-all' =>
        [
            'handler' => 'get_address_book',
            'methods' => ['GET'],
        ],
        'user/web/shipping-address/add'           =>
        [
            'handler' => 'add_address_book',
            'methods' => ['POST'],
        ],
        'user/web/shipping-address/edit'          =>
        [
            'handler' => 'edit_address_book',
            'methods' => ['PUT'],
        ],
        'user/web/shipping-address/delete'        =>
        [
            'handler' => 'delete_address_book',
            'methods' => ['DELETE'],
            'params'  => ['address_id'],
        ],
        'item/favorites/paging'        =>
        [
            'handler' => 'get_wishlist',
            'methods' => ['GET'],
            'params'  => ['pageSize', 'targetType', 'pageNo', 'userId'],
        ],
        'item/favorites/add'        =>
        [
            'handler' => 'add_wishlist',
            'methods' => ['POST'],
            'params'  => ['targetType', 'userId', 'targetId', 'tenantId'],
        ],
        'item/favorites'        =>
        [
            'handler' => 'delete_wishlist',
            'methods' => ['PUT'],
            'params'  => ['pageSize', 'targetType', 'pageNo', 'userId', 'targetId', 'tenantId'],
        ],
        'trade/cart/query/count'        =>
        [
            'handler' => 'get_cart_count',
            'methods' => ['GET'],
            'params'  => ['cartType', 'clientType'],
        ],
        'trade/cart/query/render'        =>
        [
            'handler' => 'get_cart',
            'methods' => ['GET'],
            'params'  => ['cartType', 'clientType', 'divisionIds'],
        ],
        'gateway' =>
        [
            'handler' => 'pampas_router',
            'methods' => ['POST'],
        ],
    ];

    // Split path and try to match a base route, peeling off params from the end
    $segments        = explode('/', $endpoint);
    $paramsFromPath  = [];
    $matchedRouteKey = null;

    while (!empty($segments)) {
        $try = implode('/', $segments);

        if (isset($routes[$try])) {
            $matchedRouteKey = $try;
            break;
        }

        // Move last segment into params buffer
        $paramsFromPath[] = array_pop($segments);
    }

    if ($matchedRouteKey === null) {
        wp_send_json_error(['message' => 'Unknown endpoint: ' . $endpoint], 404);
    }

    $route   = $routes[$matchedRouteKey];
    $handler = $route['handler'];

    // Check HTTP method
    $allowed = $route['methods'] ?? ['POST'];
    if (!in_array($method, $allowed, true)) {
        // wp_send_json_error([
        //     // 'message' => 'Method not allowed. Use ' . implode(', ', $allowed) . '.',
        // ], 405);
        header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
        exit;
    }

    // Map trailing segments → named params
    if (!empty($route['params'])) {
        $paramsFromPath = array_reverse($paramsFromPath); // restore left-to-right

        foreach ($route['params'] as $i => $name) {
            if (isset($paramsFromPath[$i])) {
                $input[$name] = sanitize_text_field($paramsFromPath[$i]);
            }
        }
    }

    // Optionally, for GET routes, you might want to merge query params more strongly:
    // if ($method === 'GET') {
    $input = array_merge($_GET, $input);
    // }

    // Call handler
    if (is_callable($handler)) {
        $handler($input);
    } elseif (function_exists($handler)) {
        $handler($input);
    } else {
        wp_send_json_error(['message' => 'Handler not callable: ' . $handler], 500);
    }

    exit;
});


function test()
{
    $items = WC()->cart->get_cart();
    wp_send_json_success(['cart_items' => $items]);
}
