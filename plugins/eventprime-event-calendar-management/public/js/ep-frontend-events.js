jQuery(function ($) {
    // initialize the select2 on click on the filter
    $(document).on('click', '.ep-filter-bar-toggle-filter', function () {
        // close other filters first
        $('.ep-filter-bar-filter-container').each(function () {
            let select2_id = $(this).find('.ep-filter-dropdown-created').attr('data-select2-id');
            if (select2_id) {
                $(this).find('.ep-filter-bar-filter-close').trigger('click');
            }
        });
        // hide if cost slide visible
        if ($('#ep-cost-filter-bar-container').css('display') == 'block') {
            $('#ep-cost-filter-bar-container').hide();
        }

        let $id = this.id;
        $('#' + $id + '-container').show();
        if ($id != 'ep-cost-filter-bar-container') {
            $('#' + $id + '-container .ep-filter-dropdown-created').select2();
            $('#' + $id + '-container .ep-filter-dropdown-created').select2('open');
            if ($('.select2-container').length > 0) {
                $('.select2-container').addClass('ep-event-bottom-filter-list');
            }
        }
    });

    // close and destroy select2 
    $(document).on('click', '.ep-filter-bar-filter-close', function () {
        let $id = $(this).closest('.ep-filter-bar-filter-container').id;
        $(this).closest('.ep-filter-bar-filter-container').find('.ep-filter-dropdown-created').select2('destroy');
        $(this).closest('.ep-filter-bar-filter-container').hide();
    });

    // show event calendar

    $(document).ready(function () {
        ep_load_calendar_view();
    });

    // Load More
    // $(document).on('click','#ep-loadmore-events',function(e){
    $(document).on('click', '.ep-loadmore-events', function (e) {
        var max_page = $(this).data('max');
        var section_id = $(this).data('section_id');
        $('.ep-spinner-' + section_id).addClass('ep-is-active');
        $('#ep-loadmore-events').prop("disabled", true);
        var paged = $('#ep-events-paged-' + section_id).val();
        var display_style = $('#ep-events-style-' + section_id).val();
        var limit = $('#ep-events-limit-' + section_id).val();
        var order = $('#ep-events-order-' + section_id).val();
        var event_types_ids = $('#ep-events-types-ids-' + section_id).val();
        var event_venues_ids = $('#ep-events-venues-ids-' + section_id).val();
        var event_cols = $('#ep-events-cols-' + section_id).val();
        var i_events = $('#ep-events-i-events-' + section_id).val();
        // var search = $('#ep-events-search').val();
        var formData = new FormData();
        formData.append('action', 'ep_load_more_events');
        formData.append('paged', paged);
        formData.append('display_style', display_style);
        formData.append('limit', limit);
        formData.append('order', order);
        formData.append('event_types_ids', event_types_ids);
        formData.append('event_venues_ids', event_venues_ids);
        formData.append('event_cols', event_cols);
        formData.append('i_events', i_events);
        formData.append('event_search_params', JSON.stringify(event_search_params));
        // formData.append('search',search);
        if ($('#ep_keyword').length && $('#ep_keyword').val() != '') {
            formData.append('keyword', $('#ep_keyword').val());
            formData.append('ep_search', true);
        }
        formData.append('event_atts', JSON.stringify(em_front_event_object.event_attributes));

        $('.ep-register-response').html();
        $.ajax({
            type: "POST",
            url: eventprime.ajaxurl,
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                $('.ep-spinner-' + section_id).removeClass('ep-is-active');
                $('#ep-loadmore-events').prop("disabled", false);
                $('#ep-events-paged-' + section_id).val(response.data.paged);
                if (response.data.paged >= max_page) {
                    $('.ep-events-load-more-' + section_id).hide();
                }
                // $( '#ep_events_front_views_' + display_style ).append(response.data.html);
                $('.ep_events_front_views_' + display_style + '_' + section_id).append(response.data.html);
                if (display_style === 'staggered_grid') {
                    var container = document.querySelector('#ep_events_front_views_staggered_grid');
                    if (container) {
                        var msnry;
                        imagesLoaded(container, function () {
                            msnry = new Masonry(container, {
                                itemSelector: '#ep_events_front_views_staggered_grid .ep-event-card'
                            });
                        });
                    }
                }
                //Removed in 3.3.5
                epCard_width_adjust(".ep-event-card");

                if (eventprime.global_settings.events_no_of_columns === 4) {
                    jQuery(".ep-event-card").addClass(["ep-card-col-3"]).removeClass(["ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
                }
                if (eventprime.global_settings.events_no_of_columns === 3) {
                    jQuery(".ep-event-card").addClass(["ep-card-col-4"]).removeClass(["ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);
                }

                if (eventprime.global_settings.events_no_of_columns === 2) {
                    jQuery(".ep-event-card").addClass(["ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
                }

                if (eventprime.global_settings.events_no_of_columns === 1) {
                    jQuery(".ep-event-card").addClass(["ep-card-col-12"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);
                }

            }
        });
    });
    /**
     * Event Filters start
     */
    var event_search_params = [];
    // event view filter
    $(document).ready(function (e) {
        var filter_value = localStorage.getItem("ep_calendar_date");
        if (filter_value !== null && filter_value !== '') {
            jQuery('#filter-date-from').val(filter_value);
            let param = {label: 'From', key: 'date_from', value: filter_value, text: filter_value};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);

            let param2 = {label: 'To', key: 'date_to', value: filter_value, text: filter_value};
            event_applied_filters_render_content(param2);
            event_filters_selection_update(event_search_params, param2);

            let param3 = {label: 'Days', key: 'days', value: 'all', text: 'All Days'};
            event_applied_filters_render_content(param3);
            event_filters_selection_update(event_search_params, param3);

            let display_style = $('#ep-events-style').val();
            event_applied_filters(display_style, event_search_params);

            localStorage.removeItem('ep_calendar_date');
        }
    });
    $(document).on('click', '.ep_event_view_filter', function () {
        let view_name = $(this).data('event_view');
        $('.ep_event_view_filter').removeClass('ep-active-view');
        $(this).addClass('ep-active-view');
        event_applied_filters(view_name, event_search_params);
    });

    $(document).on('click', '.ep-clear-filter', function () {
        var parentEle = $(this).parent().attr('id');
        var filter_key = $(this).parent().data('key');
        event_filters_selection_remove(event_search_params, filter_key);
        $('#' + parentEle).remove();
        var view_name = $('#ep-events-style').val();
        event_applied_filters(view_name, event_search_params);
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
    });
    // keyword search
    /*$( document ).on( 'click', '#ep_event_find_events_btn', function() {
     $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
     $(".ep-search-filter-overlay").hide();
     let search_keyword = $( '#ep_event_keyword_search' ).val();
     //if(search_keyword) {
     // sanitize the input first
     /*
     let post_data = {
     action   : 'ep_sanitize_input_field_data',
     security : em_front_event_object._nonce,
     input_val: search_keyword,
     };
     $.ajax({
     type    : "POST",
     url     : eventprime.ajaxurl,
     data    : post_data,
     success : function( response ) {
     if( response.success ) {
     //search_keyword = response.data;
     // render html
     let param = { label: 'Keyword', key: 'keyword', value: search_keyword };
     if( search_keyword ) {
     // update html
     event_applied_filters_render_content( param );
     event_filters_selection_update(event_search_params, param);    
     }
     //event_search_params.push( param );
     let display_style = $('#ep-events-style').val();
     event_applied_filters( display_style, event_search_params );
     /* } else{
     show_toast( 'error', response.data.message );
     return false;
     }
     }
     });
     // }
     
     });
     */

    function runEventSearch() {
        $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        $(".ep-search-filter-overlay").hide();

        const search_keyword = $('#ep_event_keyword_search').val();

        const param = {label: 'Keyword', key: 'keyword', value: search_keyword};
        if (search_keyword) {
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        }

        const display_style = $('#ep-events-style').val();
        event_applied_filters(display_style, event_search_params);
    }

    // Click on "Find Events"
    $(document).on('click', '#ep_event_find_events_btn', function (e) {
        e.preventDefault();
        runEventSearch();
    });

    // Press Enter inside the keyword input
    $(document).on('keydown', '#ep_event_keyword_search', function (e) {
        if (e.key === 'Enter' || e.keyCode === 13 || e.which === 13) {
            e.preventDefault(); // prevent form/page submit
            runEventSearch();
        }
    });

    // Optional: if the input is inside a form
    // (change #ep_event_search_form to your actual form id if different)
    $(document).on('submit', '#ep_event_search_form', function (e) {
        e.preventDefault();
        runEventSearch();
    });

    $(document).on('click', '.ep-filters-days', function () {
        let filter_value = $(this).data('key');
        let filter_text = $(this).text();
        if (filter_value) {
            // render html
            let param = {label: 'Days', key: 'days', value: filter_value, text: filter_text};
            // update html
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
            let display_style = $('#ep-events-style').val();
            event_applied_filters(display_style, event_search_params);
        }
    });

    $(document).on('change', '#filter-date-from', function () {
        let filter_value = $('#filter-date-from').val();
        if (filter_value !== '') {
            let param = {label: 'From', key: 'date_from', value: filter_value, text: filter_value};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        }
        let date_filter_value = $('#filter-date-days').val();
        let filter_end_date_value = $('#filter-date-to').val();
        let filter_text = $("#filter-date-days").find("option[value='" + $("#filter-date-days").val() + "']").text();
        if (date_filter_value !== '' && filter_end_date_value !== '') {
            $('#filter-date-days-section').show(500);
            let param = {label: 'Days', key: 'days', value: date_filter_value, text: filter_text};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        } else if (date_filter_value !== '' && filter_end_date_value === '') {
            $('#filter-date-days-section').hide(500);
            event_filters_selection_remove(event_search_params, 'days');
        }
    });

    $(document).on('change', '#filter-date-to', function () {
        let filter_value = $('#filter-date-to').val();
        if (filter_value) {
            let param = {label: 'To', key: 'date_to', value: filter_value, text: filter_value};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        }

        let date_filter_value = $('#filter-date-days').val();
        let filter_start_date_value = $('#filter-date-from').val();
        let filter_text = $("#filter-date-days").find("option[value='" + $("#filter-date-days").val() + "']").text();
        if (date_filter_value !== '' && filter_start_date_value !== '') {
            $('#filter-date-days-section').show(500);
            let param = {label: 'Days', key: 'days', value: date_filter_value, text: filter_text};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        } else if (date_filter_value !== '' && filter_start_date_value === '') {
            $('#filter-date-days-section').hide(500);
            event_filters_selection_remove(event_search_params, 'days');
        }
    });

    $(document).on('change', '#filter-date-days', function () {
        let filter_value = $('#filter-date-days').val();
        if (filter_value) {
            let param = {label: 'Days', key: 'days', value: filter_value, text: filter_value};
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        }
    });

    $(document).on('change', '#ep-filter-types', function () {
        let filter_text = $("#ep-filter-types").find("option[value='" + $("#ep-filter-types").val() + "']").text();
        let filter_value = $('#ep-filter-types').val();
        if (filter_value !== '') {
            // render html
            let param = {label: em_front_event_object.event_types, key: 'event_types', value: filter_value, text: filter_text};
            // update html
            event_applied_filters_render_content(param);
            //event_search_params.push( param );
            event_filters_selection_update(event_search_params, param);
            //let display_style = $('#ep-events-style').val();
            //event_applied_filters( display_style, event_search_params );
        }
    });

    $(document).on('change', '#ep-filter-venues', function () {
        let filter_text = $("#ep-filter-venues").find("option[value='" + $("#ep-filter-venues").val() + "']").text();
        let filter_value = $('#ep-filter-venues').val();
        if (filter_value !== '') {
            // render html
            let param = {label: em_front_event_object.venues, key: 'event_venues', value: filter_value, text: filter_text};
            // update html
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
            //let display_style = $('#ep-events-style').val();
            //event_applied_filters( display_style, event_search_params );

        }
    });

    $(document).on('change', '#ep-filter-performer', function () {
        //let filter_text = $("#ep-filter-performer").find("option[value='" + $("#ep-filter-performer").val() + "']").text();
        let filter_value = $('#ep-filter-performer').val();
        if (filter_value.length == 0) {
            event_filters_selection_remove(event_search_params, 'event_performers');
            $('#ep_event_performers').remove();
        } else if (filter_value.length > 0) {
            // render html
            let param = {label: em_front_event_object.performers, key: 'event_performers', value: filter_value, text: filter_value.length + ' Selected'};
            // update html
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
            //let display_style = $('#ep-events-style').val();
            //event_applied_filters( display_style, event_search_params );

        }
    });

    $(document).on('change', '#ep-filter-org', function () {
        //let filter_text = $("#ep-filter-performer").find("option[value='" + $("#ep-filter-performer").val() + "']").text();
        let filter_value = $('#ep-filter-org').val();
        if (filter_value.length == 0) {
            event_filters_selection_remove(event_search_params, 'event_performers');
            $('#ep_event_organizers').remove();
        } else if (filter_value.length > 0) {
            // render html
            let param = {label: em_front_event_object.organizers, key: 'event_organizers', value: filter_value, text: filter_value.length + ' Selected'};
            // update html
            event_applied_filters_render_content(param);
            event_filters_selection_update(event_search_params, param);
        }
    });

    if ($('#ep-filter-event-tags').length) {
        $(document).on('change', '#ep-filter-event-tags', function () {
            let filter_value = $('#ep-filter-event-tags').val();
            //console.log(filter_value)
            if (filter_value.length == 0) {
                event_filters_selection_remove(event_search_params, 'event_tags');
                $('#ep_event_tags').remove();
            } else if (filter_value.length > 0) {
                // render html
                // let param = { label: ep_custom_dash_pub_obj.event_tags_events_lising_fliter_placeholder, key:'event_tags', value: filter_value, text: filter_value.length+' Selected' };
                let selectedTexts = [];
                filter_value.forEach(function (val) {
                    let text = $('#ep-filter-event-tags option[value="' + val + '"]').text();
                    if (text) {
                        selectedTexts.push(text.trim());
                    }
                });
                let selectedTextsString = selectedTexts.join(', ');
                let param = {label: ep_custom_dash_pub_obj.event_tags_events_lising_fliter_placeholder, key: 'event_tags', value: filter_value, text: selectedTextsString};
                // update html
                event_applied_filters_render_content(param);
                event_filters_selection_update(event_search_params, param);
            }
        });
    }

    // edit timezone
    $(document).on('click', '#ep-user-profile-timezone-edit', function () {
        $(this).hide();
        $('.ep-user-profile-timezone-list').show();
    });

    // save the user timezone
    $(document).on('click', '#ep_user_profile_timezone_save', function () {
        let time_zone = $('#ep_user_profile_timezone_list').val();
        if (time_zone) {
            $('.ep-event-loader').show();
            let data = {
                action: 'ep_update_user_timezone',
                security: em_front_event_object._nonce,
                time_zone: time_zone,
                reload: 1
            };
            $.ajax({
                type: "POST",
                url: eventprime.ajaxurl,
                data: data,
                success: function (response) {
                    $('.ep-event-loader').hide();
                    if (response == -1) {
                        show_toast('error', em_front_event_object.nonce_error);
                        return false;
                    }
                    if (response.success == false) {
                        show_toast('error', response.data.error);
                        return false;
                    } else {
                        show_toast('success', response.data.message);
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    }
                }
            });
        }
    });

});

document.addEventListener("DOMContentLoaded", function (event) {
    jQuery(document).ready(function () {
        var container = document.querySelector('#ep_events_front_views_staggered_grid');
        if (container) {
            var msnry;
            imagesLoaded(container, function () {
                msnry = new Masonry(container, {
                    itemSelector: '#ep_events_front_views_staggered_grid .ep-event-card'
                });
            });
        }

        if (!eventprime.global_settings.events_no_of_columns) {
            epCard_width_adjust(".ep-event-card");
        }

        //Event FilterBar for Smaller Screen

        epSearch_width_adjust(".ep-event-views-col");
        epSearch_bar_width_adjust(".ep-search-filter-bar");

        jQuery('.ep-event-loader').hide();
    });
});

function event_applied_filters(event_view_name, event_search_params) {
    //if( view || ( event_search_params && event_search_params.length > 0 ) ) {
    jQuery('.ep-event-loader').show();
    //console.log(event_view_name);
    //console.log(event_search_params);
    let formData = new FormData();
    formData.append('action', 'ep_filter_event_data');
    formData.append('event_search_params', JSON.stringify(event_search_params));
    formData.append('display_style', event_view_name);
    formData.append('event_atts', JSON.stringify(em_front_event_object.event_attributes));
    jQuery.ajax({
        type: "POST",
        url: eventprime.ajaxurl,
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            jQuery('.ep-event-loader').hide();
            if (response.success) {
                if (response.data.html) {
                    // check for view
                    let existing_display_style = jQuery('#ep-events-style').val();
                    let event_container_id = 'ep_events_front_views_' + existing_display_style;
                    if (existing_display_style != event_view_name) { //change the view
                        jQuery('#ep-events-style').val(event_view_name);
                        let old_event_class = 'ep-event-list-' + existing_display_style + '-container';
                        let new_event_class = 'ep-event-list-' + event_view_name + '-container';
                        jQuery('.ep-events').removeClass(old_event_class).addClass(new_event_class);
                        event_container_id = 'ep_events_front_views_' + event_view_name;
                        jQuery('.' + new_event_class).attr('id', event_container_id);
                        jQuery('#ep-events-content-container').html(response.data.html);
                        if (event_view_name == 'month') {
                            ep_render_calendar_view(response.data.cal_events, JSON.stringify(event_search_params));
                        }
                        if (event_view_name === 'masonry' || event_view_name === 'staggered_grid') {
                            var container = document.querySelector('#ep_events_front_views_masonry');
                            if (container) {
                                var msnry;
                                imagesLoaded(container, function () {
                                    msnry = new Masonry(container, {
                                        itemSelector: '#ep_events_front_views_masonry .ep-event-card',
                                    });
                                });
                            } else {
                                var container = document.querySelector('#ep_events_front_views_staggered_grid');
                                if (container) {
                                    var msnry;
                                    imagesLoaded(container, function () {
                                        msnry = new Masonry(container, {
                                            itemSelector: '#ep_events_front_views_staggered_grid .ep-event-card',
                                        });
                                    });
                                }
                            }
                        }
                    } else {
                        jQuery('#ep-events-content-container').html(response.data.html);
                        if (event_view_name == 'month') {
                            ep_render_calendar_view(response.data.cal_events, JSON.stringify(event_search_params));
                        }
                    }

                    if (event_search_params && event_search_params.length > 0) {
                        jQuery('#ep_event_various_filters_section').fadeIn(500);
                        if (event_search_params.length == 1) {
                            jQuery('#ep_total_filters_applied').html(event_search_params.length + ' ' + em_front_event_object.filter_applied_text);
                        } else {
                            jQuery('#ep_total_filters_applied').html(event_search_params.length + ' ' + em_front_event_object.filters_applied_text);
                        }
                    } else {
                        jQuery('#ep_event_various_filters_section').hide();
                    }
                    epCard_width_adjust(".ep-event-card");
                    if (eventprime.global_settings.events_no_of_columns === 4) {
                        jQuery(".ep-event-card").addClass(["ep-card-col-3"]).removeClass(["ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
                    }
                    if (eventprime.global_settings.events_no_of_columns === 3) {
                        jQuery(".ep-event-card").addClass(["ep-card-col-4"]).removeClass(["ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);
                    }

                    if (eventprime.global_settings.events_no_of_columns === 2) {
                        jQuery(".ep-event-card").addClass(["ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
                    }

                    if (eventprime.global_settings.events_no_of_columns === 1) {
                        jQuery(".ep-event-card").addClass(["ep-card-col-12"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);
                    }
                }
            }
        }
    });

    //}


}

function event_applied_filters_render_content(param) {
    let filter_html = '';
    var value = param.text ? param.text : param.value;
    if (jQuery('#ep_' + param.key).length > 0) {
        filter_html += '<button type="button" class="ep-btn ep-text-dark">';
        filter_html += '<strong class="ep-text-small">' + param.label + ': </strong>';
        filter_html += '<span class="ep-text-small">' + value + '</span>';
        filter_html += '</button>';
        filter_html += '<button type="button" class="ep-btn ep-text-primary ep-clear-filter">';
        filter_html += '<span class="material-icons-outlined ep-fs-6">close</span>';
        filter_html += '</button>';
        jQuery('#ep_' + param.key).html(filter_html);
    } else {
        filter_html += '<div data-key="' + param.key + '" id="ep_' + param.key + '" class="ep-btn-group ep-btn-group-sm ep-bg-primary ep-bg-opacity-10 ep-rounded-1 ep-mb-2 ep-mr-1" role="group" aria-label="' + param.label + '">';
        filter_html += '<button type="button" class="ep-btn ep-text-dark ep-border-0">';
        filter_html += '<strong class="ep-text-small">' + param.label + ': </strong>';
        filter_html += '<span class="ep-text-small">' + value + '</span>';
        filter_html += '</button>';
        filter_html += '<button type="button" class="ep-btn ep-text-primary ep-clear-filter ep-border-0">';
        filter_html += '<span class="material-icons-outlined ep-fs-6">close</span>';
        filter_html += '</button>';
        filter_html += '</div>';
        // append html
        jQuery('#ep_applied_filters_section').append(filter_html);
    }
}

function event_filters_selection_update(event_search_params, param) {
    var found = false;
    for (const i of event_search_params) {
        if (i.key == param.key) {
            found = true;
            i.value = param.value;
        }
    }
    if (found === false) {
        event_search_params.push(param);
    }
}

function event_filters_selection_remove(event_search_params, key) {
    for (var i = 0; i < event_search_params.length; i++) {
        if (event_search_params[i].key == key) {
            event_search_params.splice(i, 1);
            if (key === 'date_from') {
                jQuery('#filter-date-from').val('');
            } else if (key === 'date_to') {
                jQuery('#filter-date-from').val('');
            } else if (key === 'days') {
                jQuery('#filter-date-days').val('');
            } else if (key === 'event_types') {
                jQuery('#ep-filter-types').val(null);
            } else if (key === 'event_venues') {
                jQuery('#ep-filter-venues').select2("val", "");
            } else if (key === 'event_performers') {
                jQuery('#filter_performer').select2("val", "");
            } else if (key === 'event_organizers') {
                jQuery('#ep-filter-org').select2();
            }
            break;
        }
    }
}

function ep_render_calendar_view(cal_events, search_param = '')
{
    ep_load_calendar_view(search_param);
}

function columnHeaderFormat(format) {
    var calFormat = {weekday: 'short'};
    if (format == 'dddd') {
        calFormat = {weekday: 'long'};
    }
    if (format == 'ddd D/M') {
        calFormat = {weekday: 'long', day: 'numeric', month: 'numeric', omitCommas: true};
    }
    if (format == 'ddd M/D') {
        calFormat = {weekday: 'long', month: 'numeric', day: 'numeric', omitCommas: true};
    }
    return calFormat;
}

function epCard_width_adjust(cardClass) {
    $ = jQuery;
    jQuery(".ep-event-card").removeClass(["ep-card-col-", "ep-card-col-1", "ep-card-col-2", "ep-card-col-3", "ep-card-col-4", "ep-card-col-5"]);
    kfWidth = $("#ep-events-content-container").innerWidth();

    if (kfWidth < 720) {
        $("#ep-events-content-container").addClass("ep-narrow");
    }
    switch (true) {
        case kfWidth <= 500:
            $(cardClass).addClass("ep-card-col-12");

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
function epSearch_width_adjust(searchFilterClass) {
    $ = jQuery;
    jQuery(".ep-event-views-col").removeClass(["ep-box-col-4"]);
    searchWidth = $("#ep_event_search_form").innerWidth();
    //console.log(searchWidth);
    switch (true) {

        case searchWidth <= 650:
            $(searchFilterClass).addClass("ep-box-col-12");

        default:
            $(searchFilterClass).addClass("ep-box-col-4 ep-default");
            break;
    }
}
function epSearch_bar_width_adjust(searchBarFilterClass) {
    $ = jQuery;
    jQuery(".ep-search-filter-bar").removeClass(["ep-box-col-8"]);
    searchWidth = $("#ep_event_search_form").innerWidth();
    switch (true) {

        case searchWidth <= 650:
            $(searchBarFilterClass).addClass("ep-box-col-12");

        default:
            $(searchBarFilterClass).addClass("ep-box-col-8 ep-default");
            break;
    }
}


// on window resize
jQuery(window).resize(function () {

    if (jQuery("#ep-events-content-container").length > 0) {
        jQuery(".ep-event-card").removeClass(["ep-card-col-", "ep-card-col-12", "ep-card-col-6", "ep-card-col-4", "ep-card-col-3", "ep-card-col-2"]);
        epCard_width_adjust(".ep-event-card");
    }
    if (eventprime.global_settings.events_no_of_columns === 4) {
        jQuery(".ep-event-card").addClass(["ep-card-col-3"]).removeClass(["ep-card-col-4", "ep-card-col-6", "ep-card-col-12"]);
    }

    if (eventprime.global_settings.events_no_of_columns === 3) {
        jQuery(".ep-event-card").addClass(["ep-card-col-4"]).removeClass(["ep-card-col-3", "ep-card-col-6", "ep-card-col-12"]);
        ;
    }

    if (eventprime.global_settings.events_no_of_columns === 2) {
        jQuery(".ep-event-card").addClass(["ep-card-col-6"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-12"]);
    }

    if (eventprime.global_settings.events_no_of_columns === 1) {
        jQuery(".ep-event-card").addClass(["ep-card-col-12"]).removeClass(["ep-card-col-3", "ep-card-col-4", "ep-card-col-6"]);
        ;
    }

    epCalViewWidhth();

});

// check calendar view width
function epCalViewWidhth() {
    $ = jQuery;
    calAreaWidht = $("#ep-events-content-container").innerWidth();
    if (calAreaWidht <= 620) {
        $("#ep-events-content-container").addClass("ep-narrow");
        $("#ep-events-content-container").removeClass("ep-wide");

        //Event Search Filter
        $('.ep-event-views-col').removeClass('ep-justify-content-end');
        $('.ep-event-views-col').addClass('ep-justify-content-center');
    } else {
        $("#ep-events-content-container").removeClass("ep-narrow");
        $("#ep-events-content-container").addClass("ep-wide");

        //Event Search Filter
        $('.ep-event-views-col').removeClass('ep-justify-content-center');
    }
}

function formatDate(date, locale, pattern = 'MMMM, YYYY') {
    if (!(date instanceof Date) || isNaN(date)) {
        return ''; // Handle invalid date
    }

    // Construct the formatted date using Intl.DateTimeFormat options
    var options = {year: 'numeric', month: 'long', day: 'numeric'};
    var formatter = new Intl.DateTimeFormat(locale, options);
    var formattedDate = formatter.format(date);

    // Extract the month index using the format method
    var monthIndex = date.getMonth();

    var monthNames = getMonthNames(locale);

    var formattedPattern = pattern.toString()
            .replace('YYYY', date.getFullYear())
            .replace('MMMM', monthNames[monthIndex])
            .replace('DD', date.getDate());

    return formattedPattern;
}


function getMonthNames(locale) {
    // Use Intl.DateTimeFormat to get month names for the specified locale
    var monthNames = [];
    for (var i = 0; i < 12; i++) {
        var formattedMonth = new Date(2000, i, 1).toLocaleDateString(locale, {month: 'long'});
        monthNames.push(formattedMonth);
    }
    return monthNames;
}

jQuery(document).ready(function () {
    // Check if the user is using a mobile device
    setTimeout(function () {
        const calAreaWidht = jQuery("#ep-events-content-container").innerWidth();
        var isMobile = /iPhone|iPad|iPod|Android|webOS|BlackBerry|Windows Phone/i.test(navigator.userAgent);
        if (isMobile || calAreaWidht <= 720) {
            // Remove href attribute for event table a tag
            jQuery('#ep_event_calendar.fc table.fc-scrollgrid-sync-table .fc-daygrid-event').removeAttr('href');
        }
    }, 3000);
});


function ep_load_calendar_view(search_param = '')
{

    // set initial view
    let default_view = 'dayGridMonth';
    let calendar_views = {
        'month': 'dayGridMonth',
        'week': 'dayGridWeek',
        'day': 'dayGridDay',
        'listweek': 'listWeek',
    };
    if (eventprime.global_settings.default_cal_view) {
        if (em_front_event_object.view) {
            default_view = calendar_views[em_front_event_object.view];
        } else {
            default_view = calendar_views[eventprime.global_settings.default_cal_view];
        }
    }
    // set initial date
    let cal_initial_date = new Date();
    if (eventprime.global_settings.enable_default_calendar_date == 1) {
        if (eventprime.global_settings.default_calendar_date && eventprime.global_settings.default_calendar_date != '') {
            cal_initial_date = eventprime.global_settings.default_calendar_date;
        }
    }
    // hide prev and next month rows
    let hide_calendar_rows = true;
    if (eventprime.global_settings.hide_calendar_rows == 1) {
        hide_calendar_rows = false;
    }
    // set calendar right view options
    let right_views = [];
    if (eventprime.global_settings.front_switch_view_option) {
        if (eventprime.global_settings.front_switch_view_option.indexOf('month') > -1) {
            right_views.push('dayGridMonth');
        }
        if (eventprime.global_settings.front_switch_view_option.indexOf('week') > -1) {
            right_views.push('dayGridWeek');
        }
        if (eventprime.global_settings.front_switch_view_option.indexOf('day') > -1) {
            right_views.push('dayGridDay');
        }
        if (eventprime.global_settings.front_switch_view_option.indexOf('listweek') > -1) {
            right_views.push('listWeek');
        }
    }
    if (right_views && right_views.length > 0) {
        right_views = right_views.toString();
    } else {
        right_views = '';
    }
    // set column header format
    let calendar_column_header_format = eventprime.global_settings.calendar_column_header_format;
    let column_header_format = 'long';
    if (calendar_column_header_format == 'ddd') {
        column_header_format = 'short';
    }
    // set day max events
    let day_max_events = eventprime.global_settings.show_max_event_on_calendar_date;
    if (!day_max_events) {
        day_max_events = 2;
    }
    // set 12 and 24 hours
    let hour12 = true;
    if (eventprime.global_settings.time_format == 'HH:mm') {
        hour12 = false;
    }
    var calendarEl = document.getElementById('ep_event_calendar');
    var element = jQuery('#ep_hidden_args').val();
    // if (element !== null) {
    if (element) {
        var args = element;
        // Do something with args
    } else if (em_front_event_object.event_attributes) {
        var args = JSON.stringify(em_front_event_object.event_attributes);
    } else {
        var args = '';
    }

    if (calendarEl) {
        const use24h = window.em_front_event_object && em_front_event_object.timeformat === 'HH:mm';
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prevYear,prev,next,nextYear today',
                center: 'title',
                right: right_views
            },
            // views: {
            //     listWeek: { buttonText: 'Agenda' }
            // },
            buttonText: {
                listWeek: em_front_event_object.list_week_btn_text,
            },
            initialDate: cal_initial_date,
            initialView: default_view,
            navLinks: true, // can click day/week names to navigate views
            dayMaxEvents: day_max_events, // allow "more" link when too many events
            editable: false,
            timeZone: 'local',
            eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},
            slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},
            height: "auto",
            events: function (info, successCallback, failureCallback) {
                // Calculate the start and end dates of the current month
                jQuery('.ep-event-loader').show();
                $.ajax({
                    url: eventprime.ajaxurl, // Replace with your events API endpoint
                    method: 'POST',
                    data: {
                        action: 'ep_get_calendar_event',
                        security: em_front_event_object._nonce,
                        start: info.startStr,
                        end: info.endStr,
                        args: args,
                        search_param: search_param

                    },
                    success: function (data) {



                        if (data.data) {
                            const pad = n => String(n).padStart(2, '0');

                            const toISO = (dateStr, timeStr) => {
                                // timeStr like "11:45 PM" or "12:00 AM"
                                const d = new Date(`${dateStr} ${timeStr}`);
                                const y = d.getFullYear();
                                const m = pad(d.getMonth() + 1);
                                const da = pad(d.getDate());
                                const h = pad(d.getHours());
                                const mi = pad(d.getMinutes());
                                return `${y}-${m}-${da}T${h}:${mi}:00`;
                            };

                            const addOneDayISODate = (dateStr) => {
                                const d = new Date(`${dateStr}T00:00:00`);
                                d.setDate(d.getDate() + 1);
                                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                            };

                            const isDateOnly = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);

                            const normalized = data.data.map(e => {
                                // Map WP field to FullCalendar
                                e.allDay = (e.all_day === 1 || e.all_day === '1' || e.all_day === true);
                                e.hideStartTime = !e.start_time; // true if start_time is blank
                                e.hideEndTime = !e.end_time;   // true if end_time is blank
                                // START: add time if missing for timed events
                                if (isDateOnly(e.start) && !e.allDay && e.start_time) {
                                    e.start = toISO(e.start, e.start_time);
                                }
                                // END: add time if missing for timed events
                                if (isDateOnly(e.end) && !e.allDay && e.end_time) {
                                    e.end = toISO(e.end, e.end_time);
                                }
                                

                                return e;
                            });

                            successCallback(normalized);
                        }

                        setTimeout(function () {
                            jQuery('.ep-event-loader').hide();
                        }, 400);
                        // jQuery('.ep-event-loader').hide();
                    },
                    error: function () {
                        failureCallback();
                    }
                });
            },

            showNonCurrentDates: hide_calendar_rows,
            fixedWeekCount: hide_calendar_rows,
            nextDayThreshold: '00:00',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: hour12,
                meridiem: 'short'
            },
            firstDay: em_front_event_object.start_of_week,
            locale: em_front_event_object.local,
            eventContent(arg) {
                const s = arg.event.extendedProps.display_start_time || '';
                const e = arg.event.extendedProps.display_end_time || '';
                let timeLabel = '';

                if (arg.isStart && arg.isEnd && s && e)
                    timeLabel = `${s} – ${e}`;
                else if (arg.isStart && s)
                    timeLabel = s;
                else if (arg.isEnd && e)
                    timeLabel = e;
                else
                    timeLabel = ''; // middle day: no "00:00"

                const timeEl = document.createElement('div');
                timeEl.className = 'fc-event-time';
                timeEl.textContent = timeLabel;

                const titleEl = document.createElement('div');
                titleEl.className = 'fc-event-title';
                titleEl.textContent = arg.event.title || '';

                return {domNodes: [timeEl, titleEl]};
            },

            titleFormat: {year: 'numeric', month: 'long', day: 'numeric'},
            dayHeaderFormat: {weekday: column_header_format},
            eventDidMount: function (info) {
                console.log(info);
                let light_bg_color = '';
                if (info.event.extendedProps.hasOwnProperty('bg_color')) {
                    var epColorRgb = info.event.extendedProps.bg_color;
                    var avoid = "rgb";
                    var eprgbRemover = epColorRgb.replace(avoid, '');
                    var emColor_bg = eprgbRemover.substring(eprgbRemover.indexOf('(') + 1, eprgbRemover.indexOf(')'))
                    info.el.style.backgroundColor = `rgba(${emColor_bg},1)`;
                    light_bg_color = info.el.style.backgroundColor;
                    info.el.style.borderColor = `rgba(${emColor_bg},1)`;
                }
                var textColor = light_bg_color;
                if (info.event.extendedProps.hasOwnProperty('type_text_color')) {
                    textColor = info.event.extendedProps.type_text_color;
                }
                if (info.event.extendedProps.hasOwnProperty('event_text_color')) {
                    textColor = info.event.extendedProps.event_text_color;
                }

                var fc_time = info.el.querySelector('.fc-event-time');
                if (fc_time) {
                    fc_time.style.color = textColor;
                    if (em_front_event_object.hide_time_on_front_calendar == 1) {
                        fc_time.textContent = '';
                        fc_time.style.color = '';
                    }
                }

                if (textColor) {
                    var fc_time = info.el.querySelector('.fc-time');
                    if (fc_time) {
                        fc_time.style.color = textColor;
                        if (em_front_event_object.hide_time_on_front_calendar == 1) {
                            fc_time.textContent = '';
                            fc_time.style.color = '';
                        }
                    }
                    var fc_title = info.el.querySelector('.fc-event-title');
                    if (fc_title) {
                        fc_title.style.color = textColor;
                    }
                    var fc_list_time = info.el.querySelector('.fc-event-time');
                    if (fc_list_time) {
                        fc_list_time.style.color = textColor;
                    }
                    var fc_list_title = info.el.querySelector('.fc-list-item-title');
                    if (fc_list_title) {
                        fc_list_title.style.color = textColor;
                    }
                    var fc_list_event_time = info.el.querySelector('.fc-list-event-time');
                    if (fc_list_event_time) {
                        fc_list_event_time.style.color = textColor;
                    }
                    var fc_list_event_dot = info.el.querySelector('.fc-list-event-dot');
                    if (fc_list_event_dot) {
                        fc_list_event_dot.style.color = textColor;
                    }
                    var fc_list_event_title = info.el.querySelector('.fc-list-event-title');
                    if (fc_list_event_title) {
                        fc_list_event_title.style.color = textColor;
                    }
                }

                // --- per-event time hiding/rewriting ---
                const {hideStartTime, hideEndTime} = info.event.extendedProps || {};
                const hour12 = (eventprime.global_settings.time_format !== 'HH:mm');

                // format helper
                const fmt = d => {
                    if (!d)
                        return '';
                    try {
                        return new Intl.DateTimeFormat(em_admin_calendar_event_object.local, {
                            hour: '2-digit', minute: '2-digit', hour12
                        }).format(d);
                    } catch (e) {
                        // fallback
                        const pad = n => String(n).padStart(2, '0');
                        return d.getHours() + ':' + pad(d.getMinutes());
                    }
                };

                // nodes present across views (dayGrid/timeGrid/list)
                const nDayTime = info.el.querySelector('.fc-event-time');       // dayGrid/timeGrid
                const nListTime = info.el.querySelector('.fc-list-event-time');  // list
                // In some themes, dayGrid uses '.fc-time' alias as well:
                const nAltTime = info.el.querySelector('.fc-time');

                // Build the desired time label
                const startText = hideStartTime ? '' : info.event.extendedProps.display_start_time; //fmt(info.event.start);
                
                // For end: if end is null, we won't show anything anyway
                const endText = hideEndTime ? '' : info.event.extendedProps.display_end_time; //fmt(info.event.end);

                let label = '';
                if (startText && endText) {
                    label = startText + ' – ' + endText;
                } else if (startText && !endText) {
                    label = startText;                 // show only start
                } else if (!startText && endText) {
                    label = endText;                   // show only end
                } else {
                    label = '';                        // both hidden → no label
                }

                // Apply the label (or hide node completely)
                const applyLabel = node => {
                    if (!node)
                        return;
                    if (label) {
                        node.textContent = label;
                        node.style.display = '';
                    } else {
                        node.textContent = '';
                        // node.style.display = 'none';
                    }
                };

                applyLabel(nDayTime);
                applyLabel(nAltTime);
                applyLabel(nListTime);

                // If you *also* globally hide times via a setting, keep your existing guard:
                if (em_front_event_object.hide_time_on_front_calendar == 1) {
                    if (nDayTime)
                        nDayTime.style.display = 'none';
                    if (nAltTime)
                        nAltTime.style.display = 'none';
                    if (nListTime)
                        nListTime.style.display = 'none';
                }
                // --- per-event time hiding/rewriting end---


                if (em_front_event_object.hide_time_on_front_calendar == 1) {

                    $(info.el).find('.fc-event-time').hide();
                    $(info.el).find('.fc-list-event-time').hide();

                }
                
                const isList = info.view && String(info.view.type).startsWith('list');
                if(isList)
                {
                    $(info.el).find('.fc-event-time').hide();
                }
                $(info.el).append(info.event.extendedProps.popup_html);
                // 1) FC variables (work across views/themes)
                const bg = info.event.extendedProps.bg_color || '';
                if (textColor) info.el.style.setProperty('--fc-event-text-color', textColor);
                if (bg) {
                  info.el.style.setProperty('--fc-event-bg-color', bg);
                  info.el.style.setProperty('--fc-event-border-color', bg);
                }

                // 2) Also push inline color to common wrappers used by week view
                const mainNodes = info.el.querySelectorAll(
                  '.fc-event-main, .fc-event-main-frame, .fc-event-title, .fc-event-time, .fc-time'
                );
                mainNodes.forEach(n => { n.style.color = textColor; });

                const anchor = info.el.matches('a') ? info.el : info.el.querySelector('a');
                if (anchor) anchor.style.color = textColor;

                // 3) list view dot needs background
                const dot = info.el.querySelector('.fc-list-event-dot');
                if (dot && bg) { dot.style.backgroundColor = bg; dot.style.borderColor = bg; }

            },
            eventMouseEnter: function (info) {
                let pop_block = info.el.querySelector('.ep_event_detail_popup');
                pop_block.style.display = 'block';
            },
            eventMouseLeave: function (info) {
                let pop_block = info.el.querySelector('.ep_event_detail_popup');
                pop_block.style.display = 'none';
            },
            eventClick: function (info) {
                var eventObj = info.event;
                if (eventObj.url) {
                    let open_event_in_new_tab = info.event.extendedProps.open_event_in_new_tab;
                    if (open_event_in_new_tab == 1) {
                        window.open(eventObj.url);
                        info.jsEvent.preventDefault();
                    }
                }
            },
        });

        if (!document.getElementById('ep-fc-color-fix')) {
            const st = document.createElement('style');
            st.id = 'ep-fc-color-fix';
            st.textContent = `
              #ep_event_calendar .fc-event,
              #ep_event_calendar .fc-event .fc-event-main,
              #ep_event_calendar .fc-event .fc-event-title,
              #ep_event_calendar .fc-event .fc-event-time,
              #ep_event_calendar .fc-event .fc-time,
              #ep_event_calendar .fc-event a {
                color: var(--fc-event-text-color) !important;
              }
              #ep_event_calendar .fc-list-event-dot {
                background-color: var(--fc-event-bg-color) !important;
                border-color: var(--fc-event-bg-color) !important;
              }
            `;
            document.head.appendChild(st);
          }

        calendar.render();

        let calAreaWidht = $("#ep-events-content-container").innerWidth();
        if (calAreaWidht <= 720) {
            calendar.setOption('dayHeaderFormat', {weekday: 'short'});
        } else {
            calendar.setOption('dayHeaderFormat', {weekday: column_header_format});
        }

        // custom class on the toolbal title
        let cal_title_class = 'ep-calendar-title-short';

        let calendar_title_format = "MMMM, YYYY";
        if (typeof eventprime.global_settings.calendar_title_format !== 'undefined') {
            calendar_title_format = eventprime.global_settings.calendar_title_format;
        }

        if (calendar_title_format.search('DD') > -1) {
            cal_title_class = 'ep-calendar-title-full';
        }
        $('#ep_event_calendar .fc-header-toolbar .fc-toolbar-title').addClass(cal_title_class);
    }

    /*
     * Select Filter Box
     */
    $("#filter-date-from").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_to = $('#filter-date-to').val();
            if (date_to) {
                $("#filter-date-from").datepicker("option", {
                    maxDate: date_to // set max date to to date
                });
            }
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-from').click(function (e) {
        $("#filter-date-from").datepicker('show');
    });
    $("#filter-date-to").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_from = $('#filter-date-from').val();
            if (date_from) {
                $("#filter-date-to").datepicker("option", {
                    minDate: date_from // set min date to from date
                });
            }
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-to').click(function (e) {
        $("#filter-date-to").datepicker('show');
    });

    $('#ep-filter-venues, #ep-filter-types').select2();
    $('#ep-filter-org').select2({
        placeholder: em_front_event_object.organizers,
        multiple: true,
        //templateSelection: set_organizers_query,
        allowClear: true
    });
    $('#ep-filter-performer').select2({
        placeholder: em_front_event_object.performers,
        multiple: true,
        //templateSelection: set_performers_query,
        allowClear: true
    });

    // event filter list toggle
    if ($('#ep-event-view-selector').length > 0) {
        $('#ep-event-view-selector, .ep-box-dropdown-overlay').click(function () {
            $('.ep-event-views-content').slideToggle();
            $('#ep-event-view-selector').toggleClass('ep-box-dropdown-open');
        });
    }

    //datepicker
    $('.epDatePicker').datepicker({
        changeYear: true,
        changeMonth: true,
        dateFormat: eventprime.datepicker_format,
        gotoCurrent: true,
        showButtonPanel: true,
    });

    $("#ep_event_keyword_search").on('click', function () {
        $("#ep-search-filters").css("animation", "ep-searchfilters 1s forwards normal 1");
        $(".ep-search-filter-overlay").show();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-3").removeClass("ep-z-index-1");
    });

    $(".ep-search-filter-overlay").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-1").removeClass("ep-z-index-3");
    });
    $("#ep_filter_next_weekend, #ep_filter_next_week, #ep_filter_next_month, #ep_filter_online").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
    });


    /* let ep_mcontainer = document.querySelector( '#ep_events_front_views_staggered_grid' );
     if ( ep_mcontainer ) {
     let msnry;
     imagesLoaded( ep_mcontainer, function() {
     msnry = new Masonry( ep_mcontainer, {
     itemSelector: '#ep_events_front_views_staggered_grid .ep-event-card',
     });
     });
     } */

    epCalViewWidhth();

    ///  Event square card Image Setting start

    var styleElement = jQuery("<style>");

    // Set the text content to include dynamic variables in :root
    var styleContent = ":root {\n";

    if (eventprime.global_settings.events_image_visibility_options) {
        styleContent += " --ep-imageCardObjectFit: " + eventprime.global_settings.events_image_visibility_options + ";\n";
    } else {
        styleContent += " --ep-imageCardObjectFit: " + 'cover' + ";\n";
    }
    if (eventprime.global_settings.events_image_height) {
        styleContent += "  --ep-imageCardHeight: " + eventprime.global_settings.events_image_height + "px" + ";\n";
    } else {
        styleContent += " --ep-imageCardHeight: " + '140' + "px" + ";\n";
    }

    styleContent += "}\n";
    styleElement.text(styleContent);

    //  Append the <style> element to the <head> of the document
    jQuery("head").append(styleElement);

    //   Event square card Image Setting end
}


