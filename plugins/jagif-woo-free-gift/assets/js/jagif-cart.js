jQuery(document).ready(function ($) {
    "use strict";
    jagif_cart_init();

    $( document.body ).on( 'wc_fragments_refreshed', function() {
        jagif_cart_init();
    });


});

function jagif_cart_init() {
    jQuery.each(jQuery('.jagif-cart-item.jagif-cart-child'), function (key, value) {
        if (jQuery(value).find('.product-name>.jagif-cart-change-variation').length) {
            if (jQuery(value).find('.variation').length) {
                let $edit = jQuery(value).find('.product-name>.jagif-cart-change-variation').prop('outerHTML');
                jQuery(value).find('.product-name>.jagif-cart-change-variation').remove();
                jQuery(value).find('.variation').append('<dt>' + $edit + '</dt>');
            } else {
            }
        }
    })
}