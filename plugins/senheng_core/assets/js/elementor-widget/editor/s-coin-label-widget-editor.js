jQuery(function($){
    'use strict';

    // S-Coin Variation Handler Class
    class SCoinVariationHandler {
        constructor() {
            this.scoinWidgets = [];
            this.init();
        }

        init() {
            this.findSCoinWidgets();
            this.bindEvents();
        }

        findSCoinWidgets() {
            $('.sh-scoin-label').each((index, element) => {
                const $widget = $(element);
                const hasParentScoinData = $widget.data('has-parent-scoin');
                const hasParentScoin = hasParentScoinData === true || hasParentScoinData === 'true';
                const variations = $widget.data('variations') || {};
                
                this.scoinWidgets.push({
                    element: $widget,
                    hasParentScoin: hasParentScoin,
                    variations: variations
                });
            });
        }

        bindEvents() {
            // Listen for WooCommerce variation changes
            $(document).on('found_variation', this.handleVariationChange.bind(this));
            $(document).on('reset_data', this.handleVariationReset.bind(this));
            
            // Also listen for variation form changes (backup)
            $('.variations_form').on('change', 'select', this.handleVariationFormChange.bind(this));
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

        updateSCoinDisplay(selectedVariationId) {
            this.scoinWidgets.forEach(widget => {
                const shouldShow = this.shouldShowWidget(widget, selectedVariationId);
                
                if (shouldShow) {
                    widget.element.show();
                } else {
                    widget.element.hide();
                }
            });
        }

        shouldShowWidget(widget, selectedVariationId) {
            // If parent has S-coin, always show
            if (widget.hasParentScoin) {
                return true;
            }

            // If no variation selected, show if any variation has S-coin
            if (!selectedVariationId) {
                return Object.values(widget.variations).some(hasScoin => hasScoin);
            }

            // Show only if selected variation has S-coin
            return widget.variations[selectedVariationId] === true;
        }

        // Public method to refresh widgets (useful for dynamic content)
        refresh() {
            this.scoinWidgets = [];
            this.findSCoinWidgets();
        }
    }

    // S-Coin Label Widget Editor Functionality
    function initSCoinLabelWidgetEditor() {
        // Function to update icon height CSS custom property
        function updateIconHeight($widget, $img) {
            // Get the actual height of the image
            var imgHeight = $img.height() || $img.attr('height') || 80;
            
            // Check for Elementor style settings
            var elementorElement = $widget.closest('.elementor-element');
            if (elementorElement.length) {
                var elementId = elementorElement.attr('data-id');
                if (elementId && window.elementor && window.elementor.settings) {
                    try {
                        var settings = window.elementor.settings.page.getSettings();
                        var widgetSettings = settings.elements && settings.elements[elementId];
                        if (widgetSettings && widgetSettings.settings) {
                            // Check for icon_size setting
                            if (widgetSettings.settings.icon_size && widgetSettings.settings.icon_size.size) {
                                imgHeight = parseInt(widgetSettings.settings.icon_size.size) || imgHeight;
                            }
                        }
                    } catch (e) {
                        // Fallback to measured height
                    }
                }
            }
            
            // Set the CSS custom property on both widget and container
            $widget.css('--icon-height', imgHeight + 'px');
            $widget.closest('.elementor-widget-container').css('--icon-height', imgHeight + 'px');
            
            // Force update on image load
            $img.on('load', function() {
                var newHeight = $(this).height() || imgHeight;
                $widget.css('--icon-height', newHeight + 'px');
                $widget.closest('.elementor-widget-container').css('--icon-height', newHeight + 'px');
            });
        }
        
        // Add editor-specific event handlers for S-Coin label widget
        $(window).on('elementor/frontend/init', function() {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                // S-Coin Label Widget Editor Events
                window.elementorFrontend.hooks.addAction('frontend/element_ready/sh_scoin_label.default', function($scope) {
                    var $widget = $scope.find('.rebate-container-small.s-coin-label-dynamic');
                    if ($widget.length) {
                        // Ensure proper positioning of rebate info text
                        var $rebateInfo = $widget.find('.rebate-info');
                        var $img = $widget.find('img');
                        
                        if ($rebateInfo.length && $img.length) {
                            // Apply editor-specific positioning adjustments
                            $rebateInfo.css({
                                'position': 'absolute',
                                'pointer-events': 'none'
                            });
                            
                            // Update CSS custom property for dynamic font sizing
                            updateIconHeight($widget, $img);
                        }
                    }
                });
            }
        });

        // Handle Elementor editor changes to maintain consistent display
        if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
            // Listen for element changes (settings updates)
            $(document).on('elementor/editor/change', function(event, model) {
                if (model && model.get && model.get('widgetType') === 'sh_scoin_label') {
                    // Small delay to ensure the widget has re-rendered
                    setTimeout(function() {
                        var $widget = $('.elementor-widget-sh_scoin_label .rebate-container-small.s-coin-label-dynamic');
                        if ($widget.length) {
                            // Ensure rebate info positioning is maintained after re-render
                            var $rebateInfo = $widget.find('.rebate-info');
                            var $img = $widget.find('img');
                            
                            if ($rebateInfo.length) {
                                $rebateInfo.css({
                                    'position': 'absolute',
                                    'pointer-events': 'none'
                                });
                            }
                            
                            // Update icon height for dynamic sizing
                            if ($img.length) {
                                updateIconHeight($widget, $img);
                            }
                        }
                    }, 100);
                }
            });
            
            // Listen for any setting changes that might affect icon size
            $(document).on('elementor/editor/change', function(event, model) {
                if (model && model.get && (model.get('widgetType') === 'sh_scoin_label' || model.changed.icon_size)) {
                    setTimeout(function() {
                        $('.elementor-widget-sh_scoin_label .rebate-container-small.s-coin-label-dynamic').each(function() {
                            var $widget = $(this);
                            var $img = $widget.find('img');
                            if ($img.length) {
                                updateIconHeight($widget, $img);
                            }
                        });
                    }, 150);
                }
            });

            // Listen for panel changes (when settings panel is opened/closed)
            $(document).on('elementor/panel/show', function() {
                // Refresh S-Coin widget display when panel is shown
                $('.elementor-widget-sh_scoin_label .rebate-container-small.s-coin-label-dynamic').each(function() {
                    var $widget = $(this);
                    var $rebateInfo = $widget.find('.rebate-info');
                    if ($rebateInfo.length) {
                        $rebateInfo.css({
                            'position': 'absolute',
                            'pointer-events': 'none'
                        });
                    }
                });
            });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initSCoinLabelWidgetEditor();
        
        // Initialize variation handler for frontend
        if (!window.elementorFrontend || !window.elementorFrontend.isEditMode()) {
            window.sCoinVariationHandler = new SCoinVariationHandler();
        }
    });

    // Also initialize when Elementor is ready
    $(window).on('elementor/frontend/init', function() {
        initSCoinLabelWidgetEditor();
    });

    // Re-initialize variation handler on AJAX content updates
    $(document).on('updated_wc_div', function() {
        if (window.sCoinVariationHandler) {
            window.sCoinVariationHandler.refresh();
        }
    });

});