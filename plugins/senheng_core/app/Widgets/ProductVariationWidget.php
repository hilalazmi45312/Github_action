<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ProductVariationWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'sh_product_variation';
    }

    public function get_title()
    {
        return esc_html__('Product Variation', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-product-variations';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['variation', 'product', 'woocommerce', 'attributes'];
    }

    public function get_script_depends() {
        $scripts = ['wc-add-to-cart-variation', 'woocommerce', 'wc-single-product'];
        
        // Add the widget's own JavaScript file
        $script_path = plugin_dir_path(__FILE__) . '../../assets/js/elementor-widget/frontend/product-variation-widget.js';
        $script_version = file_exists($script_path) ? filemtime($script_path) : '1.0.1';
        wp_enqueue_script(
            'product-variation-widget',
            plugin_dir_url(__FILE__) . '../../assets/js/elementor-widget/frontend/product-variation-widget.js',
            ['jquery', 'wc-add-to-cart-variation'],
            $script_version,
            ['in_footer' => true, 'strategy' => 'defer']
        );
        $scripts[] = 'product-variation-widget';
        
        // Add editor JavaScript file for Elementor editor
        wp_enqueue_script(
            'product-variation-widget-editor',
            plugin_dir_url(__FILE__) . '../../assets/js/elementor-widget/editor/product-variation-widget-editor.js',
            ['jquery', 'elementor-frontend'],
            '1.0.0',
            true
        );
        $scripts[] = 'product-variation-widget-editor';
        
        // Add WoodMart swatches scripts if theme is active and swatches are enabled
        if (function_exists('woodmart_get_opt') && woodmart_get_opt('swatches')) {
            // Enqueue WoodMart scripts using their system
            if (function_exists('woodmart_enqueue_js_script')) {
                woodmart_enqueue_js_script('swatches-variations');
                woodmart_enqueue_js_script('swatches-limit');
            }
            
            // Ensure the main WoodMart theme script is loaded
            if (function_exists('woodmart_enqueue_js_library')) {
                woodmart_enqueue_js_library('woodmart-theme');
            }
            $scripts[] = 'woodmart-theme'; // Main theme script that defines woodmartThemeModule
        }
        
        return $scripts;
    }

    public function get_style_depends() {
        $styles = [];
        
        // Add the widget's own CSS file
        wp_enqueue_style(
            'product-variation-widget',
            plugin_dir_url(__FILE__) . '../../assets/css/elementor-widget/frontend/product-variation-widget.css',
            [],
            '1.0.0'
        );
        $styles[] = 'product-variation-widget';
        
        // Add WoodMart swatches styles if theme is active and swatches are enabled
        if (function_exists('woodmart_get_opt') && woodmart_get_opt('swatches')) {
            // Enqueue WoodMart styles using their system
            if (function_exists('woodmart_enqueue_inline_style')) {
                woodmart_enqueue_inline_style('woo-mod-swatches-base');
                woodmart_enqueue_inline_style('woo-mod-variation-form');
                woodmart_enqueue_inline_style('woo-mod-variation-form-single');
            }
        }
        
        return $styles;
    }

    protected function register_controls()
    {
        // Content Section - General Settings
        $this->start_controls_section(
            'general_settings',
            [
                'label' => esc_html__('General Settings', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // $this->add_control(
        //     'show_variation_description',
        //     [
        //         'label' => esc_html__('Show Variation Description', 'senheng_core'),
        //         'type' => \Elementor\Controls_Manager::SWITCHER,
        //         'label_on' => esc_html__('Yes', 'senheng_core'),
        //         'label_off' => esc_html__('No', 'senheng_core'),
        //         'return_value' => 'yes',
        //         'default' => 'no',
        //         'description' => esc_html__('Show variation description when a variation is selected.', 'senheng_core'),
        //     ]
        // );

        $this->add_control(
            'woodmart_swatches_info',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('WoodMart swatches will be automatically detected and used based on your WooCommerce attribute settings configured in the WoodMart theme.', 'senheng_core'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->end_controls_section();

        // Style Section - Form Container
        $this->start_controls_section(
            'form_style',
            [
                'label' => esc_html__('Form Container', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'form_background',
                'label' => esc_html__('Background', 'senheng_core'),
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .sh-variation-form',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'form_border',
                'label' => esc_html__('Border', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-variation-form',
            ]
        );

        $this->add_responsive_control(
            'form_padding',
            [
                'label' => esc_html__('Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-variation-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '20',
                    'right' => '20',
                    'bottom' => '20',
                    'left' => '20',
                    'unit' => 'px',
                    'isLinked' => true,
                ],
            ]
        );

        $this->add_responsive_control(
            'form_margin',
            [
                'label' => esc_html__('Margin', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-variation-form' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Color & Image Swatch Style Section
        $this->start_controls_section(
            'color_image_swatch_style',
            [
                'label' => esc_html__('Color & Image Swatches', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'color_image_swatch_size',
            [
                'label' => esc_html__('Swatch Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 20,
                        'max' => 80,
                        'step' => 2,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 40,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-bg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'color_image_swatch_spacing',
            [
                'label' => esc_html__('Spacing Between Swatches', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-bg' => 'margin-right: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'color_image_swatch_border_radius',
            [
                'label' => esc_html__('Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-bg' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Text Swatch Style Section
        $this->start_controls_section(
            'text_swatch_style',
            [
                'label' => esc_html__('Text Swatches', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_swatch_typography',
                'label' => esc_html__('Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .wd-swatch.wd-text',
            ]
        );

        $this->add_responsive_control(
            'text_swatch_padding',
            [
                'label' => esc_html__('Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-text' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'default' => [
                    'top' => '8',
                    'right' => '12',
                    'bottom' => '8',
                    'left' => '12',
                    'unit' => 'px',
                ],
            ]
        );

        $this->add_responsive_control(
            'text_swatch_spacing',
            [
                'label' => esc_html__('Spacing Between Swatches', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 20,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-text' => 'margin-right: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'text_swatch_border_radius',
            [
                'label' => esc_html__('Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 4,
                ],
                'selectors' => [
                    '{{WRAPPER}} .wd-swatch.wd-text' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Selected Variation Text Style section removed (selected-value UI no longer used)

        // Variation Name/Label Style Section
        $this->start_controls_section(
            'variation_name_style',
            [
                'label' => esc_html__('Variation Name/Label', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'variation_name_typography',
                'label' => esc_html__('Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .variation-label, {{WRAPPER}} .variation-name-cell .variation-label',
            ]
        );

        $this->add_control(
            'variation_name_color',
            [
                'label' => esc_html__('Text Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .variation-label' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .variation-name-cell .variation-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'variation_name_margin',
            [
                'label' => esc_html__('Margin', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .variation-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .variation-name-cell .variation-label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'variation_name_padding',
            [
                'label' => esc_html__('Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .variation-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .variation-name-cell .variation-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();

        // Check if we're in Elementor editor mode
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!is_product() && !$is_editor) {
            echo '<p>' . esc_html__('This widget only works on product pages.', 'senheng_core') . '</p>';
            return;
        }

        global $product;
        
        // If no product found and we're in editor, get product from Woodmart preview
        if (!$product && $is_editor) {
            // Try to get product ID from Woodmart preview
            $preview_product_id = null;
            
            // Check for Woodmart preview product ID in various ways
            if (isset($_GET['preview_product_id'])) {
                $preview_product_id = intval($_GET['preview_product_id']);
            } elseif (isset($_GET['product_id'])) {
                $preview_product_id = intval($_GET['product_id']);
            } elseif (isset($_GET['preview_id'])) {
                $preview_product_id = intval($_GET['preview_id']);
            } elseif (isset($_GET['p'])) {
                $preview_product_id = intval($_GET['p']);
            }
            
            // If we found a preview product ID, get that product
            if ($preview_product_id && $preview_product_id > 0) {
                $product = wc_get_product($preview_product_id);
            }
            
            // Fallback to any published variable product if no preview product found
            if (!$product) {
                $products = wc_get_products([
                    'limit' => 1,
                    'status' => 'publish',
                    'type' => 'variable'
                ]);
                if (!empty($products)) {
                    $product = $products[0];
                }
            }
        }

        if (!$product) {
            echo '<p>' . esc_html__('No product found. Please set a product preview in Woodmart settings.', 'senheng_core') . '</p>';
            return;
        }

        if (!$product->is_type('variable')) {
            return;
        }

        $this->add_render_attribute('variation_form', [
            'class' => 'sh-variation-form',
        ]);
        ?>

        <div <?php echo $this->get_render_attribute_string('variation_form'); ?>>

            <div class="sh-variation-content">
                <?php
                // Get the variation form and augment with stock quantity info
                $variation_form = $product->get_available_variations();
                if (!empty($variation_form) && is_array($variation_form)) {
                    foreach ($variation_form as $idx => $var) {
                        $stock_qty = null;
                        $managing_stock = false;
                        if (!empty($var['variation_id'])) {
                            $var_product = wc_get_product($var['variation_id']);
                            if ($var_product && method_exists($var_product, 'managing_stock') && $var_product->managing_stock()) {
                                $managing_stock = true;
                                $stock_qty = (int) $var_product->get_stock_quantity();
                            }
                        }
                        $variation_form[$idx]['stock_quantity'] = $stock_qty; // null if not managed
                        $variation_form[$idx]['managing_stock'] = $managing_stock;
                    }
                }
                $variations_json = wp_json_encode($variation_form);
                $variations_attr = function_exists('wc_esc_json') ? wc_esc_json($variations_json) : _wp_specialchars($variations_json, ENT_QUOTES, 'UTF-8', true);
                
                // WoodMart form classes
                $form_classes = ' wd-reset-side-lg wd-reset-bottom-md wd-label-top-md';
                if (function_exists('woodmart_get_opt') && woodmart_get_opt('swatches_labels_name')) {
                    $form_classes .= ' wd-swatches-name wd-label-top-lg';
                }
                
                if (!empty($variation_form)) {
                    // Enqueue WoodMart styles
                    if (function_exists('woodmart_enqueue_inline_style')) {
                        woodmart_enqueue_inline_style('woo-mod-variation-form');
                        woodmart_enqueue_inline_style('woo-mod-swatches-base');
                        woodmart_enqueue_inline_style('woo-mod-variation-form-single');
                    }
                    
                    // Start the variation form with WoodMart classes
                    echo '<form class="variations_form cart' . esc_attr($form_classes) . '" action="' . esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())) . '" method="post" enctype="multipart/form-data" data-product_id="' . esc_attr($product->get_id()) . '" data-product_variations="' . $variations_attr . '">';
                    
                    echo '<table class="variations" role="presentation">';
                    echo '<tbody>';
                    
                    $attributes = $product->get_variation_attributes();
                    $loop = 0;
                    foreach ($attributes as $attribute_name => $options) {
                        $loop++;
                        
                        // Show all variations - removed color-only restriction
                        // if (strpos(strtolower($attribute_name), 'color') === false && 
                        //     strpos(strtolower($attribute_name), 'colour') === false) {
                        //     continue;
                        // }
                        
                        // Get WoodMart swatch data
                        $swatches = array();
                        $wrapper_class = '';
                        // Automatically detect WoodMart swatches based on WooCommerce attribute settings
                        $use_swatches = false;
                        if (function_exists('woodmart_has_swatches') && woodmart_has_swatches($product->get_id(), $attribute_name, $options, $product->get_available_variations())) {
                            $use_swatches = true;
                        } else {
                            // Fallback: Check if any attribute terms have swatch configuration
                            if (taxonomy_exists($attribute_name)) {
                                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                                foreach ($terms as $term) {
                                    $swatch_enabled = get_term_meta($term->term_id, 'not_dropdown', true);
                                    if ($swatch_enabled === 'on') {
                                        $use_swatches = true;
                                        break;
                                    }
                                }
                            }
                        }
                        
                        if ($use_swatches && function_exists('woodmart_has_swatches')) {
                            $swatches = woodmart_has_swatches($product->get_id(), $attribute_name, $options, $product->get_available_variations());
                            
                            // If no swatches from woodmart_has_swatches, check individual term configurations
                            if (empty($swatches) && taxonomy_exists($attribute_name)) {
                                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                                $has_term_swatches = false;
                                
                                foreach ($terms as $term) {
                                            if (!in_array($term->slug, $options)) continue;
                                            
                                            // Check if term has swatch enabled (WoodMart uses 'not_dropdown' meta key)
                                            $swatch_enabled = get_term_meta($term->term_id, 'not_dropdown', true);
                                            
                                            if ($swatch_enabled === 'on') {
                                                // Check if term has any swatch configuration using WoodMart meta keys
                                                $color = get_term_meta($term->term_id, 'color', true);
                                                $image = get_term_meta($term->term_id, 'image', true);
                                                
                                                $has_term_swatches = true;
                                                $swatches[array_search($term->slug, $options)] = array(
                                                    'color' => $color,
                                                    'image' => $image,
                                                    'text_swatch' => '', // WoodMart handles text swatches automatically when no color/image
                                                    'is_in_stock' => true
                                                );
                                            }
                                        }
                                
                                if (!$has_term_swatches) {
                                    $use_swatches = false;
                                }
                            }
                            
                            if (taxonomy_exists($attribute_name)) {
                                $swatch_style = function_exists('woodmart_wc_get_attribute_term') ? woodmart_wc_get_attribute_term($attribute_name, 'swatch_style') : '1';
                                $swatch_dis_style = function_exists('woodmart_wc_get_attribute_term') ? woodmart_wc_get_attribute_term($attribute_name, 'swatch_dis_style') : '1';
                                $swatch_size = function_exists('woodmart_wc_get_attribute_term') ? woodmart_wc_get_attribute_term($attribute_name, 'swatch_size') : 'default';
                                $swatch_shape = function_exists('woodmart_wc_get_attribute_term') ? woodmart_wc_get_attribute_term($attribute_name, 'swatch_shape') : 'round';
                                
                                if (!$swatch_style) $swatch_style = '1';
                                if (!$swatch_dis_style) $swatch_dis_style = '1';
                                if (!$swatch_size) $swatch_size = 'default';
                                if (!$swatch_shape) $swatch_shape = 'round';
                                
                                // Enqueue WoodMart swatch styles
                                if (function_exists('woodmart_enqueue_inline_style')) {
                                    woodmart_enqueue_inline_style('woo-mod-swatches-style-' . $swatch_style);
                                    woodmart_enqueue_inline_style('woo-mod-swatches-dis-' . $swatch_dis_style);
                                }
                                
                                $wrapper_class = ' wd-swatches-single';
                                $wrapper_class .= ' wd-bg-style-' . $swatch_style;
                                $wrapper_class .= ' wd-text-style-' . $swatch_style;
                                $wrapper_class .= ' wd-dis-style-' . $swatch_dis_style;
                                $wrapper_class .= ' wd-size-' . $swatch_size;
                                $wrapper_class .= ' wd-shape-' . $swatch_shape;
                            }
                        }
                        
                        // Display variation name in its own row
                        $unique_id = sanitize_title($attribute_name) . '-' . $this->get_id();
                        echo '<tr class="variation-name-row">';
                        echo '<td colspan="2" class="variation-name cell">';
                        echo '<label for="' . esc_attr($unique_id) . '" class="variation-label">' . rtrim(wc_attribute_label($attribute_name)) . '</label>';
                        echo '</td>';
                        echo '</tr>';
                        
                        // Display variation values in the next row
                        echo '<tr class="variation-value-row">';
                        echo '<td colspan="2" class="variation-value cell' . ($use_swatches ? ' with-swatches' : '') . '">';
                        

                        
                        if ($use_swatches && !empty($swatches)) {
                            echo '<div class="wd-swatches-product' . esc_attr($wrapper_class) . '" data-id="' . esc_attr(sanitize_title($attribute_name)) . '" role="radiogroup" aria-labelledby="' . esc_attr($unique_id) . '">';
                            
                        // Do not preselect defaults or request attributes; start with no selection
                        $selected_value = '';

                            $attr_key = 'attribute_' . sanitize_title($attribute_name);
                            
                            // Get terms if this is a taxonomy
                            if (taxonomy_exists($attribute_name)) {
                                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                                $options_fliped = array_flip($options);
                                
                                foreach ($terms as $term) {
                                    if (!in_array($term->slug, $options)) {
                                        continue;
                                    }
                                    
                                    $key = $options_fliped[$term->slug];
                                    $class = 'wd-swatch';
                                    $style = '';
                                    $image = '';
                                    
                                    if (!empty($swatches[$key]['color'])) {
                                        $class .= ' wd-bg wd-tooltip';
                                        $style = 'background-color:' . $swatches[$key]['color'];
                                    } elseif (!empty($swatches[$key]['image']) && (!is_array($swatches[$key]['image']) || (is_array($swatches[$key]['image']) && $swatches[$key]['image']['id']))) {
                                        $class .= ' wd-bg wd-tooltip';
                                        if (is_array($swatches[$key]['image'])) {
                                            $image = wp_get_attachment_image($swatches[$key]['image']['id'], 'woocommerce_thumbnail');
                                        } elseif ($swatches[$key]['image']) {
                                            $image = '<img src="' . $swatches[$key]['image'] . '" alt="Swatch image">';
                                        }
                                    } else {
                                        $class .= ' wd-text';
                                    }
                                    
                                    // Add active/selected class
                                    if ($selected_value === $term->slug) {
                                        $class .= ' wd-active';
                                    }
                                    
                                    $class .= ' wd-enabled';
                                    echo '<div class="' . esc_attr($class) . '" data-value="' . esc_attr($term->slug) . '" data-title="' . esc_attr($term->name) . '" role="radio" aria-checked="false" tabindex="0">';
                                    
                                    if (!empty($swatches[$key]['color'])) {
                                        echo '<span class="wd-swatch-bg" style="' . esc_attr($style) . '"></span>';
                                    } elseif ($image) {
                                        echo '<span class="wd-swatch-bg" style="' . esc_attr($style) . '">' . $image . '</span>';
                                    }
                                    
                                    echo '<span class="wd-swatch-text">' . esc_html($term->name) . '</span>';
                                    echo '</div>';
                                }
                            } else {
                                // Handle non-taxonomy attributes
                                foreach ($options as $key => $option) {
                                    $class = 'wd-swatch wd-text';
                                    
                                    // Add active/selected class
                                    if ($selected_value === $option) {
                                        $class .= ' wd-active';
                                    }
                                    
                                    $class .= ' wd-enabled';
                                    
                                    echo '<div class="' . esc_attr($class) . '" data-value="' . esc_attr($option) . '" data-title="' . esc_attr($option) . '" role="radio" aria-checked="false" tabindex="0">';
                                    echo '<span class="wd-swatch-text">' . esc_html($option) . '</span>';
                                    echo '</div>';
                                }
                            }
                            
                            echo '</div>';
                            
                            echo '<select id="' . esc_attr($unique_id) . '" class="" name="attribute_' . esc_attr(sanitize_title($attribute_name)) . '" data-attribute_name="attribute_' . esc_attr(sanitize_title($attribute_name)) . '" data-show_option_none="yes">';
                            echo '<option value="">' . esc_html__('Choose an option', 'woocommerce') . '</option>';
                            if (taxonomy_exists($attribute_name)) {
                                $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                                foreach ($terms as $term) {
                                    if (!in_array($term->slug, $options)) {
                                        continue;
                                    }
                                    echo '<option value="' . esc_attr($term->slug) . '" class="attached enabled"' . selected($selected_value, $term->slug, false) . '>' . esc_html($term->name) . '</option>';
                                }
                            } else {
                                foreach ($options as $option) {
                                    echo '<option value="' . esc_attr($option) . '" class="attached enabled"' . selected($selected_value, $option, false) . '>' . esc_html(apply_filters('woocommerce_variation_option_name', $option, null, $attribute_name, $product)) . '</option>';
                                }
                            }
                            echo '</select>';
                        } else {
                            // Fallback to standard WooCommerce dropdown
                            wc_dropdown_variation_attribute_options([
                                'options'   => $options,
                                'attribute' => $attribute_name,
                                'product'   => $product,
                                'selected'  => '',
                                'id'        => $unique_id,
                            ]);
                        }
                        
                        echo '</td>';
                        echo '</tr>';
                        
                        // Removed selected value display row (no longer used)
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    
                    // Variation description template (hidden, will be shown by JS)
                    if (isset($settings['show_variation_description']) && $settings['show_variation_description'] === 'yes') {
                        echo '<div class="woocommerce-variation-description"></div>';
                    }
                    
                    // Add to cart button - hide in Elementor editor
                    if (!$is_editor) {
                        echo '<div class="single_variation_wrap">';
                        echo '<div class="woocommerce-variation-add-to-cart variations_button">';
                        echo '<button type="submit" class="single_add_to_cart_button button alt">' . esc_html($product->single_add_to_cart_text()) . '</button>';
                        echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product->get_id()) . '" />';
                        echo '<input type="hidden" name="product_id" value="' . esc_attr($product->get_id()) . '" />';
                        echo '<input type="hidden" name="variation_id" class="variation_id" value="0" />';
                        echo '</div>';
                        echo '</div>';
                    }
                    
                    echo '</form>';
                    
                    // The JavaScript initialization is now handled by the external JS file
                    // /assets/js/elementor-widget/frontend/product-variation-widget.js
                } else {
                    echo '<p>' . esc_html__('No variations available for this product.', 'senheng_core') . '</p>';
                }
                ?>
            </div>
        </div>

        <?php
    }

    protected function content_template()
    {
        ?>
        <div class="sh-variation-form">
            <div class="sh-variation-content">
                <?php
                // Get current product for preview
                global $product;
                
                // Try to get product from various sources for editor preview
                if (!$product || !is_object($product)) {
                    // Check for preview product ID in URL parameters
                    $preview_product_id = 0;
                    if (isset($_GET['product_id'])) {
                        $preview_product_id = intval($_GET['product_id']);
                    } elseif (isset($_GET['preview_id'])) {
                        $preview_product_id = intval($_GET['preview_id']);
                    } elseif (isset($_GET['p'])) {
                        $preview_product_id = intval($_GET['p']);
                    }
                    
                    // If we found a preview product ID, get that product
                    if ($preview_product_id && $preview_product_id > 0) {
                        $product = wc_get_product($preview_product_id);
                    }
                    
                    // Fallback to any published variable product if no preview product found
                    if (!$product) {
                        $products = wc_get_products([
                            'limit' => 1,
                            'status' => 'publish',
                            'type' => 'variable'
                        ]);
                        if (!empty($products)) {
                            $product = $products[0];
                        }
                    }
                }
                
                if ($product && $product->is_type('variable')) {
                    // Get real variation attributes
                    $attributes = $product->get_variation_attributes();
                    
                    if (!empty($attributes)) {
                        echo '<form class="variations_form cart" method="post" enctype="multipart/form-data">';
                        echo '<table class="variations" cellspacing="0">';
                        echo '<tbody>';
                        
                        foreach ($attributes as $attribute_name => $options) {
                            // Display variation name in its own row
                            echo '<tr class="variation-name-row">';
                            echo '<td colspan="2" class="variation-name-cell">';
                            echo '<label for="' . esc_attr(sanitize_title($attribute_name)) . '" class="variation-label">' . rtrim(wc_attribute_label($attribute_name)) . '</label>';
                            echo '</td>';
                            echo '</tr>';
                            
                            // Display variation values in the next row
                            echo '<tr class="variation-value-row">';
                            echo '<td colspan="2" class="variation-value-cell">';
                            
                            // Automatically detect WoodMart swatches based on WooCommerce attribute settings
                            $use_swatches = false;
                            if (function_exists('woodmart_has_swatches') && woodmart_has_swatches($product->get_id(), $attribute_name, $options, $product->get_available_variations())) {
                                $use_swatches = true;
                            } else {
                                // Fallback: Check if any attribute terms have swatch configuration
                                if (taxonomy_exists($attribute_name)) {
                                    $terms = wc_get_product_terms($product->get_id(), $attribute_name, array('fields' => 'all'));
                                    foreach ($terms as $term) {
                                        $swatch_enabled = get_term_meta($term->term_id, 'not_dropdown', true);
                                        if ($swatch_enabled === 'on') {
                                            $use_swatches = true;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            if ($use_swatches && function_exists('woodmart_variation_attribute_options')) {
                                // Use WoodMart swatches
                                woodmart_variation_attribute_options([
                                    'options'   => $options,
                                    'attribute' => $attribute_name,
                                    'product'   => $product,
                                    'class'     => 'wd-swatches-product wd-swatches-single wd-shape-round',
                                    'selected'  => '',
                                ]);
                            } else {
                                // Use standard dropdown
                                wc_dropdown_variation_attribute_options([
                                    'options'   => $options,
                                    'attribute' => $attribute_name,
                                    'product'   => $product,
                                    'selected'  => '',
                                ]);
                            }
                            
                            echo '</td>';
                            echo '</tr>';
                            
                            // Removed selected value display row in editor preview (no longer used)
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        
                        // Add to cart button is hidden in editor preview
                        // echo '<div class="single_variation_wrap">';
                        // echo '<div class="woocommerce-variation-add-to-cart variations_button">';
                        // echo '<button type="submit" class="single_add_to_cart_button button alt" disabled>' . esc_html($product->single_add_to_cart_text()) . '</button>';
                        // echo '<input type="hidden" name="add-to-cart" value="' . esc_attr($product->get_id()) . '" />';
                        // echo '<input type="hidden" name="product_id" value="' . esc_attr($product->get_id()) . '" />';
                        // echo '<input type="hidden" name="variation_id" class="variation_id" value="0" />';
                        // echo '</div>';
                        // echo '</div>';
                        echo '</form>';
                    } else {
                        echo '<p>No variations available for this product.</p>';
                    }
                } else {
                    echo '<p>Please select a variable product for preview.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
}
