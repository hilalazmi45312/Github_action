jQuery(function($){
    'use strict';

    function formatAmount(num, prefix) {
        try {
            var formatted = Number(num).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            return (prefix || '') + formatted;
        } catch (e) {
            return (prefix || '') + num;
        }
    }

    function extractCurrencyPrefixFromHtml(html) {
        if (!html) return '';
        var $tmp = $('<div>').html(html);
        var text = $tmp.text().trim();
        var m = text.match(/^\D+/);
        return m ? m[0] : '';
    }

    function getCurrencyPrefixFallback() {
        // Try multiple sources to get currency prefix
        var $amt = $('.summary .price .woocommerce-Price-amount').first();
        if ($amt.length) {
            var t = $amt.text();
            var m = t.match(/^\D+/);
            if (m && m[0]) return m[0];
        }
        
        var $price = $('.summary .price').first();
        if ($price.length) {
            var m2 = $price.text().trim().match(/^\D+/);
            if (m2 && m2[0]) return m2[0];
        }
        
        // Try to get from WooCommerce settings
        if (typeof wc_price_format !== 'undefined' && wc_price_format.currency) {
            return wc_price_format.currency;
        }
        
        // Default fallback
        return 'RM';
    }

    // Function to update price widget from variation
    function updatePriceFromVariation(variation) {
        var $widget = $('.sh-price-widget').first();
        if (!$widget.length || !variation) return;

        var priceHtml = variation.price_html || '';
        var showSave = $widget.data('show-save') !== false;

        // Prefer numeric prices from WC to ensure correctness
        var currentNum = typeof variation.display_price !== 'undefined' ? parseFloat(variation.display_price) : NaN;
        var regularNum = typeof variation.display_regular_price !== 'undefined' ? parseFloat(variation.display_regular_price) : NaN;

        // Determine currency prefix - try multiple sources
        var currencyPrefix = '';
        
        // First try to get from the variation price_html
        if (priceHtml) {
            currencyPrefix = extractCurrencyPrefixFromHtml(priceHtml);
        }
        
        // If no currency found in price_html, try fallback
        if (!currencyPrefix) {
            currencyPrefix = getCurrencyPrefixFallback();
        }
        
        // Ensure we have a currency prefix
        if (!currencyPrefix) {
            currencyPrefix = 'RM';
        }

        if (!isNaN(currentNum)) {
            // We have reliable numbers from WC
            var formattedCurrent = formatAmount(currentNum, currencyPrefix);
            $widget.find('.sh-price-current').text(formattedCurrent);

            if (!isNaN(regularNum) && regularNum > currentNum) {
                var formattedRegular = formatAmount(regularNum, currencyPrefix);
                $widget.find('.sh-price-regular').text(formattedRegular).show();
                if (showSave) {
                    var diff = (regularNum - currentNum).toFixed(2);
                    var formattedDiff = formatAmount(diff, currencyPrefix);
                    $widget.find('.sh-price-save').text('Save ' + formattedDiff).show();
                } else {
                    $widget.find('.sh-price-save').hide();
                }
            } else {
                $widget.find('.sh-price-regular').hide();
                $widget.find('.sh-price-save').hide();
            }
            return;
        }

        // Fallback: parse HTML
        if (priceHtml) {
            var $tmp = $('<div>').html(priceHtml);
            var $ins = $tmp.find('ins .amount').first().length ? $tmp.find('ins').first() : $tmp.find('.price').first();
            var $del = $tmp.find('del').first();

            var current = $ins.length ? $ins.text() : $tmp.text();
            var regular = $del.length ? $del.text() : '';

            // Ensure current price has currency prefix
            if (current && !current.match(/^\D+/)) {
                current = currencyPrefix + current;
            }
            
            $widget.find('.sh-price-current').text(current);
            
            if (regular) {
                // Ensure regular price has currency prefix
                if (regular && !regular.match(/^\D+/)) {
                    regular = currencyPrefix + regular;
                }
                
                $widget.find('.sh-price-regular').text(regular).show();
                var regNum = parseFloat((regular || '').replace(/[^0-9.\-]/g, ''));
                var curNum = parseFloat((current || '').replace(/[^0-9.\-]/g, ''));
                if (!isNaN(regNum) && !isNaN(curNum) && regNum > curNum && showSave) {
                    var diff2 = (regNum - curNum).toFixed(2);
                    var formattedDiff2 = formatAmount(diff2, currencyPrefix);
                    $widget.find('.sh-price-save').text('Save ' + formattedDiff2).show();
                } else {
                    $widget.find('.sh-price-save').hide();
                }
            } else {
                $widget.find('.sh-price-regular').hide();
                $widget.find('.sh-price-save').hide();
            }
        }
    }

    // Initialize price widget
    function initPriceWidget() {
        $(document).off('found_variation.priceWidget').on('found_variation.priceWidget', 'form.variations_form', function(event, variation) {
            updatePriceFromVariation(variation);
        });

        $(document).off('reset_data.priceWidget').on('reset_data.priceWidget', 'form.variations_form', function() {
            var $price = $('.summary .price').first();
            if ($price.length) {
                updatePriceFromVariation({ price_html: $price.html() });
            }
        });

        // Check if we need to initialize with current variation
        $('form.variations_form').each(function() {
            var $form = $(this);
            var variationData = $form.data('product_variations');
            if (variationData && variationData.length) {
                // Check if any variation is currently selected
                var selectedVariation = null;
                var $variationId = $form.find('.variation_id');
                if ($variationId.val() && $variationId.val() !== '0') {
                    selectedVariation = variationData.find(function(v) {
                        return v.variation_id == $variationId.val();
                    });
                }
                
                if (selectedVariation) {
                    updatePriceFromVariation(selectedVariation);
                }
            }
        });
    }

    // Initialize on DOM ready
    initPriceWidget();

    // Re-initialize on AJAX content load
    $(document).on('woocommerce_variation_has_changed', initPriceWidget);

    // Elementor editor/preview compatibility
    $(window).on('elementor/frontend/init', function() {
        initPriceWidget();
        
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/sh_product_price.default', function($scope) {
                initPriceWidget();
            });
        }
    });
});
