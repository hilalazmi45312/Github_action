<?php
/* Mobile Header */
?>
<link rel="stylesheet" href="<?php echo esc_url(SENHENG_CORE_ASSETS_URL . '/css/header/style.css'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<div class="mobi-topbar" role="banner" aria-label="Mobile site header">
    <div class="mobi-left">
        <button class="mobi-icon-btn" onclick="history.back()" aria-label="Go back">
            <i class="fa fa-chevron-left" aria-hidden="true" style="width:24px;height:24px;font-size:18px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </button>

        <!-- <a class="mobi-icon-btn" href="<?php echo esc_url(home_url('/')); ?>" aria-label="Home">
            <i class="fa fa-home" aria-hidden="true" style="width:24px;height:24px;font-size:24px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </a> -->
    </div>

    <div class="mobi-title" role="heading" aria-level="1" style="margin-left: 40px;">Product Detail</div>


    <div class="mobi-right">
        <button id="impact-share-button" class="mobi-icon-btn" aria-label="Share">
            <i class="fa fa-share" aria-hidden="true" style="width:24px;height:24px;font-size:24px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </button>

        <?php if (class_exists('WooCommerce')) :
            $count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
        endif; ?>

        <a class="mobi-icon-btn mobi-cart"
            href="<?php echo function_exists('wc_get_cart_url') ? esc_url(wc_get_cart_url()) : esc_url(home_url('/cart')); ?>"
            aria-label="Cart">
            <svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2zM7.16 14h9.45c.75 0 1.4-.41 1.73-1.03l3.58-6.49A1 1 0 0 0 21 5H6.21l-.94-2H2v2h2l3.6 7.59-1.35 2.45C5.52 15.37 6.2 16 7 16h12v-2H7.42l.74-1.34z" fill="currentColor" />
            </svg>

            <span class="mobi-cart-badge" style="<?php echo ($count > 0) ? '' : 'display:none;'; ?>"
                aria-live="polite"
                aria-atomic="true"
                aria-label="<?php echo esc_attr($count); ?> items in cart">
                <?php echo (int) $count; ?>
            </span>
        </a>

        <!-- <button class="mobi-icon-btn" aria-label="More options">
            <i class="fa fa-ellipsis-v" aria-hidden="true" style="width:24px;height:24px;font-size:24px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </button> -->
    </div>
</div>

<script src="<?php echo esc_url(SENHENG_CORE_ASSETS_URL . '/js/header/script.js'); ?>"></script>
