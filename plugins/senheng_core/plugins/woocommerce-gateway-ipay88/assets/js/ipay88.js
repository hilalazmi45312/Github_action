// Global variables to track state
window.ipay88RestoringSelections = false;
window.ipay88CollapsedSections = window.ipay88CollapsedSections || [];
window.ipay88LastPlanKey = window.ipay88LastPlanKey || null;
window.ipay88PendingInstallmentSelection = null;
window.ipay88AjaxInProgress = false; // Prevent overlapping AJAX calls

// AJAX: Update admin fee (global)
function updateAdminFee(adminFee, paymentValue, months) {
	var $ = jQuery;

	// Always update the hidden admin fee input
	const adminFeeInput = $('#ipay88_admin_fee' + paymentValue);
	adminFeeInput.val(adminFee);

	// Also update the global hidden fields for the checkout form
	// These are the ones that will be submitted with "Place Order"
	let $globalFeeInput = $('input[name="ipay88_admin_fee"]');
	if ($globalFeeInput.length === 0) {
		$globalFeeInput = $('<input type="hidden" name="ipay88_admin_fee" />').appendTo('form.checkout');
	}
	$globalFeeInput.val(adminFee);

	let $globalPlanInput = $('input[name="ipay88_payment_plan"]');
	if ($globalPlanInput.length === 0) {
		$globalPlanInput = $('<input type="hidden" name="ipay88_payment_plan" />').appendTo('form.checkout');
	}
	$globalPlanInput.val(paymentValue);

	let $globalMonthsInput = $('input[name="ipay88_months"]');
	if ($globalMonthsInput.length === 0) {
		$globalMonthsInput = $('<input type="hidden" name="ipay88_months" />').appendTo('form.checkout');
	}
	$globalMonthsInput.val(months || 0);

	// Determine if this is a clearing action (non-BNPL switch)
	const isClearing = Number(adminFee) === 0;

	// Require a valid paymentValue always
	if (!paymentValue) {
		window.ipay88RestoringSelections = false;
		window.ipay88PendingInstallmentSelection = null;
		window.ipay88LastPlanKey = null;
		return;
	}

	// For BNPL fee updates, months must be provided. For clearing, allow months to be falsy.
	if (!isClearing && !months) {
		window.ipay88RestoringSelections = false;
		window.ipay88PendingInstallmentSelection = null;
		window.ipay88LastPlanKey = null;
		return;
	}

	const normalizedMonths = isClearing ? (months || 0) : months;

	const key = `${paymentValue}_${normalizedMonths}_${adminFee}`;
	if (key === window.ipay88LastPlanKey) {
		return;
	}

	window.ipay88LastPlanKey = key;
	// We NO LONGER set AjaxInProgress=true, allowing immediate consecutive clicks
	// window.ipay88AjaxInProgress = true; 
	window.ipay88RestoringSelections = true;

	// Store the NEW selection (used for restoring after checkout update)
	if (!isClearing) {
		window.ipay88PendingInstallmentSelection = {
			paymentValue: paymentValue,
			months: normalizedMonths,
			installmentId: `installment_${paymentValue}_${normalizedMonths}`
		};
	} else {
		window.ipay88PendingInstallmentSelection = null;
	}

	// DIRECT UPDATE: No separate AJAX call.
	// The form inputs are already updated above, so standard update_checkout 
	// will send the data in 'post_data', and standard Place Order will send it in $_POST.
	$(document.body).trigger('update_checkout');
}

