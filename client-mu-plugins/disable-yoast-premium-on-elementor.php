<?php
/**
 * Plugin Name: Disable Yoast Premium on Elementor
 */

function vip_is_elementor_request(): bool {
	// Elementor editor (wp-admin)
	if ( is_admin() && isset($_GET['action']) && $_GET['action'] === 'elementor' ) {
		return true;
	}

	// Elementor preview iframe / preview links
	if ( isset($_GET['elementor-preview']) ) {
		return true;
	}

	return false;
}

add_filter( 'option_active_plugins', function( $plugins ) {
	if ( ! vip_is_elementor_request() || ! is_array( $plugins ) ) {
		return $plugins;
	}

	// adjust if your path differs
	$yoast_premium = 'wordpress-seo-premium/wp-seo-premium.php'; 
	$index = array_search( $yoast_premium, $plugins, true );

	if ( $index !== false ) {
		unset( $plugins[ $index ] );
		return array_values( $plugins );
	}

	return $plugins;
}, 1 );