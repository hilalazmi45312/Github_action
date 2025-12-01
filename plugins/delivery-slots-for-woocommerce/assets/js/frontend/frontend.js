/* global dey_frontend_params */

jQuery(function ($) {
	'use strict';

	var DEY_Frontend = {
		shipping_method: dey_frontend_params.selected_shipping_method,
		billing_country: dey_frontend_params.selected_country,
		updated_order_scheduler_session: false,

		init: function () {
			// Show variation data.
			$(document).on('show_variation', this.onFoundVariation);
			$(document).on('hide_variation', this.onResetVariation);

			// Add order tip.
			$(document).on('click', '.dey-order-tip-cart-wrapper .dey-order-tip-predefined-button', this.add_cart_order_tip);
			$(document).on('click', '.dey-order-tip-checkout-wrapper .dey-order-tip-predefined-button', this.add_checkout_order_tip);

			// Add custom order tip.
			$(document).on('click', '.dey-order-tip-cart-wrapper .dey-order-tip-custom-amount-button', this.add_cart_custom_order_tip);
			$(document).on('click', '.dey-order-tip-checkout-wrapper .dey-order-tip-custom-amount-button', this.add_checkout_custom_order_tip);

			// Remove order tip.
			$(document).on('click', '.dey-cart-remove-order-tip', this.remove_cart_order_tip);
			$(document).on('click', '.dey-checkout-remove-order-tip', this.remove_checkout_order_tip);

			// Handle order scheduler type.
			$(document).on('change', '.dey-order-scheduler-type', this.toggle_order_scheduler_type);
			$(document).on('dey_init_order_scheduler_type', this.initialize_order_scheduler_type).trigger('dey_init_order_scheduler_type');
			$(document).on('updated_checkout', this.order_scheduler_fields);

			// Handle product order scheduler type.
			$(document).on('change', '.dey-product-scheduler-type', this.toggle_product_scheduler_type);
			$(document).on('dey_init_product_scheduler_type', this.handle_product_scheduler_type);
			$(document).trigger('dey_init_product_scheduler_type', 'input[name="dey_product_scheduler_type"]:checked');
		},

		toggle_order_scheduler_type: function (event) {
			event.preventDefault();
			DEY_Frontend.handle_order_scheduler_type($(event.currentTarget));
		},

		/**
		 * Initialize the order scheduler type.
		 * 
		 * @since 3.7.0
		 * @param {object} event
		 */
		initialize_order_scheduler_type: function (event) {
			DEY_Frontend.toggle_order_scheduler_fields('input[name="dey_order_scheduler_type"]:checked');
		},

		/**
		 * Toggle product scheduler type.
		 * 
		 * @since 3.5.0
		 * @param {event} event 
		 */
		toggle_product_scheduler_type: function (event) {
			event.preventDefault();
			$(document).trigger('dey_init_product_scheduler_type', $(event.currentTarget));
		},

		order_scheduler_fields: function (event) {
			
			var selected_shipping_method = $('input[name^="shipping_method"]:checked').val();
			var selected_country = $('#billing_country').val();
			if ( selected_shipping_method === DEY_Frontend.shipping_method && selected_country === DEY_Frontend.billing_country ) {
				return; 
			}

			var wrapper = $('.dey-order-scheduler-fields-wrapper'),
				data = ({
					action: 'dey_get_order_scheduler_fields',
					dey_security: dey_frontend_params.datepicker_nonce,
				});

			DEY_Frontend.block(wrapper);
			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$('.dey-order-scheduler-fields-wrapper').replaceWith(res.data.content);
					$(document).trigger('dey_init_order_scheduler_type');
					$(document.body).trigger('dey_init_order_delivery');
					$(document.body).trigger('dey_init_order_local_pickup');
				} else {
					alert(res.data.error);
				}
				DEY_Frontend.unblock(wrapper);
			});

			DEY_Frontend.shipping_method = selected_shipping_method;
			DEY_Frontend.billing_country = selected_country;
		},

		/**
		 * Handles the order scheduler type.
		 * 
		 * @since 4.0.0
		 * @param {object} scheduler_type Scheduler type. 
		 */
		handle_order_scheduler_type: function (scheduler_type) {
			if ('undefined' === typeof $(scheduler_type).val()) {
				return;
			}

			DEY_Frontend.block($('.dey-order-scheduler-fields-wrapper'));
			var data = ({
				action: 'dey_set_order_scheduler_type_session',
				order_scheduler_type: $(scheduler_type).val(),
				dey_security: dey_frontend_params.order_scheduler_nonce,
			});

			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$(document.body).trigger('update_checkout');
				}
			});

			DEY_Frontend.unblock($('.dey-order-scheduler-fields-wrapper'));

			DEY_Frontend.toggle_order_scheduler_fields(scheduler_type);
		},

		/**
		 * Toggle the order scheduler fields.
		 * 
		 * @since 4.0.0
		 * @param {object} scheduler_type Scheduler type.
		 */
		toggle_order_scheduler_fields: function (scheduler_type) {
			if ('undefined' === typeof $(scheduler_type).val()) {
				return;
			}

			var order_scheduler_wrapper = $('.dey-order-scheduler-fields-wrapper');
			order_scheduler_wrapper.find('.dey-order-scheduler-fields').hide();
			if ('order-local-pickup' === $(scheduler_type).val()) {
				order_scheduler_wrapper.find('.dey-order-expected-delivery-info-wrapper').hide();
				order_scheduler_wrapper.find('.dey-order-local-pickup-slots-wrapper').show();
			} else {
				order_scheduler_wrapper.find('.dey-order-delivery-slots-fields-wrapper').show();
				order_scheduler_wrapper.find('.dey-order-expected-delivery-info-wrapper').show();
			}
		},

		onFoundVariation: function (event, variation, purchasable) {
			DEY_Frontend.onResetVariation();

			if (variation.dey_delivery_slots) {
				$('.variations_form').find('.woocommerce-variation-add-to-cart').before(variation.dey_delivery_slots);
				if ($('.dey-product-delivery-date-field').length) {
					$('.variations_form').find('.dey-product-delivery-date-field').dey_product_delivery_datepicker();
				}

				if ($('.dey-product-pickup-date-field').length) {
					$('.variations_form').find('.dey-product-pickup-date-field').dey_product_pickup_datepicker();
				}

				$(document).trigger('dey_init_product_scheduler_type', 'input[name="dey_product_scheduler_type"]:checked');
			}
		},

		onResetVariation: function (event) {
			if ($('.variations_form').find('.dey-product-scheduler-fields-wrapper').length) {
				$('.variations_form').find('.dey-product-scheduler-fields-wrapper').remove();
			}
		},

		add_cart_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				wrapper = ($this).closest('.dey-order-tip-wrapper');

			if ('custom' === $this.data('amount')) {
				wrapper.find('.dey-order-tip-custom-amount-actions').show();
				$($this).addClass('dey-active');
			} else {
				DEY_Frontend.block(wrapper);
				var data = ({
					action: 'dey_add_order_tip',
					amount: $this.data('amount'),
					dey_security: dey_frontend_params.order_tip_nonce
				});

				$.post(dey_frontend_params.ajax_url, data, function (res) {
					if (true === res.success) {
						updateCart(res.data.message);
						wrapper.find('.dey-order-tip-predefined-button').removeClass('dey-active');
						$($this).addClass('dey-active');
						wrapper.find('.dey-order-tip-custom-amount-actions').hide();
					} else {
						updateCart(res.data.error, 'error');
					}

					DEY_Frontend.unblock(wrapper);
				});
			}
		},

		add_checkout_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
					wrapper = ($this).closest('.dey-order-tip-wrapper');

			if ('custom' === $this.data('amount')) {
				wrapper.find('.dey-order-tip-custom-amount-actions').show();
				$($this).addClass('dey-active');
			} else {
				DEY_Frontend.block(wrapper);
				var data = ({
					action: 'dey_add_order_tip',
					amount: $this.data('amount'),
					dey_security: dey_frontend_params.order_tip_nonce
				});

				$.post(dey_frontend_params.ajax_url, data, function (res) {
					if (true === res.success) {
						wrapper.find('.dey-order-tip-predefined-button').removeClass('dey-active');
						$($this).addClass('dey-active');
						wrapper.find('.dey-order-tip-custom-amount-actions').hide();
						updateCart(res.data.message, 'success', 'checkout');
					} else {
						updateCart(res.data.error, 'error', 'checkout');
					}

					DEY_Frontend.unblock(wrapper);
				});
			}
		},

		add_cart_custom_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
					wrapper = ($this).closest('.dey-order-tip-wrapper'),
					amount = wrapper.find('.dey-order-tip-custom-amount').val();

			DEY_Frontend.block(wrapper);
			var data = ({
				action: 'dey_add_custom_order_tip',
				amount: amount,
				dey_security: dey_frontend_params.order_tip_nonce
			});

			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					if ('3' === dey_frontend_params.order_tip_display_type) {
						wrapper.hide();
					} else {
						wrapper.find('.dey-order-tip-predefined-button').removeClass('dey-active');
						wrapper.find('.dey-order-tip-custom-button').addClass('dey-active');
						wrapper.find('.dey-order-tip-custom-amount-actions').hide();
					}

					updateCart(res.data.message);
				} else {
					updateCart(res.data.error, 'error');
				}

				DEY_Frontend.unblock(wrapper);
			});

		},

		add_checkout_custom_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
					wrapper = ($this).closest('.dey-order-tip-wrapper'),
					amount = wrapper.find('.dey-order-tip-custom-amount').val();

			DEY_Frontend.block(wrapper);
			var data = ({
				action: 'dey_add_custom_order_tip',
				amount: amount,
				dey_security: dey_frontend_params.order_tip_nonce,
			});

			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					updateCart(res.data.message, 'success', 'checkout');

					if ('3' === dey_frontend_params.order_tip_display_type) {
						wrapper.hide();
					} else {
						wrapper.find('.dey-order-tip-predefined-button').removeClass('dey-active');
						wrapper.find('.dey-order-tip-custom-button').addClass('dey-active');
						wrapper.find('.dey-order-tip-custom-amount-actions').hide();
					}

				} else {
					updateCart(res.data.error, 'error', 'checkout');
				}

				DEY_Frontend.unblock(wrapper);
			});
		},

		remove_cart_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);

			DEY_Frontend.block($this);
			var data = ({
				action: 'dey_remove_order_tip',
				dey_security: dey_frontend_params.order_tip_nonce,
			});

			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					$('.dey-order-tip-predefined-button').removeClass('dey-active');

					updateCart(res.data.message);
				} else {
					alert(res.data.error);
				}

				DEY_Frontend.unblock($this);
			});

		},

		remove_checkout_order_tip: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget);

			DEY_Frontend.block($this);
			var data = ({
				action: 'dey_remove_order_tip',
				dey_security: dey_frontend_params.order_tip_nonce
			});

			$.post(dey_frontend_params.ajax_url, data, function (res) {
				if (true === res.success) {
					if ('3' === dey_frontend_params.order_tip_display_type) {
						$('.dey-order-tip-wrapper').show();
					} else {
						$('.dey-order-tip-predefined-button').removeClass('dey-active');
					}

					updateCart(res.data.message, 'success', 'checkout');
				} else {
					alert(res.data.error);
				}

				DEY_Frontend.unblock($this);
			});
		},

		/**
		 * Handle product scheduler type.
		 * 
		 * @since 3.5.0
		 * @param {object} $this
		 * @param {object} $product_scheduler_type 
		 */
		handle_product_scheduler_type: function ($this, $product_scheduler_type) {
			var $product_scheduler_wrapper = $('.dey-product-scheduler-fields-wrapper');
			if (!$product_scheduler_wrapper.data('is_product_scheduler_type')) {
				return;
			}

			$product_scheduler_wrapper.find('.dey-product-scheduler-fields').hide();
			$product_scheduler_wrapper.find('.dey-product-expected-delivery-info-wrapper').hide();
			if ('product-local-pickup' === $($product_scheduler_type).val()) {
				$product_scheduler_wrapper.find('.dey-product-local-pickup-slots-wrapper').show();
			} else {
				$product_scheduler_wrapper.find('.dey-product-delivery-slots-wrapper').show();
				$product_scheduler_wrapper.find('.dey-product-expected-delivery-info-wrapper').show();
			}
		},

		block: function (id) {
			if (!DEY_Frontend.is_blocked(id)) {
				$(id).addClass('processing').block({
					message: null,
					overlayCSS: {background: '#fff', opacity: 0.7}
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

	/**
	 * Is block cart/checkout.
	 * 
	 * @since 3.7.0
	 * @returns {boolean}
	 */
	const isBlockCart = () => {
		return dey_frontend_params.is_block_cart || dey_frontend_params.is_block_checkout;
	};

	/**
	 * Update the cart after any action done.
	 * 
	 * @since 3.7.0
	 * @param {string} message
	 * @param {string} type
	 * @param {string} page
	 * @returns {undefined}
	 */
	const updateCart = (message = '', type = 'success', page = 'cart') => {
		if (isBlockCart()) {
			$(document.body).trigger('dey_update_cart_block', [message, type]);
		} else if ('checkout' === page) {
			$(document.body).trigger('update_checkout');
		} else {
			$(document.body).trigger('wc_update_cart');
		}
	};

	DEY_Frontend.init();
});
