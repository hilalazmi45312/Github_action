/**
 * Senheng Brand Display Widget Editor JavaScript
 */

(function($) {
    'use strict';

    var BrandDisplayWidgetEditor = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Initialize when Elementor editor loads
            $(window).on('elementor:init', this.onElementorInit.bind(this));
        },

        onElementorInit: function() {
            // Register editor handlers
            elementor.hooks.addAction('panel/open_editor/widget/sh_brand_display', this.onWidgetPanelOpen.bind(this));
            
            // Listen for control changes
            elementor.channels.editor.on('change', this.onControlChange.bind(this));
        },

        onWidgetPanelOpen: function(panel, model, view) {
            // Add custom behavior when widget panel opens
            this.setupControlDependencies(panel);
            this.addControlDescriptions(panel);
        },

        setupControlDependencies: function(panel) {
            // Setup dynamic control visibility based on display mode
            var displayModeControl = panel.content.currentView.children.findByModelCid(
                panel.content.currentView.getControlModel('display_mode').cid
            );

            if (displayModeControl) {
                displayModeControl.on('change', function() {
                    panel.content.currentView.render();
                });
            }
        },

        addControlDescriptions: function(panel) {
            // Add helpful descriptions to controls
            setTimeout(function() {
                var $panel = panel.$el;
                
                // Add description for display mode
                var $displayMode = $panel.find('[data-setting="display_mode"]').closest('.elementor-control');
                if ($displayMode.length && !$displayMode.find('.custom-description').length) {
                    $displayMode.append(
                        '<div class="custom-description" style="margin-top: 5px; font-size: 11px; color: #666;">' +
                        'Choose how brands should be displayed: current product brand, all brands, or specific selected brands.' +
                        '</div>'
                    );
                }

                // Add description for layout
                var $layout = $panel.find('[data-setting="layout"]').closest('.elementor-control');
                if ($layout.length && !$layout.find('.custom-description').length) {
                    $layout.append(
                        '<div class="custom-description" style="margin-top: 5px; font-size: 11px; color: #666;">' +
                        'Horizontal: brands in a row, Vertical: brands in a column, Grid: brands in a responsive grid.' +
                        '</div>'
                    );
                }
            }, 100);
        },

        onControlChange: function(controlView, elementView) {
            // Handle control changes
            if (!elementView || elementView.model.get('widgetType') !== 'sh_brand_display') {
                return;
            }

            var controlName = controlView.model.get('name');
            var controlValue = controlView.getControlValue();

            // Handle specific control changes
            switch (controlName) {
                case 'display_mode':
                    this.handleDisplayModeChange(controlValue, elementView);
                    break;
                case 'layout':
                    this.handleLayoutChange(controlValue, elementView);
                    break;
                case 'selected_brands':
                    this.handleSelectedBrandsChange(controlValue, elementView);
                    break;
            }
        },

        handleDisplayModeChange: function(value, elementView) {
            // Update preview based on display mode
            var $widget = elementView.$el.find('.sh-brand-display');
            
            if ($widget.length) {
                $widget.attr('data-display-mode', value);
                
                // Add visual indicator in editor
                var modeText = {
                    'current_product': 'Current Product Brand',
                    'all_brands': 'All Brands',
                    'specific_brands': 'Selected Brands'
                };
                
                $widget.attr('data-mode-label', modeText[value] || value);
            }
        },

        handleLayoutChange: function(value, elementView) {
            // Update preview layout
            var $widget = elementView.$el.find('.sh-brand-display');
            
            if ($widget.length) {
                // Remove existing layout classes
                $widget.removeClass('sh-brand-layout-horizontal sh-brand-layout-vertical sh-brand-layout-grid');
                
                // Add new layout class
                $widget.addClass('sh-brand-layout-' + value);
            }
        },

        handleSelectedBrandsChange: function(value, elementView) {
            // Update preview when specific brands are selected
            var $widget = elementView.$el.find('.sh-brand-display');
            
            if ($widget.length) {
                var brandCount = Array.isArray(value) ? value.length : 0;
                $widget.attr('data-selected-count', brandCount);
            }
        },

        // Utility function to show preview messages in editor
        showPreviewMessage: function($widget, message) {
            $widget.html(
                '<div class="sh-brand-preview-message" style="' +
                'padding: 20px; text-align: center; color: #666; ' +
                'border: 1px dashed #ccc; border-radius: 4px; ' +
                'background: #f9f9f9; font-size: 14px;">' +
                message +
                '</div>'
            );
        },

        // Function to validate widget settings
        validateSettings: function(settings) {
            var errors = [];

            if (settings.display_mode === 'specific_brands' && (!settings.selected_brands || settings.selected_brands.length === 0)) {
                errors.push('Please select at least one brand when using "Specific Brands" mode.');
            }

            if (settings.display_mode === 'all_brands' && (!settings.brands_limit || settings.brands_limit < 1)) {
                errors.push('Please set a valid number of brands to display.');
            }

            return errors;
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        BrandDisplayWidgetEditor.init();
    });

    // Expose to global scope
    window.SenhengBrandDisplayWidgetEditor = BrandDisplayWidgetEditor;

})(jQuery);