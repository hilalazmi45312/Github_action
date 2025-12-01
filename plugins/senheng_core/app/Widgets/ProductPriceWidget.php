<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ProductPriceWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'sh_product_price';
    }

    public function get_title()
    {
        return esc_html__('Senheng Product Price', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-price-table';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['price', 'product', 'woocommerce', 'sale'];
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

        $this->add_control(
            'show_save_amount',
            [
                'label' => esc_html__('Show Save Amount', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Sales Price Section
        $this->start_controls_section(
            'section_sales_price_style',
            [
                'label' => esc_html__('Sales Price', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'label' => esc_html__('Price Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-price-current, {{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount',
            ]
        );

        $this->add_control(
            'price_font_weight',
            [
                'label' => esc_html__('Price Font Weight', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'bold',
                'options' => [
                    'normal' => esc_html__('Normal', 'senheng_core'),
                    'bold' => esc_html__('Bold', 'senheng_core'),
                    '100' => esc_html__('100', 'senheng_core'),
                    '200' => esc_html__('200', 'senheng_core'),
                    '300' => esc_html__('300', 'senheng_core'),
                    '400' => esc_html__('400', 'senheng_core'),
                    '500' => esc_html__('500', 'senheng_core'),
                    '600' => esc_html__('600', 'senheng_core'),
                    '700' => esc_html__('700', 'senheng_core'),
                    '800' => esc_html__('800', 'senheng_core'),
                    '900' => esc_html__('900', 'senheng_core'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'font-weight: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'font-weight: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount bdi' => 'font-weight: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'price_font_size',
            [
                'label' => esc_html__('Price Font Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem', '%'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 300,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 24,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount bdi' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'price_font_family',
            [
                'label' => esc_html__('Price Font Family', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::FONT,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'font-family: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'font-family: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount bdi' => 'font-family: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => esc_html__('Price Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount bdi' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'price_background_color',
            [
                'label' => esc_html__('Price Background Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'price_padding',
            [
                'label' => esc_html__('Price Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-current .woocommerce-Price-amount.amount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Regular Price Section
        $this->start_controls_section(
            'section_regular_price_style',
            [
                'label' => esc_html__('Regular Price', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'regular_price_typography',
                'label' => esc_html__('Regular Price Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-price-regular, {{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount',
            ]
        );

        $this->add_control(
            'regular_price_font_weight',
            [
                'label' => esc_html__('Regular Price Font Weight', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'normal',
                'options' => [
                    'normal' => esc_html__('Normal', 'senheng_core'),
                    'bold' => esc_html__('Bold', 'senheng_core'),
                    '100' => esc_html__('100', 'senheng_core'),
                    '200' => esc_html__('200', 'senheng_core'),
                    '300' => esc_html__('300', 'senheng_core'),
                    '400' => esc_html__('400', 'senheng_core'),
                    '500' => esc_html__('500', 'senheng_core'),
                    '600' => esc_html__('600', 'senheng_core'),
                    '700' => esc_html__('700', 'senheng_core'),
                    '800' => esc_html__('800', 'senheng_core'),
                    '900' => esc_html__('900', 'senheng_core'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'font-weight: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'font-weight: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount bdi' => 'font-weight: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'regular_price_font_size',
            [
                'label' => esc_html__('Regular Price Font Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem', '%'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 300,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount bdi' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'regular_price_font_family',
            [
                'label' => esc_html__('Regular Price Font Family', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::FONT,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'font-family: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'font-family: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount bdi' => 'font-family: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'regular_price_color',
            [
                'label' => esc_html__('Regular Price Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount bdi' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'regular_price_background_color',
            [
                'label' => esc_html__('Regular Price Background Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'regular_price_padding',
            [
                'label' => esc_html__('Regular Price Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-regular' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-regular .woocommerce-Price-amount.amount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Save Amount Section
        $this->start_controls_section(
            'section_save_amount_style',
            [
                'label' => esc_html__('Save Amount', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'save_amount_typography',
                'label' => esc_html__('Save Amount Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-price-save, {{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount',
            ]
        );

        $this->add_control(
            'save_amount_font_weight',
            [
                'label' => esc_html__('Save Amount Font Weight', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'bold',
                'options' => [
                    'normal' => esc_html__('Normal', 'senheng_core'),
                    'bold' => esc_html__('Bold', 'senheng_core'),
                    '100' => esc_html__('100', 'senheng_core'),
                    '200' => esc_html__('200', 'senheng_core'),
                    '300' => esc_html__('300', 'senheng_core'),
                    '400' => esc_html__('400', 'senheng_core'),
                    '500' => esc_html__('500', 'senheng_core'),
                    '600' => esc_html__('600', 'senheng_core'),
                    '700' => esc_html__('700', 'senheng_core'),
                    '800' => esc_html__('800', 'senheng_core'),
                    '900' => esc_html__('900', 'senheng_core'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'font-weight: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'font-weight: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount bdi' => 'font-weight: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'save_amount_font_size',
            [
                'label' => esc_html__('Save Amount Font Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem', '%'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 100,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    'rem' => [
                        'min' => 0.5,
                        'max' => 10,
                        'step' => 0.1,
                    ],
                    '%' => [
                        'min' => 50,
                        'max' => 300,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount bdi' => 'font-size: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->add_control(
            'save_amount_font_family',
            [
                'label' => esc_html__('Save Amount Font Family', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::FONT,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'font-family: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'font-family: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount bdi' => 'font-family: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'save_amount_color',
            [
                'label' => esc_html__('Save Amount Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'color: {{VALUE}} !important;',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount bdi' => 'color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_control(
            'save_amount_background_color',
            [
                'label' => esc_html__('Save Amount Background Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'background-color: {{VALUE}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'background-color: {{VALUE}} !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'save_amount_padding',
            [
                'label' => esc_html__('Save Amount Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-save' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-save .woocommerce-Price-amount.amount' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'section_layout_style',
            [
                'label' => esc_html__('Layout', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'price_gap',
            [
                'label' => esc_html__('Price Gap', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                    'em' => [
                        'min' => 0,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-row' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'price_border',
                'label' => esc_html__('Price Border', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-price-current, {{WRAPPER}} .sh-price-regular, {{WRAPPER}} .sh-price-save',
            ]
        );

        $this->add_control(
            'price_border_radius',
            [
                'label' => esc_html__('Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-price-current' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-regular' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .sh-price-save' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
        
        // If no product found and we're in editor, show example prices
        if (!$product && $is_editor) {
            $this->renderExamplePrices($settings);
            return;
        }

        if (!$product) {
            echo '<p>' . esc_html__('No product found.', 'senheng_core') . '</p>';
            return;
        }

        // Ensure we have a valid product with proper data
        $product_id = $product->get_id();
        $product_type = $product->get_type();
        
        // Get product prices with proper validation
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $current_price = $product->get_price();
        
        // Ensure we have valid numeric prices
        $regular_price = is_numeric($regular_price) ? (float) $regular_price : 0;
        $sale_price = is_numeric($sale_price) ? (float) $sale_price : 0;
        $current_price = is_numeric($current_price) ? (float) $current_price : 0;
        
        // If current price is 0, try to get a valid price
        if ($current_price <= 0) {
            if ($sale_price > 0) {
                $current_price = $sale_price;
            } elseif ($regular_price > 0) {
                $current_price = $regular_price;
            } else {
                // Last resort: set a minimum price to prevent RM0.00
                $current_price = 1.00;
            }
        }
        
        // Prepare price display variables
        $price_html = '';
        $display_regular_price = '';
        $display_sale_price = '';
        $display_save_amount = '';

        if ($product_type === 'variable') {
            // For variable products, get the price range
            $prices = $product->get_variation_prices();
            if (!empty($prices['price'])) {
                $min_price = current($prices['price']);
                $max_price = end($prices['price']);
                
                if ($min_price > 0) {
                    if ($min_price === $max_price) {
                        $price_html = wc_price($min_price);
                    } else {
                        $price_html = wc_price($min_price) . ' - ' . wc_price($max_price);
                    }
                } else {
                    // Fallback for variable products with no valid prices
                    $price_html = wc_price($current_price);
                }
            } else {
                $price_html = wc_price($current_price);
            }
        } else {
            // For simple products
            if ($sale_price > 0 && $sale_price < $regular_price) {
                // Product is on sale
                $display_regular_price = wc_price($regular_price);
                $display_sale_price = wc_price($sale_price);
                $display_save_amount = wc_price($regular_price - $sale_price);
                $price_html = $display_sale_price;
            } else {
                // Product is not on sale
                $display_sale_price = wc_price($current_price);
                $price_html = $display_sale_price;
            }
        }

        // Ensure we always have a valid price display
        if (empty($price_html) || $price_html === '<span class="woocommerce-Price-amount amount"></span>') {
            $price_html = wc_price($current_price);
        }

        // Add widget attributes with comprehensive product data
        $this->add_render_attribute('price_widget', [
            'class' => 'sh-price-widget',
            'data-product-id' => $product_id,
            'data-product-type' => $product_type,
            'data-show-save' => $settings['show_save_amount'],
            'data-product-price' => $current_price,
            'data-product-regular-price' => $regular_price,
            'data-product-sale-price' => $sale_price,
            'data-price-html' => esc_attr($price_html),
        ]);
        ?>

        <div <?php echo $this->get_render_attribute_string('price_widget'); ?>>
            <div class="sh-price-row">
                <span class="sh-price-current"><?php echo $price_html; ?></span>
                <span class="sh-price-regular"<?php echo !$display_regular_price ? ' style="display:none;"' : ''; ?>><?php echo $display_regular_price; ?></span>
                <span class="sh-price-save"<?php echo (!$display_save_amount || $settings['show_save_amount'] !== 'yes') ? ' style="display:none;"' : ''; ?>><?php echo $display_save_amount ? sprintf(__('Save %s', 'senheng_core'), $display_save_amount) : ''; ?></span>
            </div>
        </div>

        <?php
    }

    /**
     * Render example prices for Elementor editor
     */
    private function renderExamplePrices($settings)
    {
        $currency_symbol = get_woocommerce_currency_symbol();
        
        // Example prices for editor preview
        $example_current_price = $currency_symbol . '1,299.00';
        $example_regular_price = $currency_symbol . '1,599.00';
        $example_save_amount = $currency_symbol . '300.00';
        
        // Show save amount based on settings
        $show_save = $settings['show_save_amount'] === 'yes';
        
        $this->add_render_attribute('price_widget', [
            'class' => 'sh-price-widget',
            'data-product-id' => 'example',
            'data-product-type' => 'simple',
            'data-show-save' => $settings['show_save_amount'],
            'data-is-example' => 'true',
        ]);
        ?>

        <div <?php echo $this->get_render_attribute_string('price_widget'); ?>>
            <div class="sh-price-row">
                <span class="sh-price-current"><?php echo $example_current_price; ?></span>
                <span class="sh-price-regular"<?php echo !$show_save ? ' style="display:none;"' : ''; ?>><?php echo $example_regular_price; ?></span>
                <span class="sh-price-save"<?php echo !$show_save ? ' style="display:none;"' : ''; ?>><?php echo sprintf(__('Save %s', 'senheng_core'), $example_save_amount); ?></span>
            </div>
        </div>

        <?php
    }

    protected function content_template()
    {
        ?>
        <# if (elementorFrontend.isEditMode()) { #>
            <div class="sh-price-widget" data-product-id="0">
                <div class="sh-price-row">
                    <span class="sh-price-current">RM 4,799.00</span>
                    <span class="sh-price-regular">RM 5,699.00</span>
                    <# if (settings.show_save_amount === 'yes') { #>
                        <span class="sh-price-save">Save RM 598.00</span>
                    <# } #>
                </div>
            </div>
        <# } else { #>
            <div class="sh-price-widget" data-product-id="{{{ data.product_id }}}" data-show-save="{{{ settings.show_save_amount }}}">
                <div class="sh-price-row">
                    <span class="sh-price-current">{{{ data.current_price }}}</span>
                    <# if (data.regular_price) { #>
                        <span class="sh-price-regular">{{{ data.regular_price }}}</span>
                    <# } #>
                    <# if (data.save_amount && settings.show_save_amount === 'yes') { #>
                        <span class="sh-price-save">Save {{{ data.save_amount }}}</span>
                    <# } #>
                </div>
            </div>
        <# } #>
        <?php
    }
}
