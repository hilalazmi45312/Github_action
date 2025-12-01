<?php
namespace PublishPress\Permissions\Statuses\UI;

class Admin
{
    var $disabled_conditions;

    function __construct() 
    {
        // This script executes on plugin load
        //
        add_action('presspermit_post_listing_ui', [$this, 'act_post_listing_ui']);    // fires on the 'init' action (late priority 70)
        add_action('presspermit_post_edit_ui', [$this, 'act_post_edit_ui']);          // fires on the 'init' action (late priority 70)
        add_action('presspermit_exceptions_status_ui_done', [$this, 'actExceptionsStatusUi'], 8, 2);  // Ajax: UI generation
        add_action('presspermit_options_ui', [$this, 'act_options_ui']);               // fires on admin.php plugin page load

        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);
        add_action('admin_head', [$this, 'actAdminHead']);
        add_action('presspermit_admin_ui', [$this, 'act_publishpress_dependency']);

        add_filter('publishpress_statuses_row-actions', [$this, 'fltStatusesRowActions'], 10, 2);
        add_action('publishpress_statuses_custom_column', [$this, 'actStatusesCustomColumn'], 10, 3);
    }

    function act_scripts()
    {
        global $pagenow;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        if ('publishpress-statuses' == PWP::getPluginPage()) {
            wp_enqueue_style('publishpress-statuses-permissions', PRESSPERMIT_STATUSES_URLPATH . '/common/css/statuses.css', [], PRESSPERMIT_STATUSES_VERSION);

        } elseif (in_array($pagenow, ['post.php', 'post-new.php'])) {
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit.css', [], PRESSPERMIT_STATUSES_VERSION);
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit-ie.css', [], PRESSPERMIT_STATUSES_VERSION);
        }

        if (!PPS::privacyStatusesDisabled()) {
            wp_enqueue_script('presspermit-statuses-misc', PRESSPERMIT_STATUSES_URLPATH . "/common/js/statuses{$suffix}.js", ['jquery'], PRESSPERMIT_STATUSES_VERSION, false);
        }
    }

    function actAdminHead()
    {
    }

    function act_publishpress_dependency()
    {
    }

    function act_post_listing_ui()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostsListing.php');
        new Dashboard\PostsListing();
    }

    function act_post_edit_ui()
    {
        if (in_array(PWP::findPostType(), ['forum', 'topic', 'reply'])) // future @todo: support bbp custom privacy as applicable
            return;

        if (PWP::isBlockEditorActive()) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEdit.php');
            new Gutenberg\PostEdit();
        } else {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostEdit.php');
            new Dashboard\PostEdit();
        }
    }

    function act_options_ui()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/SettingsTabStatuses.php');
        new SettingsTabStatuses();
    }

    function actExceptionsStatusUi($for_type, $args = [])
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/PermissionsAjax.php');
        PermissionsAjax::actExceptionsStatusUi($for_type, $args);
    }

    function fltStatusesRowActions($actions, $status) {
        static $base_url;
        static $can_manage_cond;

        if (!empty($status->taxonomy) && (\PublishPress_Statuses::TAXONOMY_PSEUDO_STATUS == $status->taxonomy)) {
            return [];
        }

        if (!isset($can_manage_cond))
            $can_manage_cond = current_user_can('pp_define_post_status');

        if (!isset($this->disabled_conditions)) {
            $base_url = apply_filters('presspermit_conditions_base_url', 'admin.php');
            $this->disabled_conditions = presspermit()->getOption("disabled_post_status_conditions");
        }

        $attrib = 'post_status';
        $attrib_type = 'moderation';

        $cond_obj = get_post_status_object($status->slug);
        $cond = $status->slug;

        if ($is_publishpress = !empty($cond_obj->pp_custom)) {
            unset($this->disabled_conditions[$cond]);
        }

        if (!$can_manage_cond) {
            unset($actions['edit']);
        }

        if ($cond && empty($cond_obj->_builtin)) {
            // Custom Caps now reviewed / enabled / disabled by clicking Post Access cell

        } elseif (empty($actions)) {
            $actions[''] = ['url' => '', 'label' => '&nbsp;'];  // temp workaround to prevent shrunken row
        }

        if (empty($cond_obj->_builtin) && empty($cond_obj->pp_builtin) && !empty($cond_obj->private)) { 
            $actions['delete'] = [
                'url' => wp_nonce_url($base_url . "?page=publishpress-statuses&amp;pp_action=delete&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions'),
                'label' => __('X')
            ];
        }

        $actions = apply_filters('presspermit_condition_row-actions', $actions, $attrib, $cond_obj);

        return $actions;
    }

    function actStatusesCustomColumn($column_name, $status, $args = []) {
        if (!presspermit()->getOption('legacy_status_control') || defined('PUBLISHPRESS_STATUSES_VERSION')) {
            return;
        }
        
        if (!isset($this->disabled_conditions)) {
            $this->disabled_conditions = presspermit()->getOption("disabled_post_status_conditions");
        }

        $attrib = 'post_status';
        $attrib_type = 'moderation';

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Statuses.php');
        
        $cond_obj = get_post_status_object($status->slug);
        $cond = $status->slug;

        $is_publishpress = !empty($cond_obj->pp_custom);

        switch ($column_name) {
            case 'enabled':
                if (!empty($this->disabled_conditions[$cond])) {
                    esc_html_e('Disabled', 'presspermit-pro');
                
                } else {
                    if (!\PublishPress\StatusCapabilities::postStatusHasCustomCaps($cond) || !empty($cond_obj->_builtin)) {
                        esc_html_e('Standard', 'presspermit-pro');
                    } else {
                        $tooltip = '';

                        if ($_cond_obj = \PublishPress\Permissions\Statuses\UI\Statuses::get_condition('post_status', $status->slug)) {
                            $maps = [];
            
                            if (!empty($_cond_obj->metacap_map)) {
                                foreach ($_cond_obj->metacap_map as $orig => $map) {
                                    $tooltip .= $orig . ' > ' . $map . " \r\n\r\n";
                                }
                            }
                            if (!empty($_cond_obj->cap_map)) {
                                foreach ($_cond_obj->cap_map as $orig => $map) {
                                    $tooltip .= $orig . ' > ' . $map . " \r\n\r\n";
                                }
                            }
                        }

                        echo "<span title='" . esc_attr($tooltip) . "'>";

                        if (!empty($cond_obj->capability_status) && ($cond_obj->capability_status != $cond)) {
                            if ($cap_status_obj = get_post_status_object($cond_obj->capability_status)) {
                                printf(esc_html__('(same as %s)', 'presspermit-pro'), esc_html($cap_status_obj->label));
                            } else {
                                esc_html_e('Custom', 'presspermit-pro');
                            }
                        } else {
                            esc_html_e('Custom', 'presspermit-pro');
                        }

                        echo '</span>';
                    }
                }
                
                break;
        }
    }
}
