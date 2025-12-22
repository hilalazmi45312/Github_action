jQuery( window ).on( 'load resize', function( e ) {

	// Translation

	const { __, _x, _n, _nx, sprintf } = wp.i18n;

	// jQuery

	var $ = jQuery;

	// Loop badge append

	function loopBadgeAppend() {

		// Product images in the loop (which aren't block based) require some JS to add a div around the product loop image as they don't have a relative container and one can't be added using the PHP filters, badge is then appended into this container

		$( '.products .product img:not( .wcpb-product-badges-badge-img ):first-of-type' ).each( function() { // We target the first image (which isn't the badge img) as some themes can use a quickview and have multiple images, we don't want to apply the badges to those as it requires adding a relative div around the img and it is very likely the second images need to be positioned absolute to bring them above the first image. We keep the target elements as generic as possible as some themes replace the identifiers like .woocommerce-LoopProduct-link so we can't target based off that

			$( this ).css( 'display', 'block' ); // Ensures the first image is block and therefore does not have the small margin below which inline elements have, we only want this for the first as there could be further images added by some themes for quickview which are display: none, we don't want to interfere with those. It is inline-block and not block as inline-block ensures the text-align which maybe added in parent elements is accounted for, with block it would mean the img is not centered for themes which center it on mobile, etc
			$( this ).wrap( '<div class="wcpb-product-badges-loop-container" style="position: relative;"></div>' ); // Position relative added here and not with public.css as it should only be applied dynamically by the imgs found from this .each loop
			var badge = $( this ).closest( '.product' ).find( '.wcpb-product-badges-badge' ); // Closest .product is used rather than li as some themes may add more li elements (or replace the li with divs) within .product image (e.g. a theme used an li within the .products li for some quick view functionality and that was being picked up as the parent causing the badge to be got incorrectly and in turn positioned incorrectly)
			badge.appendTo( $( this ).closest( '.wcpb-product-badges-loop-container' ) );

		});

	}

	// Loop container styles

	function loopContainerStyles() {

		$( '.wcpb-product-badges-loop-container img:not( .wcpb-product-badges-badge-img )' ).each( function() {

			$( this ).closest( '.wcpb-product-badges-loop-container' ).css( 'max-width', '100%' ); // Set the loop container to max-width 100% (without this it would have a max-width px set from earlier so when getting the width in the next line it would only be the image width within the constraints of the max width set, this would mean once the size is reduced it wouldn't get larger if the device width was made larger)
			$( this ).closest( '.wcpb-product-badges-loop-container' ).css( 'max-width', $( this ).width() ).css( 'margin', '0 auto' ); // Margin is 0 auto as the container is display block and where the product container the loop container is in is a larger width it would align to the left (e.g. on responsive), this ensures it is centered, most themes center the products when at one column per product

		});

	}

	// Load/resize triggers

	if ( 'load' == e.type ) {

		loopBadgeAppend();
		loopContainerStyles(); // Must be after loopBadgeAppend()

	} else if ( 'resize' == e.type ) {

		loopContainerStyles(); // Ensures as window changed the loop container styles are amended

	}

});