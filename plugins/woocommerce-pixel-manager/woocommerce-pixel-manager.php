<?php
/**
 * Plugin Name:          Pixel Manager Pro for WooCommerce
 * Description:          Visitor and conversion value tracking for WooCommerce. Highly optimized for data accuracy.
 * Author:               SweetCode
 * Plugin URI:           https://sweetcode.com/plugins/pmw/
 * Author URI:           https://woocommerce.com/vendor/sweetcode/
 * Developer:            SweetCode
 * Developer URI:        https://sweetcode.com
 * Text Domain:          woocommerce-google-adwords-conversion-tracking-tag
 * Domain path:          /languages
 * Version:              1.54.1
 *
 * WC requires at least: 3.7
 * WC tested up to:      10.2
 *
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @fs_premium_only /includes/pixels/class-pinterest-apic.php, /includes/pixels/class-tiktok-eapi.php, /includes/pixels/facebook/class-facebook-microdata.php, /includes/pixels/facebook/class-facebook-capi.php, /includes/pixels/class-vwo.php, /includes/pixels/class-optimizely.php, /includes/pixels/class-ab-tasty.php, /includes/pixels/google/class-google-mp-ga4.php, /js/public/wpm-public__premium_only.p1.min.js, /js/public/wpm-public__premium_only.p1.min.js.map, /includes/data/, /includes/admin/opportunities/pro/, /changelog-archive/changelog-archive-pro.txt, /js/public/pro
 * Woo: 18734000100772:676d930a45effbae15e3a023e452d2f6

 **/

defined('ABSPATH') || exit; // Exit if accessed directly

$pmw_version     = '1.54.1';
$plugin_basename = plugin_basename(__FILE__);

require_once 'pmw-loader.php';
