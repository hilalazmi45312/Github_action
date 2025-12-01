/* global dey_enhanced_params */

jQuery(function ($) {
	'use strict';

	function dey_get_enhanced_select_format_string() {
		return {
			'language': {
				errorLoading: function () {
					return dey_enhanced_params.i18n_searching;
				},
				inputTooLong: function (args) {
					var overChars = args.input.length - args.maximum;

					if (1 === overChars) {
						return dey_enhanced_params.i18n_input_too_long_1;
					}

					return dey_enhanced_params.i18n_input_too_long_n.replace('%qty%', overChars);
				},
				inputTooShort: function (args) {
					var remainingChars = args.minimum - args.input.length;

					if (1 === remainingChars) {
						return dey_enhanced_params.i18n_input_too_short_1;
					}

					return dey_enhanced_params.i18n_input_too_short_n.replace('%qty%', remainingChars);
				},
				loadingMore: function () {
					return dey_enhanced_params.i18n_load_more;
				},
				maximumSelected: function (args) {
					if (args.maximum === 1) {
						return dey_enhanced_params.i18n_selection_too_long_1;
					}

					return dey_enhanced_params.i18n_selection_too_long_n.replace('%qty%', args.maximum);
				},
				noResults: function () {
					return dey_enhanced_params.i18n_no_matches;
				},
				searching: function () {
					return dey_enhanced_params.i18n_searching;
				}
			}
		};
	}

	try {
		$(document.body).on('dey-enhanced-init', function () {
			if ($('select.dey_select2').length) {
				//Select2 with customization
				$('select.dey_select2').each(function () {
					var select2_args = {
						allowClear: $(this).data('allow_clear') ? true : false,
						placeholder: $(this).data('placeholder'),
						minimumResultsForSearch: 10,
					};

					select2_args = $.extend(select2_args, dey_get_enhanced_select_format_string());

					$(this).select2(select2_args);
				});
			}
			if ($('select.dey_select2_search').length) {
				//Multiple select with ajax search
				$('select.dey_select2_search').each(function () {
					var select2_args = {
						allowClear: $(this).data('allow_clear') ? true : false,
						placeholder: $(this).data('placeholder'),
						minimumInputLength: $(this).data('minimum_input_length') ? $(this).data('minimum_input_length') : 3,
						escapeMarkup: function (m) {
							return m;
						},
						ajax: {
							url: dey_enhanced_params.ajaxurl,
							dataType: 'json',
							delay: 250,
							data: function (params) {
								return {
									term: params.term,
									action: $(this).data('action') ? $(this).data('action') : 'dey_json_search_customers',
									exclude_global_variable: $(this).data('exclude-global-variable') ? $(this).data('exclude-global-variable') : 'no',
									dey_security: $(this).data('nonce') ? $(this).data('nonce') : dey_enhanced_params.search_nonce,
								};
							},
							processResults: function (data) {
								var terms = [];
								if (data) {
									$.each(data, function (id, term) {
										terms.push({
											id: id,
											text: term
										});
									});
								}
								return {
									results: terms
								};
							},
							cache: true
						}
					};

					select2_args = $.extend(select2_args, dey_get_enhanced_select_format_string());

					$(this).select2(select2_args);
				});
			}

			if ($('.dey_datepicker').length) {
				$('.dey_datepicker').on('change', function () {
					if ($(this).val() === '') {
						$(this).next().next(".dey_alter_datepicker_value").val('');
					}
				});
				$('.dey_datepicker').each(function () {
					$(this).datepicker({
						altField: $(this).next(".dey_alter_datepicker_value"),
						altFormat: 'yy-mm-dd',
						dateFormat: dey_enhanced_params.date_format,
						changeMonth: true,
						changeYear: true,
						showButtonPanel: true,
						showOn: "button",
						buttonImage: dey_enhanced_params.calendar_image,
						buttonImageOnly: true
					});
				});
			}

			if ($('.dey_datetimepicker').length) {
				$('.dey_datetimepicker').on('change', function () {
					if ('' === $(this).val()) {
						$(this).next().next('.dey_alter_datepicker_value').val('');
					}
				});
				$('.dey_datetimepicker').each(function () {
					$(this).datetimepicker({
						altField: $(this).next('.dey_alter_datepicker_value'),
						altFieldTimeOnly: false,
						altFormat: 'yy-mm-dd',
						altTimeFormat: 'HH:mm',
						dateFormat: 'yy-mm-dd',
						timeFormat: 'HH:mm',
						changeMonth: true,
						changeYear: true,
						showButtonPanel: true,
						showOn: 'button',
						buttonImage: dey_enhanced_params.calendar_image,
						buttonImageOnly: true
					});
				});
			}

			if ($('.dey_timepicker').length) {
				$('.dey_timepicker').on('change', function () {
					if ('' === $(this).val()) {
						$(this).next('.dey_alter_timepicker_value').val('');
					}
				});

				$('.dey_timepicker').each(function () {
					$(this).timepicker({
						altFieldTimeOnly: true,
						altField: $(this).next(".dey_alter_timepicker_value"),
						hourGrid: 6,
						minuteGrid: 10,
					});
				});
			}

			if ($('.colorpick').length) {
				// Color picker
				$('.colorpick')

					.iris({
						change: function (event, ui) {
							$(this).parent().find('.colorpickpreview').css({ backgroundColor: ui.color.toString() });
						},
					hide: true,
					border: true
					})

					.on('click focus', function (event) {
						event.stopPropagation();
						$('.iris-picker').hide();
						$(this).closest('td').find('.iris-picker').show();
						$(this).data('original-value', $(this).val());
					})

					.on('change', function () {
						if ($(this).is('.iris-error')) {
							var original_value = $(this).data('original-value');

							if (original_value.match(/^\#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/)) {
								$(this).val($(this).data('original-value')).change();
							} else {
								$(this).val('').change();
							}
						}
					});
			}

			$('body').on('click', function () {
				$('.iris-picker').hide();
			});

		});

		$(document.body).trigger('dey-enhanced-init');
	} catch (err) {
		window.console.log(err);
	}

});
