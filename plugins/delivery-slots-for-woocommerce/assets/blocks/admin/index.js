/**
 * Register custom inner blocks in the checkout.
 * 
 * @since 3.7.0
 */
(() => {
	'use strict';

	var reactElement = window.wp.element,
			blocks = window.wp.blocks,
			wc_blocks_checkout = window.wc.blocksCheckout,
			blockEditor = window.wp.blockEditor,
			wc_plugin_data = window.wc.wcSettings,
			wp_components = window.wp.components;

	const{
		order_tip_title_label,
		scheduler_block_preview_html,
		order_tip_block_preview_html
	} = wc_plugin_data.getSetting('dey-wc-blocks_data');

	/**
	 * Scheduler block class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the scheduler layout in the checkout block.
	 */
	const SchedulerBlock = {
		checkoutSchema: JSON.parse("{\"name\":\"woocommerce/dey-wc-checkout-scheduler-block\",\"icon\":\"schedule\",\"keywords\":[\"calculator\",\"delivery\",\"pickup\"],\"version\":\"1.0.0\",\"title\":\"Delivery and Pickup Scheduler\",\"description\":\"Shows the delivery and pickup scheduler layout.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-fields-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		getElement: function (e) {
			return reactElement.createElement(wp_components.Disabled, {}, reactElement.createElement(reactElement.Fragment, {}, SchedulerBlock.getFormField()));
		},
		getFormField: function () {
			return reactElement.createElement(reactElement.RawHTML, null, scheduler_block_preview_html);
		},
		edit: function (attributes) {
			return reactElement.createElement('div', blockEditor.useBlockProps(), SchedulerBlock.getElement());
		},
		save: function (e) {
			return reactElement.createElement('div', blockEditor.useBlockProps.save());
		}
	};

	/**
	 * Order Tip class.
	 * 
	 * @since 3.7.0
	 * @return {JSX.Element} A Wrapper used to display the order tip layout in the cart/checkout block.
	 */
	const OrderTipBlock = {
		cartSchema: JSON.parse("{\"name\":\"woocommerce/dey-wc-cart-order-tip-block\",\"icon\":\"calculator\",\"keywords\":[\"order\",\"tip\"],\"version\":\"1.0.0\",\"title\":\"Order Delivery Tip\",\"description\":\"Shows the order delivery tip layout in the cart page.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/cart-totals-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		checkoutSchema: JSON.parse("{\"name\":\"woocommerce/dey-wc-checkout-order-tip-block\",\"icon\":\"calculator\",\"keywords\":[\"order\",\"tip\"],\"version\":\"1.0.0\",\"title\":\"Order Delivery Tip\",\"description\":\"Shows the order delivery tip layout in the checkout page.\",\"category\":\"woocommerce\",\"supports\":{\"align\":false,\"html\":false,\"multiple\":false,\"reusable\":false},\"attributes\":{\"className\":{\"type\":\"string\",\"default\":\"\"},\"lock\":{\"type\":\"object\",\"default\":{\"remove\":true,\"move\":false}}},\"parent\":[\"woocommerce/checkout-totals-block\"],\"textdomain\":\"delivery-slots-for-woocommerce\",\"apiVersion\":2}"),
		getElement: function (e) {
			return reactElement.createElement(wp_components.Disabled, {}, reactElement.createElement(wc_blocks_checkout.TotalsWrapper, {}, OrderTipBlock.getFormField()));
		},
		getFormField: function () {
			return reactElement.createElement(wc_blocks_checkout.Panel, {className: 'dey-cart-block-order-tip-panel dey-block-order-tip-panel', initialOpen: true, title: order_tip_title_label},
					reactElement.createElement(reactElement.RawHTML, null, order_tip_block_preview_html));
		},
		edit: function (attributes) {
			return reactElement.createElement('div', blockEditor.useBlockProps(), OrderTipBlock.getElement());
		},
		save: function (e) {
			return reactElement.createElement('div', blockEditor.useBlockProps.save());
		}
	};

	// Register inner block of scheduler layout in the checkout block.  
	blocks.registerBlockType(SchedulerBlock.checkoutSchema.name, {
		...SchedulerBlock.checkoutSchema,
		edit: SchedulerBlock.edit,
		save: SchedulerBlock.save
	});

	// Register inner block of order tip layout in the checkout block.  
	blocks.registerBlockType(OrderTipBlock.cartSchema.name, {
		...OrderTipBlock.cartSchema,
		edit: OrderTipBlock.edit,
		save: OrderTipBlock.save
	});

	// Register inner block of order tip layout in the checkout block.  
	blocks.registerBlockType(OrderTipBlock.checkoutSchema.name, {
		...OrderTipBlock.checkoutSchema,
		edit: OrderTipBlock.edit,
		save: OrderTipBlock.save
	});
})();