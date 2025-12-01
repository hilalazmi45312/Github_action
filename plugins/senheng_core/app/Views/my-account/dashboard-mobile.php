<?php
defined('ABSPATH') || exit;
$current_user = wp_get_current_user();

// Helpers / fallbacks
$orders_url   = wc_get_endpoint_url('orders');
$edit_acc_url = wc_get_endpoint_url('edit-account');
$edit_addr_url = wc_get_endpoint_url('edit-address');
$logout_url   = wc_logout_url();

$wishlist_url   = home_url('/wishlist/');
$wishlist_count = function_exists('count_wishlist') ? count_wishlist() : 0;

$viewed = ! empty($_COOKIE['woocommerce_recently_viewed'])
    ? array_filter(array_map('absint', explode('|', wp_unslash($_COOKIE['woocommerce_recently_viewed']))))
    : [];
$recently_count = count($viewed);
$recently_url   = apply_filters('sh_recently_viewed_url', home_url('/shop/?recently_viewed=1'));

// If you have a vouchers/coupons endpoint, change this; otherwise stub.
$vouchers_url   = apply_filters('sh_vouchers_url', $orders_url);
$vouchers_count = 0;

// Quick links by order status (tweak to your workflow)
$to_pay_url     = add_query_arg('status', 'pending',    $orders_url);
$to_ship_url    = add_query_arg('status', 'processing', $orders_url);
$to_receive_url = add_query_arg('status', 'completed',  $orders_url); // adjust if you use a “shipped” status
$to_review_url  = add_query_arg('status', 'completed',  $orders_url);

// Icons (replace with your own icons if needed)
$icon_base = SENHENG_CORE_ASSETS_URL . 'images/';
$to_pay_icon     = $icon_base . 'topay.png';
$to_ship_icon    = $icon_base . 'toship.png';
$to_receive_icon = $icon_base . 'toreceive.png';
$to_review_icon  = $icon_base . 'toreview.png';

// Simple page link helper for static pages
function sh_page_link($slug, $fallback = '#')
{
    $p = get_page_by_path($slug);
    return $p ? get_permalink($p) : $fallback;
}
?>
<style>
    /* --- Mobile-only styles --- */
    @media (max-width: 768px) {

        /* .whb-header-bottom {
            display: none !important;
        } */

        .wd-footer {
            display: none !important;
        }

        .sh-acc {
            font-family: inherit;
        }

        .sh-hero {
            background: #d60000;
            color: #fff;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-radius: 8px;
        }

        .sh-hero .sh-avatar img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            background: #fff;
        }

        .sh-hero .sh-name {
            font-weight: 600;
        }

        .sh-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 12px 0 16px;
        }

        .sh-stat {
            text-align: center;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 10px 6px;
            display: block;
            text-decoration: none;
            color: inherit;
        }

        .sh-stat .num {
            display: block;
            font-weight: 700;
            font-size: 16px;
        }

        .sh-stat .label {
            display: block;
            font-size: 12px;
            opacity: .8;
            margin-top: 2px;
        }

        .sh-section {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .sh-section .sh-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-top: 10px;
        }

        .sh-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-align: center;
            padding: 12px 6px;
            /* border: 1px solid #eee; */
            border-radius: 100px;
            text-decoration: none;
            color: inherit;
            font-size: 12px;
        }

        .sh-pill .ico {
            width: 50px;
            height: 50px;
            border: 1px solid #ddd;
            border-radius: 50%;
            display: inline-block;
        }

        .sh-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        .sh-title a {
            font-size: 12px;
            opacity: .8;
            text-decoration: none;
        }

        .sh-menu {
            list-style: none;
            padding: 0;
            margin: 0;
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
        }

        .sh-menu li {
            border-top: 1px solid #eee;
        }

        .sh-menu li:first-child {
            border-top: 0;
        }

        .sh-menu a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 12px;
            text-decoration: none;
            color: inherit;
        }

        .sh-menu .hint {
            opacity: .5;
            font-size: 12px;
        }
    }

    /* keep desktop as theme default; no styles above 768px */
</style>

