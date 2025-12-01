jQuery( function( $ ) {

    jQuery(document).ready(function () { 
        if (ep_frontend.single_organizer_event_column == 0){
            ep_organizer_Card_width_adjust( ".ep-event-card", "#ep-organizer-upcoming-events" );
        }
        else if(ep_frontend.organizer_no_of_columns == 0){
            ep_organizer_Card_width_adjust( ".ep-organizer-col-section", "#ep-event-organizers-loader-section" );
        }
        else{

            if (ep_frontend.single_organizer_event_column === 4){
                jQuery(".ep-event-card").addClass([ "ep-card-col-3"]).removeClass([ "ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-3"]).removeClass([ "ep-box-col-4", "ep-box-col-6", "ep-box-col-12", "ep-card-col-3"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 3){
                jQuery(".ep-event-card").addClass([ "ep-card-col-4"]).removeClass([ "ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-4"]).removeClass([ "ep-box-col-3", "ep-box-col-6", "ep-box-col-12", "ep-card-col-3"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 2){
                jQuery(".ep-event-card").addClass([ "ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-sm-6"]).removeClass(["ep-box-col-3", "ep-box-col-4", "ep-box-col-12", "ep-card-col-3", "ep-box-col-6"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 1){
                jQuery(".ep-event-card").addClass([ "ep-card-col-12"]).removeClass([ "ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-12"]).removeClass([ "ep-box-col-3", "ep-box-col-4", "ep-box-col-6", "ep-card-col-3"]);
            }
        }
    });

    $(document).on( 'click', '#ep_organizer_search_form input[type="submit"]', function(ev) {
        if ( $('#ep_keyword').val() == '' ) {
            ev.preventDefault();
            $('#ep_keyword').focus(); 
        }
    });
    

    $( document ).on( 'click', '#ep-loadmore-ep-organizers', function() {
        var max_page = $('#ep-loadmore-ep-organizers').data('max');
        var paged = $('#ep-organizers-paged').val();
        var display_style = $('#ep-organizers-style').val();
        var limit = $('#ep-organizers-limit').val();
        var cols = $('#ep-organizers-cols').val();
        var orderby = $('#ep-organizers-orderby').val();
        var order = $('#ep-organizers-order').val();
        var featured = $('#ep-organizers-featured').val();
        var popular = $('#ep-organizers-popular').val();
        var search = $('#ep-organizers-search').val();
        var box_color = $('#ep-organizers-box-color').val();
        var formData = new FormData();
        formData.append('action', 'ep_load_more_event_organizer');
        formData.append('paged', paged);
        formData.append('display_style', display_style);
        formData.append('limit', limit);
        formData.append('cols', cols);
        formData.append('orderby', orderby);
        formData.append('order', order);
        formData.append('featured',featured);
        formData.append('popular',popular);
        formData.append('search',search);
        formData.append('box_color',box_color);
        if($('#ep_keyword').length && $('#ep_keyword').val()!= ''){
            formData.append('keyword', $('#ep_keyword').val());
            formData.append('ep_search', true);
        }
        $('.ep-spinner').addClass('ep-is-active');
        $('#ep-loadmore-ep-organizers').prop('disabled', true);
        $("#ep-loadmore-event-organizers").attr("disabled", true);
        $.ajax({
            type : "POST",
            url : ep_frontend.ajaxurl,
            data: formData,
            contentType: false,
            processData: false,       
            success: function(response) {
                $('.ep-spinner').removeClass('ep-is-active');
                $('#ep-loadmore-ep-organizers').prop('disabled', false);
                $("#ep-loadmore-event-organizers").attr("disabled", false);
                $('#ep-organizers-paged').val(response.data.paged);
                if(response.data.paged >= max_page){
                    $('.ep-organizers-load-more').hide();
                }
                $('#ep-event-organizers-loader-section').append(response.data.html);
                
                if (ep_frontend.organizer_no_of_columns == 0){
                    ep_organizer_Card_width_adjust( ".ep-organizer-col-section", "#ep-event-organizers-loader-section" );
                }
                else{
                    if (ep_frontend.organizer_no_of_columns === 4){
                        jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-3"]).removeClass([ "ep-box-col-4", "ep-box-col-6", "ep-box-col-12"]);
                    }
                
                    if (ep_frontend.organizer_no_of_columns === 3){
                        jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-4"]).removeClass([ "ep-box-col-3", "ep-box-col-6", "ep-box-col-12"]);
                    }
                
                    if (ep_frontend.organizer_no_of_columns === 2){
                        jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-6"]).removeClass(["ep-box-col-3", "ep-box-col-4", "ep-box-col-12"]);
                    }
                
                    if (ep_frontend.organizer_no_of_columns === 1){
                        jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-12"]).removeClass([ "ep-box-col-3", "ep-box-col-4", "ep-box-col-6"]);
                    }
                }
            }
        }); 
    });

  

    function ep_organizer_Card_width_adjust(cardClass, containerId) {
        $ = jQuery;
        jQuery(cardClass).removeClass(["ep-card-col-", "ep-card-col-1", "ep-card-col-2", "ep-card-col-3", "ep-card-col-4", "ep-card-col-5", "ep-box-col-4"]);
        var container = $(containerId);
        //console.log(containerId);
        var kfWidth = container.innerWidth();
        //console.log(kfWidth);
    
        if (kfWidth < 720) {
            container.addClass("ep-narrow");
        }
        
        switch (true) {
            case kfWidth <= 500:
                $(cardClass).addClass("ep-card-col-12");
                break;
            case kfWidth <= 650:
                $(cardClass).addClass("ep-card-col-6");
                break;
            case kfWidth <= 850:
                $(cardClass).addClass("ep-card-col-4");
                break;
            case kfWidth <= 1150:
                $(cardClass).addClass("ep-card-col-3");
                break;
            case kfWidth <= 1280:
                $(cardClass).addClass("ep-card-col-3");
                break;
            case kfWidth > 1280:
                $(cardClass).addClass("ep-card-col-3 ep-default");
                break;
            default:
                $(cardClass).addClass("ep-card-col-3 ep-default");
                break;
        }
    }
    

    jQuery(window).resize(function(){
        
        if(jQuery("#ep-organizer-upcoming-events").length > 0 && ep_frontend.single_organizer_event_column == 0){
            jQuery(".ep-event-card").removeClass(["ep-card-col-","ep-card-col-12", "ep-card-col-6", "ep-card-col-4", "ep-card-col-3", "ep-card-col-2"]);
            ep_organizer_Card_width_adjust(".ep-event-card", "#ep-organizer-upcoming-events");
        }else if(jQuery("#ep-event-organizers-loader-section").length > 0 && ep_frontend.organizer_no_of_columns == 0){
            jQuery(".ep-organizer-col-section").removeClass(["ep-card-col-","ep-card-col-12", "ep-card-col-6", "ep-card-col-4", "ep-card-col-3", "ep-card-col-2"]);
            ep_organizer_Card_width_adjust( ".ep-organizer-col-section", "#ep-event-organizers-loader-section" );
        }
        else{

            if (ep_frontend.single_organizer_event_column === 4){
                jQuery(".ep-event-card").addClass([ "ep-card-col-3"]).removeClass([ "ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-3"]).removeClass([ "ep-box-col-4", "ep-box-col-6", "ep-box-col-12", "ep-card-col-3"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 3){
                jQuery(".ep-event-card").addClass([ "ep-card-col-4"]).removeClass([ "ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-4"]).removeClass([ "ep-box-col-3", "ep-box-col-6", "ep-box-col-12", "ep-card-col-3"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 2){
                jQuery(".ep-event-card").addClass([ "ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-sm-6"]).removeClass(["ep-box-col-3", "ep-box-col-4", "ep-box-col-12", "ep-card-col-3", "ep-box-col-6"]);
            }
        
            if (ep_frontend.single_organizer_event_column === 1){
                jQuery(".ep-event-card").addClass([ "ep-card-col-12"]).removeClass([ "ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);
                // jQuery(".ep-organizer-col-section").addClass([ "ep-box-col-12"]).removeClass([ "ep-box-col-3", "ep-box-col-4", "ep-box-col-6", "ep-card-col-3"]);
            }
        }

        
    
    }); 

    // Load More
    $( document ).on( 'click', '#ep-loadmore-upcoming-event-organizer', function(e) {
        var max_page      = $( this ).attr('data-max');
        var paged         = $( this ).attr('data-paged');
        var display_style = $( this ).attr('data-style');
        var limit         = $( this ).attr('data-limit');
        var cols          = $( this ).attr('data-cols');
        var pastevent     = $( this ).attr('data-pastevent');
        var post_id       = $( this ).attr('data-id');
        var formData      = new FormData();
        formData.append( 'action', 'ep_load_more_upcomingevent_organizer' );
        formData.append( 'paged', paged );
        formData.append( 'event_style', display_style );
        formData.append( 'event_limit', limit );
        formData.append( 'event_cols', cols );
        formData.append( 'hide_past_events',pastevent );
        formData.append( 'post_id',post_id );
        $( '.ep-spinner' ).addClass( 'ep-is-active' );
        $('#ep-loadmore-upcoming-event-organizer').prop('disabled', true);
        $( '.ep-register-response' ).html();
        $.ajax({
            type : "POST",
            url : ep_frontend.ajaxurl,
            data: formData,
            contentType: false,
            processData: false,       
            success: function(response) {
                $( '.ep-spinner' ).removeClass( 'ep-is-active' );
                $('#ep-loadmore-upcoming-event-organizer').prop('disabled', false);
                $( '#ep-loadmore-upcoming-event-organizer' ).attr( 'data-paged', response.data.paged );
                if( response.data.paged >= max_page ) {
                    $( '#ep-loadmore-upcoming-event-organizer' ).hide();
                }
                $( '#ep-organizer-upcoming-events' ).append( response.data.html );

                if (ep_frontend.single_organizer_event_column == 0){
                    ep_organizer_Card_width_adjust( ".ep-event-card", "#ep-organizer-upcoming-events" );
                }
                else{
                    if (ep_frontend.single_organizer_event_column === 4){
                        jQuery(".ep-event-card").addClass([ "ep-card-col-3"]).removeClass([ "ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
                    }
                
                    if (ep_frontend.single_organizer_event_column === 3){
                        jQuery(".ep-event-card").addClass([ "ep-card-col-4"]).removeClass([ "ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);;
                    }
                
                    if (ep_frontend.single_organizer_event_column === 2){
                        jQuery(".ep-event-card").addClass([ "ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
                    }
                
                    if (ep_frontend.single_organizer_event_column === 1){
                        jQuery(".ep-event-card").addClass([ "ep-card-col-12"]).removeClass([ "ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);;
                    }
                }
            }
        }); 
    });
});
