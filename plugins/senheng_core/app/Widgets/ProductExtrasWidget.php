<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ProductExtrasWidget extends \Elementor\Widget_Base
{
    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        
        // Hook early to potentially remove default display
        add_action('wp', [$this, 'maybe_remove_default_display']);
    }
    
    /**
     * Initialize AJAX handlers - called statically to ensure they're always registered
     */
    public static function init_ajax_handlers() {
        add_action('wp_ajax_sh_add_extras_to_cart', [self::class, 'handle_add_extras_to_cart_static']);
        add_action('wp_ajax_nopriv_sh_add_extras_to_cart', [self::class, 'handle_add_extras_to_cart_static']);
    }
    
    /**
     * Static wrapper for AJAX handler
     */
    public static function handle_add_extras_to_cart_static() {
        // Create a temporary instance to call the instance method
        $instance = new self();
        $instance->handle_add_extras_to_cart();
    }
    
    /**
     * Maybe remove the default plugin display based on widget settings
     */
    public function maybe_remove_default_display() {
        // Only run on product pages
        if (!is_product()) {
            return;
        }
        
        // Check if this widget is being used on the current page
        if ($this->is_widget_used_on_page()) {
            // Get widget settings (this is a simplified approach)
            // In a real scenario, you'd need to parse the page content to get actual settings
            remove_action('woocommerce_before_add_to_cart_button', 'pewc_product_extra_fields');
        }
    }
    
    /**
     * Check if this widget is being used on the current page
     */
    private function is_widget_used_on_page() {
        global $post;
        if (!$post) {
            return false;
        }
        
        // Check if Elementor is active and get document
        if (class_exists('\Elementor\Plugin')) {
            $document = \Elementor\Plugin::$instance->documents->get($post->ID);
            if ($document) {
                $elements_data = $document->get_elements_data();
                return $this->search_for_widget_in_elements($elements_data);
            }
        }
        
        // Fallback: Check if the post content contains our widget
        return strpos($post->post_content, 'sh-product-extras') !== false;
    }
    
    /**
     * Recursively search for our widget in Elementor elements
     */
    private function search_for_widget_in_elements($elements) {
        foreach ($elements as $element) {
            if (isset($element['widgetType']) && $element['widgetType'] === 'sh-product-extras') {
                return true;
            }
            
            // Check nested elements (sections, columns, etc.)
            if (isset($element['elements']) && is_array($element['elements'])) {
                if ($this->search_for_widget_in_elements($element['elements'])) {
                    return true;
                }
            }
        }
        
        return false;
    }

    public function get_name()
    {
        return 'sh_product_extras';
    }

    public function get_title()
    {
        return esc_html__('Senheng Product Extras', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-form-horizontal';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['product', 'extras', 'woocommerce', 'fields', 'add-ons'];
    }

    /**
     * Check if the product-extras-for-woocommerce plugin is active
     */
    private function is_product_extras_plugin_active()
    {
        return is_plugin_active('product-extras-for-woocommerce/product-extras-for-woocommerce.php');
    }

    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        if (!$this->is_product_extras_plugin_active()) {
            $this->add_control(
                'plugin_notice',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => '<div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; border: 1px solid #f5c6cb;">' .
                             '<strong>Notice:</strong> The "Product Extras for WooCommerce" plugin is required for this widget to function properly. ' .
                             'Please install and activate the plugin to use this widget.' .
                             '</div>',
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                ]
            );
        }

        $this->add_control(
            'show_title',
            [
                'label' => esc_html__('Show Section Title', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'section_title',
            [
                'label' => esc_html__('Section Title', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Product Options', 'senheng_core'),
                'placeholder' => esc_html__('Enter section title', 'senheng_core'),
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'hide_when_no_extras',
            [
                'label' => esc_html__('Hide When No Extras', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'senheng_core'),
                'label_off' => esc_html__('No', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Hide the widget when the product has no extra fields', 'senheng_core'),
            ]
        );

        $this->add_control(
            'remove_default_display',
            [
                'label' => esc_html__('Remove Default Plugin Display', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'senheng_core'),
                'label_off' => esc_html__('No', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Remove the default product extras display before the add to cart button', 'senheng_core'),
            ]
        );



        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Title Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-product-extras-title',
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Title Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-title' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'title_margin',
            [
                'label' => esc_html__('Title Margin', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label' => esc_html__('Container Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'container_border',
                'label' => esc_html__('Container Border', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-product-extras-container',
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label' => esc_html__('Container Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Price Style Section
        $this->start_controls_section(
            'price_style_section',
            [
                'label' => esc_html__('Price Style', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'regular_price_color',
            [
                'label' => esc_html__('Regular Price Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-regular .woocommerce-Price-amount.amount bdi' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-regular' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'sale_price_color',
            [
                'label' => esc_html__('Sale Price Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-sale .woocommerce-Price-amount.amount bdi' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-sale' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-sale .from-text' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'original_price_color',
            [
                'label' => esc_html__('Original Price Color (Strikethrough)', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-original bdi' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-product-extras-container .sh-price-original' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'original_price_typography',
                'label' => esc_html__('Original Price Typography (Strikethrough)', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-product-extras-container .sh-price-original, {{WRAPPER}} .sh-product-extras-container .sh-price-original .woocommerce-Price-amount.amount, {{WRAPPER}} .sh-product-extras-container .sh-price-original .woocommerce-Price-amount.amount bdi',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'sale_price_typography',
                'label' => esc_html__('Sales Price Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-product-extras-container .sh-price-sale, {{WRAPPER}} .sh-product-extras-container .sh-price-sale .woocommerce-Price-amount.amount, {{WRAPPER}} .sh-product-extras-container .sh-price-sale .woocommerce-Price-amount.amount bdi',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'from_text_typography',
                'label' => esc_html__('From Text Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-product-extras-container .sh-price-sale .from-text, {{WRAPPER}} .sh-product-extras-container .sh-price-regular .from-text',
            ]
        );

        $this->end_controls_section();

        // Swatch Style Section
        $this->start_controls_section(
            'swatch_style_section',
            [
                'label' => esc_html__('Swatch Style', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'color_swatch_heading',
            [
                'label' => esc_html__('Color Swatches', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'color_swatch_width',
            [
                'label' => esc_html__('Width', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 15,
                        'max' => 80,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 35,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-color-swatch' => 'width: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-image-swatch' => 'width: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'color_swatch_height',
            [
                'label' => esc_html__('Height', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 15,
                        'max' => 80,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 35,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-color-swatch' => 'height: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-image-swatch' => 'height: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'color_swatch_margin',
            [
                'label' => esc_html__('Margin', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '5',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-color-swatch' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-color-swatches .wd-swatch-wrap .sh-image-swatch' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'text_swatch_heading',
            [
                'label' => esc_html__('Text Swatches', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_swatch_typography',
                'label' => esc_html__('Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-text-swatch, {{WRAPPER}} .sh-text-swatch .wd-swatch-text, {{WRAPPER}} .sh-color-text-swatch, {{WRAPPER}} .sh-variation-swatch.sh-text-swatch',
                'fields_options' => [
                    'font_size' => [
                        'default' => [
                            'unit' => 'px',
                            'size' => 14,
                        ],
                    ],
                    'font_weight' => [
                        'default' => '500',
                    ],
                ],
            ]
        );

        $this->add_responsive_control(
            'text_swatch_margin',
            [
                'label' => esc_html__('Margin', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default' => [
                    'top' => '0',
                    'right' => '5',
                    'bottom' => '5',
                    'left' => '0',
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-text-swatch' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-color-text-swatch' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-variation-swatch.sh-text-swatch' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'text_swatch_active_heading',
            [
                'label' => esc_html__('Active State', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'text_swatch_active_text_color',
            [
                'label' => esc_html__('Active Text Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .sh-text-swatch.wd-active' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-text-swatch.wd-active .wd-swatch-text' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-color-text-swatch.wd-active' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'text_swatch_active_bg_color',
            [
                'label' => esc_html__('Active Background Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .sh-text-swatch.wd-active' => 'background-color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-color-text-swatch.wd-active' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_swatch_active_typography',
                'label' => esc_html__('Active Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-text-swatch.wd-active, {{WRAPPER}} .sh-text-swatch.wd-active .wd-swatch-text, {{WRAPPER}} .sh-color-text-swatch.wd-active, {{WRAPPER}} .sh-variation-swatch.sh-text-swatch.wd-active',
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Check if plugin is active
        if (!$this->is_product_extras_plugin_active()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="sh-product-extras-notice">';
                echo '<p><strong>Notice:</strong> The "Product Extras for WooCommerce" plugin is required for this widget to function properly.</p>';
                echo '</div>';
            }
            return;
        }

        // Check if we're on a product page
        if (!is_product()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="sh-product-extras-notice">';
                echo '<p>This widget will display product extras on single product pages.</p>';
                echo '</div>';
            }
            return;
        }

        global $product;
        if (!$product || !is_a($product, 'WC_Product')) {
            return;
        }

        $settings = $this->get_settings_for_display();
        
        // Remove default plugin display if enabled
        if ($settings['remove_default_display'] === 'yes') {
            remove_action('woocommerce_before_add_to_cart_button', 'pewc_product_extra_fields');
        }
        
        // Enqueue widget assets only if there are extra fields
        $this->enqueue_widget_assets(!empty($extra_fields));
        
        // Cache expensive pewc_get_extra_fields operation
        $product_id = $product->get_id();
        $cache_key = 'pewc_extra_fields_' . $product_id;
        $extra_fields = wp_cache_get($cache_key, 'product_extras_widget');
        
        if ($extra_fields === false && function_exists('pewc_get_extra_fields')) {
            $extra_fields = pewc_get_extra_fields($product_id);
            wp_cache_set($cache_key, $extra_fields, 'product_extras_widget', 300); // Cache for 5 minutes
        }
        
        // If no extra fields and hide_when_no_extras is enabled, don't render
        if (empty($extra_fields) && $settings['hide_when_no_extras'] === 'yes') {
            return;
        }

        echo '<div class="sh-product-extras-container">';

        // Display title if enabled
        if ($settings['show_title'] === 'yes' && !empty($settings['section_title'])) {
            echo '<h3 class="sh-product-extras-title">' . esc_html($settings['section_title']) . '</h3>';
        }
        
        // Display the product extras fields
        echo '<div class="sh-product-extras-fields">';
        
        // Check if any field has checkbox list type to automatically use card layout
        $has_checkbox_fields = false;
        if ($extra_fields !== false) {
            
            if (!empty($extra_fields) && is_array($extra_fields)) {
                foreach ($extra_fields as $group) {
                    if (!empty($group['items']) && is_array($group['items'])) {
                        foreach ($group['items'] as $item) {
                            if (isset($item['field_type']) && in_array($item['field_type'], ['checkboxes', 'checkbox_list', 'information','products'])) {
                                $has_checkbox_fields = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        
        if ($has_checkbox_fields) {
            // Use custom card rendering with cached data
            if ($extra_fields !== false) {
                $this->render_product_cards($extra_fields, $product);
            } else {
                echo '<p>Product extras functionality is not available.</p>';
            }
        } else {
            // Use the plugin's function to display product extras
            if (function_exists('pewc_product_extra_fields')) {
                // Temporarily increase the did_action count to bypass the duplicate check
                global $wp_actions;
                $original_count = isset($wp_actions['woocommerce_before_add_to_cart_button']) ? $wp_actions['woocommerce_before_add_to_cart_button'] : 0;
                $wp_actions['woocommerce_before_add_to_cart_button'] = 0;
                
                pewc_product_extra_fields();
                
                // Restore the original count
                $wp_actions['woocommerce_before_add_to_cart_button'] = $original_count;
            } else {
                // Fallback if function doesn't exist
                echo '<p>Product extras functionality is not available.</p>';
            }
        }
        
        echo '</div>';
        
        // Output variation data as JSON for client-side processing
        $this->output_variation_data($product);
        
        // Enqueue widget assets
        $this->enqueue_widget_assets();
        echo '</div>';
    }

    /**
     * Output variation data as JSON for client-side processing
     */
    private function output_variation_data($product)
    {
        if (!$product || !$product->is_type('variable')) {
            return;
        }

        $available_variations = $product->get_available_variations();
        $variation_data = [];

        foreach ($available_variations as $variation) {
            $variation_product = wc_get_product($variation['variation_id']);
            if (!$variation_product) {
                continue;
            }

            $variation_data[] = [
                'variation_id' => $variation['variation_id'],
                'attributes' => $variation['attributes'],
                'price_html' => $variation_product->get_price_html(),
                'is_in_stock' => $variation_product->is_in_stock(),
                'stock_quantity' => $variation_product->get_stock_quantity(),
                'image_url' => wp_get_attachment_image_url($variation_product->get_image_id(), 'woocommerce_thumbnail')
            ];
        }

        if (!empty($variation_data)) {
            echo '<script type="application/json" id="sh-variation-data-' . esc_attr($product->get_id()) . '">';
            echo wp_json_encode($variation_data);
            echo '</script>';
        }
        }

    /**
     * Render product extras as cards with checkbox + image layout
     */
    private function render_product_cards($extra_fields, $product)
    {
        if (empty($extra_fields)) {
            return;
        }

        foreach ($extra_fields as $group_id => $group) {
            if (empty($group['items'])) {
                continue;
            }
            
            // Get group conditions for attribute-based visibility
            $group_conditions = $this->get_group_conditions($group_id);
            $condition_action = $this->get_group_condition_action($group_id);
            $condition_match = $this->get_group_condition_match($group_id);
            $condition_attrs = '';
            
            if (!empty($group_conditions) || !empty($condition_action) || !empty($condition_match)) {
                if (!empty($group_conditions)) {
                    $condition_attrs .= ' data-conditions="' . esc_attr(json_encode($group_conditions)) . '"';
                }
                $condition_attrs .= ' data-group-id="' . esc_attr($group_id) . '"';
                if (!empty($condition_action)) {
                    $condition_attrs .= ' data-condition-action="' . esc_attr($condition_action) . '"';
                }
                if (!empty($condition_match)) {
                    $condition_attrs .= ' data-condition-match="' . esc_attr($condition_match) . '"';
                }
                if (empty($condition_action) || $condition_action === 'show') {
                    if (empty($condition_action) || $condition_action === 'show') {
                        $condition_attrs .= ' style="display:none"';
                    }
                }
            }
            
            // Display group title if available
            $group_title = '';
            if (isset($group['group_title']) && !empty($group['group_title'])) {
                $group_title = $group['group_title'];
            } elseif (isset($group['title']) && !empty($group['title'])) {
                $group_title = $group['title'];
            }
            
            if (!empty($group_title)) {
                echo '<h4 class="sh-product-extras-group-title"' . $condition_attrs . '>' . esc_html($group_title) . '</h4>';
            } else {
                echo '<div class="sh-product-extras-group-placeholder"' . $condition_attrs . '></div>';
            }
            
            foreach ($group['items'] as $item_id => $item) {
                // Skip if field type is empty
                if (empty($item['field_type'])) {
                    continue;
                }
                
                // Display field label if available (outside wrapper)
                $field_label = '';
                if (isset($item['field_label']) && !empty($item['field_label'])) {
                    $field_label = $item['field_label'];
                }
                
                if (!empty($field_label)) {
                    echo '<h4 class="sh-product-extras-field-label"' . $condition_attrs . '>' . esc_html($field_label) . '</h4>';
                }
                
                // Handle field types that support custom card rendering
                if (in_array($item['field_type'], ['products', 'checkboxes', 'checkbox_list', 'information', 'select', 'radio', 'image_swatch', 'select-box'])) {
                    // Add special wrapper class for information fields
                    $wrapper_class = 'sh-product-extras-cards-wrapper';
                    if ($item['field_type'] === 'information') {
                        $wrapper_class .= ' sh-information-wrapper';
                    }
                    
            // Add condition attributes to card wrappers
            $condition_attrs = '';
            if (!empty($group_conditions) || !empty($condition_action) || !empty($condition_match)) {
                if (!empty($group_conditions)) {
                    $condition_attrs .= ' data-conditions="' . esc_attr(json_encode($group_conditions)) . '"';
                }
                $condition_attrs .= ' data-group-id="' . esc_attr($group_id) . '"';
                if (!empty($condition_action)) {
                    $condition_attrs .= ' data-condition-action="' . esc_attr($condition_action) . '"';
                }
                if (!empty($condition_match)) {
                    $condition_attrs .= ' data-condition-match="' . esc_attr($condition_match) . '"';
                }
                if (empty($condition_action) || $condition_action === 'show') {
                    $condition_attrs .= ' style="display:none"';
                }
            }
                    
                    echo '<div class="' . $wrapper_class . '"' . $condition_attrs . '>';
                    
                    // Handle products field type
                    if ($item['field_type'] === 'products') {
                        $this->render_product_field_cards($item, $group_id, $item_id, $field_label);
                    } elseif ($item['field_type'] === 'information') {
                        // Handle information field type with card-style design
                        $this->render_information_field_cards($item, $group_id, $item_id, $field_label);
                    } elseif ($item['field_type'] === 'select' || $item['field_type'] === 'select-box') {
                        // Handle select field type with card-style design
                        $this->render_select_field_cards($item, $group_id, $item_id);
                    } elseif ($item['field_type'] === 'radio') {
                        // Handle radio field type with card-style design
                        $this->render_radio_field_cards($item, $group_id, $item_id);
                    } elseif ($item['field_type'] === 'image_swatch') {
                        // Handle image swatch field type with card-style design
                        $this->render_image_swatch_field_cards($item, $group_id, $item_id);
                    } else {
                        // Handle checkbox/checkbox_list field types
                        $this->render_checkbox_field_cards($item, $group_id, $item_id);
                    }
                    
                    echo '</div>'; // .sh-product-extras-cards-wrapper
                } else {
                    // For all other field types, render using the plugin's default field rendering (only if it has content)
                    $this->render_default_field($item, $group_id, $item_id, $group_conditions, $condition_action, $condition_match);
                }
            }
        }
        
        // Enqueue widget assets
        $this->enqueue_widget_assets();
    }

    /**
     * Render product field cards
     */
    private function render_product_field_cards($item, $group_id, $item_id, $field_label = '')
    {
        // Get child products
        if (empty($item['child_products'])) {
            return;
        }
        
        foreach ($item['child_products'] as $child_product_id) {
            $child_product = wc_get_product($child_product_id);
            if (!is_object($child_product) || get_post_status($child_product_id) != 'publish') {
                continue;
            }
            
            // Get product data
            $product_title = get_the_title($child_product_id);
            $product_description = get_the_excerpt($child_product_id);
            
            // Get product image with proper fallback
            $product_image = '';
            if ($child_product->is_type('variation')) {
                // For variation products, get the variation image first, then fall back to parent
                $variation_image_id = $child_product->get_image_id();
                if ($variation_image_id) {
                    $product_image = wp_get_attachment_image_url($variation_image_id, 'woocommerce_thumbnail');
                } else {
                    // Fall back to parent product image
                    $parent_product = wc_get_product($child_product->get_parent_id());
                    if ($parent_product) {
                        $parent_image_id = $parent_product->get_image_id();
                        if ($parent_image_id) {
                            $product_image = wp_get_attachment_image_url($parent_image_id, 'woocommerce_thumbnail');
                        }
                    }
                }
            } else {
                // For regular products, get the product image
                $image_id = $child_product->get_image_id();
                if ($image_id) {
                    $product_image = wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail');
                }
            }
            
            // Final fallback to placeholder
            if (empty($product_image)) {
                $product_image = wc_placeholder_img_src('woocommerce_thumbnail');
            }
            
            // Get variation attributes for both variable and variation products
            $variation_attributes = array();
            if ($child_product->is_type('variation')) {
                $variation_attributes = $child_product->get_variation_attributes();
            } elseif ($child_product->is_type('variable')) {
                // For variable products, get the product attributes
                $product_attributes = $child_product->get_attributes();
                foreach ($product_attributes as $attribute) {
                    if ($attribute->get_variation()) {
                        $attribute_name = $attribute->get_name();
                        $terms = wc_get_product_terms($child_product_id, $attribute_name, array('fields' => 'names'));
                        if (!empty($terms)) {
                            $variation_attributes[$attribute_name] = implode(', ', $terms);
                        }
                    }
                }
            }
            
            // Calculate pricing
            $child_price = pewc_maybe_include_tax($child_product, $child_product->get_price());
            $original_price = $child_price;
            $discounted_price = null;
            
            // Apply discount if available
            if (!empty($item['child_discount']) && !empty($item['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($child_price, $item['child_discount'], $item['discount_type']);
                $price_html = wc_format_sale_price($original_price, $discounted_price);
                $option_cost = $discounted_price;
            } else {
                $price_html = $child_product->get_price_html();
                $option_cost = $child_price;
            }
            
            // Check stock availability
            $disabled = '';
            $disabled_class = '';
            
            // For variable products, let JavaScript handle variation-level stock checking
            // Only disable at PHP level for simple products or when the product is not purchasable
            if (!$child_product->is_purchasable()) {
                $disabled = 'disabled';
                // Don't add sh-product-disabled class to the entire card for variable products
                // Let JavaScript handle the disabled state based on variation selection
                if (!$child_product->is_type('variable')) {
                    $disabled_class = 'sh-product-disabled';
                }
            } elseif (!$child_product->is_type('variable') && (!$child_product->is_in_stock() && !$child_product->backorders_allowed())) {
                // Only disable simple products that are out of stock
                $disabled = 'disabled';
                $disabled_class = 'sh-product-disabled';
            }
            
            // Generate field IDs
            $field_id = $group_id . '_' . $item_id;
            $checkbox_id = $field_id . '_' . $child_product_id;
            $field_name = $field_id . '_child_product';
            
            echo '<div class="sh-product-extra-card ' . $disabled_class . '" data-product-id="' . esc_attr($child_product_id) . '" data-field-id="' . esc_attr($field_id) . '" data-original-price="' . esc_attr($price_html) . '">';
            
            // Checkbox
            echo '<div class="sh-product-checkbox">';
            echo '<input type="checkbox" class="sh-checkbox-input sh-product-checkbox-input" name="' . esc_attr($field_name) . '[]" id="' . esc_attr($checkbox_id) . '" value="' . esc_attr($child_product_id) . '" data-option-cost="' . esc_attr($option_cost) . '" data-product-id="' . esc_attr($child_product_id) . '" data-product-type="' . esc_attr($child_product->get_type()) . '" data-product-field-label="' . esc_attr($field_label) . '" ' . $disabled . '>';
            echo '<label for="' . esc_attr($checkbox_id) . '" class="sh-checkbox-label"></label>';
            echo '</div>';
            
            // Product Image
            echo '<div class="sh-product-image">';
            echo '<img src="' . esc_url($product_image) . '" alt="' . esc_attr($product_title) . '" loading="lazy">';
            echo '</div>';
            
            // Product Details
            echo '<div class="sh-product-details">';
            
            // Title, Price and Quantity Row - All on same line
            // Main container with left and right sections
            echo '<div class="sh-product-main-row">';
            
            // Left section: Title and Variations
            echo '<div class="sh-product-left-section">';
            
            // Product Title
            echo '<h4 class="sh-product-title"><a href="' . esc_url(get_permalink($child_product_id)) . '" target="_blank" class="sh-product-link">' . esc_html($product_title) . '</a></h4>';
            
            // Display interactive variation selectors if available
            if (!empty($variation_attributes)) {
                echo '<div class="sh-product-variations">';
                $this->render_variation_selectors($child_product, $variation_attributes, $item_id);
                echo '</div>';
            }
            
            echo '</div>'; // .sh-product-left-section
            
            // Right section: Price and Quantity (stacked vertically)
            echo '<div class="sh-product-right-section">';
            
            // Price Section
            $price_html_content = '';
            if ($discounted_price !== null) {
                $price_html_content = '<span class="sh-price-sale">' . wc_price($discounted_price) . '</span>';
                $price_html_content .= '<span class="sh-price-original">' . wc_price($original_price) . '</span>';
            } else {
                // Handle variable products with custom price display
                if ($child_product->is_type('variable')) {
                    $variations = $child_product->get_available_variations();
                    if (!empty($variations)) {
                        $lowest_price = null;
                        $highest_original_price = null;
                        
                        foreach ($variations as $variation) {
                            $variation_obj = wc_get_product($variation['variation_id']);
                            if ($variation_obj) {
                                $variation_price = $variation_obj->get_price();
                                $variation_regular_price = $variation_obj->get_regular_price();
                                
                                if ($lowest_price === null || $variation_price < $lowest_price) {
                                    $lowest_price = $variation_price;
                                }
                                
                                if ($highest_original_price === null || $variation_regular_price > $highest_original_price) {
                                    $highest_original_price = $variation_regular_price;
                                }
                            }
                        }
                        
                        if ($lowest_price !== null && $highest_original_price !== null && $lowest_price < $highest_original_price) {
                            $price_html_content = '<span class="sh-price-sale"><span class="from-text">From </span>' . wc_price($lowest_price) . '</span>';
                            $price_html_content .= '<span class="sh-price-original">' . wc_price($highest_original_price) . '</span>';
                        } else {
                            $price_html_content = '<span class="sh-price-regular"><span class="from-text">From </span>' . wc_price($lowest_price ?: $child_price) . '</span>';
                        }
                    } else {
                        $price_html_content = '<span class="sh-price-regular">' . wc_price($child_price) . '</span>';
                    }
                } else {
                    // Handle simple products
                    $regular_price = $child_product->get_regular_price();
                    $sale_price = $child_product->get_sale_price();
                    
                    if ($sale_price && $sale_price < $regular_price) {
                        $price_html_content = '<span class="sh-price-sale">' . wc_price($sale_price) . '</span>';
                        $price_html_content .= '<span class="sh-price-original">' . wc_price($regular_price) . '</span>';
                    } else {
                        $price_html_content = '<span class="sh-price-regular">' . wc_price($child_price) . '</span>';
                    }
                }
            }
            
            echo '<div class="sh-product-price">' . $price_html_content . '</div>';
            
            // Quantity Selector
            echo '<div class="sh-product-quantity">';
            echo '<span class="sh-qty-label">Qty:</span>';
            echo '<div class="sh-quantity-controls">';
            echo '<button type="button" class="sh-qty-btn sh-qty-minus" data-product-id="' . esc_attr($child_product_id) . '" ' . $disabled . '>−</button>';
            echo '<input type="number" class="sh-qty-input" name="' . esc_attr($field_id) . '_child_quantity_' . esc_attr($child_product_id) . '" value="1" min="1" max="10" step="1" data-product-id="' . esc_attr($child_product_id) . '" ' . $disabled . '>';
            echo '<button type="button" class="sh-qty-btn sh-qty-plus" data-product-id="' . esc_attr($child_product_id) . '" ' . $disabled . '>+</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>'; // .sh-product-right-section
            echo '</div>'; // .sh-product-main-row
            
            // Output variation data for this child product if it's variable
            if ($child_product->is_type('variable')) {
                $this->output_variation_data($child_product);
            }
            
            if (!empty($product_description)) {
                echo '<p class="sh-product-description">' . esc_html(wp_trim_words($product_description, 15)) . '</p>';
            }
            
            echo '</div>'; // .sh-product-details
            
            echo '</div>'; // .sh-product-extra-card
        }
    }

    /**
     * Render checkbox field cards
     */
    private function render_checkbox_field_cards($item, $group_id, $item_id)
    {
        // Get checkbox options
        if (empty($item['field_options'])) {
            return;
        }
        
        $field_id = $group_id . '_' . $item_id;
        $field_name = $field_id;
        
        foreach ($item['field_options'] as $option_key => $option) {
            $option_label = isset($option['option_label']) ? $option['option_label'] : '';
            $option_price = isset($option['option_price']) ? floatval($option['option_price']) : 0;
            $option_image = isset($option['option_image']) ? $option['option_image'] : '';
            
            if (empty($option_label)) {
                continue;
            }
            
            // Calculate final price (with discounts if applicable)
            $final_option_price = $option_price;
            
            // Apply discount if available at field level
            if (!empty($item['child_discount']) && !empty($item['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $item['child_discount'], $item['discount_type']);
                if ($discounted_price !== null && $discounted_price < $option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Check for option-specific discount
            if (!empty($option['option_discount']) && !empty($option['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $option['option_discount'], $option['discount_type']);
                if ($discounted_price !== null && $discounted_price < $final_option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Generate field IDs
            $checkbox_id = $field_id . '_' . $option_key;
            
            echo '<div class="sh-product-extra-card" data-option-key="' . esc_attr($option_key) . '" data-field-id="' . esc_attr($field_id) . '">';
            
            // Checkbox
            echo '<div class="sh-product-checkbox">';
            echo '<input type="checkbox" class="sh-checkbox-input" name="' . esc_attr($field_name) . '[]" id="' . esc_attr($checkbox_id) . '" value="' . esc_attr($option_key) . '" data-option-cost="' . esc_attr($final_option_price) . '">';
            echo '<label for="' . esc_attr($checkbox_id) . '" class="sh-checkbox-label"></label>';
            echo '</div>';
            
            // Option Image (if available)
            if (!empty($option_image)) {
                echo '<div class="sh-product-image">';
                echo '<img src="' . esc_url($option_image) . '" alt="' . esc_attr($option_label) . '" loading="lazy">';
                echo '</div>';
            } else {
                // Placeholder for consistent layout
                echo '<div class="sh-product-image">';
                echo '<div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 12px;">No Image</div>';
                echo '</div>';
            }
            
            // Option Details
            echo '<div class="sh-product-details">';
            
            // Title and Price/Quantity Row
            echo '<div class="sh-product-info-row">';
            echo '<h4 class="sh-product-title">' . esc_html($option_label) . '</h4>';
            
            echo '<div class="sh-product-right-section">';
            // Price Section
            echo '<div class="sh-product-price">';
            if ($option_price > 0) {
                // Display pricing with promotional logic
                if ($final_option_price < $option_price) {
                    echo '<span class="sh-price-original">' . wc_price($option_price) . '</span>';
                    echo '<span class="sh-price-sale">' . wc_price($final_option_price) . '</span>';
                } else {
                    echo '<span class="sh-price-regular">' . wc_price($option_price) . '</span>';
                }
            } else {
                echo '<span class="sh-price-free">Free</span>';
            }
            echo '</div>';
            
            // Quantity controls for checkbox options
            echo '<div class="sh-product-quantity">';
            echo '<span class="sh-qty-label">Qty:</span>';
            echo '<div class="sh-quantity-controls">';
            echo '<button type="button" class="sh-qty-btn sh-qty-minus" data-option-key="' . esc_attr($option_key) . '">-</button>';
            echo '<input type="number" class="sh-qty-input" value="1" min="1" step="1" data-option-key="' . esc_attr($option_key) . '" />';
            echo '<button type="button" class="sh-qty-btn sh-qty-plus" data-option-key="' . esc_attr($option_key) . '">+</button>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>'; // .sh-product-right-section
            echo '</div>'; // .sh-product-info-row
            
            echo '</div>'; // .sh-product-details
            
            echo '</div>'; // .sh-product-extra-card
        }
    }

    /**
     * Render select field cards with consistent design
     * Handles both 'select' and 'select-box' field types
     */
    private function render_select_field_cards($item, $group_id, $item_id)
    {
        // Get select options
        if (empty($item['field_options'])) {
            return;
        }
        
        $field_id = $group_id . '_' . $item_id;
        $field_name = $field_id;
        $field_type = isset($item['field_type']) ? $item['field_type'] : 'select';
        
        // Check if this is a select-box with first field empty option
        $first_field_empty = !empty($item['first_field_empty']);
        $option_count = 0;
        
        foreach ($item['field_options'] as $option_key => $option) {
            // Handle different option structures from product-extra-for-woocommerce
            $option_label = '';
            $option_price = 0;
            $option_image = '';
            
            // Check for different option structures
            if (isset($option['value'])) {
                // Standard structure: option['value'], option['price'], option['image']
                $option_label = $option['value'];
                $option_price = isset($option['price']) ? floatval($option['price']) : 0;
                $option_image = isset($option['image']) ? $option['image'] : '';
            } elseif (isset($option['option_label'])) {
                // Alternative structure: option['option_label'], option['option_price'], option['option_image']
                $option_label = $option['option_label'];
                $option_price = isset($option['option_price']) ? floatval($option['option_price']) : 0;
                $option_image = isset($option['option_image']) ? $option['option_image'] : '';
            }
            
            // Skip empty options or first empty option for select-box
            if (empty($option_label) || ($first_field_empty && $option_count === 0)) {
                $option_count++;
                continue;
            }
            
            // Get proper option price using plugin function if available
            if (function_exists('pewc_get_option_price')) {
                global $product;
                $option_price = pewc_get_option_price($option, $item, $product);
            }
            
            // Calculate final price (with discounts if applicable)
            $final_option_price = $option_price;
            
            // Apply discount if available at field level
            if (!empty($item['child_discount']) && !empty($item['discount_type']) && function_exists('pewc_get_discounted_child_price')) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $item['child_discount'], $item['discount_type']);
                if ($discounted_price !== null && $discounted_price < $option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Check for option-specific discount
            if (!empty($option['option_discount']) && !empty($option['discount_type']) && function_exists('pewc_get_discounted_child_price')) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $option['option_discount'], $option['discount_type']);
                if ($discounted_price !== null && $discounted_price < $final_option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Handle image URL - check if it's an attachment ID
            if (!empty($option_image) && is_numeric($option_image)) {
                $image_url = wp_get_attachment_image_url($option_image, 'medium');
                if ($image_url) {
                    $option_image = $image_url;
                }
            }
            
            // Generate field IDs
            $radio_id = $field_id . '_' . $option_key;
            $option_value = ($first_field_empty && $option_count === 0) ? '' : $option_label;
            
            echo '<div class="sh-product-extra-card sh-select-option-card" data-option-key="' . esc_attr($option_key) . '" data-field-id="' . esc_attr($field_id) . '" data-field-type="' . esc_attr($field_type) . '">';
            
            // Radio button for select field (only one option can be selected)
            echo '<div class="sh-product-radio">';
            echo '<input type="radio" class="sh-radio-input" name="' . esc_attr($field_name) . '" id="' . esc_attr($radio_id) . '" value="' . esc_attr($option_value) . '" data-option-cost="' . esc_attr($final_option_price) . '" data-option-key="' . esc_attr($option_key) . '">';
            echo '<label for="' . esc_attr($radio_id) . '" class="sh-radio-label"></label>';
            echo '</div>';
            
            // Option Image (if available)
            if (!empty($option_image)) {
                echo '<div class="sh-product-image">';
                echo '<img src="' . esc_url($option_image) . '" alt="' . esc_attr($option_label) . '" loading="lazy">';
                echo '</div>';
            } else {
                // Placeholder for consistent layout
                echo '<div class="sh-product-image">';
                echo '<div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 12px;">No Image</div>';
                echo '</div>';
            }
            
            // Option Details
            echo '<div class="sh-product-details">';
            
            // Title and Price Row
            echo '<div class="sh-product-info-row">';
            echo '<h4 class="sh-product-title">' . esc_html($option_label) . '</h4>';
            
            echo '<div class="sh-product-right-section">';
            // Price Section
            echo '<div class="sh-product-price">';
            if ($final_option_price > 0) {
                // Display pricing with promotional logic
                if ($final_option_price < $option_price) {
                    echo '<span class="sh-price-original">' . wc_price($option_price) . '</span>';
                    echo '<span class="sh-price-sale">' . wc_price($final_option_price) . '</span>';
                } else {
                    echo '<span class="sh-price-regular">' . wc_price($final_option_price) . '</span>';
                }
            } elseif ($option_price > 0 && $final_option_price == 0) {
                // Free after discount
                echo '<span class="sh-price-original">' . wc_price($option_price) . '</span>';
                echo '<span class="sh-price-sale">Free</span>';
            } else {
                echo '<span class="sh-price-free">Free</span>';
            }
            echo '</div>';
            echo '</div>'; // .sh-product-right-section
            echo '</div>'; // .sh-product-info-row
            
            // Add option description if available
            if (!empty($option['description'])) {
                echo '<div class="sh-product-description">';
                echo '<p>' . esc_html($option['description']) . '</p>';
                echo '</div>';
            }
            
            echo '</div>'; // .sh-product-details
            
            echo '</div>'; // .sh-product-extra-card
            
            $option_count++;
        }
    }

    /**
     * Render radio field cards with consistent design
     */
    private function render_radio_field_cards($item, $group_id, $item_id)
    {
        // Get radio options
        if (empty($item['field_options'])) {
            return;
        }
        
        $field_id = $group_id . '_' . $item_id;
        $field_name = $field_id;
        
        foreach ($item['field_options'] as $option_key => $option) {
            $option_label = isset($option['option_label']) ? $option['option_label'] : '';
            $option_price = isset($option['option_price']) ? floatval($option['option_price']) : 0;
            $option_image = isset($option['option_image']) ? $option['option_image'] : '';
            
            if (empty($option_label)) {
                continue;
            }
            
            // Calculate final price (with discounts if applicable)
            $final_option_price = $option_price;
            
            // Apply discount if available at field level
            if (!empty($item['child_discount']) && !empty($item['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $item['child_discount'], $item['discount_type']);
                if ($discounted_price !== null && $discounted_price < $option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Check for option-specific discount
            if (!empty($option['option_discount']) && !empty($option['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $option['option_discount'], $option['discount_type']);
                if ($discounted_price !== null && $discounted_price < $final_option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Generate field IDs
            $radio_id = $field_id . '_' . $option_key;
            
            echo '<div class="sh-product-extra-card sh-radio-option-card" data-option-key="' . esc_attr($option_key) . '" data-field-id="' . esc_attr($field_id) . '">';
            
            // Radio button for radio field (only one option can be selected)
            echo '<div class="sh-product-radio">';
            echo '<input type="radio" class="sh-radio-input" name="' . esc_attr($field_name) . '" id="' . esc_attr($radio_id) . '" value="' . esc_attr($option_key) . '" data-option-cost="' . esc_attr($final_option_price) . '">';
            echo '<label for="' . esc_attr($radio_id) . '" class="sh-radio-label"></label>';
            echo '</div>';
            
            // Option Image (if available)
            if (!empty($option_image)) {
                echo '<div class="sh-product-image">';
                echo '<img src="' . esc_url($option_image) . '" alt="' . esc_attr($option_label) . '" loading="lazy">';
                echo '</div>';
            } else {
                // Placeholder for consistent layout
                echo '<div class="sh-product-image">';
                echo '<div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 12px;">No Image</div>';
                echo '</div>';
            }
            
            // Option Details
            echo '<div class="sh-product-details">';
            
            // Title and Price Row
            echo '<div class="sh-product-info-row">';
            echo '<h4 class="sh-product-title">' . esc_html($option_label) . '</h4>';
            
            echo '<div class="sh-product-right-section">';
            // Price Section
            echo '<div class="sh-product-price">';
            if ($option_price > 0) {
                // Display pricing with promotional logic
                if ($final_option_price < $option_price) {
                    echo '<span class="sh-price-original">' . wc_price($option_price) . '</span>';
                    echo '<span class="sh-price-sale">' . wc_price($final_option_price) . '</span>';
                } else {
                    echo '<span class="sh-price-regular">' . wc_price($option_price) . '</span>';
                }
            } else {
                echo '<span class="sh-price-free">Free</span>';
            }
            echo '</div>';
            echo '</div>'; // .sh-product-right-section
            echo '</div>'; // .sh-product-info-row
            
            echo '</div>'; // .sh-product-details
            
            echo '</div>'; // .sh-product-extra-card
        }
    }

    /**
     * Render image swatch field cards with consistent design
     */
    private function render_image_swatch_field_cards($item, $group_id, $item_id)
    {
        // Get image swatch options
        if (empty($item['field_options'])) {
            return;
        }
        
        $field_id = $group_id . '_' . $item_id;
        $field_name = $field_id;
        $allow_multiple = !empty($item['allow_multiple']);
        
        foreach ($item['field_options'] as $option_key => $option) {
            $option_label = isset($option['option_label']) ? $option['option_label'] : '';
            $option_price = isset($option['option_price']) ? floatval($option['option_price']) : 0;
            $option_image = isset($option['option_image']) ? $option['option_image'] : '';
            
            if (empty($option_label)) {
                continue;
            }
            
            // Calculate final price (with discounts if applicable)
            $final_option_price = $option_price;
            
            // Apply discount if available at field level
            if (!empty($item['child_discount']) && !empty($item['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $item['child_discount'], $item['discount_type']);
                if ($discounted_price !== null && $discounted_price < $option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Check for option-specific discount
            if (!empty($option['option_discount']) && !empty($option['discount_type'])) {
                $discounted_price = pewc_get_discounted_child_price($option_price, $option['option_discount'], $option['discount_type']);
                if ($discounted_price !== null && $discounted_price < $final_option_price) {
                    $final_option_price = $discounted_price;
                }
            }
            
            // Generate field IDs
            $input_id = $field_id . '_' . $option_key;
            $input_type = $allow_multiple ? 'checkbox' : 'radio';
            $input_name = $allow_multiple ? $field_name . '[]' : $field_name;
            
            echo '<div class="sh-product-extra-card sh-image-swatch-card" data-option-key="' . esc_attr($option_key) . '" data-field-id="' . esc_attr($field_id) . '">';
            
            // Input (radio or checkbox based on allow_multiple setting)
            echo '<div class="sh-product-' . $input_type . '">';
            echo '<input type="' . $input_type . '" class="sh-' . $input_type . '-input" name="' . esc_attr($input_name) . '" id="' . esc_attr($input_id) . '" value="' . esc_attr($option_key) . '" data-option-cost="' . esc_attr($final_option_price) . '">';
            echo '<label for="' . esc_attr($input_id) . '" class="sh-' . $input_type . '-label"></label>';
            echo '</div>';
            
            // Option Image (prioritized for image swatches)
            echo '<div class="sh-product-image sh-image-swatch-image">';
            if (!empty($option_image)) {
                echo '<img src="' . esc_url($option_image) . '" alt="' . esc_attr($option_label) . '" loading="lazy">';
            } else {
                // Fallback with option label
                echo '<div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-size: 14px; font-weight: 500;">' . esc_html($option_label) . '</div>';
            }
            echo '</div>';
            
            // Option Details
            echo '<div class="sh-product-details">';
            
            // Title and Price Row
            echo '<div class="sh-product-info-row">';
            echo '<h4 class="sh-product-title">' . esc_html($option_label) . '</h4>';
            
            echo '<div class="sh-product-right-section">';
            // Price Section
            echo '<div class="sh-product-price">';
            if ($option_price > 0) {
                // Display pricing with promotional logic
                if ($final_option_price < $option_price) {
                    echo '<span class="sh-price-original">' . wc_price($option_price) . '</span>';
                    echo '<span class="sh-price-sale">' . wc_price($final_option_price) . '</span>';
                } else {
                    echo '<span class="sh-price-regular">' . wc_price($option_price) . '</span>';
                }
            } else {
                echo '<span class="sh-price-free">Free</span>';
            }
            echo '</div>';
            echo '</div>'; // .sh-product-right-section
            echo '</div>'; // .sh-product-info-row
            
            echo '</div>'; // .sh-product-details
            
            echo '</div>'; // .sh-product-extra-card
        }
    }

    /**
     * Enqueue widget assets
     */
    private function enqueue_widget_assets($has_extra_fields = true)
    {
        static $assets_enqueued = false;
        
        if ($assets_enqueued) {
            return;
        }
        
        // Only enqueue if there are actual extra fields to display
        if (!$has_extra_fields) {
            return;
        }
        
        $assets_enqueued = true;
        
        // Get plugin URL and path for version optimization
        $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
        $plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));
        
        // Use file modification time for better cache busting
        $css_file = $plugin_path . 'assets/css/elementor-widget/frontend/product-extras-widget.css';
        $js_file = $plugin_path . 'assets/js/elementor-widget/frontend/product-extras-widget.js';
        $css_version = file_exists($css_file) ? filemtime($css_file) : '1.0.0';
        $js_version = file_exists($js_file) ? filemtime($js_file) : '1.0.0';
        
        // Enqueue CSS
        wp_enqueue_style(
            'sh-product-extras-widget',
            $plugin_url . 'assets/css/elementor-widget/frontend/product-extras-widget.css',
            [],
            $css_version
        );
        
        // Enqueue JavaScript
         wp_enqueue_script(
            'sh-product-extras-widget',
            $plugin_url . 'assets/js/elementor-widget/frontend/product-extras-widget.js',
            ['jquery'],
            $js_version,
            ['in_footer' => true, 'strategy' => 'defer']
        );
         
         // Localize script for AJAX - Use WooCommerce native AJAX for better performance
         // Prepare WooCommerce AJAX URL with proper fallback
         $wc_ajax_url = '';
         if (class_exists('WC_AJAX')) {
             $wc_ajax_url = \WC_AJAX::get_endpoint('%%endpoint%%');
         } elseif (function_exists('wc_get_ajax_url')) {
             $wc_ajax_url = wc_get_ajax_url('%%endpoint%%');
         } else {
             // Fallback to standard WooCommerce AJAX URL format
             $wc_ajax_url = home_url('/wc-ajax/%%endpoint%%/');
         }

         wp_localize_script(
             'sh-product-extras-widget',
             'sh_product_extras_ajax',
             [
                 'wc_ajax_url' => $wc_ajax_url, // WooCommerce native AJAX endpoint
                 'add_to_cart_url' => wc_get_cart_url(), // WooCommerce cart URL
                 'nonce' => wp_create_nonce('sh_product_extras_nonce'),
                 'wc_ajax_nonce' => wp_create_nonce('wc_add_to_cart_nonce')
             ]
         );
    }

    /**
     * Render interactive variation selectors with Woodmart swatch styling
     */
    private function render_variation_selectors($product, $variation_attributes, $item_id)
    {
        if ($product->is_type('variable')) {
            // For variable products, render all available variations as selectors
            $available_variations = $product->get_available_variations();
            $product_attributes = $product->get_attributes();
            
            foreach ($product_attributes as $attribute) {
                if (!$attribute->get_variation()) {
                    continue;
                }
                
                $attribute_name = $attribute->get_name();
                $attribute_label = wc_attribute_label($attribute_name);
                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                
                if (empty($terms)) {
                    continue;
                }
                
                echo '<div class="sh-variation-attribute" data-attribute="' . esc_attr($attribute_name) . '">';
                // echo '<label class="sh-variation-label">' . esc_html($attribute_label) . ':</label>';
                
                // Check if this is a color attribute
                $is_color_attribute = strpos(strtolower($attribute_name), 'color') !== false || 
                                    strpos(strtolower($attribute_name), 'colour') !== false ||
                                    strpos(strtolower($attribute_name), 'pa_color') !== false;
                
                // Also check if any terms have color/image meta data
                if (!$is_color_attribute) {
                    foreach ($terms as $term) {
                        $color_value = get_term_meta($term->term_id, 'color', true);
                        $image_value = get_term_meta($term->term_id, 'image', true);
                        if (!empty($color_value) || !empty($image_value)) {
                            $is_color_attribute = true;
                            break;
                        }
                    }
                }
                
                if ($is_color_attribute) {
                    echo '<!-- ProductExtrasWidget: Rendering color swatches for attribute: ' . esc_html($attribute_name) . ' -->';
                    echo '<div class="wd-swatches-product wd-swatches wd-bg-style-1 wd-size-m sh-color-swatches" data-product-id="' . esc_attr($product->get_id()) . '" data-item-id="' . esc_attr($item_id) . '" data-id="' . esc_attr($attribute_name) . '">';
                    
                    foreach ($terms as $term) {
                        // Use Woodmart's meta field names
                        $color_value = get_term_meta($term->term_id, 'color', true);
                        $image_value = get_term_meta($term->term_id, 'image', true);
                        
                        // Handle Woodmart's image meta format (array with url/id keys)
                        $image_url = '';
                        if (is_array($image_value)) {
                            if (!empty($image_value['url'])) {
                                $image_url = $image_value['url'];
                            } elseif (!empty($image_value['id'])) {
                                $image_url = wp_get_attachment_image_url($image_value['id'], 'thumbnail');
                            }
                        } elseif (!empty($image_value)) {
                            // Fallback for direct image ID
                            $image_url = wp_get_attachment_image_url($image_value, 'thumbnail');
                        }
                        

                        
                        echo '<!-- ProductExtrasWidget: Rendering swatch for term: ' . esc_html($term->name) . ' -->';
                        echo '<div class="wd-swatch-wrap sh-color-swatch-wrap" data-value="' . esc_attr($term->slug) . '">';
                        
                        if (!empty($color_value)) {
                            echo '<!-- Color swatch with value: ' . esc_html($color_value) . ' -->';
                            echo '<span class="wd-swatch wd-tooltip wd-swatch-color sh-color-swatch" style="background-color: ' . esc_attr($color_value) . ';" data-value="' . esc_attr($term->slug) . '" title="' . esc_attr($term->name) . '"></span>';
                        } elseif (!empty($image_url)) {
                            echo '<!-- Image swatch with URL: ' . esc_html($image_url) . ' -->';
                            echo '<span class="wd-swatch wd-tooltip wd-swatch-image sh-image-swatch" style="background-image: url(' . esc_url($image_url) . ');" data-value="' . esc_attr($term->slug) . '" title="' . esc_attr($term->name) . '"></span>';
                        } else {
                            echo '<!-- Text swatch (no color/image meta) -->';
                            echo '<span class="wd-swatch wd-tooltip wd-swatch-text sh-text-swatch" data-value="' . esc_attr($term->slug) . '" title="' . esc_attr($term->name) . '">' . esc_html($term->name) . '</span>';
                        }
                        
                        echo '</div>';
                    }
                    
                    echo '</div>';
                } else {
                    // For non-color attributes, use text swatches
                    echo '<div class="wd-swatches-product wd-swatches wd-text-style-1 wd-size-m sh-text-swatches" data-product-id="' . esc_attr($product->get_id()) . '" data-item-id="' . esc_attr($item_id) . '" data-id="' . esc_attr($attribute_name) . '">';
                    
                    foreach ($terms as $term) {
                        echo '<div class="wd-swatch wd-text wd-enabled sh-text-swatch" data-value="' . esc_attr($term->slug) . '" data-title="' . esc_attr($term->name) . '" role="radio" aria-checked="false" tabindex="0">';
                        echo '<span class="wd-swatch-text">' . esc_html($term->name) . '</span>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                
                echo '</div>';
            }
        } elseif ($product->is_type('variation')) {
            // For variation products, show the selected attributes
            foreach ($variation_attributes as $attribute_name => $attribute_value) {
                if (!empty($attribute_value)) {
                    $attribute_label = wc_attribute_label($attribute_name);
                    echo '<div class="sh-variation-attribute sh-variation-selected">';
                    echo '<span class="sh-variation-label">' . esc_html($attribute_label) . ':</span> ';
                    echo '<span class="sh-variation-value">' . esc_html($attribute_value) . '</span>';
                    echo '</div>';
                }
            }
        }
    }

    /**
     * Render information field cards with consistent design
     */
    private function render_information_field_cards($item, $group_id, $item_id, $field_label = '')
    {
        if (!isset($item['field_rows']) || empty($item['field_rows'])) {
            return;
        }

        foreach ($item['field_rows'] as $key => $row_value) {
            // Get image with proper fallback
            $image_size = apply_filters('pewc_filter_information_row_image_size', 'woocommerce_thumbnail', $row_value, $item);
            $image_url = '';
            
            if (!empty($row_value['image'])) {
                $image_id = $row_value['image'];
                // Use wp_get_attachment_image_url for better image handling
                $image_url = wp_get_attachment_image_url($image_id, $image_size);
                
                // Fallback to wp_get_attachment_image_src if wp_get_attachment_image_url fails
                if (empty($image_url)) {
                    $image_data = wp_get_attachment_image_src($image_id, $image_size);
                    if ($image_data) {
                        $image_url = $image_data[0];
                    }
                }
            }
            
            // Fallback to placeholder if no image
            if (empty($image_url)) {
                $image_url = wc_placeholder_img_src('woocommerce_thumbnail');
            }

            $label = esc_html($row_value['label']);
            $data = wp_kses_post($row_value['data']);

            // Generate unique ID for this information row
            $info_id = $group_id . '_' . $item_id . '_info_' . $key;

            // Extract price BEFORE generating HTML
            $original_price = null;
            
            // Try to extract price from various possible sources
            if (isset($row_value['price']) && !empty($row_value['price'])) {
                $original_price = floatval($row_value['price']);
                error_log('ProductExtrasWidget: Found price in row_value[price]: ' . $original_price);
            } elseif (isset($row_value['original_price']) && !empty($row_value['original_price'])) {
                $original_price = floatval($row_value['original_price']);
                error_log('ProductExtrasWidget: Found price in row_value[original_price]: ' . $original_price);
            } elseif (preg_match('/RM\s*(\d+(?:\.\d{2})?)/', $data, $matches)) {    
                // Try to extract price from the data content if it contains RM format
                $original_price = floatval($matches[1]);
                error_log('ProductExtrasWidget: Extracted price from data content: ' . $original_price);
            } else {
                error_log('ProductExtrasWidget: No price found in any source for ' . $info_id);
            }
            
            error_log('ProductExtrasWidget: Final original_price: ' . ($original_price ?: 0));

            echo '<div class="sh-product-extra-card sh-information-card" data-info-id="' . esc_attr($info_id) . '">';
            
            // Add checkbox for cart functionality
            echo '<div class="sh-product-checkbox">';
            echo '<input type="checkbox" class="sh-checkbox-input sh-information-checkbox-input" name="' . esc_attr($info_id) . '" id="' . esc_attr($info_id) . '_checkbox" value="' . esc_attr($info_id) . '" data-info-id="' . esc_attr($info_id) . '" data-info-label="' . esc_attr($label) . '" data-info-price="0" data-info-original-price="' . esc_attr($original_price ?: 0) . '" data-info-field-label="' . esc_attr($field_label) . '">';
            
            // Debug logging for the checkbox HTML
            error_log('ProductExtrasWidget: Checkbox HTML for ' . $info_id . ' - data-info-original-price="' . ($original_price ?: 0) . '"');
            echo '<label for="' . esc_attr($info_id) . '_checkbox" class="sh-checkbox-label"></label>';
            echo '</div>';
            
            // Information Image
            echo '<div class="sh-product-image">';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($label) . '" loading="lazy">';
            echo '</div>';
            
            // Information Details
            echo '<div class="sh-product-details">';
            
            // Information Title and Data
            echo '<div class="sh-product-main-row">';
            
            // Centered Title section
            echo '<div class="sh-product-left-section">';
            echo '<h4 class="sh-product-title">' . $label . '</h4>';
            echo '</div>';
            
            // Right section: Price display (showing original price and RM 0.00 sales price)
            echo '<div class="sh-product-right-section">';
            
            // Display price section if we have an original price
            if ($original_price && $original_price > 0) {
                echo '<div class="sh-product-price">';
                echo '<span class="sh-price-sale">' . wc_price(0) . '</span>'; // Sales price as RM 0.00
                echo '<span class="sh-price-original">' . wc_price($original_price) . '</span>'; // Original price crossed out
                echo '</div>';
            }
            
            echo '</div>'; // .sh-product-right-section
            
            echo '</div>'; // .sh-product-main-row
            
            echo '</div>'; // .sh-product-details
            echo '</div>'; // .sh-product-extra-card
        }
    }

    /**
     * Render default field using plugin's native rendering
     */
    private function render_default_field($item, $group_id, $item_id, $group_conditions = [], $condition_action = '', $condition_match = '')
    {
        // Use the plugin's native field rendering function
        if (function_exists('pewc_field')) {
            // Create a temporary group structure for the single field
            $temp_group = array(
                'group_id' => $group_id,
                'items' => array(
                    $item_id => $item
                )
            );

            // Buffer the plugin output so we can conditionally render only when non-empty
            ob_start();
            pewc_field($temp_group, $item_id, $item);
            $field_html = trim(ob_get_clean());

            // Only output wrapper when the field actually renders content
            if ($field_html !== '') {
                $condition_attrs = '';
                if (!empty($group_conditions) || !empty($condition_action) || !empty($condition_match)) {
                    if (!empty($group_conditions)) {
                        $condition_attrs .= ' data-conditions="' . esc_attr(json_encode($group_conditions)) . '"';
                    }
                    $condition_attrs .= ' data-group-id="' . esc_attr($group_id) . '"';
                    if (!empty($condition_action)) {
                        $condition_attrs .= ' data-condition-action="' . esc_attr($condition_action) . '"';
                    }
                    if (!empty($condition_match)) {
                        $condition_attrs .= ' data-condition-match="' . esc_attr($condition_match) . '"';
                    }
                    if (empty($condition_action) || $condition_action === 'show') {
                        $condition_attrs .= ' style="display:none"';
                    }
                }

                echo '<div class="sh-product-extras-default-field"' . $condition_attrs . '>' . $field_html . '</div>';
            }
        } else {
            // Fallback if the function doesn't exist
            echo '<p>Field rendering function not available.</p>';
        }
    }

    protected function content_template()
    {
        ?>
        <# if ( settings.show_title === 'yes' && settings.section_title ) { #>
            <h3 class="sh-product-extras-title">{{{ settings.section_title }}}</h3>
        <# } #>
        <div class="sh-product-extras-container">
            <div class="sh-product-extras-fields">
                <p>Product extras will be displayed here on the frontend.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Handle AJAX request to add extras to cart
     */
    public function handle_add_extras_to_cart()
    {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        
        if (!wp_verify_nonce($nonce, 'sh_product_extras_nonce')) {
            wp_die('Security check failed');
        }

        $selected_products = isset($_POST['selected_products']) ? $_POST['selected_products'] : [];
        $selected_information = isset($_POST['selected_information']) ? $_POST['selected_information'] : [];
        $main_product_id = isset($_POST['main_product_id']) ? intval($_POST['main_product_id']) : 0;

        $cart_items_added = [];
        $errors = [];

        // Add selected products to cart
        foreach ($selected_products as $product_data) {
            $product_id = intval($product_data['productId']);
            $quantity = intval($product_data['quantity']);
            $product_type = sanitize_text_field($product_data['productType']);
            $variation_data = isset($product_data['variationData']) ? $product_data['variationData'] : [];

            try {
                if ($product_type === 'variable' && !empty($variation_data)) {
                    // Handle variable products
                    // Use provided variation ID if available, otherwise try to find it
                    $variation_id = isset($product_data['variationId']) ? intval($product_data['variationId']) : null;
                    
                    if (!$variation_id) {
                        $variation_id = $this->find_matching_variation($product_id, $variation_data);
                    }
                    
                    if ($variation_id) {
                        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
                        if ($cart_item_key) {
                            $cart_items_added[] = [
                                'product_id' => $product_id,
                                'variation_id' => $variation_id,
                                'quantity' => $quantity,
                                'cart_item_key' => $cart_item_key
                            ];
                        } else {
                            $errors[] = "Failed to add product ID $product_id to cart";
                        }
                    } else {
                        $errors[] = "Could not find matching variation for product ID: $product_id";
                    }
                } else {
                    // Handle simple products
                    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
                    if ($cart_item_key) {
                        $cart_items_added[] = [
                            'product_id' => $product_id,
                            'quantity' => $quantity,
                            'cart_item_key' => $cart_item_key
                        ];
                    } else {
                        $errors[] = "Failed to add product ID $product_id to cart";
                    }
                }
            } catch (Exception $e) {
                $errors[] = "Error adding product ID $product_id: " . $e->getMessage();
            }
        }

        // Handle information items (store as cart meta or custom handling)
        foreach ($selected_information as $info_data) {
            $info_id = sanitize_text_field($info_data['infoId']);
            $info_label = sanitize_text_field($info_data['infoLabel']);
            $info_price = floatval($info_data['infoPrice']);

            // Store information items in session or as cart meta
            // This can be customized based on your requirements
            if (!isset(WC()->session)) {
                WC()->session = new WC_Session_Handler();
                WC()->session->init();
            }
            
            $session_info = WC()->session->get('sh_selected_information', []);
            $session_info[$info_id] = [
                'label' => $info_label,
                'price' => $info_price
            ];
            WC()->session->set('sh_selected_information', $session_info);
        }

        // Use WooCommerce's standard fragment system
        $fragments = [];
        $cart_hash = '';
        
        if (function_exists('wc_get_refreshed_fragments')) {
            // Get standard WooCommerce fragments - this includes all the proper selectors and content
            $fragment_data = wc_get_refreshed_fragments();
            $fragments = $fragment_data['fragments'];
            $cart_hash = $fragment_data['cart_hash'];
        } else {
            // Fallback for older WooCommerce versions
            $cart_hash = WC()->cart->get_cart_hash();
            $fragments['cart_hash'] = $cart_hash;
        }

        // Prepare response with fragments (matching WooCommerce AJAX format)
        $response = [
            'success' => true,
            'cart_items_added' => $cart_items_added,
            'information_items' => $selected_information,
            'errors' => $errors,
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_total(),
            'fragments' => $fragments,
            'cart_hash' => $cart_hash
        ];

        wp_send_json($response);
    }

    /**
     * Find matching variation for variable products
     */
    private function find_matching_variation($product_id, $variation_data)
    {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type('variable')) {
            return false;
        }

        $variations = $product->get_available_variations();
        
        foreach ($variations as $variation) {
            $variation_attributes = $variation['attributes'];
            $match = true;

            foreach ($variation_data as $attribute_name => $attribute_value) {
                $variation_attribute_key = 'attribute_' . $attribute_name;
                
                if (!isset($variation_attributes[$variation_attribute_key]) || 
                    $variation_attributes[$variation_attribute_key] !== $attribute_value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $variation['variation_id'];
            }
        }

        return false;
    }

    /**
     * Get group conditions from post meta
     * Based on Product Extras pewc_get_group_conditions function
     * 
     * @param int $group_id
     * @return array
     */
    private function get_group_conditions($group_id) {
        $conditions = get_post_meta($group_id, 'conditions', true);
        return $conditions;
    }

    private function get_group_condition_action($group_id) {
        $action = get_post_meta($group_id, 'condition_action', true);
        return $action;
    }

    private function get_group_condition_match($group_id) {
        $match = get_post_meta($group_id, 'condition_match', true);
        return $match;
    }

    /**
     * Evaluate group conditions based on current variation attributes
     * 
     * @param array $conditions
     * @param array $variation_attributes
     * @return bool
     */
    private function evaluate_group_conditions($conditions, $variation_attributes) {
        if (empty($conditions) || empty($variation_attributes)) {
            return true; // Show by default if no conditions or no variation selected
        }

        $match_all = true;
        $match_any = false;
        
        foreach ($conditions as $condition) {
            $field = $condition['field'];
            $rule = $condition['rule'];
            $value = $condition['value'];
            
            // Check if this is an attribute condition (pa_ prefix)
            if (strpos($field, 'pa_') === 0) {
                $attribute_name = str_replace('attribute_', '', $field);
                $current_value = isset($variation_attributes[$attribute_name]) ? $variation_attributes[$attribute_name] : '';
                
                $condition_met = false;
                
                switch ($rule) {
                    case 'is':
                        $condition_met = ($current_value === $value);
                        break;
                    case 'is-not':
                        $condition_met = ($current_value !== $value);
                        break;
                    case 'contains':
                        $condition_met = (strpos($current_value, $value) !== false);
                        break;
                    case 'does-not-contain':
                        $condition_met = (strpos($current_value, $value) === false);
                        break;
                }
                
                if ($condition_met) {
                    $match_any = true;
                } else {
                    $match_all = false;
                }
            }
        }
        
        // Default to showing the group if no attribute conditions are found
        return $match_all;
    }


}