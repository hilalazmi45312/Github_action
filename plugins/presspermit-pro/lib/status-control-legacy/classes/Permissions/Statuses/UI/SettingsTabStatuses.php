<?php
namespace PublishPress\Permissions\Statuses\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

class SettingsTabStatuses
{
    private $disabled = false;
    private $advanced_checkbox = false;

    function __construct()
    {
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        $this->disabled = !defined('PUBLISHPRESS_STATUSES_VERSION') && !get_option('presspermit_legacy_status_control');

        if ($this->disabled) {
            $statuses_info = (function_exists('pp_permissions_statuses_info')) ? pp_permissions_statuses_info() : [];
            $statuses_last_ver = get_option('publishpress_statuses_version', 0);
            $planner_last_ver = get_option('publishpress_version', 0);
            $planner_ver = (defined('PUBLISHPRESS_VERSION')) ? PUBLISHPRESS_VERSION : 0;

            // If Legacy Mode is not already enabled, don't offer it if Statuses is installed (active or inactive) or was previously activated.
            // If Planner > 4.x was or is active, offer Legacy Status Control only if Custom Visibility Statuses are not enabled.
            if ((
                (empty($statuses_info['statuses_installed']) && !$statuses_last_ver) 
                && ((version_compare($planner_last_ver, '4.0', '<') && version_compare($planner_ver, '4.0', '<')) || get_option('presspermit_privacy_statuses_enabled'))
            ) || (defined('PRESSPERMIT_OFFER_LEGACY_STATUS_CONTROL') && PRESSPERMIT_OFFER_LEGACY_STATUS_CONTROL)
            ) {
                $this->advanced_checkbox = true;
                
                add_action('presspermit_options_ui_insertion', 
                    function($tab, $section, $ui) {
                        if (('advanced' == $tab) && ('enable' == $section)) {
                            echo '<div style="margin-top:30px">';
                            $this->legacy_enable_checkbox_ui('advanced', 'enable');
                            echo '</div>';
                        }
                    },
                    10, 3
                );
            }
            
            return;
        }
        
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 7);
        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);

        add_action('presspermit_statuses_options_ui', [$this, 'statuses_options_ui']);
    }

    function optionTabs($tabs)
    {
        $tabs['statuses'] = esc_html__('Statuses', 'presspermit-pro');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        $new = [
            'legacy' => esc_html__('Legacy Status Control', 'presspermit-pro'),
        ];

        $new['privacy'] = esc_html__('Visibility', 'presspermit-pro');

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $new['workflow'] = esc_html__('Workflow', 'presspermit-pro');
        }

        $key = 'statuses';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        if(isset($sections['advanced'])) {
            $sections['advanced'] = array_merge($sections['advanced'], ['legacy' => esc_html__('Legacy Status Control', 'presspermit-pro')]);
        }

        return $sections;
    }

    function optionCaptions($captions)
    {
        if (!$this->disabled || $this->advanced_checkbox) {
            $captions['legacy_status_control'] = esc_html__('Use legacy status control if PublishPress Statuses plugin is not activated', 'presspermit-pro');
        }

        if (!$this->disabled) {
            $captions['privacy_statuses_enabled'] = esc_html__('Custom Visibility Statuses', 'presspermit-pro');
            $captions['custom_privacy_edit_caps'] = esc_html__('Custom Visibility Statuses require status-specific editing capabilities', 'presspermit-pro');
            $captions['draft_reading_exceptions'] = esc_html__('Drafts visible on front end if specific Read Permissions assigned', 'presspermit-pro');

            if (defined('PRESSPERMIT_COLLAB_VERSION')) {
                $captions['supplemental_cap_moderate_any'] = esc_html__('Supplemental Editor Role for "standard statuses" also grants capabilities for Workflow Statuses', 'presspermit-pro');
                $captions['moderation_statuses_default_by_sequence'] = esc_html__('Publish button defaults to next workflow status (instead of highest permitted)', 'presspermit-pro');
            }
        }

        return $captions;
    }

    function optionSections($sections)
    {
        if ($this->disabled) {
            if ($this->advanced_checkbox) {
                $tab = 'advanced';
                if (isset($sections[$tab]['enable'])) {
                    $sections[$tab]['enable'] []= 'legacy_status_control';
                }
            }
        } else {
            $new = [
                'legacy' => ['legacy_status_control'],
            ];

            $new['privacy'] = ['privacy_statuses_enabled', 'custom_privacy_edit_caps', 'draft_reading_exceptions'];

            if (defined('PRESSPERMIT_COLLAB_VERSION')) {
                $new['workflow'] = ['supplemental_cap_moderate_any'];
                $new['workflow'][] = 'moderation_statuses_default_by_sequence';
            }

            $tab = 'statuses';
            $sections[$tab] = (isset($sections[$tab])) ? array_merge($sections[$tab], $new) : $new;
        }

        return $sections;
    }

    function statuses_options_ui()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'statuses';

        $section = 'legacy';                       // --- LEGACY ENABLE SECTION ---
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]); ?>
            </th>

            <td>
                <?php
                $this->legacy_enable_checkbox_ui($tab, $section);
                ?>
            </td>
        </tr>
        <?php

        $section = 'workflow';                      // --- WORKFLOW STATUS SECTION ---
        if (!empty($ui->form_options[$tab][$section]) && defined('PRESSPERMIT_COLLAB_VERSION')) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]); ?>
                    <div class="pp-extra-heading pp-statuses-other-config">
                        <h4><?php esc_html_e('Additional Configuration:', 'presspermit-pro'); ?></h4>
                        <ul>
                            <?php
                            if (PPS::publishpressStatusesActive()) : ?>
                                <li>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pp-modules-settings&settings_module=pp-custom-status-settings')); ?>"><?php esc_html_e('Post Workflow Statuses', 'presspermit-pro'); ?></a>
                                </li>

                            <?php elseif (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) : ?>
                                <li>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pp-modules-settings&settings_module=pp-custom-status-settings')); ?>"><?php esc_html_e('Enable PublishPress Status Dropdown', 'presspermit-pro'); ?></a>
                                </li>
                            <?php elseif (defined('PUBLISHPRESS_VERSION')) : ?>
                                <li>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=pp-modules-settings&settings_module=pp-modules-settings-settings#modules-wrapper')); ?>"><?php esc_html_e('Turn on PublishPress Statuses', 'presspermit-pro'); ?></a>
                                </li>
                            <?php else : ?>
                                <li style="font-size:12px;font-weight:normal">
                                    <?php esc_html_e('Activate PublishPress', 'presspermit-pro'); ?></a>
                                </li>
                            <?php endif; ?>

                            <li>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=presspermit-statuses&attrib_type=moderation')); ?>">
                                <?php
                                if (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) {
                                    esc_html_e('Workflow Order, Branching, Permissions', 'presspermit-pro');
                                } else {
                                    esc_html_e('Post Workflow Statuses', 'presspermit-pro');
                                }
                                ?>
                                </a>
                            </li>

                        </ul>
                    </div>
                </th>

                <td>
                    <?php
                    $ui->optionCheckbox('supplemental_cap_moderate_any', $tab, $section, true);
                    
                   	$ui->optionCheckbox('moderation_statuses_default_by_sequence', $tab, $section, true);

                    $ui->optionCheckbox('draft_reading_exceptions', $tab, $section);
                    ?>
                </td>
            </tr>
        <?php endif; // any options accessable in this section

        $section = 'privacy';                       // --- PRIVACY STATUS SECTION ---
                
        $privacy_statuses_enabled = $ui->getOption('privacy_statuses_enabled');
        $display_enable_checkbox = true;

        if (!empty($ui->form_options[$tab][$section]) && ($display_enable_checkbox || ($privacy_statuses_enabled && !PPS::privacyStatusesDisabled()))) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($ui->section_captions[$tab][$section]); ?>
                    <?php if ($privacy_statuses_enabled): ?>
                    <div class="pp-extra-heading pp-statuses-other-config">
                        <h4><?php esc_html_e('Additional Configuration:', 'presspermit-pro'); ?></h4>
                        <ul>
                            <li>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=presspermit-statuses&attrib_type=private')); ?>"><?php esc_html_e('Define Privacy Statuses', 'presspermit-pro'); ?></a>
                            </li>

                        </ul>
                    </div>
                    <?php endif;?>
                </th>

                <td>
                    <?php
                    if ($display_enable_checkbox) {
                        if ($statuses_used = PPS::customStatiUsed(['ignore_moderation_statuses' => true])) {
                            $args = ['val' => $privacy_statuses_enabled, 'no_storage' => $privacy_statuses_enabled, 'disabled' => $privacy_statuses_enabled, 'hint_class' => 'pp-subtext-show'];
                            $hint = ($privacy_statuses_enabled) ? SettingsAdmin::getStr('posts_using_custom_privacy') : '';
                        } else {
                            $args = [];
                            $hint = '';
                        }

                        $ui->optionCheckbox('privacy_statuses_enabled', $tab, $section, $hint, '', $args);
                    }

                    if ($privacy_statuses_enabled && !PPS::privacyStatusesDisabled()) {
                        $args = (defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS')) ? ['val' => 0, 'no_storage' => true, 'disabled' => true] : [];
                        $ui->optionCheckbox('custom_privacy_edit_caps', $tab, $section, true, '', $args);
                    }
                    ?>
                </td>
            </tr>
        <?php endif; // any options accessable in this section
    }

    function legacy_enable_checkbox_ui($tab, $section) {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 

        $hint = '';
        $args = [];

        $ui->optionCheckbox('legacy_status_control', $tab, $section, $hint, '', $args);
        ?>

        <p>
        <?php printf(
            esc_html__('%1$sNote: Requires PublishPress Planner 3.x. %2$s Legacy mode is to maintain existing functionality prior to PublishPress Statuses installation.', 'presspermit-pro'),
            '<strong>',
            '</strong>'
        );
        ?>
        </p>

        <?php
        $statuses_info = (function_exists('pp_permissions_statuses_info')) ? pp_permissions_statuses_info() : [];
        $info_url = (!empty($statuses_info['info_url'])) ? $statuses_info['info_url'] : 'https://wordpress.org/plugins/publishpress-statuses';

        if (!empty($statuses_info['statuses_installed'])) {
            ?>
            <p>
            <?php printf(
                esc_html__('You should probably leave this disabled and just %1$sre-activate the PublishPress Statuses plugin%2$s.', 'presspermit-pro'),
                '<strong>',
                '</strong>'
            );
            ?>
            </p>
            <?php
        } else {
            ?>
            <br>
            <p>
            <?php printf(
                esc_html__('For better control of the publication workflow, add the %1$sPublishPress Statuses%2$s plugin. %3$sLearn more...%4$s', 'presspermit-pro'),
                '<a href="' . esc_url($info_url) . '" class="thickbox">',
                '</a>',
                '<a href="https://publishpress.com/blog/publishpress-statuses/statuses-available/" target="_blank">',
                '</a>'
            );
            ?>
            </p>
            <p>
            <?php esc_html_e('Your current Permissions Pro version is already compatible.', 'presspermit-pro');
            ?>
            </p>
        <?php
        }
    }
}
