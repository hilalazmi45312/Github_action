jQuery(function ($) {

	var EFW_Bulk_Update = {
		init: function () {

			$(document).on('click', '#efw_productfee_bulk_update', this.update_fee);
			//Display Upgrade percentage
			this.display_upgrade_percentage();
		},
		display_upgrade_percentage: function () {

			if (!$('div.efw_progress_bar_wrapper').length) {
				return;
			}

			var data = {
				action: 'efw_progress_bar_action',
				action_scheduler_class_id : $('.efw-action-scheduler-action-id').val(),
				efw_security: efw_bulk_update_param.bulk_update_nonce
			};
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data,
				dataType: 'json',
			}).done(function (res) {
				if (true === res.success) {
					if (res.data.completed === 'no') {
						$('#efw_progress_bar_current_status').html(res.data.percentage);
						$('.efw_progress_bar_inner').css("width", res.data.percentage + "%");
						EFW_Bulk_Update.display_upgrade_percentage();
					} else {
						$('#efw_progress_bar_label').css("display", "none");
						$('.efw_progress_bar_inner').css("width", "100%");
						$('#efw_progress_bar_status').html(res.data.msg);
						window.location.href = res.data.redirect_url;
					}
				}
			});
		},
		update_fee: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);
			var enable_fee = $('#efw_productfee_bulk_enable').is(':checked') ? 'yes' : 'no';
			var fee_from = $('#efw_productfee_bulk_fee_from').val();
			var text_from = $('#efw_productfee_bulk_text_from').val();
			var fee_text = $('#efw_productfee_bulk_fee_text ').val();
			var fee_description = $('#efw_productfee_bulk_fee_description').val();
			var fee_type = $('#efw_productfee_bulk_fee_type').val();
			var fixed_value = $('#efw_productfee_bulk_fixed_value').val();
			var percent_value = $('#efw_productfee_bulk_percent_value').val();
			if ('yes' == enable_fee) {
				if ('2' == text_from) {
					if ('' == fee_text) {
						alert(efw_bulk_update_param.fee_text_error);
						return false;
					}
				}

				if ('1' == fee_type) {
					if ('' == fixed_value) {
						alert(efw_bulk_update_param.fixed_fee_error);
						return false;
					}
				} else {
					if ('' == percent_value) {
						alert(efw_bulk_update_param.percentage_fee_error);
						return false;
					}
				}
			}

			if ('2' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_inc_products').val()) {
					alert(efw_bulk_update_param.products_empty_error);
					return false;
				}
			}

			if ('3' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_exc_products').val()) {
					alert(efw_bulk_update_param.products_empty_error);
					return false;
				}
			}

			if ('4' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_inc_category').val()) {
					alert(efw_bulk_update_param.categories_empty_error);
					return false;
				}
			}

			if ('5' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_exc_category').val()) {
					alert(efw_bulk_update_param.categories_empty_error);
					return false;
				}
			}

			if ('6' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_inc_tag').val()) {
					alert(efw_bulk_update_param.tag_empty_error);
					return false;
				}
			}

			if ('7' == $('#efw_productfee_product_filters').val()) {
				if ('' == $('#efw_productfee_update_exc_tag').val()) {
					alert(efw_bulk_update_param.tag_empty_error);
					return false;
				}
			}

			EFW_Bulk_Update.block($this.closest('table'));
			var data = {
				action: 'efw_bulk_update_product_fee',
				product_filter: $('#efw_productfee_product_filters').val(),
				inc_products: $('#efw_productfee_update_inc_products').val(),
				exc_products: $('#efw_productfee_update_exc_products').val(),
				inc_category: $('#efw_productfee_update_inc_category').val(),
				exc_category: $('#efw_productfee_update_exc_category').val(),
				inc_tag: $('#efw_productfee_update_inc_tag').val(),
				exc_tag: $('#efw_productfee_update_exc_tag').val(),
				fee_mode: $('.efw_productfee_fee_setup:checked').val(),
				enable_fee: enable_fee,
				fee_from: fee_from,
				text_from: text_from,
				fee_text: fee_text,
				fee_description: fee_description,
				fee_type: fee_type,
				fixed_value: fixed_value,
				percent_value: percent_value,
				efw_security: efw_bulk_update_param.bulk_update_nonce
			};
			$.post(efw_bulk_update_param.ajaxurl, data, function (response) {
				if (true === response.success) {
					EFW_Bulk_Update.unblock($this.closest('table'));
					window.location.href = response.data.redirect_url;
				} else {
					EFW_Bulk_Update.unblock($this.closest('table'));
					window.alert(response.data.error);
				}
			});
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
	};
	EFW_Bulk_Update.init();
});
