<?php
namespace PublishPress\Permissions;

class StatusesHooks 
{
    function __construct() 
    {
        add_filter('presspermit_default_options', [$this, 'flt_default_statuses_options']);

        if (!defined('PUBLISHPRESS_STATUSES_VERSION') && !get_option('presspermit_legacy_status_control')) {
            return;
        }

        // This script executes on plugin load.
        //
        require_once(PRESSPERMIT_STATUSES_ABSPATH . '/db-config.php');

        // Legacy constants from prior versions.
        // This previously exectuted on the init action at priority 10. Move it earlier to ensure our related code doesn't check it before it's set. 
        // Priority 20 allows for possible existing define statements in main body of user-maintained plugin modules.
        add_action('plugins_loaded', function() {
            if (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS')) {
                define('PPS_CUSTOM_PRIVACY_EDIT_CAPS', !defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS') && presspermit()->getOption('custom_privacy_edit_caps'));
            } else {
                if (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED')) {
                    define('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED', true);
                }
            }
        }, 20);

        add_action('presspermit_register_role_attributes', [$this, 'act_register_role_attributes'], 5);

        add_action('presspermit_maintenance_triggers', [$this, 'actMaintenanceTriggers']);

        add_action('init', [$this, 'actRegistrations'], 46);
        add_action('init', [$this, 'act_post_stati_prep'], 48);  // StatusesHooksAdmin::act_process_conditions() follows at priority 49

        add_action('presspermit_pre_init', [$this, 'act_version_check']);
        add_action('presspermit_pre_init', [$this, 'act_forceDistinctPostCaps']);

        add_filter('presspermit_pattern_roles_raw', [$this, 'fltPatternRolesRaw']);
        add_filter('presspermit_pattern_roles', [$this, 'fltPatternRoles']);

        add_filter('presspermit_pattern_role_caps', [$this, 'flt_default_rolecaps']);
        add_filter('presspermit_exclude_arbitrary_caps', [$this, 'fltExcludeArbitraryCaps']);

        add_filter('rest_user_query', [$this, 'flt_rest_user_query'], 20, 2);  // todo: relocate?

        if (defined('REVISIONARY_VERSION')) { // note: no equivalent needed for PublishPress Revisions 3
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Revisionary/CapabilityFilters.php');
            new Statuses\Revisionary\CapabilityFilters();
        }

        add_filter('user_has_cap', [$this, 'fltPublishPostsContext'], 100, 3);

        add_action('rest_api_init', [$this, 'actRestInit'], 1);

        add_filter('post_row_actions', [$this, 'fixPostRowActions'], 9, 2);
        add_filter('page_row_actions', [$this, 'fixPostRowActions'], 9, 2);

        add_action('presspermit_pro_version_updated', [$this, 'pluginUpdated']);

        add_action('publishpress_statuses_register_visibility_statuses', [$this, 'registerVisibilityStatuses']);
        add_action('publishpress_statuses_register_taxonomies', [$this, 'registerTaxonomies']);
        add_filter('publishpress_statuses_get_default_statuses', [$this, 'fltGetDefaultStatuses'], 10, 2);

        add_filter(
            'presspermit_get_post_statuses',
            function ($statuses, $args, $return, $operator, $params = []) {
                global $pagenow;
                
                if (empty($pagenow) || !in_array($pagenow, ['post.php', 'post-new.php'])) {
                    return $statuses;
                }

                /*
                 * Filter: 'presspermit_limit_editor_post_statuses'
                 * 
                 * ['post_status' => array of selectable status names]
                 * 
                 * Note: statuses may be further limited based on user capabilities / permissions
                 */
                if (!$valid_statuses = apply_filters('presspermit_limit_editor_post_statuses', [], $args)){
                    return $statuses;
                }
                
                if (current_user_can('administrator')) {
                    return $statuses;
                }

                if (!$post_id = PWP::getPostID()) {
                    $post_status = 'draft';
                } else {
                    if (!$post_status = get_post_field('post_status', $post_id)) {
                        return $statuses;
                    }
                }

                if (isset($valid_statuses[$post_status])) {
                    $statuses = array_intersect_key($statuses, array_fill_keys($valid_statuses[$post_status], true));
                }

                return $statuses;
            }
        , 100, 5);
    }

    function act_register_role_attributes()
    {
        // Restriction of read access will be accomplished by post status setting (either private or a custom status registered with private=true) 
        //
        // force_visibility attribute does not impose condition caps, but affects the post_status of published posts.
        \PublishPress\StatusCapabilities::registerAttribute(
            'force_visibility', 
            'post',
            [
                'label' => esc_html__('Force Visibility', 'presspermit-pro'), 
                'default' => 'none', 
                'suppress_item_edit_ui' => ['object' => true]
            ]
        );

        // @todo: Remove the following code block pending confirmation the user base no longer needs this feature

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        if (is_user_logged_in()
        && (PWP::isFront() || (defined('REST_REQUEST') && REST_REQUEST))
        && get_option('draft_reading_exceptions') 
        ) {
            global $wp_post_statuses;
            $wp_post_statuses['draft']->private = true;
            $wp_post_statuses['draft']->protected = false;

            $status_obj = get_post_status_object('draft');

            \PublishPress\StatusCapabilities::registerCondition('post_status', 'draft', [
                'label' => $status_obj->label,
                'metacap_map' => ['read_post' => 'read_draft_posts'],
            ]);
        }
        */

        // register each custom post status as an attribute condition with mapped caps
        PPS::registerPrivacyConditions(PWP::getPostStatuses([], 'object'));
    }

    /**
     * Makes the call to register_post_status to register the user's post visibility statuses.
     *
     * @param array $args
     */
    public function registerVisibilityStatuses($statuses)
    {
        if (! presspermit()->getOption('privacy_statuses_enabled')) {
            return;
        }

        // This has never been implemented for visibility statuses as a whole (only per-status)
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        if (self::disable_custom_statuses_for_post_type()) {
            return;
        }
        */

        if (function_exists('register_post_status')) {
            foreach ($statuses as $status) {
                // Ignore custom moderation statues and all core statuses, which are registered elsewhere.
                if (empty($status->private) || !empty($status->_builtin) || !empty($status->moderation)
                || !empty($status->disabled)
                || in_array($status->slug, ['_pre-publish-alternate', '_disabled'])) {
                    continue;
                }

                register_post_status($status->slug, \PublishPress_Statuses::visibility_status_properties($status));
            }
        }
    }


    function registerTaxonomies() {
        if (presspermit()->getOption('privacy_statuses_enabled')) {
            // @todo: check for disable of custom privacy statuses feature
            if (! taxonomy_exists(\PublishPress_Statuses::TAXONOMY_PRIVACY)) {
                register_taxonomy(
                    \PublishPress_Statuses::TAXONOMY_PRIVACY,
                    'post',
                    [
                        'hierarchical' => false,
                        'update_count_callback' => '_update_post_term_count',
                        'label' => __('Post Visibility', 'publishpress-statuses'),
                        'labels' => (object) ['name' => __('Post Visibility', 'publishpress-statuses'), 'singular_name' => __('Post Visibility', 'publishpress-statuses')],
                        'query_var' => false,
                        'rewrite' => false,
                        'show_ui' => false,
                    ]
                );
            }
        }
    }

    public function fltGetDefaultStatuses($statuses, $taxonomy) {
        if (\PublishPress_Statuses::TAXONOMY_PRIVACY != $taxonomy) {
            return $statuses;
        }

        if (! presspermit()->getOption('privacy_statuses_enabled')) {
            return [];
        }

        $statuses = [
            'member' => (object) [
                'label' => __('Member', 'publishpress'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-universal-access-alt',
                'position' => 10,
                'order' => 901,
                'private' => true,
                'pp_builtin' => true,
            ],

            'premium' => (object) [
                'label' => __('Premium', 'publishpress'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-superhero',
                'position' => 11,
                'order' => 902,
                'private' => true,
                'pp_builtin' => true,
            ],

            'staff' => (object) [
                'label' => __('Staff', 'publishpress'),
                'description' => '',
                'color' => '#aa0000',
                'icon' => 'dashicons-id-alt',
                'position' => 12,
                'order' => 903,
                'private' => true,
                'pp_builtin' => true,
            ],
        ];

        return $statuses;
    }

    // Account for list_* capability provision: don't display Preview link if post is not editable
    function fixPostRowActions($actions, $post) {
        $can_edit = current_user_can('edit_post', $post->ID);

        if (in_array($post->post_status, get_post_stati(['public' => true, 'private' => true], 'names', 'OR'))) {
            if (!$can_edit && !current_user_can('read_post', $post->ID)) {
                unset($actions['view']);
            }
        } elseif(!$can_edit && !defined('PUBLISHPRESS_REVISIONS_VERSIONS')) {  // todo: API?
            unset($actions['view']);
        }

        return $actions;
    }

    function fltPublishPostsContext($wp_sitecaps, $orig_reqd_caps, $args)
    {
        $user = presspermit()->getUser();
        $args = (array)$args;

        $post_id = PWP::getPostID();

        if (($args[1] != $user->ID) || !$post_id || (defined('ET_BUILDER_PLUGIN_VERSION') && !PWP::empty_REQUEST('et_fb'))) {
            return $wp_sitecaps;
        }

        // If we are crediting edit_others_posts capability based on ownership of edit_others_{$status}_posts, 
        // don't honor publish_posts except for own posts
        if ($_post = get_post($post_id)) {
            if ($user->ID != $_post->post_author) {
                if ($type_obj = get_post_type_object($_post->post_type)) {
                    if (isset($type_obj->cap->publish_posts) && ($type_obj->cap->publish_posts == $args[0])) {
                        if (isset($type_obj->cap->edit_others_posts) && empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                            $cap_property = "edit_others_{$_post->post_status}_posts";
                            if (isset($type_obj->cap->$cap_property) && !empty($user->allcaps[$type_obj->cap->$cap_property])) {
                                unset($wp_sitecaps[$type_obj->cap->publish_posts]);
                            }
                        }
                    }
                }
            }
        }

        return $wp_sitecaps;
    }

    function actRestInit()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/RESTFields.php');
        Statuses\RESTFields::registerRESTFields();
    }

    function flt_default_statuses_options($def = [])
    {
        $new = [
            'privacy_statuses_enabled' => 1,
            'custom_privacy_edit_caps' => defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS') ? PPS_CUSTOM_PRIVACY_EDIT_CAPS : 0,    // previous default was intended to be 1, but now set to 0 to maintain behavior because this filter was not applied
            'supplemental_cap_moderate_any' => 0,
            'pattern_roles_include_custom_status_rolecaps' => 0,

            // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
            //'draft_reading_exceptions' => 0,
        ];

        return array_merge($def, $new);
    }

    function actMaintenanceTriggers()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Triggers.php');
        new Statuses\Triggers();
    }

    // Register default custom stati; Additional labels in status registration
    function actRegistrations()
    {
        global $wp_post_statuses;

        if ($privacy_statuses_enabled = presspermit()->getOption('privacy_statuses_enabled')) {
            // custom private stati
            register_post_status('member', [
                'label' => _x('Member', 'post'),
                'private' => true,
                'label_count' => _n_noop('Member <span class="count">(%s)</span>', 'Member <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);

            register_post_status('premium', [
                'label' => _x('Premium', 'post'),
                'private' => true,
                'label_count' => _n_noop('Premium <span class="count">(%s)</span>', 'Premium <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);

            register_post_status('staff', [
                'label' => _x('Staff', 'post'),
                'private' => true,
                'label_count' => _n_noop('Staff <span class="count">(%s)</span>', 'Staff <span class="count">(%s)</span>'),
                'pp_builtin' => true,
            ]);
        }

        // @todo: import Permissions 3.x Visibility Statuses

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        $custom_stati = (array)get_option('presspermit_custom_conditions_post_status');
        $native_moderation = false;

        foreach ($custom_stati as $status => $status_args) {
            if (isset($wp_post_statuses[$status])) {
                foreach(['label', 'save_as_label', 'publish_label'] as $property) {
                    if (!empty($status_args[$property])) {
                        $wp_post_statuses[$status]->$property = $status_args[$property];
                    }
                }
            }
            
            if (!empty($status_args['moderation'])) {
                if (defined('PP_NO_MODERATION'))
                    continue;

                $status_args['protected'] = true;
                $native_moderation = true;
            }

            if (!isset($wp_post_statuses[$status]) && $status && empty($status_args['publishpress']) 
            && !in_array($status, ['pitch', 'in-progress', 'assigned'])
            ) {
                register_post_status($status, $status_args);
            }

            if (!isset($wp_post_statuses[$status])) {
                continue;
            }

            if (!empty($status_args['post_type'])) {
                $wp_post_statuses[$status]->post_type = (array)$status_args['post_type'];
            }
        }
        */

        // todo: migrate this old option to new status disable mechanism?
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        if ($native_moderation) {
            $disable_native_moderation_statuses = get_option('ppperm_disable_native_moderation_statuses');
        }

        if ($native_moderation && $disable_native_moderation_statuses && !defined('PRESSPERMIT_LEGACY_MODERATION_STATUSES')) {
            foreach (array_intersect_key($wp_post_statuses, $custom_stati) as $post_status => $status_obj) {
                if (!in_array($post_status, ['draft', 'pending', 'future']) && empty($status_obj->_builtin) && empty($status_obj->private)) {
                    unset($wp_post_statuses[$post_status]);
                }
            }
        }
        */
    }

    function act_post_stati_prep()
    {
        global $wp_post_statuses;

        $pp = presspermit();

        // set default properties
        foreach (array_keys($wp_post_statuses) as $status) {
            if (!isset($wp_post_statuses[$status]->moderation))
                $wp_post_statuses[$status]->moderation = false;
        }


        // Apply term meta properties set by PublishPress Statuses @todo: move this into Statuses Pro for tighter integration
        if (taxonomy_exists('post_status')) {
            foreach ($wp_post_statuses as $post_status => $status_obj) {
                if (empty($status_obj->private) || ('private' == $post_status)) {
                    continue;
                }

                if ($term = get_term_by('slug', $post_status, 'post_visibility_pp')) {
                    if ($term_meta = get_term_meta($term->term_id)) {
                        foreach (['color', 'icon', 'labels', 'post_type'] as $prop) {
                            if (isset($term_meta[$prop])) {
                                $value = maybe_unserialize($term_meta[$prop]);
                                $value = (is_array($value)) ? reset($value) : $value;

                                if (('post_type' != $prop) || !PWP::is_REQUEST('page', 'pp-capabilities')) {
                                    $wp_post_statuses[$post_status]->$prop = maybe_unserialize($value);
                                }
                            }
                        }
                    }
                }
            }
        }

        // Prior implementation's status property defaulting / loading sequence
        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        /*
        if ($stati_post_types = (array)$pp->getOption('status_post_types')) {
            foreach ($stati_post_types as $status => $types) {
                if (isset($wp_post_statuses[$status])) {
                    $wp_post_statuses[$status]->post_type = $types;
                }
            }
        }

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $status_cap_status = array_intersect_key((array)$pp->getOption('status_capability_status'), $wp_post_statuses);
            
            foreach ($status_cap_status as $status => $cap_status) {
                $wp_post_statuses[$status]->capability_status = $cap_status;
            }

            $stati_order = array_intersect_key((array)$pp->getOption('status_order'), $wp_post_statuses);
            foreach ($stati_order as $status => $order) {
                $wp_post_statuses[$status]->order = $order;
            }

            $stati_parent = array_intersect_key((array)$pp->getOption('status_parent'), $wp_post_statuses);
            foreach ($stati_parent as $status => $parent) {
                $wp_post_statuses[$status]->status_parent = $parent;
            }

            if (isset($wp_post_statuses['pending']) && !isset($wp_post_statuses['pending']->order))
                $wp_post_statuses['pending']->order = 10;

            if (isset($wp_post_statuses['approved']) && !isset($wp_post_statuses['approved']->order))
                $wp_post_statuses['approved']->order = $wp_post_statuses['pending']->order + 8;

            foreach (PWP::getPostStatuses(['moderation' => true]) as $status) {
                if (!isset($wp_post_statuses[$status]->status_parent)) {
                    $wp_post_statuses[$status]->status_parent = '';
                }

                if (!isset($wp_post_statuses[$status]->order)) {
                    $wp_post_statuses[$status]->order = ($wp_post_statuses[$status]->status_parent) 
                    ? 0 
                    : $wp_post_statuses['pending']->order + 4;

                    $wp_post_statuses[$status]->order = 0;
                }
            }
        }
        */
    }

    function act_version_check()
    {
        $ver = get_option('pps_version');
        $pp_ver = get_option('presspermitpro_version');

        if (!empty($ver['version'])) {
            // These maintenance operations only apply when a previous version of PPCS was installed 
            if (version_compare(PRESSPERMIT_STATUSES_VERSION, $ver['version'], '!=')) {
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Updated.php');
                new Statuses\Updated($ver['version']);
                update_option(
                    'pps_version', 
                    ['version' => PRESSPERMIT_STATUSES_VERSION, 'db_version' => PRESSPERMIT_STATUSES_DB_VERSION]
                );

                // pp_attributes table was not created in previous 2.7-beta versions
                $force_db_update = version_compare($ver['version'], '2.7-beta3', '<');
            }
        } else {
            // first execution after install
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Updated.php');
            Statuses\Updated::populateRoles();
        }

        if (!empty($force_db_update) || !is_array($ver) || empty($ver['db_version']) 
        || version_compare(PRESSPERMIT_STATUSES_DB_VERSION, $ver['db_version'], '!=') 
        || ($pp_ver && version_compare($pp_ver['version'], '3.2.7', '<'))
        ) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/DB/DatabaseSetup.php');
            $db_ver = (is_array($ver) && isset($ver['db_version'])) ? $ver['db_version'] : '';
            new Statuses\DB\DatabaseSetup($db_ver);
            update_option('pps_version', ['version' => PRESSPERMIT_STATUSES_VERSION, 'db_version' => PRESSPERMIT_STATUSES_DB_VERSION]);
        }
    }

    function act_forceDistinctPostCaps()
    {
        global $wp_post_types;

        $pp = presspermit();

        $generic_caps = ['post' => ['set_posts_status' => 'set_posts_status'], 'page' => ['set_posts_status' => 'set_posts_status']];

        // post types which are enabled for PP filtering must have distinct type-related cap definitions
        foreach (array_intersect(get_post_types(['public' => true, 'show_ui' => true], 'names', 'or'), $pp->getEnabledPostTypes()) as $post_type) {
            $type_caps = [];
            
            if (class_exists('PublishPress_Statuses') && \PublishPress_Statuses::DisabledForPostType($post_type)) {
                continue;
            }

            if ('post' == $post_type) {
                $type_caps['set_posts_status'] = 'set_posts_status';
            } else {
                $type_caps['set_posts_status'] = str_replace('_post', "_$post_type", 'set_posts_status');
            }

            $wp_post_types[$post_type]->cap = (object)array_merge((array)$wp_post_types[$post_type]->cap, $type_caps);

            $plural_type = \PublishPress\Permissions\Capabilities::getPlural($post_type, $wp_post_types[$post_type]);

            $pp->capDefs()->all_type_caps = array_merge($pp->capDefs()->all_type_caps, array_fill_keys($type_caps, true));

            if (class_exists('PublishPress_Statuses')) {
                foreach (PWP::getPostStatuses(['moderation' => true, 'post_type' => $post_type, 'disabled' => false]) as $status_name) {
                    $cap_property = "set_{$status_name}_posts";
                    $wp_post_types[$post_type]->cap->$cap_property = str_replace("_posts", "_{$plural_type}", $cap_property);
                }
            }
        }
    }

    // For optimal flexibility with custom moderation stati (including PublishPress Statuses), dynamically insert a Submitter role 
    // containing the 'set_posts_status' capability.
    //
    // With default Contributor rolecaps, a "Page Contributor - Assigned" role enables the user to edit their own pages 
    // which have been set to assigned status.  
    //
    // "Page Submitter - Assigned" role enables setting their other pages to the Approved status
    //
    // These supplemental roles may be assigned individually or in conjunction
    // Note that the set_posts_status capability is granted implicitly for the 'pending' status, 
    // even if custom capabilities are enabled.
    function flt_default_rolecaps($caps)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION') && !isset($caps['submitter'])) {
            $caps['submitter'] = array_fill_keys(['read', PRESSPERMIT_READ_PUBLIC_CAP, 'set_posts_status'], true);
        }

        return $caps;
    }

    public function fltPatternRolesRaw($roles) {
        return $this->fltPatternRoles($roles, false);
    }

    function fltPatternRoles($roles, $set_labels = false)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            if (!isset($roles['submitter']))
                $roles['submitter'] = (object)[];

            if (!isset($roles['submitter']->labels)) {
                if ($set_labels) {
                    $roles['submitter']->labels = (object)[
                        'name' => esc_html__('Submitters', 'presspermit'), 
                        'singular_name' => esc_html__('Submitter', 'presspermit')
                    ];
                } else {
                    $display_name = 'Submitter';
                    $roles['submitter']->labels = (object)['name' => $display_name, 'singular_name' => $display_name];
                }
            }
        }

        return $roles;
    }

    // Prior implementation's correlation of status order to status term position:
    //
    // Mirror ordering defined by PressPermit back to PublishPress.
    //
    // PublishPress does not natively support branching, but position sequence 
    // is flattened considering branch parent and child order.

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function flt_publishpress_status_position($terms, $taxonomies, $query_vars, $term_query = false)
    {
        global $publishpress;

        $pp = presspermit();

        $taxonomies = (array)$taxonomies;

        if ('post_status' != reset($taxonomies)) {
            return $terms;
        }

        $status_order_arr = array_merge(PPS::defaultStatusOrder(), (array)$pp->getOption('status_order'));
        $status_parent_arr = (array)$pp->getOption('status_parent');

        foreach ($terms as $k => $term_obj) {
            $status = $term_obj->slug;

            if (!empty($status_parent_arr[$status])) {
                $parent_status = $status_parent_arr[$status];
                $parent_order = (!empty($status_order_arr[$parent_status])) ? $status_order_arr[$parent_status] : 0;
                $status_order = (!empty($status_order_arr[$status])) ? $status_order_arr[$status] : 500;
                $terms[$k]->position = (1000 * $parent_order) + $status_order + 1;

            } elseif (isset($status_order_arr[$status])) {
                // Allow space between top level statuses for branch statuses
                $terms[$k]->position = 1000 * $status_order_arr[$status];
            }

            // term description may contain encoded copy of other position property, overriding filtering 
            if (!empty($publishpress->custom_status)) {
                if (!empty($terms[$k]->description)) {
                    $descript = $publishpress->custom_status->get_unencoded_description($terms[$k]->description);
                    if (is_array($descript)) {
                        unset($descript['position']);
                    }
                    $terms[$k]->description = $publishpress->custom_status->get_encoded_description($descript);
                }
            }
        }

        return $terms;
    }
    */


    // Prior implementation's status disable application, hooked to 'presspermit_roles_defined' action ('init' action priority 70)

    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
    /*
    function act_roles_defined()
    {
        /*
        if ('presspermit-statuses' != presspermitPluginPage()) {
            if ($disabled = (array)presspermit()->getOption('disabled_post_status_conditions')) {
                $attributes = PPS::attributes();
                
                $attributes->attributes['post_status']->conditions = array_diff_key(
                    $attributes->attributes['post_status']->conditions, 
                    $disabled
                );

                global $wp_post_statuses;
                $disabled = array_diff_key($disabled, get_post_stati(['_builtin' => true]));

                foreach ($wp_post_statuses as $k => $status_obj) {
                    if (!empty($status_obj->pp_custom)) {
                        unset($wp_post_statuses[$k]);
                    }
                }

                $wp_post_statuses = array_diff_key($wp_post_statuses, $disabled);
            }
        }
    }
    */

    function fltExcludeArbitraryCaps($caps)
    {
        $excluded = ['pp_define_post_status', 'pp_define_moderation', 'pp_define_privacy'];

        if (!\PublishPress_Statuses::instance()->options->supplemental_cap_moderate_any)
            $excluded [] = 'pp_moderate_any';

        return array_merge($caps, $excluded);
    }

    // Gutenberg: filter post author dropdown 
    function flt_rest_user_query($prepared_args, $request)
    {
        if (isset($prepared_args['who']) && ('authors' == $prepared_args['who'])) {
            if ($post_type = PWP::findPostType()) {
                if ($type_obj = get_post_type_object($post_type)) {
                    if (!current_user_can($type_obj->cap->edit_others_posts)) {
                        global $current_user;
                        $prepared_args['include'] = $current_user->ID;
                    }
                }
            }
        }

        return $prepared_args;
    }

    public function pluginUpdated($prev_version) {
        if (version_compare($prev_version, '3.6.1', '<')) {
            add_action('wp_loaded', function() {
                global $wp_roles;

                // For each status, if "edit_others_{$status_name}_attachments" was set to a role due to past bug, add type-specific status capability if other editing capabilities are present for the post type

                $statuses = get_post_stati(['moderation' => true], 'names', 'or');

                foreach($statuses as $status_name) {
                    $check_cap = "edit_others_{$status_name}_attachments";

                    foreach($wp_roles->roles as $role_name => $_role) {
                        if (!empty($_role['capabilities'][$check_cap])) {
                            $role = @get_role($role_name);
                        
                            // For each post type that has custom capabilities enabled, if the user has "edit_{$status_name}_pages" and edit_others_pages, also set "edit_others_{$status_name}_pages"

                            if (\PublishPress\StatusCapabilities::postStatusHasCustomCaps($status_name)) {
                                foreach(get_post_types(['public' => true], 'object') as $post_type => $type_obj) {
                                    $edit_posts_status_cap = str_replace('edit_', "edit_{$status_name}_", $type_obj->cap->edit_posts);
                                    
                                    if (!empty($type_obj->cap->edit_others_posts) && !empty($_role['capabilities'][$edit_posts_status_cap]) && !empty($_role['capabilities'][$type_obj->cap->edit_others_posts])) {
                                        $edit_others_posts_status_cap = str_replace('edit_others_', "edit_others_{$status_name}_", $type_obj->cap->edit_others_posts);
                                        $role->add_cap($edit_others_posts_status_cap);
                                    }
                                }
                            }
                        }
                    }
                }
            }, 50);
        }
    }
}
