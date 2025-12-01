<?php

class PaymentTableSeeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function __construct()
    {
        $this->run();
    }

    public function run()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'c_payment_methods';

        $payment_methods = [
            'Public Bank EPP (Instalment Payment)' => 'active',
            'Maybank EzyPay (Visa/Mastercard Instalment Payment)' => 'active',
            'Maybank EzyPay (AMEX Instalment Payment)' => 'active',
            'HSBC (Instalment Payment)' => 'active',
            'CIMB Easy Pay (Instalment Payment)' => 'active',
            'Hong Leong Bank EPP-MIGS (Instalment Payment)' => 'active',
            'OCBC Instalment' => 'inactive',
            'Hong Leong Bank EPP-MPGS (Instalment Payment)' => 'inactive',
            'RHB (Instalment Payment)' => 'inactive',
            'Ambank EPP' => 'inactive',
            'Standard Chartered Bank Instalment' => 'inactive',
            'Atome' => 'active',
            'GrabPay BNPL' => 'active',
        ];

        $current_time = current_time('mysql');

        foreach ($payment_methods as $name => $status) {
            $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE name = %s",
                $name
            )
            );
            if (!$exists) {
            $wpdb->insert($table, [
                'name'       => $name,
                'status'     => $status,
                'created_at' => $current_time,
                'updated_at' => $current_time,
            ]);
            }
        }
    }
}
