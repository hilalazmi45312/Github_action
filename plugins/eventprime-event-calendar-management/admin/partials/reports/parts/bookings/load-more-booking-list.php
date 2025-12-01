<?php 
$ep_functions = new Eventprime_Basic_Functions;
if( isset( $bookings_data->posts_details->posts ) && ! empty( $bookings_data->posts_details->posts ) ) {
    $booking_controller = new EventPrime_Bookings;
    foreach( $bookings_data->posts_details->posts as $booking ) {
        $event_title = $booking->post_title;
        //$booking = $booking_controller->load_booking_detail( $booking->ID );?>
        <tr>
            <td>
                <?php 
                $booking_url = get_edit_post_link( $booking->em_id );
                // echo $booking_url;
                // echo esc_html( $booking->em_id );
                ?>
                <a style="text-decoration: none;" class="row-title" href="<?php echo esc_url($booking_url); ?>" target="_blank"><?php echo esc_html( $booking->em_id ); 
                    if( isset( $booking->em_guest_booking ) && $booking->em_guest_booking == 1 ) {?>
                        <sup class="ep_gb_guest_identifier">guest</sup><?php
                    } 
                    if( isset( $booking->wc_order_id ) && ! empty( $booking->wc_order_id ) ) {?>
                        <sup class="ep_woo_identifier"><?php echo esc_html('Woo','eventprime-event-calendar-management');?></sup><?php
                    }
                    if( isset( $booking->is_rsvp_booking ) && $booking->is_rsvp_booking == 1 ) {?>
                        <span class="ep_rsvp_booking_identifier" style="color: #50575e;"> - RSVP</span><?php
                    }?>
                </a>
            </td>
            <td><?php echo esc_html( $event_title );?></td>
            <td>
                <?php 
                $booking_date_time = isset( $booking->em_date ) ? esc_html( $ep_functions->ep_timestamp_to_datetime( $booking->em_date, 'dS M Y H:i A', 1 ) ) : '--';
                echo esc_html($booking_date_time);
                ?>
            </td>
            <td><?php
                if( ! empty( $booking->em_status ) ) {
                    if( $booking->em_status == 'publish' || $booking->em_status == 'completed' ) {?>
                        <span class="ep-booking-status ep-status-confirmed">
                            <?php esc_html_e( 'Completed', 'eventprime-event-calendar-management' );?>
                        </span><?php
                    }
                    if( $booking->em_status == 'pending' ) {?>
                        <span class="ep-booking-status ep-status-pending">
                            <?php esc_html_e( 'Pending', 'eventprime-event-calendar-management' );?>
                        </span> <?php
                    }
                    if( $booking->em_status == 'cancelled' ) {?>
                        <span class="ep-booking-status ep-status-cancelled">
                            <?php esc_html_e( 'Cancelled', 'eventprime-event-calendar-management' );?>
                        </span><?php
                    }
                    if( $booking->em_status == 'refunded' ) {?>
                        <span class="ep-booking-status ep-status-refunded">
                            <?php esc_html_e( 'Refunded', 'eventprime-event-calendar-management' );?>
                        </span><?php
                    }
                    if( $booking->em_status == 'draft' ) {?>
                        <span class="ep-booking-status ep-status-draft">
                            <?php esc_html_e( 'Draft', 'eventprime-event-calendar-management' );?>
                        </span><?php
                    }
                } else{
                    $booking_status = $booking->post_data->post_status;
                    if( ! empty( $booking_status ) ) {?>
                        <span class="ep-booking-status ep-status-<?php echo esc_attr( $booking_status );?>">
                            <?php echo esc_html( $ep_functions->ep_get_booking_status()[$booking_status] ); ?>
                        </span><?php
                    } else{
                        echo '--';
                    }
                } ?>
            </td>
            <td><?php
                if( ! empty( $booking->em_payment_method ) ) {
                    echo esc_html( ucfirst( $booking->em_payment_method ) );
                } else{
                    if( ! empty( $booking->em_order_info['payment_gateway'] ) ) {
                        echo esc_html( ucfirst( $booking->em_order_info['payment_gateway'] ) );
                    } else{
                        echo '--';
                    }
                }?>
            </td>
        </tr><?php 
    }
}