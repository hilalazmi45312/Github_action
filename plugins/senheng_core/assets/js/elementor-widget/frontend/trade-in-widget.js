/**
 * Senheng Trade In Widget Frontend JavaScript
 */
(function ($) {
	"use strict";

	class TradeInWidget {
		constructor() {
			this.init();
		}

		init() {
			this.bindEvents();
			this.handleFormIntegration();
		}

		bindEvents() {
			// Handle text swatch clicks for trade-in
			$(document).on(
				"click",
				".sh-trade-in-wrapper .wd-swatch",
				this.handleSwatchClick.bind(this)
			);

			// Handle variation swatch clicks to clear errors
			$(document).on(
				"click",
				".variations .wd-swatch",
				this.handleVariationSwatchClick.bind(this)
			);

			// Handle variation select changes to clear errors
			$(document).on(
				"change",
				".variations select",
				this.handleVariationSelectChange.bind(this)
			);

			// Handle deposit option change (fallback for legacy)
			$(document).on(
				"change",
				'.awcdp-deposits-wrapper input[name="awcdp_deposit_option"]',
				this.handleDepositChange.bind(this)
			);

			// Note: Form submission validation is now handled by the WoodMart override handler
			// $(document).on('submit', 'form.cart', this.validateTradeInField.bind(this));

			// Handle WooCommerce variation changes
			$(document).on("found_variation", this.handleVariationChange.bind(this));

			// Handle trade-in restriction logic
			$(document).on(
				"sh_trade_in_changed",
				this.handleTradeInRestriction.bind(this)
			);

			// Listen for quantity selector updates to ensure synchronization
			$(document).on("quantity_selector_updated", (event, data) => {
				// Ensure trade-in restrictions are still applied after quantity updates
				const $tradeInSwatches = $('.wd-swatches-product[data-id*="trade_in"]');
				if ($tradeInSwatches.length) {
					const tradeInSelected =
						$tradeInSwatches.find(".wd-swatch.selected").length > 0;
					if (tradeInSelected) {
						setTimeout(() => {
							this.handleTradeInRestriction();
						}, 0);
					}
				}
			});

			// Listen for quantity selector resets
			$(document).on("quantity_selector_reset", (event, data) => {
				// Re-apply trade-in restrictions if needed
				const $tradeInSwatches = $('.wd-swatches-product[data-id*="trade_in"]');
				if ($tradeInSwatches.length) {
					const tradeInSelected =
						$tradeInSwatches.find(".wd-swatch.selected").length > 0;
					if (tradeInSelected) {
						setTimeout(() => {
							this.handleTradeInRestriction();
						}, 0);
					}
				}
			});

			// Handle page load and setup validation
			$(document).ready(() => {
				this.onPageLoad();
				this.initializeTradeInRestrictions();
			});
		}

		handleSwatchClick(event) {
			event.preventDefault();

			const $swatch = $(event.currentTarget);
			const $swatchContainer = $swatch.closest(".wd-swatches-product");
			const value = $swatch.data("value");
			const swatchId = $swatchContainer.data("id");

			// Don't proceed if swatch is disabled
			if ($swatch.hasClass("disabled")) {
				return;
			}

			// Remove selected state from all swatches in this container
			$swatchContainer.find(".wd-swatch").removeClass("selected");

			// Add selected state to clicked swatch
			$swatch.addClass("selected");

			// Update the hidden select element
			const $hiddenSelect = $swatchContainer
				.closest(".sh-value")
				.find("select");
			if ($hiddenSelect.length) {
				$hiddenSelect.val(value).trigger("change");
			}

			// Remove any error states
			$swatchContainer.removeClass("error");
			$swatchContainer
				.closest(".sh-trade-in-wrapper")
				.find(".sh-error-message")
				.remove();

			// Determine if this is trade-in or payment option
			const isTradeIn = swatchId.includes("trade_in");
			const isPayment = swatchId.includes("awcdp_deposit_option");

			// Get product ID from the select element or container
			const productId =
				$hiddenSelect.data("product-id") ||
				$swatchContainer.closest("[data-product-id]").data("product-id");

			// Update form data with hidden inputs
			this.updateFormData();

			// Trigger appropriate custom event
			if (isTradeIn) {
				$(document).trigger("sh_trade_in_changed", {
					value: value,
					element: $hiddenSelect,
					productId: productId,
				});
			} else if (isPayment) {
				$(document).trigger("sh_payment_option_changed", {
					value: value,
					element: $hiddenSelect,
					productId: productId,
				});
			}

			// Debug logging removed
		}

		handleDepositChange(event) {
			const $radio = $(event.target);
			const value = $radio.val();
			const $container = $radio.closest(".awcdp-deposits-wrapper");

			// Handle deposit description visibility
			if (value === "yes") {
				$container.find(".awcdp-deposits-description").slideDown(200);
			} else {
				$container.find(".awcdp-deposits-description").slideUp(200);
			}

			// Trigger custom event for other plugins to listen
			$(document).trigger("sh_deposit_option_changed", {
				value: value,
				element: $radio,
				container: $container,
			});

			// Debug logging removed
		}

		handleVariationSwatchClick(event) {
			// Clear error states when variation swatch is clicked
			const $swatch = $(event.currentTarget);
			const $swatchContainer = $swatch.closest(".wd-swatches-product");
			const $select = $swatchContainer.siblings("select");

			// Remove error classes
			$swatchContainer.removeClass("error");
			$select.removeClass("error");
		}

		handleVariationSelectChange(event) {
			// Clear error states when variation select is changed
			const $select = $(event.target);
			const $swatchContainer = $select.siblings(".wd-swatches-product");

			// Remove error classes
			$select.removeClass("error");
			$swatchContainer.removeClass("error");
		}

		validateTradeInField(event) {
			// This method is deprecated - validation is now handled by the state manager
			// Always return true to prevent form submission blocking
			return true;
		}

		handleVariationChange(event, variation) {
			// Handle variation-specific trade-in logic if needed
			const $tradeInWrapper = $(".sh-trade-in-wrapper");

			if ($tradeInWrapper.length) {
				// You can add variation-specific logic here
				// For example, different trade-in options based on variation

				$(document).trigger("sh_trade_in_variation_changed", {
					variation: variation,
					wrapper: $tradeInWrapper,
				});
			}
		}

		/**
		 * Handle trade-in restriction logic
		 * When trade-in is "yes", force payment option to "deposit" only
		 */
		handleTradeInRestriction(event, data) {
			if (!data || !data.value) return;

			const productId = data.productId;
			const tradeInValue = data.value;

			// Find the payment option container for this product
			const $paymentContainer = $(
				`[data-id="awcdp_deposit_option_${productId}"]`
			);
			if (!$paymentContainer.length) return;

			if (tradeInValue === "yes") {
				this.restrictToDepositOnly($paymentContainer, productId);
			} else {
				this.allowAllPaymentOptions($paymentContainer, productId);
			}
		}

		/**
		 * Restrict payment options to deposit only
		 */
		restrictToDepositOnly($paymentContainer, productId) {
			// Disable the "Full Payment" swatch (data-value="no")
			const $fullPaymentSwatch = $paymentContainer.find(
				'.wd-swatch[data-value="no"]'
			);
			$fullPaymentSwatch.addClass("disabled").css({
				opacity: "0.5",
				"pointer-events": "none",
				cursor: "not-allowed",
			});

			// Force select the deposit option (data-value="yes")
			const $depositSwatch = $paymentContainer.find(
				'.wd-swatch[data-value="yes"]'
			);
			$depositSwatch.removeClass("disabled").css({
				opacity: "1",
				"pointer-events": "auto",
				cursor: "pointer",
			});

			// Auto-select deposit option if not already selected
			if (!$depositSwatch.hasClass("selected")) {
				$depositSwatch.trigger("click");
			}

			// Update the hidden select element
			const $hiddenSelect = $paymentContainer
				.closest(".sh-value")
				.find("select");
			if ($hiddenSelect.length) {
				$hiddenSelect.val("yes").trigger("change");
			}

			// Add visual indicator
			// this.addRestrictionNotice($paymentContainer, 'Trade-in selected: Only deposit payment available');
		}

		/**
		 * Allow all payment options
		 */
		allowAllPaymentOptions($paymentContainer, productId) {
			// Re-enable all swatches
			const $allSwatches = $paymentContainer.find(".wd-swatch");
			$allSwatches.removeClass("disabled").css({
				opacity: "1",
				"pointer-events": "auto",
				cursor: "pointer",
			});

			// Remove restriction notice
			this.removeRestrictionNotice($paymentContainer);
		}

		restrictToFullPaymentOnly($paymentContainer, productId) {
			const $depositSwatch = $paymentContainer.find(
				'.wd-swatch[data-value="yes"]'
			);
			$depositSwatch.addClass("disabled").css({
				opacity: "0.5",
				"pointer-events": "none",
				cursor: "not-allowed",
			});

			const $fullPaymentSwatch = $paymentContainer.find(
				'.wd-swatch[data-value="no"]'
			);
			$fullPaymentSwatch.removeClass("disabled").css({
				opacity: "1",
				"pointer-events": "auto",
				cursor: "pointer",
			});

			if (!$fullPaymentSwatch.hasClass("selected")) {
				$fullPaymentSwatch.trigger("click");
			}

			const $hiddenSelect = $paymentContainer
				.closest(".sh-value")
				.find("select");
			if ($hiddenSelect.length) {
				$hiddenSelect.val("no").trigger("change");
			}
		}

		/**
		 * Add restriction notice
		 */
		addRestrictionNotice($container, message) {
			// Remove existing notice first
			this.removeRestrictionNotice($container);

			// Add new notice
			const $notice = $(
				'<div class="sh-payment-restriction-notice" style="font-size: 12px; color: #666; margin-top: 5px; font-style: italic;">' +
					message +
					"</div>"
			);
			$container.closest(".sh-value").append($notice);
		}

		/**
		 * Remove restriction notice
		 */
		removeRestrictionNotice($container) {
			$container
				.closest(".sh-value")
				.find(".sh-payment-restriction-notice")
				.remove();
		}

		/**
		 * Initialize trade-in restrictions on page load
		 */
		initializeTradeInRestrictions() {
			// Check all existing trade-in values and apply restrictions
			$(".sh-trade-in-wrapper").each((index, wrapper) => {
				const $wrapper = $(wrapper);
				const productId = $wrapper.data("product-id");
				const $selectedSwatch = $wrapper.find(".wd-swatch.selected");

				if ($selectedSwatch.length) {
					const tradeInValue = $selectedSwatch.data("value");
					const $paymentContainer = $(
						`[data-id="awcdp_deposit_option_${productId}"]`
					);
					if ($paymentContainer.length) {
						if (tradeInValue === "yes") {
							this.restrictToDepositOnly($paymentContainer, productId);
						} else if (tradeInValue === "no") {
							this.allowAllPaymentOptions($paymentContainer, productId);
						}
					}
				}
			});
		}

		handleFormIntegration() {
			// Integrate with Extra Product Options plugin if present
			if (typeof window.epo_js !== "undefined") {
				this.integrateWithEPO();
			}

			// Integrate with WooCommerce variations if present
			if (typeof wc_add_to_cart_variation_params !== "undefined") {
				this.integrateWithVariations();
			}
		}

		integrateWithEPO() {
			// Integration with Extra Product Options plugin
			$(document).on("tm-epo-check-dpd", function () {
				$(".sh-trade-in-select").trigger("change");
			});
		}

		integrateWithVariations() {
			// Integration with WooCommerce variations
			$(".variations_form").on(
				"woocommerce_variation_select_change",
				function () {
					// Removed loading state for payment selection
					// $('.sh-trade-in-wrapper').addClass('loading');
					// setTimeout(function() {
					//     $('.sh-trade-in-wrapper').removeClass('loading');
					// }, 500);
				}
			);
		}

		onPageLoad() {
			// Initialize any necessary setup on page load
			const $tradeInWrappers = $(".sh-trade-in-wrapper");

			if ($tradeInWrappers.length) {
				$tradeInWrappers.addClass("loaded");

				// Set up accessibility attributes
				$tradeInWrappers.find(".sh-trade-in-select").each(function () {
					const $select = $(this);
					const $label = $select.siblings(".sh-trade-in-label");

					if ($label.length) {
						const labelId =
							"trade-in-label-" + Math.random().toString(36).substr(2, 9);
						$label.attr("id", labelId);
						$select.attr("aria-labelledby", labelId);
					}
				});

				// Initialize button states
				this.initializeButtonStates();

				// Initialize deposit sections
				this.initializeDepositSections();

				// Update form data with initial values
				this.updateFormData();
				$(document).trigger("sh_check_all_variations_oos");
			}
		}

		initializeButtonStates() {
			// Initialize trade-in button states
			$(".sh-trade-in-buttons").each(function () {
				const $container = $(this);
				const $hiddenInput = $container.siblings('input[type="hidden"]');
				const currentValue = $hiddenInput.val();

				$container.find(".sh-trade-in-btn").each(function () {
					const $btn = $(this);
					if ($btn.data("value") == currentValue) {
						$btn.addClass("active");
					} else {
						$btn.removeClass("active");
					}
				});
			});

			// Initialize payment button states
			$(".sh-payment-buttons").each(function () {
				const $container = $(this);
				const $hiddenInput = $container.siblings('input[type="hidden"]');
				const currentValue = $hiddenInput.val();

				$container.find(".sh-payment-btn").each(function () {
					const $btn = $(this);
					if ($btn.data("value") == currentValue) {
						$btn.addClass("active");
					} else {
						$btn.removeClass("active");
					}
				});
			});
		}

		initializeDepositSections() {
			const $depositWrappers = $(".awcdp-deposits-wrapper");

			$depositWrappers.each(function () {
				const $container = $(this);
				const $checkedRadio = $container.find(
					'input[name="awcdp_deposit_option"]:checked'
				);

				// Initialize description visibility based on default selection
				if ($checkedRadio.length && $checkedRadio.val() === "no") {
					$container.find(".awcdp-deposits-description").hide();
				}

				// Set up accessibility attributes for deposit options
				$container.find('input[name="awcdp_deposit_option"]').each(function () {
					const $radio = $(this);
					const $label = $radio.siblings(".awcdp-radio-label");

					if ($label.length) {
						const radioId = $radio.attr("id");
						if (radioId) {
							$label.attr("for", radioId);
						}
					}
				});
			});
		}

		validateTradeInBeforeSubmission($form) {
			// Debug logging removed

			// Ensure form data is updated with current swatch selections before validation
			this.updateFormData();

			// Check if this is a product with trade-in options by looking for trade-in swatches
			// Look both within the form and in the broader page context
			let $tradeInSwatches = $form.find(
				'.wd-swatches-product[data-id*="trade_in"]'
			);
			if (!$tradeInSwatches.length) {
				// If not found in form, look in the page (trade-in might be outside form)
				$tradeInSwatches = $('.wd-swatches-product[data-id*="trade_in"]');
			}

			// Debug logging removed

			if (!$tradeInSwatches.length) {
				// Debug logging removed
				return true;
			}

			// Debug logging removed

			let hasErrors = false;
			let errorMessages = [];

			// For variable products, check if variation is selected
			const $variationForm = $form.filter(".variations_form");

			if ($variationForm.length) {
				const variationId = $variationForm
					.find('input[name="variation_id"]')
					.val();
			}

			// Check trade-in selection via swatches (reuse the variable from above)
			// Debug logging removed

			let tradeInYesSelected = false;
			let tradeInSelected = false;

			if ($tradeInSwatches.length) {
				const $selectedSwatches = $tradeInSwatches.find(".wd-swatch.selected");

				tradeInSelected = $selectedSwatches.length > 0;

				if (tradeInSelected) {
					// Check if "Yes" is selected for trade-in
					$selectedSwatches.each(function () {
						const swatchValue =
							$(this).data("value") || $(this).text().trim().toLowerCase();
						if (
							swatchValue === "yes" ||
							swatchValue === "Yes" ||
							swatchValue === "YES"
						) {
							tradeInYesSelected = true;
						}
					});
					// Debug logging removed
				}
			}

			// Check payment option selection via swatches - ALWAYS check if payment options exist
			let $paymentSwatches = $form.find(
				'.wd-swatches-product[data-id*="awcdp_deposit_option"]'
			);

			// Also check broader page context if not found in form
			if ($paymentSwatches.length === 0) {
				$paymentSwatches = $(
					'.wd-swatches-product[data-id*="awcdp_deposit_option"]'
				);
			}

			// Debug logging removed

			let paymentSelected = false;
			if ($paymentSwatches.length) {
				const $selectedPaymentSwatches = $paymentSwatches.find(
					".wd-swatch.selected"
				);
				paymentSelected = $selectedPaymentSwatches.length > 0;
			}

			let alertMessage = "";

			if (!tradeInSelected && !paymentSelected) {
				// Both not selected → alert about trade-in selection
				alertMessage = "Please select a Trade In option.";
				hasErrors = true;
			} else if (
				tradeInSelected &&
				!paymentSelected &&
				$paymentSwatches.length > 0
			) {
				// Trade-in selected but payment not chosen → alert about payment option
				alertMessage = "Please select a Payment Option.";
				hasErrors = true;
			} else if (!tradeInSelected && paymentSelected) {
				// Payment selected but trade-in not chosen → alert about trade-in
				alertMessage = "Please select a Trade In option.";
				hasErrors = true;
			} else {
				// Debug logging removed
			}

			// Debug logging removed

			if (hasErrors) {
				return false;
			}

			// Debug logging removed
			return true;
		}

		// Update form data with hidden inputs for trade-in and deposit selections
		updateFormData() {
			const $forms = $("form.cart, form.variations_form");
			if ($forms.length === 0) return;

			// Remove existing trade-in and deposit hidden fields
			$forms
				.find('input[name="trade_in"], input[name="awcdp_deposit_option"]')
				.remove();

			// Get current trade-in selection
			const $tradeInSwatch = $(
				'.wd-swatches-product[data-id*="trade_in"] .wd-swatch.selected'
			);
			if ($tradeInSwatch.length) {
				const tradeInValue = $tradeInSwatch.data("value");
				if (tradeInValue) {
					$forms.append(
						'<input type="hidden" name="trade_in" value="' + tradeInValue + '">'
					);
				}
			}

			// Get current deposit selection
			const $depositSwatch = $(
				'.wd-swatches-product[data-id*="awcdp_deposit_option"] .wd-swatch.selected'
			);
			if ($depositSwatch.length) {
				const depositValue = $depositSwatch.data("value");
				if (depositValue) {
					$forms.append(
						'<input type="hidden" name="awcdp_deposit_option" value="' +
							depositValue +
							'">'
					);
				}
			}
		}

		// Utility method to get trade-in value
		static getTradeInValue() {
			const $selectedSwatch = $(
				'.wd-swatches-product[data-id*="trade_in"] .wd-swatch.selected'
			);
			return $selectedSwatch.length ? $selectedSwatch.data("value") : null;
		}

		// Utility method to set trade-in value
		static setTradeInValue(value) {
			const $swatchContainer = $('.wd-swatches-product[data-id*="trade_in"]');
			if ($swatchContainer.length) {
				// Remove all selected states
				$swatchContainer.find(".wd-swatch").removeClass("selected");

				// Select the swatch with matching value
				const $targetSwatch = $swatchContainer.find(
					'.wd-swatch[data-value="' + value + '"]'
				);
				if ($targetSwatch.length) {
					$targetSwatch.addClass("selected");

					// Update hidden select
					const $hiddenSelect = $swatchContainer
						.closest(".sh-value")
						.find("select");
					if ($hiddenSelect.length) {
						$hiddenSelect.val(value).trigger("change");
					}
				}
			}
		}
	}

	// Initialize the widget
	const tradeInWidget = new TradeInWidget();

	// Expose utility methods globally
	window.SenhengTradeIn = {
		getValue: TradeInWidget.getTradeInValue,
		setValue: TradeInWidget.setTradeInValue,
		instance: tradeInWidget,
	};

	// Elementor frontend compatibility
	const initElementorHandler = function () {
		elementorFrontend.hooks.addAction(
			"frontend/element_ready/widget",
			function ($scope) {
				if ($scope.find(".sh-trade-in-wrapper").length) {
					// Re-initialize for dynamically loaded content
					tradeInWidget.onPageLoad();
				}
			}
		);
	};

	if (typeof elementorFrontend !== "undefined" && elementorFrontend.hooks) {
		initElementorHandler();
	} else {
		$(window).on("elementor/frontend/init", initElementorHandler);
	}
})(jQuery);
