<?php
namespace PublishPress\Permissions;

class Statuses {
    private static $instance = null;
    private static $attributes = null;

    public static function instance() {
        if ( is_null(self::$instance) ) {
            self::$instance = new Statuses();
        }

        return self::$instance;
    }

    private function __construct()
    {
    }

    public static function defineClassAliases() {
        class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\PPS');
        class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\PPS');
        
        if (is_admin()) {
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\UI\PPS');
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\UI\Handlers\PPS');
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\UI\Dashboard\PPS');
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\UI\Gutenberg\PPS');
        }

        if (defined('PUBLISHPRESS_REVISIONS_VERSION')) {
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\Revisions\PPS');

        } elseif (defined('REVISIONARY_VERSION')) {
            class_alias('\PublishPress\Permissions\Statuses', '\PublishPress\Permissions\Statuses\Revisionary\PPS');
        }
    }

    public static function getCustomStatuses($args = [])
    {
        global $wp_post_statuses;

        $defaults = ['post_type' => '', 'ignore_moderation_statuses' => false, 'ignore_private_stati' => false];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $post_type = sanitize_key($post_type);

        $custom_stati = [];

        foreach ($wp_post_statuses as $status => $st) {
            if (
            ((!$ignore_moderation_statuses || empty($st->moderation)) && empty($st->_builtin) && !in_array($status, ['pending', 'draft', 'future'])) 
            || ((!$ignore_private_stati || empty($st->private)) && ('private' != $status))
            ) {
                $custom_stati [] = $status;
            }
        }

        return $custom_stati;
    }

