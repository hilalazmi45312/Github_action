(function($) {

	var total_updated = false;

	$( document ).ready( function() {

		// $( 'body').find( '.dz-default.dz-message span' ).text( pewc_vars.drop_files_message );
		// Init each select-box separately
		$( '.pewc-select-box' ).each( function( index, element ) {
			$( element ).ddslick({
				onSelected: function( selectedData ) {
					var original = selectedData.original[0];
					var index = selectedData.selectedIndex;
					var value = selectedData.selectedData.value;
					var box_id = $( original ).attr( 'id' );
					// Update field price
					var hidden_option_id = box_id.replace( '_select_box', '' ) + '_' + index + '_hidden';
					var price = $( '#' + hidden_option_id ).attr( 'data-option-cost' );
					var wrapper = $( '#' + box_id ).closest( '.pewc-item' )
						.attr( 'data-selected-option-price', price )
						.attr( 'data-value', value )
						.attr( 'data-field-value', value );
					var hidden_select = $( '#' + box_id + '_' + index ).closest( '.pewc-item' ).find( '.pewc-select-box-hidden' ).attr( 'data-selected-option-price', price ).val( value );
					//pewc_update_total_js(); // before 3.21.4

					var select_box_wrapper = $( 'body' ).find( '.pewc-item-select-box .dd-container' );
					$( 'body' ).find( '.dd-option label, .dd-selected label' ).each( function() {
						$( this ).next( 'small' ).addBack().wrapAll( '<div class="dd-option-wrap"/>' );
					});
					// box_id = box_id.replace( '_select_box', '' );
					box_id = $( '#' + box_id ).closest( '.pewc-item' ).attr( 'data-id' );
					// Update the field attributes
					$( 'body' ).find( '#' + box_id ).val( value ).trigger( 'change' );

					var selected_option_price = $( '#' + box_id ).find( 'option:selected' ).attr( 'data-option-cost' );
					$( wrapper ).attr( 'data-selected-option-price', selected_option_price );

					$( 'body' ).find( '#' + box_id ).trigger( 'pewc_update_select_box' );
					$( wrapper ).find( '.dd-selected-description' ).text( pewc_wc_price( selected_option_price, true ) );

					pewc_update_total_js(); // since 3.21.4, moved here so that WCFAD can also update the price of the selected option

				}
			});
		});

		// Init each color-picker separately
		$( '.pewc-color-picker-field' ).each( function ( index, element ) {
			$(element).wpColorPicker({
				defaultColor: $(element).data('color') ? $(element).data('color') : false,
				change: function( event, ui ) {
					// a callback to fire whenever the color changes to a valid color
					$( 'body' ).trigger( 'pewc_trigger_calculations' );
					$(element).trigger( 'pewc_trigger_color_picker_change' );
					$(element).closest( '.pewc-item' ).attr( 'data-field-value', ui.color.toString() ); // 3.21.4
				},
				clear: function() {
					// a callback to fire when the input is emptied or an invalid color
					$( 'body' ).trigger( 'pewc_trigger_calculations' );
					$(element).closest( '.pewc-item' ).attr( 'data-field-value', '' ); // 3.21.4
				},
				hide: !$(element).data('show'), // hide the color picker controls on load
				palettes: !!$(element).data('palettes'), // show a group of common colors beneath the square
				width: $(element).data('box-width') ? $(element).data('box-width') : 255,
				mode: 'hsv',
				type: 'full',
				slider: 'horizontal'
			});
		});

		$( 'body' ).find( '.dd-option label, .dd-selected label' ).each( function() {
			$( this ).next( 'small' ).addBack().wrapAll( '<div class="dd-option-wrap"/>' );
		});

		// 3.24.5, for input type range, so that the slider will display the default value on page load
		$( 'input.pewc-range-slider' ).each( function(){
			if ( $(this).attr( 'value' ) > 0 ) {
				$(this).trigger( 'change' );
			}
		});

		// 3.26.7, force update totals when using the quantity selector +/- buttons
		$( 'form.wp-block-add-to-cart-with-options .wc-block-components-quantity-selector' ).on( 'click', function( e ){
			$( 'body' ).trigger( 'pewc_force_update_total_js' );
		});
	});

	$('.require-depends li:first input').on('change',function() {
		// Display asterisk on dependent required fields
		if( $(this).val() != '' ) {
			$(this).closest('.pewc-group').addClass('show-required');
		} else {
			$(this).closest('.pewc-group').removeClass('show-required');
		}
	});
	$('.pewc-file-upload').on('change',function(){
		readURL( this, $(this).attr('id') );
	});
	$('.pewc-remove-image').on('click',function(e){
		e.preventDefault();
		id = $(this).attr('data-id');
		$('#'+id).val("");
		$('#'+id+'-placeholder').css('display','none');
		$('#'+id+'-placeholder img').attr('src', '#');
		$('#'+id+'-wrapper').removeClass('image-loaded');
	});
	function readURL(input,id) {
		if( input.files && input.files[0] ) {
			var i = input.files.length;
			var reader = new FileReader();
			reader.onload = function (e) {
				$('#'+id+'-wrapper').addClass('image-loaded');
				$('#'+id+'-placeholder').fadeIn();
				$('#'+id+'-placeholder img').attr('src', e.target.result);
			}
			reader.readAsDataURL(input.files[0]);
		}
	}
	$('body').on('change input keyup','.pewc-has-maxchars input, .pewc-has-maxchars textarea',function(){
		var maxchars = parseInt( $(this).attr('data-maxchars') );
		var str = $(this).val();
		var str_ex_spaces = $(this).val();
		// Don't cost spaces
		// str_ex_spaces = str_ex_spaces.replace(/\s/g, "");
		if( pewc_vars.remove_spaces != 'no' ) {
			str_ex_spaces = str.replace(/\s/g, "");
			var num_spaces = str.split( " " ).length - 1;
			maxchars += parseInt( num_spaces );
		} else {
			str_ex_spaces = str;

		}
		var str_len = str_ex_spaces.length;
		if(str_len>maxchars){
			var new_str = str_ex_spaces.substring(0, maxchars);
			$(this).val(new_str);
		}
	});
	$('body').on( 'change input', '.pewc-form-field', function() {
		$form = $(this).closest( 'form' );
		add_on_images.update_add_on_image( $( this ), $form );
		pewc_update_total_js();
		if ( 'yes' == pewc_vars.enable_character_counter ) {
			pewc_update_text_field_counter( $(this) );
		}
	});
	$('body').on( 'change input', '.pewc-range-slider', function() {
		$( this ).closest( '.pewc-item-field-wrapper' ).find( '.pewc-range-value' ).text( $( this ).val() );
		$( this ).closest( '.pewc-item' ).attr( 'data-field-value', $( this ).val() ); // 3.24.5
	});

	// this updates all text field counters, called on first load
	function pewc_update_text_field_counters() {
		$( '.pewc-form-field' ).each( function(){
			if ( $(this).closest( '.pewc-item' ).hasClass('pewc-item-text') || $(this).closest( '.pewc-item' ).hasClass('pewc-item-textarea') || $(this).closest( '.pewc-item' ).hasClass('pewc-item-advanced-preview') ) {
				pewc_update_text_field_counter( $(this) );
			}
		});
	}

	function pewc_update_text_field_counter( field ) {
		var wrapper = field.closest( '.pewc-item' );

		if ( ! wrapper.hasClass('pewc-item-text') && ! wrapper.hasClass('pewc-item-textarea') && ! wrapper.hasClass('pewc-item-advanced-preview') ) {
			return; // do nothing if not text fields
		}

		var val_length = field.val().length;
		var field_id = field.attr( 'id' ).replace( '_clone', '' ); // 3.26.11, remove _clone so that the counter inside the lightbox works
		//var current_count = $( '.pewc-text-counter-container.'+field.attr('id')+' .pewc-current-count' );
		var current_count = field.closest( '.pewc-item' ).find( '.pewc-text-counter-container.' + field_id + ' .pewc-current-count' ); // 3.22.0, so that cloned text fields' counter works correctly
		current_count.html( val_length );

		current_count.removeClass( 'error' );
		if ( parseInt( field.attr('data-minchars') ) > 0 && val_length < parseInt( field.attr('data-minchars') ) ) {
			current_count.addClass( 'error' );
		} else if ( parseInt( field.attr('data-maxchars') ) > 0 && val_length > parseInt( field.attr('data-maxchars') ) ) {
			current_count.addClass( 'error' );
		}
	}

	$('body').on('click','.pewc-remove-image',function(){
		$form = $(this).closest('form');
		// pewc_update_total( $form );
		pewc_update_total_js();
	});
	// Bind to the show_variation event
	// Update pewc-product-price field with variation price
	// 3.20.1, changed .bind to .on, .bind is deprecated as of jQuery 3.0
	$( document ).on( 'hide_variation', function( event, variation, purchasable ) {
		$('#pewc-product-price').val( 0 );
		$('#pewc-product-price-original').val( 0 ); // 3.21.7
		pewc_update_total_js();
		// hide all variation-dependent fields
		$('.pewc-variation-dependent').each(function(){
			if ($(this).hasClass( 'active' )) {
				$(this).removeClass( 'active' );
				// 3.20.1, run only if Optimised conditions is enabled
				if ( pewc_vars.conditions_timer > 0 ) {
					if( pewc_vars.reset_fields == 'yes' && ! $(this).hasClass( 'pewc-reset-me' ) ) {
						$(this).addClass( 'pewc-reset-me' );
					}
					if ( $( this ).hasClass( 'pewc-field-triggers-condition' ) ) {
						// 3.20.1, trigger the function trigger_field_condition_check() in pewc_conditions
						if ( $( this ).find( 'input' ).length > 0 ) {
							$( this ).find( 'input' ).trigger( 'change' );
						} else if ( $( this ).find( 'select' ).length > 0 ) {
							$( this ).find( 'select' ).trigger( 'change' );
						}
					}
					if ( $( this ).hasClass( 'pewc-calculation-trigger' ) ) {
						// sometimes the recalculate function is triggered too late, when the field is already hidden, so we trigger it manually
						calculations.recalculate();
					}
				}
				// 3.21.4
				$( 'body' ).trigger( 'pewc_field_visibility_updated', [ $(this).attr('data-id'), 'hide' ] );
			}
		});
		$( 'body' ).trigger( 'pewc_reset_fields' ); // 3.20.1
	});
	$( document ).on( 'show_variation', function( event, variation, purchasable ) {
		$( '#pewc_calc_set_price' ).attr( 'data-calc-set', 0 );
		pewc_update_total_js();
		var var_price = variation.display_price;
		$( '#pewc_variation_price' ).val( var_price );
		$( '#pewc-product-price' ).val( var_price );
		$( '#pewc-product-price-original' ).val( var_price ); // 3.21.7
		// Update percentage prices
		$( 'body' ).trigger( 'pewc_do_percentages' );
		// Find any select options with percentage prices - these might not have a field price
		$( this ).find( '.pewc-percentage.pewc-item-select select' ).each( function() {
			pewc_update_select_percentage_field( $( this ), var_price );
		});
		$( this ).find( '.pewc-percentage.pewc-item-select-box' ).each( function() {
			pewc_update_select_box_percentage_field( $( this ), var_price );
		});
		$( this ).find( '.pewc-percentage.pewc-item-image_swatch, .pewc-percentage.pewc-item-radio' ).each( function() {
			pewc_update_radio_percentage_field( $( this ), var_price );
		});
		// Check for variation dependent fields
		var variation_id = variation.variation_id;
		$('.pewc-variation-dependent').each(function(){
			var ids = $(this).attr('data-variations');
			ids = ids.split( ',' );
			ids = ids.map( function( x ) {
				return parseInt( x, 10 );
			});
			if( ids.indexOf( variation_id ) != -1 ) {
				$(this).addClass( 'active' );
				// 3.20.1
				if( pewc_vars.reset_fields == 'yes' && pewc_vars.conditions_timer > 0 ) {
					$(this).removeClass( 'pewc-reset-me' );
				}
				// 3.21.4
				$( 'body' ).trigger( 'pewc_field_visibility_updated', [ $(this).attr('data-id'), 'show' ] );
			} else {
				$(this).removeClass( 'active' );
				// 3.20.1
				if( pewc_vars.reset_fields == 'yes' && ! $(this).hasClass( 'pewc-reset-me' ) && pewc_vars.conditions_timer > 0 ) {
					$(this).addClass( 'pewc-reset-me' );
				}
			}
			// 3.20.1
			if ( pewc_vars.conditions_timer > 0 ) {
				if ( $( this ).hasClass( 'pewc-field-triggers-condition' ) ) {
					// trigger trigger_field_condition_check() or trigger_condition_check() in pewc_conditions
					if ( $( this ).find( 'input' ).length > 0 ) {
						$( this ).find( 'input' ).trigger( 'change' );
					} else if ( $( this ).find( 'select' ).length > 0 ) {
						$( this ).find( 'select' ).trigger( 'change' );
					}
				}
				if ( $( this ).hasClass( 'pewc-calculation-trigger' ) ) {
					// sometimes the recalculate function is triggered too late, when the field is already hidden, so we trigger it manually
					calculations.recalculate();
				}
			}
		});
		// Update product dimensions
		$( '#pewc_product_length' ).val( variation.dimensions['length'] );
		$( '#pewc_product_width' ).val( variation.dimensions['width'] );
		$( '#pewc_product_height' ).val( variation.dimensions['height'] );
		$( '#pewc_product_weight' ).val( variation.weight );
		// Trigger recalculation
		$( 'body' ).trigger( 'pewc_trigger_calculations' );
		$( 'body' ).trigger( 'pewc_variations_updated' );
		$( 'body' ).trigger( 'pewc_reset_fields' ); // 3.20.1
		pewc_update_total_js();
	});

	$( 'body' ).on( 'pewc_do_percentages', function() {
		$('.pewc-percentage').each(function() {
			var var_price = $( '#pewc-product-price' ).val();
			var new_price = ( var_price / 100 ) * parseFloat( $( this ).attr( 'data-percentage' ) );
			if( isNaN( new_price ) ) {
				new_price = 0;
			}
			$(this).attr( 'data-price', new_price );
			var new_price_html = pewc_wc_price( new_price.toFixed(pewc_vars.decimals) );
			$(this).find('.pewc-field-price').html(new_price_html);

			// 3.21.4, for checkbox fields
			if ( $(this).hasClass( 'pewc-item-checkbox' ) ) {
				var new_price_currency_only = pewc_wc_price( new_price.toFixed( pewc_vars.decimals ), true );
				$(this).find( '.pewc-checkbox-price' ).html( new_price_currency_only );
				return;
			}
			// Find any options in this field - checkbox group
			$(this).find( '.pewc-option-has-percentage' ).each(function() {
				var option_price = ( var_price / 100 ) * $(this).attr('data-option-percentage');
				$(this).attr('data-option-cost',option_price.toFixed(pewc_vars.decimals));
				option_price = pewc_wc_price( option_price.toFixed(pewc_vars.decimals) );
				$(this).closest('.pewc-checkbox-form-label').find('.pewc-option-cost-label').html(option_price);
			});
		});
	});
	$( 'body.pewc-variable-product' ).on( 'update change', '.pewc-percentage.pewc-item-select select', function( e ) {
		// Only do this on variable products
		pewc_update_select_percentage_field( $( this ), $( '#pewc_variation_price' ).val() );
	});
	// @param select is the select element itself
	function pewc_update_select_percentage_field( select, var_price ) {
		pewc_update_select_option_percentage( select, var_price );
		// Trigger recalculation
		$( 'body' ).trigger( 'pewc_trigger_calculations' );
		pewc_update_total_js();
	}
	// @param selectbox is the wrapper element
	// @param var_price the variation price
	function pewc_update_select_box_percentage_field( selectbox, var_price ) {
		var select = $( selectbox ).find( 'select' );
		// Update the hidden select field
		pewc_update_select_option_percentage( select, var_price );
		// Now update the select box options
		var box = $( selectbox ).find( '.dd-select' );
		var box_options = $( selectbox ).find( '.dd-options' );
		$( select ).find( 'option' ).each( function( i, v ) {
			var new_price = ( var_price / 100 ) * $( this ).attr( 'data-option-percentage' );
			var new_text = $( this ).val() + pewc_vars.separator + pewc_wc_price( new_price.toFixed( pewc_vars.decimals ), true );
			var option = $( box_options ).find( 'li' ).eq( i );
			$( option ).find( '.dd-option-description' ).text( new_text );
		});
		// Update the selected option price
		var selected_price = $( select ).children( 'option:selected' ).attr( 'data-option-cost' );
		selected_price = pewc_wc_price( selected_price, true );
		$( selectbox ).find( '.dd-selected .dd-selected-description' ).text( selected_price );

		// Trigger recalculation
		$( 'body' ).trigger( 'pewc_trigger_calculations' );
		pewc_update_total_js();
	}
	// Shared function for select and select box fields
	function pewc_update_select_option_percentage( select, var_price ) {
		var selected = $( select ).children( 'option:selected' );
		var option_price = ( var_price / 100 ) * $( selected ).attr( 'data-option-percentage' );
		var item = $( select ).closest( '.pewc-item' ).attr( 'data-selected-option-price', option_price );
		$( selected ).attr( 'data-option-cost', option_price.toFixed( pewc_vars.decimals ) );
		option_price = pewc_wc_price( option_price.toFixed( pewc_vars.decimals ) );
		var data_price = $( select ).closest( '.pewc-item' ).attr( 'data-price' );
		if( isNaN( data_price ) ) {
			$( select ).closest( '.pewc-item' ).attr( 'data-price', 0 );
		}
		// Update all options prices
		$( select ).find( 'option' ).each( function( i, v ) {
			var new_price = ( var_price / 100 ) * $( this ).attr( 'data-option-percentage' );
			$( this ).attr( 'data-option-cost', new_price.toFixed( pewc_vars.decimals ) );
			if ( $( select ).closest( '.pewc-item' ).hasClass( 'pewc-hide-option-price' ) || $( this ).hasClass( 'pewc-first-option' ) ) {
				return; // no need to proceed if option price is hidden for this field
			}
			var new_text = $( this ).val() + pewc_vars.separator + pewc_wc_price( new_price.toFixed( pewc_vars.decimals ), true );
			$( this ).text( new_text );
		});
	}
	// @param swatch is the swatch or radio group field wrapper
	function pewc_update_radio_percentage_field( field, var_price ) {
		// Iterate through each input element and update all options prices
		$( field ).find( 'input' ).each( function( i, v ) {
			var new_price = ( var_price / 100 ) * $( this ).attr( 'data-option-percentage' );
			$( this ).attr( 'data-option-cost', new_price.toFixed( pewc_vars.decimals ) );
			var new_text = $( this ).val() + pewc_vars.separator + pewc_wc_price( new_price.toFixed( pewc_vars.decimals ), true );
			$( this ).closest( 'label' ).next().text( new_text );
			if ( $( field ).hasClass( 'pewc-hide-option-price' ) ) {
				return; // no need to proceed if option price is hidden for this field
			}
			// 3.21.4, added pewc-item-image-swatch-checkbox
			if( ( $( field ).hasClass( 'pewc-item-radio' ) || $( field ).hasClass( 'pewc-item-image-swatch-checkbox' ) ) && $( field ).hasClass( 'pewc-item-image_swatch' ) ) {
				// 3.21.4, added .pewc-radio-image-desc to avoid duplicates
				$( this ).closest( 'label' ).find( '.pewc-radio-image-desc span' ).text( new_text );
			} else if( $( field ).hasClass( 'pewc-item-radio' ) ) {
				$( this ).closest( 'li' ).find( 'label span.pewc-radio-option-text' ).text( new_text );
			}
		});
		// Trigger recalculation
		$( 'body' ).trigger( 'pewc_trigger_calculations' );
		pewc_update_total_js();
	}
	function pewc_update_total_js( $update=0 ) {

		var flat_rate_total = 0;
		var product_price = parseFloat( $('#pewc-product-price').val() );
		var orig_product_price = parseFloat( $('#pewc-product-price-original').val() ); // 3.21.7
		var total_price = 0; // Options running total
		var orig_total_price = 0; // 3.21.7, stores add-on prices before getting adjusted by DPDR
		var added_price = 0;
		var repeatable_prices = 0; // 3.26.7, keeps track of repeatable field prices, in case we don't want them multiplied against the quantity
		var field_value = [];
		var field_label = [];

		$('form.cart .pewc-form-field').each(function() {

			added_price = 0;

			var field_wrapper = $(this).closest('.pewc-group');
			$( field_wrapper ).removeClass( 'pewc-active-field' );

			// Ignore hidden variation dependent fields
			if( ! $( field_wrapper ).hasClass( 'pewc-variation-dependent' ) || ( $( field_wrapper ).hasClass( 'pewc-variation-dependent' ) && $( field_wrapper ).hasClass( 'active' ) ) ) {

				var group_wrap = $( field_wrapper ).closest( '.pewc-group-wrap' );
				if ( group_wrap.hasClass( 'pewc-group-hidden' ) ) {
					return; // this field is inside a hidden group, so ignore and return
				}

				// Check that the extra field is not flat rate
				if( ! $(field_wrapper).hasClass('pewc-flatrate') ) {

					if( $(field_wrapper).hasClass('pewc-group-checkbox') && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {

						if( $(field_wrapper).hasClass('pewc-per-unit-pricing') && $(this).prop('checked') ) {
							// Bookings for WooCommerce
							// Multiply option cost by number of booked units
							// total_price += parseFloat( $('#num_units_int').val() ) * parseFloat( $(field_wrapper).attr('data-price') );
							added_price = parseFloat( $('#num_units_int').val() ) * parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						} else if( $(this).prop('checked') ) {
							// total_price += parseFloat( $(field_wrapper).attr('data-price') );
							added_price = parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						} else {
							$( field_wrapper ).removeClass( 'pewc-active-field' );
						}

					} else if( $(field_wrapper).hasClass('pewc-group-select' ) && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {

						// 3.24.8, added check for pewc-first-field-empty
						if ( ! $( field_wrapper ).hasClass( 'pewc-first-field-empty' ) || $(this).find(':selected').val() != '' ) {
							$( field_wrapper ).addClass( 'pewc-active-field' );
							$( field_wrapper ).attr( 'data-field-value', $(this).find(':selected').val() );

							// 'value' means use the price as value only, on calculation fields
							// 3.25.6, moved here so that Select fields that have field price, but first element is instruction only, won't get added until an option is selected
							if ( $(field_wrapper).attr( 'data-option-price-visibility' ) != 'value' ) {
								// Add cost of selected option
								added_price = parseFloat( $(this).find(':selected').attr('data-option-cost') );
								// Add cost of select field
								added_price += parseFloat( $(field_wrapper).attr('data-price') );
							}
						}

					} else if( $(field_wrapper).hasClass('pewc-group-select-box' ) && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {

						// 3.24.8, added check for pewc-first-field-empty
						if ( ! $( field_wrapper ).hasClass( 'pewc-first-field-empty' ) || $(this).find(':selected').val() != '' ) {
							$( field_wrapper ).addClass( 'pewc-active-field' );
							//$( field_wrapper ).attr( 'data-field-value', $(this).find('.pewc-select-box-hidden' ).val() ); // doesn't work as of 3.21.6
							$( field_wrapper ).attr( 'data-field-value', $(this).find( ':selected' ).val() ); // 3.21.7

							// 'value' means use the price as value only, on calculation fields
							// 3.25.6, moved here, similar to Select field above
							if ( $(field_wrapper).attr( 'data-option-price-visibility' ) != 'value' ) {
								// Add cost of selected option
								added_price = parseFloat( $( field_wrapper ).attr( 'data-selected-option-price') );
								// Add cost of select field
								added_price += parseFloat( $( field_wrapper ).attr( 'data-price' ) );
							}
						}

					} else if( $(this).val() && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {

						if( $(field_wrapper).hasClass('pewc-per-character-pricing') && ( $(field_wrapper).hasClass('pewc-item-text') || $(field_wrapper).hasClass('pewc-item-textarea') || $(field_wrapper).hasClass( 'pewc-item-advanced-preview' ) ) ) {
							var str_len = pewc_get_text_str_len( $(this).val(), field_wrapper );
							added_price = str_len * parseFloat( $(field_wrapper).attr('data-price') );
						} else if( $(field_wrapper).hasClass('pewc-multiply-pricing') ) {
							var num_value = $(this).val();
							added_price = num_value * parseFloat( $(field_wrapper).attr('data-price') );
						} else if( $(field_wrapper).hasClass('pewc-group-name_price') ) {
							added_price = parseFloat( $(this).val() );
						} else if( $(field_wrapper).hasClass('pewc-item-number' ) && $(field_wrapper).hasClass('pewc-per-unit-pricing') ) {
								// Bookings for WooCommerce
								// Multiply option cost by number of booked units
								// total_price += parseFloat( $('#num_units_int').val() ) * parseFloat( $(field_wrapper).attr('data-price') );
								added_price = parseFloat( $('#num_units_int').val() ) * parseFloat( $(field_wrapper).attr('data-price') ) * parseFloat( $( this ).val() );
						} else {
							added_price = parseFloat( $(field_wrapper).attr('data-price') );
						}

						if( $(this).val() ) {
							$( field_wrapper ).addClass( 'pewc-active-field' );

							if ( $(this).hasClass( 'pewc-range-slider' ) && $(this).attr( 'value' ) == '' ) {
								// 3.24.5, input of type range always has a value, so if this is the first load, and we don't have a user-selected value, don't set data-field-value
							} else {
								$( field_wrapper ).attr( 'data-field-value', $(this).val() );
							}
						}

					} else {

						// blank text value falls here
						$( field_wrapper ).attr( 'data-field-value', '' );

					}

					// 3.26.7, by default, add-on field prices are multiplied against the quantity. There are cases we don't want to do this for repeatable fields
					if ( $( field_wrapper ).hasClass( 'pewc-repeatable-field' ) && ! $( field_wrapper ).hasClass( 'pewc-multiply-repeatable' ) ) {
						repeatable_prices += added_price; // we don't add this field's price to the total price so that repeatable prices are not multiplied with quantity
					} else {
						total_price += added_price; // default behavior, later on total_price is multiplied against quantity
					}

					if( ! isNaN( added_price ) ) {
						$( field_wrapper ).attr( 'data-field-price', added_price );
					}

				} else {

					// Flat rate item
					if( $(field_wrapper).hasClass('pewc-group-checkbox') && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {
						if( $(field_wrapper).hasClass('pewc-per-unit-pricing') && $(this).prop('checked') ) {
							// Bookings for WooCommerce
							// Multiply option cost by number of booked units
							added_price = parseFloat( $('#num_units_int').val() ) * parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						} else if( $(this).prop('checked') ) {
							added_price = parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						}
					} else if( ( $(field_wrapper).hasClass('pewc-group-select') || $(field_wrapper).hasClass('pewc-group-select-box') ) && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {
						// Add cost of selected option
						added_price = parseFloat( $(this).find(':selected').attr('data-option-cost') );
						// Add cost of select field
						added_price += parseFloat( $(field_wrapper).attr('data-price') );
						$( field_wrapper ).addClass( 'pewc-active-field' );
					} else if( $(this).val() && ! $(field_wrapper).hasClass('pewc-hidden-field') ) {
						if( $(field_wrapper).hasClass('pewc-per-character-pricing') ) {
							var str_len = pewc_get_text_str_len( $(this).val(), field_wrapper );
							added_price = str_len * parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						} else if( $(field_wrapper).hasClass('pewc-multiply-pricing') ) {
							var num_value = $(this).val();
							added_price = num_value * parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						} else if( $(field_wrapper).hasClass('pewc-group-name_price') ) {
							added_price = parseFloat( $(this).val() );
						} else {
							added_price = parseFloat( $(field_wrapper).attr('data-price') );
							$( field_wrapper ).addClass( 'pewc-active-field' );
						}
					}

					flat_rate_total += added_price;
					$( field_wrapper ).attr( 'data-field-price', added_price );
					$( field_wrapper ).attr( 'data-field-value', $(this).val() ); // 3.24.1, for JS validation

				}

			}

			if( $( field_wrapper ).val() ) {
				set_summary_panel_data( $( field_wrapper ), $( field_wrapper ).val(), added_price );
			}

		});

		$( 'form.cart .pewc-item-radio, form.cart .pewc-item-image_swatch' ).each(function() {

			var field_value = [];

			if( ! $( this ).hasClass( 'pewc-variation-dependent' ) || ( $( this ).hasClass( 'pewc-variation-dependent' ) && $( this ).hasClass( 'active' ) ) ) {

				//var radio_group_id = $(this).attr( 'data-id' ); // 3.26.5, commented out
				var group_wrap = $( this ).closest( '.pewc-group-wrap' );

				if( ! $(this).hasClass('pewc-hidden-field') && ! group_wrap.hasClass( 'pewc-group-hidden' ) ) {

					// 3.26.5, let's use a variable
					var checked_radio = $( this ).find( 'input[type=radio]:checked' );

					// 3.26.5, moved this condition outside of the 3 if/else
					if( checked_radio.attr( 'data-option-cost' ) ) {
						if ( $(this).attr('data-option-price-visibility') == 'value' ) {

							// this option is only used for calculations, so don't add the price to the total
							var selected_option_price = checked_radio.attr( 'data-option-cost' );
							$( this ).attr( 'data-selected-option-price', selected_option_price );

						} else if( ! $(this).hasClass( 'pewc-flatrate' ) ) {

							added_price = parseFloat( $(this).attr( 'data-price' ) );
							var selected_option_price = checked_radio.attr( 'data-option-cost' );
							$( this ).attr( 'data-selected-option-price', selected_option_price );
							added_price += parseFloat( selected_option_price );

							// 3.26.7, by default, add-on field prices are multiplied against the quantity. There are cases we don't want to do this for repeatable fields
							if ( $( this ).hasClass( 'pewc-repeatable-field' ) && ! $( this ).hasClass( 'pewc-multiply-repeatable' ) ) {
								repeatable_prices += added_price; // we don't add this field's price to the total price so that repeatable prices are not multiplied with quantity
							} else {
								total_price += added_price;
							}

						} else {

							flat_rate_total += parseFloat( $(this).attr( 'data-price' ) );
							var selected_option_price = checked_radio.attr( 'data-option-cost' );
							$( this ).attr( 'data-selected-option-price', selected_option_price );
							flat_rate_total += parseFloat( selected_option_price );

						}
					}

					if( checked_radio.val() ) {
						set_summary_panel_data( $( this ), checked_radio.val(), added_price );
					} else {
						set_summary_panel_data( $( this ), '', 0 );
						$( this ).removeClass( 'pewc-active-field' ); // 3.21.4, moved here because set_summary_panel_data adds the class pewc-active-field
					}

				}

			}

		});

		$('form.cart .pewc-item-select').each(function() {
			var selected_option_price = $( this ).find( 'option:selected' ).attr( 'data-option-cost' );
			$( this ).attr( 'data-selected-option-price', selected_option_price );
		});

		$('form.cart .pewc-item-checkbox_group').each(function() {

			var field_value = [];
			var selected_counter = 0;

			if( ! $( this ).hasClass( 'pewc-variation-dependent' ) || ( $( this ).hasClass( 'pewc-variation-dependent' ) && $( this ).hasClass( 'active' ) ) ) {

				var checkbox_group_id = $(this).attr( 'data-id' );
				var checkbox_group_price = 0;
				var group_wrap = $( this ).closest( '.pewc-group-wrap' );

				if( ! $(this).hasClass('pewc-hidden-field') && ! group_wrap.hasClass( 'pewc-group-hidden' ) ) {
					var checkbox_value = [];
					if( ! $(this).hasClass('pewc-flatrate') ) {
						// Get the field price
						if( $("input[name='" + checkbox_group_id + "[]']:checked" ).val() ) {
							checkbox_group_price += parseFloat( $(this).attr('data-price') );
						}
						$('.'+checkbox_group_id).find( $('input[type=checkbox]:checked') ).each( function() {
							checkbox_group_price += parseFloat( $(this).attr('data-option-cost') );
							checkbox_value.push( $( this ).val() );
							selected_counter++;
						});
						total_price += checkbox_group_price;

						// Summary panel
						if( $("input[name='" + checkbox_group_id + "[]']:checked" ).val() ) {
							$( this ).attr( 'data-field-price', checkbox_group_price );
							$( this ).addClass( 'pewc-active-field' );
							$( this ).attr( 'data-field-value', checkbox_value.join( ', ' ) );
						}
						else {
							// remove from summary panel
							$( this ).attr( 'data-field-price', 0 );
							$( this ).removeClass( 'pewc-active-field' );
							$( this ).attr( 'data-field-value', '' );
						}

					} else {

						// Flat rate
						if( $("input[name='" + checkbox_group_id + "[]']:checked" ).val() ) {
							flat_rate_total += parseFloat( $(this).attr('data-price') );
						}
						$('.'+checkbox_group_id).find( $('input[type=checkbox]:checked') ).each( function() {
							flat_rate_total += parseFloat( $(this).attr('data-option-cost') );
							checkbox_value.push( $( this ).val() );
							selected_counter++;
						});

						if ( checkbox_value.length > 0 ) {
							$( this ).attr( 'data-field-value', checkbox_value.join( ', ' ) );
						} else {
							$( this ).attr( 'data-field-value', '' );
						}
					}
				}

			}

			pewc_update_checkbox_field_counter( $(this), selected_counter );

		});
		$('form.cart .pewc-item-image-swatch-checkbox').each(function() {

			var field_value = [];
			var selected_counter = 0;

			if( ! $( this ).hasClass( 'pewc-variation-dependent' ) || ( $( this ).hasClass( 'pewc-variation-dependent' ) && $( this ).hasClass( 'active' ) ) ) {

				var checkbox_group_id = $(this).attr( 'data-id' );
				var checkbox_group_price = 0;
				var group_wrap = $( this ).closest( '.pewc-group-wrap' );

				if( ! $(this).hasClass('pewc-hidden-field') && ! group_wrap.hasClass( 'pewc-group-hidden' ) ) {
					var field_value = [];
					if( ! $(this).hasClass('pewc-flatrate') ) {
						// Get the field price
						if( $("input[name='" + checkbox_group_id + "[]']:checked" ).val() ) {
							checkbox_group_price += parseFloat( $(this).attr('data-price') );
						}
						$('.'+checkbox_group_id).find( $('input[type=checkbox]:checked') ).each( function() {
							field_value.push( $( this ).val() );
							checkbox_group_price += parseFloat( $(this).attr('data-option-cost') );
							selected_counter++;
						});
						total_price += checkbox_group_price;
					} else {
						// Flat rate
						if( $("input[name='" + checkbox_group_id + "[]']:checked" ).val() ) {
							flat_rate_total += parseFloat( $(this).attr('data-price') );
						}
						$('.'+checkbox_group_id).find( $('input[type=checkbox]:checked') ).each( function(){
							field_value.push( $( this ).val() );
							flat_rate_total += parseFloat( $(this).attr('data-option-cost') );
							selected_counter++;
						});
					}
				}

				if( field_value.length > 0 ) {
					set_summary_panel_data( $( this ), field_value.join( ', ' ), checkbox_group_price );
				} else {
					$( this ).removeClass( 'pewc-active-field' );
					set_summary_panel_data( $( this ), '', 0 );
				}

			}

			pewc_update_checkbox_field_counter( $(this), selected_counter );

		});

		// Check child products with select layout
		var child_products_total = 0;
		$('form.cart .pewc-child-select-field').each(function() {

			var field_wrapper = $( this ).closest( '.pewc-item' );
			var field_value = [];
			var is_hidden = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-hidden-field' );
			var is_dependent = ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-variation-dependent' ) ) && ( ! $( this ).closest( '.pewc-item' ).hasClass( 'active' ) );
			if( ! is_hidden && ! is_dependent ) {
				// If the select field has a value
				if( $(this).val() && $(this).val() != '' ) {
					field_value.push( $(this).find(':selected').attr( 'data-field-value' ) );
					var selected = $(this).find(':selected');
					var child_product_price = selected.data( 'option-cost' );
					// 3.21.4, compatibility with FD
					if ( parseFloat( selected.attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( selected.attr('data-wcfad-price') );
					}
					var qty = 0;
					if( child_product_price > 0 ) {
						var wrapper = $(this).closest('.child-product-wrapper');
						var quantities = $(wrapper).data('products-quantities');
						// Get the quantity
						if( quantities == 'linked' ) {
							//qty = $('form.cart .quantity .qty').val();
							// 3.27.0
							qty = pewc_get_quantity( qty, 'linked' );
						} else if( quantities == 'independent' ) {
							// Find the child_quantity field
							qty = $(wrapper).find('.pewc-child-quantity-field').val();
							if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
								// 3.27.0
								var main_quantity = pewc_get_quantity();
								qty = qty * parseFloat( main_quantity );
							}
						} else if( quantities == 'one-only' ) {
							qty = pewc_get_quantity( 1, 'only-only' );
						}
					}

					child_products_total += parseFloat( child_product_price ) * parseFloat( qty );
					if( field_value.length > 0 ) {
						$( this ).closest( '.pewc-item' ).attr( 'data-field-price', child_products_total );
						set_summary_panel_data( $( this ).closest( '.pewc-item' ), field_value.join( ', ' ), parseFloat( child_product_price ) * parseFloat( qty ) );
					} else {
						$( this ).closest( '.pewc-item' ).removeClass( 'pewc-active-field' );
					}

				}
			}
		});
		$('form.cart .pewc-radio-images-wrapper.child-product-wrapper').each(function() {
			var field_value = [];
			var is_hidden = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-hidden-field' );
			var is_dependent = ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-variation-dependent' ) ) && ( ! $( this ).closest( '.pewc-item' ).hasClass( 'active' ) );
			if( ! is_hidden && ! is_dependent ) {
				var quantities = $(this).data('products-quantities');
				var checked_radio = $(this).find('.pewc-radio-form-field:checked');
				var radio_val = checked_radio.val();
				if( radio_val && radio_val != undefined ) {
					var child_product_price = checked_radio.data('option-cost');
					// 3.21.4, compatibility with FD
					if ( parseFloat( checked_radio.attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( checked_radio.attr('data-wcfad-price') );
					}
					var qty = 0;
					// sometimes child_product_price is zero if it's discounted

					// Get the quantity
					if( quantities == 'linked' ) {
						//qty = $('form.cart .quantity .qty').val();
						// 3.27.0
						qty = pewc_get_quantity( qty, 'linked' );
					} else if( quantities == 'independent' ) {
						// Find the child_quantity field
						qty = $(this).closest('.pewc-item-field-wrapper').find('.pewc-child-quantity-field').val();
						if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
							// 3.27.0
							var main_quantity = pewc_get_quantity();
							qty = qty * parseFloat( main_quantity );
						}
					} else if( quantities == 'one-only' ) {
						qty = pewc_get_quantity( 1, 'only-only' );
					}

					child_products_total += parseFloat( child_product_price ) * parseFloat( qty );

					if( child_products_total > 0 || qty > 0 ) {
						$( this ).closest( '.pewc-item' ).attr( 'data-field-price', child_products_total );
						set_summary_panel_data( $( this ).closest( '.pewc-item' ), checked_radio.attr( 'data-field-label' ), parseFloat( child_product_price ) * parseFloat( qty ) );
					}

				} else {
					$( this ).closest( '.pewc-item' ).removeClass( 'pewc-active-field' );
					$( this ).closest( '.pewc-item' )
						.attr( 'data-field-price', 0 )
						.attr( 'data-field-value', '' ); // 3.21.4, reset price and value
				}

			}
		});

		// 3.25.3, added form.cart .pewc-components-wrapper.child-product-wrapper
		$( 'form.cart .pewc-checkboxes-images-wrapper.child-product-wrapper, form.cart .pewc-components-wrapper.child-product-wrapper' ).each(function() {

			var this_child_total = 0;
			var field_value = [];
			var selected_counter = 0;
			var is_hidden = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-hidden-field' );
			var is_dependent = ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-variation-dependent' ) ) && ( ! $( this ).closest( '.pewc-item' ).hasClass( 'active' ) );

			if( ! is_hidden && ! is_dependent ) {
				var quantities = $(this).data('products-quantities');
				// Run through each selected checkbox

				$( this ).closest( '.pewc-item' ).removeClass( 'pewc-active-field' );

				$( this ).find('.pewc-checkbox-form-field:checkbox:checked').each(function() {
					field_value.push( $( this ).attr( 'data-field-label' ) );
					var child_product_price = $(this).data('option-cost');
					// 3.21.4, compatibility with FD
					if ( parseFloat( $(this).attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( $(this).attr('data-wcfad-price') );
					}
					var qty = 0;

					// Get the quantity
					if( quantities == 'linked' ) {
						//qty = $('form.cart .quantity .qty').val();
						// 3.27.0
						qty = pewc_get_quantity( qty, 'linked' );
						selected_counter++;
					} else if( quantities == 'independent' ) {
						qty = $(this).closest('.pewc-checkbox-wrapper').find('.pewc-child-quantity-field').val();
						selected_counter += parseInt( qty );
						if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
							// 3.27.0
							var main_quantity = pewc_get_quantity();
							qty = qty * parseFloat( main_quantity );
						}
					} else if( quantities == 'one-only' ) {
						qty = pewc_get_quantity( 1, 'only-only' );
						selected_counter++;
					}

					if( child_product_price > 0 ) {
						child_products_total += parseFloat( child_product_price ) * parseFloat( qty );
						this_child_total += parseFloat( child_product_price ) * parseFloat( qty );
					}

				});

				if( field_value.length > 0 ) {
					$( this ).closest( '.pewc-item' ).attr( 'data-field-price', child_products_total );
					set_summary_panel_data( $( this ).closest( '.pewc-item' ), field_value.join( ', ' ), this_child_total );
				}

			}

			pewc_update_checkbox_field_counter( $( this ).closest( '.pewc-item' ), selected_counter );

		});

		// reset products column first
		$('form.cart .pewc-item-products-column').each(function(){
			$(this).removeClass('pewc-active-field');
			$(this).attr('data-field-price', 0);
			$(this).attr('data-field-value', '');
			pewc_update_checkbox_field_counter( $(this), 0 );
		});
		$('form.cart .pewc-column-wrapper .pewc-variable-child-product-wrapper.checked').each(function() {
			var field_value = [];
			var field_wrapper = $( this ).closest( '.pewc-item' );
			var parent_field_value = field_wrapper.attr('data-field-value');
			if ( parent_field_value != 0 && parent_field_value != '') {
				field_value = parent_field_value.split(', ');
			}
			var parent_field_price = parseFloat( field_wrapper.attr('data-field-price') );
			var selected_counter = parseInt ( field_wrapper.attr( 'data-field-selected-counter' ) );
			var is_hidden = field_wrapper.hasClass( 'pewc-hidden-field' );
			var is_dependent = ( field_wrapper.hasClass( 'pewc-variation-dependent' ) ) && ( ! field_wrapper.hasClass( 'active' ) );

			if( ! is_hidden && ! is_dependent ) {
				var quantities = $(this).closest( '.pewc-column-wrapper' ).data('products-quantities');
				// Run through each selected checkbox for variable child products

				field_wrapper.removeClass( 'pewc-active-field' );

				$(this).find('.pewc-variable-child-select').each(function() {
					field_value.push( $( this ).find('option:selected').text() );
					var child_product_price = $(this).find(':selected').data('option-cost');
					// 3.21.4, compatibility with FD
					if ( parseFloat( $(this).find(':selected').attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( $(this).find(':selected').attr('data-wcfad-price') );
					}
					var qty = 0;

					// Get the quantity
					if( quantities == 'linked' ) {
						//qty = $('form.cart .quantity .qty').val();
						// 3.27.0
						qty = pewc_get_quantity( qty, 'linked' );
						selected_counter++;
					} else if( quantities == 'independent' ) {
						qty = $(this).closest('.pewc-checkbox-image-wrapper').find('.pewc-child-quantity-field').val();
						selected_counter += parseInt( qty );
						if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
							// 3.27.0
							var main_quantity = pewc_get_quantity();
							qty = qty * parseFloat( main_quantity );
						}
					} else if( quantities == 'one-only' ) {
						qty = pewc_get_quantity( 1, 'only-only' );
						selected_counter++;
					}

					if( child_product_price > 0 ) {
						child_products_total += parseFloat( child_product_price ) * parseFloat( qty );
						parent_field_price += parseFloat( child_product_price ) * parseFloat( qty );
					}

					if( field_value.length > 0 ) {
						field_wrapper.attr( 'data-field-price', parent_field_price );
						set_summary_panel_data( field_wrapper, field_value.join( ', ' ), parent_field_price );
					}

				});

			}

			pewc_update_checkbox_field_counter( field_wrapper, selected_counter );

		});
		$('form.cart .pewc-column-wrapper .pewc-simple-child-product-wrapper.checked').each(function() {
			var field_value = [];
			var field_wrapper = $( this ).closest( '.pewc-item' );
			var parent_field_value = field_wrapper.attr('data-field-value');
			if ( parent_field_value != 0 && parent_field_value != '') {
				field_value = parent_field_value.split(', ');
			}
			var parent_field_price = parseFloat( field_wrapper.attr('data-field-price') );
			var selected_counter = parseInt ( field_wrapper.attr( 'data-field-selected-counter' ) );
			var is_hidden = field_wrapper.hasClass( 'pewc-hidden-field' );
			var is_dependent = ( field_wrapper.hasClass( 'pewc-variation-dependent' ) ) && ( ! field_wrapper.hasClass( 'active' ) );

			if( ! is_hidden && ! is_dependent ) {
				var quantities = $(this).closest( '.pewc-column-wrapper' ).data('products-quantities');

				field_wrapper.removeClass( 'pewc-active-field' );

				// Run through each selected checkbox
				$(this).find('.pewc-checkbox-form-field').each(function(){
					field_value.push( $( this ).attr( 'data-field-label' ) );
					var child_product_price = $(this).data('option-cost');
					// 3.21.4, compatibility with FD
					if ( parseFloat( $(this).attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( $(this).attr('data-wcfad-price') );
					}
					var qty = 0;

					// Get the quantity
					if( quantities == 'linked' ) {
						//qty = $('form.cart .quantity .qty').val();
						// 3.27.0
						qty = pewc_get_quantity( qty, 'linked' );
						selected_counter++;
					} else if( quantities == 'independent' ) {
						qty = $(this).closest('.pewc-simple-child-product-wrapper').find('.pewc-child-quantity-field').val();
						selected_counter += parseInt( qty );
						if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
							// 3.27.0
							var main_quantity = pewc_get_quantity();
							qty = qty * parseFloat( main_quantity );
						}
					} else if( quantities == 'one-only' ) {
						qty = pewc_get_quantity( 1, 'only-only' );
						selected_counter++;
					}

					if( child_product_price > 0 ) {
						child_products_total += parseFloat( child_product_price ) * parseFloat( qty );
						parent_field_price += parseFloat( child_product_price ) * parseFloat( qty );
					}

					if( field_value.length > 0 ) {
						field_wrapper.attr( 'data-field-price', parent_field_price );
						set_summary_panel_data( field_wrapper, field_value.join( ', ' ), parent_field_price );
					}

				});

			}

			pewc_update_checkbox_field_counter( field_wrapper, selected_counter );

		});

		// reset products swatches first
		$('form.cart .pewc-item-products-swatches').each(function(){
			$(this).removeClass('pewc-active-field');
			$(this).attr('data-field-price', 0);
			$(this).attr('data-field-value', '');
		});
		$('form.cart .pewc-swatches-wrapper .pewc-child-variation-main').each(function() {
			var field_value = [];
			var parent_field_value = $( this ).closest( '.pewc-item' ).attr('data-field-value');
			if ( parent_field_value != 0 && parent_field_value != '') {
				field_value = parent_field_value.split(', ');
			}
			var parent_field_price = parseFloat( $( this ).closest( '.pewc-item' ).attr('data-field-price') );
			var is_hidden = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-hidden-field' );
			var is_dependent = ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-variation-dependent' ) ) && ( ! $( this ).closest( '.pewc-item' ).hasClass( 'active' ) );
			if( ! is_hidden && ! is_dependent ) {

				var quantities = $(this).closest( '.pewc-swatches-wrapper' ).data('products-quantities');

				// Run through each selected variation product
				$(this).find('.pewc-child-name input').each(function() {
					var child_product_price = parseFloat( $(this).attr( 'data-option-cost' ) );
					// 3.21.4, compatibility with FD
					if ( parseFloat( $(this).attr('data-wcfad-price') ) > 0 ) {
						child_product_price = parseFloat( $(this).attr('data-wcfad-price') );
					}
					var qty = 0;
					if( child_product_price > 0 && $(this).is( ':checked' ) ) {
						// Get the quantity
						// 3.21.4, added is :checked, only consider selected child product
						if( quantities == 'linked' ) {
							//qty = $('form.cart .quantity .qty').val();
							// 3.27.0
							qty = pewc_get_quantity( qty, 'linked' );
						} else if( quantities == 'independent' ) {
							qty = $(this).closest('.pewc-child-variation-main').find('.pewc-child-quantity-field').val();
							if ( pewc_vars.multiply_independent == 'yes' /*&& $('form.cart .quantity .qty').val() !== undefined*/ ) {
								// 3.27.0
								var main_quantity = pewc_get_quantity();
								qty = qty * parseFloat( main_quantity );
							}
						} else if( quantities == 'one-only' ) {
							qty = pewc_get_quantity( 1, 'only-only' );
						}
					}
					if ( qty < 1 ) {
						return; // quantity is zero, so ignore this field
					}
					field_value.push( $( this ).attr( 'data-field-label' ) );
					child_products_total += parseFloat( child_product_price ) * parseFloat( qty );
					parent_field_price += parseFloat( child_product_price ) * parseFloat( qty );

					if( field_value.length > 0 ) {
						$( this ).closest( '.pewc-item' ).attr( 'data-field-price', parent_field_price );
						set_summary_panel_data( $( this ).closest( '.pewc-item' ), field_value.join( ', ' ), parent_field_price );
					}
				});

			}

		});
		$('form.cart .grid-layout').each( function() {

			var is_hidden = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-hidden-field' );
			var is_hidden_group = $( this ).closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden' );
			var is_dependent = ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-variation-dependent' ) ) && ( ! $( this ).closest( '.pewc-item' ).hasClass( 'active' ) );
			if( ! is_hidden && ! is_hidden_group && ! is_dependent ) {
				var child_product_price = parseFloat( $( this ).closest( '.pewc-item' ).attr( 'data-price' ) );
				child_products_total += parseFloat( child_product_price );
			}

		});

		if( product_price < 0 ) product_price = 0;

		// Summary panel rows

		// Summary panel for add-on groups
		$( '.pewc-summary-panel-group-row' ).addClass( 'pewc-summary-panel-field-row-inactive' );

		$( '.pewc-group-wrap' ).not( '.pewc-group-hidden' ).each( function() {
			var group_id = $( this ).attr( 'data-group-id' );
			$( '.pewc-summary-panel-group-'+group_id ).removeClass( 'pewc-summary-panel-field-row-inactive' );
		});

		// Summary panel for add-on fields
		$( '.pewc-summary-panel-field-row' ).addClass( 'pewc-summary-panel-field-row-inactive' );

		// 3.26.14, added .pewc-hidden-calculation to the not() selector so that hidden Calculation fields are not displayed on the Summary panel
		$( '.pewc-active-field' ).not( '.pewc-hidden-field, .pewc-hidden-calculation' ).each( function() {
			var field_id = $( this ).attr( 'data-field-id' );
			var field_type = $( '.pewc-field-' + field_id ).attr( 'data-field-type' );
			$( '#pewc-summary-row-' + field_id ).removeClass( 'pewc-summary-panel-field-row-inactive' ).addClass( 'pewc-field-' + field_type );
			var field_price = parseFloat( $( this ).attr( 'data-field-price' ) );
			if( field_price ) {
				field_price = field_price.toFixed( pewc_vars.decimals );
			}

			var field_label = $( this ).attr( 'data-field-label' );
			var field_value;
			if( $( this ).attr( 'data-field-type') != 'checkbox' ) {
				field_value = $( this ).attr( 'data-field-value' );
			}
			$( '#pewc-summary-row-' + field_id ).find( '.pewc-summary-panel-product-name' ).text( field_label );
			$( '#pewc-summary-row-' + field_id ).find( '.pewc-summary-panel-product-value' ).text( field_value );
			$( '#pewc-summary-row-' + field_id ).find( '.pewc-summary-panel-price' ).html( pewc_wc_price( field_price ) );
		});

		var qty = 1;
		// 3.27.0, we use 'linked' so that add-ons are multiplied per day and per num_bookings (quantity)?
		qty = pewc_get_quantity( qty, 'linked' );

		// See if we need to apply a discount to the add-ons care of Dynamic Pricing and Discount Rules
		// Updated on 3.21.4
		orig_total_price = total_price; // 3.21.7, for DPDR
		if ( pewc_wcfad_apply_discount() ) {
			total_price = pewc_wcfad.adjust_price( total_price );
			if ( repeatable_prices > 0 ) {
				repeatable_prices = pewc_wcfad.adjust_price( repeatable_prices ); // 3.26.7
			}
		}

		// Summary panel subtotal (3.9.8)
		var subtotal = parseFloat( child_products_total ) + parseFloat( total_price ) + parseFloat( qty * product_price );
		if( ! isNaN( subtotal ) ) {
			$( '#pewc-summary-panel-subtotal' ).html( pewc_wc_price( subtotal.toFixed(pewc_vars.decimals) ) );
		}

		var total_grid_variations = $( '#pewc-grid-total-variations' ).val();

		// 3.27.0, for Booking products, product_price already includes "qty" (num_units), no need to multiply again
		if ( ! pewc_is_booking_product() ) {
			var product_price = qty * product_price;
		}
		var grand_total = product_price;
		var orig_grand_total = qty * orig_product_price; // 3.21.7, for DPDR
		base_price = product_price;
		product_price = product_price.toFixed(pewc_vars.decimals);
		product_price = pewc_wc_price( product_price );
		product_price = add_price_suffix( product_price, base_price );
		$('#pewc-per-product-total').html( product_price );

		total_price = qty * total_price;
		orig_total_price = qty * orig_total_price; // 3.21.7, for DPDR

		// 3.26.7, we have a separate variable for repeatable field prices in case we dont want them multiplied with quantity
		if ( repeatable_prices > 0 ) {
			total_price += repeatable_prices;
			orig_total_price += repeatable_prices;
		}

		// Multiply add-ons by number of variations in bulk grid
		// 2.7.1, added > 0 to the condition so that option total doesn't become 0 if no variations are selected
		if( total_grid_variations > 0 ) {
			total_price = total_grid_variations * total_price;
		}
		total_price += child_products_total;
		grand_total += total_price;
		orig_grand_total += orig_total_price; // 3.21.7, for DPDR

		if( ! isNaN( total_price ) ) {
			var base_total_price = total_price;
			total_price = total_price.toFixed(pewc_vars.decimals);
			total_price = pewc_wc_price( total_price );
			total_price = add_price_suffix( total_price, base_total_price );
			$('#pewc-options-total').html( total_price );
		}

		if( flat_rate_total < 0 ) flat_rate_total = 0;
		base_flat_rate_total = flat_rate_total;
		grand_total += flat_rate_total;
		flat_rate_total = flat_rate_total.toFixed(pewc_vars.decimals);
		flat_rate_total = pewc_wc_price( flat_rate_total );
		flat_rate_total = add_price_suffix( flat_rate_total, base_flat_rate_total );
		$('#pewc-flat-rate-total').html( flat_rate_total );

		// Set the product price using a calculation field
		// 3.13.3 added 2nd condition to apply only calculated price if at least 1 calculation field is not hidden
		if( ( $( '#pewc_calc_set_price').attr( 'data-calc-set' ) == 1 || $('input[name="pewc_calc_set_price"]').length > 1 ) && $( '.pewc-product-extra-groups-wrap .pewc-item-calculation' ).not( '.pewc-hidden-field' ).length > 0 ) {
			if ( $('input[name="pewc_calc_set_price"]').length > 1 ) {
				// 3.17.2, compatibility with Product Table Ultimate
				// find the first non-hidden total
				var new_grand_total = 0;
				$('input[name="pewc_calc_set_price"]').each(function(){
					if ( $(this).attr( 'data-calc-set' ) == 1 && $(this).closest( '.pewc-product-extra-groups-wrap' ).is( ':visible' ) ) {
						new_grand_total = parseFloat( $(this).closest( '.pewc-product-extra-groups-wrap' ).find('input[name="pewc_calc_set_price"][data-calc-set="1"]').val() );
					}
				});
				if ( new_grand_total > 0 ) {
					grand_total = new_grand_total;
				}
			} else {
				// single product page
				grand_total = parseFloat( $( '#pewc_calc_set_price').val() );
			}

			if ( $('form.cart .quantity .qty').length > 0 ) {

				// 3.12.0, apply dynamic pricing if it exists
				if ( pewc_wcfad_apply_discount() ) {
					grand_total = pewc_wcfad.adjust_price( grand_total );
				}

				// 3.11.6 multiply product price by quantity
				grand_total = grand_total * parseFloat( $('form.cart .quantity .qty').val() );

			}
		}

		if( ! isNaN( grand_total ) ) {
			if ( isNaN( orig_grand_total ) || orig_grand_total == grand_total ) {
				orig_grand_total = ''; // 3.21.7, let's clear this so that we know we don't need it
			}
			var base_price = grand_total;
			grand_total = grand_total.toFixed( pewc_vars.decimals );
			grand_total = pewc_wc_price( grand_total );
			if( pewc_vars.update_price == 'yes' ) {
				update_product_price( grand_total, base_price, orig_grand_total );
			}
			grand_total = add_price_suffix( grand_total, base_price );
			$('#pewc-grand-total').html( grand_total );
			if ( $( '#pewc-grand-total_clone' ).length > 0 ) {
				// 3.26.11, also update the grand total inside the lightbox
				$( '#pewc-grand-total_clone' ).html( grand_total );
			}
		}

		// Re-run this because some browsers are too quick
		// This won't run if we're using the optimised condition option
		if( $update == 0 && pewc_vars.conditions_timer < 1 ) {
			var interval = setTimeout( function() {
				pewc_update_total_js( 1 );
				if( ! total_updated ) {
					// Check any calculations before input fields are updated
					$( 'body' ).trigger( 'pewc_trigger_calculations' );
					total_updated = true;
				}
			},
			250 );
		}

		// 3.15.0, let other plugins hook into this
		$( 'body' ).trigger( 'pewc_after_update_total_js', [ base_total_price, base_price, grand_total ] );
	}

	/*
	 * Counts selected checkboxes, swatches, or products
	 */
	function pewc_update_checkbox_field_counter( field_wrapper, counter ) {

		var current_count = field_wrapper.attr( 'data-field-selected-counter' );

		// hopefully this prevents this process from being run multiple times
		if ( current_count != counter ) {
			field_wrapper.attr( 'data-field-selected-counter', counter );
			// Optimised validation will hook into the trigger below
			$( 'body' ).trigger( 'pewc_field_selected_counter_updated', [ field_wrapper, counter ] );
		}
	}

	function add_price_suffix( price, base_price ) {
		if( pewc_vars.show_suffix == 'yes' ) {
			var price_suffix_setting = pewc_vars.price_suffix_setting;
			if( price_suffix_setting.indexOf( '{price_excluding_tax}' ) > -1 ) {
				var price_ex_tax = base_price * ( pewc_vars.percent_exc_tax / 100 );
				suffix = price_suffix_setting.replace( '{price_excluding_tax}', pewc_wc_price( price_ex_tax.toFixed( pewc_vars.decimals ), false, false ) );
			} else if( price_suffix_setting.indexOf( '{price_including_tax}' ) > -1 ) {
				var price_inc_tax = base_price * ( pewc_vars.percent_inc_tax / 100 );
				suffix = price_suffix_setting.replace( '{price_including_tax}', pewc_wc_price( price_inc_tax.toFixed( pewc_vars.decimals ), false, false ) );
			} else {
				suffix = pewc_vars.price_suffix;
			}
			price = price + ' <small class="woocommerce-price-suffix">' + suffix + '</small>';
		}
		return price;
	}

	function update_product_price( grand_total, base_price, orig_grand_total ) {
		var price_suffix_setting = pewc_vars.price_suffix_setting;
		// We can rebuild him
		var suffix = $( '.pewc-main-price' ).find( '.woocommerce-price-suffix' ).html();
		var label = $( '.pewc-main-price' ).find( '.wcfad-rule-label' ).html();
		var before = $( '.pewc-main-price' ).find( '.pewc-label-before' ).html();
		var after = $( '.pewc-main-price' ).find( '.pewc-label-after' ).html();
		var hide = $( '.pewc-main-price' ).find( '.pewc-label-hidden' );
		var new_total = '';

		if( hide.length > 0 ) {
			new_total = '<span class="pewc-label-hidden">' + hide.html() + '</span>';
		} else {
			if( before ) {
				new_total += '<span class="pewc-label-before">' + before + ' </span>';
			}

			// 3.21.7
			if ( orig_grand_total != '' && pewc_vars.disable_wcfad_label != 'yes' ) {
				orig_grand_total.toFixed( pewc_vars.decimals );
				orig_grand_total = pewc_wc_price( orig_grand_total );
				new_total += ' <s>' + orig_grand_total + '</s> ';
			}

			new_total += grand_total;

			if( after ) {
				new_total += '<span class="pewc-label-after"> ' + after + '</span>';
			}
			if( suffix ) {
				if( price_suffix_setting.indexOf( '{price_excluding_tax}' ) > -1 ) {
					var price_ex_tax = base_price * ( pewc_vars.percent_exc_tax / 100 );
					suffix = price_suffix_setting.replace( '{price_excluding_tax}', pewc_wc_price( price_ex_tax.toFixed( pewc_vars.decimals ), false, false ) );
				} else if( price_suffix_setting.indexOf( '{price_including_tax}' ) > -1 ) {
					var price_inc_tax = base_price * ( pewc_vars.percent_inc_tax / 100 );
					suffix = price_suffix_setting.replace( '{price_including_tax}', pewc_wc_price( price_inc_tax.toFixed( pewc_vars.decimals ), false, false ) );
				}
				if( label && suffix.indexOf( 'wcfad-rule-label' ) < 0 ) {
					suffix += '<br><span class="wcfad-rule-label">' + label + '</span>';
				}
				new_total += '&nbsp;<small class="woocommerce-price-suffix">' + suffix + '</small>';
			}
		}

		// 3.22.1, retain DPDR label even if Update pricing label is enabled
		var main_price = $( '.pewc-main-price').not( '.pewc-quickview-product-wrapper .pewc-main-price' );
		if ( main_price.find( '.wcfad-rule-label' ).length > 0 && new_total.indexOf( 'wcfad-rule-label' ) == -1 ) {
			// this has a DPDR discount label, add it to the new_total
			var dpdr_label = main_price.find( '.wcfad-rule-label' );
			new_total += '<br>' + dpdr_label[0].outerHTML;
		}
		main_price.html( new_total );
	}

	function set_summary_panel_data( field, value, added_price ) {
		$( field ).attr( 'data-field-value', value );
		$( field ).attr( 'data-field-price', added_price );
		$( field ).addClass( 'pewc-active-field' );
	}

	// old position of pewc_wc_price_without_currency and pewc_wc_price, moved outside of this container since 3.21.4

	function format_separator( num ) {
	  return num.toString().replace( /(\d)(?=(\d{3})+(?!\d))/g, '$1,' );
	}
	// var interval = setInterval(function(){
	// 	pewc_update_total_js();
	// },
	// 500);

	// 3.24.7, add a trigger event so that other plugins like PTU can re-trigger the form.cart input changes
	$( 'body' ).on( 'pewc_attach_form_cart_input_change', function() {
		$( 'form.cart' ).on('keyup input change paste', 'input:not(.pewc-grid-quantity-field), select, textarea.pewc-has-field-price', function(){
			pewc_update_total_js();
			$( 'body' ).trigger( 'pewc_updated_total_js' );
		});
	});
	$( 'body' ).trigger( 'pewc_attach_form_cart_input_change' );

	var interval = setTimeout( function() {
		pewc_update_total_js();
		if ( 'yes' == pewc_vars.enable_character_counter ) {
			pewc_update_text_field_counters();
		}
	},
	250 );
	$( 'body' ).on( 'pewc_add_button_clicked', function() {
		pewc_update_total_js();
	});
	$( 'body' ).on( 'pewc_force_update_total_js', function() {
		pewc_update_total_js();
	});
	// Accordion and tabs
	$('.pewc-groups-accordion h3').on('click',function(e){
		// 3.13.7
		var is_open = $( this ).closest( '.pewc-group-wrap' ).hasClass( 'group-active' );

		if ( $(this).closest( '.pewc-group-wrap' ).hasClass( 'pewc-disabled-group') ) {
			return;
		}
		if( pewc_vars.close_accordion == 'yes' ) {
			$( '.pewc-group-wrap' ).removeClass( 'group-active' );
		}
		// Don't reopen group if we've just closed it, only if close_accordion snippet is active
		if( ! is_open || pewc_vars.close_accordion != 'yes' ) {
			$(this).closest('.pewc-group-wrap').toggleClass('group-active');
		}
	});
	if( pewc_vars.accordion_toggle == 'open' ) {
		$('.pewc-group-wrap').addClass('group-active');
	} else if( pewc_vars.accordion_toggle == 'closed' ) {
		$('.pewc-group-wrap').removeClass('group-active');
	} else {
		$('.first-group').addClass('group-active');
	}

	$('.pewc-tab').on('click',function(e) {
		// 3.13.7
		if ( $(this).hasClass( 'pewc-disabled-group') ) {
			return;
		}

		e.preventDefault();
		var tab_id = $(this).attr('data-group-id');
		$('.pewc-tab').removeClass('active-tab tab-failed');
		$( '.pewc-group-js-validation-notice' ).hide();
		$(this).addClass('active-tab');
		$('.pewc-group-wrap').removeClass('group-active');
		$('.pewc-group-wrap-'+tab_id).addClass('group-active');
	});
	$('.pewc-next-step-button').on('click',function(e) {
		e.preventDefault();
		// 3.13.7
		if ( $(this).hasClass( 'pewc-disabled-group') ) {
			return;
		}

		var tab_id = $( this ).attr( 'data-group-id' );
		var tab_index = $( this ).closest( '.pewc-group-wrap' ).attr( 'data-group-index' );
		var direction = $( this ).attr( 'data-direction' );
		$( '.pewc-tab' ).removeClass( 'active-tab tab-failed' );
		$( '.pewc-group-js-validation-notice' ).hide();
		// Check if the next group is visible
		if( $( '.pewc-group-wrap-' + tab_id ).hasClass( 'pewc-group-hidden' ) ) {
			// Count the total tabs
			var total_tabs = $( '.pewc-group-wrap' ).length;
			if( direction == 'next' ) {
				// Find the next group ID
				for( var i = parseInt( tab_index ) + 1; i < total_tabs; i++ ) {
					if( ! $( '.pewc-group-index-' + i ).hasClass( 'pewc-group-hidden' ) ) {
						tab_id = $( '.pewc-group-index-' + i ).attr( 'data-group-id' );
						break;
					}
				}
			} else {
				// Find the previous group ID
				for( var i = parseInt( tab_index ) - 1; i >= 0; i-- ) {
					if( ! $( '.pewc-group-index-' + i ).hasClass( 'pewc-group-hidden' ) ) {
						tab_id = $( '.pewc-group-index-' + i ).attr( 'data-group-id' );
						break;
					}
				}
			}
		}
		$( '#pewc-tab-' + tab_id ).addClass( 'active-tab' );
		$( '.pewc-group-wrap' ).removeClass( 'group-active' );
		$( '.pewc-group-wrap-' + tab_id ).addClass( 'group-active' );

		// Scroll to top
		if ( pewc_vars.steps_group_disable_scroll_to_top != 'yes' ) {
			if( $( '.pewc-steps-wrapper' ).length > 0 ) {
				$([document.documentElement, document.body]).animate({
					scrollTop: $( '.pewc-steps-wrapper' ).offset().top
	    		}, 150);
			}
		}
	});

	function pewc_get_text_str_len( str, wrapper ) {
		var new_str, str_ex_spaces ;
		var field = $(wrapper).find('.pewc-form-field');
		// Don't include spaces
		if( pewc_vars.remove_spaces != 'yes' ) {
			str_ex_spaces = str.replace(/\s/g, "");
		} else {
			str_ex_spaces = str;
		}

		var str_len = str_ex_spaces.length;
		// Exclude alphanumerics if selected
		if( $(field).attr('data-alphanumeric') == 1 ) {
			str_ex_spaces = str_ex_spaces.replace(/\W/g, '');
			$(field).val(str_ex_spaces);
			str_len = str_ex_spaces.length;
		}
		// Allow alphanumerics but don't charge if selected
		if( $(field).attr('data-alphanumeric-charge') == 1 ) {
			str_ex_spaces = str_ex_spaces.replace(/\W/g, '');
			// $(field).val(str_ex_spaces);
			str_len = str_ex_spaces.length;
		}
		// If free characters are allowed
		var freechars = $(field).attr('data-freechars');
		str_len -= freechars;
		str_len = Math.max(0,str_len);
		return str_len;
	}

	function pewc_disable_child_product_quantities() {
		$( '.woocommerce-cart-form__cart-item.pewc-child-product' ).each( function() {
			if( pewc_vars.disable_qty || pewc_vars.multiply_independent == 'yes' ) {
				$(this).find('.product-quantity input').attr('disabled',true);
			}
		});
	}

	// Disable quantity fields in Barn2 Fast Cart
	$('body').on('click', 'a.wfc-open-cart-button', function( e ) {
		var timeout = setTimeout(
			function() {
				$( '.wfc-cart-form__cart-item.pewc-child-product' ).each( function() {
					if( pewc_vars.disable_qty || pewc_vars.multiply_independent == 'yes' ) {
						$(this).find('.product-quantity input').attr('disabled',true);
					}
				});
			},
			250
		);
	} );

	// only do this if there are actually child products
	if ( $('.woocommerce-cart-form__cart-item.pewc-child-product').length > 0 ) {
		// call function on page load
		pewc_disable_child_product_quantities();
		// when cart is updated, disable quantities again
		$('body').on('updated_cart_totals', pewc_disable_child_product_quantities);
	}

	// If child product is selected, manage allowable purchase quantity
	// Applies to radio and select
	$('body').on('change','.products-quantities-independent .pewc-child-select-field',function(){
		var number_field = $(this).closest('.child-product-wrapper').find('.pewc-child-quantity-field');
		if ( $( this ).val() != '') {
			// select field is not blank, update quantity
			if( $(number_field).val() == 0 ) {
				// Automatically enter a quantity when a product is selected
				$(number_field).val(1);
			};
		} else {
			// reset number field
			$( number_field ).val(0);
		}
		var available_stock = $(this).find(':selected').data('stock');
		if( available_stock ) {
			var number = $(number_field).attr('max',available_stock);
			if( $(number).val() > available_stock ) {
				$(number_field).val(available_stock);
			}
		} else {
			$(number_field).removeAttr('max');
		}
	});
	$('body').on('change input keyup paste click','.products-quantities-independent .pewc-child-quantity-field',function( e ) {
		e.stopPropagation(); // this prevents the click event from bubbling up to the event attached to the layer
		if ( $( this ).closest( '.pewc-item' ).hasClass( 'pewc-item-products-select' ) ) {
			// this is a Products select field
			var selIndex = 0;
			if( $(this).val() > 0 ) {
				var selField = $( this ).closest( '.pewc-item' ).find( 'select.pewc-child-select-field' );
				// find first select option with value if not already selected
				selIndex = selField.prop( 'selectedIndex' );
				if ( selIndex < 1 && selField.val() == '' ) {
					$( this ).closest( '.pewc-item' ).find( 'select.pewc-child-select-field option' ).each( function( index, element ) {
						if ( $(this).val() != '' && selIndex < 1 ) {
							selIndex = index;
							return;
						}
					});
				}
			}
			$( this ).closest( '.pewc-item' ).find( 'select.pewc-child-select-field' ).prop( 'selectedIndex', selIndex );
		} else {
			// Ensure this child product is selected
			// for checkbox images
			if( $(this).val() > 0 ) {
				var checkbox = $(this).closest('.pewc-checkbox-wrapper').find('input[type=checkbox]').prop('checked',true).trigger( 'change' );
			} else {
				var checkbox = $(this).closest('.pewc-checkbox-wrapper').find('input[type=checkbox]').prop('checked',false).trigger( 'change' );
			}
		}
		var available_stock = $(this).find(':selected').data('stock');
		// $( 'body' ).trigger( 'pewc_update_child_quantity', [ $(this).closest('.pewc-checkbox-image-wrapper').find('input[type=checkbox]') ] );
		$( 'body' ).trigger( 'pewc_update_child_quantity', [ $(this).closest('.pewc-checkbox-wrapper').find('input[type=checkbox]') ] );
		pewc_update_total_js();
	});

	// 3.24.8, Products and Product Categories fields, Radio Images/Radio List layout, independent quantity. Since the quantity input is now outside .products-quantities-independent, the script above doesn't get triggered.
	$( 'body' ).on( 'change input keyup paste click', '.pewc-item-products-radio.pewc-item-products-independent .pewc-child-quantity-field, .pewc-item-products-radio-list.pewc-item-products-independent .pewc-child-quantity-field', function( e ){
		var item_field_wrapper = $( this ).closest( '.pewc-item-field-wrapper' );
		var pewc_item_wrapper = item_field_wrapper.closest( '.pewc-item' );
		var radio_type = '';
		var number_checked = 0;

		if ( pewc_item_wrapper.hasClass( 'pewc-item-products-radio' ) ) {
			radio_type = 'radio images';
			number_checked = item_field_wrapper.find( '.pewc-radio-images-wrapper .pewc-radio-image-wrapper.checked' ).length;
		} else {
			radio_type = 'radio list';
			number_checked = item_field_wrapper.find( '.pewc-radio-wrapper input.pewc-radio-form-field:checked' ).length;
		}

		var number_value = $( this ).val();
		if ( number_value > 0 && number_checked < 1 ) {
			// we don't have a product selected, select the first one?
			if ( radio_type == 'radio images' ) {
				item_field_wrapper.find( '.pewc-radio-images-wrapper .pewc-radio-image-wrapper' ).each( function(){
					if ( ! $( this ).hasClass( 'checked' ) ) {
						$( this ).addClass( 'checked' );
					}
					$( this ).find( 'input.pewc-radio-form-field' ).prop( 'checked', true );
					return false; // break the loop since we only want to select the first one
				});
			} else {
				// radio list
				item_field_wrapper.find( '.pewc-radio-list-wrapper .pewc-radio-wrapper input.pewc-radio-form-field' ).each( function(){
					$( this ).prop( 'checked', true );
					return false; // break the loop since we only want to select the first one
				});
			}
		} else if ( number_value < 1 && number_checked > 0 ) {
			// de-select all products
			if ( radio_type == 'radio images' ) {
				item_field_wrapper.find( '.pewc-radio-images-wrapper .pewc-radio-image-wrapper.checked' ).each( function(){
					$( this ).removeClass( 'checked' );
					$( this ).find( 'input.pewc-radio-form-field' ).prop( 'checked', false );
				});
			} else {
				// radio list
				item_field_wrapper.find( '.pewc-radio-list-wrapper .pewc-radio-wrapper input.pewc-radio-form-field:checked' ).each( function(){
					$( this ).prop( 'checked', false );
				});
			}
		}
	});

	$('body').on('change input keyup paste','.pewc-item-products-independent.pewc-child-qty-target .pewc-child-quantity-field',function() {
		// This runs when the user manually updates a child product quantity that has been set by a calculation field (independent quantities, radio items only)
		// If the calculation field has a reverse formula, use the result to set the source field value
		var wrapper = $( this ).closest( '.pewc-item' );
		// This is the ID of the calculation field that triggers this quantity update
		var triggered_by = $( wrapper ).attr( 'data-child-qty-triggered-by' );
		// Get the "reverse formula" - i.e. the method to calculate how to update the source input field
		var reverse_field_id = $( '.pewc-field-' + triggered_by ).attr( 'data-reverse-formula-field' );
		var reverse_formula = calculations.get_formula_by_id( reverse_field_id );
		// Do this calc "live" to avoid any race conditions with other fields getting updated
		calc_field = $( '.pewc-field-' + reverse_field_id );
		calc_field_id = $( calc_field ).attr( 'data-field-id' );
		price_wrapper = $( calc_field ).find( '.pewc-calculation-price-wrapper' );
		calc_field_id = $( calc_field ).attr( 'data-field-id' );
		formula = $( price_wrapper ).find( '.pewc-data-formula' ).val();
		fields = $( price_wrapper ).find( '.pewc-data-fields' ).val();
		round = $( price_wrapper ).find( '.pewc-formula-round' ).val();
		decimals = $( price_wrapper ).find( '.pewc-decimal-places' ).val();
		if( fields ) {
			fields = JSON.parse( fields );
		}
		var reverse_result = calculations.evaluate_formula( fields, reverse_formula, round, decimals, calc_field_id );
		// Update the "source" input field with the result of the reverse calculation
		// The source input is the number field used in the main calculation field
		var source_field_id = $( '.pewc-field-' + triggered_by ).attr( 'data-reverse-input-field' );
		$( '.pewc-field-' + source_field_id ).find( 'input' ).val( reverse_result );

		// Check if the calculation can be overridden by manual quantity updates
		if( $( '.pewc-field-' + triggered_by ).hasClass( 'pewc-quantity-overrides' ) ) {
			// Only set the quantity of this field with the result of the calculation once - to allow users to override the calculation by updating the quantity manually
			$( wrapper ).addClass( 'pewc-quantity-updated' );
		}
		
	});
	$( 'body' ).on( 'pewc_update_child_quantity', function( e, el ) {
		// This is triggered when the quantity field for a child product is updated
		var child_qty = $( el ).closest('.pewc-checkbox-wrapper').find('input[type=number]').val();
		if( child_qty > 0 ) {
			$( el ).closest('.pewc-checkbox-image-wrapper').addClass('checked');
		} else {
			$( el ).closest('.pewc-checkbox-image-wrapper').removeClass('checked');
		}
	});
	$( 'body' ).on( 'click', '.products-quantities-independent .pewc-checkbox-form-field', function( e ) {
		if( $( this ).closest( '.pewc-checkbox-images-wrapper' ).hasClass( 'pewc-force-quantity' ) ) {
			e.stopPropagation();
			e.preventDefault(); // 3.25.7, prevent the hidden input checkbox from being unchecked and deselected
			return;
		}
		// this is triggered when clicking the child product's image
		if( $(this).is(':checked') ){
			var number = $(this).closest('.pewc-checkbox-wrapper').find('input[type=number]').val();
			if( number==0 ) {
				var default_quantity = $(this).closest( '.pewc-checkbox-wrapper' ).find( 'input[type=number]' ).attr( 'data-default-quantity' );
				if( default_quantity == undefined || pewc_vars.set_child_quantity_default != 'yes' ) {
					default_quantity = 1;
				}
				$(this).closest('.pewc-checkbox-wrapper').find('input[type=number]').val( default_quantity );
				//$(this).closest('.pewc-checkbox-image-wrapper').addClass('checked'); // commented out in 3.13.2, this is handled when the wrapper itself is clicked
			}
		} else {
			var number = $(this).closest('.pewc-checkbox-wrapper').find('input[type=number]').val(0);
			//$(this).closest('.pewc-checkbox-image-wrapper').removeClass('checked'); // commented out in 3.13.2
		}
		e.stopPropagation(); // added in 3.13.2. this may be triggered when the wrapper is clicked. Prevents infinite loop
	});
	$( 'body' ).on( 'click', '.products-quantities-linked .pewc-checkbox-form-field, .products-quantities-one-only .pewc-checkbox-form-field', function( e ) {
		e.stopPropagation(); // 3.13.3, prevents infinite loop
		if( $(this).is(':checked') ){
			$(this).closest('.pewc-checkbox-image-wrapper').addClass('checked');
		} else {
			$(this).closest('.pewc-checkbox-image-wrapper').removeClass('checked');
		}
	});

	// Toggle class on label when text swatch element is updated
	$( 'body' ).on( 'change', '.pewc-text-swatch input', function( e ) {
		var field_type = $( this ).closest( '.pewc-item' ).attr( 'data-field-type' );
		if( field_type == 'radio' ) {
			$( this ).closest( 'ul' ).find( 'label' ).removeClass( 'active-swatch' );
		}

		if( $( this ).prop( 'checked' ) == true ) {
			$( this ).closest( 'label' ).addClass( 'active-swatch' );
		} else {
			$( this ).closest( 'label' ).removeClass( 'active-swatch' );
		}
	});

	$( 'body' ).on( 'click', '.pewc-radio-image-wrapper, .pewc-checkbox-image-wrapper', function( e ) {
		var wrapper = $( this );
		if( $( wrapper ).hasClass( 'pewc-checkbox-disabled' ) ) {
			return;
		}
		if( $( wrapper ).closest( '.pewc-components-wrapper' ).hasClass( 'pewc-force-quantity' ) ) {
			return;
		}
		
		// 
		var radio;
		var is_image_swatch_checkbox = $( wrapper ).closest( '.pewc-item' ).hasClass( 'pewc-item-image-swatch-checkbox' );
		var is_checkbox = $( wrapper ).hasClass( 'pewc-checkbox-image-wrapper' );
		// Remove all checked for radio button
		if( ! is_image_swatch_checkbox && ! is_checkbox ) {
			var checked = $( wrapper ).find( '.pewc-radio-form-field' ).prop( 'checked' );
			var group = $( wrapper ).closest( '.pewc-radio-images-wrapper' ).find( '.pewc-radio-image-wrapper' ).removeClass( 'checked' );
			if( ! checked ) {
				$( wrapper ).addClass( 'checked' );
				//radio = $( wrapper ).find( '.pewc-radio-form-field' ).trigger( 'click' );
			}
			radio = $( wrapper ).find( '.pewc-radio-form-field' ).trigger( 'click' ); // moved here 3.9.5 for lightbox
		} else {
			// Checkbox
			if ( is_checkbox ) {
				// added in 3.13.2 for Products checkbox images
				e.preventDefault(); // this helps avoid the conflict with clicking the child product image
				radio = $( wrapper ).find( '.pewc-checkbox-form-field' );
				$( wrapper ).toggleClass( 'checked' );
				$( radio ).trigger( 'click' ); // this triggers the click event attached to .products-quantities-independent .pewc-checkbox-form-field
				e.stopPropagation();
			} else {
				radio = $( wrapper ).find( '.pewc-radio-form-field' );
				var checked = $( radio ).prop( 'checked' );
				$( wrapper ).toggleClass( 'checked' );
				$( radio ).prop( 'checked', ! checked ).trigger( 'click' ); // click added 3.9.5
			}
		}

	}).on( 'click', '.pewc-radio-image-wrapper .pewc-radio-form-field', function( e ) {
		var is_image_swatch_checkbox = $( this ).closest( '.pewc-item' ).hasClass( 'pewc-item-image-swatch-checkbox' );
		if( ! is_image_swatch_checkbox ) {
			// Stop propagation for radio buttons
			e.stopPropagation();
			// Deselect the radio button
			var checked = $( this ).closest( '.pewc-radio-image-wrapper' ).hasClass( 'checked' );
			if( ! checked ) {
				$( this ).prop( 'checked', false ).trigger( 'change' ); // added on 3.9.5 for lightbox
				pewc_update_total_js();
			}
		} else {
			e.stopPropagation(); // added on 3.9.5, for checkbox in lightbox
			$( this ).closest( '.pewc-radio-image-wrapper' ).toggleClass( 'checked' ); // originally here before 3.9.5
		}
		$form = $( this ).closest( 'form' );
		add_on_images.update_add_on_image( $( this ), $form );
	});

	$( 'body' ).on( 'click' , '.products-quantities-independent .pewc-radio-form-field', function() {
		if($(this).is(':checked')) {
			var number_field = $(this).closest('.pewc-item-field-wrapper').find('input[type=number]');
			var number = $(number_field).val();
			if( number == 0 ) {
				$( number_field ).val( 1 );
			}
			if( $(this).attr('data-stock') > 0 ) {
				// Ensure the quantity field doesn't display more than the available stock
				$(number_field).attr( 'max', $(this).attr('data-stock') );
				if( $(number_field).val() > $(this).attr('data-stock') ) {
					$(number_field).val( $(this).attr('data-stock') );
				}
			}
		} else {
			//var number = $(this).closest( '.pewc-radio-images-wrapper' ).find( 'input[type=number]' ).val( 0 );
			var number = $(this).closest( '.pewc-item-field-wrapper' ).find( 'input[type=number]' ).val( 0 ); // 3.24.8, change quantity to 0 for Products and Product Categories field, Radio Images layout, independent quantity
		}
	});

	// 3.12.0, handler for enabling/disabling the add-to-cart button
	function pewc_toggle_add_to_cart_button( eventType, disabled, caller ) {

		var button = $( 'body' ).find( 'form.cart .single_add_to_cart_button' );
		var disable_class = 'pewc-disabled-by-'+caller;

		if ( button.length < 1 ) return; // do nothing

		if ( disabled ) {
			// always disable button
			button.attr( 'disabled', true );
			if ( ! button.hasClass( disable_class ) ) {
				button.addClass( disable_class );

				// 3.21.5
				var button_text = pewc_vars.calculating_text;
				if ( caller === 'upload' ) {
					button_text = pewc_vars.uploading_text;
				}
				pewc_update_add_to_cart_button_text( button, button_text );
			}
		} else {
			// remove the disable_class added by the caller when it enabled it previously
			button.removeClass( disable_class );
			// if we're trying to enable the add-to-cart button, check first if it hasn't been disabled by other plugins (example, by the calculation table)
			if ( button.attr( 'class' ).indexOf( 'pewc-disabled-by' ) == -1 ) {
				// our special class was not found, safe to enable again
				button.attr( 'disabled', false );
				pewc_update_add_to_cart_button_text( button, pewc_vars.default_add_to_cart_text );
			}
		}

	}
	$( 'body' ).on( 'pewc_toggle_add_to_cart_button', pewc_toggle_add_to_cart_button );

	function pewc_update_add_to_cart_button_text( button, button_text ) {
		if ( button.is( 'button' ) ) {
			button.text( button_text );
		} else {
			button.val( button_text ); // maybe an input type submit button
		}
	}

	var calculations = {

		init: function() {

			if( pewc_vars.calculations_timer > 0 ) {
				var interval = setInterval(
					this.recalculate,
					pewc_vars.calculations_timer
				);

				// since 3.12.0, disable add-to-cart button while recalculating
				if ( pewc_vars.disable_button_recalculate == 'yes' ) {
					$( 'body' ).on( 'keyup input change paste', 'form.cart .qty', this.disable_add_to_cart_button );
					$( 'body' ).on( 'keyup input change paste', 'form.cart .pewc-item.pewc-calculation-trigger, .pewc-form-field.pewc-calculation-trigger, .pewc-field-triggers-condition .pewc-form-field, .pewc-field-triggers-condition .pewc-radio-form-field', this.disable_add_to_cart_button );
					$( 'body' ).on( 'keyup input change paste', '.pewc-number-uploads', this.disable_add_to_cart_button );
					$( 'body' ).on( 'pewc_trigger_calculations', this.disable_add_to_cart_button );
					$( 'body' ).on( 'pewc_conditions_checked', this.disable_add_to_cart_button );
				}
			} else {
				// $( 'body' ).one( 'keyup input change paste', '.pewc-number-field.pewc-calculation-trigger, .pewc-field-triggers-condition .pewc-number-field', this.recalculate );
				$( 'body' ).on( 'keyup input change paste', 'form.cart .qty', this.recalculate );
				$( 'body' ).on( 'keyup input change paste', 'form.cart .pewc-item.pewc-calculation-trigger, .pewc-form-field.pewc-calculation-trigger, .pewc-field-triggers-condition .pewc-form-field, .pewc-field-triggers-condition .pewc-radio-form-field', this.recalculate );
				$( 'body' ).on( 'keyup input change paste', '.pewc-number-uploads', this.recalculate );
				$( 'body' ).on( 'pewc_trigger_calculations', this.recalculate );
				$( 'body' ).on( 'pewc_conditions_checked', this.recalculate );
			}

		},

		// 3.12.0
		disable_add_to_cart_button: function( e ) {
			// 3.12.2. Only disable if product has calculation fields
			var num_calcs = $( '.pewc-product-extra-groups-wrap .pewc-item-calculation' ).not( '.pewc-hidden-field' ).length;

			if( parseFloat( num_calcs ) < 1 ) {
				return;
			}
			pewc_toggle_add_to_cart_button( e, true, 'calculation_recalculate' );
		},

		recalculate: function( e ) {

			// If we don't have any calculation fields, just bounce
			var num_calcs = parseInt( $( '.pewc-product-extra-groups-wrap .pewc-item-calculation' ).not( '.pewc-hidden-field' ).length );
			var num_formulas_in_field_prices = parseInt( $( '.pewc-product-extra-groups-wrap .pewc-field-price-has-formula' ).not( '.pewc-hidden-field' ).length );
			var num_formulas_in_option_prices = parseInt( $( '.pewc-product-extra-groups-wrap .pewc-field-option-price-has-formula' ).not( '.pewc-hidden-field' ).length );

			if( num_calcs < 1 && num_formulas_in_field_prices < 1 && num_formulas_in_option_prices < 1 ) {
				return;
			}

			var calc_field, price_wrapper, dimensions_wrapper, formula, tags, calc_formula, replace, calc_field_id, calc_value, prev_calc_value;

			var calc_fields = [];

			var update = 0;

			if ( pewc_vars.calculations_timer > 0 && pewc_vars.disable_button_recalculate == 'yes' ) {
				// 3.12.0, we use this to determine if we have to enable or disable the add-to-cart button
				var pewc_total_calc_price_start = $( '#pewc_total_calc_price' ).val();
			}

			// Find any calculation fields
			$( 'body' ).find( 'form.cart .pewc-item-calculation' ).not( '.pewc-hidden-field' ).each( function() {

				if( $( this ).hasClass( 'pewc-variation-dependent' ) && ! $( this ).hasClass( 'active' ) ) {
					return;
				}

				var group = $( this ).closest( '.pewc-group-wrap' );
				if( $( group ).hasClass( 'pewc-group-hidden' ) ) {
					return;
				}

				var group_id = $( group ).attr( 'data-group-id' );

				calc_field = $( this );
				var field_id = $( calc_field ).attr( 'data-id' );
				calc_field_id = $( calc_field ).attr( 'data-field-id' );
				price_wrapper = $( calc_field ).find( '.pewc-calculation-price-wrapper' );
				formula = $( price_wrapper ).find( '.pewc-data-formula' ).val();
				fields = $( price_wrapper ).find( '.pewc-data-fields' ).val();
				tags = $( price_wrapper ).find( '.pewc-data-tag' ).val();
				action = $( price_wrapper ).find( '.pewc-action' ).val();
				round = $( price_wrapper ).find( '.pewc-formula-round' ).val();
				decimals = $( price_wrapper ).find( '.pewc-decimal-places' ).val();
				calc_value = $( price_wrapper ).find( '.pewc-calculation-value' );
				prev_calc_value = calc_value.val();

				if( fields ) {
					fields = JSON.parse( fields );
				}

				var result = calculations.evaluate_formula( fields, formula, round, decimals, calc_field_id );

				if( result == '*' ) {
					$( calc_field ).closest( '.pewc-item-calculation' ).attr( 'data-price', 0 );
					$( price_wrapper ).find( 'span' ).html( pewc_vars.null_signifier );
					calc_value.val( 0 );
					if ( prev_calc_value != 0 ) {
						// 3.12.2. Trigger only if the value changed to avoid infinite loop
						calc_value.trigger( 'calculation_field_updated' )
					}
					if( pewc_vars.disable_button_calcs == 'yes' ) {
						calc_fields.push( calc_field_id );
						//$( 'body' ).find( 'form.cart .single_add_to_cart_button' ).attr( 'disabled', true );
						$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ true, 'calculation' ] ); // since 3.12.0
					}
				} else if( ! result || ! isNaN( result ) ) {
					if( pewc_vars.disable_button_calcs == 'yes' ) {
						calc_fields = calc_fields.filter( function( item ) {
							return item !== calc_field_id;
						});
						if( calc_fields.length < 1 ) {
							//$( 'body' ).find( 'form.cart .single_add_to_cart_button' ).attr( 'disabled', false );
							$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ false, 'calculation' ] ); // since 3.12.0
						}
					}
					$( price_wrapper ).find( 'span' ).html( result );

					if( action == 'cost' || action == 'price' ) {
						$( calc_field ).closest( '.pewc-item-calculation' ).attr( 'data-price', result );
						$( price_wrapper ).find( 'span' ).html( pewc_wc_price( result ) );
					} else if( action == 'qty' ) {
						$( 'form.cart' ).find( '.quantity .qty' ).val( result ).trigger( 'pewc_qty_changed' );
					} else if( action == 'child-qty' ) {
						// When a calculation updates, check if it should update a child product quantity field (independent, radio buttons only)
						// If it does, we update the quantity field						
						// This is the product field that will have its quantity set
						var product_field_id = $( calc_field ).closest( '.pewc-item-calculation' ).find( '.pewc-calculation-price-wrapper' ).attr( 'data-product-field-id' );
						// This is the ID of the child product that will be selected
						var child_qty_product_id = $( calc_field ).closest( '.pewc-item-calculation' ).find( '.pewc-calculation-price-wrapper' ).attr( 'data-child-product-id' );
						// Only set the result of this calculation once - don't update quantity if the user has updated it manually themselves
						if( ! $( '.pewc-field-' + product_field_id ).hasClass( 'pewc-quantity-updated' ) ) {
							$( '.pewc-field-' + product_field_id ).find( '.pewc-child-quantity-field' ).val( result ).trigger( 'pewc_update_child_quantity' );
							var group_name = 'pewc_group_' + group_id + '_' + product_field_id;
							// Auto select item (provided an item is not already selected)
							if( $( "input[name='" + group_name + "_child_product[]']:checked" ).length < 1 ) {
								$( '#' + group_name + '_' + child_qty_product_id ).prop( 'checked', true );
								$( '#' + group_name + '_' + child_qty_product_id ).closest( '.pewc-radio-image-wrapper' ).addClass( 'checked' );
							}
						}						
					} else if ( ! $( calc_field ).hasClass( 'pewc-hidden-calculation' ) ) {
						// no-action falls here, don't bother if calc field is hidden
						// pewc-result-format is filterable
						var result_format = $( price_wrapper ).find( '.pewc-result-format' ).val();
						if ( result_format == 'price' ) {
							$( price_wrapper ).find( 'span' ).html( pewc_wc_price( result ) );
						} else if ( result_format == 'price without currency' ) {
							$( price_wrapper ).find( 'span' ).html( pewc_wc_price_without_currency( result ) );
						}
					}

					if( action == 'price' ) {
						$( calc_field ).closest( '.pewc-product-extra-groups-wrap' ).find( '#pewc_calc_set_price' ).val( result ).attr( 'data-calc-set', 1 ); // 3.17.2, compatibility with Product Table Ultimate
						//$( '#pewc_calc_set_price' ).val( result ).attr( 'data-calc-set', 1 ); // before 3.17.2
					}

					calc_value.val( result );
					if ( prev_calc_value != result ) {
						// 3.12.2. Trigger only if the value changed to avoid infinite loop
						calc_value.trigger( 'calculation_field_updated' );
						calc_value.trigger( 'change' ); // 3.17.2, makes this field more responsive to changes by typing
					}
				} else if( result && isNaN( result ) && result != 'error' ) {
					allow_text_calcs_fields = pewc_vars.allow_text_calcs_fields;
					if( pewc_vars.allow_text_calcs ) {
						$( price_wrapper ).find( 'span' ).html( result );
						calc_value.val( result );
					} else if( allow_text_calcs_fields.includes( parseInt( calc_field_id ) ) ) {
						$( price_wrapper ).find( 'span' ).html( result );
						calc_value.val( result );
					}
				}

				update++;

			});

			// 3.26.0
			if ( num_formulas_in_field_prices > 0 ) {
				calculations.evaluate_field_prices();
			}
			if ( num_formulas_in_option_prices > 0 ) {
				calculations.evaluate_option_prices();
			}

			// Update the totals
			if( update > 0 ) {
				update = 0;
				pewc_update_total_js();
			}

			if ( pewc_vars.calculations_timer > 0 && pewc_vars.disable_button_recalculate == 'yes' ) {
				// 3.12.0, we use this to determine if we have to enable or disable the add-to-cart button
				var pewc_total_calc_price_end = $( '#pewc_total_calc_price' ).val();
				var d = new Date();
				var time = d.getTime();

				if ( pewc_total_calc_price_start != pewc_total_calc_price_end ) {
					// maybe we're not done calculating, disable the add-to-cart button
					$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ true, 'calculation_recalculate' ] );
					// record the last time there was a change
					$( '#pewc_total_calc_price' ).attr( 'data-last-calc-time', time );
				} else {
					// maybe we're done calculating, let's wait a second?
					if ( isNaN( $( '#pewc_total_calc_price' ).attr( 'data-last-calc-time') ) ) {
						// maybe this is not yet created, save a value
						$( '#pewc_total_calc_price' ).attr( 'data-last-calc-time', time );
					} else {
						var diff = parseFloat( time ) - parseFloat( $( '#pewc_total_calc_price' ).attr( 'data-last-calc-time') );
						if ( isNaN( diff ) || diff < pewc_vars.recalculate_waiting_time ) {
							// do nothing if not a number, or less than waiting time (default 0.7 seconds)
						} else {
							//enable the add-to-cart button
							$( 'body' ).trigger( 'pewc_toggle_add_to_cart_button', [ false, 'calculation_recalculate' ] );
						}
					}
				}

			}

		},

		evaluate_look_up_table: function( calc_field_id ) {

			var result = false;

			if( pewc_look_up_fields == undefined ) return false;

			var look_up_field = pewc_look_up_fields[calc_field_id];

			var table = look_up_field[0];
			var x_field = look_up_field[1];
			var y_field = look_up_field[2];

			var x_value = this.get_field_value( x_field );
			var y_value = this.get_field_value( y_field );

			var tables = pewc_look_up_tables[table];
			if( tables == undefined ) {
				return false;
			}

			var x_axis = tables[x_value];

			// If there's not an element in the tables array for our x value, find the next one
			if( x_axis == undefined && x_value && x_value != undefined ) {
				if ( this.exact_match( calc_field_id, 'x' ) ) {
					return '*'; // 3.13.0, let's use the null signifier
				} else {
					x_value = calculations.find_nearest_index( x_value, tables );
					x_axis = tables[x_value];
				}
			} else if( x_axis == undefined && x_value==0 && x_value != undefined ) {
				x_axis = tables[Object.keys(tables)[0]];
			}

			if( x_axis != undefined ) {

				var y_axis = tables[y_value];

				// if( y_axis == undefined && y_value && y_value != undefined ) {
				if( y_value && y_value != undefined ) {
					if ( ! this.exact_match( calc_field_id, 'y' ) ) {
						y_value = calculations.find_nearest_index( y_value, x_axis );
					}
				}
				if( y_value == 'max' ) {
					// Get last value in x_axis
					result = x_axis[Object.keys(x_axis)[Object.keys(x_axis).length - 1]];
				} else {
					result = x_axis[y_value];
				}

				if ( this.exact_match( calc_field_id, 'y' ) && result == undefined ) {
					result = '*'; // 3.13.0
				}
			} else {

				return false;

			}

			return result;

		},

		get_field_value: function( field_id ) {

			var value = 0;

			if( $( '.pewc-field-' + field_id ).find( 'input.pewc-number-field' ).length > 0 ) {
				return $( '.pewc-field-' + field_id ).find( 'input.pewc-number-field' ).val();
			} else if( $( '.pewc-field-' + field_id ).find( 'select' ).length > 0 ) {
				return $( '.pewc-field-' + field_id ).find( 'select option:selected' ).attr( 'value' );
			} else if( $( '.pewc-field-' + field_id ).find( 'input.pewc-radio-form-field[type="radio"]:checked' ).length > 0 ) {
				return $( '.pewc-field-' + field_id ).find( 'input.pewc-radio-form-field[type="radio"]:checked' ).attr( 'data-option-cost' );
			}

			return value;
		},

		find_nearest_index: function( value, axis ) {

			var keys = Object.keys( axis ); // Just the keys
			keys.sort( (a,b) => a-b ); // sort the keys numerically. This is needed if keys have decimals

			if( parseFloat( value ) <= parseFloat( keys[0] ) ) {
				return keys[0];
			}

			// If first element is empty, replace with 0
			if( pewc_vars.set_initial_key == 'yes' ) {
				if( isNaN( keys[0] ) || keys[0] == '' ) {
					keys[0] = 0;
				}
			}

			for( var i=0; i < keys.length; i++ ) {
				if( ( parseFloat( value ) > parseFloat( keys[i] ) ) && keys[i+1] !=undefined && ( parseFloat( value ) <= parseFloat( keys[i+1] ) ) ) { // Find the first key that is greater than the value passed in
					return keys[i+1];
				}
			}

			if( keys[( keys.length ) - 1] == 'max' ) {
				return 'max';
			}

			// Return this as a fallback in case we've gone over the last index
			return keys[i];

		},

		exact_match: function( calc_field_id, axis ) {
			// since 3.13.0
			if ( $( '.pewc-item-calculation.pewc-field-' + calc_field_id ).attr( 'data-exact-match-' + axis ) == 'yes' ) {
				return true;
			} else {
				return false;
			}
		},

		evaluate_formula: function( fields, formula, round, decimals, calc_field_id ) {

			var calc_formula = formula;

			if( fields ) {

				// Replace any field tags with values
				for( var i in fields ) {

					// Look for any price values
					if( fields[i].indexOf( '_option_price' ) > -1 ) {
						var field_id = fields[i].replace( '_option_price', '' );
						// We want the price of the selected option in this field, not its value
						var o_price = parseFloat( $( 'form.cart .pewc-field-' + field_id ).attr( 'data-selected-option-price' ) );
						if( $( 'form.cart .pewc-field-' + field_id ).length == 0 && pewc_vars.zero_missing_field == 'yes' ) {
							o_price = 0;
						}
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, o_price );
					} else if ( fields[i].indexOf( '_option_value' ) > -1 ) {
						var field_id = fields[i].replace( '_option_value', '' );
						// We want the value of the selected option in this field
						var o_value = parseFloat( $( 'form.cart .pewc-field-' + field_id ).attr('data-field-value') );
						if( ( $( 'form.cart .pewc-field-' + field_id ).length == 0 && pewc_vars.zero_missing_field == 'yes' ) || isNaN( o_value ) ) {
							o_value = 0;
						}
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, o_value );
					} else if( fields[i].indexOf( '_field_price' ) > -1 ) {
						// We want the price of the field
						var field_id = fields[i].replace( '_field_price', '' );
						var f_price = parseFloat( $( 'form.cart .pewc-field-' + field_id ).attr( 'data-field-price' ) );
						if( $( 'form.cart .pewc-field-' + field_id ).length == 0 && pewc_vars.zero_missing_field == 'yes' ) {
							f_price = 0;
						}
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, f_price );
					} else if( fields[i].indexOf( '_number_uploads' ) > -1 ) {
						// We want the number of uploads
						var field_id = fields[i].replace( '_number_uploads', '' );
						var num_uploads = parseFloat( $( 'form.cart .pewc-field-' + field_id ).find( '.pewc-number-uploads' ).val() );
						if( $( 'form.cart .pewc-field-' + field_id ).length == 0 && pewc_vars.zero_missing_field == 'yes' ) {
							num_uploads = 0;
						}
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, num_uploads );
					} else if( fields[i].indexOf( '_pdf_count' ) > -1 ) {
						// Get the number of pages for this uploaded PDF
						var field_id = fields[i].replace( '_pdf_count', '' );
						var pdf_count = parseFloat( $( '#field_' + field_id + "_pdf_count" ).val() );
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, pdf_count );
					} else if( fields[i].indexOf( '_weight' ) > -1 ) {
						// Get the weight of child products selected in specified field
						var field_id = fields[i].replace( '_weight', '' );
						// Find the selected item in the field and get the weight
						var weight = $( 'form.cart .pewc-field-' + field_id ).find( 'input:checked').closest( '.pewc-radio-wrapper' ).attr( 'data-product-weight' );
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, weight );
					} else if( fields[i].indexOf( '_length' ) > -1 ) {
						// Get the length of child products selected in specified field
						var field_id = fields[i].replace( '_length', '' );
						// Find the selected item in the field and get the length
						var length = $( 'form.cart .pewc-field-' + field_id ).find( 'input:checked').closest( '.pewc-radio-wrapper' ).attr( 'data-product-length' );
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, length );
					} else if( fields[i].indexOf( '_width' ) > -1 ) {
						// Get the width of child products selected in specified field
						var field_id = fields[i].replace( '_width', '' );
						// Find the selected item in the field and get the width
						var width = $( 'form.cart .pewc-field-' + field_id ).find( 'input:checked').closest( '.pewc-radio-wrapper' ).attr( 'data-product-width' );
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, width );
					} else if( fields[i].indexOf( '_height' ) > -1 ) {
						// Get the height of child products selected in specified field
						var field_id = fields[i].replace( '_height', '' );
						// Find the selected item in the field and get the height
						var height = $( 'form.cart .pewc-field-' + field_id ).find( 'input:checked').closest( '.pewc-radio-wrapper' ).attr( 'data-product-height' );
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, height );
					} else if( fields[i].indexOf( '_quantity' ) > -1 ) {
						// Get the quantity of child products in specified field - independent quantities, radio list / images only
						var field_id = fields[i].replace( '_quantity', '' );
						// Find the selected item in the field and get the quantity
						var quantity = $( 'form.cart .pewc-field-' + field_id ).find( '.pewc-independent-quantity-field').val();
						replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
						calc_formula = calc_formula.replace( replace, quantity );
					} else {
						// Look for the value of number fields
						var f_val = parseFloat( $( 'form.cart .pewc-number-field-' + fields[i] ).val() );
						if( ! f_val && pewc_vars.zero_missing_field == 'yes' ) {
							f_val = 0;
						}
						if( ! isNaN( f_val ) ) {
							if( $( 'form.cart .pewc-field-' + fields[i] ).length == 0 && pewc_vars.zero_missing_field == 'yes' ) {
								f_val = 0;
							}
							replace = new RegExp( '{field_' + fields[i] + '}', 'g' );
							calc_formula = calc_formula.replace( replace, f_val );
						}
					}

				}

			}

			if( formula === undefined ) {
				console.log( 'formula not defined: ' + calc_field_id );
				return false;
			}

			var product_price = parseFloat( $('#pewc-product-price').val() );
			// 3.12.0, if action of calculation field is Set Product Price, and if product has an FD bulk discount, use the original product price in the calculation, so that we can then set the discounted price later
			if ( typeof pewc_wcfad !== 'undefined' && pewc_wcfad.reset_product_price( formula, calc_field_id ) ) {
				product_price = parseFloat( $('#pewc-product-price-original').val() );
			}
			var quantity = parseFloat( $( 'form.cart .quantity' ).find( '.qty' ).val() );
			if( formula.includes( "{look_up_table}" ) ) {
				calc_formula = calculations.evaluate_look_up_table( calc_field_id );
			}
			if( formula.includes( "{product_weight}" ) ) {
				var product_weight = parseFloat( $( '#pewc_product_weight' ).val() );
				calc_formula = calc_formula.replace( /{product_weight}/g, parseFloat( product_weight ) );
			}
			if( formula.includes( "{product_height}" ) ) {
				var product_height = parseFloat( $( '#pewc_product_height' ).val() );
				calc_formula = calc_formula.replace( /{product_height}/g, parseFloat( product_height ) );
			}
			if( formula.includes( "{product_length}" ) ) {
				var product_length = parseFloat( $( '#pewc_product_length' ).val() );
				calc_formula = calc_formula.replace( /{product_length}/g, parseFloat( product_length ) );
			}
			if( formula.includes( "{product_width}" ) ) {
				var product_width = parseFloat( $( '#pewc_product_width' ).val() );
				calc_formula = calc_formula.replace( /{product_width}/g, parseFloat( product_width ) );
			}
			if( formula.includes( "{product_price}" ) && product_price ) {
				calc_formula = calc_formula.replace( /{product_price}/g, parseFloat( product_price ) );
			}
			if( formula.includes( "{quantity}" ) && quantity ) {
				calc_formula = calc_formula.replace( /{quantity}/g, parseFloat( quantity ) );
			}
			if( formula.includes( "{variable_1}" ) && pewc_vars.variable_1 ) {
				calc_formula = calc_formula.replace( /{variable_1}/g, parseFloat( pewc_vars.variable_1 ) );
			}
			if( formula.includes( "{variable_2}" ) && pewc_vars.variable_2 ) {
				calc_formula = calc_formula.replace( /{variable_2}/g, parseFloat( pewc_vars.variable_2 ) );
			}
			if( formula.includes( "{variable_3}" ) && pewc_vars.variable_3 ) {
				calc_formula = calc_formula.replace( /{variable_3}/g, parseFloat( pewc_vars.variable_3 ) );
			}
			if( formula.includes( "{calculated_booking_cost}" ) ) {
				var calculated_booking_cost = parseFloat( $( '#calculated_booking_cost' ).val() );
				calc_formula = calc_formula.replace( /{calculated_booking_cost}/g, parseFloat( calculated_booking_cost ) );
			}
			if( formula.includes( "{num_units_int}" ) ) {
				var num_units_int = parseFloat( $( '#num_units_int' ).val() );
				calc_formula = calc_formula.replace( /{num_units_int}/g, parseFloat( num_units_int ) );
			}
			if( formula.includes( "{num_bookings}" ) ) {
				var num_units_int = parseFloat( $( '#num_bookings' ).val() );
				calc_formula = calc_formula.replace( /{num_bookings}/g, parseFloat( num_units_int ) );
			}
			if( formula.includes( "{total_variations}" ) ) {
				var total_variations = parseFloat( $( '#wcbvp_total_variations' ).val() );
				calc_formula = calc_formula.replace( /{total_variations}/g, parseFloat( total_variations ) );
			}
			if( formula.includes( "{pa_" ) ) {
				var attribute = formula.replace( "{", "" );
				var attribute = attribute.replace( "}", "" );
				var attribute_data = pewc_vars.attribute_data;
				calc_formula = parseFloat( attribute_data[attribute] );
			}

			if( pewc_vars.global_calc_vars ) {
				var global_calc_vars = pewc_vars.global_calc_vars;
				// Iterate through our global vars
				for( var key in global_calc_vars ) {
					if( formula.includes( "{" + key + "}" ) ) {
						var global_var = "{" + key + "}";
						var global_var_regex = new RegExp( global_var, 'g' );
						calc_formula = calc_formula.replace( global_var_regex, global_calc_vars[key] );
					}
				}
			}

			// Check for ACF fields
			if( typeof pewc_acf_fields != 'undefined' ) {
				for( var key in pewc_acf_fields ) {
					if( formula.includes( "{acf_" + key + "}" ) ) {
						var acf_field = "{acf_" + key + "}";
						// Find all instances
						var acf_field_regex = new RegExp( acf_field, 'g' );
						calc_formula = calc_formula.replace( acf_field_regex, pewc_acf_fields[key] );
					}
				}
			}

			var result;

			if( calc_formula == '*' ) return calc_formula;

			if( pewc_vars.allow_text_calcs ) {
				return calc_formula;
			}
			
			allow_text_calcs_fields = pewc_vars.allow_text_calcs_fields;
			if( allow_text_calcs_fields.includes( parseInt( calc_field_id ) ) ) {
				return calc_formula;
			}

			try {
				result = math.eval( calc_formula );

				if( round == 'ceil' ) {
					result = math.ceil( result );
				} else if( round == 'floor' ) {
					result = math.floor( result );
				}
				if( pewc_vars.math_round == 'yes' ) {
					result = Math.round( result * 100 ) / 100;
				}
				return result.toFixed( parseFloat( decimals ) );
			} catch( err ) {
				// Check all tags have been replaced
				return 'error';
			}

		},

		get_formula_by_id: function( reverse_field_id ) {

			var formula = $( '.pewc-field-' + reverse_field_id ).find( '.pewc-data-formula' ).val();
			return formula;

		},

		evaluate_field_prices: function() {

			$( 'body' ).find( 'form.cart .pewc-field-price-has-formula' ).not( '.pewc-hidden-field' ).each( function() {
				if( $( this ).hasClass( 'pewc-variation-dependent' ) && ! $( this ).hasClass( 'active' ) ) {
					return;
				}

				var group = $( this ).closest( '.pewc-group-wrap' );
				if( $( group ).hasClass( 'pewc-group-hidden' ) ) {
					return;
				}

				var group_id = $( group ).attr( 'data-group-id' );

				var pewc_item = $( this );
				var field_id = $( pewc_item ).attr( 'data-id' );
				var data_field_id = $( pewc_item ).attr( 'data-field-id' );
				//var price_wrapper = $( pewc_item ).find( '.pewc-calculation-price-wrapper' );
				var formula = $( pewc_item ).attr( 'data-field-price-formula' );
				if ( formula == undefined ) {
					return;
				}
				var fields = $( pewc_item ).attr( 'data-field-price-formula-fields' );
				var tags = $( pewc_item ).attr( 'data-field-price-formula-tags' );
				//var action = $( price_wrapper ).find( '.pewc-action' ).val();
				var round = $( pewc_item ).attr( 'data-field-price-formula-round' );
				var decimals = $( pewc_item ).attr( 'data-field-price-formula-decimals' );
				//var calc_value = $( price_wrapper ).find( '.pewc-calculation-value' );
				//var prev_calc_value = calc_value.val();

				if( fields ) {
					fields = JSON.parse( fields );
				}

				var result = calculations.evaluate_formula( fields, formula, round, decimals, data_field_id );
				if ( isNaN( result ) ) {
					result = 0;
				}
				$( pewc_item ).attr( 'data-price', result ); // data-field-price is field price (data-price) + option-price
				if ( 'checkbox' === $( pewc_item ).attr( 'data-field-type' ) ) {
					$( pewc_item ).find( '.pewc-checkbox-price' ).html( pewc_wc_price( result, true ) );
				} else {
					$( pewc_item ).find( '.pewc-field-price' ).html( pewc_wc_price( result, true ) );
				}
				$( pewc_item ).find( 'input[name="' + field_id + '_field_price_calculated"]' ).val( result );
			});

		},

		evaluate_option_prices: function() {

			$( 'body' ).find( 'form.cart .pewc-field-option-price-has-formula' ).not( '.pewc-hidden-field' ).each( function() {
				if( $( this ).hasClass( 'pewc-variation-dependent' ) && ! $( this ).hasClass( 'active' ) ) {
					return;
				}

				var group = $( this ).closest( '.pewc-group-wrap' );
				if( $( group ).hasClass( 'pewc-group-hidden' ) ) {
					return;
				}

				var group_id = $( group ).attr( 'data-group-id' );

				var pewc_item = $( this );
				var field_id = $( pewc_item ).attr( 'data-id' );
				var data_field_id = $( pewc_item ).attr( 'data-field-id' );
				var round = $( pewc_item ).attr( 'data-field-price-formula-round' );
				var decimals = $( pewc_item ).attr( 'data-field-price-formula-decimals' );

				$( this ).find( '.pewc-option-price-has-formula' ).each( function(){
					var opt = $( this );
					var formula = $( opt ).attr( 'data-option-cost-formula' );
					if ( formula == undefined ) {
						return;
					}
					var fields = $( opt ).attr( 'data-option-price-formula-fields' );
					var tags = $( opt ).attr( 'data-option-price-formula-tags' );
					var index = $( opt ).attr( 'data-option-index' );

					if( fields ) {
						fields = JSON.parse( fields );
					}

					var result = calculations.evaluate_formula( fields, formula, round, decimals, data_field_id );
					if ( isNaN( result ) ) {
						result = 0;
					}
					if ( $( opt ).attr( 'data-option-cost' ) == result && 'select' === $( pewc_item ).attr( 'data-field-type' ) ) {
						return; // no need to update, prevents Select fields from blinking
					}
					$( opt ).attr( 'data-option-cost', result );
					$( opt ).closest( 'label' ).find( '.pewc-option-cost-label' ).html( pewc_wc_price( result ) );
					if ( 'select' === $( pewc_item ).attr( 'data-field-type' ) ) {
						var new_text = $( opt ).val() + pewc_vars.separator + pewc_wc_price( result, true );
						$( this ).text( new_text );
					} else if ( 'select-box' === $( pewc_item ).attr( 'data-field-type' ) ) {
						var selectbox = $( '#' + field_id + '_select_box' );
						if ( selectbox.length > 0 ) {
							var optprice = 0;
							selectbox.find( '.dd-option' ).each( function( index, element ){
								optprice = parseFloat( $( 'input[name="' + field_id + '_option_' + index + '_price_calculated"]' ).val() );
								if ( ! isNaN( optprice ) ) {
									$( element ).find( '.dd-option-description' ).html( pewc_wc_price( optprice, true ) );
								}
							});
							var selected_value = $( pewc_item ).find( '.dd-selected-value' ).val();
							var select_option_index = 0;
							select_option_index = $( 'select#' + field_id + ' option[value="' + selected_value.replace( /"/g, '\\"' ) + '"]').index();
							optprice = parseFloat( $( 'input[name="' + field_id + '_option_' + select_option_index + '_price_calculated"]' ).val() );
							if ( ! isNaN( optprice ) ) {
								$( pewc_item ).find( '.dd-selected-description' ).html( pewc_wc_price( optprice, true ) );
								$( pewc_item ).attr( 'data-selected-option-price', optprice );
							}
						}
					}
					$( pewc_item ).find( 'input[name="' + field_id  + '_option_' + index + '_price_calculated"]' ).val( result );
				});
			});

		}

	}

	calculations.init();

	var hidden_groups = {

		init: function() {
			$( 'body' ).on( 'pewc_conditions_checked', this.check_group_visibility );
		},

		/**
		 * Check whether to hide or display groups
		 */
		check_group_visibility: function() {

			// Check each group
			$( 'body' ).find( '.pewc-group-wrap' ).each( function() {
				var all_hidden = true;
				var group = $( this );
				$( group ).find( '.pewc-item' ).each( function() {
					if( ! $( this ).hasClass( 'pewc-hidden-field' ) ) {
						all_hidden = false;
					}
				});
				if( all_hidden ) {
					$( group ).addClass( 'pewc-hidden-group' );
				} else {
					$( group ).removeClass( 'pewc-hidden-group' );
				}
			});

		}

	}

	hidden_groups.init();

	var summary_panel = {

		init: function() {
			$( '.pewc-form-field' ).on( 'change update keyup', this.update_panel );
		},

		update_panel: function( e ) {
			var field_id = $( this ).closest( '.pewc-item' ).attr( 'data-field-id' );
			var field_value = $( this ).val();
		},

	}

	summary_panel.init();

	/**
	 * Sets product images for the chosen add-on
	 */
	var add_on_images = {

		init: function() {

			var $product        = $( 'form.cart' ).closest( '.product' ),
				$product_gallery  = $product.find( pewc_vars.product_gallery ),
				$gallery_nav      = $product.find( '.flex-control-nav' ),
				$gallery_img      = $gallery_nav.find( 'li:eq(0) img' ),
				$product_img_wrap = $product_gallery.find( pewc_vars.product_img_wrap ).eq( 0 ),
				$product_img      = $product_img_wrap.find( 'img' ).eq( 0 ),
				$product_link     = $product_img_wrap.find( 'a' ).eq( 0 );

			if( add_on_images.replace_image() ) {
				// create an attribute where we can track where a replaced image came from
				$product_img.attr( 'data-pewc-from-field', '' );

				// 3.17.2, find the first field that has a default value and not hidden, then auto replace main image with the selected swatch. if multiple are selected, pick the first one?
				$( document ).ready( function() {
					// we need to delay this so that the main image container takes the size of the original product image
					add_on_images.replace_main_with_default();
				});

				// when Optimised conditions is enabled, update_add_on_image is run first before a group is hidden, so we do this to reset a main image if the field is inside a group that becomes hidden
				$( 'body' ).on( 'pewc_group_visibility_updated', function( e, id, action ) {
					add_on_images.check_if_src_hidden( $product_img );
				});

				// 3.21.4, trigger replace main image if field is initially hidden by a condition
				$( 'body' ).on( 'pewc_field_visibility_updated', function( e, id, action ) {
					var pewc_item = $( '.pewc-item.' + id );
					// 3.26.6, changed condition from data-default-value to data-field-value to accommodate Edit Options
					if ( pewc_item.attr( 'data-field-value' ) != '' ) {
						// only do this if field has selected value that needs to replace the main image
						if ( pewc_item.data( 'field-type' ) === 'image_swatch' ) {
							// 3.24.6, find the first checked/selected element
							var field = pewc_item.find( 'input[name="' + id + '[]"]:checked' )[0];
						} else {
							var field = pewc_item.find( 'input[name="' + id + '[]"]' )[0];
						}
						var $form = pewc_item.closest( 'form' );
						add_on_images.update_add_on_image( $( field ), $form );
					}
				});

			}

			// If we've got parent/child swatches, then hide/show the correct child swatch
			$( '.pewc-swatch-parent .pewc-radio-form-field' ).on( 'change', this.change_child );

			// If we've got parent/child swatches, then ensure that all the child swatches are in sync
			$( '.pewc-swatch-child .pewc-radio-form-field' ).on( 'change', this.sync_children );

		},

		replace_image: function( field ) {
			// 3.17.2
			if( pewc_vars.replace_image ) {
				// global Replace main image, or the filter pewc_get_add_on_image_action, is used
				return pewc_vars.replace_image;
			} else if ( field ) {
				if ( field.hasClass( 'pewc-replace-main-image' ) ) {
					return true;
				}
			} else {
				// if we have just one field that has enabled Replace main image, activate
				if ( $( document ).find( '.pewc-replace-main-image' ).length > 0 ) {
					return true;
				}
			}
			return false;
		},

		save_original_src: function( $product_img ) {
			if ( $product_img.attr( 'data-pewc-old-src' ) ) {
				return; // already set, do nothing
			}
			// Set original src values
			$product_img.attr( 'data-pewc-old-src', $product_img.attr( 'src' ) );
			// 3.13.3, data-src is used by WooCommerce zoom image
			if ( $product_img.attr( 'data-src' ) ) {
				$product_img.attr( 'data-pewc-old-data-src', $product_img.attr( 'data-src' ) );
			} else {
				$product_img.attr( 'data-pewc-old-data-src', '' );
			}
			if ( $product_img.attr( 'srcset' ) ) {
				$product_img.attr( 'data-pewc-old-srcset', $product_img.attr( 'srcset' ) );
			} else {
				$product_img.attr( 'data-pewc-old-srcset', '' );
			}
			// photoswipe gallery
			if ( $product_img.attr( 'data-large_image' ) ) {
				$product_img.attr( 'data-pewc-old-data-large_image', $product_img.attr( 'data-large_image' ) );
			} else {
				$product_img.attr( 'data-pewc-old-data-large_image', '' );
			}
			if ( $product_img.attr( 'data-large_image_width' ) ) {
				$product_img.attr( 'data-pewc-old-data-large_image_width', $product_img.attr( 'data-large_image_width' ) );
			} else {
				$product_img.attr( 'data-pewc-old-data-large_image_width', '' );
			}
			if ( $product_img.attr( 'data-large_image_height' ) ) {
				$product_img.attr( 'data-pewc-old-data-large_image_height', $product_img.attr( 'data-large_image_height' ) );
			} else {
				$product_img.attr( 'data-pewc-old-data-large_image_height', '' );
			}

			// zoomImg
			var zoomImg = $product_img.closest( pewc_vars.product_img_wrap ).find( 'img.zoomImg' ).eq(0);
			if ( zoomImg ) {
				$product_img.attr( 'data-pewc-old-zoom', zoomImg.attr( 'src' ) );
			}
		},

		update_add_on_image: function( field, $form ) {
			
			var field_wrapper = $( field ).closest( '.pewc-item' );
			var is_layered = $( field_wrapper ).attr( 'data-field-layered' );

			if( ! add_on_images.replace_image( field_wrapper ) && is_layered != 'yes' ) {
				return;
			}

			var field_type = $( field_wrapper ).attr( 'data-field-type' );

			var $product        	= $form.closest( '.product' ),
				$product_gallery  	= $product.find( pewc_vars.product_gallery ),
				$layer_parent 		= pewc_vars.layer_parent,
				$gallery_nav      	= $product.find( '.flex-control-nav' ),
				$gallery_img      	= $gallery_nav.find( 'li:eq(0) img' ),
				$product_img_wrap 	= $product_gallery.find( pewc_vars.product_img_wrap ).eq( 0 ),
				$product_img      	= $product_img_wrap.find( 'img' ).eq( 0 ),
				$product_link     	= $product_img_wrap.find( 'a' ).eq( 0 );

			if( ! $( field_wrapper ).hasClass( 'pewc-has-field-image' ) && field_type != 'image_swatch' && field_type != 'select-box' ) {
				// the clicked field is not an image swatch, select box, or does not have a field image, but let's check if we need to replace the main image with the original one
				add_on_images.check_if_src_hidden( $product_img );
				return;
			}

			// 3.21.4, Ignore fields in hidden groups
			if ( $( field_wrapper ).closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden' ) ) {
				add_on_images.check_if_src_hidden( $product_img );
				return; 
			}

			// 3.21.4, Ignore hidden variation-dependent fields and hidden fields
			if( ( $( field_wrapper ).hasClass( 'pewc-variation-dependent' ) && ! $( field_wrapper ).hasClass( 'active' ) ) || $( field_wrapper ).hasClass( 'pewc-hidden-field' ) ) {
				add_on_images.check_if_src_hidden( $product_img );
				return;
			}

			var add_on_image_wrapper;
			var add_on_image_src;
			var add_on_image_large_image, add_on_image_large_image_width, add_on_image_large_image_height;
			var turn = 'off';
			if( field_type == 'checkbox' && $( field ).prop( 'checked' ) && add_on_images.replace_image( field_wrapper ) != 'replace_image_swatch_only' ) {
				turn = 'on';
				add_on_image_wrapper = $( field_wrapper ).find( '.pewc-item-field-image-wrapper' );
				add_on_image_src = $( add_on_image_wrapper ).attr( 'data-image-full-size' );
				add_on_image_srcset = add_on_image_src;
				// photoswipe gallery
				add_on_image_large_image = $( add_on_image_wrapper ).attr( 'data-image-full-size' );
				add_on_image_large_image_width = $( add_on_image_wrapper ).attr( 'data-large_image_width' );
				add_on_image_large_image_height = $( add_on_image_wrapper ).attr( 'data-large_image_height' );
			}
			if( field_type == 'image_swatch') {
				add_on_image_wrapper = field.closest( '.pewc-radio-image-wrapper' );

				if ( add_on_image_wrapper.hasClass('checked') ) {
					add_on_image_src = add_on_image_wrapper.find( 'img' ).attr( 'data-src' );
					add_on_image_srcset = add_on_image_src;
					// photoswipe gallery
					add_on_image_large_image = add_on_image_wrapper.find( 'img' ).attr( 'data-large_image' );
					add_on_image_large_image_width = add_on_image_wrapper.find( 'img' ).attr( 'data-large_image_width' );
					add_on_image_large_image_height = add_on_image_wrapper.find( 'img' ).attr( 'data-large_image_height' );

					if (add_on_image_src != undefined) {
						turn = 'on';
					}
				}
			} else if ( field_type == 'select-box' && add_on_images.replace_image( field_wrapper ) != 'replace_image_swatch_only' ) {
				// 3.13.7
				if ( field_wrapper.find( 'input.dd-selected-value' ).val() != '' ) {
					// check if the full image exists
					var src_image = field_wrapper.find( 'input.dd-selected-image-full' );
					if ( src_image.length > 0 ) {
						add_on_image_src = src_image.val();
					} else {
						src_image = field_wrapper.find( 'img.dd-selected-image' );
						add_on_image_src = src_image.attr( 'src' );
					}
					add_on_image_srcset = add_on_image_src;
					// photoswipe gallery
					add_on_image_large_image = add_on_image_src;
					add_on_image_large_image_width = src_image.attr( 'data-imgsrc-width' );
					add_on_image_large_image_height = src_image.attr( 'data-imgsrc-height' );

					if ( add_on_image_src != undefined) {
						turn = 'on';
					}
				}
			}

			if( is_layered == 'yes' ) {
				// This is a layered image so we are going to create/update layers
				var field_id = $( field_wrapper ).attr( 'data-field-id' );
				var layer_id = 'pewc-layer-' + field_id;
				var layer_index = $( field_wrapper ).attr( 'data-field-index' );
				// This is the wrapper, e.g. woocommerce-product-gallery__wrapper
				// Add our cloned image into a div within this wrapper
				// The wrapper can be absolutely positioned over the main image
				var swatch_src = $( add_on_image_wrapper ).find( 'img' ).attr( 'data-large_image' );
				// Check for an alternative image to replace the main image
				if( $( add_on_image_wrapper ).find( 'img' ).attr( 'data-alt_image' ).length > 0 ) {
					swatch_src = $( add_on_image_wrapper ).find( 'img' ).attr( 'data-alt_image' );
				}
				if( $( '.' + layer_id ).length === 0 && $( add_on_image_wrapper ).hasClass( 'checked' ) ) {
					// Get the selected swatch image src
					var layer_image = '<img class="pewc-layer-image" src="' + swatch_src + '">';
					// Make the layer
					// var layer = $product_img.clone().insertAfter( $product_img ).addClass( layer_id );
					$( $( '.' + $layer_parent ) ).append( '<div class="pewc-image-layer ' + layer_id + '" style="z-index:' + ( layer_index + 1 ) * 10 + '">' + layer_image + '</div>' );
				} else if( $( '.' + layer_id ).length !== 0 && $( add_on_image_wrapper ).hasClass( 'checked' ) ) {
					// Layer exists but need to update the image inside
					// swatch_src = $( add_on_image_wrapper ).find( 'img' ).attr( 'data-large_image' );
					$( '.' + layer_id ).find( 'img' ).attr( 'src', swatch_src );
				} else if( ! $( add_on_image_wrapper ).hasClass( 'checked' ) ) {
					$( '.' + layer_id ).remove();
				}

			} else if ( add_on_image_src && turn == 'on' ) {
				// maybe save original
				add_on_images.save_original_src( $product_img );
				$product_img.attr( 'data-pewc-from-field', $( field_wrapper ).attr( 'data-id' ) );
				$product_img.attr( 'src', add_on_image_src );
				$product_img.attr( 'srcset', add_on_image_srcset );
				$product_img.attr( 'data-src', add_on_image_src ); // 3.13.3, used by WC zoomImg
				// replace photoswipe gallery
				$product_img.attr( 'data-large_image', add_on_image_large_image );
				$product_img.attr( 'data-large_image_width', add_on_image_large_image_width );
				$product_img.attr( 'data-large_image_height', add_on_image_large_image_height );
				// replace zoom image
				var zoomImg = $product_img.closest( pewc_vars.product_img_wrap ).find( 'img.zoomImg' ).eq(0);
				if ( zoomImg ) {
					zoomImg.attr( 'src', add_on_image_src );
				}
			} else {
				//add_on_images.reset_main_image( $product_img );
				add_on_images.replace_main_with_default( $product_img, false ); // 3.24.7, let's try to find an active swatch first before resetting the main image
			}

			// 3.13.2 bring back the focus on the main image to see the selected image swatch
			if ( pewc_vars.replace_image_focus == 'yes' ) {
				var control_list_selector = '.' + pewc_vars.control_container + ' ' + pewc_vars.control_list;
				$( control_list_selector ).first().find( pewc_vars.control_element ).trigger( 'click' ); // 3.25.6, changed from click() to trigger('click') because click() is deprecated
				$( 'body' ).trigger( 'pewc_update_add_on_image_focus_on_main_image' ); // 3.23.1, for other sliders that have a different way of focusing on the first image e.g. Flickity
			}
		},

		// If we are using secondary images, then hide/show the correct child swatch field
		change_child: function( e ) {
			
			var parent_swatch = e.target;
			var parent_index = $( parent_swatch ).closest( '.pewc-item' ).find( '.pewc-radio-image-wrapper.checked input' ).attr( 'data-option-index' );
			if( ! isNaN( parent_index ) ) {
				$( '.pewc-swatch-child' ).addClass( 'pewc-visibility-hidden' );
				$( '.pewc-swatch-child-' + parent_index ).removeClass( 'pewc-visibility-hidden' );
				var active_layer_id = $( '.pewc-swatch-child-' + parent_index ).attr( 'data-field-id' );
				// Get the selected child index
				var selected_input = $( '.pewc-swatch-child' ).not( '.pewc-visibility-hidden' ).closest( '.pewc-item' ).find( '.pewc-radio-image-wrapper.checked input' );
				var index = $( selected_input ).attr( 'data-option-index' );
				// Trigger the child swatch
				// var selected = $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).prop( 'checked' );

				$( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper.pewc-radio-form-field' ).prop('checked', false);

				// When we change the child, we need to hide unwanted image layers on the main image
				$( '.pewc-layer-' + active_layer_id ).show();
				// Hide the other child swatch layers
				var child_swatch_ids = JSON.parse( pewc_vars.child_swatch_ids );
				for( var i in child_swatch_ids ) {
					if( child_swatch_ids[i] != active_layer_id ) {
						$( '.pewc-layer-' + child_swatch_ids[i] ).fadeOut( 150 );
					} 
				}
				// Find any layers that we need to hide
				// $( '.pewc-show-for' ).each( function( layer_index, element ) {
				// 	// This layer only shows when a specific field is visible
				// 	var show_for = $( this ).attr( 'data-show-for' );
				// 	var layer_id = $( this ).attr( 'data-field-id' );
				// 	if( show_for != active_layer_id ) {
				// 		$( '.pewc-layer-' + layer_id ).fadeOut( 150 );
				// 	} else {
				// 		$( '.pewc-layer-' + layer_id ).fadeIn( 150 );
				// 	}
				// });
				// $( '.pewc-image-layer' ).not( '.pewc-layer-' + active_layer_id ).fadeOut( 250 );
				

				// if( ! selected ) {
					$( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).addClass( 'here-' + parent_index + '-' + index ).trigger( 'click' );
					// selected = $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).prop( 'checked' );
					$( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).trigger( 'change' );
					// if( selected ) {
						// $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).prop( 'checked', false ).trigger( 'change' );
						// selected = $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).prop( 'checked' );
						// $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).addClass( 'here-' + parent_index + '-' + index ).trigger( 'click' );
						// selected = $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).prop( 'checked' );
					// }

				// }
				
			}

		},

		// If we've updated a child swatch, we need to ensure that other child swatches have the same value
		sync_children: function( e ) {

			// Get the active parent index
			var parent_index = $( '.pewc-swatch-parent' ).find( '.pewc-radio-image-wrapper.checked input' ).attr( 'data-option-index' );
			var child_swatch = e.target;
			// Get the index of the selected child swatch
			var index = $( child_swatch ).closest( '.pewc-item' ).find( '.pewc-radio-image-wrapper.checked input' ).attr( 'data-option-index' );
			if( ! isNaN( index ) ) {
				// Ensure that all other child swatch fields have the same item selected
				$( '.pewc-swatch-child' ).find( '.pewc-radio-image-wrapper' ).removeClass( 'checked' );
				$( '.pewc-swatch-child' ).find( '.pewc-radio-image-wrapper-' + index ).addClass( 'checked' ).find( 'input' );
				// Need to ensure that the correct main image is displayed
				// Trigger a click on the currently visible selected swatch
				var input = $( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' );
				$( '.pewc-swatch-child-' + parent_index ).find( '.pewc-radio-image-wrapper-' + index + ' input' ).trigger( 'click' );
			}

		},

		check_if_src_hidden: function( $product_img ) {
			if ( $product_img.attr('data-pewc-from-field') != '' ) {

				// main image is using an image from a field, check if the field where it came from is currently hidden. if so, put back the original main image
				var pewc_from_field = $( ".pewc-item."+$product_img.attr('data-pewc-from-field') );

				if ( pewc_from_field.hasClass( 'pewc-hidden-field') || pewc_from_field.closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden') || ( pewc_from_field.hasClass( 'pewc-variation-dependent' ) && ! pewc_from_field.hasClass( 'active' ) ) ) {
					// field is hidden, reset image
					// 3.21.4, added variation-dependent fields
					//add_on_images.reset_main_image( $product_img );
					add_on_images.replace_main_with_default( $product_img ); // 3.24.7, let's try to find an active swatch first before resetting the main image
				}
			}
		},

		reset_main_image: function ( $product_img ) {
			$product_img.attr( 'data-pewc-from-field', '' );
			$product_img.attr( 'src', $product_img.attr( 'data-pewc-old-src' ) );
			$product_img.attr( 'srcset', $product_img.attr( 'data-pewc-old-srcset' ) );
			$product_img.attr( 'data-src', $product_img.attr( 'data-pewc-old-data-src' ) ); // 3.13.3
			// photoswipe gallery
			$product_img.attr( 'data-large_image', $product_img.attr( 'data-pewc-old-data-large_image' ) );
			$product_img.attr( 'data-large_image_width', $product_img.attr( 'data-pewc-old-data-large_image_width' ) );
			$product_img.attr( 'data-large_image_height', $product_img.attr( 'data-pewc-old-data-large_image_height' ) );
			// zoom image
			var zoomImg = $product_img.closest( pewc_vars.product_img_wrap ).find( 'img.zoomImg' ).eq(0);
			if ( zoomImg ) {
				zoomImg.attr( 'src', $product_img.attr( 'data-pewc-old-zoom' ) );
			}
		},

		replace_main_with_default: function( $product_img='', is_layered=true ) {
			// 3.17.2
			var replace_fields = [ 'image_swatch', 'select-box', 'checkbox' ];
			var replaced = false;

			$( '.pewc-item' ).each( function( index, element ) {
				// 3.20.1
				var layered_image = $(this).hasClass( 'pewc-layered-image' );

				// Replace main image is not active for this field, skip
				// 3.27.2, added ! layered_image condition, so that layer images work even if they don't enable Replace main image on the field setting
				if ( ! add_on_images.replace_image( $(this) ) && ! layered_image ) {
					return;
				}

				// 3.25.4, skip layered images when resetting normal swatch fields?
				if ( layered_image && ! is_layered ) {
					return;
				}

				// Main image has been replaced, we can skip
				// 3.20.1, don't skip if this is a layered image
				if ( replaced && ! layered_image ) {
					return;
				}

				// Ignore fields in hidden groups
				if ( $( this ).closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden' ) ) {
					return; 
				}

				// Ignore hidden variation-dependent fields and hidden fields
				if( ( $( this ).hasClass( 'pewc-variation-dependent' ) && ! $( this ).hasClass( 'active' ) ) || $( this ).hasClass( 'pewc-hidden-field' ) ) {
					return;
				}

				if ( replace_fields.indexOf( $( this ).attr( 'data-field-type' ) ) > -1 && $( this ).attr( 'data-field-value' ) != '' ) {
					// 3.22.1, replaced data-default value with data-field-value in the condition so that the main image is also replaced when editing add-ons via Edit options on the cart
					var field_type = $( this ).attr( 'data-field-type' );
					if ( field_type  == 'image_swatch' ) {
						// find the first checked input, that must be the default
						$( this ).find( '.pewc-radio-form-field' ).each( function( index, element ){
							// 3.20.1, allow multiple layered images with default values
							if ( $( this ).is( ':checked' ) && ( ! replaced || layered_image ) ) {
								// 3.26.15, only replace if option has an image
								if ( $( this ).closest( '.pewc-radio-image-wrapper' ).find( 'img' ).attr( 'data-src' ) ) {
									add_on_images.update_add_on_image( $( this ), $(this).closest( 'form' ) );
									replaced = true;
									return;
								}
							}
						});
					} else if ( field_type == 'checkbox' ) {
						if ( $( this ).find( '.pewc-form-field' ).is( ':checked' ) ) {
							add_on_images.update_add_on_image( $( this ).find( '.pewc-form-field' ), $(this).closest( 'form' ) );
							replaced = true;
							return;
						}
					} else if ( field_type == 'select-box' ) {
						add_on_images.update_add_on_image( $( this ).find( 'select.pewc-form-field' ), $(this).closest( 'form' ) );
						replaced = true;
						return;
					}
				}
			});

			// 3.24.7, if we didn't find a swatch, reset the main image
			if ( ! replaced && $product_img != '' ) {
				add_on_images.reset_main_image( $product_img );
			}
		},

	}

	add_on_images.init();

	var tooltips = {

		init: function() {

			if( pewc_vars.enable_tooltips == 'yes' && ! pewc_vars.dequeue_tooltips ) {
				$( '.tooltip' ).tooltipster(
					{
						theme: 'tooltipster-shadow',
		        		side: 'right',
						contentAsHTML: pewc_vars.contentAsHTML,
						autoClose : pewc_vars.autoClose,
						interactive : pewc_vars.interactive,
						hideOnClick : pewc_vars.hideOnClick,
						trigger : pewc_vars.trigger,
						triggerOpen : pewc_vars.triggerOpen,
						triggerClose: pewc_vars.triggerClose
					}
				);
			}

		}

	}

	tooltips.init();

	var quickview = {

		init: function() {
			$( 'body' ).on( 'click', '.pewc-show-quickview', this.show_quickview );
			$( 'body' ).on( 'click', '#pewc-quickview-background, .pewc-close-quickview', this.hide_quickview );
		},

		show_quickview: function( e ) {
			e.stopPropagation();
			if( pewc_vars.quickview == 'yes' ) {
				// Open the link in a new tab
				e.preventDefault();
				$( 'body' ).addClass( 'pewc-quickview-active' );
			}
			
			// $( '.pewc-quickview-product-wrapper' ).hide();
			$( '#pewc-quickview-' + $(this).closest( 'li.pewc-item' ).attr( 'data-id' ) + '_' + $( this ).attr( 'data-child-product-id' ) ).css( 'left', '50%' );
		},

		hide_quickview: function( e ) {
			 e.preventDefault();
			 $( 'body' ).removeClass( 'pewc-quickview-active' );
			 $( '.pewc-quickview-product-wrapper' ).css( 'left', '-5000px' );
		}

	}

	quickview.init();

})(jQuery);

// 3.21.4, moved outside so that other scripts can use this function
function pewc_wc_price_without_currency( price ) {

	var price_settings = {
		decimal: pewc_vars.decimal_separator,
		thousand: pewc_vars.thousand_separator,
		precision: pewc_vars.decimals,
		format: '%v'
	};

	if ( pewc_vars.price_trim_zeros == 'yes' && price == parseInt( price ) ) {
		// 3.21.3, trim zeros if filter is active
		price_settings.precision = 0;
	}

	// since 3.12.0
	price = accounting.formatMoney( price, price_settings );

	return price;

}

// 3.21.4, moved outside so that other scripts can use this function
function pewc_wc_price( price, price_only=false, update_pewc_total_calc_price=true ) {

	if ( update_pewc_total_calc_price ) {
		jQuery( '#pewc_total_calc_price' ).val( price ); // Used in Bookings for WooCommerce
	}

	// since 3.12.0
	var currency_html = '<span class="woocommerce-Price-currencySymbol">%s</span>';
	var currency_format = '';
	if ( price_only ) {
		currency_html = '%s';
	}

	if( pewc_vars.currency_pos == 'left' ) {
		currency_format = currency_html+'%v';
	} else if( pewc_vars.currency_pos == 'right' ) {
		currency_format = '%v'+currency_html;
	} else if( pewc_vars.currency_pos == 'left_space' ) {
		currency_format = currency_html+' %v';
	} else if( pewc_vars.currency_pos == 'right_space' ) {
		currency_format = '%v '+currency_html;
	}

	var price_settings = {
		symbol: pewc_vars.currency_symbol,
		decimal: pewc_vars.decimal_separator,
		thousand: pewc_vars.thousand_separator,
		precision: pewc_vars.decimals,
		format: currency_format
	};

	if ( pewc_vars.price_trim_zeros == 'yes' && price == parseInt( price ) ) {
		// 3.21.3, trim zeros if filter is active
		price_settings.precision = 0;
	}

	price = accounting.formatMoney( price, price_settings );

	if ( ! price_only ) {
		return '<span class="woocommerce-Price-amount amount"><bdi>' + price + '</bdi></span>';
	}

	return price;

}

// 3.21.7
function pewc_wcfad_apply_discount() {
	return typeof pewc_wcfad !== 'undefined' && pewc_wcfad.apply_discount();
}

// 3.27.0
function pewc_is_booking_product() {

	return ( jQuery( '#num_units_int' ).length > 0 );

}

// 3.27.0, for compatibility with Bookings for WooCommerce
function pewc_get_quantity( qty=1, type='' ) {

	if( jQuery( 'form.cart .quantity .qty' ).val() && 'one-only' !== type ) {
		qty = jQuery( 'form.cart .quantity .qty' ).val();
	} else if ( pewc_is_booking_product() ) {
		// for Booking products, where the quantity field is not available
		qty = jQuery( '#num_units_int' ).val();
		if ( jQuery( '#num_bookings' ).length > 0 && ! isNaN( jQuery( '#num_bookings' ).val() ) && 'linked' === type ) {
			// also multiply by quantity?
			qty = qty * jQuery( '#num_bookings' ).val();
		}
	}

	return qty;

}
