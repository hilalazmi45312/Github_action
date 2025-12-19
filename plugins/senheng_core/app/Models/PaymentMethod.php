<?php

// app/Models/PaymentMethod.php
class PaymentMethod
{
    /**
     * Ensure all required columns exist in the table
     * This handles schema updates without separate migration files
     */
    public static function ensureTableColumns()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'c_payment_methods';

        // Check if ipay88_id column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'ipay88_id'");

        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN ipay88_id INT DEFAULT NULL AFTER status");
        }
    }

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

    public static function findByIpay88Id($ipay88_id, $exclude_id = null)
    {
        global $wpdb;

        if ($exclude_id) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}c_payment_methods WHERE ipay88_id = %d AND id != %d",
                $ipay88_id,
                $exclude_id
            ));
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}c_payment_methods WHERE ipay88_id = %d",
            $ipay88_id
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

    public static function create($data)
    {
        global $wpdb;
        $result = $wpdb->insert(
            "{$wpdb->prefix}c_payment_methods",
            [
                'name' => $data['name'],
                'status' => $data['status'] ?? 'active',
                'ipay88_id' => isset($data['ipay88_id']) ? intval($data['ipay88_id']) : null,
            ],
            ['%s', '%s', '%d']
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    public static function update($data)
    {
        global $wpdb;
        return $wpdb->update(
            "{$wpdb->prefix}c_payment_methods",
            [
                'name' => $data['name'],
                'status' => $data['status'] ?? 'active',
                'ipay88_id' => isset($data['ipay88_id']) ? intval($data['ipay88_id']) : null,
            ],
            ['id' => $data['id']],
            ['%s', '%s', '%d'],
            ['%d']
        );
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

    public static function getAdminFeePaymentMethods($ipay88_id, $payment_plan)
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "
        SELECT COALESCE(pp.apply_admin_fee, 0)
        FROM {$wpdb->prefix}c_payment_methods pm
        LEFT JOIN {$wpdb->prefix}c_payment_plans pp
            ON pp.method_id = pm.id
           AND pp.months = %d
           AND pp.status = 'active'
        WHERE pm.ipay88_id = %d
          AND pm.status = 'active'
        LIMIT 1
        ",
            $payment_plan,
            $ipay88_id
        );

        return (int) $wpdb->get_var($sql);
    }
}
