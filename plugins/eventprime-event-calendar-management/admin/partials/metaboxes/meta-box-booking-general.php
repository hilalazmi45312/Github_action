<?php
/**
 * Booking meta box html
 */
defined( 'ABSPATH' ) || exit;
wp_enqueue_script('jquery-ui-dialog');
$ep_functions = new Eventprime_Basic_Functions;
$booking_controller = new EventPrime_Bookings();
$booking_id = $post->ID;
$post_meta = get_post_meta( $booking_id );
$booking = $booking_controller->load_booking_detail( $booking_id,true );
$user = ( ! empty( $booking->em_user ) ? get_user_by( 'id', $booking->em_user ) : '' );
$event_name = $post->post_title;
$payment_method = '';
if( ! empty( $booking->em_payment_method ) ) {
    $payment_method = esc_html( ucfirst( $booking->em_payment_method ) );
} else if( ! empty( $booking->em_order_info['payment_gateway'] ) ) {
    $payment_method = esc_html( ucfirst( $booking->em_order_info['payment_gateway'] ) );
}
$event_date = isset( $booking->em_date ) ? $booking->em_date : '';
$payment_log = isset( $booking->em_payment_log ) ? $booking->em_payment_log : array();
// $status = $ep_functions->get_status();
$status = $ep_functions->ep_get_booking_status();

$booking_date_time = isset( $booking->em_date ) ? esc_html( $ep_functions->ep_timestamp_to_datetime( $booking->em_date ) ) : '';

