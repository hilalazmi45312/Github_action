/* =========================================================
 * admin_script.js
 * ========================================================= */

'use strict';
jQuery(document).ready(function () {

    jQuery("#user_blocking_promo .user_blocking_promo-close").on('click', function () {
        var data;
        // Hide it
        jQuery("#user_blocking_promo").hide();

        // Save this preference
        jQuery.post(adminURL + "?user_blocking_promo=0", data, function (response) {
        });
    });

    jQuery('#week-sun .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-sun .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var sun_time = document.getElementById('week-sun');
    if(sun_time) {
        var sun_time_pair = new Datepair(sun_time);
    }
    

    jQuery('#week-mon .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-mon .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var mon_time = document.getElementById('week-mon');
    if(mon_time) {
        var mon_time_pair = new Datepair(mon_time);
    }

    jQuery('#week-tue .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-tue .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var tue_time = document.getElementById('week-tue');
    if(tue_time) {
        var tue_time_pair = new Datepair(tue_time);
    }

    jQuery('#week-wed .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-wed .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var wed_time = document.getElementById('week-wed');
    if(wed_time) {
        var wed_time_pair = new Datepair(wed_time);
    }    

    jQuery('#week-thu .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-thu .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var thu_time = document.getElementById('week-thu');
    if(thu_time) {
        var thu_time_pair = new Datepair(thu_time);
    }    

    jQuery('#week-fri .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-fri .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var fri_time = document.getElementById('week-fri');
    if(fri_time) {
        var fri_time_pair = new Datepair(fri_time);
    }    

    jQuery('#week-sat .start').timepicker({
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    jQuery('#week-sat .end').timepicker({
        'maxTime': '11:45 PM',
        'showDuration': true,
        'step': 15,
        'timeFormat': 'h:i A'
    });
    var sat_time = document.getElementById('week-sat');
    if(sat_time) {
        var sat_time_pair = new Datepair(sat_time);
    }    

    // stop to enter value from keyboard in time filed 
    jQuery(".time.start.time-field.ui-timepicker-input,.time.end.time-field.ui-timepicker-input,.form-table.tbl-timing #frmdate,.form-table.tbl-timing #todate").keypress(function(event) {event.preventDefault();});

    // deactivation popup code
    var ublk_plugin_admin = jQuery('.documentation_ublk_plugin').closest('div').find('.deactivate').find('a');
    ublk_plugin_admin.on('click', function (event) {
        event.preventDefault();
        jQuery('#deactivation_thickbox_ublk').trigger('click');
        jQuery('#TB_window').removeClass('thickbox-loading');
        change_thickbox_size_ublk();
    });
    checkOtherDeactivate();
    jQuery('.sol_deactivation_reasons').on('click', function () {
        checkOtherDeactivate();
    });
    jQuery('#sbtDeactivationFormCloseublk').on('click', function (event) {
        event.preventDefault();
        jQuery("#TB_closeWindowButton").trigger('click');
    });

    jQuery('.ublk-deactivation').on('click', function () {
        window.location.href = ublk_plugin_admin.attr('href');
    });

    jQuery('#txtUsername').on('keypress', function (event) {
        var keycode = (event.keyCode ? event.keyCode : event.which);
        if (keycode == '13') {
            jQuery('#filter_action').trigger('click');
        }
    });
    //Datepicker
    jQuery("#frmdate").datetimepicker({
        dateFormat: 'mm/dd/yy',
        minDate: 0,
        changeMonth: true,
        changeYear: true,
        timeFormat: 'HH:mm:ss',
        onClose: function (selectedDate) {
            jQuery("#todate").datetimepicker("option", "minDate", selectedDate);
        }
    });
    jQuery("#todate").datetimepicker({
        dateFormat: 'mm/dd/yy',
        minDate: 0,
        changeMonth: true,
        changeYear: true,
        timeFormat: 'HH:mm:ss',
        onClose: function (selectedDate) {
            jQuery("#frmdate").datepicker("option", "maxDate", selectedDate);
        }
    });
    jQuery('#display_status').on('change', function () {
        if (jQuery(this).val() == 'roles') {
            jQuery('.role_records').show();
            jQuery('.export_display').val('roles');
            jQuery('.users_records, .filter_div').hide();
            jQuery(".frmExport .actions").css("top", "-15px");
            jQuery(".frmExport").css({ "left": "225px", "bottom": "-36px" });

        }
        else {
            jQuery('.role_records').hide();
            jQuery('.export_display').val('users');
            jQuery('.users_records, .filter_div').show();
            jQuery(".frmExport .actions").css("top", "auto");
            jQuery(".frmExport").css({ "left": "395px", "bottom": "-76px" });
        }
    });
    jQuery('#chkapply').on('click', function () {
        var txtSunFrom = jQuery('#txtSunFrom').val();
        var txtSunTo = jQuery('#txtSunTo').val();
        var txtMonFrom = jQuery('#txtMonFrom').val();
        var txtMonTo = jQuery('#txtMonTo').val();
        var txtTueFrom = jQuery('#txtTueFrom').val();
        var txtTueTo = jQuery('#txtTueTo').val();
        var txtWedFrom = jQuery('#txtWedFrom').val();
        var txtWedTo = jQuery('#txtWedTo').val();
        var txtThuFrom = jQuery('#txtThuFrom').val();
        var txtThuTo = jQuery('#txtThuTo').val();
        var txtFriFrom = jQuery('#txtFriFrom').val();
        var txtFriTo = jQuery('#txtFriTo').val();
        var txtSatFrom = jQuery('#txtSatFrom').val();
        var txtSatTo = jQuery('#txtSatTo').val();
        if (txtSunFrom != '' && txtSunTo != '') {
            var from = txtSunFrom;
            var to = txtSunTo;
        } else if (txtMonFrom != '' && txtMonTo != '') {
            var from = txtMonFrom;
            var to = txtMonTo;
        } else if (txtTueFrom != '' && txtTueTo != '') {
            var from = txtTueFrom;
            var to = txtTueTo;
        } else if (txtWedFrom != '' && txtWedTo != '') {
            var from = txtWedFrom;
            var to = txtWedTo;
        } else if (txtThuFrom != '' && txtThuTo != '') {
            var from = txtThuFrom;
            var to = txtThuTo;
        } else if (txtFriFrom != '' && txtFriTo != '') {
            var from = txtFriFrom;
            var to = txtFriTo;
        } else if (txtSatFrom != '' && txtSatTo != '') {
            var from = txtSatFrom;
            var to = txtSatTo;
        }
        jQuery('#txtSunFrom').val(from);
        jQuery('#txtSunTo').val(to);
        jQuery('#txtMonFrom').val(from);
        jQuery('#txtMonTo').val(to);
        jQuery('#txtTueFrom').val(from);
        jQuery('#txtTueTo').val(to);
        jQuery('#txtWedFrom').val(from);
        jQuery('#txtWedTo').val(to);
        jQuery('#txtThuFrom').val(from);
        jQuery('#txtThuTo').val(to);
        jQuery('#txtFriFrom').val(from);
        jQuery('#txtFriTo').val(to);
        jQuery('#txtSatFrom').val(from);
        jQuery('#txtSatTo').val(to);
    });
    jQuery('#chkreset').on('click', function () {
        jQuery('#txtSunFrom').val('');
        jQuery('#txtSunTo').val('');
        jQuery('#txtMonFrom').val('');
        jQuery('#txtMonTo').val('');
        jQuery('#txtTueFrom').val('');
        jQuery('#txtTueTo').val('');
        jQuery('#txtWedFrom').val('');
        jQuery('#txtWedTo').val('');
        jQuery('#txtThuFrom').val('');
        jQuery('#txtThuTo').val('');
        jQuery('#txtFriFrom').val('');
        jQuery('#txtFriTo').val('');
        jQuery('#txtSatFrom').val('');
        jQuery('#txtSatTo').val('');
    });
    jQuery('.view_block_data').on('click', function (event) {
        event.preventDefault();
        jQuery(this).closest('tr').next('tr').slideToggle();
    });
    //Solve searching issue for role and text field
    jQuery('#srole').on('focus', function () {
        jQuery('#txtUsername').val('');
    });
    jQuery('#txtUsername').on('focus', function () {
        // jQuery('#srole').val('');
    });
    //Datepicker
    jQuery('.view_block_data_all').on('click', function (event) {
        event.preventDefault();
        jQuery(this).closest('tr').next('tr').slideToggle();
    });

    //Pagination
    jQuery('#display_status').on('change', function () {
        if (this.value == "roles" || parseInt(jQuery(".total-pages").html()) == 0) {
            jQuery(".tablenav-pages").css("display", "none");
            jQuery("div#screen-options-link-wrap").css("display", "none");
        }
        else if (parseInt(jQuery(".total-pages").html()) > 1) {
            jQuery(".tablenav-pages").css("display", "block");
            jQuery("div#screen-options-link-wrap").css("display", "block");
        }
    });

    jQuery(document).ready(function() {
        // Function to update the hidden input with selected user IDs
        function updateSelectedUsers() {
            var selected = [];
            jQuery('#username tbody .check-column input[type=checkbox]:checked').each(function() {
                selected.push(jQuery(this).val());
            });
            jQuery('#role tbody .check-column input[type=checkbox]:checked').each(function() {
                selected.push(jQuery(this).val());
            });
            jQuery('#blk_username_role').val(selected.join(','));
        }
    
        // Function to update bulk checkbox based on individual checkboxes
        function updateBulkCheckbox() {
            var allChecked = jQuery('#username tbody .check-column input[type=checkbox]').length === 
                             jQuery('#username tbody .check-column input[type=checkbox]:checked').length;
            jQuery('#username thead .check-column input[type=checkbox], #username tfoot .check-column input[type=checkbox]').prop('checked', allChecked);
            var allCheckedRole = jQuery('#role tbody .check-column input[type=checkbox]').length === 
                                 jQuery('#role tbody .check-column input[type=checkbox]:checked').length;
            jQuery('#role thead .check-column input[type=checkbox], #role tfoot .check-column input[type=checkbox]').prop('checked', allCheckedRole);
        }
    
        // Update individual checkboxes when the bulk checkbox is changed
        jQuery('#username thead .check-column input[type=checkbox], #username tfoot .check-column input[type=checkbox]').on("change", function() {
            var isChecked = jQuery(this).prop('checked');
            jQuery('#username tbody .check-column input[type=checkbox]').prop('checked', isChecked);
            updateSelectedUsers();
            updateBulkCheckbox();
        });
        jQuery('#role thead .check-column input[type=checkbox], #role tfoot .check-column input[type=checkbox]').on("change", function() {
            var isChecked = jQuery(this).prop('checked');
            jQuery('#role tbody .check-column input[type=checkbox]').prop('checked', isChecked);
            updateSelectedUsers();
            updateBulkCheckbox();
        });
    
        // Update bulk checkbox when an individual checkbox is changed
        jQuery('#username tbody .check-column input[type=checkbox]').on("change", function() {
            updateBulkCheckbox();
            updateSelectedUsers();
        });
        jQuery('#role tbody .check-column input[type=checkbox]').on("change", function() {
            updateBulkCheckbox();
            updateSelectedUsers();
        });
    
    });
    
    
    jQuery('.ublk_bulk_btn').on("click", function () {
        var blk_action = jQuery('#ublk_bulk_actions').val();
        var blk_username_role = jQuery('#blk_username_role').val();
        var multi_user_roles = jQuery('#hidden_cmbUserBy').val();
        var data = String(window.location.href).replace(/#/, "");
        if(blk_action != ""){
            if (multi_user_roles == 'role') {
                window.location.href = data + '&action=' + blk_action + '&role=' + blk_username_role;
            } else {
                window.location.href = data + '&action=' + blk_action + '&username=' + blk_username_role;
            }
        }else{
            var url = new URL(data);
            url.searchParams.set("username", ""); 
            window.location.href = url.href; 
        }
    });
    
    var url = new URL(document.location.href);
    var params = new URLSearchParams( url.search );
    var reset = params.get("reset");
    var role = params.get("role");
    if( 1 == reset && '' != role ) {
        jQuery(".frmExport .actions").css("top", "-15px");
        jQuery(".frmExport").css({ "left": "225px", "bottom": "-36px" });
    }
});

/**
 *
 * @description change user function
 */
function changeUserBy() {
    var cmbUserBy = jQuery('#cmbUserBy').val();
    jQuery('.user-records').hide();
    jQuery('#' + cmbUserBy).show();
    jQuery('#hidden_cmbUserBy').val(cmbUserBy);
    var btnVal = jQuery('#sbt-block').val();
    var is_update = 0;
    if (btnVal.toLowerCase().indexOf("update") < 0) {
        is_update = 1;
        var new_btnval = btnVal.replace("User", "Role");
        var new_btnval1 = btnVal.replace("Role", "User");
    }
    if (cmbUserBy == 'role') {
        jQuery('.filter_div').hide();
        if (is_update == 1) {
            jQuery('#sbt-block').val(new_btnval);
        }
    }
    else {
        jQuery('.filter_div').show();
        if (is_update == 1) {
            jQuery('#sbt-block').val(new_btnval1);
        }
    }
}

/**
 *
 * @description click function for search user
 */
function searchUser() {
    jQuery('#filter_action').trigger('click');
}

function ublk_show_hide_permission() {
    jQuery('.ublk_permission_cover').slideToggle();
}

function ublk_submit_optin(options) {
    var result = {};
    result.action = 'ublk_submit_optin';
    result.email = jQuery('#ublk_admin_email').val();
    result.type = options;
    var nonce = jQuery('#ublk_submit_optin_nonce').val();
    result.nonce = nonce;

    if (options == 'submit') {
        if (jQuery('input#ublk_agree_gdpr').is(':checked')) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: result,
                error: function () { },
                success: function () {
                    window.location.href = "admin.php?page=block_user";
                },
                complete: function () {
                    window.location.href = "admin.php?page=block_user";
                }
            });
        }
        else {
            jQuery('.ublk_agree_gdpr_lbl').css('color', '#ff0000');
        }
    }
    else if (options == 'deactivate') {
        if (jQuery('input#ublk_agree_gdpr_deactivate').is(':checked')) {
            var ublk_plugin_admin = jQuery('.documentation_ublk_plugin').closest('div').find('.deactivate').find('a');
            result.selected_option_de = jQuery('input[name=sol_deactivation_reasons_ublk]:checked', '#frmDeactivationublk').val();
            result.selected_option_de_id = jQuery('input[name=sol_deactivation_reasons_ublk]:checked', '#frmDeactivationublk').attr("id");
            result.selected_option_de_text = jQuery("label[for='" + result.selected_option_de_id + "']").text();
            result.selected_option_de_other = jQuery('.sol_deactivation_reason_other_ublk').val();
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: result,
                error: function () { },
                success: function () {
                    window.location.href = ublk_plugin_admin.attr('href');
                },
                complete: function () {
                    window.location.href = ublk_plugin_admin.attr('href');
                }
            });
        }
        else {
            jQuery('.ublk_agree_gdpr_lbl').css('color', '#ff0000');
        }
    }
    else {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: result,
            error: function () { },
            success: function () {
                window.location.href = "admin.php?page=block_user";
            },
            complete: function () {
                window.location.href = "admin.php?page=block_user";
            }
        });
    }
}

function change_thickbox_size_ublk() {
    jQuery(document).find('#TB_window').width('700').height('420').css('margin-left', -700 / 2);
    jQuery(document).find('#TB_ajaxContent').width('640');
    jQuery(document).find('#TB_ajaxContent').addClass('ub_deactive_window');
    var doc_height = jQuery(window).height();
    var doc_space = doc_height - 400;
    if (doc_space > 0) {
        jQuery(document).find('#TB_window').css('margin-top', doc_space / 2);
    }
}

function checkOtherDeactivate() {
    var selected_option_de = jQuery('input[name=sol_deactivation_reasons_ublk]:checked', '#frmDeactivationublk').val();
    if (selected_option_de == '6') {
        jQuery('.sol_deactivation_reason_other_ublk').val('');
        jQuery('.sol_deactivation_reason_other_ublk').show();
    }
    else {
        jQuery('.sol_deactivation_reason_other_ublk').val('');
        jQuery('.sol_deactivation_reason_other_ublk').hide();
    }
}

jQuery(window).on('load', function () {
    jQuery('#subscribe_thickbox').trigger('click');
    jQuery("#TB_closeWindowButton").on('click', function () {
        jQuery.post(ajaxurl,
            {
                'action': 'close_tab'
            });
    });
});