function initIPay88PaymentOptions() {
	var $ = jQuery;

	// CRITICAL: Remove ALL old event handlers first
	$(document).off('change.ipay88');
	$(document).off('click.ipay88');

	// Uses global updateAdminFee defined above

	// Handle payment option selection
	$(document).on('change.ipay88', '.ipay88-payment-option input[type="radio"]', function () {
		$('.ipay88-payment-option').removeClass('selected');

		const previousPaymentValue = window.ipay88SelectedPaymentValue;

		if (!window.ipay88RestoringSelections) {
			$('.ipay88-installment-options').hide();
			$('.ipay88-installment-options input[type="radio"]').prop('checked', false);
			$('.ipay88-installment-option').removeClass('selected');
		}

		$('.ipay88-month-dropdown').hide();

		if ($(this).is(':checked')) {
			const $option = $(this).closest('.ipay88-payment-option');
			$option.addClass('selected');

			if ($(this).data('payment-type') === 'bnpl' && !$option.hasClass('bnpl-option')) {
				$option.addClass('bnpl-option');
			}

			const bankName = $option.find('.ipay88-payment-label').text().trim();
			const bankLogo = $option.find('img').attr('src');
			const paymentBOX = $('.payment_box');
			paymentBOX.find('p').html(
				`<img src="${bankLogo}" alt="${bankName}" class="ipay88-bank-logo" /> 
				<span class="ipay88-payment-label">${bankName}</span>`
			);

			const paymentValue = $(this).val();

			// If switching to DIFFERENT payment, clear everything
			if (previousPaymentValue && String(previousPaymentValue) !== String(paymentValue)) {
				window.ipay88SelectedInstallmentMonths = null;
				window.ipay88SelectedInstallmentId = null;
				window.ipay88LastPlanKey = null;
				window.ipay88PendingInstallmentSelection = null;
				window.ipay88AjaxInProgress = false;
			}

			window.ipay88SelectedPaymentValue = paymentValue;
			window.ipay88SelectedPaymentType = $(this).data('payment-type');

			if ($(this).data('payment-type') === 'bnpl') {
				const paymentPlans = shBnplData.paymentPlans || [];
				const total = shBnplData.total || 0;
				const toNum = (x) => parseFloat(String(x).replace(/[^\d.]/g, '')) || 0;

				let html = '';

				if (paymentPlans.length > 0) {
					const eligiblePlans = (() => {
						// keep same provider and affordability rule
						const base = paymentPlans
							.filter(p => String(p.ipay88_id) === String(paymentValue))
							.filter(p => toNum(total) >= toNum(p.min_amount));

						// group by months and keep the one with the HIGHEST admin fee
						const byMonth = {};
						base.forEach(p => {
							const m = String(p.months);                      // group key
							const fee = toNum(p.apply_admin_fee);            // numeric fee
							if (!byMonth[m] || fee > toNum(byMonth[m].apply_admin_fee)) {
								byMonth[m] = p;
							}
						});

						// return one per month, sorted by months asc
						return Object.values(byMonth)
							.sort((a, b) => toNum(a.months) - toNum(b.months));
					})();

					if (!eligiblePlans.length) {
						html = '<div class="ipay88-installment-option">No instalment plans available for this amount.</div>';
					} else {
						eligiblePlans.forEach(plan => {
							const monthly = ((toNum(total) + toNum(plan.apply_admin_fee)) / toNum(plan.months)).toFixed(2);
							const adminFee = toNum(plan.apply_admin_fee);
							html += `<div class="ipay88-installment-option">
								<input type="radio" id="installment_${paymentValue}_${plan.months}" name="ipay88_payment_plan${paymentValue}" value="${plan.months}" data-admin-fee="${adminFee}" data-payment-id="${paymentValue}">
								<label for="installment_${paymentValue}_${plan.months}">RM ${monthly} / months (x${plan.months})${adminFee ? ` (+ RM${adminFee.toFixed(2)} admin fee)` : ''}</label>
							</div>`;
						});
					}
				} else {
					html = '<div class="ipay88-installment-option">No instalment plans available for this amount.</div>';
				}

				// CRITICAL: Remove ALL duplicate containers before proceeding
				$(`[id='installment-options-${paymentValue}']`).not(':first').remove();

				const $installmentContainer = $(`#installment-options-${paymentValue}`).first();
				const $installmentList = $installmentContainer.find('.ipay88-installment-list');

				// Clear and rebuild
				$installmentList.empty().html(html);

				if ($installmentContainer.length) {
					$installmentContainer.show();
				}

				// Attach change handler with strict guards
				$installmentList.off('change.ipay88').on('change.ipay88', 'input[type="radio"]', function (e) {
					e.stopImmediatePropagation(); // Prevent duplicate events

					const months = $(this).val();
					const adminFee = parseFloat($(this).data('admin-fee')) || 0;
					const radioPaymentId = $(this).data('payment-id');

					// STRICT GUARD: Only process if this belongs to current payment
					if (String(radioPaymentId) !== String(paymentValue)) {
						return false;
					}

					// STRICT GUARD: Only process if this is the checked radio
					if (!$(this).is(':checked')) {
						return false;
					}

					// STRICT GUARD: Prevent if AJAX in progress
					if (window.ipay88AjaxInProgress) {
						return false;
					}


					if (months) {
						window.ipay88SelectedInstallmentMonths = months;
						window.ipay88SelectedInstallmentId = `installment_${paymentValue}_${months}`;
						window.ipay88SelectedPaymentValue = paymentValue;

						updateAdminFee(adminFee, paymentValue, months);
					}

					return false;
				});

				// Restore previous selection for THIS payment only
				if (
					window.ipay88SelectedPaymentValue === paymentValue &&
					window.ipay88SelectedInstallmentMonths &&
					window.ipay88SelectedInstallmentId &&
					String(window.ipay88SelectedInstallmentId).indexOf(`installment_${paymentValue}_`) === 0
				) {
					const prevId = `installment_${paymentValue}_${window.ipay88SelectedInstallmentMonths}`;
					const $prevRadio = $installmentList.find(`#${prevId}`);
					if ($prevRadio.length) {
						$prevRadio.prop('checked', true);
					}
				}
			} else {
				// Non-BNPL: clear admin fee for this selected payment
				updateAdminFee(0, paymentValue, 0);
			}
		}
	});

	// Click on entire payment option
	$(document).on('click.ipay88', '.ipay88-payment-option', function (e) {
		if (e.target.type !== 'radio') {
			$(this).find('input[type="radio"]').prop('checked', true).trigger('change');
		}
	});

	// Handle collapse toggle
	$(document).on('click.ipay88', '.ipay88-section-title', function (e) {
		e.preventDefault();
		const $title = $(this);
		const $grid = $title.next('.ipay88-payment-grid');
		const sectionText = $title.text().trim();

		$title.toggleClass('collapsed');
		$grid.toggleClass('collapsed');

		const isCollapsed = $title.hasClass('collapsed');
		$title.attr('aria-expanded', isCollapsed ? 'true' : 'false');

		if (isCollapsed) {
			if (!window.ipay88CollapsedSections.includes(sectionText)) {
				window.ipay88CollapsedSections.push(sectionText);
			}
		} else {
			window.ipay88CollapsedSections = window.ipay88CollapsedSections.filter(function (text) {
				return text !== sectionText;
			});
		}
	});

	// Initialization
	$('.ipay88-section-title').removeClass('collapsed').attr('aria-expanded', 'false');
	$('.ipay88-payment-grid').removeClass('collapsed');
	$('.ipay88-month-dropdown').hide();
	$('.ipay88-installment-options').hide();
}

