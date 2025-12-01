/**
 * Used by import/export functions
 * @since 3.22.1
 */
const pewc_import_export = {

	to_process: [],
	
	init: function() {

		jQuery( '#pewc_import_export_aou_container .containers' ).hide();

		pewc_import_export.init_import();
		pewc_import_export.init_export();

		jQuery( '.pewc-export-aou-cancel, #pewc_import_export_aou_close' ).on( 'click', function( e ) {
			e.preventDefault();
			pewc_import_export.hide_export_settings();
		});

		// close the container
		jQuery( '#pewc_import_export_aou_cancel, #pewc_import_export_aou_close' ).on( 'click', function( e ) {
			pewc_import_export.hide_container();
		});

	},

	/**
	 * Import functions
	 */
	init_import: function() {

		// for Global Groups, these need to be hidden initially
		jQuery( '.pewc-import-export-aou-importing' ).hide();
		jQuery( '#pewc_import_export_aou_import' ).hide();

		jQuery( '.pewc-import-aou-groups' ).on( 'click', function( e ) {
			e.preventDefault();

			jQuery( '#pewc_import_groups_source' ).val( jQuery( '#pewc_import_groups_source option:first' ).val() ).trigger( 'change' ); // reset
			jQuery( '#pewc_import_export_aou_cancel' ).show();
			jQuery( '#pewc_import_export_aou_import' ).hide();
			jQuery( '#pewc_import_export_aou_export' ).hide();
			jQuery( '#pewc_import_export_aou_close' ).hide();

			pewc_import_export.show_container( 'import' );
		});

		// trigger when selecting a source
		jQuery( '#pewc_import_groups_source' ).on( 'change', function( e ) {
			jQuery( '.pewc-import-groups-sources' ).hide();
			jQuery( '#pewc_import_export_aou_import' ).hide();
			jQuery( '#pewc_import_groups_products_list' ).val( [] ).trigger( 'change' );
			jQuery( '#pewc_import_aou_groups_list').html( '' ).hide();

			if ( jQuery( this ).val() === 'product' ) {
				// product
				jQuery( '#pewc_import_groups_from_products' ).show();
			} else if ( jQuery( this ).val() === 'global' ) {
				// global
				pewc_import_export.load_groups_from_sources( [0] );
			}
		});

		// trigger when user selects a product
		jQuery( '#pewc_import_groups_products_list' ).on( 'change', function() {
			var sources = [];
			if ( jQuery( '#pewc_import_groups_products_list' ).val().length > 0 ) {
				sources = jQuery( '#pewc_import_groups_products_list' ).val();
			}
			if ( sources.length > 0 ) {
				pewc_import_export.load_groups_from_sources( sources );
			} else {
				jQuery( '#pewc_import_export_aou_import' ).hide();
				jQuery( '#pewc_import_aou_groups_list').html( '' ).hide();
			}
		});

		// trigger when user clicks the Import button
		jQuery( '#pewc_import_export_aou_import' ).on( 'click', function( e ) {
			e.preventDefault();
			pewc_import_export.import();
		});

	},

	load_groups_from_sources: function( sources ) {
		if ( sources.length < 1) {
			return;
		}
		jQuery( '#pewc_import_export_aou_container .pewc-loading' ).show();
		jQuery( '.pewc-import-global-groups .pewc-loading' ).show();

		jQuery.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'pewc_import_aou_load_groups',
				security: jQuery( '#pewc_import_export_nonce' ).val(),
				groups_to_load: sources,
			},
			success: function( response ){
				if ( response.data.length > 0 ) {
					// build the table
					var products = ( jQuery( '#pewc_import_groups_source' ).val() == 'product' || jQuery( '.wrap.pewc-import-global-groups' ).length > 0 );
					var all_groups = JSON.parse( response.data );
					var table = jQuery( '<table>' );
					var table_row = '<tr>'
						+ '<th><input type="checkbox" id="pewc_import_all_groups" /></th>'
						+ '<th>ID</th>'
						+ '<th>Group Title</th>';
					
					if ( products ) {
						table_row += '<th>Product</th>';
					}
					table_row += '</tr>';
					table.append( table_row );
					for ( var group_id in all_groups ) {
						table_row = '<tr>'
							+ '<td class="pewc-i-e-checkbox"><input type="checkbox" class="pewc-import-group-id" value="' + group_id + '"></td>'
							+ '<td>' + group_id + '</td>'
							+ '<td>' + all_groups[group_id].group_title + '</td>';
						
						if ( products ) {
							table_row += '<td>' + all_groups[group_id].product_title + '</td>';
						}
						table_row += '</tr>';
						table.append( table_row );
					}
					jQuery( '#pewc_import_aou_groups_list' ).html( table );
					jQuery( '#pewc_import_aou_groups_list' ).show();
					pewc_import_export.attach_events_to_import_checkboxes();
				} else {
					jQuery( '#pewc_import_aou_groups_list' ).html( response.message );
					jQuery( '#pewc_import_aou_groups_list' ).show();
				}
				jQuery( '#pewc_import_export_aou_container .pewc-loading' ).hide();
				jQuery( '.pewc-import-global-groups .pewc-loading' ).hide();
			},
			error: function( response ){
				alert('Error!')
				jQuery( '#pewc_import_export_aou_container .pewc-loading' ).hide();
				jQuery( '.pewc-import-global-groups .pewc-loading' ).hide();
			}
		});

	},

	attach_events_to_import_checkboxes: function() {

		// select all groups
		jQuery( '#pewc_import_all_groups' ).on( 'click', function() {
			var checked = true;
			if ( ! jQuery( this ).prop( 'checked' ) ) {
				checked = false; // uncheck
				jQuery( '#pewc_import_export_aou_import' ).hide();
			} else {
				jQuery( '#pewc_import_export_aou_import' ).show();
			}
			jQuery( '.pewc-import-group-id' ).each( function( index, element ) {
				jQuery( element ).prop( 'checked', checked );
			});
		});

		// individual checkboxes
		jQuery( '.pewc-import-group-id' ).each( function( index, element ) {
			jQuery( element ).on( 'click', function( e ) {
				pewc_import_export.check_import_checkboxes();
			});
		});

	},

	check_import_checkboxes: function() {
		var all_checked = true;
		var some_checked = false;
		jQuery( '.pewc-import-group-id' ).each( function( index, element ) {
			if ( ! jQuery( element ).prop( 'checked' ) ) {
				all_checked = false;
			} else {
				some_checked = true;
			}
			if ( ! all_checked && some_checked ) {
				return false; // we can stop the loop if we found both that we need?
			}	
		});
		if ( all_checked ) {
			jQuery( '#pewc_import_all_groups' ).prop( 'checked', true );
		} else {
			jQuery( '#pewc_import_all_groups' ).prop( 'checked', false );
		}
		if ( some_checked ) {
			jQuery( '#pewc_import_export_aou_import' ).show();
		} else {
			jQuery( '#pewc_import_export_aou_import').hide();
		}
	},

	import: function() {
		pewc_import_export.to_process = []; // always reset
		if ( jQuery( '.pewc-import-group-id:checked' ).length > 0 ) {
			jQuery( '.pewc-import-aou-groups-settings' ).hide();
			jQuery( '#pewc_import_aou_groups_list' ).hide();
			jQuery( '.pewc-import-export-aou-buttons' ).hide();
			jQuery( '.pewc-import-export-aou-importing' ).show();

			jQuery( '.pewc-import-group-id:checked' ).each( function( index, element ){
				pewc_import_export.to_process.push( jQuery( element ).val() );
			});

			if ( pewc_import_export.to_process.length > 0 ) {
				if ( jQuery( '#pewc_import_export_aou_container' ).length > 0 ) {
					// we're on a product page
					var product_id = jQuery( '#pewc_import_export_aou_container' ).attr( 'data-product-id' );
				} else {
					// we're on the Global Groups page
					var product_id = 0;
				}
				jQuery( '#pewc_import_export_aou_container .pewc-loading' ).show();

				jQuery.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'pewc_import_export_aou_process',
						security: jQuery( '#pewc_import_export_nonce' ).val(),
						groups_to_process: pewc_import_export.to_process,
						destination_parent: [ product_id ]
					},
					success: function( response ){
						//alert('Success!');
						// maybe redirect?
						if ( product_id > 0 ) {
							var edit_product_url = jQuery( '#pewc_import_export_aou_container' ).attr( 'data-pewc-admin-url' );
							window.location.href = edit_product_url + product_id;
						} else {
							var global_groups_url = jQuery( '.wrap.pewc-import-global-groups' ).attr( 'data-pewc-global-groups-url' );
							window.location.href = global_groups_url;
						}
					},
					error: function( response ){
						alert( 'Error! ' + response.status + ' ' + response.statusText );
						jQuery( '.pewc-import-aou-groups-settings' ).show();
						jQuery( '#pewc_import_aou_groups_list' ).show();
						jQuery( '.pewc-import-export-aou-buttons' ).show();
						jQuery( '#pewc_import_export_aou_import' ).show();
						jQuery( '#pewc_import_export_aou_cancel' ).show();
						jQuery( '#pewc_import_export_aou_close' ).hide();
						jQuery( '.pewc-import-export-aou-importing' ).hide();
						jQuery( '#pewc_import_export_aou_container .pewc-loading' ).hide();
					}
				});
			}
		}
	},

	/**
	 * Export functions
	 */
	init_export: function() {
		// hide settings initially
		pewc_import_export.hide_export_settings();
		// hide these in Global Groups
		jQuery( '#pewc_import_export_aou_export' ).hide();
		jQuery( '.pewc-import-export-aou-exporting' ).hide();
		jQuery( '.pewc-import-export-aou-view-products' ).hide();

		jQuery( '.pewc-export-aou-groups' ).on( 'click', function( e ) {
			e.preventDefault();
			pewc_import_export.show_export_settings();
		});

		jQuery( '.pewc-import-export-aou-group-checkbox' ).on( 'click', function( e ) {
			e.stopPropagation(); // don't open the group container when selecting groups to export
		});

		jQuery( '.pewc-export-aou-selected-group' ).on( 'click', function( e ) {
			e.preventDefault();
			// check if there are checked groups
			pewc_import_export.to_process = []; // always reset
			jQuery( '.pewc-import-export-aou-group-checkbox' ).each( function( index, element ) {
				if ( jQuery( element ).prop( 'checked' ) ) {
					pewc_import_export.to_process.push( jQuery( element ).val() );
				}
			});

			if ( pewc_import_export.to_process.length < 1 ) {
				alert( 'Please select at least one group');
				return;
			}
			jQuery( '#pewc_export_groups_list' ).html( pewc_import_export.to_process.join( ', ' ) );
			jQuery( '#pewc_export_groups_destination' ).val( jQuery( '#pewc_export_groups_destination option:first' ).val() ).trigger( 'change' ); // reset
			jQuery( '#pewc_import_export_aou_cancel' ).show();
			jQuery( '#pewc_import_export_aou_import' ).hide();
			jQuery( '#pewc_import_export_aou_export' ).hide();
			jQuery( '#pewc_import_export_aou_close' ).hide();

			pewc_import_export.show_container( 'export' );
		});

		// trigger when selecting a destination
		jQuery( '#pewc_export_groups_destination' ).on( 'change', function( e ) {
			jQuery( '.pewc-export-groups-destinations' ).hide();
			jQuery( '#pewc_import_export_aou_export' ).hide();
			jQuery( '#pewc_export_groups_products_list' ).val( [] ).trigger( 'change' );

			if ( jQuery( this ).val() === 'product' ) {
				// product
				jQuery( '#pewc_export_groups_to_products' ).show();
			} else if ( jQuery( this ).val() === 'global' ) {
				// global
				jQuery( '#pewc_import_export_aou_export' ).show();
			}
		});

		// trigger when user selects a product
		jQuery( '#pewc_export_groups_products_list' ).on( 'change', function() {
			if ( jQuery( '#pewc_export_groups_products_list' ).val().length > 0 ) {
				jQuery( '#pewc_import_export_aou_export' ).show();
			} else {
				jQuery( '#pewc_import_export_aou_export' ).hide();
			}
			pewc_import_export.update_view_products_list();
		});

		// trigger when clicking the Export button
		jQuery( '#pewc_import_export_aou_export' ).on( 'click', function( e ) {
			jQuery( '.pewc-export-aou-groups-settings' ).hide();
			jQuery( '.pewc-import-export-aou-buttons' ).hide();
			jQuery( '.pewc-import-export-aou-exporting' ).show();
			pewc_import_export.export();
		});

		// select all groups
		jQuery( '#pewc_select_all_group_for_export' ).on( 'click', function() {
			var checked = true;
			if ( ! jQuery( this ).prop( 'checked' ) ) {
				checked = false; // uncheck
			}
			jQuery( '.pewc-import-export-aou-group-checkbox' ).each( function( index, element ) {
				jQuery( element ).prop( 'checked', checked );
			});
		});

		// individual checkboxes
		jQuery( '.pewc-import-export-aou-group-checkbox' ).each( function( index, element ) {
			jQuery( element ).on( 'click', function( e ) {
				if ( pewc_import_export.all_checkboxes_checked( 'pewc-import-export-aou-group-checkbox' ) ) {
					jQuery( '#pewc_select_all_group_for_export' ).prop( 'checked', true );
				} else {
					jQuery( '#pewc_select_all_group_for_export' ).prop( 'checked', false );
				}
			});
		});
	},

	all_checkboxes_checked: function( checkbox_class ) {
		var all_checked = true;
		jQuery( '.' + checkbox_class ).each( function( index, element ) {
			if ( ! jQuery( element ).prop( 'checked' ) ) {
				all_checked = false;
				return false; // stop the loop
			}
		});
		return all_checked;
	},

	show_export_settings: function() {
		jQuery( '.pewc-import-aou-groups' ).hide();
		jQuery( '.pewc-export-aou-groups' ).hide();
		jQuery( '.add_new_group' ).hide();
		jQuery( '.pewc-group-settings' ).hide();

		jQuery( '.pewc-export-aou-selected-group' ).show();
		jQuery( '.pewc-export-aou-cancel' ).show();
		jQuery( '.pewc-import-export-aou-group-checkbox' ).show();
		jQuery( '.pewc-import-export-aou-select-all' ).show();
	},

	hide_export_settings: function() {
		jQuery( '.pewc-import-aou-groups' ).show();
		if ( jQuery( '.pewc-import-export-aou-group-checkbox' ).length > 0 ) {
			jQuery( '.pewc-export-aou-groups' ).show();
		} else {
			jQuery( '.pewc-export-aou-groups' ).hide();
		}
		jQuery( '.add_new_group' ).show();
		jQuery( '.pewc-group-settings' ).show();

		jQuery( '.pewc-export-aou-selected-group' ).hide();
		jQuery( '.pewc-export-aou-cancel' ).hide();
		jQuery( '.pewc-import-export-aou-group-checkbox' ).hide();
		jQuery( '.pewc-import-export-aou-select-all' ).hide();
	},

	update_view_products_list: function() {
		var products_list = '';
		if ( jQuery( '.wrap.pewc-export-global-groups' ).length > 0 ) {
			// Global Groups
			var edit_product_url = jQuery( '.wrap.pewc-export-global-groups' ).attr( 'data-pewc-edit-product-url' );
		} else {
			var edit_product_url = jQuery( '#pewc_import_export_aou_container' ).attr( 'data-pewc-admin-url' );
		}
		jQuery( '#pewc_export_groups_products_list option' ).each( function( index, element ) {
			// #pewc_export_groups_products_list option returns even products that have already been unselected, so we double check
			if ( jQuery.inArray( jQuery( element ).val(), jQuery( '#pewc_export_groups_products_list' ).val() ) > -1 ) {
				if ( products_list != '' ) {
					products_list += ', ';
				}
				products_list += '<a href="' + edit_product_url + jQuery( element ).val() + '" target="_blank">' + jQuery( element ).text() + '</a>';
			}
		});
		jQuery( '.pewc-import-export-aou-view-products .product-list' ).html( products_list );
	},

	export: function() {
		var is_global_groups = false;
		if ( jQuery( '.wrap.pewc-export-global-groups' ).length > 0 && jQuery( '.wrap.pewc-export-global-groups' ).attr( 'data-pewc-global-groups-export' ) != '[]' ) {
			// reset
			pewc_import_export.to_process = [];
			// Global Groups
			var global_groups = JSON.parse( jQuery( '.wrap.pewc-export-global-groups' ).attr( 'data-pewc-global-groups-export' ) );
			for ( var i in global_groups ) {
				if ( jQuery.inArray( global_groups[i], pewc_import_export.to_process ) < 0 ) {
					pewc_import_export.to_process.push( global_groups[i] );
				}
			}
			is_global_groups = true;
		}
		if ( pewc_import_export.to_process.length > 0 && ( jQuery( '#pewc_export_groups_destination' ).val() != '' || is_global_groups ) ) {
			jQuery( '.pewc-export-aou-groups-settings' ).hide();
			jQuery( '.pewc-import-export-aou-buttons' ).hide();
			jQuery( '.pewc-import-export-aou-exporting' ).show();

			if ( is_global_groups ) {
				var destination = 'product';
			} else {
				var destination = jQuery( '#pewc_export_groups_destination' ).val();
			}
			var parent_ids = [0];

			if ( destination === 'product' && jQuery( '#pewc_export_groups_products_list' ).val().length > 0 ) {
				var parent_ids = jQuery( '#pewc_export_groups_products_list' ).val();
			}
			jQuery( '#pewc_import_export_aou_container .pewc-loading' ).show();

			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'pewc_import_export_aou_process',
					security: jQuery( '#pewc_import_export_nonce' ).val(),
					groups_to_process: pewc_import_export.to_process,
					destination_parent: parent_ids
				},
				success: function( response ){
					//alert('Success!');
					jQuery( '.pewc-import-export-aou-buttons' ).show();
					jQuery( '#pewc_import_export_aou_export' ).hide();
					jQuery( '#pewc_import_export_aou_cancel' ).hide();
					jQuery( '#pewc_import_export_aou_close' ).show();
					jQuery( '.pewc-import-export-aou-exporting' ).hide();
					if ( destination === 'product' ) {
						jQuery( '.pewc-import-export-aou-view-products' ).show();
					} else {
						jQuery( '.pewc-import-export-aou-view-global' ).show();
					}
					jQuery( '#pewc_import_export_aou_container .pewc-loading' ).hide();
				},
				error: function( response ){
					alert( 'Error! ' + response.status + ' ' + response.statusText );
					jQuery( '.pewc-export-aou-groups-settings' ).show();
					jQuery( '.pewc-import-export-aou-buttons' ).show();
					jQuery( '.pewc-import-export-aou-exporting' ).hide();
					jQuery( '#pewc_import_export_aou_container .pewc-loading' ).hide();
				}
			});
		}
	},

	/**
	 * General functions
	 */
	show_container: function( type ) {
		jQuery( '#pewc_import_export_aou_container' ).fadeIn();
		jQuery( '#pewc_import_export_aou_container' ).attr( 'data-pewc-container-type', type );
		jQuery( 'body' ).css( 'overflow-y', 'hidden' );
		jQuery( '.pewc-' + type + '-aou-groups-settings' ).show();
		jQuery( '#pewc_' + type + '_aou_groups_container' ).show();
		//jQuery( '#pewc_import_export_aou_' + type ).show();
		jQuery( '#pewc_import_export_aou_cancel' ).show();
		jQuery( '#pewc_import_export_aou_close' ).hide();
		jQuery( '.pewc-import-export-aou-exporting' ).hide();
		jQuery( '.pewc-import-export-aou-importing' ).hide();
		jQuery( '.pewc-import-export-aou-view-global' ).hide();
		jQuery( '.pewc-import-export-aou-view-products' ).hide();
	},

	hide_container: function() {
		var type = jQuery( '#pewc_import_export_aou_container' ).attr( 'data-pewc-container-type' );
		jQuery( '#pewc_import_export_aou_container' ).fadeOut();
		jQuery( 'body' ).css( 'overflow-y', 'auto' );
		jQuery( '#pewc_import_export_aou_close' ).hide();
		jQuery( '.pewc-' + type + '-aou-groups-settings' ).hide();
		jQuery( '#pewc_' + type + '_aou_groups_container' ).hide();
	},

}

pewc_import_export.init();