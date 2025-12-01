/**
 * Meta-box scripts - Product.
 * 
 * @since 1.0.0
 * @global dey_product_params
 * @global ajaxurl
 */
jQuery(function ($) {
	'use strict';

	var ProductMetabox = {
		init: function () {
			// Trigger time slot week days.
			$(document).on('change', '.dey-delivery-slots-content-wrapper .dey_enable_week_days', this.trigger_time_slot_week_days);
			//Tabbed product delivery slots panel.
			$(document).on('dey-init-tabbed-delivery-slots-panels', this.tabbed_delivery_slots_panels).trigger('dey-init-tabbed-delivery-slots-panels');

			// Delivery slot mode.
			$(document).on('change', '#dey_delivery_slot_type', this.trigger_product_delivery_slot_type);
			$(document).on('change', '#dey_delivery_slot_mode', this.trigger_product_delivery_slot_mode);
			$(document).on('change', '#dey_delivery_time_mode', this.trigger_product_delivery_time_mode);

			// Add the product time slot.
			$(document).on('click', '.dey-add-delivery-time-slot', this.add_product_delivery_time_slot);
			// Delete the product time slot.
			$(document).on('click', '.dey-delete-delivery-time-slot', this.delete_product_delivery_time_slot);

			// Add the product special day.
			$(document).on('click', '.dey-add-delivery-special-day', this.add_product_delivery_special_day);
			// Delete the product special day.
			$(document).on('click', '.dey-delete-delivery-special-day', this.delete_product_delivery_special_day);

			// Add the product holiday.
			$(document).on('click', '.dey-add-delivery-holiday', this.add_product_delivery_holiday);
			// Delete the product holiday.
			$(document).on('click', '.dey-delete-delivery-holiday', this.delete_product_delivery_holiday);

			// Toggle the delivery slots content.
			$(document).on('click', '.dey-delivery-toggle', this.toggle_product_delivery_slots_content);
			$(document).on('click', '.dey-reset-product-time-slot', this.reset_product_time_slot_usage_count);
			$(document).on('click', '.dey-reset-product-special-day', this.reset_product_special_day_usage_count);

			// Order scheduler type. 
			$(document).on('change', '.dey-product-scheduler-type', this.toggle_product_scheduler_type);
			$(document).on('change', '#dey_pickup_time_mode', this.toggle_product_pickup_time_mode);

			// Add the product pickup location.
			$(document).on('click', '.dey-add-pickup-location', this.add_product_pickup_location);
			// Delete the product pickup location.
			$(document).on('click', '.dey-delete-pickup-location', this.delete_product_pickup_location);
			$(document).on('change', '#dey_pickup_location_selection_type', this.toggle_product_pickup_location_selection_type);

			this.trigger_on_page_load();
		},

		trigger_on_page_load: function () {
			ProductMetabox.time_slot_week_days('.dey_enable_week_days');
			ProductMetabox.handle_postboxes();
			ProductMetabox.handle_product_scheduler_type('.dey-product-scheduler-type');
			ProductMetabox.handle_product_pickup_location_selection_type('#dey_pickup_location_selection_type');
			ProductMetabox.product_delivery_slot_mode('#dey_delivery_slot_mode');
			ProductMetabox.product_delivery_slot_type('#dey_delivery_slot_type');
		},

		trigger_product_delivery_slot_type: function (event) {
			event.preventDefault();
			ProductMetabox.product_delivery_slot_type($(event.currentTarget));
		},

		trigger_product_delivery_slot_mode: function (event) {
			event.preventDefault();
			ProductMetabox.product_delivery_slot_mode($(event.currentTarget));
		},

		trigger_product_delivery_time_mode: function (event) {
			event.preventDefault();
			ProductMetabox.product_delivery_time_mode($(event.currentTarget));
		},

		trigger_time_slot_week_days: function (event) {
			event.preventDefault();
			ProductMetabox.time_slot_week_days($(event.currentTarget));
		},

		toggle_product_scheduler_type: function (event) {
			event.preventDefault();
			ProductMetabox.handle_product_scheduler_type($(event.currentTarget));
		},

		/**
		 * Toggle the product pickup time mode.
		 * 
		 * @since 3.5.0
		 * @param {event} event 
		 */
		toggle_product_pickup_time_mode: function (event) {
			event.preventDefault();
			ProductMetabox.handle_product_pickup_time_mode($(event.currentTarget));
		},

		/**
		 * Toggle the product pickup location selection type.
		 * 
		 * @since 3.5.0
		 * @param {event} event 
		 */
		toggle_product_pickup_location_selection_type: function (event) {
			event.preventDefault();
			ProductMetabox.handle_product_pickup_location_selection_type($(event.currentTarget));
		},

		tabbed_delivery_slots_panels: function () {
			// trigger the clicked link.
			$('.dey-delivery-slots-data-tab-link').on('click', function (event) {
				event.preventDefault();
				var $this = $(event.currentTarget),
					panel_wrapper = $($this).closest('.dey-delivery-slots-data-panels-wrapper');

				$('.dey-delivery-slots-data-tab', panel_wrapper).removeClass('active');
				$($this).parent().addClass('active');

				$('div.dey-delivery-slots-options-wrapper', panel_wrapper).hide();
				$($($this).attr('href')).show();
			});

			// Trigger the first link.
			$('div.dey-delivery-slots-data-panels-wrapper').each(function () {
				$(this).find('.dey-delivery-slots-data-tab').eq(0).find('a').click();
			});
		},

		handle_product_scheduler_type: function ($this) {
			var tab = false;
			var wrapper = $('.dey-delivery-slots-data-panels-wrapper');
			switch ($($this).val()) {
				case '1': // Product delivery
					$(wrapper).find('.dey_delivery_tab').show();
					$(wrapper).find('.dey_pickup_tab').hide();
					$(wrapper).find('.dey_pickup_locations_tab').hide();
					ProductMetabox.product_delivery_time_mode('#dey_delivery_time_mode');
					tab = $(wrapper).find('.dey_delivery_tab');
					break;

				case '2': // Product pickup
					$(wrapper).find('.dey_pickup_tab').show();
					$(wrapper).find('.dey_pickup_locations_tab').show();
					$(wrapper).find('.dey_delivery_tab').hide();
					ProductMetabox.handle_product_pickup_time_mode('#dey_pickup_time_mode');
					tab = $(wrapper).find('.dey_pickup_tab');
					break;

				default: // User's selection
					$(wrapper).find('.dey_delivery_tab').show();
					$(wrapper).find('.dey_pickup_tab').show();
					$(wrapper).find('.dey_pickup_locations_tab').show();
					$(wrapper).find('.dey_time_slots_tab').show();
					break;
			}

			if (tab) {
				$(tab).find('.dey-delivery-slots-data-tab-link').click();
			} else {
				// Trigger the first link.
				$('div.dey-delivery-slots-data-panels-wrapper').each(function () {
					$(this).find('.dey-delivery-slots-data-tab').eq(0).find('a').click();
				});
			}
		},

		handle_postboxes: function () {
			$('.dey-delivery-slot-type').appendTo('#dey_delivery_slots .hndle');
			$(function () {
				// Prevent inputs in meta box headings opening/closing contents.
				$('#dey_delivery_slots').find('.hndle').unbind('click.postboxes');

				$('#dey_delivery_slots').on('click', '.hndle', function (event) {
					// If the user clicks on some form input inside the h3 the box should not be toggled.
					if ($(event.target).filter('input, option, label, select').length) {
						return;
					}

					$('#dey_delivery_slots').toggleClass('closed');
				});
			});
		},

		product_delivery_slot_type: function ($this) {
			switch ($($this).val()) {
				case '2':
					$('#dey-delivery-slots-data').removeClass('dey-blur').show();
					break;

				default:
					$('#dey-delivery-slots-data').addClass('dey-blur').hide();
					break;
			}
		},

		product_delivery_slot_mode: function ($this) {
			$('.dey-product-delivery-field').closest('p').hide();
			$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').hide();

			switch ($($this).val()) {
				case '2':
					$('.dey-product-expected-delivery-field').closest('p').show();
					if ('3' === $('.dey-product-scheduler-type').val()) {
						$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').show();
					}
					break;

				default:
					$('.dey-product-calender-field').closest('p').show();
					ProductMetabox.product_delivery_time_mode("#dey_delivery_time_mode");
					break;
			}
		},

		product_delivery_time_mode: function ($this) {
			$('.dey-product-time-field').closest('p').hide();
			$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').hide();
			if ('3' === $('.dey-product-scheduler-type').val()) {
				$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').show();
			}

			switch ($($this).val()) {
				case '2':
					$('.dey-product-delivery-time-field').closest('p').show();
					break;

				case '3':
					$('.dey-product-time-slot-field').closest('p').show();
					$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').show();
					break;
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

		add_product_delivery_time_slot: function (event) {
			event.preventDefault();

			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-delivery-time-slots-wrapper');

			ProductMetabox.block(wrapper);
			var data = ({
				action: 'dey_add_product_delivery_time_slot',
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					wrapper.find('.dey-delivery-time-slots-inner-wrapper').append(res.data.html);
					$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
					wrapper.find('.dey-delivery-slots-content-wrapper').last().removeClass('dey-hide');
					ProductMetabox.time_slot_week_days(".dey_enable_week_days");
					$(document.body).trigger('dey-enhanced-init');
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock(wrapper);
			});
		},

		delete_product_delivery_time_slot: function (event) {
			event.preventDefault();

			$($(event.currentTarget)).closest('.dey-delivery-time-slot-wrapper').remove();
		},

		add_product_delivery_holiday: function (event) {
			event.preventDefault();

			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-delivery-holidays-wrapper');

			ProductMetabox.block(wrapper);
			var data = ({
				action: 'dey_add_product_delivery_holiday',
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					wrapper.find('.dey-delivery-holidays-inner-wrapper').append(res.data.html);
					$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
					wrapper.find('.dey-delivery-slots-content-wrapper').last().removeClass('dey-hide');
					$(document.body).trigger('dey-enhanced-init');
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock(wrapper);
			});
		},

		delete_product_delivery_holiday: function (event) {
			event.preventDefault();

			$($(event.currentTarget)).closest('.dey-delivery-holiday-wrapper').remove();
		},

		add_product_delivery_special_day: function (event) {
			event.preventDefault();

			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-delivery-special-days-wrapper');

			ProductMetabox.block(wrapper);
			var data = ({
				action: 'dey_add_product_delivery_special_day',
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					wrapper.find('.dey-delivery-special-days-inner-wrapper').append(res.data.html);
					$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
					wrapper.find('.dey-delivery-slots-content-wrapper').last().removeClass('dey-hide');
					$(document.body).trigger('dey-enhanced-init');
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock(wrapper);
			});
		},

		delete_product_delivery_special_day: function (event) {
			event.preventDefault();

			var wrapper = $($(event.currentTarget)).closest('.dey-delivery-special-day-wrapper');
			wrapper.remove();
		},

		toggle_product_delivery_slots_content: function (event) {
			event.preventDefault();

			var wrapper = $($(event.currentTarget)).closest('.dey-delivery-slots-wrapper');
			if (!wrapper.find('.dey-delivery-slots-content-wrapper').hasClass('dey-hide')) {
				$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
			} else {
				$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
				wrapper.find('.dey-delivery-slots-content-wrapper').removeClass('dey-hide');
			}
		},

		reset_product_time_slot_usage_count: function (event) {
			event.preventDefault();
			if (!confirm(dey_product_params.delete_confirm_msg)) {
				return;
			}

			var $this = $(event.currentTarget);

			ProductMetabox.block($this);
			var data = ({
				action: 'dey_reset_product_time_slot_usage_count',
				product_id: $($this).data('product_id'),
				time_slot_key: $($this).data('key'),
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock($this);
			});
		},

		reset_product_special_day_usage_count: function (event) {
			event.preventDefault();
			if (!confirm(dey_product_params.delete_confirm_msg)) {
				return;
			}

			var $this = $(event.currentTarget);

			ProductMetabox.block($this);
			var data = ({
				action: 'dey_reset_product_special_day_usage_count',
				product_id: $($this).data('product_id'),
				special_day_key: $($this).data('key'),
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					alert(res.data.msg);
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock($this);
			});
		},

		/**
		 * Handles the product pickup time mode.
		 * 
		 * @since 3.5.0
		 * @param {object} $this
		 */
		handle_product_pickup_time_mode: function ($this) {
			$('.dey-product-pickup-time-field').closest('p').hide();
			$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').hide();
			if ('3' === $('.dey-product-scheduler-type').val()) {
				$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').show();
			}

			switch ($($this).val()) {
				case '2':
					$('.dey-product-pickup-time-field').closest('p').show();
					break;

				case '3':
					$('.dey-product-time-slot-field').closest('p').show();
					$('.dey-delivery-slots-data-panels-wrapper').find('.dey_time_slots_tab').show();
					break;
			}
		},

		/**
		 * Adds a new product pickup location.
		 * 
		 * @since 3.5.0
		 * @param {event} event 
		 */
		add_product_pickup_location: function (event) {
			event.preventDefault();

			var $this = $(event.currentTarget),
				wrapper = $($this).closest('.dey-pickup-locations-wrapper');

			ProductMetabox.block(wrapper);
			var data = ({
				action: 'dey_add_product_pickup_location',
				dey_security: dey_product_params.product_nonce,
			});

			$.post(ajaxurl, data, function (res) {
				if (true === res.success) {
					wrapper.find('.dey-pickup-locations-inner-wrapper').append(res.data.html);
					$('.dey-delivery-slots-content-wrapper').addClass('dey-hide');
					wrapper.find('.dey-delivery-slots-content-wrapper').last().removeClass('dey-hide');
					$(document.body).trigger('dey-enhanced-init');
				} else {
					alert(res.data.error);
				}

				ProductMetabox.unblock(wrapper);
			});
		},

		/**
		 * Delete the product pickup location.
		 * 
		 * @since 3.5.0
		 * @param {event} event 
		 */
		delete_product_pickup_location: function (event) {
			event.preventDefault();

			$($(event.currentTarget)).closest('.dey-pickup-location-wrapper').remove();
		},

		/**
		 * Handles the product pickup location selection type.
		 * 
		 * @since 3.5.0
		 * @param {object} $this
		 */
		handle_product_pickup_location_selection_type: function ($this) {
			$('.dey-pickup-locations-wrapper').hide();
			if ('2' === $($this).val()) {
				$('.dey-pickup-locations-wrapper').show();
			}
		},

		block: function (id) {
			if (!ProductMetabox.is_blocked(id)) {
				$(id).addClass('processing').block({
					message: null,
					overlayCSS: { background: '#fff', opacity: 0.7 }
				});
			}
		},

		unblock: function (id) {
			$(id).removeClass('processing').unblock();
		},

		is_blocked: function (id) {
			return $(id).is('.processing') || $(id).parents('.processing').length;
		}
	};

	ProductMetabox.init();
});
