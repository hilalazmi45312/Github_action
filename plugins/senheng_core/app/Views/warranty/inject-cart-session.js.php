<script>
    jQuery(function($) {
        if (!$('body').hasClass('woocommerce-cart')) return;

        // --- cookie helpers ---
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
            d.setTime(d.getTime() + 30 * 24 * 60 * 60 * 1000); // 30 days
            document.cookie = 'wty_off_keys=' + v + '; expires=' + d.toUTCString() + '; path=/';
        }

        function setOff(key, off) {
            key = String(key);
            let arr = readOff().filter(k => k !== key);
            if (off) arr.push(key);
            writeOff(arr);
        }

        function inject() {
            $('.cart_item').each(function() {
                const $row = $(this);
                const $wrap = $row.find('.product-name-wrapper');
                const $col = $row.find('.product-name');
                if (!$wrap.length) return;

                const key = String($wrap.data('cart_item_key'));
                const isWarranty = String($wrap.data('is-warranty')) === '1';
                const a = $wrap.data('warranty-eligible');
                const eligible = (a === 1 || a === '1' || typeof a === 'undefined');
                const selected = String($wrap.data('warranty-selected')) === '1'; // from PHP

                $col.find('.warranty-wrap').remove();
                if (!key || isWarranty || !eligible) return;

                const $node = $(`
                    <div class="warranty-wrap" style="margin-top:6px;">
                    <label style="display:inline-flex;align-items:center;gap:6px;">
                        <input type="checkbox" class="wty-checkbox" data-cart-key="${key}">
                        <small style="color:#d00;font-weight:600;" class="9-9-warranty-added">
                        ADDED: 9.90 Product Warranty
                        </small>
                    </label>
                    </div>
                `);
                $col.append($node);

                const $cb = $node.find('.wty-checkbox');
                if (selected) $cb.prop('checked', true); // ✅ auto tick if fee already applied
            });
        }

        // Toggle → write cookie → refresh totals
        $(document).on('change', '.wty-checkbox', function() {
            const key = String($(this).data('cart-key'));
            setOff(key, !this.checked); // unchecked → add to OFF cookie
            $(document.body).trigger('wc_update_cart');
        });

        inject();

        // Rebuild UI after cart refreshes
        $(document.body).on('wc_fragments_refreshed updated_wc_div removed_from_cart cart_page_refreshed', inject);
    });
</script>