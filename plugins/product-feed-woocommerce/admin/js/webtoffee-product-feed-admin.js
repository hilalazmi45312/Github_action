
/**
 * All of the code for your admin-facing JavaScript source
 * should reside in this file.
 *
 * Note: It has been assumed you will write jQuery code here, so the
 * $ function reference has been prepared for usage within the scope
 * of this function.
 *
 * This enables you to define handlers, for when the DOM is ready:
 *
 * $(function() {
 *
 * });
 *
 * When the window is loaded:
 *
 * $( window ).load(function() {
 *
 * });
 *
 * ...and/or other possibilities.
 *
 * Ideally, it is not considered best practise to attach more than a
 * single DOM-ready or window-load handler for a particular page.
 * Although scripts in the WordPress core, Plugins and Themes may be
 * practising this, we should strive to set a better example in our own work.
 */

jQuery(function ()
{


    var process_step = function (step, data) {

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                form: data,
                action: 'wt_fbfeed_ajax_upload',
                step: step,
            },
            dataType: "json",
            success: function (response) {
                if ('done' == response.step) {

                    var export_form = jQuery('.edd-export-form');

                    export_form.find('.spinner').remove();
                    export_form.find('.edd-progress').remove();

                    jQuery('#example-basic-p-2').html('<div class="updated notice"><p>Product sync completed.</p></div><br/><div><a target="_blank" class="button" style="margin-left:15px;background:#1877f2;border-color:#1877f2;color:#fff;" href="' + response.catalog + '">Check FB Catalog</a></div>');
                    /*
                     setTimeout(function() {
                     window.location= response.url;
                     }, 30000);
                     */

                    // window.location = response.url;

                } else {

                    jQuery('.edd-progress div').animate({
                        width: response.percentage + '%',
                    }, 60, function () {
                        // Animation complete.
                    });

                    jQuery('#found-product-count').html(response.products + ' products(including variations) found.');
                    process_step(parseInt(response.step), data);
                }

            }
        }).fail(function (response) {
            if (window.console && window.console.log) {
                console.log(response);
            }
        });

    }

    jQuery("#example-basic").steps({
        headerTag: "h3",
        bodyTag: "section",
        transitionEffect: "slideLeft",
        autoFocus: true,
        titleTemplate: "<span class=\"number\">#index#</span>",
        labels: {
            finish: wt_feed_params.msgs.sync_now,
            previous: wt_feed_params.msgs.back,
            next: wt_feed_params.msgs.next,
            loading: wt_feed_params.msgs.loading
        },

        onStepChanging: function (event, currentIndex, newIndex) {


            if (currentIndex == 1 && newIndex == 2) {


            }
            return true;
        },
        onFinishing: function (event, currentIndex) {

            var data = jQuery('.category-mapping-form').serialize();
            jQuery('.actions > ul > li:last-child').attr('style', 'display:none');
            jQuery('.actions > ul > li:first-child').attr('style', 'display:none');
            jQuery('#example-basic-p-2').html('<p>' + wt_feed_params.msgs.process + '<span id="found-product-count" style="color:#42b72a"></span></p><span class="spinner is-active" style="margin-top:-30px;"></span><div class="edd-progress"><div>');

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    form: data,
                    action: 'wt_fbfeed_ajax_save_category',

                },
                dataType: "json",
                success: function (response) {

                    if ('done' == response.step) {
                        var product_data = jQuery('.sync_products, .sync_products_gen').serialize();
                        process_step(1, product_data);

                    }

                }
            }).fail(function (response) {
                if (window.console && window.console.log) {
                    console.log(response);
                }
            });

        },

        onStepChanged: function (event, currentIndex, priorIndex)
        {
            if (currentIndex == 1) {
                //jQuery('.actions > ul > li:last-child a').css('background-color', '#f89406');
            }

            if (currentIndex == 2) {

                var saveA = jQuery("<a>").attr("href", "#").addClass("saveBtn").text(wt_feed_params.msgs.sync_schedule);
                var saveBtn = jQuery("<li>").attr("aria-disabled", false).append(saveA);

                jQuery(document).find(".actions ul").append(saveBtn);
            } else {
                jQuery(".actions").find(".saveBtn").remove();
            }

        }


    });
    //jQuery('ul[role="tablist"]').hide();
    jQuery(".actions").on("click", ".saveBtn", function (e) {
        e.preventDefault();

        jQuery('#example-basic > .content').css('opacity', '0.4');
        jQuery('.wt_pf_schedule_sync_block_main').show();
    });

});

