<?php

class SharePopupController
{
    public static function enqueueAssets()
    {
        wp_enqueue_style(
            'senheng-share-popup',
            SENHENG_CORE_URL . 'assets/css/share-popup.css',
            [],
            '1.0.0'
        );

        // Always enqueue the impact.js script for share popup functionality (all users)
        wp_enqueue_script(
            'impact-share-script',
            SENHENG_CORE_URL . 'assets/js/impact.js',
            ['jquery'],
            '1.0',
            true
        );
    }

    public static function renderModal()
    {
        // Load the modal markup from view template
        $view = SENHENG_CORE_VIEW_PATH . 'share-popup/popup.php';
        if (file_exists($view)) {
            include $view;
        }
    }

    public static function injectHeaderShareButton()
    {
        if (!function_exists('is_product') || !is_product()) {
            return;
        }

        // Load the header button markup from view template
        // $view = SENHENG_CORE_VIEW_PATH . 'share-popup/header-button.php';
        // if (file_exists($view)) {
        //     include $view;
        // }
    }
}


