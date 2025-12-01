<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class BrandDisplayWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'sh_brand_display';
    }

    public function get_title()
    {
        return esc_html__('Senheng Brand Display', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-product-title';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['brand', 'product', 'woocommerce', 'senheng'];
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
            'display_mode',
            [
                'label' => esc_html__('Display Mode', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'current_product',
                'options' => [
                    'current_product' => esc_html__('Current Product Brand', 'senheng_core'),
                    'all_brands' => esc_html__('All Brands', 'senheng_core'),
                    'specific_brands' => esc_html__('Specific Brands', 'senheng_core'),
                ],
            ]
        );

        $this->add_control(
            'selected_brands',
            [
                'label' => esc_html__('Select Brands', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_brand_options(),
                'condition' => [
                    'display_mode' => 'specific_brands',
                ],
            ]
        );

        $this->add_control(
            'brands_limit',
            [
                'label' => esc_html__('Number of Brands', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
                'condition' => [
                    'display_mode' => 'all_brands',
                ],
            ]
        );

        $this->add_control(
            'show_brand_image',
            [
                'label' => esc_html__('Show Brand Image', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_brand_name',
            [
                'label' => esc_html__('Show Brand Name', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Show', 'senheng_core'),
                'label_off' => esc_html__('Hide', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
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

        $this->add_control(
            'layout',
            [
                'label' => esc_html__('Layout', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'horizontal',
                'options' => [
                    'horizontal' => esc_html__('Horizontal', 'senheng_core'),
                    'vertical' => esc_html__('Vertical', 'senheng_core'),
                    'grid' => esc_html__('Grid', 'senheng_core'),
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'condition' => [
                    'layout' => 'grid',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'brand_typography',
                'label' => esc_html__('Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-brand-name',
            ]
        );

        $this->add_control(
            'brand_color',
            [
                'label' => esc_html__('Brand Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .sh-brand-name' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'brand_hover_color',
            [
                'label' => esc_html__('Brand Hover Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .sh-brand-item:hover .sh-brand-name' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'hover_text_decoration',
            [
                'label' => esc_html__('Hover Text Decoration', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'underline',
                'options' => [
                    'none' => esc_html__('None', 'senheng_core'),
                    'underline' => esc_html__('Underline', 'senheng_core'),
                    'overline' => esc_html__('Overline', 'senheng_core'),
                    'line-through' => esc_html__('Line Through', 'senheng_core'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-brand-item:hover .sh-brand-name' => 'text-decoration: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'hover_transition_duration',
            [
                'label' => esc_html__('Transition Duration (ms)', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [
                    'size' => 300,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 2000,
                        'step' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-brand-name' => 'transition: all {{SIZE}}ms ease',
                ],
            ]
        );

        $this->add_control(
            'hover_transform',
            [
                'label' => esc_html__('Hover Transform', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => esc_html__('None', 'senheng_core'),
                    'scale(1.05)' => esc_html__('Scale Up', 'senheng_core'),
                    'scale(0.95)' => esc_html__('Scale Down', 'senheng_core'),
                    'translateY(-2px)' => esc_html__('Move Up', 'senheng_core'),
                    'translateY(2px)' => esc_html__('Move Down', 'senheng_core'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-brand-item:hover .sh-brand-name' => 'transform: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $brands = $this->get_brands_to_display($settings);

        // In Elementor editor, always show some brands for preview
        if (empty($brands) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $brands = $this->get_preview_brands($settings);
        }

        if (empty($brands)) {
            // Hide the widget when no brands are found
            echo '<style>.elementor-element-' . $this->get_id() . ' { display: none !important; }</style>';
            return;
        }

        $layout_class = 'sh-brand-layout-' . $settings['layout'];
        if ($settings['layout'] === 'grid') {
            $layout_class .= ' sh-brand-columns-' . $settings['columns'];
        }

        echo '<div class="sh-brand-display ' . esc_attr($layout_class) . '">';

        foreach ($brands as $brand) {
            // Handle preview brands in editor
            if (\Elementor\Plugin::$instance->editor->is_edit_mode() && strpos($brand->term_id, 'preview_') === 0) {
                $brand_url = '#';
            } else {
                $brand_url = get_term_link($brand, 'product_brand');
                if (is_wp_error($brand_url)) {
                    continue;
                }
            }

            echo '<div class="sh-brand-item">';
            echo '<a href="' . esc_url($brand_url) . '" class="sh-brand-link">';

            // Brand Name (text only)
            if ($settings['show_brand_name'] === 'yes') {
                echo '<span class="sh-brand-name">' . esc_html($brand->name) . '</span>';
            }

            echo '</a>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function get_brands_to_display($settings)
    {
        global $product;

        switch ($settings['display_mode']) {
            case 'current_product':
                if (is_product() && $product) {
                    $brand_terms = get_the_terms($product->get_id(), 'product_brand');
                    return ($brand_terms && !is_wp_error($brand_terms)) ? $brand_terms : [];
                }
                // In editor mode, show sample brands for current_product mode
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    return [];
                }
                return [];

            case 'specific_brands':
                if (empty($settings['selected_brands'])) {
                    return [];
                }
                $brands = [];
                foreach ($settings['selected_brands'] as $brand_id) {
                    $brand = get_term($brand_id, 'product_brand');
                    if ($brand && !is_wp_error($brand)) {
                        $brands[] = $brand;
                    }
                }
                return $brands;

            case 'all_brands':
                $args = [
                    'taxonomy' => 'product_brand',
                    'hide_empty' => true,
                    'number' => $settings['brands_limit'] ?? 10,
                ];
                $brands = get_terms($args);
                return is_wp_error($brands) ? [] : $brands;
                
            default:
                return [];
        }
    }

    private function get_preview_brands($settings)
    {
        // For current_product mode, show limited preview
        if ($settings['display_mode'] === 'current_product') {
            $mock_brands = [];
            $mock_brand = new \stdClass();
            $mock_brand->term_id = 'preview_current';
            $mock_brand->name = 'Current Product Brand';
            $mock_brand->slug = 'current-product-brand';
            $mock_brands[] = $mock_brand;
            return $mock_brands;
        }
        
        // For other modes, get actual brands for editor preview
        $limit = $settings['display_mode'] === 'all_brands' ? min($settings['brands_limit'] ?? 6, 6) : 3;
        $args = [
            'taxonomy' => 'product_brand',
            'hide_empty' => false,
            'number' => $limit,
        ];
        
        $brands = get_terms($args);
        
        // If no real brands exist, create mock data for preview
        if (is_wp_error($brands) || empty($brands)) {
            $mock_brands = [];
            $sample_names = ['Apple', 'Samsung', 'Sony', 'LG', 'Panasonic', 'Philips'];
            
            for ($i = 0; $i < $limit; $i++) {
                $mock_brand = new \stdClass();
                $mock_brand->term_id = 'preview_' . ($i + 1);
                $mock_brand->name = $sample_names[$i] ?? 'Brand ' . ($i + 1);
                $mock_brand->slug = sanitize_title($mock_brand->name);
                $mock_brands[] = $mock_brand;
            }
            
            return $mock_brands;
        }
        
        return $brands;
    }

    private function get_brand_options()
    {
        $options = [];
        $brands = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => false,
        ]);

        if (!is_wp_error($brands)) {
            foreach ($brands as $brand) {
                $options[$brand->term_id] = $brand->name;
            }
        }

        return $options;
    }
}