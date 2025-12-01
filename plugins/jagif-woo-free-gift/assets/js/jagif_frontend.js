(function () {
    'use strict';
    /* global gb_display_style_param */
    jQuery(document).ready(function ($) {
        $('#gift_pack_1').prop('checked', true);
        let is_blocked = function ($node) {
            return $node.is('.processing') || $node.parents('.processing').length;
        };
        if ($('input.jagif-ids').length) {
            $('input.jagif-ids').each(function () {
                if ($(this).data('id')) {
                    if (jagif_frontend_param.gb_display_style === '2') {
                        let popup_package_wrap = $('.jagif-popup-gift-wrap.jagif-popup-' + $(this).data('id') + ' .jagif-rule-available.jagif-free-gift-items');
                        if (popup_package_wrap.length) {
                            update_multi_ids($(popup_package_wrap), 'init', 'popup');
                        }
                    } else {
                        let product_wrap = $(this).closest('.product.type-product').find('.jagif-free_gift_wrap.jagif-gift-available');
                        if (product_wrap.length) {
                            update_multi_ids($(product_wrap), 'init');
                        }
                    }
                }
            })
        }
        let block = function ($node) {
            if (!is_blocked($node)) {
                $node.addClass('processing').block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            }
        };
        if ($('#jagif-free_gift_wrap.jagif-free_gift_wrap').length && jagif_frontend_param.gb_display_style !== '2') {
            let cookie = jagif_frontend_param.user_id !== '' ? 'jagif_rules_' + jagif_frontend_param.user_id : '';
            if (cookie !== '') {
                let rules_cookie = jagifGetCookie(cookie);
                if (rules_cookie) {
                    $.each(rules_cookie, function (key, val) {
                        let hide_rule = $('#jagif-free_gift_wrap.jagif-gift-not-available.jagif-free_gift_wrap.jagif-rule-' + val);
                        if (hide_rule.length) {
                            hide_rule.find('.jagif-free-gift-promo_title.jagif-collapse-title').data('active', 0);
                            hide_rule.find('.jagif-free-gift-items').slideUp(500);
                        }
                    });
                }
            }
        }
        let unblock = function ($node) {
            $node.removeClass('processing').unblock();
        };
        $(document).on('click', '.jagif-open-popup-choose-var:not(.jagif-variation-choose-var)', function (event) {
            let data_variation_edit = $(this).attr('data-attrs'),
                variationDropdown = $(this.parentNode).find('.jagif-variation-dropdown').html();
            if (jagif_frontend_param.gb_display_style === '2') {
                $(document.body).trigger('jagif-popup-gift-toggle', ['hide']);
            }
            $(this).closest('.jagif-free_gift_wrap').addClass('jagif-popup-active');
            data_variation_edit = data_variation_edit.split("&");
            jagif_popup_var_toggle('show', variationDropdown);
            if ($('.jagif-variation-popup').hasClass('jagif-popup-var-content-open')) {
                let check_select_var = [],
                    variation_form = $('.jagif-popup-var-content-open').find('.jagif-variation-form'),
                    data_product_variations = $(variation_form).data('product_variations'),
                    variation_select = $(variation_form).find('select.jagif-attribute-options');
                checkIsInStock(variation_form, variation_select, data_product_variations);
                triggerAttributeValue(data_variation_edit);
            }

            return false;
        });

        $(document).on('click', '.jagif-open-popup-choose-var.jagif-variation-choose-var', function (event) {
            let data_variation_edit = $(this).attr('data-attrs'),
                variationDropdown = $(this.parentNode).find('.jagif-variation-dropdown').html();
            if (jagif_frontend_param.gb_display_style === '2') {
                $(document.body).trigger('jagif-popup-gift-toggle', ['hide']);
            }
            $(this).closest('.jagif-free_gift_wrap').addClass('jagif-popup-active');
            data_variation_edit = data_variation_edit.split("&");
            jagif_popup_var_toggle('show', variationDropdown);
            if ($('.jagif-variation-popup').hasClass('jagif-popup-var-content-open')) {
                let check_select_var = [],
                    variation_form = $('.jagif-popup-var-content-open').find('.jagif-variation-form'),
                    data_product_variations = $(variation_form).data('product_variations'),
                    variation_select = $(variation_form).find('select.jagif-attribute-options');
                variation_form.addClass('jagif-variation-specifically');
                variation_form.find('.jagif-btn-choose .jagif-dropdown-close-var').prop('disabled', false).removeClass('jagif-btn-disabled');
            }

            return false;
        });

        $('input[type=radio].gift_pack_active').change(function () {
            let gifts_package = $(this).closest('.jagif-gifts-package'),
                gift_ids = $(gifts_package).find('input.jagif_id_gift_item'),
                jagif_pack_id = $('.jagif_pack_id'),
                gift_pack_content = $(this).closest('.jagif-free-gift-promo-content'),
                pack_active = $(gift_pack_content).find('.jagif-gifts-package.active'),
                pack_active_position = $(pack_active).attr('data-position');
            $(pack_active).removeClass('active');
            if (!$(gifts_package).hasClass('active')) {
                $(gifts_package).addClass('active');
                update_multi_ids(gifts_package, 'change');
            }
        });

        $(document).on('click', '.jagif-popup-var-overlay, .jagif-popup-var-close-wrap, .close-popup-choose-var', function () {
            jagif_popup_var_toggle('hide');
        });

        $(document).on('click', '.jagif-free-gift-promo_title.jagif-collapse-title', function () {
            let rule_id = $(this).closest('#jagif-free_gift_wrap').data('rule'),
                cookie = jagif_frontend_param.user_id !== '' ? 'jagif_rules_' + jagif_frontend_param.user_id : '';
            if ($(this).data('active') === 1 || $(this).data('active') === '1') {
                $(this).data('active', 0);
                if (rule_id) {
                    if (cookie !== '') {
                        let rules_cookie = jagifGetCookie(cookie),
                            rule_exits = false;
                        if (rules_cookie) {
                            $.each(rules_cookie, function (key, val) {
                                if (val === rule_id.toString()) rule_exits = true;
                            });
                            if (!rule_exits) {
                                rules_cookie.push(rule_id.toString());
                                jagifSetCookie(cookie, rules_cookie);
                            }
                        } else {
                            jagifSetCookie(cookie, [rule_id]);
                        }
                    }
                }
                $(this).closest('#jagif-free_gift_wrap').find('.jagif-free-gift-items').slideUp(500);
            } else {
                $(this).data('active', 1);
                if (rule_id) {
                    if (cookie !== '') {
                        let rules_cookie = jagifGetCookie(cookie);
                        rule_id = rule_id.toString();
                        if (rules_cookie) {
                            rules_cookie = $.grep(rules_cookie, function (value) {
                                return value !== rule_id;
                            });
                            jagifSetCookie(cookie, rules_cookie);
                        }
                    }
                }
                $(this).closest('#jagif-free_gift_wrap').find('.jagif-free-gift-items').slideDown(500);
            }
        });

        function triggerAttributeValue(data_variation_edit) {
            let wrap = $('.jagif-variation-popup');
            if (data_variation_edit.length > 0) {
                $(data_variation_edit).each(function (k, v) {
                    let attr = v.split("=");
                    if (attr.length == 2) {
                        wrap.find(`select[name="${attr[0]}"]`).val(attr[1]).trigger('change');
                    }
                })
            }
        }

        //get user cookie
        function jagifGetCookie(cname) {
            let name = cname + '=';
            let ca = document.cookie.split(';');

            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) == 0) {
                    let c_string = decodeURIComponent(c.substring(name.length, c.length));
                    return c_string.split(',');
                }
            }

            return '';
        }

        //save data to cookie
        function jagifSetCookie(cname, cvalue, exdays) {
            let d = new Date(),
                rules_string = '';
            if (cvalue.length) {
                $.each(cvalue, function (key, val) {
                    if (rules_string === '') rules_string = val; else rules_string += ',' + val;
                });
                d.setTime(d.getTime() + (
                    exdays * 24 * 60 * 60 * 1000
                ));
                let expires = 'expires=' + d.toUTCString();
                document.cookie = cname + '=' + rules_string + '; ' + expires + '; path=/';
            }
        }

        function jagif_popup_var_toggle(action = '', html) {
            let wrap = $('.jagif-variation-popup'),
                class_open = 'jagif-popup-var-content-open',
                class_close = 'jagif-popup-var-content-close';
            wrap.find('.jagif-variation-form').removeClass('jagif-variation-specifically');
            if (action === 'hide' && wrap.hasClass(class_close)) {
                return false;
            }
            if (action === 'show' && wrap.hasClass(class_open)) {
                return false;
            }
            if (wrap.hasClass(class_close)) {
                wrap.removeClass(class_open);
                wrap.addClass(class_close);
            } else {
                wrap.addClass(class_open);
                wrap.removeClass(class_close);
            }

            if (wrap.hasClass(class_close)) {
                wrap.append(html);
                wrap.addClass(class_open).removeClass(class_close);
                $('.jagif-variation-wrap').removeClass('jagif-disabled');
            } else {
                wrap.html('');
                wrap.addClass(class_close).removeClass(class_open);
                $('.jagif-variation-wrap').addClass('jagif-disabled');
                $('.jagif-free_gift_wrap.jagif-popup-active').removeClass('jagif-popup-active');
            }
        }

        // Check select variation on gift box
        $(document).on('click', '.jagif-dropdown-close-var', function () {

            let variation_id = '',
                arr_attribute_name = [],
                arr_data_attr_name = [],
                data_btn_id = $(this).attr('data-product_id'),
                product_id = $(this).closest('.jagif-variation-form').data('product_id'),
                position_selector = $('.jagif-free_gift_wrap.jagif-popup-active .free-gift.free-gift-id-' + product_id + ''),
                data_select_variations = $(this).closest('.jagif-variation-form').find('select.jagif-attribute-options'),
                select_available_choose = $(this).closest('.jagif-variation-form').find('select.jagif-attribute-options'),
                gift_item_id_active = $('.jagif-free_gift_wrap.jagif-popup-active .free-gift.free-gift-id-' + product_id),
                attributes_name = gift_item_id_active.find('.jagif-var-name'),
                attributes_value = gift_item_id_active.find('.jagif-open-popup-choose-var'),
                gift_link_element = gift_item_id_active.find('a.jagif-link-to-gift'),
                data_old_id = gift_item_id_active.find('.jagif_id_gift_item').val(),
                jagif_id_gift_variation = position_selector.find('input.jagif_id_gift_item'),
                get_ids = $('.jagif-free_gift_wrap.jagif-popup-active .jagif-gifts-package.active input.jagif_id_gift_item'),
                data_product_variations = $(this).closest('.jagif-variation-form').data('product_variations'),
                img_variation = position_selector.find('.free-gift-img img'),
                getChosenAttrs = getChosenAttributes(data_select_variations),
                match_variation = find_matching_variations(data_product_variations, getChosenAttrs.data),
                arr_attr_names = getChosenAttrs.attr_name,
                variation_data = '',
                variation_name = '';

            $.each(getChosenAttrs['data'], function (key, val) {
                if (variation_data === '') {
                    variation_data = key + '=' + val;
                } else {
                    variation_data += '&' + key + '=' + val;
                }
            });

            $.each(data_select_variations, function (key, val) {
                if (variation_name === '') {
                    variation_name = $(val).find('option:selected').html();
                } else {
                    variation_name += ' - ' + $(val).find('option:selected').html();
                }
            });
            if (getChosenAttrs.chosenCount < getChosenAttrs.count) {
                let available_thumb = [];
                match_variation.forEach((value, index, array) => {
                    if (value.is_in_stock) {
                        available_thumb.push(value);
                    }
                });
                variation_id = available_thumb[0].variation_id;
            } else if (getChosenAttrs.chosenCount == getChosenAttrs.count) {
                if (match_variation[0].is_in_stock) {
                    variation_id = match_variation[0].variation_id;
                }
            }
            if (!$(this).closest('.jagif-variation-form').hasClass('jagif-variation-specifically')) {
                $(jagif_id_gift_variation).val(variation_id).trigger('change');
            }
            $(attributes_name).html(variation_name);
            $(attributes_name).data('value', variation_data);
            $(attributes_value).data('attrs', variation_data);
            $('.jagif-free_gift_wrap.jagif-popup-active').removeClass('jagif-popup-active');
            update_multi_ids(get_ids, 'change');

            resetImageVariation(img_variation, select_available_choose, data_product_variations);
            jagif_popup_var_toggle('hide');
            unblock($('.jagif-variation-popup'));

        });
        $(document).on('click', '.jagif-cart-change-variation', function (e) {
            e.preventDefault();
            let data_variation_edit = $(this).attr('data-variation');
            data_variation_edit = data_variation_edit.split("&");
            let wrap = $('.jagif-variation-wrap .jagif-variation-popup');
            let variationPopupContent = $(this).find('.jagif-variation-dropdown').html();
            wrap.html('');
            jagif_popup_var_toggle('show', variationPopupContent);

            if ($('.jagif-variation-popup').hasClass('jagif-popup-var-content-open')) {
                let check_select_var = [],
                    variation_form = $('.jagif-popup-var-content-open').find('.jagif-variation-form'),
                    data_product_variations = $(variation_form).data('product_variations'),
                    variation_select = $(variation_form).find('select.jagif-attribute-options');
                checkIsInStock(variation_form, variation_select, data_product_variations, null);
                triggerAttributeValue(data_variation_edit);
            }

            return false;
        });
        $(document).on('click', '.jagif-cart-change-variation-popup', function (e) {
            e.preventDefault();
            let new_variation_id = $(this).attr('data-variation_id'),
                new_variation = $(this).attr('data-variation'),
                 parent_key = $(this).attr('data-parent_key'),
                 variation_key = $(this).attr('data-variation_key'),
                 variation_string = $(this).attr('data-variation'),
                 variation_spec_string = $(this).attr('data-new-variation'),
                 variable_id = $(this).attr('data-variable_id');
            let change_variation_id = '',
                btn_open_popup_index = '',
                btn_choose_index = $(this).attr('data-index'),
                btn_parent_id = $(this).attr('data-parent_id'),
                btn_quantity = $(this).attr('data-quantity'),
                cart_item = $('.jagif-cart-item.jagif-cart-child .jagif-cart-change-variation');

            cart_item.each(function (k, v) {
                if ($(v).attr('data-index') == btn_choose_index && $(v).attr('data-parent_id') == btn_parent_id) {
                    btn_open_popup_index = v;
                }
            });

            if ($(this).hasClass('jagif-cart-is-variation')) {
                if (variation_spec_string === variation_string) {
                    jagif_popup_var_toggle('hide');
                } else {
                    updateCart(new_variation_id, variation_key, parent_key, variation_spec_string, btn_choose_index, btn_quantity, variable_id);
                }
            } else {
                if (btn_open_popup_index && $(btn_open_popup_index).attr('data-variation_id') == new_variation_id &&
                    $(btn_open_popup_index).attr('data-variation') == new_variation) {
                    jagif_popup_var_toggle('hide');
                } else {
                    updateCart(new_variation_id, variation_key, parent_key, variation_string, btn_choose_index, btn_quantity, variable_id);
                }
            }

        });

        function updateCart(new_variation_id, cart_key, parent_key, variation_string, btn_choose_index, btn_quantity, variable_id) {
            $("a.checkout-button.wc-forward").addClass('disabled');
            jagif_popup_var_toggle('hide');
            block($('.woocommerce-cart-form'));
            block($('div.cart_totals'));
            let data = {
                action: 'jagif_update_cart',
                jagif_new_variation_id: new_variation_id,
                jagif_variable_id: variable_id,
                jagif_new_variation: variation_string,
                jagif_item_key: cart_key,
                nonce: jagif_frontend_param.nonce,
            };
            $.ajax({
                type: 'POST',
                url: jagif_frontend_param.ajaxurl,
                data: data,
                dataType: 'html',
                success: function (response) {

                },
                complete: function () {
                    jagifRefreshCart();
                }
            });
        }

        function jagifRefreshCart() {
            unblock($('.woocommerce-cart-form'));
            unblock($('.cart_totals'));
            $("[name='update_cart']").removeAttr('disabled').trigger('click').prop('disabled', true);
        }

        function getChosenAttributes(data_select_variations) {
            let data = {};
            let count = 0;
            let chosen = 0;
            let attr_name = [];

            data_select_variations.each(function (k, v) {
                let attribute_name = $(v).data('attribute_name') || $(v).attr('name');
                let value = $(v).val() || '';

                if (value.length > 0) {
                    chosen++;
                }

                count++;
                data[attribute_name] = value;
                attr_name[attribute_name.replace(/attribute_pa_/gi, "")] = value;
            });
            return {
                'count': count,
                'chosenCount': chosen,
                'attr_name': attr_name,
                'data': data
            };
        }

        function resetImageVariation(selector, data_select_variations, data_product_variations) {
            let setting = [];
            let thumb_variation = '',
                getChosenAttrs = getChosenAttributes(data_select_variations),
                match_variation = find_matching_variations(data_product_variations, getChosenAttrs.data);
            if (getChosenAttrs.chosenCount < getChosenAttrs.count) {
                let available_thumb = [];
                match_variation.forEach((value, index, array) => {
                    if (value.is_in_stock) {
                        available_thumb.push(value);
                    }
                });
                thumb_variation = available_thumb[0].image.thumb_src;
            } else if (getChosenAttrs.chosenCount == getChosenAttrs.count) {
                if (match_variation[0].is_in_stock) {
                    thumb_variation = match_variation[0].image.thumb_src;
                }
            }
            if (thumb_variation) {
                $(selector).attr('src', thumb_variation);
                $(selector).prop('srcset', '');
            }
        }

        function checkIsInStock(variation_form, data_select_variations, data_product_variations, gift_box_active) {
            let disable_btn = true,
                change_variation_id = '',
                btn_choose_var = $(variation_form).find('.jagif-dropdown-close-var'),
                stock_element = $(variation_form).find('.is-out-of-stock'),
                is_exists_element = $(variation_form).find('.is-variation-exists'),
                getChosenAttrs = getChosenAttributes(data_select_variations),
                match_variation = find_matching_variations(data_product_variations, getChosenAttrs.data),
                cart_btn_change_var = $(variation_form).find('.jagif-cart-change-variation-popup'),
                variation_att = '',
                prd_options = $(variation_form).find('.jagif-attribute-options.jagif-va-attribute-options');

            disable_btn = false;
            let available_thumb = [];
            match_variation.forEach((value, index, array) => {
                if (value.is_in_stock) {
                    available_thumb.push(value);
                }
            });
            if (available_thumb.length > 0) {
                change_variation_id = available_thumb[0].variation_id;
            } else {
                if (!$(cart_btn_change_var).hasClass('jagif-cart-is-variation')) {
                    disable_btn = true;
                }
            }

            if (disable_btn) {
                $(stock_element).removeClass('jagif-disabled');
                $(btn_choose_var).prop('disabled', true).addClass('jagif-btn-disabled');
                $(cart_btn_change_var).prop('disabled', true).addClass('jagif-btn-disabled');
                if (cart_btn_change_var.length) {
                    $(cart_btn_change_var).attr('data-variation_id', '');
                }
            } else {
                $(btn_choose_var).prop('disabled', false).removeClass('jagif-btn-disabled');
                $(stock_element).addClass('jagif-disabled');
                $(cart_btn_change_var).prop('disabled', false).removeClass('jagif-btn-disabled');
                if (cart_btn_change_var.length && change_variation_id) {
                    $(cart_btn_change_var).attr('data-variation_id', change_variation_id);
                    prd_options.each(function () {
                        if (variation_att) {
                            variation_att += '&' + $(this).attr('name') + '=' + $(this).find('option:selected').val();
                        } else {
                            variation_att = $(this).attr('name') + '=' + $(this).find('option:selected').val();
                        }
                    });
                    $(cart_btn_change_var).attr('data-variation', variation_att);
                }

            }
        }

        function checkCartSpecifically(variation_form, data_select_variations, data_product_variations) {
            let form_attrs = $(variation_form).find('.jagif-attribute-options'), end_variation = '',
                current_variation = $(variation_form).find('.jagif-cart-change-variation-popup');
            $.each(form_attrs, function (key, value) {
                if (end_variation === '') {
                    end_variation = $(value).attr('data-attribute_name') + '=' + $(value).val();
                } else {
                    end_variation += '&' + $(value).attr('data-attribute_name') + '=' + $(value).val();
                }
            });
            if ($(current_variation).attr('data-new-variation') !== end_variation) {
                $(current_variation).attr('data-new-variation', end_variation);
            }
        }

        $(document).on('change', '.jagif-variation-popup .jagif-attribute-options', function () {
            let variation_form = $(this).closest('.jagif-variation-form'),
                gift_box_active = $('.jagif-free_gift_wrap.jagif-popup-active'),
                select_available_choose = $(this).closest('.jagif-variation-form').find('select.jagif-attribute-options'),
                data_product_variations = $(this).closest('.jagif-variation-form').data('product_variations'),
                img_variation = $(this).closest('.jagif-variation-popup').find('.free-gift-popup-img img');
            if ( !$(this).closest('.jagif-variation-form').hasClass('jagif-variation-specifically') ) {
                checkIsInStock(variation_form, select_available_choose, data_product_variations, gift_box_active);
            }
            if ( $(this).closest('.jagif-variation-form').hasClass('jagif-cart-variation-specifically') ) {
                checkCartSpecifically(variation_form, select_available_choose, data_product_variations);
            }
        });

        function check_allow_select_variation(select_available) {
            let button_add_to_cart = $('.single_add_to_cart_button');
            let check_availability = true;
            if (select_available.length > 0) {
                select_available.each(function (i) {
                    if (select_available[i].value === '') {
                        check_availability = false;
                    }
                })
            }
            if (check_availability === false) {
                button_add_to_cart.addClass('jagif-check-disabled');
            } else {
                if (button_add_to_cart.hasClass('jagif-check-disabled')) {
                    button_add_to_cart.removeClass('jagif-check-disabled');
                }
            }
        }

        $(document)
            .on('mouseenter', '.jagif-preview-icon-position-0.jagif-preview-icon-is-archive', function () {
                let gifts_title = $(this).data('jagif_items');
                __print_box_gift(this, gifts_title, 'add');
            })
            .on('mouseleave', '.jagif-preview-icon-position-0.jagif-preview-icon-is-archive', function () {
                let gifts_title = $(this).data('jagif_items');
                __print_box_gift(this, gifts_title, 'remove');
            })
            .on('mouseenter', '.jagif-preview-icon-position-1.jagif-preview-icon-is-archive', function () {
                let gifts_title = $(this).data('jagif_items');
                __print_box_gift(this, gifts_title, 'add', 'right');
            })
            .on('mouseleave', '.jagif-preview-icon-position-1.jagif-preview-icon-is-archive', function () {
                let gifts_title = $(this).data('jagif_items');
                __print_box_gift(this, gifts_title, 'remove', 'right');
            });

        function __print_box_gift(element, gifts_title, action, type = null) {
            let position = type === 'right' ? '-right' : '';
            let content = `<div class="jagif-box-gift${position}">
                                <div class="gift-archive-item">
                                <span>Receive `;
            for (let i = 0; i < gifts_title.length; i++) {
                content += `<em>${gifts_title[i]}</em>`;
            }
            content += `</span></div></div>`;

            let offset = $(element).position();

            if (action === 'add') {
                if (type && type == 'right') {
                    $(element).after(content);
                    $(element).parent().find('.jagif-box-gift-right')
                        .css('left', offset.left - $(element).width() - ($(element).width() / 2))
                        .css('top', offset.top + 10 + $(element).height() / 2)
                } else {
                    $(element).after(content);
                    $(element).parent().find('.jagif-box-gift')
                        .css('left', offset.left + $(element).width() - ($(element).width() / 2) - 10)
                        .css('top', offset.top + 10 + $(element).height() / 2)
                        .fadeIn('100');
                }
            } else if (action === 'remove') {
                $(element).parent().find('.jagif-box-gift').remove();
                $(element).parent().find('.jagif-box-gift-right').remove();
            }

        }

        // Matches inline variation objects to chosen attributes
        // find match variation
        function find_matching_variations(product_variations, settings) {
            let matching = [];
            for (let i = 0; i < product_variations.length; i++) {
                let obj_variation = product_variations[i].attributes;
                if (variations_match(obj_variation, settings)) {
                    matching.push(product_variations[i]);
                }
            }
            return matching;
        }

        function variations_match(attrs1, attrs2) {
            let match = true;
            for (let attr_name in attrs1) {
                if (attrs1.hasOwnProperty(attr_name)) {
                    let val1 = attrs1[attr_name];
                    let val2 = attrs2[attr_name];
                    if (val1 !== undefined && val2 !== undefined && val1.length !== 0 && val2.length !== 0 && val1 !== val2) {
                        match = false;
                    }
                }
            }
            return match;
        }

        function update_gift_ids(get_ids) {
            let array_ids = [];
            get_ids.each(function (i) {
                let item_gift_var = $(get_ids[i]).closest('.jagif-inline.item-gift').find('.jagif_option_wrap .jagif-open-popup-choose-var');
                if (item_gift_var.length) {
                    array_ids.push(get_ids[i].value + '/' + item_gift_var.data('attrs'));
                } else {
                    array_ids.push(get_ids[i].value)
                }
            });
            if (array_ids.length === get_ids.length) {
                let gift_ids = array_ids.join(',');
                $('input.jagif-ids').val(gift_ids)
            }
        }

        function update_multi_ids(get_rule, mode = 'init', call = 'normal') {
            let array_ids = [],
                pack_ids = [],
                output = '';
            if (mode === 'init') {
                get_rule.each(function (i) {
                    let pack_block = $(get_rule[i]).find('.jagif-gifts-package'),
                        pack_exits = false;

                    pack_block.each(function (j) {
                        $(pack_block[j]).removeClass('active');
                        if (!pack_exits) {
                            let pack_id = $(pack_block[j]).data('pack');
                            if (!pack_ids.includes(pack_id)) {
                                let add_pack = $(pack_block[j]).find('input.jagif_id_gift_item'),
                                    radio_pack = $(pack_block[j]).find('.gift_pack_active.gift_pack_active_' + pack_id),
                                    add_pack_ids = [];
                                if (radio_pack.length) {
                                    $(radio_pack).prop("checked", true);
                                }
                                $(pack_block[j]).addClass('active');
                                add_pack.each(function (j) {
                                    let item_gift_var = $(add_pack[j]).closest('.jagif-inline.item-gift').find('.jagif_option_wrap .jagif-open-popup-choose-var');
                                    if (item_gift_var.length) {
                                        add_pack_ids.push(add_pack[j].value + '/' + item_gift_var.data('attrs'));
                                    } else {
                                        add_pack_ids.push(add_pack[j].value)
                                    }
                                });
                                array_ids.push(add_pack_ids);
                                pack_exits = true;
                                pack_ids.push(pack_id);
                            }
                        }
                    });
                });
                if (array_ids.length === get_rule.length) {
                    for (let r_key = 0; r_key < array_ids.length; r_key++) {
                        if (output === '') {
                            output = array_ids[r_key].join(',');
                        } else {
                            output += '|' + array_ids[r_key].join(',');
                        }
                    }
                    $('input.jagif-ids').val(output)
                }
            } else {
                let set_gift_wrap = $('.jagif-free_gift_wrap.jagif-gift-available');
                set_gift_wrap.each(function (i) {
                    let add_pack_ids = [],
                        pack_wrap = $(set_gift_wrap[i]).find('.jagif-gifts-package.active');
                    if (pack_wrap.length) {
                        let add_pack = $(pack_wrap).find('input.jagif_id_gift_item');
                        pack_ids.push($(pack_wrap).data('pack'));
                        add_pack.each(function (j) {
                            let item_gift_var = $(add_pack[j]).closest('.jagif-inline.item-gift').find('.jagif_option_wrap .jagif-open-popup-choose-var');
                            if (item_gift_var.length) {
                                add_pack_ids.push(add_pack[j].value + '/' + item_gift_var.data('attrs'));
                            } else {
                                add_pack_ids.push(add_pack[j].value)
                            }
                        });
                    }
                    array_ids.push(add_pack_ids);
                });
                for (let r_key = 0; r_key < array_ids.length; r_key++) {
                    if (output === '') {
                        output = array_ids[r_key].join(',');
                    } else {
                        output += '|' + array_ids[r_key].join(',');
                    }
                }
                $('input.jagif-ids').val(output);
                $('input.jagif_pack_id').val(pack_ids.join(','));
            }
        }
    });
}());