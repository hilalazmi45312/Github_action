jQuery( document ).ready( function( $ ) {

	// Translation

	const { __, _x, _n, _nx, sprintf } = wp.i18n;

	// Field functionality

	$( '.wcpb-product-badges-flatpickr' ).flatpickr({
		enableTime: true,
		dateFormat: 'Y-m-d H:i',
	});

	$( '.wcpb-product-badges-color-picker' ).wpColorPicker();

	$( '.wcpb-product-badges-select2' ).select2({
		'width': '100%',
	});

	$( '.wcpb-product-badges-select2-ajax-display-products-specific-categories' ).select2({
		'ajax': {
			'cache': true,
			'data': function ( params ) {
				return {
					'action': 'wcpb_product_badges_select2_ajax_display_products_specific_categories',
					'search_term': params.term
				};
			},
			'dataType': 'json',
			'delay': 250,
			'processResults': function ( response ) {
				return {
					'results': response,
				};
			},
			'url': ajaxurl, // ajaxurl is the WordPress AJAX url variable included in dashboard
		},
		'language': {
			'searching': function() {
				return __( 'Getting categories...', 'wcpb-product-badges' );
			}
		},
		'width': '100%',
	});

	$( '.wcpb-product-badges-select2-ajax-display-products-specific-tags' ).select2({
		'ajax': {
			'cache': true,
			'data': function ( params ) {
				return {
					'action': 'wcpb_product_badges_select2_ajax_display_products_specific_tags',
					'search_term': params.term
				};
			},
			'dataType': 'json',
			'delay': 250,
			'processResults': function ( response ) {
				return {
					'results': response,
				};
			},
			'url': ajaxurl, // ajaxurl is the WordPress AJAX url variable included in dashboard
		},
		'language': {
			'searching': function() {
				return __( 'Getting tags...', 'wcpb-product-badges' );
			}
		},
		'width': '100%',
	});

	$( '.wcpb-product-badges-select2-ajax-display-products-specific-products' ).select2({
		'ajax': {
			'cache': true,
			'data': function ( params ) {
				return {
					'action': 'wcpb_product_badges_select2_ajax_display_products_specific_products',
					'search_term': params.term
				};
			},
			'dataType': 'json',
			'delay': 250,
			'processResults': function ( response ) {
				return {
					'results': response,
				};
			},
			'url': ajaxurl, // ajaxurl is the WordPress AJAX url variable included in dashboard
		},
		'language': {
			'searching': function() {
				return __( 'Getting products...', 'wcpb-product-badges' );
			}
		},
		'width': '100%',
	});

	$( '.wcpb-product-badges-select2-ajax-display-products-specific-shipping-classes' ).select2({
		'ajax': {
			'cache': true,
			'data': function ( params ) {
				return {
					'action': 'wcpb_product_badges_select2_ajax_display_products_specific_shipping_classes',
					'search_term': params.term
				};
			},
			'dataType': 'json',
			'delay': 250,
			'processResults': function ( response ) {
				return {
					'results': response,
				};
			},
			'url': ajaxurl, // ajaxurl is the WordPress AJAX url variable included in dashboard
		},
		'language': {
			'searching': function() {
				return __( 'Getting shipping classes...', 'wcpb-product-badges' );
			}
		},
		'width': '100%',
	});

	$( '.wcpb-product-badges-select2-meta-box-product-level-badge-select' ).select2({
		'placeholder': __( 'Select a badge', 'wcpb-product-badges' ),
	});

	let productLevelBadgeSelectRemoveIds = [];

	$( '.wcpb-product-badges-select2-meta-box-product-level-badge-select' ).on( 'select2:unselect', function (e) {
		const id = e.params.data.id;
		if ( !productLevelBadgeSelectRemoveIds.includes( id ) ) {
			productLevelBadgeSelectRemoveIds.push( id );
			$( 'input[name="wcpb_product_badges_meta_box_product_level_badge_select_removals"]' ).val( productLevelBadgeSelectRemoveIds.join( ',' ) );
		}
	});

	$( '.wcpb-product-badges-select2-meta-box-product-level-badge-select' ).on( 'select2:select', function (e) {
		const id = e.params.data.id;
		const index = productLevelBadgeSelectRemoveIds.indexOf( id );
		if ( index !== -1 ) {
			productLevelBadgeSelectRemoveIds.splice( index, 1 );
			$( 'input[name="wcpb_product_badges_meta_box_product_level_badge_select_removals"]' ).val( productLevelBadgeSelectRemoveIds.join( ',' ) );
		}
	});

	// Badge > type > expand

	function badgeTypeExpand() {

		// Image library

		if ( $( 'input:radio[name="wcpb_product_badges_badge_type"]:checked' ).val() !== 'image_library' ) {

			$( '#wcpb-product-badges-badge-image-library-expand' ).css( 'display', 'none' ); // Not show/hide as when displayed requires flex for content within
			$( '#wcpb-product-badges-badge-image-library-filters' ).hide();

		} else {

			$( '#wcpb-product-badges-badge-image-library-expand' ).css( 'display', 'flex' ); // Not show/hide as when displayed requires flex for content within

			if ( $( '#wcpb-product-badges-badge-image-library-filters div' ).html() == '' ) {

				$( '#wcpb-product-badges-badge-image-library-filters-before-append' ).appendTo( $( '#wcpb-product-badges-badge-image-library-filters div' ) ); // Filter selection moved due to issues around fixing positions and overflow scroll div

			}

			$( '#wcpb-product-badges-badge-image-library-filters' ).show();

			imageLibraryScrollToSelected();

		}

		// Image custom

		if ( $( 'input:radio[name="wcpb_product_badges_badge_type"]:checked' ).val() !== 'image_custom' ) {

			$( '#wcpb-product-badges-badge-image-custom-expand' ).hide();

		} else {

			$( '#wcpb-product-badges-badge-image-custom-expand' ).show();

		}

		// Countdown

		if ( $( 'input:radio[name="wcpb_product_badges_badge_type"]:checked' ).val() !== 'countdown' ) {

			$( '#wcpb-product-badges-badge-countdown-expand' ).css( 'display', 'none' ).css( 'flex-wrap', 'unset' ); // Not show/hide as when displayed requires flex for content within

		} else {

			$( '#wcpb-product-badges-badge-countdown-expand' ).css( 'display', 'flex' ).css( 'flex-wrap', 'wrap' ); // Not show/hide as when displayed requires flex for content within

		}

		// Text

		if ( $( 'input:radio[name="wcpb_product_badges_badge_type"]:checked' ).val() !== 'text' ) {

			$( '#wcpb-product-badges-badge-text-expand' ).css( 'display', 'none' ).css( 'flex-wrap', 'unset' ); // Not show/hide as when displayed requires flex for content within

		} else {

			$( '#wcpb-product-badges-badge-text-expand' ).css( 'display', 'flex' ).css( 'flex-wrap', 'wrap' ); // Not show/hide as when displayed requires flex for content within

		}

		// Code

		if ( $( 'input:radio[name="wcpb_product_badges_badge_type"]:checked' ).val() !== 'code' ) {

			$( '#wcpb-product-badges-badge-code-expand' ).hide();

		} else {

			$( '#wcpb-product-badges-badge-code-expand' ).show();

		}

	}

	badgeTypeExpand();

	$( 'input:radio[name="wcpb_product_badges_badge_type"]' ).change( function() {

		badgeTypeExpand();

	});

	// Badge > type > image library > expand > selection

	$( 'body' ).on( 'click', '#wcpb-product-badges-badge-image-library-expand .wcpb-product-badges-badge-image-library-image', function( e ) {

		e.preventDefault();

		$( '#wcpb-product-badges-badge-image-library-image' ).val( $( this ).attr( 'data-image' ) );

		$( '#wcpb-product-badges-badge-image-library-expand .wcpb-product-badges-badge-image-library-image' ).each( function() {

			$( this ).removeClass( 'wcpb-product-badges-badge-image-library-image-selected' );

		});

		$( this ).addClass( 'wcpb-product-badges-badge-image-library-image-selected' );

	});

	// Badge > type > image library > expand > selection > scroll to selected on page load

	function imageLibraryScrollToSelected() {

		var imageLibraryScrollToSelectedParent = $( '#wcpb-product-badges-badge-image-library-expand' );
		var imageLibraryScrollToSelectedElement = $( '.wcpb-product-badges-badge-image-library-image-selected' );

		if ( imageLibraryScrollToSelectedParent.length > 0 && imageLibraryScrollToSelectedElement.length > 0 ) {

			imageLibraryScrollToSelectedParent.scrollTop( imageLibraryScrollToSelectedParent.scrollTop() + imageLibraryScrollToSelectedElement.position().top - imageLibraryScrollToSelectedParent.height() / 2 + imageLibraryScrollToSelectedElement.height() / 2 ); // Height and width divided by 2 ensures selected is centered in the parent

		}

	}

	// Badge > type > image library > expand > filters

	$( 'body' ).on( 'change', '#wcpb-product-badges-badge-image-library-filters-before-append select', function( e ) {

		e.preventDefault();

		var filterType = '';
		var filterColor = '';
		var filterResultsFound = 0;

		$( '#wcpb-product-badges-badge-image-library-no-results' ).remove();

		$( '#wcpb-product-badges-badge-image-library-expand .wcpb-product-badges-badge-image-library-image' ).hide();

		$( '#wcpb-product-badges-badge-image-library-filters-before-append select' ).each( function( index ) {

			if ( $( this ).attr( 'data-filter' ) == 'type' ) {

				filterType = $( this ).val();

			}

			if ( $( this ).attr( 'data-filter' ) == 'color' ) {

				filterColor = $( this ).val();

			}

		});

		$( '#wcpb-product-badges-badge-image-library-expand .wcpb-product-badges-badge-image-library-image' ).each( function() {

			if ( $( this ).hasClass( filterType ) && $( this ).hasClass( filterColor ) ) {

				$( this ).fadeIn( 1000 );
				filterResultsFound = filterResultsFound + 1;

			}

		});

		if ( filterResultsFound == 0 ) {

			$( '#wcpb-product-badges-badge-image-library-expand' ).append( '<div id="wcpb-product-badges-badge-image-library-no-results">' + $( '#wcpb-product-badges-badge-image-library-filters-before-append' ).attr( 'data-filters-no-results-text' ) + '</div>' );

		}

	});

	// Display > products > specific > expand

	function displayProductsSpecificExpand() {

		if ( $( 'input:radio[name="wcpb_product_badges_display_products"]:checked' ).val() == 'specific' ) {

			$( '#wcpb-product-badges-display-products-specific-expand' ).css( 'display', 'flex' );

		} else {

			$( '#wcpb-product-badges-display-products-specific-expand' ).css( 'display', 'none' );

		}

	}

	// Display > products > custom rules > expand

	function displayProductsCustomRulesExpand() {

		if ( $( 'input:radio[name="wcpb_product_badges_display_products"]:checked' ).val() == 'custom_rules' ) {

			$( '#wcpb-product-badges-display-products-custom-rules-expand' ).css( 'display', 'flex' );

		} else {

			$( '#wcpb-product-badges-display-products-custom-rules-expand' ).css( 'display', 'none' );

		}

	}

	displayProductsSpecificExpand();
	displayProductsCustomRulesExpand();

	$( 'input:radio[name="wcpb_product_badges_display_products"]' ).change(function() {

		displayProductsSpecificExpand();
		displayProductsCustomRulesExpand();

	});

});