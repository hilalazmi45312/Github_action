<?php
        $em_address           = get_term_meta( $term->term_id, 'em_address', true );
        $em_lat               = get_term_meta( $term->term_id, 'em_lat', true );
        $em_lng               = get_term_meta( $term->term_id, 'em_lng', true );
        $em_locality          = get_term_meta( $term->term_id, 'em_locality', true );
        $em_state             = get_term_meta( $term->term_id, 'em_state', true );
        $em_country           = get_term_meta( $term->term_id, 'em_country', true );
        $em_postal_code       = get_term_meta( $term->term_id, 'em_postal_code', true );
        $em_zoom_level        = get_term_meta( $term->term_id, 'em_zoom_level', true );
        $em_place_id          = get_term_meta( $term->term_id, 'em_place_id', true );
        $em_established       = get_term_meta( $term->term_id, 'em_established', true );
        $em_type              = get_term_meta( $term->term_id, 'em_type', true );
        $em_seating_organizer = get_term_meta( $term->term_id, 'em_seating_organizer', true );
        $em_facebook_page     = get_term_meta( $term->term_id, 'em_facebook_page', true );
        $em_instagram_page    = get_term_meta( $term->term_id, 'em_instagram_page', true );
        $em_gallery_images    = get_term_meta( $term->term_id, 'em_gallery_images', true );
        $em_display_address_on_frontend = get_term_meta( $term->term_id, 'em_display_address_on_frontend', true );
        $em_is_featured       = get_term_meta( $term->term_id, 'em_is_featured', true );
        $formated_image_ids   = ( is_array( $em_gallery_images ) && count( $em_gallery_images ) ) ? implode( ',', $em_gallery_images ): '';
        $ep_functions = new Eventprime_Basic_Functions;
        $gmap_api_key = $ep_functions->ep_get_global_settings( 'gmap_api_key' );
        $extensions = $ep_functions->ep_get_activate_extensions();
        if( ! empty( $gmap_api_key ) ) {?>
            <tr class="form-field ep-venue-admin-address">
                <th scope="row">
                    <label><?php esc_html_e( 'Address', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input id="em-pac-input" name="em_address" class="em-map-controls" type="text" value="<?php echo esc_html($em_address); ?>" >
                    <div id="map"></div>

                    <div id="type-selector" class="em-map-controls" style="display:none">
                        <input type="radio" name="em_type" id="changetype-all" checked="checked">
                        <label for="changetype-all"><?php esc_html_e('All', 'eventprime-event-calendar-management'); ?></label>

                        <input type="radio" name="em_type" id="changetype-establishment">
                        <label for="changetype-establishment"><?php esc_html_e('Establishments', 'eventprime-event-calendar-management'); ?></label>

                        <input type="radio" name="em_type" id="changetype-address">
                        <label for="changetype-address"><?php esc_html_e('Addresses', 'eventprime-event-calendar-management'); ?></label>

                        <input type="radio" name="em_type" id="changetype-geocode">
                        <label for="changetype-geocode"><?php esc_html_e('Geocodes', 'eventprime-event-calendar-management'); ?></label>
                    </div>
                    <p class="description">
                        <?php esc_html_e( 'This is used for displaying map marker on the event page.', 'eventprime-event-calendar-management' ); ?>
                    </p> 
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-lat">
                <th scope="row">
                    <label><?php esc_html_e( 'Latitude', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_lat); ?>" type="text" id="em_lat" name="em_lat" />
                    <p class="description">
                        <?php esc_html_e( 'Latitude.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-lng">
                <th scope="row">
                    <label><?php esc_html_e( 'Longitude', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_lng); ?>" type="text" id="em_lng" name="em_lng" />
                    <p class="description">
                        <?php esc_html_e( 'Longitude', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-locality">
                <th scope="row">
                    <label><?php esc_html_e( 'Locality', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_locality); ?>" type="text" id="em_locality" name="em_locality" />
                    <p class="description">
                        <?php esc_html_e( 'Locality', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-state"> 
                <th scope="row">
                    <label><?php esc_html_e( 'State', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_state); ?>" type="text" id="em_state" name="em_state" />
                    <p class="description">
                        <?php esc_html_e( 'State', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-country">
                <th scope="row">
                    <label><?php esc_html_e( 'Country', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_country); ?>" type="text" id="em_country" name="em_country" />
                    <p class="description">
                        <?php esc_html_e( 'Country.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-postal">
                <th scope="row">
                    <label><?php esc_html_e( 'Postal Code', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_postal_code); ?>" type="text" id="em_postal_code" name="em_postal_code" />
                    <p class="description">
                        <?php esc_html_e( 'Postal Code', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr>
            <tr class="form-field ep-venue-admin-zoom">
                <th scope="row">
                    <label><?php esc_html_e( 'Zoom Level', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input value="<?php echo esc_html($em_zoom_level); ?>" type="text" id="em_zoom_level" name="em_zoom_level" />
                    <input value="<?php echo esc_html( $em_place_id ); ?>" type="hidden" id="em_place_id" name="em_place_id" />
                    <p class="description">
                        <?php esc_html_e( 'Define how zoomed-in the map is when first loaded. Default is 1. Users will be able to zoom in and out using Google map controls.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr><?php
        } else{?>
            <tr class="form-field ep-venue-admin-address">
                <th scope="row">
                    <label><?php esc_html_e( 'Address', 'eventprime-event-calendar-management' ); ?></label>
                </th>
                <td>
                    <input id="em-pac-input" name="em_address" class="em-map-controls" type="text" value="<?php echo esc_html($em_address); ?>" >
                    <p class="description ep-text-danger">
                        <?php esc_html_e( 'Location Map field is not active as Google Map API is not configured. You can configure it from the Settings->General->Third-Party.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </td>
            </tr><?php
        }?>
        <tr class="form-field ep-venue-admin-display-address">
            <th scope="row">
                <label><?php esc_html_e( 'Display Address', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="form-field ep-venue-admin-display-address">
                    <label class="ep-toggle-btn">
                        <input type="checkbox" id="is_display_address" name="em_display_address_on_frontend" value="<?php echo esc_html( $em_display_address_on_frontend ); ?>" <?php if( $em_display_address_on_frontend == 1 ){ echo 'checked="checked"'; }?> />
                        <span class="ep-toogle-slider round"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Display Address On Frontend.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
        <tr class="form-field ep-venue-admin-established">
            <th scope="row">
                <label><?php esc_html_e( 'Established', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input value="<?php echo esc_html( $em_established ); ?>" type="text" id="em_established" name="em_established" />
                <p class="description">
                    <?php esc_html_e( 'When the Venue opened for public.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>

        <tr class="form-field ep-venue-admin-type">
            <th scope="row">
                <label><?php esc_html_e( 'Seating Type', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <select required name="em_type" class="ep-box-w-100">
                    <option value="standings" <?php if($em_type == 'standings'){ echo 'selected="selected"';}?> ><?php esc_html_e( 'Standing', 'eventprime-event-calendar-management' );?></option>
                    <?php 
                    $seating_disabled = 'disabled';
                    // if ( ! empty( $em->extensions ) && in_array( 'live_seating', $em->extensions ) ) {
                    if ( ! empty( $extensions ) && in_array( 'Eventprime_Live_Seating', $extensions ) ) {
                        
                        $seating_disabled = '';
                    } 
                    // if ( ! empty( $em->extensions ) && in_array( 'live_seating', $em->extensions ) ) {
                    if ( ! empty( $extensions ) && in_array( 'Eventprime_Live_Seating', $extensions ) ) {
                        ?>
                        <option value="seats" <?php if($em_type == 'seats'){ echo 'selected="selected"';}?>><?php esc_html_e( 'Seating', 'eventprime-event-calendar-management' );?></option><?php
                    } else{?>
                        <option value="seats" disabled="disabled"><?php esc_html_e( 'Seating - requires seating extension', 'eventprime-event-calendar-management' );?></option><?php
                    }?>
                </select>
                <p class="description">
                    <?php esc_html_e( 'Type of seating arrangement- Standing or Seating.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        <tr class="form-field ep-venue-admin-operator">
            <th scope="row">
                <label><?php esc_html_e( 'Operator', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input value="<?php echo esc_html($em_seating_organizer); ?>" type="text" id="em_seating_organizer" name="em_seating_organizer" />
                <p class="description">
                    <?php esc_html_e( 'Venue coordinator name or contact details.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        <tr class="form-field ep-venue-admin-facebook">
            <th scope="row">
                <label><?php esc_html_e( 'Facebook Page', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input value="<?php echo esc_html($em_facebook_page); ?>" type="text" id="em_facebook_page" name="em_facebook_page" placeholder="<?php esc_html_e('https://www.facebook.com/XYZ/', 'eventprime-event-calendar-management');?>" />
                <p class="description">
                    <?php esc_html_e( 'Facebook page URL of the Venue, if available. Eg.:https://www.facebook.com/XYZ/', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        <tr class="form-field ep-venue-admin-instagram">
            <th scope="row">
                <label><?php esc_html_e( 'Instagram Page', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input value="<?php echo esc_html($em_instagram_page); ?>" type="text" id="em_instagram_page" name="em_instagram_page" placeholder="<?php esc_html_e('https://www.instagram.com/stories/XYZ', 'eventprime-event-calendar-management');?>" />
                <p class="description">
                    <?php esc_html_e( 'Instagram page URL of the Venue, if available. Eg.:https://www.instagram.com/stories/XYZ', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>

        <tr class="form-field ep-venue-admin-image-wrap">
            <th scope="row">
                <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div id="ep-venue-admin-image" style="float: left; margin-right: 10px;">
                    <?php if( is_array( $em_gallery_images ) && count( $em_gallery_images ) ) {
                        foreach( $em_gallery_images as $image_id ) {
                            if( ! empty( $image_id ) ) {
                                $attach_url = wp_get_attachment_image_url( $image_id );
                                if( ! empty( $attach_url ) ) {?>
                                    <span class="ep-venue-gallery"><i class="remove-gallery-venue dashicons dashicons-trash ep-text-danger"></i>
                                        <img src="<?php echo esc_url( $attach_url );?>" data-image_id="<?php echo esc_attr($image_id);?>"/>
                                    </span><?php
                                }
                            }
                        }
                    }?>
                </div>
                <div>
                    <input type="hidden" id="ep_venue_image_id" name="em_gallery_images" value="<?php echo esc_html( $formated_image_ids ); ?>" />
                    <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                </div>
            </td>
        </tr>
        <tr class="form-field ep-venue-admin-featured">
            <th scope="row">
                <label><?php esc_html_e( 'Featured', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div class="form-field ep-venue-admin-featured">
                    <label class="ep-toggle-btn">
                        <input type="checkbox" id="is_featured" name="em_is_featured" value="<?php echo esc_html($em_is_featured); ?>" <?php if($em_is_featured == 1){ echo 'checked="checked"'; }?> />
                        <span class="ep-toogle-slider round"></span>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Check if you want to make this Venue featured.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php do_action("ep_extend_edit_event_venue_form", $term); ?>
        <?php wp_nonce_field( 'em_event_venue_nonce_action', 'em_event_venue_nonce_field' );