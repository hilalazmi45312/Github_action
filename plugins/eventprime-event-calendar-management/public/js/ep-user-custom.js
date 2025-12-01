jQuery( function( $ ) {

    $( document ).ready( function() {
        // add select2 on the registration form timezone field
        $( '#ep_register_timezone' ).select2({
            
        }); 
    });
    // show registration form
    $( document ).on( 'click', '#em_login_register', function() {
        $( '#ep_attendee_register_form_wrapper' ).show();
        $( '#ep_attendee_login_form_wrapper' ).hide();
    });

    // show login form
    $( document ).on( 'click', '#em_register_login', function() {
        $( '#ep_attendee_login_form_wrapper' ).show();
        $( '#ep_attendee_register_form_wrapper' ).hide();
    });

    // Only allow selection for past days for DOB field. 
    let now = new Date(); 
    let minDate = now.toISOString().substring(0,10);
    $('#ep_register_dob').prop('max', minDate);

    $( document ).on( 'click', '.ep-login-form-submit', function(e){
        e.preventDefault();
        var formData = new FormData(document.getElementById('ep_attendee_login_form'));
        formData.append('action', 'ep_submit_login_form');
        $('.ep-spinner').addClass('ep-is-active');
        $('.ep-login-response').html();
        $.ajax({
            type : "POST",
            url : ep_frontend.ajaxurl,
            data: formData,
            contentType: false,
            processData: false,       
            success: function(response) {
                $('.ep-spinner').removeClass('ep-is-active');
                if( response.data.success ) {
                    $( '.ep-login-response' ).html( '<div class="ep-login-success">' + response.data.msg + '</div>' );
                    // redirect
                    if( response.data.redirect ) {
                        setTimeout( function() {
                            if( response.data.redirect == 'reload' ) {
                                location.reload();
                            } else{
                                window.location.replace( response.data.redirect );
                            }
                        }, 1000);
                    } else{
                        $( document ).trigger( 'afterEPLogin', { response: response } );
                    }
                }else{
                    if ( eventprime.global_settings.login_google_recaptcha ) {
                        grecaptcha.reset();
                    }
                    $('.ep-login-response').html('<div class="ep-error-message">'+response.data.msg+'</div>');
                }
                
            }
        });
    });
    
    $( document ).on( 'click', '.ep-register-form-submit', function(e){
        e.preventDefault();
        var formData = new FormData(document.getElementById('ep_attendee_register_form'));
        formData.append('action', 'ep_submit_register_form');
        $('.ep-spinner').addClass('ep-is-active');
        $('.ep-register-response').html();
        if($('#ep_register_phone').length){
            if($('#ep_register_phone').val() != ''){
                var validPhone = registrationPhoneCheck($('#ep_register_phone').val());
                //console.log(validPhone);
                if(validPhone === false){
                    $('.ep-register-response').html('<div class="ep-error-message">'+$("#ep_register_phone").data('validate')+'</div>');
                    $('.ep-spinner').removeClass('ep-is-active');
        
                    return false;
                    
                }
            }
        }
        // Email Validation 
        let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        let isEmailValid = emailPattern.test( $('#ep_register_email').val() );
        if ( !isEmailValid ) {
            show_toast( 'error', 'Incorrect Email, please verify.' ); 
            $('.ep-spinner').removeClass('ep-is-active');
            return false; 
        }
        $.ajax({
            type : "POST",
            url : ep_frontend.ajaxurl,
            data: formData,
            contentType: false,
            processData: false,       
            success: function(response) {
                $('.ep-spinner').removeClass('ep-is-active');
                if( response.data.success ) {
                    $('.ep-register-response').html('<div class="ep-success-message">'+response.data.msg+"</div>");
                    if( response.data.redirect !== '' ) {
                        setTimeout(function() {
                            if( response.data.redirect == 'reload' ) {
                                setTimeout( function() {
                                    location.reload();
                                }, 1000 );
                            } else{
                                window.location.replace( response.data.redirect );
                            }
                        }, 1000 );
                    }
                }else{
                    if ( eventprime.global_settings.register_google_recaptcha ) {
                        grecaptcha.reset();
                    }
                    $('.ep-register-response').html('<div class="ep-error-message">'+response.data.msg+"</div>");
                }
            }
        });
    });

    // edit timezone
    $( document ).on( 'click', '#ep-user-profile-timezone-edit', function() {
        $( this ).hide();
        $( '.ep-user-profile-timezone-list' ).show();
    });

    // save the user timezone
    $( document ).on( 'click', '#ep_user_profile_timezone_save', function() {
        let time_zone = $( '#ep_user_profile_timezone_list' ).val();
        if( time_zone ) {
            $( '.ep-loader' ).show();
            let data = { 
                action    : 'ep_update_user_timezone',
                security  : ep_frontend._nonce,
                time_zone : time_zone
            };
            $.ajax({
                type    : "POST",
                url     : eventprime.ajaxurl,
                data    : data,
                success : function( response ) {
                    if( response == -1 ) {
                        show_toast( 'error', ep_frontend.nonce_error );
                        return false;
                    }
                    if( response.success == false ) {
                        show_toast( 'error', response.data.error );
                        return false;
                    } else{
                        show_toast( 'success', response.data.message );
                        $( '.ep-loader' ).hide();
                        $( '#ep_user_profile_timezone_data' ).text( time_zone );
                        $( '#ep-user-profile-timezone-edit' ).show();
                        $( '.ep-user-profile-timezone-list' ).hide();
                    }
                }
            });
        }
    });

    // delete fes event
    $( document ).on( 'click', '#ep_user_profile_delete_user_fes_event', function() {
        let fes_event_id = $( this ).data('fes_event_id' );
        if( fes_event_id ) {
            if( confirm( ep_frontend.delete_event_confirm ) == true ) {
                $( '.ep-loader' ).show();
                let data = { 
                    action    : 'ep_delete_user_fes_event',
                    security  : ep_frontend._nonce,
                    fes_event_id : fes_event_id
                };
                $.ajax({
                    type    : "POST",
                    url     : eventprime.ajaxurl,
                    data    : data,
                    success : function( response ) {
                        if( response == -1 ) {
                            show_toast( 'error', ep_frontend.nonce_error );
                            return false;
                        }
                        if( response.success == false ) {
                            show_toast( 'error', response.data.error );
                            return false;
                        } else{
                            show_toast( 'success', response.data.message );
                            $( '.ep-loader' ).hide();
                            $( '#ep_user_profile_my_events_' + fes_event_id ).remove();
                        }
                    }
                });
            }
        }
    });
    
  
 $('#ep_download_gdpr_privacy_data').on('click', function (e) {
    e.preventDefault();
    const nonce = ep_frontend._nonce;

    // Show loader
    $(".ep-event-loader").show();

    $.post(eventprime.ajaxurl, {
        action: 'ep_export_user_bookings_data',
        nonce: nonce
    }, function (res) {
        if (!res.success) {
            show_toast('error', res.data?.error || 'Something went wrong.');

            // Hide loader after toast (e.g., 3 seconds)
            setTimeout(() => {
                $(".ep-event-loader").hide();
            }, 5000);

            return false;
        }

        // Proceed with file download
        const blob = new Blob([JSON.stringify(res.data.payload, null, 2)], {
            type: 'application/json'
        });

        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = res.data.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        // Show success toast
        show_toast('success', 'Bookings data exported successfully.');

        // Hide loader after toast
        setTimeout(() => {
            $(".ep-event-loader").hide();
        }, 5000);
    });
});


$('#ep_request_data_erasure').on('click', function (e) {
    e.preventDefault();

    const nonce = ep_frontend._nonce;

    // Show loader
    $(".ep-event-loader").show();

    $.post(eventprime.ajaxurl, {
        action: 'ep_request_data_erasure',
        nonce: nonce
    }, function (res) {
        // Display toast
        if (!res.success) {
            show_toast('error', res.data?.error || 'Something went wrong.');

            // Hide loader after toast finishes (3s assumed)
            setTimeout(() => {
                $(".ep-event-loader").hide();
            }, 5000);

            return;
        }

        show_toast('success', res.data.message || 'Request submitted successfully.');

        // Hide loader after toast finishes
        setTimeout(() => {
            $(".ep-event-loader").hide();
        }, 5000); // adjust duration if your toast is longer
    });
});


    
$('#ep_delete_my_data').on('click', function (e) {
    e.preventDefault();

    if (confirm(ep_frontend.delete_my_data_confirm)) {
        const nonce = ep_frontend._nonce;

        // Show loader after user confirms
        $(".ep-event-loader").show();

        $.post(eventprime.ajaxurl, {
            action: 'ep_delete_user_bookings_data',
            nonce: nonce
        }, function (res) {
            if (!res.success) {
                show_toast('error', res.data?.error || 'Something went wrong.');
                
                // Hide loader after toast
                setTimeout(() => {
                    $(".ep-event-loader").hide();
                }, 5000);
                
                return;
            }

            show_toast('success', res.data.message || 'Bookings data successfully deleted.');

            // Hide loader after toast, before reload
            setTimeout(() => {
                $(".ep-event-loader").hide();
                location.reload();
            }, 5000); // match toast duration
        });
    }
});

    
  $('#ep_request_data_export').on('click', function (e) {
    e.preventDefault();
    const nonce = ep_frontend._nonce;

    // Show loader
    $(".ep-event-loader").show();

    $.post(eventprime.ajaxurl, {
        action: 'ep_request_data_export',
        nonce: nonce
    }, function (res) {
        if (!res.success) {
            show_toast('error', res.data?.error || 'Something went wrong.');

            // Hide loader after toast duration (e.g., 3s)
            setTimeout(() => {
                $(".ep-event-loader").hide();
            }, 5000);

            return;
        }

        show_toast('success', res.data.message || 'Export request submitted. Check your email.');

        // Hide loader after toast
        setTimeout(() => {
            $(".ep-event-loader").hide();
        }, 5000);
    });
});


    

});

