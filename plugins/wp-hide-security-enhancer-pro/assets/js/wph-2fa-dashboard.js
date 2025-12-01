

    jQuery(document).ready(function($) {
        jQuery(document).on('click', '#wph_2fa_rc_regenerate', function(e) {
            e.preventDefault();
            
            var el_wrapper  =   jQuery( this ).closest('td');
            
            // Make AJAX request
            jQuery.ajax({
                url: wph_2fa_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: '2fa_rc_regenerate',
                    nonce: wph_2fa_ajax_obj.nonce
                },
                success: function(response) {
                    jQuery( el_wrapper ).html( response );
                },
                error: function() {
                    alert('AJAX request failed.');
                }
            });
        });
        
        jQuery(document).on('click', '#wph_2fa_app_reset', function(e) {
            e.preventDefault();
            
            var el_wrapper  =   jQuery( this ).closest('td');
            
            // Make AJAX request
            jQuery.ajax({
                url: wph_2fa_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: '2fa_app_reset',
                    nonce: wph_2fa_ajax_obj.nonce
                },
                success: function(response) {
                    jQuery( el_wrapper ).html( response );
                },
                error: function() {
                    alert('AJAX request failed.');
                }
            });
        });
        
        jQuery(document).on('click', '#2fa_app_code_submit', function(e) {
            e.preventDefault();
            
            var el_wrapper  =   jQuery( this ).closest('td');
            var app_code = jQuery('#_2fa_app_code').val();
                        
            // Make AJAX request
            jQuery.ajax({
                url: wph_2fa_ajax_obj.ajax_url,
                type: 'POST',
                data: {
                    action: '2fa_app_code_submit',
                    app_code:  app_code,
                    nonce: wph_2fa_ajax_obj.nonce
                },
                success: function(response) {
                    jQuery( el_wrapper ).html( response );
                },
                error: function() {
                    alert('AJAX request failed.');
                }
            });
        });
    });