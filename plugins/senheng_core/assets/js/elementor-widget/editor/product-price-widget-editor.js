jQuery(function($){
    'use strict';

    // Product Price Widget Editor Functionality
    function initPriceWidgetEditor() {
        // Initialize WooCommerce variation forms in editor if needed
        $('.sh-price-widget').closest('.elementor-widget').find('form.variations_form').each(function(){
            var $form = $(this);
            if (!$form.data('wc-variation-form')) {
                $form.wc_variation_form();
            }
        });

        // Add editor-specific event handlers for price widget
        $(window).on('elementor/frontend/init', function() {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                // Price Widget Editor Events
                window.elementorFrontend.hooks.addAction('frontend/element_ready/sh_product_price.default', function($scope) {
                    var $widget = $scope.find('.sh-price-widget');
                    if ($widget.length) {
                        // Initialize variation form if it exists
                        var $form = $scope.find('form.variations_form');
                        if ($form.length && !$form.data('wc-variation-form')) {
                            $form.wc_variation_form();
                        }
                    }
                });
            }
        });

        // Handle Elementor editor changes to maintain consistent display
        if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
            // Listen for element changes (settings updates)
            $(document).on('elementor/editor/change', function(event, model) {
                if (model && model.get && model.get('widgetType') === 'sh_product_price') {
                    // Small delay to ensure the widget has re-rendered
                    setTimeout(function() {
                        var $widget = $('.elementor-widget-sh_product_price .sh-price-widget');
                        if ($widget.length) {
                            // Ensure variation form is initialized after re-render
                            var $form = $widget.closest('.elementor-widget').find('form.variations_form');
                            if ($form.length && !$form.data('wc-variation-form')) {
                                $form.wc_variation_form();
                            }
                        }
                    }, 100);
                }
            });

            // Listen for panel changes (when settings panel is opened/closed)
            $(document).on('elementor/panel/open_editor/widget', function(event, model) {
                if (model && model.get && model.get('widgetType') === 'sh_product_price') {
                    // Ensure widget maintains proper display when panel is opened
                    setTimeout(function() {
                        var $widget = $('.elementor-widget-sh_product_price .sh-price-widget');
                        if ($widget.length) {
                            // Ensure variation form is initialized
                            var $form = $widget.closest('.elementor-widget').find('form.variations_form');
                            if ($form.length && !$form.data('wc-variation-form')) {
                                $form.wc_variation_form();
                            }
                        }
                    }, 50);
                }
            });
        }
    }

    // Initialize price widget editor functionality
    if (window.elementorFrontend && window.elementorFrontend.isEditMode()) {
        initPriceWidgetEditor();
    }
});
