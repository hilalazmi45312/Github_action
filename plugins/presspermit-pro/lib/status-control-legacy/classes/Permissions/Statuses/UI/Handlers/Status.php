<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class Status
{
    public static function handleRequest()
    {
        $url = $referer = $redirect = $update = '';

        $pp = presspermit();

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusHelper.php');
        \PublishPress\Permissions\Statuses\UI\StatusHelper::getUrlProperties($url, $referer, $redirect);

        $action = PWP::is_REQUEST('action') ? PWP::REQUEST_key('action') : '';
        if (!$action) {
            $action = PWP::is_REQUEST('pp_action') ? PWP::REQUEST_key('pp_action') : '';
        }

        $attribute = 'post_status';
        $attrib_type = PWP::REQUEST_key('attrib_type');

        switch ($action) {

            case 'dodelete':
                check_admin_referer('delete-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(esc_html__('You are not permitted to do that.', 'presspermit-pro'));

                if (PWP::empty_REQUEST('pp_conditions') && PWP::empty_REQUEST('status')) {
                    wp_redirect($redirect);
                    exit();
                }

                if (!empty($_REQUEST['pp_conditions'])) {
                    $conds = array_map('sanitize_key', (array) $_REQUEST['pp_conditions']);
                } else {
                    $conds = (!PWP::empty_REQUEST('status')) ? (array) PWP::REQUEST_key('status') : []; 
                }

                $update = 'del';
                $delete_conds = [];

                foreach ((array)$conds as $cond) {
                    $delete_conds[$cond] = true;
                }

                if (!$delete_conds)
                    wp_die(esc_html__('You can&#8217;t delete that status.', 'presspermit-pro'));

                $conds = (array)get_option("presspermit_custom_conditions_{$attribute}");

                $conds = array_diff_key($conds, $delete_conds);

                update_option("presspermit_custom_conditions_{$attribute}", $conds);

                // PublishPress integration
                if (taxonomy_exists('post_status') && !defined('PP_DISABLE_EF_STATUS_SYNC')) {
                    foreach (array_keys($delete_conds) as $status) {
                        if (!in_array($status, ['draft', 'pending', 'pitch'])) {
                            if ($term = get_term_by('slug', $status, 'post_status'))
                                wp_delete_term($term->term_id, 'post_status');
                        }
                    }
                }

                do_action('presspermit_trigger_cache_flush');

                $redirect = add_query_arg(['delete_count' => count($delete_conds), 'update' => $update, 'pp_attribute' => $attribute, 'attrib_type' => $attrib_type], $redirect);
                wp_redirect($redirect);
                exit();

                break;

            case 'delete' :
                check_admin_referer('bulk-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(esc_html__('You are not permitted to do that.', 'presspermit-pro'));

                if (!empty($_REQUEST['pp_conditions'])) {
                    $conds = array_map('sanitize_key', (array) $_REQUEST['pp_conditions']);
                    $http_referer = (!PWP::empty_REQUEST('wp_http_referer')) ? esc_url_raw(PWP::REQUEST_url('wp_http_referer')) : '';
                    $redirect = add_query_arg(['pp_action' => 'bulkdelete', 'wp_http_referer' => $http_referer, 'pp_conditions' => $conds], $redirect);
                    wp_redirect($redirect);
                    exit();
                }

                break;

            case 'disable' :
            case 'enable' :
                check_admin_referer('bulk-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(esc_html__('You are not permitted to do that.', 'presspermit-pro'));

                if (!$status = PWP::REQUEST_key('status')) {
                    break;
                }

                $private_stati = PWP::getPostStatuses(['private' => true]);

                if (in_array($status, ['pending', 'future']) || !in_array($status, $private_stati, true)) {
                    $pp->updateOption("custom_{$status}_caps", ('enable' == $action));
                }

                // All privacy statuses, as well as moderation statuses defined by PressPermit, can be fully disabled
                if (in_array($status, $private_stati, true) || ('approved' == $status)) {
                    $disabled_conditions = (array)$pp->getOption("disabled_{$attribute}_conditions");
                    $disabled_conditions = array_filter($disabled_conditions);

                    if ('enable' == $action) {
                        $disabled_conditions = array_diff_key($disabled_conditions, [$status => true]);
                    } else {
                        $disabled_conditions[$status] = true;
                    }

                    $pp->updateOption("disabled_{$attribute}_conditions", $disabled_conditions);
                }

                // If capability status was set to nullstring ("Default Capabilities), also clear that 
                // (resulting in default of own capability status) 
                if ('enable' == $action) {
                    if ($capability_status = $pp->getOption('status_capability_status')) {
                        if (isset($capability_status[$status]) 
                        && ('' === $capability_status[$status])
                        ) {
                            unset($capability_status[$status]);
                            $pp->updateOption("status_capability_status", $capability_status);
                        }
                    }
                }

                do_action('presspermit_trigger_cache_flush');

                $redirect = add_query_arg(['update' => 'edit', 'pp_attribute' => $attribute, 'attrib_type' => $attrib_type], $redirect);
                wp_redirect($redirect);
                exit();

                break;

            default:
                if (!PWP::empty_GET('wp_http_referer') && PWP::SERVER_url('REQUEST_URI')) {
                    wp_redirect(remove_query_arg(['wp_http_referer', '_wpnonce'], stripslashes(esc_url_raw(PWP::SERVER_url('REQUEST_URI')))));
                    exit;
                }
        } // end switch
    }
}
