<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit;
}

class InstallmentDisplayWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'sh_installment_display';
    }

    public function get_title()
    {
        return esc_html__('Senheng Installment Display', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-price-list';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['installment', 'payment', 'bnpl', 'monthly', 'product'];
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
            'prefix_text',
            [
                'label' => esc_html__('Prefix Text', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'From',
                'placeholder' => esc_html__('From', 'senheng_core'),
            ]
        );

        $this->add_control(
            'suffix_text',
            [
                'label' => esc_html__('Suffix Text', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '/Month',
                'placeholder' => esc_html__('/Month', 'senheng_core'),
            ]
        );

        $this->add_control(
            'show_tenure',
            [
                'label' => esc_html__('Show Tenure', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Show tenure like (x36)', 'senheng_core'),
            ]
        );

        $this->add_control(
            'no_plan_text',
            [
                'label' => esc_html__('No Plan Available Text', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'placeholder' => esc_html__('Leave empty to hide widget', 'senheng_core'),
                'description' => esc_html__('Text to show when no installment plan is available', 'senheng_core'),
            ]
        );

        $this->add_control(
            'show_info_icon',
            [
                'label' => esc_html__('Show Info Icon', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Show info icon that opens installment details popup', 'senheng_core'),
            ]
        );

        $this->end_controls_section();

        // Style Section - Main Text
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Typography', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'label' => esc_html__('Text Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-installment-display',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label' => esc_html__('Text Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-display' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'price_color',
            [
                'label' => esc_html__('Price Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e53935',
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tenure_color',
            [
                'label' => esc_html__('Tenure Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#666666',
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-tenure' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Layout Section
        $this->start_controls_section(
            'layout_section',
            [
                'label' => esc_html__('Layout', 'senheng_core'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'senheng_core'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'senheng_core'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'senheng_core'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-wrapper' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => esc_html__('Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-display' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'background_color',
            [
                'label' => esc_html__('Background Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-display' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'border',
                'selector' => '{{WRAPPER}} .sh-installment-display',
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => esc_html__('Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-installment-display' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        // Enqueue widget assets only when widget is rendered
        wp_enqueue_style('sh-installment-display-widget-css');
        wp_enqueue_script('sh-installment-display-widget-js');

        $settings = $this->get_settings_for_display();
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        if (!is_product() && !$is_editor) {
            return;
        }

        global $product;

        // Use static cache for payment plans to avoid repeated DB queries
        static $cachedPlans = null;
        if ($cachedPlans === null) {
            $cachedPlans = \PaymentMethod::getPaymentMethodPlans();
        }
        $paymentPlans = $cachedPlans;
        
        if (empty($paymentPlans) && !$is_editor) {
            if (!empty($settings['no_plan_text'])) {
                echo '<div class="sh-installment-wrapper"><span class="sh-installment-display">' . esc_html($settings['no_plan_text']) . '</span></div>';
            }
            return;
        }

        // Get product price
        $price = 0;
        $product_type = 'simple';
        
        if ($product) {
            $product_type = $product->get_type();
            if ($product_type === 'variable') {
                // For variable products, use the LOWEST price from ALL variations for initial display
                $prices = $product->get_variation_prices();
                if (!empty($prices['price'])) {
                    // Get minimum price across all variations
                    $price = (float) min($prices['price']);
                }
            } else {
                $price = (float) ($product->get_sale_price() ?: $product->get_regular_price());
            }
        }

        // For editor preview with no product
        if ($is_editor && (!$product || $price <= 0)) {
            $price = 2099;
        }

        // Pre-process payment plans once - build optimized data structure
        // This combines both loops into one for better performance
        $allPlanMonths = [];
        $lowestMonthly = null;
        $bestTenure = 0;
        
        // BNPL providers for low-price products (RM10-500)
        $bnpl_providers = ['Atome', 'GrabPay BNPL'];
        $is_low_price_product = ($price >= 10 && $price <= 500);
        $min_tenure_for_low_price = 4; // Minimum 4 months for RM10-500 products
        
        foreach ($paymentPlans as $plan) {
            $months = (int) $plan->months;
            $minAmount = (float) ($plan->min_amount ?? 0);
            $providerName = $plan->name ?? '';
            
            // For low-price products (RM10-500), only consider BNPL providers
            if ($is_low_price_product) {
                $is_bnpl_provider = false;
                foreach ($bnpl_providers as $bnpl) {
                    if (stripos($providerName, $bnpl) !== false) {
                        $is_bnpl_provider = true;
                        break;
                    }
                }
                
                // Skip non-BNPL providers for low-price products
                if (!$is_bnpl_provider) {
                    continue;
                }
            }
            
            // Build plan data for JS (keep lowest min_amount per tenure)
            if (!isset($allPlanMonths[$months]) || $minAmount < $allPlanMonths[$months]) {
                $allPlanMonths[$months] = $minAmount;
            }
            
            // Calculate lowest monthly for current price
            if ($price >= $minAmount && $months > 0) {
                $monthly = $price / $months;
                if ($lowestMonthly === null || $monthly < $lowestMonthly) {
                    $lowestMonthly = $monthly;
                    $bestTenure = $months;
                }
            }
        }

        // If no valid plan found
        if ($lowestMonthly === null || $bestTenure === 0) {
            if ($is_editor) {
                // Show example in editor
                $lowestMonthly = 2099 / 36;
                $bestTenure = 36;
            } else {
                if (!empty($settings['no_plan_text'])) {
                    echo '<div class="sh-installment-wrapper"><span class="sh-installment-display">' . esc_html($settings['no_plan_text']) . '</span></div>';
                }
                return;
            }
        }

        // Build output - cache formatted values
        $prefix = esc_html($settings['prefix_text']);
        $suffix = esc_html($settings['suffix_text']);
        $show_tenure = $settings['show_tenure'] === 'yes';
        $show_info_icon = $settings['show_info_icon'] === 'yes';
        
        $formatted_price = 'RM' . number_format($lowestMonthly, 2);
        $tenure_text = $show_tenure ? "(x{$bestTenure})" : '';

        // Encode plans data once (already built above)

        $this->add_render_attribute('wrapper', [
            'class' => 'sh-installment-wrapper',
        ]);

        $this->add_render_attribute('display', [
            'class' => 'sh-installment-display',
            'data-product-type' => $product_type,
            'data-plans' => esc_attr(json_encode($allPlanMonths)),
            'data-prefix' => $prefix,
            'data-suffix' => $suffix,
            'data-show-tenure' => $show_tenure ? 'yes' : 'no',
        ]);

        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <span <?php echo $this->get_render_attribute_string('display'); ?>>
                <?php if ($prefix) : ?>
                    <span class="sh-installment-prefix"><?php echo $prefix; ?></span>
                <?php endif; ?>
                <span class="sh-installment-price"><?php echo $formatted_price; ?></span>
                <?php if ($suffix) : ?>
                    <span class="sh-installment-suffix"><?php echo $suffix; ?></span>
                <?php endif; ?>
                <?php if ($show_tenure && $bestTenure > 0) : ?>
                    <span class="sh-installment-tenure"><?php echo $tenure_text; ?></span>
                <?php endif; ?>
                <?php if ($show_info_icon) : ?>
                    <span class="sh-installment-info-icon installment-details" id="installment-details" role="button" tabindex="0" aria-label="View installment details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </span>
                <?php endif; ?>
            </span>
        </div>
        <?php
    }

    protected function content_template()
    {
        ?>
        <# 
        var prefix = settings.prefix_text || 'From';
        var suffix = settings.suffix_text || '/Month';
        var showTenure = settings.show_tenure === 'yes';
        var showInfoIcon = settings.show_info_icon === 'yes';
        #>
        <div class="sh-installment-wrapper">
            <span class="sh-installment-display">
                <# if (prefix) { #>
                    <span class="sh-installment-prefix">{{{ prefix }}}</span>
                <# } #>
                <span class="sh-installment-price">RM58.31</span>
                <# if (suffix) { #>
                    <span class="sh-installment-suffix">{{{ suffix }}}</span>
                <# } #>
                <# if (showTenure) { #>
                    <span class="sh-installment-tenure">(x36)</span>
                <# } #>
                <# if (showInfoIcon) { #>
                    <span class="sh-installment-info-icon installment-details" role="button" tabindex="0" aria-label="View installment details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="16" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12.01" y2="8"></line>
                        </svg>
                    </span>
                <# } #>
            </span>
        </div>
        <?php
    }
}
