jQuery( document ).ready( function( $ ) {

	// Translation

	const { __, _x, _n, _nx, sprintf } = wp.i18n;

    // Add badges to product pages by appending to selectors instead of relying on woocommerce_single_product_image_thumbnail_html, which is disabled when this compatibility mode product pages is enabled

    var productImageSelectors = $( '#wcpb-product-badges-compatibility-mode-product-pages' ).attr( 'data-selectors' );
    var productImageBadges = $( '#wcpb-product-badges-compatibility-mode-product-pages' ).html();

    $( productImageBadges ).appendTo( productImageSelectors );

    // Remove the temporary div as no longer needed

    $( '#wcpb-product-badges-compatibility-mode-product-pages' ).remove();

});