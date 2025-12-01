<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class Admin
{
    public static function handleRequest()
    {
        // This script executes on plugin load if is_admin(), for POST requests or if the action, action 2 or pp_action REQUEST arguments are non-empty
        //

        if (!PWP::empty_POST()) {
            if (in_array(presspermitPluginPage(), ['presspermit-status-edit', 'presspermit-status-new'], true)) {
                add_action(
                    'wp_loaded', 
                    function()
                    {
                        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/StatusEdit.php');
                        StatusEdit::handleRequest();
                    }
                );
            }
        }

        if (!PWP::empty_REQUEST('action') || !PWP::empty_REQUEST('action2') || !PWP::empty_REQUEST('pp_action')) {
            if (!empty($_SERVER['REQUEST_URI']) && 
            (
                ((strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'page=presspermit-statuses')) || (!PWP::empty_REQUEST('wp_http_referer')) && (strpos(esc_url_raw(PWP::REQUEST_url('wp_http_referer')), 'page=presspermit-statuses')))
                || ((strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'page=presspermit-visibility-statuses')) || (!PWP::empty_REQUEST('wp_http_referer')) && (strpos(esc_url_raw(PWP::REQUEST_url('wp_http_referer')), 'page=presspermit-visibility-statuses')))
            )) {
                add_action(
                    'init', 
                    function()
                    {
                        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/Status.php');
                        Status::handleRequest();  // action-specific nonce checks
                    }
                    , 100000
                );
            }
        }
    }
}
