/**
 * Handles Productdelivery datepicker.
 * 
 * @since 1.0.0
 * @global dey_frontend_params
 */
jQuery(function ($) {
	'use strict';

	if ('undefined' === typeof dey_frontend_params) {
		return false;
	}

	var dey_product_datepicker_data;

	/**
	 * Class.
	 * 
	 * @param {Object} $date_picker
	 * @returns {Void}
	 */
	var Dey_Product_Delivery = function ($date_picker) {
		this.element = $date_picker;
		this.wrapper = $date_picker.closest('.dey-product-delivery-slots-wrapper');

		if (!this.wrapper.find('#dey_delivery_date').length) {
			return;
		}

		$(document.body).on('dey_update_product_delivery_charges', this.handle_delivery_charges);

		this.prepare_default_datepicker_data();

		this.wrapper.on('change', '#dey_product_delivery_date_time_slots', this.toggle_time_slots);

		// Initialize the date/datetime pricker.
		if ($date_picker.hasClass('dey-datetimepicker')) {
			this.initialize_datetime_picker();
		} else {
			this.initialize_date_picker();
		}

		$(document.body).trigger('dey_update_product_delivery_charges');
	}

	/**
	 * Prepare the default datepicker data.
	 * 
	 * @returns {Void}
	 */
	Dey_Product_Delivery.prototype.prepare_default_datepicker_data = function () {
		var field_data = this.wrapper.find('.dey-product-delivery-slots-fields-data');
		dey_product_datepicker_data = {
			wrapper: this.wrapper,
			product_id: field_data.data('product_id'),
			min_date: field_data.data('min_date'),
			max_date: field_data.data('max_date'),
			first_time_slot: field_data.data('first_time_slot'),
			available_times: field_data.data('available_times'),
			available_dates: field_data.data('available_dates'),
		}
	}

	/**
	 * Initialize the date picker.
	 * 
	 * @returns {Void}
	 */
	Dey_Product_Delivery.prototype.initialize_date_picker = function () {
		$(this.element).datepicker({
			inline: true,
			beforeShowDay: this.is_valid_day,
			onSelect: this.get_selected_date_data,
			altField: this.wrapper.find('.dey-product-delivery-date'),
			altFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			gotoCurrent: true,
			minDate: new Date(dey_product_datepicker_data.min_date),
			maxDate: new Date(dey_product_datepicker_data.max_date),
		});

	}

	/**
	 * Initialize the datetime picker.
	 * 
	 * @returns {Void}
	 */
	Dey_Product_Delivery.prototype.initialize_datetime_picker = function () {
		$(this.element).datetimepicker({
			inline: true,
			beforeShowDay: this.is_valid_day,
			onSelect: this.get_selected_date_data,
			altField: this.wrapper.find('.dey-product-delivery-date'),
			altFieldTimeOnly: false,
			altFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
			gotoCurrent: true,
			minDate: new Date(dey_product_datepicker_data.min_date),
			maxDate: new Date(dey_product_datepicker_data.max_date),
			minTime: dey_product_datepicker_data.available_times.min_time,
			maxTime: dey_product_datepicker_data.available_times.max_time,
			hourMin: parseInt(dey_product_datepicker_data.available_times.hour_min),
			hourMax: parseInt(dey_product_datepicker_data.available_times.hour_max),
			minuteMin: parseInt(dey_product_datepicker_data.available_times.minute_min),
			minuteMax: parseInt(dey_product_datepicker_data.available_times.minute_max),
		});
	}

	/**
	 * Is valid day to show in datepicker?
	 * 
	 * @param {Object} date_object
	 * @returns {Array}
	 */
	Dey_Product_Delivery.prototype.is_valid_day = function (date_object) {
		var year = date_object.getFullYear(),
			month = ('0' + (date_object.getMonth() + 1)).slice(-2),
			date = ('0' + date_object.getDate()).slice(-2),
			current_date = year + '-' + month + '-' + date;

		if (dey_product_datepicker_data.available_dates[current_date]) {
			switch (dey_product_datepicker_data.available_dates[current_date].t) {
				case 'sy':
					return [true, 'dey-calender-day dey-special-day', dey_product_datepicker_data.available_dates[current_date].l];

				case 'hy':
					return [false, 'dey-calender-day dey-holiday', dey_product_datepicker_data.available_dates[current_date].l];

				case 'pb':
					return [true, 'dey-calender-day dey-partial-booked', dey_product_datepicker_data.available_dates[current_date].l];

				case 'fb':
					return [false, 'dey-calender-day dey-booked', dey_product_datepicker_data.available_dates[current_date].l];

				case 'ny':
					return [true, 'dey-calender-day', dey_product_datepicker_data.available_dates[current_date].l];
			}

		}

		return [false, 'dey-calender-day', ''];
	}

	/**
	 * Get the selected date data.
	 * 
	 * @param {String} date
	 * @param {Object} date_object
	 * @returns {Boolean}
	 */
	Dey_Product_Delivery.prototype.get_selected_date_data = function (on_selected_date, date_object) {
		var wrapper = $(this).closest('.dey-product-delivery-slots-fields-wrapper'),
			time_slot_field = wrapper.find('#dey_product_delivery_date_time_slots');

		var year = date_object.selectedYear,
			month = ('0' + (date_object.selectedMonth + 1)).slice(-2),
			date = ('0' + date_object.selectedDay).slice(-2),
			selected_date = year + '-' + month + '-' + date;

		if (wrapper.find('.dey-product-date-picker-label').length) {
			wrapper.find(".dey-product-date-picker-label").val(on_selected_date);
		}

		if (!time_slot_field.length) {
			$(document.body).trigger('dey_update_product_delivery_charges');
			return true;
		}

		dey_block(wrapper);
		var data = ({
			action: 'dey_get_product_selected_delivery_date_data',
			date: selected_date,
			product_id: dey_product_datepicker_data.product_id,
			dey_security: dey_frontend_params.datepicker_nonce,
		});

		$.post(dey_frontend_params.ajax_url, data, function (res) {
			if (true === res.success) {
				$('option:not([value=""])', time_slot_field).remove();
				$.each(res.data.time_slots, function (key, value) {
					time_slot_field.append($("<option></option>").attr('value', value.id).html(value.label));
				});

				if ('yes' == dey_product_datepicker_data.first_time_slot) {
					$('option:not([value=""]):first', time_slot_field).attr('selected', 'selected').trigger('change');
				}

				$(document.body).trigger('dey_update_product_delivery_charges');
			} else {
				alert(res.data.error);
			}

			dey_unblock(wrapper);
		});

		return true;
	}

	/**
	 * Toggle time slots.
	 * 
	 * @return {Void}
	 */
	Dey_Product_Delivery.prototype.toggle_time_slots = function () {
		$(document.body).trigger('dey_update_product_delivery_charges');
	}

	/**
	 * handle the delivery charges.
	 * 
	 * @returns {Void}
	 */
	Dey_Product_Delivery.prototype.handle_delivery_charges = function () {
		var date_field = dey_product_datepicker_data.wrapper.find('.dey-product-delivery-date'),
			time_slot_field = dey_product_datepicker_data.wrapper.find('#dey_product_delivery_date_time_slots');

		dey_block(dey_product_datepicker_data.wrapper);
		var data = ({
			action: 'dey_handle_product_delivery_charge',
			product_id: dey_product_datepicker_data.product_id,
			date: date_field.val(),
			time_slot: time_slot_field.val(),
			dey_security: dey_frontend_params.datepicker_nonce,
		});

		$.post(dey_frontend_params.ajax_url, data, function (res) {
			if (true === res.success) {
				dey_product_datepicker_data.wrapper.find('.dey-product-delivery-total').html(res.data.price);
			} else {
				alert(res.data.error);
			}

			dey_unblock(dey_product_datepicker_data.wrapper);
		});
	}

	/**
	 * Block the element.
	 * 
	 * @param {Sting} id
	 * @returns {Void}
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
	 * @param {Sting} id
	 * @returns {Boolean}
	 */
	var dey_is_blocked = function (id) {
		return $(id).is('.processing') || $(id).parents('.processing').length;
	}

	/**
	 * Unblock the element.
	 * 
	 * @param {Sting} id
	 * @returns {Void}
	 */
	var dey_unblock = function (id) {
		$(id).removeClass('processing').unblock();
	}

	/**
	 * Function to call dey_product_delivery_datepicker on jquery selector.
	 */
	$.fn.dey_product_delivery_datepicker = function () {
		new Dey_Product_Delivery(this);
		return this;
	};

	$(function () {
		$('.dey-product-delivery-date-field').each(function () {
			$(this).dey_product_delivery_datepicker();
		});
	});
});
