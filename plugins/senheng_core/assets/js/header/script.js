document.addEventListener("DOMContentLoaded", () => {

    (function () {
        const origOpen = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method, url) {
            this._url = url;
            return origOpen.apply(this, arguments);
        };
        const origSend = XMLHttpRequest.prototype.send;
        XMLHttpRequest.prototype.send = function (body) {
            this.addEventListener('load', function () {
                // Detect add to cart
                if (
                    (this._url && this._url.indexOf("wc-ajax=add_to_cart") !== -1) ||
                    (body && typeof body === "string" && (body.includes("action=woodmart_ajax_add_to_cart") ||
                    body.includes("add-to-cart")))
                ) {
                    let fragments = null;
                    try {
                        const json = JSON.parse(this.responseText);
                        fragments = json.fragments || null;
                    } catch (err) {}
                    console.log('Cart updated (ajaxComplete equivalent)', fragments);
                    updateCartCount(fragments);
                }
                // Detect remove from cart
                if (
                    (this._url && this._url.indexOf("wc-ajax=remove_from_cart") !== -1) ||
                    (body && typeof body === "string" && body.includes("remove_item"))
                ) {
                    let fragments = null;
                    try {
                        const json = JSON.parse(this.responseText);
                        fragments = json.fragments || null;
                    } catch (err) {}
                    console.log('Cart item removed (ajaxComplete equivalent)', fragments);
                    updateCartCount(fragments);
                }
            });
            return origSend.apply(this, arguments);
        };
    })();
});

function updateCartCount(fragments) {
    if (fragments && fragments['span.wd-cart-number_wd']) {
        let count = fragments['span.wd-cart-number_wd'].replace(/[^0-9]/g, '');
        var badges = document.querySelectorAll('.mobi-cart-badge');
        badges.forEach(function (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.textContent = '0';
                badge.style.display = 'none';
            }
        });
    }
}
