<?php

// app/Models/PaymentMethod.php
class PaymentMethod
{
    public static function all()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}c_payment_methods");
    }

    public static function paginate($perPage = 10, $currentPage = 1)
    {
        global $wpdb;

        $offset = ($currentPage - 1) * $perPage;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}c_payment_methods LIMIT %d OFFSET %d",
            $perPage,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}c_payment_methods");

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public static function find($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}c_payment_methods WHERE id = %d",
            $id
        ));
    }

    public static function updateStatus($id, $status)
    {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}c_payment_methods",
            ['status' => $status],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    public static function delete($id)
    {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}c_payment_methods", ['id' => $id], ['%d']);
    }

    public static function getPaymentPlans($method_id)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}c_payment_plans WHERE method_id = %d ORDER BY months ASC",
            $method_id
        ));
    }

    public static function getPaymentMethodPlans()
    {
        global $wpdb;
        return $wpdb->get_results("
            SELECT pm.id, pm.name, pp.months, pp.min_amount, pp.charge_rm, pp.apply_admin_fee, pm.ipay88_id
            FROM {$wpdb->prefix}c_payment_methods pm 
            JOIN {$wpdb->prefix}c_payment_plans pp ON pm.id = pp.method_id 
            WHERE pm.status = 'active' AND pp.status = 'active'
            ORDER BY 
            pm.id, 
            pp.months ASC
        ");
    }

    public static function getPaymentMethodPlansByAmount($amount)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("
            SELECT pm.id, pm.name, pp.months, pp.min_amount, pp.charge_rm, pp.apply_admin_fee
            FROM {$wpdb->prefix}c_payment_methods pm 
            JOIN {$wpdb->prefix}c_payment_plans pp ON pm.id = pp.method_id 
            WHERE pm.status = 'active' AND pp.status = 'active' AND pp.min_amount <= %f
            ORDER BY 
            pm.id, 
            pp.months ASC
        ", $amount));
    }

    public static function getBrandWaive()
    {
        global $wpdb;
        return $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}c_admin_fee_waivers
        ", ARRAY_A);
    }
}
