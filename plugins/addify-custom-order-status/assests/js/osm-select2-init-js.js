jQuery(document).ready(function($) {
	// User roles multi select
	jQuery('#osm-user-roles').select2();
	//Categories Multi Select
	jQuery('#osm_categories').select2();
	// Billing Countries
	jQuery('#osm_billing_country').select2();
	// Shipping Countries
	jQuery('#osm_shipping_country').select2();
  
	jQuery('#osm-products').select2({
		placeholder: "Search for products...",
		ajax: {
			url: ajaxurl, // AJAX URL is predefined in WordPress admin
			dataType: 'json',
			delay: 250, // delay in ms while typing when to perform a AJAX search
			data: function (params) {
				  return {
						q: params.term, // search query
						action: 'osm_get_products' // AJAX action for admin-ajax.php
				};
			},
			processResults: function( data ) {
				var options = [];
				if ( data ) {
			
					// data is the array of arrays, and each of them contains ID and the Label of the option
					$.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
						options.push( { id: text[0], text: text[1]  } );
					});
				
				}
				return {
					results: options
				};
			},
			cache: true
		},
		minimumInputLength: 3

	});
	 // Select and Deselect all function
	 jQuery( document ).ready( function() {
		jQuery( '.osm-select-all' ).click( function( event ) {
			event.preventDefault();
			jQuery( this ).closest( 'td' ).find( 'select.chosen_select' ).select2( 'destroy' ).find( 'option' ).prop( 'selected', 'selected' ).end().select2();
			return false;
		} );
		jQuery( '.osm-deselect-all' ).click( function( event ) {
			event.preventDefault();
			jQuery( this ).closest( 'td' ).find( 'select.chosen_select' ).val( '' ).change();
			return false;
		} );
	 } );
});
