<?php
/**
 * Plugin Name: Insider SW SDK Rewrite
 * Description: Serves insider-sw-sdk.js from the site root via rewrite rule.
 */

function insider_sw_sdk_rewrite() {
    add_rewrite_rule( '^insider-sw-sdk\.js$', 'index.php?insider_sw_sdk=true', 'top' );
}
add_action( 'init', 'insider_sw_sdk_rewrite', 10 );

function insider_sw_sdk_query_var( $public_query_vars ) {
    $public_query_vars[] = 'insider_sw_sdk';
    return $public_query_vars;
}
add_filter( 'query_vars', 'insider_sw_sdk_query_var', 10, 1 );

function insider_sw_sdk_request( $wp ) {
    if ( isset( $wp->query_vars['insider_sw_sdk'] ) && 'true' === $wp->query_vars['insider_sw_sdk'] ) {
        header( 'Content-Type: application/javascript' );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Service worker JS must be served as-is.
        echo file_get_contents( WP_PLUGIN_DIR . '/insider-sw/insider-sw-sdk.js' );
        exit;
    }
}
add_action( 'parse_request', 'insider_sw_sdk_request', 10, 1 );