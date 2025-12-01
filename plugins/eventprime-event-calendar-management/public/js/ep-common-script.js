jQuery( function( $ ) {
    $( document ).ready( function() {
        $('.thumbnail').on('click', function () {
            var clicked = $(this);
            var newSelection = clicked.data('image_url');
            //var $img = $('.primary').css("background-image", "url(" + newSelection + ")");
            var $img = $('.primary').attr('src', "" + newSelection + "");
            clicked.parent().find('.thumbnail').removeClass('selected');
            clicked.addClass('selected');
            $('.primary').empty().append($img.hide().fadeIn('slow'));
        });
        
        /*-- Theme Color Global--*/  
        // set dominent color for nice theme
        
        $(".emagic").prepend("<a>");
        var epColorRgbValue = $('.emagic, #primary.content-area .entry-content, .entry-content .emagic').find('a').css('color');
    
        /*-- Theme Color Global--*/ 
        var epColorRgb = epColorRgbValue;
        var avoid = "rgb";
        if( epColorRgb ) {
            var eprgbRemover = epColorRgb.replace(avoid, '');
            var emColor = eprgbRemover.substring(eprgbRemover.indexOf('(') + 1, eprgbRemover.indexOf(')'));
            $(':root').css('--themeColor', emColor );
        }

        let ep_font_size = eventprime_obj.global_settings.ep_frontend_font_size;
        if( !ep_font_size ) {
            ep_font_size = 14;
        }
        $(':root').css('--themefontsize', ep_font_size + 'px' );
        
        //Adding class on body in case EP content
        if ($('.emagic').length) {
            $('html').addClass('ep-embed-responsive');
        }
        
        //Adding class incase event list right col is small
        var epEventListwidth = $(".ep-box-list-right-col").width();
        
        if(epEventListwidth < 210){
            $(".ep-box-list-right-col .ep-event-list-view-action").addClass("ep-column-small");
        }

        // set dark mode
        if( eventprime_obj.global_settings.enable_dark_mode == 1 ) {
            $( 'body' ).addClass( 'ep-dark-mode-enabled' );
        }
        
    });
    
    $(function() {
        //----- OPEN
        $('[ep-modal-open]').on('click', function(e)  {
            var targeted_popup_class = jQuery(this).attr('ep-modal-open');
            $('[ep-modal="' + targeted_popup_class + '"]').fadeIn(100);
            $('body').addClass('ep-modal-open-body');
            e.preventDefault();
        });
    
        //----- CLOSE
        $('[ep-modal-close]').on('click', function(e)  {
            var targeted_popup_class = jQuery(this).attr('ep-modal-close');
            $('[ep-modal="' + targeted_popup_class + '"]').fadeOut(200);
            $('body').removeClass('ep-modal-open-body');
            e.preventDefault();
        });
    });

    // add event to wishlist
    $( document ).on( 'click', '.ep_event_wishlist_action', function() {
        if( $( '.ep-event-loader' ).length > 0 ) {
            $( '.ep-event-loader' ).show();
        }
        let event_id = $( this ).data( 'event_id' );
        var remove_row_id = $( this ).attr( 'data-remove_row' );
        if( event_id ) {
            let data = { 
                action   : 'ep_event_wishlist_action', 
                security : eventprime_obj.event_wishlist_nonce,
                event_id : event_id
            };
            $.ajax({
                type        : "POST",
                url         : eventprime_obj.ajaxurl,
                data        : data,
                success     : function( response ) {
                    if( $( '.ep-event-loader' ).length > 0 ) {
                        $( '.ep-event-loader' ).hide();
                    }
                    if( response.success == true ) {
                        show_toast( 'success', response.data.message );
                        // add, remove color
                        if( response.data.action == 'add' ) {
                            $( '#ep_event_wishlist_action_' + event_id + ' .ep-handle-fav' ).text( 'favorite' );
                            $( '#ep_event_wishlist_action_' + event_id + ' .ep-handle-fav' ).addClass( 'ep-text-danger' );
                        } else{
                            $( '#ep_event_wishlist_action_' + event_id + ' .ep-handle-fav' ).text( 'favorite_border' );
                            $( '#ep_event_wishlist_action_' + event_id + ' .ep-handle-fav' ).removeClass( 'ep-text-danger' );
                        }
                        $( '#ep_event_wishlist_action_' + event_id ).attr( 'title', response.data.title );
                        
                        // remove block of user profile
                        if( remove_row_id ) {
                            $( '#' + remove_row_id ).remove();
                        }
                        // update count
                        if( $( '#ep_wishlist_event_count' ).length > 0 ) {
                            let eve_count = $( '#ep_wishlist_event_count' ).text();
                            --eve_count;
                            $( '#ep_wishlist_event_count' ).text( eve_count );
                        }
                    } else{
                        show_toast( 'error', response.data.error );
                    }
                }
            });
        }
    });

    // ical export
    $( document ).on( 'click', '#ep_event_ical_export', function(event) {
         event.preventDefault(); // Prevents the link from navigating
        let event_id = $( this ).attr( 'data-event_id' );
        //console.log(event_id);
        if( event_id ) {
            if( window.location.search ) {
                window.location = window.location.href + '&event='+event_id+'&download=ical';
            } else{
                window.location = window.location.href + '?event='+event_id+'&download=ical';
            }
        }
    });

    // image scaling
    $( document ).on( 'mouseover', '.ep-upcoming-box-card-item', function() {
        $( this ).addClass( 'ep-shadow' );
        $( this ).find( '.ep-upcoming-box-card-thumb img' ).css( 'transform', 'scale(1.1,1.1)' );
    });

    $( document ).on( 'mouseout', '.ep-upcoming-box-card-item', function() {
        $( this ).removeClass( 'ep-shadow' );
        $( this ).find( '.ep-upcoming-box-card-thumb img' ).css("transform", "scale(1,1)");
    });

    // Tabmenu 
    $( document ).on( 'click', '.ep-tab-item a', function(){
        $( '.ep-tab-item a' ).removeClass( 'ep-tab-active' );
        $(this).addClass('ep-tab-active');
        var tagid = $(this).data('tag');
        $( '.ep-tab-content' ).removeClass( 'active' ).addClass( 'ep-item-hide' );
        $( '#'+tagid ).addClass( 'active' ).removeClass( 'ep-item-hide' );
    });  
    // Tabmenu End
    
    //Sub Tab Menu
    // Tabmenu 
    $( document ).on( 'click', '.ep-profile-events-tabs a', function(){
        $( '.ep-profile-events-tabs a' ).removeClass( 'ep-tab-active' );
        $(this).addClass('ep-tab-active');
        var tagid = $(this).data('tag');
        $( '.ep-profile-event-tabs-content' ).addClass( 'ep-item-hide' );
        $( '#'+tagid).removeClass( 'ep-item-hide' );
    });  
    // Tabmenu End
    
});
    
