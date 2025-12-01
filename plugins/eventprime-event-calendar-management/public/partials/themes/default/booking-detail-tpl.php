<?php
$ep_functions = new Eventprime_Basic_Functions;
$ep_requests = new EP_Requests;
$global_settings = $ep_functions->ep_get_global_settings();
if(isset($args->event_data->event_url))
{
?>
<div class="ep-booking-container ep-box-wrap">
        <div class="ep-box-row">
            <?php if( ! empty( $args->em_id ) ) {
                $user = wp_get_current_user();
                $roles = (array) $user->roles;
                $users = (array) $user->ID;
                
                $ep_booking_conditions = (! empty( $user->ID ) || ( isset( $args->em_order_info['guest_booking'] ) && $args->em_order_info['guest_booking'] == 1 ) ) ? true : false;

                $ep_booking_conditions = apply_filters('ep_filter_booking_conditions', $ep_booking_conditions, $user, $args, $options);

                if( $ep_booking_conditions ) {

                    $ep_event_allowed_roles = metadata_exists('post', $args->em_event, 'ep_event_allowed_roles') ? get_post_meta($args->em_event, 'ep_event_allowed_roles', true) : [];
                    $ep_event_allowed_individuals = metadata_exists('post', $args->em_event, 'ep_event_allowed_individuals') ? get_post_meta($args->em_event, 'ep_event_allowed_individuals', true) : [];

                    $ep_event_allowed_roles = is_array($ep_event_allowed_roles) ? $ep_event_allowed_roles : [];
                    $ep_event_allowed_individuals = is_array($ep_event_allowed_individuals) ? $ep_event_allowed_individuals : [];

                    $ep_user_roles = array_intersect($ep_event_allowed_roles, $roles);
                    $ep_user_roles_indi = array_intersect($ep_event_allowed_individuals, $users);

                    if( $user->ID != $args->em_user && !($user->ID != $args->em_user && !empty($args->em_order_info['guest_booking'])) && isset($options['global']->ep_allow_attendee_check_in) && $options['global']->ep_allow_attendee_check_in == 1 ) {
                        if( empty(array_intersect($ep_event_allowed_roles, $roles)) && empty(array_intersect($ep_event_allowed_individuals, $users))){ ?>
                            <div class="ep-alert ep-alert-warning ep-mt-3 ep-fs-6">
                                <?php esc_html_e( "Don't have permission to check in attendees", 'eventprime-event-calendar-management' );?>
                            </div><?php
                            exit();
                        }
                    }
                    $ep_booking_inner_conditions = ( $user->ID != $args->em_user && empty( $args->em_order_info['guest_booking'] )) ? true : false; 

                    $ep_booking_inner_conditions = apply_filters('ep_filter_booking_inner_conditions',$ep_booking_inner_conditions, $args, $user, $ep_user_roles, $ep_user_roles_indi );

                    if( $ep_booking_inner_conditions ) {
                        if( ! in_array( 'administrator', $roles, true ) ) {?>
                            <div class="ep-alert ep-alert-warning ep-mt-3">
                                <?php esc_html_e( 'No booking found!', 'eventprime-event-calendar-management' );?>
                            </div><?php
                            exit();
                        }
                    }
                    $ep_guest_order_info = ((empty($args->em_user) || $user->ID != $args->em_user) && !empty($args->em_order_info['guest_booking'])) ? true : false;

                    $ep_guest_order_info = apply_filters('ep_guest_order_info_condition',$ep_guest_order_info, $args, $user, $ep_user_roles, $ep_user_roles_indi );

                    if($ep_guest_order_info){
                        $order_key = get_post_meta($order_id, 'ep_order_key', true);
                        $key = isset($_GET['order_key']) && !empty($_GET['order_key']) ? sanitize_text_field($_GET['order_key']) : '';
                        if(!empty($order_key) && $order_key != $key){
                        ?>
                        <div class="ep-alert ep-alert-warning ep-mt-3">
                                <?php esc_html_e( 'No booking found!', 'eventprime-event-calendar-management' );?>
                            </div><?php
                            exit();
                        }
                    }
                    ?>
                    <div class="ep-box-col-12 ep-fs-2 ep-my-5 ep-text-center">
                        <?php esc_html_e( 'Thank you! Here are your booking details', 'eventprime-event-calendar-management' );?>
                    </div>

                    <div class="ep-box-col-6 ep-text-start ep-mb-3 ep-pl-0 ep-event-link-container">
                        <a href="<?php echo esc_url( $args->event_data->event_url );?>" target="_blank" class="ep-event-link">
                            <button type="button" class="ep-btn ep-btn-outline-dark ep-bg-white">
                                <span class="material-icons-outlined ep-align-middle">chevron_left</span>
                                <?php esc_html_e( 'Event Page', 'eventprime-event-calendar-management' );?>
                            </button>
                        </a>
                    </div>

                    <div class="ep-box-col-6 ep-text-end ep-mb-3 ep-pr-0 ep-account-link-container">
                        <a href="<?php echo esc_url( get_permalink( $ep_functions->ep_get_global_settings( 'profile_page' ) ) );?>" target="_blank" class="ep-account-link">
                            <button type="button" class="ep-btn ep-btn-dark">
                                <?php esc_html_e( 'Account area', 'eventprime-event-calendar-management' );?>
                                <span class="material-icons-outlined ep-align-middle">chevron_right</span>
                            </button>
                        </a>
                    </div>

                    <div class="ep-box-col-12 ep-border ep-rounded ep-bg-white">
                        <div class="ep-box-row ep-text-small">
                            <div class="ep-box-col-12 ep-ps-4 ep-py-4 ep-justify-content-between ep-d-flex">
                                <div>
                                    <span class="ep-text-uppercase">
                                        <span class="ep-fw-bold">
                                            <?php esc_html_e( 'Booking ID', 'eventprime-event-calendar-management' );?>
                                        </span>
                                        <?php echo ': #' . esc_html( $args->em_id );?>
                                    </span>
                                    <?php if( $args->em_status == 'completed' ) {?>
                                        <span class="ep-text-white ep-small ep-rounded-1 ep-py-1 ep-px-2 ep-bg-success ep-ml-1 ep-align-top">
                                            <?php esc_html_e( 'Confirmed', 'eventprime-event-calendar-management' );?>
                                        </span><?php
                                    } else {?>
                                        <span class="ep-text-white ep-small ep-rounded-1 ep-py-1 ep-px-2 ep-bg-warning ep-ml-1 ep-align-top">
                                            <?php echo esc_html( $ep_functions->ep_get_booking_status()[ $args->em_status ] ); ?>
                                        </span><?php
                                    }?>
                                </div>
                                <div> <?php do_action('ep_add_data_with_booking_status_tab', $args); ?>  </div>
                            </div>
                        </div>

                        <?php 
                        $users = (array) $user->ID;

                        $ep_show_booking_detail_page_event = apply_filters('ep_show_booking_detail_page_event', 1, $args->em_event);
                       
                        // print_r($ep_show_booking_detail_page_event);
                        if($ep_show_booking_detail_page_event == 0 && (!empty(array_intersect($ep_event_allowed_roles, $roles)) || !empty(array_intersect($ep_event_allowed_individuals, $users)))) { ?>
                        <?php } 
                        else { ?> <div class="ep-box-row ep-booking-detail-section ep-border-top">
                            <div class="ep-box-col-6 ep-py-4">
                                <div class="ep-fs-4 ep-fw-bold ep-ps-4">
                                    <span class="ep-text-break ep-booking-event-name"><?php echo esc_html( $args->event_data->name );?></span>
                                    <a href="<?php echo esc_url( $args->event_data->event_url );?>" target="_blank">
                                        <span class="material-icons-outlined align-middle ep-fs-6 ep-text-primary">open_in_new</span>
                                    </a>
                                </div>
                                <?php if( !empty( $args->event_data->venue_details ) && !empty( $args->event_data->venue_details->em_address ) && ! empty( $args->event_data->venue_details->em_display_address_on_frontend ) ) {?>
                                    <div class="ep-ps-4 ep-text-muted ep-text-small">
                                        <?php echo esc_html( $args->event_data->venue_details->em_address );?>
                                    </div><?php
                                }
                                if( ! empty( $args->event_data->em_start_date ) && $ep_functions->ep_show_event_date_time( 'em_start_date', $args->event_data ) ) {?>
                                    <div class="ep-ps-4 ep-text-muted ep-text-small">
                                        <span><?php esc_html_e('Event Start:','eventprime-event-calendar-management');?></span>
                                        
                                        <span>
                                            <?php echo esc_html( $ep_functions->ep_timestamp_to_date( $args->event_data->em_start_date, 'dS M Y', 1 ) );
                                            if( ! empty( $args->event_data->em_start_time ) && $ep_functions->ep_show_event_date_time( 'em_start_time', $args->event_data ) ) {
                                                echo ', ' . esc_html( $ep_functions->ep_convert_time_with_format( $args->event_data->em_start_time ) );
                                            }?>
                                        </span>
                                    </div><?php
                                }
                                
                                if( ! empty( $args->event_data->em_end_date ) && $ep_functions->ep_show_event_date_time( 'em_end_date', $args->event_data ) ) {?>
                                    <div class="ep-ps-4 ep-text-muted ep-text-small">
                                        <span><?php esc_html_e('Event End:','eventprime-event-calendar-management');?></span>
                                        <span>
                                            <?php echo esc_html( $ep_functions->ep_timestamp_to_date( $args->event_data->em_end_date, 'dS M Y', 1 ) );
                                            if( ! empty( $args->event_data->em_end_time ) && $ep_functions->ep_show_event_date_time( 'em_end_time', $args->event_data ) ) {
                                                echo ', ' . esc_html( $ep_functions->ep_convert_time_with_format( $args->event_data->em_end_time ) );
                                            }?>
                                        </span>
                                    </div><?php
                                }

                                // User data
                                $booking_user_id = ! empty( $args->em_payment_log['ep_event_booking_user_id'] ) ? absint( $args->em_payment_log['ep_event_booking_user_id'] ) : absint( $args->em_user );
                                if( ! empty( $booking_user_id ) ) {
                                    $user = get_user_by( 'id', $booking_user_id );
                                }
                                if( ! empty( $user ) && $user->ID && ! empty( $user->data ) ) {?>
                                    <div class="ep-text-small ep-mt-4">
                                        <div>
                                            <span class="ep-mr-2 ep-fw-bold">
                                                <?php 
                                                if( ! empty( $user->first_name ) ) {
                                                    esc_html_e( 'User Name', 'eventprime-event-calendar-management' );
                                                } else{
                                                    esc_html_e( 'Username', 'eventprime-event-calendar-management' );
                                                }?>:
                                            </span>
                                            <span><?php 
                                                if( ! empty( $user->first_name ) ) {
                                                    echo esc_html( $user->first_name );
                                                    if( ! empty( $user->last_name ) ) {
                                                        echo ' ' . esc_html( $user->last_name );
                                                    }
                                                } else{
                                                    echo esc_html( $user->data->user_login );
                                                }?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="ep-mr-2 ep-fw-bold">
                                                <?php esc_html_e( 'User Email', 'eventprime-event-calendar-management' );?>:
                                            </span>
                                            <span><?php echo esc_html( $user->data->user_email );?></span>
                                        </div>
                                    </div><?php
                                } else{
                                    if( ! empty( $args->em_order_info ) ) {?>
                                        <div class="ep-text-small ep-mt-4"><?php
                                            if( ! empty( $args->em_order_info['user_name'] ) ) {?>
                                                <div>
                                                    <span class="ep-mr-2 ep-fw-bold">
                                                        <?php esc_html_e( 'Username', 'eventprime-event-calendar-management' );?>:
                                                    </span>
                                                    <span><?php echo esc_html( $args->em_order_info['user_name'] );?></span>
                                                </div><?php
                                            }
                                            if( ! empty( $args->em_order_info['user_email'] ) ) {?>
                                                <div>
                                                    <span class="ep-mr-2 ep-fw-bold">
                                                        <?php esc_html_e( 'User Email', 'eventprime-event-calendar-management' );?>:
                                                    </span>
                                                    <span><?php echo esc_html( $args->em_order_info['user_email'] );?></span>
                                                </div><?php
                                            }
                                            if( ! empty( $args->em_order_info['user_phone'] ) ) {?>
                                                <div>
                                                    <span class="ep-mr-2 ep-fw-bold">
                                                        <?php esc_html_e( 'User Phone', 'eventprime-event-calendar-management' );?>:
                                                    </span>
                                                    <span><?php echo esc_html( $args->em_order_info['user_phone'] );?></span>
                                                </div><?php
                                            }?>
                                        </div><?php
                                    }
                                }?>
                                
                                <?php 
                                // hook to show any fees from extension on the booking
                                do_action( 'ep_booking_detail_show_booking_ticket_data', $args );?>
                            </div>
                            <div class="ep-box-col-3 ep-py-4 ep-text-small">
                                <div class="ep-ps-4">
                                    <span class="ep-mr-2 ep-fs-5">
                                        <?php esc_html_e( 'Paid', 'eventprime-event-calendar-management' );?>:
                                    </span>
                                    <span class="ep-fs-5 ep-mr-2">
                                        <?php echo esc_html( $ep_functions->ep_price_with_position( $args->em_order_info['booking_total'] ) );?>
                                    </span>
                                </div>
                                <div class="ep-text-small ep-text-muted">
                                    <div class="ep-text-small">
                                        <span class="ep-mr-2">
                                            <?php esc_html_e( 'Tickets Price', 'eventprime-event-calendar-management' );?>:
                                        </span>
                                        <span><?php echo esc_html( $ep_functions->ep_get_booking_tickets_total_price( $args->em_order_info['tickets'] ) );?></span>
                                    </div>
                                    <?php $additional_fees = 0;
                                    if(isset($args->eventprime_updated_pattern))
                                    {
                                        $additional_fees = $ep_functions->ep_calculate_order_total_additional_fees_v2( $args->em_order_info['tickets'] );
                                    }
                                    else
                                    {
                                        $additional_fees = $ep_functions->ep_calculate_order_total_additional_fees( $args->em_order_info['tickets'] );
                                    }
                                    if( ! empty( $additional_fees ) ) {?>
                                        <div class="ep-text-small">
                                            <span class="ep-mr-2"><?php esc_html_e( 'Additional Fees', 'eventprime-event-calendar-management' );?>:</span>
                                            <span><?php echo esc_html( $additional_fees );?></span>
                                        </div><?php
                                    }
                                    if( isset( $args->em_order_info['event_fixed_price'] ) && ! empty( $args->em_order_info['event_fixed_price'] ) ) { ?>
                                        <div class="ep-text-small">
                                            <span class="ep-me-2"><?php esc_html_e( 'Event Fee', 'eventprime-event-calendar-management' );?>:</span>
                                            <span><?php echo esc_html( $ep_functions->ep_price_with_position( $args->em_order_info['event_fixed_price'] ) );?></span>
                                        </div><?php
                                    }?>

                                    <?php 
                                    // hook to show any fees from extension on the booking
                                    do_action( 'ep_booking_detail_show_fee_data', $args );?>

                                    <?php
                                    $offer_price = 0;
                                    $offer_price = $ep_functions->ep_calculate_order_total_offer_price( $args->em_order_info['tickets'] );
                                    if( ! empty( $offer_price ) ) {?>
                                        <div class="ep-text-small">
                                            <span class="ep-mr-2"><?php esc_html_e( 'Offers', 'eventprime-event-calendar-management' );?>:</span><span><?php echo '-' . esc_html( $offer_price );?></span>
                                        </div><?php
                                    }?>

                                    <?php 
                                    // hook to show any discount from extension on the booking
                                    do_action( 'ep_booking_detail_show_discount_data', $args );?>
                                    
                                </div>
                                <div class="ep-text-small ep-mt-4">
                                    <div class="">
                                        <span class="ep-mr-2">
                                            <?php esc_html_e( 'Booking Status', 'eventprime-event-calendar-management' );?>:
                                        </span>
                                        <?php if( $args->em_status == 'completed' ) {?>
                                            <span class="ep-text-success">
                                                <?php esc_html_e( 'Confirmed', 'eventprime-event-calendar-management' );?>
                                            </span><?php
                                        } else {?>
                                            <span class="ep-text-success">
                                                <?php echo esc_html( $ep_functions->ep_get_booking_status()[ $args->em_status ] ); ?>
                                            </span><?php
                                        }?>
                                    </div>
                                    <div class="">
                                        <span class="ep-mr-2"><?php esc_html_e( 'Payment Status', 'eventprime-event-calendar-management' );?>:</span>
                                        <span class="ep-text-success">
                                            <?php echo ( ! empty( $args->em_payment_log ) && ( ! empty( $args->em_payment_log['payment_status'] ) ) ?  ( ( isset( $args->em_payment_log['offline_status'] ) && ! empty( $args->em_payment_log['offline_status'] ) ) ? esc_html( ucfirst( $args->em_payment_log['offline_status'] ) ) : esc_html( ucfirst( $args->em_payment_log['payment_status'] ) ) ) : '' );?>
                                        </span>
                                    </div>
                                    <div class="">
                                        <span class="ep-mr-2">
                                            <?php esc_html_e( 'Booking ID', 'eventprime-event-calendar-management' );?>:
                                        </span>
                                        <span><?php echo '#' . esc_html( $args->em_id );?></span>
                                    </div>
                                    <div class="">
                                        <span class="ep-mr-2">
                                            <?php esc_html_e( 'Payment Method', 'eventprime-event-calendar-management' );?>:
                                        </span>
                                        <span>
                                            <?php echo ( ! empty( $args->em_payment_log ) && ( ! empty( $args->em_payment_log['payment_gateway'] ) ) ? esc_html( ucfirst( $args->em_payment_log['payment_gateway'] ) ) : '' );?>
                                        </span>
                                    </div>
                                    <div class="">
                                        <span class="ep-mr-2">
                                            <?php esc_html_e( 'Booking Date', 'eventprime-event-calendar-management' );?>:
                                        </span>
                                        <span>
                                            <?php 
                                            echo ! empty( $args->em_date ) ? esc_html($ep_functions->ep_timestamp_to_datetime( $args->em_date )) : '' ; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="ep-box-col-3 ep-p-3 ep-text-small">
                                <div class="ep-pt-4">
                                    <?php 
                                    $gcal_starts = $gcal_ends = $gcal_details = $location = $calendar_url = '';
                                    $gcal_starts = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event_data, 'start' );
                                    if( ! empty( $gcal_starts ) ) {
                                        $gcal_ends = $ep_functions->ep_convert_event_date_time_to_timestamp( $args->event_data, 'end' );
                                    }
                                    $gcal_details = urlencode( wp_kses_post( $args->event_data->description ) );
                                    $calendar_url = 'https://www.google.com/calendar/event?action=TEMPLATE&text=' . urlencode( esc_attr( $args->event_data->name ) ) . '&dates=' . gmdate( 'Ymd\\THi00\\Z', esc_attr( $gcal_starts ) ) . '/' . gmdate('Ymd\\THi00\\Z', esc_attr( $gcal_ends ) ) . '&details=' . esc_attr( $gcal_details );
                                    if ( ! empty( $args->event_data->venue_details->em_address ) ) {
                                        $location = urlencode( $args->event_data->venue_details->em_address );
                                        if( ! empty( $location ) ) {
                                            $calendar_url .= '&location=' . esc_attr( $location );
                                        }
                                    }
                                  
                                    if( ! empty( $gcal_starts ) && ! empty( $gcal_ends ) ) {?>
                                        <div class="ep-text-small ep-cursor ep-d-flex ep-align-items-center ep-mb-1">
                                            <a class="em-events-gcal em-events-button ep-di-flex ep-align-items-center ep-lh-0" href="<?php echo esc_url( $calendar_url );?>" target="_blank">
                                                <!--<img class="ep-google-calendar-add ep-fs-6 ep-align-middle" src="<?php echo esc_url( plugin_dir_url( EP_PLUGIN_FILE ) . 'public/partials/images/gcal.png' ); ?>" style="height: 18px;" />-->
                                                <svg width="13px" height="13px" class="ep-mr-2" viewBox="-3 0 262 262" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid"><path d="M255.878 133.451c0-10.734-.871-18.567-2.756-26.69H130.55v48.448h71.947c-1.45 12.04-9.283 30.172-26.69 42.356l-.244 1.622 38.755 30.023 2.685.268c24.659-22.774 38.875-56.282 38.875-96.027" fill="#4285F4"/><path d="M130.55 261.1c35.248 0 64.839-11.605 86.453-31.622l-41.196-31.913c-11.024 7.688-25.82 13.055-45.257 13.055-34.523 0-63.824-22.773-74.269-54.25l-1.531.13-40.298 31.187-.527 1.465C35.393 231.798 79.49 261.1 130.55 261.1" fill="#34A853"/><path d="M56.281 156.37c-2.756-8.123-4.351-16.827-4.351-25.82 0-8.994 1.595-17.697 4.206-25.82l-.073-1.73L15.26 71.312l-1.335.635C5.077 89.644 0 109.517 0 130.55s5.077 40.905 13.925 58.602l42.356-32.782" fill="#FBBC05"/><path d="M130.55 50.479c24.514 0 41.05 10.589 50.479 19.438l36.844-35.974C195.245 12.91 165.798 0 130.55 0 79.49 0 35.393 29.301 13.925 71.947l42.211 32.783c10.59-31.477 39.891-54.251 74.414-54.251" fill="#EB4335"/></svg>
                                                <?php esc_html_e( 'Add To Calendar','eventprime-event-calendar-management' ); ?>
                                            </a>
                                        </div><?php
                                    }?>
                                            <div class="ep-text-small ep-cursor ep-d-flex ep-align-items-center ep-mb-1">
                                                <a href="javascript:void(0)" id="ep_event_ical_export" data-event_id="<?php echo esc_attr($args->event_data->em_id); ?>">
                                                    <span class="material-icons-outlined ep-fs-6 ep-align-middle ep-lh-0 ep-mr-1">event</span>
                                                    <?php esc_html_e('+ iCal Export', 'eventprime-event-calendar-management'); ?>
                                                </a>
                                            </div>
                                    <?php if( ! empty( $args->event_data->venue_details ) && ! empty( $args->event_data->venue_details->em_address ) ) {?>
                                        <div class="ep-text-small ep-cursor ep-d-flex ep-align-items-center ep-mb-1">
                                            <a target="_blank" href="https://www.google.com/maps?saddr=My+Location&daddr=<?php echo urlencode( $args->event_data->venue_details->em_address ); ?>" >
                                                <span class="material-icons-outlined ep-fs-6 ep-align-middle ep-lh-0 ep-mr-1">directions_car</span>
                                                <?php esc_html_e( 'Directions','eventprime-event-calendar-management' ); ?>
                                            </a>
                                        </div><?php
                                    }?>

                                    <?php do_action('ep_booking_confirmation_actions_lists',$args );?>
                                    
                                    <?php if( ! empty( $args->event_data->organizer_details ) && count( $args->event_data->organizer_details ) > 0 ) {
                                        $org_email = '';
                                        foreach( $args->event_data->organizer_details as $or_detail ) {
                                            if( ! empty( $or_detail->em_organizer_emails ) ) {
                                                $org_email = $or_detail->em_organizer_emails[0];
                                                break;
                                            }
                                        }
                                        if( ! empty( $org_email ) ) {?>
                                            <div class="ep-text-small ep-cursor ep-d-flex ep-align-items-center ep-mb-1">
                                                <a href="mailto:<?php echo esc_html( $org_email );?>">
                                                    <span class="material-icons-outlined ep-fs-6 ep-align-middle ep-lh-0 ep-mr-1">work_outline</span>
                                                    <?php esc_html_e('Contact Organizer','eventprime-event-calendar-management'); ?>
                                                </a>
                                            </div><?php
                                        }
                                    }
                                    if( is_user_logged_in() && ! empty( $args->event_data->em_allow_cancellations ) && 1 == $args->event_data->em_allow_cancellations && ( 'completed' == $args->em_status || 'pending' == $args->em_status ) ) {?>
                                        <div class="ep-text-small ep-cursor ep-text-danger ep-d-flex ep-align-items-center ep-mb-1" ep-modal-open="ep_booking_cancellation_modal">
                                            <span class="material-icons-outlined ep-fs-6 align-middle ep-lh-0 ep-mr-2">block</span>
                                            <?php esc_html_e('Cancel Booking','eventprime-event-calendar-management'); ?>
                                        </div><?php
                                    }
                                    do_action('ep_after_booking_details_additional', $args);
                                    
                                    if($ep_guest_order_info)
                                    {
                                        
                                        if ( $global_settings->enable_gdpr_tools && $global_settings->enable_gdpr_delete ) : ?>
                                            <div class="ep-box-col-6 ep-box-col-md-3 ep-gdpr-action-box ep-mb-4">
                                                <div class="ep-border ep-rounded ep-p-3 ep-mt-2">
                                                    <div class="ep-fw-bold ep-mb-1"><?php esc_html_e( 'Delete Booking data', 'eventprime-event-calendar-management' ); ?></div>
                                                    <p class="ep-text-small ep-mb-2"><?php esc_html_e( 'Instantly delete your EventPrime booking data.', 'eventprime-event-calendar-management' ); ?></p>
                                                    <div class="ep-loader" id="ep_event_guest_delete_booking_loader" style="display:none;"></div>
                                                    <button id="ep_delete_guest_booking_data" data-id="<?php echo esc_attr($order_id);?>" data-key="<?php echo esc_attr($order_key);?>" class="ep-btn ep-btn-danger ep-btn-sm"><?php esc_html_e( 'Delete', 'eventprime-event-calendar-management' ); ?></button>
                                                </div>
                                            </div>
                                        <?php endif; 
                                    }
                                    ?>
                                    
                                </div>
                            </div>
                        </div> <?php } ?>
                        
                        <!-- Custom Message from payment gateway -->
                        <?php do_action( 'ep_payment_method_custom_message', $args->em_id );?>
                        
                    </div>
                    
                    <?php if( ! empty( $args->em_attendee_names ) && count( $args->em_attendee_names ) > 0 ) {?>
                        <div class="ep-box-col-12 ep-border ep-rounded ep-my-5 ep-bg-white" id="ep_booking_detail_attendees_container">
                            <div class="ep-box-row ep-border-bottom ">
                                <div class="ep-box-col-12 ep-py-4 ep-ps-4 ep-fw-bold ep-text-uppercase ep-text-small">
                                    <?php esc_html_e( 'Attendees', 'eventprime-event-calendar-management' );?>
                                </div>
                            </div>
                            <?php $booking_attendees_field_labels = array();
                            foreach( $args->em_attendee_names as $ticket_id => $attendee_data ) {
                                $first_key = array_keys( $attendee_data )[0];
                                $booking_attendees_field_labels = $ep_functions->ep_get_booking_attendee_field_labels( $attendee_data[$first_key] );?>
                                <div class="ep-box-row ep-add-scroll-attendee-check-in">
                                    <div class="ep-box-col-12 ep-p-4">
                                        <div class="ep-mb-3 ep-fw-bold ep-text-small">
                                            <?php echo esc_html( $ep_functions->get_event_ticket_name_by_id_event( $ticket_id, $args->event_data ) );?>
                                        </div>
                                        <table class="ep-table ep-table-hover ep-text-small ep-table-borderless ep-ml-4 ep-text-start">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <?php foreach( $booking_attendees_field_labels as $label_key => $labels ) {?>
                                                        <th scope="col">
                                                            <?php echo esc_html__( $labels, 'eventprime-event-calendar-management' );?>
                                                        </th><?php
                                                    }?>

                                                    <?php do_action( 'ep_booking_detail_attendee_table_header' );?>
                                                </tr>
                                            </thead>
                                            <tbody class=""><?php $att_count = 1;
                                                foreach( $attendee_data as $booking_attendees ) {?>
                                                    <tr>
                                                        <th scope="row" class="py-3"><?php echo esc_html( $att_count );?></th>
                                                        <?php 
                                                        // $booking
                                                        $booking_attendees_val = array_values( $booking_attendees );
                                                        foreach( $booking_attendees_field_labels as $label_key => $labels ){?>
                                                            <td class="py-3"><?php
                                                                $formated_val = $ep_functions->ep_get_slug_from_string( $labels );
                                                                $at_val = '---';
                                                                foreach( $booking_attendees_val as $key => $baval ) {
                                                                    if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                                                        $at_val = $baval[$formated_val];
                                                                        if( is_array( $at_val ) ) {
                                                                            $at_val = implode( ', ', $at_val );
                                                                        }
                                                                        break;
                                                                    }
                                                                }
                                                                if( empty( $at_val ) ) {
                                                                    $formated_val = strtolower( $labels );
                                                                    foreach( $booking_attendees_val as $key => $baval ) {
                                                                        if( isset( $baval[$formated_val] ) && ! empty( $baval[$formated_val] ) ) {
                                                                            $at_val = $baval[$formated_val];
                                                                            if( is_array( $at_val ) ) {
                                                                                $at_val = implode( ', ', $at_val );
                                                                            }
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                                $at_val = apply_filters(
                                                                    'ep_booking_attendee_details_frontend',
                                                                    $at_val,
                                                                    $formated_val
                                                                );
                                                                echo wp_kses_post( $at_val );?>
                                                            </td><?php
                                                        }?>

                                                        <?php do_action( 'ep_booking_detail_attendee_table_data', $booking_attendees_val, $ticket_id, $args->em_id );?>
                                                        <!-- Get attendee data with count attribute -->
                                                        <?php do_action( 'ep_booking_detail_all_attendee_data', $booking_attendees_val, $ticket_id, $args->em_id, $att_count );?>
                                                        
                                                    </tr><?php
                                                    $att_count++;
                                                }?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div><?php
                            }?>
                        <?php do_action( 'ep_booking_detail_attendee_table_btns',$booking_attendees_val, $ticket_id, $args->em_id, $args);?>
                    </div>
                        <?php
                    }
                    // booking data
                    if( ! empty( $args->em_booking_fields_data ) && count( $args->em_booking_fields_data ) > 0 ) {
                        foreach( $args->em_booking_fields_data as $booking_fields ) {
                            $formated_val = $ep_functions->ep_get_slug_from_string( $booking_fields['label'] );?>
                            <div class="ep-box-col-12 ep-border ep-rounded ep-mt-5 ep-bg-white ep_booking_detail_booking_fields_container">
                                <div class="ep-box-row ep-border-bottom">
                                    <div class="ep-box-col-12 ep-py-4 ep-ps-4 ep-fw-bold ep-text-uppercase ep-text-small">
                                        <?php echo esc_html( $booking_fields['label'] );?>
                                    </div>
                                </div>
                                <div class="ep-box-row">
                                    <div class="ep-box-col-12 ep-p-4">
                                        <div class="ep-mb-3 ep-fw-bold ep-text-small">
                                            <?php 
                                            if( ! empty( $booking_fields[$formated_val] ) ) {
                                                if( is_array( $booking_fields[$formated_val] ) ) {
                                                    echo wp_kses_post(implode( ', ', $booking_fields[$formated_val] ));
                                                } else{
                                                    echo esc_html( $booking_fields[$formated_val] );
                                                }
                                            }?>
                                        </div>
                                    </div>
                                </div>
                            </div><?php
                        }
                    }?>

                    <?php do_action('ep_front_user_booking_details_custom_data', $args );

                } 
                else{?>
                    <div class="ep-box-col-12 ep-fs-2 ep-my-5 ep-text-center">
                        <?php esc_html_e( 'To view the tickets, you need to login.', 'eventprime-event-calendar-management' );?>&nbsp;
                    </div>
                    <div class="ep-box-row ep-py-3">
                        <div class="ep-box-col-12 ep-fs-6 ep-text-small ep-border ep-rounded ep-p-3">
                            <?php echo do_shortcode( '[em_login redirect="reload"]' );?>
                        </div>
                    </div><?php
                }
            } else{?>
                <div class="ep-alert ep-alert-warning ep-mt-3">
                    <?php esc_html_e( 'No booking detail found!', 'eventprime-event-calendar-management' );?>
                </div><?php
            }?>
        </div>

        <div class="ep-modal ep-modal-view" id="ep_event_booking_cancellation_action" ep-modal="ep_booking_cancellation_modal" style="display: none;" data-booking_id='<?php if( ! empty( $args->em_id ) ) { echo esc_attr( wp_json_encode( $args->em_id ) ); }?>'>
            <div class="ep-modal-overlay" ep-modal-close="ep_booking_cancellation_modal"></div>
            <div class="ep-modal-wrap ep-modal-l">
                <div class="ep-modal-content">
                    <div class="ep-modal-body"> 
                        <div class="ep-box-row ep-my-4">
                            <div class="ep-box-col-12 ep-py-3 ep-my-4 ep-text-center">
                                <?php esc_html_e( 'Are you sure you want to cancel this booking?', 'eventprime-event-calendar-management' );?>
                                
                                <div class="ep-loader ep-my-4" id="ep_event_booking_cancellation_loader" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="ep-modal-footer ep-my-4 ep-d-flex ep-items-end ep-content-center" id="ep_modal_buttonset">
                            
                            <button type="button" class="button ep-mr-3 ep-modal-close ep-booking-cancel-modal-close" ep-modal-close="ep_booking_cancellation_modal">
                                <?php esc_html_e( 'Cancel', 'eventprime-event-calendar-management' );?>
                            </button>
                            <button type="button" class="button button-primary button-large" id="ep_event_booking_cancel_booking">
                                <?php esc_html_e( 'Ok', 'eventprime-event-calendar-management' );?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>  

<?php
}
else
{
 
?>
<div class="ep-booking-container ep-box-wrap">
    <div class="ep-box-row">
        <div class="ep-alert ep-alert-warning ep-mt-3">
            <?php esc_html_e( 'The event linked to this booking no longer exists. Please contact the site administrator for assistance.', 'eventprime-event-calendar-management' );?>
        </div>
    </div>
</div>
<?php
}