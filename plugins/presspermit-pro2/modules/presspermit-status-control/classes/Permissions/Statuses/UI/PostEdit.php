<?php
namespace PublishPress\Permissions\Statuses\UI;

class PostEdit
{
    private static function isPostTypeEnabled($post_type = '') {
        global $post;

        $post_type = (!empty($post)) ? $post->post_type : get_post_field('post_type', \PublishPress\PWP::getPostID());

        $statuses_type_enabled = (class_exists('PublishPress_Statuses')) ? ! \PublishPress_Statuses::DisabledForPostType($post_type) : true;

        return $status_type_enabled && in_array($post_type, presspermit()->getEnabledPostTypes(), true);
    }


}
