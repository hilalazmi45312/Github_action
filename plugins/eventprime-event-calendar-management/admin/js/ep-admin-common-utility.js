jQuery(function ($) {

    $(document).ready(function () {
        $('.ep-help-tip').append("<span></span>");
        $('.ep-help-tip:not([tooltip-position])').attr('tooltip-position', 'top');

        $(".ep-help-tip").mouseenter(function () {
            $(this).find('span').empty().append($(this).attr('tooltip'));
        });
        
        //Ticket Model Scroll
        $(document).on('click', '#ep_event_open_ticket_modal, .ep-ticket-row-edit', function () {
            $('#ep_event_ticket_tier_modal').animate({ scrollTop: 0 }, 'slow');
        });
        
    });

    //General Modal Global
    $.fn.openPopup = function (settings, edit = '') {
        var elem = $(this);
        // Establish our default settings
        var settings = $.extend({
            anim: 'ep-modal-',
            overlayAnim:'ep-modal-overlay-fade-'
        }, settings);
        elem.show();
        elem.find('.popup-content').addClass(settings.anim + 'in').removeClass(settings.anim + 'out');
        elem.find('.ep-modal-overlay').addClass(settings.overlayAnim + 'in').removeClass(settings.overlayAnim + 'out');
        // check if edit popup is opend.
        if( edit !== '' ) {
            // change the modal title
            if( edit.title ) {
                elem.find( '.ep-modal-title' ).html( em_event_meta_box_object.edit_text + ' ' + edit.title );
            }
            // add the edit attribute on the buttons
            if( edit.row_id ) {
                elem.find( 'button' ).attr( 'data-edit_row_id' , edit.row_id );
            }
        }
    }

    $.fn.closePopup = function (settings) {
        var elem = $(this);
        // Establish our default settings
        var settings = $.extend({
            anim: 'ep-modal-',
            overlayAnim:'ep-modal-overlay-fade-'
        }, settings);
        elem.find('.popup-content').removeClass(settings.anim + 'in').addClass(settings.anim + 'out');
        elem.find('.ep-modal-overlay').removeClass(settings.overlayAnim + 'in').addClass(settings.overlayAnim + 'out');

        setTimeout(function () {
            elem.hide();
            elem.find('.popup-content').removeClass(settings.anim + 'out');
            // remove edit attribute if exists
            elem.find( 'button' ).removeAttr( 'data-edit_row_id' );
        }, 400);
    }

    // click event for open popup
    $( document ).on( 'click', '.ep-open-modal', function () {
        $('#' + $(this).data('id')).openPopup({
            anim: (!$(this).attr('data-animation') || $(this).data('animation') == null) ? 'ep-modal-' : $(this).data('animation')
        });
        
        $('body').addClass('ep-modal-open-body');
    });
    // click event for close popup
    $( document ).on( 'click', '.close-popup', function () {
        $('#' + $(this).data('id')).closePopup({
            anim: (!$(this).attr('data-animation') || $(this).data('animation') == null) ? 'ep-modal-' : $(this).data('animation')
        });
        
        $('body').removeClass('ep-modal-open-body');
    });

    // extension filter
    $( document ).on( 'click', '#ep-ext-controls li a', function(e) {
        e.preventDefault();
        $('#ep-ext-controls li a' ).removeClass('ep-extension-list-active');
        $(this).addClass( 'ep-extension-list-active' );
        var that = this,
            $that = $(that),
            id = that.id,
            ext_list = $('.ep-extensions-box-wrap');
        if (id == 'all-extensions') {
            ext_list.find('.ep-ext-card').fadeIn(500);
        }
        else {
            ext_list.find('.ep-ext-card.' + id + ':hidden').fadeIn(500);
            ext_list.find('.ep-ext-card').not('.' + id).fadeOut(500);
        }
    });

    // print attendee list
    $(document).on('click', '#ep_print_event_attendees_list', function() {
        $('#ep_print_attendee_list_loader').addClass('is-active');
        let event_id = $('#ep_event_id').val();
        let status_filter = $('#attendee_check_in_filter').val();
        let user_filter = $('#ep_attendee_page_user_filter').val();
    
        if (event_id) {
            let security = $('#ep_ep_print_event_attendees_nonce').val();
            let data = { 
                action: 'ep_event_print_all_attendees', 
                security: security,
                event_id: event_id,
                attendee_check_in_filter: status_filter,
                ep_attendee_page_user_filter: user_filter,
            };
    
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: data,
                success: function(response) {
                    let blob = new Blob([response]);
                    let link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    let file_name = 'attendees-' + event_id + '.csv';
                    link.download = file_name;
                    link.click();
                    $('#ep_print_attendee_list_loader').removeClass('is-active');
                }
            });
        }
    });
    
    $('.ep_field_list').on('change', function () {
        
        const value = $(this).val();
        //alert(value);
        if (!value) {
            return; // Do nothing if no value is selected
        }

        // Check if TinyMCE editor is active
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            tinyMCE.activeEditor.execCommand('mceInsertContent', false, value);
        } else {
            // Fallback for Text mode editor
            const textarea = document.getElementById('booking_confirmed_email');
            if (textarea) {
                const cursorPos = textarea.selectionStart;
                const textBefore = textarea.value.substring(0, cursorPos);
                const textAfter = textarea.value.substring(cursorPos, textarea.value.length);
                textarea.value = textBefore + value + textAfter;
            }
        }

        // Reset dropdown to default after insertion
        $(this).val('');
    });

    // Tabmenu 
