<?php

namespace Senheng\Widgets;

use ScoinController;

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

class SCoinLabelWidget extends \Elementor\Widget_Base
{
	public function get_name()
	{
		return 'sh_scoin_label';
	}

	public function get_title()
	{
		return esc_html__('S-Coin Label', 'senheng_core');
	}

	public function get_icon()
	{
		return 'eicon-favorite';
	}

	public function get_categories()
	{
		return ['senheng'];
	}

	public function get_keywords()
	{
		return ['s-coin', 'loyalty', 'reward', 'senheng'];
	}

	protected function register_controls()
	{
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__('Content', 'senheng_core'),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label' => esc_html__('Show S-Coin icon', 'senheng_core'),
				'type' => \Elementor\Controls_Manager::SWITCHER,
				'label_on' => esc_html__('Show', 'senheng_core'),
				'label_off' => esc_html__('Hide', 'senheng_core'),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			[
				'label' => esc_html__('Label', 'senheng_core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		// Font size is automatically scaled based on icon size using CSS clamp()
		// Typography control removed to prevent conflicts with responsive scaling

        $this->add_responsive_control(
            'percentage_font_size',
            [
                'label' => esc_html__('Number Text Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 200,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic .rebate-info .percentage-number[data-single-digit="true"]' => 'font-size: clamp(12px, calc(var(--icon-height, 80px) * {{SIZE}} / 80), 200px) !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'percentage_font_size_triple',
            [
                'label' => esc_html__('Number Text Size (100+)', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 200,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 18,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic .rebate-info .percentage-number[data-triple-digit="true"]' => 'font-size: clamp(10px, calc(var(--icon-height, 80px) * {{SIZE}} / 80), 200px) !important;',
                ],
            ]
        );

        $this->add_responsive_control(
            'percentage_symbol_font_size',
            [
                'label' => esc_html__('Symbol Text Size', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 120,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 12,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic .rebate-info .percentage-number[data-single-digit="true"] + .percentage-symbol' => 'font-size: clamp(8px, calc(var(--icon-height, 80px) * {{SIZE}} / 80), 120px) !important;',
                ],
            ] 
        );

        $this->add_responsive_control(
            'percentage_symbol_font_size_triple',
            [
                'label' => esc_html__('Symbol Text Size (100+)', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 120,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic .rebate-info .percentage-number[data-triple-digit="true"] + .percentage-symbol' => 'font-size: clamp(8px, calc(var(--icon-height, 80px) * {{SIZE}} / 80), 120px) !important;',
                ],
            ]
        );

		$this->add_control(
			'label_color',
			[
				'label' => esc_html__('Text Color', 'senheng_core'),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sh-scoin-label' => 'color: {{VALUE}};',
				],
				'default' => '#333',
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'label_typography',
				'label' => esc_html__('Typography', 'senheng_core'),
				'selector' => '{{WRAPPER}} .sh-scoin-label, {{WRAPPER}} .sh-scoin-label .percentage-number, {{WRAPPER}} .sh-scoin-label .percentage-symbol',
				'fields_options' => [
					'typography' => ['default' => 'yes'],
					'font_size' => ['default' => ['size' => '']],
					'line_height' => ['default' => ['size' => '']],
				],
			]
		);

		$this->add_group_control(
			\Elementor\Group_Control_Border::get_type(),
			[
				'name' => 'label_border',
				'label' => esc_html__('Border', 'senheng_core'),
				'selector' => '{{WRAPPER}} .sh-scoin-label',
			]
		);

		$this->add_responsive_control(
			'label_padding',
			[
				'label' => esc_html__('Padding', 'senheng_core'),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => ['px', 'em', '%'],
				'selectors' => [
					'{{WRAPPER}} .sh-scoin-label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Icon Style Section
		$this->start_controls_section(
			'icon_style_section',
			[
				'label' => esc_html__('Icon', 'senheng_core'),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label' => esc_html__('Size', 'senheng_core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', '%'],
				'range' => [
					'px' => [
						'min' => 20,
						'max' => 200,
						'step' => 1,
					],
					'em' => [
						'min' => 1,
						'max' => 10,
						'step' => 0.1,
					],
					'rem' => [
						'min' => 1,
						'max' => 10,
						'step' => 0.1,
					],
					'%' => [
						'min' => 10,
						'max' => 200,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 80,
				],
				'selectors' => [
					'{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic img' => 'height: {{SIZE}}{{UNIT}}; width: auto;',
				],
			]
		);

		$this->add_responsive_control(
			'icon_max_width',
			[
				'label' => esc_html__('Max Width', 'senheng_core'),
				'type' => \Elementor\Controls_Manager::SLIDER,
				'size_units' => ['px', 'em', 'rem', '%'],
				'range' => [
					'px' => [
						'min' => 20,
						'max' => 300,
						'step' => 1,
					],
					'%' => [
						'min' => 10,
						'max' => 100,
						'step' => 1,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} .rebate-container-small.s-coin-label-dynamic img' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render()
	{
		// Enqueue widget assets only when widget is rendered
		wp_enqueue_style('sh-s-coin-label-widget-css');
		wp_enqueue_script('sh-s-coin-label-widget-js');

		$settings = $this->get_settings_for_display();

		// Only show on product pages or in editor
		if (!is_product() && !\Elementor\Plugin::$instance->editor->is_edit_mode()) {
			return;
		}

		// In editor mode, show example
		if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
			$this->renderExample($settings);
			return;
		}

		// Get S-coin data for variation handling
        $scoin_data = ScoinController::get_product_scoin_data();
        $percent = isset($scoin_data['parent_percent']) ? (float) $scoin_data['parent_percent'] : 0;
        $has_variation_positive = false;
        if (!empty($scoin_data['variations']) && is_array($scoin_data['variations'])) {
            foreach ($scoin_data['variations'] as $v) {
                if ((float) $v > 0) { $has_variation_positive = true; break; }
            }
        }
        if ($percent <= 0 && !$has_variation_positive) {
            return;
        }
		$icon_url = $this->get_icon_url();

        $this->add_render_attribute('scoin_label', [
            'class' => 'sh-scoin-label',
            'data-percent' => $percent,
            'data-has-parent-scoin' => ($percent > 0) ? 'true' : 'false',
            'data-variations' => wp_json_encode($scoin_data['variations']),
            'data-parent-percent' => isset($scoin_data['parent_percent']) ? $scoin_data['parent_percent'] : 0,
            'data-variation-percents' => wp_json_encode($scoin_data['variations']),
        ]);

		?>
		<div <?php echo $this->get_render_attribute_string('scoin_label'); ?>>
			<div class="rebate-container-small s-coin-label-dynamic">
				<?php if ($settings['show_icon'] === 'yes' && $icon_url) : ?>
					<img src="<?php echo esc_url($icon_url); ?>" alt="S-Coin Cashback">
				<?php endif; ?>
        <div class="rebate-info">
            <?php 
            $percent_str = (string) $percent;
            $digits_only = preg_replace('/\D/', '', $percent_str);
            $digits_count = strlen($digits_only);
            $is_single_digit = ($digits_count <= 2) && ((float)$percent > 0);
            $single_digit_attr = $is_single_digit ? ' data-single-digit="true"' : '';
            $is_triple_digit = $digits_count >= 3;
            $triple_digit_attr = $is_triple_digit ? ' data-triple-digit="true"' : '';
            ?>
            <span class="percentage-number"<?php echo $single_digit_attr . $triple_digit_attr; ?>><?php echo esc_html($percent); ?></span><span class="percentage-symbol">%</span>
        </div>
			</div>
		</div>
		<?php
	}

	private function renderExample($settings)
	{
		$icon_url = $this->get_icon_url();
		$this->add_render_attribute('scoin_label', [
			'class' => 'sh-scoin-label',
			'data-percent' => '1',
		]);
		?>
		<div <?php echo $this->get_render_attribute_string('scoin_label'); ?>>
			<div class="rebate-container-small s-coin-label-dynamic">
				<?php if ($settings['show_icon'] === 'yes' && $icon_url) : ?>
					<img src="<?php echo esc_url($icon_url); ?>" alt="S-Coin Cashback">
				<?php endif; ?>
		<div class="rebate-info">
			<span class="percentage-number" data-single-digit="true">1</span><span class="percentage-symbol">%</span>
		</div>
			</div>
		</div>
		<?php
	}

	private function get_icon_url()
	{
		$default = plugins_url('senheng_core/assets/uploads/s-coin-label.png');
		$relative = '/assets/uploads/s-coin-label.png';

		// Resolve plugin base dir and url
		$base_dir = defined('SENHENG_CORE_PATH') ? rtrim(SENHENG_CORE_PATH, '/').'/' : rtrim(dirname(__DIR__, 2), '/').'/'; 
		$base_url = defined('SENHENG_CORE_URL') ? rtrim(SENHENG_CORE_URL, '/').'/' : plugins_url('/', dirname(__DIR__, 3));

		$path = $base_dir . $relative;
		$url  = $base_url . $relative;

		if (file_exists($path)) {
			return $url;
		}
		return $default;
	}
}
