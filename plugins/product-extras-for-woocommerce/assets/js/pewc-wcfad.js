// since 3.12.0, for compatibility with Dynamic Pricing and Discount Rules
// 3.21.4, separated to its own file so that it doesn't always gets loaded
const pewc_wcfad = {

	adjusting_price: false,
	counting_quantities: false,
	product_id: '',
	tiers: [],
	applies_to: '',
	simple_rules: [],
	has_child_products: false,

	init: function() {

		// 3.21.5, prevent undefined warning for wcfad_all_tiers
		if ( ! pewc_wcfad.apply_discount() ) {
			return;
		}

		pewc_wcfad.update_wcfad_tiers();

		// 3.21.6, #wcfad_product_id is updated when selecting variants, so update tiers as well
		jQuery( document ).on( 'show_variation', function( event, variation, purchasable ) {
			pewc_wcfad.update_wcfad_tiers();
			jQuery( 'body' ).trigger( 'pewc_force_update_total_js' );
		});
		jQuery( document ).on( 'hide_variation', function( event, variation, purchasable ){
			pewc_wcfad.update_wcfad_tiers();
			jQuery( 'body' ).trigger( 'pewc_force_update_total_js' );

		});

		if ( pewc_wcfad.tiers == undefined ) {
			return;
		}

		// this is to ensure that the prices are updated after we count the quantities. In pewc.js, the grid quantity field is excluded from this event
		jQuery( 'form.cart' ).on('keyup input change paste', 'input.pewc-grid-quantity-field', function(){
			jQuery( 'body' ).trigger( 'pewc_force_update_total_js' );
		});

		// 3.21.5, check if product has child products, if so, always adjust price for child products even if filter pewc_disable_wcfad_on_addons is false
		if ( jQuery( '.pewc-item-products, .pewc-item-product-categories' ).length > 0 ) {
			pewc_wcfad.has_child_products = true;
		}
	},

	// 3.21.6, moved process to a function
	update_wcfad_tiers: function() {

		pewc_wcfad.product_id = jQuery( '#wcfad_product_id' ).val();
		pewc_wcfad.tiers = wcfad_all_tiers[ pewc_wcfad.product_id ];

		if ( pewc_wcfad.tiers == undefined ) {
			return;
		}

		if ( pewc_wcfad.tiers['tiers'] != undefined ) {
			// since AOU 3.21.4 and DPDR 2.1.1
			if ( pewc_wcfad.tiers['simple_rules'] != undefined ) {
				pewc_wcfad.simple_rules = pewc_wcfad.tiers['simple_rules']; // 3.25.5
			}
			pewc_wcfad.applies_to = pewc_wcfad.tiers['applies_to'];
			pewc_wcfad.tiers = pewc_wcfad.tiers['tiers'];
		}

	},

	reset_product_price: function( formula, calc_field_id ) {

		if ( formula.includes( "{product_price}" ) && jQuery( '.pewc-item.pewc-field-'+calc_field_id+' input.pewc-action' ).val() == 'price' && pewc_wcfad.apply_discount() ) {
			return true;
		} else {
			return false;
		}

	},

	apply_discount: function() {

		if ( window.wcfad_all_tiers != undefined && ( pewc_vars.disable_wcfad_label != 'yes' || pewc_vars.disable_wcfad_table != 'yes' ) && ( pewc_vars.disable_wcfad_on_addons != 'yes' || pewc_wcfad.has_child_products ) ) {
			return true;
		} else {
			return false;
		}

	},

	adjust_price: function( total_price ) {

		if ( pewc_wcfad.adjusting_price ) {
			return;
		} else {
			pewc_wcfad.adjusting_price = true;
		}

		var qty = 1;
		if( jQuery('form.cart .qty').val() ) {
			qty = parseFloat( jQuery('form.cart .qty').val() );
		}
		var wcfad_product_id = pewc_wcfad.product_id; //jQuery( '#wcfad_product_id' ).val();
		var wcfad_tiers = pewc_wcfad.tiers; //wcfad_all_tiers[ wcfad_product_id ];
		var wcfad_applies_to = pewc_wcfad.applies_to;
		var wcfad_simple_rules = false;
		if ( pewc_wcfad.simple_rules ) {
			wcfad_simple_rules = pewc_wcfad.simple_rules; // 3.25.5
		}
		var wcfad_prices = [];
		if ( window.wcfad_all_prices != undefined ) {
			wcfad_prices = wcfad_all_prices[ wcfad_product_id ];
		}
		var wcfad_perc = 1;
		var wcfad_tier_perc = 1;
		var wcfad_tier_type = '';
		var wcfad_tier_new_price = 0;
		var wcfad_tier_addon_price = 0;

		if ( wcfad_tiers != undefined && wcfad_tiers.length > 0 ) {

			// 3.21.4, also add the quantity of independent products if they are included in the discount
			qty += pewc_wcfad.get_child_product_quantities( wcfad_applies_to );

			// loop through the tiers
			for ( var tier_index in wcfad_tiers ) {
				var wcfad_tier = wcfad_tiers[tier_index];

				if( ! wcfad_tier.max || isNaN( wcfad_tier.max ) ) {
					wcfad_tier.max = 99999999999;
				}

				// we only do this for percentage type adjustments
				if ( wcfad_tier.type.substr(0,11) == 'percentage-' ) {

					if ( ! isNaN( parseFloat( wcfad_tier.amount ) ) ) {
						wcfad_tier_perc = parseFloat( wcfad_tier.amount )/100;
					} else {
						wcfad_tier_perc = 0; // no discount
					}

					if( qty >= parseInt( wcfad_tier.min ) && qty <= parseInt( wcfad_tier.max ) ) {
						// this matches the current selection get percentage
						wcfad_perc = wcfad_tier_perc;
						wcfad_tier_type = wcfad_tier.type;
					}

					// now do the following to adjust the prices in the pricing table
					if ( wcfad_prices.length > 0 ) {
						if ( wcfad_tier.type == 'percentage-discount' ) {
							wcfad_tier_addon_price = total_price - parseFloat( total_price * wcfad_tier_perc );
						} else {
							wcfad_tier_addon_price = total_price + parseFloat( total_price * wcfad_tier_perc );
						}

						// since 3.12.0. If a calc field sets the product price, use this to adjust the prices on the pricing table
						if ( jQuery( '#pewc_calc_set_price').attr( 'data-calc-set' ) == 1 ) {
							wcfad_tier_new_price = parseFloat( wcfad_tier_addon_price );
						} else {
							wcfad_tier_new_price = parseFloat( wcfad_prices[tier_index].value ) + parseFloat( wcfad_tier_addon_price );
						}
						if ( pewc_vars.disable_wcfad_table != 'yes' ) {
							jQuery('div.wcfad-variation-table-'+wcfad_product_id+' td.tier_'+tier_index).html( pewc_wc_price( wcfad_tier_new_price.toFixed( pewc_vars.decimals ), true ) );
						}
					}

				} else if ( wcfad_tier.type.substr(0,6) == 'fixed-' && wcfad_prices.length > 0 ) {

					// 3.25.5, if this exists, the filter apply_simple_rules_after_product_rules is active, apply it to the total add-on price first?
					if ( wcfad_simple_rules ) {
						for ( var simple_rule_id in wcfad_simple_rules ) {
							var simple_rule = wcfad_simple_rules[simple_rule_id];
							var simple_rule = wcfad_simple_rules[simple_rule_id];
							if ( simple_rule.tiers[0].type.substr(0,11) == 'percentage-' ) {
								wcfad_tier_type = simple_rule.tiers[0].type;
								if ( ! isNaN( parseFloat( simple_rule.tiers[0].amount ) ) ) {
									wcfad_perc = parseFloat( simple_rule.tiers[0].amount )/100;
								} else {
									wcfad_perc = 0; // no discount
								}
								break; // only get the first simple rule for now?
							}
						}
						if ( wcfad_tier_type == 'percentage-discount' ) {
							wcfad_tier_addon_price = total_price - parseFloat( total_price * wcfad_perc );
						} else {
							wcfad_tier_addon_price = total_price + parseFloat( total_price * wcfad_perc );
						}
						// since 3.12.0. If a calc field sets the product price, use this to adjust the prices on the pricing table
						if ( jQuery( '#pewc_calc_set_price').attr( 'data-calc-set' ) == 1 ) {
							wcfad_tier_new_price = parseFloat( wcfad_tier_addon_price );
						} else {
							wcfad_tier_new_price = parseFloat( wcfad_prices[tier_index].value ) + parseFloat( wcfad_tier_addon_price );
						}
						if ( pewc_vars.disable_wcfad_table != 'yes' ) {
							jQuery('div.wcfad-variation-table-'+wcfad_product_id+' td.tier_'+tier_index).html( pewc_wc_price( wcfad_tier_new_price.toFixed( pewc_vars.decimals ), true ) );
						}
					}

					// for fixed adjustments, base price has already been adjusted, so just add the total add-on price
					wcfad_tier_new_price = parseFloat( wcfad_prices[tier_index].value ) + parseFloat( total_price );
					if ( pewc_vars.disable_wcfad_table != 'yes' ) {
						jQuery('div.wcfad-variation-table-'+wcfad_product_id+' td.tier_'+tier_index).html( pewc_wc_price( wcfad_tier_new_price.toFixed( pewc_vars.decimals ), true ) );
					}

				}
			}

			if ( pewc_vars.disable_wcfad_label != 'yes' ) {
				if ( wcfad_tier_type != '' ) {
					// adjust
					total_price = pewc_wcfad.calculate_adjusted_price( wcfad_tier_type, total_price, wcfad_perc ); // 3.21.4
				} else if ( wcfad_simple_rules ) {
					// 3.25.5, perhaps the product has a product-level fixed fee rule, and apply_simple_rules_after_product_rules is active, apply the Simple rule
					for ( var simple_rule_id in wcfad_simple_rules ) {
						var simple_rule = wcfad_simple_rules[simple_rule_id];
						if ( simple_rule.tiers[0].type.substr(0,11) == 'percentage-' ) {
							wcfad_tier_type = simple_rule.tiers[0].type;
							if ( ! isNaN( parseFloat( simple_rule.tiers[0].amount ) ) ) {
								wcfad_perc = parseFloat( simple_rule.tiers[0].amount )/100;
							} else {
								wcfad_perc = 0; // no discount
							}
							break; // only get the first simple rule for now?
						}
					}
				}
			}

			// 3.21.4, adjust field prices, including option prices and child product prices
			jQuery( '#pewc-wcfad-total-child-product-quantity' ).attr( 'data-qty-checked', qty ); // keep track of the quantity that we have checked, so that we know not to do this repeatedly
			if ( pewc_vars.disable_wcfad_label != 'yes' ) {
				pewc_wcfad.adjust_field_prices( wcfad_tier_type, wcfad_perc, qty );
			}

		}

		pewc_wcfad.adjusting_price = false;

		return total_price;

	},

	// 3.21.4, adjust each AOU field prices display on the product page
	adjust_field_prices: function( type, percentage, quantity ) {

		// loop through all AOU fields
		jQuery( '.pewc-item' ).each( function(){

			if ( pewc_vars.disable_wcfad_on_addons === 'yes' && ! jQuery(this).hasClass( 'pewc-item-products' ) && ! jQuery(this).hasClass( 'pewc-item-product-categories' ) ) {
				return; // 3.21.5, DPDR is disabled on add-on fields, but we always adjust child products because discounts are applied on them in the cart
			}

			if ( jQuery(this).attr( 'data-wcfad-adjusted-quantity' ) == quantity && 
				 jQuery(this).attr( 'data-field-type' ) != 'select-box' && 
				 ! jQuery(this).hasClass( 'pewc-item-products-swatches' ) && 
				 ! jQuery(this).hasClass( 'pewc-item-products-column' ) && 
				 ! jQuery(this).hasClass( 'pewc-item-products-grid' )
			) {
				// we have done this before for this quantity, return
				// if select-box, or using swatches or column layout, we might have arrived here when an option/variation was selected, so we need to update the price again, so do not return
				return;
			}

			var adjusted_price = field_price = parseFloat( jQuery(this).attr( 'data-price' ) );
			if ( type === '' ) {
				// quantity didn't match any tier type, put back original price
				pewc_wcfad.adjust_field_price_label( jQuery(this), field_price, field_price );
			} else if ( field_price > 0 ) {
				// only do this if field has price
				adjusted_price = pewc_wcfad.calculate_adjusted_price( type, field_price, percentage );
				pewc_wcfad.adjust_field_price_label( jQuery(this), adjusted_price, field_price );
			}

			if ( jQuery(this).hasClass( 'pewc-wcfad-has-options' ) && ! jQuery(this).hasClass( 'pewc-hide-option-price') ) {
				// this field has options (checkbox group, radio group, select, select-box, etc)
				// 3.21.7, skip if option price is hidden (e.g. Value Only)
				pewc_wcfad.adjust_option_price_labels( jQuery(this), type, percentage );
			}

			if ( ( jQuery(this).hasClass( 'pewc-item-products' ) || jQuery(this).hasClass( 'pewc-item-product-categories' ) ) && pewc_wcfad.applies_to === 'all' ) {
				// this is a Products or Product Categories field with one of its child products selected, we might need to adjust the price field for the child product
				pewc_wcfad.adjust_child_products_price_labels( jQuery(this), type, percentage );
			}

			// add this attribute so that we can keep track whether we have done this before for this quantity
			if ( ! isNaN( quantity ) ) {
				jQuery(this).attr( 'data-wcfad-adjusted-quantity', quantity );
			}

		});

	},

	// 3.21.4
	adjust_field_price_label: function( pewc_item, new_price, original_price ){

		// 3.24.1, don't adjust for flat rate items
		if ( pewc_item.hasClass( 'pewc-flatrate' ) ) {
			return;
		}

		var output_price = pewc_wcfad.output_new_price( new_price, original_price );

		if ( pewc_item.attr( 'data-field-type' ) === 'checkbox' ) {
			pewc_item.find( '.pewc-checkbox-form-label' ).find( '.pewc-checkbox-price' ).html( output_price );
		} else {
			pewc_item.find( '.pewc-field-label' ).find( '.pewc-field-price' ).html( output_price );
		}

	},

	// 3.21.4
	adjust_option_price_labels: function ( pewc_item, tier_type, percentage ) {

		// 3.24.1, don't adjust for flat rate items
		if ( pewc_item.hasClass( 'pewc-flatrate' ) ) {
			return;
		}

		var field_type = pewc_item.attr( 'data-field-type');
		var options;

		if ( field_type === 'select' || field_type === 'select-box' ) {

			options = pewc_item.find( 'select option' );
			if ( options.length > 0 ) {
				// this field has options
				var strikethrough = true;
				if ( field_type === 'select' ) {
					strikethrough = false; // select option text doesn't render HTML
				}
				options.each( function( index, element ){
					var adjusted_price = cost = parseFloat( jQuery(this).attr( 'data-option-cost' ) );
					if ( cost > 0 ) {
						adjusted_price = pewc_wcfad.calculate_adjusted_price( tier_type, cost, percentage );
						var new_price = pewc_wcfad.output_new_price( adjusted_price, cost, strikethrough, true );
						var new_text = jQuery(this).val() + pewc_vars.separator + new_price;
						jQuery(this).text( new_text );

						if ( field_type === 'select-box' ) {
							var ddoption = pewc_item.find( '.dd-options .dd-option' )[index];
							if ( ddoption != undefined ) {
								if ( jQuery( ddoption ).hasClass( 'dd-option-selected' ) ) {
									pewc_item.find( '.dd-selected .dd-selected-description' ).html( new_price );
								}
								jQuery( ddoption ).find( '.dd-option-description' ).html( new_price );
							}
						}
					}
				})
			}

		} else if ( field_type === 'checkbox_group' ) {

			options = pewc_item.find( 'input.pewc-checkbox-form-field' );
			if ( options.length > 0 ) {
				options.each( function( index, element ){
					var adjusted_price = cost = parseFloat( jQuery(this).attr( 'data-option-cost' ) );
					if ( cost > 0 ) {
						adjusted_price = pewc_wcfad.calculate_adjusted_price( tier_type, cost, percentage );
						var new_price = pewc_wcfad.output_new_price( adjusted_price, cost, true );
						jQuery(this).closest( '.pewc-checkbox-form-label' ).find( '.pewc-option-cost-label' ).html( new_price );
					}
				});
			}

		} else if ( field_type === 'radio' || field_type === 'image_swatch' ) {

			options = pewc_item.find( 'input.pewc-radio-form-field' );
			if ( options.length > 0 ) {
				options.each( function( index, element ){
					var adjusted_price = cost = parseFloat( jQuery(this).attr( 'data-option-cost' ) );
					if ( cost > 0 ) {
						adjusted_price = pewc_wcfad.calculate_adjusted_price( tier_type, cost, percentage );
						var new_price = pewc_wcfad.output_new_price( adjusted_price, cost, true );
						var new_text = jQuery( this ).val() + pewc_vars.separator + new_price;
						if ( field_type === 'radio' ) {
							jQuery(this).closest( '.pewc-radio-form-label' ).find( '.pewc-radio-option-text' ).html( new_text );
						} else {
							jQuery(this).closest( '.pewc-radio-image-wrapper' ).find( '.pewc-radio-image-desc > span' ).html( new_text );
						}
					}
				});
			}

		}

	},

	// 3.21.4
	adjust_child_products_price_labels: function( pewc_item, tier_type, percentage ) {

		// loop through all child products, then determine if we need to apply the discount or fee
		var field_id = pewc_item.attr( 'data-id' );
		var selector = '';
		var layout = '';
		var strikethrough = true;
		var price_only = false;

		if ( pewc_item.hasClass( 'pewc-item-products-select' ) ) {
			selector = 'select.pewc-form-field option';
			layout = 'select';
			strikethrough = false;
			price_only = true;
		} else if ( pewc_item.hasClass( 'pewc-item-products-swatches' ) ) {
			selector = 'input.pewc-swatch-form-field';
			layout = 'swatches';
		} else if ( pewc_item.hasClass( 'pewc-item-products-column' ) ) {
			layout = 'column';
		} else if ( pewc_item.hasClass( 'pewc-item-products-grid' ) ) {
			layout = 'grid';
			selector = 'input.pewc-grid-quantity-field';
		}

		if ( selector === '' ) {
			// default selector
			selector = 'input[name="' + field_id + '_child_product[]"]';
		}

		pewc_item.find( selector ).each( function( index, element ){
			var new_price = '';
			var adjusted_price = cost = 0;

			if ( layout === 'column' && jQuery(this).closest( '.pewc-checkbox-image-wrapper' ).hasClass( 'pewc-variable-child-product-wrapper' ) ) {
				// this is a variable child product using Column layout, loop through the select options
				//strikethrough = false;
				//price_only = true;
				var wrapper = jQuery(this).closest( '.pewc-checkbox-image-wrapper' );
				var select = wrapper.find( 'select.pewc-variable-child-select' );
				var selected_var = 0;

				select.find( 'option:selected' ).each( function( index, element ){
					adjusted_price = cost = parseFloat( jQuery(this).attr( 'data-option-cost' ) );
					selected_var = parseFloat( jQuery(this).val() );
					if ( cost > 0 ) {
						adjusted_price = pewc_wcfad.calculate_adjusted_price( tier_type, cost, percentage );
						new_price = pewc_wcfad.output_new_price( adjusted_price, cost, strikethrough, price_only );
						if ( ! isNaN( adjusted_price ) ) {
							jQuery(this).attr( 'data-wcfad-price', adjusted_price );
							// Column layout
							wrapper.find( '.pewc-variation-price' ).html( new_price );
							// hide the variation price to avoid confusion?
							wrapper.find( '.pewc-column-price-wrapper' ).hide();
						}
					}
				});

				// we update the price_html in this attr because .pewc-variation-price gets updated in pewc-variations.js
				var variation_data = select.attr( 'data-product_variations' );
				if ( variation_data != '' ) {
					variation_data = JSON.parse( variation_data );
					jQuery( variation_data ).each( function( index, element ){
						if ( jQuery(this)[0].variation_id == selected_var ) {
							jQuery(this)[0].price_html = new_price;
						}
					});
					select.attr( 'data-product_variations', JSON.stringify( variation_data ) );
					select.attr( 'data-wcfad-updated', 'yes' )
				}

				return;

			}

			// other product types arrive here
			adjusted_price = cost = parseFloat( jQuery(this).attr( 'data-option-cost' ) );

			if ( cost > 0 ) {
				adjusted_price = pewc_wcfad.calculate_adjusted_price( tier_type, cost, percentage );
				new_price = pewc_wcfad.output_new_price( adjusted_price, cost, strikethrough, price_only );

				if ( layout === 'select' ) {
					var new_text = jQuery(this).attr( 'data-field-value' ) + pewc_vars.separator + new_price;
					jQuery(this).text( new_text );
				} else if ( layout === 'swatches' ) {
					jQuery(this).closest( '.pewc-child-name' ).find( '.pewc-variation-price' ).html( new_price );
				} else {
					var wrapper = jQuery(this).closest( '.pewc-radio-checkbox-image-wrapper, .pewc-checkbox-wrapper, .pewc-radio-wrapper' );
					if ( layout === 'column' ) {
						// Simple Products using Column layout arrive here
						wrapper.find( '.pewc-column-price-wrapper' ).html( new_price );
						// hide the variation price to avoid confusion?
						wrapper.find( '.pewc-variation-price' ).hide();
					} else {
						// All other layouts
						wrapper.find( '.pewc-child-product-price-label' ).html( new_price );
					}
				}
			}

			if ( ! isNaN( adjusted_price ) ) {
				jQuery(this).attr( 'data-wcfad-price', adjusted_price );
			}
		});

	},

	// 3.21.4
	calculate_adjusted_price: function( tier_type, price, percentage ){

		if ( tier_type === '' ) {
			return price;
		}

		var adjustment = parseFloat( price * percentage );
		if ( tier_type.indexOf( 'discount' ) > -1 ) {
			price = parseFloat( price - adjustment );
		} else {
			price = parseFloat( price + adjustment );
		}

		price = parseFloat( price.toFixed( pewc_vars.decimals ) );

		return price;

	},

	// 3.21.4
	output_new_price: function( new_price, original_price, strikethrough=true, price_only=false ) {

		var output_price = pewc_wc_price( new_price.toFixed( pewc_vars.decimals ), price_only );
		if ( new_price != original_price && strikethrough ) {
			output_price = '<s>' + pewc_wc_price( original_price.toFixed( pewc_vars.decimals ), price_only ) + '</s> ' + output_price;
		}

		return output_price;

	},

	// 3.21.4
	get_child_product_quantities: function( wcfad_applies_to ) {

		if ( pewc_wcfad.counting_quantities ) {
			return;
		} else {
			pewc_wcfad.counting_quantities = true;
		}

		var qty = 0;
		var child_qty = parseFloat( jQuery( '#pewc-wcfad-total-child-product-quantity' ).val() );

		jQuery( '.pewc-item.pewc-item-products, .pewc-item.pewc-item-product-categories' ).each( function( index, element ){
			var curr_quantity = 0;
			var pewc_item = jQuery( this );
			var is_linked = pewc_item.find( '.child-product-wrapper' ).hasClass( 'products-quantities-linked' );
			var is_one_only = pewc_item.find( '.child-product-wrapper' ).hasClass( 'products-quantities-one-only' );

			if ( pewc_item.hasClass( 'pewc-item-products-grid' ) ) {
				// Grid layout
				pewc_item.find( 'input.pewc-grid-quantity-field' ).each( function( index, element ){
					var var_quantity = parseFloat( jQuery( this ).val() );
					if ( ! isNaN( var_quantity ) && var_quantity > 0 ) {
						curr_quantity += var_quantity;
					}
				});
			} else if ( pewc_item.hasClass( 'pewc-item-products-swatches' ) ) {
				// Swatches layout
				if ( is_linked ) {
					var product_qty = parseFloat( jQuery( 'form.cart .quantity .qty' ).val() );
					if ( isNaN( product_qty ) ) {
						product_qty = 1;
					}
				} else if ( is_one_only ) {
					product_qty = 1;
				}

				pewc_item.find( 'input.pewc-swatch-form-field' ).each( function( index, element ){
					if ( jQuery( this ).prop( 'checked' ) ) {
						// this swatch was selected, get quantity
						if ( is_linked || is_one_only ) {
							curr_quantity += product_qty;
						} else {
							var prod_quantity = parseFloat( jQuery( this ).closest( '.pewc-swatches-child-product-outer' ).find( 'input.pewc-child-quantity-field' ).val() );
							if ( ! isNaN( prod_quantity ) && prod_quantity > 0 ) {
								curr_quantity += prod_quantity;
							}
						}
					}
				});
			} else if ( pewc_item.attr( 'data-field-selected-counter' ) ) {
				// for Checkboxes Images and List
				curr_quantity = parseFloat( pewc_item.attr( 'data-field-selected-counter' ) );
			} else if ( pewc_item.find( 'input.pewc-independent-quantity-field' ).length > 0 && parseFloat( pewc_item.attr( 'data-field-price' ) ) > 0 ) {
				// for Radio Images and List, Select (independent quantity)
				curr_quantity = parseFloat( pewc_item.find( 'input.pewc-independent-quantity-field' ).val() );
			} else if ( ( pewc_item.hasClass( 'pewc-item-products-radio' ) || pewc_item.hasClass( 'pewc-item-products-radio-list' ) ) && pewc_item.attr( 'data-field-value' ) != '' ) {
				// Radio Images and List, Linked and One Only
				if ( is_linked ) {
					var product_qty = parseFloat( jQuery( 'form.cart .quantity .qty' ).val() );
					if ( isNaN( product_qty ) ) {
						product_qty = 1;
					}
				} else if ( is_one_only ) {
					product_qty = 1;
				}
				curr_quantity = product_qty;
			} else if ( pewc_item.find( '.products-quantities-linked ').length > 0 ) {
				// Last catcher for child products with Linked quantities
				var product_qty = parseFloat( jQuery( 'form.cart .quantity .qty' ).val() );
				if ( isNaN( product_qty ) ) {
					product_qty = 1;
				}
				if ( pewc_item.hasClass( 'pewc-item-products-select' ) && pewc_item.find( 'select.pewc-child-select-field option:selected' ).val() != '' ) {
					curr_quantity = product_qty;
				}
			} else if ( pewc_item.find( '.products-quantities-one-only ').length > 0 ) {
				// Last catcher for child products with One Only quantity
				if ( pewc_item.hasClass( 'pewc-item-products-select' ) && pewc_item.find( 'select.pewc-child-select-field option:selected' ).val() != '' ) {
					curr_quantity = 1;
				}
			}

			if ( wcfad_applies_to === 'all' ) {
				qty += curr_quantity;
			}
		});

		if ( qty != child_qty ) {
			// update hidden field, to be used in DPDR's wcfad-script.js
			jQuery( '#pewc-wcfad-total-child-product-quantity' ).val( qty );

			// trigger main quantity so that DPDR also updates the discount?
			jQuery( 'form.cart input.qty' ).trigger( 'pewc_qty_changed' );
		}

		pewc_wcfad.counting_quantities = false;

		return qty;

	}

};

// 3.21.6, put init inside the ready function so that we wait for DPDR code as well
jQuery( document ).ready( function(){
	pewc_wcfad.init();
});
