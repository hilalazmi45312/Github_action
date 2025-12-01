/**
 * Product Extras Widget Frontend JavaScript
 */
(function ($) {
    'use strict';

    // Silence console within this widget only
    try {
        var __sh_console = window.console || {};
        var console = { log: function () {}, warn: function () {}, error: function () {} };
    } catch (e) {}

    var ProductExtrasWidget = {
        priceUpdateTimeout: null,
        initialized: false,

        // DOM Cache for performance optimization
        cache: {
            $document: null,
            $cartForm: null,
            $extrasContainer: null,
            $priceWidget: null,
            initialized: false,
        },

        // Debounce timers
        debounceTimers: {
            priceUpdate: null,
            formUpdate: null,
        },

        init: function () {
            // Prevent multiple initializations
            if (this.initialized) {
                return;
            }

            // Initialize DOM cache
            this.initializeCache();

            var self = this;
            $(document).ready(function () {
                self.bindEvents();
                self.initializeFields();
                self.initializeVariationAvailability();
                self.initializeQuantityControls();
                self.autoCollectInformationFields();
                self.initialized = true;
            });
        },

        // Initialize DOM cache for performance
        initializeCache: function () {
            if (!this.cache.initialized) {
                this.cache.$document = $(document);
                this.cache.initialized = true;
            }

            // Update dynamic cache elements
            this.updateCache();
        },

        // Update cache for dynamic elements
        updateCache: function () {
            this.cache.$cartForm = $('form.cart, form.variations_form');
            this.cache.$extrasContainer = $('.sh-product-extras-container');
            this.cache.$priceWidget = $('.sh-price-widget');
        },

        // Clear cache and reinitialize for fresh data
        clearCache: function () {
            this.cache.initialized = false;
            this.cache.$document = null;
            this.cache.$cartForm = null;
            this.cache.$extrasContainer = null;
            this.cache.$priceWidget = null;
            this.initializeCache();
        },

        bindEvents: function () {
            // Unbind existing events first to prevent duplicates
            this.unbindEvents();

            // Update cache before binding
            this.updateCache();

            // Single delegated event handler for all product extras interactions
            this.cache.$document.on('click.productExtras change.productExtras input.productExtras', this.handleDelegatedEvents.bind(this));

            // Handle variation selection
            this.bindVariationEvents();

            // Integrate with WooCommerce add to cart button
            this.bindCartIntegration();
        },

        unbindEvents: function () {
            // Unbind all events with our namespace
            this.cache.$document.off('.productExtras');
            // Reset cart integration binding flag
            this.cartIntegrationBound = false;
        },

        // Centralized event handler using event delegation
        handleDelegatedEvents: function (e) {
            var $target = $(e.target);
            var eventType = e.type;

            // Handle different event types and targets efficiently
            if (eventType === 'click') {
                this.handleClickEvents($target, e);
            } else if (eventType === 'change') {
                this.handleChangeEvents($target, e);
            } else if (eventType === 'input') {
                this.handleInputEvents($target, e);
            }
        },

        // Optimized click event handling
        handleClickEvents: function ($target, e) {
            // Checkbox label clicks
            if ($target.hasClass('sh-checkbox-label')) {
                e.preventDefault();
                this.handleCheckboxLabelClick($target);
                return;
            }

            // Quantity controls
            if ($target.hasClass('sh-qty-minus')) {
                e.stopPropagation();
                this.handleQuantityDecrease($target);
                return;
            }

            if ($target.hasClass('sh-qty-plus')) {
                e.stopPropagation();
                this.handleQuantityIncrease($target);
                return;
            }

            // Checkbox/radio label clicks
            if ($target.hasClass('pewc-checkbox-label') || $target.hasClass('pewc-radio-label')) {
                this.handleCheckboxRadioClick($target);
                return;
            }

            // Checkbox list wrapper clicks
            if ($target.closest('.pewc-checkbox-wrapper').length && !$target.is('input[type="checkbox"], input[type="number"]')) {
                this.handleCheckboxWrapperClick($target);
                return;
            }

            // Select option card clicks
            if ($target.closest('.sh-select-option-card').length && !$target.is('input[type="radio"]')) {
                this.handleSelectOptionCardClick($target);
                return;
            }
        },

        // Optimized change event handling
        handleChangeEvents: function ($target, e) {
            // File uploads
            if ($target.is('input[type="file"]') && $target.closest('.sh-product-extras-container').length) {
                this.handleFileUpload(e);
                return;
            }

            // Checkbox inputs
            if ($target.hasClass('sh-checkbox-input')) {
                this.handleCheckboxChange($target);
                return;
            }

            // Quantity inputs
            if ($target.hasClass('sh-qty-input')) {
                this.handleQuantityInputChange($target);
                return;
            }

            // Checkbox list fields
            if ($target.hasClass('pewc-checkbox-form-field')) {
                this.handleCheckboxListChange($target);
                return;
            }

            // Radio inputs for select fields
            if ($target.hasClass('sh-radio-input')) {
                this.handleRadioChange($target);
                return;
            }

            // General field changes
            if ($target.closest('.sh-product-extras-container, .sh-product-extras-fields').length) {
                this.debouncedFormUpdate($target);
                return;
            }
        },

        // Optimized input event handling
        handleInputEvents: function ($target, e) {
            // Handle input events for form fields
            if ($target.closest('.sh-product-extras-fields').length) {
                this.debouncedFormUpdate($target);
            }
        },

        // bindCardEvents function removed - replaced by optimized event delegation

        updateSelectFormFields: function ($card) {
            // Update any hidden form fields for select field types
            var $radio = $card.find('.sh-radio-input');
            if ($radio.length) {
                var fieldName = $radio.attr('name');
                var fieldValue = $radio.val();
                var form = this.cache.$cartForm;

                if (fieldName && form.length > 0 && $radio.is(':checked')) {
                    // Update or create hidden field in the cart form
                    var hiddenField = form.find('input[type="hidden"][name="' + fieldName + '"]');
                    if (hiddenField.length > 0) {
                        hiddenField.val(fieldValue);
                    } else {
                        var newHiddenField = $('<input type="hidden" name="' + fieldName + '" value="' + fieldValue + '">');
                        form.append(newHiddenField);
                    }
                }
            }
        },

        initializeFields: function () {
            // Initialize any special field behaviors

            // Set initial state for checkboxes and quantities
            $('.sh-product-extra-card').each(function () {
                var $card = $(this);
                var $checkbox = $card.find('.sh-checkbox-input');
                var $quantityInput = $card.find('.sh-qty-input');

                // Initialize base prices for this card
                ProductExtrasWidget.initializeCardBasePrices($card);

                // Enable checkboxes for variable products initially (they will be disabled later if needed)
                var productType = $checkbox.data('product-type');
                if (productType === 'variable') {
                    $checkbox.prop('disabled', false);
                    $card.removeClass('sh-product-disabled');
                }

                // Ensure quantity is at least 1 if checkbox is checked
                if ($checkbox.is(':checked') && parseInt($quantityInput.val()) < 1) {
                    $quantityInput.val(1);
                }

                // Update card state
                if ($checkbox.is(':checked')) {
                    $card.addClass('sh-product-selected');
                } else {
                    $card.removeClass('sh-product-selected');
                }
            });

            // Initialize checkbox list wrappers
            $('.pewc-checkboxes-list-wrapper .pewc-checkbox-wrapper').each(function () {
                var $wrapper = $(this);
                var $checkbox = $wrapper.find('.pewc-checkbox-form-field');
                var $quantityInput = $wrapper.find('.pewc-child-quantity-field');

                if ($checkbox.is(':checked')) {
                    $wrapper.addClass('selected');
                    if ($quantityInput.length && parseInt($quantityInput.val()) <= 0) {
                        $quantityInput.val(1);
                    }
                } else {
                    $wrapper.removeClass('selected');
                    if ($quantityInput.length) {
                        $quantityInput.val(0);
                    }
                }
            });

            // Initialize any special field types
            this.initializeColorPickers();
            this.initializeDatePickers();
            this.initializeTooltips();

            // Trigger initial price calculation
            this.debouncedPriceUpdate();
            this.evaluateGroupConditions();
        },

        initializeCardBasePrices: function ($card) {
            // Store the base prices for later quantity-based calculations
            var $priceSection = $card.find('.sh-product-price');
            var $originalPrice = $priceSection.find('.sh-price-original');
            var $salePrice = $priceSection.find('.sh-price-sale');
            var $regularPrice = $priceSection.find('.sh-price-regular');

            // Store the original price HTML for variation reset
            if ($priceSection.length) {
                $card.data('original-price', $priceSection.html());
            }

            if ($originalPrice.length && $salePrice.length) {
                // Has sale pricing
                var basePriceOriginal = this.extractPriceFromElement($originalPrice);
                var basePriceSale = this.extractPriceFromElement($salePrice);
                $card.data('base-price-original', basePriceOriginal);
                $card.data('base-price-sale', basePriceSale);
            } else if ($regularPrice.length) {
                // Regular pricing only
                var basePriceRegular = this.extractPriceFromElement($regularPrice);
                $card.data('base-price-regular', basePriceRegular);
            }
        },

        updateFormFields: function (card) {
            var checkbox = card.find('.sh-checkbox-input');
            var quantityInput = card.find('.sh-qty-input');
            var form = this.cache.$cartForm;

            if (form.length > 0 && checkbox.length > 0) {
                var checkboxName = checkbox.attr('name');
                var quantity = quantityInput.length && quantityInput.val() !== undefined ? parseInt(quantityInput.val()) || 1 : 1;

                // Update checkbox value in form
                if (checkboxName && checkbox.val() !== undefined) {
                    var hiddenCheckbox = form.find('input[type="hidden"][name="' + checkboxName + '"]');
                    if (checkbox.is(':checked')) {
                        if (hiddenCheckbox.length === 0) {
                            form.append('<input type="hidden" name="' + checkboxName + '" value="' + checkbox.val() + '">');
                        }
                        // Also add quantity if needed
                        var quantityName = checkboxName.replace('[]', '_quantity[]');
                        var hiddenQuantity = form.find('input[type="hidden"][name="' + quantityName + '"]');
                        if (hiddenQuantity.length === 0) {
                            form.append('<input type="hidden" name="' + quantityName + '" value="' + quantity + '">');
                        } else {
                            hiddenQuantity.val(quantity);
                        }
                    } else {
                        hiddenCheckbox.remove();
                        var quantityName = checkboxName.replace('[]', '_quantity[]');
                        form.find('input[type="hidden"][name="' + quantityName + '"]').remove();
                    }
                }
            }
        },

        // Optimized individual event handlers
        handleCheckboxLabelClick: function ($label) {
            var $checkbox = $label.siblings('.sh-checkbox-input');

            if ($checkbox.prop('disabled')) {
                return;
            }

            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        },

        handleQuantityDecrease: function ($button) {
            if ($button.hasClass('sh-disabled') || $button.prop('disabled')) {
                return;
            }

            var $card = $button.closest('.sh-product-extra-card');
            var $input = $button.siblings('.sh-qty-input');

            // Check if all variations are selected
            if (!this.areAllVariationsSelected($card)) {
                return;
            }

            if ($input.length && $input.val() !== undefined) {
                var currentVal = parseInt($input.val()) || 1;
                if (currentVal > 1) {
                    $input.val(currentVal - 1).trigger('change');
                }
            }
        },

        handleQuantityIncrease: function ($button) {
            var $card = $button.closest('.sh-product-extra-card');
            var $input = $button.siblings('.sh-qty-input');

            if ($input.length && $input.val() !== undefined) {
                var currentVal = parseInt($input.val()) || 1;
                var maxVal = parseInt($input.attr('max')) || 10;
                if (currentVal < maxVal) {
                    $input.val(currentVal + 1).trigger('change');
                }
            }
        },

        handleCheckboxChange: function ($checkbox) {
            var $card = $checkbox.closest('.sh-product-extra-card');

            if ($checkbox.is(':checked')) {
                $card.addClass('sh-product-selected');
                // Ensure quantity is at least 1
                var $quantityInput = $card.find('.sh-qty-input');
                if (parseInt($quantityInput.val()) < 1) {
                    $quantityInput.val(1);
                }

                // Handle cart functionality for product and information fields
                this.handleCartSelection($checkbox, true);
            } else {
                $card.removeClass('sh-product-selected');

                // Handle cart functionality for product and information fields
                this.handleCartSelection($checkbox, false);
            }

            // Batch DOM updates
            this.batchDOMUpdates($card, function () {
                // Update form fields
                ProductExtrasWidget.updateFormFields($card);

                // Update quantity controls state
                ProductExtrasWidget.updateQuantityControlsState($card);

                // Trigger price update with debouncing
                ProductExtrasWidget.debouncedPriceUpdate();
            });
        },

        handleQuantityInputChange: function ($input) {
            // Prevent duplicate processing
            if ($input.data('processing')) {
                return;
            }
            $input.data('processing', true);

            var $card = $input.closest('.sh-product-extra-card');
            var $checkbox = $card.find('.sh-checkbox-input');
            var rawValue = $input.val();
            var quantity = parseInt(rawValue);

            // Validate the input value
            var minVal = parseInt($input.attr('min')) || 1;
            var maxVal = parseInt($input.attr('max')) || 10;
            var step = parseInt($input.attr('step')) || 1;

            // Handle invalid or empty input
            if (isNaN(quantity) || rawValue === '') {
                if ($checkbox.length && $checkbox.is(':checked')) {
                    $input.val(minVal);
                    quantity = minVal;
                } else {
                    $input.val(0);
                    quantity = 0;
                }
            } else {
                // Ensure quantity is within bounds
                if (quantity < minVal && $checkbox.length && $checkbox.is(':checked')) {
                    $input.val(minVal);
                    quantity = minVal;
                } else if (quantity > maxVal) {
                    $input.val(maxVal);
                    quantity = maxVal;
                } else if (quantity % step !== 0) {
                    // Round to nearest valid step
                    var roundedQuantity = Math.round(quantity / step) * step;
                    if (roundedQuantity < minVal && $checkbox.length && $checkbox.is(':checked')) {
                        roundedQuantity = minVal;
                    }
                    $input.val(roundedQuantity);
                    quantity = roundedQuantity;
                }
            }

            // Batch DOM updates for better performance
            this.batchDOMUpdates($card, function () {
                // Ensure base prices are initialized before updating
                ProductExtrasWidget.ensureBasePricesInitialized($card);
                // Update individual card price display
                ProductExtrasWidget.updateCardPrices($card);

                // Update quantity controls state (enable/disable minus button)
                ProductExtrasWidget.updateQuantityControlsState($card);

                // Update any hidden form fields if needed
                ProductExtrasWidget.updateFormFields($card);

                // Update stored product data if this product is selected
                var $checkbox = $card.find('.sh-checkbox-input');
                if ($checkbox.is(':checked')) {
                    var productId = $checkbox.data('product-id');
                    var productType = $checkbox.data('product-type');
                    if (productId) {
                        ProductExtrasWidget.storeSelectedProduct(productId, quantity, productType, $card);
                    }
                }
            });

            // Clear processing flag after a short delay
            setTimeout(function () {
                $input.removeData('processing');
            }, 100);
        },

        handleCheckboxListChange: function ($checkbox) {
            var $wrapper = $checkbox.closest('.pewc-checkbox-wrapper');
            var $quantityInput = $wrapper.find('.pewc-child-quantity-field');

            if ($checkbox.is(':checked')) {
                $wrapper.addClass('selected');
                // If there's a quantity field and it's 0 or empty, set it to 1
                if ($quantityInput.length && $quantityInput.val() !== undefined) {
                    var currentQty = parseInt($quantityInput.val()) || 0;
                    if (currentQty <= 0) {
                        $quantityInput.val(1);
                    }
                }
            } else {
                $wrapper.removeClass('selected');
                // If there's a quantity field, set it to 0
                if ($quantityInput.length && $quantityInput.val() !== undefined) {
                    $quantityInput.val(0);
                }
            }
        },

        handleCheckboxWrapperClick: function ($target) {
            var $wrapper = $target.closest('.pewc-checkbox-wrapper');
            var $checkbox = $wrapper.find('.pewc-checkbox-form-field');
            if ($checkbox.length && $checkbox.prop('checked') !== undefined) {
                $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
            }
        },

        handleSelectOptionCardClick: function ($target) {
            var $card = $target.closest('.sh-select-option-card');
            var $radio = $card.find('.sh-radio-input');

            if ($radio.length && !$radio.prop('disabled')) {
                // Batch DOM operations for better performance
                this.batchDOMUpdates($card, function () {
                    // Uncheck other radios in the same group
                    var radioName = $radio.attr('name');
                    if (radioName) {
                        $('input[name="' + radioName + '"]').prop('checked', false);
                        $('.sh-select-option-card').removeClass('sh-product-selected');
                    }

                    // Check this radio and select the card
                    $radio.prop('checked', true);
                    $card.addClass('sh-product-selected');

                    // Trigger change event
                    $radio.trigger('change');
                });
            }
        },

        handleRadioChange: function ($radio) {
            var $card = $radio.closest('.sh-select-option-card');
            var radioName = $radio.attr('name');

            // Batch DOM operations for better performance
            this.batchDOMUpdates($card, function () {
                // Remove selection from all cards in this group
                if (radioName) {
                    $('input[name="' + radioName + '"]').each(function () {
                        $(this).closest('.sh-select-option-card').removeClass('sh-product-selected');
                    });
                }

                // Add selection to current card if checked
                if ($radio.is(':checked')) {
                    $card.addClass('sh-product-selected');
                }
            });

            // Update form fields and trigger price update
            this.updateSelectFormFields($card);
            this.debouncedPriceUpdate();
        },

        // Debounced price update for better performance
        debouncedPriceUpdate: function () {
            clearTimeout(this.debounceTimers.priceUpdate);
            this.debounceTimers.priceUpdate = setTimeout(function () {
                ProductExtrasWidget.triggerPriceUpdate();
            }, 150); // 150ms debounce
        },

        // Debounced form update for better performance
        debouncedFormUpdate: function ($field) {
            clearTimeout(this.debounceTimers.formUpdate);
            this.debounceTimers.formUpdate = setTimeout(function () {
                ProductExtrasWidget.handleFieldUpdate($field);
            }, 100); // 100ms debounce
        },

        // Optimized field update handler
        handleFieldUpdate: function ($field) {
            var fieldName = $field.attr('name');

            if (fieldName && this.cache.$cartForm.length > 0 && $field.val() !== undefined) {
                var fieldValue = $field.val();
                // Update or create hidden field in the cart form
                var $hiddenField = this.cache.$cartForm.find('input[type="hidden"][name="' + fieldName + '"]');
                if ($hiddenField.length > 0) {
                    $hiddenField.val(fieldValue);
                } else if (fieldValue !== null && fieldValue !== '') {
                    var $newHiddenField = $('<input type="hidden" name="' + fieldName + '" value="' + fieldValue + '">');
                    this.cache.$cartForm.append($newHiddenField);
                }
            }
        },

        // Batch DOM updates to minimize reflows/repaints
        batchDOMUpdates: function ($context, callback) {
            // Use requestAnimationFrame for optimal DOM update timing
            if (window.requestAnimationFrame) {
                requestAnimationFrame(callback);
            } else {
                // Fallback for older browsers
                setTimeout(callback, 16); // ~60fps
            }
        },

        triggerPriceUpdate: function () {
            // Calculate total price including product extras
            var totalExtrasCost = this.calculateExtrasCost();

            // Only trigger a custom event for other systems to listen to
            // No longer updating sh-price-widget directly
            $(document).trigger('productExtrasPriceChanged', {
                extrasCost: totalExtrasCost,
                timestamp: Date.now(),
            });
        },

        calculateExtrasCost: function () {
            var totalCost = 0;

            $('.sh-product-extra-card').each(function () {
                var $card = $(this);
                var $checkbox = $card.find('.sh-checkbox-input');

                if ($checkbox.is(':checked')) {
                    var optionCost = parseFloat($checkbox.data('option-cost')) || 0;
                    var $quantityInput = $card.find('.sh-qty-input');
                    var quantity = parseInt($quantityInput.val()) || 1;

                    totalCost += optionCost * quantity;
                }
            });

            return totalCost;
        },

        updateCardPrices: function ($card) {
            // Update individual card prices based on quantity
            var $quantityInput = $card.find('.sh-qty-input');
            var quantity = parseInt($quantityInput.val()) || 1;
            var $priceSection = $card.find('.sh-product-price');

            // Get the base prices from data attributes ONLY
            var basePriceOriginal = parseFloat($card.data('base-price-original'));
            var basePriceSale = parseFloat($card.data('base-price-sale'));
            var basePriceRegular = parseFloat($card.data('base-price-regular'));

            // If base prices aren't stored, initialize them first
            if (isNaN(basePriceOriginal) && isNaN(basePriceSale) && isNaN(basePriceRegular)) {
                this.initializeCardBasePrices($card);

                // Re-get the base prices after initialization
                basePriceOriginal = parseFloat($card.data('base-price-original'));
                basePriceSale = parseFloat($card.data('base-price-sale'));
                basePriceRegular = parseFloat($card.data('base-price-regular'));
            }

            // Calculate new prices based on quantity using ONLY the stored base prices
            if (!isNaN(basePriceOriginal) && !isNaN(basePriceSale)) {
                // Update sale pricing
                var newOriginalPrice = basePriceOriginal * quantity;
                var newSalePrice = basePriceSale * quantity;

                $priceSection.find('.sh-price-original').html(this.formatPrice(newOriginalPrice));
                $priceSection.find('.sh-price-sale').html(this.formatPrice(newSalePrice));
            } else if (!isNaN(basePriceRegular)) {
                // Update regular pricing
                var newRegularPrice = basePriceRegular * quantity;

                $priceSection.find('.sh-price-regular').html(this.formatPrice(newRegularPrice));
            } else {
                // Fallback: try to reset to original prices if available
                this.resetCardPrices($card);
            }
        },

        resetCardPrices: function ($card) {
            // Reset card prices to their original values as a fallback

            // Clear any corrupted base price data
            $card.removeData('base-price-original base-price-sale base-price-regular');

            // Re-initialize the base prices from the current display
            this.initializeCardBasePrices($card);

            // Reset quantity to 1 and update display
            var $quantityInput = $card.find('.sh-qty-input');
            if ($quantityInput.length) {
                $quantityInput.val(1);
                this.updateCardPrices($card);
            }
        },

        ensureBasePricesInitialized: function ($card) {
            // Check if base prices are already initialized
            var basePriceOriginal = parseFloat($card.data('base-price-original'));
            var basePriceSale = parseFloat($card.data('base-price-sale'));
            var basePriceRegular = parseFloat($card.data('base-price-regular'));

            // If no base prices are set, initialize them
            if (isNaN(basePriceOriginal) && isNaN(basePriceSale) && isNaN(basePriceRegular)) {
                this.initializeCardBasePrices($card);
            }
        },

        extractPriceFromElement: function ($element) {
            // Extract numeric price from price element
            var originalText = $element.text();

            // First try to get price from bdi element (WooCommerce format)
            var $bdi = $element.find('bdi');
            if ($bdi.length) {
                originalText = $bdi.text();
            }

            // Remove currency symbols and non-numeric characters except digits, commas, and periods
            var priceText = originalText.replace(/[^\d.,]/g, '');

            // Handle thousands separators and decimal points
            // If there are multiple commas or periods, assume the last one is decimal
            if (priceText.includes(',') && priceText.includes('.')) {
                // Both comma and period present - determine which is decimal separator
                var lastComma = priceText.lastIndexOf(',');
                var lastPeriod = priceText.lastIndexOf('.');

                if (lastPeriod > lastComma) {
                    // Period is decimal separator, remove commas
                    priceText = priceText.replace(/,/g, '');
                } else {
                    // Comma is decimal separator, replace with period and remove other periods
                    priceText = priceText.substring(0, lastComma).replace(/\./g, '') + '.' + priceText.substring(lastComma + 1);
                }
            } else if (priceText.includes(',')) {
                // Only comma present - could be thousands separator or decimal
                var commaCount = (priceText.match(/,/g) || []).length;
                if (commaCount === 1 && priceText.indexOf(',') === priceText.length - 3) {
                    // Single comma in decimal position, replace with period
                    priceText = priceText.replace(',', '.');
                } else {
                    // Multiple commas or comma not in decimal position, remove all
                    priceText = priceText.replace(/,/g, '');
                }
            }

            return parseFloat(priceText) || 0;
        },

        formatPrice: function (price) {
            // Format price using WooCommerce-like formatting with proper HTML structure
            var formattedPrice = price.toFixed(2);

            // Add thousands separators
            var parts = formattedPrice.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            formattedPrice = parts.join('.');

            // Return with proper WooCommerce HTML structure
            return '<span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">RM</span>' + formattedPrice + '</bdi></span>';
        },

        handleFieldChange: function (e) {
            var $field = $(e.target);
            var $container = $field.closest('.sh-product-extras-container');

            if ($field.length > 0) {
                // Add visual feedback for field interaction
                $field.addClass('field-changed');

                // Get field value safely
                var fieldValue = $field.val();
                if (fieldValue === undefined || fieldValue === null) {
                    fieldValue = '';
                }

                // Trigger custom event for other scripts to listen to
                $container.trigger('productExtrasFieldChanged', {
                    field: $field,
                    value: fieldValue,
                    fieldType: $field.attr('type') || $field.prop('tagName').toLowerCase(),
                });

                // Remove the visual feedback after a short delay
                setTimeout(function () {
                    $field.removeClass('field-changed');
                }, 300);
            }
        },

        handleFileUpload: function (e) {
            var $fileInput = $(e.target);
            var files = e.target.files;

            if (files.length > 0) {
                var fileName = files[0].name;
                var $feedback = $fileInput.siblings('.file-upload-feedback');

                if ($feedback.length === 0) {
                    $feedback = $('<div class="file-upload-feedback"></div>');
                    $fileInput.after($feedback);
                }

                $feedback.html('<i class="fa fa-check-circle" style="color: green;"></i> File selected: ' + fileName);
            }
        },

        handleCheckboxRadioClick: function (e) {
            var $label = $(e.currentTarget);
            var $input = $label.find('input[type="checkbox"], input[type="radio"]');

            // Add ripple effect
            $label.addClass('clicked');
            setTimeout(function () {
                $label.removeClass('clicked');
            }, 200);
        },

        initializeColorPickers: function () {
            // Initialize color picker fields if any
            $('.sh-product-extras-container input[type="color"]').each(function () {
                var $colorInput = $(this);
                $colorInput.on('change', function () {
                    var color = $(this).val();
                    $(this).css('border-color', color);
                });
            });
        },

        initializeDatePickers: function () {
            // Initialize date picker fields if any
            $('.sh-product-extras-container input[type="date"]').each(function () {
                var $dateInput = $(this);
                // Add any custom date picker initialization here
            });
        },

        initializeTooltips: function () {
            // Initialize tooltips for field descriptions
            $('.sh-product-extras-container [data-tooltip]').each(function () {
                var $element = $(this);
                var tooltipText = $element.data('tooltip');

                $element
                    .on('mouseenter', function () {
                        var $tooltip = $('<div class="field-tooltip">' + tooltipText + '</div>');
                        $('body').append($tooltip);

                        var offset = $element.offset();
                        $tooltip.css({
                            position: 'absolute',
                            top: offset.top - $tooltip.outerHeight() - 5,
                            left: offset.left + $element.outerWidth() / 2 - $tooltip.outerWidth() / 2,
                            zIndex: 9999,
                        });
                    })
                    .on('mouseleave', function () {
                        $('.field-tooltip').remove();
                    });
            });
        },

        // Utility function to validate fields
        validateFields: function () {
            var isValid = true;
            var $container = $('.sh-product-extras-container');

            $container.find('input[required], select[required], textarea[required]').each(function () {
                var $field = $(this);
                var value = $field.val();

                if (!value || value.trim() === '') {
                    $field.addClass('field-error');
                    isValid = false;
                } else {
                    $field.removeClass('field-error');
                }
            });

            return isValid;
        },

        // Function to get all field values
        getFieldValues: function () {
            var values = {};
            var $container = $('.sh-product-extras-container');

            $container.find('input, select, textarea').each(function () {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                var type = $field.attr('type');

                if (name && value !== undefined && value !== null) {
                    if (type === 'checkbox' || type === 'radio') {
                        if ($field.is(':checked')) {
                            values[name] = value;
                        }
                    } else {
                        values[name] = value;
                    }
                }
            });

            return values;
        },

        areAllVariationsSelected: function ($card) {
            // Check if all required variation attributes have been selected
            var allSelected = true;
            var hasVariations = false;

            $card.find('.sh-variation-attribute').each(function () {
                hasVariations = true;
                var $container = $(this);
                var hasActiveSelection = $container.find('.wd-swatch.wd-active').length > 0;

                if (!hasActiveSelection) {
                    allSelected = false;
                    return false; // Break out of each loop
                }
            });

            // If no variations exist, consider it as "all selected"
            return !hasVariations || allSelected;
        },

        updateQuantityControlsState: function ($card) {
            // Update the state of quantity controls based on variation selection
            var allVariationsSelected = this.areAllVariationsSelected($card);
            var $minusButton = $card.find('.sh-qty-minus');
            var $plusButton = $card.find('.sh-qty-plus');
            var $quantityInput = $card.find('.sh-qty-input');

            if (allVariationsSelected) {
                // Enable quantity input
                $quantityInput.removeClass('sh-disabled').prop('disabled', false);

                // Enable buttons based on current quantity
                var currentQty = parseInt($quantityInput.val()) || 1;
                var maxQty = parseInt($quantityInput.attr('max')) || 10;

                // Enable/disable minus button based on quantity
                if (currentQty > 1) {
                    $minusButton.removeClass('sh-disabled').prop('disabled', false);
                } else {
                    $minusButton.addClass('sh-disabled').prop('disabled', true);
                }

                // Enable/disable plus button based on max quantity
                if (currentQty < maxQty) {
                    $plusButton.removeClass('sh-disabled').prop('disabled', false);
                } else {
                    $plusButton.addClass('sh-disabled').prop('disabled', true);
                }
            } else {
                // Disable all quantity controls when variations not selected
                $quantityInput.addClass('sh-disabled').prop('disabled', true);
                $minusButton.addClass('sh-disabled').prop('disabled', true);
                $plusButton.addClass('sh-disabled').prop('disabled', true);
            }
        },

        bindVariationEvents: function () {
            // Handle variation swatch selection (Woodmart style)
            $(document).on('click.productExtras', '.wd-swatch', function (e) {
                e.preventDefault();

                var $swatch = $(this);
                var $container = $swatch.closest('.sh-variation-attribute');
                var $card = $swatch.closest('.sh-product-extra-card');

                // Exit early if no card found
                if ($card.length === 0) {
                    return;
                }

                // Don't proceed if disabled
                if ($swatch.hasClass('wd-disabled') || $swatch.hasClass('disabled')) {
                    return;
                }

                // Remove active state from siblings
                $container.find('.wd-swatch').removeClass('wd-active');

                // Add active state to clicked swatch
                $swatch.addClass('wd-active');

                // Update hidden input value
                var attributeName = $container.data('attribute');
                var attributeValue = $swatch.data('value');

                // Ensure attribute name has proper format for WooCommerce
                var inputName = 'variation_' + attributeName;
                var $hiddenInput = $card.find('input[name="' + inputName + '"]');

                if ($hiddenInput.length === 0) {
                    // Create hidden input if it doesn't exist
                    $hiddenInput = $('<input type="hidden" name="' + inputName + '" />');
                    $card.append($hiddenInput);
                }

                $hiddenInput.val(attributeValue);

                ProductExtrasWidget.updateCardSwatches($card);

                // Trigger variation change event
                ProductExtrasWidget.handleVariationChange($card);

                // Update quantity controls state after variation selection
                ProductExtrasWidget.updateQuantityControlsState($card);
            });
        },

        handleVariationChange: function ($card) {
            // Check if $card exists and has elements
            if (!$card || $card.length === 0) {
                return;
            }

            // Get product ID from the swatch container, not the card
            var $swatchContainer = $card.find('.wd-swatches-product').first();
            var swatchProductId = $swatchContainer.data('product-id');
            var cardProductId = $card.data('product-id');

            var productId = swatchProductId || cardProductId;

            var variations = {};
            var allAttributesSelected = true;
            var totalAttributes = 0;
            var selectedAttributes = 0;

            // Collect all selected variations for this product
            $card.find('.sh-variation-attribute').each(function () {
                var $attribute = $(this);
                var attributeName = $attribute.data('attribute');
                totalAttributes++;

                var inputName = 'variation_' + attributeName;
                var $hiddenInput = $card.find('input[name="' + inputName + '"]');
                var value = $hiddenInput.val();

                if (value && value !== '') {
                    // Convert attribute name to WooCommerce format
                    var wooAttributeName = attributeName;
                    if (attributeName.startsWith('pa_')) {
                        // Convert pa_color-family to attribute_pa_color-family
                        wooAttributeName = 'attribute_' + attributeName;
                    }

                    variations[wooAttributeName] = value;
                    selectedAttributes++;
                } else {
                    allAttributesSelected = false;
                }
            });

            // Reset price display if not all attributes are selected
            if (!allAttributesSelected || selectedAttributes < totalAttributes) {
                this.resetVariationDisplay($card);
                return;
            }

            // Find matching variation and update price only when ALL attributes are selected
            this.findMatchingVariation(productId, variations, $card);

            // Update stored product data if this product is already selected
            if (window.shSelectedProducts && window.shSelectedProducts[productId]) {
                // Check if variation data has actually changed
                var currentVariationData = window.shSelectedProducts[productId].variationData || {};
                var variationChanged = JSON.stringify(currentVariationData) !== JSON.stringify(variations);

                if (variationChanged) {
                    window.shSelectedProducts[productId].variationData = variations;
                    // Update cart form data to regenerate hidden inputs with new variation data
                    this.updateCartFormData();
                }
            }
        },

        resetVariationDisplay: function ($card) {
            // Reset price display to original
            const $priceElement = $card.find('.sh-product-price');
            const originalPrice = $card.data('original-price');

            if ($priceElement.length && originalPrice) {
                $priceElement.html(originalPrice);
            }

            // Reset main product price to show "From [lowest price]"
            this.resetMainProductPrice();

            // Remove variation ID
            $card.removeData('variation-id');

            // Re-enable checkboxes
            $card.find('.sh-checkbox-input').prop('disabled', false);

            // Clear stored variation data if this product is selected
            var productId = $card.data('product-id');
            if (window.shSelectedProducts && window.shSelectedProducts[productId]) {
                window.shSelectedProducts[productId].variationData = {};
                // Update cart form data to regenerate hidden inputs with cleared variation data
                this.updateCartFormData();
            }
        },

        findMatchingVariation: function (productId, selectedVariations, $card) {
            // Get variation data from local JSON
            const variationData = this.getVariationData(productId, selectedVariations);
            if (!variationData) {
                return;
            }

            // Find matching variation locally
            const matchingVariation = this.findLocalMatchingVariation(variationData, selectedVariations);

            if (matchingVariation) {
                this.updateVariationData($card, matchingVariation);
            }
        },

        getVariationData: function (productId, selectedVariations) {
            // First try the parent product ID
            const elementId = 'sh-variation-data-' + productId;

            let dataElement = document.getElementById(elementId);

            if (dataElement) {
                try {
                    const data = JSON.parse(dataElement.textContent);
                    return data;
                } catch (e) {
                    // Error parsing variation data for parent
                }
            }

            // If not found, search through all variation data elements
            const allVariationElements = document.querySelectorAll('[id^="sh-variation-data-"]');

            let bestMatch = null;
            let bestMatchScore = 0;

            for (let i = 0; i < allVariationElements.length; i++) {
                const element = allVariationElements[i];

                try {
                    const data = JSON.parse(element.textContent);
                    if (data && Array.isArray(data)) {
                        // Check if this variation data contains attributes that match our selected values
                        let matchScore = 0;
                        if (selectedVariations) {
                            // Count how many selected values exist in this variation data
                            for (const selectedAttr in selectedVariations) {
                                const selectedValue = selectedVariations[selectedAttr].toLowerCase();

                                // Check if any variation in this data has this attribute value
                                let foundMatch = false;
                                for (const variation of data) {
                                    if (variation.attributes) {
                                        for (const attrName in variation.attributes) {
                                            const attrValue = variation.attributes[attrName];
                                            if (attrValue) {
                                                const attrValueLower = attrValue.toLowerCase();
                                                if (attrValueLower === selectedValue || attrValueLower.includes(selectedValue)) {
                                                    matchScore++;
                                                    foundMatch = true;
                                                    break;
                                                }
                                            }
                                        }
                                        if (foundMatch) break;
                                    }
                                }
                            }
                        }

                        if (matchScore > bestMatchScore) {
                            bestMatchScore = matchScore;
                            bestMatch = data;
                        }
                    }
                } catch (e) {
                    // Error parsing variation data
                }
            }

            if (bestMatch) {
                return bestMatch;
            }

            return null;
        },

        findLocalMatchingVariation: function (variationData, selectedVariations) {
            for (let i = 0; i < variationData.length; i++) {
                const variation = variationData[i];
                const variationAttributes = variation.attributes;
                let match = true;

                // Check if all selected attributes match this variation
                for (const selectedAttr in selectedVariations) {
                    const selectedValue = selectedVariations[selectedAttr];
                    let variationValue = null;

                    // Try different attribute name formats
                    const possibleAttrNames = [
                        selectedAttr, // exact match
                        'attribute_' + selectedAttr, // with attribute_ prefix
                        selectedAttr.replace('pa_', 'attribute_pa_'), // convert pa_ to attribute_pa_
                        selectedAttr.replace('-', '_'), // replace hyphens with underscores
                        selectedAttr.replace('_', '-'), // replace underscores with hyphens
                    ];

                    // Find the matching attribute in variation data
                    for (const attrName of possibleAttrNames) {
                        if (variationAttributes.hasOwnProperty(attrName)) {
                            variationValue = variationAttributes[attrName];
                            break;
                        }
                    }

                    // Also try to find by value matching (case insensitive)
                    if (!variationValue) {
                        for (const varAttr in variationAttributes) {
                            const varValue = variationAttributes[varAttr];
                            if (varValue && varValue.toLowerCase() === selectedValue.toLowerCase()) {
                                variationValue = varValue;
                                break;
                            }
                        }
                    }

                    if (!variationValue || variationValue.toLowerCase() !== selectedValue.toLowerCase()) {
                        match = false;
                        break;
                    }
                }

                if (match) {
                    return variation;
                }
            }

            return null;
        },

        updateVariationData: function ($card, variationData) {
            // Update price display
            if (variationData.price_html) {
                // Parse the price HTML to extract sale and regular prices
                var $tempDiv = $('<div>').html(variationData.price_html);
                var salePrice = null;
                var regularPrice = null;
                var normalPrice = null;

                // Look for sale price (ins tag or .woocommerce-Price-amount)
                var $saleElement = $tempDiv.find('ins .woocommerce-Price-amount, ins.amount, .price ins');
                if ($saleElement.length > 0) {
                    salePrice = $saleElement.first().text();
                }

                // Look for regular price (del tag)
                var $regularElement = $tempDiv.find('del .woocommerce-Price-amount, del.amount, .price del');
                if ($regularElement.length > 0) {
                    regularPrice = $regularElement.first().text();
                }

                // If no sale price found, look for normal price
                if (!salePrice) {
                    var $normalElement = $tempDiv.find('.woocommerce-Price-amount, .amount');
                    if ($normalElement.length > 0) {
                        normalPrice = $normalElement.first().text();
                    }
                }

                // Format price using ProductExtra widget structure
                var formattedPriceHtml = '';
                if (salePrice && regularPrice) {
                    // Use same classes as ProductExtras widget for consistency
                    formattedPriceHtml = '<span class="sh-price-sale">' + salePrice + '</span>';
                    formattedPriceHtml += '<span class="sh-price-original">' + regularPrice + '</span>';
                } else if (normalPrice) {
                    formattedPriceHtml = '<span class="sh-price-regular">' + normalPrice + '</span>';
                } else {
                    // Fallback to original HTML if parsing fails
                    formattedPriceHtml = variationData.price_html;
                }

                $card.find('.sh-product-price').html(formattedPriceHtml);

                // Also update the main product price display to show selected variation price
                this.updateMainProductPrice(variationData);
            }

            // Update stock status
            if (variationData.is_in_stock !== undefined) {
                var $checkbox = $card.find('.sh-checkbox-input');
                if (!variationData.is_in_stock) {
                    $checkbox.prop('disabled', true);
                    // Add disabled class to the card only when variation is out of stock
                    $card.addClass('sh-product-disabled');
                } else {
                    $checkbox.prop('disabled', false);
                    // Remove disabled class when variation is in stock
                    $card.removeClass('sh-product-disabled');
                }
            }

            // Store variation ID for cart
            $card.data('variation-id', variationData.variation_id);

            // Update stored product data if this product is already selected
            var productId = $card.data('product-id');
            if (window.shSelectedProducts && window.shSelectedProducts[productId]) {
                var quantity = window.shSelectedProducts[productId].quantity || 1;
                var productType = window.shSelectedProducts[productId].productType || 'variable';
                this.storeSelectedProduct(productId, quantity, productType, $card);
            }

            // Update variation option availability based on stock
            this.updateVariationOptionsAvailability($card);

            // Trigger price update for the whole widget
            this.debouncedPriceUpdate();
        },

        updateVariationOptionsAvailability: function ($card) {
            ProductExtrasWidget.updateCardSwatches($card);
        },

        buildCardVariationIndex: function (productId) {
            const data = this.getVariationData(productId, {});
            const index = {};
            if (!data || !Array.isArray(data)) return { list: [], index: index };
            for (let i = 0; i < data.length; i++) {
                const v = data[i];
                if (!v || !v.attributes) continue;
                const attrs = v.attributes;
                for (const key in attrs) {
                    if (!Object.prototype.hasOwnProperty.call(attrs, key)) continue;
                    const val = attrs[key];
                    const keys = [key];
                    if (key.startsWith('attribute_')) {
                        const raw = key.replace('attribute_', '');
                        keys.push(raw);
                    }
                    for (let k = 0; k < keys.length; k++) {
                        const useKey = keys[k];
                        if (!index[useKey]) index[useKey] = {};
                        if (!index[useKey][val]) index[useKey][val] = [];
                        index[useKey][val].push(v);
                    }
                }
            }
            return { list: data, index: index };
        },

        updateCardSwatches: function ($card) {
            const productId = $card.data('product-id');
            if (!productId) return;
            const cached = this.buildCardVariationIndex(productId);
            const variationIndex = cached.index;
            if (!cached.list || !cached.list.length || !variationIndex || !Object.keys(variationIndex).length) return;

            const selectedMap = {};
            $card.find('.sh-variation-attribute').each(function () {
                const $attr = $(this);
                const nm = $attr.data('attribute');
                const inputName = 'variation_' + nm;
                const val = $card.find('input[name="' + inputName + '"]').val() || '';
                selectedMap[nm] = val;
                selectedMap['attribute_' + nm] = val;
            });

            $card.find('.wd-swatches-product').each(function () {
                const $swatchContainer = $(this);
                const attrName = $swatchContainer.data('id') || $swatchContainer.attr('data-id');
                $swatchContainer.find('.wd-swatch').each(function () {
                    const $option = $(this);
                    const value = $option.data('value') || $option.attr('data-value');
                    const candidates = variationIndex[attrName] && variationIndex[attrName][value] ? variationIndex[attrName][value] : variationIndex['attribute_' + attrName] && variationIndex['attribute_' + attrName][value] ? variationIndex['attribute_' + attrName][value] : [];
                    let totalQty = 0;
                    for (let i = 0; i < candidates.length; i++) {
                        const v = candidates[i];
                        if (!v || !v.attributes) continue;
                        const attrs = v.attributes;
                        let matches = true;
                        for (const key in selectedMap) {
                            if (!Object.prototype.hasOwnProperty.call(selectedMap, key)) continue;
                            if (key === attrName || key === 'attribute_' + attrName) continue;
                            const selVal = selectedMap[key];
                            if (selVal && selVal !== '') {
                                const varVal = attrs[key] || attrs['attribute_' + key] || null;
                                if (!varVal || varVal !== selVal) {
                                    matches = false;
                                    break;
                                }
                            }
                        }
                        if (!matches) continue;
                        let qty = 0;
                        if (v.managing_stock) {
                            qty = parseInt(v.stock_quantity, 10);
                            if (isNaN(qty)) qty = 0;
                        } else {
                            qty = v.is_in_stock ? 1 : 0;
                        }
                        totalQty += qty;
                    }
                    if (totalQty <= 0) {
                        $option.addClass('wd-disabled').removeClass('wd-enabled').prop('disabled', true).attr('data-stock-qty', 0);
                    } else {
                        $option.removeClass('wd-disabled').addClass('wd-enabled').prop('disabled', false).attr('data-stock-qty', totalQty);
                    }
                });
            });
        },

        initializeVariationAvailability: function () {
            $('.sh-product-extras-container .sh-product-extra-card').each(function () {
                var $card = $(this);
                var productId = $card.data('product-id');
                if (productId) {
                    ProductExtrasWidget.updateCardSwatches($card);
                }
            });
        },

        initializeQuantityControls: function () {
            // Initialize quantity controls state for all product cards
            $('.sh-product-extras-container .sh-product-extra-card').each(function () {
                var $card = $(this);
                ProductExtrasWidget.updateQuantityControlsState($card);
            });
        },

        autoCollectInformationFields: function () {
            // Automatically collect all information field data and add to cart
            var self = this;
            var collectedCount = 0;

            // Start auto-collection of information fields

            // Find all information field cards
            $('.sh-information-card').each(function () {
                var $card = $(this);
                var $checkbox = $card.find('.sh-information-checkbox-input');

                if ($checkbox.length > 0) {
                    // Automatically check the checkbox to include in cart
                    $checkbox.prop('checked', true);

                    // Add selected class to card
                    $card.addClass('sh-product-selected');

                    // Extract information data
                    var infoId = $checkbox.data('info-id');
                    var infoLabel = $checkbox.data('info-label');
                    var infoPrice = $checkbox.data('info-price') || 0;

                    // Get current image URL from the card
                    var imageUrl = '';
                    var $image = $card.find('.sh-product-image img');
                    if ($image.length) {
                        imageUrl = $image.attr('src') || '';
                    }

                    // Auto-collected information field

                    // Store the information data
                    self.storeSelectedInformation(infoId, infoLabel, infoPrice, imageUrl);

                    // Update form fields
                    self.updateFormFields($card);

                    collectedCount++;
                }
            });

            // Auto-collected information fields: " + collectedCount

            // Update cart form data with all collected information
            this.updateCartFormData();

            // Final selected information state is available in window.shSelectedInformation
        },

        updateMainProductPrice: function (variationData) {
            // Store original price HTML if not already stored
            if (!this.originalMainPriceHtml) {
                var $mainPriceElements = $('.product-price .price, .woocommerce-Price-amount, .senheng-price-wrapper');
                if ($mainPriceElements.length > 0) {
                    this.originalMainPriceHtml = $mainPriceElements.first().html();
                }
            }

            // Find the main product price element (could be in different locations)
            var $mainPriceElements = $('.product-price .price, .woocommerce-Price-amount, .senheng-price-wrapper');

            if ($mainPriceElements.length > 0 && variationData.price_html) {
                // Extract price from variation data
                var $tempDiv = $('<div>').html(variationData.price_html);
                var salePrice = $tempDiv.find('.sh-price-sale').text();
                var regularPrice = $tempDiv.find('.sh-price-original').text();
                var normalPrice = $tempDiv.find('.sh-price-regular').text();

                // Update the main price display
                $mainPriceElements.each(function () {
                    var $priceElement = $(this);

                    // Check if this is a "From" price display
                    var $fromText = $priceElement.find('.price-from');

                    if (salePrice && regularPrice) {
                        // Use same classes as ProductExtras widget for consistency
                        var newPriceHtml = '<span class="sh-price-sale">' + salePrice + '</span>';
                        newPriceHtml += '<span class="sh-price-original">' + regularPrice + '</span>';

                        if ($fromText.length > 0) {
                            // Remove "From" text when showing specific variation price
                            $fromText.remove();
                        }
                        $priceElement.html(newPriceHtml);

                        // Remove any separate original price rows that might exist
                        $priceElement.siblings('.original-price-row').remove();
                    } else if (normalPrice) {
                        // Show regular price
                        if ($fromText.length > 0) {
                            // Remove "From" text when showing specific variation price
                            $fromText.remove();
                        }
                        $priceElement.html(normalPrice);

                        // Remove original price row if it exists
                        var $originalPriceRow = $priceElement.siblings('.original-price-row');
                        if ($originalPriceRow.length > 0) {
                            $originalPriceRow.remove();
                        }
                    }
                });
            }
        },

        resetMainProductPrice: function () {
            // Find the main product price element
            var $mainPriceElements = $('.product-price .price, .woocommerce-Price-amount, .senheng-price-wrapper');

            if ($mainPriceElements.length > 0 && this.originalMainPriceHtml) {
                // Restore original price HTML
                $mainPriceElements.each(function () {
                    var $priceElement = $(this);
                    $priceElement.html(ProductExtrasWidget.originalMainPriceHtml);
                });

                // Remove any additional original price rows that were added
                $('.original-price-row').remove();
            }
        },

        handleCartSelection: function (checkbox, isSelected) {
            var $checkbox = $(checkbox);

            // Handle product field type
            if ($checkbox.hasClass('sh-product-checkbox-input')) {
                this.handleProductCartSelection($checkbox, isSelected);
            }
            // Handle information field type
            else if ($checkbox.hasClass('sh-information-checkbox-input')) {
                this.handleInformationCartSelection($checkbox, isSelected);
            }
            // Handle regular option checkboxes (fallback for options without specific classes)
            else if ($checkbox.hasClass('sh-checkbox-input')) {
                // Check if this is a product option by looking for data attributes or card context
                var card = $checkbox.closest('.sh-product-extra-card');
                var productId = $checkbox.data('product-id');
                var infoId = $checkbox.data('info-id');

                if (productId) {
                    // This is a product checkbox
                    this.handleProductCartSelection($checkbox, isSelected);
                } else if (infoId) {
                    // This is an information checkbox
                    this.handleInformationCartSelection($checkbox, isSelected);
                } else if (card.length > 0) {
                    // This is a regular option checkbox - treat as information
                    var optionKey = $checkbox.val();
                    var optionCost = $checkbox.data('option-cost') || 0;
                    var optionLabel = card.find('.sh-product-title').text() || 'Option ' + optionKey;

                    // Create a pseudo-checkbox with info data for processing
                    var $pseudoCheckbox = $checkbox.clone();
                    $pseudoCheckbox.data('info-id', 'option_' + optionKey);
                    $pseudoCheckbox.data('info-label', optionLabel);
                    $pseudoCheckbox.data('info-price', optionCost);

                    this.handleInformationCartSelection($pseudoCheckbox, isSelected);
                }
            }
        },

        handleProductCartSelection: function ($checkbox, isSelected) {
            var productId = $checkbox.data('product-id');
            var productType = $checkbox.data('product-type');
            var card = $checkbox.closest('.sh-product-extra-card');
            var quantity = card.find('.sh-qty-input').val() || 1;

            if (isSelected) {
                // Store product data for cart integration
                this.storeSelectedProduct(productId, quantity, productType, card);
            } else {
                this.removeSelectedProduct(productId);
            }
        },

        handleInformationCartSelection: function ($checkbox, isSelected) {
            var infoId = $checkbox.data('info-id');
            var infoLabel = $checkbox.data('info-label');
            var infoPrice = $checkbox.data('info-price') || 0;

            if (isSelected) {
                // Get current image URL from the card
                var imageUrl = '';
                var $card = $checkbox.closest('.sh-product-extra-card');
                var $image = $card.find('.sh-product-image img');
                if ($image.length) {
                    imageUrl = $image.attr('src') || '';
                }

                // Store information data for cart integration
                this.storeSelectedInformation(infoId, infoLabel, infoPrice, imageUrl);
            } else {
                this.removeSelectedInformation(infoId);
            }
        },

        storeSelectedProduct: function (productId, quantity, productType, card) {
            if (!window.shSelectedProducts) {
                window.shSelectedProducts = {};
            }

            // Get variation data if it's a variable product
            var variationData = {};
            var variationId = null;
            if (productType === 'variable') {
                // Collect variation data from hidden inputs (same as existing variation handling)
                var attributeElements = card.find('.sh-variation-attribute');

                attributeElements.each(function () {
                    var $attribute = $(this);
                    var attributeName = $attribute.data('attribute');

                    var inputName = 'variation_' + attributeName;
                    var $hiddenInput = card.find('input[name="' + inputName + '"]');
                    var value = $hiddenInput.val();

                    if (value && value !== '') {
                        // Convert attribute name to WooCommerce format
                        // ALL variation attributes need the 'attribute_' prefix in WooCommerce
                        var wooAttributeName = 'attribute_' + attributeName;
                        variationData[wooAttributeName] = value;
                    }
                });

                // Get variation ID from data attribute
                variationId = card.data('variation-id');
            }

            // Get field label from the checkbox data attribute first, then fallback to DOM search
            var $checkbox = card.find('.sh-product-checkbox-input');
            var fieldLabel = $checkbox.data('product-field-label') || '';

            // If no field label in data attribute, try to find it in the DOM
            if (!fieldLabel) {
                // Look for the field label element that precedes the cards wrapper
                var $cardsWrapper = card.closest('.sh-product-extras-cards-wrapper');
                if ($cardsWrapper.length) {
                    var $fieldLabelElement = $cardsWrapper.prev('.sh-product-extras-field-label');
                    if ($fieldLabelElement.length) {
                        fieldLabel = $fieldLabelElement.text().trim();
                    }
                }
            }

            // Don't store image URL - let server get fresh image to avoid caching issues
            var productData = {
                productId: productId,
                quantity: parseInt(quantity),
                productType: productType,
                variationData: variationData,
                fieldLabel: fieldLabel,
                imageUrl: '',
            };

            // Add variation ID if available
            if (variationId) {
                productData.variationId = variationId;
            }

            window.shSelectedProducts[productId] = productData;

            this.updateCartFormData();
        },

        removeSelectedProduct: function (productId) {
            if (window.shSelectedProducts && window.shSelectedProducts[productId]) {
                delete window.shSelectedProducts[productId];
            }

            this.updateCartFormData();
        },

        storeSelectedInformation: function (infoId, infoLabel, infoPrice, imageUrl) {
            if (!window.shSelectedInformation) {
                window.shSelectedInformation = {};
            }

            // Get original price from the checkbox data attribute
            var $checkbox = $('.sh-information-checkbox-input[data-info-id="' + infoId + '"]');
            var originalPrice = $checkbox.length ? parseFloat($checkbox.data('info-original-price') || 0) : 0;

            // Get field label from the checkbox data attribute first, then fallback to DOM search
            var fieldLabel = $checkbox.data('info-field-label') || '';

            // If no field label in data attribute, try to find it in the DOM
            if (!fieldLabel) {
                // Look for the field label element that precedes the cards wrapper
                var $cardsWrapper = $checkbox.closest('.sh-product-extras-cards-wrapper');
                if ($cardsWrapper.length) {
                    var $fieldLabelElement = $cardsWrapper.prev('.sh-product-extras-field-label');
                    if ($fieldLabelElement.length) {
                        fieldLabel = $fieldLabelElement.text().trim();
                    }
                }
            }

            // Debug logging removed

            window.shSelectedInformation[infoId] = {
                infoId: infoId,
                infoLabel: infoLabel,
                infoPrice: parseFloat(infoPrice),
                infoOriginalPrice: originalPrice,
                imageUrl: imageUrl || '',
                fieldLabel: fieldLabel,
            };

            this.updateCartFormData();
        },

        removeSelectedInformation: function (infoId) {
            if (window.shSelectedInformation && window.shSelectedInformation[infoId]) {
                delete window.shSelectedInformation[infoId];
            }

            this.updateCartFormData();
        },

        bindCartIntegration: function () {
            // Prevent duplicate event binding
            if (this.cartIntegrationBound) {
                return;
            }
            this.cartIntegrationBound = true;

            // Update hidden form fields whenever selections change
            this.updateCartFormData();

            // Bind to form submission to ensure hidden fields are up to date
            $(document).on('submit.productExtras', 'form.cart, form.variations_form', function (e) {
                // Update hidden fields one final time before submission
                ProductExtrasWidget.updateCartFormData();

                // Don't intercept the form submission - let add-to-cart.js handle all submissions consistently
                // This ensures proper loading states and error handling
                return;
            });
        },

        updateCartFormData: function () {
            var selectedProducts = window.shSelectedProducts || {};
            var selectedInformation = window.shSelectedInformation || {};

            // Find WooCommerce add-to-cart forms (cached for performance)
            var $forms = $('form.cart, form.variations_form');
            if ($forms.length === 0) return; // Early exit if no forms

            // Remove existing product extras hidden fields
            $forms.find('input[name^="sh_selected_"], input[name^="product_extras_"], input[name^="extra_product_"], input[name^="extra_info_"], input[name="has_product_extras"]').remove();

            // Build HTML string for batch insertion (much faster than multiple appends)
            var hiddenFieldsHTML = '';
            var hasData = false;

            // Process selected products
            var productKeys = Object.keys(selectedProducts);
            if (productKeys.length > 0) {
                hasData = true;
                for (var i = 0; i < productKeys.length; i++) {
                    var productId = productKeys[i];
                    var productData = selectedProducts[productId];

                    hiddenFieldsHTML += '<input type="hidden" name="extra_product_ids[]" value="' + productData.productId + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_product_qty[' + productData.productId + ']" value="' + productData.quantity + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_product_type[' + productData.productId + ']" value="' + productData.productType + '">';

                    // Add variation data efficiently
                    if (productData.variationData) {
                        var variationKeys = Object.keys(productData.variationData);
                        for (var j = 0; j < variationKeys.length; j++) {
                            var attrName = variationKeys[j];
                            var attrValue = productData.variationData[attrName];
                            hiddenFieldsHTML += '<input type="hidden" name="extra_product_variation[' + productData.productId + '][' + attrName + ']" value="' + attrValue + '">';
                        }
                    }

                    if (productData.variationId) {
                        hiddenFieldsHTML += '<input type="hidden" name="extra_product_variation_id[' + productData.productId + ']" value="' + productData.variationId + '">';
                    }

                    if (productData.fieldLabel) {
                        hiddenFieldsHTML += '<input type="hidden" name="extra_product_field_labels[' + productData.productId + ']" value="' + productData.fieldLabel + '">';
                    }
                }
            }

            // Process selected information
            var infoKeys = Object.keys(selectedInformation);
            if (infoKeys.length > 0) {
                hasData = true;
                for (var i = 0; i < infoKeys.length; i++) {
                    var infoId = infoKeys[i];
                    var infoData = selectedInformation[infoId];

                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_ids[]" value="' + infoData.infoId + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_labels[' + infoData.infoId + ']" value="' + infoData.infoLabel + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_prices[' + infoData.infoId + ']" value="' + infoData.infoPrice + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_original_prices[' + infoData.infoId + ']" value="' + (infoData.infoOriginalPrice || 0) + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_images[' + infoData.infoId + ']" value="' + (infoData.imageUrl || '') + '">';
                    hiddenFieldsHTML += '<input type="hidden" name="extra_info_field_labels[' + infoData.infoId + ']" value="' + (infoData.fieldLabel || '') + '">';
                }
            }

            // Add flag if we have data
            if (hasData) {
                hiddenFieldsHTML += '<input type="hidden" name="has_product_extras" value="1">';
            }

            // Single DOM operation to add all hidden fields
            if (hiddenFieldsHTML) {
                $forms.append(hiddenFieldsHTML);
            }
        },

        /**
         * Collect all product extras data for AJAX add-to-cart
         * This method is called by add-to-cart.js to get current product extras data
         */
        getProductExtrasData: function () {
            var selectedProducts = window.shSelectedProducts || {};
            var selectedInformation = window.shSelectedInformation || {};

            // Convert to arrays for consistent structure
            var selectedProductsArray = [];
            var selectedInfoArray = [];

            // Convert selected products to array format
            $.each(selectedProducts, function (productId, productData) {
                selectedProductsArray.push({
                    productId: productData.productId,
                    quantity: productData.quantity,
                    productType: productData.productType,
                    variationData: productData.variationData || {},
                    variationId: productData.variationId || null,
                    title: productData.title || '',
                    price: productData.price || 0,
                    imageUrl: '', // Always empty - let server get fresh image
                });
            });

            // Convert selected information to array format
            $.each(selectedInformation, function (infoId, infoData) {
                selectedInfoArray.push({
                    infoId: infoData.infoId,
                    infoLabel: infoData.infoLabel,
                    infoPrice: infoData.infoPrice || 0,
                    imageUrl: infoData.imageUrl || '',
                });
            });

            // Create the data structure expected by add-to-cart.js
            var productExtrasData = {
                selectedProducts: selectedProductsArray,
                selectedInfo: selectedInfoArray,
                has_product_extras: selectedProductsArray.length > 0 || selectedInfoArray.length > 0 ? '1' : '0',
            };

            // Also include legacy format for backward compatibility
            if (selectedProductsArray.length > 0) {
                productExtrasData.extra_product_ids = [];
                productExtrasData.extra_product_qty = {};
                productExtrasData.extra_product_type = {};
                productExtrasData.extra_product_variation = {};
                productExtrasData.extra_product_variation_id = {};

                $.each(selectedProductsArray, function (index, productData) {
                    productExtrasData.extra_product_ids.push(productData.productId);
                    productExtrasData.extra_product_qty[productData.productId] = productData.quantity;
                    productExtrasData.extra_product_type[productData.productId] = productData.productType;

                    if (productData.variationData && Object.keys(productData.variationData).length > 0) {
                        productExtrasData.extra_product_variation[productData.productId] = productData.variationData;
                    }

                    if (productData.variationId) {
                        productExtrasData.extra_product_variation_id[productData.productId] = productData.variationId;
                    }
                });
            }

            if (selectedInfoArray.length > 0) {
                productExtrasData.extra_info_ids = [];
                productExtrasData.extra_info_labels = {};
                productExtrasData.extra_info_prices = {};
                productExtrasData.extra_info_images = {};

                $.each(selectedInfoArray, function (index, infoData) {
                    productExtrasData.extra_info_ids.push(infoData.infoId);
                    productExtrasData.extra_info_labels[infoData.infoId] = infoData.infoLabel;
                    productExtrasData.extra_info_prices[infoData.infoId] = infoData.infoPrice;
                    productExtrasData.extra_info_images[infoData.infoId] = infoData.imageUrl || '';
                });
            }

            // Also collect any PEWC (Product Extra & WooCommerce) fields
            var pewcData = this.collectPEWCFields();
            if (Object.keys(pewcData).length > 0) {
                $.extend(productExtrasData, pewcData);
            }

            return productExtrasData;
        },

        /**
         * Collect PEWC (Product Extra & WooCommerce) plugin fields
         */
        collectPEWCFields: function () {
            var pewcData = {};

            // Find all PEWC fields in the form
            $('form.cart, form.variations_form')
                .find('input, select, textarea')
                .each(function () {
                    var $field = $(this);
                    var name = $field.attr('name');

                    // Check if this is a PEWC field
                    if (name && (name.indexOf('pewc_') === 0 || name.indexOf('product_extras_') === 0)) {
                        var value = '';

                        if ($field.is(':checkbox') || $field.is(':radio')) {
                            if ($field.is(':checked')) {
                                value = $field.val();
                            }
                        } else {
                            value = $field.val();
                        }

                        if (value !== '') {
                            pewcData[name] = value;
                        }
                    }
                });

            return pewcData;
        },

        evaluateGroupConditions: function () {
            var currentAttributes = {};
            if (window.shCurrentVariation && window.shCurrentVariation.attributes) {
                $.each(window.shCurrentVariation.attributes, function (key, value) {
                    var attrName = key.replace('attribute_', '');
                    currentAttributes[attrName] = value;
                });
            } else {
                $('form.cart, form.variations_form')
                    .find('select[name^="attribute_"]')
                    .each(function () {
                        var key = $(this).attr('name');
                        var attrName = key.replace('attribute_', '');
                        currentAttributes[attrName] = $(this).val() || '';
                    });
            }

            $('[data-conditions]').each(function () {
                var $element = $(this);
                var conditionsAttr = $element.attr('data-conditions');
                var conditions = conditionsAttr ? JSON.parse(conditionsAttr) : [];
                var match = ($element.attr('data-condition-match') || 'any').toLowerCase();
                var action = ($element.attr('data-condition-action') || 'show').toLowerCase();
                var total = 0;
                var met = 0;

                if (conditions && conditions.length > 0) {
                    total = conditions.length;
                    $.each(conditions, function (index, condition) {
                        var field = condition.field || '';
                        var rule = (condition.rule || '').toLowerCase();
                        var value = condition.value || '';
                        var conditionMet = false;

                        if (field.indexOf('pa_') === 0) {
                            var attrKey = field;
                            var currentValue = currentAttributes[attrKey] || '';

                            switch (rule) {
                                case 'is':
                                    conditionMet = currentValue === value;
                                    break;
                                case 'is-not':
                                    conditionMet = currentValue !== value;
                                    break;
                                case 'contains':
                                    conditionMet = currentValue.indexOf(value) !== -1;
                                    break;
                                case 'does-not-contain':
                                    conditionMet = currentValue.indexOf(value) === -1;
                                    break;
                                default:
                                    conditionMet = false;
                            }

                            if (conditionMet) {
                                met++;
                            }
                        }
                    });

                    var conditionsMet = match === 'all' ? met === total && total > 0 : met > 0;
                    var showGroup;
                    if (action === 'show') {
                        showGroup = conditionsMet;
                    } else if (action === 'hide') {
                        showGroup = !conditionsMet;
                    } else {
                        showGroup = conditionsMet;
                    }

                    if (showGroup) {
                        $element.addClass('sh-conditions-met').show();
                        if ($element.hasClass('sh-product-extras-group-title') || $element.hasClass('sh-product-extras-group-placeholder')) {
                            $element.nextUntil('[data-conditions], .sh-product-extras-group-title, .sh-product-extras-group-placeholder').addClass('sh-conditions-met').show();
                        }
                    } else {
                        $element.removeClass('sh-conditions-met').hide();
                        if ($element.hasClass('sh-product-extras-group-title') || $element.hasClass('sh-product-extras-group-placeholder')) {
                            $element.nextUntil('[data-conditions], .sh-product-extras-group-title, .sh-product-extras-group-placeholder').removeClass('sh-conditions-met').hide();
                        }
                    }
                }
            });

            $('.sh-product-extras-container').each(function () {
                var $container = $(this);
                var anyVisible = $container.find('[data-conditions].sh-conditions-met').length > 0;
                if (anyVisible) {
                    $container.removeClass('sh-hidden');
                } else {
                    $container.addClass('sh-hidden');
                }
            });
        },
    };

    // Initialize when document is ready
    $(document).ready(function () {
        ProductExtrasWidget.init();
        ProductExtrasWidget.evaluateGroupConditions();
    });

    // Fallback initialization on window load (ensures all scripts are loaded)
    $(window).on('load', function () {
        if (!ProductExtrasWidget.initialized) {
            ProductExtrasWidget.init();
        }
        ProductExtrasWidget.evaluateGroupConditions();

        // Additional fallback for cart integration
        setTimeout(function () {
            if (ProductExtrasWidget.initialized && $('form.cart, form.variations_form').length > 0) {
                // Ensure cart integration is properly set up
                ProductExtrasWidget.bindCartIntegration();
            }
        }, 500);
    });

    // Re-initialize when Elementor preview is refreshed
    $(window).on('elementor/frontend/init', function () {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/sh_product_extras.default', function ($scope) {
                ProductExtrasWidget.init();
                ProductExtrasWidget.evaluateGroupConditions();
            });
        }
    });

    // Handle payment method changes to clear cache and ensure extras are properly included
    $(document).on('change', '[name="awcdp_deposit_option"], [name="trade_in"]', function () {
        ProductExtrasWidget.clearCache();
        ProductExtrasWidget.initializeFields();

        // Force cart form data update
        setTimeout(function () {
            ProductExtrasWidget.updateCartFormData();

            // Trigger WooCommerce fragments refresh to update cart
            $(document.body).trigger('wc_fragment_refresh');
        }, 100);
    });

    // Handle variation changes to clear cache
    $(document).on('found_variation reset_data', '.variations_form', function (event, variation) {
        ProductExtrasWidget.clearCache();
        ProductExtrasWidget.initializeFields();
        try {
            window.shCurrentVariation = variation || null;
        } catch (e) {}
        ProductExtrasWidget.evaluateGroupConditions();
    });

    $(document).on('found_variation', function (event, variation) {
        try {
            window.shCurrentVariation = variation || null;
        } catch (e) {}
        ProductExtrasWidget.evaluateGroupConditions();
    });

    $(document).on('hide_variation', function () {
        try {
            window.shCurrentVariation = null;
        } catch (e) {}
        ProductExtrasWidget.evaluateGroupConditions();
    });

    $(document).on('variation_data_synced', function (event, variation) {
        try {
            window.shCurrentVariation = variation || null;
        } catch (e) {}
        ProductExtrasWidget.evaluateGroupConditions();
    });

    // Make the widget object globally available
    window.ProductExtrasWidget = ProductExtrasWidget;
})(jQuery);

