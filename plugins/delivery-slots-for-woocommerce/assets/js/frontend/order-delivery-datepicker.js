/* global dey_order_delivery_params */

jQuery(function ($) {
	'use strict';

	if (typeof dey_order_delivery_params === 'undefined') {
		return false;
	}

	var datepicker_params = dey_order_delivery_params;

	/**
	 * Order Delivery
	 * 
	 * @type Object
	 */
	var Dey_Order_Delivery = {
		init: function () {
			$(document.body).on('dey_init_order_delivery_fields', this.initialize_fields);
			$(document.body).on('dey_init_order_delivery', this.init_order_delivery).trigger('dey_init_order_delivery');
			$(document.body).on('dey_update_order_delivery_selected_date_data', this.update_selected_date_data);
			$(document.body).on('change', '#dey_order_delivery_date_time_slots', this.handle_time_slots);
			$(document.body).on('change', '.dey-order-delivery-date-field', this.reset_order_delivery_date_data);
		},

		init_order_delivery: function () {
			Dey_Order_Delivery.initialize_fields();
			Dey_Order_Delivery.update_selected_date_data();
		},

		initialize_fields: function () {
			var wrapper = $('.dey-order-delivery-slots-fields-wrapper');
			if (!wrapper.length) {
				return;
			}

			dey_block(wrapper);
			var data = ({
				action: 'dey_get_order_delivery_script_data',
				dey_security: datepicker_params.datepicker_nonce,
			});

			$.post(datepicker_params.ajax_url, data, function (res) {
				if (true === res.success) {
					datepicker_params = res.data.script_data;
				} else {
					datepicker_params = dey_order_delivery_params;
				}

				dey_unblock(wrapper);
				// Initialize the date/datetime pricker.
				if ($('.dey-order-delivery-slots-fields-wrapper').find('#dey_delivery_date').hasClass('dey-datetimepicker')) {
					Dey_Order_Delivery.initialize_datetime_picker();
				} else {
					Dey_Order_Delivery.initialize_date_picker();
				}

				$(document).trigger('dey_init_order_scheduler_type');
			});
		},

		initialize_date_picker: function () {
			var wrapper = $('.dey-order-delivery-slots-fields-wrapper');

			if (wrapper.find('#dey_delivery_date').data('datepicker-initialized')) {
				return;
			}

			wrapper.find('#dey_delivery_date').datepicker({
				inline: true,
				beforeShowDay: Dey_Order_Delivery.is_valid_day,
				onSelect: Dey_Order_Delivery.update_selected_date_data,
				altField: wrapper.find('.dey-order-delivery-date'),
				altFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true,
				gotoCurrent: true,
				minDate: new Date(datepicker_params.min_date),
				maxDate: new Date(datepicker_params.max_date),
			});

			wrapper.find('#dey_delivery_date').data('datepicker-initialized', true);
		},

		initialize_datetime_picker: function () {
			var wrapper = $('.dey-order-delivery-slots-fields-wrapper'),
				selectedDate = wrapper.find('.dey-order-delivery-date').data('selected_date'),
				defaultDate = null;

			if ('' !== selectedDate) {
				var parts = selectedDate.split(/[- :]/),
				defaultDate = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4]);
			}

			$(wrapper.find('#dey_delivery_date')).datepicker('destroy');
			$(wrapper.find('#dey_delivery_date')).datetimepicker({
				inline: true,
				beforeShowDay: Dey_Order_Delivery.is_valid_day,
				onSelect: Dey_Order_Delivery.update_selected_date_data,
				altField: wrapper.find('.dey-order-delivery-date'),
				defaultDate: defaultDate,
				altFieldTimeOnly: false,
				altFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true,
				gotoCurrent: true,
				minDate: new Date(datepicker_params.min_date),
				maxDate: new Date(datepicker_params.max_date + datepicker_params.available_times.max_time),
				minTime: datepicker_params.available_times.min_time,
				maxTime: datepicker_params.available_times.max_time,
				hourMin: parseInt(datepicker_params.available_times.hour_min),
				hourMax: parseInt(datepicker_params.available_times.hour_max),
				minuteMin: parseInt(datepicker_params.available_times.minute_min),
				minuteMax: parseInt(datepicker_params.available_times.minute_max),
			});
		},

		is_valid_day: function (date_object) {
			var year = date_object.getFullYear(),
				month = ('0' + (date_object.getMonth() + 1)).slice(-2),
				date = ('0' + date_object.getDate()).slice(-2),
				current_date = year + '-' + month + '-' + date;

			if (datepicker_params.holidays[current_date] && (!datepicker_params.available_dates[current_date] || 'sy' !== datepicker_params.available_dates[current_date].t)) {
				return [false, 'dey-calender-day dey-holiday', datepicker_params.holidays[current_date]];
			}

			if (datepicker_params.available_dates[current_date]) {
				switch (datepicker_params.available_dates[current_date].t) {
					case 'sy':
						return [true, 'dey-calender-day dey-special-day', datepicker_params.available_dates[current_date].l];

					case 'hy':
						return [false, 'dey-calender-day dey-holiday', datepicker_params.available_dates[current_date].l];

					case 'pb':
						return [true, 'dey-calender-day dey-partial-booked', datepicker_params.available_dates[current_date].l];

					case 'fb':
						return [false, 'dey-calender-day dey-booked', datepicker_params.available_dates[current_date].l];

					case 'ny':
						return [true, 'dey-calender-day', datepicker_params.available_dates[current_date].l];
				}
			}

			return [false, 'dey-calender-day', ''];
		},

		/**
		 * Update the selected date data.
		 * 
		 * @since 4.0.0
		 */
		update_selected_date_data: function (selected_date_html) {
			var wrapper = $('.dey-order-delivery-slots-fields-wrapper'),
				selected_date = $('.dey-order-delivery-date').val(),
				time_slot_field = wrapper.find('#dey_order_delivery_date_time_slots');

			// For Compatibility of Divi theme.
			wrapper.find('.dey-order-delivery-date').trigger('change');
			if (wrapper.find('.dey-order-delivery-date-picker-field').length) {
				wrapper.find('.dey-order-delivery-date-picker-field').val(selected_date_html);
			}

			dey_block(wrapper);
			var data = ({
				action: 'dey_handle_order_delivery_selected_date_data',
				date: selected_date,
				time_slot_id: time_slot_field.val(),
				dey_security: datepicker_params.datepicker_nonce,
			});

			$.post(datepicker_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$('option:not([value=""])', time_slot_field).remove();
					$.each(res.data.time_slots, function (key, value) {
						$(time_slot_field).append($('<option></option>').attr('value', value.id).html(value.label));
					});

					if ('yes' === datepicker_params.first_time_slot) {
						$('option:not([value=""]):first', time_slot_field).attr('selected', 'selected').trigger('change');
					}
				}

				updateCheckout();
				dey_unblock(wrapper);
			});
		},

		/**
		 * Handle the order delivery time slots.
		 * 
		 * @since 4.0.0
		 * @param {object} event
		 */
		handle_time_slots: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $('.dey-order-delivery-slots-fields-wrapper');

			dey_block(wrapper);
			var data = ({
				action: 'dey_handle_order_delivery_time_slots',
				time_slot_id: $this.val(),
				dey_security: datepicker_params.datepicker_nonce,
			});

			$.post(datepicker_params.ajax_url, data, function (res) {
				if (true === res.success) {
					updateCheckout();
				}

				dey_unblock(wrapper);
			});
		},

		/**
		 * Reset order delivery date data.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		reset_order_delivery_date_data: function (event) {
			if ('' === $(event.currentTarget).val()) {
				$('.dey-order-delivery-date').val('');
				// Update the selected date data.
				Dey_Order_Delivery.update_selected_date_data();
			}
		},
	};

	/**
	 * Is block cart/checkout.
	 * 
	 * @since 4.0.0
	 * @returns {boolean}
	 */
	const isBlockCart = () => {
		return dey_frontend_params.is_block_cart || dey_frontend_params.is_block_checkout;
	};

	/**
	 * Update the cart or checkout.
	 * 
	 * @since 4.0.0
	 */
	const updateCheckout = () => {
		if (isBlockCart()) {
			$(document.body).trigger('dey_update_cart_block');
		} else {
			$(document.body).trigger('update_checkout');
		}
	};

	/**
	 * Block the element.
	 * 
	 * @param {string} id
	 * @returns {void}
	 */
	var dey_block = function (id) {
		if (!dey_is_blocked(id)) {
			$(id).addClass('processing').block({
				message: null,
				overlayCSS: {
					background: '#fff',
					opacity: 0.7
				}
			});
		}
	}

	/**
	 * Is blocked current element?.
	 * 
	 * @param {string} id
	 * @returns {boolean}
	 */
	var dey_is_blocked = function (id) {
		return $(id).is('.processing') || $(id).parents('.processing').length;
	}

	/**
	 * Unblock the element.
	 * 
	 * @param {string} id
	 * @returns {void}
	 */
	var dey_unblock = function (id) {
		$(id).removeClass('processing').unblock();
	}

	Dey_Order_Delivery.init();
});
