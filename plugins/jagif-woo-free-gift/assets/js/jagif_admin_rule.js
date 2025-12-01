jQuery(document).ready(function ($) {
    "use strict";
    $('#jagif-detail-rule').villatheme_accordion('refresh');
    let rules_wrap = $('.jagif-accordion-wrap-init .jagif-condition-wrap-wrap'),
        index = 0;
    rules_wrap.each(function () {
        elements_check($(this));
        init_row_index($(this), index);
        index++;
    });
    load_select2($('.jagif-content-pack .jagif-input-search-gift'), 'product_gift');

    $(document).on('change', '.jagif-col-enable', function () {
        let loader = $(this).closest('.rule_enable').find('.jagif-col-loader'),
            data = {
            action: 'jagif_save_switch',
            rule_id: $(this).data('id'),
            enable: $(this).is(":checked"),
            nonce: jagif_rule_params.jagif_nonce,
        };
        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: data,
            beforeSend: function (response) {
                $(loader).removeClass('jagif-hidden');
            },
            success: function (response) {

            },
            complete: function (response) {
                $(loader).addClass('jagif-hidden');
            },
        });
    });

    // Add a Rule
    $(document).on('click', '.jagif-add-condition-btn', function () {
        let $this_wrap = $(this).closest('.jagif-rule-cond-wrap').find('.jagif-rule-wrap');
        let html = $('#jagif-rule-template').html();
        $this_wrap.append(html);
        elements_check($($this_wrap.last()));
        init_row_index($this_wrap.find('.jagif-condition-wrap-wrap:last-child'));
    });

    $(document).on('change', '.jagif-rule-select', function () {
        let input_wrap = $(this).closest('.jagif-condition-wrap-wrap').find('.jagif-condition-input-wrap');
        input_wrap.each(function () {
            $(this).find('.jagif-condition-wrap').addClass('jagif-hidden');
        });
        switch ($(this).find('select').val()) {
            case 'ex_product':
                input_wrap.find('.jagif-condition-ex-product-wrap').removeClass('jagif-hidden');
                break;
            case 'in_product':
                input_wrap.find('.jagif-condition-in-product-wrap').removeClass('jagif-hidden');
                break;
            case 'ex_category':
                input_wrap.find('.jagif-condition-ex-category-wrap').removeClass('jagif-hidden');
                break;
            case 'in_category':
                input_wrap.find('.jagif-condition-in-category-wrap').removeClass('jagif-hidden');
                break;
            default:
                break;
        }
    });

    $(document).on('click', '.jagif-remove-condition-btn', function () {
        let $this = $(this),
            $this_closet = $this.closest('.jagif-condition-wrap-wrap'),
            cf = confirm(jagif_rule_params.jagif_confirm_delete);
        if (cf == true) {
            $this_closet.remove();
            let rule_wrap = $('.jagif-accordion-wrap-init .jagif-rule-wrap .jagif-condition-wrap-wrap'),
                index = 0;
            rule_wrap.each(function () {
                init_row_index($(this), index);
                index++;
            });
        }
        return false;
    });

    function init_row_index(selector, index = '') {
        let rules_wrap = selector.closest('.jagif-rule-wrap').find('.jagif-condition-wrap-wrap');
        if (index === '') {
            index = parseInt(rules_wrap.length) - 1;
        }
        if (index >= 0) {
            selector.find('.jagif-dropdown-init select').attr('name', 'jagif_conditions[' + index + ']');
            selector.find('.jagif-condition-in-product-wrap select').attr('name', 'jagif_in_product[' + index + '][]');
            selector.find('.jagif-condition-ex-product-wrap select').attr('name', 'jagif_ex_product[' + index + '][]');
            selector.find('.jagif-condition-in-category-wrap select').attr('name', 'jagif_in_category[' + index + '][]');
            selector.find('.jagif-condition-ex-category-wrap select').attr('name', 'jagif_ex_category[' + index + '][]');
        }
    }

    function elements_check($select_wrap) {
        let elements_select = $select_wrap.find('.jagif-condition-wrap .jagif-search-select2');
        $select_wrap.find('.vi-ui.dropdown:not(.jagif-dropdown-init)').addClass('jagif-dropdown-init').dropdown();
        elements_select.each(function () {
            load_select2($(this), $(this).data('select-type'));
        });
    }

    function load_select2(selector, type) {
        selector.on("select2:unselect", function () {
            let arr_term = $(this).val();
            if (arr_term.length == 0) {
                $(this).html('');
            }
        });
        switch (type) {
            case 'product':
                selector.select2({
                    closeOnSelect: false,
                    minimumInputLength: 1,
                    dropdownParent: selector.parent(),
                    placeholder: 'All Product',
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        allowClear: true,
                        data: function (params) {
                            return {
                                q: params.term,
                                nonce: jagif_rule_params.jagif_nonce,
                                action: 'jagif_product_ajax'
                            };
                        },
                        processResults: function (data) {
                            let options = [];
                            if (data) {
                                $.each(data, function (index, text) {
                                    let product_type = text[2] === 'simple' ? '' : ' ( ' + text[2] + ' )';
                                    options.push({
                                        id: text[0],
                                        text: text[1] + ' ( ID: ' + text[0] + ' )' + product_type,
                                        'type': text[2]
                                    });
                                });
                            }
                            return {
                                results: options
                            };
                        },
                        cache: true
                    }
                });
                break;
            case 'category':
                selector.select2({
                    closeOnSelect: false,
                    minimumInputLength: 1,
                    dropdownParent: selector.parent(),
                    placeholder: 'All Categories',
                    ajax: {
                        type: 'post',
                        url: ajaxurl,
                        data: function (params) {
                            let query = {
                                keysearch: params.term,
                                nonce: jagif_rule_params.jagif_nonce,
                                tax_search: 'product_cat',
                                type: 'public',
                                action: 'jagif_cats_ajax'
                            };
                            return query;
                        },
                        processResults: function (data) {

                            return {
                                results: data
                            };

                            let newOption = new Option(data.text, data.id, false, false);

                            selector.append(newOption);
                        }
                    }
                });
                break;
            case 'product_gift':
                selector.select2({
                    closeOnSelect: false,
                    minimumInputLength: 1,
                    dropdownParent: selector.parent(),
                    placeholder: 'Search Gift',
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        allowClear: true,
                        data: function (params) {
                            return {
                                q: params.term,
                                nonce: jagif_rule_params.jagif_nonce,
                                action: 'jagif_gift_product_ajax'
                            };
                        },
                        processResults: function (data) {
                            let options = [];
                            if (data) {
                                $.each(data, function (index, text) {
                                    options.push({
                                        id: text[0],
                                        text: text[1] + ' ( ID: ' + text[0] + ' )'
                                    });
                                });
                            }
                            return {
                                results: options
                            };
                        },
                        cache: true
                    }
                });
                break;
            default:
                break;
        }
    }
});