/**
 * Payment Gateway Frontend JavaScript
 * 
 * @package Senheng_Core
 * @version 1.0.0
 * @author Senheng Core Team
 */

(function($) {
    'use strict';

    // Payment Gateway Frontend Class
    class PaymentGatewayFrontend {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initPaymentForm();
            this.createProcessingOverlay();
        }

        bindEvents() {
            // Payment method selection
            $(document.body).on('payment_method_selected', this.handlePaymentMethodSelected.bind(this));
            
            // Checkout form submission
            $(document.body).on('checkout_error', this.handleCheckoutError.bind(this));
            
            // Place order button click
            $(document.body).on('click', '#place_order', this.handlePlaceOrder.bind(this));
            
            // iPay88 form submission
            $(document.body).on('submit', '#ipay88_payment_form', this.handleIPay88Submit.bind(this));
        }

        handlePaymentMethodSelected(e, paymentMethod) {
            if (paymentMethod === 'ipay88') {
                this.showIPay88Info();
            } else {
                this.hideIPay88Info();
            }
        }

        showIPay88Info() {
            // Add iPay88 specific information
            if (!$('.ipay88-info').length) {
                const infoHtml = `
                    <div class="ipay88-info">
                        <div class="ipay88-logo">
                            <span class="payment-method-icon"></span>
                            <strong>iPay88 Payment Gateway</strong>
                        </div>
                        <p>You will be redirected to iPay88's secure payment page to complete your transaction.</p>
                        <div class="ipay88-features">
                            <ul>
                                <li>✓ Secure payment processing</li>
                                <li>✓ Multiple payment methods</li>
                                <li>✓ Instant payment confirmation</li>
                            </ul>
                        </div>
                    </div>
                `;
                
                $('.payment_method_ipay88').append(infoHtml);
            }
        }

        hideIPay88Info() {
            $('.ipay88-info').remove();
        }

        handleCheckoutError(e, errorMessage) {
            // Remove loading state
            this.hideProcessingOverlay();
            $('.woocommerce-checkout').removeClass('loading');
            
            // Show error message
            this.showErrorMessage(errorMessage);
        }

        handlePlaceOrder(e) {
            const selectedPaymentMethod = $('input[name="payment_method"]:checked').val();
            
            if (selectedPaymentMethod === 'ipay88') {
                // Prevent default form submission
                e.preventDefault();
                
                console.log('🚀 iPay88 selected - proceeding without validation');
                
                // Show processing overlay
                this.showProcessingOverlay();
                
                // Submit form via AJAX
                this.submitIPay88Order();
            }
        }

        // validateCheckoutForm() - REMOVED: No longer validates form fields

        submitIPay88Order() {
            const formData = $('.woocommerce-checkout').serialize();
            
            // Debug: Check visible filled fields specifically
            console.log('🔍 Debugging visible filled fields:');
            $('.woocommerce-checkout input:visible, .woocommerce-checkout select:visible').each(function() {
                const $field = $(this);
                const name = $field.attr('name') || 'no-name';
                const value = $field.val() || '';
                if (value.trim()) {
                    console.log(`  ✅ ${name}: "${value}"`);
                }
            });
            
            console.log('🚀 Sending checkout data to iPay88:', formData);
                console.log('🌐 AJAX URL:', wc_checkout_params.ajax_url);
            console.log('🔐 Nonce:', senheng_payment_ajax.nonce);
            
            // TEST MODE - Change this back to 'process_ipay88_payment' after debugging
            $.ajax({
                url: wc_checkout_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'process_ipay88_payment', // REAL ACTION
                    form_data: formData,
                    nonce: senheng_payment_ajax.nonce
                },
                success: (response) => {
                    console.log('📥 AJAX Response received:', response);
                    if (response.success) {
                        console.log('✅ Payment form generated successfully');
                        console.log('🔐 Payment data debug:', {
                            form: response.data.form,
                            order_id: response.data.order_id,
                            payment_data: response.data.payment_data
                        });
                        // Insert payment form and submit
                        this.insertAndSubmitPaymentForm(response.data.form);
                    } else {
                        console.error('❌ Payment processing failed:', response.data);
                        this.handleCheckoutError(null, response.data.message || 'Payment processing failed.');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('🚫 AJAX Error Details:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusText: xhr.statusText,
                        url: xhr.responseURL
                    });
                    this.handleCheckoutError(null, `Network error (${xhr.status}): ${error}`);
                }
            });
        }

        insertAndSubmitPaymentForm(formHtml) {
            // Remove existing form
            $('#ipay88_payment_form').remove();
            
            // Insert new form
            $('body').append(formHtml);
            
            // Submit form
            $('#ipay88_payment_form').submit();
        }

        handleIPay88Submit(e) {
            // Show processing message
            this.showProcessingOverlay();
            
            // Update processing message
            $('.ipay88-processing-text').text('Redirecting to iPay88...');
            $('.ipay88-processing-subtext').text('Please wait while we redirect you to the secure payment page.');
        }

        createProcessingOverlay() {
            const overlayHtml = `
                <div class="ipay88-processing-overlay">
                    <div class="ipay88-processing-content">
                        <div class="ipay88-processing-spinner"></div>
                        <div class="ipay88-processing-text">Processing Payment...</div>
                        <div class="ipay88-processing-subtext">Please do not close this window or refresh the page.</div>
                    </div>
                </div>
            `;
            
            $('body').append(overlayHtml);
        }

        showProcessingOverlay() {
            $('.ipay88-processing-overlay').fadeIn(300);
            $('.woocommerce-checkout').addClass('loading');
        }

        hideProcessingOverlay() {
            $('.ipay88-processing-overlay').fadeOut(300);
            $('.woocommerce-checkout').removeClass('loading');
        }

        showErrorMessage(message) {
            // Remove existing error messages
            $('.woocommerce-error').remove();
            
            // Add new error message
            const errorHtml = `
                <div class="woocommerce-error">
                    ${message}
                </div>
            `;
            
            $('.woocommerce-checkout').prepend(errorHtml);
            
            // Scroll to error
            $('html, body').animate({
                scrollTop: $('.woocommerce-error').offset().top - 100
            }, 500);
        }

        showSuccessMessage(message) {
            // Remove existing messages
            $('.woocommerce-message').remove();
            
            // Add new success message
            const successHtml = `
                <div class="woocommerce-message">
                    ${message}
                </div>
            `;
            
            $('.woocommerce-checkout').prepend(successHtml);
        }

        initPaymentForm() {
            // Payment method icon is handled by WooCommerce gateway
            // No need to add additional icon
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        new PaymentGatewayFrontend();
    });

    // Add CSS for enhanced styling
    const additionalCSS = `
        .ipay88-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #0073aa;
        }
        
        .ipay88-logo {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 16px;
            font-weight: 600;
            color: #23282d;
        }
        
        .ipay88-logo .payment-method-icon {
            /* Icon styling handled by WooCommerce gateway */
            margin-right: 10px;
        }
        
        .ipay88-info p {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .ipay88-features ul {
            margin: 0;
            padding-left: 0;
            list-style: none;
        }
        
        .ipay88-features li {
            margin-bottom: 5px;
            color: #46b450;
            font-size: 13px;
        }
        
        .woocommerce-checkout input.error {
            border-color: #dc3232;
            box-shadow: 0 0 0 1px #dc3232;
        }
        
        .woocommerce-checkout input.error:focus {
            border-color: #dc3232;
            box-shadow: 0 0 0 1px #dc3232;
        }
    `;

    // Inject CSS
    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);

})(jQuery);
