<?php
namespace PublishPress\Permissions\Statuses;

/**
 * Triggers class
 *
 * Deals with content, user or site changes which may require a 
 * corresponding permissions data update or other action. 
 * 
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2024, PublishPress
 *
 */
class Triggers
{
    var $filtered_post_status = [];

    function __construct() {
        // This script normally executes on plugin load, 
        // but can be bypassed for front end URLs if defined('PP_NO_FRONTEND_ADMIN')
        //
        add_action('save_post', [$this, 'actSavePost'], 10, 2);
        add_action('delete_post', [$this, 'actDeletePost'], 10, 3);

        add_filter('pre_post_status', [$this, 'fltPostStatus'], 20);

        add_filter('wp_insert_post_data', [$this, 'fltPostData'], 50, 2);
    }

    function actSavePost($post_id, $post)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if ('revision' == $post->post_type) return;

        if (!empty(presspermit()->flags['ignore_save_post'])) {
            return;
        }

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/PostSave.php');
        return PostSave::actSavePost($post_id, $post);
    }

    function actDeletePost($object_id)
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/ItemDelete.php');
        ItemDelete::actDeletePost($object_id);
    }

    function fltPostData($post_data, $post_arr) {
        // If 'pre_post_status' filtered the status to a custom privacy status, 
        // re-filter on 'wp_insert_post_data' to counteract status being subsequently forced back to 'draft'
        if (('draft' == $post_data['post_status'])
        && !empty($post_arr['ID'])
        && !empty($this->filtered_post_status[$post_arr['ID']])
        && ($this->filtered_post_status[$post_arr['ID']] != $post_data['post_status'])
        ) {
            if ($status_obj = get_post_status_object($this->filtered_post_status[$post_arr['ID']])) {
                if (('private' != $status_obj->name) && !empty($status_obj->private)) {
                    $post_data['post_status'] = $this->fltPostStatus($post_data['post_status'], ['filter_draft_status' => true]);
                }
            }
        }

        return $post_data;
    }

    function fltPostStatus($status, $args = [])
    {
        global $pagenow;
        if (in_array($status, ['inherit', 'trash']) 
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ('async-upload.php' == $pagenow)) {
            return $status;
        }

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/PostSave.php');

        $orig_status = $status;

        $status = PostSave::fltPostStatus($status, $args);
        $status = PostSave::flt_force_visibility($status);

        if ($orig_status != $status) {
            if (!$post_id = presspermit()->getCurrentSanitizePostID()) {
                $post_id = PWP::getPostID();
            }

            if ($post_id) {
                if (!in_array($status, ['draft'])) {
                    $this->filtered_post_status[$post_id]= $status;
                }
            }
        }

        return $status;
    }
}
