<?php
$ep_functions = new Eventprime_Basic_Functions;
$gmap_api_key = $ep_functions->ep_get_global_settings( 'gmap_api_key' );
$extensions = $ep_functions->ep_get_activate_extensions();
        if( ! empty( $gmap_api_key ) ) {?>
            <div class="form-field ep-venue-admin-address">
                <label for="em_address">
                    <?php esc_html_e( 'Address', 'eventprime-event-calendar-management' ); ?>
                </label>
                <input id="em-pac-input" name="em_address" class="em-map-controls" type="text">
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
            </div>
            <p class="emnote emeditor">
                <?php esc_html_e('This is used for displaying map marker on the event page.', 'eventprime-event-calendar-management'); ?>
            </p>

            <div class="form-field ep-venue-admin-field ep-venue-admin-location ep-d-flex">
                <div class="ep-venue-admin-lat ep-venue-left-field ep-box-w-50">
                    <input type="text" name="em_lat" id="em_lat" placeholder="<?php esc_html_e('Latitude', 'eventprime-event-calendar-management');?>">
                </div>
                <div class="ep-venue-admin-lang ep-venue-right-field ep-box-w-50">
                    <input type="text" name="em_lng" id="em_lng" placeholder="<?php esc_html_e('Longitude', 'eventprime-event-calendar-management');?>">
                </div>
            </div>

            <div class="form-field ep-venue-admin-field ep-venue-admin-location ep-d-flex">
                <div class="ep-venue-admin-locality ep-venue-left-field ep-box-w-50">
                    <input type="text" name="em_locality" id="em_locality" value="" placeholder="<?php esc_html_e('Locality', 'eventprime-event-calendar-management');?>">
                </div>
                <div class="ep-venue-admin-state ep-venue-left-field ep-box-w-50">
                    <input type="text" name="em_state" id="em_state" placeholder="<?php esc_html_e('State', 'eventprime-event-calendar-management');?>">
                </div>
            </div>

            <div class="form-field ep-venue-admin-field ep-venue-admin-location2 ep-d-flex">
                <div class="ep-venue-admin-country ep-venue-right-field ep-box-w-50">
                    <input type="text" name="em_country" id="em_country" placeholder="<?php esc_html_e('Country', 'eventprime-event-calendar-management');?>">
                </div>
                <div class="ep-venue-admin-postal-code ep-venue-left-field ep-box-w-50">
                    <input type="text" name="em_postal_code" id="em_postal_code" placeholder="<?php esc_html_e('Postal Code', 'eventprime-event-calendar-management');?>">
                </div>
            </div>

            <div class="form-field ep-venue-admin-field ep-venue-admin-location3 ep-d-flex">
                <div class="ep-venue-admin-zoom ep-venue-right-field">
                    <input type="number" name="em_zoom_level" id="em_zoom_level" placeholder="<?php esc_html_e('Zoom Level', 'eventprime-event-calendar-management');?>" min="0" step="1">
                    <p class="emnote emeditor">
                        <?php esc_html_e('Define how zoomed-in the map is when first loaded. Default is 1. Users will be able to zoom in and out using Google map controls.', 'eventprime-event-calendar-management'); ?>
                    </p>
                </div>
            </div>
            

            <!-- hidden field to get place id -->
            <input type="hidden" name="em_place_id" id="em_place_id" value=""><?php
        } else{?>
            <div class="form-field ep-venue-admin-address">
                <p class="emnote emeditor ep-text-danger">
                    <?php esc_html_e( 'Location Map field is not active as Google Map API is not configured. You can configure it from the Settings->General->Third-Party.', 'eventprime-event-calendar-management'); ?>
                </p>
                <label for="em_address">
                    <?php esc_html_e( 'Address', 'eventprime-event-calendar-management' ); ?>
                </label>
                <input id="em-pac-input" name="em_address" class="em-map-controls" type="text">
            </div><?php
        }?>

        <div class="form-field ep-venue-admin-display-address">
            <label for="display_address_on_frontend">
                <?php esc_html_e( 'Display Address', 'eventprime-event-calendar-management' ); ?>
                <label class="ep-toggle-btn">
                    <input type="checkbox" id="display_address_on_frontend" name="em_display_address_on_frontend" value="1" checked />
                    <span class="ep-toogle-slider round"></span>
                </label>
                <p class="emnote emeditor">
                    <?php esc_html_e( 'Display Address On Frontend.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </label>
        </div>

        <div class="form-field ep-venue-admin-established ">
            <input type="text" name="em_established" id="em_established" class="epDatePicker" placeholder="<?php esc_html_e( 'Established', 'eventprime-event-calendar-management' );?>" autocomplete="off">
        </div>
        <div class="form-field ep-venue-admin-reset-bt">
            <input type="button" class="button" value="<?php esc_html_e( 'Reset', 'eventprime-event-calendar-management' );?>" id="em_venue_esh_reset" />
            <p class="emnote emeditor">
                <?php esc_html_e( 'When the Venue opened for public.', 'eventprime-event-calendar-management' ); ?>
            </p>
        </div>

        <div class="form-field ep-venue-admin-type">
            <label for="em_type">
                <?php esc_html_e( 'Seating Type', 'eventprime-event-calendar-management' ); ?>
            </label>
            <select required name="em_type" class="ep-box-w-100">
                <option value="standings"><?php esc_html_e( 'Standing', 'eventprime-event-calendar-management' );?></option>
                <?php 
                // if ( ! empty( $em->extensions ) && in_array( 'live_seating', $em->extensions ) ) {
                if ( ! empty( $extensions ) && in_array( 'Eventprime_Live_Seating', $extensions ) ) {
                    ?>
                    <option value="seats"><?php esc_html_e( 'Seating', 'eventprime-event-calendar-management' );?></option><?php
                } else{?>
                    <option value="seats" disabled="disabled"><?php esc_html_e( 'Seating - requires seating extension', 'eventprime-event-calendar-management' );?></option><?php
                }?>
            </select>
            <p class="emnote emeditor">
                <?php esc_html_e('Type of seating arrangement- Standing or Seating.', 'eventprime-event-calendar-management'); ?>
            </p>
        </div>

        <div class="form-field ep-venue-admin-operator">
            <label for="em_seating_organizer">
                <?php esc_html_e( 'Operator', 'eventprime-event-calendar-management' ); ?>
            </label>
            <input type="text" name="em_seating_organizer" id="em_seating_organizer" placeholder="<?php esc_html_e('Operator', 'eventprime-event-calendar-management');?>">
            <p class="emnote emeditor">
                <?php esc_html_e( 'Venue coordinator name or contact details.', 'eventprime-event-calendar-management'); ?>
            </p>
        </div>

        <div class="form-field ep-venue-admin-facebook">
            <label for="em_facebook_page">
                <?php esc_html_e( 'Facebook Page', 'eventprime-event-calendar-management' ); ?>
            </label>
            <input type="text" name="em_facebook_page" id="em_facebook_page" placeholder="<?php esc_html_e('https://www.facebook.com/XYZ/', 'eventprime-event-calendar-management');?>">
            <p class="emnote emeditor">
                <?php esc_html_e('Facebook page URL of the Venue, if available. Eg.:https://www.facebook.com/XYZ/', 'eventprime-event-calendar-management'); ?>
            </p>
        </div>

        <div class="form-field ep-venue-admin-instagram">
            <label for="em_instagram_page">
                <?php esc_html_e( 'Instagram Page', 'eventprime-event-calendar-management' ); ?>
            </label>
            <input type="text" name="em_instagram_page" id="em_instagram_page" placeholder="<?php esc_html_e('https://www.instagram.com/stories/XYZ', 'eventprime-event-calendar-management');?>">
            <p class="emnote emeditor">
                <?php esc_html_e('Instagram page URL of the Venue, if available. Eg.:https://www.instagram.com/stories/XYZ', 'eventprime-event-calendar-management'); ?>
            </p>
        </div>

        <div class="form-field ep-venue-admin-image-wrap">
            <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            <div id="ep-venue-admin-image" class="ep-d-flex ep-flex-wrap ep-mb-3"></div>
            <div class="ep-box-w-100">
                <input type="hidden" id="ep_venue_image_id" name="em_gallery_images" />
                <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                <p class="emnote emeditor">
                    <?php esc_html_e(' Image or icon of the Venue. Will be displayed on the Venue directory page.', 'eventprime-event-calendar-management'); ?>
                </p>
            </div>
        </div>
        <div class="form-field ep-venue-admin-featured">
            <label for="is_featured">
                <?php esc_html_e( 'Featured', 'eventprime-event-calendar-management' ); ?>
                <label class="ep-toggle-btn">
                    <input type="checkbox" id="is_featured" name="em_is_featured" />
                    <span class="ep-toogle-slider round"></span>
                </label>
                <p class="emnote emeditor">
                    <?php esc_html_e('Check if you want to make this Venue featured.', 'eventprime-event-calendar-management'); ?>
                </p>
            </label>
        </div>
        <?php do_action("ep_extend_add_event_venue_form"); ?>
        <?php wp_nonce_field( 'em_event_venue_nonce_action', 'em_event_venue_nonce_field' );