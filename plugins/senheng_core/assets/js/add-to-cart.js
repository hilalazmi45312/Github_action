(function ($) {
    'use strict';

    jQuery(document.body).on('wc_fragment_refresh', function () {
        // Try multiple cart selectors to find the active cart (prioritize Woodmart)
        var cartSelectors = [
            '.wd-cart-sidebar', // Woodmart cart sidebar
            '.woodmart-cart-sidebar', // Woodmart cart sidebar (alternative)
            '.widget_shopping_cart_content', // WooCommerce default
            '.woocommerce-mini-cart', // WooCommerce mini cart
            '.cart-sidebar', // Generic sidebar cart
            '.side-cart', // Generic side cart
            '.mini-cart', // Generic mini cart
            '.cart-widget', // Generic cart widget
            '.shopping-cart-widget', // Generic shopping cart widget
        ];

        window._cartScroll = {};
        jQuery.each(cartSelectors, function (index, selector) {
            var $cart = jQuery(selector);
            if ($cart.length && $cart.scrollTop() > 0) {
                window._cartScroll[selector] = $cart.scrollTop();
            }
        });
    });
    window.fragmentRefreshControl = window.fragmentRefreshControl || {
        blockUntil: 0,
        block: function (ms) {
            this.blockUntil = Date.now() + ms;
        },
        isBlocked: function () {
            return Date.now() < this.blockUntil;
        },
        reset: function () {
            this.blockUntil = 0;
        },
    };
    jQuery(document).on('sh_trade_in_changed sh_payment_option_changed', function () {
        if (window.fragmentRefreshControl && typeof window.fragmentRefreshControl.block === 'function') {
            window.fragmentRefreshControl.block(800);
        }
    });
    jQuery(document).ajaxSend(function (event, xhr, settings) {
        if (settings && settings.url && settings.url.indexOf('wc-ajax=get_refreshed_fragments') !== -1) {
            if (window.fragmentRefreshControl && window.fragmentRefreshControl.isBlocked && window.fragmentRefreshControl.isBlocked()) {
                try {
                    xhr.abort();
                } catch (e) {}
            }
        }
    });

    function getSelectStatus($form) {
        var $selects = $form.find('.variations select');
        var total = $selects.length;
        var selected = 0;
        var map = {};
        $selects.each(function () {
            var $s = $(this);
            var v = $s.val();
            var n = $s.attr('name');
            map[n] = v || '';
            if (v) {
                selected++;
            }
        });
        return {
            total: total,
            selected: selected,
            map: map,
            allSelected: total > 0 && selected === total,
        };
    }

    function getRequirementStatus($form) {
        var vid = parseInt($form.find('input[name="variation_id"]').val() || '0', 10);
        var $tradeScope = $form.find('.wd-swatches-product[data-id*="trade_in"]');
        if (!$tradeScope.length) {
            $tradeScope = jQuery('.wd-swatches-product[data-id*="trade_in"]');
        }
        var tradeRequired = $tradeScope.length > 0 || $form.find('select[name*="trade_in"]').length > 0 || $form.find('input[name="trade_in"]').length > 0;
        var hasTrade = false;
        if ($tradeScope.length) {
            hasTrade = $tradeScope.find('.wd-swatch.wd-active, .wd-swatch.selected').length > 0;
            if (!hasTrade) {
                var $ts = $tradeScope.siblings('.wd-swatch-select');
                if ($ts.length) {
                    var tv = $ts.val();
                    hasTrade = !!(tv && tv !== '' && tv !== '0');
                }
            }
        }
        if (!hasTrade) {
            var $trSel = $form.find('select[name*="trade_in"]');
            if ($trSel.length) {
                var tv2 = $trSel.val();
                hasTrade = !!(tv2 && tv2 !== '' && tv2 !== '0');
            }
        }
        if (!hasTrade) {
            hasTrade = $form.find('input[name="trade_in"]').length > 0 || jQuery('input[name="trade_in"]').length > 0;
        }
        var $depScope = $form.find('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
        if (!$depScope.length) {
            $depScope = jQuery('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
        }
        var $tradeWrapper = $form.closest('.custom-cart-wrapper').find('.sh-trade-in-wrapper');
        if (!$tradeWrapper.length) {
            $tradeWrapper = jQuery('.sh-trade-in-wrapper');
        }
        var depEnabled = $tradeWrapper.length && ($tradeWrapper.data('deposit-enabled') === 'yes' || $tradeWrapper.attr('data-deposit-enabled') === 'yes');
        if (!depEnabled) {
            tradeRequired = false;
        }
        var depReq = depEnabled && ($depScope.length > 0 || $form.find('input[name="deposit_option"]').length > 0 || $form.find('select[name*="awcdp_deposit_option"]').length > 0);
        var hasDep = false;
        if ($depScope.length) {
            hasDep = $depScope.find('.wd-swatch.wd-active, .wd-swatch.selected').length > 0;
            if (!hasDep) {
                var $ds = $depScope.siblings('.wd-swatch-select');
                if ($ds.length) {
                    var dv = $ds.val();
                    hasDep = !!(dv && dv !== '' && dv !== '0');
                }
            }
        }
        if (!hasDep) {
            var $depSel = $form.find('select[name*="awcdp_deposit_option"]');
            if ($depSel.length) {
                var dv2 = $depSel.val();
                hasDep = !!(dv2 && dv2 !== '' && dv2 !== '0');
            }
        }
        if (!hasDep) {
            var $depRadio = $form.find('input[name="deposit_option"]');
            hasDep = $depRadio.filter(':checked').length > 0 || $form.find('input[name="awcdp_deposit_option"]').length > 0 || jQuery('input[name="awcdp_deposit_option"]').length > 0;
        }
        return {
            vid: vid,
            tradeRequired: tradeRequired,
            hasTrade: hasTrade,
            depositRequired: depReq,
            hasDeposit: hasDep,
        };
    }

    function isOOS(variation) {
        if (!variation) return false;
        if (variation.is_in_stock === false) return true;
        var m = variation.managing_stock;
        var sq = variation.stock_quantity;
        var mx = variation.max_qty;
        if (m && typeof sq !== 'undefined' && sq !== null && parseInt(sq, 10) <= 0) return true;
        if (typeof mx === 'number' && mx <= 0) return true;
        return false;
    }

    function updateQuantityOptions($dropdown, maxQty, minQty) {
        var max = Math.max(1, parseInt(maxQty, 10) || 1);
        var min = Math.max(1, parseInt(minQty, 10) || 1);
        $dropdown.empty();
        for (var i = 1; i <= max; i++) {
            $dropdown.append('<option value="' + i + '">' + i + '</option>');
        }
        $dropdown.val(Math.min(min, max));
    }

    function setOutState($form) {
        var $button = $form.find('.custom-add-to-basket-btn');
        var $qty = $form.find('.custom-qty-dropdown');
        var sp = $button.find('span').first();
        var sm = $button.find('small').first();
        if (sp.length) {
            sp.text('Out of stock');
        } else {
            $button.text('Out of stock');
        }
        if (sm.length) {
            sm.text('');
        }
        $button.prop('disabled', true).addClass('disabled wc-variation-is-unavailable').removeClass('wc-variation-selection-needed');
        $qty.prop('disabled', true);
        $qty.empty();
        $qty.append('<option value="0" disabled selected>0</option>');
        $qty.val(0);
        $qty.closest('.quantity-selector').addClass('no-variation');
        $form.find('.custom-cart-wrapper').attr('data-out-of-stock', 'true');
    }

    function setSelectState($form) {
        var $button = $form.find('.custom-add-to-basket-btn');
        var $qty = $form.find('.custom-qty-dropdown');
        var sp = $button.find('span').first();
        var sm = $button.find('small').first();
        if (sp.length) {
            sp.text('Select Option');
        } else {
            $button.text('Select Option');
        }
        if (sm.length) {
            sm.text('');
        }
        $button.prop('disabled', false).addClass('wc-variation-selection-needed').removeClass('disabled wc-variation-is-unavailable');
        $qty.prop('disabled', true);
        $qty.closest('.quantity-selector').addClass('no-variation');
        $form.find('.custom-cart-wrapper').removeAttr('data-out-of-stock');
    }

    function setReadyState($form, variation) {
        var $button = $form.find('.custom-add-to-basket-btn');
        var $qty = $form.find('.custom-qty-dropdown');
        var defTxt = $button.attr('data-default-text');
        var defSub = $button.attr('data-default-subtext');
        var sp = $button.find('span').first();
        var sm = $button.find('small').first();
        if (defTxt) {
            if (sp.length) {
                sp.text(defTxt);
            } else {
                $button.text(defTxt);
            }
        }
        if (sm.length) {
            sm.text(defSub || '');
        }
        $button.prop('disabled', false).removeClass('wc-variation-selection-needed wc-variation-is-unavailable disabled');
        $qty.prop('disabled', false);
        $qty.closest('.quantity-selector').removeClass('no-variation');
        var maxQ = typeof variation.max_qty === 'number' && variation.max_qty > 0 ? variation.max_qty : typeof variation.stock_quantity !== 'undefined' && variation.stock_quantity !== null ? variation.stock_quantity : 1;
        maxQ = Math.max(1, parseInt(maxQ, 10) || 1);
        var minQ = Math.max(1, parseInt(variation.min_qty, 10) || 1);
        updateQuantityOptions($qty, maxQ, minQ);
        $form.find('input[name="variation_id"]').val(variation.variation_id);
    }

    function attributesMatch($form, variation) {
        var s = getSelectStatus($form);
        var attrs = (variation && variation.attributes) || {};
        var keys = Object.keys(attrs);
        for (var i = 0; i < keys.length; i++) {
            var k = keys[i];
            var v = attrs[k] || '';
            if ((s.map[k] || '') !== v) {
                return false;
            }
        }
        return true;
    }

    function getMatchingVariationFromForm($form) {
        var raw = $form.attr('data-product_variations') || $form.data('product_variations');
        var list = [];
        try {
            list = typeof raw === 'string' ? JSON.parse(raw) : raw || [];
        } catch (e) {
            list = [];
        }
        var s = getSelectStatus($form);
        if (!s.allSelected) return null;
        for (var i = 0; i < list.length; i++) {
            var v = list[i];
            if (v && attributesMatch($form, v)) {
                return v;
            }
        }
        return null;
    }

    $(document).on('found_variation', '.variations_form', function (e, variation) {
        var $form = $(this);
        var s = getSelectStatus($form);
        if (!s.allSelected) {
            setSelectState($form);
            return;
        }
        if (!attributesMatch($form, variation)) {
            setSelectState($form);
            return;
        }
        $form.data('variation-data', variation);
        var vid = variation && variation.variation_id ? parseInt(variation.variation_id, 10) : 0;
        if (vid) {
            $form.find('input[name="variation_id"]').val(vid);
        }
        var req = getRequirementStatus($form);
        var ok = vid !== 0 && (!req.tradeRequired || req.hasTrade) && (!req.depositRequired || req.hasDeposit);
        if (!ok) {
            setSelectState($form);
            return;
        }
        if (isOOS(variation)) {
            setOutState($form);
            return;
        }
        setReadyState($form, variation);
    });

    $(document).on('variation_data_synced', '.variations_form', function (e, variation) {
        var $form = $(this);
        if (!variation) {
            return;
        }
        var s = getSelectStatus($form);
        if (!s.allSelected) {
            setSelectState($form);
            return;
        }
        if (!attributesMatch($form, variation)) {
            setSelectState($form);
            return;
        }
        $form.data('variation-data', variation);
        var vid = variation && variation.variation_id ? parseInt(variation.variation_id, 10) : 0;
        if (vid) {
            $form.find('input[name="variation_id"]').val(vid);
        }
        var req = getRequirementStatus($form);
        var ok = vid !== 0 && (!req.tradeRequired || req.hasTrade) && (!req.depositRequired || req.hasDeposit);
        if (!ok) {
            setSelectState($form);
            return;
        }
        if (isOOS(variation)) {
            setOutState($form);
            return;
        }
        setReadyState($form, variation);
    });

    $(document).on('reset_data', '.variations_form', function () {
        var $form = $(this);
        $form.removeData('variation-data');
        $form.find('input[name="variation_id"]').val('0');
        setSelectState($form);
    });

    $(document).on('change', '.variations select', function () {
        var $form = $(this).closest('.variations_form');
        var v = getMatchingVariationFromForm($form);
        if (v) {
            $form.data('variation-data', v);
            var vid = v && v.variation_id ? parseInt(v.variation_id, 10) : 0;
            if (vid) {
                $form.find('input[name="variation_id"]').val(vid);
            }
            var req = getRequirementStatus($form);
            var ok = vid !== 0 && (!req.tradeRequired || req.hasTrade) && (!req.depositRequired || req.hasDeposit);
            if (!ok) {
                setSelectState($form);
                return;
            }
            if (isOOS(v)) {
                setOutState($form);
                return;
            }
            setReadyState($form, v);
        } else {
            $form.find('input[name="variation_id"]').val('0');
            setSelectState($form);
        }
    });

    $(document).on('sh_trade_in_changed sh_payment_option_changed', function () {
        $('form.variations_form').each(function () {
            var $form = $(this);
            var variation = $form.data('variation-data');
            var req = getRequirementStatus($form);
            if (!variation) {
                setSelectState($form);
                return;
            }
            var ok = req.vid !== 0 && (!req.tradeRequired || req.hasTrade) && (!req.depositRequired || req.hasDeposit);
            if (!ok) {
                setSelectState($form);
                return;
            }
            if (isOOS(variation)) {
                setOutState($form);
                return;
            }
            setReadyState($form, variation);
        });
    });
    $(document).on('change', '[name="awcdp_deposit_option"]', function () {
        $('form.variations_form').each(function () {
            var $form = $(this);
            var variation = $form.data('variation-data');
            var req = getRequirementStatus($form);
            if (!variation) {
                setSelectState($form);
                return;
            }
            var ok = req.vid !== 0 && (!req.tradeRequired || req.hasTrade) && (!req.depositRequired || req.hasDeposit);
            if (!ok) {
                setSelectState($form);
                return;
            }
            if (isOOS(variation)) {
                setOutState($form);
                return;
            }
            setReadyState($form, variation);
        });
    });

    function allVariationsOutOfStock($form) {
        var raw = $form.attr('data-product_variations') || $form.data('product_variations');
        var list = [];
        if (raw) {
            try {
                list = typeof raw === 'string' ? JSON.parse(raw) : raw;
            } catch (e) {
                list = [];
            }
        }
        if (!list || !list.length) return false;
        var anyInStock = false;
        for (var i = 0; i < list.length; i++) {
            var v = list[i];
            if (!isOOS(v)) {
                anyInStock = true;
                break;
            }
        }
        return !anyInStock;
    }

    function isSimpleProductOutOfStock($form) {
        var $product = $form.closest('.product');
        var $stock = $product.length ? $product.find('.stock') : jQuery('.stock');
        if ($stock.length) {
            if ($stock.hasClass('out-of-stock')) return true;
            var txt = ($stock.text() || '').trim().toLowerCase();
            if (txt.indexOf('out of stock') !== -1) return true;
        }
        var $btn = $form.find('.single_add_to_cart_button, .custom-add-to-basket-btn');
        if ($btn.length) {
            if ($btn.prop('disabled')) return true;
            if ($btn.hasClass('disabled') || $btn.hasClass('wc-variation-is-unavailable')) return true;
        }
        return false;
    }

    function checkOutOfStockOnLoad() {
        $('form.variations_form').each(function () {
            var $form = $(this);
            if (allVariationsOutOfStock($form)) {
                setOutState($form);
            }
        });
        $('form.cart').each(function () {
            var $form = $(this);
            if ($form.hasClass('variations_form')) return;
            if (isSimpleProductOutOfStock($form)) {
                setOutState($form);
            }
        });
    }

    $(document).ready(function () {
        checkOutOfStockOnLoad();
    });

    $(document).on('woocommerce_update_variation_values', function () {
        checkOutOfStockOnLoad();
    });

    $(document).on('sh_all_variations_out_of_stock', function (e, data) {
        var $form = data && data.form ? $(data.form) : $('form.variations_form').first();
        if ($form && $form.length) {
            setOutState($form);
        }
    });

    $(document).on('sh_check_all_variations_oos', function () {
        checkOutOfStockOnLoad();
    });

    $(document).on('click', '.custom-add-to-basket-btn.wc-variation-selection-needed', function (ev) {
        ev.preventDefault();
        var $btn = $(this);
        var $form = $btn.closest('.variations_form');
        var $target = $('.sh-variation-form').first();
        if (!$target.length) {
            $target = $form;
        }
        var headerHeight = $('.whb-header .whb-row.whb-sticky-row').length > 0 ? $('.whb-header .whb-main-header').outerHeight() : 0;
        var $stickyHeader = $('.whb-sticky-header');
        var stickyHeaderHeight = $stickyHeader.length ? $stickyHeader.outerHeight() : headerHeight;
        var offsetCfg = (window.woodmart_settings && window.woodmart_settings.sticky_add_to_cart_offset) || 0;
        var scrollTo = $target.offset().top - stickyHeaderHeight - offsetCfg;
        $('html, body').animate({ scrollTop: scrollTo }, 800);
    });

    function ensureTradeDepositInputs($form) {
        try {
            var $tradeScope = $form.find('.wd-swatches-product[data-id*="trade_in"]');
            if (!$tradeScope.length) {
                $tradeScope = jQuery('.wd-swatches-product[data-id*="trade_in"]');
            }
            var $payScope = $form.find('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
            if (!$payScope.length) {
                $payScope = jQuery('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
            }
            $form.find('input[name="trade_in"], input[name="awcdp_deposit_option"]').remove();
            var tSel = $tradeScope.find('.wd-swatch.selected').first();
            if (tSel.length && tSel.data('value')) {
                $form.append('<input type="hidden" name="trade_in" value="' + tSel.data('value') + '">');
            }
            var dSel = $payScope.find('.wd-swatch.selected').first();
            if (dSel.length && dSel.data('value')) {
                $form.append('<input type="hidden" name="awcdp_deposit_option" value="' + dSel.data('value') + '">');
            }
        } catch (e) {}
    }

    function clearErrorNotices() {
        jQuery('.woocommerce-error, .woocommerce-message.error, .notice-error').remove();
    }

    function resetButtonToNormal($button, $cartWrapper) {
        $button.removeClass('loading');
        $cartWrapper.removeClass('loading');
    }

    function woodmart_ajax_add_to_cart($form, $button, $cartWrapper) {
        ensureTradeDepositInputs($form);
        var data = $form.serialize();
        function removeParam(str, key) {
            var parts = str ? str.split('&') : [];
            var res = [];
            for (var i = 0; i < parts.length; i++) {
                var kv = parts[i].split('=');
                if (decodeURIComponent(kv[0]) !== key) {
                    res.push(parts[i]);
                }
            }
            return res.join('&');
        }
        function setParam(str, key, value) {
            var s = removeParam(str, key);
            if (value !== undefined && value !== null && String(value).length > 0) {
                if (s.length) s += '&';
                s += encodeURIComponent(key) + '=' + encodeURIComponent(String(value));
            }
            return s;
        }
        var $wcBtn = $form.find('.single_add_to_cart_button');
        var pid = $form.find('input[name="product_id"]').val() || $button.data('product_id') || $button.data('productId') || $button.val() || '';
        var hasIndividualExtras = $form.find('input[name^="extra_info_ids"], input[name^="extra_product_ids"]').length > 0;
        if (!hasIndividualExtras) {
            try {
                if (window.ProductExtrasWidget && typeof window.ProductExtrasWidget.getProductExtrasData === 'function') {
                    var ped = window.ProductExtrasWidget.getProductExtrasData();
                    if (ped && ((ped.selectedProducts && ped.selectedProducts.length > 0) || (ped.selectedInfo && ped.selectedInfo.length > 0))) {
                        var productsJson = encodeURIComponent(JSON.stringify(ped.selectedProducts));
                        var infoJson = encodeURIComponent(JSON.stringify(ped.selectedInfo));
                        data += '&product_extras_products=' + productsJson + '&product_extras_info=' + infoJson + '&has_product_extras=1';
                    }
                }
            } catch (e) {}
        } else {
            if ($form.find('input[name="has_product_extras"]').length === 0) {
                data += '&has_product_extras=1';
            }
        }
        var addVal = '';
        if ($wcBtn.length && $wcBtn.val()) {
            addVal = $wcBtn.val();
        } else if (pid) {
            addVal = String(pid);
        }
        if (pid) {
            data = setParam(data, 'product_id', String(pid));
        }
        if (addVal) {
            data = setParam(data, 'add-to-cart', addVal);
        }
        var qty = $form.find('.custom-qty-dropdown').val() || $form.find('input[name="quantity"]').val();
        data = setParam(data, 'quantity', qty ? String(qty) : '');
        data = setParam(data, 'action', 'woodmart_ajax_add_to_cart');
        $button.removeClass('added not-added').addClass('loading');
        var $body = window.woodmartThemeModule && woodmartThemeModule.$body ? woodmartThemeModule.$body : jQuery(document.body);
        $body.trigger('adding_to_cart', [$button, data]);
        var ajaxUrl = window.woodmart_settings && woodmart_settings.ajaxurl ? woodmart_settings.ajaxurl : typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.ajax_url ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php';
        jQuery
            .ajax({ url: ajaxUrl, data: data, method: 'POST' })
            .done(function (response) {
                if (!response) {
                    resetButtonToNormal($button, $cartWrapper);
                    return;
                }
                if (response.error && response.product_url) {
                    window.location = response.product_url;
                    return;
                }
                $button.removeClass('loading');
                var fragments = response.fragments || {};
                var cart_hash = response.cart_hash;
                if (fragments) {
                    jQuery.each(fragments, function (key) {
                        jQuery(key).addClass('updating');
                    });
                    jQuery.each(fragments, function (key, value) {
                        jQuery(key).replaceWith(value);
                    });
                }
                var $noticeWrapper = jQuery('.woocommerce-notices-wrapper');
                $noticeWrapper.empty();
                if (response.notices && response.notices.indexOf('error') > 0) {
                    $noticeWrapper.append(response.notices);
                    $button.addClass('not-added');
                    jQuery(document.body).trigger('not_added_to_cart', [fragments, cart_hash, $button]);
                } else {
                    if (typeof jQuery.fn.magnificPopup !== 'undefined' && window.woodmart_settings && woodmart_settings.add_to_cart_action === 'widget') {
                        jQuery.magnificPopup.close();
                    }
                    $button.addClass('added');
                    jQuery(document.body).trigger('added_to_cart', [fragments, cart_hash, $button]);
                }
                resetButtonToNormal($button, $cartWrapper);
            })
            .fail(function () {
                resetButtonToNormal($button, $cartWrapper);
            });
    }

    jQuery(document)
        .off('submit.customAddToCart')
        .on('submit.customAddToCart', 'form.cart, form.variations_form', function (e) {
            var $form = jQuery(this);
            var $button = $form.find('.custom-add-to-basket-btn');
            var $cartWrapper = $form.find('.custom-cart-wrapper');
            if (!$button.length || !$button.hasClass('custom-add-to-basket-btn')) {
                return;
            }
            var vid = parseInt($form.find('input[name="variation_id"]').val() || '0', 10);
            var req = getRequirementStatus($form);
            var needs = vid === 0 || !(!req.tradeRequired || req.hasTrade) || !(!req.depositRequired || req.hasDeposit) || $button.hasClass('wc-variation-selection-needed') || $button.prop('disabled');
            if (needs) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var $target = $form.closest('.sh-variation-form');
                if (!$target.length) {
                    $target = $form;
                }
                var headerHeight = jQuery('.whb-header .whb-row.whb-sticky-row').length > 0 ? jQuery('.whb-header .whb-main-header').outerHeight() : 0;
                var $stickyHeader = jQuery('.whb-sticky-header');
                var stickyHeaderHeight = $stickyHeader.length ? $stickyHeader.outerHeight() : headerHeight;
                var offsetCfg = (window.woodmart_settings && window.woodmart_settings.sticky_add_to_cart_offset) || 0;
                var scrollTo = $target.offset().top - stickyHeaderHeight - offsetCfg;
                jQuery('html, body').animate({ scrollTop: scrollTo }, 800);
                return false;
            }
            var $qty = $form.find('.custom-qty-dropdown');
            var quantity = parseInt($qty.val() || '0', 10);
            if (!quantity || quantity <= 0) {
                e.preventDefault();
                return false;
            }
            clearErrorNotices();
            ensureTradeDepositInputs($form);
            $button.addClass('loading');
            $cartWrapper.addClass('loading');
            if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.wc_ajax_url) {
                e.preventDefault();
                e.stopImmediatePropagation();
                woodmart_ajax_add_to_cart($form, $button, $cartWrapper);
                return false;
            }
        });

    jQuery(document.body).on('wc_add_to_cart_error', function () {
        jQuery('.custom-add-to-basket-btn.loading, .custom-add-to-basket-btn').each(function () {
            var $b = jQuery(this);
            var $w = $b.closest('.custom-cart-wrapper');
            resetButtonToNormal($b, $w);
        });
    });

    jQuery(document).on('click', '.custom-add-to-basket-btn', function (e) {
        var $button = jQuery(this);
        if ($button.hasClass('wc-variation-selection-needed') || $button.prop('disabled')) {
            return;
        }
        if ($button.hasClass('loading')) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        var $form = $button.closest('form');
        var $cartWrapper = $form.find('.custom-cart-wrapper');
        var vid = parseInt($form.find('input[name="variation_id"]').val() || '0', 10);
        var req = getRequirementStatus($form);
        var needs = vid === 0 || !(!req.tradeRequired || req.hasTrade) || !(!req.depositRequired || req.hasDeposit);
        if (needs) {
            return;
        }
        var qty = parseInt($form.find('.custom-qty-dropdown').val() || $form.find('input[name="quantity"]').val() || '0', 10);
        if (!qty || qty <= 0) {
            e.preventDefault();
            return false;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        clearErrorNotices();
        ensureTradeDepositInputs($form);
        $button.addClass('loading');
        $cartWrapper.addClass('loading');
        woodmart_ajax_add_to_cart($form, $button, $cartWrapper);
        return false;
    });
})(jQuery);