// Tabmenu 


$(document).ready(function () {
    // Ensure the first main tab and its corresponding content are active by default
    if (!$('.ep-tab-item a.ep-tab-active').length) {
        $('.ep-tab-item a').first().addClass('ep-tab-active');
        var firstMainTabId = $('.ep-tab-item a').first().data('tag');
        $('#' + firstMainTabId).addClass('active').removeClass('ep-item-hide');

        // Ensure the first internal tab inside the active parent is also shown
        var firstInternalTab = $('#' + firstMainTabId).find('.ep-tab-item a').first();
        if (firstInternalTab.length) {
            firstInternalTab.addClass('ep-tab-active');
            var firstInternalTabId = firstInternalTab.data('tag');
            $('#' + firstInternalTabId).addClass('active').removeClass('ep-item-hide');
        }
    }
});

$(document).on('click', '.ep-tab-item a', function (e) {
    e.preventDefault(); // Prevent default anchor behavior

    var tagid = $(this).data('tag');
    var parentContainer = $(this).closest('.ep-tab-content'); // Find the closest parent tab content

    // If this is a main tab, target all main tab contents
    if (!parentContainer.length) {
        $('.ep-tab-item a').removeClass('ep-tab-active');
        $(this).addClass('ep-tab-active');
        $('.ep-tab-content').removeClass('active').addClass('ep-item-hide');
        $('#' + tagid).addClass('active').removeClass('ep-item-hide');

        // Ensure the first internal tab inside the newly activated parent remains visible
        var firstInternalTab = $('#' + tagid).find('.ep-tab-item a').first();
        if (firstInternalTab.length) {
            firstInternalTab.addClass('ep-tab-active');
            var firstInternalTabId = firstInternalTab.data('tag');
            $('#' + firstInternalTabId).addClass('active').removeClass('ep-item-hide');
        }
    } else {
        // If this is an internal tab, only target tabs inside the same section
        parentContainer.find('.ep-tab-content').removeClass('active').addClass('ep-item-hide');
        $('#' + tagid).addClass('active').removeClass('ep-item-hide');

        // Remove active class from other internal tabs and set clicked one as active
        parentContainer.find('.ep-tab-item a').removeClass('ep-tab-active');
        $(this).addClass('ep-tab-active');
    }
});








  
    // Tabmenu End
});

/**
 * return ajax url
 */
function get_ajax_url() {
    return ep_admin_utility_script.ajaxurl;
}

function hide_show_default_date_setting(element, childId) {
    jQuery('#' + childId).toggle();
    if (element.checked) {
        jQuery('#' + childId).find('input').attr('required', 'required');
    } else {
        jQuery('#' + childId).find('input').removeAttr('required');
    }
}
function hide_show_paypal_client_id(element, childId) {
    jQuery('#' + childId).toggle();
    if (element.checked) {
        jQuery('#' + childId).find('input').attr('required', 'required');
    } else {
        jQuery('#' + childId).find('input').removeAttr('required');
    }
}
function hide_show_google_share_setting(element, childId) {
    jQuery('.' + childId).toggle();
    if (element.checked) {
        jQuery('.' + childId).find('input').attr('required', 'required');
    } else {
        jQuery('.' + childId).find('input').removeAttr('required');
    }
}

function ep_hide_show_child_setting(element, childId) {
    
    if (element.checked) {
       jQuery('.' + childId).show(200);
    } else {
        jQuery('.' + childId).hide(200);
    }
}


/*
 * Hide Show
 */
function ep_frontend_view_child_hide_show(element, childId){
    if (element.checked) {
        jQuery('#' + childId).show(200);
    } else {
        jQuery('#' + childId).hide(200);
    }
}

function ep_email_attendies_hide_show(){
    jQuery('#ep-autopopulate').toggle(200);
}
