<?php
namespace PublishPress\Permissions\Statuses\UI\Gutenberg;

class PostEdit
{
    function __construct() 
    {
        if ($post_id = PWP::getPostID()) {
            if (defined('PUBLISHPRESS_REVISIONS_VERSION') && rvy_in_revision_workflow($post_id)) {
                return;
            }
        }
        
        add_action('rest_api_init', [$this, 'act_status_control_scripts']);

        add_filter('publishpress_statuses_can_publish', [$this, 'flt_can_publish'], 10, 2);
    }

    // If PressPermit permissions filtering is enabled for this post type, load additional js to support it
    public function act_status_control_scripts() {
        if (self::isPostTypeEnabled()) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditPrivacy.php');
            new PostEditPrivacy();

            if (is_post_type_hierarchical(PWP::findPostType())) {
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditPrivacySub.php');
                new PostEditPrivacySub();
            }
        }
    }

	// If PressPermit permissions filtering is disabled for post type, don't load custom js for status dropdown and button labeling
    // Note, though, that 'pp_custom_status_list' filter still applies any per-type status availability set in Permissions > Post Statuses
    private static function isPostTypeEnabled($post_type = '') {
        global $post, $typenow;

        if (!empty($post)) {
            $post_type = $post->post_type;
        } else {
            if ($post_id = PWP::getPostID()) {
                $post_type = get_post_field('post_type', $post_id);
            } else {
                $post_type = (!empty($typenow)) ? $typenow : '';
            }
        }

        $statuses_type_enabled = (class_exists('PublishPress_Statuses')) ? ! \PublishPress_Statuses::DisabledForPostType($post_type) : true;

        return $statuses_type_enabled && in_array($post_type, presspermit()->getEnabledPostTypes(), true);
    }

    function flt_can_publish($can_publish, $args) {
        $args = (array) $args;
        $defaults = ['post_id' => 0, 'is_published' => false, 'post_type' => ''];

        if ($can_publish) {
            return $can_publish;
        }

        foreach ($defaults as $var => $default_val) {
            $$var = (isset($args[$var])) ? $args[$var] : $default_val;
        }

        $user = presspermit()->getUser();

        $operation = (!$is_published && presspermit()->getOption('publish_exceptions')) ? 'publish' : 'edit';
        $additional_ids = $user->getExceptionPosts($operation, 'additional', $post_type);

        $can_publish = in_array($post_id, $additional_ids);

        return $can_publish;
    }
}
