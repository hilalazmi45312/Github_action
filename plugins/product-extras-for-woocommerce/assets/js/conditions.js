(function($) {

	$( document ).ready( function() {

		var reset_fields = [];

		var pewc_conditions = {

			init: function() {

				this.initial_check();
				$( '.pewc-condition-trigger input' ).on( 'change input keyup paste', this.trigger_condition_check );
				$( 'body' ).on( 'change', '.pewc-condition-trigger select', this.trigger_condition_check );
				$( '.pewc-calculation-trigger input' ).on( 'change input keyup paste', this.trigger_calculation );

				// 3.26.5, added pewc_trigger_initial_check, used in pewc-repeatable.js
				$( document ).on( 'ptuwc_opened_config_row, pewc_trigger_initial_check', function ( event, instance, active_row ) {
					pewc_conditions.initial_check();
				});

				// 3.26.5
				$( document ).on( 'pewc_attach_condition_events', function( event ){
					pewc_conditions.attach_events();
				});

				// 3.26.11
				$( 'body' ).on( 'pewc_field_visibility_updated', function( e, field_id, action ){
					pewc_conditions.put_back_default( field_id );
				});

				if( pewc_vars.conditions_timer > 0 ) {

					// 3.26.5
					this.attach_events();

					$( 'form.cart .qty' ).on( 'change input keyup paste', this.trigger_quantity_condition_check );
					$( 'body' ).on( 'pewc_reset_field_condition', this.trigger_field_reset_condition_check );

					// since 3.11.9
					$( 'body' ).on( 'show_variation', this.trigger_attribute_condition_check );
					$( 'body' ).on( 'hide_variation', this.trigger_attribute_condition_check );

					// 3.20.1
					$( 'body' ).on( 'pewc_reset_fields', this.reset_fields );

					if( typeof pewc_cost_triggers !== 'undefined' && pewc_cost_triggers.length > 0 ) {
						var cost_interval = setInterval(
							this.trigger_cost_condition_check,
							pewc_vars.conditions_timer
						);
					}
				}

			},

			// 3.26.5, created a separate function so that we can re-attach these to repeatable groups
			attach_events: function() {
				$( '.pewc-field-triggers-condition' ).on( 'pewc_update_select_box', this.trigger_field_condition_check );
				$( '.pewc-field-triggers-condition input' ).on( 'change input keyup paste', this.trigger_field_condition_check );
				$( '.pewc-field-triggers-condition select' ).on( 'update change', this.trigger_field_condition_check );
				$( '.pewc-field-triggers-condition .pewc-calculation-value' ).on( 'calculation_field_updated', this.trigger_field_condition_check );
			},

			initial_check: function() {

				// Check the fields
				if( pewc_vars.conditions_timer > 0 ) {

					$( '.pewc-field-triggers-condition' ).each( function() {

						var field = $( this ).closest( '.pewc-item' );
						var parent = pewc_conditions.get_field_parent( field );
						var field_value = pewc_conditions.get_field_value( $( field ).attr( 'data-field-id' ), $( field ).attr( 'data-field-type' ), parent );
						var triggers_for = JSON.parse( $( field ).attr( 'data-triggers-for' ) );

						// 3.26.5
						pewc_conditions.check_triggered_fields( $( field ), field_value, triggers_for, parent );

						// Iterate through each field that is conditional on the updated field
						//for( var g in triggers_for ) {
						//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], field_value, parent );
						//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
						//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
						//}

					});

				}

				// Check the groups
				$( '.pewc-condition-trigger' ).each( function() {
					var field = $( this );
					var groups = JSON.parse( $( field ).attr( 'data-trigger-groups' ) );
					for( var g in groups ) {
						conditions_obtain = pewc_conditions.check_group_conditions( groups[g] );
						var action = $( '#pewc-group-' + groups[g] ).attr( 'data-condition-action' );
						pewc_conditions.assign_group_classes( conditions_obtain, action, groups[g] );
					}
				});

				// 3.11.9, check all groups and fields using attributes on conditions
				pewc_conditions.trigger_attribute_condition_check();

			},

			trigger_calculation: function() {

				// Possibly add a delay here to ensure calculations are made
				var calculations = $( this ).closest( '.pewc-item' ).attr( 'data-trigger-calculations' );
				if( calculations ) {
					calculations = JSON.parse( calculations );
					for( var c in calculations ) {
						$( '.pewc-field-' + calculations[c] ).find( '.pewc-calculation-value' ).trigger( 'change' );
					}
				}

			},

			trigger_condition_check: function() {

				var field = $( this ).closest( '.pewc-item' );
				var groups = JSON.parse( $( field ).attr( 'data-trigger-groups' ) );
				pewc_conditions.trigger_group_conditions( groups );

				if( pewc_vars.reset_fields == 'yes' ) {
					pewc_conditions.reset_fields();
				}

			},

			trigger_group_conditions: function( groups ) {
				for( var g in groups ) {
					conditions_obtain = pewc_conditions.check_group_conditions( groups[g] );
					var action = $( '#pewc-group-' + groups[g] ).attr( 'data-condition-action' );
					pewc_conditions.assign_group_classes( conditions_obtain, action, groups[g] );
				}
				// let's do this if we need to toggle other groups or fields that are dependent on a field inside a toggled group
				$( 'body' ).trigger( 'pewc_reset_field_condition' );
			},

			get_field_parent: function( field ) {

				var parent = $( field ).closest( '.product' );
				if( $( parent ).length < 1 ) {
					parent = $( field ).closest( '.ptuwc-product-config-row' );
				}

				return parent;

			},

			get_field_group_id: function( field ) {

				var group_id = $( field ).closest( '.pewc-group-wrap' ).attr( 'data-group-id' );
				return group_id;

			},

			trigger_field_condition_check: function() {

				var field = $( this ).closest( '.pewc-item' );
				var parent = pewc_conditions.get_field_parent( field );

				var field_value = pewc_conditions.get_field_value( $( field ).attr( 'data-field-id' ), $( field ).attr( 'data-field-type' ), parent );
				var triggers_for = JSON.parse( $( field ).attr( 'data-triggers-for' ) );

				// 3.26.5
				pewc_conditions.check_triggered_fields( $( field ), field_value, triggers_for, parent );

				// Iterate through each field that is conditional on the updated field
				//for( var g in triggers_for ) {
				//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], field_value, parent );
				//	var group = $( '.pewc-field-' + triggers_for[g] ).closest( '.pewc-group-wrap' );
				//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
				//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
				//}

				if( pewc_vars.reset_fields == 'yes' ) {
					pewc_conditions.reset_fields();
				}

			},

			// Iterate through fields that have had their values reset
			// Ensures fields with dependent conditions will also get reset correctly
			trigger_field_reset_condition_check: function() {

				// Use a timer to allow complex pages to catch up
				var reset_timer = setTimeout(
					function() {
						$( '.pewc-reset' ).each( function() {
							$( this ).removeClass( 'pewc-reset' );
							var field = $( this );
							var parent = pewc_conditions.get_field_parent( field );
							if ( $( field ).hasClass( 'pewc-repeatable-field' ) ) {
								// 3.26.5, use the group wrapper as the parent because it's possible we have duplicate fields
								parent = $( field ).closest( '.pewc-group-wrap' );
							}
							var field_value = pewc_conditions.get_field_value( $( field ).attr( 'data-field-id' ), $( field ).attr( 'data-field-type' ), parent );
							var triggers_for = $( field ).attr( 'data-triggers-for' );
							if( triggers_for != undefined ) {

								var triggers_for = JSON.parse( $( field ).attr( 'data-triggers-for' ) );

								// 3.26.5
								pewc_conditions.check_triggered_fields( $( field ), field_value, triggers_for, parent );

								// Iterate through each field that is conditional on the updated field
								//for( var g in triggers_for ) {
								//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], field_value, parent );
								//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
								//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
								//}
							}

						});
					}, 100
				);

			},

			trigger_quantity_condition_check: function() {

				if( typeof pewc_quantity_triggers === 'undefined' ) {
					return;
				}

				var triggers_for = pewc_quantity_triggers;

				// 3.26.5
				pewc_conditions.check_triggered_fields( 'quantity', $( 'form.cart .quantity input.qty' ).val(), triggers_for );

				// Iterate through each field that is conditional on the updated field
				//for( var g in triggers_for ) {
				//	var parent = pewc_conditions.get_field_parent( $( '.pewc-field-'+triggers_for[g] ) );
				//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], $( 'form.cart .quantity input.qty' ).val(), parent );
				//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
				//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
				//}

			},

			trigger_cost_condition_check: function() {

				var triggers_for = pewc_cost_triggers;

				// 3.26.5
				pewc_conditions.check_triggered_fields( 'cost', 0, triggers_for );

				// Iterate through each field that is conditional on the updated field
				// 3.21.7, added parent to the condition
				//for( var g in triggers_for ) {
				//	var parent = pewc_conditions.get_field_parent( $( '.pewc-field-'+triggers_for[g] ) );
				//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], 0, parent );
				//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
				//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
				//}

			},

			check_group_conditions: function( group_id ) {

				var conditions = JSON.parse( $( '#pewc-group-' + group_id ).attr( 'data-conditions' ) );
				var match = $( '#pewc-group-' + group_id ).attr( 'data-condition-match' );
				var is_visible = false;
				if( match == 'all' ) {
					is_visible = true;
				}
				for( var i in conditions ) {
					var condition = conditions[i];
					if( ! condition.field_type ) {
						condition.field_type = $( '.' + condition.field ).attr( 'data-field-type' );
					}

					var field = $( '.pewc-field-' + $( '.' + condition.field ).attr( 'data-field-id' ) );
					var parent = pewc_conditions.get_field_parent( field );
					var value = pewc_conditions.get_field_value( $( '.' + condition.field ).attr( 'data-field-id' ), condition.field_type, parent );
					if ( condition.field.substring(0, 3) == 'pa_' ) {
						value = $( '#'+condition.field ).val();
					}
					var meets_condition = this.field_meets_condition( value, condition.rule, condition.value );
					if( meets_condition && match =='any' ) {
						return true;
					} else if( ! meets_condition && match =='all' ) {
						return false;
					}
				}

				return is_visible;

			},

			check_field_conditions: function( field_id, field_value, parent ) {

				var field = $( parent ).find( '.pewc-field-' + field_id );
				if( $( field ).length < 1 ) {
					return false;
				}

				var conditions = JSON.parse( $( field ).attr( 'data-field-conditions' ) );
				var match = $( field ).attr( 'data-field-conditions-match' );
				var is_visible = false;
				if( match == 'all' ) {
					is_visible = true;
				}
				var loop_parent = parent; // 3.26.5

				for( var i in conditions ) {
					var condition = conditions[i];
					if ( condition.field == 'cost' ) {
						// 3.21.7
						var field_value = $( '#pewc_total_calc_price' ).val();
					} else if ( condition.field == 'quantity' ) {
						var field_value = $( 'form.cart .quantity input.qty' ).val();
					} else if ( condition.field.substring(0, 3) == 'pa_' ){
						var attribute_field = $( '#' + condition.field );
						var field_value = $( attribute_field ).val();
						if ( field_value != undefined && field_value.indexOf( ',' ) !== -1 && $( attribute_field ).is( 'input' ) ) {
							// 3.26.11, this could be a hidden input field with a list of attributes, split into an array?
							field_value = field_value.split( ',' );
						}
					} else {
						if ( $( parent ).find( '.pewc-field-' + $( '.' + condition.field ).attr( 'data-field-id' ) ).length < 1 ) {
							// this field is not a sibling in this repeatable field? change parent back
							loop_parent = pewc_conditions.get_field_parent( $( field ) );
						} else {
							loop_parent = parent;
						}
						var field_value = this.get_field_value( $( '.' + condition.field ).attr( 'data-field-id' ), condition.field_type, loop_parent );
					}
					var meets_condition = this.field_meets_condition( field_value, condition.rule, condition.value );
					if( meets_condition && match == 'any' ) {
						return true;
					} else if( ! meets_condition && match =='all' ) {
						return false;
					}
				}

				return is_visible;

			},

			// Get the value of the specified field
			get_field_value: function( field_id, field_type, parent ) {

				if( typeof field_id == 'undefined' ) {
					return;
				}

				// var field_wrapper = $( '.' + field_id.replace( 'field', 'group' ) );
				var input_fields = ['text','number','advanced-preview'];

				var field = $( parent ).find( '.pewc-field-' + field_id );

				// since 3.11.5
				if ( field.hasClass( 'pewc-hidden-field') || field.closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden' ) ) {
					if ( ! field.hasClass( 'pewc-reset-me' ) ) {
						field.addClass( 'pewc-reset-me' ); // so that we can reset
					}
					return ''; // field is hidden so return a blank value
				}

				if( input_fields.includes( field_type ) ) {
					return $( field ).find( 'input' ).val();
				} else if( field_type == 'select' || field_type == 'select-box' ) {
					return $( field ).find( 'select' ).val();
				} else if( field_type == 'checkbox_group' ) {
					var field_value = [];
					$( field ).find( 'input:checked' ).each( function() {
						field_value.push( $( this ).val() );
					});
					return field_value;
				} else if( field_type == 'products' || field_type == 'product-categories' ) {
					var field_value = [];
					if ( field.hasClass( 'pewc-item-products-select' ) ) {
						return $( field ).find( 'select' ).val();
					}
					else {
						$( field ).find( 'input:checked' ).each( function() {
							field_value.push( Number( $( this ).val() ) );
						});
					}
					return field_value;
				} else if( field_type == 'image_swatch' ) {
					if( $( field ).hasClass( 'pewc-item-image-swatch-checkbox' ) ) {
						// Array
						var field_value = [];
						$( field ).find( 'input:checked' ).each( function() {
							field_value.push( $( this ).val() );
						});
						return field_value;
					} else {
						return $( field ).find( 'input:radio:checked' ).val();
					}
				} else if( field_type == 'checkbox' ) {
					if( $( field ).find( 'input' ).prop( 'checked' ) ) {
						return '__checked__';
					}
					return false;
				} else if( field_type == 'radio' ) {
					return $( field ).find( 'input:radio:checked' ).val();
				} else if( field_type == 'quantity' ) {
					return $( 'form.cart .quantity input.qty' ).val();
				} else if( field_type == 'cost' ) {
					return $( '#pewc_total_calc_price' ).val();
				} else if( field_type == 'upload' ) {
					return $( field ).find( '.pewc-number-uploads' ).val();
				} else if( field_type == 'calculation' ) {
					return $( field ).find( '.pewc-calculation-value' ).val();
				}

			},

			field_meets_condition: function( value, rule, required_value ) {
				if (value == undefined ) {
					return false;
				} else if( rule == 'is') {
					return value == required_value;
				} else if( rule == 'is-not' ) {
					return value != required_value;
				} else if( rule == 'contains' ) {
					if ( typeof required_value === 'string' && required_value.indexOf( ',' ) !== -1 ) {
						// 3.13.7, comma-separated product categories IDs
						return this.csv_required_value_in_field_value( value, required_value );
					} else {
						// sometimes value is an array of numbers (e.g. product IDs) so we have to parse required_value first
						return value.includes( required_value ) || value.includes( parseFloat( required_value ) );
					}
				} else if( rule == 'does-not-contain' ) {
					if ( typeof required_value === 'string' && required_value.indexOf( ',' ) !== -1 ) {
						// 3.13.7, comma-separated product categories IDs
						return ! this.csv_required_value_in_field_value( value, required_value );
					} else {
						return ! value.includes( required_value ) && ! value.includes( parseFloat( required_value ) );
					}
				} else if ( rule == 'cost-equals' ) {
					return parseFloat(value) == parseFloat(required_value);
				} else if( rule == 'greater-than' || rule == 'cost-greater' ) {
					return parseFloat(value) > parseFloat(required_value);
				} else if( rule == 'greater-than-equals' ) {
					return parseFloat(value) >= parseFloat(required_value);
				} else if( rule == 'less-than' || rule == 'cost-less' ) {
					return parseFloat(value) < parseFloat(required_value);
				} else if( rule == 'less-than-equals' ) {
					return parseFloat(value) <= parseFloat(required_value);
				}

			},

			// 3.13.7
			csv_required_value_in_field_value: function( field_value, required_value ) {
				var required_values = required_value.split( ',' );
				for ( var i in required_values ) {
					if ( ! field_value.includes( required_values[i] ) && ! field_value.includes( parseFloat( required_values[i] ) ) ) {
						return false;
					}
				}
				return true;
			},

			assign_group_classes: function( conditions_obtain, action, group_id ) {

				if( conditions_obtain ) {
					if( action == 'show' ) {
						$( '#pewc-group-' + group_id ).removeClass( 'pewc-group-hidden' );
						$( '#pewc-tab-' + group_id ).removeClass( 'pewc-group-hidden' );
						$( '#pewc-group-' + group_id ).removeClass( 'pewc-reset-group' );
						$( '#pewc-tab-' + group_id ).removeClass( 'pewc-reset-group' );
					} else {
						$( '#pewc-group-' + group_id ).addClass( 'pewc-group-hidden pewc-reset-group' );
						$( '#pewc-tab-' + group_id ).addClass( 'pewc-group-hidden pewc-reset-group' );
					}
				} else {
					if( action == 'show' ) {
						$( '#pewc-group-' + group_id ).addClass( 'pewc-group-hidden pewc-reset-group' );
						$( '#pewc-tab-' + group_id ).addClass( 'pewc-group-hidden pewc-reset-group' );

						// $( '#pewc-group-' + group_id ).find( '.pewc-field-triggers-condition' ).each( function() {
							// Check each field in this group, in case of conditions on the fields
							// $( this ).find( 'input' ).trigger( 'change' );
							// pewc_conditions.trigger_field_condition_check_by_id( $( this ).attr( 'data-field-id' ) );
						// });
					} else {
						$( '#pewc-group-' + group_id ).removeClass( 'pewc-group-hidden' );
						$( '#pewc-tab-' + group_id ).removeClass( 'pewc-group-hidden' );
						$( '#pewc-group-' + group_id ).removeClass( 'pewc-reset-group' );
						$( '#pewc-tab-' + group_id ).removeClass( 'pewc-reset-group' );
					}
				}

				// moved here since 3.11.5. Let's always trigger this because sometimes another field is dependent on a just-hidden field
				pewc_conditions.trigger_fields_within_hidden_groups( group_id );
				// also moved here since 3.11.5 because the replace main image function for image swatch depends on this
				$( 'body' ).trigger( 'pewc_group_visibility_updated', [ group_id, action ] );
				$( 'body' ).trigger('pewc_force_update_total_js'); // added in 3.11.9

				// Iterate through each field in the group to check for layered swatches
				$( '#pewc-group-' + group_id ).find( '.pewc-item' ).each( function( layer_index, element ) {
					pewc_conditions.hide_layered_images( $( this ), $( this ).attr( 'data-field-id' ) );
					// 3.26.6, trigger this in case group has condition but field does not, needed when editing options from the cart
					$( 'body' ).trigger( 'pewc_field_visibility_updated', [ $( this ).attr('data-id'), action ] );
				});

			},

			trigger_fields_within_hidden_groups: function( group_id ) {

				$( '#pewc-group-' + group_id ).find( '.pewc-field-triggers-condition' ).each( function() {
					// Check each field in this group, in case of conditions on the fields
					var field = $( '.pewc-field-' + $( this ).attr( 'data-field-id' ) );
					var parent = pewc_conditions.get_field_parent( field );
					var field_value = pewc_conditions.get_field_value( $( field ).attr( 'data-field-id' ), $( field ).attr( 'data-field-type' ), parent );
					var triggers_for = JSON.parse( $( field ).attr( 'data-triggers-for' ) );

					// 3.26.5
					pewc_conditions.check_triggered_fields( $( field ), field_value, triggers_for, parent );

					// Iterate through each field that is conditional on the updated field
					//for( var g in triggers_for ) {
					//	conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], field_value, parent );
					//	var group = $( '.pewc-field-' + triggers_for[g] ).closest( '.pewc-group-wrap' );
					//	var action = $( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
					//	pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], parent );
					//}

				});

				if( pewc_vars.reset_fields == 'yes' ) {
					// pewc_conditions.reset_fields();
				}

			},

			assign_field_classes: function( conditions_obtain, action, field_id, parent ) {

				var field = $( parent ).find( '.pewc-field-' + field_id );
				//$( 'body' ).trigger( 'pewc_field_visibility_updated', [ field.attr('data-id'), action ] ); // commented out on 3.21.7

				if( conditions_obtain ) {
					if( action == 'show' ) {
						$( field ).removeClass( 'pewc-hidden-field' );
						$( field ).removeClass( 'pewc-reset-me' ); // 3.26.6
						$( parent ).removeClass( 'pewc-hidden-field-' + $( field ).attr( 'data-field-id' ) );
					} else {
						if( ! $( field ).hasClass( 'pewc-hidden-field' ) ) {
							$( field ).addClass( 'pewc-hidden-field pewc-reset-me' );
							$( parent ).addClass( 'pewc-hidden-field-' + $( field ).attr( 'data-field-id' ) );
						}
					}
				} else {
					if( action == 'show' ) {
						if( $( field ).hasClass( 'pewc-item-advanced-preview' ) ) {
							$( parent ).addClass( 'pewc-hidden-field-' + $( field ).attr( 'data-field-id' ) );
						}
						// 3.24.8, changed hasClass selector from pewc-hidden-field to pewc-reset-me, because sometimes a field already has pewc-hidden-field and so pewc-reset-me is not added
						// 3.25.3, updated this again, because the changes in 3.24.8 didn't work for some calculation fields with conditions
						if( ! $( field).hasClass( 'pewc-reset-me' ) || ! $( field).hasClass( 'pewc-hidden-field' ) ) {
							// avoid having duplicates
							$( field ).removeClass( 'pewc-hidden-field' );
							$( field ).removeClass( 'pewc-reset-me' );
							// add both back
							$( field ).addClass( 'pewc-hidden-field pewc-reset-me' );
						}
					} else {
						$( field).removeClass( 'pewc-hidden-field' );
						$( parent ).removeClass( 'pewc-hidden-field-' + $( field ).attr( 'data-field-id' ) );
					}
				}

				// Hide layered images
				if( $( field ).hasClass( 'pewc-layered-image' ) ) {
					pewc_conditions.hide_layered_images( field, field_id );
				}

				// 3.21.7, moved here so that field visibility has already been updated before other plugins hook into this
				$( 'body' ).trigger( 'pewc_field_visibility_updated', [ field.attr('data-id'), action ] );

			},

			hide_layered_images: function( field, field_id ) {

				var group_id = pewc_conditions.get_field_group_id( field );
				var is_field_hidden = $( field ).hasClass( 'pewc-hidden-field' );
				var is_group_hidden = $( '.pewc-group-wrap-' + group_id ).hasClass( 'pewc-group-hidden' );

				if( is_field_hidden || is_group_hidden ) {
					// Hide the layer too
					$( '.pewc-layer-' + field_id ).fadeOut( 150 );
				} else {
					$( '.pewc-layer-' + field_id ).fadeIn( 150 );
				}

			},

			reset_fields: function() {

				if( $( '.pewc-reset-me' ).length < 1 && $( '.pewc-reset-group' ).length < 1 ) {
					return;
				}

				$( '.pewc-reset-me' ).each( function() {

					var field = $( this );
					pewc_conditions.reset_field_value( field );
					$( field ).removeClass( 'pewc-reset-me' ).addClass( 'pewc-reset' );

				});

				$( '.pewc-reset-group' ).each( function() {

					$( this ).find( '.pewc-item' ).each( function() {

						var field = $( this );
						pewc_conditions.reset_field_value( field );

					});

				});

			},

			// improvement: maybe don't apply default_values here, which can cause issues in the backend if field is hidden, and another field is dependent on the hidden field
			reset_field_value: function( field ) {

				// Iterate through all fields with pewc-reset-me class
				var inputs = ['date', 'name_price', 'number', 'text', 'textarea', 'advanced-preview'];
				var checks = ['checkbox', 'checkbox_group', 'radio'];
				var field_type = $( field ).attr( 'data-field-type' );
				var default_value = $( field ).attr( 'data-default-value' );
				$( field ).attr( 'data-field-value', default_value );

				if( inputs.includes( field_type ) ) {
					// 3.21.1, the trigger is needed by Text Preview, but could cause an infinite loop, so we check if the default value has already been added
					if ( ( default_value || default_value == '' ) && $( field ).find( '.pewc-form-field' ).val() != default_value ) {
						$( field ).find( '.pewc-form-field' ).val( default_value ).trigger( 'change' );
					}
				} else if( field_type == 'image_swatch' ) {
					// 3.17.2 version, removes the swatch from the main image if swatch field is hidden
					$( field ).find( '.pewc-radio-image-wrapper, .pewc-checkbox-image-wrapper' ).each(function(){
						if ( $(this).hasClass( 'checked' ) ) {
							$(this).removeClass( 'checked' ); // needed for swatch fields where "allow multiple" is enabled
							$(this).trigger( 'click' ); // this triggers the update_add_on_image() function in pewc.js
						}
						if ( $(this).find( 'input' ).val() == default_value ) {
							// this field has a default value, add checked class back
							$(this).addClass( 'checked' );
							$(this).trigger( 'click' ); // 3.24.8, added to trigger swatches with conditions and layers
						} else {
							$(this).find( 'input' ).prop( 'checked', false );
						}
					});
					// 3.24.6, maybe remove the layer if we're resetting anyway? So that when the field is shown again, the layer doesn't contain the last selection
					if ( default_value === '' && $( field ).hasClass( 'pewc-layered-image' ) ) {
						$( '.pewc-layer-' + $( field ).attr( 'data-field-id' ) ).remove();
					}
				} else if( field_type == 'products' || field_type == 'product-categories' ) {
					if ( ( field ).hasClass( 'pewc-item-products-select' ) ) {
						// 3.25.5
						$( field ).find( '.pewc-form-field' ).prop( 'selectedIndex', 0 );
					} else {
						$( field ).find( 'input' ).prop( 'checked', false );
						$( field ).find( '.pewc-form-field' ).val( 0 );
						$( field ).find( '.pewc-radio-image-wrapper, .pewc-checkbox-image-wrapper' ).removeClass( 'checked' );
					}
					// 3.13.0, put back default value
					// 3.27.2, this is now also done in put_back_default_products(), called by put_back_default when a product is shown
					// put_back_default_products for now only works for checkboxes and radio layout, but maybe transition the function below when the need arises?
					if ( default_value ) {
						// convert new value to a valid ID string first (e.g. no spaces, lower case)
						var default_value2 = default_value.toLowerCase().replaceAll(' ', '_');
						$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).prop( 'checked', true );
						$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).closest( '.pewc-radio-image-wrapper, .pewc-checkbox-image-wrapper' ).addClass( 'checked' );
					}
				} else if( checks.includes( field_type ) ) {
					$( field ).find( 'input' ).prop( 'checked', false );
					// 3.23.1
					if ( $( field ).hasClass( 'pewc-text-swatch' ) ) {
						$( field ).find( '.active-swatch' ).removeClass( 'active-swatch' );
					}
					default_value = $( field ).hasClass( 'pewc-hidden-field' ) ? '' : $( field ).attr( 'data-default-value' ); // 3.21.7, if checkbox is hidden, nullify the value, so that conditions based on this does not match in the backend
					if ( default_value ) {
						if ( field_type === 'checkbox' ) {
							$( field ).find( 'input' ).prop( 'checked', true ); // 3.17.2
							// 3.23.1
							if ( $( field ).hasClass( 'pewc-text-swatch' ) ) {
								$( field ).find( 'input' ).closest( '.pewc-checkbox-form-label' ).addClass( 'active-swatch' );
							}
						} else {
							// convert new value to a valid ID string first (e.g. no spaces, lower case)
							var default_value2 = default_value.toLowerCase().replaceAll(' ', '_');
							$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).prop( 'checked', true );
							// 3.23.1
							if ( field_type === 'radio' && $( field ).hasClass( 'pewc-text-swatch' ) ) {
								$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).closest( '.pewc-radio-form-label' ).addClass( 'active-swatch' ); // 3.23.1
							}
						}
					}
				} else if ( field_type == 'select' ) {
					if( default_value ) {
						$( field ).find( '.pewc-form-field' ).val( default_value );
					} else {
						$( field ).find( '.pewc-form-field' ).prop( 'selectedIndex', 0 );
					}
				} else if( field_type == 'select-box' ) {
					// 3.25.2, separated this from the reset process for Select fields
					// Reselect option for Select Box
					var select_box_id = $( field ).attr( 'data-id' );
					if ( $( '#' + select_box_id + '_select_box' ).find( 'ul.dd-options' ).length > 0 ) {
						// select box ready?
						var selected_value = $( field ).find( '.dd-selected-value' ).val();
						var select_option_index = 0;
						// find the index of the current value
						select_option_index = $( 'select#' + select_box_id + ' option[value="' + selected_value.replace( /"/g, '\\"' ) + '"]').index();
						if ( default_value ) {
							if ( default_value != selected_value ) {
								// find index of the default value
								select_option_index = $( 'select#' + select_box_id + ' option[value="' + default_value.replace( /"/g, '\\"' ) + '"]').index();
								$( '#' + select_box_id + '_select_box' ).ddslick( 'select', {index: select_option_index} );
							}
						} else if ( select_option_index > 0 ) {
							// reset to the first value
							$( '#' + select_box_id + '_select_box' ).ddslick( 'select', {index: 0} );
						}
					}
					if( default_value ) {
						$( field ).find( '.pewc-form-field' ).val( default_value );
					} else {
						$( field ).find( '.pewc-form-field' ).val( '' );
						$( field ).attr( 'data-value', '' );
					}
					
				} else if( field_type == 'calculation' ) {
					$( field ).attr( 'data-price', 0 ).attr( 'data-field-price', 0 );
					var action = $( field ).find( '.pewc-action' ).val();
					if( pewc_vars.conditions_timer > 0 ) {
						if( action == 'price' ) {
							$( '#pewc_calc_set_price' ).val( 0 );
							$( field ).find( '.pewc-calculation-value' ).val( 0 ).trigger( 'change' );
						} else {
							$( field ).find( '.pewc-calculation-value' ).val( 0 );
						}
					} else {
						// This is an older method with some performance issues
						$( field ).find( '.pewc-calculation-value' ).val( 0 ).trigger( 'change' );
						if( action == 'price' ) {
							$( '#pewc_calc_set_price' ).val( 0 );
						}
					}
				} else if ( field_type == 'color-picker' ) {
					// 3.17.2
					if ( default_value ) {
						$( field ).find( '.pewc-color-picker-field' ).val( default_value ).trigger( 'change' );
					} else {
						$( field ).find( '.pewc-color-picker-field' ).val( '' ).trigger( 'change' );
					}
				}

				// Does this trigger a group?
				if( $( field ).attr( 'data-trigger-groups' ) ) {
					var groups = JSON.parse( $( field ).attr( 'data-trigger-groups' ) );
					pewc_conditions.trigger_group_conditions( groups );
				}

				if ( $( field ).attr( 'data-field-value') != '' && ! $( field ).hasClass( 'pewc-active-field') ) {
					// this is added so that the summary panel can detect the field
					$( field ).addClass( 'pewc-active-field' );
				}
				// 3.12.2
				if ( $( field ).attr( 'data-field-value') == '' && $( field ).attr( 'data-field-price' ) != 0 ) {
					// maybe also reset price
					$( field ).attr( 'data-field-price', 0 );
				}
				// we force update_total_js so that the summary panel is also updated
				$( 'body' ).trigger('pewc_force_update_total_js');
				$( 'body' ).trigger( 'pewc_reset_field_condition' );

			},

			// since 3.11.9, not sure if this is necessary if the group already has the class?
			group_has_attribute_conditions: function( group ) {

				if ( group.attr( 'data-condition-action') != '' && group.attr( 'data-conditions-match' ) != '' && group.attr( 'data-conditions' ) != '' ) {
					var data_conditions = JSON.parse( group.attr( 'data-conditions' ) );
					if ( data_conditions.length > 0 ) {
						var has_attribute_condition = false;
						for ( var i in data_conditions ) {
							if ( data_conditions[i].field.substring( 0, 3 ) == 'pa_' ) {
								has_attribute_condition = true;
								break;
							}
						}
						return has_attribute_condition;
					}
				}
				return false;

			},

			// since 3.11.9
			trigger_groups_with_attribute_conditions: function( event, variation, purchasable ) {

				$( '.pewc-group-wrap.pewc-has-attribute-condition' ).each( function() {
					var group = $( this );

					// check if this group has conditions
					//if ( pewc_conditions.group_has_attribute_conditions( group ) ) {
						// this group is dependent on attributes, check now
						var group_id = parseFloat( group.attr( 'id' ).replace( 'pewc-group-', '' ) );
						conditions_obtain = pewc_conditions.check_group_conditions( group_id );
						var action = $( '#pewc-group-' + group_id ).attr( 'data-condition-action' );
						pewc_conditions.assign_group_classes( conditions_obtain, action, group_id );
					//}
				});

			},

			// since 3.11.9
			trigger_fields_with_attribute_conditions: function( event, variation, purchasable ) {

				$( '.pewc-item.pewc-field-has-attribute-condition' ).each( function() {

					var field = $( this );
					var field_id = field.attr( 'data-field-id' );
					var parent = pewc_conditions.get_field_parent( field );

					conditions_obtain = pewc_conditions.check_field_conditions( field_id, '', parent );
					var action = $( '.pewc-field-' + field_id ).attr( 'data-field-conditions-action' );

					pewc_conditions.assign_field_classes( conditions_obtain, action, field_id, parent );

					if( pewc_vars.reset_fields == 'yes' ) {
						pewc_conditions.reset_fields();
					}

				});

			},

			// since 3.11.9
			trigger_attribute_condition_check: function( event, variation, purchasable ) {

				pewc_conditions.trigger_groups_with_attribute_conditions( event, variation, purchasable );
				pewc_conditions.trigger_fields_with_attribute_conditions( event, variation, purchasable );

			},

			// 3.26.5, created a separate function for this loop
			check_triggered_fields: function( field, field_value, triggers_for, parent=false ) {

				var is_repeatable_field = false;
				var is_cloned_field = false;
				var group_wrapper = $( field ).closest( '.pewc-group-wrap' );

				if ( field != 'cost' && field != 'quantity' ) {
					// was this triggered from a field inside a repeatable group?
					is_repeatable_field = $( group_wrapper ).hasClass( 'pewc-repeatable-group' );
					// was this triggered from a cloned field?
					is_cloned_field = $( group_wrapper ).hasClass( 'pewc-cloned-group' );
				}

				// we use a different var so that it doesn't get overwritten in the loop
				// trigger_quantity_condition_check and trigger_cost_condition_check sets the parent var inside the loop
				var loop_parent = parent;

				// Iterate through each field that is conditional on the updated field
				for( var g in triggers_for ) {
					if ( ! parent ) {
						loop_parent = pewc_conditions.get_field_parent( $( '.pewc-field-'+triggers_for[g] ) );
					} else if ( is_repeatable_field ) {
						// this was triggered from a repeatable field, check if the current field is a sibling
						if ( $( group_wrapper ).find( '.pewc-field-' + triggers_for[g] ).length > 0 ) {
							// this is a sibling, change the loop_parent to the group_wrapper so that we only look for this field within this group
							loop_parent = group_wrapper;
						} else {
							// not a sibling, point back to the original parent
							loop_parent = parent;
						}
					}
					conditions_obtain = pewc_conditions.check_field_conditions( triggers_for[g], field_value, loop_parent );
					var action = $( loop_parent ).find( '.pewc-field-' + triggers_for[g] ).attr( 'data-field-conditions-action' );
					pewc_conditions.assign_field_classes( conditions_obtain, action, triggers_for[g], loop_parent );
				}

			},

			// 3.26.11, put back a field's default value if it has been displayed
			put_back_default: function( pewc_id ) {

				var field = $( '.pewc-item.' + pewc_id );
				if ( field.length > 0 && ! field.hasClass( 'pewc-hidden-field' ) && ! field.closest( '.pewc-group-wrap' ).hasClass( 'pewc-group-hidden' ) && field.attr( 'data-default-value' ) != undefined && field.attr( 'data-default-value' ) != '' ) {
					var default_value = field.attr( 'data-default-value' );

					if ( 'checkbox' === field.attr( 'data-field-type' ) && 'checked' === default_value ) {
						field.find( 'input#' + pewc_id ).prop( 'checked', true ).trigger( 'change' );
					} else if ( 'products' === field.attr( 'data-field-type' ) || 'product-categories' === field.attr( 'data-field-type' ) ) {
						// 3.27.2
						pewc_conditions.put_back_default_products( field, default_value );
					}
				}

			},

			// 3.27.2, we will support other layouts when the need arises
			put_back_default_products: function( field, default_value ) {

				var is_independent = field.hasClass( 'pewc-item-products-independent');
				var default_value2;

				if ( field.hasClass( 'pewc-item-products-checkboxes' ) || field.hasClass( 'pewc-item-products-checkboxes-list' ) ) {
					// default could be a comma-separated list
					default_value2 = default_value.split( ',' );

					for ( var i in default_value2 ) {
						$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2[i] ).prop( 'checked', true );
						$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2[i] ).closest( '.pewc-checkbox-wrapper' ).addClass( 'checked' );
						if ( is_independent ) {
							$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2[i] ).closest( '.pewc-checkbox-wrapper' ).find( '.pewc-child-quantity-field' ).val( 1 );
						}
					}
				} else if ( field.hasClass( 'pewc-item-products-radio' ) || field.hasClass( 'pewc-item-products-radio-list' ) ) {
					default_value2 = default_value.toLowerCase().replaceAll(' ', '_'); // maybe not needed if this is an ID?
					$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).prop( 'checked', true );
					$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).closest( '.pewc-radio-wrapper' ).addClass( 'checked' );
					if ( is_independent ) {
						$( '#' + $( field ).attr( 'data-id' ) + '_' + default_value2 ).closest( '.pewc-item-field-wrapper' ).find( '.pewc-child-quantity-field' ).val( 1 );
					}
				}

			},

		}

		pewc_conditions.init();

	});

})(jQuery);
