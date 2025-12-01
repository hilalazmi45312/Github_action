( function() {
	jQuery(document).ready( function() {

		function crproUpdateCountriesInput() {
			let countriesInput = {};
			jQuery('.crpro-countries-table tbody tr.crpro-countries-tr').each( function( i ) {
				const tds = jQuery(this).find( 'td' );
				const delay2enbld = tds.eq( 2 ).data( 'delay2enbld' );
				let delay2 = '';
				if ( delay2enbld ) {
					delay2 = tds.eq( 2 ).text();
				}
				countriesInput[tds.eq( 0 ).data( 'country' )] = {
					'delay': tds.eq( 1 ).text(),
					'delay2enbld': delay2enbld,
					'delay2': delay2,
					'sendat': tds.eq( 3 ).data( 'sendat' )
				}
			} );
			jQuery('#ivole_country_delays').val( JSON.stringify( countriesInput ) );
		}

		// display a modal to a add country
		jQuery('.crpro-button-add-country').on( 'click', function( e ) {
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-title' ).text( crpro_object.modal_new );
			jQuery('.crpro-countries-modal-internal #crpro_select_country' ).prop( "disabled", false );
			jQuery('.crpro-countries-table tbody tr.crpro-countries-tr').each( function( i ) {
				jQuery('.crpro-countries-modal-internal #crpro_select_country option[value="' + jQuery(this).find( 'td' ).eq( 0 ).data( 'country' ) + '"]').prop( "disabled", true );
			} );
			const remainingCountries = jQuery('.crpro-countries-modal-internal #crpro_select_country option:not([disabled]');
			if( 0 < remainingCountries.length ) {
				remainingCountries.eq( 0 ).prop( 'selected', true );
			} else {
				alert( 'All countries have already been added' );
				return;
			}
			jQuery('.crpro-countries-modal-internal #crpro_sending_delay').val( crpro_object.def_sending_delay );
			jQuery('.crpro-countries-modal-internal #crpro_sending_delay2').val( crpro_object.def_sending_delay2 );
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-error').text( '' );
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-error').removeClass( 'crpro-error-display' );
			jQuery('.crpro-countries-modal-cont').addClass( 'crpro-countries-modal-visible' );
		} );

		// prevent propogation of a click event
		jQuery('.crpro-countries-modal-internal, .crpro-delete-modal-internal').on( 'click', function( e ) {
			e.stopPropagation();
		} );

		// close a modal to a add country
		jQuery('.crpro-countries-modal-close-top, .crpro-countries-modal-cancel, .crpro-countries-modal-cont').on( 'click', function( e ) {
			jQuery('.crpro-countries-modal-cont').removeClass( 'crpro-countries-modal-visible' );
		} );

		// close a modal to confirm deletion
		jQuery('.crpro-countries-modal-close-top, .crpro-countries-modal-cancel, .crpro-delete-modal-cont').on( 'click', function( e ) {
			jQuery('.crpro-delete-modal-cont').removeClass( 'crpro-countries-modal-visible' );
		} );

		// close a modal to a add country on ESC button
		jQuery(document).on( 'keyup', function( e ) {
			if( e.key === "Escape" ) {
				jQuery('.crpro-countries-modal-cont').removeClass( 'crpro-countries-modal-visible' );
				jQuery('.crpro-delete-modal-cont').removeClass( 'crpro-countries-modal-visible' );
			}
		} );

		// save country modal
		jQuery('.crpro-countries-modal .crpro-countries-modal-save').on( 'click', function( e ) {
			const country = jQuery(this).closest( '.crpro-countries-modal-internal' ).find( '#crpro_select_country' ).find( ':selected' );
			const delay = jQuery(this).closest( '.crpro-countries-modal-internal' ).find( '#crpro_sending_delay' );
			const delay2enbld = jQuery(this).closest( '.crpro-countries-modal-internal' ).find( '#crpro_sending_delay2_enbld' ).prop( 'checked' );
			const delay2 = jQuery(this).closest( '.crpro-countries-modal-internal' ).find( '#crpro_sending_delay2' );
			const sendAt = jQuery(this).closest( '.crpro-countries-modal-internal' ).find( '#crpro_send_at' ).find( ':selected' );
			const tdCountry = '<td data-country="' + country.val() + '">' + country.text() + '</td>';
			const tdDelay = '<td>' + delay.val() + '</td>';
			let tdDelay2 = '';
			//
			const errorMsg = jQuery(this).closest( '.crpro-countries-modal-bottombar' ).find( '.crpro-countries-modal-error' );
			// validate that delay is not missing
			if ( ! delay.val() ) {
				errorMsg.addClass( 'crpro-error-display' );
				errorMsg.text( crpro_object.sending_delay_error0 );
				return;
			}
			// validate length of delays
			if (
				delay2.val() &&
				parseInt( delay2.val(), 10 ) <= parseInt( delay.val(), 10 )
			) {
				errorMsg.addClass( 'crpro-error-display' );
				errorMsg.text( crpro_object.sending_delay_error1 );
				return;
			}
			//
			if ( delay2enbld && delay2.val() ) {
				tdDelay2 = '<td data-delay2enbld="' + delay2enbld + '">' + delay2.val() + '</td>';
			} else {
				tdDelay2 = '<td data-delay2enbld="' + delay2enbld + '">' + crpro_object.not_set + '</td>';
			}
			const tdSendAt = '<td data-sendat="' + sendAt.val() + '">' + sendAt.text() + '</td>';
			const tdButtonEdit = crpro_object.button_edit;
			const tdButtonDelete = crpro_object.button_delete;
			const tdButtons = '<td>' + tdButtonEdit + tdButtonDelete + '</td>';
			const tbody = jQuery('.crpro-countries-table').find( 'tbody' );

			// check if the country already exists in the table
			const countryAlreadyExists = tbody.find( 'tr.crpro-countries-tr' ).filter( function( index ) {
				return country.val() === jQuery('td', this).eq( 0 ).data('country');
			} );
			if( 0 === countryAlreadyExists.length ) {
				tbody.append( '<tr class="crpro-countries-tr">' + tdCountry + tdDelay + tdDelay2 + tdSendAt + tdButtons + '</tr>' );
				tbody.find( 'tr.crpro-countries-tr' ).sort( function( a, b ) {
					return jQuery('td', a).text().localeCompare( jQuery('td', b).text() );
				} ).appendTo( tbody );
			} else {
				const tdsToUpdate = countryAlreadyExists.eq( 0 ).find( 'td' );
				tdsToUpdate.eq( 1 ).text( delay.val() );
				tdsToUpdate.eq( 2 ).data( 'delay2enbld', delay2enbld );
				if ( delay2enbld && delay2.val() ) {
					tdsToUpdate.eq( 2 ).text( delay2.val() );
				} else {
					tdsToUpdate.eq( 2 ).text( crpro_object.not_set );
				}
				tdsToUpdate.eq( 3 ).data( 'sendat', sendAt.val() );
				tdsToUpdate.eq( 3 ).text( sendAt.text() );
			}

			// remove the placeholder row
			if( 0 < tbody.find( 'tr.crpro-countries-tr' ).length ) {
				tbody.find( 'tr.crpro-countries-empty' ).addClass( 'crpro-generic-hide' );
			}

			// show or hide 'Add Country' button
			if( jQuery('.crpro-countries-table tbody tr.crpro-countries-tr').length >= jQuery('.crpro-countries-modal-internal #crpro_select_country option').length ) {
				jQuery('.crpro-button-add-country').addClass( 'crpro-generic-hide' );
			} else {
				jQuery('.crpro-button-add-country').removeClass( 'crpro-generic-hide' );
			}

			crproUpdateCountriesInput();

			jQuery('.crpro-countries-modal-cont').removeClass( 'crpro-countries-modal-visible' );
		} );

		// delete country modal
		jQuery('.crpro-delete-modal .crpro-countries-modal-save').on( 'click', function( e ) {
			const countryCode = jQuery(this).data( 'country' );
			jQuery('.crpro-countries-table tbody tr.crpro-countries-tr').each( function( i ) {
				if( jQuery(this).find( 'td' ).eq( 0 ).data( 'country' ) === countryCode ) {
					jQuery(this).remove();
					return false;
				}
			} );

			crproUpdateCountriesInput();

			// display the placeholder row
			if( 0 >= jQuery('.crpro-countries-table tbody tr.crpro-countries-tr').length ) {
				jQuery('.crpro-countries-table tbody tr.crpro-countries-empty' ).removeClass( 'crpro-generic-hide' );
			}

			jQuery('.crpro-delete-modal-cont').removeClass( 'crpro-countries-modal-visible' );
		} );

		// edit country settings
		jQuery('.crpro-countries-table').on( 'click', '.crpro-countries-button-edit', function( e ) {

			// current values
			const currentTds = jQuery(this).closest( '.crpro-countries-tr' ).find( 'td' );
			const country = currentTds.eq( 0 ).data( 'country' );
			const delay = currentTds.eq( 1 ).text();
			const delay2enbld = currentTds.eq( 2 ).data( 'delay2enbld' );
			const delay2 = currentTds.eq( 2 ).text();
			const sendAt = currentTds.eq( 3 ).data( 'sendat' );

			// update values in the modal box
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-title').text( crpro_object.modal_edit );
			jQuery('.crpro-countries-modal-internal #crpro_select_country').val( country );
			jQuery('.crpro-countries-modal-internal #crpro_select_country').prop( "disabled", true );
			jQuery('.crpro-countries-modal-internal #crpro_sending_delay').val( delay );
			if ( delay2enbld ) {
				jQuery('.crpro-countries-modal-internal #crpro_sending_delay2_enbld').prop( "checked", true );
			}
			jQuery('.crpro-countries-modal-internal #crpro_sending_delay2').val( delay2 );
			jQuery('.crpro-countries-modal-internal #crpro_send_at').val( sendAt );
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-error').text( '' );
			jQuery('.crpro-countries-modal-internal .crpro-countries-modal-error').removeClass( 'crpro-error-display' );

			jQuery('.crpro-countries-modal-cont').addClass( 'crpro-countries-modal-visible' );
		} );

		// delete country settings
		jQuery('.crpro-countries-table').on( 'click', '.crpro-countries-button-delete', function( e ) {

			const country = jQuery(this).closest( '.crpro-countries-tr' ).find( 'td' ).eq( 0 );
			jQuery('.crpro-delete-modal-cont .crpro-countries-modal-title').text( country.text() );
			jQuery('.crpro-delete-modal-cont .crpro-countries-modal-save').data( 'country', country.data( 'country' ) );

			jQuery('.crpro-delete-modal-cont').addClass( 'crpro-countries-modal-visible' );
		} );

	} );
} () );
