<?php

// add_filter('woocommerce_checkout_fields', 'add_custom_billing_ic_warranty_field');
// add_action('woocommerce_checkout_update_customer', 'save_cust_icno_warranty_to_user_meta');
// add_action('woocommerce_checkout_update_order_meta', 'save_cust_icno_warranty_to_order_meta');

add_filter('woocommerce_checkout_fields', 'add_custom_billing_ic_field');
add_action('woocommerce_checkout_update_order_meta', 'save_cust_icno_to_order_meta');

function add_custom_billing_ic_warranty_field($fields)
{
    // Only show if product with warranty is in the cart
    $has_warranty = false;

    foreach (WC()->cart->get_cart() as $item) {
        if (get_field('warranty_enabled_sh', $item['product_id'])) {
            $has_warranty = true;
            break;
        }
    }

    if ($has_warranty) {
        $current_user_id = get_current_user_id();
        $cust_icno = $current_user_id ? get_user_meta($current_user_id, 'cust_icno', true) : '';

        $fields['billing']['cust_icno_for_warranty'] = array(
            'label' => __('IC Number (For Warranty Registration)', 'woocommerce'),
            'placeholder' => 'e.g. 800101-14-5678',
            'required' => true,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 45,
            // 'default' => $cust_icno,
        );
    }

    return $fields;
}

function save_cust_icno_warranty_to_user_meta($customer)
{
    if (isset($_POST['cust_icno_for_warranty'])) {
        update_user_meta($customer->get_id(), 'cust_icno_for_warranty', sanitize_text_field($_POST['cust_icno_for_warranty']));
    }
}

function save_cust_icno_warranty_to_order_meta($order_id)
{
    if (isset($_POST['cust_icno_for_warranty'])) {
        update_post_meta($order_id, 'cust_icno_for_warranty', sanitize_text_field($_POST['cust_icno_for_warranty']));
    }
}

function add_custom_billing_ic_field($fields)
{
    if (!is_user_logged_in()) {

        $fields['billing']['cust_icno_type'] = array(
            'type'     => 'select',
            'label'    => __('ID Type (Optional)', 'textdomain'),
            'required' => false,
            'priority' => 9,
            'options'  => [
                // 'BRN'      => __('Business Registration Number (BRN)', 'textdomain'),
                'NRIC'     => __('National ID (NRIC)', 'textdomain'),
                // 'Passport' => __('Passport', 'textdomain'),
                // 'Army'     => __('Army', 'textdomain'),
            ],
            'class'    => array('form-row-wide'),
            'default'  => 'NRIC',
        );

        $fields['billing']['cust_icno'] = array(
            'label' => __('IC Number', 'woocommerce'),
            'placeholder' => 'eg: 1234567891012',
            'required' => false,
            'class' => array('form-row-wide'),
            'clear' => true,
            'priority' => 10,
            // 'default' => $cust_icno,
        );
    }

    return $fields;
}

function save_cust_icno_to_order_meta($order_id)
{
    if (isset($_POST['cust_icno'])) {
        update_post_meta($order_id, 'cust_icno', sanitize_text_field($_POST['cust_icno']));
    }
}
