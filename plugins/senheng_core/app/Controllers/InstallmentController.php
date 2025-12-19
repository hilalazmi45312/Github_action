<?php

// app/Controllers/InstallmentController.php

// use PaymentMethod;

class InstallmentController
{

    public static function sh_enqueue_bnpl_assets()
    {
        if (is_home() || is_front_page()) {
            return;
        }

        wp_enqueue_style(
            'sh-bnpl-style',
            SENHENG_CORE_URL . 'assets/css/sh-bnpl.css'
        );

        wp_enqueue_script(
            'sh-bnpl-js',
            SENHENG_CORE_URL . 'assets/js/sh-bnpl.js',
            ['jquery'],
            null,
            true
        );

        // Bootstrap CSS and JS
        wp_enqueue_style(
            'bootstrap-scoped-css',
            SENHENG_CORE_URL . 'assets/css/bootstrap-scoped.min.css'
        );
    }

    public static function index()
    {
        $page = isset($_GET['paged']) ? max((int)$_GET['paged'], 1) : 1;
        $pagination = PaymentMethod::paginate(10, $page);
        $brandWaives = PaymentMethod::getBrandWaive();
        include SENHENG_CORE_VIEW_PATH . 'installment/index.php';
    }

    public static function plans()
    {
        $method_id = intval($_GET['method_id']);
        $method = PaymentMethod::find($method_id);
        $plans = PaymentMethod::getPaymentPlans($method_id);
        include SENHENG_CORE_VIEW_PATH . 'installment/plans.php';
    }

    public static function payment_method_settings()
    {
        if (isset($_GET['enable']) && check_admin_referer('enable_method')) {
            $method_id = intval($_GET['enable']);
            PaymentMethod::updateStatus($method_id, 'active');
            wp_redirect(admin_url('admin.php?page=senheng-payment-settings'));
            exit;
        }

        if (isset($_GET['disable']) && check_admin_referer('disable_method')) {
            $method_id = intval($_GET['disable']);
            PaymentMethod::updateStatus($method_id, 'inactive');
            wp_redirect(admin_url('admin.php?page=senheng-payment-settings'));
            exit;
        }

        if (isset($_GET['delete']) && check_admin_referer('delete_method')) {
            $method_id = intval($_GET['delete']);
            PaymentMethod::delete($method_id);
            wp_redirect(admin_url('admin.php?page=senheng-payment-settings'));
            exit;
        }
    }

    public static function createPaymentMethod()
    {
        $arr_post = $_POST;

        // Validate required fields
        $rules = [
            'name' => ['required', 'string'],
        ];

        $errors = validate($arr_post, $rules);

        if (!empty($errors)) {
            wp_send_json_error(['errors' => $errors]);
            return;
        }

        $data = [
            'name' => sanitize_text_field($arr_post['name']),
            'status' => isset($arr_post['status']) ? sanitize_text_field($arr_post['status']) : 'active',
            'ipay88_id' => isset($arr_post['ipay88_id']) && !empty($arr_post['ipay88_id']) ? intval($arr_post['ipay88_id']) : null,
        ];

        // For editing existing payment method
        if (isset($arr_post['method_id']) && !empty($arr_post['method_id'])) {
            $data['id'] = intval($arr_post['method_id']);
            
            // Check for duplicate ipay88_id (excluding current record)
            if (!empty($data['ipay88_id'])) {
                $existing = PaymentMethod::findByIpay88Id($data['ipay88_id'], $data['id']);
                if ($existing) {
                    wp_send_json_error(['message' => 'iPay88 Payment ID "' . $data['ipay88_id'] . '" already exists for "' . $existing->name . '".']);
                    return;
                }
            }
            
            $update = PaymentMethod::update($data);
            if ($update === false) {
                wp_send_json_error(['message' => 'Failed to update payment method.']);
                return;
            }
            wp_send_json_success(['message' => 'Payment method updated successfully']);
            return;
        }

        // Check for duplicate ipay88_id before creating
        if (!empty($data['ipay88_id'])) {
            $existing = PaymentMethod::findByIpay88Id($data['ipay88_id']);
            if ($existing) {
                wp_send_json_error(['message' => 'iPay88 Payment ID "' . $data['ipay88_id'] . '" already exists for "' . $existing->name . '".']);
                return;
            }
        }

        // Create new payment method
        $insert_id = PaymentMethod::create($data);
        if ($insert_id === false) {
            wp_send_json_error(['message' => 'Failed to create payment method.']);
            return;
        }

        wp_send_json_success([
            'message' => 'Payment method created successfully',
            'method_id' => $insert_id
        ]);
    }

