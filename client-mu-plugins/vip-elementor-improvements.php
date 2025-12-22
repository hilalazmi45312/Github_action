<?php

/**
 * Disable regeneration of thumbnails on the fly for Gambit theme.
 *
 * On WordPress VIP, separate intermediate image files are not created for
 * images that are uploaded to a WordPress media library.
 * 
 * @link https://wordpressvip.zendesk.com/hc/en-us/requests/217289
 */
add_filter( 'image_downsize', function ( $out, $id, $size ) {
remove_filter( 'image_downsize', 'gambit_otf_regen_thumbs_media_downsize', 10, 3 );

return $out;
}, 1, 3 );


add_filter('woocommerce_ajax_variation_threshold', function() {
    return 1; // keep AJAX but avoid preloading all variations
});

add_filter('woocommerce_load_all_data_on_get_variation', '__return_false');

// originates from the woocommerce-analytics script that comes bundled with Jetpack MU Plugin on WPVIP Platform.
add_filter( 'js_do_concat', function( $do_concat, $handle ) {
	if ( 'woocommerce-analytics-client' === $handle ) {
		return false;
	}
	return $do_concat;
}, 10, 2 );