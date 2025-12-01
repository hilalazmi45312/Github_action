jQuery(function($){
    'use strict';

    // S-Coin Variation Handler Class for Frontend
    class SCoinVariationHandler {
        constructor() {
            this.scoinWidgets = [];
            this.init();
        }

        init() {
            this.findSCoinWidgets();
            this.bindEvents();
            this.updateIconHeights();
            this.initializeSCoinCentering();
            this.showInitialDisplay();
        }
        
        updateIconHeights() {
            // Update --icon-height CSS variable for all S-coin widgets
            $('.sh-scoin-label').each(function() {
                const $widget = $(this);
                const $img = $widget.find('img');
                
                if ($img.length) {
                    const imgHeight = $img.height() || $img.attr('height') || 80;
                    $widget.css('--icon-height', imgHeight + 'px');
                    $widget.closest('.elementor-widget-container').css('--icon-height', imgHeight + 'px');
                    
                    // Update on image load
                    $img.on('load', function() {
                        const newHeight = $(this).height() || imgHeight;
                        $widget.css('--icon-height', newHeight + 'px');
                        $widget.closest('.elementor-widget-container').css('--icon-height', newHeight + 'px');
                    });
                }
            });
        }

        findSCoinWidgets() {
            const elements = $('.sh-scoin-label');
            
            elements.each((index, element) => {
                const $widget = $(element);
                const hasParentScoinData = $widget.data('has-parent-scoin');
                const hasParentScoin = hasParentScoinData === true || hasParentScoinData === 'true';
                // Variations presence or percent mapping (from data-variations)
                let variations = {};
                const variationsAttr = $widget.attr('data-variations');
                try {
                    variations = variationsAttr ? JSON.parse(variationsAttr) : ($widget.data('variations') || {});
                } catch (e) {
                    variations = $widget.data('variations') || {};
                }
                // Parent percent
                const parentPercent = parseFloat($widget.attr('data-parent-percent')) || 0;
                // Variation percents map
                let variationPercents = {};
                const vpAttr = $widget.attr('data-variation-percents');
                try {
                    variationPercents = vpAttr ? JSON.parse(vpAttr) : {};
                } catch (e) {
                    variationPercents = {};
                }
                

                
                this.scoinWidgets.push({
                    element: $widget,
                    hasParentScoin: hasParentScoin,
                    variations: variations,
                    parentPercent: parentPercent,
                    variationPercents: variationPercents
                });
            });
            

        }

        bindEvents() {
            // Listen for user-driven variation changes
            $(document).on('reset_data', this.handleVariationReset.bind(this));
            
            // Listen for window resize to update icon heights
            $(window).on('resize', () => {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    this.updateIconHeights();
                }, 250);
            });
            
            // Listen for variation form changes
            $('.variations_form').on('change', 'select', this.handleVariationFormChange.bind(this));
            
            // Listen for WooCommerce variation has changed event
            $('form.variations_form').on('woocommerce_variation_has_changed', this.handleWooCommerceVariationChange.bind(this));

            // Listen for WooCommerce found_variation to get full variation object
            $('form.variations_form').on('found_variation', this.handleVariationChange.bind(this));
        }

        handleVariationChange(event, variation) {
            const variationId = variation.variation_id;
            this.updateSCoinDisplay(variationId);
        }

        handleVariationReset(event) {
            // When variation is reset, show based on parent or any variation having S-coin
            this.updateSCoinDisplay(null);
        }

        handleVariationFormChange() {
            // Fallback handler for when found_variation doesn't fire
            const $form = $('.variations_form');
            const variationId = $form.find('input[name="variation_id"]').val();
            
            if (variationId) {
                this.updateSCoinDisplay(parseInt(variationId));
            } else {
                this.updateSCoinDisplay(null);
            }
        }

        handleWooCommerceVariationChange() {
            const variationId = $('input.variation_id').val();
            if (variationId) {
                this.updateSCoinDisplay(parseInt(variationId));
            } else {
                this.updateSCoinDisplay(null);
            }
        }

        updateSCoinDisplay(selectedVariationId) {
            
            this.scoinWidgets.forEach((widget, index) => {
                const shouldShow = this.shouldShowWidget(widget, selectedVariationId);
                
                if (shouldShow) {
                    const cashbackValue = this.getCashbackValue(widget, selectedVariationId);

                    this.renderBadge(widget, cashbackValue);
                    widget.element.removeClass('scoin-hidden');
                } else {
                    widget.element.addClass('scoin-hidden');
                }
            });
            
            // Update icon heights after display changes
            this.updateIconHeights();
        }

        shouldShowWidget(widget, selectedVariationId) {
            if (selectedVariationId) {
                const v = (widget.variationPercents && widget.variationPercents[selectedVariationId] !== undefined)
                    ? widget.variationPercents[selectedVariationId]
                    : (widget.variations && widget.variations[selectedVariationId] !== undefined)
                        ? widget.variations[selectedVariationId]
                        : 0;
                return parseFloat(v) > 0;
            }
            return widget.hasParentScoin;
        }

        getCashbackValue(widget, selectedVariationId) {
            if (selectedVariationId) {
                if (widget.variationPercents && widget.variationPercents[selectedVariationId] !== undefined) {
                    return parseFloat(widget.variationPercents[selectedVariationId]);
                }
                if (widget.variations && widget.variations[selectedVariationId] !== undefined) {
                    return parseFloat(widget.variations[selectedVariationId]);
                }
                return 0;
            }
            return parseFloat(widget.parentPercent) || 0;
        }

        initializeSCoinCentering() {
            $('.sh-scoin-label .rebate-info').each(function() {
                const $rebateInfo = $(this);
                const $number = $rebateInfo.find('.percentage-number');
                if ($number.length) {
                    const txt = $number.text();
                    const digitsCount = (txt.match(/\d/g) || []).length;
                    const val = parseFloat(txt);
                    const isSingleDigit = digitsCount <= 2 && val > 0;
                    const isTripleDigit = digitsCount >= 3;
                    $number.attr('data-single-digit', isSingleDigit ? 'true' : 'false');
                    $number.attr('data-triple-digit', isTripleDigit ? 'true' : 'false');
                    $rebateInfo.removeClass('scoin-widget-single-digit scoin-widget-multi-digit');
                    if (isSingleDigit) {
                        $rebateInfo.addClass('scoin-widget-single-digit');
                    } else {
                        $rebateInfo.addClass('scoin-widget-multi-digit');
                    }
                }
            });
        }

        showInitialDisplay() {
            // Show initial display on page load
            if ($('body').hasClass('single-product')) {
                this.updateSCoinDisplay(null);
            }
        }

        renderBadge(widget, value) {
            const rebateInfo = widget.element.find('.rebate-info');
            if (rebateInfo.length && !isNaN(value) && value > 0) {
                const percentageNumber = rebateInfo.find('.percentage-number');
                const percentageSymbol = rebateInfo.find('.percentage-symbol');
                const valStr = String(value);
                const digitsCount = (valStr.match(/\d/g) || []).length;
                const val = parseFloat(value);
                const isSingleDigit = digitsCount <= 2 && val > 0;
                const isTripleDigit = digitsCount >= 3;
                if (percentageNumber.length && percentageSymbol.length) {
                    percentageNumber.text(value);
                    percentageSymbol.text('%');
                    percentageNumber.attr('data-single-digit', isSingleDigit ? 'true' : 'false');
                    percentageNumber.attr('data-triple-digit', isTripleDigit ? 'true' : 'false');
                    rebateInfo.removeClass('scoin-widget-single-digit scoin-widget-multi-digit');
                    if (isSingleDigit) {
                        rebateInfo.addClass('scoin-widget-single-digit');
                    } else {
                        rebateInfo.addClass('scoin-widget-multi-digit');
                    }
                } else {
                    const singleDigitAttr = isSingleDigit ? ' data-single-digit="true"' : '';
                    const tripleDigitAttr = isTripleDigit ? ' data-triple-digit="true"' : '';
                    rebateInfo.html('<span class="percentage-number"' + singleDigitAttr + tripleDigitAttr + '>' + value + '</span><span class="percentage-symbol">%</span>');
                    rebateInfo.removeClass('scoin-widget-single-digit scoin-widget-multi-digit');
                    if (isSingleDigit) {
                        rebateInfo.addClass('scoin-widget-single-digit');
                    } else {
                        rebateInfo.addClass('scoin-widget-multi-digit');
                    }
                }
            }
        }

        // Public method to refresh widgets (useful for dynamic content)
        refresh() {
            this.scoinWidgets = [];
            this.findSCoinWidgets();
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize variation handler for frontend
        window.sCoinVariationHandler = new SCoinVariationHandler();
    });

    // Re-initialize variation handler on AJAX content updates
    $(document).on('updated_wc_div', function() {
        if (window.sCoinVariationHandler) {
            window.sCoinVariationHandler.refresh();
        }
    });

});