// Initialize on document ready
jQuery(document).ready(function ($) {
	initIPay88PaymentOptions();
});

// Reinitialize after WooCommerce AJAX updates
jQuery(document).on('updated_checkout', function () {
	const $ = jQuery;

	// Clean up duplicate IDs only within IPay88 container
	const $scope = $('.ipay88-payment-container').first();
	if ($scope.length) {
		const seenIds = {};
		$scope.find('[id]').each(function () {
			const id = this.id;
			if (seenIds[id]) {
				$(this).remove();
			} else {
				seenIds[id] = true;
			}
		});
	}

	let selectedPaymentValue, selectedInstallmentId, selectedInstallmentMonths;

	if (window.ipay88PendingInstallmentSelection) {
		selectedPaymentValue = window.ipay88PendingInstallmentSelection.paymentValue;
		selectedInstallmentMonths = window.ipay88PendingInstallmentSelection.months;
		selectedInstallmentId = window.ipay88PendingInstallmentSelection.installmentId;

		window.ipay88SelectedPaymentValue = selectedPaymentValue;
		window.ipay88SelectedInstallmentMonths = selectedInstallmentMonths;
		window.ipay88SelectedInstallmentId = selectedInstallmentId;

		/* logging removed */
	} else {
		const selectedPaymentRadio = $('.ipay88-payment-option input[type="radio"]:checked').first();
		selectedPaymentValue = selectedPaymentRadio.val() || window.ipay88SelectedPaymentValue;

		const selectedInstallmentRadio = $('.ipay88-installment-list input[type="radio"]:checked').first();
		selectedInstallmentId = selectedInstallmentRadio.attr('id') || window.ipay88SelectedInstallmentId;
		selectedInstallmentMonths = selectedInstallmentRadio.val() || window.ipay88SelectedInstallmentMonths;
	}

	/* logging removed */

	initIPay88PaymentOptions();

	if (!selectedInstallmentId && !selectedInstallmentMonths) {
		window.ipay88RestoringSelections = false;
		window.ipay88PendingInstallmentSelection = null;
	}

	setTimeout(function () {
		// Restore collapsed sections
		window.ipay88CollapsedSections.forEach(function (sectionText) {
			$('.ipay88-section-title').each(function () {
				if ($(this).text().trim() === sectionText) {
					const $title = $(this);
					const $grid = $title.next('.ipay88-payment-grid');
					$title.addClass('collapsed');
					$grid.addClass('collapsed');
					$title.attr('aria-expanded', 'true');
				}
			});
		});

		if (window.ipay88RestoringSelections && selectedPaymentValue) {
			const $paymentRadio = $('input[name="ipay88_payment_type"][value="' + selectedPaymentValue + '"]').first();
			if ($paymentRadio.length) {
				$paymentRadio.prop('checked', true);
				const $paymentOption = $paymentRadio.closest('.ipay88-payment-option');
				$paymentOption.addClass('selected');

				if ($paymentRadio.data('payment-type') === 'bnpl' && !$paymentOption.hasClass('bnpl-option')) {
					$paymentOption.addClass('bnpl-option');
				}

				setTimeout(function () {
					// Remove duplicates again
					$(`[id='installment-options-${selectedPaymentValue}']`).not(':first').remove();

					const $installmentContainer = $(`#installment-options-${selectedPaymentValue}`).first();
					if ($installmentContainer.length) {
						$installmentContainer.show();

						if (selectedInstallmentId && selectedInstallmentMonths &&
							String(selectedInstallmentId).indexOf('installment_' + selectedPaymentValue + '_') === 0) {

							let $installmentRadio = $('#' + selectedInstallmentId).first();

							if (!$installmentRadio.length) {
								$installmentRadio = $(`#installment-options-${selectedPaymentValue} input[name="ipay88_payment_plan${selectedPaymentValue}"][value="${selectedInstallmentMonths}"]`).first();
							}

							if ($installmentRadio.length) {
								$installmentRadio.prop('checked', true);
								$installmentRadio.closest('.ipay88-installment-option').addClass('selected');
							}
						}
					}

					// Clear ALL flags
					window.ipay88RestoringSelections = false;
					window.ipay88PendingInstallmentSelection = null;
					window.ipay88AjaxInProgress = false;
				}, 100);
			}
		} else if (selectedPaymentValue) {
			const $paymentRadio = $('input[name="ipay88_payment_type"][value="' + selectedPaymentValue + '"]').first();
			if ($paymentRadio.length) {
				const $paymentOption = $paymentRadio.closest('.ipay88-payment-option');
				$paymentOption.addClass('selected');

				if ($paymentRadio.data('payment-type') === 'bnpl') {
					if (!$paymentOption.hasClass('bnpl-option')) {
						$paymentOption.addClass('bnpl-option');
					}

					setTimeout(function () {
						$(`[id='installment-options-${selectedPaymentValue}']`).not(':first').remove();

						const $installmentContainer = $(`#installment-options-${selectedPaymentValue}`).first();
						if ($installmentContainer.length) {
							$installmentContainer.show();

							if (selectedInstallmentId && selectedInstallmentMonths &&
								String(selectedInstallmentId).indexOf('installment_' + selectedPaymentValue + '_') === 0) {

								let $installmentRadio = $('#' + selectedInstallmentId).first();

								if (!$installmentRadio.length) {
									$installmentRadio = $(`#installment-options-${selectedPaymentValue} input[name="ipay88_payment_plan${selectedPaymentValue}"][value="${selectedInstallmentMonths}"]`).first();
								}

								if ($installmentRadio.length) {
									$installmentRadio.prop('checked', true);
									$installmentRadio.closest('.ipay88-installment-option').addClass('selected');
								}
							}
						}

						window.ipay88RestoringSelections = false;
						window.ipay88PendingInstallmentSelection = null;
						window.ipay88AjaxInProgress = false;
					}, 100);
				} else {
					// Non-BNPL: ensure admin fee is cleared to 0
					$('.ipay88-installment-options').hide();
					updateAdminFee(0, selectedPaymentValue, 0);
				}
			}
		} else {
			$('.ipay88-installment-options').hide();
		}
	}, 50);
});

