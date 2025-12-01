/**
 * Admin script - only used after migration to custom post types
 * @since 3.0.0
 */
jQuery( function( $ ) {

  'use strict';

  $( document ).ready( function() {
    $( 'body' ).find( '.pewc-date-field' ).datepicker();
    $('.field-item:not(.new-field-item)').find('.pewc-field-color').wpColorPicker();
    $( '.pewc-variation-field, .pewc-multiselect' ).select2();
    $('.pewc-global-set-wrap .pewc-rule-select, .post-type-pewc_group .pewc-rule-select').select2();
    $( '#pewc_group_wrapper' ).sortable({
      stop: function( e, ui ) {
        $( 'body' ).trigger( 'refresh_group_order' );
      }
    });

	// 3.22.1, updated to allow dragging/moving of fields to a different group
    $( '.field-list' ).sortable({
		connectWith: '.field-list',
		receive: function( event, ui ) {
			var field = ui.item;
			var field_id = $(field).attr( 'id' );
			var tmp = field_id.split( '_' );
			var old_group_id = tmp[2];
			var field_id2 = tmp[3];
			var new_parent = $( 'li#' + field_id ).closest( 'ul.field-list.ui-sortable' );
			var new_group_id = new_parent.attr('data-pewc-field-list-group-id');
			// find this field's position in the new group by getting the ID of the previous field
			var prev_field_id = 0; // if this remains 0 at the end of the loop, field was added at the start of the group
			new_parent.find( 'li.field-item' ).each( function( index, element ){
				var curr_id = $( element ).attr( 'data-item-id' );
				if ( curr_id != field_id2 ) {
					// save this as the prev_field_id
					prev_field_id = curr_id;
				} else {
					return false;
				}
			});
			var panel = $( 'li#' + field_id ).closest( '.panel' );
			$( panel ).find( '.pewc-loading' ).show();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'pewc_move_field_to_new_group',
					security: $( '#add_new_pewc_group_nonce' ).val(),
					product_id: $( '#post_ID' ).val(),
					field_id: field_id2,
					old_group_id: old_group_id,
					new_group_id: new_group_id,
					add_after: prev_field_id
				},
				success: function( response ) {
					if ( ! response.success ) {
						alert( 'Error! ' + response.data );
					} else {
						// update IDs in the HTML
						pewc_update_duplicated_ids( $( 'li#' + field_id ), old_group_id, new_group_id );
					}
					$( panel ).find( '.pewc-loading' ).hide();
				},
				error: function( response ) {
					alert( 'Error! ' + response.status + ' ' + response.statusText );
					$( panel ).find( '.pewc-loading' ).hide();
				}
			});
		}
	});

    $( '.pewc-field-options-wrapper' ).sortable();

    $( 'body' ).trigger( 'update_field_names_object' );

  });

  // Media uploader
	var meta_image_frame;

  // All our actions
  var pewc_actions = {

    /**
		 * Initialize field and group actions
		 */
		init: function() {

      $( document.body ).on( 'click', '.add_new_group', this.add_new_group );
      $( document.body ).on( 'click', '.pewc-group-meta-actions .duplicate', this.duplicate_group );
      $( document.body ).on( 'click', '.pewc-duplicate-global', this.duplicate_group_post_type );
      $( document.body ).on( 'click', '.remove', this.remove_group );

      $( document.body ).on( 'click', '.add_new_field', this.add_new_field );
      $( document.body ).on( 'click', '.pewc-field-actions .duplicate', this.duplicate_field );
      $( document.body ).on( 'click', '.remove-field', this.remove_field );

      $( document.body ).on( 'click', '#pewc_add_global_set', this.add_global_group );
      $( document.body ).on( 'click', '#pewc_save_globals', this.save_global_groups );

      $( document.body ).on( 'change', '.pewc-field-type', this.change_field_type );

      $( document.body ).on( 'click', '.add_new_option', this.add_new_option );
      $( document.body ).on( 'click', '.remove-option', this.remove_option );

      $( document.body ).on( 'click', '.add_new_row', this.add_new_row );
      $( document.body ).on( 'click', '.remove-row', this.remove_row );

      $( document.body ).on( 'click', '.add_new_condition', this.add_new_condition );
      $( document.body ).on( 'change', '.pewc-condition-field', this.change_condition_field );
	  $( document.body ).on( 'change', '.pewc-condition-rule', this.change_condition_rule_attributes );
      $( document.body ).on( 'click', '.remove-condition', this.remove_condition );
      $( document.body ).on( 'click', '.pewc-allow-multiple', this.toggle_allow_multiple );

      $( document.body ).on( 'click', '.add_new_group_condition', this.add_new_group_condition );

      $( document.body ).on( 'change', '.pewc-field-products_layout', this.update_products_layout );
      $( document.body ).on( 'change', '.pewc-field-products_quantities', this.update_products_quantities );
	  
      $( document.body ).on( 'change', '.pewc-field-action', this.update_field_action );

      $( document.body ).on( 'click', '.pewc-field-per-character', this.toggle_per_char );
      $( document.body ).on( 'keyup input change paste', '.pewc-field-default', this.set_default_field );

      $( document.body ).on( 'update_field_names_object', this.update_field_names_object );
      $( document.body ).on( 'update_conditional_fields', this.update_conditional_fields );

      $( document.body ).on( 'focusout', '.pewc-field-option-value', this.update_field_names_object );
      $( document.body ).on( 'focusout', '.pewc-field-label', this.update_field_names_object );
    	$( document.body ).on( 'change', '.pewc-field-type', this.update_field_names_object );
    	$( document.body ).on( 'focusout', '.product-extra-option-wrapper input', this.update_field_names_object );

      $( document.body ).on( 'refresh_group_order', this.refresh_group_order );

      $( document.body ).on( 'click', '.pewc-upload-button', this.upload_media );

      $( document.body ).on( 'update change', '.pewc-field-maxdate', this.update_ymd );

      $( document.body ).trigger( 'update_field_names_object' );

		},

    /**
	 * Add new group
	 */
	add_new_group: function( e ) {
      e.preventDefault();
      var panel, security;
      if( $( '.panel' )[0] ) {
        panel = $( this ).closest( '.panel' );
      } else {
        // Global page
        panel = $( '#pewc_global_settings_form' );
      }
      var last_row = $( panel ).find( '.product-extra-group-data .group-row' ).last();
  		var count = $(last_row).attr( 'data-group-count' );
  		count = parseFloat( count ) + 1;
  		if( isNaN( count ) ) {
  			count = 0;
  		}

      $( panel ).find( '.pewc-loading' ).show();
      $.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_get_new_group_id',
  				security: $( '#add_new_pewc_group_nonce' ).val(),
          parent_id: $( '#post_ID' ).val(),
          group_order: $( '#pewc_group_order' ).val()
  			},
			success: function( response ) {
				var new_group_id = response.data.group_id;
				var group_order = response.data.group_order;
				var clone_row = $( panel ).find( '.new-group-row' ).clone().appendTo( '#pewc_group_wrapper' );
				$( clone_row ).removeClass( 'new-group-row' );
				$( clone_row ).attr( 'data-group-count', new_group_id );
				$( clone_row ).attr( 'data-group-id', new_group_id );
				$( clone_row ).attr( 'id', 'group-' + new_group_id );
				$( clone_row ).find( '.pewc-group-meta-heading .meta-item-id' ).html( "&#35;" + new_group_id );
				$( clone_row ).find( '.pewc-group-title' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][group_title]' );
				$( clone_row ).find( '.pewc-group-required' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][group_required]' );
				$( clone_row ).find( '.pewc-group-description' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][group_description]' );
				$( clone_row ).find( '.pewc-group-layout' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][group_layout]' );

				// 3.22.0
				pewc_repeatable.update_cloned_repeatable( clone_row, new_group_id );

				// Update the group order
				$( '#pewc_group_order' ).val( group_order );
				// $( 'body' ).trigger( 'refresh_group_order' );
				$( panel ).find( '.pewc-loading' ).hide();
  			}
  		});

		},

    /**
		 * Duplicate a group
		 */
		duplicate_group: function( e ) {
      e.preventDefault();
      var panel, security, group_order_field;
      if( $( '.panel' )[0] ) {
        panel = $( this ).closest( '.panel' );
        group_order_field = 'pewc_group_order';
      } else {
        // Global page
        panel = $( '#pewc_global_settings_form' );
        group_order_field = 'pewc_global_group_order';
      }
  		var clone_group = $( this ).closest( '.group-row' ).clone().appendTo( '#pewc_group_wrapper' );
  		var old_group_id = $( clone_group ).attr( 'data-group-id' );
      var group_order = $( '#' + group_order_field ).val();

      $( panel ).find( '.pewc-loading' ).show();

      $.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_duplicate_group',
  				security: $( '#add_new_pewc_group_nonce' ).val(),
				product_id: $( '#post_ID' ).val(),
				old_group_id: old_group_id,
				group_order: group_order
  			},
  			success: function( response ) {
				var new_group_id = response.data.group_id;
				var new_group_order = response.data.group_order;
				var fields = response.data.fields;
				var new_field_id;

				// Update the group order
				$( '#' + group_order_field ).val( new_group_order );

				$( clone_group ).attr( 'data-group-id', new_group_id );
				$( clone_group ).attr( 'id', 'group-' + new_group_id );

				var group_title = $( clone_group ).find( '.pewc-group-title' ).val();
				$( clone_group ).find( '.pewc-group-title' ).val( group_title + ' [' + pewc_obj.copy_label + ']');

				pewc_update_duplicated_ids( clone_group, old_group_id, new_group_id );
				$( clone_group ).find( '.pewc-group-meta-heading .meta-item-id' ).html( "&#35;" + new_group_id );

				// Conditions
				$(clone_group).find('.pewc-condition-field, .pewc-condition-rule, .pewc-condition-value').each(function(){
					if( $(this).attr('data-group-id') != undefined ) {
						var old_group_id = $(this).attr('data-group-id');
						var new_group_id = old_group_id.replace( old_group_id, new_group_id );
						$( this ).attr( 'data-group-id', new_group_id) ;
					}
				});

				// Iterate through any duplicated fields and replace IDs with new IDs
				for( var old_field_id in fields ) {
					new_field_id = fields[old_field_id];
					pewc_update_duplicated_ids( clone_group, old_field_id, new_field_id );
					var duplicate_field = $( 'body' ).find( '#pewc_group_' + new_group_id + '_' + new_field_id );
					$( duplicate_field ).find( '.pewc-field-meta-heading .meta-item-id' ).html( "&#35;" + new_field_id );
				}

				// Repopulate condition field values
				$( 'body' ).trigger( 'update_field_names_object' );

				// Set the duplicated group's condition fields to their new versions
				$( clone_group ).find( '.pewc-condition-field' ).each( function() {
					var $val = $( this ).attr( 'data-value' );
					$( this ).val( $val );
				});

				// 3.24.2, update repeatable
				pewc_repeatable.update_cloned_repeatable( clone_group, new_group_id, old_group_id );

				// 3.24.2, reinitialize and enhanced select2 (product search)
				pewc_actions.reinitialize_select2( clone_group );

    			$( 'body' ).trigger( 'refresh_group_order' );

				$( panel ).find( '.pewc-loading' ).hide();

  			}
  		});

    },

    /**
		 * Duplicate a group as post type
		 */
		duplicate_group_post_type: function( e ) {
      e.preventDefault();
      var panel, security, group_order_field;
      panel = $( '#poststuff' );

  		// var clone_group = $( this ).closest( '.group-row' ).clone().appendTo( '#pewc_group_wrapper' );
  		var old_group_id = $( '#post_ID' ).val();
      var group_order = $( '#pewc_group_order' ).val();

      $( panel ).find( '.pewc-loading' ).show();

      $.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_duplicate_group',
  				security: $( '#add_new_pewc_group_nonce' ).val(),
          product_id: 0,
          old_group_id: old_group_id,
          group_order: group_order
  			},
  			success: function( response ) {
          var new_group_id = response.data.group_id;
          var location = window.location.href;
          location = location.replace( 'post=' + old_group_id, 'post=' + new_group_id );
          window.location.replace( location );
  			}
  		});

    },

    /**
		 * Remove a group
		 */
    remove_group: function( e ) {

      e.preventDefault;

      // Avoid name conflicts with other plugins
      if( ! $( this ).hasClass( 'table-panel' ) ) {
        return;
      }
  		var r = confirm( pewc_obj.delete_group );
  		if( r == true ) {
        var panel, security;
        if( $( '.panel' )[0] ) {
          panel = $( this ).closest( '.panel' );
        } else {
          // Global page
          panel = $( '#pewc_global_settings_form' );
        }
  			var group = $( this ).closest( '.group-row' );
        var group_id = $( group ).data( 'group-id' );
        $( panel ).find( '.pewc-loading' ).show();

        $.ajax({
    			type: 'POST',
    			url: ajaxurl,
    			data: {
    				action: 'pewc_remove_group_id',
    				security: $( '#add_new_pewc_group_nonce' ).val(),
            product_id: $( '#post_ID' ).val(),
            group_id: group_id,
            group_order: $( '#pewc_group_order' ).val()
    			},
    			success: function( response ) {
            var new_group_id = response.data.group_id;
            var group_order = response.data.group_order;
            $( group ).remove();
            // Repopulate condition field values
            $( 'body' ).trigger( 'update_field_names_object' );
            $( '#pewc_global_group_order' ).val( group_order );
            $( 'body' ).trigger( 'refresh_group_order' );
            $( panel ).find( '.pewc-loading' ).hide();
          }
        });
  		}

    },

    /**
	 * Add new field
	 */
	add_new_field: function( e ) {
      e.preventDefault();

      // Panel exists on individual product pages
      var panel, security;
      if( $( '.panel' )[0] ) {
        panel = $( this ).closest( '.panel' );
      } else {
        // Global page
        panel = $( '#pewc_global_settings_form' );
      }

  		var group_id = $( this ).closest( '.group-row' ).attr( 'data-group-id' );
  		var last_item = $( '#group-' + group_id + ' ul.field-list' ).find( 'li.field-item' ).last();
  		var item_count = 0;
  		if( last_item ) {
  			item_count = $(last_item).attr( 'data-size-count' );
  			item_count = parseFloat( item_count ) + 1;
  		} else {
  			item_count = 0;
  		}
  		if( isNaN( item_count ) ) {
  			item_count = 0;
  		}

  		var clone_item = $( panel ).find( '.new-field-item' ).clone().appendTo( '#group-' + group_id + ' ul.field-list' );
      $( panel ).find( '.pewc-loading' ).show();

      $.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_get_new_field_id',
  				security: $( '#add_new_pewc_group_nonce' ).val(),
          group_id: group_id
  			},
  			success: function( response ) {
          if( response.success ) {
            var new_item_id = response.data;

            // var new_item_id = pewc_get_id_code();
        		$( clone_item ).removeClass( 'new-field-item' );
        		$( clone_item ).attr( 'id','pewc_group_' + group_id + '_' + new_item_id);

            $( clone_item ).find( '.meta-item-id' ).text( new_item_id );
        		$( clone_item ).attr( 'data-size-count', new_item_id );
        		$( clone_item ).attr( 'data-item-id', new_item_id );
        		$( clone_item )
        			.find( '.pewc-id' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[id]' )
        			.val( 'pewc_group_' + group_id + '_' + new_item_id );

        		$( clone_item )
        			.find( '.pewc-group-id' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[group_id]' )
        			.val( group_id );

        		$( clone_item )
        			.find( '.pewc-field-id' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_id]' )
        			.val( new_item_id );

        		$( clone_item )
        			.find( '.pewc-field-type' )
        			.attr( 'id', 'field_type_' + group_id + '_' + new_item_id );

			$( clone_item )
			  .find( '.pewc-field-visibility' )
			  .attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_visibility]' )
			  .attr( 'id', 'field_visibility_' + group_id + '_' + new_item_id );
			$( clone_item )
              .find( '.pewc-field-price-visibility' )
              .attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[price_visibility]' )
              .attr( 'id', 'price_visibility_' + group_id + '_' + new_item_id );
            $( clone_item )
              .find( '.pewc-option-price-visibility' )
              .attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[option_price_visibility]' )
              .attr( 'id', 'option_price_visibility_' + group_id + '_' + new_item_id );

        		$( clone_item )
        			.find( '.pewc-option-fields' )
        			.attr( 'id', 'pewc_option_' + group_id + '_' + new_item_id );

        		$( clone_item )
        			.find( '.pewc-upload-button' )
        			.attr( 'data-item-id', new_item_id );

        		$( clone_item )
        			.find( '.pewc-image-attachment-id' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_image]' );

        		$( clone_item )
        			.find( '.pewc-field-image' )
        			.addClass( 'pewc-field-image-' + new_item_id );

        		$( clone_item )
        			.addClass( 'field-type-checkbox' );

            $( clone_item )
              .find( '.pewc-calculation-field' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[formula]' );

            $( clone_item )
              .find( '.pewc-field-round' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[formula_round]' );

            $( clone_item )
              .find( '.pewc-decimal-places' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[decimal_places]' );

            $( clone_item )
              .find( '.pewc-field-action' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[formula_action]' );

            $( clone_item )
              .find( '.pewc-multiple-uploads' )
        			.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[multiple_uploads]' );

            $( clone_item )
              	.find( '.pewc-field-multiply' )
        		.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[multiply]' );

            $( clone_item )
              .	find( '.pewc-field-max-files' )
        		.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[max_files]' );

            $( clone_item )
				.find ( '.pewc-field-color' )
					.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_color]' );

		  	$( clone_item )
			  	.find ( '.pewc-field-palettes' )
			  	.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_palettes]' );

		  	$( clone_item )
			  	.find ( '.pewc-field-width' )
			  	.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_width]' );

		  	$( clone_item )
			  	.find ( '.pewc-field-show' )
			  	.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_show]' );

			$( clone_item )
				.find ( '.pewc-products-field-id' )
				.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[products_field_id]' );

			$( clone_item )
				.find ( '.pewc-child-qty-product-id' )
				.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[child_qty_product_id]' );

			$( clone_item )
				.find ( '.pewc-reverse-formula' )
				.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[reverse_formula_field]' );
		
			$( clone_item )
				.find ( '.pewc-reverse-input' )
				.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[reverse_input_field]' );

			$( clone_item )
				.find ( '.pewc-quantity-override' )
				.attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[quantity_override]' );						  

            $( clone_item )
				.find( '.pewc-field-color' )
					.wpColorPicker();

            // Set default field to checkbox
            $( clone_item ).find( '.pewc-field-type' ).val( 'checkbox' );

            // Update role based price fields
            $( clone_item ).find( '.pewc-field-role-price-new' ).each( function() {
              var role = $( this).attr( 'data-role' );
              $( this ).attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[field_price_' + role + ']' ).removeClass( 'pewc-field-role-price-new' );
            });

            var fields_object = { 'pewc-number-columns': 'number_columns', 'pewc-min-child-products': 'min_products', 'pewc-max-child-products' : 'max_products', 'pewc-default-quantity' : 'default_quantity', 'pewc-force-quantity' : 'force_quantity', 'pewc-allow-multiple-components' : 'allow_multiple_components', 'pewc-hide-labels': 'hide_labels', 'pewc-allow-multiple': 'allow_multiple', 'pewc-field-per-character': 'per_character', 'pewc-field-show-char-counter': 'show_char_counter', 'pewc-field-alphanumeric-charge': 'field_alphanumeric_charge', 'pewc-first-field-empty': 'first_field_empty', 'pewc-field-default-hidden': 'field_default_hidden', 'pewc-replace-main-image': 'replace_main_image', 'pewc-layered-images': 'layered_images', 'pewc-field-field-user-field-id' : 'field_user_field_id', 'pewc-parent-swatch-id' : 'parent_swatch_id' };
            for( var k in fields_object ) {
              $( clone_item )
          			.find( '.' + k )
					.attr( 'name' , '_product_extra_groups_' + group_id + '_' + new_item_id + '[' + fields_object[k] + ']' );
            }

			var replace_fields = [ 'child_products', 'child_categories', 'products_layout', 'products_quantities', 'select_placeholder', 'allow_none', 'min_date_today' ];
			$(replace_fields).each( function( i, v ) {
				var new_name = '_product_extra_groups_' + group_id + '_' + new_item_id + '[' + v + ']';
				// 3.24.2, this needs to be an array so that the selections get saved
				if ( v === 'child_products' || v === 'child_categories' ) {
					new_name += '[]';
				}
				$( clone_item ).find( '.pewc-field-' + v).attr( 'name', new_name );
			});

			var fields = [ 'label', 'type', 'price', 'required', 'per_unit', 'flatrate', 'display_as_swatch', 'enable_range_slider', 'percentage', 'description', 'minchars', 'maxchars', 'minchecks', 'maxchecks', 'swatchwidth', 'minval', 'maxval', 'step', 'freechars', 'alphanumeric', 'mindate', 'maxdate', 'maxdate_ymd', 'default' ];
			$(fields).each( function( i, v ) {
				$( clone_item ).find( '.pewc-field-' + v).not( '.pewc-field-default-hidden' ).attr( 'name','_product_extra_groups_' + group_id + '_' + new_item_id + '[field_' + v + ']' ).attr( 'id','_product_extra_groups_' + group_id + '_' + new_item_id + '_field_' + v );
				$( clone_item ).find( '.pewc-field-' + v).closest( 'div' ).find( 'label' ).attr( 'for','_product_extra_groups_' + group_id + '_' + new_item_id + '_field_' + v );
			});

			// Check action and match names are populated
			var condition_fields = $( clone_item ).closest( '.pewc-fields-conditionals' );
			$( clone_item ).find( '.pewc-condition-action' ).attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[condition_action]' );
			$( clone_item ).find( '.pewc-condition-condition' ).attr( 'name', '_product_extra_groups_' + group_id + '_' + new_item_id + '[condition_match]' );

            // Update list of fields
            $( document.body ).trigger( 'update_field_names_object' );
            $( document.body ).trigger( 'pewc_added_new_field', [ clone_item, group_id, new_item_id ] ); // DWS

			// 3.24.2, reinitialize enhanced select2
			pewc_actions.reinitialize_select2( clone_item );

          } else {
            alert( 'Failed to add field' );
          }

          $( panel ).find( '.pewc-loading' ).hide();

  			}
  		});

		},

    /**
	 * Duplicate a single field
	 */
	duplicate_field: function( e ) {

		e.preventDefault();
		// var list = $(this).closest('.field-list');
		var field = $(this).closest( '.field-item' );
		// var row = $( this ).closest( '.field-item' );
		var old_field_id = $( field ).attr( 'data-item-id' );
		var panel = $( this ).closest( '.panel' );
		$( panel ).find( '.pewc-loading' ).show();

		$.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_duplicate_field',
  				security: $( '#add_new_pewc_group_nonce' ).val(),
				product_id: $( '#post_ID' ).val(),
				old_field_id: old_field_id,
				group_id: $( this ).closest( '.group-row' ).attr( 'data-group-id' )
  			},
  			success: function( response ) {
				var new_field_id = response.data;
				var clone_field = $( field ).clone().insertAfter( $( field ) );

				$( clone_field )
					.attr( 'data-item-id', new_field_id )
					.attr( 'data-size-count', new_field_id )
					.attr( 'id', 'group-' + new_field_id );

				var field_title = $(clone_field).find('.pewc-field-label').val();
				$( clone_field ).find( '.pewc-field-label' ).val( field_title + ' [' + pewc_obj.copy_label + ']');

				$( clone_field ).find( '.pewc-field-meta-heading .meta-item-id' ).html( "&#35;" + new_field_id );

				pewc_update_duplicated_ids( clone_field, old_field_id, new_field_id );

				$( clone_field ).find( '.pewc-field-type' ).val( $( clone_field ).find( '.pewc-field-type' ).attr( 'data-field-type' ) );

				var color_field = $( clone_field ).find ('.pewc-field-color').not( '.pewc-option-hex .pewc-field-color' );
				color_field.closest('.wp-picker-container').replaceWith(color_field);
				color_field.wpColorPicker();

				// Repopulate condition field values
				$( 'body' ).trigger( 'update_field_names_object' );
				$( document.body ).trigger( 'pewc_cloned_field', [ clone_field, new_field_id ] ); // DWS

				var action = $( field ).find( '.pewc-condition-action' ).val();
				var rule = $( field ).find( '.pewc-condition-condition' ).val();
				$( clone_field ).find( '.pewc-condition-action' ).val( action );
				$( clone_field ).find( '.pewc-condition-condtion' ).val( rule );

				// Set the duplicated group's condition fields to their new versions
				$( clone_field ).find( '.pewc-condition-field' ).each( function() {
					var $val = $( this ).attr( 'data-value' );
					$( this ).val( $val );
				});

				// 3.24.2
				pewc_actions.reinitialize_select2( clone_field );

				$( panel ).find( '.pewc-loading' ).hide();
			}
		});

    },

    /**
		 * Remove a field
		 */
    remove_field: function( e ) {

    		e.preventDefault;
    		var r = confirm( pewc_obj.delete_field );
    		if( r == true ) {
          var panel, security;
          if( $( '.panel' )[0] ) {
            panel = $( this ).closest( '.panel' );
          } else {
            // Global page
            panel = $( '#pewc_global_settings_form' );
          }
          var group = $( this ).closest( '.group-row' );
          var group_id = $( group ).data( 'group-id' );
          var row = $( this ).closest( '.field-item' );
    			var item_id = $( row ).attr( 'data-item-id' );
          $( panel ).find( '.pewc-loading' ).show();
          $.ajax({
      			type: 'POST',
      			url: ajaxurl,
      			data: {
      				action: 'pewc_remove_field_id',
      				security: $( '#add_new_pewc_group_nonce' ).val(),
              group_id: group_id,
              item_id: item_id
      			},
      			success: function( response ) {
              $( panel ).find( '.pewc-loading' ).hide();
              $( row ).remove();
    					pewc_remove_associated_conditions( group_id, item_id );
            }
          });
    		}

    },

    /**
	 * Add new group
	 */
	add_global_group: function( e ) {
      e.preventDefault();
      var panel = $( '#pewc_global_settings_form' );
  		var last_row = $('.pewc-global-set-wrap').find('.group-row').last();
  		var count = $(last_row).attr('data-group-count');
  		count = parseFloat( count ) + 1;
  		if( isNaN( count ) ) {
  			count = 0;
  		}

      $( panel ).find( '.pewc-loading' ).show();

      $.ajax({
  			type: 'POST',
  			url: ajaxurl,
  			data: {
  				action: 'pewc_get_new_global_group_id',
  				security: $( '#pewc_global_set' ).val(),
          group_order: $( '#pewc_global_group_order' ).val()
  			},
  			success: function( response ) {
				var new_group_id = response.data.group_id;
				var group_order = response.data.group_order;

				var clone_row = $('.new-group-row').clone().appendTo('#pewc_group_wrapper');

				$(clone_row).removeClass('new-group-row');
				$(clone_row).attr('data-group-count',count);
				$(clone_row).attr('data-group-id',new_group_id);
				$(clone_row).attr('id','group-' + new_group_id);
				$( clone_row ).find( '.pewc-group-meta-heading .meta-item-id' ).html( "&#35;" + new_group_id );
				$(clone_row).find('.pewc-group-title').attr('name','_product_extra_groups_'+ new_group_id +'[meta][group_title]');
				$(clone_row).find('.pewc-group-description').attr('name','_product_extra_groups_'+ new_group_id +'[meta][group_description]');
				// $(clone_row).find('.pewc-group-required').attr('name','_product_extra_groups_'+ new_group_id +'[meta][group_required]');
				$(clone_row).find('.pewc-rule-field').each(function(){
					if($(this).attr('data-name')) {
						var data_name = $(this).attr('data-name');
						data_name = data_name.replace('GROUP_KEY',new_group_id);
						$(this).attr('name',data_name);
					}
				});
				$(clone_row).find('.pewc-rule-select').select2();

				// 3.22.0
				pewc_repeatable.update_cloned_repeatable( clone_row, new_group_id );

				// Update the group order
				$( '#pewc_global_group_order' ).val( group_order );
				// $( 'body' ).trigger( 'refresh_group_order' );
				$( panel ).find( '.pewc-loading' ).hide();
  			}
  		});

    },

    /**
     * Save the global groups
     */
    save_global_groups: function( e ) {
      e.preventDefault();
      var panel = $( '#pewc_global_settings_form' );
      $( panel ).find( '.pewc-loading' ).show();
  		var button = $(this);
  		$( button ).attr('disabled','true');
  		// $(button).parent().find('.spinner').css('visibility','visible');
  		var form = $('#pewc_global_settings_form').serializeArray();
  		$.ajax({
  			type: 'POST',
  			url: ajaxurl,
        // contentType: 'application/json',
        // dataType: 'json',
  			data: {
  				action: 'pewc_save_globals',
  				form: JSON.stringify( form ),
  				security: $( '#pewc_global_set' ).val(),
          order: $( '#pewc_global_group_order' ).val()
  			},
  			success: function(response) {
          $( panel ).find( '.pewc-loading' ).hide();
  				$(button).removeAttr('disabled');
  				// $(button).parent().find('.spinner').css('visibility','hidden');
  			}
  		});
    },

    /**
	 * Add a new option
	 */
	add_new_option: function( e ) {

      e.preventDefault();
  		var group_id = $( this ).closest( '.group-row' ).attr( 'data-group-id' );
  		var item_id = $( this ).closest( '.field-item' ).attr( 'data-item-id' );
  		var option_fields = $( this ).closest( '.pewc-option-fields' );
  		var last_option = $( option_fields ).find( 'tbody .product-extra-option-wrapper' ).last();
      // Check if this will be the first option
  		var option_count = 0;
  		if( last_option ) {
  			option_count = parseFloat( $( last_option ).attr( 'data-option-count' ) );
  			option_count++;
  		}
  		if( isNaN( option_count ) ) {
  			option_count = 0;
  		}

      var table = $( this ).closest( 'table.pewc-option-fields' );
      var tbody = $( table ).find( 'tbody' );

      // var clone_option = $( '.new-option .product-extra-option-wrapper' ).clone().insertBefore( $( this ).parent() );
  		var clone_option = $( '.new-option .product-extra-option-wrapper' ).clone();

      $( tbody ).append( clone_option );

  		$( clone_option ).attr( 'data-option-count', option_count );
  		$( clone_option )
  			.find( '.pewc-field-option-value' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_options][' + option_count + '][value]' )
  			.val( '' );

		$( clone_option )
			.find( '.pewc-field-option-hex' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_options][' + option_count + '][hex]' )
  			.val( '' );

  		$( clone_option )
  			.find( '.pewc-field-option-price' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_options][' + option_count + '][price]' )
  			.val( '' );

  		$( clone_option )
  			.find( '.pewc-image-std-attachment-id' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_options][' + option_count + '][image]' );

		$( clone_option )
  			.find( '.pewc-image-alt-attachment-id' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_options][' + option_count + '][image_alt]' );

  		$( clone_option )
  			.find( '.pewc-field-image' )
  			.addClass( 'pewc-field-image-' + item_id + '_' + option_count);

  		$( clone_option )
  			.find( '.pewc-upload-option-image' )
  			.attr( 'data-item-id',item_id+'_'+option_count);

		$( clone_option )
			.find( '.pewc-field-color' )
			.wpColorPicker();

      $( clone_option ).find( '.pewc-field-option-extra' ).each( function() {
        var name = $( this ).attr( 'name' );
        name = name.replace( 'GROUP_ID', group_id );
        name = name.replace( 'ITEM_KEY', item_id );
        name = name.replace( 'OPTION_KEY', option_count );
        $( this ).attr( 'name', name );
      });

    },

    /**
	 * Remove an option
	 */
	remove_option: function( e ) {

      e.preventDefault;
  		var r = confirm( pewc_obj.delete_option );
  		if( r == true ) {
  			var field_item = $(this).closest('.field-item');
  			$(this).closest('.product-extra-option-wrapper').fadeOut(
  				150,
  				function(){
  					$(this).remove();
  					// Remove this option from any conditions
  					set_options_data( field_item );
  				}
  			);
  		}

    },

    /**
	 * Add a new information row
	 */
	add_new_row: function( e ) {

      e.preventDefault();
  		var group_id = $( this ).closest( '.group-row' ).attr( 'data-group-id' );
  		var item_id = $( this ).closest( '.field-item' ).attr( 'data-item-id' );
  		var information_fields = $( this ).closest( '.pewc-information-fields' );
  		var last_row = $( information_fields ).find( '.product-extra-row-wrapper' ).last();
  		var row_count = 0;
  		if( last_row ) {
  			row_count = parseFloat( $( last_row ).attr( 'data-row-count' ) );
  			row_count++;
  		}
  		if( isNaN( row_count ) ) {
  			row_count = 0;
  		}

  		var clone_row = $( '.new-information-row .product-extra-row-wrapper' ).clone().appendTo( $( information_fields ).find( '.pewc-field-information-wrapper') );

      $( clone_row ).attr( 'data-row-count', row_count );
  		$( clone_row )
  			.find( '.pewc-field-row-label' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_rows][' + row_count + '][label]' )
  			.val( '' );

  		$( clone_row )
  			.find( '.pewc-field-row-data' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_rows][' + row_count + '][data]' )
  			.val( '' );

  		$( clone_row )
  			.find( '.pewc-image-attachment-id' )
  			.attr( 'name','_product_extra_groups_' + group_id + '_' + item_id + '[field_rows][' + row_count + '][image]' );

  		$( clone_row )
  			.find( '.pewc-field-image' )
  			.addClass( 'pewc-field-image-' + item_id + '_' + row_count );

  		$( clone_row )
  			.find( '.pewc-upload-option-image' )
  			.attr( 'data-item-id', item_id+'_'+row_count );

    },

    /**
		 * Remove an option
		 */
		remove_row: function( e ) {

      e.preventDefault;
  		var r = confirm( pewc_obj.delete_option );
  		if( r == true ) {
  			$(this).closest( '.product-extra-row-wrapper' ).fadeOut(
  				150,
  				function(){
  					$(this).remove();
  					// Remove this option from any conditions
  					// set_options_data( field_item );
  				}
  			);
  		}

    },

    /**
		 * Add a new condition
		 */
		add_new_condition: function( e ) {

      e.preventDefault();
  		var group_id = $(this).closest('.group-row').attr('data-group-id');
  		var item_id = $(this).closest('.field-item').attr('data-item-id');
  		var condition_fields = $(this).closest('.pewc-fields-conditionals');
  		var last_condition = $(condition_fields).find('.product-extra-conditional-row').last();
  		var condition_count = 0;
  		if( last_condition ) {
  			condition_count = parseFloat( $(last_condition).attr('data-condition-count') );
  			condition_count++;
  		}
  		if( isNaN( condition_count ) ) {
  			condition_count = 0;
  			$(this).closest('.pewc-fields-conditionals').find('.product-extra-action-match-row').fadeIn();
  		}

  		var clone_condition = $('.new-conditional-row').clone().insertBefore( $(this).parent() );
  		$(clone_condition).removeClass('new-conditional-row');

		// 3.22.0, remove User Role from field conditions for now
		$(clone_condition).find( '.pewc-condition-field optgroup[id="user-roles"]' ).remove();

  		$(clone_condition).attr('data-condition-count',condition_count);
  		$(clone_condition)
  			.find('.pewc-condition-field')
  			.attr('name','_product_extra_groups_' + group_id + '_' + item_id + '[condition_field][' + condition_count + ']')
  			.attr('id','condition_field_' + group_id + '_' + item_id + '_' + condition_count )
  			.attr('data-group-id', group_id)
  			.attr('data-item-id', item_id)
  			.attr('data-condition-id', condition_count)
  			.val('');

  		// If we're in global, just get fields from current group

  		// Remove the current field from the list of fields
  		var select = $(clone_condition).find('.pewc-condition-field').attr('id');
  		var select_id = '#condition_field_' + group_id + '_' + item_id + '_' + condition_count;
  		var option_value = 'pewc_group_' + group_id + '_' + item_id;
  		$(select_id + ' option[value="' + option_value + '"]').remove();

  		$(clone_condition)
  			.find('.pewc-condition-rule')
  			.attr('name','_product_extra_groups_' + group_id + '_' + item_id + '[condition_rule][' + condition_count + ']')
  			.attr('id','condition_rule_' + group_id + '_' + item_id + '_' + condition_count )
  			.attr('data-group-id', group_id)
  			.attr('data-item-id', item_id)
  			.attr('data-condition-id', condition_count);

    },

    /**
	 * Change a condition field
	 */
	change_condition_field: function( e ) {

      // Set the value as data to make duplicating easier
      $( this ).attr( 'data-value', $( this ).val() );

      // Display a value field if both selects have a legitimate value, i.e not 'not-selected'
  		var select = $(this);
  		var group_id = $(this).attr( 'data-group-id' );
      var is_group, condition_field, condition_rule;
      var condition_id = $(this).attr( 'data-condition-id' );
      if( $( this ).hasClass( 'pewc-group-condition-field' ) ) {
        is_group = true;
        condition_field = $( '#condition_field_' + group_id + '_' + condition_id ).val();
    		condition_rule = $( '#condition_rule_' + group_id + '_' + condition_id ).val();
      } else {
        var item_id = $(this).attr( 'data-item-id' );
        condition_field = $( '#condition_field_' + group_id + '_' + item_id + '_' + condition_id ).val();
    		condition_rule = $( '#condition_rule_' + group_id + '_' + item_id + '_' + condition_id ).val();
      }

  		// var condition_field = $( '#condition_field_' + group_id + '_' + item_id + '_' + condition_id ).val();
  		// var condition_rule = $( '#condition_rule_' + group_id + '_' + item_id + '_' + condition_id ).val();
  		if( condition_field != null && condition_field != 'not-selected' && condition_rule != 'not-selected' ) {
  			// Show the value field
  			var value_field;
  			// Find the field type of the selected field
  			var field_id = condition_field.replace( 'pewc_group_', 'field_type_' );
  			var field_type = $('#' + field_id ).val();
  			if( field_type == undefined ) {
  				// Catch 'cost'
  				field_type = $( select ).find(':selected').attr( 'data-type' );
  			}
  			var value_field = pewc_get_value_field_type( field_type );

			$( this ).closest( '.product-extra-field-third' ).find( '.pewc-hidden-field-type' ).val( field_type );

			// since 3.11.9, pewc_set_rule_field is called first, so that we can use the selected rule in pewc_add_value_field, where the attribute depends on the rule selected
			pewc_set_rule_field( select, field_type );
			pewc_add_value_field( select, field_id, field_type, value_field, '' );

			// this was the order these functions were called previously up to 3.11.6
			//pewc_add_value_field( select, field_id, field_type, value_field, '' );
  			//pewc_set_rule_field( select, field_type );

  		} else {
  			// Hide the value field
  		}

    },

    /**
		 * Delete a condition field
		 */
		remove_condition: function( e ) {

      e.preventDefault;
      var wrapper = $( this ).closest( '.pewc-fields-conditionals' );
  		var r = confirm( 'Delete this condition?' );
  		if( r == true ) {
  			$(this).closest( '.product-extra-conditional-row' ).fadeOut(
  				150,
  				function() {
            // Check if this is the last condition
            var count = $( wrapper ).find( '.product-extra-conditional-row' ).length;
  					if( count <= 2 ) {
  						// Last condition removed so hide actions and set to null
  						$(this).parent().find( '.product-extra-action-match-row' ).fadeOut();
  						$(this).closest( '.pewc-fields-conditionals' ).find( 'select option:selected' ).removeAttr( "selected" );
  					}
  					$(this).remove();
  				}
  			);
  		}

    },

	/**
	 * Change a condition rule
	 * since 3.11.9
	 */
	change_condition_rule_attributes: function( e ) {
		var conditional_row = $( this ).closest( '.product-extra-conditional-row' );
		if ( conditional_row.find( '.pewc-condition-field option:selected' ).attr( 'data-type' ) != 'attribute' ) {
			return; // do not do anything if condition is not an attribute
		}

		var select = conditional_row.find( '.pewc-condition-field' );
		var field_id = select.val();
		var field_type = 'attribute';
		var value_field = false;
		var rule = conditional_row.find( '.pewc-condition-rule option:selected' ).val(); //$( this ).val();
		if ( rule == 'is' || rule == 'is-not' ) {
			value_field = 'pewc-value-select'; // select field
		} else if ( rule == 'contains' || rule == 'does-not-contain' ) {
			value_field = 'pewc-input-text'; // input text
		}
		pewc_add_value_field( select, field_id, field_type, value_field, '' );
	},

    // When a swatch's allow_multiple setting is updated, find any conditions that include the swatch field
    toggle_allow_multiple: function() {

      var allow_multiple = $( this ).prop( 'checked' );
      var new_val = 'is';
      if( allow_multiple ) {
        new_val = 'contains';
      }
      var field = $( this ).closest( '.field-item' );
      var field_id = $( field ).attr( 'id' );
      $( 'body' ).find( '.pewc-condition-field' ).each( function() {
        if( $( this ).val() == field_id ) {
          // Toggle disabled statuses
          var rule = $( this ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-rule' );
          pewc_set_rules( $( rule ), allow_multiple, '' );
          $( rule ).val( new_val );
        }
      });

	  // 3.26.16, toggle Value Only availability in Option Price Visibility for Swatch fields
	  if ( field.hasClass( 'field-type-image_swatch' ) ) {
		if ( allow_multiple ) {
			pewc_actions.toggle_option_price_value_only( $( field ).find( '.pewc-option-price-visibility' ), 'disable' );
		} else {
			pewc_actions.toggle_option_price_value_only( $( field ).find( '.pewc-option-price-visibility' ), 'enable' );
		}
	  }

    },

    /**
	 * Add a new condition
	 */
	add_new_group_condition: function( e ) {

      e.preventDefault();
      var group = $( this ).closest( '.pewc-group-meta-table' );
  		var group_id = $( group ).attr( 'data-group-id' );

  		var condition_fields = $( group ).closest( '.pewc-fields-conditionals' );
  		var last_condition = $( group ).find( '.product-extra-conditional-row' ).last();
  		var condition_count = 0;
  		if( last_condition ) {
  			condition_count = parseFloat( $( last_condition ).attr( 'data-condition-count' ) );
  			condition_count++;
  		}
  		if( isNaN( condition_count ) ) {
  			condition_count = 0;
  			$( group ).find('.product-extra-action-match-row').fadeIn();
  		}

  		var clone_condition = $( '.new-conditional-row' ).clone().insertBefore( $(this).parent() );
  		$( clone_condition ).removeClass( 'new-conditional-row' );

  		$( clone_condition ).attr( 'data-condition-count', condition_count );
  		$( clone_condition )
  			.find('.pewc-condition-field')
        	.addClass( 'pewc-group-condition-field' )
  			.attr('name','_product_extra_groups_' + group_id + '[condition_field][' + condition_count + ']')
  			.attr('id','condition_field_' + group_id + '_' + condition_count )
  			.attr('data-group-id', group_id)
  			.attr('data-condition-id', condition_count)
  			.val('');

  		// If we're in global, just get fields from current group

      // Remove group's own fields from group condition
      var select = $( clone_condition ).find( '.pewc-condition-field option' ).each( function() {
        var option_value = $( this ).attr( 'value' );
        if( option_value.indexOf( 'pewc_group_' + group_id ) > -1 ) {
          $( this ).closest( 'optgroup' ).remove();
        }
      });
      // Remove cost and quantity optgroup
      $( clone_condition ).find( ".pewc-condition-field optgroup[label='Product Cost']" ).remove();

      $(clone_condition)
  			.find('.pewc-condition-rule')
  			.attr('name','_product_extra_groups_' + group_id + '[condition_rule][' + condition_count + ']')
  			.attr('id','condition_rule_' + group_id + '_' + condition_count )
  			.attr('data-group-id', group_id)
  			.attr('data-condition-id', condition_count);

      $(clone_condition)
  			.find('.pewc-hidden-field-type')
  			.attr('name','_product_extra_groups_' + group_id + '[condition_field_type][' + condition_count + ']')
  			.attr('id','condition_field_type_' + group_id + '_' + condition_count );

    },

    /**
		 * Update the products_layout field
		 */
    update_products_layout: function( e ) {
      e.preventDefault;
      var layout = $(this).val();
      var wrapper = $(this).closest('.field-item');
      $(wrapper).removeClass( function(index, className) {
        return (className.match (/(^|\s)products-layout-\S+/g) || []).join(' ');
      });
      $(wrapper).addClass('products-layout-'+layout);
      // Set allow_none to enabled if layout is checkboxes
      $(wrapper).find('.pewc-field-allow_none').attr('disabled',false);
      if( layout=='checkboxes' || layout=='checkboxes-list' || layout=='column' ) {
        // $(wrapper).find('.pewc-field-allow_none').attr('checked',true);
        $(wrapper).find('.pewc-field-allow_none').attr('disabled',true);
      }

      var allow_multiple;

      if( layout == 'checkboxes' || layout=='checkboxes-list' || layout == 'column' ) {
        // Toggle the hidden allow_multiple field, which is used in setting conditions
        $( wrapper ).find( '.pewc-allow-multiple' ).prop( 'checked', true );
        allow_multiple = true;
      } else {
        $( wrapper ).find( '.pewc-allow-multiple' ).prop( 'checked', false );
        allow_multiple = false;
      }

      var new_val = 'is';
      if( allow_multiple ) {
        new_val = 'contains';
      }

      var field_id = $( wrapper ).attr( 'id' );

      pewc_actions.update_condition_rules( field_id, allow_multiple, new_val );

    },

	update_field_action: function( e ) {

		var layout = $(this).val();
		var wrapper = $(this).closest('.field-item');
		$(wrapper).removeClass( function(index, className) {
			return (className.match (/(^|\s)field-action-\S+/g) || []).join(' ');
		});
		$(wrapper).addClass('field-action-'+layout);

	},

    /**
		 * Update the rules in conditions
		 */
    update_condition_rules: function( field_id, allow_multiple, new_val ) {
      $( 'body' ).find( '.pewc-condition-field' ).each( function() {
        if( $( this ).val() == field_id ) {
          // Toggle disabled statuses
          var rule = $( this ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-rule' );
          pewc_set_rules( $( rule ), allow_multiple, '' );
          $( rule ).val( new_val );
        }
      });

    },

    /**
		 * Update the products_quantities field
		 */
    update_products_quantities: function( e ) {
      e.preventDefault;
  		var quantities = $(this).val();
  		var wrapper = $(this).closest('.field-item');
  		$(wrapper).removeClass( function(index, className) {
  			return (className.match (/(^|\s)products-quantities-\S+/g) || []).join(' ');
  		});
  		$(wrapper).addClass('products-quantities-'+quantities);
    },

    /**
		 * Toggle the per character checkbox
		 */
		toggle_per_char: function( e ) {
      e.preventDefault;
      var wrapper = $( this ).closest( '.field-item' ).toggleClass( 'per-char-selected' );
    },

    /**
		 * Update the default fields(s) value
		 */
		set_default_field: function( e ) {
      e.preventDefault;
      // If the field is a checkbox, set the default value depending on whether it's checked or not
      if( $( this ).hasClass( 'pewc-field-default-field-checkbox' ) ) {
        var field_status = '';
        if( $( this ).prop( 'checked' ) == true ) {
          field_status = 'checked';
        }
        $( this ).closest( '.pewc-default-fields' ).find( '.pewc-field-default-hidden' ).val( field_status );
      } else {
        $( this ).closest( '.pewc-default-fields' ).find( '.pewc-field-default-hidden' ).val( $( this ).val() );
      }
    },

    /**
	 * Update a field type
	 */
    change_field_type: function( e ) {

		e.preventDefault;
  		var field_type = $( this ).val();
		$( this ).attr( 'data-field-type', field_type );
		var wrapper = $( this ).closest( '.field-item' );
		var allow_multiple = $( wrapper ).find( '.pewc-allow-multiple' ).is( ':checked' );

		if ( ( field_type == 'image_swatch' && allow_multiple ) || field_type == 'checkbox_group' ) {
			//$( wrapper ).find( '.pewc-option-price-visibility' ).children('option[value="value"]').attr( 'disabled', 'disabled' );
			//$( wrapper ).find( '.pewc-option-price-visibility' ).prop('selectedIndex', 0);
			pewc_actions.toggle_option_price_value_only( $( wrapper ).find( '.pewc-option-price-visibility' ), 'disable' );
		} else {
			//$( wrapper ).find( '.pewc-option-price-visibility' ).children('option[value="value"]').attr( 'disabled', false );
			pewc_actions.toggle_option_price_value_only( $( wrapper ).find( '.pewc-option-price-visibility' ), 'enable' );
		}

		// Check if there are any conditionals associated with this field
  		pewc_check_field_has_conditions( $( this ).attr( 'id' ), field_type );

  		$( wrapper ).removeClass( function( index, className ) {
  			return ( className.match (/(^|\s)field-type-\S+/g) || [] ).join( ' ' );
  		});
  		$( wrapper ).addClass( 'field-item field-type-' + field_type );

    },

	// 3.26.16, create a separate function for this
	toggle_option_price_value_only: function( field, action ) {

		if ( 'disable' === action ) {
			$( field ).children( 'option[value="value"]' ).attr( 'disabled', 'disabled' );
			$( field ).prop( 'selectedIndex', 0 );
		} else {
			$( field ).children( 'option[value="value"]' ).attr( 'disabled', false );
		}

	},

    update_field_names_object: function() {

      if( $( 'body' ).hasClass( 'post-type-product' ) || $( 'body' ).hasClass( 'post-type-pewc_group' ) || $( 'body' ).hasClass( 'pewc_product_extra_page_global' ) || $( 'body' ).hasClass( 'product-add-ons_page_global' ) || $( 'body' ).attr( 'class' ).indexOf('_page_global') > -1 ) {
  			var all_fields = {};
  			$( 'body' ).find( '.field-item' ).not( '.new-field-item' ).find( '.pewc-field-label' ).each(function(){
  				var group_id = $( this ).closest( '.group-row' ).attr( 'data-group-id' );
  				var field_id = $( this ).closest( 'li.field-item' ).attr( 'data-item-id' );
  				var label = '[no label]';
  				if( $( this ).val() != '' ) {
  					label = $( this ).val();
  				}
  				var type = $( 'body' ).find( '#field_type_' + group_id + '_' + field_id ).attr( 'data-field-type' );
  				if( ! all_fields[group_id] ) {
  					all_fields[group_id] = {};
  				}
  				all_fields[group_id][field_id] = {'label': label, 'type': type};
  				if( type=='select' || type=='select-box' || type=='radio' || type=='image_swatch' || type=='checkbox_group' ) {
  					// Update data-options
  					var option_fields = $( this ).closest( 'li.field-item' ).find( '.pewc-option-fields' );
  					var options = [ '' ];
  					$(option_fields).find( '.pewc-field-option-value' ).each(function(i,v){
  						options.push($( this ).val());
  					});
  					$( '#pewc_option_'+group_id+'_'+field_id).find( '.pewc-data-options' ).attr( 'data-options', JSON.stringify(options) );
  					// Get all possible values for the select field
  					all_fields[group_id][field_id] = {'label': label, 'type': type, 'options': options};
  				} else if( type=='products' ) {
  					// Update data-options
  					var selected_products = $( this ).closest( 'li.field-item' ).find( '.pewc-field-child_products' ).val();
  					$( this ).closest( 'li.field-item' ).find( '.pewc-data-options' ).attr( 'data-options',JSON.stringify(selected_products));
  					// Get all possible values for the select field
  					all_fields[group_id][field_id] = {'label': label, 'type': type, 'options': selected_products};
  				} else if( type=='product-categories' ) {
  					// Update data-options
  					var selected_categories = $( this ).closest( 'li.field-item' ).find( '.pewc-field-child_categories' ).val();
  					$( this ).closest( 'li.field-item' ).find( '.pewc-data-options' ).attr( 'data-options',JSON.stringify(selected_categories));
  					// Get all possible values for the select field
  					all_fields[group_id][field_id] = {'label': label, 'type': type, 'options': selected_categories};
  				}

  			});
  			$( '.product-extra-group-data' ).attr( 'data-fields', JSON.stringify( all_fields ) );
			update_conditional_fields();
			update_conditional_value_fields();
  		}

    },

    refresh_group_order: function() {
      var sorted = $( "#pewc_group_wrapper" ).sortable( "toArray" );
      sorted = sorted.join( ',' );
      sorted = sorted.replace( /group-/g, '' );
      $( '#pewc_group_order' ).val( sorted.replace( /group-/g, '' ) );
      $( '#pewc_global_group_order' ).val( sorted.replace( /group-/g, '' ) );
    },

    upload_media: function( e ) {

		e.preventDefault();
		// 3.20.1, this function is used by both option images and field image
		if ( $( this ).hasClass( 'pewc-upload-option-image' ) ) {
			// add-on option images
			var wrapper = $( this ).closest( '.pewc-option-image' );
			var image = $( wrapper ).find( '.pewc-field-image' );
		} else {
			// add-on field image
			var item_id = $(this).data( 'item-id' );
			var wrapper = $( this ).closest( '.pewc-field-image-' + item_id );
			var image = $('.pewc-field-image-'+item_id+' .pewc-field-image');
		}

  		// Removing or adding the image?
  		if( $(this).hasClass('remove-image') ) {
  			// Remove
  			$(image).removeClass('has-image');
  			$(this).removeClass('remove-image');
  			// $('.pewc-field-image-'+item_id+' .pewc-image-attachment-id').val('');
  			$( wrapper ).find( '.pewc-image-attachment-id' ).val('');
  			// var placeholder = $('.pewc-field-image-'+item_id+' .pewc-upload-button img').attr('data-placeholder');
  			var placeholder = $( wrapper ).find( '.pewc-upload-button img').attr('data-placeholder');
  			// $('.pewc-field-image-'+item_id+' .pewc-upload-button img').attr( 'src', placeholder );
  			$( wrapper ).find( '.pewc-upload-button img' ).attr( 'src', placeholder );
  		} else {
  			// Sets up the media library frame
  			meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
  				library: { type: 'image' }
  			});
  			// $('.pewc-field-image-'+item_id+' .pewc-field-image').addClass('has-image');
  			$( wrapper ).find( '.pewc-field-image' ).addClass( 'has-image' );
  			$( this ).addClass('remove-image');
  			// Runs when an image is selected.
  			meta_image_frame.on('select', function(){
  				// Grabs the attachment selection and creates a JSON representation of the model.
  				var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
  				// Sends the attachment URL to our custom image input field.
  				// $('.pewc-field-image-'+item_id+' .pewc-image-attachment-id').val(media_attachment.id);
  				$( wrapper ).find( '.pewc-image-attachment-id' ).val(media_attachment.id);
  				// $('.pewc-field-image-'+item_id+' .pewc-upload-button img').attr( 'src', media_attachment.url );
  				$( wrapper ).find( '.pewc-upload-button img' ).attr( 'src', media_attachment.url );
  			});
  			// Opens the media library frame.
  			meta_image_frame.open();
  		}

    },

    update_ymd: function( e ) {

      var ymd_field = $( this ).parent().find( '.pewc-field-maxdate-ymd' );
      var d = new Date( $(this).datepicker('getDate') );
			var month = '0' + ( d.getMonth() + 1 );
			var day = '0' + ( d.getDate() );
			var ymd_date = d.getFullYear() + '-' + month.substr( -2, 2 ) + '-' + day.substr( -2, 2 );
      $( ymd_field ).val( ymd_date );

    },

	// 3.24.2, reinitialize select2 and enhanced select2 (product search)
	reinitialize_select2( clone_element ) {

		// remove first the old select2 container
		$( clone_element ).find( 'span.select2.select2-container' ).each( function(){
			$( this ).remove();
		});
		// initialize again
		$( clone_element ).find( '.pewc-rule-select' ).each( function(){
			$( this ).select2();
		});
		// look for product search and category search
		$( clone_element ).find( '.wc-product-search, .wc-category-search' ).each( function(){
			// for new fields, selected products or categories are added to the hidden select field without the "selected" attribute.
			// This prevents select2 from adding it to the display when a field is copied. So we add them here if they don't exist yet
			$( this ).find( 'option' ).each( function(){
				if ( $( this ).attr( 'selected' ) !== 'selected' ) {
					// add the selected attribute
					$( this ).attr( 'selected', 'selected' );
				}
			});
			// remove the enhanced class so that it can be enhanced again
			$( this ).removeClass( 'enhanced' );
		});
		// reinitialize enhanced select2
		$( document.body ).trigger( 'wc-enhanced-select-init' );

	},

  };

  pewc_actions.init();

  $( 'body' ).on( 'click', '.pewc-group-meta-heading, .pewc-global-set-wrap .pewc-actions .collapse', function( e ) {
		e.preventDefault;
		$( this ).closest( '.field-table' ).toggleClass( 'collapse-panel' );
	});
	$( 'body' ).on( 'click', '.pewc-field-meta-heading', function( e ) {
		e.preventDefault;
		$( this ).closest( '.field-item' ).toggleClass( 'collapsed-field' );
	});
  $( 'body' ).on( 'keyup','.pewc-group-title', function() {
		var title = $( this ).val();
		var heading = $( this ).closest( '.group-row' ).find( '.pewc-display-title' ).text(title);
	});
	$( 'body' ).on( 'keyup','.pewc-field-label',function(){
		var title = $( this ).val();
		var heading = $( this ).closest( '.field-item' ).find( '.pewc-display-field-title' ).text(title);
	});

  function pewc_check_field_has_conditions( id, field_type ) {
		var field_type = $( '#' + id).val();
		var field_id = id.replace( 'field_type_', 'pewc_group_' );
		$( '.pewc-condition-select' ).each(function(i,v){
			var select_id = $( this ).attr( 'id' );
			if ( select_id == '' ) {
				return; // 3.21.2
			}
			$( '#' + select_id).find( 'option:selected' ).each(function(){
				var option_value = $( this ).val();
				if( option_value == field_id ) {
					var r = confirm( pewc_obj.condition_continue );
					if( r == true ) {
						// Iterate through each instance of this field in conditions, check the value field if required
						var value_field_type = pewc_get_value_field_type( field_type );
						// Try to retain the condition value if field types permit it
						var condition_value = $( '#' + select_id).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-value' ).val();
						pewc_add_value_field( $( '#' + select_id), field_id, field_type, value_field_type, condition_value );
					}
				}
			});
		});
	}

  function update_conditional_value_fields() {

    // Update all the options with any newly added option
    $( 'body' ).find( '.field-item' ).not( '.new-field-item' ).find( '.pewc-field-options-wrapper' ).each(function(i,v){
      var option_id = $( this ).closest( '.pewc-option-fields' ).attr( 'id' );
      option_id = option_id.replace( 'pewc_option','pewc_group' );
      var options = $( this ).attr( 'data-options' );
      if( options != undefined ) {
        var options = JSON.parse( $( this ).attr( 'data-options' ) );
        if( $( this ).closest( '.pewc-option-fields' ).attr( 'id' ) != undefined ) {
          $( '.pewc-condition-select' ).each(function(i,v){
            if( $( this ).val() == option_id ) {
              // Using .pewc-condition-set-value to ensure we don't overwrite values that have already been set
              var condition_value_field = $( this ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-value' ).not( '.pewc-condition-set-value' );
              if( options != undefined ) {
                // Remove existing options and replace with updated set
                var selected = $(condition_value_field).find( ':selected' ).val();
                $( condition_value_field ).find( 'option' ).remove();
                for(var i=0; i < options.length; i++ ) {
                  $(condition_value_field).append($( '<option>', {
                    value: options[i],
                    text: options[i]
                  }));
                }
                $(condition_value_field).val(selected);
              }
              // Replace Is/Not Is for fields that allow multiple selections
              $( '.pewc-condition-rule' ).each(function( i, v ) {
                var has_multiple = $( this ).hasClass( 'pewc-has-multiple' );
                pewc_set_rules( $( this ), has_multiple, $( this ).attr( 'data-rule' ) );

              });
            }
          });
        }
      }
    });

  }

  function update_conditional_fields() {

    if( $( '.product-extra-group-data' ).attr( 'data-fields' ) ) {
      var all_fields = JSON.parse( $( '.product-extra-group-data' ).attr( 'data-fields' ) );
    } else {
      return;
    }

    // If we're in a product, get fields from all groups
    // If we're on the global page, only get fields belonging to the specific group
    // Changed in 2.2.2 so that all fields are available in global
    var page = 'product';
    if( $( 'body' ).hasClass( 'pewc_product_extra_page_global' ) || $( 'body' ).hasClass( 'product-add-ons_page_global' ) || $( 'body' ).hasClass( 'post-type-pewc_group' ) ) {
      page = 'global';
    }

    // Save options by group for global
    var options_by_group = [];
    // Get the first option
    // var option_value = $( '.new-conditional-row .pewc-condition-field.pewc-condition-select' ).find( 'option:first-of-type' ).html();
    var options = '<option value="not-selected">'+pewc_obj.select_text+'</option>';
	// get original list of all attributes before getting removed
	var global_attributes = $( '.new-conditional-row .pewc-condition-field.pewc-condition-select optgroup#pewc-all-attributes-optgroup' ).html();
    // Remove all current options except the first one
    $( '.new-conditional-row .pewc-condition-field.pewc-condition-select option' ).remove();
    $( '.new-conditional-row .pewc-condition-field.pewc-condition-select optgroup' ).remove();
    // Read new set of options from object
    for( var group in all_fields ) {
      var group_name = $( '#group-' + group + ' .pewc-group-title' ).val();
      if( ! group_name ) group_name = '[No group title]';
      options_by_group[group] = '<option value="not-selected">'+pewc_obj.select_text+'</option>';
      var items = all_fields[group];
      options += '<optgroup label="' + group_name + ' #' + group + '">';
      for( var field in items ) {
        if( ! items.hasOwnProperty(field) ) continue;
        if( items[field].type == 'information' ) continue;
        if( items[field].type == 'upload' ) {
          items[field]['label'] += ' (Number Uploads)';
        };
        options_by_group[group] += '<option data-type="' + items[field]['type'] + '" value="pewc_group_'+group+'_'+field+'">'+items[field]['label']+'</option>';
        options += '<option data-type="' + items[field]['type'] + '" value="pewc_group_'+group+'_'+field+'">'+items[field]['label']+' [#'+field+']</option>';
      }
      options += '</optgroup>';
    }

	if ( $( '.variations_options.variations_tab' ).is( ':visible' ) ) {
		// 3.11.9. do this for variable products only (maybe including variable subscriptions)
		// find all attributes
		var attribute_options = '';
		$( '#product_attributes .woocommerce_attribute.taxonomy').each(function(){
			var taxonomy = $( this ).attr( 'data-taxonomy' );
			if ( taxonomy != '' ) {
				var attribute_name = $( this ).find( 'h3 .attribute_name' ).text();
				if ( attribute_name == '' ) attribute_name = taxonomy;
				attribute_options += '<option data-type="attribute" value="' + taxonomy + '">' + attribute_name + '</option>';
			}
		})
		if ( attribute_options != '' ) {
			options += '<optgroup id="pewc-attributes-optgroup" label="Attributes">';
			options += attribute_options;
			options += '</optgroup>';
		}
	} else if ( page == 'global' && global_attributes != undefined ) {
		// this could be a global addon, add back all attributes
		options += '<optgroup id="pewc-all-attributes-optgroup" label="Attributes">';
		options += global_attributes;
		options += '</optgroup>';
	}

    options += '<optgroup id="cost-optgroup" label="Product Cost">';
    options += '<option data-type="cost" value="cost">Cost</option>';
    options += '<option data-type="quantity" value="quantity">Quantity</option>';
    options += '</optgroup>';

	options += '<optgroup id="status" data-type="status" label="Status">';
	options += '<option data-type="status" value="log-in-status">Logged In</option>';
	options += '</optgroup>';

	// 3.22.0
	options += '<optgroup id="user-roles" data-type="user-roles" label="Roles">';
	options += '<option data-type="user-role" value="user-role">User Role</option>';
	options += '</optgroup>';

    // Update the new condition select field
    $( '.new-conditional-row .pewc-condition-field.pewc-condition-select' ).append( options );

    // Now update all the condition select fields in use
    $( 'body' ).find( '.group-row .pewc-condition-field.pewc-condition-select' ).each(function() {
      // Update the field with the new options
      var group_id = $( this ).closest( '.group-row' ).attr( 'data-group-id' );
      var field_id = $( this ).closest( 'li.field-item' ).attr( 'data-item-id' );
      // Retain the currently selected option
      var selected = $( this ).find( ':selected' ).val();

      $( this ).children().remove( 'optgroup' );
      $( this ).find( 'option' ).remove();
      $( this ).append(options);

	  // 3.22.0, remove User Role from field conditions, only allowed on Group Conditions for now
	  if ( $( this ).closest( 'li.field-item' ).length > 0 ) {
		$( this ).find( 'optgroup[id="user-roles"]' ).remove();
	  }

      // Ensure that a field can't be a condition of itself
      $( this ).find( 'option[value="pewc_group_' + group_id + '_' + field_id + '"]' ).remove();

      // Set correct option to selected
      $( this ).val( selected );

	  if ( $( this ).attr( 'data-field-type' ) == 'attribute' && $( this ).attr( 'data-value' ) != '' ) {
		// 3.11.9, attributes are dynamically created, so set the selected value here
		$( this ).val( $( this ).attr( 'data-value' ) );
		// let's trigger this because the attribute condition needs change_condition_rule_attributes to be triggered
		$( this ).trigger( 'change' );
	  }

    });

    $( 'body' ).find( '.pewc-group-meta-table' ).each(function() {
      var group_id = $( this ).closest( '.pewc-group-meta-table' ).attr( 'data-group-id' );
      // Remove group's own fields from group condition
      var select = $( this ).find( '.pewc-condition-field option' ).each( function() {
        var option_value = $( this ).attr( 'value' );
        if( option_value.indexOf( 'pewc_group_' + group_id ) > -1 ) {
          $( this ).closest( 'optgroup' ).remove();
        }
      });
      // Remove cost and quantity optgroup
      $( this ).find( ".pewc-condition-field optgroup[label='Product Cost']" ).remove();
    });

  }

  // Remove associated conditions when field is deleted
	function pewc_remove_associated_conditions( group_id, item_id ) {
		// Look for each condition where this field is selected
		var field_id = 'pewc_group_' + group_id + '_' + item_id;
		$( '.pewc-condition-select' ).each(function(i,v){
			var select_id = $( this ).attr( 'id' );
			$( '#' + select_id).find( 'option:selected' ).each(function(){
				var option_value = $( this ).val();
				if( option_value == field_id ) {
					var conditions_wrapper = $( this ).closest( '.pewc-fields-conditionals' );
					$( '#' + select_id).closest( '.product-extra-conditional-rule' ).remove();
					// Have we removed the last condition?
					if( $(conditions_wrapper).find( '.product-extra-conditional-rule' ).length == 0 ) {
						$(conditions_wrapper).find( '.product-extra-action-match-row' ).fadeOut();
					}
				}
			});
		});
	}

  // Update data for field options
  function set_options_data( option_wrapper ) {
		if( $(option_wrapper).length > 0 ) {
			var options = [];
			$(option_wrapper).find('.pewc-field-option-value').each(function(i,v){
				options.push($(this).val());
			});
			$(option_wrapper).attr('data-options',JSON.stringify(options));
		}
	}

  // Return the type of value field based on the condition field selected
	function pewc_get_value_field_type( field_type ) {
		if( field_type == 'number' || field_type == 'cost' || field_type == 'calculation' || field_type == 'quantity' || field_type == 'upload' ) {
			return 'pewc-input-number';
		} else if( field_type == 'text' || field_type == 'advanced-preview' || field_type == 'product-categories' ) {
			return 'pewc-input-text';
		} else if( field_type == 'select' || field_type == 'select-box' || field_type == 'radio' || field_type == 'image_swatch' || field_type == 'products' || field_type == 'checkbox_group' || field_type == 'status' || field_type == 'user-role' ) {
			return 'pewc-value-select';
		} else if( field_type == 'checkbox' ) {
			return 'pewc-value-checkbox';
		}
		return false;
	}

	// Set the value field for a conditional
	function pewc_add_value_field( field, field_id, field_type, value_field, val ) {
		if( val == '__checked__' ) {
			val = '';
		}

		$(field).closest('.product-extra-conditional-row').find('.pewc-checked-placeholder').remove();
		var wrapper = $(field).closest('.product-extra-conditional-row');
		var original_value_field = $(wrapper).find('.pewc-condition-value-field .pewc-condition-value');
		var data_select_value = original_value_field.attr( 'data-select-value' );
		var input_value = original_value_field.attr( 'value' );
		original_value_field.remove();
		var group_id = $(field).closest('.group-row').attr('data-group-id');
		var item_id = $(field).closest('.field-item').attr('data-item-id');
		var condition_id = $(field).attr( 'data-condition-id' );

		if ( field_type == 'attribute' ) {
			if ( ! value_field ) {
				// an attribute condition can either use a select field or a text field, so we determine that here
				var current_rule_val = $(field).closest('.product-extra-conditional-row').find('.pewc-condition-rule').val();
				if ( current_rule_val == 'is' || current_rule_val == 'is-not' ) {
					value_field = 'pewc-value-select'; // select field
				} else if ( current_rule_val == 'contains' || current_rule_val == 'does-not-contain' ) {
					value_field = 'pewc-input-text'; // text field
				}
			}
			if ( ! val ) {
				if ( value_field == 'pewc-value-select' ) {
					val = data_select_value;
				} else if ( value_field == 'pewc-input-text' ) {
					val = input_value;
				}
			}
		}

		var clone_value = $('.new-condition-value-field .' + value_field).clone().appendTo( $(wrapper).find('.pewc-condition-value-field') );

		if( item_id != undefined ) {
			// Field condition
			$(clone_value)
  				.attr('name','_product_extra_groups_' + group_id + '_' + item_id + '[condition_value][' + condition_id + ']')
				.attr('id','condition_value_' + group_id + '_' + item_id + '_' + condition_id )
  				.attr('data-group-id', group_id)
  				.attr('data-item-id', item_id)
  				.attr('data-condition-id', condition_id)
  				.val(val);
		} else {
      		// Group condition
      		$(clone_value)
  				.attr('name','_product_extra_groups_' + group_id + '[condition_value][' + condition_id + ']')
  				.attr('id','condition_value_' + group_id + '_' + condition_id )
  				.attr('data-group-id', group_id)
  				.attr('data-condition-id', condition_id)
  				.val(val);
    	}

		if ( field_type == 'attribute' ) {
			if ( value_field == 'pewc-value-select' ) {
				// 3.11.9. populate this field with attribute values
				var options = pewc_populate_attribute_field( field_id );

				if ( options.length > 0 ) {
					for ( var i in options ) {
						$( clone_value ).append( $( '<option>' , {
							value: options[i].value,
							text: options[i].text
						}));

						if ( data_select_value == options[i].value ) {
							// this is valid value, maybe set during first load, so let's pass this on
							$( clone_value ).attr( 'data-select-value', data_select_value );
							$( clone_value ).val( data_select_value );
						}
					}
				}
			} else if ( value_field == 'pewc-input-text' ) {
				$(clone_value).attr( 'value', val );
			}
		}
		if( field_type == 'select' || field_type == 'select-box' || field_type == 'radio' || field_type == 'image_swatch' || field_type == 'products' || field_type == 'product-categories' || field_type == 'checkbox_group' ) {
			var options = pewc_populate_select_value_field( $(field).val() );
			for(var i=0; i < options.length; i++ ) {
				$(clone_value).append($('<option>', {
					value: options[i],
					text: options[i]
				}));
			}
		} else if( field_type == 'checkbox' ) {
			$(wrapper).find('.pewc-checked-placeholder').remove();
			var clone_span = $('.new-condition-value-field .pewc-checked-placeholder').clone().appendTo( $(wrapper).find('.pewc-condition-value-field') );
			$( clone_value ).addClass('pewc-condition-set-value');
			$( clone_value ).val( '__checked__' );
		} else if ( field_type == 'product-categories' ) {
			// input text, do nothing?
		} else if ( field_type == 'status' ) {
			// Log-in status
			$(clone_value).append($('<option value="true">True</option>' ) );
			$(clone_value).append($('<option value="false">False</option>' ) );
		} else if ( field_type == 'user-role' ) {
			// 3.22.0, User Role based group
			var options = pewc_populate_user_roles();
			if ( options.length > 0 ) {
				for(var i=0; i < options.length; i++ ) {
					$(clone_value).append($('<option>', {
						value: options[i].value,
						text: options[i].text
					}));
				}
			}
		}
	}

  // Set the conditional rule field
  function pewc_set_rule_field( select, field_type ) {
		// Decide whether to show is/is not or contains/does not contain
		var row = $( select ).closest( '.product-extra-conditional-row' );
		var rule = $( row ).find( '.pewc-condition-rule' );
		var has_multiple = pewc_has_multiple( select );
		pewc_set_rules( rule, has_multiple, $( select ).val() );
	}

  // Set rules for conditions
  // @param field   This is the condition row, e.g. with an ID of condition_rule_7500_7501_0
  function pewc_set_rules( field, has_multiple, is_cost ) {

    if( is_cost == null || field == undefined ) {

      return;

    } else {

      // var field_type_id = $( field ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-field' ).val();
      var field_type_id = $( field ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-field' ).attr( 'data-value' );
      var field_type = $( '#' + field_type_id ).find( '.pewc-field-type' ).attr( 'data-field-type' );
      if( field_type_id == 'cost' || field_type_id == 'quantity' ) {
        field_type = field_type_id;
      }
	  var is_attribute = false;
	  if ( $( field ).closest( '.product-extra-conditional-row' ).find( '.pewc-condition-field option:selected' ).attr( 'data-type' ) == 'attribute' ) {
		is_attribute = true;
	  }

      $( field ).closest( '.product-extra-conditional-row' ).find( '.pewc-hidden-field-type' ).val( field_type );

      var is_number_field = false;
      if( is_cost.indexOf( 'cost' ) > -1 || is_cost.indexOf( 'quantity' ) > -1 || field_type == 'calculation' || field_type == 'number' || field_type == 'upload' || field_type == 'quantity' ) {
		is_number_field = true;
      }

      if( pewc_obj.enable_numeric_options && (field_type == 'radio' || field_type == 'select') ) {
		// let's limit this option to radio and select field types for now...
    	is_number_field = true;
      }

  		$(field).find('option[value="is"]').attr('disabled', has_multiple);
  		$(field).find('option[value="is-not"]').attr('disabled', has_multiple);
  		$(field).find('option[value="contains"]').attr('disabled', ! has_multiple);
  		$(field).find('option[value="does-not-contain"]').attr('disabled', ! has_multiple);
  		$(field).find('option[value="cost-equals"]').attr('disabled', ! is_number_field );
  		$(field).find('option[value="cost-greater"]').attr('disabled', ! is_number_field );
  		$(field).find('option[value="cost-less"]').attr('disabled', ! is_number_field );
    	$(field).find('option[value="greater-than-equals"]').attr('disabled', ! is_number_field );
  		$(field).find('option[value="less-than-equals"]').attr('disabled', ! is_number_field );

  		// Ensure an enabled option is selected
  		var current_val = $( field ).val();
  		if( current_val == null ) current_val = '';

  		if ( is_attribute ) {
			$(field).find('option[value="contains"]').attr( 'disabled', false );
  			$(field).find('option[value="does-not-contain"]').attr( 'disabled', false );
		} else if( has_multiple && current_val.indexOf( 'contain' ) > -1 ) {
  			$(field).val( current_val );
  			$(field).addClass( 'pewc-has-multiple' );
  		} else if( ! is_number_field && current_val.indexOf( 'is' ) == -1 ) {
  			$(field).val( 'is' );
  			$(field).removeClass('pewc-has-multiple');
  		} else if( is_number_field ) {
			if( !pewc_obj.enable_numeric_options ) {
				// disable only for real number fields
  				$(field).find('option[value="is"]').attr( 'disabled', true );
  				$(field).find('option[value="is-not"]').attr( 'disabled', true );
  				$(field).find('option[value="contains"]').attr( 'disabled', true );
  				$(field).find('option[value="does-not-contain"]').attr( 'disabled', true );
			}
  			$(field).find('option[value="cost-equals"]').attr( 'disabled', false );
  			$(field).find('option[value="cost-greater"]').attr('disabled', false );
        	$(field).find('option[value="cost-less"]').attr('disabled', false );
        	$(field).find('option[value="cost-greater-equals"]').attr('disabled', false );
  			$(field).find('option[value="cost-less-equals"]').attr('disabled', false );
  			if( ! $(field).val() ) {
  				$(field).val( 'cost-equals' ); // cost-equals etc is also used for quantity
  			}
  			$(field).removeClass( 'pewc-has-multiple' );
  		}
    }

    // Set first enabled option if necessary
    if( $( field ).val() == null ) {
      $( field ).children('option:enabled').eq(0).prop('selected',true);
    }

	//$( field ).trigger( 'change' ); // 3.11.9. let's trigger this because the attribute condition needs change_condition_rule_attributes to be triggered
  }

  // Check if our field type allows multiple selections
	function pewc_has_multiple( field ) {
		var parent_field_id = $(field).val(); // The id of the field that we are dependent on
		var parent_field_type = $('#' + parent_field_id).find('.pewc-field-type').val();

		if( parent_field_type == 'products' || parent_field_type == 'product-categories' ) {
			var parent_field_layout = $('#' + parent_field_id).find('.pewc-field-products_layout').val();
			if ( parent_field_layout == 'checkboxes' || parent_field_layout == 'checkboxes-list' || parent_field_layout == 'column' ) {
				return true;
			}
		} else if ( parent_field_type == 'checkbox_group' ) {
			return true;
		} else if( parent_field_type == 'image_swatch' ) {
			if( $('#' + parent_field_id).find('.pewc-allow-multiple').attr('checked') ) {
				return true;
			}
		}
		return false;
	}

  // Populate a dynamically added select field
	function pewc_populate_select_value_field( condition_field ) {
		var option_field = condition_field.replace( 'pewc_group', 'pewc_option' );
		var data = $('body').find('#' + option_field + ' .pewc-data-options').attr( 'data-options' );
		if( data == undefined ) {
			data = '[]';
		}
		data = JSON.parse( data );
		return data;
	}

	// since 3.11.9 populate dynamically an attribute condition
	function pewc_populate_attribute_field( attribute ) {
		var attribute_container = $( '#product_attributes .woocommerce_attribute.taxonomy.'+attribute);
		var product_attribute_values = attribute_container.find( 'select.attribute_values option:selected' );
		var product_attributes_array = [];
		var options = [];

		if ( product_attribute_values.length > 0 ) {
			// save the values in an array so that they can be searched later
			product_attribute_values.each( function() {
				product_attributes_array.push( parseFloat( $( this ).val() ) );
			});
		}

		// get all attributes
		var all_attribute_values = $( '#pewc_all_attributes_json' ).val();
		if ( all_attribute_values != '' ){
			var all_attribute_values_array = JSON.parse( all_attribute_values );
			var attribute_values = all_attribute_values_array[attribute];

			if ( attribute_values.length > 0 ) {
				for ( var i in attribute_values ) {
					if ( product_attributes_array.length < 1 || product_attributes_array.includes( attribute_values[i].id ) ) {
						// only push values if product_attributes_array is empty (maybe we're in global) or if the attribute is used by the product
						options.push( { "value" : attribute_values[i].slug, "text" : attribute_values[i].value } );
					}
				}
			}
		}

		return options;
	}

	// 3.22.0
	function pewc_populate_user_roles() {
		var options = [];
		var all_roles_values = $( '#pewc_all_roles_json' ).val();
		if ( all_roles_values != '' ) {
			var all_roles_values_array = JSON.parse( all_roles_values );
			for ( var i in all_roles_values_array ) {
				options.push( { 'value' : i, 'text' : all_roles_values_array[i] } );
			}
		}
		return options;
	}

  // Replace old IDs with new ones
  function pewc_update_duplicated_ids( item, old_id, new_id ) {

		// Update form names to new ID
		$( item ).find('[name]').each(function() {
			var old_name = $(this).attr('name');
			var new_name = old_name.replace( old_id, new_id );
			$(this).attr('name',new_name);
		});
    $( item ).find('[data-size-count]').each(function(){
			var old_name = $(this).attr('data-size-count');
			var new_name = old_name.replace( old_id, new_id );
			$(this).attr('data-size-count',new_name);
		});
    $( item ).find('[data-item-id]').each(function(){
			var old_name = $(this).attr('data-item-id');
			var new_name = old_name.replace( old_id, new_id );
			$(this).attr('data-item-id',new_name);
		});
		// Update field IDs to new ID
		$( item ).find('.pewc-field-item').each(function(){
			if( $(this).attr('id') != undefined ) {
				var old_field_id = $(this).attr('id');
				var new_field_id = old_field_id.replace( old_id, new_id );
				$(this).attr('id',new_field_id);
			}
		});
    $( item ).find('.field-item').each(function(){
      if( $(this).attr('id') != undefined ) {
        var old_field_id = $(this).attr('id');
        var new_field_id = old_field_id.replace( old_id, new_id );
        $(this).attr('id',new_field_id);
      }
    });
		$( item ).find('.pewc-hidden-id-field').each(function(){
			if( $(this).val() ) {
				var old_field_val = $(this).val();
				var new_field_val = old_field_val.replace( old_id, new_id );
				$(this).val(new_field_val);
			}
		});
		$( item ).find('.pewc-condition-field, .pewc-condition-rule, .pewc-condition-value').each(function(){
			if( $(this).attr('id') != undefined ) {
				var old_field_id = $( this ).attr( 'id' );
				var new_field_id = old_field_id.replace( old_id, new_id );
				$( this ).attr( 'id', new_field_id );
			}
		});
    $( item ).find( '.pewc-condition-field' ).each( function() {
			if( $( this ).attr( 'data-value' ) != undefined ) {
				var old_field_val = $( this ).attr( 'data-value' );
				var new_field_val = old_field_val.replace( old_id, new_id );
				$( this ).attr( 'data-value', new_field_val );
			}
		});
    $( item ).find( '.pewc-calculation-field' ).each( function() {
      if( $(this).val() ) {
				var old_field_val = $(this).val();
				var new_field_val = old_field_val.replace( old_id, new_id );
				$(this).val(new_field_val);
			}
    });
		// Update options
		$( item ).find('option').each(function(i,v){
			var old_field_val = $(this).val();
			var new_field_val = old_field_val.replace( old_id, new_id );
			$(this).val(new_field_val);
		});

		// 3.25.4, allow other plugins to hook into this (e.g. Advanced Calculations)
		$( 'body' ).trigger( 'pewc_update_duplicated_ids', [ item, old_id, new_id ] );
	}

  $('.pewc-view-image').on('click',function(e){
		e.preventDefault();
		$(this).closest('.pewc-image-modal-wrapper').addClass('active');
	});
	$('.pewc-image-close, .pewc-image-inner').on('click',function(){
		$(this).closest('.pewc-image-modal-wrapper').removeClass('active');
	});

  $( 'body' ).on( 'click', '.pewc-is-dismissible-pewc-notice' , function() {
		$.ajax(
			ajaxurl,
			{
				type: 'POST',
				data: {
					action: 'pewc_dismiss_notification',
					option: $( this ).closest( '.notice' ).attr( 'data-option' )
				}
			}
		);
	});

  // Load add-ons in the admin through AJAX
  var load_addons = {

    init: function() {
      $( document.body ).on( 'click', 'li.pewc_options > a', this.load_addons );
    },

    load_addons: function( e ) {
      e.preventDefault();
      var addons_loaded = $( '#pewc_addons_loaded' ).val();
      if( ! addons_loaded ) {
        $( '.pewc-loading' ).show();
        $.ajax({
          type: 'POST',
  				url: ajaxurl,
  				data: {
  					action: 'pewc_load_addons',
            post_id: $( '#post_ID' ).val()
  				},
          success: function( response ) {
  					$( '#pewc_group_wrapper' ).empty().append( response );
            $( '#pewc_addons_loaded' ).val( 1 );
            $( '#pewc_group_wrapper' ).sortable({
              stop: function( e, ui ) {
                $( 'body' ).trigger( 'refresh_group_order' );
              }
            });
            $( '.field-list' ).sortable();
            $( '.pewc-field-options-wrapper' ).sortable();
  					$( 'body' ).trigger( 'pewc_addons_loaded' );
            $( 'body' ).trigger( 'update_field_names_object' );
            $( '.pewc-loading' ).hide();
  				}
        });
      }
    }

  }

  load_addons.init();

  var assign_groups = {

    init: function() {
      $( document.body ).on( 'click', '#pewc_assign_groups_to_products', this.assign );
    },

    assign: function( e ) {
      e.preventDefault();

      $( '.pewc-loading' ).show();
      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
          action: 'pewc_duplicate_and_assign',
          post_id: $( '#post_ID' ).val(),
          assign_to: $( '#pewc_assign_to_products' ).val(),
          overwrite: $( '#pewc_replace_existing_groups' ).prop( 'checked' )
        },
        success: function( response ) {
          $( '.pewc-loading' ).hide();
        }
      });
    }

  }

  assign_groups.init();

  var export_groups = {

    init: function() {
      $( document.body ).on( 'click', '.pewc-export-groups', this.export );
      $( document.body ).on( 'click', '.pewc-import-groups', this.import );
    },

    export: function( e ) {
      e.preventDefault();
      $( '.pewc-loading' ).show();
      $.ajax({
        type: 'POST',
        url: ajaxurl,
        data: {
          action: 'pewc_export_addons_to_json',
          post_id: $( '#post_ID' ).val(),
          security: $( '#pewc_export_addons' ).val()
        },
        success: function( response ) {
          var a = document.createElement( "a" );
          a.style.display = "none";
          document.body.appendChild( a );
          a.href = window.URL.createObjectURL(
            new Blob([response.data], { type: "application/json" } )
          );
          var filename = "add_ons_export_" + $( '#post_ID' ).val();
          a.setAttribute( "download", filename + ".json" );
          a.click();
          window.URL.revokeObjectURL( a.href );
          document.body.removeChild( a );
          $( '.pewc-loading' ).hide();
        },
        error: function( response ) {
          $( '.pewc-loading' ).hide();
          console.log( 'export failed' );
        }
      });
    },

    import: function( e ) {
      e.preventDefault();
      $( 'body' ).find( '#pewc_import_groups_wrapper' ).fadeIn();
      $( 'body' ).css( 'overflow-y', 'hidden' );
      var myDropzone = new Dropzone( "#pewc_import_dropzone", {
        // url: ajaxurl,
        acceptedFiles: 'application/json',
        init: function() {
          this.on( 'addedfile', function( file ) {
            var reader = new FileReader();
            reader.addEventListener( "loadend", function(event) {
              $( '.pewc-loading' ).show();
              $( 'body' ).find( '#pewc_import_groups_wrapper' ).fadeOut();
              $( 'body' ).css( 'overflow-y', 'auto' );
              $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                  action: 'pewc_import_addons_from_json',
                  post_id: $( '#post_ID' ).val(),
                  security: $( '#pewc_export_addons' ).val(),
                  groups: event.target.result
                },
                success: function( response ) {
                  console.log( response );
                  $( '#pewc_group_order' ).val( response.groups );
                  $( '.pewc-loading' ).hide();
                },
                error: function( response ) {
                  $( '.pewc-loading' ).hide();
                  console.log( 'import failed' );
                }
              });

            });
            reader.readAsText( file );
            // Send this off via AJAX to PHP
          } ),
          this.on( 'success', function( file, response ) {
            // console.log( file );
          } );
        }
      } );
    }

  }

  export_groups.init();

  /**
   * @since		3.22.0
   * @version	3.24.2
   */
  const pewc_repeatable = {

	init: function() {
		$( '.pewc-group-repeatable' ).each( function(){
			pewc_repeatable.attach_click( $(this) );
		});
	},

	attach_click: function( element ) {
		element.on( 'click', function(){
			var group_id = $( this ).data( 'group-id' );
			if ( $( '.pewc-repeatable-options-' + group_id ).hasClass( 'hidden' ) ) {
				$( '.pewc-repeatable-options-' + group_id ).removeClass( 'hidden' )
			} else {
				$( '.pewc-repeatable-options-' + group_id ).addClass( 'hidden' )
			}
		});
	},

	update_cloned_repeatable: function( clone_row, new_group_id, old_group_id=0 ) {
		$( clone_row ).find( '.pewc-group-repeatable' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][repeatable]' );
		$( clone_row ).find( '.pewc-group-repeatable' ).attr( 'data-group-id', new_group_id );
		$( clone_row ).find( '.pewc-group-repeatable-limit' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][repeatable_limit]' );
		$( clone_row ).find( '.pewc-group-repeatable-by-quantity' ).attr( 'name', '_product_extra_groups_'+ new_group_id +'[meta][repeatable_by_quantity]' );

		// 3.24.2, detect if we copied a group
		if ( old_group_id > 0 ) {
			// this is a copied group
			$( clone_row ).find( '.pewc-repeatable-limit-' + old_group_id ).addClass( 'pewc-repeatable-limit-' + new_group_id ).removeClass( 'pewc-repeatable-limit-' + old_group_id );
			$( clone_row ).find( '.pewc-repeatable-by-quantity-' + old_group_id ).addClass( 'pewc-repeatable-by-quantity-' + new_group_id ).removeClass( 'pewc-repeatable-by-quantity-' + old_group_id );
			$( clone_row ).find( '.pewc-repeatable-options-' + old_group_id ).addClass( 'pewc-repeatable-options-' + new_group_id ).removeClass( 'pewc-repeatable-options-' + old_group_id );
		} else {
			// this is a new group
			$( clone_row ).find( '.pewc-repeatable-limit' ).addClass( 'pewc-repeatable-limit-' + new_group_id ).removeClass( 'pewc-repeatable-limit' );
			$( clone_row ).find( '.pewc-repeatable-by-quantity' ).addClass( 'pewc-repeatable-by-quantity-' + new_group_id ).removeClass( 'pewc-repeatable-by-quantity' );
			$( clone_row ).find( '.pewc-repeatable-options' ).addClass( 'pewc-repeatable-options-' + new_group_id ).removeClass( 'pewc-repeatable-options' );
		}

		pewc_repeatable.attach_click( $( clone_row ).find( '.pewc-group-repeatable' ) ); // reinitialize
	}

  }

  pewc_repeatable.init();

});
