<?php
namespace PublishPress\StatusCapabilities;

class StatusSave
{
    public static function save($status, $new = false)
    {
        check_admin_referer('edit-status');
        
        if (isset($_REQUEST['status_capability_status'])) {
            \PublishPress\StatusCapabilities::updateCapabilityStatus($status, sanitize_key($_REQUEST['status_capability_status']));
        }

        // previous versions of PublishPress Statuses handled this status_caps submission directly
        if (defined('PUBLISHPRESS_STATUSES_VERSION') && version_compare(PUBLISHPRESS_STATUSES_VERSION, '1.0.1', '>')) {
            if (isset($_REQUEST['status_caps'])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                foreach ($_REQUEST['status_caps'] as $role_name => $set_status_caps) { // array elements sanitized below
                    $role_name = sanitize_key($role_name);
                    $set_status_caps = array_map('boolval', $set_status_caps);

                    if (!\PublishPress_Functions::isEditableRole($role_name)) {
                        continue;
                    }

                    $role = get_role($role_name);

                    if ($add_caps = array_diff_key(
                        array_filter($set_status_caps),
                        array_filter($role->capabilities)
                    )) {
                        foreach (array_keys($add_caps) as $cap_name) {
                            $cap_name = sanitize_key($cap_name);
                            $role->add_cap($cap_name);
                        }
                    }

                    $set_false_status_caps = array_diff_key($set_status_caps, array_filter($set_status_caps));

                    foreach(array_keys($set_false_status_caps) as $cap_name) {
                        $cap_name = sanitize_key($cap_name);

                        if (!empty($role->capabilities[$cap_name])) {
                            $role->remove_cap($cap_name);
                        }
                    }
                }
            }
        }
    }

    public static function handleAjaxToggleStatusPostAccess() {
        // Check for proper nonce
        check_ajax_referer('pp-permissions-statuses-nonce');

        // Only allow users with the proper caps
        if (!current_user_can('manage_options') && !current_user_can('pp_manage_statuses')) {
            wp_die(esc_html__('Sorry, you do not have permission to edit custom statuses.', 'publishpress-statuses'));
        }

        $status = !empty($_REQUEST['name']) ? sanitize_key($_REQUEST['name']) : '';

        $params = [];

        $all_capability_statuses = get_option('presspermit_status_capability_status');

        // existing capability status
        $capability_status = (!empty($all_capability_statuses[$status])) ? $all_capability_statuses[$status] : '';

        // new capability status
        $capability_status = ($capability_status == $status) ? '' : $status;

        $return = \PublishPress\StatusCapabilities::updateCapabilityStatus($status, $capability_status);
        if (is_wp_error($return)) {
            self::printAjaxResponse('error', '', [], $params);
            exit;
        }

        $message = '';

        if ($capability_status) {
            if ($capability_status == $status) {
                $display = __('Custom', 'publishpress-status-capabilities');
            } else {
                $cap_status_obj = get_post_status_object($capability_status);
                $cap_status_caption = (!empty($cap_status_obj) && !empty($cap_status_obj->label)) ? $cap_status_obj->label : $capability_status;
                $display = sprintf(__('same as %s', 'publishpress-status-capabilities'), $cap_status_caption);
            }
        } else {
            $display = __('Standard', 'publishpress-status-capabilities');
        }

        $data = [
            'statusName' => $status,
            'display' => $display
        ];

        self::printAjaxResponse('success', '', $data, $params);
        exit;
    }

    private static function printAjaxResponse($request_status, $message = '', $data = null, $params = null)
    {
        header('Content-type: application/json;');

        $result = [
            'status'  => $request_status,
            'message' => $message,
        ];

        if (!is_null($data)) {
            $result['data'] = $data;
        }

        if (!is_null($params)) {
            $result['params'] = $params;
        }

        echo wp_json_encode($result);

        exit;
    }
}
