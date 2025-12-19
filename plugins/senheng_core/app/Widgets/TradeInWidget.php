<?php

namespace Senheng\Widgets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class TradeInWidget extends \Elementor\Widget_Base
{
    public function get_name()
    {
        return 'sh_trade_in';
    }

    public function get_title()
    {
        return esc_html__('Senheng Trade In', 'senheng_core');
    }

    public function get_icon()
    {
        return 'eicon-exchange';
    }

    public function get_categories()
    {
        return ['senheng'];
    }

    public function get_keywords()
    {
        return ['trade', 'in', 'deposit', 'product', 'woocommerce'];
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
            'field_label',
            [
                'label' => esc_html__('Field Label', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Trade In',
                'placeholder' => esc_html__('Enter field label', 'senheng_core'),
            ]
        );

        $this->add_control(
            'required_field',
            [
                'label' => esc_html__('Required Field', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'senheng_core'),
                'label_off' => esc_html__('No', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'hide_when_no_deposit',
            [
                'label' => esc_html__('Hide When Deposit Disabled', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Yes', 'senheng_core'),
                'label_off' => esc_html__('No', 'senheng_core'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Hide the widget completely when deposits are not enabled for the product', 'senheng_core'),
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
                'name' => 'label_typography',
                'label' => esc_html__('Label Typography', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-trade-in-label',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Label Color', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .sh-trade-in-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'field_spacing',
            [
                'label' => esc_html__('Field Spacing', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 15,
                ],
                'selectors' => [
                    '{{WRAPPER}} .sh-trade-in-wrapper' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'select_border',
                'label' => esc_html__('Select Border', 'senheng_core'),
                'selector' => '{{WRAPPER}} .sh-trade-in-select',
            ]
        );

        $this->add_control(
            'select_border_radius',
            [
                'label' => esc_html__('Select Border Radius', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-trade-in-select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'select_padding',
            [
                'label' => esc_html__('Select Padding', 'senheng_core'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .sh-trade-in-select' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get current product ID with enhanced Elementor and preview support
     */
    private function get_current_product_id()
    {
        global $product, $post;

        // Method 1: Check if we have a global product object
        if (isset($product) && is_object($product) && method_exists($product, 'get_id')) {
            return $product->get_id();
        }

        // Method 2: Check URL parameters for product ID (common in Elementor preview)
        if (isset($_GET['post']) && is_numeric($_GET['post'])) {
            $post_id = intval($_GET['post']);
            if (get_post_type($post_id) === 'product') {
                return $post_id;
            }
        }

        // Method 3: Check for product_id in URL parameters
        if (isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
            return intval($_GET['product_id']);
        }

        // Method 4: Check for Woodmart preview mode
        if (isset($_GET['preview_id']) && !empty($_GET['preview_id'])) {
            $preview_id = intval($_GET['preview_id']);
            if (get_post_type($preview_id) === 'product') {
                return $preview_id;
            }
        }

        // Method 5: Check for Elementor preview mode
        if (isset($_GET['elementor-preview']) && !empty($_GET['elementor-preview'])) {
            $preview_id = intval($_GET['elementor-preview']);
            if (get_post_type($preview_id) === 'product') {
                return $preview_id;
            }
        }

        // Method 6: Check if we're on a product page via global $post
        if (isset($post) && is_object($post) && $post->post_type === 'product') {
            return $post->ID;
        }

        // Method 7: Try to get product from WooCommerce functions
        if (function_exists('wc_get_product')) {
            // Check if we're on a single product page
            if (is_product()) {
                $current_product = wc_get_product();
                if ($current_product) {
                    return $current_product->get_id();
                }
            }
        }

        // Method 8: Check if we're on a product page
        if (is_product() && get_the_ID()) {
            return get_the_ID();
        }

        // Method 9: Check queried object as last resort
        $queried_object = get_queried_object();
        if ($queried_object && isset($queried_object->ID) && get_post_type($queried_object->ID) === 'product') {
            return $queried_object->ID;
        }

        // Method 10: For Elementor editor, try to get from the current editing context
        if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            // In Elementor editor, try to get the post being edited
            if (isset($_GET['post']) && is_numeric($_GET['post'])) {
                $editing_post_id = intval($_GET['post']);
                if (get_post_type($editing_post_id) === 'product') {
                    return $editing_post_id;
                }
            }
        }

        return false;
    }

    /**
     * Check if deposits are enabled for the current product
     */
    private function is_deposit_enabled($product_id)
    {
        // Check if Acowebs Deposits plugin is active
        if (!defined('AWCDP_DEPOSITS_META_KEY')) {
            // Fallback to direct meta key if constant not defined
            $meta_key = '_awcdp_deposit_enabled';
        } else {
            $meta_key = AWCDP_DEPOSITS_META_KEY;
        }

        // Get deposit enabled status from product meta
        $deposit_enabled = get_post_meta($product_id, $meta_key, true);
        
        // If explicitly set to 'no', return false immediately
        if ($deposit_enabled === 'no') {
            return false;
        }
        
        // Debug: Also check for common variations of the meta key
        if (empty($deposit_enabled) || $deposit_enabled !== 'yes') {
            $alt_keys = ['_awcdp_deposits_enabled', '_awcdp_enable_deposit', '_deposit_enabled'];
            foreach ($alt_keys as $alt_key) {
                $alt_value = get_post_meta($product_id, $alt_key, true);
                // Check for explicit 'no' values
                if ($alt_value === 'no') {
                    return false;
                }
                // Check for positive values
                if ($alt_value === 'yes' || $alt_value === '1' || $alt_value === 1) {
                    $deposit_enabled = 'yes';
                    break;
                }
            }
        }
        
        return $deposit_enabled === 'yes';
    }

    /**
     * Check if Extra Product Options plugin is active and trade_in field exists
     */
    private function is_extra_product_options_active()
    {
        // Check if Extra Product Options plugin by ThemeHigh is active
        // The plugin defines THWEPOF_VERSION constant when active (not THWEPO_VERSION)
        $is_active = defined('THWEPOF_VERSION') || 
                    class_exists('THWEPOF_Public') || 
                    class_exists('THWEPOF') ||
                    function_exists('thwepof_get_public_instance') ||
                    // Also check for TM Extra Product Options (alternative plugin)
                    class_exists('THEMECOMPLETE_EPO') ||
                    function_exists('wc_epo_get_product_tm_epos') ||
                    // Legacy checks for older versions
                    defined('THWEPO_VERSION') || 
                    class_exists('THWEPO_Public') || 
                    class_exists('THWEPO') ||
                    function_exists('thwepo_get_public_instance');
        
        return $is_active;
    }

    /**
     * Render the deposit section for AWCDP deposits
     */
    protected function render_deposit_section($product_id)
    {
        if (!$product_id) {
            return;
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        // Get general settings for default selection
        $awcdp_gs = get_option('awcdp_general_settings');
        $default_checked = isset($awcdp_gs['default_option']) ? $awcdp_gs['default_option'] : 'deposit';
        
        // Get deposit settings (these are now defined in main render method)
        $deposit_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
        $deposit_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
        $deposit_text = get_post_meta($product_id, '_awcdp_deposit_text', true);
        $full_text = get_post_meta($product_id, '_awcdp_full_text', true);
        
        // Default values if not set
        if (empty($deposit_text)) {
            $deposit_text = __('Deposit', 'senheng_core');
        }
        if (empty($full_text)) {
            $full_text = __('Full Amount', 'senheng_core');
        }
        if (empty($deposit_amount)) {
            $deposit_amount = 0;
        }
        // Don't set default deposit_type here - let it remain empty if not set
        // This allows us to handle the "Select" case properly
        
        // Calculate deposit description
        $deposit_option_text = __('Pay only', 'senheng_core') . ' ';
        $suffix = ' ' . __('now and the rest later', 'senheng_core');
        
        // Display logic for deposit description
        $display = ($default_checked === 'deposit') ? '' : 'style="display:none;"';
        
        ?>
        <div class="sh-trade-in-table variations sh-payment-section">
            <div class="sh-label sh-cell">
                <label for="awcdp_deposit_option_<?php echo esc_attr($product_id); ?>">
                    <?php esc_html_e('Payment Option', 'senheng_core'); ?>
                </label>
            </div>
            <div class="sh-value sh-cell with-swatches">
                <div class="wd-swatches-product wd-swatches-single wd-text-style-1 wd-size-default" data-id="awcdp_deposit_option_<?php echo esc_attr($product_id); ?>" role="radiogroup">
                    <div class="wd-swatch wd-text" data-value="no" data-title="<?php echo esc_attr($full_text); ?>">
                        <span class="wd-swatch-text"><?php echo esc_html($full_text); ?></span>
                    </div>
                    <div class="wd-swatch wd-text" data-value="yes" data-title="<?php 
                        // Create deposit option label
                        if ($deposit_type === 'percent') {
                            $deposit_label = $deposit_text . ': ' . $deposit_amount . '%';
                            echo esc_attr($deposit_label);
                        } elseif ($deposit_type === 'fixed') {
                            echo esc_attr($deposit_text . ': ' . strip_tags(wc_price($deposit_amount)));
                        } else {
                            if (!empty($deposit_amount)) {
                                echo esc_attr($deposit_text . ': ' . strip_tags(wc_price($deposit_amount)));
                            } else {
                                echo esc_attr($deposit_text);
                            }
                        }
                        ?>">
                        <span class="wd-swatch-text">
                            <?php 
                            // Create deposit option label
                            if ($deposit_type === 'percent') {
                                $deposit_label = $deposit_text . ': ' . $deposit_amount . '%';
                                echo esc_html($deposit_label);
                            } elseif ($deposit_type === 'fixed') {
                                echo esc_html($deposit_text . ': ') . wp_kses_post(wc_price($deposit_amount));
                            } else {
                                if (!empty($deposit_amount)) {
                                    echo esc_html($deposit_text . ': ') . wp_kses_post(wc_price($deposit_amount));
                                } else {
                                    echo esc_html($deposit_text);
                                }
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <select name="awcdp_deposit_option" id="awcdp_deposit_option_<?php echo esc_attr($product_id); ?>" class="sh-payment-select" data-product-id="<?php echo esc_attr($product_id); ?>" style="display: none;">
                    <option value=""><?php esc_html_e('Choose an option', 'senheng_core'); ?></option>
                    <option value="no" <?php selected($default_checked, 'full'); ?>><?php echo esc_html($full_text); ?></option>
                    <option value="yes" <?php selected($default_checked, 'deposit'); ?>>
                        <?php 
                        // Create deposit option label
                        if ($deposit_type === 'percent') {
                            $deposit_label = $deposit_text . ': ' . $deposit_amount . '%';
                            echo esc_html($deposit_label);
                        } elseif ($deposit_type === 'fixed') {
                            echo esc_html($deposit_text . ': ') . wp_kses_post(wc_price($deposit_amount));
                        } else {
                            if (!empty($deposit_amount)) {
                                echo esc_html($deposit_text . ': ') . wp_kses_post(wc_price($deposit_amount));
                            } else {
                                echo esc_html($deposit_text);
                            }
                        }
                        ?>
                    </option>
                </select>
            </div>
        </div>
        <?php
    }

    protected function render()
    {
        // Enqueue widget assets only when widget is rendered
        wp_enqueue_style('sh-trade-in-widget-css');
        wp_enqueue_script('sh-trade-in-widget-js');

        $settings = $this->get_settings_for_display();
        $product_id = $this->get_current_product_id();

        // Remove AWCDP default deposit container to prevent conflicts with our custom widget
        if (class_exists('AWCDP_Front_End')) {
            // Use WordPress's remove_class_action function if available, otherwise use manual approach
            if (function_exists('remove_class_action')) {
                remove_class_action('woocommerce_before_add_to_cart_button', 'AWCDP_Front_End', 'awcdp_get_deposit_container', 999);
            } else {
                // Manual removal by iterating through the filter callbacks
                global $wp_filter;
                if (isset($wp_filter['woocommerce_before_add_to_cart_button']) && 
                    isset($wp_filter['woocommerce_before_add_to_cart_button']->callbacks[999])) {
                    
                    foreach ($wp_filter['woocommerce_before_add_to_cart_button']->callbacks[999] as $key => $callback) {
                        if (is_array($callback['function']) && 
                            is_object($callback['function'][0]) && 
                            get_class($callback['function'][0]) === 'AWCDP_Front_End' && 
                            $callback['function'][1] === 'awcdp_get_deposit_container') {
                            
                            unset($wp_filter['woocommerce_before_add_to_cart_button']->callbacks[999][$key]);
                            break;
                        }
                    }
                }
            }
        }

        // Remove WooCommerce Extra Product Options to prevent conflicts with our custom widget
        if (class_exists('THWEPOF_Public')) {
            // Remove the before add to cart button extra options
            $this->remove_thwepof_actions('woocommerce_before_add_to_cart_button', 'woo_before_add_to_cart_button');
            // Remove the after add to cart button extra options  
            $this->remove_thwepof_actions('woocommerce_after_add_to_cart_button', 'woo_after_add_to_cart_button');
        }

        // Debug information for Elementor editor
        $is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();

        // If no product ID found, don't render anything
        if (!$product_id) {
            return;
        }

        // Check if Extra Product Options is active first
        if (!$this->is_extra_product_options_active()) {
            if (!$is_editor) {
                return;
            }
        }

        // Check if deposits are enabled
        $deposit_enabled = $this->is_deposit_enabled($product_id);
        
        // Get deposit settings for debug info (even if deposits are disabled)
        $deposit_amount = get_post_meta($product_id, '_awcdp_deposits_deposit_amount', true);
        $deposit_type = get_post_meta($product_id, '_awcdp_deposit_type', true);
        $deposit_text = get_post_meta($product_id, '_awcdp_deposit_text', true);
        $full_text = get_post_meta($product_id, '_awcdp_full_text', true);
        
        // Default values for display
        if (empty($deposit_text)) {
            $deposit_text = __('Deposit', 'senheng_core');
        }
        if (empty($full_text)) {
            $full_text = __('Full Amount', 'senheng_core');
        }
        if (empty($deposit_amount)) {
            $deposit_amount = 0;
        }

        // If deposits are not enabled, hide widget on frontend (but show in editor for debugging)
        if (!$deposit_enabled) {
            if (!$is_editor) {
                return;
            }
        }

        // If deposit amount is empty or zero, hide widget on frontend (but show in editor for debugging)
        if (empty($deposit_amount) || $deposit_amount <= 0) {
            if (!$is_editor) {
                return;
            }
        }

        $field_label = !empty($settings['field_label']) ? $settings['field_label'] : 'Trade In';
        $required = $settings['required_field'] === 'yes';
        $required_attr = $required ? 'required' : '';
        $required_mark = ''; // Removed required mark display

        ?>
        <div class="sh-trade-in-wrapper" data-product-id="<?php echo esc_attr($product_id); ?>" data-deposit-enabled="<?php echo $deposit_enabled ? 'yes' : 'no'; ?>">
            
            
            <div class="sh-trade-in-table variations">
                <div class="sh-label sh-cell">
                    <label for="trade_in_<?php echo esc_attr($product_id); ?>">
                        <?php echo esc_html($field_label) . $required_mark; ?>
                    </label>
                </div>
                <div class="sh-value sh-cell with-swatches">
                    <div class="wd-swatches-product wd-swatches-single wd-text-style-1 wd-size-default" data-id="trade_in_<?php echo esc_attr($product_id); ?>" role="radiogroup">
                        <div class="wd-swatch wd-text" data-value="yes" data-title="Yes">
                            <span class="wd-swatch-text">Yes</span>
                        </div>
                        <div class="wd-swatch wd-text" data-value="no" data-title="No">
                            <span class="wd-swatch-text">No</span>
                        </div>
                    </div>
                    <select name="trade_in" id="trade_in_<?php echo esc_attr($product_id); ?>" class="sh-trade-in-select" data-product-id="<?php echo esc_attr($product_id); ?>" <?php echo $required_attr; ?> style="display: none;">
                        <option value=""><?php esc_html_e('Choose an option', 'senheng_core'); ?></option>
                        <option value="yes"><?php esc_html_e('Yes', 'senheng_core'); ?></option>
                        <option value="no"><?php esc_html_e('No', 'senheng_core'); ?></option>
                    </select>
                </div>
            </div>
            
            <?php 
            // Add deposit section if deposits are enabled
            if ($deposit_enabled) {
                $this->render_deposit_section($product_id);
            } else if ($settings['hide_when_no_deposit'] !== 'yes') { ?>
                <div class="sh-trade-in-notice sh-trade-in-no-deposit">
                    <?php esc_html_e('Note: Deposits are not enabled for this product', 'senheng_core'); ?>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    /**
     * Helper method to remove THWEPOF (WooCommerce Extra Product Options) actions
     * 
     * @param string $hook_name The WordPress hook name (e.g., 'woocommerce_before_add_to_cart_button')
     * @param string $method_name The THWEPOF method name (e.g., 'woo_before_add_to_cart_button')
     */
    private function remove_thwepof_actions($hook_name, $method_name)
    {
        global $wp_filter;
        
        if (!isset($wp_filter[$hook_name])) {
            return;
        }
        
        // Iterate through all priority levels
        foreach ($wp_filter[$hook_name]->callbacks as $priority => $callbacks) {
            foreach ($callbacks as $key => $callback) {
                if (is_array($callback['function']) && 
                    is_object($callback['function'][0]) && 
                    get_class($callback['function'][0]) === 'THWEPOF_Public' && 
                    $callback['function'][1] === $method_name) {
                    
                    unset($wp_filter[$hook_name]->callbacks[$priority][$key]);
                    break 2; // Break out of both loops once found
                }
            }
        }
    }

    protected function content_template()
    {
        ?>
        <#
        var fieldLabel = settings.field_label || 'Trade In';
        var required = settings.required_field === 'yes';
        var requiredMark = ''; // Removed required mark display
        var requiredAttr = required ? 'required' : '';
        #>
        <div class="sh-trade-in-wrapper">
            <label for="trade_in" class="sh-trade-in-label">
                {{{ fieldLabel }}}{{{ requiredMark }}}
            </label>
            <select name="trade_in" id="trade_in" class="sh-trade-in-select" {{{ requiredAttr }}}>
                <option value="">Please select...</option>
                <option value="yes">Yes</option>
                <option value="no">No</option>
            </select>
        </div>
        <?php
    }
}