/**
 * Checkout Animation Handler
 * Handles Funnel Builder (WFACP) skeleton loading animation
 */
(function ($) {
    'use strict';

    // Selectors for sections that should show skeleton animation
    var animatedSections = [
        '.woocommerce-checkout-payment',
        '.woocommerce-shipping-totals',
        '.woocommerce-shipping-methods',
        '#shipping_method',
        '.wc-local-pickup-plus-package-pickup-location-field',
        '#order_review'
    ].join(', ');

    // Helper function to add animation classes
    function addAnimationClasses() {
        if (!$('body').hasClass('wfacp_anim_active')) {
            $('body').addClass('wfacp_anim_active');
        }
        $(animatedSections).addClass('wfacp_anim');
    }

    // Helper function to remove animation classes
    function removeAnimationClasses() {
        $('body').removeClass('wfacp_anim_active');
        $(animatedSections).removeClass('wfacp_anim');
    }

    // Wait for DOM ready
    $(function () {
        // Don't add animation on initial page load - it should only appear during AJAX updates
        // The animation will be triggered by update_checkout event when checkout updates

        // Add animation class when checkout is updating
        $(document.body).on('update_checkout', function () {
            window.refresh_page_data_load_trigger = true;
            addAnimationClasses();
        });

        // Remove animation class when checkout is updated or has an error
        $(document.body).on('updated_checkout checkout_error', function () {
            removeAnimationClasses();
        });

        // Bind to ajaxSend to catch Local Pickup Plus and other WooCommerce AJAX calls
        // that trigger update_order_review but don't fire the update_checkout event first
        $(document).on('ajaxSend', function (event, xhr, settings) {
            // Check if this is a WooCommerce checkout-related AJAX call
            if (settings.url && settings.data) {
                var isCheckoutAjax = false;

                // Check for update_order_review AJAX (direct WooCommerce call)
                if (settings.url.indexOf('update_order_review') !== -1) {
                    isCheckoutAjax = true;
                }

                // Check for Local Pickup Plus AJAX calls that affect checkout
                if (typeof settings.data === 'string') {
                    if (settings.data.indexOf('wc_local_pickup_plus_set_package_items_handling') !== -1 ||
                        settings.data.indexOf('wc_local_pickup_plus_set_package_handling') !== -1 ||
                        settings.data.indexOf('wc_local_pickup_plus_set_cart_item_handling') !== -1) {
                        isCheckoutAjax = true;
                    }
                }

                // Add animation for checkout-related AJAX
                if (isCheckoutAjax) {
                    addAnimationClasses();
                }
            }
        });

        // Fix Local Pickup Plus Select2 width (plugin sets fixed pixel width from parent td)
        function fixSelect2Width() {
            $('.wc-local-pickup-plus-package-pickup-location-field .select2-container').css('width', '100%');
            $('table.lpp-shipping-package-wrapper .select2-container').css('width', '100%');
        }

        // Fix on page load
        fixSelect2Width();

        // Fix after checkout AJAX updates
        $(document.body).on('updated_checkout', function () {
            setTimeout(fixSelect2Width, 100);
        });
    });

})(jQuery);
