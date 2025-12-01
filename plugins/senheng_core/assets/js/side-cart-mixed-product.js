/**
 * Side Cart Mixed Product Validation (Footer Notice Persistent Version)
 * Shows notice in `.shopping-cart-widget-footer` and keeps checkout button state persistent.
 * Maintains loading state throughout the entire AJAX sequence.
 * FIXED: Prevents fragment refresh from resetting notice and loading state
 */
jQuery(document).ready(function($) {

    var config = {
        debounceDelay: 100,
        messages: {
            mixed: 'You cannot combine normal and ESD products together. Please adjust your cart to proceed.',
            depositMixed: 'You cannot combine partial payment items with normal products. Please adjust your cart to proceed.',
            depositEsd: 'You cannot combine partial payment items with ESD products. Please adjust your cart to proceed.',
            empty: '🛒 Your cart is empty'
        },
        selectors: {
            sideCart: '.wd-dropdown-cart, .woocommerce-mini-cart-wrapper, .elementor-menu-cart__wrapper',
            cartContent: '.widget_shopping_cart_content, .woocommerce-mini-cart, .mini-cart-content',
            checkoutBtn: '.button.checkout, .checkout-button, .elementor-button[href*="checkout"]:not([href*="view-cart"]), .proceed-to-checkout',
            footer: '.shopping-cart-widget-footer, .widget-footer, .cart-footer, .mini-cart-footer'
        }
    };

    // Test selectors on initial load

    var lastResult = null;
    var currentState = 'idle'; // 'idle' | 'loading' | 'mixed' | 'valid'
    var validationTimer = null;
    var isValidating = false;
    var pendingValidations = 0; // Track how many validations are pending
    var isRemovingFromCart = false; // Track if remove action is in progress
    var isAddingToCart = false; // Track if add action is in progress
    var awaitingFragmentRefresh = false; // NEW: Track if we're waiting for fragment refresh

    /**
     * Get container to insert notice before cart total
     */
    function getNoticeContainer() {
        // First try to find the cart total element
        var $cartTotal = $(config.selectors.cartContent).find('.woocommerce-mini-cart__total, .total');
        
        if ($cartTotal.length > 0) {
            return $cartTotal.parent(); // Return the parent container
        }
        
        // Fallback to footer container
        var $container = $(config.selectors.cartContent).find(config.selectors.footer).first();
        return $container;
    }

    /**
     * Render notice above cart total with WooCommerce styling
     */
    function renderFooterNotice(message, type) {
        var $container = getNoticeContainer();
        
        if (!$container || $container.length === 0) {
            return;
        }

        // Remove existing notices
        $container.find('.sh-mixed-products-notice').remove();

        // Find the cart total element to insert before it
        var $cartTotal = $container.find('.woocommerce-mini-cart__total, .total');
        
        // Create WooCommerce-style notice
        var noticeClass = 'woocommerce-message';
        var iconClass = 'woocommerce-message';
        
        if (type === 'error') {
            noticeClass = 'woocommerce-error';
            iconClass = 'woocommerce-error';
        } else if (type === 'info') {
            noticeClass = 'woocommerce-info';
            iconClass = 'woocommerce-info';
        }

        var $notice = $('<div>', {
            class: 'sh-mixed-products-notice ' + noticeClass,
            html: message,
            css: {
                'padding': '1em 1.5em',
                'border-left': '4px solid',
                'border-radius': '2px',
                'font-size': '12px',
                'line-height': '1.4',
                'position': 'relative'
            }
        });

        // Force display with !important using attr method
        $notice[0].style.setProperty('display', 'block', 'important');
        $notice[0].style.setProperty('visibility', 'visible', 'important');

        // Apply type-specific styling to match WooCommerce
        if (type === 'error') {
            $notice.css({
                'background-color': '#fbeaea',
                'border-left-color': '#dc3232',
                'color': '#721c24'
            });
        } else if (type === 'info') {
            $notice.css({
                'background-color': '#e5f7ff',
                'border-left-color': '#00a0d2',
                'color': '#0073aa'
            });
        } else {
            // Default success/message styling
            $notice.css({
                'background-color': '#ecf7ed',
                'border-left-color': '#46b450',
                'color': '#155724'
            });
        }

        // Insert before cart total if found, otherwise append to container
        if ($cartTotal.length > 0) {
            $cartTotal.before($notice);
        } else {
            $container.append($notice);
        }
    }

    /**
     * Find checkout button inside refreshed fragment
     */
    function getCheckoutButton() {
        return $(config.selectors.cartContent).find(config.selectors.checkoutBtn);
    }

    /**
     * Toggle checkout button state with loading indicator
     */
    function applyCheckoutState(disable, showLoading = false) {
        var $btn = getCheckoutButton();
        if (!$btn.length) return;

        if (disable) {
            $btn.addClass('sh-disabled-checkout')
                .attr('disabled', true);
                
            if (showLoading) {
                // Store original text and show loading
                if (!$btn.data('original-text')) {
                    $btn.data('original-text', $btn.text());
                }
                
                // Create modern loading spinner
                var loadingSpinner = '<span class="sh-loading-spinner" style="display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: sh-spin 1s ease-in-out infinite; margin-right: 8px;"></span>';
                
                $btn.html(loadingSpinner + 'Loading...')
                    .css({
                        'opacity': '0.8',
                        'cursor': 'wait',
                        'pointer-events': 'none'
                    });
                
                // Add CSS animation if not already present
                if (!$('style#sh-loading-animation').length) {
                    $('<style id="sh-loading-animation">@keyframes sh-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
                }
            } else {
                // Restore original text if it was stored
                if ($btn.data('original-text')) {
                    $btn.text($btn.data('original-text'));
                    $btn.removeData('original-text');
                }
                $btn.css({
                    'opacity': '0.5',
                    'cursor': 'not-allowed',
                    'pointer-events': 'none'
                });
            }
        } else {
            // Restore original text if it was stored
            if ($btn.data('original-text')) {
                $btn.text($btn.data('original-text'));
                $btn.removeData('original-text');
            }
            
            $btn.removeClass('sh-disabled-checkout')
                .removeAttr('disabled')
                .css({
                    'opacity': '1',
                    'cursor': 'pointer',
                    'pointer-events': 'auto'
                });
        }
    }

    /**
     * AJAX validation with pending counter
     */
    function checkMixedProducts() {
        pendingValidations++;
        isValidating = true;
        
        var ajaxUrl = wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'check_mixed_products');

        return new Promise(function(resolve) {
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    pendingValidations--;
                    
                    if (response && response.success) {
                        lastResult = {
                            hasMixed: response.data.has_mixed_products || false,
                            depositMixed: response.data.has_deposit_mixed || false,
                            depositEsd: response.data.has_deposit_esd || false,
                            cartCount: response.data.cart_count || 0
                        };
                        resolve(lastResult);
                    } else {
                        resolve({hasMixed: false, cartCount: 0});
                    }
                    
                    // Only mark as not validating if no pending validations
                    if (pendingValidations <= 0) {
                        isValidating = false;
                    }
                },
                error: function() {
                    pendingValidations--;
                    resolve({hasMixed: false, cartCount: 0});
                    
                    // Only mark as not validating if no pending validations
                    if (pendingValidations <= 0) {
                        isValidating = false;
                    }
                }
            });
        });
    }

    /**
     * Update footer notice + button state based on validation
     */
    function updateUI(result) {
        if (result.cartCount === 0) {
            $(config.selectors.cartContent).find('.sh-mixed-products-notice').remove();
            applyCheckoutState(false);
            currentState = 'valid';
            return;
        }

        // Prefer the combined ESD+deposit message when both types are present
        if (result.depositEsd) {
            renderFooterNotice(config.messages.depositEsd, 'error');
            applyCheckoutState(true);
            currentState = 'mixed';
        } else if (result.depositMixed) {
            renderFooterNotice(config.messages.depositMixed, 'error');
            applyCheckoutState(true);
            currentState = 'mixed';
        } else if (result.hasMixed) {
            renderFooterNotice(config.messages.mixed, 'error');
            applyCheckoutState(true);
            currentState = 'mixed';
        } else {
            $(config.selectors.cartContent).find('.sh-mixed-products-notice').remove();
            applyCheckoutState(false);
            currentState = 'valid';
        }
    }

    /**
     * NEW: Reapply current state after fragment refresh
     * This prevents the fragment refresh from resetting our changes
     */
    function reapplyCurrentState() {
        if (currentState === 'loading') {
            applyCheckoutState(true, true);
        } else if (currentState === 'mixed' && lastResult) {
            // Reapply the notice and disabled state for the specific mixed case
            if (lastResult.depositMixed && lastResult.hasMixed) {
                var combinedMsg = '<p>' + config.messages.mixed + '</p>' +
                                  '<p>' + config.messages.depositMixed + '</p>';
                renderFooterNotice(combinedMsg, 'error');
            } else if (lastResult.depositMixed) {
                renderFooterNotice(config.messages.depositMixed, 'error');
            } else {
                renderFooterNotice(config.messages.mixed, 'error');
            }
            applyCheckoutState(true);
        } else if (currentState === 'valid') {
            // Ensure button is enabled
            applyCheckoutState(false);
        }
    }

    /**
     * Debounced validation
     */
    function debouncedValidation() {
        clearTimeout(validationTimer);
        validationTimer = setTimeout(immediateValidation, config.debounceDelay);
    }

    /**
     * Immediate validation
     */
    function immediateValidation() {
        currentState = 'loading';
        applyCheckoutState(true, true); // Show loading in button
        checkMixedProducts().then(updateUI);
    }

    // =============================================================
    // EVENT FLOW
    // =============================================================

    // 1️⃣ Intercept remove_from_cart AJAX START - Set flag and show loading
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('wc-ajax=remove_from_cart') !== -1) {
            isRemovingFromCart = true;
            awaitingFragmentRefresh = true; // NEW: Mark that we're awaiting refresh
            applyCheckoutState(true, true); // Show loading in button
            currentState = 'loading';
        }
        // Handle add_to_cart operations (WooCommerce and Woodmart)
        if (settings.url && (settings.url.indexOf('wc-ajax=add_to_cart') !== -1 || 
            (typeof settings.data === 'string' && settings.data.indexOf('action=woodmart_ajax_add_to_cart') !== -1))) {
            isAddingToCart = true;
            awaitingFragmentRefresh = true; // NEW: Mark that we're awaiting refresh
            applyCheckoutState(true, true); // Show loading in button
            currentState = 'loading';
        }
    });

    // 2️⃣ Intercept AJAX COMPLETE for both remove and add operations
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.indexOf('wc-ajax=remove_from_cart') !== -1) {
            // Validation will happen in fragment refresh handler
        }
        if (settings.url && (settings.url.indexOf('wc-ajax=add_to_cart') !== -1 || 
            (typeof settings.data === 'string' && settings.data.indexOf('action=woodmart_ajax_add_to_cart') !== -1))) {
            // Validation will happen in fragment refresh handler
        }
    });

    // 3️⃣ Fragment refresh handlers - maintain loading until validation complete
    $(document).on('wc_fragment_refresh added_to_cart woodmart_ajax_add_to_cart', function() {
        awaitingFragmentRefresh = true; // NEW: Mark that we're awaiting refresh
        applyCheckoutState(true, true); // Show loading in button
        currentState = 'loading';
    });

    // 4️⃣ FIXED: Handle fragment refresh completion
    $(document).on('wc_fragments_refreshed wc_fragments_loaded', function() {
        awaitingFragmentRefresh = true;
        applyCheckoutState(true, true); 
        // Always validate after fragments refresh
        checkMixedProducts().then(function(result) {
            updateUI(result);
            // Clear both flags after validation is complete
            isRemovingFromCart = false;
            isAddingToCart = false;
            awaitingFragmentRefresh = false;
        });
    });

    // 5️⃣ Optional: detect quantity changes
    $(document).on('change', '.woocommerce-mini-cart .qty, .widget_shopping_cart .qty', debouncedValidation);
    
    // 6️⃣ NEW: Handle cart opening - reapply state in case it was lost
    $(document).on('click', config.selectors.sideCart, function() {
        // Small delay to let cart open animation complete
        setTimeout(reapplyCurrentState, 100);
    });

    // 7️⃣ Initial validation
    if ($('.woocommerce-mini-cart-item, .mini_cart_item').length > 0) {
        immediateValidation();
    }
});