jQuery(document).ready(function () {


    jQuery('select.wt_sync_schedule_interval').change(function () {
        var selected_interval = jQuery(this).val();
        if ('weekly' == selected_interval) {
            jQuery('.wt_pf_schedule_sync_weekdays').show();
        } else {
            jQuery('.wt_pf_schedule_sync_weekdays').hide();
        }
    });

    jQuery('#schedule_procceed').on('click', function () {
        var product_data = jQuery('.sync_products, .sync_products_gen, .sync_products_schedule').serialize();


        jQuery.ajax({
            url: ajaxurl,
            data: {'action': 'wt_fbfeed_set_auto_upload', 'form': product_data},
            type: 'post',
            dataType: "json",
            success: function (data)
            {

                if (data.status == 'finished')
                {
                    wt_fbfeed_notify_msg.success('Success');
                } else
                {
                    jQuery('.wt_fbfeed_log_loader').html("err");
                    wt_fbfeed_notify_msg.error("err");
                }
            },
            error: function ()
            {
                jQuery('.wt_fbfeed_log_loader').html("err");
                wt_fbfeed_notify_msg.error("err");
            }
        });

    });

    jQuery('#schedule_cancel').on('click', function () {
        jQuery('.wt_pf_schedule_sync_block_main').hide();
        jQuery('#example-basic > .content').css('opacity', '1');
    });
    jQuery('.productfeed-schedule-modal-cancel').on('click', function () {
        jQuery('.wt_pf_schedule_sync_block_main').hide();
        jQuery('#example-basic > .content').css('opacity', '1');
    });


    jQuery('#wt_sync_enable_autosync').on('click', function () {
        jQuery('.wt_pf_schedule_sync_block').toggle();
    });
    jQuery('.wt_pf_delete_fbsync').on('click', function () {
        if (confirm(wt_pf_basic_params.msgs.sure))
        {
            window.location.href = jQuery(this).attr('data-href');
        }
    });

    jQuery(".wt-pf-tips").tipTip({'attribute': 'data-wt-pf-tip'});
    jQuery("#sync-loader").hide();
    jQuery(".sync-product-tab").show();

    var process_step = function (step, data) {

        jQuery.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                form: data,
                action: 'wt_fbfeed_ajax_upload',
                step: step,
            },
            dataType: "json",
            success: function (response) {
                if ('done' == response.step) {

                    var export_form = jQuery('.edd-export-form');

                    export_form.find('.spinner').remove();
                    export_form.find('.edd-progress').remove();

                    window.location = response.url;

                } else {

                    jQuery('.edd-progress div').animate({
                        width: response.percentage + '%',
                    }, 50, function () {
                        // Animation complete.
                    });
                    process_step(parseInt(response.step), data);
                }

            }
        }).fail(function (response) {
            if (window.console && window.console.log) {
                console.log(response);
            }
        });

    }
    jQuery('#sync_products').on('submit', function (e) {
        e.preventDefault();
        jQuery(':input[type="submit"]').prop('disabled', true);
        var data = jQuery(this).serialize();

        jQuery(this).append('<p>' + wt_feed_params.msgs.process + '</p><span class="spinner is-active" style="margin-bottom:15px;"></span><div class="edd-progress"><div></div></div>');

        // start the process
        process_step(1, data);

    });

    jQuery('.wt_fbfeed_popup_close, .wt_fbfeed_popup_cancel').off('click').on('click', function () {
        jQuery('.wt_fbfeed_overlay, .wt_fbfeed_popup').hide();
    });

});