// Handle payment method re-render
jQuery(document).on('payment_method_selected', function () {
	setTimeout(function () {
		initIPay88PaymentOptions();
		jQuery('.ipay88-installment-options').hide();
		window.ipay88SelectedInstallmentMonths = null;
		window.ipay88SelectedInstallmentId = null;
		window.ipay88RestoringSelections = false;
		window.ipay88PendingInstallmentSelection = null;
		window.ipay88LastPlanKey = null;
		window.ipay88AjaxInProgress = false;
	}, 100);
});

// Handle update_order_review
jQuery(document.body).on('updated_wc_div', function () {
	const $ = jQuery;

	// Clean duplicate IDs only within IPay88 container
	const $scope = $('.ipay88-payment-container').first();
	if ($scope.length) {
		const seenIds = {};
		$scope.find('[id]').each(function () {
			const id = this.id;
			if (seenIds[id]) {
				$(this).remove();
			} else {
				seenIds[id] = true;
			}
		});
	}

	let selectedPaymentValue, selectedInstallmentId, selectedInstallmentMonths;

	if (window.ipay88PendingInstallmentSelection) {
		selectedPaymentValue = window.ipay88PendingInstallmentSelection.paymentValue;
		selectedInstallmentMonths = window.ipay88PendingInstallmentSelection.months;
		selectedInstallmentId = window.ipay88PendingInstallmentSelection.installmentId;

		window.ipay88SelectedPaymentValue = selectedPaymentValue;
		window.ipay88SelectedInstallmentMonths = selectedInstallmentMonths;
		window.ipay88SelectedInstallmentId = selectedInstallmentId;
	} else {
		const selectedPaymentRadio = $('.ipay88-payment-option input[type="radio"]:checked').first();
		selectedPaymentValue = selectedPaymentRadio.val() || window.ipay88SelectedPaymentValue;

		const selectedInstallmentRadio = $('.ipay88-installment-list input[type="radio"]:checked').first();
		selectedInstallmentId = selectedInstallmentRadio.attr('id') || window.ipay88SelectedInstallmentId;
		selectedInstallmentMonths = selectedInstallmentRadio.val() || window.ipay88SelectedInstallmentMonths;
	}

	initIPay88PaymentOptions();

	if (!selectedInstallmentId && !selectedInstallmentMonths) {
		window.ipay88RestoringSelections = false;
		window.ipay88PendingInstallmentSelection = null;
	}

	setTimeout(function () {
		window.ipay88CollapsedSections.forEach(function (sectionText) {
			$('.ipay88-section-title').each(function () {
				if ($(this).text().trim() === sectionText) {
					const $title = $(this);
					const $grid = $title.next('.ipay88-payment-grid');
					$title.addClass('collapsed');
					$grid.addClass('collapsed');
					$title.attr('aria-expanded', 'true');
				}
			});
		});

		if (selectedPaymentValue) {
			const $paymentRadio = $('input[name="ipay88_payment_type"][value="' + selectedPaymentValue + '"]').first();
			if ($paymentRadio.length) {
				$paymentRadio.prop('checked', true);

				const $paymentOption = $paymentRadio.closest('.ipay88-payment-option');
				const isBNPL = $paymentRadio.data('payment-type') === 'bnpl';

				$paymentOption.addClass('selected');
				if (isBNPL && !$paymentOption.hasClass('bnpl-option')) {
					$paymentOption.addClass('bnpl-option');
				}

				// Do not trigger change here; init already rebuilt options for checked radios

				setTimeout(function () {
					$paymentOption.addClass('selected');
					if (isBNPL && !$paymentOption.hasClass('bnpl-option')) {
						$paymentOption.addClass('bnpl-option');
					}

					if (isBNPL) {
						$(`[id='installment-options-${selectedPaymentValue}']`).not(':first').remove();

						const $installmentContainer = $(`#installment-options-${selectedPaymentValue}`).first();
						if ($installmentContainer.length) {
							$installmentContainer.show();
						}
					} else {
						// Non-BNPL restore: hide installment containers and clear admin fee
						$('.ipay88-installment-options').hide();
						updateAdminFee(0, selectedPaymentValue, 0);
					}

					if (selectedInstallmentId && selectedInstallmentMonths &&
						String(selectedInstallmentId).indexOf('installment_' + selectedPaymentValue + '_') === 0) {

						let $installmentRadio = $('#' + selectedInstallmentId).first();

						if (!$installmentRadio.length) {
							$installmentRadio = $(`#installment-options-${selectedPaymentValue} input[name="ipay88_payment_plan${selectedPaymentValue}"][value="${selectedInstallmentMonths}"]`).first();
						}

						if ($installmentRadio.length) {
							$installmentRadio.prop('checked', true);
							$installmentRadio.closest('.ipay88-installment-option').addClass('selected');
						}
					}

					window.ipay88RestoringSelections = false;
					window.ipay88PendingInstallmentSelection = null;
					window.ipay88AjaxInProgress = false;
				}, 100);
			}
		} else {
			$('.ipay88-installment-options').hide();
		}
	}, 50);
});

