<?php
/**
 * View: Venues List - Card View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/eventprime/venues/list/views/card.php
 *
 */
$ep_functions = new Eventprime_Basic_Functions;
?>
<?php foreach ( $args->venues as $venue ) {?>
    <div class="ep-box-col-<?php echo esc_attr($args->cols); ?> ep-col-md-6 ep-mb-3 ep-venue-col-section">
        <div class="ep-box-card-item ">
            <div class="ep-box-card-thumb" >
                <a href="<?php echo esc_attr($venue->venue_url); ?>" class="ep-img-link">
                    <img src="<?php echo esc_url( $venue->image_url ); ?>" alt="<?php echo esc_attr( $venue->name ); ?>">
                </a>
            </div>
            <div class="ep-box-card-content ep-p-3 ep-bg-white">
                <div class="ep-box-title ep-box-card-title ep-text-truncate ep-mb-2">
                    <a href="<?php echo esc_url( $venue->venue_url ); ?>" class="ep-fw-bold ep-fs-6">
                        <?php echo esc_html( $venue->name ); ?>
                    </a>
                </div>
                
                <div class="ep-box-card-venue ep-card-venue ep-text-muted ep-text-small ep-text-truncate ep-mb-1">
                    <?php if ( !empty( $venue->em_address ) && ! empty( $venue->em_display_address_on_frontend ) ) {
                        echo wp_kses_post(wp_trim_words( $venue->em_address, 10 ));
                    }?>
                </div>
                
                <div class="ep-venue-seating-capacity ep-event-details ep-text-small ep-d-flex ep-justify-content-between">
                    <?php if ( !empty( $venue->em_type ) ) {?>
                        <div class="ep-event-attr-name ep-fw-bold"><?php echo esc_html__( 'Type', 'eventprime-event-calendar-management' ) .' : '. esc_html__( $ep_functions->ep_get_venue_type_label( $venue->em_type ), 'eventprime-event-calendar-management'); ?></div>
                        <?php
                    }?>
                </div>
            </div>
        </div>
    </div><?php 
} ?>