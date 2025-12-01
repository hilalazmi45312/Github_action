/**
 * Cart / Checkout block.
 * 
 * @since 3.7.0
 */
(() => {
	'use strict';
	var reactElement = window.wp.element,
		wc_blocks_checkout = window.wc.blocksCheckout,
		wc_plugin_data = window.wc.wcSettings,
		wp_data = window.wp.data,
		datepicker_field_initialized = false; // Prevents interaction with the datepicker before initialization.
	const {
		createNotice
	} = wp_data.dispatch('core/notices');

	const {
		scheduler_rule_id,
		default_order_scheduler_type
	} = wc_plugin_data.getSetting('dey-wc-blocks_data');

	/**
	 * Delivery Pickup Scheduler block class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the delivery and pickup scheduler layout in the checkout page.
	 */
	const DeliveryPickupSchedulerBlock = {
		getElement: function (e) {
			if (undefined === e.extensions['dey-delivery-slots']) {
				return '';
			}

			// Return if the content doest not exists.
			if (!e.extensions['dey-delivery-slots']['delivery_pickup_scheduler_html']) {
				return '';
			}

			if (!datepicker_field_initialized) {
				jQuery(document.body).trigger('dey_init_order_delivery_fields');
				jQuery(document.body).trigger('dey_init_order_local_pickup_fields');
				jQuery(document).trigger('dey_init_order_scheduler_type');
			}
		  
			datepicker_field_initialized = true;
			return reactElement.createElement(reactElement.Fragment, null,
				reactElement.createElement(reactElement.RawHTML, null, e.extensions['dey-delivery-slots']['delivery_pickup_scheduler_html']));
		}
	};
	/**
	 * Order tip cart block class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the order tip layout in the cart page.
	 */
	const OrderTipCartBlock = {
		getElement: function (e) {
			if (!e.extensions['dey-delivery-slots']) {
				return '';
			}

			// Return if the content doest not exists.
			if (!e.extensions['dey-delivery-slots']['cart_order_tip_html']) {
				return '';
			}

			return reactElement.createElement(wc_blocks_checkout.TotalsWrapper, null,
				reactElement.createElement(wc_blocks_checkout.Panel, { className: 'dey-cart-block-order-tip-panel dey-block-order-tip-panel', initialOpen: true, title: e.extensions['dey-delivery-slots'].order_tip_title },
					reactElement.createElement(reactElement.RawHTML, null, e.extensions['dey-delivery-slots']['cart_order_tip_html'])));
		}
	};
	/**
	 * Order tip checkout block class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the order tip layout in the checkout page.
	 */
	const OrderTipCheckoutBlock = {
		getElement: function (e) {
			if (!e.extensions['dey-delivery-slots']) {
				return '';
			}

			// Return if the content doest not exists.
			if (!e.extensions['dey-delivery-slots']['checkout_order_tip_html']) {
				return '';
			}

			return reactElement.createElement(wc_blocks_checkout.TotalsWrapper, null,
				reactElement.createElement(wc_blocks_checkout.Panel, { className: 'dey-checkout-block-order-tip-panel dey-block-order-tip-panel', initialOpen: true, title: e.extensions['dey-delivery-slots'].order_tip_title },
					reactElement.createElement(reactElement.RawHTML, null, e.extensions['dey-delivery-slots']['checkout_order_tip_html'])));
		}
	};
	/**
	 * Delivery Slots fees class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the delivery slots fees in the cart/checkout pages.
	 */
	const DeliverySlotsFeesBlock = {
		getElement: function (e) {
			if (!e.extensions['dey-delivery-slots']) {
				return '';
			}

			// Return if the content doest not exists.
			if (!e.extensions['dey-delivery-slots']['fee_html']) {
				return '';
			}

			DeliverySlotsFeesBlock.hideWCDeliverySlotsFeesWrapper();
			return reactElement.createElement(reactElement.Fragment, null,
				reactElement.createElement(wc_blocks_checkout.ExperimentalDiscountsMeta, null,
					reactElement.createElement(reactElement.RawHTML, null, e.extensions['dey-delivery-slots']['fee_html'])));
		},
		hideWCDeliverySlotsFeesWrapper: function () {
			let cart_fee_wrapper = jQuery('.wp-block-woocommerce-cart-order-summary-fee-block'),
				checkout_fee_wrapper = jQuery('.wp-block-woocommerce-checkout-order-summary-fee-block');
			if (1 === cart_fee_wrapper.find('.wc-block-components-totals-fees').length && cart_fee_wrapper.find('.wc-block-components-totals-fees__dey_order_tip').length) {
				cart_fee_wrapper.hide();
			} else if (1 === checkout_fee_wrapper.find('.wc-block-components-totals-fees').length && checkout_fee_wrapper.find('.wc-block-components-totals-fees__dey_order_tip').length) {
				checkout_fee_wrapper.hide();
			} else {
				cart_fee_wrapper.show();
				checkout_fee_wrapper.show();
				cart_fee_wrapper.find('.wc-block-components-totals-fees__dey_order_tip').hide();
				checkout_fee_wrapper.find('.wc-block-components-totals-fees__dey_order_tip').hide();
			}
		}
	};
	/**
	 * Update notice.
	 * 
	 * @since 3.7.0
	 * @param {string} notice
	 * @param {string} type
	 */
	const updateNotice = (notice = '', type = 'success') => {
		if (!notice) {
			return;
		}

		createNotice(type, notice, {
			id: 'dey-action-notice',
			context: 'wc/cart',
			type: 'snackbar'
		});
	};

	/**
	 * Trigger the scheduler fields.
	 * 
	 * @since 4.3.0
	 */
	jQuery(document).on('change','.wc-block-components-radio-control__input', function () {
		setTimeout(function () {
			jQuery(document.body).trigger('dey_init_order_delivery_fields');
			jQuery(document.body).trigger('dey_init_order_local_pickup_fields');
			jQuery(document).trigger('dey_init_order_scheduler_type');
		}, 4000);

	});

	/**
	 * Trigger the scheduler fields.
	 * 
	 * @since 4.4.0
	 */
	jQuery(document).on('click','#shipping-method', function () {
		setTimeout(function () {
			jQuery(document.body).trigger('dey_init_order_delivery_fields');
			jQuery(document.body).trigger('dey_init_order_local_pickup_fields');
			jQuery(document).trigger('dey_init_order_scheduler_type');
		}, 4000);
		
	});
	
	/**
	 * Refresh the cart after any action done.
	 * 
	 * @since 3.7.0
	 */
	jQuery(document).on('dey_update_cart_block', function (e, message = '', type = 'success') {
		if ('error' === type) {
			updateNotice(message, type);
		} else {
			// Refresh the cart.
			wc_blocks_checkout.extensionCartUpdate({
				namespace: 'dey-delivery-slots',
				data: {
					action: 'refresh_cart'
				}
			}).then(() => {
				jQuery(document.body).trigger('dey_init_order_delivery_fields');
				jQuery(document.body).trigger('dey_init_order_local_pickup_fields');
				jQuery(document).trigger('dey_init_order_scheduler_type');

				DeliverySlotsFeesBlock.hideWCDeliverySlotsFeesWrapper();
			}).finally(() => {
				updateNotice(message, type);
			});
		}
	});

	// Register delivery and pickup scheduler inner block in the checkout block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/dey-wc-checkout-scheduler-block\",\"icon\":\"calculator\",\"keywords\":[\"scheduler\",\"delivery\",\"pickup\"],\"version\":\"1.0.0\",\"title\":\"Delivery and Pickup Scheduler\",\"description\":\"Shows the delivery and pickup scheduler layout in the checkout page.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		component: DeliveryPickupSchedulerBlock.getElement
	});
	// Register order tip inner block in the cart block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/dey-wc-cart-order-tip-block\",\"icon\":\"calculator\",\"keywords\":[\"order\",\"tip\"],\"version\":\"1.0.0\",\"title\":\"Order Delivery Tip\",\"description\":\"Shows the order delivery tip layout in the cart page.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		component: OrderTipCartBlock.getElement
	});
	// Register order tip inner block in the checkout block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/dey-wc-checkout-order-tip-block\",\"icon\":\"calculator\",\"keywords\":[\"order\",\"tip\"],\"version\":\"1.0.0\",\"title\":\"Order Delivery Tip\",\"description\":\"Shows the order delivery tip layout in the checkout page.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		component: OrderTipCheckoutBlock.getElement
	});
	// Register delivery slots fees inner block in the cart block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/dey-wc-cart-delivery-slots-fee-block\",\"icon\":\"calculator\",\"keywords\":[\"delivery\",\"slots\",\"fee\"],\"version\":\"1.0.0\",\"title\":\"Delivery Slots Fee\",\"description\":\"Shows the delivery slots fee layout.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-order-summary-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		component: DeliverySlotsFeesBlock.getElement
	});
	// Register delivery slots fees inner block in the checkout block.
	wc_blocks_checkout.registerCheckoutBlock({
		metadata: JSON.parse("{\"name\":\"woocommerce/dey-wc-checkout-delivery-slots-fee-block\",\"icon\":\"calculator\",\"keywords\":[\"delivery\",\"slots\",\"fee\"],\"version\":\"1.0.0\",\"title\":\"Delivery Slots Fee\",\"description\":\"Shows the delivery slots fee layout.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-order-summary-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		component: DeliverySlotsFeesBlock.getElement
	});

	//Handles the checkout fields.
	const getData = {
		'dey_scheduler_rule_id': scheduler_rule_id,
		'dey_order_scheduler_type': default_order_scheduler_type,
		'dey_delivery_date': false,
		'dey_local_pickup_date': false,
		'dey_pickup_location': false,
		'dey_order_delivery_date_time_slots': '',
		'dey_order_local_pickup_date_time_slots': ''
	};

	document.addEventListener('DOMContentLoaded', function () {
		// Update the checkout fields data when the fields are initialized.
		updateCheckoutFieldsData(getData);
		jQuery(document).on('change', "input[name='dey_order_scheduler_type']", function (e) {
			updateCheckoutFieldData('dey_order_scheduler_type', jQuery("input[name='dey_order_scheduler_type']:checked").val());
		});

		jQuery(document).on('change', '.dey-order-delivery-date', function (e) {
			updateCheckoutFieldData('dey_delivery_date', jQuery('.dey-order-delivery-date').val());
		});

		jQuery(document).on('change', '.dey-order-local-pickup-date', function (e) {
			updateCheckoutFieldData('dey_local_pickup_date', jQuery('.dey-order-local-pickup-date').val());
		});

		jQuery(document).on('change', '#dey_order_delivery_date_time_slots', function (e) {
			updateCheckoutFieldData('dey_order_delivery_date_time_slots', jQuery(this).val());
		});

		jQuery(document).on('change', '#dey_pickup_location', function (e) {
			updateCheckoutFieldData('dey_pickup_location', jQuery(this).val());
		});

		jQuery(document).on('change', '#dey_order_local_pickup_date_time_slots', function (e) {
			updateCheckoutFieldData('dey_order_local_pickup_date_time_slots', jQuery(this).val());
		});
	});

	/**
	 * Update the checkout fields data when the fields are initialized.
	 * 
	 * @since 3.8.0
	 * @param {array} data Checkout fields data.
	 */
	const updateCheckoutFieldsData = (data) => {
		jQuery.each(data, function (key, value) {
			updateCheckoutFieldData(key, value);
		});
	};

	/**
	 * Set scheduler field data in checkout fields.
	 * 
	 * @since 3.7.0
	 * @param {string} key
	 * @param {mixed} value
	 */
	const updateCheckoutFieldData = (key, value) => {
		getData[key] = value;
		window.wp.data.dispatch(window.wc.wcBlocksData.CHECKOUT_STORE_KEY).__internalSetExtensionData(
			'dey-delivery-slots',
			{ 'delivery_fields': getData },
			false);
	};
})();