$event_date_time = isset($booking->event_data->em_start_date)? esc_html( $ep_functions->ep_timestamp_to_date( $booking->event_data->em_start_date, 'dS M Y', 1 ) ) .' '. esc_html( $ep_functions->ep_convert_time_with_format( $booking->event_data->em_start_time ) ) : '';
$event_url = isset($booking->event_data->em_id) ?get_edit_post_link($booking->event_data->em_id):'';
?>
<div class="emagic">
    <div class="ep-box-wrap">
        <div class="ep-box-row ep-my-3 ep-p-2">
            <div class="ep-box-col-12">
                <div class="ep-booking-title ep-m-0 ep-fs-4">
                    <?php echo sprintf( esc_html__('Booking #%d :- ', 'eventprime-event-calendar-management'), esc_html($booking_id)); if(!empty($event_url)) {$ep_functions->ep_documentation_link_read_more_html($event_url,$event_name);}else{ echo esc_html($event_name);} ?> 
                </div>
                <div class="ep-payment-method ep-text-muted ep-fs-5">
                    <?php 
                    echo sprintf( esc_html__('Payment via %s', 'eventprime-event-calendar-management'), esc_html($payment_method));?>
                </div>
            </div>
        </div>

        <div class="ep-box-row">
            <div class="ep-box-col-12 ep-box-pr-0">
                <h3 class="ep-fw-bold ep-my-3 ep-fs-6"><?php esc_html_e( 'User', 'eventprime-event-calendar-management' );?></h3>
            </div>
            <div class="ep-box-row">
                <div class="ep-box-col-3 ep-boo-username">
                    <div class="ep-gen-date ep-fw-bold">
                        <?php 
                        if( ! empty( $user->first_name ) ) {
                            esc_html_e( 'User Name:', 'eventprime-event-calendar-management' );
                        } else{
                            esc_html_e( 'Username:', 'eventprime-event-calendar-management' );
                        }?>
                    </div>
                    <div>
                        <?php if( ! empty( $user ) && ! empty( $user->ID ) ) {
                            if( ! empty( $user->first_name ) ) {
                                echo esc_html( $user->first_name );
                                if( ! empty( $user->last_name ) ) {
                                    echo ' ' . esc_html( $user->last_name );
                                }
                            } else{
                                echo esc_html( $user->data->user_login );
                            }
                        } else{
                            if( ! empty( $booking->em_order_info ) && ! empty( $booking->em_order_info['user_name'] ) ) {
                                echo esc_html( $booking->em_order_info['user_name'] );
                            } else{
                                echo esc_html__( 'Guest', 'eventprime-event-calendar-management' );
                            }
                        }?>
                    </div>
                </div>
                
                <div class="ep-box-col-3 ep-boo-useremail">
                    <div class="ep-gen-user ep-fw-bold"><?php esc_html_e('User Email:','eventprime-event-calendar-management');?></div>
                    <div>
                        <?php if( ! empty( $user ) && ! empty( $user->ID ) ) {
                            echo esc_html( $user->data->user_email );
                        } else{
                            if( ! empty( $booking->em_order_info ) && ! empty( $booking->em_order_info['user_email'] ) ) {
                                echo esc_html( $booking->em_order_info['user_email'] );
                            }
                        }?>
                    </div>
                </div>
                <?php if( ! empty( $booking->em_order_info ) && ! empty( $booking->em_order_info['user_phone'] ) ) {?>
                    <div class="ep-box-col-3 ep-boo-userphone">
                        <div class="ep-gen-user ep-fw-bold"><?php esc_html_e('User Phone:', 'eventprime-event-calendar-management' );?></div>
                        <div><?php echo esc_html( $booking->em_order_info['user_phone'] );?></div>
                    </div><?php
                }?>
            </div>
        </div>
        
        <div class="ep-box-row">
            <div class="ep-box-col-12 ep-box-pr-0">
                <h3 class="ep-fw-bold ep-my-3 ep-fs-6"><?php esc_html_e( 'General', 'eventprime-event-calendar-management' );?></h3>
            </div>
            <div class="ep-box-row">
                <div class="ep-box-col-3 ep-boo-date">
                    <div class="ep-gen-date  ep-fw-bold"><?php esc_html_e( 'Booking Date:', 'eventprime-event-calendar-management' );?></div>
                    <div class="ep-py-2 ep-align-top"><?php echo esc_attr( $booking_date_time ); ?></div>
                </div>
                
                <div class="ep-box-col-3 ep-boo-date">
                    <div class="ep-gen-date  ep-fw-bold"><?php esc_html_e( 'Event Start:', 'eventprime-event-calendar-management' );?></div>
                    <div class="ep-py-2 ep-align-top"><?php echo esc_attr( $event_date_time ); ?></div>
                </div>
                
                <div class="ep-box-col-3 ep-boo-status">
                    <div class="ep-gen-date ep-fw-bold"><?php esc_html_e( 'Booking Status:', 'eventprime-event-calendar-management' );?></div>
                    <div class="ep-booking-status-<?php echo esc_attr($booking->em_status); ?> ep-booking-status-completed ep-py-2 ep-align-top ">
                        <?php
                        $booking_status = '';
                        if( ! empty( $booking->em_status ) ) {
                            $booking_status = $booking->em_status;
                            echo esc_html( ucfirst( $booking->em_status ) );
                        } else{
                            $booking_status = $booking->post_data->post_status;
                            esc_html_e( $status[$booking_status], 'eventprime-event-calendar-management' );
                        }?>
                        <?php if(! empty( $booking_status ) && ( $booking_status == 'completed' || $booking_status == 'pending' || $booking_status == 'draft' || $booking_status == 'publish' ) ):?>
                            <span class="icon-asset edit-booking-status ep-cursor ep-text-primary" id="ep-booking-status-edit">Edit</span>
                        <?php endif;?>
                    </div>

                    <?php if( ! empty( $booking_status ) && ( $booking_status == 'completed' || $booking_status == 'pending' || $booking_status == 'draft' || $booking_status == 'publish' ) ):?>
                        <div class="ep-booking-status-edit" id="ep-booking-status-child" style="display:none;">
                            <div class="ep-form-row">
                                <select class="ep-booking-status" id='ep-booking-status'>
                                    <?php foreach($status as $key => $label){?>
                                        <option value="<?php echo esc_attr( $key );?>" <?php echo $booking_status == $key ? 'selected': '';?>><?php echo esc_attr($label)?></option>
                                    <?php } ?>
                                </select>
                                <button type="button" id="update_booking_status" class="button"><?php esc_html_e('Update','eventprime-event-calendar-management');?></button>
                                <span class="spinner ep-booking-status-spinner"></span>
                            </div>
                        </div>
                    <?php endif;?>

                    <?php if( ! empty( $booking_status ) && $booking_status == 'cancelled' ):?>
                        <button class="ep-btn" type="button" id="ep_refunded_btn" onclick="ep_booking_refund_status(<?php echo esc_html($booking_id);?>,'<?php echo esc_html($payment_method);?>','<?php echo 'refunded';?>');"><?php esc_html_e('Mark as Refunded','eventprime-event-calendar-management');?></button>
                    <?php endif;?>
                </div>
                
                <div class="ep-box-col-3 ep-payment-status">
                    <div class="ep-gen-date ep-fw-bold"><?php esc_html_e('Payment Status:','eventprime-event-calendar-management');?></div>
                    <div class="ep-payment-status ep-booking-status ep-py-2 ep-align-top">
                        <?php 
                        if(strtolower($payment_method) == 'offline'){
                            echo isset($payment_log['offline_status']) ? esc_html($payment_log['offline_status']) : '';
                        }else{
                            echo isset($payment_log['payment_status']) ? esc_html($payment_log['payment_status']) : '';
                        }?>
                        
                        <?php do_action( 'ep_booking_payment_status', $booking_id );?>
                    </div>    
                </div>
            </div>
        </div>
    </div>
</div>