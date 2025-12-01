<?php

// app/Models/PaymentPlan.php
class PaymentPlan
{
    public static function all()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}c_payment_plans ORDER BY months ASC");
    }

    public static function find($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}c_payment_plans WHERE id = %d",
            $id
        ));
    }

    public static function create($data)
    {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}c_payment_plans", $data);
    }

    public static function update($data)
    {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}c_payment_plans",
            $data,
            ['id' => $data['id']]
        );
    }

    public static function updatePlanStatus($id, $status)
    {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}c_payment_plans",
            ['status' => $status],
            ['id' => $id]
        );
    }

    public static function deletePlan($id)
    {
        global $wpdb;
        return $wpdb->delete(
            "{$wpdb->prefix}c_payment_plans",
            ['id' => $id]
        );
    }
}