function ep_load_calendar_view_new(search_param = '') {

    // set initial view
    let default_view = 'dayGridMonth';
    let calendar_views = {
        'month': 'dayGridMonth',
        'week': 'dayGridWeek',
        'day': 'dayGridDay',
        'listweek': 'listWeek',
    };
    if (eventprime.global_settings.default_cal_view) {
        if (em_front_event_object.view) {
            default_view = calendar_views[em_front_event_object.view];
        } else {
            default_view = calendar_views[eventprime.global_settings.default_cal_view];
        }
    }

    // set initial date
    let cal_initial_date = new Date();
    if (eventprime.global_settings.enable_default_calendar_date == 1) {
        if (eventprime.global_settings.default_calendar_date && eventprime.global_settings.default_calendar_date !== '') {
            cal_initial_date = eventprime.global_settings.default_calendar_date;
        }
    }

    // hide prev and next month rows
    let hide_calendar_rows = true;
    if (eventprime.global_settings.hide_calendar_rows == 1) {
        hide_calendar_rows = false;
    }

    // set calendar right view options
    let right_views = [];
    if (eventprime.global_settings.front_switch_view_option) {
        if (eventprime.global_settings.front_switch_view_option.indexOf('month') > -1)
            right_views.push('dayGridMonth');
        if (eventprime.global_settings.front_switch_view_option.indexOf('week') > -1)
            right_views.push('dayGridWeek');
        if (eventprime.global_settings.front_switch_view_option.indexOf('day') > -1)
            right_views.push('dayGridDay');
        if (eventprime.global_settings.front_switch_view_option.indexOf('listweek') > -1)
            right_views.push('listWeek');
    }
    right_views = (right_views && right_views.length > 0) ? right_views.toString() : '';

    // set column header format
    let calendar_column_header_format = eventprime.global_settings.calendar_column_header_format;
    let column_header_format = 'long';
    if (calendar_column_header_format == 'ddd') {
        column_header_format = 'short';
    }

    // set day max events
    let day_max_events = eventprime.global_settings.show_max_event_on_calendar_date;
    if (!day_max_events)
        day_max_events = 2;

    // 12/24-hour mode (single source of truth)
    const use24h = (eventprime.global_settings.time_format === 'HH:mm');

    var calendarEl = document.getElementById('ep_event_calendar');
    var element = jQuery('#ep_hidden_args').val();
    var args = '';
    if (element) {
        args = element;
    } else if (em_front_event_object.event_attributes) {
        args = JSON.stringify(em_front_event_object.event_attributes);
    }

    if (calendarEl) {
        var calendar = new FullCalendar.Calendar(calendarEl, {
            headerToolbar: {
                left: 'prevYear,prev,next,nextYear today',
                center: 'title',
                right: right_views
            },
            buttonText: {
                listWeek: em_front_event_object.list_week_btn_text,
            },
            initialDate: cal_initial_date,
            initialView: default_view,
            navLinks: true,
            dayMaxEvents: day_max_events,
            editable: false,
            timeZone: 'local',

            // FC’s own formats (affects timeGrid/list/headers). Keep only one block.
            eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},
            slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},

            height: "auto",

            events: function (info, successCallback, failureCallback) {
                jQuery('.ep-event-loader').show();
                $.ajax({
                    url: eventprime.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'ep_get_calendar_event',
                        security: em_front_event_object._nonce,
                        start: info.startStr,
                        end: info.endStr,
                        args: args,
                        search_param: search_param
                    },
                    success: function (data) {
                        if (data && data.data) {
                            const pad = n => String(n).padStart(2, '0');

                            const toISO = (dateStr, timeStr) => {
                                // timeStr like "11:45 PM" or "12:00 AM"
                                const d = new Date(`${dateStr} ${timeStr}`);
                                const y = d.getFullYear();
                                const m = pad(d.getMonth() + 1);
                                const da = pad(d.getDate());
                                const h = pad(d.getHours());
                                const mi = pad(d.getMinutes());
                                return `${y}-${m}-${da}T${h}:${mi}:00`;
                            };

                            const addOneDayISODate = (dateStr) => {
                                const d = new Date(`${dateStr}T00:00:00`);
                                d.setDate(d.getDate() + 1);
                                return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                            };

                            const isDateOnly = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);

                            const normalized = data.data.map(e => {
                                // Map WP field to FullCalendar
                                e.allDay = (e.all_day === 1 || e.all_day === '1' || e.all_day === true);

                                // convenience flags
                                e.hideStartTime = !e.start_time;
                                e.hideEndTime = !e.end_time;

                                // If start/end are date-only but this is a timed event, attach the time
                                if (isDateOnly(e.start) && !e.allDay && e.start_time) {
                                    e.start = toISO(e.start, e.start_time);
                                }
                                if (isDateOnly(e.end) && !e.allDay && e.end_time) {
                                    e.end = toISO(e.end, e.end_time);
                                }

                                // True all-day: make end exclusive (next day)
                                if (e.allDay) {
                                    if (!isDateOnly(e.start) && typeof e.event_start_date === 'string') {
                                        e.start = e.event_start_date; // YYYY-MM-DD
                                    }
                                    const baseEnd = isDateOnly(e.end) ? e.end
                                            : (typeof e.event_end_date === 'string' ? e.event_end_date : e.start);
                                    e.end = addOneDayISODate(baseEnd);
                                }

                                return e;
                            });

                            successCallback(normalized);
                        }

                        setTimeout(function () {
                            jQuery('.ep-event-loader').hide();
                        }, 400);
                    },
                    error: function () {
                        failureCallback();
                    }
                });
            },

            showNonCurrentDates: hide_calendar_rows,
            fixedWeekCount: hide_calendar_rows,
            nextDayThreshold: '00:00',

            // Custom label for chips: use display_* times from PHP; avoid 00:00 on continuation days
            eventContent(arg) {
                const s = arg.event.extendedProps.display_start_time || '';
                const e = arg.event.extendedProps.display_end_time || '';
                let timeLabel = '';

                if (arg.isStart && arg.isEnd && s && e)
                    timeLabel = `${s} – ${e}`;
                else if (arg.isStart && s)
                    timeLabel = s;
                else if (arg.isEnd && e)
                    timeLabel = e;
                else
                    timeLabel = ''; // middle day segment

                const timeEl = document.createElement('div');
                timeEl.className = 'fc-event-time';
                timeEl.textContent = timeLabel;

                const titleEl = document.createElement('div');
                titleEl.className = 'fc-event-title';
                titleEl.textContent = arg.event.title || '';

                return {domNodes: [timeEl, titleEl]};
            },

            titleFormat: {year: 'numeric', month: 'long', day: 'numeric'},
            dayHeaderFormat: {weekday: column_header_format},

            eventDidMount: function (info) {
                // --- colors only ---
                let light_bg_color = '';
                if (info.event.extendedProps.hasOwnProperty('bg_color')) {
                    var epColorRgb = info.event.extendedProps.bg_color; // e.g., "rgb( 230,230,250 )"
                    var emColor_bg = epColorRgb.substring(epColorRgb.indexOf('(') + 1, epColorRgb.indexOf(')'));
                    info.el.style.backgroundColor = `rgba(${emColor_bg},1)`;
                    info.el.style.borderColor = `rgba(${emColor_bg},1)`;
                    light_bg_color = info.el.style.backgroundColor;
                }

                var textColor = light_bg_color;
                if (info.event.extendedProps.hasOwnProperty('type_text_color')) {
                    textColor = info.event.extendedProps.type_text_color;
                }
                if (info.event.extendedProps.hasOwnProperty('event_text_color')) {
                    textColor = info.event.extendedProps.event_text_color;
                }

                // apply text colors to known nodes (no time rewriting here)
                const selectors = [
                    '.fc-event-time',
                    '.fc-time',
                    '.fc-event-title',
                    '.fc-list-event-time',
                    '.fc-list-item-title',
                    '.fc-list-event-title',
                    '.fc-list-event-dot'
                ];
                selectors.forEach(q => {
                    const n = info.el.querySelector(q);
                    if (n && textColor)
                        n.style.color = textColor;
                });

                // Global hide (if enabled)
                if (em_front_event_object.hide_time_on_front_calendar == 1) {
                    const a = info.el.querySelector('.fc-event-time');
                    const b = info.el.querySelector('.fc-list-event-time');
                    if (a)
                        a.style.display = 'none';
                    if (b)
                        b.style.display = 'none';
                }

                // append popup
                $(info.el).append(info.event.extendedProps.popup_html);
            },

            eventMouseEnter: function (info) {
                let pop_block = info.el.querySelector('.ep_event_detail_popup');
                if (pop_block)
                    pop_block.style.display = 'block';
            },
            eventMouseLeave: function (info) {
                let pop_block = info.el.querySelector('.ep_event_detail_popup');
                if (pop_block)
                    pop_block.style.display = 'none';
            },
            eventClick: function (info) {
                var eventObj = info.event;
                if (eventObj.url) {
                    let open_event_in_new_tab = info.event.extendedProps.open_event_in_new_tab;
                    if (open_event_in_new_tab == 1) {
                        window.open(eventObj.url);
                        info.jsEvent.preventDefault();
                    }
                }
            },
        });

        calendar.render();

        let calAreaWidht = $("#ep-events-content-container").innerWidth();
        if (calAreaWidht <= 720) {
            calendar.setOption('dayHeaderFormat', {weekday: 'short'});
        } else {
            calendar.setOption('dayHeaderFormat', {weekday: column_header_format});
        }

        // custom class on the toolbar title
        let cal_title_class = 'ep-calendar-title-short';
        let calendar_title_format = "MMMM, YYYY";
        if (typeof eventprime.global_settings.calendar_title_format !== 'undefined') {
            calendar_title_format = eventprime.global_settings.calendar_title_format;
        }
        if (calendar_title_format.search('DD') > -1) {
            cal_title_class = 'ep-calendar-title-full';
        }
        $('#ep_event_calendar .fc-header-toolbar .fc-toolbar-title').addClass(cal_title_class);
    }

    /*
     * Select Filter Box
     */
    $("#filter-date-from").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_to = $('#filter-date-to').val();
            if (date_to) {
                $("#filter-date-from").datepicker("option", {maxDate: date_to});
            }
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-from').click(function (e) {
        $("#filter-date-from").datepicker('show');
    });
    $("#filter-date-to").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_from = $('#filter-date-from').val();
            if (date_from) {
                $("#filter-date-to").datepicker("option", {minDate: date_from});
            }
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-to').click(function (e) {
        $("#filter-date-to").datepicker('show');
    });

    $('#ep-filter-venues, #ep-filter-types').select2();
    $('#ep-filter-org').select2({
        placeholder: em_front_event_object.organizers,
        multiple: true,
        allowClear: true
    });
    $('#ep-filter-performer').select2({
        placeholder: em_front_event_object.performers,
        multiple: true,
        allowClear: true
    });

    // event filter list toggle
    if ($('#ep-event-view-selector').length > 0) {
        $('#ep-event-view-selector, .ep-box-dropdown-overlay').click(function () {
            $('.ep-event-views-content').slideToggle();
            $('#ep-event-view-selector').toggleClass('ep-box-dropdown-open');
        });
    }

    // datepicker
    $('.epDatePicker').datepicker({
        changeYear: true,
        changeMonth: true,
        dateFormat: eventprime.datepicker_format,
        gotoCurrent: true,
        showButtonPanel: true,
    });

    $("#ep_event_keyword_search").on('click', function () {
        $("#ep-search-filters").css("animation", "ep-searchfilters 1s forwards normal 1");
        $(".ep-search-filter-overlay").show();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-3").removeClass("ep-z-index-1");
    });

    $(".ep-search-filter-overlay").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-1").removeClass("ep-z-index-3");
    });
    $("#ep_filter_next_weekend, #ep_filter_next_week, #ep_filter_next_month, #ep_filter_online").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
    });

    epCalViewWidhth();

    //  Event square card Image Setting start
    var styleElement = jQuery("<style>");
    var styleContent = ":root {\n";
    if (eventprime.global_settings.events_image_visibility_options) {
        styleContent += " --ep-imageCardObjectFit: " + eventprime.global_settings.events_image_visibility_options + ";\n";
    } else {
        styleContent += " --ep-imageCardObjectFit: cover;\n";
    }
    if (eventprime.global_settings.events_image_height) {
        styleContent += " --ep-imageCardHeight: " + eventprime.global_settings.events_image_height + "px;\n";
    } else {
        styleContent += " --ep-imageCardHeight: 140px;\n";
    }
    styleContent += "}\n";
    styleElement.text(styleContent);
    jQuery("head").append(styleElement);
    //  Event square card Image Setting end
}