// Update total
jQuery(function ($) {
	$(document.body).on('updated_checkout', function () {
		let total = $('.order-total .woocommerce-Price-amount bdi').text();
		let numericTotal = parseFloat(total.replace(/[^0-9.]/g, '')) || 0;
		window.shBnplData = window.shBnplData || {};
		window.shBnplData.total = numericTotal;
	});
});

jQuery(function ($) {
	'use strict';

	// Check if checkout form exists
	if (!$('form.checkout').length) {
		return;
	}

	console.log('iPay88: Script loaded');

	// Handle the checkout process
	$('form.checkout').on('checkout_place_order_ipay88', function () {
		console.log('iPay88: Place order triggered');
		return true; // Allow the order to be placed
	});

	// Listen for AJAX complete
	$(document).ajaxComplete(function (event, xhr, settings) {
		// Check if this is the checkout AJAX request
		if (settings.url && settings.url.indexOf('wc-ajax=checkout') > -1) {
			console.log('iPay88: Checkout AJAX complete');

			try {
				var response = JSON.parse(xhr.responseText);
				console.log('iPay88: Response:', response);

				if (response.result === 'success' && response.ipay88_form_data && response.ipay88_form_url) {
					console.log('iPay88: Submitting form to iPay88');

					// Block the UI
					$.blockUI({
						message: 'Thank you for your order. We are now redirecting you to iPay88 to make payment.',
						overlayCSS: {
							background: '#fff',
							opacity: 0.6
						},
						css: {
							padding: 20,
							textAlign: 'center',
							color: '#555',
							border: '3px solid #aaa',
							backgroundColor: '#fff',
							cursor: 'wait',
							lineHeight: '32px',
							zIndex: 9999
						}
					});

					// Create and submit the form
					var $form = $('<form>', {
						method: 'POST',
						action: response.ipay88_form_url,
						target: '_top'
					});

					// Add all hidden fields
					$.each(response.ipay88_form_data, function (name, value) {
						$form.append($('<input>', {
							type: 'hidden',
							name: name,
							value: value
						}));
					});

					console.log('iPay88: Form created, submitting...');

					// Append to body and submit
					$('body').append($form);

					// Small delay to ensure form is in DOM
					setTimeout(function () {
						$form.submit();
					}, 100);
				}
			} catch (e) {
				console.error('iPay88: Error parsing response', e);
			}
		}
	});
});

