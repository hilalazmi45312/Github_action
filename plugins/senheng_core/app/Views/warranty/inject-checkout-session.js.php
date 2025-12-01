<script>
    jQuery(function($) {
        if (!$('body').hasClass('woocommerce-checkout')) return;

        const reviewSelector = '.wfacp-order-review, #wfacp-order-summary, .woocommerce-checkout-review-order-table, #order_review';

        function inject() {
            $('.product-name-wrapper').each(function() {
                const $wrap = $(this);
                const key = String($wrap.data('cart_item_key'));
                const isWarranty = String($wrap.data('is-warranty')) === '1';
                const a = $wrap.data('warranty-eligible');
                const eligible = (a === 1 || a === '1' || typeof a === 'undefined');
                const selected = String($wrap.data('warranty-selected')) === '1';

                $wrap.siblings('.warranty-wrap').remove();
                if (!key || isWarranty || !eligible) return;

                const $node = $(`
                    <div class="warranty-wrap" data-cart-key="${key}" style="margin-top:6px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;">
                        <input type="checkbox" class="wty-checkbox" data-cart-key="${key}">
                        <small style="color:#d00;font-weight:600;" class="9-9-warranty-added">
                            ADDED: 9.90 Product Warranty
                        </small>
                    </label>
                    </div>
                `);
                $wrap.after($node);

                const $cb = $node.find('.wty-checkbox');
                if (selected) $cb.prop('checked', true); // ✅ auto tick if fee already applied (cookie says ON)
            });
            $('.sh-item-meta').each(function() {
                const $meta = $(this);
                const metaKey = $meta.data('cart-key');
                if (!metaKey) return;

                // find warranty-wrap with the same data-cart-key
                const $warranty = $('.warranty-wrap[data-cart-key="' + metaKey + '"]').first();
                if (!$warranty.length) return;

                // optional: avoid duplicates inside this meta
                $meta.find('.warranty-wrap').not($warranty).remove();

                // move/append the matching warranty into this meta
                $warranty.appendTo($meta);
            });
        }

        // cookie writer (same as cart)
        function readOff() {
            try {
                return JSON.parse(decodeURIComponent((document.cookie.match(/(?:^|;)\s*wty_off_keys=([^;]+)/) || [])[1] || '[]'));
            } catch (e) {
                return [];
            }
        }

        function writeOff(arr) {
            const v = encodeURIComponent(JSON.stringify(arr || []));
            const d = new Date();
            d.setTime(d.getTime() + 30 * 24 * 60 * 60 * 1000);
            document.cookie = 'wty_off_keys=' + v + '; expires=' + d.toUTCString() + '; path=/';
        }

        function setOff(key, off) {
            key = String(key);
            let arr = readOff().filter(k => k !== key);
            if (off) arr.push(key);
            writeOff(arr);
        }

        // Toggle → cookie → recalc checkout
        $(document).on('change', '.wty-checkbox', function() {
            const key = String($(this).data('cart-key'));
            setOff(key, !this.checked);
            $(document.body).trigger('update_checkout');
        });

        inject();

        // Re-inject on FunnelKit / WC refreshes
        const target = document.querySelector(reviewSelector);
        if (target) {
            const obs = new MutationObserver((muts) => {
                for (const m of muts) {
                    if (m.addedNodes.length || m.removedNodes.length) {
                        inject();
                        break;
                    }
                }
            });
            obs.observe(target, {
                childList: true,
                subtree: true
            });
        }
        $(document.body).on('updated_checkout wfacp_after_reload_checkout applied_coupon_in_checkout removed_coupon_in_checkout', inject);
    });
</script>