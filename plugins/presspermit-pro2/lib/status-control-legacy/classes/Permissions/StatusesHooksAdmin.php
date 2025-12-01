<?php

namespace PublishPress\Permissions;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

class StatusesHooksAdmin
{
    function __construct()
    {
        // This script executes on plugin load if is_admin()
        //

        define('PRESSPERMIT_STATUSES_URLPATH', plugins_url('', PRESSPERMIT_PRO_FILE) . '/lib/status-control-legacy');

        add_action('admin_menu', [$this, 'actBuildMenu'], 50, 1);

        if (defined('PUBLISHPRESS_STATUSES_VERSION') || get_option('presspermit_legacy_status_control')) {
            add_action('presspermit_conditions_loaded', [$this, 'act_process_conditions'], 49);

            add_action('presspermit_admin_handlers', [$this, 'act_admin_handlers']);
            add_action('check_ajax_referer', [$this, 'act_inline_edit_status_helper']);
            add_action('check_admin_referer', [$this, 'act_bulk_edit_posts']);

            add_action('presspermit_menu_handler', [$this, 'actMenuHandler']);
            add_action('presspermit_permissions_menu', [$this, 'act_permissions_menu'], 10, 2);
            add_action('admin_head', [$this, 'actAdminHead']);

            add_action('presspermit_condition_caption', [$this, 'act_condition_caption'], 10, 3);
            add_action('presspermit_permission_status_ui_done', [$this, 'act_permission_status_ui'], 10, 4);

            if (defined('DOING_AJAX') && DOING_AJAX && !defined('PP_AJAX_FINDPOSTS_STATI_OK'))
                add_action('wp_ajax_find_posts', [$this, 'ajax_find_posts'], 0);

            add_filter('acf/location/rule_values/post_status', [$this, 'acf_status_rule_options']);

            add_action('wp_loaded', [$this, 'actLoadAjaxHandler'], 20);
        } else {
            add_action('presspermit_handle_submission', function () {
                // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
                if (!empty($_REQUEST['legacy_status_control'])) {
                    wp_redirect(admin_url('admin.php?page=presspermit-settings&pp_tab=statuses'));
                    exit;
                }
            });
        }

        add_action('admin_print_scripts', function () {
            global $pagenow;

            if (in_array(presspermitPluginPage(), ['presspermit-settings', 'presspermit-statuses', 'presspermit-visibility-statuses', 'presspermit-status-edit']) || ('plugins.php' == $pagenow)) {

                if (get_option('presspermit_legacy_status_control')) {
                    add_action('admin_notices', [$this, 'act_statuses_plugin_legacy_notice']);
                }
            }
        });

        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions'], 5);  // priority 5 for ordering between PPS and PPCC additions in caps list

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Admin.php'); // @todo: more conditional loading?
        new Statuses\UI\Admin();
    }

    function flt_cap_descriptions($pp_caps)
    {
        foreach(['pp_define_post_status', 'pp_define_moderation', 'pp_define_privacy', 'set_posts_status', 'pp_moderate_any'] as $cap_name) {
            $pp_caps[$cap_name] = SettingsAdmin::getStr('cap_' . $cap_name);
        }

        return $pp_caps;
    }

