/**
 * Meta-box scripts - Scheduler rule.
 * 
 * @since 4.0.0
 * @global dey_scheduler_rule_params
 * @global ajaxurl
 */
jQuery(function ($) {
	'use strict';

	if (typeof dey_scheduler_rule_params === 'undefined') {
		return false;
	}

	/**
	 * Handles the Scheduler rule metabox.
	 * 
	 * @since 4.0.0
	 */
	var SchedulerRule = {
		init: function () {
			// Tabbed scheduler rule panels.
			$(document).on('dey-init-tabbed-scheduler-rule-panels', this.tabbed_scheduler_rule_panels).trigger('dey-init-tabbed-scheduler-rule-panels');
			$(document).on('change', '#dey_order_delivery_slot_mode', this.trigger_scheduler_rule_delivery_slot_mode);
			$(document).on('change', '#dey_order_delivery_time_mode', this.trigger_scheduler_rule_delivery_time_mode);
			$(document).on('change', '.dey-scheduler-rule-time-slots-mode', this.toggle_scheduler_rule_time_slots);
			$(document).on('change', '.dey-scheduler-rule-holidays-mode', this.toggle_scheduler_rule_holidays);
			$(document).on('change', '.dey-scheduler-rule-special-days-mode', this.toggle_scheduler_rule_special_days);
			$(document).on('change', '.dey-scheduler-rule-data-panels-wrapper #dey_order_pickup_time_mode', this.toggle_scheduler_rule_local_pickup_time_mode);
			$(document).on('change', '.dey-order-scheduler-type', this.trigger_scheduler_rule_panels);
			$(document).on('click', '.dey-create-scheduler-rule-time-slot', this.create_scheduler_rule_time_slot);
			$(document).on('click', '.dey-create-scheduler-rule-holiday', this.create_scheduler_rule_holiday);
			$(document).on('click', '.dey-create-scheduler-rule-special-day', this.create_scheduler_rule_special_day);
			$(document).on('click', '.dey-reset-scheduler-rule-time-slot-usage-count', this.reset_scheduler_rule_time_slot_usage_count);
			$(document).on('click', '.dey-reset-scheduler-rule-special-day-usage-count', this.reset_scheduler_rule_special_day_usage_count);
			$('form#post').on('submit', this.prevent_scheduler_rule_save);

			this.trigger_on_page_load();
		},

		/**
		 * Triggers on page load.
		 * 
		 * @since 4.0.0
		 */
		trigger_on_page_load: function () {
			SchedulerRule.handle_postboxes();
			SchedulerRule.handle_scheduler_rule_delivery_time_mode('#dey_order_delivery_time_mode');
			SchedulerRule.handle_scheduler_rule_local_pickup_time_mode('.dey-scheduler-rule-data-panels-wrapper #dey_order_pickup_time_mode');
			SchedulerRule.handle_scheduler_rule_time_slots('.dey-scheduler-rule-time-slots-mode');
			SchedulerRule.handle_scheduler_rule_holidays('.dey-scheduler-rule-holidays-mode');
			SchedulerRule.handle_scheduler_rule_special_days('.dey-scheduler-rule-special-days-mode');
			SchedulerRule.handle_order_delivery_slot_mode('#dey_order_delivery_slot_mode');
			SchedulerRule.handle_scheduler_rule_panels('.dey-order-scheduler-type');
		},

		/**
		 * Toggle delivery slot mode on scheduler rule.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		trigger_scheduler_rule_delivery_slot_mode: function (event) {
			event.preventDefault();
			SchedulerRule.handle_order_delivery_slot_mode($(event.currentTarget));
		},

		/**
		 * Toggle delivery time mode on scheduler rule.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		trigger_scheduler_rule_delivery_time_mode: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_delivery_time_mode($(event.currentTarget));
		},

		/**
		 * Toggle the scheduler rule time slots rules.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_scheduler_rule_time_slots: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_time_slots('.dey-scheduler-rule-time-slots-mode');
		},

		/**
		 * Toggle the scheduler rule holidays rules.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_scheduler_rule_holidays: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_holidays('.dey-scheduler-rule-holidays-mode');
		},

		/**
		 * Toggle the scheduler rule special days rules.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_scheduler_rule_special_days: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_special_days('.dey-scheduler-rule-special-days-mode');
		},

		/**
		 * Toggle the order local pickup time mode.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		toggle_scheduler_rule_local_pickup_time_mode: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_local_pickup_time_mode($(event.currentTarget));
		},

		/**
		 * Trigger the scheduler rule panels.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		trigger_scheduler_rule_panels: function (event) {
			event.preventDefault();
			SchedulerRule.handle_scheduler_rule_panels($(event.currentTarget));
		},

		/**
		 * Handle the postboxes.
		 * 
		 * @since 4.0.0
		 */
		handle_postboxes: function () {
			// Scheduler rule meta box.
			$('#dey-scheduler-rule-general-data .postbox-header').remove(); // Remove the general scheduler rule meta box header.
			$('#dey-scheduler-rule-data .hndle').html('');// Remove the meta box title.
			$('.dey-scheduler-rule-data-panel-header').appendTo('#dey-scheduler-rule-data .hndle');
			$(function () {
				// Prevent inputs in meta box headings opening/closing contents.
				$('#dey-scheduler-rule-data').find('.hndle').unbind('click.postboxes');

				$('#dey-scheduler-rule-data').on('click', '.hndle', function (event) {
					// If the user clicks on some form input inside the h3 the box should not be toggled.
					if ($(event.target).filter('input, option, label, select').length) {
						return;
					}
				});
			});
		},

		/**
		 * Tabbed scheduler rule panels.
		 * 
		 * @since 4.0.0
		 */
		tabbed_scheduler_rule_panels: function () {
			// trigger the clicked link.
			$('.dey-scheduler-rule-data-tab-link').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
					panel_wrapper = $($this).closest('.dey-scheduler-rule-data-panels-wrapper');

				$('.dey-scheduler-rule-data-tab', panel_wrapper).removeClass('active');
				$($this).parent().addClass('active');

				$('div.dey-scheduler-rule-options-wrapper', panel_wrapper).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.dey-scheduler-rule-data-panels-wrapper').each(function () {
				$(this).find('.dey-scheduler-rule-data-tab').eq(0).find('a').click();
			});
		},

		/**
		 * Create a new scheduler rule time slot.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		create_scheduler_rule_time_slot: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-order-time-slot-modal-wrapper');

			// Return if the time slot name is empty.
			if (!wrapper.find('.dey-time-slot-name').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.name_error_msg);
				return false;
			}

			// Return if the time slot time field is empty.
			if (!wrapper.find('input[name="dey_time_slot[from_time]"]').val() || !wrapper.find('input[name="dey_time_slot[to_time]"]').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.time_slot_time_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_scheduler_rule_time_slot',
				time_slot_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_scheduler_rule_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-order-time-slots-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					SchedulerRule.reset_new_time_slot_data();
					$('.close-modal').click();
				} else {
					alert(res.data.error);
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Create a new scheduler rule holiday.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		create_scheduler_rule_holiday: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-holiday-modal-wrapper');
			// Check is holiday name is empty.
			if (!wrapper.find('.dey-holiday-name').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.name_error_msg);
				return false;
			}

			// Check is holiday from date and to date is empty.
			if (!wrapper.find('input[name="dey_holidays[from_date]"]').val() || !wrapper.find('input[name="dey_holidays[to_date]"]').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.holiday_date_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_scheduler_rule_holiday',
				holiday_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_scheduler_rule_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-scheduler-rule-holidays-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					SchedulerRule.reset_new_holiday_data();
					$('.close-modal').click();
				} else {
					alert(res.data.error);
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Create a new scheduler rule special day.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		create_scheduler_rule_special_day: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-special-day-modal-wrapper');

			// Check is special day name is empty.
			if (!wrapper.find('.dey-special-day-name').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.name_error_msg);
				return false;
			}

			// Check is special day date is empty.
			if (!wrapper.find('input[name="dey_special_days[date]"]').val()) {
				SchedulerRule.error_notice(wrapper, dey_scheduler_rule_params.special_day_date_error_msg);
				return false;
			}

			// Clear the error message.
			wrapper.find('.dey-error').html('');
			wrapper.find('.dey-error').removeClass('dey-error-notice');
			dey_block(wrapper);
			var data = ({
				action: 'dey_create_scheduler_rule_special_day',
				special_day_data: $(wrapper).find('input, select').serialize(),
				dey_security: dey_scheduler_rule_params.metabox_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					$('.dey-scheduler-rule-special-days-inner-wrapper').append(res.data.html);
					$(document.body).trigger('dey-enhanced-init');
					SchedulerRule.reset_new_special_day_data();
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
		 * Handle order delivery slot mode.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_order_delivery_slot_mode: function ($this) {
			$('.dey-order-delivery-field').closest('p').hide();
			
			switch ($($this).val()) {
				case '2':
					$('.dey-order-expected-delivery-field').closest('p').show();
					$('.dey-scheduler-rule-order-delivery-fee-options').hide();
					$('.dey-scheduler-rule-order-delivery-time-options').hide();
					break;

				default:
					$('.dey-order-calender-field').closest('p').show();
					$('.dey-scheduler-rule-order-delivery-fee-options').show();
					$('.dey-scheduler-rule-order-delivery-time-options').show();
					SchedulerRule.handle_scheduler_rule_delivery_time_mode("#dey_order_delivery_time_mode");
					break;
			}
		},

		/**
		 * Handle the order delivery time mode.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_scheduler_rule_delivery_time_mode: function ($this) {
			$('.dey-order-delivery-time-field').closest('p').hide();
			if ('2' === $($this).val()) {
				$('.dey-order-calender-field').closest('p').show();
			}

			// Trigger the scheduler rule panels. 
			SchedulerRule.handle_scheduler_rule_panels('.dey-order-scheduler-type', '.dey_delivery_tab');
		},

		/**
		 * Handle the scheduler rule panels.
		 * 
		 * @since 4.0.0  
		 * @param {object} $this 
		 */
		handle_scheduler_rule_panels: function ($this, current_tab = false) {
			var tab = false,
				wrapper = $('.dey-scheduler-rule-data-panels-wrapper');
			wrapper.find('.dey_time_slots_tab, .dey_time_slots_general_tab').hide();
			wrapper.find('.dey-scheduler-rule-general-time-slot-options-wrapper').hide();

			switch ($($this).val()) {
				case '1': // order delivery.
					wrapper.find('.dey_delivery_tab').show();
					wrapper.find('.dey_local_pickup_tab').hide();
					tab = wrapper.find('.dey_delivery_tab');
					if ('3' === $('#dey_order_delivery_time_mode').val()) {
						wrapper.find('.dey_time_slots_tab, .dey_time_slots_general_tab').show();
						wrapper.find('.dey-scheduler-rule-delivery-time-slots-wrapper').show();
					}
					break;

				case '2': // order local pickup.
					wrapper.find('.dey_local_pickup_tab').show();
					wrapper.find('.dey_delivery_tab').hide();
					tab = wrapper.find('.dey_local_pickup_tab');
					if ('3' === $('#dey_order_pickup_time_mode').val()) {
						wrapper.find('.dey_time_slots_tab, .dey_time_slots_general_tab').show();
						wrapper.find('.dey-scheduler-rule-local-pickup-time-slots-wrapper').show();
					}
					break;

				default: // Both local pickup and delivery. 
					wrapper.find('.dey_delivery_tab').show();
					wrapper.find('.dey_local_pickup_tab').show();
					if ('3' === $('#dey_order_delivery_time_mode').val() ) {
						wrapper.find('.dey_time_slots_tab, .dey_time_slots_general_tab').show();
						wrapper.find('.dey-scheduler-rule-delivery-time-slots-wrapper').show();
					}
					
					if ('3' === $('#dey_order_pickup_time_mode').val()) {
						wrapper.find('.dey_time_slots_tab, .dey_time_slots_general_tab').show();						
						wrapper.find('.dey-scheduler-rule-local-pickup-time-slots-wrapper').show();
					}
					break;
			}

			if (current_tab) {
				$(current_tab).find('.dey-scheduler-rule-data-tab-link').click();
			} else if (tab) {
				$(tab).find('.dey-scheduler-rule-data-tab-link').click();
			} else {
				// Trigger the first link.
				$('div.dey-scheduler-rule-data-panels-wrapper').each(function () {
					$(this).find('.dey-scheduler-rule-data-tab').eq(0).find('a').click();
				});
			}
		},

		/**
		 * Handle the scheduler rule time slots.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_scheduler_rule_time_slots: function ($this) {
			$('.dey-scheduler-rule-time-slots-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-scheduler-rule-time-slots-wrapper').show();
			}
		},

		/**
		 * Handle the scheduler rule holidays.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_scheduler_rule_holidays: function ($this) {
			$('.dey-scheduler-rule-holidays-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-scheduler-rule-holidays-wrapper').show();
			}
		},

		/**
		 * Handle the scheduler rule special days.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_scheduler_rule_special_days: function ($this) {
			$('.dey-scheduler-rule-special-days-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-scheduler-rule-special-days-wrapper').show();
			}
		},

		/**
		 * Handle the order local pickup time mode.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_scheduler_rule_local_pickup_time_mode: function ($this) {
			var wrapper = $($this).closest('.dey-scheduler-rule-options-wrapper');
			wrapper.find('.dey-order-local-pickup-time-field').closest('p').hide();

			if ('2' === $($this).val()) { // Time selectors.
				wrapper.find('.dey-order-local-pickup-time-field').closest('p').show();
			}

			// Trigger the scheduler rule panels. 
			SchedulerRule.handle_scheduler_rule_panels('.dey-order-scheduler-type', '.dey_local_pickup_tab');
		},

		/**
		 * Reset the scheduler rule time slot usage count.
		 * 
		 * @since 4.0.0
		 * @param {object} event
		 */
		reset_scheduler_rule_time_slot_usage_count: function (event) {
			if (!confirm(dey_scheduler_rule_params.reset_time_slot_msg)) {
				return;
			}

			event.preventDefault();
			var $this = $(event.currentTarget);
			dey_block($this.closest('.dey-scheduler-rule-time-slot-wrapper'));
			var data = ({
				action: 'dey_reset_scheduler_rule_time_slot_usage_count',
				scheduler_rule_id: $('#post_ID').val(),
				time_slot_key: $($this).data('key'),
				dey_security: dey_scheduler_rule_params.metabox_nonce,
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
		 * Reset the scheduler rule special day usage count.
		 * 
		 * @since 4.0.0
		 * @param {object} event
		 */
		reset_scheduler_rule_special_day_usage_count: function (event) {
			if (!confirm(dey_scheduler_rule_params.reset_special_day_msg)) {
				return;
			}

			event.preventDefault();
			var $this = $(event.currentTarget);
			dey_block($this.closest('.dey-scheduler-rule-special-day-wrapper'));
			var data = ({
				action: 'dey_reset_scheduler_rule_special_day_usage_count',
				scheduler_rule_id: $('#post_ID').val(),
				special_day_key: $($this).data('key'),
				dey_security: dey_scheduler_rule_params.metabox_nonce,
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
		 * Prevent the scheduler rule save.
		 * 
		 * @since 4.2.0
		 * @param {event} event
		 */
		prevent_scheduler_rule_save: function (event) {
			if ( 'dey_scheduler_rule' !== $('#post_type').val() ) {
				return;
			}

			try {
				var scheduler_type = $('#dey_scheduler_type').val();

				// Validate order delivery fields.
				if ('2' !== scheduler_type) {
					switch ($('#dey_order_delivery_slot_mode').val()) {
						case '1': // Calender mode.
							// Throw error, if the number of days availability is empty. 
							if ('' === $('#dey_order_delivery_days_availability').val()) {
								throw new Error(dey_scheduler_rule_params.order_delivery_days_availability_empty_alert_msg);
							}

							switch ($('#dey_order_delivery_time_mode').val()) {
								case '2': // Time selectors.
									// Throw error, if the time selector fields are empty.
									if ('' === $('input[name="dey_order_delivery_available_time_from"]').val() || '' === $('input[name="dey_order_delivery_available_time_to"]').val()) {
										throw new Error(dey_scheduler_rule_params.order_delivery_time_selector_empty_alert_msg);
									}
									break;

								case '3': // Time slots.
									// Throw error, if the rule level time slots are empty.
									if ('2' === $('#dey_scheduler_rule_time_slots_mode').val() && 1 > $('.dey-scheduler-rule-time-slot-wrapper').length) {
										throw new Error(dey_scheduler_rule_params.time_slots_empty_alert_msg);
									}
									break;
							}							
							break;

						case '2': // Expected delivery mode.
							// Throw error, if the expected delivery From Date field is empty.
							if ('' === $('#dey_order_delivery_expected_date_from').val()) {
								throw new Error(dey_scheduler_rule_params.order_delivery_expected_from_date_empty_alert_msg);
							}

							// Throw error, if the expected delivery To Date field is empty.
							if ('' === $('#dey_order_delivery_expected_date_to').val()) {
								throw new Error(dey_scheduler_rule_params.order_delivery_expected_to_date_empty_alert_msg);
							}
							break;
					}
				}

				// Validate order local pickup fields. 
				if ('1' !== scheduler_type) {
					// Throw error, if the number of days availability is empty.
					if ('' === $('#dey_order_pickup_days_availability').val()) {
						throw new Error(dey_scheduler_rule_params.order_pickup_days_availability_empty_alert_msg);
					}

					switch ($('#dey_order_pickup_time_mode').val()) {
						case '2': // Time selectors.
							// Throw error, if the time selector fields are empty.
							if ('' === $('input[name="dey_order_pickup_available_time_from"]').val() || '' === $('input[name="dey_order_pickup_available_time_to"]').val()) {
								throw new Error(dey_scheduler_rule_params.order_pickup_time_selector_empty_alert_msg);
							}
							break;

						case '3': // Time slots.
							// Throw error, if the rule level time slots are empty.
							if ('2' === $('#dey_scheduler_rule_time_slots_mode').val() && 1 > $('.dey-scheduler-rule-time-slot-wrapper').length) {
								throw new Error(dey_scheduler_rule_params.time_slots_empty_alert_msg);
							}
							break;
					}
				}
			} catch (error) {
				alert(error.message);
				event.preventDefault();
				return false;
			}

			// Confirm the user before saving the inactive rule.
			if ('dey_inactive' === $('#post_status :selected').val() && !confirm(dey_scheduler_rule_params.inactive_rule_alert_msg)) {
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

	SchedulerRule.init();
});
