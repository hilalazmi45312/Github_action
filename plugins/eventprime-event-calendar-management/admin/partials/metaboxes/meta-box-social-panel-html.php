<?php

/**
 * Event Social Information meta panel html.
 */
defined('ABSPATH') || exit;
$em_social_links = (array)get_post_meta($post->ID, 'em_social_links', true);?>
<div id="ep_event_social_data" class="panel ep_event_options_panel">
    <div class="ep-box-wrap ep-my-3">
        <div class="ep-box-row">
            <?php 
            $text = esc_html__('Social Information','eventprime-event-calendar-management');
            $link = 'https://theeventprime.com/how-to-add-social-media-links-to-a-wordpress-event/';
            ?>
            <div class="ep-box-col-12 ep-mb-3">
                <?php $ep_functions->ep_documentation_link_notice_html($text,$link);?>
            </div>
            <?php
            $social_links = $ep_functions->ep_social_sharing_fields();
            foreach ($social_links as $key => $links) { ?>
                <div class="ep-box-col-12 ep-mb-3 ep-meta-box-section">
                    <div class="ep-box-row">
                        <div class="ep-box-col-4">
                            <div class="ep-meta-box-title">
                                <?php echo esc_html( $links ); ?>
                            </div>
                            <div class="ep-meta-box-data">
                                <input class="ep-form-control" type="text" name="em_social_links[<?php echo esc_attr($key); ?>]" placeholder="<?php echo esc_attr( $links ); ?>" value="<?php echo isset( $em_social_links[$key] ) ? esc_html( $em_social_links[$key] ) : '' ; ?>">
                            </div>
                        </div>
                    </div>
                </div><?php
            } ?>
        </div>
    </div>
</div>