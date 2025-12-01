<?php
namespace PublishPress\Permissions\Statuses\UI\Dashboard;

class PostsListing
{
    var $post_ids = [];

    function __construct()
    {
        // This script executes on the 'init' action if is_admin() for 'edit.php' and ajax action 'inline-save', if the post type is enabled for PP filtering. 
        //

        add_action('admin_print_footer_scripts', [$this, 'act_modify_inline_edit_ui']);

        if (PWP::empty_REQUEST('post_status') && PWP::empty_REQUEST('author')) {
            add_action('presspermit_user_init', [$this, 'maybe_force_all_posts_view']);
        }

        add_filter('presspermit_hide_quickedit', [$this, 'flt_hide_quickedit'], 10, 2);

        if (!PWP::empty_REQUEST('pp_submission_done')) {
            add_action('admin_notices', [$this, 'act_submission_notice']);
        }
    }

    // Since we are providing WYSIWYCE, don't default non-Editors to "Mine" view
    function maybe_force_all_posts_view() {
        $user = presspermit()->getUser();

        if (!$post_type = PWP::REQUEST_key('post_type')) {
            $post_type = 'post';
        }
    
        if (class_exists('PublishPress_Statuses') && \PublishPress_Statuses::DisabledForPostType($post_type)) {
            return;
        }

        if ($type_obj = get_post_type_object($post_type)) {
            if (!empty($type_obj->cap->edit_others_posts) && empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                $_REQUEST['all_posts'] = 1;
            }
        }
    }

    function act_submission_notice()
    {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php
                if (!PWP::empty_REQUEST('pp_submission_done')) {
                    if ($status_obj = get_post_status_object(PWP::REQUEST_key('pp_submission_done'))) {
                        $type_obj = get_post_type_object(PWP::findPostType());

                        if ($type_obj && class_exists('PublishPress_Statuses') && \PublishPress_Statuses::DisabledForPostType($type_obj->name)) {
                            return;
                        }

                        $type_label = ($type_obj) ? strtolower($type_obj->labels->singular_name) : esc_html__('post', 'ppx');
                        printf(
                                esc_html__('Your %1$s was successfully submitted, but you cannot make further edits at this time. The current status of the %1$s is %2$s.', 'presspermit-pro'), 
                                esc_html($type_label), 
                                esc_html($status_obj->label)
                        );
                    }
                }
                ?>
            </p>
        </div>
        <?php
    }

    function flt_hide_quickedit($hide, $type_obj)
    {
        if (empty($type_obj) || (class_exists('PublishPress_Statuses') && \PublishPress_Statuses::DisabledForPostType($type_obj->name))) {
            return false;
        }

        return !PPS::havePermission('moderate_any');
    }

