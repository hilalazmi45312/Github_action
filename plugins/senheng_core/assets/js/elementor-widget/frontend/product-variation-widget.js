jQuery(document).ready(function ($) {
    'use strict';

    // Helper: build variation index for quick lookups (no caching to ensure fresh data)
    function buildVariationIndex($form) {
        var raw = $form.attr('data-product_variations') || $form.data('product_variations');
        // Always fetch fresh data - remove caching to prevent stale variation data
        var variationsList = [];
        if (raw) {
            try {
                variationsList = typeof raw === 'string' ? JSON.parse(raw) : raw;
            } catch (e) {
                variationsList = [];
            }
        }
        var variationIndex = {};
        for (var i = 0; i < variationsList.length; i++) {
            var v = variationsList[i];
            if (!v || !v.attributes) continue;
            var attrs = v.attributes;
            for (var key in attrs) {
                if (!Object.prototype.hasOwnProperty.call(attrs, key)) continue;
                var val = attrs[key];
                if (!variationIndex[key]) variationIndex[key] = {};
                if (!variationIndex[key][val]) variationIndex[key][val] = [];
                variationIndex[key][val].push(v);
            }
        }
        var result = { list: variationsList, index: variationIndex };
        // Remove caching to prevent stale variation data
        return result;
    }

    function getSelectedMap($form) {
        var m = {};
        $form.find('.variations select').each(function () {
            var $s = jQuery(this);
            var nm = $s.attr('name');
            m[nm] = $s.val() || '';
        });
        return m;
    }

    function findMatchingVariation($form) {
        var cached = buildVariationIndex($form);
        var list = cached.list || [];
        var total = $form.find('.variations select').length;
        var map = getSelectedMap($form);
        var selected = 0;
        for (var k in map) {
            if (map[k]) selected++;
        }
        if (!(total > 0 && selected === total)) return null;
        for (var i = 0; i < list.length; i++) {
            var v = list[i];
            if (!v || !v.attributes) continue;
            var attrs = v.attributes;
            var keys = Object.keys(attrs);
            var ok = true;
            for (var j = 0; j < keys.length; j++) {
                var key = keys[j];
                var val = attrs[key] || '';
                if ((map[key] || '') !== val) {
                    ok = false;
                    break;
                }
            }
            if (ok) return v;
        }
        return null;
    }

    // Helper: update swatch enabled/disabled state (works on load and on change)
    function updateSwatches($form) {
        var cached = buildVariationIndex($form);
        var variationIndex = cached.index;

        if (!cached.list || !cached.list.length || !variationIndex || !Object.keys(variationIndex).length) {
            return;
        }

        $form.find('.wd-swatch').removeClass('wd-enabled wd-disabled');

        // Build map of current selections for all attributes
        var selectedMap = {};
        $form.find('.variations select').each(function () {
            var $s = $(this);
            var nm = $s.attr('name');
            selectedMap[nm] = $s.val() || '';
        });

        var totalSelects = $form.find('.variations select').length;
        var selectedCount = 0;
        for (var k in selectedMap) {
            if (selectedMap[k]) selectedCount++;
        }
        var allSelected = selectedCount === totalSelects && totalSelects > 0;

        // Update swatches per attribute based on stock-aware index
        $form.find('.variations select').each(function () {
            var $select = $(this);
            var attrName = $select.attr('name');
            var $swatchContainer = $select.siblings('.wd-swatches-product');
            var selectedValForAttr = selectedMap[attrName] || '';

            $select.find('option').each(function () {
                var $option = $(this);
                var value = $option.val();
                var $swatch = $swatchContainer.find('[data-value="' + value + '"]');

                var candidates = variationIndex[attrName] && variationIndex[attrName][value] ? variationIndex[attrName][value] : [];
                $option.prop('disabled', false).addClass('enabled');
                $swatch.removeClass('wd-disabled').addClass('wd-enabled');
                $swatch.removeAttr('data-stock-qty').removeAttr('data-stock-unlimited');
            });
            $swatchContainer.find('.wd-swatch').each(function () {
                var $sw = $(this);
                $sw.removeClass('wd-disabled').addClass('wd-enabled');
            });
        });
    }

    // Helper: Clear variation cache to ensure fresh data
    function clearVariationCache($form) {
        $form.removeData('variationIndexCache');
        $form.removeData('parsedVariations');
    }

    function forceClearInitialSelections($form) {
        try {
            $form.find('.wd-swatches-product .wd-swatch').removeClass('wd-active');
            $form.find('.variations select').each(function () {
                var $s = $(this);
                if ($s.find('option[value=""]').length === 0) {
                    $s.prepend('<option value=""></option>');
                }
                $s.val('');
                $s.removeAttr('data-user-touched').data('userTouched', false);
            });
            $form.trigger('reset_data');
            $form.trigger('woocommerce_update_variation_values');
        } catch (e) {}
    }

    function detachThemeAddToCartClicks() {
        try {
            $(document).off('click', '.single_add_to_cart_button');
            $(document).off('click', '.wd-buy-now-btn');
            if (window.woodmartThemeModule && woodmartThemeModule.$body) {
                woodmartThemeModule.$body.off('click', '.single_add_to_cart_button');
                woodmartThemeModule.$body.off('click', '.wd-buy-now-btn');
            }
        } catch (e) {}
    }

    // Helper removed: default selection clearing is handled by PHP markup now

    // Initialize variation widget
    function initVariationWidget() {
        $('.product-variation-widget .variations_form, .sh-variation-form .variations_form').each(function () {
            var $form = $(this);
            var $widget = $form.closest('.product-variation-widget, .sh-variation-form');
            if ($form.data('variationWidgetBound')) {
                return;
            }
            $form.data('variationWidgetBound', true);

            // Bind our update handler BEFORE initializing, then trigger AFTER init
            $form.off('woocommerce_update_variation_values.widget').on('woocommerce_update_variation_values.widget', function () {
                updateSwatches($form);
            });

            // Initialize WooCommerce variation form and trigger initial update
            if (typeof wc_add_to_cart_variation_params !== 'undefined' && typeof $.fn.wc_variation_form !== 'undefined') {
                if (!$form.hasClass('variations-initialized')) {
                    $form.addClass('variations-initialized');
                    $form.wc_variation_form();
                }
                // Disable out-of-stock on first load based on current PHP-rendered state
                // Find the main add to cart form on the page
                var $mainForm = $('form.cart, form.variations_form').not($form).first();
                if (!$mainForm.length) {
                    $mainForm = $('form').has('button[name="add-to-cart"]').first();
                }
                if (!$mainForm.length) {
                    $mainForm = $('form').has('input[name="add-to-cart"]').first();
                }
                if (!$mainForm.length) {
                    $mainForm = $('.single_add_to_cart_button').closest('form').first();
                }
                if (!$mainForm.length) {
                    $mainForm = $('.wd-single-add-cart form').first();
                }
                // Store reference to main form for syncing
                $widget.data('main-form', $mainForm);
                if ($mainForm && $mainForm.length) {
                    $mainForm.find('.qty').val(0);
                }
                var $formBtn = $form.find('.single_add_to_cart_button');
                var $mainBtn = $mainForm && $mainForm.length ? $mainForm.find('.single_add_to_cart_button') : $();
                var $mainCustomBtn = $mainForm && $mainForm.length ? $mainForm.find('.custom-add-to-basket-btn') : $();
                // Build index and update immediately, then trigger Woo update without delay
                updateSwatches($form);
                $form.trigger('woocommerce_update_variation_values');
                // Force clear any auto-selected attributes so user must choose
                forceClearInitialSelections($form);
                var cached = buildVariationIndex($form);
                var list = cached.list || [];
                var anyInStock = false;
                for (var i = 0; i < list.length; i++) {
                    var v = list[i];
                    if (v && v.is_in_stock !== false) {
                        anyInStock = true;
                        break;
                    }
                    if (v && typeof v.max_qty === 'number' && v.max_qty > 0) {
                        anyInStock = true;
                        break;
                    }
                    if (v && typeof v.stock_quantity === 'number' && v.stock_quantity > 0) {
                        anyInStock = true;
                        break;
                    }
                }
                if (!anyInStock && list.length > 0) {
                    var targetForm = $mainForm && $mainForm.length ? $mainForm : $form;
                    $(document).trigger('sh_all_variations_out_of_stock', { form: targetForm });
                }
            }

            // Handle WoodMart swatch clicks
            $widget.off('click.swatches', '.wd-swatch').on('click.swatches', '.wd-swatch', function (e) {
                e.preventDefault();

                var $swatch = $(this);
                // Block interaction if swatch is disabled (no stock)
                if ($swatch.hasClass('wd-disabled')) {
                    return;
                }
                var $swatchesWrap = $swatch.closest('.wd-swatches-product');
                var $hiddenSelect = $swatchesWrap.siblings('.wd-swatch-select');
                var value = $swatch.data('value');
                var attributeName;
                if (!$hiddenSelect.length) {
                    var attrId = $swatchesWrap.data('id');
                    var selectName = 'attribute_' + attrId;
                    var $formScope = $swatchesWrap.closest('form.variations_form');
                    $hiddenSelect = $formScope.find('select[name="' + selectName + '"]');
                }
                attributeName = $hiddenSelect.attr('name');

                // Remove active class from siblings
                $swatchesWrap.find('.wd-swatch').removeClass('wd-active');

                // Add active class to clicked swatch
                $swatch.addClass('wd-active');

                // Update hidden select
                if ($hiddenSelect.length) {
                    $hiddenSelect.val(value).attr('data-user-touched', '1').data('userTouched', true).trigger('change');
                }

                // Sync with main form if it exists
                var $mainForm = $widget.data('main-form');
                if ($mainForm && $mainForm.length && attributeName) {
                    var $mainSelect = $mainForm.find('select[name="' + attributeName + '"]');
                    if ($mainSelect.length) {
                        $mainSelect.val(value);
                    }
                }

                // Selected value row removed; no UI update needed here

                // Let WooCommerce handle variation processing via change events
                // Ensure our stock view stays consistent with current selection
                updateSwatches($form);
                var mv = findMatchingVariation($form);
                if (mv) {
                    $form.trigger('found_variation', [mv]);
                }
            });

            // Attach variation update handler is moved earlier and namespaced
            // Add event listeners for WooCommerce variation events
            $form.off('found_variation.widget').on('found_variation.widget', function (event, variation) {
                // Store variation data for later use
                $form.data('variation-data', variation);

                // Sync variation data with main form
                var $mainForm = $widget.data('main-form');
                if ($mainForm && $mainForm.length && variation) {
                    // Update main form's variation_id
                    $mainForm.find('input[name="variation_id"]').val(variation.variation_id);

                    // Also update product_id if needed
                    if (variation.product_id) {
                        $mainForm.find('input[name="product_id"]').val(variation.product_id);
                    }

                    // Ensure variation data includes all necessary stock information
                    var completeVariation = $.extend({}, variation);

                    // Make sure stock quantity and max quantity are properly set
                    if (variation.is_in_stock && variation.stock_quantity !== undefined) {
                        completeVariation.stock_quantity = variation.stock_quantity;
                    }
                    if (variation.max_qty !== undefined) {
                        completeVariation.max_qty = variation.max_qty;
                    }

                    // Trigger WooCommerce events that the controller expects
                    $mainForm.trigger('found_variation', [completeVariation]);
                    $mainForm.trigger('variation_data_synced', [completeVariation]);
                }

                try {
                } catch (e) {}
            });

            $form.off('reset_data.widget').on('reset_data.widget', function () {
                // Clear stored variation data
                $form.removeData('variation-data');

                // Sync reset with main form
                var $mainForm = $widget.data('main-form');
                if ($mainForm && $mainForm.length) {
                    // Reset main form's variation_id
                    $mainForm.find('input[name="variation_id"]').val('0');
                    $mainForm.find('.qty').val(0);

                    // Trigger WooCommerce reset events that the controller expects
                    $mainForm.trigger('reset_data');
                    $mainForm.trigger('variation_reset_synced');
                }
            });

            // Helper: trade/deposit status
            function getTradeDepositStatus($form) {
                var $tradeScope = $form.find('.wd-swatches-product[data-id*="trade_in"]');
                if (!$tradeScope.length) {
                    $tradeScope = jQuery('.wd-swatches-product[data-id*="trade_in"]');
                }
                var tradeRequired = $tradeScope.length > 0;
                var tradeSelected = $tradeScope.find('.wd-swatch.selected').length > 0 || $form.find('input[name="trade_in"]').length > 0;

                var $depositSwatchScope = $form.find('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
                if (!$depositSwatchScope.length) {
                    $depositSwatchScope = jQuery('.wd-swatches-product[data-id*="awcdp_deposit_option"]');
                }
                var depositSwatchSelected = $depositSwatchScope.find('.wd-swatch.selected').length > 0 || $form.find('input[name="awcdp_deposit_option"]').length > 0;

                var $depositRadio = $form.find('input[name="deposit_option"]');
                var depositRadioPresent = $depositRadio.length > 0;
                var depositRadioSelected = depositRadioPresent && $depositRadio.filter(':checked').length > 0;

                var depositRequired = $depositSwatchScope.length > 0 || depositRadioPresent;
                var depositSelected = depositSwatchSelected || depositRadioSelected;

                return {
                    tradeRequired: tradeRequired,
                    tradeSelected: tradeSelected,
                    depositRequired: depositRequired,
                    depositSelected: depositSelected,
                };
            }

            detachThemeAddToCartClicks();
            document.addEventListener(
                'click',
                function (ev) {
                    var target = ev.target;
                    if (!target) return;
                    var clickable = target.closest('.single_add_to_cart_button, .custom-add-to-basket-btn, .wd-buy-now-btn');
                    if (!clickable) return;
                    var $btn = $(clickable);
                    var $form = $btn.closest('form.variations_form');
                    if (!$form.length) {
                        $form = $('.product-variation-widget .variations_form, .sh-variation-form .variations_form').first();
                        if (!$form.length) {
                            $form = $('form.variations_form').first();
                        }
                    }
                    if (!$form.length) return;
                    var vid = parseInt($form.find('input[name="variation_id"]').val() || '0', 10);
                    var req = getTradeDepositStatus($form);
                    var requirementsOk = (!req.tradeRequired || req.tradeSelected) && (!req.depositRequired || req.depositSelected);
                    var needsSelection = vid === 0 || !requirementsOk || $btn.hasClass('wc-variation-selection-needed') || $btn.prop('disabled');
                    if (needsSelection) {
                        ev.preventDefault();
                        ev.stopImmediatePropagation();
                        var $targetWrapper = $form.closest('.sh-variation-form');
                        var $scrollTarget = $targetWrapper.length ? $targetWrapper : $form;
                        var headerHeight = $('.whb-header .whb-row.whb-sticky-row').length > 0 ? $('.whb-header .whb-main-header').outerHeight() : 0;
                        var $stickyHeader = $('.whb-sticky-header');
                        var stickyHeaderHeight = $stickyHeader.length ? $stickyHeader.outerHeight() : headerHeight;
                        var offsetCfg = (window.woodmart_settings && window.woodmart_settings.sticky_add_to_cart_offset) || 0;
                        var scrollTo = $scrollTarget.offset().top - stickyHeaderHeight - offsetCfg;
                        $('html, body').animate({ scrollTop: scrollTo }, 800);
                    }
                },
                true
            );

            $(document).on('click.pvwSync', '.wd-swatches-product[data-id*="trade_in"] .wd-swatch, .wd-swatches-product[data-id*="awcdp_deposit_option"] .wd-swatch', function () {
                try {
                    console.log('PVW selection changed', { form: !!$form.length });
                } catch (e) {}
                updateSwatches($form);
            });

            // Mark selects as user-touched on real interaction
            $widget.off('mousedown.userTouch touchstart.userTouch', '.variations select').on('mousedown.userTouch touchstart.userTouch', '.variations select', function () {
                $(this).attr('data-user-touched', '1').data('userTouched', true);
            });

            // Variation form is already initialized; avoid duplicate triggers to improve performance
        });
    }

    // Initialize on page load
    initVariationWidget();

    // Re-initialize when Elementor frontend loads widgets
    $(window).on('elementor/frontend/init', function () {
        initVariationWidget();
    });

    // Re-initialize on Woo fragments events
    $(document.body).on('wc_fragments_loaded wc_fragments_refreshed updated_wc_div', function () {
        initVariationWidget();
    });

    // Selected value row removed; function no longer needed

    // Handle clear button clicks - consolidated with form-specific handler

    // Handle dropdown changes (fallback for non-swatch attributes)
    $(document).on('change', 'select[data-attribute_name]', function () {
        var $select = $(this);
        var selectedValue = $select.val();
        var attributeName = $select.attr('name');
        var $widget = $select.closest('.product-variation-widget, .sh-variation-form');
        var $form = $select.closest('.variations_form');

        // Clear cache when variation changes to ensure fresh stock data
        if ($form.length) {
            clearVariationCache($form);
        }

        // Sync with main form if this is within a variation widget
        if ($widget.length) {
            var $mainForm = $widget.data('main-form');
            if ($mainForm && $mainForm.length && attributeName) {
                var $mainSelect = $mainForm.find('select[name="' + attributeName + '"]');
                if ($mainSelect.length && $mainSelect.val() !== selectedValue) {
                    $mainSelect.val(selectedValue);

                    setTimeout(function () {
                        var $widgetForm = $widget.find('.variations_form');
                        var widgetVariationId = $widgetForm.find('input[name="variation_id"]').val();
                        if (widgetVariationId && widgetVariationId !== '0') {
                            $mainForm.find('input[name="variation_id"]').val(widgetVariationId);

                            var variationData = $widgetForm.data('variation-data');
                            if (variationData) {
                                var completeVariation = $.extend({}, variationData);

                                if (variationData.is_in_stock && variationData.stock_quantity !== undefined) {
                                    completeVariation.stock_quantity = variationData.stock_quantity;
                                }
                                if (variationData.max_qty !== undefined) {
                                    completeVariation.max_qty = variationData.max_qty;
                                }

                                $mainForm.trigger('variation_data_synced', [completeVariation]);
                            }
                        }
                    }, 0);
                }
            }
        }

        var mv = findMatchingVariation($form);
        if (mv) {
            $form.trigger('found_variation', [mv]);
        }
    });

    // Handle WooCommerce variation changes to clear cache
    $(document).on('found_variation', '.variations_form', function (event, variation) {
        var $form = $(this);
        clearVariationCache($form);
        updateSwatches($form);
    });

    // Handle variation reset to clear cache
    $(document).on('reset_data', '.variations_form', function () {
        var $form = $(this);
        clearVariationCache($form);
        updateSwatches($form);
    });
});
