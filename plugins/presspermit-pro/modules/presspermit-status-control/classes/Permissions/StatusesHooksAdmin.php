<?php
namespace PublishPress\Permissions;

class StatusesHooksAdmin 
{
    function __construct() {
        // This script executes on plugin load if is_admin()
        //
        define('PRESSPERMIT_STATUSES_URLPATH', plugins_url('', PRESSPERMIT_STATUSES_FILE));

        add_action('publishpress_status_capabilities_loaded', [$this, 'act_process_conditions'], 49);

        // === todo: Should bulk editing be supported on Statuses screen ? ====
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        //add_action('presspermit_admin_handlers', [$this, 'act_admin_handlers']);

        add_action('check_ajax_referer', [$this, 'act_inline_edit_status_helper']);
        add_action('check_admin_referer', [$this, 'act_bulk_edit_posts']);

        add_action('presspermit_condition_caption', [$this, 'act_condition_caption'], 10, 3);

        add_action('presspermit_permission_status_ui_done', [$this, 'act_permission_status_ui'], 10, 4);

        if (defined('DOING_AJAX') && DOING_AJAX && !defined('PP_AJAX_FINDPOSTS_STATI_OK'))
            add_action('wp_ajax_find_posts', [$this, 'ajax_find_posts'], 0);

        add_filter('acf/location/rule_values/post_status', [$this, 'acf_status_rule_options']);

        add_action('wp_loaded', [$this,'actLoadAjaxHandler'], 20);

        add_filter('presspermit_option_captions', [$this, 'optionCaptions'], 15);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 15);
        add_action('presspermit_options_ui_insertion', [$this, 'advanced_tab_permissions_options_ui'], 5, 3); // hook for UI insertion on Settings > Advanced tab

        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions'], 15);  // priority 5 for ordering between PPS and PPCC additions in caps list

        add_filter('cme_plugin_capabilities', [$this, 'fltRegisterCapabilities'], 20);

