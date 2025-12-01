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

    <div class="mobi-title" role="heading" aria-level="1" style="margin-left: 40px;">Shopping Cart</div>


    <div class="mobi-right">
        <button class="mobi-icon-btn" onclick="if(navigator.share){navigator.share({title:document.title,url:location.href});}" aria-label="Share">
            <i class="fa fa-share" aria-hidden="true" style="width:24px;height:24px;font-size:24px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </button>

        <a class="mobi-icon-btn" aria-label="Support" href="/appRedirect::/customerSupport">
            <i class="fa fa-headphones" aria-hidden="true" style="width:24px;height:24px;font-size:24px;display:inline-flex;align-items:center;justify-content:center;"></i>
        </a>
    </div>
</div>