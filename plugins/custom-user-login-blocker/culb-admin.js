jQuery(function ($) {

    // Select2 user search
    $('#culb-users').select2({
        placeholder: 'Search users...',
        minimumInputLength: 1,
        ajax: {
            url: culbAjax.ajaxurl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    action: 'culb_search_users',
                    _ajax_nonce: culbAjax.nonce,
                    q: params.term
                };
            },
            processResults: function (data) {
                return { results: data };
            }
        }
    });

    function loadBlockedUsers() {
        $.post(culbAjax.ajaxurl, {
            action: 'culb_get_blocked_users',
            _ajax_nonce: culbAjax.nonce
        }, function (res) {

            if (!res.success) return;

            let rows = '';
            res.data.forEach(u => {
                rows += `
                    <tr>
                        <td>${u.name}</td>
                        <td>${u.email}</td>
                        <td>${u.until}</td>
                        <td>${u.by}</td>
                        <td>
                            <button class="button culb-unblock" data-id="${u.id}">
                                Unblock
                            </button>
                        </td>
                    </tr>
                `;
            });

            $('#culb-table tbody').html(rows);
        });
    }

    function update(mode, users = null) {
        $.post(culbAjax.ajaxurl, {
            action: 'culb_update_block',
            _ajax_nonce: culbAjax.nonce,
            mode: mode,
            users: users ?? $('#culb-users').val(),
            role: $('#culb-role').val(),
            until: $('#culb-until').val()
        }, function (res) {
            $('#culb-result').html('<div class="updated notice"><p>' + res.data + '</p></div>');
            loadBlockedUsers();
        });
    }

    $('#culb-block').on('click', () => update('block'));
    $('#culb-unblock').on('click', () => update('unblock'));

    $(document).on('click', '.culb-unblock', function () {
        update('unblock', [$(this).data('id')]);
    });

    loadBlockedUsers();
});
