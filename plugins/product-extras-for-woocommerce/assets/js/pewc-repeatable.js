// since 3.22.0
const pewc_repeatable = {

	init: function() {

		// if the page has been submitted, let's update the group container IDs
		if ( jQuery( '.pewc-cloned-group' ).length > 0 ) {
			var prev_group = 0, curr_count = 2;
			jQuery( '.pewc-cloned-group' ).each( function( index, element ){
				if ( jQuery( this ).attr( 'data-group-id' ) != prev_group ) {
					// new group, reset clone_count
					curr_count = 2;
					prev_group = jQuery( this ).attr( 'data-group-id' );
				} else {
					curr_count++;
				}
				var curr_id = jQuery( this ).attr( 'id' );
				jQuery( this ).attr( 'id', curr_id + '-cloned-' + curr_count );
			});
		}

		if ( jQuery( '.pewc-repeat-by-quantity' ).length > 0 ) {
			// get the max repeat limit and apply it to the product quantity
			var max_repeat_limit = 0;
			jQuery( '.pewc-repeat-by-quantity' ).each( function( index, element ){
				var repeat_limit = parseInt( jQuery( this ).find( '.pewc-repeat-group-button' ).attr( 'data-repeat-limit' ) );
				if ( repeat_limit > max_repeat_limit ) {
					max_repeat_limit = repeat_limit;
				}
			});
			if ( max_repeat_limit > 0 ) {
				jQuery( 'form.cart .qty' ).attr( 'max', max_repeat_limit+1 );
			}

			// attach an on change event to the quantity
			// 3.26.6, changed 'change' to 'keyup input change paste'
			jQuery( 'form.cart .qty' ).on( 'keyup input change paste', function(){
				// get the current quantity
				var curr_quantity = parseInt( jQuery( 'form.cart .qty' ).val() );

				if ( isNaN( curr_quantity ) ) {
					// 3.26.6
					return;
				}

				// repeat all groups that are dependent on quantity
				jQuery( '.pewc-repeat-by-quantity' ).each( function( index, element ){
					var repeat_button = jQuery( this ).find( '.pewc-repeat-group-button' );
					var prev_quantity = parseInt( repeat_button.attr( 'data-prev-quantity' ) );
					var group_id = repeat_button.attr( 'id' ).replace( 'pewc-repeat-group-', '' );
					var loop_counter = 1;

					if ( isNaN( prev_quantity ) || curr_quantity > prev_quantity ) {
						// repeat the group
						// 3.26.6, we now use looping in case a customer types a quantity that is more than 1 from the previous quantity e.g. from 1 to 4
						if ( ! isNaN( prev_quantity ) ) {
							loop_counter = prev_quantity;
						}
						for ( var i = loop_counter; i < curr_quantity; i++ ) {
							pewc_repeatable.repeat_group( group_id, i+1 );
						}
						//pewc_repeatable.repeat_group( group_id, curr_quantity );
					} else {
						// hide a repeated group
						// 3.26.6, we now use looping
						for ( var i = prev_quantity; i > curr_quantity; i-- ) {
							pewc_repeatable.hide_repeated_group( group_id, i-1 );
						}
						//pewc_repeatable.hide_repeated_group( group_id, curr_quantity );
					}

					// keep track of the quantity
					repeat_button.attr( 'data-prev-quantity', curr_quantity );
				});
			});

			// 3.26.15, if default quantity is > 1, trigger quantity field so that groups are auto-cloned on load. Only do this if the page hasn't been submitted already?
			if ( jQuery( 'form.cart .qty' ).val() && jQuery( 'form.cart .qty' ).val() > 1 && jQuery( '.pewc-cloned-group' ).length < 1 ) {
				jQuery( 'form.cart .qty' ).trigger( 'change' );
			}
		}

		if ( jQuery( '.pewc-repeat-group-button' ).length > 0 ) {
			// attach the click event to the Add More button for groups that are not repeatable by quantity
			jQuery( '.pewc-repeat-group-button' ).on( 'click', function( e ){
				e.preventDefault();
				var group_id = jQuery( this ).attr( 'id' ).replace( 'pewc-repeat-group-', '' );
				return pewc_repeatable.repeat_group( group_id );
			});
		}

	},

	is_repeatable_field( field_type ) {

		if ( pewc_vars.repeatable_fields && pewc_vars.repeatable_fields.length > 0 ) {
			return pewc_vars.repeatable_fields.includes( field_type );
		}

		return false;

	},

	repeat_group: function( group_id, curr_quantity ) {

		var repeat_button = jQuery( '#pewc-repeat-group-' + group_id );
		if ( repeat_button.length < 1 ) {
			return; // button not found
		}
		var clone_count = parseInt( repeat_button.attr( 'data-clone-counter' ) );
		var repeat_limit = parseInt( repeat_button.attr( 'data-repeat-limit' ) );
		var repeat_labeling = repeat_button.attr( 'data-repeat-labeling' );
		var repeat_label_format = repeat_button.attr( 'data-repeat-label-format' );

		if ( clone_count >= curr_quantity ) {
			// we may have some hidden cloned groups, show them instead
			var counter = 2;
			jQuery( '.pewc-cloned-group-' + group_id ).each( function( index, element ){
				if ( counter <= curr_quantity ) {
					jQuery( this ).removeClass( 'pewc-group-hidden' );
				}
				counter++;
			});
			return;
		}
		if ( clone_count >= repeat_limit+1 ) {
			return; // we've reached the limit, don't do anything
		}

		// clone the group and add it above the button
		var orig_title = repeat_button.attr( 'data-group-title' );
		var parent_group = jQuery( '#pewc-group-' + group_id );
		var cloned_group = parent_group.clone();//.insertBefore( '.pewc-repeat-group-' + group_id ); // 3.26.5, commented out because of issues in radio buttons

		// change IDs and add some classes, and remove fields that are not allowed to be cloned
		clone_count++;
		cloned_group.attr( 'id', 'pewc-group-' + group_id + '-cloned-' + clone_count );
		cloned_group.addClass( 'pewc-cloned-group' );
		cloned_group.addClass( 'pewc-cloned-group-' + group_id );

		if ( 'group' === repeat_labeling && orig_title != '' ) {
			// change the group title
			//var new_title = orig_title + ' ' + clone_count;
			var new_title = repeat_label_format.replace( '[group_title]', orig_title ).replace( '[clone_count]', clone_count );
			var orig_html = cloned_group.find( '.pewc-group-heading-wrapper' ).html();
			cloned_group.find( '.pewc-group-heading-wrapper' ).html( orig_html.replace( orig_title, new_title ) );
		}

		cloned_group.find( '.pewc-item' ).each(function( index, element ){

			if ( ! pewc_repeatable.is_repeatable_field( jQuery( this ).attr( 'data-field-type' ) ) ) {
				jQuery( this ).remove();
				return; // 3.26.5, no need to proceed
			}
			if ( 'field' === repeat_labeling ) {
				// 3.26.18, moved to new function
				pewc_repeatable.update_field_label( jQuery( this ), repeat_label_format, clone_count );
			}

			// 3.26.6, add unique classes?
			if ( ! jQuery( this ).hasClass( 'pewc-cloned-field-' + clone_count ) ) {
				jQuery( this ).addClass( 'pewc-cloned-field-' + clone_count );
			}

			// reset value
			jQuery( this ).find( 'input.pewc-form-field' ).val( '' );
			//jQuery( this ).find( 'input.pewc-form-field' ).attr( 'id', jQuery( this ).attr( 'data-id' ) + '_' + clone_count ); // 3.26.6, text fields? not sure if needed
			jQuery( this ).attr( 'data-field-value', jQuery( this ).attr( 'data-default-value' ) );
			if ( /*jQuery( this ).attr( 'data-field-value' ) == '' &&*/ jQuery( this ).attr( 'data-field-type' ) == 'select' ) {
				// for select fields, default field value is the first item
				jQuery( this ).find( '.pewc-form-field' ).attr( 'id', jQuery( this ).attr( 'data-id' ) + '_' + clone_count ).trigger( 'change' ); // 3.26.6, select fields also wants unique IDs
				jQuery( this ).attr( 'data-field-value', jQuery( this ).find( '.pewc-form-field' ).val() );
			} else if ( /*jQuery( this ).attr( 'data-field-value' ) == '' && */jQuery( this ).attr( 'data-field-type' ) == 'radio' ) {
				// For radio fields, ensure we have unique IDs and names
				id = jQuery( this ).attr( 'data-id' );
				var radio_index = clone_count-1; // 3.26.5, index causes duplicate radio buttons, use clone_count instead
				jQuery( this ).find( 'li' ).each(function( count, el ){
					label = jQuery( this ).find( '.pewc-radio-form-label' ).attr( 'for' );
					jQuery( this ).find( '.pewc-radio-form-label' )
						.attr( 'for', label + '_' + radio_index )
						.removeClass( 'active-swatch' );
					jQuery( this ).find( '.pewc-radio-form-field' )
						.attr( 'id', label + '_' + radio_index )
						.attr( 'name', id + '[' + radio_index + ']' )
						.attr( 'data-orig-label', label ) // 3.26.18, used to update radio button IDs when removing clones
						.prop( 'checked', false ); // uncheck duplicated radio button
				});
			}

			// remove validations
			jQuery( this ).removeClass( 'pewc-passed-validation' );
			jQuery( this ).removeClass( 'pewc-failed-validation' );
			jQuery( this ).find( '.pewc-js-validation-notice' ).html( '' );

		});

		// 3.26.5, moved here. We update IDs first before inserting it to the page because radio button values get reset if it detects duplicate IDs
		cloned_group.insertBefore( '.pewc-repeat-group-' + group_id );

		// update clone counter
		repeat_button.attr( 'data-clone-counter', clone_count );
		jQuery( '#pewc-repeat-group-count-' + group_id ).val( clone_count );

		if ( clone_count >= repeat_limit+1 ) {
			// hide button
			repeat_button.hide();
		}

		// 3.26.18, initialize Remove button
		cloned_group.find( '.pewc-remove-clone' ).on( 'click', function( e ){

			e.preventDefault();
			if ( confirm( pewc_vars.repeatable_confirm_remove ) ) {
				var cloned_group = jQuery( this ).closest( '.pewc-group-wrap' );
				pewc_repeatable.remove_repeated_group( cloned_group );
			}
			return false;

		});

		// 3.26.5, attach condition events, then trigger pewc_trigger_initial_check
		jQuery( document ).trigger( 'pewc_attach_condition_events' );
		jQuery( document ).trigger( 'pewc_trigger_initial_check' );

		return;

	},

	hide_repeated_group: function( group_id, curr_quantity ) {

		var repeat_button = jQuery( '#pewc-repeat-group-' + group_id );
		if ( repeat_button.length < 1 ) {
			return; // button not found
		}
		var clone_count = parseInt( repeat_button.attr( 'data-clone-counter' ) );
		var repeat_limit = parseInt( repeat_button.attr( 'data-repeat-limit' ) );

		if ( clone_count > curr_quantity ) {
			// we have an excess of repeated groups that we need to hide
			var counter = 2; // we always start with 2
			jQuery( '.pewc-cloned-group-' + group_id ).each( function( index, element ) {
				if ( counter > curr_quantity ) {
					jQuery( this ).addClass( 'pewc-group-hidden' );
				}
				counter++;
			});
		}
	},

	// 3.26.18
	remove_repeated_group: function( cloned_group ) {

		if ( cloned_group.hasClass( 'pewc-cloned-group' ) ) {
			var group_id = jQuery( cloned_group ).attr( 'data-group-id' );
			var repeat_button = jQuery( '#pewc-repeat-group-' + group_id );
			var repeat_labeling = repeat_button.attr( 'data-repeat-labeling' );
			var repeat_label_format = repeat_button.attr( 'data-repeat-label-format' );
			var orig_title = repeat_button.attr( 'data-group-title' );
			var clone_counter = 1;

			//if ( repeat_button.length > 0 ) {
				clone_counter = parseInt( repeat_button.attr( 'data-clone-counter' ) ); 
				// decrease clone_count
				clone_counter -= 1;
				// update it
				repeat_button.attr( 'data-clone-counter', clone_counter );
			//}
			cloned_group.remove();

			// update group or field labels
			if ( clone_counter > 1 ) {
				var i = 2;
				jQuery( '.pewc-cloned-group-' + group_id ).each( function(){
					// update ID
					jQuery( this ).attr( 'id', 'pewc-group-' + group_id + '-cloned-' + i );

					// update group title
					if ( 'group' === repeat_labeling && orig_title != '' ) {
						// change the group title
						var new_title = repeat_label_format.replace( '[group_title]', orig_title ).replace( '[clone_count]', i );
						var orig_html = jQuery( this ).find( '.pewc-group-heading-wrapper' ).html();
						var old_title = jQuery( orig_html ).text();
						jQuery( this ).find( '.pewc-group-heading-wrapper' ).html( orig_html.replace( old_title, new_title ) );
					}

					// loop through the fields
					jQuery( this ).find( '.pewc-item' ).each( function(){
						if ( 'field' === repeat_labeling ) {
							pewc_repeatable.update_field_label( jQuery( this ), repeat_label_format, i );
						}
						// update radio IDs so that there are no duplicates and they are selectable
						if ( jQuery( this ).attr( 'data-field-type' ) == 'radio' ) {
							// For radio fields, ensure we have unique IDs and names
							id = jQuery( this ).attr( 'data-id' );
							var radio_index = i-1;
							jQuery( this ).find( 'li' ).each(function( count, el ){
								//label = jQuery( this ).find( '.pewc-radio-form-label' ).attr( 'for' );
								label = jQuery( this ).find( '.pewc-radio-form-field' ).attr( 'data-orig-label' );
								jQuery( this ).find( '.pewc-radio-form-label' ).attr( 'for', label + '_' + radio_index );
								jQuery( this ).find( '.pewc-radio-form-field' )
									.attr( 'id', label + '_' + radio_index )
									.attr( 'name', id + '[' + radio_index + ']' );
							});
						}
					});
					
					i++;
				});
			}
		}

	},

	// 3.26.18, created a separate function
	update_field_label: function( pewc_item, repeat_label_format, clone_count ) {

		var label = pewc_item.find( '.pewc-field-label-text' );
		var orig_label = pewc_item.attr( 'data-field-label' ); //label.text();
		var new_label_text = '';
		if ( label && orig_label != '' ) {
			//new_label_text = orig_label + ' ' + clone_count;
			new_label_text = repeat_label_format.replace( '[field_label]', orig_label ).replace( '[clone_count]', clone_count );
			label.text( new_label_text );

			// update labels in optimised validation messages
			var old_message = '';
			if ( pewc_item.attr( 'data-validation-notice' ) != undefined ) {
				old_message = pewc_item.attr( 'data-validation-notice' );
				pewc_item.attr( 'data-validation-notice', old_message.replace( orig_label, new_label_text ) );
			}
			if ( pewc_item.attr( 'data-field-minchars-error' ) != undefined ) {
				old_message = pewc_item.attr( 'data-field-minchars-error' );
				pewc_item.attr( 'data-field-minchars-error', old_message.replace( orig_label, new_label_text ) );
			}
			if ( pewc_item.attr( 'data-field-maxchars-error' ) != undefined ) {
				old_message = pewc_item.attr( 'data-field-maxchars-error' );
				pewc_item.attr( 'data-field-maxchars-error', old_message.replace( orig_label, new_label_text ) );
			}
			if ( pewc_item.attr( 'data-field-minval-error' ) != undefined ) {
				old_message = pewc_item.attr( 'data-field-minval-error' );
				pewc_item.attr( 'data-field-minval-error', old_message.replace( orig_label, new_label_text ) );
			}
			if ( pewc_item.attr( 'data-field-maxval-error' ) != undefined ) {
				old_message = pewc_item.attr( 'data-field-maxval-error' );
				pewc_item.attr( 'data-field-maxval-error', old_message.replace( orig_label, new_label_text ) );
			}
		}

	}

};

jQuery( document ).ready( function(){
	pewc_repeatable.init();
});
