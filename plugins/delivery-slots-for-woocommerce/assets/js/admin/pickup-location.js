/**
 * Meta-box scripts - Pickup location.
 * 
 * @since 4.0.0
 * @global dey_pickup_location_params
 * @global ajaxurl
 */
jQuery(function ($) {
	'use strict';

	if (typeof dey_pickup_location_params === 'undefined') {
		return false;
	}

	/**
	 * Handles the pickup location metabox.
	 * 
	 * @since 4.0.0
	 */
	var PickupLocation = {
		init: function () {
			// Tabbed pickup locations panel.
			$(document).on('dey-init-pickup-locations-panels', this.toggle_pickup_location_panels).trigger('dey-init-pickup-locations-panels');
			// Tabbed order pickup scheduler panel.
			$(document).on('dey-init-tabbed-order-pickup-scheduler-panels', this.tabbed_order_pickup_scheduler_panels).trigger('dey-init-tabbed-order-pickup-scheduler-panels');
			// Toggle order scheduler panel.
			$(document).on('change', '#dey_pickup_mode', this.toggle_order_pickup_scheduler_panel);
			$(document).on('change', '.dey-order-pickup-scheduler-data-panels-wrapper #dey_order_pickup_time_mode', this.toggle_order_pickup_location_local_pickup_time_mode);
			$(document).on('change', '.dey-order-pickup-location-time-slots-mode', this.toggle_order_pickup_location_time_slots);
			$(document).on('change', '.dey-order-pickup-location-holidays-mode', this.toggle_order_pickup_location_holidays);
			$(document).on('change', '.dey-order-pickup-location-special-days-mode', this.toggle_order_pickup_location_special_days);
			$(document).on('click', '.dey-create-order-pickup-location-time-slot', this.create_order_pickup_location_time_slot);
			$(document).on('click', '.dey-create-order-pickup-location-holiday', this.create_order_pickup_location_holiday);
			$(document).on('click', '.dey-create-order-pickup-location-special-day', this.create_order_pickup_location_special_day);
			$(document).on('click', '.dey-reset-pickup-location-time-slot-usage-count', this.reset_pickup_location_time_slot_usage_count);
			$(document).on('click', '.dey-reset-pickup-location-special-day-usage-count', this.reset_pickup_location_special_day_usage_count);
			$('form#post').on('submit', this.prevent_pickup_location_save);

			this.trigger_on_page_load();
		},

		/**
		 * Triggers on page load.
		 * 
		 * @since 4.0.0
		 */
		trigger_on_page_load: function () {
			PickupLocation.handle_postboxes();
			PickupLocation.handle_order_pickup_location_panels();
			PickupLocation.handle_order_pickup_location_local_pickup_time_mode('#dey_order_pickup_time_mode');
			PickupLocation.handle_order_pickup_location_time_slots('.dey-order-pickup-location-time-slots-mode');
			PickupLocation.handle_order_pickup_location_holidays('.dey-order-pickup-location-holidays-mode');
			PickupLocation.handle_order_pickup_location_special_days('.dey-order-pickup-location-special-days-mode');
			PickupLocation.handle_order_pickup_scheduler_panel('#dey_pickup_mode');
		},

		/**
		 * Toggle the order pickup scheduler panel.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_order_pickup_scheduler_panel: function (event) {
			event.preventDefault();
			PickupLocation.handle_order_pickup_scheduler_panel($(event.currentTarget));
		},

		/**
		 * Toggle the local pickup time mode on order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_order_pickup_location_local_pickup_time_mode: function (event) {
			event.preventDefault();
			PickupLocation.handle_order_pickup_location_local_pickup_time_mode('#dey_order_pickup_time_mode');
		},

		/**
		 * Toggle the time slots in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_order_pickup_location_time_slots: function (event) {
			event.preventDefault();
			PickupLocation.handle_order_pickup_location_time_slots('.dey-order-pickup-location-time-slots-mode');
		},

		/**
		 * Toggle the holidays in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_order_pickup_location_holidays: function (event) {
			event.preventDefault();
			PickupLocation.handle_order_pickup_location_holidays('.dey-order-pickup-location-holidays-mode');
		},

		/**
		 * Toggle the special days in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_order_pickup_location_special_days: function (event) {
			event.preventDefault();
			PickupLocation.handle_order_pickup_location_special_days('.dey-order-pickup-location-special-days-mode');
		},

		handle_postboxes: function () {
			// Pickup location meta box.
			$('.dey-order-pickup-scheduler-panel-header').appendTo('#dey-order-pickup-scheduler-data .hndle');
			$(function () {
				// Prevent inputs in meta box headings opening/closing contents.
				$('#dey-order-pickup-scheduler-data').find('.hndle').unbind('click.postboxes');

				$('#dey-order-pickup-scheduler-data').on('click', '.hndle', function (event) {
					// If the user clicks on some form input inside the h3 the box should not be toggled.
					if ($(event.target).filter('input, option, label, select').length) {
						return;
					}

					$('#dey-order-pickup-scheduler-data').toggleClass('closed');
				});
			});
		},

		toggle_pickup_location_panels: function () {
			// trigger the clicked link.
			$('.dey-pickup-location-data-tab-link').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
					panel_wrapper = $($this).closest('.dey-pickup-location-data-panels-wrapper');

				$('.dey-pickup-location-data-tab', panel_wrapper).removeClass('active');
				$($this).parent().addClass('active');

				$('div.dey-pickup-location-options-wrapper', panel_wrapper).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.dey-pickup-location-data-panels-wrapper').each(function () {
				$(this).find('.dey-pickup-location-data-tab').eq(0).find('a').click();
			});
		},

		/**
		 * Tabbed order pickup scheduler panels.
		 * 
		 * @since 4.0.0
		 */
		tabbed_order_pickup_scheduler_panels: function () {
			// trigger the clicked link.
			$('.dey-order-pickup-scheduler-data-tab-link').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
					panel_wrapper = $($this).closest('.dey-order-pickup-scheduler-data-panels-wrapper');

				$('.dey-order-pickup-scheduler-data-tab', panel_wrapper).removeClass('active');
				$($this).parent().addClass('active');

				$('div.dey-order-pickup-scheduler-options-wrapper', panel_wrapper).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.dey-order-pickup-scheduler-data-panels-wrapper').each(function () {
				$(this).find('.dey-order-pickup-scheduler-data-tab').eq(0).find('a').click();
			});
		},

		/**
		 * Create a new order pickup location time slot.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		create_order_pickup_location_time_slot: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-order-time-slot-modal-wrapper');

			// Check is time slot name is empty.
			if (!wrapper.find('.dey-time-slot-name').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.time_slot_name_error_msg);
				return false;
			}

			// Check is time slots is empty.
			if (!wrapper.find('input[name="dey_time_slot[from_time]"]').val() || !wrapper.find('input[name="dey_time_slot[to_time]"]').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.time_slot_time_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_order_pickup_location_time_slot',
				time_slot_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_pickup_location_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-order-time-slots-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					PickupLocation.reset_new_time_slot_data();
					$('.close-modal').click();
				} else {
					alert(res.data.error);
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Create a new order pickup location holiday.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		create_order_pickup_location_holiday: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-holiday-modal-wrapper');

			// Check is holiday name is empty.
			if (!$(wrapper).find('.dey-holiday-name').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.name_error_msg);
				return false;
			}

			// Check is holiday from date and to date is empty.
			if (!$(wrapper).find('input[name="dey_holidays[from_date]"]').val() || !$(wrapper).find('input[name="dey_holidays[to_date]"]').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.holiday_date_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_order_pickup_location_holiday',
				holiday_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_pickup_location_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-order-pickup-scheduler-holidays-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					PickupLocation.reset_new_holiday_data();
					$('.close-modal').click();
				} else {
					alert(res.data.error);
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Create a new order pickup location special day.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		create_order_pickup_location_special_day: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-special-day-modal-wrapper');

			// Check is special day name is empty.
			if (!wrapper.find('.dey-special-day-name').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.name_error_msg);
				return false;
			}

			// Check is special day date is empty.
			if (!wrapper.find('input[name="dey_special_days[date]"]').val()) {
				PickupLocation.error_notice(wrapper, dey_pickup_location_params.special_day_date_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_order_pickup_location_special_day',
				special_day_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_pickup_location_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-scheduler-rule-special-days-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					PickupLocation.reset_new_special_day_data();
					$('.close-modal').click();
				} else {
					alert(res.data.error);
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Clear data before displaying the time slot popup.
		 * 
		 * @since 4.0.0
		 */
		reset_new_time_slot_data: function () {
			$('.dey-order-time-slot-modal-wrapper').find('input[type="text"]').val('');
		},

		/**
		 * Clear data before displaying the holiday popup.
		 * 
		 * @since 4.0.0
		 */
		reset_new_holiday_data: function () {
			$('.dey-holiday-modal-wrapper').find('input[type="text"]').val('');
		},

		/**
		 * Clear data before displaying the special day popup.
		 * 
		 * @since 4.0.0
		 */
		reset_new_special_day_data: function () {
			$('.dey-special-day-modal-wrapper').find('input[type="text"]').val('');
		},

		/**
		 * Handle the order pickup location panels.
		 * 
		 * @since 4.0.0  
		 */
		handle_order_pickup_location_panels: function () {
			$('.dey-order-pickup-scheduler-data-panels-wrapper').find('.dey_time_slots_tab').hide();
			if ('3' === $('#dey_order_pickup_time_mode').val()) { // Time slots.
				$('.dey-order-pickup-scheduler-data-panels-wrapper').find('.dey_time_slots_tab').show();
			}
		},

		/**
		 * Handle the local pickup time mode on order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_pickup_location_local_pickup_time_mode: function ($this) {
			var wrapper = $($this).closest('.dey-order-pickup-scheduler-options-wrapper');
			wrapper.find('.dey-order-local-pickup-time-field').closest('p').hide();

			if ('2' === $($this).val()) { // Time selectors.
				wrapper.find('.dey-order-local-pickup-time-field').closest('p').show();
			}

			// Trigger order pickup location panels.
			PickupLocation.handle_order_pickup_location_panels();

		},

		/**
		 * Handle the time slots in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_pickup_location_time_slots: function ($this) {
			$('.dey-order-pickup-scheduler-time-slots-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-order-pickup-scheduler-time-slots-wrapper').show();
			}
		},

		/**
		 * Handle the holidays in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_pickup_location_holidays: function ($this) {
			$('.dey-order-pickup-scheduler-holidays-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-order-pickup-scheduler-holidays-wrapper').show();
			}
		},

		/**
		 * Handle the special days in order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_pickup_location_special_days: function ($this) {
			$('.dey-order-pickup-scheduler-special-days-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-order-pickup-scheduler-special-days-wrapper').show();
			}
		},

		/**
		 * Handle the order pickup scheduler panel
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_pickup_scheduler_panel: function ($this) {
			$('.dey-order-pickup-scheduler-data-panels-wrapper').addClass('dey-blur').hide();
			if ('2' === $($this).val()) {
				$('.dey-order-pickup-scheduler-data-panels-wrapper').removeClass('dey-blur').show();
			}
		},

		/**
		 * Reset the pickup location time slot usage count.
		 * 
		 * @since 4.0.0
		 * @param {object} event
		 */
		reset_pickup_location_time_slot_usage_count: function (event) {
			if (!confirm(dey_pickup_location_params.reset_time_slot_msg)) {
				return;
			}

			event.preventDefault();
			var $this = $(event.currentTarget);
			dey_block($this.closest('.dey-scheduler-rule-time-slot-wrapper'));
			var data = ({
				action: 'dey_reset_pickup_location_time_slot_usage_count',
				pickup_location_id: $('#post_ID').val(),
				time_slot_key: $($this).data('key'),
				dey_security: dey_pickup_location_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				dey_unblock($this.closest('.dey-scheduler-rule-time-slot-wrapper'));
			});
		},

		/**
		 * Reset the pickup location special day usage count.
		 * 
		 * @since 4.0.0
		 * @param {object} event
		 */
		reset_pickup_location_special_day_usage_count: function (event) {
			if (!confirm(dey_pickup_location_params.reset_special_day_msg)) {
				return;
			}

			event.preventDefault();
			var $this = $(event.currentTarget);
			dey_block($this.closest('.dey-scheduler-rule-special-day-wrapper'));
			var data = ({
				action: 'dey_reset_pickup_location_special_day_usage_count',
				pickup_location_id: $('#post_ID').val(),
				special_day_key: $($this).data('key'),
				dey_security: dey_pickup_location_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				dey_unblock($this.closest('.dey-scheduler-rule-special-day-wrapper'));
			});
		},

		/**
		 * Prevent the pickup location save.
		 * 
		 * @since 4.2.0
		 * @param {event} event
		 */
		prevent_pickup_location_save: function (event) {
			if ( 'dey_pickup_locations' !== $('#post_type').val() || '1' === $('#dey_pickup_mode').val()) {
				return;
			}

			try {
				// Throw error, if the number of days availability is empty.
				if ('' === $('#dey_order_pickup_days_availability').val()) {
					throw new Error(dey_pickup_location_params.order_pickup_days_availability_empty_alert_msg);
				}

				switch ($('#dey_order_pickup_time_mode').val()) {
					case '2': // Time selectors.
						// Throw error, if the time selector fields are empty.
						if ('' === $('input[name="dey_order_pickup_available_time_from"]').val() || '' === $('input[name="dey_order_pickup_available_time_to"]').val()) {
							throw new Error(dey_pickup_location_params.order_pickup_time_selector_empty_alert_msg);
						}
						break;

					case '3': // Time slots.
						// Throw error, if the rule level time slots are empty.
						if ('2' === $('#dey_order_pickup_time_slots_mode').val() && 1 > $('.dey-scheduler-rule-time-slot-wrapper').length) {
							throw new Error(dey_pickup_location_params.time_slots_empty_alert_msg);
						}
						break;
				}
			} catch (error) {
				alert(error.message);
				event.preventDefault();
				return false;
			}

			// Confirm the user before saving the inactive rule.
			if ('dey_inactive' === $('#post_status :selected').val() && !confirm(dey_pickup_location_params.inactive_rule_alert_msg)) {
				event.preventDefault();
				return false;
			}
		},

		/**
		 * Displays the error message.
		 * 
		 * @since 4.2.0
		 * @param {object} wrapper
		 * @param {string} message
		 */
		error_notice: function (wrapper, message) {
			var error = wrapper.find('.dey-error');
			error.html(message);
			if (!error.hasClass('dey-error-notice')) {
				error.addClass('dey-error-notice');
			}
		},
	};

	/**
	 * Is blocked current element?.
	 * 
	 * @since 4.0.0
	 * @param {object} id
	 * @returns {boolean}
	 */
	var dey_is_blocked = function (id) {
		return $(id).is('.processing') || $(id).parents('.processing').length;
	}

	/**
	 * Block the element.
	 * 
	 * @since 4.0.0
	 * @param {object} id
	 */
	var dey_block = function (id) {
		if (!dey_is_blocked(id)) {
			$(id).addClass('processing').block({
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.7 }
			});
		}
	}

	/**
	 * Unblock the element.
	 * 
	 * @since 4.0.0
	 * @param {object} id
	 */
	var dey_unblock = function (id) {
		$(id).removeClass('processing').unblock();
	}

	PickupLocation.init();
});
