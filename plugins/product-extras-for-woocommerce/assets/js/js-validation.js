(function($) {

	$( document ).ready( function() {

		var passed2 = true;
		var min_max_error_message = '';

		var pewc_js_validation = {

			currently_checking: false,
			hide_totals_enabled: false,
			group_display_type: '',
			display_notification: false,
			scroll_to_first_failed: false,

			init: function() {

				// attach optimised validation to the submit button
				$( 'body' ).find( 'form.cart' ).on( 'submit', function(e){
					// validate, moved to a separate function in 3.21.2
					return pewc_js_validation.validate();
				});

				// this gets triggered when user selects an option in a checkbox group, image swatch, or products/product categories
				$( 'body' ).on( 'pewc_field_selected_counter_updated', function( event, field_wrapper, current_count ){
					// maybe check max count only, let the submit function check the min count
					var max_count = 0;

					if ( field_wrapper.attr( 'data-field-maxchecks' ) ) {
						// image swatch and checkbox group
						max_count = field_wrapper.attr( 'data-field-maxchecks' );
						min_max_error_message = field_wrapper.attr('data-field-maxchecks-error');
					} else if ( field_wrapper.attr( 'data-max-products' ) ) {
						// products and product categories
						max_count = field_wrapper.attr( 'data-max-products' );
						min_max_error_message = field_wrapper.attr('data-max-products-error');
					}
					max_count = parseInt( max_count );

					if ( max_count < 1 ) {
						return; // do nothing
					}

					// maybe always remove these classes, start clean
					field_wrapper.removeClass( 'pewc-passed-validation' );
					field_wrapper.removeClass( 'pewc-failed-validation' );
					field_wrapper.removeClass( 'pewc-failed-minmax' );
					field_wrapper.removeClass( 'pewc-failed-counter' );
					field_wrapper.find( '.pewc-js-validation-notice' ).html( '' ).hide(); // 3.21.2, reset notice

					if ( current_count > max_count ) {
						// max selection has been reached, show error
						field_wrapper.addClass( 'pewc-failed-validation' );
						field_wrapper.addClass( 'pewc-failed-minmax' );
						field_wrapper.addClass( 'pewc-failed-counter' );
						field_wrapper.find( '.pewc-js-validation-notice' ).html( min_max_error_message ).show();
					}
				});

				// 3.13.7. hide_totals_timer is used both by hide_totals and disable_groups, so if this is set, one of them is enabled
				if ( pewc_vars.hide_totals_timer > 0 ) {

					if ( pewc_vars.hide_totals_if_missing_required_fields == 'yes' && $( '.pewc-item.required-field' ).length > 0 ) {
						// hide totals until all required fields are completed
						// 3.26.11, updated condition so that totals are only hidden if the product has required fields
						pewc_js_validation.hide_totals_enabled = true;
						$( '.pewc-total-field-wrapper' ).hide();
						$( '.pewc-total-only' ).hide();
					}

					if ( pewc_vars.disable_groups_if_missing_required_fields == 'yes' ) {

						// only enable this for certain display type
						if ( pewc_js_validation.is_accordion_group() ) {
							pewc_js_validation.group_display_type = 'accordion';
						} else if ( pewc_js_validation.is_steps_group() ) {
							pewc_js_validation.group_display_type = 'steps';
						} else if ( pewc_js_validation.is_tabs_group() ) {
							pewc_js_validation.group_display_type = 'tabs';
						}

						if ( pewc_js_validation.group_display_type != '' ) {
							// overrides click functions in pewc.js
							pewc_js_validation.override_click_functions();
						}

					}

					if ( pewc_js_validation.hide_totals_enabled || pewc_js_validation.group_display_type != '' ) {
						var check_all_required_interval = setInterval(
							pewc_js_validation.check_all_required,
							pewc_vars.hide_totals_timer
						);
					}

				}

			},

			// 3.21.2
			validate: function() {
				var passed = true;
				var first_failed = '';
				var first_failed_group = ''; // 3.22.0
				var has_min_max = [ 'text', 'textarea', 'advanced-preview', 'number', 'name_price', 'products', 'product-categories', 'image_swatch', 'checkbox_group' ];

				// loop through all add-on fields
				$( 'body' ).find( 'form.cart' ).find( '.pewc-item' ).each( function() {
					var curr_item = $(this);
					var curr_id = curr_item.attr('data-id');
					var curr_value = curr_item.attr('data-field-value');
					var curr_type = curr_item.attr('data-field-type');
					var curr_notice = curr_item.find( '.pewc-js-validation-notice' );
					var curr_group = curr_item.closest( '.pewc-group-wrap' );
					var curr_group_heading = curr_group.find( '.pewc-group-heading-wrapper' );

					// maybe always remove these classes, start clean
					curr_item.removeClass( 'pewc-passed-validation' );
					curr_item.removeClass( 'pewc-failed-validation' );
					curr_item.removeClass( 'pewc-failed-minmax' );

					// reset error message
					curr_notice.html('').hide();

					// if field is hidden, no need to do the rest
					if ( pewc_js_validation.is_hidden_field( curr_item, curr_group ) ) {
						return;
					}

					// 3.13.7
					if ( pewc_js_validation.group_display_type != '' && pewc_js_validation.is_disabled_group( curr_group ) ) {
						// Disable Groups is enabled, and if this field is inside a disabled group, no need to validate
						return;
					}

					// only check required fields
					if ( curr_item.hasClass( 'required-field' ) ) {

						if ( ! pewc_js_validation.passed_required( curr_type, curr_value, curr_item ) ) {

							// store the value of the first failed element so that we can scroll the user there later
							if ( first_failed == '' ) {
								first_failed = curr_id;
								first_failed_group = curr_group.attr( 'id' ); // we also use this in case this is a cloned group?
								if ( ! curr_group_heading.hasClass( 'pewc-group-failed-validation' ) ) {
									curr_group_heading.addClass( 'pewc-group-failed-validation' );
								}
							}
							passed = false;
							curr_item.addClass( 'pewc-failed-validation' );
							curr_notice.html( curr_item.attr('data-validation-notice') ).show();

						}
						else {
							curr_item.addClass( 'pewc-passed-validation' );
						}
					}

					// now check if field has min and max... these could be non-required fields, but if they have a value, they must be checked
					// this gets skipped if the field failed already
					if ( has_min_max.includes( curr_type ) && ! curr_item.hasClass( 'pewc-failed-validation' ) ) {

						passed2 = true;
						min_max_error_message = '';

						if ( curr_type == 'text' || curr_type == 'textarea' || curr_type == 'advanced-preview' ) {

							pewc_js_validation.validate_text_field( curr_id, curr_item );

						} else if ( curr_type == 'number' || curr_type == 'name_price' ) {

							pewc_js_validation.validate_number_field( curr_id, curr_item );

						} else if ( curr_type == 'products' || curr_type == 'product-categories' ) {

							var min_products = 0, max_products = 0;

							if ( curr_item.attr('data-min-products') > 0 ) {
								min_products = parseFloat( curr_item.attr('data-min-products') );
							}
							if ( curr_item.attr('data-max-products') > 0 ) {
								max_products = parseFloat( curr_item.attr('data-max-products') );
							}

							if ( min_products > 0 || max_products > 0 ) {

								// we have either a min or max set so count this field's child products, take quantity into account
								var total_child_products = 0;

								curr_item.find('.pewc-checkbox-form-field').each( function(){
									if ( $(this).is(':checked') ) {

										var product_id = $(this).val();
										var child_product_quantity = parseFloat( $('input[name="'+curr_id+'_child_quantity_'+product_id+'"]').val() );

										if ( ! isNaN(child_product_quantity) ) {
											total_child_products += child_product_quantity;
										}
									}
								});

								if ( min_products == max_products && total_child_products != min_products ) {
									min_max_error_message = curr_item.attr('data-exact-products-error');
									passed2 = false;
								} else if ( ! pewc_js_validation.passed_min_req( min_products, total_child_products ) ) {
									min_max_error_message = curr_item.attr('data-min-products-error');
									passed2 = false;
								} else if ( ! pewc_js_validation.passed_max_req( max_products, total_child_products ) ) {
									min_max_error_message = curr_item.attr('data-max-products-error');
									passed2 = false;
								}

							}

						} else if ( curr_type == 'image_swatch' || curr_type == 'checkbox_group' ) {

							var field_minchecks = 0, field_maxchecks = 0;

							if ( curr_item.attr('data-field-minchecks') > 0 ) {
								field_minchecks = parseFloat( curr_item.attr('data-field-minchecks') );
							}
							if ( curr_item.attr('data-field-maxchecks') > 0 ) {
								field_maxchecks = parseFloat( curr_item.attr('data-field-maxchecks') );
							}

							if ( field_minchecks > 0 || field_maxchecks > 0 ) {

								// we have either a min or max set so count this field's selected items
								var total_selected = 0;
								var pewc_field_class = '';

								if ( curr_type == 'image_swatch' )
									pewc_field_class = 'pewc-radio-form-field';
								else
									pewc_field_class = 'pewc-checkbox-form-field';

								curr_item.find( '.'+pewc_field_class ).each( function(){
									if ( $(this).is(':checked') ) {
										total_selected += 1;
									}
								});

								if ( total_selected < 1 ) {
									return; // in the backend, if nothing is selected, and the field is not required, the min/max check is not run
								}

								// exact-error is not available for now
								//if ( field_minchecks == field_maxchecks && total_selected != field_minchecks ) {
								//	min_max_error_message = curr_item.attr('data-field-exact-error');
								//	passed2 = false;
								//} else 
								if ( ! pewc_js_validation.passed_min_req( field_minchecks, total_selected ) ) {
									min_max_error_message = curr_item.attr('data-field-minchecks-error');
									passed2 = false;
								} else if ( ! pewc_js_validation.passed_max_req( field_maxchecks, total_selected ) ) {
									min_max_error_message = curr_item.attr('data-field-maxchecks-error');
									passed2 = false;
								}

							}

						}

						if ( ! passed2 ) {
							curr_item.removeClass( 'pewc-passed-validation' );
							if ( ! curr_item.hasClass( 'pewc-failed-validation' ) ) curr_item.addClass( 'pewc-failed-validation' );
							if ( ! curr_item.hasClass( 'pewc-failed-minmax' ) ) curr_item.addClass( 'pewc-failed-minmax' );
							curr_notice.html( min_max_error_message ).show();
							if ( first_failed == '' )
								first_failed = curr_id;
							passed = false;
						}

					}

					if ( ! passed ) {
						// this field did not pass, so attach an event?
						curr_item.on( 'keyup input change paste update pewc_trigger_color_picker_change', '.pewc-form-field, .pewc-radio-form-field, .pewc-checkbox-form-field', function(e) {
							if ( e.which == 13 )
								return; // do nothing on enter, because the validations above might be removed

							if ( curr_type == 'text' || curr_type == 'textarea' || curr_type == 'advanced-preview' ) {
								if ( e.which == 0 ) return; // do nothing on input, avoid jerky movements
								// live validation as they type
								pewc_js_validation.validate_text_field( curr_id, curr_item );
							} else if ( ! curr_item.hasClass( 'pewc-failed-counter' ) ) {
								// reset notice but only those that didn't fail the selected counter check
								curr_notice.html('').hide();
								curr_item.removeClass( 'pewc-failed-validation' );
							}
							return;
						});

						if ( pewc_js_validation.is_accordion_group() ) {
							// if a failed field is inside a closed accordion, open the accordion if it's still closed
							if ( ! curr_group.hasClass( 'group-active' ) ) {
								curr_group.addClass( 'group-active' );
							}
						}
					}
				});

				if ( ! passed ) {
					// scroll to first failed element
					if ( $( '.pewc-item.'+first_failed ).length > 0 ) {
						pewc_js_validation.scroll_screen_to_first_failed( first_failed, first_failed_group );
					}
					first_failed = '';
				}

				return passed;
			},

			// 3.13.7
			is_hidden_field: function( curr_item, curr_group ) {

				if (
					curr_item.hasClass( 'pewc-hidden-field' ) || 
					pewc_js_validation.is_hidden_group( curr_group ) || 
					( curr_item.hasClass( 'pewc-variation-dependent') && ! curr_item.hasClass( 'active' ) )
				) {
					return true;
				} else {
					return false;
				}

			},

			// 3.13.7
			is_hidden_group: function( curr_group ) {

				if ( curr_group.hasClass( 'pewc-group-hidden' ) ) {
					return true;
				} else {
					return false;
				}

			},

			// 3.13.7
			is_disabled_group: function( curr_group ) {

				if ( curr_group.hasClass( 'pewc-disabled-group' ) ) {
					return true;
				} else {
					return false;
				}

			},

			// 3.13.7
			is_accordion_group: function() {

				if ( $( '.pewc-product-extra-groups-wrap' ).hasClass( 'pewc-groups-accordion' ) ) return true;
				else return false;

			},

			// 3.13.7
			is_steps_group: function() {

				if ( $( '.pewc-product-extra-groups-wrap' ).hasClass( 'pewc-groups-steps' ) ) return true;
				else return false;

			},

			// 3.13.7
			is_tabs_group: function() {

				if ( $( '.pewc-product-extra-groups-wrap' ).hasClass( 'pewc-groups-tabs' ) ) return true;
				else return false;

			},

			// 3.13.7
			passed_required: function( curr_type, curr_value, curr_item ) {

				if (
					( curr_type != 'checkbox' && curr_value == '') ||
					( curr_type == 'checkbox' && ! curr_item.hasClass( 'pewc-active-field' ) ) ||
					( curr_type == 'upload' && curr_value == 0 ) ||
					( curr_type == 'products' && curr_value == 0 ) ||
					( curr_type == 'product-categories' && curr_value == 0 )
				) {
					return false;
				} else {
					return true;
				}

			},

			passed_min_req: function( min_value, field_value ) {

				if ( min_value > 0 && field_value < min_value )
					return false;
				else return true;

			},

			passed_max_req: function( max_value, field_value ) {

				if ( max_value > 0 && field_value > max_value )
					return false;
				else return true;

			},

			validate_text_field: function( curr_id, curr_item ) {

				var curr_field = $( '#'+curr_id );
				var curr_notice = curr_item.find( '.pewc-js-validation-notice' );
				passed2 = true;

				if ( curr_field.val() != '' && ( curr_field.attr('data-minchars') > 0 || curr_field.attr('data-maxchars') > 0 ) ) {
					strlen = curr_field.val().length;

					if ( ! pewc_js_validation.passed_min_req( curr_field.attr('data-minchars'), strlen ) ) {
						passed2 = false;
						min_max_error_message = curr_item.attr('data-field-minchars-error');
					} else if ( ! pewc_js_validation.passed_max_req( curr_field.attr('data-maxchars'), strlen ) ) {
						passed2 = false; // maybe max is not needed since some browsers seem to respect the max value?
						min_max_error_message = curr_item.attr('data-field-maxchars-error');
					}
				}

				if ( ! passed2 ) {
					curr_item.removeClass( 'pewc-passed-validation' );
					if ( ! curr_item.hasClass( 'pewc-failed-validation' ) ) curr_item.addClass( 'pewc-failed-validation' );
					if ( ! curr_item.hasClass( 'pewc-failed-minmax' ) ) curr_item.addClass( 'pewc-failed-minmax' );
					curr_notice.html( min_max_error_message ).show();
				}
				else {
					curr_notice.html( '' ).hide();
					curr_item.removeClass( 'pewc-failed-validation' );
					curr_item.removeClass( 'pewc-failed-minmax' );
				}
			},

			// 3.21.4, created a separate function to be used by check_all_required(), which is used when disabling groups or hiding totals
			validate_number_field: function( curr_id, curr_item ) {

				var curr_field = $( '#'+curr_id );

				if ( curr_field.val() == '' ) return; // only validate if not blank? If this is required, this is validated somewhere else

				if ( curr_field.attr('min') > 0 || curr_field.attr('max') > 0 ) {

					/*if ( curr_field.attr('data-require-required') == 'no' && curr_field.attr('min') > 0 ) {
						passed2 = false;
						min_max_error_message = curr_item.attr('data-field-minval-error');
					} else */
					if ( ! pewc_js_validation.passed_min_req( curr_field.attr('min'), parseFloat( curr_field.val() ) ) ) {
						passed2 = false;
						min_max_error_message = curr_item.attr('data-field-minval-error');
					} else if ( ! pewc_js_validation.passed_max_req( curr_field.attr('max'), parseFloat( curr_field.val() ) ) ) {
						passed2 = false;
						min_max_error_message = curr_item.attr('data-field-maxval-error');
					}

				}

			},

			// 3.13.7
			scroll_screen_to_first_failed: function( first_failed, first_failed_group ) {

				// 3.22.0
				if ( first_failed_group !== '' && $( '#' + first_failed_group ).length > 0 ) {
					var failed_group = $( '#' + first_failed_group );
					var failed_group_divid = first_failed_group;
					var first_failed_group_id = failed_group.attr( 'data-group-id' ); // this might be incorrect for tabs?
				} else {
					var failed_group = $( '.pewc-item.' + first_failed ).closest( '.pewc-group-wrap' );
					var failed_group_divid = failed_group.attr( 'id' );
					var first_failed_group_id = failed_group.attr( 'data-group-id' );
				}
				// Add a class to the failed group tab
				$( '#pewc-tab-' + first_failed_group_id ).addClass( 'tab-failed' );

				if ( pewc_vars.disable_scroll_on_steps_validation == 'yes' && pewc_js_validation.is_steps_group() ) {
					failed_group.find( '.pewc-group-js-validation-notice' ).show();
					return; // don't scroll
				}

				if ( pewc_js_validation.is_steps_group() || pewc_js_validation.is_tabs_group() ) {
					// hide other groups and deactivate other tabs first
					$( '.pewc-tab' ).removeClass( 'active-tab tab-failed' );
					$( '.pewc-group-wrap' ).removeClass( 'group-active' );
					// activate tab and group with error
					$( '#pewc-tab-' + first_failed_group_id ).addClass( 'active-tab' );
					$( '#pewc-group-' + first_failed_group_id ).addClass( 'group-active' );
				} else {
					// for other types, open the group where failure happened
					if ( ! failed_group.hasClass( 'group-active') && ! failed_group.hasClass( 'pewc-disabled-group' ) ) {
						failed_group.addClass( 'group-active' );
					}
				}

				// the groups above need to be open before we scroll
				$([document.documentElement, document.body]).animate({
					scrollTop: $( '#' + failed_group_divid + ' .pewc-item.'+first_failed ).offset().top-50
				}, 150);

			},

			// 3.13.7. Use this for both hide_totals and disable_groups. This is called via setInterval.
			check_all_required: function() {

				// if this product does not have required fields, do nothing
				if ( $( '.pewc-item.required-field' ).length < 1 ) return;

				if ( pewc_js_validation.currently_checking ) return; // loop is currently running, go back

				pewc_js_validation.currently_checking = true;
				var hide_the_totals = false;
				var first_failed = '';
				var first_failed_group = ''; // 3.22.0
				var passed = true;
				var prev_group_id = 0;

				if ( pewc_js_validation.group_display_type != '' ) {
					// disable all groups first
					pewc_js_validation.disable_groups();
				}

				// 3.17.2, check all fields, including non-required
				$( '.pewc-item' ).each( function(){

					var curr_item = $(this);
					var curr_id = curr_item.attr('data-id');
					var curr_value = curr_item.attr('data-field-value');
					var curr_type = curr_item.attr('data-field-type');
					var curr_group = curr_item.closest( '.pewc-group-wrap' );
					var curr_group_id = curr_group.attr( 'data-group-id' );
					var curr_group_heading = curr_group.find( '.pewc-group-heading-wrapper' );
					var curr_notice = curr_item.find( '.pewc-js-validation-notice' );
					var notice = ''; // 3.21.4
					// 3.21.4, reset
					passed2 = true;
					min_max_error_message = '';

					// if field is hidden, no need to do the rest
					if ( pewc_js_validation.is_hidden_field( curr_item, curr_group ) ) {
						return;
					}

					if ( pewc_js_validation.group_display_type != '' && prev_group_id > 0 && prev_group_id != curr_group_id ) {
						// we only enter here if this is a new group
						if ( passed ) {
							// this is a field in a new group, but no failures yet, so unlock this group and the previous group
							curr_group.removeClass( 'pewc-disabled-group' );
							$( '#pewc-group-' + prev_group_id ).removeClass( 'pewc-disabled-group' );
							//curr_group.addClass( 'group-active' ); // this auto-opens an accordion

							if ( pewc_js_validation.group_display_type == 'tabs' || pewc_js_validation.group_display_type == 'steps' ) {
								// enable the tab for the next group and the previous group
								$( '#pewc-tab-' + curr_group_id ).removeClass( 'pewc-disabled-group' );
								$( '#pewc-tab-' + prev_group_id ).removeClass( 'pewc-disabled-group' );

								if ( pewc_js_validation.group_display_type == 'steps' ) {
									// enable the 'next' button' for the previous group
									// this takes into account groups hidden by condition, which are skipped by our loop
									$( '#pewc-group-' + prev_group_id ).find( '.pewc-next-step-button' ).removeClass( 'pewc-disabled-group' );
									// enable the 'previous' button for the current group
									curr_group.find( '.pewc-next-step-button' ).each( function(){
										if ( $(this).attr( 'data-direction' ) == 'previous' ) {
											$(this).removeClass( 'pewc-disabled-group' );
										}
									});
								}
							}
						} else {
							return; // do not continue validating this field since this is in a possibly disabled group and therefore hidden
						}
					}

					// 3.17.2, if this is not a required field, no need to continue
					if ( ! curr_item.hasClass( 'required-field' ) ) {
						if ( prev_group_id != curr_group.attr( 'data-group-id' ) ) {
							// still, record the current group
							prev_group_id = curr_group.attr( 'data-group-id' );
						}
						return; // not a required field
					}

					if ( ! pewc_js_validation.passed_required( curr_type, curr_value, curr_item ) ) {

						passed = passed2 = false;
						notice = curr_item.attr( 'data-validation-notice' );

						// missing required field, hide the totals
						/*hide_the_totals = true;

						if ( pewc_js_validation.display_notification ) {
							// store the value of the first failed element so that we can scroll the user there later
							if ( first_failed == '' ) {
								first_failed = curr_id;
								if ( ! curr_group_heading.hasClass( 'pewc-group-failed-validation' ) ) {
									curr_group_heading.addClass( 'pewc-group-failed-validation' );
								}
							}
							curr_item.addClass( 'pewc-failed-validation' );
							curr_notice.html( curr_item.attr('data-validation-notice') ).show();
						}*/

					} else if ( ! curr_item.hasClass( 'pewc-failed-minmax' ) /*&& ! curr_item.hasClass( 'pewc-passed-validation' )*/ ) {

						// 3.21.4, check if field has min/max, validate it as well
						if ( pewc_js_validation.group_display_type != '' && ( curr_type == 'number' || curr_type == 'name_price' ) ) {
							pewc_js_validation.validate_number_field( curr_id, curr_item );
						}
						// 3.21.2, only do this if it didn't fail the min/max validation, and if it doesn't have pewc-passed-validation already
						if ( passed2 ) {
							curr_group_heading.removeClass( 'pewc-group-failed-validation' );
							curr_item.addClass( 'pewc-passed-validation' );
							curr_item.removeClass( 'pewc-failed-validation' );
							curr_notice.html( curr_item.attr('data-validation-notice') ).hide();
						} else {
							passed = false;
							notice = min_max_error_message;
						}

					}

					// 3.21.4, catches missing required and those that failed minmax validation
					if ( ! passed2 ) {
						// missing required field, hide the totals
						hide_the_totals = true;

						if ( pewc_js_validation.display_notification ) {
							// store the value of the first failed element so that we can scroll the user there later
							if ( first_failed == '' ) {
								first_failed = curr_id;
								first_failed_group = curr_group.attr( 'id' ); // 3.24.3
								if ( ! curr_group_heading.hasClass( 'pewc-group-failed-validation' ) ) {
									curr_group_heading.addClass( 'pewc-group-failed-validation' );
								}
							}
							curr_item.removeClass( 'pewc-passed-validation' );
							curr_item.addClass( 'pewc-failed-validation' );
							curr_notice.html( notice ).show();
						}
					}

					if ( prev_group_id != curr_group.attr( 'data-group-id' ) ) {
						prev_group_id = curr_group.attr( 'data-group-id' );
					}

				});

				// Hide Totals
				if ( pewc_js_validation.hide_totals_enabled ) {
					if ( hide_the_totals ) {
						$( '.pewc-total-field-wrapper' ).hide();
						$( '.pewc-total-only' ).hide();
					} else {
						$( '.pewc-total-field-wrapper' ).show();
						$( '.pewc-total-only' ).show();
					}
				}

				// Disable Groups
				if ( pewc_js_validation.group_display_type != '' ) {

					if ( first_failed != '' && pewc_js_validation.scroll_to_first_failed ) {
						// scroll to first failed element
						if ( $( '.pewc-item.'+first_failed ).length > 0 ) {
							pewc_js_validation.scroll_screen_to_first_failed( first_failed, first_failed_group );
						}
						first_failed = '';
						pewc_js_validation.scroll_to_first_failed = false; // set to false so that we don't always scroll to the first error
						pewc_js_validation.display_notification = false; // set to false so that if a required field fails validation again, notice is not displayed until user submits the cart or clicks the disabled group header
					}

				}

				pewc_js_validation.currently_checking = false;

			},

			// 3.13.7
			disable_groups: function() {

				// find all other tabs and add pewc-disabled-group class. click is triggered in pewc.js. when it finds the class pewc-disabled-group, the group won't toggle
				if ( pewc_js_validation.group_display_type == 'tabs' || pewc_js_validation.group_display_type == 'steps' ) {
					$( '.pewc-tab' ).each( function() {
						if ( ! $(this).hasClass( 'active-tab' ) ) {
							$(this).addClass( 'pewc-disabled-group' );
						}
					});
					// disable all Next/Previous buttons on init. they may be enabled later if all required fields are satisfied
					$( '.pewc-next-step-button' ).addClass( 'pewc-disabled-group' );
				}

				// find all other groups and add pewc-disabled-group class.
				$( '.pewc-group-wrap' ).each( function(){
					if ( ! $(this).hasClass( 'first-group' ) && ! $(this).hasClass( 'group-active' ) ) {
						$(this).addClass( 'pewc-disabled-group' );
					}
				});

			},

			// 3.13.7. override click functions originally defined in pewc.js
			override_click_functions: function() {

				if ( pewc_js_validation.group_display_type == 'tabs' || pewc_js_validation.group_display_type == 'steps' ) {
					$( '.pewc-tab' ).on( 'click', function( e ) {
						if ( $(this).hasClass( 'pewc-disabled-group' ) ) {
							e.preventDefault();
							pewc_js_validation.display_notification = true;
							pewc_js_validation.scroll_to_first_failed = true;
							return;
						}
					});
					if ( pewc_js_validation.group_display_type == 'steps' ) {
						$( '.pewc-next-step-button' ).on( 'click', function( e ) {
							if ( $(this).hasClass( 'pewc-disabled-group' ) ) {
								e.preventDefault();
								pewc_js_validation.display_notification = true;
								pewc_js_validation.scroll_to_first_failed = true;
								return;
							}
						});
					}
				} else {
					$( '.pewc-groups-accordion h3' ).on( 'click', function( e ){
						if ( $( this ).closest( '.pewc-group-wrap' ).hasClass( 'pewc-disabled-group' ) ) {
							e.preventDefault();
							pewc_js_validation.display_notification = true;
							pewc_js_validation.scroll_to_first_failed = true;
						}
					});
				}

			},

		}

		pewc_js_validation.init();

		// 3.17.2, allow other plugins to reinitiate Optimised validation, e.g. Product Table Ultimate
		$( document ).on( 'pewc_reinitiate_js_validation', function(){
			pewc_js_validation.init();
		});

		// 3.21.2, allow others to trigger validation via JS event
		$( document ).on( 'pewc_trigger_js_validation', function(){
			return pewc_js_validation.validate();
		});

	});

})(jQuery);