    // @todo: move to .js
    // add "keep" checkboxes for custom private stati; set checked based on current or scheduled post status
    // add conditions UI to inline edit
    function act_modify_inline_edit_ui()
    {
        $pp = presspermit();

        $screen = get_current_screen();
        $post_type_object = get_post_type_object($screen->post_type);

        if (empty($post_type_object) || (class_exists('PublishPress_Statuses') && \PublishPress_Statuses::DisabledForPostType($screen->post_type))) {
            return;
        }
        ?>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function ($) {
                <?php
                $isContentAdministrator = presspermit()->isContentAdministrator();
                $moderation_statuses = [];
                global $typenow;

                $_stati = $pp->admin()->orderTypes(
                    PWP::getPostStatuses(
                        ['_builtin' => false, 'moderation' => true, 'post_type' => $typenow], 
                        'object'
                    ), 
                    ['order_property' => 'order']
                );
                
                foreach ($_stati as $status => $status_obj) {
                    $set_status_cap = "set_{$status}_posts";

                    $check_cap = (!empty($post_type_object->cap->$set_status_cap)) 
                    ? $post_type_object->cap->$set_status_cap 
                    : $post_type_object->cap->publish_posts;

                    if ($isContentAdministrator || PPS::havePermission('moderate_any') || current_user_can($check_cap)) {
                        $moderation_statuses[$status] = $status_obj;
                    }
                }

                $pvt_stati = [];
                $_stati = $pp->admin()->orderTypes(
                    PWP::getPostStatuses(
                        ['private' => true, 'post_type' => $typenow], 
                        'object'
                    ), 
                    ['order_property' => 'label']
                );
                
                foreach ($_stati as $status => $status_obj) {
                    $set_status_cap = "set_{$status}_posts";

                    $check_cap = (!empty($post_type_object->cap->$set_status_cap)) 
                    ? $post_type_object->cap->$set_status_cap 
                    : $post_type_object->cap->publish_posts;

                    if ($isContentAdministrator || current_user_can($check_cap)) {
                        $pvt_stati[$status] = $status_obj;
                    }
                }
                ?>

                <?php foreach( $moderation_statuses as $status => $status_obj ) :?>
                if (!$('select[name="_status"] option[value="<?php echo esc_attr($status);?>"]').length) {
                    $('<option value="<?php echo esc_attr($status);?>"><?php echo esc_html($status_obj->label);?></option>').insertBefore('select[name="_status"] option[value="pending"]');
                }
                <?php endforeach;?>

                <?php if (!PPS::privacyStatusesDisabled()): ?>
                    if ($('select[name="_status"] option[value="-1"]').length) {
                        <?php foreach( $pvt_stati as $status => $status_obj ) :?>
                        if (!$('select[name="_status"] option[value="<?php echo esc_attr($status);?>"]').length) {
                            $('<option value="<?php echo esc_attr($status);?>"><?php echo esc_html($status_obj->label);?></option>').insertAfter('select[name="_status"] option[value="private"]');
                        }
                        <?php endforeach;?>
                    }
                <?php endif;

                // also support forcing of default privacy for non-hierarchical types
                $is_hierarchical = is_post_type_hierarchical($typenow);

                if ( $is_hierarchical || ($pp->getTypeOption('default_privacy', $typenow) && ($pp->getTypeOption('force_default_privacy', $typenow) || PWP::isBlockEditorActive($typenow))) ) {
                global $posts;

                
                if ( !empty($posts) ) {
	                $attributes = PPS::attributes();
	
	                foreach( array_keys($posts) as $key ) :
		                if (!in_array($posts[$key]->ID, $this->post_ids)) continue;
		
		                $force_vis = $attributes->getItemCondition(
		                    'post',
		                    'force_visibility',
		                    ['id' => $posts[$key]->ID, 'assign_for' => 'item', 'default_only' => !$is_hierarchical, 'post_type' => $typenow]
		                );
		
		                if ( $is_hierarchical ) :
		                	$child_status = $attributes->getItemCondition('post', 'force_visibility', ['id' => $posts[$key]->ID, 'assign_for' => 'children']);
		                ?>
		                    $('#inline_<?php echo (int) $posts[$key]->ID;?> div._status').after('<div class="_status_sub"><?php echo esc_html($child_status);?></div>');
		                <?php endif; ?>
		                
		                $('#inline_<?php echo (int) $posts[$key]->ID;?> div._status').after('<div class="_force_vis"><?php echo esc_html($force_vis);?></div>');
	                <?php
	                endforeach;
                }
                ?>

                $("tr.bulk-edit-page label.inline-edit-status").parent().after('<div class="inline-edit-group"><label class="inline-edit-status-sub alignleft">'
                + '<span class="title"><?php printf(esc_html__('Subpage Vis.', 'presspermit-pro'), esc_html($post_type_object->label));?></span>'
                + '<span class="pp_child_select-open" style="margin-left: 0.1em">[</span>'
                + '<select name="_status_sub" title="<?php printf(esc_html__('Force visibility of sub-%s', 'presspermit-pro'), esc_html($post_type_object->label));?>" autocomplete="off"></select>'
                + '<span class="pp_child_select-close">]</span></label><span>'
                + '<label for="pp_propagate_visibility" class="alignleft" style="display:none; margin-top:0.5em"><input type="checkbox" name="pp_propagate_visibility" id="pp_propagate_visibility" checked disabled />'
                + ' <?php printf(esc_html__('existing sub-%s', 'presspermit-pro'), esc_html($post_type_object->label));?></label>'
                + '</span></div>');

                var elems = '';
                if (!$('select[name="_status_sub"] option[value="<?php echo esc_attr($status);?>"]').length) {
                    elems = elems + '<option value="publish"><?php echo esc_html(PWP::__wp('Public'));?></option>';
                    <?php foreach( $pvt_stati as $status => $status_obj ) :?>
                    elems = elems + '<option value="<?php echo esc_attr($status);?>"><?php echo esc_html($status_obj->label);?></option>';
                    <?php endforeach;?>
                }
                $("select[name='_status_sub']").html('<option value=""><?php esc_html_e('(manual)', 'presspermit-pro');?></option>' + elems);
                $('.inline-edit-status-sub select').prepend('<option value="-1"><?php esc_html_e('&mdash; No Change &mdash;');?></option>');
                $('.inline-edit-status-sub select option[value="-1"]').prop('selected', true);

                $("label.inline-edit-status-sub span.title").append('<span class="pp_disclaimer" title="<?php esc_attr_e('Status may also be altered by category or term', 'presspermit-pro');?>"> * </span>');
                $("select[name='_status_sub']").siblings('span').attr('title', $("select[name='_status_sub']").attr('title'));

                $(document).on('click', 'select[name="_status_sub"]', function (e) {
                    $('input[name="pp_propagate_visibility"]').parent().toggle($(this).val() != -1 && $(this).val() != '');
                });

                <?php } // endif hier ?>

                $(document).on('click', '.inline-edit-row input[name="keep_custom_privacy"]', function()
                {
                    $("input[name='keep_private']").prop("checked", false);
                    $('input.inline-edit-password-input').val('').prop('disabled', true);
                });

                $(document).on('click', '.inline-edit-row input[name="keep_private"]', function()
                {
                    $("input[name='keep_custom_privacy']").prop("checked", false);
                });

                $(document).on('focusin', '.inline-edit-row input.ptitle', function()
                {
                    <?php
                    $pp = presspermit();
                    $scheduled_stati = [];

                    if (!empty($pp->listed_ids[$screen->post_type])) {
                        global $wpdb;
                        $id_csv = implode("','", array_keys($pp->listed_ids[$screen->post_type]));

                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                        if ($results = $wpdb->get_results(
                            "SELECT meta_value, post_id FROM $wpdb->postmeta"
                            . " WHERE meta_key = '_scheduled_status' AND post_id IN ('$id_csv')"  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        )) {
                            foreach ($results as $row) {
                                $scheduled_stati[$row->meta_value][$row->post_id] = true;
                            }
                        }
                    }
                    
                    ?>
                    var rowData, status;
                    id = inlineEditPost.getId(this);
                    rowData = $('#inline_' + id);
                    status = $('._status', rowData).text();
                    keep_status = '';
                    var pvt_stati = ["<?php echo implode('", "', array_map('esc_attr', array_keys($pvt_stati))); ?>"];

                    <?php if (!PPS::privacyStatusesDisabled()): ?>
                        <?php // append elements for pvt stati ?>
                        if (!$('#edit-' + id + ' input[name="keep_custom_privacy"]').length) {
                            var elems = '';
                            <?php foreach( $pvt_stati as $status => $status_obj ) :
                            if ('private' == $status) continue;
                            ?>
                            elems = elems + '<label class="alignleft">&nbsp;&nbsp;<input type="radio" name="keep_custom_privacy" value="<?php echo esc_attr($status);?>"><span class="checkbox-title"> <?php echo esc_html($status_obj->label);?>&nbsp;&nbsp;</span></label>';
                            <?php endforeach; ?>

                            if (elems) {
                                <?php if ( !$post_type_object->hierarchical ) : ?>
                                $('#edit-' + id + ' label.inline-edit-private').before('<br /><br />');
                                <?php endif; ?>

                                $('#edit-' + id + ' label.inline-edit-private').after(elems);
                            }
                        }
                    <?php endif;?>

                    if (-1 !== jQuery.inArray(status, pvt_stati)) {
                        keep_status = status;
                    }
                    <?php if( $scheduled_stati ) : ?>
                    else if ('future' == status) {
                        var id_val = parseInt(id);

                        <?php foreach( $scheduled_stati as $status => $pvt_ids ) : ?>
                        var pvt_ids = new Array(<?php echo esc_attr(implode(', ', array_keys($pvt_ids))); ?>);

                        if (-1 !== jQuery.inArray(id_val, pvt_ids))
                            keep_status = '<?php echo esc_attr($status); ?>';
                        <?php endforeach; ?>
                    }
                    <?php endif; ?>

                    if (keep_status) {
                        if ('private' != keep_status) {
                            $("input[name='keep_private']").prop("checked", false);
                            $("input[name='keep_custom_privacy'][value='" + keep_status + "']").prop("checked", true);
                        }
                    }

                    var current_val = $('._status_sub', rowData).text();
                    $('select[name="_status_sub"] option[value="' + current_val + '"]').prop('selected', true);

                    var current_val = $('._force_vis', rowData).text();
                    var inputs = $("input[name='keep_private'],input[name='keep_custom_privacy']");
                    $(inputs).prop('disabled', current_val != '');
                    if (current_val != '') {
                        $(inputs).parent().attr('title', '<?php esc_html_e('Visibility locked', 'presspermit-pro'); ?>');
                    } else {
                        $(inputs).parent().attr('title', '');
                    }
                });
            });
            //]]>
        </script>
        <?php
    } // end function modify_inline_edit_ui
}
