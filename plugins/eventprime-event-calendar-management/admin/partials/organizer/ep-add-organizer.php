<?php
$ep_functions = new Eventprime_Basic_Functions;
?>
<div class="form-field ep-organizer-admin-phone">
            <label for="em_organizer_phones">
                <?php esc_html_e( 'Phone', 'eventprime-event-calendar-management' ); ?>
            </label>
            <div class="ep-organizers-phone">
                <span class="ep-org-phone ep-org-data-field">
                    <input type="text" class="ep-org-data-input" name="em_organizer_phones[]" placeholder="<?php echo esc_attr('Phone', 'eventprime-event-calendar-management');?>">
                    <button type="button" class="ep-org-add-more button button-primary" data-input="phone" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                        +
                    </button>
                </span>
                <p class="emnote emeditor">
                    <?php esc_html_e( "Add the Organizer's phone numbers.", 'eventprime-event-calendar-management' ); ?>
                </p>
            </div>
        </div>
        <div class="form-field ep-organizer-admin-email">
            <label for="em_organizer_emails">
                <?php esc_html_e( 'Email', 'eventprime-event-calendar-management' ); ?>
            </label>
            <div class="ep-organizers-email">
                <span class="ep-org-email ep-org-data-field">
                    <input type="email" name="em_organizer_emails[]" placeholder="<?php echo esc_attr('Email', 'eventprime-event-calendar-management');?>">
                    <button type="button" class="ep-org-add-more button button-primary" data-input="email" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>">
                        +
                    </button>
                </span>
                <p class="emnote emeditor">
                    <?php esc_html_e( "Add the Organizer's email addresses.", 'eventprime-event-calendar-management' ); ?>
                </p>
            </div>
        </div>
        <div class="form-field ep-organizer-admin-website">
            <label for="em_organizer_websites">
                <?php esc_html_e( 'Website', 'eventprime-event-calendar-management' ); ?>
            </label>
            <div class="ep-organizers-website">
                <span class="ep-org-website ep-org-data-field">
                    <input type="text" name="em_organizer_websites[]" placeholder="<?php echo esc_attr('Website', 'eventprime-event-calendar-management');?>">
                    <button type="button" class="ep-org-add-more button button-primary" data-input="website" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>">
                        +
                    </button>
                </span>
                <p class="emnote emeditor">
                    <?php esc_html_e( "Add the Organizer's website URLs.", 'eventprime-event-calendar-management' ); ?>
                </p>
            </div>
        </div>

        <div class="form-field ep-organizer-admin-image-wrap">
            <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            <div id="ep-organizer-admin-image"></div>
            <div>
                <input type="hidden" id="ep_organizer_image_id" name="em_image_id" />
                <button type="button" class="upload_image_button button"><?php echo esc_attr( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                <p class="emnote emeditor">
                    <?php esc_html_e( 'Image or icon of the Event Organizer.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </div>
        </div>
        
        <?php $social_links = $ep_functions->ep_social_sharing_fields();
        foreach( $social_links as $key => $links) { ?>
            <div class="form-field ep-organizer-admin-social ep-organizer-<?php echo esc_attr($key);?>">
                <label for="<?php echo esc_attr($key);?>" >
                    <?php echo wp_kses_post($links);?>
                </label>
                <input type="url" name="em_social_links[<?php echo esc_attr($key);?>]" placeholder="<?php echo sprintf( esc_html__( 'https://www.%s.com/XYZ/', 'eventprime-event-calendar-management' ), esc_attr(strtolower( $links )) ); ?>" data-key="<?php esc_html_e($key);?>">
                <p class="emnote emeditor">
                    <?php echo sprintf( esc_html__( 'Enter %s URL of the Organizer, if available. Eg.:https://www.%s.com/XYZ/', 'eventprime-event-calendar-management' ), wp_kses_post($links), esc_attr(strtolower( $links )) ); ?>
                </p>
            </div>
            <?php
        }?>

        <div class="form-field ep-organizer-admin-featured">
            <label for="is_featured">
				<?php esc_html_e( 'Featured', 'eventprime-event-calendar-management' ); ?>
                <label class="ep-toggle-btn">
                    <input type="checkbox" id="is_featured" name="em_is_featured" />
                    <span class="ep-toogle-slider round"></span>
                </label>
                <p class="emnote emeditor">
                    <?php esc_html_e( 'Check if you want to make this organizer featured.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </label>
        </div>
        <?php do_action("ep_extend_add_organizer_form"); ?>
        <?php wp_nonce_field( 'em_event_organizer_nonce_action', 'em_event_organizer_nonce_field' );