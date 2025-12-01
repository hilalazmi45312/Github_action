(function($) {
	$(document).ready(function() {

		const pewc_progress_text = $('.pewc-progress-text'); // default layout
		const pewc_bar = $( '.progress-bar' ); // default layout
		const pewc_bar_percent_steps = $("#progress-bar-percent-steps"); // percent steps layout
		
		let pewc_groups_fields_visible, pewc_groups_fields_visible_active;
		let pewc_groups_wrap = $('.pewc-product-extra-groups-wrap');
		let pewc_groups_layout = pewc_groups_wrap.hasClass('pewc-groups-accordion') ? 'accordion' :
									pewc_groups_wrap.hasClass('pewc-groups-steps') ? 'steps' :
									pewc_groups_wrap.hasClass('pewc-groups-tabs') ? 'tabs' :
									pewc_groups_wrap.hasClass('pewc-groups-lightbox') ? 'lightbox' : 'standard';
									
		pewc_relocate_progress_bar();
		pewc_update_progress_bar();
		$('.pewc-item, .pewc-variable-child-select').change(function(e) {
			pewc_update_progress_bar();
		});
		$('.pewc-form-field, .pewc-grid-quantity-field').keyup(function(e) {
			pewc_update_progress_bar();
		});
		$('body').on('pewc_trigger_color_picker_change, pewc_trigger_calculations', function() {
			pewc_update_progress_bar();
		});
		$('.pewc-color-picker-field').on('change', function() {
			if ( $(this).hasClass('iris-error') || $(this).val() === '#' ) {
				$(this).val('');
			}
		});
		$('.variations_form').on('woocommerce_variation_select_change', function(event, variation) {
			pewc_update_progress_bar();
		});
		function pewc_update_progress_bar() {
			setTimeout(function() {
				let pewc_groups_visible = new Set();
				let pewc_groups_completed = new Set();
				let pewc_fields_total = 0;
				let pewc_fields_active = 0;
				let pewc_exclude_field_types = pewc_vars.exclude_field_types;
				let pewc_exclude_groups = pewc_vars.exclude_groups;
				let pewc_required_fields_only = pewc_vars.required_fields_only;
				let pewc_percentage_complete_by_groups = pewc_vars.complete_by_groups;
				$('.pewc-group-wrap:not(.pewc-group-hidden, .pewc-form-field-clone)').each(function(i, el) {
					if ( ! pewc_exclude_groups.includes( $(el).attr('data-group-id') ) ) {
						pewc_groups_visible.add( $(el).attr('data-group-id') );
					}
				});
				pewc_groups_fields_visible = [];
				pewc_groups_fields_visible_active = [];
				for ( let group_id of pewc_groups_visible ) {
					$fields = $('.pewc-group-wrap-'+group_id+':not(.pewc-form-field-clone) .pewc-item'+(pewc_required_fields_only?'.required-field':'')
									+ ':not(.pewc-hidden-field, .pewc-visibility-hidden, .pewc-variation-dependent:not(.active))');
					if ( $fields.length > 0 ) {
						pewc_groups_fields_visible[group_id] = new Set();
					}
					let current_group_fields_active = new Set();
					$fields.each(function(i, el) {
						if ( ! pewc_exclude_field_types.includes( $(el).attr('data-field-type') ) 
								&& ! ( $(el).attr('data-field-type') == 'number' && $(el).is(':hidden') ) // combined quantity field (adv. uploads)
						) {
							pewc_groups_fields_visible[group_id].add( $(el).attr('data-field-id') );
							pewc_fields_total++;
							if ( $(el).hasClass('pewc-active-field') && ! $(el).hasClass('pewc-item-color-picker') ) {
								pewc_fields_active++;
								let remove_item = false;
								current_group_fields_active.add( $(el).attr('data-field-id') );
								switch( $(el).attr('data-field-type') ) {
									case 'image_swatch':
									case 'radio':
									case 'select':
									case 'select-box':
										if ( $(el).attr('data-field-value') === '' ) {
											remove_item = true;
										}
										break;
									case 'upload':
										if ( $(el).attr('data-field-value') === '0' ) {
											remove_item = true;
										}
										break;
									case 'products':
									case 'product-categories':
										// radio images / radio list / select - independent - qty field
										if ( $(el).find( '.pewc-independent-quantity-field' ).val() === '0' ||
												$(el).find( '.pewc-independent-quantity-field' ).val() === '' ) {	
											remove_item = true;
										}
										break;
								}
								if ( remove_item ) {
									pewc_fields_active--;
									current_group_fields_active.delete( $(el).attr('data-field-id') );
								}
							} else if ( $(el).hasClass('pewc-item-products-swatches') ) {
								if ( $(el).find('.pewc-child-variation-main').hasClass('checked') ) {
									pewc_fields_active++;
									current_group_fields_active.add( $(el).attr('data-field-id') );
								}
							} else if ( $(el).hasClass('pewc-item-products-grid') ) {
								let total_variations = 0;
								$(el).find('.pewc-grid-quantity-field').each( function() {
									total_variations += Number( $(this).val() );
								} );
								if ( total_variations > 0 ) {
									pewc_fields_active++;
									current_group_fields_active.add( $(el).attr('data-field-id') );
								}
							} else if ( $(el).hasClass('pewc-item-color-picker') ) {
								let color = $(el).find('.pewc-color-picker-field').val();
								if ( color !== '' && ! $(el).find('.pewc-color-picker-field').hasClass('iris-error') ) {
									pewc_fields_active++;
									current_group_fields_active.add( $(el).attr('data-field-id') );
								}
							}
						}
					});
					if ( current_group_fields_active.size > 0 ) {
						pewc_groups_fields_visible_active[group_id] = current_group_fields_active;
						if ( pewc_groups_fields_visible_active[group_id].size == pewc_groups_fields_visible[group_id].size ) {
							pewc_groups_completed.add( group_id );
						}
					}
				}
				let percentage_complete = 0;
				let groups_count = Object.keys( pewc_groups_fields_visible ).length;
				if ( pewc_percentage_complete_by_groups ) {
					pewc_progress_text.text( pewc_get_formatted_progress_text( pewc_groups_completed.size, groups_count, 
												pewc_percentage_complete_by_groups, pewc_required_fields_only ) );
					percentage_complete = ( pewc_groups_completed.size/groups_count ) * 100;
				} else {
					pewc_progress_text.text( pewc_get_formatted_progress_text( pewc_fields_active, pewc_fields_total, 
												pewc_percentage_complete_by_groups, pewc_required_fields_only ) );
					percentage_complete = ( pewc_fields_active/pewc_fields_total ) * 100;
				}
				if ( pewc_bar.length ) {
					pewc_bar.width( percentage_complete + '%' );
					pewc_fields_total ? pewc_bar.closest('.progress').show() : pewc_bar.closest('.progress').hide();	
				} else if ( pewc_bar_percent_steps.length ) {
					let step_circles = pewc_update_percent_steps( pewc_percentage_complete_by_groups ? groups_count : pewc_fields_total );
					let step_count_active = 0;
					$(step_circles).each(function(i, circle) {
						if ( ($(circle).attr('data-percent-calculated') / ($(step_circles[step_circles.length-1]).attr('data-percent-calculated')) * 100) <= percentage_complete ) {
							$(circle).addClass('active');
							step_count_active++;
						}
					});
					pewc_bar_percent_steps.css( 'width', ((step_count_active - 1) / (step_circles.length - 1)) * 100 + "%" );
					pewc_fields_total ? pewc_bar_percent_steps.closest('.pewc-progress-wrapper-percent-steps').show() : pewc_bar_percent_steps.closest('.pewc-progress-wrapper-percent-steps').hide();
				}
				pewc_update_group_progress();
				if ( pewc_vars.progress_bar_log ) {
					console.log( pewc_groups_fields_visible );
					console.log( pewc_groups_fields_visible_active );
				}
			}, pewc_vars.progress_bar_timeout);
		};

		function pewc_update_percent_steps( total ) {
			let step_circles = pewc_bar_percent_steps.siblings('.circle').removeClass('active inactive');
			let step_size = Math.max( 1, Math.ceil( total / step_circles.length ) );
			let display_percent_steps = Array.from({length: Math.ceil(total/step_size)}, (_, i) => 1 + i * step_size);
			let display_step_circles = [];
			step_circles.each(function(i, circle) {
				if ( i < display_percent_steps.length ) {
					let percent_calculated = i + 1;
					let percent_calculated_text = percent_calculated; 
					if ( $(step_circles[step_circles.length-1]).attr('data-percent') == 100 ) {
						percent_calculated = Math.floor( ( 100 / display_percent_steps.length ) * (i+1) );
						percent_calculated_text = percent_calculated + '%';
					}
					$(circle).attr('data-percent-calculated', percent_calculated).text( percent_calculated_text ).show();
					display_step_circles.push( circle );
				} else {
					$(circle).addClass('inactive').attr('data-percent-calculated', '').text('').hide();
				}
			});
			return display_step_circles;
		}
		function pewc_get_formatted_progress_text( active, total, percentage_complete_by_groups, required_fields_only ) {
			let progress_text = eval( pewc_vars.progress_text );
			if ( progress_text ) {
				return progress_text;
			}
			progress_text = {
				'group_required': `${active} / ${total}`,
				'group': `${active} / ${total}`,
				'item_required': `${active} / ${total}`,
				'item': `${active} / ${total}`
			};
			return progress_text[ (percentage_complete_by_groups ? 'group' : 'item') + (required_fields_only ? '_required' : '') ];
		}
		function pewc_add_group_progress() {
			if ( pewc_groups_layout == 'tabs' || pewc_groups_layout == 'steps' ) {
				pewc_groups_wrap.find('.pewc-'+pewc_groups_layout+'-wrapper .group-progress').remove();
				pewc_groups_fields_visible.forEach(function(fields, group_id) {
					pewc_groups_wrap.find('.pewc-'+pewc_groups_layout+'-wrapper #pewc-tab-'+group_id)
						.html($('.pewc-group-wrap-'+group_id+' .pewc-group-heading-wrapper h3').text() + '<span class="group-progress"></span>');
				});
			} else {
				$('.pewc-group-wrap .pewc-group-heading-wrapper .group-progress').remove();
				pewc_groups_fields_visible.forEach(function(fields, group_id) {
					$('.pewc-group-wrap-'+group_id+' .pewc-group-heading-wrapper h3').after('<span class="group-progress"></span>');
				});
			}
		}
		function pewc_update_group_progress() {
			if ( ! pewc_vars.group_progress ) {
				return;
			}
			pewc_add_group_progress();
			pewc_groups_fields_visible.forEach(function(fields, group_id) {
				if ( pewc_groups_layout == 'tabs' || pewc_groups_layout == 'steps' ) {
					group_progress = pewc_groups_wrap.find('.pewc-'+pewc_groups_layout+'-wrapper #pewc-tab-'+group_id);
				} else {
					group_progress = $('.pewc-group-wrap-'+group_id+' .pewc-group-heading-wrapper');
				}
				if ( fields.size > 0 ) {
					active = pewc_groups_fields_visible_active[group_id];
					group_progress.find('.group-progress').text( ( active ? active.size : 0 ) +'/'+ fields.size );
				} else {
					group_progress.find('.group-progress').text( '' );
				}
			});	
		}
		function pewc_relocate_progress_bar() {
			pewc_bar.closest('.pewc-progress-wrapper').insertBefore(pewc_groups_wrap.find('.pewc-'+pewc_groups_layout+'-wrapper'));
			pewc_bar_percent_steps.closest('.pewc-progress-wrapper-percent-steps').insertBefore(pewc_groups_wrap.find('.pewc-'+pewc_groups_layout+'-wrapper'));
			if ( pewc_groups_layout == 'lightbox' ) {
				$(document).on('click', '.pewc-lightbox-launch-link', function() {
					$('.pewc-lightbox .pewc-product-extra-groups-wrap.pewc-groups-lightbox .pewc-progress-wrapper').hide();
					$('.pewc-lightbox .pewc-product-extra-groups-wrap.pewc-groups-lightbox .pewc-progress-wrapper-percent-steps').hide();
				});
			}
		}

	});
})(jQuery);
