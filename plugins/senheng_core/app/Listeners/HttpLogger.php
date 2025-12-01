<?php

class HttpLogger
{
    public static function listen($response, $type, $class, $args, $url)
    {
        if (!defined('SENHENG_ENV') || SENHENG_ENV !== 'local') {
            return;
        }

        // Skip known WordPress internal API calls
        if (self::is_wordpress_internal_url($url) || self::is_cron_url($url)) {
            return;
        }

        // Check if it's an error response first
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            if (empty($error_message)) {
                return; // nothing to log
            }
            $log = [];
            $log[] = "📡 HTTP API Call (Error)";
            $log[] = "🔗 URL: $url";
            $log[] = "❌ WP_Error: $error_message";
            // custom_log(implode("\n", $log), 'http-logger');
            log_api_request(
                $url,
                'HTTP-LOGGER',
                json_encode($log)
            );
            return;
        }

        // Extract response data
        $code       = wp_remote_retrieve_response_code($response);
        $resp_body  = wp_remote_retrieve_body($response);

        // ✅ Only log if response body has actual data
        if (empty(trim($resp_body))) {
            return; // no data, skip logging
        }

        //if response body is html, skip logging
        if (stripos($resp_body, '<!DOCTYPE html>') !== false || stripos($resp_body, '<html') !== false) {
            return;
        }

        // Now build your log normally
        $log = [];
        $log[] = "📡 HTTP API Call";
        $log[] = "🔗 URL: $url";
        $log[] = "📦 Type: $type";
        $log[] = "🚚 Transport: $class";
        $log[] = "✅ Status: $code";
        $log[] = "📥 Response: $resp_body";

        if (!empty($args['headers'])) {
            $log[] = "🧾 Headers: " . print_r($args['headers'], true);
        }

        if (!empty($args['body'])) {
            $body = is_array($args['body']) ? wp_json_encode($args['body']) : $args['body'];
            $log[] = "📤 Body: $body";
        }

        // custom_log(implode("\n", $log), 'http-logger');
        log_api_request(
            $url,
            'HTTP-LOGGER',
            json_encode($log)
        );
    }

    private static function is_wordpress_internal_url($url)
    {
        $wp_domains = [
            'api.wordpress.org',
            'downloads.wordpress.org',
            'w.org',
        ];

        foreach ($wp_domains as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function is_cron_url($url)
    {
        return (strpos($url, 'wp-cron.php') !== false || strpos($url, 'doing_wp_cron') !== false);
    }
}
