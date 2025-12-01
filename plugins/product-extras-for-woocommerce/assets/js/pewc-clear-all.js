/**
 * Functions for Clear All Options
 * @since 3.21.7
 */
const pewc_clear_all = {

	init: function() {

		// allow a custom script to trigger this manually
		// e.g. $( 'body' ).trigger( 'pewc_clear_all_options' );
		jQuery( 'body' ).on( 'pewc_clear_all_options', function( e ){
			pewc_clear_all.clear_all();
		});

		jQuery( '.pewc-clear-all' ).on( 'click', function( e ) {
			jQuery( 'body' ).trigger( 'pewc_clear_all_options' );
		});

	},

	clear_all: function() {
		jQuery( '.pewc-item' ).each( function( index, element ){
			if ( ! pewc_clear_all.is_hidden_field( jQuery( this ) ) && ! jQuery(this).hasClass( 'pewc-reset-me' ) ) {
				jQuery(this).addClass( 'pewc-reset-me' );
			}
		});

		jQuery( 'body' ).trigger( 'pewc_reset_fields' );
	},

	is_hidden_field: function( pewc_item ) {
		if (
			pewc_item.hasClass( 'pewc-hidden-field' ) || 
			pewc_clear_all.is_hidden_group( pewc_item.closest( '.pewc-group-wrap' ) ) || 
			( pewc_item.hasClass( 'pewc-variation-dependent') && ! pewc_item.hasClass( 'active' ) )
		) {
			return true;
		} else {
			return false;
		}
	},

	is_hidden_group: function( pewc_group ) {
		if ( pewc_group.hasClass( 'pewc-group-hidden' ) ) {
			return true;
		} else {
			return false;
		}
	}

};

jQuery( document ).ready( function(){
	pewc_clear_all.init();
});
