jQuery( function( $ ) {

    // ical export
    $( document ).on( 'click', '#ep_event_ical_export', function() {
        let event_id = $( this ).attr( 'data-event_id' );
        if( event_id ) {
            window.location = window.location.href + '&event='+event_id+'&download=ical';
        }
    });
    
    // cancel booking
    $( document ).on( 'click', '#ep_event_booking_cancel_booking', function() {
        $( '#ep_event_booking_cancellation_loader' ).show();
        let booking_id = $( '#ep_event_booking_cancellation_action' ).data( 'booking_id' );
        if( booking_id ) {
            booking_id = JSON.parse( booking_id );
            let data = { 
                action    : 'ep_event_booking_cancel', 
                security  : ep_event_booking_detail.booking_cancel_nonce,
                booking_id: booking_id
            };
            $.ajax({
                type        : "POST",
                url         : eventprime.ajaxurl,
                data        : data,
                success     : function( response ) {
                    $( '#ep_event_booking_cancellation_loader' ).hide();
                    // hide popup
                    $( '[ep-modal="ep_booking_cancellation_modal"]' ).fadeOut(200);
                    $( 'body' ).removeClass( 'ep-modal-open-body' );
                    if( response.success == true ) {
                        show_toast( 'success', response.data.message );
                        setTimeout( function() {
                            location.reload();
                        }, 2000);
                    } else{
                        show_toast( 'error', response.data.error );
                    }
                }
            });
        }
    });
    
    
    // delete guest booking
    $( document ).on( 'click', '#ep_delete_guest_booking_data', function() {
        $( '#ep_event_guest_delete_booking_loader' ).show();
        let booking_id = $( '#ep_delete_guest_booking_data' ).data( 'id' );
        let order_key = $( '#ep_delete_guest_booking_data' ).data( 'key' );
        if(booking_id && order_key ) {
           
            let data = { 
                action    : 'ep_delete_guest_booking_data', 
                security  : ep_event_booking_detail.booking_cancel_nonce,
                booking_id: booking_id,
                key       : order_key
            };
            $.ajax({
                type        : "POST",
                url         : eventprime.ajaxurl,
                data        : data,
                success     : function( response ) {
                    $( '#ep_event_guest_delete_booking_loader' ).hide();
                    if( response.success == true ) {
                        show_toast( 'success', response.data.message );
                        setTimeout( function() {
                            //location.reload();
                            window.location.href = response.data.redirect_url;

                        }, 2000);
                        
                    } else{
                        show_toast( 'error', response.data.error );
                    }
                }
            });
        }
    });
    
});