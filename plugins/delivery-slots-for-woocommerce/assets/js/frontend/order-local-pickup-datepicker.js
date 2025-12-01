/* global dey_order_local_pickup_params */

jQuery(function ($) {
	'use strict';

	if (typeof dey_order_local_pickup_params === 'undefined') {
		return false;
	}

	var datepicker_params = dey_order_local_pickup_params;

	/**
	 * Order Local Pickup
	 * 
	 * @type Object
	 */
	var Dey_Order_Local_Pickup = {
		init: function () {
			$(document.body).on('dey_init_order_local_pickup_fields', this.initialize_fields);
			$(document.body).on('dey_init_order_local_pickup', this.init_order_local_pickup).trigger('dey_init_order_local_pickup');
			$(document.body).on('change', '#dey_pickup_location', this.trigger_order_pickup_location);
			$(document.body).on('change', '#dey_order_local_pickup_date_time_slots', this.handle_time_slots);
			$(document.body).on('change', '.dey-order-local-pickup-date-field', this.reset_order_local_pickup_date_data);
		},

		/**
		 * Handle the order pickup location.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		trigger_order_pickup_location: function (event) {
			event.preventDefault();
			Dey_Order_Local_Pickup.initialize_fields(true);
		},

		init_order_local_pickup: function () {
			Dey_Order_Local_Pickup.initialize_fields(true);
		},

		initialize_fields: function (update_selected_date = false) {
			var wrapper = $('.dey-order-local-pickup-slots-wrapper');
			if (!wrapper.length) {
				return;
			}

			var previousDate = $('.dey-order-local-pickup-date').val();

			dey_block(wrapper);
			var data = ({
				action: 'dey_get_order_local_pickup_script_data',
				pickup_location_id: $('#dey_pickup_location').val(),
				dey_security: datepicker_params.datepicker_nonce,
			});

			$.post(datepicker_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$(wrapper).replaceWith(res.data.html);
					datepicker_params = res.data.script_data;

					var $newDateField = $('.dey-order-local-pickup-slots-wrapper .dey-order-local-pickup-date');
					
					if (previousDate) {
						$newDateField.val(previousDate);
					}

				} else {
					datepicker_params = dey_order_local_pickup_params;
				} 

				dey_unblock(wrapper);

				// Initialize the date/datetime picker.
				if ($('.dey-order-local-pickup-slots-wrapper').find('#dey_local_pickup_date').hasClass('dey-datetimepicker')) {
					Dey_Order_Local_Pickup.initialize_datetime_picker();
				} else {
					Dey_Order_Local_Pickup.initialize_date_picker();
				}

				if( true === update_selected_date) {
					Dey_Order_Local_Pickup.update_selected_date_data();
				}

				$(document).trigger('dey_init_order_scheduler_type');
			});
		},

		initialize_date_picker: function () {
			var wrapper = $('.dey-order-local-pickup-slots-wrapper');

			if (wrapper.find('#dey_local_pickup_date').data('datepicker-initialized')) {
				return;
			}

			wrapper.find('#dey_local_pickup_date').datepicker({
				inline: true,
				beforeShowDay: Dey_Order_Local_Pickup.is_valid_day,
				onSelect: Dey_Order_Local_Pickup.update_selected_date_data,
				altField: wrapper.find('.dey-order-local-pickup-date'),
				altFormat: 'yy-mm-dd',
				changeMonth: true,
				changeYear: true,
				gotoCurrent: true,
				minDate: new Date(datepicker_params.min_date),
				maxDate: new Date(datepicker_params.max_date),
			});

			wrapper.find('#dey_local_pickup_date').data('datepicker-initialized', true);
		},

		initialize_datetime_picker: function () {
			var wrapper = $('.dey-order-local-pickup-slots-wrapper'),
				selectedDate = wrapper.find('.dey-order-local-pickup-date').data('selected_date'),
				defaultDate = null;

			if ('' !== selectedDate) {
				var parts = selectedDate.split(/[- :]/),
				defaultDate = new Date(parts[0], parts[1] - 1, parts[2], parts[3], parts[4]);
			}

			$(wrapper.find('#dey_local_pickup_date')).datepicker('destroy');
			$(wrapper.find('#dey_local_pickup_date')).datetimepicker({
				inline: true,
				beforeShowDay: Dey_Order_Local_Pickup.is_valid_day,
				onSelect: Dey_Order_Local_Pickup.update_selected_date_data,
				altField: wrapper.find('.dey-order-local-pickup-date'),
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
			var wrapper = $('.dey-order-local-pickup-slots-wrapper'),
				selected_date = $('.dey-order-local-pickup-date').val(),
				time_slot_field = wrapper.find('#dey_order_local_pickup_date_time_slots');

			// For Compatibility of Divi theme.
			wrapper.find('.dey-order-local-pickup-date').trigger('change');
			if (wrapper.find('.dey-order-local-pickup-date-picker-field').length) {
				wrapper.find('.dey-order-local-pickup-date-picker-field').val(selected_date_html);
			}

			dey_block(wrapper);
			var data = ({
				action: 'dey_handle_order_pickup_selected_date_data',
				date: selected_date,
				time_slot_id: time_slot_field.val(),
				pickup_location_id: wrapper.find('#dey_pickup_location').val(),
				dey_security: datepicker_params.datepicker_nonce,
			});

			$.post(datepicker_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$('option:not([value=""])', time_slot_field).remove();
					$.each(res.data.time_slots, function (key, value) {
						time_slot_field.append($('<option></option>').attr('value', value.id).html(value.label));
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
		 * Handle the order local pickup time slots.
		 * 
		 * @since 4.0.0 
		 * @param {object} event 
		 */
		handle_time_slots: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = $('.dey-order-local-pickup-slots-wrapper');

			dey_block(wrapper);
			var data = ({
				action: 'dey_handle_order_local_pickup_time_slots',
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
		 * Reset order local pickup date data.
		 * 
		 * @since 4.0.0
		 * @param {object} event 
		 */
		reset_order_local_pickup_date_data: function (event) {
			if ('' === $(event.currentTarget).val()) {
				$('.dey-order-local-pickup-date').val('');
				// Update selected date data.
				Dey_Order_Local_Pickup.update_selected_date_data();
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
	 * @since 4.0.0
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
	 * @since 4.0.0
	 * @param {string} id
	 * @returns {boolean}
	 */
	var dey_is_blocked = function (id) {
		return $(id).is('.processing') || $(id).parents('.processing').length;
	}

	/**
	 * Unblock the element.
	 * 
	 * @since 4.0.0
	 * @param {string} id
	 * @returns {void}
	 */
	var dey_unblock = function (id) {
		$(id).removeClass('processing').unblock();
	}

	Dey_Order_Local_Pickup.init();
});