    public function actLoadAjaxHandler()
    {
        foreach (['set_privacy'] as $ajax_type) {
            if (isset($_REQUEST["pp_ajax_{$ajax_type}"])) {
                check_ajax_referer('pp-ajax');

                $class_name = str_replace('_', '', ucwords($ajax_type, '_')) . 'Ajax';

                $class_parent = (in_array($class_name, ['SetPrivacyAjax'])) ? 'Gutenberg' : '';

                $require_path = ($class_parent) ? "{$class_parent}/" : '';
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . "/UI/{$require_path}{$class_name}.php");

                $load_class = "\\PublishPress\Permissions\Statuses\UI\\";
                $load_class .= ($class_parent) ? $class_parent . "\\" . $class_name : $class_name;

                new $load_class();

                exit;
            }
        }
    }

    // For old extensions linking to page=pp-settings.php, redirect to page=presspermit-settings, preserving other request args
    function actSettingsPageMaybeRedirect()
    {
        foreach (
            [
                'pp-stati' => 'presspermit-statuses',
                'pp-status-edit' => 'presspermit-status-edit',
                'pp-status-new' => 'presspermit-status-new',
            ] as $old_slug => $new_slug
        ) {
            if (
                !empty($_SERVER['REQUEST_URI']) && strpos(esc_url_raw($_SERVER['REQUEST_URI']), "page=$old_slug")
                && (false !== strpos(esc_url_raw($_SERVER['REQUEST_URI']), 'admin.php'))
            ) {
                global $submenu;

                // Don't redirect if pp-settings is registered by another plugin or theme
                foreach (array_keys($submenu) as $i) {
                    foreach (array_keys($submenu[$i]) as $j) {
                        if (isset($submenu[$i][$j][2]) && ($old_slug == $submenu[$i][$j][2])) {
                            return;
                        }
                    }
                }

                $arr_url = wp_parse_url(esc_url_raw($_SERVER['REQUEST_URI']));
                wp_redirect(admin_url('admin.php?' . str_replace("page=$old_slug", "page=$new_slug", $arr_url['query'])));
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

        $this->reinstate_draft_pending();
        add_action('wp_loaded', [$this, 'reinstate_draft_pending']);

        // This is necessary to make these statuses available in the Permissions > Post Statuses list. 
        // But actual treatment as a moderation status is determined by stored option and applied 
        // by PPCE before PPS::registerCondition() call,
        $wp_post_statuses['pending']->moderation = true;
        $wp_post_statuses['future']->moderation = true;

        foreach (array_keys($wp_post_statuses) as $status) {
            if (empty($wp_post_statuses[$status]->moderation))
                $wp_post_statuses[$status]->moderation = false;
        }
    }

    function reinstate_draft_pending()
    {
        global $wp_post_statuses;

        // Cannot currently deal with PublishPress deletion of Draft or Pending status
        if (empty($wp_post_statuses['draft']) || empty($wp_post_statuses['draft']->label)) {
            register_post_status('draft', [
                'label' => _x('Draft', 'post'),
                'protected' => true,
                '_builtin' => true, /* internal use only. */
                'label_count' => _n_noop('Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>'),
            ]);

            if (!empty($wp_post_statuses['draft']->labels))
                $wp_post_statuses['draft']->labels->save_as = esc_attr(PWP::__wp('Save Draft'));
        }

        if (empty($wp_post_statuses['pending']) || empty($wp_post_statuses['pending']->label)) {
            register_post_status('pending', [
                'label' => _x('Pending', 'post'),
                'protected' => true,
                '_builtin' => true, /* internal use only. */
                'label_count' => _n_noop('Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>'),
            ]);
        }
    }

    function act_admin_handlers()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/Admin.php');
        Statuses\UI\Handlers\Admin::handleRequest();
    }

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
                Statuses\UI\Dashboard\BulkEdit::bulk_edit_posts($_REQUEST); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        }
    }

    function actBuildMenu()
    {
        // satisfy WordPress' demand that all admin links be properly defined in menu
        $pp_page = presspermitPluginPage();

        if (in_array($pp_page, ['presspermit-status-new', 'presspermit-status-edit'], true)) {
            $handler = [$this, 'actMenuHandler'];
            $pp_cred_menu = presspermit()->admin()->getMenuParams('permits');

            $titles = [
                'presspermit-status-new' => esc_html__('Add New Status', 'presspermit-pro'),
                'presspermit-status-edit' => esc_html__('Edit Status', 'presspermit-pro'),
            ];

            if (PPS::privacyStatusesDisabled()) {
                unset($titles['presspermit-status-new']);
            }

            add_submenu_page($pp_cred_menu, $titles[$pp_page], '', 'read', $pp_page, $handler);
        }
    }

    function actMenuHandler($pp_page)
    {
        $pp_page = presspermitPluginPage();

        if ('presspermit-visibility-statuses' == $pp_page) {
            $class_name = 'Statuses';

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . "/UI/{$class_name}.php");
            $load_class = "\\PublishPress\Permissions\\Statuses\\UI\\$class_name";

            new $load_class('private');
        } elseif (in_array($pp_page, ['presspermit-statuses', 'presspermit-status-edit', 'presspermit-status-new'], true)) {

            $class_name = str_replace('-', '', ucwords(str_replace('presspermit-', '', $pp_page), '-'));

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . "/UI/{$class_name}.php");
            $load_class = "\\PublishPress\Permissions\\Statuses\\UI\\$class_name";

            new $load_class();
        }
    }

    function act_permissions_menu($options_menu, $handler)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            // If we are disabling native custom statuses in favor of PublishPress, 
            // but the Editing Permissions module is not active, hide this menu item.
            add_submenu_page(
                $options_menu,
                esc_html__('Workflow Statuses', 'presspermit-pro'),
                esc_html__('Workflow Statuses', 'presspermit-pro'),
                'read',
                'presspermit-statuses',
                $handler
            );
        }

        if (!PPS::privacyStatusesDisabled()) {
            // If we are disabling native custom statuses in favor of PublishPress, 
            // but the Editing Permissions module is not active, hide this menu item.
            add_submenu_page(
                $options_menu,
                esc_html__('Visibility Statuses', 'presspermit-pro'),
                esc_html__('Visibility Statuses', 'presspermit-pro'),
                'read',
                'presspermit-visibility-statuses',
                $handler
            );
        }
    }

    function actAdminHead()
    {
        if ('presspermit-statuses' == presspermitPluginPage()) {
            if ($links = apply_filters('presspermit_post_status_types', [])) {
                $link = reset($links);
                $attrib_type = $link->attrib_type;
            } else {
                $attrib_type = 'moderation';
            }

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusListTable.php');
            Statuses\UI\StatusListTable::instance($attrib_type);
        } elseif ('presspermit-visibility-statuses' == presspermitPluginPage()) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusListTable.php');
            Statuses\UI\StatusListTable::instance('private');
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

    private function useNetworkUpdates()
    {
        return (is_multisite() && (is_network_admin() || PWP::isNetworkActivated() || PWP::isMuPlugin()));
    }

    function act_statuses_plugin_legacy_notice()
    {
        global $pagenow;

        if (!defined('PUBLISHPRESS_STATUSES_VERSION') && current_user_can('activate_plugins')) {

            if (('plugins.php' == $pagenow) && PWP::empty_REQUEST('pp-statuses-info')) {
                return;
            }

            $class = 'notice pp-admin-notice pp-admin-notice-plugin'; ?>
            <div class='<?php echo esc_attr($class); ?>' id='pp_dashboard_message'>
                <div>
                    <?php
                    if ('plugins.php' == $pagenow) :
                        $settings_url = admin_url('admin.php?page=presspermit-settings&pp_tab=modules');

                        $_url = "plugin-install.php?tab=plugin-information&plugin=publishpress-statuses&section=changelog&TB_iframe=true&width=600&height=800";
                        $info_url = ($this->useNetworkUpdates()) ? network_admin_url($_url) : admin_url($_url);
                    ?>
                        <span class='update-message'>
                            <?php
                            printf(
                                esc_html__('%1$sStatus Control%2$s is running in Legacy Mode. For more control and a better editing workflow UI, install the %3$sPublishPress Statuses%4$s plugin. %5$sDetails / Install%6$s', 'presspermit-pro'),
                                "<a href='" . esc_url($settings_url) . "'>",
                                '</a>',
                                "<a href='" . esc_url($info_url) . "' class='thickbox'>",
                                '</a>',
                                "<a href='" . esc_url($info_url) . "' class='thickbox'>",
                                '</a>'
                            );
                            ?>
                        </span>

                    <?php else: ?>
                        <?php
                        $settings_url = admin_url('admin.php?page=presspermit-settings&pp_tab=modules');
                        $plugins_url = ($this->useNetworkUpdates()) ? network_admin_url('plugins.php?pp-statuses-info=1') : admin_url('plugins.php?pp-statuses-info=1');

                        printf(
                            esc_html__('%1$sStatus Control%2$s is running in Legacy Mode. For more control and a better editing workflow UI, %3$sinstall the %3$sPublishPress Statuses plugin%4$s.', 'presspermit-pro'),
                            "<a href='" . esc_url($settings_url) . "'>",
                            '</a>',
                            "<a href='" . esc_url($plugins_url) . "'>",
                            '</a>'
                        );
                        ?>

                    <?php endif; ?>
                </div>
            </div>
            <?php

            if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '4.0-rc', '>=')) : ?>
                <div class='<?php echo esc_attr($class); ?>' id='pp_dashboard_message'>
                    <div>
                        <?php
                        $settings_url = admin_url('admin.php?page=presspermit-settings&pp_tab=modules');

                        printf(
                            esc_html__('To restore all Workflow Statuses in %1$sLegacy Mode%2$s, revert PublishPress Planner Pro to version 3.x.', 'presspermit-pro'),
                            "<a href='" . esc_url($settings_url) . "'>",
                            '</a>'
                        );
                        ?>
                    </div>
                </div>
            <?php endif;
        }
    }

    // Deprecated in 4.0.35
    function act_statuses_plugin_inactive_notice()
    {
        global $pagenow, $current_user;

        if (!defined('PUBLISHPRESS_STATUSES_VERSION') && current_user_can('activate_plugins') && !get_user_option('pp_permissions_statuses_notice_done') && !defined('PRESSPERMIT_DISABLE_EXTRA_NOTICES')) {
            update_user_option($current_user->ID, 'pp_permissions_statuses_notice_done', true);

            $class = 'error pp-admin-notice pp-admin-notice-plugin';
            ?>
            <div class='<?php echo esc_attr($class); ?>' id='pp_dashboard_message'>
                <div>
                    <?php

                    $statuses_info = (function_exists('pp_permissions_statuses_info')) ? pp_permissions_statuses_info() : [];

                    $info_url = (!empty($statuses_info['info_url'])) ? $statuses_info['info_url'] : 'https://wordpress.org/plugins/publishpress-statuses';

                    $settings_url = admin_url('admin.php?page=presspermit-settings&pp_tab=modules');
                    ?>
                    <span class='update-message'>
                        <?php

                        $msg = !empty($statuses_info['statuses_installed'])
                            ? esc_html__('%1$sStatus Control%2$s is currently disabled because the PublishPress Statuses plugin is %3$sinactive%4$s.', 'presspermit-pro')
                            : esc_html__('%1$sStatus Control%2$s is currently disabled because the %3$sPublishPress Statuses%4$s plugin is missing. %3$sInstall it%4$s for better editing workflow control.', 'presspermit-pro');

                        printf(
                            $msg,   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            "<a href='" . esc_url($settings_url) . "'>",
                            '</a>',
                            "<a href='" . esc_url($info_url) . "' class='thickbox'>",
                            '</a>'
                        );
                        ?>
                    </span>

                </div>
            </div>
<?php
        }
    }
}
