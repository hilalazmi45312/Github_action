<?php
/*
Copyright 2025 PublishPress

This file is part of PublishPress Permissions Pro.

PublishPress Permissions Pro is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

PublishPress Permissions Pro is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this plugin.  If not, see <http://www.gnu.org/licenses/>.
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!defined('PRESSPERMIT_STATUSES_FILE')) {
    define('PRESSPERMIT_STATUSES_FILE', __FILE__);
    define('PRESSPERMIT_STATUSES_ABSPATH', __DIR__);

    if (!defined('REVISIONARY_VERSION') && defined('RVY_VERSION')) {
        define('REVISIONARY_VERSION', RVY_VERSION);
    }

    if (!defined('PRESSPERMIT_VERSION')) {
        return;
    }

    $ext_version = PRESSPERMIT_VERSION;

    $module_title = 'Status Control'; // @todo: review removing this, as it is separately set with translation downstream

    if (presspermit()->registerModule(
        'status-control', $module_title, dirname(plugin_basename(__FILE__)), $ext_version, ['min_pp_version' => '2.7-beta']
    )) {
        define('PRESSPERMIT_STATUSES_VERSION', $ext_version);

        define('PRESSPERMIT_STATUSES_DB_VERSION', '1.0');

        class_alias('\PressShack\LibArray', '\PublishPress\Permissions\Statuses\Arr');
        class_alias('\PressShack\LibArray', '\PublishPress\Permissions\Statuses\UI\Arr');

        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\DB\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\Revisionary\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\UI\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\UI\Dashboard\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\UI\Gutenberg\PWP');
        class_alias('\PressShack\LibWP', '\PublishPress\Permissions\Statuses\UI\Handlers\PWP');

        // Status capabilities library: loader stub to select latest version of library (@todo: vendor library?)
        require_once(PRESSPERMIT_PRO_ABSPATH . '/lib/status-capabilities/status-capabilities.php');

        add_filter('publishpress_status_capabilities_class', 
            function($class_name) { 
                require_once(__DIR__ . '/classes/Permissions/Statuses/Attributes.php');
                return '\PublishPress\Permissions\Statuses\Attributes';
            }
        );

        add_action('plugins_loaded', function() {
            if (defined('PUBLISHPRESS_STATUSES_VERSION')) {
                define('PRESSPERMIT_STATUSES_CLASSPATH', __DIR__ . '/classes/Permissions/Statuses');

                if (!class_exists('PublishPress\StatusCapabilities')) {
                    $status_caps_package = apply_filters('publishpress_status_capabilities_library', false);

                    if (is_object($status_caps_package) && isset($status_caps_package->path) && file_exists($status_caps_package->path)) {
                        require_once($status_caps_package->path);

                        if (class_exists('PublishPress\StatusCapabilities')) {
                            \PublishPress\StatusCapabilities::instance();
                        }
                    }
                }

                require_once(__DIR__ . '/classes/Permissions/Statuses.php');
                \PublishPress\Permissions\Statuses::defineClassAliases();

                require_once(__DIR__ . '/classes/Permissions/StatusesHooks.php');
                new \PublishPress\Permissions\StatusesHooks();
        
                if (is_admin()) {
                    require_once(__DIR__ . '/classes/Permissions/StatusesHooksAdmin.php');
                    new \PublishPress\Permissions\StatusesHooksAdmin();
                }

            } elseif ( !defined('PRESSPERMIT_DISABLE_LEGACY_STATUS_CONTROL')
            && file_exists(PRESSPERMIT_PRO_ABSPATH . '/lib/status-control-legacy/classes/Permissions/StatusesHooks.php')
            ) {
                $statuses_info = (function_exists('pp_permissions_statuses_info')) ? pp_permissions_statuses_info() : [];
                $planner_ver = (defined('PUBLISHPRESS_VERSION')) ? PUBLISHPRESS_VERSION : 0;

                // Don't support Legacy Status Control if Statuses is installed (activate or inactive).
                // If Planner > 4.x is active, support Legacy Status Control only if Custom Visibility Statuses are enabled.
                // This prevents reverting to a (previously enabled) Legacy Status Control if Statuses is temporarily deactivated.
                if ((
                    empty($statuses_info['statuses_installed'])
                    && (version_compare($planner_ver, '4.0', '<') || get_option('presspermit_privacy_statuses_enabled'))
                ) || (defined('PRESSPERMIT_OFFER_LEGACY_STATUS_CONTROL') && PRESSPERMIT_OFFER_LEGACY_STATUS_CONTROL)
                ) {
                define('PRESSPERMIT_STATUSES_CLASSPATH', PRESSPERMIT_PRO_ABSPATH . '/lib/status-control-legacy/classes/Permissions/Statuses');

                require_once(PRESSPERMIT_PRO_ABSPATH . '/lib/status-control-legacy/classes/Permissions/Statuses.php');
                \PublishPress\Permissions\Statuses::defineClassAliases();

                // Early version check to establish default Legacy Status Control setting (affecting which modules load)
                $ver = get_option('pps_version');

                $opt_val = get_option('presspermit_legacy_status_control', -1);

                if ((-1 === $opt_val) && $ver && is_array($ver) && !empty($ver['version'])) {
                    // These maintenance operations only apply when a previous version of Permissions Pro was installed 
                    if (version_compare($ver['version'], '4.0.0', '<')) {

                        // Don't default to Legacy Mode if PublishPress Statuses has ever been active
                        if (!get_option('publishpress_statuses_version')) {

                            // Default to Legacy Mode if any of these options used by Permissions Pro Status Control are stored
                            if (!$default_legacy = get_option('presspermit_status_capability_status') || get_option('presspermit_status_order') 
                            || get_option('presspermit_status_parent') || get_option('presspermit_custom_conditions_post_status')) {
                                
                                // Also default to Legacy Mode if any posts currently have a plugin-defined custom status
                                global $wpdb;
                                $default_legacy = $wpdb->get_var(  // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                                    "SELECT ID FROM $wpdb->posts WHERE post_status IN ('pitch', 'assigned', 'in-progress', 'approved', 'member', 'staff', 'premium') LIMIT 1"
                                );
                            }

                            if ($default_legacy) {
                                update_option('presspermit_legacy_status_control' , 1);
                            } else {
                                update_option('presspermit_legacy_status_control', 0);
                            }
                        }
                    }
                }

                if (get_option('presspermit_legacy_status_control')) {
                    require_once(PRESSPERMIT_PRO_ABSPATH . '/lib/status-control-legacy/classes/Permissions/StatusesHooks.php');
                    new \PublishPress\Permissions\StatusesHooks();
                }

                if (is_admin()) {
                    require_once(PRESSPERMIT_PRO_ABSPATH . '/lib/status-control-legacy/classes/Permissions/StatusesHooksAdmin.php');
                    new \PublishPress\Permissions\StatusesHooksAdmin();
                    }
                }

                if (is_admin()) {
                    add_filter('presspermit_constants', 
                        function($pp_constants) {
                            $pp_constants['PRESSPERMIT_OFFER_LEGACY_STATUS_CONTROL'] = (object) [
                                'descript' => ucwords(strtolower(str_replace('_', ' ', 'OFFER_LEGACY_STATUS_CONTROL'))),        // phpcs:ignore WordPressVIPMinimum.Security.StaticStrreplace.StaticStrreplace
                                'type' => 'filtering-switches'
                            ];
                            
                            return $pp_constants;
                        }, 20
                    );
                }
            }
        });
    }
} else {
    add_action(
        'init', 
        function()
        {
            do_action('presspermit_duplicate_module', 'pp-custom-post-status', dirname(plugin_basename(__FILE__)));
        }
    );
    return;
}