<div class="sh-acc">
    <!-- Hero -->
    <div class="sh-hero">
        <div class="sh-avatar"><?php echo get_avatar($current_user->ID, 96); ?></div>
        <div class="sh-name"><?php echo esc_html($current_user->display_name); ?></div>
    </div>

    <!-- Stats -->
    <div class="sh-stats">
        <a class="sh-stat" href="<?php echo esc_url($wishlist_url); ?>">
            <span class="num"><?php echo (int) $wishlist_count; ?></span>
            <span class="label"><?php esc_html_e('My Wishlist', 'woocommerce'); ?></span>
        </a>
        <a class="sh-stat" href="<?php echo esc_url($recently_url); ?>">
            <span class="num"><?php echo (int) $recently_count; ?></span>
            <span class="label"><?php esc_html_e('Recently Viewed', 'woocommerce'); ?></span>
        </a>
        <a class="sh-stat" href="<?php echo esc_url($vouchers_url); ?>">
            <span class="num"><?php echo (int) $vouchers_count; ?></span>
            <span class="label"><?php esc_html_e('Vouchers', 'woocommerce'); ?></span>
        </a>
    </div>

    <!-- My Orders quick actions -->
    <div class="sh-section">
        <div class="sh-title">
            <span><?php esc_html_e('My Orders', 'woocommerce'); ?></span>
            <a href="<?php echo esc_url($orders_url); ?>"><?php esc_html_e('View All Orders ›', 'woocommerce'); ?></a>
        </div>
        <div class="sh-row">
            <a class="sh-pill" href="<?php echo esc_url($to_pay_url); ?>">
                <span class="ico" aria-hidden="true"><img src="<?php echo esc_url($to_pay_icon); ?>" alt="<?php esc_attr_e('To Pay', 'woocommerce'); ?>" /></span><span><?php esc_html_e('To Pay', 'woocommerce'); ?></span>
            </a>
            <a class="sh-pill" href="<?php echo esc_url($to_ship_url); ?>">
                <span class="ico" aria-hidden="true"><img src="<?php echo esc_url($to_ship_icon); ?>" alt="<?php esc_attr_e('To Ship', 'woocommerce'); ?>" /></span><span><?php esc_html_e('To Ship', 'woocommerce'); ?></span>
            </a>
            <a class="sh-pill" href="<?php echo esc_url($to_receive_url); ?>">
                <span class="ico" aria-hidden="true"><img src="<?php echo esc_url($to_receive_icon); ?>" alt="<?php esc_attr_e('To Receive', 'woocommerce'); ?>" /></span><span><?php esc_html_e('To Receive', 'woocommerce'); ?></span>
            </a>
            <a class="sh-pill" href="<?php echo esc_url($to_review_url); ?>">
                <span class="ico" aria-hidden="true"><img src="<?php echo esc_url($to_review_icon); ?>" alt="<?php esc_attr_e('To Review', 'woocommerce'); ?>" /></span><span><?php esc_html_e('To Review', 'woocommerce'); ?></span>
            </a>
        </div>
    </div>

    <!-- Menu -->
    <ul class="sh-menu">
        <li><a href="<?php echo esc_url($edit_acc_url); ?>"><span><?php esc_html_e('Account Information', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
        <li><a href="<?php echo esc_url($edit_addr_url); ?>"><span><?php esc_html_e('My Address', 'woocommerce'); ?></span><span class="hint">›</span></a></li>

        <!-- Custom pages – change the slugs to your pages -->
        <li><a href="<?php echo esc_url(sh_page_link('membership')); ?>"><span><?php esc_html_e('My Membership', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
        <li><a href="<?php echo esc_url(sh_page_link('senheng-affiliate-program')); ?>"><span><?php esc_html_e('Senheng Affiliate Program', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
        <li><a href="<?php echo esc_url(sh_page_link('privacy-policy')); ?>"><span><?php esc_html_e('Privacy Policy', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
        <li><a href="<?php echo esc_url(sh_page_link('faq')); ?>"><span><?php esc_html_e('FAQ', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
        <li><a href="<?php echo esc_url(sh_page_link('terms-of-use')); ?>"><span><?php esc_html_e('Terms Of Use', 'woocommerce'); ?></span><span class="hint">›</span></a></li>

        <li><a href="<?php echo esc_url($logout_url); ?>"><span><?php esc_html_e('Log Out', 'woocommerce'); ?></span><span class="hint">›</span></a></li>
    </ul>

    <?php
    // keep core hooks firing for extensions that rely on them
    // do_action('woocommerce_account_dashboard');
    // do_action('woocommerce_before_my_account');
    // do_action('woocommerce_after_my_account');
    ?>
</div>