    public static function customStatiUsed($args = [])
    {
        global $wpdb, $wp_post_statuses;

        $defaults = ['post_type' => '', 'ignore_moderation_statuses' => false, 'ignore_private_stati' => false, 'ignore_status' => []];
        $args = array_merge($defaults, $args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $status_args = [];

        if (!empty($ignore_moderation_statuses)) {
            $status_args['moderation'] = false;
            $status_args['for_revision'] = false;
        }

        if (!empty($ignore_private_stati)) {
            $status_args['private'] = false;
        }

        $custom_stati = \PublishPress_Statuses::getCustomStatuses($status_args, 'names');

        if (!empty($args['ignore_status'])) {
            $custom_stati = array_diff($custom_stati, (array)$args['ignore_status']);
        }

        $status_csv = implode("','", array_map('sanitize_key', $custom_stati));

        if ($post_type) {
            // Direct query of posts table for plugin admin query

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $post_exists = (int)$wpdb->get_var(
                $wpdb->prepare(
                    "SELECT ID FROM $wpdb->posts WHERE post_status IN ('$status_csv') AND post_type = %s LIMIT 1",  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    $post_type
                )
            );
        } else {
            // Direct query of posts table for plugin admin query
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $post_exists = (int)$wpdb->get_var(
                "SELECT ID FROM $wpdb->posts WHERE post_status IN ('$status_csv') LIMIT 1"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            );
        }

        return $post_exists;
    }

    public static function privacyStatusesDisabled() {
        // This replaces constant PPS_NATIVE_CUSTOM_STATI_DISABLED (previously defined dynamically in StatusesHooks::actRegistrations())
        return !presspermit()->getOption('privacy_statuses_enabled');
    }

    public static function customPrivacyEditCapsEnabled() {
        return (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS')) ? PPS_CUSTOM_PRIVACY_EDIT_CAPS : presspermit()->getOption('custom_privacy_edit_caps');
    }

    public static function customStatusesEnabled($post_type = '', $ignore_status = [])
    {
        global $wp_post_statuses;

        $ignore_status = (array)$ignore_status;

        foreach ($wp_post_statuses as $status => $st) {
            if (!in_array($status, $ignore_status, true) && ((!empty($st->moderation) && !in_array($status, ['private', 'future'])) 
            || (!empty($st->private) && ('private' != $status))) && empty($st->_builtin)) {

                if (!$post_type || !isset($st->post_type) 
                || (is_array($st->post_type) && (!$st->post_type || in_array($post_type, $st->post_type)))
                ) {    
                    return true;
                }
            }
        }

        return false;
    }

    public static function defaultStatusOrder()
    {
        // Prior implementation's status order values
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        return [
            'draft' => 0,
            'pitch' => 2,
            'assigned' => 5,
            'in-progress' => 7,
            'pending' => 10,
            'pending-review' => 10,  // @todo
            'approved' => 18,
        ];*/

        return [
            'draft' => 0,
            'pitch' => 100,
            'assigned' => 200,
            'in-progress' => 300,
            'pending' => 500,
            'pending-review' => 500,  // @todo
            'approved' => 600,
        ];
    }

    public static function attributes()
    {
        if ( is_null(self::$attributes) ) {
            // \PublishPress\StatusCapabilities filters instance class based on our hook
            self::$attributes = \PublishPress\StatusCapabilities::instance();
        }

        return self::$attributes;
    }

    public static function registerPrivacyConditions($statuses)
    {
        global $wp_post_statuses;

        if (empty($statuses)) {
            $statuses = $wp_post_statuses;
        }

        $suppress_edit_caps = !PPS::customPrivacyEditCapsEnabled();

        // register each custom post status as an attribute condition with mapped caps
        foreach ($statuses as $status => $status_obj) {
            if (!empty($status_obj->private)) {
                if ('private' == $status) {
                    if (!$suppress_edit_caps && defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                        $metacap_map = ['copy_post' => "copy_private_posts"];
                        $cap_map = [];
                    } else {
                        continue;
                    }
                } else {
                    \PublishPress\StatusCapabilities::registerCondition('force_visibility', $status, ['label' => $status_obj->label]);

                    if (!empty($wp_post_statuses[$status])) {
                        $wp_post_statuses[$status]->capability_status = $status;
                    }

                    $metacap_map = ($suppress_edit_caps) 
                    ? ['read_post' => "read_{$status}_posts", 'edit_post' => "edit_private_posts", 'delete_post' => "delete_private_posts"] 
                    : ['read_post' => "read_{$status}_posts", 'edit_post' => "edit_{$status}_posts", 'delete_post' => "delete_{$status}_posts"];

                    if (!$suppress_edit_caps && defined('PUBLISHPRESS_REVISIONS_VERSION')) {
                        $metacap_map['copy_post'] = "copy_{$status}_posts";
                    }

                    // Custom Visibility statuses have never mapped edit_others or delete_others capabilities
                    $cap_map = ($suppress_edit_caps) ? ['set_posts_status' => "status_change_{$status}"] : ['set_posts_status' => "set_posts_{$status}"];
                }

                \PublishPress\StatusCapabilities::registerCondition('post_status', $status, [
                    'label' => $status_obj->label,
                    'metacap_map' => $metacap_map,
                    'cap_map' => $cap_map,
                    'pattern_role_availability_requirement' => [
                        'edit_posts' => 'edit_published_posts', 
                        'delete_posts' => 'delete_published_posts'
                    ],
                ]);
            }
        }

        \PublishPress\StatusCapabilities::instance()->process_status_caps($statuses, ['private' => true]);
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
   /*
    * set_conditions[attribute][condition] = true
    * args : ['force_flush' => false]
    */ 
    public static function setItemCondition(
        $attribute, $scope, $item_source, $item_id, $set_conditions, $assign_for = 'item', $args = [])
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/DB/AttributesUpdate.php');

        return Statuses\DB\AttributesUpdate::set_item_condition(
            $attribute, $scope, $item_source, $item_id, $set_conditions, $assign_for, $args
        );
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
     * args : ['inherited_only' => false]
     */ 
    public static function clearItemCondition($attribute, $scope, $item_source, $item_id, $assign_for, $args = [])
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/DB/AttributesUpdate.php');

        return Statuses\DB\AttributesUpdate::clear_item_condition(
            $attribute, $scope, $item_source, $item_id, $assign_for, $args
        );
    }

    public static function orderStatuses($statuses = false, $args = [])
    {
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        // defaults: 'min_order' => 0, 'status_parent' => false, 'ignore_status' => [], 'include_status' => []

        return \PublishPress_Statuses::orderStatuses($statuses, $args);
    }

    public static function havePermission($perm_name, $args = [])
    {
        $defaults = ['force_refresh' => false];
        $args = array_merge($defaults, (array)$args);
        foreach (array_keys($defaults) as $var) {
            $$var = $args[$var];
        }

        $user = presspermit()->getUser();

        if (!isset($user->cfg[$perm_name])) {
            $user->cfg[$perm_name] = [];
        }

        if (!$force_refresh) {
            // requested values already cached
            return $user->cfg[$perm_name];
        }

        switch ($perm_name) {
            case 'moderate_any':
                $return = !empty($user->allcaps['pp_moderate_any']);
                break;
            default:
        }

        $user->cfg[$perm_name] = $return;
        return $return;
    }

    public static function haveStatusPermission($perm_name, $post_type, $post_status, $args = [])
    {
        $perms = self::getUserStatusPermissions($perm_name, $post_type, $post_status, $args);
        return !empty($perms[$post_status]);
    }

    public static function getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args = [])
    {
    	// Prior version of Statuses called this method, so avoid infinite recursion
        if (class_exists('PublishPress_Statuses') && (!empty($args['internal_call']) || version_compare(PUBLISHPRESS_STATUSES_VERSION, '1.0.6.5-rc2', '>='))) {
            return \PublishPress_Statuses::getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args);
        } else {
        	require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Workflow.php');
        	return Statuses\Workflow::getUserStatusPermissions($perm_name, $post_type, $check_statuses, $args);
        }
    }

    public static function publishpressStatusesActive($post_type = '', $args = [])
    {
        return true;
    }

    // If PressPermit permissions filtering is disabled for post type, don't load custom js for status dropdown and button labeling
    // Note, though, that 'pp_custom_status_list' filter still applies any per-type status availability set in Permissions > Post Statuses
    public static function isPostTypeEnabled($post_type = '') {
        global $post, $typenow;

        if (!empty($post)) {
            $post_type = $post->post_type;
        } else {
            if ($post_id = PWP::getPostID()) {
                $post_type = get_post_field('post_type', $post_id);
            } else {
                $post_type = (!empty($typenow)) ? $typenow : '';
            }
        }

        $statuses_type_enabled = (class_exists('PublishPress_Statuses')) ? ! \PublishPress_Statuses::DisabledForPostType($post_type) : true;

        return $statuses_type_enabled && in_array($post_type, presspermit()->getEnabledPostTypes(), true);
    }
}