function ep_load_calendar_view_new2(search_param = '') {

    // set initial view
    let default_view = 'dayGridMonth';
    let calendar_views = {
        'month': 'dayGridMonth',
        'week': 'dayGridWeek',
        'day': 'dayGridDay',
        'listweek': 'listWeek',
    };
    if (eventprime.global_settings.default_cal_view) {
        default_view = em_front_event_object.view
                ? calendar_views[em_front_event_object.view]
                : calendar_views[eventprime.global_settings.default_cal_view];
    }

    // initial date
    let cal_initial_date = new Date();
    if (eventprime.global_settings.enable_default_calendar_date == 1 &&
            eventprime.global_settings.default_calendar_date) {
        cal_initial_date = eventprime.global_settings.default_calendar_date;
    }

    // hide prev/next rows
    let hide_calendar_rows = !(eventprime.global_settings.hide_calendar_rows == 1);

    // right views
    let right_views = [];
    if (eventprime.global_settings.front_switch_view_option) {
        if (eventprime.global_settings.front_switch_view_option.indexOf('month') > -1)
            right_views.push('dayGridMonth');
        if (eventprime.global_settings.front_switch_view_option.indexOf('week') > -1)
            right_views.push('dayGridWeek');
        if (eventprime.global_settings.front_switch_view_option.indexOf('day') > -1)
            right_views.push('dayGridDay');
        if (eventprime.global_settings.front_switch_view_option.indexOf('listweek') > -1)
            right_views.push('listWeek');
    }
    right_views = right_views.length ? right_views.toString() : '';

    // header/day names
    let column_header_format = (eventprime.global_settings.calendar_column_header_format === 'ddd') ? 'short' : 'long';

    // max events
    let day_max_events = eventprime.global_settings.show_max_event_on_calendar_date || 2;

    // 12/24h mode
    const use24h = (eventprime.global_settings.time_format === 'HH:mm');

    var calendarEl = document.getElementById('ep_event_calendar');
    var element = jQuery('#ep_hidden_args').val();
    var args = element ? element : (em_front_event_object.event_attributes ? JSON.stringify(em_front_event_object.event_attributes) : '');

    if (!calendarEl)
        return;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prevYear,prev,next,nextYear today',
            center: 'title',
            right: right_views
        },
        buttonText: {
            listWeek: em_front_event_object.list_week_btn_text,
        },
        initialDate: cal_initial_date,
        initialView: default_view,
        navLinks: true,
        dayMaxEvents: day_max_events,
        editable: false,
        timeZone: 'local',

        // FullCalendar’s native formats (timeGrid/list/headers)
        eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},
        slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},

        height: "auto",

        events: function (info, successCallback, failureCallback) {
            jQuery('.ep-event-loader').show();
            $.ajax({
                url: eventprime.ajaxurl,
                method: 'POST',
                data: {
                    action: 'ep_get_calendar_event',
                    security: em_front_event_object._nonce,
                    start: info.startStr,
                    end: info.endStr,
                    args: args,
                    search_param: search_param
                },
                success: function (data) {
                    if (data && data.data) {
                        const pad = n => String(n).padStart(2, '0');

                        const toISO = (dateStr, timeStr) => {
                            const d = new Date(`${dateStr} ${timeStr}`);
                            const y = d.getFullYear();
                            const m = pad(d.getMonth() + 1);
                            const da = pad(d.getDate());
                            const h = pad(d.getHours());
                            const mi = pad(d.getMinutes());
                            return `${y}-${m}-${da}T${h}:${mi}:00`;
                        };

                        const addOneDayISODate = (dateStr) => {
                            const d = new Date(`${dateStr}T00:00:00`);
                            d.setDate(d.getDate() + 1);
                            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        };

                        const isDateOnly = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);

                        const normalized = data.data.map(e => {
                            e.allDay = (e.all_day === 1 || e.all_day === '1' || e.all_day === true);

                            // flags for rendering
                            e.hideStartTime = !e.start_time;
                            e.hideEndTime = !e.end_time;

                            // attach times for timed date-only events
                            if (isDateOnly(e.start) && !e.allDay && e.start_time)
                                e.start = toISO(e.start, e.start_time);
                            if (isDateOnly(e.end) && !e.allDay && e.end_time)
                                e.end = toISO(e.end, e.end_time);

                            // all-day → exclusive end
                            if (e.allDay) {
                                if (!isDateOnly(e.start) && typeof e.event_start_date === 'string') {
                                    e.start = e.event_start_date;
                                }
                                const baseEnd = isDateOnly(e.end) ? e.end
                                        : (typeof e.event_end_date === 'string' ? e.event_end_date : e.start);
                                e.end = addOneDayISODate(baseEnd);
                            }

                            return e;
                        });

                        successCallback(normalized);
                    }

                    setTimeout(function () {
                        jQuery('.ep-event-loader').hide();
                    }, 400);
                },
                error: function () {
                    failureCallback();
                }
            });
        },

        showNonCurrentDates: hide_calendar_rows,
        fixedWeekCount: hide_calendar_rows,
        nextDayThreshold: '00:00',

        // Custom label for dayGrid/timeGrid chips
        // For list view we'll not inject our own .fc-event-time here to avoid duplicates
        eventContent(arg) {
            const isList = arg.view && String(arg.view.type).indexOf('list') === 0;
            const titleEl = document.createElement('div');
            titleEl.className = 'fc-event-title';
            titleEl.textContent = arg.event.title || '';

            if (isList) {
                // Let list view keep its own time column; we'll override it in eventDidMount.
                return {domNodes: [titleEl]};
            }

            // dayGrid/timeGrid custom time label
            const s = arg.event.extendedProps.display_start_time || '';
            const e = arg.event.extendedProps.display_end_time || '';
            let timeLabel = '';
            if (arg.isStart && arg.isEnd && s && e)
                timeLabel = `${s} – ${e}`;
            else if (arg.isStart && s)
                timeLabel = s;
            else if (arg.isEnd && e)
                timeLabel = e;

            const timeEl = document.createElement('div');
            timeEl.className = 'fc-event-time';
            timeEl.textContent = timeLabel;

            return {domNodes: [timeEl, titleEl]};
        },

        titleFormat: {year: 'numeric', month: 'long', day: 'numeric'},
        dayHeaderFormat: {weekday: column_header_format},

        eventDidMount: function (info) {
            // --- colors ---
            let light_bg_color = '';
            if (info.event.extendedProps.hasOwnProperty('bg_color')) {
                var epColorRgb = info.event.extendedProps.bg_color; // "rgb( r,g,b )"
                var emColor_bg = epColorRgb.substring(epColorRgb.indexOf('(') + 1, epColorRgb.indexOf(')'));
                info.el.style.backgroundColor = `rgba(${emColor_bg},1)`;
                info.el.style.borderColor = `rgba(${emColor_bg},1)`;
                light_bg_color = info.el.style.backgroundColor;
            }

            var textColor = light_bg_color;
            if (info.event.extendedProps.type_text_color)
                textColor = info.event.extendedProps.type_text_color;
            if (info.event.extendedProps.event_text_color)
                textColor = info.event.extendedProps.event_text_color;

            // apply text colors
            ['.fc-event-time', '.fc-time', '.fc-event-title', '.fc-list-event-time', '.fc-list-item-title', '.fc-list-event-title', '.fc-list-event-dot']
                    .forEach(sel => {
                        const n = info.el.querySelector(sel);
                        if (n && textColor)
                            n.style.color = textColor;
                    });

            // Global hide
            if (em_front_event_object.hide_time_on_front_calendar == 1) {
                const a = info.el.querySelector('.fc-event-time');
                const b = info.el.querySelector('.fc-list-event-time');
                if (a)
                    a.style.display = 'none';
                if (b)
                    b.style.display = 'none';
            }

            // ⬇️ List view: override the time column label to avoid "00:00" and enforce 12h/24h preference
            const isList = info.view && String(info.view.type).indexOf('list') === 0;
            if (isList && em_front_event_object.hide_time_on_front_calendar != 1) {
                const nListTime = info.el.querySelector('.fc-list-event-time');
                if (nListTime) {
                    const s = info.event.extendedProps.display_start_time || '';
                    const e = info.event.extendedProps.display_end_time || '';
                    let label = '';
                    if (info.isStart && info.isEnd && s && e)
                        label = `${s} – ${e}`;
                    else if (info.isStart && s)
                        label = s;
                    else if (info.isEnd && e)
                        label = e;
                    else
                        label = ''; // middle segments → blank
                    nListTime.textContent = label;
                }
            }

            // append popup
            $(info.el).append(info.event.extendedProps.popup_html);
        },

        eventMouseEnter: function (info) {
            let pop_block = info.el.querySelector('.ep_event_detail_popup');
            if (pop_block)
                pop_block.style.display = 'block';
        },
        eventMouseLeave: function (info) {
            let pop_block = info.el.querySelector('.ep_event_detail_popup');
            if (pop_block)
                pop_block.style.display = 'none';
        },
        eventClick: function (info) {
            var eventObj = info.event;
            if (eventObj.url) {
                let open_event_in_new_tab = info.event.extendedProps.open_event_in_new_tab;
                if (open_event_in_new_tab == 1) {
                    window.open(eventObj.url);
                    info.jsEvent.preventDefault();
                }
            }
        },
    });

    calendar.render();

    let calAreaWidht = $("#ep-events-content-container").innerWidth();
    if (calAreaWidht <= 720) {
        calendar.setOption('dayHeaderFormat', {weekday: 'short'});
    } else {
        calendar.setOption('dayHeaderFormat', {weekday: column_header_format});
    }

    // toolbar title class
    let cal_title_class = 'ep-calendar-title-short';
    let calendar_title_format = "MMMM, YYYY";
    if (typeof eventprime.global_settings.calendar_title_format !== 'undefined') {
        calendar_title_format = eventprime.global_settings.calendar_title_format;
    }
    if (calendar_title_format.search('DD') > -1) {
        cal_title_class = 'ep-calendar-title-full';
    }
    $('#ep_event_calendar .fc-header-toolbar .fc-toolbar-title').addClass(cal_title_class);

    /* datepickers & filters below unchanged ... */

    $("#filter-date-from").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_to = $('#filter-date-to').val();
            if (date_to)
                $("#filter-date-from").datepicker("option", {maxDate: date_to});
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-from').click(function () {
        $("#filter-date-from").datepicker('show');
    });

    $("#filter-date-to").datepicker({
        dateFormat: eventprime.datepicker_format,
        beforeShow: function () {
            let date_from = $('#filter-date-from').val();
            if (date_from)
                $("#filter-date-to").datepicker("option", {minDate: date_from});
            $('#ui-datepicker-div').addClass('ep-ui-cal-wrap');
        },
    });
    $('.ep-trigger-date-to').click(function () {
        $("#filter-date-to").datepicker('show');
    });

    $('#ep-filter-venues, #ep-filter-types').select2();
    $('#ep-filter-org').select2({
        placeholder: em_front_event_object.organizers,
        multiple: true,
        allowClear: true
    });
    $('#ep-filter-performer').select2({
        placeholder: em_front_event_object.performers,
        multiple: true,
        allowClear: true
    });

    if ($('#ep-event-view-selector').length > 0) {
        $('#ep-event-view-selector, .ep-box-dropdown-overlay').click(function () {
            $('.ep-event-views-content').slideToggle();
            $('#ep-event-view-selector').toggleClass('ep-box-dropdown-open');
        });
    }

    $('.epDatePicker').datepicker({
        changeYear: true,
        changeMonth: true,
        dateFormat: eventprime.datepicker_format,
        gotoCurrent: true,
        showButtonPanel: true,
    });

    $("#ep_event_keyword_search").on('click', function () {
        $("#ep-search-filters").css("animation", "ep-searchfilters 1s forwards normal 1");
        $(".ep-search-filter-overlay").show();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-3").removeClass("ep-z-index-1");
    });

    $(".ep-search-filter-overlay").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
        jQuery("#ep_event_find_events_btn").addClass("ep-z-index-1").removeClass("ep-z-index-3");
    });
    $("#ep_filter_next_weekend, #ep_filter_next_week, #ep_filter_next_month, #ep_filter_online").on('click', function () {
        if ($("#ep-search-filters").css("visibility") === "visible") {
            $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
        }
        $(".ep-search-filter-overlay").hide();
    });

    epCalViewWidhth();

    // Image CSS vars
    var styleElement = jQuery("<style>");
    var styleContent = ":root {\n";
    styleContent += " --ep-imageCardObjectFit: " + (eventprime.global_settings.events_image_visibility_options || 'cover') + ";\n";
    styleContent += " --ep-imageCardHeight: " + (eventprime.global_settings.events_image_height ? eventprime.global_settings.events_image_height + "px" : "140px") + ";\n";
    styleContent += "}\n";
    styleElement.text(styleContent);
    jQuery("head").append(styleElement);
}

