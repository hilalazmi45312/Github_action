<?php
/**
 * Event other settings panel html.
 */
defined( 'ABSPATH' ) || exit;
$event_theme = get_post_meta( $post->ID, 'eventprime_event_theme', true );
//print_r($event_theme.'adsf');die;
$themes = $ep_functions->eventprime_get_pm_theme_name();
?>
<div id="ep_event_theme_data" class="panel ep_event_options_panel">
    <div class="ep-box-wrap ep-my-3">
        <div class="ep-box-row ep-mb-3 ep-items-end">
            <div class="ep-box-col-12 ep-meta-box-data">
                <label for="eventprime_event_theme" class="ep-eventprime-theme"> <?php esc_html_e( 'Event Layout Template', 'eventprime-event-calendar-management'); ?></label>
                <div class="ep-eventprime-theme">
                    <select name="eventprime_event_theme" id="eventprime_event_theme" class="ep-form-control">
                        <option value=""><?php esc_html_e('Choose Layout Template','eventprime-event-calendar-management');?></option>
                    <?php 
                    foreach($themes as $themename){
                        ?>
                        <option value="<?php echo esc_attr($themename);?>" <?php selected($themename,$event_theme);?>><?php echo esc_html($themename);?></option>
                        <?php
                    }?>
                </select>
                </div>
                <div class="ep-text-muted ep-text-small"><?php esc_html_e( "Select a layout template for this event to customize its appearance. This template will apply only to this event, giving you flexibility in design without affecting other events.", 'eventprime-event-calendar-management' ); ?></div>
            </div> 
        </div>
        
     
        
        
    </div>
</div>