/**
 * Format price with currency position.
 * @param {int|float} price 
 * @param {string} currency 
 * @returns Formatted Price.
 */
function ep_format_price_with_position( price, currency = null ) {
    price = parseFloat( price ).toFixed( 2 );
    if( !currency ) {
        currency = eventprime_obj.currency_symbol;
    }
    position = eventprime_obj.global_settings.currency_position;
    if( position == 'before' ) {
        price = currency + price;
    } else if( position == 'before_space' ) {
        price = currency + ' ' + price;
    } else if( position == 'after' ) {
        price = price + currency;
    } else if( position == 'after_space' ) {
        price = price + ' ' + currency;
    }
    return price;
}

/**
 * Return the translation of errors
 * 
 * @param {string} key
 */
function get_translation_string( key ) {
    let transObj = eventprime_obj.trans_obj;
    if ( transObj.hasOwnProperty( key ) ) {
        return eventprime_obj.trans_obj[key];
    }
}

/**
 * Validate the website url
 * 
 * @param {string} url Website URL
 * 
 * @return {bool} URL is valid or invalid
 */
function is_valid_url_old( url ) {
    var urlPattern = new RegExp('^(https?:\\/\\/)?' + // validate protocol
            '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // validate domain name
            '((\\d{1,3}\\.){3}\\d{1,3}))' + // validate OR ip (v4) address
            '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // validate port and path
            '(\\?[;&a-z\\d%_.~+=-]*)?' + // validate query string
            '(\\#[-a-z\\d_]*)?$', 'i'); // validate fragment locator
    return !!urlPattern.test( url );
}