function ep_load_calendar_view_new3(search_param = '') {
    let default_view = 'dayGridMonth';
    const calendar_views = {
        'month': 'dayGridMonth',
        'week': 'dayGridWeek',
        'day': 'dayGridDay',
        'listweek': 'listWeek',
    };

    if (eventprime.global_settings.default_cal_view) {
        default_view = em_front_event_object.view
                ? calendar_views[em_front_event_object.view]
                : calendar_views[eventprime.global_settings.default_cal_view];
    }

    // Initial date
    let cal_initial_date = new Date();
    if (eventprime.global_settings.enable_default_calendar_date == 1 &&
            eventprime.global_settings.default_calendar_date) {
        cal_initial_date = eventprime.global_settings.default_calendar_date;
    }

    // Hide extra weeks
    const hide_calendar_rows = !(eventprime.global_settings.hide_calendar_rows == 1);

    // Toolbar buttons
    let right_views = [];
    if (eventprime.global_settings.front_switch_view_option) {
        const opt = eventprime.global_settings.front_switch_view_option;
        if (opt.includes('month'))
            right_views.push('dayGridMonth');
        if (opt.includes('week'))
            right_views.push('dayGridWeek');
        if (opt.includes('day'))
            right_views.push('dayGridDay');
        if (opt.includes('listweek'))
            right_views.push('listWeek');
    }
    right_views = right_views.join(',');

    // Date header format
    const column_header_format =
            (eventprime.global_settings.calendar_column_header_format === 'ddd') ? 'short' : 'long';

    // Max events per day
    const day_max_events = eventprime.global_settings.show_max_event_on_calendar_date || 2;

    // Time format (12h / 24h)
    const use24h = (eventprime.global_settings.time_format === 'HH:mm');

    const calendarEl = document.getElementById('ep_event_calendar');
    const args = jQuery('#ep_hidden_args').val()
            || (em_front_event_object.event_attributes
                    ? JSON.stringify(em_front_event_object.event_attributes)
                    : '');

    if (!calendarEl)
        return;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
            left: 'prevYear,prev,next,nextYear today',
            center: 'title',
            right: right_views
        },
        buttonText: {
            listWeek: em_front_event_object.list_week_btn_text,
        },
        initialDate: cal_initial_date,
        initialView: default_view,
        navLinks: true,
        dayMaxEvents: day_max_events,
        editable: false,
        timeZone: 'local',

        // Native formats for timeGrid/list
        eventTimeFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},
        slotLabelFormat: {hour: '2-digit', minute: '2-digit', hour12: !use24h},

        height: "auto",

        events: function (info, successCallback, failureCallback) {
            jQuery('.ep-event-loader').show();
            $.ajax({
                url: eventprime.ajaxurl,
                method: 'POST',
                data: {
                    action: 'ep_get_calendar_event',
                    security: em_front_event_object._nonce,
                    start: info.startStr,
                    end: info.endStr,
                    args: args,
                    search_param: search_param
                },
                success: function (data) {
                    if (data && data.data) {
                        const pad = n => String(n).padStart(2, '0');
                        const toISO = (dateStr, timeStr) => {
                            const d = new Date(`${dateStr} ${timeStr}`);
                            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}:00`;
                        };
                        const addOneDayISODate = (dateStr) => {
                            const d = new Date(`${dateStr}T00:00:00`);
                            d.setDate(d.getDate() + 1);
                            return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
                        };
                        const isDateOnly = (s) => /^\d{4}-\d{2}-\d{2}$/.test(s);

                        const normalized = data.data.map(e => {
                            e.allDay = (e.all_day === 1 || e.all_day === '1' || e.all_day === true);
                            e.hideStartTime = !e.start_time;
                            e.hideEndTime = !e.end_time;

                            if (isDateOnly(e.start) && !e.allDay && e.start_time)
                                e.start = toISO(e.start, e.start_time);
                            if (isDateOnly(e.end) && !e.allDay && e.end_time)
                                e.end = toISO(e.end, e.end_time);

                            if (e.allDay) {
                                if (!isDateOnly(e.start) && typeof e.event_start_date === 'string') {
                                    e.start = e.event_start_date;
                                }
                                const baseEnd = isDateOnly(e.end)
                                        ? e.end
                                        : (typeof e.event_end_date === 'string'
                                                ? e.event_end_date
                                                : e.start);
                                e.end = addOneDayISODate(baseEnd);
                            }
                            return e;
                        });

                        successCallback(normalized);
                    }
                    setTimeout(() => jQuery('.ep-event-loader').hide(), 400);
                },
                error: failureCallback
            });
        },

        showNonCurrentDates: hide_calendar_rows,
        fixedWeekCount: hide_calendar_rows,
        nextDayThreshold: '00:00',

        // ✅ Custom rendering for all non-list views
        eventContent(arg) {
            const isList = arg.view && String(arg.view.type).startsWith('list');
            const titleEl = document.createElement('div');
            titleEl.className = 'fc-event-title';
            titleEl.textContent = arg.event.title || '';

            if (isList)
                return {domNodes: [titleEl]}; // skip in list; handled in eventDidMount

            const s = arg.event.extendedProps.display_start_time || '';
            const e = arg.event.extendedProps.display_end_time || '';
            let timeLabel = '';
            if (arg.isStart && arg.isEnd && s && e)
                timeLabel = `${s} – ${e}`;
            else if (arg.isStart && s)
                timeLabel = s;
            else if (arg.isEnd && e)
                timeLabel = e;

            const timeEl = document.createElement('div');
            timeEl.className = 'fc-event-time';
            timeEl.textContent = timeLabel;

            return {domNodes: [timeEl, titleEl]};
        },

        titleFormat: {year: 'numeric', month: 'long', day: 'numeric'},
        dayHeaderFormat: {weekday: column_header_format},

        // ✅ Apply colors, fix list view times, and attach popup
        eventDidMount: function (info) {
            let light_bg_color = '';
            if (info.event.extendedProps.bg_color) {
                const rgb = info.event.extendedProps.bg_color;
                const color = rgb.substring(rgb.indexOf('(') + 1, rgb.indexOf(')'));
                info.el.style.backgroundColor = `rgba(${color},1)`;
                info.el.style.borderColor = `rgba(${color},1)`;
                light_bg_color = info.el.style.backgroundColor;
            }

            let textColor = light_bg_color;
            if (info.event.extendedProps.type_text_color)
                textColor = info.event.extendedProps.type_text_color;
            if (info.event.extendedProps.event_text_color)
                textColor = info.event.extendedProps.event_text_color;

            // Apply colors to all text
            [
                '.fc-event-time',
                '.fc-time',
                '.fc-event-title',
                '.fc-list-event-time',
                '.fc-list-item-title',
                '.fc-list-event-title',
                '.fc-list-event-dot'
            ].forEach(sel => {
                const el = info.el.querySelector(sel);
                if (el && textColor)
                    el.style.color = textColor;
            });

            // Hide times globally if set
            if (em_front_event_object.hide_time_on_front_calendar == 1) {
                info.el.querySelectorAll('.fc-event-time, .fc-list-event-time').forEach(n => n.style.display = 'none');
            }

            // ✅ Fix list view label
            const isList = info.view && String(info.view.type).startsWith('list');
            if (isList && em_front_event_object.hide_time_on_front_calendar != 1) {
                const timeNode = info.el.querySelector('.fc-list-event-time');
                if (timeNode) {
                    const s = info.event.extendedProps.display_start_time || '';
                    const e = info.event.extendedProps.display_end_time || '';
                    let label = '';
                    if (info.isStart && info.isEnd && s && e)
                        label = `${s} – ${e}`;
                    else if (info.isStart && s)
                        label = s;
                    else if (info.isEnd && e)
                        label = e;
                    timeNode.textContent = label;
                }
            }

            // Append popup
            $(info.el).append(info.event.extendedProps.popup_html);
        },

        // Popup behaviors
        eventMouseEnter(info) {
            const pop = info.el.querySelector('.ep_event_detail_popup');
            if (pop)
                pop.style.display = 'block';
        },
        eventMouseLeave(info) {
            const pop = info.el.querySelector('.ep_event_detail_popup');
            if (pop)
                pop.style.display = 'none';
        },
        eventClick(info) {
            if (info.event.url) {
                if (info.event.extendedProps.open_event_in_new_tab == 1) {
                    window.open(info.event.url);
                    info.jsEvent.preventDefault();
                }
            }
        },
    });

    calendar.render();

    // Responsive header
    const width = $("#ep-events-content-container").innerWidth();
    calendar.setOption('dayHeaderFormat', {weekday: width <= 720 ? 'short' : column_header_format});

    // Calendar title styling
    const cal_title_class = (eventprime.global_settings.calendar_title_format || '').includes('DD')
            ? 'ep-calendar-title-full'
            : 'ep-calendar-title-short';
    $('#ep_event_calendar .fc-header-toolbar .fc-toolbar-title').addClass(cal_title_class);

    // (Keep your datepicker/filter code unchanged below)
}

function ep_load_calendar_view_old(search_param='')
{
    
        // set initial view
        let default_view = 'dayGridMonth';
        let calendar_views = {
            'month':    'dayGridMonth',
            'week':     'dayGridWeek',
            'day':      'dayGridDay',
            'listweek': 'listWeek',
        };
        if( eventprime.global_settings.default_cal_view ) {
            if( em_front_event_object.view ) {
                default_view = calendar_views[em_front_event_object.view];    
            } else{
                default_view = calendar_views[eventprime.global_settings.default_cal_view];
            }
        }
        // set initial date
        let cal_initial_date = new Date();
        if( eventprime.global_settings.enable_default_calendar_date == 1 ) {
            if( eventprime.global_settings.default_calendar_date && eventprime.global_settings.default_calendar_date != '' ) {
                cal_initial_date = eventprime.global_settings.default_calendar_date;
            }
        }
        // hide prev and next month rows
        let hide_calendar_rows = true;
        if(eventprime.global_settings.hide_calendar_rows == 1){
            hide_calendar_rows = false;
        }
        // set calendar right view options
        let right_views = [];
        if( eventprime.global_settings.front_switch_view_option ) {
            if( eventprime.global_settings.front_switch_view_option.indexOf( 'month' ) > -1 ) {
                right_views.push( 'dayGridMonth' );
            }
            if( eventprime.global_settings.front_switch_view_option.indexOf( 'week' ) > -1 ) {
                right_views.push( 'dayGridWeek' );
            }
            if( eventprime.global_settings.front_switch_view_option.indexOf( 'day' ) > -1 ) {
                right_views.push( 'dayGridDay' );
            }
            if( eventprime.global_settings.front_switch_view_option.indexOf( 'listweek' ) > -1 ) {
                right_views.push( 'listWeek' );
            }
        }
        if( right_views && right_views.length > 0 ) {
            right_views = right_views.toString();
        } else{
            right_views = '';
        }
        // set column header format
        let calendar_column_header_format = eventprime.global_settings.calendar_column_header_format;
        let column_header_format = 'long';
        if( calendar_column_header_format == 'ddd' ) {
            column_header_format = 'short';
        }
        // set day max events
        let day_max_events = eventprime.global_settings.show_max_event_on_calendar_date;
        if( !day_max_events ) {
            day_max_events = 2;
        }
        // set 12 and 24 hours
        let hour12 = true;
        if( eventprime.global_settings.time_format == 'HH:mm' ){
            hour12 = false;
        }
        var calendarEl = document.getElementById( 'ep_event_calendar' );
        var element = jQuery('#ep_hidden_args' ).val();
        // if (element !== null) {
        if (element) {
            var args = element;
            // Do something with args
        } else if ( em_front_event_object.event_attributes ) {
            var args = JSON.stringify(em_front_event_object.event_attributes); 
        } else {
            var args = '';
        }
        
        if( calendarEl ) {
            //console.log(eventprime.timezone)
            var calendar = new FullCalendar.Calendar( calendarEl, {
                headerToolbar: {
                    left: 'prevYear,prev,next,nextYear today',
                    center: 'title',
                    right: right_views
                },
                // views: {
                //     listWeek: { buttonText: 'Agenda' }
                // },
                buttonText: {
                    listWeek: em_front_event_object.list_week_btn_text,  
                },
                initialDate: cal_initial_date,
                initialView: default_view,
                navLinks: true, // can click day/week names to navigate views
                dayMaxEvents: day_max_events, // allow "more" link when too many events
                editable: false,
                timeZone:eventprime.timezone,
                height: "auto",
                events: function(info, successCallback, failureCallback) {
        // Calculate the start and end dates of the current month
        jQuery('.ep-event-loader').show();
        $.ajax({
            url: eventprime.ajaxurl, // Replace with your events API endpoint
            method: 'POST',
            data: {
                action:'ep_get_calendar_event',
                security  : em_front_event_object._nonce,
                start: info.startStr,
                end: info.endStr,
                args: args,
                search_param:search_param
                        
            },
            success: function(data) {
                
                
                // clearTimeout(hideLoaderTimeout);
                /*
                if(data.data)
                {
                    successCallback(data.data);
                }
                
                /*
 * ⚠️ Important Note:
 * FullCalendar treats events differently depending on whether the "end" field
 * has a time or is just a date string. 
 *
 * Problem:
 * - For timed events, if "end" is passed as a plain date (YYYY-MM-DD) without time,
 *   FullCalendar interprets it as an all-day or exclusive end, causing the event 
 *   to appear on the wrong day (spilling into two days).
 *
 * Fix:
 * - For timed events, always convert start/end into full ISO datetime strings.
 * - For true all-day events, keep date-only but make the "end" exclusive 
 *   (next day at 00:00) as per FullCalendar’s requirement.
 *
 * Example:
 *   Bad → start: "2025-08-25 23:40", end: "2025-08-25"
 *   Good → start: "2025-08-25T23:40:00", end: "2025-08-25T23:45:00"
 *
 * This normalization ensures one-day events are rendered correctly 
 * and prevents them from showing on multiple days.
 */
               
               
                if (data.data) {
                    const pad = n => String(n).padStart(2, '0');

                    const toISO = (dateStr, timeStr) => {
                        // timeStr like "11:45 PM" or "12:00 AM"
                        const d = new Date(`${dateStr} ${timeStr}`);
                        const y = d.getFullYear();
                        const m = pad(d.getMonth() + 1);
                        const da = pad(d.getDate());
                        const h = pad(d.getHours());
                        const mi = pad(d.getMinutes());
                        return `${y}-${m}-${da}T${h}:${mi}:00`;
                    };

                    const addOneDayISODate = (dateStr) => {
                        const d = new Date(`${dateStr}T00:00:00`);
                        d.setDate(d.getDate() + 1);
                        return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;
                    };

                    const isDateOnly = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);

                    const normalized = data.data.map(e => {
                        // Map WP field to FullCalendar
                        e.allDay = (e.all_day === 1 || e.all_day === '1' || e.all_day === true);
                        e.hideStartTime = !e.start_time; // true if start_time is blank
                        e.hideEndTime   = !e.end_time;   // true if end_time is blank
                        // START: add time if missing for timed events
                        if (isDateOnly(e.start) && !e.allDay && e.start_time) {
                            e.start = toISO(e.start, e.start_time);
                        }
                        // END: add time if missing for timed events
                        if (isDateOnly(e.end) && !e.allDay && e.end_time) {
                            e.end = toISO(e.end, e.end_time);
                        }

                        // For true all-day events, make end exclusive (next day)
                        if (e.allDay) {
                            // Ensure start is date-only for all-day
                            if (!isDateOnly(e.start) && typeof e.event_start_date === 'string') {
                                e.start = e.event_start_date; // YYYY-MM-DD
                            }
                            // Ensure end is exclusive date-only
                            const baseEnd = isDateOnly(e.end) ? e.end
                                          : (typeof e.event_end_date === 'string' ? e.event_end_date : e.start);
                            e.end = addOneDayISODate(baseEnd);
                        }

                        return e;
                    });

                    successCallback(normalized);
                }

                setTimeout(function() {
                        jQuery('.ep-event-loader').hide();
                    }, 400);
                // jQuery('.ep-event-loader').hide();
            },
            error: function() {
                failureCallback();
            }
        });
    },
                
                showNonCurrentDates: hide_calendar_rows,
                fixedWeekCount: hide_calendar_rows,
                nextDayThreshold: '00:00',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: hour12,
                    meridiem: 'short'
                },
                firstDay: em_front_event_object.start_of_week,
                locale: em_front_event_object.local,
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
                dayHeaderFormat: { weekday: column_header_format },
                eventDidMount: function(info) {
                    let light_bg_color = '';
                    if (info.event.extendedProps.hasOwnProperty('bg_color')) {
                        var epColorRgb = info.event.extendedProps.bg_color;
                        var avoid = "rgb";
                        var eprgbRemover = epColorRgb.replace(avoid, '');
                        var emColor_bg = eprgbRemover.substring(eprgbRemover.indexOf('(') + 1, eprgbRemover.indexOf(')'))
                        info.el.style.backgroundColor =  `rgba(${emColor_bg},1)`;
                        light_bg_color = info.el.style.backgroundColor;
                        info.el.style.borderColor =  `rgba(${emColor_bg},1)`;
                    }
                    var textColor = light_bg_color;
                    if ( info.event.extendedProps.hasOwnProperty( 'type_text_color' ) ) {
                        textColor = info.event.extendedProps.type_text_color;
                    }
                    if ( info.event.extendedProps.hasOwnProperty( 'event_text_color' ) ) {
                        textColor = info.event.extendedProps.event_text_color;
                    }
                    
                    var fc_time = info.el.querySelector('.fc-event-time'); 
                    if(fc_time){
                        fc_time.style.color = textColor;
                        if( em_front_event_object.hide_time_on_front_calendar == 1 ) {
                            fc_time.textContent = '';
                            fc_time.style.color = '';
                        }
                    }
                    
                    if(textColor){
                        var fc_time = info.el.querySelector('.fc-time');
                        if(fc_time){
                            fc_time.style.color = textColor;
                            if( em_front_event_object.hide_time_on_front_calendar == 1 ) {
                                fc_time.textContent = '';
                                fc_time.style.color = '';
                            }
                        }
                        var fc_title = info.el.querySelector('.fc-event-title');
                        if(fc_title){
                            fc_title.style.color = textColor;
                        }
                        var fc_list_time = info.el.querySelector('.fc-event-time');
                        if(fc_list_time){
                            fc_list_time.style.color = textColor;
                        }
                        var fc_list_title = info.el.querySelector('.fc-list-item-title');
                        if( fc_list_title ) {
                            fc_list_title.style.color = textColor;
                        }
                        var fc_list_event_time = info.el.querySelector('.fc-list-event-time');
                        if( fc_list_event_time ) {
                            fc_list_event_time.style.color = textColor;
                        }
                        var fc_list_event_dot = info.el.querySelector('.fc-list-event-dot');
                        if( fc_list_event_dot ) {
                            fc_list_event_dot.style.color = textColor;
                        }
                        var fc_list_event_title = info.el.querySelector('.fc-list-event-title');
                        if( fc_list_event_title ) {
                            fc_list_event_title.style.color = textColor;
                        }
                    }
                    
                      // --- per-event time hiding/rewriting ---
                        const { hideStartTime, hideEndTime } = info.event.extendedProps || {};
                        const hour12 = (eventprime.global_settings.time_format !== 'HH:mm');

                        // format helper
                        const fmt = d => {
                          if (!d) return '';
                          try {
                            return new Intl.DateTimeFormat(em_admin_calendar_event_object.local, {
                              hour: '2-digit', minute: '2-digit', hour12
                            }).format(d);
                          } catch (e) {
                            // fallback
                            const pad = n => String(n).padStart(2,'0');
                            return d.getHours() + ':' + pad(d.getMinutes());
                          }
                        };

                        // nodes present across views (dayGrid/timeGrid/list)
                        const nDayTime   = info.el.querySelector('.fc-event-time');       // dayGrid/timeGrid
                        const nListTime  = info.el.querySelector('.fc-list-event-time');  // list
                        // In some themes, dayGrid uses '.fc-time' alias as well:
                        const nAltTime   = info.el.querySelector('.fc-time');

                        // Build the desired time label
                        const startText = hideStartTime ? '' : fmt(info.event.start);
                        // For end: if end is null, we won't show anything anyway
                        const endText   = hideEndTime   ? '' : fmt(info.event.end);

                        let label = '';
                        if (startText && endText) {
                          label = startText + ' – ' + endText;
                        } else if (startText && !endText) {
                          label = startText;                 // show only start
                        } else if (!startText && endText) {
                          label = endText;                   // show only end
                        } else {
                          label = '';                        // both hidden → no label
                        }

                        // Apply the label (or hide node completely)
                        const applyLabel = node => {
                          if (!node) return;
                          if (label) {
                            node.textContent = label;
                            node.style.display = '';
                          } else {
                            node.textContent = '';
                           // node.style.display = 'none';
                          }
                        };

                        applyLabel(nDayTime);
                        applyLabel(nAltTime);
                        applyLabel(nListTime);

                        // If you *also* globally hide times via a setting, keep your existing guard:
                        if (em_front_event_object.hide_time_on_front_calendar == 1) {
                          if (nDayTime)  nDayTime.style.display = 'none';
                          if (nAltTime)  nAltTime.style.display = 'none';
                          if (nListTime) nListTime.style.display = 'none';
                        }
                     // --- per-event time hiding/rewriting end---
                     
                    
                    if( em_front_event_object.hide_time_on_front_calendar == 1 ) {
                        
                        $(info.el).find('.fc-event-time').hide();
                        $(info.el).find('.fc-list-event-time').hide();
                        
                    }
                    $( info.el ).append( info.event.extendedProps.popup_html );
                },
                eventMouseEnter: function( info ) {
                    let pop_block = info.el.querySelector( '.ep_event_detail_popup' );
                    pop_block.style.display = 'block';
                },
                eventMouseLeave: function( info ) {
                    let pop_block = info.el.querySelector( '.ep_event_detail_popup' );
                    pop_block.style.display = 'none';
                },
                eventClick: function( info ) {
                    var eventObj = info.event;
                    if ( eventObj.url ) {
                        let open_event_in_new_tab = info.event.extendedProps.open_event_in_new_tab;
                        if( open_event_in_new_tab == 1 ) {
                            window.open( eventObj.url );
                            info.jsEvent.preventDefault();
                        }
                    }
                },
            });
        
            calendar.render();

            let calAreaWidht = $( "#ep-events-content-container" ).innerWidth();
            if( calAreaWidht <= 720 ) {
                calendar.setOption( 'dayHeaderFormat', { weekday: 'short' } );
            } else{
                calendar.setOption( 'dayHeaderFormat', { weekday: column_header_format } );
            }

            // custom class on the toolbal title
            let cal_title_class = 'ep-calendar-title-short';

            let calendar_title_format = "MMMM, YYYY";
            if (  typeof eventprime.global_settings.calendar_title_format !== 'undefined' ) { 
                calendar_title_format = eventprime.global_settings.calendar_title_format;
            }

            if( calendar_title_format.search( 'DD' ) > -1 ) {
                cal_title_class = 'ep-calendar-title-full';
            }
            $( '#ep_event_calendar .fc-header-toolbar .fc-toolbar-title').addClass( cal_title_class );
        }
        
        /*
         * Select Filter Box
         */
        $( "#filter-date-from" ).datepicker({
            dateFormat: eventprime.datepicker_format,
            beforeShow: function () {
                let date_to = $( '#filter-date-to' ).val();
                if( date_to ) {
                    $( "#filter-date-from" ).datepicker("option", {
                        maxDate: date_to // set max date to to date
                    });
                }
                 $('#ui-datepicker-div').addClass('ep-ui-cal-wrap' );
            },
        });
        $('.ep-trigger-date-from').click(function(e){
            $("#filter-date-from").datepicker('show');
        });
        $( "#filter-date-to" ).datepicker({
            dateFormat: eventprime.datepicker_format,
            beforeShow: function () {
                let date_from = $( '#filter-date-from' ).val();
                if( date_from ) {
                    $( "#filter-date-to" ).datepicker("option", {
                        minDate: date_from // set min date to from date
                    });
                }
                $('#ui-datepicker-div').addClass('ep-ui-cal-wrap' );
            },
        });
        $('.ep-trigger-date-to').click(function(e){
            $("#filter-date-to").datepicker('show');
        });
        
        $('#ep-filter-venues, #ep-filter-types').select2();
        $('#ep-filter-org').select2({
            placeholder: em_front_event_object.organizers,
            multiple: true,
            //templateSelection: set_organizers_query,
            allowClear: true
        });
        $('#ep-filter-performer').select2({
            placeholder: em_front_event_object.performers,
            multiple: true,
            //templateSelection: set_performers_query,
            allowClear: true
        });
        
        // event filter list toggle
        if( $( '#ep-event-view-selector' ).length > 0 ) {
            $( '#ep-event-view-selector, .ep-box-dropdown-overlay' ).click( function(){
                $( '.ep-event-views-content' ).slideToggle();
                $( '#ep-event-view-selector' ).toggleClass( 'ep-box-dropdown-open' );
            });
        }
        
        //datepicker
        $( '.epDatePicker' ).datepicker({
            changeYear: true,
            changeMonth: true,
            dateFormat: eventprime.datepicker_format,
            gotoCurrent: true,
            showButtonPanel: true,
        });

        $("#ep_event_keyword_search").on('click', function(){
            $("#ep-search-filters").css("animation", "ep-searchfilters 1s forwards normal 1");
            $(".ep-search-filter-overlay").show();
             jQuery("#ep_event_find_events_btn").addClass("ep-z-index-3").removeClass("ep-z-index-1");
        });
        
        $(".ep-search-filter-overlay").on('click',function(){
            if ($("#ep-search-filters").css("visibility") === "visible") {
                $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
            }
            $(".ep-search-filter-overlay").hide();
            jQuery("#ep_event_find_events_btn").addClass("ep-z-index-1").removeClass("ep-z-index-3");
        });
        $("#ep_filter_next_weekend, #ep_filter_next_week, #ep_filter_next_month, #ep_filter_online").on('click',function(){
            if ($("#ep-search-filters").css("visibility") === "visible") {
                $("#ep-search-filters").css("animation", "ep-searchfilters-exit 1s forwards normal 1");
            }
            $(".ep-search-filter-overlay").hide();
        });


        /* let ep_mcontainer = document.querySelector( '#ep_events_front_views_staggered_grid' );
        if ( ep_mcontainer ) {
            let msnry;
            imagesLoaded( ep_mcontainer, function() {
                msnry = new Masonry( ep_mcontainer, {
                    itemSelector: '#ep_events_front_views_staggered_grid .ep-event-card',
                });
            });
        } */

        epCalViewWidhth();

    ///  Event square card Image Setting start
                
    var styleElement = jQuery("<style>");

    // Set the text content to include dynamic variables in :root
    var styleContent = ":root {\n";

    if(eventprime.global_settings.events_image_visibility_options){
       styleContent += " --ep-imageCardObjectFit: " + eventprime.global_settings.events_image_visibility_options +  ";\n";
    }   else{
        styleContent += " --ep-imageCardObjectFit: " + 'cover' + ";\n"; 
    }
    if(eventprime.global_settings.events_image_height){
        styleContent += "  --ep-imageCardHeight: " + eventprime.global_settings.events_image_height + "px" + ";\n";
    }
    else{
        styleContent += " --ep-imageCardHeight: " + '140' + "px" + ";\n"; 
    }

    styleContent += "}\n";
    styleElement.text(styleContent);

    //  Append the <style> element to the <head> of the document
     jQuery("head").append(styleElement);

    //   Event square card Image Setting end
}


