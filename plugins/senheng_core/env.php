<?php

// define('SENHENG_ENV', 'local');
if (defined('VIP_GO_APP_ENVIRONMENT')) {

    switch (VIP_GO_APP_ENVIRONMENT) {
        case 'production':
            define('SENHENG_ENV', 'production');
            break;

        case 'staging':
            define('SENHENG_ENV', 'local');
            break;

        default:
            define('SENHENG_ENV', 'local');
            break;
    }

} else {
    define('SENHENG_ENV', 'local');
}


define('WOO_STG_API_URL', 'https://app-dev.s-eco.com.my');
define('WOO_STG_CUSTOMER_KEY', '19');
define('WOO_STG_API_KEY', 'WooCommerce0Gcv8yjTJ0iNAHxDag0CowZlr2Pw');

define('SSO_STG_API_URL', 'https://sso.cloone.my');

define('WOO_API_URL', 'https://app.s-eco.com.my');
define('WOO_CUSTOMER_KEY', '19');
define('WOO_API_KEY', 'WooCommerce0Gcv8yjTJ0iNAHxDag0CowZlr2Pw');

define('SSO_API_URL', 'https://sso.senheng.com.my');
define('MINI_ORANGE_CUSTOMER_KEY', '1');
define('MINI_ORANGE_API_KEY', 'KSmfmeLDiSHMbBI97roS0eJNQ3b2kTSU');

define('BRANCH_IO_KEY', 'key_live_osviSsOvRa2mlOvzzgcZAcabyxatX09a');

define('INSIDER_PARTNER_NAME', 'senheng');
define('INSIDER_PARTNER_ID', '10011721');
define('INSIDER_CATALOG_TOKEN', 'INS.egdCQXgBxyWvsx3dv-hE.VCQPB8NxhQ361DrSNYNudcjUaZgOV93O2rNkIxHqDE8Y-nEEl7');
define('INSIDER_PARTNER_NAME_STG', 'senhengwoocommerceuat');
define('INSIDER_PARTNER_ID_STG', '10013122');
define('INSIDER_CATALOG_TOKEN_STG', 'INS.gNUfkkQJdVpeYA8LnLcE.pNomK6H8AS4MIoT-6lueqd60b4fddCpQf5HsNky40dhG327kfj');
define('INSIDER_BULK_FEED_TOKEN', 'c3d2e4f7-0a8b-4f1b-8a5c-6d9e2f1a0b7d');

define('IPAY88_MERCHANT_CODE_LIVE', 'M06028_S0004');
define('IPAY88_MERCHANT_KEY_LIVE', '98yHHg1N5l');
define('SECOND_IPAY88_MERCHANT_CODE_LIVE', 'M06028_S0008');
define('SECOND_IPAY88_MERCHANT_KEY_LIVE', 'hM8p7mfdQQ');

define('IPAY88_MERCHANT_CODE_STG', '');
define('IPAY88_MERCHANT_KEY_STG', '');
define('SECOND_IPAY88_MERCHANT_CODE_STG', '');
define('SECOND_IPAY88_MERCHANT_KEY_STG', '');