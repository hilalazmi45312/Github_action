(function($) {

	$(document).ready(function() {

		$( 'body' ).find( '.pewc-variable-child-product-wrapper select' ).each( function() {
			update_variation( $( this ) );
			// 3.26.3, update column thumbnail with the default variant
			update_column_thumbnail( $( this ) );
		});
		$( 'body' ).on( 'pewc_update_child_quantity', function( e, el ) {
			// This is triggered when the quantity field for a child product is updated
			update_checkbox_image_wrapper( el );

			// 3.26.3, update column thumbnail with the selected variant
			var column_select = $( el ).closest( '.pewc-checkbox-image-wrapper' ).find( '.pewc-variable-child-select' );
			if ( column_select.length > 0 ) {
				update_column_thumbnail( column_select );
			}
		});
		$( '.pewc-column-form-field' ).on( 'change', function() {
			update_checkbox_image_wrapper( $( this ) );

			// 3.26.3, update column thumbnail with the selected variant
			var column_select = $( this ).closest( '.pewc-checkbox-image-wrapper' ).find( '.pewc-variable-child-select' );
			if ( column_select.length > 0 ) {
				update_column_thumbnail( column_select );
			}
		});

		$( '.pewc-add-button' ).on( 'click', function( e ) {
			e.preventDefault();
			e.stopPropagation();
			var button = $( this );
			var wrapper = $( this ).closest( '.pewc-checkbox-image-wrapper' );
			var checkbox = $( wrapper ).find( '.pewc-column-form-field' );
			var quantity = $( wrapper ).find( 'input.pewc-child-quantity-field' );
			var manage_stock = $( wrapper ).data( 'manage-stock' );
			if( manage_stock && $( wrapper ).hasClass( 'pewc-simple-child-product-wrapper' ) ) {
				var stock = $( quantity ).attr( 'max' );
				if( ! stock ) stock = true;
			} else if( manage_stock ) {
				var stock = $( wrapper ).find( '.pewc-variable-child-select option:selected' ).data( 'stock' );
				if ( ! stock ) stock = true; // 3.26.11, if only some variants are stock managed, other variants will have blank data-stock, so allow them to be added
			}

			var independent_quantities = $( button ).closest( '.pewc-column-wrapper' ).hasClass( 'products-quantities-independent' );

			// 3.26.11
			var default_quantity = 1;
			if ( $( quantity ).attr( 'data-default-quantity' ) > 0 ) {
				default_quantity = quantity.attr( 'data-default-quantity' );
			}

			if( $( wrapper ).hasClass( 'checked' ) ) {
				$( checkbox ).prop( 'checked', false );
				$( wrapper ).removeClass( 'checked' );
				$( quantity ).val( 0 ).trigger( 'change' );
			} else {
				$( checkbox ).prop( 'checked', true );
				$( wrapper ).addClass( 'checked' );
				// If stock is available, or if we're not managing stock so don't care
				if( stock || ! manage_stock ) {
					// Update quantity field
					$( quantity ).val( default_quantity ).trigger( 'change' );
				}
			}
			if ( ! independent_quantities ) {
				// trigger checkbox change so that conditions that rely on this field is triggered as well. independent quantities have its own trigger on the quantity field, so we do this only if this field's quantity is not independent
				$( checkbox ).trigger( 'change' );
			}

			// 3.26.3, update column thumbnail with the selected variant, only do this for variable products
			if ( $( wrapper ).find( '.pewc-variable-child-select' ).length > 0 ) {
				update_column_thumbnail( $( wrapper ).find( '.pewc-variable-child-select' ) );
			}

			$( 'body' ).trigger( 'pewc_add_button_clicked' );
		});

		$( '.pewc-variable-child-select' ).on( 'change click', function( e ) {
			e.stopPropagation();
			var select = $( this );
			// If we change the variation, then select the product
			var checkbox = $( select ).closest( '.pewc-checkbox-image-wrapper' ).find( 'input[type=checkbox]' ).prop( 'checked' , true );
			var quantity = $( select ).closest( '.pewc-checkbox-image-wrapper' ).find( 'input.pewc-child-quantity-field' );

			// 3.26.11
			var default_quantity = 1;
			if ( $( quantity ).attr( 'data-default-quantity' ) > 0 ) {
				default_quantity = quantity.attr( 'data-default-quantity' );
			}

			if( $( quantity ).val() == 0 ) {
				$( quantity ).val( default_quantity );
			}
			$( quantity ).attr( 'max', '' ); // 3.26.11, reset
			update_checkbox_image_wrapper( $( select ) );

			// Check available stock
			var stock = $( select ).find( ':selected' ).data( 'stock' );
			if( stock != undefined && stock != '' ) {
				// Restrict quantity field to available stock
				// 3.26.11, added stock != '' and stock < default_quantity to condition
				var independent_quantities = $( select ).closest( '.pewc-column-wrapper' ).hasClass( 'products-quantities-independent' );
				$( quantity ).attr( 'max', stock );
				if ( stock < $( quantity ).val() ) {
					$( quantity ).val( stock );
				}
			}

			update_variation( select );
			// 3.26.3, update column thumbnail with the selected variant
			update_column_thumbnail( select );
		});

		function update_variation( select ) {
			var variation_id = $( select ).val();
			// 3.21.4, if FD updated the prices, use .attr because .data is cached
			if ( $( select ).attr( 'data-wcfad-updated' ) == 'yes' ) {
				var variation_data = JSON.parse( $( select ).attr( 'data-product_variations' ) );
			} else {
				var variation_data = $( select ).data( 'product_variations' );
			}
			for ( var i = 0; i < variation_data.length; i++ ) {
				var variation = variation_data[i];
				if( variation.variation_id == variation_id ) {
					var wrapper = $( select ).closest( '.pewc-checkbox-desc-wrapper' );
					// Found the attribute
					$( wrapper ).find( '.pewc-column-description' ).html( variation.variation_description + '<p class="pewc-variation-price">' + variation.price_html + '</div>' + variation.availability_html );
					// $( wrapper ).find( '.pewc-column-price-wrapper' ).html( variation.price_html );
					break;
				}
			}
		}

		function update_checkbox_image_wrapper( el ) {
			var id = $( el ).closest( '.pewc-checkbox-image-wrapper' ).attr( 'data-option-id' );
			var wrapper = $( el ).closest( '.pewc-checkbox-image-wrapper' );
			var checked = $( wrapper ).find( '.pewc-column-form-field' ).prop( 'checked' );
			if( checked == true ) {
				$( '.' + id ).addClass( 'checked' );
			} else {
				$( '.' + id ).removeClass( 'checked' );
			}
			$( 'body' ).trigger( 'pewc_force_update_total_js' ); // 3.21.4
		}

		// 3.26.3, replace variable thumbnail with the selected variant's thumbnail
		function update_column_thumbnail( select ) {
			if ( ! select.hasClass( 'pewc-replace-variable-image' ) ) {
				// 3.26.4, if the class doesn't exist, then this feature isn't activated
				return;
			}
			var selected_option = $( select ).find( ':selected' );
			var wrapper = $( select ).closest( '.pewc-checkbox-image-wrapper' );
			var column_image = $( wrapper ).find( 'label img.attachment-thumbnail' );
			if ( column_image.length < 1 ) {
				// maybe product does not have a main image
				column_image = $( wrapper ).find( 'label img.woocommerce-placeholder' );
			}

			if ( $( wrapper ).hasClass( 'checked' ) && selected_option.length > 0 ) {
				if ( $( column_image ).attr( 'data-original-column-image' ) == undefined ) {
					$( column_image ).attr( 'data-original-column-image', $( column_image ).attr( 'src' ) );
					$( column_image ).attr( 'data-original-column-image-srcset', $( column_image ).attr( 'srcset' ) );
				}
				if ( $( selected_option ).attr( 'data-variation-image' ) != '' ) {
					$( column_image ).attr( 'src', $( selected_option ).attr( 'data-variation-image' ) );
					$( column_image ).attr( 'srcset', '' ); // so that the thumbnail above is displayed
				}
			} else {
				// put back original image
				if ( $( column_image ).attr( 'data-original-column-image' ) != undefined ) {
					$( column_image ).attr( 'src', $( column_image ).attr( 'data-original-column-image' ) );
					$( column_image ).attr( 'srcset', $( column_image ).attr( 'data-original-column-image-srcset' ) );
				}
			}
		}

		var swatches = {

			init: function() {

				$( 'body' ).on( 'click', '.pewc-variation-swatch a', this.update_swatch_wrapper );
				$( 'body' ).on( 'click', '.pewc-swatches-toggle', this.toggle_swatch );

			},

			toggle_swatch: function( e ) {

				e.preventDefault();
				var wrapper = $( this ).closest( '.pewc-swatches-child-product-outer' );
				$( wrapper ).toggleClass( 'visible-swatch' );
			},

			update_swatch_wrapper: function( e ) {

				e.preventDefault();
				var swatch = $( this ).closest( '.pewc-variation-swatch' );
				var variation_id = $( swatch ).attr( 'data-variation-id' );
				var wrapper = $( this ).closest( '.pewc-swatches-child-product-outer' );
				$( wrapper ).find( '.pewc-variation-swatch img' ).removeClass( 'active-swatch' );
				$( swatch ).find( 'img' ).addClass( 'active-swatch' );
				var update_selected_id = $( wrapper ).find( 'input.pewc-child-variant' ).val( variation_id );
				var image = $( swatch ).find( 'img' ).attr( 'src' );
				var viewer_image = $( swatch ).attr( 'data-viewer-image' );
				var price = $( swatch ).attr( 'data-option-cost' );
				var name = $( swatch ).attr( 'data-name' );
				var sku = $( swatch ).attr( 'data-sku' );
				if( image ) {
					$( wrapper ).find( '.pewc-child-thumb img' ).attr( 'src', image );
				}
				if( viewer_image ) {
					$( wrapper ).find( '.pewc-viewer-thumb img' ).attr( 'src', viewer_image );
				}
				if( price ) {
					$( wrapper ).find( '.pewc-child-name input' ).attr( 'data-option-cost', price );
					var currency = $( wrapper ).find( '.pewc-child-name .pewc-swatches-main-title span.pewc-variation-price' ).html( pewc_get_wc_price( price ) );
				}
				if( name ) {
					$( wrapper ).find( '.pewc-child-name input' ).attr( 'data-field-label', name );
					$( wrapper ).find( 'span.pewc-variation-name' ).html( name );
					$( wrapper ).find( '.pewc-viewer-title' ).html( name );
				}
				if( sku ) {
					$( wrapper ).find( '.pewc-variation-sku' ).html( sku );
					$( wrapper ).find( '.pewc-viewer-sku' ).html( sku );
				}
				$( wrapper ).find( '.pewc-child-quantity-field' ).trigger( 'change' ); // trigger pewc_update_total_js

			},

		};

		swatches.init();

		var grid = {

			init: function() {

				$( 'body' ).on( 'change keyup', '.grid-layout .pewc-grid-quantity-field', this.update_grid_quantities );

				// 3.25.6, check if any quantities have values, e.g. when editing add-ons or if there are defaults
				$( '.pewc-item-products-grid' ).each( function( index, element ){
					grid.update_grid_totals( element );
				});

			},

			update_grid_quantities: function( e ) {

				e.preventDefault();
				var field = $( this ).closest( '.pewc-item' );
				grid.update_grid_totals( field );

			},

			// 3.25.6, separated this into a function so that we can call this on page load
			update_grid_totals: function( field ) {

				var field_price = 0;
				var total = 0;
				var selected_variations = '';
				var quantity_symbol = $( field ).attr( 'data-variation-quantity-symbol' );
				var separator = $( field ).attr( 'data-variation-separator' );

				$( field ).find( '.pewc-grid-quantity-field' ).each( function() {
					var child_price = $( this ).attr( 'data-option-cost' );
					// 3.21.4, compatibility with FD
					if ( parseFloat( $(this).attr('data-wcfad-price') ) > 0 ) {
						child_price = parseFloat( $(this).attr('data-wcfad-price') );
					}
					var var_quantity = parseInt( $( this ).val() );
					if ( var_quantity > 0 ) {
						child_price = var_quantity * child_price;
						field_price += child_price;
						total += var_quantity;
						if ( selected_variations != '' ) {
							selected_variations += separator;
						}
						selected_variations += $( this ).attr( 'data-pewc-variation-name' ) + quantity_symbol + var_quantity;
					}
				});

				$( field ).attr( 'data-price', field_price );
				$( field ).attr( 'data-field-price', field_price );
				$( field ).attr( 'data-field-value', selected_variations );
				if ( total > 0 ) {
					if ( ! $( field ).hasClass( 'pewc-active-field' ) ) {
						$( field ).addClass( 'pewc-active-field' );
					}
				} else {
					$( field ).removeClass( 'pewc-active-field' );
				}
				$( '#pewc-grid-total-variations' ).val( total );
				$( 'body' ).trigger( 'pewc_force_update_total_js' );

			}

		};

		grid.init();

		function pewc_get_wc_price( price ) {
			var return_html, price_html, formatted_price;
			if( pewc_vars.currency_pos == 'left' ) {
				formatted_price = pewc_vars.currency_symbol + '&#x200e;' + price;
			} else if( pewc_vars.currency_pos == 'right' ) {
				formatted_price = price + pewc_vars.currency_symbol + '&#x200f;';
			} else if( pewc_vars.currency_pos == 'left_space' ) {
				formatted_price = pewc_vars.currency_symbol + '&#x200e;&nbsp;' + price;
			} else if( pewc_vars.currency_pos == 'right_space' ) {
				formatted_price = price + '&nbsp;' + pewc_vars.currency_symbol + '&#x200f;';
			}
			formatted_price = formatted_price.replace('.',pewc_vars.decimal_separator);
			price_html = '<span class="woocommerce-Price-currencySymbol">' + formatted_price + '</span>';
			return_html = '<span class="woocommerce-Price-amount amount">' + price_html + '</span>';

			$('#pewc_total_calc_price').val( price ); // Used in Bookings for WooCommerce

			return return_html;
		}

	});
})(jQuery);
