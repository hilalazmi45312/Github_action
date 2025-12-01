<?php
namespace PublishPress\Permissions\Statuses;

class Updated
{
    public function __construct($prev_version)
    {
        // single-pass do loop to easily skip unnecessary version checks
        do {
            if (!get_option("ppperm_added_pps_role_caps_10beta"))
                self::populateRoles();

            if (version_compare($prev_version, '3.8-beta3', '<')) {
                // Explicitly store previous default setting so new default can be implemented without affecting sites that have never updated settings
                if (null === get_option('presspermit_privacy_statuses_enabled', null)) {
                    update_option('presspermit_privacy_statuses_enabled', 1);
                }
            } else break;

            if (version_compare($prev_version, '2.1.14', '<')) {
                if ($statuses = get_option('presspermit_custom_conditions_post_status')) {
                    $modified = false;
                    foreach (array_keys($statuses) as $status_name) {
                        if (strlen($status_name) > 20) {
                            $new_name = substr($status_name, 0, 20);
                            $statuses[$new_name] = (array)$statuses[$status_name];
                            unset($statuses[$status_name]);
                            $modified = true;
                        }
                    }

                    if ($modified)
                        update_option('presspermit_custom_conditions_post_status', $statuses);
                }
            } else break;

            if (version_compare($prev_version, '2.0.10-beta', '<')) {
                global $wpdb;

                // phpcs Note: Direct query of options table on plugin update operation, for efficient and reliable bulk update of options

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->update(
                    $wpdb->options, 
                    ['autoload' => 'yes'], 
                    ['option_name' => 'presspermit_custom_conditions_post_status']
                );

            } else break;
        } while (0); // end single-pass version check loop
    }

    public static function populateRoles($ver = '10beta')
    {
        // in case the role has been manually customized, don't force default caps back in
        if (get_option("ppperm_added_pps_role_caps_{$ver}"))
            return;

        switch ($ver) {
            case '10beta' :
                if ($role = @get_role('administrator')) {
                    $role->add_cap('pp_define_post_status');
                    $role->add_cap('set_posts_status');  // need this in pattern role to support mapping of set_posts_approved, etc.
                }

                if ($role = @get_role('editor')) {
                    $role->add_cap('set_posts_status');
                    $role->add_cap('pp_moderate_any');
                }

                if ($role = @get_role('author')) {
                    $role->add_cap('set_posts_status');
                }
                break;
        }

        update_option("ppperm_added_pps_role_caps_{$ver}", true);
    }
}