        add_filter('publishpress_status_edit_redirect_args', [$this, 'fltEditStatusRedirectArgs'], 10, 2);

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Admin.php'); // @todo: more conditional loading?
        new Statuses\UI\Admin();
    }

    function fltRegisterCapabilities($caps) {
        if (!isset($caps['PublishPress Statuses'])) {
            $caps['PublishPress Statuses'] = [];
        }

        $caps['PublishPress Statuses'] = array_merge(
            $caps['PublishPress Statuses'], 
            ['set_posts_status', 'pp_moderate_any', 'pp_define_post_status', 'pp_define_privacy']
        );

        asort($caps['PublishPress Statuses']);

        return $caps;
    }

    function flt_cap_descriptions($pp_caps)
    {
        require_once(PRESSPERMIT_CLASSPATH . '/UI/SettingsAdmin.php');

        foreach(['pp_define_post_status', 'pp_define_privacy', 'set_posts_status', 'pp_moderate_any'] as $cap_name) {
            $pp_caps[$cap_name] = UI\SettingsAdmin::getStr('cap_' . $cap_name);
        }

        return $pp_caps;
    }

    function fltEditStatusRedirectArgs($args, $status_obj) {
        
        // If custom capabilities are newly enabled, redirect back to Post Access tab for review / editing of Role Caps
        if (PWP::is_REQUEST('status_capability_status')) {
            if (!$stored_capability_status = presspermit()->getOption('status_capability_status')) {
                $stored_capability_status = [];
            }

            $set_status_capability_status = PWP::REQUEST_key('status_capability_status');

            if (($status_obj->name == $set_status_capability_status) && empty($stored_capability_status[$status_obj->name])) {
                $args['action'] = 'edit-status';
                $args['name'] = $status_obj->name;
                $args['pp_tab'] = 'post_access';
            }
        }

        return $args;
    }

    public function actLoadAjaxHandler()
    {
        foreach (['set_privacy'] as $ajax_type) {
            if (isset($_REQUEST["pp_ajax_{$ajax_type}"])) {
                check_ajax_referer('pp-ajax');

                $class_name = str_replace('_', '', ucwords( $ajax_type, '_') ) . 'Ajax';
                
                $class_parent = ( in_array($class_name, ['SetPrivacyAjax']) ) ? 'Gutenberg' : '';
                
                $require_path = ( $class_parent ) ? "{$class_parent}/" : '';
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . "/UI/{$require_path}{$class_name}.php");
                
                $load_class = "\\PublishPress\Permissions\Statuses\UI\\";
                $load_class .= ($class_parent) ? $class_parent . "\\" . $class_name : $class_name;

                new $load_class();

                exit;
            }
        }
    }

    function ajax_find_posts()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/Ajax.php');
        Statuses\UI\Dashboard\Ajax::wp_ajax_find_posts();
    }

    function act_process_conditions()
    {
        global $wp_post_statuses;

        // This was previously an important startup function, but almost everything has been moved into the StatusCapabilities library.

        // Previous safeguard against draft or pending status being deleted by PublishPress
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        $this->reinstate_draft_pending();
        add_action('wp_loaded', [$this, 'reinstate_draft_pending']);
        */

        // This was previously necessary to make these statuses available in the Permissions > Post Statuses list. 
        // But actual treatment as a moderation status was determined by stored option and applied by PPCE before PPS::registerCondition() call.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        $wp_post_statuses['pending']->moderation = true;
        $wp_post_statuses['future']->moderation = true;
        */

        // This was previously applied as a precaution against checking a non-existant moderation property.
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        foreach (array_keys($wp_post_statuses) as $status) {
            if (empty($wp_post_statuses[$status]->moderation)) {
                $wp_post_statuses[$status]->moderation = false;
            }
        }
        */

        $pp = presspermit();

        $user = presspermit()->getUser();

        $supplemental_cap_moderate_any = (defined('PUBLISHPRESS_STATUSES_VERSION'))
        ? !empty(\PublishPress_Statuses::instance()->options->supplemental_cap_moderate_any)
        : $pp->getOption('supplemental_cap_moderate_any');

        // unfortunate little hack due to execution order
        if ($supplemental_cap_moderate_any && $user->ID 
        && $user->site_roles && !$pp->isContentAdministrator()
        ) {
            require_once(PRESSPERMIT_COLLAB_CLASSPATH . '/Permissions.php');
            Collab\Permissions::supplementModerateAnyCap();
        }
    }

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function reinstate_draft_pending()
    {
        global $wp_post_statuses;

        // Cannot currently deal with PublishPress deletion of Draft or Pending status
        if (empty($wp_post_statuses['draft']) || empty($wp_post_statuses['draft']->label)) {
            register_post_status('draft', [
                'label' => _x('Draft', 'post'),
                'protected' => true,
                '_builtin' => true,
                'label_count' => _n_noop('Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>'),
            ]);

            if (!empty($wp_post_statuses['draft']->labels))
                $wp_post_statuses['draft']->labels->save_as = esc_attr(PWP::__wp('Save Draft'));
        }

        if (empty($wp_post_statuses['pending']) || empty($wp_post_statuses['pending']->label)) {
            register_post_status('pending', [
                'label' => _x('Pending', 'post'),
                'protected' => true,
                '_builtin' => true,
                'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>'),
            ]);
        }
    }
    */

    function act_inline_edit_status_helper($referer)
    {
        if ('inlineeditnonce' == $referer) {
            if ($keep_custom_privacy = PWP::POST_key('keep_custom_privacy')) {
                $_POST['_status'] = $keep_custom_privacy;
            }
        }
    }

    function act_bulk_edit_posts($referer)
    {
        if ('bulk-posts' == $referer) {
            if (presspermit()->isContentAdministrator() || current_user_can('pp_force_quick_edit')) {
                // phpcs note: Nonce check not required because this code is already triggered by a check_admin_referer('bulk-posts') call.

                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/BulkEdit.php');
                Statuses\UI\Dashboard\BulkEdit::bulk_edit_posts($_REQUEST);  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }
    }

    function act_condition_caption($cond_caption, $attrib, $cond)
    {
        $attributes = PPS::attributes();

        if (isset($attributes->attributes[$attrib]->conditions[$cond])) {
            $cond_caption = $attributes->attributes[$attrib]->conditions[$cond]->label;

        } elseif ('post_status' == $attrib) {
            if ($status_obj = get_post_status_object($cond))
                $cond_caption = $status_obj->label;
        }

        return $cond_caption;
    }

    function act_permission_status_ui($object_type, $type_caps, $role_name = '')
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Attributes.php');
        Statuses\UI\Attributes::attributes_ui($object_type, $type_caps, $role_name);
    }

    function acf_status_rule_options($statuses)
    {
        $stati = get_post_stati(['internal' => false], 'object');
        foreach ($stati as $status => $status_obj) {
            if (!isset($statuses[$status]))
                $statuses[$status] = $status_obj->label;
        }

        return $statuses;
    }

    function optionCaptions($captions)
    {
        $captions['pattern_roles_include_custom_status_rolecaps'] = esc_html__('Type-specific Supplemental Roles grant all custom status capabilities in Pattern Role', 'presspermit-pro');
        return $captions;
    }

    function optionSections($sections)
    {
        if (presspermit()->getOption('advanced_options')) {
            $new = [
                'role_integration' => ['pattern_roles_include_custom_status_rolecaps'],
            ];

            $tab = 'advanced';

            if (isset($sections[$tab])) {
                foreach ($new as $section => $options) {
                    $sections[$tab][$section] = (isset($sections[$tab][$section])) ? array_merge($sections[$tab][$section], $options) : $options;
                }
            }
        }

        return $sections;
    }

    function advanced_tab_permissions_options_ui ($tab, $section, $ui) {
        if (('advanced' == $tab) && ('role_integration' == $section)) {
            $hint = __('For example, if the Author role has the edit_pitch_posts capability, a Supplemental Role of Page Author will include edit_pitch_pages', 'presspermit-pro');
            $ui->optionCheckbox('pattern_roles_include_custom_status_rolecaps', $tab, $section, $hint);
        }
    }
}
