window.gtm_ecommerce_pro = {
	items: {
		cart: [],
		byId: {},
		byProductId: {},
		byAttributeId: [],
	},
	extensions: {},
    firedEvents: {},
	getItemsFromCart() {
		return this.items.cart;
	},
	getItemByProductId(productId) {
		if (undefined === this.items.byProductId[productId]) {
			console.warn('gtm_ecommerce_pro.getItemByProductId('+productId+') - undefined');

			return {};
		}

		return this.items.byProductId[productId];
	},
	getItemByItemId(itemId) {
		if (undefined === this.items.byId[itemId]) {
			console.warn('gtm_ecommerce_pro.getItemByItemId('+itemId+') - undefined');

			return {};
		}

		return this.items.byId[itemId];
	},
	getItemsByAttributes() {
		return this.items.byAttributeId;
	},
    fireEvent(e) {
        if (undefined === e.event) {
            return;
        }

        if (undefined === this.firedEvents[e.event]) {
            this.firedEvents[e.event] = [];
        }

        this.firedEvents[e.event].push(e);
    },
    hasEventFired(eventName) {
        return undefined !== this.firedEvents[eventName];
    }
}
