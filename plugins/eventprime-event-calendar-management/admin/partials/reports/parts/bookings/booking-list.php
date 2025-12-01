<div class="wrap ep-box-wrap">
    <div id="poststuff" class="ep-box-row">
        <div class="ep-box-col-12"><?php 
        $ep_functions = new Eventprime_Basic_Functions;
            if( isset( $bookings_data->posts_details->posts ) && ! empty( $bookings_data->posts_details->posts ) ) {
                $booking_controller = new EventPrime_Bookings; ?>
                <div class="ep-d-flex ep-justify-content-between ep-align-items-center"><?php
                    if ($bookings_data->posts_details->found_posts > 1) {
                        echo sprintf( esc_html__( '%d bookings found', 'eventprime-event-calendar-management' ), esc_html($bookings_data->posts_details->found_posts) );
                    } else {
                        echo sprintf( esc_html__( '%d booking found', 'eventprime-event-calendar-management' ), esc_html($bookings_data->posts_details->found_posts) );
                    } ?>
                    <?php if( class_exists( 'Eventprime_Advanced_Reports' ) ) { ?>
                        <button type="button" id="ep_booking_export" class="button-primary ep-btn ep-ar-btn-primary"><?php echo esc_html__( 'Export All', 'eventprime-event-calendar-management' );?></button><?php 
                    }?>
                </div>
                <table class="form-table ep-table ep-table-striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Booking ID', 'eventprime-event-calendar-management' );?></th>
                            <th><?php esc_html_e( 'Event', 'eventprime-event-calendar-management' );?></th>
                            <th><?php esc_html_e( 'Booked On', 'eventprime-event-calendar-management' );?></th>
                            <th><?php esc_html_e( 'Status', 'eventprime-event-calendar-management' );?></th>
                            <th><?php esc_html_e( 'Payment Gateway', 'eventprime-event-calendar-management' );?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings_data->posts_details->posts as $booking){
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
                                <td><?php echo esc_attr($event_title);?></td>
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
                                    }?>
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
                        } ?>
                    </tbody>
                </table>
                <div class="ep-reports-boooking-load-more">
                    <?php
                    if( isset($bookings_data->posts_details->max_num_pages) && $bookings_data->posts_details->max_num_pages > 1) {?>
                        <div class="ep-report-load-more ep-frontend-loadmore ep-box-w-100 ep-my-4 ep-text-center">
                            <input type="hidden" id="ep-report-booking-paged" value="1"/>
                            <button type="button" data-max="<?php echo esc_attr( $bookings_data->posts_details->max_num_pages );?>" id="ep-loadmore-report-bookings" class="button-primary ep-btn ep-ar-btn-primary"><span class="ep-spinner ep-spinner-border-sm ep-mr-1"></span><?php esc_html_e( 'Load more', 'eventprime-event-calendar-management' );?></button>
                        </div><?php
                    }?>
                </div><?php 
            } else{ 
                echo esc_html( 'No Booking Found.', 'eventprime-event-calendar-management' );
            }?>
        </div>
    </div>
</div>