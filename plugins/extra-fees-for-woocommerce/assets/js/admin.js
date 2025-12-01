jQuery(function ($) {

	var EFW_Toggle = {
		init: function () {
			this.trigger_on_page_load();

			$(document).on('click', ".efw-rules-content h3", this.toggle_section);
			$(document).on('change', '.efw_enable_fee', this.enable_extra_fee);
			$(document).on('change', '.efw-enable-gateway-fee', this.enable_gateway_fee);
			$(document).on('change', '#efw_ordertotalfee_enable', this.enable_ordertotal_fee);
			$(document).on('change', '#efw_ordertotalfee_shipping_based_on', this.shipping_restriction_based_on);

			$(document).on('change', '.efw_productfee_fee_setup', this.fee_based_on);
			$(document).on('change', '#efw_productfee_apply_for', this.apply_for);
			$(document).on('change', '#efw_productfee_product_filters', this.product_filter_type);

			$(document).on('change', '#efw_productfee_fee_type', this.product_fee_type);
			$(document).on('change', '.efw-fee-type', this.extra_fee_type);
			$(document).on('change', '.efw-rule-fee-type', this.extra_rule_fee_type);
			$(document).on('change', '.efw-fee-from', this.fee_from);
			$(document).on('change', '.efw-text-from', this.fee_text_from);
			$(document).on('change', '.efw-fee-type', this.gateway_fee_type);
			$(document).on('change', '.efw-percentage-calculation-based-on', this.percentage_type);
			$(document).on('change', '.efw-percentage-based-on', this.percentage_type_for_shipping);
			$(document).on('change', '#efw_productfee_user_filter', this.user_filter_for_product);
			$(document).on('change', '.efw-user-filter-type', this.user_filter_for_gateway);
			$(document).on('change', '.efw-product-filter-type', this.product_filter_for_gateway);
			$(document).on('change', '.efw-fee-based-on', this.gateway_fee_based_on);
			$(document).on('change', '#efw_ordertotalfee_fee_type', this.order_total_fee_type);
			$(document).on('change', '#efw_ordertotalfee_user_filter', this.user_filter_for_order);
			$(document).on('change', '#efw_ordertotalfee_product_filter', this.product_filter_for_order);
			//fee configuration
			$(document).on('change', '#efw_ordertotalfee_fee_configuration', this.order_fee_select_type_change);

			$(document).on('change', '#efw_productfee_bulk_enable', this.bulk_update_products);
			$(document).on('change', '#efw_productfee_bulk_fee_from', this.bulk_update_fee_from);
			$(document).on('change', '#efw_productfee_bulk_text_from', this.bulk_update_fee_text_from);
			$(document).on('change', '#efw_productfee_bulk_fee_type', this.bulk_update_fee_type);

			$(document).on('click', '.efw-view-report', this.get_update_fee_data);
			//Multiple level fee
			$(document).on('click', ".efw-multiple-fee-remove", this.remove_multiple_fee);
			$(document).on('click', ".efw-add-new-multiple-fee", this.add_new_multiple_fee);
			//Add Rules for Additional Fee.
			$(document).on('click', ".efw-add-new-additional-fee", this.add_rule_for_additional_fee);
			$(document).on('click', '.efw-remove-additional-fee', this.delete_additional_fee_rule);

			//Add Rules for Simple Product.
			$(document).on('click', ".efw-add-rule-for-simple", this.add_rule_for_simple);
			//Add Rules for Simple Product.
			$(document).on('click', ".efw-add-rule-for-variable", this.add_rule_for_variable);
			//Delete Rules.
			$(document).on('click', ".efw-delete-rule", this.delete_rule);
			// Toggle shipping user filter type.
			$(document).on('change', ".efw-shipping-user-filter-type", this.toggle_shipping_user_filter_type);
			$('.efw-shipping-user-filter-type').change();
			// Toggle shipping product filter type.
			$(document).on('change', ".efw-shipping-product-filter-type", this.toggle_shipping_product_filter_type);
			$('.efw-shipping-product-filter-type').change();
			// Toggle shipping fee type.
			$(document).on('change', ".efw-shipping-fee-type", this.toggle_shipping_fee_type);
			$('.efw-shipping-fee-type').change();
			// Toggle enable shipping method fee checkbox.
			$(document).on('change', ".efw-enable-shipping-method-fee", this.toggle_enable_shipping_method_fee_checkbox);
			$('.efw-enable-shipping-method-fee').change();
			//Toggle Tax class setup
			$(document).on('change', "#efw_productfee_tax_setup", this.productfee_tax_setup);
			//Toggle Overall Fee Text
			$(document).on('change', "#efw_productfee_qty_restriction_enabled", this.qty_restriction_enabled);
			$(document).on('change', '.efw-duration-type', this.toggle_reports_duration_type);
			$(document).on('change', '#efw_productfee_show_product_fee_shop', this.toggle_display_product_fee_on_shop);
			$(document).on('click', '.efw-order-fee-rule-checkbox-remove', this.remove_order_fee_multiple_level_rule);
			$(document).on('change', '.efw-order-fee-rule-checkbox-all', this.order_fee_multiple_level_rule_select_all);

			$(document).on('click', '.efw-export-csv', this.export_plugin_settings);

			$(document).on('change', '#efw_advance_combine_fee', this.enable_combine_fee);
			$(document).on('change', '#efw_advance_additional_fee', this.enable_additional_fee);
		},

		trigger_on_page_load: function () {
			$('.efw-rule-fields').hide();
			EFW_Toggle.toggle_fee_based_on('.efw_productfee_fee_setup:checked');
			EFW_Toggle.toggle_user_filter_for_product('#efw_productfee_user_filter');
			EFW_Toggle.toggle_enable_productfee_tax_setup('#efw_productfee_tax_setup');
			EFW_Toggle.toggle_qty_restriction_enabled('#efw_productfee_qty_restriction_enabled');
			EFW_Toggle.toggle_combined_fee('#efw_advance_combine_fee');
			EFW_Toggle.toggle_additional_fee('#efw_advance_additional_fee');

			$('.efw-enable-gateway-fee').each(function () {
				EFW_Toggle.toggle_enable_gateway_fee($(this));
			});

			$('.efw-rule-fee-type').each(function () {
				EFW_Toggle.toggle_extra_rule_fee_type($(this));
			});

			EFW_Toggle.toggle_enable_ordertotal_fee('#efw_ordertotalfee_enable');

			EFW_Toggle.toggle_enable_extra_fee('.efw_enable_fee');

			$('#woocommerce-product-data').on('woocommerce_variations_loaded', function (evt) {
				$('.efw-rule-fields').hide();
				$('.efw_enable_fee').each(function () {
					EFW_Toggle.toggle_enable_extra_fee($(this));
				});
				$('.efw-fee-from').each(function () {
					EFW_Toggle.toggle_fee_from($(this));
				});

				if(!$('.efw-fee-from').length){
					$('.efw-text-from').each(function () {
						EFW_Toggle.toggle_fee_text_from($(this).closest('.efw-fee-wrapper'));
					});
				}
				
				$('.efw-rule-fee-type').each(function () {
					EFW_Toggle.toggle_extra_rule_fee_type($(this));
				});
				$(document.body).trigger('efw-enhanced-init');
			});

			EFW_Toggle.handle_reports_duration_type($('.efw-duration-type'));
			EFW_Toggle.handle_display_product_fee_on_shop($('#efw_productfee_show_product_fee_shop'));
		},

		order_fee_multiple_level_rule_select_all(event) {
			event.preventDefault();
			let $this = $(event.currentTarget);

			$('input:checkbox').not(this).prop('checked', this.checked);
		},

		remove_order_fee_multiple_level_rule(event) {
			event.preventDefault();
			let $this = $(event.currentTarget);

			if (!confirm(efw_admin_param.remove_multiple_fee)) {
				return false;
			}

			$($this).closest('table#efw_multiple_fee_table').find('input:checked').each(function () {
				if ('all' != $(this).data('id') || '0' != $(this).data('id')) {
					$(this).closest('tr').remove();
				}
			});
		},

		toggle_display_product_fee_on_shop(event) {
			event.preventDefault();
			EFW_Toggle.handle_display_product_fee_on_shop($(event.currentTarget));
		},

		handle_display_product_fee_on_shop($this) {
			if ('no' === $($this).val()) {
				$('#efw_productfee_add_to_cart_label').closest('tr').show();
			} else {
				$('#efw_productfee_add_to_cart_label').closest('tr').hide();
			}
		},

		toggle_reports_duration_type(event) {
			event.preventDefault();
			EFW_Toggle.handle_reports_duration_type($(event.currentTarget));
		},

		handle_reports_duration_type($this) {
			if ('custom_date' === $($this).val()) {
				$($this).closest('div').find('.efw-custom-date-range-field').show();
			} else {
				$($this).closest('div').find('.efw-custom-date-range-field').hide();
			}
		},

		remove_multiple_fee: function (event) {
			event.preventDefault();

			if (confirm(efw_admin_param.remove_multiple_fee)) {
				$(this).closest('tr').remove();
			} else {
				return false;
			}
		},

		add_new_multiple_fee: function (event) {
			event.preventDefault();
			var $row_id = $(this).closest('tr').prev('tr').data('id');
			$row_id = $.isNumeric($row_id) ? $row_id : 0;
			$(this).closest('table#efw_multiple_fee_table tr:last').css("background-color", "yellow");
			$row_id = $row_id + 1;
			$(this).closest('table#efw_multiple_fee_table').find('tr:last').prev().after('<tr data-id="' + $row_id + '">\n\
                <td><input type="checkbox" class="efw-order-fee-rule-checkbox" data-id="'+ $row_id + '"></td>\n\
                <td><input type="number" class="efw-min-cart-fee" name="efw_fee_rule['+ $row_id + '][min_cart_fee]" step="any"></td>\n\
                <td><input type="number" class="efw-max-cart-fee" name="efw_fee_rule['+ $row_id + '][max_cart_fee]" step="any"></td>\n\
                <td><select name="efw_fee_rule['+ $row_id + '][fee_type]"><option value="1">Fixed Fee</option><option value="2">Percentage of Cart Subtotal</option><option value="3">Percentage of Order Total</option></select></td>\n\
                <td><input type="number" required="required" class="efw-multiple-fee-value" name="efw_fee_rule['+ $row_id + '][fee_value]" step="any"></td>\n\
                <td><input type="button" class="efw-multiple-fee-remove" value="X"></td></tr>');
		},

		toggle_section: function () {
			$(this).nextUntil('h3').toggle();
		},
		enable_extra_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_enable_extra_fee($this);
		},
		toggle_enable_extra_fee: function ($this) {
			if (true == $($this).is(':checked')) {
				$($this).closest('.efw-fee-wrapper').find('.efw-show-if-extra-fee-enable').show();
				$($this).closest('table').find('.efw-show-if-extra-fee-enable').show();
				EFW_Toggle.toggle_fee_from('.efw-fee-from');
				if(!$('.efw-fee-from').length){
					EFW_Toggle.toggle_fee_text_from('.efw-text-from');
					EFW_Toggle.toggle_extra_fee_type($($this).closest('.efw-fee-wrapper').find('.efw-fee-type'));
					EFW_Toggle.toggle_extra_fee_type($($this).closest('table').find('.efw-fee-type'));
				}
			} else {
				$($this).closest('.efw-fee-wrapper').find('.efw-show-if-extra-fee-enable').hide();
				$($this).closest('table').find('.efw-show-if-extra-fee-enable').hide();
			}
		},
		extra_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_extra_fee_type($this);
		},
		toggle_extra_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-fee-wrapper').find('.efw-fixed-fee').show();
				$($this).closest('.efw-fee-wrapper').find('.efw-percent-fee').hide();
				$($this).closest('table').find('.efw-fixed-fee').show();
				$($this).closest('table').find('.efw-percent-fee').hide();
			} else if ('2' == $($this).val()) {
				$($this).closest('.efw-fee-wrapper').find('.efw-fixed-fee').hide();
				$($this).closest('.efw-fee-wrapper').find('.efw-percent-fee').show();
				$($this).closest('table').find('.efw-fixed-fee').hide();
				$($this).closest('table').find('.efw-percent-fee').show();
			}
		},
		extra_rule_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_extra_rule_fee_type($this);
		},
		toggle_extra_rule_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-fee-wrapper').find('.efw-rule-fixed-fee').show();
				$($this).closest('.efw-fee-wrapper').find('.efw-rule-percent-fee').hide();
			} else {
				$($this).closest('.efw-fee-wrapper').find('.efw-rule-fixed-fee').hide();
				$($this).closest('.efw-fee-wrapper').find('.efw-rule-percent-fee').show();
			}
		},
		fee_text_from: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_fee_text_from($this);
		},
		toggle_fee_text_from: function ($this) {
			if ('2' == $($this).val()) {
				$($this).closest('.efw-fee-wrapper').find('.efw-extra-fee-text').show();
				$($this).closest('.efw-fee-wrapper').find('.efw-toggle-extra-fee-desc').show();
				$($this).closest('table').find('.efw-extra-fee-text').show();
				$($this).closest('table').find('.efw-toggle-extra-fee-desc').show();
			} else {
				$($this).closest('.efw-fee-wrapper').find('.efw-extra-fee-text').hide();
				$($this).closest('.efw-fee-wrapper').find('.efw-toggle-extra-fee-desc').hide();
				$($this).closest('table').find('.efw-extra-fee-text').hide();
				$($this).closest('table').find('.efw-toggle-extra-fee-desc').hide();
			}
		},
		fee_from: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_fee_from($this);
		},
		toggle_fee_from: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-fee-wrapper').find('.efw-show-if-product-level').show();
				EFW_Toggle.toggle_fee_text_from($($this).closest('.efw-fee-wrapper').find('.efw-text-from'));
				EFW_Toggle.toggle_extra_fee_type($($this).closest('.efw-fee-wrapper').find('.efw-fee-type'));
				EFW_Toggle.toggle_extra_fee_type($($this).closest('table').find('.efw-fee-type'));
			} else {
				$($this).closest('.efw-fee-wrapper').find('.efw-show-if-product-level').hide();
			}
		},
		enable_gateway_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_enable_gateway_fee($this);
		},
		toggle_enable_gateway_fee: function ($this) {
			if (true == $($this).is(':checked')) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-show-if-enable').show();
				EFW_Toggle.toggle_gateway_fee_type($($this).closest('.efw-gateway-fee-settings').find('.efw-fee-type'));
				EFW_Toggle.toggle_user_filter_for_gateway($($this).closest('.efw-gateway-fee-settings').find('.efw-user-filter-type'));
				EFW_Toggle.toggle_product_filter_for_gateway($($this).closest('.efw-gateway-fee-settings').find('.efw-product-filter-type'));
				EFW_Toggle.toggle_gateway_fee_based_on($($this).closest('.efw-gateway-fee-settings').find('.efw-fee-based-on'));
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-show-if-enable').hide();
			}
		},
		enable_ordertotal_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_enable_ordertotal_fee($this);
		},
		toggle_enable_ordertotal_fee: function ($this) {
			if (true == $($this).is(':checked')) {
				$('.show-if-order-fee-enable').closest('tr').show();
				EFW_Toggle.toggle_order_total_fee_type('#efw_ordertotalfee_fee_type');
				EFW_Toggle.toggle_shipping_restriction_based_on('#efw_ordertotalfee_shipping_based_on');
				EFW_Toggle.toggle_user_filter_for_order('#efw_ordertotalfee_user_filter');
				EFW_Toggle.toggle_product_filter_for_order('#efw_ordertotalfee_product_filter');
				EFW_Toggle.toggle_order_fee_select_type_change('#efw_ordertotalfee_fee_configuration');
			} else {
				$('.show-if-order-fee-enable').closest('tr').hide();
				$('#efw_multiple_fee_table').hide();
			}
		},
		fee_based_on: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_fee_based_on($this);
		},
		toggle_fee_based_on: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_apply_for').closest('tr').show();
				EFW_Toggle.toggle_apply_for('#efw_productfee_apply_for');
				$('#efw_productfee_fee_type').closest('tr').show();
				EFW_Toggle.toggle_product_fee_type('#efw_productfee_fee_type');
				$('#efw_productfee_product_filters').closest('.form-table').hide();
				$('#efw_productfee_apply_for').closest('.form-table').next().hide();
				$('.efw-extra-fee-wrapper').show();
			} else {
				$('#efw_productfee_apply_for').closest('tr').hide();
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
				$('#efw_productfee_fee_type').closest('tr').hide();
				$('#efw_productfee_percent_value').closest('tr').hide();
				$('#efw_productfee_fixed_value').closest('tr').hide();
				$('#efw_productfee_product_filters').closest('.form-table').show();
				$('#efw_productfee_apply_for').closest('.form-table').next().show();
				EFW_Toggle.toggle_product_filter_type('#efw_productfee_product_filters');
				$('#efw_productfee_bulk_enable').closest('tr').show();
				EFW_Toggle.toggle_bulk_update_products('#efw_productfee_bulk_enable');
				$('.efw-extra-fee-wrapper').hide();
			}
		},
		bulk_update_products: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_bulk_update_products($this);
		},
		toggle_bulk_update_products: function ($this) {
			if ($($this).is(':checked')) {
				$('#efw_productfee_bulk_text_from').closest('tr').show();
				$('#efw_productfee_bulk_fee_from').closest('tr').show();
				$('#efw_productfee_bulk_fee_type').closest('tr').show();
				EFW_Toggle.toggle_bulk_update_fee_from('#efw_productfee_bulk_fee_from');
			} else {
				$('#efw_productfee_bulk_fee_from').closest('tr').hide();
				$('#efw_productfee_bulk_text_from').closest('tr').hide();
				$('#efw_productfee_bulk_fee_text').closest('tr').hide();
				$('#efw_productfee_bulk_fee_description').closest('tr').hide();
				$('#efw_productfee_bulk_fee_type').closest('tr').hide();
				$('#efw_productfee_bulk_fixed_value').closest('tr').hide();
				$('#efw_productfee_bulk_percent_value').closest('tr').hide();
			}
		},
		bulk_update_fee_from: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_bulk_update_fee_from($this);
		},
		toggle_bulk_update_fee_from: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_bulk_text_from').closest('tr').show();
				$('#efw_productfee_bulk_fee_type').closest('tr').show();
				EFW_Toggle.toggle_bulk_update_fee_text_from('#efw_productfee_bulk_text_from');
				EFW_Toggle.toggle_bulk_update_fee_type('#efw_productfee_bulk_fee_type');
			} else {
				$('#efw_productfee_bulk_text_from').closest('tr').hide();
				$('#efw_productfee_bulk_fee_text').closest('tr').hide();
				$('#efw_productfee_bulk_fee_description').closest('tr').hide();
				$('#efw_productfee_bulk_fee_type').closest('tr').hide();
				$('#efw_productfee_bulk_fixed_value').closest('tr').hide();
				$('#efw_productfee_bulk_percent_value').closest('tr').hide();
			}
		},
		bulk_update_fee_text_from: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_bulk_update_fee_text_from($this);
		},
		toggle_bulk_update_fee_text_from: function ($this) {
			if ('2' == $($this).val()) {
				$('#efw_productfee_bulk_fee_text').closest('tr').show();
				$('#efw_productfee_bulk_fee_description').closest('tr').show();
			} else {
				$('#efw_productfee_bulk_fee_text').closest('tr').hide();
				$('#efw_productfee_bulk_fee_description').closest('tr').hide();
			}
		},
		bulk_update_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_bulk_update_fee_type($this);
		},
		toggle_bulk_update_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_bulk_fixed_value').closest('tr').show();
				$('#efw_productfee_bulk_percent_value').closest('tr').hide();
			} else {
				$('#efw_productfee_bulk_fixed_value').closest('tr').hide();
				$('#efw_productfee_bulk_percent_value').closest('tr').show();
			}
		},
		product_filter_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_product_filter_type($this);
		},
		toggle_product_filter_type: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else if ('2' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').show();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else if ('3' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').show();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else if ('4' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').show();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else if ('5' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').show();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else if ('6' == $($this).val()) {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').show();
				$('#efw_productfee_update_exc_tag').closest('tr').hide();
			} else {
				$('#efw_productfee_update_inc_products').closest('tr').hide();
				$('#efw_productfee_update_exc_products').closest('tr').hide();
				$('#efw_productfee_update_inc_category').closest('tr').hide();
				$('#efw_productfee_update_exc_category').closest('tr').hide();
				$('#efw_productfee_update_inc_tag').closest('tr').hide();
				$('#efw_productfee_update_exc_tag').closest('tr').show();
			}
		},
		apply_for: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_apply_for($this);
		},
		toggle_apply_for: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else if ('2' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').show();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else if ('3' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').show();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else if ('4' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').show();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').show();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else if ('5' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').show();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').show();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else if ('6' == $($this).val()) {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').show();
				$('#efw_productfee_exclude_tag').closest('tr').hide();
			} else {
				$('#efw_productfee_include_products').closest('tr').hide();
				$('#efw_productfee_exclude_products').closest('tr').hide();
				$('#efw_productfee_include_category').closest('tr').hide();
				$('#efw_productfee_exclude_category').closest('tr').hide();
				$('#efw_productfee_include_additional_products').closest('tr').hide();
				$('#efw_productfee_exclude_additional_products').closest('tr').hide();
				$('#efw_productfee_include_tag').closest('tr').hide();
				$('#efw_productfee_exclude_tag').closest('tr').show();
			}
		},
		product_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_product_fee_type($this);
		},
		toggle_product_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_fixed_value').closest('tr').show();
				$('#efw_productfee_percent_value').closest('tr').hide();
			} else {
				$('#efw_productfee_fixed_value').closest('tr').hide();
				$('#efw_productfee_percent_value').closest('tr').show();
			}
		},
		user_filter_for_product: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_user_filter_for_product($this);
		},
		toggle_user_filter_for_product: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_productfee_include_users').closest('tr').hide();
				$('#efw_productfee_exclude_users').closest('tr').hide();
				$('#efw_productfee_include_userrole').closest('tr').hide();
				$('#efw_productfee_exclude_userrole').closest('tr').hide();
			} else if ('2' == $($this).val()) {
				$('#efw_productfee_include_users').closest('tr').show();
				$('#efw_productfee_exclude_users').closest('tr').hide();
				$('#efw_productfee_include_userrole').closest('tr').hide();
				$('#efw_productfee_exclude_userrole').closest('tr').hide();
			} else if ('3' == $($this).val()) {
				$('#efw_productfee_include_users').closest('tr').hide();
				$('#efw_productfee_exclude_users').closest('tr').show();
				$('#efw_productfee_include_userrole').closest('tr').hide();
				$('#efw_productfee_exclude_userrole').closest('tr').hide();
			} else if ('4' == $($this).val()) {
				$('#efw_productfee_include_users').closest('tr').hide();
				$('#efw_productfee_exclude_users').closest('tr').hide();
				$('#efw_productfee_include_userrole').closest('tr').show();
				$('#efw_productfee_exclude_userrole').closest('tr').hide();
			} else {
				$('#efw_productfee_include_users').closest('tr').hide();
				$('#efw_productfee_exclude_users').closest('tr').hide();
				$('#efw_productfee_include_userrole').closest('tr').hide();
				$('#efw_productfee_exclude_userrole').closest('tr').show();
			}
		},
		gateway_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_gateway_fee_type($this);
		},
		toggle_gateway_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-fixed-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-calc-based-on').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-fee-type').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percent-cart-subtotal').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-minimum-fee').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-maximum-fee').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-add-fixed-fee').hide();
			} else if ('2' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-fixed-fee').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-calc-based-on').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-fee-type').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percent-cart-subtotal').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-minimum-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-maximum-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-add-fixed-fee').hide();
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-fixed-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-calc-based-on').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percent-cart-subtotal').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-minimum-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-maximum-fee').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-add-fixed-fee').show();
				EFW_Toggle.toggle_percentage_type($($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-calculation-based-on'));
			}
		},
		percentage_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_percentage_type($this);
		},
		toggle_percentage_type: function ($this) {
			var fee_type = $($this).closest('.efw-gateway-fee-settings').find('.efw-fee-type').val();
			if('3' == fee_type){
				if ('1' == $($this).val()) {
					$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-fee-type').hide();
				} else {
					$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-fee-type').show();
				}
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-percentage-fee-type').hide();
			}
		},
		percentage_type_for_shipping: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_percentage_type_for_shipping($this);
		},
		toggle_percentage_type_for_shipping: function ($this) {
			var fee_type = $($this).closest('.efw-shipping-fee-contents').find('.efw-shipping-fee-type').val();
			if('3' == fee_type){
				if ('1' == $($this).val()) {
					$($this).closest('.efw-shipping-fee-contents').find('.efw-percentage-fee-type').closest('tr').hide();
				} else {
					$($this).closest('.efw-shipping-fee-contents').find('.efw-percentage-fee-type').closest('tr').show();
				}
			} else {
				$($this).closest('.efw-shipping-fee-contents').find('.efw-percentage-fee-type').closest('tr').hide();
			}
		},
		user_filter_for_gateway: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_user_filter_for_gateway($this);
		},
		toggle_user_filter_for_gateway: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user-role').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user-role').hide();
			} else if ('2' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user-role').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user-role').hide();
			} else if ('3' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user-role').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user-role').hide();
			} else if ('4' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user-role').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user-role').hide();
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-user-role').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-user-role').show();
			}
		},
		product_filter_for_gateway: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_product_filter_for_gateway($this);
		},
		toggle_product_filter_for_gateway: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-additional-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-additional-product').hide();
			} else if ('2' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-product').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-additional-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-additional-product').hide();
			} else if ('3' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-product').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-additional-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-additional-product').hide();
			} else if ('4' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-category').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-additional-product').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-additional-product').hide();
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-category').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-category').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-additional-product').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-exclude-additional-product').show();
			}
		},
		gateway_fee_based_on: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_gateway_fee_based_on($this);
		},
		toggle_gateway_fee_based_on: function ($this) {
			if ('1' == $($this).val()) {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-states').hide();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-country').show();
			} else {
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-states').show();
				$($this).closest('.efw-gateway-fee-settings').find('.efw-include-country').hide();
			}
		},
		order_total_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_order_total_fee_type($this);
		},
		toggle_order_total_fee_type: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_ordertotalfee_fixed_value').closest('tr').show();
				$('#efw_ordertotalfee_cart_subtotal_percentage').closest('tr').hide();
			} else {
				$('#efw_ordertotalfee_fixed_value').closest('tr').hide();
				$('#efw_ordertotalfee_cart_subtotal_percentage').closest('tr').show();
			}
		},
		shipping_restriction_based_on: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_shipping_restriction_based_on($this);
		},
		toggle_shipping_restriction_based_on: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_ordertotalfee_excluded_shipping').closest('tr').show();
				$('#efw_ordertotalfee_excluded_shipping_zone').closest('tr').hide();
			} else {
				$('#efw_ordertotalfee_excluded_shipping').closest('tr').hide();
				$('#efw_ordertotalfee_excluded_shipping_zone').closest('tr').show();
			}
		},
		user_filter_for_order: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_user_filter_for_order($this);
		},
		toggle_user_filter_for_order: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_ordertotalfee_include_users').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_users').closest('tr').hide();
				$('#efw_ordertotalfee_include_userrole').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_userrole').closest('tr').hide();
			} else if ('2' == $($this).val()) {
				$('#efw_ordertotalfee_include_users').closest('tr').show();
				$('#efw_ordertotalfee_exclude_users').closest('tr').hide();
				$('#efw_ordertotalfee_include_userrole').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_userrole').closest('tr').hide();
			} else if ('3' == $($this).val()) {
				$('#efw_ordertotalfee_include_users').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_users').closest('tr').show();
				$('#efw_ordertotalfee_include_userrole').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_userrole').closest('tr').hide();
			} else if ('4' == $($this).val()) {
				$('#efw_ordertotalfee_include_users').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_users').closest('tr').hide();
				$('#efw_ordertotalfee_include_userrole').closest('tr').show();
				$('#efw_ordertotalfee_exclude_userrole').closest('tr').hide();
			} else {
				$('#efw_ordertotalfee_include_users').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_users').closest('tr').hide();
				$('#efw_ordertotalfee_include_userrole').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_userrole').closest('tr').show();
			}
		},
		product_filter_for_order: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_product_filter_for_order($this);
		},
		toggle_product_filter_for_order: function ($this) {
			if ('1' == $($this).val()) {
				$('#efw_ordertotalfee_include_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_products').closest('tr').hide();
				$('#efw_ordertotalfee_include_categories').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_categories').closest('tr').hide();
				$('#efw_ordertotalfee_include_additional_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_additional_products').closest('tr').hide();
			} else if ('2' == $($this).val()) {
				$('#efw_ordertotalfee_include_products').closest('tr').show();
				$('#efw_ordertotalfee_exclude_products').closest('tr').hide();
				$('#efw_ordertotalfee_include_categories').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_categories').closest('tr').hide();
				$('#efw_ordertotalfee_include_additional_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_additional_products').closest('tr').hide();
			} else if ('3' == $($this).val()) {
				$('#efw_ordertotalfee_include_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_products').closest('tr').show();
				$('#efw_ordertotalfee_include_categories').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_categories').closest('tr').hide();
				$('#efw_ordertotalfee_include_additional_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_additional_products').closest('tr').hide();
			} else if ('4' == $($this).val()) {
				$('#efw_ordertotalfee_include_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_products').closest('tr').hide();
				$('#efw_ordertotalfee_include_categories').closest('tr').show();
				$('#efw_ordertotalfee_exclude_categories').closest('tr').hide();
				$('#efw_ordertotalfee_include_additional_products').closest('tr').show();
				$('#efw_ordertotalfee_exclude_additional_products').closest('tr').hide();
			} else {
				$('#efw_ordertotalfee_include_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_products').closest('tr').hide();
				$('#efw_ordertotalfee_include_categories').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_categories').closest('tr').show();
				$('#efw_ordertotalfee_include_additional_products').closest('tr').hide();
				$('#efw_ordertotalfee_exclude_additional_products').closest('tr').show();
			}
		},
		order_fee_select_type_change: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_order_fee_select_type_change($this);
		},

		toggle_order_fee_select_type_change: function ($this) {
			if ('1' == $($this).val()) {
				$('.efw-multiple-level-elements').closest('tr').hide();
				$('#efw_multiple_fee_table').hide();
				$('.efw-single-level-elements').closest('tr').show();
				EFW_Toggle.toggle_order_total_fee_type('#efw_ordertotalfee_fee_type');
			} else if ('2' == $($this).val()) {
				$('.efw-multiple-level-elements').closest('tr').show();
				$('#efw_multiple_fee_table').show();
				$('.efw-single-level-elements').closest('tr').hide();
			}
		},
		get_update_fee_data: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.block($this);
			var data = {
				action: 'efw_fee_data',
				duration_type: $('.efw-duration-type').val(),
				from_date: $('#efw_reports_custom_from_date').val(),
				to_date: $('#efw_reports_custom_to_date').val(),
				efw_security: efw_admin_param.update_fee_data_nonce
			};
			$.post(efw_admin_param.ajaxurl, data, function (response) {
				if (true === response.success) {
					$($('.efw-reports-wrapper')).html(response.data.html);
					EFW_Toggle.unblock($this);
				} else {
					window.alert(response.data.error);
					EFW_Toggle.unblock($this);
				}
			});
		},
		add_rule_for_additional_fee: function (e) {
			e.preventDefault();
			var $this = $(e.currentTarget),
				wrapper = $($this).closest('.efw-additional-fee-rule-content-wrapper').find('.efw-additional-fee-rule-content'),
				count = Math.round(new Date().getTime() + (Math.random() * 100));

			var rule = wrapper.attr('data-new_rule');
			wrapper.append(rule.replaceAll('{{key}}', count));
		},
		delete_additional_fee_rule: function (e) {
			e.preventDefault();
			var $this = $(e.currentTarget);
			$($this).closest('tr').remove();
		},
		add_rule_for_simple: function (e) {
			e.preventDefault();
			EFW_Toggle.block('.efw-rules-wrapper');
			var $this = $(e.currentTarget);
			var count = parseInt($('input#efw_fee_key:last').val());
			count = count + 1 || 1;
			var data = {
				action: 'efw_add_rule_for_simple',
				count: count,
				efw_security: efw_admin_param.rule_nonce
			};

			$.post(ajaxurl, data, function (response) {
				if (true === response.success) {
					$($this).closest('.efw-extra-fee-wrapper').find('.efw-rules-content').append(response.data.field);
					$('.efw-rule-fee-type').each(function () {
						EFW_Toggle.toggle_extra_rule_fee_type($(this));
					});
				} else {
					window.alert(response.data.error);
				}
				$(document.body).trigger('efw-enhanced-init');
				EFW_Toggle.unblock('.efw-rules-wrapper');
			});
		},
		add_rule_for_variable: function (e) {
			e.preventDefault();
			EFW_Toggle.block('.efw-rules-wrapper');
			var $this = $(e.currentTarget);
			var count = parseInt($('input#efw_fee_key:last').val());
			count = count + 1 || 1;
			var data = {
				action: 'efw_add_rule_for_variable',
				loop: $(this).attr('data-loop'),
				count: count,
				efw_security: efw_admin_param.rule_nonce
			};

			$.post(ajaxurl, data, function (response) {
				if (true === response.success) {
					$($this).closest('.efw-extra-fee-wrapper').find('.efw-rules-content').append(response.data.field);
					$('.efw-rule-fee-type').each(function () {
						EFW_Toggle.toggle_extra_rule_fee_type($(this));
					});
				} else {
					window.alert(response.data.error);
				}
				$(document.body).trigger('efw-enhanced-init');
				EFW_Toggle.unblock('.efw-rules-wrapper');
			});
		},
		delete_rule: function (e) {
			e.preventDefault();
			if (confirm(efw_admin_param.delete_rule)) {
				var $this = $(e.currentTarget);
				$($this).closest('.efw-rules-content-wrapper').remove();
				var data = {
					action: 'efw_delete_rule',
					rule_id: $(this).attr('data-ruleid'),
					efw_security: efw_admin_param.rule_nonce
				};

				$.post(ajaxurl, data, function (response) {
					if (true === response.success) {
						$($this).closest('.efw-rules-content-wrapper').remove();
					} else {
						window.alert(response.data.error);
					}
				});
			}
		},
		toggle_shipping_user_filter_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-users').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-users').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-userroles').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-userroles').closest('tr').hide();
			if ('2' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-users').closest('tr').show();
			} else if ('3' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-users').closest('tr').show();
			} else if ('4' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-userroles').closest('tr').show();
			} else if ('5' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-userroles').closest('tr').show();
			}
		},
		toggle_shipping_product_filter_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-products').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-products').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-additional-products').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-additional-products').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-categories').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-categories').closest('tr').hide();
			if ('2' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-products').closest('tr').show();
			} else if ('3' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-products').closest('tr').show();
			} else if ('4' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-categories').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-include-additional-products').closest('tr').show();
			} else if ('5' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-categories').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-exclude-additional-products').closest('tr').show();
			}
		},
		toggle_shipping_country_filter_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			if ('1' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-select-country-to-include').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-select-state-to-include').closest('tr').hide();
			} else {
				$this.closest('.efw-shipping-fee-contents').find('.efw-select-country-to-include').closest('tr').hide();
				$this.closest('.efw-shipping-fee-contents').find('.efw-select-state-to-include').closest('tr').show();
			}
		},
		toggle_shipping_fee_type: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-fixed-value').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-percentage-value').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-percentage-based-on').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-percentage-fee-type').closest('tr').hide();
				$this.closest('.efw-shipping-fee-contents').find('.efw-add-fixed-fee').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-minimum-fee-value').closest('tr').hide();
			$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-maximum-fee-value').closest('tr').hide();
			if ('1' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-fixed-value').closest('tr').show();
			} else if ('2' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-percentage-value').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-percentage-based-on').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-minimum-fee-value').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-maximum-fee-value').closest('tr').show();
			}  else if ('3' == $this.val()) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-fixed-value').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-percentage-value').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-percentage-based-on').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-percentage-fee-type').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-add-fixed-fee').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-minimum-fee-value').closest('tr').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-maximum-fee-value').closest('tr').show();
				EFW_Toggle.toggle_percentage_type_for_shipping($($this).closest('.efw-shipping-fee-contents').find('.efw-percentage-based-on'));
			}
		},
		toggle_enable_shipping_method_fee_checkbox: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			if ($this.is(':checked')) {
				$this.closest('.efw-shipping-fee-contents').find('.efw-hide-shipping-options').show();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-user-filter-type').change();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-product-filter-type').change();
				$this.closest('.efw-shipping-fee-contents').find('.efw-shipping-fee-type').change();
			} else {
				$this.closest('.efw-shipping-fee-contents').find('.efw-hide-shipping-options').hide();
			}
		},
		block: function (id) {
			$(id).block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.6
				}
			});
		},
		unblock: function (id) {
			$(id).unblock();
		},
		productfee_tax_setup: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_enable_productfee_tax_setup($this);
		},

		toggle_enable_productfee_tax_setup: function ($this) {
			if (true === $($this).is(':checked')) {
				$('.show-if-tax-setup-enable').closest('tr').show();
				$('.show-if-tax-or-quantity-restriction-enable').closest('tr').show();
				$('.show-if-tax-or-quantity-restriction-disable').closest('tr').hide();
				$('#efw_productfee_restrict_for_renewal').closest('tr').show();
			} else {
				$('.show-if-tax-setup-enable').closest('tr').hide();
				if (true !== $('#efw_productfee_qty_restriction_enabled').is(':checked')) {
					$('.show-if-tax-or-quantity-restriction-enable').closest('tr').hide();
					$('.show-if-tax-or-quantity-restriction-disable').closest('tr').show();
				}
				$('#efw_productfee_restrict_for_renewal').closest('tr').hide();
			}

		},
		qty_restriction_enabled: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_qty_restriction_enabled($this);
		},

		toggle_qty_restriction_enabled: function ($this) {
			if (true !== $($this).is(':checked') && true !== $('#efw_productfee_tax_setup').is(':checked')) {
				$('.show-if-tax-or-quantity-restriction-enable').closest('tr').hide();
				$('.show-if-tax-or-quantity-restriction-disable').closest('tr').show();
				$('#efw_productfee_restrict_for_renewal').closest('tr').hide();
			} else {
				$('.show-if-tax-or-quantity-restriction-enable').closest('tr').show();
				$('.show-if-tax-or-quantity-restriction-disable').closest('tr').hide();
				$('#efw_productfee_restrict_for_renewal').closest('tr').show();
			}
		},enable_combine_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_combined_fee($this);
		},toggle_combined_fee: function ($this) {
			if (true == $($this).is(':checked')) {
				$('.show-if-combine-fee-enable').closest('tr').show();
			} else {
				$('.show-if-combine-fee-enable').closest('tr').hide();
			}
		},enable_additional_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			EFW_Toggle.toggle_additional_fee($this);
		},toggle_additional_fee: function ($this) {
			if (true == $($this).is(':checked')) {
				$('.show-if-additional-fee-enable').closest('tr').show();
				$('.efw-additional-fee-rule-content-wrapper').show();
			} else {
				$('.show-if-additional-fee-enable').closest('tr').hide();
				$('.efw-additional-fee-rule-content-wrapper').hide();
			}
		}, export_plugin_settings: function (event, step) {

			if (event) {
				event.preventDefault();
				var $this = $(event.currentTarget);
				EFW_Toggle.block('.efw-export-csv');
			}
			
			var data = {
				action: 'efw_export_plugin_settings',
				step: step,
				efw_security: efw_admin_param.export_nonce
			};
			$.post(
				efw_admin_param.ajaxurl,
				data,
				function (response) {
					if ('done' === response.data.step) {
						window.location = response.data.url;
					}  else {
						EFW_Toggle.export_csv_file(false, parseInt(response.data.step, 10));
					}
					EFW_Toggle.unblock('.efw-export-csv');
				}
			);
		} , block: function (id) {
			if (!EFW_Toggle.is_blocked(id)) {
				$(id).addClass('processing').block(
					{
						message: null,
						overlayCSS: {
							background: '#fff',
							opacity: 0.7
						}
					}
				);
			}
		}, unblock: function (id) {
			$(id).removeClass('processing').unblock();
		}, is_blocked: function (id) {
			return $(id).is('.processing') || $(id).parents('.processing').length;
		}
	};
	EFW_Toggle.init();
});
