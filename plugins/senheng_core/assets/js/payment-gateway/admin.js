/**
 * Payment Gateway Admin JavaScript
 * 
 * @package Senheng_Core
 * @version 1.0.0
 * @author Senheng Core Team
 */

(function($) {
    'use strict';

    // Payment Gateway Admin Class
    class PaymentGatewayAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.initFormValidation();
            this.initEnvironmentToggle();
            this.initCopyButtons();
        }

        bindEvents() {
            // Form submission
            $('.gateway-config-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Environment change
            $('#ipay88_environment').on('change', this.handleEnvironmentChange.bind(this));
            
            // Enable/disable toggle
            $('#ipay88_enabled').on('change', this.handleEnableToggle.bind(this));
            
            // Copy URL buttons (using event delegation for dynamically created buttons)
            $(document).on('click', '.copy-url-btn', this.handleCopyUrl.bind(this));
            
            // Debug: Log when form is found
            console.log('Payment gateway admin initialized');
            console.log('Forms found:', $('.gateway-config-form').length);
            console.log('Enabled checkbox found:', $('#ipay88_enabled').length);
            console.log('Initial enabled state:', $('#ipay88_enabled').is(':checked'));
        }

        handleFormSubmit(e) {
            const form = $(e.currentTarget);
            const submitBtn = form.find('button[type="submit"]');
            
            console.log('Form submission started');
            
            // Ensure all form fields are enabled before submission
            form.find('input, select, textarea').prop('disabled', false);
            
            // Validate form
            if (!this.validateForm(form)) {
                console.log('Form validation failed');
                e.preventDefault();
                return false;
            }

            console.log('Form validation passed, submitting...');

            // Show loading state
            submitBtn.prop('disabled', true).text('Saving...');
            form.addClass('loading');

            // Allow the form to submit naturally
            // Don't prevent default - let the form submit to the server
        }

        validateForm(form) {
            let isValid = true;
            const errors = [];

            console.log('Validating form...');

            // Clear previous errors
            form.find('.field-error').remove();
            form.find('.config-field').removeClass('error');

            // Check if enabled
            const isEnabled = $('#ipay88_enabled').is(':checked');
            console.log('Gateway enabled:', isEnabled);
            
            // Merchant validation is commented out - no validation required
            // if (isEnabled) {
            //     // Validate merchant key
            //     const merchantKey = $('#ipay88_merchant_key').val().trim();
            //     if (!merchantKey) {
            //         this.showFieldError('#ipay88_merchant_key', 'Merchant Key is required when enabled');
            //         isValid = false;
            //     }

            //     // Validate merchant code
            //     const merchantCode = $('#ipay88_merchant_code').val().trim();
            //     if (!merchantCode) {
            //         this.showFieldError('#ipay88_merchant_key', 'Merchant Code is required when enabled');
            //         isValid = false;
            //     }
            // }

            console.log('Form validation result:', isValid);

            // Show errors if any
            if (!isValid) {
                this.showFormErrors(errors);
            }

            return isValid;
        }

        showFieldError(selector, message) {
            const field = $(selector);
            const errorDiv = $('<div class="field-error">' + message + '</div>');
            
            field.closest('.config-field').addClass('error');
            field.after(errorDiv);
        }

        showFormErrors(errors) {
            if (errors.length > 0) {
                const errorHtml = '<div class="notice notice-error"><p><strong>Please fix the following errors:</strong></p><ul>' + 
                    errors.map(error => '<li>' + error + '</li>').join('') + '</ul></div>';
                
                $('.gateway-header').after(errorHtml);
                
                // Scroll to first error
                $('html, body').animate({
                    scrollTop: $('.field-error').first().offset().top - 100
                }, 500);
            }
        }

        handleEnvironmentChange() {
            const environment = $('#ipay88_environment').val();
            const testInfo = $('.test-info');
            
            if (environment === 'test') {
                testInfo.show();
                this.updateTestCredentials();
            } else {
                testInfo.hide();
            }
        }

        handleEnableToggle() {
            const isEnabled = $('#ipay88_enabled').is(':checked');
            const credentialFields = $('#ipay88_merchant_key, #ipay88_merchant_code');
            
            if (isEnabled) {
                credentialFields.prop('disabled', false);
                $('.credential-section').addClass('enabled');
            } else {
                // Don't disable the fields - just style them differently
                credentialFields.prop('disabled', false);
                $('.credential-section').removeClass('enabled');
            }
        }

        updateTestCredentials() {
            // Update test credentials display
            const testCredentials = {
                'test': {
                    'merchant_code': 'M00067',
                    'merchant_key': 'S-0Q02AwF8F4S6N3X'
                }
            };

            const env = $('#ipay88_environment').val();
            if (testCredentials[env]) {
                $('#test_merchant_code').text(testCredentials[env].merchant_code);
                $('#test_merchant_key').text(testCredentials[env].merchant_key);
            }
        }

        initCopyButtons() {
            // Add copy buttons to URL fields
            $('input[readonly]').each(function(index) {
                const input = $(this);
                const inputValue = input.val();
                
                // Only add copy button if input has a value
                if (inputValue && inputValue.trim() !== '') {
                    // Check if copy button already exists
                    if (input.next('.copy-url-btn').length === 0) {
                        const copyBtn = $('<button type="button" class="copy-url-btn button button-small">Copy</button>');
                        input.after(copyBtn);
                    }
                }
            });
        }

        handleCopyUrl(e) {
            e.preventDefault();
            
            const btn = $(e.currentTarget);
            const input = btn.prev('input');
            const originalText = btn.text();
            const urlToCopy = input.val();
            
            // Ensure we have a valid URL to copy
            if (!urlToCopy || urlToCopy.trim() === '') {
                this.showCopyError(btn, originalText);
                return;
            }
            
            this.simpleCopy(urlToCopy, btn, originalText);
        }
        
        simpleCopy(textToCopy, btn, originalText) {
            // Method 1: Modern Clipboard API
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(textToCopy)
                    .then(() => {
                        this.showCopyFeedback(btn, originalText);
                    })
                    .catch((err) => {
                        this.fallbackCopyMethod(textToCopy, btn, originalText);
                    });
            } else {
                this.fallbackCopyMethod(textToCopy, btn, originalText);
            }
        }
        
        fallbackCopyMethod(textToCopy, btn, originalText) {
            // Create temporary input
            const tempInput = $('<input type="text">');
            tempInput.val(textToCopy);
            tempInput.css({
                position: 'absolute',
                left: '-9999px',
                top: '0'
            });
            
            $('body').append(tempInput);
            tempInput[0].select();
            tempInput[0].setSelectionRange(0, 99999);
            
            try {
                const successful = document.execCommand('copy');
                
                if (successful) {
                    this.showCopyFeedback(btn, originalText);
                } else {
                    this.showCopyError(btn, originalText);
                }
            } catch (err) {
                this.showCopyError(btn, originalText);
            } finally {
                tempInput.remove();
            }
        }


        showCopyFeedback(btn, originalText) {
            btn.text('Copied!').addClass('copied');
            btn.css({
                'background-color': '#46b450',
                'color': 'white',
                'border-color': '#46b450'
            });
            
            setTimeout(() => {
                btn.text(originalText).removeClass('copied');
                btn.css({
                    'background-color': '',
                    'color': '',
                    'border-color': ''
                });
            }, 2000);
        }

        showCopyError(btn, originalText) {
            btn.text('Error!').addClass('error');
            btn.css({
                'background-color': '#dc3232',
                'color': 'white',
                'border-color': '#dc3232'
            });
            
            setTimeout(() => {
                btn.text(originalText).removeClass('error');
                btn.css({
                    'background-color': '',
                    'color': '',
                    'border-color': ''
                });
            }, 2000);
        }



        initFormValidation() {
            // Real-time validation
            $('#ipay88_merchant_key, #ipay88_merchant_code').on('blur', function() {
                const field = $(this);
                const value = field.val().trim();
                
                if (value && field.hasClass('error')) {
                    field.removeClass('error');
                    field.next('.field-error').remove();
                }
            });
        }

        initEnvironmentToggle() {
            // Trigger initial state
            this.handleEnvironmentChange();
            this.handleEnableToggle();
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        new PaymentGatewayAdmin();
    });

    // Add CSS for enhanced styling
    const additionalCSS = `
        .field-error {
            color: #dc3232;
            font-size: 12px;
            margin-top: 5px;
            font-style: italic;
        }
        
        .form-field.error input {
            border-color: #dc3232;
        }
        
        .copy-url-btn {
            margin-left: 10px;
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .copy-url-btn.copied {
            background: #46b450;
            border-color: #46b450;
            color: white;
        }
        
        .credential-section {
            opacity: 0.6;
            transition: opacity 0.3s ease;
        }
        
        .credential-section.enabled {
            opacity: 1;
        }
        
        .test-credentials {
            background: #f0f8ff;
            border: 1px solid #0073aa;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .test-credentials h4 {
            color: #0073aa;
            margin: 0 0 10px 0;
        }
        
        .test-credentials code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    `;

    // Inject CSS
    const style = document.createElement('style');
    style.textContent = additionalCSS;
    document.head.appendChild(style);
    

    


})(jQuery);
