function removeScoinButtonsInWidget() {
	jQuery('.widget_shopping_cart .scoin-cont').remove();
	jQuery('.widget_shopping_cart .9-9-warranty-added').remove();
}
removeScoinButtonsInWidget();

/* ------------------------------
   1. Detect WooCommerce AJAX
------------------------------ */
jQuery(document).ajaxComplete(function (event, xhr, settings) {

	// Normalize payload (POST body)
	var payload = settings.data || "";

	/* -----------------------------
	   1. WooCommerce core AJAX
	----------------------------- */
	if (settings.url.indexOf('wc-ajax=add_to_cart') !== -1) {
		removeScoinButtonsInWidget();
	}

	if (settings.url.indexOf('wc-ajax=remove_from_cart') !== -1) {
		removeScoinButtonsInWidget();
	}

	/* -----------------------------
	   2. Woodmart AJAX add to cart
	   POST body contains: action=woodmart_ajax_add_to_cart
	----------------------------- */
	if (payload.indexOf('action=woodmart_ajax_add_to_cart') !== -1) {
		removeScoinButtonsInWidget();
	}

	/* -----------------------------
	   3. Woodmart AJAX remove item
	   POST body contains: action=woodmart_remove_from_cart
	----------------------------- */
	if (payload.indexOf('action=woodmart_remove_from_cart') !== -1) {
		removeScoinButtonsInWidget();
	}

});


/* ------------------------------
   2. WooCommerce fragment events
------------------------------ */
jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed', removeScoinButtonsInWidget);

/* ------------------------------
   3. Woodmart AJAX "add to cart"
------------------------------ */
// When Woodmart triggers its AJAX add-to-cart
jQuery(document.body).on('woodmart_ajax_add_to_cart', function () {
	removeScoinButtonsInWidget();
});

// After Woodmart finishes updating the mini-cart
jQuery(document.body).on('woodmart_added_to_cart', function () {
	removeScoinButtonsInWidget();
});

// Woodmart sometimes uses this event after updating HTML in minicart
jQuery(document.body).on('woodmart-ajax-content-replaced', function () {
	removeScoinButtonsInWidget();
});