var wt_fbfeed_history = (function ($) {
    //'use strict';
    var wt_fbfeed_history =
            {
                log_offset: 0,
                Set: function ()
                {
                    this.reg_view_log();

                },
                reg_view_log: function ()
                {
                    jQuery('.wt_fbfeed_view_log_btn').on('click', function () {
                        wt_fbfeed_history.show_log_popup();
                        var batch_handle = $(this).attr('data-batch-handle');
                        var batch_handle_catalog = $(this).attr('data-batch-handle-catalog');
                        wt_fbfeed_history.view_raw_log(batch_handle, batch_handle_catalog);

                    });
                },
                view_raw_log: function (batch_handle, batch_handle_catalog)
                {
                    $('.wt_fbfeed_log_container').html('<div class="wt_fbfeed_log_loader">' + wt_feed_params.msgs.loading + '</div>');
                    $.ajax({
                        url: ajaxurl,
                        data: {'action': 'fbfeed_batch_status_ajax', 'batch_handle': batch_handle, 'catalog_id': batch_handle_catalog, _wpnonce: wt_feed_params.nonces.main, },
                        type: 'post',
                        dataType: "json",
                        success: function (data)
                        {

                            if (data.status == 'finished')
                            {

                                var badge = '';
                                for (var key in data.errors) {

                                    var title = data.errors[key].id;
                                    var desc = data.errors[key].message;

                                    badge += '<p>' + title + '</p>' + '<p>' + desc + '</p><br/>';


                                }
                                /*
                                 for ( var key in data.ids_of_invalid_requests ) {
                                 
                                 var invalid_product_id = data.ids_of_invalid_requests[key];
                                 
                                 
                                 badge += '<p>' + invalid_product_id + '</p>' + '<br/>';
                                 
                                 
                                 }
                                 */

                                $('.wt_fbfeed_log_container').html(badge);


                            } else
                            {
                                $('.wt_fbfeed_log_loader').html("err");
                                wt_fbfeed_notify_msg.error("err");
                            }
                        },
                        error: function ()
                        {
                            $('.wt_fbfeed_log_loader').html("err");
                            wt_fbfeed_notify_msg.error("err");
                        }
                    });
                },
                show_log_popup: function ()
                {
                    var pop_elm = $('.wt_fbfeed_view_log');
                    var ww = $(window).width();
                    pop_w = (ww < 1300 ? ww : 1300) - 200;
                    pop_w = (pop_w < 200 ? 200 : pop_w);
                    pop_elm.width(pop_w);

                    wh = $(window).height();
                    pop_h = (wh >= 400 ? (wh - 200) : wh);
                    $('.wt_fbfeed_log_container').css({'max-height': pop_h + 'px', 'overflow': 'auto'});
                    wt_fbfeed_popup.showPopup(pop_elm);
                }
            }
    return wt_fbfeed_history;

})(jQuery);

var wt_fbfeed_popup = {
    Set: function ()
    {
        this.regPopupOpen();
        this.regPopupClose();
        jQuery('body').prepend('<div class="wt_fbfeed_overlay"></div>');
    },
    regPopupOpen: function ()
    {
        jQuery('[data-wt_fbfeed_popup]').on('click', function () {
            var elm_class = jQuery(this).attr('data-wt_fbfeed_popup');
            var elm = jQuery('.' + elm_class);
            if (elm.length > 0)
            {
                wt_fbfeed_popup.showPopup(elm);
            }
        });
    },
    showPopup: function (popup_elm)
    {
        var pw = popup_elm.outerWidth();
        var wh = jQuery(window).height();
        var ph = wh - 150;
        popup_elm.css({'margin-left': ((pw / 2) * -1), 'display': 'block', 'top': '20px'}).animate({'top': '50px'});
        popup_elm.find('.wt_fbfeed_popup_body').css({'max-height': ph + 'px', 'overflow': 'auto'});
        jQuery('.wt_fbfeed_overlay').show();
    },
    hidePopup: function ()
    {
        jQuery('.wt_fbfeed_popup_close').trigger('click');
    },
    regPopupClose: function (popup_elm)
    {
        jQuery(document).on('keyup', function (e) {
            if (e.keyCode == 27)
            {
                wt_fbfeed_popup.hidePopup();
            }
        });
        jQuery('.wt_fbfeed_popup_close, .wt_fbfeed_popup_cancel').off('click').on('click', function () {
            jQuery('.wt_fbfeed_overlay, .wt_fbfeed_popup').hide();
        });
    }
}

var wt_fbfeed_notify_msg =
        {
            error: function (message)
            {
                var er_elm = jQuery('<div class="wt_notify_msg" style="background:#f8d7da; border:solid 1px #dd431c;">' + message + '</div>');
                this.setNotify(er_elm);
            },
            success: function (message)
            {
                var suss_elm = jQuery('<div class="wt_notify_msg" style="background:rgb(212, 237, 218); border:1px solid rgb(195, 230, 203);color:#000;">' + message + '</div>');
                this.setNotify(suss_elm);
            },
            setNotify: function (elm)
            {
                jQuery('body').append(elm);

                jQuery('.wt_notify_msg').on('click', function () {
                    jQuery(this).remove();
                });
                elm.stop(true, true).animate({'opacity': 1, 'top': '50px'}, 1000);
                setTimeout(function () {
                    elm.animate({'opacity': 0, 'top': '100px'}, 1000, function () {
                        elm.remove();
                    });
                }, 3000);
            }
        }


jQuery(function () {
    wt_fbfeed_history.Set();
});