function is_valid_url( url ) {
    if (typeof url !== 'string') return false;
    var v = url.trim();
    if (!v) return false;

    // Accept URLs without protocol (like your old regex): add http:// to parse
    var hasProtocol = /^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//.test(v);
    var candidate = hasProtocol ? v : 'http://' + v;

    try {
        var u = new URL(candidate);

        // If protocol was provided, only accept http/https (old regex did http/https only)
        if (hasProtocol && u.protocol !== 'http:' && u.protocol !== 'https:') return false;

        // Host must be either IPv4 or domain with a dot + TLD >= 2 chars (like old regex)
        var host = u.hostname;
        var isIPv4 = /^(25[0-5]|2[0-4]\d|[01]?\d?\d)(\.(25[0-5]|2[0-4]\d|[01]?\d?\d)){3}$/.test(host);
        var isDomain = /^[a-z0-9-]+(\.[a-z0-9-]+)+$/i.test(host) && /\.[a-z]{2,}$/i.test(host);

        if (!isIPv4 && !isDomain) return false;

        // Optional port/path/query/hash are already handled by URL parsing
        // If we reach here, treat as valid (same boolean return style as before)
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Validate the phone number
 * 
 * @param {string} phone_number Phone Number
 * 
 * @return {bool} Phone Number is valid or invalid
 */
function is_valid_phone( phone_number ) {
    var phonePattern = new RegExp('^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$');
    return !!phonePattern.test( phone_number );
}
/**
 * Validate the email
 * 
 * @param {string} email Email
 * 
 * @return {bool} Email is valid or invalid
 */
function is_valid_email( email ) {
    //var emailPattern = new RegExp('[^@ \t\r\n]+@[^@ \t\r\n]+\.[^@ \t\r\n]+');
    var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    return emailPattern.test(email);
}

jQuery(document).ready(function($) {
    $('#ep-gdpr-badge, #ep_gdpr_modal .ep-modal-overlay, #ep_gdpr_modal .ep-modal-close').on('click', function() {
        //$('#ep-gdpr-modal').show(100);
        $('#ep_gdpr_modal').toggleClass("ep_gdpr_modal_active").fadeToggle(100);
    });

    $('.ep-gdpr-modal-close, #ep-gdpr-modal').on('click', function(e) {
        // Close modal only if clicking background or close button
        if ($(e.target).is('#ep-gdpr-modal') || $(e.target).hasClass('ep-gdpr-modal-close')) {
            $('#ep-gdpr-modal').fadeOut();
        }
    });
});

/*---

   $(function() {
        //----- OPEN
        $('[ep-modal-open]').on('click', function(e)  {
            var targeted_popup_class = jQuery(this).attr('ep-modal-open');
            $('[ep-modal="' + targeted_popup_class + '"]').fadeIn(100);
            $('body').addClass('ep-modal-open-body');
            e.preventDefault();
        });
    
        //----- CLOSE
        $('[ep-modal-close]').on('click', function(e)  {
            var targeted_popup_class = jQuery(this).attr('ep-modal-close');
            $('[ep-modal="' + targeted_popup_class + '"]').fadeOut(200);
            $('body').removeClass('ep-modal-open-body');
            e.preventDefault();
        });
    });
 * 
 * 
 */