<?php
/**
 * Event other settings panel html.
 */
defined( 'ABSPATH' ) || exit;
$max_tickets_per_user = get_post_meta( $post->ID, 'em_event_max_tickets_per_user', true );
$max_tickets_per_order = get_post_meta( $post->ID, 'em_event_max_tickets_per_order', true );
$em_event_max_tickets_reached_message = get_post_meta($post->ID, 'em_event_max_tickets_reached_message',true);
if(empty($em_event_max_tickets_reached_message))
{
   $em_event_max_tickets_reached_message = esc_html__('You have already reached the maximum ticket limit for this event and cannot purchase additional tickets.','eventprime-event-calendar-management');
}
$em_restrict_no_of_bookings_per_user = get_post_meta($post->ID, 'em_restrict_no_of_bookings_per_user',true);
?>
<div id="ep_event_restrictions_data" class="panel ep_event_options_panel">
    <div class="ep-box-wrap ep-my-3">
        <div class="ep-box-row ep-mb-3 ep-items-end">
            <div class="ep-box-col-12 ep-meta-box-data">
                <label for="em_event_max_tickets_per_user" class="ep-max-tickets-per-user-label"> <?php esc_html_e( 'Maximum Tickets Per User', 'eventprime-event-calendar-management'); ?></label>
                <div class="ep-max-tickets-per-user">
                    <input value="<?php echo ( ! empty( $max_tickets_per_user ) ? esc_html( $max_tickets_per_user ) : '0' ); ?>" type="number" id="em_event_max_tickets_per_user" name="em_event_max_tickets_per_user" min="0" />
                </div>
                <div class="ep-text-muted ep-text-small"><?php esc_html_e( "Set the maximum number of tickets that a single registered user can purchase for this event. Leave blank or set to '0' for no limit. For example, enter '1' to restrict each user to a single ticket.", 'eventprime-event-calendar-management' ); ?></div>
            </div> 
        </div>
        
        <div class="ep-box-row ep-mb-3 ep-items-end">
            <div class="ep-box-col-12 ep-meta-box-data">
                <label for="em_event_max_tickets_per_order" class="ep-max-tickets-per-order-label"> <?php esc_html_e( 'Maximum Tickets Per Order Across All Tickets', 'eventprime-event-calendar-management'); ?></label>
                <div class="ep-max-tickets-per-order">
                    <input value="<?php echo ( ! empty( $max_tickets_per_order ) ? esc_html( $max_tickets_per_order ) : '0' ); ?>" type="number" id="em_event_max_tickets_per_order" name="em_event_max_tickets_per_order" min="0" />
                </div>
                <div class="ep-text-muted ep-text-small"><?php esc_html_e( "Set the maximum number of tickets a user can purchase in total for this event, including tickets from all categories and individual tickets without categories. For example, enter '1' to allow the user to select only one ticket (whether categorized or individual), but not multiple tickets. Leave blank or set to '0' for no limit.", 'eventprime-event-calendar-management' ); ?></div>
            </div> 
        </div>
        
        <div class="ep-box-row ep-mb-3 ep-items-end">
            <div class="ep-box-col-12 ep-meta-box-data">
                <label for="em_event_max_tickets_reached_message" class="ep-max-tickets-reached-message-label"> <?php esc_html_e( 'Message for Maximum Tickets Reached', 'eventprime-event-calendar-management' ); ?></label>
                <div class="ep-max-tickets-reached-message">
                    <textarea id="em_event_max_tickets_reached_message" rows="5" cols="50" name="em_event_max_tickets_reached_message" ><?php echo esc_html( $em_event_max_tickets_reached_message );?></textarea>
                </div>
                <div class="ep-text-muted ep-text-small"><?php esc_html_e( "Enter the message to display when a user has already purchased the maximum allowed number of tickets for this event. For example: 'You have already reached the maximum ticket limit for this event and cannot purchase additional tickets.' Leave blank to use the default message.", 'eventprime-event-calendar-management' ); ?></div>
            </div> 
        </div> 

        <div class="ep-box-row ep-mb-3 ep-items-end">
            <div class="ep-box-col-12 ep-meta-box-data">
                <label for="em_restrict_no_of_bookings_per_user" class="ep-max-tickets-per-user-label"> <?php esc_html_e( 'Booking Limit per User', 'eventprime-event-calendar-management'); ?></label>
                <div class="ep-max-tickets-per-user">
                    <input value="<?php echo ( ! empty( $em_restrict_no_of_bookings_per_user ) ? esc_html( $em_restrict_no_of_bookings_per_user ) : '0' ); ?>" type="number" id="em_restrict_no_of_bookings_per_user" name="em_restrict_no_of_bookings_per_user" min="0" />
                </div>
                <div class="ep-text-muted ep-text-small"><?php esc_html_e( "Maximum number of separate bookings allowed per user for this event. Enter 1 to allow only one booking (any number of tickets). Enter 0 for no limit.", 'eventprime-event-calendar-management' ); ?></div>
            </div> 
        </div> 
        
        
    </div>
</div>