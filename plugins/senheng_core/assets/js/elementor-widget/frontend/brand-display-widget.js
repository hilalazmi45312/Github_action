/**
 * Senheng Brand Display Widget Frontend JavaScript
 */

(function($) {
    'use strict';

    var BrandDisplayWidget = {
        init: function() {
            this.bindEvents();
            this.initializeWidgets();
        },

        bindEvents: function() {
            // Initialize widgets when Elementor frontend loads
            $(window).on('elementor/frontend/init', this.onElementorFrontendInit.bind(this));
        },

        onElementorFrontendInit: function() {
            // Register widget handler
            elementorFrontend.hooks.addAction('frontend/element_ready/sh_brand_display.default', this.initWidget.bind(this));
        },

        initializeWidgets: function() {
            // Initialize widgets on page load (for non-Elementor pages)
            $('.sh-brand-display').each(function() {
                BrandDisplayWidget.initWidget($(this));
            });
        },

        initWidget: function($scope) {
            var $widget = $scope.find('.sh-brand-display');
            
            if ($widget.length === 0) {
                return;
            }

            this.setupBrandLinks($widget);
            this.setupLazyLoading($widget);
            this.setupAnalytics($widget);
        },

        setupBrandLinks: function($widget) {
            $widget.find('.sh-brand-link').on('click', function(e) {
                var $link = $(this);
                var brandName = $link.find('.sh-brand-name').text().trim();
                var brandUrl = $link.attr('href');

                // Add loading state
                $link.closest('.sh-brand-item').addClass('loading');

                // Track click event
                BrandDisplayWidget.trackBrandClick(brandName, brandUrl);

                // Allow default navigation
                return true;
            });
        },

        setupLazyLoading: function($widget) {
            // Implement lazy loading for brand images
            var $images = $widget.find('.sh-brand-image img');
            
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                $images.each(function() {
                    if (this.dataset.src) {
                        imageObserver.observe(this);
                    }
                });
            }
        },

        setupAnalytics: function($widget) {
            // Track widget view
            this.trackWidgetView($widget);

            // Track brand impressions
            this.trackBrandImpressions($widget);
        },

        trackWidgetView: function($widget) {
            // Track when the widget comes into view
            if ('IntersectionObserver' in window) {
                var widgetObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var layout = 'unknown';
                            if (entry.target.classList.contains('sh-brand-layout-horizontal')) {
                                layout = 'horizontal';
                            } else if (entry.target.classList.contains('sh-brand-layout-vertical')) {
                                layout = 'vertical';
                            } else if (entry.target.classList.contains('sh-brand-layout-grid')) {
                                layout = 'grid';
                            }

                            // Send analytics event
                            if (typeof gtag !== 'undefined') {
                                gtag('event', 'brand_widget_view', {
                                    'event_category': 'Brand Widget',
                                    'event_label': layout,
                                    'value': $(entry.target).find('.sh-brand-item').length
                                });
                            }

                            // Track with Insider if available
                            if (typeof window.insider !== 'undefined' && window.insider.event) {
                                window.insider.event('brand_widget_viewed', {
                                    layout: layout,
                                    brand_count: $(entry.target).find('.sh-brand-item').length
                                });
                            }

                            widgetObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.5 });

                widgetObserver.observe($widget[0]);
            }
        },

        trackBrandImpressions: function($widget) {
            // Track individual brand impressions
            if ('IntersectionObserver' in window) {
                var brandObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $brandItem = $(entry.target);
                            var brandName = $brandItem.find('.sh-brand-name').text().trim();

                            if (brandName && !$brandItem.data('impression-tracked')) {
                                $brandItem.data('impression-tracked', true);

                                // Send analytics event
                                if (typeof gtag !== 'undefined') {
                                    gtag('event', 'brand_impression', {
                                        'event_category': 'Brand Widget',
                                        'event_label': brandName
                                    });
                                }

                                // Track with Insider if available
                                if (typeof window.insider !== 'undefined' && window.insider.event) {
                                    window.insider.event('brand_impression', {
                                        brand_name: brandName
                                    });
                                }
                            }
                        }
                    });
                }, { threshold: 0.5 });

                $widget.find('.sh-brand-item').each(function() {
                    brandObserver.observe(this);
                });
            }
        },

        trackBrandClick: function(brandName, brandUrl) {
            // Track brand click events
            if (typeof gtag !== 'undefined') {
                gtag('event', 'brand_click', {
                    'event_category': 'Brand Widget',
                    'event_label': brandName,
                    'custom_parameters': {
                        'brand_url': brandUrl
                    }
                });
            }

            // Track with Insider if available
            if (typeof window.insider !== 'undefined' && window.insider.event) {
                window.insider.event('brand_clicked', {
                    brand_name: brandName,
                    brand_url: brandUrl
                });
            }


        },

        // Utility function to refresh widget content (for dynamic updates)
        refreshWidget: function($widget) {
            $widget.addClass('loading');
            
            // Re-initialize after content update
            setTimeout(function() {
                $widget.removeClass('loading');
                BrandDisplayWidget.initWidget($widget.closest('.elementor-widget-sh_brand_display'));
            }, 500);
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        BrandDisplayWidget.init();
    });

    // Expose to global scope for external access
    window.SenhengBrandDisplayWidget = BrandDisplayWidget;

})(jQuery);