    public static function createPaymentPlan()
    {
        $arr_post = $_POST;
        // Validate required fields
        $rules = [
            'months'              => ['required', 'integer'],
            'min_amount'          => ['required', 'numeric'],
            'charge_percent'      => ['required', 'numeric'],
            'charge_rm'           => ['required', 'numeric'],
            'cost_share_merchant' => ['required', 'numeric'],
            'cost_share_operator' => ['required', 'numeric'],
            'method_id'           => ['required', 'integer']
        ];

        if ($arr_post['admin_fee'] === 'yes') {
            $rules['admin_fee_charges'] = ['required', 'numeric'];
        } else {
            $arr_post['admin_fee_charges'] = 0;
        }

        $errors = validate($arr_post, $rules);

        if (!empty($errors)) {
            wp_send_json_error(['errors' => $errors]);
            return;
        }

        $data = [
            'months' => intval($arr_post['months']),
            'min_amount' => floatval($arr_post['min_amount']),
            'charge_percent' => floatval($arr_post['charge_percent']),
            'charge_rm' => floatval($arr_post['charge_rm']),
            'cost_share_merchant' => floatval($arr_post['cost_share_merchant']),
            'cost_share_operator' => floatval($arr_post['cost_share_operator']),
            'status' => 'active',
            'method_id' => intval($arr_post['method_id']),
            'apply_admin_fee' => floatval($arr_post['admin_fee_charges'])
        ];

        // for editing existing plan
        if (isset($arr_post['plan_id'])) {
            $data['id'] = intval($arr_post['plan_id']);
            $update = PaymentPlan::update($data);
            if ($update === false) {
                wp_send_json_error(['message' => 'Failed to update payment plan.']);
                return;
            }
            wp_send_json_success(['message' => 'Payment plan updated successfully']);
            return;
        }

        PaymentPlan::create($data);
        wp_send_json_success(['message' => 'Payment plan created successfully']);
    }

    public static function payment_plan_method_settings()
    {
        if (isset($_GET['enable']) && check_admin_referer('enable_method')) {
            $plan_id = intval($_GET['enable']);
            PaymentPlan::updatePlanStatus($plan_id, 'active');
            wp_redirect(admin_url('admin.php?page=senheng-payment-plans&method_id=' . $_GET['method_id']));
            exit;
        }

        if (isset($_GET['disable']) && check_admin_referer('disable_method')) {
            $plan_id = intval($_GET['disable']);
            PaymentPlan::updatePlanStatus($plan_id, 'inactive');
            wp_redirect(admin_url('admin.php?page=senheng-payment-plans&method_id=' . $_GET['method_id']));
            exit;
        }

        if (isset($_GET['delete']) && check_admin_referer('delete_method')) {
            $plan_id = intval($_GET['delete']);
            PaymentPlan::deletePlan($plan_id);
            wp_redirect(admin_url('admin.php?page=senheng-payment-plans&method_id=' . $_GET['method_id']));
            exit;
        }
    }

