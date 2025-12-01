<?php

$ages_groups = array(
            'all' => esc_html__( 'All', 'eventprime-event-calendar-management' ),
            'parental_guidance' => esc_html__( 'All ages but parental guidance', 'eventprime-event-calendar-management' ),
            'custom_group' => esc_html__(' Custom Age', 'eventprime-event-calendar-management' )
        );
        
	    $em_color = get_term_meta( $term->term_id, 'em_color', true );
	    $em_type_text_color = get_term_meta( $term->term_id, 'em_type_text_color', true );
	    $em_image_id = get_term_meta( $term->term_id, 'em_image_id', true );
	    $image = '';
        if( ! empty( $em_image_id ) ) {
            $image = wp_get_attachment_image_url( $em_image_id );
        }
	    $em_is_featured = get_term_meta( $term->term_id, 'em_is_featured', true );
        $em_age_group = get_term_meta( $term->term_id, 'em_age_group', true );
        $custom_group = '';
        if( $em_age_group == 'custom_group' ){
            $custom_group = get_term_meta( $term->term_id, 'em_custom_group', true ); 
        }?>
        <tr class="form-field ep-type-admin-back-color">
            <th scope="row">
                <label><?php esc_html_e( 'Background Color', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input data-jscolor="{}" value="<?php echo esc_attr($em_color); ?>" type="text" id="color" name="em_color" />
                <p class="description">
                    <?php esc_html_e( 'Background color for events of this type when they appear on the events calendar.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        <tr class="form-field ep-type-admin-text-color">
            <th scope="row">
                <label><?php esc_html_e( 'Text Color', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <input data-jscolor="{}" value="<?php echo esc_attr($em_type_text_color); ?>" type="text" id="type_text_color" name="em_type_text_color" />
                <p class="description">
                    <?php esc_html_e( 'Text color for events of this type when they appear on the events calendar. Can be overridden for individual events from their respective settings.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        <tr class="form-field ep-type-admin-age-group-selector">
            <th scope="row">
                <?php esc_html_e( 'Age Group', 'eventprime-event-calendar-management' ); ?>
            </th>
            <td>
                <select name="em_age_group" id="ep-event-type-age-group">
                    <?php foreach( $ages_groups as $key => $group ):?>
                        <option value="<?php echo esc_attr( $key );?>" <?php echo ( $em_age_group == esc_attr( $key ) ) ? 'selected' : '';?>><?php echo esc_html( $group );?></option>
                    <?php endforeach;?>
                </select>
                <div class="form-field em-type-admin-age-group-custom" style="<?php if( $em_age_group != 'custom_group' ) {echo 'display:none;';};?>">
                    <div class="ep-age-bar-fields">
                        <input type="text" id="ep-custom-group" name="em_custom_group" value="<?php echo esc_attr( $custom_group );?>" readonly style="border:0; color:#f6931f; font-weight:bold;">
                        <div id="ep-custom-group-range"></div>
                    </div>
                </div>
                <p class="description">
                    <?php esc_html_e( 'Valid age group for the Event. This will be displayed on Event page.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </td>
        </tr>
        
        <tr class="form-field ep-type-admin-image-wrap">
            <th scope="row">
                <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            </th>
            <td>
                <div id="ep-type-admin-image" style="float: left; margin-right: 10px;">
                    <span class="ep-event-type-image">
                        <?php if( ! empty( $image ) ) {?>
                            <i class="remove_image_button dashicons dashicons-trash ep-text-danger"></i>
                            <img src="<?php echo esc_url( $image ); ?>" width="60px" height="60px" /><?php
                        }?>
                    </span>
                </div>
                <div style="line-height: 60px;">
                    <input type="hidden" id="ep_type_image_id" name="em_image_id" value="<?php echo esc_attr( $em_image_id ); ?>" />
                    <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                    <p class="description">
                        <?php esc_html_e( 'Image or icon of the Event Type. Will be displayed on the Event Types directory page.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
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
                        <?php esc_html_e( 'Check if you want to make this event type featured.', 'eventprime-event-calendar-management' ); ?>
                    </p>
                </div>
            </td>
        </tr>
        <?php wp_nonce_field( 'em_event_type_nonce_action', 'em_event_type_nonce_field' );
