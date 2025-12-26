jQuery(function ($) {

    $('#culb-users').select2({
        ajax: {
            url: culbAjax.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: params => ({
                q: params.term,
                action: 'culb_search_users',
                _ajax_nonce: culbAjax.nonce
            })
        }
    });

    function update(mode) {
        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            _ajax_nonce: culbAjax.nonce,
            mode: mode,
            users: $('#culb-users').val(),
            role: $('#culb-role').val(),
            until: $('#culb-until').val()
        }, res => {
            $('#culb-result').html('<div class="updated notice"><p>' + res.data + '</p></div>');
        });
    }

    $('#culb-block').on('click', () => update('block'));
    $('#culb-unblock').on('click', () => update('unblock'));

});
