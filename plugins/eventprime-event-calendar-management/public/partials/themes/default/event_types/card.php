<?php
/**
 * View: Event Types List - Card View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/eventprime/event_types/list/views/card.php
 *
 */
?>
<?php foreach ( $args->event_types as $event_type ) {?>
    <div class="ep-box-col-<?php echo absint( $args->cols ); ?> ep-col-md-6 ep-mb-3 ep-event-type-col-section">
        <div class="ep-box-card-item ">
            <div class="ep-box-card-thumb" >
                <a href="<?php echo esc_url( $event_type->event_type_url ); ?>" class="ep-img-link">
                    <img src="<?php echo esc_url( $event_type->image_url ); ?>" alt="<?php esc_attr( $event_type->name ); ?>"> 
                </a>
            </div>
            <div class="ep-box-card-content ep-bg-white">
                <div class="ep-box-title ep-box-card-title">
                    <a href="<?php echo esc_url( $event_type->event_type_url ); ?>">
                        <?php echo esc_html( $event_type->name ); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
    