    public static function payment_plan_front_end()
    {
        global $post;

        $product_id = $post->ID;
        $product = wc_get_product($product_id);
        if (!$product) {
            return;
        }

        $product_type = $product->get_type(); // 'simple', 'variable', etc.

        $data = [];
        $paymentPlans = PaymentMethod::getPaymentMethodPlans();
        if (empty($paymentPlans)) {
            return;
        }

        $bank_icons = [
            'Public Bank EPP (Instalment Payment)' => 'iPay88%20_%20banklogo/pb_bank.jpg',
            'Maybank EzyPay (Visa/Mastercard Instalment Payment)' => 'maybank.jpg',
            'Maybank EzyPay (AMEX Instalment Payment)' => 'maybank.jpg',
            'HSBC (Instalment Payment)' => 'HSBC-Logo.png',
            'CIMB Easy Pay (Instalment Payment)' => 'iPay88%20_%20banklogo/cimb.jpg',
            'Hong Leong Bank EPP-MIGS (Instalment Payment)' => 'iPay88%20_%20banklogo/hong_leong_connect.jpg',
            'Hong Leong Bank EPP-MPGS (Instalment Payment)' => 'iPay88%20_%20banklogo/hong_leong_connect.jpg',
            'OCBC Instalment' => 'ocbc.png',
            'RHB (Instalment Payment)' => 'iPay88%20_%20banklogo/rhb_now.jpg',
            'Ambank EPP' => 'ambank.png',
            'Standard Chartered Bank Instalment' => 'iPay88%20_%20banklogo/standard_chartered.jpg',
            'Atome' => 'atome.png',
            'GrabPay BNPL' => 'GrabPay.png',
        ];

        $icon_base_url = 'https://senheng-prod.oss-ap-southeast-3.aliyuncs.com/payment_methods/';


        foreach ($paymentPlans as $key => $plan) {
            $mechant_id = $plan->id;
            $merchant_name = $plan->name;

            if (!isset($data[$mechant_id])) {
                $data[$mechant_id] = [
                    'id' => $mechant_id,
                    'name' => $merchant_name,
                    'icon' => $icon_base_url . $bank_icons[$merchant_name],
                    'plans' => [],
                ];
            }

            $data[$mechant_id]['plans'][] = [
                'months' => $plan->months,
                'min_amount' => $plan->min_amount,
                'charge_rm' => $plan->charge_rm,
                'apply_admin_fee' => $plan->apply_admin_fee,
            ];
        }

        //reset data keys to be sequential
        $data = array_values($data);

        include SENHENG_CORE_VIEW_PATH . 'installment/modal.php';

        wp_localize_script('sh-bnpl-js', 'shBnplData', [
            'paymentPlans' => $data,
            'productType'  => $product_type,
            'parentPrice' => $product->get_sale_price() ?: $product->get_regular_price(),
        ]);
    }

    public static function senheng_exclude_brands()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'c_admin_fee_waivers';
        $brandsRaw = $_POST['brands'] ?? '';

        $brands = is_array($brandsRaw) ? $brandsRaw : explode(',', (string)$brandsRaw);

        $brands = array_map('trim', $brands);
        $brands = array_filter($brands, fn($b) => $b !== '');
        $brands = array_map('sanitize_title', $brands);
        $brands = array_unique($brands);

        if (empty($brands)) {
            wp_send_json_error(['message' => 'No brands provided.']);
        }

        $now = current_time('mysql');
        $values = [];
        foreach ($brands as $brand) {
            $values[] = $wpdb->prepare('(%s, %s)', $brand, $now);
        }

        $delete_result = $wpdb->query("DELETE FROM {$table}");

        $sql = "INSERT IGNORE INTO {$table} (brand_slug, created_at) VALUES " . implode(',', $values);
        $result = $wpdb->query($sql);

        if ($result === false) {
            wp_send_json_error(['message' => 'DB error', 'error' => $wpdb->last_error]);
        }

        wp_send_json_success([
            'message' => "Inserted {$result} brand(s).",
            'brands'  => array_values($brands),
        ]);
    }
}
