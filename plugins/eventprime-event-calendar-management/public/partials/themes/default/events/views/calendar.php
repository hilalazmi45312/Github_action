<?php
/**
 * View: Event Calendar
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/eventprime/events/list/views/calendar.php
 *
 */
$ep_functions = new Eventprime_Basic_Functions;

//print_r($args);
if(!isset($args->event_types) || empty($args->event_types))
{
    if($ep_functions->ep_get_global_settings('show_event_types_on_calendar')==1 || $ep_functions->ep_get_global_settings( 'disable_filter_options' ) != 1 )
    {
         $args->event_types=  $ep_functions->ep_get_terms_with_meta_on_all_events_page('em_event_type',array( 'id', 'name', 'em_color', 'em_type_text_color' ));
    }
}
?>

<div id="ep_event_calendar" class="ep-mb-5 ep-box-col-12"></div>
 <!-- Event type swatches -->
 <div class="ep-event-types ep-d-flex ep-flex-wrap">
    <?php
    if( isset( $args->types_ids ) && ! empty( $args->types_ids ) && is_array( $args->types_ids ) ) {
        foreach( $args->types_ids as $type_id ) {
            if( ! empty( $type_id ) ){?>
            <div class="ep-event-type ep-event-type ep-mr-2 ep-border ep-p-2 ep-rounded-1 ep-lh-0 ep-di-flex ep-align-items-center ep-mb-2">
                <?php
                $type_id = (int)trim($type_id);
                $type_url = $ep_functions->ep_get_custom_page_url( 'event_types', $type_id, 'event_type', 'term' );
                $enable_seo_urls = $ep_functions->ep_get_global_settings( 'enable_seo_urls' );
                if( isset( $enable_seo_urls ) && ! empty( $enable_seo_urls ) ){
                    $type_url = get_term_link( $type_id );
                }
                $type = get_term( $type_id );
                $type_color = get_term_meta( $type->term_id, 'em_color', true );
                ?>
                <a class="ep-outline-width-0" href="<?php echo esc_url( $type_url ); ?>"><?php echo esc_html( $type->name ); ?></a><?php
                if( ! empty( $type_color ) && $type_color != '#' ) {?>
                    <span style="background-color:<?php echo esc_attr( $type_color ); ?>" class="ep-ml-1"></span><?php
                }?>
            </div><?php 
            } 
        } 
    }elseif(isset($args->event_types)){
        foreach( $args->event_types as $type ) {?>
            <div class="ep-event-type ep-event-type ep-mr-2 ep-border ep-p-2 ep-rounded-1 ep-lh-0 ep-di-flex ep-align-items-center ep-mb-2 ep-bg-white">
                <?php
                $type_url = $ep_functions->ep_get_custom_page_url( 'event_types', $type['id'], 'event_type', 'term' );
                $enable_seo_urls = $ep_functions->ep_get_global_settings( 'enable_seo_urls' );
                if( isset( $enable_seo_urls ) && ! empty( $enable_seo_urls ) ){
                    $type_url = get_term_link( $type['id'] );
                }?>
                <a class="ep-outline-width-0" href="<?php echo esc_url( $type_url ); ?>"><?php echo esc_html( $type['name'] ); ?></a><?php
                if( ! empty( $type['em_color'] ) && $type['em_color'] != '#' ) {?>
                    <span style="background-color:<?php echo esc_attr( $type['em_color'] ); ?>" class="ep-ml-1"></span><?php
                }?>
            </div><?php 
        }
    }?>  
</div>      
<!-- Swatches ends here -->