jQuery(function($){
    'use strict';

    // Product Variation Widget Editor Functionality
    function initVariationWidgetEditor() {
        // Initialize WooCommerce variation forms in editor
        $('.sh-variation-form form.variations_form').each(function(){
            var $form = $(this);
            if (!$form.data('wc-variation-form')) {
                $form.wc_variation_form();
            }
        });

        // Add editor-specific event handlers for variation widget
        $(window).on('elementor/frontend/init', function() {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                // Variation Widget Editor Events
                window.elementorFrontend.hooks.addAction('frontend/element_ready/sh_product_variation.default', function($scope) {
                    var $form = $scope.find('form.variations_form');
                    if ($form.length && !$form.data('wc-variation-form')) {
                        $form.wc_variation_form();
                    }
                    
                    // Initialize editor event handlers (swatches/dropdowns sync only)
                    initSelectedValueDisplay($scope);
                });
            }
        });
    }

    // Initialize editor handlers: swatch clicks and dropdown changes (no selected-value row)
    function initSelectedValueDisplay($scope) {
        var $widget = $scope.find('.sh-variation-form');
        
        if ($widget.length === 0) {
            return;
        }
        
        // Find the main add to cart form on the page (for editor preview)
        var $mainForm = $('form.cart, form.variations_form').not($widget.find('form')).first();
        if (!$mainForm.length) {
            $mainForm = $('form').has('button[name="add-to-cart"]').first();
        }
        
        // Store reference to main form for syncing
        $widget.data('main-form', $mainForm);

        // Handle swatch clicks in editor
        $widget.off('click.editor-swatches', '.wd-swatch').on('click.editor-swatches', '.wd-swatch', function(e) {
            e.preventDefault();
            
            var $swatch = $(this);
            var $swatchesWrap = $swatch.closest('.wd-swatches-product');
            var $hiddenSelect = $swatchesWrap.siblings('.wd-swatch-select');
            var value = $swatch.data('value');
            var attributeName = $hiddenSelect.attr('name');
            
            // Remove active class from siblings
            $swatchesWrap.find('.wd-swatch').removeClass('wd-active');
            
            // Add active class to clicked swatch
            $swatch.addClass('wd-active');
            
            // Update hidden select
            if ($hiddenSelect.length) {
                $hiddenSelect.val(value).trigger('change');
            }
            
            // Sync with main form if it exists
            var $mainForm = $widget.data('main-form');
            if ($mainForm && $mainForm.length && attributeName) {
                var $mainSelect = $mainForm.find('select[name="' + attributeName + '"]');
                if ($mainSelect.length) {
                    $mainSelect.val(value).trigger('change');
                }
            }
        });
        
        
        // Handle dropdown changes in editor (sync only)
        $widget.off('change.editor-dropdown', 'select[data-attribute_name]').on('change.editor-dropdown', 'select[data-attribute_name]', function() {
            var $select = $(this);
            var selectedValue = $select.val();
            var attributeName = $select.attr('name');
            
            // Sync with main form if it exists
            var $mainForm = $widget.data('main-form');
            if ($mainForm && $mainForm.length && attributeName) {
                var $mainSelect = $mainForm.find('select[name="' + attributeName + '"]');
                if ($mainSelect.length && $mainSelect.val() !== selectedValue) {
                    $mainSelect.val(selectedValue).trigger('change');
                }
            }
        });
    }

    // Handle Elementor editor changes to maintain consistent display
    if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
        // Listen for element changes (settings updates)
        $(document).on('elementor/editor/change', function(event, model) {
            if (model && model.get && model.get('widgetType') === 'sh_product_variation') {
                // Small delay to ensure the widget has re-rendered
                setTimeout(function() {
                    var $widget = $('.elementor-widget-sh_product_variation .sh-variation-form');
                    if ($widget.length) {
                        // Re-initialize variation form after re-render
                        var $form = $widget.find('form.variations_form');
                        if ($form.length && !$form.data('wc-variation-form')) {
                            $form.wc_variation_form();
                        }
                        
                        // Re-initialize editor handlers for swatches/dropdowns
                        initSelectedValueDisplay($widget.closest('.elementor-widget-sh_product_variation'));
                    }
                }, 100);
            }
        });

        // Listen for panel changes (when settings panel is opened/closed)
        $(document).on('elementor/panel/open_editor/widget', function(event, model) {
            if (model && model.get && model.get('widgetType') === 'sh_product_variation') {
                // Ensure widget maintains proper display when panel is opened
                setTimeout(function() {
                    var $widget = $('.elementor-widget-sh_product_variation .sh-variation-form');
                    if ($widget.length) {
                        // Ensure variation form is initialized
                        var $form = $widget.find('form.variations_form');
                        if ($form.length && !$form.data('wc-variation-form')) {
                            $form.wc_variation_form();
                        }
                        
                        // Re-initialize editor handlers for swatches/dropdowns
                        initSelectedValueDisplay($widget.closest('.elementor-widget-sh_product_variation'));
                    }
                }, 50);
            }
        });

        // Listen for style changes that might affect the selected variation text and variation name
        $(document).on('elementor/editor/change:style', function(event, model) {
            if (model && model.get && model.get('widgetType') === 'sh_product_variation') {
                // Small delay to ensure styles have been applied
                setTimeout(function() {
                    var $widget = $('.elementor-widget-sh_product_variation');
                    if ($widget.length) {
                        // Force refresh of variation name/label to apply new styles
                        var $variationLabel = $widget.find('.variation-label');
                        if ($variationLabel.length) {
                            $variationLabel.hide().show();
                        }
                    }
                }, 150);
            }
        });

        // Listen for preview refresh events
        $(document).on('elementor/preview/loaded', function() {
            setTimeout(function() {
                var $widgets = $('.elementor-widget-sh_product_variation');
                $widgets.each(function() {
                    var $widget = $(this);
                    initSelectedValueDisplay($widget);
                });
            }, 200);
        });

        // Listen for widget render events
        if (window.elementorFrontend && window.elementorFrontend.hooks) {
            window.elementorFrontend.hooks.addAction('frontend/element_ready/widget', function($scope, $) {
                if ($scope.hasClass('elementor-widget-sh_product_variation')) {
                    setTimeout(function() {
                        initSelectedValueDisplay($scope);
                    }, 100);
                }
            });
        }

        // Add mutation observer to watch for DOM changes in widget areas
        if (window.MutationObserver) {
            var observer = new MutationObserver(function(mutations) {
                var shouldUpdate = false;
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'attributes') {
                        var $target = $(mutation.target);
                        if ($target.closest('.elementor-widget-sh_product_variation').length) {
                            shouldUpdate = true;
                        }
                    }
                });
                
                if (shouldUpdate) {
                    setTimeout(function() {
                        $('.elementor-widget-sh_product_variation').each(function() {
                            var $widget = $(this);
                            if ($widget.find('.sh-variation-form').length) {
                                initSelectedValueDisplay($widget);
                            }
                        });
                    }, 50);
                }
            });
            
            // Start observing
            observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        }
    }

    // Initialize variation widget editor functionality
    if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
        initVariationWidgetEditor();
    }
});
