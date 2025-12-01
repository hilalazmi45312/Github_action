(function () {
    'use strict';
    wp.customize.bind('preview-ready', function () {
        let icon_custom = jagif_preview.icon_image,
            icon_default = jagif_preview.icon_default;
        wp.customize.preview.bind('jagif_update_url', function (url) {
            wp.customize.preview.send('jagif_update_url', url);
        });
        wp.customize.preview.bind('jagif_pg_toggle', function (action, new_effect) {
            jagif_pg_toggle(action, new_effect);
        });
        wp.customize.preview.bind('active', function () {
            jQuery('.jagif-popup-gift-overlay, .jagif-popup-gift-close-wrap').on('click', function () {
                jagif_pg_toggle('hide');
            });
            jQuery('.jagif-popup-gift-icon-wrap').on('mouseenter', function () {
                if (jQuery(this).hasClass('jagif-popup-gift-icon-wrap-click')) {
                    jQuery(this).removeClass('jagif-popup-gift-icon-wrap-mouseleave').addClass('jagif-popup-gift-icon-wrap-mouseenter');
                } else {
                    jagif_pg_toggle('show');
                }
            }).on('mouseleave', function () {
                if (jQuery(this).hasClass('jagif-popup-gift-icon-wrap-mouseenter')) {
                    jQuery(this).removeClass('jagif-popup-gift-icon-wrap-mouseenter').addClass('jagif-popup-gift-icon-wrap-mouseleave');
                }
            }).on('click', function () {
                if (jQuery(this).hasClass('jagif-popup-gift-icon-wrap-click')) {
                    jagif_pg_toggle('show');
                }
            });
        });

        wp.customize('jagif_woo_free_gift_params[gb_display_style]', function (value) {
            value.bind(function (newval) {
                if (newval == 0) {
                    jagif_pg_toggle('hide');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').removeClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').addClass('jagif-disabled');
                } else if (newval == 1) {
                    jagif_pg_toggle('hide');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').removeClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').addClass('jagif-disabled');
                } else if (newval == 2) {
                    jagif_pg_toggle('show');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').removeClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').addClass('jagif-disabled');
                } else if (newval == 3) {
                    jagif_pg_toggle('hide');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').removeClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').addClass('jagif-disabled');
                } else if (newval == 4) {
                    jagif_pg_toggle('hide');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').removeClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').addClass('jagif-disabled');
                } else if (newval == 5) {
                    jagif_pg_toggle('hide');
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-0').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-1').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-2').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-3').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-4').addClass('jagif-disabled');
                    jQuery('.jagif-free-gift-wrap-position-5').removeClass('jagif-disabled');
                }
            });
        });

        wp.customize('jagif_woo_free_gift_params[pg_display_type]', function (value) {
            value.bind(function (newval) {
                let wrap = jQuery('.jagif-popup-gift');
                let oldval = wrap.data('type');
                wrap.removeClass('jagif-popup-gift-' + oldval).addClass('jagif-popup-gift-' + newval);
                wrap.removeClass('jagif-popup-gift-init');
                wrap.data('type', newval);
            });
        });

        wp.customize('jagif_woo_free_gift_params[pg_position]', function (value) {
            value.bind(function (newval) {
                let wrap_icon = jQuery('.jagif-popup-gift-icon-wrap');
                let wrap_popup = jQuery('.jagif-popup-gift');
                let oldval_icon = wrap_icon.data('position');
                let oldval_popup = wrap_popup.data('position');

                wrap_icon.removeClass('jagif-popup-gift-icon-wrap-' + oldval_icon).addClass('jagif-popup-gift-icon-wrap-' + newval);
                wrap_popup.removeClass('jagif-popup-gift-' + oldval_popup).addClass('jagif-popup-gift-' + newval);
                wrap_icon.data('position', newval);
                wrap_popup.data('position', newval);
                wrap_icon.data('old_position', oldval_icon);
                wrap_popup.data('old_position', oldval_popup);
            });
        });

        wp.customize('jagif_woo_free_gift_params[ic_enable_shop]', function (value) {
            value.bind(function (newval) {
                if (newval) {
                    jQuery('.jagif-preview-icon-is-archive').removeClass('jagif-disabled');
                } else {
                    jQuery('.jagif-preview-icon-is-archive').addClass('jagif-disabled');
                }
            });
        });

        wp.customize('jagif_woo_free_gift_params[ic_position]', function (value) {
            value.bind(function (newval) {
                let position = parseInt(newval);
                let wrap_icon = jQuery('.jagif-icon-gift');
                let oldval_icon = wrap_icon.data('position');
                wrap_icon.removeClass('jagif-preview-icon-position-' + oldval_icon).addClass('jagif-preview-icon-position-' + position);
                wrap_icon.data('position', position);
                wrap_icon.data('old_position', oldval_icon);
            });
        });

        wp.customize('jagif_woo_free_gift_params[price_in_cart]', function (value) {
            value.bind(function (newval) {
                let cart_gift = jQuery('.jagif-cart-item.jagif-cart-child .jagif-subtotal-child .jagif-cart-display-price .jagif-cart-icon-price'),
                    cart_gift_wrap = jQuery('.jagif-cart-item.jagif-cart-child .jagif-subtotal-child .jagif-cart-display-price');
                if (cart_gift.length) {
                    switch (newval) {
                        case 'free':
                            jQuery(cart_gift_wrap).css('display', 'initial');
                            jQuery.each(cart_gift, function (key) {
                                if ( jQuery(cart_gift[key]).hasClass('jagif-cart-icon-customize-free') ) {
                                    jQuery(cart_gift[key]).removeClass('jagif-hidden');
                                } else {
                                    jQuery(cart_gift[key]).addClass('jagif-hidden');
                                }
                            });
                            break;
                        case '':
                            jQuery(cart_gift_wrap).css('display', 'none');
                            jQuery.each(cart_gift, function (key) {
                                if ( jQuery(cart_gift[key]).hasClass('jagif-cart-icon-customize-null') ) {
                                    jQuery(cart_gift[key]).removeClass('jagif-hidden');
                                } else {
                                    jQuery(cart_gift[key]).addClass('jagif-hidden');
                                }
                            });
                            break;
                        case 'icon':
                            jQuery(cart_gift_wrap).css('display', 'initial');
                            jQuery.each(cart_gift, function (key) {
                                if ( icon_custom === '1' ) {
                                    if ( jQuery(cart_gift[key]).hasClass('jagif-cart-icon-customize-image') ) {
                                        jQuery(cart_gift[key]).removeClass('jagif-hidden');
                                    } else {
                                        jQuery(cart_gift[key]).addClass('jagif-hidden');
                                    }
                                } else {
                                    if ( jQuery(cart_gift[key]).hasClass('jagif-cart-icon-customize-font') ) {
                                        jQuery(cart_gift[key]).removeClass('jagif-hidden');
                                    } else {
                                        jQuery(cart_gift[key]).addClass('jagif-hidden');
                                    }
                                }

                            });
                            break;
                        default:
                            jQuery(cart_gift_wrap).css('display', 'initial');
                            jQuery.each(cart_gift, function (key) {
                                if ( jQuery(cart_gift[key]).hasClass('jagif-cart-icon-customize-zero') ) {
                                    jQuery(cart_gift[key]).removeClass('jagif-hidden');
                                } else {
                                    jQuery(cart_gift[key]).addClass('jagif-hidden');
                                }
                            });
                            break;
                    }
                }
            });
        });

        wp.customize('jagif_woo_free_gift_params[ic_horizontal]', function (value) {
            value.bind(function (newval) {
                let ic_horizontal = parseInt(newval) > 20 ? parseInt(newval) - 10 : 0;
                let css = '\n' +
                    '            .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0{\n' +
                    '                left: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1{\n' +
                    '                right: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-0{\n' +
                    '                    left: ' + ic_horizontal + 'px ;\n' +
                    '                };\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif_badge-gift-icon .jagif-icon-gift.jagif-preview-icon-position-1{\n' +
                    '                    right: ' + ic_horizontal + 'px ;\n' +
                    '                }';
                jQuery('#jagif-preview-ic_horizontal').html(css);
            });
        });
        wp.customize('jagif_woo_free_gift_params[ic_vertical]', function (value) {
            value.bind(function (newval) {
                let ic_vertical_mobile = parseInt(newval) > 10 ? parseInt(newval) - 10 : 0;

                let css = '\n' +
                    '            .jagif_badge-gift-icon .jagif-icon-gift{\n' +
                    '                top: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif_badge-gift-icon .jagif-icon-gift{\n' +
                    '                    top: ' + ic_vertical_mobile + 'px ;\n' +
                    '                }';
                jQuery('#jagif-preview-ic_vertical').html(css);
            });
        });
        wp.customize('jagif_woo_free_gift_params[ic_size]', function (value) {
            value.bind(function (newval) {
                let ic_size_mobile = parseInt(newval) > 20 ? parseInt(newval) - 5 : 10;
                let css = '\n' +
                    '            .jagif_badge-gift-icon .jagif-icon-gift{\n' +
                    '                width: ' + newval + 'px ;\n' +
                    '                height: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            .jagif_badge-gift-icon .jagif-icon-gift i:before{\n' +
                    '                font-size: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif_badge-gift-icon .jagif-icon-gift{\n' +
                    '                    width: ' + ic_size_mobile + 'px ;\n' +
                    '                    height: ' + ic_size_mobile + 'px ;\n' +
                    '                }' +
                    '               .jagif_badge-gift-icon .jagif-icon-gift i:before{\n' +
                    '                   font-size: ' + ic_size_mobile + 'px ;\n' +
                    '               }\n' +
                    '            }';
                jQuery('#jagif-preview-ic_size').html(css);
            });
        });
        wp.customize('jagif_woo_free_gift_params[box_font_size]', function (value) {
            value.bind(function (newval) {
                let css = '\n' +
                    '            .jagif-popup-gift-products-wrap, .jagif-free-gift-promo-content{\n' +
                    '                font-size: ' + newval + 'px ;\n' +
                    '            }';
                jQuery('#jagif-preview-box_font_size').html(css);
            });
        });

        wp.customize('jagif_woo_free_gift_params[pg_horizontal]', function (value) {
            value.bind(function (newval) {
                let pg_horizontal_mobile = parseInt(newval) > 20 ? 20 - parseInt(newval) : 0;
                let css = '\n' +
                    '            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left{\n' +
                    '                left: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            \n' +
                    '            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right{\n' +
                    '                right: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left{\n' +
                    '                    left: ' + pg_horizontal_mobile + 'px  ;\n' +
                    '                }\n' +
                    '                .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right\n' +
                    '                    right: ' + pg_horizontal_mobile + 'px  ;\n' +
                    '                }\n' +
                    '            }';
                jQuery('#jagif-preview-pg_horizontal').html(css);
            });
        });
        wp.customize('jagif_woo_free_gift_params[pg_vertical]', function (value) {
            value.bind(function (newval) {
                let pg_vertical_mobile = parseInt(newval) > 20 ? 20 - parseInt(newval) : 0;
                let css = '\n' +
                    '            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right{\n' +
                    '                top: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            \n' +
                    '            .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left{\n' +
                    '                bottom: ' + newval + 'px ;\n' +
                    '            }\n' +
                    '            @media screen and (max-width: 768px) {\n' +
                    '                .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_left, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-top_right{\n' +
                    '                    top: ' + pg_vertical_mobile + 'px ;\n' +
                    '                }\n' +
                    '                .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_right, .jagif-popup-gift-icon-wrap.jagif-popup-gift-icon-wrap-bottom_left{\n' +
                    '                    bottom: ' + pg_vertical_mobile + 'px ;\n' +
                    '                }\n' +
                    '            }';
                jQuery('#jagif-preview-pg_vertical').html(css);
            });
        });

        wp.customize('jagif_woo_free_gift_params[pg_icon]', function (value) {
            value.bind(function (newval) {
                jQuery.ajax({
                    type: 'POST',
                    url: jagif_preview.ajax_url,
                    data: {
                        action: 'jagif_get_class_icon',
                        icon_id: newval,
                        type: 'gift_icons',
                        nonce: jagif_preview.jagif_nonce
                    },
                    success: function (response) {
                        if (response && response.status === 'success') {
                            jQuery('.jagif-popup-gift-icon i').attr('class', response.message);
                        }
                    },
                    error: function (err) {
                        console.log(err);
                    }
                });
            });
        });
        wp.customize('jagif_woo_free_gift_params[icon_image]', function (value) {
            value.bind(function (newval) {
                let badge_def = jQuery('.jagif_badge-gift-icon .jagif-icon-gift>i'),
                    badge_cus = jQuery('.jagif_badge-gift-icon img.jagif-icon-gift'),
                    cart_badge = jQuery('.jagif-cart-display-price .jagif-cart-icon-price');
                if (newval === '') {
                    icon_custom = '0';
                    jQuery.each(badge_def, function (key) {
                        jQuery(badge_def[key]).prop('class', icon_default);
                        jQuery(badge_def[key]).closest('.jagif-icon-gift').removeClass('jagif-hidden');
                    });
                    jQuery.each(badge_cus, function (key) {
                        jQuery(badge_cus[key]).addClass('jagif-hidden');
                    });
                    jQuery.each(cart_badge, function (key) {
                        if (jQuery(cart_badge[key]).hasClass('jagif-cart-icon-customize-font')) {
                            jQuery(cart_badge[key]).removeClass('jagif-hidden');
                        } else {
                            jQuery(cart_badge[key]).addClass('jagif-hidden');
                        }
                    });
                } else {
                    icon_custom = '1';
                    jQuery.ajax({
                        type: 'POST',
                        url: jagif_preview.ajax_url,
                        data: {
                            action: 'jagif_get_class_icon',
                            icon_id: newval,
                            type: 'icon_image',
                            nonce: jagif_preview.jagif_nonce
                        },
                        success: function (response) {
                            if (response && response.status === 'success') {
                                jQuery.each(badge_cus, function (key) {
                                    jQuery(badge_cus[key]).prop('src', response.message).removeClass('jagif-hidden');
                                });
                                jQuery.each(badge_def, function (key) {
                                    jQuery(badge_def[key]).closest('.jagif-icon-gift').addClass('jagif-hidden');
                                });
                                jQuery.each(cart_badge, function (key) {
                                    if (jQuery(cart_badge[key]).hasClass('jagif-cart-icon-customize-image')) {
                                        jQuery(cart_badge[key]).removeClass('jagif-hidden').prop('src', response.message);
                                    } else {
                                        jQuery(cart_badge[key]).addClass('jagif-hidden');
                                    }
                                });
                            }
                        },
                        error: function (err) {
                            console.log(err);
                        }
                    });
                }
            });
        });
        wp.customize('jagif_woo_free_gift_params[icon_default]', function (value) {
            value.bind(function (newval) {
                jQuery.ajax({
                    type: 'POST',
                    url: jagif_preview.ajax_url,
                    data: {
                        action: 'jagif_get_class_icon',
                        icon_id: newval,
                        type: 'gift_icons',
                        nonce: jagif_preview.jagif_nonce
                    },
                    success: function (response) {
                        if (response && response.status === 'success') {
                            icon_default = response.message;
                            let badge = jQuery('.jagif_badge-gift-icon .jagif-icon-gift>i'),
                                cart_badge = jQuery('.jagif-cart-display-price .jagif-cart-icon-price');
                            jQuery.each(badge, function (key) {
                                jQuery(badge[key]).prop('class', response.message);
                            });
                            if (cart_badge.length && icon_custom === '0') {
                                jQuery.each(cart_badge, function (key) {
                                    if (jQuery(cart_badge[key]).hasClass('jagif-cart-icon-customize-font')) {
                                        jQuery(cart_badge[key]).removeClass('jagif-hidden');
                                        jQuery(cart_badge[key]).find('i').prop('class', response.message);
                                    } else {
                                        jQuery(cart_badge[key]).addClass('jagif-hidden');
                                    }
                                })
                            }
                        }
                    },
                    error: function (err) {
                        console.log(err);
                    }
                });
            });
        });
        wp.customize('jagif_woo_free_gift_params[box_title]', function (value) {
            value.bind(function (newval) {
                jQuery('.jagif-free-gift-promo_title').html(newval);
            });
        });

        wp.customize('jagif_woo_free_gift_params[pg_enable_auto_show]', function (value) {
            value.bind(function (newval) {
                if (newval) {
                    jQuery('.jagif-popup-gift-icon-wrap').addClass('jagif-popup-auto-show-enable');
                } else {
                    jQuery('.jagif-popup-gift-icon-wrap').removeClass('jagif-popup-auto-show-enable');
                }
            });
        });
        wp.customize('jagif_woo_free_gift_params[pg_icon_box_shadow]', function (value) {
            value.bind(function (newval) {
                let css = '';
                if (newval) {
                    css = '.jagif-popup-gift-icon-wrap{\n' +
                        '                box-shadow: inset 0 0 2px rgba(0,0,0,0.03), 0 4px 10px rgba(0,0,0,0.17);\n' +
                        '            }';
                }
                jQuery('#jagif-preview-pg_icon_box_shadow').html(css);
            });
        });
        wp.customize('jagif_woo_free_gift_params[custom_css]', function (value) {
            value.bind(function (newval) {
                jQuery('#jagif-custom-css').html(newval);
            });
        });
        jagif_add_preview_control('ic_color', '.jagif_badge-gift-icon div.jagif-icon-gift >i', 'color', '');
        jagif_add_preview_control('ic_background', '.jagif_badge-gift-icon .jagif-icon-gift', 'background-color', '');
        jagif_add_preview_control('title_box_color', '.jagif-free-gift-promo_title', 'color', '');
        jagif_add_preview_control('pg_icon_color', '.jagif-popup-gift-icon-wrap .jagif-popup-gift-icon i', 'color', '');
        jagif_add_preview_control('pg_icon_bg', '.jagif-popup-gift-icon-wrap', 'background', '');
        jagif_add_preview_control('pg_icon_count_color', '.jagif-popup-gift-icon .jagif-popup-gift-count-wrap', 'color', '');
        jagif_add_preview_control('pg_icon_count_bg_color', '.jagif-popup-gift-icon .jagif-popup-gift-count-wrap', 'background', '');
        jagif_add_preview_control('gift_title_color', '.jagif-gifts-package .gift-pack-check label', 'color', '');
        jagif_add_preview_control('gift_name_color', '.jagif-free-gift-promo-item .item-gift a, .gift-item-receive .name-gift span, .gift-item-receive a.jagif-open-dropdown-choose-var', 'color', '');
        jagif_add_preview_control('gift_name_hover_color', '.jagif-free-gift-promo-item .item-gift a:hover, .gift-item-receive .name-gift span:hover, .gift-item-receive a.jagif-open-dropdown-choose-var:hover', 'color', '');

    });
})();

function jagif_add_preview_control(name, element, style, suffix = '') {
    wp.customize('jagif_woo_free_gift_params[' + name + ']', function (value) {
        value.bind(function (newval) {
            jQuery('#jagif-preview-' + name).html(element + '{' + style + ':' + newval + suffix + '}');
        })
    })
}