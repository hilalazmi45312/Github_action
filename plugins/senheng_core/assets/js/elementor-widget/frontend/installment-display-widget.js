/**
 * Installment Display Widget JavaScript
 * Optimized for performance with cached selectors and pre-parsed data
 */
(function ($) {
    'use strict';

    // Exit early if no widget on page
    var $widgets = $('.sh-installment-display');
    if (!$widgets.length) return;

    // Cache widget references and pre-parse data
    var widgetData = [];
    $widgets.each(function () {
        var $widget = $(this);
        var plansData = $widget.data('plans');
        var plans = typeof plansData === 'string' ? JSON.parse(plansData) : plansData;

        // Pre-sort tenures descending for faster lookup
        var sortedTenures = plans ? Object.keys(plans).map(Number).sort(function (a, b) { return b - a; }) : [];

        widgetData.push({
            $el: $widget,
            $price: $widget.find('.sh-installment-price'),
            $tenure: $widget.find('.sh-installment-tenure'),
            plans: plans,
            sortedTenures: sortedTenures,
            showTenure: $widget.data('show-tenure') === 'yes',
            productType: $widget.data('product-type'),
            initialPrice: $widget.find('.sh-installment-price').text(),
            initialTenure: $widget.find('.sh-installment-tenure').text()
        });
    });

    /**
     * Find lowest monthly using pre-sorted tenures (longest first = lowest monthly)
     */
    function findLowestInstallment(data, price) {
        var plans = data.plans;
        var tenures = data.sortedTenures;

        // Iterate from longest tenure (lowest monthly) first
        for (var i = 0; i < tenures.length; i++) {
            var months = tenures[i];
            var minAmount = plans[months] || 0;

            if (price >= minAmount && months > 0) {
                return {
                    monthly: price / months,
                    tenure: months
                };
            }
        }
        return null;
    }

    /**
     * Update widget display with cached DOM references
     */
    function updateWidget(data, price) {
        if (price <= 0 || !data.plans) {
            data.$el.addClass('sh-no-plan');
            return;
        }

        var result = findLowestInstallment(data, price);

        if (!result) {
            data.$el.addClass('sh-no-plan');
            return;
        }

        data.$el.removeClass('sh-no-plan');
        data.$price.text('RM' + result.monthly.toFixed(2));

        if (data.showTenure) {
            data.$tenure.text('(x' + result.tenure + ')');
        }
    }

    // Single event binding for variation form
    var $variationForm = $('form.variations_form');
    if ($variationForm.length) {
        $variationForm.on('found_variation', function (e, variation) {
            var price = parseFloat(variation.display_price) || 0;
            for (var i = 0; i < widgetData.length; i++) {
                updateWidget(widgetData[i], price);
            }
        });

        $variationForm.on('reset_data', function () {
            for (var i = 0; i < widgetData.length; i++) {
                var data = widgetData[i];
                if (data.productType === 'variable') {
                    data.$price.text(data.initialPrice);
                    data.$tenure.text(data.initialTenure);
                    data.$el.removeClass('sh-no-plan');
                }
            }
        });
    }

    // Keyboard accessibility for info icon (Enter/Space triggers click)
    $(document).on('keydown', '.sh-installment-info-icon', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            $(this).trigger('click');
        }
    });

})(jQuery);
