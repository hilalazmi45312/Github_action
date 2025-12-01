jQuery(document).ready(function(e){

	$ = jQuery;
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

    let ep_font_size = eventprime.global_settings.ep_frontend_font_size;
    if( !ep_font_size ) {
        ep_font_size = 14;
    }
    $(':root').css('--themefontsize', ep_font_size + 'px' );

	// For Event calendar widget 
	if ( jQuery( "#ep_calendar_block" ).length > 0) {
		// Send ajax request to get all the event start dates
		jQuery.ajax({
			type: "POST",
			url: eventprime.ajaxurl,
			data: {action: 'ep_load_event_dates'},
			success: function (response) {
				let data = JSON.parse(response);
				let dates = data.start_dates;
				em_show_calendar(dates);
			}
		});
	} 
});


function em_show_calendar( dates ) {
	$ = jQuery;
	$( '#ep_calendar_block' ).datepicker({
		onChangeMonthYear: function () {
			setTimeout(em_change_dp_css, 40);
			return;
		},
		onHover: function () {},
		onSelect: function (dateText, inst) {
			let gotDate = $.inArray( dateText, dates );
			if(gotDate >=0){
                localStorage.setItem("ep_calendar_active", true);
                localStorage.setItem("ep_calendar_date", dateText);
                var page_url = blocks_obj.event_page_url;
				//console.log(page_url)
                window.location.href = page_url;	// ***** Redirect to date view for calendar 
            }
			/*
			if ( gotDate >= 0 ) {
				// Accessing only first element to avoid conflict if duplicate element exists on page
				$( '#em_start_date:first' ).val( dateText );
				let search_url = $("form[name='ep_calendar_event_form']:first").attr('action');
				search_url = em_add_param_to_url("ep-search=" + $("input[name='ep-search']:first").val(), search_url);
				search_url = em_add_param_to_url("date=" + dateText, search_url);
				location.href = search_url;
			}
			*/
		},
		beforeShowDay: function ( date ) {
			setTimeout(em_change_dp_css, 10);
			let year = date.getFullYear();
			// months and days are inserted into the array in the form, e.g "01/01/2009", but here the format is "1/1/2009"
			let month = em_padNumber(date.getMonth() + 1);
			let day = em_padNumber(date.getDate());
			// This depends on the datepicker's date format
			let dateString = year + "-" + month + "-" + day;
			let gotDate = $.inArray( dateString, dates );
			if ( gotDate >= 0 ) {
				// Enable date so it can be deselected. Set style to be highlighted
				return [true, "em-cal-state-highlight"];
			}
			// Dates not in the array are left enabled, but with no extra style
			return [true, ""];
		}, changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd"
	});
    em_generate_calendar_html();
	em_change_dp_css();
 }

function em_change_dp_css() {
	$ = jQuery;
	$(".ep_widget_container .ui-datepicker-header").removeClass("ui-widget-header");
	let emColor = $('.ep_widget_container').find('a').css('color');
	$(".em_color").css('color', emColor);
	$(".ep_widget_container .ui-datepicker-header").css('background-color', emColor);
	$(".ep_widget_container .ui-datepicker-current-day").css('background-color', emColor);
}

function em_padNumber( number ) {
	let ret = new String( number );
	if (ret.length == 1)
		ret = "0" + ret;
	return ret;
}
 
function em_add_param_to_url( param, url ) {
	let _url = url;
	_url += (_url.split('?')[1] ? '&' : '?') + param;
	return _url;
}

function em_generate_calendar_html(){
    jQuery('.em-cal-state-highlight').each(function(){
        if(jQuery(this).find('span').length){
            
        } else{
            jQuery(this).append('<span class="ep-calendar-block-dots"></span>');
        }
    });
}