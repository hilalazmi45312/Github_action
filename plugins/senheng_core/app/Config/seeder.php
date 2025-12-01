<?php

class Seeder
{
    public static function run()
    {
        (new PaymentTableSeeder())->run();
    }
}
