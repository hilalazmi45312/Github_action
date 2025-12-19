jQuery( window ).on( 'load', function() {

	// Translation

	const { __, _x, _n, _nx, sprintf } = wp.i18n;

	// jQuery

	var $ = jQuery;

	// Magnify

	var magnify = $( '.woocommerce-product-gallery__trigger' );

	if ( magnify.length > 0 ) {

		var badge = magnify.parent().find( '.wcpb-product-badges-badge' );

		// Any left moves the magnify to top right (bottom not used as make magnifier display down with the gallery images)

		if ( badge.hasClass( 'wcpb-product-badges-badge-top-left' ) || badge.hasClass( 'wcpb-product-badges-badge-bottom-left' ) ) {

			magnify.css( 'top', '.875em' ).css( 'left', 'auto' ).css( 'bottom', 'auto' ).css( 'right', '.875em' ); // .875em is default WooCommerce position for magnify

		} else {

			// Any right moves the magnify to top left (bottom not used as make magnifier display down with the gallery images)

			if ( badge.hasClass( 'wcpb-product-badges-badge-top-right' ) || badge.hasClass( 'wcpb-product-badges-badge-bottom-right' ) ) {

				magnify.css( 'top', '.875em' ).css( 'left', '.875em' ).css( 'bottom', 'auto' ).css( 'right', 'auto' ); // .875em is default WooCommerce position for magnify

			}

		}

	}

});