<?php
$ep_functions = new Eventprime_Basic_Functions;
$em_organizer_phones = get_term_meta( $term->term_id, 'em_organizer_phones', true );
	    $em_organizer_emails = get_term_meta( $term->term_id, 'em_organizer_emails', true );
        $em_organizer_websites = get_term_meta( $term->term_id, 'em_organizer_websites', true );
        $em_social_links = (array)get_term_meta( $term->term_id, 'em_social_links', true );
	    $em_image_id = get_term_meta( $term->term_id, 'em_image_id', true );
	    $image = '';
        if($em_image_id) {
            $image = wp_get_attachment_image_url( $em_image_id );
        }
	    $em_is_featured = get_term_meta( $term->term_id, 'em_is_featured', true );
            ?>
        <tr class="form-field ep-type-admin-phone">
            <th scope="row">
                <label><?php esc_html_e( 'Phone', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="ep-organizers-phone">
                    <?php if( ! empty( $em_organizer_phones ) && count( $em_organizer_phones ) > 0 ) {
                        $p = 0; 
                        foreach( $em_organizer_phones as $phones ) { ?>
                            <span class="ep-org-phone ep-org-data-field">
                                <input type="text" class="ep-org-data-input" value="<?php echo esc_attr($phones);?>" name="em_organizer_phones[]" placeholder="<?php echo esc_attr('Phone', 'eventprime-event-calendar-management');?>">
                                <?php if( $p == 0 ) { ?>
                                    <button type="button" class="ep-org-add-more button button-primary" data-input="phone" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        +
                                    </button><?php
                                } else{ ?>
                                    <button type="button" class="ep-org-remove button button-primary" data-input="phone" title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        -
                                    </button><?php
                                }?>
                            </span>
                            <p class="emnote description">
                                <?php esc_html_e( "Add the Organizer's phone numbers.", 'eventprime-event-calendar-management' ); ?>
                            </p><?php
                            $p++;
                        }
                    } else{?>
                        <span class="ep-org-phone ep-org-data-field">
                            <input type="text" class="ep-org-data-input" value="" name="em_organizer_phones[]" placeholder="<?php echo esc_attr('Phone', 'eventprime-event-calendar-management');?>">
                                <button type="button" class="ep-org-add-more button button-primary" data-input="phone" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                    +
                                </button>
                        </span>
                        <p class="emnote description">
                            <?php esc_html_e( "Add the Organizer's phone numbers.", 'eventprime-event-calendar-management' ); ?>
                        </p><?php
                    }?>
                </div>
            </td>
        </tr>
        <tr class="form-field ep-type-admin-email">
            <th scope="row">
                <label><?php esc_html_e( 'Email', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="ep-organizers-email">
                    <?php if( ! empty( $em_organizer_emails ) && count( $em_organizer_emails ) > 0 ) {
                        $p = 0; 
                        foreach( $em_organizer_emails as $emails ) { ?>
                            <span class="ep-org-email ep-org-data-field">
                                <input type="text" class="ep-org-data-input" value="<?php echo esc_attr($emails);?>" name="em_organizer_emails[]" placeholder="<?php echo esc_attr('Email', 'eventprime-event-calendar-management');?>">
                                <?php if( $p == 0 ) { ?>
                                    <button type="button" class="ep-org-add-more button button-primary" data-input="email" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        +
                                    </button><?php
                                } else{ ?>
                                    <button type="button" class="ep-org-remove button button-primary" data-input="email" title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        -
                                    </button><?php
                                }?>
                            </span>
                            <p class="emnote description">
                                <?php esc_html_e( "Add the Organizer's email addresses.", 'eventprime-event-calendar-management' ); ?>
                            </p><?php
                            $p++;
                        }
                    } else{?>
                        <span class="ep-org-email ep-org-data-field">
                            <input type="text" class="ep-org-data-input" value="" name="em_organizer_emails[]" placeholder="<?php echo esc_attr('Email', 'eventprime-event-calendar-management');?>">
                                <button type="button" class="ep-org-add-more button button-primary" data-input="email" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                    +
                                </button>
                        </span>
                        <p class="emnote description">
                            <?php esc_html_e( "Add the Organizer's email addresses.", 'eventprime-event-calendar-management' ); ?>
                        </p><?php
                    }?>
                </div>
            </td>
        </tr>
        <tr class="form-field ep-type-admin-website">
            <th scope="row">
                <label><?php esc_html_e( 'Website', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="ep-organizers-website">
                    <?php if( ! empty( $em_organizer_websites ) && count( $em_organizer_websites ) > 0 ) {
                        $p = 0; 
                        foreach( $em_organizer_websites as $websites ) { ?>
                            <span class="ep-org-website ep-org-data-field">
                                <input type="text" class="ep-org-data-input" value="<?php echo esc_attr($websites);?>" name="em_organizer_websites[]" placeholder="<?php echo esc_attr('Website', 'eventprime-event-calendar-management');?>">
                                <?php if( $p == 0 ) { ?>
                                    <button type="button" class="ep-org-add-more button button-primary" data-input="website" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        +
                                    </button><?php
                                } else{ ?>
                                    <button type="button" class="ep-org-remove button button-primary" data-input="website" title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                        -
                                    </button><?php
                                }?>
                            </span>
                            <p class="emnote description">
                                <?php esc_html_e( "Add the Organizer's website URLs.", 'eventprime-event-calendar-management' ); ?>
                            </p><?php
                            $p++;
                        }
                    } else{?>
                        <span class="ep-org-website ep-org-data-field">
                            <input type="text" class="ep-org-data-input" value="" name="em_organizer_websites[]" placeholder="<?php echo esc_attr('Website', 'eventprime-event-calendar-management');?>">
                                <button type="button" class="ep-org-add-more button button-primary" data-input="website" title="<?php echo esc_attr('Add More', 'eventprime-event-calendar-management');?>" data-remove_title="<?php echo esc_attr('Remove', 'eventprime-event-calendar-management');?>" >
                                    +
                                </button>
                        </span>
                        <p class="emnote description">
                            <?php esc_html_e( "Add the Organizer's website URLs.", 'eventprime-event-calendar-management' ); ?>
                        </p><?php
                    }?>
                </div>
            </td>
        </tr>
        
        <tr class="form-field ep-type-admin-image-wrap">
            <th scope="row">
                <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div id="ep-organizer-admin-image" style="float: left; margin-right: 10px;">
                    <?php if( ! empty( $image ) ) {?>
                        <i class="remove_image_button dashicons dashicons-trash ep-text-danger"></i>
                        <img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /><?php
                    }?>
                </div>
                <div style="line-height: 60px;">
                    <input type="hidden" id="ep_organizer_image_id" name="em_image_id" value="<?php echo esc_attr( $em_image_id ); ?>" />
                    <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                    <p class="description">
                        <?php esc_html_e( 'Image or icon of the Event Organizer.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php $social_links =$ep_functions->ep_social_sharing_fields();
        foreach( $social_links as $key => $links) { 
            $sl = ( ! empty( $em_social_links[$key] ) ? $em_social_links[$key] : '' );?>
            <tr class="form-field ep-type-admin-social">
                <th scope="row">
                    <label><?php echo esc_html( $links ); ?></label>
                </th>
                <td>
                    <input type="text" class="ep-org-data-social-input" value="<?php echo esc_attr( $sl );?>" name="em_social_links[<?php echo esc_attr( $key );?>]" placeholder="<?php echo esc_attr( $links ); ?>" >
                    <p class="description">
                        <?php echo sprintf( esc_html__( 'Enter %s link', 'eventprime-event-calendar-management' ), wp_kses_post($links) ); ?>
                    </p>
                </td>
            </tr><?php
        }?>

        <tr class="form-field ep-type-admin-featured">
            <th scope="row">
                <label><?php esc_html_e( 'Featured', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="form-field ep-type-admin-featured">
                    <label class="ep-toggle-btn">
                        <input type="checkbox" id="is_featured" name="em_is_featured" value="<?php echo esc_attr($em_is_featured); ?>" <?php if($em_is_featured == 1){ echo 'checked="checked"'; }?> />
                        <span class="ep-toogle-slider round"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Check if you want to make this organizer featured.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php do_action("ep_extend_edit_organizer_form", $term->term_id); ?>
        <?php wp_nonce_field( 'em_event_organizer_nonce_action', 'em_event_organizer_nonce_field' );
