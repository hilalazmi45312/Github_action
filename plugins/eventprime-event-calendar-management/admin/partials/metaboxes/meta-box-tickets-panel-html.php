<?php
/**
 * Event tickets panel html
 */
defined( 'ABSPATH' ) || exit;
$ep_functions = new Eventprime_Basic_Functions;
$em_enable_booking = get_post_meta( $post->ID, 'em_enable_booking', true );
$extensions = $ep_functions->ep_get_activate_extensions();
$sanitizer = new EventPrime_sanitizer();
$show_tickets = $show_message = '';
$event_has_ticket = 0;
$is_event_expired = $ep_functions->check_event_has_expired( $single_event_data );?>
<div id="ep_event_ticket_data" class="panel ep_event_options_panel">
    <div class="ep-box-wrap">
        <?php if( $is_event_expired ) {
            $show_tickets = '';$show_message = 1;?>
            <div class="ep-box-row ep-p-3" id="ep_show_event_expire_warning">
                <div class="ep-alert ep-alert-warning ep-mt-3 ep-py-2">
                    <strong><?php esc_html_e( 'This event has ended.', 'eventprime-event-calendar-management' ); ?></strong>
                </div>
            </div><?php
        }
        if( empty( $single_event_data->em_enable_booking ) || $single_event_data->em_enable_booking !== 'bookings_on' ) {
            $show_tickets = '';$show_message = 1;?>
            <div class="ep-box-row ep-p-3" id="ep_event_booking_not_enabled_warning">
                <div class="ep-alert ep-alert-warning ep-mt-3 ep-py-2">
                    <strong><?php esc_html_e( 'The event booking is not enabled.', 'eventprime-event-calendar-management' ); ?></strong>
                </div>
            </div><?php
        }
        if( empty( $show_tickets ) ) {
            $show_tickets = 'style=display:none;';
        }?>

        <!-- Ticket Options Buttons Area --> 
        <div id="ep-event-tickets-options" <?php echo esc_attr( $show_tickets );?>>
            <div class="ep-box-row ep-p-3">
                <div class="ep-box-col-12 ep-d-flex ep-content-center">
                    <button type="button" class="button button-large ep-m-3 ep-open-modal"  data-id="ep-ticket-category-modal" id="ep_event_open_category_modal" title="<?php esc_html_e( 'Add Tickets Category', 'eventprime-event-calendar-management' );?>">
                        <?php esc_html_e( 'Add Tickets Category', 'eventprime-event-calendar-management' );?>
                    </button>        
                    <button type="button" class="button button-large ep-m-3 ep-open-modal" data-id="ep_event_ticket_tier_modal" id="ep_event_open_ticket_modal" title="<?php esc_html_e( 'Add Tickets', 'eventprime-event-calendar-management' );?>">
                        <?php esc_html_e( 'Add Ticket Type', 'eventprime-event-calendar-management' );?>
                    </button>
                </div>
                <!--Existing Tickets-->
                <div id="ep_existing_tickets_category_list" class="ep_existing_tickets_category_list ep-box-col-12">
                    <?php 
                    $existing_cat_data = $ep_functions->get_existing_category_lists( $post->ID );
                    $ep_ticket_category_data = ( ! empty( $existing_cat_data ) ? wp_json_encode($existing_cat_data) : '' );?>
                    <input type="hidden" name="em_ticket_category_data" id="ep_ticket_category_data" value="<?php echo esc_attr( $ep_ticket_category_data );?>" />
                    <input type="hidden" name="em_ticket_category_delete_ids" id="ep_ticket_category_delete_ids" value="" />
                
                    <div class="ep-box-row ep-p-3" id="ep_existing_tickets_list">
                        <?php if( !empty( $existing_cat_data ) && count( $existing_cat_data ) > 0 ) {
                            foreach( $existing_cat_data as $key => $cat_data ) {
                                $cat_data = $sanitizer->sanitize($cat_data);
                                $cat_row_data = wp_json_encode($cat_data);
                                $row_key = $key + 1;
                                $cat_row_id = 'ep_ticket_cat_section'. $row_key; ?>
                                <div class="ep-box-col-12 ep-p-3 ep-border ep-rounded ep-mb-3 ep-bg-white ep-shadow-sm ui-state-default ep-cat-list-class" id="<?php echo esc_attr( $cat_row_id );?>" data-cat_row_data="<?php echo esc_attr( $cat_row_data );?>">
                                    <div class="ep-box-row ep-mb-3 ep-items-center">
                                        <div class="ep-box-col-1">
                                            <span class="ep-ticket-cat-sort material-icons ep-cursor-move text-muted" data-parent_id="<?php echo esc_attr( $cat_row_id );?>" >drag_indicator</span>
                                        </div>
                                        <div class="ep-box-col-5">
                                            <h4 class="ep-cat-name"><?php echo esc_html( $cat_data->name );?></h4>
                                        </div>
                                        <div class="ep-box-col-4">
                                            <h4 class="ep-cat-capacity">
                                                <?php echo esc_html__( 'Capacity', 'eventprime-event-calendar-management' ) . ': '. esc_html( $cat_data->capacity );?>
                                            </h4>
                                        </div>
                                        <div class="ep-box-col-1">
                                            <a href="javascript:void(0)" class="ep-ticket-cat-edit ep-text-primary" data-parent_id="<?php echo esc_attr( $cat_row_id );?>" title="<?php esc_html_e( 'Edit Category', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Edit', 'eventprime-event-calendar-management' );?></a>
                                        </div>
                                        <div class="ep-box-col-1">
                                            <a href="javascript:void(0)" class="ep-ticket-cat-delete ep-item-delete" data-parent_id="<?php echo esc_attr( $cat_row_id );?>" title="<?php esc_html_e( 'Delete Category', 'eventprime-event-calendar-management' );?>"><?php esc_html_e( 'Delete', 'eventprime-event-calendar-management' );?></a>
                                        </div>
                                    </div>
                                
                                    <div class="ep-ticket-category-section" data-parent_category_id="<?php echo esc_attr( $row_key );?>">
                                        <?php 
                                        $existing_cat_ticket_data = $ep_functions->get_existing_category_ticket_lists( $post->ID, $cat_data->id );
                                        if( !empty( $existing_cat_ticket_data ) ) {
                                            $tic_row = 1;$event_has_ticket = 1;
                                            foreach( $existing_cat_ticket_data as $ticket ) {
                                                $ticket = $sanitizer->sanitize($ticket);
                                                $icon_url = '';
                                                if( ! empty( $ticket->icon ) ) {
                                                    $icon_url = wp_get_attachment_url( $ticket->icon );
                                                }
                                                $ticket->icon_url = $icon_url;
                                                $ticket_row_data = wp_json_encode( $ticket );
                                                $ticket_row_id = '';
                                                $ticket_row_id = 'ep_cat_' . $row_key . '_ticket' . $tic_row;?>
                                                <div class="ep-box-row ep-tickets-cate-ticket-row" id="<?php echo esc_attr( $ticket_row_id );?>" data-ticket_row_data="<?php echo esc_attr( $ticket_row_data );?>">
                                                    <div class="ep-box-col-12">
                                                        <div class="ep-box-row ep-border ep-rounded ep-ml-2 ep-my-1 ep-mr-2 ep-bg-white ep-items-center ui-state-default">
                                                            <div class="ep-box-col-1 ep-p-3">
                                                                <span class="ep-ticket-row-sort material-icons ep-cursor-move ep-text-muted" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>">drag_indicator</span>
                                                            </div>
                                                            <div class="ep-box-col-3 ep-p-3">
                                                                <?php echo esc_html( $ticket->name );?>
                                                            </div>
                                                            <div class="ep-box-col-2 ep-p-3">
                                                                <span><?php echo esc_html( $ep_functions->ep_price_with_position( $ticket->price ) );?></span>
                                                            </div>
                                                            <div class="ep-box-col-3 ep-py-3">
                                                                <span>
                                                                    <?php echo esc_html__( 'Capacity', 'eventprime-event-calendar-management' ) . ' ' . esc_html( $ticket->capacity ) . '/' . esc_html( $cat_data->capacity );?>
                                                                </span>
                                                            </div>
                                                            <?php do_action( 'ep_event_tickets_action_icons', $post->ID, $ticket->id );?>
                                                            <div class="ep-box-col-1 ep-p-3">
                                                                <a href="javascript:void(0)" class="ep-ticket-row-edit ep-text-primary ep-cursor" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>" data-parent_category_id="<?php echo esc_attr( $cat_row_id );?>" title="<?php esc_html_e( 'Edit Ticket', 'eventprime-event-calendar-management' );?>">Edit</a>
                                                            </div>
                                                            <div class="ep-box-col-1 ep-p-3">
                                                                <span class="ep-ticket-row-delete ep-text-danger ep-cursor" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>" title="<?php esc_html_e( 'Delete Ticket', 'eventprime-event-calendar-management' );?>">Delete</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div><?php
                                                $tic_row++;
                                            }
                                        }?>
                                    </div><?php
                                    $total_caps = $ep_functions->get_category_tickets_capacity( $existing_cat_ticket_data );
                                    if( $total_caps < $cat_data->capacity ){?>
                                        <div class="ep_category_add_tickets_button" id="ep_category_add_tickets_button_<?php echo esc_attr( $cat_row_id );?>">
                                            <button type="button" class="button button-large ep-m-3 ep-open-category-ticket-modal" data-id="ep_event_ticket_tier_modal" data-parent_id="<?php echo esc_attr( $cat_row_id );?>"><?php esc_html_e( 'Add Tickets', 'eventprime-event-calendar-management' );?></button>
                                        </div><?php
                                    }?>
                                </div><?php
                            }
                        }?>
                    </div>
                </div>
                <div id="ep_existing_individual_tickets_list">
                    <input type="hidden" name="ep_ticket_order_arr" id="ep_ticket_order_arr" value="" /> 
                    <?php $get_existing_individual_ticket_lists = $ep_functions->get_existing_individual_ticket_lists( $post->ID );
                    $ep_ticket_data = ( ! empty( $get_existing_individual_ticket_lists ) ? $get_existing_individual_ticket_lists : array() );
                    //$em_ticket_individual_data = ( ! empty( $get_existing_individual_ticket_lists ) ? wp_json_encode( $get_existing_individual_ticket_lists ) : '' );?>
                    <input type="hidden" name="em_ticket_individual_data" id="ep_ticket_individual_data" value="" />
                    <input type="hidden" name="em_ticket_individual_delete_ids" id="ep_ticket_individual_delete_ids" value="" />
                    <?php 
                    do_action('ep_ticket_additional_hidden_fields');
                    do_action('ep_ticket_additional_hidden_fields_data',$post->ID);
                    ?>
                    <div class="ep-ticket-category-section">
                        <?php if( !empty( $ep_ticket_data ) ) {
                            
                            if ( isset( get_post_meta( $post->ID )["ep_ticket_order_arr"] ) && !empty( get_post_meta( $post->ID )["ep_ticket_order_arr"] ) ) {
                                $ep_ticket_order_arr = maybe_unserialize( get_post_meta( $post->ID )["ep_ticket_order_arr"][0] ); 
                                if ( is_array( $ep_ticket_order_arr ) ) {
                                    $ids_fliped_for_ep_ticket_order_arr = array_flip($ep_ticket_order_arr);
                                    usort($ep_ticket_data, function($a, $b) use ($ids_fliped_for_ep_ticket_order_arr) {
                                        if ( isset( $ids_fliped_for_ep_ticket_order_arr[$a->id] ) && isset( $ids_fliped_for_ep_ticket_order_arr[$b->id] ) ) {
                                            $posA = $ids_fliped_for_ep_ticket_order_arr[$a->id];
                                            $posB = $ids_fliped_for_ep_ticket_order_arr[$b->id];
                                            return $posA - $posB;
                                        }
                                    }); 
                                }
                            }
                            
                            $tic_row = 1;$event_has_ticket = 1;
                            foreach( $ep_ticket_data as $ticket ) {
                                $ticket = $sanitizer->sanitize($ticket);
                                $icon_url = '';
                                if( ! empty( $ticket->icon ) ) {
                                    $icon_url = wp_get_attachment_url( $ticket->icon );
                                }
                                $ticket->icon_url = $icon_url;
                                $ticket_row_data = wp_json_encode( $ticket );
                                $ticket_row_id = 'ep_event_ticket_row' . $tic_row;?>
                                <div class="ep-box-row ep-tickets-indi-ticket-row" id="<?php echo esc_attr( $ticket_row_id );?>" data-ticket_row_data="<?php echo esc_attr( $ticket_row_data );?>">
                                    <div class="ep-box-col-12">
                                        <div class="ep-box-row ep-border ep-rounded ep-ml-2 ep-my-1 ep-mr-2 ep-bg-white ep-items-center ui-state-default">
                                            <div class="ep-box-col-1 ep-p-3">
                                                <span class="ep-ticket-row-sort material-icons ep-cursor-move ep-text-muted" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>">drag_indicator</span>
                                            </div>
                                            <div class="ep-box-col-3 ep-p-3">
                                                <?php echo esc_html( $ticket->name );?>
                                            </div>
                                            <div class="ep-box-col-2 ep-p-3">
                                                <span><?php echo esc_html( $ep_functions->ep_price_with_position( $ticket->price ) );?></span>
                                            </div>
                                            <div class="ep-box-col-3 ep-p-3">
                                                <span>
                                                    <?php echo esc_html__( 'Capacity', 'eventprime-event-calendar-management' ) . ' ' . esc_html( $ticket->capacity );?>
                                                </span>
                                            </div>
                                            <?php do_action( 'ep_event_tickets_action_icons', $post->ID, $ticket->id );?>
                                            <div class="ep-box-col-1 ep-p-3">
                                                    <a href="javascript:void(0)" class="ep-ticket-row-edit ep-text-primary ep-cursor" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>" title="<?php esc_html_e( 'Edit Ticket', 'eventprime-event-calendar-management' );?>">Edit</a>
                                            </div>
                                            <div class="ep-box-col-1 ep-py-3">
                                                <a href="javascript:void(0)" class="ep-ticket-row-delete  ep-text-danger ep-cursor" data-parent_id="<?php echo esc_attr( $ticket_row_id );?>" title="<?php esc_html_e( 'Delete Ticket', 'eventprime-event-calendar-management' );?>">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                </div><?php
                                $tic_row++;
                            }
                        }?>
                    </div>
                </div>
                <!--Existing Tickets Ends-->
            </div>
        </div>
    
        <!--Add Ticket Category Modal-->
        <div id="ep-ticket-category-modal" class="ep-modal-view" style="display: none;">
            <div class="ep-modal-overlay ep-modal-overlay-fade-in"></div>  
            <div class="popup-content ep-modal-wrap ep-modal-sm ep-modal-out">
                <div class="ep-modal-body">
                    <div class="ep-modal-titlebar ep-d-flex ep-items-center">
                        <h3 class="ep-modal-title ep-px-3 ">
                            <?php esc_html_e('Add Tickets Category', 'eventprime-event-calendar-management');  ?> 
                        </h3>
                        <a href="#" class="ep-modal-close close-popup" data-id="ep-ticket-category-modal">&times;</a>
                    </div>
                    <div class="ep-modal-content-wrapep-box-wrap">
                            <div class="ep-box-row ep-p-3 ep-box-w-75">
                                <div class="ep-box-col-12">
                                    <label class="ep-form-label">
                                        <?php esc_html_e('Tickets Category', 'eventprime-event-calendar-management'); ?>
                                    </label>
                                    <input type="text" class="ep-form-control" name="em_ticket_category_name" id="ep_ticket_category_name">
                                    <div class="ep-text-muted ep-text-small">
                                        <?php esc_html_e('Category name will be visible to users while selecting tickets.', 'eventprime-event-calendar-management'); ?><?php $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-create-ticket-categories-for-wordpress-events/');?>
                        
                                    </div>
                                    <div id="ep_ticket_category_name_error" class="ep-error-message"></div>
                                </div> 

                                <div class="ep-box-col-12 ep-mt-3">
                                    <label class="ep-form-label">
                                        <?php esc_html_e('Total Quantity/Inventory', 'eventprime-event-calendar-management'); ?>
                                    </label>
                                    <input type="number" class="ep-form-control" name="em_ticket_category_capacity" min="1" id="ep_ticket_category_capacity">
                                    <div class="ep-text-muted ep-text-small">
                                        <?php esc_html_e('Combined capacity or inventory of the tickets you wish to include in this tickets category should not exceed this number.', 'eventprime-event-calendar-management'); ?>
                                    </div>
                                    <div id="ep_ticket_category_capacity_error" class="ep-error-message"></div>
                                </div> 
                            </div>

                        <div class="ep-modal-footer ep-mt-3 ep-d-flex ep-items-end ep-content-right">
                            <button type="button" class="button ep-mr-3 ep-modal-close close-popup" data-id="ep-ticket-category-modal"><?php esc_html_e('Close', 'eventprime-event-calendar-management'); ?></button>
                            <button type="button" class="button button-primary button-large" id="ep_save_ticket_category"><?php esc_html_e('Add', 'eventprime-event-calendar-management'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--Add Ticket Category Modal-->
        
        <!-- Add  Ticket Tier Modal -->   
        <div id="ep_event_ticket_tier_modal" class="ep-modal-view" style="display: none;">
            <input type="hidden" name="em_ticket_category_id" value="" />
            <input type="hidden" name="em_ticket_id" value="" />
            <input type="hidden" name="em_ticket_parent_div_id" value="" />
            <div class="ep-modal-overlay ep-modal-overlay-fade-in close-popup" data-id="ep_event_ticket_tier_modal"></div>
            <div class="popup-content ep-modal-wrap ep-modal-xssm ep-modal-out">
                <div class="ep-modal-body">    
                    <div class="ep-modal-titlebar ep-d-flex ep-items-center ep-border-0">
                        <h3 class="ep-modal-title ep-px-4 ep-pt-3"><?php esc_html_e( 'Add Ticket Type', 'eventprime-event-calendar-management' );?></h3>
                        
                        <a href="#" class="ep-modal-close close-popup" data-id="ep_event_ticket_tier_modal"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"></path></svg></a>
                    </div>  
                    <div class="ep-modal-content-wrap ep-box-wrap">
                        <div class="ep-box-row ep-p-3">
                            
                            <div class="ep-box-col-3 ep-box-pl-0">
                                <ul class="ep-nav-pills ep-nav ep-nav-tabs ep-flex-column ep-m-0 ep-border-0" id="ep-ticket-Tabs" role="tablist">
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-details" id="ep-ticket-details-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100 ep-tab-active"> <?php esc_html_e('Details','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-price" id="ep-ticket-price-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Price','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-inventory" id="ep-ticket-inventory-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Quantity ','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-availability" id="ep-ticket-availability-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Availability','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-limits" id="ep-ticket-limits-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Limits','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-visibility" id="ep-ticket-visibility-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Visibility','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-offers" id="ep-ticket-offers-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Offers','eventprime-event-calendar-management');?></a></li>
                                    <li class="ep-tab-item ep-mx-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-template-extension" id="ep-ticket-template-extension-tab" class="ep-tab-link ep-border-0 ep-rounded-1 ep-px-2 ep-box-w-100"><?php esc_html_e('Ticket Template','eventprime-event-calendar-management');?></a></li>
                                </ul>
                            </div>
                            
                            <div class="ep-box-col-9 ep-box-pr-0">
                                <div id="ep-tab-container" class="ep-box-w-100">
                                    
                                <!--Tab content Details--->
                                
                                <div class="ep-tab-content ep-ticket-details ep-tab-active" id="ep-ticket-details"  role="tabpanel" > 
                                    <div class="ep-article-guide ep-ticket-type-guide ep-text-end">
                                       
                                            <strong><?php esc_html_e( 'Ticket Type', 'eventprime-event-calendar-management' );?>:</strong>
                                           <?php $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-create-ticket-types-for-events-using-eventprime/',__('Read full guide','eventprime-event-calendar-management'));?>
                                            
                                   
                                    </div>
                                      
                                    
                                <div class="ep-box-row">

                                 <div class="ep-box-col-12">
                                <label class="ep-form-label">
                                    <?php esc_html_e( 'Name', 'eventprime-event-calendar-management' );?> <i><?php esc_html_e( '(required)', 'eventprime-event-calendar-management' );?></i>
                                </label>
                                <input type="text" class="ep-form-control" name="name" id="ep_event_ticke_name">
                                <div class="ep-text-muted ep-text-small">
                                    <?php esc_html_e('Ticket names are visible to the user on the frontend.', 'eventprime-event-calendar-management'); ?>
                                </div>
                                <div class="ep-error-message" id="ep_event_ticket_name_error"></div>
                            </div> 

                            <div class="ep-box-col-12 ep-mt-3">
                                <label class="ep-form-label">
                                    <?php esc_html_e( 'Description', 'eventprime-event-calendar-management' );?>
                                </label>
                                <textarea class="ep-form-control" name="description" id="ep_event_ticke_description"  rows="3" cols="50" ></textarea>
                                <div class="ep-text-muted ep-text-small">
                                    <?php esc_html_e('Ticket description are visible to the user on the frontend during ticket selection.', 'eventprime-event-calendar-management'); ?>
                                </div>
                            </div>   

                            <div class="ep-box-col-12 ep-mt-3">
                                <label class="ep-form-label">
                                    <?php esc_html_e( 'Icon', 'eventprime-event-calendar-management' );?>
                                </label>
                                <div class="ep-d-flex">
                                <input type="hidden" name="icon" id="ep_event_ticket_icon">
                                <button type="button" class="upload_offer_icon_button button">
                                    <span class="material-icons">add</span>
                                    <!--<?php esc_html_e( 'Upload Icon', 'eventprime-event-calendar-management' );?>-->
                                </button>
                                
                                <div id="ep_event_ticket_icon_image"></div>
                                </div>
                                <div class="ep-text-muted ep-text-small">
                                    <?php esc_html_e('A small icon or an image representative of the ticket type.', 'eventprime-event-calendar-management'); ?>
                                </div>
                                
                            </div>                

                              

                           
                            <?php do_action('em_ticket_additional_fields');?>
                                           </div>
                                    
                                 
                                    
                                </div>
                                
                                <!--Tab content Details Ends---> 
                                
                                 <!--Tab content Price--->
                                 
                                    <div class="ep-tab-content ep-ticket-price ep-item-hide" id="ep-ticket-price" role="tabpannel">
                                        
                                        <div class="ep-box-row">
                                            <div class="ep-box-col-9 ep-mt-3">
                                                <label class="ep-form-label">
                                                    <?php esc_html_e('Base Price (per ticket)', 'eventprime-event-calendar-management'); ?>
                                                </label>
                                                <input type="number" class="ep-form-control" name="price" id="ep_event_ticket_price" min="0.00" step="0.01">
                                                 <div class="ep-error-message" id="ep_event_ticket_price_error"></div>
                                            </div>                
                                            <div class="ep-box-col-3 ep-mt-3 ep-d-flex ep-items-end ep-pb-3">
                                                <?php 
                                                    $selected_currency = $ep_functions->ep_get_global_settings( 'currency' );
                                                    if( empty( $selected_currency ) ) {
                                                        $selected_currency = 'USD';
                                                    }
                                                ?>
                                               
                                                <span><?php echo esc_html( $selected_currency );?></span>
                                            </div>
                                            <div class="ep-box-col-12">
                                                <p class="ep-text-small ep-text-muted"><?php 
                                                $payment_setting_url  = $ep_functions->ep_get_dashboard_setting_url('payments');
echo sprintf(
    esc_html__('Set 0 or leave blank for free events. More currency options are available in %s.', 'eventprime-event-calendar-management'), 
    '<a href="' . esc_url($payment_setting_url) . '" target="_blank">' . esc_html__('Settings', 'eventprime-event-calendar-management') . '</a>'
);
?></p>
                                            </div>
                                            
                                             <div class="ep-box-col-12 ep-mt-3">
                                <button type="button" class="button button-primary button-large" id="add_more_additional_ticket_fee"><?php esc_html_e( 'Add Additional Fee', 'eventprime-event-calendar-management' );?></button>
                            </div>

                            <div class="ep-additional-ticket-fee-wrapper ep-box-w-100" id="ep_additional_ticket_fee_wrapper"></div>
                            
                                            <div class="ep-box-col-12">
                                                <p class="ep-text-small ep-text-muted"><?php esc_html_e('Add optional fees to be charged per ticket. Each fee consists of a label and a value, and will be added to the base price. You can create multiple fees (e.g., service charges, facility fees). These apply individually to every ticket purchased and will be visible to the user while purchasing tickets.','eventprime-event-calendar-management'); $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-add-additional-fees-to-wordpress-event-tickets/'); ?></p>
                                            </div>
                                        </div>
                                        
                                    </div>
                                
                                <!--Tab content Price Ends---> 
                                <!--Tab content Inventory---> 
                                
                                <div class="ep-tab-content ep-ticket-inventory ep-item-hide" id="ep-ticket-inventory" role="tabpannel">
                                    <div class="ep-box-col-12 ep-mt-3">
                                        <label class="ep-form-label">
                                            <?php esc_html_e('Available Quantity', 'eventprime-event-calendar-management'); ?>
                                        </label>
                                        <input type="number" class="ep-form-control" min="0" name="capacity" id="ep_event_ticket_qty">
                                        <div class="ep-error-message" id="ep_event_ticket_qty_error"></div>
                                        <span id="ep_ticket_remaining_capacity" data-max_ticket_label="<?php esc_html_e('Remaining Seats', 'eventprime-event-calendar-management'); ?>"></span>
                                    </div> 
                                    <div class="ep-box-col-12">
                                        <p class="ep-text-small ep-text-muted"><?php esc_html_e('Total number of tickets available for this ticket type.','eventprime-event-calendar-management');?></p>
                                    </div>
                                    
                                    <div class="ep-box-col-10 ep-mt-3">
                                        <div class="ep-d-flex ">
                                            <label class="ep-form-check-label ep-toggle-btn ep-mr-2" for="ep_show_remaining_tickets">
                                                <input class="ep-form-check-input" type="checkbox" name="show_remaining_tickets" value="1" id="ep_show_remaining_tickets">
                                                <span class="ep-toogle-slider round"></span>
                                            </label>
                                            <label>
                                                <?php esc_html_e( 'Display the number of tickets left on the event page.', 'eventprime-event-calendar-management' );?>
                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('For example 22 tickets remaining etc.','eventprime-event-calendar-management'); $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-show-remaining-tickets-on-wordpress-event-page/');?></div>
                                            </label>
                                        </div>
                                    </div>
                                   
                                    
                                </div>
                                
                                <!--Tab content Inventory Ends---> 
                                
                                  <!--Tab content Availability---> 
                                  
                                  <div class="ep-tab-content ep-ticket-availability ep-item-hide" id="ep-ticket-availability" role="tabpannel">
                                      <div class="ep-box-row">
                                          <div class="ep-box-col-12">
                                              <h2 class="ep-m-0 ep-p-0 ep-fw-bold"><?php esc_html_e('Tickets Availability Period','eventprime-event-calendar-management');?></h2>
                                              <div class="ep-text-small ep-text-muted">
                                                  <?php esc_html_e('Choose the start and the end dates between which this ticket type will be available for booking. You can skip this section if you wish to start selling tickets as soon as the event is published.','eventprime-event-calendar-management'); $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-set-ticket-sale-dates-for-wordpress-events/'); ?>
                                              </div>
                                          </div>
                                          
                                          <div class="ep-box-col-10 ep-mt-3">
                                            <div class="ep-d-flex">
                                                <label class="ep-form-check-label ep-toggle-btn ep-mr-2" for="ep_show_ticket_booking_dates">
                                                    <input class="ep-form-check-input" type="checkbox" name="show_ticket_booking_dates" value="1" id="ep_show_ticket_booking_dates">
                                                    <span class="ep-toogle-slider round"></span>
                                                </label>
                                                <label>
                                                    <?php esc_html_e( 'Show tickets availability dates on the frontend', 'eventprime-event-calendar-management' );?>
                                                    <div class="ep-text-small ep-text-muted"><?php esc_html_e('For example, Tickets available from Aug 24 etc.','eventprime-event-calendar-management');?></div>
                                                </label>
                                            </div>
                                        </div>
                                 
                                          
                                         <div class="ep-box-col-12 ep-nav-tabs-internal">
                                            <ul class="ep-nav-pills ep-nav ep-nav-tabs ep-flex-nowrap ep-justify-content-space-between" id="ep-ticket-availability-tabs" role="tablist">
                                                <li class="ep-tab-item ep-mx-0 ep-box-w-100 ep-my-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-availability_start" class="ep-tab-link ep-border-0 ep-rounded-1 ep-py-3 ep-px-2 ep-box-w-100 ep-justify-content-center ep-tab-active"> <?php esc_html_e('Starts','eventprime-event-calendar-management');?></a></li>
                                                <li class="ep-tab-item ep-mx-0 ep-box-w-100 ep-my-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-ticket-availability_end" class="ep-tab-link ep-border-0 ep-rounded-1 ep-py-3 ep-px-2 ep-box-w-100 ep-justify-content-center"><?php esc_html_e('Ends','eventprime-event-calendar-management');?></a></li>
                                            </ul>
                                             
                                             <div id="ep-tab-container" class="ep-tabs-container-internal">

                                                 <div class="ep-tab-content ep-ticket-availability_start ep-tab-active" id="ep-ticket-availability_start" role="tabpannel">
                                                   
                                                     <div class="ep-box-row">  
                                                     <div class="ep-box-col-12 ep-mt-3">
                                                         <label class="ep-fw-bold"><?php esc_html_e('Date Type', 'eventprime-event-calendar-management'); ?></label>
                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_start_booking_type" type="radio" name="em_ticket_start_booking_type" value="custom_date" id="ep_ticket_start_booking_custom_date" checked>
                                                            <label class="ep-form-check-label" for="ep_ticket_start_booking_custom_date">
                                                                <?php esc_html_e('Choose your own Date', 'eventprime-event-calendar-management'); ?>
                                                                    <div class="ep-text-small ep-text-muted"><?php esc_html_e('Enter fix date and time.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                        

                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_start_booking_type" type="radio" name="em_ticket_start_booking_type" value="event_date" id="ep_ticket_start_booking_event_date">
                                                            <label class="ep-form-check-label" for="ep_ticket_start_booking_event_date">
                                                                <?php esc_html_e('Choose from saved event dates', 'eventprime-event-calendar-management'); ?>
                                                                  <div class="ep-text-small ep-text-muted"><?php esc_html_e('Date will be automatically synced with a saved event date. For example, event start date.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                  


                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_start_booking_type" type="radio" name="em_ticket_start_booking_type" value="relative_date" id="ep_ticket_start_booking_relative_date">
                                                            <label class="ep-form-check-label" for="ep_ticket_start_booking_relative_date">
                                                                <?php esc_html_e('Set a relative Date', 'eventprime-event-calendar-management'); ?>
                                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('Relative Dates are set in relation to event start or end dates. For example, 2 days before the start of event.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                      
                                                           
                                                      
                                                    </div>
                                                     <div class="ep-box-col-6 ep-mt-3 ep_ticket_start_booking_options ep_ticket_start_booking_custom_date">
                                                         <label class="ep-form-label">
                                                             <?php esc_html_e('Choose Date', 'eventprime-event-calendar-management'); ?>
                                                         </label>
                                                         <input type="text" class="ep-form-control ep_metabox_custom_date_picker" name="em_ticket_start_booking_date" id="ep_ticket_start_booking_date" data-start="" data-end="event_end">
                                                     </div>
                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_start_booking_options ep_ticket_start_booking_custom_date">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Choose Time', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <input type="text" class="ep-form-control epTimePicker" name="em_ticket_start_booking_time" id="ep_ticket_start_booking_time">
                                                    </div> 
                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_start_booking_options ep_ticket_start_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Enter Days', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <input type="number" class="ep-form-control" name="em_ticket_start_booking_days" id="ep_ticket_start_booking_days" min="0">
                                                    </div>
                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_start_booking_options ep_ticket_start_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Days Option', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <select class="ep-form-control" name="em_ticket_start_booking_days_option" id="ep_ticket_start_booking_days_option">
                                                            <option value="before"><?php esc_html_e( 'Days Before', 'eventprime-event-calendar-management');?></option>
                                                            <option value="after"><?php esc_html_e( 'Days After', 'eventprime-event-calendar-management');?></option>
                                                        </select>
                                                    </div>
                                                    <div class="ep-box-col-12 ep-mt-3 ep_ticket_start_booking_options ep_ticket_start_booking_event_date ep_ticket_start_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Event Option', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <select class="ep-form-control" name="em_ticket_start_booking_event_option" id="ep_ticket_start_booking_event_option">
                                                            <?php $existing_cat_data = $ep_functions->get_ticket_booking_event_date_options( $post->ID );
                                                            if( ! empty( $existing_cat_data ) ) {
                                                                foreach( $existing_cat_data as $key => $option ) {?>
                                                                    <option value="<?php echo esc_attr( $key );?>"><?php echo esc_html( $option );?></option><?php
                                                                }
                                                            }?>
                                                        </select>
                                                        <div class="ep-error-message" id="em_ticket_start_booking_event_option_error"></div>
                                                    </div>
                                                         
                                                     </div>
                                                     
                                                 </div>

                                            <div class="ep-tab-content ep-ticket-availability_end ep-item-hide" id="ep-ticket-availability_end" role="tabpannel">
                                                     
                                                <div class="ep-box-row">
                                                     
                                                    <div class="ep-box-col-12 ep-mt-3">
                                                        <div class="ep-radio-option-title ep-fw-bold"><?php esc_html_e('Date Type', 'eventprime-event-calendar-management'); ?></div>
                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_ends_booking_type" type="radio" name="em_ticket_ends_booking_type" value="custom_date" id="ep_ticket_ends_booking_custom_date" checked>
                                                            <label class="ep-form-check-label" for="ep_ticket_ends_booking_custom_date">
                                                                <?php esc_html_e('Choose your own Date', 'eventprime-event-calendar-management'); ?>
                                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('Enter fix date and time.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                  

                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_ends_booking_type" type="radio" name="em_ticket_ends_booking_type" value="event_date" id="ep_ticket_ends_booking_event_date">
                                                            <label class="ep-form-check-label" for="ep_ticket_ends_booking_event_date">
                                                                <?php esc_html_e('Choose from saved event dates', 'eventprime-event-calendar-management'); ?>
                                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('Date will be automatically synced with a saved event date. For example, event end date.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                      
                                                        <div class="ep-form-check ep-mt-2">
                                                            <input class="ep-form-check-input ep_ticket_ends_booking_type" type="radio" name="em_ticket_ends_booking_type" value="relative_date" id="ep_ticket_ends_booking_relative_date">
                                                            <label class="ep-form-check-label" for="ep_ticket_ends_booking_relative_date">
                                                                <?php esc_html_e('Set a relative Date', 'eventprime-event-calendar-management'); ?>
                                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('Relative Dates are set in relation to event start or end dates. For example, 2 days before the end of event.','eventprime-event-calendar-management');?></div>
                                                            </label>
                                                        </div>
                                                 
                                                    </div>

                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_ends_booking_options ep_ticket_ends_booking_custom_date">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Choose Date', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <input type="text" class="ep-form-control ep_metabox_custom_date_picker" name="em_ticket_ends_booking_date" id="ep_ticket_ends_booking_date" data-start="" data-end="event_end">
                                                    </div>

                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_ends_booking_options ep_ticket_ends_booking_custom_date">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Choose Time', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <input type="text" class="ep-form-control epTimePicker" name="em_ticket_ends_booking_time" id="ep_ticket_ends_booking_time">
                                                    </div> 

                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_ends_booking_options ep_ticket_ends_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Enter Days', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <input type="number" class="ep-form-control" name="em_ticket_ends_booking_days" id="ep_ticket_ends_booking_days" min="0">
                                                    </div>

                                                    <div class="ep-box-col-6 ep-mt-3 ep_ticket_ends_booking_options ep_ticket_ends_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Days Option', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <select class="ep-form-control" name="em_ticket_ends_booking_days_option" id="ep_ticket_ends_booking_days_option">
                                                            <option value="before"><?php esc_html_e( 'Days Before', 'eventprime-event-calendar-management');?></option>
                                                            <option value="after"><?php esc_html_e( 'Days After', 'eventprime-event-calendar-management');?></option>
                                                        </select>
                                                    </div>

                                                    <div class="ep-box-col-12 ep-mt-3 ep_ticket_ends_booking_options ep_ticket_ends_booking_event_date ep_ticket_ends_booking_relative_date" style="display:none;">
                                                        <label class="ep-form-label">
                                                            <?php esc_html_e( 'Event Option', 'eventprime-event-calendar-management' );?>
                                                        </label>
                                                        <select class="ep-form-control" name="em_ticket_ends_booking_event_option" id="ep_ticket_ends_booking_event_option">
                                                            <?php $existing_cat_data = $ep_functions->get_ticket_booking_event_date_options( $post->ID );
                                                            if( ! empty( $existing_cat_data ) ) {
                                                                foreach( $existing_cat_data as $key => $option ) {?>
                                                                    <option value="<?php echo esc_attr( $key );?>"><?php echo esc_html( $option );?></option><?php
                                                                }
                                                            }?>
                                                        </select>
                                                    </div>
                                                         
                                                   </div>

                                            </div>

                                             </div>
                                             
                                         </div>  
                                          
                                     
                                          
                                      
                                          
                                          
                                         
                                          
                                          
                                          
                                      </div>
                                  </div>
                                  <!--Tab content Availability Ends---> 
                                  <!--Tab Ticket limits ---> 
                                   <div class="ep-tab-content ep-ticket-limits ep-item-hide" id="ep-ticket-limits" role="tabpannel">
                                       <div class="ep-box-col-12 ep-mt-3">
                                             <label class="ep-form-label">
                                                <?php esc_html_e( 'Minimum Tickets Per Order', 'eventprime-event-calendar-management');?>
                                            </label>
                                           <input type="number" id="ep_min_ticket_no" class="ep-form-control" min="0" name="min_ticket_no" value="1">
                                            <div class="ep-error-message" id="ep_event_ticket_min_ticket_error"></div>
                                        </div> 
                                        <div class="ep-box-col-12">
                                            <p class="ep-text-small ep-text-muted"><?php esc_html_e('Customer will need to add minimum number of tickets before becoming eligible for checkout.','eventprime-event-calendar-management');?></p>
                                        </div>

                                        <div class="ep-box-col-12 ep-mt-3">
                                            <label class="ep-form-check-label" for="ep_max_ticket_no">
                                                <?php esc_html_e( 'Maximum Tickets Per Order', 'eventprime-event-calendar-management');?>
                                            </label>
                                            <input type="number" id="ep_max_ticket_no" class="ep-form-control" min="0" name="max_ticket_no">
                                            <div class="ep-error-message" id="ep_event_ticket_max_ticket_error"></div>
                                        </div>
                                       
                                       <div class="ep-box-col-12">
                                            <p class="ep-text-small ep-text-muted"><?php esc_html_e('Customer will not able to add more than maximum number of tickets allowed.','eventprime-event-calendar-management');?></p>
                                        </div>
                                       
                                   </div>
                                  
                                    <!--Tab Ticket limits Ends---> 
                                    
                                    <div class="ep-tab-content ep-ticket-visibility ep-item-hide" id="ep-ticket-visibility" role="tabpannel">
                                      <div class="ep-box-row">
                                          <div class="ep-box-col-12">
                                              <h2 class="ep-m-0 ep-p-0 ep-fw-bold"><?php esc_html_e('Visibility','eventprime-event-calendar-management');?></h2>
                                              <p class="ep-text-small ep-text-muted">
                                                  <?php esc_html_e('Control who can see this ticket type. You can selectively make this ticket type available to specific group of visitors.','eventprime-event-calendar-management'); $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-hide-event-tickets-based-on-user-roles/');?>
                                              </p>
                                          </div>
                                          
                                          
                                          <div class="ep-box-col-12 ep-mt-3">
                                                <div class="ep-fw-bold"><?php esc_html_e( 'Select user type', 'eventprime-event-calendar-management');?></div>
                                                    <div class="ep-form-check ep-mt-2">
                                                        <input class="ep-form-check-input" type="radio" name="em_tickets_user_visibility" value="public" id="em_tickets_user_visibility_public" checked>
                                                        <label class="ep-form-check-label" for="em_tickets_user_visibility_public">
                                                            <?php esc_html_e( 'Public', 'eventprime-event-calendar-management');?>
                                                            <div class="ep-text-small ep-text-muted"><?php esc_html_e('Everyone will be able to see and purchase this type of ticket.','eventprime-event-calendar-management');?></div>
                                                        </label>
                                                    </div>
                                                  
                                                
                                                <div class="ep-form-check ep-mt-2">
                                                        <input class="ep-form-check-input" type="radio" name="em_tickets_user_visibility" value="all_login" id="em_tickets_user_visibility_registered">
                                                        <label class="ep-form-check-label" for="em_tickets_user_visibility_registered">
                                                            <?php esc_html_e( 'Registered users', 'eventprime-event-calendar-management');?>
                                                            <div class="ep-text-small ep-text-muted"><?php esc_html_e('Users who have registered on your website will be able to purchase this type of ticket.','eventprime-event-calendar-management');?></div>
                                                        </label>
                                                    </div>
                                                 
                                                
                                                <div class="ep-form-check ep-mt-2">
                                                        <input class="ep-form-check-input" type="radio" name="em_tickets_user_visibility" value="user_roles" id="em_tickets_user_visibility_roles">
                                                        <label class="ep-form-check-label" for="em_tickets_user_visibility_roles">
                                                            <?php esc_html_e( 'Filtered by user role', 'eventprime-event-calendar-management');?>
                                                            <div class="ep-text-small ep-text-muted"><?php esc_html_e('Users who belong to selected user roles will be able to purchase this type of ticket.','eventprime-event-calendar-management');?></div>
                                                        </label>
                                                    </div>
                                           
                                                  <?php do_action("ep_extend_tic_visibility_option"); ?>
                                                
                                                <div class="ep-box-col-12 ep-mt-3" id="ep_ticket_visibility_user_roles_select" style="display:none;">
                                                <!-- <label class="ep-form-label"><?php esc_html_e( 'Roles', 'eventprime-event-calendar-management');?></label> -->
                                                <select name="em_ticket_visibility_user_roles[]" id="ep_ticket_visibility_user_roles" multiple="multiple" class="ep-form-control ep_user_roles_options">
                                                    <?php foreach( $ep_functions->ep_get_all_user_roles() as $key => $role ){?>
                                                        <option value="<?php echo esc_attr( $key );?>">
                                                            <?php echo esc_html($role);?>
                                                        </option><?php
                                                    }?>
                                                </select>
                                            </div>

                                            <?php do_action("ep_extend_tic_visibility_sub_option"); ?>
       
                                            </div>
                                        
                                             <div class="ep-box-col-12 ep-mt-3">
                                                <div class="ep-fw-bold"><?php esc_html_e( 'For Ineligible Users', 'eventprime-event-calendar-management');?></div>
                                                    <div class="ep-form-check ep-mt-2">
                                                        <input class="ep-form-check-input" type="radio" name="em_ticket_for_invalid_user" value="hidden" id="ep_ticket_for_hidden_user">
                                                        <label class="ep-form-check-label" for="ep_ticket_for_hidden_user">
                                                            <?php esc_html_e( 'Hide Tickets', 'eventprime-event-calendar-management');?>
                                                                <div class="ep-text-small ep-text-muted"><?php esc_html_e('Ticket type will not be visible on the ticket selection screen.','eventprime-event-calendar-management');?></div>
                                                        </label>
                                                    </div>
                                              
                                                
                                                <div class="ep-form-check ep-mt-2">
                                                    <input class="ep-form-check-input" type="radio" name="em_ticket_for_invalid_user" value="disabled" id="ep_ticket_for_disabled_user" checked>
                                                    <label class="ep-form-check-label" for="ep_ticket_for_disabled_user">
                                                        <?php esc_html_e( 'Show tickets as disabled', 'eventprime-event-calendar-management');?>
                                                        <div class="ep-text-small ep-text-muted"><?php esc_html_e('Ticket type will be visible on the ticket selection screen but in greyed-out state.','eventprime-event-calendar-management');?></div>
                                                    </label>
                                                </div>
                                   
                                            </div>
                                       
                                          
                                          
                                             
                                       
                                          
                                          
                                          
                                      </div>
                                    </div>
                                    
                                    <div class="ep-tab-content ep-ticket-offers ep-item-hide" id="ep-ticket-offers" role="tabpannel">
                                      <div class="ep-box-row">
                                            <div class="ep-box-col-12">
                                                <h2 class="ep-m-0 ep-p-0 ep-fw-bold"><?php esc_html_e('Offers','eventprime-event-calendar-management');?></h2>
                                                <p class="ep-text-small ep-text-muted">
                                                    <?php esc_html_e('Control who can see this ticket type. You can selectively make this ticket type available to specific group of visitors.','eventprime-event-calendar-management'); $ep_functions->ep_documentation_link_read_more_html('https://theeventprime.com/how-to-add-offers-to-wordpress-events/'); ?>
                                                </p>
                                            </div>
                                          
                                            <div class="ep-box-col-12 ep-mt-3">
                                                <button type="button" class="button button-primary button-large ep-open-modal" data-id="ep_ticket_add_offer_modal" id="ep_ticket_add_offer_modal_btn"><?php esc_html_e( 'Add Offer', 'eventprime-event-calendar-management');?></button>
                                            </div>
                                          
                                          
                                          <div class="ep-box-row ep-my-4 ep-multiple-offer">
                                              <div class="ep-box-col-6">
                                                  <label class="ep-form-label"><?php esc_html_e('How to Handle Multiple Offers?', 'eventprime-event-calendar-management'); ?></label>
                                                  <select class="ep-form-control" name="multiple_offers_option" id="em_multiple_offers_option">
                                                      <option value="stack_offers"><?php esc_html_e('Stack offers', 'eventprime-event-calendar-management'); ?></option>
                                                      <option value="first_offer"><?php esc_html_e('First one that applies', 'eventprime-event-calendar-management'); ?></option>
                                                  </select>
                                              </div>
                                              <div class="ep-box-col-6">
                                                  <label class="form-label"><?php esc_html_e('Max Cumulative Discount', 'eventprime-event-calendar-management'); ?></label>
                                                  <input type="number" min="0" class="ep-form-control" name="multiple_offers_max_discount" id="em_multiple_offers_max_discount">
                                              </div>
                                          </div>
                                          
                                          
                                          
                                          <div class="ep-box-row ep-mt-4" id="ep_ticket_offers_wrapper" style="display: none;">
                                              <div class="ep-box-col-12 ep-my-3">
                                                  <strong><?php esc_html_e('Added Offers', 'eventprime-event-calendar-management'); ?></strong>
                                              </div>
                                              <input type="hidden" name="offers" id="ep_event_ticket_offers" value="" />
                                              <div class="ep-box-col-12" id="ep_existing_offers_list"></div>
                                              <div class="ep-error-message ep-lh-lg ep-mt-3" id="ep_ticket_offer_not_save_error"></div>
                                          </div> 
                                          
                                  
                                      </div>
                                    </div>
                                    
                                    
                                    
                                    <div class="ep-tab-content ep-ticket-template-extension ep-item-hide" id="ep-ticket-template-extension" role="tabpannel">
                                      <div class="ep-box-row">
                                    <div class="ep-box-col-12 ep-mt-3">
                                <label class="ep-form-check-label" for="ep-ticket-template">
                                    <?php esc_html_e( 'Tickets Template', 'eventprime-event-calendar-management');?>
                                </label>
                                <select id="ep-event-ticket-template" class="ep-form-control" name="em_ticket_template" disabled>
                                    <?php do_action( 'ep_event_get_ticket_template_options' );?>
                                </select>
                                <div class="ep-text-muted ep-text-small">
                                    <?php esc_html_e( 'Templates allow you to design custom ticket styles which are sent to the users as PDF for printing.', 'eventprime-event-calendar-management'); 
                                    if( ! in_array( 'Eventprime_Event_Tickets', $extensions ) ) {
                                        echo '<br>';
                                        esc_html_e( 'To use ticket templates, please install', 'eventprime-event-calendar-management' );?>
                                        <a href="<?php echo esc_url( admin_url('edit.php?post_type=em_event&page=ep-extensions') );?>" target="_blank"><?php echo esc_html( 'Event Tickets extension' );?></a><?php
                                    }?>
                                </div>
                            </div>
                                          
                                      </div>
                                    </div>
                                    
                                    
                                
                                </div>
                            </div>
                            
                        </div>

                        <!-- Tabs -->
                        
                        
                        <!-- Modal Wrap  End --> 
                    
                        <div class="ep-modal-footer ep-border-0 ep-mt-3 ep-d-flex ep-items-end ep-content-right">
                            <button type="button" class="button ep-mr-3 ep-modal-close close-popup" data-id="ep_event_ticket_tier_modal"><?php esc_html_e( 'Close', 'eventprime-event-calendar-management');?></button>
                            <button type="button" class="button button-primary button-large" id="ep_save_ticket_tier"><?php esc_html_e( 'Save changes', 'eventprime-event-calendar-management');?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add  Ticket Tier Modal End --> 
        
        
        <!---Ticket Offer Modal--->
        
        
         <div id="ep_ticket_add_offer_modal" class="ep-modal-view" style="display:none">
             <div class="ep-modal-overlay close-popup ep-offer-modal-close" data-id="ep_ticket_add_offer_modal"></div>
                                              <div class="popup-content ep-modal-wrap ep-modal-xssm ep-modal-out">

                                                  <div class="ep-modal-body">    
                                                      <div class="ep-modal-titlebar ep-d-flex ep-items-center ep-border-0">
                                                          <h3 class="ep-modal-title ep-px-4 ep-pt-3"><?php esc_html_e( 'Add New Offer', 'eventprime-event-calendar-management');?></h3>
                                                          <a href="#" class="ep-modal-close close-popup ep-offer-modal-close" data-id="ep_ticket_add_offer_modal"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"></path></svg></a>
                                                      </div> 
                                                  
                                                      <div class="ep-modal-content-wrap ep-box-wrap">
                                                       
                                                        <div class="ep-box-row ep-p-2" id="ep_event_ticket_offer_wrapper">
                                                            <div class="ep-box-col-12">
                                                                <label class="ep-form-label"><?php esc_html_e( 'Name', 'eventprime-event-calendar-management');?></label>
                                                                <input type="text" class="ep-form-control" name="em_ticket_offer_name" id="ep_ticket_offer_name">
                                                                <div class="ep-text-muted ep-text-small">
                                                                    <?php esc_html_e('Examples: Weekend Offer, Early-bird Discount etc. Offer name is visible on the frontend.', 'eventprime-event-calendar-management'); ?>
                                                                </div>
                                                                <div class="ep-error-message" id="ep_event_offer_name_error"></div>
                                                            </div>

                                                            <div class="ep-box-col-12 ep-mt-3">
                                                                <label class="ep-form-label"><?php esc_html_e( 'Description', 'eventprime-event-calendar-management');?></label>
                                                                <textarea class="ep-form-control" name="em_ticket_offer_description"></textarea>
                                                                <div class="ep-text-muted ep-text-small">
                                                                    <?php esc_html_e('Offer Description is visible on the frontend.', 'eventprime-event-calendar-management'); ?>
                                                                </div>
                                                            </div>

                                                            <div class="ep-box-col-12 ep-mt-3">
                                                                <div class="ep-form-check ep-form-check-inline">
                                                                    <input class="ep-form-check-input" type="checkbox" name="em_ticket_show_offer_detail" value="1" id="show-offer-details">
                                                                    <label class="ep-form-check-label" for="show-offer-details">
                                                                        <?php esc_html_e( 'Show this offer in the offers section of the event', 'eventprime-event-calendar-management');?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            
                                                            
                                                            
                                                            <div class="ep-box-col-12 ep-nav-tabs-internal">
                                                                <ul class="ep-nav-pills ep-nav ep-nav-tabs ep-flex-nowrap ep-justify-content-space-between" id="ep-offer-start-tabs" role="tablist">
                                                                    <li class="ep-tab-item ep-mx-0 ep-box-w-100 ep-my-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-offer-start-date" class="ep-tab-link ep-border-0 ep-rounded-1 ep-py-3 ep-px-2 ep-box-w-100 ep-justify-content-center ep-tab-active"> <?php esc_html_e('Starts','eventprime-event-calendar-management');?></a></li>
                                                                    <li class="ep-tab-item ep-mx-0 ep-box-w-100 ep-my-0" role="presentation"><a href="javascript:void(0)" data-tag="ep-offer-end-date" class="ep-tab-link ep-border-0 ep-rounded-1 ep-py-3 ep-px-2 ep-box-w-100 ep-justify-content-center"><?php esc_html_e('Ends','eventprime-event-calendar-management');?></a></li>
                                                                </ul>
                                                                
                                                                <div id="ep-tab-container" class="ep-tabs-container-internal">
                                                                    <div class="ep-tab-content ep-ticket-availability_start ep-tab-active" id="ep-offer-start-date" role="tabpannel">
                                                                   

                                                                        <div class="ep-box-col-12 ep-mt-3">
                                                                            <div class="ep-box-row" >
                                                                                <div class="ep-box-col-12">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Offer Start', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control ep_offer_start_booking_type" name="em_offer_start_booking_type">
                                                                                        <option value="custom_date"><?php esc_html_e('Custom Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="event_date"><?php esc_html_e('Event Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="relative_date"><?php esc_html_e('Relative Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_start_booking_options ep_offer_start_booking_custom_date">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Choose Date', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="text" class="ep-form-control ep_metabox_custom_date_picker" name="em_offer_start_booking_date" id="ep_offer_start_booking_date" data-start="booking_start" data-end="booking_end">
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_start_booking_options ep_offer_start_booking_custom_date">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Choose Time', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="text" class="ep-form-control epTimePicker" name="em_offer_start_booking_time" id="ep_offer_start_booking_time">
                                                                                </div> 
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_start_booking_options ep_offer_start_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Enter Days', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="number" class="ep-form-control" name="em_offer_start_booking_days" min="0">
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_start_booking_options ep_offer_start_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Days Option', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control" name="em_offer_start_booking_days_option">
                                                                                        <option value="before"><?php esc_html_e('Days Before', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="after"><?php esc_html_e('Days After', 'eventprime-event-calendar-management'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="ep-box-col-12 ep-mt-3 ep_offer_start_booking_options ep_offer_start_booking_event_date ep_offer_start_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Event Option', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control" name="em_offer_start_booking_event_option">
                                                                                        <?php
                                                                                        $existing_cat_data = $ep_functions->get_ticket_booking_event_date_options($post->ID);
                                                                                        if (!empty($existing_cat_data)) {
                                                                                            foreach ($existing_cat_data as $key => $option) {
                                                                                                ?>
                                                                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($option); ?></option><?php
                                                                                    }
                                                                                }
                                                                                        ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="ep-tab-content ep-ticket-availability_start" id="ep-offer-end-date" role="tabpannel">
                                                                   
                                                                        <div class="ep-box-col-12 ep-mt-3">
                                                                            <div class="ep-box-row">
                                                                                <div class="ep-box-col-12">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Offer Ends', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control ep_offer_ends_booking_type" name="em_offer_ends_booking_type">
                                                                                        <option value="custom_date"><?php esc_html_e('Custom Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="event_date"><?php esc_html_e('Event Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="relative_date"><?php esc_html_e('Relative Date', 'eventprime-event-calendar-management'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_ends_booking_options ep_offer_ends_booking_custom_date">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Choose Date', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="text" class="ep-form-control ep_metabox_custom_date_picker" name="em_offer_ends_booking_date" id="ep_offer_ends_booking_date" data-start="booking_start" data-end="booking_end">
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_ends_booking_options ep_offer_ends_booking_custom_date">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Choose Time', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="text" class="ep-form-control epTimePicker" name="em_offer_ends_booking_time" id="ep_offer_ends_booking_time">
                                                                                </div> 
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_ends_booking_options ep_offer_ends_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Enter Days', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <input type="number" class="ep-form-control" name="em_offer_ends_booking_days" min="0">
                                                                                </div>
                                                                                <div class="ep-box-col-6 ep-mt-3 ep_offer_ends_booking_options ep_offer_ends_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Days Option', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control" name="em_offer_ends_booking_days_option">
                                                                                        <option value="before"><?php esc_html_e('Days Before', 'eventprime-event-calendar-management'); ?></option>
                                                                                        <option value="after"><?php esc_html_e('Days After', 'eventprime-event-calendar-management'); ?></option>
                                                                                    </select>
                                                                                </div>
                                                                                <div class="ep-box-col-12 ep-mt-3 ep_offer_ends_booking_options ep_offer_ends_booking_event_date ep_offer_ends_booking_relative_date" style="display:none;">
                                                                                    <label class="ep-form-label">
                                                                                        <?php esc_html_e('Event Option', 'eventprime-event-calendar-management'); ?>
                                                                                    </label>
                                                                                    <select class="ep-form-control" name="em_offer_ends_booking_event_option">
                                                                                        <?php
                                                                                        $existing_cat_data = $ep_functions->get_ticket_booking_event_date_options($post->ID);
                                                                                        if (!empty($existing_cat_data)) {
                                                                                            foreach ($existing_cat_data as $key => $option) {
                                                                                                ?>
                                                                                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($option); ?></option><?php
                                                                                    }
                                                                                }
                                                                                        ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                </div> 
                                                                
                                                                
                                                            </div>
                                                            

                                                          
                                                            <div class="ep-box-row">
                                                                <div class="ep-box-col-12 ep-my-3 ep-py-2 ep-border-bottom"></div>
                                                            </div>
                                                         

                                                            <div class="ep-box-col-6 ep-mt-3">
                                                                <label class="ep-form-label"><?php esc_html_e( 'Offer Type', 'eventprime-event-calendar-management');?></label>
                                                                <select id="ep_ticket_offer_type" class="ep-form-control" name="em_ticket_offer_type">
                                                                    <option value="seat_based"><?php esc_html_e( 'Admittance Based', 'eventprime-event-calendar-management');?></option>
                                                                    <option value="role_based"><?php esc_html_e( 'User Role Based', 'eventprime-event-calendar-management');?></option>
                                                                    <option value="volume_based"><?php esc_html_e( 'Volume Based', 'eventprime-event-calendar-management');?></option>
                                                                    <?php do_action("ep_extend_tic_offer_option"); ?> 
                                                                </select>
                                                            </div>

                                                            <div class="ep-box-col-6 ep-mt-3">
                                                                <div class="ep-box-row">
                                                                    <div class="ep-box-col-12 offer-fields ep-seat-based-offer-wrapper" id="ep_ticket_offer_seat_based">
                                                                        <div class="ep-box-row">
                                                                            <div class="ep-box-col-6">
                                                                                <label class="ep-form-label"><?php esc_html_e( 'Select Admittance Order', 'eventprime-event-calendar-management');?></label>
                                                                                <select class="ep-form-control" name="em_ticket_offer_seat_option">
                                                                                    <option value=""><?php esc_html_e( 'Select Option', 'eventprime-event-calendar-management');?></option>
                                                                                    <option value="first"><?php esc_html_e( 'First', 'eventprime-event-calendar-management');?></option>
                                                                                    <option value="last"><?php esc_html_e( 'Last', 'eventprime-event-calendar-management');?></option>
                                                                                </select>
                                                                            </div>
                                                                            <div class="ep-box-col-6">
                                                                                <label class="ep-form-label"><?php esc_html_e( 'Enter Number', 'eventprime-event-calendar-management');?></label>
                                                                                <input type="number" min="0" class="ep-form-control" name="em_ticket_offer_seat_number" placeholder="<?php esc_html_e( 'Count', 'eventprime-event-calendar-management');?>">
                                                                            </div>

                                                                        </div>
                                                                    </div>

                                                                    <div class="offer-fields ep-box-col-12" id="ep_ticket_offer_role_based" style="display: none;">
                                                                        <label class="ep-form-label"><?php esc_html_e( 'Select Roles', 'eventprime-event-calendar-management');?></label>
                                                                        <select name="em_ticket_offer_user_roles" id="em_ticket_offer_user_roles" multiple="multiple" class="ep-form-control ep_user_roles_options">
                                                                            <?php foreach( $ep_functions->ep_get_all_user_roles() as $key => $role ){?>
                                                                                <option value="<?php echo esc_attr( $key );?>">
                                                                                    <?php echo esc_html($role);?>
                                                                                </option><?php
                                                                            }?>
                                                                        </select>
                                                                        
                                                                        
                                                                        <!-- Select Offer type -->
                                                                        
                                                                        
                                                                        
                                                                        
                                                                        <!-- Select Offer type end -->
                                                                        
                                                                     
                                                                        
                                                                    </div>

                                                                    <div class="offer-fields ep-box-col-12" id="ep_ticket_offer_volume_based" style="display: none;">
                                                                        <label class="ep-form-label"><?php esc_html_e( 'Enter Number', 'eventprime-event-calendar-management');?></label>
                                                                        <input type="number" class="ep-form-control" name="em_ticket_offer_volumn_count" placeholder="<?php esc_html_e( 'Minimum Number of Tickets', 'eventprime-event-calendar-management');?>">
                                                                    </div>

                                                                    <?php do_action("ep_extend_tic_offer_sub_option"); ?>

                                                                </div>
                                                            </div>

                                                            <div class="ep-box-col-6 ep-mt-3" id="ep_offer_discount_type">
                                                                <label class="ep-form-label">
                                                                    <?php esc_html_e( 'Select Discount Type', 'eventprime-event-calendar-management' );?>
                                                                </label>
                                                                <select class="ep-form-control" name="em_ticket_offer_discount_type" id="ep_ticket_offer_discount_type">
                                                                    <option value=""><?php esc_html_e( 'Select Discount Type', 'eventprime-event-calendar-management');?></option>
                                                                    <option value="flat"><?php esc_html_e( 'Flat Discount', 'eventprime-event-calendar-management');?></option>
                                                                    <option value="percentage"><?php esc_html_e( 'Percentage', 'eventprime-event-calendar-management');?></option>
                                                                </select>
                                                                <div class="ep-error-message" id="ep_ticket_offer_discount_type_error"></div>
                                                            </div>

                                                            <div class="ep-box-col-6 ep-mt-3" id="ep_offer_discount">
                                                                <label class="ep-form-label">
                                                                    <?php esc_html_e( 'Enter Discount', 'eventprime-event-calendar-management' );?>
                                                                </label>
                                                                <input type="number" min="0" class="ep-form-control" name="em_ticket_offer_discount" id="ep_ticket_offer_discount" placeholder="<?php esc_html_e( 'Enter Discount', 'eventprime-event-calendar-management');?>">
                                                                <div class="ep-error-message" id="ep_ticket_offer_discount_error"></div>
                                                            </div>

                                                            <div class="ep-box-col-12 ep-mt-3">
                                                                <button type="button" class="button button-primary button-large" id="ep_ticket_add_offer"><?php esc_html_e( 'Add Offer', 'eventprime-event-calendar-management');?></button>
                                                                
                                                            </div>

                                                        </div>

                                                    

                                                     </div>
                                                  
                                                </div>
                                              </div>
                                            </div>
        
        <!--Ticket offer Modal End--->
        
        
        
        <input type="hidden" name="ep_event_has_ticket" id="ep_event_has_ticket" value="<?php echo absint( $event_has_ticket );?>" >

        <?php do_action( 'ep_event_after_admin_tickets_section' );?>
        <?php if( empty( $show_message ) ) {?>
            <div class="ep-box-row ep-p-3" id="ep_event_booking_disabled_warning" style="display: none;">
                <div class="ep-alert ep-alert-warning ep-mt-3 ep-py-2">
                    <strong><?php esc_html_e( 'The event booking is not enabled.', 'eventprime-event-calendar-management' ); ?></strong>
                </div>
            </div><?php
        }?>
    </div>
</div>

<div id="ep_event_booking_turn_off_modal" class="ep-modal-view" style="display: none;">
    <div class="ep-modal-overlay ep-modal-overlay-fade-in close-popup" data-id="ep_event_booking_turn_off_modal"></div>
    <div class="popup-content ep-modal-wrap ep-modal-sm ep-modal-out"> 
        <div class="ep-modal-body">
            <div class="ep-modal-titlebar ep-d-flex ep-items-center">
                <h3 class="ep-modal-title ep-px-3">
                    <?php esc_html_e( 'No ticket found', 'eventprime-event-calendar-management' ); ?>
                </h3>
                <a href="#" class="ep-modal-close close-popup" data-id="ep_event_booking_turn_off_modal">&times;</a>
            </div> 
            <div class="ep-modal-content-wrap"> 
                <div class="ep-box-wrap">
                    <div class="ep-box-row ep-p-3 ep-box-w-75 ep-event-booking-field-manager">
                        <div class="ep-box-col-12 form-field">
                            <?php esc_html_e( 'You have not added any tickets for this event. Therefore, bookings for this event will be turned off.', 'eventprime-event-calendar-management' );?>
                        </div>
                    </div>
                </div>
                <div class="ep-modal-footer ep-mt-3 ep-d-flex ep-items-end ep-content-right" id="ep_modal_buttonset">
                    <span class="spinner ep-mr-2 ep-mb-2 ep-text-end" id="ep_event_booking_turn_off_loader"></span>
                    <button type="button" class="button ep-mr-3 ep-modal-close close-popup" data-id="ep_event_booking_turn_off_modal" id="ep_event_booking_turn_off_cancel" title="<?php echo esc_attr( 'Cancel', 'eventprime-event-calendar-management' ); ?>"><?php esc_html_e('Cancel', 'eventprime-event-calendar-management'); ?></button>
                    <button type="button" class="button button-primary button-large" id="ep_event_booking_turn_off_continue" title="<?php echo esc_attr( 'Turn Off Booking', 'eventprime-event-calendar-management' ); ?>"><?php esc_html_e('Turn Off Booking', 'eventprime-event-calendar-management'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>