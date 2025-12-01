jQuery(document).ready(function ($) {

    const SubscriptionDiscounts = function () {

        this.settings = subscriptionDiscountsData.settings;
        this.productType = subscriptionDiscountsData.product_type;

        this.init = function () {

            if (this.settings !== undefined) {

                if (this.productType === 'variable-subscription' || this.productType === 'variable') {
                    $(".single_variation_wrap").on("show_variation", this.loadVariationTable.bind(this));

                    $(document).on('reset_data', function () {
                        $('[data-variation-discounts-for-subscriptions-table]').html('');
                    });
                }
            }
        };

        this.loadVariationTable = function (event, variation) {

            $.post(document.location.origin + document.location.pathname + '?wc-ajax=get_discounts_table', {
                variation_id: variation['variation_id'],
                nonce: subscriptionDiscountsData.load_table_nonce
            }, (function (response) {
                $('.data-discounts-table-container').remove();
                $('[data-variation-discounts-for-subscriptions-table]').html(response);
            }).bind(this));
        };
    };

    document.subscriptionsDiscounts = new SubscriptionDiscounts();

    document.subscriptionsDiscounts.init();
});