<?php
namespace PublishPress\Permissions\Statuses\UI;

use \PublishPress\Permissions\UI\SettingsAdmin as SettingsAdmin;

class SettingsTabStatuses
{
    function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 3);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_statuses_options_pre_ui', [$this, 'statuses_options_pre_ui']);
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
            'privacy' => esc_html__('Visibility', 'presspermit-pro'),
        ];

        $key = 'statuses';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function optionCaptions($captions)
    {
        $captions['privacy_statuses_enabled'] = esc_html__('Custom Visibility Statuses', 'presspermit-pro');
        $captions['custom_privacy_edit_caps'] = esc_html__('Custom Visibility Statuses require status-specific editing capabilities', 'presspermit-pro');

        // @todo: Remove the following code block pending confirmation the user base no longer needs this feature

        // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
        //$captions['draft_reading_exceptions'] = esc_html__('Drafts visible on front end if specific Read Permissions assigned', 'presspermit-pro');

        return $captions;
    }

    function optionSections($sections)
    {
        $new = [
            'privacy' => ['privacy_statuses_enabled', 'custom_privacy_edit_caps'],
        ];

        $tab = 'statuses';
        $sections[$tab] = (isset($sections[$tab])) ? array_merge($sections[$tab], $new) : $new;

        return $sections;
    }

    function statuses_options_pre_ui()
    {
        if (presspermit()->getOption('display_hints')) :
            ?>
            <div class="pp-optionhint">
                <?php
                // output a caption here as needed
                ?>
            </div>
        <?php
        endif;
    }

    function statuses_options_ui()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'statuses';

        $section = 'privacy';                       // --- PRIVACY STATUS SECTION ---
        
        $privacy_statuses_enabled = $ui->getOption('privacy_statuses_enabled');
        $display_enable_checkbox = true;
        
        if (!empty($ui->form_options[$tab][$section]) && ($display_enable_checkbox || ($privacy_statuses_enabled && !PPS::privacyStatusesDisabled()))) : ?>
            <tr>
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

                    if ($privacy_statuses_enabled) {
                        if (defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS_LOCKED') || defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS')) {
                            // This constant is defined by the StatusesHooks constructor (if not already defined) for legacy support
                            $args = ['val' => PPS_CUSTOM_PRIVACY_EDIT_CAPS, 'no_storage' => true, 'disabled' => true, 'title' => esc_attr__('Locked by constant definition', 'presspermit-pro')];
                        } else {
                            $args = [];
                        }

                        $ui->optionCheckbox('custom_privacy_edit_caps', $tab, $section, true, '', $args);
                    }
                    ?>
                </td>
            </tr>
        <?php endif; // any options accessable in this section

        ?>

        <tr><td><p>
        <?php 
        $url = admin_url('admin.php?page=publishpress-statuses');
        
        printf(
            esc_html__('To manage Statuses, go to %sStatuses > Statuses%s', 'publishpress'),
            "<strong><a href='" . esc_url($url) . "'>",
            '</a></strong>'
        );
        ?>
        </p></td>
        </tr>

        <?php
    }
}