// Add some CSS for visual feedback
jQuery(document).ready(function ($) {
    if ($('head').find('#product-extras-widget-dynamic-css').length === 0) {
        var dynamicCSS = `
            <style id="product-extras-widget-dynamic-css">
                .sh-product-extras-container .field-changed {
                    border-color: #007cba !important;
                    box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.2) !important;
                    transition: all 0.3s ease;
                }
                
                .sh-product-extras-container .field-error {
                    border-color: #dc3545 !important;
                    box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.2) !important;
                }
                
                .sh-product-extras-container .pewc-checkbox-label.clicked,
                .sh-product-extras-container .pewc-radio-label.clicked {
                    background-color: #e3f2fd !important;
                    transform: scale(0.98);
                    transition: all 0.2s ease;
                }
                
                .field-tooltip {
                    background: #333;
                    color: #fff;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    max-width: 200px;
                    word-wrap: break-word;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                }
                
                .field-tooltip:before {
                    content: '';
                    position: absolute;
                    top: 100%;
                    left: 50%;
                    margin-left: -5px;
                    border-width: 5px;
                    border-style: solid;
                    border-color: #333 transparent transparent transparent;
                }
                
                .file-upload-feedback {
                    margin-top: 5px;
                    font-size: 12px;
                    color: #28a745;
                }
            </style>
        `;
        $('head').append(dynamicCSS);
    }
});
