<?php 
$ep_functions = new Eventprime_Basic_Functions;
$organizers = $args->event->organizer_details;

if( isset( $organizers ) && !empty($organizers) && empty( $ep_functions->ep_get_global_settings( 'hide_organizers_section' ) ) ) {
    $organized_by_label = $ep_functions->ep_global_settings_button_title( 'Organized by' );?>
    <div class="ep-box-col-12 ep-my-3 ep-d-flex ep-items-center" id="ep-sl-event-meta">
        <span class="ep-fw-bold ep-text-small ep-white-space"><?php echo esc_html( $organized_by_label );?></span>
        <span class="material-icons-outlined ep-fs-6 ep-mr-1 ep-mt-1 ep-align-middle ep-text-warning ep-lh-0"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 0 24 24" width="24px" fill="#FFC107"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M10.02 6L8.61 7.41 13.19 12l-4.58 4.59L10.02 18l6-6-6-6z"/></svg></span>
        <span class="ep-text-smalll ep-d-inline-flex ep-items-center ep-flex-wrap ep-mt-1" id="ep_single_event_organizers">
            <?php foreach( $organizers as $organizer ) {
                //print_r($organizer);die;
                if( ! empty( $organizer ) ) {?>
                    <a href="<?php echo esc_url( $organizer->organizer_url );?>" target="_blank" class="ep-text-dark">
                        <span class="ep-text-small ep-my-2 ep-mr-4 ep-d-flex ep-items-center ">
                            <img src="<?php echo esc_url( $organizer->image_url ); ?>" alt="<?php esc_attr( $organizer->name ); ?>" class="ep-inline-block ep-rounded-circle ep-object-cover-fit ep-mr-1" style="width:24px; height: 24px;">
                            <span class="ep-align-middle">
                                <?php echo esc_html( $organizer->name );?>
                            </span>
                        </span>
                    </a><?php
                }
            }?>
        </span>
    </div><?php
}?>