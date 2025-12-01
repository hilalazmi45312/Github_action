/**
 * Product Extras Widget - Elementor Editor JavaScript
 * Handles editor-specific functionality and interactions
 */

(function($) {
    'use strict';

    // Wait for Elementor to be ready
    $(window).on('elementor:init', function() {
        
        // Product Extras Widget Editor Handler
        var ProductExtrasWidgetEditor = {
            
            init: function() {
                this.bindEvents();
                this.setupPreview();
            },
            
            bindEvents: function() {
                // Listen for widget settings changes
                elementor.hooks.addAction('panel/open_editor/widget/sh-product-extras', this.onWidgetEdit.bind(this));
                
                // Listen for control changes
                elementor.channels.editor.on('change', this.onControlChange.bind(this));
                
                // Listen for preview refresh
                elementor.channels.editor.on('preview:loaded', this.onPreviewLoaded.bind(this));
            },
            
            onWidgetEdit: function(panel, model, view) {
                // Widget opened for editing
                this.currentWidget = {
                    panel: panel,
                    model: model,
                    view: view
                };
                
                // Add custom styling to the panel
                this.stylizePanel(panel);
                
                // Setup control dependencies
                this.setupControlDependencies(panel);
            },
            
            onControlChange: function(controlView, elementView) {
                if (!elementView || elementView.model.get('widgetType') !== 'sh-product-extras') {
                    return;
                }
                
                var controlName = controlView.model.get('name');
                var controlValue = controlView.getControlValue();
                
                // Handle specific control changes
                switch(controlName) {
                    case 'show_title':
                        this.toggleTitleControls(controlValue, elementView);
                        break;
                    case 'custom_title':
                        this.updatePreviewTitle(controlValue, elementView);
                        break;
                    case 'title_tag':
                        this.updateTitleTag(controlValue, elementView);
                        break;
                }
                
                // Refresh preview if needed
                this.refreshPreview(elementView);
            },
            
            onPreviewLoaded: function() {
                // Preview has been refreshed
                this.initializePreviewElements();
            },
            
            stylizePanel: function(panel) {
                // Add custom classes to the panel
                panel.$el.addClass('sh-product-extras-panel');
                
                // Add widget icon to the panel header
                var $header = panel.$el.find('.elementor-panel-navigation .elementor-panel-navigation-tab.elementor-active');
                if ($header.length && !$header.find('.sh-widget-icon').length) {
                    $header.prepend('<i class="sh-widget-icon fas fa-puzzle-piece"></i>');
                }
            },
            
            setupControlDependencies: function(panel) {
                // Setup dependencies between controls
                var self = this;
                
                // Title controls dependency
                panel.$el.on('change', '[data-setting="show_title"]', function() {
                    var showTitle = $(this).is(':checked');
                    self.toggleTitleControlsVisibility(showTitle, panel);
                });
                
                // Initialize visibility based on current values
                var showTitle = panel.getCurrentPageView().model.get('settings').get('show_title');
                this.toggleTitleControlsVisibility(showTitle, panel);
            },
            
            toggleTitleControls: function(showTitle, elementView) {
                // Toggle title-related controls visibility
                var $panel = elementView.getEditModel().get('editSettings').panel.$el;
                this.toggleTitleControlsVisibility(showTitle, { $el: $panel });
            },
            
            toggleTitleControlsVisibility: function(showTitle, panel) {
                var titleControls = [
                    'custom_title',
                    'title_tag',
                    'title_typography',
                    'title_color'
                ];
                
                titleControls.forEach(function(controlName) {
                    var $control = panel.$el.find('[data-setting="' + controlName + '"]').closest('.elementor-control');
                    if (showTitle) {
                        $control.slideDown(200);
                    } else {
                        $control.slideUp(200);
                    }
                });
            },
            
            updatePreviewTitle: function(title, elementView) {
                // Update title in the preview
                var $preview = elementView.$el.find('.sh-product-extras-title');
                if ($preview.length) {
                    $preview.text(title || 'Product Extras');
                }
            },
            
            updateTitleTag: function(tag, elementView) {
                // Update title tag in the preview
                var $preview = elementView.$el.find('.sh-product-extras-title');
                if ($preview.length) {
                    var currentText = $preview.text();
                    var newElement = $('<' + tag + '>').addClass('sh-product-extras-title').text(currentText);
                    $preview.replaceWith(newElement);
                }
            },
            
            refreshPreview: function(elementView) {
                // Debounced preview refresh
                clearTimeout(this.refreshTimeout);
                this.refreshTimeout = setTimeout(function() {
                    if (elementView && elementView.renderHTML) {
                        elementView.renderHTML();
                    }
                }, 300);
            },
            
            initializePreviewElements: function() {
                // Initialize any preview-specific elements
                var $widgets = $('.elementor-widget-sh-product-extras');
                
                $widgets.each(function() {
                    var $widget = $(this);
                    
                    // Add editor-specific classes
                    $widget.addClass('sh-editor-mode');
                    
                    // Add placeholder content if empty
                    var $container = $widget.find('.elementor-widget-container');
                    if ($container.is(':empty') || $container.text().trim() === '') {
                        $container.html('<div class="sh-editor-placeholder"><i class="fas fa-puzzle-piece"></i><p>Product Extras Widget</p><small>Configure settings in the panel</small></div>');
                    }
                });
            },
            
            setupPreview: function() {
                // Setup preview-specific functionality
                var self = this;
                
                // Listen for preview frame changes
                elementor.on('preview:loaded', function() {
                    self.initializePreviewElements();
                });
                
                // Handle widget drag and drop
                elementor.hooks.addAction('widget:before:render', function(widget) {
                    if (widget.model.get('widgetType') === 'sh-product-extras') {
                        // Add any pre-render logic here
                    }
                });
            }
        };
        
        // Initialize the editor handler
        ProductExtrasWidgetEditor.init();
        
        // Make it globally accessible for debugging
        window.ProductExtrasWidgetEditor = ProductExtrasWidgetEditor;
    });
    
    // CSS for editor-specific styling
    var editorCSS = `
        .sh-product-extras-panel .elementor-panel-navigation-tab .sh-widget-icon {
            margin-right: 8px;
            color: #007cba;
        }
        
        .sh-editor-placeholder {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            background: #f8f9fa;
        }
        
        .sh-editor-placeholder i {
            font-size: 32px;
            margin-bottom: 12px;
            color: #007cba;
        }
        
        .sh-editor-placeholder p {
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .sh-editor-placeholder small {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .elementor-widget-sh-product-extras.sh-editor-mode {
            min-height: 100px;
        }
    `;
    
    // Inject editor CSS
    if (!document.getElementById('sh-product-extras-editor-css')) {
        var style = document.createElement('style');
        style.id = 'sh-product-extras-editor-css';
        style.textContent = editorCSS;
        document.head.appendChild(style);
    }
    
})(jQuery);