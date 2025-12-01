<?php

class MyAccountController
{
    public static function overide_account_details($template, $template_name, $template_path)
    {
        // if ($template_name === 'myaccount/my-account.php') {
        //     $plugin_template = SENHENG_CORE_VIEW_PATH . 'my-account/my-account.php';
        //     if (file_exists($plugin_template)) {
        //         return $plugin_template;
        //     }
        // }

        // if ($template_name === 'myaccount/dashboard.php') {
        //     $plugin_template = SENHENG_CORE_VIEW_PATH . 'my-account/dashboard-mobile.php';
        //     if (file_exists($plugin_template)) {
        //         return $plugin_template;
        //     }
        // }

        // keep your other overrides
        if ($template_name === 'myaccount/form-edit-account.php') {
            $t = SENHENG_CORE_VIEW_PATH . 'my-account/form-edit-account.php';
            if (file_exists($t)) return $t;
        }

        return $template;
    }
}