jQuery(function ($) {
	// Define a function to hide payment plans based on eligibility
	function updatePaymentPlansVisibility() {
		const paymentPlans = shBnplData.paymentPlans || [];
		const total = shBnplData.total || 0;
		let anyEligiblePlans = false; // Flag to track if there are any eligible plans

		// loop through each BNPL payment option wrapper
		$('.ipay88-payment-option-wrapper').each(function () {
			const $wrapper = $(this);
			const paymentValue = $wrapper.find('input[name="ipay88_payment_type"]').val();

			// find plans for this payment type
			const eligiblePlans = paymentPlans
				.filter(p => String(p.ipay88_id) === String(paymentValue))
				.filter(p => total >= (p.min_amount));

			if (eligiblePlans.length === 0) {
				// hide the entire wrapper if no eligible plans
				$wrapper.hide();
			} else {
				// Show the wrapper if there are eligible plans
				$wrapper.show();
				anyEligiblePlans = true; // At least one plan is available
			}
		});

		// If no eligible plans are available, hide the .bnpl-section
		if (anyEligiblePlans === false) {
			$('.bnpl-section').hide();
		} else {
			$('.bnpl-section').show();
		}
	}

	// Run the function on page load
	updatePaymentPlansVisibility();

	// Reapply the visibility check after WooCommerce AJAX updates (i.e., after checkout updates)
	$(document.body).on('updated_checkout', function () {
		updatePaymentPlansVisibility();
	});
});
