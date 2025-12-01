"use strict";

jQuery(document).ready(function()
{
	jQuery(document.body).on('added_to_cart', wcmca_reload_page);
});
function wcmca_reload_page(event, fragments, cart_hash, $button)
{
	window.location.reload(true);
}