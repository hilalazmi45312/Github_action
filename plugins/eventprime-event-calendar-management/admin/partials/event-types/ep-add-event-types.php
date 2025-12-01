<?php
$ages_groups = array(
            'all' => esc_html__( 'All', 'eventprime-event-calendar-management' ),
            'parental_guidance' => esc_html__( 'All ages but parental guidance', 'eventprime-event-calendar-management' ),
            'custom_group' => esc_html__(' Custom Age', 'eventprime-event-calendar-management' )
        );?>
        <div class="form-field ep-type-admin-back-color">
            <label for="color">
                <?php esc_html_e( 'Background Color', 'eventprime-event-calendar-management' ); ?>
            </label>
            <input data-jscolor="{}" value="#2271B1" type="text" id="color" name="em_color" />
            <p class="emnote emeditor">
                <?php esc_html_e( 'Background color for events of this type when they appear on the events calendar.', 'eventprime-event-calendar-management' ); ?>
            </p>
        </div>
        <div class="form-field ep-type-admin-text-color">
            <label for="type_text_color">
                <?php esc_html_e( 'Text Color', 'eventprime-event-calendar-management' ); ?>
            </label>
            <input data-jscolor="{}" value="#000000" type="text" id="type_text_color" name="em_type_text_color" />
            <p class="emnote emeditor">
                <?php esc_html_e( 'Text color for events of this type when they appear on the events calendar. Can be overridden for individual events from their respective settings.', 'eventprime-event-calendar-management' ); ?>
            </p>
        </div>
        
        <div class="form-field ep-type-admin-age-group-selector">
            <label for="type_text_color">
                <?php esc_html_e( 'Age Group', 'eventprime-event-calendar-management' ); ?>
            </label>
            <select name="em_age_group" id="ep-event-type-age-group" class="ep-box-w-100">
                <?php foreach( $ages_groups as $key => $group ):?>
                    <option value="<?php echo esc_attr( $key );?>"><?php echo esc_html( $group );?></option>
                <?php endforeach;?>
            </select>
            <p class="emnote emeditor">
                <?php esc_html_e( 'Valid age group for the Event. This will be displayed on Event page.', 'eventprime-event-calendar-management' ); ?>
            </p>
        </div>
        <div class="form-field em-type-admin-age-group-custom" style="display:none;">
            <div class="ep-age-bar-fields">
                <input type="text" id="ep-custom-group" name="em_custom_group" readonly style="border:0; color:#f6931f; font-weight:bold;">
                <div id="ep-custom-group-range"></div>
            </div>
        </div>
        <div class="form-field ep-type-admin-image-wrap">
            <label><?php esc_html_e( 'Image', 'eventprime-event-calendar-management' ); ?></label>
            <div id="ep-type-admin-image"></div>
            <div>
                <input type="hidden" id="ep_type_image_id" name="em_image_id" />
                <button type="button" class="upload_image_button button"><?php esc_html_e( 'Upload/Add image', 'eventprime-event-calendar-management' ); ?></button>
                <!-- <button type="button" class="remove_image_button button"><?php //esc_html_e( 'Remove image', 'eventprime-event-calendar-management' ); ?></button> -->
                <p class="emnote emeditor">
                    <?php esc_html_e( 'Image or icon of the Event Type. Will be displayed on the Event Types directory page.', 'eventprime-event-calendar-management' ); ?>
                </p>
            </div>
        </div>
        <div class="form-field ep-type-admin-featured">
            <label >
                <?php esc_html_e('Featured', 'eventprime-event-calendar-management'); ?>
            </label>
            <label class="ep-toggle-btn">
                <input type="checkbox" id="is_featured" name="em_is_featured" />
                <span class="ep-toogle-slider round"></span>
            </label>
            <p class="emnote emeditor">
                <?php esc_html_e('Check if you want to make this event type featured.', 'eventprime-event-calendar-management'); ?>
            </p>
        </div>
     <?php wp_nonce_field( 'em_event_type_nonce_action', 'em_event_type_nonce_field' );

        