function ep_event_download_attendees( event_id ){
    if( event_id ){
        jQuery.ajax({
            type: "POST",
            url: ep_frontend.ajaxurl,
            data: {action: 'ep_export_submittion_attendees', security  : ep_frontend._nonce, event_id: event_id},
            success: function (response) {
                if( response.success == false ) {
                    show_toast( 'error', response.data.error );
                    return false;
                }
                else{
                    var link = document.createElement('a');
                    link.download = "attendees.csv";
                    link.href = 'data:application/csv;charset=utf-8,' + encodeURIComponent(response);
                    link.click();
                }
        }
        });
    }
}



function registrationPhoneCheck(str) {
  var isphone = /^(\+{0,})(\d{0,})([(]{1}\d{1,3}[)]{0,}){0,}(\s?\d+|\+\d{2,3}\s{1}\d+|\d+){1}[\s|-]?\d+([\s|-]?\d+){1,2}(\s){0,}$/gm
.test(str);
  return (isphone);
}

jQuery(document).on("input", "#ep_register_phone", function (e) {
  this.value = this.value.replace(/[^0-9+]/g, '').replace(/(\..*)\./g, '$1');
  jQuery(this).attr({"minlength": "10","maxlength": "15"}); 
});

jQuery('#ep_register_phone').on('keyup, keydown',function(){
  checkPhone(this);
});

function checkPhone(elem){
  if( jQuery('#ep_register_phone').val() != '' && jQuery(elem).val().match(/\+/g) != null){
    if( jQuery(elem).val().match(/\+/g).length > 1 ){
      var value = jQuery('#ep_register_phone').val();
      jQuery(elem).val(value.slice(0,-1));
    }
  }

}