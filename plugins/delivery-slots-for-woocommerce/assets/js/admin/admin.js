/**
 * Admin Scripts.
 * 
 * @since 1.0.0
 * @global dey_admin_params
 * @global ajaxurl
 */
jQuery(function ($) {
	'use strict';

	var Admin = {
		init: function () {
			// Order Tip.
			$(document).on('change', '#dey_order_tip_checkout_enabled', this.trigger_order_tip_checkout);
			$(document).on('change', '#dey_order_tip_cart_enabled', this.trigger_order_tip_cart);
			$(document).on('change', '#dey_order_tip_display_type', this.trigger_order_tip_display_type);
			$(document).on('change', '#dey_order_tip_predefined_value_type', this.trigger_order_tip_predefined_value_type);
			$(document).on('change', '#dey_delivery_day_filter', this.trigger_delivery_day_filter);

			// validate the delete post.
			$(document).on('click', '.dey-print-data', this.print_data);

			// validate the Send email.
			$(document).on('click', '.dey-send-email', this.validate_send_email);

			// Prevent settings save in functionality.
			$(document).on('click', 'form .dey_save_btn', this.prevent_save_settings);
			$(document).on('click', '.post-type-dey_scheduler_rule .page-title-action', this.display_scheduler_rule_popup);

			// Scheduler rule.
			$(document).on('click', '.dey-create-scheduler-rule', this.create_scheduler_rule);
			// Validate the action.
			$(document).on('click', '.wp-list-table .dey-action', this.action_confirmation);
			$(document).on('click', '.post-type-dey_time_slots #doaction, .post-type-dey_holiday #doaction, .post-type-dey_special_days #doaction, .post-type-dey_pickup_locations #doaction, .post-type-dey_scheduler_rule #doaction', this.bulk_action_confirmation);
			// Trigger time slot week days.
			$(document).on('change', '.dey-time-slot-options-wrapper .dey_enable_week_days', this.trigger_time_slot_week_days);
			// Toggle the delivery slots content.
			$(document).on('click', '.dey-order-delivery-toggle', this.toggle_order_delivery_slots_content);
			// Delete the order delivery slots rule.
			$(document).on('click', '.dey-delete-order-delivery-slots-rule', this.delete_order_delivery_slots_rule);
			// Reset order usage count.
			$(document).on('click', '.dey-reset-order-time-slot', this.reset_order_time_slot_usage_count);
			$(document).on('click', '.dey-reset-order-special-day', this.reset_order_special_day_usage_count);

			this.trigger_on_page_load();
		},

		trigger_on_page_load: function () {
			// Order Delivery.
			Admin.delivery_day_filter("#dey_delivery_day_filter");

			//Order Tip.
			Admin.order_tip_checkout("#dey_order_tip_checkout_enabled");
			Admin.order_tip_cart("#dey_order_tip_cart_enabled");
			Admin.order_tip_display_type("#dey_order_tip_display_type");
			Admin.order_tip_predefined_value_type("#dey_order_tip_predefined_value_type");

			Admin.initialize_datetimepicker();
			Admin.time_slot_week_days('.dey_enable_week_days');
		},

		trigger_order_tip_checkout: function (event) {
			event.preventDefault();
			Admin.order_tip_checkout($(event.currentTarget));
		},

		trigger_order_tip_cart: function (event) {
			event.preventDefault();
			Admin.order_tip_cart($(event.currentTarget));
		},

		trigger_order_tip_display_type: function (event) {
			event.preventDefault();
			Admin.order_tip_display_type($(event.currentTarget));
		},

		trigger_order_tip_predefined_value_type: function (event) {
			event.preventDefault();
			Admin.order_tip_predefined_value_type($(event.currentTarget));
		},

		trigger_delivery_day_filter: function (event) {
			event.preventDefault();
			Admin.delivery_day_filter($(event.currentTarget));
		},

		trigger_time_slot_week_days: function (event) {
			event.preventDefault();
			Admin.time_slot_week_days($(event.currentTarget));
		},

		order_tip_checkout: function ($this) {
			if ($($this).is(':checked')) {
				$('.dey-order-tip-checkout-fields').closest('tr').show();
			} else {
				$('.dey-order-tip-checkout-fields').closest('tr').hide();
			}
		},

		order_tip_cart: function ($this) {
			if ($($this).is(':checked')) {
				$('.dey-order-tip-cart-fields').closest('tr').show();
			} else {
				$('.dey-order-tip-cart-fields').closest('tr').hide();
			}
		},

		order_tip_display_type: function ($this) {
			$('.dey-order-tip-type-fields').closest('tr').show();
			Admin.order_tip_predefined_value_type('#dey_order_tip_predefined_value_type');
			switch ($($this).val()) {
				case '3':
					$('.dey-order-tip-predefined-fields').closest('tr').hide();
					break;

				case '2':
					$('.dey-order-tip-custom-fields').closest('tr').hide();
					break;
			}
		},

		order_tip_predefined_value_type: function ($this) {
			switch ($($this).val()) {
				case '1':
					$('#dey_order_tip_percentage_type').closest('tr').hide();
					break;

				case '2':
					$('#dey_order_tip_percentage_type').closest('tr').show();
					break;
			}
		},

		delivery_day_filter: function ($this) {
			if ('5' == $($this).val()) {
				$('#dey_from_datetime_picker').show();
				$('#dey_to_datetime_picker').show();
			} else {
				$('#dey_from_datetime_picker').hide();
				$('#dey_to_datetime_picker').hide();
			}
		},

		print_data: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				type = $($this).data('type');

			Admin.block($('body'));
			var data = ({
				action: 'dey_delivery_printing_data',
				type: type,
				day_filter: $('#dey_delivery_day_filter').val(),
				from_date: $('#dey_from_datetime_picker_value').val(),
				to_date: $('#dey_to_datetime_picker_value').val(),
				dey_security: dey_admin_params.print_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					printJS({ printable: res.data.html, type: 'raw-html' });
				} else {
					alert(res.data.error);
				}

				Admin.unblock($('body'));
			});

			return true;
		},

		initialize_datetimepicker: function (event) {
			if ($('#dey_from_datetime_picker').length) {
				$('#dey_from_datetime_picker').datetimepicker({
					altFieldTimeOnly: false,
					altField: $('#dey_from_datetime_picker').next("#dey_from_datetime_picker_value"),
					altFormat: 'yy-mm-dd',
					altTimeFormat: 'HH:mm',
					changeMonth: true,
					changeYear: true
				});

				$('#dey_from_datetime_picker').on('change', function () {
					if ($(this).val() === '') {
						$(this).next("#dey_from_datetime_picker_value").val('');
					}
				});
			}

			if ($('#dey_to_datetime_picker').length) {
				$('#dey_to_datetime_picker').datetimepicker({
					altFieldTimeOnly: false,
					altField: $('#dey_to_datetime_picker').next("#dey_to_datetime_picker_value"),
					altFormat: 'yy-mm-dd',
					altTimeFormat: 'HH:mm',
					changeMonth: true,
					changeYear: true,
				});

				$('#dey_from_datetime_picker').on('change', function () {
					if ($(this).val() === '') {
						$(this).next("#dey_to_datetime_picker_value").val('');
					}
				});
			}
		},

		validate_send_email: function (event) {
			var message = confirm(dey_admin_params.send_email_confirm_msg);

			if (!message) {
				event.preventDefault();
				return;
			}
		},

		prevent_save_settings: function (event) {
			var error_message = false;

			if ($('#dey_order_tip_display_type').length && '3' !== $('#dey_order_tip_display_type').val() && '' === $('#dey_order_tip_predefined_values').val()) {
				error_message = $('#dey_order_tip_predefined_values').data('error');
			}

			if (error_message) {
				alert(error_message);
				event.preventDefault();
				return false;
			}
		},

		/**
		 * Display the scheduler rule popup.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		display_scheduler_rule_popup: function (event) {
			event.preventDefault();
			$('.dey-scheduler-rule-modal-wrapper').modal();
		},

		/**
		 * Create a new scheduler rule.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		create_scheduler_rule: function (event) {
			event.preventDefault();
			var wrapper = $('.dey-scheduler-rule-modal-wrapper');

			// Check the scheduler rule name is empty.
			if (!$(wrapper).find('#dey_scheduler_rule_title').val()) {
				$(wrapper).find('.dey-error').html(dey_admin_params.rule_name_empty_error_msg);
				$(wrapper).find('.dey-error').addClass('dey-error-notice');
				return false;
			}

			// Clear the error message.
			$(wrapper).find('.dey-error').html('');
			$(wrapper).find('.dey-error').removeClass('dey-error-notice');

			var data = ({
				action: 'dey_create_scheduler_rule',
				title: wrapper.find('#dey_scheduler_rule_title').val(),
				shipping_methods: wrapper.find('#dey_order_delivery_shipping_methods').val(),
				scheduler_type: wrapper.find('#dey_scheduler_type').val(),
				dey_security: dey_admin_params.scheduler_rule_nonce,
			});

			Admin.block($('body'));
			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					window.location.href = res.data.url;
				} else {
					alert(res.data.error);
				}

				Admin.unblock($('body'));
			});
		},

		/**
		 * Handle action confirmation.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		action_confirmation: function (event) {
			var message = '';

			switch ($(event.currentTarget).data('action')) {
				case 'duplicate':
					message = dey_admin_params.duplicate_confirm_msg;
					break;

				case 'delete':
					message = dey_admin_params.delete_confirm_msg;
					break;
			}

			if (message && !confirm(message)) {
				event.preventDefault( );
				return;
			}

			Admin.block($(event.currentTarget).closest('table'));
		},

		/**
		 * Handle bulk action confirmation.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		bulk_action_confirmation: function (event) {
			var $this = $(event.currentTarget),
				message = '';

			switch ($($this).closest('.bulkactions').find('select').val()) {
				case 'delete':
					message = dey_admin_params.delete_confirm_msg;
					break;
			}

			if (message && !confirm(message)) {
				event.preventDefault();
			} else {
				Admin.block($this.closest('.dey_table_wrap').find('table'));
			}
		},

		time_slot_week_days: function ($this) {
			if ($($this).is(":checked")) {
				$('.dey_week_days').closest('p').show();
				$('.dey_week_days').closest('tr').show();
			} else {
				$('.dey_week_days').closest('p').hide();
				$('.dey_week_days').closest('tr').hide();
			}
		},

		reset_order_time_slot_usage_count: function (event) {
			event.preventDefault();
			if (!confirm(dey_admin_params.delete_confirm_msg)) {
				return;
			}

			var $this = $(event.currentTarget);

			Admin.block($this);
			var data = ({
				action: 'dey_reset_order_time_slot_usage_count',
				time_slot_id: $($this).data('id'),
				dey_security: dey_admin_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				Admin.unblock($this);
			});
		},

		reset_order_special_day_usage_count: function (event) {
			event.preventDefault();
			if (!confirm(dey_admin_params.delete_confirm_msg)) {
				return;
			}

			var $this = $(event.currentTarget);
			Admin.block($this);
			var data = ({
				action: 'dey_reset_order_special_day_usage_count',
				special_day_id: $($this).data('id'),
				dey_security: dey_admin_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				Admin.unblock($this);
			});
		},

		/**
		 * Toggle order delivery slots rule content.
		 * 
		 * @since 4.0.0
		 * @param {event} event
		 */
		toggle_order_delivery_slots_content: function (event) {
			event.preventDefault();

			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-order-delivery-slots-wrapper');

			if (!wrapper.find('.dey-order-delivery-slots-content-wrapper').hasClass('dey-hide')) {
				$('.dey-order-delivery-slots-content-wrapper').addClass('dey-hide');
			} else {
				$('.dey-order-delivery-slots-content-wrapper').addClass('dey-hide');
				wrapper.find('.dey-order-delivery-slots-content-wrapper').removeClass('dey-hide');
			}
		},

		/**
		 * Delete the scheduler rule.
		 * 
		 * @since 4.0.0
		 * @param {event} event
		 */
		delete_order_delivery_slots_rule: function (event) {
			event.preventDefault();

			$($(event.currentTarget)).closest('.dey-order-delivery-slots-wrapper').remove();
		},

		block: function (id) {
			if (Admin.is_blocked(id)) {
				return;
			}

			$(id).addClass('processing').block({
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.7 }
			});
		},

		unblock: function (id) {
			$(id).removeClass('processing').unblock();
		},

		is_blocked: function (id) {
			return $(id).is('.processing') || $(id).parents('.processing').length;
		}
	};

	Admin.init();
});
